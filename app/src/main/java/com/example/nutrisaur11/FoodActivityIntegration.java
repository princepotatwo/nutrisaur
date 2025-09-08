package com.example.nutrisaur11;

import android.util.Log;
import java.util.List;
import java.util.ArrayList;

/**
 * Integration methods for FoodActivity to use optimized malnutrition recovery API
 */
public class FoodActivityIntegration {
    private static final String TAG = "FoodActivityIntegration";
    
    /**
     * Replace your current loadFoodDataForAllCategories() method with this:
     */
    public static void loadMalnutritionRecoveryFoods(FoodActivity activity, 
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
        
        Log.d(TAG, "Loading malnutrition recovery foods with single API call");
        Log.d(TAG, "User data - Age: " + userAge + ", Sex: " + userSex + ", BMI: " + userBMI + 
              ", Health: " + userHealthConditions + ", Budget: " + userBudgetLevel);
        
        OptimizedMalnutritionFoodAPI.loadMalnutritionRecoveryFoods(
            userAge, userSex, userBMI, userHealthConditions, userBudgetLevel, 
            userAllergies, userDietPrefs, userPregnancyStatus,
            new OptimizedMalnutritionFoodAPI.MalnutritionFoodCallback() {
                @Override
                public void onSuccess(OptimizedMalnutritionFoodAPI.MalnutritionFoodResponse response) {
                    Log.d(TAG, "✅ SUCCESS: Gemini API loaded malnutrition recovery foods");
                    Log.d(TAG, "✅ API Response received with " + 
                          "Breakfast(" + response.breakfast.size() + "), " +
                          "Lunch(" + response.lunch.size() + "), " +
                          "Dinner(" + response.dinner.size() + "), " +
                          "Snacks(" + response.snacks.size() + ") foods");
                    
                    // Update breakfast foods
                    breakfastFoods.clear();
                    breakfastFoods.addAll(response.breakfast);
                    activity.runOnUiThread(() -> {
                        breakfastAdapter.setLoading(false);
                        breakfastAdapter.notifyDataSetChanged();
                    });
                    
                    // Update lunch foods
                    lunchFoods.clear();
                    lunchFoods.addAll(response.lunch);
                    activity.runOnUiThread(() -> {
                        lunchAdapter.setLoading(false);
                        lunchAdapter.notifyDataSetChanged();
                    });
                    
                    // Update dinner foods
                    dinnerFoods.clear();
                    dinnerFoods.addAll(response.dinner);
                    activity.runOnUiThread(() -> {
                        dinnerAdapter.setLoading(false);
                        dinnerAdapter.notifyDataSetChanged();
                    });
                    
                    // Update snack foods
                    snackFoods.clear();
                    snackFoods.addAll(response.snacks);
                    activity.runOnUiThread(() -> {
                        snackAdapter.setLoading(false);
                        snackAdapter.notifyDataSetChanged();
                    });
                    
                    Log.d(TAG, "✅ All meal categories updated successfully - Gemini API working!");
                }
                
                @Override
                public void onError(String error) {
                    Log.e(TAG, "❌ FAILED: Gemini API error - " + error);
                    Log.e(TAG, "❌ Loading fallback foods due to API failure");
                    
                    // Load fallback foods
                    loadFallbackMalnutritionFoods(activity, breakfastFoods, lunchFoods, dinnerFoods, snackFoods,
                                                breakfastAdapter, lunchAdapter, dinnerAdapter, snackAdapter);
                }
            }
        );
    }
    
