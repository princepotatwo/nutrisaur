package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.util.Log;
import android.view.View;
import android.widget.*;
import android.app.DatePickerDialog;
import android.content.Context;
import android.app.AlertDialog;

import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import com.example.nutrisaur11.ScreeningResultStore;
import com.example.nutrisaur11.Constants;

import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;
import java.util.Arrays;
import java.util.Collections;
import java.util.Date;
import java.util.Calendar;
import java.util.Locale;
import org.json.JSONObject;
import org.json.JSONArray;
import java.text.SimpleDateFormat;
import java.util.Locale;
import java.util.concurrent.CompletableFuture;

public class ScreeningFormActivity extends AppCompatActivity {

    // Question flow tracking - Updated for 7 sections
    private int currentQuestion = 0;
    private static final int TOTAL_QUESTIONS = 7; // 7 sections of Decision Tree Assessment
    
    // Question cards - each section gets its own card
    private View questionCards[];
    
    // Navigation buttons
    private Button prevButton, nextButton;
    
    // Section 1: Basic Information
    private Spinner locationSpinner;
    private Button birthdatePickerBtn;
    private String selectedBirthdate = "";
    private int calculatedAge = 0;
    // Sex and pregnancy selection now use buttons instead of radio groups
    private String selectedMunicipality = "";
    private String selectedBarangay = "";
    private String selectedSex = "";
    private String selectedPregnant = "";
    
    // Section 2: Anthropometric Assessment
    private EditText weightInput;
    private EditText heightInput;
    private TextView bmiResult;
    private TextView bmiCategory;
    
    // Section 3: Meal Assessment (24-Hour Recall)
    // Food checkboxes for meal assessment
    private CheckBox foodCarbs, foodProtein, foodVeggiesFruits, foodDairy;
    
    // Section 4: Family History
    private CheckBox diabetesCheckBox;
    private CheckBox hypertensionCheckBox;
    private CheckBox heartDiseaseCheckBox;
    private CheckBox kidneyDiseaseCheckBox;
    private CheckBox tuberculosisCheckBox;
    private CheckBox obesityCheckBox;
    private CheckBox malnutritionCheckBox;
    private EditText otherConditionInput;
    private CheckBox noneCheckBox;
    
    // Section 5: Lifestyle
    private RadioGroup lifestyleRadioGroup;
    private EditText otherLifestyleInput;
    private String selectedLifestyle = "";
    
    // Section 6: Immunization (Children ≤ 12 years old)
    // Immunization checkboxes
    private CheckBox immBcg, immDpt, immPolio, immMeasles, immHepatitis, immVitaminA;
    
    // Section 7: Final Assessment (System Generated)
    private TextView finalAssessmentText;
    private TextView riskLevelText;
    private TextView recommendationText;
    private TextView interventionText;
    
    // Bataan Municipalities and Barangays
    private static final String[] BATAAN_MUNICIPALITIES = {
        "ABUCAY", "BAGAC", "CITY OF BALANGA (Capital)", "DINALUPIHAN", 
        "HERMOSA", "LIMAY", "MARIVELES", "MORONG", "ORANI", "ORION", "PILAR", "SAMAL"
    };
    
