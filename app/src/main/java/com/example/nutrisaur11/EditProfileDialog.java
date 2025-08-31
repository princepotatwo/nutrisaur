package com.example.nutrisaur11;

import android.app.Dialog;
import android.content.Context;
import android.content.SharedPreferences;
import android.content.Intent;
import android.app.Activity;
import android.database.Cursor;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;
import android.widget.Spinner;
import android.widget.ArrayAdapter;
import android.widget.AdapterView;
import androidx.annotation.NonNull;
import com.example.nutrisaur11.Constants;
import org.json.JSONArray;
import org.json.JSONObject;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;
import okhttp3.MediaType;
import android.database.sqlite.SQLiteDatabase;
import android.content.ContentValues;
import java.util.Iterator;

public class EditProfileDialog extends Dialog {
    private final Context context;
    private final String userEmail;
    private UserPreferencesDbHelper dbHelper;

    // UI elements
    private Button birthdayPickerBtn, genderBoyBtn, genderGirlBtn;
    private EditText weightInput, heightInput, dietaryDiversityInput, muacInput;
    private EditText nameEditText, weightEditText, heightEditText, allergiesEditText, dietPrefsEditText, avoidFoodsEditText;
    private TextView ageDisplay, muacLabel;
    private Button swellingYesBtn, swellingNoBtn;
    private Button weightLoss10PlusBtn, weightLoss5To10Btn, weightLossLess5Btn;
    private Button feedingGoodBtn, feedingModerateBtn, feedingPoorBtn;
    private Button saveProfileBtn;
    private Button barangaySpinner, incomeSpinner;

    // Physical Signs Assessment
    private Button physicalThinBtn, physicalShorterBtn, physicalWeakBtn, physicalNoneBtn;
    
    // Additional Clinical Risk Factors
    private Button illnessYesBtn, illnessNoBtn;
    private Button eatingDifficultyYesBtn, eatingDifficultyNoBtn;
    private Button foodInsecurityYesBtn, foodInsecurityNoBtn;
    private Button micronutrientYesBtn, micronutrientNoBtn;
    private Button functionalDeclineYesBtn, functionalDeclineNoBtn;
    
    // Physical Signs state
    private Boolean isThin = null, isShorter = null, isWeak = null, isNone = null;
    
    private Boolean hasRecentIllness = null;
    private Boolean hasEatingDifficulty = null;
    private Boolean hasFoodInsecurity = null;
    private Boolean hasMicronutrientDeficiency = null;
    private Boolean hasFunctionalDecline = null;

    // State
    private java.util.Calendar selectedBirthday;
    private int ageInMonths = 0;
    // Add missing fields for barangay and income
    private String selectedBarangay = "";
    private String selectedIncome = "";
    private String selectedGender = "";
    private String existingScreeningData = null;

    // Button selection state tracking
    private boolean genderBoySelected = false;
    private boolean genderGirlSelected = false;
    private boolean swellingYesSelected = false;
    private boolean swellingNoSelected = false;
    private boolean weightLoss10PlusSelected = false;
    private boolean weightLoss5To10Selected = false;
    private boolean weightLossLess5Selected = false;
    private boolean feedingGoodSelected = false;
    private boolean feedingModerateSelected = false;
    private boolean feedingPoorSelected = false;

    private static final String API_BASE_URL = Constants.UNIFIED_API_URL;
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

    public EditProfileDialog(@NonNull Context context, String userEmail) {
        super(context);
        this.context = context;
        this.userEmail = userEmail;
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.dialog_edit_profile);
        getWindow().setBackgroundDrawableResource(android.R.color.transparent);
        dbHelper = new UserPreferencesDbHelper(context);
        
        // Force database upgrade to ensure all columns exist
        try {
            dbHelper.getWritableDatabase();
        } catch (Exception e) {
            Log.e("EditProfileDialog", "Database upgrade error: " + e.getMessage());
        }
        
