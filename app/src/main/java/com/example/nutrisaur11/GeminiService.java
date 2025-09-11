package com.example.nutrisaur11;

import android.util.Log;
import org.json.JSONArray;
import org.json.JSONObject;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.ArrayList;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class GeminiService {
    private static final String TAG = "GeminiService";
    private static final String GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";
    private static final String API_KEY = "AIzaSyAkX7Tpnsz-UnslwnmGytbnfc9XozoxtmU"; // Gemini API key
    
    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    
    public interface GeminiCallback {
        void onSuccess(List<FoodItem> foods, String summary);
        void onError(String error);
    }
    
    public void getPersonalizedFoodRecommendations(String mealCategory, int maxCalories, UserProfile userProfile, GeminiCallback callback) {
        executor.execute(() -> {
            try {
                String prompt = createPersonalizedPrompt(mealCategory, maxCalories, userProfile);
                String response = callGeminiAPI(prompt);
                List<FoodItem> foods = parseGeminiResponse(response, maxCalories);
                String summary = extractSummary(response);
                
                // Validate recommendations with clinical nutritionist standards
                NutritionistValidator.ValidationResult validation = NutritionistValidator.validateRecommendations(foods, mealCategory, maxCalories, userProfile);
                NutritionistValidator.logValidationResults(validation, mealCategory, userProfile);
                
                if (!validation.isValid) {
                    Log.e(TAG, "❌ AI recommendations failed clinical validation: " + validation.errorMessage);
                    // Still return the foods but log the errors for debugging
                }
                
                callback.onSuccess(foods, summary);
                
            } catch (Exception e) {
                Log.e(TAG, "Error getting Gemini recommendations: " + e.getMessage());
                callback.onError("Failed to get AI recommendations: " + e.getMessage());
            }
        });
    }
    
    private String createPersonalizedPrompt(String mealCategory, int maxCalories, UserProfile userProfile) {
        StringBuilder prompt = new StringBuilder();
        
        prompt.append("You are a professional nutritionist. Analyze this user's BMI and age, then recommend appropriate foods for their health goals.\n\n");
        
        // User Profile Analysis
        prompt.append("PATIENT PROFILE:\n");
        prompt.append("- Age: ").append(userProfile.getAge()).append(" years\n");
        prompt.append("- Weight: ").append(userProfile.getWeight()).append(" kg\n");
        prompt.append("- Height: ").append(userProfile.getHeight()).append(" cm\n");
        prompt.append("- BMI: ").append(String.format("%.1f", userProfile.getBmi())).append(" (").append(userProfile.getBmiCategory()).append(")\n");
        prompt.append("- Meal: ").append(mealCategory).append("\n\n");
        
        // BMI-based assessment
        String bmiCategory = userProfile.getBmiCategory().toLowerCase();
        int age = userProfile.getAge();
        
        if (bmiCategory.contains("underweight")) {
            prompt.append("NUTRITIONIST ASSESSMENT: UNDERWEIGHT (BMI ").append(String.format("%.1f", userProfile.getBmi())).append(")\n");
            prompt.append("- Goal: Weight gain and muscle building\n");
            prompt.append("- Focus: High-calorie, nutrient-dense foods\n");
            prompt.append("- Protein needs: Higher than normal\n");
        } else if (bmiCategory.contains("normal")) {
            prompt.append("NUTRITIONIST ASSESSMENT: HEALTHY WEIGHT (BMI ").append(String.format("%.1f", userProfile.getBmi())).append(")\n");
            prompt.append("- Goal: Maintain current weight and optimize health\n");
            prompt.append("- Focus: Balanced, nutrient-rich foods\n");
            prompt.append("- Protein needs: Standard (1.2-1.6g per kg)\n");
        } else if (bmiCategory.contains("overweight")) {
            prompt.append("NUTRITIONIST ASSESSMENT: OVERWEIGHT (BMI ").append(String.format("%.1f", userProfile.getBmi())).append(")\n");
            prompt.append("- Goal: Gradual weight loss\n");
            prompt.append("- Focus: High-fiber, low-calorie density foods\n");
            prompt.append("- Protein needs: Higher to preserve muscle\n");
        } else if (bmiCategory.contains("obese")) {
            prompt.append("NUTRITIONIST ASSESSMENT: OBESE (BMI ").append(String.format("%.1f", userProfile.getBmi())).append(")\n");
            prompt.append("- Goal: Significant weight loss\n");
            prompt.append("- Focus: Very low-calorie, high-nutrient foods\n");
            prompt.append("- Protein needs: High to preserve muscle during weight loss\n");
            prompt.append("- Priority: Vegetables, lean proteins, whole grains\n");
        }
        
        // Age considerations
        if (age < 18) {
            prompt.append("\nAGE CONSIDERATION: Pediatric - Ensure adequate nutrition for growth\n");
        } else if (age >= 65) {
            prompt.append("\nAGE CONSIDERATION: Geriatric - Focus on nutrient density\n");
        }
        
        prompt.append("\nNUTRITIONIST RECOMMENDATIONS:\n");
        prompt.append("think like a nutritionist and provide food that wont exceed the kcal limit but should be based on bmi and age meaning the underweight people should get food that would help them gain weight normal people to maintain their weight and overweight to loose weight this is important also the age look at the age first and make sure this apropritate for the age of that person remember think like a nutritionist or just apply common sense to know what food to recommend make sure the food is available in ph but dont focus on ph food just the availability also dont give food that dont make sense or just and ingrdient food make sure its a dish fruit or like something u can eat already give atleast 8-12 food i expect many meat for underwieght many vegi or something that would less fat for overweight and balance for normal but dont just stick to this expectation try to be intellegent nutritionist to find the best food search for food website or use ai intellegence to ability to know the best food deck to show to the user\n");
        prompt.append("1. Appropriate for the user's BMI category and health goals\n");
        prompt.append("2. Suitable for the meal category (").append(mealCategory).append(")\n");
        prompt.append("3. Nutritionally beneficial for their specific needs\n");
        prompt.append("4. Include a variety of food types\n\n");
        
        prompt.append("RESPOND IN THIS EXACT JSON FORMAT:\n");
        prompt.append("{\n");
        prompt.append("  \"foods\": [\n");
        prompt.append("    {\n");
        prompt.append("      \"id\": \"1\",\n");
        prompt.append("      \"name\": \"Food Name\",\n");
        prompt.append("      \"calories\": CALORIE_COUNT,\n");
        prompt.append("      \"serving_size\": 100,\n");
        prompt.append("      \"serving_unit\": \"g\",\n");
        prompt.append("      \"description\": \"Why this food is good for this user's BMI and age\"\n");
        prompt.append("    }\n");
        prompt.append("  ],\n");
        prompt.append("  \"summary\": \"Nutritional guidance based on BMI and age assessment\"\n");
        prompt.append("}\n\n");
        prompt.append("IMPORTANT: Return calories as numbers (e.g., 100) not as strings or arrays.");
        
        prompt.append("Focus on being a good nutritionist - assess BMI, consider age, and recommend foods that will help achieve their health goals.");
        
        return prompt.toString();
    }
    
    private String getMealTarget(String mealCategory, String bmiCategory, int age) {
        if (bmiCategory.contains("underweight")) {
            switch (mealCategory.toLowerCase()) {
                case "breakfast": return "High-calorie, protein-rich start to the day";
                case "lunch": return "Nutrient-dense, calorie-packed main meal";
                case "dinner": return "Satisfying, high-calorie evening meal";
                case "snacks": return "Calorie-dense, healthy snacks between meals";
                default: return "High-calorie, nutrient-dense foods";
            }
        } else if (bmiCategory.contains("obese")) {
            switch (mealCategory.toLowerCase()) {
                case "breakfast": return "Low-calorie, high-fiber start to the day";
                case "lunch": return "Balanced, portion-controlled main meal";
                case "dinner": return "Light, nutrient-dense evening meal";
                case "snacks": return "Low-calorie, high-volume snacks";
                default: return "Low-calorie, high-nutrient foods";
            }
        } else if (bmiCategory.contains("overweight")) {
            switch (mealCategory.toLowerCase()) {
                case "breakfast": return "Balanced, moderate-calorie start";
                case "lunch": return "Satisfying, portion-controlled meal";
                case "dinner": return "Light, balanced evening meal";
                case "snacks": return "Healthy, moderate-calorie snacks";
                default: return "Balanced, portion-controlled foods";
            }
        } else {
            switch (mealCategory.toLowerCase()) {
                case "breakfast": return "Nutritious start to maintain health";
                case "lunch": return "Balanced, satisfying main meal";
                case "dinner": return "Complete, nutritious evening meal";
                case "snacks": return "Healthy, balanced snacks";
                default: return "Balanced, nutritious foods";
            }
        }
    }
    
    private String callGeminiAPI(String prompt) throws Exception {
        URL url = new URL(GEMINI_API_URL + "?key=" + API_KEY);
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setRequestProperty("Content-Type", "application/json");
        conn.setDoOutput(true);
        
        // Create request body
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
        
        // Add generation config
        JSONObject generationConfig = new JSONObject();
        generationConfig.put("temperature", 0.7);
        generationConfig.put("maxOutputTokens", 4096); // Increased token limit
        requestBody.put("generationConfig", generationConfig);
        
        // Send request
        OutputStream os = conn.getOutputStream();
        os.write(requestBody.toString().getBytes());
        os.flush();
        os.close();
        
        // Read response
        int responseCode = conn.getResponseCode();
        BufferedReader reader;
        if (responseCode >= 200 && responseCode < 300) {
            reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
        } else {
            reader = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
        }
        
        StringBuilder response = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) {
            response.append(line);
        }
        reader.close();
        
        Log.d(TAG, "Gemini API Response Code: " + responseCode);
        Log.d(TAG, "Gemini API Response: " + response.toString());
        
        if (responseCode != 200) {
            throw new Exception("Gemini API Error " + responseCode + ": " + response.toString());
        }
        
        return response.toString();
    }
    
    private List<FoodItem> parseGeminiResponse(String response, int maxCalories) {
        List<FoodItem> foods = new ArrayList<>();
        
        try {
            JSONObject jsonResponse = new JSONObject(response);
            JSONArray candidates = jsonResponse.getJSONArray("candidates");
            JSONObject candidate = candidates.getJSONObject(0);
            JSONObject content = candidate.getJSONObject("content");
            JSONArray parts = content.getJSONArray("parts");
            JSONObject part = parts.getJSONObject(0);
            String text = part.getString("text");
            
            // Extract JSON from the text response
            // Handle both plain JSON and markdown code blocks
            String jsonString = text;
            
            // Check if it's wrapped in markdown code blocks
            if (text.contains("```json")) {
                int start = text.indexOf("```json") + 7;
                int end = text.indexOf("```", start);
                if (end > start) {
                    jsonString = text.substring(start, end).trim();
                }
            } else {
                // Look for JSON object boundaries
                int jsonStart = text.indexOf("{");
                int jsonEnd = text.lastIndexOf("}") + 1;
                if (jsonStart != -1 && jsonEnd > jsonStart) {
                    jsonString = text.substring(jsonStart, jsonEnd);
                }
            }
            
            // If JSON appears truncated, try to fix it
            if (jsonString.contains("\"foods\":") && !jsonString.endsWith("}")) {
                // Try to find the last complete food item and close the JSON
                int lastCompleteFood = jsonString.lastIndexOf("}");
                if (lastCompleteFood > 0) {
                    // Find the last complete food object
                    int foodsArrayEnd = jsonString.lastIndexOf("]");
                    if (foodsArrayEnd > 0) {
                        jsonString = jsonString.substring(0, foodsArrayEnd + 1) + ",\n  \"summary\": \"AI-generated recommendations based on BMI and age assessment\"\n}";
                        Log.d(TAG, "Fixed truncated JSON response");
                    }
                }
            }
            
            Log.d(TAG, "Extracted JSON string: " + jsonString);
            
            if (!jsonString.isEmpty()) {
                JSONObject foodData;
                try {
                    foodData = new JSONObject(jsonString);
                } catch (Exception e) {
                    Log.e(TAG, "JSON parsing failed: " + e.getMessage());
                    Log.e(TAG, "JSON string length: " + jsonString.length());
                    Log.e(TAG, "JSON string: " + jsonString);
                    return foods; // Return empty list if JSON is invalid
                }
                
                if (foodData.has("foods")) {
                    JSONArray foodsArray = foodData.getJSONArray("foods");
                    Log.d(TAG, "Found " + foodsArray.length() + " foods in JSON response");
                    
                    for (int i = 0; i < foodsArray.length(); i++) {
                    JSONObject food = foodsArray.getJSONObject(i);
                    String id = food.optString("id", String.valueOf(i + 1));
                    String name = food.optString("name", "Unknown Food");
                    
                    // Handle calories field - it might be an array, number, or string
                    int calories = 0;
                    try {
                        Object caloriesObj = food.get("calories");
                        if (caloriesObj instanceof JSONArray) {
                            JSONArray caloriesArray = (JSONArray) caloriesObj;
                            if (caloriesArray.length() > 0) {
                                calories = caloriesArray.getInt(0);
                            }
                        } else if (caloriesObj instanceof Number) {
                            calories = ((Number) caloriesObj).intValue();
                        } else if (caloriesObj instanceof String) {
                            String caloriesStr = (String) caloriesObj;
                            // Handle string format like "[100]" or "100"
                            if (caloriesStr.startsWith("[") && caloriesStr.endsWith("]")) {
                                caloriesStr = caloriesStr.substring(1, caloriesStr.length() - 1);
                            }
                            calories = Integer.parseInt(caloriesStr);
                        } else {
                            calories = food.optInt("calories", 0);
                        }
                    } catch (Exception e) {
                        Log.w(TAG, "Error parsing calories for " + name + ": " + e.getMessage());
                        calories = food.optInt("calories", 0);
                    }
                    
                    int servingSize = food.optInt("serving_size", 100);
                    String servingUnit = food.optString("serving_unit", "g");
                    String description = food.optString("description", "");
                    
                    // Include all foods regardless of calorie count - let user choose
                    if (calories > 0) {
                        foods.add(new FoodItem(id, name, calories, servingSize, servingUnit));
                        Log.d(TAG, "Added food: " + name + " (" + calories + " kcal)");
                    } else {
                        Log.d(TAG, "Skipped food: " + name + " - invalid calorie count");
                    }
                }
                } else {
                    Log.w(TAG, "No 'foods' array found in JSON response");
                }
            } else {
                Log.w(TAG, "Empty JSON string extracted from response");
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Error parsing Gemini response: " + e.getMessage());
            // No fallback - return empty list to test Gemini integration
            foods = new ArrayList<>();
        }
        
        return foods;
    }
    
    private String extractSummary(String response) {
        try {
            JSONObject jsonResponse = new JSONObject(response);
            JSONArray candidates = jsonResponse.getJSONArray("candidates");
            JSONObject candidate = candidates.getJSONObject(0);
            JSONObject content = candidate.getJSONObject("content");
            JSONArray parts = content.getJSONArray("parts");
            JSONObject part = parts.getJSONObject(0);
            String text = part.getString("text");
            
            // Extract summary from the text response
            int jsonStart = text.indexOf("{");
            int jsonEnd = text.lastIndexOf("}") + 1;
            if (jsonStart != -1 && jsonEnd > jsonStart) {
                String jsonString = text.substring(jsonStart, jsonEnd);
                JSONObject foodData = new JSONObject(jsonString);
                return foodData.optString("summary", "AI-generated personalized recommendations based on your profile.");
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Error extracting summary: " + e.getMessage());
        }
        
        return "AI-generated personalized recommendations based on your profile.";
    }
    
    private List<FoodItem> createFallbackRecommendations(int maxCalories) {
        List<FoodItem> foods = new ArrayList<>();
        
        // Basic Filipino food recommendations as fallback
        foods.add(new FoodItem("1", "Sinigang na Bangus", 120, 100, "g"));
        foods.add(new FoodItem("2", "Adobong Manok", 180, 100, "g"));
        foods.add(new FoodItem("3", "Pinakbet", 80, 100, "g"));
        foods.add(new FoodItem("4", "Tinola", 90, 100, "g"));
        foods.add(new FoodItem("5", "Ginataang Gulay", 110, 100, "g"));
        foods.add(new FoodItem("6", "Lumpiang Sariwa", 150, 100, "g"));
        foods.add(new FoodItem("7", "Kare-Kare", 200, 100, "g"));
        foods.add(new FoodItem("8", "Bicol Express", 160, 100, "g"));
        
        return filterByCalories(foods, maxCalories);
    }
    
    private List<FoodItem> filterByCalories(List<FoodItem> foods, int maxCalories) {
        List<FoodItem> filtered = new ArrayList<>();
        for (FoodItem food : foods) {
            if (food.getCalories() <= maxCalories) {
                filtered.add(food);
            }
        }
        return filtered;
    }
}

