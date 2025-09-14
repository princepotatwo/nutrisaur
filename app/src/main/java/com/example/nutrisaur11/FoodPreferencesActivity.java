package com.example.nutrisaur11;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class FoodPreferencesActivity extends AppCompatActivity {
    
    private int currentQuestion = 0;
    private Map<String, String> answers = new HashMap<>();
    private LinearLayout optionsContainer;
    private TextView questionText;
    private TextView progressText;
    private Button nextButton;
    private Button previousButton;
    private Button skipButton;
    private String userEmail;
    
    // Food preference questions
    private String[] questions = {
        "What is your main food identity?",
        "Do you have any food allergies or intolerances?",
        "What are your current food cravings?",
        "What cooking methods do you prefer?",
        "What is your daily food budget range?"
    };
    
    private int totalQuestions = 5;
    private int currentQuestionIndex = 0;
    
    // Question options
    private String[][] questionOptions = {
        // Question 1: Main Food Identity
        {"HALAL", "KOSHER", "VEGETARIAN", "VEGAN", "PESCATARIAN", "STANDARD EATER"},
        
        // Question 2: Food Allergies (Multiple choice)
        {"PEANUTS", "TREE NUTS", "MILK", "EGGS", "FISH", "SHELLFISH", "SOY", "WHEAT / GLUTEN"},
        
        // Question 3: Food Cravings (Multiple choice)
        {"SWEET", "SALTY", "SPICY", "SOUR", "UMAMI", "CRUNCHY", "CREAMY"},
        
        // Question 4: Cooking Methods (Multiple choice)
        {"GRILLED", "STEAMED", "FRIED", "BAKED", "RAW", "STEWED", "STIR-FRIED"},
        
        // Question 5: Budget Range (PHP)
        {"₱50-100 per day", "₱100-200 per day", "₱200-300 per day", "₱300-500 per day", "₱500+ per day"}
    };
    
    // Which questions allow multiple selection
    private boolean[] multipleChoiceQuestions = {false, true, true, true, false};
    
    // Selected options for multiple choice questions
    private Map<Integer, List<String>> multipleSelections = new HashMap<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_food_preferences);
        
        // Get user email from SharedPreferences
        SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        userEmail = prefs.getString("current_user_email", "");
        
        if (userEmail.isEmpty()) {
            // Fallback to legacy UserPreferences
            prefs = getSharedPreferences("UserPreferences", MODE_PRIVATE);
            userEmail = prefs.getString("user_email", "");
        }
        
        if (userEmail.isEmpty()) {
            Log.e("FoodPreferencesActivity", "No user email found in either SharedPreferences");
            Toast.makeText(this, "Please log in to update preferences", Toast.LENGTH_SHORT).show();
            finish();
            return;
        }
        
        Log.d("FoodPreferencesActivity", "Found user email: " + userEmail);

        // Initialize views
        initializeViews();
        setupClickListeners();
        
        // Load existing answers
        loadExistingAnswers();
        
        // Show first question
        showQuestion(0);
    }
    
    private void initializeViews() {
        optionsContainer = findViewById(R.id.options_container);
        questionText = findViewById(R.id.question_text);
        progressText = findViewById(R.id.progress_text);
        nextButton = findViewById(R.id.next_button);
        previousButton = findViewById(R.id.previous_button);
        skipButton = findViewById(R.id.skip_button);
    }
    
    private void setupClickListeners() {
        // Close button
        findViewById(R.id.close_button).setOnClickListener(v -> finish());
        
        // Next button
        nextButton.setOnClickListener(v -> nextQuestion());
        
        // Previous button
        previousButton.setOnClickListener(v -> previousQuestion());
        
        // Skip button
        skipButton.setOnClickListener(v -> skipQuestion());
    }
    
    private void showQuestion(int questionIndex) {
        currentQuestionIndex = questionIndex;
        questionText.setText(questions[questionIndex]);
        progressText.setText((questionIndex + 1) + "/" + totalQuestions);
        
        // Clear previous options efficiently
        optionsContainer.removeAllViews();
        
        // Create option buttons
        createOptionButtons(questionIndex);
        
        // Update navigation buttons
        updateNavigationButtons();
        
        // Restore previous answer
        restorePreviousAnswer(questionIndex);
        
        // Scroll to top for better UX
        findViewById(R.id.scrollable_options).scrollTo(0, 0);
    }
    
    private void createOptionButtons(int questionIndex) {
        String[] options = questionOptions[questionIndex];
        boolean isMultipleChoice = multipleChoiceQuestions[questionIndex];
        
        for (String option : options) {
            Button optionButton = createOptionButton(option, isMultipleChoice);
            optionsContainer.addView(optionButton);
        }
    }
    
    private Button createOptionButton(String optionText, boolean isMultipleChoice) {
        Button button = new Button(this);
        button.setText(optionText);
        button.setTextSize(16);
        button.setTextColor(getResources().getColor(android.R.color.black));
        button.setBackgroundResource(R.drawable.option_button_unselected);
        
        // Create layout params with margin
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT,
            (int) (56 * getResources().getDisplayMetrics().density)
        );
        params.bottomMargin = (int) (12 * getResources().getDisplayMetrics().density);
        button.setLayoutParams(params);
        
        // Disable state list animator for better performance
        button.setStateListAnimator(null);
        button.setElevation(0);
        
        // Set click listener
        button.setOnClickListener(v -> selectOption(button, optionText, isMultipleChoice));
        
        return button;
    }
    
    private void selectOption(Button button, String optionText, boolean isMultipleChoice) {
        String key = "question_" + currentQuestionIndex;
        
        if (isMultipleChoice) {
            // Multiple choice - toggle selection
            List<String> selectedChoices = multipleSelections.get(currentQuestionIndex);
            if (selectedChoices == null) {
                selectedChoices = new ArrayList<>();
                multipleSelections.put(currentQuestionIndex, selectedChoices);
            }
            
            boolean isSelected = selectedChoices.contains(optionText);
            if (isSelected) {
                selectedChoices.remove(optionText);
                button.setBackgroundResource(R.drawable.option_button_unselected);
                button.setTextColor(getResources().getColor(android.R.color.black));
            } else {
                selectedChoices.add(optionText);
                button.setBackgroundResource(R.drawable.option_button_selected);
                button.setTextColor(getResources().getColor(android.R.color.black));
            }
            
            // Save as comma-separated string
            answers.put(key, String.join(",", selectedChoices));
        } else {
            // Single choice - select only this option
            // Reset all buttons first
            for (int i = 0; i < optionsContainer.getChildCount(); i++) {
                Button otherButton = (Button) optionsContainer.getChildAt(i);
                otherButton.setBackgroundResource(R.drawable.option_button_unselected);
                otherButton.setTextColor(getResources().getColor(android.R.color.black));
            }
            
            // Select current button
            button.setBackgroundResource(R.drawable.option_button_selected);
            button.setTextColor(getResources().getColor(android.R.color.black));
            
            answers.put(key, optionText);
        }
        
        // Update navigation buttons
        updateNavigationButtons();
    }
    
    private void updateNavigationButtons() {
        // Previous button
        if (currentQuestionIndex == 0) {
            previousButton.setVisibility(View.GONE);
        } else {
            previousButton.setVisibility(View.VISIBLE);
        }
        
        // Skip button - always visible except on last question
        if (currentQuestionIndex == totalQuestions - 1) {
            skipButton.setVisibility(View.GONE);
        } else {
            skipButton.setVisibility(View.VISIBLE);
        }
        
        // Next button
        if (currentQuestionIndex == totalQuestions - 1) {
            nextButton.setText("Save Preferences");
        } else {
            nextButton.setText("Next");
        }
        
        // Enable next button if current question has an answer
        String key = "question_" + currentQuestionIndex;
        boolean hasAnswer = answers.containsKey(key) && !answers.get(key).isEmpty();
        nextButton.setEnabled(hasAnswer);
        nextButton.setBackgroundResource(hasAnswer ? R.drawable.button_next_active : R.drawable.button_next_inactive);
    }
    
    private void restorePreviousAnswer(int questionIndex) {
        String key = "question_" + questionIndex;
        String answer = answers.get(key);
        
        if (answer != null && !answer.isEmpty()) {
            if (multipleChoiceQuestions[questionIndex]) {
                // Multiple choice - select all buttons
                String[] selectedChoices = answer.split(",");
                for (int i = 0; i < optionsContainer.getChildCount(); i++) {
                    Button button = (Button) optionsContainer.getChildAt(i);
                    if (Arrays.asList(selectedChoices).contains(button.getText().toString())) {
                        button.setBackgroundResource(R.drawable.option_button_selected);
                        button.setTextColor(getResources().getColor(android.R.color.black));
                    }
                }
            } else {
                // Single choice - find and select the button
                for (int i = 0; i < optionsContainer.getChildCount(); i++) {
                    Button button = (Button) optionsContainer.getChildAt(i);
                    if (button.getText().toString().equals(answer)) {
                        button.setBackgroundResource(R.drawable.option_button_selected);
                        button.setTextColor(getResources().getColor(android.R.color.black));
                        break;
                    }
                }
            }
            
            updateNavigationButtons();
        }
    }
    
    private void nextQuestion() {
        if (currentQuestionIndex < totalQuestions - 1) {
            showQuestion(currentQuestionIndex + 1);
        } else {
            // Save all answers and finish
            saveAllAnswers();
            Toast.makeText(this, "Food preferences updated successfully!", Toast.LENGTH_SHORT).show();
            finish();
        }
    }
    
    private void previousQuestion() {
        if (currentQuestionIndex > 0) {
            showQuestion(currentQuestionIndex - 1);
        }
    }
    
    private void skipQuestion() {
        // Mark current question as skipped
        String key = "question_" + currentQuestionIndex;
        answers.put(key, "SKIPPED");
        
        if (currentQuestionIndex < totalQuestions - 1) {
            showQuestion(currentQuestionIndex + 1);
        } else {
            // Save all answers and finish
            saveAllAnswers();
            Toast.makeText(this, "Food preferences updated successfully!", Toast.LENGTH_SHORT).show();
            finish();
        }
    }
    
    private void saveAllAnswers() {
        SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        SharedPreferences.Editor editor = prefs.edit();
        
        // Save individual answers with user-specific keys
        for (Map.Entry<String, String> entry : answers.entrySet()) {
            String key = userEmail + "_" + entry.getKey();
            editor.putString(key, entry.getValue());
        }
        
        // Combine all answers into a comprehensive food preferences string
        String combinedPreferences = combineAllAnswers();
        editor.putString(userEmail + "_food_preferences_combined", combinedPreferences);
        
        // Mark preferences as updated
        editor.putLong(userEmail + "_preferences_updated", System.currentTimeMillis());
        
        // Clear food recommendation cache to force refresh with new preferences
        clearFoodRecommendationCache();
        
        editor.apply();
        
        Log.d("FoodPreferencesActivity", "Saved preferences for user: " + userEmail);
        Log.d("FoodPreferencesActivity", "Combined preferences: " + combinedPreferences);
    }
    
    private String combineAllAnswers() {
        StringBuilder combined = new StringBuilder();
        
        // Question 1: Main Food Identity
        String foodIdentity = answers.get("question_0");
        if (foodIdentity != null && !foodIdentity.equals("SKIPPED")) {
            combined.append("Food Identity: ").append(foodIdentity).append(". ");
        }
        
        // Question 2: Food Allergies
        String allergies = answers.get("question_1");
        if (allergies != null && !allergies.equals("SKIPPED") && !allergies.isEmpty()) {
            combined.append("Food Allergies: ").append(allergies.replace(",", ", ")).append(". ");
        }
        
        // Question 3: Food Cravings
        String cravings = answers.get("question_2");
        if (cravings != null && !cravings.equals("SKIPPED") && !cravings.isEmpty()) {
            combined.append("Food Cravings: ").append(cravings.replace(",", ", ")).append(". ");
        }
        
        // Question 4: Cooking Methods
        String cookingMethods = answers.get("question_3");
        if (cookingMethods != null && !cookingMethods.equals("SKIPPED") && !cookingMethods.isEmpty()) {
            combined.append("Preferred Cooking Methods: ").append(cookingMethods.replace(",", ", ")).append(". ");
        }
        
        // Question 5: Budget Range
        String budget = answers.get("question_4");
        if (budget != null && !budget.equals("SKIPPED")) {
            combined.append("Daily Food Budget: ").append(budget).append(". ");
        }
        
        return combined.toString().trim();
    }
    
    private void clearFoodRecommendationCache() {
        try {
            // Clear any food recommendation caches
            SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
            SharedPreferences.Editor editor = prefs.edit();
            
            // Clear food recommendation timestamps to force refresh
            editor.remove(userEmail + "_last_food_recommendation_time");
            editor.remove(userEmail + "_last_breakfast_recommendation_time");
            editor.remove(userEmail + "_last_lunch_recommendation_time");
            editor.remove(userEmail + "_last_dinner_recommendation_time");
            editor.remove(userEmail + "_last_snack_recommendation_time");
            
            // Clear any cached food data
            editor.remove(userEmail + "_cached_food_recommendations");
            editor.remove(userEmail + "_cached_breakfast_foods");
            editor.remove(userEmail + "_cached_lunch_foods");
            editor.remove(userEmail + "_cached_dinner_foods");
            editor.remove(userEmail + "_cached_snack_foods");
            
            editor.apply();
            
            // Clear GeminiCacheManager cache (this is the main cache system)
            try {
                GeminiCacheManager.clearUserData(this, userEmail);
                Log.d("FoodPreferencesActivity", "Cleared GeminiCacheManager cache for user: " + userEmail);
            } catch (Exception e) {
                Log.w("FoodPreferencesActivity", "Could not clear GeminiCacheManager cache: " + e.getMessage());
            }
            
            Log.d("FoodPreferencesActivity", "Cleared food recommendation cache for user: " + userEmail);
        } catch (Exception e) {
            Log.e("FoodPreferencesActivity", "Error clearing food recommendation cache: " + e.getMessage());
        }
    }
    
    private void loadExistingAnswers() {
        SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        
        // Load answers with user-specific keys
        for (int i = 0; i < totalQuestions; i++) {
            String key = userEmail + "_question_" + i;
            String value = prefs.getString(key, "");
            
            if (!value.isEmpty()) {
                answers.put("question_" + i, value);
                
                // For multiple choice questions, also populate the multipleSelections map
                if (i < multipleChoiceQuestions.length && multipleChoiceQuestions[i]) {
                    List<String> choices = Arrays.asList(value.split(","));
                    multipleSelections.put(i, new ArrayList<>(choices));
                }
            }
        }
    }
}