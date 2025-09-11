package com.example.nutrisaur11;

import java.util.List;

/**
 * Data model for nutrition recommendations from Gemini API
 */
public class NutritionData {
    private int totalCalories;
    private int caloriesLeft;
    private int caloriesEaten;
    private int caloriesBurned;
    private Macronutrients macronutrients;
    private ActivityData activity;
    private MealDistribution mealDistribution;
    private String recommendation;
    private String healthStatus;
    private double bmi;
    private String bmiCategory;

    public NutritionData() {}

    public NutritionData(int totalCalories, int caloriesLeft, int caloriesEaten, int caloriesBurned,
                       Macronutrients macronutrients, ActivityData activity, MealDistribution mealDistribution,
                       String recommendation, String healthStatus, double bmi, String bmiCategory) {
        this.totalCalories = totalCalories;
        this.caloriesLeft = caloriesLeft;
        this.caloriesEaten = caloriesEaten;
        this.caloriesBurned = caloriesBurned;
        this.macronutrients = macronutrients;
        this.activity = activity;
        this.mealDistribution = mealDistribution;
        this.recommendation = recommendation;
        this.healthStatus = healthStatus;
        this.bmi = bmi;
        this.bmiCategory = bmiCategory;
    }

    // Getters and Setters
    public int getTotalCalories() { return totalCalories; }
    public void setTotalCalories(int totalCalories) { this.totalCalories = totalCalories; }

    public int getCaloriesLeft() { return caloriesLeft; }
    public void setCaloriesLeft(int caloriesLeft) { this.caloriesLeft = caloriesLeft; }

    public int getCaloriesEaten() { return caloriesEaten; }
    public void setCaloriesEaten(int caloriesEaten) { this.caloriesEaten = caloriesEaten; }

    public int getCaloriesBurned() { return caloriesBurned; }
    public void setCaloriesBurned(int caloriesBurned) { this.caloriesBurned = caloriesBurned; }

    public Macronutrients getMacronutrients() { return macronutrients; }
    public void setMacronutrients(Macronutrients macronutrients) { this.macronutrients = macronutrients; }

    public ActivityData getActivity() { return activity; }
    public void setActivity(ActivityData activity) { this.activity = activity; }

    public MealDistribution getMealDistribution() { return mealDistribution; }
    public void setMealDistribution(MealDistribution mealDistribution) { this.mealDistribution = mealDistribution; }

    public String getRecommendation() { return recommendation; }
    public void setRecommendation(String recommendation) { this.recommendation = recommendation; }

    public String getHealthStatus() { return healthStatus; }
    public void setHealthStatus(String healthStatus) { this.healthStatus = healthStatus; }

    public double getBmi() { return bmi; }
    public void setBmi(double bmi) { this.bmi = bmi; }

    public String getBmiCategory() { return bmiCategory; }
    public void setBmiCategory(String bmiCategory) { this.bmiCategory = bmiCategory; }

    /**
     * Macronutrients data
     */
    public static class Macronutrients {
        private int carbs;
        private int protein;
        private int fat;
        private int carbsTarget;
        private int proteinTarget;
        private int fatTarget;

        public Macronutrients() {}

        public Macronutrients(int carbs, int protein, int fat, int carbsTarget, int proteinTarget, int fatTarget) {
            this.carbs = carbs;
            this.protein = protein;
            this.fat = fat;
            this.carbsTarget = carbsTarget;
            this.proteinTarget = proteinTarget;
            this.fatTarget = fatTarget;
        }

        // Getters and Setters
        public int getCarbs() { return carbs; }
        public void setCarbs(int carbs) { this.carbs = carbs; }

        public int getProtein() { return protein; }
        public void setProtein(int protein) { this.protein = protein; }

        public int getFat() { return fat; }
        public void setFat(int fat) { this.fat = fat; }

        public int getCarbsTarget() { return carbsTarget; }
        public void setCarbsTarget(int carbsTarget) { this.carbsTarget = carbsTarget; }

        public int getProteinTarget() { return proteinTarget; }
        public void setProteinTarget(int proteinTarget) { this.proteinTarget = proteinTarget; }

        public int getFatTarget() { return fatTarget; }
        public void setFatTarget(int fatTarget) { this.fatTarget = fatTarget; }
    }

