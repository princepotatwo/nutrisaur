package com.example.nutrisaur11;

import android.util.Log;
import java.util.*;
import java.util.concurrent.TimeUnit;
import okhttp3.*;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;

/**
 * Additional methods for FoodActivityIntegration
 * This file contains the remaining methods that were too large for the main file
 */
public class FoodActivityIntegrationMethods {
    private static final String TAG = "FoodActivityIntegrationMethods";
    
    // Use centralized API configuration
    private static final String GEMINI_API_URL = ApiConfig.GEMINI_TEXT_API_URL;
    private static final String GROQ_API_URL = ApiConfig.GROQ_API_URL;
    private static final String GROQ_API_KEY = ApiConfig.GROQ_API_KEY;
    private static final String GROQ_MODEL = ApiConfig.GROQ_MODEL;
    
    public static String buildComprehensiveFoodPrompt(String userAge, String userSex, String userBMI, 
                                                     String userHeight, String userWeight, String userHealthConditions, 
                                                     String userActivityLevel, String userBudgetLevel, String userDietaryRestrictions, 
                                                     String userAllergies, String userDietPrefs, String userAvoidFoods, 
                                                     String userRiskScore, String userBarangay, String userIncome, 
                                                     String userPregnancyStatus, String screeningAnswers) {
        
        // DEBUG: Log the comprehensive input parameters
        Log.d(TAG, "=== BUILDING COMPREHENSIVE FOOD PROMPT ===");
        Log.d(TAG, "Input userAge: " + userAge);
        Log.d(TAG, "Input userSex: " + userSex);
        Log.d(TAG, "Input userBMI: " + userBMI);
        Log.d(TAG, "Input userHeight: " + userHeight);
        Log.d(TAG, "Input userWeight: " + userWeight);
        Log.d(TAG, "Input userHealthConditions: " + userHealthConditions);
        Log.d(TAG, "Input userActivityLevel: " + userActivityLevel);
        Log.d(TAG, "Input userBudgetLevel: " + userBudgetLevel);
        Log.d(TAG, "Input userDietaryRestrictions: " + userDietaryRestrictions);
        Log.d(TAG, "Input userAllergies: " + userAllergies);
        Log.d(TAG, "Input userDietPrefs: " + userDietPrefs);
        Log.d(TAG, "Input userAvoidFoods: " + userAvoidFoods);
        Log.d(TAG, "Input userRiskScore: " + userRiskScore);
        Log.d(TAG, "Input userBarangay: " + userBarangay);
        Log.d(TAG, "Input userIncome: " + userIncome);
        Log.d(TAG, "Input userPregnancyStatus: " + userPregnancyStatus);
        Log.d(TAG, "Input screeningAnswers length: " + (screeningAnswers != null ? screeningAnswers.length() : "null"));
        
        // Create nutrition dashboard to get personalized targets
        DailyNutritionDashboard nutritionDashboard = new DailyNutritionDashboard(
            userAge, userSex, userBMI, userHealthConditions, userPregnancyStatus
        );
        
        // DEBUG: Log nutrition dashboard results
        Log.d(TAG, "Nutrition Dashboard Results:");
        Log.d(TAG, "  Suggested Calories: " + nutritionDashboard.getSuggestedCalories());
        Log.d(TAG, "  Suggested Protein: " + nutritionDashboard.getSuggestedProtein());
        Log.d(TAG, "  Suggested Carbs: " + nutritionDashboard.getSuggestedCarbs());
        Log.d(TAG, "  Suggested Fat: " + nutritionDashboard.getSuggestedFat());
        
        StringBuilder prompt = new StringBuilder();
        prompt.append("You are a PROFESSIONAL NUTRITIONIST and EXPERT CHEF specializing in Filipino cuisine and malnutrition recovery. ");
        prompt.append("You understand what this person needs based on their comprehensive nutritional assessment and screening data. ");
        prompt.append("Generate EXACTLY 8 food dishes for EACH of the 4 meal categories for comprehensive daily nutrition:\n\n");
        
        prompt.append("COMPREHENSIVE USER NUTRITIONAL PROFILE:\n");
        prompt.append("Age: ").append(userAge != null ? userAge : "25").append("\n");
        prompt.append("Sex: ").append(userSex != null ? userSex : "Not specified").append("\n");
        prompt.append("BMI: ").append(userBMI != null ? userBMI : "22.5").append("\n");
        prompt.append("Height: ").append(userHeight != null ? userHeight + " cm" : "Not specified").append("\n");
        prompt.append("Weight: ").append(userWeight != null ? userWeight + " kg" : "Not specified").append("\n");
        prompt.append("Health Conditions: ").append(userHealthConditions != null ? userHealthConditions : "None").append("\n");
        prompt.append("Activity Level: ").append(userActivityLevel != null ? userActivityLevel : "Moderate").append("\n");
        prompt.append("Budget Level: ").append(userBudgetLevel != null ? userBudgetLevel : "Low").append("\n");
        prompt.append("Dietary Restrictions: ").append(userDietaryRestrictions != null && !userDietaryRestrictions.isEmpty() ? userDietaryRestrictions : "None").append("\n");
        prompt.append("Allergies: ").append(userAllergies != null && !userAllergies.isEmpty() ? userAllergies : "None").append("\n");
        prompt.append("Diet Preferences: ").append(userDietPrefs != null && !userDietPrefs.isEmpty() ? userDietPrefs : "None").append("\n");
        prompt.append("Foods to Avoid: ").append(userAvoidFoods != null && !userAvoidFoods.isEmpty() ? userAvoidFoods : "None").append("\n");
        prompt.append("Risk Score: ").append(userRiskScore != null ? userRiskScore + "/10" : "Not assessed").append("\n");
        prompt.append("Location: ").append(userBarangay != null ? userBarangay : "Not specified").append("\n");
        prompt.append("Income Level: ").append(userIncome != null ? userIncome : "Not specified").append("\n");
        prompt.append("Pregnancy Status: ").append(userPregnancyStatus != null ? userPregnancyStatus : "Not Applicable").append("\n\n");
        
        // Add comprehensive screening data
        if (screeningAnswers != null && !screeningAnswers.isEmpty()) {
            prompt.append("DETAILED NUTRITIONAL SCREENING DATA:\n");
            prompt.append(screeningAnswers).append("\n\n");
        }
        
        prompt.append("PERSONALIZED NUTRITION TARGETS:\n");
        prompt.append("Daily Calorie Target: ").append(nutritionDashboard.getSuggestedCalories()).append(" kcal\n");
        prompt.append("Protein Target: ").append(nutritionDashboard.getSuggestedProtein()).append("g\n");
        prompt.append("Carbohydrate Target: ").append(nutritionDashboard.getSuggestedCarbs()).append("g\n");
        prompt.append("Fat Target: ").append(nutritionDashboard.getSuggestedFat()).append("g\n");
        prompt.append("Fiber Target: ").append(nutritionDashboard.getSuggestedFiber()).append("g\n");
        prompt.append("Sodium Limit: ").append(nutritionDashboard.getSuggestedSodium()).append("mg\n");
        prompt.append("Sugar Limit: ").append(nutritionDashboard.getSuggestedSugar()).append("g\n\n");
        
        prompt.append("MEAL CATEGORIES AND NUTRITIONAL REQUIREMENTS:\n\n");
        
        // Get meal distribution from nutrition dashboard
        Map<String, Integer> mealDistribution = nutritionDashboard.getMealDistribution();
        int breakfastCalories = mealDistribution.get("breakfast");
        int lunchCalories = mealDistribution.get("lunch");
        int dinnerCalories = mealDistribution.get("dinner");
        int snackCalories = mealDistribution.get("snacks");
        
        // Add age-specific considerations
        int age = parseAge(userAge);
        String ageGroup = getAgeGroup(age);
        prompt.append("AGE-SPECIFIC CONSIDERATIONS:\n");
        prompt.append("Age Group: ").append(ageGroup).append("\n");
        prompt.append(getAgeSpecificGuidelines(age)).append("\n\n");
        
        prompt.append("1. BREAKFAST (8 dishes):\n");
        prompt.append(getAgeSpecificBreakfastGuidelines(age, breakfastCalories)).append("\n");
        
        prompt.append("2. LUNCH (8 dishes):\n");
        prompt.append(getAgeSpecificLunchGuidelines(age, lunchCalories)).append("\n");
        
        prompt.append("3. DINNER (8 dishes):\n");
        prompt.append(getAgeSpecificDinnerGuidelines(age, dinnerCalories)).append("\n");
        
        prompt.append("4. SNACKS (8 dishes):\n");
        prompt.append(getAgeSpecificSnackGuidelines(age, snackCalories)).append("\n");
        
        prompt.append("NUTRITION REQUIREMENTS:\n");
        prompt.append("1. All nutritional information MUST be for 1 serving only\n");
        prompt.append("2. Use accurate, realistic nutrition data\n");
        prompt.append("3. Calories should be between 150-800 per serving\n");
        prompt.append("4. Protein should be between 5-40g per serving\n");
        prompt.append("5. Fat should be between 2-30g per serving\n");
        prompt.append("6. Carbs should be between 10-100g per serving\n");
        prompt.append("7. Ensure total calories = (protein × 4) + (fat × 9) + (carbs × 4) ± 10%\n");
        prompt.append("8. Use realistic serving sizes\n\n");
        
        prompt.append("DESCRIPTION REQUIREMENTS:\n");
        prompt.append("1. Write simple, appetizing descriptions\n");
        prompt.append("2. Focus on what makes the dish appealing and nutritious\n");
        prompt.append("3. Keep descriptions 1-2 sentences long\n");
        prompt.append("4. Mention key nutritional benefits\n\n");
        
        prompt.append("Return ONLY valid JSON with this structure:\n");
        prompt.append("{\n");
        prompt.append("  \"breakfast\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Breakfast\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"lunch\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Lunch\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"dinner\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Dinner\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"snacks\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Snacks\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ]\n");
        prompt.append("}");
        
        String finalPrompt = prompt.toString();
        
        // DEBUG: Log the final prompt (first 2000 characters)
        Log.d(TAG, "=== COMPREHENSIVE FINAL PROMPT (first 2000 chars) ===");
        Log.d(TAG, finalPrompt.substring(0, Math.min(2000, finalPrompt.length())));
        Log.d(TAG, "=== END OF COMPREHENSIVE PROMPT PREVIEW ===");
        
        return finalPrompt;
    }
    
