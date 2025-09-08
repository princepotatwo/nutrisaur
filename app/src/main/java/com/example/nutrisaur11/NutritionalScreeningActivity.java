package com.example.nutrisaur11;

import android.app.DatePickerDialog;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.DatePicker;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import org.json.JSONObject;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Calendar;
import java.util.HashMap;
import java.util.Map;

public class NutritionalScreeningActivity extends AppCompatActivity {
    
    private int currentQuestion = 0;
    private Map<String, String> answers = new HashMap<>();
    private LinearLayout optionsContainer;
    private TextView questionText;
    private TextView progressText;
    private Button nextButton;
    private Button optionVeryOften;
    private Button optionFairlyOften;
    private Button optionSometimes;
    private Button optionAlmostNever;
    private Button optionNever;
    private EditText weightInput;
    private EditText heightInput;
    private EditText muacInput;
    
    // Community Nutritional Assessment Questions based on flowchart
    private String[] questions = {
        "Select your municipality",
        "Select your barangay", 
        "What is your sex?",
        "What is your age?",
        "Are you pregnant?",
        "What is your weight (kg)?",
        "What is your height (cm)?",
        "What is your MUAC (cm) - if available?"
    };
    
    private int totalQuestions = 8;
    private int currentQuestionIndex = 0;
    
    // Complete municipality data from dash.php
    private String[] municipalities = {
        "ABUCAY", "BAGAC", "CITY OF BALANGA", "DINALUPIHAN", "HERMOSA", 
        "LIMAY", "MARIVELES", "MORONG", "ORANI", "ORION", "PILAR", "SAMAL"
    };
    
    // Complete barangay data for each municipality (from dash.php)
    private String[] abucayBarangays = {
        "Bangkal", "Calaylayan (Pob.)", "Capitangan", "Gabon", "Laon (Pob.)", 
        "Mabatang", "Omboy", "Salian", "Wawa (Pob.)"
    };
    
    private String[] bagacBarangays = {
        "Bagumbayan (Pob.)", "Banawang", "Binuangan", "Binukawan", "Ibaba", 
        "Ibis", "Pag-asa (Wawa-Sibacan)", "Parang", "Paysawan", "Quinawan", 
        "San Antonio", "Saysain", "Tabing-Ilog (Pob.)", "Atilano L. Ricardo"
    };
    
    private String[] balangaBarangays = {
        "Bagumbayan", "Cabog-Cabog", "Munting Batangas (Cadre)", "Cataning", 
        "Central", "Cupang Proper", "Cupang West", "Dangcol (Bernabe)", "Ibayo", 
        "Malabia", "Poblacion", "Pto. Rivas Ibaba", "Pto. Rivas Itaas", 
        "San Jose", "Sibacan", "Camacho", "Talisay", "Tanato", "Tenejero", 
        "Tortugas", "Tuyo", "Bagong Silang", "Cupang North", "Doña Francisca", "Lote"
    };
    
    private String[] dinalupihanBarangays = {
        "Bangal", "Bonifacio (Pob.)", "Burgos (Pob.)", "Colo", "Daang Bago", 
        "Dalao", "Del Pilar (Pob.)", "Gen. Luna (Pob.)", "Gomez (Pob.)", 
        "Happy Valley", "Kataasan", "Layac", "Luacan", "Mabini Proper (Pob.)", 
        "Mabini Ext. (Pob.)", "Magsaysay", "Naparing", "New San Jose", 
        "Old San Jose", "Padre Dandan (Pob.)", "Pag-asa", "Pagalanggang", 
        "Pinulot", "Pita", "Rizal (Pob.)", "Roosevelt", "Roxas (Pob.)", 
        "Saguing", "San Benito", "San Isidro (Pob.)", "San Pablo (Bulate)", 
        "San Ramon", "San Simon", "Santo Niño", "Sapang Balas", 
        "Santa Isabel (Tabacan)", "Torres Bugauen (Pob.)", "Tucop", 
        "Zamora (Pob.)", "Aquino", "Bayan-bayanan", "Maligaya", "Payangan", 
        "Pentor", "Tubo-tubo", "Jose C. Payumo, Jr."
    };
    
