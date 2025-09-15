package com.example.nutrisaur11;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;
import androidx.appcompat.app.AlertDialog;
import org.json.JSONObject;
import java.util.Map;

public class SessionManager {
    private static final String TAG = "SessionManager";
    private static final String API_BASE_URL = "https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php";
    
    // Battery optimization: Only check database when user interacts with app
    private static final long SESSION_CHECK_INTERVAL = 5 * 60 * 1000; // 5 minutes (reduced for better UX)
    private static final long IDLE_TIMEOUT = 10 * 60 * 1000; // 10 minutes of inactivity
    private static final long CACHE_VALIDITY = 24 * 60 * 60 * 1000; // 24 hours
    
    // Track user interaction
    private long lastUserInteraction = 0;
    private boolean isUserActive = false;
    
    private Context context;
    private static SessionManager instance;
    
    private SessionManager(Context context) {
        this.context = context.getApplicationContext();
    }
    
    public static synchronized SessionManager getInstance(Context context) {
        if (instance == null) {
            instance = new SessionManager(context);
        }
        return instance;
    }
    
    /**
     * Check if user is logged in and exists in database (BATTERY OPTIMIZED)
     */
    public boolean isUserValid() {
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        String email = prefs.getString("current_user_email", null);
        boolean isLoggedIn = prefs.getBoolean("is_logged_in", false);
        
        if (email == null || !isLoggedIn) {
            Log.d(TAG, "User not logged in");
            return false;
        }
        
        // Battery optimization: Check cache first, only hit database if needed
        return isUserValidCached(email);
    }
    
    /**
     * Battery-optimized user validation with interaction-based checking
     */
    private boolean isUserValidCached(String email) {
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        long currentTime = System.currentTimeMillis();
        
        // Check if user is idle (no interaction for 10+ minutes)
        if (currentTime - lastUserInteraction > IDLE_TIMEOUT) {
            Log.d(TAG, "User is idle, skipping session validation");
            return true; // Assume valid when idle to avoid unnecessary checks
        }
        
        // Check if we have a recent validation result
        long lastCheck = prefs.getLong("session_last_check_" + email, 0);
        
        // If we checked recently (within 5 minutes) and user is active, use cached result
        if (currentTime - lastCheck < SESSION_CHECK_INTERVAL && isUserActive) {
            boolean cachedResult = prefs.getBoolean("session_is_valid_" + email, false);
            Log.d(TAG, "Using cached session validation result: " + cachedResult);
            return cachedResult;
        }
        
        // Only check database if user is active and enough time has passed
        if (isUserActive) {
            Log.d(TAG, "User is active, validating with database");
            boolean isValid = checkUserExistsInDatabase(email);
            
            // Cache the result
            prefs.edit()
                .putBoolean("session_is_valid_" + email, isValid)
                .putLong("session_last_check_" + email, currentTime)
                .apply();
                
            return isValid;
        }
        
        // If user is not active, return cached result
        return prefs.getBoolean("session_is_valid_" + email, true);
    }
    
    /**
     * Record user interaction (call this when user navigates, presses buttons, etc.)
     */
    public void recordUserInteraction() {
        lastUserInteraction = System.currentTimeMillis();
        isUserActive = true;
        Log.d(TAG, "User interaction recorded at: " + lastUserInteraction);
    }
    
    /**
     * Mark user as idle (call this when app goes to background or user stops interacting)
     */
    public void markUserAsIdle() {
        isUserActive = false;
        Log.d(TAG, "User marked as idle");
    }
    
    /**
     * Check if user exists in community_users database
     */
    private boolean checkUserExistsInDatabase(String email) {
        try {
            CommunityUserManager userManager = new CommunityUserManager(context);
            Map<String, String> userData = userManager.getCurrentUserDataFromDatabase();
            
            // If we get empty data, user doesn't exist
            if (userData.isEmpty()) {
                Log.d(TAG, "User not found in database: " + email);
                return false;
            }
            
            // Check if we have essential user data
            boolean hasEssentialData = userData.containsKey("name") || userData.containsKey("email");
            if (!hasEssentialData) {
                Log.d(TAG, "User data incomplete in database: " + email);
                return false;
            }
            
            Log.d(TAG, "User exists and is valid: " + email);
            return true;
            
        } catch (Exception e) {
            Log.e(TAG, "Error checking user existence: " + e.getMessage());
            return false;
        }
    }
    
