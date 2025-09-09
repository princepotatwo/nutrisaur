package com.example.nutrisaur11;

import java.util.List;
import java.util.ArrayList;

/**
 * Detailed food information including nutrients and ingredients
 */
public class FoodDetails {
    private String foodName;
    private String description;
    private String servingSize;
    private String cookingMethod;
    private String cuisine;
    private String difficulty;
    private int prepTime; // in minutes
    private int cookTime; // in minutes
    private int totalTime; // in minutes
    private int servings;
    
    // Nutritional information
    private int calories;
    private double protein;
    private double fat;
    private double carbs;
    private double fiber;
    private double sugar;
    private double sodium;
    private double cholesterol;
    private double saturatedFat;
    private double transFat;
    private double monounsaturatedFat;
    private double polyunsaturatedFat;
    private double omega3;
    private double omega6;
    private double vitaminA;
    private double vitaminC;
    private double vitaminD;
    private double vitaminE;
    private double vitaminK;
    private double thiamine;
    private double riboflavin;
    private double niacin;
    private double vitaminB6;
    private double folate;
    private double vitaminB12;
    private double biotin;
    private double pantothenicAcid;
    private double calcium;
    private double iron;
    private double magnesium;
    private double phosphorus;
    private double potassium;
    private double zinc;
    private double copper;
    private double manganese;
    private double selenium;
    private double iodine;
    
    // Ingredients
    private List<Ingredient> ingredients;
    
    // Allergens
    private List<String> allergens;
    
    // Dietary tags
    private List<String> dietaryTags; // e.g., "vegetarian", "gluten-free", "dairy-free"
    
    // Health benefits
    private List<String> healthBenefits;
    
    // Storage instructions
    private String storageInstructions;
    
    // Reheating instructions
    private String reheatingInstructions;
    
    public FoodDetails() {
        this.ingredients = new ArrayList<>();
        this.allergens = new ArrayList<>();
        this.dietaryTags = new ArrayList<>();
        this.healthBenefits = new ArrayList<>();
    }
    
    // Getters and Setters
    public String getFoodName() { return foodName; }
    public void setFoodName(String foodName) { this.foodName = foodName; }
    
    public String getDescription() { return description; }
    public void setDescription(String description) { this.description = description; }
    
    public String getServingSize() { return servingSize; }
    public void setServingSize(String servingSize) { this.servingSize = servingSize; }
    
    public String getCookingMethod() { return cookingMethod; }
    public void setCookingMethod(String cookingMethod) { this.cookingMethod = cookingMethod; }
    
    public String getCuisine() { return cuisine; }
    public void setCuisine(String cuisine) { this.cuisine = cuisine; }
    
    public String getDifficulty() { return difficulty; }
    public void setDifficulty(String difficulty) { this.difficulty = difficulty; }
    
    public int getPrepTime() { return prepTime; }
    public void setPrepTime(int prepTime) { this.prepTime = prepTime; }
    
    public int getCookTime() { return cookTime; }
    public void setCookTime(int cookTime) { this.cookTime = cookTime; }
    
    public int getTotalTime() { return totalTime; }
    public void setTotalTime(int totalTime) { this.totalTime = totalTime; }
    
    public int getServings() { return servings; }
    public void setServings(int servings) { this.servings = servings; }
    
    // Nutritional getters and setters
    public int getCalories() { return calories; }
    public void setCalories(int calories) { this.calories = calories; }
    
    public double getProtein() { return protein; }
    public void setProtein(double protein) { this.protein = protein; }
    
    public double getFat() { return fat; }
    public void setFat(double fat) { this.fat = fat; }
    
    public double getCarbs() { return carbs; }
    public void setCarbs(double carbs) { this.carbs = carbs; }
    
    public double getFiber() { return fiber; }
    public void setFiber(double fiber) { this.fiber = fiber; }
    
    public double getSugar() { return sugar; }
    public void setSugar(double sugar) { this.sugar = sugar; }
    
    public double getSodium() { return sodium; }
    public void setSodium(double sodium) { this.sodium = sodium; }
    
    public double getCholesterol() { return cholesterol; }
    public void setCholesterol(double cholesterol) { this.cholesterol = cholesterol; }
    
    public double getSaturatedFat() { return saturatedFat; }
    public void setSaturatedFat(double saturatedFat) { this.saturatedFat = saturatedFat; }
    
    public double getTransFat() { return transFat; }
    public void setTransFat(double transFat) { this.transFat = transFat; }
    
    public double getMonounsaturatedFat() { return monounsaturatedFat; }
    public void setMonounsaturatedFat(double monounsaturatedFat) { this.monounsaturatedFat = monounsaturatedFat; }
    
    public double getPolyunsaturatedFat() { return polyunsaturatedFat; }
    public void setPolyunsaturatedFat(double polyunsaturatedFat) { this.polyunsaturatedFat = polyunsaturatedFat; }
    
    public double getOmega3() { return omega3; }
    public void setOmega3(double omega3) { this.omega3 = omega3; }
    
    public double getOmega6() { return omega6; }
    public void setOmega6(double omega6) { this.omega6 = omega6; }
    
    // Vitamin getters and setters
    public double getVitaminA() { return vitaminA; }
    public void setVitaminA(double vitaminA) { this.vitaminA = vitaminA; }
    
    public double getVitaminC() { return vitaminC; }
    public void setVitaminC(double vitaminC) { this.vitaminC = vitaminC; }
    
