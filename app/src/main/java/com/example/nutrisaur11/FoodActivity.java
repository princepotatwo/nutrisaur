package com.example.nutrisaur11;

import android.os.Bundle;
import android.view.View;
import android.content.Intent;
import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import android.util.Log;
import android.widget.TextView;
import android.widget.ProgressBar;
import android.widget.FrameLayout;
import android.os.Handler;
import android.os.Looper;

public class FoodActivity extends AppCompatActivity {
    private static final String TAG = "FoodActivity";
    
    // Meal category cards
    private CardView breakfastCard, lunchCard, dinnerCard, snacksCard;
    
    // Navigation views
    private View navHome, navFood, navFavorites, navAccount;

    // Nutrition UI elements
    private LoadingCalorieView caloriesLeftLoading, eatenCaloriesLoading, burnedCaloriesLoading;
    private LoadingCalorieView walkingCaloriesLoading, activityCaloriesLoading;
    private TextView carbsText, proteinText, fatText;
    private TextView carbsTargetText, proteinTargetText, fatTargetText;
    private ProgressBar carbsProgress, proteinProgress, fatProgress;
    private FrameLayout centerCircle;
    
    // Meal progress text views
    private TextView breakfastProgressText, lunchProgressText, dinnerProgressText, snacksProgressText;

