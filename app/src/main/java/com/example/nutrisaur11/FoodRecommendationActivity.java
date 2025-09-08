package com.example.nutrisaur11;

import android.os.Bundle;
import android.util.Log;
import android.view.View;
import androidx.appcompat.app.AppCompatActivity;
import androidx.viewpager2.widget.ViewPager2;
import androidx.viewpager2.widget.ViewPager2.OnPageChangeCallback;

import org.json.JSONObject;
import org.json.JSONException;
import org.json.JSONArray;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.HashSet;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import android.widget.ProgressBar;

import okhttp3.*;
import okhttp3.MediaType;
import okhttp3.RequestBody;
import okhttp3.Response;

public class FoodRecommendationActivity extends AppCompatActivity {
    private static final String TAG = "FoodRecommendation";
    private static final String GEMINI_API_KEY = "AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM";
    private static final String GEMINI_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
    
    private ViewPager2 viewPager;
    private FoodRecommendationAdapter adapter;
    private List<FoodRecommendation> recommendations;
    private int currentPosition = 0;
    private ExecutorService executorService;
    private Set<String> generatedFoodNames = new HashSet<>(); // Track generated foods
    private int generationCount = 0; // Track generation attempts
    
    // User profile data
    private String userAge;
    private String userSex;
    private String userBMI;
    private String userActivityLevel;
    private String userHealthConditions;
    private String userBudgetLevel;
    private String userDietaryRestrictions;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        Log.d(TAG, "FoodRecommendationActivity onCreate started");
        setContentView(R.layout.activity_food_recommendation);
        Log.d(TAG, "FoodRecommendationActivity layout set successfully");
        
        // Initialize executor service
        executorService = Executors.newFixedThreadPool(2);
        Log.d(TAG, "Executor service initialized");
        
        // Initialize UI components
        initializeViews();
        Log.d(TAG, "Views initialized");
        
        // Initialize recommendations list
        recommendations = new ArrayList<>();
        Log.d(TAG, "Recommendations list initialized");
        
        // Setup ViewPager2 for swipe functionality
        setupViewPager();
        Log.d(TAG, "ViewPager2 setup completed");
        
        // Load user profile data
        loadUserProfile();
        Log.d(TAG, "User profile loaded");
        
