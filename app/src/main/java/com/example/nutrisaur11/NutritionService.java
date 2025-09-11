package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import org.json.JSONObject;
import org.json.JSONException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import okhttp3.*;
import java.io.IOException;

/**
 * Service to handle nutrition recommendations from Gemini API
 * Uses JSON format for easy data extraction and UI updates
 */
public class NutritionService {
    private static final String TAG = "NutritionService";
    private static final String GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
    private static final String API_KEY = "AIzaSyAkX7Tpnsz-UnslwnmGytbnfc9XozoxtmU";
    
    private Context context;
    private ExecutorService executorService;
    private OkHttpClient httpClient;
    private SharedPreferences prefs;

    public NutritionService(Context context) {
        this.context = context;
        this.executorService = Executors.newSingleThreadExecutor();
        this.httpClient = new OkHttpClient();
        this.prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
    }

    /**
     * Interface for nutrition data callbacks
     */
    public interface NutritionCallback {
        void onSuccess(NutritionData nutritionData);
        void onError(String error);
    }

    /**
     * Get personalized nutrition recommendations
     */
    public void getNutritionRecommendations(NutritionCallback callback) {
        executorService.execute(() -> {
            try {
                Log.d(TAG, "Starting nutrition recommendations...");
                
                // Get user profile data
                UserProfile userProfile = getUserProfile();
                if (userProfile == null) {
                    Log.e(TAG, "User profile is null - personalization not completed");
                    callback.onError("User profile not found. Please complete personalization first.");
                    return;
                }
                
                Log.d(TAG, "User profile loaded: " + userProfile.getName() + ", BMI: " + userProfile.getBmi());

                // Create nutritionist-level prompt
                String prompt = createNutritionPrompt(userProfile);
                Log.d(TAG, "Generated prompt: " + prompt);
                
                // Call Gemini API
                Log.d(TAG, "Calling Gemini API...");
                String response = callGeminiAPI(prompt);
                Log.d(TAG, "Gemini API response: " + (response != null ? "Success" : "Failed"));
                NutritionData nutritionData;
                
                if (response == null) {
                    Log.w(TAG, "Gemini API failed, using fallback nutrition data");
                    // Use fallback nutrition data based on user profile
                    nutritionData = generateFallbackNutritionData(userProfile);
                } else {
                    // Parse JSON response
                    nutritionData = parseNutritionResponse(response, userProfile);
                    if (nutritionData == null) {
                        Log.w(TAG, "Failed to parse Gemini response, using fallback nutrition data");
                        nutritionData = generateFallbackNutritionData(userProfile);
                    }
                }

                // Return success
                callback.onSuccess(nutritionData);

            } catch (Exception e) {
                Log.e(TAG, "Error getting nutrition recommendations: " + e.getMessage());
                callback.onError("Error: " + e.getMessage());
            }
        });
    }

    /**
     * Get personalized nutrition recommendations with user data from community_users table
     */
    public void getNutritionRecommendationsWithUserData(java.util.Map<String, String> userData, NutritionCallback callback) {
        executorService.execute(() -> {
            try {
                Log.d(TAG, "Starting nutrition recommendations with database user data...");
                
                // Create user profile from database data
                UserProfile userProfile = createUserProfileFromDatabaseData(userData);
                if (userProfile == null) {
                    Log.e(TAG, "Invalid user data from database");
                    callback.onError("Invalid user data from database");
                    return;
                }
                
                Log.d(TAG, "User profile created from database: " + userProfile.getName() + ", BMI: " + userProfile.getBmi());

                // Create nutritionist-level prompt
                String prompt = createNutritionPrompt(userProfile);
                Log.d(TAG, "Generated prompt with database data: " + prompt);
                
                // Call Gemini API
                Log.d(TAG, "Calling Gemini API with database data...");
                String response = callGeminiAPI(prompt);
                Log.d(TAG, "Gemini API response: " + (response != null ? "Success" : "Failed"));
                NutritionData nutritionData;
                
                if (response == null) {
                    Log.w(TAG, "Gemini API failed, using fallback nutrition data");
                    // Use fallback nutrition data based on user profile
                    nutritionData = generateFallbackNutritionData(userProfile);
                } else {
                    // Parse JSON response
                    nutritionData = parseNutritionResponse(response, userProfile);
                    if (nutritionData == null) {
                        Log.w(TAG, "Failed to parse Gemini response, using fallback nutrition data");
                        nutritionData = generateFallbackNutritionData(userProfile);
                    }
                }

                // Return success
                callback.onSuccess(nutritionData);

            } catch (Exception e) {
                Log.e(TAG, "Error getting nutrition recommendations: " + e.getMessage());
                callback.onError("Error: " + e.getMessage());
            }
        });
    }

