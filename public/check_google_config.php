<?php
/**
 * Check Google Sign-In Configuration
 */

echo "<h1>Google Sign-In Configuration Check</h1>";

// Read the current google-services.json
$googleServicesPath = __DIR__ . '/../app/google-services.json';
if (file_exists($googleServicesPath)) {
    $googleServices = json_decode(file_get_contents($googleServicesPath), true);
    
    echo "<h2>Current Configuration</h2>";
    echo "<p><strong>Project ID:</strong> " . ($googleServices['project_info']['project_id'] ?? 'Not found') . "</p>";
    echo "<p><strong>Project Number:</strong> " . ($googleServices['project_info']['project_number'] ?? 'Not found') . "</p>";
    
    if (isset($googleServices['client'][0])) {
        $client = $googleServices['client'][0];
        echo "<p><strong>Package Name:</strong> " . ($client['client_info']['android_client_info']['package_name'] ?? 'Not found') . "</p>";
        echo "<p><strong>Mobile SDK App ID:</strong> " . ($client['client_info']['mobilesdk_app_id'] ?? 'Not found') . "</p>";
        
        // Check OAuth client configuration
        if (isset($client['oauth_client']) && !empty($client['oauth_client'])) {
            echo "<p style='color: green;'>✅ OAuth client is configured</p>";
            echo "<h3>OAuth Client Details:</h3>";
            foreach ($client['oauth_client'] as $index => $oauthClient) {
                echo "<p><strong>Client " . ($index + 1) . ":</strong></p>";
                echo "<ul>";
                echo "<li>Client ID: " . ($oauthClient['client_id'] ?? 'Not found') . "</li>";
                echo "<li>Client Type: " . ($oauthClient['client_type'] ?? 'Not found') . "</li>";
                if (isset($oauthClient['android_info'])) {
                    echo "<li>Package Name: " . ($oauthClient['android_info']['package_name'] ?? 'Not found') . "</li>";
                    echo "<li>Certificate Hash: " . ($oauthClient['android_info']['certificate_hash'] ?? 'Not found') . "</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>❌ OAuth client is NOT configured (empty array)</p>";
            echo "<p>This is the root cause of Google Sign-In error code 10.</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ google-services.json file not found at: $googleServicesPath</p>";
}

echo "<h2>Required SHA-1 Fingerprint</h2>";
echo "<p>Your debug keystore SHA-1 fingerprint:</p>";
echo "<code>F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10</code>";

echo "<h2>Fix Instructions</h2>";
echo "<ol>";
echo "<li>Go to <a href='https://console.firebase.google.com/' target='_blank'>Firebase Console</a></li>";
echo "<li>Select project: <strong>nutrisaur-ebf29</strong></li>";
echo "<li>Go to <strong>Project Settings</strong> > <strong>General</strong></li>";
echo "<li>Under <strong>Your apps</strong>, find your Android app</li>";
echo "<li>Click <strong>Add Fingerprint</strong> and add: <code>F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10</code></li>";
echo "<li>Download the updated <code>google-services.json</code> file</li>";
echo "<li>Replace the existing file in <code>app/google-services.json</code></li>";
echo "<li>Clean and rebuild your Android project</li>";
echo "<li>Test Google Sign-In</li>";
echo "</ol>";

echo "<h2>Alternative: Google Cloud Console</h2>";
echo "<p>If Firebase doesn't work:</p>";
echo "<ol>";
echo "<li>Go to <a href='https://console.developers.google.com/apis/credentials' target='_blank'>Google Cloud Console</a></li>";
echo "<li>Select project: <strong>nutrisaur-ebf29</strong></li>";
echo "<li>Go to <strong>Credentials</strong></li>";
echo "<li>Click <strong>Create Credentials</strong> > <strong>OAuth client ID</strong></li>";
echo "<li>Select <strong>Android</strong> as application type</li>";
echo "<li>Enter package name: <code>com.example.nutrisaur11</code></li>";
echo "<li>Enter SHA-1 fingerprint: <code>F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10</code></li>";
echo "<li>Create the OAuth client</li>";
echo "</ol>";
?>