    private static final Map<String, String[]> MUNICIPALITY_BARANGAYS = new HashMap<>();
    static {
        MUNICIPALITY_BARANGAYS.put("ABUCAY", new String[]{
            "Bangkal", "Calaylayan (Pob.)", "Capitangan", "Gabon", "Laon (Pob.)", 
            "Mabatang", "Omboy", "Salian", "Wawa (Pob.)"
        });
        MUNICIPALITY_BARANGAYS.put("BAGAC", new String[]{
            "Bagumbayan (Pob.)", "Banawang", "Binuangan", "Binukawan", "Ibaba", 
            "Ibis", "Pag-asa (Wawa-Sibacan)", "Parang", "Paysawan", "Quinawan", 
            "San Antonio", "Saysain", "Tabing-Ilog (Pob.)", "Atilano L. Ricardo"
        });
        MUNICIPALITY_BARANGAYS.put("CITY OF BALANGA (Capital)", new String[]{
            "Bagumbayan", "Cabog-Cabog", "Munting Batangas (Cadre)", "Cataning", 
            "Central", "Cupang Proper", "Cupang West", "Dangcol (Bernabe)", "Ibayo", 
            "Malabia", "Poblacion", "Pto. Rivas Ibaba", "Pto. Rivas Itaas", "San Jose", 
            "Sibacan", "Camacho", "Talisay", "Tanato", "Tenejero", "Tortugas", "Tuyo", 
            "Bagong Silang", "Cupang North", "Doña Francisca", "Lote"
        });
        // Add other municipalities...
    };

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_comprehensive_screening_form);
        ScreeningResultStore.init(this);
        initializeViews();
        setupClickListeners();
        initializeButtonStates();
        
        // Handle test mode - pre-fill with demo data
        handleTestMode();
        
        showQuestion(0);
    }

    private void initializeViews() {
        // Navigation buttons
        prevButton = findViewById(R.id.prev_button);
        nextButton = findViewById(R.id.next_button);
        
        // Initialize question cards array
        questionCards = new View[TOTAL_QUESTIONS];
        questionCards[0] = findViewById(R.id.section_1_card);  // Basic Information
        questionCards[1] = findViewById(R.id.section_2_card);  // Anthropometric Assessment
        questionCards[2] = findViewById(R.id.section_3_card);  // Meal Assessment
        questionCards[3] = findViewById(R.id.section_4_card);  // Family History
        questionCards[4] = findViewById(R.id.section_5_card);  // Lifestyle
        questionCards[5] = findViewById(R.id.section_6_card);  // Immunization
        questionCards[6] = findViewById(R.id.section_7_card);  // Final Assessment
        
        // Section 1: Basic Information
        locationSpinner = findViewById(R.id.location_spinner);
        birthdatePickerBtn = findViewById(R.id.birthdate_picker_btn);
        // Sex and pregnancy selection now use buttons instead of radio groups
        
        // Section 2: Anthropometric Assessment
        weightInput = findViewById(R.id.weight_input);
        heightInput = findViewById(R.id.height_input);
        bmiResult = findViewById(R.id.bmi_result);
        bmiCategory = findViewById(R.id.bmi_category);
        
        // Section 3: Meal Assessment
        // Food checkboxes
        foodCarbs = findViewById(R.id.food_carbs);
        foodProtein = findViewById(R.id.food_protein);
        foodVeggiesFruits = findViewById(R.id.food_veggies_fruits);
        foodDairy = findViewById(R.id.food_dairy);
        
        // Section 4: Family History
        diabetesCheckBox = findViewById(R.id.diabetes_checkbox);
        hypertensionCheckBox = findViewById(R.id.hypertension_checkbox);
        heartDiseaseCheckBox = findViewById(R.id.heart_disease_checkbox);
        kidneyDiseaseCheckBox = findViewById(R.id.kidney_disease_checkbox);
        tuberculosisCheckBox = findViewById(R.id.tuberculosis_checkbox);
        obesityCheckBox = findViewById(R.id.obesity_checkbox);
        malnutritionCheckBox = findViewById(R.id.malnutrition_checkbox);
        otherConditionInput = findViewById(R.id.other_condition_input);
        noneCheckBox = findViewById(R.id.none_checkbox);
        
        // Section 5: Lifestyle
        lifestyleRadioGroup = findViewById(R.id.lifestyle_radio_group);
        otherLifestyleInput = findViewById(R.id.other_lifestyle_input);
        
        // Section 6: Immunization
        // Immunization checkboxes
        immBcg = findViewById(R.id.imm_bcg);
        immDpt = findViewById(R.id.imm_dpt);
        immPolio = findViewById(R.id.imm_polio);
        immMeasles = findViewById(R.id.imm_measles);
        immHepatitis = findViewById(R.id.imm_hepatitis);
        immVitaminA = findViewById(R.id.imm_vitamin_a);
        
        // Section 7: Final Assessment
        finalAssessmentText = findViewById(R.id.final_assessment_text);
        riskLevelText = findViewById(R.id.risk_level_text);
        recommendationText = findViewById(R.id.recommendation_text);
        interventionText = findViewById(R.id.intervention_text);
        
        // Setup spinners
        setupLocationSpinner();
        
        // Setup birthdate picker
        birthdatePickerBtn.setOnClickListener(v -> showDatePickerDialog());
        
        // Setup weight/height input listeners for BMI calculation
        weightInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            @Override
            public void afterTextChanged(android.text.Editable s) {
                calculateBMI();
            }
        });
        
        heightInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            @Override
            public void afterTextChanged(android.text.Editable s) {
                calculateBMI();
            }
        });
        
        // Setup food checkboxes for meal assessment
        setupFoodCheckboxes();
    }

    private void setupLocationSpinner() {
        // First show "Select Municipality" prompt
        String[] municipalityOptions = new String[BATAAN_MUNICIPALITIES.length + 1];
        municipalityOptions[0] = "Select Municipality";
        System.arraycopy(BATAAN_MUNICIPALITIES, 0, municipalityOptions, 1, BATAAN_MUNICIPALITIES.length);
        
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, 
            android.R.layout.simple_spinner_item, municipalityOptions);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        locationSpinner.setAdapter(adapter);
        
                    locationSpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
                @Override
                public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                    if (isShowingMunicipalities) {
                        if (position > 0) { // Skip "Select Municipality" prompt
                            selectedMunicipality = (String) parent.getItemAtPosition(position);
                            // Switch to barangay list
                            updateLocationSpinnerToBarangays();
                        }
                    } else {
                        if (position == 1) { // "← Change Municipality" selected
                            // Go back to municipality selection
                            resetLocationSpinnerToMunicipalities();
                            selectedBarangay = "";
                        } else if (position > 1) { // Skip "Select Barangay" and "← Change Municipality"
                            selectedBarangay = (String) parent.getItemAtPosition(position);
                        }
                    }
                }
                @Override
                public void onNothingSelected(AdapterView<?> parent) {}
            });
    }

    private boolean isShowingMunicipalities = true;

    private void updateLocationSpinnerToBarangays() {
        String[] barangays = MUNICIPALITY_BARANGAYS.get(selectedMunicipality);
        if (barangays != null) {
            // Add "Select Barangay" prompt and "Change Municipality" option
            String[] barangayOptions = new String[barangays.length + 2];
            barangayOptions[0] = "Select Barangay";
            barangayOptions[1] = "← Change Municipality";
            System.arraycopy(barangays, 0, barangayOptions, 2, barangays.length);
            
            ArrayAdapter<String> adapter = new ArrayAdapter<>(this, 
                android.R.layout.simple_spinner_item, barangayOptions);
            adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
            locationSpinner.setAdapter(adapter);
            isShowingMunicipalities = false;
        }
    }

    private void resetLocationSpinnerToMunicipalities() {
        // Reset to municipality selection
        String[] municipalityOptions = new String[BATAAN_MUNICIPALITIES.length + 1];
        municipalityOptions[0] = "Select Municipality";
        System.arraycopy(BATAAN_MUNICIPALITIES, 0, municipalityOptions, 1, BATAAN_MUNICIPALITIES.length);
        
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, 
            android.R.layout.simple_spinner_item, municipalityOptions);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        locationSpinner.setAdapter(adapter);
        isShowingMunicipalities = true;
        selectedMunicipality = ""; // Reset municipality selection
    }

    private void showDatePickerDialog() {
        Calendar calendar = Calendar.getInstance();
        int year = calendar.get(Calendar.YEAR);
        int month = calendar.get(Calendar.MONTH);
        int day = calendar.get(Calendar.DAY_OF_MONTH);

        DatePickerDialog datePickerDialog = new DatePickerDialog(this, 
            (view, selectedYear, selectedMonth, selectedDay) -> {
                Calendar selectedDate = Calendar.getInstance();
                selectedDate.set(selectedYear, selectedMonth, selectedDay);
                
                SimpleDateFormat sdf = new SimpleDateFormat("MMM dd, yyyy", Locale.getDefault());
                selectedBirthdate = sdf.format(selectedDate.getTime());
                birthdatePickerBtn.setText(selectedBirthdate);
                
                // Calculate age
                Calendar today = Calendar.getInstance();
                calculatedAge = today.get(Calendar.YEAR) - selectedYear;
                if (today.get(Calendar.DAY_OF_YEAR) < selectedDate.get(Calendar.DAY_OF_YEAR)) {
                    calculatedAge--;
                }
                
                // Show/hide immunization section for children ≤ 12 years
                if (calculatedAge <= 12) {
                    questionCards[5].setVisibility(View.VISIBLE); // Immunization section
                } else {
                    questionCards[5].setVisibility(View.GONE);
                }
                
                // Update pregnancy section visibility for females
                updatePregnancyVisibility();
                updateButtonShading();
            }, year, month, day);
        
        datePickerDialog.show();
    }

    private void updatePregnancyVisibility() {
        // Only show pregnancy question if both birthdate and sex are selected AND conditions are met
        if (!TextUtils.isEmpty(selectedBirthdate) && !TextUtils.isEmpty(selectedSex)) {
            if ("Female".equals(selectedSex) && calculatedAge >= 12 && calculatedAge <= 50) {
                findViewById(R.id.pregnant_section).setVisibility(View.VISIBLE);
            } else {
                findViewById(R.id.pregnant_section).setVisibility(View.GONE);
                selectedPregnant = "Not Applicable";
            }
        } else {
            // Hide pregnancy question if birthdate or sex not selected yet
            findViewById(R.id.pregnant_section).setVisibility(View.GONE);
            selectedPregnant = "Not Applicable";
        }
    }

    private void updateButtonShading() {
        // Update sex button shading
        findViewById(R.id.sex_male).setSelected("Male".equals(selectedSex));
        findViewById(R.id.sex_female).setSelected("Female".equals(selectedSex));
        
        // Update pregnancy button shading
        findViewById(R.id.pregnant_yes).setSelected("Yes".equals(selectedPregnant));
        findViewById(R.id.pregnant_no).setSelected("No".equals(selectedPregnant));
    }

    private void setupFoodCheckboxes() {
        // Add listeners to all food checkboxes for shading
        CheckBox[] foodCheckboxes = {foodCarbs, foodProtein, foodVeggiesFruits, foodDairy};
        
        for (CheckBox checkbox : foodCheckboxes) {
            checkbox.setOnCheckedChangeListener((buttonView, isChecked) -> {
                // Just track selections - no assessment needed
            });
        }
    }

    private void setupClickListeners() {
        // Navigation buttons
        prevButton.setOnClickListener(v -> previousQuestion());
        nextButton.setOnClickListener(v -> nextQuestion());
        
        // Sex selection buttons
        findViewById(R.id.sex_male).setOnClickListener(v -> {
            selectedSex = "Male";
            updatePregnancyVisibility();
            updateButtonShading();
        });
        
                findViewById(R.id.sex_female).setOnClickListener(v -> {
            selectedSex = "Female";
            updatePregnancyVisibility();
            updateButtonShading();
        });
        
        // Pregnancy selection buttons
        findViewById(R.id.pregnant_yes).setOnClickListener(v -> {
            selectedPregnant = "Yes";
            updateButtonShading();
        });
        
        findViewById(R.id.pregnant_no).setOnClickListener(v -> {
            selectedPregnant = "No";
            updateButtonShading();
        });
        
        // Lifestyle selection
        lifestyleRadioGroup.setOnCheckedChangeListener((group, checkedId) -> {
            if (checkedId == R.id.lifestyle_active) {
                selectedLifestyle = "Active";
                otherLifestyleInput.setVisibility(View.GONE);
            } else if (checkedId == R.id.lifestyle_sedentary) {
                selectedLifestyle = "Sedentary";
                otherLifestyleInput.setVisibility(View.GONE);
            } else if (checkedId == R.id.lifestyle_other) {
                selectedLifestyle = "Other";
                otherLifestyleInput.setVisibility(View.VISIBLE);
            }
        });
        
        // Family history checkboxes
        noneCheckBox.setOnCheckedChangeListener((buttonView, isChecked) -> {
            if (isChecked) {
                // Uncheck all other checkboxes
                diabetesCheckBox.setChecked(false);
                hypertensionCheckBox.setChecked(false);
                heartDiseaseCheckBox.setChecked(false);
                kidneyDiseaseCheckBox.setChecked(false);
                tuberculosisCheckBox.setChecked(false);
                obesityCheckBox.setChecked(false);
                malnutritionCheckBox.setChecked(false);
                otherConditionInput.setText("");
            }
        });
        
        // Other checkboxes - uncheck "None" when any other is selected
        View.OnClickListener uncheckNoneListener = v -> noneCheckBox.setChecked(false);
        diabetesCheckBox.setOnClickListener(uncheckNoneListener);
        hypertensionCheckBox.setOnClickListener(uncheckNoneListener);
        heartDiseaseCheckBox.setOnClickListener(uncheckNoneListener);
        kidneyDiseaseCheckBox.setOnClickListener(uncheckNoneListener);
        tuberculosisCheckBox.setOnClickListener(uncheckNoneListener);
        obesityCheckBox.setOnClickListener(uncheckNoneListener);
        malnutritionCheckBox.setOnClickListener(uncheckNoneListener);
    }

    private void calculateBMI() {
        try {
            double weight = Double.parseDouble(weightInput.getText().toString());
            double height = Double.parseDouble(heightInput.getText().toString());
            
            if (weight > 0 && height > 0) {
                double heightM = height / 100.0;
                double bmi = weight / (heightM * heightM);
                bmiResult.setText(String.format("%.1f", bmi));
                
                // Determine BMI category
                String category;
                int color;
                if (bmi < 18.5) {
                    category = "Underweight";
                    color = 0xFFF44336; // Red
                } else if (bmi < 25) {
                    category = "Normal";
                    color = 0xFF4CAF50; // Green
                } else if (bmi < 30) {
                    category = "Overweight";
                    color = 0xFFFF9800; // Orange
                } else {
                    category = "Obese";
                    color = 0xFFD32F2F; // Dark Red
                }
                
                bmiCategory.setText(category);
                bmiCategory.setTextColor(color);
            }
        } catch (NumberFormatException e) {
            bmiResult.setText("");
            bmiCategory.setText("");
        }
    }



    private void showQuestion(int question) {
        // Hide all questions
        for (View card : questionCards) {
            card.setVisibility(View.GONE);
        }
        
        // Show current question
        questionCards[question].setVisibility(View.VISIBLE);
        
        // Update navigation buttons
        prevButton.setVisibility(question == 0 ? View.GONE : View.VISIBLE);
        nextButton.setText(question == TOTAL_QUESTIONS - 1 ? "Submit" : "Next Section →");
        prevButton.setEnabled(true);
        nextButton.setEnabled(true);
    }

    private void previousQuestion() {
        if (currentQuestion > 0) {
            currentQuestion--;
            showQuestion(currentQuestion);
        }
    }

    private void nextQuestion() {
        if (currentQuestion == TOTAL_QUESTIONS - 1) {
            // Submit form
            submitForm();
        } else {
            // Validate current question
            if (validateCurrentQuestion()) {
                currentQuestion++;
                showQuestion(currentQuestion);
                
                // If moving to final assessment section, calculate results
                if (currentQuestion == TOTAL_QUESTIONS - 1) {
                    calculateFinalAssessment();
                }
            }
        }
    }

    private boolean validateCurrentQuestion() {
        switch (currentQuestion) {
            case 0: // Basic Information
                if (TextUtils.isEmpty(selectedMunicipality) || "Select Municipality".equals(selectedMunicipality)) {
                    Toast.makeText(this, "Please select municipality", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(selectedBarangay) || "Select Barangay".equals(selectedBarangay) || "← Change Municipality".equals(selectedBarangay)) {
                    Toast.makeText(this, "Please select barangay", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(selectedBirthdate)) {
                    Toast.makeText(this, "Please select birthdate", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(selectedSex)) {
                    Toast.makeText(this, "Please select sex", Toast.LENGTH_SHORT).show();
                    return false;
                }
                break;
            case 1: // Anthropometric Assessment
                if (TextUtils.isEmpty(weightInput.getText().toString())) {
                    weightInput.setError("Weight is required");
                    return false;
                }
                if (TextUtils.isEmpty(heightInput.getText().toString())) {
                    heightInput.setError("Height is required");
                    return false;
                }
                break;
            case 2: // Meal Assessment
                // Check if at least one food group is selected
                boolean hasFoodSelected = foodCarbs.isChecked() || foodProtein.isChecked() || 
                                        foodVeggiesFruits.isChecked() || foodDairy.isChecked();
                
                if (!hasFoodSelected) {
                    Toast.makeText(this, "Please select at least one food group", Toast.LENGTH_SHORT).show();
                    return false;
                }
                break;
            case 3: // Family History
                // At least one option must be selected
                if (!diabetesCheckBox.isChecked() && !hypertensionCheckBox.isChecked() && 
                    !heartDiseaseCheckBox.isChecked() && !kidneyDiseaseCheckBox.isChecked() && 
                    !tuberculosisCheckBox.isChecked() && !obesityCheckBox.isChecked() && 
                    !malnutritionCheckBox.isChecked() && !noneCheckBox.isChecked()) {
                    Toast.makeText(this, "Please select at least one option or choose None", Toast.LENGTH_SHORT).show();
                    return false;
                }
                break;
            case 4: // Lifestyle
                if (TextUtils.isEmpty(selectedLifestyle)) {
                    Toast.makeText(this, "Please select lifestyle", Toast.LENGTH_SHORT).show();
                    return false;
                }
                break;
            case 5: // Immunization (only for children ≤ 12 years)
                // Validate immunization for children ≤ 12 years
                if (calculatedAge <= 12) {
                    // For checkboxes, no validation needed - users can check or uncheck as needed
                }
                break;
        }
        return true;
    }

    private void calculateFinalAssessment() {
        // Calculate risk score based on all sections
        int riskScore = calculateRiskScore();
        
        // Determine risk level
        String riskLevel;
        String recommendation;
        String intervention;
        
        if (riskScore >= 80) {
            riskLevel = "High Risk";
            recommendation = "Immediate lifestyle intervention needed";
            intervention = "Nutrition counseling, physical activity program, regular health monitoring";
        } else if (riskScore >= 50) {
            riskLevel = "Medium Risk";
            recommendation = "Nutrition intervention needed";
            intervention = "DOH feeding program, nutrition counseling";
        } else {
            riskLevel = "Low Risk";
            recommendation = "Maintain current healthy lifestyle";
            intervention = "Regular monitoring";
        }
        
        // Update final assessment display
        finalAssessmentText.setText("Risk Score: " + riskScore + "%");
        riskLevelText.setText("Risk Level: " + riskLevel);
        recommendationText.setText("Recommendation: " + recommendation);
        interventionText.setText("Intervention: " + intervention);
    }

    private int calculateRiskScore() {
        int score = 0;
        
        try {
            // Get basic data
            int age = calculatedAge;
            double weight = Double.parseDouble(weightInput.getText().toString());
            double height = Double.parseDouble(heightInput.getText().toString());
            
            // Calculate BMI
            double heightM = height / 100.0;
            double bmi = weight / (heightM * heightM);
            
            // BMI scoring
            if (bmi < 18.5) score += 25;
            else if (bmi < 25) score += 0;
            else if (bmi < 30) score += 15;
            else score += 30;
            
            // Meal assessment scoring - simplified
            boolean hasBalancedMeal = foodCarbs.isChecked() && foodProtein.isChecked() && foodVeggiesFruits.isChecked();
            if (!hasBalancedMeal) score += 15;
            
            // Family history scoring
            if (diabetesCheckBox.isChecked()) score += 10;
            if (hypertensionCheckBox.isChecked()) score += 10;
            if (heartDiseaseCheckBox.isChecked()) score += 10;
            if (kidneyDiseaseCheckBox.isChecked()) score += 10;
            if (tuberculosisCheckBox.isChecked()) score += 8;
            if (obesityCheckBox.isChecked()) score += 12;
            if (malnutritionCheckBox.isChecked()) score += 15;
            
            // Lifestyle scoring
            if ("Sedentary".equals(selectedLifestyle)) score += 15;
            
            // Immunization scoring (for children ≤ 12 years)
            if (age <= 12) {
                // Check immunization completeness using checkboxes
                boolean bcgComplete = immBcg.isChecked();
                boolean dptComplete = immDpt.isChecked();
                boolean polioComplete = immPolio.isChecked();
                boolean measlesComplete = immMeasles.isChecked();
                boolean hepatitisComplete = immHepatitis.isChecked();
                boolean vitaminAComplete = immVitaminA.isChecked();
                
                if (!bcgComplete || !dptComplete || !polioComplete || 
                    !measlesComplete || !hepatitisComplete || !vitaminAComplete) {
                    score += 10;
                }
            }
            
        } catch (NumberFormatException e) {
            // Handle parsing errors
        }
        
        return Math.min(score, 100);
    }

    private String getRadioGroupValue(RadioGroup radioGroup) {
        int checkedId = radioGroup.getCheckedRadioButtonId();
        if (checkedId != -1) {
            RadioButton radioButton = findViewById(checkedId);
            return radioButton.getText().toString();
        }
        return "";
    }

    private void submitForm() {
        if (!validateCurrentQuestion()) {
            return;
        }

        // Calculate final risk score
        int riskScore = calculateRiskScore();

        // Save to local database
        saveScreeningResults(riskScore);

        // Show result
        showScreeningResult(riskScore);
    }

    private void saveScreeningResults(int riskScore) {
        // Save to local storage
        ScreeningResultStore.setRiskScore(this, riskScore);
        
        // Get current user email
        String email = getCurrentUserEmail();
        if (email != null && !email.isEmpty()) {
            // Save to local database
            saveScreeningToLocalDatabase(email, riskScore);
            
            // Sync to API
            syncScreeningToApi(email, riskScore);
        }
        
        // Show success message
        Toast.makeText(this, "Comprehensive screening results saved successfully!", Toast.LENGTH_SHORT).show();
    }
    
    private void saveScreeningToLocalDatabase(String email, int riskScore) {
        try {
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(this);
            
            // Create comprehensive screening data JSON
            JSONObject screeningData = new JSONObject();
            
            // Section 1: Basic Information
            screeningData.put("municipality", selectedMunicipality);
            screeningData.put("barangay", selectedBarangay);
            screeningData.put("birthdate", selectedBirthdate);
            screeningData.put("age", String.valueOf(calculatedAge));
            screeningData.put("sex", selectedSex);
            screeningData.put("pregnant", selectedPregnant);
            
            // Section 2: Anthropometric Assessment
            screeningData.put("weight", weightInput.getText().toString());
            screeningData.put("height", heightInput.getText().toString());
            screeningData.put("bmi", bmiResult.getText().toString());
            screeningData.put("bmi_category", bmiCategory.getText().toString());
            
            // Section 3: Meal Assessment
            JSONObject foodGroups = new JSONObject();
            foodGroups.put("carbohydrates", foodCarbs.isChecked());
            foodGroups.put("protein", foodProtein.isChecked());
            foodGroups.put("vegetables_fruits", foodVeggiesFruits.isChecked());
            foodGroups.put("dairy", foodDairy.isChecked());
            screeningData.put("food_groups", foodGroups);
            
            // Section 4: Family History
            JSONObject familyHistory = new JSONObject();
            familyHistory.put("diabetes", diabetesCheckBox.isChecked());
            familyHistory.put("hypertension", hypertensionCheckBox.isChecked());
            familyHistory.put("heart_disease", heartDiseaseCheckBox.isChecked());
            familyHistory.put("kidney_disease", kidneyDiseaseCheckBox.isChecked());
            familyHistory.put("tuberculosis", tuberculosisCheckBox.isChecked());
            familyHistory.put("obesity", obesityCheckBox.isChecked());
            familyHistory.put("malnutrition", malnutritionCheckBox.isChecked());
            familyHistory.put("other", otherConditionInput.getText().toString());
            familyHistory.put("none", noneCheckBox.isChecked());
            screeningData.put("family_history", familyHistory);
            
            // Section 5: Lifestyle
            screeningData.put("lifestyle", selectedLifestyle);
            screeningData.put("other_lifestyle", otherLifestyleInput.getText().toString());
            
            // Section 6: Immunization
            JSONObject immunization = new JSONObject();
            immunization.put("bcg", immBcg.isChecked());
            immunization.put("dpt", immDpt.isChecked());
            immunization.put("polio", immPolio.isChecked());
            immunization.put("measles", immMeasles.isChecked());
            immunization.put("hepatitis", immHepatitis.isChecked());
            immunization.put("vitamin_a", immVitaminA.isChecked());
            screeningData.put("immunization", immunization);
            
            // Risk assessment
            screeningData.put("risk_score", riskScore);
            screeningData.put("risk_level", riskLevelText.getText().toString());
            screeningData.put("recommendation", recommendationText.getText().toString());
            screeningData.put("intervention", interventionText.getText().toString());
            
            // Save to database
            android.database.sqlite.SQLiteDatabase db = dbHelper.getWritableDatabase();
            android.content.ContentValues values = new android.content.ContentValues();
            values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
            values.put(UserPreferencesDbHelper.COL_RISK_SCORE, riskScore);
            values.put(UserPreferencesDbHelper.COL_SCREENING_ANSWERS, screeningData.toString());
            
            // Check if user already exists
            android.database.Cursor cursor = db.query(UserPreferencesDbHelper.TABLE_NAME, 
                null, UserPreferencesDbHelper.COL_USER_EMAIL + "=?", 
                new String[]{email}, null, null, null);
            
            if (cursor.moveToFirst()) {
                // Update existing record
                db.update(UserPreferencesDbHelper.TABLE_NAME, values, 
                    UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
            } else {
                // Insert new record
                db.insert(UserPreferencesDbHelper.TABLE_NAME, null, values);
            }
            cursor.close();
            dbHelper.close();
            
        } catch (Exception e) {
            Log.e("ScreeningFormActivity", "Error saving to local database: " + e.getMessage());
        }
    }
    
    private void syncScreeningToApi(String email, int riskScore) {
        // Create comprehensive screening data for API
        try {
            JSONObject screeningData = new JSONObject();
            
            // Basic information
            screeningData.put("email", email);
            screeningData.put("municipality", selectedMunicipality);
            screeningData.put("barangay", selectedBarangay);
            screeningData.put("birthdate", selectedBirthdate);
            screeningData.put("age", String.valueOf(calculatedAge));
            screeningData.put("sex", selectedSex);
            screeningData.put("pregnant", selectedPregnant);
            
            // Anthropometric data
            screeningData.put("weight", weightInput.getText().toString());
            screeningData.put("height", heightInput.getText().toString());
            screeningData.put("bmi", bmiResult.getText().toString());
            screeningData.put("bmi_category", bmiCategory.getText().toString());
            
            // Meal assessment
            // Food groups data is already added above
            // Meal assessment removed - just track food groups
            
            // Family history
            JSONArray familyHistory = new JSONArray();
            if (diabetesCheckBox.isChecked()) familyHistory.put("Diabetes");
            if (hypertensionCheckBox.isChecked()) familyHistory.put("Hypertension");
            if (heartDiseaseCheckBox.isChecked()) familyHistory.put("Heart Disease");
            if (kidneyDiseaseCheckBox.isChecked()) familyHistory.put("Kidney Disease");
            if (tuberculosisCheckBox.isChecked()) familyHistory.put("Tuberculosis");
            if (obesityCheckBox.isChecked()) familyHistory.put("Obesity");
            if (malnutritionCheckBox.isChecked()) familyHistory.put("Malnutrition");
            screeningData.put("family_history", familyHistory);
            
            // Lifestyle
            screeningData.put("lifestyle", selectedLifestyle);
            
            // Immunization
            JSONObject immunization = new JSONObject();
            immunization.put("bcg", immBcg.isChecked());
            immunization.put("dpt", immDpt.isChecked());
            immunization.put("polio", immPolio.isChecked());
            immunization.put("measles", immMeasles.isChecked());
            immunization.put("hepatitis", immHepatitis.isChecked());
            immunization.put("vitamin_a", immVitaminA.isChecked());
            screeningData.put("immunization", immunization);
            
            // Risk assessment
            screeningData.put("risk_score", riskScore);
            screeningData.put("risk_level", riskLevelText.getText().toString());
            screeningData.put("recommendation", recommendationText.getText().toString());
            screeningData.put("intervention", interventionText.getText().toString());
            
            // Send to API
            sendToComprehensiveScreeningApi(email, riskScore, screeningData);
            
        } catch (Exception e) {
            Log.e("ScreeningFormActivity", "Error creating screening data: " + e.getMessage());
        }
    }

    private void sendToComprehensiveScreeningApi(String email, int riskScore, JSONObject screeningData) {
        new Thread(() -> {
            try {
                JSONObject requestData = new JSONObject();
                requestData.put("action", "save_comprehensive_screening");
                requestData.put("email", email);
                requestData.put("risk_score", riskScore);
                requestData.put("screening_data", screeningData.toString());
                
                // Make HTTP request to comprehensive screening API
                java.net.URL url = new java.net.URL(Constants.API_BASE_URL + "/api/comprehensive_screening.php");
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setRequestProperty("User-Agent", Constants.USER_AGENT);
                conn.setDoOutput(true);
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                
                java.io.OutputStream os = conn.getOutputStream();
                os.write(requestData.toString().getBytes("UTF-8"));
                os.close();
                
                int responseCode = conn.getResponseCode();
                Log.d("ScreeningFormActivity", "Comprehensive screening API response code: " + responseCode);
                
                if (responseCode == 200) {
                    java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    Log.d("ScreeningFormActivity", "Comprehensive screening API response: " + response.toString());
                    runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, 
                        "Comprehensive screening synced successfully", Toast.LENGTH_SHORT).show());
                } else {
                    Log.e("ScreeningFormActivity", "Failed to sync comprehensive screening: " + responseCode);
                    runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, 
                        "Failed to sync comprehensive screening data", Toast.LENGTH_SHORT).show());
                }
                
            } catch (Exception e) {
                Log.e("ScreeningFormActivity", "Error syncing comprehensive screening: " + e.getMessage());
                runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, 
                    "Error syncing comprehensive screening: " + e.getMessage(), Toast.LENGTH_SHORT).show());
            }
        }).start();
    }

    private String getCurrentUserEmail() {
        return getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).getString("current_user_email", null);
    }

    private void showScreeningResult(int riskScore) {
        String riskCategory = riskLevelText.getText().toString();
        String recommendation = recommendationText.getText().toString();
        
        // Create result message
        String message = riskCategory + ": " + riskScore + "% - " + recommendation;
        
        // Show result as toast message
        Toast.makeText(this, message, Toast.LENGTH_LONG).show();
        
        // Navigate to main activity after a short delay
        new android.os.Handler().postDelayed(() -> {
            Intent intent = new Intent(ScreeningFormActivity.this, MainActivity.class);
            startActivity(intent);
            finish();
        }, 2000);
    }

    private void initializeButtonStates() {
        // Initialize all form elements to default states
        // This method can be expanded as needed for specific UI elements
    }

    private void handleTestMode() {
        // Check if we're in test mode
        boolean isTestMode = getIntent().getBooleanExtra("test_mode", false);
        if (!isTestMode) return;
        
        // Show test mode notification
        Toast.makeText(this, "🧪 TEST MODE: Comprehensive screening demo data pre-filled!", Toast.LENGTH_LONG).show();
        
        // Pre-fill form with realistic test data for comprehensive screening
        new Thread(() -> {
            try {
                Thread.sleep(500);
            } catch (InterruptedException e) {
                return;
            }
            
            runOnUiThread(() -> {
                try {
                    // Section 1: Basic Information
                    birthdatePickerBtn.setText("Jan 15, 1995");
                    selectedBirthdate = "Jan 15, 1995";
                    calculatedAge = 28;
                    selectedSex = "Female";
                    selectedPregnant = "No";
                    
                    // Section 2: Anthropometric Assessment
                    weightInput.setText("55");
                    heightInput.setText("158");
                    
                    // Section 3: Meal Assessment
                    foodCarbs.setChecked(true);
                    foodProtein.setChecked(true);
                    foodVeggiesFruits.setChecked(true);
                    
                    // Section 4: Family History
                    hypertensionCheckBox.setChecked(true);
                    
                    // Section 5: Lifestyle
                    selectedLifestyle = "Active";
                    
                    // Section 6: Immunization (for children)
                    // Will be handled by age validation
                    
                } catch (Exception e) {
                    Log.e("ScreeningFormActivity", "Error pre-filling test data: " + e.getMessage());
                }
            });
        }).start();
    }
} 