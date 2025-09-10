package com.example.nutrisaur11;

import android.os.Bundle;
import android.view.View;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.content.Intent;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.RecyclerView;
import androidx.recyclerview.widget.LinearLayoutManager;
import java.util.*;
import java.util.Random;
import android.util.Log;
import com.example.nutrisaur11.adapters.HorizontalFoodAdapter;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import okhttp3.*;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;
import java.io.IOException;

public class FoodActivity extends AppCompatActivity implements com.example.nutrisaur11.adapters.HorizontalFoodAdapter.OnFoodClickListener {
    private static final String TAG = "FoodActivity";
    
    // RecyclerViews for different meal categories
    private RecyclerView breakfastRecycler, lunchRecycler, dinnerRecycler, snackRecycler;
    
    // Adapters for different meal categories
    private com.example.nutrisaur11.adapters.HorizontalFoodAdapter breakfastAdapter, lunchAdapter, dinnerAdapter, snackAdapter;
    
    // Food lists for different meal categories
    private List<FoodRecommendation> breakfastFoods = new ArrayList<>();
    private List<FoodRecommendation> lunchFoods = new ArrayList<>();
    private List<FoodRecommendation> dinnerFoods = new ArrayList<>();
    private List<FoodRecommendation> snackFoods = new ArrayList<>();

    // Featured banner views
    private ImageView featuredBackgroundImage;
    private TextView featuredFoodName, featuredFoodDescription;
    
    private ExecutorService executorService;
    
    // Food substitution components
    private FoodSubstitutionManager substitutionManager;
    private FoodSubstitutionDialog currentSubstitutionDialog;
    
    // Food details components
    private FoodDetailsManager foodDetailsManager;
    private FoodDetails currentFoodDetails;
    
    // Nutrition insights views
    private TextView tvBmiStatus, tvCaloriesTarget, tvProteinTarget, tvFatTarget, tvCarbsTarget, tvHealthRecommendation;
    
    // User profile variables
    private String userAge;
    private String userSex;
    private String userBMI;
    private String userHeight;
    private String userWeight;
    private String userBMICategory;
    private String userMUAC;
    private String userMUACCategory;
    private String userNutritionalRisk;
    private String userMunicipality;
    private String userScreeningDate;
    private String userNotes;
    private String userHealthConditions;
    private String userActivityLevel;
    private String userBudgetLevel;
    private String userDietaryRestrictions;
    private String userAllergies;
    private String userDietPrefs;
    private String userAvoidFoods;
    private String userRiskScore;
    private String userBarangay;
    private String userIncome;
    private String userPregnancyStatus;
    
