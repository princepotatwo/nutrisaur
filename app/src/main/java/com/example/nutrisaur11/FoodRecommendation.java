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
        this.imageUrl = null;
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

    // Getters
    public String getFoodName() {
        return foodName;
    }

    public int getCalories() {
        return calories;
    }

    public double getProtein() {
        return protein;
    }

    public double getFat() {
        return fat;
    }

    public double getCarbs() {
        return carbs;
    }

    public String getServingSize() {
        return servingSize;
    }

    public String getDietType() {
        return dietType;
    }

    public String getDescription() {
        return description;
    }

    public String getImageUrl() {
        return imageUrl;
    }

    // Setters
    public void setFoodName(String foodName) {
        this.foodName = foodName;
    }

    public void setCalories(int calories) {
        this.calories = calories;
    }

    public void setProtein(double protein) {
        this.protein = protein;
    }

    public void setFat(double fat) {
        this.fat = fat;
    }

    public void setCarbs(double carbs) {
        this.carbs = carbs;
    }

    public void setServingSize(String servingSize) {
        this.servingSize = servingSize;
    }

    public void setDietType(String dietType) {
        this.dietType = dietType;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public void setImageUrl(String imageUrl) {
        this.imageUrl = imageUrl;
    }

    @Override
    public String toString() {
        return "FoodRecommendation{" +
                "foodName='" + foodName + '\'' +
                ", calories=" + calories +
                ", protein=" + protein +
                ", fat=" + fat +
                ", carbs=" + carbs +
                ", servingSize='" + servingSize + '\'' +
                ", dietType='" + dietType + '\'' +
                ", description='" + description + '\'' +
                ", imageUrl='" + imageUrl + '\'' +
                '}';
    }
}
