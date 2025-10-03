# Android App Archive Status Fix

## Problem
The Android app uses cached user data and doesn't check the `status` field to detect if a user is archived. The app needs to:

1. Include `status` field in user data cache
2. Force fresh data fetch when checking for archive status
3. Show logout modal if `status = 0`

## Required Changes

### 1. Update CommunityUserManager.java

#### Add status field to cache (Line 117-133):
```java
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
userData.put("status", user.optString("status", "1")); // ADD THIS LINE - Default to active
```

#### Add status field to cache loading (Line 864-884):
```java
if ((currentTime - lastUpdate) < cacheValidity) {
    userData.put("name", prefs.getString("user_data_name_" + email, ""));
    userData.put("email", prefs.getString("user_data_email_" + email, email));
    userData.put("sex", prefs.getString("user_data_sex_" + email, ""));
    userData.put("age", prefs.getString("user_data_age_" + email, ""));
    userData.put("birthday", prefs.getString("user_data_birthday_" + email, ""));
    userData.put("height_cm", prefs.getString("user_data_height_cm_" + email, ""));
    userData.put("weight_kg", prefs.getString("user_data_weight_kg_" + email, ""));
    userData.put("bmi", prefs.getString("user_data_bmi_" + email, ""));
    userData.put("bmi_category", prefs.getString("user_data_bmi_category_" + email, ""));
    userData.put("muac_cm", prefs.getString("user_data_muac_cm_" + email, ""));
    userData.put("muac_category", prefs.getString("user_data_muac_category_" + email, ""));
    userData.put("nutritional_risk", prefs.getString("user_data_nutritional_risk_" + email, ""));
    userData.put("is_pregnant", prefs.getString("user_data_is_pregnant_" + email, ""));
    userData.put("barangay", prefs.getString("user_data_barangay_" + email, ""));
    userData.put("municipality", prefs.getString("user_data_municipality_" + email, ""));
    userData.put("screening_date", prefs.getString("user_data_screening_date_" + email, ""));
    userData.put("notes", prefs.getString("user_data_notes_" + email, ""));
    userData.put("status", prefs.getString("user_data_status_" + email, "1")); // ADD THIS LINE
    
    Log.d(TAG, "Retrieved cached user data for: " + email);
}
```

#### Add status field to cache clearing (Line 170-172):
```java
// Clear all cached user data fields
String[] fields = {"name", "email", "sex", "age", "birthday", "height_cm", "weight_kg", 
                  "bmi", "bmi_category", "muac_cm", "muac_category", "nutritional_risk", 
                  "is_pregnant", "barangay", "municipality", "screening_date", "notes", "status"}; // ADD "status"
```

#### Add status field to cache keys (Line 689-691):
```java
// List of keys we cache
String[] keys = {"name", "email", "sex", "age", "birthday", "height_cm", "weight_kg", 
                "bmi", "bmi_category", "muac_cm", "muac_category", "nutritional_risk", 
                "is_pregnant", "barangay", "municipality", "screening_date", "notes", "status"}; // ADD "status"
```

#### Add status field to cache clearing in clearUserCache (Line 710-712):
```java
// Remove all cached data for this user
String[] keys = {"name", "email", "sex", "age", "birthday", "height_cm", "weight_kg", 
                "bmi", "bmi_category", "muac_cm", "muac_category", "nutritional_risk", 
                "is_pregnant", "barangay", "municipality", "screening_date", "notes", "status"}; // ADD "status"
```

### 2. Add Archive Status Check Method

Add this new method to CommunityUserManager.java:

```java
/**
 * Check if current user is archived and show logout modal if needed
 */
public void checkArchiveStatusAndLogoutIfNeeded(Activity activity) {
    Log.d(TAG, "=== CHECKING ARCHIVE STATUS ===");
    
    if (!isLoggedIn()) {
        return;
    }
    
    String email = getCurrentUserEmail();
    if (email == null || email.isEmpty()) {
        return;
    }
    
    // Force fresh fetch to check archive status
    getCurrentUserDataFromDatabaseForceRefresh(new UserDataCallback() {
        @Override
        public void onUserDataReceived(Map<String, String> userData) {
            if (userData.containsKey("status")) {
                String status = userData.get("status");
                Log.d(TAG, "User status: " + status);
                
                // Check if user is archived (status = 0)
                if ("0".equals(status)) {
                    Log.d(TAG, "User is archived, showing logout modal");
                    showArchiveLogoutModal(activity);
                } else {
                    Log.d(TAG, "User is active (status = " + status + ")");
                }
            } else {
                Log.d(TAG, "No status field found in user data");
            }
        }
    });
}

/**
 * Show logout modal for archived users
 */
private void showArchiveLogoutModal(Activity activity) {
    if (activity == null || activity.isFinishing()) {
        return;
    }
    
    activity.runOnUiThread(() -> {
        new AlertDialog.Builder(activity)
            .setTitle("Account Archived")
            .setMessage("Your account has been archived. Please contact an administrator.")
            .setCancelable(false)
            .setPositiveButton("OK", (dialog, which) -> {
                // Log out the user
                logout();
                // Navigate to login screen
                Intent intent = new Intent(activity, LoginActivity.class);
                intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
                activity.startActivity(intent);
                activity.finish();
            })
            .show();
    });
}
```

### 3. Update Activities to Check Archive Status

In each Activity (AccountActivity, MainActivity, etc.), add this to onResume():

```java
@Override
protected void onResume() {
    super.onResume();
    
    // Check if user is archived
    CommunityUserManager userManager = new CommunityUserManager(this);
    userManager.checkArchiveStatusAndLogoutIfNeeded(this);
}
```

### 4. Update SessionManager Validation

In SessionManager.java, modify the validation to check status:

```java
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
```

## Implementation Steps

1. **Update CommunityUserManager.java** with all the changes above
2. **Add archive check method** to CommunityUserManager.java
3. **Update all Activities** to call checkArchiveStatusAndLogoutIfNeeded() in onResume()
4. **Update SessionManager.java** to check status field during validation
5. **Test** by archiving a user and navigating between pages

## Expected Behavior

- When user navigates between pages, app checks archive status
- If user is archived (status = 0), shows logout modal
- User gets logged out and redirected to login screen
- Archived users cannot use the app even if they were already logged in
