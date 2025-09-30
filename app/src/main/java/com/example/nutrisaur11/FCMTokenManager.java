package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import android.os.Handler;
import android.os.Looper;
import android.content.Intent;
import android.app.Activity;
import com.example.nutrisaur11.UserPreferencesDbHelper;

import com.google.firebase.messaging.FirebaseMessaging;
import com.google.firebase.messaging.FirebaseMessagingService;

import org.json.JSONObject;
import org.json.JSONArray;
import org.json.JSONException;

import java.io.IOException;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.CountDownLatch;

import okhttp3.MediaType;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;

public class FCMTokenManager {
    private static final String TAG = "FCMTokenManager";
    private static final String PREFS_NAME = "fcm_prefs";
    private static final String KEY_FCM_TOKEN = "fcm_token";
    private static final String KEY_LAST_REGISTRATION = "last_registration";
    private static final String KEY_USER_EMAIL = "user_email";
    private static final String KEY_USER_BARANGAY = "user_barangay";
    // Device ID tracking removed to avoid database changes
    
    // Server endpoint for FCM token registration - using working database update API
    private static final String SERVER_URL = Constants.API_BASE_URL + "api/DatabaseAPI.php?action=update";
    
    // Registration intervals
    private static final long REGISTRATION_INTERVAL = TimeUnit.HOURS.toMillis(24); // 24 hours (daily sync)
    private static final long RETRY_INTERVAL = TimeUnit.MINUTES.toMillis(30); // 30 minutes
    
    private Context context;
    private SharedPreferences prefs;
    private Handler handler;
    private OkHttpClient httpClient;
    private boolean isRegistrationInProgress = false;
    
    public FCMTokenManager(Context context) {
        this.context = context;
        this.prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
        this.handler = new Handler(Looper.getMainLooper());
        this.httpClient = new OkHttpClient.Builder()
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build();
        
        // Device ID generation removed to avoid database changes
    }
    
    // Device ID methods removed to avoid database changes

    /**
     * Check with server if the given token exists
     */
    private boolean tokenExistsOnServer(String token) {
        try {
            // Use database select API to check if token exists
            JSONObject requestData = new JSONObject();
            requestData.put("table", "community_users");
            requestData.put("where", "fcm_token = :fcm_token");
            requestData.put("params", new JSONObject().put("fcm_token", token));
            
            RequestBody body = RequestBody.create(
                requestData.toString(), 
                MediaType.parse("application/json; charset=utf-8")
            );
            
            Request request = new Request.Builder()
                .url(Constants.API_BASE_URL + "api/DatabaseAPI.php?action=select")
                .post(body)
                .addHeader("Content-Type", "application/json")
                .build();
                
            try (Response response = httpClient.newCall(request).execute()) {
                if (!response.isSuccessful()) {
                    return false;
                }
                String responseBody = response.body() != null ? response.body().string() : "";
                JSONObject json = new JSONObject(responseBody);
                if (json.optBoolean("success", false)) {
                    JSONArray data = json.optJSONArray("data");
                    return data != null && data.length() > 0;
                }
                return false;
            }
        } catch (Exception e) {
            Log.w(TAG, "Failed to check token existence on server: " + e.getMessage());
            return false;
        }
    }
    
    /**
     * Initialize FCM token registration
     * Call this when the app starts or user logs in
     */
    public void initialize() {
        Log.d(TAG, "Initializing FCM token manager");
        
        // Get current FCM token
        FirebaseMessaging.getInstance().getToken()
            .addOnCompleteListener(task -> {
                if (task.isSuccessful() && task.getResult() != null) {
                    String token = task.getResult();
                    Log.d(TAG, "FCM token obtained: " + token.substring(0, Math.min(50, token.length())) + "...");
                    
                    // Check if we need to register this token
                    if (shouldRegisterToken(token)) {
                        Log.d(TAG, "Token needs registration, sending to server");
                        registerTokenWithServer(token);
                    } else {
                        Log.d(TAG, "Token already registered and up-to-date, skipping server call");
                    }
                } else {
                    Log.e(TAG, "Failed to get FCM token", task.getException());
                }
            });
        
        // Schedule periodic token refresh (daily sync)
        scheduleTokenRefresh();
    }
    
