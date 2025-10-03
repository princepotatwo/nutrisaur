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
     * Navigate to another activity with session validation and community user cache clearing
     */
    protected void navigateWithSessionValidation(Class<?> targetActivity) {
        // Record user interaction for session management
        onUserInteraction();
        
        // Clear community user cache and validate archived status before navigation
        clearCommunityUserCacheAndNavigate(targetActivity);
    }
    
    /**
     * Clear community user cache and navigate to target activity
     */
    private void clearCommunityUserCacheAndNavigate(Class<?> targetActivity) {
        Log.d("BaseActivity", "Clearing community user cache before navigation...");
        
        // Get current user email
        CommunityUserManager userManager = new CommunityUserManager(this);
        String email = userManager.getCurrentUserEmail();
        
        if (email == null || email.isEmpty()) {
            Log.d("BaseActivity", "No email found, proceeding with normal navigation");
            proceedWithNavigation(targetActivity);
            return;
        }
        
        // Call the clear_community_user_cache API
        SessionManager.getInstance(this).clearCommunityUserCache(email, new SessionManager.CommunityUserCacheCallback() {
            @Override
            public void onCacheCleared(boolean success, String message, boolean isArchived, Map<String, String> freshUserData) {
                if (isArchived) {
                    // User is archived, show logout modal
                    Log.d("BaseActivity", "User is archived: " + message);
                    showArchivedAccountModal(message);
                } else if (success && freshUserData != null) {
                    // Cache cleared successfully, update local cache with fresh data
                    Log.d("BaseActivity", "Community user cache cleared successfully, updating local cache");
                    userManager.updateCacheWithFreshData(email, freshUserData);
                    proceedWithNavigation(targetActivity);
                } else if (success) {
                    // Cache cleared successfully but no fresh data
                    Log.d("BaseActivity", "Community user cache cleared successfully");
                    proceedWithNavigation(targetActivity);
                } else {
                    // API call failed, but proceed with navigation (don't block user)
                    Log.w("BaseActivity", "Failed to clear cache: " + message + ", proceeding anyway");
                    proceedWithNavigation(targetActivity);
                }
            }
        });
    }
    
    /**
     * Proceed with navigation to target activity
     */
    private void proceedWithNavigation(Class<?> targetActivity) {
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
     * Show archived account modal
     */
    private void showArchivedAccountModal(String message) {
        runOnUiThread(() -> {
            new androidx.appcompat.app.AlertDialog.Builder(this)
                    .setTitle("Account Archived")
                    .setMessage(message)
                    .setCancelable(false)
                    .setPositiveButton("OK", (dialog, which) -> {
                        // Logout user and redirect to login
                        CommunityUserManager userManager = new CommunityUserManager(this);
                        userManager.logout();
                        
                        Intent intent = new Intent(this, LoginActivity.class);
                        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                        startActivity(intent);
                        finish();
                    })
                    .show();
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
