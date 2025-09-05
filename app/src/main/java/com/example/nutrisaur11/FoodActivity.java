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
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import okhttp3.*;
import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;
import java.io.IOException;

public class FoodActivity extends AppCompatActivity implements HorizontalFoodAdapter.OnFoodClickListener {
    private static final String TAG = "FoodActivity";
    
    // RecyclerViews for different categories
    private RecyclerView traditionalRecycler, healthyRecycler, internationalRecycler, budgetRecycler;
    
    // Adapters for different categories
    private HorizontalFoodAdapter traditionalAdapter, healthyAdapter, internationalAdapter, budgetAdapter;
    
    // Food lists for different categories
    private List<FoodRecommendation> traditionalFoods = new ArrayList<>();
    private List<FoodRecommendation> healthyFoods = new ArrayList<>();
    private List<FoodRecommendation> internationalFoods = new ArrayList<>();
    private List<FoodRecommendation> budgetFoods = new ArrayList<>();

    // Featured banner views
    private ImageView featuredBackgroundImage;
    private TextView featuredFoodName, featuredFoodDescription;
    
    private ExecutorService executorService;
    private Set<String> generatedFoodNames = new HashSet<>();
    private int generationCount = 0;
    private int foodsPerBatch = 10; // Track foods per batch
    private boolean isPreloading = false; // Prevent multiple simultaneous preloads
    private int consecutiveFailures = 0; // Track consecutive failures to prevent infinite loops
    
    // Nutrition insights views
    private TextView tvBmiStatus, tvCaloriesTarget, tvProteinTarget, tvFatTarget, tvCarbsTarget, tvHealthRecommendation;
    
    // User profile variables
    private String userAge;
    private String userSex;
    private String userBMI;
    private String userHeight;
    private String userWeight;
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
    
    // Gemini API configuration
    private static final String GEMINI_API_KEY = "AIzaSyAR0YOJALZphmQaSbc5Ydzs5kZS6eCefJM";
    private static final String GEMINI_TEXT_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" + GEMINI_API_KEY;
    private static final String GEMINI_IMAGE_API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=" + GEMINI_API_KEY;

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
        traditionalRecycler = findViewById(R.id.traditional_recycler);
        healthyRecycler = findViewById(R.id.healthy_recycler);
        internationalRecycler = findViewById(R.id.international_recycler);
        budgetRecycler = findViewById(R.id.budget_recycler);
        
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
        // Setup Traditional RecyclerView
        traditionalRecycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        traditionalAdapter = new HorizontalFoodAdapter(traditionalFoods, this, this);
        traditionalRecycler.setAdapter(traditionalAdapter);
        
        // Setup Healthy RecyclerView
        healthyRecycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        healthyAdapter = new HorizontalFoodAdapter(healthyFoods, this, this);
        healthyRecycler.setAdapter(healthyAdapter);
        
        // Setup International RecyclerView
        internationalRecycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        internationalAdapter = new HorizontalFoodAdapter(internationalFoods, this, this);
        internationalRecycler.setAdapter(internationalAdapter);
        
        // Setup Budget RecyclerView
        budgetRecycler.setLayoutManager(new LinearLayoutManager(this, LinearLayoutManager.HORIZONTAL, false));
        budgetAdapter = new HorizontalFoodAdapter(budgetFoods, this, this);
        budgetRecycler.setAdapter(budgetAdapter);
        
        Log.d(TAG, "All RecyclerViews setup completed");
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
        // Load traditional Filipino foods
        loadTraditionalFoods();
        
        // Load healthy options
        loadHealthyFoods();
        
        // Load international cuisine
        loadInternationalFoods();
        
