package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import org.json.JSONObject;
import org.json.JSONException;

/**
 * Cache manager for kcal suggestions to avoid regenerating them unless user profile changes
 */
public class KcalSuggestionCacheManager {
    private static final String TAG = "KcalSuggestionCache";
    private static final String CACHE_PREFS = "kcal_suggestion_cache";
    private static final String CACHE_KEY = "cached_kcal_suggestion";
    private static final String USER_PROFILE_KEY = "user_profile_hash";
    private static final String CACHE_TIMESTAMP_KEY = "cache_timestamp";
    private static final long CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 hours
    
    private Context context;
    private SharedPreferences prefs;
    
    public KcalSuggestionCacheManager(Context context) {
        this.context = context;
        this.prefs = context.getSharedPreferences(CACHE_PREFS, Context.MODE_PRIVATE);
    }
    
    /**
     * Generate a hash key for user profile to detect changes
     */
    public String generateUserProfileHash(UserProfile userProfile) {
        if (userProfile == null) return "null";
        
        // Create a hash based on key user attributes that affect kcal calculations
        String profileData = String.format("%s_%d_%.1f_%.1f_%s_%s_%s",
            userProfile.getGender(),
            userProfile.getAge(),
            userProfile.getWeight(),
            userProfile.getHeight(),
            userProfile.getActivityLevel(),
            userProfile.getHealthGoals(),
            userProfile.getBmiCategory()
        );
        
        return String.valueOf(profileData.hashCode());
    }
    
    /**
     * Check if cached kcal suggestion is valid and user profile hasn't changed
     */
    public boolean isCacheValid(UserProfile userProfile) {
        try {
            // Check if cache exists
            if (!prefs.contains(CACHE_KEY)) {
                Log.d(TAG, "No cached kcal suggestion found");
                return false;
            }
            
            // Check if cache is expired
            long timestamp = prefs.getLong(CACHE_TIMESTAMP_KEY, 0);
            long currentTime = System.currentTimeMillis();
            if ((currentTime - timestamp) > CACHE_DURATION) {
                Log.d(TAG, "Cached kcal suggestion expired");
                return false;
            }
            
            // Check if user profile has changed
            String currentProfileHash = generateUserProfileHash(userProfile);
            String cachedProfileHash = prefs.getString(USER_PROFILE_KEY, "");
            
            if (!currentProfileHash.equals(cachedProfileHash)) {
                Log.d(TAG, "User profile changed, cache invalid");
                return false;
            }
            
            Log.d(TAG, "Cached kcal suggestion is valid");
            return true;
            
        } catch (Exception e) {
            Log.e(TAG, "Error checking cache validity: " + e.getMessage());
            return false;
        }
    }
    