    private String[] hermosaBarangays = {
        "A. Rivera (Pob.)", "Almacen", "Bacong", "Balsic", "Bamban", 
        "Burgos-Soliman (Pob.)", "Cataning (Pob.)", "Culis", "Daungan (Pob.)", 
        "Mabiga", "Mabuco", "Maite", "Mambog - Mandama", "Palihan", 
        "Pandatung", "Pulo", "Saba", "San Pedro (Pob.)", "Santo Cristo (Pob.)", 
        "Sumalo", "Tipo", "Judge Roman Cruz Sr. (Mandama)", "Sacrifice Valley"
    };
    
    private String[] limayBarangays = {
        "Alangan", "Kitang I", "Kitang 2 & Luz", "Lamao", "Landing", 
        "Poblacion", "Reformista", "Townsite", "Wawa", "Duale", 
        "San Francisco de Asis", "St. Francis II"
    };
    
    private String[] marivelesBarangays = {
        "Alas-asin", "Alion", "Batangas II", "Cabcaben", "Lucanin", 
        "Baseco Country (Nassco)", "Poblacion", "San Carlos", "San Isidro", 
        "Sisiman", "Balon-Anito", "Biaan", "Camaya", "Ipag", "Malaya", 
        "Maligaya", "Mt. View", "Townsite"
    };
    
    private String[] morongBarangays = {
        "Binaritan", "Mabayo", "Nagbalayong", "Poblacion", "Sabang"
    };
    
    private String[] oraniBarangays = {
        "Bagong Paraiso (Pob.)", "Balut (Pob.)", "Bayan (Pob.)", "Calero (Pob.)", 
        "Paking-Carbonero (Pob.)", "Centro II (Pob.)", "Dona", "Kaparangan", 
        "Masantol", "Mulawin", "Pag-asa", "Palihan (Pob.)", 
        "Pantalan Bago (Pob.)", "Pantalan Luma (Pob.)", "Parang Parang (Pob.)", 
        "Centro I (Pob.)", "Tala", "Tugatog", "Tagumpay", "Tenejero", 
        "Wawa (Pob.)", "Apollo", "Bagong Silang", "Balut (Pob.)", "Bayan (Pob.)", 
        "Calero (Pob.)", "Paking-Carbonero (Pob.)", "Centro II (Pob.)", "Dona", 
        "Kaparangan", "Masantol", "Mulawin", "Pag-asa", "Palihan (Pob.)", 
        "Pantalan Bago (Pob.)", "Pantalan Luma (Pob.)", "Parang Parang (Pob.)", 
        "Centro I (Pob.)", "Tala", "Tugatog", "Tagumpay", "Tenejero", "Wawa (Pob.)"
    };
    
    private String[] orionBarangays = {
        "Bagumbayan", "Bantan", "Bilolo", "Calungusan", "Camachile", 
        "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente", 
        "Wawa", "Balagtas", "Balut", "Bataan", "Bilolo", "Calungusan", 
        "Camachile", "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente"
    };
    
    private String[] pilarBarangays = {
        "Bagumbayan", "Bantan", "Bilolo", "Calungusan", "Camachile", 
        "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente", 
        "Wawa", "Balagtas", "Balut", "Bataan", "Bilolo", "Calungusan", 
        "Camachile", "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente"
    };
    
