# Google Sign-In Error Code 10 Fix

## Error Analysis
From the Android logs, we can see:
```
GoogleSignIn com.example.nutrisaur11 W signInResult:failed code=10
```

**Error Code 10 = DEVELOPER_ERROR**

This error typically occurs due to:
1. **SHA-1 fingerprint mismatch** in Google Console
2. **Package name mismatch** 
3. **Client ID configuration issues**
4. **Google Services configuration problems**

## Solutions

### 1. **Get Current SHA-1 Fingerprint**

Run this command to get your current SHA-1 fingerprint:

```bash
# For debug keystore (development)
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android

# For release keystore (if you have one)
keytool -list -v -keystore your-release-key.keystore -alias your-key-alias
```

### 2. **Update Google Console Configuration**

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project: `nutrisaur-ebf29`
3. Go to **APIs & Services** > **Credentials**
4. Find your Android OAuth 2.0 client ID
5. Click **Edit** on the Android client
6. Update the **SHA-1 certificate fingerprint** with the one from step 1
7. Verify the **Package name** is exactly: `com.example.nutrisaur11`

### 3. **Verify google-services.json**

Make sure your `google-services.json` file has the correct configuration:

```json
{
  "project_info": {
    "project_number": "43537903747",
    "project_id": "nutrisaur-ebf29"
  },
  "client": [
    {
      "client_info": {
        "mobilesdk_app_id": "1:43537903747:android:a4efa056a2399439846bb6",
        "android_client_info": {
          "package_name": "com.example.nutrisaur11"
        }
      },
      "oauth_client": [
        {
          "client_id": "43537903747-2nd9mtmm972ucoirho2sthkqlu8mct6b.apps.googleusercontent.com",
          "client_type": 1,
          "android_info": {
            "package_name": "com.example.nutrisaur11",
            "certificate_hash": "YOUR_SHA1_FINGERPRINT_HERE"
          }
        }
      ]
    }
  ]
}
```

### 4. **Check Android App Configuration**

Verify these files are correct:

#### **build.gradle.kts**
```kotlin
plugins {
    id("com.android.application")
    id("com.google.gms.google-services") // This must be present
}

dependencies {
    implementation("com.google.android.gms:play-services-auth:20.7.0")
    implementation(platform("com.google.firebase:firebase-bom:34.1.0"))
    implementation("com.google.firebase:firebase-analytics")
}
```

#### **AndroidManifest.xml**
Make sure you have internet permission:
```xml
<uses-permission android:name="android.permission.INTERNET" />
```

### 5. **Test with Real Device**

Google Sign-In often doesn't work properly in emulators. Test on a real Android device.

### 6. **Enable Debug Logging**

Add this to your app to get more detailed error information:

```java
// In your LoginActivity or SignUpActivity
GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
    .requestIdToken(ANDROID_CLIENT_ID)
    .requestEmail()
    .build();

// Enable debug logging
GoogleSignIn.getClient(this, gso).getSignInIntent();
```

### 7. **Common Fixes**

#### **Fix 1: Update SHA-1 in Google Console**
1. Get your SHA-1 fingerprint using the keytool command above
2. Go to Google Console > Credentials > Your Android OAuth client
3. Add the SHA-1 fingerprint to the "SHA-1 certificate fingerprints" field
4. Save the changes

#### **Fix 2: Verify Package Name**
Make sure the package name in Google Console exactly matches:
- `com.example.nutrisaur11`

#### **Fix 3: Check Client ID**
Verify the client ID in your code matches Google Console:
- Android Client ID: `43537903747-2nd9mtmm972ucoirho2sthkqlu8mct6b.apps.googleusercontent.com`

### 8. **Testing Steps**

1. **Clean and rebuild** your project:
   ```bash
   ./gradlew clean
   ./gradlew assembleDebug
   ```

2. **Uninstall and reinstall** the app on your device

3. **Test Google Sign-In** again

4. **Check logs** for any new error messages:
   ```bash
   adb logcat | grep -i "google\|signin\|oauth"
   ```

### 9. **Alternative Debugging**

If the issue persists, try this debugging approach:

```java
// Add this to your Google Sign-In callback
private void handleGoogleSignInResult(Task<GoogleSignInAccount> completedTask) {
    try {
        GoogleSignInAccount account = completedTask.getResult(ApiException.class);
        // Success
    } catch (ApiException e) {
        Log.e("GoogleSignIn", "signInResult:failed code=" + e.getStatusCode());
        Log.e("GoogleSignIn", "Error details: " + e.getMessage());
        
        // Handle specific error codes
        switch (e.getStatusCode()) {
            case 10: // DEVELOPER_ERROR
                Log.e("GoogleSignIn", "DEVELOPER_ERROR: Check SHA-1 fingerprint and package name");
                break;
            case 7: // NETWORK_ERROR
                Log.e("GoogleSignIn", "NETWORK_ERROR: Check internet connection");
                break;
            case 12501: // SIGN_IN_CANCELLED
                Log.e("GoogleSignIn", "SIGN_IN_CANCELLED: User cancelled sign-in");
                break;
            default:
                Log.e("GoogleSignIn", "Unknown error: " + e.getStatusCode());
        }
    }
}
```

## Quick Fix Checklist

- [ ] Get current SHA-1 fingerprint using keytool
- [ ] Update SHA-1 in Google Console
- [ ] Verify package name is exactly `com.example.nutrisaur11`
- [ ] Check client ID matches Google Console
- [ ] Clean and rebuild project
- [ ] Test on real device (not emulator)
- [ ] Check Android logs for detailed error messages

## Expected Result

After applying these fixes, Google Sign-In should work without error code 10. The app should successfully:
1. Open Google Sign-In dialog
2. Allow user to select Google account
3. Return to app with user information
4. Process the sign-in through your backend API
