package com.example.nutrisaur11;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ListView;
import android.widget.TextView;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class FoodAlternativesActivity extends Activity {
    private static final String TAG = "FoodAlternativesActivity";
    
    // UI Elements
    private ImageView backButton;
    private TextView originalFoodName;
    private LinearLayout loadingLayout;
    private LinearLayout contentLayout;
    private LinearLayout errorLayout;
    private ListView alternativesList;
    private Button retryButton;
    
    // Data
    private FoodItem originalFoodItem;
    private List<FoodItem> alternativeFoods = new ArrayList<>();
    private AlternativeFoodAdapter adapter;
    private UserProfile userProfile;
    
    // Services
    private ExecutorService executorService;
    private Handler mainHandler;
    private GeminiService geminiService;
    private AddedFoodManager addedFoodManager;
    private CalorieTracker calorieTracker;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_food_alternatives);
        
        // Get original food item from intent
        originalFoodItem = (FoodItem) getIntent().getSerializableExtra("food_item");
        if (originalFoodItem == null) {
            Log.e(TAG, "No food item provided");
            finish();
            return;
        }
        
        // Get meal category from intent
        String mealCategory = getIntent().getStringExtra("meal_category");
        if (mealCategory != null) {
            // Set the meal category on the original food item
            originalFoodItem.setMealCategory(mealCategory);
            Log.d(TAG, "Set meal category for alternatives: " + mealCategory);
        }
        
        // Initialize services
        executorService = Executors.newSingleThreadExecutor();
        mainHandler = new Handler(Looper.getMainLooper());
        geminiService = new GeminiService();
        addedFoodManager = new AddedFoodManager(this);
        calorieTracker = new CalorieTracker(this);
        
        // Load user profile
        userProfile = getUserProfile();
        
        // Initialize views
        initializeViews();
        
        // Setup click listeners
        setupClickListeners();
        
        // Set original food name
        originalFoodName.setText(originalFoodItem.getName());
        
        // Initialize adapter
        adapter = new AlternativeFoodAdapter(this, alternativeFoods);
        adapter.setCalorieChangeCallback(this::onCalorieChanged);
        // Set meal category for alternatives (use the same category as the original food)
        adapter.setMealCategory(originalFoodItem.getMealCategory());
        alternativesList.setAdapter(adapter);
        
        // Start loading alternatives
        loadAlternatives();
    }
    
    private void initializeViews() {
        backButton = findViewById(R.id.back_button);
        originalFoodName = findViewById(R.id.original_food_name);
        loadingLayout = findViewById(R.id.loading_layout);
        contentLayout = findViewById(R.id.content_layout);
        errorLayout = findViewById(R.id.error_layout);
        alternativesList = findViewById(R.id.alternatives_list);
        retryButton = findViewById(R.id.retry_button);
    }
    
    private void setupClickListeners() {
        backButton.setOnClickListener(v -> finish());
        retryButton.setOnClickListener(v -> loadAlternatives());
    }
    
    private UserProfile getUserProfile() {
        try {
            // Try to get user data from SharedPreferences first
            UserProfile profile = getUserProfileFromSharedPreferences();
            if (profile != null) {
                Log.d(TAG, "Loaded user profile from SharedPreferences: " + profile.getName() + " (BMI: " + profile.getBmi() + ")");
                return profile;
            }
            
            // Fallback to default profile
            Log.w(TAG, "Using default user profile");
            return createDefaultUserProfile();
            
        } catch (Exception e) {
            Log.e(TAG, "Error loading user profile: " + e.getMessage());
            return createDefaultUserProfile();
        }
    }
    
    private UserProfile getUserProfileFromSharedPreferences() {
        try {
            android.content.SharedPreferences prefs = getSharedPreferences("user_preferences", MODE_PRIVATE);
            
            String userId = prefs.getString("user_id", "");
            String name = prefs.getString("user_name", "");
            int age = prefs.getInt("user_age", 25);
            String gender = prefs.getString("user_gender", "Male");
            double weight = Double.parseDouble(prefs.getString("user_weight", "70"));
            double height = Double.parseDouble(prefs.getString("user_height", "170"));
            String activityLevel = prefs.getString("activity_level", "Moderately Active");
            String healthGoals = prefs.getString("health_goals", "Maintain weight");
            String dietaryPreferences = prefs.getString("dietary_preferences", "None");
            String allergies = prefs.getString("allergies", "None");
            String medicalConditions = prefs.getString("medical_conditions", "None");
            boolean isPregnant = prefs.getBoolean("is_pregnant", false);
            int pregnancyWeek = prefs.getInt("pregnancy_week", 0);
            String occupation = prefs.getString("occupation", "Office worker");
            String lifestyle = prefs.getString("lifestyle", "Moderate");
            
            return new UserProfile(userId, name, age, gender, weight, height, activityLevel,
                    healthGoals, dietaryPreferences, allergies, medicalConditions, isPregnant,
                    pregnancyWeek, occupation, lifestyle);
                    
        } catch (Exception e) {
            Log.e(TAG, "Error loading user profile from SharedPreferences: " + e.getMessage());
            return null;
        }
    }
    
    private UserProfile createDefaultUserProfile() {
        return new UserProfile("default", "User", 25, "Male", 70.0, 170.0, 
                "Moderately Active", "Maintain weight", "None", "None", "None", 
                false, 0, "Office worker", "Moderate");
    }
    
    private void loadAlternatives() {
        showLoadingState();
        
        executorService.execute(() -> {
            try {
                Log.d(TAG, "Loading alternatives for: " + originalFoodItem.getName());
                
                // Check cache first
                GeminiCacheManager cacheManager = new GeminiCacheManager(FoodAlternativesActivity.this);
                GeminiCacheManager.CachedAlternatives cached = cacheManager.getCachedAlternatives(
                    originalFoodItem.getName(), userProfile);
                
                if (cached != null) {
                    Log.d(TAG, "Using cached alternatives for: " + originalFoodItem.getName());
                    mainHandler.post(() -> {
                        alternativeFoods.clear();
                        alternativeFoods.addAll(cached.getAlternatives());
                        adapter.notifyDataSetChanged();
                        showContentState();
                        Log.d(TAG, "Loaded " + cached.getAlternatives().size() + " cached alternatives");
                    });
                    return;
                }
                
                // Create prompt for finding alternatives
                String prompt = createAlternativesPrompt();
                Log.d(TAG, "Generated prompt: " + prompt);
                
                // Call Gemini API
                geminiService.getFoodAlternatives(prompt, new GeminiService.AlternativesCallback() {
                    @Override
                    public void onSuccess(List<FoodItem> foods) {
                        mainHandler.post(() -> {
                            alternativeFoods.clear();
                            alternativeFoods.addAll(foods);
                            adapter.notifyDataSetChanged();
                            showContentState();
                            Log.d(TAG, "Loaded " + foods.size() + " alternatives");
                        });
                        
                        // Cache the alternatives for future use
                        cacheManager.cacheAlternatives(originalFoodItem.getName(), userProfile, foods);
                    }
                    
                    @Override
                    public void onError(String error) {
                        mainHandler.post(() -> {
                            Log.e(TAG, "Error loading alternatives: " + error);
                            showErrorState();
                        });
                    }
                });
                
            } catch (Exception e) {
                Log.e(TAG, "Error in loadAlternatives: " + e.getMessage());
                mainHandler.post(() -> showErrorState());
            }
        });
    }
    
    private String createAlternativesPrompt() {
        StringBuilder prompt = new StringBuilder();
        
        prompt.append("Find 5-8 healthy food alternatives for '").append(originalFoodItem.getName()).append("' ");
        prompt.append("that would be suitable for a ").append(userProfile.getAge()).append("-year-old ");
        prompt.append(userProfile.getGender().toLowerCase()).append(" ");
        prompt.append("with a BMI of ").append(String.format("%.1f", userProfile.getBmi())).append(" ");
        prompt.append("(").append(userProfile.getBmiCategory()).append("). ");
        
        // Add health goals context
        if (userProfile.getHealthGoals() != null && !userProfile.getHealthGoals().equals("None")) {
            prompt.append("Health goals: ").append(userProfile.getHealthGoals()).append(". ");
        }
        
        // Add dietary preferences
        if (userProfile.getDietaryPreferences() != null && !userProfile.getDietaryPreferences().equals("None")) {
            prompt.append("Dietary preferences: ").append(userProfile.getDietaryPreferences()).append(". ");
        }
        
        // Add allergies context
        if (userProfile.getAllergies() != null && !userProfile.getAllergies().equals("None")) {
            prompt.append("Allergies to avoid: ").append(userProfile.getAllergies()).append(". ");
        }
        
        prompt.append("Provide alternatives that are: ");
        prompt.append("1) Similar in taste/texture but healthier, ");
        prompt.append("2) Different ingredients but same nutritional benefits, ");
        prompt.append("3) Lower calorie options if the person needs weight management, ");
        prompt.append("4) Higher protein options if beneficial for their goals. ");
        prompt.append("For each alternative, provide: name, calories per 100g, serving size, and a brief reason why it's a good alternative. ");
        prompt.append("Format the response as a JSON array with objects containing: name, calories, servingSize, unit, reason, protein, carbs, fat, fiber.");
        
        return prompt.toString();
    }
    
    private void onCalorieChanged() {
        // This method is called when calories change due to adding/removing foods
        Log.d(TAG, "Calories changed in alternatives");
        // The adapter already handles adding/removing from AddedFoodManager and CalorieTracker
    }
    
    private void showLoadingState() {
        loadingLayout.setVisibility(View.VISIBLE);
        contentLayout.setVisibility(View.GONE);
        errorLayout.setVisibility(View.GONE);
    }
    
    private void showContentState() {
        loadingLayout.setVisibility(View.GONE);
        contentLayout.setVisibility(View.VISIBLE);
        errorLayout.setVisibility(View.GONE);
    }
    
    private void showErrorState() {
        loadingLayout.setVisibility(View.GONE);
        contentLayout.setVisibility(View.GONE);
        errorLayout.setVisibility(View.VISIBLE);
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (executorService != null) {
            executorService.shutdown();
        }
    }
}
