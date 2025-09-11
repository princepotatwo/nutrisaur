package com.example.nutrisaur11;

import android.util.Log;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;
import java.util.List;
import java.util.ArrayList;
import okhttp3.*;
import java.io.IOException;

/**
 * Gemini Food API for malnutrition recovery with professional nutritionist approach
 * Single API call to populate Breakfast, Lunch, Dinner, Snacks categories
 */
public class OptimizedMalnutritionFoodAPI {
    private static final String TAG = "GeminiMalnutritionAPI";
    private static final String GEMINI_API_KEY = "AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM";
    private static final String GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
    
    /**
     * Build professional nutritionist prompt for malnutrition recovery
     */
    public static String buildMalnutritionRecoveryPrompt(String userAge, String userSex, String userBMI, 
                                                        String userHealthConditions, String userBudgetLevel, 
                                                        String userAllergies, String userDietPrefs, 
                                                        String userPregnancyStatus) {
        
        // Parse age for life stage considerations
        int age = 25;
        try {
            age = Integer.parseInt(userAge != null ? userAge : "25");
        } catch (NumberFormatException e) {
            age = 25;
        }
        
        String lifeStage = determineLifeStage(age);
        
        return "You are a professional nutritionist. A patient has come to you with these details:\n\n" +
               "Patient Age: " + (userAge != null ? userAge : "25") + " years old\n" +
               "Gender: " + (userSex != null ? userSex : "Not specified") + "\n" +
               "BMI: " + (userBMI != null ? userBMI : "18.5") + "\n" +
               "Health Conditions: " + (userHealthConditions != null ? userHealthConditions : "None") + "\n" +
               "Budget Level: " + (userBudgetLevel != null ? userBudgetLevel : "Low") + "\n" +
               "Allergies: " + (userAllergies != null && !userAllergies.isEmpty() ? userAllergies : "None") + "\n" +
               "Diet Preferences: " + (userDietPrefs != null && !userDietPrefs.isEmpty() ? userDietPrefs : "None") + "\n" +
               "Pregnancy: " + (userPregnancyStatus != null ? userPregnancyStatus : "Not Applicable") + "\n\n" +
               "As a nutritionist, please:\n" +
               "1. ASSESS this patient's nutritional needs based on their profile\n" +
               "2. ANALYZE what specific nutrients they need most\n" +
               "3. RECOMMEND appropriate Filipino foods for their condition\n" +
               "4. CONSIDER their budget, allergies, and preferences\n" +
               "5. PROVIDE 8 foods for each meal category (breakfast, lunch, dinner, snacks)\n\n" +
               "IMPORTANT RULES:\n" +
               "- Use ONLY real Filipino food names (short, recognizable names)\n" +
               "- NO numbers in food names\n" +
               "- NO made-up or hallucinated foods\n" +
               "- Keep food names simple and authentic\n" +
               "- Consider their budget and health condition\n\n" +
               "AVAILABLE FILIPINO FOOD IMAGES:\n" +
               "adobo, sinigang, kare_kare, tinola, kaldereta, afritada, mechado, menudo, pancit_canton, lumpiang_shanghai, tapsilog, sisig, bicol_express, chicken_inasal, lechon, crispy_pata, dinengdeng, laing, ginataang_munggo, pinakbet, bulalo, nilagang_baboy, paksiw_na_bangus, lechon_kawali, lechon_manok, pancit_bihon, pancit_molo, pancit_sotanghon, pansit_malabon, pansit_lomi, mami, sopas, lugaw, arroz_caldo, goto_dish, taho, buko_salad, halo_halo, leche_flan, biko, sapin_sapin, ube_halaya, cassava_cake, bibingka, puto, kutsinta, turon, banana_chips, chicharon, fish_balls, kwek_kwek, tokneneng, dynamite, embutido, vigan_empanada, okoy, ukoy, tortang_talong, tortang_giniling, ginataang_saging, ginataang_mais, buko_juice, mango_shake, calamansi_juice, sago_at_gulaman, mais_con_yelo\n\n" +
               "Please provide your recommendations in this JSON format:\n" +
               "{\n" +
               "  \"breakfast\": [\n" +
               "    {\"food_name\": \"Adobo\", \"calories\": 450, \"protein_g\": 25, \"fat_g\": 18, \"carbs_g\": 35, \"serving_size\": \"1 plate\", \"diet_type\": \"Malnutrition Recovery\", \"description\": \"High-protein breakfast with beef, egg, and rice for energy and muscle building\", \"image_name\": \"adobo\"},\n" +
               "    {\"food_name\": \"Arroz Caldo\", \"calories\": 380, \"protein_g\": 20, \"fat_g\": 12, \"carbs_g\": 45, \"serving_size\": \"1 bowl\", \"diet_type\": \"Malnutrition Recovery\", \"description\": \"Nutritious rice porridge with chicken and ginger for easy digestion\", \"image_name\": \"arroz_caldo\"}\n" +
               "  ],\n" +
               "  \"lunch\": [\n" +
               "    {\"food_name\": \"Sinigang\", \"calories\": 420, \"protein_g\": 30, \"fat_g\": 15, \"carbs_g\": 25, \"serving_size\": \"1 bowl\", \"diet_type\": \"Malnutrition Recovery\", \"description\": \"Protein-rich sour soup with pork and vegetables for recovery\", \"image_name\": \"sinigang\"}\n" +
               "  ],\n" +
               "  \"dinner\": [\n" +
               "    {\"food_name\": \"Tinola\", \"calories\": 350, \"protein_g\": 25, \"fat_g\": 12, \"carbs_g\": 20, \"serving_size\": \"1 bowl\", \"diet_type\": \"Malnutrition Recovery\", \"description\": \"Light chicken soup with vegetables for easy evening digestion\", \"image_name\": \"tinola\"}\n" +
               "  ],\n" +
               "  \"snacks\": [\n" +
               "    {\"food_name\": \"Taho\", \"calories\": 200, \"protein_g\": 12, \"fat_g\": 3, \"carbs_g\": 35, \"serving_size\": \"1 cup\", \"diet_type\": \"Malnutrition Recovery\", \"description\": \"High-protein soy pudding with sago for healthy snacking\", \"image_name\": \"taho\"}\n" +
               "  ]\n" +
               "}\n\n" +
               "Remember: Use your professional judgment to recommend the best foods for this specific patient's needs. Make sure food names are real Filipino dishes and keep them short and simple.";
    }
    
