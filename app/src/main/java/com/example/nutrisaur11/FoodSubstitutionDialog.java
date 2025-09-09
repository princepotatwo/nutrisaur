package com.example.nutrisaur11;

import android.app.Dialog;
import android.content.Context;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import java.util.List;

public class FoodSubstitutionDialog extends Dialog {
    private static final String TAG = "FoodSubstitutionDialog";
    
    private FoodRecommendation originalFood;
    private List<FoodRecommendation> substitutions;
    private String substitutionReason;
    private String userAge, userSex, userBMI, userHealthConditions, userBudgetLevel;
    private String userAllergies, userDietPrefs, userPregnancyStatus;
    
    private FoodSubstitutionManager substitutionManager;
    private FoodSubstitutionAdapter substitutionAdapter;
    private OnSubstitutionSelectedListener listener;
    
    // UI Components
    private ImageView originalFoodImage;
    private TextView originalFoodName, originalFoodNutrition, substitutionReasonText;
    private TextView substitutionCount;
    private RecyclerView substitutionsRecycler;
    private Button refreshSubstitutionsButton;
    
    public interface OnSubstitutionSelectedListener {
        void onSubstitutionSelected(FoodRecommendation substitution);
        void onRefreshSubstitutions();
    }
    
    public FoodSubstitutionDialog(@NonNull Context context, FoodRecommendation originalFood, 
                                List<FoodRecommendation> substitutions, String substitutionReason,
                                String userAge, String userSex, String userBMI, String userHealthConditions,
                                String userBudgetLevel, String userAllergies, String userDietPrefs,
                                String userPregnancyStatus, OnSubstitutionSelectedListener listener) {
        super(context);
        this.originalFood = originalFood;
        this.substitutions = substitutions;
        this.substitutionReason = substitutionReason;
        this.userAge = userAge;
        this.userSex = userSex;
        this.userBMI = userBMI;
        this.userHealthConditions = userHealthConditions;
        this.userBudgetLevel = userBudgetLevel;
        this.userAllergies = userAllergies;
        this.userDietPrefs = userDietPrefs;
        this.userPregnancyStatus = userPregnancyStatus;
        this.listener = listener;
        this.substitutionManager = new FoodSubstitutionManager(context);
    }
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.dialog_food_substitution);
        
        Log.d(TAG, "Creating substitution dialog for: " + originalFood.getFoodName());
        
        initializeViews();
        setupOriginalFoodInfo();
        setupSubstitutions();
        setupClickListeners();
    }
    
    private void initializeViews() {
        originalFoodImage = findViewById(R.id.original_food_image);
        originalFoodName = findViewById(R.id.original_food_name);
        originalFoodNutrition = findViewById(R.id.original_food_nutrition);
        substitutionReasonText = findViewById(R.id.substitution_reason_text);
        substitutionCount = findViewById(R.id.substitution_count);
        substitutionsRecycler = findViewById(R.id.substitutions_recycler);
        refreshSubstitutionsButton = findViewById(R.id.refresh_substitutions_button);
        
        // Close button
        findViewById(R.id.close_button).setOnClickListener(v -> dismiss());
    }
    
    private void setupOriginalFoodInfo() {
        // Set original food name
        originalFoodName.setText(originalFood.getFoodName());
        
        // Set nutrition info
        String nutritionText = String.format("Calories: %d kcal | Protein: %.1fg | Fat: %.1fg | Carbs: %.1fg",
            originalFood.getCalories(), originalFood.getProtein(), 
            originalFood.getFat(), originalFood.getCarbs());
        originalFoodNutrition.setText(nutritionText);
        
        // Set substitution reason
        if (substitutionReason != null && !substitutionReason.isEmpty()) {
            substitutionReasonText.setText(substitutionReason);
        } else {
            substitutionReasonText.setText("Here are some great alternatives");
        }
        
        // Load original food image
        FoodImageService foodImageService = new FoodImageService();
        foodImageService.loadFoodImage(originalFood.getFoodName(), originalFoodImage, null);
    }
    
    private void setupSubstitutions() {
        // Set substitution count
        substitutionCount.setText("Finding alternatives...");
        
        // Setup RecyclerView with loading state
        substitutionAdapter = new FoodSubstitutionAdapter(
            substitutions, getContext(), 
            new FoodSubstitutionAdapter.OnSubstitutionClickListener() {
                @Override
                public void onSubstitutionClick(FoodRecommendation substitution) {
                    Log.d(TAG, "Substitution selected: " + substitution.getFoodName());
                    if (listener != null) {
                        listener.onSubstitutionSelected(substitution);
                    }
                    dismiss();
                }
            }, 
            substitutionReason
        );
        
        // Show loading state immediately
        substitutionAdapter.setLoading(true);
        
        substitutionsRecycler.setLayoutManager(new LinearLayoutManager(getContext(), LinearLayoutManager.HORIZONTAL, false));
        substitutionsRecycler.setAdapter(substitutionAdapter);
        
        Log.d(TAG, "Setup " + substitutions.size() + " substitutions");
    }
    
    private void setupClickListeners() {
        refreshSubstitutionsButton.setOnClickListener(v -> {
            Log.d(TAG, "Refresh substitutions requested");
            
            // Show loading state immediately
            substitutionAdapter.setLoading(true);
            substitutionCount.setText("Finding alternatives...");
            refreshSubstitutionsButton.setText("Loading...");
            refreshSubstitutionsButton.setEnabled(false);
            
            if (listener != null) {
                listener.onRefreshSubstitutions();
            }
            // Don't dismiss - let the parent handle refresh
        });
    }
    
    public void updateSubstitutions(List<FoodRecommendation> newSubstitutions, String reason) {
        this.substitutions = newSubstitutions;
        this.substitutionReason = reason;
        
        if (substitutionAdapter != null) {
            substitutionAdapter.updateSubstitutions(newSubstitutions, reason);
            substitutionCount.setText(newSubstitutions.size() + " options");
            substitutionReasonText.setText(reason);
            
            // Turn off loading state and re-enable button
            refreshSubstitutionsButton.setText("Find Other Options");
            refreshSubstitutionsButton.setEnabled(true);
        }
        
        Log.d(TAG, "Updated substitutions: " + newSubstitutions.size() + " items");
    }
    
    @Override
    public void dismiss() {
        if (substitutionManager != null) {
            substitutionManager.shutdown();
        }
        super.dismiss();
    }
}
