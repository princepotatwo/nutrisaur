package com.example.nutrisaur11;

import android.content.Context;
import android.content.SharedPreferences;
import android.database.Cursor;
import android.util.Log;
import okhttp3.*;
import org.json.JSONObject;
import java.io.IOException;

public class ScreeningResultStore {
    private static Context context;
    
    public static void init(Context ctx) {
        context = ctx;
    }
    
    private static String getCurrentUserEmail() {
        return context.getSharedPreferences("nutrisaur_prefs", Context.MODE_PRIVATE).getString("current_user_email", null);
    }
    
    public static void setRiskScore(Context context, int score) {
        Log.d("ScreeningResultStore", "Setting risk score: " + score);
        String email = getCurrentUserEmail();
        if (email == null) {
            Log.e("ScreeningResultStore", "No user email found");
            return;
        }
        
        UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(context);
        Cursor cursor = dbHelper.getReadableDatabase().rawQuery("SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
        
        if (cursor.getCount() > 0) {
            Log.d("ScreeningResultStore", "Updating existing record");
            android.content.ContentValues values = new android.content.ContentValues();
            values.put(UserPreferencesDbHelper.COL_RISK_SCORE, score);
            int updateResult = dbHelper.getWritableDatabase().update(UserPreferencesDbHelper.TABLE_NAME, values, UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
            Log.d("ScreeningResultStore", "Update result: " + updateResult);
        } else {
            Log.d("ScreeningResultStore", "Inserting new record");
            android.content.ContentValues values = new android.content.ContentValues();
            values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
            values.put(UserPreferencesDbHelper.COL_RISK_SCORE, score);
            long insertResult = dbHelper.getWritableDatabase().insert(UserPreferencesDbHelper.TABLE_NAME, null, values);
            Log.d("ScreeningResultStore", "Insert result: " + insertResult);
        }
        cursor.close();
        dbHelper.close();
    }
    
    public static int getRiskScore() {
        // Return cached value or local database value to avoid network calls on main thread
        Context context = getContext();
        if (context == null) {
            Log.e("ScreeningResultStore", "Context is null");
            return 0;
        }
        
        String email = getCurrentUserEmail();
        if (email == null) {
            Log.e("ScreeningResultStore", "No user email found");
            return 0;
        }
        
        // Use local database instead of making network calls on main thread
        return getRiskScoreFromLocalDb(context, email);
    }
    
    private static int getRiskScoreFromLocalDb(Context context, String email) {
        UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(context);
        Cursor cursor = dbHelper.getReadableDatabase().rawQuery("SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
        
        int riskScore = 0;
        if (cursor.moveToFirst()) {
            int riskScoreIndex = cursor.getColumnIndex(UserPreferencesDbHelper.COL_RISK_SCORE);
            if (riskScoreIndex >= 0) {
                riskScore = cursor.getInt(riskScoreIndex);
            }
        }
        
        cursor.close();
        dbHelper.close();
        return riskScore;
    }
    
    // New method to get risk score asynchronously
    public static void getRiskScoreAsync(Context context, OnRiskScoreReceivedListener listener) {
        if (context == null || listener == null) {
            Log.e("ScreeningResultStore", "Context or listener is null");
            return;
        }
        
        new Thread(() -> {
            try {
                int riskScore = getRiskScore();
                // Post result back to main thread
                if (context instanceof android.app.Activity) {
                    ((android.app.Activity) context).runOnUiThread(() -> listener.onRiskScoreReceived(riskScore));
                }
            } catch (Exception e) {
                Log.e("ScreeningResultStore", "Error in async risk score fetch: " + e.getMessage());
                if (context instanceof android.app.Activity) {
                    ((android.app.Activity) context).runOnUiThread(() -> listener.onRiskScoreReceived(0));
                }
            }
        }).start();
    }
    
    public interface OnRiskScoreReceivedListener {
        void onRiskScoreReceived(int riskScore);
    }
    
    private static Context getContext() {
        return context;
    }
    
    public static void saveScreeningData(Context context, String screeningData) {
        Log.d("ScreeningResultStore", "Saving screening data: " + screeningData);
        String email = getCurrentUserEmail();
        if (email == null) {
            Log.e("ScreeningResultStore", "No user email found");
            return;
        }
        
        UserPreferencesDbHelper dbHelper = new UserPreferencesDbHelper(context);
        Cursor cursor = dbHelper.getReadableDatabase().rawQuery("SELECT * FROM " + UserPreferencesDbHelper.TABLE_NAME + " WHERE " + UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
        
        if (cursor.getCount() > 0) {
            Log.d("ScreeningResultStore", "Updating existing record with screening data");
            android.content.ContentValues values = new android.content.ContentValues();
            values.put(UserPreferencesDbHelper.COL_SCREENING_ANSWERS, screeningData);
            int updateResult = dbHelper.getWritableDatabase().update(UserPreferencesDbHelper.TABLE_NAME, values, UserPreferencesDbHelper.COL_USER_EMAIL + "=?", new String[]{email});
            Log.d("ScreeningResultStore", "Update result: " + updateResult);
        } else {
            Log.d("ScreeningResultStore", "Inserting new record with screening data");
            android.content.ContentValues values = new android.content.ContentValues();
            values.put(UserPreferencesDbHelper.COL_USER_EMAIL, email);
            values.put(UserPreferencesDbHelper.COL_SCREENING_ANSWERS, screeningData);
            long insertResult = dbHelper.getWritableDatabase().insert(UserPreferencesDbHelper.TABLE_NAME, null, values);
            Log.d("ScreeningResultStore", "Insert result: " + insertResult);
        }
        cursor.close();
        dbHelper.close();
    }
}