    /**
     * Initialize FCM token registration with user email
     * Call this when the app starts and user is logged in
     */
    public void initializeWithUser(String userEmail) {
        Log.d(TAG, "Initializing FCM token manager with user: " + userEmail);
        
        // Save user email
        prefs.edit().putString(KEY_USER_EMAIL, userEmail).apply();
        
        // Get current FCM token
        FirebaseMessaging.getInstance().getToken()
            .addOnCompleteListener(task -> {
                if (task.isSuccessful() && task.getResult() != null) {
                    String token = task.getResult();
                    Log.d(TAG, "FCM token obtained: " + token.substring(0, Math.min(50, token.length())) + "...");
                    
                    // Decide registration in background to avoid blocking main thread
                    new Thread(() -> {
                        try {
                            boolean shouldRegister = shouldRegisterToken(token);
                            if (!shouldRegister) {
                                // Double-check with server in case database was cleared
                                boolean existsOnServer = tokenExistsOnServer(token);
                                if (!existsOnServer) {
                                    Log.d(TAG, "Server reports token is missing; will register now");
                                    registerTokenWithServer(token);
                                } else {
                                    Log.d(TAG, "Token already registered and up-to-date on server, skipping");
                                }
                            } else {
                                Log.d(TAG, "Token needs registration based on local checks; registering");
                                registerTokenWithServer(token);
                            }
                        } catch (Exception e) {
                            Log.e(TAG, "Error deciding token registration: " + e.getMessage());
                        }
                    }).start();
                } else {
                    Log.e(TAG, "Failed to get FCM token", task.getException());
                }
            });
        
        // Schedule periodic token refresh
        scheduleTokenRefresh();
    }
    
    /**
     * Register FCM token after user screening or profile update
     * Call this when user completes screening or updates their location
     */
    public void registerTokenAfterScreening(String userEmail, String userBarangay) {
        Log.d(TAG, "Registering token after screening for user: " + userEmail + " in " + userBarangay);
        
        // Save user info
        prefs.edit()
            .putString(KEY_USER_EMAIL, userEmail)
            .putString(KEY_USER_BARANGAY, userBarangay)
            .apply();
        
        // Get current token and register
        FirebaseMessaging.getInstance().getToken()
            .addOnCompleteListener(task -> {
                if (task.isSuccessful() && task.getResult() != null) {
                    String token = task.getResult();
                    Log.d(TAG, "FCM token obtained for screening registration: " + token.substring(0, Math.min(50, token.length())) + "...");
                    registerTokenWithServer(token, userEmail, userBarangay);
                } else {
                    Log.e(TAG, "Failed to get FCM token for screening registration", task.getException());
                }
            });
    }
    
    /**
     * Update user location and refresh FCM token
     * Call this when user changes their location
     */
    public void updateUserLocation(String userEmail, String newBarangay) {
        Log.d(TAG, "Updating user location for " + userEmail + " to " + newBarangay);
        
        // Update stored barangay
        prefs.edit().putString(KEY_USER_BARANGAY, newBarangay).apply();
        
        // Force refresh token to update server with new location
        forceTokenRefresh();
    }
    
    /**
     * Check if we should register the token
     */
    private boolean shouldRegisterToken(String newToken) {
        String storedToken = prefs.getString(KEY_FCM_TOKEN, "");
        long lastRegistration = prefs.getLong(KEY_LAST_REGISTRATION, 0);
        long currentTime = System.currentTimeMillis();
        
        // Only register if:
        // 1. Token is completely different from stored token (Firebase generated new token)
        // 2. No token was ever stored (first time)
        // 3. Last registration was more than 24 hours ago (daily refresh for server sync)
        
        if (storedToken.isEmpty()) {
            Log.d(TAG, "No stored token found, will register");
            return true;
        }
        
        if (!newToken.equals(storedToken)) {
            Log.d(TAG, "Token changed from Firebase, will register new token");
            return true;
        }
        
        // Only refresh every 24 hours for server sync, not every 6 hours
        long dailyInterval = TimeUnit.HOURS.toMillis(24);
        if ((currentTime - lastRegistration) > dailyInterval) {
            Log.d(TAG, "Daily refresh interval reached, will sync with server");
            return true;
        }
        
        Log.d(TAG, "Token is the same and recently registered, skipping registration");
        return false;
    }
    
