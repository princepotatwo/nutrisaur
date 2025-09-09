package com.example.nutrisaur11;

import android.content.Context;
import android.util.Log;
import java.util.HashMap;
import java.util.Map;

/**
 * Daily Nutrition Dashboard
 * Provides suggested daily nutrition targets based on user screening data
 * Uses reliable nutrition data from WHO, USDA, and Philippine nutrition guidelines
 */
public class DailyNutritionDashboard {
    private static final String TAG = "DailyNutritionDashboard";
    
    // User screening data
    private String userAge;
    private String userSex;
    private String userBMI;
    private String userHealthConditions;
    private String userPregnancyStatus;
    private String userActivityLevel;
    
    // Suggested daily nutrition targets
    private int suggestedCalories;
    private int suggestedProtein;
    private int suggestedCarbs;
    private int suggestedFat;
    private int suggestedFiber;
    private int suggestedSodium;
    private int suggestedSugar;
    
    public DailyNutritionDashboard(String userAge, String userSex, String userBMI, 
                                 String userHealthConditions, String userPregnancyStatus) {
        this.userAge = userAge;
        this.userSex = userSex;
        this.userBMI = userBMI;
        this.userHealthConditions = userHealthConditions;
        this.userPregnancyStatus = userPregnancyStatus;
        this.userActivityLevel = "Moderate"; // Default activity level
        
        calculateSuggestedNutrition();
    }
    
    /**
     * Calculate suggested daily nutrition based on user screening data
     * Uses WHO, USDA, and Philippine nutrition guidelines
     */
    private void calculateSuggestedNutrition() {
        try {
            int age = parseAge(userAge);
            double bmi = parseBMI(userBMI);
            boolean isMale = "Male".equalsIgnoreCase(userSex);
            boolean isPregnant = "Yes".equalsIgnoreCase(userPregnancyStatus);
            
            // Calculate Basal Metabolic Rate (BMR) using Mifflin-St Jeor Equation
            double bmr = calculateBMR(age, isMale, bmi);
            
            // Calculate Total Daily Energy Expenditure (TDEE)
            double tdee = calculateTDEE(bmr, userActivityLevel);
            
            // Adjust for health conditions
            tdee = adjustForHealthConditions(tdee, userHealthConditions, isPregnant);
            
            // Set suggested calories (rounded to nearest 50)
            suggestedCalories = (int) Math.round(tdee / 50.0) * 50;
            
            // Calculate macronutrient distribution based on WHO/Philippine guidelines
            calculateMacronutrients(suggestedCalories, age, isMale, isPregnant, userHealthConditions);
            
            // Calculate micronutrients
            calculateMicronutrients(age, isMale, isPregnant, userHealthConditions);
            
            Log.d(TAG, "Calculated nutrition targets - Calories: " + suggestedCalories + 
                  ", Protein: " + suggestedProtein + "g, Carbs: " + suggestedCarbs + "g, Fat: " + suggestedFat + "g");
            
        } catch (Exception e) {
            Log.e(TAG, "Error calculating nutrition targets: " + e.getMessage());
            setDefaultValues();
        }
    }
    
    /**
     * Calculate Basal Metabolic Rate using age-appropriate equations
     * Reference: WHO/FAO/UNU Expert Consultation on Energy and Protein Requirements
     */
    private double calculateBMR(int age, boolean isMale, double bmi) {
        // Estimate weight and height from BMI
        double height, weight;
        
        if (age < 2) {
            // For infants (0-2 years), use WHO growth standards
            height = getAverageHeightForAge(age);
            weight = bmi * Math.pow(height / 100.0, 2);
            return calculateInfantBMR(age, weight, height, isMale);
        } else if (age < 18) {
            // For children (2-17 years), use WHO growth standards
            height = getAverageHeightForAge(age);
            weight = bmi * Math.pow(height / 100.0, 2);
            return calculateChildBMR(age, weight, height, isMale);
        } else {
            // For adults (18+ years), use Mifflin-St Jeor Equation
            height = 165.0; // Average height for Filipino adults (cm)
            weight = bmi * Math.pow(height / 100.0, 2);
            
            if (isMale) {
                return (10 * weight) + (6.25 * height) - (5 * age) + 5;
            } else {
                return (10 * weight) + (6.25 * height) - (5 * age) - 161;
            }
        }
    }
    
    /**
     * Calculate BMR for infants (0-2 years)
     * Reference: WHO/FAO/UNU Expert Consultation on Energy and Protein Requirements
     */
    private double calculateInfantBMR(int age, double weight, double height, boolean isMale) {
        // For infants, BMR is calculated per kg body weight
        // Age 0-6 months: 55 kcal/kg/day
        // Age 6-12 months: 55 kcal/kg/day  
        // Age 12-24 months: 55 kcal/kg/day
        return weight * 55.0; // 55 kcal per kg per day for infants
    }
    
