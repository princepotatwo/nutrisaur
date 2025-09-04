<?php
// Simple Resend test using PHP SDK
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'No data received']);
        exit;
    }
    
    $email = $data['email'] ?? 'kevinpingol123@gmail.com';
    $username = $data['username'] ?? 'TestUser';
    $verificationCode = $data['verificationCode'] ?? '1234';
    
    try {
        // Using Resend PHP SDK
        $resend = Resend::client('re_Vk6LhArD_KSi2P8EiHxz2CSwh9N2cAUZB');
        
        $result = $resend->emails->send([
            'from' => 'kevinpingol123@gmail.com',
            'to' => $email,
            'subject' => "Nutrisaur Verification Code: $verificationCode",
            'html' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0;'>🧪 Nutrisaur</h1>
                </div>
                <div style='background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #333;'>Hello $username!</h2>
                    <p style='color: #666; font-size: 16px;'>Your verification code is:</p>
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
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully using Resend PHP SDK!',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => 'Resend PHP SDK',
                'email_id' => $result->id ?? 'unknown',
                'note' => 'Check your email (including spam folder)'
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Resend PHP SDK failed: ' . $e->getMessage(),
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => 'Resend PHP SDK',
                'error' => $e->getMessage()
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
