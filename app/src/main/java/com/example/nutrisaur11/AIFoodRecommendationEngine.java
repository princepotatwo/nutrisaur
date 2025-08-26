package com.example.nutrisaur11;

import android.content.Context;
import android.database.Cursor;
import android.util.Log;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import java.util.*;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

/**
 * AI-Powered Food Recommendation System
 * Integrates with database screening data to provide personalized nutrition recommendations
 * Based on WHO nutritional guidelines and evidence-based nutrition research
 */
public class AIFoodRecommendationEngine {
    
    private static final String TAG = "AIFoodEngine";
    private Context context;
    private UserPreferencesDbHelper dbHelper;
    
    // Enhanced WHO-based nutritional priorities with comprehensive screening data analysis
    public enum NutritionalPriority {
        SEVERE_ACUTE_MALNUTRITION,    // WHZ < -3, MUAC < 11.5cm, Risk Score 70+
        MODERATE_ACUTE_MALNUTRITION,  // WHZ < -2, MUAC < 12.5cm, Risk Score 40-69
        PROTEIN_ENERGY_DEFICIENCY,    // Low protein, high energy needs, weight loss
        MICRONUTRIENT_DEFICIENCY,     // Iron, Vitamin A, Zinc deficiency signs
        GROWTH_SUPPORT,               // For children with stunting, height issues
        IMMUNE_SYSTEM_SUPPORT,        // For frequent infections, recent illness
        DIGESTIVE_HEALTH,             // For poor feeding behavior, chewing issues
        WEIGHT_GAIN_SUPPORT,          // For significant weight loss, thin appearance
        BONE_DEVELOPMENT,             // For calcium needs, height concerns
        ENERGY_BOOST,                 // For fatigue, weakness, low energy
        APPETITE_STIMULATION,         // For poor appetite, food insecurity
        COMFORT_AND_APPEAL,           // For encouraging eating, familiar foods
        WEIGHT_MAINTENANCE,           // For normal nutritional status
        CHRONIC_DISEASE_PREVENTION,   // For long-term health, functional decline
        VITAMIN_A_FOODS,             // For vitamin A deficiency, vision issues
        IRON_RICH_FOODS,             // For iron deficiency, anemia
        BRAIN_DEVELOPMENT,            // For cognitive development in young children
        LOW_COST_FOODS,              // For low-income households
        REGIONAL_FOODS,              // For barangay-specific food preferences
        THERAPEUTIC_FOODS             // For severe malnutrition recovery
    }
    
    // Enhanced AI Food Categories with comprehensive nutritional profiles
    public enum AIFoodCategory {
        THERAPEUTIC_FOODS("High-energy, high-protein foods for severe malnutrition recovery"),
        IRON_RICH_FOODS("Iron-fortified foods for anemia prevention and treatment"),
        CALCIUM_SOURCES("Calcium-rich foods for bone development and height support"),
        VITAMIN_A_FOODS("Vitamin A rich foods for vision, immunity, and growth"),
        PROTEIN_POWERHOUSES("High-quality protein sources for muscle development and repair"),
        ENERGY_DENSE_FOODS("Calorie-dense foods for weight gain and energy needs"),
        IMMUNE_BOOSTING_FOODS("Foods rich in vitamins C, E, and zinc for infection resistance"),
        DIGESTIVE_FRIENDLY_FOODS("Easy-to-digest, gut-friendly options for feeding issues"),
        HYDRATING_FOODS("Water-rich foods and drinks for hydration and appetite"),
        COMFORT_FOODS("Familiar, appealing foods to encourage eating and appetite"),
        WEIGHT_GAIN_FOODS("High-calorie, nutrient-dense foods for weight restoration"),
        APPETITE_STIMULATING_FOODS("Flavorful, aromatic foods to increase food interest"),
        BONE_STRENGTHENING_FOODS("Calcium and vitamin D rich foods for skeletal health"),
        ENERGY_BOOSTING_FOODS("Complex carbohydrates and healthy fats for sustained energy"),
        GUT_HEALTH_FOODS("Probiotic and fiber-rich foods for digestive wellness");
        
        private final String description;
        
        AIFoodCategory(String description) {
            this.description = description;
        }
        
        public String getDescription() {
            return description;
        }
    }
    
    public AIFoodRecommendationEngine(Context context) {
        this.context = context;
        this.dbHelper = new UserPreferencesDbHelper(context);
    }
    
    /**
     * AI-powered personalized food recommendations
     * Analyzes user's complete screening data to generate intelligent recommendations
     */
    public List<DishData.Dish> getAIPersonalizedRecommendations(String userEmail) {
        Log.d(TAG, "Generating AI-powered recommendations for user: " + userEmail);
        
        // Get comprehensive user data
        UserNutritionalProfile profile = analyzeUserNutritionalProfile(userEmail);
        
        // Determine nutritional priorities using AI logic
        List<NutritionalPriority> priorities = determineNutritionalPriorities(profile);
        
        Log.d(TAG, "User Profile Analysis:");
        Log.d(TAG, "  - Risk Score: " + profile.riskScore);
        Log.d(TAG, "  - BMI: " + profile.bmi);
        Log.d(TAG, "  - Age: " + profile.birthday);
        Log.d(TAG, "  - Gender: " + profile.gender);
        Log.d(TAG, "  - Screening Data Fields: " + (profile.screeningData != null ? profile.screeningData.size() : 0));
        if (profile.screeningData != null) {
            for (Map.Entry<String, String> entry : profile.screeningData.entrySet()) {
                Log.d(TAG, "    " + entry.getKey() + ": " + entry.getValue());
            }
        }
        Log.d(TAG, "  - Nutritional Priorities: " + priorities.size());
        for (NutritionalPriority priority : priorities) {
            Log.d(TAG, "    - " + priority.name());
        }
        
        // Generate targeted food recommendations
        List<DishData.Dish> recommendations = generateTargetedRecommendations(priorities, profile);
        
        // Apply dietary filters (allergies, preferences)
        recommendations = applyDietaryFilters(recommendations, profile);
        
        // Sort by nutritional impact and user preference
        recommendations = rankRecommendationsByImpact(recommendations, priorities);
        
        Log.d(TAG, "Generated " + recommendations.size() + " AI-powered recommendations");
        return recommendations;
    }
    
