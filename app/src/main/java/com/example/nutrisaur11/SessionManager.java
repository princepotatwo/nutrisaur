package com.example.nutrisaur11;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;
import androidx.appcompat.app.AlertDialog;
import org.json.JSONObject;
import java.util.Map;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.CountDownLatch;
import java.util.concurrent.TimeUnit;
import android.os.Handler;
import android.os.Looper;

public class SessionManager {
    private static final String TAG = "SessionManager";
    private static final String API_BASE_URL = "https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php";
    
    // Real-time validation: Check more frequently for immediate detection
    private static final long SESSION_CHECK_INTERVAL = 3 * 1000; // 3 seconds for real-time detection
    private static final long IDLE_TIMEOUT = 10 * 60 * 1000; // 10 minutes of inactivity
    private static final long CACHE_VALIDITY = 24 * 60 * 60 * 1000; // 24 hours
    
    // Connection quality thresholds
    private static final long SLOW_CONNECTION_THRESHOLD = 5000; // 5 seconds
    private static final long VERY_SLOW_CONNECTION_THRESHOLD = 10000; // 10 seconds
    
    // Track user interaction
    private long lastUserInteraction = 0;
    private boolean isUserActive = false;
    
    private Context context;
    private static SessionManager instance;
    private ExecutorService executorService;
    private Handler mainHandler;
    private Handler validationHandler;
    private Runnable validationRunnable;
    
