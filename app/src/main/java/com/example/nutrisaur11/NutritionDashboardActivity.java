package com.example.nutrisaur11;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import java.util.Map;

/**
 * Nutrition Dashboard Activity
 * Displays suggested daily nutrition targets based on user screening data
 */
public class NutritionDashboardActivity extends AppCompatActivity {
    private static final String TAG = "NutritionDashboardActivity";
    
    // UI Components
    private TextView tvDailyCalories;
    private TextView tvProteinTarget;
    private TextView tvCarbsTarget;
    private TextView tvFatTarget;
    private TextView tvFiberTarget;
    private TextView tvSodiumTarget;
    private TextView tvSugarTarget;
    private TextView tvBreakfastCalories;
    private TextView tvLunchCalories;
    private TextView tvDinnerCalories;
    private TextView tvSnacksCalories;
    
    // Nutrition dashboard
    private DailyNutritionDashboard nutritionDashboard;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_nutrition_dashboard);
        
        Log.d(TAG, "Creating Nutrition Dashboard Activity");
        
        // Initialize UI components
        initializeViews();
        
        // Get user screening data from SharedPreferences
        SharedPreferences prefs = getSharedPreferences("user_screening", MODE_PRIVATE);
        String userAge = prefs.getString("age", "25");
        String userSex = prefs.getString("sex", "Not specified");
        String userBMI = prefs.getString("bmi", "22.5");
        String userHealthConditions = prefs.getString("health_conditions", "None");
        String userPregnancyStatus = prefs.getString("pregnancy_status", "Not Applicable");
        
        Log.d(TAG, "User screening data - Age: " + userAge + ", Sex: " + userSex + 
              ", BMI: " + userBMI + ", Health: " + userHealthConditions + 
              ", Pregnancy: " + userPregnancyStatus);
        
        // Create nutrition dashboard with user data
        nutritionDashboard = new DailyNutritionDashboard(
            userAge, userSex, userBMI, userHealthConditions, userPregnancyStatus
        );
        
        // Update UI with calculated nutrition targets
        updateNutritionDisplay();
    }
    
    /**
     * Initialize UI components
     */
    private void initializeViews() {
        tvDailyCalories = findViewById(R.id.tv_daily_calories);
        tvProteinTarget = findViewById(R.id.tv_protein_target);
        tvCarbsTarget = findViewById(R.id.tv_carbs_target);
        tvFatTarget = findViewById(R.id.tv_fat_target);
        tvFiberTarget = findViewById(R.id.tv_fiber_target);
        tvSodiumTarget = findViewById(R.id.tv_sodium_target);
        tvSugarTarget = findViewById(R.id.tv_sugar_target);
        tvBreakfastCalories = findViewById(R.id.tv_breakfast_calories);
        tvLunchCalories = findViewById(R.id.tv_lunch_calories);
        tvDinnerCalories = findViewById(R.id.tv_dinner_calories);
        tvSnacksCalories = findViewById(R.id.tv_snacks_calories);
    }
    
    /**
     * Update the nutrition display with calculated targets
     */
    private void updateNutritionDisplay() {
        if (nutritionDashboard == null) {
            Log.e(TAG, "Nutrition dashboard is null");
            return;
        }
        
        try {
            // Update daily calorie target
            tvDailyCalories.setText(String.format("%,d kcal", nutritionDashboard.getSuggestedCalories()));
            
            // Update macronutrient targets
            tvProteinTarget.setText(nutritionDashboard.getSuggestedProtein() + "g");
            tvCarbsTarget.setText(nutritionDashboard.getSuggestedCarbs() + "g");
            tvFatTarget.setText(nutritionDashboard.getSuggestedFat() + "g");
            tvFiberTarget.setText(nutritionDashboard.getSuggestedFiber() + "g");
            
            // Update micronutrient targets
            tvSodiumTarget.setText(String.format("%,dmg", nutritionDashboard.getSuggestedSodium()));
            tvSugarTarget.setText(nutritionDashboard.getSuggestedSugar() + "g");
            
            // Update meal distribution
            Map<String, Integer> mealDistribution = nutritionDashboard.getMealDistribution();
            tvBreakfastCalories.setText(mealDistribution.get("breakfast") + " kcal");
            tvLunchCalories.setText(mealDistribution.get("lunch") + " kcal");
            tvDinnerCalories.setText(mealDistribution.get("dinner") + " kcal");
            tvSnacksCalories.setText(mealDistribution.get("snacks") + " kcal");
            
            Log.d(TAG, "Nutrition display updated successfully");
            
        } catch (Exception e) {
            Log.e(TAG, "Error updating nutrition display: " + e.getMessage());
        }
    }
    
    /**
     * Get nutrition dashboard instance for external access
     */
    public DailyNutritionDashboard getNutritionDashboard() {
        return nutritionDashboard;
    }
    
    /**
     * Get nutrition targets as a formatted string for sharing
     */
    public String getNutritionSummary() {
        if (nutritionDashboard == null) {
            return "Nutrition data not available";
        }
        
        StringBuilder summary = new StringBuilder();
        summary.append("Daily Nutrition Targets:\n\n");
        summary.append("Calories: ").append(String.format("%,d kcal", nutritionDashboard.getSuggestedCalories())).append("\n");
        summary.append("Protein: ").append(nutritionDashboard.getSuggestedProtein()).append("g\n");
        summary.append("Carbohydrates: ").append(nutritionDashboard.getSuggestedCarbs()).append("g\n");
        summary.append("Fat: ").append(nutritionDashboard.getSuggestedFat()).append("g\n");
        summary.append("Fiber: ").append(nutritionDashboard.getSuggestedFiber()).append("g\n");
        summary.append("Sodium (Max): ").append(String.format("%,dmg", nutritionDashboard.getSuggestedSodium())).append("\n");
        summary.append("Sugar (Max): ").append(nutritionDashboard.getSuggestedSugar()).append("g\n\n");
        
        Map<String, Integer> mealDistribution = nutritionDashboard.getMealDistribution();
        summary.append("Meal Distribution:\n");
        summary.append("Breakfast: ").append(mealDistribution.get("breakfast")).append(" kcal (25%)\n");
        summary.append("Lunch: ").append(mealDistribution.get("lunch")).append(" kcal (35%)\n");
        summary.append("Dinner: ").append(mealDistribution.get("dinner")).append(" kcal (30%)\n");
        summary.append("Snacks: ").append(mealDistribution.get("snacks")).append(" kcal (10%)\n");
        
        return summary.toString();
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        Log.d(TAG, "Nutrition Dashboard Activity resumed");
    }
    
    @Override
    protected void onPause() {
        super.onPause();
        Log.d(TAG, "Nutrition Dashboard Activity paused");
    }
}
