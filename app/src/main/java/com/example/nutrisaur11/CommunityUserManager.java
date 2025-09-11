package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;

import org.json.JSONObject;

import java.util.HashMap;
import java.util.Map;

public class CommunityUserManager {
    private static final String TAG = "CommunityUserManager";
    private static final String API_BASE_URL = "https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php";
    
    private Context context;
    
    public CommunityUserManager(Context context) {
        this.context = context;
    }
    
    public boolean isLoggedIn() {
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        String userEmail = prefs.getString("current_user_email", null);
        return userEmail != null && !userEmail.isEmpty();
    }
    
    public String getCurrentUserEmail() {
        SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
        return prefs.getString("current_user_email", null);
    }
    
    public Map<String, String> getCurrentUserDataFromDatabase() {
        Map<String, String> userData = new HashMap<>();
        
        Log.d(TAG, "=== GETTING CURRENT USER DATA FROM DATABASE ===");
        
        if (!isLoggedIn()) {
            Log.d(TAG, "User not logged in, returning empty data");
            return userData;
        }
        
        String email = getCurrentUserEmail();
        if (email == null || email.isEmpty()) {
            Log.d(TAG, "No email found, returning empty data");
            return userData;
        }
        
        try {
            // Make API request to get user data from database
            JSONObject requestData = new JSONObject();
            requestData.put("email", email);
            
            String response = makeApiRequest("get_community_user_data", requestData);
            JSONObject jsonResponse = new JSONObject(response);
            
            if (jsonResponse.getBoolean("success")) {
                JSONObject user = jsonResponse.getJSONObject("user");
                
                // Map database fields to our expected format
                userData.put("name", user.optString("name", ""));
                userData.put("email", user.optString("email", email));
                userData.put("sex", user.optString("sex", ""));
                userData.put("age", user.optString("age", ""));
                userData.put("birthday", user.optString("birthday", ""));
                userData.put("height_cm", user.optString("height_cm", user.optString("height", "")));
                userData.put("weight_kg", user.optString("weight_kg", user.optString("weight", "")));
                userData.put("bmi", user.optString("bmi", ""));
                userData.put("bmi_category", user.optString("bmi_category", ""));
                userData.put("muac_cm", user.optString("muac_cm", ""));
                userData.put("muac_category", user.optString("muac_category", ""));
                userData.put("nutritional_risk", user.optString("nutritional_risk", ""));
                userData.put("is_pregnant", user.optString("is_pregnant", ""));
                userData.put("barangay", user.optString("barangay", ""));
                userData.put("municipality", user.optString("municipality", ""));
                userData.put("screening_date", user.optString("screening_date", ""));
                userData.put("notes", user.optString("notes", ""));
                
                // DEBUG: Log what data was retrieved from database
                Log.d(TAG, "Retrieved user data from database:");
                for (Map.Entry<String, String> entry : userData.entrySet()) {
                    Log.d(TAG, "  " + entry.getKey() + ": " + entry.getValue());
                }
                
                // Check if we have essential data
                boolean hasEssentialData = userData.containsKey("age") || userData.containsKey("bmi") || 
                                         userData.containsKey("height_cm") || userData.containsKey("weight_kg");
                
                if (hasEssentialData) {
                    Log.d(TAG, "Essential user data found for personalization");
                } else {
                    Log.w(TAG, "WARNING: No essential user data found - personalization may be limited");
                }
                
            } else {
                Log.e(TAG, "API returned success=false: " + jsonResponse.optString("message", "Unknown error"));
            }
            
        } catch (Exception e) {
            Log.e(TAG, "Error getting user data from database: " + e.getMessage());
        }
        
        return userData;
    }
    
