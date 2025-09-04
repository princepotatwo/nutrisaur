<?php
/**
 * Verification System using Resend API
 * Clean implementation for verifying email codes
 */

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

// Include required files
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/DatabaseAPI.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }
    
    $email = trim($data['email'] ?? '');
    $verificationCode = trim($data['verification_code'] ?? '');
    
    // Validation
    if (empty($email) || empty($verificationCode)) {
        echo json_encode(['success' => false, 'message' => 'Please provide email and verification code']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }
    
    try {
        // Initialize database
        $db = DatabaseAPI::getInstance();
        $pdo = $db->getPDO();
        
        // Find user
        $stmt = $pdo->prepare("SELECT user_id, username, verification_code, verification_code_expires, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        if ($user['email_verified']) {
            echo json_encode(['success' => false, 'message' => 'Email is already verified']);
            exit;
        }
        
        // Check if code is expired
        if (strtotime($user['verification_code_expires']) < time()) {
            echo json_encode(['success' => false, 'message' => 'Verification code has expired']);
            exit;
        }
        
        // Verify code
        if ($user['verification_code'] !== $verificationCode) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
            exit;
        }
        
        // Mark email as verified
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = ?");
        $result = $stmt->execute([$user['user_id']]);
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Failed to verify email']);
            exit;
        }
        
        // Send welcome email using Resend
        sendWelcomeEmail($email, $user['username']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully! Welcome to Nutrisaur.',
            'data' => [
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $email,
                'email_verified' => true
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

/**
 * Send welcome email using Resend API
 */
function sendWelcomeEmail($email, $username) {
    try {
        // Resend API configuration
        $resendApiKey = 're_Vk6LhArD_KSi2P8EiHxz2CSwh9N2cAUZB';
        $fromEmail = 'kevinpingol123@gmail.com'; // Your Gmail address
        
        // Create welcome email
        $emailData = [
            'from' => $fromEmail,
            'to' => [$email],
            'subject' => "Welcome to Nutrisaur! 🧪",
            'html' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0;'>🧪 Nutrisaur</h1>
                </div>
                <div style='background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #333;'>Welcome, $username! 🎉</h2>
                    <p style='color: #666; font-size: 16px;'>Your email has been successfully verified!</p>
                    <div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                        <p style='color: #155724; margin: 0; font-size: 16px;'>✅ Your account is now active and ready to use!</p>
                    </div>
                    <p style='color: #666; font-size: 14px;'>You can now log in to your Nutrisaur account and start exploring our features.</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                    <p style='color: #999; font-size: 12px; text-align: center;'>Best regards,<br>Nutrisaur Team</p>
                </div>
            </div>
            "
        ];
        
        // Send email using Resend API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $resendApiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Don't fail verification if welcome email fails
        if ($httpCode != 200) {
            error_log("Welcome email failed: HTTP $httpCode");
        }
        
    } catch (Exception $e) {
        error_log("Welcome email exception: " . $e->getMessage());
    }
}
?>