        // Load budget-friendly options
        loadBudgetFoods();
    }
    
    private void loadTraditionalFoods() {
        // Traditional Filipino dishes
        String[] traditionalDishes = {
            "Adobo", "Sinigang", "Kare-kare", "Tinola", "Kaldereta", "Afritada", "Mechado", "Menudo",
            "Pancit", "Lumpia", "Tapsilog", "Sisig", "Bicol Express", "Chicken Inasal", "Lechon",
            "Crispy Pata", "Dinuguan", "Laing", "Ginataang Gulay", "Pinakbet"
        };
        
        List<FoodRecommendation> foods = createFoodListFromNames(traditionalDishes, "Traditional Filipino");
        traditionalFoods.clear();
        traditionalFoods.addAll(foods);
        traditionalAdapter.notifyDataSetChanged();
        Log.d(TAG, "Loaded " + foods.size() + " traditional foods");
    }
    
    private void loadHealthyFoods() {
        // Healthy Filipino dishes
        String[] healthyDishes = {
            "Ginisang Munggo", "Ginisang Ampalaya", "Ginisang Kangkong", "Ginisang Malunggay",
            "Sinigang na Isda", "Tinolang Manok", "Ginisang Togue", "Ginisang Baguio Beans",
            "Ginisang Sayote", "Ginisang Kalabasa", "Ginisang Talong", "Ginisang Okra"
        };
        
        List<FoodRecommendation> foods = createFoodListFromNames(healthyDishes, "Healthy");
        healthyFoods.clear();
        healthyFoods.addAll(foods);
        healthyAdapter.notifyDataSetChanged();
        Log.d(TAG, "Loaded " + foods.size() + " healthy foods");
    }
    
    private void loadInternationalFoods() {
        // International dishes popular in Philippines
        String[] internationalDishes = {
            "Chicken Teriyaki", "Beef Bulgogi", "Pad Thai", "Spaghetti Carbonara", "Burger",
            "Fried Chicken", "Pizza", "Sushi", "Ramen", "Curry", "Stir Fry", "Fish and Chips",
            "Chicken Curry", "Beef Steak", "Pork Chop", "Chicken Wings", "Tacos", "Burrito"
        };
        
        List<FoodRecommendation> foods = createFoodListFromNames(internationalDishes, "International");
        internationalFoods.clear();
        internationalFoods.addAll(foods);
        internationalAdapter.notifyDataSetChanged();
        Log.d(TAG, "Loaded " + foods.size() + " international foods");
    }
    
    private void loadBudgetFoods() {
        // Budget-friendly Filipino dishes
        String[] budgetDishes = {
            "Ginisang Munggo", "Ginisang Togue", "Ginisang Kangkong", "Ginisang Ampalaya",
            "Lugaw", "Arroz Caldo", "Goto", "Taho", "Pancit Canton", "Pancit Bihon",
            "Ginisang Sayote", "Ginisang Kalabasa", "Ginisang Talong", "Ginisang Okra"
        };
        
        List<FoodRecommendation> foods = createFoodListFromNames(budgetDishes, "Budget-Friendly");
        budgetFoods.clear();
        budgetFoods.addAll(foods);
        budgetAdapter.notifyDataSetChanged();
        Log.d(TAG, "Loaded " + foods.size() + " budget foods");
    }
    
    private List<FoodRecommendation> createFoodListFromNames(String[] dishNames, String category) {
        List<FoodRecommendation> foods = new ArrayList<>();
        Random random = new Random();
        
        for (String dishName : dishNames) {
            // Generate varied nutritional values
            int calories = 150 + random.nextInt(300); // 150-450 calories
            double protein = 8.0 + random.nextInt(20); // 8-28g protein
            double fat = 3.0 + random.nextInt(15); // 3-18g fat
            double carbs = 10.0 + random.nextInt(30); // 10-40g carbs
            
            FoodRecommendation food = new FoodRecommendation(
                dishName, calories, protein, fat, carbs, "1 serving", category, 
                "Delicious " + category.toLowerCase() + " dish"
            );
            foods.add(food);
        }
        
        return foods;
    }
    
    private List<FoodRecommendation> createFallbackFoods() {
        String[] fallbackDishes = {
            "Adobo", "Sinigang", "Kare-kare", "Tinola", "Kaldereta", "Afritada", "Mechado", "Menudo",
            "Pancit", "Lumpia", "Tapsilog", "Sisig", "Bicol Express", "Chicken Inasal", "Lechon"
        };
        
        return createFoodListFromNames(fallbackDishes, "Traditional Filipino");
    }
    
    @Override
    public void onFoodClick(FoodRecommendation food) {
        // Handle food item click - show detailed view or add to favorites
        Log.d(TAG, "Food clicked: " + food.getFoodName());
        // TODO: Implement detailed food view or add to favorites functionality
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
            
            if (userEmail != null) {
                // Load complete user profile from database
                UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(this);
        android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
                    "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + 
                    " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
                    new String[]{userEmail}
        );
        
        if (cursor.moveToFirst()) {
                    // Load all user profile data with safe column access
                    userAge = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_USER_AGE);
                    userSex = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_GENDER);
                    userBMI = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_USER_BMI);
                    userHeight = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_USER_HEIGHT);
                    userWeight = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_USER_WEIGHT);
                    userAllergies = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_ALLERGIES);
                    userDietPrefs = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_DIET_PREFS);
                    userAvoidFoods = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_AVOID_FOODS);
                    userRiskScore = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_RISK_SCORE);
                    userBarangay = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_BARANGAY);
                    userIncome = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_INCOME);
                    
                    // Load health conditions
                    userHealthConditions = buildHealthConditionsString(cursor);
                    
                    // Load pregnancy status from screening_answers JSON
                    String screeningAnswersJson = getStringFromCursor(cursor, UserPreferencesDbHelper.COL_SCREENING_ANSWERS);
                    userPregnancyStatus = loadPregnancyStatusFromScreeningJson(screeningAnswersJson);
                    
                    // Determine activity level based on risk score
                    userActivityLevel = determineActivityLevel(userRiskScore);
                    
                    // Determine budget level based on income
                    userBudgetLevel = determineBudgetLevel(userIncome);
                    
                    Log.d(TAG, "Loaded complete user profile: Age=" + userAge + ", Sex=" + userSex + 
                          ", BMI=" + userBMI + ", Health=" + userHealthConditions + 
                          ", Pregnancy=" + userPregnancyStatus + ", Allergies=" + userAllergies + 
                          ", Diet=" + userDietPrefs);
                } else {
                    Log.w(TAG, "No user profile found in database, using defaults");
                    setDefaultUserProfile();
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
    


    


    

    

    
    public List<FoodRecommendation> callGeminiAPIForMultiple() {
        return callGeminiAPIForMultipleWithRetry(0);
    }
    
    private FoodRecommendation callGeminiAPI() {
        List<FoodRecommendation> foods = callGeminiAPIForMultiple();
        if (!foods.isEmpty()) {
            return foods.get(0);
        }
        return null;
    }
    
        private List<FoodRecommendation> callGeminiAPIForMultipleWithRetry(int retryCount) {
        if (retryCount > 3) {
            Log.w(TAG, "Max retry count reached, returning fallback foods");
            return createFallbackFoods();
        }
        
        try {
            generationCount++;
            String masterPrompt = buildMasterPrompt();
            
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
            
            // Make API call
            OkHttpClient client = new OkHttpClient();
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
                    Log.d(TAG, "Gemini API response: " + responseText);
                    
                    List<FoodRecommendation> recommendations = parseFoodRecommendations(responseText);
                    if (recommendations != null && !recommendations.isEmpty()) {
                        // Verify that these are actual food dishes, not just ingredients
                        List<FoodRecommendation> verifiedRecommendations = verifyFoodRecommendations(recommendations);
                        
                        // Check if we have enough unique foods
                        List<FoodRecommendation> uniqueRecommendations = new ArrayList<>();
                        for (FoodRecommendation rec : verifiedRecommendations) {
                            if (!generatedFoodNames.contains(rec.getFoodName().toLowerCase().trim())) {
                                uniqueRecommendations.add(rec);
                            }
                        }
                        
                        // Return any unique foods we have (no minimum requirement)
                        if (uniqueRecommendations.size() > 0) {
                            Log.d(TAG, "Generated " + uniqueRecommendations.size() + " unique verified foods out of " + verifiedRecommendations.size() + " total");
                            return uniqueRecommendations;
        } else {
                            Log.d(TAG, "No unique foods found, retrying... (attempt " + (retryCount + 1) + ")");
                            // Sleep briefly before retry
                            Thread.sleep(1000);
                            return callGeminiAPIForMultipleWithRetry(retryCount + 1);
                        }
                    } else {
                        Log.w(TAG, "No recommendations parsed from response, retrying... (attempt " + (retryCount + 1) + ")");
                        // Sleep briefly before retry
                        Thread.sleep(1000);
                        return callGeminiAPIForMultipleWithRetry(retryCount + 1);
                    }
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error calling Gemini API: " + e.getMessage());
        }
        
        // If we get here, retry
        Log.d(TAG, "Retrying API call (attempt " + (retryCount + 1) + ")");
        try {
            Thread.sleep(1000);
        } catch (InterruptedException e) {
            Thread.currentThread().interrupt();
        }
        return callGeminiAPIForMultipleWithRetry(retryCount + 1);
    }
    
    private FoodRecommendation callGeminiAPIWithRetry(int retryCount) {
        List<FoodRecommendation> foods = callGeminiAPIForMultipleWithRetry(retryCount);
        if (foods != null && !foods.isEmpty()) {
            return foods.get(0);
        }
        return null;
    }
    
    private String buildMasterPrompt() {
        // Parse age to determine life stage
        int age = 25; // default
        try {
            age = Integer.parseInt(userAge != null ? userAge : "25");
        } catch (NumberFormatException e) {
            age = 25;
        }
        
        // Determine life stage and special considerations
        String lifeStage = determineLifeStage(age);
        String specialConsiderations = buildSpecialConsiderations(age);
        
        // Let Gemini generate diverse Filipino dishes based on user profile
        // No limited list - let AI access its vast knowledge of Filipino cuisine
        
        return "You are an expert nutritionist and chef. Generate EXACTLY 10 DIFFERENT food dishes available in the Philippines for: " +
               "Age: " + (userAge != null ? userAge : "25") + " (" + lifeStage + "), " +
               "Sex: " + (userSex != null ? userSex : "Not specified") + ", " +
               "BMI: " + (userBMI != null ? userBMI : "22.5") + ", " +
               "Health: " + (userHealthConditions != null ? userHealthConditions : "None") + ", " +
               "Budget: " + (userBudgetLevel != null ? userBudgetLevel : "Low") + ", " +
               "Allergies: " + (userAllergies != null && !userAllergies.isEmpty() ? userAllergies : "None") + ", " +
               "Diet: " + (userDietPrefs != null && !userDietPrefs.isEmpty() ? userDietPrefs : "None") + ", " +
               "Pregnancy: " + (userPregnancyStatus != null ? userPregnancyStatus : "Not Applicable") + ". " +
               "SPECIAL CONSIDERATIONS: " + specialConsiderations + " " +
               "REQUIREMENTS: " +
               "1. Choose from ANY food dishes available in the Philippines - Filipino, Asian, Western, International " +
               "2. Include traditional Filipino dishes, Asian cuisine, Western dishes, and international foods " +
               "3. Consider ingredients commonly available in Philippine markets and restaurants " +
               "4. Include dishes from all cuisines popular in the Philippines " +
               "5. Consider the user's health conditions and dietary restrictions " +
               "6. Ensure dishes are appropriate for the user's age and life stage " +
               "7. Include both popular and diverse food options " +
               "8. Consider budget-friendly options based on user's income level " +
               "9. Include vegetarian, seafood, meat, and international options " +
               "10. Each dish must be completely different from the previous recommendations " +
               "CRITICAL: Each dish must be different. NO REPEATS. " +
               "FOOD NAME RULES: " +
               "1. Keep food names SHORT (maximum 30 characters) " +
               "2. Use simple, clear names without unnecessary words " +
               "3. NO parentheses, brackets, or extra descriptions in food_name " +
               "4. NO words like 'Puree', 'Mash', 'Cream', 'Smoothie' in food_name " +
               "5. Use actual dish names that people recognize and order " +
               "6. Examples: 'Adobo', 'Sinigang', 'Kare-kare', 'Pancit', 'Tapsilog', 'Chicken Teriyaki', 'Beef Bulgogi', 'Pad Thai', 'Spaghetti', 'Burger' " +
               "7. NO numbers or special characters in food names " +
               "8. NO generic names like 'Dish' or 'Food' " +
               "9. NEVER use single ingredients like 'Apple', 'Carrot', 'Pear', 'Banana', 'Rice', 'Chicken', 'Beef', 'Tomato' - ONLY complete dishes " +
               "10. Avoid odd or made-up combinations " +
               "11. CRITICAL: Each food_name must be a complete dish that people order at restaurants or cook at home " +
               "12. Examples of GOOD names: 'Chicken Adobo', 'Beef Bulgogi', 'Pad Thai', 'Spaghetti Carbonara', 'Fish and Chips' " +
               "13. Examples of BAD names: 'Apple', 'Carrot', 'Pear', 'Rice', 'Chicken' (these are ingredients, not dishes) " +
               "NUTRITION REQUIREMENTS: " +
               "1. All nutritional information MUST be for 1 serving only " +
               "2. Use accurate, realistic nutrition data from reliable sources " +
               "3. Calories should be between 150-800 per serving " +
               "4. Protein should be between 5-40g per serving " +
               "5. Fat should be between 2-30g per serving " +
               "6. Carbs should be between 10-100g per serving " +
               "7. Ensure total calories = (protein × 4) + (fat × 9) + (carbs × 4) ± 10% " +
               "8. Use realistic serving sizes (e.g., 1 cup rice, 1 piece chicken, 1 bowl soup) " +
               "9. Verify nutrition data is appropriate for Filipino/Asian dishes " +
               "DESCRIPTION REQUIREMENTS: " +
               "1. Write simple, appetizing introductions for each dish " +
               "2. Focus on what makes the dish appealing and unique " +
               "3. Keep descriptions 1-2 sentences long " +
               "4. NO generic phrases like 'delicious food recommendation' " +
               "5. NO repetitive descriptions across different dishes " +
               "6. NO detailed nutrition analysis (that goes in ingredients button) " +
               "7. NO detailed ingredient lists (that goes in ingredients button) " +
               "8. Examples of good descriptions: " +
               "   - 'A classic Filipino dish featuring tender chicken braised in a savory soy-vinegar sauce with aromatic garlic and bay leaves.' " +
               "   - 'Fresh rice noodles stir-fried with crisp vegetables and succulent shrimp in a perfectly balanced sweet-savory sauce.' " +
               "   - 'Hearty beef stew simmered with root vegetables in a rich, flavorful broth that warms the soul.' " +
               "Return ONLY valid JSON array with 10 items: " +
               "[{\"food_name\": \"[SHORT DISH NAME]\", \"calories\": <number>, \"protein_g\": <number>, \"fat_g\": <number>, \"carbs_g\": <number>, \"serving_size\": \"1 serving\", \"diet_type\": \"[TYPE]\", \"description\": \"[SIMPLE APPETIZING INTRODUCTION]\"}, ...]";
    }
    
    private List<FoodRecommendation> verifyFoodRecommendations(List<FoodRecommendation> recommendations) {
        try {
            // First verify food names are actual dishes
            List<FoodRecommendation> verifiedDishes = verifyFoodNames(recommendations);
            
            // Then verify nutrition data accuracy
            List<FoodRecommendation> verifiedNutrition = verifyNutritionData(verifiedDishes);
            
            return verifiedNutrition;
        } catch (Exception e) {
            Log.e(TAG, "Error during food verification: " + e.getMessage());
            return recommendations;
        }
    }
    
    private List<FoodRecommendation> verifyFoodNames(List<FoodRecommendation> foods) {
        try {
            // Create verification prompt
            StringBuilder foodNames = new StringBuilder();
            for (int i = 0; i < foods.size(); i++) {
                foodNames.append((i + 1)).append(". ").append(foods.get(i).getFoodName());
                if (i < foods.size() - 1) {
                    foodNames.append("\n");
                }
            }
            
            String verificationPrompt = "You are a food expert. Review these food names and identify which ones are ACTUAL FOOD DISHES (complete meals/recipes) vs SINGLE INGREDIENTS.\n\n" +
                    "Food names to review:\n" + foodNames.toString() + "\n\n" +
                    "RULES:\n" +
                    "1. FOOD DISHES (GOOD): Complete meals, recipes, or prepared foods like 'Adobo', 'Chicken Teriyaki', 'Spaghetti', 'Beef Bulgogi', 'Pad Thai', 'Pizza', 'Burger', 'Sinigang', 'Kare-kare', 'Lechon', 'Congee', 'Broth', 'Mush'\n" +
                    "2. INGREDIENTS (BAD): Single raw ingredients like 'Apple', 'Carrot', 'Pear', 'Banana', 'Rice', 'Chicken', 'Beef', 'Tomato', 'Fish', 'Pork', 'Vegetables'\n" +
                    "3. NUMBERS ARE BAD: Any food name containing numbers (like 'Food 1', 'Dish 2', 'Recipe 3') should be REJECTED\n" +
                    "4. GENERIC NAMES ARE BAD: Names like 'Food', 'Dish', 'Recipe', 'Meal' should be REJECTED\n" +
                    "5. BE GENEROUS: If a food name could be a dish (even if it's simple), accept it\n" +
                    "6. Only return the numbers of ACTUAL FOOD DISHES (not ingredients, not numbers, not generic names)\n" +
                    "7. Return ONLY a comma-separated list of numbers (e.g., '1,3,5,7,9')\n" +
                    "8. When in doubt, accept the food as a dish\n\n" +
                    "Return ONLY the numbers of actual food dishes:";
            
            // Create JSON request for verification
            JSONObject requestBody = new JSONObject();
            JSONArray contents = new JSONArray();
            JSONObject content = new JSONObject();
            JSONArray parts = new JSONArray();
            JSONObject part = new JSONObject();
            part.put("text", verificationPrompt);
            parts.put(part);
            content.put("parts", parts);
            contents.put(content);
            requestBody.put("contents", contents);
            
            // Make API call
            OkHttpClient client = new OkHttpClient();
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
                    Log.d(TAG, "Verification response: " + responseText);
                    
                    // Parse the verification response
                    String verifiedNumbers = extractTextFromGeminiResponse(responseText);
                    if (verifiedNumbers != null && !verifiedNumbers.trim().isEmpty()) {
                        // Parse comma-separated numbers
                        String[] numbers = verifiedNumbers.trim().split(",");
                        List<FoodRecommendation> verifiedRecommendations = new ArrayList<>();
                        
                        for (String numberStr : numbers) {
                            try {
                                int index = Integer.parseInt(numberStr.trim()) - 1; // Convert to 0-based index
                                if (index >= 0 && index < foods.size()) {
                                    verifiedRecommendations.add(foods.get(index));
                                    Log.d(TAG, "Verified food dish: " + foods.get(index).getFoodName());
                                }
                            } catch (NumberFormatException e) {
                                Log.w(TAG, "Invalid number in verification response: " + numberStr);
                            }
                        }
                        
                        Log.d(TAG, "Verification complete: " + verifiedRecommendations.size() + " out of " + foods.size() + " are actual food dishes");
                        return verifiedRecommendations;
                    }
                        }
                    }
                } catch (Exception e) {
            Log.e(TAG, "Error during food verification: " + e.getMessage());
        }
        
        // If verification fails, return original foods
        Log.w(TAG, "Food name verification failed, returning original foods");
        return foods;
    }
    
    private List<FoodRecommendation> verifyNutritionData(List<FoodRecommendation> foods) {
        try {
            // Create nutrition verification prompt
            StringBuilder nutritionData = new StringBuilder();
            for (int i = 0; i < foods.size(); i++) {
                FoodRecommendation rec = foods.get(i);
                nutritionData.append((i + 1)).append(". ").append(rec.getFoodName())
                           .append(" - Calories: ").append(rec.getCalories())
                           .append(", Protein: ").append(rec.getProtein()).append("g")
                           .append(", Fat: ").append(rec.getFat()).append("g")
                           .append(", Carbs: ").append(rec.getCarbs()).append("g");
                if (i < foods.size() - 1) {
                    nutritionData.append("\n");
                }
            }
            
            String nutritionPrompt = "You are a nutrition expert. Verify the accuracy of these nutrition data for 1 serving of each dish.\n\n" +
                    "Nutrition data to verify:\n" + nutritionData.toString() + "\n\n" +
                    "VERIFICATION RULES:\n" +
                    "1. Check if calories = (protein × 4) + (fat × 9) + (carbs × 4) ± 10%\n" +
                    "2. Verify realistic ranges: Calories 150-800, Protein 5-40g, Fat 2-30g, Carbs 10-100g\n" +
                    "3. Consider typical Filipino/Asian serving sizes\n" +
                    "4. Return ONLY the numbers of dishes with ACCURATE nutrition data\n" +
                    "5. Return ONLY a comma-separated list of numbers (e.g., '1,3,5,7,9')\n\n" +
                    "Return ONLY the numbers of dishes with accurate nutrition:";
            
            // Create JSON request for verification
            JSONObject requestBody = new JSONObject();
            JSONArray contents = new JSONArray();
            JSONObject content = new JSONObject();
            JSONArray parts = new JSONArray();
            JSONObject part = new JSONObject();
            part.put("text", nutritionPrompt);
            parts.put(part);
            content.put("parts", parts);
            contents.put(content);
            requestBody.put("contents", contents);
            
            // Make API call
            OkHttpClient client = new OkHttpClient();
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
                    Log.d(TAG, "Nutrition verification response: " + responseText);
                    
                    // Parse the verification response
                    String verifiedNumbers = extractTextFromGeminiResponse(responseText);
                    if (verifiedNumbers != null && !verifiedNumbers.trim().isEmpty()) {
                        // Parse comma-separated numbers
                        String[] numbers = verifiedNumbers.trim().split(",");
                        List<FoodRecommendation> verifiedRecommendations = new ArrayList<>();
                        
                        for (String numberStr : numbers) {
                            try {
                                int index = Integer.parseInt(numberStr.trim()) - 1; // Convert to 0-based index
                                if (index >= 0 && index < foods.size()) {
                                    verifiedRecommendations.add(foods.get(index));
                                    Log.d(TAG, "Verified nutrition for: " + foods.get(index).getFoodName());
                                }
                            } catch (NumberFormatException e) {
                                Log.w(TAG, "Invalid number in nutrition verification response: " + numberStr);
                            }
                        }
                        
                        Log.d(TAG, "Nutrition verification complete: " + verifiedRecommendations.size() + " out of " + foods.size() + " have accurate nutrition");
                        return verifiedRecommendations;
                    }
                }
            } catch (Exception e) {
                Log.e(TAG, "Error during nutrition verification: " + e.getMessage());
            }
        } catch (Exception e) {
            Log.e(TAG, "Error during nutrition verification: " + e.getMessage());
        }
        
        // If verification fails, return original foods
        Log.w(TAG, "Nutrition verification failed, returning original foods");
        return foods;
    }
    
    private String extractTextFromGeminiResponse(String responseText) {
        try {
            JSONObject geminiResponse = new JSONObject(responseText);
            JSONArray candidates = geminiResponse.getJSONArray("candidates");
            
            if (candidates.length() > 0) {
                JSONObject candidate = candidates.getJSONObject(0);
                JSONObject content = candidate.getJSONObject("content");
                JSONArray parts = content.getJSONArray("parts");
                
                if (parts.length() > 0) {
                    JSONObject part = parts.getJSONObject(0);
                    String text = part.getString("text");
                    Log.d(TAG, "Extracted verification text: " + text);
                    return text;
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error extracting text from Gemini response: " + e.getMessage());
        }
        return null;
    }
    
    private String determineLifeStage(int age) {
        if (age < 1) return "Infant (0-12 months)";
        else if (age < 3) return "Toddler (1-3 years)";
        else if (age < 6) return "Preschool (3-6 years)";
        else if (age < 12) return "School Age (6-12 years)";
        else if (age < 18) return "Adolescent (12-18 years)";
        else if (age < 50) return "Adult (18-50 years)";
        else if (age < 65) return "Middle Age (50-65 years)";
        else return "Senior (65+ years)";
    }
    
    private String buildSpecialConsiderations(int age) {
        StringBuilder considerations = new StringBuilder();
        
        // Age-specific considerations
        if (age < 1) {
            considerations.append("INFANT: Only recommend soft, pureed foods. NO hard foods, NO choking hazards. ");
            considerations.append("Focus on: rice porridge (lugaw), mashed vegetables, soft fruits. ");
            considerations.append("Avoid: nuts, seeds, hard vegetables, spicy foods, raw foods. ");
        } else if (age < 3) {
            considerations.append("TODDLER: Recommend soft, easy-to-chew foods. Small portions. ");
            considerations.append("Focus on: rice, soft vegetables, lean proteins, fruits. ");
            considerations.append("Avoid: hard foods, spicy foods, large chunks. ");
        } else if (age < 6) {
            considerations.append("PRESCHOOL: Recommend balanced meals with variety. Moderate portions. ");
            considerations.append("Focus on: whole grains, vegetables, lean proteins, fruits. ");
            considerations.append("Avoid: excessive salt, sugar, processed foods. ");
        } else if (age < 12) {
            considerations.append("SCHOOL AGE: Recommend nutrient-dense foods for growth and learning. ");
            considerations.append("Focus on: protein, complex carbs, healthy fats, vitamins. ");
            considerations.append("Avoid: excessive junk food, sugary drinks. ");
        } else if (age < 18) {
            considerations.append("ADOLESCENT: Recommend foods supporting growth and development. ");
            considerations.append("Focus on: protein, calcium, iron, vitamins. ");
            considerations.append("Avoid: excessive processed foods, sugary drinks. ");
        } else if (age >= 50) {
            considerations.append("OLDER ADULT: Recommend heart-healthy, bone-strengthening foods. ");
            considerations.append("Focus on: lean proteins, fiber, calcium, antioxidants. ");
            considerations.append("Avoid: excessive salt, saturated fats, processed foods. ");
        }
        
        // Pregnancy considerations
        if ("Female".equalsIgnoreCase(userSex) && age >= 12 && age <= 50) {
            if ("Yes".equalsIgnoreCase(userPregnancyStatus)) {
                considerations.append("PREGNANT: CRITICAL - Avoid: raw fish, unpasteurized dairy, undercooked meat, ");
                considerations.append("excessive caffeine, alcohol, high-mercury fish, soft cheeses, deli meats. ");
                considerations.append("Focus on: folate-rich foods (malunggay, spinach), iron (lean meat, beans), ");
                considerations.append("calcium (milk, yogurt), protein (chicken, fish), omega-3 (safe fish). ");
                considerations.append("Recommend: Lugaw with chicken, Ginisang Malunggay, Tinola, boiled eggs. ");
            }
        }
        
        // Health condition considerations
        if (userHealthConditions != null && !userHealthConditions.equals("None")) {
            if (userHealthConditions.contains("Diabetes")) {
                considerations.append("DIABETES: Recommend low glycemic index foods, complex carbs, fiber. ");
                considerations.append("Avoid: excessive simple sugars, refined carbs. ");
            }
            if (userHealthConditions.contains("Hypertension")) {
                considerations.append("HYPERTENSION: Recommend low-sodium foods, potassium-rich foods. ");
                considerations.append("Avoid: excessive salt, processed foods, canned foods. ");
            }
            if (userHealthConditions.contains("Heart Disease")) {
                considerations.append("HEART DISEASE: Recommend heart-healthy foods, omega-3 rich foods. ");
                considerations.append("Avoid: excessive saturated fats, trans fats, cholesterol. ");
            }
            if (userHealthConditions.contains("Kidney Disease")) {
                considerations.append("KIDNEY DISEASE: Recommend low-protein, low-sodium foods. ");
                considerations.append("Avoid: excessive protein, salt, potassium-rich foods. ");
            }
        }
        
        return considerations.toString();
    }
    
    private boolean isDuplicate(FoodRecommendation recommendation) {
        if (recommendation == null || recommendation.getFoodName() == null) {
            return false;
        }
        
        String foodName = recommendation.getFoodName().toLowerCase().trim();
        
        // Only check exact match
        if (generatedFoodNames.contains(foodName)) {
            Log.d(TAG, "Duplicate detected: " + foodName);
            return true;
        }
        
        Log.d(TAG, "No duplicate found for: " + foodName);
        return false;
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
                        
                        // Extract JSON array from the text content
                        int arrayStart = textContent.indexOf("[");
                        int arrayEnd = textContent.lastIndexOf("]") + 1;
                        
                        if (arrayStart >= 0 && arrayEnd > arrayStart) {
                            String jsonArrayString = textContent.substring(arrayStart, arrayEnd);
                            Log.d(TAG, "Extracted JSON array: " + jsonArrayString);
                            
                            JSONArray foodArray = new JSONArray(jsonArrayString);
                            
                            for (int j = 0; j < foodArray.length(); j++) {
                                try {
                                    JSONObject foodJson = foodArray.getJSONObject(j);
                                    
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
                                    
                                    Log.d(TAG, "Parsed food: " + foodName + " - Calories: " + calories + 
                                              ", Protein: " + protein + ", Fat: " + fat + ", Carbs: " + carbs);
                                    
                                    FoodRecommendation recommendation = new FoodRecommendation(
                                        foodName, calories, protein, fat, carbs, servingSize, dietType, description
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
        } catch (JSONException e) {
            Log.e(TAG, "Error parsing JSON: " + e.getMessage());
            Log.e(TAG, "Response text: " + responseText);
        }
        
        return foods;
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

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (executorService != null) {
            executorService.shutdown();
        }
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

} 