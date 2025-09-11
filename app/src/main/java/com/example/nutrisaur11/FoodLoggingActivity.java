package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import java.util.ArrayList;
import java.util.List;

public class FoodLoggingActivity extends AppCompatActivity {
    private static final String TAG = "FoodLoggingActivity";
    
    // UI Elements
    private ImageView backButton;
    private TextView mealCategoryText;
    private ImageView mealCategoryDropdown;
    private EditText searchEditText;
    private ImageView filterButton;
    private Button quickLogButton;
    private Button createFoodButton;
    private TextView recentTab, favoritesTab, addedFoodTab;
    private ListView foodListView;
    
    // Data
    private String currentMealCategory;
    private int maxCalories;
    private List<FoodItem> foodItems = new ArrayList<>();
    private List<FoodItem> recommendedFoods = new ArrayList<>(); // Cache for recommended foods
    private FoodItemAdapter foodAdapter;
    
    // Services
    private FatSecretService fatSecretService;
    private FavoritesManager favoritesManager;
    private AddedFoodManager addedFoodManager;
    private CalorieTracker calorieTracker;
    private GeminiCacheManager cacheManager;
    private Handler mainHandler;
    
    // User data
    private UserProfile userProfile;
    private java.util.Map<String, String> databaseUserData;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_food_logging);
        
        // Get meal category and max calories from intent
        currentMealCategory = getIntent().getStringExtra("meal_category");
        maxCalories = getIntent().getIntExtra("max_calories", 500);
        
        // Get user data from intent if available
        android.os.Bundle userDataBundle = getIntent().getBundleExtra("user_data");
        if (userDataBundle != null) {
            databaseUserData = new java.util.HashMap<>();
            for (String key : userDataBundle.keySet()) {
                databaseUserData.put(key, userDataBundle.getString(key));
            }
            Log.d(TAG, "Received user data from database: " + databaseUserData.get("name") + " (BMI: " + databaseUserData.get("bmi") + ")");
        } else {
            Log.w(TAG, "No user data received from FoodActivity, will use SharedPreferences fallback");
        }
        
        // Initialize services
        fatSecretService = new FatSecretService();
        favoritesManager = new FavoritesManager(this);
        addedFoodManager = new AddedFoodManager(this);
        calorieTracker = new CalorieTracker(this);
        cacheManager = new GeminiCacheManager(this);
        mainHandler = new Handler(Looper.getMainLooper());
        
        // Load user profile (prioritize database data over SharedPreferences)
        userProfile = getUserProfile();
        
        // Initialize views
        initializeViews();
        
        // Setup click listeners
        setupClickListeners();
        
        // Load food data
        loadFoodData();
        
        Log.d(TAG, "FoodLoggingActivity created for " + currentMealCategory + " (max " + maxCalories + " kcal)");
        Log.d(TAG, "Initial food items count: " + foodItems.size());
    }
    
    private void initializeViews() {
        // Header elements
        backButton = findViewById(R.id.back_button);
        mealCategoryText = findViewById(R.id.meal_category_text);
        mealCategoryDropdown = findViewById(R.id.meal_category_dropdown);
        
        // Search and action bar
        searchEditText = findViewById(R.id.search_edit_text);
        filterButton = findViewById(R.id.filter_button);
        quickLogButton = findViewById(R.id.quick_log_button);
        createFoodButton = findViewById(R.id.create_food_button);
        
        // Tabs
        recentTab = findViewById(R.id.recent_tab);
        favoritesTab = findViewById(R.id.favorites_tab);
        addedFoodTab = findViewById(R.id.added_food_tab);
        
        // Food list
        foodListView = findViewById(R.id.food_list_view);
        
        // Set meal category text
        if (mealCategoryText != null) {
            mealCategoryText.setText(currentMealCategory);
        }
        
        // Initialize food adapter
        foodAdapter = new FoodItemAdapter(this, foodItems);
        foodAdapter.setMealCategory(currentMealCategory);
        if (foodListView != null) {
            foodListView.setAdapter(foodAdapter);
        }
        
        // Set initial tab to recommended
        setActiveTab("recent");
    }
    
    private void setupClickListeners() {
        // Back button
        if (backButton != null) {
            backButton.setOnClickListener(v -> {
                finish();
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            });
        }
        
        // Meal category dropdown
        if (mealCategoryDropdown != null) {
            mealCategoryDropdown.setOnClickListener(v -> {
                // TODO: Show meal category picker
                Log.d(TAG, "Meal category dropdown clicked");
            });
        }
        
        // Search functionality
        if (searchEditText != null) {
            searchEditText.setOnEditorActionListener((v, actionId, event) -> {
                String query = searchEditText.getText().toString().trim();
                if (!query.isEmpty()) {
                    searchFoods(query);
                }
                return true;
            });
        }
        
        // Filter button
        if (filterButton != null) {
            filterButton.setOnClickListener(v -> {
                // TODO: Show filter options
                Log.d(TAG, "Filter button clicked");
            });
        }
        
        // Hide quick log and create food buttons
        if (quickLogButton != null) {
            quickLogButton.setVisibility(View.GONE);
        }
        if (createFoodButton != null) {
            createFoodButton.setVisibility(View.GONE);
        }
        
        // Tab clicks
        if (recentTab != null) {
            recentTab.setOnClickListener(v -> setActiveTab("recent"));
        }
        if (favoritesTab != null) {
            favoritesTab.setOnClickListener(v -> setActiveTab("favorites"));
        }
        if (addedFoodTab != null) {
            addedFoodTab.setOnClickListener(v -> setActiveTab("added_food"));
        }
        
        // Food item clicks
        if (foodListView != null) {
            foodListView.setOnItemClickListener((parent, view, position, id) -> {
                FoodItem selectedFood = foodItems.get(position);
                onFoodItemSelected(selectedFood);
            });
        }
    }
    
    private void setActiveTab(String tab) {
        // Reset all tabs
        if (recentTab != null) recentTab.setBackgroundResource(android.R.color.transparent);
        if (favoritesTab != null) favoritesTab.setBackgroundResource(android.R.color.transparent);
        if (addedFoodTab != null) addedFoodTab.setBackgroundResource(android.R.color.transparent);
        
        // Set active tab
        switch (tab) {
            case "recent":
                if (recentTab != null) recentTab.setBackgroundResource(R.drawable.tab_background_active);
                loadRecentFoods();
                break;
            case "favorites":
                if (favoritesTab != null) favoritesTab.setBackgroundResource(R.drawable.tab_background_active);
                loadFavoriteFoods();
                break;
            case "added_food":
                if (addedFoodTab != null) addedFoodTab.setBackgroundResource(R.drawable.tab_background_active);
                loadAddedFoods();
                break;
        }
    }
    
    private void loadFoodData() {
        // Set max calories for this meal in calorie tracker
        calorieTracker.setMealMaxCalories(currentMealCategory, maxCalories);
        
        // Load personalized foods based on meal category, calorie limit, and user profile
        if (userProfile != null) {
            Log.d(TAG, "Loading personalized foods for " + currentMealCategory + " (BMI: " + userProfile.getBmi() + ", Age: " + userProfile.getAge() + ")");
            
            // Check cache first
            GeminiCacheManager.CachedRecommendation cached = cacheManager.getCachedRecommendations(currentMealCategory, userProfile);
            if (cached != null) {
                Log.d(TAG, "Using cached recommendations for " + currentMealCategory);
                mainHandler.post(() -> {
                    recommendedFoods.clear();
                    recommendedFoods.addAll(cached.getFoods());
                    foodItems.clear();
                    foodItems.addAll(cached.getFoods());
                    foodAdapter.setHideAddButton(false);
                    foodAdapter.notifyDataSetChanged();
                    Log.d(TAG, "Loaded " + cached.getFoods().size() + " cached foods for " + currentMealCategory);
                });
                return;
            }
            
            fatSecretService.getPersonalizedFoods(currentMealCategory, maxCalories, userProfile, new FatSecretService.FoodSearchCallback() {
                @Override
                public void onSuccess(List<FoodItem> foods) {
                    mainHandler.post(() -> {
                        // Cache the recommended foods
                        recommendedFoods.clear();
                        recommendedFoods.addAll(foods);
                        
                        // Update current display immediately since we start on recent tab
                        foodItems.clear();
                        foodItems.addAll(foods);
                        foodAdapter.setHideAddButton(false);
                        foodAdapter.notifyDataSetChanged();
                        
                        Log.d(TAG, "Loaded " + foods.size() + " personalized foods for " + currentMealCategory);
                    });
                }
                
                @Override
                public void onError(String error) {
                    mainHandler.post(() -> {
                        Log.e(TAG, "Error loading personalized foods: " + error);
                        // Fallback to regular search
                        loadDefaultFoods();
                    });
                }
            });
        } else {
            Log.w(TAG, "User profile not available, using default food search");
            // Fallback to regular search if user profile is not available
            fatSecretService.searchFoods(currentMealCategory, maxCalories, new FatSecretService.FoodSearchCallback() {
                @Override
                public void onSuccess(List<FoodItem> foods) {
                    mainHandler.post(() -> {
                        // Cache the recommended foods
                        recommendedFoods.clear();
                        recommendedFoods.addAll(foods);
                        
                        // Update current display immediately since we start on recent tab
                        foodItems.clear();
                        foodItems.addAll(foods);
                        foodAdapter.setHideAddButton(false);
                        foodAdapter.notifyDataSetChanged();
                        
                        Log.d(TAG, "Loaded " + foods.size() + " default foods for " + currentMealCategory);
                    });
                }
                
                @Override
                public void onError(String error) {
                    mainHandler.post(() -> {
                        Log.e(TAG, "Error loading foods: " + error);
                        // Show default foods or error message
                        loadDefaultFoods();
                    });
                }
            });
        }
    }
    
    private void searchFoods(String query) {
        fatSecretService.searchFoods(query, maxCalories, new FatSecretService.FoodSearchCallback() {
            @Override
            public void onSuccess(List<FoodItem> foods) {
                mainHandler.post(() -> {
                    foodItems.clear();
                    foodItems.addAll(foods);
                    foodAdapter.notifyDataSetChanged();
                    Log.d(TAG, "Search results: " + foods.size() + " foods");
                });
            }
            
            @Override
            public void onError(String error) {
                mainHandler.post(() -> {
                    Log.e(TAG, "Search error: " + error);
                });
            }
        });
    }
    
    private void loadRecentFoods() {
        // Load recommended foods from cache
        mainHandler.post(() -> {
            foodItems.clear();
            foodItems.addAll(recommendedFoods);
            // Show add/remove buttons in Recent tab
            foodAdapter.setHideAddButton(false);
            foodAdapter.notifyDataSetChanged();
            Log.d(TAG, "Loaded " + recommendedFoods.size() + " recent/recommended foods");
        });
    }
    
    private void loadFavoriteFoods() {
        List<FoodItem> favorites = favoritesManager.getFavorites();
        
        // Filter favorites by calorie limit
        List<FoodItem> filteredFavorites = new ArrayList<>();
        for (FoodItem food : favorites) {
            if (food.getCalories() <= maxCalories) {
                filteredFavorites.add(food);
            }
        }
        
        mainHandler.post(() -> {
            foodItems.clear();
            foodItems.addAll(filteredFavorites);
            // Show add/remove buttons in Favorites tab
            foodAdapter.setHideAddButton(false);
            foodAdapter.notifyDataSetChanged();
            Log.d(TAG, "Loaded " + filteredFavorites.size() + " favorite foods");
        });
    }
    
    private void loadAddedFoods() {
        List<FoodItem> addedFoods = addedFoodManager.getAddedFoods();
        
        // Filter added foods by calorie limit
        List<FoodItem> filteredAddedFoods = new ArrayList<>();
        for (FoodItem food : addedFoods) {
            if (food.getCalories() <= maxCalories) {
                filteredAddedFoods.add(food);
            }
        }
        
        mainHandler.post(() -> {
            foodItems.clear();
            foodItems.addAll(filteredAddedFoods);
            // Hide add/remove buttons in Added Food tab
            foodAdapter.setHideAddButton(true);
            foodAdapter.notifyDataSetChanged();
            Log.d(TAG, "Loaded " + filteredAddedFoods.size() + " added foods");
        });
    }
    
    
    private void loadDefaultFoods() {
        // Default foods for testing
        foodItems.clear();
        foodItems.add(new FoodItem("1", "Grilled Chicken Breast", 165, 100, "g"));
        foodItems.add(new FoodItem("2", "Brown Rice", 111, 100, "g"));
        foodItems.add(new FoodItem("3", "Steamed Broccoli", 34, 100, "g"));
        foodItems.add(new FoodItem("4", "Salmon Fillet", 208, 100, "g"));
        foodItems.add(new FoodItem("5", "Quinoa", 120, 100, "g"));
        foodItems.add(new FoodItem("6", "Mixed Vegetables", 25, 100, "g"));
        foodAdapter.notifyDataSetChanged();
    }
    
    private void onFoodItemSelected(FoodItem foodItem) {
        // TODO: Show food details dialog or add to meal
        Log.d(TAG, "Food selected: " + foodItem.getName() + " (" + foodItem.getCalories() + " kcal)");
        
        // For now, just show a simple message
        // In the future, this will open a dialog to add the food to the meal
    }
    
    /**
     * Get user profile from database data (no SharedPreferences)
     */
    private UserProfile getUserProfile() {
        try {
            if (databaseUserData != null && !databaseUserData.isEmpty()) {
                Log.d(TAG, "Creating user profile from database data...");
                return createUserProfileFromDatabaseData(databaseUserData);
            } else {
                Log.w(TAG, "No database user data available, cannot create user profile");
                return null;
            }
        } catch (Exception e) {
            Log.e(TAG, "Error creating user profile from database data: " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Create user profile from community_users table data
     */
    private UserProfile createUserProfileFromDatabaseData(java.util.Map<String, String> userData) {
        try {
            Log.d(TAG, "Creating user profile from community_users table data...");
            
            // Extract data from database map
            String userId = userData.getOrDefault("email", "");
            String name = userData.getOrDefault("name", "");
            int age = 25; // Default age
            String birthday = userData.getOrDefault("birthday", "");
            
            // First try to get age from age field
            try {
                String ageStr = userData.getOrDefault("age", "");
                if (!ageStr.isEmpty()) {
                    age = Integer.parseInt(ageStr);
                    Log.d(TAG, "Using age from database: " + age);
                } else {
                    throw new NumberFormatException("Age field is empty");
                }
            } catch (NumberFormatException e) {
                // Calculate age from birthday if age field is empty
                if (!birthday.isEmpty()) {
                    try {
                        java.text.SimpleDateFormat sdf = new java.text.SimpleDateFormat("yyyy-MM-dd");
                        java.util.Date birthDate = sdf.parse(birthday);
                        java.util.Calendar birth = java.util.Calendar.getInstance();
                        birth.setTime(birthDate);
                        java.util.Calendar today = java.util.Calendar.getInstance();
                        age = today.get(java.util.Calendar.YEAR) - birth.get(java.util.Calendar.YEAR);
                        if (today.get(java.util.Calendar.DAY_OF_YEAR) < birth.get(java.util.Calendar.DAY_OF_YEAR)) {
                            age--;
                        }
                        Log.d(TAG, "Calculated age from birthday '" + birthday + "': " + age);
                    } catch (Exception ex) {
                        Log.w(TAG, "Could not calculate age from birthday '" + birthday + "', using default: 25. Error: " + ex.getMessage());
                    }
                } else {
                    Log.w(TAG, "No birthday provided, using default age: 25");
                }
            }
            
            String gender = userData.getOrDefault("sex", "Male");
            double weight = 70.0; // Default weight
            try {
                double weightVal = Double.parseDouble(userData.getOrDefault("weight_kg", "70"));
                if (weightVal > 0) {
                    weight = weightVal;
                } else {
                    Log.w(TAG, "Weight is 0 or invalid, using default: 70");
                }
            } catch (NumberFormatException e) {
                Log.w(TAG, "Could not parse weight, using default: 70");
            }
            
            double height = 170.0; // Default height
            try {
                double heightVal = Double.parseDouble(userData.getOrDefault("height_cm", "170"));
                if (heightVal > 0) {
                    height = heightVal;
                } else {
                    Log.w(TAG, "Height is 0 or invalid, using default: 170");
                }
            } catch (NumberFormatException e) {
                Log.w(TAG, "Could not parse height, using default: 170");
            }
            
            // Set default values for missing fields
            String activityLevel = "Moderately Active";
            String healthGoals = "Maintain weight";
            String dietaryPreferences = "None";
            String allergies = "None";
            String medicalConditions = "None";
            boolean isPregnant = "1".equals(userData.getOrDefault("is_pregnant", "0")) || 
                               "Yes".equals(userData.getOrDefault("is_pregnant", "No"));
            int pregnancyWeek = 0;
            String occupation = "Office worker";
            String lifestyle = "Moderate";
            
            Log.d(TAG, "Database user data: name=" + name + ", age=" + age + ", weight=" + weight + ", height=" + height + ", gender=" + gender);
            
            return new UserProfile(userId, name, age, gender, weight, height, activityLevel,
                    healthGoals, dietaryPreferences, allergies, medicalConditions, isPregnant,
                    pregnancyWeek, occupation, lifestyle);

        } catch (Exception e) {
            Log.e(TAG, "Error creating user profile from database data: " + e.getMessage());
            return null;
        }
    }


    @Override
    public void onBackPressed() {
        super.onBackPressed();
        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
    }
}