    /**
     * Calculate BMR for children (2-17 years)
     * Reference: WHO/FAO/UNU Expert Consultation on Energy and Protein Requirements
     */
    private double calculateChildBMR(int age, double weight, double height, boolean isMale) {
        // For children, use the Schofield equation
        if (age >= 3 && age <= 10) {
            if (isMale) {
                return (22.7 * weight) + 495;
            } else {
                return (22.5 * weight) + 499;
            }
        } else if (age >= 10 && age <= 18) {
            if (isMale) {
                return (17.5 * weight) + (651 * height / 100) + 137;
            } else {
                return (12.2 * weight) + (746 * height / 100) + 461;
            }
        } else {
            // Fallback for ages 2-3
            return weight * 60.0; // 60 kcal per kg per day
        }
    }
    
    /**
     * Get average height for age based on WHO growth standards
     */
    private double getAverageHeightForAge(int age) {
        // WHO growth standards for Filipino children
        if (age < 1) {
            return 70.0; // 6 months average height
        } else if (age < 2) {
            return 80.0; // 18 months average height
        } else if (age < 3) {
            return 90.0; // 2.5 years average height
        } else if (age < 4) {
            return 95.0; // 3.5 years average height
        } else if (age < 5) {
            return 100.0; // 4.5 years average height
        } else if (age < 6) {
            return 105.0; // 5.5 years average height
        } else if (age < 7) {
            return 110.0; // 6.5 years average height
        } else if (age < 8) {
            return 115.0; // 7.5 years average height
        } else if (age < 9) {
            return 120.0; // 8.5 years average height
        } else if (age < 10) {
            return 125.0; // 9.5 years average height
        } else if (age < 11) {
            return 130.0; // 10.5 years average height
        } else if (age < 12) {
            return 135.0; // 11.5 years average height
        } else if (age < 13) {
            return 140.0; // 12.5 years average height
        } else if (age < 14) {
            return 145.0; // 13.5 years average height
        } else if (age < 15) {
            return 150.0; // 14.5 years average height
        } else if (age < 16) {
            return 155.0; // 15.5 years average height
        } else if (age < 17) {
            return 160.0; // 16.5 years average height
        } else {
            return 165.0; // Adult average height
        }
    }
    
    /**
     * Calculate Total Daily Energy Expenditure
     * Reference: WHO Physical Activity Guidelines
     */
    private double calculateTDEE(double bmr, String activityLevel) {
        // For infants and young children, use age-appropriate activity factors
        int age = parseAge(userAge);
        
        if (age < 2) {
            // Infants have higher activity factors due to rapid growth
            return bmr * 1.8; // Higher activity factor for infants
        } else if (age < 6) {
            // Toddlers and preschoolers are very active
            return bmr * 1.7; // High activity factor for young children
        } else if (age < 12) {
            // School-age children are moderately active
            return bmr * 1.6; // Moderate-high activity factor
        } else if (age < 18) {
            // Adolescents can be very active
            return bmr * 1.5; // Moderate activity factor
        } else {
            // Adults use standard activity factors
            switch (activityLevel.toLowerCase()) {
                case "sedentary":
                    return bmr * 1.2;
                case "light":
                    return bmr * 1.375;
                case "moderate":
                    return bmr * 1.55;
                case "active":
                    return bmr * 1.725;
                case "very active":
                    return bmr * 1.9;
                default:
                    return bmr * 1.55; // Default to moderate activity
            }
        }
    }
    
    /**
     * Adjust calories based on health conditions
     * Reference: WHO Guidelines for Malnutrition Management
     */
    private double adjustForHealthConditions(double tdee, String healthConditions, boolean isPregnant) {
        if (isPregnant) {
            return tdee + 300; // Additional 300 calories during pregnancy
        }
        
        if (healthConditions != null && !healthConditions.equals("None")) {
            if (healthConditions.toLowerCase().contains("diabetes")) {
                return tdee * 0.9; // 10% reduction for diabetes management
            } else if (healthConditions.toLowerCase().contains("hypertension")) {
                return tdee * 0.95; // 5% reduction for hypertension
            } else if (healthConditions.toLowerCase().contains("obesity")) {
                return tdee * 0.8; // 20% reduction for weight management
            } else if (healthConditions.toLowerCase().contains("underweight")) {
                return tdee * 1.2; // 20% increase for weight gain
            }
        }
        
        return tdee;
    }
    
