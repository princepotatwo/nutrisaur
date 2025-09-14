package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import java.lang.reflect.Type;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

public class CalorieTracker {
    private static final String TAG = "CalorieTracker";
    private static final String PREFS_NAME = "calorie_tracker_prefs";
    private static final String KEY_DAILY_CALORIES = "daily_calories";
    private static final String KEY_MEAL_CALORIES = "meal_calories";
    private static final String KEY_LAST_RESET_DATE = "last_reset_date";

    private SharedPreferences sharedPreferences;
    private Gson gson;
    private String userEmail;

    public CalorieTracker(Context context) {
        // Get current user email for user-specific storage
        SharedPreferences userPrefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        userEmail = userPrefs.getString("current_user_email", "default_user");
        
        // Create user-specific preferences file
        String prefsFileName = PREFS_NAME + "_" + userEmail.replace("@", "_").replace(".", "_");
        sharedPreferences = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
        gson = new Gson();
        
        // Note: Daily reset is handled by DailyResetManager in activities
        // to prevent circular dependency
    }

    public static class MealCalories {
        private String mealCategory;
        private int totalCalories;
        private int eatenCalories;
        private List<FoodItem> eatenFoods;

        public MealCalories() {
            this.eatenFoods = new ArrayList<>();
        }

        public MealCalories(String mealCategory, int totalCalories) {
            this.mealCategory = mealCategory;
            this.totalCalories = totalCalories;
            this.eatenCalories = 0;
            this.eatenFoods = new ArrayList<>();
        }

        // Getters and setters
        public String getMealCategory() { return mealCategory; }
        public void setMealCategory(String mealCategory) { this.mealCategory = mealCategory; }
        
        public int getTotalCalories() { return totalCalories; }
        public void setTotalCalories(int totalCalories) { this.totalCalories = totalCalories; }
        
        public int getEatenCalories() { return eatenCalories; }
        public void setEatenCalories(int eatenCalories) { this.eatenCalories = eatenCalories; }
        
        public List<FoodItem> getEatenFoods() { return eatenFoods; }
        public void setEatenFoods(List<FoodItem> eatenFoods) { this.eatenFoods = eatenFoods; }

        public void addFood(FoodItem food) {
            eatenFoods.add(food);
            eatenCalories += food.getCalories();
        }

        public void removeFood(FoodItem food) {
            if (eatenFoods.removeIf(item -> item.getName().equals(food.getName()))) {
                eatenCalories -= food.getCalories();
                if (eatenCalories < 0) eatenCalories = 0;
            }
        }

        public String getCalorieText() {
            return eatenCalories + "/" + totalCalories + " kcal";
        }

        public int getCaloriesLeft() {
            return totalCalories - eatenCalories;
        }
    }

    public void addFoodToMeal(String mealCategory, FoodItem food, int maxCalories) {
        try {
            Map<String, MealCalories> mealCalories = getMealCalories();
            MealCalories meal = mealCalories.get(mealCategory);
            
            if (meal == null) {
                meal = new MealCalories(mealCategory, maxCalories);
                mealCalories.put(mealCategory, meal);
            }
            
            meal.addFood(food);
            saveMealCalories(mealCalories);
            
            Log.d(TAG, "Added " + food.getName() + " to " + mealCategory + ". Calories: " + meal.getCalorieText());
        } catch (Exception e) {
            Log.e(TAG, "Error adding food to meal: " + e.getMessage());
        }
    }

    public void removeFoodFromMeal(String mealCategory, FoodItem food) {
        try {
            Map<String, MealCalories> mealCalories = getMealCalories();
            MealCalories meal = mealCalories.get(mealCategory);
            
            if (meal != null) {
                int caloriesBefore = meal.getEatenCalories();
                meal.removeFood(food);
                int caloriesAfter = meal.getEatenCalories();
                saveMealCalories(mealCalories);
                
                Log.d(TAG, "Removed " + food.getName() + " from " + mealCategory + 
                      ". Calories before: " + caloriesBefore + ", after: " + caloriesAfter);
            } else {
                Log.w(TAG, "Meal category not found: " + mealCategory);
            }
        } catch (Exception e) {
            Log.e(TAG, "Error removing food from meal: " + e.getMessage());
        }
    }