    private String[] samalBarangays = {
        "Bagumbayan", "Bantan", "Bilolo", "Calungusan", "Camachile", 
        "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente", 
        "Wawa", "Balagtas", "Balut", "Bataan", "Bilolo", "Calungusan", 
        "Camachile", "Kapunitan", "Lati", "Puting Bato", "Sabatan", "San Vicente"
    };
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_nutritional_screening);
        
        // Initialize views
        optionsContainer = findViewById(R.id.options_container);
        questionText = findViewById(R.id.question_text);
        progressText = findViewById(R.id.progress_text);
        nextButton = findViewById(R.id.next_button);
        optionVeryOften = findViewById(R.id.option_very_often);
        optionFairlyOften = findViewById(R.id.option_fairly_often);
        optionSometimes = findViewById(R.id.option_sometimes);
        optionAlmostNever = findViewById(R.id.option_almost_never);
        optionNever = findViewById(R.id.option_never);
        weightInput = findViewById(R.id.weight_input);
        heightInput = findViewById(R.id.height_input);
        muacInput = findViewById(R.id.muac_input);
        
        // Set up close button
        findViewById(R.id.close_button).setOnClickListener(v -> finish());
        
        // Set up next button
        nextButton.setOnClickListener(v -> nextQuestion());
        
        // Set up option buttons
        setupOptionButtons();
        
        // Show first question
        showQuestion(0);
    }
    
    private void setupOptionButtons() {
        optionVeryOften.setOnClickListener(v -> selectOption(optionVeryOften, "Very often"));
        optionFairlyOften.setOnClickListener(v -> selectOption(optionFairlyOften, "Fairly often"));
        optionSometimes.setOnClickListener(v -> selectOption(optionSometimes, "Sometimes"));
        optionAlmostNever.setOnClickListener(v -> selectOption(optionAlmostNever, "Almost never"));
        optionNever.setOnClickListener(v -> selectOption(optionNever, "Never"));
    }
    
    private void showQuestion(int questionIndex) {
        currentQuestionIndex = questionIndex;
        questionText.setText(questions[questionIndex]);
        progressText.setText((questionIndex + 1) + "/" + totalQuestions);
        
        // Reset all options to unselected state
        resetAllOptions();
        
        // Disable next button initially
        nextButton.setEnabled(false);
        nextButton.setBackgroundResource(R.drawable.button_next_inactive);
        
        // Show appropriate question UI based on question type
        switch (questionIndex) {
            case 0: showMunicipalityQuestion(); break;
            case 1: showBarangayQuestion(); break;
            case 2: showSexQuestion(); break;
            case 3: showAgeQuestion(); break;
            case 4: showPregnancyQuestion(); break;
            case 5: showWeightQuestion(); break;
            case 6: showHeightQuestion(); break;
            case 7: showMUACQuestion(); break;
        }
    }
    
    private void resetAllOptions() {
        // Reset option buttons
        optionVeryOften.setBackgroundResource(R.drawable.option_button_unselected);
        optionVeryOften.setTextColor(getResources().getColor(android.R.color.black));
        optionFairlyOften.setBackgroundResource(R.drawable.option_button_unselected);
        optionFairlyOften.setTextColor(getResources().getColor(android.R.color.black));
        optionSometimes.setBackgroundResource(R.drawable.option_button_unselected);
        optionSometimes.setTextColor(getResources().getColor(android.R.color.black));
        optionAlmostNever.setBackgroundResource(R.drawable.option_button_unselected);
        optionAlmostNever.setTextColor(getResources().getColor(android.R.color.black));
        optionNever.setBackgroundResource(R.drawable.option_button_unselected);
        optionNever.setTextColor(getResources().getColor(android.R.color.black));
        
        // Hide input fields
        weightInput.setVisibility(View.GONE);
        heightInput.setVisibility(View.GONE);
        muacInput.setVisibility(View.GONE);
    }
    
    private void selectOption(Button selected, String value) {
        // Reset all options first
        resetAllOptions();
        
        // Set selected option
        selected.setBackgroundResource(R.drawable.option_button_selected);
        selected.setTextColor(getResources().getColor(android.R.color.white));
        
        // Enable next button
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
        
        // Store answer
        answers.put("question_" + currentQuestionIndex, value);
    }
    
    private void showMunicipalityQuestion() {
        // Clear existing options
        optionsContainer.removeAllViews();
        
        // Create buttons for all municipalities
        for (int i = 0; i < municipalities.length; i++) {
            Button municipalityButton = createOptionButton(municipalities[i]);
            final String municipality = municipalities[i];
            
            municipalityButton.setOnClickListener(v -> {
                // Reset all buttons
                resetAllMunicipalityButtons();
                
                // Set selected button
                municipalityButton.setBackgroundResource(R.drawable.option_button_selected);
                municipalityButton.setTextColor(getResources().getColor(android.R.color.white));
                
                // Store answer
                answers.put("question_" + currentQuestionIndex, municipality);
                
                // Enable next button
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
            });
            
            optionsContainer.addView(municipalityButton);
        }
    }
    
    private Button createOptionButton(String text) {
        Button button = new Button(this);
        button.setText(text);
        button.setBackgroundResource(R.drawable.option_button_unselected);
        button.setTextColor(getResources().getColor(android.R.color.black));
        button.setTextSize(16);
        button.setPadding(0, 20, 0, 20);
        button.setElevation(0);
        
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT, 
            LinearLayout.LayoutParams.WRAP_CONTENT
        );
        params.setMargins(0, 12, 0, 12);
        button.setLayoutParams(params);
        return button;
    }
    
    private void resetAllMunicipalityButtons() {
        for (int i = 0; i < optionsContainer.getChildCount(); i++) {
            View child = optionsContainer.getChildAt(i);
            if (child instanceof Button) {
                Button button = (Button) child;
                button.setBackgroundResource(R.drawable.option_button_unselected);
                button.setTextColor(getResources().getColor(android.R.color.black));
            }
        }
    }
    
    private void showBarangayQuestion() {
        // Get selected municipality
        String selectedMunicipality = answers.get("question_0");
        String[] barangays = getBarangaysForMunicipality(selectedMunicipality);
        
        // Clear existing options
        optionsContainer.removeAllViews();
        
        if (barangays.length == 0) {
            // No barangays available
            Button noBarangayButton = createOptionButton("No barangays available");
            noBarangayButton.setEnabled(false);
            optionsContainer.addView(noBarangayButton);
            return;
        }
        
        // Create buttons for all barangays
        for (int i = 0; i < barangays.length; i++) {
            Button barangayButton = createOptionButton(barangays[i]);
            final String barangay = barangays[i];
            
            barangayButton.setOnClickListener(v -> {
                // Reset all buttons
                resetAllMunicipalityButtons();
                
                // Set selected button
                barangayButton.setBackgroundResource(R.drawable.option_button_selected);
                barangayButton.setTextColor(getResources().getColor(android.R.color.white));
                
                // Store answer
                answers.put("question_" + currentQuestionIndex, barangay);
                
                // Enable next button
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
            });
            
            optionsContainer.addView(barangayButton);
        }
    }
    
    private String[] getBarangaysForMunicipality(String municipality) {
        if (municipality == null) return new String[0];
        
        switch (municipality) {
            case "CITY OF BALANGA": return balangaBarangays;
            case "DINALUPIHAN": return dinalupihanBarangays;
            case "HERMOSA": return hermosaBarangays;
            case "LIMAY": return limayBarangays;
            case "MARIVELES": return marivelesBarangays;
            case "MORONG": return morongBarangays;
            case "ORANI": return oraniBarangays;
            case "ORION": return orionBarangays;
            case "PILAR": return pilarBarangays;
            case "SAMAL": return samalBarangays;
            case "ABUCAY": return abucayBarangays;
            case "BAGAC": return bagacBarangays;
            default: return new String[0];
        }
    }
    
    private void showSexQuestion() {
        // Clear existing options
        optionsContainer.removeAllViews();
        
        // Create buttons for sex options
        String[] sexOptions = {"Male", "Female", "Other"};
        
        for (int i = 0; i < sexOptions.length; i++) {
            Button sexButton = createOptionButton(sexOptions[i]);
            final String sex = sexOptions[i];
            
            sexButton.setOnClickListener(v -> {
                // Reset all buttons
                resetAllMunicipalityButtons();
                
                // Set selected button
                sexButton.setBackgroundResource(R.drawable.option_button_selected);
                sexButton.setTextColor(getResources().getColor(android.R.color.white));
                
                // Store answer
                answers.put("question_" + currentQuestionIndex, sex);
                
                // Enable next button
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
            });
            
            optionsContainer.addView(sexButton);
        }
    }
    
    private void showAgeQuestion() {
        // Clear existing options
        optionsContainer.removeAllViews();
        
        // Create a button that opens date picker
        Button datePickerButton = createOptionButton("Select your birthday");
        datePickerButton.setOnClickListener(v -> showDatePicker());
        
        optionsContainer.addView(datePickerButton);
    }
    
    private void showDatePicker() {
        Calendar calendar = Calendar.getInstance();
        new DatePickerDialog(this, (view, year, month, dayOfMonth) -> {
            String date = String.format("%04d-%02d-%02d", year, month + 1, dayOfMonth);
            answers.put("question_" + currentQuestionIndex, date);
            
            // Calculate age and update button text
            int age = calculateAge(year, month, dayOfMonth);
            Button dateButton = (Button) optionsContainer.getChildAt(0);
            dateButton.setText("Birthday: " + date + " (Age: " + age + ")");
            dateButton.setBackgroundResource(R.drawable.option_button_selected);
            dateButton.setTextColor(getResources().getColor(android.R.color.white));
            
            // Enable next button
            nextButton.setEnabled(true);
            nextButton.setBackgroundResource(R.drawable.button_next_active);
        }, calendar.get(Calendar.YEAR), calendar.get(Calendar.MONTH), calendar.get(Calendar.DAY_OF_MONTH)).show();
    }
    
    private int calculateAge(int year, int month, int day) {
        Calendar today = Calendar.getInstance();
        Calendar birthDate = Calendar.getInstance();
        birthDate.set(year, month, day);
        
        int age = today.get(Calendar.YEAR) - birthDate.get(Calendar.YEAR);
        if (today.get(Calendar.DAY_OF_YEAR) < birthDate.get(Calendar.DAY_OF_YEAR)) {
            age--;
        }
        return age;
    }
    
    private void showPregnancyQuestion() {
        // Check if user is female and of childbearing age
        String sex = answers.get("question_2");
        String ageRange = answers.get("question_3");
        
        if (!"Female".equals(sex) || "Under 18".equals(ageRange) || "Over 50".equals(ageRange)) {
            // Skip pregnancy question - go to next question
            nextQuestion();
            return;
        }
        
        // Clear existing options
        optionsContainer.removeAllViews();
        
        // Create buttons for pregnancy options
        String[] pregnancyOptions = {"Yes", "No"};
        
        for (int i = 0; i < pregnancyOptions.length; i++) {
            Button pregnancyButton = createOptionButton(pregnancyOptions[i]);
            final String pregnancy = pregnancyOptions[i];
            
            pregnancyButton.setOnClickListener(v -> {
                // Reset all buttons
                resetAllMunicipalityButtons();
                
                // Set selected button
                pregnancyButton.setBackgroundResource(R.drawable.option_button_selected);
                pregnancyButton.setTextColor(getResources().getColor(android.R.color.white));
                
                // Store answer
                answers.put("question_" + currentQuestionIndex, pregnancy);
                
                // Enable next button
                nextButton.setEnabled(true);
                nextButton.setBackgroundResource(R.drawable.button_next_active);
            });
            
            optionsContainer.addView(pregnancyButton);
        }
    }
    
    private void showWeightQuestion() {
        // Clear all dynamically added views from options container
        optionsContainer.removeAllViews();
        
        // Hide all option buttons
        optionVeryOften.setVisibility(View.GONE);
        optionFairlyOften.setVisibility(View.GONE);
        optionSometimes.setVisibility(View.GONE);
        optionAlmostNever.setVisibility(View.GONE);
        optionNever.setVisibility(View.GONE);
        
        // Hide height input field
        heightInput.setVisibility(View.GONE);
        
        // Show weight input field
        weightInput.setVisibility(View.VISIBLE);
        weightInput.setText("");
        weightInput.setHint("Enter Weight (kg)");
        weightInput.setBackgroundResource(R.drawable.option_button_unselected);
        weightInput.setTextColor(getResources().getColor(android.R.color.black));
        
        // Clear any existing text watchers
        weightInput.removeTextChangedListener(null);
        
        // Set up text change listener
        weightInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            
            @Override
            public void afterTextChanged(android.text.Editable s) {
                String weight = s.toString().trim();
                if (!weight.isEmpty()) {
                    // Change background to selected state
                    weightInput.setBackgroundResource(R.drawable.option_button_selected);
                    weightInput.setTextColor(getResources().getColor(android.R.color.white));
                    
                    // Store answer
                    answers.put("question_" + currentQuestionIndex, weight);
                    
                    // Enable next button
                    nextButton.setEnabled(true);
                    nextButton.setBackgroundResource(R.drawable.button_next_active);
                } else {
                    // Reset to unselected state
                    weightInput.setBackgroundResource(R.drawable.option_button_unselected);
                    weightInput.setTextColor(getResources().getColor(android.R.color.black));
                    
                    // Disable next button
                    nextButton.setEnabled(false);
                    nextButton.setBackgroundResource(R.drawable.button_next_inactive);
                }
            }
        });
    }
    
    private void showHeightQuestion() {
        // Clear all dynamically added views from options container
        optionsContainer.removeAllViews();
        
        // Hide all option buttons
        optionVeryOften.setVisibility(View.GONE);
        optionFairlyOften.setVisibility(View.GONE);
        optionSometimes.setVisibility(View.GONE);
        optionAlmostNever.setVisibility(View.GONE);
        optionNever.setVisibility(View.GONE);
        
        // Hide weight input field
        weightInput.setVisibility(View.GONE);
        
        // Show height input field
        heightInput.setVisibility(View.VISIBLE);
        heightInput.setText("");
        heightInput.setHint("Enter Height (cm)");
        heightInput.setBackgroundResource(R.drawable.option_button_unselected);
        heightInput.setTextColor(getResources().getColor(android.R.color.black));
        
        // Clear any existing text watchers
        heightInput.removeTextChangedListener(null);
        
        // Set up text change listener
        heightInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            
            @Override
            public void afterTextChanged(android.text.Editable s) {
                String height = s.toString().trim();
                if (!height.isEmpty()) {
                    // Change background to selected state
                    heightInput.setBackgroundResource(R.drawable.option_button_selected);
                    heightInput.setTextColor(getResources().getColor(android.R.color.white));
                    
                    // Store answer
                    answers.put("question_" + currentQuestionIndex, height);
                    
                    // Enable next button
                    nextButton.setEnabled(true);
                    nextButton.setBackgroundResource(R.drawable.button_next_active);
                } else {
                    // Reset to unselected state
                    heightInput.setBackgroundResource(R.drawable.option_button_unselected);
                    heightInput.setTextColor(getResources().getColor(android.R.color.black));
                    
                    // Disable next button
                    nextButton.setEnabled(false);
                    nextButton.setBackgroundResource(R.drawable.button_next_inactive);
                }
            }
        });
    }
    
    private void showMUACQuestion() {
        // Clear all dynamically added views from options container
        optionsContainer.removeAllViews();
        
        // Hide all option buttons
        optionVeryOften.setVisibility(View.GONE);
        optionFairlyOften.setVisibility(View.GONE);
        optionSometimes.setVisibility(View.GONE);
        optionAlmostNever.setVisibility(View.GONE);
        optionNever.setVisibility(View.GONE);
        
        // Hide other input fields
        weightInput.setVisibility(View.GONE);
        heightInput.setVisibility(View.GONE);
        
        // Show MUAC input field
        muacInput.setVisibility(View.VISIBLE);
        muacInput.setText("");
        muacInput.setHint("Enter MUAC (cm)");
        muacInput.setBackgroundResource(R.drawable.option_button_unselected);
        muacInput.setTextColor(getResources().getColor(android.R.color.black));
        
        // Clear any existing text watchers
        muacInput.removeTextChangedListener(null);
        
        // Set up text change listener
        muacInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            
            @Override
            public void afterTextChanged(android.text.Editable s) {
                String muac = s.toString().trim();
                if (!muac.isEmpty()) {
                    // Change background to selected state
                    muacInput.setBackgroundResource(R.drawable.option_button_selected);
                    muacInput.setTextColor(getResources().getColor(android.R.color.white));
                    
                    // Store answer
                    answers.put("question_" + currentQuestionIndex, muac);
                    
                    // Enable next button
        nextButton.setEnabled(true);
        nextButton.setBackgroundResource(R.drawable.button_next_active);
                } else {
                    // Reset to unselected state
                    muacInput.setBackgroundResource(R.drawable.option_button_unselected);
                    muacInput.setTextColor(getResources().getColor(android.R.color.black));
                    
                    // Disable next button
                    nextButton.setEnabled(false);
                    nextButton.setBackgroundResource(R.drawable.button_next_inactive);
                }
            }
        });
    }
    
    
    
    
    
    private void nextQuestion() {
        if (currentQuestionIndex < totalQuestions - 1) {
            showQuestion(currentQuestionIndex + 1);
        } else {
            // Save answers and complete screening
            saveAnswers();
            Toast.makeText(this, "Screening completed!", Toast.LENGTH_SHORT).show();
            finish();
        }
    }
    
    
    private void saveAnswers() {
        // Save to SharedPreferences for local storage
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        
        for (Map.Entry<String, String> entry : answers.entrySet()) {
            editor.putString("screening_" + entry.getKey(), entry.getValue());
        }
        
        editor.apply();
        
        // Save to community_users database
        saveToCommunityUsers();
    }
    
    private void saveToCommunityUsers() {
        // Check if user is already logged in
        CommunityUserManager userManager = new CommunityUserManager(this);
        if (userManager.isLoggedIn()) {
            // User is logged in, update their screening data
            updateUserScreeningData();
        } else {
            // User not logged in, show dialog to collect name, email, and password
            showUserInfoDialog();
        }
    }
    
    private void updateUserScreeningData() {
        CommunityUserManager userManager = new CommunityUserManager(this);
        String email = userManager.getCurrentUserEmail();
        
        if (email != null) {
            // Update existing user with screening data
            new Thread(() -> {
                try {
                    JSONObject screeningData = new JSONObject();
                    screeningData.put("email", email);
                    screeningData.put("municipality", answers.get("question_0"));
                    screeningData.put("barangay", answers.get("question_1"));
                    screeningData.put("sex", answers.get("question_2"));
                    screeningData.put("birthday", answers.get("question_3"));
                    screeningData.put("is_pregnant", answers.get("question_4"));
                    screeningData.put("weight", answers.get("question_5"));
                    screeningData.put("height", answers.get("question_6"));
                    screeningData.put("muac", answers.get("question_7"));
                    
                    // Send to API
                    sendScreeningDataToAPI(screeningData);
                    
                } catch (Exception e) {
                    Log.e("NutritionalScreening", "Error updating screening data: " + e.getMessage());
                    runOnUiThread(() -> {
                        Toast.makeText(this, "Error updating data", Toast.LENGTH_SHORT).show();
                    });
                }
            }).start();
        }
    }
    
    private void showUserInfoDialog() {
        android.app.AlertDialog.Builder builder = new android.app.AlertDialog.Builder(this);
        builder.setTitle("Complete Your Profile");
        builder.setMessage("Please provide your information to save your screening results:");
        
        // Create input fields
        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        layout.setPadding(50, 20, 50, 20);
        
        EditText nameInput = new EditText(this);
        nameInput.setHint("Full Name");
        nameInput.setInputType(android.text.InputType.TYPE_CLASS_TEXT | android.text.InputType.TYPE_TEXT_FLAG_CAP_WORDS);
        layout.addView(nameInput);
        
        EditText emailInput = new EditText(this);
        emailInput.setHint("Email Address");
        emailInput.setInputType(android.text.InputType.TYPE_CLASS_TEXT | android.text.InputType.TYPE_TEXT_VARIATION_EMAIL_ADDRESS);
        layout.addView(emailInput);
        
        EditText passwordInput = new EditText(this);
        passwordInput.setHint("Password");
        passwordInput.setInputType(android.text.InputType.TYPE_CLASS_TEXT | android.text.InputType.TYPE_TEXT_VARIATION_PASSWORD);
        layout.addView(passwordInput);
        
        builder.setView(layout);
        
        builder.setPositiveButton("Save Screening", (dialog, which) -> {
            String name = nameInput.getText().toString().trim();
            String email = emailInput.getText().toString().trim();
            String password = passwordInput.getText().toString().trim();
            
            if (name.isEmpty() || email.isEmpty() || password.isEmpty()) {
                Toast.makeText(this, "Please fill in all fields", Toast.LENGTH_SHORT).show();
                showUserInfoDialog(); // Show dialog again
                return;
            }
            
            // Prepare data for API
            try {
                JSONObject screeningData = new JSONObject();
                screeningData.put("name", name);
                screeningData.put("email", email);
                screeningData.put("password", password);
                screeningData.put("municipality", answers.get("question_0"));
                screeningData.put("barangay", answers.get("question_1"));
                screeningData.put("sex", answers.get("question_2"));
                screeningData.put("birthday", answers.get("question_3"));
                screeningData.put("is_pregnant", answers.get("question_4"));
                screeningData.put("weight", answers.get("question_5"));
                screeningData.put("height", answers.get("question_6"));
                screeningData.put("muac", answers.get("question_7"));
                
                // Send to API
                sendScreeningDataToAPI(screeningData);
                
            } catch (Exception e) {
                Log.e("NutritionalScreening", "Error preparing screening data: " + e.getMessage());
                Toast.makeText(this, "Error preparing data", Toast.LENGTH_SHORT).show();
            }
        });
        
        builder.setNegativeButton("Cancel", (dialog, which) -> {
            Toast.makeText(this, "Screening data not saved", Toast.LENGTH_SHORT).show();
        });
        
        builder.show();
    }
    
    private void sendScreeningDataToAPI(JSONObject data) {
        new Thread(() -> {
            try {
                String url = "https://nutrisaur-production.up.railway.app/community_users_simple_api.php?action=save_screening";
                
                HttpURLConnection connection = (HttpURLConnection) new URL(url).openConnection();
                connection.setRequestMethod("POST");
                connection.setRequestProperty("Content-Type", "application/json");
                connection.setDoOutput(true);
                
                // Send data
                OutputStreamWriter writer = new OutputStreamWriter(connection.getOutputStream());
                writer.write(data.toString());
                writer.flush();
                writer.close();
                
                // Get response
                int responseCode = connection.getResponseCode();
                if (responseCode == HttpURLConnection.HTTP_OK) {
                    BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    // Parse response
                    JSONObject responseJson = new JSONObject(response.toString());
                    if (responseJson.getBoolean("success")) {
                        runOnUiThread(() -> {
                            Toast.makeText(this, "Screening data saved successfully!", Toast.LENGTH_LONG).show();
                        });
                    } else {
                        runOnUiThread(() -> {
                            Toast.makeText(this, "Failed to save screening data", Toast.LENGTH_SHORT).show();
                        });
                    }
                } else {
                    runOnUiThread(() -> {
                        Toast.makeText(this, "Error saving data to server", Toast.LENGTH_SHORT).show();
                    });
                }
                
            } catch (Exception e) {
                Log.e("NutritionalScreening", "Error sending data to API: " + e.getMessage());
                runOnUiThread(() -> {
                    Toast.makeText(this, "Error connecting to server", Toast.LENGTH_SHORT).show();
                });
            }
        }).start();
    }
    
    private int getUserIdFromEmail(String email) {
        // This is a placeholder - you might need to implement a method to get user_id from email
        // For now, return 0 to indicate no linked user
        return 0;
    }
}
