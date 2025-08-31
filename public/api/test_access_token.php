<?php
// Test access token generation step by step
header('Content-Type: application/json');

// Include database configuration
require_once __DIR__ . '/../config.php';

// Get database connection
$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Test Firebase Admin SDK file access
    $adminSdkPath = __DIR__ . '/../sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json';
    if (!file_exists($adminSdkPath)) {
        throw new Exception("Firebase Admin SDK file not found at: $adminSdkPath");
    }
    
    // Test service account JSON parsing
    $serviceAccount = json_decode(file_get_contents($adminSdkPath), true);
    if (!$serviceAccount || !isset($serviceAccount['project_id'])) {
        throw new Exception("Invalid Firebase service account JSON file");
    }
    
    // Test access token generation
    $accessToken = generateAccessToken($serviceAccount);
    if (!$accessToken) {
        throw new Exception("Failed to generate access token");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Access token generation test successful',
        'access_token_length' => strlen($accessToken),
        'access_token_preview' => substr($accessToken, 0, 20) . '...'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

// Function to generate access token using service account
function generateAccessToken($serviceAccount) {
    try {
        // Check if we have a cached token that's still valid
        $cacheFile = __DIR__ . '/fcm_token_cache.json';
        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['expires_at']) && $cached['expires_at'] > time()) {
                return $cached['access_token'];
            }
        }
        
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
        openssl_sign(
            $headerEncoded . '.' . $payloadEncoded,
            $signature,
            $serviceAccount['private_key'],
            'SHA256'
        );
        
        $signatureEncoded = base64url_encode($signature);
        $jwt = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
        
        // Exchange JWT for access token
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
            throw new Exception("cURL error: " . $curlError);
        }
        
        if ($httpCode == 200) {
            $tokenData = json_decode($response, true);
            if (isset($tokenData['access_token'])) {
                return $tokenData['access_token'];
            }
        }
        
        throw new Exception("Failed to generate access token. HTTP Code: " . $httpCode . " Response: " . $response);
        
    } catch (Exception $e) {
        throw new Exception("Error generating access token: " . $e->getMessage());
    }
}

// Helper function for base64url encoding
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>
