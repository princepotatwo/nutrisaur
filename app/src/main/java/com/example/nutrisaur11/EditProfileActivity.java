package com.example.nutrisaur11;

import android.content.Intent;
import android.os.Bundle;
import android.text.TextUtils;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;
import java.util.Map;

import okhttp3.MediaType;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;

public class EditProfileActivity extends AppCompatActivity {
    
    private static final String TAG = "EditProfileActivity";
    private static final String API_BASE_URL = Constants.API_BASE_URL + "api/DatabaseAPI.php";
    
    private EditText nameField;
    private EditText emailField;
    private EditText birthdayField;
    private EditText sexField;
    private EditText weightField;
    private EditText heightField;
    private Button saveButton;
    private Button cancelButton;
    
    private CommunityUserManager userManager;
    private Map<String, String> currentUserData;
    private String currentEmail;
    
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_edit_profile);
        
        userManager = new CommunityUserManager(this);
        currentEmail = userManager.getCurrentUserEmail();
        
        initializeViews();
        loadCurrentUserData();
        setupClickListeners();
        setupBackButton();
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        // Refresh profile data when returning to the activity
        // This ensures we have the latest data if the user made changes elsewhere
        if (userManager.isLoggedIn()) {
            refreshProfileData();
        }
    }
    
    private void initializeViews() {
        nameField = findViewById(R.id.name_field);
        emailField = findViewById(R.id.email_field);
        birthdayField = findViewById(R.id.birthday_field);
        sexField = findViewById(R.id.sex_field);
        weightField = findViewById(R.id.weight_field);
        heightField = findViewById(R.id.height_field);
        saveButton = findViewById(R.id.save_button);
        cancelButton = findViewById(R.id.cancel_button);
    }
    
    private void loadCurrentUserData() {
        // Check if user is logged in
        if (!userManager.isLoggedIn()) {
            Log.w(TAG, "User not logged in, cannot load profile data");
            showDefaultProfileData();
            return;
        }
        
        // Show loading state
        showLoadingState();
        
        // Fetch user data from database in background thread (same pattern as FoodActivity)
        new Thread(() -> {
            try {
                // Get user data from community_users table using the same method as FoodActivity
                Map<String, String> userData = userManager.getCurrentUserDataFromDatabase();
                
                if (userData != null && !userData.isEmpty()) {
                    Log.d(TAG, "User profile data fetched from community_users table");
                    
                    // Store user data for later use
                    currentUserData = userData;
                    
                    // Update UI on main thread
                    runOnUiThread(() -> updateProfileUI(userData));
                } else {
                    Log.w(TAG, "No user profile data found in community_users table");
                    runOnUiThread(() -> showDefaultProfileData());
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error fetching user profile data: " + e.getMessage());
                runOnUiThread(() -> showDefaultProfileData());
            }
        }).start();
    }
    
    private void showLoadingState() {
        // Show loading state for all fields
        nameField.setText("Loading...");
        emailField.setText("Loading...");
        birthdayField.setText("Loading...");
        sexField.setText("Loading...");
        weightField.setText("Loading...");
        heightField.setText("Loading...");
    }
    
    private void updateProfileUI(Map<String, String> userData) {
        Log.d(TAG, "Updating profile UI with fetched data");
        
        // Set current values from database
        String name = userData.get("name");
        String email = userData.get("email");
        String birthday = userData.get("birthday");
        String sex = userData.get("sex");
        String weight = userData.get("weight_kg");
        String height = userData.get("height_cm");
        
        if (name != null && !name.isEmpty()) {
            nameField.setText(name);
        } else {
            nameField.setText("Not set");
        }
        
        if (email != null && !email.isEmpty()) {
            emailField.setText(email);
        } else {
            emailField.setText("Not set");
        }
        
        if (birthday != null && !birthday.isEmpty()) {
            birthdayField.setText(birthday);
        } else {
            birthdayField.setText("Not set");
        }
        
        if (sex != null && !sex.isEmpty()) {
            sexField.setText(sex);
        } else {
            sexField.setText("Not set");
        }
        
        if (weight != null && !weight.isEmpty()) {
            weightField.setText(weight + " kg");
        } else {
            weightField.setText("Not set");
        }
        
        if (height != null && !height.isEmpty()) {
            heightField.setText(height + " cm");
        } else {
            heightField.setText("Not set");
        }
        
        Log.d(TAG, "Profile UI updated successfully");
    }
    
    private void showDefaultProfileData() {
        Log.d(TAG, "Showing default profile data");
        
        // Show default/placeholder values
        nameField.setText("Not set");
        emailField.setText("Not set");
        birthdayField.setText("Not set");
        sexField.setText("Not set");
        weightField.setText("Not set");
        heightField.setText("Not set");
    }
    
    private void setupClickListeners() {
        // Save button
        saveButton.setOnClickListener(v -> saveProfile());
        
        // Cancel button
        cancelButton.setOnClickListener(v -> finish());
    }
    
    private void setupBackButton() {
        findViewById(R.id.back_button).setOnClickListener(v -> finish());
    }
    
    private void showEditDialog(String title, String field, String currentValue) {
        // Create a simple dialog for editing
        android.app.AlertDialog.Builder builder = new android.app.AlertDialog.Builder(this);
        builder.setTitle("Edit " + title);
        
        final EditText input = new EditText(this);
        input.setInputType(android.text.InputType.TYPE_CLASS_TEXT);
        
        if (field.equals("height_cm") || field.equals("weight_kg")) {
            input.setInputType(android.text.InputType.TYPE_CLASS_NUMBER | android.text.InputType.TYPE_NUMBER_FLAG_DECIMAL);
        } else if (field.equals("birthday")) {
            input.setHint("YYYY-MM-DD");
        } else if (field.equals("password")) {
            input.setInputType(android.text.InputType.TYPE_CLASS_TEXT | android.text.InputType.TYPE_TEXT_VARIATION_PASSWORD);
            input.setHint("Enter new password");
        }
        
        if (!field.equals("password")) {
            input.setText(currentValue);
        }
        
        builder.setView(input);
        
        builder.setPositiveButton("Save", (dialog, which) -> {
            String newValue = input.getText().toString().trim();
            if (!TextUtils.isEmpty(newValue)) {
                updateField(field, newValue);
            }
        });
        
        builder.setNegativeButton("Cancel", (dialog, which) -> dialog.cancel());
        
        builder.show();
    }
    
    private void updateField(String field, String newValue) {
        // Update the display immediately
        switch (field) {
            case "name":
                nameField.setText(newValue);
                break;
            case "height_cm":
                heightField.setText(newValue + " cm");
                break;
            case "weight_kg":
                weightField.setText(newValue + " kg");
                break;
            case "birthday":
                birthdayField.setText(newValue);
                break;
        }
        
        // Update the data map
        currentUserData.put(field, newValue);
        
        Toast.makeText(this, field + " updated", Toast.LENGTH_SHORT).show();
    }
    
    private void saveProfile() {
        saveButton.setText("Saving...");
        saveButton.setEnabled(false);
        
        new Thread(() -> {
            try {
                // Update the database
                boolean success = updateUserInDatabase();
                
                runOnUiThread(() -> {
                    if (success) {
                        Toast.makeText(EditProfileActivity.this, "Profile updated successfully!", Toast.LENGTH_SHORT).show();
                        // Clear cache to force refresh
                        userManager.clearUserCache(currentEmail);
                        // Refresh profile data to show updated values
                        refreshProfileData();
                        finish();
                    } else {
                        Toast.makeText(EditProfileActivity.this, "Failed to update profile", Toast.LENGTH_LONG).show();
                        saveButton.setText("Save");
                        saveButton.setEnabled(true);
                    }
                });
            } catch (Exception e) {
                Log.e(TAG, "Error saving profile: " + e.getMessage());
                runOnUiThread(() -> {
                    Toast.makeText(EditProfileActivity.this, "Error saving profile: " + e.getMessage(), Toast.LENGTH_LONG).show();
                    saveButton.setText("Save");
                    saveButton.setEnabled(true);
                });
            }
        }).start();
    }
    
    private boolean updateUserInDatabase() {
        try {
            OkHttpClient client = new OkHttpClient();
            
            JSONObject requestData = new JSONObject();
            requestData.put("action", "update_community_user");
            requestData.put("email", currentEmail);
            
            // Get values directly from EditText fields
            String name = nameField.getText().toString().trim();
            String email = emailField.getText().toString().trim();
            String birthday = birthdayField.getText().toString().trim();
            String sex = sexField.getText().toString().trim();
            String weight = weightField.getText().toString().trim();
            String height = heightField.getText().toString().trim();
            
            // Add updated fields if they have values
            if (!name.isEmpty() && !name.equals("Not set")) {
                requestData.put("name", name);
            }
            if (!email.isEmpty() && !email.equals("Not set")) {
                requestData.put("email", email);
            }
            if (!birthday.isEmpty() && !birthday.equals("Not set")) {
                requestData.put("birthday", birthday);
            }
            if (!sex.isEmpty() && !sex.equals("Not set")) {
                requestData.put("sex", sex);
            }
            if (!weight.isEmpty() && !weight.equals("Not set") && !weight.equals("-- kg")) {
                // Remove "kg" suffix if present
                weight = weight.replace(" kg", "");
                requestData.put("weight_kg", weight);
            }
            if (!height.isEmpty() && !height.equals("Not set") && !height.equals("-- cm")) {
                // Remove "cm" suffix if present
                height = height.replace(" cm", "");
                requestData.put("height_cm", height);
            }
            
            // Update screening date to current timestamp
            String currentDate = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.getDefault()).format(new Date());
            requestData.put("screening_date", currentDate);
            
            RequestBody body = RequestBody.create(
                requestData.toString(),
                MediaType.parse("application/json")
            );
            
            Request request = new Request.Builder()
                .url(API_BASE_URL)
                .post(body)
                .build();
            
            try (Response response = client.newCall(request).execute()) {
                if (response.isSuccessful()) {
                    String responseBody = response.body().string();
                    JSONObject jsonResponse = new JSONObject(responseBody);
                    return jsonResponse.getBoolean("success");
                }
            }
        } catch (Exception e) {
            Log.e(TAG, "Error updating user in database: " + e.getMessage());
        }
        
        return false;
    }
    
    /**
     * Refresh profile data after saving (similar to FoodActivity refresh pattern)
     */
    private void refreshProfileData() {
        Log.d(TAG, "Refreshing profile data after save...");
        
        // Fetch fresh data from database
        new Thread(() -> {
            try {
                Map<String, String> freshUserData = userManager.getCurrentUserDataFromDatabase();
                
                if (freshUserData != null && !freshUserData.isEmpty()) {
                    Log.d(TAG, "Profile data refreshed successfully");
                    currentUserData = freshUserData;
                } else {
                    Log.w(TAG, "No fresh profile data available after refresh");
                }
                
            } catch (Exception e) {
                Log.e(TAG, "Error refreshing profile data: " + e.getMessage());
            }
        }).start();
    }
}
