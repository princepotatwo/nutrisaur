<?php
// Verify service account details safely
header('Content-Type: application/json');

try {
    // Test Firebase Admin SDK file access
    $adminSdkPath = __DIR__ . '/../../sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json';
    if (!file_exists($adminSdkPath)) {
        throw new Exception("Firebase Admin SDK file not found at: $adminSdkPath");
    }
    
    // Read the JSON file
    $jsonContent = file_get_contents($adminSdkPath);
    $jsonSize = strlen($jsonContent);
    
    // Parse JSON
    $serviceAccount = json_decode($jsonContent, true);
    if (!$serviceAccount) {
        throw new Exception("Failed to parse JSON: " . json_last_error_msg());
    }
    
    // Check required fields
    $requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email', 'client_id', 'auth_uri', 'token_uri', 'auth_provider_x509_cert_url', 'client_x509_cert_url'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($serviceAccount[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missingFields));
    }
    
    // Verify service account details
    $verificationResults = [
        'json_file_size' => $jsonSize,
        'type' => $serviceAccount['type'],
        'project_id' => $serviceAccount['project_id'],
        'private_key_id' => $serviceAccount['private_key_id'],
        'client_email' => $serviceAccount['client_email'],
        'client_id' => $serviceAccount['client_id'],
        'auth_uri' => $serviceAccount['auth_uri'],
        'token_uri' => $serviceAccount['token_uri'],
        'private_key_length' => strlen($serviceAccount['private_key']),
        'private_key_format' => [
            'starts_with_begin' => strpos($serviceAccount['private_key'], '-----BEGIN PRIVATE KEY-----') === 0,
            'ends_with_end' => strpos($serviceAccount['private_key'], '-----END PRIVATE KEY-----') !== false,
            'contains_newlines' => strpos($serviceAccount['private_key'], "\n") !== false,
            'base64_content_length' => strlen(preg_replace('/-----BEGIN PRIVATE KEY-----|-----END PRIVATE KEY-----|\s+/', '', $serviceAccount['private_key']))
        ]
    ];
    
    // Test if private key can be loaded by OpenSSL
    $privateKeyResource = openssl_pkey_get_private($serviceAccount['private_key']);
    if ($privateKeyResource) {
        $keyDetails = openssl_pkey_get_details($privateKeyResource);
        openssl_free_key($privateKeyResource);
        
        $verificationResults['openssl_verification'] = [
            'success' => true,
            'key_type' => $keyDetails['type'],
            'key_bits' => $keyDetails['bits'],
            'key_size' => $keyDetails['bits']
        ];
    } else {
        $verificationResults['openssl_verification'] = [
            'success' => false,
            'error' => openssl_error_string()
        ];
    }
    
    // Test JWT generation
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
    
    if ($signResult) {
        $signatureEncoded = base64url_encode($signature);
        $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
        
        $verificationResults['jwt_generation'] = [
            'success' => true,
            'jwt_length' => strlen($jwt),
            'header_length' => strlen($headerEncoded),
            'payload_length' => strlen($payloadEncoded),
            'signature_length' => strlen($signatureEncoded)
        ];
    } else {
        $verificationResults['jwt_generation'] = [
            'success' => false,
            'error' => openssl_error_string()
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Service account verification complete',
        'verification_results' => $verificationResults
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
