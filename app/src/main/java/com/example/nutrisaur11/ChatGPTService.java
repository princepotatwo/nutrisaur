package com.example.nutrisaur11;

import android.util.Log;
import java.util.*;
import java.util.concurrent.TimeUnit;
import okhttp3.*;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;

/**
 * ChatGPT API Service for food recommendations
 */
public class ChatGPTService {
    private static final String TAG = "ChatGPTService";
    private static final String API_KEY = "YOUR_OPENAI_API_KEY_HERE";
    private static final String API_URL = "https://api.openai.com/v1/chat/completions";
    
    /**
     * Call ChatGPT API with optimized settings
     */
    public static Map<String, List<FoodRecommendation>> callChatGPTWithRetry(String prompt) {
        // Optimize prompt if too long
        String optimizedPrompt = optimizePrompt(prompt);
        Log.d(TAG, "Original prompt length: " + prompt.length() + ", Optimized: " + optimizedPrompt.length());
        
        try {
            Log.d(TAG, "ChatGPT API attempt 1/1");
            
            Map<String, List<FoodRecommendation>> result = callChatGPTAPI(optimizedPrompt, 1);
            if (result != null && !result.isEmpty()) {
                // Verify we got foods in all categories
                int totalFoods = 0;
                for (List<FoodRecommendation> foods : result.values()) {
                    totalFoods += foods.size();
                }
                
                if (totalFoods > 0) {
                    Log.d(TAG, "ChatGPT API successful with " + totalFoods + " total foods");
                    return result;
                } else {
                    Log.e(TAG, "API returned empty result");
                    throw new RuntimeException("API returned empty result");
                }
            } else {
                Log.e(TAG, "API returned null result");
                throw new RuntimeException("API returned null result");
            }
            
        } catch (Exception e) {
            Log.e(TAG, "ChatGPT API failed: " + e.getMessage());
            throw new RuntimeException("Failed to get food recommendations: " + e.getMessage());
        }
    }
    
