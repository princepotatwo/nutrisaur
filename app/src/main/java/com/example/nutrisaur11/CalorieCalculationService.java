    package com.example.nutrisaur11;

import android.util.Log;

/**
 * Service for calculating daily calorie needs and macronutrients
 * This replaces Gemini-based calculations with system-based calculations
 */
public class CalorieCalculationService {
    private static final String TAG = "CalorieCalculationService";
    
    /**
     * Calculate BMR using Mifflin-St Jeor Equation
     */
    public static double calculateBMR(UserProfile userProfile) {
        double weight = userProfile.getWeight();
        double height = userProfile.getHeight();
        int age = userProfile.getAge();
        String gender = userProfile.getGender().toLowerCase();
        
        double bmr;
        if (gender.equals("male") || gender.equals("m")) {
            bmr = 10 * weight + 6.25 * height - 5 * age + 5;
        } else {
            bmr = 10 * weight + 6.25 * height - 5 * age - 161;
        }
        
        Log.d(TAG, "Calculated BMR: " + bmr + " for " + userProfile.getName());
        return bmr;
    }
    
    /**
     * Calculate Total Daily Energy Expenditure (TDEE)
     */
    public static double calculateTDEE(UserProfile userProfile) {
        double bmr = calculateBMR(userProfile);
        double activityMultiplier = userProfile.getActivityMultiplier();
        double tdee = bmr * activityMultiplier;
        
        Log.d(TAG, "Calculated TDEE: " + tdee + " (BMR: " + bmr + " × Activity: " + activityMultiplier + ")");
        return tdee;
    }
    
    /**
     * Calculate daily calorie target based on BMI and health goals
     */
    public static int calculateDailyCalorieTarget(UserProfile userProfile) {
        double tdee = calculateTDEE(userProfile);
        double bmi = userProfile.getBmi();
        String healthGoals = userProfile.getHealthGoals().toLowerCase();
        
        double calorieAdjustment = 0;
        
        // Adjust based on BMI category
        if (bmi < 18.5) {
            // Underweight - add calories for weight gain
            if (bmi < 16) {
                calorieAdjustment = 1000; // Severe underweight
            } else {
                calorieAdjustment = 500; // Mild underweight
            }
        } else if (bmi >= 25) {
            // Overweight/Obese - subtract calories for weight loss
            if (bmi >= 30) {
                calorieAdjustment = -1000; // Obese
            } else {
                calorieAdjustment = -500; // Overweight
            }
        }
        
        // Adjust based on health goals
        if (healthGoals.contains("lose") || healthGoals.contains("weight loss")) {
            calorieAdjustment = Math.min(calorieAdjustment, -500);
        } else if (healthGoals.contains("gain") || healthGoals.contains("weight gain")) {
            calorieAdjustment = Math.max(calorieAdjustment, 500);
        }
        
        int targetCalories = (int) (tdee + calorieAdjustment);
        
        // Ensure minimum calories for health
        if (targetCalories < 1200) {
            targetCalories = 1200;
            Log.w(TAG, "Calorie target too low, set to minimum 1200");
        }
        
        Log.d(TAG, "Calculated daily calorie target: " + targetCalories + " (TDEE: " + tdee + " + Adjustment: " + calorieAdjustment + ")");
        return targetCalories;
    }
    
    /**
     * Calculate protein target in grams
     */
    public static double calculateProteinTarget(UserProfile userProfile) {
        double weight = userProfile.getWeight();
        double bmi = userProfile.getBmi();
        
        double proteinPerKg;
        if (bmi < 18.5) {
            proteinPerKg = 2.2; // Higher for underweight
        } else if (bmi >= 25) {
            proteinPerKg = 2.0; // Higher for overweight to preserve muscle
        } else {
            proteinPerKg = 1.6; // Normal weight
        }
        
        double proteinTarget = weight * proteinPerKg;
        Log.d(TAG, "Calculated protein target: " + proteinTarget + "g (" + proteinPerKg + "g/kg)");
        return proteinTarget;
    }
    