    /**
     * Public method to check if token registration is needed
     * Used by MyFirebaseMessagingService to determine if onNewToken should trigger registration
     */
    public boolean isTokenRegistrationNeeded() {
        String storedToken = prefs.getString(KEY_FCM_TOKEN, "");
        long lastRegistration = prefs.getLong(KEY_LAST_REGISTRATION, 0);
        long currentTime = System.currentTimeMillis();
        
        // If no token stored, registration is needed
        if (storedToken.isEmpty()) {
            Log.d(TAG, "isTokenRegistrationNeeded: No stored token, registration needed");
            return true;
        }
        
        // If last registration was more than 24 hours ago, registration is needed for daily sync
        long dailyInterval = TimeUnit.HOURS.toMillis(24);
        if ((currentTime - lastRegistration) > dailyInterval) {
            Log.d(TAG, "isTokenRegistrationNeeded: Daily sync interval reached, registration needed");
            return true;
        }
        
        Log.d(TAG, "isTokenRegistrationNeeded: Token recently registered, no registration needed");
        return false;
    }
    
    /**
     * Register token with server
     */
    private void registerTokenWithServer(String token) {
        String userEmail = prefs.getString(KEY_USER_EMAIL, "");
        String userBarangay = prefs.getString(KEY_USER_BARANGAY, "");
        
        // If no barangay in preferences, try to fetch from database
        if (userBarangay.isEmpty() && !userEmail.isEmpty()) {
            userBarangay = fetchBarangayFromDatabase(userEmail);
            if (!userBarangay.isEmpty()) {
                // Save the fetched barangay
                prefs.edit().putString(KEY_USER_BARANGAY, userBarangay).apply();
                Log.d(TAG, "Fetched barangay from database: " + userBarangay);
            } else {
                Log.w(TAG, "No barangay found for user " + userEmail + ". FCM token will be registered without location data.");
                Log.w(TAG, "User should complete screening to enable location-based notifications.");
            }
        }
        
        registerTokenWithServer(token, userEmail, userBarangay);
    }
    
    /**
     * Register token with server with user info
     */
    private void registerTokenWithServer(String token, String userEmail, String userBarangay) {
        if (isRegistrationInProgress) {
            Log.d(TAG, "Registration already in progress, skipping");
            return;
        }
        
        isRegistrationInProgress = true;
        
        try {
            // Update existing user with FCM token (no status/device_id columns needed)
            JSONObject data = new JSONObject();
            data.put("fcm_token", token);
            data.put("barangay", userBarangay);
            
            JSONObject requestData = new JSONObject();
            requestData.put("table", "community_users");
            requestData.put("data", data);
            requestData.put("where", "email = ?");
            requestData.put("params", new JSONArray().put(userEmail));
            
            RequestBody body = RequestBody.create(
                requestData.toString(), 
                MediaType.parse("application/json; charset=utf-8")
            );
            
            Request request = new Request.Builder()
                .url(SERVER_URL)
                .post(body)
                .addHeader("Content-Type", "application/json")
                .build();
            
            // Execute request in background
            new Thread(() -> {
                try {
                    Response response = httpClient.newCall(request).execute();
                    String responseBody = response.body() != null ? response.body().string() : "";
                    
                    if (response.isSuccessful()) {
                        Log.d(TAG, "FCM token registered successfully: " + responseBody);
                        
                        // Save successful registration
                        handler.post(() -> {
                            prefs.edit()
                                .putString(KEY_FCM_TOKEN, token)
                                .putLong(KEY_LAST_REGISTRATION, System.currentTimeMillis())
                                .apply();
                            
                            isRegistrationInProgress = false;
                        });
                        
                    } else {
                        Log.e(TAG, "Failed to register FCM token. HTTP " + response.code() + ": " + responseBody);
                        
                        // Schedule retry
                        handler.post(() -> {
                            isRegistrationInProgress = false;
                            scheduleRetry();
                        });
                    }
                    
                } catch (IOException e) {
                    Log.e(TAG, "Network error registering FCM token", e);
                    
                    // Schedule retry
                    handler.post(() -> {
                        isRegistrationInProgress = false;
                        scheduleRetry();
                    });
                }
            }).start();
            
        } catch (JSONException e) {
            Log.e(TAG, "Error creating request JSON", e);
            isRegistrationInProgress = false;
        }
    }
    
