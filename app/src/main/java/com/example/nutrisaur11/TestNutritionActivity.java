package com.example.nutrisaur11;

import android.app.Activity;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.widget.TextView;

public class TestNutritionActivity extends Activity {
    private static final String TAG = "TestNutritionActivity";
    private TextView resultText;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // Create a simple layout
        resultText = new TextView(this);
        resultText.setText("Testing Nutrition Service...");
        setContentView(resultText);
        
        // Test the nutrition service
        testNutritionService();
    }
    
    private void testNutritionService() {
        Log.d(TAG, "Starting nutrition service test...");
        
        // First, let's check if we have user data
        SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        String userName = prefs.getString("user_name", "");
        int userAge = prefs.getInt("user_age", 0);
        String userWeight = prefs.getString("user_weight", "");
        String userHeight = prefs.getString("user_height", "");
        
        Log.d(TAG, "User data check:");
        Log.d(TAG, "Name: " + userName);
        Log.d(TAG, "Age: " + userAge);
        Log.d(TAG, "Weight: " + userWeight);
        Log.d(TAG, "Height: " + userHeight);
        
        // If no user data, create some test data
        if (userName.isEmpty() || userAge == 0 || userWeight.isEmpty() || userHeight.isEmpty()) {
            Log.d(TAG, "No user data found, creating test data...");
            SharedPreferences.Editor editor = prefs.edit();
            editor.putString("user_id", "test_user");
            editor.putString("user_name", "Test User");
            editor.putInt("user_age", 25);
            editor.putString("user_gender", "Male");
            editor.putString("user_weight", "70");
            editor.putString("user_height", "175");
            editor.putString("activity_level", "Moderately Active");
            editor.putString("health_goals", "Maintain weight");
            editor.putString("dietary_preferences", "None");
            editor.putString("allergies", "None");
            editor.putString("medical_conditions", "None");
            editor.putBoolean("is_pregnant", false);
            editor.putInt("pregnancy_week", 0);
            editor.putString("occupation", "Office worker");
            editor.putString("lifestyle", "Moderate");
            editor.putBoolean("personalization_completed", true);
            editor.apply();
            
            resultText.setText("Test data created. Testing nutrition service...");
        }
        
        // Now test the nutrition service
        NutritionService nutritionService = new NutritionService(this);
        nutritionService.getNutritionRecommendations(new NutritionService.NutritionCallback() {
            @Override
            public void onSuccess(NutritionData nutritionData) {
                Log.d(TAG, "Nutrition service SUCCESS!");
                Log.d(TAG, "Total Calories: " + nutritionData.getTotalCalories());
                Log.d(TAG, "Calories Left: " + nutritionData.getCaloriesLeft());
                
                runOnUiThread(() -> {
                    resultText.setText("SUCCESS!\n" +
                        "Total Calories: " + nutritionData.getTotalCalories() + "\n" +
                        "Calories Left: " + nutritionData.getCaloriesLeft() + "\n" +
                        "BMI: " + nutritionData.getBmi() + "\n" +
                        "Category: " + nutritionData.getBmiCategory());
                });
            }
            
            @Override
            public void onError(String error) {
                Log.e(TAG, "Nutrition service ERROR: " + error);
                runOnUiThread(() -> {
                    resultText.setText("ERROR: " + error);
                });
            }
        });
    }
}
