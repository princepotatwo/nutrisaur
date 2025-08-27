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
// Removed WebViewAPIClient - using direct HTTP requests

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

    // Question flow tracking
    private int currentQuestion = 0;
    private static final int TOTAL_QUESTIONS = 2; // Total number of pages
    
    // Question cards - each page gets its own card
    private View questionCards[];
    
    // Navigation buttons
    private Button prevButton, nextButton;
    
    // Section A - Basic Info
    private EditText childWeightInput;
    private EditText childHeightInput;
    private EditText dietaryDiversityInput;
    private EditText muacInput; // MUAC input for children 6-59 months
    private TextView muacLabel; // MUAC label for children 6-59 months
    private TextView muacSubtitle; // MUAC subtitle for children 6-59 months
    private Button genderBoy, genderGirl;
    private String selectedGender = "";
    private Button swellingYes, swellingNo;
    private Boolean hasSwelling = null;
    private Button weightLoss10Plus, weightLoss5To10, weightLossLess5;
    private String weightLossStatus = "";
    private Button feedingGood, feedingModerate, feedingPoor;
    private String feedingBehavior = "";
    
    // Physical Signs Assessment
    private Button physicalThin, physicalShorter, physicalWeak, physicalNone;
    private Boolean isThin = null, isShorter = null, isWeak = null, isNone = null;
    
    // Additional Clinical Risk Factors
    private Button illnessYes, illnessNo;
    private Button eatingDifficultyYes, eatingDifficultyNo;
    private Button foodInsecurityYes, foodInsecurityNo;
    private Button micronutrientYes, micronutrientNo;
    private Button functionalDeclineYes, functionalDeclineNo;
    private TextView functionalDeclineLabel;
    private TextView functionalDeclineSubtitle;
    private LinearLayout functionalDeclineButtons;
    
    private Boolean hasRecentIllness = null;
    private Boolean hasEatingDifficulty = null;
    private Boolean hasFoodInsecurity = null;
    private Boolean hasMicronutrientDeficiency = null;
    private Boolean hasFunctionalDecline = null;

    private Button birthdayPickerBtn;

    private java.util.Calendar selectedBirthday;

    // 1. Add fields
    private Spinner barangaySpinner, incomeSpinner;
    private String selectedBarangay = "";
    private String selectedIncome = "";

    private static final String[] BATAAN_BARANGAYS = {
        // Abucay
        "ABUCAY", "Bangkal", "Calaylayan (Pob.)", "Capitangan", "Gabon", "Laon (Pob.)", "Mabatang", "Omboy", "Salian", "Wawa (Pob.)",
        // Bagac
        "BAGAC", "Bagumbayan (Pob.)", "Banawang", "Binuangan", "Binukawan", "Ibaba", "Ibis", "Pag-asa (Wawa-Sibacan)", "Parang", "Paysawan", "Quinawan", "San Antonio", "Saysain", "Tabing-Ilog (Pob.)", "Atilano L. Ricardo",
        // Balanga City
        "CITY OF BALANGA (Capital)", "Bagumbayan", "Cabog-Cabog", "Munting Batangas (Cadre)", "Cataning", "Central", "Cupang Proper", "Cupang West", "Dangcol (Bernabe)", "Ibayo", "Malabia", "Poblacion", "Pto. Rivas Ibaba", "Pto. Rivas Itaas", "San Jose", "Sibacan", "Camacho", "Talisay", "Tanato", "Tenejero", "Tortugas", "Tuyo", "Bagong Silang", "Cupang North", "Doña Francisca", "Lote",
        // Dinalupihan
        "DINALUPIHAN", "Bangal", "Bonifacio (Pob.)", "Burgos (Pob.)", "Colo", "Daang Bago", "Dalao", "Del Pilar (Pob.)", "Gen. Luna (Pob.)", "Gomez (Pob.)", "Happy Valley", "Kataasan", "Layac", "Luacan", "Mabini Proper (Pob.)", "Mabini Ext. (Pob.)", "Magsaysay", "Naparing", "New San Jose", "Old San Jose", "Padre Dandan (Pob.)", "Pag-asa", "Pagalanggang", "Pinulot", "Pita", "Rizal (Pob.)", "Roosevelt", "Roxas (Pob.)", "Saguing", "San Benito", "San Isidro (Pob.)", "San Pablo (Bulate)", "San Ramon", "San Simon", "Santo Niño", "Sapang Balas", "Santa Isabel (Tabacan)", "Torres Bugauen (Pob.)", "Tucop", "Zamora (Pob.)", "Aquino", "Bayan-bayanan", "Maligaya", "Payangan", "Pentor", "Tubo-tubo", "Jose C. Payumo, Jr.",
        // Hermosa
        "HERMOSA", "A. Rivera (Pob.)", "Almacen", "Bacong", "Balsic", "Bamban", "Burgos-Soliman (Pob.)", "Cataning (Pob.)", "Culis", "Daungan (Pob.)", "Mabiga", "Mabuco", "Maite", "Mambog - Mandama", "Palihan", "Pandatung", "Pulo", "Saba", "San Pedro (Pob.)", "Santo Cristo (Pob.)", "Sumalo", "Tipo", "Judge Roman Cruz Sr. (Mandama)", "Sacrifice Valley",
        // Limay
        "LIMAY", "Alangan", "Kitang I", "Kitang 2 & Luz", "Lamao", "Landing", "Poblacion", "Reformista", "Townsite", "Wawa", "Duale", "San Francisco de Asis", "St. Francis II",
        // Mariveles
        "MARIVELES", "Alas-asin", "Alion", "Batangas II", "Cabcaben", "Lucanin", "Baseco Country (Nassco)", "Poblacion", "San Carlos", "San Isidro", "Sisiman", "Balon-Anito", "Biaan", "Camaya", "Ipag", "Malaya", "Maligaya", "Mt. View", "Townsite",
        // Morong
        "MORONG", "Binaritan", "Mabayo", "Nagbalayong", "Poblacion", "Sabang",
        // Orani
        "ORANI", "Bagong Paraiso (Pob.)", "Balut (Pob.)", "Bayan (Pob.)", "Calero (Pob.)", "Paking-Carbonero (Pob.)", "Centro II (Pob.)", "Dona", "Kaparangan", "Masantol", "Mulawin", "Pag-asa", "Palihan (Pob.)", "Pantalan Bago (Pob.)", "Pantalan Luma (Pob.)", "Parang Parang (Pob.)", "Centro I (Pob.)", "Sibul", "Silahis", "Tala", "Talimundoc", "Tapulao", "Tenejero (Pob.)", "Tugatog", "Wawa (Pob.)", "Apollo", "Kabalutan", "Maria Fe", "Puksuan", "Tagumpay",
        // Orion
        "ORION", "Arellano (Pob.)", "Bagumbayan (Pob.)", "Balagtas (Pob.)", "Balut (Pob.)", "Bantan", "Bilolo", "Calungusan", "Camachile", "Daang Bago (Pob.)", "Daang Bilolo (Pob.)", "Daang Pare", "General Lim (Kaput)", "Kapunitan", "Lati (Pob.)", "Lusungan (Pob.)", "Puting Buhangin", "Sabatan", "San Vicente (Pob.)", "Santo Domingo", "Villa Angeles (Pob.)", "Wakas (Pob.)", "Wawa (Pob.)", "Santa Elena",
        // Pilar
        "PILAR", "Ala-uli", "Bagumbayan", "Balut I", "Balut II", "Bantan Munti", "Burgos", "Del Rosario (Pob.)", "Diwa", "Landing", "Liyang", "Nagwaling", "Panilao", "Pantingan", "Poblacion", "Rizal (Pob.)", "Santa Rosa", "Wakas North", "Wakas South", "Wawa",
        // Samal
        "SAMAL", "East Calaguiman (Pob.)", "East Daang Bago (Pob.)", "Ibaba (Pob.)", "Imelda", "Lalawigan", "Palili", "San Juan (Pob.)", "San Roque (Pob.)", "Santa Lucia", "Sapa", "Tabing Ilog", "Gugo", "West Calaguiman (Pob.)", "West Daang Bago (Pob.)"
    };
    private static final String[] INCOME_BRACKETS = {"Below PHP 12,030/month (Below poverty line)", "PHP 12,031–20,000/month (Low)", "PHP 20,001–40,000/month (Middle)", "Above PHP 40,000/month (High)"};

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_screening_form);
        ScreeningResultStore.init(this);
        initializeViews();
        setupClickListeners();
        initializeButtonStates(); // Initialize button states
        
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
        questionCards[0] = findViewById(R.id.question_1_card);  // Birthday
        questionCards[1] = findViewById(R.id.question_2_card);  // Weight & Height
        
        // Section A
        childWeightInput = findViewById(R.id.child_weight_input);
        childHeightInput = findViewById(R.id.child_height_input);
        dietaryDiversityInput = findViewById(R.id.dietary_diversity_input);
        muacInput = findViewById(R.id.muac_input);
        muacLabel = findViewById(R.id.muac_label);
        muacSubtitle = findViewById(R.id.muac_subtitle);
        genderBoy = findViewById(R.id.gender_boy);
        genderGirl = findViewById(R.id.gender_girl);
        swellingYes = findViewById(R.id.swelling_yes);
        swellingNo = findViewById(R.id.swelling_no);
        weightLoss10Plus = findViewById(R.id.weightloss_10plus);
        weightLoss5To10 = findViewById(R.id.weightloss_5to10);
        weightLossLess5 = findViewById(R.id.weightloss_less5);
        feedingGood = findViewById(R.id.feeding_good);
        feedingModerate = findViewById(R.id.feeding_moderate);
        feedingPoor = findViewById(R.id.feeding_poor);
        birthdayPickerBtn = findViewById(R.id.birthday_picker_btn);
        
        // Additional Clinical Risk Factors
        illnessYes = findViewById(R.id.illness_yes);
        illnessNo = findViewById(R.id.illness_no);
        eatingDifficultyYes = findViewById(R.id.eating_difficulty_yes);
        eatingDifficultyNo = findViewById(R.id.eating_difficulty_no);
        foodInsecurityYes = findViewById(R.id.food_insecurity_yes);
        foodInsecurityNo = findViewById(R.id.food_insecurity_no);
        micronutrientYes = findViewById(R.id.micronutrient_yes);
        micronutrientNo = findViewById(R.id.micronutrient_no);
        functionalDeclineYes = findViewById(R.id.functional_decline_yes);
        functionalDeclineNo = findViewById(R.id.functional_decline_no);
        functionalDeclineLabel = findViewById(R.id.functional_decline_label);
        functionalDeclineSubtitle = findViewById(R.id.functional_decline_subtitle);
        functionalDeclineButtons = findViewById(R.id.functional_decline_buttons);
        
        // Physical Signs Assessment
        physicalThin = findViewById(R.id.physical_thin);
        physicalShorter = findViewById(R.id.physical_shorter);
        physicalWeak = findViewById(R.id.physical_weak);
        physicalNone = findViewById(R.id.physical_none);

        // 2. In initializeViews(), after genderGirl = ...
        barangaySpinner = findViewById(R.id.barangay_spinner);
        incomeSpinner = findViewById(R.id.income_spinner);
        // Populate barangay spinner (alphabetical, searchable)
        String[] sortedBarangays = java.util.Arrays.copyOf(BATAAN_BARANGAYS, BATAAN_BARANGAYS.length);
        java.util.Arrays.sort(sortedBarangays, String::compareToIgnoreCase);
        ArrayAdapter<String> barangayAdapter = new ArrayAdapter<>(this, R.layout.spinner_item, sortedBarangays);
        barangayAdapter.setDropDownViewResource(R.layout.spinner_dropdown_item);
        barangaySpinner.setAdapter(barangayAdapter);
        barangaySpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                selectedBarangay = (String) parent.getItemAtPosition(position);
            }
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
        // Show searchable picker when tapping spinner
        barangaySpinner.setOnTouchListener((v, event) -> {
            if (event.getAction() == android.view.MotionEvent.ACTION_UP) {
                showBarangayPickerDialog(sortedBarangays, value -> {
                    selectedBarangay = value;
                    int idx = java.util.Arrays.asList(sortedBarangays).indexOf(value);
                    if (idx >= 0) barangaySpinner.setSelection(idx);
                });
                return true;
            }
            return false;
        });
        // Populate income spinner
        ArrayAdapter<String> incomeAdapter = new ArrayAdapter<>(this, R.layout.spinner_item, INCOME_BRACKETS);
        incomeAdapter.setDropDownViewResource(R.layout.spinner_dropdown_item);
        incomeSpinner.setAdapter(incomeAdapter);
        incomeSpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                selectedIncome = (String) parent.getItemAtPosition(position);
            }
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }

    private interface OnBarangayPicked { void onPick(String value); }

    private void showBarangayPickerDialog(String[] items, OnBarangayPicked callback) {
        android.app.AlertDialog.Builder builder = new android.app.AlertDialog.Builder(this);

        android.widget.LinearLayout container = new android.widget.LinearLayout(this);
        container.setOrientation(android.widget.LinearLayout.VERTICAL);
        int pad = (int) (16 * getResources().getDisplayMetrics().density);
        container.setPadding(pad, pad, pad, pad);
        container.setBackgroundResource(R.drawable.rounded_bg);

        // Custom header
        android.widget.TextView header = new android.widget.TextView(this);
        header.setText("Select Barangay");
        header.setTextSize(18);
        header.setTextColor(0xFF222222);
        header.setTypeface(android.graphics.Typeface.DEFAULT_BOLD);
        header.setPadding(pad, pad, pad, pad / 2);
        container.addView(header);

        android.widget.EditText search = new android.widget.EditText(this);
        search.setHint("Search barangay");
        search.setSingleLine(true);
        search.setBackgroundResource(R.drawable.edit_text_outline);
        search.setPadding(pad, pad / 2, pad, pad / 2);
        container.addView(search);

        // Build municipality -> barangays map from provided list (list contains municipality names as separators)
        java.util.Set<String> municipalities = new java.util.LinkedHashSet<>();
        municipalities.add("ABUCAY");
        municipalities.add("BAGAC");
        municipalities.add("CITY OF BALANGA (Capital)");
        municipalities.add("DINALUPIHAN");
        municipalities.add("HERMOSA");
        municipalities.add("LIMAY");
        municipalities.add("MARIVELES");
        municipalities.add("MORONG");
        municipalities.add("ORANI");
        municipalities.add("ORION");
        municipalities.add("PILAR");
        municipalities.add("SAMAL");

        java.util.Map<String, java.util.List<String>> muniToBarangays = new java.util.LinkedHashMap<>();
        String currentMuni = null;
        // Build map using the authoritative listing (image provided) — treat municipality lines as headers in array
        currentMuni = null;
        for (String s : BATAAN_BARANGAYS) {
            if (municipalities.contains(s)) { currentMuni = s; muniToBarangays.putIfAbsent(s, new java.util.ArrayList<>()); }
            else if (currentMuni != null) { muniToBarangays.get(currentMuni).add(s); }
        }
        // Sort barangays within each municipality
        for (java.util.List<String> list : muniToBarangays.values()) {
            java.util.Collections.sort(list, String::compareToIgnoreCase);
        }
        // Build initial display list
        java.util.List<String> display = new java.util.ArrayList<>();
        for (String muni : muniToBarangays.keySet()) {
            display.add("— " + muni + " —");
            for (String brgy : muniToBarangays.get(muni)) display.add(brgy);
        }

        android.widget.ListView listView = new android.widget.ListView(this);
        android.widget.ArrayAdapter<String> adapter = new android.widget.ArrayAdapter<String>(this, android.R.layout.simple_list_item_1, display) {
            @Override
            public boolean isEnabled(int position) {
                String v = getItem(position);
                return v != null && !v.startsWith("— ");
            }
            @Override
            public android.view.View getView(int position, android.view.View convertView, android.view.ViewGroup parent) {
                android.view.View v = super.getView(position, convertView, parent);
                String t = getItem(position);
                if (t != null && t.startsWith("— ")) {
                    ((android.widget.TextView) v).setTextColor(0xFF888888);
                    ((android.widget.TextView) v).setTextSize(14);
                    v.setBackgroundColor(0x00FFFFFF);
                } else {
                    ((android.widget.TextView) v).setTextColor(0xFF222222);
                    ((android.widget.TextView) v).setTextSize(16);
                }
                return v;
            }
        };
        listView.setAdapter(adapter);
        container.addView(listView);

        builder.setView(container);
        android.app.AlertDialog dialog = builder.create();
        listView.setOnItemClickListener((parent, view, position, id) -> {
            String value = adapter.getItem(position);
            if (value != null && !value.startsWith("— ") && callback != null) callback.onPick(value);
            dialog.dismiss();
        });
        search.addTextChangedListener(new android.text.TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override public void onTextChanged(CharSequence s, int start, int before, int count) {
                // Filter by query within each municipality, rebuild with muni headers
                String q = s == null ? "" : s.toString().trim().toLowerCase();
                java.util.List<String> disp = new java.util.ArrayList<>();
                for (String muni : muniToBarangays.keySet()) {
                    java.util.List<String> list = muniToBarangays.get(muni);
                    java.util.List<String> filtered = new java.util.ArrayList<>();
                    for (String brgy : list) if (brgy.toLowerCase().contains(q)) filtered.add(brgy);
                    if (!filtered.isEmpty() || muni.toLowerCase().contains(q)) {
                        disp.add("— " + muni + " —");
                        for (String br : filtered.isEmpty() ? list : filtered) disp.add(br);
                    }
                }
                adapter.clear();
                adapter.addAll(disp);
            }
            @Override public void afterTextChanged(android.text.Editable s) {}
        });
        dialog.show();
        if (dialog.getWindow() != null) {
            dialog.getWindow().setBackgroundDrawable(new android.graphics.drawable.ColorDrawable(android.graphics.Color.TRANSPARENT));
        }
    }

    private void setupClickListeners() {
        // Navigation buttons
        prevButton.setOnClickListener(v -> previousQuestion());
        nextButton.setOnClickListener(v -> nextQuestion());
        
        // Section A - Gender selection
        genderBoy.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            selectedGender = "boy";
            updateButtonStatesForAction("gender", v);
        });
        genderGirl.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            selectedGender = "girl";
            updateButtonStatesForAction("gender", v);
        });
        
        // Swelling
        swellingYes.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasSwelling = true;
            updateButtonStatesForAction("swelling", v);
            // Show warning about swelling but continue with the form
            Toast.makeText(this, "⚠️ Swelling detected - this indicates high risk. Please complete all questions.", Toast.LENGTH_LONG).show();
        });
        swellingNo.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasSwelling = false;
            updateButtonStatesForAction("swelling", v);
        });
        
        // Weight loss/failure to gain
        weightLoss10Plus.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            weightLossStatus = ">10%";
            updateButtonStatesForAction("weightLoss", v);
        });
        weightLoss5To10.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            weightLossStatus = "5-10%";
            updateButtonStatesForAction("weightLoss", v);
        });
        weightLossLess5.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            weightLossStatus = "<5% or none";
            updateButtonStatesForAction("weightLoss", v);
        });
        
        // Dietary diversity
        feedingGood.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            feedingBehavior = "good appetite";
            updateButtonStatesForAction("feeding", v);
        });
        feedingModerate.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            feedingBehavior = "moderate appetite";
            updateButtonStatesForAction("feeding", v);
        });
        feedingPoor.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            feedingBehavior = "poor appetite";
            updateButtonStatesForAction("feeding", v);
        });
        
        // Physical Signs Assessment - Fixed logic
        physicalThin.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            // If selecting "thin", unselect "none" and update states
            if (isThin == null || !isThin) {
                isThin = true;
                isNone = false; // Unselect "none" when selecting other signs
            } else {
                isThin = false;
            }
            updateButtonStatesForAction("physical", v);
        });
        physicalShorter.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            // If selecting "shorter", unselect "none" and update states
            if (isShorter == null || !isShorter) {
                isShorter = true;
                isNone = false; // Unselect "none" when selecting other signs
            } else {
                isShorter = false;
            }
            updateButtonStatesForAction("physical", v);
        });
        physicalWeak.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            // If selecting "weak", unselect "none" and update states
            if (isWeak == null || !isWeak) {
                isWeak = true;
                isNone = false; // Unselect "none" when selecting other signs
            } else {
                isWeak = false;
            }
            updateButtonStatesForAction("physical", v);
        });
        physicalNone.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            // If selecting "none", unselect all other signs
            if (isNone == null || !isNone) {
                isNone = true;
                isThin = false;      // Unselect other signs
                isShorter = false;
                isWeak = false;
            } else {
                isNone = false;
            }
            updateButtonStatesForAction("physical", v);
        });
        
        // Additional Clinical Risk Factors
        illnessYes.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasRecentIllness = true;
            updateButtonStatesForAction("illness", v);
        });
        illnessNo.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasRecentIllness = false;
            updateButtonStatesForAction("illness", v);
        });
        eatingDifficultyYes.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasEatingDifficulty = true;
            updateButtonStatesForAction("eatingDifficulty", v);
        });
        eatingDifficultyNo.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasEatingDifficulty = false;
            updateButtonStatesForAction("eatingDifficulty", v);
        });
        foodInsecurityYes.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasFoodInsecurity = true;
            updateButtonStatesForAction("foodInsecurity", v);
        });
        foodInsecurityNo.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasFoodInsecurity = false;
            updateButtonStatesForAction("foodInsecurity", v);
        });
        micronutrientYes.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasMicronutrientDeficiency = true;
            updateButtonStatesForAction("micronutrient", v);
        });
        micronutrientNo.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasMicronutrientDeficiency = false;
            updateButtonStatesForAction("micronutrient", v);
        });
        functionalDeclineYes.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasFunctionalDecline = true;
            updateButtonStatesForAction("functionalDecline", v);
        });
        functionalDeclineNo.setOnClickListener(v -> {
            // Add press animation
            v.animate()
                .scaleX(0.95f)
                .scaleY(0.95f)
                .setDuration(100)
                .withEndAction(() -> {
                    v.animate()
                        .scaleX(1.0f)
                        .scaleY(1.0f)
                        .setDuration(100)
                        .start();
                })
                .start();
            
            hasFunctionalDecline = false;
            updateButtonStatesForAction("functionalDecline", v);
        });
        
        // Birthday picker
        birthdayPickerBtn.setOnClickListener(v -> showBirthdayPicker());
    }

    private void setupYesNoButtons(Button yesBtn, Button noBtn, String field) {
        yesBtn.setOnClickListener(v -> {
            setBooleanField(field, true);
            updateButtonStates(yesBtn, noBtn);
        });
        noBtn.setOnClickListener(v -> {
            setBooleanField(field, false);
            updateButtonStates(noBtn, yesBtn);
        });
    }

    private void setBooleanField(String field, boolean value) {
        switch (field) {
            case "illness":
                hasRecentIllness = value;
                break;
            case "eatingDifficulty":
                hasEatingDifficulty = value;
                break;
            case "foodInsecurity":
                hasFoodInsecurity = value;
                break;
            case "micronutrient":
                hasMicronutrientDeficiency = value;
                break;
            case "functionalDecline":
                hasFunctionalDecline = value;
                break;
        }
    }

    private void updateButtonStates(Button selected, Button... unselected) {
        // Set the selected button state with dark grey background
        selected.setSelected(true);
        selected.setElevation(8f); // Higher elevation for selected state
        selected.setBackgroundColor(0xFF424242); // Dark grey for selected state
        
        // Clear the selected state for all unselected buttons
        for (Button btn : unselected) {
            btn.setSelected(false);
            btn.setElevation(6f); // Normal elevation for unselected state
            btn.setBackgroundColor(0xFFCCCCCC); // Lighter grey for unselected state - more distinguishable
        }
        
        // Force a redraw to show the visual changes
        selected.invalidate();
        for (Button btn : unselected) {
            btn.invalidate();
        }
        
        // Add haptic feedback for better user experience
        selected.performHapticFeedback(android.view.HapticFeedbackConstants.VIRTUAL_KEY);
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
        nextButton.setText(question == TOTAL_QUESTIONS - 1 ? "Submit" : "Next Page →");
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
            }
        }
    }

    private void showBirthdayPicker() {
        DatePickerDialog.OnDateSetListener dateSetListener = (view, year, month, dayOfMonth) -> {
            selectedBirthday = java.util.Calendar.getInstance();
            selectedBirthday.set(year, month, dayOfMonth);
            
            // Calculate age
            int ageMonths = calculateAgeInMonths(selectedBirthday, java.util.Calendar.getInstance());
            
            // Show/hide MUAC field based on age
            updateMUACVisibility(ageMonths);
            
            // Update birthday button text
            birthdayPickerBtn.setText(String.format("%d/%d/%d", month + 1, dayOfMonth, year));
        };
        
        DatePickerDialog datePickerDialog = new DatePickerDialog(this, dateSetListener, 
            java.util.Calendar.getInstance().get(java.util.Calendar.YEAR),
            java.util.Calendar.getInstance().get(java.util.Calendar.MONTH),
            java.util.Calendar.getInstance().get(java.util.Calendar.DAY_OF_MONTH));
        
        datePickerDialog.show();
    }
    

    
    private void updateMUACVisibility(int ageMonths) {
        // Show MUAC field only for children 6-59 months
        if (ageMonths >= 6 && ageMonths <= 59) {
            muacLabel.setVisibility(View.VISIBLE);
            muacSubtitle.setVisibility(View.VISIBLE);
            muacInput.setVisibility(View.VISIBLE);
        } else {
            muacLabel.setVisibility(View.GONE);
            muacSubtitle.setVisibility(View.GONE);
            muacInput.setVisibility(View.GONE);
        }
        
        // Show functional decline field for older adults (60+ years)
        if (ageMonths >= 720) { // 60 years = 720 months
            functionalDeclineLabel.setVisibility(View.VISIBLE);
            functionalDeclineSubtitle.setVisibility(View.VISIBLE);
            functionalDeclineButtons.setVisibility(View.VISIBLE);
        } else {
            functionalDeclineLabel.setVisibility(View.GONE);
            functionalDeclineSubtitle.setVisibility(View.GONE);
            functionalDeclineButtons.setVisibility(View.GONE);
        }
    }

    private int calculateAgeInMonths(java.util.Calendar birth, java.util.Calendar now) {
        int years = now.get(java.util.Calendar.YEAR) - birth.get(java.util.Calendar.YEAR);
        int months = now.get(java.util.Calendar.MONTH) - birth.get(java.util.Calendar.MONTH);
        int totalMonths = years * 12 + months;
        if (now.get(java.util.Calendar.DAY_OF_MONTH) < birth.get(java.util.Calendar.DAY_OF_MONTH)) {
            totalMonths--;
        }
        return Math.max(totalMonths, 0);
    }

    private boolean validateCurrentQuestion() {
        switch (currentQuestion) {
            case 0: // Page 1: Basic Information & Measurements
                if (selectedBirthday == null) {
                    Toast.makeText(this, "Please select birthday", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(childWeightInput.getText().toString())) {
                    childWeightInput.setError("Weight is required");
                    return false;
                }
                if (TextUtils.isEmpty(childHeightInput.getText().toString())) {
                    childHeightInput.setError("Height is required");
                    return false;
                }
                if (TextUtils.isEmpty(selectedGender)) {
                    Toast.makeText(this, "Please select gender", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(selectedBarangay)) {
                    Toast.makeText(this, "Please select your barangay", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(selectedIncome)) {
                    Toast.makeText(this, "Please select your household income", Toast.LENGTH_SHORT).show();
                    return false;
                }
                break;
            case 1: // Page 2: Health Assessment & Risk Factors
                if (hasSwelling == null) {
                    Toast.makeText(this, "Please answer swelling question", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(weightLossStatus)) {
                    Toast.makeText(this, "Please answer weight loss question", Toast.LENGTH_SHORT).show();
                    return false;
                }
                if (TextUtils.isEmpty(dietaryDiversityInput.getText().toString())) {
                    dietaryDiversityInput.setError("Dietary diversity is required");
                    return false;
                }
                if (TextUtils.isEmpty(feedingBehavior)) {
                    Toast.makeText(this, "Please select feeding behavior", Toast.LENGTH_SHORT).show();
                    return false;
                }
                // No specific validation for physical signs, just ensure at least one is selected
                // Check if at least one physical sign has been answered (either Yes or No)
                // Since these are now Boolean wrapper classes, they can be null when not answered
                boolean hasAnsweredPhysicalSigns = (isThin != null) || (isShorter != null) || (isWeak != null) || (isNone != null);
                
                if (!hasAnsweredPhysicalSigns) {
                    Toast.makeText(this, "Please select at least one physical sign", Toast.LENGTH_SHORT).show();
                    return false;
                }
                // No specific validation for clinical risk factors, just ensure at least one is selected
                // Check if at least one clinical risk factor has been answered (either Yes or No)
                // Since these are now Boolean wrapper classes, they can be null when not answered
                boolean hasAnsweredClinicalFactors = (hasRecentIllness != null) || 
                                                   (hasEatingDifficulty != null) || 
                                                   (hasFoodInsecurity != null) || 
                                                   (hasMicronutrientDeficiency != null) || 
                                                   (hasFunctionalDecline != null);
                
                if (!hasAnsweredClinicalFactors) {
                    Toast.makeText(this, "Please answer all clinical risk factor questions", Toast.LENGTH_SHORT).show();
                    return false;
                }
                break;
        }
        return true;
    }

    private void submitForm() {
        if (!validateCurrentQuestion()) {
            return;
        }

        // Get form data
        String weightStr = childWeightInput.getText().toString();
        String heightStr = childHeightInput.getText().toString();
        String dietaryDiversityStr = dietaryDiversityInput.getText().toString();

        if (TextUtils.isEmpty(weightStr) || TextUtils.isEmpty(heightStr) || TextUtils.isEmpty(dietaryDiversityStr)) {
            Toast.makeText(this, "Please fill in all fields", Toast.LENGTH_SHORT).show();
            return;
        }

        double weight = Double.parseDouble(weightStr);
        double height = Double.parseDouble(heightStr);
        int dietaryGroups = Integer.parseInt(dietaryDiversityStr);

        // Calculate risk score
        int riskScore = calculateRiskScore(weight, height, dietaryGroups);

        // Save to local database
        saveScreeningResults(riskScore);

        // Show result
        showScreeningResult(riskScore);
    }

    private int calculateRiskScore(double weight, double height, int dietaryGroups) {
        int score = 0;
        
        // --- Evidence-based anthropometric scoring (WHO standards) ---
        // Calculate age in months
        int ageMonths = 0;
        if (selectedBirthday != null) {
            ageMonths = calculateAgeInMonths(selectedBirthday, java.util.Calendar.getInstance());
        }
        double heightMeters = height / 100.0;
        double bmi = 0;
        if (heightMeters > 0) {
            bmi = weight / (heightMeters * heightMeters);
        }
        
        // Validate input ranges (WHO plausible ranges)
        if (weight < 2 || weight > 300 || height < 30 || height > 250) {
            // Out of plausible range, return max risk
            return 100;
        }
        
        // Check for edema first - this overrides everything else
        if (hasSwelling != null && hasSwelling) {
            return 100; // Immediate severe risk - urgent referral alert
        }
        
        // --- Age-based risk assessment (Updated to match verified system) ---
        if (ageMonths >= 6 && ageMonths <= 59) {
            // Children 6-59 months: Use MUAC thresholds
            // Check if MUAC input is available
            String muacText = muacInput.getText().toString().trim();
            if (!muacText.isEmpty()) {
                try {
                    double muac = Double.parseDouble(muacText);
                    if (muac < 11.5) score += 40;      // Severe acute malnutrition (MUAC < 11.5 cm)
                    else if (muac < 12.5) score += 25; // Moderate acute malnutrition (MUAC 11.5-12.5 cm)
                    else score += 0;                    // Normal (MUAC ≥ 12.5 cm)
                } catch (NumberFormatException e) {
                    // If MUAC parsing fails, fallback to weight-for-height approximation
                    double wfh = weight / heightMeters;
                    if (wfh < 0.8) score += 40;      // Severe acute malnutrition
                    else if (wfh < 0.9) score += 25; // Moderate acute malnutrition
                    else score += 0;                  // Normal
                }
            } else {
                // If MUAC not provided, use weight-for-height approximation
                double wfh = weight / heightMeters;
                if (wfh < 0.8) score += 40;      // Severe acute malnutrition
                else if (wfh < 0.9) score += 25; // Moderate acute malnutrition
                else score += 0;                  // Normal
            }
        } else if (ageMonths < 240) {
            // Children/adolescents 5-19 years (BMI-for-age, WHO)
            if (bmi < 15) score += 40;        // Severe thinness
            else if (bmi < 17) score += 30;   // Moderate thinness
            else if (bmi < 18.5) score += 20; // Mild thinness
            else score += 0;                  // Normal
        } else {
            // Adults 20+ (BMI, WHO) - Updated to match verified system
            if (bmi < 16.5) score += 40;      // Severe thinness
            else if (bmi < 18.5) score += 25; // Moderate thinness
            else score += 0;                  // Normal weight
        }
        
        // --- Weight loss scoring (Updated to match verified system) ---
        if (">10%".equals(weightLossStatus)) score += 20;
        else if ("5-10%".equals(weightLossStatus)) score += 10;
        else if ("<5%".equals(weightLossStatus) || "none".equals(weightLossStatus)) score += 0;
        // Handle legacy values
        else if ("yes".equals(weightLossStatus)) score += 20; // Assume >10%
        else if ("not sure".equals(weightLossStatus)) score += 10; // Assume 5-10%
        
        // --- Feeding behavior scoring (Updated to match verified system) ---
        if ("poor appetite".equals(feedingBehavior)) score += 8;      // Was 25, should be 8
        else if ("moderate appetite".equals(feedingBehavior)) score += 8; // Was 10, should be 8
        else score += 0; // Good feeding behavior
        
        // --- Physical signs scoring (Updated to match verified system) ---
        if (isThin != null && isThin) score += 8;      // Was 15, should be 8
        if (isShorter != null && isShorter) score += 8;   // Was 15, should be 8
        if (isWeak != null && isWeak) score += 8;      // Was 15, should be 8
        
        // --- Additional Clinical Risk Factors (New implementation) ---
        if (hasRecentIllness != null && hasRecentIllness) score += 8;           // Recent acute illness (past 2 weeks)
        if (hasEatingDifficulty != null && hasEatingDifficulty) score += 8;        // Difficulty chewing/swallowing
        if (hasFoodInsecurity != null && hasFoodInsecurity) score += 10;         // Food insecurity / skipped meals
        if (hasMicronutrientDeficiency != null && hasMicronutrientDeficiency) score += 6; // Visible signs of micronutrient deficiency
        if (hasFunctionalDecline != null && hasFunctionalDecline) score += 8;       // Functional decline (older adults only)
        
        // --- Dietary diversity scoring (Updated to match verified system) ---
        if (dietaryGroups < 4) score += 10;      // Was 30, should be 10
        else if (dietaryGroups < 6) score += 5;  // Was 15, should be 5
        else score += 0;                         // 6+ food groups
        
        // Note: All clinical risk factors from verified system are now implemented:
        // - Poor appetite (8 points) - Covered by feeding behavior
        // - Recent acute illness (8 points) - ✅ Implemented
        // - Difficulty chewing/swallowing (8 points) - ✅ Implemented
        // - Food insecurity (10 points) - ✅ Implemented
        // - Visible signs of micronutrient deficiency (6 points) - ✅ Implemented
        // - Functional decline for older adults (8 points) - ✅ Implemented
        
        // Cap score at 100
        return Math.min(score, 100);
    }

    private FCMTokenManager fcmTokenManager;
    
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
            
            // FCM token registration will happen after successful API sync
            // This ensures the barangay is saved to the server first
            Log.d("ScreeningFormActivity", "Screening saved locally. FCM token will be registered after API sync.");
        }
        
        // Show success message
        Toast.makeText(this, "Screening results saved successfully!", Toast.LENGTH_SHORT).show();
        
        // Navigation is now handled in showScreeningResult method
        // No need to call finish() here
    }
    
    private void saveScreeningToLocalDatabase(String email, int riskScore) {
        try {
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(this);
            
            // Create screening answers JSON for backward compatibility
            org.json.JSONObject screeningAnswers = new org.json.JSONObject();
            screeningAnswers.put("gender", selectedGender);
            screeningAnswers.put("swelling", hasSwelling != null ? (hasSwelling ? "yes" : "no") : "");
            screeningAnswers.put("weight_loss", weightLossStatus);
            screeningAnswers.put("feeding_behavior", feedingBehavior);
            
            // Combine physical signs
            java.util.List<String> physicalSigns = new java.util.ArrayList<>();
            if (isThin != null && isThin) physicalSigns.add("thin");
            if (isShorter != null && isShorter) physicalSigns.add("shorter");
            if (isWeak != null && isWeak) physicalSigns.add("weak");
            String physicalSignsStr = String.join(",", physicalSigns);
            screeningAnswers.put("physical_signs", physicalSignsStr);
            
            android.util.Log.d("ScreeningFormActivity", "Saving physical signs: " + physicalSignsStr);
            android.util.Log.d("ScreeningFormActivity", "Physical signs state - thin: " + isThin + ", shorter: " + isShorter + ", weak: " + isWeak);
            
            // Get dietary diversity from input
            String dietaryDiversityStr = dietaryDiversityInput.getText().toString();
            int dietaryDiversity = dietaryDiversityStr.isEmpty() ? 0 : Integer.parseInt(dietaryDiversityStr);
            screeningAnswers.put("dietary_diversity", dietaryDiversity);
            
            // Add weight, height, and birthday
            String weightStr = childWeightInput.getText().toString();
            String heightStr = childHeightInput.getText().toString();
            double weight = weightStr.isEmpty() ? 0.0 : Double.parseDouble(weightStr);
            double height = heightStr.isEmpty() ? 0.0 : Double.parseDouble(heightStr);
            
            String birthdayStr = "";
            if (selectedBirthday != null) {
                birthdayStr = String.format("%04d-%02d-%02d", 
                    selectedBirthday.get(java.util.Calendar.YEAR),
                    selectedBirthday.get(java.util.Calendar.MONTH) + 1,
                    selectedBirthday.get(java.util.Calendar.DAY_OF_MONTH));
            }
            
            screeningAnswers.put("weight", weight);
            screeningAnswers.put("height", height);
            screeningAnswers.put("birthday", birthdayStr);
            
            // Calculate BMI
            double bmi = 0.0;
            if (weight > 0 && height > 0) {
                bmi = weight / ((height / 100) * (height / 100));
            }
            screeningAnswers.put("bmi", bmi);
            
            // Add barangay and income (always as string, never null)
            screeningAnswers.put("barangay", selectedBarangay != null ? selectedBarangay : "");
            screeningAnswers.put("income", selectedIncome != null ? selectedIncome : "");
            
            // Add clinical risk factors
            screeningAnswers.put("has_recent_illness", hasRecentIllness);
            screeningAnswers.put("has_eating_difficulty", hasEatingDifficulty);
            screeningAnswers.put("has_food_insecurity", hasFoodInsecurity);
            screeningAnswers.put("has_micronutrient_deficiency", hasMicronutrientDeficiency);
            screeningAnswers.put("has_functional_decline", hasFunctionalDecline);
            
            // Save to database - maintain backward compatibility with JSON field
            android.database.sqlite.SQLiteDatabase db = dbHelper.getWritableDatabase();
            android.content.ContentValues values = new android.content.ContentValues();
            values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
            values.put(UserPreferencesDbHelper.COL_RISK_SCORE, riskScore);
            values.put(UserPreferencesDbHelper.COL_SCREENING_ANSWERS, screeningAnswers.toString());
            values.put(UserPreferencesDbHelper.COL_GENDER, selectedGender);
            values.put(UserPreferencesDbHelper.COL_SWELLING, hasSwelling != null ? (hasSwelling ? "yes" : "no") : "");
            values.put(UserPreferencesDbHelper.COL_WEIGHT_LOSS, weightLossStatus);
            values.put(UserPreferencesDbHelper.COL_FEEDING_BEHAVIOR, feedingBehavior);
            values.put(UserPreferencesDbHelper.COL_FEEDING, feedingBehavior);
            values.put("dietary_diversity", dietaryDiversity);
            values.put("barangay", selectedBarangay != null ? selectedBarangay : "");
            values.put("income", selectedIncome != null ? selectedIncome : "");
            
            // Check if user already exists
            android.database.Cursor cursor = db.query(UserPreferencesDbHelper.TABLE_NAME, 
                null, UserPreferencesDbHelper.COL_USER_EMAIL + "=?", 
                new String[]{email}, null, null, null);
            
            if (cursor.moveToFirst()) {
                // Update existing record
                db.update(UserPreferencesDbHelper.TABLE_NAME, values, 
                    UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
                android.util.Log.d("ScreeningFormActivity", "Updated screening data in local database");
            } else {
                // Insert new record
                db.insert(UserPreferencesDbHelper.TABLE_NAME, null, values);
                android.util.Log.d("ScreeningFormActivity", "Inserted screening data in local database");
            }
            cursor.close();
            dbHelper.close();
            
            runOnUiThread(() -> Toast.makeText(this, "Screening saved locally", Toast.LENGTH_SHORT).show());
            
        } catch (Exception e) {
            android.util.Log.e("ScreeningFormActivity", "Error saving to local database: " + e.getMessage());
            runOnUiThread(() -> Toast.makeText(this, "Error saving locally: " + e.getMessage(), Toast.LENGTH_SHORT).show());
        }
    }
    
    private void syncScreeningToApi(String email, int riskScore) {
        // Create comprehensive screening data
        try {
            org.json.JSONObject screeningAnswers = new org.json.JSONObject();
            
            // Essential user data (CRITICAL - was missing!)
            screeningAnswers.put("gender", selectedGender);
            screeningAnswers.put("birthday", selectedBirthday != null ? 
                new java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.getDefault()).format(selectedBirthday.getTime()) : "");
            screeningAnswers.put("barangay", selectedBarangay);
            screeningAnswers.put("income", selectedIncome);
            screeningAnswers.put("name", email); // Use email as name if no name field
            
            // Basic measurements
                String weightStr = childWeightInput.getText().toString();
                String heightStr = childHeightInput.getText().toString();
                double weight = weightStr.isEmpty() ? 0.0 : Double.parseDouble(weightStr);
                double height = heightStr.isEmpty() ? 0.0 : Double.parseDouble(heightStr);
                String dietaryDiversityStr = dietaryDiversityInput.getText().toString();
            int dietaryGroups = dietaryDiversityStr.isEmpty() ? 0 : Integer.parseInt(dietaryDiversityStr);
                
            // Anthropometric data
                screeningAnswers.put("weight", weight);
                screeningAnswers.put("height", height);
            screeningAnswers.put("dietary_diversity", dietaryGroups); // Fixed field name to match API
            screeningAnswers.put("muac", muacInput.getText().toString());
            
            // Physical signs (fixed field names to match API expectations)
                    java.util.List<String> physicalSigns = new java.util.ArrayList<>();
                    if (isThin != null && isThin) physicalSigns.add("thin");
                    if (isShorter != null && isShorter) physicalSigns.add("shorter");
                    if (isWeak != null && isWeak) physicalSigns.add("weak");
            if (isNone != null && isNone) physicalSigns.add("none");
            screeningAnswers.put("physical_signs", new org.json.JSONArray(physicalSigns));
            
            // Clinical risk factors (fixed field names to match API expectations)
            screeningAnswers.put("swelling", hasSwelling != null && hasSwelling ? "yes" : "no");
            screeningAnswers.put("weight_loss", weightLossStatus);
            screeningAnswers.put("feeding_behavior", feedingBehavior);
            screeningAnswers.put("has_recent_illness", hasRecentIllness);
            screeningAnswers.put("has_eating_difficulty", hasEatingDifficulty);
            screeningAnswers.put("has_food_insecurity", hasFoodInsecurity);
            screeningAnswers.put("has_micronutrient_deficiency", hasMicronutrientDeficiency);
            screeningAnswers.put("has_functional_decline", hasFunctionalDecline);
            
            // Risk assessment
            screeningAnswers.put("risk_score", riskScore);
            screeningAnswers.put("assessment_date", new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault()).format(new java.util.Date()));
            
            // Call unified API with comprehensive data
            syncScreeningToUnifiedApi(email, riskScore, screeningAnswers);
            
        } catch (Exception e) {
            android.util.Log.e("ScreeningFormActivity", "Error creating screening data: " + e.getMessage());
        }
    }

    // Removed WebViewAPIClient - using direct HTTP requests
    
    private void syncScreeningToUnifiedApi(String email, int riskScore, org.json.JSONObject screeningAnswers) {
        // Use direct HTTP request to sync screening data
        new Thread(() -> {
            try {
                android.util.Log.d("ScreeningFormActivity", "Starting API sync with direct HTTP");
                
                // Create the JSON payload
            org.json.JSONObject requestData = new org.json.JSONObject();
            requestData.put("action", "save_screening");
            requestData.put("email", email);
            requestData.put("username", email);
            requestData.put("risk_score", riskScore);
            
            // Individual fields matching database columns
            requestData.put("birthday", screeningAnswers.optString("birthday", ""));
            requestData.put("age", screeningAnswers.optInt("age", 0));
            requestData.put("gender", screeningAnswers.optString("gender", ""));
            requestData.put("weight", screeningAnswers.optDouble("weight", 0.0));
            requestData.put("height", screeningAnswers.optDouble("height", 0.0));
            requestData.put("bmi", screeningAnswers.optDouble("bmi", 0.0));
            requestData.put("muac", screeningAnswers.optString("muac", ""));
            requestData.put("swelling", screeningAnswers.optString("swelling", "no"));
            requestData.put("weight_loss", screeningAnswers.optString("weight_loss", ""));
            requestData.put("dietary_diversity", screeningAnswers.optInt("dietary_diversity", 0));
            requestData.put("feeding_behavior", screeningAnswers.optString("feeding_behavior", ""));
            
            // Physical signs as individual boolean fields
            requestData.put("physical_thin", screeningAnswers.optString("physical_signs", "").contains("thin"));
            requestData.put("physical_shorter", screeningAnswers.optString("physical_signs", "").contains("shorter"));
            requestData.put("physical_weak", screeningAnswers.optString("physical_signs", "").contains("weak"));
            requestData.put("physical_none", screeningAnswers.optString("physical_signs", "").contains("none"));
            
            // Clinical risk factors as individual boolean fields
            requestData.put("has_recent_illness", screeningAnswers.optBoolean("has_recent_illness", false));
            requestData.put("has_eating_difficulty", screeningAnswers.optBoolean("has_eating_difficulty", false));
            requestData.put("has_food_insecurity", screeningAnswers.optBoolean("has_food_insecurity", false));
            requestData.put("has_micronutrient_deficiency", screeningAnswers.optBoolean("has_micronutrient_deficiency", false));
            requestData.put("has_functional_decline", screeningAnswers.optBoolean("has_functional_decline", false));
            
            // Location and socioeconomic data
            requestData.put("barangay", screeningAnswers.optString("barangay", ""));
            requestData.put("income", screeningAnswers.optString("income", ""));
            
                android.util.Log.d("ScreeningFormActivity", "Sending to unified API via HTTP: " + requestData.toString());
                
                // Make direct HTTP request with API key and enhanced headers
                java.net.URL url = new java.net.URL(Constants.UNIFIED_API_URL);
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json; charset=utf-8");
                conn.setRequestProperty("User-Agent", Constants.USER_AGENT);
                conn.setRequestProperty("Accept", "application/json, text/plain, */*");
                conn.setRequestProperty("X-API-Key", Constants.API_KEY);
                conn.setRequestProperty("Origin", "https://nutrisaur-production.up.railway.app");
                conn.setRequestProperty("Cache-Control", "no-cache");
                conn.setDoOutput(true);
                conn.setConnectTimeout(15000);
                conn.setReadTimeout(15000);
                
                java.io.OutputStream os = conn.getOutputStream();
                os.write(requestData.toString().getBytes("UTF-8"));
                os.close();
                
                int responseCode = conn.getResponseCode();
                android.util.Log.d("ScreeningFormActivity", "HTTP response code: " + responseCode);
                
                if (responseCode == 200) {
                    java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    android.util.Log.d("ScreeningFormActivity", "HTTP API response: " + response.toString());
                    
                    org.json.JSONObject jsonResponse = new org.json.JSONObject(response.toString());
                    if (jsonResponse.optBoolean("success", false)) {
                        android.util.Log.d("ScreeningFormActivity", "Screening synced successfully via HTTP");
                        runOnUiThread(() -> {
                            Toast.makeText(ScreeningFormActivity.this, "Screening synced successfully", Toast.LENGTH_SHORT).show();
                            registerFCMTokenAfterScreening();
                        });
                    } else {
                        String errorMsg = jsonResponse.optString("message", "Unknown error");
                        android.util.Log.e("ScreeningFormActivity", "HTTP API returned success=false: " + errorMsg);
                        runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, "API Error: " + errorMsg, Toast.LENGTH_SHORT).show());
                    }
                } else {
                    android.util.Log.e("ScreeningFormActivity", "HTTP request failed with code: " + responseCode);
                    runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, "Failed to sync screening data", Toast.LENGTH_SHORT).show());
                }
            } catch (Exception e) {
                android.util.Log.e("ScreeningFormActivity", "Error syncing screening data: " + e.getMessage(), e);
                runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, "Error syncing screening data: " + e.getMessage(), Toast.LENGTH_SHORT).show());
            }
        }).start();
    }
    
    private void testNetworkConnectivity() {
        new Thread(() -> {
            try {
                android.util.Log.d("ScreeningFormActivity", "Testing network connectivity...");
                
                // Test basic internet connectivity
                java.net.URL testUrl = new java.net.URL("https://www.google.com");
                java.net.HttpURLConnection testConn = (java.net.HttpURLConnection) testUrl.openConnection();
                testConn.setConnectTimeout(5000);
                testConn.setReadTimeout(5000);
                testConn.setRequestMethod("HEAD");
                
                int testResponseCode = testConn.getResponseCode();
                android.util.Log.d("ScreeningFormActivity", "Internet connectivity test: " + testResponseCode);
                testConn.disconnect();
                
                // Test local network connectivity using Constants
                try {
                    java.net.URL localTestUrl = new java.net.URL(Constants.API_BASE_URL);
                    java.net.HttpURLConnection localTestConn = (java.net.HttpURLConnection) localTestUrl.openConnection();
                    localTestConn.setConnectTimeout(5000);
                    localTestConn.setReadTimeout(5000);
                    localTestConn.setRequestMethod("HEAD");
                    
                    int localTestResponseCode = localTestConn.getResponseCode();
                    android.util.Log.d("ScreeningFormActivity", "Local network test (" + Constants.API_BASE_URL + "): " + localTestResponseCode);
                    localTestConn.disconnect();
                } catch (Exception e) {
                    android.util.Log.e("ScreeningFormActivity", "Local network test failed: " + e.getMessage());
                }
                
            } catch (Exception e) {
                android.util.Log.e("ScreeningFormActivity", "Network connectivity test failed: " + e.getMessage());
            }
        }).start();
    }

    private String getCurrentUserEmail() {
        return getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).getString("current_user_email", null);
    }
    
    /**
     * Register FCM token after successful screening API sync
     * This ensures the barangay is saved to the server before registering FCM token
     */
    private void registerFCMTokenAfterScreening() {
        try {
            // Get current user email
            String email = getCurrentUserEmail();
            if (email == null || email.isEmpty()) {
                Log.e("ScreeningFormActivity", "Cannot register FCM token: no user email");
                return;
            }
            
            // Get selected barangay
            String selectedBarangay = this.selectedBarangay != null ? this.selectedBarangay : "";
            if (selectedBarangay.isEmpty()) {
                Log.e("ScreeningFormActivity", "Cannot register FCM token: no barangay selected");
                return;
            }
            
            // Initialize FCM token manager if needed
            if (fcmTokenManager == null) {
                fcmTokenManager = new FCMTokenManager(this);
            }
            
            // Register FCM token with barangay
            fcmTokenManager.registerTokenAfterScreening(email, selectedBarangay);
            
            Log.d("ScreeningFormActivity", "FCM token registration initiated for user: " + email + " in " + selectedBarangay);
            
            // Show success message
            Toast.makeText(this, "FCM token registered with location: " + selectedBarangay, Toast.LENGTH_SHORT).show();
            
        } catch (Exception e) {
            Log.e("ScreeningFormActivity", "Error registering FCM token: " + e.getMessage());
        }
    }
    
    // Add method to sync food preferences with updated risk score
    private void syncFoodPreferencesWithRiskScore(String email, int riskScore) {
        new Thread(() -> {
            try {
                // Create food preferences JSON with updated risk score
                org.json.JSONObject requestData = new org.json.JSONObject();
                requestData.put("action", "save_preferences");
                requestData.put("email", email);
                requestData.put("username", email);
                requestData.put("allergies", new org.json.JSONArray());
                requestData.put("diet_prefs", new org.json.JSONArray());
                requestData.put("avoid_foods", "");
                requestData.put("risk_score", riskScore);
                
                String json = requestData.toString();
                android.util.Log.d("ScreeningFormActivity", "Sending food preferences to unified API: " + json);
                
                // Send to unified API
                java.net.URL url = new java.net.URL(Constants.UNIFIED_API_URL);
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setRequestProperty("User-Agent", "Mozilla/5.0 (Android) Nutrisaur-App/1.0");
                conn.setRequestProperty("Accept", "application/json, text/plain, */*");
                conn.setRequestProperty("Accept-Language", "en-US,en;q=0.9");
                conn.setRequestProperty("Accept-Encoding", "gzip, deflate, br");
                conn.setRequestProperty("Connection", "keep-alive");
                conn.setRequestProperty("Cache-Control", "no-cache");
                conn.setDoOutput(true);
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(10000);
                
                try (java.io.OutputStream os = conn.getOutputStream()) {
                    os.write(json.getBytes("UTF-8"));
                }
                
                int responseCode = conn.getResponseCode();
                android.util.Log.d("ScreeningFormActivity", "Food preferences API response code: " + responseCode);
                
                if (responseCode >= 200 && responseCode < 300) {
                    // Read response
                    java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    android.util.Log.d("ScreeningFormActivity", "Food preferences API response: " + response.toString());
                    android.util.Log.d("ScreeningFormActivity", "Food preferences synced with risk score successfully");
                    runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, "Food preferences updated", Toast.LENGTH_SHORT).show());
                } else {
                    android.util.Log.e("ScreeningFormActivity", "Failed to sync food preferences: " + responseCode);
                    runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, "Failed to update preferences: " + responseCode, Toast.LENGTH_SHORT).show());
                }
                
            } catch (Exception e) {
                android.util.Log.e("ScreeningFormActivity", "Error syncing food preferences: " + e.getMessage());
                runOnUiThread(() -> Toast.makeText(ScreeningFormActivity.this, "Error updating preferences: " + e.getMessage(), Toast.LENGTH_SHORT).show());
            }
        }).start();
    }

    private void showScreeningResult(int riskScore) {
        String riskCategory;
        String recommendation;
        String alertMessage = "";
        
        // Updated risk categories according to verified malnutrition screening system
        if (riskScore >= 80 || hasSwelling) {
            riskCategory = "Severe Risk";
            recommendation = "Urgent referral required";
            alertMessage = "⚠️ URGENT: This person requires immediate medical attention and referral to a healthcare provider.";
        } else if (riskScore >= 50) {
            riskCategory = "High Risk";
            recommendation = "Prompt clinical referral recommended";
            alertMessage = "🔴 HIGH RISK: This person should be referred to a healthcare provider for further assessment.";
        } else if (riskScore >= 20) {
            riskCategory = "Moderate Risk";
            recommendation = "Further assessment recommended";
            alertMessage = "🟡 MODERATE RISK: This person should receive further assessment and monitoring.";
        } else {
            riskCategory = "Low Risk";
            recommendation = "Routine counseling";
            alertMessage = "🟢 LOW RISK: This person can receive routine nutrition counseling.";
        }
        
        // Create enhanced result message
        String message = riskCategory + ": " + riskScore + "% - " + recommendation;
        
        // Show result as toast message to avoid window leak
        Toast.makeText(this, message, Toast.LENGTH_LONG).show();
        
        // Show alert message as separate toast - make alertMessage final for lambda
        final String finalAlertMessage = alertMessage;
        if (!finalAlertMessage.isEmpty()) {
            new android.os.Handler().postDelayed(() -> {
                Toast.makeText(this, finalAlertMessage, Toast.LENGTH_LONG).show();
            }, 1000);
        }
        
        // Navigate to main activity after a short delay
        new android.os.Handler().postDelayed(() -> {
        Intent intent = new Intent(ScreeningFormActivity.this, MainActivity.class);
        startActivity(intent);
        finish();
        }, 2000);
    }

    private void updateButtonStatesForAction(String action, View selectedButton) {
        switch (action) {
            case "gender":
                if (selectedButton.getId() == R.id.gender_boy) {
                    updateButtonStates((Button) selectedButton, genderGirl);
                } else {
                    updateButtonStates((Button) selectedButton, genderBoy);
                }
                break;
            case "swelling":
                if (selectedButton.getId() == R.id.swelling_yes) {
                    updateButtonStates((Button) selectedButton, swellingNo);
                } else {
                    updateButtonStates((Button) selectedButton, swellingYes);
                }
                break;
            case "weightLoss":
                if (selectedButton.getId() == R.id.weightloss_10plus) {
                    updateButtonStates((Button) selectedButton, weightLoss5To10, weightLossLess5);
                } else if (selectedButton.getId() == R.id.weightloss_5to10) {
                    updateButtonStates((Button) selectedButton, weightLoss10Plus, weightLossLess5);
                } else {
                    updateButtonStates((Button) selectedButton, weightLoss10Plus, weightLoss5To10);
                }
                break;
            case "feeding":
                if (selectedButton.getId() == R.id.feeding_good) {
                    updateButtonStates((Button) selectedButton, feedingModerate, feedingPoor);
                } else if (selectedButton.getId() == R.id.feeding_moderate) {
                    updateButtonStates((Button) selectedButton, feedingGood, feedingPoor);
                } else {
                    updateButtonStates((Button) selectedButton, feedingGood, feedingModerate);
                }
                break;
            case "physical":
                // For physical signs, update all button states based on current values
                updatePhysicalSignsButtonStates();
                break;
            case "illness":
                if (selectedButton.getId() == R.id.illness_yes) {
                    updateButtonStates((Button) selectedButton, illnessNo);
                } else {
                    updateButtonStates((Button) selectedButton, illnessYes);
                }
                break;
            case "eatingDifficulty":
                if (selectedButton.getId() == R.id.eating_difficulty_yes) {
                    updateButtonStates((Button) selectedButton, eatingDifficultyNo);
                } else {
                    updateButtonStates((Button) selectedButton, eatingDifficultyYes);
                }
                break;
            case "foodInsecurity":
                if (selectedButton.getId() == R.id.food_insecurity_yes) {
                    updateButtonStates((Button) selectedButton, foodInsecurityNo);
                } else {
                    updateButtonStates((Button) selectedButton, foodInsecurityYes);
                }
                break;
            case "micronutrient":
                if (selectedButton.getId() == R.id.micronutrient_yes) {
                    updateButtonStates((Button) selectedButton, micronutrientNo);
                } else {
                    updateButtonStates((Button) selectedButton, micronutrientYes);
                }
                break;
            case "functionalDecline":
                if (selectedButton.getId() == R.id.functional_decline_yes) {
                    updateButtonStates((Button) selectedButton, functionalDeclineNo);
                } else {
                    updateButtonStates((Button) selectedButton, functionalDeclineYes);
                }
                break;
        }
    }
    
    private void updatePhysicalSignsButtonStates() {
        // Update physical signs buttons based on current state
        if (isThin != null && isThin) {
            physicalThin.setSelected(true);
            physicalThin.setElevation(8f);
            physicalThin.setBackgroundColor(0xFF424242); // Dark grey for selected
        } else {
            physicalThin.setSelected(false);
            physicalThin.setElevation(6f);
            physicalThin.setBackgroundColor(0xFFCCCCCC); // Lighter grey for unselected - more distinguishable
        }
        
        if (isShorter != null && isShorter) {
            physicalShorter.setSelected(true);
            physicalShorter.setElevation(8f);
            physicalShorter.setBackgroundColor(0xFF424242); // Dark grey for selected
        } else {
            physicalShorter.setSelected(false);
            physicalShorter.setElevation(6f);
            physicalShorter.setBackgroundColor(0xFFCCCCCC); // Lighter grey for unselected - more distinguishable
        }
        
        if (isWeak != null && isWeak) {
            physicalWeak.setSelected(true);
            physicalWeak.setElevation(8f);
            physicalWeak.setBackgroundColor(0xFF424242); // Dark grey for selected
        } else {
            physicalWeak.setSelected(false);
            physicalWeak.setElevation(6f);
            physicalWeak.setBackgroundColor(0xFFCCCCCC); // Lighter grey for unselected - more distinguishable
        }
        
        if (isNone != null && isNone) {
            physicalNone.setSelected(true);
            physicalNone.setElevation(8f);
            physicalNone.setBackgroundColor(0xFF424242); // Dark grey for selected
        } else {
            physicalNone.setSelected(false);
            physicalNone.setElevation(6f);
            physicalNone.setBackgroundColor(0xFFCCCCCC); // Lighter grey for unselected - more distinguishable
        }
        
        // Force redraw
        physicalThin.invalidate();
        physicalShorter.invalidate();
        physicalWeak.invalidate();
        physicalNone.invalidate();
    }
    
    private void initializeButtonStates() {
        // Initialize all buttons to unselected state with lighter grey background
        genderBoy.setSelected(false);
        genderGirl.setSelected(false);
        genderBoy.setElevation(6f);
        genderGirl.setElevation(6f);
        genderBoy.setBackgroundColor(0xFFCCCCCC);
        genderGirl.setBackgroundColor(0xFFCCCCCC);
        
        swellingYes.setSelected(false);
        swellingNo.setSelected(false);
        swellingYes.setElevation(6f);
        swellingNo.setElevation(6f);
        swellingYes.setBackgroundColor(0xFFCCCCCC);
        swellingNo.setBackgroundColor(0xFFCCCCCC);
        
        weightLoss10Plus.setSelected(false);
        weightLoss5To10.setSelected(false);
        weightLossLess5.setSelected(false);
        weightLoss10Plus.setElevation(6f);
        weightLoss5To10.setElevation(6f);
        weightLossLess5.setElevation(6f);
        weightLoss10Plus.setBackgroundColor(0xFFCCCCCC);
        weightLoss5To10.setBackgroundColor(0xFFCCCCCC);
        weightLossLess5.setBackgroundColor(0xFFCCCCCC);
        
        feedingGood.setSelected(false);
        feedingModerate.setSelected(false);
        feedingPoor.setSelected(false);
        feedingGood.setElevation(6f);
        feedingModerate.setElevation(6f);
        feedingPoor.setElevation(6f);
        feedingGood.setBackgroundColor(0xFFCCCCCC);
        feedingModerate.setBackgroundColor(0xFFCCCCCC);
        feedingPoor.setBackgroundColor(0xFFCCCCCC);
        
        // Physical signs
        physicalThin.setSelected(false);
        physicalShorter.setSelected(false);
        physicalWeak.setSelected(false);
        physicalNone.setSelected(false);
        physicalThin.setElevation(6f);
        physicalShorter.setElevation(6f);
        physicalWeak.setElevation(6f);
        physicalNone.setElevation(6f);
        physicalThin.setBackgroundColor(0xFFCCCCCC);
        physicalShorter.setBackgroundColor(0xFFCCCCCC);
        physicalWeak.setBackgroundColor(0xFFCCCCCC);
        physicalNone.setBackgroundColor(0xFFCCCCCC);
        
        // Clinical risk factors
        illnessYes.setSelected(false);
        illnessNo.setSelected(false);
        illnessYes.setElevation(6f);
        illnessNo.setElevation(6f);
        illnessYes.setBackgroundColor(0xFFCCCCCC);
        illnessNo.setBackgroundColor(0xFFCCCCCC);
        
        eatingDifficultyYes.setSelected(false);
        eatingDifficultyNo.setSelected(false);
        eatingDifficultyYes.setElevation(6f);
        eatingDifficultyNo.setElevation(6f);
        eatingDifficultyYes.setBackgroundColor(0xFFCCCCCC);
        eatingDifficultyNo.setBackgroundColor(0xFFCCCCCC);
        
        foodInsecurityYes.setSelected(false);
        foodInsecurityNo.setSelected(false);
        foodInsecurityYes.setElevation(6f);
        foodInsecurityNo.setElevation(6f);
        foodInsecurityYes.setBackgroundColor(0xFFCCCCCC);
        foodInsecurityNo.setBackgroundColor(0xFFCCCCCC);
        
        micronutrientYes.setSelected(false);
        micronutrientNo.setSelected(false);
        micronutrientYes.setElevation(6f);
        micronutrientNo.setElevation(6f);
        micronutrientYes.setBackgroundColor(0xFFCCCCCC);
        micronutrientNo.setBackgroundColor(0xFFCCCCCC);
        
        functionalDeclineYes.setSelected(false);
        functionalDeclineNo.setSelected(false);
        functionalDeclineYes.setElevation(6f);
        functionalDeclineNo.setElevation(6f);
        functionalDeclineYes.setBackgroundColor(0xFFCCCCCC);
        functionalDeclineNo.setBackgroundColor(0xFFCCCCCC);
        
        // Set white text color for all buttons
        setButtonTextColor(genderBoy, 0xFFFFFFFF);
        setButtonTextColor(genderGirl, 0xFFFFFFFF);
        setButtonTextColor(swellingYes, 0xFFFFFFFF);
        setButtonTextColor(swellingNo, 0xFFFFFFFF);
        setButtonTextColor(weightLoss10Plus, 0xFFFFFFFF);
        setButtonTextColor(weightLoss5To10, 0xFFFFFFFF);
        setButtonTextColor(weightLossLess5, 0xFFFFFFFF);
        setButtonTextColor(feedingGood, 0xFFFFFFFF);
        setButtonTextColor(feedingModerate, 0xFFFFFFFF);
        setButtonTextColor(feedingPoor, 0xFFFFFFFF);
        setButtonTextColor(physicalThin, 0xFFFFFFFF);
        setButtonTextColor(physicalShorter, 0xFFFFFFFF);
        setButtonTextColor(physicalWeak, 0xFFFFFFFF);
        setButtonTextColor(physicalNone, 0xFFFFFFFF);
        setButtonTextColor(illnessYes, 0xFFFFFFFF);
        setButtonTextColor(illnessNo, 0xFFFFFFFF);
        setButtonTextColor(eatingDifficultyYes, 0xFFFFFFFF);
        setButtonTextColor(eatingDifficultyNo, 0xFFFFFFFF);
        setButtonTextColor(foodInsecurityYes, 0xFFFFFFFF);
        setButtonTextColor(foodInsecurityNo, 0xFFFFFFFF);
        setButtonTextColor(micronutrientYes, 0xFFFFFFFF);
        setButtonTextColor(micronutrientNo, 0xFFFFFFFF);
        setButtonTextColor(functionalDeclineYes, 0xFFFFFFFF);
        setButtonTextColor(functionalDeclineNo, 0xFFFFFFFF);
        
        // Force redraw of all buttons
        genderBoy.invalidate();
        genderGirl.invalidate();
        swellingYes.invalidate();
        swellingNo.invalidate();
        weightLoss10Plus.invalidate();
        weightLoss5To10.invalidate();
        weightLossLess5.invalidate();
        feedingGood.invalidate();
        feedingModerate.invalidate();
        feedingPoor.invalidate();
        physicalThin.invalidate();
        physicalShorter.invalidate();
        physicalWeak.invalidate();
        physicalNone.invalidate();
        illnessYes.invalidate();
        illnessNo.invalidate();
        eatingDifficultyYes.invalidate();
        eatingDifficultyNo.invalidate();
        foodInsecurityYes.invalidate();
        foodInsecurityNo.invalidate();
        micronutrientYes.invalidate();
        micronutrientNo.invalidate();
        functionalDeclineYes.invalidate();
        functionalDeclineNo.invalidate();
    }
    
    // Helper method to set button text color
    private void setButtonTextColor(Button button, int color) {
        button.setTextColor(color);
    }

    private void updateBooleanValue(String action, boolean value) {
        switch (action) {
            case "gender":
                if (value) {
                    selectedGender = "boy";
                } else {
                    selectedGender = "girl";
                }
                break;
            case "illness":
                hasRecentIllness = value;
                break;
            case "eatingDifficulty":
                hasEatingDifficulty = value;
                break;
            case "foodInsecurity":
                hasFoodInsecurity = value;
                break;
            case "micronutrient":
                hasMicronutrientDeficiency = value;
                break;
            case "functionalDecline":
                hasFunctionalDecline = value;
                break;
            case "weightLoss":
                // Handle weight loss status based on button ID
                // This will be handled in the specific click listeners
                break;
            case "feeding":
                // Handle feeding behavior based on button ID
                // This will be handled in the specific click listeners
                break;
            case "physical":
                // Handle physical signs - allow multiple selection
                // This will be handled in the specific click listeners
                break;
        }
    }

    private void handleTestMode() {
        // Check if we're in test mode
        boolean isTestMode = getIntent().getBooleanExtra("test_mode", false);
        if (!isTestMode) return;
        
        // Show test mode notification
        Toast.makeText(this, "🧪 TEST MODE: Demo data pre-filled!", Toast.LENGTH_LONG).show();
        
        // Pre-fill form with realistic test data - simplified version
        new Thread(() -> {
            // Wait a bit for views to be initialized
            try {
                Thread.sleep(500);
            } catch (InterruptedException e) {
                return;
            }
            
            runOnUiThread(() -> {
                try {
                    // Basic info - simulating a 3-year-old child
                    if (childWeightInput != null) childWeightInput.setText("12.5"); // kg
                    if (childHeightInput != null) childHeightInput.setText("90"); // cm
                    if (dietaryDiversityInput != null) dietaryDiversityInput.setText("4"); // food groups
                    if (muacInput != null) muacInput.setText("14.2"); // cm
                    
                    // Set gender selection manually
                    selectedGender = "girl";
                    if (genderGirl != null) {
                        genderGirl.setSelected(true);
                        genderGirl.setBackgroundResource(R.drawable.button_selected);
                    }
                    if (genderBoy != null) {
                        genderBoy.setSelected(false);
                        genderBoy.setBackgroundResource(R.drawable.button_unselected);
                    }
                    
                    // Set birthday to 3 years ago
                    selectedBirthday = Calendar.getInstance();
                    selectedBirthday.add(Calendar.YEAR, -3);
                    if (birthdayPickerBtn != null) {
                        java.text.SimpleDateFormat dateFormat = new java.text.SimpleDateFormat("MMM dd, yyyy", Locale.getDefault());
                        birthdayPickerBtn.setText(dateFormat.format(selectedBirthday.getTime()));
                    }
                    
                    // Set clinical assessments manually
                    hasSwelling = false;
                    if (swellingNo != null) {
                        swellingNo.setSelected(true);
                        swellingNo.setBackgroundResource(R.drawable.button_selected);
                    }
                    if (swellingYes != null) {
                        swellingYes.setSelected(false);
                        swellingYes.setBackgroundResource(R.drawable.button_unselected);
                    }
                    
                    weightLossStatus = "5-10%";
                    if (weightLoss5To10 != null) {
                        weightLoss5To10.setSelected(true);
                        weightLoss5To10.setBackgroundResource(R.drawable.button_selected);
                    }
                    
                    feedingBehavior = "moderate";
                    if (feedingModerate != null) {
                        feedingModerate.setSelected(true);
                        feedingModerate.setBackgroundResource(R.drawable.button_selected);
                    }
                    
                    // Physical signs
                    isThin = true;
                    isWeak = true;
                    isShorter = false;
                    isNone = false;
                    if (physicalThin != null) {
                        physicalThin.setSelected(true);
                        physicalThin.setBackgroundResource(R.drawable.button_selected);
                    }
                    if (physicalWeak != null) {
                        physicalWeak.setSelected(true);
                        physicalWeak.setBackgroundResource(R.drawable.button_selected);
                    }
                    
                    // Risk factors
                    hasRecentIllness = true;
                    if (illnessYes != null) {
                        illnessYes.setSelected(true);
                        illnessYes.setBackgroundResource(R.drawable.button_selected);
                    }
                    
                    hasFoodInsecurity = true;
                    if (foodInsecurityYes != null) {
                        foodInsecurityYes.setSelected(true);
                        foodInsecurityYes.setBackgroundResource(R.drawable.button_selected);
                    }
                    
                    // Location data
                    selectedBarangay = "A. Rivera (Pob.)";
                    selectedIncome = "PHP 20,001–40,000/month (Middle)";
                    
                    // Set spinners if they exist
                    setSpinnerSelection(barangaySpinner, selectedBarangay);
                    setSpinnerSelection(incomeSpinner, selectedIncome);
                    
                } catch (Exception e) {
                    Log.e("ScreeningFormActivity", "Error pre-filling test data: " + e.getMessage());
                }
            });
        }).start();
    }
    
    private void setSpinnerSelection(Spinner spinner, String value) {
        if (spinner == null || value == null) return;
        
        try {
            android.widget.SpinnerAdapter adapter = spinner.getAdapter();
            for (int i = 0; i < adapter.getCount(); i++) {
                if (adapter.getItem(i).toString().equals(value)) {
                    spinner.setSelection(i);
                    break;
                }
            }
        } catch (Exception e) {
            Log.e("ScreeningFormActivity", "Error setting spinner selection: " + e.getMessage());
        }
    }
} 