    public MealCalories getMealCalories(String mealCategory) {
        try {
            Map<String, MealCalories> mealCalories = getMealCalories();
            return mealCalories.get(mealCategory);
        } catch (Exception e) {
            Log.e(TAG, "Error getting meal calories: " + e.getMessage());
            return null;
        }
    }

    public int getTotalEatenCalories() {
        try {
            Map<String, MealCalories> mealCalories = getMealCalories();
            int total = 0;
            for (MealCalories meal : mealCalories.values()) {
                total += meal.getEatenCalories();
            }
            return total;
        } catch (Exception e) {
            Log.e(TAG, "Error getting total eaten calories: " + e.getMessage());
            return 0;
        }
    }

    public void setMealMaxCalories(String mealCategory, int maxCalories) {
        try {
            Map<String, MealCalories> mealCalories = getMealCalories();
            MealCalories meal = mealCalories.get(mealCategory);
            
            if (meal == null) {
                meal = new MealCalories(mealCategory, maxCalories);
                mealCalories.put(mealCategory, meal);
            } else {
                meal.setTotalCalories(maxCalories);
            }
            
            saveMealCalories(mealCalories);
            Log.d(TAG, "Set max calories for " + mealCategory + " to " + maxCalories);
        } catch (Exception e) {
            Log.e(TAG, "Error setting meal max calories: " + e.getMessage());
        }
    }

    public void clearDay() {
        try {
            sharedPreferences.edit().clear().apply();
            Log.d(TAG, "Cleared all calorie data for the day");
        } catch (Exception e) {
            Log.e(TAG, "Error clearing day: " + e.getMessage());
        }
    }
    
    /**
     * Clear all calorie data for the current user
     */
    public void clearUserData() {
        try {
            sharedPreferences.edit().clear().apply();
            Log.d(TAG, "Cleared all calorie data for user: " + userEmail);
        } catch (Exception e) {
            Log.e(TAG, "Error clearing user data: " + e.getMessage());
        }
    }
    
    /**
     * Clear all calorie data for a specific user (used when logging out)
     */
    public static void clearUserData(Context context, String userEmail) {
        try {
            String prefsFileName = PREFS_NAME + "_" + userEmail.replace("@", "_").replace(".", "_");
            SharedPreferences prefs = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
            prefs.edit().clear().apply();
            Log.d(TAG, "Cleared all calorie data for user: " + userEmail);
        } catch (Exception e) {
            Log.e(TAG, "Error clearing user data: " + e.getMessage());
        }
    }

    private Map<String, MealCalories> getMealCalories() {
        try {
            String json = sharedPreferences.getString(KEY_MEAL_CALORIES, null);
            if (json == null) {
                return new HashMap<>();
            }
            Type type = new TypeToken<Map<String, MealCalories>>() {}.getType();
            return gson.fromJson(json, type);
        } catch (Exception e) {
            Log.e(TAG, "Error getting meal calories: " + e.getMessage());
            return new HashMap<>();
        }
    }

    private void saveMealCalories(Map<String, MealCalories> mealCalories) {
        try {
            String json = gson.toJson(mealCalories);
            sharedPreferences.edit().putString(KEY_MEAL_CALORIES, json).apply();
        } catch (Exception e) {
            Log.e(TAG, "Error saving meal calories: " + e.getMessage());
        }
    }
    