    /**
     * Schedule token refresh
     */
    private void scheduleTokenRefresh() {
        handler.postDelayed(() -> {
            Log.d(TAG, "Performing scheduled token refresh");
            
            FirebaseMessaging.getInstance().getToken()
                .addOnCompleteListener(task -> {
                    if (task.isSuccessful() && task.getResult() != null) {
                        String token = task.getResult();
                        if (shouldRegisterToken(token)) {
                            registerTokenWithServer(token);
                        }
                    }
                    
                    // Schedule next refresh
                    scheduleTokenRefresh();
                });
                
        }, REGISTRATION_INTERVAL);
    }
    
    /**
     * Schedule retry after failure
     */
    private void scheduleRetry() {
        handler.postDelayed(() -> {
            Log.d(TAG, "Retrying FCM token registration");
            FirebaseMessaging.getInstance().getToken()
                .addOnCompleteListener(task -> {
                    if (task.isSuccessful() && task.getResult() != null) {
                        String token = task.getResult();
                        registerTokenWithServer(token);
                    }
                });
        }, RETRY_INTERVAL);
    }
    
    /**
     * Get current stored token
     */
    public String getCurrentToken() {
        return prefs.getString(KEY_FCM_TOKEN, "");
    }
    
    /**
     * Get user email
     */
    public String getUserEmail() {
        return prefs.getString(KEY_USER_EMAIL, "");
    }
    
    /**
     * Get user barangay
     */
    public String getUserBarangay() {
        return prefs.getString(KEY_USER_BARANGAY, "");
    }
    
    /**
     * Clear stored data (call on logout)
     */
    public void clearData() {
        // Clear FCM token from database
        clearFCMTokenFromDatabase();
        
        // Clear local preferences
        prefs.edit().clear().apply();
        Log.d(TAG, "FCM data cleared");
    }
    