    /**
     * Calculate macronutrient distribution
     * Reference: WHO/FAO/UNU Expert Consultation on Protein and Amino Acid Requirements
     */
    private void calculateMacronutrients(int calories, int age, boolean isMale, boolean isPregnant, String healthConditions) {
        // Age-specific protein requirements
        if (age < 1) {
            // Infants: 1.2-1.5g per kg body weight
            suggestedProtein = (int) Math.round(calories * 0.15 / 4); // 15% of calories
        } else if (age < 3) {
            // Toddlers: 1.1-1.3g per kg body weight
            suggestedProtein = (int) Math.round(calories * 0.18 / 4); // 18% of calories
        } else if (age < 6) {
            // Preschoolers: 1.0-1.2g per kg body weight
            suggestedProtein = (int) Math.round(calories * 0.19 / 4); // 19% of calories
        } else if (age < 18) {
            // School-age children and adolescents: 0.9-1.1g per kg body weight
            suggestedProtein = (int) Math.round(calories * 0.2 / 4); // 20% of calories
        } else {
            // Adults: 15-20% of total calories
            suggestedProtein = (int) Math.round(calories * 0.175 / 4); // 17.5% average
        }
        
        if (isPregnant) {
            suggestedProtein += 25; // Additional 25g during pregnancy
        }
        
        // Age-specific carbohydrate requirements
        if (age < 2) {
            // Infants: 40-50% of calories from carbs
            suggestedCarbs = (int) Math.round(calories * 0.45 / 4); // 45% of calories
        } else if (age < 6) {
            // Toddlers and preschoolers: 45-55% of calories from carbs
            suggestedCarbs = (int) Math.round(calories * 0.5 / 4); // 50% of calories
        } else {
            // School-age children and adults: 45-65% of calories from carbs
            suggestedCarbs = (int) Math.round(calories * 0.55 / 4); // 55% average
        }
        
        // Adjust carbs for health conditions
        if (healthConditions != null && healthConditions.toLowerCase().contains("diabetes")) {
            suggestedCarbs = (int) Math.round((calories * 0.45) / 4); // 45% for diabetes
        }
        
        // Age-specific fat requirements
        if (age < 2) {
            // Infants: 30-40% of calories from fat (essential for brain development)
            suggestedFat = (int) Math.round(calories * 0.35 / 9); // 35% of calories
        } else if (age < 6) {
            // Toddlers and preschoolers: 25-35% of calories from fat
            suggestedFat = (int) Math.round(calories * 0.3 / 9); // 30% of calories
        } else {
            // School-age children and adults: 20-35% of calories from fat
            suggestedFat = (int) Math.round(calories * 0.275 / 9); // 27.5% average
        }
        
        // Age-specific fiber requirements
        if (age < 1) {
            suggestedFiber = 0; // No fiber for infants
        } else if (age < 3) {
            suggestedFiber = 5; // 5g for toddlers
        } else if (age < 6) {
            suggestedFiber = 10; // 10g for preschoolers
        } else if (age < 12) {
            suggestedFiber = 15; // 15g for school-age children
        } else if (age < 18) {
            suggestedFiber = 20; // 20g for adolescents
        } else {
            suggestedFiber = 30; // 30g for adults
        }
    }
    
    /**
     * Calculate micronutrient recommendations
     * Reference: Philippine Dietary Reference Intakes (PDRI) 2015
     */
    private void calculateMicronutrients(int age, boolean isMale, boolean isPregnant, String healthConditions) {
        // Sodium: WHO recommends <2g per day (2000mg)
        suggestedSodium = 2000;
        
        if (healthConditions != null && healthConditions.toLowerCase().contains("hypertension")) {
            suggestedSodium = 1500; // Lower sodium for hypertension
        }
        
        // Sugar: WHO recommends <10% of total calories from free sugars
        suggestedSugar = (int) Math.round(suggestedCalories * 0.1 / 4); // 10% of calories as sugar
    }
    
    /**
     * Set default values if calculation fails
     */
    private void setDefaultValues() {
        suggestedCalories = 2000;
        suggestedProtein = 75;
        suggestedCarbs = 250;
        suggestedFat = 65;
        suggestedFiber = 30;
        suggestedSodium = 2000;
        suggestedSugar = 50;
    }
    
    /**
     * Parse age from string
     */
    private int parseAge(String ageStr) {
        try {
            if (ageStr != null && !ageStr.isEmpty()) {
                return Integer.parseInt(ageStr.replaceAll("[^0-9]", ""));
            }
        } catch (NumberFormatException e) {
            Log.w(TAG, "Could not parse age: " + ageStr);
        }
        return 25; // Default age
    }
    
