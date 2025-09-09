package com.example.nutrisaur11;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Fast fallback foods that display immediately while API loads
 */
public class FastFallbackFoods {
    private static final String TAG = "FastFallbackFoods";
    
    /**
     * Get immediate fallback foods for all categories
     */
    public static Map<String, List<FoodRecommendation>> getFastFallbackFoods() {
        Map<String, List<FoodRecommendation>> foods = new HashMap<>();
        
        // Traditional Filipino foods
        List<FoodRecommendation> traditional = new ArrayList<>();
        traditional.add(new FoodRecommendation("Chicken Adobo", 350, 25.0, 15.0, 20.0, "1 serving", "Traditional Filipino", "Classic Filipino dish with tender chicken in savory soy-vinegar sauce"));
        traditional.add(new FoodRecommendation("Sinigang na Baboy", 280, 20.0, 12.0, 15.0, "1 serving", "Traditional Filipino", "Sour soup with pork and vegetables in tamarind broth"));
        traditional.add(new FoodRecommendation("Kare-kare", 450, 18.0, 25.0, 30.0, "1 serving", "Traditional Filipino", "Oxtail and vegetables in rich peanut sauce"));
        traditional.add(new FoodRecommendation("Pancit Canton", 320, 15.0, 8.0, 45.0, "1 serving", "Traditional Filipino", "Stir-fried noodles with vegetables and meat"));
        traditional.add(new FoodRecommendation("Lechon Kawali", 420, 22.0, 28.0, 5.0, "1 serving", "Traditional Filipino", "Crispy fried pork belly with garlic rice"));
        foods.put("traditional", traditional);
        
        // Healthy options
        List<FoodRecommendation> healthy = new ArrayList<>();
        healthy.add(new FoodRecommendation("Grilled Salmon", 250, 30.0, 12.0, 0.0, "1 serving", "Healthy", "Omega-3 rich salmon grilled with herbs"));
        healthy.add(new FoodRecommendation("Quinoa Salad", 180, 8.0, 6.0, 25.0, "1 serving", "Healthy", "Nutrient-dense quinoa with fresh vegetables"));
        healthy.add(new FoodRecommendation("Chicken Breast", 200, 35.0, 4.0, 0.0, "1 serving", "Healthy", "Lean protein grilled with vegetables"));
        healthy.add(new FoodRecommendation("Vegetable Stir-fry", 120, 5.0, 3.0, 20.0, "1 serving", "Healthy", "Fresh vegetables stir-fried in minimal oil"));
        healthy.add(new FoodRecommendation("Greek Yogurt Bowl", 150, 15.0, 2.0, 15.0, "1 serving", "Healthy", "Protein-rich yogurt with berries and nuts"));
        foods.put("healthy", healthy);
        
        // International cuisine
        List<FoodRecommendation> international = new ArrayList<>();
        international.add(new FoodRecommendation("Chicken Teriyaki", 300, 25.0, 8.0, 25.0, "1 serving", "International", "Japanese-style chicken with teriyaki sauce"));
        international.add(new FoodRecommendation("Beef Bulgogi", 280, 22.0, 10.0, 20.0, "1 serving", "International", "Korean marinated beef with rice"));
        international.add(new FoodRecommendation("Pad Thai", 350, 15.0, 12.0, 40.0, "1 serving", "International", "Thai stir-fried noodles with shrimp"));
        international.add(new FoodRecommendation("Spaghetti Carbonara", 420, 18.0, 20.0, 35.0, "1 serving", "International", "Creamy Italian pasta with bacon"));
        international.add(new FoodRecommendation("Chicken Tikka", 250, 28.0, 8.0, 15.0, "1 serving", "International", "Indian spiced chicken with basmati rice"));
        foods.put("international", international);
        
        // Budget-friendly
        List<FoodRecommendation> budget = new ArrayList<>();
        budget.add(new FoodRecommendation("Ginisang Sardinas", 200, 15.0, 8.0, 20.0, "1 serving", "Budget", "Affordable sardines with rice and vegetables"));
        budget.add(new FoodRecommendation("Tocino with Rice", 350, 18.0, 15.0, 30.0, "1 serving", "Budget", "Sweet cured pork with garlic rice"));
        budget.add(new FoodRecommendation("Egg and Rice", 250, 12.0, 10.0, 35.0, "1 serving", "Budget", "Simple scrambled eggs with rice"));
        budget.add(new FoodRecommendation("Pancit Bihon", 280, 10.0, 6.0, 45.0, "1 serving", "Budget", "Rice noodles with vegetables"));
        budget.add(new FoodRecommendation("Arroz Caldo", 220, 8.0, 5.0, 35.0, "1 serving", "Budget", "Comforting rice porridge with chicken"));
        foods.put("budget", budget);
        
        return foods;
    }
    
    /**
     * Get fallback foods for a specific category
     */
    public static List<FoodRecommendation> getFallbackForCategory(String category) {
        Map<String, List<FoodRecommendation>> allFoods = getFastFallbackFoods();
        return allFoods.getOrDefault(category, new ArrayList<>());
    }
}
