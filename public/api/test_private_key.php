<?php
// Test private key format and JWT generation
header('Content-Type: application/json');

try {
    // Test Firebase Admin SDK file access
    $adminSdkPath = __DIR__ . '/../../sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json';
    if (!file_exists($adminSdkPath)) {
        throw new Exception("Firebase Admin SDK file not found at: $adminSdkPath");
    }
    
    // Test service account JSON parsing
    $serviceAccount = json_decode(file_get_contents($adminSdkPath), true);
    if (!$serviceAccount || !isset($serviceAccount['project_id'])) {
        throw new Exception("Invalid Firebase service account JSON file");
    }
    
    // Check private key format
    $privateKey = $serviceAccount['private_key'];
    $privateKeyInfo = [
        'length' => strlen($privateKey),
        'starts_with' => substr($privateKey, 0, 30) . '...',
        'ends_with' => '...' . substr($privateKey, -30),
        'contains_newlines' => strpos($privateKey, "\n") !== false,
        'contains_begin_marker' => strpos($privateKey, '-----BEGIN PRIVATE KEY-----') !== false,
        'contains_end_marker' => strpos($privateKey, '-----END PRIVATE KEY-----') !== false
    ];
    
    // Test JWT generation step by step
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
    
    // Test OpenSSL private key loading
    $privateKeyResource = openssl_pkey_get_private($privateKey);
    if (!$privateKeyResource) {
        throw new Exception("Failed to load private key with OpenSSL: " . openssl_error_string());
    }
    
    $keyDetails = openssl_pkey_get_details($privateKeyResource);
    openssl_free_key($privateKeyResource);
    
    $signature = '';
    $signResult = openssl_sign(
        $headerEncoded . '.' . $payloadEncoded,
        $signature,
        $privateKey,
        'SHA256'
    );
    
    if (!$signResult) {
        throw new Exception("Failed to sign JWT: " . openssl_error_string());
    }
    
    $signatureEncoded = base64url_encode($signature);
    $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    
    echo json_encode([
        'success' => true,
        'message' => 'Private key and JWT generation test successful',
        'project_id' => $serviceAccount['project_id'],
        'client_email' => $serviceAccount['client_email'],
        'private_key_info' => $privateKeyInfo,
        'key_details' => [
            'type' => $keyDetails['type'],
            'bits' => $keyDetails['bits']
        ],
        'jwt_length' => strlen($jwt),
        'jwt_preview' => substr($jwt, 0, 50) . '...'
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
