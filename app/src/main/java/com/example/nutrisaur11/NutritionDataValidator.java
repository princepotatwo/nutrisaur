package com.example.nutrisaur11;

import android.util.Log;

/**
 * Comprehensive Nutrition Data Validator
 * Validates all user input data for medical accuracy and realistic ranges
 */
public class NutritionDataValidator {
    private static final String TAG = "NutritionDataValidator";
    
    // Medical validation constants
    private static final double MIN_WEIGHT_KG = 0.5; // 500g minimum (premature baby)
    private static final double MAX_WEIGHT_KG = 1000.0; // 1000kg maximum (world record)
    private static final double MIN_HEIGHT_CM = 20.0; // 20cm minimum (premature baby)
    private static final double MAX_HEIGHT_CM = 300.0; // 300cm maximum (world record)
    private static final int MIN_AGE_YEARS = 0;
    private static final int MAX_AGE_YEARS = 150;
    private static final double MIN_BMI = 8.0; // Minimum possible BMI
    private static final double MAX_BMI = 100.0; // Maximum possible BMI
    
    // Age-specific weight/height ranges (WHO standards)
    private static final double[][] AGE_WEIGHT_RANGES = {
        // Age, Min Weight, Max Weight (kg)
        {0, 0.5, 4.5},     // 0-1 years
        {1, 7.0, 15.0},    // 1-2 years
        {2, 10.0, 20.0},   // 2-5 years
        {5, 15.0, 35.0},   // 5-10 years
        {10, 25.0, 70.0},  // 10-15 years
        {15, 40.0, 120.0}, // 15-18 years
        {18, 30.0, 200.0}  // 18+ years
    };
    
    private static final double[][] AGE_HEIGHT_RANGES = {
        // Age, Min Height, Max Height (cm)
        {0, 30.0, 80.0},   // 0-1 years
        {1, 70.0, 95.0},   // 1-2 years
        {2, 80.0, 120.0},  // 2-5 years
        {5, 100.0, 140.0}, // 5-10 years
        {10, 120.0, 180.0}, // 10-15 years
        {15, 140.0, 200.0}, // 15-18 years
        {18, 120.0, 250.0}  // 18+ years
    };
    
    public static class ValidationResult {
        public boolean isValid;
        public String errorMessage;
        public String warningMessage;
        public String correctedValue;
        public String recommendation;
        
        public ValidationResult(boolean isValid, String errorMessage) {
            this.isValid = isValid;
            this.errorMessage = errorMessage;
        }
        
        public ValidationResult(boolean isValid, String errorMessage, String warningMessage, 
                              String correctedValue, String recommendation) {
            this.isValid = isValid;
            this.errorMessage = errorMessage;
            this.warningMessage = warningMessage;
            this.correctedValue = correctedValue;
            this.recommendation = recommendation;
        }
    }
    
    /**
     * Validate complete user profile data
     */
    public static ValidationResult validateUserProfile(int age, double weight, double height, 
                                                     String gender, String healthConditions) {
        Log.d(TAG, "Validating user profile: Age=" + age + ", Weight=" + weight + 
              ", Height=" + height + ", Gender=" + gender);
        
        // Validate age
        ValidationResult ageResult = validateAge(age);
        if (!ageResult.isValid) {
            return ageResult;
        }
        
        // Validate weight
        ValidationResult weightResult = validateWeight(weight, age);
        if (!weightResult.isValid) {
            return weightResult;
        }
        
        // Validate height
        ValidationResult heightResult = validateHeight(height, age);
        if (!heightResult.isValid) {
            return heightResult;
        }
        
        // Validate BMI if both weight and height are valid
        if (weight > 0 && height > 0) {
            double bmi = calculateBMI(weight, height);
            ValidationResult bmiResult = validateBMI(bmi, age);
            if (!bmiResult.isValid) {
                return bmiResult;
            }
        }
        
        // Check for impossible combinations
        ValidationResult combinationResult = validateWeightHeightCombination(weight, height, age);
        if (!combinationResult.isValid) {
            return combinationResult;
        }
        
        return new ValidationResult(true, null, "All data appears valid", null, null);
    }
    
    /**
     * Validate age input
     */
    public static ValidationResult validateAge(int age) {
        if (age < MIN_AGE_YEARS) {
            return new ValidationResult(false, 
                "Invalid age: " + age + " years. Age must be at least " + MIN_AGE_YEARS + " years.",
                null, "0", "Please enter a valid age in years.");
        }
        
        if (age > MAX_AGE_YEARS) {
            return new ValidationResult(false, 
                "Invalid age: " + age + " years. Age must be less than " + MAX_AGE_YEARS + " years.",
                null, String.valueOf(MAX_AGE_YEARS - 1), "Please enter a realistic age.");
        }
        
        if (age > 120) {
            return new ValidationResult(true, null, 
                "Age " + age + " is extremely high. Please verify this is correct.", 
                null, "If this is correct, please consult a healthcare provider.");
        }
        
        return new ValidationResult(true, null, null, null, null);
    }
    
