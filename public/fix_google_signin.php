<?php
/**
 * Google Sign-In Configuration Fix
 * This script helps diagnose and fix Google Sign-In issues
 */

echo "<h1>Google Sign-In Configuration Fix</h1>";

echo "<h2>Current Issue</h2>";
echo "<p style='color: red;'>❌ Google Sign-In is failing with error code 10</p>";
echo "<p>This indicates a developer error, usually due to missing OAuth client configuration.</p>";

echo "<h2>Root Cause</h2>";
echo "<p>The <code>google-services.json</code> file has an empty <code>oauth_client</code> array:</p>";
echo "<pre>";
echo '"oauth_client": [],';
echo "</pre>";
echo "<p>This means the OAuth client ID and SHA-1 fingerprint are not configured.</p>";

echo "<h2>Steps to Fix</h2>";

echo "<h3>Step 1: Get SHA-1 Fingerprint</h3>";
echo "<p>Run this command in your project root to get the SHA-1 fingerprint:</p>";
echo "<pre>";
echo "cd /Users/jasminpingol/Downloads/thesis75/nutrisaur11<br>";
echo "keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android";
echo "</pre>";

echo "<h3>Step 2: Configure Firebase Console</h3>";
echo "<ol>";
echo "<li>Go to <a href='https://console.firebase.google.com/' target='_blank'>Firebase Console</a></li>";
echo "<li>Select your project: <strong>nutrisaur-ebf29</strong></li>";
echo "<li>Go to <strong>Project Settings</strong> > <strong>General</strong></li>";
echo "<li>Under <strong>Your apps</strong>, find your Android app</li>";
echo "<li>Click <strong>Add Fingerprint</strong> and add the SHA-1 fingerprint from Step 1</li>";
echo "<li>Download the updated <code>google-services.json</code> file</li>";
echo "<li>Replace the existing file in <code>app/google-services.json</code></li>";
echo "</ol>";

echo "<h3>Step 3: Alternative - Manual OAuth Client Setup</h3>";
echo "<ol>";
echo "<li>Go to <a href='https://console.developers.google.com/apis/credentials' target='_blank'>Google Cloud Console</a></li>";
echo "<li>Select project: <strong>nutrisaur-ebf29</strong></li>";
echo "<li>Go to <strong>Credentials</strong></li>";
echo "<li>Click <strong>Create Credentials</strong> > <strong>OAuth client ID</strong></li>";
echo "<li>Select <strong>Android</strong> as application type</li>";
echo "<li>Enter package name: <code>com.example.nutrisaur11</code></li>";
echo "<li>Enter SHA-1 fingerprint from Step 1</li>";
echo "<li>Create the OAuth client</li>";
echo "</ol>";

echo "<h3>Step 4: Update google-services.json</h3>";
echo "<p>After configuring the OAuth client, your <code>google-services.json</code> should have:</p>";
echo "<pre>";
echo '"oauth_client": [';
echo '  {';
echo '    "client_id": "YOUR_CLIENT_ID.apps.googleusercontent.com",';
echo '    "client_type": 1,';
echo '    "android_info": {';
echo '      "package_name": "com.example.nutrisaur11",';
echo '      "certificate_hash": "YOUR_SHA1_FINGERPRINT"';
echo '    }';
echo '  }';
echo ']';
echo "</pre>";

echo "<h2>Current Configuration</h2>";
echo "<p><strong>Project ID:</strong> nutrisaur-ebf29</p>";
echo "<p><strong>Project Number:</strong> 43537903747</p>";
echo "<p><strong>Package Name:</strong> com.example.nutrisaur11</p>";
echo "<p><strong>Android Client ID:</strong> 43537903747-2nd9mtmm972ucoirho2sthkqlu8mct6b.apps.googleusercontent.com</p>";

echo "<h2>Verification</h2>";
echo "<p>After fixing the configuration:</p>";
echo "<ol>";
echo "<li>Clean and rebuild your Android project</li>";
echo "<li>Test Google Sign-In again</li>";
echo "<li>Check that the OAuth client array is no longer empty</li>";
echo "<li>Verify that SHA-1 fingerprint matches your debug keystore</li>";
echo "</ol>";

echo "<h2>Common Issues</h2>";
echo "<ul>";
echo "<li><strong>Wrong SHA-1:</strong> Make sure you're using the debug keystore SHA-1 for development</li>";
echo "<li><strong>Package Name Mismatch:</strong> Ensure package name in Firebase matches your app</li>";
echo "<li><strong>Outdated google-services.json:</strong> Always download the latest file after configuration changes</li>";
echo "<li><strong>Build Issues:</strong> Clean and rebuild after updating google-services.json</li>";
echo "</ul>";

echo "<h2>Next Steps</h2>";
echo "<p>1. Run the SHA-1 command above</p>";
echo "<p>2. Configure Firebase Console with the SHA-1 fingerprint</p>";
echo "<p>3. Download updated google-services.json</p>";
echo "<p>4. Test Google Sign-In</p>";
echo "<p>5. If still failing, check the Android logs for more specific error details</p>";
?>