        initializeViews();
        setupButtonListeners();
        initialLoadProfileAndScreeningData();
    }

    private void initializeViews() {
        // Initialize close button
        android.widget.ImageButton closeButton = findViewById(R.id.close_button);
        closeButton.setOnClickListener(v -> dismiss());
        
        birthdayPickerBtn = findViewById(R.id.birthday_picker_btn);
        ageDisplay = findViewById(R.id.age_display);
        weightInput = findViewById(R.id.child_weight_input);
        heightInput = findViewById(R.id.child_height_input);
        genderBoyBtn = findViewById(R.id.gender_boy);
        genderGirlBtn = findViewById(R.id.gender_girl);
        dietaryDiversityInput = findViewById(R.id.dietary_diversity_input);
        muacInput = findViewById(R.id.muac_input);
        muacLabel = findViewById(R.id.muac_label);
        swellingYesBtn = findViewById(R.id.swelling_yes);
        swellingNoBtn = findViewById(R.id.swelling_no);
        weightLoss10PlusBtn = findViewById(R.id.weightloss_10plus);
        weightLoss5To10Btn = findViewById(R.id.weightloss_5to10);
        weightLossLess5Btn = findViewById(R.id.weightloss_less5);
        feedingGoodBtn = findViewById(R.id.feeding_good);
        feedingModerateBtn = findViewById(R.id.feeding_moderate);
        feedingPoorBtn = findViewById(R.id.feeding_poor);
        
        // Initialize physical signs buttons
        physicalThinBtn = findViewById(R.id.physical_thin);
        physicalShorterBtn = findViewById(R.id.physical_shorter);
        physicalWeakBtn = findViewById(R.id.physical_weak);
        physicalNoneBtn = findViewById(R.id.physical_none);
        
        // Initialize new clinical risk factor buttons
        illnessYesBtn = findViewById(R.id.illness_yes);
        illnessNoBtn = findViewById(R.id.illness_no);
        eatingDifficultyYesBtn = findViewById(R.id.eating_difficulty_yes);
        eatingDifficultyNoBtn = findViewById(R.id.eating_difficulty_no);
        foodInsecurityYesBtn = findViewById(R.id.food_insecurity_yes);
        foodInsecurityNoBtn = findViewById(R.id.food_insecurity_no);
        micronutrientYesBtn = findViewById(R.id.micronutrient_yes);
        micronutrientNoBtn = findViewById(R.id.micronutrient_no);
        functionalDeclineYesBtn = findViewById(R.id.functional_decline_yes);
        functionalDeclineNoBtn = findViewById(R.id.functional_decline_no);
        
        saveProfileBtn = findViewById(R.id.save_profile);
        barangaySpinner = findViewById(R.id.barangay_spinner);
        incomeSpinner = findViewById(R.id.income_spinner);
        
        // Set initial button backgrounds - use default button style like ScreeningFormActivity
        // No custom background resource needed
        
        // Set light text color for all buttons with better contrast
        setButtonTextColor(genderBoyBtn, 0xFFFFFFFF);
        setButtonTextColor(genderGirlBtn, 0xFFFFFFFF);
        setButtonTextColor(swellingYesBtn, 0xFFFFFFFF);
        setButtonTextColor(swellingNoBtn, 0xFFFFFFFF);
        setButtonTextColor(weightLoss10PlusBtn, 0xFFFFFFFF);
        setButtonTextColor(weightLoss5To10Btn, 0xFFFFFFFF);
        setButtonTextColor(weightLossLess5Btn, 0xFFFFFFFF);
        setButtonTextColor(feedingGoodBtn, 0xFFFFFFFF);
        setButtonTextColor(feedingModerateBtn, 0xFFFFFFFF);
        setButtonTextColor(feedingPoorBtn, 0xFFFFFFFF);
        setButtonTextColor(physicalThinBtn, 0xFFFFFFFF);
        setButtonTextColor(physicalShorterBtn, 0xFFFFFFFF);
        setButtonTextColor(physicalWeakBtn, 0xFFFFFFFF);
        setButtonTextColor(physicalNoneBtn, 0xFFFFFFFF);
        setButtonTextColor(illnessYesBtn, 0xFFFFFFFF);
        setButtonTextColor(illnessNoBtn, 0xFFFFFFFF);
        setButtonTextColor(eatingDifficultyYesBtn, 0xFFFFFFFF);
        setButtonTextColor(eatingDifficultyNoBtn, 0xFFFFFFFF);
        setButtonTextColor(foodInsecurityYesBtn, 0xFFFFFFFF);
        setButtonTextColor(foodInsecurityNoBtn, 0xFFFFFFFF);
        setButtonTextColor(micronutrientYesBtn, 0xFFFFFFFF);
        setButtonTextColor(micronutrientNoBtn, 0xFFFFFFFF);
        setButtonTextColor(functionalDeclineYesBtn, 0xFFFFFFFF);
        setButtonTextColor(functionalDeclineNoBtn, 0xFFFFFFFF);
        
        // Initialize button states like ScreeningFormActivity
        initializeButtonStates();

        // Setup barangay button click
        barangaySpinner.setOnClickListener(v -> showBarangayPickerDialog(BATAAN_BARANGAYS, selectedBarangay -> {
            this.selectedBarangay = selectedBarangay;
            barangaySpinner.setText(selectedBarangay);
        }));
        
        // Setup income button click
        incomeSpinner.setOnClickListener(v -> showIncomePickerDialog(INCOME_BRACKETS, selectedIncome -> {
            this.selectedIncome = selectedIncome;
            incomeSpinner.setText(selectedIncome);
        }));
    }

    private void setupButtonListeners() {
        // Gender
        genderBoyBtn.setOnClickListener(v -> {
            selectGender(true);
            updateButtonStatesForAction("gender", v);
        });
        genderGirlBtn.setOnClickListener(v -> {
            selectGender(false);
            updateButtonStatesForAction("gender", v);
        });
        
        // Swelling
        swellingYesBtn.setOnClickListener(v -> {
            selectSwelling(true);
            updateButtonStatesForAction("swelling", v);
        });
        swellingNoBtn.setOnClickListener(v -> {
            selectSwelling(false);
            updateButtonStatesForAction("swelling", v);
        });
        
        // Weight Loss
        weightLoss10PlusBtn.setOnClickListener(v -> {
            selectWeightLoss("10+");
            updateButtonStatesForAction("weightLoss", v);
        });
        weightLoss5To10Btn.setOnClickListener(v -> {
            selectWeightLoss("5-10");
            updateButtonStatesForAction("weightLoss", v);
        });
        weightLossLess5Btn.setOnClickListener(v -> {
            selectWeightLoss("less_5");
            updateButtonStatesForAction("weightLoss", v);
        });
        
        // Feeding Behavior
        feedingGoodBtn.setOnClickListener(v -> {
            selectFeeding("good");
            updateButtonStatesForAction("feeding", v);
        });
        feedingModerateBtn.setOnClickListener(v -> {
            selectFeeding("moderate");
            updateButtonStatesForAction("feeding", v);
        });
        feedingPoorBtn.setOnClickListener(v -> {
            selectFeeding("poor");
            updateButtonStatesForAction("feeding", v);
        });
        
        // Physical Signs Assessment - Fixed logic like ScreeningFormActivity
        physicalThinBtn.setOnClickListener(v -> {
            // If selecting "thin", unselect "none" and update states
            if (isThin == null || !isThin) {
                isThin = true;
                isNone = false; // Unselect "none" when selecting other signs
            } else {
                isThin = false;
            }
            updateButtonStatesForAction("physical", v);
        });
        physicalShorterBtn.setOnClickListener(v -> {
            // If selecting "shorter", unselect "none" and update states
            if (isShorter == null || !isShorter) {
                isShorter = true;
                isNone = false; // Unselect "none" when selecting other signs
            } else {
                isShorter = false;
            }
            updateButtonStatesForAction("physical", v);
        });
        physicalWeakBtn.setOnClickListener(v -> {
            // If selecting "weak", unselect "none" and update states
            if (isWeak == null || !isWeak) {
                isWeak = true;
                isNone = false; // Unselect "none" when selecting other signs
            } else {
                isWeak = false;
            }
            updateButtonStatesForAction("physical", v);
        });
        physicalNoneBtn.setOnClickListener(v -> {
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
        
        // Clinical Risk Factors
        illnessYesBtn.setOnClickListener(v -> {
            setBooleanField("has_recent_illness", true);
            updateButtonStatesForAction("illness", v);
        });
        illnessNoBtn.setOnClickListener(v -> {
            setBooleanField("has_recent_illness", false);
            updateButtonStatesForAction("illness", v);
        });
        eatingDifficultyYesBtn.setOnClickListener(v -> {
            setBooleanField("has_eating_difficulty", true);
            updateButtonStatesForAction("eatingDifficulty", v);
        });
        eatingDifficultyNoBtn.setOnClickListener(v -> {
            setBooleanField("has_eating_difficulty", false);
            updateButtonStatesForAction("eatingDifficulty", v);
        });
        foodInsecurityYesBtn.setOnClickListener(v -> {
            setBooleanField("has_food_insecurity", true);
            updateButtonStatesForAction("foodInsecurity", v);
        });
        foodInsecurityNoBtn.setOnClickListener(v -> {
            setBooleanField("has_food_insecurity", false);
            updateButtonStatesForAction("foodInsecurity", v);
        });
        micronutrientYesBtn.setOnClickListener(v -> {
            setBooleanField("has_micronutrient_deficiency", true);
            updateButtonStatesForAction("micronutrient", v);
        });
        micronutrientNoBtn.setOnClickListener(v -> {
            setBooleanField("has_micronutrient_deficiency", false);
            updateButtonStatesForAction("micronutrient", v);
        });
        functionalDeclineYesBtn.setOnClickListener(v -> {
            setBooleanField("has_functional_decline", true);
            updateButtonStatesForAction("functionalDecline", v);
        });
        functionalDeclineNoBtn.setOnClickListener(v -> {
            setBooleanField("has_functional_decline", false);
            updateButtonStatesForAction("functionalDecline", v);
        });
        
        // Birthday picker
        birthdayPickerBtn.setOnClickListener(v -> showBirthdayPicker());
        // Save
        saveProfileBtn.setOnClickListener(v -> saveProfileChanges());
    }

    private interface OnBarangayPicked { void onPick(String value); }
    private interface OnIncomePicked { void onPick(String value); }
    public interface ProfileUpdateListener { void onProfileUpdated(); }
    
    private ProfileUpdateListener profileUpdateListener;
    
    public void setProfileUpdateListener(ProfileUpdateListener listener) {
        this.profileUpdateListener = listener;
    }

    private void showBarangayPickerDialog(String[] items, OnBarangayPicked callback) {
        android.app.AlertDialog.Builder builder = new android.app.AlertDialog.Builder(getContext());

        android.widget.LinearLayout container = new android.widget.LinearLayout(getContext());
        container.setOrientation(android.widget.LinearLayout.VERTICAL);
        int pad = (int) (16 * getContext().getResources().getDisplayMetrics().density);
        container.setPadding(pad, pad, pad, pad);
        container.setBackgroundResource(R.drawable.rounded_bg);

        android.widget.TextView header = new android.widget.TextView(getContext());
        header.setText("Select Barangay");
        header.setTextSize(18);
        header.setTextColor(0xFF222222);
        header.setTypeface(android.graphics.Typeface.DEFAULT_BOLD);
        header.setPadding(pad, pad, pad, pad / 2);
        container.addView(header);

        android.widget.EditText search = new android.widget.EditText(getContext());
        search.setHint("Search barangay");
        search.setSingleLine(true);
        search.setBackgroundResource(R.drawable.edit_text_outline);
        search.setPadding(pad, pad / 2, pad, pad / 2);
        container.addView(search);

        // Group by municipality headers
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
        // Build from authoritative listing (array contains muni headers followed by barangays)
        for (String s : BATAAN_BARANGAYS) {
            if (municipalities.contains(s)) { currentMuni = s; muniToBarangays.putIfAbsent(s, new java.util.ArrayList<>()); }
            else if (currentMuni != null) { muniToBarangays.get(currentMuni).add(s); }
        }
        for (java.util.List<String> list : muniToBarangays.values()) java.util.Collections.sort(list, String::compareToIgnoreCase);
        java.util.List<String> display = new java.util.ArrayList<>();
        for (String muni : muniToBarangays.keySet()) { display.add("— " + muni + " —"); display.addAll(muniToBarangays.get(muni)); }

        android.widget.ListView listView = new android.widget.ListView(getContext());
        android.widget.ArrayAdapter<String> adapter = new android.widget.ArrayAdapter<String>(getContext(), android.R.layout.simple_list_item_1, display) {
            @Override public boolean isEnabled(int position) {
                String v = getItem(position); return v != null && !v.startsWith("— ");
            }
            @Override public android.view.View getView(int position, android.view.View convertView, android.view.ViewGroup parent) {
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
                String q = s == null ? "" : s.toString().trim().toLowerCase();
                java.util.List<String> disp = new java.util.ArrayList<>();
                for (String muni : muniToBarangays.keySet()) {
                    java.util.List<String> list = muniToBarangays.get(muni);
                    java.util.List<String> filtered = new java.util.ArrayList<>();
                    for (String brgy : list) if (brgy.toLowerCase().contains(q)) filtered.add(brgy);
                    if (!filtered.isEmpty() || muni.toLowerCase().contains(q)) {
                        disp.add("— " + muni + " —");
                        disp.addAll(filtered.isEmpty() ? list : filtered);
                    }
                }
                adapter.clear(); adapter.addAll(disp);
            }
            @Override public void afterTextChanged(android.text.Editable s) {}
        });
        dialog.show();
        if (dialog.getWindow() != null) {
            dialog.getWindow().setBackgroundDrawable(new android.graphics.drawable.ColorDrawable(android.graphics.Color.TRANSPARENT));
        }
    }
    
    private void showIncomePickerDialog(String[] items, OnIncomePicked callback) {
        android.app.AlertDialog.Builder builder = new android.app.AlertDialog.Builder(getContext());

        android.widget.LinearLayout container = new android.widget.LinearLayout(getContext());
        container.setOrientation(android.widget.LinearLayout.VERTICAL);
        int pad = (int) (16 * context.getResources().getDisplayMetrics().density);
        container.setPadding(pad, pad, pad, pad);
        container.setBackgroundResource(R.drawable.rounded_bg);

        // Custom header
        android.widget.TextView header = new android.widget.TextView(getContext());
        header.setText("Select Income");
        header.setTextSize(18);
        header.setTextColor(0xFF222222);
        header.setTypeface(android.graphics.Typeface.DEFAULT_BOLD);
        header.setPadding(pad, pad, pad, pad / 2);
        container.addView(header);

        android.widget.EditText search = new android.widget.EditText(getContext());
        search.setHint("Search income");
        search.setSingleLine(true);
        search.setBackgroundResource(R.drawable.edit_text_outline);
        search.setPadding(pad, pad / 2, pad, pad / 2);
        container.addView(search);

        android.widget.ListView listView = new android.widget.ListView(getContext());
        android.widget.ArrayAdapter<String> adapter = new android.widget.ArrayAdapter<String>(getContext(), android.R.layout.simple_list_item_1, items);
        listView.setAdapter(adapter);
        container.addView(listView);

        builder.setView(container);
        android.app.AlertDialog dialog = builder.create();
        listView.setOnItemClickListener((parent, view, position, id) -> {
            String value = adapter.getItem(position);
            if (value != null && callback != null) callback.onPick(value);
            dialog.dismiss();
        });
        search.addTextChangedListener(new android.text.TextWatcher() {
            @Override public void beforeTextChanged(CharSequence s, int start, int count, int after) {}
            @Override public void onTextChanged(CharSequence s, int start, int before, int count) {
                String q = s == null ? "" : s.toString().trim().toLowerCase();
                java.util.List<String> filtered = new java.util.ArrayList<>();
                for (String item : items) {
                    if (item.toLowerCase().contains(q)) filtered.add(item);
                }
                adapter.clear();
                adapter.addAll(filtered);
            }
            @Override public void afterTextChanged(android.text.Editable s) {}
        });
        dialog.show();
        if (dialog.getWindow() != null) {
            dialog.getWindow().setBackgroundDrawable(new android.graphics.drawable.ColorDrawable(android.graphics.Color.TRANSPARENT));
        }
    }

    private void loadCurrentProfileData() {
        Log.d("EditProfileDialog", "Loading profile data for user: " + userEmail);
        
        // Load data directly from API instead of local SQLite
        syncScreeningDataFromAPI();
    }
    
    private void initialLoadProfileAndScreeningData() {
        Log.d("EditProfileDialog", "Starting initial load for user: " + userEmail);
        
        // Only initiate API sync - UI will be updated after sync completes
        syncScreeningDataFromAPI();
    }
    
    private void updateUIFromLocalDB() {
        Log.d("EditProfileDialog", "updateUIFromLocalDB called for user: " + userEmail);
        
        // Instead of using local SQLite, fetch data directly from unified API
        // This method now just calls the main API sync method
        syncScreeningDataFromAPI();
    }
    
    private void syncScreeningDataFromAPI() {
        Log.d("EditProfileDialog", "Syncing screening data from API for user: " + userEmail);
        
        new Thread(() -> {
            try {
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                
                // Create request body for POST request to get_screening_data
                org.json.JSONObject requestBody = new org.json.JSONObject();
                requestBody.put("action", "get_screening_data");
                requestBody.put("email", userEmail);
                
                okhttp3.RequestBody body = okhttp3.RequestBody.create(
                    okhttp3.MediaType.parse("application/json; charset=utf-8"),
                    requestBody.toString()
                );
                
                okhttp3.Request request = new okhttp3.Request.Builder()
                    .url(Constants.UNIFIED_API_URL)
                    .post(body)
                    .addHeader("Content-Type", "application/json")
                    .build();
                
                try (okhttp3.Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        String responseBody = response.body().string();
                        Log.d("EditProfileDialog", "API response: " + responseBody);
                        
                        org.json.JSONObject jsonResponse = new org.json.JSONObject(responseBody);
                        Log.d("EditProfileDialog", "Parsed JSON response successfully");
                        Log.d("EditProfileDialog", "Full API response: " + responseBody);
                        
                                                if (jsonResponse.getBoolean("success")) {
                            // Check if this is a list response (preferences array) or single user response (data field)
                            if (jsonResponse.has("preferences")) {
                                // This is a list response - find the specific user
                                org.json.JSONArray preferences = jsonResponse.getJSONArray("preferences");
                                org.json.JSONObject userData = null;
                                
                                // Find the user with matching email
                                for (int i = 0; i < preferences.length(); i++) {
                                    org.json.JSONObject user = preferences.getJSONObject(i);
                                    if (userEmail.equals(user.optString("user_email", ""))) {
                                        userData = user;
                                        break;
                                    }
                                }
                                
                                if (userData != null) {
                                    Log.d("EditProfileDialog", "Found user data for: " + userEmail);
                                    
                                    // Log all available fields for this user
                                    java.util.Iterator<String> fields = userData.keys();
                                    while (fields.hasNext()) {
                                        String field = fields.next();
                                        Log.d("EditProfileDialog", "User field: " + field + " = " + userData.optString(field, "null"));
                                    }
                                    
                                    // Extract data from user object
                                    final String gender = userData.optString("gender", "");
                                    final String barangay = userData.optString("barangay", "");
                                    final String income = userData.optString("income", "");
                                    final double weight = userData.optDouble("weight", 0.0);
                                    final double height = userData.optDouble("height", 0.0);
                                    final String birthday = userData.optString("birthday", "");
                                    
                                    // Extract data directly from user object fields
                                    String swelling = userData.optString("swelling", "");
                                    String weightLoss = userData.optString("weight_loss", "");
                                    String feeding = userData.optString("feeding_behavior", "");
                                    String dietaryDiversity = userData.optString("dietary_diversity", "");
                                    
                                    // Extract physical signs from individual boolean fields
                                    Boolean hasRecentIllness = userData.optBoolean("has_recent_illness", false);
                                    Boolean hasEatingDifficulty = userData.optBoolean("has_eating_difficulty", false);
                                    Boolean hasFoodInsecurity = userData.optBoolean("has_food_insecurity", false);
                                    Boolean hasMicronutrientDeficiency = userData.optBoolean("has_micronutrient_deficiency", false);
                                    Boolean hasFunctionalDecline = userData.optBoolean("has_functional_decline", false);
                                    
                                    // Create final copies for use in lambda expressions
                                    final String finalGender = gender;
                                    final String finalBarangay = barangay;
                                    final String finalIncome = income;
                                    final double finalWeight = weight;
                                    final double finalHeight = height;
                                    final String finalBirthday = birthday;
                                    final String finalSwelling = swelling;
                                    final String finalWeightLoss = weightLoss;
                                    final String finalFeeding = feeding;
                                    final String finalDietaryDiversity = dietaryDiversity;
                                    final Boolean finalHasRecentIllness = hasRecentIllness;
                                    final Boolean finalHasEatingDifficulty = hasEatingDifficulty;
                                    final Boolean finalHasFoodInsecurity = hasFoodInsecurity;
                                    final Boolean finalHasMicronutrientDeficiency = hasMicronutrientDeficiency;
                                    final Boolean finalHasFunctionalDecline = hasFunctionalDecline;
                                    
                                    Log.d("EditProfileDialog", "Parsed screening data - gender: " + gender + ", swelling: " + swelling + ", weight_loss: " + weightLoss + ", feeding: " + feeding + ", dietary_diversity: " + dietaryDiversity + ", weight: " + weight + ", height: " + height + ", birthday: " + birthday + ", barangay: " + barangay + ", income: " + income);
                                    Log.d("EditProfileDialog", "Clinical risk factors - illness: " + hasRecentIllness + ", eating: " + hasEatingDifficulty + ", food: " + hasFoodInsecurity + ", micronutrient: " + hasMicronutrientDeficiency + ", functional: " + hasFunctionalDecline);
                                    
                                    // Update UI directly with API data
                                    if (context != null) {
                                        ((android.app.Activity) context).runOnUiThread(() -> {
                                            // Set weight and height input fields
                                            if (weight > 0) {
                                                weightInput.setText(String.valueOf((int)weight));
                                                Log.d("EditProfileDialog", "Set weight input to: " + weight);
                                            } else {
                                                Log.d("EditProfileDialog", "Weight is 0 or missing, leaving input empty");
                                            }
                                            if (height > 0) {
                                                heightInput.setText(String.valueOf((int)height));
                                                Log.d("EditProfileDialog", "Set height input to: " + height);
                                            } else {
                                                Log.d("EditProfileDialog", "Height is 0 or missing, leaving input empty");
                                            }
                                            
                                            // Set birthday if available
                                            if (birthday != null && !birthday.isEmpty() && !"null".equals(birthday) && birthdayPickerBtn != null) {
                                                birthdayPickerBtn.setText(birthday);
                                                Log.d("EditProfileDialog", "Set birthday to: " + birthday);
                                                
                                                // Parse birthday string to calculate age
                                                try {
                                                    java.text.SimpleDateFormat dateFormat = new java.text.SimpleDateFormat("yyyy-MM-dd");
                                                    java.util.Date birthDate = dateFormat.parse(birthday);
                                                    selectedBirthday = java.util.Calendar.getInstance();
                                                    selectedBirthday.setTime(birthDate);
                                                    
                                                    // Calculate and display age
                                                    ageInMonths = calculateAgeInMonths(selectedBirthday, java.util.Calendar.getInstance());
                                                    ageDisplay.setText("Age: " + ageInMonths + " months");
                                                    Log.d("EditProfileDialog", "Set age: " + ageInMonths + " months");
                                                } catch (Exception e) {
                                                    Log.e("EditProfileDialog", "Error parsing birthday: " + e.getMessage());
                                                    ageDisplay.setText("Age: 0 months");
                                                }
                                            } else {
                                                Log.d("EditProfileDialog", "Birthday is null, empty, or 'null' string, leaving button as default");
                                                birthdayPickerBtn.setText("Select Birthday");
                                                ageDisplay.setText("Age: 0 months");
                                                selectedBirthday = null;
                                                ageInMonths = 0;
                                            }
                                            
                                            // Set dietary diversity
                                            if (finalDietaryDiversity != null && !finalDietaryDiversity.isEmpty()) {
                                                dietaryDiversityInput.setText(finalDietaryDiversity);
                                                Log.d("EditProfileDialog", "Set dietary diversity to: " + finalDietaryDiversity);
                                            } else {
                                                Log.d("EditProfileDialog", "Dietary diversity is null or empty, leaving input empty");
                                            }

                                            // Set barangay spinner selection
                                            if (finalBarangay != null && !finalBarangay.isEmpty() && !"null".equals(finalBarangay)) {
                                                selectedBarangay = finalBarangay;
                                                barangaySpinner.setText(finalBarangay);
                                            } else {
                                                selectedBarangay = BATAAN_BARANGAYS[0];
                                                barangaySpinner.setText("Select Barangay");
                                            }
                                            
                                            // Set income spinner selection
                                            if (finalIncome != null && !finalIncome.isEmpty() && !"null".equals(finalIncome)) {
                                                selectedIncome = finalIncome;
                                                incomeSpinner.setText(finalIncome);
                                            } else {
                                                selectedIncome = INCOME_BRACKETS[0];
                                                incomeSpinner.setText("Select Income");
                                            }
                                            
                                                    // Apply shading for screening questions
        applyScreeningDataShading(finalGender, finalSwelling, finalWeightLoss, finalFeeding, "", finalDietaryDiversity, finalBarangay, finalIncome, 
            finalHasRecentIllness, finalHasEatingDifficulty, finalHasFoodInsecurity, finalHasMicronutrientDeficiency, finalHasFunctionalDecline,
            false, false, false, false);
        Log.d("EditProfileDialog", "Applied real-time shading from API");
        
        // Update button states after applying shading
        updatePhysicalSignsButtonStates();
                                        });
                                    }
                                } else {
                                    Log.d("EditProfileDialog", "User not found in preferences array for email: " + userEmail);
                                    // Show empty form for new users
                                    if (context != null) {
                                        ((android.app.Activity) context).runOnUiThread(() -> {
                                            // Clear all inputs for new user
                                            weightInput.setText("");
                                            heightInput.setText("");
                                            dietaryDiversityInput.setText("");
                                            muacInput.setText("");
                                            birthdayPickerBtn.setText("Select Birthday");
                                            ageDisplay.setText("Age: 0 months");
                                            selectedBirthday = null;
                                            ageInMonths = 0;
                                            
                                            // Reset all button states to unselected
                                            applyScreeningDataShading("", "", "", "", "", "", "", "", false, false, false, false, false, false, false, false, false);
                                            Log.d("EditProfileDialog", "Initialized empty form for new user");
                                            
                                            // Update button states after resetting
                                            updatePhysicalSignsButtonStates();
                                        });
                                    }
                                }
                            } else if (jsonResponse.has("data")) {
                                // This is a single user response - use the data field directly
                                org.json.JSONObject userData = jsonResponse.getJSONObject("data");
                                Log.d("EditProfileDialog", "Found user data in data field for: " + userEmail);
                                
                                                                    // Extract data directly from the comprehensive response
                                    final String gender = userData.optString("gender", "");
                                    final String swelling = userData.optString("swelling", "");
                                    final String weightLoss = userData.optString("weight_loss", "");
                                    final String feeding = userData.optString("feeding_behavior", "");
                                    final String dietaryDiversity = userData.optString("dietary_diversity", "");
                                    final String barangay = userData.optString("barangay", "");
                                    final String income = userData.optString("income", "");
                                    
                                    // Extract physical signs from individual boolean fields
                                    final Boolean physicalThin = userData.optBoolean("physical_thin", false);
                                    final Boolean physicalShorter = userData.optBoolean("physical_shorter", false);
                                    final Boolean physicalWeak = userData.optBoolean("physical_weak", false);
                                    final Boolean physicalNone = userData.optBoolean("physical_none", false);
                                    
                                    // Extract clinical risk factors from screening data
                                    final Boolean hasRecentIllness = userData.optBoolean("has_recent_illness", false);
                                    final Boolean hasEatingDifficulty = userData.optBoolean("has_eating_difficulty", false);
                                    final Boolean hasFoodInsecurity = userData.optBoolean("has_food_insecurity", false);
                                    final Boolean hasMicronutrientDeficiency = userData.optBoolean("has_micronutrient_deficiency", false);
                                    final Boolean hasFunctionalDecline = userData.optBoolean("has_functional_decline", false);
                                        
                                    // Extract weight, height, and birthday from screening data
                                    final double weight = userData.optDouble("weight", 0.0);
                                    final double height = userData.optDouble("height", 0.0);
                                    final String birthday = userData.optString("birthday", "");
                                        
                                Log.d("EditProfileDialog", "Parsed screening data - gender: " + gender + ", swelling: " + swelling + ", weight_loss: " + weightLoss + ", feeding: " + feeding + ", physical_thin: " + physicalThin + ", physical_shorter: " + physicalShorter + ", physical_weak: " + physicalWeak + ", physical_none: " + physicalNone + ", dietary_diversity: " + dietaryDiversity + ", weight: " + weight + ", height: " + height + ", birthday: " + birthday + ", barangay: " + barangay + ", income: " + income);
                                Log.d("EditProfileDialog", "Clinical risk factors - illness: " + hasRecentIllness + ", eating: " + hasEatingDifficulty + ", food: " + hasFoodInsecurity + ", micronutrient: " + hasMicronutrientDeficiency + ", functional: " + hasFunctionalDecline);
                                        
                                // Update UI directly with API data (no local SQLite needed)
                                if (context != null) {
                                    ((android.app.Activity) context).runOnUiThread(() -> {
                                        // Set weight and height input fields with better debugging
                                        if (weight > 0) {
                                            weightInput.setText(String.valueOf((int)weight));
                                            Log.d("EditProfileDialog", "Set weight input to: " + weight);
                                        } else {
                                            Log.d("EditProfileDialog", "Weight is 0 or missing, leaving input empty");
                                        }
                                        if (height > 0) {
                                            heightInput.setText(String.valueOf((int)height));
                                            Log.d("EditProfileDialog", "Set height input to: " + height);
                                        } else {
                                            Log.d("EditProfileDialog", "Height is 0 or missing, leaving input empty");
                                        }
                                        
                                        // Set birthday if available - improved null checking
                                        if (birthday != null && !birthday.isEmpty() && !"null".equals(birthday) && birthdayPickerBtn != null) {
                                            birthdayPickerBtn.setText(birthday);
                                            Log.d("EditProfileDialog", "Set birthday to: " + birthday);
                                            
                                            // Parse birthday string to calculate age
                                            try {
                                                java.text.SimpleDateFormat dateFormat = new java.text.SimpleDateFormat("yyyy-MM-dd");
                                                java.util.Date birthDate = dateFormat.parse(birthday);
                                                selectedBirthday = java.util.Calendar.getInstance();
                                                selectedBirthday.setTime(birthDate);
                                                
                                                // Calculate and display age
                                                ageInMonths = calculateAgeInMonths(selectedBirthday, java.util.Calendar.getInstance());
                                                ageDisplay.setText("Age: " + ageInMonths + " months");
                                                Log.d("EditProfileDialog", "Set age: " + ageInMonths + " months");
                                            } catch (Exception e) {
                                                Log.e("EditProfileDialog", "Error parsing birthday: " + e.getMessage());
                                                ageDisplay.setText("Age: 0 months");
                                            }
                                        } else {
                                            Log.d("EditProfileDialog", "Birthday is null, empty, or 'null' string, leaving button as default");
                                            birthdayPickerBtn.setText("Select Birthday");
                                            ageDisplay.setText("Age: 0 months");
                                            selectedBirthday = null;
                                            ageInMonths = 0;
                                        }
                                        
                                        // Set dietary diversity
                                        if (dietaryDiversity != null && !dietaryDiversity.isEmpty()) {
                                            dietaryDiversityInput.setText(dietaryDiversity);
                                            Log.d("EditProfileDialog", "Set dietary diversity to: " + dietaryDiversity);
                                        } else {
                                            Log.d("EditProfileDialog", "Dietary diversity is null or empty, leaving input empty");
                                        }

                                        // Set barangay spinner selection (default to first if empty) - improved null checking
                                        if (barangay != null && !barangay.isEmpty() && !"null".equals(barangay)) {
                                            selectedBarangay = barangay;
                                            barangaySpinner.setText(barangay);
                                            Log.d("EditProfileDialog", "Set barangay to: " + barangay);
                                        } else {
                                            selectedBarangay = BATAAN_BARANGAYS[0];
                                            barangaySpinner.setText("Select Barangay");
                                            Log.d("EditProfileDialog", "Barangay is null, empty, or 'null' string, set to default");
                                        }
                                        
                                        // Set income spinner selection (default to first if empty) - improved null checking
                                        if (income != null && !income.isEmpty() && !"null".equals(income)) {
                                            selectedIncome = income;
                                            incomeSpinner.setText(income);
                                            Log.d("EditProfileDialog", "Set income to: " + income);
                                        } else {
                                            selectedIncome = INCOME_BRACKETS[0];
                                            incomeSpinner.setText("Select Income");
                                            Log.d("EditProfileDialog", "Income is null, empty, or 'null' string, set to default");
                                        }
                                        
                                                // Apply shading for screening questions
        applyScreeningDataShading(gender, swelling, weightLoss, feeding, "", dietaryDiversity, barangay, income, 
            hasRecentIllness, hasEatingDifficulty, hasFoodInsecurity, hasMicronutrientDeficiency, hasFunctionalDecline,
            physicalThin, physicalShorter, physicalWeak, physicalNone);
        Log.d("EditProfileDialog", "Applied real-time shading from API");
        
        // Update button states after applying shading
        updatePhysicalSignsButtonStates();
                                    });
                                }
                            } else {
                                Log.d("EditProfileDialog", "API response has neither preferences nor data field");
                                // Show empty form for new users
                                if (context != null) {
                                    ((android.app.Activity) context).runOnUiThread(() -> {
                                        // Clear all inputs for new user
                                        weightInput.setText("");
                                        heightInput.setText("");
                                        dietaryDiversityInput.setText("");
                                        muacInput.setText("");
                                        birthdayPickerBtn.setText("Select Birthday");
                                        ageDisplay.setText("Age: 0 months");
                                        selectedBirthday = null;
                                        ageInMonths = 0;
                                        
                                        // Reset all button states to unselected
                                        applyScreeningDataShading("", "", "", "", "", "", "", "", false, false, false, false, false, false, false, false, false);
                                        Log.d("EditProfileDialog", "Initialized empty form for new user");
                                    });
                                }
                            }
                        } else {
                            Log.d("EditProfileDialog", "API returned success=false: " + jsonResponse.optString("message", "Unknown error"));
                            // Show empty form for new users
                            if (context != null) {
                                ((android.app.Activity) context).runOnUiThread(() -> {
                                    // Clear all inputs for new user
                                    weightInput.setText("");
                                    heightInput.setText("");
                                    dietaryDiversityInput.setText("");
                                    muacInput.setText("");
                                    birthdayPickerBtn.setText("Select Birthday");
                                    ageDisplay.setText("Age: 0 months");
                                    
                                    // Reset all button states to unselected
                                    applyScreeningDataShading("", "", "", "", "", "", "", "", false, false, false, false, false, false, false, false, false);
                                    Log.d("EditProfileDialog", "Initialized empty form for new user");
                                    
                                    // Update button states after resetting
                                    updatePhysicalSignsButtonStates();
                                });
                            }
                        }
                    } else {
                        Log.e("EditProfileDialog", "Failed to sync from API: " + response.code());
                        // Show empty form on API error
                        if (context != null) {
                            ((android.app.Activity) context).runOnUiThread(() -> {
                                // Clear all inputs
                                weightInput.setText("");
                                heightInput.setText("");
                                dietaryDiversityInput.setText("");
                                muacInput.setText("");
                                birthdayPickerBtn.setText("Select Birthday");
                                ageDisplay.setText("Age: 0 months");
                                
                                // Reset all button states to unselected
                                applyScreeningDataShading("", "", "", "", "", "", "", "", false, false, false, false, false, false, false, false, false);
                                Log.d("EditProfileDialog", "Initialized empty form due to API error");
                                
                                // Update button states after resetting
                                updatePhysicalSignsButtonStates();
                            });
                        }
                    }
                }
            } catch (Exception e) {
                Log.e("EditProfileDialog", "Error syncing screening data from API: " + e.getMessage());
                // Show empty form on exception
                if (context != null) {
                    ((android.app.Activity) context).runOnUiThread(() -> {
                        // Clear all inputs
                        weightInput.setText("");
                        heightInput.setText("");
                        dietaryDiversityInput.setText("");
                        muacInput.setText("");
                        birthdayPickerBtn.setText("Select Birthday");
                        ageDisplay.setText("Age: 0 months");
                        
                        // Reset all button states to unselected
                        applyScreeningDataShading("", "", "", "", "", "", "", "", false, false, false, false, false, false, false, false, false);
                        Log.d("EditProfileDialog", "Initialized empty form due to exception: " + e.getMessage());
                        
                        // Update button states after resetting
                        updatePhysicalSignsButtonStates();
                    });
                }
            }
        }).start();
    }
    

    
    private void applyScreeningDataShading(String gender, String swelling, String weightLoss, String feeding, String physicalSigns, String dietaryDiversity, String barangay, String income, 
                                          boolean hasRecentIllness, boolean hasEatingDifficulty, boolean hasFoodInsecurity, boolean hasMicronutrientDeficiency, boolean hasFunctionalDecline,
                                          Boolean physicalThin, Boolean physicalShorter, Boolean physicalWeak, Boolean physicalNone) {
        Log.d("EditProfileDialog", "applyScreeningDataShading called with - gender: '" + gender + "', swelling: '" + swelling + "', weight_loss: '" + weightLoss + "', feeding: '" + feeding + "', physical_signs: '" + physicalSigns + "', dietary_diversity: '" + dietaryDiversity + "', barangay: '" + barangay + "', income: '" + income + "'");
        
        // Debug: Log exact values for comparison
        Log.d("EditProfileDialog", "DEBUG VALUES - gender: '" + gender + "' (length: " + (gender != null ? gender.length() : "null") + ")");
        Log.d("EditProfileDialog", "DEBUG VALUES - swelling: '" + swelling + "' (length: " + (swelling != null ? swelling.length() : "null") + ")");
        Log.d("EditProfileDialog", "DEBUG VALUES - weightLoss: '" + weightLoss + "' (length: " + (weightLoss != null ? weightLoss.length() : "null") + ")");
        Log.d("EditProfileDialog", "DEBUG VALUES - feeding: '" + feeding + "' (length: " + (feeding != null ? feeding.length() : "null") + ")");
        
        // Reset all state variables first
        genderBoySelected = false;
        genderGirlSelected = false;
        swellingYesSelected = false;
        swellingNoSelected = false;
        weightLoss10PlusSelected = false;
        weightLoss5To10Selected = false;
        weightLossLess5Selected = false;
        feedingPoorSelected = false;
        feedingModerateSelected = false;
        feedingGoodSelected = false;
        
        // Reset clinical risk factor states
        this.hasRecentIllness = hasRecentIllness;
        this.hasEatingDifficulty = hasEatingDifficulty;
        this.hasFoodInsecurity = hasFoodInsecurity;
        this.hasMicronutrientDeficiency = hasMicronutrientDeficiency;
        this.hasFunctionalDecline = hasFunctionalDecline;
        
        // Reset physical signs states
        this.isThin = null;
        this.isShorter = null;
        this.isWeak = null;
        this.isNone = null;
        
        // Apply gender shading
        Log.d("EditProfileDialog", "DEBUG: Comparing gender '" + gender + "' with 'boy' and 'girl'");
        if ("boy".equals(gender)) {
            genderBoySelected = true;
            selectedGender = "boy";
            updateButtonStates(genderBoyBtn, genderGirlBtn);
            Log.d("EditProfileDialog", "Applied boy gender shading");
        } else if ("girl".equals(gender)) {
            genderGirlSelected = true;
            selectedGender = "girl";
            updateButtonStates(genderGirlBtn, genderBoyBtn);
            Log.d("EditProfileDialog", "Applied girl gender shading");
        } else {
            genderBoyBtn.setSelected(false);
            genderGirlBtn.setSelected(false);
            selectedGender = "";
            genderBoyBtn.setVisibility(View.VISIBLE);
            genderGirlBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(genderBoyBtn, false);
            setButtonSizeAndShape(genderGirlBtn, false);
            Log.d("EditProfileDialog", "No gender shading applied - gender value: '" + gender + "'");
        }
        
        // Apply swelling shading
        Log.d("EditProfileDialog", "DEBUG: Comparing swelling '" + swelling + "' with 'yes' and 'no'");
        if ("yes".equals(swelling)) {
            swellingYesSelected = true;
            updateButtonStates(swellingYesBtn, swellingNoBtn);
            Log.d("EditProfileDialog", "Applied yes swelling shading");
        } else if ("no".equals(swelling)) {
            swellingNoSelected = true;
            updateButtonStates(swellingNoBtn, swellingYesBtn);
            Log.d("EditProfileDialog", "Applied no swelling shading");
        } else {
            swellingYesBtn.setSelected(false);
            swellingNoBtn.setSelected(false);
            swellingYesBtn.setVisibility(View.VISIBLE);
            swellingNoBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(swellingYesBtn, false);
            setButtonSizeAndShape(swellingNoBtn, false);
            Log.d("EditProfileDialog", "No swelling shading applied - swelling value: '" + swelling + "'");
        }
        
        // Apply weight loss shading - Updated to match screening form values
        if (">10%".equals(weightLoss)) {
            weightLoss10PlusSelected = true;
            updateButtonStates(weightLoss10PlusBtn, weightLoss5To10Btn, weightLossLess5Btn);
            Log.d("EditProfileDialog", "Applied >10% weight loss shading");
        } else if ("5-10%".equals(weightLoss)) {
            weightLoss5To10Selected = true;
            updateButtonStates(weightLoss5To10Btn, weightLoss10PlusBtn, weightLossLess5Btn);
            Log.d("EditProfileDialog", "Applied 5-10% weight loss shading");
        } else if ("<5% or none".equals(weightLoss)) {
            weightLossLess5Selected = true;
            updateButtonStates(weightLossLess5Btn, weightLoss10PlusBtn, weightLoss5To10Btn);
            Log.d("EditProfileDialog", "Applied <5% or none weight loss shading");
        } else {
            weightLoss10PlusBtn.setSelected(false);
            weightLoss5To10Btn.setSelected(false);
            weightLossLess5Btn.setSelected(false);
            weightLoss10PlusBtn.setVisibility(View.VISIBLE);
            weightLoss5To10Btn.setVisibility(View.VISIBLE);
            weightLossLess5Btn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(weightLoss10PlusBtn, false);
            setButtonSizeAndShape(weightLoss5To10Btn, false);
            setButtonSizeAndShape(weightLossLess5Btn, false);
            Log.d("EditProfileDialog", "No weight loss shading applied for value: " + weightLoss);
        }
        
        // Apply feeding behavior shading - Updated to match screening form values
        if ("poor appetite".equals(feeding)) {
            feedingPoorSelected = true;
            updateButtonStates(feedingPoorBtn, feedingModerateBtn, feedingGoodBtn);
            Log.d("EditProfileDialog", "Applied poor appetite feeding shading");
        } else if ("moderate appetite".equals(feeding)) {
            feedingModerateSelected = true;
            updateButtonStates(feedingModerateBtn, feedingPoorBtn, feedingGoodBtn);
            Log.d("EditProfileDialog", "Applied moderate appetite feeding shading");
        } else if ("good appetite".equals(feeding)) {
            feedingGoodSelected = true;
            updateButtonStates(feedingGoodBtn, feedingPoorBtn, feedingModerateBtn);
            Log.d("EditProfileDialog", "Applied good appetite feeding shading");
        } else {
            feedingPoorBtn.setSelected(false);
            feedingModerateBtn.setSelected(false);
            feedingGoodBtn.setSelected(false);
            feedingPoorBtn.setVisibility(View.VISIBLE);
            feedingModerateBtn.setVisibility(View.VISIBLE);
            feedingGoodBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(feedingPoorBtn, false);
            setButtonSizeAndShape(feedingModerateBtn, false);
            setButtonSizeAndShape(feedingGoodBtn, false);
            Log.d("EditProfileDialog", "No feeding shading applied");
        }
        
        // Apply physical signs shading from individual boolean fields
        if (physicalThin != null && physicalThin) {
            this.isThin = true;
            physicalThinBtn.setSelected(true);
            physicalThinBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(physicalThinBtn, true);
            Log.d("EditProfileDialog", "Set physical sign: thin = true");
        } else {
            this.isThin = false;
            physicalThinBtn.setSelected(false);
            physicalThinBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(physicalThinBtn, false);
        }
        
        if (physicalShorter != null && physicalShorter) {
            this.isShorter = true;
            physicalShorterBtn.setSelected(true);
            physicalShorterBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(physicalShorterBtn, true);
            Log.d("EditProfileDialog", "Set physical sign: shorter = true");
        } else {
            this.isShorter = false;
            physicalShorterBtn.setSelected(false);
            physicalShorterBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(physicalShorterBtn, false);
        }
        
        if (physicalWeak != null && physicalWeak) {
            this.isWeak = true;
            physicalWeakBtn.setSelected(true);
            physicalWeakBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(physicalWeakBtn, true);
            Log.d("EditProfileDialog", "Set physical sign: weak = true");
        } else {
            this.isWeak = false;
            physicalWeakBtn.setSelected(false);
            physicalWeakBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(physicalWeakBtn, false);
        }
        
        if (physicalNone != null && physicalNone) {
            this.isNone = true;
            physicalNoneBtn.setSelected(true);
            physicalNoneBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(physicalNoneBtn, true);
            Log.d("EditProfileDialog", "Set physical sign: none = true");
        } else {
            this.isNone = false;
            physicalNoneBtn.setSelected(false);
            physicalNoneBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(physicalNoneBtn, false);
        }
        
        Log.d("EditProfileDialog", "Applied physical signs shading - thin: " + this.isThin + ", shorter: " + this.isShorter + ", weak: " + this.isWeak + ", none: " + this.isNone);
        
        // Set dietary diversity
        if (dietaryDiversity != null && !dietaryDiversity.isEmpty()) {
            dietaryDiversityInput.setText(dietaryDiversity);
            Log.d("EditProfileDialog", "Set dietary diversity: " + dietaryDiversity);
        }

        // Set barangay
        if (barangay != null && !barangay.isEmpty() && !"null".equals(barangay)) {
            selectedBarangay = barangay;
            barangaySpinner.setText(barangay);
            Log.d("EditProfileDialog", "Set barangay to: " + barangay);
        } else {
            Log.d("EditProfileDialog", "Barangay is null, empty, or 'null' string, leaving spinner as default");
        }

        // Set income
        if (income != null && !income.isEmpty() && !"null".equals(income)) {
            selectedIncome = income;
            incomeSpinner.setText(income);
            Log.d("EditProfileDialog", "Set income to: " + income);
        } else {
            Log.d("EditProfileDialog", "Income is null, empty, or 'null' string, leaving spinner as default");
        }
        
        // Update button states for clinical risk factors
        if (this.hasRecentIllness != null && this.hasRecentIllness) {
            updateButtonStates(illnessYesBtn, illnessNoBtn);
        } else if (this.hasRecentIllness != null && !this.hasRecentIllness) {
            updateButtonStates(illnessNoBtn, illnessYesBtn);
        } else {
            illnessYesBtn.setSelected(false);
            illnessNoBtn.setSelected(false);
            illnessYesBtn.setVisibility(View.VISIBLE);
            illnessNoBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(illnessYesBtn, false);
            setButtonSizeAndShape(illnessNoBtn, false);
        }
        
        if (this.hasEatingDifficulty != null && this.hasEatingDifficulty) {
            updateButtonStates(eatingDifficultyYesBtn, eatingDifficultyNoBtn);
        } else if (this.hasEatingDifficulty != null && !this.hasEatingDifficulty) {
            updateButtonStates(eatingDifficultyNoBtn, eatingDifficultyYesBtn);
        } else {
            eatingDifficultyYesBtn.setSelected(false);
            eatingDifficultyNoBtn.setSelected(false);
            eatingDifficultyYesBtn.setVisibility(View.VISIBLE);
            eatingDifficultyNoBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(eatingDifficultyYesBtn, false);
            setButtonSizeAndShape(eatingDifficultyNoBtn, false);
        }
        
        if (this.hasFoodInsecurity != null && this.hasFoodInsecurity) {
            updateButtonStates(foodInsecurityYesBtn, foodInsecurityNoBtn);
        } else if (this.hasFoodInsecurity != null && !this.hasFoodInsecurity) {
            updateButtonStates(foodInsecurityNoBtn, foodInsecurityYesBtn);
        } else {
            foodInsecurityYesBtn.setSelected(false);
            foodInsecurityNoBtn.setSelected(false);
            foodInsecurityYesBtn.setVisibility(View.VISIBLE);
            foodInsecurityNoBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(foodInsecurityYesBtn, false);
            setButtonSizeAndShape(foodInsecurityNoBtn, false);
        }
        
        if (this.hasMicronutrientDeficiency != null && this.hasMicronutrientDeficiency) {
            updateButtonStates(micronutrientYesBtn, micronutrientNoBtn);
        } else if (this.hasMicronutrientDeficiency != null && !this.hasMicronutrientDeficiency) {
            updateButtonStates(micronutrientNoBtn, micronutrientYesBtn);
        } else {
            micronutrientYesBtn.setSelected(false);
            micronutrientNoBtn.setSelected(false);
            micronutrientYesBtn.setVisibility(View.VISIBLE);
            micronutrientNoBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(micronutrientYesBtn, false);
            setButtonSizeAndShape(micronutrientNoBtn, false);
        }
        
        if (this.hasFunctionalDecline != null && this.hasFunctionalDecline) {
            updateButtonStates(functionalDeclineYesBtn, functionalDeclineNoBtn);
        } else if (this.hasFunctionalDecline != null && !this.hasFunctionalDecline) {
            updateButtonStates(functionalDeclineNoBtn, functionalDeclineYesBtn);
        } else {
            functionalDeclineYesBtn.setSelected(false);
            functionalDeclineNoBtn.setSelected(false);
            functionalDeclineYesBtn.setVisibility(View.VISIBLE);
            functionalDeclineNoBtn.setVisibility(View.VISIBLE);
            setButtonSizeAndShape(functionalDeclineYesBtn, false);
            setButtonSizeAndShape(functionalDeclineNoBtn, false);
        }
        
        Log.d("EditProfileDialog", "Applied clinical risk factor shading - illness: " + this.hasRecentIllness + ", eating: " + this.hasEatingDifficulty + ", food: " + this.hasFoodInsecurity + ", micronutrient: " + this.hasMicronutrientDeficiency + ", functional: " + this.hasFunctionalDecline);
        
        Log.d("EditProfileDialog", "applyScreeningDataShading completed");
    }

    // Selection helpers
    private void selectGender(boolean isBoy) {
        genderBoySelected = isBoy;
        genderGirlSelected = !isBoy;
        if (isBoy) {
            updateButtonStates(genderBoyBtn, genderGirlBtn);
        } else {
            updateButtonStates(genderGirlBtn, genderBoyBtn);
        }
    }
    private void selectSwelling(boolean yes) {
        swellingYesSelected = yes;
        swellingNoSelected = !yes;
        if (yes) {
            updateButtonStates(swellingYesBtn, swellingNoBtn);
        } else {
            updateButtonStates(swellingNoBtn, swellingYesBtn);
        }
    }
    private void selectWeightLoss(String which) {
        weightLoss10PlusSelected = "10+".equalsIgnoreCase(which);
        weightLoss5To10Selected = "5-10".equalsIgnoreCase(which);
        weightLossLess5Selected = "less_5".equalsIgnoreCase(which);
        
        if ("10+".equalsIgnoreCase(which)) {
            updateButtonStates(weightLoss10PlusBtn, weightLoss5To10Btn, weightLossLess5Btn);
        } else if ("5-10".equalsIgnoreCase(which)) {
            updateButtonStates(weightLoss5To10Btn, weightLoss10PlusBtn, weightLossLess5Btn);
        } else if ("less_5".equalsIgnoreCase(which)) {
            updateButtonStates(weightLossLess5Btn, weightLoss10PlusBtn, weightLoss5To10Btn);
        }
    }
    private void selectFeeding(String which) {
        feedingGoodSelected = "good".equals(which);
        feedingModerateSelected = "moderate".equals(which);
        feedingPoorSelected = "poor".equals(which);
        
        if ("good".equals(which)) {
            updateButtonStates(feedingGoodBtn, feedingModerateBtn, feedingPoorBtn);
        } else if ("moderate".equals(which)) {
            updateButtonStates(feedingModerateBtn, feedingGoodBtn, feedingPoorBtn);
        } else if ("poor".equals(which)) {
            updateButtonStates(feedingPoorBtn, feedingGoodBtn, feedingModerateBtn);
        }
    }
    


    private void setBooleanField(String field, boolean value) {
        switch (field) {
            case "has_recent_illness":
                hasRecentIllness = value;
                if (value) {
                    updateButtonStates(illnessYesBtn, illnessNoBtn);
                } else {
                    updateButtonStates(illnessNoBtn, illnessYesBtn);
                }
                break;
            case "has_eating_difficulty":
                hasEatingDifficulty = value;
                if (value) {
                    updateButtonStates(eatingDifficultyYesBtn, eatingDifficultyNoBtn);
                } else {
                    updateButtonStates(eatingDifficultyNoBtn, eatingDifficultyYesBtn);
                }
                break;
            case "has_food_insecurity":
                hasFoodInsecurity = value;
                if (value) {
                    updateButtonStates(foodInsecurityYesBtn, foodInsecurityNoBtn);
                } else {
                    updateButtonStates(foodInsecurityNoBtn, foodInsecurityYesBtn);
                }
                break;
            case "has_micronutrient_deficiency":
                hasMicronutrientDeficiency = value;
                if (value) {
                    updateButtonStates(micronutrientYesBtn, micronutrientNoBtn);
                } else {
                    updateButtonStates(micronutrientNoBtn, micronutrientYesBtn);
                }
                break;
            case "has_functional_decline":
                hasFunctionalDecline = value;
                if (value) {
                    updateButtonStates(functionalDeclineYesBtn, functionalDeclineNoBtn);
                } else {
                    updateButtonStates(functionalDeclineNoBtn, functionalDeclineYesBtn);
                }
                break;
        }
    }

    private void showBirthdayPicker() {
        final java.util.Calendar calendar = selectedBirthday != null ? (java.util.Calendar) selectedBirthday.clone() : java.util.Calendar.getInstance();
        android.app.DatePickerDialog datePickerDialog = new android.app.DatePickerDialog(context, (view, year, month, dayOfMonth) -> {
            calendar.set(year, month, dayOfMonth);
            selectedBirthday = calendar;
            String dateStr = String.format("%04d-%02d-%02d", year, month + 1, dayOfMonth);
            birthdayPickerBtn.setText(dateStr);
            ageInMonths = calculateAgeInMonths(selectedBirthday, java.util.Calendar.getInstance());
            ageDisplay.setText("Age: " + ageInMonths + " months");
            
            // Update MUAC visibility based on age
            updateMUACVisibility(ageInMonths);
        }, calendar.get(java.util.Calendar.YEAR), calendar.get(java.util.Calendar.MONTH), calendar.get(java.util.Calendar.DAY_OF_MONTH));
        
        // Style the date picker dialog header to match our dark grey theme
        datePickerDialog.setOnShowListener(dialog -> {
            // Get the dialog's window and style the header
            android.view.Window window = datePickerDialog.getWindow();
            if (window != null) {
                // Set the header background color to dark grey
                window.setStatusBarColor(0xFF424242); // Dark grey for header
                // Also try to set the navigation bar color for better header styling
                window.setNavigationBarColor(0xFF424242);
            }
        });
        
        // Set the dialog background to dark grey for the header area
        datePickerDialog.getDatePicker().setBackgroundColor(0xFF424242);
        
        datePickerDialog.show();
    }

    private void updateMUACVisibility(int ageMonths) {
        if (muacLabel != null && muacInput != null) {
            if (ageMonths >= 6 && ageMonths <= 59) {
                // Children 6-59 months: Show MUAC input
                muacLabel.setVisibility(View.VISIBLE);
                muacInput.setVisibility(View.VISIBLE);
                Log.d("EditProfileDialog", "MUAC input shown for age: " + ageInMonths + " months");
            } else {
                // Other ages: Hide MUAC input
                muacLabel.setVisibility(View.GONE);
                muacInput.setVisibility(View.GONE);
                Log.d("EditProfileDialog", "MUAC input hidden for age: " + ageInMonths + " months");
            }
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

    private void saveProfileChanges() {
        // Get values from existing UI elements
        String weightStr = weightInput.getText().toString().trim();
        String heightStr = heightInput.getText().toString().trim();
        String dietaryDiversityStr = dietaryDiversityInput.getText().toString().trim();
        
        // Get selected values from buttons
        String selectedBarangay = this.selectedBarangay;
        String selectedIncome = this.selectedIncome;

        // Validate required fields
        if (weightStr.isEmpty() || heightStr.isEmpty() || selectedBarangay.isEmpty() || selectedIncome.isEmpty()) {
            Toast.makeText(getContext(), "Please fill in all required fields", Toast.LENGTH_SHORT).show();
                return;
            }
            
            // Calculate BMI
        double weightValue = Double.parseDouble(weightStr);
        double heightValue = Double.parseDouble(heightStr);
        double bmi = weightValue / ((heightValue / 100) * (heightValue / 100));

        // Create a single ContentValues object for all data
        ContentValues allValues = new ContentValues();
        
        // Profile data - only save to screening_answers JSON, not individual columns
        allValues.put(UserPreferencesDbHelper.COL_USER_EMAIL, userEmail);
        allValues.put(UserPreferencesDbHelper.COL_BARANGAY, selectedBarangay);
        allValues.put(UserPreferencesDbHelper.COL_INCOME, selectedIncome);

        // Create comprehensive screening answers JSON for backward compatibility
        try {
            JSONObject screeningAnswers = new JSONObject();
            screeningAnswers.put("gender", selectedGender);
            screeningAnswers.put("weight", weightValue);
            screeningAnswers.put("height", heightValue);
            screeningAnswers.put("bmi", bmi);
            if (selectedBirthday != null) {
                String birthdayStr = String.format("%04d-%02d-%02d", 
                    selectedBirthday.get(java.util.Calendar.YEAR),
                    selectedBirthday.get(java.util.Calendar.MONTH) + 1,
                    selectedBirthday.get(java.util.Calendar.DAY_OF_MONTH));
                screeningAnswers.put("birthday", birthdayStr);
            }
            screeningAnswers.put("barangay", selectedBarangay);
            screeningAnswers.put("income", selectedIncome);
            
            // Add screening question answers from current button states
            screeningAnswers.put("swelling", getSelectedSwelling());
            screeningAnswers.put("weight_loss", getSelectedWeightLoss());
            screeningAnswers.put("feeding_behavior", getSelectedFeeding());
            // Physical signs are now collected in edit profile dialog
            screeningAnswers.put("physical_signs", getSelectedPhysicalSigns().toString());
            screeningAnswers.put("dietary_diversity", dietaryDiversityStr.isEmpty() ? "0" : dietaryDiversityStr);
            
            // Add new clinical risk factors
            screeningAnswers.put("has_recent_illness", hasRecentIllness);
            screeningAnswers.put("has_eating_difficulty", hasEatingDifficulty);
            screeningAnswers.put("has_food_insecurity", hasFoodInsecurity);
            screeningAnswers.put("has_micronutrient_deficiency", hasMicronutrientDeficiency);
            screeningAnswers.put("has_functional_decline", hasFunctionalDecline);
            
            // Add existing screening data if available (for fields not covered above)
            if (existingScreeningData != null) {
                try {
                    JSONObject existingScreening = new JSONObject(existingScreeningData);
                    // Copy existing screening answers that aren't being updated
                    Iterator<String> keys = existingScreening.keys();
                    while (keys.hasNext()) {
                        String key = keys.next();
                        if (!screeningAnswers.has(key)) {
                            screeningAnswers.put(key, existingScreening.get(key));
                        }
                    }
                } catch (Exception e) {
                    Log.e("EditProfileDialog", "Error parsing existing screening data: " + e.getMessage());
                }
            }
            
            allValues.put(UserPreferencesDbHelper.COL_SCREENING_ANSWERS, screeningAnswers.toString());
            Log.d("EditProfileDialog", "Complete screening answers JSON: " + screeningAnswers.toString());
            
            // Also save the calculated BMI
            allValues.put(UserPreferencesDbHelper.COL_USER_BMI, bmi);
            allValues.put(UserPreferencesDbHelper.COL_USER_AGE, ageInMonths);
            allValues.put(UserPreferencesDbHelper.COL_GENDER, selectedGender);
            allValues.put(UserPreferencesDbHelper.COL_USER_HEIGHT, heightValue);
            allValues.put(UserPreferencesDbHelper.COL_USER_WEIGHT, weightValue);
            
        } catch (Exception e) {
            Log.e("EditProfileDialog", "Error creating screening answers JSON: " + e.getMessage());
            Toast.makeText(getContext(), "Error saving data", Toast.LENGTH_SHORT).show();
            return;
        }

        // Calculate and save risk score (outside try-catch block)
        int riskScore = calculateRiskScore(
            selectedGender,
            getSelectedSwelling(),
            getSelectedWeightLoss(),
            getSelectedFeeding(),
            getSelectedPhysicalSigns().toString(),
            dietaryDiversityStr.isEmpty() ? "0" : dietaryDiversityStr
        );
        allValues.put(UserPreferencesDbHelper.COL_RISK_SCORE, riskScore);
        Log.d("EditProfileDialog", "Calculated risk score: " + riskScore);

        // Debug: Log the complete ContentValues being saved
        Log.d("EditProfileDialog", "Saving all values: " + allValues.toString());

        // Single database operation
        SQLiteDatabase db = dbHelper.getWritableDatabase();
        long result = db.insertWithOnConflict(
            UserPreferencesDbHelper.TABLE_NAME,
            null,
            allValues,
            SQLiteDatabase.CONFLICT_REPLACE
        );
            
        if (result != -1) {
            Toast.makeText(getContext(), "Profile updated successfully", Toast.LENGTH_SHORT).show();
            
            // Sync to web API with the calculated risk score
            syncProfileChangesToAPI("", selectedBarangay, selectedIncome, weightStr, heightStr, 
                                 "", "", "", bmi);
            
            // Log the successful save with risk score
            Log.d("EditProfileDialog", "Profile saved successfully with risk score: " + riskScore);
            
            // Notify listener and close dialog
            if (profileUpdateListener != null) {
                profileUpdateListener.onProfileUpdated();
            }
            dismiss();
        } else {
            Toast.makeText(getContext(), "Failed to update profile", Toast.LENGTH_SHORT).show();
        }
    }
    
    private void syncProfileChangesToAPI(String name, String selectedBarangay, String selectedIncome, 
                                       String weight, String height, String allergies, String dietPrefs, 
                                       String avoidFoods, double bmi) {
        Log.d("EditProfileDialog", "Syncing profile changes to API for user: " + userEmail);
        
        new Thread(() -> {
            try {
                // Calculate the updated risk score
                int updatedRiskScore = calculateRiskScore(
                    selectedGender,
                    getSelectedSwelling(),
                    getSelectedWeightLoss(),
                    getSelectedFeeding(),
                    getSelectedPhysicalSigns().toString(),
                    dietaryDiversityInput.getText().toString().trim().isEmpty() ? "0" : dietaryDiversityInput.getText().toString().trim()
                );
                
                Log.d("EditProfileDialog", "Updated risk score: " + updatedRiskScore);
                
                // Create screening answers JSON with all data including weight and height
                org.json.JSONObject screeningAnswers = new org.json.JSONObject();
                screeningAnswers.put("gender", selectedGender);
                screeningAnswers.put("weight", Double.parseDouble(weight));
                screeningAnswers.put("height", Double.parseDouble(height));
                screeningAnswers.put("bmi", bmi);
                if (selectedBirthday != null) {
                    String birthdayStr = String.format("%04d-%02d-%02d", 
                        selectedBirthday.get(java.util.Calendar.YEAR),
                        selectedBirthday.get(java.util.Calendar.MONTH) + 1,
                        selectedBirthday.get(java.util.Calendar.DAY_OF_MONTH));
                    screeningAnswers.put("birthday", birthdayStr);
                }
                screeningAnswers.put("barangay", selectedBarangay);
                screeningAnswers.put("income", selectedIncome);
                screeningAnswers.put("allergies", allergies);
                screeningAnswers.put("diet_prefs", dietPrefs);
                screeningAnswers.put("avoid_foods", avoidFoods);
                
                // Add screening question answers
                screeningAnswers.put("swelling", getSelectedSwelling());
                screeningAnswers.put("weight_loss", getSelectedWeightLoss());
                screeningAnswers.put("feeding_behavior", getSelectedFeeding());
                screeningAnswers.put("physical_signs", getSelectedPhysicalSigns().toString());
                screeningAnswers.put("dietary_diversity", dietaryDiversityInput.getText().toString().trim().isEmpty() ? "0" : dietaryDiversityInput.getText().toString().trim());
                
                // Add clinical risk factors
                screeningAnswers.put("has_recent_illness", hasRecentIllness);
                screeningAnswers.put("has_eating_difficulty", hasEatingDifficulty);
                screeningAnswers.put("has_food_insecurity", hasFoodInsecurity);
                screeningAnswers.put("has_micronutrient_deficiency", hasMicronutrientDeficiency);
                screeningAnswers.put("has_functional_decline", hasFunctionalDecline);
                
                Log.d("EditProfileDialog", "Screening answers JSON: " + screeningAnswers.toString());
                
                // Create user data JSON for unified API
                org.json.JSONObject requestData = new org.json.JSONObject();
                requestData.put("action", "save_screening");
                requestData.put("email", userEmail);
                requestData.put("username", userEmail.split("@")[0]); // Extract username from email
                requestData.put("screening_data", screeningAnswers.toString());
                requestData.put("risk_score", updatedRiskScore); // Include the updated risk score
                
                Log.d("EditProfileDialog", "Request data JSON: " + requestData.toString());
                
                // Send to unified API endpoint
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                okhttp3.RequestBody body = okhttp3.RequestBody.create(
                    okhttp3.MediaType.parse("application/json; charset=utf-8"),
                    requestData.toString()
                );
                
                okhttp3.Request request = new okhttp3.Request.Builder()
                    .url(Constants.UNIFIED_API_URL)
                    .post(body)
                    .addHeader("Content-Type", "application/json")
                    .build();
                
                try (okhttp3.Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        String responseBody = response.body().string();
                        Log.d("EditProfileDialog", "API response: " + responseBody);
                        
                        // Parse response to check if it was successful
                        try {
                            org.json.JSONObject jsonResponse = new org.json.JSONObject(responseBody);
                            if (jsonResponse.getBoolean("success")) {
                                Log.d("EditProfileDialog", "Profile successfully synced to API with risk score: " + updatedRiskScore);
                                
                                // Show success message
                                if (context != null) {
                                    ((android.app.Activity) context).runOnUiThread(() -> {
                                        android.widget.Toast.makeText(context, "Profile updated! Risk Score: " + updatedRiskScore, android.widget.Toast.LENGTH_LONG).show();
                                    });
                                }
                                
                                // Trigger a refresh of the main activity to update risk score and food recommendations
                                if (profileUpdateListener != null) {
                                    profileUpdateListener.onProfileUpdated();
                                }
                            } else {
                                Log.e("EditProfileDialog", "API returned success=false: " + jsonResponse.optString("message", "Unknown error"));
                                if (context != null) {
                                    ((android.app.Activity) context).runOnUiThread(() -> {
                                        android.widget.Toast.makeText(context, "API Error: " + jsonResponse.optString("message", "Failed to update profile"), android.widget.Toast.LENGTH_LONG).show();
                                    });
                                }
                            }
                        } catch (Exception e) {
                            Log.e("EditProfileDialog", "Error parsing API response: " + e.getMessage());
                        }
                    } else {
                        Log.e("EditProfileDialog", "API sync failed: " + response.code());
                        if (context != null) {
                            ((android.app.Activity) context).runOnUiThread(() -> {
                                android.widget.Toast.makeText(context, "Failed to sync with API (HTTP " + response.code() + ")", android.widget.Toast.LENGTH_LONG).show();
                            });
                        }
                    }
                }
            } catch (Exception e) {
                Log.e("EditProfileDialog", "Error syncing to API: " + e.getMessage());
                if (context != null) {
                    ((android.app.Activity) context).runOnUiThread(() -> {
                        android.widget.Toast.makeText(context, "Error syncing profile: " + e.getMessage(), android.widget.Toast.LENGTH_LONG).show();
                    });
                }
            }
        }).start();
    }
    
    private int calculateRiskScore(String gender, String swelling, String weightLoss, String feeding, String physicalSigns, String dietaryDiversity) {
        int score = 0;
        
        // Check for edema first - this overrides everything else
        if ("yes".equalsIgnoreCase(swelling)) {
            return 100; // Immediate severe risk - urgent referral alert
        }
        
        // Calculate age-based anthropometry scoring
        if (ageInMonths >= 6 && ageInMonths <= 59) {
            // Children 6-59 months: Use MUAC if available, otherwise fallback to weight-for-height
            try {
                double weight = Double.parseDouble(weightInput.getText().toString());
                double height = Double.parseDouble(heightInput.getText().toString());
                if (height > 0) {
                    double wfh = weight / (height / 100.0);
                    // Approximate MUAC-based scoring using weight-for-height
                    if (wfh < 0.8) score += 40;      // Severe acute malnutrition
                    else if (wfh < 0.9) score += 25; // Moderate acute malnutrition
                    else score += 0;                  // Normal
                }
            } catch (NumberFormatException e) {
                // If measurements not available, use default risk
                score += 20;
            }
        } else if (ageInMonths >= 240) {
            // Adults 20+ years: Use BMI
            try {
                double weight = Double.parseDouble(weightInput.getText().toString());
                double height = Double.parseDouble(heightInput.getText().toString());
                if (height > 0) {
                    double bmi = weight / Math.pow(height / 100.0, 2);
                    if (bmi < 16.5) score += 40;      // Severe underweight
                    else if (bmi < 18.5) score += 25; // Moderate underweight
                    else score += 0;                   // Normal weight
                }
            } catch (NumberFormatException e) {
                // If measurements not available, use default risk
                score += 20;
            }
        } else {
            // Children/adolescents 5-19 years: Use BMI-for-age
            try {
                double weight = Double.parseDouble(weightInput.getText().toString());
                double height = Double.parseDouble(heightInput.getText().toString());
                if (height > 0) {
                    double bmi = weight / Math.pow(height / 100.0, 2);
                    if (bmi < 15) score += 40;        // Severe thinness
                    else if (bmi < 17) score += 30;   // Moderate thinness
                    else if (bmi < 18.5) score += 20; // Mild thinness
                    else score += 0;                   // Normal
                }
            } catch (NumberFormatException e) {
                // If measurements not available, use default risk
                score += 20;
            }
        }
        
        // Weight loss scoring - match screening form values
        if (">10%".equalsIgnoreCase(weightLoss)) score += 20;
        else if ("5-10%".equalsIgnoreCase(weightLoss)) score += 10;
        else if ("<5% or none".equalsIgnoreCase(weightLoss)) score += 0;
        else if ("<5%".equalsIgnoreCase(weightLoss)) score += 0;
        
        // Feeding behavior scoring - match screening form values
        if ("poor appetite".equalsIgnoreCase(feeding)) score += 8;
        else if ("moderate appetite".equalsIgnoreCase(feeding)) score += 8;
        else if ("good appetite".equalsIgnoreCase(feeding)) score += 0;
        
        // Physical signs scoring - Updated to match verified system
        if (isThin != null && isThin) score += 8;      // Was 5, should be 8
        if (isShorter != null && isShorter) score += 8;   // Was 5, should be 8
        if (isWeak != null && isWeak) score += 8;      // Was 5, should be 8
        
        // Dietary diversity scoring
        try {
            int diversity = Integer.parseInt(dietaryDiversity);
            if (diversity < 4) score += 10;      // Was 10, should be 10
            else if (diversity < 6) score += 5;  // Was 5, should be 5
            else score += 0;                     // 6+ food groups
        } catch (NumberFormatException e) {
            score += 5;
        }
        
        // Clinical & Social Risk Factors scoring - Updated to match verified system
        if (hasRecentIllness != null && hasRecentIllness) score += 8;           // Recent acute illness
        if (hasEatingDifficulty != null && hasEatingDifficulty) score += 8;        // Difficulty chewing/swallowing
        if (hasFoodInsecurity != null && hasFoodInsecurity) score += 10;         // Food insecurity / skipped meals
        if (hasMicronutrientDeficiency != null && hasMicronutrientDeficiency) score += 6; // Visible signs of micronutrient deficiency
        if (hasFunctionalDecline != null && hasFunctionalDecline) score += 8;       // Functional decline (older adults only)
        
        // Ensure score doesn't exceed 100
        return Math.min(score, 100);
    }
    
    // Helper methods for risk score calculation
    private double calculateWFAZ(int ageMonths, double weight, String sex) {
        return WHOReferenceData.calculateZScore("WFA", ageMonths, weight, sex);
    }
    
    private double calculateHFAZ(int ageMonths, double height, String sex) {
        return WHOReferenceData.calculateZScore("HFA", ageMonths, height, sex);
    }
    
    private double calculateWFHZ(double height, double weight, String sex) {
        return WHOReferenceData.calculateZScore("WFH", height, weight, sex);
    }
    
    private int scoreDietaryDiversity(int groups) {
        if (groups >= 5) return 0;
        else if (groups >= 3) return 1;
        else return 2;
    }
    
    private int scoreFeedingBehavior(String behavior) {
        switch (behavior) {
            case "good appetite": return 0;
            case "moderate appetite": return 1;
            case "poor appetite": return 2;
            default: return 1;
        }
    }
    
    private double calculateBMI(double height, double weight) {
        // BMI = weight(kg) / height(m)²
        double heightInMeters = height / 100.0;
        return weight / (heightInMeters * heightInMeters);
    }
    
    private int calculateAdultBMIRisk(double bmi) {
        // Adult BMI risk assessment (for ages > 5 years) - Updated to match verified system
        // BMI < 16.5: Severe underweight (high risk) - 40 points
        // BMI 16.5-<18.5: Moderate underweight - 25 points  
        // BMI ≥18.5: Normal weight - 0 points
        
        if (bmi < 16.5) {
            return 40; // Severe underweight - highest risk
        } else if (bmi < 18.5) {
            return 25; // Moderate underweight
        } else {
            return 0; // Normal weight - no risk
        }
    }

    private String getSelectedGender() {
        if (genderBoySelected) return "boy";
        else return "girl";
    }

    private String getSelectedSwelling() {
        if (swellingYesSelected) return "yes";
        else return "no";
    }

    private String getSelectedWeightLoss() {
        if (weightLoss10PlusSelected) return ">10%";
        else if (weightLoss5To10Selected) return "5-10%";
        else if (weightLossLess5Selected) return "<5% or none";
        else return "";
    }

    private String getSelectedFeeding() {
        if (feedingGoodSelected) return "good appetite";
        else if (feedingModerateSelected) return "moderate appetite";
        else if (feedingPoorSelected) return "poor appetite";
        else return "";
    }

    // Physical signs are now collected in edit profile dialog
    private org.json.JSONArray getSelectedPhysicalSigns() {
        org.json.JSONArray physicalSigns = new org.json.JSONArray();
        if (isThin != null && isThin) physicalSigns.put("thin");
        if (isShorter != null && isShorter) physicalSigns.put("shorter");
        if (isWeak != null && isWeak) physicalSigns.put("weak");
        if (isNone != null && isNone) physicalSigns.put("none");
        return physicalSigns;
    }

    private int getBarangayPosition(String barangay) {
        for (int i = 0; i < BATAAN_BARANGAYS.length; i++) {
            if (BATAAN_BARANGAYS[i].equals(barangay)) {
                return i;
            }
        }
        return 0; // Default to first item if not found
    }

    private int getIncomePosition(String income) {
        for (int i = 0; i < INCOME_BRACKETS.length; i++) {
            if (INCOME_BRACKETS[i].equals(income)) {
                return i;
            }
        }
        return 0; // Default to first item if not found
    }

    // Button state management methods - same style as ScreeningFormActivity
    private void updateButtonStates(Button selected, Button... unselected) {
        // Set the selected button state with dark grey background, larger size, and rounded corners
        selected.setSelected(true);
        selected.setElevation(8f); // Higher elevation for selected state
        selected.setBackgroundColor(0xFF424242); // Dark grey for selected state
        selected.setVisibility(View.VISIBLE); // Ensure selected button is visible
        setButtonSizeAndShape(selected, true);
        
        // Clear the selected state for all unselected buttons
        for (Button btn : unselected) {
            btn.setSelected(false);
            btn.setElevation(6f); // Normal elevation for unselected state
            btn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected state
            btn.setVisibility(View.VISIBLE); // Ensure unselected buttons remain visible
            setButtonSizeAndShape(btn, false);
        }
        
        // Force a redraw to show the visual changes
        selected.invalidate();
        for (Button btn : unselected) {
            btn.invalidate();
        }
        
        // Add haptic feedback for better user experience
        selected.performHapticFeedback(android.view.HapticFeedbackConstants.VIRTUAL_KEY);
    }
    
    // Helper method to set button text color
    private void setButtonTextColor(Button button, int color) {
        button.setTextColor(color);
    }
    
    // Helper method to set button size and shape
    private void setButtonSizeAndShape(Button button, boolean isSelected) {
        // Get the button's current layout parameters
        android.view.ViewGroup.LayoutParams params = button.getLayoutParams();
        if (params != null) {
            // Only reduce width by 25% for unselected buttons, keep height normal
            if (!isSelected) {
                // Store original width if not already stored
                if (button.getTag() == null) {
                    button.setTag(params.width);
                }
                // Reduce only width by 25%
                int originalWidth = (Integer) button.getTag();
                params.width = (int) (originalWidth * 0.75);
            } else {
                // Restore original width for selected buttons
                if (button.getTag() != null) {
                    params.width = (Integer) button.getTag();
                } else {
                    params.width = android.view.ViewGroup.LayoutParams.WRAP_CONTENT;
                }
            }
            // Keep height as WRAP_CONTENT for both states
            params.height = android.view.ViewGroup.LayoutParams.WRAP_CONTENT;
            button.setLayoutParams(params);
        }
        
        // Set slightly rounded corners (less rounded than before)
        android.graphics.drawable.GradientDrawable shape = new android.graphics.drawable.GradientDrawable();
        shape.setShape(android.graphics.drawable.GradientDrawable.RECTANGLE);
        shape.setCornerRadius(8f); // Reduced from 20f to 8f for subtle rounding
        
        if (isSelected) {
            shape.setColor(0xFF424242); // Dark grey for selected
        } else {
            shape.setColor(0xFFBDBDBD); // Light grey for unselected
        }
        
        button.setBackground(shape);
    }
    
    // Initialize button states like ScreeningFormActivity
    private void initializeButtonStates() {
        // Initialize all buttons to unselected state with light grey background, smaller width, and subtle rounded corners
        genderBoyBtn.setSelected(false);
        genderGirlBtn.setSelected(false);
        genderBoyBtn.setElevation(6f);
        genderGirlBtn.setElevation(6f);
        genderBoyBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        genderGirlBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        genderBoyBtn.setVisibility(View.VISIBLE); // Ensure visibility
        genderGirlBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(genderBoyBtn, false);
        setButtonSizeAndShape(genderGirlBtn, false);
        
        swellingYesBtn.setSelected(false);
        swellingNoBtn.setSelected(false);
        swellingYesBtn.setElevation(6f);
        swellingNoBtn.setElevation(6f);
        swellingYesBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        swellingNoBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        swellingYesBtn.setVisibility(View.VISIBLE); // Ensure visibility
        swellingNoBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(swellingYesBtn, false);
        setButtonSizeAndShape(swellingNoBtn, false);
        
        weightLoss10PlusBtn.setSelected(false);
        weightLoss5To10Btn.setSelected(false);
        weightLossLess5Btn.setSelected(false);
        weightLoss10PlusBtn.setElevation(6f);
        weightLoss5To10Btn.setElevation(6f);
        weightLossLess5Btn.setElevation(6f);
        weightLoss10PlusBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        weightLoss5To10Btn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        weightLossLess5Btn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        weightLoss10PlusBtn.setVisibility(View.VISIBLE); // Ensure visibility
        weightLoss5To10Btn.setVisibility(View.VISIBLE); // Ensure visibility
        weightLossLess5Btn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(weightLoss10PlusBtn, false);
        setButtonSizeAndShape(weightLoss5To10Btn, false);
        setButtonSizeAndShape(weightLossLess5Btn, false);
        
        feedingGoodBtn.setSelected(false);
        feedingModerateBtn.setSelected(false);
        feedingPoorBtn.setSelected(false);
        feedingGoodBtn.setElevation(6f);
        feedingModerateBtn.setElevation(6f);
        feedingPoorBtn.setElevation(6f);
        feedingGoodBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        feedingModerateBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        feedingPoorBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        feedingGoodBtn.setVisibility(View.VISIBLE); // Ensure visibility
        feedingModerateBtn.setVisibility(View.VISIBLE); // Ensure visibility
        feedingPoorBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(feedingGoodBtn, false);
        setButtonSizeAndShape(feedingModerateBtn, false);
        setButtonSizeAndShape(feedingPoorBtn, false);
        
        // Physical signs
        physicalThinBtn.setSelected(false);
        physicalShorterBtn.setSelected(false);
        physicalWeakBtn.setSelected(false);
        physicalNoneBtn.setSelected(false);
        physicalThinBtn.setElevation(6f);
        physicalShorterBtn.setElevation(6f);
        physicalWeakBtn.setElevation(6f);
        physicalNoneBtn.setElevation(6f);
        physicalThinBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        physicalShorterBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        physicalWeakBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        physicalNoneBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        physicalThinBtn.setVisibility(View.VISIBLE); // Ensure visibility
        physicalShorterBtn.setVisibility(View.VISIBLE); // Ensure visibility
        physicalWeakBtn.setVisibility(View.VISIBLE); // Ensure visibility
        physicalNoneBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(physicalThinBtn, false);
        setButtonSizeAndShape(physicalShorterBtn, false);
        setButtonSizeAndShape(physicalWeakBtn, false);
        setButtonSizeAndShape(physicalNoneBtn, false);
        
        // Clinical risk factors
        illnessYesBtn.setSelected(false);
        illnessNoBtn.setSelected(false);
        illnessYesBtn.setElevation(6f);
        illnessNoBtn.setElevation(6f);
        illnessYesBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        illnessNoBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        illnessYesBtn.setVisibility(View.VISIBLE); // Ensure visibility
        illnessNoBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(illnessYesBtn, false);
        setButtonSizeAndShape(illnessNoBtn, false);
        
        eatingDifficultyYesBtn.setSelected(false);
        eatingDifficultyNoBtn.setSelected(false);
        eatingDifficultyYesBtn.setElevation(6f);
        eatingDifficultyNoBtn.setElevation(6f);
        eatingDifficultyYesBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        eatingDifficultyNoBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        eatingDifficultyYesBtn.setVisibility(View.VISIBLE); // Ensure visibility
        eatingDifficultyNoBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(eatingDifficultyYesBtn, false);
        setButtonSizeAndShape(eatingDifficultyNoBtn, false);
        
        foodInsecurityYesBtn.setSelected(false);
        foodInsecurityNoBtn.setSelected(false);
        foodInsecurityYesBtn.setElevation(6f);
        foodInsecurityNoBtn.setElevation(6f);
        foodInsecurityYesBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        foodInsecurityNoBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        foodInsecurityYesBtn.setVisibility(View.VISIBLE); // Ensure visibility
        foodInsecurityNoBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(foodInsecurityYesBtn, false);
        setButtonSizeAndShape(foodInsecurityNoBtn, false);
        
        micronutrientYesBtn.setSelected(false);
        micronutrientNoBtn.setSelected(false);
        micronutrientYesBtn.setElevation(6f);
        micronutrientNoBtn.setElevation(6f);
        micronutrientYesBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        micronutrientNoBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        micronutrientYesBtn.setVisibility(View.VISIBLE); // Ensure visibility
        micronutrientNoBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(micronutrientYesBtn, false);
        setButtonSizeAndShape(micronutrientNoBtn, false);
        
        functionalDeclineYesBtn.setSelected(false);
        functionalDeclineNoBtn.setSelected(false);
        functionalDeclineYesBtn.setElevation(6f);
        functionalDeclineNoBtn.setElevation(6f);
        functionalDeclineYesBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        functionalDeclineNoBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
        functionalDeclineYesBtn.setVisibility(View.VISIBLE); // Ensure visibility
        functionalDeclineNoBtn.setVisibility(View.VISIBLE); // Ensure visibility
        setButtonSizeAndShape(functionalDeclineYesBtn, false);
        setButtonSizeAndShape(functionalDeclineNoBtn, false);
        
        // Force redraw of all buttons
        genderBoyBtn.invalidate();
        genderGirlBtn.invalidate();
        swellingYesBtn.invalidate();
        swellingNoBtn.invalidate();
        weightLoss10PlusBtn.invalidate();
        weightLoss5To10Btn.invalidate();
        weightLossLess5Btn.invalidate();
        feedingGoodBtn.invalidate();
        feedingModerateBtn.invalidate();
        feedingPoorBtn.invalidate();
        physicalThinBtn.invalidate();
        physicalShorterBtn.invalidate();
        physicalWeakBtn.invalidate();
        physicalNoneBtn.invalidate();
        illnessYesBtn.invalidate();
        illnessNoBtn.invalidate();
        eatingDifficultyYesBtn.invalidate();
        eatingDifficultyNoBtn.invalidate();
        foodInsecurityYesBtn.invalidate();
        foodInsecurityNoBtn.invalidate();
        micronutrientYesBtn.invalidate();
        micronutrientNoBtn.invalidate();
        functionalDeclineYesBtn.invalidate();
        functionalDeclineNoBtn.invalidate();
    }
    
    // Update physical signs button states like ScreeningFormActivity
    private void updatePhysicalSignsButtonStates() {
        // Update physical signs buttons based on current state
        if (isThin != null && isThin) {
            physicalThinBtn.setSelected(true);
            physicalThinBtn.setElevation(8f);
            physicalThinBtn.setBackgroundColor(0xFF424242); // Dark grey for selected
            physicalThinBtn.setVisibility(View.VISIBLE); // Ensure visibility
            setButtonSizeAndShape(physicalThinBtn, true);
        } else {
            physicalThinBtn.setSelected(false);
            physicalThinBtn.setElevation(6f);
            physicalThinBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
            physicalThinBtn.setVisibility(View.VISIBLE); // Ensure visibility
            setButtonSizeAndShape(physicalThinBtn, false);
        }
        
        if (isShorter != null && isShorter) {
            physicalShorterBtn.setSelected(true);
            physicalShorterBtn.setElevation(8f);
            physicalShorterBtn.setBackgroundColor(0xFF424242); // Dark grey for selected
            physicalShorterBtn.setVisibility(View.VISIBLE); // Ensure visibility
            setButtonSizeAndShape(physicalShorterBtn, true);
        } else {
            physicalShorterBtn.setSelected(false);
            physicalShorterBtn.setElevation(6f);
            physicalShorterBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
            physicalShorterBtn.setVisibility(View.VISIBLE); // Ensure visibility
            setButtonSizeAndShape(physicalShorterBtn, false);
        }
        
        if (isWeak != null && isWeak) {
            physicalWeakBtn.setSelected(true);
            physicalWeakBtn.setElevation(8f);
            physicalWeakBtn.setBackgroundColor(0xFF424242); // Dark grey for selected
            physicalWeakBtn.setVisibility(View.VISIBLE); // Ensure visibility
            setButtonSizeAndShape(physicalWeakBtn, true);
        } else {
            physicalWeakBtn.setSelected(false);
            physicalWeakBtn.setElevation(6f);
            physicalWeakBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
            physicalWeakBtn.setVisibility(View.VISIBLE); // Ensure visibility
            setButtonSizeAndShape(physicalWeakBtn, false);
        }
        
        if (isNone != null && isNone) {
            physicalNoneBtn.setSelected(true);
            physicalNoneBtn.setElevation(8f);
            physicalNoneBtn.setBackgroundColor(0xFF424242); // Dark grey for selected
            physicalNoneBtn.setVisibility(View.VISIBLE); // Ensure visibility
            setButtonSizeAndShape(physicalNoneBtn, true);
        } else {
            physicalNoneBtn.setSelected(false);
            physicalNoneBtn.setElevation(6f);
            physicalNoneBtn.setBackgroundColor(0xFFBDBDBD); // Light grey for unselected
            physicalNoneBtn.setVisibility(View.VISIBLE); // Ensure visibility
            setButtonSizeAndShape(physicalNoneBtn, false);
        }
        
        // Force redraw
        physicalThinBtn.invalidate();
        physicalShorterBtn.invalidate();
        physicalWeakBtn.invalidate();
        physicalNoneBtn.invalidate();
    }

    private void updateButtonStatesForAction(String action, View selectedButton) {
        switch (action) {
            case "gender":
                if (selectedButton.getId() == R.id.gender_boy) {
                    updateButtonStates((Button) selectedButton, genderGirlBtn);
                } else {
                    updateButtonStates((Button) selectedButton, genderBoyBtn);
                }
                break;
            case "swelling":
                if (selectedButton.getId() == R.id.swelling_yes) {
                    updateButtonStates((Button) selectedButton, swellingNoBtn);
                } else {
                    updateButtonStates((Button) selectedButton, swellingYesBtn);
                }
                break;
            case "weightLoss":
                if (selectedButton.getId() == R.id.weightloss_10plus) {
                    updateButtonStates((Button) selectedButton, weightLoss5To10Btn, weightLossLess5Btn);
                } else if (selectedButton.getId() == R.id.weightloss_5to10) {
                    updateButtonStates((Button) selectedButton, weightLoss10PlusBtn, weightLossLess5Btn);
                } else {
                    updateButtonStates((Button) selectedButton, weightLoss10PlusBtn, weightLoss5To10Btn);
                }
                break;
            case "feeding":
                if (selectedButton.getId() == R.id.feeding_good) {
                    updateButtonStates((Button) selectedButton, feedingModerateBtn, feedingPoorBtn);
                } else if (selectedButton.getId() == R.id.feeding_moderate) {
                    updateButtonStates((Button) selectedButton, feedingGoodBtn, feedingPoorBtn);
                } else {
                    updateButtonStates((Button) selectedButton, feedingGoodBtn, feedingModerateBtn);
                }
                break;
            case "physical":
                // For physical signs, update all button states based on current values
                updatePhysicalSignsButtonStates();
                break;
            case "illness":
                if (selectedButton.getId() == R.id.illness_yes) {
                    updateButtonStates((Button) selectedButton, illnessNoBtn);
                } else {
                    updateButtonStates((Button) selectedButton, illnessYesBtn);
                }
                break;
            case "eatingDifficulty":
                if (selectedButton.getId() == R.id.eating_difficulty_yes) {
                    updateButtonStates((Button) selectedButton, eatingDifficultyNoBtn);
                } else {
                    updateButtonStates((Button) selectedButton, eatingDifficultyYesBtn);
                }
                break;
            case "foodInsecurity":
                if (selectedButton.getId() == R.id.food_insecurity_yes) {
                    updateButtonStates((Button) selectedButton, foodInsecurityNoBtn);
                } else {
                    updateButtonStates((Button) selectedButton, foodInsecurityYesBtn);
                }
                break;
            case "micronutrient":
                if (selectedButton.getId() == R.id.micronutrient_yes) {
                    updateButtonStates((Button) selectedButton, micronutrientNoBtn);
                } else {
                    updateButtonStates((Button) selectedButton, micronutrientYesBtn);
                }
                break;
            case "functionalDecline":
                if (selectedButton.getId() == R.id.functional_decline_yes) {
                    updateButtonStates((Button) selectedButton, functionalDeclineNoBtn);
                } else {
                    updateButtonStates((Button) selectedButton, functionalDeclineYesBtn);
                }
                break;
        }
    }
} 