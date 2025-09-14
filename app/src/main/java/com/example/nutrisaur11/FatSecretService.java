package com.example.nutrisaur11;

import android.content.Context;
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
    private Context context;
    
    public FatSecretService() {
        httpClient = new OkHttpClient.Builder()
                .connectTimeout(30, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS)
                .writeTimeout(30, TimeUnit.SECONDS)
                .build();
    }
    
    public FatSecretService(Context context) {
        this();
        this.context = context;
    }
    
    public interface FoodSearchCallback {
        void onSuccess(List<FoodItem> foods);
        void onError(String error);
    }
    
    public void searchFoods(String query, int maxCalories, FoodSearchCallback callback) {
        Log.d(TAG, "searchFoods called with query: " + query + ", maxCalories: " + maxCalories);
        
        // Use FatSecret API only - no fallback
        makeFatSecretAPICall("foods.search", query, maxCalories, new FoodSearchCallback() {
            @Override
            public void onSuccess(List<FoodItem> foods) {
                if (foods != null && !foods.isEmpty()) {
                    Log.d(TAG, "FatSecret API returned " + foods.size() + " foods");
                    callback.onSuccess(foods);
                } else {
                    Log.e(TAG, "FatSecret API returned empty results - no fallback");
                    callback.onSuccess(new ArrayList<>());
                }
            }
            
            @Override
            public void onError(String error) {
                Log.e(TAG, "FatSecret API failed: " + error + " - no fallback");
                callback.onSuccess(new ArrayList<>());
            }
        });
    }
    
    private List<FoodItem> getMockFoods(String query, int maxCalories) {
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
        
        Log.d(TAG, "Returning " + foods.size() + " mock foods for query: " + query);
        return foods;
    }
    
    /**
     * Get personalized food recommendations using Gemini AI
     */
    public void getPersonalizedFoods(String mealCategory, int maxCalories, UserProfile userProfile, FoodSearchCallback callback) {
        Log.d(TAG, "getPersonalizedFoods called for " + mealCategory + ", BMI: " + userProfile.getBmi() + " (" + userProfile.getBmiCategory() + "), Age: " + userProfile.getAge());
        
        // Use Gemini AI for personalized recommendations
        GeminiService geminiService = new GeminiService(context);
        geminiService.getPersonalizedFoodRecommendations(mealCategory, maxCalories, userProfile, new GeminiService.GeminiCallback() {
            @Override
            public void onSuccess(List<FoodItem> foods, String summary) {
                Log.d(TAG, "Gemini AI returned " + foods.size() + " personalized foods for " + mealCategory);
                Log.d(TAG, "AI Summary: " + summary);
                
                // Cache the results using GeminiCacheManager
                if (context != null) {
                    GeminiCacheManager cacheManager = new GeminiCacheManager(context);
                    cacheManager.cacheRecommendations(mealCategory, userProfile, foods, summary);
                    Log.d(TAG, "Cached Gemini recommendations for " + mealCategory);
                }
                
                callback.onSuccess(foods);
            }
            
            @Override
            public void onError(String error) {
                Log.e(TAG, "Gemini AI failed: " + error);
                // No fallback - return empty list to test Gemini integration
                callback.onSuccess(new ArrayList<>());
            }
        });
    }
    
    private String createPersonalizedSearchQuery(String mealCategory, UserProfile userProfile) {
        String bmiCategory = userProfile.getBmiCategory().toLowerCase();
        StringBuilder query = new StringBuilder();
        
        // Add Filipino food terms
        query.append("filipino ");
        
        // Add meal-specific terms
        switch (mealCategory.toLowerCase()) {
            case "breakfast":
                query.append("breakfast ");
                if (bmiCategory.equals("underweight")) {
                    query.append("high calorie rice porridge champorado taho pandesal ");
                } else if (bmiCategory.equals("obese")) {
                    query.append("low calorie oatmeal egg white ");
                } else {
                    query.append("balanced ");
                }
                break;
            case "lunch":
                query.append("lunch ");
                if (bmiCategory.equals("underweight")) {
                    query.append("high calorie adobo sinigang kare-kare ");
                } else if (bmiCategory.equals("obese")) {
                    query.append("low calorie grilled steamed ");
                } else {
                    query.append("balanced ");
                }
                break;
            case "dinner":
                query.append("dinner ");
                if (bmiCategory.equals("underweight")) {
                    query.append("high calorie lechon sisig ");
                } else if (bmiCategory.equals("obese")) {
                    query.append("low calorie fish chicken ");
                } else {
                    query.append("balanced ");
                }
                break;
            case "snacks":
                query.append("snacks ");
                if (bmiCategory.equals("underweight")) {
                    query.append("high calorie nuts dried fruits ");
                } else if (bmiCategory.equals("obese")) {
                    query.append("low calorie fruits vegetables ");
                } else {
                    query.append("balanced ");
                }
                break;
        }
        
        // Add protein requirements based on BMI
        if (bmiCategory.equals("underweight")) {
            query.append("protein rich chicken fish beef ");
        } else if (bmiCategory.equals("obese")) {
            query.append("lean protein fish chicken breast ");
        }
        
        return query.toString().trim();
    }
    
    private List<FoodItem> getFallbackPersonalizedFoods(String mealCategory, int maxCalories, UserProfile userProfile) {
        String bmiCategory = userProfile.getBmiCategory();
        String ageGroup = getAgeGroup(userProfile.getAge());
        
        List<FoodItem> foods = new ArrayList<>();
        
        if (mealCategory.equalsIgnoreCase("breakfast")) {
            foods = getPersonalizedBreakfastFoods(maxCalories, bmiCategory, ageGroup, userProfile);
        } else if (mealCategory.equalsIgnoreCase("lunch")) {
            foods = getPersonalizedLunchFoods(maxCalories, bmiCategory, ageGroup, userProfile);
        } else if (mealCategory.equalsIgnoreCase("dinner")) {
            foods = getPersonalizedDinnerFoods(maxCalories, bmiCategory, ageGroup, userProfile);
        } else if (mealCategory.equalsIgnoreCase("snacks")) {
            foods = getPersonalizedSnacksFoods(maxCalories, bmiCategory, ageGroup, userProfile);
        } else {
            foods = getPersonalizedGeneralFoods(mealCategory, maxCalories, bmiCategory, ageGroup, userProfile);
        }
        
        Log.d(TAG, "Returning " + foods.size() + " fallback personalized foods for " + mealCategory);
        return foods;
    }
    
    /**
     * Get age group classification for personalized recommendations
     */
    private String getAgeGroup(int age) {
        if (age < 2) {
            return "infant";
        } else if (age < 6) {
            return "toddler";
        } else if (age < 12) {
            return "child";
        } else if (age < 18) {
            return "adolescent";
        } else if (age < 65) {
            return "adult";
        } else {
            return "elderly";
        }
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
    
    // ===== PERSONALIZED FOOD RECOMMENDATIONS BASED ON BMI CATEGORY =====
    
    /**
     * Get personalized breakfast foods based on BMI category and age group
     */
    private List<FoodItem> getPersonalizedBreakfastFoods(int maxCalories, String bmiCategory, String ageGroup, UserProfile userProfile) {
        List<FoodItem> foods = new ArrayList<>();
        
        // Age-specific considerations
        boolean isChild = ageGroup.equals("infant") || ageGroup.equals("toddler") || ageGroup.equals("child");
        boolean isAdolescent = ageGroup.equals("adolescent");
        boolean isElderly = ageGroup.equals("elderly");
        
        switch (bmiCategory.toLowerCase()) {
            case "underweight":
                // High-calorie, nutrient-dense foods for weight gain
                if (isChild) {
                    // Child-friendly high-calorie foods
                    foods.add(new FoodItem("1", "Peanut Butter Oatmeal", 280, 120, "g"));
                    foods.add(new FoodItem("2", "Banana Pancakes with Syrup", 350, 150, "g"));
                    foods.add(new FoodItem("3", "Greek Yogurt with Berries", 220, 150, "g"));
                    foods.add(new FoodItem("4", "Scrambled Eggs with Cheese", 200, 100, "g"));
                    foods.add(new FoodItem("5", "Whole Grain Toast with Avocado", 250, 80, "g"));
                    foods.add(new FoodItem("6", "Fruit Smoothie with Milk", 300, 250, "ml"));
                    foods.add(new FoodItem("7", "Granola with Full-Fat Milk", 250, 120, "g"));
                    foods.add(new FoodItem("8", "Nut Butter on Crackers", 280, 80, "g"));
                } else if (isAdolescent) {
                    // Adolescent growth needs - higher calories
                    foods.add(new FoodItem("1", "Peanut Butter Oatmeal", 350, 150, "g"));
                    foods.add(new FoodItem("2", "Avocado Toast with Eggs", 400, 120, "g"));
                    foods.add(new FoodItem("3", "Greek Yogurt with Nuts & Honey", 320, 200, "g"));
                    foods.add(new FoodItem("4", "Banana Smoothie with Protein", 380, 300, "ml"));
                    foods.add(new FoodItem("5", "Whole Grain Pancakes with Syrup", 450, 200, "g"));
                    foods.add(new FoodItem("6", "Scrambled Eggs with Cheese", 280, 150, "g"));
                    foods.add(new FoodItem("7", "Granola with Full-Fat Milk", 350, 150, "g"));
                    foods.add(new FoodItem("8", "Nut Butter Sandwich", 350, 100, "g"));
                } else if (isElderly) {
                    // Elderly - softer, easier to digest, high-nutrient
                    foods.add(new FoodItem("1", "Soft Oatmeal with Nuts", 300, 150, "g"));
                    foods.add(new FoodItem("2", "Scrambled Eggs with Avocado", 320, 120, "g"));
                    foods.add(new FoodItem("3", "Greek Yogurt with Honey", 250, 200, "g"));
                    foods.add(new FoodItem("4", "Soft Toast with Nut Butter", 280, 80, "g"));
                    foods.add(new FoodItem("5", "Banana Smoothie", 300, 250, "ml"));
                    foods.add(new FoodItem("6", "Cottage Cheese with Fruit", 200, 150, "g"));
                    foods.add(new FoodItem("7", "Soft Pancakes with Syrup", 350, 150, "g"));
                    foods.add(new FoodItem("8", "Warm Milk with Oats", 250, 200, "ml"));
                } else {
                    // Adult underweight
                    foods.add(new FoodItem("1", "Peanut Butter Oatmeal", 320, 150, "g"));
                    foods.add(new FoodItem("2", "Avocado Toast with Eggs", 380, 120, "g"));
                    foods.add(new FoodItem("3", "Greek Yogurt with Nuts & Honey", 280, 200, "g"));
                    foods.add(new FoodItem("4", "Banana Smoothie with Protein", 350, 300, "ml"));
                    foods.add(new FoodItem("5", "Whole Grain Pancakes with Syrup", 400, 200, "g"));
                    foods.add(new FoodItem("6", "Scrambled Eggs with Cheese", 250, 150, "g"));
                    foods.add(new FoodItem("7", "Granola with Full-Fat Milk", 300, 150, "g"));
                    foods.add(new FoodItem("8", "Nut Butter Sandwich", 320, 100, "g"));
                }
                break;
                
            case "normal weight":
                // Balanced, nutritious foods to maintain healthy weight
                if (isChild) {
                    // Child-friendly balanced foods
                    foods.add(new FoodItem("1", "Oatmeal with Berries", 180, 120, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt with Fruit", 150, 150, "g"));
                    foods.add(new FoodItem("3", "Scrambled Eggs", 180, 100, "g"));
                    foods.add(new FoodItem("4", "Whole Grain Toast with Butter", 200, 80, "g"));
                    foods.add(new FoodItem("5", "Fruit Smoothie", 220, 200, "ml"));
                    foods.add(new FoodItem("6", "Banana with Peanut Butter", 180, 100, "g"));
                    foods.add(new FoodItem("7", "Cereal with Milk", 200, 150, "g"));
                    foods.add(new FoodItem("8", "Pancakes with Syrup", 250, 120, "g"));
                } else if (isAdolescent) {
                    // Adolescent growth needs - higher calories
                    foods.add(new FoodItem("1", "Oatmeal with Berries", 250, 150, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt Parfait", 220, 200, "g"));
                    foods.add(new FoodItem("3", "Scrambled Eggs with Vegetables", 280, 150, "g"));
                    foods.add(new FoodItem("4", "Whole Grain Toast with Avocado", 300, 100, "g"));
                    foods.add(new FoodItem("5", "Smoothie Bowl", 350, 250, "g"));
                    foods.add(new FoodItem("6", "Banana with Almonds", 250, 120, "g"));
                    foods.add(new FoodItem("7", "Quinoa Breakfast Bowl", 300, 150, "g"));
                    foods.add(new FoodItem("8", "Cottage Cheese with Fruit", 200, 150, "g"));
                } else if (isElderly) {
                    // Elderly - softer, nutrient-dense, easy to digest
                    foods.add(new FoodItem("1", "Soft Oatmeal with Berries", 180, 120, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt with Honey", 150, 150, "g"));
                    foods.add(new FoodItem("3", "Soft Scrambled Eggs", 160, 100, "g"));
                    foods.add(new FoodItem("4", "Soft Toast with Avocado", 200, 80, "g"));
                    foods.add(new FoodItem("5", "Fruit Smoothie", 200, 200, "ml"));
                    foods.add(new FoodItem("6", "Banana with Nuts", 180, 100, "g"));
                    foods.add(new FoodItem("7", "Soft Pancakes", 220, 120, "g"));
                    foods.add(new FoodItem("8", "Cottage Cheese with Fruit", 140, 120, "g"));
                } else {
                    // Adult normal weight
                    foods.add(new FoodItem("1", "Oatmeal with Berries", 200, 150, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt Parfait", 180, 200, "g"));
                    foods.add(new FoodItem("3", "Scrambled Eggs with Vegetables", 220, 150, "g"));
                    foods.add(new FoodItem("4", "Whole Grain Toast with Avocado", 250, 100, "g"));
                    foods.add(new FoodItem("5", "Smoothie Bowl", 280, 250, "g"));
                    foods.add(new FoodItem("6", "Banana with Almonds", 200, 120, "g"));
                    foods.add(new FoodItem("7", "Quinoa Breakfast Bowl", 240, 150, "g"));
                    foods.add(new FoodItem("8", "Cottage Cheese with Fruit", 160, 150, "g"));
                }
                break;
                
            case "overweight":
                // Lower-calorie, high-fiber foods for weight management
                if (isChild) {
                    // Child-friendly weight management foods
                    foods.add(new FoodItem("1", "Oatmeal with Fruit", 120, 100, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt with Berries", 100, 120, "g"));
                    foods.add(new FoodItem("3", "Scrambled Eggs with Vegetables", 140, 100, "g"));
                    foods.add(new FoodItem("4", "Whole Grain Toast with Tomato", 120, 60, "g"));
                    foods.add(new FoodItem("5", "Fruit Smoothie", 100, 200, "ml"));
                    foods.add(new FoodItem("6", "Apple Slices", 80, 100, "g"));
                    foods.add(new FoodItem("7", "Cereal with Milk", 120, 120, "g"));
                    foods.add(new FoodItem("8", "Pancakes (Small)", 150, 80, "g"));
                } else if (isAdolescent) {
                    // Adolescent weight management - still need growth nutrients
                    foods.add(new FoodItem("1", "Steel-Cut Oatmeal", 180, 120, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt with Berries", 150, 150, "g"));
                    foods.add(new FoodItem("3", "Vegetable Omelet", 200, 150, "g"));
                    foods.add(new FoodItem("4", "Whole Grain Toast with Tomato", 160, 80, "g"));
                    foods.add(new FoodItem("5", "Green Smoothie", 120, 250, "ml"));
                    foods.add(new FoodItem("6", "Apple with Peanut Butter", 180, 100, "g"));
                    foods.add(new FoodItem("7", "Chia Pudding", 150, 150, "g"));
                    foods.add(new FoodItem("8", "Egg White Scramble", 140, 150, "g"));
                } else if (isElderly) {
                    // Elderly weight management - softer, nutrient-dense
                    foods.add(new FoodItem("1", "Soft Oatmeal with Fruit", 120, 100, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt with Berries", 100, 120, "g"));
                    foods.add(new FoodItem("3", "Soft Scrambled Eggs", 120, 100, "g"));
                    foods.add(new FoodItem("4", "Soft Toast with Tomato", 100, 60, "g"));
                    foods.add(new FoodItem("5", "Fruit Smoothie", 80, 200, "ml"));
                    foods.add(new FoodItem("6", "Apple Slices", 60, 80, "g"));
                    foods.add(new FoodItem("7", "Soft Pancakes", 120, 80, "g"));
                    foods.add(new FoodItem("8", "Cottage Cheese with Fruit", 100, 100, "g"));
                } else {
                    // Adult overweight
                    foods.add(new FoodItem("1", "Steel-Cut Oatmeal", 150, 100, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt with Berries", 120, 150, "g"));
                    foods.add(new FoodItem("3", "Vegetable Omelet", 180, 150, "g"));
                    foods.add(new FoodItem("4", "Whole Grain Toast with Tomato", 140, 80, "g"));
                    foods.add(new FoodItem("5", "Green Smoothie", 100, 250, "ml"));
                    foods.add(new FoodItem("6", "Apple with Peanut Butter", 160, 100, "g"));
                    foods.add(new FoodItem("7", "Chia Pudding", 130, 150, "g"));
                    foods.add(new FoodItem("8", "Egg White Scramble", 120, 150, "g"));
                }
                break;
                
            case "obese":
                // Very low-calorie, nutrient-dense foods for weight loss
                if (isChild) {
                    // Child obesity management - still need growth nutrients
                    foods.add(new FoodItem("1", "Oatmeal with Cinnamon", 100, 80, "g"));
                    foods.add(new FoodItem("2", "Greek Yogurt (Low-fat)", 80, 120, "g"));
                    foods.add(new FoodItem("3", "Scrambled Eggs (Small)", 80, 80, "g"));
                    foods.add(new FoodItem("4", "Rice Cakes with Avocado", 120, 50, "g"));
                    foods.add(new FoodItem("5", "Fruit Smoothie (Small)", 80, 150, "ml"));
                    foods.add(new FoodItem("6", "Berries", 60, 80, "g"));
                    foods.add(new FoodItem("7", "Cereal (Small)", 100, 80, "g"));
                    foods.add(new FoodItem("8", "Apple Slices", 60, 80, "g"));
                } else if (isAdolescent) {
                    // Adolescent obesity management - careful calorie restriction
                    foods.add(new FoodItem("1", "Plain Oatmeal with Cinnamon", 140, 100, "g"));
                    foods.add(new FoodItem("2", "Non-Fat Greek Yogurt", 100, 150, "g"));
                    foods.add(new FoodItem("3", "Vegetable Scramble", 120, 150, "g"));
                    foods.add(new FoodItem("4", "Rice Cakes with Avocado", 160, 60, "g"));
                    foods.add(new FoodItem("5", "Green Tea Smoothie", 100, 200, "ml"));
                    foods.add(new FoodItem("6", "Berries with Cottage Cheese", 120, 120, "g"));
                    foods.add(new FoodItem("7", "Egg White Omelet", 110, 120, "g"));
                    foods.add(new FoodItem("8", "Cucumber with Hummus", 80, 100, "g"));
                } else if (isElderly) {
                    // Elderly obesity management - softer, very low calorie
                    foods.add(new FoodItem("1", "Soft Oatmeal with Cinnamon", 100, 80, "g"));
                    foods.add(new FoodItem("2", "Non-Fat Greek Yogurt", 60, 120, "g"));
                    foods.add(new FoodItem("3", "Soft Scrambled Eggs", 80, 100, "g"));
                    foods.add(new FoodItem("4", "Rice Cakes (Plain)", 80, 40, "g"));
                    foods.add(new FoodItem("5", "Green Tea", 5, 200, "ml"));
                    foods.add(new FoodItem("6", "Berries", 40, 80, "g"));
                    foods.add(new FoodItem("7", "Soft Pancakes (Small)", 100, 60, "g"));
                    foods.add(new FoodItem("8", "Cucumber Slices", 40, 80, "g"));
                } else {
                    // Adult obese
                    foods.add(new FoodItem("1", "Plain Oatmeal with Cinnamon", 120, 100, "g"));
                    foods.add(new FoodItem("2", "Non-Fat Greek Yogurt", 80, 150, "g"));
                    foods.add(new FoodItem("3", "Vegetable Scramble", 100, 150, "g"));
                    foods.add(new FoodItem("4", "Rice Cakes with Avocado", 140, 60, "g"));
                    foods.add(new FoodItem("5", "Green Tea Smoothie", 80, 200, "ml"));
                    foods.add(new FoodItem("6", "Berries with Cottage Cheese", 100, 120, "g"));
                    foods.add(new FoodItem("7", "Egg White Omelet", 90, 120, "g"));
                    foods.add(new FoodItem("8", "Cucumber with Hummus", 60, 100, "g"));
                }
                break;
                
            default:
                // Fallback to normal weight recommendations
                foods = getPersonalizedBreakfastFoods(maxCalories, "normal weight", "adult", userProfile);
                break;
        }
        
        return filterByCalories(foods, maxCalories);
    }
    
    /**
     * Get personalized lunch foods based on BMI category and age group
     */
    private List<FoodItem> getPersonalizedLunchFoods(int maxCalories, String bmiCategory, String ageGroup, UserProfile userProfile) {
        List<FoodItem> foods = new ArrayList<>();
        
        switch (bmiCategory.toLowerCase()) {
            case "underweight":
                // High-calorie, protein-rich foods for weight gain
                foods.add(new FoodItem("1", "Grilled Chicken with Rice", 450, 200, "g"));
                foods.add(new FoodItem("2", "Salmon with Sweet Potato", 420, 180, "g"));
                foods.add(new FoodItem("3", "Turkey Sandwich with Avocado", 380, 200, "g"));
                foods.add(new FoodItem("4", "Quinoa Bowl with Beans", 400, 200, "g"));
                foods.add(new FoodItem("5", "Pasta with Meat Sauce", 480, 250, "g"));
                foods.add(new FoodItem("6", "Tuna Salad with Olive Oil", 350, 180, "g"));
                foods.add(new FoodItem("7", "Beef Stir Fry with Rice", 420, 200, "g"));
                foods.add(new FoodItem("8", "Lentil Curry with Rice", 380, 200, "g"));
                break;
                
            case "normal weight":
                // Balanced, nutritious foods to maintain health
                foods.add(new FoodItem("1", "Grilled Chicken Salad", 280, 200, "g"));
                foods.add(new FoodItem("2", "Quinoa Bowl with Vegetables", 320, 180, "g"));
                foods.add(new FoodItem("3", "Turkey Wrap with Hummus", 300, 200, "g"));
                foods.add(new FoodItem("4", "Salmon with Roasted Vegetables", 350, 180, "g"));
                foods.add(new FoodItem("5", "Vegetable Soup with Bread", 250, 300, "g"));
                foods.add(new FoodItem("6", "Brown Rice with Beans", 280, 200, "g"));
                foods.add(new FoodItem("7", "Mixed Green Salad", 200, 250, "g"));
                foods.add(new FoodItem("8", "Baked Cod with Quinoa", 300, 180, "g"));
                break;
                
            case "overweight":
                // Lower-calorie, high-fiber foods for weight management
                foods.add(new FoodItem("1", "Grilled Chicken Salad", 220, 200, "g"));
                foods.add(new FoodItem("2", "Vegetable Soup", 150, 300, "g"));
                foods.add(new FoodItem("3", "Turkey Lettuce Wraps", 200, 150, "g"));
                foods.add(new FoodItem("4", "Baked Fish with Vegetables", 250, 180, "g"));
                foods.add(new FoodItem("5", "Quinoa Salad", 200, 150, "g"));
                foods.add(new FoodItem("6", "Lentil Soup", 180, 250, "g"));
                foods.add(new FoodItem("7", "Grilled Vegetables", 120, 200, "g"));
                foods.add(new FoodItem("8", "Tuna Salad (Light)", 180, 150, "g"));
                break;
                
            case "obese":
                // Very low-calorie, nutrient-dense foods for weight loss
                foods.add(new FoodItem("1", "Green Salad with Grilled Chicken", 180, 200, "g"));
                foods.add(new FoodItem("2", "Vegetable Soup (Clear)", 100, 300, "g"));
                foods.add(new FoodItem("3", "Turkey Lettuce Wraps", 150, 120, "g"));
                foods.add(new FoodItem("4", "Baked White Fish", 120, 120, "g"));
                foods.add(new FoodItem("5", "Steamed Vegetables", 80, 200, "g"));
                foods.add(new FoodItem("6", "Lentil Soup (Light)", 120, 200, "g"));
                foods.add(new FoodItem("7", "Grilled Chicken Breast", 160, 120, "g"));
                foods.add(new FoodItem("8", "Cucumber Salad", 60, 150, "g"));
                break;
                
            default:
                foods = getPersonalizedLunchFoods(maxCalories, "normal weight", "adult", userProfile);
                break;
        }
        
        return filterByCalories(foods, maxCalories);
    }
    
    /**
     * Get personalized dinner foods based on BMI category and age group
     */
    private List<FoodItem> getPersonalizedDinnerFoods(int maxCalories, String bmiCategory, String ageGroup, UserProfile userProfile) {
        List<FoodItem> foods = new ArrayList<>();
        
        switch (bmiCategory.toLowerCase()) {
            case "underweight":
                // High-calorie, protein-rich foods for weight gain
                foods.add(new FoodItem("1", "Baked Salmon with Rice", 480, 200, "g"));
                foods.add(new FoodItem("2", "Grilled Steak with Potatoes", 520, 250, "g"));
                foods.add(new FoodItem("3", "Pasta with Meatballs", 450, 300, "g"));
                foods.add(new FoodItem("4", "Chicken Thighs with Quinoa", 420, 200, "g"));
                foods.add(new FoodItem("5", "Beef Stir Fry with Noodles", 400, 250, "g"));
                foods.add(new FoodItem("6", "Lamb Chops with Vegetables", 450, 200, "g"));
                foods.add(new FoodItem("7", "Pork Tenderloin with Rice", 380, 200, "g"));
                foods.add(new FoodItem("8", "Fish Curry with Rice", 420, 250, "g"));
                break;
                
            case "normal weight":
                // Balanced, nutritious foods to maintain health
                foods.add(new FoodItem("1", "Baked Salmon with Vegetables", 350, 200, "g"));
                foods.add(new FoodItem("2", "Grilled Chicken with Quinoa", 320, 180, "g"));
                foods.add(new FoodItem("3", "Turkey Meatballs with Pasta", 380, 250, "g"));
                foods.add(new FoodItem("4", "Vegetable Stir Fry with Rice", 300, 200, "g"));
                foods.add(new FoodItem("5", "Baked Cod with Sweet Potato", 320, 200, "g"));
                foods.add(new FoodItem("6", "Lentil Curry with Rice", 280, 200, "g"));
                foods.add(new FoodItem("7", "Grilled Fish with Vegetables", 300, 180, "g"));
                foods.add(new FoodItem("8", "Chicken Stir Fry", 280, 200, "g"));
                break;
                
            case "overweight":
                // Lower-calorie, high-fiber foods for weight management
                foods.add(new FoodItem("1", "Baked Salmon with Vegetables", 280, 200, "g"));
                foods.add(new FoodItem("2", "Grilled Chicken Breast", 220, 150, "g"));
                foods.add(new FoodItem("3", "Turkey Meatballs (Lean)", 250, 200, "g"));
                foods.add(new FoodItem("4", "Vegetable Stir Fry", 180, 200, "g"));
                foods.add(new FoodItem("5", "Baked White Fish", 200, 150, "g"));
                foods.add(new FoodItem("6", "Lentil Soup", 200, 250, "g"));
                foods.add(new FoodItem("7", "Grilled Vegetables", 120, 200, "g"));
                foods.add(new FoodItem("8", "Chicken and Vegetable Soup", 180, 250, "g"));
                break;
                
            case "obese":
                // Very low-calorie, nutrient-dense foods for weight loss
                foods.add(new FoodItem("1", "Baked White Fish", 150, 120, "g"));
                foods.add(new FoodItem("2", "Grilled Chicken Breast", 180, 120, "g"));
                foods.add(new FoodItem("3", "Turkey Breast (Skinless)", 160, 120, "g"));
                foods.add(new FoodItem("4", "Steamed Vegetables", 80, 200, "g"));
                foods.add(new FoodItem("5", "Baked Cod", 140, 120, "g"));
                foods.add(new FoodItem("6", "Vegetable Soup (Clear)", 100, 300, "g"));
                foods.add(new FoodItem("7", "Grilled Fish", 160, 120, "g"));
                foods.add(new FoodItem("8", "Chicken and Vegetable Soup", 120, 200, "g"));
                break;
                
            default:
                foods = getPersonalizedDinnerFoods(maxCalories, "normal weight", "adult", userProfile);
                break;
        }
        
        return filterByCalories(foods, maxCalories);
    }
    
    /**
     * Get personalized snack foods based on BMI category and age group
     */
    private List<FoodItem> getPersonalizedSnacksFoods(int maxCalories, String bmiCategory, String ageGroup, UserProfile userProfile) {
        List<FoodItem> foods = new ArrayList<>();
        
        switch (bmiCategory.toLowerCase()) {
            case "underweight":
                // High-calorie, nutrient-dense snacks for weight gain
                foods.add(new FoodItem("1", "Mixed Nuts", 200, 40, "g"));
                foods.add(new FoodItem("2", "Peanut Butter with Apple", 250, 120, "g"));
                foods.add(new FoodItem("3", "Cheese and Crackers", 220, 80, "g"));
                foods.add(new FoodItem("4", "Trail Mix", 280, 50, "g"));
                foods.add(new FoodItem("5", "Greek Yogurt with Granola", 200, 150, "g"));
                foods.add(new FoodItem("6", "Avocado Toast", 180, 80, "g"));
                foods.add(new FoodItem("7", "Protein Smoothie", 300, 250, "ml"));
                foods.add(new FoodItem("8", "Dark Chocolate", 150, 30, "g"));
                break;
                
            case "normal weight":
                // Balanced, nutritious snacks to maintain health
                foods.add(new FoodItem("1", "Apple Slices with Almonds", 180, 120, "g"));
                foods.add(new FoodItem("2", "Greek Yogurt with Berries", 150, 150, "g"));
                foods.add(new FoodItem("3", "Hummus with Vegetables", 120, 100, "g"));
                foods.add(new FoodItem("4", "Mixed Nuts", 160, 30, "g"));
                foods.add(new FoodItem("5", "Banana with Peanut Butter", 200, 100, "g"));
                foods.add(new FoodItem("6", "Cheese and Crackers", 180, 60, "g"));
                foods.add(new FoodItem("7", "Berries", 80, 100, "g"));
                foods.add(new FoodItem("8", "Rice Cakes with Avocado", 140, 60, "g"));
                break;
                
            case "overweight":
                // Lower-calorie, high-fiber snacks for weight management
                foods.add(new FoodItem("1", "Apple Slices", 80, 150, "g"));
                foods.add(new FoodItem("2", "Greek Yogurt (Non-fat)", 100, 150, "g"));
                foods.add(new FoodItem("3", "Carrot Sticks with Hummus", 100, 100, "g"));
                foods.add(new FoodItem("4", "Mixed Nuts (Small Portion)", 120, 20, "g"));
                foods.add(new FoodItem("5", "Berries", 60, 100, "g"));
                foods.add(new FoodItem("6", "Rice Cakes", 50, 20, "g"));
                foods.add(new FoodItem("7", "Cucumber Slices", 15, 100, "g"));
                foods.add(new FoodItem("8", "Air-Popped Popcorn", 80, 30, "g"));
                break;
                
            case "obese":
                // Very low-calorie, nutrient-dense snacks for weight loss
                foods.add(new FoodItem("1", "Celery Sticks", 10, 100, "g"));
                foods.add(new FoodItem("2", "Cucumber Slices", 15, 100, "g"));
                foods.add(new FoodItem("3", "Berries", 40, 80, "g"));
                foods.add(new FoodItem("4", "Greek Yogurt (Non-fat)", 80, 100, "g"));
                foods.add(new FoodItem("5", "Carrot Sticks", 25, 100, "g"));
                foods.add(new FoodItem("6", "Rice Cakes (Plain)", 35, 15, "g"));
                foods.add(new FoodItem("7", "Apple Slices", 60, 100, "g"));
                foods.add(new FoodItem("8", "Green Tea", 5, 250, "ml"));
                break;
                
            default:
                foods = getPersonalizedSnacksFoods(maxCalories, "normal weight", "adult", userProfile);
                break;
        }
        
        return filterByCalories(foods, maxCalories);
    }
    
    /**
     * Get personalized general foods based on BMI category and age group
     */
    private List<FoodItem> getPersonalizedGeneralFoods(String query, int maxCalories, String bmiCategory, String ageGroup, UserProfile userProfile) {
        List<FoodItem> foods = new ArrayList<>();
        
        // Base foods that work for all BMI categories, with adjustments
        switch (bmiCategory.toLowerCase()) {
            case "underweight":
                foods.add(new FoodItem("1", "Grilled Chicken (Extra Portion)", 300, 150, "g"));
                foods.add(new FoodItem("2", "Brown Rice (Extra Portion)", 200, 150, "g"));
                foods.add(new FoodItem("3", "Steamed Broccoli with Butter", 80, 100, "g"));
                foods.add(new FoodItem("4", "Salmon Fillet (Large)", 350, 150, "g"));
                foods.add(new FoodItem("5", "Quinoa with Nuts", 180, 120, "g"));
                foods.add(new FoodItem("6", "Mixed Vegetables with Oil", 60, 100, "g"));
                break;
                
            case "normal weight":
                foods.add(new FoodItem("1", "Grilled Chicken", 200, 100, "g"));
                foods.add(new FoodItem("2", "Brown Rice", 110, 100, "g"));
                foods.add(new FoodItem("3", "Steamed Broccoli", 35, 100, "g"));
                foods.add(new FoodItem("4", "Salmon Fillet", 250, 100, "g"));
                foods.add(new FoodItem("5", "Quinoa", 120, 100, "g"));
                foods.add(new FoodItem("6", "Mixed Vegetables", 25, 100, "g"));
                break;
                
            case "overweight":
                foods.add(new FoodItem("1", "Grilled Chicken Breast", 160, 100, "g"));
                foods.add(new FoodItem("2", "Brown Rice (Small Portion)", 80, 80, "g"));
                foods.add(new FoodItem("3", "Steamed Broccoli", 30, 100, "g"));
                foods.add(new FoodItem("4", "Baked White Fish", 180, 100, "g"));
                foods.add(new FoodItem("5", "Quinoa (Small Portion)", 90, 80, "g"));
                foods.add(new FoodItem("6", "Mixed Vegetables", 20, 100, "g"));
                break;
                
            case "obese":
                foods.add(new FoodItem("1", "Grilled Chicken Breast (Skinless)", 140, 100, "g"));
                foods.add(new FoodItem("2", "Brown Rice (Very Small)", 60, 60, "g"));
                foods.add(new FoodItem("3", "Steamed Broccoli", 25, 100, "g"));
                foods.add(new FoodItem("4", "Baked White Fish", 120, 80, "g"));
                foods.add(new FoodItem("5", "Quinoa (Very Small)", 70, 60, "g"));
                foods.add(new FoodItem("6", "Mixed Vegetables", 15, 100, "g"));
                break;
                
            default:
                foods = getPersonalizedGeneralFoods(query, maxCalories, "normal weight", "adult", userProfile);
                break;
        }
        
        return filterByCalories(foods, maxCalories);
    }


    // Use Gemini AI for personalized food recommendations
    private void makeFatSecretAPICall(String method, String query, int maxCalories, FoodSearchCallback callback) {
        Log.d(TAG, "Using Gemini AI for personalized food recommendations: " + query);
        
        // This method is now used for both searchFoods and getPersonalizedFoods
        // We'll use Gemini for personalized recommendations
        mainHandler.post(() -> callback.onSuccess(new ArrayList<>()));
    }
    
    private String generateNonce() {
        return String.valueOf(System.nanoTime());
    }
    
    private String generateOAuthSignature(String method, String query, String timestamp, String nonce) {
        try {
            // Build parameter string (must be sorted alphabetically)
            java.util.Map<String, String> params = new java.util.TreeMap<>();
            params.put("format", "json");
            params.put("max_results", "20");
            params.put("method", method);
            params.put("oauth_consumer_key", CLIENT_ID);
            params.put("oauth_nonce", nonce);
            params.put("oauth_signature_method", "HMAC-SHA1");
            params.put("oauth_timestamp", timestamp);
            params.put("oauth_version", "1.0");
            params.put("search_expression", query);
            
            // Create parameter string with proper encoding
            StringBuilder paramString = new StringBuilder();
            for (java.util.Map.Entry<String, String> entry : params.entrySet()) {
                if (paramString.length() > 0) {
                    paramString.append("&");
                }
                paramString.append(percentEncode(entry.getKey()))
                          .append("=")
                          .append(percentEncode(entry.getValue()));
            }
            
            // OAuth 1.0 signature base string
            String baseString = "GET&" + 
                percentEncode(BASE_URL) + "&" +
                percentEncode(paramString.toString());
            
            Log.d(TAG, "OAuth Base String: " + baseString);
            
            // HMAC-SHA1 signature
            javax.crypto.Mac mac = javax.crypto.Mac.getInstance("HmacSHA1");
            javax.crypto.spec.SecretKeySpec secretKeySpec = new javax.crypto.spec.SecretKeySpec(
                (CLIENT_SECRET + "&").getBytes("UTF-8"), "HmacSHA1");
            mac.init(secretKeySpec);
            
            byte[] signatureBytes = mac.doFinal(baseString.getBytes("UTF-8"));
            String signature = java.util.Base64.getEncoder().encodeToString(signatureBytes);
            
            Log.d(TAG, "Generated OAuth Signature: " + signature);
            return signature;
            
        } catch (Exception e) {
            Log.e(TAG, "Error generating OAuth signature: " + e.getMessage());
            e.printStackTrace();
            return "";
        }
    }
    
    private String percentEncode(String value) {
        try {
            return java.net.URLEncoder.encode(value, "UTF-8")
                .replace("+", "%20")
                .replace("*", "%2A")
                .replace("%7E", "~");
        } catch (Exception e) {
            Log.e(TAG, "Error percent encoding: " + e.getMessage());
            return value;
        }
    }
    
    private List<FoodItem> parseFatSecretResponse(String responseBody, int maxCalories) {
        try {
            Log.e(TAG, "=== FULL FATSECRET API RESPONSE ===");
            Log.e(TAG, "Response Body: " + responseBody);
            Log.e(TAG, "Response Length: " + responseBody.length());
            
            JSONObject jsonResponse = new JSONObject(responseBody);
            JSONObject foodsObject = jsonResponse.optJSONObject("foods");
            
            if (foodsObject == null) {
                Log.w(TAG, "No 'foods' object in API response");
                // Log all available keys
                java.util.Iterator<String> keys = jsonResponse.keys();
                StringBuilder keyList = new StringBuilder();
                while (keys.hasNext()) {
                    keyList.append(keys.next()).append(", ");
                }
                Log.w(TAG, "Available keys in response: " + keyList.toString());
                return new ArrayList<>();
            }
            
            JSONArray foodArray = foodsObject.optJSONArray("food");
            if (foodArray == null) {
                Log.w(TAG, "No 'food' array in API response");
                return new ArrayList<>();
            }
            
            List<FoodItem> foods = new ArrayList<>();
            for (int i = 0; i < foodArray.length() && i < 20; i++) {
                try {
                    JSONObject food = foodArray.getJSONObject(i);
                    String foodId = food.optString("food_id", "");
                    String foodName = food.optString("food_name", "");
                    
                    // Get nutrition info
                    JSONObject nutrition = food.optJSONObject("food_description");
                    if (nutrition != null) {
                        int calories = nutrition.optInt("calories", 0);
                        
                        // Only include foods within calorie limit
                        if (calories <= maxCalories && calories > 0) {
                            String servingSize = nutrition.optString("serving_description", "100g");
                            foods.add(new FoodItem(foodId, foodName, calories, 100, servingSize));
                        }
                    }
                } catch (Exception e) {
                    Log.w(TAG, "Error parsing food item " + i + ": " + e.getMessage());
                }
            }
            
            Log.d(TAG, "Parsed " + foods.size() + " foods from FatSecret API");
            return foods;
            
        } catch (Exception e) {
            Log.e(TAG, "Error parsing FatSecret response: " + e.getMessage());
            return new ArrayList<>();
        }
    }
    
    private android.os.Handler mainHandler = new android.os.Handler(android.os.Looper.getMainLooper());
}