    /**
     * Fallback method to load static malnutrition recovery foods
     */
    private static void loadFallbackMalnutritionFoods(FoodActivity activity,
                                                     List<FoodRecommendation> breakfastFoods,
                                                     List<FoodRecommendation> lunchFoods,
                                                     List<FoodRecommendation> dinnerFoods,
                                                     List<FoodRecommendation> snackFoods,
                                                     HorizontalFoodAdapter breakfastAdapter,
                                                     HorizontalFoodAdapter lunchAdapter,
                                                     HorizontalFoodAdapter dinnerAdapter,
                                                     HorizontalFoodAdapter snackAdapter) {
        
        Log.d(TAG, "Loading fallback malnutrition recovery foods");
        
        // Clear existing foods
        breakfastFoods.clear();
        lunchFoods.clear();
        dinnerFoods.clear();
        snackFoods.clear();
        
        // Add fallback foods for each category
        breakfastFoods.add(new FoodRecommendation("Arroz Caldo", 450, 25, 15, 50, "1 bowl", "Malnutrition Recovery", "Nutritious rice porridge with chicken", "arroz_caldo"));
        breakfastFoods.add(new FoodRecommendation("Champorado", 400, 20, 12, 60, "1 bowl", "Malnutrition Recovery", "Chocolate rice porridge with milk", "champorado"));
        breakfastFoods.add(new FoodRecommendation("Tapsilog", 500, 30, 20, 45, "1 plate", "Malnutrition Recovery", "High-protein breakfast with beef and egg", "tapsilog"));
        breakfastFoods.add(new FoodRecommendation("Lugaw", 350, 18, 10, 40, "1 bowl", "Malnutrition Recovery", "Simple rice porridge for easy digestion", "lugaw"));
        
        lunchFoods.add(new FoodRecommendation("Sinigang", 420, 30, 15, 25, "1 bowl", "Malnutrition Recovery", "Protein-rich sour soup with pork", "sinigang"));
        lunchFoods.add(new FoodRecommendation("Adobo", 450, 25, 18, 35, "1 plate", "Malnutrition Recovery", "High-protein stewed meat dish", "adobo"));
        lunchFoods.add(new FoodRecommendation("Tinola", 380, 25, 12, 20, "1 bowl", "Malnutrition Recovery", "Light chicken soup with vegetables", "tinola"));
        lunchFoods.add(new FoodRecommendation("Kare-Kare", 500, 28, 22, 30, "1 plate", "Malnutrition Recovery", "Nutritious stew with vegetables and meat", "kare_kare"));
        
        dinnerFoods.add(new FoodRecommendation("Nilagang Baboy", 400, 22, 16, 25, "1 bowl", "Malnutrition Recovery", "Boiled pork soup with vegetables", "nilagang_baboy"));
        dinnerFoods.add(new FoodRecommendation("Bulalo", 450, 30, 20, 15, "1 bowl", "Malnutrition Recovery", "Rich beef bone marrow soup", "bulalo"));
        dinnerFoods.add(new FoodRecommendation("Paksiw na Bangus", 350, 25, 14, 20, "1 plate", "Malnutrition Recovery", "Fish cooked in vinegar and spices", "paksiw_na_bangus"));
        dinnerFoods.add(new FoodRecommendation("Mechado", 480, 28, 20, 30, "1 plate", "Malnutrition Recovery", "Slow-cooked beef with vegetables", "mechado"));
        
        snackFoods.add(new FoodRecommendation("Taho", 200, 12, 3, 35, "1 cup", "Malnutrition Recovery", "High-protein soy pudding with sago", "taho"));
        snackFoods.add(new FoodRecommendation("Halo-Halo", 300, 8, 15, 45, "1 glass", "Malnutrition Recovery", "Nutritious mixed dessert with fruits", "halo_halo"));
        snackFoods.add(new FoodRecommendation("Buko Salad", 250, 6, 12, 35, "1 cup", "Malnutrition Recovery", "Fresh coconut and fruit salad", "buko_salad"));
        snackFoods.add(new FoodRecommendation("Leche Flan", 280, 10, 18, 25, "1 slice", "Malnutrition Recovery", "Protein-rich custard dessert", "leche_flan"));
        
        // Notify adapters on UI thread
        activity.runOnUiThread(() -> {
            breakfastAdapter.setLoading(false);
            lunchAdapter.setLoading(false);
            dinnerAdapter.setLoading(false);
            snackAdapter.setLoading(false);
            
            breakfastAdapter.notifyDataSetChanged();
            lunchAdapter.notifyDataSetChanged();
            dinnerAdapter.notifyDataSetChanged();
            snackAdapter.notifyDataSetChanged();
            Log.d(TAG, "✅ Fallback foods loaded successfully");
        });
    }
}
