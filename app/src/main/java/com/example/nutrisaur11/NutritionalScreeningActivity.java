package com.example.nutrisaur11;

import android.app.DatePickerDialog;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONObject;

import java.util.ArrayList;
import java.util.Calendar;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class NutritionalScreeningActivity extends AppCompatActivity {
    
    // FCM Token Manager
    private FCMTokenManager fcmTokenManager;
    
    // UI Components
    private LinearLayout optionsContainer;
    private TextView questionText;
    private TextView progressText;
    private Button nextButton;
    private Button btnPrevious;
    private EditText weightInput;
    private EditText heightInput;
    private android.app.ProgressDialog progressDialog;
    
    // Question Management
    private int currentQuestionIndex = 0;
    private Map<String, String> answers = new HashMap<>();
    private List<Question> questions = new ArrayList<>();
    
    // Question Types
    private enum QuestionType {
        MUNICIPALITY, BARANGAY, SEX, BIRTHDAY, PREGNANCY, WEIGHT, HEIGHT
    }
    
    // Question Data Structure
    private static class Question {
        String text;
        QuestionType type;
        String[] options;
        String answer;
        boolean isRequired;
        
        Question(String text, QuestionType type, String[] options, boolean isRequired) {
            this.text = text;
            this.type = type;
            this.options = options;
            this.isRequired = isRequired;
            this.answer = "";
        }
    }
    
    // Municipality and Barangay Data
    private String[] municipalities = {
        "ABUCAY", "BAGAC", "CITY OF BALANGA", "DINALUPIHAN", "HERMOSA", 
        "LIMAY", "MARIVELES", "MORONG", "ORANI", "ORION", "PILAR", "SAMAL"
    };
    
    private Map<String, String[]> barangayMap = new HashMap<>();
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_nutritional_screening);
        
        // Initialize FCM Token Manager
        fcmTokenManager = new FCMTokenManager(this);
        
        initializeViews();
        setupBarangayData();
        initializeQuestions();
        setupListeners();
        showCurrentQuestion();
    }
    
    private void initializeViews() {
        optionsContainer = findViewById(R.id.options_container);
        questionText = findViewById(R.id.question_text);
        progressText = findViewById(R.id.progress_text);
        nextButton = findViewById(R.id.next_button);
        btnPrevious = findViewById(R.id.btn_previous);
        weightInput = findViewById(R.id.weight_input_new);
        heightInput = findViewById(R.id.height_input);
    }
    
    private void setupBarangayData() {
        barangayMap.put("ABUCAY", new String[]{
        "Bangkal", "Calaylayan (Pob.)", "Capitangan", "Gabon", "Laon (Pob.)", 
        "Mabatang", "Omboy", "Salian", "Wawa (Pob.)"
        });
        barangayMap.put("BAGAC", new String[]{
        "Bagumbayan (Pob.)", "Banawang", "Binuangan", "Binukawan", "Ibaba", 
        "Ibis", "Pag-asa (Wawa-Sibacan)", "Parang", "Paysawan", "Quinawan", 
        "San Antonio", "Saysain", "Tabing-Ilog (Pob.)", "Atilano L. Ricardo"
        });
        barangayMap.put("CITY OF BALANGA", new String[]{
            "Bagong Silang", "Bagumbayan", "Batanes", "Cataning", "Central", 
            "Dangcol", "Doña Francisca", "Lote", "Malabia", "Munting Batangas", 
            "Poblacion", "Puerto Rivas Ibaba", "Puerto Rivas Itaas", "San Jose", 
            "Sibacan", "Talipapa", "Tanato", "Tenejero", "Tortugas", "Tuyo"
        });
        barangayMap.put("DINALUPIHAN", new String[]{
            "Bayan-bayanan", "Bonifacio", "Burgos", "Daang Bago", "Del Pilar", 
            "General Emilio Aguinaldo", "General Luna", "Kawayan", "Layac", 
            "Lourdes", "Luakan", "Maligaya", "Naparing", "Paco", "Pag-asa", 
            "Pagalanggang", "Panggalangan", "Pinulot", "Poblacion", "Rizal", 
            "Saguing", "San Benito", "San Isidro", "San Ramon", "Santo Niño", 
            "Sapang Kawayan", "Tipo", "Tubo-tubo", "Zamora"
        });
        barangayMap.put("HERMOSA", new String[]{
            "A. Ricardo", "Almacen", "Bamban", "Burgos-Soliman", "Cataning", 
            "Culis", "Daungan", "Judicial", "Mabiga", "Mabuco", "Maite", 
            "Mambog - Mandama", "Palihan", "Pandatung", "Poblacion", "Saba", 
            "Sacatihan", "Sumalo", "Tipo", "Tortugas"
        });
        barangayMap.put("LIMAY", new String[]{
            "Alangan", "Kitang I", "Kitang II", "Kitang III", "Kitang IV", 
            "Kitang V", "Lamao", "Luz", "Poblacion", "Reforma", "Sitio Baga", 
            "Sitio Pulo", "Wawa"
        });
        barangayMap.put("MARIVELES", new String[]{
            "Alion", "Balong Anito", "Baseco Country", "Batan", "Biaan", 
            "Cabcaben", "Camaya", "Lucanin", "Mabayo", "Malaya", "Maligaya", 
            "Mountain View", "Poblacion", "San Carlos", "San Isidro", "San Nicolas", 
            "Saysain", "Sisiman", "Townsite", "Vista Alegre"
        });
        barangayMap.put("MORONG", new String[]{
            "Binaritan", "Mabayo", "Nagbalayong", "Poblacion", "Sabang", 
            "San Jose", "Sitio Pulo"
        });
        barangayMap.put("ORANI", new String[]{
            "Apollo", "Bagong Paraiso", "Balut", "Bayani", "Cabral", "Calero", 
            "Calutit", "Camachile", "Kaparangan", "Luna", "Mabolo", "Magtaong", 
            "Maligaya", "Pag-asa", "Paglabanan", "Pagtakhan", "Palihan", 
            "Poblacion", "Rizal", "Sagrada", "San Jose", "Sulong", "Tagumpay", 
            "Tala", "Talimundoc", "Tapulao", "Tugatog", "Wawa"
        });
        barangayMap.put("ORION", new String[]{
            "Balagtas", "Balut", "Bantan", "Bilolo", "Calungusan", "Camachile", 
            "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente", 
            "Wawa", "Poblacion"
        });
        barangayMap.put("PILAR", new String[]{
            "Alas-asin", "Bantan Munti", "Bantan", "Del Rosario", "Diwa", 
            "Landing", "Liwayway", "Nagbalayong", "Panilao", "Pantingan", 
            "Poblacion", "Rizal", "Saguing", "Santo Cristo", "Wakas"
        });
        barangayMap.put("SAMAL", new String[]{
            "Bagumbayan", "Bantan", "Bilolo", "Calungusan", "Camachile", 
            "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente", 
            "Wawa", "Balagtas", "Balut", "Bataan"
        });
    }
    
    private void initializeQuestions() {
        questions.clear();
        
        // Question 0: Municipality
        questions.add(new Question(
            "Select your municipality",
            QuestionType.MUNICIPALITY,
            municipalities,
            true
        ));
        
        // Question 1: Barangay (will be populated based on municipality)
        questions.add(new Question(
            "Select your barangay",
            QuestionType.BARANGAY,
            new String[]{}, // Will be populated dynamically
            true
        ));
        
        // Question 2: Sex
        questions.add(new Question(
            "What is your sex?",
            QuestionType.SEX,
            new String[]{"Male", "Female"},
            true
        ));
        
        // Question 3: Birthday
        questions.add(new Question(
            "What is your birthday?",
            QuestionType.BIRTHDAY,
            new String[]{}, // No options for date picker
            true
        ));
        
        // Question 4: Pregnancy (conditional)
        questions.add(new Question(
            "Are you pregnant?",
            QuestionType.PREGNANCY,
            new String[]{"Yes", "No"},
            true
        ));
        
        // Question 5: Weight
        questions.add(new Question(
            "What is your weight (kg)?",
            QuestionType.WEIGHT,
            new String[]{}, // Input field
            true
        ));
        
        // Question 6: Height
        questions.add(new Question(
            "What is your height (cm)?",
            QuestionType.HEIGHT,
            new String[]{}, // Input field
            true
        ));
    }
    
    private void setupListeners() {
        nextButton.setOnClickListener(v -> nextQuestion());
        btnPrevious.setOnClickListener(v -> previousQuestion());
        
        // Add touch listeners for input fields to ensure they're interactive
        weightInput.setOnTouchListener((v, event) -> {
            if (event.getAction() == android.view.MotionEvent.ACTION_DOWN) {
                weightInput.requestFocus();
                android.view.inputmethod.InputMethodManager imm = (android.view.inputmethod.InputMethodManager) getSystemService(android.content.Context.INPUT_METHOD_SERVICE);
                if (imm != null) {
                    imm.showSoftInput(weightInput, android.view.inputmethod.InputMethodManager.SHOW_IMPLICIT);
                }
            }
            return false; // Let the EditText handle the event
        });
        
        heightInput.setOnTouchListener((v, event) -> {
            if (event.getAction() == android.view.MotionEvent.ACTION_DOWN) {
                heightInput.requestFocus();
                android.view.inputmethod.InputMethodManager imm = (android.view.inputmethod.InputMethodManager) getSystemService(android.content.Context.INPUT_METHOD_SERVICE);
                if (imm != null) {
                    imm.showSoftInput(heightInput, android.view.inputmethod.InputMethodManager.SHOW_IMPLICIT);
                }
            }
            return false; // Let the EditText handle the event
        });
        
        // Add text change listener for real-time height validation
        heightInput.addTextChangedListener(new android.text.TextWatcher() {
    @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            
            @Override
            public void afterTextChanged(android.text.Editable s) {
                // Only validate if we're on the height question
                if (currentQuestionIndex < questions.size() && questions.get(currentQuestionIndex).type == QuestionType.HEIGHT) {
                    validateHeight();
                }
            }
        });
        
        // Add text change listener for real-time weight validation
        weightInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            
            @Override
            public void afterTextChanged(android.text.Editable s) {
                // Only validate if we're on the weight question
                if (currentQuestionIndex < questions.size() && questions.get(currentQuestionIndex).type == QuestionType.WEIGHT) {
                    validateWeight();
                }
            }
        });
    }
    
    private void showCurrentQuestion() {
        if (currentQuestionIndex >= questions.size()) {
            completeScreening();
            return;
        }
        
        Question currentQuestion = questions.get(currentQuestionIndex);
        questionText.setText(currentQuestion.text);
        
        // Update progress
        int totalQuestions = getTotalQuestions();
        progressText.setText((currentQuestionIndex + 1) + "/" + totalQuestions);
        
        // Show/hide previous button
        btnPrevious.setVisibility(currentQuestionIndex > 0 ? View.VISIBLE : View.GONE);
        
        // Clear previous UI
        clearOptions();
        hideInputFields();
        
        // Show appropriate UI based on question type
        switch (currentQuestion.type) {
            case MUNICIPALITY:
                showMunicipalityOptions();
                break;
            case BARANGAY:
                showBarangayOptions();
                break;
            case SEX:
                showSexOptions();
                break;
            case BIRTHDAY:
                showBirthdayPicker();
                break;
            case PREGNANCY:
                showPregnancyOptions();
                break;
            case WEIGHT:
                showWeightInput();
                break;
            case HEIGHT:
                showHeightInput();
                break;
        }
        
        // Restore previous answer if available
        restoreAnswer(currentQuestion);
    }
    
    private int getTotalQuestions() {
        int total = 4; // Municipality, Barangay, Sex, Birthday are always included
        
        // Add pregnancy question if applicable
        if (shouldShowPregnancyQuestion()) {
            total++;
        }
        
        // Add weight and height
        total += 2;
        
        return total;
    }
    
    private boolean shouldShowPregnancyQuestion() {
        String sex = answers.get("question_2");
        String birthday = answers.get("question_3");
        
        if (!"Female".equals(sex) || birthday == null || birthday.isEmpty()) {
            return false;
        }
        
        try {
            int age = calculateAgeFromBirthday(birthday);
            return age >= 18 && age <= 50;
        } catch (Exception e) {
            return false;
        }
    }
    
    private void clearOptions() {
        // Remove only dynamically created buttons, not the input fields
        for (int i = optionsContainer.getChildCount() - 1; i >= 0; i--) {
            View child = optionsContainer.getChildAt(i);
            // Only remove buttons, keep EditText fields
            if (child instanceof Button) {
                optionsContainer.removeViewAt(i);
            }
        }
        
        // Ensure input fields are hidden
        weightInput.setVisibility(View.GONE);
        heightInput.setVisibility(View.GONE);
    }
    
    private void hideInputFields() {
        weightInput.setVisibility(View.GONE);
        heightInput.setVisibility(View.GONE);
        
        // Also hide any dynamically created buttons that might be showing
        for (int i = 0; i < optionsContainer.getChildCount(); i++) {
            View child = optionsContainer.getChildAt(i);
            if (child instanceof Button) {
                child.setVisibility(View.GONE);
            }
        }
    }
    
    private void showMunicipalityOptions() {
        for (String municipality : municipalities) {
            Button button = createOptionButton(municipality);
            button.setOnClickListener(v -> selectOption(button, municipality));
            optionsContainer.addView(button);
        }
    }
    
    private void showBarangayOptions() {
        String selectedMunicipality = answers.get("question_0");
        if (selectedMunicipality == null) {
            Toast.makeText(this, "Please select municipality first", Toast.LENGTH_SHORT).show();
            return;
        }
        
        String[] barangays = barangayMap.get(selectedMunicipality);
        if (barangays != null) {
            for (String barangay : barangays) {
                Button button = createOptionButton(barangay);
                button.setOnClickListener(v -> selectOption(button, barangay));
                optionsContainer.addView(button);
            }
        }
    }
    
    private void showSexOptions() {
        String[] sexOptions = {"Male", "Female"};
        for (String sex : sexOptions) {
            Button button = createOptionButton(sex);
            button.setOnClickListener(v -> selectOption(button, sex));
            optionsContainer.addView(button);
        }
    }
    
    private void showBirthdayPicker() {
        Log.d("NutritionalScreening", "Showing birthday picker");
        // Clear any existing content first
        clearOptions();
        
        Button dateButton = createOptionButton("Select Birthday");
        dateButton.setOnClickListener(v -> showDatePicker());
        optionsContainer.addView(dateButton);
        Log.d("NutritionalScreening", "Created birthday button, container now has " + optionsContainer.getChildCount() + " children");
    }
    
    private void showPregnancyOptions() {
        String[] pregnancyOptions = {"Yes", "No"};
        for (String option : pregnancyOptions) {
            Button button = createOptionButton(option);
            button.setOnClickListener(v -> selectOption(button, option));
            optionsContainer.addView(button);
        }
    }
    
    private void showWeightInput() {
        // Clear all other options first
        clearOptions();
        
        // Show weight input field
        weightInput.setVisibility(View.VISIBLE);
        weightInput.setHint("Enter weight in kg");
        weightInput.setText("");
        
        // Ensure the input field is fully interactive
        weightInput.setFocusable(true);
        weightInput.setFocusableInTouchMode(true);
        weightInput.setClickable(true);
        weightInput.setEnabled(true);
        
        // Request focus and show keyboard
        weightInput.requestFocus();
        android.view.inputmethod.InputMethodManager imm = (android.view.inputmethod.InputMethodManager) getSystemService(android.content.Context.INPUT_METHOD_SERVICE);
        if (imm != null) {
            imm.showSoftInput(weightInput, android.view.inputmethod.InputMethodManager.SHOW_IMPLICIT);
        }
    }
    
    private void showHeightInput() {
        // Clear all other options first
        clearOptions();
        
        // Show height input field
        heightInput.setVisibility(View.VISIBLE);
        heightInput.setHint("Enter height in cm");
        heightInput.setText("");
        
        // Ensure the input field is fully interactive
        heightInput.setFocusable(true);
        heightInput.setFocusableInTouchMode(true);
        heightInput.setClickable(true);
        heightInput.setEnabled(true);
        
        // Request focus and show keyboard
        heightInput.requestFocus();
        android.view.inputmethod.InputMethodManager imm = (android.view.inputmethod.InputMethodManager) getSystemService(android.content.Context.INPUT_METHOD_SERVICE);
        if (imm != null) {
            imm.showSoftInput(heightInput, android.view.inputmethod.InputMethodManager.SHOW_IMPLICIT);
        }
    }
    
    private Button createOptionButton(String text) {
        Button button = new Button(this);
        button.setText(text);
        
        // Fixed height to prevent stretching
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT,
            (int) (56 * getResources().getDisplayMetrics().density) // 56dp in pixels
        );
        params.setMargins(0, 0, 0, (int) (12 * getResources().getDisplayMetrics().density)); // 12dp margin
        button.setLayoutParams(params);
        
        button.setBackgroundResource(R.drawable.option_button_unselected);
        button.setTextColor(getResources().getColor(android.R.color.black));
        button.setTextSize(16);
        
        // Remove elevation and shadow effects
        if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.LOLLIPOP) {
            button.setElevation(0f);
        }
        button.setStateListAnimator(null);
        
        // Prevent text from being stretched
        button.setSingleLine(true);
        button.setEllipsize(android.text.TextUtils.TruncateAt.END);
        
        return button;
    }
    
    private void selectOption(Button button, String value) {
        // Clear all other selections
        for (int i = 0; i < optionsContainer.getChildCount(); i++) {
            View child = optionsContainer.getChildAt(i);
            if (child instanceof Button) {
                Button otherButton = (Button) child;
                otherButton.setBackgroundResource(R.drawable.option_button_unselected);
                otherButton.setTextColor(getResources().getColor(android.R.color.black));
            }
        }
        
        // Select current button
        button.setBackgroundResource(R.drawable.option_button_selected);
        button.setTextColor(getResources().getColor(android.R.color.white));
                
                // Store answer
        String answerKey = "question_" + currentQuestionIndex;
        answers.put(answerKey, value);
                
                // Enable next button
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
    }
    
    private void showDatePicker() {
        Calendar calendar = Calendar.getInstance();
        int year = calendar.get(Calendar.YEAR) - 20; // Default to 20 years ago
        int month = calendar.get(Calendar.MONTH);
        int day = calendar.get(Calendar.DAY_OF_MONTH);
        
        DatePickerDialog datePickerDialog = new DatePickerDialog(
            this,
            (view, selectedYear, selectedMonth, selectedDay) -> {
                String birthday = String.format("%04d-%02d-%02d", selectedYear, selectedMonth + 1, selectedDay);
                int age = calculateAgeFromBirthday(birthday);
                
                // Find the date button (skip any EditText children)
                Button dateButton = null;
                for (int i = 0; i < optionsContainer.getChildCount(); i++) {
                    View child = optionsContainer.getChildAt(i);
                    if (child instanceof Button) {
                        dateButton = (Button) child;
                        break;
                    }
                }
                
                if (dateButton != null) {
                    dateButton.setText("Birthday: " + birthday + " (Age: " + age + ")");
                    dateButton.setBackgroundResource(R.drawable.option_button_selected);
                    dateButton.setTextColor(getResources().getColor(android.R.color.white));
                }
                
                // Store answer
                String answerKey = "question_" + currentQuestionIndex;
                answers.put(answerKey, birthday);
                
                // Enable next button
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
            },
            year, month, day
        );
        
        // Set max date to today
        datePickerDialog.getDatePicker().setMaxDate(System.currentTimeMillis());
        datePickerDialog.show();
    }
    
    private void restoreAnswer(Question question) {
        String answerKey = "question_" + currentQuestionIndex;
        String answer = answers.get(answerKey);
        
        if (answer != null && !answer.isEmpty()) {
            switch (question.type) {
                case MUNICIPALITY:
                case BARANGAY:
                case SEX:
                case PREGNANCY:
                    restoreButtonAnswer(answer);
                    break;
                case BIRTHDAY:
                    restoreBirthdayAnswer(answer);
                    break;
                case WEIGHT:
                    weightInput.setText(answer);
            nextButton.setEnabled(true);
            nextButton.setBackgroundResource(R.drawable.button_next_active);
                    break;
                case HEIGHT:
                    heightInput.setText(answer);
                    nextButton.setEnabled(true);
                    nextButton.setBackgroundResource(R.drawable.button_next_active);
                    break;
            }
        }
    }
    
    private void restoreButtonAnswer(String answer) {
        for (int i = 0; i < optionsContainer.getChildCount(); i++) {
            View child = optionsContainer.getChildAt(i);
            if (child instanceof Button) {
                Button button = (Button) child;
                if (button.getText().toString().contains(answer) || answer.contains(button.getText().toString())) {
                    button.setBackgroundResource(R.drawable.option_button_selected);
                    button.setTextColor(getResources().getColor(android.R.color.white));
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
                    break;
                }
            }
        }
    }
    
    private void restoreBirthdayAnswer(String answer) {
        Log.d("NutritionalScreening", "Restoring birthday answer: " + answer);
        Log.d("NutritionalScreening", "Options container child count: " + optionsContainer.getChildCount());
        
        // Find existing birthday button first
        Button dateButton = null;
        for (int i = 0; i < optionsContainer.getChildCount(); i++) {
            View child = optionsContainer.getChildAt(i);
            if (child instanceof Button) {
                Button button = (Button) child;
                if (button.getText().toString().contains("Birthday") || button.getText().toString().contains("Select Birthday")) {
                    dateButton = button;
                    break;
                }
            }
        }
        
        if (dateButton != null) {
            // Update existing button
            int age = calculateAgeFromBirthday(answer);
            dateButton.setText("Birthday: " + answer + " (Age: " + age + ")");
            dateButton.setBackgroundResource(R.drawable.option_button_selected);
            dateButton.setTextColor(getResources().getColor(android.R.color.white));
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
            Log.d("NutritionalScreening", "Updated existing button with birthday: " + answer);
        } else {
            // Create new button if none exists
            Log.d("NutritionalScreening", "Creating new button with birthday: " + answer);
            Button newDateButton = createOptionButton("Birthday: " + answer + " (Age: " + calculateAgeFromBirthday(answer) + ")");
            newDateButton.setOnClickListener(v -> showDatePicker());
            newDateButton.setBackgroundResource(R.drawable.option_button_selected);
            newDateButton.setTextColor(getResources().getColor(android.R.color.white));
            optionsContainer.addView(newDateButton);
            nextButton.setEnabled(true);
            nextButton.setBackgroundResource(R.drawable.button_next_active);
            Log.d("NutritionalScreening", "Created new button with birthday: " + answer);
        }
    }
    
    private void nextQuestion() {
        if (!validateCurrentQuestion()) {
            return;
        }
        
        // Store current answer
        storeCurrentAnswer();
        
        // Move to next question
        currentQuestionIndex++;
        
        // Skip pregnancy question if not applicable
        if (currentQuestionIndex == 4 && !shouldShowPregnancyQuestion()) {
            currentQuestionIndex++;
        }
        
        showCurrentQuestion();
    }
    
    private void previousQuestion() {
        if (currentQuestionIndex > 0) {
            // Store current answer before going back
            storeCurrentAnswer();
            
            // Move to previous question
            currentQuestionIndex--;
            
            // Skip pregnancy question if not applicable when going back
            if (currentQuestionIndex == 4 && !shouldShowPregnancyQuestion()) {
                currentQuestionIndex--;
            }
            
            showCurrentQuestion();
        }
    }
    
    private boolean validateCurrentQuestion() {
        Question currentQuestion = questions.get(currentQuestionIndex);
        
        switch (currentQuestion.type) {
            case WEIGHT:
                return validateWeight();
            case HEIGHT:
                return validateHeight();
            default:
                String answerKey = "question_" + currentQuestionIndex;
                String answer = answers.get(answerKey);
                if (answer == null || answer.isEmpty()) {
                    Toast.makeText(this, "Please select an option", Toast.LENGTH_SHORT).show();
                    return false;
                }
                return true;
        }
    }
    
    private boolean validateWeight() {
        String weightText = weightInput.getText().toString().trim();
            if (weightText.isEmpty()) {
                showValidationError("Please enter your weight");
            resetWeightInputAppearance();
            return false;
            }
            
            try {
                double weight = Double.parseDouble(weightText);
                if (weight <= 0 || weight > 1000) {
                    showValidationError("Weight must be between 0.1 and 1000 kg");
                setWeightInputError();
                return false;
            }
            
            // Additional age-based validation
            String birthday = answers.get("question_3");
            if (birthday != null) {
                int age = calculateAgeFromBirthday(birthday);
                boolean isWeightValidForAge = true;
                
                if (age < 2 && weight < 3) {
                    isWeightValidForAge = false;
                } else if (age >= 2 && age < 5 && weight < 8) {
                    isWeightValidForAge = false;
                } else if (age >= 5 && age < 10 && weight < 15) {
                    isWeightValidForAge = false;
                } else if (age >= 10 && age < 15 && weight < 25) {
                    isWeightValidForAge = false;
                } else if (age >= 15 && weight < 30) {
                    isWeightValidForAge = false;
                }
                
                if (!isWeightValidForAge) {
                    showValidationError("Weight seems impossible for this age. Please check your input.");
                    setWeightInputError();
                    return false;
                }
            }
            
            // If we reach here, weight is valid
            resetWeightInputAppearance();
            return true;
            } catch (NumberFormatException e) {
                showValidationError("Please enter a valid weight number");
            setWeightInputError();
            return false;
        }
    }
    
    private void setWeightInputError() {
        weightInput.setTextColor(getResources().getColor(android.R.color.holo_red_dark));
        weightInput.setBackgroundResource(R.drawable.option_button_invalid);
        nextButton.setEnabled(false);
        nextButton.setBackgroundResource(R.drawable.button_next_inactive);
    }
    
    private void resetWeightInputAppearance() {
        weightInput.setTextColor(getResources().getColor(android.R.color.black));
        weightInput.setBackgroundResource(R.drawable.option_button_unselected);
        nextButton.setEnabled(true);
        nextButton.setBackgroundResource(R.drawable.button_next_active);
    }
        
    private boolean validateHeight() {
            String heightText = heightInput.getText().toString().trim();
            if (heightText.isEmpty()) {
                showValidationError("Please enter your height");
            resetHeightInputAppearance();
            return false;
            }
            
            try {
                double height = Double.parseDouble(heightText);
                if (height <= 0 || height > 300) {
                showValidationError("Height must be between 1 and 300 cm");
                setHeightInputError();
                return false;
            }
            
            // Additional age-based validation
            String birthday = answers.get("question_3");
            if (birthday != null) {
                int age = calculateAgeFromBirthday(birthday);
                boolean isHeightValidForAge = true;
                
                if (age < 2 && height < 30) {
                    isHeightValidForAge = false;
                } else if (age >= 2 && age < 5 && height < 50) {
                    isHeightValidForAge = false;
                } else if (age >= 5 && age < 10 && height < 80) {
                    isHeightValidForAge = false;
                } else if (age >= 10 && age < 15 && height < 120) {
                    isHeightValidForAge = false;
                } else if (age >= 15 && height < 140) {
                    isHeightValidForAge = false;
                }
                
                if (!isHeightValidForAge) {
                    showValidationError("Height seems impossible for this age. Please check your input.");
                    setHeightInputError();
                    return false;
                }
            }
            
            // If we reach here, height is valid
            resetHeightInputAppearance();
            return true;
            } catch (NumberFormatException e) {
                showValidationError("Please enter a valid height number");
            setHeightInputError();
            return false;
        }
    }
    
    private void setHeightInputError() {
        heightInput.setTextColor(getResources().getColor(android.R.color.holo_red_dark));
        heightInput.setBackgroundResource(R.drawable.option_button_invalid);
        nextButton.setEnabled(false);
        nextButton.setBackgroundResource(R.drawable.button_next_inactive);
    }
    
    private void resetHeightInputAppearance() {
        heightInput.setTextColor(getResources().getColor(android.R.color.black));
        heightInput.setBackgroundResource(R.drawable.option_button_unselected);
        nextButton.setEnabled(true);
        nextButton.setBackgroundResource(R.drawable.button_next_active);
    }
    
    private void showValidationError(String message) {
        Toast.makeText(this, message, Toast.LENGTH_LONG).show();
    }
    
    private void storeCurrentAnswer() {
        Question currentQuestion = questions.get(currentQuestionIndex);
        String answerKey = "question_" + currentQuestionIndex;
        
        switch (currentQuestion.type) {
            case WEIGHT:
                String weight = weightInput.getText().toString().trim();
                if (!weight.isEmpty()) {
                    answers.put(answerKey, weight);
                }
                    break;
            case HEIGHT:
                String height = heightInput.getText().toString().trim();
                if (!height.isEmpty()) {
                    answers.put(answerKey, height);
                }
                    break;
            // Other answers are already stored when options are selected
        }
    }
    
    private int calculateAgeFromBirthday(String birthday) {
        try {
            String[] parts = birthday.split("-");
                int year = Integer.parseInt(parts[0]);
                int month = Integer.parseInt(parts[1]) - 1; // Calendar months are 0-based
                int day = Integer.parseInt(parts[2]);
                
            Calendar birthDate = Calendar.getInstance();
            birthDate.set(year, month, day);
            
            Calendar today = Calendar.getInstance();
            int age = today.get(Calendar.YEAR) - birthDate.get(Calendar.YEAR);
            
            if (today.get(Calendar.DAY_OF_YEAR) < birthDate.get(Calendar.DAY_OF_YEAR)) {
                age--;
            }
            
            return age;
            } catch (Exception e) {
            return 0;
        }
    }
    
    private void completeScreening() {
        Log.d("NutritionalScreening", "=== COMPLETING SCREENING ===");
        Log.d("NutritionalScreening", "Current answers: " + answers.toString());
        
        // Save all answers first, then navigate only after successful save
        saveAnswers();
    }
    
    
    private void saveAnswers() {
        try {
            // Save to SharedPreferences for local storage
            android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
            android.content.SharedPreferences.Editor editor = prefs.edit();
            
            for (Map.Entry<String, String> entry : answers.entrySet()) {
                editor.putString("screening_" + entry.getKey(), entry.getValue());
            }
            
            editor.apply();
            
            // Save to community_users database
            saveToCommunityUsers();
            
        } catch (Exception e) {
            Log.e("NutritionalScreening", "Error saving answers: " + e.getMessage());
            Toast.makeText(this, "Error saving data. Please try again.", Toast.LENGTH_LONG).show();
        }
    }
    
    
    private void saveToCommunityUsers() {
        // Check if user is already logged in
        CommunityUserManager userManager = new CommunityUserManager(this);
        boolean isLoggedIn = userManager.isLoggedIn();
        String userEmail = userManager.getCurrentUserEmail();
        
        Log.d("NutritionalScreening", "=== SAVING SCREENING DATA ===");
        Log.d("NutritionalScreening", "User logged in: " + isLoggedIn);
        Log.d("NutritionalScreening", "User email: " + userEmail);
        
        if (isLoggedIn) {
            // User is logged in, update their screening data
            Log.d("NutritionalScreening", "User is logged in, updating screening data");
            updateUserScreeningData(userManager);
        } else {
            // User not logged in, try to register them with screening data
            Log.d("NutritionalScreening", "User not logged in, trying to register with screening data");
            registerNewUserWithScreeningData(userManager);
        }
    }
    
    private void updateUserScreeningData(CommunityUserManager userManager) {
        // Use the save_screening API endpoint for updating existing users
        String email = userManager.getCurrentUserEmail();
        if (email == null || email.isEmpty()) {
            Log.e("NutritionalScreening", "No user email found");
            return;
        }
        
        // Get user data from SharedPreferences
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        String userName = prefs.getString("current_user_name", "Unknown User");
        
        // Prepare screening data using the save_screening format
        String municipality = answers.get("question_0");
        String barangay = answers.get("question_1");
        String sex = answers.get("question_2");
        String birthDate = answers.get("question_3");
        String pregnancyStatus = getPregnancyStatus();
        String weight = answers.get("question_5");
        String height = answers.get("question_6");
        
        Log.d("NutritionalScreening", "Saving screening data using save_screening API");
        Log.d("NutritionalScreening", "Email: " + email);
        Log.d("NutritionalScreening", "Name: " + userName);
        Log.d("NutritionalScreening", "Municipality: " + municipality);
        Log.d("NutritionalScreening", "Barangay: " + barangay);
        Log.d("NutritionalScreening", "Sex: " + sex);
        Log.d("NutritionalScreening", "Birth Date: " + birthDate);
        Log.d("NutritionalScreening", "Pregnancy: " + pregnancyStatus);
        Log.d("NutritionalScreening", "Weight: " + weight);
        Log.d("NutritionalScreening", "Height: " + height);
        
        // Use the save_screening API endpoint
        makeScreeningApiRequest(email, userName, municipality, barangay, sex, birthDate, pregnancyStatus, weight, height);
    }
    
    private void makeScreeningApiRequest(String email, String name, String municipality, String barangay, 
                                       String sex, String birthday, String isPregnant, String weight, String height) {
        // Show loading dialog
        runOnUiThread(() -> {
            android.app.ProgressDialog progressDialog = new android.app.ProgressDialog(this);
            progressDialog.setMessage("Saving screening data...");
            progressDialog.setCancelable(false);
            progressDialog.show();
            
            // Store reference to dismiss later
            this.progressDialog = progressDialog;
        });
        
        new Thread(() -> {
            try {
                // Create JSON request data
                org.json.JSONObject requestData = new org.json.JSONObject();
                requestData.put("email", email);
                requestData.put("name", name);
                requestData.put("municipality", municipality);
                requestData.put("barangay", barangay);
                requestData.put("sex", sex);
                requestData.put("birthday", birthday);
                requestData.put("is_pregnant", isPregnant);
                requestData.put("weight", weight);
                requestData.put("height", height);
                
                // Make API request
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient.Builder()
                    .connectTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                    .readTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                    .writeTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                    .build();
                
                okhttp3.RequestBody body = okhttp3.RequestBody.create(
                    requestData.toString(), 
                    okhttp3.MediaType.parse("application/json; charset=utf-8")
                );
                
                okhttp3.Request request = new okhttp3.Request.Builder()
                    .url("https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=save_screening")
                    .post(body)
                    .addHeader("Content-Type", "application/json")
                    .addHeader("Accept", "application/json")
                    .build();
                
                try (okhttp3.Response response = client.newCall(request).execute()) {
                    Log.d("NutritionalScreening", "Screening API response code: " + response.code());
                    
                    if (response.isSuccessful() && response.body() != null) {
                        String responseBody = response.body().string();
                        Log.d("NutritionalScreening", "Screening API response: " + responseBody);
                        
                        org.json.JSONObject jsonResponse = new org.json.JSONObject(responseBody);
                        
                        if (jsonResponse.getBoolean("success")) {
                            Log.d("NutritionalScreening", "Screening data saved successfully: " + jsonResponse.optString("message", "Success"));
                            
                            // Register FCM token after successful screening completion
                            registerFCMTokenAfterScreening(email, barangay);
                            
                            runOnUiThread(() -> {
                                // Dismiss progress dialog
                                if (progressDialog != null && progressDialog.isShowing()) {
                                    progressDialog.dismiss();
                                }
                                Toast.makeText(NutritionalScreeningActivity.this, "Screening data saved successfully!", Toast.LENGTH_LONG).show();
                                // Add a small delay before navigation so user can see the success message
                                new android.os.Handler().postDelayed(() -> {
                                    navigateToMainActivity();
                                }, 1500); // 1.5 second delay
                            });
                        } else {
                            String errorMessage = jsonResponse.optString("message", "Unknown error");
                            Log.e("NutritionalScreening", "Failed to save screening data: " + errorMessage);
                            runOnUiThread(() -> {
                                // Dismiss progress dialog
                                if (progressDialog != null && progressDialog.isShowing()) {
                                    progressDialog.dismiss();
                                }
                                Toast.makeText(NutritionalScreeningActivity.this, "Failed to save screening data: " + errorMessage, Toast.LENGTH_LONG).show();
                            });
                        }
                    } else {
                        String errorBody = response.body() != null ? response.body().string() : "No error body";
                        Log.e("NutritionalScreening", "Screening API request failed: " + response.code() + " - " + response.message() + " - " + errorBody);
                        runOnUiThread(() -> {
                            // Dismiss progress dialog
                            if (progressDialog != null && progressDialog.isShowing()) {
                                progressDialog.dismiss();
                            }
                            Toast.makeText(NutritionalScreeningActivity.this, "Failed to save screening data: " + response.code(), Toast.LENGTH_LONG).show();
                        });
                    }
                }
                } catch (Exception e) {
                Log.e("NutritionalScreening", "Error making screening API request: " + e.getMessage());
                e.printStackTrace();
                    runOnUiThread(() -> {
                        // Dismiss progress dialog
                        if (progressDialog != null && progressDialog.isShowing()) {
                            progressDialog.dismiss();
                        }
                        Toast.makeText(NutritionalScreeningActivity.this, "Error saving screening data: " + e.getMessage(), Toast.LENGTH_LONG).show();
                    });
                }
            }).start();
    }
    
    
    private String getPregnancyStatus() {
        String pregnancyAnswer = answers.get("question_4");
        if (pregnancyAnswer == null) {
            return "Not Applicable";
        }
        return pregnancyAnswer.equals("Yes") ? "Yes" : "No";
    }
    
    private void registerNewUserWithScreeningData(CommunityUserManager userManager) {
        // Get user data from SharedPreferences
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        String userName = prefs.getString("current_user_name", "Screening User");
        String email = prefs.getString("current_user_email", "");
        String password = "screening_user"; // Default password for screening users
        
        if (email == null || email.isEmpty()) {
            Log.e("NutritionalScreening", "No email found for new user registration");
                        runOnUiThread(() -> {
                Toast.makeText(this, "Please log in to save screening data", Toast.LENGTH_LONG).show();
                        });
                        return;
                    }
                    
        // Prepare screening data
        String municipality = answers.get("question_0");
        String barangay = answers.get("question_1");
        String sex = answers.get("question_2");
        String birthDate = answers.get("question_3");
        String pregnancyStatus = getPregnancyStatus();
        String weight = answers.get("question_5");
        String height = answers.get("question_6");
        String muac = "0"; // Default MUAC value
        
        Log.d("NutritionalScreening", "Registering new user with screening data");
        
        // Use the original registerUser method
        userManager.registerUser(
            userName, email, password, barangay, municipality, 
            sex, birthDate, pregnancyStatus, weight, height, muac,
            new CommunityUserManager.RegisterCallback() {
                @Override
                public void onSuccess(String message) {
                    Log.d("NutritionalScreening", "New user registered successfully: " + message);
                    
                    // Register FCM token after successful new user registration with screening data
                    registerFCMTokenAfterScreening(email, barangay);
                    
                    runOnUiThread(() -> {
                        // Dismiss progress dialog
                        if (progressDialog != null && progressDialog.isShowing()) {
                            progressDialog.dismiss();
                        }
                        Toast.makeText(NutritionalScreeningActivity.this, "Screening data saved successfully!", Toast.LENGTH_LONG).show();
                        // Add a small delay before navigation so user can see the success message
                        new android.os.Handler().postDelayed(() -> {
                            navigateToMainActivity();
                        }, 1500); // 1.5 second delay
                    });
                }
                
                @Override
                public void onError(String error) {
                    Log.e("NutritionalScreening", "Failed to register new user: " + error);
                        runOnUiThread(() -> {
                            // Dismiss progress dialog
                            if (progressDialog != null && progressDialog.isShowing()) {
                                progressDialog.dismiss();
                            }
                            if (error.contains("already exists")) {
                                Toast.makeText(NutritionalScreeningActivity.this, "User already exists. Please log in to update your screening data.", Toast.LENGTH_LONG).show();
                        } else {
                            Toast.makeText(NutritionalScreeningActivity.this, "Failed to save screening data: " + error, Toast.LENGTH_LONG).show();
                        }
                        });
                }
            }
        );
                }
                
    private void showUserInfoDialog() {
        // For now, just show a message that user needs to be logged in
                runOnUiThread(() -> {
            Toast.makeText(this, "Please log in to save screening data", Toast.LENGTH_LONG).show();
        });
    }
    
    /**
     * Register FCM token after successful screening completion
     */
    private void registerFCMTokenAfterScreening(String userEmail, String userBarangay) {
        Log.d("NutritionalScreening", "Registering FCM token after screening completion for user: " + userEmail + " in " + userBarangay);
        
        if (fcmTokenManager != null) {
            fcmTokenManager.registerTokenAfterScreening(userEmail, userBarangay);
        } else {
            Log.e("NutritionalScreening", "FCMTokenManager is null, cannot register token");
        }
    }
    
    private void navigateToMainActivity() {
        // Navigate to main activity
        Intent intent = new Intent(this, MainActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
        finish();
    }
}
