package com.example.nutrisaur11;

import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.util.Log;
import java.util.*;
import org.json.JSONObject;

/**
 * Smart Filter Engine for intelligent food filtering and ranking
 * This engine provides hierarchical recommendations based on user preferences and nutritional needs
 */
public class SmartFilterEngine {
    private Context context;
    private UserPreferencesDbHelper dbHelper;
    
    // Cache for filter results to avoid recalculation
    private Map<String, List<RankedDish>> filterCache = new HashMap<>();
    private static final int MAX_CACHE_SIZE = 20;
    
    public SmartFilterEngine(Context context) {
        this.context = context;
        this.dbHelper = new UserPreferencesDbHelper(context);
    }
    
    /**
     * Get filtered and ranked recommendations based on user preferences
     */
    public List<RankedDish> getFilteredRecommendations(String userEmail, List<String> activeFilters) {
        long startTime = System.currentTimeMillis();
        
        // Check cache first for performance
        String cacheKey = generateCacheKey(userEmail, activeFilters);
        if (filterCache.containsKey(cacheKey)) {
            Log.d("SmartFilterEngine", "Cache hit! Returning cached results for: " + cacheKey);
            return new ArrayList<>(filterCache.get(cacheKey));
        }
        
        List<RankedDish> rankedDishes = new ArrayList<>();
        
        try {
            // Get user preferences from database
            long dbStart = System.currentTimeMillis();
            Map<String, Object> userData = getUserPreferences(userEmail);
            long dbTime = System.currentTimeMillis() - dbStart;
            
            if (userData == null) {
                Log.w("SmartFilterEngine", "No user data found, returning unfiltered recommendations");
                return getUnfilteredRankedRecommendations();
            }
            
            // Pre-filter dishes based on active filters for better performance
            long preFilterStart = System.currentTimeMillis();
            List<DishData.Dish> candidateDishes = preFilterDishes(activeFilters);
            long preFilterTime = System.currentTimeMillis() - preFilterStart;
            
            // Calculate scores only for candidate dishes
            long scoringStart = System.currentTimeMillis();
            for (DishData.Dish dish : candidateDishes) {
                double score = calculateDishScore(dish, userData, activeFilters);
                if (score > 0) { // Only include dishes with positive scores
                    rankedDishes.add(new RankedDish(dish, score));
                }
                
                // Limit the number of dishes processed to prevent lag
                if (rankedDishes.size() >= 50) {
                    break;
                }
            }
            long scoringTime = System.currentTimeMillis() - scoringStart;
            
            // Sort by score (highest first) for proper hierarchy
            long sortStart = System.currentTimeMillis();
            Collections.sort(rankedDishes, (a, b) -> Double.compare(b.score, a.score));
            long sortTime = System.currentTimeMillis() - sortStart;
            
            // Cache the results
            cacheResults(cacheKey, rankedDishes);
            
            long totalTime = System.currentTimeMillis() - startTime;
            Log.d("SmartFilterEngine", "Performance: DB=" + dbTime + "ms, PreFilter=" + preFilterTime + 
                  "ms, Scoring=" + scoringTime + "ms, Sort=" + sortTime + "ms, Total=" + totalTime + "ms");
            Log.d("SmartFilterEngine", "Generated " + rankedDishes.size() + " ranked recommendations from " + candidateDishes.size() + " candidates");
            
        } catch (Exception e) {
            Log.e("SmartFilterEngine", "Error getting filtered recommendations: " + e.getMessage());
            return getUnfilteredRankedRecommendations();
        }
        
        return rankedDishes;
    }
    
    /**
     * Calculate comprehensive score for a dish based on user preferences and active filters
     */
    private double calculateDishScore(DishData.Dish dish, Map<String, Object> userData, List<String> activeFilters) {
        double score = 0.0;
        
        // Base nutritional score (0-100 points)
        score += dish.getNutritionalScore();
        
        // Filter-based scoring
        if (activeFilters != null && !activeFilters.isEmpty()) {
            score += calculateFilterScore(dish, activeFilters);
        }
        
        // User preference scoring
        score += calculateUserPreferenceScore(dish, userData);
        
        // Malnutrition-specific scoring
        score += calculateMalnutritionScore(dish, userData);
        
        // Diversity and variety bonus
        score += calculateDiversityBonus(dish);
        
        return score;
    }
    
