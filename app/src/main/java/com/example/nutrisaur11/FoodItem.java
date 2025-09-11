package com.example.nutrisaur11;

import java.io.Serializable;

public class FoodItem implements Serializable {
    private String id;
    private String name;
    private int calories;
    private int weight;
    private String unit;
    private String brand;
    private String description;
    private String imageUrl;
    
    public FoodItem() {}
    
    public FoodItem(String id, String name, int calories, int weight, String unit) {
        this.id = id;
        this.name = name;
        this.calories = calories;
        this.weight = weight;
        this.unit = unit;
    }
    
    // Getters and Setters
    public String getId() { return id; }
    public void setId(String id) { this.id = id; }
    
    public String getName() { return name; }
    public void setName(String name) { this.name = name; }
    
    public int getCalories() { return calories; }
    public void setCalories(int calories) { this.calories = calories; }
    
    public int getWeight() { return weight; }
    public void setWeight(int weight) { this.weight = weight; }
    
    public String getUnit() { return unit; }
    public void setUnit(String unit) { this.unit = unit; }
    
    public String getBrand() { return brand; }
    public void setBrand(String brand) { this.brand = brand; }
    
    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }
    
    public String getImageUrl() { return imageUrl; }
    public void setImageUrl(String imageUrl) { this.imageUrl = imageUrl; }
    
    public String getCaloriesText() {
        return calories + " kcal, " + weight + " " + unit;
    }
    
    public int getServingSizeGrams() {
        return weight;
    }
    
    @Override
    public String toString() {
        return name;
    }
}