    /**
     * Analyze user's complete nutritional profile from database
     */
    private UserNutritionalProfile analyzeUserNutritionalProfile(String userEmail) {
        UserNutritionalProfile profile = new UserNutritionalProfile();
        
        try {
            // Get all user data from consolidated user_preferences table
            Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
                "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + 
                " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?", 
                new String[]{userEmail}
            );
            
            if (cursor.moveToFirst()) {
                // Basic demographics
                profile.email = userEmail;
                profile.riskScore = cursor.getInt(cursor.getColumnIndex(UserPreferencesDbHelper.COL_RISK_SCORE));
                profile.allergies = parseJsonArray(cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_ALLERGIES)));
                profile.dietaryPreferences = parseJsonArray(cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_DIET_PREFS)));
                profile.avoidFoods = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_AVOID_FOODS));
                
                // Profile data
                String name = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_USER_NAME));
                String birthday = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_USER_BIRTHDAY));
                String gender = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_USER_GENDER));
                Float height = getFloat(cursor, UserPreferencesDbHelper.COL_USER_HEIGHT);
                Float weight = getFloat(cursor, UserPreferencesDbHelper.COL_USER_WEIGHT);
                Float bmi = getFloat(cursor, UserPreferencesDbHelper.COL_USER_BMI);
                
                profile.name = name;
                profile.birthday = birthday;
                profile.gender = gender;
                profile.height = height;
                profile.weight = weight;
                profile.bmi = bmi;
                
                // Parse screening answers for detailed analysis
                String screeningAnswersJson = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_SCREENING_ANSWERS));
                if (screeningAnswersJson != null && !screeningAnswersJson.isEmpty()) {
                    profile.screeningData = parseScreeningData(screeningAnswersJson);
                    
                    // Extract additional clinical risk factors for better recommendations
                    try {
                        JSONObject screening = new JSONObject(screeningAnswersJson);
                        profile.hasRecentIllness = screening.optBoolean("has_recent_illness", false);
                        profile.hasEatingDifficulty = screening.optBoolean("has_eating_difficulty", false);
                        profile.hasFoodInsecurity = screening.optBoolean("has_food_insecurity", false);
                        profile.hasMicronutrientDeficiency = screening.optBoolean("has_micronutrient_deficiency", false);
                        profile.hasFunctionalDecline = screening.optBoolean("has_functional_decline", false);
                        
                        Log.d("AIFoodEngine", "Clinical risk factors - illness: " + profile.hasRecentIllness + 
                              ", eating: " + profile.hasEatingDifficulty + 
                              ", food: " + profile.hasFoodInsecurity + 
                              ", micronutrient: " + profile.hasMicronutrientDeficiency + 
                              ", functional: " + profile.hasFunctionalDecline);
                        
                        // Also extract key screening fields for better recommendations
                        if (profile.screeningData != null) {
                            profile.screeningData.put("swelling", screening.optString("swelling", ""));
                            profile.screeningData.put("weight_loss", screening.optString("weight_loss", ""));
                            profile.screeningData.put("feeding_behavior", screening.optString("feeding_behavior", ""));
                            profile.screeningData.put("physical_signs", screening.optString("physical_signs", ""));
                            profile.screeningData.put("dietary_diversity", screening.optString("dietary_diversity", ""));
                            profile.screeningData.put("muac", screening.optString("muac", ""));
                            profile.screeningData.put("gender", screening.optString("gender", ""));
                            profile.screeningData.put("age", screening.optString("age", ""));
                            profile.screeningData.put("income", screening.optString("income", ""));
                            profile.screeningData.put("barangay", screening.optString("barangay", ""));
                        }
                    } catch (Exception e) {
                        Log.e("AIFoodEngine", "Error parsing clinical risk factors: " + e.getMessage());
                    }
                }
                
                Log.d(TAG, "Analyzed profile - Risk Score: " + profile.riskScore + 
                          ", BMI: " + profile.bmi + ", Gender: " + profile.gender);
            }
            cursor.close();
            
        } catch (Exception e) {
            Log.e(TAG, "Error analyzing user profile: " + e.getMessage());
        }
        
        return profile;
    }
    
    /**
     * Determine nutritional priorities using AI logic
     */
    private List<NutritionalPriority> determineNutritionalPriorities(UserNutritionalProfile profile) {
        List<NutritionalPriority> priorities = new ArrayList<>();
        
        // Critical malnutrition assessment - WHO-verified thresholds
        if (profile.riskScore >= 80) {
            priorities.add(NutritionalPriority.SEVERE_ACUTE_MALNUTRITION);
            priorities.add(NutritionalPriority.IMMUNE_SYSTEM_SUPPORT);
        } else if (profile.riskScore >= 50) {
            priorities.add(NutritionalPriority.MODERATE_ACUTE_MALNUTRITION);
            priorities.add(NutritionalPriority.PROTEIN_ENERGY_DEFICIENCY);
        } else if (profile.riskScore >= 20) {
            priorities.add(NutritionalPriority.GROWTH_SUPPORT);
            priorities.add(NutritionalPriority.MICRONUTRIENT_DEFICIENCY);
        }
        
        // Age-based priorities
        int ageMonths = calculateAgeInMonths(profile.birthday);
        if (ageMonths >= 6 && ageMonths <= 59) { // Children 6-59 months
            priorities.add(NutritionalPriority.GROWTH_SUPPORT);
            priorities.add(NutritionalPriority.MICRONUTRIENT_DEFICIENCY);
        }
        
        // BMI-based recommendations
        if (profile.bmi != null) {
            if (profile.bmi < 18.5) {
                priorities.add(NutritionalPriority.PROTEIN_ENERGY_DEFICIENCY);
            } else if (profile.bmi > 25) {
                priorities.add(NutritionalPriority.CHRONIC_DISEASE_PREVENTION);
            }
        }
        
        // Screening-based priorities
        if (profile.screeningData != null) {
            analyzeScreeningSymptoms(profile.screeningData, priorities);
        }
        
        // Default priority for normal cases
        if (priorities.isEmpty()) {
            priorities.add(NutritionalPriority.WEIGHT_MAINTENANCE);
        }
        
        Log.d(TAG, "Determined " + priorities.size() + " nutritional priorities");
        return priorities;
    }
    
    /**
     * Generate targeted food recommendations based on priorities
     */
    private List<DishData.Dish> generateTargetedRecommendations(List<NutritionalPriority> priorities, UserNutritionalProfile profile) {
        List<DishData.Dish> recommendations = new ArrayList<>();
        Set<String> addedDishes = new HashSet<>(); // Prevent duplicates
        
        // First pass: Add priority-based foods
        for (NutritionalPriority priority : priorities) {
            List<DishData.Dish> categoryFoods = getFoodsForPriority(priority);
            Log.d(TAG, "Priority " + priority.name() + ": Found " + categoryFoods.size() + " matching dishes");
            
            // Add top foods from each category
            for (DishData.Dish dish : categoryFoods) {
                if (addedDishes.size() >= 15) break; // Limit priority recommendations
                
                if (!addedDishes.contains(dish.name)) {
                    // Enhance dish with AI reasoning
                    DishData.Dish enhancedDish = enhanceDishWithAIReasoning(dish, priority, profile);
                    recommendations.add(enhancedDish);
                    addedDishes.add(dish.name);
                    Log.d(TAG, "  ✓ Added: " + dish.name + " for " + priority.name());
                }
            }
        }
        
        // Second pass: Add popular Filipino dishes if we have less than 20 recommendations
        if (recommendations.size() < 20) {
            List<DishData.Dish> popularDishes = getPopularFilipinoDishes();
            for (DishData.Dish dish : popularDishes) {
                if (addedDishes.size() >= 25) break; // Increase total limit
                
                if (!addedDishes.contains(dish.name)) {
                    recommendations.add(dish);
                    addedDishes.add(dish.name);
                }
            }
        }
        
        // Third pass: Add age-appropriate dishes if still low
        if (recommendations.size() < 20) {
            List<DishData.Dish> ageAppropriateDishes = getAgeAppropriateDishes(profile);
            for (DishData.Dish dish : ageAppropriateDishes) {
                if (addedDishes.size() >= 30) break; // Final limit
                
                if (!addedDishes.contains(dish.name)) {
                    recommendations.add(dish);
                    addedDishes.add(dish.name);
                }
            }
        }
        
        return recommendations;
    }
    
    /**
     * Get popular Filipino dishes for fallback recommendations
     */
    private List<DishData.Dish> getPopularFilipinoDishes() {
        List<DishData.Dish> popularDishes = new ArrayList<>();
        
        // Add well-known Filipino dishes that are generally well-liked
        for (DishData.Dish dish : DishData.DISHES) {
            if (dish.name.equals("Adobo") || dish.name.equals("Sinigang na Baboy") || 
                dish.name.equals("Tinola") || dish.name.equals("Kare-kare") || 
                dish.name.equals("Lechon Manok") || dish.name.equals("Chicken Inasal") ||
                dish.name.equals("Bulalo") || dish.name.equals("Nilagang Baka") ||
                dish.name.equals("Pancit Canton") || dish.name.equals("Arroz Caldo") ||
                dish.name.equals("Champorado") || dish.name.equals("Halo-halo") ||
                dish.name.equals("Biko") || dish.name.equals("Bibingka")) {
                popularDishes.add(dish);
            }
        }
        
        return popularDishes;
    }
    
    /**
     * Get age-appropriate dishes based on user profile
     */
    private List<DishData.Dish> getAgeAppropriateDishes(UserNutritionalProfile profile) {
        List<DishData.Dish> ageAppropriateDishes = new ArrayList<>();
        int ageMonths = calculateAgeInMonths(profile.birthday);
        
        for (DishData.Dish dish : DishData.DISHES) {
            boolean isAppropriate = false;
            
            if (ageMonths < 12) { // Infants
                isAppropriate = dish.tags.contains("infant-friendly") || 
                               dish.tags.contains("newborn-friendly");
            } else if (ageMonths < 36) { // Toddlers
                isAppropriate = dish.tags.contains("toddler-friendly") || 
                               dish.tags.contains("child-friendly");
            } else if (ageMonths < 72) { // Preschool
                isAppropriate = dish.tags.contains("preschool") || 
                               dish.tags.contains("child-friendly");
            } else if (ageMonths < 144) { // School age
                isAppropriate = dish.tags.contains("school-age") || 
                               dish.tags.contains("child-friendly");
            } else if (ageMonths < 216) { // Adolescents
                isAppropriate = dish.tags.contains("adolescent") || 
                               dish.tags.contains("adult-friendly");
            } else { // Adults
                isAppropriate = dish.tags.contains("adult-friendly");
            }
            
            if (isAppropriate) {
                ageAppropriateDishes.add(dish);
            }
        }
        
        return ageAppropriateDishes;
    }
    
    /**
     * Get foods specific to nutritional priority using REAL Filipino dishes
     */
    private List<DishData.Dish> getFoodsForPriority(NutritionalPriority priority) {
        List<DishData.Dish> foods = new ArrayList<>();
        
        // Get ALL Filipino dishes and filter by priority
        for (DishData.Dish dish : DishData.DISHES) {
            if (matchesPriority(dish, priority)) {
                foods.add(dish);
            }
        }
        
        Log.d(TAG, "Found " + foods.size() + " dishes for priority: " + priority);
        return foods;
    }
    
    /**
     * Check if a dish matches a specific nutritional priority
     */
    private boolean matchesPriority(DishData.Dish dish, NutritionalPriority priority) {
        String dishText = (dish.name + " " + dish.desc + " " + String.join(" ", dish.tags)).toLowerCase();
        
        switch (priority) {
            case SEVERE_ACUTE_MALNUTRITION:
                // High-protein, high-energy, therapeutic foods
                return dishText.contains("high-protein") || dishText.contains("protein-powerhouse") || 
                       dishText.contains("weight-gain") || dishText.contains("high-energy") ||
                       dishText.contains("therapeutic") || dishText.contains("ed") ||
                       dish.tags.contains(DishData.HP) || dish.tags.contains(DishData.ED) ||
                       dish.tags.contains(DishData.WG) || dish.tags.contains(DishData.TH);
                       
            case MODERATE_ACUTE_MALNUTRITION:
                // Protein-rich, energy-dense foods
                return dishText.contains("protein") || dishText.contains("high-protein") || 
                       dishText.contains("energy") || dishText.contains("weight-gain") ||
                       dish.tags.contains(DishData.HP) || dish.tags.contains(DishData.ED) ||
                       dish.tags.contains(DishData.WG);
                       
            case PROTEIN_ENERGY_DEFICIENCY:
                // Protein and energy sources
                return dishText.contains("protein") || dishText.contains("energy") || 
                       dishText.contains("weight-gain") || dishText.contains("high-protein") ||
                       dish.tags.contains(DishData.HP) || dish.tags.contains(DishData.ED) ||
                       dish.tags.contains(DishData.WG);
                       
            case MICRONUTRIENT_DEFICIENCY:
                // Vitamin and mineral rich foods
                return dishText.contains("vitamin") || dishText.contains("iron") || 
                       dishText.contains("calcium") || dishText.contains("micronutrient") ||
                       dish.tags.contains(DishData.HVC) || dish.tags.contains(DishData.HVA) || dish.tags.contains(DishData.HI);
                       
            case GROWTH_SUPPORT:
                // Foods that support growth and development
                return dishText.contains("growth") || dishText.contains("bone") || 
                       dishText.contains("calcium") || dishText.contains("protein") ||
                       dish.tags.contains(DishData.HP) || dish.tags.contains(DishData.HI) || dish.tags.contains(DishData.HVC);
                       
            case IMMUNE_SYSTEM_SUPPORT:
                // Immune-boosting foods
                return dishText.contains("immune") || dishText.contains("vitamin-c") || 
                       dishText.contains("antioxidant") || dishText.contains("healing") ||
                       dish.tags.contains(DishData.HVC) || dish.tags.contains("immune-boosting");
                       
            case DIGESTIVE_HEALTH:
                // Easy-to-digest, gut-friendly foods
                return dishText.contains("digestive") || dishText.contains("gut") || 
                       dishText.contains("soft") || dishText.contains("easy") ||
                       dish.tags.contains(DishData.DIG) || dish.tags.contains(DishData.SOF) || dish.tags.contains(DishData.EZ);
                       
            case WEIGHT_GAIN_SUPPORT:
                // High-calorie, weight-gain foods
                return dishText.contains("weight-gain") || dishText.contains("high-calorie") || 
                       dishText.contains("energy-dense") || dishText.contains("high-energy") ||
                       dish.tags.contains(DishData.ED) || dish.tags.contains(DishData.WG);
                       
            case BONE_DEVELOPMENT:
                // Calcium and bone-health foods
                return dishText.contains("bone") || dishText.contains("calcium") || 
                       dishText.contains("height") || dishText.contains("growth") ||
                       dish.tags.contains("bone-health") || dish.tags.contains(DishData.HI);
                       
            case ENERGY_BOOST:
                // Energy-boosting foods
                return dishText.contains("energy") || dishText.contains("boost") || 
                       dishText.contains("high-energy") || dishText.contains("energy-dense") ||
                       dish.tags.contains(DishData.ED) || dish.tags.contains("energy-boosting");
                       
            case APPETITE_STIMULATION:
                // Appetite-stimulating foods
                return dishText.contains("appetite") || dishText.contains("stimulating") || 
                       dishText.contains("aromatic") || dishText.contains("flavorful") ||
                       dish.tags.contains("appetite-stimulating") || dish.tags.contains("comfort-food");
                       
            case COMFORT_AND_APPEAL:
                // Comfort and appealing foods
                return dishText.contains("comfort") || dishText.contains("appealing") || 
                       dishText.contains("traditional") || dishText.contains("familiar") ||
                       dish.tags.contains("comfort-food") || dish.tags.contains("traditional");
                       
            case WEIGHT_MAINTENANCE:
                // Balanced nutrition foods
                return dishText.contains("balanced") || dishText.contains("nutritious") || 
                       dishText.contains("complete") || dishText.contains("healthy") ||
                       dish.tags.contains(DishData.BAL) || dish.tags.contains("balanced");
                       
            case CHRONIC_DISEASE_PREVENTION:
                // Health-promoting foods
                return dishText.contains("healthy") || dishText.contains("nutritious") || 
                       dishText.contains("balanced") || dishText.contains("prevention") ||
                       dish.tags.contains(DishData.BAL) || dish.tags.contains("healthy");
                       
            case VITAMIN_A_FOODS:
                // Vitamin A rich foods
                return dishText.contains("vitamin-a") || dishText.contains("beta-carotene") || 
                       dishText.contains("carrot") || dishText.contains("sweet potato") ||
                       dish.tags.contains(DishData.HVA);
                       
            case IRON_RICH_FOODS:
                // Iron-rich foods
                return dishText.contains("iron") || dishText.contains("iron-rich") || 
                       dishText.contains("liver") || dishText.contains("spinach") ||
                       dish.tags.contains(DishData.HI);
                       
            case BRAIN_DEVELOPMENT:
                // Brain development foods
                return dishText.contains("omega-3") || dishText.contains("brain") || 
                       dishText.contains("cognitive") || dishText.contains("development") ||
                       dish.tags.contains(DishData.BD);
                       
            case LOW_COST_FOODS:
                // Low-cost, accessible foods
                return dishText.contains("low-cost") || dishText.contains("affordable") || 
                       dishText.contains("traditional") || dishText.contains("street-food") ||
                       dish.tags.contains(DishData.LOW) || dish.tags.contains(DishData.TR);
                       
            case REGIONAL_FOODS:
                // Regional Filipino foods
                return dishText.contains("filipino") || dishText.contains("traditional") || 
                       dishText.contains("local") || dishText.contains("regional") ||
                       dish.tags.contains(DishData.TR);
                       
            case THERAPEUTIC_FOODS:
                // Therapeutic and healing foods
                return dishText.contains("therapeutic") || dishText.contains("healing") || 
                       dishText.contains("recovery") || dishText.contains("medicinal") ||
                       dish.tags.contains(DishData.TH);
                       
            default:
                // Default to including all foods
                return true;
        }
    }
    
    // Hardcoded therapeutic foods removed - now using REAL Filipino dishes
    
    // High-energy foods for moderate malnutrition
    private List<DishData.Dish> getHighEnergyFoods() {
        return Arrays.asList(
            new DishData.Dish("Avocado Toast", "🥑", 
                "Whole grain bread with mashed avocado and egg", 
                Arrays.asList("Avocado", "Whole grain bread", "Egg"), 
                Arrays.asList("Healthy fats", "Protein", "Fiber")),
            new DishData.Dish("Banana Oat Smoothie", "🍌", 
                "Creamy smoothie with banana, oats, and peanut butter", 
                Arrays.asList("Banana", "Oats", "Peanut butter", "Milk"), 
                Arrays.asList("Complex carbs", "Protein", "Potassium")),
            new DishData.Dish("Sweet Potato Mash", "🍠", 
                "Mashed sweet potato with butter and honey", 
                Arrays.asList("Sweet potato", "Butter", "Honey"), 
                Arrays.asList("Beta-carotene", "Calories", "Vitamin A"))
        );
    }
    
    // Iron-rich foods for anemia prevention
    private List<DishData.Dish> getIronRichFoods() {
        return Arrays.asList(
            new DishData.Dish("Spinach and Lentil Curry", "🍛", 
                "Iron-rich curry with spinach, lentils, and tomatoes", 
                Arrays.asList("Spinach", "Lentils", "Tomatoes"), 
                Arrays.asList("Iron", "Folate", "Vitamin C")),
            new DishData.Dish("Liver and Vegetables", "🥩", 
                "Tender liver cooked with vegetables and herbs", 
                Arrays.asList("Liver", "Onions", "Bell peppers"), 
                Arrays.asList("Heme iron", "B vitamins", "Protein")),
            new DishData.Dish("Fortified Cereal Bowl", "🥣", 
                "Iron-fortified cereal with milk and fruits", 
                Arrays.asList("Fortified cereal", "Milk", "Fruits"), 
                Arrays.asList("Iron", "Calcium", "Vitamins"))
        );
    }
    
    // Protein-rich foods
    private List<DishData.Dish> getProteinRichFoods() {
        return Arrays.asList(
            new DishData.Dish("Grilled Fish Fillet", "🐟", 
                "Fresh grilled fish with herbs and lemon", 
                Arrays.asList("Fish", "Herbs", "Lemon"), 
                Arrays.asList("Complete protein", "Omega-3", "Low fat")),
            new DishData.Dish("Chicken and Rice", "🍗", 
                "Tender chicken with steamed rice and vegetables", 
                Arrays.asList("Chicken", "Rice", "Vegetables"), 
                Arrays.asList("High protein", "Energy", "B vitamins")),
            new DishData.Dish("Bean and Egg Salad", "🥗", 
                "Mixed beans with hard-boiled egg and greens", 
                Arrays.asList("Mixed beans", "Egg", "Leafy greens"), 
                Arrays.asList("Plant protein", "Fiber", "Folate"))
        );
    }
    
    // Additional food categories...
    private List<DishData.Dish> getEnergyDenseFoods() {
        return Arrays.asList(
            new DishData.Dish("Nut Butter Sandwich", "🥪", 
                "Whole grain bread with almond butter and banana", 
                Arrays.asList("Whole grain bread", "Almond butter", "Banana"), 
                Arrays.asList("Healthy fats", "Protein", "Potassium"))
        );
    }
    
    private List<DishData.Dish> getVitaminAFoods() {
        return Arrays.asList(
            new DishData.Dish("Carrot and Orange Soup", "🥕", 
                "Creamy soup with carrots, oranges, and ginger", 
                Arrays.asList("Carrots", "Oranges", "Ginger"), 
                Arrays.asList("Beta-carotene", "Vitamin C", "Antioxidants"))
        );
    }
    
    private List<DishData.Dish> getGrowthSupportFoods() {
        return Arrays.asList(
            new DishData.Dish("Growth Support Porridge", "🍼", 
                "Nutrient-dense porridge for growing children", 
                Arrays.asList("Mixed grains", "Milk", "Fruits"), 
                Arrays.asList("Complete nutrition", "Calcium", "Vitamins"))
        );
    }
    
    private List<DishData.Dish> getImmuneBoostingFoods() {
        return Arrays.asList(
            new DishData.Dish("Immune Boost Soup", "🍲", 
                "Vegetable soup with garlic, ginger, and herbs", 
                Arrays.asList("Mixed vegetables", "Garlic", "Ginger"), 
                Arrays.asList("Vitamin C", "Antioxidants", "Anti-inflammatory"))
        );
    }
    
    private List<DishData.Dish> getDigestiveFriendlyFoods() {
        return Arrays.asList(
            new DishData.Dish("Probiotic Yogurt Bowl", "🥛", 
                "Plain yogurt with honey and soft fruits", 
                Arrays.asList("Yogurt", "Honey", "Soft fruits"), 
                Arrays.asList("Probiotics", "Easy digestion", "Natural sugars"))
        );
    }
    
    private List<DishData.Dish> getBalancedNutritionFoods() {
        return Arrays.asList(
            new DishData.Dish("Balanced Meal Plate", "🍽️", 
                "Well-balanced meal with protein, carbs, and vegetables", 
                Arrays.asList("Lean protein", "Whole grains", "Vegetables"), 
                Arrays.asList("Complete nutrition", "Balanced macros", "Vitamins"))
        );
    }
    
    /**
     * Get foods for weight gain support
     */
    private List<DishData.Dish> getWeightGainFoods() {
        return Arrays.asList(
            new DishData.Dish("Creamy Avocado Smoothie", "🥑", 
                "High-calorie smoothie with healthy fats and protein", 
                Arrays.asList("Avocado", "Banana", "Peanut butter", "Milk"), 
                Arrays.asList("High-calorie", "Healthy-fats", "Protein", "Weight-gain")),
            new DishData.Dish("Nut Butter Toast", "🍞", 
                "Whole grain toast with almond/peanut butter and banana", 
                Arrays.asList("Whole grain bread", "Nut butter", "Banana"), 
                Arrays.asList("High-calorie", "Protein", "Healthy-fats", "Weight-gain")),
            new DishData.Dish("Cheese and Egg Sandwich", "🥪", 
                "Protein-rich sandwich with cheese and eggs", 
                Arrays.asList("Bread", "Cheese", "Eggs"), 
                Arrays.asList("High-protein", "High-calorie", "Weight-gain")),
            new DishData.Dish("Coconut Rice", "🍚", 
                "Rice cooked in coconut milk for extra calories", 
                Arrays.asList("Rice", "Coconut milk", "Coconut oil"), 
                Arrays.asList("High-calorie", "Energy-dense", "Weight-gain"))
        );
    }
    
    /**
     * Get foods for appetite stimulation
     */
    private List<DishData.Dish> getAppetiteStimulatingFoods() {
        return Arrays.asList(
            new DishData.Dish("Aromatic Chicken Soup", "🍲", 
                "Fragrant soup with herbs and spices to stimulate appetite", 
                Arrays.asList("Chicken", "Herbs", "Spices", "Vegetables"), 
                Arrays.asList("Appetite-stimulating", "Aromatic", "Digestive-friendly")),
            new DishData.Dish("Ginger Tea with Honey", "🫖", 
                "Warming drink to improve appetite and digestion", 
                Arrays.asList("Ginger", "Honey", "Lemon"), 
                Arrays.asList("Appetite-stimulating", "Digestive-friendly", "Warming")),
            new DishData.Dish("Citrus Fruit Salad", "🍊", 
                "Bright, tangy fruits to awaken taste buds", 
                Arrays.asList("Oranges", "Lemons", "Grapefruits"), 
                Arrays.asList("Appetite-stimulating", "Vitamin-C", "Refreshing")),
            new DishData.Dish("Herb-Infused Water", "💧", 
                "Water with mint, lemon, or cucumber for appetite", 
                Arrays.asList("Water", "Mint", "Lemon", "Cucumber"), 
                Arrays.asList("Appetite-stimulating", "Hydrating", "Refreshing"))
        );
    }
    
    /**
     * Get foods for bone development and height support
     */
    private List<DishData.Dish> getBoneStrengtheningFoods() {
        return Arrays.asList(
            new DishData.Dish("Calcium-Rich Yogurt Bowl", "🥣", 
                "Yogurt with nuts and fruits for bone health", 
                Arrays.asList("Yogurt", "Nuts", "Fruits"), 
                Arrays.asList("Calcium-rich", "Bone-health", "Protein")),
            new DishData.Dish("Salmon with Vegetables", "🐟", 
                "Omega-3 rich fish for bone and brain development", 
                Arrays.asList("Salmon", "Vegetables", "Herbs"), 
                Arrays.asList("Omega-3", "Protein", "Bone-health")),
            new DishData.Dish("Fortified Cereal with Milk", "🥣", 
                "Calcium and vitamin D fortified breakfast", 
                Arrays.asList("Fortified cereal", "Milk", "Fruits"), 
                Arrays.asList("Calcium-rich", "Vitamin-D", "Bone-health")),
            new DishData.Dish("Dark Leafy Greens", "🥬", 
                "Calcium and vitamin K rich vegetables", 
                Arrays.asList("Spinach", "Kale", "Collard greens"), 
                Arrays.asList("Calcium-rich", "Vitamin-K", "Bone-health"))
        );
    }
    
    /**
     * Get foods for energy boosting
     */
    private List<DishData.Dish> getEnergyBoostingFoods() {
        return Arrays.asList(
            new DishData.Dish("Oatmeal with Nuts and Berries", "🥣", 
                "Complex carbs with protein for sustained energy", 
                Arrays.asList("Oats", "Nuts", "Berries", "Honey"), 
                Arrays.asList("Energy-boosting", "Complex-carbs", "Protein")),
            new DishData.Dish("Quinoa Bowl", "🥗", 
                "Complete protein with vegetables for energy", 
                Arrays.asList("Quinoa", "Vegetables", "Olive oil"), 
                Arrays.asList("Energy-boosting", "Complete-protein", "Nutritious")),
            new DishData.Dish("Sweet Potato with Beans", "🍠", 
                "Complex carbs with plant protein", 
                Arrays.asList("Sweet potato", "Beans", "Herbs"), 
                Arrays.asList("Energy-boosting", "Complex-carbs", "Plant-protein")),
            new DishData.Dish("Banana and Nut Smoothie", "🍌", 
                "Natural sugars with healthy fats for quick energy", 
                Arrays.asList("Banana", "Nuts", "Milk", "Honey"), 
                Arrays.asList("Energy-boosting", "Quick-energy", "Healthy-fats"))
        );
    }
    
    /**
     * Get foods for comfort and appeal
     */
    private List<DishData.Dish> getComfortAndAppealFoods() {
        return Arrays.asList(
            new DishData.Dish("Warm Chicken Noodle Soup", "🍜", 
                "Comforting soup that's easy to eat", 
                Arrays.asList("Chicken", "Noodles", "Vegetables"), 
                Arrays.asList("Comfort-food", "Easy-to-eat", "Digestive-friendly")),
            new DishData.Dish("Soft Scrambled Eggs", "🍳", 
                "Gentle protein source for sensitive stomachs", 
                Arrays.asList("Eggs", "Butter", "Milk"), 
                Arrays.asList("Comfort-food", "Soft-texture", "Protein")),
            new DishData.Dish("Mashed Potatoes", "🥔", 
                "Smooth, comforting carbohydrate source", 
                Arrays.asList("Potatoes", "Butter", "Milk"), 
                Arrays.asList("Comfort-food", "Smooth-texture", "Carbohydrates")),
            new DishData.Dish("Rice Pudding", "🍚", 
                "Gentle, sweet comfort food", 
                Arrays.asList("Rice", "Milk", "Sugar", "Cinnamon"), 
                Arrays.asList("Comfort-food", "Sweet", "Gentle"))
        );
    }
    
    /**
     * Get foods for gut health
     */
    private List<DishData.Dish> getGutHealthFoods() {
        return Arrays.asList(
            new DishData.Dish("Probiotic Yogurt", "🥛", 
                "Live cultures for digestive health", 
                Arrays.asList("Yogurt", "Honey", "Berries"), 
                Arrays.asList("Probiotic", "Gut-health", "Digestive-friendly")),
            new DishData.Dish("Fermented Vegetables", "🥬", 
                "Natural probiotics for gut microbiome", 
                Arrays.asList("Cabbage", "Carrots", "Salt"), 
                Arrays.asList("Probiotic", "Gut-health", "Fermented")),
            new DishData.Dish("Fiber-Rich Oatmeal", "🥣", 
                "Soluble fiber for digestive health", 
                Arrays.asList("Oats", "Fruits", "Nuts"), 
                Arrays.asList("Fiber-rich", "Gut-health", "Digestive-friendly")),
            new DishData.Dish("Ginger and Turmeric Tea", "🫖", 
                "Anti-inflammatory spices for gut health", 
                Arrays.asList("Ginger", "Turmeric", "Honey"), 
                Arrays.asList("Anti-inflammatory", "Gut-health", "Digestive-friendly"))
        );
    }
    
    /**
     * Get calcium-rich foods for bone development
     */
    private List<DishData.Dish> getCalciumSources() {
        return Arrays.asList(
            new DishData.Dish("Milk and Honey", "🥛", 
                "Simple calcium-rich drink with natural sweetness", 
                Arrays.asList("Milk", "Honey"), 
                Arrays.asList("Calcium-rich", "Protein", "Natural-sweetness")),
            new DishData.Dish("Cheese and Crackers", "🧀", 
                "Calcium-rich snack with whole grain crackers", 
                Arrays.asList("Cheese", "Whole grain crackers"), 
                Arrays.asList("Calcium-rich", "Protein", "Whole-grains")),
            new DishData.Dish("Yogurt Parfait", "🥣", 
                "Layered yogurt with fruits and granola", 
                Arrays.asList("Yogurt", "Fruits", "Granola"), 
                Arrays.asList("Calcium-rich", "Probiotics", "Fiber")),
            new DishData.Dish("Sardines on Toast", "🐟", 
                "Calcium-rich fish with bones on whole grain toast", 
                Arrays.asList("Sardines", "Whole grain bread"), 
                Arrays.asList("Calcium-rich", "Omega-3", "Protein"))
        );
    }
    
    /**
     * Enhance dish with AI reasoning based on user profile
     */
    private DishData.Dish enhanceDishWithAIReasoning(DishData.Dish dish, NutritionalPriority priority, UserNutritionalProfile profile) {
        String aiReasoning = generateAIReasoning(dish, priority, profile);
        
        // Create enhanced description with AI insights
        String enhancedDesc = dish.desc + "\n\n🤖 AI Insight: " + aiReasoning;
        
        return new DishData.Dish(dish.name, dish.emoji, enhancedDesc, dish.tags, dish.allergens);
    }
    
    /**
     * Generate AI reasoning for food recommendation
     */
    private String generateAIReasoning(DishData.Dish dish, NutritionalPriority priority, UserNutritionalProfile profile) {
        StringBuilder reasoning = new StringBuilder();
        
        reasoning.append("Recommended for ").append(profile.name != null ? profile.name : "you");
        reasoning.append(" based on your risk score of ").append(profile.riskScore);
        
        switch (priority) {
            case SEVERE_ACUTE_MALNUTRITION:
                reasoning.append(". This therapeutic food provides essential calories and nutrients for malnutrition recovery.");
                break;
            case MODERATE_ACUTE_MALNUTRITION:
                reasoning.append(". This high-energy food supports weight gain and nutritional rehabilitation.");
                break;
            case PROTEIN_ENERGY_DEFICIENCY:
                reasoning.append(". Rich in protein and calories to address nutritional deficits.");
                break;
            case MICRONUTRIENT_DEFICIENCY:
                reasoning.append(". Provides essential vitamins and minerals for optimal health.");
                break;
            case GROWTH_SUPPORT:
                reasoning.append(". Supports healthy growth and development in children.");
                break;
            case IMMUNE_SYSTEM_SUPPORT:
                reasoning.append(". Boosts immune function and helps fight infections.");
                break;
            case DIGESTIVE_HEALTH:
                reasoning.append(". Gentle on digestion and supports gut health.");
                break;
            case WEIGHT_GAIN_SUPPORT:
                reasoning.append(". High-calorie and nutrient-dense to support weight restoration.");
                break;
            case BONE_DEVELOPMENT:
                reasoning.append(". Rich in calcium and nutrients for bone growth and height support.");
                break;
            case ENERGY_BOOST:
                reasoning.append(". Provides sustained energy to combat fatigue and weakness.");
                break;
            case APPETITE_STIMULATION:
                reasoning.append(". Designed to increase appetite and make eating more appealing.");
                break;
            case COMFORT_AND_APPEAL:
                reasoning.append(". Familiar and comforting to encourage regular eating.");
                break;
            case WEIGHT_MAINTENANCE:
                reasoning.append(". Balanced nutrition to maintain current healthy weight.");
                break;
            case CHRONIC_DISEASE_PREVENTION:
                reasoning.append(". Supports long-term health and prevents chronic conditions.");
                break;
            default:
                reasoning.append(". Supports your overall nutritional needs and health goals.");
                break;
        }
        
        return reasoning.toString();
    }
    
    /**
     * Apply comprehensive dietary filters based on ALL screening answers and user preferences
     * This makes the system smarter by considering individual symptoms and needs
     */
    private List<DishData.Dish> applyDietaryFilters(List<DishData.Dish> recommendations, UserNutritionalProfile profile) {
        List<DishData.Dish> filtered = new ArrayList<>();
        
        Log.d(TAG, "Applying comprehensive dietary filters for user: " + profile.name);
        Log.d(TAG, "Screening data available: " + (profile.screeningData != null ? profile.screeningData.size() : 0) + " fields");
        
        for (DishData.Dish dish : recommendations) {
            boolean include = true;
            String exclusionReason = "";
            
            // 1. ALLERGY FILTERING - Critical for safety
            if (profile.allergies != null) {
                for (String allergy : profile.allergies) {
                    if (containsAllergen(dish, allergy)) {
                        include = false;
                        exclusionReason = "Allergy: " + allergy;
                        break;
                    }
                }
            }
            
            // 2. DIETARY PREFERENCE FILTERING - User choice
            if (include && profile.dietaryPreferences != null) {
                include = matchesDietaryPreference(dish, profile.dietaryPreferences);
                if (!include) exclusionReason = "Dietary preference mismatch";
            }
            
            // 3. AVOID FOODS FILTERING - User dislikes
            if (include && profile.avoidFoods != null && !profile.avoidFoods.isEmpty()) {
                String dishName = dish.name.toLowerCase();
                String avoidFoods = profile.avoidFoods.toLowerCase();
                if (avoidFoods.contains(dishName) || dishName.contains(avoidFoods)) {
                    include = false;
                    exclusionReason = "User avoids: " + dish.name;
                }
            }
            
            // 4. NEW: SCREENING-BASED INTELLIGENT FILTERING
            if (include && profile.screeningData != null) {
                include = applyScreeningBasedFilters(dish, profile.screeningData);
                if (!include) exclusionReason = "Screening-based exclusion";
            }
            
            // 5. NEW: AGE-APPROPRIATE FILTERING
            if (include && profile.birthday != null) {
                int ageMonths = calculateAgeInMonths(profile.birthday);
                include = isAgeAppropriate(dish, ageMonths);
                if (!include) exclusionReason = "Age inappropriate for " + ageMonths + " months";
            }
            
            // 6. NEW: FEEDING DIFFICULTY FILTERING
            if (include && profile.screeningData != null) {
                String feedingDifficulty = profile.screeningData.get("has_eating_difficulty");
                if ("yes".equals(feedingDifficulty)) {
                    include = isEasyToEat(dish);
                    if (!include) exclusionReason = "Too difficult to eat for user with feeding issues";
                }
            }
            
            // 7. NEW: INCOME-BASED ACCESSIBILITY FILTERING
            if (include && profile.screeningData != null) {
                String income = profile.screeningData.get("income");
                if (income != null && income.contains("low")) {
                    include = isLowCost(dish);
                    if (!include) exclusionReason = "Too expensive for low-income household";
                }
            }
            
            if (include) {
                filtered.add(dish);
                Log.d(TAG, "✓ Included: " + dish.name);
            } else {
                Log.d(TAG, "✗ Excluded: " + dish.name + " - " + exclusionReason);
            }
        }
        
        Log.d(TAG, "Filtering complete: " + recommendations.size() + " → " + filtered.size() + " dishes");
        return filtered;
    }
    
    /**
     * NEW: Apply intelligent filters based on screening answers
     */
    private boolean applyScreeningBasedFilters(DishData.Dish dish, Map<String, String> screeningData) {
        // Check if dish is appropriate based on specific screening answers
        
        // 1. Check for feeding difficulties - prefer soft, easy-to-eat foods
        String feedingDifficulty = screeningData.get("has_eating_difficulty");
        if ("yes".equals(feedingDifficulty)) {
            // Prefer soft, pureed, or easy-to-digest foods
            if (dish.tags.contains("SOF") || dish.tags.contains("PURE") || dish.tags.contains("DIG")) {
                return true; // Prioritize these foods
            }
        }
        
        // 2. Check for appetite issues - prefer appetite-stimulating foods
        String feedingBehavior = screeningData.get("feeding_behavior");
        if ("poor appetite".equals(feedingBehavior)) {
            // Prefer foods that stimulate appetite
            if (dish.tags.contains("appetite-stimulating") || dish.tags.contains("comfort-food")) {
                return true; // Prioritize these foods
            }
        }
        
        // 3. Check for digestive issues - prefer gut-friendly foods
        String digestiveIssues = screeningData.get("has_eating_difficulty");
        if ("yes".equals(digestiveIssues)) {
            if (dish.tags.contains("DIG") || dish.tags.contains("gut-health")) {
                return true; // Prioritize these foods
            }
        }
        
        // 4. Check for immune system support needs
        String recentIllness = screeningData.get("has_recent_illness");
        if ("yes".equals(recentIllness)) {
            if (dish.tags.contains("immune-boosting") || dish.tags.contains("vitamin-c")) {
                return true; // Prioritize these foods
            }
        }
        
        return true; // Default to including the dish
    }
    
    /**
     * NEW: Check if dish is age-appropriate
     */
    private boolean isAgeAppropriate(DishData.Dish dish, int ageMonths) {
        // Check age-specific tags
        if (ageMonths < 6) {
            // Infants: only pureed, soft foods
            return dish.tags.contains("INF") || dish.tags.contains("PURE");
        } else if (ageMonths < 12) {
            // 6-12 months: soft, easy-to-digest foods
            return dish.tags.contains("TOD") || dish.tags.contains("SOF") || dish.tags.contains("DIG");
        } else if (ageMonths < 24) {
            // 12-24 months: toddler-appropriate foods
            return dish.tags.contains("TOD") || dish.tags.contains("CHI");
        } else if (ageMonths < 60) {
            // 2-5 years: preschool-appropriate foods
            return dish.tags.contains("PRE") || dish.tags.contains("CHI");
        } else if (ageMonths < 120) {
            // 5-10 years: school-age appropriate foods
            return dish.tags.contains("SCH") || dish.tags.contains("CHI");
        } else if (ageMonths < 216) {
            // 10-18 years: adolescent appropriate foods
            return dish.tags.contains("ADO") || dish.tags.contains("ADU");
        } else {
            // Adults: all foods
            return dish.tags.contains("ADU");
        }
    }
    
    /**
     * NEW: Check if dish is easy to eat for users with feeding difficulties
     */
    private boolean isEasyToEat(DishData.Dish dish) {
        // Prefer soft, pureed, or easy-to-digest foods
        return dish.tags.contains("SOF") || dish.tags.contains("PURE") || dish.tags.contains("DIG") || 
               dish.tags.contains("EZ") || dish.tags.contains("comfort-food");
    }
    
    /**
     * NEW: Check if dish is low-cost for low-income households
     */
    private boolean isLowCost(DishData.Dish dish) {
        // Prefer low-cost foods
        return dish.tags.contains("LOW") || dish.tags.contains("FREE") || 
               dish.tags.contains("traditional") || dish.tags.contains("street-food");
    }
    
    /**
     * Rank recommendations by nutritional impact
     */
    private List<DishData.Dish> rankRecommendationsByImpact(List<DishData.Dish> recommendations, List<NutritionalPriority> priorities) {
        // Sort by priority order and nutritional impact
        Collections.sort(recommendations, (dish1, dish2) -> {
            // Higher impact dishes first
            return Integer.compare(calculateNutritionalImpact(dish2), calculateNutritionalImpact(dish1));
        });
        
        return recommendations;
    }
    
    // Helper methods
    private int calculateAgeInMonths(String birthday) {
        if (birthday == null || birthday.isEmpty()) return 0;
        try {
            SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd", Locale.getDefault());
            Date birthDate = sdf.parse(birthday);
            Date now = new Date();
            long diffInMillis = now.getTime() - birthDate.getTime();
            return (int) (diffInMillis / (1000L * 60 * 60 * 24 * 30)); // Approximate months
        } catch (Exception e) {
            return 0;
        }
    }
    
    /**
     * Enhanced screening symptom analysis using ALL screening answers for comprehensive malnutrition assessment
     */
    private void analyzeScreeningSymptoms(Map<String, String> screeningData, List<NutritionalPriority> priorities) {
        Log.d(TAG, "Analyzing ALL screening answers for comprehensive malnutrition assessment: " + screeningData);
        
        // 1. PHYSICAL SIGNS ASSESSMENT - Critical for malnutrition detection
        String physicalSigns = screeningData.get("physical_signs");
        if (physicalSigns != null && !physicalSigns.isEmpty()) {
            try {
                JSONArray signsArray = new JSONArray(physicalSigns);
                for (int i = 0; i < signsArray.length(); i++) {
                    String sign = signsArray.getString(i);
                    switch (sign) {
                        case "thin":
                            priorities.add(NutritionalPriority.WEIGHT_GAIN_SUPPORT);
                            priorities.add(NutritionalPriority.PROTEIN_ENERGY_DEFICIENCY);
                            Log.d(TAG, "Added WEIGHT_GAIN_SUPPORT for thin appearance");
                            break;
                        case "shorter":
                            priorities.add(NutritionalPriority.GROWTH_SUPPORT);
                            priorities.add(NutritionalPriority.BONE_DEVELOPMENT);
                            Log.d(TAG, "Added GROWTH_SUPPORT for height concerns");
                            break;
                        case "weak":
                            priorities.add(NutritionalPriority.ENERGY_BOOST);
                            priorities.add(NutritionalPriority.PROTEIN_ENERGY_DEFICIENCY);
                            Log.d(TAG, "Added ENERGY_BOOST for weakness");
                            break;
                        case "none":
                            // No physical signs - maintain current status
                            break;
                    }
                }
            } catch (Exception e) {
                Log.e(TAG, "Error parsing physical signs: " + e.getMessage());
            }
        }
        
        // 2. WEIGHT LOSS ASSESSMENT - Critical for nutritional intervention
        String weightLoss = screeningData.get("weight_loss");
        if (weightLoss != null) {
            switch (weightLoss) {
                case ">10%":
                    priorities.add(NutritionalPriority.SEVERE_ACUTE_MALNUTRITION);
                    priorities.add(NutritionalPriority.WEIGHT_GAIN_SUPPORT);
                    Log.d(TAG, "Added SEVERE_ACUTE_MALNUTRITION for >10% weight loss");
                    break;
                case "5-10%":
                    priorities.add(NutritionalPriority.MODERATE_ACUTE_MALNUTRITION);
                    priorities.add(NutritionalPriority.WEIGHT_GAIN_SUPPORT);
                    Log.d(TAG, "Added MODERATE_ACUTE_MALNUTRITION for 5-10% weight loss");
                    break;
                case "<5% or none":
                    // Minimal weight loss - maintain current status
                    break;
            }
        }
        
        // 3. FEEDING BEHAVIOR ASSESSMENT - Critical for food acceptance
        String feedingBehavior = screeningData.get("feeding_behavior");
        if (feedingBehavior != null) {
            switch (feedingBehavior) {
                case "poor appetite":
                    priorities.add(NutritionalPriority.APPETITE_STIMULATION);
                    priorities.add(NutritionalPriority.COMFORT_AND_APPEAL);
                    priorities.add(NutritionalPriority.DIGESTIVE_HEALTH);
                    Log.d(TAG, "Added APPETITE_STIMULATION for poor appetite");
                    break;
                case "moderate appetite":
                    priorities.add(NutritionalPriority.APPETITE_STIMULATION);
                    Log.d(TAG, "Added APPETITE_STIMULATION for moderate appetite");
                    break;
                case "good appetite":
                    // Good appetite - can focus on nutritional quality
                    break;
            }
        }
        
        // 4. SWELLING (EDEMA) ASSESSMENT - Critical for severe malnutrition
        String swelling = screeningData.get("swelling");
        if ("yes".equals(swelling)) {
            priorities.add(NutritionalPriority.SEVERE_ACUTE_MALNUTRITION);
            priorities.add(NutritionalPriority.IMMUNE_SYSTEM_SUPPORT);
            Log.d(TAG, "Added SEVERE_ACUTE_MALNUTRITION for edema");
        }
        
        // 5. CLINICAL RISK FACTORS - Comprehensive health assessment
        if ("true".equals(screeningData.get("has_recent_illness"))) {
            priorities.add(NutritionalPriority.IMMUNE_SYSTEM_SUPPORT);
            priorities.add(NutritionalPriority.ENERGY_BOOST);
            Log.d(TAG, "Added IMMUNE_SYSTEM_SUPPORT for recent illness");
        }
        
        if ("true".equals(screeningData.get("has_eating_difficulty"))) {
            priorities.add(NutritionalPriority.DIGESTIVE_HEALTH);
            priorities.add(NutritionalPriority.COMFORT_AND_APPEAL);
            Log.d(TAG, "Added DIGESTIVE_HEALTH for eating difficulty");
        }
        
        if ("true".equals(screeningData.get("has_food_insecurity"))) {
            priorities.add(NutritionalPriority.APPETITE_STIMULATION);
            priorities.add(NutritionalPriority.COMFORT_AND_APPEAL);
            priorities.add(NutritionalPriority.WEIGHT_GAIN_SUPPORT);
            Log.d(TAG, "Added APPETITE_STIMULATION for food insecurity");
        }
        
        if ("true".equals(screeningData.get("has_micronutrient_deficiency"))) {
            priorities.add(NutritionalPriority.MICRONUTRIENT_DEFICIENCY);
            priorities.add(NutritionalPriority.VITAMIN_A_FOODS);
            priorities.add(NutritionalPriority.IRON_RICH_FOODS);
            Log.d(TAG, "Added MICRONUTRIENT_DEFICIENCY for micronutrient deficiency signs");
        }
        
        if ("true".equals(screeningData.get("has_functional_decline"))) {
            priorities.add(NutritionalPriority.ENERGY_BOOST);
            priorities.add(NutritionalPriority.PROTEIN_ENERGY_DEFICIENCY);
            priorities.add(NutritionalPriority.CHRONIC_DISEASE_PREVENTION);
            Log.d(TAG, "Added ENERGY_BOOST for functional decline");
        }
        
        // 6. NEW: DIETARY DIVERSITY ASSESSMENT - Critical for micronutrient deficiencies
        String dietaryDiversity = screeningData.get("dietary_diversity");
        if (dietaryDiversity != null) {
            try {
                int diversityScore = Integer.parseInt(dietaryDiversity);
                if (diversityScore < 4) {
                    priorities.add(NutritionalPriority.MICRONUTRIENT_DEFICIENCY);
                    priorities.add(NutritionalPriority.VITAMIN_A_FOODS);
                    Log.d(TAG, "Added MICRONUTRIENT_DEFICIENCY for low dietary diversity: " + diversityScore);
                }
            } catch (NumberFormatException e) {
                Log.e(TAG, "Error parsing dietary diversity: " + e.getMessage());
            }
        }
        
        // 7. NEW: MUAC ASSESSMENT - Critical for acute malnutrition
        String muac = screeningData.get("muac");
        if (muac != null && !muac.isEmpty()) {
            try {
                float muacValue = Float.parseFloat(muac);
                if (muacValue < 11.5f) {
                    priorities.add(NutritionalPriority.SEVERE_ACUTE_MALNUTRITION);
                    priorities.add(NutritionalPriority.THERAPEUTIC_FOODS);
                    Log.d(TAG, "Added SEVERE_ACUTE_MALNUTRITION for MUAC < 11.5cm: " + muacValue);
                } else if (muacValue < 12.5f) {
                    priorities.add(NutritionalPriority.MODERATE_ACUTE_MALNUTRITION);
                    priorities.add(NutritionalPriority.PROTEIN_ENERGY_DEFICIENCY);
                    Log.d(TAG, "Added MODERATE_ACUTE_MALNUTRITION for MUAC < 12.5cm: " + muacValue);
                }
            } catch (NumberFormatException e) {
                Log.e(TAG, "Error parsing MUAC: " + e.getMessage());
            }
        }
        
        // 8. NEW: GENDER-SPECIFIC NUTRITIONAL NEEDS
        String gender = screeningData.get("gender");
        if ("female".equals(gender)) {
            priorities.add(NutritionalPriority.IRON_RICH_FOODS);
            Log.d(TAG, "Added IRON_RICH_FOODS for female gender");
        }
        
        // 9. NEW: AGE-SPECIFIC NUTRITIONAL NEEDS
        String age = screeningData.get("age");
        if (age != null && !age.isEmpty()) {
            try {
                int ageMonths = Integer.parseInt(age);
                if (ageMonths < 24) {
                    priorities.add(NutritionalPriority.GROWTH_SUPPORT);
                    priorities.add(NutritionalPriority.BRAIN_DEVELOPMENT);
                    Log.d(TAG, "Added GROWTH_SUPPORT for young child < 24 months: " + ageMonths);
                } else if (ageMonths < 60) {
                    priorities.add(NutritionalPriority.GROWTH_SUPPORT);
                    priorities.add(NutritionalPriority.BONE_DEVELOPMENT);
                    Log.d(TAG, "Added GROWTH_SUPPORT for preschool child: " + ageMonths);
                }
            } catch (NumberFormatException e) {
                Log.e(TAG, "Error parsing age: " + e.getMessage());
            }
        }
        
        // 10. NEW: INCOME-BASED ACCESSIBILITY CONSIDERATIONS
        String income = screeningData.get("income");
        if (income != null && income.contains("low")) {
            priorities.add(NutritionalPriority.LOW_COST_FOODS);
            Log.d(TAG, "Added LOW_COST_FOODS for low income household");
        }
        
        // 11. NEW: BARANGAY-BASED REGIONAL FOOD PREFERENCES
        String barangay = screeningData.get("barangay");
        if (barangay != null && !barangay.isEmpty()) {
            priorities.add(NutritionalPriority.REGIONAL_FOODS);
            Log.d(TAG, "Added REGIONAL_FOODS for barangay: " + barangay);
        }
        
        Log.d(TAG, "Comprehensive screening analysis completed with " + priorities.size() + " priorities");
    }
    
    private Map<String, String> parseScreeningData(String jsonString) {
        Map<String, String> data = new HashMap<>();
        try {
            JSONObject json = new JSONObject(jsonString);
            Iterator<String> keys = json.keys();
            while (keys.hasNext()) {
                String key = keys.next();
                Object value = json.get(key);
                // Handle different data types properly
                if (value instanceof String) {
                    data.put(key, (String) value);
                } else if (value instanceof Boolean) {
                    data.put(key, value.toString());
                } else if (value instanceof Integer) {
                    data.put(key, value.toString());
                } else if (value instanceof Double) {
                    data.put(key, value.toString());
                } else if (value instanceof JSONArray) {
                    // Convert JSON array to comma-separated string
                    JSONArray array = (JSONArray) value;
                    StringBuilder sb = new StringBuilder();
                    for (int i = 0; i < array.length(); i++) {
                        if (i > 0) sb.append(",");
                        sb.append(array.getString(i));
                    }
                    data.put(key, sb.toString());
                } else {
                    data.put(key, value.toString());
                }
            }
            Log.d(TAG, "Successfully parsed screening data with " + data.size() + " fields");
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing screening data: " + e.getMessage());
            // Fallback: try to parse as comma-separated values
            try {
                String[] pairs = jsonString.split(",");
                for (String pair : pairs) {
                    String[] keyValue = pair.split(":");
                    if (keyValue.length == 2) {
                        data.put(keyValue[0].trim(), keyValue[1].trim());
                    }
                }
                Log.d(TAG, "Fallback parsing completed with " + data.size() + " fields");
            } catch (Exception fallbackError) {
                Log.e(TAG, "Fallback parsing also failed: " + fallbackError.getMessage());
            }
        }
        return data;
    }
    
    private List<String> parseJsonArray(String jsonString) {
        List<String> list = new ArrayList<>();
        if (jsonString == null || jsonString.isEmpty()) return list;
        
        try {
            JSONArray jsonArray = new JSONArray(jsonString);
            for (int i = 0; i < jsonArray.length(); i++) {
                list.add(jsonArray.getString(i));
            }
        } catch (JSONException e) {
            // If not JSON, split by comma
            String[] parts = jsonString.split(",");
            for (String part : parts) {
                list.add(part.trim());
            }
        }
        return list;
    }
    
    private Float getFloat(Cursor cursor, String columnName) {
        int columnIndex = cursor.getColumnIndex(columnName);
        if (columnIndex != -1 && !cursor.isNull(columnIndex)) {
            return cursor.getFloat(columnIndex);
        }
        return null;
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
    
    private int calculateNutritionalImpact(DishData.Dish dish) {
        // Calculate nutritional impact score based on tags and allergens
        int score = dish.tags.size() * 10;
        score += dish.allergens.size() * 5;
        if (dish.desc.contains("high-protein")) score += 20;
        if (dish.desc.contains("high-energy")) score += 15;
        if (dish.desc.contains("therapeutic")) score += 30;
        return score;
    }
    
    public void close() {
        if (dbHelper != null) {
            dbHelper.close();
        }
    }
    
    /**
     * Inner class to hold user nutritional profile
     */
    private static class UserNutritionalProfile {
        String email;
        String name;
        String birthday;
        String gender;
        Float height;
        Float weight;
        Float bmi;
        int riskScore;
        List<String> allergies;
        List<String> dietaryPreferences;
        String avoidFoods;
        Map<String, String> screeningData;
        Boolean hasRecentIllness;
        Boolean hasEatingDifficulty;
        Boolean hasFoodInsecurity;
        Boolean hasMicronutrientDeficiency;
        Boolean hasFunctionalDecline;
    }
}