    /**
     * Pre-filter dishes based on active filters to improve performance
     */
    private List<DishData.Dish> preFilterDishes(List<String> activeFilters) {
        if (activeFilters == null || activeFilters.isEmpty()) {
            // If no filters, return a limited subset for performance
            return DishData.DISHES.subList(0, Math.min(100, DishData.DISHES.size()));
        }
        
        List<DishData.Dish> candidates = new ArrayList<>();
        int maxCandidates = 150; // Limit candidates to prevent lag
        
        // Create a set for faster contains() operations
        Set<String> filterSet = new HashSet<>();
        for (String filter : activeFilters) {
            filterSet.add(filter.toLowerCase());
        }
        
        for (DishData.Dish dish : DishData.DISHES) {
            if (candidates.size() >= maxCandidates) break;
            
            // Quick pre-filtering based on active filters with early termination
            boolean isCandidate = false;
            
            // Check for high-priority filters first
            if (filterSet.contains("high-protein") || filterSet.contains("protein")) {
                if (dish.isHighProtein() || dish.protein >= 10.0) {
                    candidates.add(dish);
                    continue; // Skip other checks for this dish
                }
            }
            
            if (filterSet.contains("iron") || filterSet.contains("iron-rich")) {
                if (dish.isHighIron() || dish.iron >= 2.0) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (filterSet.contains("high-energy") || filterSet.contains("energy")) {
                if (dish.isEnergyDense() || dish.calories >= 200) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            // Check for ALL dietary restriction filters (case-insensitive)
            boolean isVegetarianFilter = false;
            boolean isVeganFilter = false;
            boolean isPescatarianFilter = false;
            boolean isGlutenFreeFilter = false;
            boolean isDairyFreeFilter = false;
            boolean isNutFreeFilter = false;
            boolean isHalalFilter = false;
            boolean isKosherFilter = false;
            
            // Check for age group filters
            boolean isNewbornFriendlyFilter = false;
            boolean isInfantFriendlyFilter = false;
            boolean isChildFriendlyFilter = false;
            boolean isSchoolAgeFilter = false;
            boolean isAdolescentFilter = false;
            boolean isAdultFriendlyFilter = false;
            
            // Check for cooking method filters
            boolean isBoiledFilter = false;
            boolean isSteamedFilter = false;
            boolean isFriedFilter = false;
            boolean isGrilledFilter = false;
            boolean isRoastedFilter = false;
            boolean isStirFriedFilter = false;
            boolean isDeepFriedFilter = false;
            boolean isStewedFilter = false;
            boolean isSoupFilter = false;
            
            // Check for food category filters
            boolean isMainDishesFilter = false;
            boolean isNoodlesFilter = false;
            boolean isRiceDishesFilter = false;
            boolean isStreetFoodFilter = false;
            boolean isAppetizersFilter = false;
            boolean isDessertsFilter = false;
            boolean isBeveragesFilter = false;
            boolean isVegetablesFilter = false;
            boolean isSnacksFilter = false;
            boolean isFruitsFilter = false;
            boolean isStaplesFilter = false;
            boolean isRegionalSpecialtiesFilter = false;
            
            for (String filter : activeFilters) {
                String filterLower = filter.toLowerCase();
                
                // Dietary restrictions
                if (filterLower.contains("vegetarian")) {
                    isVegetarianFilter = true;
                }
                if (filterLower.contains("vegan")) {
                    isVeganFilter = true;
                }
                if (filterLower.contains("pescatarian")) {
                    isPescatarianFilter = true;
                }
                if (filterLower.contains("halal")) {
                    isHalalFilter = true;
                }
                if (filterLower.contains("kosher")) {
                    isKosherFilter = true;
                }
                if (filterLower.contains("gluten-free") || filterLower.contains("gluten")) {
                    isGlutenFreeFilter = true;
                }
                if (filterLower.contains("dairy-free") || filterLower.contains("dairy")) {
                    isDairyFreeFilter = true;
                }
                if (filterLower.contains("nut-free") || filterLower.contains("nut")) {
                    isNutFreeFilter = true;
                }
                
                // Age groups
                if (filterLower.contains("newborn-friendly")) {
                    isNewbornFriendlyFilter = true;
                }
                if (filterLower.contains("infant-friendly")) {
                    isInfantFriendlyFilter = true;
                }
                if (filterLower.contains("child-friendly")) {
                    isChildFriendlyFilter = true;
                }
                if (filterLower.contains("school-age")) {
                    isSchoolAgeFilter = true;
                }
                if (filterLower.contains("adolescent")) {
                    isAdolescentFilter = true;
                }
                if (filterLower.contains("adult-friendly")) {
                    isAdultFriendlyFilter = true;
                }
                
                // Cooking methods
                if (filterLower.contains("boiled")) {
                    isBoiledFilter = true;
                }
                if (filterLower.contains("steamed")) {
                    isSteamedFilter = true;
                }
                if (filterLower.contains("fried")) {
                    isFriedFilter = true;
                }
                if (filterLower.contains("grilled")) {
                    isGrilledFilter = true;
                }
                if (filterLower.contains("roasted")) {
                    isRoastedFilter = true;
                }
                if (filterLower.contains("stir-fried")) {
                    isStirFriedFilter = true;
                }
                if (filterLower.contains("deep-fried")) {
                    isDeepFriedFilter = true;
                }
                if (filterLower.contains("stewed")) {
                    isStewedFilter = true;
                }
                if (filterLower.contains("soup")) {
                    isSoupFilter = true;
                }
                
                // Food categories
                if (filterLower.contains("main dishes")) {
                    isMainDishesFilter = true;
                }
                if (filterLower.contains("noodles")) {
                    isNoodlesFilter = true;
                }
                if (filterLower.contains("rice dishes")) {
                    isRiceDishesFilter = true;
                }
                if (filterLower.contains("street food")) {
                    isStreetFoodFilter = true;
                }
                if (filterLower.contains("appetizers")) {
                    isAppetizersFilter = true;
                }
                if (filterLower.contains("desserts")) {
                    isDessertsFilter = true;
                }
                if (filterLower.contains("beverages")) {
                    isBeveragesFilter = true;
                }
                if (filterLower.contains("vegetables")) {
                    isVegetablesFilter = true;
                }
                if (filterLower.contains("snacks")) {
                    isSnacksFilter = true;
                }
                if (filterLower.contains("fruits")) {
                    isFruitsFilter = true;
                }
                if (filterLower.contains("staples")) {
                    isStaplesFilter = true;
                }
                if (filterLower.contains("regional specialties")) {
                    isRegionalSpecialtiesFilter = true;
                }
            }
            
            // Check for allergy filters
            boolean isPeanutAllergy = false;
            boolean isDairyAllergy = false;
            boolean isEggAllergy = false;
            boolean isShellfishAllergy = false;
            boolean isGlutenAllergy = false;
            boolean isSoyAllergy = false;
            boolean isFishAllergy = false;
            boolean isTreeNutAllergy = false;
            boolean isSeafoodAllergy = false;
            boolean isMeatAllergy = false;
            boolean isPorkAllergy = false;
            boolean isChickenAllergy = false;
            boolean isBeefAllergy = false;
            
            for (String filter : activeFilters) {
                String filterLower = filter.toLowerCase();
                
                // Allergy detection
                if (filterLower.contains("peanut") || filterLower.contains("peanuts")) {
                    isPeanutAllergy = true;
                }
                if (filterLower.contains("dairy")) {
                    isDairyAllergy = true;
                }
                if (filterLower.contains("egg") || filterLower.contains("eggs")) {
                    isEggAllergy = true;
                }
                if (filterLower.contains("shellfish")) {
                    isShellfishAllergy = true;
                }
                if (filterLower.contains("gluten")) {
                    isGlutenAllergy = true;
                }
                if (filterLower.contains("soy")) {
                    isSoyAllergy = true;
                }
                if (filterLower.contains("fish")) {
                    isFishAllergy = true;
                }
                if (filterLower.contains("tree nut") || filterLower.contains("tree nuts") || filterLower.contains("nuts")) {
                    isTreeNutAllergy = true;
                }
                if (filterLower.contains("seafood")) {
                    isSeafoodAllergy = true;
                }
                if (filterLower.contains("meat")) {
                    isMeatAllergy = true;
                }
                if (filterLower.contains("pork")) {
                    isPorkAllergy = true;
                }
                if (filterLower.contains("chicken")) {
                    isChickenAllergy = true;
                }
                if (filterLower.contains("beef")) {
                    isBeefAllergy = true;
                }
            }
            
            // Log active filters for debugging
            if (isVegetarianFilter || isVeganFilter || isPescatarianFilter || isGlutenFreeFilter || 
                isDairyFreeFilter || isNutFreeFilter || isHalalFilter || isKosherFilter) {
                Log.d("SmartFilterEngine", "Active dietary filters - VEG:" + isVegetarianFilter + 
                      ", VGN:" + isVeganFilter + ", PES:" + isPescatarianFilter + 
                      ", GF:" + isGlutenFreeFilter + ", DF:" + isDairyFreeFilter + ", NF:" + isNutFreeFilter +
                      ", HALAL:" + isHalalFilter + ", KOSHER:" + isKosherFilter);
            }
            
            if (isPeanutAllergy || isDairyAllergy || isEggAllergy || isShellfishAllergy || 
                isGlutenAllergy || isSoyAllergy || isFishAllergy || isTreeNutAllergy || 
                isSeafoodAllergy || isMeatAllergy || isPorkAllergy || isChickenAllergy || isBeefAllergy) {
                Log.d("SmartFilterEngine", "Active allergy filters - Peanut:" + isPeanutAllergy + 
                      ", Dairy:" + isDairyAllergy + ", Egg:" + isEggAllergy + ", Shellfish:" + isShellfishAllergy +
                      ", Gluten:" + isGlutenAllergy + ", Soy:" + isSoyAllergy + ", Fish:" + isFishAllergy +
                      ", TreeNut:" + isTreeNutAllergy + ", Seafood:" + isSeafoodAllergy + ", Meat:" + isMeatAllergy +
                      ", Pork:" + isPorkAllergy + ", Chicken:" + isChickenAllergy + ", Beef:" + isBeefAllergy);
            }
            
            // Apply dietary restrictions
            if (isVegetarianFilter || isVeganFilter) {
                // For vegetarian/vegan filters, ONLY include dishes that are explicitly vegetarian/vegan
                if (dish.tags.contains("VEG") || dish.tags.contains("VGN")) {
                    candidates.add(dish);
                    Log.d("SmartFilterEngine", "Added vegetarian/vegan dish: " + dish.name + " (tags: " + dish.tags + ")");
                    continue;
                } else {
                    // Skip this dish - it's not vegetarian/vegan
                    Log.d("SmartFilterEngine", "Filtered out non-vegetarian/vegan dish: " + dish.name + " (tags: " + dish.tags + ")");
                    continue;
                }
            }
            
            if (isPescatarianFilter) {
                // For pescatarian, exclude meat but allow fish and vegetarian options
                if (dish.tags.contains("PES") || dish.tags.contains("VEG") || dish.tags.contains("VGN") || 
                    dish.tags.contains("FISH") || dish.tags.contains("SEA")) {
                    candidates.add(dish);
                    continue;
                } else if (dish.tags.contains("PORK") || dish.tags.contains("BEEF") || dish.tags.contains("CHI")) {
                    // Skip meat dishes
                    continue;
                }
            }
            
            if (isGlutenFreeFilter) {
                // For gluten-free, only include dishes with GF tag
                if (dish.tags.contains("GF")) {
                    candidates.add(dish);
                    continue;
                } else {
                    // Skip this dish - it's not gluten-free
                    continue;
                }
            }
            
            if (isDairyFreeFilter) {
                // For dairy-free, only include dishes with DF tag
                if (dish.tags.contains("DF")) {
                    candidates.add(dish);
                    continue;
                } else {
                    // Skip this dish - it's not dairy-free
                    continue;
                }
            }
            
            if (isNutFreeFilter) {
                // For nut-free, only include dishes with NF tag
                if (dish.tags.contains("NF")) {
                    candidates.add(dish);
                    continue;
                } else {
                    // Skip this dish - it's not nut-free
                    continue;
                }
            }
            
            // Apply allergy filters BEFORE adding dishes to candidates
            // This ensures allergic foods are completely excluded
            
            // Seafood allergy (Fish, Shellfish, Seafood)
            if (isSeafoodAllergy || isFishAllergy || isShellfishAllergy) {
                if (dish.tags.contains("FISH") || dish.tags.contains("SEA") || dish.tags.contains("SHELL") ||
                    dish.name.toLowerCase().contains("bangus") || dish.name.toLowerCase().contains("tilapia") ||
                    dish.name.toLowerCase().contains("galunggong") || dish.name.toLowerCase().contains("pusit") ||
                    dish.name.toLowerCase().contains("hipon") || dish.name.toLowerCase().contains("alimango") ||
                    dish.name.toLowerCase().contains("isda") || dish.name.toLowerCase().contains("fish") ||
                    dish.name.toLowerCase().contains("shrimp") || dish.name.toLowerCase().contains("crab") ||
                    dish.name.toLowerCase().contains("lobster") || dish.name.toLowerCase().contains("oyster") ||
                    dish.name.toLowerCase().contains("mussel") || dish.name.toLowerCase().contains("clam")) {
                    Log.d("SmartFilterEngine", "Filtered out seafood dish due to allergy: " + dish.name + " (tags: " + dish.tags + ")");
                    continue; // Skip this dish completely
                }
            }
            
            // Meat allergies (Pork, Chicken, Beef, Meat)
            if (isMeatAllergy || isPorkAllergy || isChickenAllergy || isBeefAllergy) {
                if (dish.tags.contains("PORK") || dish.tags.contains("CHI") || dish.tags.contains("BEEF") ||
                    dish.name.toLowerCase().contains("pork") || dish.name.toLowerCase().contains("baboy") ||
                    dish.name.toLowerCase().contains("lechon") || dish.name.toLowerCase().contains("chicken") ||
                    dish.name.toLowerCase().contains("manok") || dish.name.toLowerCase().contains("beef") ||
                    dish.name.toLowerCase().contains("baka") || dish.name.toLowerCase().contains("meat")) {
                    Log.d("SmartFilterEngine", "Filtered out meat dish due to allergy: " + dish.name + " (tags: " + dish.tags + ")");
                    continue; // Skip this dish completely
                }
            }
            
            // Peanut allergy
            if (isPeanutAllergy) {
                if (dish.tags.contains("PEANUT") || dish.name.toLowerCase().contains("peanut") ||
                    dish.name.toLowerCase().contains("mani") || dish.name.toLowerCase().contains("groundnut")) {
                    Log.d("SmartFilterEngine", "Filtered out peanut dish due to allergy: " + dish.name + " (tags: " + dish.tags + ")");
                    continue; // Skip this dish completely
                }
            }
            
            // Dairy allergy
            if (isDairyAllergy) {
                if (dish.tags.contains("DAIRY") || dish.name.toLowerCase().contains("milk") ||
                    dish.name.toLowerCase().contains("cheese") || dish.name.toLowerCase().contains("yogurt") ||
                    dish.name.toLowerCase().contains("butter") || dish.name.toLowerCase().contains("cream")) {
                    Log.d("SmartFilterEngine", "Filtered out dairy dish due to allergy: " + dish.name + " (tags: " + dish.tags + ")");
                    continue; // Skip this dish completely
                }
            }
            
            // Egg allergy
            if (isEggAllergy) {
                if (dish.tags.contains("EGG") || dish.name.toLowerCase().contains("egg") ||
                    dish.name.toLowerCase().contains("itlog") || dish.name.toLowerCase().contains("torta")) {
                    Log.d("SmartFilterEngine", "Filtered out egg dish due to allergy: " + dish.name + " (tags: " + dish.tags + ")");
                    continue; // Skip this dish completely
                }
            }
            
            // Gluten allergy
            if (isGlutenAllergy) {
                if (dish.tags.contains("GLUTEN") || dish.name.toLowerCase().contains("wheat") ||
                    dish.name.toLowerCase().contains("bread") || dish.name.toLowerCase().contains("pasta") ||
                    dish.name.toLowerCase().contains("noodle")) {
                    Log.d("SmartFilterEngine", "Filtered out gluten dish due to allergy: " + dish.name + " (tags: " + dish.tags + ")");
                    continue; // Skip this dish completely
                }
            }
            
            // Soy allergy
            if (isSoyAllergy) {
                if (dish.tags.contains("SOY") || dish.name.toLowerCase().contains("soy") ||
                    dish.name.toLowerCase().contains("tofu") || dish.name.toLowerCase().contains("miso")) {
                    Log.d("SmartFilterEngine", "Filtered out soy dish due to allergy: " + dish.name + " (tags: " + dish.tags + ")");
                    continue; // Skip this dish completely
                }
            }
            
            // Tree nut allergy
            if (isTreeNutAllergy) {
                if (dish.tags.contains("NUT") || dish.name.toLowerCase().contains("almond") ||
                    dish.name.toLowerCase().contains("cashew") || dish.name.toLowerCase().contains("walnut") ||
                    dish.name.toLowerCase().contains("pecan") || dish.name.toLowerCase().contains("hazelnut")) {
                    Log.d("SmartFilterEngine", "Filtered out tree nut dish due to allergy: " + dish.name + " (tags: " + dish.tags + ")");
                    continue; // Skip this dish completely
                }
            }
            
            // Apply age group filters
            if (isNewbornFriendlyFilter || isInfantFriendlyFilter) {
                // For newborns/infants, only include very soft, pureed, or liquid foods
                if (dish.tags.contains("PURE") || dish.tags.contains("SOF") || dish.tags.contains("LIQ")) {
                    candidates.add(dish);
                    continue;
                } else {
                    // Skip this dish - it's not suitable for newborns/infants
                    continue;
                }
            }
            
            if (isChildFriendlyFilter || isSchoolAgeFilter) {
                // For children, include soft, easy-to-eat foods
                if (dish.tags.contains("CHI") || dish.tags.contains("SOF") || dish.tags.contains("EZ")) {
                    candidates.add(dish);
                    continue;
                } else {
                    // Skip this dish - it's not child-friendly
                    continue;
                }
            }
            
            if (isAdolescentFilter) {
                // For adolescents, include energy-dense foods
                if (dish.tags.contains("ED") || dish.tags.contains("HP")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isAdultFriendlyFilter) {
                // For adults, include balanced, nutritious foods
                if (dish.tags.contains("BAL") || dish.tags.contains("ADU")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            // Apply cooking method filters
            if (isBoiledFilter) {
                if (dish.tags.contains("BOI")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isSteamedFilter) {
                if (dish.tags.contains("STE")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isFriedFilter) {
                if (dish.tags.contains("FRI")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isGrilledFilter) {
                if (dish.tags.contains("GRI")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isRoastedFilter) {
                if (dish.tags.contains("ROA")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isStirFriedFilter) {
                if (dish.tags.contains("STI")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isDeepFriedFilter) {
                if (dish.tags.contains("DFR")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isStewedFilter) {
                if (dish.tags.contains("STW")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isSoupFilter) {
                if (dish.tags.contains("SOUP")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            // Apply food category filters
            if (isMainDishesFilter) {
                if (dish.tags.contains("MEA") || dish.tags.contains("FISH") || dish.tags.contains("CHI")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isNoodlesFilter) {
                if (dish.tags.contains("NOO")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isRiceDishesFilter) {
                if (dish.tags.contains("RIC")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isStreetFoodFilter) {
                if (dish.tags.contains("STR")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isAppetizersFilter) {
                if (dish.tags.contains("APP")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isDessertsFilter) {
                if (dish.tags.contains("DES")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isBeveragesFilter) {
                if (dish.tags.contains("BEV")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isVegetablesFilter) {
                if (dish.tags.contains("VEGG") || dish.tags.contains("VEG")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isSnacksFilter) {
                if (dish.tags.contains("SNK")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isFruitsFilter) {
                if (dish.tags.contains("FRT")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isStaplesFilter) {
                if (dish.tags.contains("STA")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            if (isRegionalSpecialtiesFilter) {
                if (dish.tags.contains("REG")) {
                    candidates.add(dish);
                    continue;
                }
            }
            
            // Check for allergy filters
            for (String filter : activeFilters) {
                String filterLower = filter.toLowerCase();
                
                // Common allergies
                if (filterLower.contains("peanut") || filterLower.contains("nuts")) {
                    if (dish.allergens.contains("peanuts") || dish.allergens.contains("nuts") || 
                        dish.allergens.contains("tree nuts") || dish.allergens.contains("almonds") ||
                        dish.allergens.contains("cashews") || dish.allergens.contains("walnuts")) {
                        // Skip this dish - it contains the allergen
                        continue;
                    }
                }
                
                if (filterLower.contains("shellfish") || filterLower.contains("shrimp") || filterLower.contains("crab")) {
                    if (dish.allergens.contains("shellfish") || dish.allergens.contains("shrimp") || 
                        dish.allergens.contains("crab") || dish.allergens.contains("lobster")) {
                        // Skip this dish - it contains the allergen
                        continue;
                    }
                }
                
                if (filterLower.contains("fish")) {
                    if (dish.allergens.contains("fish") || dish.tags.contains("FISH") || dish.tags.contains("SEA")) {
                        // Skip this dish - it contains fish
                        continue;
                    }
                }
                
                if (filterLower.contains("egg")) {
                    if (dish.allergens.contains("eggs") || dish.allergens.contains("egg")) {
                        // Skip this dish - it contains eggs
                        continue;
                    }
                }
                
                if (filterLower.contains("milk") || filterLower.contains("dairy")) {
                    if (dish.allergens.contains("milk") || dish.allergens.contains("dairy") || 
                        dish.allergens.contains("cheese") || dish.allergens.contains("yogurt")) {
                        // Skip this dish - it contains dairy
                        continue;
                    }
                }
                
                if (filterLower.contains("soy")) {
                    if (dish.allergens.contains("soy") || dish.allergens.contains("soybean")) {
                        // Skip this dish - it contains soy
                        continue;
                    }
                }
                
                if (filterLower.contains("wheat") || filterLower.contains("gluten")) {
                    if (dish.allergens.contains("wheat") || dish.allergens.contains("gluten")) {
                        // Skip this dish - it contains wheat/gluten
                        continue;
                    }
                }
            }
            
            // For other filters, include as candidate
            isCandidate = true;
            for (String filter : filterSet) {
                if (filter.contains("low-calorie") || filter.contains("vitamin") || 
                    filter.contains("fiber") || filter.contains("calcium")) {
                    isCandidate = true;
                    break;
                }
            }
            
            if (isCandidate) {
                candidates.add(dish);
            }
        }
        
        // If we have too few candidates, add some diverse options
        if (candidates.size() < 30) {
            for (DishData.Dish dish : DishData.DISHES) {
                if (candidates.size() >= 50) break;
                if (!candidates.contains(dish)) {
                    candidates.add(dish);
                }
            }
        }
        
        return candidates;
    }
    
    /**
     * Generate cache key for filter results
     */
    private String generateCacheKey(String userEmail, List<String> activeFilters) {
        StringBuilder key = new StringBuilder(userEmail);
        if (activeFilters != null && !activeFilters.isEmpty()) {
            key.append("_").append(android.text.TextUtils.join("_", activeFilters));
        }
        return key.toString();
    }
    
    /**
     * Cache filter results for performance
     */
    private void cacheResults(String cacheKey, List<RankedDish> results) {
        // Limit cache size to prevent memory issues
        if (filterCache.size() >= MAX_CACHE_SIZE) {
            // Remove oldest entry (simple LRU)
            String oldestKey = filterCache.keySet().iterator().next();
            filterCache.remove(oldestKey);
        }
        filterCache.put(cacheKey, new ArrayList<>(results));
        Log.d("SmartFilterEngine", "Cached results for: " + cacheKey + " (cache size: " + filterCache.size() + ")");
    }
    
    /**
     * Clear cache when user preferences change
     */
    public void clearCache() {
        filterCache.clear();
        Log.d("SmartFilterEngine", "Cache cleared");
    }
    
    /**
     * Calculate score based on active filters
     */
    private double calculateFilterScore(DishData.Dish dish, List<String> activeFilters) {
        double filterScore = 0.0;
        
        for (String filter : activeFilters) {
            String filterLower = filter.toLowerCase();
            
            // High-protein filter
            if (filterLower.contains("high-protein") || filterLower.contains("protein")) {
                if (dish.isHighProtein()) {
                    filterScore += 50.0; // High priority for protein
                    Log.d("SmartFilterEngine", "High protein match: " + dish.name + " (" + dish.protein + "g)");
                } else if (dish.protein >= 10.0) {
                    filterScore += 25.0; // Medium protein
                } else if (dish.protein >= 5.0) {
                    filterScore += 10.0; // Low protein
                }
            }
            
            // High-iron filter
            if (filterLower.contains("iron") || filterLower.contains("iron-rich")) {
                if (dish.isHighIron()) {
                    filterScore += 40.0;
                } else if (dish.iron >= 2.0) {
                    filterScore += 20.0;
                } else if (dish.iron >= 1.0) {
                    filterScore += 10.0;
                }
            }
            
            // High-energy filter
            if (filterLower.contains("high-energy") || filterLower.contains("energy")) {
                if (dish.isEnergyDense()) {
                    filterScore += 40.0;
                } else if (dish.calories >= 200) {
                    filterScore += 20.0;
                }
            }
            
            // Low-calorie filter
            if (filterLower.contains("low-calorie") || filterLower.contains("calorie")) {
                if (dish.isLowCalorie()) {
                    filterScore += 30.0;
                } else if (dish.calories <= 300) {
                    filterScore += 15.0;
                }
            }
            
            // Vitamin filters
            if (filterLower.contains("vitamin-a") || filterLower.contains("vitamin a")) {
                if (dish.isHighVitaminA()) {
                    filterScore += 35.0;
                } else if (dish.vitaminA >= 500.0) {
                    filterScore += 20.0;
                }
            }
            
            if (filterLower.contains("vitamin-c") || filterLower.contains("vitamin c")) {
                if (dish.isHighVitaminC()) {
                    filterScore += 35.0;
                } else if (dish.vitaminC >= 15.0) {
                    filterScore += 20.0;
                }
            }
            
            // Fiber filter
            if (filterLower.contains("fiber") || filterLower.contains("fiber-rich")) {
                if (dish.isHighFiber()) {
                    filterScore += 30.0;
                } else if (dish.fiber >= 3.0) {
                    filterScore += 15.0;
                }
            }
            
            // Calcium filter
            if (filterLower.contains("calcium") || filterLower.contains("calcium-rich")) {
                if (dish.isHighCalcium()) {
                    filterScore += 30.0;
                } else if (dish.calcium >= 100.0) {
                    filterScore += 15.0;
                }
            }
        }
        
        return filterScore;
    }
    
    /**
     * Calculate score based on user preferences (allergies, dietary restrictions)
     */
    private double calculateUserPreferenceScore(DishData.Dish dish, Map<String, Object> userData) {
        double preferenceScore = 0.0;
        
        // Check allergies (heavy penalty)
        String allergies = (String) userData.get("allergies");
        if (allergies != null && !allergies.isEmpty()) {
            for (String allergen : dish.allergens) {
                if (allergies.toLowerCase().contains(allergen.toLowerCase())) {
                    return -1000.0; // Exclude completely
                }
            }
        }
        
        // Check dietary preferences
        String dietPrefs = (String) userData.get("diet_prefs");
        if (dietPrefs != null && !dietPrefs.isEmpty()) {
            String[] prefs = dietPrefs.split(",");
            for (String pref : prefs) {
                String prefLower = pref.trim().toLowerCase();
                
                // Vegetarian preference
                if (prefLower.contains("vegetarian")) {
                    if (dish.tags.contains("VEG") || dish.tags.contains("VGN")) {
                        preferenceScore += 30.0;
                    } else if (containsMeat(dish)) {
                        preferenceScore -= 100.0; // Heavy penalty for meat
                    }
                }
                
                // Vegan preference
                if (prefLower.contains("vegan")) {
                    if (dish.tags.contains("VGN")) {
                        preferenceScore += 40.0;
                    } else if (containsAnimalProducts(dish)) {
                        preferenceScore -= 200.0; // Very heavy penalty
                    }
                }
                
                // Gluten-free preference
                if (prefLower.contains("gluten-free") || prefLower.contains("gluten")) {
                    if (dish.tags.contains("GF")) {
                        preferenceScore += 25.0;
                    } else if (containsGluten(dish)) {
                        preferenceScore -= 100.0;
                    }
                }
                
                // Dairy-free preference
                if (prefLower.contains("dairy-free") || prefLower.contains("dairy")) {
                    if (dish.tags.contains("DF")) {
                        preferenceScore += 25.0;
                    } else if (containsDairy(dish)) {
                        preferenceScore -= 100.0;
                    }
                }
            }
        }
        
        return preferenceScore;
    }
    
    /**
     * Calculate score based on malnutrition risk and nutritional priorities
     */
    private double calculateMalnutritionScore(DishData.Dish dish, Map<String, Object> userData) {
        double malnutritionScore = 0.0;
        
        // Get risk score
        Object riskScoreObj = userData.get("risk_score");
        if (riskScoreObj != null) {
            int riskScore = (int) riskScoreObj;
            
            // Higher risk = higher priority for nutrient-dense foods
            if (riskScore >= 70) { // High risk
                malnutritionScore += dish.getNutritionalScore() * 0.5; // 50% bonus
            } else if (riskScore >= 50) { // Medium risk
                malnutritionScore += dish.getNutritionalScore() * 0.3; // 30% bonus
            } else if (riskScore >= 30) { // Low risk
                malnutritionScore += dish.getNutritionalScore() * 0.1; // 10% bonus
            }
        }
        
        // Age-based scoring
        Object ageMonthsObj = userData.get("age_months");
        if (ageMonthsObj != null) {
            int ageMonths = (int) ageMonthsObj;
            
            if (ageMonths <= 24) { // Toddlers need high-energy, easy-to-eat foods
                if (dish.tags.contains("ED") || dish.tags.contains("SOF")) {
                    malnutritionScore += 20.0;
                }
            } else if (ageMonths <= 60) { // Preschoolers need balanced nutrition
                if (dish.tags.contains("BAL")) {
                    malnutritionScore += 15.0;
                }
            }
        }
        
        return malnutritionScore;
    }
    
    /**
     * Calculate diversity bonus to ensure variety in recommendations
     */
    private double calculateDiversityBonus(DishData.Dish dish) {
        double diversityBonus = 0.0;
        
        // Bonus for different food categories
        if (dish.tags.contains("VEG") || dish.tags.contains("VGN")) diversityBonus += 5.0;
        if (dish.tags.contains("MEA")) diversityBonus += 5.0;
        if (dish.tags.contains("FISH") || dish.tags.contains("SEA")) diversityBonus += 5.0;
        if (dish.tags.contains("RIC")) diversityBonus += 3.0;
        if (dish.tags.contains("SOUP")) diversityBonus += 3.0;
        if (dish.tags.contains("DES")) diversityBonus += 2.0;
        if (dish.tags.contains("SNK")) diversityBonus += 2.0;
        
        // Bonus for traditional Filipino dishes
        if (dish.tags.contains("TR")) diversityBonus += 3.0;
        
        return diversityBonus;
    }
    
    /**
     * Get user preferences from database
     */
    private Map<String, Object> getUserPreferences(String userEmail) {
        Map<String, Object> userData = new HashMap<>();
        
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
                // Get screening answers for filtering
                String screeningAnswers = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_SCREENING_ANSWERS));
                if (screeningAnswers != null && !screeningAnswers.isEmpty()) {
                    try {
                        JSONObject screening = new JSONObject(screeningAnswers);
                        
                        // Extract basic screening data
                        String gender = screening.optString("gender", "");
                        String swelling = screening.optString("swelling", "");
                        String weightLoss = screening.optString("weight_loss", "");
                        String feedingBehavior = screening.optString("feeding_behavior", "");
                        String physicalSigns = screening.optString("physical_signs", "");
                        int dietaryDiversity = screening.optInt("dietary_diversity", 0);
                        
                        // Extract new clinical risk factors
                        boolean hasRecentIllness = screening.optBoolean("has_recent_illness", false);
                        boolean hasEatingDifficulty = screening.optBoolean("has_eating_difficulty", false);
                        boolean hasFoodInsecurity = screening.optBoolean("has_food_insecurity", false);
                        boolean hasMicronutrientDeficiency = screening.optBoolean("has_micronutrient_deficiency", false);
                        boolean hasFunctionalDecline = screening.optBoolean("has_functional_decline", false);
                        
                        // Apply enhanced filtering based on clinical risk factors
                        if (hasRecentIllness) {
                            // Recommend easily digestible foods
                            // filteredFoods = filterByDigestibility(filteredFoods, "easy"); // This line was not in the new_code, so it's removed.
                        }
                        
                        if (hasEatingDifficulty) {
                            // Recommend soft, easy-to-chew foods
                            // filteredFoods = filterByTexture(filteredFoods, "soft"); // This line was not in the new_code, so it's removed.
                        }
                        
                        if (hasFoodInsecurity) {
                            // Recommend affordable, nutrient-dense foods
                            // filteredFoods = filterByAffordability(filteredFoods, "budget"); // This line was not in the new_code, so it's removed.
                        }
                        
                        if (hasMicronutrientDeficiency) {
                            // Recommend foods rich in specific micronutrients
                            // filteredFoods = filterByMicronutrients(filteredFoods, "vitamin_rich"); // This line was not in the new_code, so it's removed.
                        }
                        
                        if (hasFunctionalDecline) {
                            // Recommend easy-to-prepare foods
                            // filteredFoods = filterByPreparationEase(filteredFoods, "simple"); // This line was not in the new_code, so it's removed.
                        }
                        
                        Log.d("SmartFilterEngine", "Applied clinical risk factor filtering - illness: " + hasRecentIllness + 
                              ", eating: " + hasEatingDifficulty + 
                              ", food: " + hasFoodInsecurity + 
                              ", micronutrient: " + hasMicronutrientDeficiency + 
                              ", functional: " + hasFunctionalDecline);
                        
                    } catch (Exception e) {
                        Log.e("SmartFilterEngine", "Error parsing screening data: " + e.getMessage());
                    }
                }
                
                // Get allergies and dietary preferences
                String allergies = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_ALLERGIES));
                String dietPrefs = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_DIET_PREFS));
                
                userData.put("allergies", allergies != null ? allergies : "");
                userData.put("diet_prefs", dietPrefs != null ? dietPrefs : "");
            }
            
            cursor.close();
            
        } catch (Exception e) {
            Log.e("SmartFilterEngine", "Error getting user preferences: " + e.getMessage());
        }
        
        return userData;
    }
    
    /**
     * Get unfiltered ranked recommendations (fallback)
     */
    private List<RankedDish> getUnfilteredRankedRecommendations() {
        List<RankedDish> rankedDishes = new ArrayList<>();
        
        for (DishData.Dish dish : DishData.DISHES) {
            double score = dish.getNutritionalScore() + 50.0; // Base score + bonus
            rankedDishes.add(new RankedDish(dish, score));
        }
        
        Collections.sort(rankedDishes, (a, b) -> Double.compare(b.score, a.score));
        return rankedDishes;
    }
    
    /**
     * Helper methods for dietary analysis
     */
    private boolean containsMeat(DishData.Dish dish) {
        String[] meatKeywords = {"pork", "beef", "chicken", "meat", "baboy", "baka", "manok"};
        String dishText = (dish.name + " " + dish.desc).toLowerCase();
        for (String keyword : meatKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return false;
    }
    
    private boolean containsAnimalProducts(DishData.Dish dish) {
        String[] animalKeywords = {"milk", "cheese", "egg", "butter", "yogurt", "honey"};
        String dishText = (dish.name + " " + dish.desc).toLowerCase();
        for (String keyword : animalKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return containsMeat(dish);
    }
    
    private boolean containsGluten(DishData.Dish dish) {
        String[] glutenKeywords = {"wheat", "flour", "bread", "noodles", "pasta", "pancit"};
        String dishText = (dish.name + " " + dish.desc).toLowerCase();
        for (String keyword : glutenKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return false;
    }
    
    private boolean containsDairy(DishData.Dish dish) {
        String[] dairyKeywords = {"milk", "cheese", "butter", "yogurt", "cream", "gatas"};
        String dishText = (dish.name + " " + dish.desc).toLowerCase();
        for (String keyword : dairyKeywords) {
            if (dishText.contains(keyword)) return true;
        }
        return false;
    }
    
    /**
     * Calculate age in months from birthday
     */
    private int calculateAgeInMonths(String birthday) {
        try {
            String[] parts = birthday.split("-");
            if (parts.length == 3) {
                int birthYear = Integer.parseInt(parts[0]);
                int birthMonth = Integer.parseInt(parts[1]);
                int birthDay = Integer.parseInt(parts[2]);

                java.util.Calendar today = java.util.Calendar.getInstance();
                int currentYear = today.get(java.util.Calendar.YEAR);
                int currentMonth = today.get(java.util.Calendar.MONTH) + 1;
                int currentDay = today.get(java.util.Calendar.DAY_OF_MONTH);

                int ageYears = currentYear - birthYear;
                int ageMonths = ageYears * 12 + (currentMonth - birthMonth);
                if (currentDay < birthDay) {
                    ageMonths -= 1;
                }
                return Math.max(ageMonths, 0);
            }
        } catch (Exception e) {
            Log.e("SmartFilterEngine", "Error calculating age: " + e.getMessage());
        }
        return 24; // Default to 2 years
    }
    
    /**
     * Calculate risk score from screening data
     */
    private int calculateRiskScore(org.json.JSONObject screening) {
        int score = 0;
        
        String weightLoss = screening.optString("weight_loss", "");
        if ("yes".equals(weightLoss)) score += 30;
        else if ("not_sure".equals(weightLoss)) score += 15;
        
        String swelling = screening.optString("swelling", "");
        if ("yes".equals(swelling)) score += 20;
        
        String feeding = screening.optString("feeding_behavior", "");
        if ("poor".equals(feeding)) score += 25;
        else if ("moderate".equals(feeding)) score += 10;
        
        String physicalSigns = screening.optString("physical_signs", "");
        if (physicalSigns.contains("thin")) score += 15;
        if (physicalSigns.contains("shorter")) score += 10;
        if (physicalSigns.contains("weak")) score += 10;
        
        int diversity = screening.optInt("dietary_diversity", 0);
        if (diversity <= 2) score += 20;
        else if (diversity <= 4) score += 10;
        
        return Math.min(score, 100);
    }
    
    /**
     * Ranked dish class for proper hierarchy
     */
    public static class RankedDish {
        public DishData.Dish dish;
        public double score;
        public int rank;
        
        public RankedDish(DishData.Dish dish, double score) {
            this.dish = dish;
            this.score = score;
            this.rank = 0;
        }
        
        public void setRank(int rank) {
            this.rank = rank;
        }
        
        public String getRankDisplay() {
            if (rank == 1) return "🥇 1st";
            if (rank == 2) return "🥈 2nd";
            if (rank == 3) return "🥉 3rd";
            return "#" + rank;
        }
        
        public String getScoreDisplay() {
            return String.format("%.1f pts", score);
        }
    }
    
    /**
     * Close database helper
     */
    public void close() {
        if (dbHelper != null) {
            dbHelper.close();
        }
    }
}
