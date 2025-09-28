<?php
/**
 * Debug Google Sign-In Configuration
 */

echo "<h1>Google Sign-In Debug Report</h1>";

// Check current google-services.json
$googleServicesPath = __DIR__ . '/../app/google-services.json';
if (file_exists($googleServicesPath)) {
    $googleServices = json_decode(file_get_contents($googleServicesPath), true);
    
    echo "<h2>Current Configuration Analysis</h2>";
    
    // Project info
    echo "<h3>Project Information</h3>";
    echo "<p><strong>Project ID:</strong> " . ($googleServices['project_info']['project_id'] ?? 'Not found') . "</p>";
    echo "<p><strong>Project Number:</strong> " . ($googleServices['project_info']['project_number'] ?? 'Not found') . "</p>";
    
    if (isset($googleServices['client'][0])) {
        $client = $googleServices['client'][0];
        
        // Client info
        echo "<h3>Client Information</h3>";
        echo "<p><strong>Package Name:</strong> " . ($client['client_info']['android_client_info']['package_name'] ?? 'Not found') . "</p>";
        echo "<p><strong>Mobile SDK App ID:</strong> " . ($client['client_info']['mobilesdk_app_id'] ?? 'Not found') . "</p>";
        
        // OAuth client analysis
        echo "<h3>OAuth Client Analysis</h3>";
        if (isset($client['oauth_client']) && !empty($client['oauth_client'])) {
            echo "<p style='color: green;'>✅ OAuth client is configured</p>";
            echo "<p><strong>Number of OAuth clients:</strong> " . count($client['oauth_client']) . "</p>";
            
            foreach ($client['oauth_client'] as $index => $oauthClient) {
                echo "<h4>OAuth Client " . ($index + 1) . ":</h4>";
                echo "<ul>";
                echo "<li><strong>Client ID:</strong> " . ($oauthClient['client_id'] ?? 'Not found') . "</li>";
                echo "<li><strong>Client Type:</strong> " . ($oauthClient['client_type'] ?? 'Not found') . "</li>";
                
                if (isset($oauthClient['android_info'])) {
                    echo "<li><strong>Package Name:</strong> " . ($oauthClient['android_info']['package_name'] ?? 'Not found') . "</li>";
                    echo "<li><strong>Certificate Hash:</strong> " . ($oauthClient['android_info']['certificate_hash'] ?? 'Not found') . "</li>";
                    
                    // Check if certificate hash matches expected SHA-1
                    $expectedHash = 'f7fa7bac3388cabac62dfd8a09ede29c3c508010';
                    $actualHash = strtolower($oauthClient['android_info']['certificate_hash'] ?? '');
                    
                    if ($actualHash === $expectedHash) {
                        echo "<li style='color: green;'>✅ Certificate hash matches expected SHA-1</li>";
                    } else {
                        echo "<li style='color: red;'>❌ Certificate hash mismatch</li>";
                        echo "<li>Expected: <code>$expectedHash</code></li>";
                        echo "<li>Actual: <code>$actualHash</code></li>";
                    }
                } else {
                    echo "<li style='color: orange;'>⚠️ No Android info (Web client)</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>❌ OAuth client is NOT configured (empty array)</p>";
        }
        
        // API key
        echo "<h3>API Key</h3>";
        if (isset($client['api_key'][0]['current_key'])) {
            echo "<p><strong>API Key:</strong> " . substr($client['api_key'][0]['current_key'], 0, 20) . "...</p>";
        } else {
            echo "<p style='color: red;'>❌ API key not found</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ google-services.json file not found</p>";
}

echo "<h2>Error Analysis</h2>";
echo "<p><strong>Error Code 10:</strong> Developer error - usually OAuth configuration issues</p>";
echo "<p><strong>Error Code 12502:</strong> Network error - Google Play Services connectivity issues</p>";

echo "<h2>Recommended Actions</h2>";
echo "<ol>";
echo "<li><strong>For Error 10:</strong> Verify OAuth client configuration above</li>";
echo "<li><strong>For Error 12502:</strong> Check Google Play Services on device</li>";
echo "<li><strong>Test on physical device</strong> (not emulator)</li>";
echo "<li><strong>Ensure internet connectivity</strong></li>";
echo "<li><strong>Update Google Play Services</strong> if needed</li>";
echo "</ol>";

echo "<h2>SHA-1 Fingerprint Verification</h2>";
echo "<p><strong>Expected SHA-1:</strong> <code>F7:FA:7B:AC:33:88:CA:BA:C6:2D:FD:8A:09:ED:E2:9C:3C:50:80:10</code></p>";
echo "<p><strong>Expected Hash:</strong> <code>f7fa7bac3388cabac62dfd8a09ede29c3c508010</code></p>";

echo "<h2>Package Name Verification</h2>";
echo "<p><strong>Expected Package:</strong> <code>com.example.nutrisaur11</code></p>";

echo "<h2>Next Steps</h2>";
echo "<p>1. If OAuth client is missing or incorrect, follow the Firebase Console setup</p>";
echo "<p>2. If certificate hash doesn't match, update the SHA-1 fingerprint in Firebase</p>";
echo "<p>3. Test on a physical device with Google Play Services</p>";
echo "<p>4. Check Android logs for more specific error details</p>";
?>
