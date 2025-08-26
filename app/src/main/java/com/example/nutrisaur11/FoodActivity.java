package com.example.nutrisaur11;

import android.app.AlertDialog;
import android.content.ContentValues;
import android.content.Context;
import android.content.Intent;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;
import android.os.Bundle;
import android.text.InputType;
import android.view.View;
import android.widget.Button;
import android.widget.ImageButton;
import android.widget.TextView;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.EditText;
import androidx.cardview.widget.CardView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import java.util.*;
import com.example.nutrisaur11.ScreeningResultStore;
import com.example.nutrisaur11.UserPreferencesDbHelper;
import android.widget.Toast;
import android.os.AsyncTask;
import okhttp3.*;
import org.json.JSONObject;
import com.example.nutrisaur11.MainActivity;
import com.example.nutrisaur11.FoodCardAdapter;
import com.example.nutrisaur11.NutritionRecommendationEngine;
import com.example.nutrisaur11.FoodImageHelper;
import com.example.nutrisaur11.DishInfo;
import org.json.JSONArray;
import okhttp3.MediaType;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;
import android.util.Log;
import com.example.nutrisaur11.Constants;
import android.widget.ImageView;
import com.example.nutrisaur11.SmartFilterEngine;

// Use DishData.Dish and UserPreferencesDbHelper from the same package
public class FoodActivity extends AppCompatActivity implements FoodCardAdapter.OnInfoClickListener {
    private UserPreferencesDbHelper dbHelper;
    private static final String[] ALLERGENS = {"Peanuts", "Dairy", "Eggs", "Shellfish", "Gluten", "Soy", "Fish", "Tree nuts"};
    private static final String[] DIET_PREFS = {"Vegetarian", "Vegan", "Halal", "Kosher", "Pescatarian"};
    private static final List<DishData.Dish> DISHES = DishData.DISHES;
    private RecyclerView foodRecyclerView;
    private FoodCardAdapter foodCardAdapter;
    private List<DishData.Dish> currentRecommendations = new ArrayList<>();
    private boolean isLoading = false;
    private int failedTries = 0;
    private static final int MAX_RETRIES = 5;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_food);
        
        // Initialize database helper
        dbHelper = new UserPreferencesDbHelper(this);
        
        // Initialize FoodImageHelper
        FoodImageHelper.initialize(this);
        
        // Setup RecyclerView with performance optimizations
        foodRecyclerView = findViewById(R.id.food_recycler_view);
        foodRecyclerView.setLayoutManager(new GridLayoutManager(this, 2));
        
        // Performance optimizations
        foodRecyclerView.setHasFixedSize(true);
        foodRecyclerView.setItemViewCacheSize(20);
        foodRecyclerView.setDrawingCacheEnabled(true);
        foodRecyclerView.setDrawingCacheQuality(View.DRAWING_CACHE_QUALITY_HIGH);
        
        foodCardAdapter = new FoodCardAdapter(currentRecommendations, dish -> showSubstitutionPanel(dish), this, this, getCurrentUserEmail());
        foodRecyclerView.setAdapter(foodCardAdapter);
        
        // Setup optimized infinite scroll with performance safeguards
        foodRecyclerView.addOnScrollListener(new RecyclerView.OnScrollListener() {
            private static final int SCROLL_THRESHOLD = 5; // Load more when 5 items away from end
            private static final long MIN_LOAD_INTERVAL = 1000; // Minimum 1 second between loads
            private long lastLoadTime = 0;
            
            @Override
            public void onScrolled(RecyclerView recyclerView, int dx, int dy) {
                super.onScrolled(recyclerView, dx, dy);
                
                // Prevent excessive loading at edges
                if (isLoading || currentRecommendations.size() < 10) {
                    return;
                }
                
                // Rate limiting to prevent rapid successive loads
                long currentTime = System.currentTimeMillis();
                if (currentTime - lastLoadTime < MIN_LOAD_INTERVAL) {
                    return;
                }
                
                GridLayoutManager layoutManager = (GridLayoutManager) recyclerView.getLayoutManager();
                if (layoutManager != null) {
                    int lastVisiblePosition = layoutManager.findLastVisibleItemPosition();
                    int totalItems = currentRecommendations.size();
                    
                    // Only load more if we're approaching the end (not at the very edge)
                    if (lastVisiblePosition >= totalItems - SCROLL_THRESHOLD) {
                        lastLoadTime = currentTime;
                        loadMoreFoods();
                    }
                }
            }
        });
        
        // Setup buttons
        setupButtons();
        
        // Load recommendations in background using existing sophisticated system
        new android.os.Handler().post(() -> loadOrFetchRecommendations());
        
        // Show current screening data summary
        showScreeningDataSummary();
    }
    
    private void setupButtons() {
        Button customizeBtn = findViewById(R.id.btn_customize_recommendations);
        if (customizeBtn != null) {
            customizeBtn.setOnClickListener(v -> showFilterDialog());
        }
        
        Button refreshBtn = findViewById(R.id.btn_refresh);
        if (refreshBtn != null) {
            refreshBtn.setOnClickListener(v -> {
                // Clear cached recommendations and reload based on current screening data
                dbHelper.getWritableDatabase().delete(UserPreferencesDbHelper.TABLE_FOOD_RECS, null, null);
                currentRecommendations.clear();
                foodCardAdapter.notifyDataSetChanged();
                
                android.widget.Toast.makeText(FoodActivity.this, 
                    "🔄 Refreshing recommendations based on your latest screening data...", 
                    android.widget.Toast.LENGTH_SHORT).show();
                
                // Use AI engine for fresh recommendations
                loadAIRecommendations();
            });
        }
        
        // Setup bottom navigation bar (use explicit nav bar IDs from activity_food.xml)
        findViewById(R.id.nav_home).setOnClickListener(v -> {
            Intent intent = new Intent(FoodActivity.this, MainActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        findViewById(R.id.nav_food).setOnClickListener(v -> {
            // Already in FoodActivity, do nothing
        });
        findViewById(R.id.nav_favorites).setOnClickListener(v -> {
            Intent intent = new Intent(FoodActivity.this, FavoritesActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        findViewById(R.id.nav_account).setOnClickListener(v -> {
            Intent intent = new Intent(FoodActivity.this, AccountActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        // Setup close button for substitution bottom sheet
        View closeBtn = findViewById(R.id.close_substitution);
        if (closeBtn != null) {
            closeBtn.setOnClickListener(v -> hideSubstitutionPanel());
        }
        
        // Setup close button for dish info panel
        View closeDishInfoBtn = findViewById(R.id.close_dish_info);
        if (closeDishInfoBtn != null) {
            closeDishInfoBtn.setOnClickListener(v -> hideDishInfoPanel());
        }
    }
    
    private void showFilterDialog() {
        FilterDialog filterDialog = new FilterDialog(this, new FilterDialog.OnFilterAppliedListener() {
            @Override
            public void onFilterApplied(List<String> allergies, List<String> dietPrefs, String avoidFoods) {
                // Combine allergies, diet preferences, and avoid foods into active filters
                List<String> activeFilters = new ArrayList<>();
                
                // Add allergies as filters
                if (allergies != null) {
                    activeFilters.addAll(allergies);
                }
                
                // Add diet preferences as filters (both dietary restrictions and nutritional preferences)
                if (dietPrefs != null) {
                    for (String pref : dietPrefs) {
                        String prefLower = pref.trim().toLowerCase();
                        
                        // Add the actual dietary preference (Vegan, Vegetarian, etc.)
                        activeFilters.add(pref);
                        
                        // Also extract nutritional filters for enhanced recommendations
                        if (prefLower.contains("high-protein") || prefLower.contains("protein")) {
                            activeFilters.add("high-protein");
                        }
                        if (prefLower.contains("high-energy") || prefLower.contains("energy")) {
                            activeFilters.add("high-energy");
                        }
                        if (prefLower.contains("iron") || prefLower.contains("iron-rich")) {
                            activeFilters.add("iron-rich");
                        }
                        if (prefLower.contains("vitamin") || prefLower.contains("vitamin-rich")) {
                            activeFilters.add("vitamin-rich");
                        }
                        if (prefLower.contains("fiber") || prefLower.contains("fiber-rich")) {
                            activeFilters.add("fiber-rich");
                        }
                        if (prefLower.contains("calcium") || prefLower.contains("calcium-rich")) {
                            activeFilters.add("calcium-rich");
                        }
                        if (prefLower.contains("low-calorie") || prefLower.contains("calorie")) {
                            activeFilters.add("low-calorie");
                        }
                    }
                }
                
                // Add avoid foods as filters
                if (avoidFoods != null && !avoidFoods.trim().isEmpty()) {
                    activeFilters.add("avoid:" + avoidFoods.trim());
                }
                
                android.util.Log.d("FoodActivity", "Combined active filters: " + activeFilters);
                
                // Apply smart filtering with active filters
                updateFoodRecommendationsWithFilters(activeFilters);
            }
        });
        filterDialog.show();
    }

    private String getCurrentUserEmail() {
        return getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).getString("current_user_email", null);
    }

    private boolean userHasDietPrefs() {
        String email = getCurrentUserEmail();
        if (email == null) return false;
        Cursor cursor = dbHelper.getReadableDatabase().rawQuery("SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
        boolean exists = cursor.moveToFirst();
        cursor.close();
        return exists;
    }

    private void showDietPrefsDialog(Runnable onComplete) {
        boolean[] checkedAllergies = new boolean[ALLERGENS.length];
        boolean[] checkedPrefs = new boolean[DIET_PREFS.length];
        List<String> selectedAllergies = new ArrayList<>();
        List<String> selectedPrefs = new ArrayList<>();
        
        // Get current risk score asynchronously to preserve it
        ScreeningResultStore.getRiskScoreAsync(this, new ScreeningResultStore.OnRiskScoreReceivedListener() {
            @Override
            public void onRiskScoreReceived(int currentRiskScore) {
                showDietPrefsDialogWithRiskScore(currentRiskScore, onComplete, checkedAllergies, checkedPrefs, selectedAllergies, selectedPrefs);
            }
        });
    }
    
    private void showCurrentScreeningData() {
        String email = getCurrentUserEmail();
        if (email == null) {
            android.widget.Toast.makeText(this, "No user data available", android.widget.Toast.LENGTH_SHORT).show();
            return;
        }
        
        // Get current screening data from database
        android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
            "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
            new String[]{email}
        );
        
        if (cursor.moveToFirst()) {
            int riskScore = cursor.getInt(cursor.getColumnIndex(UserPreferencesDbHelper.COL_RISK_SCORE));
            String screeningAnswers = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_SCREENING_ANSWERS));
            
            StringBuilder message = new StringBuilder();
            message.append("📊 Current Screening Data:\n\n");
            message.append("Risk Score: ").append(riskScore).append("%\n");
            
            if (screeningAnswers != null && !screeningAnswers.isEmpty()) {
                try {
                    org.json.JSONObject screening = new org.json.JSONObject(screeningAnswers);
                    message.append("Screening Answers:\n");
                    
                    // Show key screening fields
                    String[] keyFields = {"swelling", "weight_loss", "feeding_behavior", "physical_signs", 
                                        "dietary_diversity", "muac", "has_recent_illness", "has_eating_difficulty"};
                    
                    for (String field : keyFields) {
                        if (screening.has(field)) {
                            String value = screening.getString(field);
                            message.append("• ").append(field.replace("_", " ").toUpperCase()).append(": ").append(value).append("\n");
                        }
                    }
                } catch (Exception e) {
                    message.append("Error parsing screening data: ").append(e.getMessage());
                }
            } else {
                message.append("No screening data available");
            }
            
            new AlertDialog.Builder(this)
                .setTitle("Current Screening Data")
                .setMessage(message.toString())
                .setPositiveButton("OK", null)
                .show();
        }
        cursor.close();
    }
    

    
    private void showScreeningDataSummary() {
        String email = getCurrentUserEmail();
        if (email == null) return;
        
        // Get current screening data
        android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
            "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
            new String[]{email}
        );
        
        if (cursor.moveToFirst()) {
            int riskScore = cursor.getInt(cursor.getColumnIndex(UserPreferencesDbHelper.COL_RISK_SCORE));
            String screeningAnswers = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_SCREENING_ANSWERS));
            
            // Show a brief summary of what's being used for recommendations
            String summary = "🎯 Recommendations based on:\n";
            summary += "Risk Score: " + riskScore + "%\n";
            
            if (screeningAnswers != null && !screeningAnswers.isEmpty()) {
                try {
                    org.json.JSONObject screening = new org.json.JSONObject(screeningAnswers);
                    if (screening.has("swelling") && "yes".equals(screening.getString("swelling"))) {
                        summary += "⚠️ Edema detected\n";
                    }
                    if (screening.has("weight_loss")) {
                        String weightLoss = screening.getString("weight_loss");
                        if (">10%".equals(weightLoss)) {
                            summary += "📉 Severe weight loss\n";
                        } else if ("5-10%".equals(weightLoss)) {
                            summary += "📉 Moderate weight loss\n";
                        }
                    }
                    if (screening.has("feeding_behavior")) {
                        String feeding = screening.getString("feeding_behavior");
                        if ("poor appetite".equals(feeding)) {
                            summary += "🍽️ Poor appetite\n";
                        }
                    }
                } catch (Exception e) {
                    summary += "📊 Screening data available\n";
                }
            } else {
                summary += "📊 Basic screening data\n";
            }
            
            // Show as a toast message
            android.widget.Toast.makeText(this, summary, android.widget.Toast.LENGTH_LONG).show();
        }
        cursor.close();
    }
    
    private void showTestScenariosDialog() {
        String[] scenarios = {
            "High Risk (80%+) - Severe Malnutrition",
            "High Risk (50-79%) - Moderate Malnutrition", 
            "Moderate Risk (20-49%) - Growth Issues",
            "Low Risk (0-19%) - Normal Status"
        };
        
        new AlertDialog.Builder(this)
            .setTitle("Test Different Screening Scenarios")
            .setItems(scenarios, (dialog, which) -> {
                String email = getCurrentUserEmail();
                if (email == null) {
                    android.widget.Toast.makeText(this, "No user data available", android.widget.Toast.LENGTH_SHORT).show();
                    return;
                }
                
                // Simulate different screening scenarios
                simulateScreeningScenario(email, which);
            })
            .setNegativeButton("Cancel", null)
            .show();
    }
    
    private void simulateScreeningScenario(String email, int scenarioIndex) {
        // Create different screening data for testing
        org.json.JSONObject screeningData = new org.json.JSONObject();
        
        try {
            switch (scenarioIndex) {
                case 0: // High Risk - Severe Malnutrition
                    screeningData.put("risk_score", 85);
                    screeningData.put("swelling", "yes");
                    screeningData.put("weight_loss", ">10%");
                    screeningData.put("feeding_behavior", "poor appetite");
                    screeningData.put("physical_signs", "thin,weak");
                    screeningData.put("muac", "11.0");
                    screeningData.put("has_recent_illness", true);
                    screeningData.put("has_eating_difficulty", true);
                    screeningData.put("has_food_insecurity", true);
                    break;
                    
                case 1: // High Risk - Moderate Malnutrition
                    screeningData.put("risk_score", 65);
                    screeningData.put("swelling", "no");
                    screeningData.put("weight_loss", "5-10%");
                    screeningData.put("feeding_behavior", "moderate appetite");
                    screeningData.put("physical_signs", "thin");
                    screeningData.put("muac", "12.0");
                    screeningData.put("has_recent_illness", false);
                    screeningData.put("has_eating_difficulty", false);
                    screeningData.put("has_food_insecurity", false);
                    break;
                    
                case 2: // Moderate Risk - Growth Issues
                    screeningData.put("risk_score", 35);
                    screeningData.put("swelling", "no");
                    screeningData.put("weight_loss", "<5% or none");
                    screeningData.put("feeding_behavior", "good appetite");
                    screeningData.put("physical_signs", "shorter");
                    screeningData.put("muac", "13.0");
                    screeningData.put("has_recent_illness", false);
                    screeningData.put("has_eating_difficulty", false);
                    screeningData.put("has_food_insecurity", false);
                    break;
                    
                case 3: // Low Risk - Normal Status
                    screeningData.put("risk_score", 15);
                    screeningData.put("swelling", "no");
                    screeningData.put("weight_loss", "<5% or none");
                    screeningData.put("feeding_behavior", "good appetite");
                    screeningData.put("physical_signs", "none");
                    screeningData.put("muac", "14.0");
                    screeningData.put("has_recent_illness", false);
                    screeningData.put("has_eating_difficulty", false);
                    screeningData.put("has_food_insecurity", false);
                    break;
            }
            
            // Save to database
            android.content.ContentValues values = new android.content.ContentValues();
            values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
            values.put(UserPreferencesDbHelper.COL_RISK_SCORE, screeningData.getInt("risk_score"));
            values.put(UserPreferencesDbHelper.COL_SCREENING_ANSWERS, screeningData.toString());
            
            dbHelper.getWritableDatabase().update(
                UserPreferencesDbHelper.TABLE_NAME,
                values,
                UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
                new String[]{email}
            );
            
            android.widget.Toast.makeText(this, 
                "🧪 Test scenario applied! Refreshing recommendations...", 
                android.widget.Toast.LENGTH_SHORT).show();
            
            // Reload recommendations with new scenario
            loadAIRecommendations();
            
        } catch (Exception e) {
            android.widget.Toast.makeText(this, "Error applying test scenario: " + e.getMessage(), android.widget.Toast.LENGTH_SHORT).show();
        }
    }
    
    private void showCurrentRecommendationsSummary() {
        if (currentRecommendations.isEmpty()) {
            android.widget.Toast.makeText(this, "No recommendations loaded yet", android.widget.Toast.LENGTH_SHORT).show();
            return;
        }
        
        StringBuilder summary = new StringBuilder();
        summary.append("🍽️ Current Food Recommendations:\n\n");
        summary.append("Total: ").append(currentRecommendations.size()).append(" dishes\n\n");
        
        // Group by categories (simplified)
        Map<String, List<String>> categories = new HashMap<>();
        for (DishData.Dish dish : currentRecommendations) {
            String category = "General";
            if (dish.tags.contains("HP")) category = "High Protein";
            else if (dish.tags.contains("ED")) category = "Energy Dense";
            else if (dish.tags.contains("WG")) category = "Weight Gain";
            else if (dish.tags.contains("TH")) category = "Therapeutic";
            else if (dish.tags.contains("HVC")) category = "Vitamin C Rich";
            else if (dish.tags.contains("HVA")) category = "Vitamin A Rich";
            else if (dish.tags.contains("HI")) category = "Iron Rich";
            
            categories.computeIfAbsent(category, k -> new ArrayList<>()).add(dish.name);
        }
        
        for (Map.Entry<String, List<String>> entry : categories.entrySet()) {
            summary.append(entry.getKey()).append(":\n");
            for (String dishName : entry.getValue()) {
                summary.append("  • ").append(dishName).append("\n");
            }
            summary.append("\n");
        }
        
        new AlertDialog.Builder(this)
            .setTitle("Current Recommendations Summary")
            .setMessage(summary.toString())
            .setPositiveButton("OK", null)
            .show();
    }
    
    private void forceNewAIRecommendations() {
        android.widget.Toast.makeText(this, 
            "🧠 Forcing new AI recommendations...", 
            android.widget.Toast.LENGTH_SHORT).show();
        
        // Clear current recommendations
        currentRecommendations.clear();
        foodCardAdapter.notifyDataSetChanged();
        
        // Force reload with AI engine
        loadAIRecommendations();
    }
    
    private void showDietPrefsDialogWithRiskScore(int currentRiskScore, Runnable onComplete, boolean[] checkedAllergies, boolean[] checkedPrefs, 
                                                 List<String> selectedAllergies, List<String> selectedPrefs) {
        new AlertDialog.Builder(this)
            .setTitle("Select your allergies")
            .setMultiChoiceItems(ALLERGENS, checkedAllergies, (dialog, which, isChecked) -> {
                if (isChecked) selectedAllergies.add(ALLERGENS[which]);
                else selectedAllergies.remove(ALLERGENS[which]);
            })
            .setPositiveButton("Next", (dialog, which) -> {
                new AlertDialog.Builder(this)
                    .setTitle("Select your dietary preferences")
                    .setMultiChoiceItems(DIET_PREFS, checkedPrefs, (d2, w2, isC) -> {
                        if (isC) selectedPrefs.add(DIET_PREFS[w2]);
                        else selectedPrefs.remove(DIET_PREFS[w2]);
                    })
                    .setPositiveButton("Next", (d2, w2) -> {
                        final EditText avoidInput = new EditText(this);
                        avoidInput.setHint("Foods to avoid (optional)");
                        avoidInput.setInputType(InputType.TYPE_CLASS_TEXT);
                        new AlertDialog.Builder(this)
                            .setTitle("Any foods to avoid?")
                            .setView(avoidInput)
                            .setPositiveButton("Save", (d3, w3) -> {
                                String avoid = avoidInput.getText().toString();
                                saveUserPreferences(selectedAllergies, selectedPrefs, avoid, currentRiskScore);
                                if (onComplete != null) onComplete.run();
                            })
                            .setNegativeButton("Skip", (d3, w3) -> {
                                saveUserPreferences(selectedAllergies, selectedPrefs, "", currentRiskScore);
                                if (onComplete != null) onComplete.run();
                            })
                            .show();
                    })
                    .setNegativeButton("Skip", (d2, w2) -> {
                        saveUserPreferences(selectedAllergies, new ArrayList<>(), "", currentRiskScore);
                        if (onComplete != null) onComplete.run();
                    })
                    .show();
            })
            .setNegativeButton("Skip", (dialog, which) -> {
                if (onComplete != null) onComplete.run();
            })
            .show();
    }
    
    private void saveUserPreferences(List<String> allergies, List<String> dietPrefs, String avoidFoods, int riskScore) {
        String email = getCurrentUserEmail();
        if (email == null) return;
        
        ContentValues values = new ContentValues();
        values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
        values.put(UserPreferencesDbHelper.COL_ALLERGIES, join(allergies));
        values.put(UserPreferencesDbHelper.COL_DIET_PREFS, join(dietPrefs));
        values.put(UserPreferencesDbHelper.COL_AVOID_FOODS, avoidFoods);
        values.put(UserPreferencesDbHelper.COL_RISK_SCORE, riskScore);
        
        // Safe upsert: UPDATE existing row; INSERT only if missing
        android.database.sqlite.SQLiteDatabase db = dbHelper.getWritableDatabase();
        int updated = db.update(
            UserPreferencesDbHelper.TABLE_NAME,
            values,
            UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
            new String[]{email}
        );
        if (updated == 0) {
            db.insert(UserPreferencesDbHelper.TABLE_NAME, null, values);
        }
        
        // Sync with web API
        syncPreferencesToApi(email, allergies, dietPrefs, avoidFoods, riskScore);
    }
    
    // New method to sync preferences with web API
    private void syncPreferencesToApi(String email, List<String> allergies, List<String> dietPrefs, String avoidFoods, int riskScore) {
        new Thread(() -> {
            try {
                OkHttpClient client = new OkHttpClient();
                
                JSONObject json = new JSONObject();
                json.put("action", "save_preferences");
                json.put("email", email);
                json.put("allergies", new JSONArray(allergies));
                json.put("diet_prefs", new JSONArray(dietPrefs));
                json.put("avoid_foods", avoidFoods);
                json.put("risk_score", riskScore);
                
                RequestBody body = RequestBody.create(json.toString(), MediaType.parse("application/json"));
                Request request = new Request.Builder()
                    .url(Constants.UNIFIED_API_URL)
                    .post(body)
                    .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        Log.d("FoodActivity", "Preferences synced to API successfully");
                    } else {
                        Log.e("FoodActivity", "Failed to sync preferences: " + response.code());
                    }
                }
            } catch (Exception e) {
                Log.e("FoodActivity", "Error syncing preferences: " + e.getMessage());
            }
        }).start();
    }
    
    // New method to sync screening results with web API
    private void syncScreeningToApi(String email, int riskScore, List<String> screeningAnswers) {
        new Thread(() -> {
            try {
                OkHttpClient client = new OkHttpClient();
                
                JSONObject json = new JSONObject();
                json.put("action", "save_screening");
                json.put("email", email);
                json.put("risk_score", riskScore);
                json.put("screening_data", new JSONArray(screeningAnswers));
                
                RequestBody body = RequestBody.create(json.toString(), MediaType.parse("application/json"));
                Request request = new Request.Builder()
                    .url(Constants.UNIFIED_API_URL)
                    .post(body)
                    .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        Log.d("FoodActivity", "Screening data synced to API successfully");
                    } else {
                        Log.e("FoodActivity", "Failed to sync screening: " + response.code());
                    }
                }
            } catch (Exception e) {
                Log.e("FoodActivity", "Error syncing screening: " + e.getMessage());
            }
        }).start();
    }
    
    // New method to sync food recommendations with web API
    private void syncFoodRecommendationsToApi(String email, List<DishData.Dish> recommendations) {
        new Thread(() -> {
            try {
                OkHttpClient client = new OkHttpClient();
                
                JSONArray recsArray = new JSONArray();
                for (DishData.Dish dish : recommendations) {
                    JSONObject dishJson = new JSONObject();
                    dishJson.put("name", dish.name);
                    dishJson.put("emoji", dish.emoji);
                    dishJson.put("desc", dish.desc);
                    recsArray.put(dishJson);
                }
                
                JSONObject json = new JSONObject();
                json.put("action", "save_food_recommendations");
                json.put("email", email);
                json.put("recommendations", recsArray);
                
                RequestBody body = RequestBody.create(json.toString(), MediaType.parse("application/json"));
                Request request = new Request.Builder()
                    .url(Constants.UNIFIED_API_URL)
                    .post(body)
                    .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        Log.d("FoodActivity", "Food recommendations synced to API successfully");
                    } else {
                        Log.e("FoodActivity", "Failed to sync recommendations: " + response.code());
                    }
                }
            } catch (Exception e) {
                Log.e("FoodActivity", "Error syncing recommendations: " + e.getMessage());
            }
        }).start();
    }

    private String join(List<String> list) {
        return android.text.TextUtils.join(",", list);
    }
    
    /**
     * Update food recommendations with smart filtering
     */
    private void updateFoodRecommendationsWithFilters(List<String> activeFilters) {
        String email = getCurrentUserEmail();
        if (email == null) {
            android.util.Log.e("FoodActivity", "No current user email found");
            return;
        }
        
        android.util.Log.d("FoodActivity", "Updating food recommendations with filters: " + activeFilters);
        
        // Toast message removed for performance
        
        new Thread(() -> {
            try {
                SmartFilterEngine filterEngine = new SmartFilterEngine(this);
                List<SmartFilterEngine.RankedDish> rankedRecommendations = 
                    filterEngine.getFilteredRecommendations(email, activeFilters);
                filterEngine.close();
                
                runOnUiThread(() -> {
                    android.util.Log.d("FoodActivity", "Got " + rankedRecommendations.size() + " filtered recommendations");
                    
                    // Set ranks for display
                    for (int i = 0; i < rankedRecommendations.size(); i++) {
                        rankedRecommendations.get(i).setRank(i + 1);
                    }
                    
                    // Clear current recommendations and add filtered ones
                    currentRecommendations.clear();
                    for (SmartFilterEngine.RankedDish rankedDish : rankedRecommendations) {
                        currentRecommendations.add(rankedDish.dish);
                    }
                    
                    // Update adapter
                    if (foodCardAdapter != null) {
                        foodCardAdapter.notifyDataSetChanged();
                    }
                    
                    // Show that recommendations have been updated
                    android.widget.Toast.makeText(FoodActivity.this, 
                        "🎯 Updated recommendations based on your filters!", 
                        android.widget.Toast.LENGTH_SHORT).show();
                    
                    // Show filter summary
                    if (!rankedRecommendations.isEmpty()) {
                        StringBuilder filterSummary = new StringBuilder();
                        filterSummary.append("🎯 Applied Filters: ");
                        for (String filter : activeFilters) {
                            filterSummary.append(filter).append(", ");
                        }
                        filterSummary.append("\n🥇 Top Recommendation: ").append(rankedRecommendations.get(0).dish.name);
                        filterSummary.append("\n📊 Score: ").append(rankedRecommendations.get(0).getScoreDisplay());
                        
                        // Toast message removed for performance
                    }
                });
                
            } catch (Exception e) {
                android.util.Log.e("FoodActivity", "Error getting filtered recommendations: " + e.getMessage());
                runOnUiThread(() -> {
                    // Toast message removed for performance
                    updateFoodRecommendations(); // Fallback to default
                });
            }
        }).start();
    }

    private void updateFoodRecommendations() {
        String email = getCurrentUserEmail();
        if (email == null) {
            android.util.Log.e("FoodActivity", "No current user email found");
            return;
        }
        
        android.util.Log.d("FoodActivity", "Updating food recommendations for user: " + email);
        
        // Check if user has set preferences
        if (!userHasDietPrefs()) {
            // Toast message removed for performance
        }
        
        NutritionRecommendationEngine engine = new NutritionRecommendationEngine(this);
        try {
            List<DishData.Dish> recommendations = engine.getPersonalizedRecommendations(email);
            
            android.util.Log.d("FoodActivity", "Got " + recommendations.size() + " recommendations");
            
            // Clear current recommendations and add new ones
            currentRecommendations.clear();
            currentRecommendations.addAll(recommendations);
            
            // Update adapter
            if (foodCardAdapter != null) {
                foodCardAdapter.notifyDataSetChanged();
            }
            
            // Show top recommendation explanation with AI learning message
            if (!recommendations.isEmpty()) {
                // Get risk score asynchronously
                ScreeningResultStore.getRiskScoreAsync(this, new ScreeningResultStore.OnRiskScoreReceivedListener() {
                    @Override
                    public void onRiskScoreReceived(int riskScore) {
                        String explanation = engine.getNutritionExplanation(recommendations.get(0), riskScore);
                        android.util.Log.d("FoodActivity", "Risk score: " + riskScore + ", Top recommendation: " + recommendations.get(0).name);
                        
                        // Add AI learning message
                        String aiMessage = "🤖 AI Learning: System adapts recommendations based on your screening data and preferences.";
                        // Toast message removed for performance
                            
                        // Save recommendation data for AI learning
                        saveRecommendationForLearning(email, recommendations, riskScore);
                    }
                });
            }
        } finally {
            engine.close();
        }
    }
    
    /**
     * Load AI-powered personalized recommendations
     */
    private void loadAIRecommendations() {
        String email = getCurrentUserEmail();
        if (email == null) {
            updateFoodRecommendations();
            return;
        }
        
        new Thread(() -> {
            try {
                AIFoodRecommendationEngine aiEngine = new AIFoodRecommendationEngine(this);
                List<DishData.Dish> aiRecommendations = aiEngine.getAIPersonalizedRecommendations(email);
                aiEngine.close();
                
                runOnUiThread(() -> {
                    android.util.Log.d("FoodActivity", "Got " + aiRecommendations.size() + " AI recommendations");
                    
                    // Clear current recommendations and add AI ones
                    currentRecommendations.clear();
                    currentRecommendations.addAll(aiRecommendations);
                    
                    // Update adapter
                    if (foodCardAdapter != null) {
                        foodCardAdapter.notifyDataSetChanged();
                    }
                    
                    // Show that AI recommendations have been loaded
                    android.widget.Toast.makeText(FoodActivity.this, 
                        "🧠 AI-powered recommendations loaded based on your screening data!", 
                        android.widget.Toast.LENGTH_SHORT).show();
                    
                    // Show AI recommendation summary
                    if (!aiRecommendations.isEmpty()) {
                        // Get risk score asynchronously
                        ScreeningResultStore.getRiskScoreAsync(FoodActivity.this, new ScreeningResultStore.OnRiskScoreReceivedListener() {
                            @Override
                            public void onRiskScoreReceived(int riskScore) {
                                String aiSummary = generateAISummary(aiRecommendations, riskScore);
                                // Toast message removed for performance
                                
                                // Save AI recommendations to database for future use
                                saveAIRecommendationsToDb(aiRecommendations);
                                
                                // Sync with web API
                                syncFoodRecommendationsToApi(email, aiRecommendations);
                            }
                        });
                    } else {
                        // Toast message removed for performance
                    }
                });
                
            } catch (Exception e) {
                android.util.Log.e("FoodActivity", "Error loading AI recommendations: " + e.getMessage());
                // Toast message removed for performance
                // Fallback to regular recommendations
                updateFoodRecommendations();
            }
        }).start();
    }
    
    /**
     * Generate AI summary message for user
     */
    private String generateAISummary(List<DishData.Dish> recommendations, int riskScore) {
        StringBuilder summary = new StringBuilder();
        summary.append("🧠 AI Analysis Complete! ");
        
        if (riskScore >= 70) {
            summary.append("High nutritional support needed. ");
        } else if (riskScore >= 40) {
            summary.append("Moderate nutritional support recommended. ");
        } else {
            summary.append("Maintaining healthy nutrition. ");
        }
        
        summary.append("Generated ").append(recommendations.size()).append(" personalized recommendations.");
        
        return summary.toString();
    }
    
    /**
     * Save AI recommendations to local database
     */
    private void saveAIRecommendationsToDb(List<DishData.Dish> recommendations) {
        String email = getCurrentUserEmail();
        if (email == null) return;
        
        // Clear previous recommendations
        dbHelper.getWritableDatabase().delete(UserPreferencesDbHelper.TABLE_FOOD_RECS, 
            UserPreferencesDbHelper.COL_FOOD_RECS_USER_EMAIL + "=?", new String[]{email});
        
        // Save new AI recommendations
        for (DishData.Dish dish : recommendations) {
            saveRecommendationToDb(dish);
        }
        
        android.util.Log.d("FoodActivity", "Saved " + recommendations.size() + " AI recommendations to database");
    }
    
    /**
     * Save recommendation data for AI learning and improvement
     */
    private void saveRecommendationForLearning(String email, List<DishData.Dish> recommendations, int riskScore) {
        new Thread(() -> {
            try {
                // Create learning data JSON
                org.json.JSONObject learningData = new org.json.JSONObject();
                learningData.put("email", email);
                learningData.put("risk_score", riskScore);
                learningData.put("timestamp", System.currentTimeMillis());
                
                // Add recommendation data for learning
                org.json.JSONArray recsArray = new org.json.JSONArray();
                for (DishData.Dish dish : recommendations) {
                    org.json.JSONObject dishData = new org.json.JSONObject();
                    dishData.put("name", dish.name);
                    dishData.put("tags", new org.json.JSONArray(dish.tags));
                    dishData.put("allergens", new org.json.JSONArray(dish.allergens));
                    recsArray.put(dishData);
                }
                learningData.put("recommendations", recsArray);
                
                // Send to API for AI learning
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                okhttp3.RequestBody body = okhttp3.RequestBody.create(
                    learningData.toString(), 
                    okhttp3.MediaType.parse("application/json")
                );
                okhttp3.Request request = new okhttp3.Request.Builder()
                    .url(Constants.UNIFIED_API_URL)
                    .post(body)
                    .build();
                
                try (okhttp3.Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        android.util.Log.d("FoodActivity", "AI learning data saved successfully");
                    } else {
                        android.util.Log.e("FoodActivity", "Failed to save AI learning data: " + response.code());
                    }
                }
                
            } catch (Exception e) {
                android.util.Log.e("FoodActivity", "Error saving AI learning data: " + e.getMessage());
            }
        }).start();
    }

    private void loadOrFetchRecommendations() {
        // Use AI-powered personalized nutrition recommendation engine
        String userEmail = getCurrentUserEmail();
        Log.d("FoodActivity", "Loading AI-powered recommendations for user: " + userEmail);
        
        if (userEmail != null) {
            // First, try to sync data from web to ensure we have the latest screening data
            new Thread(() -> {
                SyncDataActivity.syncUserDataFromWeb(FoodActivity.this, userEmail);
                
                runOnUiThread(() -> {
                    // Use AI engine for personalized recommendations based on screening data
                    loadAIRecommendations();
                });
            }).start();
        } else {
            Log.d("FoodActivity", "No user email found, falling back to default");
            // Fallback to default recommendations
            updateFoodRecommendations();
        }
    }

    private List<DishData.Dish> loadRecommendationsFromDb() {
        List<DishData.Dish> list = new ArrayList<>();
        String email = getCurrentUserEmail();
        if (email == null) return list;
        Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
            "SELECT " + UserPreferencesDbHelper.COL_FOOD_RECS_NAME + ", " + UserPreferencesDbHelper.COL_FOOD_RECS_EMOJI + ", " + UserPreferencesDbHelper.COL_FOOD_RECS_DESC + " FROM " + UserPreferencesDbHelper.TABLE_FOOD_RECS + " WHERE " + UserPreferencesDbHelper.COL_FOOD_RECS_USER_EMAIL + "=? ORDER BY " + UserPreferencesDbHelper.COL_FOOD_RECS_TIMESTAMP + " ASC", new String[]{email});
        while (cursor.moveToNext()) {
            String name = cursor.getString(0);
            String emoji = cursor.getString(1);
            String desc = cursor.getString(2);
            list.add(new DishData.Dish(name, emoji, desc, new ArrayList<>(), new ArrayList<>()));
        }
        cursor.close();
        return list;
    }

    private void saveRecommendationToDb(DishData.Dish dish) {
        ContentValues values = new ContentValues();
        values.put(UserPreferencesDbHelper.COL_FOOD_RECS_USER_EMAIL, getCurrentUserEmail());
        values.put(UserPreferencesDbHelper.COL_FOOD_RECS_NAME, dish.name);
        values.put(UserPreferencesDbHelper.COL_FOOD_RECS_EMOJI, dish.emoji);
        values.put(UserPreferencesDbHelper.COL_FOOD_RECS_DESC, dish.desc);
        values.put(UserPreferencesDbHelper.COL_FOOD_RECS_TIMESTAMP, System.currentTimeMillis());
        dbHelper.getWritableDatabase().insert(UserPreferencesDbHelper.TABLE_FOOD_RECS, null, values);
    }

    // Infinite scroll: load more foods as needed
    public void loadMoreFoods() {
        // No more foods, or not loaded yet
    }

    private void showSubstitutionPanel(DishData.Dish dish) {
        CardView bottomSheet = findViewById(R.id.substitution_bottom_sheet);
        TextView title = findViewById(R.id.substitution_title);
        LinearLayout options = findViewById(R.id.substitution_options_container);
        if (bottomSheet != null && title != null) {
            title.setText("Substitute for " + dish.name);
            if (options != null) {
                options.removeAllViews();
                // Build ranked substitutes
                List<DishData.Dish> substitutes = getRankedSubstitutes(dish);
                for (DishData.Dish sub : substitutes) {
                    options.addView(createSubstituteRow(options, dish, sub));
                }
            }
            bottomSheet.setVisibility(View.VISIBLE);
            bottomSheet.setAlpha(0f);
            bottomSheet.animate().alpha(1f).setDuration(300).start();
        }
    }

    @Override
    public void onBackPressed() {
        // Check if dish info panel is visible, close it first
        CardView dishInfoPanel = findViewById(  R.id.dish_info_bottom_sheet);
        if (dishInfoPanel != null && dishInfoPanel.getVisibility() == View.VISIBLE) {
            hideDishInfoPanel();
            return;
        }
        
        // Check if substitution panel is visible, close it first
        CardView substitutionPanel = findViewById(R.id.substitution_bottom_sheet);
        if (substitutionPanel != null && substitutionPanel.getVisibility() == View.VISIBLE) {
            hideSubstitutionPanel();
            return;
        }
        
        // Default back behavior
        super.onBackPressed();
    }

    @Override
    public void onInfoClick(DishData.Dish dish) {
        showDishInfoPanel(dish);
    }
    
    /**
     * Show AI reasoning for a specific dish recommendation
     */
    private void showAIReasoning(DishData.Dish dish) {
        String email = getCurrentUserEmail();
        if (email == null) return;
        
        // Get current screening data to show reasoning
        android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
            "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
            new String[]{email}
        );
        
        if (cursor.moveToFirst()) {
            int riskScore = cursor.getInt(cursor.getColumnIndex(UserPreferencesDbHelper.COL_RISK_SCORE));
            String screeningAnswers = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_SCREENING_ANSWERS));
            
            StringBuilder reasoning = new StringBuilder();
            reasoning.append("🤖 AI Reasoning for ").append(dish.name).append(":\n\n");
            reasoning.append("Your Risk Score: ").append(riskScore).append("%\n\n");
            
            if (screeningAnswers != null && !screeningAnswers.isEmpty()) {
                try {
                    org.json.JSONObject screening = new org.json.JSONObject(screeningAnswers);
                    reasoning.append("Based on your screening:\n");
                    
                    // Show key factors that influenced this recommendation
                    if (screening.has("swelling") && "yes".equals(screening.getString("swelling"))) {
                        reasoning.append("• Edema detected → High priority for therapeutic foods\n");
                    }
                    if (screening.has("weight_loss")) {
                        String weightLoss = screening.getString("weight_loss");
                        if (">10%".equals(weightLoss)) {
                            reasoning.append("• Severe weight loss → High-calorie, protein-rich foods\n");
                        } else if ("5-10%".equals(weightLoss)) {
                            reasoning.append("• Moderate weight loss → Energy-dense foods\n");
                        }
                    }
                    if (screening.has("feeding_behavior")) {
                        String feeding = screening.getString("feeding_behavior");
                        if ("poor appetite".equals(feeding)) {
                            reasoning.append("• Poor appetite → Appetite-stimulating foods\n");
                        }
                    }
                    if (screening.has("physical_signs")) {
                        String signs = screening.getString("physical_signs");
                        if (signs.contains("thin")) {
                            reasoning.append("• Thin appearance → Weight gain support foods\n");
                        }
                        if (signs.contains("weak")) {
                            reasoning.append("• Weakness → Energy-boosting foods\n");
                        }
                    }
                } catch (Exception e) {
                    reasoning.append("Error parsing screening data: ").append(e.getMessage());
                }
            }
            
            reasoning.append("\nThis dish was selected because it matches your nutritional needs based on the screening assessment.");
            
            new AlertDialog.Builder(this)
                .setTitle("AI Reasoning")
                .setMessage(reasoning.toString())
                .setPositiveButton("OK", null)
                .show();
        }
        cursor.close();
    }

    private void showDishInfoPanel(DishData.Dish dish) {
        CardView dishInfoPanel = findViewById(R.id.dish_info_bottom_sheet);
        if (dishInfoPanel != null) {
            // Get detailed dish information
            DishInfo.DetailedDish detailedDish = DishInfo.getDishInfo(dish.name);
            
            if (detailedDish != null) {
                // Update the dish info card with detailed information
                updateDishInfoCard(detailedDish);
            } else {
                // Fallback to basic dish information
                updateDishInfoCardBasic(dish);
            }
            
            // Show the panel with animation
            dishInfoPanel.setVisibility(View.VISIBLE);
            dishInfoPanel.setAlpha(0f);
            dishInfoPanel.animate().alpha(1f).setDuration(300).start();
            
            // Setup close button
            View closeBtn = dishInfoPanel.findViewById(R.id.close_dish_info);
            if (closeBtn != null) {
                closeBtn.setOnClickListener(v -> hideDishInfoPanel());
            }
        }
    }

    private void hideDishInfoPanel() {
        CardView dishInfoPanel = findViewById(R.id.dish_info_bottom_sheet);
        if (dishInfoPanel != null) {
            dishInfoPanel.animate().alpha(0f).setDuration(300).withEndAction(() -> dishInfoPanel.setVisibility(View.GONE)).start();
        }
    }

    private void updateDishInfoCard(DishInfo.DetailedDish detailedDish) {
        CardView dishInfoPanel = findViewById(R.id.dish_info_bottom_sheet);
        if (dishInfoPanel == null) return;
        
        // Update image
        ImageView imageView = dishInfoPanel.findViewById(R.id.dish_info_image);
        if (imageView != null) {
            int imageResourceId = FoodImageHelper.getImageResourceId(this, detailedDish.name);
            imageView.setImageResource(imageResourceId);
        }
        
        // Update emoji and name
        TextView emojiView = dishInfoPanel.findViewById(R.id.dish_info_emoji);
        if (emojiView != null) emojiView.setText(detailedDish.emoji);
        
        TextView nameView = dishInfoPanel.findViewById(R.id.dish_info_name);
        if (nameView != null) nameView.setText(detailedDish.name);
        
        // Note: Other detailed fields are not yet implemented in the new layout
        // They can be added later when needed
    }

    private void updateDishInfoCardBasic(DishData.Dish dish) {
        CardView dishInfoPanel = findViewById(R.id.dish_info_bottom_sheet);
        if (dishInfoPanel == null) return;
        
        // Update image
        ImageView imageView = dishInfoPanel.findViewById(R.id.dish_info_image);
        if (imageView != null) {
            int imageResourceId = FoodImageHelper.getImageResourceId(this, dish.name);
            imageView.setImageResource(imageResourceId);
        }
        
        // Update emoji and name
        TextView emojiView = dishInfoPanel.findViewById(R.id.dish_info_emoji);
        if (emojiView != null) emojiView.setText("🍽️");
        
        TextView nameView = dishInfoPanel.findViewById(R.id.dish_info_name);
        if (nameView != null) nameView.setText(dish.name);
        
        // Note: Other detailed fields are not yet implemented in the new layout
        // They can be added later when needed
    }

    private void hideSubstitutionPanel() {
        CardView bottomSheet = findViewById(R.id.substitution_bottom_sheet);
        if (bottomSheet != null) {
            bottomSheet.animate().alpha(0f).setDuration(300).withEndAction(() -> bottomSheet.setVisibility(View.GONE)).start();
        }
    }

    private List<DishData.Dish> getRankedSubstitutes(DishData.Dish original) {
        // Similar dishes: share at least one tag and same age group tag
        // Rank by: past substitution choices (by reason/tag), then tag overlap count
        String email = getCurrentUserEmail();
        Map<String, Integer> reasonCounts = getUserSubstitutionReasonCounts(email);

        // Detect age group tag from original
        String ageGroupTag = detectAgeGroupTag(original);

        List<DishData.Dish> candidates = new ArrayList<>();
        for (DishData.Dish d : DishData.DISHES) {
            if (d.name.equals(original.name)) continue;
            if (ageGroupTag != null && !d.tags.contains(ageGroupTag)) continue; // keep age group
            // must share at least one tag besides age group
            boolean share = false;
            for (String t : original.tags) {
                if (t.equals(ageGroupTag)) continue;
                if (d.tags.contains(t)) { share = true; break; }
            }
            if (share) candidates.add(d);
        }

        candidates.sort((a, b) -> {
            // Compute preferred reason weight
            int aReason = topReasonWeight(a.tags, reasonCounts);
            int bReason = topReasonWeight(b.tags, reasonCounts);
            if (aReason != bReason) return Integer.compare(bReason, aReason);
            // Fallback: overlap count with original (excluding age tag)
            int aOverlap = overlapCount(original, a, ageGroupTag);
            int bOverlap = overlapCount(original, b, ageGroupTag);
            return Integer.compare(bOverlap, aOverlap);
        });

        // Limit to top 6
        if (candidates.size() > 6) return candidates.subList(0, 6);
        return candidates;
    }

    private int overlapCount(DishData.Dish base, DishData.Dish other, String ageGroupTag) {
        int count = 0;
        for (String t : base.tags) {
            if (t.equals(ageGroupTag)) continue;
            if (other.tags.contains(t)) count++;
        }
        return count;
    }

    private String detectAgeGroupTag(DishData.Dish d) {
        String[] groups = {"Infant-Early","Infant","Toddler-Early","Toddler","Preschool","School-age","Adolescent"};
        for (String g : groups) if (d.tags.contains(g)) return g;
        return null;
    }

    private int topReasonWeight(List<String> tags, Map<String, Integer> reasonCounts) {
        int best = 0;
        for (String t : tags) {
            Integer c = reasonCounts.get(t);
            if (c != null && c > best) best = c;
        }
        return best;
    }

    private Map<String, Integer> getUserSubstitutionReasonCounts(String email) {
        Map<String, Integer> map = new HashMap<>();
        if (email == null) return map;
        Cursor c = dbHelper.getReadableDatabase().query(
            UserPreferencesDbHelper.TABLE_SUBSTITUTIONS,
            new String[]{UserPreferencesDbHelper.COL_SUB_REASON_TAG},
            UserPreferencesDbHelper.COL_SUB_USER_EMAIL + "=?",
            new String[]{email}, null, null, null
        );
        while (c.moveToNext()) {
            String tag = c.getString(0);
            if (tag != null && !tag.isEmpty()) {
                map.put(tag, map.getOrDefault(tag, 0) + 1);
            }
        }
        c.close();
        return map;
    }

    private View createSubstituteRow(ViewGroup parent, DishData.Dish original, DishData.Dish substitute) {
        LinearLayout row = new LinearLayout(parent.getContext());
        row.setOrientation(LinearLayout.HORIZONTAL);
        row.setPadding(dp(12), dp(12), dp(12), dp(12));
        row.setBackgroundResource(android.R.drawable.list_selector_background);
        row.setLayoutParams(new LinearLayout.LayoutParams(ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.WRAP_CONTENT));
        row.setGravity(android.view.Gravity.CENTER_VERTICAL);

        TextView emoji = new TextView(parent.getContext());
        emoji.setText(substitute.emoji);
        emoji.setTextSize(32);
        LinearLayout.LayoutParams emojiLp = new LinearLayout.LayoutParams(dp(48), dp(48));
        emoji.setLayoutParams(emojiLp);
        row.addView(emoji);

        LinearLayout col = new LinearLayout(parent.getContext());
        col.setOrientation(LinearLayout.VERTICAL);
        LinearLayout.LayoutParams colLp = new LinearLayout.LayoutParams(0, ViewGroup.LayoutParams.WRAP_CONTENT, 1f);
        colLp.leftMargin = dp(16);
        col.setLayoutParams(colLp);

        TextView name = new TextView(parent.getContext());
        name.setText(substitute.name);
        name.setTextSize(16);
        name.setTypeface(android.graphics.Typeface.DEFAULT_BOLD);
        name.setTextColor(0xFF222222);
        col.addView(name);

        TextView desc = new TextView(parent.getContext());
        desc.setText("Similar option");
        desc.setTextSize(13);
        desc.setTextColor(0xFF888888);
        col.addView(desc);
        
        row.addView(col);

        // Click to apply substitution
        row.setOnClickListener(v -> {
            applySubstitution(original, substitute);
            hideSubstitutionPanel();
        });

        return row;
    }

    private int dp(int v) {
        float d = getResources().getDisplayMetrics().density;
        return Math.round(v * d);
    }

    private void applySubstitution(DishData.Dish original, DishData.Dish substitute) {
        // Replace in currentRecommendations
        for (int i = 0; i < currentRecommendations.size(); i++) {
            if (currentRecommendations.get(i).name.equals(original.name)) {
                currentRecommendations.set(i, substitute);
                foodCardAdapter.notifyItemChanged(i);
                break;
            }
        }
        // Persist choice locally
        saveSubstitutionChoice(original, substitute);
        // Send to API for learning
        sendSubstitutionToApi(original, substitute);
    }

    private void saveSubstitutionChoice(DishData.Dish original, DishData.Dish substitute) {
        String email = getCurrentUserEmail();
        if (email == null) return;
        String reason = firstSharedNonAgeTag(original, substitute);
        android.content.ContentValues v = new android.content.ContentValues();
        v.put(UserPreferencesDbHelper.COL_SUB_USER_EMAIL, email);
        v.put(UserPreferencesDbHelper.COL_SUB_ORIGINAL_NAME, original.name);
        v.put(UserPreferencesDbHelper.COL_SUB_CHOSEN_NAME, substitute.name);
        v.put(UserPreferencesDbHelper.COL_SUB_CHOSEN_EMOJI, substitute.emoji);
        v.put(UserPreferencesDbHelper.COL_SUB_CHOSEN_DESC, substitute.desc);
        v.put(UserPreferencesDbHelper.COL_SUB_CHOSEN_TAGS, substitute.tags.toString());
        v.put(UserPreferencesDbHelper.COL_SUB_REASON_TAG, reason);
        v.put(UserPreferencesDbHelper.COL_SUB_TIMESTAMP, System.currentTimeMillis());
        dbHelper.getWritableDatabase().insert(UserPreferencesDbHelper.TABLE_SUBSTITUTIONS, null, v);
    }

    private String firstSharedNonAgeTag(DishData.Dish a, DishData.Dish b) {
        String age = detectAgeGroupTag(a);
        for (String t : a.tags) {
            if (age != null && t.equals(age)) continue;
            if (b.tags.contains(t)) return t;
        }
        return "";
    }

    private void sendSubstitutionToApi(DishData.Dish original, DishData.Dish substitute) {
        new Thread(() -> {
            try {
                org.json.JSONObject json = new org.json.JSONObject();
                json.put("action", "save_substitution");
                json.put("email", getCurrentUserEmail());
                json.put("original_name", original.name);
                json.put("chosen_name", substitute.name);
                json.put("chosen_emoji", substitute.emoji);
                json.put("chosen_desc", substitute.desc);
                json.put("chosen_tags", new org.json.JSONArray(substitute.tags));
                json.put("reason_tag", firstSharedNonAgeTag(original, substitute));
                json.put("timestamp", System.currentTimeMillis());

                okhttp3.RequestBody body = okhttp3.RequestBody.create(
                    json.toString(), okhttp3.MediaType.parse("application/json"));
                okhttp3.Request req = new okhttp3.Request.Builder()
                    .url(Constants.UNIFIED_API_URL)
                    .post(body)
                    .build();
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                try (okhttp3.Response resp = client.newCall(req).execute()) {
                    if (!resp.isSuccessful()) {
                        android.util.Log.e("FoodActivity", "Failed to save substitution: " + resp.code());
                    }
                }
            } catch (Exception e) {
                android.util.Log.e("FoodActivity", "Error sending substitution: " + e.getMessage());
            }
        }).start();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (dbHelper != null) {
            dbHelper.close();
        }
    }
} 