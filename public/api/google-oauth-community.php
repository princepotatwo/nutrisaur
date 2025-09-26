<?php
require_once '../DatabaseAPI.php';

// Set content type to JSON
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
    $profilePicture = $input['profile_picture'] ?? '';
    
    if ($action !== 'google_signin') {
        throw new Exception('Invalid action');
    }
    
    if (empty($idToken) || empty($email)) {
        throw new Exception('Missing required parameters');
    }
    
    // Google OAuth Client IDs (both web and Android)
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
    
    // Token is valid, proceed with user creation/authentication
    $userEmail = $tokenInfo['email'];
    $userName = $name ?: $tokenInfo['name'] ?? 'Google User';
    
    // Initialize database
    $db = new DatabaseAPI();
    
    if (!$db->isAvailable()) {
        throw new Exception('Database not available');
    }
    
    // Check if user exists in community_users table
    $existingUser = $db->select('community_users', '*', 'email = ?', [$userEmail]);
    
    if (empty($existingUser)) {
        // Create new user in community_users table
        $userData = [
            'email' => $userEmail,
            'name' => $userName,
            'password' => password_hash(uniqid('google_', true), PASSWORD_DEFAULT),
            'municipality' => 'Unknown',
            'barangay' => 'Unknown',
            'sex' => 'Unknown',
            'google_oauth' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $result = $db->insert('community_users', $userData);
        
        if (!$result) {
            throw new Exception('Failed to create user account');
        }
        
        $message = 'Account created successfully with Google Sign-In';
    } else {
        // User exists, update Google OAuth flag if needed
        if (!$existingUser[0]['google_oauth']) {
            $db->update('community_users', ['google_oauth' => 1], 'email = ?', [$userEmail]);
        }
        $message = 'Signed in successfully with Google';
    }
    
    // Start session
    session_start();
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['username'] = $userName;
    $_SESSION['logged_in'] = true;
    $_SESSION['user_type'] = 'community_user';
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $message,
        'user' => [
            'email' => $userEmail,
            'name' => $userName,
            'type' => 'community_user'
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
