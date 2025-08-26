package com.example.nutrisaur11;

import android.content.Context;
import android.database.sqlite.SQLiteDatabase;
import android.database.Cursor;
import android.content.ContentValues;
import android.util.Log;
import android.widget.Toast;

import org.json.JSONObject;
import org.json.JSONException;

import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;
import okhttp3.RequestBody;
import okhttp3.MediaType;

import java.io.IOException;

public class SyncDataActivity {
    
    private static final String TAG = "SyncDataActivity";
    private static final String API_URL = Constants.UNIFIED_API_URL;
    
    public static void syncUserDataFromWeb(Context context, String userEmail) {
        new Thread(() -> {
            try {
                // Get user data from web API
                OkHttpClient client = new OkHttpClient();
                
                // Create request to get user data
                JSONObject requestData = new JSONObject();
                requestData.put("action", "get_user_data");
                requestData.put("email", userEmail);
                
                RequestBody body = RequestBody.create(
                    requestData.toString(), 
                    MediaType.get("application/json; charset=utf-8")
                );
                
                Request request = new Request.Builder()
                    .url(API_URL)
                    .post(body)
                    .build();
                
                try (Response response = client.newCall(request).execute()) {
                    if (response.isSuccessful() && response.body() != null) {
                        String responseBody = response.body().string();
                        Log.d(TAG, "API response: " + responseBody);
                        
                        try {
                            JSONObject responseJson = new JSONObject(responseBody);
                            
                            if (responseJson.has("success") && responseJson.getBoolean("success")) {
                                if (responseJson.has("user_data")) {
                                    JSONObject userData = responseJson.getJSONObject("user_data");
                                    
                                    // Update local database with web data
                                    updateLocalDatabase(context, userEmail, userData);
                                    
                                    Log.d(TAG, "Successfully synced user data from web");
                                } else {
                                    Log.d(TAG, "No user_data in response, but API call successful");
                                }
                            } else {
                                Log.e(TAG, "API returned error: " + (responseJson.has("error") ? responseJson.getString("error") : "Unknown error"));
                            }
                        } catch (Exception e) {
                            Log.e(TAG, "Error parsing API response: " + e.getMessage());
                            Log.d(TAG, "Raw response: " + responseBody);
                        }
                    } else {
                        Log.e(TAG, "API request failed: " + response.code());
                    }
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error syncing data: " + e.getMessage());
            }
        }).start();
    }
    
    private static void updateLocalDatabase(Context context, String userEmail, JSONObject userData) {
        try {
            UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(context);
            SQLiteDatabase db = dbHelper.getWritableDatabase();
            
            // Create comprehensive screening answers JSON for backward compatibility
            JSONObject screeningAnswers = new JSONObject();
            
            // Add all available data from web
            if (userData.has("weight")) screeningAnswers.put("weight", userData.getDouble("weight"));
            if (userData.has("height")) screeningAnswers.put("height", userData.getDouble("height"));
            if (userData.has("birthday")) screeningAnswers.put("birthday", userData.getString("birthday"));
            if (userData.has("gender")) screeningAnswers.put("gender", userData.getString("gender"));
            if (userData.has("bmi")) screeningAnswers.put("bmi", userData.getDouble("bmi"));
            if (userData.has("barangay")) screeningAnswers.put("barangay", userData.getString("barangay"));
            if (userData.has("income")) screeningAnswers.put("income", userData.getString("income"));
            if (userData.has("swelling")) screeningAnswers.put("swelling", userData.getString("swelling"));
            if (userData.has("weight_loss")) screeningAnswers.put("weight_loss", userData.getString("weight_loss"));
            if (userData.has("feeding_behavior")) screeningAnswers.put("feeding_behavior", userData.getString("feeding_behavior"));
            if (userData.has("physical_signs")) screeningAnswers.put("physical_signs", userData.getString("physical_signs"));
            if (userData.has("dietary_diversity")) screeningAnswers.put("dietary_diversity", userData.getInt("dietary_diversity"));
            
            // Add new clinical risk factors
            if (userData.has("has_recent_illness")) screeningAnswers.put("has_recent_illness", userData.getBoolean("has_recent_illness"));
            if (userData.has("has_eating_difficulty")) screeningAnswers.put("has_eating_difficulty", userData.getBoolean("has_eating_difficulty"));
            if (userData.has("has_food_insecurity")) screeningAnswers.put("has_food_insecurity", userData.getBoolean("has_food_insecurity"));
            if (userData.has("has_micronutrient_deficiency")) screeningAnswers.put("has_micronutrient_deficiency", userData.getBoolean("has_micronutrient_deficiency"));
            if (userData.has("has_functional_decline")) screeningAnswers.put("has_functional_decline", userData.getBoolean("has_functional_decline"));
            
            // Create ContentValues for database update
            ContentValues values = new ContentValues();
            values.put(UserPreferencesDbHelper.COL_USER_EMAIL, userEmail);
            values.put(UserPreferencesDbHelper.COL_SCREENING_ANSWERS, screeningAnswers.toString());
            
            // Add only the columns that exist in the local database
            if (userData.has("barangay")) values.put(UserPreferencesDbHelper.COL_BARANGAY, userData.getString("barangay"));
            if (userData.has("income")) values.put(UserPreferencesDbHelper.COL_INCOME, userData.getString("income"));
            if (userData.has("risk_score")) values.put(UserPreferencesDbHelper.COL_RISK_SCORE, userData.getInt("risk_score"));
            
            // Check if user exists in local database
            Cursor cursor = db.query(UserPreferencesDbHelper.TABLE_NAME, 
                null, UserPreferencesDbHelper.COL_USER_EMAIL + "=?", 
                new String[]{userEmail}, null, null, null);
            
            if (cursor.moveToFirst()) {
                // Update existing record
                db.update(UserPreferencesDbHelper.TABLE_NAME, values, 
                    UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{userEmail});
                Log.d(TAG, "Updated user data in local database");
            } else {
                // Insert new record
                db.insert(UserPreferencesDbHelper.TABLE_NAME, null, values);
                Log.d(TAG, "Inserted user data in local database");
            }
            
            cursor.close();
            dbHelper.close();
            
            Log.d(TAG, "Local database updated with screening_answers: " + screeningAnswers.toString());
            
        } catch (Exception e) {
            Log.e(TAG, "Error updating local database: " + e.getMessage());
        }
    }
    
    // Method to manually trigger sync for testing
    public static void manualSync(Context context, String userEmail) {
        Log.d(TAG, "Manual sync triggered for user: " + userEmail);
        syncUserDataFromWeb(context, userEmail);
    }
}