    /**
     * Get user profile from SharedPreferences
     */
    private UserProfile getUserProfile() {
        try {
            Log.d(TAG, "Getting user profile from SharedPreferences...");
            
            String userId = prefs.getString("user_id", "");
            String name = prefs.getString("user_name", "");
            int age = prefs.getInt("user_age", 25);
            String gender = prefs.getString("user_gender", "Male");
            double weight = Double.parseDouble(prefs.getString("user_weight", "70"));
            double height = Double.parseDouble(prefs.getString("user_height", "170"));
            String activityLevel = prefs.getString("activity_level", "Moderately Active");
            String healthGoals = prefs.getString("health_goals", "Maintain weight");
            String dietaryPreferences = prefs.getString("dietary_preferences", "None");
            String allergies = prefs.getString("allergies", "None");
            String medicalConditions = prefs.getString("medical_conditions", "None");
            boolean isPregnant = prefs.getBoolean("is_pregnant", false);
            int pregnancyWeek = prefs.getInt("pregnancy_week", 0);
            String occupation = prefs.getString("occupation", "Office worker");
            String lifestyle = prefs.getString("lifestyle", "Moderate");
            
            Log.d(TAG, "User data: name=" + name + ", age=" + age + ", weight=" + weight + ", height=" + height);

            return new UserProfile(userId, name, age, gender, weight, height, activityLevel,
                    healthGoals, dietaryPreferences, allergies, medicalConditions, isPregnant,
                    pregnancyWeek, occupation, lifestyle);

        } catch (Exception e) {
            Log.e(TAG, "Error getting user profile: " + e.getMessage());
            return null;
        }
    }

    /**
     * Create user profile from community_users table data
     */
    private UserProfile createUserProfileFromDatabaseData(java.util.Map<String, String> userData) {
        try {
            Log.d(TAG, "Creating user profile from community_users table data...");
            
            // Extract data from database map
            String userId = userData.getOrDefault("email", "");
            String name = userData.getOrDefault("name", "");
            int age = 25; // Default age
            try {
                age = Integer.parseInt(userData.getOrDefault("age", "25"));
            } catch (NumberFormatException e) {
                Log.w(TAG, "Could not parse age, using default: 25");
            }
            
            String gender = userData.getOrDefault("sex", "Male");
            double weight = 70.0; // Default weight
            try {
                weight = Double.parseDouble(userData.getOrDefault("weight_kg", "70"));
            } catch (NumberFormatException e) {
                Log.w(TAG, "Could not parse weight, using default: 70");
            }
            
            double height = 170.0; // Default height
            try {
                height = Double.parseDouble(userData.getOrDefault("height_cm", "170"));
            } catch (NumberFormatException e) {
                Log.w(TAG, "Could not parse height, using default: 170");
            }
            
            // Set default values for missing fields
            String activityLevel = "Moderately Active";
            String healthGoals = "Maintain weight";
            String dietaryPreferences = "None";
            String allergies = "None";
            String medicalConditions = "None";
            boolean isPregnant = "1".equals(userData.getOrDefault("is_pregnant", "0")) || 
                               "Yes".equals(userData.getOrDefault("is_pregnant", "No"));
            int pregnancyWeek = 0;
            String occupation = "Office worker";
            String lifestyle = "Moderate";
            
            Log.d(TAG, "Database user data: name=" + name + ", age=" + age + ", weight=" + weight + ", height=" + height + ", gender=" + gender);
            
            return new UserProfile(userId, name, age, gender, weight, height, activityLevel,
                    healthGoals, dietaryPreferences, allergies, medicalConditions, isPregnant,
                    pregnancyWeek, occupation, lifestyle);

        } catch (Exception e) {
            Log.e(TAG, "Error creating user profile from database data: " + e.getMessage());
            return null;
        }
    }

