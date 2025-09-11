package com.example.nutrisaur11;

/**
 * User profile data for nutrition calculations
 */
public class UserProfile {
    private String userId;
    private String name;
    private int age;
    private String gender;
    private double weight; // in kg
    private double height; // in cm
    private double bmi;
    private String bmiCategory;
    private String activityLevel;
    private String healthGoals;
    private String dietaryPreferences;
    private String allergies;
    private String medicalConditions;
    private boolean isPregnant;
    private int pregnancyWeek;
    private String occupation;
    private String lifestyle;

    public UserProfile() {}

    public UserProfile(String userId, String name, int age, String gender, double weight, double height,
                      String activityLevel, String healthGoals, String dietaryPreferences, String allergies,
                      String medicalConditions, boolean isPregnant, int pregnancyWeek, String occupation, String lifestyle) {
        this.userId = userId;
        this.name = name;
        this.age = age;
        this.gender = gender;
        this.weight = weight;
        this.height = height;
        this.activityLevel = activityLevel;
        this.healthGoals = healthGoals;
        this.dietaryPreferences = dietaryPreferences;
        this.allergies = allergies;
        this.medicalConditions = medicalConditions;
        this.isPregnant = isPregnant;
        this.pregnancyWeek = pregnancyWeek;
        this.occupation = occupation;
        this.lifestyle = lifestyle;
        
        // Calculate BMI
        this.bmi = calculateBMI(weight, height);
        this.bmiCategory = getBMICategory(this.bmi);
    }

    /**
     * Calculate BMI from weight (kg) and height (cm)
     */
    private double calculateBMI(double weight, double height) {
        if (height <= 0) return 0;
        double heightInMeters = height / 100.0;
        return weight / (heightInMeters * heightInMeters);
    }

    /**
     * Get BMI category based on BMI value
     */
    private String getBMICategory(double bmi) {
        if (bmi < 18.5) return "Underweight";
        else if (bmi < 25) return "Normal weight";
        else if (bmi < 30) return "Overweight";
        else return "Obese";
    }

    /**
     * Check if user needs weight management
     */
    public boolean needsWeightManagement() {
        return bmi >= 25; // Overweight or obese
    }

    /**
     * Check if user is obese
     */
    public boolean isObese() {
        return bmi >= 30;
    }

    /**
     * Check if user is overweight
     */
    public boolean isOverweight() {
        return bmi >= 25 && bmi < 30;
    }

    /**
     * Get activity multiplier for calorie calculations
     */
    public double getActivityMultiplier() {
        switch (activityLevel.toLowerCase()) {
            case "sedentary":
                return 1.2;
            case "lightly active":
                return 1.375;
            case "moderately active":
                return 1.55;
            case "very active":
                return 1.725;
            case "extremely active":
                return 1.9;
            default:
                return 1.2;
        }
    }

    // Getters and Setters
    public String getUserId() { return userId; }
    public void setUserId(String userId) { this.userId = userId; }

    public String getName() { return name; }
    public void setName(String name) { this.name = name; }

    public int getAge() { return age; }
    public void setAge(int age) { this.age = age; }

    public String getGender() { return gender; }
    public void setGender(String gender) { this.gender = gender; }

    public double getWeight() { return weight; }
    public void setWeight(double weight) { 
        this.weight = weight; 
        this.bmi = calculateBMI(weight, height);
        this.bmiCategory = getBMICategory(this.bmi);
    }

    public double getHeight() { return height; }
    public void setHeight(double height) { 
        this.height = height; 
        this.bmi = calculateBMI(weight, height);
        this.bmiCategory = getBMICategory(this.bmi);
    }

    public double getBmi() { return bmi; }
    public String getBmiCategory() { return bmiCategory; }

    public String getActivityLevel() { return activityLevel; }
    public void setActivityLevel(String activityLevel) { this.activityLevel = activityLevel; }

    public String getHealthGoals() { return healthGoals; }
    public void setHealthGoals(String healthGoals) { this.healthGoals = healthGoals; }

    public String getDietaryPreferences() { return dietaryPreferences; }
    public void setDietaryPreferences(String dietaryPreferences) { this.dietaryPreferences = dietaryPreferences; }

    public String getAllergies() { return allergies; }
    public void setAllergies(String allergies) { this.allergies = allergies; }

    public String getMedicalConditions() { return medicalConditions; }
    public void setMedicalConditions(String medicalConditions) { this.medicalConditions = medicalConditions; }

    public boolean isPregnant() { return isPregnant; }
    public void setPregnant(boolean pregnant) { isPregnant = pregnant; }

    public int getPregnancyWeek() { return pregnancyWeek; }
    public void setPregnancyWeek(int pregnancyWeek) { this.pregnancyWeek = pregnancyWeek; }

    public String getOccupation() { return occupation; }
    public void setOccupation(String occupation) { this.occupation = occupation; }

    public String getLifestyle() { return lifestyle; }
    public void setLifestyle(String lifestyle) { this.lifestyle = lifestyle; }
}
