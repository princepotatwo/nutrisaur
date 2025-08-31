package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.widget.Button;
import android.widget.CheckBox;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONArray;
import org.json.JSONObject;

import java.util.concurrent.TimeUnit;

import okhttp3.MediaType;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;

import android.util.Log;
import android.content.SharedPreferences;
import android.database.sqlite.SQLiteDatabase;
import android.content.ContentValues;
import com.example.nutrisaur11.Constants;

public class SignUpActivity extends AppCompatActivity {

    private EditText fullNameInput;
    private EditText emailInput;
    private EditText passwordInput;
    private EditText confirmPasswordInput;
    private CheckBox termsCheckbox;
    private Button signUpButton;
    private Button googleSignUp;
    private Button appleSignUp;
    private TextView loginLink;

    private static final String API_BASE_URL = Constants.UNIFIED_API_URL;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_signup);

        // Initialize views
        initializeViews();
        setupClickListeners();
    }

    private void initializeViews() {
        fullNameInput = findViewById(R.id.full_name_input);
        emailInput = findViewById(R.id.email_input);
        passwordInput = findViewById(R.id.password_input);
        confirmPasswordInput = findViewById(R.id.confirm_password_input);
        termsCheckbox = findViewById(R.id.terms_checkbox);
        signUpButton = findViewById(R.id.signup_button);
        googleSignUp = findViewById(R.id.google_signup);
        appleSignUp = findViewById(R.id.apple_signup);
        loginLink = findViewById(R.id.login_link);
    }

    private void setupClickListeners() {
        // Sign Up Button
        signUpButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                handleSignUp();
            }
        });

        // Google Sign Up
        googleSignUp.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                handleGoogleSignUp();
            }
        });

        // Apple Sign Up
        appleSignUp.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                handleAppleSignUp();
            }
        });

        // Login Link
        loginLink.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Go back to login page
                finish();
            }
        });
    }

    private void handleSignUp() {
        String fullName = fullNameInput.getText().toString().trim();
        String email = emailInput.getText().toString().trim();
        String password = passwordInput.getText().toString();
        String confirmPassword = confirmPasswordInput.getText().toString();

        // Validation
        if (TextUtils.isEmpty(fullName)) {
            fullNameInput.setError("Full name is required");
            return;
        }

        if (TextUtils.isEmpty(email)) {
            emailInput.setError("Email is required");
            return;
        }

        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            emailInput.setError("Please enter a valid email");
            return;
        }

        if (TextUtils.isEmpty(password)) {
            passwordInput.setError("Password is required");
            return;
        }

        if (password.length() < 6) {
            passwordInput.setError("Password must be at least 6 characters");
            return;
        }

        if (!password.equals(confirmPassword)) {
            confirmPasswordInput.setError("Passwords do not match");
            return;
        }

        if (!termsCheckbox.isChecked()) {
            Toast.makeText(this, "Please agree to the Terms of Service", Toast.LENGTH_SHORT).show();
            return;
        }

        // Save user profile to database
        saveUserProfile(fullName, email, password);
        
        // Show success message and go to screening
        Toast.makeText(this, "Account created successfully!", Toast.LENGTH_SHORT).show();
        // Save current user email
        getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).edit().putString("current_user_email", email).apply();
        // Navigate to screening form
        getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).edit().putBoolean("is_logged_in", true).apply();
        Intent intent = new Intent(SignUpActivity.this, ScreeningFormActivity.class);
        startActivity(intent);
        finish();
    }
    
    private void saveUserProfile(String username, String email, String password) {
        try {
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(this);
            
            // Save to local SQLite users table
            android.database.sqlite.SQLiteDatabase db = dbHelper.getWritableDatabase();
            android.content.ContentValues values = new android.content.ContentValues();
            values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
            values.put(UserPreferencesDbHelper.COL_USERNAME, username);
            values.put(UserPreferencesDbHelper.COL_PASSWORD, password);
            values.put(UserPreferencesDbHelper.COL_CREATED_AT, new java.text.SimpleDateFormat("yyyy-MM-dd HH:mm:ss", java.util.Locale.getDefault()).format(new java.util.Date()));
            
            long result = db.insert("users", null, values);
            android.util.Log.d("SignUpActivity", "User saved to local DB: " + result);
            
            // Create initial profile entry in user_profile table
            android.content.ContentValues profileValues = new android.content.ContentValues();
            profileValues.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
            profileValues.put(UserPreferencesDbHelper.COL_USER_NAME, username);
            profileValues.put(UserPreferencesDbHelper.COL_USER_AGE, 0); // Will be calculated from birthday
            profileValues.put(UserPreferencesDbHelper.COL_USER_HEIGHT, 0.0); // Will be set during screening
            profileValues.put(UserPreferencesDbHelper.COL_USER_WEIGHT, 0.0); // Will be set during screening
            profileValues.put(UserPreferencesDbHelper.COL_USER_BMI, 0.0); // Will be calculated
            profileValues.put(UserPreferencesDbHelper.COL_USER_GENDER, "Not specified");
            profileValues.put(UserPreferencesDbHelper.COL_USER_GOAL, "Healthy Nutrition");
            profileValues.put(UserPreferencesDbHelper.COL_BARANGAY, "Not specified"); // Will be set during screening
            
            long profileResult = db.insertWithOnConflict(
                UserPreferencesDbHelper.TABLE_NAME,
                null,
                profileValues,
                android.database.sqlite.SQLiteDatabase.CONFLICT_REPLACE
            );
            android.util.Log.d("SignUpActivity", "Initial profile created: " + profileResult);
            
            // Sync to web API
            new Thread(() -> {
                try {
                    okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                    
                    org.json.JSONObject json = new org.json.JSONObject();
                    json.put("action", "save_screening");
                    json.put("email", email);
                    json.put("username", username);
                    json.put("screening_data", new org.json.JSONObject().toString());
                    json.put("risk_score", 0);
                    json.put("barangay", "Not specified"); // Will be set during screening
                    json.put("municipality", "Not specified"); // Will be set during screening
                    
                    okhttp3.RequestBody body = okhttp3.RequestBody.create(
                        json.toString(), 
                        okhttp3.MediaType.parse("application/json")
                    );
                    
                    okhttp3.Request request = new okhttp3.Request.Builder()
                        .url(Constants.UNIFIED_API_URL + "?type=mobile_signup")
                        .post(body)
                        .build();
                    
                    try (okhttp3.Response response = client.newCall(request).execute()) {
                        if (response.isSuccessful()) {
                            android.util.Log.d("SignUpActivity", "User synced to unified API successfully");
                        } else {
                            android.util.Log.e("SignUpActivity", "Failed to sync user: " + response.code());
                        }
                    }
                } catch (Exception e) {
                    android.util.Log.e("SignUpActivity", "Error syncing user: " + e.getMessage());
                }
            }).start();
            
            // Sync default food preferences
            syncDefaultFoodPreferences(email);
            
        } catch (Exception e) {
            android.util.Log.e("SignUpActivity", "Error saving user profile: " + e.getMessage());
        }
    }
    
    // Add method to sync default food preferences
    private void syncDefaultFoodPreferences(String email) {
        new Thread(() -> {
            try {
                okhttp3.OkHttpClient client = new okhttp3.OkHttpClient();
                
                // Create default food preferences
                org.json.JSONObject json = new org.json.JSONObject();
                json.put("action", "save_preferences");
                json.put("email", email);
                json.put("username", email);
                json.put("allergies", new org.json.JSONArray()); // No allergies by default
                json.put("diet_prefs", new org.json.JSONArray()); // No diet preferences by default
                json.put("avoid_foods", ""); // No foods to avoid by default
                json.put("risk_score", 0); // Default risk score until screening is completed
                json.put("barangay", "Not specified"); // Will be set during screening
                json.put("municipality", "Not specified"); // Will be set during screening
                
                okhttp3.RequestBody body = okhttp3.RequestBody.create(
                    json.toString(), 
                    okhttp3.MediaType.parse("application/json")
                );
                
                okhttp3.Request request = new okhttp3.Request.Builder()
                    .url(Constants.UNIFIED_API_URL + "?type=mobile_signup")
                    .post(body)
                    .build();
                
                try (okhttp3.Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        android.util.Log.d("SignUpActivity", "Default food preferences synced to unified API successfully");
                    } else {
                        android.util.Log.e("SignUpActivity", "Failed to sync default food preferences: " + response.code());
                    }
                }
            } catch (Exception e) {
                android.util.Log.e("SignUpActivity", "Error syncing default food preferences: " + e.getMessage());
            }
        }).start();
    }

    private void handleGoogleSignUp() {
        // TODO: Implement Google Sign Up
        Toast.makeText(this, "Google Sign Up coming soon!", Toast.LENGTH_SHORT).show();
    }

    private void handleAppleSignUp() {
        // TODO: Implement Apple Sign Up
        Toast.makeText(this, "Apple Sign Up coming soon!", Toast.LENGTH_SHORT).show();
    }
} 