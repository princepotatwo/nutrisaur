<?php
// Fix private key format if needed
header('Content-Type: application/json');

try {
    // Test Firebase Admin SDK file access
    $adminSdkPath = __DIR__ . '/../../sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json';
    if (!file_exists($adminSdkPath)) {
        throw new Exception("Firebase Admin SDK file not found at: $adminSdkPath");
    }
    
    // Read the original JSON file
    $jsonContent = file_get_contents($adminSdkPath);
    $serviceAccount = json_decode($jsonContent, true);
    
    if (!$serviceAccount || !isset($serviceAccount['project_id'])) {
        throw new Exception("Invalid Firebase service account JSON file");
    }
    
    // Check if private key needs fixing
    $privateKey = $serviceAccount['private_key'];
    $originalLength = strlen($privateKey);
    
    // Check if private key has proper formatting
    $needsFixing = false;
    $issues = [];
    
    if (strpos($privateKey, '-----BEGIN PRIVATE KEY-----') === false) {
        $needsFixing = true;
        $issues[] = 'Missing BEGIN marker';
    }
    
    if (strpos($privateKey, '-----END PRIVATE KEY-----') === false) {
        $needsFixing = true;
        $issues[] = 'Missing END marker';
    }
    
    // Check if newlines are preserved
    if (strpos($privateKey, "\n") === false) {
        $needsFixing = true;
        $issues[] = 'Missing newlines';
    }
    
    // Try to fix the private key if needed
    if ($needsFixing) {
        // Try to restore proper formatting
        $fixedKey = "-----BEGIN PRIVATE KEY-----\n";
        
        // Extract the base64 content (remove markers and whitespace)
        $base64Content = preg_replace('/-----BEGIN PRIVATE KEY-----|-----END PRIVATE KEY-----|\s+/', '', $privateKey);
        
        // Split into 64-character lines
        $chunks = str_split($base64Content, 64);
        $fixedKey .= implode("\n", $chunks);
        $fixedKey .= "\n-----END PRIVATE KEY-----\n";
        
        // Test if the fixed key works
        $privateKeyResource = openssl_pkey_get_private($fixedKey);
        if ($privateKeyResource) {
            $keyDetails = openssl_pkey_get_details($privateKeyResource);
            openssl_free_key($privateKeyResource);
            
            if ($keyDetails['bits'] == 2048) {
                $serviceAccount['private_key'] = $fixedKey;
                $needsFixing = false;
                $issues[] = 'Fixed: Restored proper formatting';
            }
        }
    }
    
    // Test JWT generation with current/fixed private key
    $header = [
        'alg' => 'RS256',
        'typ' => 'JWT'
    ];
    
    $time = time();
    $payload = [
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $time + 3600,
        'iat' => $time
    ];
    
    $headerEncoded = base64url_encode(json_encode($header));
    $payloadEncoded = base64url_encode(json_encode($payload));
    
    $signature = '';
    $signResult = openssl_sign(
        $headerEncoded . '.' . $payloadEncoded,
        $signature,
        $serviceAccount['private_key'],
        'SHA256'
    );
    
    if (!$signResult) {
        throw new Exception("Failed to sign JWT: " . openssl_error_string());
    }
    
    $signatureEncoded = base64url_encode($signature);
    $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    
    echo json_encode([
        'success' => true,
        'message' => 'Private key analysis complete',
        'original_length' => $originalLength,
        'current_length' => strlen($serviceAccount['private_key']),
        'needs_fixing' => $needsFixing,
        'issues' => $issues,
        'private_key_info' => [
            'starts_with' => substr($serviceAccount['private_key'], 0, 30) . '...',
            'ends_with' => '...' . substr($serviceAccount['private_key'], -30),
            'contains_newlines' => strpos($serviceAccount['private_key'], "\n") !== false,
            'contains_begin_marker' => strpos($serviceAccount['private_key'], '-----BEGIN PRIVATE KEY-----') !== false,
            'contains_end_marker' => strpos($serviceAccount['private_key'], '-----END PRIVATE KEY-----') !== false
        ],
        'jwt_generated' => strlen($jwt) > 0,
        'jwt_length' => strlen($jwt)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

// Helper function for base64url encoding
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>