    public static String buildMainFoodPrompt(String userAge, String userSex, String userBMI, 
                                           String userHealthConditions, String userBudgetLevel,
                                           String userAllergies, String userDietPrefs, 
                                           String userPregnancyStatus) {
        
        // DEBUG: Log the input parameters
        Log.d(TAG, "=== BUILDING FOOD PROMPT ===");
        Log.d(TAG, "Input userAge: " + userAge);
        Log.d(TAG, "Input userSex: " + userSex);
        Log.d(TAG, "Input userBMI: " + userBMI);
        Log.d(TAG, "Input userHealthConditions: " + userHealthConditions);
        Log.d(TAG, "Input userBudgetLevel: " + userBudgetLevel);
        Log.d(TAG, "Input userAllergies: " + userAllergies);
        Log.d(TAG, "Input userDietPrefs: " + userDietPrefs);
        Log.d(TAG, "Input userPregnancyStatus: " + userPregnancyStatus);
        
        // Create nutrition dashboard to get personalized targets
        DailyNutritionDashboard nutritionDashboard = new DailyNutritionDashboard(
            userAge, userSex, userBMI, userHealthConditions, userPregnancyStatus
        );
        
        // DEBUG: Log nutrition dashboard results
        Log.d(TAG, "Nutrition Dashboard Results:");
        Log.d(TAG, "  Suggested Calories: " + nutritionDashboard.getSuggestedCalories());
        Log.d(TAG, "  Suggested Protein: " + nutritionDashboard.getSuggestedProtein());
        Log.d(TAG, "  Suggested Carbs: " + nutritionDashboard.getSuggestedCarbs());
        Log.d(TAG, "  Suggested Fat: " + nutritionDashboard.getSuggestedFat());
        
        StringBuilder prompt = new StringBuilder();
        prompt.append("You are a PROFESSIONAL NUTRITIONIST and EXPERT CHEF specializing in Filipino cuisine and malnutrition recovery. ");
        prompt.append("You understand what this person needs based on their nutritional assessment. ");
        prompt.append("Generate EXACTLY 8 food dishes for EACH of the 4 meal categories for comprehensive daily nutrition:\n\n");
        
        prompt.append("USER NUTRITIONAL PROFILE:\n");
        prompt.append("Age: ").append(userAge != null ? userAge : "25").append("\n");
        prompt.append("Sex: ").append(userSex != null ? userSex : "Not specified").append("\n");
        prompt.append("BMI: ").append(userBMI != null ? userBMI : "22.5").append("\n");
        prompt.append("Health Conditions: ").append(userHealthConditions != null ? userHealthConditions : "None").append("\n");
        prompt.append("Budget Level: ").append(userBudgetLevel != null ? userBudgetLevel : "Low").append("\n");
        prompt.append("Allergies: ").append(userAllergies != null && !userAllergies.isEmpty() ? userAllergies : "None").append("\n");
        prompt.append("Diet Preferences: ").append(userDietPrefs != null && !userDietPrefs.isEmpty() ? userDietPrefs : "None").append("\n");
        prompt.append("Pregnancy Status: ").append(userPregnancyStatus != null ? userPregnancyStatus : "Not Applicable").append("\n\n");
        
        prompt.append("PERSONALIZED NUTRITION TARGETS:\n");
        prompt.append("Daily Calorie Target: ").append(nutritionDashboard.getSuggestedCalories()).append(" kcal\n");
        prompt.append("Protein Target: ").append(nutritionDashboard.getSuggestedProtein()).append("g\n");
        prompt.append("Carbohydrate Target: ").append(nutritionDashboard.getSuggestedCarbs()).append("g\n");
        prompt.append("Fat Target: ").append(nutritionDashboard.getSuggestedFat()).append("g\n");
        prompt.append("Fiber Target: ").append(nutritionDashboard.getSuggestedFiber()).append("g\n");
        prompt.append("Sodium Limit: ").append(nutritionDashboard.getSuggestedSodium()).append("mg\n");
        prompt.append("Sugar Limit: ").append(nutritionDashboard.getSuggestedSugar()).append("g\n\n");
        
        prompt.append("MEAL CATEGORIES AND NUTRITIONAL REQUIREMENTS:\n\n");
        
        // Get meal distribution from nutrition dashboard
        Map<String, Integer> mealDistribution = nutritionDashboard.getMealDistribution();
        int breakfastCalories = mealDistribution.get("breakfast");
        int lunchCalories = mealDistribution.get("lunch");
        int dinnerCalories = mealDistribution.get("dinner");
        int snackCalories = mealDistribution.get("snacks");
        
        // Add age-specific considerations
        int age = parseAge(userAge);
        String ageGroup = getAgeGroup(age);
        prompt.append("AGE-SPECIFIC CONSIDERATIONS:\n");
        prompt.append("Age Group: ").append(ageGroup).append("\n");
        prompt.append(getAgeSpecificGuidelines(age)).append("\n\n");
        
        prompt.append("1. BREAKFAST (8 dishes):\n");
        prompt.append(getAgeSpecificBreakfastGuidelines(age, breakfastCalories)).append("\n");
        
        prompt.append("2. LUNCH (8 dishes):\n");
        prompt.append(getAgeSpecificLunchGuidelines(age, lunchCalories)).append("\n");
        
        prompt.append("3. DINNER (8 dishes):\n");
        prompt.append(getAgeSpecificDinnerGuidelines(age, dinnerCalories)).append("\n");
        
        prompt.append("4. SNACKS (8 dishes):\n");
        prompt.append(getAgeSpecificSnackGuidelines(age, snackCalories)).append("\n");
        
        prompt.append("NUTRITION REQUIREMENTS:\n");
        prompt.append("1. All nutritional information MUST be for 1 serving only\n");
        prompt.append("2. Use accurate, realistic nutrition data\n");
        prompt.append("3. Calories should be between 150-800 per serving\n");
        prompt.append("4. Protein should be between 5-40g per serving\n");
        prompt.append("5. Fat should be between 2-30g per serving\n");
        prompt.append("6. Carbs should be between 10-100g per serving\n");
        prompt.append("7. Ensure total calories = (protein × 4) + (fat × 9) + (carbs × 4) ± 10%\n");
        prompt.append("8. Use realistic serving sizes\n\n");
        
        prompt.append("DESCRIPTION REQUIREMENTS:\n");
        prompt.append("1. Write simple, appetizing descriptions\n");
        prompt.append("2. Focus on what makes the dish appealing and nutritious\n");
        prompt.append("3. Keep descriptions 1-2 sentences long\n");
        prompt.append("4. Mention key nutritional benefits\n\n");
        
        prompt.append("Return ONLY valid JSON with this structure:\n");
        prompt.append("{\n");
        prompt.append("  \"breakfast\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Breakfast\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"lunch\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Lunch\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"dinner\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Dinner\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"snacks\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Snacks\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ]\n");
        prompt.append("}");
        
        String finalPrompt = prompt.toString();
        
        // DEBUG: Log the final prompt (first 2000 characters)
        Log.d(TAG, "=== FINAL PROMPT (first 2000 chars) ===");
        Log.d(TAG, finalPrompt.substring(0, Math.min(2000, finalPrompt.length())));
        Log.d(TAG, "=== END OF PROMPT PREVIEW ===");
        
        // DEBUG: Log age-specific sections
        if (finalPrompt.contains("INFANT NUTRITION")) {
            Log.d(TAG, "✅ PROMPT CONTAINS INFANT NUTRITION GUIDELINES");
        }
        if (finalPrompt.contains("TODDLER NUTRITION")) {
            Log.d(TAG, "✅ PROMPT CONTAINS TODDLER NUTRITION GUIDELINES");
        }
        if (finalPrompt.contains("ADULT NUTRITION")) {
            Log.d(TAG, "✅ PROMPT CONTAINS ADULT NUTRITION GUIDELINES");
        }
        if (finalPrompt.contains("Rice porridge (lugaw)")) {
            Log.d(TAG, "✅ PROMPT CONTAINS INFANT FOOD EXAMPLES");
        }
        if (finalPrompt.contains("Tapsilog")) {
            Log.d(TAG, "✅ PROMPT CONTAINS ADULT FOOD EXAMPLES");
        }
        
        return finalPrompt;
    }
    
