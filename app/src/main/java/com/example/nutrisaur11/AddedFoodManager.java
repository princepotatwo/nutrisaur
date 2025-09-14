package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class AddedFoodManager {
    private static final String PREFS_NAME = "added_food_prefs";
    private static final String KEY_ADDED_FOODS = "added_food_items";

    private SharedPreferences sharedPreferences;
    private Gson gson;
    private String userEmail;

    public AddedFoodManager(Context context) {
        // Get current user email for user-specific storage
        SharedPreferences userPrefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        userEmail = userPrefs.getString("current_user_email", "default_user");
        
        // Create user-specific preferences file
        String prefsFileName = PREFS_NAME + "_" + userEmail.replace("@", "_").replace(".", "_");
        sharedPreferences = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
        gson = new Gson();
    }

    public void addToAddedFoods(FoodItem foodItem) {
        List<FoodItem> addedFoods = getAddedFoods();
        if (!isAdded(foodItem)) {
            addedFoods.add(foodItem);
            saveAddedFoods(addedFoods);
        }
    }

    public void removeFromAddedFoods(FoodItem foodItem) {
        List<FoodItem> addedFoods = getAddedFoods();
        
        // Log the food item we're trying to remove
        android.util.Log.d("AddedFoodManager", "Attempting to remove food: " + foodItem.getName() + 
                          " (calories: " + foodItem.getCalories() + 
                          ", meal: " + foodItem.getMealCategory() + ")");
        
        // Log all current added foods for debugging
        android.util.Log.d("AddedFoodManager", "Current added foods count: " + addedFoods.size());
        for (int i = 0; i < addedFoods.size(); i++) {
            FoodItem item = addedFoods.get(i);
            android.util.Log.d("AddedFoodManager", "  [" + i + "] " + item.getName() + 
                              " (calories: " + item.getCalories() + 
                              ", meal: " + item.getMealCategory() + ")");
        }
        
        // Try multiple removal strategies for better success rate
        boolean removed = false;
        
        // Strategy 1: Exact match (original logic)
        if (!removed) {
            removed = addedFoods.removeIf(item -> 
                item.getName().equals(foodItem.getName()) && 
                item.getMealCategory() != null && 
                foodItem.getMealCategory() != null &&
                item.getMealCategory().equals(foodItem.getMealCategory()) &&
                Math.abs(item.getCalories() - foodItem.getCalories()) < 1
            );
            if (removed) {
                android.util.Log.d("AddedFoodManager", "Removed using exact match strategy");
            }
        }
        
        // Strategy 2: Name and meal category match (ignore calories)
        if (!removed) {
            removed = addedFoods.removeIf(item -> 
                item.getName().equals(foodItem.getName()) && 
                item.getMealCategory() != null && 
                foodItem.getMealCategory() != null &&
                item.getMealCategory().equals(foodItem.getMealCategory())
            );
            if (removed) {
                android.util.Log.d("AddedFoodManager", "Removed using name+meal strategy");
            }
        }
        
        // Strategy 3: Name match only (most permissive)
        if (!removed) {
            removed = addedFoods.removeIf(item -> 
                item.getName().equals(foodItem.getName())
            );
            if (removed) {
                android.util.Log.d("AddedFoodManager", "Removed using name-only strategy");
            }
        }
        
        if (removed) {
            saveAddedFoods(addedFoods);
            android.util.Log.d("AddedFoodManager", "Successfully removed food: " + foodItem.getName());
        } else {
            android.util.Log.w("AddedFoodManager", "Failed to remove food: " + foodItem.getName() + 
                              " - no matching item found with any strategy");
        }
    }

    public boolean isAdded(FoodItem foodItem) {
        List<FoodItem> addedFoods = getAddedFoods();
        return addedFoods.stream().anyMatch(item -> item.getName().equals(foodItem.getName()));
    }

    public List<FoodItem> getAddedFoods() {
        String json = sharedPreferences.getString(KEY_ADDED_FOODS, null);
        if (json == null) {
            return new ArrayList<>();
        }
        Type type = new TypeToken<List<FoodItem>>() {}.getType();
        return gson.fromJson(json, type);
    }

    private void saveAddedFoods(List<FoodItem> addedFoods) {
        String json = gson.toJson(addedFoods);
        sharedPreferences.edit().putString(KEY_ADDED_FOODS, json).apply();
    }
    
    /**
     * Clear all added foods for the current user
     */
    public void clearUserData() {
        sharedPreferences.edit().clear().apply();
    }
    
    /**
     * Clear all added foods for a specific user (used when logging out)
     */
    public static void clearUserData(Context context, String userEmail) {
        String prefsFileName = PREFS_NAME + "_" + userEmail.replace("@", "_").replace(".", "_");
        SharedPreferences prefs = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
        prefs.edit().clear().apply();
    }
    
    /**
     * Clear all added foods for the current user (reset to initial state)
     */
    public void clearAllAddedFoods() {
        List<FoodItem> emptyList = new ArrayList<>();
        saveAddedFoods(emptyList);
        android.util.Log.d("AddedFoodManager", "Cleared all added foods for user: " + userEmail);
    }
    
    /**
     * Get count of added foods
     */
    public int getAddedFoodsCount() {
        return getAddedFoods().size();
    }
    
    /**
     * Check if there are any added foods
     */
    public boolean hasAddedFoods() {
        return !getAddedFoods().isEmpty();
    }
}