    /**
     * Validate session and handle invalid sessions
     */
    public boolean validateSession(Activity activity) {
        if (!isUserValid()) {
            Log.d(TAG, "Session invalid, redirecting to login");
            handleInvalidSession(activity);
            return false;
        }
        return true;
    }
    
    /**
     * Handle invalid session - show dialog and redirect to login
     */
    private void handleInvalidSession(Activity activity) {
        if (activity == null || activity.isFinishing()) {
            return;
        }
        
        activity.runOnUiThread(() -> {
            new AlertDialog.Builder(activity)
                .setTitle("Session Expired")
                .setMessage("Your account is no longer available or has been deleted from the database. Please log in again.")
                .setCancelable(false)
                .setPositiveButton("OK", (dialog, which) -> {
                    clearUserSession();
                    redirectToLogin(activity);
                })
                .show();
        });
    }
    
    /**
     * Clear user session data
     */
    public void clearUserSession() {
        try {
            SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
            String currentUserEmail = prefs.getString("current_user_email", null);
            
            // Clear user-specific data
            if (currentUserEmail != null) {
                AddedFoodManager.clearUserData(context, currentUserEmail);
                CalorieTracker.clearUserData(context, currentUserEmail);
                GeminiCacheManager.clearUserData(context, currentUserEmail);
                FavoritesManager.clearUserData(context, currentUserEmail);
                
                // Clear profile cache
                CommunityUserManager userManager = new CommunityUserManager(context);
                userManager.clearUserCache(currentUserEmail);
                
                Log.d(TAG, "Cleared user-specific data for: " + currentUserEmail);
            }
            
            // Clear all user data from main preferences
            SharedPreferences.Editor editor = prefs.edit();
            editor.clear();
            editor.putBoolean("is_logged_in", false);
            editor.apply();
            
            Log.d(TAG, "User session cleared");
            
        } catch (Exception e) {
            Log.e(TAG, "Error clearing user session: " + e.getMessage());
        }
    }
    
    /**
     * Redirect to login activity
     */
    private void redirectToLogin(Activity activity) {
        try {
            Intent intent = new Intent(activity, MainActivity.class);
            intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
            activity.startActivity(intent);
            activity.finish();
        } catch (Exception e) {
            Log.e(TAG, "Error redirecting to login: " + e.getMessage());
        }
    }
    
    /**
     * Force logout with custom message
     */
    public void forceLogout(Activity activity, String message) {
        if (activity == null || activity.isFinishing()) {
            return;
        }
        
        activity.runOnUiThread(() -> {
            new AlertDialog.Builder(activity)
                .setTitle("Account Issue")
                .setMessage(message)
                .setCancelable(false)
                .setPositiveButton("OK", (dialog, which) -> {
                    clearUserSession();
                    redirectToLogin(activity);
                })
                .show();
        });
    }
    
    /**
     * Check if user is logged in (basic check)
     */
    public boolean isLoggedIn() {
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        String email = prefs.getString("current_user_email", null);
        boolean isLoggedIn = prefs.getBoolean("is_logged_in", false);
        return email != null && isLoggedIn;
    }
    
    /**
     * Get current user email
     */
    public String getCurrentUserEmail() {
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        return prefs.getString("current_user_email", null);
    }
    
    /**
     * Force refresh session validation (use sparingly - only when needed)
     * This bypasses the cache and immediately checks the database
     */
    public void forceRefreshSession() {
        String email = getCurrentUserEmail();
        if (email != null) {
            Log.d(TAG, "Force refreshing session validation for: " + email);
            boolean isValid = checkUserExistsInDatabase(email);
            
            SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
            prefs.edit()
                .putBoolean("session_is_valid_" + email, isValid)
                .putLong("session_last_check_" + email, System.currentTimeMillis())
                .apply();
        }
    }
    
    /**
     * Clear session cache (useful for logout or when user data changes)
     */
    public void clearSessionCache() {
        String email = getCurrentUserEmail();
        if (email != null) {
            SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
            prefs.edit()
                .remove("session_is_valid_" + email)
                .remove("session_last_check_" + email)
                .apply();
            Log.d(TAG, "Session cache cleared for: " + email);
        }
    }
}
