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
    private static final String KEY_ALTERNATIVES_CACHE = "gemini_alternatives_cache";
    private static final long CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

    private SharedPreferences sharedPreferences;
    private Gson gson;
    private String userEmail;

    public GeminiCacheManager(Context context) {
        // Get current user email for user-specific storage
        SharedPreferences userPrefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        userEmail = userPrefs.getString("current_user_email", "default_user");
        
        // Create user-specific preferences file
        String prefsFileName = PREFS_NAME + "_" + userEmail.replace("@", "_").replace(".", "_");
        sharedPreferences = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
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

    public static class CachedAlternatives {
        private long timestamp;
        private List<FoodItem> alternatives;
        private String originalFoodName;
        private String userProfileKey;

        public CachedAlternatives() {}

        public CachedAlternatives(List<FoodItem> alternatives, String originalFoodName, String userProfileKey) {
            this.timestamp = System.currentTimeMillis();
            this.alternatives = alternatives;
            this.originalFoodName = originalFoodName;
            this.userProfileKey = userProfileKey;
        }

        public boolean isExpired() {
            return System.currentTimeMillis() - timestamp > CACHE_DURATION;
        }

        // Getters and setters
        public long getTimestamp() { return timestamp; }
        public void setTimestamp(long timestamp) { this.timestamp = timestamp; }
        
        public List<FoodItem> getAlternatives() { return alternatives; }
        public void setAlternatives(List<FoodItem> alternatives) { this.alternatives = alternatives; }
        
        public String getOriginalFoodName() { return originalFoodName; }
        public void setOriginalFoodName(String originalFoodName) { this.originalFoodName = originalFoodName; }
        
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

    public void cacheAlternatives(String originalFoodName, UserProfile userProfile, List<FoodItem> alternatives) {
        try {
            String userProfileKey = generateUserProfileKey(userProfile);
            String cacheKey = originalFoodName.toLowerCase().replaceAll("[^a-z0-9]", "_") + "_" + userProfileKey;
            
            CachedAlternatives cached = new CachedAlternatives(alternatives, originalFoodName, userProfileKey);
            
            Map<String, CachedAlternatives> cache = getAlternativesCache();
            cache.put(cacheKey, cached);
            saveAlternativesCache(cache);
            
            Log.d(TAG, "Cached alternatives for " + originalFoodName + " (user: " + userProfileKey + ")");
        } catch (Exception e) {
            Log.e(TAG, "Error caching alternatives: " + e.getMessage());
        }
    }

    public CachedAlternatives getCachedAlternatives(String originalFoodName, UserProfile userProfile) {
        try {
            String userProfileKey = generateUserProfileKey(userProfile);
            String cacheKey = originalFoodName.toLowerCase().replaceAll("[^a-z0-9]", "_") + "_" + userProfileKey;
            
            Map<String, CachedAlternatives> cache = getAlternativesCache();
            CachedAlternatives cached = cache.get(cacheKey);
            
            if (cached != null && !cached.isExpired()) {
                Log.d(TAG, "Found valid cached alternatives for " + originalFoodName);
                return cached;
            } else if (cached != null) {
                Log.d(TAG, "Cached alternatives expired for " + originalFoodName);
                // Remove expired cache
                cache.remove(cacheKey);
                saveAlternativesCache(cache);
            }
            
            return null;
        } catch (Exception e) {
            Log.e(TAG, "Error getting cached alternatives: " + e.getMessage());
            return null;
        }
    }

    public void clearCache() {
        try {
            sharedPreferences.edit()
                .remove(KEY_CACHE)
                .remove(KEY_ALTERNATIVES_CACHE)
                .apply();
            Log.d(TAG, "Cache cleared for user: " + userEmail);
        } catch (Exception e) {
            Log.e(TAG, "Error clearing cache: " + e.getMessage());
        }
    }
    
    /**
     * Clear all cache data for a specific user (used when logging out)
     */
    public static void clearUserData(Context context, String userEmail) {
        try {
            String prefsFileName = PREFS_NAME + "_" + userEmail.replace("@", "_").replace(".", "_");
            SharedPreferences prefs = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
            prefs.edit().clear().apply();
            Log.d(TAG, "Cleared all cache data for user: " + userEmail);
        } catch (Exception e) {
            Log.e(TAG, "Error clearing user cache data: " + e.getMessage());
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

    private Map<String, CachedAlternatives> getAlternativesCache() {
        try {
            String json = sharedPreferences.getString(KEY_ALTERNATIVES_CACHE, null);
            if (json == null) {
                return new HashMap<>();
            }
            Type type = new TypeToken<Map<String, CachedAlternatives>>() {}.getType();
            return gson.fromJson(json, type);
        } catch (Exception e) {
            Log.e(TAG, "Error getting alternatives cache: " + e.getMessage());
            return new HashMap<>();
        }
    }

    private void saveAlternativesCache(Map<String, CachedAlternatives> cache) {
        try {
            String json = gson.toJson(cache);
            sharedPreferences.edit().putString(KEY_ALTERNATIVES_CACHE, json).apply();
        } catch (Exception e) {
            Log.e(TAG, "Error saving alternatives cache: " + e.getMessage());
        }
    }
}
