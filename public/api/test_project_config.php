<?php
// Test project configuration and service account details
header('Content-Type: application/json');

try {
    // Test Firebase Admin SDK file access
    $adminSdkPath = __DIR__ . '/../../sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json';
    if (!file_exists($adminSdkPath)) {
        throw new Exception("Firebase Admin SDK file not found at: $adminSdkPath");
    }
    
    // Read and parse the JSON file
    $jsonContent = file_get_contents($adminSdkPath);
    $serviceAccount = json_decode($jsonContent, true);
    
    if (!$serviceAccount || !isset($serviceAccount['project_id'])) {
        throw new Exception("Invalid Firebase service account JSON file");
    }
    
    // Extract key information
    $projectInfo = [
        'project_id' => $serviceAccount['project_id'],
        'client_email' => $serviceAccount['client_email'],
        'private_key_id' => $serviceAccount['private_key_id'],
        'client_id' => $serviceAccount['client_id'],
        'type' => $serviceAccount['type']
    ];
    
    // Test if we can generate a valid JWT
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
    
    // Test OAuth2 token exchange with detailed error reporting
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . $jwt);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception("cURL error: " . $curlError);
    }
    
    // Parse response headers and body
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);
    
    $responseData = json_decode($responseBody, true);
    
    echo json_encode([
        'success' => $httpCode == 200,
        'http_code' => $httpCode,
        'response_headers' => $responseHeaders,
        'response_body' => $responseData,
        'raw_response' => $responseBody,
        'project_info' => $projectInfo,
        'jwt_info' => [
            'length' => strlen($jwt),
            'header' => json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $headerEncoded)), true),
            'payload' => json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadEncoded)), true),
            'signature_length' => strlen($signatureEncoded)
        ],
        'diagnostic_info' => [
            'jwt_generation_success' => true,
            'openssl_sign_success' => true,
            'private_key_valid' => true,
            'service_account_format_valid' => true
        ]
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
