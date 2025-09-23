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
    
    // Comprehensive age-specific weight/height ranges for malnutrition screening
    // MINIMUM = Boundary between impossible and severe malnutrition
    // MAXIMUM = Boundary between impossible and severe obesity
    // Covers every age from 0 months to 100+ years
    private static final double[][] AGE_WEIGHT_RANGES = {
        // Age in years, Min Weight, Max Weight (kg) - Boundary between possible/impossible
        {0, 0.3, 5.5},       // 0-1 years (infants: 0-12 months) - Impossible < 0.3 kg or > 5.5 kg
        {1, 4.0, 18.0},      // 1-2 years (toddlers: 12-24 months) - Impossible < 4.0 kg or > 18.0 kg
        {2, 6.0, 25.0},      // 2-3 years - Impossible < 6.0 kg or > 25.0 kg
        {3, 8.0, 35.0},      // 3-4 years - Impossible < 8.0 kg or > 35.0 kg
        {4, 10.0, 45.0},     // 4-5 years - Impossible < 10.0 kg or > 45.0 kg
        {5, 12.0, 55.0},     // 5-6 years - Impossible < 12.0 kg or > 55.0 kg
        {6, 14.0, 65.0},     // 6-7 years - Impossible < 14.0 kg or > 65.0 kg
        {7, 16.0, 75.0},     // 7-8 years - Impossible < 16.0 kg or > 75.0 kg
        {8, 18.0, 85.0},     // 8-9 years - Impossible < 18.0 kg or > 85.0 kg
        {9, 20.0, 95.0},     // 9-10 years - Impossible < 20.0 kg or > 95.0 kg
        {10, 22.0, 110.0},   // 10-11 years - Impossible < 22.0 kg or > 110.0 kg
        {11, 24.0, 130.0},   // 11-12 years - Impossible < 24.0 kg or > 130.0 kg
        {12, 26.0, 150.0},   // 12-13 years - Impossible < 26.0 kg or > 150.0 kg
        {13, 28.0, 170.0},   // 13-14 years - Impossible < 28.0 kg or > 170.0 kg
        {14, 30.0, 190.0},   // 14-15 years - Impossible < 30.0 kg or > 190.0 kg
        {15, 32.0, 210.0},   // 15-16 years - Impossible < 32.0 kg or > 210.0 kg
        {16, 34.0, 230.0},   // 16-17 years - Impossible < 34.0 kg or > 230.0 kg
        {17, 36.0, 250.0},   // 17-18 years - Impossible < 36.0 kg or > 250.0 kg
        {18, 25.0, 280.0},   // 18-20 years - Impossible < 25.0 kg or > 280.0 kg
        {20, 30.0, 280.0},   // 20-25 years - Impossible < 30.0 kg or > 280.0 kg
        {25, 35.0, 280.0},   // 25-30 years - Impossible < 35.0 kg or > 280.0 kg
        {30, 40.0, 280.0},   // 30-35 years - Impossible < 40.0 kg or > 280.0 kg
        {35, 45.0, 280.0},   // 35-40 years - Impossible < 45.0 kg or > 280.0 kg
        {40, 50.0, 280.0},   // 40-45 years - Impossible < 50.0 kg or > 280.0 kg
        {45, 55.0, 280.0},   // 45-50 years - Impossible < 55.0 kg or > 280.0 kg
        {50, 60.0, 280.0},   // 50-55 years - Impossible < 60.0 kg or > 280.0 kg
        {55, 65.0, 280.0},   // 55-60 years - Impossible < 65.0 kg or > 280.0 kg
        {60, 60.0, 280.0},   // 60-65 years - Impossible < 60.0 kg or > 280.0 kg
        {65, 55.0, 280.0},   // 65-70 years - Impossible < 55.0 kg or > 280.0 kg
        {70, 50.0, 280.0},   // 70-75 years - Impossible < 50.0 kg or > 280.0 kg
        {75, 45.0, 280.0},   // 75-80 years - Impossible < 45.0 kg or > 280.0 kg
        {80, 40.0, 280.0},   // 80-85 years - Impossible < 40.0 kg or > 280.0 kg
        {85, 35.0, 280.0},   // 85-90 years - Impossible < 35.0 kg or > 280.0 kg
        {90, 30.0, 280.0},   // 90-95 years - Impossible < 30.0 kg or > 280.0 kg
        {95, 25.0, 280.0},   // 95-100 years - Impossible < 25.0 kg or > 280.0 kg
        {100, 20.0, 280.0}   // 100+ years - Impossible < 20.0 kg or > 280.0 kg
    };
    
    private static final double[][] AGE_HEIGHT_RANGES = {
        // Age in years, Min Height, Max Height (cm) - Boundary between possible/impossible
        {0, 25.0, 90.0},     // 0-1 years (infants: 0-12 months) - Impossible < 25.0 cm or > 90.0 cm
        {1, 50.0, 110.0},    // 1-2 years (toddlers: 12-24 months) - Impossible < 50.0 cm or > 110.0 cm
        {2, 60.0, 120.0},    // 2-3 years - Impossible < 60.0 cm or > 120.0 cm
        {3, 70.0, 130.0},    // 3-4 years - Impossible < 70.0 cm or > 130.0 cm
        {4, 75.0, 140.0},    // 4-5 years - Impossible < 75.0 cm or > 140.0 cm
        {5, 80.0, 150.0},    // 5-6 years - Impossible < 80.0 cm or > 150.0 cm
        {6, 85.0, 160.0},    // 6-7 years - Impossible < 85.0 cm or > 160.0 cm
        {7, 90.0, 170.0},    // 7-8 years - Impossible < 90.0 cm or > 170.0 cm
        {8, 95.0, 180.0},    // 8-9 years - Impossible < 95.0 cm or > 180.0 cm
        {9, 100.0, 190.0},   // 9-10 years - Impossible < 100.0 cm or > 190.0 cm
        {10, 105.0, 200.0},  // 10-11 years - Impossible < 105.0 cm or > 200.0 cm
        {11, 110.0, 210.0},  // 11-12 years - Impossible < 110.0 cm or > 210.0 cm
        {12, 115.0, 220.0},  // 12-13 years - Impossible < 115.0 cm or > 220.0 cm
        {13, 120.0, 230.0},  // 13-14 years - Impossible < 120.0 cm or > 230.0 cm
        {14, 125.0, 240.0},  // 14-15 years - Impossible < 125.0 cm or > 240.0 cm
        {15, 130.0, 250.0},  // 15-16 years - Impossible < 130.0 cm or > 250.0 cm
        {16, 135.0, 260.0},  // 16-17 years - Impossible < 135.0 cm or > 260.0 cm
        {17, 140.0, 270.0},  // 17-18 years - Impossible < 140.0 cm or > 270.0 cm
        {18, 100.0, 280.0},  // 18-20 years - Impossible < 100.0 cm or > 280.0 cm
        {20, 110.0, 280.0},  // 20-25 years - Impossible < 110.0 cm or > 280.0 cm
        {25, 120.0, 280.0},  // 25-30 years - Impossible < 120.0 cm or > 280.0 cm
        {30, 130.0, 280.0},  // 30-35 years - Impossible < 130.0 cm or > 280.0 cm
        {35, 140.0, 280.0},  // 35-40 years - Impossible < 140.0 cm or > 280.0 cm
        {40, 150.0, 280.0},  // 40-45 years - Impossible < 150.0 cm or > 280.0 cm
        {45, 160.0, 280.0},  // 45-50 years - Impossible < 160.0 cm or > 280.0 cm
        {50, 170.0, 280.0},  // 50-55 years - Impossible < 170.0 cm or > 280.0 cm
        {55, 180.0, 280.0},  // 55-60 years - Impossible < 180.0 cm or > 280.0 cm
        {60, 170.0, 280.0},  // 60-65 years - Impossible < 170.0 cm or > 280.0 cm
        {65, 160.0, 280.0},  // 65-70 years - Impossible < 160.0 cm or > 280.0 cm
        {70, 150.0, 280.0},  // 70-75 years - Impossible < 150.0 cm or > 280.0 cm
        {75, 140.0, 280.0},  // 75-80 years - Impossible < 140.0 cm or > 280.0 cm
        {80, 130.0, 280.0},  // 80-85 years - Impossible < 130.0 cm or > 280.0 cm
        {85, 120.0, 280.0},  // 85-90 years - Impossible < 120.0 cm or > 280.0 cm
        {90, 110.0, 280.0},  // 90-95 years - Impossible < 110.0 cm or > 280.0 cm
        {95, 100.0, 280.0},  // 95-100 years - Impossible < 100.0 cm or > 280.0 cm
        {100, 90.0, 280.0}   // 100+ years - Impossible < 90.0 cm or > 280.0 cm
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
        // Special handling for infants (0-12 months) - more precise ranges
        if (age == 0) {
            // For 0-year-olds (infants 0-12 months)
            double minWeight = 0.5;
            double maxWeight = 4.5;
            
            if (weight < minWeight) {
                return new ValidationResult(false, 
                    "Weight " + weight + " kg is impossible for age " + age + " (infant 0-12 months)" +
                    " (possible range: " + minWeight + "-" + maxWeight + " kg).",
                    null, String.valueOf(minWeight), 
                    "This weight is medically impossible. Please verify measurement or consult a healthcare provider.");
            }
            
            if (weight > maxWeight) {
                return new ValidationResult(false, 
                    "Weight " + weight + " kg is impossible for age " + age + " (infant 0-12 months)" +
                    " (possible range: " + minWeight + "-" + maxWeight + " kg).",
                    null, String.valueOf(maxWeight), 
                    "This weight is medically impossible. Please verify measurement or consult a healthcare provider.");
            }
            return new ValidationResult(true, null, null, null, null);
        }
        
        // Handle all other ages using the comprehensive ranges
        for (int i = 0; i < AGE_WEIGHT_RANGES.length; i++) {
            double[] range = AGE_WEIGHT_RANGES[i];
            int minAge = (int) range[0];
            int maxAge = i < AGE_WEIGHT_RANGES.length - 1 ? (int) AGE_WEIGHT_RANGES[i + 1][0] : 200;
            
            if (age >= minAge && age < maxAge) {
                double minWeight = range[1];
                double maxWeight = range[2];
                
                if (weight < minWeight) {
                    return new ValidationResult(false, 
                        "Weight " + weight + " kg is impossible for age " + age + 
                        " (possible range: " + minWeight + "-" + maxWeight + " kg).",
                        null, String.valueOf(minWeight), 
                        "This weight is medically impossible. Please verify measurement or consult a healthcare provider.");
                }
                
                if (weight > maxWeight) {
                    return new ValidationResult(false, 
                        "Weight " + weight + " kg is impossible for age " + age + 
                        " (possible range: " + minWeight + "-" + maxWeight + " kg).",
                        null, String.valueOf(maxWeight), 
                        "This weight is medically impossible. Please verify measurement or consult a healthcare provider.");
                }
                break;
            }
        }
        
        // Handle edge case for very high ages (100+)
        if (age >= 100) {
            double minWeight = 25.0;
            double maxWeight = 200.0;
            
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
        }
        
        return new ValidationResult(true, null, null, null, null);
    }
    
    /**
     * Validate age-specific height ranges
     */
    private static ValidationResult validateAgeSpecificHeight(double height, int age) {
        // Special handling for infants (0-12 months) - more precise ranges
        if (age == 0) {
            // For 0-year-olds (infants 0-12 months)
            double minHeight = 30.0;
            double maxHeight = 80.0;
            
            if (height < minHeight) {
                return new ValidationResult(false, 
                    "Height " + height + " cm is impossible for age " + age + " (infant 0-12 months)" +
                    " (possible range: " + minHeight + "-" + maxHeight + " cm).",
                    null, String.valueOf(minHeight), 
                    "This height is medically impossible. Please verify measurement or consult a healthcare provider.");
            }
            
            if (height > maxHeight) {
                return new ValidationResult(false, 
                    "Height " + height + " cm is impossible for age " + age + " (infant 0-12 months)" +
                    " (possible range: " + minHeight + "-" + maxHeight + " cm).",
                    null, String.valueOf(maxHeight), 
                    "This height is medically impossible. Please verify measurement or consult a healthcare provider.");
            }
            return new ValidationResult(true, null, null, null, null);
        }
        
        // Handle all other ages using the comprehensive ranges
        for (int i = 0; i < AGE_HEIGHT_RANGES.length; i++) {
            double[] range = AGE_HEIGHT_RANGES[i];
            int minAge = (int) range[0];
            int maxAge = i < AGE_HEIGHT_RANGES.length - 1 ? (int) AGE_HEIGHT_RANGES[i + 1][0] : 200;
            
            if (age >= minAge && age < maxAge) {
                double minHeight = range[1];
                double maxHeight = range[2];
                
                if (height < minHeight) {
                    return new ValidationResult(false, 
                        "Height " + height + " cm is impossible for age " + age + 
                        " (possible range: " + minHeight + "-" + maxHeight + " cm).",
                        null, String.valueOf(minHeight), 
                        "This height is medically impossible. Please verify measurement or consult a healthcare provider.");
                }
                
                if (height > maxHeight) {
                    return new ValidationResult(false, 
                        "Height " + height + " cm is impossible for age " + age + 
                        " (possible range: " + minHeight + "-" + maxHeight + " cm).",
                        null, String.valueOf(maxHeight), 
                        "This height is medically impossible. Please verify measurement or consult a healthcare provider.");
                }
                break;
            }
        }
        
        // Handle edge case for very high ages (100+)
        if (age >= 100) {
            double minHeight = 120.0;
            double maxHeight = 250.0;
            
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
    
    /**
     * Quick validation for real-time input checking (used in screening)
     */
    public static ValidationResult quickValidateWeight(double weight, int age) {
        // Basic range check first
        if (weight <= 0) {
            return new ValidationResult(false, "Weight must be greater than 0 kg");
        }
        
        if (weight > 1000) {
            return new ValidationResult(false, "Weight cannot exceed 1000 kg");
        }
        
        // Age-specific validation
        return validateAgeSpecificWeight(weight, age);
    }
    
    /**
     * Quick validation for real-time input checking (used in screening)
     */
    public static ValidationResult quickValidateHeight(double height, int age) {
        // Basic range check first
        if (height <= 0) {
            return new ValidationResult(false, "Height must be greater than 0 cm");
        }
        
        if (height > 300) {
            return new ValidationResult(false, "Height cannot exceed 300 cm");
        }
        
        // Age-specific validation
        return validateAgeSpecificHeight(height, age);
    }
}
