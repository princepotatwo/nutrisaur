package com.example.nutrisaur11;

import android.content.Context;
import android.util.Log;
import java.util.*;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import okhttp3.*;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;
import java.io.IOException;

/**
 * Optimized Food Activity Integration
 * Handles food recommendations with substitutions and details in just 2 API calls
 */
public class FoodActivityIntegration {
    private static final String TAG = "FoodActivityIntegration";
    
    // Gemini API configuration
    private static final String GEMINI_API_KEY = "AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM";
    private static final String GEMINI_TEXT_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" + GEMINI_API_KEY;
    
    private Context context;
    private ExecutorService executorService;
    
    public interface FoodLoadingCallback {
        void onFoodsLoaded(List<FoodRecommendation> traditionalFoods, 
                          List<FoodRecommendation> healthyFoods,
                          List<FoodRecommendation> internationalFoods,
                          List<FoodRecommendation> budgetFoods);
        void onError(String error);
    }
    
    public interface FoodDetailsCallback {
        void onFoodDetailsLoaded(FoodDetails foodDetails);
        void onError(String error);
    }
    
    public FoodActivityIntegration(Context context) {
        this.context = context;
        this.executorService = Executors.newFixedThreadPool(4);
    }
    
    /**
     * Load malnutrition recovery foods with substitutions for all categories
     * This is the main method that loads everything in 2 API calls
     */
    public static void loadMalnutritionRecoveryFoods(Context context,
                                                    String userAge, String userSex, String userBMI, 
                                                    String userHealthConditions, String userBudgetLevel, 
                                                    String userAllergies, String userDietPrefs, 
                                                    String userPregnancyStatus,
                                                   List<FoodRecommendation> traditionalFoods,
                                                   List<FoodRecommendation> healthyFoods,
                                                   List<FoodRecommendation> internationalFoods,
                                                   List<FoodRecommendation> budgetFoods,
                                                   HorizontalFoodAdapter traditionalAdapter,
                                                   HorizontalFoodAdapter healthyAdapter,
                                                   HorizontalFoodAdapter internationalAdapter,
                                                   HorizontalFoodAdapter budgetAdapter) {
        
        FoodActivityIntegration integration = new FoodActivityIntegration(context);
        integration.loadFoodsWithSubstitutions(userAge, userSex, userBMI, userHealthConditions, 
                                             userBudgetLevel, userAllergies, userDietPrefs, 
                                             userPregnancyStatus, traditionalFoods, healthyFoods, 
                                             internationalFoods, budgetFoods, traditionalAdapter, 
                                             healthyAdapter, internationalAdapter, budgetAdapter);
    }
    