    /**
     * Create comprehensive nutritionist-level prompt
     */
    private String createNutritionPrompt(UserProfile userProfile) {
        StringBuilder prompt = new StringBuilder();
        
        prompt.append("You are a professional nutritionist. Analyze this user profile and provide personalized daily nutrition recommendations in JSON format.\n\n");
        
        // User Profile
        prompt.append("USER PROFILE:\n");
        prompt.append("- Name: ").append(userProfile.getName()).append("\n");
        prompt.append("- Age: ").append(userProfile.getAge()).append(" years\n");
        prompt.append("- Gender: ").append(userProfile.getGender()).append("\n");
        prompt.append("- Weight: ").append(userProfile.getWeight()).append(" kg\n");
        prompt.append("- Height: ").append(userProfile.getHeight()).append(" cm\n");
        prompt.append("- BMI: ").append(String.format("%.1f", userProfile.getBmi())).append(" (").append(userProfile.getBmiCategory()).append(")\n");
        prompt.append("- Activity Level: ").append(userProfile.getActivityLevel()).append("\n");
        prompt.append("- Health Goals: ").append(userProfile.getHealthGoals()).append("\n");
        prompt.append("- Dietary Preferences: ").append(userProfile.getDietaryPreferences()).append("\n");
        prompt.append("- Allergies: ").append(userProfile.getAllergies()).append("\n");
        prompt.append("- Medical Conditions: ").append(userProfile.getMedicalConditions()).append("\n");
        prompt.append("- Occupation: ").append(userProfile.getOccupation()).append("\n");
        prompt.append("- Lifestyle: ").append(userProfile.getLifestyle()).append("\n");
        
        if (userProfile.isPregnant()) {
            prompt.append("- Pregnancy: Week ").append(userProfile.getPregnancyWeek()).append("\n");
        }
        
        prompt.append("\nNUTRITIONIST ANALYSIS REQUIRED:\n");
        prompt.append("1. Calculate optimal daily calorie intake based on user profile\n");
        prompt.append("2. Determine macronutrient distribution (carbs, protein, fat)\n");
        prompt.append("3. Distribute calories across meals (breakfast, lunch, dinner, snacks)\n");
        prompt.append("4. Consider BMI category for weight management recommendations\n");
        prompt.append("5. Account for activity level and lifestyle factors\n");
        prompt.append("6. Provide specific meal recommendations\n\n");
        
        // Add specific guidance for obese users
        if (userProfile.getBmi() >= 30) {
            prompt.append("IMPORTANT: This user is OBESE (BMI ").append(String.format("%.1f", userProfile.getBmi())).append("). ");
            prompt.append("Recommend a calorie DEFICIT for weight loss. ");
            prompt.append("For obese individuals, suggest 1200-1500 calories per day maximum. ");
            prompt.append("Focus on nutrient-dense, low-calorie foods.\n\n");
        } else if (userProfile.getBmi() >= 25) {
            prompt.append("IMPORTANT: This user is OVERWEIGHT (BMI ").append(String.format("%.1f", userProfile.getBmi())).append("). ");
            prompt.append("Recommend a moderate calorie deficit for healthy weight loss. ");
            prompt.append("Suggest 1500-1800 calories per day.\n\n");
        }
        
        prompt.append("RESPOND IN THIS EXACT JSON FORMAT:\n");
        prompt.append("{\n");
        prompt.append("  \"totalCalories\": [CALCULATE_BASED_ON_BMI_AND_GOALS],\n");
        prompt.append("  \"caloriesLeft\": [SAME_AS_TOTAL_CALORIES],\n");
        prompt.append("  \"caloriesEaten\": 0,\n");
        prompt.append("  \"caloriesBurned\": 0,\n");
        prompt.append("  \"macronutrients\": {\n");
        prompt.append("    \"carbs\": 0,\n");
        prompt.append("    \"protein\": 0,\n");
        prompt.append("    \"fat\": 0,\n");
        prompt.append("    \"carbsTarget\": [CALCULATE_BASED_ON_CALORIES],\n");
        prompt.append("    \"proteinTarget\": [CALCULATE_BASED_ON_CALORIES],\n");
        prompt.append("    \"fatTarget\": [CALCULATE_BASED_ON_CALORIES]\n");
        prompt.append("  },\n");
        prompt.append("  \"activity\": {\n");
        prompt.append("    \"walkingCalories\": 0,\n");
        prompt.append("    \"activityCalories\": 0,\n");
        prompt.append("    \"totalBurned\": 0\n");
        prompt.append("  },\n");
        prompt.append("  \"mealDistribution\": {\n");
        prompt.append("    \"breakfastCalories\": [25%_OF_TOTAL_CALORIES],\n");
        prompt.append("    \"lunchCalories\": [35%_OF_TOTAL_CALORIES],\n");
        prompt.append("    \"dinnerCalories\": [30%_OF_TOTAL_CALORIES],\n");
        prompt.append("    \"snacksCalories\": [10%_OF_TOTAL_CALORIES],\n");
        prompt.append("    \"breakfastEaten\": 0,\n");
        prompt.append("    \"lunchEaten\": 0,\n");
        prompt.append("    \"dinnerEaten\": 0,\n");
        prompt.append("    \"snacksEaten\": 0,\n");
        prompt.append("    \"breakfastRecommendation\": \"Oatmeal with fruits and nuts\",\n");
        prompt.append("    \"lunchRecommendation\": \"Grilled chicken salad with quinoa\",\n");
        prompt.append("    \"dinnerRecommendation\": \"Baked salmon with vegetables\",\n");
        prompt.append("    \"snacksRecommendation\": \"Greek yogurt with berries\"\n");
        prompt.append("  },\n");
        prompt.append("  \"recommendation\": \"[PERSONALIZED_ADVICE_BASED_ON_BMI_AND_GOALS]\",\n");
        prompt.append("  \"healthStatus\": \"[BMI_STATUS_AND_WEIGHT_MANAGEMENT_RECOMMENDATIONS]\",\n");
        prompt.append("  \"bmi\": ").append(String.format("%.1f", userProfile.getBmi())).append(",\n");
        prompt.append("  \"bmiCategory\": \"").append(userProfile.getBmiCategory()).append("\"\n");
        prompt.append("}\n\n");
        
        prompt.append("CRITICAL: Return ONLY raw JSON data. No markdown, no code blocks, no explanations. Just the JSON object. Base recommendations on evidence-based nutrition science.");

        return prompt.toString();
    }