    /**
     * Determine life stage based on age
     */
    private static String determineLifeStage(int age) {
        if (age < 1) return "Infant (0-12 months)";
        else if (age < 3) return "Toddler (1-3 years)";
        else if (age < 6) return "Preschool (3-6 years)";
        else if (age < 12) return "School Age (6-12 years)";
        else if (age < 18) return "Adolescent (12-18 years)";
        else if (age < 50) return "Adult (18-50 years)";
        else if (age < 65) return "Middle Age (50-65 years)";
        else return "Senior (65+ years)";
    }
    
    /**
     * Make single API call for malnutrition recovery foods
     */
    public static void loadMalnutritionRecoveryFoods(String userAge, String userSex, String userBMI, 
                                                    String userHealthConditions, String userBudgetLevel, 
                                                    String userAllergies, String userDietPrefs, 
                                                    String userPregnancyStatus,
                                                    MalnutritionFoodCallback callback) {
        Log.d(TAG, "Starting malnutrition recovery food API call");
        Log.d(TAG, "API Key: " + GEMINI_API_KEY.substring(0, 10) + "...");
        Log.d(TAG, "API URL: " + GEMINI_API_URL);
        
        new Thread(() -> {
            try {
                String prompt = buildMalnutritionRecoveryPrompt(userAge, userSex, userBMI, 
                                                              userHealthConditions, userBudgetLevel, 
                                                              userAllergies, userDietPrefs, userPregnancyStatus);
                
                // Create JSON request for Gemini API
                JSONObject requestBody = new JSONObject();
                JSONObject content = new JSONObject();
                JSONArray parts = new JSONArray();
                JSONObject part = new JSONObject();
                part.put("text", prompt);
                parts.put(part);
                content.put("parts", parts);
                JSONArray contents = new JSONArray();
                contents.put(content);
                requestBody.put("contents", contents);
                
                // Make API call with extended timeout for Gemini
                OkHttpClient client = new OkHttpClient.Builder()
                    .connectTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                    .writeTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                    .readTimeout(60, java.util.concurrent.TimeUnit.SECONDS)
                    .build();
                RequestBody body = RequestBody.create(
                    requestBody.toString(), 
                    okhttp3.MediaType.parse("application/json")
                );
                
                Request request = new Request.Builder()
                    .url(GEMINI_API_URL + "?key=" + GEMINI_API_KEY)
                    .post(body)
                    .build();
                    
                try (Response response = client.newCall(request).execute()) {
                    Log.d(TAG, "API Response Code: " + response.code());
                    if (response.isSuccessful() && response.body() != null) {
                        String responseText = response.body().string();
                        Log.d(TAG, "Malnutrition recovery API response received");
                        Log.d(TAG, "Response length: " + responseText.length());
                        Log.d(TAG, "Response preview: " + responseText.substring(0, Math.min(200, responseText.length())));
                        
                        // Parse the response
                        MalnutritionFoodResponse apiResponse = parseMalnutritionResponse(responseText);
                        Log.d(TAG, "✅ SUCCESS: Gemini API loaded malnutrition recovery foods");
                        callback.onSuccess(apiResponse);
                        
                    } else {
                        Log.e(TAG, "API call failed: " + response.code() + " - " + response.message());
                        if (response.body() != null) {
                            String errorBody = response.body().string();
                            Log.e(TAG, "Error response body: " + errorBody);
                        }
                        callback.onError("API call failed: " + response.code());
                    }
                }
            } catch (Exception e) {
                Log.e(TAG, "Error in malnutrition recovery API call: " + e.getMessage());
                callback.onError("Error: " + e.getMessage());
            }
        }).start();
    }
    
