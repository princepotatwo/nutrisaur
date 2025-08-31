<?php
// Test JWT transmission to Google
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
    
    // Generate JWT
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
    
    // Test different ways of sending the JWT
    $testResults = [];
    
    // Method 1: http_build_query
    $postData1 = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);
    $testResults['method_1_http_build_query'] = [
        'post_data_length' => strlen($postData1),
        'post_data_preview' => substr($postData1, 0, 100) . '...',
        'jwt_in_post_data' => strpos($postData1, $jwt) !== false,
        'jwt_position' => strpos($postData1, $jwt)
    ];
    
    // Method 2: Manual concatenation
    $postData2 = 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . urlencode($jwt);
    $testResults['method_2_manual_concat'] = [
        'post_data_length' => strlen($postData2),
        'post_data_preview' => substr($postData2, 0, 100) . '...',
        'jwt_in_post_data' => strpos($postData2, $jwt) !== false,
        'jwt_position' => strpos($postData2, $jwt)
    ];
    
    // Method 3: Direct JWT in assertion
    $postData3 = 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . $jwt;
    $testResults['method_3_direct_jwt'] = [
        'post_data_length' => strlen($postData3),
        'post_data_preview' => substr($postData3, 0, 100) . '...',
        'jwt_in_post_data' => strpos($postData3, $jwt) !== false,
        'jwt_position' => strpos($postData3, $jwt)
    ];
    
    // Test actual transmission with method 3 (direct JWT)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        throw new Exception("cURL error: " . $curlError);
    }
    
    $responseData = json_decode($response, true);
    
    echo json_encode([
        'success' => $httpCode == 200,
        'http_code' => $httpCode,
        'response' => $responseData,
        'raw_response' => $response,
        'jwt_info' => [
            'length' => strlen($jwt),
            'header_decoded' => json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $headerEncoded)), true),
            'payload_decoded' => json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadEncoded)), true),
            'signature_length' => strlen($signatureEncoded)
        ],
        'test_results' => $testResults,
        'transmission_test' => [
            'method_used' => 'method_3_direct_jwt',
            'post_data_length' => strlen($postData3),
            'jwt_transmitted_correctly' => strpos($postData3, $jwt) !== false
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
