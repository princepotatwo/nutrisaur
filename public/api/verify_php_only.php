<?php
// PHP-only verification API - no Node.js dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

try {
    require_once __DIR__ . "/../../config.php";
    
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'No data received']);
            exit;
        }
        
        $email = $data['email'] ?? '';
        $verificationCode = $data['verification_code'] ?? '';
        
        if (empty($email) || empty($verificationCode)) {
            echo json_encode(['success' => false, 'message' => 'Email and verification code are required']);
            exit;
        }
        
        // Validate verification code format
        if (!preg_match('/^\d{4}$/', $verificationCode)) {
            echo json_encode(['success' => false, 'message' => 'Verification code must be a 4-digit number']);
            exit;
        }
        
        // Find user with the given email and verification code
        $stmt = $pdo->prepare("SELECT user_id, username, email, verification_code, verification_code_expires, email_verified FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        if ($user['email_verified']) {
            echo json_encode(['success' => false, 'message' => 'Email is already verified']);
            exit;
        }
        
        // Check if verification code matches
        if ($user['verification_code'] !== $verificationCode) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
            exit;
        }
        
        // Check if verification code is expired
        if (strtotime($user['verification_code_expires']) < time()) {
            echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please request a new one.']);
            exit;
        }
        
        // Mark email as verified
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Email verified successfully! Welcome to Nutrisaur!',
                'data' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'email_verified' => true
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to verify email']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log("Verification error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?>