    /**
     * Get cached kcal suggestion
     */
    public NutritionData getCachedKcalSuggestion() {
        try {
            String cachedJson = prefs.getString(CACHE_KEY, "");
            if (cachedJson.isEmpty()) {
                Log.d(TAG, "No cached kcal suggestion data found");
                return null;
            }
            
            JSONObject jsonData = new JSONObject(cachedJson);
            NutritionData nutritionData = new NutritionData();
            
            // Parse cached data
            nutritionData.setTotalCalories(jsonData.getInt("totalCalories"));
            nutritionData.setCaloriesLeft(jsonData.getInt("caloriesLeft"));
            nutritionData.setCaloriesEaten(jsonData.getInt("caloriesEaten"));
            nutritionData.setCaloriesBurned(jsonData.getInt("caloriesBurned"));
            
            // Parse macronutrients
            JSONObject macronutrientsJson = jsonData.getJSONObject("macronutrients");
            NutritionData.Macronutrients macronutrients = new NutritionData.Macronutrients();
            macronutrients.setCarbs(macronutrientsJson.getInt("carbs"));
            macronutrients.setProtein(macronutrientsJson.getInt("protein"));
            macronutrients.setFat(macronutrientsJson.getInt("fat"));
            macronutrients.setCarbsTarget(macronutrientsJson.getInt("carbsTarget"));
            macronutrients.setProteinTarget(macronutrientsJson.getInt("proteinTarget"));
            macronutrients.setFatTarget(macronutrientsJson.getInt("fatTarget"));
            nutritionData.setMacronutrients(macronutrients);
            
            // Parse activity data
            JSONObject activityJson = jsonData.getJSONObject("activity");
            NutritionData.ActivityData activity = new NutritionData.ActivityData();
            activity.setWalkingCalories(activityJson.getInt("walkingCalories"));
            activity.setActivityCalories(activityJson.getInt("activityCalories"));
            activity.setTotalBurned(activityJson.getInt("totalBurned"));
            nutritionData.setActivity(activity);
            
            // Parse meal distribution
            JSONObject mealDistJson = jsonData.getJSONObject("mealDistribution");
            NutritionData.MealDistribution mealDist = new NutritionData.MealDistribution();
            mealDist.setBreakfastCalories(mealDistJson.getInt("breakfastCalories"));
            mealDist.setLunchCalories(mealDistJson.getInt("lunchCalories"));
            mealDist.setDinnerCalories(mealDistJson.getInt("dinnerCalories"));
            mealDist.setSnacksCalories(mealDistJson.getInt("snacksCalories"));
            mealDist.setBreakfastEaten(mealDistJson.getInt("breakfastEaten"));
            mealDist.setLunchEaten(mealDistJson.getInt("lunchEaten"));
            mealDist.setDinnerEaten(mealDistJson.getInt("dinnerEaten"));
            mealDist.setSnacksEaten(mealDistJson.getInt("snacksEaten"));
            mealDist.setBreakfastRecommendation(mealDistJson.getString("breakfastRecommendation"));
            mealDist.setLunchRecommendation(mealDistJson.getString("lunchRecommendation"));
            mealDist.setDinnerRecommendation(mealDistJson.getString("dinnerRecommendation"));
            mealDist.setSnacksRecommendation(mealDistJson.getString("snacksRecommendation"));
            nutritionData.setMealDistribution(mealDist);
            
            // Parse other fields
            nutritionData.setRecommendation(jsonData.getString("recommendation"));
            nutritionData.setHealthStatus(jsonData.getString("healthStatus"));
            nutritionData.setBmi(jsonData.getDouble("bmi"));
            nutritionData.setBmiCategory(jsonData.getString("bmiCategory"));
            
            Log.d(TAG, "Successfully loaded cached kcal suggestion");
            return nutritionData;
            
        } catch (Exception e) {
            Log.e(TAG, "Error loading cached kcal suggestion: " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Cache kcal suggestion with user profile hash
     */
    public void cacheKcalSuggestion(UserProfile userProfile, NutritionData nutritionData) {
        try {
            String profileHash = generateUserProfileHash(userProfile);
            long timestamp = System.currentTimeMillis();
            
            // Convert NutritionData to JSON
            JSONObject jsonData = new JSONObject();
            jsonData.put("totalCalories", nutritionData.getTotalCalories());
            jsonData.put("caloriesLeft", nutritionData.getCaloriesLeft());
            jsonData.put("caloriesEaten", nutritionData.getCaloriesEaten());
            jsonData.put("caloriesBurned", nutritionData.getCaloriesBurned());
            
            // Add macronutrients
            JSONObject macronutrients = new JSONObject();
            NutritionData.Macronutrients macronutrientsData = nutritionData.getMacronutrients();
            if (macronutrientsData != null) {
                macronutrients.put("carbs", macronutrientsData.getCarbs());
                macronutrients.put("protein", macronutrientsData.getProtein());
                macronutrients.put("fat", macronutrientsData.getFat());
                macronutrients.put("carbsTarget", macronutrientsData.getCarbsTarget());
                macronutrients.put("proteinTarget", macronutrientsData.getProteinTarget());
                macronutrients.put("fatTarget", macronutrientsData.getFatTarget());
            }
            jsonData.put("macronutrients", macronutrients);
            
            // Add activity data
            JSONObject activity = new JSONObject();
            NutritionData.ActivityData activityData = nutritionData.getActivity();
            if (activityData != null) {
                activity.put("walkingCalories", activityData.getWalkingCalories());
                activity.put("activityCalories", activityData.getActivityCalories());
                activity.put("totalBurned", activityData.getTotalBurned());
            }
            jsonData.put("activity", activity);
            
            // Add meal distribution
            JSONObject mealDist = new JSONObject();
            NutritionData.MealDistribution mealDistData = nutritionData.getMealDistribution();
            if (mealDistData != null) {
                mealDist.put("breakfastCalories", mealDistData.getBreakfastCalories());
                mealDist.put("lunchCalories", mealDistData.getLunchCalories());
                mealDist.put("dinnerCalories", mealDistData.getDinnerCalories());
                mealDist.put("snacksCalories", mealDistData.getSnacksCalories());
                mealDist.put("breakfastEaten", mealDistData.getBreakfastEaten());
                mealDist.put("lunchEaten", mealDistData.getLunchEaten());
                mealDist.put("dinnerEaten", mealDistData.getDinnerEaten());
                mealDist.put("snacksEaten", mealDistData.getSnacksEaten());
                mealDist.put("breakfastRecommendation", mealDistData.getBreakfastRecommendation());
                mealDist.put("lunchRecommendation", mealDistData.getLunchRecommendation());
                mealDist.put("dinnerRecommendation", mealDistData.getDinnerRecommendation());
                mealDist.put("snacksRecommendation", mealDistData.getSnacksRecommendation());
            }
            jsonData.put("mealDistribution", mealDist);
            
            // Add other fields
            jsonData.put("recommendation", nutritionData.getRecommendation());
            jsonData.put("healthStatus", nutritionData.getHealthStatus());
            jsonData.put("bmi", nutritionData.getBmi());
            jsonData.put("bmiCategory", nutritionData.getBmiCategory());
            
            // Save to SharedPreferences
            SharedPreferences.Editor editor = prefs.edit();
            editor.putString(CACHE_KEY, jsonData.toString());
            editor.putString(USER_PROFILE_KEY, profileHash);
            editor.putLong(CACHE_TIMESTAMP_KEY, timestamp);
            editor.apply();
            
            Log.d(TAG, "Successfully cached kcal suggestion for user profile hash: " + profileHash);
            
        } catch (Exception e) {
            Log.e(TAG, "Error caching kcal suggestion: " + e.getMessage());
        }
    }
    
    /**
     * Clear cached kcal suggestion
     */
    public void clearCache() {
        try {
            SharedPreferences.Editor editor = prefs.edit();
            editor.remove(CACHE_KEY);
            editor.remove(USER_PROFILE_KEY);
            editor.remove(CACHE_TIMESTAMP_KEY);
            editor.apply();
            
            Log.d(TAG, "Cleared kcal suggestion cache");
        } catch (Exception e) {
            Log.e(TAG, "Error clearing cache: " + e.getMessage());
        }
    }
    
    /**
     * Check if user profile has changed since last cache
     */
    public boolean hasUserProfileChanged(UserProfile userProfile) {
        try {
            String currentProfileHash = generateUserProfileHash(userProfile);
            String cachedProfileHash = prefs.getString(USER_PROFILE_KEY, "");
            
            boolean hasChanged = !currentProfileHash.equals(cachedProfileHash);
            Log.d(TAG, "User profile changed: " + hasChanged + " (current: " + currentProfileHash + ", cached: " + cachedProfileHash + ")");
            
            return hasChanged;
        } catch (Exception e) {
            Log.e(TAG, "Error checking user profile change: " + e.getMessage());
            return true; // Assume changed if error
        }
    }
}
