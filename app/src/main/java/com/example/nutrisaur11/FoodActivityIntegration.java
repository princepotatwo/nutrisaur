package com.example.nutrisaur11;

import android.content.Context;
import android.util.Log;

import com.example.nutrisaur11.adapters.HorizontalFoodAdapter;
import com.example.nutrisaur11.models.FoodRecommendation;

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
    
    private static void loadConditionSpecificFallbackFoods(String primaryCondition,
                                                          List<FoodRecommendation> breakfastFoods,
                                                          List<FoodRecommendation> lunchFoods,
                                                          List<FoodRecommendation> dinnerFoods,
                                                          List<FoodRecommendation> snackFoods) {
        
        Log.d(TAG, "Loading fallback foods for condition: " + primaryCondition);
        
        switch (primaryCondition) {
            case "SEVERE_MALNUTRITION":
                loadSevereMalnutritionFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "PREGNANT":
                loadPregnantFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "INFANT":
                loadInfantFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "TODDLER":
                loadToddlerFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "CHILD":
                loadChildFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "TEEN":
                loadTeenFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "SENIOR":
                loadSeniorFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "UNDERWEIGHT":
                loadUnderweightFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "OVERWEIGHT":
                loadOverweightFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "OBESE":
                loadObeseFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            case "MALNUTRITION":
                loadMalnutritionFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
            default:
                loadNormalAdultFoods(breakfastFoods, lunchFoods, dinnerFoods, snackFoods);
                break;
        }
    }
    
    // Condition-specific food loading methods
    private static void loadSevereMalnutritionFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                                   List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("High-Protein Porridge", 400, 20, 8, 45, "1 bowl", "Breakfast", "Nutrient-dense recovery meal"));
        lunch.add(new FoodRecommendation("Fortified Rice with Fish", 500, 30, 12, 55, "1 plate", "Lunch", "Complete protein and vitamins"));
        dinner.add(new FoodRecommendation("Chicken Soup with Vegetables", 450, 25, 10, 40, "1 bowl", "Dinner", "Easy to digest, high nutrition"));
        snacks.add(new FoodRecommendation("Peanut Butter Banana", 200, 8, 12, 25, "1 serving", "Snack", "High-calorie, nutrient-dense"));
    }
    
    private static void loadPregnantFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                         List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Fortified Cereal with Milk", 350, 15, 8, 45, "1 bowl", "Breakfast", "Folic acid and iron rich"));
        lunch.add(new FoodRecommendation("Grilled Salmon with Spinach", 450, 35, 15, 20, "1 serving", "Lunch", "Omega-3 and folate"));
        dinner.add(new FoodRecommendation("Lean Beef with Sweet Potato", 500, 40, 12, 50, "1 plate", "Dinner", "Iron and beta-carotene"));
        snacks.add(new FoodRecommendation("Greek Yogurt with Berries", 150, 15, 2, 20, "1 cup", "Snack", "Calcium and antioxidants"));
    }
    
    private static void loadInfantFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                       List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Soft Rice Porridge", 200, 5, 2, 35, "1/2 cup", "Breakfast", "Easy to digest"));
        lunch.add(new FoodRecommendation("Mashed Sweet Potato", 150, 3, 1, 25, "1/4 cup", "Lunch", "Vitamin A rich"));
        dinner.add(new FoodRecommendation("Pureed Chicken and Carrots", 180, 12, 4, 15, "1/4 cup", "Dinner", "Protein and vitamins"));
        snacks.add(new FoodRecommendation("Soft Banana", 80, 1, 0, 20, "1/4 piece", "Snack", "Natural sweetness"));
    }
    
    private static void loadToddlerFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                        List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Mini Pancakes with Fruit", 250, 8, 6, 35, "2 small", "Breakfast", "Fun and nutritious"));
        lunch.add(new FoodRecommendation("Chicken Nuggets with Veggies", 300, 20, 12, 25, "4 pieces", "Lunch", "Kid-friendly protein"));
        dinner.add(new FoodRecommendation("Fish Sticks with Rice", 350, 25, 8, 40, "3 pieces", "Dinner", "Omega-3 for brain development"));
        snacks.add(new FoodRecommendation("Cheese Cubes", 100, 8, 6, 1, "4 cubes", "Snack", "Calcium for growing bones"));
    }
    
    private static void loadChildFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                      List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Whole Grain Toast with Eggs", 300, 15, 10, 30, "2 slices", "Breakfast", "Energy for active kids"));
        lunch.add(new FoodRecommendation("Turkey Sandwich with Veggies", 400, 25, 12, 45, "1 sandwich", "Lunch", "Balanced nutrition"));
        dinner.add(new FoodRecommendation("Baked Chicken with Broccoli", 450, 35, 15, 25, "1 serving", "Dinner", "Lean protein and vitamins"));
        snacks.add(new FoodRecommendation("Apple Slices with Peanut Butter", 200, 8, 12, 25, "1 apple", "Snack", "Fiber and healthy fats"));
    }
    
    private static void loadTeenFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                     List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Protein Smoothie Bowl", 400, 25, 8, 45, "1 bowl", "Breakfast", "Fuel for growth spurt"));
        lunch.add(new FoodRecommendation("Chicken Burrito Bowl", 550, 35, 15, 60, "1 bowl", "Lunch", "High-calorie for active teens"));
        dinner.add(new FoodRecommendation("Grilled Steak with Quinoa", 600, 45, 20, 50, "1 serving", "Dinner", "Iron and protein for development"));
        snacks.add(new FoodRecommendation("Trail Mix", 300, 10, 20, 25, "1/2 cup", "Snack", "Energy-dense for busy teens"));
    }
    
    private static void loadSeniorFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                       List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Oatmeal with Nuts", 300, 12, 8, 45, "1 bowl", "Breakfast", "Fiber and heart-healthy"));
        lunch.add(new FoodRecommendation("Salmon Salad", 350, 30, 15, 20, "1 plate", "Lunch", "Omega-3 for brain health"));
        dinner.add(new FoodRecommendation("Baked Fish with Vegetables", 400, 35, 12, 30, "1 serving", "Dinner", "Easy to digest, nutrient-rich"));
        snacks.add(new FoodRecommendation("Greek Yogurt", 120, 15, 2, 10, "1 cup", "Snack", "Protein and probiotics"));
    }
    
    private static void loadUnderweightFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                            List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("High-Calorie Smoothie", 500, 20, 15, 60, "1 large", "Breakfast", "Nutrient-dense weight gain"));
        lunch.add(new FoodRecommendation("Avocado Toast with Eggs", 450, 20, 25, 35, "2 slices", "Lunch", "Healthy fats and protein"));
        dinner.add(new FoodRecommendation("Pasta with Meat Sauce", 600, 30, 20, 70, "1 large plate", "Dinner", "High-calorie, balanced meal"));
        snacks.add(new FoodRecommendation("Nuts and Dried Fruits", 350, 10, 25, 30, "1/2 cup", "Snack", "Calorie-dense, nutritious"));
    }
    
    private static void loadOverweightFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                           List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Greek Yogurt Parfait", 250, 20, 5, 30, "1 cup", "Breakfast", "High protein, low calorie"));
        lunch.add(new FoodRecommendation("Grilled Chicken Salad", 350, 35, 12, 20, "1 large bowl", "Lunch", "Lean protein, lots of veggies"));
        dinner.add(new FoodRecommendation("Baked Fish with Roasted Vegetables", 400, 30, 15, 25, "1 serving", "Dinner", "Low-calorie, nutrient-rich"));
        snacks.add(new FoodRecommendation("Vegetable Sticks with Hummus", 150, 8, 6, 15, "1 serving", "Snack", "Low-calorie, filling"));
    }
    
    private static void loadObeseFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                      List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Vegetable Omelet", 200, 20, 12, 8, "1 serving", "Breakfast", "High protein, low carb"));
        lunch.add(new FoodRecommendation("Grilled Chicken with Steamed Broccoli", 300, 40, 8, 15, "1 serving", "Lunch", "Lean protein, low calorie"));
        dinner.add(new FoodRecommendation("Baked Salmon with Asparagus", 350, 35, 15, 12, "1 serving", "Dinner", "Heart-healthy, portion-controlled"));
        snacks.add(new FoodRecommendation("Apple with Cinnamon", 80, 0, 0, 20, "1 medium", "Snack", "Natural sweetness, low calorie"));
    }
    
    private static void loadMalnutritionFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                             List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Fortified Rice Porridge", 350, 15, 8, 50, "1 bowl", "Breakfast", "Nutrient-dense recovery"));
        lunch.add(new FoodRecommendation("Chicken and Vegetable Stew", 450, 30, 12, 40, "1 bowl", "Lunch", "Complete nutrition"));
        dinner.add(new FoodRecommendation("Fish with Rice and Beans", 500, 35, 10, 60, "1 plate", "Dinner", "Protein and complex carbs"));
        snacks.add(new FoodRecommendation("Peanut Butter on Crackers", 200, 8, 12, 20, "4 crackers", "Snack", "High-calorie, nutritious"));
    }
    
    private static void loadNormalAdultFoods(List<FoodRecommendation> breakfast, List<FoodRecommendation> lunch, 
                                            List<FoodRecommendation> dinner, List<FoodRecommendation> snacks) {
        breakfast.add(new FoodRecommendation("Whole Grain Toast with Avocado", 300, 12, 15, 35, "2 slices", "Breakfast", "Balanced nutrition"));
        lunch.add(new FoodRecommendation("Grilled Chicken Wrap", 400, 30, 12, 45, "1 wrap", "Lunch", "Complete meal"));
        dinner.add(new FoodRecommendation("Baked Salmon with Quinoa", 500, 35, 18, 45, "1 serving", "Dinner", "Omega-3 and complete protein"));
        snacks.add(new FoodRecommendation("Mixed Nuts", 200, 8, 16, 8, "1/4 cup", "Snack", "Healthy fats and protein"));
    }
}
