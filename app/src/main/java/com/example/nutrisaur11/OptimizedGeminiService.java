package com.example.nutrisaur11;

import android.util.Log;
import java.util.*;
import java.util.concurrent.TimeUnit;
import okhttp3.*;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;

/**
 * Optimized Gemini API Service with retry logic and request optimization
 */
public class OptimizedGeminiService {
    private static final String TAG = "OptimizedGeminiService";
    
    /**
     * Call Gemini API with optimized settings and retry logic
     */
    public static Map<String, List<FoodRecommendation>> callGeminiWithRetry(String prompt) {
        // Optimize prompt if too long
        String optimizedPrompt = optimizePrompt(prompt);
        Log.d(TAG, "Original prompt length: " + prompt.length() + ", Optimized: " + optimizedPrompt.length());
        
        for (int attempt = 1; attempt <= ApiConfig.MAX_RETRY_ATTEMPTS; attempt++) {
            try {
                Log.d(TAG, "Gemini API attempt " + attempt + "/" + ApiConfig.MAX_RETRY_ATTEMPTS);
                
                Map<String, List<FoodRecommendation>> result = callGeminiAPI(optimizedPrompt, attempt);
                if (result != null && !result.isEmpty()) {
                    Log.d(TAG, "Gemini API successful on attempt " + attempt);
                    return result;
                }
                
                // If not the last attempt, wait before retry
                if (attempt < ApiConfig.MAX_RETRY_ATTEMPTS) {
                    long delay = calculateRetryDelay(attempt);
                    Log.w(TAG, "Gemini API failed on attempt " + attempt + ", retrying in " + delay + "ms");
                    Thread.sleep(delay);
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Gemini API attempt " + attempt + " failed: " + e.getMessage());
                if (attempt == ApiConfig.MAX_RETRY_ATTEMPTS) {
                    Log.e(TAG, "All Gemini API attempts failed");
                }
            }
        }
        
        return null;
    }
    
    /**
     * Optimize prompt to reduce size and complexity
     */
    private static String optimizePrompt(String prompt) {
        if (prompt.length() <= ApiConfig.MAX_PROMPT_LENGTH) {
            return prompt;
        }
        
        Log.w(TAG, "Prompt too long (" + prompt.length() + " chars), optimizing...");
        
        // Create a more concise version
        StringBuilder optimized = new StringBuilder();
        optimized.append("You are a PROFESSIONAL NUTRITIONIST. Generate EXACTLY 8 food dishes for EACH of 4 categories:\n\n");
        
        // Extract key user info (simplified)
        String[] lines = prompt.split("\n");
        for (String line : lines) {
            if (line.startsWith("Age:") || line.startsWith("Sex:") || line.startsWith("BMI:") || 
                line.startsWith("Health:") || line.startsWith("Budget:") || line.startsWith("Allergies:")) {
                optimized.append(line).append("\n");
            }
        }
        
        optimized.append("\nCATEGORIES:\n");
        optimized.append("1. TRADITIONAL FILIPINO (8 dishes)\n");
        optimized.append("2. HEALTHY OPTIONS (8 dishes)\n");
        optimized.append("3. INTERNATIONAL CUISINE (8 dishes)\n");
        optimized.append("4. BUDGET-FRIENDLY (8 dishes)\n\n");
        
        optimized.append("REQUIREMENTS:\n");
        optimized.append("- Each dish: food_name, calories (150-800), protein_g (5-40), fat_g (2-30), carbs_g (10-100)\n");
        optimized.append("- serving_size: \"1 serving\", diet_type: category name\n");
        optimized.append("- description: 1-2 sentences\n\n");
        
        optimized.append("Return ONLY valid JSON:\n");
        optimized.append("{\"traditional\":[{\"food_name\":\"Name\",\"calories\":300,\"protein_g\":20,\"fat_g\":10,\"carbs_g\":25,\"serving_size\":\"1 serving\",\"diet_type\":\"Traditional Filipino\",\"description\":\"Description\"},...],\"healthy\":[...],\"international\":[...],\"budget\":[...]}");
        
        return optimized.toString();
    }
    
    /**
     * Calculate retry delay with exponential backoff
     */
    private static long calculateRetryDelay(int attempt) {
        long delay = ApiConfig.INITIAL_RETRY_DELAY_MS * (1L << (attempt - 1)); // Exponential backoff
        return Math.min(delay, ApiConfig.MAX_RETRY_DELAY_MS);
    }
    
    /**
     * Call Gemini API with optimized settings
     */
    private static Map<String, List<FoodRecommendation>> callGeminiAPI(String prompt, int attempt) {
        try {
            // Create optimized JSON request
            JSONObject requestBody = new JSONObject();
            
            // Add generation config for better performance
            JSONObject generationConfig = new JSONObject();
            generationConfig.put("maxOutputTokens", ApiConfig.MAX_TOKENS);
            generationConfig.put("temperature", 0.7);
            generationConfig.put("topP", 0.8);
            generationConfig.put("topK", 40);
            requestBody.put("generationConfig", generationConfig);
            
            // Add safety settings
            JSONObject safetySettings = new JSONObject();
            safetySettings.put("category", "HARM_CATEGORY_HARASSMENT");
            safetySettings.put("threshold", "BLOCK_MEDIUM_AND_ABOVE");
            JSONArray safetyArray = new JSONArray();
            safetyArray.put(safetySettings);
            requestBody.put("safetySettings", safetyArray);
            
            // Add content
            JSONArray contents = new JSONArray();
            JSONObject content = new JSONObject();
            JSONArray parts = new JSONArray();
            JSONObject part = new JSONObject();
            part.put("text", prompt);
            parts.put(part);
            content.put("parts", parts);
            contents.put(content);
            requestBody.put("contents", contents);
            
            // Create optimized OkHttpClient
            OkHttpClient client = new OkHttpClient.Builder()
                .connectTimeout(ApiConfig.CONNECT_TIMEOUT, TimeUnit.SECONDS)
                .readTimeout(ApiConfig.READ_TIMEOUT, TimeUnit.SECONDS)
                .writeTimeout(ApiConfig.WRITE_TIMEOUT, TimeUnit.SECONDS)
                .retryOnConnectionFailure(true)
                .build();
            
            RequestBody body = RequestBody.create(
                requestBody.toString(), 
                okhttp3.MediaType.parse("application/json; charset=utf-8")
            );
            
            Request request = new Request.Builder()
                .url(ApiConfig.GEMINI_TEXT_API_URL)
                .post(body)
                .addHeader("Content-Type", "application/json")
                .addHeader("User-Agent", "NutrisaurApp/1.0")
                .build();
            
            Log.d(TAG, "Sending Gemini request (attempt " + attempt + ")");
            long startTime = System.currentTimeMillis();
            
            try (Response response = client.newCall(request).execute()) {
                long duration = System.currentTimeMillis() - startTime;
                Log.d(TAG, "Gemini response received in " + duration + "ms");
                
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Gemini response length: " + responseText.length() + " chars");
                    
                    return parseGeminiResponse(responseText);
                } else {
                    Log.e(TAG, "Gemini API error: " + response.code() + " - " + response.message());
                    if (response.body() != null) {
                        String errorBody = response.body().string();
                        Log.e(TAG, "Error body: " + errorBody);
                    }
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Exception in Gemini API call (attempt " + attempt + "): " + e.getMessage());
            if (e.getCause() != null) {
                Log.e(TAG, "Caused by: " + e.getCause().getMessage());
            }
        }
        
        return null;
    }
    
    /**
     * Parse Gemini response with improved error handling
     */
    private static Map<String, List<FoodRecommendation>> parseGeminiResponse(String responseText) {
        Map<String, List<FoodRecommendation>> result = new HashMap<>();
        
        try {
            JSONObject geminiResponse = new JSONObject(responseText);
            
            // Check for errors first
            if (geminiResponse.has("error")) {
                JSONObject error = geminiResponse.getJSONObject("error");
                Log.e(TAG, "Gemini API error: " + error.optString("message", "Unknown error"));
                return null;
            }
            
            JSONArray candidates = geminiResponse.optJSONArray("candidates");
            if (candidates == null || candidates.length() == 0) {
                Log.e(TAG, "No candidates in Gemini response");
                return null;
            }
            
            JSONObject candidate = candidates.getJSONObject(0);
            JSONObject content = candidate.optJSONObject("content");
            if (content == null) {
                Log.e(TAG, "No content in Gemini candidate");
                return null;
            }
            
            JSONArray parts = content.optJSONArray("parts");
            if (parts == null || parts.length() == 0) {
                Log.e(TAG, "No parts in Gemini content");
                return null;
            }
            
            for (int i = 0; i < parts.length(); i++) {
                JSONObject part = parts.getJSONObject(i);
                if (part.has("text")) {
                    String textContent = part.getString("text");
                    Log.d(TAG, "Extracted Gemini text: " + textContent.substring(0, Math.min(200, textContent.length())) + "...");
                    
                    // Extract JSON object from the text content
                    int objectStart = textContent.indexOf("{");
                    int objectEnd = textContent.lastIndexOf("}") + 1;
                    
                    if (objectStart >= 0 && objectEnd > objectStart) {
                        String jsonObjectString = textContent.substring(objectStart, objectEnd);
                        Log.d(TAG, "Extracted JSON: " + jsonObjectString.substring(0, Math.min(200, jsonObjectString.length())) + "...");
                        
                        JSONObject mainFoodsJson = new JSONObject(jsonObjectString);
                        
                        // Parse each category
                        result.put("traditional", parseFoodArray(mainFoodsJson.optJSONArray("traditional")));
                        result.put("healthy", parseFoodArray(mainFoodsJson.optJSONArray("healthy")));
                        result.put("international", parseFoodArray(mainFoodsJson.optJSONArray("international")));
                        result.put("budget", parseFoodArray(mainFoodsJson.optJSONArray("budget")));
                        
                        Log.d(TAG, "Successfully parsed Gemini response: " + result.size() + " categories");
                        return result;
                    } else {
                        Log.w(TAG, "No valid JSON found in Gemini response text");
                    }
                }
            }
        } catch (JSONException e) {
            Log.e(TAG, "JSON parsing error: " + e.getMessage());
            Log.e(TAG, "Response text: " + responseText.substring(0, Math.min(500, responseText.length())));
        } catch (Exception e) {
            Log.e(TAG, "Unexpected error parsing Gemini response: " + e.getMessage());
        }
        
        return null;
    }
    
    /**
     * Parse food array from JSON
     */
    private static List<FoodRecommendation> parseFoodArray(JSONArray foodArray) {
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
                    
                    if (!foodName.trim().isEmpty() && calories > 0) {
                        FoodRecommendation food = new FoodRecommendation(
                            foodName, calories, protein, fat, carbs, servingSize, dietType, description
                        );
                        foods.add(food);
                        Log.d(TAG, "Added food: " + foodName + " (" + calories + " cal)");
                    }
                } catch (JSONException e) {
                    Log.w(TAG, "Error parsing food at index " + i + ": " + e.getMessage());
                }
            }
        }
        
        return foods;
    }
}
