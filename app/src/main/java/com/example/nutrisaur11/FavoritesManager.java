package com.example.nutrisaur11;

import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.util.Log;
import java.util.ArrayList;
import java.util.List;

public class FavoritesManager {
    private static final String TAG = "FavoritesManager";
    private final UserPreferencesDbHelper dbHelper;
    private final Context context;

    public FavoritesManager(Context context) {
        this.context = context;
        this.dbHelper = new UserPreferencesDbHelper(context);
    }

    /**
     * Add a dish to favorites
     */
    public boolean addToFavorites(String userEmail, DishData.Dish dish) {
        try {
            SQLiteDatabase db = dbHelper.getWritableDatabase();
            
            ContentValues values = new ContentValues();
            values.put(UserPreferencesDbHelper.COL_FAVORITE_USER_EMAIL, userEmail);
            values.put(UserPreferencesDbHelper.COL_FAVORITE_DISH_NAME, dish.name);
            values.put(UserPreferencesDbHelper.COL_FAVORITE_DISH_EMOJI, dish.emoji);
            values.put(UserPreferencesDbHelper.COL_FAVORITE_DISH_DESC, dish.desc);
            values.put(UserPreferencesDbHelper.COL_FAVORITE_DISH_TAGS, dish.tags.toString());
            
            long result = db.insertWithOnConflict(
                UserPreferencesDbHelper.TABLE_FAVORITES,
                null,
                values,
                SQLiteDatabase.CONFLICT_REPLACE
            );
            
            if (result != -1) {
                Log.d(TAG, "Added " + dish.name + " to favorites for " + userEmail);
                return true;
            } else {
                Log.e(TAG, "Failed to add " + dish.name + " to favorites");
                return false;
            }
        } catch (Exception e) {
            Log.e(TAG, "Error adding to favorites: " + e.getMessage());
            return false;
        }
    }

    /**
     * Remove a dish from favorites
     */
    public boolean removeFromFavorites(String userEmail, String dishName) {
        try {
            SQLiteDatabase db = dbHelper.getWritableDatabase();
            
            int result = db.delete(
                UserPreferencesDbHelper.TABLE_FAVORITES,
                UserPreferencesDbHelper.COL_FAVORITE_USER_EMAIL + "=? AND " + 
                UserPreferencesDbHelper.COL_FAVORITE_DISH_NAME + "=?",
                new String[]{userEmail, dishName}
            );
            
            if (result > 0) {
                Log.d(TAG, "Removed " + dishName + " from favorites for " + userEmail);
                return true;
            } else {
                Log.e(TAG, "Failed to remove " + dishName + " from favorites");
                return false;
            }
        } catch (Exception e) {
            Log.e(TAG, "Error removing from favorites: " + e.getMessage());
            return false;
        }
    }

    /**
     * Check if a dish is in favorites
     */
    public boolean isFavorite(String userEmail, String dishName) {
        try {
            SQLiteDatabase db = dbHelper.getReadableDatabase();
            
            Cursor cursor = db.query(
                UserPreferencesDbHelper.TABLE_FAVORITES,
                new String[]{UserPreferencesDbHelper.COL_FAVORITE_ID},
                UserPreferencesDbHelper.COL_FAVORITE_USER_EMAIL + "=? AND " + 
                UserPreferencesDbHelper.COL_FAVORITE_DISH_NAME + "=?",
                new String[]{userEmail, dishName},
                null, null, null
            );
            
            boolean isFavorite = cursor.getCount() > 0;
            cursor.close();
            
            return isFavorite;
        } catch (Exception e) {
            Log.e(TAG, "Error checking favorite status: " + e.getMessage());
            return false;
        }
    }

    /**
     * Get all favorites for a user
     */
    public List<DishData.Dish> getFavorites(String userEmail) {
        List<DishData.Dish> favorites = new ArrayList<>();
        
        try {
            SQLiteDatabase db = dbHelper.getReadableDatabase();
            
            Cursor cursor = db.query(
                UserPreferencesDbHelper.TABLE_FAVORITES,
                null,
                UserPreferencesDbHelper.COL_FAVORITE_USER_EMAIL + "=?",
                new String[]{userEmail},
                null, null,
                UserPreferencesDbHelper.COL_FAVORITE_ADDED_AT + " DESC"
            );
            
            while (cursor.moveToNext()) {
                String dishName = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_FAVORITE_DISH_NAME));
                String dishEmoji = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_FAVORITE_DISH_EMOJI));
                String dishDesc = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_FAVORITE_DISH_DESC));
                String dishTags = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_FAVORITE_DISH_TAGS));
                
                // Create a Dish object from the database data
                DishData.Dish dish = new DishData.Dish(dishName, dishEmoji, dishDesc, new ArrayList<>(), new ArrayList<>());
                
                // Parse tags if available
                if (dishTags != null && !dishTags.isEmpty()) {
                    try {
                        // Remove brackets and split by comma
                        String tagsStr = dishTags.replace("[", "").replace("]", "");
                        String[] tags = tagsStr.split(", ");
                        List<String> parsedTags = new ArrayList<>();
                        for (String tag : tags) {
                            if (!tag.trim().isEmpty()) {
                                parsedTags.add(tag.trim());
                            }
                        }
                        // Create a new Dish with parsed tags
                        dish = new DishData.Dish(dishName, dishEmoji, dishDesc, parsedTags, new ArrayList<>());
                    } catch (Exception e) {
                        Log.e(TAG, "Error parsing tags: " + e.getMessage());
                        dish = new DishData.Dish(dishName, dishEmoji, dishDesc, new ArrayList<>(), new ArrayList<>());
                    }
                }
                
                favorites.add(dish);
            }
            
            cursor.close();
            Log.d(TAG, "Retrieved " + favorites.size() + " favorites for " + userEmail);
            
        } catch (Exception e) {
            Log.e(TAG, "Error getting favorites: " + e.getMessage());
        }
        
        return favorites;
    }

    /**
     * Get current user email from SharedPreferences
     */
    private String getCurrentUserEmail() {
        android.content.SharedPreferences prefs = context.getSharedPreferences("UserPrefs", Context.MODE_PRIVATE);
        return prefs.getString("user_email", "");
    }

    /**
     * Close the database helper
     */
    public void close() {
        if (dbHelper != null) {
            dbHelper.close();
        }
    }
}
