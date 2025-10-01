package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import androidx.appcompat.app.AppCompatActivity;

/**
 * Base activity that provides automatic session validation
 * All activities that require authentication should extend this class
 */
public abstract class BaseActivity extends AppCompatActivity {
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        // Record user interaction (opening activity)
        SessionManager.getInstance(this).recordUserInteraction();
        
        // Validate session for all activities that extend this
        if (!SessionManager.getInstance(this).validateSession(this)) {
            return; // Will redirect to login
        }
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        
        // Record user interaction (returning to activity)
        SessionManager.getInstance(this).recordUserInteraction();
        
        // INTERACTION-BASED VALIDATION: Only validate when user is actively using the app
        if (!SessionManager.getInstance(this).validateSession(this)) {
            return; // Will redirect to login
        }
    }
    
    @Override
    protected void onPause() {
        super.onPause();
        
        // Mark user as idle when activity goes to background
        SessionManager.getInstance(this).markUserAsIdle();
    }
    
    @Override
    protected void onStop() {
        super.onStop();
        
        // Mark user as idle when activity stops
        SessionManager.getInstance(this).markUserAsIdle();
    }
    
    @Override
    protected void onDestroy() {
        super.onDestroy();
        
        // Cleanup SessionManager resources to prevent memory leaks
        SessionManager.getInstance(this).cleanup();
    }
    
    /**
     * Override this method to perform additional setup after session validation
     */
    protected abstract void initializeActivity();
    
    /**
     * Call this method when user interacts with the app (button clicks, navigation, etc.)
     * This helps optimize battery usage by only checking session when user is active
     */
    public void onUserInteraction() {
        SessionManager.getInstance(this).recordUserInteraction();
    }
    
    /**
     * Navigate to another activity with session validation
     */
    protected void navigateWithSessionValidation(Class<?> targetActivity) {
        // Record user interaction for session management
        onUserInteraction();
        
        // Validate session before navigation
        SessionManager.getInstance(this).isUserValidAsync(new SessionManager.ValidationCallback() {
            @Override
            public void onValidationResult(boolean isValid) {
                if (isValid) {
                    // Session is valid, proceed with navigation
                    runOnUiThread(() -> {
                        Intent intent = new Intent(BaseActivity.this, targetActivity);
                        startActivity(intent);
                        overridePendingTransition(android.R.anim.fade_in, android.R.anim.fade_out);
                        finish();
                    });
                } else {
                    // Session is invalid, user will be redirected to login
                    Log.d("BaseActivity", "Session invalid during navigation, redirecting to login");
                }
            }
        });
    }
    
    /**
     * Setup common navigation with session validation
     */
    protected void setupCommonNavigation() {
        // Home navigation
        findViewById(R.id.nav_home).setOnClickListener(v -> {
            if (this instanceof MainActivity) {
                // Already on home, just refresh
                ((MainActivity) this).fetchAndDisplayDashboardEvents();
            } else {
                navigateWithSessionValidation(MainActivity.class);
            }
        });

        // Food navigation
        findViewById(R.id.nav_food).setOnClickListener(v -> {
            if (this instanceof FoodActivity) {
                // Already on food page, do nothing
                return;
            }
            navigateWithSessionValidation(FoodActivity.class);
        });

        // Favorites navigation
        findViewById(R.id.nav_favorites).setOnClickListener(v -> {
            if (this instanceof FavoritesActivity) {
                // Already on favorites page, do nothing
                return;
            }
            navigateWithSessionValidation(FavoritesActivity.class);
        });

        // Account navigation
        findViewById(R.id.nav_account).setOnClickListener(v -> {
            if (this instanceof AccountActivity) {
                // Already on account page, do nothing
                return;
            }
            navigateWithSessionValidation(AccountActivity.class);
        });
    }
    
    /**
     * Call this method at the end of onCreate after session validation
     */
    protected void onSessionValidated() {
        initializeActivity();
    }
}
