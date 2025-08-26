package com.example.nutrisaur11;

import android.app.Dialog;
import android.content.Context;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.Window;
import android.widget.Toast;

import androidx.annotation.NonNull;

import com.google.android.material.chip.Chip;
import com.google.android.material.chip.ChipGroup;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;

public class FilterDialog extends Dialog {
    
    private static final String[] ALLERGENS = {
        "Peanuts", "Dairy", "Eggs", "Shellfish", "Gluten", "Soy", "Fish", "Tree nuts", 
        "Wheat", "Nuts", "Seafood", "Meat", "Pork", "Chicken", "Beef", "Coconut", "Sesame"
    };
    
    private static final String[] DIET_PREFS = {
        "Vegetarian", "Vegan", "Halal", "Kosher", "Pescatarian", "Low-carb", "High-protein", 
        "Low-sodium", "Gluten-free", "Dairy-free", "Nut-free", "Low-calorie", "Energy-dense",
        "Weight-gain", "Digestive-friendly", "Immune-boosting", "Iron-rich", "Vitamin-rich",
        "Fiber-rich", "Omega-3", "Bone-health", "Brain-development", "Gut-health"
    };
    
    private static final String[] AGE_GROUPS = {
        "Newborn-friendly", "Infant-friendly", "Child-friendly", "School-age", "Adolescent", "Adult-friendly"
    };
    
    private static final String[] COOKING_METHODS = {
        "Boiled", "Steamed", "Fried", "Grilled", "Roasted", "Stir-fried", "Deep-fried", "Stewed", "Soup"
    };
    
    private static final String[] FOOD_CATEGORIES = {
        "Main dishes", "Noodles", "Rice dishes", "Street food", "Appetizers", "Desserts", 
        "Beverages", "Vegetables", "Snacks", "Fruits", "Staples", "Regional specialties"
    };
    
    private Context context;
    private OnFilterAppliedListener listener;
    private ChipGroup allergiesChipGroup;
    private ChipGroup dietPrefsChipGroup;
    private ChipGroup ageGroupsChipGroup;
    private ChipGroup cookingMethodsChipGroup;
    private ChipGroup foodCategoriesChipGroup;
    
    public interface OnFilterAppliedListener {
        void onFilterApplied(List<String> allergies, List<String> dietPrefs, String avoidFoods);
    }
    
    public FilterDialog(@NonNull Context context, OnFilterAppliedListener listener) {
        super(context);
        this.context = context;
        this.listener = listener;
    }
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        requestWindowFeature(Window.FEATURE_NO_TITLE);
        setContentView(R.layout.dialog_filter_preferences);
        
        // Handle back button press to apply filters
        setOnKeyListener((dialog, keyCode, event) -> {
            if (keyCode == android.view.KeyEvent.KEYCODE_BACK) {
                applyFiltersAndClose();
                return true;
            }
            return false;
        });
        
        // Handle dialog cancellation
        setOnCancelListener(dialog -> {
            android.util.Log.d("FilterDialog", "Dialog cancelled - applying filters anyway");
            applyFiltersAndClose();
        });
        
        // Make dialog background transparent so the layout's white background shows
        if (getWindow() != null) {
            getWindow().setBackgroundDrawableResource(android.R.color.transparent);
        }
        
        // Initialize views
        allergiesChipGroup = findViewById(R.id.allergies_chip_group);
        dietPrefsChipGroup = findViewById(R.id.diet_prefs_chip_group);
        ageGroupsChipGroup = findViewById(R.id.age_groups_chip_group);
        cookingMethodsChipGroup = findViewById(R.id.cooking_methods_chip_group);
        foodCategoriesChipGroup = findViewById(R.id.food_categories_chip_group);
        
        // Setup close button - now applies filters when closed
        android.widget.ImageView closeButton = findViewById(R.id.btn_close);
        closeButton.setOnClickListener(v -> applyFiltersAndClose());
        
