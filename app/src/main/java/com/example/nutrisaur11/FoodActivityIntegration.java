package com.example.nutrisaur11;

import android.content.Context;
import android.util.Log;

import com.example.nutrisaur11.adapters.HorizontalFoodAdapter;
import com.example.nutrisaur11.FoodRecommendation;

import java.util.List;
import java.util.Map;
import java.util.ArrayList;
import java.util.HashMap;

public class FoodActivityIntegration {
    private static final String TAG = "FoodActivityIntegration";
    
    public static void loadMalnutritionRecoveryFoodsWithScreening(Context context,
                                                    String userAge, String userSex, String userBMI, 
                                                                String userHeight, String userWeight,
                                                                String userBMICategory, String userMUAC, 
                                                                String userMUACCategory, String userNutritionalRisk,
                                                                String userHealthConditions, String userActivityLevel, 
                                                                String userBudgetLevel, String userDietaryRestrictions, 
                                                    String userAllergies, String userDietPrefs, 
                                                                String userAvoidFoods, String userRiskScore, 
                                                                String userBarangay, String userIncome, 
                                                                String userPregnancyStatus, String userMunicipality, 
                                                                String userScreeningDate, String userNotes,
                                                   List<FoodRecommendation> breakfastFoods,
                                                   List<FoodRecommendation> lunchFoods,
                                                   List<FoodRecommendation> dinnerFoods,
                                                   List<FoodRecommendation> snackFoods,
                                                   HorizontalFoodAdapter breakfastAdapter,
                                                   HorizontalFoodAdapter lunchAdapter,
                                                   HorizontalFoodAdapter dinnerAdapter,
                                                   HorizontalFoodAdapter snackAdapter) {
        
        Log.d(TAG, "=== LOADING FOODS WITH AI NUTRITIONIST ===");
        
        // Load questionnaire answers from SharedPreferences
        Map<String, String> questionnaireAnswers = loadQuestionnaireAnswers(context);
        Log.d("FoodActivityIntegration", "Loaded questionnaire answers: " + questionnaireAnswers.toString());
        
        // Create simple, intelligent prompt that lets AI analyze everything
        String prompt = FoodActivityIntegrationMethods.buildConditionSpecificPrompt(
            userAge, userSex, userBMI, userHeight, userWeight,
            userBMICategory, userMUAC, userMUACCategory, userNutritionalRisk,
            userHealthConditions, userActivityLevel, userBudgetLevel, 
            userDietaryRestrictions, userAllergies, userDietPrefs, 
            userAvoidFoods, userRiskScore, userBarangay, userIncome, 
            userPregnancyStatus, userMunicipality, userScreeningDate, userNotes,
            "AI_ANALYSIS", // Let AI determine the condition
            questionnaireAnswers
        );
        
        // Use ChatGPTService to get personalized recommendations
        new Thread(() -> {
            try {
                Map<String, List<FoodRecommendation>> result = ChatGPTService.callChatGPTWithRetry(prompt);
                
                // Process the result on main thread
                new android.os.Handler(android.os.Looper.getMainLooper()).post(() -> {
                    Log.d(TAG, "=== SUCCESS: Received AI-generated recommendations ===");
                    
                    // Extract foods from each category
                    List<FoodRecommendation> allRecommendations = new ArrayList<>();
                    if (result.containsKey("breakfast")) allRecommendations.addAll(result.get("breakfast"));
                    if (result.containsKey("lunch")) allRecommendations.addAll(result.get("lunch"));
                    if (result.containsKey("dinner")) allRecommendations.addAll(result.get("dinner"));
                    if (result.containsKey("snacks")) allRecommendations.addAll(result.get("snacks"));
                    
                    Log.d(TAG, "=== SUCCESS: Received " + allRecommendations.size() + " AI-generated recommendations ===");
                    
                    // Log each recommendation
                    for (int i = 0; i < allRecommendations.size(); i++) {
                        FoodRecommendation food = allRecommendations.get(i);
                        Log.d(TAG, "Recommendation " + i + ": " + food.getFoodName() + " (" + food.getDietType() + ")");
                    }
                    
                    // Categorize recommendations
                    categorizeRecommendations(allRecommendations, breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                    
                    // Update adapters with new food lists
                    breakfastAdapter.updateFoodList(breakfastFoods);
                    lunchAdapter.updateFoodList(lunchFoods);
                    dinnerAdapter.updateFoodList(dinnerFoods);
                    snackAdapter.updateFoodList(snackFoods);
                    
                    // Set loading to false
                    breakfastAdapter.setLoading(false);
                    lunchAdapter.setLoading(false);
                    dinnerAdapter.setLoading(false);
                    snackAdapter.setLoading(false);
                    
                    Log.d(TAG, "=== FINAL COUNTS ===");
                    Log.d(TAG, "Breakfast foods: " + breakfastFoods.size());
                    Log.d(TAG, "Lunch foods: " + lunchFoods.size());
                    Log.d(TAG, "Dinner foods: " + dinnerFoods.size());
                    Log.d(TAG, "Snack foods: " + snackFoods.size());
                    Log.d(TAG, "Food lists updated with AI nutritionist recommendations - loading state disabled");
                });
                
            } catch (Exception e) {
                Log.e(TAG, "Error getting AI recommendations: " + e.getMessage());
                // Retry with a simpler prompt
                Log.d(TAG, "Retrying API call due to error: " + e.getMessage());
                
                try {
                    String retryPrompt = "Generate 8 Filipino breakfast foods, 8 lunch foods, 8 dinner foods, and 8 snack foods. Return JSON: {\"breakfast\":[{\"food_name\":\"Name\",\"calories\":300,\"protein_g\":20,\"fat_g\":10,\"carbs_g\":25,\"serving_size\":\"1 serving\",\"diet_type\":\"Breakfast\",\"description\":\"Description\"},...],\"lunch\":[...],\"dinner\":[...],\"snacks\":[...]}";
                    
                    Map<String, List<FoodRecommendation>> retryResult = ChatGPTService.callChatGPTWithRetry(retryPrompt);
                    
                    // Process retry result on main thread
                    new android.os.Handler(android.os.Looper.getMainLooper()).post(() -> {
                        Log.d(TAG, "Retry SUCCESS: Received recommendations");
                        
                        // Extract foods from each category
                        List<FoodRecommendation> allRetryRecommendations = new ArrayList<>();
                        if (retryResult.containsKey("breakfast")) allRetryRecommendations.addAll(retryResult.get("breakfast"));
                        if (retryResult.containsKey("lunch")) allRetryRecommendations.addAll(retryResult.get("lunch"));
                        if (retryResult.containsKey("dinner")) allRetryRecommendations.addAll(retryResult.get("dinner"));
                        if (retryResult.containsKey("snacks")) allRetryRecommendations.addAll(retryResult.get("snacks"));
                        
                        // Categorize recommendations
                        categorizeRecommendations(allRetryRecommendations, breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                        
                        // Update adapters with new food lists
                        breakfastAdapter.updateFoodList(breakfastFoods);
                        lunchAdapter.updateFoodList(lunchFoods);
                        dinnerAdapter.updateFoodList(dinnerFoods);
                        snackAdapter.updateFoodList(snackFoods);
                        
                        // Set loading to false
                        breakfastAdapter.setLoading(false);
                        lunchAdapter.setLoading(false);
                        dinnerAdapter.setLoading(false);
                        snackAdapter.setLoading(false);
                        
                        Log.d(TAG, "Retry successful - food lists updated");
                    });
                    
                } catch (Exception retryException) {
                    Log.e(TAG, "Retry also failed: " + retryException.getMessage());
                    // Set loading to false even on failure
                    new android.os.Handler(android.os.Looper.getMainLooper()).post(() -> {
                        breakfastAdapter.setLoading(false);
                        lunchAdapter.setLoading(false);
                        dinnerAdapter.setLoading(false);
                        snackAdapter.setLoading(false);
                    });
                }
            }
        }).start();
    }
    
    /**
     * Load questionnaire answers from SharedPreferences
     */
    public static Map<String, String> loadQuestionnaireAnswers(Context context) {
        android.content.SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        Map<String, String> answers = new java.util.HashMap<>();
        
        // Get user email for user-specific preferences
        // Try nutrisaur_prefs first (community users), then UserPreferences (legacy)
        String userEmail = prefs.getString("current_user_email", "");
        if (userEmail.isEmpty()) {
            // Fallback to legacy UserPreferences
            userEmail = prefs.getString("user_email", "");
        }
        
        if (userEmail.isEmpty()) {
            Log.w(TAG, "No user email found, cannot load user-specific preferences");
            return answers;
        }
        
        // Load food preferences with user-specific keys
        for (int i = 0; i < 5; i++) {
            String key = userEmail + "_question_" + i;
            String value = prefs.getString(key, "");
            if (!value.isEmpty()) {
                // Store with generic key for compatibility
                answers.put("question_" + i, value);
            }
        }
        
        // Also load combined food preferences for AI prompting
        String combinedPreferences = prefs.getString(userEmail + "_food_preferences_combined", "");
        if (!combinedPreferences.isEmpty()) {
            answers.put("food_preferences_combined", combinedPreferences);
        }
        
        // Also load old format for backward compatibility
        for (int i = 0; i < 5; i++) {
            String key = "question_" + i;
            if (!answers.containsKey(key)) {
                String value = prefs.getString(key, "");
                if (!value.isEmpty()) {
                    answers.put(key, value);
                }
            }
        }
        
        Log.d(TAG, "Loaded questionnaire answers: " + answers.size() + " answers for user: " + userEmail);
        return answers;
    }
    
    /**
     * Categorize recommendations into breakfast, lunch, dinner, and snacks
     */
    private static void categorizeRecommendations(List<FoodRecommendation> recommendations,
                                                List<FoodRecommendation> breakfastFoods,
                                 List<FoodRecommendation> lunchFoods,
                                 List<FoodRecommendation> dinnerFoods,
                                                List<FoodRecommendation> snackFoods) {
        
        Log.d(TAG, "=== CATEGORIZING " + recommendations.size() + " RECOMMENDATIONS ===");
        
        // Clear existing lists
        breakfastFoods.clear();
        lunchFoods.clear();
        dinnerFoods.clear();
        snackFoods.clear();
        
        // Categorize each recommendation
        for (FoodRecommendation food : recommendations) {
            String dietType = food.getDietType();
            Log.d(TAG, "Categorizing: " + food.getFoodName() + " -> dietType: '" + dietType + "'");
            
            if (dietType != null) {
                if (dietType.equalsIgnoreCase("Breakfast")) {
                    breakfastFoods.add(food);
                    Log.d(TAG, "Added to breakfast: " + food.getFoodName());
                } else if (dietType.equalsIgnoreCase("Lunch")) {
                    lunchFoods.add(food);
                    Log.d(TAG, "Added to lunch: " + food.getFoodName());
                } else if (dietType.equalsIgnoreCase("Dinner")) {
                    dinnerFoods.add(food);
                    Log.d(TAG, "Added to dinner: " + food.getFoodName());
                } else if (dietType.equalsIgnoreCase("Snacks")) {
                    snackFoods.add(food);
                    Log.d(TAG, "Added to snacks: " + food.getFoodName());
                } else {
                    // Default to snacks if unknown
                    snackFoods.add(food);
                    Log.d(TAG, "Added to snacks (default): " + food.getFoodName());
                }
            } else {
                // Default to snacks if no diet type
                snackFoods.add(food);
                Log.d(TAG, "Added to snacks (no type): " + food.getFoodName());
            }
        }
        
        Log.d(TAG, "=== CATEGORIZATION COMPLETE ===");
        Log.d(TAG, "Breakfast: " + breakfastFoods.size() + " foods");
        Log.d(TAG, "Lunch: " + lunchFoods.size() + " foods");
        Log.d(TAG, "Dinner: " + dinnerFoods.size() + " foods");
        Log.d(TAG, "Snacks: " + snackFoods.size() + " foods");
    }
}