    // Services
    private NutritionService nutritionService;
    private CommunityUserManager userManager;
    private Handler mainHandler;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_food);
        
        Log.d(TAG, "FoodActivity onCreate started");
        
        // Initialize services
        nutritionService = new NutritionService(this);
        userManager = new CommunityUserManager(this);
        mainHandler = new Handler(Looper.getMainLooper());
        
        // Set header title
        setupHeader();
        
        // Initialize views
        initializeViews();
        
        // Setup click listeners
        setupClickListeners();
        
        // Setup back press handler
        setupBackPressHandler();
        
        // Setup navigation
        setupNavigation();
        
        // First fetch user screening data from API, then load nutrition data
        fetchUserScreeningData();
    }
    
    private void setupHeader() {
        // Set header title and subtitle
        android.widget.TextView pageTitle = findViewById(R.id.page_title);
        android.widget.TextView pageSubtitle = findViewById(R.id.page_subtitle);
        if (pageTitle != null) {
            pageTitle.setText("AI FOOD RECOMMENDATIONS");
        }
        if (pageSubtitle != null) {
            pageSubtitle.setText("Personalized nutrition suggestions");
        }
    }
    
    private void initializeViews() {
        // Initialize meal category cards
        breakfastCard = findViewById(R.id.breakfast_card);
        lunchCard = findViewById(R.id.lunch_card);
        dinnerCard = findViewById(R.id.dinner_card);
        snacksCard = findViewById(R.id.snacks_card);
        
        // Initialize navigation views
        navHome = findViewById(R.id.nav_home);
        navFood = findViewById(R.id.nav_food);
        navFavorites = findViewById(R.id.nav_favorites);
        navAccount = findViewById(R.id.nav_account);
        
        // Initialize nutrition UI elements
        initializeNutritionViews();
        
        // Setup edit personalization button
        setupEditPersonalizationButton();
        
        Log.d(TAG, "Views initialized");
    }
    
    private void initializeNutritionViews() {
        // Initialize nutrition UI elements
        caloriesLeftLoading = findViewById(R.id.calories_left_loading);
        eatenCaloriesLoading = findViewById(R.id.eaten_calories_loading);
        burnedCaloriesLoading = findViewById(R.id.burned_calories_loading);
        walkingCaloriesLoading = findViewById(R.id.walking_calories_loading);
        activityCaloriesLoading = findViewById(R.id.activity_calories_loading);
        
        // Initialize meal progress text views
        breakfastProgressText = findViewById(R.id.breakfast_progress_text);
        lunchProgressText = findViewById(R.id.lunch_progress_text);
        dinnerProgressText = findViewById(R.id.dinner_progress_text);
        snacksProgressText = findViewById(R.id.snacks_progress_text);
        
        Log.d(TAG, "Nutrition views initialized");
    }
    
    private void setupEditPersonalizationButton() {
        // Find the edit personalization card and make it clickable
        androidx.cardview.widget.CardView editPersonalizationCard = findViewById(R.id.edit_personalization_card);
        if (editPersonalizationCard != null) {
            editPersonalizationCard.setOnClickListener(v -> {
                // Start food preferences activity instead of personalization
                Intent intent = new Intent(this, FoodPreferencesActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            });
        }
    }
    
    private void setupClickListeners() {
        // Breakfast card click
        if (breakfastCard != null) {
            breakfastCard.setOnClickListener(v -> {
                Log.d(TAG, "Breakfast card clicked");
                // TODO: Implement breakfast functionality
                showMealCategory("Breakfast");
            });
        }
        
        // Lunch card click
        if (lunchCard != null) {
            lunchCard.setOnClickListener(v -> {
                Log.d(TAG, "Lunch card clicked");
                // TODO: Implement lunch functionality
                showMealCategory("Lunch");
            });
        }
        
        // Dinner card click
        if (dinnerCard != null) {
            dinnerCard.setOnClickListener(v -> {
                Log.d(TAG, "Dinner card clicked");
                // TODO: Implement dinner functionality
                showMealCategory("Dinner");
            });
        }
        
        // Snacks card click
        if (snacksCard != null) {
            snacksCard.setOnClickListener(v -> {
                Log.d(TAG, "Snacks card clicked");
                // TODO: Implement snacks functionality
                showMealCategory("Snacks");
            });
        }
    }
    
    private void showMealCategory(String category) {
        Log.d(TAG, "Selected meal category: " + category);
        
        // Get max calories for this meal category from stored nutrition data
        int maxCalories = getMaxCaloriesForCategory(category);
        
        // Launch FoodLoggingActivity
        Intent intent = new Intent(this, FoodLoggingActivity.class);
        intent.putExtra("meal_category", category);
        intent.putExtra("max_calories", maxCalories);
        startActivity(intent);
        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
    }
    
    private int getMaxCaloriesForCategory(String category) {
        // Get stored meal distribution from SharedPreferences
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        
        switch (category.toLowerCase()) {
            case "breakfast":
                return prefs.getInt("breakfast_calories", 300);
            case "lunch":
                return prefs.getInt("lunch_calories", 420);
            case "dinner":
                return prefs.getInt("dinner_calories", 360);
            case "snacks":
                return prefs.getInt("snacks_calories", 120);
            default:
                return 300;
        }
    }
    
    /**
     * Show loading state for all calorie displays
     */
    private void showLoadingState() {
        if (caloriesLeftLoading != null) {
            caloriesLeftLoading.showLoading();
        }
        if (eatenCaloriesLoading != null) {
            eatenCaloriesLoading.showLoading();
        }
        if (burnedCaloriesLoading != null) {
            burnedCaloriesLoading.showLoading();
        }
        if (walkingCaloriesLoading != null) {
            walkingCaloriesLoading.showLoading();
        }
        if (activityCaloriesLoading != null) {
            activityCaloriesLoading.showLoading();
        }
    }

    /**
     * Fetch user screening data from community_users table and load nutrition data
     */
    private void fetchUserScreeningData() {
        Log.d(TAG, "Fetching user screening data from community_users table...");
        
        // Check if user is logged in
        if (!userManager.isLoggedIn()) {
            Log.w(TAG, "User not logged in, cannot fetch nutrition data");
            showDefaultNutritionData();
            return;
        }
        
        // Fetch user data from database in background thread
        new Thread(() -> {
            try {
                // Get user data from community_users table
                java.util.Map<String, String> userData = userManager.getCurrentUserDataFromDatabase();
                
                if (userData != null && !userData.isEmpty()) {
                    Log.d(TAG, "User screening data fetched from community_users table");
                    
                    // Load nutrition data with database user profile (no SharedPreferences)
                    mainHandler.post(() -> loadNutritionDataWithUserData(userData));
                } else {
                    Log.w(TAG, "No user screening data found in community_users table");
                    mainHandler.post(() -> showDefaultNutritionData());
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error fetching user screening data: " + e.getMessage());
                mainHandler.post(() -> showDefaultNutritionData());
            }
        }).start();
    }
    
    /**
     * Load nutrition data with user data from community_users table (no SharedPreferences)
     */
    private void loadNutritionDataWithUserData(java.util.Map<String, String> userData) {
        Log.d(TAG, "Loading nutrition data with user data from community_users table...");
        
        // Show loading state for all calorie displays
        showLoadingState();
        
        // Pass user data directly to NutritionService
        nutritionService.getNutritionRecommendationsWithUserData(userData, new NutritionService.NutritionCallback() {
            @Override
            public void onSuccess(NutritionData nutritionData) {
                mainHandler.post(() -> {
                    updateNutritionUI(nutritionData);
                    Log.d(TAG, "Nutrition data loaded successfully with database user data");
                });
            }
            
            @Override
            public void onError(String error) {
                mainHandler.post(() -> {
                    Log.e(TAG, "Error loading nutrition data: " + error);
                    // Show error message or use default values
                    showDefaultNutritionData();
                });
            }
        });
    }

    /**
     * Load nutrition data from Gemini API
     */
    private void loadNutritionData() {
        Log.d(TAG, "Loading nutrition data...");
        
        // Show loading state for all calorie displays
        showLoadingState();
        
        nutritionService.getNutritionRecommendations(new NutritionService.NutritionCallback() {
            @Override
            public void onSuccess(NutritionData nutritionData) {
                mainHandler.post(() -> {
                    updateNutritionUI(nutritionData);
                    Log.d(TAG, "Nutrition data loaded successfully");
                });
            }
            
            @Override
            public void onError(String error) {
                mainHandler.post(() -> {
                    Log.e(TAG, "Error loading nutrition data: " + error);
                    // Show error message or use default values
                    showDefaultNutritionData();
                });
            }
        });
    }
    
    /**
     * Update UI with nutrition data and animations
     */
    private void updateNutritionUI(NutritionData nutritionData) {
        if (nutritionData == null) return;
        
        Log.d(TAG, "Updating nutrition UI with data");
        
        // Update calorie information with animations
        updateCalorieData(nutritionData);
        
        // Update macronutrient data with animations
        updateMacronutrientData(nutritionData);
        
        // Update activity data with animations
        updateActivityData(nutritionData);
        
        // Update meal progress
        updateMealProgress(nutritionData);
        
        // Store meal distribution for meal category clicks
        storeMealDistribution(nutritionData.getMealDistribution());
    }
    
    /**
     * Update calorie data with animations
     */
    private void updateCalorieData(NutritionData nutritionData) {
        Log.d(TAG, "Calories Left: " + nutritionData.getCaloriesLeft());
        Log.d(TAG, "Calories Eaten: " + nutritionData.getCaloriesEaten());
        Log.d(TAG, "Calories Burned: " + nutritionData.getCaloriesBurned());
        
        // Update UI elements with loading views
        if (caloriesLeftLoading != null) {
            caloriesLeftLoading.showValue(String.valueOf(nutritionData.getCaloriesLeft()));
        }
        if (eatenCaloriesLoading != null) {
            eatenCaloriesLoading.showValue(String.valueOf(nutritionData.getCaloriesEaten()));
        }
        if (burnedCaloriesLoading != null) {
            burnedCaloriesLoading.showValue(String.valueOf(nutritionData.getCaloriesBurned()));
        }
        
        // Store in SharedPreferences for future use
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        editor.putInt("calories_left", nutritionData.getCaloriesLeft());
        editor.putInt("calories_eaten", nutritionData.getCaloriesEaten());
        editor.putInt("calories_burned", nutritionData.getCaloriesBurned());
        editor.apply();
    }
    
    /**
     * Update meal progress text views
     */
    private void updateMealProgress(NutritionData nutritionData) {
        NutritionData.MealDistribution mealDistribution = nutritionData.getMealDistribution();
        if (mealDistribution == null) return;
        
        // Update breakfast progress
        if (breakfastProgressText != null) {
            String breakfastText = mealDistribution.getBreakfastEaten() + " / " + 
                                 mealDistribution.getBreakfastCalories() + " kcal";
            breakfastProgressText.setText(breakfastText);
        }
        
        // Update lunch progress
        if (lunchProgressText != null) {
            String lunchText = mealDistribution.getLunchEaten() + " / " + 
                             mealDistribution.getLunchCalories() + " kcal";
            lunchProgressText.setText(lunchText);
        }
        
        // Update dinner progress
        if (dinnerProgressText != null) {
            String dinnerText = mealDistribution.getDinnerEaten() + " / " + 
                              mealDistribution.getDinnerCalories() + " kcal";
            dinnerProgressText.setText(dinnerText);
        }
        
        // Update snacks progress
        if (snacksProgressText != null) {
            String snacksText = mealDistribution.getSnacksEaten() + " / " + 
                              mealDistribution.getSnacksCalories() + " kcal";
            snacksProgressText.setText(snacksText);
        }
        
        Log.d(TAG, "Meal progress updated");
    }
    
    /**
     * Update macronutrient data with animations
     */
    private void updateMacronutrientData(NutritionData nutritionData) {
        NutritionData.Macronutrients macros = nutritionData.getMacronutrients();
        if (macros == null) return;
        
        // For now, we'll store the data and log it
        Log.d(TAG, "Carbs: " + macros.getCarbs() + "/" + macros.getCarbsTarget() + "g");
        Log.d(TAG, "Protein: " + macros.getProtein() + "/" + macros.getProteinTarget() + "g");
        Log.d(TAG, "Fat: " + macros.getFat() + "/" + macros.getFatTarget() + "g");
        
        // Store in SharedPreferences for future use
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        editor.putInt("carbs_current", macros.getCarbs());
        editor.putInt("carbs_target", macros.getCarbsTarget());
        editor.putInt("protein_current", macros.getProtein());
        editor.putInt("protein_target", macros.getProteinTarget());
        editor.putInt("fat_current", macros.getFat());
        editor.putInt("fat_target", macros.getFatTarget());
        editor.apply();
    }
    
    /**
     * Update activity data with animations
     */
    private void updateActivityData(NutritionData nutritionData) {
        NutritionData.ActivityData activity = nutritionData.getActivity();
        if (activity == null) return;
        
        // Update UI elements with loading views
        Log.d(TAG, "Walking Calories: " + activity.getWalkingCalories());
        Log.d(TAG, "Activity Calories: " + activity.getActivityCalories());
        
        if (walkingCaloriesLoading != null) {
            walkingCaloriesLoading.showValue(String.valueOf(activity.getWalkingCalories()));
        }
        if (activityCaloriesLoading != null) {
            activityCaloriesLoading.showValue(String.valueOf(activity.getActivityCalories()));
        }
        
        // Store in SharedPreferences for future use
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        editor.putInt("walking_calories", activity.getWalkingCalories());
        editor.putInt("activity_calories", activity.getActivityCalories());
        editor.apply();
    }
    
    /**
     * Store meal distribution for future use
     */
    private void storeMealDistribution(NutritionData.MealDistribution mealDistribution) {
        if (mealDistribution == null) return;
        
        // Store in SharedPreferences for meal category clicks
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        
        editor.putInt("breakfast_calories", mealDistribution.getBreakfastCalories());
        editor.putInt("lunch_calories", mealDistribution.getLunchCalories());
        editor.putInt("dinner_calories", mealDistribution.getDinnerCalories());
        editor.putInt("snacks_calories", mealDistribution.getSnacksCalories());
        
        editor.putString("breakfast_recommendation", mealDistribution.getBreakfastRecommendation());
        editor.putString("lunch_recommendation", mealDistribution.getLunchRecommendation());
        editor.putString("dinner_recommendation", mealDistribution.getDinnerRecommendation());
        editor.putString("snacks_recommendation", mealDistribution.getSnacksRecommendation());
        
        editor.apply();
        
        Log.d(TAG, "Meal distribution stored");
    }
    
    /**
     * Show default nutrition data when API fails
     */
    private void showDefaultNutritionData() {
        Log.d(TAG, "Showing default nutrition data");
        
        // Store default values in SharedPreferences
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        
        editor.putInt("calories_left", 2000);
        editor.putInt("calories_eaten", 0);
        editor.putInt("calories_burned", 0);
        editor.putInt("walking_calories", 0);
        editor.putInt("activity_calories", 0);
        editor.putInt("carbs_current", 0);
        editor.putInt("carbs_target", 250);
        editor.putInt("protein_current", 0);
        editor.putInt("protein_target", 150);
        editor.putInt("fat_current", 0);
        editor.putInt("fat_target", 67);
        
        editor.apply();
    }
    
    private void setupNavigation() {
        // Home navigation
        if (navHome != null) {
            navHome.setOnClickListener(v -> {
                Intent intent = new Intent(FoodActivity.this, MainActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
        });
        }

        // Food navigation (current page - no action needed)
        if (navFood != null) {
            navFood.setOnClickListener(v -> {
                // Already on food page, do nothing
        });
        }

        // Favorites navigation
        if (navFavorites != null) {
            navFavorites.setOnClickListener(v -> {
                Intent intent = new Intent(FoodActivity.this, FavoritesActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
        });
        }

        // Account navigation
        if (navAccount != null) {
            navAccount.setOnClickListener(v -> {
                Intent intent = new Intent(FoodActivity.this, AccountActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            });
        }
    }

    // Modern back press handling - register in onCreate
    private void setupBackPressHandler() {
        getOnBackPressedDispatcher().addCallback(this, new androidx.activity.OnBackPressedCallback(true) {
    @Override
            public void handleOnBackPressed() {
                // Handle back press - navigate to MainActivity
                Intent intent = new Intent(FoodActivity.this, MainActivity.class);
                startActivity(intent);
                finish();
            }
        });
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (nutritionService != null) {
            nutritionService.cleanup();
        }
    }

} 