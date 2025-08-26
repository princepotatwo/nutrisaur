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

public class AccountActivity extends AppCompatActivity {
    
    private UserPreferencesDbHelper dbHelper;
    private TextView userNameText;
    private TextView userGoalText;
    private TextView bmiDisplayText;
    private TextView ageDisplayText;
    private TextView goalProgressText;
    private TextView currentWeightText;
    
    // Edit Profile button
    private Button editProfileButton;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_account);
        
        dbHelper = new UserPreferencesDbHelper(this);
        
        // Initialize views
        initializeViews();
        
        // Load and display user profile
        loadUserProfile();
        
        // Setup bottom navigation bar
        setupNavigation();
        
        // Setup logout
        setupLogout();
    }
    
    private void initializeViews() {
        userNameText = findViewById(R.id.user_name);
        userGoalText = findViewById(R.id.user_goal);
        bmiDisplayText = findViewById(R.id.bmi_display);
        ageDisplayText = findViewById(R.id.age_display);
        goalProgressText = findViewById(R.id.goal_progress);
        currentWeightText = findViewById(R.id.current_weight);
        
        // Edit Profile button
        editProfileButton = findViewById(R.id.edit_profile_button);
        if (editProfileButton != null) {
            editProfileButton.setOnClickListener(v -> showEditProfileDialog());
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
        
        // Use real-time API data instead of local SQLite
        new Thread(() -> {
            try {
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                
                okhttp3.Request request = new okhttp3.Request.Builder()
                    .url(Constants.API_BASE_URL + "unified_api.php?type=usm")
                    .get()
                    .build();
                
                try (okhttp3.Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        String responseBody = response.body().string();
                        Log.d("AccountActivity", "Real-time API response: " + responseBody);
                        
                        org.json.JSONObject jsonResponse = new org.json.JSONObject(responseBody);
                        if (jsonResponse.has("users")) {
                            org.json.JSONArray users = jsonResponse.getJSONArray("users");
                            // Find user by email in the array
                            org.json.JSONObject userData = null;
                            for (int i = 0; i < users.length(); i++) {
                                org.json.JSONObject user = users.getJSONObject(i);
                                if (user.getString("email").equals(email)) {
                                    userData = user;
                                    break;
                                }
                            }
                            if (userData != null) {
                                String screeningAnswersJson = userData.optString("screening_answers", "");
                                
                                Log.d("AccountActivity", "Found user data in real-time API: " + email);
                                Log.d("AccountActivity", "Screening answers: " + screeningAnswersJson);
                                
                                // Parse screening data for profile information
                                final String name = userData.optString("username", "User"); // Get actual username from API
                                String gender = "Not specified";
                                double weight = 0.0;
                                double height = 0.0;
                                int age = 0;
                                String goal = "Healthy Nutrition";
                                
                                if (!screeningAnswersJson.isEmpty() && !screeningAnswersJson.equals("[]")) {
                                    try {
                                        org.json.JSONObject screeningAnswers = new org.json.JSONObject(screeningAnswersJson);
                                        final String finalGender = screeningAnswers.optString("gender", "Not specified");
                                        
                                        // Extract weight and height from screening data
                                        final double finalWeight = screeningAnswers.optDouble("weight", 0.0);
                                        final double finalHeight = screeningAnswers.optDouble("height", 0.0);
                                        final int finalAge = calculateAgeFromBirthday(screeningAnswers.optString("birthday", ""));
                                        final String finalGoal = "Healthy Nutrition";
                                        
                                        // Extract new clinical risk factors for better profile insights
                                        final boolean hasRecentIllness = screeningAnswers.optBoolean("has_recent_illness", false);
                                        final boolean hasEatingDifficulty = screeningAnswers.optBoolean("has_eating_difficulty", false);
                                        final boolean hasFoodInsecurity = screeningAnswers.optBoolean("has_food_insecurity", false);
                                        final boolean hasMicronutrientDeficiency = screeningAnswers.optBoolean("has_micronutrient_deficiency", false);
                                        final boolean hasFunctionalDecline = screeningAnswers.optBoolean("has_functional_decline", false);
                                        
                                        // Calculate BMI
                                        final double bmi = (finalWeight > 0 && finalHeight > 0) ? 
                                            finalWeight / ((finalHeight / 100) * (finalHeight / 100)) : 0;
                                        
                                        Log.d("AccountActivity", "Parsed screening data - gender: " + finalGender + ", weight: " + finalWeight + ", height: " + finalHeight + ", bmi: " + bmi + ", age: " + finalAge + " months");
                                        Log.d("AccountActivity", "Clinical risk factors - illness: " + hasRecentIllness + ", eating: " + hasEatingDifficulty + ", food: " + hasFoodInsecurity + ", micronutrient: " + hasMicronutrientDeficiency + ", functional: " + hasFunctionalDecline);
                                        
                                        Log.d("AccountActivity", "Real-time profile data - name: " + name + ", age: " + finalAge + " months, weight: " + finalWeight + ", height: " + finalHeight + ", bmi: " + bmi + ", gender: " + finalGender + ", goal: " + finalGoal);
                                        
                                        // Update UI on main thread with real-time data
                                        runOnUiThread(() -> {
                                            displayUserProfile(name, finalAge, finalHeight, finalWeight, bmi, finalGender, finalGoal);
                                            Log.d("AccountActivity", "Updated profile display with real-time API data");
                                        });
                                        
                                    } catch (Exception e) {
                                        Log.e("AccountActivity", "Error parsing screening answers: " + e.getMessage());
                                        
                                        // Update UI on main thread with default data
                                        runOnUiThread(() -> {
                                            displayUserProfile(name, age, height, weight, 0, gender, goal);
                                            Log.d("AccountActivity", "Updated profile display with default data");
                                        });
                                    }
                                } else {
                                    // Update UI on main thread with default data
                                    runOnUiThread(() -> {
                                        displayUserProfile(name, age, height, weight, 0, gender, goal);
                                        Log.d("AccountActivity", "Updated profile display with default data");
                                    });
                                }
                                
                            } else {
                                Log.d("AccountActivity", "User not found in real-time API: " + email);
                                // Fallback to local SQLite
                                runOnUiThread(() -> {
                                    loadUserProfileFromLocalDB();
                                });
                            }
                        } else {
                            Log.d("AccountActivity", "No users data in real-time API response");
                            // Fallback to local SQLite
                            runOnUiThread(() -> {
                                loadUserProfileFromLocalDB();
                            });
                        }
                    } else {
                        Log.e("AccountActivity", "Failed to get real-time data: " + response.code());
                        // Fallback to local SQLite
                        runOnUiThread(() -> {
                            loadUserProfileFromLocalDB();
                        });
                    }
                }
            } catch (Exception e) {
                Log.e("AccountActivity", "Error getting real-time profile data: " + e.getMessage());
                // Fallback to local SQLite
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
        
        // Display BMI and Age with accurate values
        if (bmiDisplayText != null) {
            if (bmi > 0) {
            bmiDisplayText.setText(String.format("%.1f", bmi));
            } else {
                bmiDisplayText.setText("--");
            }
        }
        
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
        
        // Calculate and display goal progress based on BMI
        if (goalProgressText != null) {
            String progress = calculateGoalProgress(bmi);
            goalProgressText.setText(progress);
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
        if (bmiDisplayText != null) bmiDisplayText.setText("0.0");
        if (ageDisplayText != null) ageDisplayText.setText("25");
        if (goalProgressText != null) goalProgressText.setText("0%");
        if (currentWeightText != null) currentWeightText.setText("0.0 kg");
    }
    
    private double calculateBMI(double height, double weight) {
        // BMI = weight(kg) / height(m)²
        double heightInMeters = height / 100.0;
        return weight / (heightInMeters * heightInMeters);
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
    
    private String calculateGoalProgress(double bmi) {
        // Calculate progress based on BMI and nutrition goals
        if (bmi < 18.5) {
            // Underweight - goal is to gain weight
            return "65";
        } else if (bmi >= 18.5 && bmi < 25) {
            // Normal weight - goal is to maintain
            return "85";
        } else {
            // Overweight - goal is to lose weight
            return "45";
        }
    }
    
    private void setupNavigation() {
        findViewById(R.id.nav_home).setOnClickListener(v -> {
            Intent intent = new Intent(AccountActivity.this, MainActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        findViewById(R.id.nav_food).setOnClickListener(v -> {
            Intent intent = new Intent(AccountActivity.this, FoodActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        findViewById(R.id.nav_favorites).setOnClickListener(v -> {
            Intent intent = new Intent(AccountActivity.this, FavoritesActivity.class);
            startActivity(intent);
            overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
            finish();
        });
        findViewById(R.id.nav_account).setOnClickListener(v -> {
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
                        getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).edit()
                            .putBoolean("is_logged_in", false)
                            .remove("current_user_email")
                            .apply();
                        Intent intent = new Intent(AccountActivity.this, LoginActivity.class);
                        startActivity(intent);
                        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                        finish();
                    })
                    .setNegativeButton("Cancel", null)
                    .show();
            });
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