        // Generate exactly 10 recommendations at startup
        generateInitialRecommendations();
        Log.d(TAG, "Initial 10 recommendations generation started");
    }
    
    private void initializeViews() {
        viewPager = findViewById(R.id.view_pager);
        Log.d(TAG, "ViewPager2 initialized: " + (viewPager != null ? "success" : "failed"));
    }
    
    private void setupViewPager() {
        adapter = new FoodRecommendationAdapter(recommendations, this);
        viewPager.setAdapter(adapter);
        
        // Configure ViewPager2 for unlimited swiping
        viewPager.setOffscreenPageLimit(3); // Keep 3 pages in memory for smooth swiping
        
        // Setup page change callback for fixed 10 recommendations
        viewPager.registerOnPageChangeCallback(new OnPageChangeCallback() {
            @Override
            public void onPageSelected(int position) {
                super.onPageSelected(position);
                currentPosition = position;
                
                // Just log the position change - no more progressive loading
                Log.d(TAG, "Showing recommendation at position: " + position + ", isPreloading: false, total foods: " + recommendations.size());
            }
        });
    }
    
    private void loadUserProfile() {
        // Load user profile from CommunityUserManager
        CommunityUserManager userManager = new CommunityUserManager(this);
        
        if (userManager.isLoggedIn()) {
            Map<String, String> userData = userManager.getCurrentUserData();
            
            if (!userData.isEmpty()) {
                // Calculate age from birthday
                String birthday = userData.get("birthday");
                if (birthday != null && !birthday.isEmpty()) {
                    try {
                        java.text.SimpleDateFormat sdf = new java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.getDefault());
                        java.util.Date birthDate = sdf.parse(birthday);
                        java.util.Date currentDate = new java.util.Date();
                        long ageInMillis = currentDate.getTime() - birthDate.getTime();
                        int age = (int) (ageInMillis / (365.25 * 24 * 60 * 60 * 1000));
                        userAge = String.valueOf(age);
                    } catch (Exception e) {
                        userAge = "25"; // Default age
                    }
                } else {
                    userAge = "25"; // Default age
                }
                
                userSex = userData.get("sex");
                if (userSex == null || userSex.isEmpty()) {
                    userSex = "Not specified";
                }
                
                // Calculate BMI from weight and height
                String weightStr = userData.get("weight");
                String heightStr = userData.get("height");
                if (weightStr != null && heightStr != null && !weightStr.isEmpty() && !heightStr.isEmpty()) {
                    try {
                        double weight = Double.parseDouble(weightStr);
                        double height = Double.parseDouble(heightStr) / 100.0; // Convert cm to m
                        double bmi = weight / (height * height);
                        userBMI = String.format("%.1f", bmi);
                    } catch (Exception e) {
                        userBMI = "22.0"; // Default BMI
                    }
                } else {
                    userBMI = "22.0"; // Default BMI
                }
                
                userActivityLevel = "Moderate"; // Default
                userHealthConditions = "None"; // Default
                userBudgetLevel = "Medium"; // Default
                userDietaryRestrictions = "None"; // Default
                
                Log.d(TAG, "Loaded user profile: Age=" + userAge + ", Sex=" + userSex + ", BMI=" + userBMI);
            } else {
                setDefaultUserProfile();
            }
        } else {
            setDefaultUserProfile();
        }
    }
    
    private void setDefaultUserProfile() {
        userAge = "25";
        userSex = "Male";
        userBMI = "22.5";
        userActivityLevel = "Moderate";
        userHealthConditions = "None";
        userBudgetLevel = "Medium";
        userDietaryRestrictions = "None";
        Log.d(TAG, "Using default user profile");
    }
    
    private void generateInitialRecommendations() {
        // No loading indicator needed for default images
        
        executorService.execute(() -> {
            try {
                // Generate exactly 10 recommendations
                for (int i = 0; i < 10; i++) {
                    final int currentIndex = i;
                    FoodRecommendation recommendation = callGeminiAPI();
                    if (recommendation != null) {
                        runOnUiThread(() -> {
                            recommendations.add(recommendation);
                            generatedFoodNames.add(recommendation.getFoodName().toLowerCase().trim());
                            adapter.notifyDataSetChanged();
                            
                            Log.d(TAG, "Generated recommendation " + (currentIndex + 1) + "/10: " + recommendation.getFoodName());
                        });
                    } else {
                        // Add fallback if API fails
                        runOnUiThread(() -> {
                            FoodRecommendation fallback = createFallbackRecommendation();
                            recommendations.add(fallback);
                            adapter.notifyDataSetChanged();
                            Log.d(TAG, "Added fallback recommendation " + (currentIndex + 1) + "/10");
                        });
                    }
                }
                
                // All 10 recommendations generated successfully
                runOnUiThread(() -> {
                    Log.d(TAG, "All 10 recommendations generated successfully");
                });
                
            } catch (Exception e) {
                Log.e(TAG, "Error generating initial recommendations: " + e.getMessage());
                runOnUiThread(() -> {
                    // Add fallback recommendations to reach 10
                    while (recommendations.size() < 10) {
                        FoodRecommendation fallback = createFallbackRecommendation();
                        recommendations.add(fallback);
                    }
                    adapter.notifyDataSetChanged();
                });
            }
        });
    }
    

    
    private FoodRecommendation callGeminiAPI() {
        return callGeminiAPIWithRetry(0);
    }
    
    private FoodRecommendation callGeminiAPIWithRetry(int retryCount) {
        // Prevent infinite recursion
        if (retryCount > 3) {
            Log.w(TAG, "Max retry count reached, returning fallback recommendation");
            return createFallbackRecommendation();
        }
        
        try {
            // Increment generation count
            generationCount++;
            
            // Build the master prompt with user profile
            String masterPrompt = buildMasterPrompt();
            
            // Create JSON request for Gemini API
            JSONObject requestBody = new JSONObject();
            JSONObject content = new JSONObject();
            JSONArray parts = new JSONArray();
            JSONObject part = new JSONObject();
            part.put("text", masterPrompt);
            parts.put(part);
            content.put("parts", parts);
            JSONArray contents = new JSONArray();
            contents.put(content);
            requestBody.put("contents", contents);
            
            // Make API call
            OkHttpClient client = new OkHttpClient();
            RequestBody body = RequestBody.create(
                requestBody.toString(),
                MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                .url(GEMINI_API_URL + "?key=" + GEMINI_API_KEY)
                .post(body)
                .build();
            
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    String responseBody = response.body().string();
                    JSONObject jsonResponse = new JSONObject(responseBody);
                    
                    // Extract the response text from Gemini API
                    if (jsonResponse.has("candidates") && 
                        jsonResponse.getJSONArray("candidates").length() > 0) {
                        
                        JSONObject candidate = jsonResponse.getJSONArray("candidates")
                            .getJSONObject(0);
                        
                        if (candidate.has("content") && 
                            candidate.getJSONObject("content").has("parts") &&
                            candidate.getJSONObject("content").getJSONArray("parts").length() > 0) {
                            
                            String responseText = candidate.getJSONObject("content")
                                .getJSONArray("parts")
                                .getJSONObject(0)
                                .getString("text");
                            
                            // Parse the JSON response from Gemini
                            FoodRecommendation recommendation = parseFoodRecommendation(responseText);
                            
                            // Check for duplicates and regenerate if needed
                            if (recommendation != null && isDuplicate(recommendation)) {
                                Log.d(TAG, "Duplicate detected, regenerating... (attempt " + (retryCount + 1) + ")");
                                return callGeminiAPIWithRetry(retryCount + 1); // Recursive call to regenerate
                            }
                            
                            return recommendation;
                        }
                    }
                }
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Error calling Gemini API: " + e.getMessage());
        }
        
        return null;
    }
    
    private boolean isDuplicate(FoodRecommendation recommendation) {
        if (recommendation == null || recommendation.getFoodName() == null) {
            return false;
        }
        
        String foodName = recommendation.getFoodName().toLowerCase().trim();
        
        // Check if this food name has been generated before
        if (generatedFoodNames.contains(foodName)) {
            Log.d(TAG, "Duplicate food detected: " + foodName);
            return true;
        }
        
        // Check for similar names (partial matches)
        for (String existingName : generatedFoodNames) {
            if (foodName.contains(existingName) || existingName.contains(foodName)) {
                Log.d(TAG, "Similar food detected: " + foodName + " vs " + existingName);
                return true;
            }
        }
        
        return false;
    }
    
    private String buildMasterPrompt() {
        // Create variety instructions based on generation count
        String varietyInstruction = "";
        if (generationCount > 0) {
            varietyInstruction = "\n\nVARIETY REQUIREMENT: " +
                "This is recommendation #" + (generationCount + 1) + ". " +
                "Please suggest a DIFFERENT dish from previous recommendations. " +
                "Focus on variety in: " +
                "- Different protein sources (chicken, fish, pork, beef, tofu, etc.) " +
                "- Different cooking methods (grilled, steamed, fried, baked, etc.) " +
                "- Different Filipino regions or cuisines " +
                "- Different meal types (breakfast, lunch, dinner, snacks) " +
                "- Different complexity levels (simple to elaborate)";
        }
        
        return "You are an AI-powered food recommendation system for community health. " +
               "Your task is to recommend a meal based on the user's profile. " +
               "\n\nUser Profile: " +
               "- Age: " + userAge + " " +
               "- Sex: " + userSex + " " +
               "- BMI: " + userBMI + " " +
               "- Activity Level: " + userActivityLevel + " " +
               "- Health Conditions (if any): " + userHealthConditions + " " +
               "- Budget Level: " + userBudgetLevel + " " +
               "- Dietary Restrictions: " + userDietaryRestrictions + " " +
               "\n\nRequirements: " +
               "1. Recommend one specific dish (preferably Filipino or locally available). " +
               "2. Make sure the food is affordable and culturally appropriate based on budget. " +
               "3. Ensure variety and uniqueness in recommendations. " +
               "4. Return ONLY valid JSON in this format (no extra text, no explanations): " +
               "\n\n{ " +
               "\"food_name\": \"\", " +
               "\"calories\": <number>, " +
               "\"protein_g\": <number>, " +
               "\"fat_g\": <number>, " +
               "\"carbs_g\": <number>, " +
               "\"serving_size\": \"\", " +
               "\"diet_type\": \"\", " +
               "\"description\": \"\" " +
               "}" + varietyInstruction;
    }
    
    private FoodRecommendation parseFoodRecommendation(String responseText) {
        try {
            // Clean the response text to extract JSON
            int jsonStart = responseText.indexOf("{");
            int jsonEnd = responseText.lastIndexOf("}") + 1;
            
            if (jsonStart >= 0 && jsonEnd > jsonStart) {
                String jsonString = responseText.substring(jsonStart, jsonEnd);
                JSONObject json = new JSONObject(jsonString);
                
                String foodName = json.optString("food_name", "");
                int calories = json.optInt("calories", 0);
                double protein = json.optDouble("protein_g", 0.0);
                double fat = json.optDouble("fat_g", 0.0);
                double carbs = json.optDouble("carbs_g", 0.0);
                String servingSize = json.optString("serving_size", "");
                String dietType = json.optString("diet_type", "");
                String description = json.optString("description", "");
                String imageUrl = json.optString("image_url", ""); // New field for image URL
                
                FoodRecommendation recommendation = new FoodRecommendation(
                    foodName, calories, protein, fat, carbs, servingSize, dietType, description, imageUrl
                );
                
                Log.d(TAG, "Parsed recommendation: " + recommendation.getFoodName());
                return recommendation;
            }
        } catch (Exception e) {
            Log.e(TAG, "Error parsing food recommendation: " + e.getMessage());
        }
        
        return null;
    }
    
    private void showFallbackRecommendation() {
        // Show a fallback recommendation when API fails
        FoodRecommendation fallback = createFallbackRecommendation();
        
        recommendations.add(fallback);
        generatedFoodNames.add(fallback.getFoodName().toLowerCase().trim());
        adapter.notifyDataSetChanged();
        Log.d(TAG, "Fallback recommendation added: " + fallback.getFoodName());
    }
    
    private FoodRecommendation createFallbackRecommendation() {
        // Create a variety of fallback recommendations
        String[] fallbackFoods = {
            "Ginisang Munggo with Malunggay",
            "Sinigang na Baboy",
            "Tinola",
            "Adobo",
            "Nilagang Baka",
            "Bulalo",
            "Kare-kare",
            "Menudo",
            "Kaldereta",
            "Afritada"
        };
        
        // Choose a fallback food that hasn't been generated yet
        for (String foodName : fallbackFoods) {
            if (!generatedFoodNames.contains(foodName.toLowerCase().trim())) {
                return new FoodRecommendation(
                    foodName,
                    300 + (int)(Math.random() * 200), // Random calories
                    20 + (int)(Math.random() * 30), // Random protein
                    5 + (int)(Math.random() * 15), // Random fat
                    10 + (int)(Math.random() * 40), // Random carbs
                    "1 serving",
                    "Balanced",
                    "A delicious Filipino dish prepared with fresh ingredients and traditional cooking methods.",
                    "" // No image URL for fallback
                );
            }
        }
        
        // If all fallbacks are used, create a generic one
        return new FoodRecommendation(
            "Ginisang Munggo",
            350,
            25,
            12,
            20,
            "1 serving",
            "Balanced",
            "A healthy Filipino dish with balanced nutrition.",
            "" // No image URL for fallback
        );
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (executorService != null) {
            executorService.shutdown();
        }
    }
}
