package com.example.nutrisaur11;

import java.util.Arrays;
import java.util.List;

public class DishInfo {
    
    public static class DetailedDish {
        public String name;
        public String emoji;
        public String description;
        public List<String> ingredients;
        public List<String> nutritionalFacts;
        public List<String> healthBenefits;
        public List<String> cookingMethod;
        public List<String> allergens;
        public String origin;
        public String servingSize;
        public String preparationTime;
        public String cookingTime;
        public String difficulty;
        public String cost;
        
        public DetailedDish(String name, String emoji, String description, 
                           List<String> ingredients, List<String> nutritionalFacts,
                           List<String> healthBenefits, List<String> cookingMethod,
                           List<String> allergens, String origin, String servingSize,
                           String preparationTime, String cookingTime, String difficulty, String cost) {
            this.name = name;
            this.emoji = emoji;
            this.description = description;
            this.ingredients = ingredients;
            this.nutritionalFacts = nutritionalFacts;
            this.healthBenefits = healthBenefits;
            this.cookingMethod = cookingMethod;
            this.allergens = allergens;
            this.origin = origin;
            this.servingSize = servingSize;
            this.preparationTime = preparationTime;
            this.cookingTime = cookingTime;
            this.difficulty = difficulty;
            this.cost = cost;
        }
    }
    
