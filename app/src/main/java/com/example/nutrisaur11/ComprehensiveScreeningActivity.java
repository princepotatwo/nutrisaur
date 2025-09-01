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

import org.json.JSONObject;
import org.json.JSONArray;
import java.util.List;
import java.util.ArrayList;
import java.util.Map;
import java.util.HashMap;
import java.util.Arrays;
import java.util.Collections;
import java.util.Date;
import java.util.Calendar;
import java.util.Locale;
import java.util.concurrent.CompletableFuture;

public class ComprehensiveScreeningActivity extends AppCompatActivity {

    private static final String TAG = "ComprehensiveScreening";
    private static final int TOTAL_SECTIONS = 7;
    
    // Current section tracking
    private int currentSection = 1;
    
    // Section views
    private View[] sectionViews;
    
    // Navigation buttons
    private Button prevButton, nextButton;
    private TextView progressText;
    private ProgressBar progressBar;
    
    // Section 1: Basic Information
    private Spinner municipalitySpinner, barangaySpinner;
    private EditText ageYearsInput, ageMonthsInput;
    private RadioGroup sexRadioGroup;
    private LinearLayout pregnantSection;
    private RadioGroup pregnantRadioGroup;
    
    // Section 2: Anthropometric Assessment
    private EditText weightInput, heightInput;
    private LinearLayout bmiDisplay;
    private TextView bmiValue, bmiCategory;
    
    // Section 3: Meal Assessment
    private EditText mealRecallInput;
    private TextView mealAnalysis;
    
    // Section 4: Family History
    private CheckBox fhDiabetes, fhHypertension, fhHeart, fhKidney, fhTb, fhObesity, fhMalnutrition, fhOther, fhNone;
    
    // Section 5: Lifestyle
    private RadioGroup lifestyleRadioGroup;
    private EditText lifestyleOtherInput;
    
    // Section 6: Immunization
    private CheckBox immBcg, immDpt, immPolio, immMeasles, immHepb, immVitamina;
    private LinearLayout immunizationSection;
    
    // Section 7: Final Assessment
    private TextView assessmentSummary, recommendations;
    
    // Data storage
    private String selectedMunicipality = "";
    private String selectedBarangay = "";
    private String selectedSex = "";
    private String selectedPregnant = "";
    private String selectedLifestyle = "";
    private String lifestyleOther = "";
    
    // Municipalities and Barangays data
    private static final String[] MUNICIPALITIES = {
        "ABUCAY", "BAGAC", "CITY OF BALANGA (Capital)", "DINALUPIHAN", "HERMOSA", 
        "LIMAY", "MARIVELES", "MORONG", "ORANI", "ORION", "PILAR", "SAMAL"
    };
    
