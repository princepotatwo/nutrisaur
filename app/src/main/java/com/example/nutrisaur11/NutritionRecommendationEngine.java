package com.example.nutrisaur11;

import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.util.Log;
import org.json.JSONArray;
import org.json.JSONObject;
import java.util.*;
import java.util.ArrayList;

public class NutritionRecommendationEngine {
    private Context context;
    private UserPreferencesDbHelper dbHelper;
    
    public NutritionRecommendationEngine(Context context) {
        this.context = context;
        this.dbHelper = new UserPreferencesDbHelper(context);
    }
    
    public List<DishData.Dish> getPersonalizedRecommendations(String userEmail) {
        try {
            Log.d("NutritionEngine", "Getting personalized recommendations for user: " + userEmail);
            
            // Get user screening data
            Map<String, Object> userData = getUserScreeningData(userEmail);
            if (userData == null) {
                Log.d("NutritionEngine", "No user data found, returning default recommendations");
                return getDefaultRecommendations();
            }
            
            // Load user's favorites to personalize recommendations
            FavoritesData favoritesData = getUserFavoritesData(userEmail);
            Log.d("NutritionEngine", "Favorites loaded - names: " + favoritesData.favoriteNames.size() + 
                    ", tagPrefs: " + favoritesData.favoriteTagCounts.size());

            // Extract key parameters with null checks
            Object ageMonthsObj = userData.get("age_months");
            Object bmiObj = userData.get("bmi");
            Object riskScoreObj = userData.get("risk_score");
            Object dietaryDiversityObj = userData.get("dietary_diversity");
            
            int ageMonths = (ageMonthsObj != null) ? (int) ageMonthsObj : 24; // Default to 2 years
            double bmi = (bmiObj != null) ? (double) bmiObj : 16.0; // Default BMI
            String gender = (String) userData.get("gender");
            String income = (String) userData.get("income");
            int riskScore = (riskScoreObj != null) ? (int) riskScoreObj : 30; // Default risk score
            int dietaryDiversity = (dietaryDiversityObj != null) ? (int) dietaryDiversityObj : 0; // Default diversity
            
            Log.d("NutritionEngine", "User data - Age: " + ageMonths + " months, BMI: " + bmi + ", Income: " + income + ", Risk: " + riskScore);
            
            // Determine age group with strict filtering
            String ageGroup = determineAgeGroup(ageMonths);
            
            // Determine nutritional priorities
            List<String> priorities = determineNutritionalPriorities(ageMonths, bmi, riskScore, dietaryDiversity, userData);
            
            // Determine cost constraints
            List<String> costConstraints = determineCostConstraints(income);
            
            Log.d("NutritionEngine", "Age group: " + ageGroup);
            Log.d("NutritionEngine", "Priorities: " + priorities);
            Log.d("NutritionEngine", "Cost constraints: " + costConstraints);
            
            // Filter and score dishes with flexible age filtering for adults
            List<ScoredDish> scoredDishes = scoreDishes(ageGroup, priorities, costConstraints, userData, favoritesData, ageMonths);
            
            Log.d("NutritionEngine", "Scored dishes count: " + scoredDishes.size());
            
            // Return top recommendations
            List<DishData.Dish> recommendations = getTopRecommendations(scoredDishes);
            Log.d("NutritionEngine", "Final recommendations count: " + recommendations.size());
            
            // Apply dietary filters (allergies, preferences)
            recommendations = applyDietaryFilters(recommendations, userData);
            Log.d("NutritionEngine", "After dietary filtering: " + recommendations.size() + " recommendations");
            
            return recommendations;
            
        } catch (Exception e) {
            Log.e("NutritionEngine", "Error getting recommendations: " + e.getMessage());
            return getDefaultRecommendations();
        }
    }
    
    /**
     * Get nutrition explanation for a specific dish based on user's risk score
     */
    public String getNutritionExplanation(DishData.Dish dish, int riskScore) {
        StringBuilder explanation = new StringBuilder();
        
        // Base explanation based on dish tags
        if (dish.tags.contains("High-protein")) {
            explanation.append("High in protein for muscle development. ");
        }
        if (dish.tags.contains("High-iron")) {
            explanation.append("Rich in iron for healthy blood. ");
        }
        if (dish.tags.contains("High-calcium")) {
            explanation.append("Good source of calcium for strong bones. ");
        }
        if (dish.tags.contains("High-omega3")) {
            explanation.append("Contains omega-3 fatty acids for brain health. ");
        }
        if (dish.tags.contains("High-fiber")) {
            explanation.append("High in fiber for digestive health. ");
        }
        if (dish.tags.contains("High-vitamin-a")) {
            explanation.append("Rich in vitamin A for eye health. ");
        }
        if (dish.tags.contains("High-vitamin-c")) {
            explanation.append("Good source of vitamin C for immunity. ");
        }
        if (dish.tags.contains("Low-carb")) {
            explanation.append("Low in carbohydrates for weight management. ");
        }
        if (dish.tags.contains("Low-cost")) {
            explanation.append("Affordable and budget-friendly. ");
        }
        
        // Risk-based explanation
        if (riskScore >= 70) {
            explanation.append("Recommended for high malnutrition risk - provides essential nutrients. ");
        } else if (riskScore >= 50) {
            explanation.append("Good choice for moderate risk - balanced nutrition. ");
        } else {
            explanation.append("Healthy option for maintaining good nutrition. ");
        }
        
        // Age-appropriate explanation
        if (dish.tags.contains("Infant")) {
            explanation.append("Perfect for infants - soft and easy to digest. ");
        } else if (dish.tags.contains("Toddler")) {
            explanation.append("Great for toddlers - soft texture and nutrient-dense. ");
        } else if (dish.tags.contains("Preschool")) {
            explanation.append("Ideal for preschoolers - kid-friendly and nutritious. ");
        } else if (dish.tags.contains("School-age")) {
            explanation.append("Excellent for school-age children - energy-dense and healthy. ");
        } else if (dish.tags.contains("Adolescent")) {
            explanation.append("Perfect for adolescents - adult portions with balanced nutrition. ");
        }
        
        return explanation.toString().trim();
    }
    
    /**
     * Close the database helper
     */
    public void close() {
        if (dbHelper != null) {
            dbHelper.close();
        }
    }
    
