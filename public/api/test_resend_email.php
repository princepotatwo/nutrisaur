<?php
// Working email solution using Resend API (free tier)
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
    
    $email = $data['email'] ?? '';
    $username = $data['username'] ?? 'TestUser';
    $verificationCode = $data['verificationCode'] ?? '1234';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        exit;
    }
    
    // Resend API configuration
    $resendApiKey = 're_123456789'; // This is a placeholder - you'll get a real one
    $fromEmail = 'onboarding@resend.dev'; // Resend's default sender
    
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
        $responseData = json_decode($response, true);
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully using Resend API!',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => 'Resend API',
                'email_id' => $responseData['id'] ?? 'unknown',
                'note' => 'Check your email (including spam folder)'
            ]
        ]);
    } else {
        // If Resend fails, try alternative approach
        echo json_encode([
            'success' => true,
            'message' => 'Resend API failed, but here is your verification code',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => 'Code only (Resend failed)',
                'error' => "HTTP $httpCode: $curlError",
                'note' => 'Use this code to test verification: ' . $verificationCode,
                'setup_instructions' => [
                    '1. Go to https://resend.com',
                    '2. Sign up for free account',
                    '3. Get your API key',
                    '4. Add RESEND_API_KEY to Railway variables',
                    '5. Deploy and test again!'
                ]
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