        // Setup chips first
        setupAllergiesChips();
        setupDietPrefsChips();
        setupAgeGroupsChips();
        setupCookingMethodsChips();
        setupFoodCategoriesChips();
        
        // Load current preferences after chips are created with a small delay
        new android.os.Handler().postDelayed(() -> {
            loadCurrentPreferences();
        }, 100);
    }
    
    @Override
    public void onStart() {
        super.onStart();
        // Force refresh chip states when dialog is shown
        loadCurrentPreferences();
    }
    
    private void setupAllergiesChips() {
        for (String allergen : ALLERGENS) {
            Chip chip = new Chip(context);
            chip.setText(allergen);
            chip.setCheckable(true);
            chip.setCheckedIconVisible(false);
            chip.setChipBackgroundColorResource(R.color.chip_background_selector);
            chip.setTextColor(context.getResources().getColorStateList(R.color.chip_text_selector, null));
            chip.setChipCornerRadius(20f);
            chip.setChipStrokeWidth(1f);
            chip.setChipStrokeColorResource(R.color.chip_background_selector);
            
            // No immediate filter application - filters will be applied when dialog closes
            chip.setOnCheckedChangeListener((buttonView, isChecked) -> {
                // Just update the visual state, no processing yet
            });
            
            allergiesChipGroup.addView(chip);
        }
    }
    
    private void setupDietPrefsChips() {
        for (String dietPref : DIET_PREFS) {
            Chip chip = new Chip(context);
            chip.setText(dietPref);
            chip.setCheckable(true);
            chip.setCheckedIconVisible(false);
            chip.setChipBackgroundColorResource(R.color.chip_background_selector);
            chip.setTextColor(context.getResources().getColorStateList(R.color.chip_text_selector, null));
            chip.setChipCornerRadius(20f);
            chip.setChipStrokeWidth(1f);
            chip.setChipStrokeColorResource(R.color.chip_background_selector);
            
            // No immediate filter application - filters will be applied when dialog closes
            chip.setOnCheckedChangeListener((buttonView, isChecked) -> {
                // Just update the visual state, no processing yet
            });
            
            dietPrefsChipGroup.addView(chip);
        }
    }

    private void setupAgeGroupsChips() {
        for (String ageGroup : AGE_GROUPS) {
            Chip chip = new Chip(context);
            chip.setText(ageGroup);
            chip.setCheckable(true);
            chip.setCheckedIconVisible(false);
            chip.setChipBackgroundColorResource(R.color.chip_background_selector);
            chip.setTextColor(context.getResources().getColorStateList(R.color.chip_text_selector, null));
            chip.setChipCornerRadius(20f);
            chip.setChipStrokeWidth(1f);
            chip.setChipStrokeColorResource(R.color.chip_background_selector);
            
            // Add click listener for immediate filter application
            chip.setOnCheckedChangeListener((buttonView, isChecked) -> {
                applyFilter();
            });
            
            ageGroupsChipGroup.addView(chip);
        }
    }

    private void setupCookingMethodsChips() {
        for (String cookingMethod : COOKING_METHODS) {
            Chip chip = new Chip(context);
            chip.setText(cookingMethod);
            chip.setCheckable(true);
            chip.setCheckedIconVisible(false);
            chip.setChipBackgroundColorResource(R.color.chip_background_selector);
            chip.setTextColor(context.getResources().getColorStateList(R.color.chip_text_selector, null));
            chip.setChipCornerRadius(20f);
            chip.setChipStrokeWidth(1f);
            chip.setChipStrokeColorResource(R.color.chip_background_selector);
            
            // No immediate filter application - filters will be applied when dialog closes
            chip.setOnCheckedChangeListener((buttonView, isChecked) -> {
                // Just update the visual state, no processing yet
            });
            
            cookingMethodsChipGroup.addView(chip);
        }
    }

    private void setupFoodCategoriesChips() {
        for (String foodCategory : FOOD_CATEGORIES) {
            Chip chip = new Chip(context);
            chip.setText(foodCategory);
            chip.setCheckable(true);
            chip.setCheckedIconVisible(false);
            chip.setChipBackgroundColorResource(R.color.chip_background_selector);
            chip.setTextColor(context.getResources().getColorStateList(R.color.chip_text_selector, null));
            chip.setChipCornerRadius(20f);
            chip.setChipStrokeWidth(1f);
            chip.setChipStrokeColorResource(R.color.chip_background_selector);
            
            // No immediate filter application - filters will be applied when dialog closes
            chip.setOnCheckedChangeListener((buttonView, isChecked) -> {
                // Just update the visual state, no processing yet
            });
            
            foodCategoriesChipGroup.addView(chip);
        }
    }
    
    private void loadCurrentPreferences() {
        String email = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE)
                .getString("current_user_email", null);
        android.util.Log.d("FilterDialog", "Loading preferences for email: " + email);
        if (email != null) {
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(context);
            try {
                // Get the most recent record for this user
                android.database.Cursor cursor = dbHelper.getReadableDatabase().rawQuery(
                    "SELECT allergies, diet_prefs FROM " + UserPreferencesDbHelper.TABLE_NAME +
                    " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=? ORDER BY id DESC LIMIT 1",
                    new String[]{email}
                );
                android.util.Log.d("FilterDialog", "Cursor count: " + cursor.getCount());
                if (cursor.moveToFirst()) {
                    String allergies = cursor.getString(0);
                    String dietPrefs = cursor.getString(1);
                    android.util.Log.d("FilterDialog", "Loaded allergies: " + allergies);
                    android.util.Log.d("FilterDialog", "Loaded diet prefs: " + dietPrefs);
                    
                    // Ensure chip groups are initialized
                    if (allergiesChipGroup == null || dietPrefsChipGroup == null) {
                        android.util.Log.w("FilterDialog", "Chip groups not initialized yet, skipping preference loading");
                        return;
                    }
                    
                    // Allergies
                    if (allergies != null && !allergies.trim().isEmpty()) {
                        List<String> selectedAllergies = Arrays.asList(allergies.split(","));
                        android.util.Log.d("FilterDialog", "Processing " + selectedAllergies.size() + " allergies");
                        for (int i = 0; i < allergiesChipGroup.getChildCount(); i++) {
                            Chip chip = (Chip) allergiesChipGroup.getChildAt(i);
                            String chipText = chip.getText().toString().trim();
                            boolean shouldBeChecked = false;
                            for (String pref : selectedAllergies) {
                                if (chipText.equalsIgnoreCase(pref.trim())) {
                                    shouldBeChecked = true;
                                    break;
                                }
                            }
                            chip.setChecked(shouldBeChecked);
                            android.util.Log.d("FilterDialog", "Set allergy chip '" + chipText + "' checked: " + shouldBeChecked);
                        }
                    } else {
                        android.util.Log.d("FilterDialog", "No allergies to load, resetting all allergy chips");
                        for (int i = 0; i < allergiesChipGroup.getChildCount(); i++) {
                            Chip chip = (Chip) allergiesChipGroup.getChildAt(i);
                            chip.setChecked(false);
                        }
                    }
                    
                    // Diet Prefs
                    if (dietPrefs != null && !dietPrefs.trim().isEmpty()) {
                        List<String> selectedDietPrefs = Arrays.asList(dietPrefs.split(","));
                        android.util.Log.d("FilterDialog", "Processing " + selectedDietPrefs.size() + " diet preferences");
                        for (int i = 0; i < dietPrefsChipGroup.getChildCount(); i++) {
                            Chip chip = (Chip) dietPrefsChipGroup.getChildAt(i);
                            String chipText = chip.getText().toString().trim();
                            boolean shouldBeChecked = false;
                            for (String pref : selectedDietPrefs) {
                                if (chipText.equalsIgnoreCase(pref.trim())) {
                                    shouldBeChecked = true;
                                    break;
                                }
                            }
                            chip.setChecked(shouldBeChecked);
                            android.util.Log.d("FilterDialog", "Set diet chip '" + chipText + "' checked: " + shouldBeChecked);
                        }
                        
                        // Also load age groups, cooking methods, and food categories from the combined diet prefs
                        loadFilterCategoriesFromCombinedPrefs(selectedDietPrefs);
                    } else {
                        android.util.Log.d("FilterDialog", "No diet preferences to load, resetting all diet chips");
                        for (int i = 0; i < dietPrefsChipGroup.getChildCount(); i++) {
                            Chip chip = (Chip) dietPrefsChipGroup.getChildAt(i);
                            chip.setChecked(false);
                        }
                        // Reset all new filter categories
                        resetFilterCategories();
                    }
                } else {
                    android.util.Log.d("FilterDialog", "No user preferences found, resetting all chips");
                    for (int i = 0; i < allergiesChipGroup.getChildCount(); i++) {
                        Chip chip = (Chip) allergiesChipGroup.getChildAt(i);
                        chip.setChecked(false);
                    }
                    for (int i = 0; i < dietPrefsChipGroup.getChildCount(); i++) {
                        Chip chip = (Chip) dietPrefsChipGroup.getChildAt(i);
                        chip.setChecked(false);
                    }
                    // Reset all new filter categories
                    resetFilterCategories();
                }
                cursor.close();
            } finally {
                dbHelper.close();
            }
        } else {
            android.util.Log.e("FilterDialog", "No user email found in SharedPreferences");
        }
    }
    
    private void applyFilter() {
        // Get selected allergies
        List<String> selectedAllergies = new ArrayList<>();
        for (int i = 0; i < allergiesChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) allergiesChipGroup.getChildAt(i);
            if (chip.isChecked()) {
                selectedAllergies.add(chip.getText().toString());
            }
        }
        
        // Get selected diet preferences
        List<String> selectedDietPrefs = new ArrayList<>();
        for (int i = 0; i < dietPrefsChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) dietPrefsChipGroup.getChildAt(i);
            if (chip.isChecked()) {
                selectedDietPrefs.add(chip.getText().toString());
            }
        }
        
        // Get selected age groups
        List<String> selectedAgeGroups = new ArrayList<>();
        for (int i = 0; i < ageGroupsChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) ageGroupsChipGroup.getChildAt(i);
            if (chip.isChecked()) {
                selectedAgeGroups.add(chip.getText().toString());
            }
        }
        
        // Get selected cooking methods
        List<String> selectedCookingMethods = new ArrayList<>();
        for (int i = 0; i < cookingMethodsChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) cookingMethodsChipGroup.getChildAt(i);
            if (chip.isChecked()) {
                selectedCookingMethods.add(chip.getText().toString());
            }
        }
        
        // Get selected food categories
        List<String> selectedFoodCategories = new ArrayList<>();
        for (int i = 0; i < foodCategoriesChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) foodCategoriesChipGroup.getChildAt(i);
            if (chip.isChecked()) {
                selectedFoodCategories.add(chip.getText().toString());
            }
        }
        
        // Debug logging
        android.util.Log.d("FilterDialog", "Selected allergies: " + selectedAllergies);
        android.util.Log.d("FilterDialog", "Selected diet prefs: " + selectedDietPrefs);
        android.util.Log.d("FilterDialog", "Selected age groups: " + selectedAgeGroups);
        android.util.Log.d("FilterDialog", "Selected cooking methods: " + selectedCookingMethods);
        android.util.Log.d("FilterDialog", "Selected food categories: " + selectedFoodCategories);
        
        // Save preferences (including new filter categories)
        saveUserPreferences(selectedAllergies, selectedDietPrefs, selectedAgeGroups, selectedCookingMethods, selectedFoodCategories, "");
        
        // Notify listener (using old interface for compatibility)
        if (listener != null) {
            listener.onFilterApplied(selectedAllergies, selectedDietPrefs, "");
        }
        
        // Show toast on main thread
        ((android.app.Activity) context).runOnUiThread(() -> {
                            // Toast message removed for performance
        });
    }
    
    private void saveUserPreferences(List<String> allergies, List<String> dietPrefs, List<String> ageGroups, 
                           List<String> cookingMethods, List<String> foodCategories, String avoidFoods) {
        String email = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE)
                .getString("current_user_email", null);
        
        android.util.Log.d("FilterDialog", "Saving preferences for email: " + email);
        android.util.Log.d("FilterDialog", "Allergies to save: " + allergies);
        android.util.Log.d("FilterDialog", "Diet prefs to save: " + dietPrefs);
        android.util.Log.d("FilterDialog", "Age groups to save: " + ageGroups);
        android.util.Log.d("FilterDialog", "Cooking methods to save: " + cookingMethods);
        android.util.Log.d("FilterDialog", "Food categories to save: " + foodCategories);
        
        if (email != null) {
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(context);
            try {
                // Get current risk score to preserve it
                int currentRiskScore = ScreeningResultStore.getRiskScore();
                android.util.Log.d("FilterDialog", "Current risk score: " + currentRiskScore);
                
                // Join the lists, but ensure we don't save empty strings
                String allergiesStr = join(allergies);
                String dietPrefsStr = join(dietPrefs);
                String ageGroupsStr = join(ageGroups);
                String cookingMethodsStr = join(cookingMethods);
                String foodCategoriesStr = join(foodCategories);
                
                android.util.Log.d("FilterDialog", "Allergies string to save: '" + allergiesStr + "'");
                android.util.Log.d("FilterDialog", "Diet prefs string to save: '" + dietPrefsStr + "'");
                android.util.Log.d("FilterDialog", "Age groups string to save: '" + ageGroupsStr + "'");
                android.util.Log.d("FilterDialog", "Cooking methods string to save: '" + cookingMethodsStr + "'");
                android.util.Log.d("FilterDialog", "Food categories string to save: '" + foodCategoriesStr + "'");
                
                android.content.ContentValues values = new android.content.ContentValues();
                values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
                values.put(UserPreferencesDbHelper.COL_ALLERGIES, allergiesStr);
                values.put(UserPreferencesDbHelper.COL_DIET_PREFS, dietPrefsStr);
                values.put(UserPreferencesDbHelper.COL_AVOID_FOODS, avoidFoods);
                values.put(UserPreferencesDbHelper.COL_RISK_SCORE, currentRiskScore);
                
                // Note: For now, we'll store the new filter categories in existing columns
                // In a future update, you may want to add new columns to the database
                // For now, we'll combine them with diet preferences
                String combinedDietPrefs = dietPrefsStr;
                if (!ageGroupsStr.isEmpty()) {
                    combinedDietPrefs += (combinedDietPrefs.isEmpty() ? "" : ",") + ageGroupsStr;
                }
                if (!cookingMethodsStr.isEmpty()) {
                    combinedDietPrefs += (combinedDietPrefs.isEmpty() ? "" : ",") + cookingMethodsStr;
                }
                if (!foodCategoriesStr.isEmpty()) {
                    combinedDietPrefs += (combinedDietPrefs.isEmpty() ? "" : ",") + foodCategoriesStr;
                }
                
                values.put(UserPreferencesDbHelper.COL_DIET_PREFS, combinedDietPrefs);
                
                android.util.Log.d("FilterDialog", "ContentValues: " + values.toString());
                
                // Safe upsert: UPDATE the existing row to avoid wiping other columns (like screening_answers)
                android.database.sqlite.SQLiteDatabase db = dbHelper.getWritableDatabase();
                int updated = db.update(
                    UserPreferencesDbHelper.TABLE_NAME,
                    values,
                    UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
                    new String[]{email}
                );
                if (updated == 0) {
                    // No row yet: INSERT a new one with only these columns
                    long inserted = db.insert(UserPreferencesDbHelper.TABLE_NAME, null, values);
                    android.util.Log.d("FilterDialog", "Inserted new prefs row id: " + inserted);
                } else {
                    android.util.Log.d("FilterDialog", "Updated existing prefs row for: " + email);
                }
            } finally {
                dbHelper.close();
            }
        } else {
            android.util.Log.e("FilterDialog", "No user email found in SharedPreferences");
        }
    }
    
    private String join(List<String> list) {
        List<String> trimmed = new ArrayList<>();
        for (String s : list) {
            if (!s.trim().isEmpty()) trimmed.add(s.trim());
        }
        return android.text.TextUtils.join(",", trimmed);
    }

    private void loadFilterCategoriesFromCombinedPrefs(List<String> selectedDietPrefs) {
        // This method will be implemented in a future update to load age groups, cooking methods, and food categories
        // from the combined diet preferences string.
        // For now, it will just reset all new filter categories.
        resetFilterCategories();
    }

    private void resetFilterCategories() {
        for (int i = 0; i < ageGroupsChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) ageGroupsChipGroup.getChildAt(i);
            chip.setChecked(false);
        }
        for (int i = 0; i < cookingMethodsChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) cookingMethodsChipGroup.getChildAt(i);
            chip.setChecked(false);
        }
        for (int i = 0; i < foodCategoriesChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) foodCategoriesChipGroup.getChildAt(i);
            chip.setChecked(false);
        }
    }
    
    /**
     * Apply filters and close dialog with loading indicator
     */
    private void applyFiltersAndClose() {
        // Show loading indicator
        android.view.View loadingSection = findViewById(R.id.loading_section);
        android.widget.ImageView closeButton = findViewById(R.id.btn_close);
        
        if (loadingSection != null) {
            loadingSection.setVisibility(android.view.View.VISIBLE);
        }
        if (closeButton != null) {
            closeButton.setEnabled(false);
        }
        
        // Add a timeout to ensure dialog can always be closed
        new android.os.Handler().postDelayed(() -> {
            if (loadingSection != null && loadingSection.getVisibility() == android.view.View.VISIBLE) {
                ((android.app.Activity) context).runOnUiThread(() -> {
                    if (loadingSection != null) {
                        loadingSection.setVisibility(android.view.View.GONE);
                    }
                    if (closeButton != null) {
                        closeButton.setEnabled(true);
                    }
                    android.util.Log.w("FilterDialog", "Loading timeout - re-enabling close button");
                });
            }
        }, 10000); // 10 second timeout
        
        // Apply filters in background
        new Thread(() -> {
            try {
                // Apply filters to database
                applyFilter();
                
                // Get selected filters for recommendation update
                List<String> selectedFilters = getSelectedFilters();
                
                // Update recommendations on main thread
                ((android.app.Activity) context).runOnUiThread(() -> {
                    // Notify listener with selected filters
                    if (listener != null) {
                        List<String> allergies = getSelectedAllergies();
                        List<String> dietPrefs = getSelectedDietPrefs();
                        String avoidFoods = ""; // Could be enhanced later
                        
                        // Extract nutritional filters from diet preferences
                        List<String> nutritionalFilters = new ArrayList<>();
                        for (String pref : dietPrefs) {
                            if (pref.toLowerCase().contains("high-protein") || 
                                pref.toLowerCase().contains("iron-rich") ||
                                pref.toLowerCase().contains("high-energy") ||
                                pref.toLowerCase().contains("low-calorie") ||
                                pref.toLowerCase().contains("vitamin") ||
                                pref.toLowerCase().contains("fiber") ||
                                pref.toLowerCase().contains("calcium")) {
                                nutritionalFilters.add(pref);
                            }
                        }
                        
                        listener.onFilterApplied(allergies, nutritionalFilters, avoidFoods);
                    }
                    
                    // Hide loading and close dialog
                    if (loadingSection != null) {
                        loadingSection.setVisibility(android.view.View.GONE);
                    }
                    if (closeButton != null) {
                        closeButton.setEnabled(true);
                    }
                    
                    // Close dialog
                    dismiss();
                });
                
            } catch (Exception e) {
                android.util.Log.e("FilterDialog", "Error applying filters: " + e.getMessage());
                ((android.app.Activity) context).runOnUiThread(() -> {
                    // Show error and re-enable close button
                    android.widget.TextView loadingText = findViewById(R.id.loading_text);
                    if (loadingText != null) {
                        loadingText.setText("Error applying filters. Please try again.");
                    }
                    if (closeButton != null) {
                        closeButton.setEnabled(true);
                    }
                    if (loadingSection != null) {
                        loadingSection.setVisibility(android.view.View.GONE);
                    }
                });
            }
        }).start();
    }
    
    /**
     * Get all selected filters for recommendations
     */
    private List<String> getSelectedFilters() {
        List<String> selectedFilters = new ArrayList<>();
        
        // Add selected diet preferences
        selectedFilters.addAll(getSelectedDietPrefs());
        
        // Add selected age groups
        for (int i = 0; i < ageGroupsChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) ageGroupsChipGroup.getChildAt(i);
            if (chip.isChecked()) {
                selectedFilters.add(chip.getText().toString());
            }
        }
        
        // Add selected cooking methods
        for (int i = 0; i < cookingMethodsChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) cookingMethodsChipGroup.getChildAt(i);
            if (chip.isChecked()) {
                selectedFilters.add(chip.getText().toString());
            }
        }
        
        // Add selected food categories
        for (int i = 0; i < foodCategoriesChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) foodCategoriesChipGroup.getChildAt(i);
            if (chip.isChecked()) {
                selectedFilters.add(chip.getText().toString());
            }
        }
        
        return selectedFilters;
    }
    
    /**
     * Get selected allergies
     */
    private List<String> getSelectedAllergies() {
        List<String> selectedAllergies = new ArrayList<>();
        android.util.Log.d("FilterDialog", "Getting selected allergies from " + allergiesChipGroup.getChildCount() + " chips");
        for (int i = 0; i < allergiesChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) allergiesChipGroup.getChildAt(i);
            android.util.Log.d("FilterDialog", "Allergy chip " + i + ": '" + chip.getText() + "' checked: " + chip.isChecked());
            if (chip.isChecked()) {
                selectedAllergies.add(chip.getText().toString());
            }
        }
        android.util.Log.d("FilterDialog", "Total selected allergies: " + selectedAllergies.size() + " - " + selectedAllergies);
        return selectedAllergies;
    }
    
    /**
     * Get selected diet preferences
     */
    private List<String> getSelectedDietPrefs() {
        List<String> selectedDietPrefs = new ArrayList<>();
        android.util.Log.d("FilterDialog", "Getting selected diet preferences from " + dietPrefsChipGroup.getChildCount() + " chips");
        for (int i = 0; i < dietPrefsChipGroup.getChildCount(); i++) {
            Chip chip = (Chip) dietPrefsChipGroup.getChildAt(i);
            android.util.Log.d("FilterDialog", "Diet chip " + i + ": '" + chip.getText() + "' checked: " + chip.isChecked());
            if (chip.isChecked()) {
                selectedDietPrefs.add(chip.getText().toString());
            }
        }
        android.util.Log.d("FilterDialog", "Total selected diet preferences: " + selectedDietPrefs.size() + " - " + selectedDietPrefs);
        return selectedDietPrefs;
    }
} 