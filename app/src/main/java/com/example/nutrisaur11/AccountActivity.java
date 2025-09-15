package com.example.nutrisaur11;

import android.content.Intent;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import android.widget.Button;
import com.example.nutrisaur11.UserPreferencesDbHelper;
import com.example.nutrisaur11.MainActivity;
import java.util.Map;

public class AccountActivity extends BaseActivity {
    
    private UserPreferencesDbHelper dbHelper;
    private TextView userNameText;
    private TextView userGoalText;
    private TextView bmiDisplayText;
    private TextView ageDisplayText;
    private TextView currentWeightText;
    private TextView heightDisplayText;
    
    // Edit Profile button
    private Button editProfileButton;
    private Button refreshProfileButton;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_account);
        
        dbHelper = new UserPreferencesDbHelper(this);
        
        // Set header title
        TextView pageTitle = findViewById(R.id.page_title);
        TextView pageSubtitle = findViewById(R.id.page_subtitle);
        if (pageTitle != null) {
            pageTitle.setText("PROFILE");
        }
        if (pageSubtitle != null) {
            pageSubtitle.setText("Manage your account settings");
        }
        
        // Initialize views
        initializeViews();
        
        // Load and display user profile
        loadUserProfile();
        
        // Setup bottom navigation bar
        setupNavigation();
        
        // Setup logout
        setupLogout();
        
        // Call this after session validation
        onSessionValidated();
    }
    
    @Override
    protected void initializeActivity() {
        // Additional initialization after session validation
        // This method is called automatically by BaseActivity
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        // Refresh profile data when returning to the activity
        // This ensures we have the latest data if the user made changes elsewhere
        refreshProfileData();
    }
    
    private void initializeViews() {
        userNameText = findViewById(R.id.user_name);
        userGoalText = findViewById(R.id.user_goal);
        bmiDisplayText = findViewById(R.id.bmi_display);
        ageDisplayText = findViewById(R.id.age_display);
        currentWeightText = findViewById(R.id.current_weight);
        heightDisplayText = findViewById(R.id.height_display);
        
        // Edit Profile button
        editProfileButton = findViewById(R.id.edit_profile_button);
        if (editProfileButton != null) {
            editProfileButton.setOnClickListener(v -> showEditProfileDialog());
        }
        
        // Refresh Profile button
        refreshProfileButton = findViewById(R.id.refresh_profile_button);
        if (refreshProfileButton != null) {
            refreshProfileButton.setOnClickListener(v -> {
                onUserInteraction(); // Track user interaction
                Log.d("AccountActivity", "Refresh button clicked");
                refreshProfileData();
                Toast.makeText(AccountActivity.this, "Profile data refreshed", Toast.LENGTH_SHORT).show();
            });
        }
    }
    
    private void showEditProfileDialog() {
        String email = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE)
                .getString("current_user_email", null);
        
        if (email == null) {
            Toast.makeText(this, "No user email found", Toast.LENGTH_SHORT).show();
            return;
        }
        
        // Launch the new EditProfileDialog (to be created)
        EditProfileDialog editProfileDialog = new EditProfileDialog(this, email);
        editProfileDialog.setProfileUpdateListener(new EditProfileDialog.ProfileUpdateListener() {
            public void onProfileUpdated() {
                Log.d("AccountActivity", "Profile updated, refreshing data");
                loadUserProfile();
                // Apply fade transition
                overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            }
        });
        editProfileDialog.setOnDismissListener(dialog -> {
            Log.d("AccountActivity", "Edit Profile dialog dismissed");
            // Apply fade transition on dismiss
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
        });
        editProfileDialog.show();
    }
    
    private String getCurrentUserEmail() {
        return getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE)
                .getString("current_user_email", null);
    }
    
    private int calculateAgeFromBirthday(String birthdayStr) {
        if (birthdayStr == null || birthdayStr.isEmpty()) {
            return 0;
        }
        
        try {
            // Parse birthday string (format: MM/DD/YYYY)
            String[] parts = birthdayStr.split("/");
            if (parts.length == 3) {
                int birthMonth = Integer.parseInt(parts[0]) - 1; // Calendar months are 0-based
                int birthDay = Integer.parseInt(parts[1]);
                int birthYear = Integer.parseInt(parts[2]);
                
                java.util.Calendar birth = java.util.Calendar.getInstance();
                birth.set(birthYear, birthMonth, birthDay);
                
                java.util.Calendar now = java.util.Calendar.getInstance();
                int years = now.get(java.util.Calendar.YEAR) - birth.get(java.util.Calendar.YEAR);
                int months = now.get(java.util.Calendar.MONTH) - birth.get(java.util.Calendar.MONTH);
                int totalMonths = years * 12 + months;
                if (now.get(java.util.Calendar.DAY_OF_MONTH) < birth.get(java.util.Calendar.DAY_OF_MONTH)) {
                    totalMonths--;
                }
                return Math.max(totalMonths, 0);
            }
        } catch (Exception e) {
            Log.e("AccountActivity", "Error calculating age from birthday '" + birthdayStr + "': " + e.getMessage());
        }
        return 0;
    }
    
    private void loadUserProfile() {
        String email = getCurrentUserEmail();
        if (email == null) {
            Log.e("AccountActivity", "No user email found");
            return;
        }
        
        Log.d("AccountActivity", "Loading user profile for email: " + email);
        
        // Check if we have cached data first
        if (hasCachedProfileData(email)) {
            Log.d("AccountActivity", "Using cached profile data");
            loadCachedProfileData(email);
            return;
        }
        
        // Use CommunityUserManager to fetch data from community_users table
        new Thread(() -> {
            try {
                CommunityUserManager userManager = new CommunityUserManager(AccountActivity.this);
                Map<String, String> userData = userManager.getCurrentUserData();
                
                if (!userData.isEmpty()) {
                    Log.d("AccountActivity", "Retrieved user data from community_users: " + userData.toString());
                    
                    // Extract data from community_users table
                    final String name = userData.getOrDefault("name", "User");
                    final String sex = userData.getOrDefault("sex", "Not specified");
                    final String ageStr = userData.getOrDefault("age", "0");
                    final String weightStr = userData.getOrDefault("weight_kg", "0");
                    final String heightStr = userData.getOrDefault("height_cm", "0");
                    final String bmiStr = userData.getOrDefault("bmi", "0");
                    
                    // Parse values
                    final int age = Integer.parseInt(ageStr);
                    final double weight = Double.parseDouble(weightStr);
                    final double height = Double.parseDouble(heightStr);
                    final double bmi = Double.parseDouble(bmiStr);
                    final String goal = "Healthy Nutrition";
                    
                    Log.d("AccountActivity", "Parsed community_users data - name: " + name + ", age: " + age + ", weight: " + weight + ", height: " + height + ", bmi: " + bmi + ", sex: " + sex);
                    
                    // Cache the data
                    cacheProfileData(email, name, age, height, weight, bmi, sex, goal);
                    
                    // Update UI on main thread
                    runOnUiThread(() -> {
                        displayUserProfile(name, age, height, weight, bmi, sex, goal);
                        Log.d("AccountActivity", "Updated profile display with community_users data");
                    });
                    
                } else {
                    Log.d("AccountActivity", "No data found in community_users - user may have been deleted");
                    // User not found in database, trigger session validation
                    runOnUiThread(() -> {
                        SessionManager.getInstance(AccountActivity.this).forceLogout(AccountActivity.this, 
                            "Your account is no longer available in the database. Please contact support or create a new account.");
                    });
                }
                
            } catch (Exception e) {
                Log.e("AccountActivity", "Error getting community_users data: " + e.getMessage());
                runOnUiThread(() -> {
                    loadUserProfileFromLocalDB();
                });
            }
        }).start();
    }
    
    // Fallback method to load from local SQLite
    private void loadUserProfileFromLocalDB() {
        String email = getCurrentUserEmail();
        if (email == null) {
            Log.e("AccountActivity", "No user email found");
            return;
        }
        
        try {
            // Load user profile from preferences table (birthday, weight, height)
            android.database.Cursor profileCursor = dbHelper.getReadableDatabase().rawQuery(
                "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + 
                " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
                new String[]{email}
            );
            
            // Load screening data for gender and other info
            android.database.Cursor screeningCursor = dbHelper.getReadableDatabase().rawQuery(
                "SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + 
                " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?",
                new String[]{email}
            );
            
            if (!profileCursor.moveToFirst()) {
                Log.d("AccountActivity", "No profile data found, creating default profile");
                createDefaultProfile(email);
                profileCursor.close();
                screeningCursor.close();
                return;
            }
            
            // Extract profile data with safety checks for column existence
            String name = null;
            String birthdayStr = null;
            double weight = 0.0;
            double height = 0.0;
            String goal = null;
            
            try {
                int nameIndex = profileCursor.getColumnIndex(UserPreferencesDbHelper.COL_USER_NAME);
                int birthdayIndex = profileCursor.getColumnIndex(UserPreferencesDbHelper.COL_USER_BIRTHDAY);
                int weightIndex = profileCursor.getColumnIndex(UserPreferencesDbHelper.COL_USER_WEIGHT);
                int heightIndex = profileCursor.getColumnIndex(UserPreferencesDbHelper.COL_USER_HEIGHT);
                int goalIndex = profileCursor.getColumnIndex(UserPreferencesDbHelper.COL_USER_GOAL);
                
                if (nameIndex >= 0) name = profileCursor.getString(nameIndex);
                if (birthdayIndex >= 0) birthdayStr = profileCursor.getString(birthdayIndex);
                if (weightIndex >= 0) weight = profileCursor.getDouble(weightIndex);
                if (heightIndex >= 0) height = profileCursor.getDouble(heightIndex);
                if (goalIndex >= 0) goal = profileCursor.getString(goalIndex);
            } catch (Exception e) {
                Log.e("AccountActivity", "Error reading profile columns: " + e.getMessage());
            }
            
            // Get gender from screening data with safety check
            String gender = "Not specified";
            if (screeningCursor.moveToFirst()) {
                try {
                    int genderIndex = screeningCursor.getColumnIndex(UserPreferencesDbHelper.COL_GENDER);
                    if (genderIndex >= 0) {
                        gender = screeningCursor.getString(genderIndex);
                    }
                } catch (Exception e) {
                    Log.e("AccountActivity", "Error reading gender column: " + e.getMessage());
                }
            }
            
            // Calculate age from birthday
            int age = 0;
            if (birthdayStr != null && !birthdayStr.isEmpty()) {
                try {
                    // Parse birthday string (format: YYYY-MM-DD)
                    String[] parts = birthdayStr.split("-");
                    if (parts.length == 3) {
                        int birthYear = Integer.parseInt(parts[0]);
                        int birthMonth = Integer.parseInt(parts[1]) - 1; // Calendar months are 0-based
                        int birthDay = Integer.parseInt(parts[2]);
                        
                        java.util.Calendar birth = java.util.Calendar.getInstance();
                        birth.set(birthYear, birthMonth, birthDay);
                        
                        java.util.Calendar now = java.util.Calendar.getInstance();
                        int years = now.get(java.util.Calendar.YEAR) - birth.get(java.util.Calendar.YEAR);
                        int months = now.get(java.util.Calendar.MONTH) - birth.get(java.util.Calendar.MONTH);
                        int totalMonths = years * 12 + months;
                        if (now.get(java.util.Calendar.DAY_OF_MONTH) < birth.get(java.util.Calendar.DAY_OF_MONTH)) {
                            totalMonths--;
                        }
                        age = Math.max(totalMonths, 0);
                        Log.d("AccountActivity", "Calculated age: " + age + " months from birthday: " + birthdayStr);
                    }
                } catch (Exception e) {
                    Log.e("AccountActivity", "Error calculating age from birthday '" + birthdayStr + "': " + e.getMessage());
                    age = 0;
                }
            } else {
                Log.d("AccountActivity", "No birthday found, age set to 0");
            }
            
            // Calculate BMI
            double bmi = 0;
            if (weight > 0 && height > 0) {
                bmi = calculateBMI(height, weight);
            }
            
            Log.d("AccountActivity", "Profile data loaded from local DB - name: " + name + ", age: " + age + " months, weight: " + weight + ", height: " + height + ", bmi: " + bmi + ", gender: " + gender + ", goal: " + goal);
            
            displayUserProfile(name, age, height, weight, bmi, gender, goal);
            
            profileCursor.close();
            screeningCursor.close();
            
        } catch (Exception e) {
            Log.e("AccountActivity", "Error loading profile from local DB: " + e.getMessage());
            setDefaultProfile();
        }
    }
    
    private void displayUserProfile(String name, int age, double height, double weight, double bmi, String gender, String goal) {
        // Set user name
        if (userNameText != null) {
            userNameText.setText(name != null ? name : "User");
        }
        
        // Set user goal
        if (userGoalText != null) {
            userGoalText.setText("Goal: " + (goal != null ? goal : "Healthy Nutrition"));
        }
        
        // Display BMI with accurate values
        if (bmiDisplayText != null) {
            if (bmi > 0) {
            bmiDisplayText.setText(String.format("%.1f", bmi));
            } else {
                bmiDisplayText.setText("--");
            }
        }
        
        // Display Age
        if (ageDisplayText != null) {
            if (age > 0) {
            // Convert months to years for display
                int years = age / 12;
                int months = age % 12;
                if (years > 0) {
                    ageDisplayText.setText(years + " years" + (months > 0 ? " " + months + " months" : ""));
                } else {
                    ageDisplayText.setText(months + " months");
                }
            } else {
                ageDisplayText.setText("--");
            }
        }
        
        // Display current weight with accurate value
        if (currentWeightText != null) {
            if (weight > 0) {
            currentWeightText.setText(String.format("%.1f kg", weight));
            } else {
                currentWeightText.setText("-- kg");
            }
        }
        
        // Display height
        if (heightDisplayText != null) {
            if (height > 0) {
                heightDisplayText.setText(String.format("%.1f cm", height));
            } else {
                heightDisplayText.setText("-- cm");
            }
        }
    }
    
    private void createDefaultProfile(String email) {
        // Create a default profile based on signup information
        // For now, we'll use placeholder data
        String name = email.split("@")[0]; // Use email prefix as name
        int age = 25; // Default age
        double height = 170.0; // Default height in cm
        double weight = 70.0; // Default weight in kg
        double bmi = calculateBMI(height, weight);
        String gender = "Not specified";
        String goal = "Healthy Nutrition";
        
        // Save to database
        android.content.ContentValues values = new android.content.ContentValues();
        values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
        values.put(UserPreferencesDbHelper.COL_USER_NAME, name);
        values.put(UserPreferencesDbHelper.COL_USER_AGE, age);
        values.put(UserPreferencesDbHelper.COL_USER_HEIGHT, height);
        values.put(UserPreferencesDbHelper.COL_USER_WEIGHT, weight);
        values.put(UserPreferencesDbHelper.COL_USER_BMI, bmi);
        values.put(UserPreferencesDbHelper.COL_USER_GENDER, gender);
        values.put(UserPreferencesDbHelper.COL_USER_GOAL, goal);
        
        dbHelper.getWritableDatabase().insertWithOnConflict(
            UserPreferencesDbHelper.TABLE_NAME,
            null,
            values,
            android.database.sqlite.SQLiteDatabase.CONFLICT_REPLACE
        );
        
        displayUserProfile(name, age, height, weight, bmi, gender, goal);
    }
    
    private void setDefaultProfile() {
        // Set default values when no user data is available
        if (userNameText != null) userNameText.setText("User");
        if (userGoalText != null) userGoalText.setText("Goal: Healthy Nutrition");
        if (bmiDisplayText != null) bmiDisplayText.setText("--");
        if (ageDisplayText != null) ageDisplayText.setText("--");
        if (currentWeightText != null) currentWeightText.setText("-- kg");
        if (heightDisplayText != null) heightDisplayText.setText("-- cm");
    }
    
    private double calculateBMI(double height, double weight) {
        // BMI = weight(kg) / height(m)²
        double heightInMeters = height / 100.0;
        return weight / (heightInMeters * heightInMeters);
    }
    
    private String getBMICategory(double bmi) {
        if (bmi < 18.5) return "Underweight";
        else if (bmi < 25) return "Normal";
        else if (bmi < 30) return "Overweight";
        else return "Obese";
    }
    
    // Caching methods
    private boolean hasCachedProfileData(String email) {
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        long lastUpdate = prefs.getLong("profile_cache_time_" + email, 0);
        long currentTime = System.currentTimeMillis();
        long cacheValidity = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
        
        return (currentTime - lastUpdate) < cacheValidity && prefs.contains("profile_name_" + email);
    }
    
    private void cacheProfileData(String email, String name, int age, double height, double weight, double bmi, String gender, String goal) {
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        
        editor.putString("profile_name_" + email, name);
        editor.putInt("profile_age_" + email, age);
        editor.putFloat("profile_height_" + email, (float) height);
        editor.putFloat("profile_weight_" + email, (float) weight);
        editor.putFloat("profile_bmi_" + email, (float) bmi);
        editor.putString("profile_gender_" + email, gender);
        editor.putString("profile_goal_" + email, goal);
        editor.putLong("profile_cache_time_" + email, System.currentTimeMillis());
        
        editor.apply();
        Log.d("AccountActivity", "Cached profile data for: " + email);
    }
    
    private void loadCachedProfileData(String email) {
        android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
        
        String name = prefs.getString("profile_name_" + email, "User");
        int age = prefs.getInt("profile_age_" + email, 0);
        double height = prefs.getFloat("profile_height_" + email, 0.0f);
        double weight = prefs.getFloat("profile_weight_" + email, 0.0f);
        double bmi = prefs.getFloat("profile_bmi_" + email, 0.0f);
        String gender = prefs.getString("profile_gender_" + email, "Not specified");
        String goal = prefs.getString("profile_goal_" + email, "Healthy Nutrition");
        
        Log.d("AccountActivity", "Loaded cached profile data for: " + email);
        displayUserProfile(name, age, height, weight, bmi, gender, goal);
    }
    
    public void refreshProfileData() {
        String email = getCurrentUserEmail();
        if (email != null) {
            // Clear cache and reload
            android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
            android.content.SharedPreferences.Editor editor = prefs.edit();
            editor.remove("profile_cache_time_" + email);
            editor.apply();
            
            loadUserProfile();
        }
    }
    
    private String calculateDietStreak() {
        // For now, return a placeholder value
        // In a real app, this would calculate based on user's meal logging history
        return "7";
    }
    
    private String calculateWeightGained(double currentWeight) {
        // For now, return a placeholder value
        // In a real app, this would calculate based on weight tracking history
        return "+1.2";
    }
    
    
    private void setupNavigation() {
        findViewById(R.id.nav_home).setOnClickListener(v -> {
            onUserInteraction(); // Track user interaction
            Intent intent = new Intent(AccountActivity.this, MainActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        findViewById(R.id.nav_food).setOnClickListener(v -> {
            onUserInteraction(); // Track user interaction
            Intent intent = new Intent(AccountActivity.this, FoodActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        findViewById(R.id.nav_favorites).setOnClickListener(v -> {
            onUserInteraction(); // Track user interaction
            Intent intent = new Intent(AccountActivity.this, FavoritesActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        findViewById(R.id.nav_account).setOnClickListener(v -> {
            onUserInteraction(); // Track user interaction
            // Already in AccountActivity, do nothing
        });
    }
    
    private void setupLogout() {
        View logoutSection = findViewById(R.id.logout_section);
        if (logoutSection != null) {
            logoutSection.setOnClickListener(v -> {
                new androidx.appcompat.app.AlertDialog.Builder(AccountActivity.this)
                    .setTitle("Logout")
                    .setMessage("Are you sure you want to logout?")
                    .setPositiveButton("Yes", (dialog, which) -> {
                        clearAllUserData();
                        Intent intent = new Intent(AccountActivity.this, MainActivity.class);
                        startActivity(intent);
                        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                        finish();
                    })
                    .setNegativeButton("Cancel", null)
                    .show();
            });
        }
    }
    
    private void clearAllUserData() {
        try {
            android.content.SharedPreferences prefs = getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE);
            String currentUserEmail = prefs.getString("current_user_email", null);
            
            // Clear user-specific data first
            if (currentUserEmail != null) {
                AddedFoodManager.clearUserData(this, currentUserEmail);
                CalorieTracker.clearUserData(this, currentUserEmail);
                GeminiCacheManager.clearUserData(this, currentUserEmail);
                FavoritesManager.clearUserData(this, currentUserEmail);
                
                // Clear profile cache
                CommunityUserManager userManager = new CommunityUserManager(this);
                userManager.clearUserCache(currentUserEmail);
                
                android.util.Log.d("AccountActivity", "Cleared user-specific data for: " + currentUserEmail);
            }
            
            // Clear all user data from main preferences
            android.content.SharedPreferences.Editor editor = prefs.edit();
            editor.clear();
            
            // Set basic logout state
            editor.putBoolean("is_logged_in", false);
            
            editor.apply();
            
            android.util.Log.d("AccountActivity", "All user data cleared from SharedPreferences");
            
        } catch (Exception e) {
            android.util.Log.e("AccountActivity", "Error clearing user data: " + e.getMessage());
        }
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (dbHelper != null) {
            dbHelper.close();
        }
    }
} 