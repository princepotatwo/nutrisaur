package com.example.nutrisaur11;

import android.content.Intent;
import android.util.Log;
import android.os.Build;

/**
 * Helper class to add the startFoodPreloadService method to MainActivity
 * This is a temporary solution to add the method without modifying the large MainActivity file
 */
public class MainActivityHelper {
    
    public static void startFoodPreloadService(android.content.Context context) {
        try {
            Intent intent = new Intent(context, FoodPreloadService.class);
            
            // For Android 8.0+ (API 26+), use startForegroundService for background restrictions
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                context.startForegroundService(intent);
            } else {
                context.startService(intent);
            }
            Log.d("MainActivity", "Started food preload service");
        } catch (Exception e) {
            Log.e("MainActivity", "Failed to start food preload service: " + e.getMessage());
            // Don't crash the app if service start fails
        }
    }
}
