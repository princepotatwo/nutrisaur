package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.HashMap;

/**
 * Simple cache system for food recommendations to avoid repeated API calls
 */
public class FoodCache {
    private static final String TAG = "FoodCache";
    private static final String CACHE_PREFS = "food_cache_prefs";
    private static final String CACHE_KEY = "cached_foods";
    private static final String CACHE_TIMESTAMP_KEY = "cache_timestamp";
    private static final long CACHE_DURATION = 30 * 60 * 1000; // 30 minutes
    
    private Context context;
    private SharedPreferences prefs;
    
    public FoodCache(Context context) {
        this.context = context;
        this.prefs = context.getSharedPreferences(CACHE_PREFS, Context.MODE_PRIVATE);
    }
    
    /**
     * Check if cache is valid and not expired
     */
    public boolean isCacheValid() {
        long timestamp = prefs.getLong(CACHE_TIMESTAMP_KEY, 0);
        long currentTime = System.currentTimeMillis();
        boolean isValid = (currentTime - timestamp) < CACHE_DURATION;
        
        Log.d(TAG, "Cache valid: " + isValid + " (age: " + (currentTime - timestamp) / 1000 + "s)");
        return isValid;
    }
    
    /**
     * Get cached food recommendations
     */
    public Map<String, List<FoodRecommendation>> getCachedFoods() {
        if (!isCacheValid()) {
            Log.d(TAG, "Cache expired or not found");
            return null;
        }
        
        try {
            String cachedData = prefs.getString(CACHE_KEY, null);
            if (cachedData == null) {
                return null;
            }
            
            JSONObject jsonData = new JSONObject(cachedData);
            Map<String, List<FoodRecommendation>> result = new HashMap<>();
            
            // Parse each category
            String[] categories = {"traditional", "healthy", "international", "budget"};
            for (String category : categories) {
                if (jsonData.has(category)) {
                    JSONArray categoryArray = jsonData.getJSONArray(category);
                    List<FoodRecommendation> foods = new ArrayList<>();
                    
                    for (int i = 0; i < categoryArray.length(); i++) {
                        JSONObject foodJson = categoryArray.getJSONObject(i);
                        FoodRecommendation food = new FoodRecommendation(
                            foodJson.getString("food_name"),
                            foodJson.getInt("calories"),
                            foodJson.getDouble("protein_g"),
                            foodJson.getDouble("fat_g"),
                            foodJson.getDouble("carbs_g"),
                            foodJson.getString("serving_size"),
                            foodJson.getString("diet_type"),
                            foodJson.getString("description")
                        );
                        foods.add(food);
                    }
                    
                    result.put(category, foods);
                }
            }
            
            Log.d(TAG, "Loaded cached foods: " + result.size() + " categories");
            return result;
            
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing cached data: " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Cache food recommendations
     */
    public void cacheFoods(Map<String, List<FoodRecommendation>> foods) {
        try {
            JSONObject jsonData = new JSONObject();
            
            for (Map.Entry<String, List<FoodRecommendation>> entry : foods.entrySet()) {
                JSONArray categoryArray = new JSONArray();
                
                for (FoodRecommendation food : entry.getValue()) {
                    JSONObject foodJson = new JSONObject();
                    foodJson.put("food_name", food.getFoodName());
                    foodJson.put("calories", food.getCalories());
                    foodJson.put("protein_g", food.getProtein());
                    foodJson.put("fat_g", food.getFat());
                    foodJson.put("carbs_g", food.getCarbs());
                    foodJson.put("serving_size", food.getServingSize());
                    foodJson.put("diet_type", food.getDietType());
                    foodJson.put("description", food.getDescription());
                    categoryArray.put(foodJson);
                }
                
                jsonData.put(entry.getKey(), categoryArray);
            }
            
            prefs.edit()
                .putString(CACHE_KEY, jsonData.toString())
                .putLong(CACHE_TIMESTAMP_KEY, System.currentTimeMillis())
                .apply();
                
            Log.d(TAG, "Cached foods successfully");
            
        } catch (JSONException e) {
            Log.e(TAG, "Error caching data: " + e.getMessage());
        }
    }
    
    /**
     * Clear cache
     */
    public void clearCache() {
        prefs.edit().clear().apply();
        Log.d(TAG, "Cache cleared");
    }
    
    /**
     * Get cache age in minutes
     */
    public long getCacheAgeMinutes() {
        long timestamp = prefs.getLong(CACHE_TIMESTAMP_KEY, 0);
        return (System.currentTimeMillis() - timestamp) / (60 * 1000);
    }
}
