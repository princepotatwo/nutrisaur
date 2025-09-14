package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

/**
 * Manages daily reset functionality for calorie tracking and nutrition data
 */
public class DailyResetManager {
    private static final String TAG = "DailyResetManager";
    private static final String KEY_LAST_RESET_DATE = "last_daily_reset_date";
    private static final String MAIN_PREFS_NAME = "nutrisaur_prefs";
    
    private Context context;
    private SharedPreferences mainPrefs;
    
    public DailyResetManager(Context context) {
        this.context = context;
        this.mainPrefs = context.getSharedPreferences(MAIN_PREFS_NAME, Context.MODE_PRIVATE);
    }
    
    /**
     * Check if daily reset is needed and perform reset if necessary
     */
    public void checkAndResetDaily() {
        try {
            String currentDate = getCurrentDateString();
            String lastResetDate = mainPrefs.getString(KEY_LAST_RESET_DATE, "");
            
            if (!currentDate.equals(lastResetDate)) {
                Log.d(TAG, "New day detected. Performing daily reset. Current: " + currentDate + ", Last: " + lastResetDate);
                performDailyReset();
                mainPrefs.edit().putString(KEY_LAST_RESET_DATE, currentDate).apply();
            } else {
                Log.d(TAG, "Same day, no reset needed. Current: " + currentDate);
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
     * Perform daily reset of all calorie and nutrition data
     */
    private void performDailyReset() {
        try {
            // Reset CalorieTracker data directly without creating new instance
            resetCalorieTrackerData();
            
            // Reset main SharedPreferences calorie data
            SharedPreferences.Editor editor = mainPrefs.edit();
            
            // Reset calorie tracking data
            editor.putInt("calories_left", 0);
            editor.putInt("calories_eaten", 0);
            editor.putInt("calories_burned", 0);
            editor.putInt("walking_calories", 0);
            editor.putInt("activity_calories", 0);
            
            // Reset macro tracking data
            editor.putInt("carbs_current", 0);
            editor.putInt("protein_current", 0);
            editor.putInt("fat_current", 0);
            
            // Keep targets as they don't change daily
            // carbs_target, protein_target, fat_target remain unchanged
            
            editor.apply();
            
            Log.d(TAG, "Daily reset completed successfully");
        } catch (Exception e) {
            Log.e(TAG, "Error performing daily reset: " + e.getMessage());
        }
    }
    
    /**
     * Reset CalorieTracker data directly without creating new instance
     */
    private void resetCalorieTrackerData() {
        try {
            // Get current user email for user-specific storage
            SharedPreferences userPrefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
            String userEmail = userPrefs.getString("current_user_email", "default_user");
            
            // Create user-specific preferences file
            String prefsFileName = "calorie_tracker_prefs_" + userEmail.replace("@", "_").replace(".", "_");
            SharedPreferences caloriePrefs = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
            
            // Clear CalorieTracker data
            caloriePrefs.edit().clear().apply();
            
            Log.d(TAG, "CalorieTracker data reset completed for user: " + userEmail);
        } catch (Exception e) {
            Log.e(TAG, "Error resetting CalorieTracker data: " + e.getMessage());
        }
    }
    
    /**
     * Manually trigger daily reset (for testing or manual reset)
     */
    public void manualResetDaily() {
        try {
            performDailyReset();
            String currentDate = getCurrentDateString();
            mainPrefs.edit().putString(KEY_LAST_RESET_DATE, currentDate).apply();
            Log.d(TAG, "Manual daily reset completed");
        } catch (Exception e) {
            Log.e(TAG, "Error in manual daily reset: " + e.getMessage());
        }
    }
    
    /**
     * Get the last reset date
     */
    public String getLastResetDate() {
        return mainPrefs.getString(KEY_LAST_RESET_DATE, "Never");
    }
    
    /**
     * Check if today is a new day compared to last reset
     */
    public boolean isNewDay() {
        try {
            String currentDate = getCurrentDateString();
            String lastResetDate = mainPrefs.getString(KEY_LAST_RESET_DATE, "");
            return !currentDate.equals(lastResetDate);
        } catch (Exception e) {
            Log.e(TAG, "Error checking if new day: " + e.getMessage());
            return false;
        }
    }
    
    /**
     * Get current date for debugging
     */
    public String getCurrentDate() {
        return getCurrentDateString();
    }
    
    /**
     * Force reset for testing purposes
     */
    public void forceResetForTesting() {
        Log.d(TAG, "Force reset for testing - clearing all data");
        performDailyReset();
        String currentDate = getCurrentDateString();
        mainPrefs.edit().putString(KEY_LAST_RESET_DATE, currentDate).apply();
    }
}
