package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;
import org.json.JSONObject;
import org.json.JSONArray;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.HashMap;
import java.util.Map;

public class CommunityUserManager {
    private static final String TAG = "CommunityUserManager";
    private static final String API_BASE_URL = "https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php";
    private static final String PREFS_NAME = "nutrisaur_prefs";
    
    private Context context;
    private SharedPreferences prefs;
    
    public CommunityUserManager(Context context) {
        this.context = context;
        this.prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE);
    }
    
    /**
     * Login user with email and password
     */
    public void loginUser(String email, String password, LoginCallback callback) {
        new Thread(() -> {
            try {
                JSONObject requestData = new JSONObject();
                requestData.put("email", email);
                requestData.put("password", password);
                
                String response = makeApiRequest("login_community_user", requestData);
                JSONObject jsonResponse = new JSONObject(response);
                
                if (jsonResponse.getBoolean("success")) {
                    // Save user data to SharedPreferences
                    JSONObject user = jsonResponse.getJSONObject("user");
                    saveUserToPrefs(user);
                    
                    // Set login status
                    prefs.edit()
                        .putString("current_user_email", email)
                        .putBoolean("is_logged_in", true)
                        .apply();
                    
                    callback.onSuccess("Login successful!");
                } else {
                    callback.onError(jsonResponse.getString("message"));
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Login error: " + e.getMessage());
                callback.onError("Login failed: " + e.getMessage());
            }
        }).start();
    }
    
    /**
     * Register new user
     */
    public void registerUser(String name, String email, String password, String municipality, 
                           String barangay, String sex, String birthday, String isPregnant, 
                           String weight, String height, RegisterCallback callback) {
        new Thread(() -> {
            try {
                JSONObject requestData = new JSONObject();
                requestData.put("name", name);
                requestData.put("email", email);
                requestData.put("password", password);
                requestData.put("municipality", municipality);
                requestData.put("barangay", barangay);
                requestData.put("sex", sex);
                requestData.put("birthday", birthday);
                requestData.put("is_pregnant", isPregnant);
                requestData.put("weight", weight);
                requestData.put("height", height);
                
                String response = makeApiRequest("save_screening", requestData);
                JSONObject jsonResponse = new JSONObject(response);
                
                if (jsonResponse.getBoolean("success")) {
                    // Save user data to SharedPreferences
                    JSONObject userData = jsonResponse.getJSONObject("data");
                    saveUserToPrefs(userData);
                    
                    // Set login status
                    prefs.edit()
                        .putString("current_user_email", email)
                        .putBoolean("is_logged_in", true)
                        .apply();
                    
                    callback.onSuccess("Registration successful!");
                } else {
                    callback.onError(jsonResponse.getString("message"));
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Registration error: " + e.getMessage());
                callback.onError("Registration failed: " + e.getMessage());
            }
        }).start();
    }
    
    /**
     * Get current user data
     */
    public Map<String, String> getCurrentUserData() {
        Map<String, String> userData = new HashMap<>();
        
        if (isLoggedIn()) {
            userData.put("name", prefs.getString("user_name", ""));
            userData.put("email", prefs.getString("current_user_email", ""));
            userData.put("municipality", prefs.getString("user_municipality", ""));
            userData.put("barangay", prefs.getString("user_barangay", ""));
            userData.put("sex", prefs.getString("user_sex", ""));
            userData.put("birthday", prefs.getString("user_birthday", ""));
            userData.put("is_pregnant", prefs.getString("user_is_pregnant", ""));
            userData.put("weight", prefs.getString("user_weight", ""));
            userData.put("height", prefs.getString("user_height", ""));
            userData.put("screening_date", prefs.getString("user_screening_date", ""));
        }
        
        return userData;
    }
    
    /**
     * Check if user is logged in
     */
    public boolean isLoggedIn() {
        return prefs.getBoolean("is_logged_in", false) && 
               prefs.getString("current_user_email", null) != null;
    }
    
    /**
     * Get current user email
     */
    public String getCurrentUserEmail() {
        return prefs.getString("current_user_email", null);
    }
    
    /**
     * Logout user
     */
    public void logout() {
        prefs.edit()
            .remove("current_user_email")
            .putBoolean("is_logged_in", false)
            .remove("user_name")
            .remove("user_municipality")
            .remove("user_barangay")
            .remove("user_sex")
            .remove("user_birthday")
            .remove("user_is_pregnant")
            .remove("user_weight")
            .remove("user_height")
            .remove("user_screening_date")
            .apply();
    }
    
    /**
     * Save user data to SharedPreferences
     */
    private void saveUserToPrefs(JSONObject user) {
        try {
            SharedPreferences.Editor editor = prefs.edit();
            editor.putString("user_name", user.optString("name", ""));
            editor.putString("user_municipality", user.optString("municipality", ""));
            editor.putString("user_barangay", user.optString("barangay", ""));
            editor.putString("user_sex", user.optString("sex", ""));
            editor.putString("user_birthday", user.optString("birthday", ""));
            editor.putString("user_is_pregnant", user.optString("is_pregnant", ""));
            editor.putString("user_weight", user.optString("weight", ""));
            editor.putString("user_height", user.optString("height", ""));
            editor.putString("user_screening_date", user.optString("screening_date", ""));
            editor.apply();
        } catch (Exception e) {
            Log.e(TAG, "Error saving user to prefs: " + e.getMessage());
        }
    }
    
    /**
     * Make API request
     */
    private String makeApiRequest(String action, JSONObject data) throws Exception {
        URL url = new URL(API_BASE_URL + "?action=" + action);
        HttpURLConnection conn = (HttpURLConnection) url.openConnection();
        conn.setRequestMethod("POST");
        conn.setRequestProperty("Content-Type", "application/json");
        conn.setDoOutput(true);
        conn.setConnectTimeout(10000);
        conn.setReadTimeout(10000);
        
        OutputStreamWriter os = new OutputStreamWriter(conn.getOutputStream());
        os.write(data.toString());
        os.close();
        
        BufferedReader reader = new BufferedReader(new InputStreamReader(conn.getInputStream()));
        StringBuilder response = new StringBuilder();
        String line;
        while ((line = reader.readLine()) != null) {
            response.append(line);
        }
        reader.close();
        
        return response.toString();
    }
    
    /**
     * Callback interfaces
     */
    public interface LoginCallback {
        void onSuccess(String message);
        void onError(String error);
    }
    
    public interface RegisterCallback {
        void onSuccess(String message);
        void onError(String error);
    }
}
