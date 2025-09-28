<?php
// Simple Google OAuth test without database
header('Content-Type: application/json');

// Enable CORS for Android app
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST; // Fallback to POST data
    }
    
    $action = $input['action'] ?? '';
    $idToken = $input['id_token'] ?? '';
    $email = $input['email'] ?? '';
    $name = $input['name'] ?? '';
    
    if ($action !== 'google_signin') {
        throw new Exception('Invalid action: ' . $action);
    }
    
    if (empty($idToken) || empty($email)) {
        throw new Exception('Missing required parameters');
    }
    
    // Google OAuth Client IDs
    $webClientId = '43537903747-ppt6bbcnfa60p0hchanl32equ9c3b0ao.apps.googleusercontent.com';
    $androidClientId = '43537903747-2nd9mtmm972ucoirho2sthkqlu8mct6b.apps.googleusercontent.com';
    
    // Verify the token with Google
    $response = file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=$idToken");
    
    if (!$response) {
        throw new Exception('Failed to verify token with Google');
    }
    
    $tokenInfo = json_decode($response, true);
    
    if (!$tokenInfo) {
        throw new Exception('Invalid token response from Google');
    }
    
    // Verify the audience (client ID)
    if ($tokenInfo['aud'] !== $webClientId && $tokenInfo['aud'] !== $androidClientId) {
        throw new Exception('Invalid client ID');
    }
    
    // Verify the email matches
    if ($tokenInfo['email'] !== $email) {
        throw new Exception('Email mismatch');
    }
    
    // Return success response (without database operations)
    echo json_encode([
        'success' => true,
        'message' => 'Google OAuth verification successful',
        'user' => [
            'email' => $tokenInfo['email'],
            'name' => $name,
            'type' => 'community_user'
        ],
        'token_info' => $tokenInfo
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