    /**
     * Validate weight input
     */
    public static ValidationResult validateWeight(double weight, int age) {
        if (weight <= 0) {
            return new ValidationResult(false, 
                "Invalid weight: " + weight + " kg. Weight must be greater than 0.",
                null, "1.0", "Please enter a valid weight in kilograms.");
        }
        
        if (weight < MIN_WEIGHT_KG) {
            return new ValidationResult(false, 
                "Invalid weight: " + weight + " kg. Weight is too low (minimum " + MIN_WEIGHT_KG + " kg).",
                null, String.valueOf(MIN_WEIGHT_KG), "Please enter a realistic weight.");
        }
        
        if (weight > MAX_WEIGHT_KG) {
            return new ValidationResult(false, 
                "Invalid weight: " + weight + " kg. Weight is too high (maximum " + MAX_WEIGHT_KG + " kg).",
                null, String.valueOf(MAX_WEIGHT_KG), "Please enter a realistic weight.");
        }
        
        // Age-specific weight validation
        ValidationResult ageWeightResult = validateAgeSpecificWeight(weight, age);
        if (!ageWeightResult.isValid) {
            return ageWeightResult;
        }
        
        if (weight > 200) {
            return new ValidationResult(true, null, 
                "Weight " + weight + " kg is very high. Please verify this is correct.", 
                null, "If this is correct, please consult a healthcare provider.");
        }
        
        return new ValidationResult(true, null, null, null, null);
    }
    
    /**
     * Validate height input
     */
    public static ValidationResult validateHeight(double height, int age) {
        if (height <= 0) {
            return new ValidationResult(false, 
                "Invalid height: " + height + " cm. Height must be greater than 0.",
                null, "50.0", "Please enter a valid height in centimeters.");
        }
        
        if (height < MIN_HEIGHT_CM) {
            return new ValidationResult(false, 
                "Invalid height: " + height + " cm. Height is too low (minimum " + MIN_HEIGHT_CM + " cm).",
                null, String.valueOf(MIN_HEIGHT_CM), "Please enter a realistic height.");
        }
        
        if (height > MAX_HEIGHT_CM) {
            return new ValidationResult(false, 
                "Invalid height: " + height + " cm. Height is too high (maximum " + MAX_HEIGHT_CM + " cm).",
                null, String.valueOf(MAX_HEIGHT_CM), "Please enter a realistic height.");
        }
        
        // Age-specific height validation
        ValidationResult ageHeightResult = validateAgeSpecificHeight(height, age);
        if (!ageHeightResult.isValid) {
            return ageHeightResult.isValid ? 
                new ValidationResult(true, null, ageHeightResult.warningMessage, null, ageHeightResult.recommendation) :
                ageHeightResult;
        }
        
        if (height > 250) {
            return new ValidationResult(true, null, 
                "Height " + height + " cm is very high. Please verify this is correct.", 
                null, "If this is correct, please consult a healthcare provider.");
        }
        
        return new ValidationResult(true, null, null, null, null);
    }
    
    /**
     * Validate BMI calculation
     */
    public static ValidationResult validateBMI(double bmi, int age) {
        if (bmi < MIN_BMI) {
            return new ValidationResult(false, 
                "Invalid BMI: " + String.format("%.1f", bmi) + ". BMI is too low (minimum " + MIN_BMI + ").",
                null, String.valueOf(MIN_BMI), "Please check your weight and height measurements.");
        }
        
        if (bmi > MAX_BMI) {
            return new ValidationResult(false, 
                "Invalid BMI: " + String.format("%.1f", bmi) + ". BMI is too high (maximum " + MAX_BMI + ").",
                null, String.valueOf(MAX_BMI), "Please check your weight and height measurements.");
        }
        
        // Age-specific BMI validation
        if (age < 18) {
            // For children, BMI ranges are different
            if (bmi < 10 || bmi > 50) {
                return new ValidationResult(false, 
                    "Invalid BMI for age " + age + ": " + String.format("%.1f", bmi) + 
                    ". BMI for children should be between 10-50.",
                    null, "15.0", "Please verify weight and height measurements for this child.");
            }
        } else {
            // For adults, standard BMI ranges
            if (bmi < 12 || bmi > 60) {
                return new ValidationResult(false, 
                    "Invalid BMI: " + String.format("%.1f", bmi) + 
                    ". BMI should be between 12-60 for adults.",
                    null, "22.0", "Please verify your weight and height measurements.");
            }
        }
        
        return new ValidationResult(true, null, null, null, null);
    }
    