    /**
     * Call Gemini API
     */
    private String callGeminiAPI(String prompt) {
        try {
            JSONObject requestBody = new JSONObject();
            JSONObject content = new JSONObject();
            JSONObject text = new JSONObject();
            
            text.put("text", prompt);
            content.put("parts", new org.json.JSONArray().put(text));
            requestBody.put("contents", new org.json.JSONArray().put(content));
            
            RequestBody body = RequestBody.create(
                requestBody.toString(),
                MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                .url(GEMINI_API_URL + "?key=" + API_KEY)
                .post(body)
                .addHeader("Content-Type", "application/json")
                .build();
            
            Response response = httpClient.newCall(request).execute();
            String responseBody = response.body().string();
            
            if (response.isSuccessful()) {
                Log.d(TAG, "API call successful");
                return extractGeminiResponse(responseBody);
            } else {
                Log.e(TAG, "API call failed: " + response.code() + " - " + response.message());
                Log.e(TAG, "Response body: " + responseBody);
                Log.e(TAG, "Request body: " + requestBody.toString());
                return null;
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Error calling Gemini API: " + e.getMessage());
            return null;
        }
    }

    /**
     * Extract text from Gemini response
     */
    private String extractGeminiResponse(String responseBody) {
        try {
            JSONObject jsonResponse = new JSONObject(responseBody);
            String text = jsonResponse
                .getJSONArray("candidates")
                .getJSONObject(0)
                .getJSONObject("content")
                .getJSONArray("parts")
                .getJSONObject(0)
                .getString("text");
            
            // Clean up markdown code blocks if present
            if (text.contains("```json")) {
                text = text.replaceAll("```json\\s*", "").replaceAll("```\\s*$", "").trim();
            } else if (text.contains("```")) {
                text = text.replaceAll("```\\s*", "").trim();
            }
            
            return text;
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing Gemini response: " + e.getMessage());
            return null;
        }
    }

    /**
     * Parse nutrition response JSON
     */
    private NutritionData parseNutritionResponse(String jsonResponse, UserProfile userProfile) {
        try {
            JSONObject json = new JSONObject(jsonResponse);
            
            // Parse main nutrition data
            int totalCalories = json.getInt("totalCalories");
            int caloriesLeft = json.getInt("caloriesLeft");
            int caloriesEaten = json.getInt("caloriesEaten");
            int caloriesBurned = json.getInt("caloriesBurned");
            
            // Parse macronutrients
            JSONObject macrosJson = json.getJSONObject("macronutrients");
            NutritionData.Macronutrients macronutrients = new NutritionData.Macronutrients(
                macrosJson.getInt("carbs"),
                macrosJson.getInt("protein"),
                macrosJson.getInt("fat"),
                macrosJson.getInt("carbsTarget"),
                macrosJson.getInt("proteinTarget"),
                macrosJson.getInt("fatTarget")
            );
            
            // Parse activity data
            JSONObject activityJson = json.getJSONObject("activity");
            NutritionData.ActivityData activity = new NutritionData.ActivityData(
                activityJson.getInt("walkingCalories"),
                activityJson.getInt("activityCalories"),
                activityJson.getInt("totalBurned")
            );
            
            // Parse meal distribution
            JSONObject mealJson = json.getJSONObject("mealDistribution");
            NutritionData.MealDistribution mealDistribution = new NutritionData.MealDistribution(
                mealJson.getInt("breakfastCalories"),
                mealJson.getInt("lunchCalories"),
                mealJson.getInt("dinnerCalories"),
                mealJson.getInt("snacksCalories"),
                mealJson.getString("breakfastRecommendation"),
                mealJson.getString("lunchRecommendation"),
                mealJson.getString("dinnerRecommendation"),
                mealJson.getString("snacksRecommendation")
            );
            
            // Set eaten calories (default to 0 if not provided)
            mealDistribution.setBreakfastEaten(mealJson.optInt("breakfastEaten", 0));
            mealDistribution.setLunchEaten(mealJson.optInt("lunchEaten", 0));
            mealDistribution.setDinnerEaten(mealJson.optInt("dinnerEaten", 0));
            mealDistribution.setSnacksEaten(mealJson.optInt("snacksEaten", 0));
            
            // Parse other data
            String recommendation = json.getString("recommendation");
            String healthStatus = json.getString("healthStatus");
            double bmi = json.getDouble("bmi");
            String bmiCategory = json.getString("bmiCategory");
            
            return new NutritionData(totalCalories, caloriesLeft, caloriesEaten, caloriesBurned,
                    macronutrients, activity, mealDistribution, recommendation, healthStatus, bmi, bmiCategory);
                    
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing nutrition response: " + e.getMessage());
            return null;
        }
    }

    /**
     * Generate fallback nutrition data based on user profile
     */
    private NutritionData generateFallbackNutritionData(UserProfile userProfile) {
        Log.d(TAG, "Generating fallback nutrition data for user: " + userProfile.getName());
        
        // Calculate BMR using Mifflin-St Jeor Equation
        double bmr = calculateBMR(userProfile);
        
        // Apply activity multiplier
        double totalCalories = bmr * userProfile.getActivityMultiplier();
        
        // Adjust based on BMI category
        if (userProfile.getBmi() >= 40) {
            // Severely obese - aggressive calorie deficit
            totalCalories = Math.min(totalCalories * 0.6, 1200); // Max 1200 calories
        } else if (userProfile.isObese()) {
            totalCalories = Math.min(totalCalories * 0.7, 1500); // Max 1500 calories
        } else if (userProfile.isOverweight()) {
            totalCalories = Math.min(totalCalories * 0.8, 1800); // Max 1800 calories
        } else if (userProfile.getBmi() < 18.5) {
            totalCalories *= 1.1; // 10% calorie surplus for underweight users
        }
        
        int calories = (int) Math.round(totalCalories);
        
        // Calculate macronutrients (40% carbs, 30% protein, 30% fat)
        int carbsTarget = (int) Math.round(calories * 0.4 / 4); // 4 cal/g
        int proteinTarget = (int) Math.round(calories * 0.3 / 4); // 4 cal/g
        int fatTarget = (int) Math.round(calories * 0.3 / 9); // 9 cal/g
        
        // Create macronutrients
        NutritionData.Macronutrients macros = new NutritionData.Macronutrients(
            0, 0, 0, // Current values start at 0
            carbsTarget, proteinTarget, fatTarget
        );
        
        // Create activity data
        NutritionData.ActivityData activity = new NutritionData.ActivityData(0, 0, 0);
        
        // Create meal distribution
        NutritionData.MealDistribution mealDistribution = new NutritionData.MealDistribution(
            (int) Math.round(calories * 0.25), // Breakfast 25%
            (int) Math.round(calories * 0.35), // Lunch 35%
            (int) Math.round(calories * 0.30), // Dinner 30%
            (int) Math.round(calories * 0.10), // Snacks 10%
            "Oatmeal with fruits and nuts",
            "Grilled chicken salad with quinoa",
            "Baked salmon with vegetables",
            "Greek yogurt with berries"
        );
        
        // Create recommendation based on BMI
        String recommendation = generateRecommendation(userProfile);
        String healthStatus = "BMI: " + String.format("%.1f", userProfile.getBmi()) + " (" + userProfile.getBmiCategory() + ")";
        
        return new NutritionData(
            calories, calories, 0, 0, // total, left, eaten, burned
            macros, activity, mealDistribution,
            recommendation, healthStatus, userProfile.getBmi(), userProfile.getBmiCategory()
        );
    }
    
    /**
     * Calculate BMR using Mifflin-St Jeor Equation
     */
    private double calculateBMR(UserProfile userProfile) {
        double weight = userProfile.getWeight();
        double height = userProfile.getHeight();
        int age = userProfile.getAge();
        String gender = userProfile.getGender().toLowerCase();
        
        if (gender.equals("male")) {
            return (10 * weight) + (6.25 * height) - (5 * age) + 5;
        } else {
            return (10 * weight) + (6.25 * height) - (5 * age) - 161;
        }
    }
    
    /**
     * Generate personalized recommendation based on user profile
     */
    private String generateRecommendation(UserProfile userProfile) {
        StringBuilder recommendation = new StringBuilder();
        
        if (userProfile.isObese()) {
            recommendation.append("Focus on a calorie deficit with balanced nutrition. ");
            recommendation.append("Include plenty of vegetables and lean proteins. ");
            recommendation.append("Consider consulting a healthcare provider for weight management.");
        } else if (userProfile.isOverweight()) {
            recommendation.append("Maintain a moderate calorie deficit for healthy weight loss. ");
            recommendation.append("Focus on whole foods and regular physical activity.");
        } else if (userProfile.getBmi() < 18.5) {
            recommendation.append("Focus on nutrient-dense foods to support healthy weight gain. ");
            recommendation.append("Include healthy fats and adequate protein in your diet.");
        } else {
            recommendation.append("Maintain your current healthy lifestyle. ");
            recommendation.append("Focus on balanced nutrition and regular physical activity.");
        }
        
        if (userProfile.isPregnant()) {
            recommendation.append(" Ensure adequate folic acid, iron, and calcium intake for pregnancy.");
        }
        
        return recommendation.toString();
    }

    /**
     * Clean up resources
     */
    public void cleanup() {
        if (executorService != null && !executorService.isShutdown()) {
            executorService.shutdown();
        }
    }
}
