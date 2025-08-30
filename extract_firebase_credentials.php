<?php
/**
 * Firebase Credentials Extractor
 * This script helps extract Firebase credentials from your JSON file
 * and formats them for Railway environment variables
 */

echo "🔐 Firebase Credentials Extractor for Railway\n";
echo "=============================================\n\n";

// Check if the Firebase file exists
$firebaseFile = 'sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-152a242b3b.json';

if (!file_exists($firebaseFile)) {
    echo "❌ Firebase Admin SDK file not found at: $firebaseFile\n\n";
    echo "📁 Please place your Firebase Admin SDK JSON file at:\n";
    echo "   $firebaseFile\n\n";
    echo "🔍 Or run this script from the directory containing your Firebase file.\n";
    exit(1);
}

// Read and parse the Firebase credentials
$credentials = json_decode(file_get_contents($firebaseFile), true);

if (!$credentials) {
    echo "❌ Failed to parse Firebase credentials JSON file\n";
    exit(1);
}

echo "✅ Firebase credentials loaded successfully!\n\n";

// Display the credentials (masked for security)
echo "📋 Firebase Project Information:\n";
echo "   Project ID: " . ($credentials['project_id'] ?? 'NOT FOUND') . "\n";
echo "   Client Email: " . ($credentials['client_email'] ?? 'NOT FOUND') . "\n";
echo "   Private Key ID: " . ($credentials['private_key_id'] ?? 'NOT FOUND') . "\n\n";

// Generate Railway environment variables
echo "🚀 Railway Environment Variables to Add:\n";
echo "=========================================\n\n";

echo "FIREBASE_PROJECT_ID=" . ($credentials['project_id'] ?? '') . "\n";
echo "FIREBASE_PRIVATE_KEY_ID=" . ($credentials['private_key_id'] ?? '') . "\n";
echo "FIREBASE_PRIVATE_KEY=\"" . str_replace("\n", "\\n", $credentials['private_key'] ?? '') . "\"\n";
echo "FIREBASE_CLIENT_EMAIL=" . ($credentials['client_email'] ?? '') . "\n";
echo "FIREBASE_CLIENT_ID=" . ($credentials['client_id'] ?? '') . "\n";
echo "FIREBASE_CLIENT_CERT_URL=" . ($credentials['client_x509_cert_url'] ?? '') . "\n\n";

echo "📝 Instructions:\n";
echo "1. Copy the above environment variables\n";
echo "2. Go to Railway Dashboard → Your Project → Variables\n";
echo "3. Add each variable with its corresponding value\n";
echo "4. Railway will automatically redeploy\n";
echo "5. Test FCM functionality again\n\n";

echo "🔒 Security Note: These credentials are sensitive. Keep them secure!\n";
?>
