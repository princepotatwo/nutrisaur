package com.example.nutrisaur11;

import android.util.Log;
import okhttp3.*;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.TimeUnit;

public class FatSecretService {
    private static final String TAG = "FatSecretService";
    private static final String BASE_URL = "https://platform.fatsecret.com/rest/server.api";
    private static final String CLIENT_ID = "63c29de913a54b108f99106d9d1b3c5e";
    private static final String CLIENT_SECRET = "45a16d6866d14f949e6be311d1970d44";
    
    private OkHttpClient httpClient;
    
    public FatSecretService() {
        httpClient = new OkHttpClient.Builder()
                .connectTimeout(30, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS)
                .writeTimeout(30, TimeUnit.SECONDS)
                .build();
    }
    
    public interface FoodSearchCallback {
        void onSuccess(List<FoodItem> foods);
        void onError(String error);
    }
    
    public void searchFoods(String query, int maxCalories, FoodSearchCallback callback) {
        // For now, return mock data based on meal category and calorie limits
        // In the future, this will make actual API calls to FatSecret
        
        Log.d(TAG, "searchFoods called with query: " + query + ", maxCalories: " + maxCalories);
        
        List<FoodItem> foods = new ArrayList<>();
        
        if (query.equalsIgnoreCase("breakfast")) {
            foods = getBreakfastFoods(maxCalories);
        } else if (query.equalsIgnoreCase("lunch")) {
            foods = getLunchFoods(maxCalories);
        } else if (query.equalsIgnoreCase("dinner")) {
            foods = getDinnerFoods(maxCalories);
        } else if (query.equalsIgnoreCase("snacks")) {
            foods = getSnacksFoods(maxCalories);
        } else {
            // General search
            foods = getGeneralFoods(query, maxCalories);
        }
        
        Log.d(TAG, "Returning " + foods.size() + " foods for query: " + query);
        callback.onSuccess(foods);
    }
    
    private List<FoodItem> getBreakfastFoods(int maxCalories) {
        List<FoodItem> foods = new ArrayList<>();
        
        // Healthy breakfast options within calorie limit
        foods.add(new FoodItem("1", "Oatmeal with Berries", 150, 100, "g"));
        foods.add(new FoodItem("2", "Greek Yogurt", 100, 150, "g"));
        foods.add(new FoodItem("3", "Scrambled Eggs", 160, 100, "g"));
        foods.add(new FoodItem("4", "Whole Grain Toast", 80, 50, "g"));
        foods.add(new FoodItem("5", "Banana", 105, 120, "g"));
        foods.add(new FoodItem("6", "Almonds", 160, 30, "g"));
        foods.add(new FoodItem("7", "Smoothie Bowl", 200, 250, "g"));
        foods.add(new FoodItem("8", "Avocado Toast", 180, 100, "g"));
        
        Log.d(TAG, "Generated " + foods.size() + " breakfast foods before filtering");
        List<FoodItem> filtered = filterByCalories(foods, maxCalories);
        Log.d(TAG, "After filtering by " + maxCalories + " calories: " + filtered.size() + " foods");
        return filtered;
    }
    
    private List<FoodItem> getLunchFoods(int maxCalories) {
        List<FoodItem> foods = new ArrayList<>();
        
        // Healthy lunch options within calorie limit
        foods.add(new FoodItem("1", "Grilled Chicken Salad", 250, 200, "g"));
        foods.add(new FoodItem("2", "Quinoa Bowl", 220, 150, "g"));
        foods.add(new FoodItem("3", "Turkey Wrap", 300, 200, "g"));
        foods.add(new FoodItem("4", "Vegetable Soup", 120, 300, "g"));
        foods.add(new FoodItem("5", "Salmon Fillet", 280, 120, "g"));
        foods.add(new FoodItem("6", "Brown Rice Bowl", 200, 150, "g"));
        foods.add(new FoodItem("7", "Mixed Vegetables", 80, 200, "g"));
        foods.add(new FoodItem("8", "Lentil Curry", 180, 200, "g"));
        
        return filterByCalories(foods, maxCalories);
    }
    
    private List<FoodItem> getDinnerFoods(int maxCalories) {
        List<FoodItem> foods = new ArrayList<>();
        
        // Healthy dinner options within calorie limit
        foods.add(new FoodItem("1", "Baked Salmon", 350, 150, "g"));
        foods.add(new FoodItem("2", "Grilled Chicken Breast", 200, 120, "g"));
        foods.add(new FoodItem("3", "Vegetable Stir Fry", 180, 200, "g"));
        foods.add(new FoodItem("4", "Turkey Meatballs", 250, 150, "g"));
        foods.add(new FoodItem("5", "Roasted Vegetables", 120, 250, "g"));
        foods.add(new FoodItem("6", "Quinoa Pilaf", 200, 150, "g"));
        foods.add(new FoodItem("7", "Baked Cod", 180, 120, "g"));
        foods.add(new FoodItem("8", "Sweet Potato", 130, 150, "g"));
        
        return filterByCalories(foods, maxCalories);
    }
    
    private List<FoodItem> getSnacksFoods(int maxCalories) {
        List<FoodItem> foods = new ArrayList<>();
        
        // Healthy snack options within calorie limit
        foods.add(new FoodItem("1", "Apple Slices", 80, 150, "g"));
        foods.add(new FoodItem("2", "Carrot Sticks", 25, 100, "g"));
        foods.add(new FoodItem("3", "Greek Yogurt", 100, 150, "g"));
        foods.add(new FoodItem("4", "Mixed Nuts", 160, 30, "g"));
        foods.add(new FoodItem("5", "Hummus", 100, 50, "g"));
        foods.add(new FoodItem("6", "Berries", 60, 100, "g"));
        foods.add(new FoodItem("7", "Rice Cakes", 50, 20, "g"));
        foods.add(new FoodItem("8", "Cucumber Slices", 15, 100, "g"));
        
        return filterByCalories(foods, maxCalories);
    }
    
    private List<FoodItem> getGeneralFoods(String query, int maxCalories) {
        List<FoodItem> foods = new ArrayList<>();
        
        // General search results
        foods.add(new FoodItem("1", "Grilled Chicken", 200, 100, "g"));
        foods.add(new FoodItem("2", "Brown Rice", 110, 100, "g"));
        foods.add(new FoodItem("3", "Steamed Broccoli", 35, 100, "g"));
        foods.add(new FoodItem("4", "Salmon Fillet", 250, 100, "g"));
        foods.add(new FoodItem("5", "Quinoa", 120, 100, "g"));
        foods.add(new FoodItem("6", "Mixed Vegetables", 25, 100, "g"));
        
        return filterByCalories(foods, maxCalories);
    }
    
    private List<FoodItem> filterByCalories(List<FoodItem> foods, int maxCalories) {
        List<FoodItem> filteredFoods = new ArrayList<>();
        for (FoodItem food : foods) {
            if (food.getCalories() <= maxCalories) {
                filteredFoods.add(food);
            }
        }
        return filteredFoods;
    }
    
    // Future implementation for actual FatSecret API calls
    private void makeFatSecretAPICall(String method, String query, int maxCalories, FoodSearchCallback callback) {
        // TODO: Implement OAuth 1.0 authentication
        // TODO: Make actual API calls to FatSecret
        // TODO: Parse JSON responses and convert to FoodItem objects
        
        Log.d(TAG, "Making FatSecret API call: " + method + " for query: " + query);
        
        // For now, just call the mock method
        searchFoods(query, maxCalories, callback);
    }
}
