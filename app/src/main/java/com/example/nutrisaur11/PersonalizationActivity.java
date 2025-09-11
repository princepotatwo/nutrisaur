package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageButton;
import android.view.animation.Animation;
import android.view.animation.ScaleAnimation;
import android.view.GestureDetector;
import android.view.MotionEvent;
import android.widget.LinearLayout;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import androidx.viewpager2.widget.ViewPager2;
import com.google.android.material.floatingactionbutton.FloatingActionButton;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.io.Serializable;

public class PersonalizationActivity extends AppCompatActivity implements PersonalizationQuestionAdapter.OnQuestionInteractionListener {
    private int currentQuestionIndex = 0;
    private List<PersonalizationQuestion> questions;
    private Map<String, Object> userAnswers = new HashMap<String, Object>();
    private ViewPager2 questionViewPager;
    private TextView questionTitle;
    private ImageButton btnBack;
    private PersonalizationQuestionAdapter adapter;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_personalization);
        
        // Check if this is for editing preferences
        boolean isEditingPreferences = getIntent().getBooleanExtra("is_editing_preferences", false);
        
        // Initialize views
        questionViewPager = findViewById(R.id.question_view_pager);
        questionTitle = findViewById(R.id.question_title);
        btnBack = findViewById(R.id.btn_back);
        
        // Initialize questions
        questions = PersonalizationQuestion.getQuestions();
        
        // Setup ViewPager2
        setupViewPager();
        
        // Setup back button
        setupBackButton(isEditingPreferences);
    }
    
    private void setupViewPager() {
        adapter = new PersonalizationQuestionAdapter(this, questions, this);
        adapter.setUserAnswers(userAnswers); // Pass user answers to adapter
        questionViewPager.setAdapter(adapter);
        
        // Update header for first question
        updateHeader(currentQuestionIndex);
        
        // Setup page change listener
        questionViewPager.registerOnPageChangeCallback(new ViewPager2.OnPageChangeCallback() {
            @Override
            public void onPageSelected(int position) {
                currentQuestionIndex = position;
                updateHeader(position);
                // Update adapter with current answers
                adapter.setUserAnswers(userAnswers);
                adapter.notifyItemChanged(position);
            }
        });
        
        // Setup swipe gestures for custom behavior
        setupSwipeGestures();
    }
    
    private void setupSwipeGestures() {
        // Create gesture detector for swipe gestures
        GestureDetector gestureDetector = new GestureDetector(this, new GestureDetector.SimpleOnGestureListener() {
            private static final float SWIPE_THRESHOLD = 100;
            private static final float SWIPE_VELOCITY_THRESHOLD = 100;
            
            @Override
            public boolean onFling(MotionEvent e1, MotionEvent e2, float velocityX, float velocityY) {
                if (e1 == null || e2 == null) return false;
                
                float deltaX = e2.getX() - e1.getX();
                float deltaY = e2.getY() - e1.getY();
                
                // Check if it's a horizontal swipe
                if (Math.abs(deltaX) > Math.abs(deltaY) && 
                    Math.abs(deltaX) > SWIPE_THRESHOLD && 
                    Math.abs(velocityX) > SWIPE_VELOCITY_THRESHOLD) {
                    
                    if (deltaX > 0) {
                        // Swipe right - go to next question (save answers)
                        saveCurrentAnswersAndNext();
                    } else {
                        // Swipe left - go to previous question
                        moveToPreviousQuestion();
                    }
                    return true;
                }
                return false;
            }
        });
        
        // Add touch listener to the main layout
        View mainLayout = findViewById(R.id.main_content_layout);
        mainLayout.setOnTouchListener(new View.OnTouchListener() {
            @Override
            public boolean onTouch(View v, MotionEvent event) {
                return gestureDetector.onTouchEvent(event);
            }
        });
    }
    
    private void saveCurrentAnswersAndNext() {
        // Check if current question has been answered
        if (!isCurrentQuestionAnswered()) {
            // Show toast or some indication that question needs to be answered
            android.widget.Toast.makeText(this, "Please select an answer before continuing", android.widget.Toast.LENGTH_SHORT).show();
            return;
        }
        
        // For multiple choice questions, save current selections
        PersonalizationQuestion question = questions.get(currentQuestionIndex);
        if (question.isMultipleChoice()) {
            // The answers are already saved when checkboxes are clicked
            // Just move to next question
            moveToNextQuestion();
        } else {
            // For single choice, just move to next
            moveToNextQuestion();
        }
    }
    
    private boolean isCurrentQuestionAnswered() {
        String key = "question_" + currentQuestionIndex;
        return userAnswers.containsKey(key);
    }
    
    private void updateHeader(int position) {
        PersonalizationQuestion question = questions.get(position);
        questionTitle.setText(question.getTitle());
        
        TextView subtitle = findViewById(R.id.question_subtitle);
        if (question.isMultipleChoice()) {
            subtitle.setText("SELECT ALL THAT APPLY");
        } else {
            subtitle.setText("PICK ONE");
        }
    }
    

    
    private void setupBackButton(boolean isEditingPreferences) {
        btnBack.setOnClickListener(v -> {
            if (isEditingPreferences) {
                // If editing preferences, just go back to FoodActivity
                finish();
            } else {
                // If initial setup, navigate back to Food Activity
                Intent intent = new Intent(PersonalizationActivity.this, FoodActivity.class);
                startActivity(intent);
                finish();
            }
        });
    }
    
    @Override
    public void onChoiceSelected(int questionIndex, Choice choice) {
        PersonalizationQuestion question = questions.get(questionIndex);
        
        if (question.isMultipleChoice()) {
            // Handle multiple choice - save answer and stay on same question
            handleMultipleChoiceSelection(questionIndex, choice);
        } else {
            // Handle single choice - save answer and auto advance to next question
            handleSingleChoiceSelection(questionIndex, choice);
            if (questionIndex < questions.size() - 1) {
                // Auto advance to next question after a short delay
                questionViewPager.postDelayed(() -> {
                    questionViewPager.setCurrentItem(questionIndex + 1, true);
                }, 500);
            }
        }
    }
    
    @Override
    public void onQuestionChanged(int position) {
        // This is handled by the ViewPager2 page change callback
    }
    
    private void handleSingleChoiceSelection(int questionIndex, Choice choice) {
        userAnswers.put("question_" + questionIndex, choice.getText());
    }
    
    private void handleMultipleChoiceSelection(int questionIndex, Choice choice) {
        String key = "question_" + questionIndex;
        List<String> selectedChoices = (List<String>) userAnswers.get(key);
        if (selectedChoices == null) {
            selectedChoices = new ArrayList<>();
        }
        
        boolean isSelected = selectedChoices.contains(choice.getText());
        if (isSelected) {
            selectedChoices.remove(choice.getText());
        } else {
            selectedChoices.add(choice.getText());
        }
        
        userAnswers.put(key, selectedChoices);
        
        // Update the checkbox state in the adapter
        adapter.updateCheckboxState(questionIndex, choice, !isSelected);
    }
    

    

    
    private int makeColorLighter(int color, float factor) {
        float[] hsv = new float[3];
        android.graphics.Color.colorToHSV(color, hsv);
        hsv[2] = Math.min(1.0f, hsv[2] + factor); // Increase value (brightness)
        return android.graphics.Color.HSVToColor(hsv);
    }
    
    private void animateSelection(View view) {
        // Smooth button press animation
        view.animate()
            .scaleX(0.98f)
            .scaleY(0.98f)
            .alpha(0.8f)
            .setDuration(150)
            .withEndAction(() -> {
                view.animate()
                    .scaleX(1.0f)
                    .scaleY(1.0f)
                    .alpha(1.0f)
                    .setDuration(150);
            });
    }
    
    private void moveToNextQuestion() {
        if (currentQuestionIndex < questions.size() - 1) {
            questionViewPager.setCurrentItem(currentQuestionIndex + 1, true);
        } else {
            finishPersonalization();
        }
    }
    
    private void moveToPreviousQuestion() {
        if (currentQuestionIndex > 0) {
            // Save current answer before moving back
            saveCurrentAnswer();
            questionViewPager.setCurrentItem(currentQuestionIndex - 1, true);
        }
    }
    
    private void saveCurrentAnswer() {
        // This method can be used to save the current answer if needed
        // For now, answers are saved when choices are selected
    }
    
    private void finishPersonalization() {
        // Check if this is for editing preferences
        boolean isEditingPreferences = getIntent().getBooleanExtra("is_editing_preferences", false);
        
        // Mark personalization as completed and store answers
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        editor.putBoolean("personalization_completed", true);
        
        // Store answers in SharedPreferences
        for (Map.Entry<String, Object> entry : userAnswers.entrySet()) {
            if (entry.getValue() instanceof String) {
                editor.putString(entry.getKey(), (String) entry.getValue());
            } else if (entry.getValue() instanceof List) {
                // Convert List to comma-separated string
                List<String> list = (List<String>) entry.getValue();
                editor.putString(entry.getKey(), String.join(",", list));
            }
        }
        editor.apply();
        
        if (isEditingPreferences) {
            // If editing preferences, just finish and go back to FoodActivity
            finish();
        } else {
            // If initial setup, start Food Activity
            android.content.Intent intent = new android.content.Intent(this, FoodActivity.class);
            startActivity(intent);
            finish();
        }
    }
}
