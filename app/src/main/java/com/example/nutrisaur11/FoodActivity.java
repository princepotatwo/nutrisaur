package com.example.nutrisaur11;

import android.os.Bundle;
import android.view.View;
import android.content.Intent;
import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import android.widget.LinearLayout;
import android.util.Log;
import android.widget.TextView;
import android.widget.ProgressBar;
import android.widget.FrameLayout;
import android.os.Handler;
import android.os.Looper;

public class FoodActivity extends BaseActivity {
    private static final String TAG = "FoodActivity";
    private static final int REQUEST_CODE_FOOD_LOGGING = 1001;
    
    // Meal category cards
    private LinearLayout breakfastCard, lunchCard, dinnerCard, snacksCard;
    
    // Navigation views
    private View navHome, navFood, navFavorites, navAccount;

    // Nutrition UI elements
    private CuteCircularProgressView caloriesLeftLoading;
    private TextView carbsText, proteinText, fatText;
    private ProgressBar carbsProgress, proteinProgress, fatProgress;
    private FrameLayout centerCircle;
    
    // Meal progress text views
    private TextView breakfastProgressText, lunchProgressText, dinnerProgressText, snacksProgressText;
    
    // Date display
    private TextView dateText;

    // Services
    private NutritionService nutritionService;
    private CommunityUserManager userManager;
    private Handler mainHandler;
    