    private Map<String, Object> getUserScreeningData(String userEmail) {
        try {
            SQLiteDatabase db = dbHelper.getReadableDatabase();
            Cursor cursor = db.query(
                UserPreferencesDbHelper.TABLE_NAME,
                null,
                UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
                new String[]{userEmail},
                null, null, null
            );
            
            if (cursor.moveToFirst()) {
                Map<String, Object> data = new HashMap<>();
                
                // Debug: Log all column names and values
                String[] columnNames = cursor.getColumnNames();
                Log.d("NutritionEngine", "Available columns: " + java.util.Arrays.toString(columnNames));
                for (String columnName : columnNames) {
                    String value = cursor.getString(cursor.getColumnIndex(columnName));
                    Log.d("NutritionEngine", "Column " + columnName + ": " + value);
                }
                
                // Get screening answers - maintain backward compatibility
                String screeningAnswers = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_SCREENING_ANSWERS));
                Log.d("NutritionEngine", "Raw screening answers: " + screeningAnswers);
                
                if (screeningAnswers != null && !screeningAnswers.isEmpty()) {
                    try {
                        JSONObject screening = new JSONObject(screeningAnswers);
                        
                        // Extract basic data
                        data.put("gender", screening.optString("gender", ""));
                        data.put("income", screening.optString("income", ""));
                        data.put("dietary_diversity", screening.optInt("dietary_diversity", 0));

                        // Extract clinical signs used in practice
                        data.put("swelling", screening.optString("swelling", ""));
                        data.put("weight_loss", screening.optString("weight_loss", ""));
                        data.put("feeding_behavior", screening.optString("feeding_behavior", ""));
                        data.put("physical_signs", screening.optString("physical_signs", ""));
                        
                        // Extract new clinical risk factors
                        data.put("has_recent_illness", screening.optBoolean("has_recent_illness", false));
                        data.put("has_eating_difficulty", screening.optBoolean("has_eating_difficulty", false));
                        data.put("has_food_insecurity", screening.optBoolean("has_food_insecurity", false));
                        data.put("has_micronutrient_deficiency", screening.optBoolean("has_micronutrient_deficiency", false));
                        data.put("has_functional_decline", screening.optBoolean("has_functional_decline", false));
                        
                        // Calculate age from birthday
                        String birthday = screening.optString("birthday", "");
                        Log.d("NutritionEngine", "Birthday from screening: " + birthday);
                        if (!birthday.isEmpty()) {
                            int ageMonths = calculateAgeInMonths(birthday);
                            data.put("age_months", ageMonths);
                            Log.d("NutritionEngine", "Calculated age in months: " + ageMonths);
                        } else {
                            data.put("age_months", 24); // Default to 2 years
                            Log.d("NutritionEngine", "Using default age: 24 months");
                        }
                        
                        // Calculate BMI
                        double weight = screening.optDouble("weight", 0);
                        double height = screening.optDouble("height", 0);
                        Log.d("NutritionEngine", "Weight: " + weight + ", Height: " + height);
                        if (weight > 0 && height > 0) {
                            double bmi = weight / ((height / 100) * (height / 100));
                            data.put("bmi", bmi);
                            Log.d("NutritionEngine", "Calculated BMI: " + bmi);
                        } else {
                            data.put("bmi", 16.0); // Default BMI
                            Log.d("NutritionEngine", "Using default BMI: 16.0");
                        }
                        
                        // Calculate risk score
                        int riskScore = calculateRiskScore(screening);
                        data.put("risk_score", riskScore);
                        Log.d("NutritionEngine", "Calculated risk score: " + riskScore);
                        
                    } catch (Exception e) {
                        Log.e("NutritionEngine", "Error parsing screening data: " + e.getMessage());
                        // Set default values if parsing fails
                        data.put("age_months", 24);
                        data.put("bmi", 16.0);
                        data.put("risk_score", 30);
                        data.put("dietary_diversity", 0);
                    }
                } else {
                    Log.d("NutritionEngine", "No screening answers found, using defaults");
                    // Set default values if no screening data
                    data.put("age_months", 24);
                    data.put("bmi", 16.0);
                    data.put("risk_score", 30);
                    data.put("dietary_diversity", 0);
                }
                
                cursor.close();
                return data;
            } else {
                Log.d("NutritionEngine", "No user data found for: " + userEmail);
                cursor.close();
                return null;
            }
        } catch (Exception e) {
            Log.e("NutritionEngine", "Error getting user screening data: " + e.getMessage());
            return null;
        }
    }
    
    private String determineAgeGroup(int ageMonths) {
        if (ageMonths <= 6) return "Infant-Early";      // 0-6 months: Only breastmilk/formula
        else if (ageMonths <= 12) return "Infant";      // 6-12 months: Soft purees
        else if (ageMonths <= 24) return "Toddler-Early"; // 12-24 months: Soft foods
        else if (ageMonths <= 36) return "Toddler";     // 24-36 months: Bite-sized
        else if (ageMonths <= 60) return "Preschool";   // 3-5 years: Kid-friendly
        else if (ageMonths <= 144) return "School-age"; // 5-12 years: Complex foods
        else return "Adolescent";                       // 12+ years: Adult portions
    }
    
    private List<String> determineNutritionalPriorities(int ageMonths, double bmi, int riskScore, int dietaryDiversity, Map<String, Object> userData) {
        List<String> priorities = new ArrayList<>();
        
        // Age-based priorities (more specific and accurate)
        if (ageMonths <= 6) {
            // 0-6 months: Only breastmilk/formula - no solid foods
            priorities.add("Infant-Early");
            priorities.add("Breastmilk");
            priorities.add("Formula");
        } else if (ageMonths <= 12) {
            // 6-12 months: Soft purees only
            priorities.add("Infant");
            priorities.add("Soft");
            priorities.add("Easy-digest");
            priorities.add("Pureed");
        } else if (ageMonths <= 24) {
            // 12-24 months: Soft foods, some texture
            priorities.add("Toddler-Early");
            priorities.add("Soft");
            priorities.add("High-protein");
            priorities.add("Easy-digest");
        } else if (ageMonths <= 36) {
            // 24-36 months: Bite-sized foods
            priorities.add("Toddler");
            priorities.add("Soft");
            priorities.add("High-protein");
            priorities.add("Bite-sized");
        } else if (ageMonths <= 60) {
            // 3-5 years: Kid-friendly foods
            priorities.add("Preschool");
            priorities.add("High-protein");
            priorities.add("High-fiber");
            priorities.add("Kid-friendly");
        } else if (ageMonths <= 144) {
            // 5-12 years: Complex, energy-dense foods
            priorities.add("School-age");
            priorities.add("High-protein");
            priorities.add("High-fiber");
            priorities.add("Energy-dense");
        } else {
            // 12+ years: Adult portions
            priorities.add("Adolescent");
            priorities.add("High-protein");
            priorities.add("High-fiber");
            priorities.add("Balanced");
        }
        
        // BMI-based priorities (avoid using adult BMI heuristics for <5 years)
        if (ageMonths >= 144) { // Only apply BMI-based logic for adolescents/adults
            if (bmi < 18.5) {
                priorities.add("High-protein");
                priorities.add("High-energy");
            } else if (bmi > 25.0) {
                priorities.add("Low-carb");
                priorities.add("High-fiber");
            }
        }
        
        // Risk score-based priorities
        if (riskScore >= 70) {
            priorities.add("Malnutrition");
            priorities.add("High-protein");
            priorities.add("High-energy");
        } else if (riskScore >= 50) {
            priorities.add("High-protein");
            priorities.add("High-iron");
        }
        
        // Dietary diversity priorities
        if (dietaryDiversity < 4) {
            priorities.add("High-vitamin-c");
            priorities.add("High-vitamin-a");
        }

        // Clinical signs-based priorities
        String feedingBehavior = (String) userData.get("feeding_behavior");
        String physicalSigns = (String) userData.get("physical_signs");
        String weightLoss = (String) userData.get("weight_loss");
        String swelling = (String) userData.get("swelling");

        if (feedingBehavior != null && ("poor".equalsIgnoreCase(feedingBehavior) || "moderate".equalsIgnoreCase(feedingBehavior))) {
            priorities.add("Easy-digest");
            priorities.add("Soft");
        }
        if (physicalSigns != null && physicalSigns.contains("thin")) {
            priorities.add("High-energy");
            priorities.add("High-protein");
        }
        if (weightLoss != null && weightLoss.equalsIgnoreCase("yes")) {
            priorities.add("High-energy");
        }
        // Swelling (edema) often involves sodium management; we would prefer low-sodium options if tagged
        if (swelling != null && swelling.equalsIgnoreCase("yes")) {
            priorities.add("Low-sodium");
        }
        
        return priorities;
    }
    
    private List<String> determineCostConstraints(String income) {
        List<String> constraints = new ArrayList<>();
        
        if (income == null) {
            constraints.add("Low-cost");
            constraints.add("Free"); // Always include free options
            return constraints;
        }
        
        if (income.contains("Below PHP 12,030") || income.contains("PHP 12,031")) {
            // Low-income users get access to ALL foods - most Filipino dishes are affordable
            constraints.add("Low-cost");
            constraints.add("Free");
            constraints.add("Medium-cost"); // Include medium-cost foods for variety
            // Don't add "High-cost" but don't exclude dishes without cost tags
        } else if (income.contains("PHP 20,001")) {
            constraints.add("Low-cost");
            constraints.add("Medium-cost");
            constraints.add("Free"); // Include free options for middle income
        } else if (income.contains("Above PHP 40,000")) {
            constraints.add("High-cost");
            constraints.add("Medium-cost");
            constraints.add("Low-cost");
            constraints.add("Free"); // Include free options for high income
        } else {
            constraints.add("Low-cost");
            constraints.add("Medium-cost");
            constraints.add("Free"); // Default to include free options
        }
        
        return constraints;
    }
    
    private List<ScoredDish> scoreDishes(String ageGroup, List<String> priorities, List<String> costConstraints, Map<String, Object> userData, FavoritesData favoritesData, int ageMonths) {
        List<ScoredDish> scoredDishes = new ArrayList<>();
        
        Log.d("NutritionEngine", "Scoring dishes for ageGroup: " + ageGroup);
        Log.d("NutritionEngine", "Priorities: " + priorities);
        Log.d("NutritionEngine", "Cost constraints: " + costConstraints);
        
        int totalDishes = 0;
        int ageGroupMatches = 0;
        int costMatches = 0;
        
        for (DishData.Dish dish : DishData.DISHES) {
            totalDishes++;
            double score = 0.0;
            
            // AGE GROUP FILTERING - More flexible for adults
            boolean ageGroupMatch = false;
            if (ageMonths >= 144) { // Adult (12+ years) - show ALL foods
                ageGroupMatch = true;
                score += 500.0; // Base score for adults
                
                // Bonus for age-appropriate tags if they exist
                for (String tag : dish.tags) {
                    if (tag.equalsIgnoreCase(ageGroup)) {
                        score += 200.0; // Bonus for age-appropriate foods
                        Log.d("NutritionEngine", "Age group bonus for " + dish.name + " with tag: " + tag);
                        break;
                    }
                }
            } else {
                // For children, still do strict age filtering
                for (String tag : dish.tags) {
                    if (tag.equalsIgnoreCase(ageGroup)) {
                        ageGroupMatch = true;
                        Log.d("NutritionEngine", "Age group match found: " + dish.name + " has tag: " + tag + " matching: " + ageGroup);
                        break;
                    }
                }
                if (!ageGroupMatch) {
                    continue; // Skip dishes that don't match the age group for children
                }
                score += 1000.0; // Very high score for children
            }
            ageGroupMatches++;
            
            // Priority matching - Case-insensitive (INCREASED WEIGHT)
            for (String priority : priorities) {
                for (String tag : dish.tags) {
                    if (tag.equalsIgnoreCase(priority)) {
                        score += 50.0; // Increased from 20.0 to 50.0 for stronger nutritional priority
                        break;
                    }
                }
            }
            
            // Cost constraint matching - Case-insensitive (REDUCED WEIGHT)
            boolean costMatch = false;
            for (String constraint : costConstraints) {
                for (String tag : dish.tags) {
                    if (tag.equalsIgnoreCase(constraint)) {
                        score += 8.0; // Reduced from 15.0 to 8.0 to make cost less dominant
                        costMatch = true;
                        Log.d("NutritionEngine", "Cost constraint match found: " + dish.name + " has tag: " + tag + " matching: " + constraint);
                        break;
                    }
                }
            }
            
            // Note: Dietary filtering is now handled in applyDietaryFilters method
            // to avoid double filtering and ensure more balanced results

            // Personalization boost from favorites
            if (favoritesData.favoriteNames.contains(dish.name)) {
                score += 50.0; // Strong boost for previously favorited dishes
            }
                for (String tag : dish.tags) {
                Integer count = favoritesData.favoriteTagCounts.get(tag);
                if (count != null && count > 0) {
                    score += Math.min(count, 3) * 3.0; // Small boost per matching favorite tag
                }
            }

            // Bonus for multiple priority matches - Case-insensitive (INCREASED WEIGHT)
            int priorityMatches = 0;
            for (String priority : priorities) {
                for (String tag : dish.tags) {
                    if (tag.equalsIgnoreCase(priority)) {
                        priorityMatches++;
                        break;
                    }
                }
            }
            score += priorityMatches * 15.0; // Increased from 5.0 to 15.0 for stronger priority bonus
            
            // Bonus for dishes with multiple nutritional tags (nutritional density bonus)
            int nutritionalTagCount = 0;
            String[] nutritionalTags = {"High-protein", "High-energy", "Iron-rich", "Vitamin-A", "Calcium-rich", "Fiber-rich", "Omega-3", "Antioxidant"};
            for (String tag : dish.tags) {
                for (String nutritionalTag : nutritionalTags) {
                    if (tag.equalsIgnoreCase(nutritionalTag)) {
                        nutritionalTagCount++;
                        break;
                    }
                }
            }
            if (nutritionalTagCount > 1) {
                score += nutritionalTagCount * 10.0; // Bonus for multiple nutritional benefits
                Log.d("NutritionEngine", "Nutritional density bonus for " + dish.name + ": " + nutritionalTagCount + " nutritional tags");
            }
            
            // DIVERSITY BONUS - Encourage variety in recommendations
            int diversityScore = 0;
            
            // Bonus for unique food categories
            if (dish.tags.contains("VEG") || dish.tags.contains("VGN")) diversityScore += 5;
            if (dish.tags.contains("PES")) diversityScore += 5;
            if (dish.tags.contains("RIC")) diversityScore += 3;
            if (dish.tags.contains("MEA")) diversityScore += 3;
            if (dish.tags.contains("FISH") || dish.tags.contains("SEA")) diversityScore += 3;
            if (dish.tags.contains("SOUP")) diversityScore += 3;
            if (dish.tags.contains("DES")) diversityScore += 2;
            if (dish.tags.contains("SNK")) diversityScore += 2;
            
            // Bonus for different cooking methods
            if (dish.tags.contains("STE")) diversityScore += 2;
            if (dish.tags.contains("FRI")) diversityScore += 2;
            if (dish.tags.contains("GRI")) diversityScore += 2;
            if (dish.tags.contains("BOI")) diversityScore += 2;
            if (dish.tags.contains("BRA")) diversityScore += 2;
            
            // Bonus for traditional Filipino dishes
            if (dish.tags.contains("traditional")) diversityScore += 3;
            
            score += diversityScore;
            Log.d("NutritionEngine", "Diversity bonus for " + dish.name + ": " + diversityScore + " points");
            
            // SMART DIETARY MATCHING BONUS - Give extra points for intelligent combinations
            if (userData.containsKey("diet_prefs")) {
                String dietPrefsStr = (String) userData.get("diet_prefs");
                if (dietPrefsStr != null && !dietPrefsStr.trim().isEmpty()) {
                    List<String> dietPrefs = new ArrayList<>();
                    String[] prefs = dietPrefsStr.split(",");
                    for (String pref : prefs) {
                        String trimmed = pref.trim();
                        if (!trimmed.isEmpty()) {
                            dietPrefs.add(trimmed);
                        }
                    }
                    
                    // Check if this dish is a smart dietary match
                    if (isSmartDietaryMatch(dish, dietPrefs)) {
                        score += 100.0; // Significant bonus for smart matches
                        Log.d("NutritionEngine", "Smart dietary match bonus for " + dish.name + " with preferences: " + dietPrefs);
                    }
                }
            }
            
            // Penalty for allergens if user has allergies
            if (userData.containsKey("allergies")) {
                String allergies = (String) userData.get("allergies");
                if (allergies != null && !allergies.isEmpty()) {
                    for (String allergen : dish.allergens) {
                        if (allergies.toLowerCase().contains(allergen.toLowerCase())) {
                            score -= 1000.0; // Very heavy penalty for allergens - exclude completely
                            Log.d("NutritionEngine", "Excluding dish due to allergy: " + dish.name + " (allergen: " + allergen + ")");
                        }
                    }
                }
            }
            
            // (Diet preferences exclusion handled earlier above with hard excludes)
            
            // Check avoid foods
            if (userData.containsKey("avoid_foods")) {
                String avoidFoods = (String) userData.get("avoid_foods");
                if (avoidFoods != null && !avoidFoods.isEmpty()) {
                    String[] avoidList = avoidFoods.toLowerCase().split(",");
                    for (String avoidFood : avoidList) {
                        if (dish.name.toLowerCase().contains(avoidFood.trim())) {
                            score -= 1000.0; // Exclude completely
                            Log.d("NutritionEngine", "Excluding dish due to avoid food: " + dish.name + " (avoid: " + avoidFood.trim() + ")");
                        }
                    }
                }
            }
            
            // Cost constraint handling - More inclusive for low-income users
            if (costMatch) {
                costMatches++;
                scoredDishes.add(new ScoredDish(dish, score));
                Log.d("NutritionEngine", "Added dish: " + dish.name + " (Score: " + score + ")");
            } else {
                // For low-income users, be more inclusive - don't exclude dishes without cost tags
                // Most Filipino dishes are affordable by default
                String income = (String) userData.get("income");
                if (income != null && (income.contains("Below PHP 12,030") || income.contains("PHP 12,031"))) {
                    // Low-income users get more inclusive recommendations
                    // Give a small bonus for implicitly affordable dishes to balance with explicit cost tags
                    score += 5.0;
                    costMatches++;
                    scoredDishes.add(new ScoredDish(dish, score));
                    Log.d("NutritionEngine", "Added dish for low-income user (no cost tag): " + dish.name + " (Score: " + score + ")");
                } else {
                    // For higher income, still apply cost constraints
                    Log.d("NutritionEngine", "Skipped dish due to cost constraint: " + dish.name);
                }
            }
        }
        
        Log.d("NutritionEngine", "Total dishes: " + totalDishes + ", Age group matches: " + ageGroupMatches + ", Cost matches: " + costMatches);
        
        // Sort by score (highest first)
        Collections.sort(scoredDishes, (a, b) -> Double.compare(b.score, a.score));
        
        return scoredDishes;
    }
    
    private List<DishData.Dish> getTopRecommendations(List<ScoredDish> scoredDishes) {
        List<DishData.Dish> recommendations = new ArrayList<>();
        
        // Enhanced diversity-based selection for better variety
        if (scoredDishes.size() <= 25) {
            // If we have 25 or fewer dishes, return all for maximum variety
            for (ScoredDish scoredDish : scoredDishes) {
                recommendations.add(scoredDish.dish);
            }
            Log.d("NutritionEngine", "Returning all " + scoredDishes.size() + " dishes for maximum variety");
        } else {
            // Smart diversity selection: mix high-scoring with diverse options
            Set<String> selectedCategories = new HashSet<>();
            Set<String> selectedCookingMethods = new HashSet<>();
            Set<String> selectedMainIngredients = new HashSet<>();
            
            // First, add top 10 highest-scoring dishes
            int topCount = Math.min(10, scoredDishes.size());
            for (int i = 0; i < topCount; i++) {
                DishData.Dish dish = scoredDishes.get(i).dish;
                recommendations.add(dish);
                
                // Track what we've selected
                updateDiversityTracking(dish, selectedCategories, selectedCookingMethods, selectedMainIngredients);
            }
            
            // Then add diverse dishes to fill remaining slots (up to 25 total)
            for (int i = topCount; i < scoredDishes.size() && recommendations.size() < 25; i++) {
                DishData.Dish dish = scoredDishes.get(i).dish;
                
                // Check if this dish adds diversity
                if (addsDiversity(dish, selectedCategories, selectedCookingMethods, selectedMainIngredients)) {
                    recommendations.add(dish);
                    updateDiversityTracking(dish, selectedCategories, selectedCookingMethods, selectedMainIngredients);
                    Log.d("NutritionEngine", "Added diverse dish: " + dish.name);
                }
            }
            
            // If we still have slots, add remaining high-scoring dishes
            for (int i = topCount; i < scoredDishes.size() && recommendations.size() < 25; i++) {
                DishData.Dish dish = scoredDishes.get(i).dish;
                if (!recommendations.contains(dish)) {
                    recommendations.add(dish);
                }
            }
        }
        
        Log.d("NutritionEngine", "Final diverse recommendations count: " + recommendations.size());
        return recommendations;
    }
    
    private void updateDiversityTracking(DishData.Dish dish, Set<String> categories, Set<String> cookingMethods, Set<String> mainIngredients) {
        // Track food categories
        for (String tag : dish.tags) {
            if (tag.equals("VEG") || tag.equals("VGN") || tag.equals("PES") || tag.equals("GF") || tag.equals("DF")) {
                categories.add(tag);
            }
            if (tag.equals("FRI") || tag.equals("STE") || tag.equals("GRI") || tag.equals("BOI") || tag.equals("BRA")) {
                cookingMethods.add(tag);
            }
            if (tag.equals("RIC") || tag.equals("MEA") || tag.equals("PORK") || tag.equals("BEEF") || tag.equals("FISH") || tag.equals("SEA") || tag.equals("VEGG") || tag.equals("FRT") || tag.equals("SOUP") || tag.equals("DES") || tag.equals("SNK")) {
                mainIngredients.add(tag);
            }
        }
    }
    
    private boolean addsDiversity(DishData.Dish dish, Set<String> categories, Set<String> cookingMethods, Set<String> mainIngredients) {
        // Check if this dish adds new categories, cooking methods, or main ingredients
        for (String tag : dish.tags) {
            if (tag.equals("VEG") || tag.equals("VGN") || tag.equals("PES") || tag.equals("GF") || tag.equals("DF")) {
                if (!categories.contains(tag)) return true;
            }
            if (tag.equals("FRI") || tag.equals("STE") || tag.equals("GRI") || tag.equals("BOI") || tag.equals("BRA")) {
                if (!cookingMethods.contains(tag)) return true;
            }
            if (tag.equals("RIC") || tag.equals("MEA") || tag.equals("PORK") || tag.equals("BEEF") || tag.equals("FISH") || tag.equals("SEA") || tag.equals("VEGG") || tag.equals("FRT") || tag.equals("SOUP") || tag.equals("DES") || tag.equals("SNK")) {
                if (!mainIngredients.contains(tag)) return true;
            }
        }
        return false;
    }

    private boolean containsAnyIgnoreCase(String haystack, String[] needles) {
        if (haystack == null) return false;
        String s = haystack.toLowerCase();
        for (String n : needles) {
            if (s.contains(n.toLowerCase())) return true;
        }
        return false;
    }
    
    private List<DishData.Dish> getDefaultRecommendations() {
        // Return comprehensive default recommendations when user data is not available
        List<DishData.Dish> defaultRecs = new ArrayList<>();
        
        // Add popular Filipino dishes across different categories
        String[] popularDishes = {
            "Steamed Rice", "Adobo", "Sinigang na Baboy", "Tinola", "Kare-kare",
            "Lechon Manok", "Chicken Inasal", "Bulalo", "Nilagang Baka",
            "Pancit Canton", "Arroz Caldo", "Champorado", "Halo-halo",
            "Biko", "Bibingka", "Sinangag", "Lumpiang Shanghai",
            "Fish Balls", "Chicken Balls", "Ukoy", "Fresh Lumpia"
        };
        
        for (String dishName : popularDishes) {
            for (DishData.Dish dish : DishData.DISHES) {
                if (dish.name.equals(dishName)) {
                    defaultRecs.add(dish);
                    break;
                }
            }
        }
        
        // If still less than 15, add more dishes
        if (defaultRecs.size() < 15) {
            for (DishData.Dish dish : DishData.DISHES) {
                if (defaultRecs.size() >= 20) break;
                if (!defaultRecs.contains(dish)) {
                    defaultRecs.add(dish);
                }
            }
        }
        
        return defaultRecs;
    }
    
    private int calculateAgeInMonths(String birthday) {
        if (birthday == null || birthday.isEmpty() || "null".equals(birthday)) {
            Log.d("NutritionEngine", "Birthday is null, empty, or 'null' string, using default age");
            return 24; // Default to 2 years
        }
        
        try {
            String[] parts = birthday.split("-");
            if (parts.length == 3) {
                int birthYear = Integer.parseInt(parts[0]);
                int birthMonth = Integer.parseInt(parts[1]);
                int birthDay = Integer.parseInt(parts[2]);

                java.util.Calendar today = java.util.Calendar.getInstance();
                int currentYear = today.get(java.util.Calendar.YEAR);
                int currentMonth = today.get(java.util.Calendar.MONTH) + 1; // 0-based
                int currentDay = today.get(java.util.Calendar.DAY_OF_MONTH);

                int ageYears = currentYear - birthYear;
                int ageMonths = ageYears * 12 + (currentMonth - birthMonth);
                if (currentDay < birthDay) {
                    ageMonths -= 1; // Not completed this month yet
                }
                return Math.max(ageMonths, 0);
            }
        } catch (Exception e) {
            Log.e("NutritionEngine", "Error calculating age from birthday '" + birthday + "': " + e.getMessage());
        }
        return 24; // Default to 2 years
    }
    
    private int calculateRiskScore(JSONObject screening) {
        int score = 0;
        
        // Weight loss
        String weightLoss = screening.optString("weight_loss", "");
        if ("yes".equals(weightLoss)) score += 30;
        else if ("not_sure".equals(weightLoss)) score += 15;
        
        // Swelling
        String swelling = screening.optString("swelling", "");
        if ("yes".equals(swelling)) score += 20;
        
        // Feeding behavior
        String feeding = screening.optString("feeding_behavior", "");
        if ("poor".equals(feeding)) score += 25;
        else if ("moderate".equals(feeding)) score += 10;
        
        // Physical signs
        String physicalSigns = screening.optString("physical_signs", "");
        if (physicalSigns.contains("thin")) score += 15;
        if (physicalSigns.contains("shorter")) score += 10;
        if (physicalSigns.contains("weak")) score += 10;
        
        // Dietary diversity
        int diversity = screening.optInt("dietary_diversity", 0);
        if (diversity <= 2) score += 20;
        else if (diversity <= 4) score += 10;
        
        return Math.min(score, 100);
    }
    
    private static class ScoredDish {
        DishData.Dish dish;
        double score;
        
        ScoredDish(DishData.Dish dish, double score) {
            this.dish = dish;
            this.score = score;
        }
    }

    private static class FavoritesData {
        Set<String> favoriteNames = new HashSet<>();
        Map<String, Integer> favoriteTagCounts = new HashMap<>();
    }

    private FavoritesData getUserFavoritesData(String userEmail) {
        FavoritesData data = new FavoritesData();
        try {
            SQLiteDatabase db = dbHelper.getReadableDatabase();
            Cursor cursor = db.query(
                UserPreferencesDbHelper.TABLE_FAVORITES,
                new String[]{
                    UserPreferencesDbHelper.COL_FAVORITE_DISH_NAME,
                    UserPreferencesDbHelper.COL_FAVORITE_DISH_TAGS
                },
                UserPreferencesDbHelper.COL_FAVORITE_USER_EMAIL + "=?",
                new String[]{userEmail},
                null, null, null
            );
            while (cursor.moveToNext()) {
                String name = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_FAVORITE_DISH_NAME));
                String tags = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_FAVORITE_DISH_TAGS));
                if (name != null) data.favoriteNames.add(name);
                if (tags != null) {
                    String cleaned = tags.replace("[", "").replace("]", "");
                    for (String t : cleaned.split(",")) {
                        String tag = t.trim();
                        if (!tag.isEmpty()) {
                            data.favoriteTagCounts.put(tag, data.favoriteTagCounts.getOrDefault(tag, 0) + 1);
                        }
                    }
                }
            }
            cursor.close();
        } catch (Exception e) {
            Log.e("NutritionEngine", "Error loading favorites: " + e.getMessage());
        }
        return data;
    }
    
    /**
     * Apply dietary filters (allergies, preferences) with SMART priority-aware filtering
     */
    private List<DishData.Dish> applyDietaryFilters(List<DishData.Dish> recommendations, Map<String, Object> userData) {
        List<DishData.Dish> filtered = new ArrayList<>();
        
        // Get dietary preferences and allergies
        String dietPrefsStr = (String) userData.get("diet_prefs");
        String allergiesStr = (String) userData.get("allergies");
        
        List<String> dietPrefs = new ArrayList<>();
        List<String> allergies = new ArrayList<>();
        
        // Parse diet preferences
        if (dietPrefsStr != null && !dietPrefsStr.trim().isEmpty()) {
            String[] prefs = dietPrefsStr.split(",");
            for (String pref : prefs) {
                String trimmed = pref.trim();
                if (!trimmed.isEmpty()) {
                    dietPrefs.add(trimmed);
                }
            }
        }
        
        // Parse allergies
        if (allergiesStr != null && !allergiesStr.trim().isEmpty()) {
            String[] alls = allergiesStr.split(",");
            for (String allergy : alls) {
                String trimmed = allergy.trim();
                if (!trimmed.isEmpty()) {
                    allergies.add(trimmed);
                }
            }
        }
        
        Log.d("NutritionEngine", "Applying SMART dietary filters - Diet prefs: " + dietPrefs + ", Allergies: " + allergies);
        
        // If no dietary preferences, return all recommendations
        if (dietPrefs.isEmpty()) {
            Log.d("NutritionEngine", "No dietary preferences, returning all recommendations");
            return recommendations;
        }
        
        // SMART FILTERING: Apply dietary preferences while preserving nutritional priorities
        List<DishData.Dish> priorityPreserved = new ArrayList<>();
        List<DishData.Dish> standardFiltered = new ArrayList<>();
        List<DishData.Dish> smartMatches = new ArrayList<>();
        
        for (DishData.Dish dish : recommendations) {
            boolean include = true;
            
            // Check allergies (always strict for safety)
            if (!allergies.isEmpty()) {
                for (String allergy : allergies) {
                    if (containsAllergen(dish, allergy)) {
                        include = false;
                        Log.d("NutritionEngine", "Excluding " + dish.name + " due to allergy: " + allergy);
                        break;
                    }
                }
            }
            
            if (include) {
                // SMART DIETARY FILTERING: Check if dish matches dietary preferences
                boolean matchesDietary = matchesDietaryPreference(dish, dietPrefs);
                
                if (matchesDietary) {
                    // Check if this is a smart match (intelligent combination)
                    if (isSmartDietaryMatch(dish, dietPrefs)) {
                        smartMatches.add(dish);
                        Log.d("NutritionEngine", "Smart match found: " + dish.name + " for preferences: " + dietPrefs);
                    } else {
                        // Standard filtering - dish meets dietary requirements
                        standardFiltered.add(dish);
                    }
                } else {
                    // SMART PRIORITY PRESERVATION: Check if this dish has high nutritional value
                    // that might be worth including despite dietary preference mismatch
                    if (hasHighNutritionalValue(dish)) {
                        priorityPreserved.add(dish);
                        Log.d("NutritionEngine", "Priority preserving " + dish.name + " despite dietary preference mismatch");
                    }
                }
            }
        }
        
        // Combine results: prioritize smart matches first, then standard matches, then priority-preserved dishes
        filtered.addAll(smartMatches);
        filtered.addAll(standardFiltered);
        
        // If we have enough results, add some priority-preserved dishes for variety
        if (filtered.size() >= 10) {
            // Add up to 3 priority-preserved dishes for variety
            int toAdd = Math.min(3, priorityPreserved.size());
            for (int i = 0; i < toAdd; i++) {
                filtered.add(priorityPreserved.get(i));
            }
        } else if (filtered.size() < 5) {
            // If we have very few results, add more priority-preserved dishes
            int toAdd = Math.min(8, priorityPreserved.size());
            for (int i = 0; i < toAdd; i++) {
                filtered.add(priorityPreserved.get(i));
            }
        }
        
        Log.d("NutritionEngine", "SMART filtering complete: " + filtered.size() + " dishes (smart: " + smartMatches.size() + ", standard: " + standardFiltered.size() + ", priority-preserved: " + priorityPreserved.size() + ")");
        
        // If still too few results, apply lenient filtering
        if (filtered.size() < 5 && !dietPrefs.isEmpty()) {
            Log.d("NutritionEngine", "Still too few results (" + filtered.size() + "), applying lenient filtering");
            return applyLenientDietaryFiltering(recommendations, dietPrefs, allergies);
        }
        
        // ENSURE MINIMUM RECOMMENDATIONS: If we still don't have enough, add fallback options
        if (filtered.size() < 8) {
            Log.d("NutritionEngine", "Still insufficient results (" + filtered.size() + "), adding fallback options");
            filtered = addFallbackRecommendations(filtered, recommendations, dietPrefs, allergies);
        }
        
        return filtered;
    }
    
    /**
     * Apply more lenient dietary filtering when strict filtering produces too few results
     */
    private List<DishData.Dish> applyLenientDietaryFiltering(List<DishData.Dish> recommendations, List<String> dietPrefs, List<String> allergies) {
        List<DishData.Dish> lenientFiltered = new ArrayList<>();
        
        Log.d("NutritionEngine", "Applying lenient dietary filtering for: " + dietPrefs);
        
        for (DishData.Dish dish : recommendations) {
            boolean include = true;
            
            // Check allergies (still strict for safety)
            if (!allergies.isEmpty()) {
                for (String allergy : allergies) {
                    if (containsAllergen(dish, allergy)) {
                        include = false;
                        break;
                    }
                }
            }
            
            if (include) {
                // For vegetarian: be more lenient, allow dishes that might have small amounts of animal products
                // but are primarily plant-based
                boolean isPrimarilyPlantBased = isPrimarilyPlantBased(dish);
                
                for (String pref : dietPrefs) {
                    String prefLower = pref.toLowerCase();
                    if ("vegetarian".equals(prefLower)) {
                        // Allow dishes that are primarily plant-based, even if they might have small amounts of animal products
                        if (isPrimarilyPlantBased) {
                            include = true;
                        } else {
                            include = false;
                        }
                        break;
                    } else if ("vegan".equals(prefLower)) {
                        // Still strict for vegan
                        include = !containsAnimalProducts(dish);
                        break;
                    }
                }
            }
            
            if (include) {
                lenientFiltered.add(dish);
            }
        }
        
        Log.d("NutritionEngine", "Lenient filtering complete: " + lenientFiltered.size() + " dishes remaining");
        return lenientFiltered;
    }
    
    /**
     * Check if a dish is primarily plant-based (more than 70% plant ingredients)
     */
    private boolean isPrimarilyPlantBased(DishData.Dish dish) {
        // Count plant-based vs animal-based indicators
        int plantIndicators = 0;
        int animalIndicators = 0;
        
        // Plant-based indicators (expanded list)
        String[] plantKeywords = {"vegetable", "fruit", "grain", "bean", "legume", "rice", "corn", "potato", "tomato", "onion", "garlic", "ginger", "coconut", "ampalaya", "sayote", "repolyo", "cabbage", "carrot", "chayote", "bitter", "mung", "taro", "leaf"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        
        for (String keyword : plantKeywords) {
            if (dishText.contains(keyword)) {
                plantIndicators++;
            }
        }
        
        // Animal-based indicators
        String[] animalKeywords = {"meat", "chicken", "beef", "pork", "fish", "egg", "milk", "cheese", "butter", "yogurt", "liver", "blood", "intestine", "tripe"};
        for (String keyword : animalKeywords) {
            if (dishText.contains(keyword)) {
                animalIndicators++;
            }
        }
        
        // More lenient for adults - if plant indicators exist and don't heavily favor animal products
        return plantIndicators > 0 && (plantIndicators >= animalIndicators || animalIndicators <= 1);
    }
    
    private boolean containsAllergen(DishData.Dish dish, String allergy) {
        String allergyLower = allergy.toLowerCase();
        for (String tag : dish.tags) {
            if (tag.toLowerCase().contains(allergyLower)) {
                return true;
            }
        }
        return dish.name.toLowerCase().contains(allergyLower) || 
               dish.desc.toLowerCase().contains(allergyLower);
    }
    
    private boolean matchesDietaryPreference(DishData.Dish dish, List<String> preferences) {
        // SMART DIETARY MATCHING: First check for intelligent combinations
        if (isSmartDietaryMatch(dish, preferences)) {
            Log.d("NutritionEngine", "Smart match found for " + dish.name + " with preferences: " + preferences);
            return true;
        }
        
        // Enhanced matching logic using dish tags
        for (String pref : preferences) {
            String prefLower = pref.toLowerCase();
            
            // Check if dish has explicit dietary tags
            boolean hasExplicitTag = false;
            for (String tag : dish.tags) {
                String tagLower = tag.toLowerCase();
                if (tagLower.equals(prefLower) || tagLower.equals("non-" + prefLower)) {
                    hasExplicitTag = true;
                    if (tagLower.equals("non-" + prefLower)) {
                        return false; // Dish explicitly marked as non-compatible
                    }
                    break;
                }
            }
            
            // If no explicit tag, use fallback logic
            if (!hasExplicitTag) {
                if ("vegetarian".equals(prefLower)) {
                    if (containsMeat(dish)) return false;
                }
                if ("vegan".equals(prefLower)) {
                    if (containsAnimalProducts(dish)) return false;
                }
                if ("pescatarian".equals(prefLower)) {
                    if (containsMeat(dish) && !containsFish(dish)) return false;
                }
                if ("gluten-free".equals(prefLower)) {
                    if (containsGluten(dish)) return false;
                }
                if ("dairy-free".equals(prefLower)) {
                    if (containsDairy(dish)) return false;
                }
                if ("nut-free".equals(prefLower)) {
                    if (containsNuts(dish)) return false;
                }
            }
        }
        return true;
    }
    
    /**
     * SMART DIETARY MATCHING: Check if a dish is a good match for specific dietary + nutritional combinations
     */
    private boolean isSmartDietaryMatch(DishData.Dish dish, List<String> preferences) {
        // Check for smart combinations like "vegetarian + high-protein"
        boolean hasVegetarian = preferences.stream().anyMatch(p -> p.toLowerCase().contains("vegetarian"));
        boolean hasVegan = preferences.stream().anyMatch(p -> p.toLowerCase().contains("vegan"));
        boolean hasHighProtein = preferences.stream().anyMatch(p -> p.toLowerCase().contains("high-protein") || p.toLowerCase().contains("protein"));
        boolean hasHighEnergy = preferences.stream().anyMatch(p -> p.toLowerCase().contains("high-energy") || p.toLowerCase().contains("energy"));
        boolean hasIronRich = preferences.stream().anyMatch(p -> p.toLowerCase().contains("iron") || p.toLowerCase().contains("iron-rich"));
        boolean hasVitaminA = preferences.stream().anyMatch(p -> p.toLowerCase().contains("vitamin-a") || p.toLowerCase().contains("vitamin a"));
        boolean hasVitaminC = preferences.stream().anyMatch(p -> p.toLowerCase().contains("vitamin-c") || p.toLowerCase().contains("vitamin c"));
        
        // If user wants vegetarian + high-protein, prioritize plant-based protein sources
        if ((hasVegetarian || hasVegan) && hasHighProtein) {
            return isPlantBasedProteinSource(dish);
        }
        
        // If user wants vegetarian + high-energy, prioritize energy-dense plant foods
        if ((hasVegetarian || hasVegan) && hasHighEnergy) {
            return isEnergyDensePlantFood(dish);
        }
        
        // If user wants vegetarian + iron-rich, prioritize iron-rich plant foods
        if ((hasVegetarian || hasVegan) && hasIronRich) {
            return isIronRichPlantFood(dish);
        }
        
        // If user wants vegetarian + vitamin-rich, prioritize vitamin-rich plant foods
        if ((hasVegetarian || hasVegan) && (hasVitaminA || hasVitaminC)) {
            return isVitaminRichPlantFood(dish);
        }
        
        return false;
    }
    
    /**
     * Add fallback recommendations when strict filtering produces too few results
     * This ensures users always have multiple options to choose from
     */
    private List<DishData.Dish> addFallbackRecommendations(List<DishData.Dish> currentResults, List<DishData.Dish> allRecommendations, List<String> dietPrefs, List<String> allergies) {
        List<DishData.Dish> fallbackResults = new ArrayList<>(currentResults);
        Set<String> alreadyIncluded = new HashSet<>();
        
        // Mark dishes already included
        for (DishData.Dish dish : currentResults) {
            alreadyIncluded.add(dish.name);
        }
        
        Log.d("NutritionEngine", "Adding fallback recommendations. Current: " + currentResults.size() + ", Target: 8+");
        
        // Strategy 1: Add dishes that are close to dietary preferences
        for (DishData.Dish dish : allRecommendations) {
            if (fallbackResults.size() >= 12) break; // Don't add too many
            if (alreadyIncluded.contains(dish.name)) continue;
            
            // Check if this dish could be a reasonable option
            if (couldBeReasonableOption(dish, dietPrefs, allergies)) {
                fallbackResults.add(dish);
                alreadyIncluded.add(dish.name);
                Log.d("NutritionEngine", "Added fallback option: " + dish.name + " (close to preferences)");
            }
        }
        
        // Strategy 2: If still not enough, add some general nutritious options
        if (fallbackResults.size() < 8) {
            for (DishData.Dish dish : allRecommendations) {
                if (fallbackResults.size() >= 10) break;
                if (alreadyIncluded.contains(dish.name)) continue;
                
                // Add dishes with good nutritional value
                if (hasGoodNutritionalValue(dish)) {
                    fallbackResults.add(dish);
                    alreadyIncluded.add(dish.name);
                    Log.d("NutritionEngine", "Added nutritional fallback: " + dish.name);
                }
            }
        }
        
        // Strategy 3: Last resort - add any remaining dishes that don't have major conflicts
        if (fallbackResults.size() < 8) {
            for (DishData.Dish dish : allRecommendations) {
                if (fallbackResults.size() >= 12) break;
                if (alreadyIncluded.contains(dish.name)) continue;
                
                // Only exclude if it has major conflicts
                if (!hasMajorDietaryConflicts(dish, dietPrefs, allergies)) {
                    fallbackResults.add(dish);
                    alreadyIncluded.add(dish.name);
                    Log.d("NutritionEngine", "Added general fallback: " + dish.name);
                }
            }
        }
        
        Log.d("NutritionEngine", "Fallback recommendations complete: " + fallbackResults.size() + " total options");
        return fallbackResults;
    }
    
    /**
     * Check if a dish could be a reasonable option despite not being a perfect match
     */
    private boolean couldBeReasonableOption(DishData.Dish dish, List<String> dietPrefs, List<String> allergies) {
        // Check for allergies (always strict)
        if (!allergies.isEmpty()) {
            for (String allergy : allergies) {
                if (containsAllergen(dish, allergy)) {
                    return false;
                }
            }
        }
        
        // For vegetarian preferences, allow dishes that are primarily plant-based
        for (String pref : dietPrefs) {
            String prefLower = pref.toLowerCase();
            if ("vegetarian".equals(prefLower)) {
                return isPrimarilyPlantBased(dish) || !containsMeat(dish);
            }
            if ("vegan".equals(prefLower)) {
                return !containsAnimalProducts(dish);
            }
            if ("pescatarian".equals(prefLower)) {
                return !containsMeat(dish) || containsFish(dish);
            }
        }
        
        return true;
    }
    
    /**
     * Check if a dish has good nutritional value
     */
    private boolean hasGoodNutritionalValue(DishData.Dish dish) {
        // Check for nutritional tags
        String[] nutritionalTags = {"HP", "HI", "HVA", "HVC", "ED", "DIG", "BAL"};
        for (String tag : dish.tags) {
            for (String nutritionalTag : nutritionalTags) {
                if (tag.equalsIgnoreCase(nutritionalTag)) {
                    return true;
                }
            }
        }
        
        // Check for traditional Filipino dishes (usually nutritious)
        if (dish.tags.contains("traditional")) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if a dish has major dietary conflicts
     */
    private boolean hasMajorDietaryConflicts(DishData.Dish dish, List<String> dietPrefs, List<String> allergies) {
        // Check allergies
        if (!allergies.isEmpty()) {
            for (String allergy : allergies) {
                if (containsAllergen(dish, allergy)) {
                    return true;
                }
            }
        }
        
        // Check for major dietary preference violations
        for (String pref : dietPrefs) {
            String prefLower = pref.toLowerCase();
            if ("vegetarian".equals(prefLower) && containsMeat(dish)) {
                return true;
            }
            if ("vegan".equals(prefLower) && containsAnimalProducts(dish)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a dish is a good plant-based protein source
     */
    private boolean isPlantBasedProteinSource(DishData.Dish dish) {
        // Check for plant-based protein indicators
        String[] plantProteinKeywords = {"bean", "legume", "tofu", "tempeh", "seitan", "lentil", "chickpea", "mung", "soy", "edamame", "quinoa", "amaranth", "spirulina", "chia", "hemp"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        
        for (String keyword : plantProteinKeywords) {
            if (dishText.contains(keyword)) {
                return true;
            }
        }
        
        // Also check for high-protein tags
        for (String tag : dish.tags) {
            if (tag.equalsIgnoreCase("HP") || tag.equalsIgnoreCase("high-protein")) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a dish is an energy-dense plant food
     */
    private boolean isEnergyDensePlantFood(DishData.Dish dish) {
        // Check for energy-dense plant food indicators
        String[] energyKeywords = {"banana", "avocado", "coconut", "nut", "seed", "dried fruit", "sweet potato", "yam", "cassava", "plantain"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        
        for (String keyword : energyKeywords) {
            if (dishText.contains(keyword)) {
                return true;
            }
        }
        
        // Also check for high-energy tags
        for (String tag : dish.tags) {
            if (tag.equalsIgnoreCase("ED") || tag.equalsIgnoreCase("high-energy")) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a dish is an iron-rich plant food
     */
    private boolean isIronRichPlantFood(DishData.Dish dish) {
        // Check for iron-rich plant food indicators
        String[] ironKeywords = {"spinach", "kale", "malunggay", "moringa", "bean", "legume", "lentil", "chickpea", "tofu", "tempeh", "quinoa", "amaranth", "pumpkin seed", "sunflower seed"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        
        for (String keyword : ironKeywords) {
            if (dishText.contains(keyword)) {
                return true;
            }
        }
        
        // Also check for iron-rich tags
        for (String tag : dish.tags) {
            if (tag.equalsIgnoreCase("HI") || tag.equalsIgnoreCase("iron-rich")) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if a dish is a vitamin-rich plant food
     */
    private boolean isVitaminRichPlantFood(DishData.Dish dish) {
        // Check for vitamin-rich plant food indicators
        String[] vitaminKeywords = {"carrot", "sweet potato", "pumpkin", "mango", "papaya", "orange", "lemon", "guava", "bell pepper", "broccoli", "spinach", "kale", "malunggay", "moringa"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        
        for (String keyword : vitaminKeywords) {
            if (dishText.contains(keyword)) {
                return true;
            }
        }
        
        // Also check for vitamin tags
        for (String tag : dish.tags) {
            if (tag.equalsIgnoreCase("HVA") || tag.equalsIgnoreCase("HVC") || tag.equalsIgnoreCase("vitamin-a") || tag.equalsIgnoreCase("vitamin-c")) {
                return true;
            }
        }
        
        return false;
    }
    
    private boolean containsMeat(DishData.Dish dish) {
        String[] meatKeywords = {"chicken", "beef", "pork", "fish", "meat", "liver"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        for (String keyword : meatKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return false;
    }
    
    private boolean containsAnimalProducts(DishData.Dish dish) {
        String[] animalKeywords = {"milk", "cheese", "egg", "butter", "yogurt", "honey"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        for (String keyword : animalKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return containsMeat(dish);
    }
    
    private boolean containsFish(DishData.Dish dish) {
        String[] fishKeywords = {"fish", "bangus", "tilapia", "galunggong", "hipon", "shrimp", "seafood"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        for (String keyword : fishKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return false;
    }
    
    private boolean containsGluten(DishData.Dish dish) {
        String[] glutenKeywords = {"wheat", "flour", "bread", "noodles", "pasta", "pancit", "pansit"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        Log.d("NutritionEngine", "Checking gluten in " + dish.name + ": " + dishText);
        for (String keyword : glutenKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return false;
    }
    
    private boolean containsDairy(DishData.Dish dish) {
        String[] dairyKeywords = {"milk", "cheese", "butter", "yogurt", "cream", "gatas"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        for (String keyword : dairyKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return false;
    }
    
    private boolean containsNuts(DishData.Dish dish) {
        String[] nutKeywords = {"peanut", "cashew", "almond", "walnut", "pili", "mani"};
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        for (String keyword : nutKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return false;
    }
    
    /**
     * Check if a dish has high nutritional value that should be preserved despite dietary preference mismatches
     */
    private boolean hasHighNutritionalValue(DishData.Dish dish) {
        // Check for high-protein, high-energy, or other critical nutritional tags
        String[] criticalTags = {"HP", "ED", "HI", "HVA", "HVC", "high-protein", "high-energy", "iron-rich", "vitamin-a", "vitamin-c", "calcium-rich"};
        
        for (String tag : dish.tags) {
            for (String criticalTag : criticalTags) {
                if (tag.equalsIgnoreCase(criticalTag)) {
                    return true;
                }
            }
        }
        
        // Also check description for nutritional keywords
        String dishText = (dish.name + " " + dish.desc).toLowerCase();
        String[] nutritionalKeywords = {"protein", "energy", "iron", "vitamin", "calcium", "nutrient", "healthy"};
        
        for (String keyword : nutritionalKeywords) {
            if (dishText.contains(keyword)) {
                return true;
            }
        }
        
        return false;
    }
}
