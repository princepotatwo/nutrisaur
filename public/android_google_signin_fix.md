# Android Google Sign-In Fix

## Current Android Issues

### Error Code 10 (Developer Error)
- **Cause**: Android app is using hardcoded `ANDROID_CLIENT_ID` instead of the proper OAuth client ID
- **Current Client ID**: `43537903747-2nd9mtmm972ucoirho2sthkqlu8mct6b.apps.googleusercontent.com`

### Error Code 12502 (Network Error)
- **Cause**: Google Play Services connectivity issues
- **Solution**: Test on physical device with Google Play Services

## Android-Side Fixes

### 1. Fix OAuth Client ID Configuration

**Current Problem**: The app is using a hardcoded client ID that might not match the OAuth client in `google-services.json`.

**Solution**: Update the Android code to use the correct client ID from `google-services.json`.

#### Option A: Use Web Client ID (Recommended)
```java
// In LoginActivity.java and SignUpActivity.java
// Replace hardcoded ANDROID_CLIENT_ID with:
GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
        .requestIdToken(getString(R.string.default_web_client_id))  // Use web client ID
        .requestEmail()
        .build();
```

#### Option B: Use Correct Android Client ID
If you want to keep using Android client ID, make sure it matches the one in `google-services.json`:
```java
// Update ANDROID_CLIENT_ID to match google-services.json
private static final String ANDROID_CLIENT_ID = "YOUR_ACTUAL_ANDROID_CLIENT_ID_FROM_GOOGLE_SERVICES_JSON";
```

### 2. Add Better Error Handling

```java
private void handleGoogleSignInResult(Task<GoogleSignInAccount> completedTask) {
    try {
        GoogleSignInAccount account = completedTask.getResult(ApiException.class);
        
        if (account != null) {
            String idToken = account.getIdToken();
            String email = account.getEmail();
            String name = account.getDisplayName();
            String profilePicture = account.getPhotoUrl() != null ? account.getPhotoUrl().toString() : "";
            
            Log.d("GoogleSignIn", "Email: " + email + ", Name: " + name);
            sendGoogleTokenToBackend(idToken, email, name, profilePicture);
        }
        
    } catch (ApiException e) {
        Log.w("GoogleSignIn", "signInResult:failed code=" + e.getStatusCode());
        
        // Better error handling
        String errorMessage;
        switch (e.getStatusCode()) {
            case 10:
                errorMessage = "Developer error - check OAuth configuration";
                break;
            case 12502:
                errorMessage = "Network error - check Google Play Services";
                break;
            case 7:
                errorMessage = "Network error - check internet connection";
                break;
            case 8:
                errorMessage = "Internal error - try again";
                break;
            default:
                errorMessage = "Sign-in failed: " + e.getMessage();
        }
        
        Toast.makeText(this, errorMessage, Toast.LENGTH_LONG).show();
    }
}
```

### 3. Add Google Play Services Check

```java
private void checkGooglePlayServices() {
    GoogleApiAvailability apiAvailability = GoogleApiAvailability.getInstance();
    int resultCode = apiAvailability.isGooglePlayServicesAvailable(this);
    
    if (resultCode != ConnectionResult.SUCCESS) {
        if (apiAvailability.isUserResolvableError(resultCode)) {
            apiAvailability.getErrorDialog(this, resultCode, 2404).show();
        } else {
            Log.w("GoogleSignIn", "This device is not supported.");
            Toast.makeText(this, "Google Play Services not available", Toast.LENGTH_LONG).show();
        }
    }
}
```

### 4. Update build.gradle Dependencies

Make sure you have the correct Google Sign-In dependencies:

```gradle
dependencies {
    implementation 'com.google.android.gms:play-services-auth:20.7.0'
    implementation 'com.google.firebase:firebase-auth:22.3.0'
    implementation 'com.google.firebase:firebase-core:21.1.1'
}
```

## Testing Steps

1. **Test on Physical Device** (not emulator)
2. **Ensure Google Play Services is installed and updated**
3. **Check internet connectivity**
4. **Verify Google account is signed in on device**
5. **Clean and rebuild the project**

## Common Android Issues

- **Emulator without Google Play Services**: Error 12502
- **Wrong OAuth Client ID**: Error 10
- **Network connectivity**: Error 7 or 12502
- **Outdated Google Play Services**: Various errors

## Files to Update

1. `app/src/main/java/com/example/nutrisaur11/LoginActivity.java`
2. `app/src/main/java/com/example/nutrisaur11/SignUpActivity.java`
3. `app/build.gradle` (if dependencies need updating)

## Verification

After implementing these fixes:
1. Clean and rebuild the project
2. Test on a physical device with Google Play Services
3. Check Android logs for any remaining errors
4. Verify OAuth client ID matches google-services.json