    /**
     * Calculate fat target in grams
     */
    public static double calculateFatTarget(int totalCalories) {
        // 30% of total calories from fat
        double fatCalories = totalCalories * 0.30;
        double fatTarget = fatCalories / 9; // 9 calories per gram of fat
        
        Log.d(TAG, "Calculated fat target: " + fatTarget + "g (30% of " + totalCalories + " calories)");
        return fatTarget;
    }
    
    /**
     * Calculate carbohydrate target in grams
     */
    public static double calculateCarbTarget(int totalCalories, double proteinTarget, double fatTarget) {
        double proteinCalories = proteinTarget * 4; // 4 calories per gram of protein
        double fatCalories = fatTarget * 9; // 9 calories per gram of fat
        double carbCalories = totalCalories - proteinCalories - fatCalories;
        double carbTarget = carbCalories / 4; // 4 calories per gram of carbohydrate
        
        Log.d(TAG, "Calculated carb target: " + carbTarget + "g");
        return carbTarget;
    }
    
    /**
     * Calculate meal distribution calories
     */
    public static MealDistribution calculateMealDistribution(int totalCalories) {
        MealDistribution distribution = new MealDistribution();
        
        distribution.breakfastCalories = (int) (totalCalories * 0.25);
        distribution.lunchCalories = (int) (totalCalories * 0.35);
        distribution.dinnerCalories = (int) (totalCalories * 0.30);
        distribution.snacksCalories = (int) (totalCalories * 0.10);
        
        Log.d(TAG, "Calculated meal distribution: Breakfast=" + distribution.breakfastCalories + 
              ", Lunch=" + distribution.lunchCalories + 
              ", Dinner=" + distribution.dinnerCalories + 
              ", Snacks=" + distribution.snacksCalories);
        
        return distribution;
    }
    
    /**
     * Generate health status message based on BMI
     */
    public static String generateHealthStatus(UserProfile userProfile) {
        double bmi = userProfile.getBmi();
        String bmiCategory = userProfile.getBmiCategory();
        
        if (bmi < 16) {
            return "🚨 CRITICAL: Severely underweight (BMI " + String.format("%.1f", bmi) + 
                   "). Immediate medical attention required. Focus on high-calorie, nutrient-dense foods.";
        } else if (bmi < 18.5) {
            return "⚠️ Underweight (BMI " + String.format("%.1f", bmi) + 
                   "). Focus on healthy weight gain with nutrient-dense foods.";
        } else if (bmi < 25) {
            return "✅ Healthy weight (BMI " + String.format("%.1f", bmi) + 
                   "). Maintain current weight with balanced nutrition.";
        } else if (bmi < 30) {
            return "⚠️ Overweight (BMI " + String.format("%.1f", bmi) + 
                   "). Focus on gradual weight loss with calorie deficit.";
        } else {
            return "🚨 Obese (BMI " + String.format("%.1f", bmi) + 
                   "). Significant weight loss needed. Consult healthcare provider.";
        }
    }
    
    /**
     * Generate nutrition recommendation based on user profile
     */
    public static String generateNutritionRecommendation(UserProfile userProfile) {
        double bmi = userProfile.getBmi();
        String healthGoals = userProfile.getHealthGoals().toLowerCase();
        
        if (bmi < 18.5) {
            return "Focus on high-calorie, nutrient-dense foods. Include healthy fats, lean proteins, " +
                   "and complex carbohydrates. Eat frequent meals and snacks. Consider consulting a " +
                   "nutritionist for personalized meal planning.";
        } else if (bmi >= 25) {
            return "Focus on portion control, high-fiber foods, and regular physical activity. " +
                   "Include plenty of vegetables, lean proteins, and whole grains. " +
                   "Avoid processed foods and sugary beverages.";
        } else {
            return "Maintain a balanced diet with variety. Include fruits, vegetables, lean proteins, " +
                   "whole grains, and healthy fats. Stay hydrated and maintain regular physical activity.";
        }
    }
    
    /**
     * Data class for meal distribution
     */
    public static class MealDistribution {
        public int breakfastCalories;
        public int lunchCalories;
        public int dinnerCalories;
        public int snacksCalories;
    }
}
