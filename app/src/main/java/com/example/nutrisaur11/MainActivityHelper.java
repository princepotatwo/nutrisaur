package com.example.nutrisaur11;

import android.content.Intent;
import android.util.Log;

/**
 * Helper class to add the startFoodPreloadService method to MainActivity
 * This is a temporary solution to add the method without modifying the large MainActivity file
 */
public class MainActivityHelper {
    
    public static void startFoodPreloadService(android.content.Context context) {
        Intent intent = new Intent(context, FoodPreloadService.class);
        context.startService(intent);
        Log.d("MainActivity", "Started food preload service");
    }
}