    /**
     * Parse BMI from string
     */
    private double parseBMI(String bmiStr) {
        try {
            if (bmiStr != null && !bmiStr.isEmpty()) {
                return Double.parseDouble(bmiStr.replaceAll("[^0-9.]", ""));
            }
        } catch (NumberFormatException e) {
            Log.w(TAG, "Could not parse BMI: " + bmiStr);
        }
        return 22.5; // Default BMI (normal weight)
    }
    
    /**
     * Get age-appropriate meal distribution
     */
    public Map<String, Integer> getMealDistribution() {
        Map<String, Integer> mealDistribution = new HashMap<>();
        int age = parseAge(userAge);
        
        if (age < 2) {
            // Infants: 6-8 small meals per day
            mealDistribution.put("breakfast", (int) Math.round(suggestedCalories * 0.15)); // 15%
            mealDistribution.put("lunch", (int) Math.round(suggestedCalories * 0.15)); // 15%
            mealDistribution.put("dinner", (int) Math.round(suggestedCalories * 0.15)); // 15%
            mealDistribution.put("snacks", (int) Math.round(suggestedCalories * 0.55)); // 55% (multiple small meals)
        } else if (age < 6) {
            // Toddlers and preschoolers: 5-6 meals per day
            mealDistribution.put("breakfast", (int) Math.round(suggestedCalories * 0.2)); // 20%
            mealDistribution.put("lunch", (int) Math.round(suggestedCalories * 0.25)); // 25%
            mealDistribution.put("dinner", (int) Math.round(suggestedCalories * 0.25)); // 25%
            mealDistribution.put("snacks", (int) Math.round(suggestedCalories * 0.3)); // 30%
        } else if (age < 12) {
            // School-age children: 4-5 meals per day
            mealDistribution.put("breakfast", (int) Math.round(suggestedCalories * 0.25)); // 25%
            mealDistribution.put("lunch", (int) Math.round(suggestedCalories * 0.3)); // 30%
            mealDistribution.put("dinner", (int) Math.round(suggestedCalories * 0.3)); // 30%
            mealDistribution.put("snacks", (int) Math.round(suggestedCalories * 0.15)); // 15%
        } else {
            // Adolescents and adults: 3-4 meals per day
            mealDistribution.put("breakfast", (int) Math.round(suggestedCalories * 0.25)); // 25%
            mealDistribution.put("lunch", (int) Math.round(suggestedCalories * 0.35)); // 35%
            mealDistribution.put("dinner", (int) Math.round(suggestedCalories * 0.3)); // 30%
            mealDistribution.put("snacks", (int) Math.round(suggestedCalories * 0.1)); // 10%
        }
        
        return mealDistribution;
    }

    // Getters for nutrition targets
    public int getSuggestedCalories() { return suggestedCalories; }
    public int getSuggestedProtein() { return suggestedProtein; }
    public int getSuggestedCarbs() { return suggestedCarbs; }
    public int getSuggestedFat() { return suggestedFat; }
    public int getSuggestedFiber() { return suggestedFiber; }
    public int getSuggestedSodium() { return suggestedSodium; }
    public int getSuggestedSugar() { return suggestedSugar; }
    
    /**
     * Get nutrition targets as a map for easy access
     */
    public Map<String, Integer> getNutritionTargets() {
        Map<String, Integer> targets = new HashMap<>();
        targets.put("calories", suggestedCalories);
        targets.put("protein", suggestedProtein);
        targets.put("carbs", suggestedCarbs);
        targets.put("fat", suggestedFat);
        targets.put("fiber", suggestedFiber);
        targets.put("sodium", suggestedSodium);
        targets.put("sugar", suggestedSugar);
        return targets;
    }
    
    /**
     * Get nutrition targets with units for display
     */
    public Map<String, String> getNutritionTargetsWithUnits() {
        Map<String, String> targets = new HashMap<>();
        targets.put("calories", suggestedCalories + " kcal");
        targets.put("protein", suggestedProtein + " g");
        targets.put("carbs", suggestedCarbs + " g");
        targets.put("fat", suggestedFat + " g");
        targets.put("fiber", suggestedFiber + " g");
        targets.put("sodium", suggestedSodium + " mg");
        targets.put("sugar", suggestedSugar + " g");
        return targets;
    }
    
    
    /**
     * Get references used for calculations
     */
    public String[] getReferences() {
        return new String[]{
            "WHO/FAO/UNU Expert Consultation on Protein and Amino Acid Requirements (2007)",
            "WHO Guidelines on Physical Activity, Sedentary Behaviour and Sleep (2019)",
            "Philippine Dietary Reference Intakes (PDRI) 2015",
            "Mifflin MD, et al. (1990). A new predictive equation for resting energy expenditure",
            "WHO Guidelines for the Management of Malnutrition (2019)"
        };
    }
}
