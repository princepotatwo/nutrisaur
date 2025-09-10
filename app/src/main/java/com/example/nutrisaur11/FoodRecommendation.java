package com.example.nutrisaur11;

public class FoodRecommendation {
    private String foodName;
    private int calories;
    private double protein;
    private double fat;
    private double carbs;
    private String servingSize;
    private String dietType;
    private String description;
    private String imageUrl;

    public FoodRecommendation() {
        // Default constructor
    }

    public FoodRecommendation(String foodName, int calories, double protein, double fat, double carbs, 
                             String servingSize, String dietType, String description) {
        this.foodName = foodName;
        this.calories = calories;
        this.protein = protein;
        this.fat = fat;
        this.carbs = carbs;
        this.servingSize = servingSize;
        this.dietType = dietType;
        this.description = description;
    }

    public FoodRecommendation(String foodName, int calories, double protein, double fat, double carbs, 
                             String servingSize, String dietType, String description, String imageUrl) {
        this.foodName = foodName;
        this.calories = calories;
        this.protein = protein;
        this.fat = fat;
        this.carbs = carbs;
        this.servingSize = servingSize;
        this.dietType = dietType;
        this.description = description;
        this.imageUrl = imageUrl;
    }

    // Getters and Setters
    public String getFoodName() {
        return foodName;
    }

    public void setFoodName(String foodName) {
        this.foodName = foodName;
    }

    public int getCalories() {
        return calories;
    }

    public void setCalories(int calories) {
        this.calories = calories;
    }

    public double getProtein() {
        return protein;
    }

    public void setProtein(double protein) {
        this.protein = protein;
    }

    public double getFat() {
        return fat;
    }

    public void setFat(double fat) {
        this.fat = fat;
    }

    public double getCarbs() {
        return carbs;
    }

    public void setCarbs(double carbs) {
        this.carbs = carbs;
    }

    public String getServingSize() {
        return servingSize;
    }

    public void setServingSize(String servingSize) {
        this.servingSize = servingSize;
    }

    public String getDietType() {
        return dietType;
    }

    public void setDietType(String dietType) {
        this.dietType = dietType;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public String getImageUrl() {
        return imageUrl;
    }

    public void setImageUrl(String imageUrl) {
        this.imageUrl = imageUrl;
    }
}
