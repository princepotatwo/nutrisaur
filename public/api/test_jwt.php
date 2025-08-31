<?php
// Simple JWT test endpoint to debug FCM issues
header('Content-Type: application/json');

try {
    $testResults = [];
    
    // Check if we have the required environment variables
    if (!isset($_ENV['FIREBASE_PROJECT_ID']) || 
        !isset($_ENV['FIREBASE_CLIENT_EMAIL']) || 
        !isset($_ENV['FIREBASE_PRIVATE_KEY_ID']) || 
        !isset($_ENV['FIREBASE_CLIENT_ID']) || 
        !isset($_ENV['FIREBASE_PRIVATE_KEY'])) {
        throw new Exception('Missing required Firebase environment variables');
    }
    
    $testResults['environment_check'] = '✅ All required variables present';
    
    // Test 1: Private key format
    $privateKey = $_ENV['FIREBASE_PRIVATE_KEY'];
    $privateKey = str_replace('\\n', "\n", $privateKey);
    
    if (!str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
        $privateKey = "-----BEGIN PRIVATE KEY-----\n" . $privateKey . "\n-----END PRIVATE KEY-----";
    }
    
    $testResults['private_key'] = [
        'original_length' => strlen($_ENV['FIREBASE_PRIVATE_KEY']),
        'processed_length' => strlen($privateKey),
        'has_begin_marker' => str_contains($privateKey, '-----BEGIN PRIVATE KEY-----'),
        'has_end_marker' => str_contains($privateKey, '-----END PRIVATE KEY-----'),
        'starts_with' => substr($privateKey, 0, 50),
        'ends_with' => substr($privateKey, -50)
    ];
    
    // Test 2: JWT Header generation
    $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $testResults['jwt_header'] = [
        'generated' => true,
        'length' => strlen($header),
        'value' => $header
    ];
    
    // Test 3: JWT Payload generation
    $time = time();
    $payload = base64_encode(json_encode([
        'iss' => $_ENV['FIREBASE_CLIENT_EMAIL'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $time + 3600,
        'iat' => $time
    ]));
    
    $testResults['jwt_payload'] = [
        'generated' => true,
        'length' => strlen($payload),
        'iss' => $_ENV['FIREBASE_CLIENT_EMAIL'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $time + 3600,
        'iat' => $time
    ];
    
    // Test 4: JWT Signature generation
    $signature = '';
    $signatureSuccess = openssl_sign($header . '.' . $payload, $signature, $privateKey, 'SHA256');
    
    if ($signatureSuccess) {
        $signature = base64_encode($signature);
        $jwt = $header . '.' . $payload . '.' . $signature;
        
        $testResults['jwt_signature'] = [
            'generated' => true,
            'signature_length' => strlen($signature),
            'jwt_length' => strlen($jwt),
            'jwt_starts_with' => substr($jwt, 0, 50) . '...'
        ];
        
        // Test 5: OAuth token exchange
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $testResults['oauth_exchange'] = [
                'success' => false,
                'error' => 'cURL error: ' . $curlError
            ];
        } else {
            $testResults['oauth_exchange'] = [
                'success' => $httpCode === 200,
                'http_code' => $httpCode,
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 200)
            ];
            
            if ($httpCode === 200) {
                $tokenData = json_decode($response, true);
                if (isset($tokenData['access_token'])) {
                    $testResults['oauth_exchange']['access_token'] = [
                        'received' => true,
                        'token_length' => strlen($tokenData['access_token']),
                        'token_starts_with' => substr($tokenData['access_token'], 0, 20) . '...',
                        'expires_in' => $tokenData['expires_in'] ?? 'not_set',
                        'token_type' => $tokenData['token_type'] ?? 'not_set'
                    ];
                } else {
                    $testResults['oauth_exchange']['access_token'] = [
                        'received' => false,
                        'response_keys' => array_keys($tokenData)
                    ];
                }
            }
        }
        
    } else {
        $testResults['jwt_signature'] = [
            'generated' => false,
            'error' => 'Failed to sign JWT with OpenSSL'
        ];
        
        // Get OpenSSL error details
        $opensslErrors = [];
        while ($error = openssl_error_string()) {
            $opensslErrors[] = $error;
        }
        $testResults['jwt_signature']['openssl_errors'] = $opensslErrors;
    }
    
    // Test 6: FCM endpoint test
    if (isset($testResults['oauth_exchange']['access_token']['received']) && 
        $testResults['oauth_exchange']['access_token']['received']) {
        
        $accessToken = $testResults['oauth_exchange']['access_token']['token_starts_with'];
        $projectId = $_ENV['FIREBASE_PROJECT_ID'];
        $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        $testResults['fcm_endpoint'] = [
            'url' => $fcmUrl,
            'project_id' => $projectId,
            'access_token_available' => true
        ];
        
        // Test FCM endpoint accessibility (without sending actual message)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $testResults['fcm_endpoint']['test_response'] = [
            'http_code' => $httpCode,
            'accessible' => $httpCode !== 404
        ];
    }
    
    $testResults['summary'] = [
        'jwt_generation' => isset($testResults['jwt_signature']['generated']) && $testResults['jwt_signature']['generated'],
        'oauth_exchange' => isset($testResults['oauth_exchange']['success']) && $testResults['oauth_exchange']['success'],
        'access_token_received' => isset($testResults['oauth_exchange']['access_token']['received']) && $testResults['oauth_exchange']['access_token']['received'],
        'fcm_endpoint_accessible' => isset($testResults['fcm_endpoint']['test_response']['accessible']) && $testResults['fcm_endpoint']['test_response']['accessible']
    ];
    
    echo json_encode($testResults, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