    // Use centralized API configuration
    private static final String GEMINI_TEXT_API_URL = ApiConfig.GEMINI_TEXT_API_URL;
    private static final String GEMINI_IMAGE_API_URL = ApiConfig.GEMINI_IMAGE_API_URL;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_food);
        
        Log.d(TAG, "FoodActivity onCreate started");
        
        // Check if personalization has been completed
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        boolean personalizationCompleted = prefs.getBoolean("personalization_completed", false);
        
        if (!personalizationCompleted) {
            // Start personalization activity
            android.content.Intent intent = new android.content.Intent(this, PersonalizationActivity.class);
            startActivity(intent);
            finish();
            return;
        }
        
        // Initialize executor service
        executorService = Executors.newFixedThreadPool(2);
        Log.d(TAG, "Executor service initialized");
        
        // Initialize substitution manager
        substitutionManager = new FoodSubstitutionManager(this);
        Log.d(TAG, "Food substitution manager initialized");
        
        // Initialize food details manager
        foodDetailsManager = new FoodDetailsManager(this);
        Log.d(TAG, "Food details manager initialized");
        
        
        // Initialize views
        initializeViews();
        
        Log.d(TAG, "Food lists initialized");
        
        // Load user profile
        loadUserProfile();
        
        // Initialize nutrition insights
        initializeNutritionInsights();
        
        // Load food data for all categories
        Log.d(TAG, "Loading food data for all categories");
        loadFoodDataForAllCategories();
        

        
        // Setup navigation
        setupNavigation();
    }
    
    private void initializeViews() {
        // Initialize RecyclerViews
        breakfastRecycler = findViewById(R.id.breakfast_recycler);
        lunchRecycler = findViewById(R.id.lunch_recycler);
        dinnerRecycler = findViewById(R.id.dinner_recycler);
        snackRecycler = findViewById(R.id.snack_recycler);
        
        // Initialize featured banner views
        featuredBackgroundImage = findViewById(R.id.featured_background_image);
        featuredFoodName = findViewById(R.id.featured_food_name);
        featuredFoodDescription = findViewById(R.id.featured_food_description);
        
        Log.d(TAG, "Views initialized");
        
        // Set header title
        TextView pageTitle = findViewById(R.id.page_title);
        TextView pageSubtitle = findViewById(R.id.page_subtitle);
        if (pageTitle != null) {
            pageTitle.setText("AI FOOD RECOMMENDATIONS");
        }
        if (pageSubtitle != null) {
            pageSubtitle.setText("Personalized nutrition suggestions");
        }
        
        // Setup edit personalization button
        setupEditPersonalizationButton();
        
        // Setup RecyclerViews with horizontal layout managers
        setupRecyclerViews();
        
        // Setup featured banner
        setupFeaturedBanner();
    }
    
    private void setupEditPersonalizationButton() {
        // Find the edit personalization card and make it clickable
        androidx.cardview.widget.CardView editPersonalizationCard = findViewById(R.id.edit_personalization_card);
        if (editPersonalizationCard != null) {
            editPersonalizationCard.setOnClickListener(v -> {
                // Start personalization activity
                Intent intent = new Intent(this, PersonalizationActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            });
        }
    }
    
    private void initializeNutritionInsights() {
        // Initialize nutrition insights views
        tvBmiStatus = findViewById(R.id.tv_bmi_status);
        tvCaloriesTarget = findViewById(R.id.tv_calories_target);
        tvProteinTarget = findViewById(R.id.tv_protein_target);
        tvFatTarget = findViewById(R.id.tv_fat_target);
        tvCarbsTarget = findViewById(R.id.tv_carbs_target);
        tvHealthRecommendation = findViewById(R.id.tv_health_recommendation);
        
        // Calculate and display nutrition targets
        calculateAndDisplayNutritionTargets();
    }
    
    private void calculateAndDisplayNutritionTargets() {
        try {
            // Parse user data
            int age = userAge != null ? Integer.parseInt(userAge) : 25;
            double weight = 70.0; // Default weight
            double height = 170.0; // Default height in cm
            double bmi = 22.5; // Default BMI
            
            // Try to parse BMI if available
            if (userBMI != null && !userBMI.isEmpty()) {
                try {
                    bmi = Double.parseDouble(userBMI);
                } catch (NumberFormatException e) {
                    Log.w(TAG, "Could not parse BMI: " + userBMI);
                }
            }
            
            // Calculate weight from BMI (assuming average height)
            if (bmi > 0) {
                weight = bmi * 1.7 * 1.7; // BMI = weight(kg) / height(m)^2, assuming 1.7m height
            }
            
            // Calculate BMI status
            String bmiStatus = determineBmiStatus(bmi);
            int bmiStatusColor = getBmiStatusColor(bmi);
            
            // Calculate daily nutrition targets
            NutritionTargets targets = calculateDailyNutritionTargets(age, weight, height, bmi, userSex);
            
            // Update UI
            if (tvBmiStatus != null) {
                tvBmiStatus.setText(bmiStatus);
                tvBmiStatus.setTextColor(getResources().getColor(bmiStatusColor));
            }
            
            if (tvCaloriesTarget != null) {
                tvCaloriesTarget.setText(targets.calories + " kcal");
            }
            
            if (tvProteinTarget != null) {
                tvProteinTarget.setText(targets.protein + "g");
            }
            
            if (tvFatTarget != null) {
                tvFatTarget.setText(targets.fat + "g");
            }
            
            if (tvCarbsTarget != null) {
                tvCarbsTarget.setText(targets.carbs + "g");
            }
            
            if (tvHealthRecommendation != null) {
                tvHealthRecommendation.setText(generateHealthRecommendation(bmi, age, userHealthConditions));
            }
            
            Log.d(TAG, "Nutrition targets calculated: " + targets.calories + " kcal, " + targets.protein + "g protein");
            
        } catch (Exception e) {
            Log.e(TAG, "Error calculating nutrition targets: " + e.getMessage());
            // Set default values
            if (tvBmiStatus != null) tvBmiStatus.setText("Normal");
            if (tvCaloriesTarget != null) tvCaloriesTarget.setText("2000 kcal");
            if (tvProteinTarget != null) tvProteinTarget.setText("75g");
            if (tvFatTarget != null) tvFatTarget.setText("65g");
            if (tvCarbsTarget != null) tvCarbsTarget.setText("250g");
            if (tvHealthRecommendation != null) tvHealthRecommendation.setText("Focus on balanced meals with lean proteins and vegetables.");
        }
    }
    
    private String determineBmiStatus(double bmi) {
        if (bmi < 18.5) return "Underweight";
        else if (bmi < 25) return "Normal";
        else if (bmi < 30) return "Overweight";
        else return "Obese";
    }
    
    private int getBmiStatusColor(double bmi) {
        if (bmi < 18.5) return android.R.color.holo_orange_light; // Underweight - orange
        else if (bmi < 25) return android.R.color.holo_green_light; // Normal - green
        else if (bmi < 30) return android.R.color.holo_orange_light; // Overweight - orange
        else return android.R.color.holo_red_light; // Obese - red
    }
    
    private NutritionTargets calculateDailyNutritionTargets(int age, double weight, double height, double bmi, String sex) {
        // Calculate BMR (Basal Metabolic Rate) using Mifflin-St Jeor Equation
        double bmr;
        if ("Male".equalsIgnoreCase(sex) || "M".equalsIgnoreCase(sex)) {
            bmr = 10 * weight + 6.25 * height - 5 * age + 5;
        } else {
            bmr = 10 * weight + 6.25 * height - 5 * age - 161;
        }
        
        // Activity factor (sedentary to moderate activity)
        double activityFactor = 1.4; // Moderate activity
        
        // Calculate TDEE (Total Daily Energy Expenditure)
        double tdee = bmr * activityFactor;
        
        // Adjust calories based on BMI status
        if (bmi < 18.5) {
            tdee *= 1.1; // Increase calories for underweight
        } else if (bmi > 25) {
            tdee *= 0.9; // Decrease calories for overweight/obese
        }
        
        // Calculate macronutrient targets
        int calories = (int) Math.round(tdee);
        int protein = (int) Math.round(weight * 1.2); // 1.2g per kg body weight
        int fat = (int) Math.round(calories * 0.25 / 9); // 25% of calories from fat
        int carbs = (int) Math.round((calories - (protein * 4) - (fat * 9)) / 4); // Remaining calories from carbs
        
        return new NutritionTargets(calories, protein, fat, carbs);
    }
    
    private String generateHealthRecommendation(double bmi, int age, String healthConditions) {
        StringBuilder recommendation = new StringBuilder();
        
        // BMI-based recommendations
        if (bmi < 18.5) {
            recommendation.append("Focus on nutrient-dense foods to gain healthy weight. ");
        } else if (bmi > 25) {
            recommendation.append("Choose lean proteins and vegetables to support healthy weight management. ");
            } else {
            recommendation.append("Maintain balanced nutrition with variety in your meals. ");
        }
        
        // Age-based recommendations
        if (age < 18) {
            recommendation.append("Include calcium-rich foods for bone development. ");
        } else if (age > 50) {
            recommendation.append("Prioritize fiber and antioxidants for healthy aging. ");
        }
        
        // Health condition recommendations
        if (healthConditions != null && !healthConditions.isEmpty()) {
            if (healthConditions.toLowerCase().contains("diabetes")) {
                recommendation.append("Choose low-glycemic foods and control portion sizes. ");
            }
            if (healthConditions.toLowerCase().contains("hypertension")) {
                recommendation.append("Limit sodium and include potassium-rich foods. ");
            }
            if (healthConditions.toLowerCase().contains("heart")) {
                recommendation.append("Focus on heart-healthy fats and whole grains. ");
            }
        }
        
        return recommendation.toString().trim();
    }
    
    // Helper class for nutrition targets
    private static class NutritionTargets {
        final int calories;
        final int protein;
        final int fat;
        final int carbs;
        
        NutritionTargets(int calories, int protein, int fat, int carbs) {
            this.calories = calories;
            this.protein = protein;
            this.fat = fat;
            this.carbs = carbs;
        }
    }
    
    private void setupRecyclerViews() {
        // Setup Breakfast RecyclerView
        breakfastRecycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        breakfastAdapter = new HorizontalFoodAdapter(breakfastFoods, this, this);
        breakfastRecycler.setAdapter(breakfastAdapter);
        breakfastAdapter.setLoading(true);
        
        // Setup Lunch RecyclerView
        lunchRecycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        lunchAdapter = new HorizontalFoodAdapter(lunchFoods, this, this);
        lunchRecycler.setAdapter(lunchAdapter);
        lunchAdapter.setLoading(true);
        
        // Setup Dinner RecyclerView
        dinnerRecycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        dinnerAdapter = new HorizontalFoodAdapter(dinnerFoods, this, this);
        dinnerRecycler.setAdapter(dinnerAdapter);
        dinnerAdapter.setLoading(true);
        
        // Setup Snack RecyclerView
        snackRecycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        snackAdapter = new HorizontalFoodAdapter(snackFoods, this, this);
        snackRecycler.setAdapter(snackAdapter);
        snackAdapter.setLoading(true);
        
        Log.d(TAG, "All RecyclerViews setup completed with loading state");
    }
    
    private void setupFeaturedBanner() {
        // Set featured banner with static adobo image
        runOnUiThread(() -> {
            // Set featured food image to adobo.jpg
            featuredBackgroundImage.setImageResource(R.drawable.adobo);
            
            // Set featured food name and description
            featuredFoodName.setText("Chicken Adobo");
            featuredFoodDescription.setText("A classic Filipino dish featuring tender chicken braised in savory soy-vinegar sauce with aromatic garlic and bay leaves");
            
            Log.d(TAG, "Featured banner setup with static adobo image");
        });
    }
    
    private void loadFoodDataForAllCategories() {
        Log.d(TAG, "Loading food data for all categories");
        
        // DEBUG: Log what data is being passed to food loading
        Log.d(TAG, "=== PASSING DATA TO FOOD LOADING ===");
        Log.d(TAG, "userAge: " + userAge);
        Log.d(TAG, "userSex: " + userSex);
        Log.d(TAG, "userBMI: " + userBMI);
        Log.d(TAG, "userHeight: " + userHeight);
        Log.d(TAG, "userWeight: " + userWeight);
        Log.d(TAG, "userHealthConditions: " + userHealthConditions);
        Log.d(TAG, "userActivityLevel: " + userActivityLevel);
        Log.d(TAG, "userBudgetLevel: " + userBudgetLevel);
        Log.d(TAG, "userDietaryRestrictions: " + userDietaryRestrictions);
        Log.d(TAG, "userAllergies: " + userAllergies);
        Log.d(TAG, "userDietPrefs: " + userDietPrefs);
        Log.d(TAG, "userAvoidFoods: " + userAvoidFoods);
        Log.d(TAG, "userRiskScore: " + userRiskScore);
        Log.d(TAG, "userBarangay: " + userBarangay);
        Log.d(TAG, "userIncome: " + userIncome);
        Log.d(TAG, "userPregnancyStatus: " + userPregnancyStatus);
        Log.d(TAG, "=== END DATA PASSING ===");
        
        // Get comprehensive screening data for the prompt
        String screeningAnswers = getScreeningAnswersForPrompt();
        
        // Debug: Log all user data being sent to AI
        Log.d(TAG, "=== CALLING AI WITH USER DATA ===");
        Log.d(TAG, "userWeight: " + userWeight);
        Log.d(TAG, "userHeight: " + userHeight);
        Log.d(TAG, "userBMI: " + userBMI);
        Log.d(TAG, "userBMICategory: " + userBMICategory);
        Log.d(TAG, "userHealthConditions: " + userHealthConditions);
        Log.d(TAG, "userNutritionalRisk: " + userNutritionalRisk);
        
        // Load food data using only Gemini API
        loadFoodDataWithGemini();
    }
    
    private void loadFoodDataWithGemini() {
        Log.d(TAG, "Loading food data with Gemini API only");
        
        // Load foods for each category using Gemini
        executorService.execute(() -> {
            try {
                // Make one API call to get all categories
                List<FoodRecommendation> allFoods = callGeminiAPIForCategory("all");
                
                // Separate foods by category
                List<FoodRecommendation> breakfastFoodsList = new ArrayList<>();
                List<FoodRecommendation> lunchFoodsList = new ArrayList<>();
                List<FoodRecommendation> dinnerFoodsList = new ArrayList<>();
                List<FoodRecommendation> snackFoodsList = new ArrayList<>();
                
                // Since the new format returns all categories in one response,
                // we'll take the first 5 for each category from the combined list
                int foodsPerCategory = 5;
                for (int i = 0; i < allFoods.size(); i++) {
                    FoodRecommendation food = allFoods.get(i);
                    int categoryIndex = i / foodsPerCategory;
                    
                    switch (categoryIndex) {
                        case 0:
                            if (breakfastFoodsList.size() < foodsPerCategory) {
                                breakfastFoodsList.add(food);
                            }
                            break;
                        case 1:
                            if (lunchFoodsList.size() < foodsPerCategory) {
                                lunchFoodsList.add(food);
                            }
                            break;
                        case 2:
                            if (dinnerFoodsList.size() < foodsPerCategory) {
                                dinnerFoodsList.add(food);
                            }
                            break;
                        case 3:
                            if (snackFoodsList.size() < foodsPerCategory) {
                                snackFoodsList.add(food);
                            }
                            break;
                    }
                }
                
                // Update UI with all categories
                runOnUiThread(() -> {
        breakfastFoods.clear();
                    breakfastFoods.addAll(breakfastFoodsList);
                    breakfastAdapter.setLoading(false);
        breakfastAdapter.notifyDataSetChanged();
                    Log.d(TAG, "Loaded " + breakfastFoodsList.size() + " breakfast foods");
                    
        lunchFoods.clear();
                    lunchFoods.addAll(lunchFoodsList);
                    lunchAdapter.setLoading(false);
        lunchAdapter.notifyDataSetChanged();
                    Log.d(TAG, "Loaded " + lunchFoodsList.size() + " lunch foods");
                    
        dinnerFoods.clear();
                    dinnerFoods.addAll(dinnerFoodsList);
                    dinnerAdapter.setLoading(false);
        dinnerAdapter.notifyDataSetChanged();
                    Log.d(TAG, "Loaded " + dinnerFoodsList.size() + " dinner foods");
                    
        snackFoods.clear();
                    snackFoods.addAll(snackFoodsList);
                    snackAdapter.setLoading(false);
        snackAdapter.notifyDataSetChanged();
                    Log.d(TAG, "Loaded " + snackFoodsList.size() + " snack foods");
                });
                
            } catch (Exception e) {
                Log.e(TAG, "Error loading food data with Gemini: " + e.getMessage());
                runOnUiThread(() -> {
                    breakfastAdapter.setLoading(false);
                    lunchAdapter.setLoading(false);
                    dinnerAdapter.setLoading(false);
                    snackAdapter.setLoading(false);
                });
            }
        });
    }
    
    public List<FoodRecommendation> callGeminiAPIForCategory(String category) {
        try {
            String masterPrompt = buildMasterPromptForCategory(category);
            
            // Create JSON request
            JSONObject requestBody = new JSONObject();
            JSONArray contents = new JSONArray();
            JSONObject content = new JSONObject();
            JSONArray parts = new JSONArray();
            JSONObject part = new JSONObject();
            part.put("text", masterPrompt);
            parts.put(part);
            content.put("parts", parts);
            contents.put(content);
            requestBody.put("contents", contents);
            
            // Make API call with extended timeout
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
                .url("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent")
                .addHeader("Content-Type", "application/json")
                .addHeader("X-goog-api-key", ApiConfig.GEMINI_API_KEY)
                .post(body)
                .build();
                
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful() && response.body() != null) {
                    String responseText = response.body().string();
                    Log.d(TAG, "Gemini API response for " + category + ": " + responseText);
                    
                    List<FoodRecommendation> recommendations = parseFoodRecommendations(responseText);
                    if (recommendations != null && !recommendations.isEmpty()) {
                        Log.d(TAG, "Generated " + recommendations.size() + " " + category + " foods");
                        return recommendations;
                    }
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error calling Gemini API for " + category + ": " + e.getMessage());
        }
        
        // Return empty list if API fails (no fallbacks as requested)
        return new ArrayList<>();
    }
    
    private String buildMasterPromptForCategory(String category) {
        String screeningAnswers = getScreeningAnswersForPrompt();
        
        return "You are a professional Filipino nutritionist specializing in malnutrition recovery.\n" +
               "Based on the user profile, generate food recommendations for **Breakfast, Lunch, Dinner, and Snack**.\n\n" +
               
               "USER PROFILE:\n" +
               "Age: " + (userAge != null ? userAge : "25") + " years old\n" +
               "Sex: " + (userSex != null ? userSex : "Not specified") + "\n" +
               "BMI: " + (userBMI != null ? userBMI : "22.5") + "\n" +
               "Height: " + (userHeight != null ? userHeight : "Not specified") + " cm\n" +
               "Weight: " + (userWeight != null ? userWeight : "Not specified") + " kg\n" +
               "Health Conditions: " + (userHealthConditions != null ? userHealthConditions : "None") + "\n" +
               "Activity Level: " + (userActivityLevel != null ? userActivityLevel : "Moderate") + "\n" +
               "Budget Level: " + (userBudgetLevel != null ? userBudgetLevel : "Low") + "\n" +
               "Allergies: " + (userAllergies != null ? userAllergies : "None") + "\n" +
               "Diet Preferences: " + (userDietPrefs != null ? userDietPrefs : "None") + "\n" +
               "Pregnancy Status: " + (userPregnancyStatus != null ? userPregnancyStatus : "Not Applicable") + "\n\n" +
               
               "SCREENING DATA:\n" + screeningAnswers + "\n\n" +
               
               "RULES:\n" +
               "- Provide **exactly 5 dishes per category** (Breakfast, Lunch, Dinner, Snack).\n" +
               "- Use only complete dish names (no ingredients).\n" +
               "- Adapt recommendations to BMI status:\n" +
               "  *Underweight (BMI <18.5)*: Recommend calorie- and protein-rich meals, encourage energy-dense dishes.\n" +
               "  *Normal weight (BMI 18.5–24.9)*: Recommend balanced meals with moderate calories, protein, and vegetables.\n" +
               "  *Overweight (BMI 25–29.9)*: Recommend lighter meals, less fried or fatty foods, more vegetables and lean protein.\n" +
               "  *Obese (BMI ≥30)*: Recommend low-calorie nutrient-dense meals, avoid sugary/fried dishes, focus on vegetables, soups, and lean protein.\n" +
               "- Return ONLY valid JSON object, no explanations.\n\n" +
               
               "Return JSON in this format:\n" +
               "{\n" +
               "  \"Breakfast\": [\n" +
               "    {\"food_name\": \"Arroz Caldo\", \"calories\": 350, \"protein_g\": 12, \"fat_g\": 8, \"carbs_g\": 55, \"serving_size\": \"1 bowl\", \"diet_type\": \"Filipino\", \"description\": \"Rice porridge with chicken and ginger, a comforting Filipino breakfast staple.\"},\n" +
               "    ...\n" +
               "  ],\n" +
               "  \"Lunch\": [ ... ],\n" +
               "  \"Dinner\": [ ... ],\n" +
               "  \"Snack\": [ ... ]\n" +
               "}";
    }
    
    
    
    
    
    @Override
    public void onFoodClick(FoodRecommendation food) {
        // Handle food item click - show food details
        Log.d(TAG, "Food clicked: " + food.getFoodName());
        showFoodDetails(food);
    }
    
    @Override
    public void onFoodLongClick(FoodRecommendation food) {
        // Handle food long click - show substitution options
        Log.d(TAG, "Food long clicked: " + food.getFoodName());
        showFoodSubstitutionOptions(food);
    }
    
    private void setupNavigation() {
        // Home navigation
        findViewById(R.id.nav_home).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(FoodActivity.this, MainActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            }
        });

        // Food navigation (current page - no action needed)
        findViewById(R.id.nav_food).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Already on food page, do nothing
            }
        });

        // Favorites navigation
        findViewById(R.id.nav_favorites).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(FoodActivity.this, FavoritesActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            }
        });

        // Account navigation
        findViewById(R.id.nav_account).setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(FoodActivity.this, AccountActivity.class);
                startActivity(intent);
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                finish();
            }
        });
    }
    

    

    
    private void loadUserProfile() {
        try {
            // Get current user email from SharedPreferences
            android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
            String userEmail = prefs.getString("current_user_email", null);
            boolean isLoggedIn = prefs.getBoolean("is_logged_in", false);
            
            Log.d(TAG, "=== LOADING USER PROFILE ===");
            Log.d(TAG, "User Email: " + userEmail);
            Log.d(TAG, "Is Logged In: " + isLoggedIn);
            
            if (userEmail != null) {
                // Load basic user profile data from CommunityUserManager (community_users table)
                CommunityUserManager userManager = new CommunityUserManager(this);
                Map<String, String> userData = userManager.getCurrentUserDataFromDatabase();
                
                Log.d(TAG, "=== USER DATA FROM DATABASE ===");
                Log.d(TAG, "User Data Size: " + userData.size());
                for (Map.Entry<String, String> entry : userData.entrySet()) {
                    Log.d(TAG, entry.getKey() + ": " + entry.getValue());
                }
                
                if (!userData.isEmpty()) {
                    // Load basic user data from community_users
                    userSex = userData.get("sex");
                    userHeight = userData.get("height_cm"); // Database field name
                    userWeight = userData.get("weight_kg"); // Database field name
                    userBarangay = userData.get("barangay");
                    userBMICategory = userData.get("bmi_category");
                    userMUAC = userData.get("muac");
                    userMUACCategory = userData.get("muac_category");
                    userNutritionalRisk = userData.get("nutritional_risk");
                    userMunicipality = userData.get("municipality");
                    userScreeningDate = userData.get("screening_date");
                    userNotes = userData.get("notes");
                    
                    // Use age directly from database (already calculated)
                    String age = userData.get("age");
                    if (age != null && !age.isEmpty()) {
                        userAge = age;
                    } else {
                        // Fallback: Calculate age from birthday
                        String birthday = userData.get("birthday");
                        if (birthday != null && !birthday.isEmpty()) {
                            userAge = calculateAgeFromBirthday(birthday);
                        }
                    }
                    
                    // Use BMI directly from database (already calculated)
                    String bmi = userData.get("bmi");
                    if (bmi != null && !bmi.isEmpty()) {
                        userBMI = bmi;
                    } else {
                        // Fallback: Calculate BMI from height and weight
                        if (userHeight != null && userWeight != null && !userHeight.isEmpty() && !userWeight.isEmpty()) {
                            try {
                                double height = Double.parseDouble(userHeight);
                                double weight = Double.parseDouble(userWeight);
                                if (height > 0 && weight > 0) {
                                    double calculatedBmi = weight / ((height / 100) * (height / 100));
                                    userBMI = String.format("%.1f", calculatedBmi);
                                }
                            } catch (NumberFormatException e) {
                                userBMI = "0";
                            }
                        }
                    }
                    
                    // Load pregnancy status from community_users data
                    String isPregnant = userData.get("is_pregnant");
                    if (isPregnant != null && (isPregnant.equals("Yes") || isPregnant.equals("1"))) {
                        userPregnancyStatus = "Yes";
                    } else {
                        userPregnancyStatus = "No";
                    }
                }
                
                // Load user preferences and additional data from local database
                UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(this);
        android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
                    "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + 
                    " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
                    new String[]{userEmail}
        );
        
        if (cursor.moveToFirst()) {
                    userAllergies = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_ALLERGIES);
                    userDietPrefs = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_DIET_PREFS);
                    userAvoidFoods = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_AVOID_FOODS);
                    userRiskScore = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_RISK_SCORE);
                    userIncome = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_INCOME);
                    
                    // Load health conditions
                    userHealthConditions = buildHealthConditionsString(cursor);
                    
                    // Determine activity level based on risk score
                    userActivityLevel = determineActivityLevel(userRiskScore);
                    
                    // Determine budget level based on income
                    userBudgetLevel = determineBudgetLevel(userIncome);
                    
                    Log.d(TAG, "=== LOADED USER PROFILE DATA ===");
                    Log.d(TAG, "Age: " + userAge);
                    Log.d(TAG, "Sex: " + userSex);
                    Log.d(TAG, "BMI: " + userBMI);
                    Log.d(TAG, "Height: " + userHeight);
                    Log.d(TAG, "Weight: " + userWeight);
                    Log.d(TAG, "Health Conditions: " + userHealthConditions);
                    Log.d(TAG, "Pregnancy Status: " + userPregnancyStatus);
                    Log.d(TAG, "Allergies: " + userAllergies);
                    Log.d(TAG, "Diet Preferences: " + userDietPrefs);
                    Log.d(TAG, "Budget Level: " + userBudgetLevel);
                    Log.d(TAG, "Barangay: " + userBarangay);
                    Log.d(TAG, "=== END USER PROFILE DATA ===");
                } else {
                    Log.w(TAG, "No user preferences found in local database, but we have data from community_users");
                    // Don't call setDefaultUserProfile() here because we already have data from community_users
                }
        cursor.close();
                dbHelper.close();
            } else {
                Log.w(TAG, "No user email found, using defaults");
                setDefaultUserProfile();
            }
                } catch (Exception e) {
            Log.e(TAG, "Error loading user profile: " + e.getMessage());
            setDefaultUserProfile();
        }
        
        Log.d(TAG, "User profile loaded");
    }
    
    private String buildHealthConditionsString(android.database.Cursor cursor) {
        StringBuilder conditions = new StringBuilder();
        
        // Check BMI category first
        double bmi = getDoubleFromCursor(cursor, UserPreferencesDbHelper.COL_USER_BMI);
        if (bmi > 0) {
            if (bmi < 18.5) {
                conditions.append("Underweight, ");
            } else if (bmi >= 25.0) {
                conditions.append("Overweight/Obese, ");
            }
        }
        
        // Check for physical signs from screening
        String physicalSigns = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_PHYSICAL_SIGNS);
        if (physicalSigns != null && !physicalSigns.isEmpty()) {
            if (physicalSigns.contains("thin")) {
                conditions.append("Physical thinness, ");
            }
            if (physicalSigns.contains("shorter")) {
                conditions.append("Stunted growth, ");
            }
            if (physicalSigns.contains("weak")) {
                conditions.append("Physical weakness, ");
            }
        }
        
        // Check for feeding behavior issues
        String feedingBehavior = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_FEEDING_BEHAVIOR);
        if (feedingBehavior != null && !feedingBehavior.isEmpty()) {
            if (feedingBehavior.contains("difficulty")) {
                conditions.append("Eating difficulty, ");
            }
        }
        
        // Check for weight loss
        String weightLoss = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_WEIGHT_LOSS);
        if (weightLoss != null && !weightLoss.isEmpty()) {
            if (weightLoss.contains("yes")) {
                conditions.append("Recent weight loss, ");
            }
        }
        
        // Check for swelling
        String swelling = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_SWELLING);
        if (swelling != null && !swelling.isEmpty()) {
            if (swelling.contains("yes")) {
                conditions.append("Edema/swelling, ");
            }
        }
        
        // Extract additional health conditions from screening_answers JSON
        try {
            String screeningAnswersJson = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_SCREENING_ANSWERS);
            if (screeningAnswersJson != null && !screeningAnswersJson.isEmpty()) {
                JSONObject screeningData = new JSONObject(screeningAnswersJson);
                
                // Check family history
                JSONObject familyHistory = screeningData.optJSONObject("family_history");
                if (familyHistory != null) {
                    if (familyHistory.optBoolean("diabetes", false)) {
                        conditions.append("Family history of diabetes, ");
                    }
                    if (familyHistory.optBoolean("hypertension", false)) {
                        conditions.append("Family history of hypertension, ");
                    }
                    if (familyHistory.optBoolean("heart_disease", false)) {
                        conditions.append("Family history of heart disease, ");
                    }
                    if (familyHistory.optBoolean("obesity", false)) {
                        conditions.append("Family history of obesity, ");
                    }
                }
                
                // Check BMI category from screening
                String bmiCategory = screeningData.optString("bmi_category", "");
                if (!bmiCategory.isEmpty()) {
                    conditions.append("BMI Category: ").append(bmiCategory).append(", ");
                    }
                }
            } catch (Exception e) {
            Log.e(TAG, "Error parsing additional health conditions from screening JSON: " + e.getMessage());
        }
        
        String result = conditions.toString();
        return result.isEmpty() ? "None" : result.substring(0, result.length() - 2); // Remove last comma
    }
    
    private String determineActivityLevel(String riskScore) {
        if (riskScore == null || riskScore.isEmpty()) return "Moderate";
        
        try {
            int score = Integer.parseInt(riskScore);
            if (score <= 3) return "Low";
            else if (score <= 7) return "Moderate";
            else return "High";
        } catch (NumberFormatException e) {
            return "Moderate";
        }
    }
    
    private String determineBudgetLevel(String income) {
        if (income == null || income.isEmpty()) return "Low";
        
        String lowerIncome = income.toLowerCase();
        if (lowerIncome.contains("low") || lowerIncome.contains("minimum")) return "Low";
        else if (lowerIncome.contains("high") || lowerIncome.contains("above")) return "High";
        else return "Medium";
    }
    
    private String loadPregnancyStatusFromScreeningJson(String screeningAnswersJson) {
        try {
            if (screeningAnswersJson == null || screeningAnswersJson.isEmpty()) {
                return "Not Applicable";
            }
            
            JSONObject screeningData = new JSONObject(screeningAnswersJson);
            String pregnant = screeningData.optString("pregnant", "Not Applicable");
            
            Log.d(TAG, "Pregnancy status from screening JSON: " + pregnant);
            return pregnant;
            } catch (Exception e) {
            Log.e(TAG, "Error parsing pregnancy status from screening JSON: " + e.getMessage());
            return "Not Applicable";
        }
    }
    
    private void setDefaultUserProfile() {
        userAge = "25";
        userSex = "Not specified";
        userBMI = "22.5";
        userBMICategory = "Normal";
        userMUAC = "Not specified";
        userMUACCategory = "Normal";
        userNutritionalRisk = "Low";
        userMunicipality = "Not specified";
        userScreeningDate = "Not specified";
        userNotes = "No notes available";
        userHealthConditions = "None";
        userActivityLevel = "Moderate";
        userBudgetLevel = "Low";
        userDietaryRestrictions = "None";
        userAllergies = "";
        userDietPrefs = "";
        userAvoidFoods = "";
        userRiskScore = "5";
        userBarangay = "Not specified";
        userIncome = "Low";
        userPregnancyStatus = "Not Applicable";
    }
    


    


    

    

    
    
    
    
    private List<FoodRecommendation> parseFoodRecommendations(String responseText) {
        List<FoodRecommendation> foods = new ArrayList<>();
        
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
                        Log.d(TAG, "Extracted text content: " + textContent);
                        
                        // Extract JSON object from the text content
                        int objectStart = textContent.indexOf("{");
                        int objectEnd = textContent.lastIndexOf("}") + 1;
                        
                        if (objectStart >= 0 && objectEnd > objectStart) {
                            String jsonObjectString = textContent.substring(objectStart, objectEnd);
                            Log.d(TAG, "Extracted JSON object: " + jsonObjectString);
                            
                            JSONObject foodData = new JSONObject(jsonObjectString);
                            
                            // Parse each category
                            String[] categories = {"Breakfast", "Lunch", "Dinner", "Snack"};
                            for (String category : categories) {
                                if (foodData.has(category)) {
                                    JSONArray categoryArray = foodData.getJSONArray(category);
                                    
                                    for (int j = 0; j < categoryArray.length(); j++) {
                                        try {
                                            JSONObject foodJson = categoryArray.getJSONObject(j);
                                    
                                    // Skip if the food object is null
                                    if (foodJson == null || foodJson == JSONObject.NULL) {
                                        Log.w(TAG, "Skipping null food object at index " + j);
                                        continue;
                                    }
                                    
                                    String foodName = foodJson.optString("food_name", "");
                                    int calories = foodJson.optInt("calories", 0);
                                    double protein = foodJson.optDouble("protein_g", 0.0);
                                    double fat = foodJson.optDouble("fat_g", 0.0);
                                    double carbs = foodJson.optDouble("carbs_g", 0.0);
                                    String servingSize = foodJson.optString("serving_size", "");
                                    String dietType = foodJson.optString("diet_type", "");
                                    String description = foodJson.optString("description", "");
                                    
                                    // Skip if food name is empty
                                    if (foodName.trim().isEmpty()) {
                                        Log.w(TAG, "Skipping food with empty name at index " + j);
                                        continue;
                                    }
                                            
                                            // Map food name to drawable resource
                                            String imageUrl = mapImageNameToDrawable("", foodName);
                                    
                                    Log.d(TAG, "Parsed food: " + foodName + " - Calories: " + calories + 
                                                      ", Protein: " + protein + ", Fat: " + fat + ", Carbs: " + carbs + 
                                                      ", Image: " + imageUrl);
                                    
                                    FoodRecommendation recommendation = new FoodRecommendation(
                                                foodName, calories, protein, fat, carbs, servingSize, dietType, description, imageUrl
                                    );
                                    
                                    foods.add(recommendation);
                                } catch (JSONException e) {
                                    Log.w(TAG, "Error parsing food at index " + j + ": " + e.getMessage() + ", skipping");
                                    continue;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing JSON: " + e.getMessage());
            Log.e(TAG, "Response text: " + responseText);
        }
        
        return foods;
    }
    
    private String mapImageNameToDrawable(String imageName, String foodName) {
        if (imageName == null || imageName.trim().isEmpty()) {
            // Try to match food name to available images
            String lowerFoodName = foodName.toLowerCase().replaceAll("[^a-z]", "");
            
            // Common Filipino food mappings
            if (lowerFoodName.contains("adobo")) return "adobo.jpg";
            if (lowerFoodName.contains("afritada")) return "afritada.jpg";
            if (lowerFoodName.contains("arrozcaldo") || lowerFoodName.contains("arrozcaldo")) return "arroz_caldo.jpg";
            if (lowerFoodName.contains("bangsilog")) return "bangsilog.jpg";
            if (lowerFoodName.contains("bibingka")) return "bibingka.jpg";
            if (lowerFoodName.contains("bicol")) return "bicol_express.jpg";
            if (lowerFoodName.contains("biko")) return "biko.jpg";
            if (lowerFoodName.contains("bilobilo")) return "bilo_bilo.jpg";
            if (lowerFoodName.contains("binignit")) return "binignit.jpg";
            if (lowerFoodName.contains("bulalo")) return "bulalo.jpg";
            if (lowerFoodName.contains("champorado")) return "champorado.jpg";
            if (lowerFoodName.contains("chicharon")) return "chicharon.jpg";
            if (lowerFoodName.contains("chickeninasal") || lowerFoodName.contains("chickeninasal")) return "chicken_inasal.jpg";
            if (lowerFoodName.contains("crispypata") || lowerFoodName.contains("crispypata")) return "crispy_pata.jpg";
            if (lowerFoodName.contains("daing") && lowerFoodName.contains("bangus")) return "daing_na_bangus.jpg";
            if (lowerFoodName.contains("dinengdeng")) return "dinengdeng.jpg";
            if (lowerFoodName.contains("embutido")) return "embutido.jpg";
            if (lowerFoodName.contains("escabeche")) return "escabeche.jpg";
            if (lowerFoodName.contains("freshlumpia") || lowerFoodName.contains("freshlumpia")) return "fresh_lumpia.jpg";
            if (lowerFoodName.contains("ginataangmais") || lowerFoodName.contains("ginataangmais")) return "ginataang_mais.jpg";
            if (lowerFoodName.contains("ginataangmunggo") || lowerFoodName.contains("ginataangmunggo")) return "ginataang_munggo.jpg";
            if (lowerFoodName.contains("ginataangsaging") || lowerFoodName.contains("ginataangsaging")) return "ginataang_saging.jpg";
            if (lowerFoodName.contains("ginisangampalaya") || lowerFoodName.contains("ginisangampalaya")) return "ginisang_ampalaya.jpg";
            if (lowerFoodName.contains("ginisangsayote") || lowerFoodName.contains("ginisangsayote")) return "ginisang_sayote.jpg";
            if (lowerFoodName.contains("goto")) return "goto_dish.jpg";
            if (lowerFoodName.contains("halohalo") || lowerFoodName.contains("halohalo")) return "halo_halo.jpg";
            if (lowerFoodName.contains("kaldereta")) return "kaldereta.jpg";
            if (lowerFoodName.contains("karekare") || lowerFoodName.contains("karekare")) return "kare_kare.jpg";
            if (lowerFoodName.contains("kinilaw")) return "kinilaw.jpg";
            if (lowerFoodName.contains("laing")) return "laing.jpg";
            if (lowerFoodName.contains("lechon")) return "lechon.jpg";
            if (lowerFoodName.contains("longsilog")) return "longsilog.jpg";
            if (lowerFoodName.contains("lugaw")) return "lugaw.jpg";
            if (lowerFoodName.contains("lumpiangshanghai") || lowerFoodName.contains("lumpiangshanghai")) return "lumpiang_shanghai.jpg";
            if (lowerFoodName.contains("macaronisalad") || lowerFoodName.contains("macaronisalad")) return "macaroni_salad.jpg";
            if (lowerFoodName.contains("maisconyelo") || lowerFoodName.contains("maisconyelo")) return "mais_con_yelo.jpg";
            if (lowerFoodName.contains("mami")) return "mami.jpg";
            if (lowerFoodName.contains("mangoshake") || lowerFoodName.contains("mangoshake")) return "mango_shake.jpg";
            if (lowerFoodName.contains("mechado")) return "mechado.jpg";
            if (lowerFoodName.contains("menudo")) return "menudo.jpg";
            if (lowerFoodName.contains("monggoguisado") || lowerFoodName.contains("monggoguisado")) return "monggo_guisado.jpg";
            if (lowerFoodName.contains("nilagangbaboy") || lowerFoodName.contains("nilagangbaboy")) return "nilagang_baboy.jpg";
            if (lowerFoodName.contains("nilagangbaka") || lowerFoodName.contains("nilagangbaka")) return "nilagang_baka.jpg";
            if (lowerFoodName.contains("paksiw") && lowerFoodName.contains("bangus")) return "paksiw_na_bangus.jpg";
            if (lowerFoodName.contains("pancitcanton") || lowerFoodName.contains("pancitcanton")) return "pancit_canton.jpg";
            if (lowerFoodName.contains("pancitmolo") || lowerFoodName.contains("pancitmolo")) return "pancit_molo.jpg";
            if (lowerFoodName.contains("pancitsotanghon") || lowerFoodName.contains("pancitsotanghon")) return "pancit_sotanghon.jpg";
            if (lowerFoodName.contains("pansitbihon") || lowerFoodName.contains("pansitbihon")) return "pansit_bihon.jpg";
            if (lowerFoodName.contains("pansitlomi") || lowerFoodName.contains("pansitlomi")) return "pansit_lomi.jpg";
            if (lowerFoodName.contains("pansitmalabon") || lowerFoodName.contains("pansitmalabon")) return "pansit_malabon.jpg";
            if (lowerFoodName.contains("papaitan")) return "papaitan.jpg";
            if (lowerFoodName.contains("pares")) return "pares.jpg";
            if (lowerFoodName.contains("pinakbet")) return "pinakbet.jpg";
            if (lowerFoodName.contains("pritongbangus") || lowerFoodName.contains("pritongbangus")) return "pritong_bangus.jpg";
            if (lowerFoodName.contains("pritonggalunggong") || lowerFoodName.contains("pritonggalunggong")) return "pritong_galunggong.jpg";
            if (lowerFoodName.contains("pritongtilapia") || lowerFoodName.contains("pritongtilapia")) return "pritong_tilapia.jpg";
            if (lowerFoodName.contains("puto")) return "puto.png";
            if (lowerFoodName.contains("putobumbong") || lowerFoodName.contains("putobumbong")) return "puto_bumbong.jpg";
            if (lowerFoodName.contains("sagoatgulaman") || lowerFoodName.contains("sagoatgulaman")) return "sago_at_gulaman.jpg";
            if (lowerFoodName.contains("salabat")) return "salabat.jpg";
            if (lowerFoodName.contains("sinangag")) return "sinangag.jpg";
            if (lowerFoodName.contains("sinigang") && lowerFoodName.contains("baboy")) return "sinigang_na_baboy.jpg";
            if (lowerFoodName.contains("sinigang") && lowerFoodName.contains("hipon")) return "sinigang_na_hipon.jpg";
            if (lowerFoodName.contains("sisig")) return "sisig.jpg";
            if (lowerFoodName.contains("sopas")) return "sopas.jpg";
            if (lowerFoodName.contains("sorbetes")) return "sorbetes.jpg";
            if (lowerFoodName.contains("soyamilk") || lowerFoodName.contains("soyamilk")) return "soya_milk.jpg";
            if (lowerFoodName.contains("sweetandsourfish") || lowerFoodName.contains("sweetandsourfish")) return "sweet_and_sour_fish.jpg";
            if (lowerFoodName.contains("sweetsourpork") || lowerFoodName.contains("sweetsourpork")) return "sweet_sour_pork.jpg";
            if (lowerFoodName.contains("tinapa")) return "tinapa.jpg";
            if (lowerFoodName.contains("tinola")) return "tinola.jpg";
            if (lowerFoodName.contains("tinolangbangus") || lowerFoodName.contains("tinolangbangus")) return "tinolang_bangus.jpg";
            if (lowerFoodName.contains("tocilog")) return "tocilog.jpg";
            if (lowerFoodName.contains("tortangginiling") || lowerFoodName.contains("tortangginiling")) return "tortang_giniling.jpg";
            if (lowerFoodName.contains("tortangtalong") || lowerFoodName.contains("tortangtalong")) return "tortang_talong.jpg";
            if (lowerFoodName.contains("turon")) return "turon.jpg";
            if (lowerFoodName.contains("ubehalaya") || lowerFoodName.contains("ubehalaya")) return "ube_halaya.jpg";
            if (lowerFoodName.contains("ubebibingka") || lowerFoodName.contains("ubebibingka")) return "ube_bibingka.jpg";
            if (lowerFoodName.contains("viganempanada") || lowerFoodName.contains("viganempanada")) return "vigan_empanada.jpg";
            
            // Default fallback
            return "default_food_image.xml";
        }
        
        // Use the provided image name directly
        return imageName.toLowerCase().replaceAll("[^a-z0-9_.]", "_") + ".jpg";
    }
    
    private FoodRecommendation parseFoodRecommendation(String responseText) {
        List<FoodRecommendation> foods = parseFoodRecommendations(responseText);
        if (!foods.isEmpty()) {
            return foods.get(0);
        }
        return null;
    }



    @Override
    public void onBackPressed() {
        super.onBackPressed();
    }

    /**
     * Show detailed food information including nutrients and ingredients
     */
    private void showFoodDetails(FoodRecommendation food) {
        Log.d(TAG, "Loading detailed information for: " + food.getFoodName());
        
        // Show loading indicator
        // You can add a progress dialog here if needed
        
        foodDetailsManager.getFoodDetails(food, new FoodDetailsManager.FoodDetailsCallback() {
            @Override
            public void onFoodDetailsFound(FoodDetails foodDetails) {
                runOnUiThread(() -> {
                    currentFoodDetails = foodDetails;
                    showFoodDetailsDialog(foodDetails);
                });
            }
            
            @Override
            public void onError(String error) {
                runOnUiThread(() -> {
                    Log.e(TAG, "Error loading food details: " + error);
                    // Show error message
                    Log.e(TAG, "Error loading food details: " + error);
                });
            }
        });
    }
    
    /**
     * Show food details dialog
     */
    private void showFoodDetailsDialog(FoodDetails foodDetails) {
        // Create and show a dialog with food details
        // This would typically be a custom dialog showing:
        // - Food name and description
        // - Nutritional information (calories, protein, fat, carbs, vitamins, minerals)
        // - Ingredients list
        // - Cooking instructions
        // - Allergens and dietary tags
        // - Health benefits
        // - Storage and reheating instructions
        
        Log.d(TAG, "Showing food details for: " + foodDetails.getFoodName());
        Log.d(TAG, "Calories: " + foodDetails.getCalories() + ", Protein: " + foodDetails.getProtein() + "g");
        Log.d(TAG, "Ingredients: " + foodDetails.getIngredients().size() + " items");
        Log.d(TAG, "Allergens: " + foodDetails.getAllergens().size() + " items");
        
        // TODO: Implement actual dialog UI
        // For now, just log the details
    }
    

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (executorService != null) {
            executorService.shutdown();
        }
        if (substitutionManager != null) {
            substitutionManager.shutdown();
        }
        if (foodDetailsManager != null) {
            foodDetailsManager.shutdown();
        }
        if (currentSubstitutionDialog != null && currentSubstitutionDialog.isShowing()) {
            currentSubstitutionDialog.dismiss();
        }
    }
    
    
    /**
     * Show food substitution options for a selected food
     */
    private void showFoodSubstitutionOptions(FoodRecommendation food) {
        Log.d(TAG, "Showing substitution options for: " + food.getFoodName());
        
        // Determine substitution reason based on user profile
        String substitutionReason = determineSubstitutionReason();
        
        // Get substitutions based on user profile
        substitutionManager.getFoodSubstitutions(
            food, userAge, userSex, userBMI, userHealthConditions, userBudgetLevel,
            userAllergies, userDietPrefs, userPregnancyStatus, substitutionReason,
            new FoodSubstitutionManager.SubstitutionCallback() {
                @Override
                public void onSubstitutionsFound(List<FoodRecommendation> substitutions, String reason) {
                    runOnUiThread(() -> {
                        showSubstitutionDialog(food, substitutions, reason);
                    });
                }
                
                @Override
                public void onError(String error) {
                    runOnUiThread(() -> {
                        Log.e(TAG, "Error getting substitutions: " + error);
                        // Show error message
                        Log.e(TAG, "No substitutions available");
                    });
                }
            }
        );
    }
    
    /**
     * Determine the most appropriate substitution reason based on user profile
     */
    private String determineSubstitutionReason() {
        StringBuilder reason = new StringBuilder();
        
        // Check for health conditions
        if (userHealthConditions != null && !userHealthConditions.equals("None")) {
            if (userHealthConditions.contains("Diabetes")) {
                reason.append("Diabetes-friendly alternatives needed. ");
            }
            if (userHealthConditions.contains("Hypertension")) {
                reason.append("Low-sodium alternatives needed. ");
            }
            if (userHealthConditions.contains("Heart Disease")) {
                reason.append("Heart-healthy alternatives needed. ");
            }
        }
        
        // Check for allergies
        if (userAllergies != null && !userAllergies.isEmpty()) {
            reason.append("Allergy-safe alternatives needed. ");
        }
        
        // Check for dietary preferences
        if (userDietPrefs != null && !userDietPrefs.isEmpty()) {
            reason.append("Diet-appropriate alternatives needed. ");
        }
        
        // Check for pregnancy
        if ("Yes".equalsIgnoreCase(userPregnancyStatus)) {
            reason.append("Pregnancy-safe alternatives needed. ");
        }
        
        // Check for budget constraints
        if ("Low".equalsIgnoreCase(userBudgetLevel)) {
            reason.append("Budget-friendly alternatives needed. ");
        }
        
        // Default reason if none apply
        if (reason.length() == 0) {
            reason.append("Here are some great alternative options for you");
        }
        
        return reason.toString().trim();
    }
    
    /**
     * Show the substitution dialog
     */
    private void showSubstitutionDialog(FoodRecommendation originalFood, List<FoodRecommendation> substitutions, String reason) {
        // Dismiss any existing dialog
        if (currentSubstitutionDialog != null && currentSubstitutionDialog.isShowing()) {
            currentSubstitutionDialog.dismiss();
        }
        
        currentSubstitutionDialog = new FoodSubstitutionDialog(
            this, originalFood, substitutions, reason,
            userAge, userSex, userBMI, userHealthConditions, userBudgetLevel,
            userAllergies, userDietPrefs, userPregnancyStatus,
            new FoodSubstitutionDialog.OnSubstitutionSelectedListener() {
                @Override
                public void onSubstitutionSelected(FoodRecommendation substitution) {
                    Log.d(TAG, "Substitution selected: " + substitution.getFoodName());
                    // Handle substitution selection - could add to favorites, show details, etc.
                    showSubstitutionSelectedMessage(substitution);
                }
                
                @Override
                public void onRefreshSubstitutions() {
                    Log.d(TAG, "Refresh substitutions requested");
                    // Get new substitutions
                    refreshSubstitutions(originalFood, reason);
                }
            }
        );
        
        currentSubstitutionDialog.show();
    }
    
    /**
     * Refresh substitutions with new options
     */
    private void refreshSubstitutions(FoodRecommendation originalFood, String reason) {
        substitutionManager.getFoodSubstitutions(
            originalFood, userAge, userSex, userBMI, userHealthConditions, userBudgetLevel,
            userAllergies, userDietPrefs, userPregnancyStatus, reason + " - More options",
            new FoodSubstitutionManager.SubstitutionCallback() {
                @Override
                public void onSubstitutionsFound(List<FoodRecommendation> substitutions, String newReason) {
                    runOnUiThread(() -> {
                        if (currentSubstitutionDialog != null && currentSubstitutionDialog.isShowing()) {
                            currentSubstitutionDialog.updateSubstitutions(substitutions, newReason);
                        }
                    });
                }
                
                @Override
                public void onError(String error) {
                    Log.e(TAG, "Error refreshing substitutions: " + error);
                }
            }
        );
    }
    
    
    /**
     * Show message when substitution is selected
     */
    private void showSubstitutionSelectedMessage(FoodRecommendation substitution) {
        // You can implement a toast, snackbar, or other UI feedback here
        Log.d(TAG, "Substitution selected: " + substitution.getFoodName());
        // For now, just log - you can add UI feedback as needed
    }
    
    /**
     * Show message when keeping original food
     */
    private void showKeepOriginalMessage(FoodRecommendation originalFood) {
        // You can implement a toast, snackbar, or other UI feedback here
        Log.d(TAG, "Keeping original: " + originalFood.getFoodName());
        // For now, just log - you can add UI feedback as needed
    }
    
    /**
     * Safe method to get string value from cursor, handling missing columns gracefully
     */
    private String getStringFromCursor(android.database.Cursor cursor, String columnName) {
        try {
            int columnIndex = cursor.getColumnIndex(columnName);
            if (columnIndex >= 0) {
                return cursor.getString(columnIndex);
            } else {
                Log.w(TAG, "Column not found: " + columnName);
                return null;
            }
        } catch (Exception e) {
            Log.e(TAG, "Error accessing column " + columnName + ": " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Safe method to get double value from cursor, handling missing columns gracefully
     */
    private double getDoubleFromCursor(android.database.Cursor cursor, String columnName) {
        try {
            int columnIndex = cursor.getColumnIndex(columnName);
            if (columnIndex >= 0) {
                return cursor.getDouble(columnIndex);
            } else {
                Log.w(TAG, "Column not found: " + columnName);
                return 0.0;
            }
        } catch (Exception e) {
            Log.e(TAG, "Error accessing column " + columnName + ": " + e.getMessage());
            return 0.0;
        }
    }
    
    /**
     * Safe method to get int value from cursor, handling missing columns gracefully
     */
    private int getIntFromCursor(android.database.Cursor cursor, String columnName) {
        try {
            int columnIndex = cursor.getColumnIndex(columnName);
            if (columnIndex >= 0) {
                return cursor.getInt(columnIndex);
            } else {
                Log.w(TAG, "Column not found: " + columnName);
                return 0;
            }
        } catch (Exception e) {
            Log.e(TAG, "Error accessing column " + columnName + ": " + e.getMessage());
            return 0;
        }
    }

    private String calculateAgeFromBirthday(String birthday) {
        try {
            if (birthday == null || birthday.isEmpty()) {
                return "25"; // Default age
            }
            
            // Parse birthday in YYYY-MM-DD format
            java.text.SimpleDateFormat format = new java.text.SimpleDateFormat("yyyy-MM-dd");
            java.util.Date birthDate = format.parse(birthday);
            java.util.Date currentDate = new java.util.Date();
            
            long diffInMillis = currentDate.getTime() - birthDate.getTime();
            long diffInYears = diffInMillis / (365L * 24L * 60L * 60L * 1000L);
            
            return String.valueOf(Math.max(1, diffInYears)); // Minimum age of 1
        } catch (Exception e) {
            Log.e(TAG, "Error calculating age from birthday: " + e.getMessage());
            return "25"; // Default age on error
        }
    }
    
    /**
     * Get screening answers formatted for the Gemini prompt
     */
    private String getScreeningAnswersForPrompt() {
        try {
            // Get current user email from SharedPreferences
            android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
            String userEmail = prefs.getString("current_user_email", null);
            
            if (userEmail == null) {
                return "No screening data available - using default profile";
            }
            
            // Load screening data from CommunityUserManager (community_users table)
            CommunityUserManager userManager = new CommunityUserManager(this);
            Map<String, String> userData = userManager.getCurrentUserData();
            
            if (userData.isEmpty()) {
                return "No screening data available - using default profile";
            }
            
            StringBuilder screeningAnswers = new StringBuilder();
            screeningAnswers.append("NUTRITIONAL SCREENING RESPONSES:\n");
            
            // Basic demographic information
            String municipality = userData.get("municipality");
            String barangay = userData.get("barangay");
            String sex = userData.get("sex");
            String birthday = userData.get("birthday");
            String isPregnant = userData.get("is_pregnant");
            String weight = userData.get("weight_kg");
            String height = userData.get("height_cm");
            
            // DEBUG: Log weight and height values
            Log.d(TAG, "=== WEIGHT AND HEIGHT DEBUG ===");
            Log.d(TAG, "Weight from database: '" + weight + "'");
            Log.d(TAG, "Height from database: '" + height + "'");
            Log.d(TAG, "Weight is null: " + (weight == null));
            Log.d(TAG, "Height is null: " + (height == null));
            Log.d(TAG, "Weight is empty: " + (weight != null && weight.isEmpty()));
            Log.d(TAG, "Height is empty: " + (height != null && height.isEmpty()));
            Log.d(TAG, "=== END WEIGHT AND HEIGHT DEBUG ===");
            
            screeningAnswers.append("1. Location: ").append(municipality != null ? municipality : "Not specified");
            if (barangay != null && !barangay.isEmpty()) {
                screeningAnswers.append(", ").append(barangay);
            }
            screeningAnswers.append("\n");
            
            screeningAnswers.append("2. Sex: ").append(sex != null ? sex : "Not specified").append("\n");
            
            screeningAnswers.append("3. Age: ");
            if (birthday != null && !birthday.isEmpty()) {
                String age = calculateAgeFromBirthday(birthday);
                screeningAnswers.append(age).append(" years old (born ").append(birthday).append(")");
            } else {
                screeningAnswers.append("Not specified");
            }
            screeningAnswers.append("\n");
            
            screeningAnswers.append("4. Pregnancy Status: ").append(isPregnant != null ? isPregnant : "Not specified").append("\n");
            
            screeningAnswers.append("5. Physical Measurements:\n");
            screeningAnswers.append("   - Weight: ").append(weight != null ? weight + " kg" : "Not specified").append("\n");
            screeningAnswers.append("   - Height: ").append(height != null ? height + " cm" : "Not specified").append("\n");
            
            // Calculate and display BMI
            if (weight != null && height != null && !weight.isEmpty() && !height.isEmpty()) {
                try {
                    double weightKg = Double.parseDouble(weight);
                    double heightCm = Double.parseDouble(height);
                    if (heightCm > 0) {
                        double bmi = weightKg / ((heightCm / 100) * (heightCm / 100));
                        screeningAnswers.append("   - BMI: ").append(String.format("%.1f", bmi));
                        
                        // Add BMI category
                        if (bmi < 18.5) {
                            screeningAnswers.append(" (Underweight - nutritional intervention needed)");
                        } else if (bmi < 25) {
                            screeningAnswers.append(" (Normal weight)");
                        } else if (bmi < 30) {
                            screeningAnswers.append(" (Overweight - weight management recommended)");
                        } else {
                            screeningAnswers.append(" (Obese - weight management and nutritional counseling needed)");
                        }
                        screeningAnswers.append("\n");
                    }
                } catch (NumberFormatException e) {
                    screeningAnswers.append("   - BMI: Could not calculate (invalid weight/height data)\n");
                }
            } else {
                screeningAnswers.append("   - BMI: Could not calculate (missing weight/height data)\n");
            }
            
            // Load additional screening data from local database
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(this);
            android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
                "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + 
                " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
                new String[]{userEmail}
            );
            
            if (cursor.moveToFirst()) {
                screeningAnswers.append("6. Health Assessment:\n");
                
                // Physical signs
                String physicalSigns = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_PHYSICAL_SIGNS);
                if (physicalSigns != null && !physicalSigns.isEmpty()) {
                    screeningAnswers.append("   - Physical Signs: ").append(physicalSigns).append("\n");
                }
                
                // Feeding behavior
                String feedingBehavior = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_FEEDING_BEHAVIOR);
                if (feedingBehavior != null && !feedingBehavior.isEmpty()) {
                    screeningAnswers.append("   - Feeding Behavior: ").append(feedingBehavior).append("\n");
                }
                
                // Weight loss
                String weightLoss = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_WEIGHT_LOSS);
                if (weightLoss != null && !weightLoss.isEmpty()) {
                    screeningAnswers.append("   - Recent Weight Loss: ").append(weightLoss).append("\n");
                }
                
                // Swelling
                String swelling = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_SWELLING);
                if (swelling != null && !swelling.isEmpty()) {
                    screeningAnswers.append("   - Swelling/Edema: ").append(swelling).append("\n");
                }
                
                // Risk score
                String riskScore = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_RISK_SCORE);
                if (riskScore != null && !riskScore.isEmpty()) {
                    screeningAnswers.append("   - Nutritional Risk Score: ").append(riskScore).append("/10");
                    try {
                        int score = Integer.parseInt(riskScore);
                        if (score <= 3) {
                            screeningAnswers.append(" (Low Risk)");
                        } else if (score <= 7) {
                            screeningAnswers.append(" (Medium Risk)");
                        } else {
                            screeningAnswers.append(" (High Risk - immediate intervention needed)");
                        }
                    } catch (NumberFormatException e) {
                        // Keep as is
                    }
                    screeningAnswers.append("\n");
                }
                
                // Income level
                String income = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_INCOME);
                if (income != null && !income.isEmpty()) {
                    screeningAnswers.append("   - Income Level: ").append(income).append("\n");
                }
                
                // Allergies
                String allergies = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_ALLERGIES);
                if (allergies != null && !allergies.isEmpty()) {
                    screeningAnswers.append("   - Food Allergies: ").append(allergies).append("\n");
                }
                
                // Diet preferences
                String dietPrefs = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_DIET_PREFS);
                if (dietPrefs != null && !dietPrefs.isEmpty()) {
                    screeningAnswers.append("   - Dietary Preferences: ").append(dietPrefs).append("\n");
                }
                
                // Foods to avoid
                String avoidFoods = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_AVOID_FOODS);
                if (avoidFoods != null && !avoidFoods.isEmpty()) {
                    screeningAnswers.append("   - Foods to Avoid: ").append(avoidFoods).append("\n");
                }
                
                // Parse additional screening data from JSON
                String screeningAnswersJson = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_SCREENING_ANSWERS);
                if (screeningAnswersJson != null && !screeningAnswersJson.isEmpty()) {
                    try {
                        JSONObject screeningData = new JSONObject(screeningAnswersJson);
                        screeningAnswers.append("7. Additional Screening Data:\n");
                        
                        // Family history
                        JSONObject familyHistory = screeningData.optJSONObject("family_history");
                        if (familyHistory != null) {
                            screeningAnswers.append("   - Family History: ");
                            boolean hasHistory = false;
                            if (familyHistory.optBoolean("diabetes", false)) {
                                screeningAnswers.append("Diabetes, ");
                                hasHistory = true;
                            }
                            if (familyHistory.optBoolean("hypertension", false)) {
                                screeningAnswers.append("Hypertension, ");
                                hasHistory = true;
                            }
                            if (familyHistory.optBoolean("heart_disease", false)) {
                                screeningAnswers.append("Heart Disease, ");
                                hasHistory = true;
                            }
                            if (familyHistory.optBoolean("obesity", false)) {
                                screeningAnswers.append("Obesity, ");
                                hasHistory = true;
                            }
                            if (hasHistory) {
                                screeningAnswers.setLength(screeningAnswers.length() - 2); // Remove last comma
                            } else {
                                screeningAnswers.append("None");
                            }
                            screeningAnswers.append("\n");
                        }
                        
                        // BMI category from screening
                        String bmiCategory = screeningData.optString("bmi_category", "");
                        if (!bmiCategory.isEmpty()) {
                            screeningAnswers.append("   - BMI Category: ").append(bmiCategory).append("\n");
                        }
                        
                    } catch (Exception e) {
                        Log.e(TAG, "Error parsing additional screening data: " + e.getMessage());
                    }
                }
            }
            
            cursor.close();
            dbHelper.close();
            
            screeningAnswers.append("\nNUTRITIONAL ASSESSMENT SUMMARY:\n");
            screeningAnswers.append("Based on the above screening data, this person requires personalized nutritional recommendations ");
            screeningAnswers.append("that address their specific health needs, dietary restrictions, and economic situation. ");
            screeningAnswers.append("Focus on nutrient-dense foods that are accessible and appropriate for their condition.");
            
            Log.d(TAG, "Screening answers for prompt: " + screeningAnswers.toString());
            return screeningAnswers.toString();
            
        } catch (Exception e) {
            Log.e(TAG, "Error getting screening answers for prompt: " + e.getMessage());
            return "Screening data unavailable - using default nutritional recommendations";
        }
    }

} 