    /**
     * Parse JSON response for malnutrition recovery foods
     */
    private static MalnutritionFoodResponse parseMalnutritionResponse(String responseText) throws JSONException {
        // Parse Gemini response format
        JSONObject geminiResponse = new JSONObject(responseText);
        JSONArray candidates = geminiResponse.getJSONArray("candidates");
        JSONObject candidate = candidates.getJSONObject(0);
        JSONObject content = candidate.getJSONObject("content");
        JSONArray parts = content.getJSONArray("parts");
        JSONObject part = parts.getJSONObject(0);
        String contentText = part.getString("text");
        
        // Extract JSON from the content
        int jsonStart = contentText.indexOf("{");
        int jsonEnd = contentText.lastIndexOf("}") + 1;
        
        if (jsonStart >= 0 && jsonEnd > jsonStart) {
            String jsonString = contentText.substring(jsonStart, jsonEnd);
            JSONObject jsonResponse = new JSONObject(jsonString);
            
            MalnutritionFoodResponse apiResponse = new MalnutritionFoodResponse();
            
            // Parse each meal category
            apiResponse.breakfast = parseMealCategoryFromJSON(jsonResponse, "breakfast");
            apiResponse.lunch = parseMealCategoryFromJSON(jsonResponse, "lunch");
            apiResponse.dinner = parseMealCategoryFromJSON(jsonResponse, "dinner");
            apiResponse.snacks = parseMealCategoryFromJSON(jsonResponse, "snacks");
            
            return apiResponse;
        } else {
            throw new JSONException("Could not extract JSON from Gemini response content");
        }
    }
    
    /**
     * Parse a specific meal category from JSON
     */
    private static List<FoodRecommendation> parseMealCategoryFromJSON(JSONObject jsonResponse, String categoryKey) throws JSONException {
        List<FoodRecommendation> foodList = new ArrayList<>();
        JSONArray categoryArray = jsonResponse.getJSONArray(categoryKey);
        
        for (int i = 0; i < categoryArray.length(); i++) {
            JSONObject foodJson = categoryArray.getJSONObject(i);
            
            String foodName = foodJson.optString("food_name", "");
            int calories = foodJson.optInt("calories", 0);
            double protein = foodJson.optDouble("protein_g", 0.0);
            double fat = foodJson.optDouble("fat_g", 0.0);
            double carbs = foodJson.optDouble("carbs_g", 0.0);
            String servingSize = foodJson.optString("serving_size", "1 serving");
            String dietType = foodJson.optString("diet_type", "Malnutrition Recovery");
            String description = foodJson.optString("description", "");
            String imageName = foodJson.optString("image_name", "adobo");
            
            if (!foodName.trim().isEmpty()) {
                FoodRecommendation food = new FoodRecommendation(
                    foodName, calories, protein, fat, carbs, servingSize, dietType, description, imageName
                );
                foodList.add(food);
            }
        }
        
        return foodList;
    }
    
    /**
     * Callback interface for malnutrition food response
     */
    public interface MalnutritionFoodCallback {
        void onSuccess(MalnutritionFoodResponse response);
        void onError(String error);
    }
    
    /**
     * Response data class for malnutrition recovery foods
     */
    public static class MalnutritionFoodResponse {
        public List<FoodRecommendation> breakfast = new ArrayList<>();
        public List<FoodRecommendation> lunch = new ArrayList<>();
        public List<FoodRecommendation> dinner = new ArrayList<>();
        public List<FoodRecommendation> snacks = new ArrayList<>();
    }
}
