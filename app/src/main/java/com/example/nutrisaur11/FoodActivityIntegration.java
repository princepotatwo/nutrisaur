package com.example.nutrisaur11;

import android.content.Context;
import android.util.Log;

import com.example.nutrisaur11.adapters.HorizontalFoodAdapter;
import com.example.nutrisaur11.FoodRecommendation;

import java.util.List;

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
        
        // Create simple, intelligent prompt that lets AI analyze everything
        String prompt = FoodActivityIntegrationMethods.buildConditionSpecificPrompt(
            userAge, userSex, userBMI, userHeight, userWeight,
            userBMICategory, userMUAC, userMUACCategory, userNutritionalRisk,
            userHealthConditions, userActivityLevel, userBudgetLevel, 
            userDietaryRestrictions, userAllergies, userDietPrefs, 
            userAvoidFoods, userRiskScore, userBarangay, userIncome, 
            userPregnancyStatus, userMunicipality, userScreeningDate, userNotes,
            "AI_ANALYSIS" // Let AI determine the condition
        );
        
        // Use OptimizedGeminiService to get personalized recommendations
        OptimizedGeminiService geminiService = new OptimizedGeminiService();
        geminiService.generateFoodRecommendations(
            prompt,
            new OptimizedGeminiService.FoodRecommendationCallback() {
                @Override
                public void onSuccess(List<FoodRecommendation> recommendations) {
                    Log.d(TAG, "Successfully received " + recommendations.size() + " AI-generated recommendations");
                    
                    // Categorize recommendations
                    categorizeRecommendations(recommendations, breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                    
                    // Update adapters
                    breakfastAdapter.notifyDataSetChanged();
                    lunchAdapter.notifyDataSetChanged();
                    dinnerAdapter.notifyDataSetChanged();
                    snackAdapter.notifyDataSetChanged();
                    
                    Log.d(TAG, "Food lists updated with AI nutritionist recommendations");
                }
                
                @Override
                public void onError(String error) {
                    Log.e(TAG, "Error getting AI recommendations: " + error);
                    // Simple fallback - let user know AI is unavailable
                    loadSimpleFallbackFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                    
                    // Update adapters
                    breakfastAdapter.notifyDataSetChanged();
                    lunchAdapter.notifyDataSetChanged();
                    dinnerAdapter.notifyDataSetChanged();
                    snackAdapter.notifyDataSetChanged();
                }
            }
        );
    }
    
    
    private static void categorizeRecommendations(List<FoodRecommendation> recommendations,
                                                List<FoodRecommendation> breakfastFoods,
                                 List<FoodRecommendation> lunchFoods,
                                 List<FoodRecommendation> dinnerFoods,
                                                List<FoodRecommendation> snackFoods) {
        
        for (FoodRecommendation food : recommendations) {
            String dietType = food.getDietType();
            if (dietType != null) {
                switch (dietType.toLowerCase()) {
                    case "breakfast":
                        breakfastFoods.add(food);
                        break;
                    case "lunch":
                        lunchFoods.add(food);
                        break;
                    case "dinner":
                        dinnerFoods.add(food);
                        break;
                    case "snack":
                        snackFoods.add(food);
                        break;
                    default:
                        lunchFoods.add(food);
                        break;
                }
            } else {
                lunchFoods.add(food);
            }
        }
        
        Log.d(TAG, "Categorized recommendations: " + 
              breakfastFoods.size() + " breakfast, " + 
              lunchFoods.size() + " lunch, " + 
              dinnerFoods.size() + " dinner, " + 
              snackFoods.size() + " snacks");
    }
    
    private static void loadSimpleFallbackFoods(List<FoodRecommendation> breakfastFoods,
                                               List<FoodRecommendation> lunchFoods,
                                               List<FoodRecommendation> dinnerFoods,
                                               List<FoodRecommendation> snackFoods) {
        
        Log.d(TAG, "Loading simple fallback foods - AI nutritionist unavailable");
        
        // Simple fallback foods when AI is not available
        breakfastFoods.add(new FoodRecommendation("Chicken Arroz Caldo", 350, 25, 10, 40, "1 bowl", "Breakfast", "Warm and nutritious rice porridge"));
        lunchFoods.add(new FoodRecommendation("Grilled Fish with Rice", 450, 30, 15, 50, "1 serving", "Lunch", "Lean protein with complex carbs"));
        dinnerFoods.add(new FoodRecommendation("Vegetable Stir-fry", 300, 15, 8, 35, "1 plate", "Dinner", "Colorful vegetables with minimal oil"));
        snackFoods.add(new FoodRecommendation("Banana", 100, 1, 0, 25, "1 piece", "Snack", "Natural energy boost"));
    }
}
