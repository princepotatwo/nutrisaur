<?php
// Firebase FCM Configuration
// Replace this with your actual Firebase Server Key from Firebase Console

// Option 1: Firebase Server Key (Recommended - Get this from Firebase Console)
define('FIREBASE_SERVER_KEY', 'YOUR_FIREBASE_SERVER_KEY_HERE');

// Option 2: Use Service Account (Alternative approach)
define('USE_SERVICE_ACCOUNT', true); // Set to true if you want to use service account instead

// Instructions:
// 1. Go to https://console.firebase.google.com/
// 2. Select your project: nutrisaur-ebf29
// 3. Click gear icon → Project settings → Cloud Messaging tab
// 4. Copy the "Server key" (starts with AAAA...)
// 5. Replace 'YOUR_FIREBASE_SERVER_KEY_HERE' above with your actual key

// Example of what the key looks like:
// define('FIREBASE_SERVER_KEY', 'AAAAqwertyuiopasdfghjklzxcvbnm1234567890');

// Security Note: Keep this key secret and never commit it to public repositories!
?>
