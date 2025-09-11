package com.example.nutrisaur11;

import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Log;
import android.widget.Button;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import java.util.List;

public class TestFatSecretActivity extends AppCompatActivity {
    private static final String TAG = "TestFatSecretActivity";
    
    private TextView resultsText;
    private Button testBreakfastButton, testLunchButton, testDinnerButton, testSnacksButton;
    private FatSecretService fatSecretService;
    private Handler mainHandler;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_test_fatsecret);
        
        // Initialize services
        fatSecretService = new FatSecretService();
        mainHandler = new Handler(Looper.getMainLooper());
        
        // Initialize views
        resultsText = findViewById(R.id.results_text);
        testBreakfastButton = findViewById(R.id.test_breakfast_button);
        testLunchButton = findViewById(R.id.test_lunch_button);
        testDinnerButton = findViewById(R.id.test_dinner_button);
        testSnacksButton = findViewById(R.id.test_snacks_button);
        
        // Setup click listeners
        setupClickListeners();
        
        Log.d(TAG, "TestFatSecretActivity created");
    }
    
    private void setupClickListeners() {
        testBreakfastButton.setOnClickListener(v -> testMealCategory("Breakfast", 375));
        testLunchButton.setOnClickListener(v -> testMealCategory("Lunch", 525));
        testDinnerButton.setOnClickListener(v -> testMealCategory("Dinner", 450));
        testSnacksButton.setOnClickListener(v -> testMealCategory("Snacks", 150));
    }
    
    private void testMealCategory(String category, int maxCalories) {
        Log.d(TAG, "Testing " + category + " with max " + maxCalories + " calories");
        
        resultsText.setText("Testing " + category + "...\n");
        
        fatSecretService.searchFoods(category, maxCalories, new FatSecretService.FoodSearchCallback() {
            @Override
            public void onSuccess(List<FoodItem> foods) {
                mainHandler.post(() -> {
                    StringBuilder result = new StringBuilder();
                    result.append("✅ ").append(category).append(" Test Results:\n");
                    result.append("Max Calories: ").append(maxCalories).append("\n");
                    result.append("Foods Found: ").append(foods.size()).append("\n\n");
                    
                    for (FoodItem food : foods) {
                        result.append("• ").append(food.getName())
                              .append(" - ").append(food.getCalories()).append(" kcal")
                              .append(" (").append(food.getWeight()).append(" ").append(food.getUnit()).append(")\n");
                    }
                    
                    // Check if all foods are within calorie limit
                    boolean allWithinLimit = true;
                    for (FoodItem food : foods) {
                        if (food.getCalories() > maxCalories) {
                            allWithinLimit = false;
                            break;
                        }
                    }
                    
                    result.append("\n").append(allWithinLimit ? "✅ All foods within calorie limit!" : "❌ Some foods exceed calorie limit!");
                    
                    resultsText.setText(result.toString());
                    Log.d(TAG, "Test completed for " + category + ": " + foods.size() + " foods found");
                });
            }
            
            @Override
            public void onError(String error) {
                mainHandler.post(() -> {
                    resultsText.setText("❌ Error testing " + category + ": " + error);
                    Log.e(TAG, "Error testing " + category + ": " + error);
                });
            }
        });
    }
}