    public double getVitaminD() { return vitaminD; }
    public void setVitaminD(double vitaminD) { this.vitaminD = vitaminD; }
    
    public double getVitaminE() { return vitaminE; }
    public void setVitaminE(double vitaminE) { this.vitaminE = vitaminE; }
    
    public double getVitaminK() { return vitaminK; }
    public void setVitaminK(double vitaminK) { this.vitaminK = vitaminK; }
    
    public double getThiamine() { return thiamine; }
    public void setThiamine(double thiamine) { this.thiamine = thiamine; }
    
    public double getRiboflavin() { return riboflavin; }
    public void setRiboflavin(double riboflavin) { this.riboflavin = riboflavin; }
    
    public double getNiacin() { return niacin; }
    public void setNiacin(double niacin) { this.niacin = niacin; }
    
    public double getVitaminB6() { return vitaminB6; }
    public void setVitaminB6(double vitaminB6) { this.vitaminB6 = vitaminB6; }
    
    public double getFolate() { return folate; }
    public void setFolate(double folate) { this.folate = folate; }
    
    public double getVitaminB12() { return vitaminB12; }
    public void setVitaminB12(double vitaminB12) { this.vitaminB12 = vitaminB12; }
    
    public double getBiotin() { return biotin; }
    public void setBiotin(double biotin) { this.biotin = biotin; }
    
    public double getPantothenicAcid() { return pantothenicAcid; }
    public void setPantothenicAcid(double pantothenicAcid) { this.pantothenicAcid = pantothenicAcid; }
    
    // Mineral getters and setters
    public double getCalcium() { return calcium; }
    public void setCalcium(double calcium) { this.calcium = calcium; }
    
    public double getIron() { return iron; }
    public void setIron(double iron) { this.iron = iron; }
    
    public double getMagnesium() { return magnesium; }
    public void setMagnesium(double magnesium) { this.magnesium = magnesium; }
    
    public double getPhosphorus() { return phosphorus; }
    public void setPhosphorus(double phosphorus) { this.phosphorus = phosphorus; }
    
    public double getPotassium() { return potassium; }
    public void setPotassium(double potassium) { this.potassium = potassium; }
    
    public double getZinc() { return zinc; }
    public void setZinc(double zinc) { this.zinc = zinc; }
    
    public double getCopper() { return copper; }
    public void setCopper(double copper) { this.copper = copper; }
    
    public double getManganese() { return manganese; }
    public void setManganese(double manganese) { this.manganese = manganese; }
    
    public double getSelenium() { return selenium; }
    public void setSelenium(double selenium) { this.selenium = selenium; }
    
    public double getIodine() { return iodine; }
    public void setIodine(double iodine) { this.iodine = iodine; }
    
    // List getters and setters
    public List<Ingredient> getIngredients() { return ingredients; }
    public void setIngredients(List<Ingredient> ingredients) { this.ingredients = ingredients; }
    
    public List<String> getAllergens() { return allergens; }
    public void setAllergens(List<String> allergens) { this.allergens = allergens; }
    
    public List<String> getDietaryTags() { return dietaryTags; }
    public void setDietaryTags(List<String> dietaryTags) { this.dietaryTags = dietaryTags; }
    
    public List<String> getHealthBenefits() { return healthBenefits; }
    public void setHealthBenefits(List<String> healthBenefits) { this.healthBenefits = healthBenefits; }
    
    public String getStorageInstructions() { return storageInstructions; }
    public void setStorageInstructions(String storageInstructions) { this.storageInstructions = storageInstructions; }
    
    public String getReheatingInstructions() { return reheatingInstructions; }
    public void setReheatingInstructions(String reheatingInstructions) { this.reheatingInstructions = reheatingInstructions; }
    
    /**
     * Ingredient class for detailed ingredient information
     */
    public static class Ingredient {
        private String name;
        private String amount;
        private String unit;
        private String preparation; // e.g., "chopped", "diced", "minced"
        private String notes; // e.g., "optional", "to taste"
        private boolean isOptional;
        
        public Ingredient() {}
        
        public Ingredient(String name, String amount, String unit) {
            this.name = name;
            this.amount = amount;
            this.unit = unit;
        }
        
        // Getters and Setters
        public String getName() { return name; }
        public void setName(String name) { this.name = name; }
        
        public String getAmount() { return amount; }
        public void setAmount(String amount) { this.amount = amount; }
        
        public String getUnit() { return unit; }
        public void setUnit(String unit) { this.unit = unit; }
        
        public String getPreparation() { return preparation; }
        public void setPreparation(String preparation) { this.preparation = preparation; }
        
        public String getNotes() { return notes; }
        public void setNotes(String notes) { this.notes = notes; }
        
        public boolean isOptional() { return isOptional; }
        public void setOptional(boolean optional) { isOptional = optional; }
        
        @Override
        public String toString() {
            StringBuilder sb = new StringBuilder();
            if (amount != null && !amount.isEmpty()) {
                sb.append(amount).append(" ");
            }
            if (unit != null && !unit.isEmpty()) {
                sb.append(unit).append(" ");
            }
            if (preparation != null && !preparation.isEmpty()) {
                sb.append(preparation).append(" ");
            }
            sb.append(name);
            if (notes != null && !notes.isEmpty()) {
                sb.append(" (").append(notes).append(")");
            }
            return sb.toString().trim();
        }
    }
}
