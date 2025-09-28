# Comprehensive Fix for Nutrisaur App Issues

## Issues Identified from Logs

### 1. **Google Sign-In Error Code 10** (Still occurring)
### 2. **NetworkOnMainThreadException** (Critical)
### 3. **Database Error**: Missing 'status' column
### 4. **Session Management Issues**

## Fix 1: Google Sign-In Error Code 10 - Alternative Approach

Since the standard fixes aren't working, let's try a different approach:

### **Option A: Use Web Client ID Instead**
```java
// In LoginActivity.java and SignUpActivity.java
// Change from Android client ID to Web client ID
private static final String WEB_CLIENT_ID = "43537903747-ppt6bbcnfa60p0hchanl32equ9c3b0ao.apps.googleusercontent.com";

// Update GoogleSignInOptions
GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
    .requestIdToken(WEB_CLIENT_ID)  // Use web client ID instead
    .requestEmail()
    .build();
```

### **Option B: Add Multiple Client IDs to Google Console**
1. Go to Google Console > Credentials
2. Edit your Android OAuth client
3. Add BOTH client IDs:
   - Android: `43537903747-2nd9mtmm972ucoirho2sthkqlu8mct6b.apps.googleusercontent.com`
   - Web: `43537903747-ppt6bbcnfa60p0hchanl32equ9c3b0ao.apps.googleusercontent.com`

## Fix 2: NetworkOnMainThreadException

The app is making network calls on the main thread. Fix this in `CommunityUserManager.java`:

```java
// Make sure all network calls are in background threads
private void makeApiRequest(String action, JSONObject requestData) {
    // This should already be in a background thread, but let's verify
    new Thread(() -> {
        try {
            // Network call here
        } catch (Exception e) {
            // Handle error
        }
    }).start();
}
```

## Fix 3: Database Error - Missing 'status' Column

The database is missing a 'status' column. This needs to be added to the database schema.

### **SQL to Add Missing Column:**
```sql
ALTER TABLE community_users ADD COLUMN status VARCHAR(50) DEFAULT 'active';
```

## Fix 4: Session Management Issues

The session validation is failing. Let's improve the error handling:

```java
// In SessionManager.java
private void handleInvalidSession() {
    runOnUiThread(() -> {
        // Clear session data
        clearUserSession();
        
        // Show login dialog
        showLoginDialog();
    });
}
```

## **🚀 Complete Fix Implementation**

### **Step 1: Fix Google Sign-In (Try Web Client ID)**

Update both `LoginActivity.java` and `SignUpActivity.java`:

```java
// Change the client ID
private static final String WEB_CLIENT_ID = "43537903747-ppt6bbcnfa60p0hchanl32equ9c3b0ao.apps.googleusercontent.com";

// Update GoogleSignInOptions
GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
    .requestIdToken(WEB_CLIENT_ID)
    .requestEmail()
    .build();
```

### **Step 2: Fix Database Schema**

Add the missing 'status' column to your database:

```sql
-- Run this SQL on your database
ALTER TABLE community_users ADD COLUMN status VARCHAR(50) DEFAULT 'active';
```

### **Step 3: Fix Network Threading**

Ensure all network calls are in background threads in `CommunityUserManager.java`.

### **Step 4: Test the Fixes**

1. **Clean and rebuild**:
   ```bash
   cd /Users/jasminpingol/Downloads/thesis75/nutrisaur11
   ./gradlew clean
   ./gradlew assembleDebug
   ```

2. **Uninstall and reinstall**:
   ```bash
   adb uninstall com.example.nutrisaur11
   adb install app/build/outputs/apk/debug/app-debug.apk
   ```

3. **Test Google Sign-In**

## **Expected Results**

After these fixes:
- ✅ **No more error code 10** (using web client ID)
- ✅ **No more NetworkOnMainThreadException**
- ✅ **No more database column errors**
- ✅ **Google Sign-In should work**

## **Alternative: Complete Google Console Reset**

If the above doesn't work, try this:

1. **Delete the existing Android OAuth client** in Google Console
2. **Create a new Android OAuth client** with:
   - Package name: `com.example.nutrisaur11`
   - SHA-1: `F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10`
3. **Download new google-services.json**
4. **Replace the file in your app**
5. **Rebuild and test**

The web client ID approach often works when Android client ID fails due to device-specific issues.
