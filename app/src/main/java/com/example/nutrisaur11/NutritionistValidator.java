package com.example.nutrisaur11;

import android.util.Log;
import java.util.List;

public class NutritionistValidator {
    private static final String TAG = "NutritionistValidator";
    
    public static class ValidationResult {
        public boolean isValid;
        public String errorMessage;
        public List<String> warnings;
        
        public ValidationResult(boolean isValid, String errorMessage, List<String> warnings) {
            this.isValid = isValid;
            this.errorMessage = errorMessage;
            this.warnings = warnings;
        }
    }
    
    public static ValidationResult validateRecommendations(List<FoodItem> foods, String mealCategory, 
                                                          int maxCalories, UserProfile userProfile) {
        StringBuilder errors = new StringBuilder();
        StringBuilder warnings = new StringBuilder();
        
        // Check if foods list is empty
        if (foods == null || foods.isEmpty()) {
            errors.append("No foods recommended. ");
        }
        
        // Check calorie limits
        int totalCalories = 0;
        for (FoodItem food : foods) {
            totalCalories += food.getCalories();
        }
        
        if (totalCalories > maxCalories) {
            errors.append("Total calories (").append(totalCalories).append(") exceed limit (").append(maxCalories).append("). ");
        }
        
        // Check for inappropriate foods for obese patients
        if (userProfile.getBmiCategory().toLowerCase().contains("obese")) {
            for (FoodItem food : foods) {
                String foodName = food.getName().toLowerCase();
                
                // Check for obvious unhealthy foods for obese patients
                if (foodName.contains("dessert") || foodName.contains("cake") || 
                    foodName.contains("candy") || foodName.contains("sweet")) {
                    warnings.append("Consider if this is appropriate for weight loss: ").append(food.getName()).append(". ");
                }
                
                // Check for fried foods
                if (foodName.contains("fried") || foodName.contains("deep fried")) {
                    warnings.append("Fried food may not be ideal for weight loss: ").append(food.getName()).append(". ");
                }
            }
        }
        
        // Check meal category appropriateness
        for (FoodItem food : foods) {
            String foodName = food.getName().toLowerCase();
            
            if (mealCategory.toLowerCase().equals("breakfast")) {
                if (foodName.contains("dinner") || foodName.contains("heavy")) {
                    warnings.append("Inappropriate breakfast food: ").append(food.getName()).append(". ");
                }
            } else if (mealCategory.toLowerCase().equals("snacks")) {
                if (food.getCalories() > 100) {
                    warnings.append("High-calorie snack: ").append(food.getName()).append(" (").append(food.getCalories()).append(" kcal). ");
                }
            }
        }
        
        boolean isValid = errors.length() == 0;
        String errorMessage = errors.toString();
        
        Log.d(TAG, "Validation result - Valid: " + isValid + ", Errors: " + errorMessage + ", Warnings: " + warnings.toString());
        
        return new ValidationResult(isValid, errorMessage, List.of(warnings.toString()));
    }
    
    public static void logValidationResults(ValidationResult result, String mealCategory, UserProfile userProfile) {
        Log.d(TAG, "=== NUTRITIONIST VALIDATION RESULTS ===");
        Log.d(TAG, "Meal: " + mealCategory);
        Log.d(TAG, "User: " + userProfile.getName() + " (BMI: " + userProfile.getBmi() + ", " + userProfile.getBmiCategory() + ")");
        Log.d(TAG, "Valid: " + result.isValid);
        
        if (!result.isValid) {
            Log.e(TAG, "❌ ERRORS: " + result.errorMessage);
        }
        
        if (!result.warnings.isEmpty()) {
            Log.w(TAG, "⚠️ WARNINGS: " + String.join(", ", result.warnings));
        }
        
        if (result.isValid && result.warnings.isEmpty()) {
            Log.i(TAG, "✅ All recommendations are clinically appropriate!");
        }
        Log.d(TAG, "=====================================");
    }
}