    /**
     * Activity data
     */
    public static class ActivityData {
        private int walkingCalories;
        private int activityCalories;
        private int totalBurned;

        public ActivityData() {}

        public ActivityData(int walkingCalories, int activityCalories, int totalBurned) {
            this.walkingCalories = walkingCalories;
            this.activityCalories = activityCalories;
            this.totalBurned = totalBurned;
        }

        // Getters and Setters
        public int getWalkingCalories() { return walkingCalories; }
        public void setWalkingCalories(int walkingCalories) { this.walkingCalories = walkingCalories; }

        public int getActivityCalories() { return activityCalories; }
        public void setActivityCalories(int activityCalories) { this.activityCalories = activityCalories; }

        public int getTotalBurned() { return totalBurned; }
        public void setTotalBurned(int totalBurned) { this.totalBurned = totalBurned; }
    }

    /**
     * Meal distribution data
     */
    public static class MealDistribution {
        private int breakfastCalories;
        private int lunchCalories;
        private int dinnerCalories;
        private int snacksCalories;
        private int breakfastEaten;
        private int lunchEaten;
        private int dinnerEaten;
        private int snacksEaten;
        private String breakfastRecommendation;
        private String lunchRecommendation;
        private String dinnerRecommendation;
        private String snacksRecommendation;

        public MealDistribution() {}

        public MealDistribution(int breakfastCalories, int lunchCalories, int dinnerCalories, int snacksCalories,
                              String breakfastRecommendation, String lunchRecommendation, 
                              String dinnerRecommendation, String snacksRecommendation) {
            this.breakfastCalories = breakfastCalories;
            this.lunchCalories = lunchCalories;
            this.dinnerCalories = dinnerCalories;
            this.snacksCalories = snacksCalories;
            this.breakfastEaten = 0;
            this.lunchEaten = 0;
            this.dinnerEaten = 0;
            this.snacksEaten = 0;
            this.breakfastRecommendation = breakfastRecommendation;
            this.lunchRecommendation = lunchRecommendation;
            this.dinnerRecommendation = dinnerRecommendation;
            this.snacksRecommendation = snacksRecommendation;
        }

        // Getters and Setters
        public int getBreakfastCalories() { return breakfastCalories; }
        public void setBreakfastCalories(int breakfastCalories) { this.breakfastCalories = breakfastCalories; }

        public int getLunchCalories() { return lunchCalories; }
        public void setLunchCalories(int lunchCalories) { this.lunchCalories = lunchCalories; }

        public int getDinnerCalories() { return dinnerCalories; }
        public void setDinnerCalories(int dinnerCalories) { this.dinnerCalories = dinnerCalories; }

        public int getSnacksCalories() { return snacksCalories; }
        public void setSnacksCalories(int snacksCalories) { this.snacksCalories = snacksCalories; }

        public int getBreakfastEaten() { return breakfastEaten; }
        public void setBreakfastEaten(int breakfastEaten) { this.breakfastEaten = breakfastEaten; }

        public int getLunchEaten() { return lunchEaten; }
        public void setLunchEaten(int lunchEaten) { this.lunchEaten = lunchEaten; }

        public int getDinnerEaten() { return dinnerEaten; }
        public void setDinnerEaten(int dinnerEaten) { this.dinnerEaten = dinnerEaten; }

        public int getSnacksEaten() { return snacksEaten; }
        public void setSnacksEaten(int snacksEaten) { this.snacksEaten = snacksEaten; }

        public String getBreakfastRecommendation() { return breakfastRecommendation; }
        public void setBreakfastRecommendation(String breakfastRecommendation) { this.breakfastRecommendation = breakfastRecommendation; }

        public String getLunchRecommendation() { return lunchRecommendation; }
        public void setLunchRecommendation(String lunchRecommendation) { this.lunchRecommendation = lunchRecommendation; }

        public String getDinnerRecommendation() { return dinnerRecommendation; }
        public void setDinnerRecommendation(String dinnerRecommendation) { this.dinnerRecommendation = dinnerRecommendation; }

        public String getSnacksRecommendation() { return snacksRecommendation; }
        public void setSnacksRecommendation(String snacksRecommendation) { this.snacksRecommendation = snacksRecommendation; }
    }
}
