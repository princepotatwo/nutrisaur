package com.example.nutrisaur11;

import android.content.Context;
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
import java.util.Map;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

public class GeminiService {
    private static final String TAG = "GeminiService";
    private static final String GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent";
    private static final String API_KEY = "AIzaSyAkX7Tpnsz-UnslwnmGytbnfc9XozoxtmU"; // Gemini API key
    
    private final ExecutorService executor = Executors.newSingleThreadExecutor();
    private Context context;
    
    public GeminiService() {
        // Default constructor
    }
    
    public GeminiService(Context context) {
        this.context = context;
    }
    
    public interface GeminiCallback {
        void onSuccess(List<FoodItem> foods, String summary);
        void onError(String error);
    }
    
    public interface AlternativesCallback {
        void onSuccess(List<FoodItem> foods);
        void onError(String error);
    }
    
    public void getPersonalizedFoodRecommendations(String mealCategory, int maxCalories, UserProfile userProfile, GeminiCallback callback) {
        executor.execute(() -> {
            try {
                // CRITICAL: Validate user data before processing
                NutritionDataValidator.ValidationResult dataValidation = NutritionDataValidator.validateUserProfile(
                    userProfile.getAge(), 
                    userProfile.getWeight(), 
                    userProfile.getHeight(), 
                    userProfile.getGender(), 
                    userProfile.getMedicalConditions()
                );
                
                if (!dataValidation.isValid) {
                    Log.e(TAG, "❌ Invalid user data: " + dataValidation.errorMessage);
                    callback.onError("Invalid data: " + dataValidation.errorMessage + 
                                   (dataValidation.recommendation != null ? "\n\nRecommendation: " + dataValidation.recommendation : ""));
                    return;
                }
                
                if (dataValidation.warningMessage != null) {
                    Log.w(TAG, "⚠️ Data validation warning: " + dataValidation.warningMessage);
                }
                
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
    
    public void getFoodAlternatives(String prompt, AlternativesCallback callback) {
        executor.execute(() -> {
            try {
                Log.d(TAG, "Getting food alternatives with prompt: " + prompt);
                String response = callGeminiAPI(prompt);
                List<FoodItem> foods = parseAlternativesResponse(response);
                
                callback.onSuccess(foods);
                
            } catch (Exception e) {
                Log.e(TAG, "Error getting food alternatives: " + e.getMessage());
                callback.onError("Failed to get food alternatives: " + e.getMessage());
            }
        });
    }
    
    private String createPersonalizedPrompt(String mealCategory, int maxCalories, UserProfile userProfile) {
        StringBuilder prompt = new StringBuilder();
        
        prompt.append("You are a professional nutritionist. Analyze this user's BMI and age, then recommend appropriate foods for their health goals.\n\n");
        
        // Load food preferences from questionnaire
        Map<String, String> questionnaireAnswers = FoodActivityIntegration.loadQuestionnaireAnswers(context);
        Log.d("GeminiService", "Loaded questionnaire answers: " + questionnaireAnswers.toString());
        
        // Add food preferences to prompt - OPTIMIZED VERSION
        String combinedPreferences = questionnaireAnswers.get("food_preferences_combined");
        if (combinedPreferences != null && !combinedPreferences.isEmpty()) {
            prompt.append("FOOD PREFERENCES:\n");
            prompt.append(combinedPreferences).append("\n\n");
            
            // SUPER SMART QUESTIONNAIRE IMPLEMENTATION - OPTIMIZED
            buildOptimizedDietaryRequirements(prompt, questionnaireAnswers);
            buildOptimizedAllergyRequirements(prompt, questionnaireAnswers);
            buildOptimizedBudgetConsiderations(prompt, questionnaireAnswers);
            buildOptimizedCravingsIntegration(prompt, questionnaireAnswers);
            
            Log.d("GeminiService", "Using optimized combined food preferences in prompt");
        } else {
            Log.w("GeminiService", "No questionnaire data found - using basic recommendations");
        }
        
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
        
        // CRITICAL: Check for impossible/extreme values first
        if (age == 1 && userProfile.getWeight() > 1000 && userProfile.getHeight() > 1000) {
            prompt.append("\n🚨 IMPOSSIBLE DATA DETECTED: Age ").append(age).append(", Weight ").append(userProfile.getWeight()).append(" kg, Height ").append(userProfile.getHeight()).append(" cm\n");
            prompt.append("- These measurements are medically impossible for a 1-year-old\n");
            prompt.append("- Please verify measurements and enter realistic values\n");
            prompt.append("- For a 1-year-old: Weight should be 7-12 kg, Height should be 70-80 cm\n");
            prompt.append("- Cannot provide accurate nutrition recommendations with invalid data\n");
            return prompt.toString();
        }
        
        if (userProfile.getWeight() > 500 || userProfile.getHeight() > 250) {
            prompt.append("\n⚠️ EXTREME VALUES WARNING: Weight ").append(userProfile.getWeight()).append(" kg, Height ").append(userProfile.getHeight()).append(" cm\n");
            prompt.append("- These values are extremely high and may be incorrect\n");
            prompt.append("- Please verify measurements are accurate\n");
            prompt.append("- Proceeding with caution - recommendations may not be optimal\n\n");
        }
        
        // Age-specific considerations
        if (age < 2) {
            prompt.append("\n🚨 CRITICAL: INFANT NUTRITION (Age ").append(age).append(")\n");
            prompt.append("- This is an INFANT requiring specialized nutrition\n");
            prompt.append("- Focus on: Breast milk/formula, pureed foods, soft textures\n");
            prompt.append("- Calorie needs: 90-120 kcal/kg body weight (much higher than adults)\n");
            prompt.append("- Protein needs: 1.2-1.5g per kg body weight\n");
            prompt.append("- Fat needs: 30-40% of calories (essential for brain development)\n");
            prompt.append("- NO hard foods, choking hazards, or adult portions\n");
            prompt.append("- Recommend: Rice porridge, mashed vegetables, soft fruits, pureed meats\n");
        } else if (age < 5) {
            prompt.append("\n⚠️ IMPORTANT: TODDLER NUTRITION (Age ").append(age).append(")\n");
            prompt.append("- This is a TODDLER requiring age-appropriate nutrition\n");
            prompt.append("- Focus on: Small portions, finger foods, variety introduction\n");
            prompt.append("- Calorie needs: 1000-1400 calories per day\n");
            prompt.append("- Protein needs: 1.1-1.3g per kg body weight\n");
            prompt.append("- Fat needs: 25-35% of calories\n");
            prompt.append("- Recommend: Small portions of Filipino dishes, soft rice, vegetables, fruits\n");
            prompt.append("- Avoid: Spicy foods, large portions, choking hazards\n");
        } else if (age < 12) {
            prompt.append("\n⚠️ IMPORTANT: CHILD NUTRITION (Age ").append(age).append(")\n");
            prompt.append("- This is a SCHOOL-AGE CHILD requiring growth-focused nutrition\n");
            prompt.append("- Focus on: Balanced meals, regular snacks, variety\n");
            prompt.append("- Calorie needs: 1200-2000 calories per day (depending on activity)\n");
            prompt.append("- Protein needs: 1.0-1.2g per kg body weight\n");
            prompt.append("- Fat needs: 25-35% of calories\n");
            prompt.append("- Recommend: Child-sized portions of Filipino dishes, fruits, vegetables\n");
            prompt.append("- Include: Milk, eggs, fish, chicken, rice, vegetables\n");
        } else if (age < 18) {
            prompt.append("\n⚠️ IMPORTANT: ADOLESCENT NUTRITION (Age ").append(age).append(")\n");
            prompt.append("- This is an ADOLESCENT in rapid growth phase\n");
            prompt.append("- Focus on: Higher calorie needs, increased protein, calcium for bone growth\n");
            prompt.append("- Calorie needs: 1800-2800 calories per day (depending on gender/activity)\n");
            prompt.append("- Protein needs: 0.9-1.1g per kg body weight\n");
            prompt.append("- Fat needs: 20-35% of calories\n");
            prompt.append("- Recommend: Larger portions of Filipino dishes, dairy, lean proteins\n");
            prompt.append("- Include: Rice, meat, fish, vegetables, fruits, milk\n");
        } else if (age >= 65) {
            prompt.append("\nAGE CONSIDERATION: Geriatric - Focus on nutrient density\n");
        }
        
        prompt.append("\nNUTRITIONIST RECOMMENDATIONS:\n");
        if (age < 18) {
            prompt.append("Provide 8-12 food recommendations that are:\n");
            prompt.append("1. AGE-APPROPRIATE for ").append(age).append(" year old (NOT adult portions)\n");
            prompt.append("2. GROWTH-FOCUSED nutrition (not weight loss/maintenance)\n");
            prompt.append("3. Suitable for the meal category (").append(mealCategory).append(")\n");
            prompt.append("4. Available in the Philippines\n");
            prompt.append("5. Child-friendly textures and portions\n");
            prompt.append("6. High in nutrients needed for growth and development\n");
            prompt.append("7. Consider cultural preferences and family meals\n");
            if (age < 5) {
                prompt.append("8. SOFT, EASY-TO-EAT foods (no choking hazards)\n");
                prompt.append("9. SMALL PORTIONS appropriate for child's age\n");
            }
        } else {
            prompt.append("Provide 8-12 food recommendations that are:\n");
            prompt.append("1. Appropriate for the user's BMI category and health goals\n");
            prompt.append("2. Suitable for the meal category (").append(mealCategory).append(")\n");
            prompt.append("3. Available in the Philippines\n");
            prompt.append("4. Complete dishes, not just ingredients\n");
            prompt.append("5. Variety of food types\n");
            prompt.append("6. Consider cultural preferences\n");
        }
        prompt.append("\n");
        
        prompt.append("RESPOND IN THIS EXACT JSON FORMAT:\n");
        prompt.append("{\n");
        prompt.append("  \"foods\": [\n");
        prompt.append("    {\n");
        prompt.append("      \"id\": \"1\",\n");
        prompt.append("      \"name\": \"Filipino Food Name Only\",\n");
        prompt.append("      \"calories\": CALORIE_COUNT,\n");
        prompt.append("      \"protein\": PROTEIN_GRAMS,\n");
        prompt.append("      \"carbs\": CARBS_GRAMS,\n");
        prompt.append("      \"fat\": FAT_GRAMS,\n");
        if (age < 18) {
            prompt.append("      \"serving_size\": ").append(age < 5 ? "50" : age < 12 ? "75" : "100").append(",\n");
            prompt.append("      \"serving_unit\": \"g\",\n");
            prompt.append("      \"description\": \"Why this food is good for this ").append(age).append(" year old's growth and development\"\n");
        } else {
            prompt.append("      \"serving_size\": 100,\n");
            prompt.append("      \"serving_unit\": \"g\",\n");
            prompt.append("      \"description\": \"Why this food is good for this user's BMI and age\"\n");
        }
        prompt.append("    }\n");
        prompt.append("  ],\n");
        if (age < 18) {
            prompt.append("  \"summary\": \"Age-appropriate nutritional guidance for ").append(age).append(" year old - focus on growth and development\"\n");
        } else {
            prompt.append("  \"summary\": \"Nutritional guidance based on BMI and age assessment\"\n");
        }
        prompt.append("}\n\n");
        prompt.append("IMPORTANT: Return calories, protein, carbs, and fat as numbers (e.g., 100, 25, 30, 15) not as strings or arrays.");
        prompt.append("\n\nFOOD NAMING RULES:");
        prompt.append("\n- Use ONLY Filipino food names that most Filipinos commonly use");
        prompt.append("\n- NO parentheses, descriptions, or additional text in the food name");
        prompt.append("\n- NO English translations or explanations in the name");
        prompt.append("\n- Examples: 'Adobo', 'Sinigang', 'Kare-kare', 'Pancit', 'Lumpia'");
        prompt.append("\n- NOT: 'Adobo (Filipino stewed meat)', 'Sinigang - sour soup', 'Kare-kare (peanut stew)'");
        if (age < 18) {
            prompt.append("\n- For children, use smaller serving sizes and focus on growth nutrition.");
        }
        
        prompt.append("Focus on being a good nutritionist - assess BMI, consider age, and recommend foods that will help achieve their health goals.");
        
        String finalPrompt = prompt.toString();
        Log.d("GeminiService", "=== FINAL PROMPT SENT TO AI ===");
        Log.d("GeminiService", "Prompt length: " + finalPrompt.length() + " characters");
        Log.d("GeminiService", "Full prompt:\n" + finalPrompt);
        Log.d("GeminiService", "=== END OF PROMPT ===");
        
        return finalPrompt;
    }
    
    private String getMealTarget(String mealCategory, String bmiCategory, int age) {
        // Age-specific meal guidance (most important for children)
        if (age < 2) {
            // INFANT MEAL GUIDANCE
            switch (mealCategory.toLowerCase()) {
                case "breakfast": return "Soft, pureed foods - rice porridge, mashed banana, soft egg";
                case "lunch": return "Pureed vegetables and meat - mashed carrots, soft chicken";
                case "dinner": return "Soft, easy-to-digest foods - rice porridge, mashed fish";
                case "snacks": return "Soft fruits and milk - mashed avocado, breast milk/formula";
                default: return "Soft, pureed, age-appropriate foods";
            }
        } else if (age < 5) {
            // TODDLER MEAL GUIDANCE
            switch (mealCategory.toLowerCase()) {
                case "breakfast": return "Small portions, finger foods - small rice balls, soft fruits";
                case "lunch": return "Child-sized portions - small adobo, soft vegetables";
                case "dinner": return "Easy-to-eat foods - soft rice, tender meat, cooked vegetables";
                case "snacks": return "Healthy finger foods - small fruits, crackers, milk";
                default: return "Small, soft, child-friendly portions";
            }
        } else if (age < 12) {
            // SCHOOL-AGE CHILD MEAL GUIDANCE
            switch (mealCategory.toLowerCase()) {
                case "breakfast": return "Energy-rich start - rice, egg, milk for school energy";
                case "lunch": return "Balanced school meal - rice, meat, vegetables for growth";
                case "dinner": return "Family meal - complete Filipino dishes in child portions";
                case "snacks": return "Growth snacks - fruits, nuts, milk for development";
                default: return "Child-appropriate portions of Filipino dishes";
            }
        } else if (age < 18) {
            // ADOLESCENT MEAL GUIDANCE
            switch (mealCategory.toLowerCase()) {
                case "breakfast": return "High-energy start - larger portions for growth spurt";
                case "lunch": return "Nutrient-dense meal - protein and carbs for development";
                case "dinner": return "Complete family meal - full portions for growth";
                case "snacks": return "Healthy growth snacks - protein and calcium rich";
                default: return "Larger portions for adolescent growth needs";
            }
        }
        
        // ADULT MEAL GUIDANCE (existing logic)
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
                } else {
                    // If no closing ``` found, try to find the end of the JSON object
                    int jsonStart = text.indexOf("{", start);
                    int jsonEnd = text.lastIndexOf("}") + 1;
                    if (jsonStart != -1 && jsonEnd > jsonStart) {
                        jsonString = text.substring(jsonStart, jsonEnd);
                    }
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
                // Clean up the JSON string - remove any trailing text after the closing brace
                jsonString = jsonString.trim();
                if (jsonString.endsWith("}")) {
                    // Find the last complete closing brace
                    int lastBrace = jsonString.lastIndexOf("}");
                    if (lastBrace > 0) {
                        jsonString = jsonString.substring(0, lastBrace + 1);
                    }
                }
                
                JSONObject foodData;
                try {
                    foodData = new JSONObject(jsonString);
                } catch (Exception e) {
                    Log.e(TAG, "JSON parsing failed: " + e.getMessage());
                    Log.e(TAG, "JSON string length: " + jsonString.length());
                    Log.e(TAG, "JSON string: " + jsonString);
                    // Try to fix common JSON issues
                    try {
                        // Remove any trailing text that might be causing issues
                        String cleanedJson = jsonString.replaceAll("\\s*\\*\\*.*$", "").trim();
                        if (cleanedJson.endsWith("}")) {
                            foodData = new JSONObject(cleanedJson);
                            Log.d(TAG, "Successfully parsed cleaned JSON");
                        } else {
                            return foods; // Return empty list if JSON is still invalid
                        }
                    } catch (Exception e2) {
                        Log.e(TAG, "JSON parsing failed even after cleaning: " + e2.getMessage());
                        return foods; // Return empty list if JSON is invalid
                    }
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
                    
                    // Parse macronutrients
                    double protein = food.optDouble("protein", 0.0);
                    double carbs = food.optDouble("carbs", 0.0);
                    double fat = food.optDouble("fat", 0.0);
                    
                    // Include all foods regardless of calorie count - let user choose
                    if (calories > 0) {
                        FoodItem foodItem = new FoodItem(id, name, calories, servingSize, servingUnit);
                        foodItem.setProtein(protein);
                        foodItem.setCarbs(carbs);
                        foodItem.setFat(fat);
                        foodItem.setDescription(description);
                        foods.add(foodItem);
                        Log.d(TAG, "Added food: " + name + " (" + calories + " kcal, " + protein + "g protein, " + carbs + "g carbs, " + fat + "g fat)");
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
    
    private List<FoodItem> parseAlternativesResponse(String response) {
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
            String jsonString = text;
            
            // Check if it's wrapped in markdown code blocks
            if (text.contains("```json")) {
                int start = text.indexOf("```json") + 7;
                int end = text.indexOf("```", start);
                if (end > start) {
                    jsonString = text.substring(start, end).trim();
                } else {
                    // If no closing ``` found, try to find the end of the JSON array
                    int jsonStart = text.indexOf("[", start);
                    int jsonEnd = text.lastIndexOf("]") + 1;
                    if (jsonStart != -1 && jsonEnd > jsonStart) {
                        jsonString = text.substring(jsonStart, jsonEnd);
                    }
                }
            } else {
                // Look for JSON array boundaries
                int jsonStart = text.indexOf("[");
                int jsonEnd = text.lastIndexOf("]") + 1;
                if (jsonStart != -1 && jsonEnd > jsonStart) {
                    jsonString = text.substring(jsonStart, jsonEnd);
                }
            }
            
            Log.d(TAG, "Extracted alternatives JSON string: " + jsonString);
            
            if (!jsonString.isEmpty()) {
                // Clean up the JSON string - remove any trailing text after the closing bracket
                jsonString = jsonString.trim();
                if (jsonString.endsWith("]")) {
                    // Find the last complete closing bracket
                    int lastBracket = jsonString.lastIndexOf("]");
                    if (lastBracket > 0) {
                        jsonString = jsonString.substring(0, lastBracket + 1);
                    }
                }
                
                JSONArray alternativesArray;
                try {
                    alternativesArray = new JSONArray(jsonString);
                } catch (Exception e) {
                    Log.e(TAG, "JSON parsing failed for alternatives: " + e.getMessage());
                    Log.e(TAG, "Problematic JSON: " + jsonString);
                    // Try to fix common JSON issues
                    try {
                        // Remove any trailing text that might be causing issues
                        String cleanedJson = jsonString.replaceAll("\\s*\\*\\*.*$", "").trim();
                        if (cleanedJson.endsWith("]")) {
                            alternativesArray = new JSONArray(cleanedJson);
                            Log.d(TAG, "Successfully parsed cleaned JSON");
                        } else {
                            return foods; // Return empty list if JSON is still invalid
                        }
                    } catch (Exception e2) {
                        Log.e(TAG, "JSON parsing failed even after cleaning: " + e2.getMessage());
                        return foods; // Return empty list if JSON is invalid
                    }
                }
                
                Log.d(TAG, "Found " + alternativesArray.length() + " alternatives in JSON response");
                
                for (int i = 0; i < alternativesArray.length(); i++) {
                    JSONObject food = alternativesArray.getJSONObject(i);
                    String id = food.optString("id", "alt_" + (i + 1));
                    String name = food.optString("name", "Unknown Alternative");
                    
                    // Handle calories field
                    int calories = 0;
                    try {
                        Object caloriesObj = food.get("calories");
                        if (caloriesObj instanceof Number) {
                            calories = ((Number) caloriesObj).intValue();
                        } else if (caloriesObj instanceof String) {
                            calories = Integer.parseInt((String) caloriesObj);
                        } else {
                            calories = food.optInt("calories", 0);
                        }
                    } catch (Exception e) {
                        Log.w(TAG, "Error parsing calories for " + name + ": " + e.getMessage());
                        calories = food.optInt("calories", 0);
                    }
                    
                    int servingSize = food.optInt("servingSize", 100);
                    String servingUnit = food.optString("unit", "g");
                    String reason = food.optString("reason", "");
                    
                    // Get nutritional values
                    double protein = food.optDouble("protein", 0.0);
                    double carbs = food.optDouble("carbs", 0.0);
                    double fat = food.optDouble("fat", 0.0);
                    double fiber = food.optDouble("fiber", 0.0);
                    
                    if (calories > 0) {
                        FoodItem foodItem = new FoodItem(id, name, calories, servingSize, servingUnit);
                        foodItem.setAlternativeReason(reason);
                        foodItem.setProtein(protein);
                        foodItem.setCarbs(carbs);
                        foodItem.setFat(fat);
                        foodItem.setFiber(fiber);
                        foods.add(foodItem);
                        Log.d(TAG, "Added alternative: " + name + " (" + calories + " kcal) - " + reason);
                    } else {
                        Log.d(TAG, "Skipped alternative: " + name + " - invalid calorie count");
                    }
                }
            } else {
                Log.w(TAG, "Empty JSON string extracted from alternatives response");
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Error parsing alternatives response: " + e.getMessage());
            foods = new ArrayList<>();
        }
        
        return foods;
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
    
    // ========================================
    // SUPER SMART QUESTIONNAIRE IMPLEMENTATION
    // ========================================
    
    private void buildOptimizedDietaryRequirements(StringBuilder prompt, Map<String, String> answers) {
        String diet = answers.get("question_0");
        if (diet == null || diet.isEmpty() || diet.equals("SKIPPED")) return;
        
        prompt.append("DIETARY REQUIREMENTS:\n");
        
        switch (diet.toUpperCase()) {
            case "VEGETARIAN":
                prompt.append("• No meat/poultry/fish. Use: tofu, beans, lentils, quinoa, nuts, dairy, eggs\n");
                break;
            case "VEGAN":
                prompt.append("• No animal products. Use: tofu, tempeh, beans, lentils, quinoa, nuts, plant milks\n");
                break;
            case "PESCATARIAN":
                prompt.append("• Vegetarian + fish/seafood. Use: fish, tofu, beans, lentils, dairy, eggs\n");
                break;
            case "FLEXITARIAN":
                prompt.append("• Plant-focused with occasional meat/fish. Emphasize plants, moderate animal products\n");
                break;
            case "KETO":
                prompt.append("• Low carb (5-10%), high fat (70-80%), moderate protein. Use: avocados, nuts, oils, fatty fish\n");
                break;
            case "PALEO":
                prompt.append("• Whole foods only. Use: meat, fish, eggs, vegetables, fruits, nuts. Avoid: grains, legumes, dairy\n");
                break;
            case "MEDITERRANEAN":
                prompt.append("• Heart-healthy. Use: olive oil, fish, vegetables, fruits, whole grains, nuts, legumes\n");
                break;
            case "STANDARD EATER":
                prompt.append("• Balanced approach with all food groups. Focus on variety and moderation\n");
                break;
        }
        prompt.append("\n");
    }
    
    private void buildOptimizedAllergyRequirements(StringBuilder prompt, Map<String, String> answers) {
        String allergies = answers.get("question_1");
        if (allergies == null || allergies.isEmpty() || allergies.equals("SKIPPED")) return;
        
        prompt.append("CRITICAL ALLERGY REQUIREMENTS:\n");
        String[] allergyList = allergies.split(",");
        
        for (String allergy : allergyList) {
            allergy = allergy.trim().toUpperCase();
            switch (allergy) {
                case "FISH":
                    prompt.append("• NO FISH/SEAFOOD: Avoid fish, shellfish, fish sauce, bagoong, tuyo. Use: plant omega-3, seaweed\n");
                    break;
                case "PEANUTS":
                    prompt.append("• NO PEANUTS: Avoid peanuts, peanut butter, peanut oil. Use: other nuts, seeds, tahini\n");
                    break;
                case "MILK":
                    prompt.append("• NO DAIRY: Avoid milk, cheese, yogurt, butter. Use: plant milks, nutritional yeast\n");
                    break;
                case "EGGS":
                    prompt.append("• NO EGGS: Avoid eggs, mayonnaise, some pastas. Use: flax eggs, aquafaba\n");
                    break;
                case "GLUTEN":
                    prompt.append("• NO GLUTEN: Avoid wheat, barley, rye, most breads. Use: rice, quinoa, corn, potatoes\n");
                    break;
                case "SOY":
                    prompt.append("• NO SOY: Avoid tofu, tempeh, soy sauce, miso. Use: other beans, chickpeas, lentils\n");
                    break;
                case "TREE NUTS":
                    prompt.append("• NO TREE NUTS: Avoid almonds, walnuts, cashews. Use: seeds, coconut, peanuts (if safe)\n");
                    break;
            }
        }
        prompt.append("• Suggest safe alternatives that maintain cultural authenticity\n\n");
    }
    
    
    private void buildOptimizedBudgetConsiderations(StringBuilder prompt, Map<String, String> answers) {
        String budget = answers.get("question_4");
        if (budget == null || budget.isEmpty() || budget.equals("SKIPPED")) return;
        
        prompt.append("BUDGET CONSIDERATIONS:\n");
        
        if (budget.contains("₱0-50") || budget.contains("0-50")) {
            prompt.append("• ₱0-50/day: Use eggs, beans, rice, seasonal vegetables. Buy bulk, local markets\n");
        } else if (budget.contains("₱50-100") || budget.contains("50-100")) {
            prompt.append("• ₱50-100/day: Mix of chicken, fish, eggs, beans, whole grains, fresh/frozen vegetables\n");
        } else if (budget.contains("₱100-200") || budget.contains("100-200")) {
            prompt.append("• ₱100-200/day: Quality proteins, whole grains, organic options, variety\n");
        } else if (budget.contains("₱200+") || budget.contains("200+")) {
            prompt.append("• ₱200+/day: Premium proteins, exotic fruits, organic produce, specialty items\n");
        }
        prompt.append("\n");
    }
    
    private void buildOptimizedCravingsIntegration(StringBuilder prompt, Map<String, String> answers) {
        String cravings = answers.get("question_2");
        if (cravings == null || cravings.isEmpty() || cravings.equals("SKIPPED")) return;
        
        prompt.append("FLAVOR PREFERENCES:\n");
        String[] cravingList = cravings.split(",");
        
        for (String craving : cravingList) {
            craving = craving.trim().toUpperCase();
            switch (craving) {
                case "SWEET":
                    prompt.append("• SWEET: Use fruits, dates, honey, dark chocolate. Avoid refined sugars\n");
                    break;
                case "SPICY":
                    prompt.append("• SPICY: Use chili peppers, ginger, garlic, cayenne. Boosts metabolism\n");
                    break;
                case "SALTY":
                    prompt.append("• SALTY: Use sea salt, olives, seaweed, nuts. Watch sodium intake\n");
                    break;
                case "CRUNCHY":
                    prompt.append("• CRUNCHY: Use raw vegetables, nuts, seeds, roasted chickpeas\n");
                    break;
                case "CREAMY":
                    prompt.append("• CREAMY: Use avocados, nut butters, coconut milk, Greek yogurt\n");
                    break;
                case "UMAMI":
                    prompt.append("• UMAMI: Use mushrooms, tomatoes, seaweed, fermented foods\n");
                    break;
            }
        }
        prompt.append("\n");
    }
}

