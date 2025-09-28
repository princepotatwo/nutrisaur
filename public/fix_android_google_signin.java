// Android Google Sign-In Fix Code
// Apply these changes to your Android app

// 1. Update LoginActivity.java - Better error handling
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
        
        // Better error handling for common Google Sign-In errors
        String errorMessage;
        switch (e.getStatusCode()) {
            case 10:
                errorMessage = "Developer error - OAuth configuration issue. Check google-services.json";
                break;
            case 12502:
                errorMessage = "Network error - Check Google Play Services and internet connection";
                break;
            case 7:
                errorMessage = "Network error - Check internet connection";
                break;
            case 8:
                errorMessage = "Internal error - Google Play Services issue";
                break;
            case 12501:
                errorMessage = "Sign-in cancelled by user";
                break;
            default:
                errorMessage = "Sign-in failed: " + e.getMessage();
        }
        
        Toast.makeText(this, errorMessage, Toast.LENGTH_LONG).show();
    }
}

// 2. Add Google Play Services check
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

// 3. Update setupGoogleSignIn method - Use web client ID instead of hardcoded
private void setupGoogleSignIn() {
    // Option A: Use web client ID (recommended)
    GoogleSignInOptions gso = new GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
            .requestIdToken(getString(R.string.default_web_client_id))  // Use web client ID
            .requestEmail()
            .build();
            
    googleSignInClient = GoogleSignIn.getClient(this, gso);
    
    // Check Google Play Services
    checkGooglePlayServices();
}

// 4. Add imports for Google Play Services check
// Add these imports to your LoginActivity.java:
// import com.google.android.gms.common.ConnectionResult;
// import com.google.android.gms.common.GoogleApiAvailability;

// 5. Update handleGoogleLogin method
private void handleGoogleLogin() {
    // Check Google Play Services before attempting sign-in
    if (GoogleApiAvailability.getInstance().isGooglePlayServicesAvailable(this) != ConnectionResult.SUCCESS) {
        Toast.makeText(this, "Google Play Services not available", Toast.LENGTH_LONG).show();
        return;
    }
    
    Intent signInIntent = googleSignInClient.getSignInIntent();
    startActivityForResult(signInIntent, RC_SIGN_IN);
}

// 6. Alternative: If you want to keep using Android client ID, make sure it matches google-services.json
// Update the ANDROID_CLIENT_ID constant to match the client_id in your google-services.json:
// private static final String ANDROID_CLIENT_ID = "YOUR_ACTUAL_CLIENT_ID_FROM_GOOGLE_SERVICES_JSON";

// 7. Add to onCreate method
@Override
protected void onCreate(Bundle savedInstanceState) {
    super.onCreate(savedInstanceState);
    setContentView(R.layout.activity_login);
    
    // ... existing code ...
    
    // Check Google Play Services on app start
    checkGooglePlayServices();
    
    // ... rest of onCreate ...
}