    private SessionManager(Context context) {
        this.context = context.getApplicationContext();
        this.executorService = Executors.newSingleThreadExecutor();
        this.mainHandler = new Handler(Looper.getMainLooper());
        this.validationHandler = new Handler(Looper.getMainLooper());
        startPeriodicValidation();
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
     * Async version of isUserValid for background validation
     */
    public void isUserValidAsync(ValidationCallback callback) {
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        String email = prefs.getString("current_user_email", null);
        boolean isLoggedIn = prefs.getBoolean("is_logged_in", false);
        
        if (email == null || !isLoggedIn) {
            Log.d(TAG, "User not logged in");
            if (callback != null) {
                mainHandler.post(() -> callback.onValidationResult(false));
            }
            return;
        }
        
        // Check cache first
        boolean cachedResult = isUserValidCached(email);
        if (cachedResult) {
            if (callback != null) {
                mainHandler.post(() -> callback.onValidationResult(true));
            }
            return;
        }
        
        // If cache is invalid, check database in background
        executorService.execute(() -> {
            boolean isValid = checkUserExistsInDatabase(email);
            if (callback != null) {
                mainHandler.post(() -> callback.onValidationResult(isValid));
            }
        });
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
     * Check if user exists in community_users database with timeout handling
     */
    public boolean checkUserExistsInDatabase(String email) {
        try {
            Log.d(TAG, "Checking user existence in database: " + email);
            long startTime = System.currentTimeMillis();
            
            CommunityUserManager userManager = new CommunityUserManager(context);
            
            // Use a synchronous approach for session validation
            // We need to wait for the result to determine if user exists
            final boolean[] userExists = {false};
            final CountDownLatch latch = new CountDownLatch(1);
            
            userManager.getCurrentUserDataFromDatabaseAsync(new CommunityUserManager.UserDataCallback() {
                @Override
                public void onUserDataReceived(Map<String, String> userData) {
                    // Check if user is archived
                    if (userData.containsKey("status") && "0".equals(userData.get("status"))) {
                        Log.d(TAG, "User is archived, invalidating session");
                        userExists[0] = false;
                    } else {
                        // Check if we have essential user data
                        boolean hasEssentialData = userData.containsKey("name") || userData.containsKey("email");
                        userExists[0] = !userData.isEmpty() && hasEssentialData;
                    }
                    latch.countDown();
                }
            });
            
            // Wait for the result (max 5 seconds)
            try {
                latch.await(5, TimeUnit.SECONDS);
            } catch (InterruptedException e) {
                Log.e(TAG, "Interrupted while waiting for user data: " + e.getMessage());
                return false;
            }
            
            long duration = System.currentTimeMillis() - startTime;
            Log.d(TAG, "Database check completed in " + duration + "ms");
            
            // Store connection duration for cache optimization
            SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
            prefs.edit().putLong("last_connection_duration_" + email, duration).apply();
            
            // Adjust validation frequency based on connection quality
            if (duration > VERY_SLOW_CONNECTION_THRESHOLD) {
                Log.d(TAG, "Very slow connection detected (" + duration + "ms), reducing validation frequency");
                // For very slow connections, we'll be more lenient with validation
            } else if (duration > SLOW_CONNECTION_THRESHOLD) {
                Log.d(TAG, "Slow connection detected (" + duration + "ms), monitoring connection quality");
            }
            
            if (!userExists[0]) {
                Log.d(TAG, "User not found in database: " + email);
                return false;
            }
            
            Log.d(TAG, "User exists and is valid: " + email);
            return true;
            
        } catch (Exception e) {
            Log.e(TAG, "Error checking user existence: " + e.getMessage());
            
            // If it's a timeout or network error, don't immediately fail
            if (e.getMessage() != null && 
                (e.getMessage().contains("timeout") || 
                 e.getMessage().contains("network") ||
                 e.getMessage().contains("connection"))) {
                Log.d(TAG, "Network timeout detected, treating as temporary failure");
                // Return true to avoid unnecessary logout on slow connections
                return true;
            }
            
            return false;
        }
    }
    
    /**
     * Validate session and handle invalid sessions
     */
    public boolean validateSession(Activity activity) {
        Log.d(TAG, "=== REAL-TIME SESSION VALIDATION ===");
        
        // For immediate validation, use cached result only
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        String email = prefs.getString("current_user_email", null);
        boolean isLoggedIn = prefs.getBoolean("is_logged_in", false);
        
        if (email == null || !isLoggedIn) {
            Log.d(TAG, "User not logged in");
            handleInvalidSession(activity);
            return false;
        }
        
        // Check if we have a recent valid session in cache
        long lastCheck = prefs.getLong("session_last_check_" + email, 0);
        long currentTime = System.currentTimeMillis();
        
        // For slow connections, extend cache validity to reduce network calls
        long cacheInterval = SESSION_CHECK_INTERVAL;
        long lastConnectionTime = prefs.getLong("last_connection_time_" + email, 0);
        if (lastConnectionTime > 0 && (currentTime - lastConnectionTime) < 60000) { // If last connection was slow within 1 minute
            long lastConnectionDuration = prefs.getLong("last_connection_duration_" + email, 0);
            if (lastConnectionDuration > SLOW_CONNECTION_THRESHOLD) {
                cacheInterval = SESSION_CHECK_INTERVAL * 3; // Extend cache to 9 seconds for slow connections
                Log.d(TAG, "Extending cache validity due to slow connection history");
            }
        }
        
        boolean hasRecentValidSession = (currentTime - lastCheck < cacheInterval) && 
                                       prefs.getBoolean("session_is_valid_" + email, false);
        
        if (hasRecentValidSession) {
            Log.d(TAG, "Using cached valid session");
            return true;
        }
        
        // REAL-TIME VALIDATION: Check database immediately for critical validation
        Log.d(TAG, "No recent valid session found, performing real-time database check");
        
        // First check if device is online
        if (!isNetworkAvailable()) {
            Log.d(TAG, "Device is offline, showing offline dialog");
            handleOfflineSession(activity);
            return false;
        }
        
        boolean isValid = checkUserExistsInDatabase(email);
        
        // Store connection timing for future cache decisions
        long connectionDuration = prefs.getLong("last_connection_duration_" + email, 0);
        prefs.edit()
            .putBoolean("session_is_valid_" + email, isValid)
            .putLong("session_last_check_" + email, currentTime)
            .putLong("last_connection_time_" + email, currentTime)
            .putLong("last_connection_duration_" + email, connectionDuration)
            .apply();
        
        if (!isValid) {
            Log.d(TAG, "Real-time validation failed, user not found in database");
            handleInvalidSession(activity);
            return false;
        }
        
        Log.d(TAG, "Real-time validation successful");
        return true;
    }
    
    /**
     * Force real-time validation - bypasses cache and checks database immediately
     */
    public boolean forceRealTimeValidation(Activity activity) {
        Log.d(TAG, "=== FORCE REAL-TIME VALIDATION ===");
        
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        String email = prefs.getString("current_user_email", null);
        boolean isLoggedIn = prefs.getBoolean("is_logged_in", false);
        
        if (email == null || !isLoggedIn) {
            Log.d(TAG, "User not logged in");
            handleInvalidSession(activity);
            return false;
        }
        
        // First check if device is online
        if (!isNetworkAvailable()) {
            Log.d(TAG, "Device is offline, showing offline dialog");
            handleOfflineSession(activity);
            return false;
        }
        
        // Force database check - bypass cache
        boolean isValid = checkUserExistsInDatabase(email);
        
        // Update cache with result
        long currentTime = System.currentTimeMillis();
        prefs.edit()
            .putBoolean("session_is_valid_" + email, isValid)
            .putLong("session_last_check_" + email, currentTime)
            .apply();
        
        if (!isValid) {
            Log.d(TAG, "Force validation failed, user not found in database");
            handleInvalidSession(activity);
            return false;
        }
        
        Log.d(TAG, "Force validation successful");
        return true;
    }
    
    /**
     * Async session validation
     */
    private void validateSessionAsync(Activity activity) {
        isUserValidAsync(new ValidationCallback() {
            @Override
            public void onValidationResult(boolean isValid) {
                if (!isValid) {
                    Log.d(TAG, "Background validation failed, redirecting to login");
                    handleInvalidSession(activity);
                } else {
                    Log.d(TAG, "Background validation successful");
                }
            }
        });
    }
    
    /**
     * Handle invalid session - show dialog and redirect to login
     */
    private void handleInvalidSession(Activity activity) {
        Log.d(TAG, "=== HANDLING INVALID SESSION ===");
        Log.d(TAG, "Activity: " + (activity != null ? activity.getClass().getSimpleName() : "null"));
        Log.d(TAG, "Activity finishing: " + (activity != null ? activity.isFinishing() : "null"));
        
        if (activity == null || activity.isFinishing()) {
            Log.d(TAG, "Activity is null or finishing, skipping dialog");
            return;
        }
        
        Log.d(TAG, "Showing logout dialog");
        activity.runOnUiThread(() -> {
            new AlertDialog.Builder(activity)
                .setTitle("Account Not Found")
                .setMessage("Your account is no longer available or has been deleted from the database. Please log in again.")
                .setCancelable(false)
                .setPositiveButton("OK", (dialog, which) -> {
                    Log.d(TAG, "User clicked OK on logout dialog");
                    clearUserSession();
                    redirectToLogin(activity);
                })
                .show();
        });
    }
    
    /**
     * Handle offline session - show dialog and redirect to login
     */
    private void handleOfflineSession(Activity activity) {
        Log.d(TAG, "=== HANDLING OFFLINE SESSION ===");
        Log.d(TAG, "Activity: " + (activity != null ? activity.getClass().getSimpleName() : "null"));
        Log.d(TAG, "Activity finishing: " + (activity != null ? activity.isFinishing() : "null"));
        
        if (activity == null || activity.isFinishing()) {
            Log.d(TAG, "Activity is null or finishing, skipping dialog");
            return;
        }
        
        Log.d(TAG, "Showing offline dialog");
        activity.runOnUiThread(() -> {
            new AlertDialog.Builder(activity)
                .setTitle("You went offline")
                .setMessage("No internet connection detected. Please check your network and try again.")
                .setCancelable(false)
                .setPositiveButton("OK", (dialog, which) -> {
                    Log.d(TAG, "User clicked OK on offline dialog");
                    clearUserSession();
                    redirectToLogin(activity);
                })
                .show();
        });
    }
    
    /**
     * Check if network is available
     */
    private boolean isNetworkAvailable() {
        try {
            android.net.ConnectivityManager connectivityManager = 
                (android.net.ConnectivityManager) context.getSystemService(android.content.Context.CONNECTIVITY_SERVICE);
            
            if (connectivityManager != null) {
                // Use modern API for Android 6.0+ (API 23+)
                if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.M) {
                    android.net.Network activeNetwork = connectivityManager.getActiveNetwork();
                    if (activeNetwork != null) {
                        android.net.NetworkCapabilities capabilities = connectivityManager.getNetworkCapabilities(activeNetwork);
                        return capabilities != null && (
                            capabilities.hasTransport(android.net.NetworkCapabilities.TRANSPORT_WIFI) ||
                            capabilities.hasTransport(android.net.NetworkCapabilities.TRANSPORT_CELLULAR) ||
                            capabilities.hasTransport(android.net.NetworkCapabilities.TRANSPORT_ETHERNET)
                        );
                    }
                } else {
                    // Fallback for older Android versions
                    android.net.NetworkInfo activeNetwork = connectivityManager.getActiveNetworkInfo();
                    return activeNetwork != null && activeNetwork.isConnected();
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error checking network availability: " + e.getMessage());
        }
        return false;
    }
    
    /**
     * Start periodic validation to check session every 30 seconds
     */
    private void startPeriodicValidation() {
        validationRunnable = new Runnable() {
            @Override
            public void run() {
                Log.d(TAG, "=== PERIODIC SESSION VALIDATION ===");
                
                // Only validate if user is active and logged in
                if (isUserActive && isUserValid()) {
                    // Check if user is still valid in database
                    SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
                    String email = prefs.getString("current_user_email", null);
                    
                    if (email != null) {
                        // Check network first
                        if (!isNetworkAvailable()) {
                            Log.d(TAG, "Periodic validation: Device offline detected");
                            // Don't show dialog here, let the next activity resume handle it
                            return;
                        }
                        
                        // Check database
                        boolean isValid = checkUserExistsInDatabase(email);
                        if (!isValid) {
                            Log.d(TAG, "Periodic validation: User not found in database");
                            // Clear the cache to force validation on next activity
                            prefs.edit()
                                .putBoolean("session_is_valid_" + email, false)
                                .putLong("session_last_check_" + email, 0)
                                .apply();
                        }
                    }
                }
                
                // Schedule next validation in 3 seconds
                validationHandler.postDelayed(this, 3000);
            }
        };
        
        // Start the first validation after 3 seconds
        validationHandler.postDelayed(validationRunnable, 3000);
    }
    
    /**
     * Stop periodic validation
     */
    public void stopPeriodicValidation() {
        if (validationHandler != null && validationRunnable != null) {
            validationHandler.removeCallbacks(validationRunnable);
        }
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
                Log.d(TAG, "=== SESSION CLEARING DEBUG START ===");
                Log.d(TAG, "Current user email: " + currentUserEmail);
                
                Log.d(TAG, "Step 1: Clearing AddedFoodManager data");
                AddedFoodManager.clearUserData(context, currentUserEmail);
                Log.d(TAG, "Step 1.1: AddedFoodManager cleared");
                
                Log.d(TAG, "Step 2: Clearing CalorieTracker data");
                CalorieTracker.clearUserData(context, currentUserEmail);
                Log.d(TAG, "Step 2.1: CalorieTracker cleared");
                
                Log.d(TAG, "Step 3: Clearing GeminiCacheManager data");
                GeminiCacheManager.clearUserData(context, currentUserEmail);
                Log.d(TAG, "Step 3.1: GeminiCacheManager cleared");
                
                Log.d(TAG, "Step 4: Clearing FavoritesManager data");
                FavoritesManager.clearUserData(context, currentUserEmail);
                Log.d(TAG, "Step 4.1: FavoritesManager cleared");
                
                Log.d(TAG, "Step 5: Creating FCMTokenManager instance");
                FCMTokenManager fcmManager = new FCMTokenManager(context);
                Log.d(TAG, "Step 5.1: FCMTokenManager created");
                
                Log.d(TAG, "Step 6: Calling clearFCMTokenForUser");
                fcmManager.clearFCMTokenForUser(currentUserEmail);
                Log.d(TAG, "Step 6.1: clearFCMTokenForUser call completed");
                
                Log.d(TAG, "Step 7: Clearing CommunityUserManager cache");
                CommunityUserManager userManager = new CommunityUserManager(context);
                userManager.clearUserCache(currentUserEmail);
                Log.d(TAG, "Step 7.1: CommunityUserManager cache cleared");
                
                Log.d(TAG, "SUCCESS: Cleared user-specific data for: " + currentUserEmail);
                Log.d(TAG, "=== SESSION CLEARING DEBUG END ===");
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
            Intent intent = new Intent(activity, FoodActivity.class);
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
     * Mark session as valid (useful after successful signup or login)
     */
    public void markSessionAsValid() {
        String email = getCurrentUserEmail();
        if (email != null) {
            Log.d(TAG, "Marking session as valid for: " + email);
            SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
            prefs.edit()
                .putBoolean("session_is_valid_" + email, true)
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
    
    /**
     * Cleanup resources
     */
    public void cleanup() {
        if (executorService != null && !executorService.isShutdown()) {
            executorService.shutdown();
        }
    }
    
    /**
     * Callback interface for async validation
     */
    public interface ValidationCallback {
        void onValidationResult(boolean isValid);
    }
}