    // Store user data for passing to FoodLoggingActivity
    private java.util.Map<String, String> currentUserData;
    private int previousKcalGoal = -1; // Track previous kcal goal to detect changes

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_food);
        
        Log.d(TAG, "FoodActivity onCreate started");
        
        // Check for daily reset before initializing
        DailyResetManager resetManager = new DailyResetManager(this);
        resetManager.checkAndResetDaily();
        
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
        
        // Call this after session validation
        onSessionValidated();
    }
    
    @Override
    protected void initializeActivity() {
        // Additional initialization after session validation
        // This method is called automatically by BaseActivity
    }
    
    private void setupHeader() {
        // Set header title and subtitle
        android.widget.TextView pageTitle = findViewById(R.id.page_title);
        android.widget.TextView pageSubtitle = findViewById(R.id.page_subtitle);
        if (pageTitle != null) {
            pageTitle.setText("AI FOOD\nRECOMMENDATIONS");
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
        
        // Initialize macronutrient text views
        carbsText = findViewById(R.id.carbs_text);
        proteinText = findViewById(R.id.protein_text);
        fatText = findViewById(R.id.fat_text);
        
        // Initialize macronutrient progress bars
        carbsProgress = findViewById(R.id.carbs_progress);
        proteinProgress = findViewById(R.id.protein_progress);
        fatProgress = findViewById(R.id.fat_progress);
        
        // Initialize meal progress text views
        breakfastProgressText = findViewById(R.id.breakfast_progress_text);
        lunchProgressText = findViewById(R.id.lunch_progress_text);
        dinnerProgressText = findViewById(R.id.dinner_progress_text);
        snacksProgressText = findViewById(R.id.snacks_progress_text);
        
        // Initialize date text view
        dateText = findViewById(R.id.date_text);
        if (dateText != null) {
            setCurrentDate();
        }
        
        Log.d(TAG, "Nutrition views initialized");
    }
    
    private void setCurrentDate() {
        try {
            java.text.SimpleDateFormat dateFormat = new java.text.SimpleDateFormat("MMM dd", java.util.Locale.getDefault());
            String currentDate = "Today, " + dateFormat.format(new java.util.Date());
            dateText.setText(currentDate);
            Log.d(TAG, "Date set to: " + currentDate);
        } catch (Exception e) {
            Log.e(TAG, "Error setting current date: " + e.getMessage());
            dateText.setText("Today");
        }
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
        
        // Pass user data if available
        if (currentUserData != null && !currentUserData.isEmpty()) {
            // Convert Map to Bundle for passing to FoodLoggingActivity
            android.os.Bundle userDataBundle = new android.os.Bundle();
            for (java.util.Map.Entry<String, String> entry : currentUserData.entrySet()) {
                userDataBundle.putString(entry.getKey(), entry.getValue());
            }
            intent.putExtra("user_data", userDataBundle);
            Log.d(TAG, "Passing user data to FoodLoggingActivity: " + currentUserData.get("name") + " (BMI: " + currentUserData.get("bmi") + ")");
        } else {
            Log.w(TAG, "No user data available to pass to FoodLoggingActivity");
        }
        
        startActivityForResult(intent, REQUEST_CODE_FOOD_LOGGING);
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
    }
    
    /**
     * Enable progress colors when user starts interacting
     */
    private void enableProgressColors() {
        if (caloriesLeftLoading != null) {
            // Get current calories left and calculate calories eaten for proper progress
            String currentText = caloriesLeftLoading.getCenterText();
            try {
                int caloriesLeft = Integer.parseInt(currentText);
                int totalDailyCalories = (int) caloriesLeftLoading.getMaxProgress();
                int caloriesEaten = totalDailyCalories - caloriesLeft;
                
                // Only update if the values are valid and different from current
                if (caloriesEaten >= 0 && caloriesEaten <= totalDailyCalories) {
                    // Use a small delay to prevent animation conflicts
                    caloriesLeftLoading.postDelayed(() -> {
                        caloriesLeftLoading.showValueWithProgress(currentText, caloriesEaten);
                    }, 100);
                } else {
                    // Fallback to regular showValue if calculation is invalid
                    caloriesLeftLoading.showValue(currentText);
                }
            } catch (NumberFormatException e) {
                // Fallback to regular showValue if parsing fails
                caloriesLeftLoading.showValue(currentText);
            }
        }
        
        // Update progress bars with actual values
        updateMacronutrientProgressBars();
    }
    
    /**
     * Update macronutrient progress bars with actual progress
     */
    private void updateMacronutrientProgressBars() {
        if (proteinProgress != null) {
            String proteinText = this.proteinText.getText().toString();
            String[] parts = proteinText.split("/");
            if (parts.length == 2) {
                try {
                    int current = Integer.parseInt(parts[0].trim());
                    int target = Integer.parseInt(parts[1].trim().split(" ")[0]);
                    if (target > 0) {
                        int progressValue = (int) ((current / (double) target) * 100);
                        proteinProgress.setProgress(Math.min(progressValue, 100));
                    }
                } catch (NumberFormatException e) {
                    proteinProgress.setProgress(0);
                }
            }
        }
        
        if (carbsProgress != null) {
            String carbsText = this.carbsText.getText().toString();
            String[] parts = carbsText.split("/");
            if (parts.length == 2) {
                try {
                    int current = Integer.parseInt(parts[0].trim());
                    int target = Integer.parseInt(parts[1].trim().split(" ")[0]);
                    if (target > 0) {
                        int progressValue = (int) ((current / (double) target) * 100);
                        carbsProgress.setProgress(Math.min(progressValue, 100));
                    }
                } catch (NumberFormatException e) {
                    carbsProgress.setProgress(0);
                }
            }
        }
        
        if (fatProgress != null) {
            String fatText = this.fatText.getText().toString();
            String[] parts = fatText.split("/");
            if (parts.length == 2) {
                try {
                    int current = Integer.parseInt(parts[0].trim());
                    int target = Integer.parseInt(parts[1].trim().split(" ")[0]);
                    if (target > 0) {
                        int progressValue = (int) ((current / (double) target) * 100);
                        fatProgress.setProgress(Math.min(progressValue, 100));
                    }
                } catch (NumberFormatException e) {
                    fatProgress.setProgress(0);
                }
            }
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
                    
                    // Store user data for passing to FoodLoggingActivity
                    currentUserData = userData;
                    
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
     * Update calorie data with animations - Now shows calories burned in the circle
     */
    private void updateCalorieData(NutritionData nutritionData) {
        // Get actual eaten calories from CalorieTracker
        CalorieTracker calorieTracker = new CalorieTracker(this);
        
        // Sync CalorieTracker with AddedFoodManager to ensure data consistency
        calorieTracker.syncWithAddedFoods(this);
        
        // Use the new method to get total eaten calories
        int totalEatenCalories = calorieTracker.getTotalEatenCalories();
        
        // Calculate calories left based on actual eaten calories
        int totalDailyCalories = nutritionData.getTotalCalories();
        int caloriesLeft = totalDailyCalories - totalEatenCalories;
        int caloriesBurned = nutritionData.getCaloriesBurned();
        
        // Check if this is a new kcal goal (user profile changed)
        checkForKcalGoalChange(totalDailyCalories);
        
        // Ensure calories left doesn't go below 0
        if (caloriesLeft < 0) {
            caloriesLeft = 0;
        }
        
        Log.d(TAG, "=== CALORIE UPDATE ===");
        Log.d(TAG, "Total Daily Calories: " + totalDailyCalories);
        Log.d(TAG, "Total Eaten Calories: " + totalEatenCalories);
        Log.d(TAG, "Calories Left: " + caloriesLeft);
        Log.d(TAG, "Calories Burned: " + caloriesBurned);
        
        // Update UI elements - Show calories left in the circle (decreases as you eat)
        if (caloriesLeftLoading != null) {
            caloriesLeftLoading.setCenterText(String.valueOf(caloriesLeft));
            // Set max progress based on total daily calories
            caloriesLeftLoading.setMaxProgress(totalDailyCalories);
            
            // Calculate progress based on calories eaten (not calories left)
            // Progress should increase as you eat more food
            int caloriesEaten = totalDailyCalories - caloriesLeft;
            caloriesLeftLoading.showValueWithProgress(String.valueOf(caloriesLeft), caloriesEaten);
        }
        
        // Store in SharedPreferences for future use
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        editor.putInt("calories_left", caloriesLeft);
        editor.putInt("calories_eaten", totalEatenCalories);
        editor.putInt("calories_burned", caloriesBurned);
        editor.apply();
        
        Log.d(TAG, "Calorie data updated and saved to SharedPreferences");
    }
    
    /**
     * Update meal progress text views
     */
    private void updateMealProgress(NutritionData nutritionData) {
        NutritionData.MealDistribution mealDistribution = nutritionData.getMealDistribution();
        if (mealDistribution == null) return;
        
        // Get actual eaten calories from CalorieTracker
        CalorieTracker calorieTracker = new CalorieTracker(this);
        
        // Update breakfast progress
        if (breakfastProgressText != null) {
            CalorieTracker.MealCalories breakfastCalories = calorieTracker.getMealCalories("Breakfast");
            int breakfastEaten = breakfastCalories != null ? breakfastCalories.getEatenCalories() : 0;
            String breakfastText = breakfastEaten + " / " + 
                                 mealDistribution.getBreakfastCalories() + " kcal";
            breakfastProgressText.setText(breakfastText);
        }
        
        // Update lunch progress
        if (lunchProgressText != null) {
            CalorieTracker.MealCalories lunchCalories = calorieTracker.getMealCalories("Lunch");
            int lunchEaten = lunchCalories != null ? lunchCalories.getEatenCalories() : 0;
            String lunchText = lunchEaten + " / " + 
                             mealDistribution.getLunchCalories() + " kcal";
            lunchProgressText.setText(lunchText);
        }
        
        // Update dinner progress
        if (dinnerProgressText != null) {
            CalorieTracker.MealCalories dinnerCalories = calorieTracker.getMealCalories("Dinner");
            int dinnerEaten = dinnerCalories != null ? dinnerCalories.getEatenCalories() : 0;
            String dinnerText = dinnerEaten + " / " + 
                              mealDistribution.getDinnerCalories() + " kcal";
            dinnerProgressText.setText(dinnerText);
        }
        
        // Update snacks progress
        if (snacksProgressText != null) {
            CalorieTracker.MealCalories snacksCalories = calorieTracker.getMealCalories("Snacks");
            int snacksEaten = snacksCalories != null ? snacksCalories.getEatenCalories() : 0;
            String snacksText = snacksEaten + " / " + 
                              mealDistribution.getSnacksCalories() + " kcal";
            snacksProgressText.setText(snacksText);
        }
        
        Log.d(TAG, "Meal progress updated with CalorieTracker data");
    }
    
    /**
     * Update macronutrient data with animations - Now shows remaining amounts like calories
     */
    private void updateMacronutrientData(NutritionData nutritionData) {
        NutritionData.Macronutrients macros = nutritionData.getMacronutrients();
        if (macros == null) return;
        
        // Get actual eaten macronutrients from CalorieTracker
        CalorieTracker calorieTracker = new CalorieTracker(this);
        double totalProteinEaten = 0;
        double totalCarbsEaten = 0;
        double totalFatEaten = 0;
        
        String[] mealCategories = {"Breakfast", "Lunch", "Dinner", "Snacks"};
        for (String category : mealCategories) {
            CalorieTracker.MealCalories mealCalories = calorieTracker.getMealCalories(category);
            if (mealCalories != null) {
                for (FoodItem food : mealCalories.getEatenFoods()) {
                    // Get macronutrients from food item if available, otherwise estimate
                    int calories = food.getCalories();
                    if (food.getProtein() > 0) {
                        totalProteinEaten += food.getProtein();
                    } else {
                        totalProteinEaten += calories * 0.25 / 4; // 25% protein, 4 cal/g
                    }
                    if (food.getCarbs() > 0) {
                        totalCarbsEaten += food.getCarbs();
                    } else {
                        totalCarbsEaten += calories * 0.50 / 4;   // 50% carbs, 4 cal/g
                    }
                    if (food.getFat() > 0) {
                        totalFatEaten += food.getFat();
                    } else {
                        totalFatEaten += calories * 0.25 / 9;     // 25% fat, 9 cal/g
                    }
                }
            }
        }
        
        // Calculate remaining macronutrients
        int proteinTarget = macros.getProteinTarget();
        int carbsTarget = macros.getCarbsTarget();
        int fatTarget = macros.getFatTarget();
        
        int proteinLeft = Math.max(0, proteinTarget - (int) totalProteinEaten);
        int carbsLeft = Math.max(0, carbsTarget - (int) totalCarbsEaten);
        int fatLeft = Math.max(0, fatTarget - (int) totalFatEaten);
        
        // Update text displays with consumed amounts (current/target)
        if (proteinText != null) {
            proteinText.setText(String.format("%d/%d g", (int) totalProteinEaten, proteinTarget));
        }
        if (carbsText != null) {
            carbsText.setText(String.format("%d/%d g", (int) totalCarbsEaten, carbsTarget));
        }
        if (fatText != null) {
            fatText.setText(String.format("%d/%d g", (int) totalFatEaten, fatTarget));
        }
        
        // Update progress bars based on consumed amounts (should increase as food is added)
        if (proteinProgress != null && proteinTarget > 0) {
            int proteinProgressValue = (int) ((totalProteinEaten / (double) proteinTarget) * 100);
            proteinProgress.setProgress(Math.min(proteinProgressValue, 100));
        }
        if (carbsProgress != null && carbsTarget > 0) {
            int carbsProgressValue = (int) ((totalCarbsEaten / (double) carbsTarget) * 100);
            carbsProgress.setProgress(Math.min(carbsProgressValue, 100));
        }
        if (fatProgress != null && fatTarget > 0) {
            int fatProgressValue = (int) ((totalFatEaten / (double) fatTarget) * 100);
            fatProgress.setProgress(Math.min(fatProgressValue, 100));
        }
        
        Log.d(TAG, "Macronutrients remaining - Protein: " + proteinLeft + "/" + proteinTarget + 
              ", Carbs: " + carbsLeft + "/" + carbsTarget + 
              ", Fat: " + fatLeft + "/" + fatTarget);
        
        // Store in SharedPreferences for future use
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        editor.putInt("carbs_current", (int) totalCarbsEaten);
        editor.putInt("carbs_target", carbsTarget);
        editor.putInt("protein_current", (int) totalProteinEaten);
        editor.putInt("protein_target", proteinTarget);
        editor.putInt("fat_current", (int) totalFatEaten);
        editor.putInt("fat_target", fatTarget);
        editor.apply();
    }
    
    /**
     * Update activity data with animations
     */
    private void updateActivityData(NutritionData nutritionData) {
        NutritionData.ActivityData activity = nutritionData.getActivity();
        if (activity == null) return;
        
        // Log activity data for debugging
        Log.d(TAG, "Walking Calories: " + activity.getWalkingCalories());
        Log.d(TAG, "Activity Calories: " + activity.getActivityCalories());
        
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
        
        // Show default values in UI - initially no color
        if (caloriesLeftLoading != null) {
            caloriesLeftLoading.setCenterText("2000"); // Show default remaining calories
            caloriesLeftLoading.setMaxProgress(2000); // Set max progress
            // Don't call showValue() initially to keep no color state
        }
        
        // Set default macronutrient values (showing consumed amounts - start at 0)
        if (proteinText != null) {
            proteinText.setText("0/29 g");
        }
        if (carbsText != null) {
            carbsText.setText("0/120 g");
        }
        if (fatText != null) {
            fatText.setText("0/42 g");
        }
        
        // Set default progress bars (start at 0 for initial state)
        if (proteinProgress != null) {
            proteinProgress.setProgress(0);
        }
        if (carbsProgress != null) {
            carbsProgress.setProgress(0);
        }
        if (fatProgress != null) {
            fatProgress.setProgress(0);
        }
        
        // Store default values in SharedPreferences
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        
        editor.putInt("calories_left", 2000);
        editor.putInt("calories_eaten", 0);
        editor.putInt("calories_burned", 0);
        editor.putInt("walking_calories", 0);
        editor.putInt("activity_calories", 0);
        editor.putInt("carbs_current", 0);
        editor.putInt("carbs_target", 120);
        editor.putInt("protein_current", 0);
        editor.putInt("protein_target", 29);
        editor.putInt("fat_current", 0);
        editor.putInt("fat_target", 42);
        
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
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        
        if (requestCode == REQUEST_CODE_FOOD_LOGGING) {
            if (resultCode == RESULT_OK) {
                Log.d(TAG, "Food logging completed, refreshing calorie data");
                // Refresh calorie data when returning from food logging
                refreshCalorieData();
            }
        }
    }
    
    public void refreshCalorieData() {
        Log.d(TAG, "Refreshing calorie data...");
        
        // Sync CalorieTracker with AddedFoodManager first
        CalorieTracker calorieTracker = new CalorieTracker(this);
        calorieTracker.syncWithAddedFoods(this);
        
        // Log current state for debugging
        int totalEatenCalories = calorieTracker.getTotalEatenCalories();
        Log.d(TAG, "Total eaten calories after sync: " + totalEatenCalories);
        
        // Reload nutrition data to reflect changes from food logging
        if (nutritionService != null) {
            nutritionService.getNutritionRecommendationsWithUserData(currentUserData, new NutritionService.NutritionCallback() {
                @Override
                public void onSuccess(NutritionData nutritionData) {
                    runOnUiThread(() -> {
                        updateCalorieData(nutritionData);
                        updateMacronutrientData(nutritionData);
                        updateMealProgress(nutritionData);
                        
                        // Enable progress colors only once after data update
                        enableProgressColors();
                        
                        Log.d(TAG, "Calorie data refreshed successfully");
                    });
                }
                
                @Override
                public void onError(String error) {
                    Log.e(TAG, "Error refreshing calorie data: " + error);
                }
            });
        }
    }
    
    /**
     * Reset all food data and calories to initial state
     */
    public void resetAllFoodData() {
        Log.d(TAG, "Resetting all food data...");
        
        // Clear all added foods
        AddedFoodManager addedFoodManager = new AddedFoodManager(this);
        addedFoodManager.clearAllAddedFoods();
        
        // Clear all calorie tracking data
        CalorieTracker calorieTracker = new CalorieTracker(this);
        calorieTracker.clearAllCalorieData();
        
        // Clear kcal suggestion cache to force recalculation
        if (nutritionService != null) {
            nutritionService.clearKcalSuggestionCache();
        }
        
        // Refresh the UI to show initial state
        refreshCalorieData();
        
        Log.d(TAG, "All food data reset to initial state");
    }
    
    /**
     * Clear kcal suggestion cache (call this when user profile is updated)
     */
    public void clearKcalSuggestionCache() {
        if (nutritionService != null) {
            nutritionService.clearKcalSuggestionCache();
            Log.d(TAG, "Cleared kcal suggestion cache - will recalculate on next visit");
        }
    }
    
    /**
     * Check if kcal goal has changed and notify user if needed
     */
    private void checkForKcalGoalChange(int newKcalGoal) {
        if (previousKcalGoal != -1 && previousKcalGoal != newKcalGoal) {
            // Kcal goal has changed - notify user
            int difference = newKcalGoal - previousKcalGoal;
            String message;
            
            if (difference > 0) {
                message = "Your daily calorie goal increased by " + difference + " calories due to profile changes.";
            } else {
                message = "Your daily calorie goal decreased by " + Math.abs(difference) + " calories due to profile changes.";
            }
            
            // Show toast notification
            android.widget.Toast.makeText(this, message, android.widget.Toast.LENGTH_LONG).show();
            
            Log.d(TAG, "Kcal goal changed: " + previousKcalGoal + " → " + newKcalGoal + " (difference: " + difference + ")");
        }
        
        // Update previous goal for next comparison
        previousKcalGoal = newKcalGoal;
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (nutritionService != null) {
            nutritionService.cleanup();
        }
    }

} 