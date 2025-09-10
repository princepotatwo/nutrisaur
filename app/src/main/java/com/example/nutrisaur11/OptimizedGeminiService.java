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
        
        try {
            Log.d(TAG, "Gemini API attempt 1/1");
            
            Map<String, List<FoodRecommendation>> result = callGeminiAPI(optimizedPrompt, 1);
            if (result != null && !result.isEmpty()) {
                // Verify we got foods in all categories
                int totalFoods = 0;
                for (List<FoodRecommendation> foods : result.values()) {
                    totalFoods += foods.size();
                }
                
                if (totalFoods > 0) {
                    Log.d(TAG, "Gemini API successful with " + totalFoods + " total foods");
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
            Log.e(TAG, "Gemini API failed: " + e.getMessage());
            throw new RuntimeException("Failed to get food recommendations: " + e.getMessage());
        }
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
        optimized.append("1. BREAKFAST (8 dishes)\n");
        optimized.append("2. LUNCH (8 dishes)\n");
        optimized.append("3. DINNER (8 dishes)\n");
        optimized.append("4. SNACKS (8 dishes)\n\n");
        
        optimized.append("REQUIREMENTS:\n");
        optimized.append("- Each dish: food_name, calories (150-800), protein_g (5-40), fat_g (2-30), carbs_g (10-100)\n");
        optimized.append("- serving_size: \"1 serving\", diet_type: category name\n");
        optimized.append("- description: 1-2 sentences\n\n");
        
        optimized.append("Return ONLY valid JSON:\n");
        optimized.append("{\"breakfast\":[{\"food_name\":\"Name\",\"calories\":300,\"protein_g\":20,\"fat_g\":10,\"carbs_g\":25,\"serving_size\":\"1 serving\",\"diet_type\":\"Breakfast\",\"description\":\"Description\"},...],\"lunch\":[...],\"dinner\":[...],\"snacks\":[...]}");
        
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
            // Create simplified JSON request
            JSONObject requestBody = new JSONObject();
            
            // Add generation config
            JSONObject generationConfig = new JSONObject();
            generationConfig.put("maxOutputTokens", ApiConfig.MAX_TOKENS);
            generationConfig.put("temperature", 0.7);
            requestBody.put("generationConfig", generationConfig);
            
            // Add content (simplified)
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
            Log.d(TAG, "Request URL: " + ApiConfig.GEMINI_TEXT_API_URL);
            Log.d(TAG, "API Key: " + ApiConfig.GEMINI_API_KEY);
            Log.d(TAG, "Request body: " + requestBody.toString());
            long startTime = System.currentTimeMillis();
            
            try (Response response = client.newCall(request).execute()) {
                long duration = System.currentTimeMillis() - startTime;
                Log.d(TAG, "Gemini response received in " + duration + "ms");
                Log.d(TAG, "Response code: " + response.code());
                Log.d(TAG, "Response message: " + response.message());
                
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Gemini response length: " + responseText.length() + " chars");
                    Log.d(TAG, "Gemini response content: " + responseText);
                    
                    return parseGeminiResponse(responseText);
                } else {
                    Log.e(TAG, "Gemini API error: " + response.code() + " - " + response.message());
                    if (response.body() != null) {
                        String errorBody = response.body().string();
                        Log.e(TAG, "Error body: " + errorBody);
                    } else {
                        Log.e(TAG, "Response body is null");
                    }
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Exception in Gemini API call (attempt " + attempt + "): " + e.getMessage());
            Log.e(TAG, "Exception type: " + e.getClass().getSimpleName());
            if (e.getCause() != null) {
                Log.e(TAG, "Caused by: " + e.getCause().getMessage());
            }
            e.printStackTrace();
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
                    String jsonObjectString = extractJsonFromText(textContent);
                    
                    if (jsonObjectString != null && !jsonObjectString.isEmpty()) {
                        Log.d(TAG, "Extracted JSON: " + jsonObjectString.substring(0, Math.min(200, jsonObjectString.length())) + "...");
                        
                        // Clean and fix the JSON string
                        jsonObjectString = fixJsonString(jsonObjectString);
                        Log.d(TAG, "Fixed JSON: " + jsonObjectString.substring(0, Math.min(200, jsonObjectString.length())) + "...");
                        
                        JSONObject mainFoodsJson = new JSONObject(jsonObjectString);
                        
                        // Parse food recommendations directly
                        
                        // Parse each category - try both lowercase and capitalized versions
                        JSONArray breakfastArray = mainFoodsJson.optJSONArray("breakfast");
                        if (breakfastArray == null) breakfastArray = mainFoodsJson.optJSONArray("Breakfast");
                        
                        JSONArray lunchArray = mainFoodsJson.optJSONArray("lunch");
                        if (lunchArray == null) lunchArray = mainFoodsJson.optJSONArray("Lunch");
                        
                        JSONArray dinnerArray = mainFoodsJson.optJSONArray("dinner");
                        if (dinnerArray == null) dinnerArray = mainFoodsJson.optJSONArray("Dinner");
                        
                        JSONArray snacksArray = mainFoodsJson.optJSONArray("snacks");
                        if (snacksArray == null) snacksArray = mainFoodsJson.optJSONArray("Snacks");
                        
                        result.put("breakfast", parseFoodArray(breakfastArray));
                        result.put("lunch", parseFoodArray(lunchArray));
                        result.put("dinner", parseFoodArray(dinnerArray));
                        result.put("snacks", parseFoodArray(snacksArray));
                        
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
            Log.d(TAG, "Parsing food array with " + foodArray.length() + " items");
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
                    String imageUrl = foodJson.optString("image_url", "");
                    
                    // Get local drawable resource for Filipino foods if not provided
                    if (imageUrl.isEmpty()) {
                        imageUrl = getFilipinoFoodImageResource(foodName);
                    }
                    
                    Log.d(TAG, "Parsing food " + i + ": " + foodName + " (diet_type: " + dietType + ")");
                    
                    if (!foodName.trim().isEmpty() && calories > 0) {
                        FoodRecommendation food = new FoodRecommendation(
                            foodName, calories, protein, fat, carbs, servingSize, dietType, description, imageUrl
                        );
                        foods.add(food);
                        Log.d(TAG, "Successfully added food: " + foodName + " (" + calories + " cal, " + dietType + ")");
                    } else {
                        Log.w(TAG, "Skipping invalid food: " + foodName + " (calories: " + calories + ")");
                    }
                } catch (JSONException e) {
                    Log.w(TAG, "Error parsing food at index " + i + ": " + e.getMessage());
                }
            }
        } else {
            Log.w(TAG, "Food array is null");
        }
        
        Log.d(TAG, "Parsed " + foods.size() + " foods from array");
        return foods;
    }
    
    /**
     * Get local drawable resource name for Filipino foods
     */
    private static String getFilipinoFoodImageResource(String foodName) {
        // Map food names to existing drawable resources
        String lowerName = foodName.toLowerCase().replace(" ", "_").replace("(", "").replace(")", "").replace("-", "_");
        
        // Direct matches
        switch (lowerName) {
            case "champorado_with_tuyo":
            case "champorado":
                return "champorado";
            case "banana_cue":
            case "banana_cue_with_puto":
                return "banana_chips"; // closest match
            case "chicken_tinola":
            case "chicken_tinola_with_rice":
            case "tinola":
                return "tinola";
            case "ginataang_gulay":
            case "ginataang_gulay_with_rice":
                return "ginataang_mais"; // closest match
            case "pinakbet":
            case "pinakbet_with_fish":
                return "pinakbet";
            case "pork_sinigang":
            case "pork_sinigang_with_kangkong":
            case "sinigang":
                return "sinigang_na_baboy";
            case "suman":
                return "suman_sa_latik";
            case "banana_chips":
                return "banana_chips";
            case "adobo":
                return "adobo";
            case "afritada":
                return "afritada";
            case "arroz_caldo":
                return "arroz_caldo";
            case "bangsilog":
                return "bangsilog";
            case "bibingka":
                return "bibingka";
            case "bicol_express":
                return "bicol_express";
            case "biko":
                return "biko";
            case "bilo_bilo":
                return "bilo_bilo";
            case "binatog":
                return "binatog";
            case "binignit":
                return "binignit";
            case "bulalo":
                return "bulalo";
            case "cassava_cake":
                return "cassava_cake";
            case "chicharon":
                return "chicharon";
            case "chicken_inasal":
                return "chicken_inasal";
            case "crispy_pata":
                return "crispy_pata";
            case "daing_na_bangus":
                return "daing_na_bangus";
            case "dinengdeng":
                return "dinengdeng";
            case "dried_mangoes":
                return "dried_mangoes";
            case "embutido":
                return "embutido";
            case "empanada":
                return "empanada";
            case "escabeche":
                return "escabeche";
            case "espasol":
                return "espasol";
            case "fish_balls":
                return "fish_balls";
            case "fresh_lumpia":
                return "fresh_lumpia";
            case "fruit_salad":
                return "fruit_salad";
            case "ginataang_mais":
                return "ginataang_mais";
            case "ginataang_munggo":
                return "ginataang_munggo";
            case "ginataang_saging":
                return "ginataang_saging";
            case "ginanggang":
                return "ginanggang";
            case "goto_dish":
                return "goto_dish";
            case "halo_halo":
                return "halo_halo";
            case "hawflakes":
                return "hawflakes";
            case "inihaw_na_baboy":
                return "inihaw_na_baboy";
            case "inihaw_na_isda":
                return "inihaw_na_isda";
            case "inihaw_na_liempo":
                return "inihaw_na_liempo";
            case "inihaw_na_pusit":
                return "inihaw_na_pusit";
            case "isaw":
                return "isaw";
            case "kaldereta":
                return "kaldereta";
            case "kare_kare":
                return "kare_kare";
            case "kikiam":
                return "kikiam";
            case "kinilaw":
                return "kinilaw";
            case "kutsinta":
                return "kutsinta";
            case "kwek_kwek":
                return "kwek_kwek";
            case "laing":
                return "laing";
            case "lauya":
                return "lauya";
            case "leche_flan":
                return "leche_flan";
            case "lechon":
                return "lechon";
            case "lechon_kawali":
                return "lechon_kawali";
            case "lechon_manok":
                return "lechon_manok";
            case "longsilog":
                return "longsilog";
            case "lugaw":
                return "lugaw";
            case "lumpiang_shanghai":
                return "lumpiang_shanghai";
            case "macaroni_salad":
                return "macaroni_salad";
            case "mami":
                return "mami";
            case "mango_shake":
                return "mango_shake";
            case "mechado":
                return "mechado";
            case "menudo":
                return "menudo";
            case "monggo_guisado":
                return "monggo_guisado";
            case "mushroom_sisig":
                return "mushroom_sisig";
            case "nilagang_baboy":
                return "nilagang_baboy";
            case "nilagang_baka":
                return "nilagang_baka";
            case "nilupak":
                return "nilupak";
            case "okoy":
                return "okoy";
            case "otap":
                return "otap";
            case "paksiw_na_bangus":
                return "paksiw_na_bangus";
            case "paksiw_na_pata":
                return "paksiw_na_pata";
            case "palitaw":
                return "palitaw";
            case "pancit_canton":
                return "pancit_canton";
            case "pancit_molo":
                return "pancit_molo";
            case "pancit_sotanghon":
                return "pancit_sotanghon";
            case "pansit_bihon":
                return "pansit_bihon";
            case "pansit_lomi":
                return "pansit_lomi";
            case "pansit_malabon":
                return "pansit_malabon";
            case "papaitan":
                return "papaitan";
            case "pares":
                return "pares";
            case "pichi_pichi":
                return "pichi_pichi";
            case "pochero":
                return "pochero";
            case "pritong_bangus":
                return "pritong_bangus";
            case "pritong_galunggong":
                return "pritong_galunggong";
            case "pritong_tilapia":
                return "pritong_tilapia";
            case "puto_bumbong":
                return "puto_bumbong";
            case "puto_maya":
                return "puto_maya";
            case "puto":
                return "puto";
            case "saging_con_yelo":
                return "saging_con_yelo";
            case "sago_at_gulaman":
                return "sago_at_gulaman";
            case "salabat":
                return "salabat";
            case "sapin_sapin":
                return "sapin_sapin";
            case "sinangag":
                return "sinangag";
            case "sinigang_na_hipon":
                return "sinigang_na_hipon";
            case "sisig":
                return "sisig";
            case "sopas":
                return "sopas";
            case "sorbetes":
                return "sorbetes";
            case "soya_milk":
                return "soya_milk";
            case "squid_balls":
                return "squid_balls";
            case "suman_sa_latik":
                return "suman_sa_latik";
            case "suman_sa_lihiya":
                return "suman_sa_lihiya";
            case "sweet_and_sour_fish":
                return "sweet_and_sour_fish";
            case "sweet_sour_pork":
                return "sweet_sour_pork";
            case "tinapa":
                return "tinapa";
            case "tinolang_bangus":
                return "tinolang_bangus";
            case "tocilog":
                return "tocilog";
            case "tokneneng":
                return "tokneneng";
            case "tortang_giniling":
                return "tortang_giniling";
            case "tortang_talong":
                return "tortang_talong";
            case "tupig":
                return "tupig";
            case "turon":
                return "turon";
            case "twin_sticks":
                return "twin_sticks";
            case "ube_bibingka":
                return "ube_bibingka";
            case "ube_halaya":
                return "ube_halaya";
            case "ukoy":
                return "ukoy";
            case "vigan_empanada":
                return "vigan_empanada";
            case "yakult":
                return "yakult";
            default:
                // Return default food image if no match found
                return "steamed_riced";
        }
    }
    
    // Callback interface for food recommendations
    public interface FoodRecommendationCallback {
        void onSuccess(List<FoodRecommendation> recommendations);
        void onError(String error);
    }
    
    // Generate food recommendations with callback
    public void generateFoodRecommendations(String prompt, FoodRecommendationCallback callback) {
        Log.d(TAG, "=== STARTING FOOD RECOMMENDATION GENERATION ===");
        Log.d(TAG, "Prompt length: " + prompt.length() + " characters");
        
        // Run on background thread to avoid NetworkOnMainThreadException
        new Thread(() -> {
            try {
                Map<String, List<FoodRecommendation>> result = callGeminiWithRetry(prompt);
                if (result != null && !result.isEmpty()) {
                    List<FoodRecommendation> allRecommendations = new ArrayList<>();
                    for (Map.Entry<String, List<FoodRecommendation>> entry : result.entrySet()) {
                        String category = entry.getKey();
                        List<FoodRecommendation> categoryFoods = entry.getValue();
                        Log.d(TAG, "Category " + category + ": " + categoryFoods.size() + " foods");
                        allRecommendations.addAll(categoryFoods);
                    }
                    Log.d(TAG, "Total recommendations generated: " + allRecommendations.size());
                    
                    // Call callback on main thread
                    android.os.Handler mainHandler = new android.os.Handler(android.os.Looper.getMainLooper());
                    mainHandler.post(() -> {
                        Log.d(TAG, "Calling onSuccess with " + allRecommendations.size() + " recommendations");
                        callback.onSuccess(allRecommendations);
                    });
                } else {
                    Log.e(TAG, "No food recommendations generated - result is null or empty");
                    // Call callback on main thread
                    android.os.Handler mainHandler = new android.os.Handler(android.os.Looper.getMainLooper());
                    mainHandler.post(() -> callback.onError("No food recommendations generated"));
                }
            } catch (Exception e) {
                Log.e(TAG, "Error in generateFoodRecommendations: " + e.getMessage());
                e.printStackTrace();
                // Call callback on main thread
                android.os.Handler mainHandler = new android.os.Handler(android.os.Looper.getMainLooper());
                mainHandler.post(() -> callback.onError("Error generating food recommendations: " + e.getMessage()));
            }
        }).start();
    }
    
    private static String extractJsonFromText(String text) {
        // Look for JSON between ```json and ``` or just find the first complete JSON object
        String jsonStart = "```json";
        String jsonEnd = "```";
        
        int startIndex = text.indexOf(jsonStart);
        if (startIndex >= 0) {
            startIndex += jsonStart.length();
            int endIndex = text.indexOf(jsonEnd, startIndex);
            if (endIndex > startIndex) {
                return text.substring(startIndex, endIndex).trim();
            }
        }
        
        // Fallback: find first complete JSON object
        int objectStart = text.indexOf("{");
        if (objectStart >= 0) {
            int braceCount = 0;
            int objectEnd = objectStart;
            boolean inString = false;
            boolean escaped = false;
            
            for (int i = objectStart; i < text.length(); i++) {
                char c = text.charAt(i);
                
                if (escaped) {
                    escaped = false;
                    continue;
                }
                
                if (c == '\\') {
                    escaped = true;
                    continue;
                }
                
                if (c == '"') {
                    inString = !inString;
                    continue;
                }
                
                if (!inString) {
                    if (c == '{') {
                        braceCount++;
                    } else if (c == '}') {
                        braceCount--;
                        if (braceCount == 0) {
                            objectEnd = i + 1;
                            break;
                        }
                    }
                }
            }
            
            if (braceCount == 0 && objectEnd > objectStart) {
                return text.substring(objectStart, objectEnd);
            }
        }
        
        // If no complete JSON found, try to extract partial JSON and fix it
        if (objectStart >= 0) {
            String partialJson = text.substring(objectStart);
            // Try to close the JSON by adding missing closing braces
            return fixTruncatedJson(partialJson);
        }
        
        return null;
    }
    
    private static String fixTruncatedJson(String json) {
        if (json == null || json.isEmpty()) return null;
        
        // Count opening and closing braces
        int openBraces = 0;
        int openBrackets = 0;
        boolean inString = false;
        boolean escaped = false;
        
        for (int i = 0; i < json.length(); i++) {
            char c = json.charAt(i);
            
            if (escaped) {
                escaped = false;
                continue;
            }
            
            if (c == '\\') {
                escaped = true;
                continue;
            }
            
            if (c == '"') {
                inString = !inString;
                continue;
            }
            
            if (!inString) {
                if (c == '{') openBraces++;
                else if (c == '}') openBraces--;
                else if (c == '[') openBrackets++;
                else if (c == ']') openBrackets--;
            }
        }
        
        // Add missing closing braces and brackets
        StringBuilder fixed = new StringBuilder(json);
        for (int i = 0; i < openBrackets; i++) {
            fixed.append("]");
        }
        for (int i = 0; i < openBraces; i++) {
            fixed.append("}");
        }
        
        return fixed.toString();
    }
    
    private static String fixJsonString(String jsonString) {
        if (jsonString == null) return null;
        
        // Fix common JSON issues
        String fixed = jsonString;
        
        // Remove any trailing commas before closing braces - fix regex syntax
        fixed = fixed.replaceAll(",\\s*\\}", "}");
        fixed = fixed.replaceAll(",\\s*\\]", "]");
        
        return fixed;
    }
}
