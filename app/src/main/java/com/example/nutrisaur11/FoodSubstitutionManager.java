package com.example.nutrisaur11;

import android.content.Context;
import android.util.Log;
import java.util.*;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import okhttp3.*;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;
import java.io.IOException;

/**
 * Intelligent Food Substitution Manager
 * Provides alternative food options when certain foods are not available or suitable
 */
public class FoodSubstitutionManager {
    private static final String TAG = "FoodSubstitutionManager";
    
    // Use centralized API configuration
    private static final String GEMINI_TEXT_API_URL = ApiConfig.GEMINI_TEXT_API_URL;
    
    private Context context;
    private ExecutorService executorService;
    
    public interface SubstitutionCallback {
        void onSubstitutionsFound(List<FoodRecommendation> substitutions, String reason);
        void onError(String error);
    }
    
    public FoodSubstitutionManager(Context context) {
        this.context = context;
        this.executorService = Executors.newFixedThreadPool(2);
    }
    
    /**
     * Get intelligent food substitutions based on various criteria
     */
    public void getFoodSubstitutions(FoodRecommendation originalFood, 
                                   String userAge, String userSex, String userBMI, 
                                   String userHealthConditions, String userBudgetLevel,
                                   String userAllergies, String userDietPrefs, 
                                   String userPregnancyStatus, String substitutionReason,
                                   SubstitutionCallback callback) {
        
        executorService.execute(() -> {
            try {
                Log.d(TAG, "Getting substitutions for: " + originalFood.getFoodName() + 
                      " | Reason: " + substitutionReason);
                
                // Build substitution prompt
                String substitutionPrompt = buildSubstitutionPrompt(originalFood, userAge, userSex, 
                    userBMI, userHealthConditions, userBudgetLevel, userAllergies, userDietPrefs, 
                    userPregnancyStatus, substitutionReason);
                
                // Call Gemini API for substitutions
                List<FoodRecommendation> substitutions = callGeminiForSubstitutions(substitutionPrompt);
                
                if (substitutions != null && !substitutions.isEmpty()) {
                    Log.d(TAG, "Found " + substitutions.size() + " substitutions for " + originalFood.getFoodName());
                    callback.onSubstitutionsFound(substitutions, substitutionReason);
                } else {
                    Log.w(TAG, "No substitutions found, using fallback");
                    List<FoodRecommendation> fallbackSubstitutions = getFallbackSubstitutions(originalFood, substitutionReason);
                    callback.onSubstitutionsFound(fallbackSubstitutions, substitutionReason);
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error getting food substitutions: " + e.getMessage());
                callback.onError("Failed to get food substitutions: " + e.getMessage());
            }
        });
    }
    
    /**
     * Get substitutions for budget constraints
     */
    public void getBudgetSubstitutions(FoodRecommendation originalFood, String budgetLevel, SubstitutionCallback callback) {
        getFoodSubstitutions(originalFood, null, null, null, null, budgetLevel, 
                           null, null, null, "Budget constraint - need cheaper alternatives", callback);
    }
    
    /**
     * Get substitutions for health conditions
     */
    public void getHealthSubstitutions(FoodRecommendation originalFood, String healthConditions, 
                                     String userAge, String userSex, String userBMI, SubstitutionCallback callback) {
        getFoodSubstitutions(originalFood, userAge, userSex, userBMI, healthConditions, 
                           null, null, null, null, "Health condition - need healthier alternatives", callback);
    }
    
    /**
     * Get substitutions for allergies
     */
    public void getAllergySubstitutions(FoodRecommendation originalFood, String allergies, SubstitutionCallback callback) {
        getFoodSubstitutions(originalFood, null, null, null, null, null, 
                           allergies, null, null, "Allergy constraint - need safe alternatives", callback);
    }
    
    /**
     * Get substitutions for dietary preferences
     */
    public void getDietarySubstitutions(FoodRecommendation originalFood, String dietPrefs, SubstitutionCallback callback) {
        getFoodSubstitutions(originalFood, null, null, null, null, null, 
                           null, dietPrefs, null, "Dietary preference - need suitable alternatives", callback);
    }
    
    /**
     * Get substitutions for pregnancy
     */
    public void getPregnancySubstitutions(FoodRecommendation originalFood, String pregnancyStatus, 
                                        String userAge, String userSex, SubstitutionCallback callback) {
        getFoodSubstitutions(originalFood, userAge, userSex, null, null, null, 
                           null, null, pregnancyStatus, "Pregnancy - need safe alternatives", callback);
    }
    
    /**
     * Get substitutions for availability issues
     */
    public void getAvailabilitySubstitutions(FoodRecommendation originalFood, String location, SubstitutionCallback callback) {
        getFoodSubstitutions(originalFood, null, null, null, null, null, 
                           null, null, null, "Availability - need accessible alternatives in " + location, callback);
    }
    
    private String buildSubstitutionPrompt(FoodRecommendation originalFood, String userAge, String userSex, 
                                         String userBMI, String userHealthConditions, String userBudgetLevel,
                                         String userAllergies, String userDietPrefs, String userPregnancyStatus, 
                                         String substitutionReason) {
        
        return "3 healthier alternatives to: " + originalFood.getFoodName() + 
               " (" + originalFood.getCalories() + " cal, " + originalFood.getProtein() + "g protein)\n\n" +
               
               "Make healthier: lower calories/fat, higher protein/fiber, more vegetables\n" +
               "User: BMI " + (userBMI != null ? userBMI : "22.5") + 
               (userHealthConditions != null && !userHealthConditions.equals("None") ? ", Health: " + userHealthConditions : "") + "\n\n" +
               
               "JSON: [{\"food_name\": \"[HEALTHY ALTERNATIVE]\", \"calories\": <num>, \"protein_g\": <num>, " +
               "\"fat_g\": <num>, \"carbs_g\": <num>, \"serving_size\": \"1 serving\", " +
               "\"diet_type\": \"[TYPE]\", \"description\": \"[BENEFITS]\"}, ...]";
    }
    
    private List<FoodRecommendation> callGeminiForSubstitutions(String prompt) {
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
            
            // Make API call with optimized timeouts
            OkHttpClient client = new OkHttpClient.Builder()
                .connectTimeout(ApiConfig.CONNECT_TIMEOUT, java.util.concurrent.TimeUnit.SECONDS)
                .readTimeout(ApiConfig.READ_TIMEOUT, java.util.concurrent.TimeUnit.SECONDS)
                .writeTimeout(ApiConfig.WRITE_TIMEOUT, java.util.concurrent.TimeUnit.SECONDS)
                .build();
                
            RequestBody body = RequestBody.create(
                requestBody.toString(), 
                okhttp3.MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                .url(GEMINI_TEXT_API_URL)
                .post(body)
                .build();
                
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Gemini substitution response: " + responseText);
                    
                    return parseSubstitutionResponse(responseText);
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error calling Gemini for substitutions: " + e.getMessage());
        }
        
        return null;
    }
    
    private List<FoodRecommendation> parseSubstitutionResponse(String responseText) {
        List<FoodRecommendation> substitutions = new ArrayList<>();
        
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
                        Log.d(TAG, "Extracted substitution text: " + textContent);
                        
                        // Extract JSON array from the text content
                        int arrayStart = textContent.indexOf("[");
                        int arrayEnd = textContent.lastIndexOf("]") + 1;
                        
                        if (arrayStart >= 0 && arrayEnd > arrayStart) {
                            String jsonArrayString = textContent.substring(arrayStart, arrayEnd);
                            Log.d(TAG, "Extracted substitution JSON: " + jsonArrayString);
                            
                            JSONArray substitutionArray = new JSONArray(jsonArrayString);
                            
                            for (int j = 0; j < substitutionArray.length(); j++) {
                                try {
                                    JSONObject substitutionJson = substitutionArray.getJSONObject(j);
                                    
                                    String foodName = substitutionJson.optString("food_name", "");
                                    int calories = substitutionJson.optInt("calories", 0);
                                    double protein = substitutionJson.optDouble("protein_g", 0.0);
                                    double fat = substitutionJson.optDouble("fat_g", 0.0);
                                    double carbs = substitutionJson.optDouble("carbs_g", 0.0);
                                    String servingSize = substitutionJson.optString("serving_size", "1 serving");
                                    String dietType = substitutionJson.optString("diet_type", "Substitution");
                                    String description = substitutionJson.optString("description", "");
                                    
                                    if (!foodName.trim().isEmpty()) {
                                        FoodRecommendation substitution = new FoodRecommendation(
                                            foodName, calories, protein, fat, carbs, servingSize, dietType, description
                                        );
                                        substitutions.add(substitution);
                                        Log.d(TAG, "Added substitution: " + foodName);
                                    }
                                } catch (JSONException e) {
                                    Log.w(TAG, "Error parsing substitution at index " + j + ": " + e.getMessage());
                                }
                            }
                        }
                    }
                }
            }
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing substitution JSON: " + e.getMessage());
        }
        
        return substitutions;
    }
    
    private List<FoodRecommendation> getFallbackSubstitutions(FoodRecommendation originalFood, String reason) {
        List<FoodRecommendation> fallbackSubstitutions = new ArrayList<>();
        
        // Create fallback substitutions based on the original food
        String originalName = originalFood.getFoodName().toLowerCase();
        
        if (originalName.contains("adobo")) {
            fallbackSubstitutions.add(new FoodRecommendation("Tinola", 380, 25, 12, 20, "1 bowl", "Substitution", "Light chicken soup with vegetables - healthier alternative to adobo"));
            fallbackSubstitutions.add(new FoodRecommendation("Nilagang Baboy", 400, 22, 16, 25, "1 bowl", "Substitution", "Boiled pork soup - lighter cooking method than adobo"));
            fallbackSubstitutions.add(new FoodRecommendation("Paksiw na Bangus", 350, 25, 14, 20, "1 plate", "Substitution", "Fish cooked in vinegar - similar tangy flavor, healthier protein"));
        } else if (originalName.contains("sinigang")) {
            fallbackSubstitutions.add(new FoodRecommendation("Tinola", 380, 25, 12, 20, "1 bowl", "Substitution", "Light chicken soup - similar clear broth soup"));
            fallbackSubstitutions.add(new FoodRecommendation("Nilagang Baboy", 400, 22, 16, 25, "1 bowl", "Substitution", "Boiled pork soup - similar clear soup base"));
            fallbackSubstitutions.add(new FoodRecommendation("Bulalo", 450, 30, 20, 15, "1 bowl", "Substitution", "Rich beef bone marrow soup - hearty alternative"));
        } else if (originalName.contains("kare-kare")) {
            fallbackSubstitutions.add(new FoodRecommendation("Mechado", 480, 28, 20, 30, "1 plate", "Substitution", "Slow-cooked beef with vegetables - similar hearty stew"));
            fallbackSubstitutions.add(new FoodRecommendation("Afritada", 420, 25, 18, 25, "1 plate", "Substitution", "Pork stew with vegetables - similar tomato-based sauce"));
            fallbackSubstitutions.add(new FoodRecommendation("Kaldereta", 450, 28, 22, 30, "1 plate", "Substitution", "Goat stew with vegetables - similar rich, flavorful dish"));
        } else {
            // Generic fallback substitutions
            fallbackSubstitutions.add(new FoodRecommendation("Tinola", 380, 25, 12, 20, "1 bowl", "Substitution", "Light chicken soup - healthy and nutritious alternative"));
            fallbackSubstitutions.add(new FoodRecommendation("Adobo", 450, 25, 18, 35, "1 plate", "Substitution", "Classic Filipino stew - versatile and budget-friendly"));
            fallbackSubstitutions.add(new FoodRecommendation("Sinigang", 420, 30, 15, 25, "1 bowl", "Substitution", "Sour soup with vegetables - refreshing and healthy"));
        }
        
        Log.d(TAG, "Created " + fallbackSubstitutions.size() + " fallback substitutions");
        return fallbackSubstitutions;
    }
    
    public void shutdown() {
        if (executorService != null) {
            executorService.shutdown();
        }
    }
}
