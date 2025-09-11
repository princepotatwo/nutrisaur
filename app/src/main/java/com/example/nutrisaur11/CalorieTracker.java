package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class CalorieTracker {
    private static final String TAG = "CalorieTracker";
    private static final String PREFS_NAME = "calorie_tracker_prefs";
    private static final String KEY_DAILY_CALORIES = "daily_calories";
    private static final String KEY_MEAL_CALORIES = "meal_calories";

    private SharedPreferences sharedPreferences;
    private Gson gson;

    public CalorieTracker(Context context) {
        sharedPreferences = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
        gson = new Gson();
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
                meal.removeFood(food);
                saveMealCalories(mealCalories);
                
                Log.d(TAG, "Removed " + food.getName() + " from " + mealCategory + ". Calories: " + meal.getCalorieText());
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
}