    private static final Map<String, String[]> MUNICIPALITY_BARANGAYS = new HashMap<String, String[]>() {{
        put("ABUCAY", new String[]{"Bangkal", "Calaylayan (Pob.)", "Capitangan", "Gabon", "Laon (Pob.)", "Mabatang", "Omboy", "Salian", "Wawa (Pob.)"});
        put("BAGAC", new String[]{"Bagumbayan (Pob.)", "Banawang", "Binuangan", "Binukawan", "Ibaba", "Ibis", "Pag-asa (Wawa-Sibacan)", "Parang", "Paysawan", "Quinawan", "San Antonio", "Saysain", "Tabing-Ilog (Pob.)", "Atilano L. Ricardo"});
        put("CITY OF BALANGA (Capital)", new String[]{"Bagumbayan", "Cabog-Cabog", "Munting Batangas (Cadre)", "Cataning", "Central", "Cupang Proper", "Cupang West", "Dangcol (Bernabe)", "Ibayo", "Malabia", "Poblacion", "Pto. Rivas Ibaba", "Pto. Rivas Itaas", "San Jose", "Sibacan", "Camacho", "Talisay", "Tanato", "Tenejero", "Tortugas", "Tuyo", "Bagong Silang", "Cupang North", "Doña Francisca", "Lote"});
        put("DINALUPIHAN", new String[]{"Bangal", "Bonifacio (Pob.)", "Burgos (Pob.)", "Colo", "Daang Bago", "Dalao", "Del Pilar (Pob.)", "Gen. Luna (Pob.)", "Gomez (Pob.)", "Happy Valley", "Kataasan", "Layac", "Luacan", "Mabini Proper (Pob.)", "Mabini Ext. (Pob.)", "Magsaysay", "Naparing", "New San Jose", "Old San Jose", "Padre Dandan (Pob.)", "Pag-asa", "Pagalanggang", "Pinulot", "Pita", "Rizal (Pob.)", "Roosevelt", "Roxas (Pob.)", "Saguing", "San Benito", "San Isidro (Pob.)", "San Pablo (Bulate)", "San Ramon", "San Simon", "Santo Niño", "Sapang Balas", "Santa Isabel (Tabacan)", "Torres Bugauen (Pob.)", "Tucop", "Zamora (Pob.)", "Aquino", "Bayan-bayanan", "Maligaya", "Payangan", "Pentor", "Tubo-tubo", "Jose C. Payumo, Jr."});
        put("HERMOSA", new String[]{"A. Rivera (Pob.)", "Almacen", "Bacong", "Balsic", "Bamban", "Burgos-Soliman (Pob.)", "Cataning (Pob.)", "Culis", "Daungan (Pob.)", "Mabiga", "Mabuco", "Maite", "Mambog - Mandama", "Palihan", "Pandatung", "Pulo", "Saba", "San Pedro (Pob.)", "Santo Cristo (Pob.)", "Sumalo", "Tipo", "Judge Roman Cruz Sr. (Mandama)", "Sacrifice Valley"});
        put("LIMAY", new String[]{"Alangan", "Kitang I", "Kitang 2 & Luz", "Lamao", "Landing", "Poblacion", "Reformista", "Townsite", "Wawa", "Duale", "San Francisco de Asis", "St. Francis II"});
        put("MARIVELES", new String[]{"Alas-asin", "Alion", "Batangas II", "Cabcaben", "Lucanin", "Baseco Country (Nassco)", "Poblacion", "San Carlos", "San Isidro", "Sisiman", "Balon-Anito", "Biaan", "Camaya", "Ipag", "Malaya", "Maligaya", "Mt. View", "Townsite"});
        put("MORONG", new String[]{"Binaritan", "Mabayo", "Nagbalayong", "Poblacion", "Sabang"});
        put("ORANI", new String[]{"Bagong Paraiso (Pob.)", "Balut (Pob.)", "Bayan (Pob.)", "Calero (Pob.)", "Paking-Carbonero (Pob.)", "Centro II (Pob.)", "Dona", "Kaparangan", "Masantol", "Mulawin", "Pag-asa", "Palihan (Pob.)", "Pantalan Bago (Pob.)", "Pantalan Luma (Pob.)", "Parang Parang (Pob.)", "Centro I (Pob.)", "Sibul", "Silahis", "Tala", "Talimundoc", "Tapulao", "Tenejero (Pob.)", "Tugatog", "Wawa (Pob.)", "Apollo", "Kabalutan", "Maria Fe", "Puksuan", "Tagumpay"});
        put("ORION", new String[]{"Arellano (Pob.)", "Bagumbayan (Pob.)", "Balagtas (Pob.)", "Balut (Pob.)", "Bantan", "Bilolo", "Calungusan", "Camachile", "Daang Bago (Pob.)", "Daang Bilolo (Pob.)", "Daang Pare", "General Lim (Kaput)", "Kapunitan", "Lati (Pob.)", "Lusungan (Pob.)", "Puting Buhangin", "Sabatan", "San Vicente (Pob.)", "Santo Domingo", "Villa Angeles (Pob.)", "Wakas (Pob.)", "Wawa (Pob.)", "Santa Elena"});
        put("PILAR", new String[]{"Ala-uli", "Bagumbayan", "Balut I", "Balut II", "Bantan Munti", "Burgos", "Del Rosario (Pob.)", "Diwa", "Landing", "Liyang", "Nagwaling", "Panilao", "Pantingan", "Poblacion", "Rizal (Pob.)", "Santa Rosa", "Wakas North", "Wakas South", "Wawa"});
        put("SAMAL", new String[]{"East Calaguiman (Pob.)", "East Daang Bago (Pob.)", "Ibaba (Pob.)", "Imelda", "Lalawigan", "Palili", "San Juan (Pob.)", "San Roque (Pob.)", "Santa Lucia", "Sapa", "Tabing Ilog", "Gugo", "West Calaguiman (Pob.)", "West Daang Bago (Pob.)"});
    }};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_comprehensive_screening_form);
        
        initializeViews();
        setupClickListeners();
        showSection(1);
    }

    private void initializeViews() {
        // Navigation
        prevButton = findViewById(R.id.prev_button);
        nextButton = findViewById(R.id.next_button);
        progressText = findViewById(R.id.progress_text);
        progressBar = findViewById(R.id.progress_bar);
        
        // Section views
        sectionViews = new View[TOTAL_SECTIONS];
        sectionViews[0] = findViewById(R.id.section_1_basic_info);
        sectionViews[1] = findViewById(R.id.section_2_anthropometric);
        sectionViews[2] = findViewById(R.id.section_3_meal);
        sectionViews[3] = findViewById(R.id.section_4_family_history);
        sectionViews[4] = findViewById(R.id.section_5_lifestyle);
        sectionViews[5] = findViewById(R.id.section_6_immunization);
        sectionViews[6] = findViewById(R.id.section_7_final);
        
        // Section 1: Basic Information
        municipalitySpinner = findViewById(R.id.municipality_spinner);
        barangaySpinner = findViewById(R.id.barangay_spinner);
        ageYearsInput = findViewById(R.id.age_years_input);
        ageMonthsInput = findViewById(R.id.age_months_input);
        sexRadioGroup = findViewById(R.id.sex_radio_group);
        pregnantSection = findViewById(R.id.pregnant_section);
        pregnantRadioGroup = findViewById(R.id.pregnant_radio_group);
        
        // Section 2: Anthropometric Assessment
        weightInput = findViewById(R.id.weight_input);
        heightInput = findViewById(R.id.height_input);
        bmiDisplay = findViewById(R.id.bmi_display);
        bmiValue = findViewById(R.id.bmi_value);
        bmiCategory = findViewById(R.id.bmi_category);
        
        // Section 3: Meal Assessment
        mealRecallInput = findViewById(R.id.meal_recall_input);
        mealAnalysis = findViewById(R.id.meal_analysis);
        
        // Section 4: Family History
        fhDiabetes = findViewById(R.id.fh_diabetes);
        fhHypertension = findViewById(R.id.fh_hypertension);
        fhHeart = findViewById(R.id.fh_heart);
        fhKidney = findViewById(R.id.fh_kidney);
        fhTb = findViewById(R.id.fh_tb);
        fhObesity = findViewById(R.id.fh_obesity);
        fhMalnutrition = findViewById(R.id.fh_malnutrition);
        fhOther = findViewById(R.id.fh_other);
        fhNone = findViewById(R.id.fh_none);
        
        // Section 5: Lifestyle
        lifestyleRadioGroup = findViewById(R.id.lifestyle_radio_group);
        lifestyleOtherInput = findViewById(R.id.lifestyle_other_input);
        
        // Section 6: Immunization
        immunizationSection = findViewById(R.id.section_6_immunization);
        immBcg = findViewById(R.id.imm_bcg);
        immDpt = findViewById(R.id.imm_dpt);
        immPolio = findViewById(R.id.imm_polio);
        immMeasles = findViewById(R.id.imm_measles);
        immHepb = findViewById(R.id.imm_hepb);
        immVitamina = findViewById(R.id.imm_vitamina);
        
        // Section 7: Final Assessment
        assessmentSummary = findViewById(R.id.assessment_summary);
        recommendations = findViewById(R.id.recommendations);
        
        // Setup spinners
        setupMunicipalitySpinner();
        setupBarangaySpinner();
    }

    private void setupMunicipalitySpinner() {
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, MUNICIPALITIES);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        municipalitySpinner.setAdapter(adapter);
        
        municipalitySpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                selectedMunicipality = MUNICIPALITIES[position];
                updateBarangaySpinner();
            }
            
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }

    private void setupBarangaySpinner() {
        ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, new String[]{});
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        barangaySpinner.setAdapter(adapter);
        
        barangaySpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                if (position > 0) {
                    selectedBarangay = (String) parent.getItemAtPosition(position);
                }
            }
            
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }

    private void updateBarangaySpinner() {
        String[] barangays = MUNICIPALITY_BARANGAYS.get(selectedMunicipality);
        if (barangays != null) {
            ArrayAdapter<String> adapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, barangays);
            adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
            barangaySpinner.setAdapter(adapter);
        }
    }

    private void setupClickListeners() {
        // Navigation buttons
        prevButton.setOnClickListener(v -> previousSection());
        nextButton.setOnClickListener(v -> nextSection());
        
        // Age input handler
        ageYearsInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            
            @Override
            public void afterTextChanged(android.text.Editable s) {
                String ageText = s.toString();
                if (!TextUtils.isEmpty(ageText)) {
                    int age = Integer.parseInt(ageText);
                    if (age < 1) {
                        ageMonthsInput.setVisibility(View.VISIBLE);
                    } else {
                        ageMonthsInput.setVisibility(View.GONE);
                    }
                    
                    // Show/hide immunization section
                    if (age <= 12) {
                        immunizationSection.setVisibility(View.VISIBLE);
                    } else {
                        immunizationSection.setVisibility(View.GONE);
                    }
                    
                    // Check pregnant section visibility
                    checkPregnantSectionVisibility();
                }
            }
        });
        
        // Sex change handler
        sexRadioGroup.setOnCheckedChangeListener((group, checkedId) -> {
            if (checkedId == R.id.sex_male) {
                selectedSex = "Male";
            } else if (checkedId == R.id.sex_female) {
                selectedSex = "Female";
            }
            checkPregnantSectionVisibility();
        });
        
        // BMI calculation
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
        
        // Meal analysis
        mealRecallInput.addTextChangedListener(new android.text.TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            
            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {}
            
            @Override
            public void afterTextChanged(android.text.Editable s) {
                analyzeMeal();
            }
        });
        
        // Lifestyle other field
        lifestyleRadioGroup.setOnCheckedChangeListener((group, checkedId) -> {
            if (checkedId == R.id.lifestyle_other) {
                selectedLifestyle = "Other";
                lifestyleOtherInput.setVisibility(View.VISIBLE);
            } else {
                if (checkedId == R.id.lifestyle_active) {
                    selectedLifestyle = "Active";
                } else if (checkedId == R.id.lifestyle_sedentary) {
                    selectedLifestyle = "Sedentary";
                }
                lifestyleOtherInput.setVisibility(View.GONE);
            }
        });
        
        // Family history validation
        fhNone.setOnCheckedChangeListener((buttonView, isChecked) -> {
            if (isChecked) {
                // Uncheck all other family history options
                fhDiabetes.setChecked(false);
                fhHypertension.setChecked(false);
                fhHeart.setChecked(false);
                fhKidney.setChecked(false);
                fhTb.setChecked(false);
                fhObesity.setChecked(false);
                fhMalnutrition.setChecked(false);
                fhOther.setChecked(false);
            }
        });
        
        // When any other family history option is checked, uncheck "None"
        android.widget.CompoundButton.OnCheckedChangeListener familyHistoryListener = (buttonView, isChecked) -> {
            if (isChecked && buttonView != fhNone) {
                fhNone.setChecked(false);
            }
        };
        
        fhDiabetes.setOnCheckedChangeListener(familyHistoryListener);
        fhHypertension.setOnCheckedChangeListener(familyHistoryListener);
        fhHeart.setOnCheckedChangeListener(familyHistoryListener);
        fhKidney.setOnCheckedChangeListener(familyHistoryListener);
        fhTb.setOnCheckedChangeListener(familyHistoryListener);
        fhObesity.setOnCheckedChangeListener(familyHistoryListener);
        fhMalnutrition.setOnCheckedChangeListener(familyHistoryListener);
        fhOther.setOnCheckedChangeListener(familyHistoryListener);
    }

    private void checkPregnantSectionVisibility() {
        String ageText = ageYearsInput.getText().toString();
        if (!TextUtils.isEmpty(ageText) && selectedSex.equals("Female")) {
            int age = Integer.parseInt(ageText);
            if (age >= 12 && age <= 50) {
                pregnantSection.setVisibility(View.VISIBLE);
            } else {
                pregnantSection.setVisibility(View.GONE);
            }
        } else {
            pregnantSection.setVisibility(View.GONE);
        }
    }

    private void calculateBMI() {
        String weightText = weightInput.getText().toString();
        String heightText = heightInput.getText().toString();
        
        if (!TextUtils.isEmpty(weightText) && !TextUtils.isEmpty(heightText)) {
            try {
                float weight = Float.parseFloat(weightText);
                float height = Float.parseFloat(heightText) / 100; // Convert cm to meters
                float bmi = weight / (height * height);
                
                bmiValue.setText(String.format("BMI: %.2f", bmi));
                
                String category;
                if (bmi < 18.5) category = "Underweight";
                else if (bmi < 25) category = "Normal";
                else if (bmi < 30) category = "Overweight";
                else category = "Obese";
                
                bmiCategory.setText("Category: " + category);
                bmiDisplay.setVisibility(View.VISIBLE);
            } catch (NumberFormatException e) {
                bmiDisplay.setVisibility(View.GONE);
            }
        } else {
            bmiDisplay.setVisibility(View.GONE);
        }
    }

    private void analyzeMeal() {
        String mealText = mealRecallInput.getText().toString().toLowerCase();
        if (TextUtils.isEmpty(mealText)) {
            mealAnalysis.setText("");
            return;
        }
        
        Map<String, String[]> foodGroups = new HashMap<>();
        foodGroups.put("carbs", new String[]{"rice", "bread", "pasta", "potato", "corn", "cereal", "oatmeal"});
        foodGroups.put("protein", new String[]{"meat", "fish", "chicken", "pork", "beef", "egg", "milk", "cheese", "beans", "tofu"});
        foodGroups.put("vegetables", new String[]{"vegetable", "carrot", "broccoli", "spinach", "lettuce", "tomato", "onion"});
        foodGroups.put("fruits", new String[]{"fruit", "apple", "banana", "orange", "mango", "grape"});
        
        List<String> foundGroups = new ArrayList<>();
        for (Map.Entry<String, String[]> entry : foodGroups.entrySet()) {
            for (String food : entry.getValue()) {
                if (mealText.contains(food)) {
                    foundGroups.add(entry.getKey());
                    break;
                }
            }
        }
        
        if (foundGroups.size() >= 3) {
            mealAnalysis.setText("✅ Balanced diet detected");
            mealAnalysis.setTextColor(getResources().getColor(android.R.color.holo_green_dark));
        } else {
            mealAnalysis.setText("⚠️ At Risk: Missing major food groups");
            mealAnalysis.setTextColor(getResources().getColor(android.R.color.holo_red_dark));
        }
    }

    private void showSection(int section) {
        currentSection = section;
        
        // Hide all sections
        for (View sectionView : sectionViews) {
            sectionView.setVisibility(View.GONE);
        }
        
        // Show current section
        if (section >= 1 && section <= TOTAL_SECTIONS) {
            sectionViews[section - 1].setVisibility(View.VISIBLE);
        }
        
        // Update progress
        progressText.setText(String.format("Section %d of %d", section, TOTAL_SECTIONS));
        progressBar.setProgress((section * 100) / TOTAL_SECTIONS);
        
        // Update navigation buttons
        prevButton.setEnabled(section > 1);
        if (section == TOTAL_SECTIONS) {
            nextButton.setText("Submit");
            generateFinalAssessment();
        } else {
            nextButton.setText("Next");
        }
    }

    private void previousSection() {
        if (currentSection > 1) {
            showSection(currentSection - 1);
        }
    }

    private void nextSection() {
        if (currentSection < TOTAL_SECTIONS) {
            if (validateCurrentSection()) {
                showSection(currentSection + 1);
            }
        } else {
            if (validateCurrentSection()) {
                submitScreening();
            }
        }
    }

    private boolean validateCurrentSection() {
        switch (currentSection) {
            case 1:
                return validateBasicInfo();
            case 2:
                return validateAnthropometric();
            case 3:
                return validateMealAssessment();
            case 4:
                return validateFamilyHistory();
            case 5:
                return validateLifestyle();
            case 6:
                return validateImmunization();
            case 7:
                return true; // Final assessment is read-only
            default:
                return true;
        }
    }

    private boolean validateBasicInfo() {
        if (TextUtils.isEmpty(selectedMunicipality)) {
            showError("Please select a municipality");
            return false;
        }
        
        if (TextUtils.isEmpty(selectedBarangay)) {
            showError("Please select a barangay");
            return false;
        }
        
        String ageText = ageYearsInput.getText().toString();
        if (TextUtils.isEmpty(ageText)) {
            showError("Please enter age");
            return false;
        }
        
        int age = Integer.parseInt(ageText);
        if (age < 0 || age > 120) {
            showError("Age cannot be negative or > 120 years");
            return false;
        }
        
        if (sexRadioGroup.getCheckedRadioButtonId() == -1) {
            showError("Please select sex");
            return false;
        }
        
        return true;
    }

    private boolean validateAnthropometric() {
        String weightText = weightInput.getText().toString();
        String heightText = heightInput.getText().toString();
        
        if (TextUtils.isEmpty(weightText)) {
            showError("Please enter weight");
            return false;
        }
        
        if (TextUtils.isEmpty(heightText)) {
            showError("Please enter height");
            return false;
        }
        
        float weight = Float.parseFloat(weightText);
        float height = Float.parseFloat(heightText);
        String ageText = ageYearsInput.getText().toString();
        int age = Integer.parseInt(ageText);
        
        if (age < 5 && weight > 50) {
            showError("Weight seems unusually high for age < 5");
            return false;
        }
        
        if (weight < 2 || weight > 250) {
            showError("Weight must be between 2-250 kg");
            return false;
        }
        
        if (age < 5 && height > 130) {
            showError("Height seems unusually high for age < 5");
            return false;
        }
        
        if (height < 30 || height > 250) {
            showError("Height must be between 30-250 cm");
            return false;
        }
        
        return true;
    }

    private boolean validateMealAssessment() {
        if (TextUtils.isEmpty(mealRecallInput.getText().toString())) {
            showError("Please describe the participant's meals");
            return false;
        }
        return true;
    }

    private boolean validateFamilyHistory() {
        boolean hasSelection = fhDiabetes.isChecked() || fhHypertension.isChecked() || 
                             fhHeart.isChecked() || fhKidney.isChecked() || fhTb.isChecked() || 
                             fhObesity.isChecked() || fhMalnutrition.isChecked() || 
                             fhOther.isChecked() || fhNone.isChecked();
        
        if (!hasSelection) {
            showError("Please select at least one option or choose None");
            return false;
        }
        
        return true;
    }

    private boolean validateLifestyle() {
        if (lifestyleRadioGroup.getCheckedRadioButtonId() == -1) {
            showError("Please select lifestyle");
            return false;
        }
        
        if (selectedLifestyle.equals("Other") && TextUtils.isEmpty(lifestyleOtherInput.getText().toString())) {
            showError("Please specify lifestyle");
            return false;
        }
        
        return true;
    }

    private boolean validateImmunization() {
        // Immunization is optional, only validate if section is visible and age <= 12
        String ageText = ageYearsInput.getText().toString();
        if (!TextUtils.isEmpty(ageText)) {
            int age = Integer.parseInt(ageText);
            if (age <= 12 && immunizationSection.getVisibility() == View.VISIBLE) {
                // Immunization validation could be added here if needed
                return true;
            }
        }
        return true;
    }

    private void generateFinalAssessment() {
        // Calculate BMI
        float bmi = 0;
        String weightText = weightInput.getText().toString();
        String heightText = heightInput.getText().toString();
        if (!TextUtils.isEmpty(weightText) && !TextUtils.isEmpty(heightText)) {
            float weight = Float.parseFloat(weightText);
            float height = Float.parseFloat(heightText) / 100;
            bmi = weight / (height * height);
        }
        
        // Determine BMI category
        String bmiCategory = "";
        if (bmi < 18.5) bmiCategory = "Underweight";
        else if (bmi < 25) bmiCategory = "Normal";
        else if (bmi < 30) bmiCategory = "Overweight";
        else bmiCategory = "Obese";
        
        // Analyze meal balance
        String mealText = mealRecallInput.getText().toString().toLowerCase();
        boolean isBalanced = false;
        if (!TextUtils.isEmpty(mealText)) {
            Map<String, String[]> foodGroups = new HashMap<>();
            foodGroups.put("carbs", new String[]{"rice", "bread", "pasta", "potato", "corn", "cereal", "oatmeal"});
            foodGroups.put("protein", new String[]{"meat", "fish", "chicken", "pork", "beef", "egg", "milk", "cheese", "beans", "tofu"});
            foodGroups.put("vegetables", new String[]{"vegetable", "carrot", "broccoli", "spinach", "lettuce", "tomato", "onion"});
            foodGroups.put("fruits", new String[]{"fruit", "apple", "banana", "orange", "mango", "grape"});
            
            List<String> foundGroups = new ArrayList<>();
            for (Map.Entry<String, String[]> entry : foodGroups.entrySet()) {
                for (String food : entry.getValue()) {
                    if (mealText.contains(food)) {
                        foundGroups.add(entry.getKey());
                        break;
                    }
                }
            }
            isBalanced = foundGroups.size() >= 3;
        }
        
        // Check immunization completeness
        boolean immunizationComplete = true;
        String ageText = ageYearsInput.getText().toString();
        if (!TextUtils.isEmpty(ageText)) {
            int age = Integer.parseInt(ageText);
            if (age <= 12) {
                immunizationComplete = immBcg.isChecked() && immDpt.isChecked() && 
                                     immPolio.isChecked() && immMeasles.isChecked() && 
                                     immHepb.isChecked() && immVitamina.isChecked();
            }
        }
        
        // Build assessment summary
        StringBuilder summary = new StringBuilder();
        summary.append("The participant is ").append(bmiCategory).append(" ");
        summary.append("with a ").append(isBalanced ? "balanced" : "unbalanced").append(" diet ");
        summary.append("and ").append(immunizationComplete ? "complete" : "incomplete").append(" immunization. ");
        
        // Add risk factors
        List<String> riskFactors = new ArrayList<>();
        if (fhDiabetes.isChecked() || fhHypertension.isChecked() || fhHeart.isChecked() || 
            fhKidney.isChecked() || fhTb.isChecked() || fhObesity.isChecked() || 
            fhMalnutrition.isChecked()) {
            riskFactors.add("family history");
        }
        if (selectedLifestyle.equals("Sedentary")) {
            riskFactors.add("sedentary lifestyle");
        }
        
        if (!riskFactors.isEmpty()) {
            summary.append("Risk factors: ").append(String.join(" and ", riskFactors)).append(". ");
        } else {
            summary.append("No significant risk factors identified. ");
        }
        
        assessmentSummary.setText(summary.toString());
        
        // Generate recommendations
        StringBuilder recommendationsText = new StringBuilder();
        if (bmiCategory.equals("Underweight")) {
            recommendationsText.append("• Nutrition counseling\n");
            recommendationsText.append("• DOH feeding program referral\n");
        } else if (bmiCategory.equals("Overweight") || bmiCategory.equals("Obese")) {
            recommendationsText.append("• Weight management program\n");
            recommendationsText.append("• Physical activity recommendations\n");
        }
        
        if (!isBalanced) {
            recommendationsText.append("• Dietary counseling\n");
            recommendationsText.append("• Meal planning assistance\n");
        }
        
        if (!immunizationComplete && !TextUtils.isEmpty(ageText) && Integer.parseInt(ageText) <= 12) {
            recommendationsText.append("• Referral to barangay immunization clinic\n");
        }
        
        if (selectedLifestyle.equals("Sedentary")) {
            recommendationsText.append("• Physical activity recommendations\n");
        }
        
        if (recommendationsText.length() == 0) {
            recommendationsText.append("Continue current healthy lifestyle and regular check-ups.");
        }
        
        recommendations.setText(recommendationsText.toString());
    }

    private void submitScreening() {
        try {
            // Collect all data
            JSONObject screeningData = new JSONObject();
            screeningData.put("municipality", selectedMunicipality);
            screeningData.put("barangay", selectedBarangay);
            screeningData.put("age", ageYearsInput.getText().toString());
            screeningData.put("age_months", ageMonthsInput.getText().toString());
            screeningData.put("sex", selectedSex);
            screeningData.put("pregnant", selectedPregnant);
            screeningData.put("weight", weightInput.getText().toString());
            screeningData.put("height", heightInput.getText().toString());
            screeningData.put("meal_recall", mealRecallInput.getText().toString());
            screeningData.put("lifestyle", selectedLifestyle);
            screeningData.put("lifestyle_other", lifestyleOtherInput.getText().toString());
            
            // Family history
            JSONArray familyHistory = new JSONArray();
            if (fhDiabetes.isChecked()) familyHistory.put("Diabetes");
            if (fhHypertension.isChecked()) familyHistory.put("Hypertension");
            if (fhHeart.isChecked()) familyHistory.put("Heart Disease");
            if (fhKidney.isChecked()) familyHistory.put("Kidney Disease");
            if (fhTb.isChecked()) familyHistory.put("Tuberculosis");
            if (fhObesity.isChecked()) familyHistory.put("Obesity");
            if (fhMalnutrition.isChecked()) familyHistory.put("Malnutrition");
            if (fhOther.isChecked()) familyHistory.put("Other");
            if (fhNone.isChecked()) familyHistory.put("None");
            screeningData.put("family_history", familyHistory);
            
            // Immunization
            JSONArray immunization = new JSONArray();
            if (immBcg.isChecked()) immunization.put("BCG");
            if (immDpt.isChecked()) immunization.put("DPT");
            if (immPolio.isChecked()) immunization.put("Polio");
            if (immMeasles.isChecked()) immunization.put("Measles");
            if (immHepb.isChecked()) immunization.put("Hepatitis B");
            if (immVitamina.isChecked()) immunization.put("Vitamin A");
            screeningData.put("immunization", immunization);
            
            // Assessment summary
            screeningData.put("assessment_summary", assessmentSummary.getText().toString());
            screeningData.put("recommendations", recommendations.getText().toString());
            
            // Save to local storage and send to server
            saveScreeningData(screeningData);
            
            // Show success message
            new AlertDialog.Builder(this)
                .setTitle("Screening Complete")
                .setMessage("Your comprehensive nutrition screening has been submitted successfully!")
                .setPositiveButton("OK", (dialog, which) -> {
                    Intent intent = new Intent(this, MainActivity.class);
                    intent.setFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_NEW_TASK);
                    startActivity(intent);
                    finish();
                })
                .setCancelable(false)
                .show();
                
        } catch (Exception e) {
            Log.e(TAG, "Error submitting screening", e);
            showError("Error submitting screening: " + e.getMessage());
        }
    }

    private void saveScreeningData(JSONObject screeningData) {
        // Save to local storage
        ScreeningResultStore.saveScreeningData(this, screeningData.toString());
        
        // Send to server (implement API call here)
        // This would typically involve making an HTTP request to your server
        Log.d(TAG, "Screening data: " + screeningData.toString());
    }

    private void showError(String message) {
        Toast.makeText(this, message, Toast.LENGTH_LONG).show();
    }
}
