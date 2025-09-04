<?php
// Test verification email with HTML template
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
    
    // Create HTML email template
    $htmlMessage = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Verify Your Account</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .verification-code { 
                font-size: 32px; 
                font-weight: bold; 
                text-align: center; 
                color: #4CAF50; 
                padding: 20px; 
                background: white; 
                border: 2px solid #4CAF50; 
                border-radius: 10px; 
                margin: 20px 0; 
            }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Nutrisaur Account Verification</h1>
            </div>
            <div class='content'>
                <h2>Hello $username!</h2>
                <p>Thank you for registering with Nutrisaur. To complete your registration, please use the verification code below:</p>
                
                <div class='verification-code'>$verificationCode</div>
                
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This code will expire in 5 minutes</li>
                    <li>If you didn't request this verification, please ignore this email</li>
                    <li>For security, never share this code with anyone</li>
                </ul>
                
                <p>If you have any questions, please contact our support team.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 Nutrisaur. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Set headers for HTML email
    $headers = "From: kevinpingol123@gmail.com\r\n";
    $headers .= "Reply-To: kevinpingol123@gmail.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Send email
    $subject = "Verify Your Nutrisaur Account - Test";
    $result = mail($email, $subject, $htmlMessage, $headers);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Verification email sent successfully',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'subject' => $subject,
                'method' => 'PHP mail() with HTML template'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send verification email',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'subject' => $subject,
                'method' => 'PHP mail() with HTML template'
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