    private String makeApiRequest(String action, JSONObject requestData) {
        try {
            Log.d(TAG, "Making API request to: " + API_BASE_URL + "?action=" + action);
            Log.d(TAG, "Request data: " + requestData.toString());
            
            // Test network connectivity first
            if (!isNetworkAvailable()) {
                Log.e(TAG, "No network connectivity available");
                return "{\"success\": false, \"message\": \"No network connectivity\"}";
            }
            
            // Make actual HTTP request to the API
            okhttp3.OkHttpClient client = new okhttp3.OkHttpClient.Builder()
                .connectTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                .readTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                .writeTimeout(30, java.util.concurrent.TimeUnit.SECONDS)
                .build();
            
            okhttp3.RequestBody body = okhttp3.RequestBody.create(
                requestData.toString(), 
                okhttp3.MediaType.parse("application/json; charset=utf-8")
            );
            
            okhttp3.Request request = new okhttp3.Request.Builder()
                .url(API_BASE_URL + "?action=" + action)
                .post(body)
                .addHeader("Content-Type", "application/json")
                .addHeader("Accept", "application/json")
                .build();
            
            try (okhttp3.Response response = client.newCall(request).execute()) {
                Log.d(TAG, "API response code: " + response.code());
                Log.d(TAG, "API response message: " + response.message());
                
                if (response.isSuccessful() && response.body() != null) {
                    String responseBody = response.body().string();
                    Log.d(TAG, "API response for " + action + ": " + responseBody);
                    return responseBody;
                } else {
                    String errorBody = response.body() != null ? response.body().string() : "No error body";
                    Log.e(TAG, "API request failed: " + response.code() + " - " + response.message() + " - " + errorBody);
                    return "{\"success\": false, \"message\": \"API request failed: " + response.code() + " - " + response.message() + "\"}";
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error making API request: " + e.getMessage());
            e.printStackTrace();
            String errorMessage = e.getMessage();
            if (errorMessage == null || errorMessage.isEmpty()) {
                errorMessage = e.getClass().getSimpleName();
            }
            return "{\"success\": false, \"message\": \"API request failed: " + errorMessage + "\"}";
        }
    }
    
    /**
     * Check if network is available
     */
    private boolean isNetworkAvailable() {
        try {
            android.net.ConnectivityManager connectivityManager = 
                (android.net.ConnectivityManager) context.getSystemService(android.content.Context.CONNECTIVITY_SERVICE);
            
            if (connectivityManager != null) {
                android.net.NetworkInfo activeNetwork = connectivityManager.getActiveNetworkInfo();
                return activeNetwork != null && activeNetwork.isConnected();
            }
        } catch (Exception e) {
            Log.e(TAG, "Error checking network availability: " + e.getMessage());
        }
        return false;
    }
    
    private String getMockUserData() {
        // Mock data for testing - replace with actual API call
        return "{\n" +
               "  \"success\": true,\n" +
               "  \"user\": {\n" +
               "    \"name\": \"Test User\",\n" +
               "    \"email\": \"test@example.com\",\n" +
               "    \"sex\": \"Male\",\n" +
               "    \"age\": \"22\",\n" +
               "    \"birthday\": \"2003-09-10\",\n" +
               "    \"height_cm\": \"150\",\n" +
               "    \"weight_kg\": \"90\",\n" +
               "    \"bmi\": \"40.0\",\n" +
               "    \"bmi_category\": \"Obese\",\n" +
               "    \"muac_cm\": \"25.5\",\n" +
               "    \"muac_category\": \"Normal\",\n" +
               "    \"nutritional_risk\": \"High\",\n" +
               "    \"is_pregnant\": \"0\",\n" +
               "    \"barangay\": \"Alion\",\n" +
               "    \"municipality\": \"MARIVELES\",\n" +
               "    \"screening_date\": \"2025-09-10\",\n" +
               "    \"notes\": \"Obese patient needs weight management\"\n" +
               "  }\n" +
               "}";
    }
    
    // Callback interfaces
    public interface LoginCallback {
        void onSuccess(String message);
        void onError(String error);
    }
    
    public interface RegisterCallback {
        void onSuccess(String message);
        void onError(String error);
    }
    
    // Login method
    public void loginUser(String email, String password, LoginCallback callback) {
        // TODO: Implement login logic
        callback.onError("Login method not implemented yet");
    }
    
    // Register method
    public void registerUser(String fullName, String email, String password, String barangay, 
                           String municipality, String sex, String birthDate, String pregnancyStatus,
                           String weight, String height, String muac, RegisterCallback callback) {
        
        Log.d(TAG, "=== REGISTERING USER ===");
        Log.d(TAG, "Name: " + fullName);
        Log.d(TAG, "Email: " + email);
        Log.d(TAG, "Barangay: " + barangay);
        Log.d(TAG, "Municipality: " + municipality);
        
        // Run API request in background thread to avoid NetworkOnMainThreadException
        new Thread(() -> {
            try {
                // Create registration request
                JSONObject requestData = new JSONObject();
                requestData.put("name", fullName);
                requestData.put("email", email);
                requestData.put("password", password);
                requestData.put("barangay", barangay);
                requestData.put("municipality", municipality);
                requestData.put("sex", sex);
                requestData.put("birth_date", birthDate);
                requestData.put("pregnancy_status", pregnancyStatus);
                requestData.put("weight", weight);
                requestData.put("height", height);
                requestData.put("muac", muac);
                
                // Make API request
                String response = makeApiRequest("register_community_user", requestData);
                
                // Check if API request failed
                if (response == null) {
                    Log.e(TAG, "API request returned null response");
                    callback.onError("Registration failed: Unable to connect to server");
                    return;
                }
                
                JSONObject jsonResponse = new JSONObject(response);
                
                if (jsonResponse.getBoolean("success")) {
                    // Only save basic login state to SharedPreferences (no user profile data)
                    SharedPreferences prefs = context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE);
                    SharedPreferences.Editor editor = prefs.edit();
                    
                    // Clear any existing user data first
                    editor.clear();
                    
                    // Save only login state - no user profile data
                    editor.putString("current_user_email", email)
                          .putString("current_user_name", fullName)
                          .putBoolean("is_logged_in", true);
                    
                    editor.apply();
                    
                    Log.d(TAG, "Registration successful: " + jsonResponse.optString("message", "User registered successfully"));
                    callback.onSuccess(jsonResponse.optString("message", "Registration successful! Please complete your nutritional screening."));
                } else {
                    String errorMessage = jsonResponse.optString("message", "Registration failed");
                    Log.e(TAG, "Registration failed: " + errorMessage);
                    callback.onError(errorMessage);
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error during registration: " + e.getMessage());
                callback.onError("Registration failed: " + e.getMessage());
            }
        }).start();
    }
    
    // Get current user data (alias for getCurrentUserDataFromDatabase)
    public Map<String, String> getCurrentUserData() {
        return getCurrentUserDataFromDatabase();
    }
}
