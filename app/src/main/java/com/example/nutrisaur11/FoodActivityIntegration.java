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
                                                   List<FoodRecommendation> breakfastFoods,
                                                   List<FoodRecommendation> lunchFoods,
                                                   List<FoodRecommendation> dinnerFoods,
                                                   List<FoodRecommendation> snackFoods,
                                                   HorizontalFoodAdapter breakfastAdapter,
                                                   HorizontalFoodAdapter lunchAdapter,
                                                   HorizontalFoodAdapter dinnerAdapter,
                                                   HorizontalFoodAdapter snackAdapter) {
        
        FoodActivityIntegration integration = new FoodActivityIntegration(context);
        integration.loadFoodsWithSubstitutions(userAge, userSex, userBMI, userHealthConditions, 
                                             userBudgetLevel, userAllergies, userDietPrefs, 
                                             userPregnancyStatus, breakfastFoods, lunchFoods, 
                                             dinnerFoods, snackFoods, breakfastAdapter, 
                                             lunchAdapter, dinnerAdapter, snackAdapter);
    }
    
    /**
     * Load malnutrition recovery foods with comprehensive screening data
     * This method uses all available user data for highly personalized recommendations
     */
    public static void loadMalnutritionRecoveryFoodsWithScreening(Context context,
                                                                String userAge, String userSex, String userBMI, 
                                                                String userHeight, String userWeight, String userHealthConditions, 
                                                                String userActivityLevel, String userBudgetLevel, String userDietaryRestrictions, 
                                                                String userAllergies, String userDietPrefs, String userAvoidFoods, 
                                                                String userRiskScore, String userBarangay, String userIncome, 
                                                                String userPregnancyStatus, String screeningAnswers,
                                                               List<FoodRecommendation> breakfastFoods,
                                                               List<FoodRecommendation> lunchFoods,
                                                               List<FoodRecommendation> dinnerFoods,
                                                               List<FoodRecommendation> snackFoods,
                                                               HorizontalFoodAdapter breakfastAdapter,
                                                               HorizontalFoodAdapter lunchAdapter,
                                                               HorizontalFoodAdapter dinnerAdapter,
                                                               HorizontalFoodAdapter snackAdapter) {
        
        FoodActivityIntegration integration = new FoodActivityIntegration(context);
        integration.loadFoodsWithComprehensiveScreening(userAge, userSex, userBMI, userHeight, userWeight, 
                                                       userHealthConditions, userActivityLevel, userBudgetLevel, 
                                                       userDietaryRestrictions, userAllergies, userDietPrefs, 
                                                       userAvoidFoods, userRiskScore, userBarangay, userIncome, 
                                                       userPregnancyStatus, screeningAnswers, breakfastFoods, 
                                                       lunchFoods, dinnerFoods, snackFoods, breakfastAdapter, 
                                                       lunchAdapter, dinnerAdapter, snackAdapter);
    }
    
    private void loadFoodsWithComprehensiveScreening(String userAge, String userSex, String userBMI, 
                                                    String userHeight, String userWeight, String userHealthConditions, 
                                                    String userActivityLevel, String userBudgetLevel, String userDietaryRestrictions, 
                                                    String userAllergies, String userDietPrefs, String userAvoidFoods, 
                                                    String userRiskScore, String userBarangay, String userIncome, 
                                                    String userPregnancyStatus, String screeningAnswers,
                                                    List<FoodRecommendation> breakfastFoods,
                                                    List<FoodRecommendation> lunchFoods,
                                                    List<FoodRecommendation> dinnerFoods,
                                                    List<FoodRecommendation> snackFoods,
                                                    HorizontalFoodAdapter breakfastAdapter,
                                                    HorizontalFoodAdapter lunchAdapter,
                                                    HorizontalFoodAdapter dinnerAdapter,
                                                    HorizontalFoodAdapter snackAdapter) {
        
        executorService.execute(() -> {
            try {
                Log.d(TAG, "Starting comprehensive food loading with screening data");
                
                // First API call: Get main food recommendations for all categories with comprehensive data
                String mainPrompt = FoodActivityIntegrationMethods.buildComprehensiveFoodPrompt(
                    userAge, userSex, userBMI, userHeight, userWeight, userHealthConditions, 
                    userActivityLevel, userBudgetLevel, userDietaryRestrictions, userAllergies, 
                    userDietPrefs, userAvoidFoods, userRiskScore, userBarangay, userIncome, 
                    userPregnancyStatus, screeningAnswers
                );
                
                Map<String, List<FoodRecommendation>> mainFoods = FoodActivityIntegrationMethods.callGeminiForMainFoods(mainPrompt);
                
                if (mainFoods != null && !mainFoods.isEmpty()) {
                    // Update food lists
                    breakfastFoods.clear();
                    lunchFoods.clear();
                    dinnerFoods.clear();
                    snackFoods.clear();
                    
                    breakfastFoods.addAll(mainFoods.getOrDefault("breakfast", new ArrayList<>()));
                    lunchFoods.addAll(mainFoods.getOrDefault("lunch", new ArrayList<>()));
                    dinnerFoods.addAll(mainFoods.getOrDefault("dinner", new ArrayList<>()));
                    snackFoods.addAll(mainFoods.getOrDefault("snacks", new ArrayList<>()));
                    
                    Log.d(TAG, "Food lists updated with comprehensive data - Breakfast: " + breakfastFoods.size() + 
                          ", Lunch: " + lunchFoods.size() + ", Dinner: " + dinnerFoods.size() + 
                          ", Snacks: " + snackFoods.size());
                    
                    // Update adapters on main thread
                    if (context instanceof android.app.Activity) {
                        ((android.app.Activity) context).runOnUiThread(() -> {
                            Log.d(TAG, "Updating adapters on UI thread with comprehensive data");
                            if (breakfastAdapter != null) {
                                Log.d(TAG, "Updating breakfast adapter with " + breakfastFoods.size() + " items");
                                breakfastAdapter.updateFoods(breakfastFoods);
                            }
                            if (lunchAdapter != null) {
                                Log.d(TAG, "Updating lunch adapter with " + lunchFoods.size() + " items");
                                lunchAdapter.updateFoods(lunchFoods);
                            }
                            if (dinnerAdapter != null) {
                                Log.d(TAG, "Updating dinner adapter with " + dinnerFoods.size() + " items");
                                dinnerAdapter.updateFoods(dinnerFoods);
                            }
                            if (snackAdapter != null) {
                                Log.d(TAG, "Updating snack adapter with " + snackFoods.size() + " items");
                                snackAdapter.updateFoods(snackFoods);
                            }
                        });
                    }
                    
                    Log.d(TAG, "Comprehensive foods loaded successfully");
                    
                    // Second API call: Get substitutions for all foods
                    loadSubstitutionsForAllFoods(breakfastFoods, lunchFoods, dinnerFoods, 
                                               snackFoods, userAge, userSex, userBMI, userHealthConditions, 
                                               userBudgetLevel, userAllergies, userDietPrefs, userPregnancyStatus);
                } else {
                    Log.w(TAG, "No comprehensive foods loaded, using fallback");
                    // Run on main thread to avoid UI threading issues
                    if (context instanceof android.app.Activity) {
                        ((android.app.Activity) context).runOnUiThread(() -> {
                            loadFallbackFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods,
                                            breakfastAdapter, lunchAdapter, dinnerAdapter, snackAdapter);
                        });
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error loading comprehensive foods: " + e.getMessage());
                // Run on main thread to avoid UI threading issues
                if (context instanceof android.app.Activity) {
                    ((android.app.Activity) context).runOnUiThread(() -> {
                        loadFallbackFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods,
                                        breakfastAdapter, lunchAdapter, dinnerAdapter, snackAdapter);
                    });
                }
            }
        });
    }
    
    private void loadFoodsWithSubstitutions(String userAge, String userSex, String userBMI, 
                                          String userHealthConditions, String userBudgetLevel,
                                          String userAllergies, String userDietPrefs, 
                                          String userPregnancyStatus,
                                          List<FoodRecommendation> breakfastFoods,
                                          List<FoodRecommendation> lunchFoods,
                                          List<FoodRecommendation> dinnerFoods,
                                          List<FoodRecommendation> snackFoods,
                                          HorizontalFoodAdapter breakfastAdapter,
                                          HorizontalFoodAdapter lunchAdapter,
                                          HorizontalFoodAdapter dinnerAdapter,
                                          HorizontalFoodAdapter snackAdapter) {
        
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
                    breakfastFoods.clear();
                    lunchFoods.clear();
                    dinnerFoods.clear();
                    snackFoods.clear();
                    
                    breakfastFoods.addAll(mainFoods.getOrDefault("breakfast", new ArrayList<>()));
                    lunchFoods.addAll(mainFoods.getOrDefault("lunch", new ArrayList<>()));
                    dinnerFoods.addAll(mainFoods.getOrDefault("dinner", new ArrayList<>()));
                    snackFoods.addAll(mainFoods.getOrDefault("snacks", new ArrayList<>()));
                    
                    Log.d(TAG, "Food lists updated - Breakfast: " + breakfastFoods.size() + 
                          ", Lunch: " + lunchFoods.size() + ", Dinner: " + dinnerFoods.size() + 
                          ", Snacks: " + snackFoods.size());
                    
                    // Update adapters on main thread
                    if (context instanceof android.app.Activity) {
                        ((android.app.Activity) context).runOnUiThread(() -> {
                            Log.d(TAG, "Updating adapters on UI thread");
                            if (breakfastAdapter != null) {
                                Log.d(TAG, "Updating breakfast adapter with " + breakfastFoods.size() + " items");
                                breakfastAdapter.updateFoods(breakfastFoods);
                            }
                            if (lunchAdapter != null) {
                                Log.d(TAG, "Updating lunch adapter with " + lunchFoods.size() + " items");
                                lunchAdapter.updateFoods(lunchFoods);
                            }
                            if (dinnerAdapter != null) {
                                Log.d(TAG, "Updating dinner adapter with " + dinnerFoods.size() + " items");
                                dinnerAdapter.updateFoods(dinnerFoods);
                            }
                            if (snackAdapter != null) {
                                Log.d(TAG, "Updating snack adapter with " + snackFoods.size() + " items");
                                snackAdapter.updateFoods(snackFoods);
                            }
                        });
                    }
                    
                    Log.d(TAG, "Main foods loaded successfully");
                    
                    // Second API call: Get substitutions for all foods
                    loadSubstitutionsForAllFoods(breakfastFoods, lunchFoods, dinnerFoods, 
                                               snackFoods, userAge, userSex, userBMI, userHealthConditions, 
                                               userBudgetLevel, userAllergies, userDietPrefs, userPregnancyStatus);
                } else {
                    Log.w(TAG, "No main foods loaded, using fallback");
                    // Run on main thread to avoid UI threading issues
                    if (context instanceof android.app.Activity) {
                        ((android.app.Activity) context).runOnUiThread(() -> {
                            loadFallbackFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods,
                                            breakfastAdapter, lunchAdapter, dinnerAdapter, snackAdapter);
                        });
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error loading foods with substitutions: " + e.getMessage());
                // Run on main thread to avoid UI threading issues
                if (context instanceof android.app.Activity) {
                    ((android.app.Activity) context).runOnUiThread(() -> {
                        loadFallbackFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods,
                                        breakfastAdapter, lunchAdapter, dinnerAdapter, snackAdapter);
                    });
                }
            }
        });
    }
    
    /**
     * Load substitutions for all foods
     */
    private void loadSubstitutionsForAllFoods(List<FoodRecommendation> breakfastFoods, 
                                            List<FoodRecommendation> lunchFoods,
                                            List<FoodRecommendation> dinnerFoods,
                                            List<FoodRecommendation> snackFoods,
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
    private void loadFallbackFoods(List<FoodRecommendation> breakfastFoods,
                                 List<FoodRecommendation> lunchFoods,
                                 List<FoodRecommendation> dinnerFoods,
                                 List<FoodRecommendation> snackFoods,
                                 HorizontalFoodAdapter breakfastAdapter,
                                 HorizontalFoodAdapter lunchAdapter,
                                 HorizontalFoodAdapter dinnerAdapter,
                                 HorizontalFoodAdapter snackAdapter) {
        Log.d(TAG, "Loading fallback foods...");
        
        // Create some basic fallback foods
        if (breakfastFoods.isEmpty()) {
            breakfastFoods.add(new FoodRecommendation("Tapsilog", 450, 25.0, 15.0, 35.0, "1 serving", "Breakfast", "Classic Filipino breakfast", "https://example.com/tapsilog.jpg"));
        }
        if (lunchFoods.isEmpty()) {
            lunchFoods.add(new FoodRecommendation("Chicken Adobo", 400, 30.0, 20.0, 15.0, "1 serving", "Lunch", "Traditional Filipino lunch", "https://example.com/adobo.jpg"));
        }
        if (dinnerFoods.isEmpty()) {
            dinnerFoods.add(new FoodRecommendation("Grilled Fish", 250, 35.0, 8.0, 5.0, "1 serving", "Dinner", "Healthy dinner option", "https://example.com/fish.jpg"));
        }
        if (snackFoods.isEmpty()) {
            snackFoods.add(new FoodRecommendation("Fresh Fruits", 80, 1.0, 0.3, 20.0, "1 cup", "Snacks", "Nutritious snack", "https://example.com/fruits.jpg"));
        }
        
        // Update adapters on main thread
        if (context instanceof android.app.Activity) {
            ((android.app.Activity) context).runOnUiThread(() -> {
                if (breakfastAdapter != null) {
                    breakfastAdapter.setLoading(false);
                    breakfastAdapter.notifyDataSetChanged();
                }
                if (lunchAdapter != null) {
                    lunchAdapter.setLoading(false);
                    lunchAdapter.notifyDataSetChanged();
                }
                if (dinnerAdapter != null) {
                    dinnerAdapter.setLoading(false);
                    dinnerAdapter.notifyDataSetChanged();
                }
                if (snackAdapter != null) {
                    snackAdapter.setLoading(false);
                    snackAdapter.notifyDataSetChanged();
                }
            });
        }
    }
    
    public void shutdown() {
        if (executorService != null) {
            executorService.shutdown();
        }
    }
}