    /**
     * Clear FCM token from database for specific user (called during logout)
     * This method runs in background thread but waits for completion to ensure timing
     */
    public void clearFCMTokenForUser(String userEmail) {
        Log.d(TAG, "=== FCM TOKEN CLEARING DEBUG START ===");
        Log.d(TAG, "Input userEmail: " + userEmail);
        
        if (userEmail == null || userEmail.isEmpty()) {
            Log.e(TAG, "ERROR: No user email provided to clear FCM token");
            return;
        }
        
        Log.d(TAG, "Step 1: Starting FCM token clearing for: " + userEmail);
        
        // Use CountDownLatch to wait for background thread completion
        final CountDownLatch latch = new CountDownLatch(1);
        
        // Run in background thread to avoid NetworkOnMainThreadException
        new Thread(() -> {
            try {
                Log.d(TAG, "Step 2: Creating JSON data object");
                // Clear FCM token for the user
                JSONObject data = new JSONObject();
                data.put("fcm_token", ""); // Clear FCM token with empty string
                Log.d(TAG, "Step 2.1: Created data JSON: " + data.toString());
                
                Log.d(TAG, "Step 3: Creating request data object");
                JSONObject requestData = new JSONObject();
                requestData.put("table", "community_users");
                requestData.put("data", data);
                requestData.put("where", "email = ?");
                requestData.put("params", new JSONArray().put(userEmail));
                Log.d(TAG, "Step 3.1: Created requestData JSON: " + requestData.toString());
                
                String requestBody = requestData.toString();
                Log.d(TAG, "Step 4: Final request body: " + requestBody);
                
                Log.d(TAG, "Step 5: Creating RequestBody");
                RequestBody body = RequestBody.create(
                    requestBody, 
                    MediaType.parse("application/json; charset=utf-8")
                );
                Log.d(TAG, "Step 5.1: RequestBody created successfully");
                
                Log.d(TAG, "Step 6: Checking httpClient");
                if (httpClient == null) {
                    Log.e(TAG, "ERROR: HttpClient is null, cannot clear FCM token");
                    return;
                }
                Log.d(TAG, "Step 6.1: HttpClient is not null");
                
                Log.d(TAG, "Step 7: Checking SERVER_URL");
                Log.d(TAG, "Step 7.1: SERVER_URL value: '" + SERVER_URL + "'");
                if (SERVER_URL == null || SERVER_URL.isEmpty()) {
                    Log.e(TAG, "ERROR: SERVER_URL is null or empty, cannot clear FCM token");
                    return;
                }
                Log.d(TAG, "Step 7.2: SERVER_URL is valid");
                
                Log.d(TAG, "Step 8: Building HTTP request");
                Request request = new Request.Builder()
                    .url(SERVER_URL)
                    .post(body)
                    .addHeader("Content-Type", "application/json")
                    .build();
                Log.d(TAG, "Step 8.1: HTTP request built successfully");
                Log.d(TAG, "Step 8.2: Request URL: " + request.url());
                Log.d(TAG, "Step 8.3: Request method: " + request.method());
                Log.d(TAG, "Step 8.4: Request headers: " + request.headers());
                
                Log.d(TAG, "Step 9: Executing HTTP request");
                try (Response response = httpClient.newCall(request).execute()) {
                    Log.d(TAG, "Step 9.1: HTTP request executed");
                    Log.d(TAG, "Step 9.2: Response code: " + response.code());
                    Log.d(TAG, "Step 9.3: Response message: " + response.message());
                    Log.d(TAG, "Step 9.4: Response headers: " + response.headers());
                    
                    if (response.isSuccessful()) {
                        Log.d(TAG, "SUCCESS: FCM token cleared from database for: " + userEmail);
                        String responseBody = response.body() != null ? response.body().string() : "null";
                        Log.d(TAG, "Step 9.5: Response body: " + responseBody);
                    } else {
                        Log.w(TAG, "WARNING: Failed to clear FCM token: " + response.code());
                        String responseBody = response.body() != null ? response.body().string() : "null";
                        Log.w(TAG, "Step 9.6: Error response body: " + responseBody);
                    }
                }
                Log.d(TAG, "Step 10: HTTP request completed");
            } catch (Exception e) {
                Log.e(TAG, "ERROR: Exception in FCM token clearing: " + e.getMessage());
                Log.e(TAG, "ERROR: Exception type: " + e.getClass().getSimpleName());
                Log.e(TAG, "ERROR: Stack trace:", e);
            } finally {
                latch.countDown(); // Signal completion
            }
        }).start();
        
        // Wait for background thread to complete (max 10 seconds)
        try {
            Log.d(TAG, "Step 11: Waiting for background thread to complete");
            latch.await(10, java.util.concurrent.TimeUnit.SECONDS);
            Log.d(TAG, "Step 11.1: Background thread completed");
        } catch (InterruptedException e) {
            Log.e(TAG, "ERROR: Interrupted while waiting for FCM token clearing: " + e.getMessage());
        }
        
        Log.d(TAG, "=== FCM TOKEN CLEARING DEBUG END ===");
    }
    
    /**
     * Clear FCM token from database on logout
     */
    private void clearFCMTokenFromDatabase() {
        String userEmail = prefs.getString(KEY_USER_EMAIL, "");
        if (userEmail.isEmpty()) {
            Log.d(TAG, "No user email to clear FCM token");
            return;
        }
        
        new Thread(() -> {
            try {
                // Clear FCM token for the user
                JSONObject data = new JSONObject();
                data.put("fcm_token", null); // Clear FCM token
                
                JSONObject requestData = new JSONObject();
                requestData.put("table", "community_users");
                requestData.put("data", data);
                requestData.put("where", "email = ?");
                requestData.put("params", new JSONArray().put(userEmail));
                
                RequestBody body = RequestBody.create(
                    requestData.toString(), 
                    MediaType.parse("application/json; charset=utf-8")
                );
                
                Request request = new Request.Builder()
                    .url(SERVER_URL)
                    .post(body)
                    .addHeader("Content-Type", "application/json")
                    .build();
                
                try (Response response = httpClient.newCall(request).execute()) {
                    if (response.isSuccessful()) {
                        Log.d(TAG, "FCM token cleared from database for: " + userEmail);
                    } else {
                        Log.w(TAG, "Failed to clear FCM token: " + response.code());
                    }
                }
            } catch (Exception e) {
                Log.e(TAG, "Error clearing FCM token: " + e.getMessage());
            }
        }).start();
    }
    
    /**
     * Mark user as inactive on server (call on logout)
     */
    public void markUserInactive() {
        String userEmail = prefs.getString(KEY_USER_EMAIL, "");
        if (userEmail.isEmpty()) {
            Log.d(TAG, "No user email to mark inactive");
            return;
        }
        
        // Removed complex user switching logic
    }
    
