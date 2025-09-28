# Google Sign-In Fix Instructions

## Problem
Google Sign-In is failing with error code 10, which indicates a developer error due to missing OAuth client configuration.

## Root Cause
The `google-services.json` file has an empty `oauth_client` array, meaning the SHA-1 fingerprint and OAuth client ID are not properly configured.

## Solution

### Step 1: SHA-1 Fingerprint
**Your debug keystore SHA-1 fingerprint is:**
```
F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10
```

### Step 2: Configure Firebase Console
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project: **nutrisaur-ebf29**
3. Go to **Project Settings** > **General**
4. Under **Your apps**, find your Android app
5. Click **Add Fingerprint** and add: `F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10`
6. Download the updated `google-services.json` file
7. Replace the existing file in `app/google-services.json`

### Step 3: Alternative - Google Cloud Console
If Firebase doesn't work, configure directly in Google Cloud Console:

1. Go to [Google Cloud Console](https://console.developers.google.com/apis/credentials)
2. Select project: **nutrisaur-ebf29**
3. Go to **Credentials**
4. Click **Create Credentials** > **OAuth client ID**
5. Select **Android** as application type
6. Enter package name: `com.example.nutrisaur11`
7. Enter SHA-1 fingerprint: `F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10`
8. Create the OAuth client

### Step 4: Verify Configuration
After updating `google-services.json`, it should contain:
```json
"oauth_client": [
  {
    "client_id": "YOUR_CLIENT_ID.apps.googleusercontent.com",
    "client_type": 1,
    "android_info": {
      "package_name": "com.example.nutrisaur11",
      "certificate_hash": "F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10"
    }
  }
]
```

### Step 5: Test
1. Clean and rebuild your Android project
2. Test Google Sign-In
3. Check Android logs for any remaining errors

## Current Configuration
- **Project ID:** nutrisaur-ebf29
- **Project Number:** 43537903747
- **Package Name:** com.example.nutrisaur11
- **Android Client ID:** 43537903747-2nd9mtmm972ucoirho2sthkqlu8mct6b.apps.googleusercontent.com

## Troubleshooting
- Make sure you're using the debug keystore SHA-1 for development
- Ensure package name matches exactly: `com.example.nutrisaur11`
- Always download the latest `google-services.json` after configuration changes
- Clean and rebuild after updating the configuration file
