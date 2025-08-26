package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.nutrisaur11.Constants;
// Removed WebViewAPIClient - using direct HTTP requests
import java.util.concurrent.CompletableFuture;

public class LoginActivity extends AppCompatActivity {

    private EditText emailInput;
    private EditText passwordInput;
    private TextView forgotPassword;
    private Button loginButton;
    private Button googleLogin;
    private Button appleLogin;
    private TextView signUpLink;
    private Button testScreeningButton;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        // Initialize views
        initializeViews();
        setupClickListeners();
    }

    private void initializeViews() {
        emailInput = findViewById(R.id.email_input);
        passwordInput = findViewById(R.id.password_input);
        forgotPassword = findViewById(R.id.forgot_password);
        loginButton = findViewById(R.id.login_button);
        googleLogin = findViewById(R.id.google_login);
        appleLogin = findViewById(R.id.apple_login);
        signUpLink = findViewById(R.id.sign_up_link);
        testScreeningButton = findViewById(R.id.test_screening_button);
    }

    private void setupClickListeners() {
        // Login Button
        loginButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                handleLogin();
            }
        });

        // Forgot Password
        forgotPassword.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                handleForgotPassword();
            }
        });

        // Google Login
        googleLogin.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                handleGoogleLogin();
            }
        });

        // Apple Login
        appleLogin.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                handleAppleLogin();
            }
        });

        // Sign Up Link
        signUpLink.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                // Navigate to sign up page
                Intent intent = new Intent(LoginActivity.this, SignUpActivity.class);
                startActivity(intent);
            }
        });

        // Test Screening Button
        testScreeningButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                startTestScreening();
            }
        });
    }

    private void handleLogin() {
        String email = emailInput.getText().toString().trim();
        String password = passwordInput.getText().toString();

        // Validation
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

        // Validate user exists in web database before login
        validateUserAndLogin(email, password);
    }
    
    // Removed WebViewAPIClient - using direct HTTP requests
    
    private void validateUserAndLogin(String email, String password) {
        // Use direct HTTP request to validate user
        new Thread(() -> {
            try {
                // Create JSON request
                org.json.JSONObject requestData = new org.json.JSONObject();
                requestData.put("action", "validate_user");
                requestData.put("email", email);
                
                // Make direct HTTP request
                java.net.URL url = new java.net.URL(Constants.UNIFIED_API_URL);
                java.net.HttpURLConnection conn = (java.net.HttpURLConnection) url.openConnection();
                conn.setRequestMethod("POST");
                conn.setRequestProperty("Content-Type", "application/json");
                conn.setDoOutput(true);
                conn.setConnectTimeout(10000);
                conn.setReadTimeout(10000);
                
                java.io.OutputStream os = conn.getOutputStream();
                os.write(requestData.toString().getBytes("UTF-8"));
                os.close();
                
                int responseCode = conn.getResponseCode();
                if (responseCode == 200) {
                    java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(conn.getInputStream()));
                    StringBuilder response = new StringBuilder();
                    String line;
                    while ((line = reader.readLine()) != null) {
                        response.append(line);
                    }
                    reader.close();
                    
                    org.json.JSONObject jsonResponse = new org.json.JSONObject(response.toString());
                    if (jsonResponse.optBoolean("valid", false)) {
                        // User is valid, proceed with login
                        runOnUiThread(() -> {
                                Toast.makeText(LoginActivity.this, "Login successful!", Toast.LENGTH_SHORT).show();
                                // Save current user email and set login status
                                getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).edit()
                                    .putString("current_user_email", email)
                                    .putBoolean("is_logged_in", true)
                                    .apply();
                                // Navigate to main activity (dashboard)
                                Intent intent = new Intent(LoginActivity.this, MainActivity.class);
                                startActivity(intent);
                                finish();
                            });
                    } else {
                        // User was deleted, clear local data and show error
                        runOnUiThread(() -> {
                            clearLocalUserData(email);
                            Toast.makeText(LoginActivity.this, "Account not found. Please sign up again.", Toast.LENGTH_LONG).show();
                        });
                    }
                } else {
                    // HTTP error, proceed with local login as fallback
                    runOnUiThread(() -> {
                        Toast.makeText(LoginActivity.this, "Login successful!", Toast.LENGTH_SHORT).show();
                        getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).edit()
                            .putString("current_user_email", email)
                            .putBoolean("is_logged_in", true)
                            .apply();
                        Intent intent = new Intent(LoginActivity.this, MainActivity.class);
                        startActivity(intent);
                        finish();
                    });
                }
            } catch (Exception e) {
                // Error with HTTP request, proceed with local login as fallback
                runOnUiThread(() -> {
                    Toast.makeText(LoginActivity.this, "Login successful!", Toast.LENGTH_SHORT).show();
                    getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).edit()
                        .putString("current_user_email", email)
                        .putBoolean("is_logged_in", true)
                        .apply();
                    Intent intent = new Intent(LoginActivity.this, MainActivity.class);
                    startActivity(intent);
                    finish();
                });
            }
        }).start();
    }
    
    private void clearLocalUserData(String email) {
        try {
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(this);
            android.database.sqlite.SQLiteDatabase db = dbHelper.getWritableDatabase();
            
            // Delete user preferences
            db.delete("preferences", "user_email = ?", new String[]{email});
            
            // User profile data is now in preferences table, will be deleted below
            
            // Delete food recommendations
            db.delete("food_recommendations", "user_email = ?", new String[]{email});
            
            dbHelper.close();
            
            // Clear shared preferences
            getSharedPreferences("nutrisaur_prefs", MODE_PRIVATE).edit()
                .remove("current_user_email")
                .putBoolean("is_logged_in", false)
                .apply();
                
        } catch (Exception e) {
            android.util.Log.e("LoginActivity", "Error clearing local data: " + e.getMessage());
        }
    }

    private void handleForgotPassword() {
        // TODO: Implement forgot password functionality
        Toast.makeText(this, "Forgot password coming soon!", Toast.LENGTH_SHORT).show();
    }

    private void handleGoogleLogin() {
        // TODO: Implement Google Login
        Toast.makeText(this, "Google Login coming soon!", Toast.LENGTH_SHORT).show();
    }

    private void handleAppleLogin() {
        // TODO: Implement Apple Login
        Toast.makeText(this, "Apple Login coming soon!", Toast.LENGTH_SHORT).show();
    }

    private void startTestScreening() {
        Toast.makeText(this, "Starting test screening with demo data...", Toast.LENGTH_SHORT).show();
        
        // Create SharedPreferences editor to save test user data
        android.content.SharedPreferences prefs = getSharedPreferences("NutrisaurPrefs", MODE_PRIVATE);
        android.content.SharedPreferences.Editor editor = prefs.edit();
        
        // Save test user credentials
        String testEmail = "test.user@nutrisaur.demo";
        String testUsername = "Demo User";
        editor.putString("user_email", testEmail);
        editor.putString("username", testUsername);
        editor.putBoolean("is_logged_in", true);
        
        // Pre-fill some basic user info for the test
        editor.putString("user_barangay", "A. Rivera (Pob.)");
        editor.putString("user_municipality", "Biñan");
        editor.putInt("user_age", 25);
        editor.putString("user_gender", "female");
        editor.putFloat("user_weight", 65.0f);
        editor.putFloat("user_height", 160.0f);
        editor.putString("user_income", "15000-25000");
        
        editor.apply();
        
        // Navigate directly to screening activity with some pre-filled data
        Intent intent = new Intent(LoginActivity.this, ScreeningFormActivity.class);
        intent.putExtra("test_mode", true);
        intent.putExtra("test_email", testEmail);
        intent.putExtra("test_username", testUsername);
        
        startActivity(intent);
        finish(); // Close login activity
    }
} 