    /**
     * Validate age-specific weight ranges
     */
    private static ValidationResult validateAgeSpecificWeight(double weight, int age) {
        for (int i = 0; i < AGE_WEIGHT_RANGES.length; i++) {
            double[] range = AGE_WEIGHT_RANGES[i];
            int minAge = (int) range[0];
            int maxAge = i < AGE_WEIGHT_RANGES.length - 1 ? (int) AGE_WEIGHT_RANGES[i + 1][0] : 200;
            
            if (age >= minAge && age < maxAge) {
                double minWeight = range[1];
                double maxWeight = range[2];
                
                if (weight < minWeight) {
                    return new ValidationResult(false, 
                        "Weight " + weight + " kg is too low for age " + age + 
                        " (expected range: " + minWeight + "-" + maxWeight + " kg).",
                        null, String.valueOf(minWeight), 
                        "Please verify weight measurement or consult a healthcare provider.");
                }
                
                if (weight > maxWeight) {
                    return new ValidationResult(false, 
                        "Weight " + weight + " kg is too high for age " + age + 
                        " (expected range: " + minWeight + "-" + maxWeight + " kg).",
                        null, String.valueOf(maxWeight), 
                        "Please verify weight measurement or consult a healthcare provider.");
                }
                break;
            }
        }
        
        return new ValidationResult(true, null, null, null, null);
    }
    
    /**
     * Validate age-specific height ranges
     */
    private static ValidationResult validateAgeSpecificHeight(double height, int age) {
        for (int i = 0; i < AGE_HEIGHT_RANGES.length; i++) {
            double[] range = AGE_HEIGHT_RANGES[i];
            int minAge = (int) range[0];
            int maxAge = i < AGE_HEIGHT_RANGES.length - 1 ? (int) AGE_HEIGHT_RANGES[i + 1][0] : 200;
            
            if (age >= minAge && age < maxAge) {
                double minHeight = range[1];
                double maxHeight = range[2];
                
                if (height < minHeight) {
                    return new ValidationResult(false, 
                        "Height " + height + " cm is too low for age " + age + 
                        " (expected range: " + minHeight + "-" + maxHeight + " cm).",
                        null, String.valueOf(minHeight), 
                        "Please verify height measurement or consult a healthcare provider.");
                }
                
                if (height > maxHeight) {
                    return new ValidationResult(false, 
                        "Height " + height + " cm is too high for age " + age + 
                        " (expected range: " + minHeight + "-" + maxHeight + " cm).",
                        null, String.valueOf(maxHeight), 
                        "Please verify height measurement or consult a healthcare provider.");
                }
                break;
            }
        }
        
        return new ValidationResult(true, null, null, null, null);
    }
    
    /**
     * Validate weight-height combination for impossible scenarios
     */
    private static ValidationResult validateWeightHeightCombination(double weight, double height, int age) {
        // Check for impossible BMI scenarios
        double bmi = calculateBMI(weight, height);
        
        // Example: 1 year old with 10000kg weight and 10000cm height
        if (age == 1 && weight > 1000 && height > 1000) {
            return new ValidationResult(false, 
                "Impossible measurements detected: Age " + age + ", Weight " + weight + 
                " kg, Height " + height + " cm. These values are not medically possible.",
                null, "10.0", "Please enter realistic measurements for a 1-year-old child.");
        }
        
        // Check for extremely high BMI that would be impossible
        if (bmi > 80) {
            return new ValidationResult(false, 
                "Impossible BMI detected: " + String.format("%.1f", bmi) + 
                ". This combination of weight and height is not medically possible.",
                null, "25.0", "Please verify your weight and height measurements.");
        }
        
        // Check for extremely low BMI that would be impossible
        if (bmi < 5) {
            return new ValidationResult(false, 
                "Impossible BMI detected: " + String.format("%.1f", bmi) + 
                ". This combination of weight and height is not medically possible.",
                null, "20.0", "Please verify your weight and height measurements.");
        }
        
        return new ValidationResult(true, null, null, null, null);
    }
    
    /**
     * Calculate BMI
     */
    private static double calculateBMI(double weight, double height) {
        if (height <= 0) return 0;
        double heightInMeters = height / 100.0;
        return weight / (heightInMeters * heightInMeters);
    }
    
    /**
     * Get validation summary for logging
     */
    public static String getValidationSummary(ValidationResult result) {
        if (result.isValid) {
            return "✅ Validation passed" + 
                   (result.warningMessage != null ? " (Warning: " + result.warningMessage + ")" : "");
        } else {
            return "❌ Validation failed: " + result.errorMessage + 
                   (result.recommendation != null ? " (Recommendation: " + result.recommendation + ")" : "");
        }
    }
}