    /**
     * Check if daily reset is needed and reset if it's a new day
     */
    private void checkAndResetDaily() {
        try {
            String currentDate = getCurrentDateString();
            String lastResetDate = sharedPreferences.getString(KEY_LAST_RESET_DATE, "");
            
            if (!currentDate.equals(lastResetDate)) {
                Log.d(TAG, "New day detected. Resetting calorie data. Current: " + currentDate + ", Last: " + lastResetDate);
                resetDailyData();
                sharedPreferences.edit().putString(KEY_LAST_RESET_DATE, currentDate).apply();
            }
        } catch (Exception e) {
            Log.e(TAG, "Error checking daily reset: " + e.getMessage());
        }
    }
    
    /**
     * Get current date as string in YYYY-MM-DD format
     */
    private String getCurrentDateString() {
        SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd", Locale.getDefault());
        return dateFormat.format(new Date());
    }
    
    /**
     * Reset all daily calorie data
     */
    private void resetDailyData() {
        try {
            // Clear all meal calories
            sharedPreferences.edit().remove(KEY_MEAL_CALORIES).apply();
            
            // Clear any other daily data
            sharedPreferences.edit().remove(KEY_DAILY_CALORIES).apply();
            
            Log.d(TAG, "Daily calorie data reset completed for user: " + userEmail);
        } catch (Exception e) {
            Log.e(TAG, "Error resetting daily data: " + e.getMessage());
        }
    }
    
    /**
     * Manually reset daily data (for testing or manual reset)
     */
    public void manualResetDaily() {
        try {
            resetDailyData();
            String currentDate = getCurrentDateString();
            sharedPreferences.edit().putString(KEY_LAST_RESET_DATE, currentDate).apply();
            Log.d(TAG, "Manual daily reset completed for user: " + userEmail);
        } catch (Exception e) {
            Log.e(TAG, "Error in manual daily reset: " + e.getMessage());
        }
    }
    
    /**
     * Get the last reset date
     */
    public String getLastResetDate() {
        return sharedPreferences.getString(KEY_LAST_RESET_DATE, "Never");
    }
    
    /**
     * Sync CalorieTracker with AddedFoodManager data
     * This method rebuilds the calorie tracking data from the added foods
     */
    public void syncWithAddedFoods(Context context) {
        try {
            AddedFoodManager addedFoodManager = new AddedFoodManager(context);
            List<FoodItem> allAddedFoods = addedFoodManager.getAddedFoods();
            
            Log.d(TAG, "Syncing with " + allAddedFoods.size() + " added foods");
            
            // Clear existing meal calories completely
            Map<String, MealCalories> mealCalories = new HashMap<>();
            
            // Group added foods by meal category
            for (FoodItem food : allAddedFoods) {
                if (food.getMealCategory() != null && !food.getMealCategory().isEmpty()) {
                    String mealCategory = food.getMealCategory();
                    MealCalories meal = mealCalories.get(mealCategory);
                    
                    if (meal == null) {
                        // Create new meal with default max calories (will be updated later)
                        meal = new MealCalories(mealCategory, 500);
                        mealCalories.put(mealCategory, meal);
                    }
                    
                    // Add food to meal (this will update eatenCalories)
                    meal.addFood(food);
                    Log.d(TAG, "Added " + food.getName() + " (" + food.getCalories() + " cal) to " + mealCategory);
                }
            }
            
            // Save the synced data
            saveMealCalories(mealCalories);
            
            Log.d(TAG, "Synced CalorieTracker with AddedFoodManager. Total meals: " + mealCalories.size());
            for (MealCalories meal : mealCalories.values()) {
                Log.d(TAG, "Meal " + meal.getMealCategory() + ": " + meal.getCalorieText());
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Error syncing with added foods: " + e.getMessage());
        }
    }
    
    /**
     * Clear all calorie tracking data
     */
    public void clearAllCalorieData() {
        try {
            Map<String, MealCalories> emptyMealCalories = new HashMap<>();
            saveMealCalories(emptyMealCalories);
            Log.d(TAG, "Cleared all calorie tracking data");
        } catch (Exception e) {
            Log.e(TAG, "Error clearing calorie data: " + e.getMessage());
        }
    }
    
}