    public static Map<String, List<FoodRecommendation>> callGeminiForMainFoods(String prompt) {
        // Try optimized Gemini API first
        Map<String, List<FoodRecommendation>> result = OptimizedGeminiService.callGeminiWithRetry(prompt);
        if (result != null && !result.isEmpty()) {
            Log.d(TAG, "Optimized Gemini API successful");
            return result;
        }
        
        // If optimized Gemini fails, try original Gemini API as fallback
        Log.w(TAG, "Optimized Gemini failed, trying original Gemini API");
        result = callGeminiAPIWithTimeout(prompt);
        if (result != null) {
            return result;
        }
        
        // If Gemini fails, try Groq API as fallback
        Log.w(TAG, "Gemini API failed, trying Groq API as fallback");
        result = callGroqAPIWithTimeout(prompt);
        if (result != null) {
            return result;
        }
        
        // If all APIs fail, return null to trigger fallback foods
        Log.e(TAG, "All APIs failed, will use fallback foods");
        return null;
    }
    
    private static Map<String, List<FoodRecommendation>> callGeminiAPIWithTimeout(String prompt) {
        try {
            // Create JSON request
            JSONObject requestBody = new JSONObject();
            JSONArray contents = new JSONArray();
            JSONObject content = new JSONObject();
            JSONArray parts = new JSONArray();
            JSONObject part = new JSONObject();
            part.put("text", prompt);
            parts.put(part);
            content.put("parts", parts);
            contents.put(content);
            requestBody.put("contents", contents);
            
            // Create OkHttpClient with extended timeout
            OkHttpClient client = new OkHttpClient.Builder()
                .connectTimeout(ApiConfig.CONNECT_TIMEOUT, TimeUnit.SECONDS)
                .readTimeout(ApiConfig.READ_TIMEOUT, TimeUnit.SECONDS)
                .writeTimeout(ApiConfig.WRITE_TIMEOUT, TimeUnit.SECONDS)
                .build();
            
            RequestBody body = RequestBody.create(
                requestBody.toString(), 
                okhttp3.MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                .url(GEMINI_API_URL)
                .post(body)
                .build();
                
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Gemini main foods response: " + responseText);
                    
                    return parseMainFoodsResponse(responseText);
                } else {
                    Log.e(TAG, "Gemini API error: " + response.code() + " - " + response.message());
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error calling Gemini for main foods: " + e.getMessage());
        }
        
        return null;
    }
    
    public static Map<String, List<FoodRecommendation>> callGroqAPIWithTimeout(String prompt) {
        try {
            // Create JSON request for Groq API
            JSONObject requestBody = new JSONObject();
            requestBody.put("model", GROQ_MODEL);
            requestBody.put("max_tokens", 4000);
            requestBody.put("temperature", 0.7);
            
            JSONArray messages = new JSONArray();
            JSONObject message = new JSONObject();
            message.put("role", "user");
            message.put("content", prompt);
            messages.put(message);
            requestBody.put("messages", messages);
            
            // Create OkHttpClient with timeout
            OkHttpClient client = new OkHttpClient.Builder()
                .connectTimeout(ApiConfig.CONNECT_TIMEOUT, TimeUnit.SECONDS)
                .readTimeout(ApiConfig.READ_TIMEOUT, TimeUnit.SECONDS)
                .writeTimeout(ApiConfig.WRITE_TIMEOUT, TimeUnit.SECONDS)
                .build();
            
            RequestBody body = RequestBody.create(
                requestBody.toString(), 
                okhttp3.MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                .url(GROQ_API_URL)
                .post(body)
                .addHeader("Authorization", "Bearer " + GROQ_API_KEY)
                .addHeader("Content-Type", "application/json")
                .build();
                
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Groq main foods response: " + responseText);
                    
                    return parseGroqMainFoodsResponse(responseText);
                } else {
                    Log.e(TAG, "Groq API error: " + response.code() + " - " + response.message());
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error calling Groq for main foods: " + e.getMessage());
        }
        
        return null;
    }
    
    private static Map<String, List<FoodRecommendation>> parseGroqMainFoodsResponse(String responseText) {
        Map<String, List<FoodRecommendation>> result = new HashMap<>();
        
        try {
            // Parse the Groq response structure
            JSONObject groqResponse = new JSONObject(responseText);
            JSONArray choices = groqResponse.getJSONArray("choices");
            
            if (choices.length() > 0) {
                JSONObject choice = choices.getJSONObject(0);
                JSONObject message = choice.getJSONObject("message");
                String content = message.getString("content");
                
                Log.d(TAG, "Extracted Groq main foods text: " + content);
                
                // Extract JSON object from the text content
                // Remove markdown code blocks if present
                String cleanedContent = content.replaceAll("```json\\s*", "").replaceAll("```\\s*", "");
                
                int objectStart = cleanedContent.indexOf("{");
                int objectEnd = cleanedContent.lastIndexOf("}") + 1;
                
                if (objectStart >= 0 && objectEnd > objectStart) {
                    String jsonObjectString = cleanedContent.substring(objectStart, objectEnd);
                    Log.d(TAG, "Extracted Groq main foods JSON: " + jsonObjectString);
                    
                    JSONObject mainFoodsJson = new JSONObject(jsonObjectString);
                    
                    // Debug: Log all available keys in the JSON
                    Log.d(TAG, "Groq Available JSON keys: " + mainFoodsJson.keys().toString());
                    
                    // Debug: Check if keys exist
                    Log.d(TAG, "Groq Has breakfast key: " + mainFoodsJson.has("breakfast"));
                    Log.d(TAG, "Groq Has lunch key: " + mainFoodsJson.has("lunch"));
                    Log.d(TAG, "Groq Has dinner key: " + mainFoodsJson.has("dinner"));
                    Log.d(TAG, "Groq Has snacks key: " + mainFoodsJson.has("snacks"));
                    
                    // Parse each category
                    JSONArray breakfastArray = mainFoodsJson.optJSONArray("breakfast");
                    JSONArray lunchArray = mainFoodsJson.optJSONArray("lunch");
                    JSONArray dinnerArray = mainFoodsJson.optJSONArray("dinner");
                    JSONArray snacksArray = mainFoodsJson.optJSONArray("snacks");
                    
                    Log.d(TAG, "Groq JSON Arrays - Breakfast: " + (breakfastArray != null ? breakfastArray.length() : "null") + 
                          ", Lunch: " + (lunchArray != null ? lunchArray.length() : "null") + 
                          ", Dinner: " + (dinnerArray != null ? dinnerArray.length() : "null") + 
                          ", Snacks: " + (snacksArray != null ? snacksArray.length() : "null"));
                    
                    result.put("breakfast", parseFoodArray(breakfastArray));
                    result.put("lunch", parseFoodArray(lunchArray));
                    result.put("dinner", parseFoodArray(dinnerArray));
                    result.put("snacks", parseFoodArray(snacksArray));
                    
                    Log.d(TAG, "Successfully parsed Groq main foods: " + result.size() + " categories");
                    return result;
                }
            }
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing Groq main foods JSON: " + e.getMessage());
        }
        
        return null;
    }
    
    public static Map<String, List<FoodRecommendation>> parseMainFoodsResponse(String responseText) {
        Map<String, List<FoodRecommendation>> result = new HashMap<>();
        
        try {
            // Parse the Gemini response structure
            JSONObject geminiResponse = new JSONObject(responseText);
            JSONArray candidates = geminiResponse.getJSONArray("candidates");
            
            if (candidates.length() > 0) {
                JSONObject candidate = candidates.getJSONObject(0);
                JSONObject content = candidate.getJSONObject("content");
                JSONArray parts = content.getJSONArray("parts");
                
                for (int i = 0; i < parts.length(); i++) {
                    JSONObject part = parts.getJSONObject(i);
                    if (part.has("text")) {
                        String textContent = part.getString("text");
                        Log.d(TAG, "Extracted main foods text: " + textContent);
                        
                        // Extract JSON object from the text content
                        // Remove markdown code blocks if present
                        String cleanedText = textContent.replaceAll("```json\\s*", "").replaceAll("```\\s*", "");
                        
                        int objectStart = cleanedText.indexOf("{");
                        int objectEnd = cleanedText.lastIndexOf("}") + 1;
                        
                        if (objectStart >= 0 && objectEnd > objectStart) {
                            String jsonObjectString = cleanedText.substring(objectStart, objectEnd);
                            Log.d(TAG, "Extracted main foods JSON: " + jsonObjectString);
                            
                            JSONObject mainFoodsJson = new JSONObject(jsonObjectString);
                            
                            // Debug: Log all available keys in the JSON
                            Log.d(TAG, "Available JSON keys: " + mainFoodsJson.keys().toString());
                            
                            // Debug: Check if keys exist
                            Log.d(TAG, "Has breakfast key: " + mainFoodsJson.has("breakfast"));
                            Log.d(TAG, "Has lunch key: " + mainFoodsJson.has("lunch"));
                            Log.d(TAG, "Has dinner key: " + mainFoodsJson.has("dinner"));
                            Log.d(TAG, "Has snacks key: " + mainFoodsJson.has("snacks"));
                            
                            // Parse each category
                            JSONArray breakfastArray = mainFoodsJson.optJSONArray("breakfast");
                            JSONArray lunchArray = mainFoodsJson.optJSONArray("lunch");
                            JSONArray dinnerArray = mainFoodsJson.optJSONArray("dinner");
                            JSONArray snacksArray = mainFoodsJson.optJSONArray("snacks");
                            
                            Log.d(TAG, "JSON Arrays - Breakfast: " + (breakfastArray != null ? breakfastArray.length() : "null") + 
                                  ", Lunch: " + (lunchArray != null ? lunchArray.length() : "null") + 
                                  ", Dinner: " + (dinnerArray != null ? dinnerArray.length() : "null") + 
                                  ", Snacks: " + (snacksArray != null ? snacksArray.length() : "null"));
                            
                            result.put("breakfast", parseFoodArray(breakfastArray));
                            result.put("lunch", parseFoodArray(lunchArray));
                            result.put("dinner", parseFoodArray(dinnerArray));
                            result.put("snacks", parseFoodArray(snacksArray));
                            
                            Log.d(TAG, "Successfully parsed main foods: " + result.size() + " categories");
                            return result;
                        }
                    }
                }
            }
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing main foods JSON: " + e.getMessage());
        }
        
        return null;
    }
    
    public static List<FoodRecommendation> parseFoodArray(JSONArray foodArray) {
        List<FoodRecommendation> foods = new ArrayList<>();
        
        Log.d(TAG, "parseFoodArray called with array: " + (foodArray != null ? foodArray.length() + " items" : "null"));
        
        if (foodArray != null) {
            for (int i = 0; i < foodArray.length(); i++) {
                try {
                    JSONObject foodJson = foodArray.getJSONObject(i);
                    
                    String foodName = foodJson.optString("food_name", "");
                    int calories = foodJson.optInt("calories", 0);
                    double protein = foodJson.optDouble("protein_g", 0.0);
                    double fat = foodJson.optDouble("fat_g", 0.0);
                    double carbs = foodJson.optDouble("carbs_g", 0.0);
                    String servingSize = foodJson.optString("serving_size", "1 serving");
                    String dietType = foodJson.optString("diet_type", "");
                    String description = foodJson.optString("description", "");
                    
                    if (!foodName.trim().isEmpty()) {
                        FoodRecommendation food = new FoodRecommendation(
                            foodName, calories, protein, fat, carbs, servingSize, dietType, description
                        );
                        foods.add(food);
                        Log.d(TAG, "Added food: " + foodName);
                    }
                } catch (JSONException e) {
                    Log.w(TAG, "Error parsing food at index " + i + ": " + e.getMessage());
                }
            }
        }
        
        return foods;
    }
    
    /**
     * Parse age from string
     */
    private static int parseAge(String ageStr) {
        try {
            if (ageStr != null && !ageStr.isEmpty()) {
                return Integer.parseInt(ageStr.replaceAll("[^0-9]", ""));
            }
        } catch (NumberFormatException e) {
            Log.w(TAG, "Could not parse age: " + ageStr);
        }
        return 25; // Default age
    }
    
    /**
     * Get age group description
     */
    private static String getAgeGroup(int age) {
        if (age < 1) return "Infant (0-12 months)";
        else if (age < 3) return "Toddler (1-3 years)";
        else if (age < 6) return "Preschooler (3-6 years)";
        else if (age < 12) return "School-age Child (6-12 years)";
        else if (age < 18) return "Adolescent (12-18 years)";
        else return "Adult (18+ years)";
    }
    
    /**
     * Get age-specific nutritional guidelines
     */
    private static String getAgeSpecificGuidelines(int age) {
        if (age < 1) {
            return "INFANT NUTRITION (0-12 months):\n" +
                   "- CRITICAL: Only recommend soft, pureed, or mashed foods\n" +
                   "- NO hard foods, NO choking hazards, NO spicy foods\n" +
                   "- Focus on: breast milk/formula, rice porridge (lugaw), mashed vegetables, soft fruits\n" +
                   "- Avoid: nuts, seeds, hard vegetables, raw foods, honey, cow's milk\n" +
                   "- Small, frequent feedings (6-8 times per day)\n" +
                   "- Essential for brain development and growth";
        } else if (age < 3) {
            return "TODDLER NUTRITION (1-3 years):\n" +
                   "- Soft, easy-to-chew foods in small pieces\n" +
                   "- Focus on: rice, soft vegetables, lean proteins, fruits, whole milk\n" +
                   "- Avoid: hard foods, spicy foods, large chunks, choking hazards\n" +
                   "- 5-6 small meals per day\n" +
                   "- Essential for growth and development";
        } else if (age < 6) {
            return "PRESCHOOLER NUTRITION (3-6 years):\n" +
                   "- Balanced meals with variety and moderate portions\n" +
                   "- Focus on: whole grains, vegetables, lean proteins, fruits, dairy\n" +
                   "- Avoid: excessive salt, sugar, processed foods\n" +
                   "- 4-5 meals per day\n" +
                   "- Important for learning and physical development";
        } else if (age < 12) {
            return "SCHOOL-AGE NUTRITION (6-12 years):\n" +
                   "- Nutrient-dense foods for growth and learning\n" +
                   "- Focus on: protein, complex carbs, healthy fats, vitamins, minerals\n" +
                   "- Avoid: excessive junk food, sugary drinks\n" +
                   "- 4-5 meals per day\n" +
                   "- Essential for academic performance and physical growth";
        } else if (age < 18) {
            return "ADOLESCENT NUTRITION (12-18 years):\n" +
                   "- Foods supporting growth and development\n" +
                   "- Focus on: protein, calcium, iron, vitamins, minerals\n" +
                   "- Avoid: excessive processed foods, sugary drinks\n" +
                   "- 3-4 meals per day\n" +
                   "- Critical for puberty and final growth spurt";
        } else {
            return "ADULT NUTRITION (18+ years):\n" +
                   "- Balanced nutrition with variety\n" +
                   "- Focus on: lean proteins, whole grains, vegetables, fruits\n" +
                   "- Consider health conditions and lifestyle\n" +
                   "- 3-4 meals per day\n" +
                   "- Maintain health and prevent chronic diseases";
        }
    }
    
    /**
     * Get age-specific breakfast guidelines
     */
    private static String getAgeSpecificBreakfastGuidelines(int age, int calories) {
        if (age < 1) {
            return "- INFANT BREAKFAST: Soft, pureed foods only\n" +
                   "- Include: Rice porridge (lugaw), mashed banana, soft scrambled egg\n" +
                   "- Avoid: Hard foods, spicy foods, large pieces\n" +
                   "- Target: " + calories + " kcal (15% of daily intake)\n" +
                   "- Focus on: Easy digestion, essential nutrients for growth";
        } else if (age < 3) {
            return "- TODDLER BREAKFAST: Soft, easy-to-chew morning foods\n" +
                   "- Include: Soft rice, mashed vegetables, soft fruits, whole milk\n" +
                   "- Avoid: Hard foods, spicy foods, large chunks\n" +
                   "- Target: " + calories + " kcal (20% of daily intake)\n" +
                   "- Focus on: Energy for active play, brain development";
        } else if (age < 6) {
            return "- PRESCHOOLER BREAKFAST: Balanced morning meals\n" +
                   "- Include: Rice, soft vegetables, lean proteins, fruits, dairy\n" +
                   "- Avoid: Excessive salt, sugar, processed foods\n" +
                   "- Target: " + calories + " kcal (20% of daily intake)\n" +
                   "- Focus on: Learning readiness, physical development";
        } else if (age < 12) {
            return "- SCHOOL-AGE BREAKFAST: Energy-boosting morning meals\n" +
                   "- Include: Rice-based dishes, lean proteins, fruits, dairy\n" +
                   "- Avoid: Excessive junk food, sugary drinks\n" +
                   "- Target: " + calories + " kcal (25% of daily intake)\n" +
                   "- Focus on: Academic performance, physical growth";
        } else if (age < 18) {
            return "- ADOLESCENT BREAKFAST: Nutrient-dense morning meals\n" +
                   "- Include: Rice-based dishes, lean proteins, fruits, dairy\n" +
                   "- Avoid: Excessive processed foods, sugary drinks\n" +
                   "- Target: " + calories + " kcal (25% of daily intake)\n" +
                   "- Focus on: Growth spurt support, academic performance";
        } else {
            return "- ADULT BREAKFAST: Energy-boosting, nutrient-dense morning meals\n" +
                   "- Include: Rice-based dishes (Tapsilog, Longsilog, Bangsilog), Pancit, Lugaw, Champorado\n" +
                   "- Emphasize: Complex carbohydrates, protein, essential vitamins\n" +
                   "- Target: " + calories + " kcal (25% of daily intake)\n" +
                   "- Focus on: Traditional Filipino breakfast staples with modern healthy twists";
        }
    }
    
    /**
     * Get age-specific lunch guidelines
     */
    private static String getAgeSpecificLunchGuidelines(int age, int calories) {
        if (age < 1) {
            return "- INFANT LUNCH: Soft, pureed midday foods\n" +
                   "- Include: Mashed vegetables, soft proteins, rice porridge\n" +
                   "- Avoid: Hard foods, spicy foods, large pieces\n" +
                   "- Target: " + calories + " kcal (15% of daily intake)\n" +
                   "- Focus on: Easy digestion, essential nutrients";
        } else if (age < 3) {
            return "- TODDLER LUNCH: Soft, balanced midday meals\n" +
                   "- Include: Soft rice, mashed vegetables, lean proteins, fruits\n" +
                   "- Avoid: Hard foods, spicy foods, large chunks\n" +
                   "- Target: " + calories + " kcal (25% of daily intake)\n" +
                   "- Focus on: Growth support, energy for play";
        } else if (age < 6) {
            return "- PRESCHOOLER LUNCH: Balanced midday meals\n" +
                   "- Include: Rice, vegetables, lean proteins, fruits, dairy\n" +
                   "- Avoid: Excessive salt, sugar, processed foods\n" +
                   "- Target: " + calories + " kcal (25% of daily intake)\n" +
                   "- Focus on: Learning support, physical development";
        } else if (age < 12) {
            return "- SCHOOL-AGE LUNCH: Substantial midday meals\n" +
                   "- Include: Rice-based dishes, lean proteins, vegetables, fruits\n" +
                   "- Avoid: Excessive junk food, sugary drinks\n" +
                   "- Target: " + calories + " kcal (30% of daily intake)\n" +
                   "- Focus on: Academic performance, physical growth";
        } else if (age < 18) {
            return "- ADOLESCENT LUNCH: Balanced, substantial midday meals\n" +
                   "- Include: Rice-based dishes, lean proteins, vegetables, fruits\n" +
                   "- Avoid: Excessive processed foods, sugary drinks\n" +
                   "- Target: " + calories + " kcal (30% of daily intake)\n" +
                   "- Focus on: Growth spurt support, academic performance";
        } else {
            return "- ADULT LUNCH: Balanced, substantial midday meals\n" +
                   "- Include: Adobo variations, Sinigang, Kare-kare, Tinola, Nilaga\n" +
                   "- Emphasize: Complete proteins, vegetables, healthy fats\n" +
                   "- Target: " + calories + " kcal (35% of daily intake)\n" +
                   "- Focus on: Traditional Filipino cooking methods with nutritional optimization";
        }
    }
    
    /**
     * Get age-specific dinner guidelines
     */
    private static String getAgeSpecificDinnerGuidelines(int age, int calories) {
        if (age < 1) {
            return "- INFANT DINNER: Soft, pureed evening foods\n" +
                   "- Include: Mashed vegetables, soft proteins, rice porridge\n" +
                   "- Avoid: Hard foods, spicy foods, large pieces\n" +
                   "- Target: " + calories + " kcal (15% of daily intake)\n" +
                   "- Focus on: Easy digestion, sleep preparation";
        } else if (age < 3) {
            return "- TODDLER DINNER: Soft, balanced evening meals\n" +
                   "- Include: Soft rice, mashed vegetables, lean proteins, fruits\n" +
                   "- Avoid: Hard foods, spicy foods, large chunks\n" +
                   "- Target: " + calories + " kcal (25% of daily intake)\n" +
                   "- Focus on: Growth support, sleep preparation";
        } else if (age < 6) {
            return "- PRESCHOOLER DINNER: Balanced evening meals\n" +
                   "- Include: Rice, vegetables, lean proteins, fruits, dairy\n" +
                   "- Avoid: Excessive salt, sugar, processed foods\n" +
                   "- Target: " + calories + " kcal (25% of daily intake)\n" +
                   "- Focus on: Learning support, sleep preparation";
        } else if (age < 12) {
            return "- SCHOOL-AGE DINNER: Balanced evening meals\n" +
                   "- Include: Rice-based dishes, lean proteins, vegetables, fruits\n" +
                   "- Avoid: Excessive junk food, sugary drinks\n" +
                   "- Target: " + calories + " kcal (30% of daily intake)\n" +
                   "- Focus on: Academic performance, sleep preparation";
        } else if (age < 18) {
            return "- ADOLESCENT DINNER: Balanced evening meals\n" +
                   "- Include: Rice-based dishes, lean proteins, vegetables, fruits\n" +
                   "- Avoid: Excessive processed foods, sugary drinks\n" +
                   "- Target: " + calories + " kcal (30% of daily intake)\n" +
                   "- Focus on: Growth spurt support, sleep preparation";
        } else {
            return "- ADULT DINNER: Lighter, easily digestible evening meals\n" +
                   "- Include: Grilled fish, steamed vegetables, soup-based dishes, lean proteins\n" +
                   "- Emphasize: Vitamins, minerals, fiber, lean proteins\n" +
                   "- Target: " + calories + " kcal (30% of daily intake)\n" +
                   "- Focus on: Both Filipino and international healthy dinner options";
        }
    }
    
    /**
     * Get age-specific snack guidelines
     */
    private static String getAgeSpecificSnackGuidelines(int age, int calories) {
        if (age < 1) {
            return "- INFANT SNACKS: Soft, pureed between-meal foods\n" +
                   "- Include: Mashed fruits, soft vegetables, rice porridge\n" +
                   "- Avoid: Hard foods, spicy foods, large pieces\n" +
                   "- Target: " + calories + " kcal (55% of daily intake - multiple small meals)\n" +
                   "- Focus on: Frequent small feedings, essential nutrients";
        } else if (age < 3) {
            return "- TODDLER SNACKS: Soft, nutritious between-meal foods\n" +
                   "- Include: Soft fruits, soft vegetables, whole milk, soft crackers\n" +
                   "- Avoid: Hard foods, spicy foods, large chunks\n" +
                   "- Target: " + calories + " kcal (30% of daily intake)\n" +
                   "- Focus on: Growth support, energy for play";
        } else if (age < 6) {
            return "- PRESCHOOLER SNACKS: Nutritious between-meal foods\n" +
                   "- Include: Fruits, vegetables, dairy, whole grain crackers\n" +
                   "- Avoid: Excessive salt, sugar, processed foods\n" +
                   "- Target: " + calories + " kcal (30% of daily intake)\n" +
                   "- Focus on: Learning support, physical development";
        } else if (age < 12) {
            return "- SCHOOL-AGE SNACKS: Nutritious between-meal foods\n" +
                   "- Include: Fruits, vegetables, dairy, nuts, whole grain snacks\n" +
                   "- Avoid: Excessive junk food, sugary drinks\n" +
                   "- Target: " + calories + " kcal (15% of daily intake)\n" +
                   "- Focus on: Academic performance, physical growth";
        } else if (age < 18) {
            return "- ADOLESCENT SNACKS: Nutritious between-meal foods\n" +
                   "- Include: Fruits, vegetables, dairy, nuts, whole grain snacks\n" +
                   "- Avoid: Excessive processed foods, sugary drinks\n" +
                   "- Target: " + calories + " kcal (15% of daily intake)\n" +
                   "- Focus on: Growth spurt support, academic performance";
        } else {
            return "- ADULT SNACKS: Nutritious, energy-sustaining between-meal options\n" +
                   "- Include: Fresh fruits, nuts, yogurt, healthy Filipino snacks\n" +
                   "- Emphasize: Vitamins, minerals, healthy fats, fiber\n" +
                   "- Target: " + calories + " kcal (10% of daily intake)\n" +
                   "- Focus on: Budget-friendly, portable, and nutritious options";
        }
    }
}