    /**
     * Call ChatGPT API directly
     */
    private static Map<String, List<FoodRecommendation>> callChatGPTAPI(String prompt, int attempt) {
        OkHttpClient client = new OkHttpClient.Builder()
                .connectTimeout(30, TimeUnit.SECONDS)
                .readTimeout(60, TimeUnit.SECONDS)
                .writeTimeout(30, TimeUnit.SECONDS)
                .build();
        
        try {
            Log.d(TAG, "Sending ChatGPT request (attempt " + attempt + ")");
            Log.d(TAG, "Request URL: " + API_URL);
            
            // Build request body for ChatGPT
            JSONObject requestBody = new JSONObject();
            requestBody.put("model", "gpt-4");
            requestBody.put("temperature", 0.7);
            requestBody.put("max_tokens", 2048);
            
            JSONArray messages = new JSONArray();
            JSONObject message = new JSONObject();
            message.put("role", "user");
            message.put("content", prompt);
            messages.put(message);
            requestBody.put("messages", messages);
            
            RequestBody body = RequestBody.create(
                requestBody.toString(),
                MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                    .url(API_URL)
                    .addHeader("Authorization", "Bearer " + API_KEY)
                    .addHeader("Content-Type", "application/json")
                    .post(body)
                    .build();
            
            Response response = client.newCall(request).execute();
            Log.d(TAG, "ChatGPT response received in " + response.receivedResponseAtMillis() + "ms");
            Log.d(TAG, "Response code: " + response.code());
            Log.d(TAG, "Response message: " + response.message());
            
            if (response.isSuccessful() && response.body() != null) {
                String responseBody = response.body().string();
                Log.d(TAG, "ChatGPT response length: " + responseBody.length() + " chars");
                Log.d(TAG, "ChatGPT response content: " + responseBody.substring(0, Math.min(500, responseBody.length())));
                
                return parseChatGPTResponse(responseBody);
            } else {
                Log.e(TAG, "ChatGPT API call failed with code: " + response.code());
                if (response.body() != null) {
                    String errorBody = response.body().string();
                    Log.e(TAG, "Error response: " + errorBody);
                }
                throw new RuntimeException("ChatGPT API call failed with code: " + response.code());
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Exception in ChatGPT API call (attempt " + attempt + "): " + e.getMessage());
            Log.e(TAG, "Exception type: " + e.getClass().getSimpleName());
            throw new RuntimeException("ChatGPT API call failed: " + e.getMessage());
        } finally {
            client.dispatcher().executorService().shutdown();
        }
    }
    
    /**
     * Parse ChatGPT response and extract food recommendations
     */
    private static Map<String, List<FoodRecommendation>> parseChatGPTResponse(String responseBody) {
        Map<String, List<FoodRecommendation>> result = new HashMap<>();
        
        try {
            Log.d(TAG, "Parsing ChatGPT response...");
            
            JSONObject jsonResponse = new JSONObject(responseBody);
            JSONArray choices = jsonResponse.getJSONArray("choices");
            
            if (choices.length() > 0) {
                JSONObject firstChoice = choices.getJSONObject(0);
                JSONObject message = firstChoice.getJSONObject("message");
                String content = message.getString("content");
                
                Log.d(TAG, "Extracted ChatGPT text: " + content.substring(0, Math.min(200, content.length())) + "...");
                
                // Extract JSON from the response
                String jsonObjectString = extractJSONFromResponse(content);
                Log.d(TAG, "Extracted JSON: " + jsonObjectString.substring(0, Math.min(200, jsonObjectString.length())) + "...");
                
                // Fix common JSON issues
                String fixedJson = fixJSONIssues(jsonObjectString);
                Log.d(TAG, "Fixed JSON: " + fixedJson.substring(0, Math.min(200, fixedJson.length())) + "...");
                
                // Parse the JSON
                JSONObject mainFoodsJson = new JSONObject(fixedJson);
                
                // Parse each category - try both lowercase and capitalized versions
                JSONArray breakfastArray = mainFoodsJson.optJSONArray("breakfast");
                if (breakfastArray == null) breakfastArray = mainFoodsJson.optJSONArray("Breakfast");
                
                JSONArray lunchArray = mainFoodsJson.optJSONArray("lunch");
                if (lunchArray == null) lunchArray = mainFoodsJson.optJSONArray("Lunch");
                
                JSONArray dinnerArray = mainFoodsJson.optJSONArray("dinner");
                if (dinnerArray == null) dinnerArray = mainFoodsJson.optJSONArray("Dinner");
                
                JSONArray snacksArray = mainFoodsJson.optJSONArray("snacks");
                if (snacksArray == null) snacksArray = mainFoodsJson.optJSONArray("Snacks");
                
                // Parse foods for each category
                List<FoodRecommendation> breakfastFoods = parseFoodArray(breakfastArray, "breakfast");
                List<FoodRecommendation> lunchFoods = parseFoodArray(lunchArray, "lunch");
                List<FoodRecommendation> dinnerFoods = parseFoodArray(dinnerArray, "dinner");
                List<FoodRecommendation> snackFoods = parseFoodArray(snacksArray, "snacks");
                
                // Add to result
                if (!breakfastFoods.isEmpty()) result.put("breakfast", breakfastFoods);
                if (!lunchFoods.isEmpty()) result.put("lunch", lunchFoods);
                if (!dinnerFoods.isEmpty()) result.put("dinner", dinnerFoods);
                if (!snackFoods.isEmpty()) result.put("snacks", snackFoods);
                
                Log.d(TAG, "Successfully parsed ChatGPT response: " + result.size() + " categories");
                Log.d(TAG, "ChatGPT API successful with " + (breakfastFoods.size() + lunchFoods.size() + dinnerFoods.size() + snackFoods.size()) + " total foods");
                
                // Log category counts
                for (Map.Entry<String, List<FoodRecommendation>> entry : result.entrySet()) {
                    Log.d(TAG, "Category " + entry.getKey() + ": " + entry.getValue().size() + " foods");
                }
                
                return result;
            } else {
                Log.e(TAG, "No choices in ChatGPT response");
                throw new RuntimeException("No choices in ChatGPT response");
            }
            
        } catch (JSONException e) {
            Log.e(TAG, "JSON parsing error: " + e.getMessage());
            throw new RuntimeException("Failed to parse ChatGPT response: " + e.getMessage());
        } catch (Exception e) {
            Log.e(TAG, "Unexpected error parsing ChatGPT response: " + e.getMessage());
            throw new RuntimeException("Failed to parse ChatGPT response: " + e.getMessage());
        }
    }
    
    /**
     * Extract JSON from ChatGPT response text
     */
    private static String extractJSONFromResponse(String response) {
        // Look for JSON block in the response
        String[] lines = response.split("\n");
        StringBuilder jsonBuilder = new StringBuilder();
        boolean inJsonBlock = false;
        
        for (String line : lines) {
            line = line.trim();
            if (line.startsWith("```json") || line.startsWith("```")) {
                inJsonBlock = true;
                continue;
            }
            if (inJsonBlock && line.equals("```")) {
                break;
            }
            if (inJsonBlock) {
                jsonBuilder.append(line).append("\n");
            }
        }
        
        String jsonString = jsonBuilder.toString().trim();
        if (jsonString.isEmpty()) {
            // Try to find JSON object directly
            int startIndex = response.indexOf("{");
            int endIndex = response.lastIndexOf("}");
            if (startIndex != -1 && endIndex != -1 && endIndex > startIndex) {
                jsonString = response.substring(startIndex, endIndex + 1);
            }
        }
        
        return jsonString;
    }
    
    /**
     * Fix common JSON issues
     */
    private static String fixJSONIssues(String jsonString) {
        try {
            // Remove trailing commas before closing braces/brackets
            jsonString = jsonString.replaceAll(",\\s*\\}", "}");
            jsonString = jsonString.replaceAll(",\\s*\\]", "]");
            
            // Fix unquoted keys
            jsonString = jsonString.replaceAll("(\\w+):", "\"$1\":");
            
            // Fix single quotes to double quotes
            jsonString = jsonString.replaceAll("'([^']*)'", "\"$1\"");
            
            return jsonString;
        } catch (Exception e) {
            Log.w(TAG, "Error fixing JSON, returning original: " + e.getMessage());
            return jsonString;
        }
    }
    
    /**
     * Parse food array from JSON
     */
    private static List<FoodRecommendation> parseFoodArray(JSONArray foodArray, String category) {
        List<FoodRecommendation> foods = new ArrayList<>();
        
        if (foodArray == null) {
            Log.w(TAG, "No " + category + " array found");
            return foods;
        }
        
        Log.d(TAG, "Parsing food array with " + foodArray.length() + " items");
        
        for (int i = 0; i < foodArray.length(); i++) {
            try {
                JSONObject foodJson = foodArray.getJSONObject(i);
                
                String foodName = foodJson.optString("food_name", "Unknown Food");
                int calories = foodJson.optInt("calories", 0);
                int protein = foodJson.optInt("protein_g", 0);
                int fat = foodJson.optInt("fat_g", 0);
                int carbs = foodJson.optInt("carbs_g", 0);
                String servingSize = foodJson.optString("serving_size", "1 serving");
                String dietType = foodJson.optString("diet_type", category);
                String description = foodJson.optString("description", "");
                
                Log.d(TAG, "Parsing food " + i + ": " + foodName + " (diet_type: " + dietType + ")");
                
                // Create FoodRecommendation object
                FoodRecommendation food = new FoodRecommendation();
                food.setFoodName(foodName);
                food.setCalories(calories);
                food.setProtein(protein);
                food.setFat(fat);
                food.setCarbs(carbs);
                food.setServingSize(servingSize);
                food.setDietType(dietType);
                food.setDescription(description);
                
                foods.add(food);
                Log.d(TAG, "Successfully added food: " + foodName + " (" + calories + " cal, " + dietType + ")");
                
            } catch (JSONException e) {
                Log.e(TAG, "Error parsing food " + i + ": " + e.getMessage());
            }
        }
        
        Log.d(TAG, "Parsed " + foods.size() + " foods from array");
        return foods;
    }
    
    /**
     * Optimize prompt if too long
     */
    private static String optimizePrompt(String prompt) {
        if (prompt.length() > 3000) {
            Log.d(TAG, "Prompt too long, optimizing...");
            // Keep the essential parts and truncate the rest
            String[] lines = prompt.split("\n");
            StringBuilder optimized = new StringBuilder();
            
            for (String line : lines) {
                if (line.contains("PATIENT PROFILE") || 
                    line.contains("CRITICAL") || 
                    line.contains("IMPORTANT") ||
                    line.contains("YOUR TASK") ||
                    line.contains("Return JSON")) {
                    optimized.append(line).append("\n");
                }
            }
            
            return optimized.toString();
        }
        return prompt;
    }
}
