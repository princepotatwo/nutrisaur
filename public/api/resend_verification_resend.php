<?php
/**
 * Resend Verification Code using Resend API
 * Clean implementation for resending verification codes
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
    
    // Validation
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
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
        $stmt = $pdo->prepare("SELECT user_id, username, email_verified FROM users WHERE email = ?");
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
        
        // Generate new verification code
        $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Update user with new verification code
        $stmt = $pdo->prepare("UPDATE users SET verification_code = ?, verification_code_expires = ? WHERE user_id = ?");
        $result = $stmt->execute([$verificationCode, $expiresAt, $user['user_id']]);
        
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Failed to update verification code']);
            exit;
        }
        
        // Send email using Resend API
        $emailSent = sendResendEmail($email, $user['username'], $verificationCode);
        
        echo json_encode([
            'success' => true,
            'message' => 'Verification code sent successfully! Please check your email.',
            'data' => [
                'email' => $email,
                'email_sent' => $emailSent,
                'verification_code' => $verificationCode // For testing purposes
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to resend verification code: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

/**
 * Send email using Resend API
 */
function sendResendEmail($email, $username, $verificationCode) {
    try {
        // Resend API configuration
        $resendApiKey = 're_Vk6LhArD_KSi2P8EiHxz2CSwh9N2cAUZB';
        $fromEmail = 'kevinpingol123@gmail.com'; // Your Gmail address
        
        // Create email data
        $emailData = [
            'from' => $fromEmail,
            'to' => [$email],
            'subject' => "Nutrisaur Verification Code: $verificationCode",
            'html' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0;'>🧪 Nutrisaur</h1>
                </div>
                <div style='background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #333;'>Hello $username!</h2>
                    <p style='color: #666; font-size: 16px;'>Here's your new verification code:</p>
                    <div style='background: #f8f9fa; border: 2px solid #4CAF50; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;'>
                        <span style='font-size: 32px; font-weight: bold; color: #4CAF50; letter-spacing: 5px;'>$verificationCode</span>
                    </div>
                    <p style='color: #666; font-size: 14px;'>This code will expire in 5 minutes.</p>
                    <p style='color: #666; font-size: 14px;'>If you didn't request this verification, please ignore this email.</p>
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
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 && !$curlError) {
            return true;
        } else {
            error_log("Resend API failed: HTTP $httpCode, Error: $curlError");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Email sending exception: " . $e->getMessage());
        return false;
    }
}
?>
