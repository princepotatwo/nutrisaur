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

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        // Initialize views
        initializeViews();
        setupClickListeners();
        
        // Button styling is handled by XML layout and themes
    }

    private void initializeViews() {
        emailInput = findViewById(R.id.email_input);
        passwordInput = findViewById(R.id.password_input);
        forgotPassword = findViewById(R.id.forgot_password);
        loginButton = findViewById(R.id.login_button);
        googleLogin = findViewById(R.id.google_login);
        appleLogin = findViewById(R.id.apple_login);
        signUpLink = findViewById(R.id.sign_up_link);
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
        try {
            // Show loading state
            loginButton.setText("Signing In...");
            loginButton.setEnabled(false);
            
            CommunityUserManager userManager = new CommunityUserManager(this);
            userManager.loginUser(email, password, new CommunityUserManager.LoginCallback() {
                @Override
                public void onSuccess(String message) {
                    runOnUiThread(() -> {
                        try {
                            Toast.makeText(LoginActivity.this, message, Toast.LENGTH_SHORT).show();
                            // Navigate to main activity (dashboard)
                            Intent intent = new Intent(LoginActivity.this, MainActivity.class);
                            startActivity(intent);
                            finish();
                        } catch (Exception e) {
                            android.util.Log.e("LoginActivity", "Error in success callback: " + e.getMessage());
                            resetLoginButton();
                        }
                    });
                }
                
                @Override
                public void onError(String error) {
                    runOnUiThread(() -> {
                        try {
                            Toast.makeText(LoginActivity.this, error, Toast.LENGTH_LONG).show();
                            resetLoginButton();
                        } catch (Exception e) {
                            android.util.Log.e("LoginActivity", "Error in error callback: " + e.getMessage());
                            resetLoginButton();
                        }
                    });
                }
            });
        } catch (Exception e) {
            android.util.Log.e("LoginActivity", "Error in validateUserAndLogin: " + e.getMessage());
            resetLoginButton();
            Toast.makeText(this, "Login failed. Please try again.", Toast.LENGTH_SHORT).show();
        }
    }
    
    private void resetLoginButton() {
        try {
            loginButton.setText("Sign In");
            loginButton.setEnabled(true);
        } catch (Exception e) {
            android.util.Log.e("LoginActivity", "Error resetting login button: " + e.getMessage());
        }
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
            
            // Clear user-specific added foods, calorie data, cache data, and favorites
            AddedFoodManager.clearUserData(this, email);
            CalorieTracker.clearUserData(this, email);
            GeminiCacheManager.clearUserData(this, email);
            FavoritesManager.clearUserData(this, email);
            
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
    
    // Button styling is handled by XML layout and themes
    // Method removed to avoid compilation errors with R.drawable references

} 