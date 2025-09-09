package com.example.nutrisaur11;

import android.util.Log;
import java.util.*;
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
    
    public static String buildMainFoodPrompt(String userAge, String userSex, String userBMI, 
                                           String userHealthConditions, String userBudgetLevel,
                                           String userAllergies, String userDietPrefs, 
                                           String userPregnancyStatus) {
        
        StringBuilder prompt = new StringBuilder();
        prompt.append("You are an expert nutritionist and chef specializing in Filipino cuisine. ");
        prompt.append("Generate EXACTLY 8 food dishes for EACH of the 4 categories for malnutrition recovery:\n\n");
        
        prompt.append("USER PROFILE:\n");
        prompt.append("Age: ").append(userAge != null ? userAge : "25").append("\n");
        prompt.append("Sex: ").append(userSex != null ? userSex : "Not specified").append("\n");
        prompt.append("BMI: ").append(userBMI != null ? userBMI : "22.5").append("\n");
        prompt.append("Health: ").append(userHealthConditions != null ? userHealthConditions : "None").append("\n");
        prompt.append("Budget: ").append(userBudgetLevel != null ? userBudgetLevel : "Low").append("\n");
        prompt.append("Allergies: ").append(userAllergies != null && !userAllergies.isEmpty() ? userAllergies : "None").append("\n");
        prompt.append("Diet: ").append(userDietPrefs != null && !userDietPrefs.isEmpty() ? userDietPrefs : "None").append("\n");
        prompt.append("Pregnancy: ").append(userPregnancyStatus != null ? userPregnancyStatus : "Not Applicable").append("\n\n");
        
        prompt.append("CATEGORIES AND REQUIREMENTS:\n\n");
        
        prompt.append("1. TRADITIONAL FILIPINO (8 dishes):\n");
        prompt.append("- Focus on classic Filipino dishes that are nutrient-dense\n");
        prompt.append("- Include: Adobo variations, Sinigang variations, Kare-kare, Tinola, etc.\n");
        prompt.append("- Consider traditional cooking methods (adobo, sinigang, nilaga)\n");
        prompt.append("- Include both meat and vegetable options\n\n");
        
        prompt.append("2. HEALTHY OPTIONS (8 dishes):\n");
        prompt.append("- Focus on nutrient-rich, low-calorie options\n");
        prompt.append("- Include: Grilled fish, steamed vegetables, lean proteins\n");
        prompt.append("- Emphasize vitamins, minerals, and fiber\n");
        prompt.append("- Include both Filipino and international healthy dishes\n\n");
        
        prompt.append("3. INTERNATIONAL CUISINE (8 dishes):\n");
        prompt.append("- Include popular international dishes available in Philippines\n");
        prompt.append("- Focus on: Asian (Korean, Japanese, Chinese, Thai), Western dishes\n");
        prompt.append("- Consider local availability and affordability\n");
        prompt.append("- Include both protein-rich and vegetable-rich options\n\n");
        
        prompt.append("4. BUDGET-FRIENDLY (8 dishes):\n");
        prompt.append("- Focus on affordable, nutritious options\n");
        prompt.append("- Include: Rice-based dishes, vegetable stews, simple proteins\n");
        prompt.append("- Consider cost-effective ingredients\n");
        prompt.append("- Include both traditional and modern budget options\n\n");
        
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
        prompt.append("  \"traditional\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Traditional Filipino\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"healthy\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Healthy\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"international\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"International\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ],\n");
        prompt.append("  \"budget\": [\n");
        prompt.append("    {\"food_name\": \"[DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"Budget-Friendly\", \"description\": \"[DESCRIPTION]\"},\n");
        prompt.append("    ... (8 items)\n");
        prompt.append("  ]\n");
        prompt.append("}");
        
        return prompt.toString();
    }
    
    public static Map<String, List<FoodRecommendation>> callGeminiForMainFoods(String prompt) {
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
            
            // Make API call
            OkHttpClient client = new OkHttpClient();
            RequestBody body = RequestBody.create(
                requestBody.toString(), 
                okhttp3.MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                .url("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM")
                .post(body)
                .build();
                
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Gemini main foods response: " + responseText);
                    
                    return parseMainFoodsResponse(responseText);
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error calling Gemini for main foods: " + e.getMessage());
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
                        int objectStart = textContent.indexOf("{");
                        int objectEnd = textContent.lastIndexOf("}") + 1;
                        
                        if (objectStart >= 0 && objectEnd > objectStart) {
                            String jsonObjectString = textContent.substring(objectStart, objectEnd);
                            Log.d(TAG, "Extracted main foods JSON: " + jsonObjectString);
                            
                            JSONObject mainFoodsJson = new JSONObject(jsonObjectString);
                            
                            // Parse each category
                            result.put("traditional", parseFoodArray(mainFoodsJson.optJSONArray("traditional")));
                            result.put("healthy", parseFoodArray(mainFoodsJson.optJSONArray("healthy")));
                            result.put("international", parseFoodArray(mainFoodsJson.optJSONArray("international")));
                            result.put("budget", parseFoodArray(mainFoodsJson.optJSONArray("budget")));
                            
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
}