    // Comprehensive Filipino dish database with detailed information
    public static final List<DetailedDish> DETAILED_DISHES = Arrays.asList(
        
        // ===== MAIN DISHES =====
        new DetailedDish(
            "Adobo",
            "🍖",
            "A savory meat dish where pork or chicken is braised in soy sauce, vinegar, garlic, and spices until tender. The meat absorbs the rich flavors of the marinade, creating a dish with balanced salty, sour, and savory taste. Served with steamed rice.",
            Arrays.asList("500g pork shoulder or chicken thighs", "60ml soy sauce", "60ml white vinegar", "6 garlic cloves, minced", "2 bay leaves", "1 tsp black peppercorns", "1 onion, sliced", "2 tbsp cooking oil", "Salt to taste"),
            Arrays.asList("Protein: 25-30g per serving", "Calories: 300-400 kcal", "Fat: 15-20g", "Carbohydrates: 5-8g", "Sodium: 800-1200mg", "Iron: 2-3mg", "Vitamin B12: 1-2mcg", "Zinc: 3-4mg"),
            Arrays.asList("High protein for muscle building and repair", "Iron for healthy blood and oxygen transport", "B vitamins for energy metabolism", "Zinc for immune function", "Complete amino acid profile", "Low in carbohydrates"),
            Arrays.asList("Marinate meat in soy sauce, vinegar, garlic, and spices for 30 minutes", "Heat oil in a large pot and brown meat on all sides", "Add marinade and bring to a boil", "Reduce heat and simmer covered for 45-60 minutes", "Uncover and cook until sauce thickens", "Serve hot with steamed rice"),
            Arrays.asList("Soy", "Meat"),
            "Philippines",
            "1 cup (200g) with sauce",
            "30 minutes",
            "45-60 minutes",
            "Easy",
            "Low to Medium"
        ),
        
        new DetailedDish(
            "Sinigang na Baboy",
            "🍲",
            "A sour soup with tender pork, fresh vegetables, and tamarind broth. The tamarind provides a refreshing sourness while the vegetables add texture and nutrients. The soup is light yet flavorful, perfect for any meal.",
            Arrays.asList("500g pork belly or ribs", "2 tbsp tamarind powder or 100g fresh tamarind", "200g kangkong (water spinach)", "150g radish, sliced", "200g eggplant, sliced", "2 tomatoes, quartered", "1 onion, sliced", "2 tbsp fish sauce", "1L water", "Salt and pepper to taste"),
            Arrays.asList("Protein: 20-25g per serving", "Calories: 250-350 kcal", "Fat: 12-18g", "Carbohydrates: 15-20g", "Fiber: 4-6g", "Vitamin C: 30-40mg", "Iron: 2-3mg", "Potassium: 400-500mg", "Vitamin A: 200-300mcg"),
            Arrays.asList("High vitamin C for immunity and collagen production", "Protein for muscle health and repair", "Fiber for digestive health and satiety", "Iron for healthy blood and energy", "Low calorie option for weight management", "Hydrating soup base", "Antioxidant-rich vegetables"),
            Arrays.asList("Boil pork in water with onions until tender (about 1 hour)", "Add tamarind and simmer for 10 minutes to extract sourness", "Add vegetables gradually, starting with radish and eggplant", "Add kangkong last as it cooks quickly", "Season with fish sauce, salt, and pepper", "Serve hot with steamed rice"),
            Arrays.asList("Pork", "Meat"),
            "Philippines",
            "1 bowl (300g) with broth",
            "20 minutes",
            "60-90 minutes",
            "Easy",
            "Low"
        ),
        
        new DetailedDish(
            "Kare-kare",
            "🥘",
            "A rich stew with tender beef, fresh vegetables, and peanut sauce. The sauce is thickened with ground rice and colored with annatto seeds. Served with shrimp paste on the side for added flavor.",
            Arrays.asList("1kg beef oxtail or tripe", "120ml peanut butter", "200g eggplant, sliced", "200g string beans, cut", "200g bok choy", "2 tbsp shrimp paste", "6 garlic cloves, minced", "1 onion, sliced", "1 tsp annatto seeds", "2 tbsp ground rice flour", "2 tbsp cooking oil"),
            Arrays.asList("Protein: 30-35g per serving", "Calories: 450-550 kcal", "Fat: 25-35g", "Carbohydrates: 20-25g", "Fiber: 6-8g", "Iron: 4-5mg", "Zinc: 5-6mg", "Vitamin E: 3-4mg", "B vitamins", "Healthy fats from peanuts"),
            Arrays.asList("High protein for muscle development and repair", "Iron for preventing anemia and energy production", "Zinc for immune function and wound healing", "Healthy fats from peanuts for heart health", "Fiber for digestive health and satiety", "Vitamin E for skin health and antioxidant protection", "Complete protein source"),
            Arrays.asList("Boil beef with aromatics until very tender (2-3 hours)", "Prepare peanut sauce by sautéing garlic and onions", "Add peanut butter and beef broth to create sauce", "Add vegetables and cook until tender", "Thicken with rice flour slurry", "Serve hot with shrimp paste and steamed rice"),
            Arrays.asList("Beef", "Meat", "Peanuts", "Shellfish"),
            "Philippines",
            "1 cup (250g) with sauce",
            "30 minutes",
            "2-3 hours",
            "Medium",
            "Medium to High"
        ),
        
        new DetailedDish(
            "Bulalo",
            "🍖",
            "A hearty beef soup with marrow bones simmered until tender. The marrow adds richness to the broth while the vegetables provide nutrients and texture. A complete meal in a bowl.",
            Arrays.asList("1kg beef marrow bones", "500g beef meat chunks", "2 corn cobs, cut", "300g cabbage, chopped", "300g potatoes, cubed", "200g carrots, sliced", "1 onion, sliced", "6 garlic cloves", "3 bay leaves", "1 tbsp black peppercorns", "2 tbsp fish sauce"),
            Arrays.asList("Protein: 35-40g per serving", "Calories: 500-600 kcal", "Fat: 30-40g", "Carbohydrates: 25-30g", "Collagen: High", "Iron: 5-6mg", "Zinc: 6-7mg", "Vitamin B12: 3-4mcg", "Bone marrow nutrients", "Rich in minerals"),
            Arrays.asList("High protein for muscle building and tissue repair", "Collagen for joint health and skin elasticity", "Iron for healthy blood and oxygen transport", "Zinc for immune function and wound healing", "Bone marrow nutrients for overall health", "Rich in minerals and vitamins", "Comforting and warming"),
            Arrays.asList("Boil bones and meat with aromatics for 2-3 hours until very tender", "Add vegetables gradually, starting with corn and potatoes", "Add cabbage last as it cooks quickly", "Season with fish sauce, salt, and pepper", "Serve hot with steamed rice and fish sauce on the side"),
            Arrays.asList("Beef", "Meat"),
            "Philippines",
            "1 bowl (350g) with broth",
            "15 minutes",
            "3-4 hours",
            "Easy",
            "Medium to High"
        ),
        
        new DetailedDish(
            "Laing",
            "🌿",
            "Taro leaves cooked in coconut milk with chili peppers and aromatics. The leaves absorb the rich coconut flavor while the chilies add heat. A creamy, spicy vegetable dish.",
            Arrays.asList("200g dried taro leaves or 400g fresh", "500ml coconut milk", "10-15 chili peppers", "6 garlic cloves, minced", "1 onion, sliced", "2-inch ginger, minced", "2 tbsp shrimp paste", "2 tbsp cooking oil", "Salt and pepper to taste"),
            Arrays.asList("Protein: 8-12g per serving", "Calories: 200-300 kcal", "Fat: 15-25g", "Carbohydrates: 15-20g", "Fiber: 8-10g", "Vitamin A: 500-600mcg", "Vitamin C: 20-30mg", "Iron: 3-4mg", "Medium-chain triglycerides", "Antioxidants"),
            Arrays.asList("High fiber for digestive health and satiety", "Vitamin A for eye health and immune function", "Iron for healthy blood and energy", "Medium-chain triglycerides from coconut for quick energy", "Antioxidant properties for cell protection", "Low calorie option for weight management", "Spicy food for metabolism boost"),
            Arrays.asList("Sauté garlic, onions, and ginger in oil until fragrant", "Add coconut milk and bring to a gentle simmer", "Add taro leaves and cook until tender", "Add chili peppers and shrimp paste", "Simmer until sauce thickens and flavors meld", "Season with salt and pepper to taste"),
            Arrays.asList(),
            "Philippines",
            "1 cup (200g)",
            "20 minutes",
            "45-60 minutes",
            "Medium",
            "Low"
        ),
        
        new DetailedDish(
            "Pinakbet",
            "🥗",
            "Mixed vegetables cooked with shrimp paste. The vegetables are simmered until slightly wilted, absorbing the umami flavor of the shrimp paste. A healthy, nutritious vegetable dish.",
            Arrays.asList("200g bitter melon, sliced", "200g eggplant, sliced", "150g okra", "200g string beans, cut", "300g squash, cubed", "2 tomatoes, quartered", "1 onion, sliced", "4 garlic cloves, minced", "2 tbsp shrimp paste", "2 tbsp cooking oil", "Salt and pepper"),
            Arrays.asList("Protein: 6-10g per serving", "Calories: 150-250 kcal", "Fat: 8-12g", "Carbohydrates: 20-25g", "Fiber: 8-12g", "Vitamin A: 600-800mcg", "Vitamin C: 40-50mg", "Iron: 3-4mg", "Antioxidants", "Low sodium"),
            Arrays.asList("High fiber for digestive health and weight management", "Vitamin A for vision and immune function", "Vitamin C for immunity and collagen production", "Iron for healthy blood and energy", "Low calorie for weight management", "Antioxidant-rich for cell protection", "Heart-healthy vegetable dish"),
            Arrays.asList("Sauté garlic and onions in oil until fragrant", "Add vegetables gradually, starting with harder ones like squash", "Add bitter melon and eggplant next", "Add okra and string beans last", "Season with shrimp paste and simmer until tender", "Serve hot with steamed rice"),
            Arrays.asList("Shellfish"),
            "Philippines",
            "1 cup (200g)",
            "15 minutes",
            "30-45 minutes",
            "Easy",
            "Low"
        ),
        
        new DetailedDish(
            "Champorado",
            "🍚",
            "Sweet chocolate rice porridge made with sticky rice, cocoa powder, and milk. The rice becomes creamy and chocolatey, creating a comforting breakfast dish. Often served with dried fish for a sweet-savory combination.",
            Arrays.asList("200g sticky rice", "30g cocoa powder", "500ml milk", "100g sugar", "1/4 tsp salt", "500ml water", "60ml evaporated milk (optional)"),
            Arrays.asList("Protein: 8-12g per serving", "Calories: 300-400 kcal", "Fat: 8-12g", "Carbohydrates: 55-65g", "Fiber: 2-4g", "Calcium: 200-250mg", "Iron: 2-3mg", "B vitamins", "Complex carbohydrates"),
            Arrays.asList("Energy-dense breakfast for sustained energy", "Calcium for bone health and muscle function", "Iron for healthy blood and oxygen transport", "Complex carbohydrates for steady blood sugar", "Comfort food for emotional well-being", "Easy to digest", "Good source of B vitamins"),
            Arrays.asList("Rinse sticky rice and drain well", "Bring water to boil and add rice", "Cook rice until almost done", "Add cocoa powder and sugar, stir well", "Add milk and continue cooking until creamy", "Season with salt and serve hot"),
            Arrays.asList("Dairy", "Milk"),
            "Philippines",
            "1 bowl (250g)",
            "5 minutes",
            "25-30 minutes",
            "Easy",
            "Low"
        ),
        
        new DetailedDish(
            "Arroz Caldo",
            "🍚",
            "Savory rice porridge with chicken, ginger, and garlic. The rice cooks in chicken broth until creamy, absorbing the aromatic flavors. A warm, comforting dish perfect for any meal.",
            Arrays.asList("200g rice", "500g chicken, cut", "2-inch ginger, minced", "6 garlic cloves, minced", "1 onion, sliced", "1.5L chicken broth", "2 tbsp fish sauce", "1/4 tsp saffron or turmeric", "4 green onions, chopped", "4 hard-boiled eggs", "2 tbsp cooking oil"),
            Arrays.asList("Protein: 20-25g per serving", "Calories: 350-450 kcal", "Fat: 12-18g", "Carbohydrates: 45-55g", "Fiber: 2-3g", "Iron: 2-3mg", "Zinc: 2-3mg", "Vitamin B6: 0.5-1mg", "Ginger compounds", "Complex carbohydrates"),
            Arrays.asList("Easy to digest for recovery and comfort", "Ginger for nausea relief and digestion", "Protein for muscle repair and recovery", "Warm and comforting for emotional well-being", "Hydrating soup base", "Good for convalescence", "Immune-boosting properties"),
            Arrays.asList("Sauté ginger, garlic, and onions in oil until fragrant", "Add chicken and cook until lightly browned", "Add rice and stir to coat with oil", "Pour in chicken broth and bring to boil", "Simmer until rice is creamy and chicken is tender", "Garnish with green onions and serve with eggs"),
            Arrays.asList("Chicken", "Meat"),
            "Philippines",
            "1 bowl (300g)",
            "15 minutes",
            "45-60 minutes",
            "Easy",
            "Low to Medium"
        ),
        
        new DetailedDish(
            "Lumpiang Shanghai",
            "🥟",
            "Crispy spring rolls filled with ground pork and vegetables. The filling is seasoned with soy sauce and black pepper, then wrapped and deep-fried until golden brown. Served as appetizers or snacks.",
            Arrays.asList("500g ground pork", "2 carrots, finely chopped", "2 onions, finely chopped", "4 garlic cloves, minced", "20 spring roll wrappers", "2 eggs", "2 tbsp soy sauce", "1 tsp black pepper", "500ml cooking oil for frying", "Sweet chili sauce for serving"),
            Arrays.asList("Protein: 15-20g per serving", "Calories: 250-350 kcal", "Fat: 15-25g", "Carbohydrates: 20-25g", "Fiber: 2-3g", "Iron: 2-3mg", "Vitamin A: 200-300mcg", "Sodium: 600-800mg", "B vitamins"),
            Arrays.asList("Protein for muscle health and repair", "Vitamin A for vision and immune function", "Iron for healthy blood and energy", "Portable snack for convenience", "High energy for active lifestyles", "Good finger food for social gatherings", "Complete protein source"),
            Arrays.asList("Mix ground pork with finely chopped vegetables", "Season with soy sauce, black pepper, and beaten eggs", "Place filling on spring roll wrappers and roll tightly", "Seal edges with beaten egg", "Deep fry in hot oil until golden brown", "Drain on paper towels and serve hot"),
            Arrays.asList("Pork", "Meat", "Eggs"),
            "Philippines",
            "4-6 pieces (100g)",
            "25 minutes",
            "15-20 minutes",
            "Medium",
            "Low to Medium"
        ),
        
        new DetailedDish(
            "Lechon Kawali",
            "🥓",
            "Crispy pork belly that is first boiled then deep-fried. The pork becomes tender inside with a crispy, golden skin outside. Served with dipping sauce for added flavor.",
            Arrays.asList("1kg pork belly", "8 garlic cloves", "3 bay leaves", "1 tbsp black peppercorns", "2 tbsp salt", "1L cooking oil for deep frying", "60ml vinegar", "2 tbsp soy sauce", "Chili peppers (optional)"),
            Arrays.asList("Protein: 25-30g per serving", "Calories: 400-500 kcal", "Fat: 30-40g", "Carbohydrates: 2-5g", "Iron: 2-3mg", "Zinc: 3-4mg", "Vitamin B12: 1-2mcg", "Sodium: 800-1200mg", "B vitamins"),
            Arrays.asList("High protein for muscle building and repair", "Iron for healthy blood and oxygen transport", "Zinc for immune function and wound healing", "Energy dense for active lifestyles", "Celebration food for social bonding", "Rich flavor for special occasions", "Complete protein source"),
            Arrays.asList("Boil pork with garlic, bay leaves, and peppercorns until tender", "Cool pork completely and pat dry", "Heat oil to 350°F (175°C)", "Deep fry pork until skin is crispy and golden", "Drain on paper towels", "Serve hot with dipping sauce"),
            Arrays.asList("Pork", "Meat"),
            "Philippines",
            "1 cup (200g)",
            "20 minutes",
            "60-90 minutes",
            "Medium",
            "Medium to High"
        ),
        
        new DetailedDish(
            "Bicol Express",
            "🌶️",
            "Spicy pork dish cooked in coconut milk with chili peppers. The pork becomes tender in the rich coconut sauce while the chilies provide heat. A flavorful dish with a creamy, spicy profile.",
            Arrays.asList("500g pork belly or shoulder", "500ml coconut milk", "10-15 chili peppers", "6 garlic cloves, minced", "2 onions, sliced", "2 tbsp shrimp paste", "2 tbsp cooking oil", "Salt and pepper to taste"),
            Arrays.asList("Protein: 25-30g per serving", "Calories: 400-500 kcal", "Fat: 30-40g", "Carbohydrates: 8-12g", "Fiber: 2-3g", "Iron: 2-3mg", "Zinc: 3-4mg", "Medium-chain triglycerides", "Capsaicin from chilies"),
            Arrays.asList("High protein for muscle health and repair", "Iron for healthy blood and energy", "Zinc for immune function and wound healing", "Medium-chain triglycerides for quick energy", "Spicy food for metabolism boost", "Rich and satisfying for appetite", "Capsaicin for pain relief"),
            Arrays.asList("Sauté pork until browned and fat is rendered", "Add garlic, onions, and chili peppers", "Pour in coconut milk and bring to simmer", "Add shrimp paste and season with salt", "Simmer until pork is tender and sauce thickens", "Serve hot with steamed rice"),
            Arrays.asList("Pork", "Meat"),
            "Philippines",
            "1 cup (200g)",
            "20 minutes",
            "60-90 minutes",
            "Medium",
            "Medium"
        )
    );
    