    private void loadFoodsWithSubstitutions(String userAge, String userSex, String userBMI, 
                                          String userHealthConditions, String userBudgetLevel,
                                          String userAllergies, String userDietPrefs, 
                                          String userPregnancyStatus,
                                          List<FoodRecommendation> traditionalFoods,
                                          List<FoodRecommendation> healthyFoods,
                                          List<FoodRecommendation> internationalFoods,
                                          List<FoodRecommendation> budgetFoods,
                                          HorizontalFoodAdapter traditionalAdapter,
                                          HorizontalFoodAdapter healthyAdapter,
                                          HorizontalFoodAdapter internationalAdapter,
                                          HorizontalFoodAdapter budgetAdapter) {
        
        executorService.execute(() -> {
            try {
                Log.d(TAG, "Starting optimized food loading with substitutions");
                
                // First API call: Get main food recommendations for all categories
                String mainPrompt = FoodActivityIntegrationMethods.buildMainFoodPrompt(userAge, userSex, userBMI, userHealthConditions, 
                                                      userBudgetLevel, userAllergies, userDietPrefs, 
                                                      userPregnancyStatus);
                
                Map<String, List<FoodRecommendation>> mainFoods = FoodActivityIntegrationMethods.callGeminiForMainFoods(mainPrompt);
                
                if (mainFoods != null && !mainFoods.isEmpty()) {
                    // Update food lists
                    traditionalFoods.clear();
                    healthyFoods.clear();
                    internationalFoods.clear();
                    budgetFoods.clear();
                    
                    traditionalFoods.addAll(mainFoods.getOrDefault("traditional", new ArrayList<>()));
                    healthyFoods.addAll(mainFoods.getOrDefault("healthy", new ArrayList<>()));
                    internationalFoods.addAll(mainFoods.getOrDefault("international", new ArrayList<>()));
                    budgetFoods.addAll(mainFoods.getOrDefault("budget", new ArrayList<>()));
                    
                    // Update adapters
                    if (traditionalAdapter != null) {
                        traditionalAdapter.setLoading(false);
                        traditionalAdapter.notifyDataSetChanged();
                    }
                    if (healthyAdapter != null) {
                        healthyAdapter.setLoading(false);
                        healthyAdapter.notifyDataSetChanged();
                    }
                    if (internationalAdapter != null) {
                        internationalAdapter.setLoading(false);
                        internationalAdapter.notifyDataSetChanged();
                    }
                    if (budgetAdapter != null) {
                        budgetAdapter.setLoading(false);
                        budgetAdapter.notifyDataSetChanged();
                    }
                    
                    Log.d(TAG, "Main foods loaded successfully");
                    
                    // Second API call: Get substitutions for all foods
                    loadSubstitutionsForAllFoods(traditionalFoods, healthyFoods, internationalFoods, 
                                               budgetFoods, userAge, userSex, userBMI, userHealthConditions, 
                                               userBudgetLevel, userAllergies, userDietPrefs, userPregnancyStatus);
                } else {
                    Log.w(TAG, "No main foods loaded, using fallback");
                    // Run on main thread to avoid UI threading issues
                    if (context instanceof android.app.Activity) {
                        ((android.app.Activity) context).runOnUiThread(() -> {
                            loadFallbackFoods(traditionalFoods, healthyFoods, internationalFoods, budgetFoods,
                                            traditionalAdapter, healthyAdapter, internationalAdapter, budgetAdapter);
                        });
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error loading foods with substitutions: " + e.getMessage());
                // Run on main thread to avoid UI threading issues
                if (context instanceof android.app.Activity) {
                    ((android.app.Activity) context).runOnUiThread(() -> {
                        loadFallbackFoods(traditionalFoods, healthyFoods, internationalFoods, budgetFoods,
                                        traditionalAdapter, healthyAdapter, internationalAdapter, budgetAdapter);
                    });
                }
            }
        });
    }
    
    /**
     * Load substitutions for all foods
     */
    private void loadSubstitutionsForAllFoods(List<FoodRecommendation> traditionalFoods, 
                                            List<FoodRecommendation> healthyFoods,
                                            List<FoodRecommendation> internationalFoods,
                                            List<FoodRecommendation> budgetFoods,
                                            String userAge, String userSex, String userBMI,
                                            String userHealthConditions, String userBudgetLevel,
                                            String userAllergies, String userDietPrefs, String userPregnancyStatus) {
        // This method would load substitutions for all foods
        // For now, we'll just log that substitutions are being loaded
        Log.d(TAG, "Loading substitutions for all foods...");
        
        // TODO: Implement substitution loading logic
        // This would typically make API calls to get substitution data
        // and update the food lists with substitution information
    }
    
    /**
     * Load fallback foods when main foods fail to load
     */
    private void loadFallbackFoods(List<FoodRecommendation> traditionalFoods,
                                 List<FoodRecommendation> healthyFoods,
                                 List<FoodRecommendation> internationalFoods,
                                 List<FoodRecommendation> budgetFoods,
                                 HorizontalFoodAdapter traditionalAdapter,
                                 HorizontalFoodAdapter healthyAdapter,
                                 HorizontalFoodAdapter internationalAdapter,
                                 HorizontalFoodAdapter budgetAdapter) {
        Log.d(TAG, "Loading fallback foods...");
        
        // Create some basic fallback foods
        if (traditionalFoods.isEmpty()) {
            traditionalFoods.add(new FoodRecommendation("Rice", 200, 4.0, 0.5, 45.0, "1 cup", "Traditional", "Basic staple food", "https://example.com/rice.jpg"));
        }
        if (healthyFoods.isEmpty()) {
            healthyFoods.add(new FoodRecommendation("Vegetables", 50, 2.0, 0.3, 10.0, "1 cup", "Healthy", "Fresh vegetables", "https://example.com/vegetables.jpg"));
        }
        if (internationalFoods.isEmpty()) {
            internationalFoods.add(new FoodRecommendation("Pasta", 220, 8.0, 1.2, 44.0, "1 cup", "International", "International cuisine", "https://example.com/pasta.jpg"));
        }
        if (budgetFoods.isEmpty()) {
            budgetFoods.add(new FoodRecommendation("Bread", 80, 3.0, 1.0, 15.0, "1 slice", "Budget", "Affordable option", "https://example.com/bread.jpg"));
        }
        
        // Update adapters
        if (traditionalAdapter != null) {
            traditionalAdapter.notifyDataSetChanged();
        }
        if (healthyAdapter != null) {
            healthyAdapter.notifyDataSetChanged();
        }
        if (internationalAdapter != null) {
            internationalAdapter.notifyDataSetChanged();
        }
        if (budgetAdapter != null) {
            budgetAdapter.notifyDataSetChanged();
        }
    }
    
    public void shutdown() {
        if (executorService != null) {
            executorService.shutdown();
        }
    }
}