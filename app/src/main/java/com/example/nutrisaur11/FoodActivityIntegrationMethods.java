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
    private static final String GROK_API_URL = ApiConfig.GROK_API_URL;
    private static final String GROK_API_KEY = ApiConfig.GROK_API_KEY;
    private static final String GROK_MODEL = ApiConfig.GROK_MODEL;
    
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
        // Try Gemini API first with extended timeout
        Map<String, List<FoodRecommendation>> result = callGeminiAPIWithTimeout(prompt);
        if (result != null) {
            return result;
        }
        
        // If Gemini fails, try Grok API as fallback
        Log.w(TAG, "Gemini API failed, trying Grok API as fallback");
        result = callGrokAPIWithTimeout(prompt);
        if (result != null) {
            return result;
        }
        
        // If both APIs fail, return null to trigger fallback foods
        Log.e(TAG, "Both Gemini and Grok APIs failed, will use fallback foods");
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
    
    private static Map<String, List<FoodRecommendation>> callGrokAPIWithTimeout(String prompt) {
        try {
            // Create JSON request for Grok API
            JSONObject requestBody = new JSONObject();
            requestBody.put("model", GROK_MODEL);
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
                .url(GROK_API_URL)
                .post(body)
                .addHeader("Authorization", "Bearer " + GROK_API_KEY)
                .addHeader("Content-Type", "application/json")
                .build();
                
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Grok main foods response: " + responseText);
                    
                    return parseGrokMainFoodsResponse(responseText);
                } else {
                    Log.e(TAG, "Grok API error: " + response.code() + " - " + response.message());
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error calling Grok for main foods: " + e.getMessage());
        }
        
        return null;
    }
    
    private static Map<String, List<FoodRecommendation>> parseGrokMainFoodsResponse(String responseText) {
        Map<String, List<FoodRecommendation>> result = new HashMap<>();
        
        try {
            // Parse the Grok response structure
            JSONObject grokResponse = new JSONObject(responseText);
            JSONArray choices = grokResponse.getJSONArray("choices");
            
            if (choices.length() > 0) {
                JSONObject choice = choices.getJSONObject(0);
                JSONObject message = choice.getJSONObject("message");
                String content = message.getString("content");
                
                Log.d(TAG, "Extracted Grok main foods text: " + content);
                
                // Extract JSON object from the text content
                int objectStart = content.indexOf("{");
                int objectEnd = content.lastIndexOf("}") + 1;
                
                if (objectStart >= 0 && objectEnd > objectStart) {
                    String jsonObjectString = content.substring(objectStart, objectEnd);
                    Log.d(TAG, "Extracted Grok main foods JSON: " + jsonObjectString);
                    
                    JSONObject mainFoodsJson = new JSONObject(jsonObjectString);
                    
                    // Parse each category
                    result.put("traditional", parseFoodArray(mainFoodsJson.optJSONArray("traditional")));
                    result.put("healthy", parseFoodArray(mainFoodsJson.optJSONArray("healthy")));
                    result.put("international", parseFoodArray(mainFoodsJson.optJSONArray("international")));
                    result.put("budget", parseFoodArray(mainFoodsJson.optJSONArray("budget")));
                    
                    Log.d(TAG, "Successfully parsed Grok main foods: " + result.size() + " categories");
                    return result;
                }
            }
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing Grok main foods JSON: " + e.getMessage());
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
