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
    private Spinner municipalitySpinner, barangaySpinner;
    private EditText ageInput;
    private EditText monthsInput; // For children < 1 year
    private RadioGroup sexRadioGroup;
    private RadioGroup pregnantRadioGroup;
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
    private EditText mealRecallInput;
    private TextView mealAssessmentResult;
    
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
    private RadioGroup bcgRadioGroup;
    private RadioGroup dptRadioGroup;
    private RadioGroup polioRadioGroup;
    private RadioGroup measlesRadioGroup;
    private RadioGroup hepatitisRadioGroup;
    private RadioGroup vitaminARadioGroup;
    
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
        municipalitySpinner = findViewById(R.id.municipality_spinner);
        barangaySpinner = findViewById(R.id.barangay_spinner);
        ageInput = findViewById(R.id.age_input);
        monthsInput = findViewById(R.id.months_input);
        sexRadioGroup = findViewById(R.id.sex_radio_group);
        pregnantRadioGroup = findViewById(R.id.pregnant_radio_group);
        
        // Section 2: Anthropometric Assessment
        weightInput = findViewById(R.id.weight_input);
        heightInput = findViewById(R.id.height_input);
        bmiResult = findViewById(R.id.bmi_result);
        bmiCategory = findViewById(R.id.bmi_category);
        
        // Section 3: Meal Assessment
        mealRecallInput = findViewById(R.id.meal_recall_input);
        mealAssessmentResult = findViewById(R.id.meal_assessment_result);
        
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
        bcgRadioGroup = findViewById(R.id.bcg_radio_group);
        dptRadioGroup = findViewById(R.id.dpt_radio_group);
        polioRadioGroup = findViewById(R.id.polio_radio_group);
        measlesRadioGroup = findViewById(R.id.measles_radio_group);
        hepatitisRadioGroup = findViewById(R.id.hepatitis_radio_group);
        vitaminARadioGroup = findViewById(R.id.vitamin_a_radio_group);
        
        // Section 7: Final Assessment
        finalAssessmentText = findViewById(R.id.final_assessment_text);
        riskLevelText = findViewById(R.id.risk_level_text);
        recommendationText = findViewById(R.id.recommendation_text);
        interventionText = findViewById(R.id.intervention_text);
        
        // Setup spinners
        setupMunicipalitySpinner();
        setupBarangaySpinner();
        
        // Setup age input listener for months field visibility
        ageInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            @Override
            public void afterTextChanged(android.text.Editable s) {
                try {
                    int age = Integer.parseInt(s.toString());
                    if (age < 1) {
                        monthsInput.setVisibility(View.VISIBLE);
                    } else {
                        monthsInput.setVisibility(View.GONE);
                    }
                    // Show/hide immunization section for children ≤ 12 years
                    if (age <= 12) {
                        questionCards[5].setVisibility(View.VISIBLE); // Immunization section
                    } else {
                        questionCards[5].setVisibility(View.GONE);
                    }
                } catch (NumberFormatException e) {
                    monthsInput.setVisibility(View.GONE);
                }
            }
        });
        
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
        
        // Setup meal recall input listener for assessment
        mealRecallInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            @Override
            public void afterTextChanged(android.text.Editable s) {
                assessMealDiversity(s.toString());
            }
        });
    }

    private void setupMunicipalitySpinner() {
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, 
            android.R.layout.simple_spinner_item, BATAAN_MUNICIPALITIES);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        municipalitySpinner.setAdapter(adapter);
        
        municipalitySpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                selectedMunicipality = (String) parent.getItemAtPosition(position);
                updateBarangaySpinner();
            }
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }

    private void setupBarangaySpinner() {
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, 
            android.R.layout.simple_spinner_item, new String[]{});
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        barangaySpinner.setAdapter(adapter);
        
        barangaySpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                selectedBarangay = (String) parent.getItemAtPosition(position);
            }
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }

    private void updateBarangaySpinner() {
        String[] barangays = MUNICIPALITY_BARANGAYS.get(selectedMunicipality);
        if (barangays != null) {
            ArrayAdapter<String> adapter = new ArrayAdapter<>(this, 
                android.R.layout.simple_spinner_item, barangays);
            adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
            barangaySpinner.setAdapter(adapter);
        }
    }

    private void setupClickListeners() {
        // Navigation buttons
        prevButton.setOnClickListener(v -> previousQuestion());
        nextButton.setOnClickListener(v -> nextQuestion());
        
        // Sex selection
        sexRadioGroup.setOnCheckedChangeListener((group, checkedId) -> {
            if (checkedId == R.id.sex_female) {
                selectedSex = "Female";
                // Show pregnancy question for females 12-50 years
                try {
                    int age = Integer.parseInt(ageInput.getText().toString());
                    if (age >= 12 && age <= 50) {
                        pregnantRadioGroup.setVisibility(View.VISIBLE);
                    } else {
                        pregnantRadioGroup.setVisibility(View.GONE);
                    }
                } catch (NumberFormatException e) {
                    pregnantRadioGroup.setVisibility(View.GONE);
                }
            } else {
                selectedSex = "Male";
                pregnantRadioGroup.setVisibility(View.GONE);
            }
        });
        
        // Pregnancy selection
        pregnantRadioGroup.setOnCheckedChangeListener((group, checkedId) -> {
            if (checkedId == R.id.pregnant_yes) {
                selectedPregnant = "Yes";
            } else if (checkedId == R.id.pregnant_no) {
                selectedPregnant = "No";
            } else {
                selectedPregnant = "Not Applicable";
            }
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

    private void assessMealDiversity(String mealText) {
        if (mealText.isEmpty()) {
            mealAssessmentResult.setText("");
            return;
        }
        
        String lowerMeal = mealText.toLowerCase();
        
        // Check for food groups
        boolean hasCarbs = lowerMeal.contains("rice") || lowerMeal.contains("bread") || 
                          lowerMeal.contains("pasta") || lowerMeal.contains("potato") || 
                          lowerMeal.contains("corn") || lowerMeal.contains("cereal");
        
        boolean hasProtein = lowerMeal.contains("meat") || lowerMeal.contains("fish") || 
                            lowerMeal.contains("chicken") || lowerMeal.contains("pork") || 
                            lowerMeal.contains("beef") || lowerMeal.contains("egg") || 
                            lowerMeal.contains("milk") || lowerMeal.contains("cheese") || 
                            lowerMeal.contains("beans") || lowerMeal.contains("tofu");
        
        boolean hasVeggies = lowerMeal.contains("vegetable") || lowerMeal.contains("carrot") || 
                           lowerMeal.contains("broccoli") || lowerMeal.contains("spinach") || 
                           lowerMeal.contains("lettuce") || lowerMeal.contains("tomato") || 
                           lowerMeal.contains("onion");
        
        boolean hasFruits = lowerMeal.contains("fruit") || lowerMeal.contains("apple") || 
                          lowerMeal.contains("banana") || lowerMeal.contains("orange") || 
                          lowerMeal.contains("mango") || lowerMeal.contains("grape");
        
        if (hasCarbs && hasProtein && (hasVeggies || hasFruits)) {
            mealAssessmentResult.setText("✅ Balanced");
            mealAssessmentResult.setTextColor(0xFF4CAF50); // Green
        } else {
            mealAssessmentResult.setText("⚠️ At Risk");
            mealAssessmentResult.setTextColor(0xFFFF9800); // Orange
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
                if (TextUtils.isEmpty(selectedMunicipality)) {
                    Toast.makeText(this, "Please select municipality", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(selectedBarangay)) {
                    Toast.makeText(this, "Please select barangay", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(ageInput.getText().toString())) {
                    ageInput.setError("Age is required");
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
                if (TextUtils.isEmpty(mealRecallInput.getText().toString())) {
                    mealRecallInput.setError("24-hour meal recall is required");
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
                try {
                    int age = Integer.parseInt(ageInput.getText().toString());
                    if (age <= 12) {
                        // Validate immunization responses
                        if (bcgRadioGroup.getCheckedRadioButtonId() == -1 || 
                            dptRadioGroup.getCheckedRadioButtonId() == -1 || 
                            polioRadioGroup.getCheckedRadioButtonId() == -1 || 
                            measlesRadioGroup.getCheckedRadioButtonId() == -1 || 
                            hepatitisRadioGroup.getCheckedRadioButtonId() == -1 || 
                            vitaminARadioGroup.getCheckedRadioButtonId() == -1) {
                            Toast.makeText(this, "Please answer all immunization questions", Toast.LENGTH_SHORT).show();
                            return false;
                        }
                    }
                } catch (NumberFormatException e) {
                    // Age not entered, skip validation
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
            int age = Integer.parseInt(ageInput.getText().toString());
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
            
            // Meal assessment scoring
            String mealAssessment = mealAssessmentResult.getText().toString();
            if (mealAssessment.contains("At Risk")) score += 15;
            
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
                // Check immunization completeness
                boolean bcgComplete = getRadioGroupValue(bcgRadioGroup).equals("Yes");
                boolean dptComplete = getRadioGroupValue(dptRadioGroup).equals("Yes");
                boolean polioComplete = getRadioGroupValue(polioRadioGroup).equals("Yes");
                boolean measlesComplete = getRadioGroupValue(measlesRadioGroup).equals("Yes");
                boolean hepatitisComplete = getRadioGroupValue(hepatitisRadioGroup).equals("Yes");
                boolean vitaminAComplete = getRadioGroupValue(vitaminARadioGroup).equals("Yes");
                
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
            screeningData.put("age", ageInput.getText().toString());
            screeningData.put("sex", selectedSex);
            screeningData.put("pregnant", selectedPregnant);
            
            // Section 2: Anthropometric Assessment
            screeningData.put("weight", weightInput.getText().toString());
            screeningData.put("height", heightInput.getText().toString());
            screeningData.put("bmi", bmiResult.getText().toString());
            screeningData.put("bmi_category", bmiCategory.getText().toString());
            
            // Section 3: Meal Assessment
            screeningData.put("meal_recall", mealRecallInput.getText().toString());
            screeningData.put("meal_assessment", mealAssessmentResult.getText().toString());
            
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
            immunization.put("bcg", getRadioGroupValue(bcgRadioGroup));
            immunization.put("dpt", getRadioGroupValue(dptRadioGroup));
            immunization.put("polio", getRadioGroupValue(polioRadioGroup));
            immunization.put("measles", getRadioGroupValue(measlesRadioGroup));
            immunization.put("hepatitis", getRadioGroupValue(hepatitisRadioGroup));
            immunization.put("vitamin_a", getRadioGroupValue(vitaminARadioGroup));
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
            screeningData.put("age", ageInput.getText().toString());
            screeningData.put("sex", selectedSex);
            screeningData.put("pregnant", selectedPregnant);
            
            // Anthropometric data
            screeningData.put("weight", weightInput.getText().toString());
            screeningData.put("height", heightInput.getText().toString());
            screeningData.put("bmi", bmiResult.getText().toString());
            screeningData.put("bmi_category", bmiCategory.getText().toString());
            
            // Meal assessment
            screeningData.put("meal_recall", mealRecallInput.getText().toString());
            screeningData.put("meal_assessment", mealAssessmentResult.getText().toString());
            
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
            immunization.put("bcg", getRadioGroupValue(bcgRadioGroup));
            immunization.put("dpt", getRadioGroupValue(dptRadioGroup));
            immunization.put("polio", getRadioGroupValue(polioRadioGroup));
            immunization.put("measles", getRadioGroupValue(measlesRadioGroup));
            immunization.put("hepatitis", getRadioGroupValue(hepatitisRadioGroup));
            immunization.put("vitamin_a", getRadioGroupValue(vitaminARadioGroup));
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
                    ageInput.setText("28");
                    selectedSex = "Female";
                    selectedPregnant = "No";
                    
                    // Section 2: Anthropometric Assessment
                    weightInput.setText("55");
                    heightInput.setText("158");
                    
                    // Section 3: Meal Assessment
                    mealRecallInput.setText("Rice, chicken, vegetables, apple");
                    
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