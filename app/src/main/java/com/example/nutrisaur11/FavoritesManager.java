package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class FavoritesManager {
    private static final String TAG = "FavoritesManager";
    private static final String PREFS_NAME = "favorites_prefs";
    private static final String FAVORITES_KEY = "favorite_foods";
    
    private Context context;
    private SharedPreferences prefs;
    private Gson gson;
    private String userEmail;
    
    public FavoritesManager(Context context) {
        this.context = context;
        // Get current user email for user-specific storage
        SharedPreferences userPrefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        userEmail = userPrefs.getString("current_user_email", "default_user");
        
        // Create user-specific preferences file
        String prefsFileName = PREFS_NAME + "_" + userEmail.replace("@", "_").replace(".", "_");
        this.prefs = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
        this.gson = new Gson();
    }
    
    public void addToFavorites(FoodItem foodItem) {
        List<FoodItem> favorites = getFavorites();
        
        // Check if already in favorites
        for (FoodItem favorite : favorites) {
            if (favorite.getId().equals(foodItem.getId())) {
                Log.d(TAG, "Food already in favorites: " + foodItem.getName());
                return;
            }
        }
        
        favorites.add(foodItem);
        saveFavorites(favorites);
        Log.d(TAG, "Added to favorites: " + foodItem.getName());
    }
    
    public void removeFromFavorites(FoodItem foodItem) {
        List<FoodItem> favorites = getFavorites();
        
        for (int i = 0; i < favorites.size(); i++) {
            if (favorites.get(i).getId().equals(foodItem.getId())) {
                favorites.remove(i);
                saveFavorites(favorites);
                Log.d(TAG, "Removed from favorites: " + foodItem.getName());
                return;
            }
        }
        
        Log.d(TAG, "Food not found in favorites: " + foodItem.getName());
    }
    
    public boolean isFavorite(FoodItem foodItem) {
        List<FoodItem> favorites = getFavorites();
        
        for (FoodItem favorite : favorites) {
            if (favorite.getId().equals(foodItem.getId())) {
                return true;
            }
        }
        
        return false;
    }
    
    public List<FoodItem> getFavorites() {
        String favoritesJson = prefs.getString(FAVORITES_KEY, "[]");
        
        try {
            Type listType = new TypeToken<List<FoodItem>>(){}.getType();
            List<FoodItem> favorites = gson.fromJson(favoritesJson, listType);
            
            if (favorites == null) {
                favorites = new ArrayList<>();
            }
            
            return favorites;
        } catch (Exception e) {
            Log.e(TAG, "Error loading favorites: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    private void saveFavorites(List<FoodItem> favorites) {
        String favoritesJson = gson.toJson(favorites);
        prefs.edit().putString(FAVORITES_KEY, favoritesJson).apply();
    }
    
    public void clearFavorites() {
        prefs.edit().remove(FAVORITES_KEY).apply();
        Log.d(TAG, "Cleared all favorites");
    }
    
    /**
     * Clear all favorites data for a specific user (used when logging out)
     */
    public static void clearUserData(Context context, String userEmail) {
        try {
            String prefsFileName = PREFS_NAME + "_" + userEmail.replace("@", "_").replace(".", "_");
            SharedPreferences prefs = context.getSharedPreferences(prefsFileName, Context.MODE_PRIVATE);
            prefs.edit().clear().apply();
            Log.d(TAG, "Cleared all favorites data for user: " + userEmail);
        } catch (Exception e) {
            Log.e(TAG, "Error clearing user favorites data: " + e.getMessage());
        }
    }
}