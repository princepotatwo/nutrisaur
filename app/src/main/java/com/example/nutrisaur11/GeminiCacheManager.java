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

public class GeminiCacheManager {
    private static final String TAG = "GeminiCacheManager";
    private static final String PREFS_NAME = "gemini_cache_prefs";
    private static final String KEY_CACHE = "gemini_recommendations_cache";
    private static final long CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

    private SharedPreferences sharedPreferences;
    private Gson gson;

    public GeminiCacheManager(Context context) {
        sharedPreferences = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
        gson = new Gson();
    }

    public static class CachedRecommendation {
        private long timestamp;
        private List<FoodItem> foods;
        private String summary;
        private String mealCategory;
        private String userProfileKey;

        public CachedRecommendation() {}

        public CachedRecommendation(List<FoodItem> foods, String summary, String mealCategory, String userProfileKey) {
            this.timestamp = System.currentTimeMillis();
            this.foods = foods;
            this.summary = summary;
            this.mealCategory = mealCategory;
            this.userProfileKey = userProfileKey;
        }

        public boolean isExpired() {
            return System.currentTimeMillis() - timestamp > CACHE_DURATION;
        }

        // Getters and setters
        public long getTimestamp() { return timestamp; }
        public void setTimestamp(long timestamp) { this.timestamp = timestamp; }
        
        public List<FoodItem> getFoods() { return foods; }
        public void setFoods(List<FoodItem> foods) { this.foods = foods; }
        
        public String getSummary() { return summary; }
        public void setSummary(String summary) { this.summary = summary; }
        
        public String getMealCategory() { return mealCategory; }
        public void setMealCategory(String mealCategory) { this.mealCategory = mealCategory; }
        
        public String getUserProfileKey() { return userProfileKey; }
        public void setUserProfileKey(String userProfileKey) { this.userProfileKey = userProfileKey; }
    }

    public void cacheRecommendations(String mealCategory, UserProfile userProfile, List<FoodItem> foods, String summary) {
        try {
            String userProfileKey = generateUserProfileKey(userProfile);
            String cacheKey = mealCategory + "_" + userProfileKey;
            
            CachedRecommendation cached = new CachedRecommendation(foods, summary, mealCategory, userProfileKey);
            
            Map<String, CachedRecommendation> cache = getCache();
            cache.put(cacheKey, cached);
            saveCache(cache);
            
            Log.d(TAG, "Cached recommendations for " + mealCategory + " (user: " + userProfileKey + ")");
        } catch (Exception e) {
            Log.e(TAG, "Error caching recommendations: " + e.getMessage());
        }
    }

    public CachedRecommendation getCachedRecommendations(String mealCategory, UserProfile userProfile) {
        try {
            String userProfileKey = generateUserProfileKey(userProfile);
            String cacheKey = mealCategory + "_" + userProfileKey;
            
            Map<String, CachedRecommendation> cache = getCache();
            CachedRecommendation cached = cache.get(cacheKey);
            
            if (cached != null && !cached.isExpired()) {
                Log.d(TAG, "Found valid cached recommendations for " + mealCategory);
                return cached;
            } else if (cached != null) {
                Log.d(TAG, "Cached recommendations expired for " + mealCategory);
                // Remove expired cache
                cache.remove(cacheKey);
                saveCache(cache);
            }
            
            return null;
        } catch (Exception e) {
            Log.e(TAG, "Error getting cached recommendations: " + e.getMessage());
            return null;
        }
    }

    public void clearCache() {
        try {
            sharedPreferences.edit().remove(KEY_CACHE).apply();
            Log.d(TAG, "Cache cleared");
        } catch (Exception e) {
            Log.e(TAG, "Error clearing cache: " + e.getMessage());
        }
    }

    private String generateUserProfileKey(UserProfile userProfile) {
        // Create a unique key based on user profile characteristics that affect recommendations
        return userProfile.getBmi() + "_" + userProfile.getBmiCategory() + "_" + 
               userProfile.getAge() + "_" + userProfile.getGender();
    }

    private Map<String, CachedRecommendation> getCache() {
        try {
            String json = sharedPreferences.getString(KEY_CACHE, null);
            if (json == null) {
                return new HashMap<>();
            }
            Type type = new TypeToken<Map<String, CachedRecommendation>>() {}.getType();
            return gson.fromJson(json, type);
        } catch (Exception e) {
            Log.e(TAG, "Error getting cache: " + e.getMessage());
            return new HashMap<>();
        }
    }

    private void saveCache(Map<String, CachedRecommendation> cache) {
        try {
            String json = gson.toJson(cache);
            sharedPreferences.edit().putString(KEY_CACHE, json).apply();
        } catch (Exception e) {
            Log.e(TAG, "Error saving cache: " + e.getMessage());
        }
    }
}