    /**
     * Get detailed dish information by name
     */
    public static DetailedDish getDishInfo(String dishName) {
        for (DetailedDish dish : DETAILED_DISHES) {
            if (dish.name.equalsIgnoreCase(dishName)) {
                return dish;
            }
        }
        return null;
    }
    
    /**
     * Get all dish names for search functionality
     */
    public static List<String> getAllDishNames() {
        List<String> names = new java.util.ArrayList<>();
        for (DetailedDish dish : DETAILED_DISHES) {
            names.add(dish.name);
        }
        return names;
    }
    
    /**
     * Search dishes by ingredient
     */
    public static List<DetailedDish> searchByIngredient(String ingredient) {
        List<DetailedDish> results = new java.util.ArrayList<>();
        for (DetailedDish dish : DETAILED_DISHES) {
            for (String dishIngredient : dish.ingredients) {
                if (dishIngredient.toLowerCase().contains(ingredient.toLowerCase())) {
                    results.add(dish);
                    break;
                }
            }
        }
        return results;
    }
    
    /**
     * Search dishes by health benefit
     */
    public static List<DetailedDish> searchByHealthBenefit(String benefit) {
        List<DetailedDish> results = new java.util.ArrayList<>();
        for (DetailedDish dish : DETAILED_DISHES) {
            for (String healthBenefit : dish.healthBenefits) {
                if (healthBenefit.toLowerCase().contains(benefit.toLowerCase())) {
                    results.add(dish);
                    break;
                }
            }
        }
        return results;
    }
}