    // Removed complex user switching logic - keeping it simple
    
    /**
     * Clear FCM token data for current user (call when switching users)
     */
    public void clearTokenData() {
        prefs.edit()
            .remove(KEY_FCM_TOKEN)
            .remove(KEY_LAST_REGISTRATION)
            .apply();
        Log.d(TAG, "FCM token data cleared for user switch");
    }
    
    /**
     * Fetch barangay from user_preferences database
     */
    private String fetchBarangayFromDatabase(String userEmail) {
        try {
            // Use the existing UserPreferencesDbHelper to fetch barangay
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(context);
            android.database.sqlite.SQLiteDatabase db = dbHelper.getReadableDatabase();
            
            // Check if table exists first
            android.database.Cursor tableCheck = db.rawQuery(
                "SELECT name FROM sqlite_master WHERE type='table' AND name=?", 
                new String[]{UserPreferencesDbHelper.TABLE_NAME}
            );
            
            if (tableCheck == null || !tableCheck.moveToFirst()) {
                Log.w(TAG, "Table " + UserPreferencesDbHelper.TABLE_NAME + " does not exist");
                if (tableCheck != null) tableCheck.close();
                dbHelper.close();
                return "";
            }
            tableCheck.close();
            
            // Check if barangay column exists
            android.database.Cursor columnCheck = db.rawQuery(
                "PRAGMA table_info(" + UserPreferencesDbHelper.TABLE_NAME + ")", 
                null
            );
            
            boolean hasBarangayColumn = false;
            if (columnCheck != null) {
                while (columnCheck.moveToNext()) {
                    String columnName = columnCheck.getString(columnCheck.getColumnIndex("name"));
                    if (UserPreferencesDbHelper.COL_BARANGAY.equals(columnName)) {
                        hasBarangayColumn = true;
                        break;
                    }
                }
                columnCheck.close();
            }
            
            if (!hasBarangayColumn) {
                Log.w(TAG, "Column " + UserPreferencesDbHelper.COL_BARANGAY + " does not exist in table " + UserPreferencesDbHelper.TABLE_NAME);
                dbHelper.close();
                return "";
            }
            
            String[] columns = {UserPreferencesDbHelper.COL_BARANGAY};
            String selection = UserPreferencesDbHelper.COL_USER_EMAIL + " = ?";
            String[] selectionArgs = {userEmail};
            
            android.database.Cursor cursor = db.query(
                UserPreferencesDbHelper.TABLE_NAME,
                columns,
                selection,
                selectionArgs,
                null, null, null
            );
            
            String barangay = "";
            if (cursor != null && cursor.moveToFirst()) {
                barangay = cursor.getString(cursor.getColumnIndex(UserPreferencesDbHelper.COL_BARANGAY));
                cursor.close();
            }
            
            dbHelper.close();
            Log.d(TAG, "Fetched barangay from database for " + userEmail + ": " + barangay);
            return barangay != null ? barangay : "";
            
        } catch (Exception e) {
            Log.e(TAG, "Error fetching barangay from database: " + e.getMessage());
            return "";
        }
    }
    
    /**
     * Force token refresh (call when needed)
     */
    public void forceTokenRefresh() {
        Log.d(TAG, "Forcing token refresh");
        FirebaseMessaging.getInstance().deleteToken()
            .addOnCompleteListener(task -> {
                if (task.isSuccessful()) {
                    Log.d(TAG, "Old token deleted, getting new token");
                    FirebaseMessaging.getInstance().getToken()
                        .addOnCompleteListener(tokenTask -> {
                            if (tokenTask.isSuccessful() && tokenTask.getResult() != null) {
                                String newToken = tokenTask.getResult();
                                registerTokenWithServer(newToken);
                            }
                        });
                }
            });
    }
    
    // Removed complex user deletion handling - keeping it simple
    
    /**
     * Clear stored FCM token (used when account is deleted)
     */
    public void clearStoredToken() {
        Log.d(TAG, "Clearing stored FCM token");
        
        prefs.edit()
            .remove(KEY_FCM_TOKEN)
            .remove(KEY_TOKEN_TIMESTAMP)
            .remove(KEY_USER_EMAIL)
            .remove(KEY_USER_BARANGAY)
            .apply();
            
        Log.d(TAG, "FCM token cleared from local storage");
    }
    
}
