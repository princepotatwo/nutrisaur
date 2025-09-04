<?php
// Test SendGrid API email service
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
    
    // SendGrid API configuration (you would need to set these up)
    $sendgridApiKey = getenv('SENDGRID_API_KEY') ?: 'your_sendgrid_api_key_here';
    $fromEmail = getenv('FROM_EMAIL') ?: 'kevinpingol123@gmail.com';
    
    // If SendGrid is not configured, try alternative methods
    if ($sendgridApiKey === 'your_sendgrid_api_key_here') {
        // Method 1: Try using a free email service API
        $emailData = [
            'to' => $email,
            'from' => $fromEmail,
            'subject' => "Nutrisaur Verification Code: $verificationCode",
            'text' => "Hello $username! Your verification code is: $verificationCode"
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer re_123456789' // This would be your actual API key
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 && !$curlError) {
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully using Resend API',
                'data' => [
                    'to' => $email,
                    'username' => $username,
                    'verification_code' => $verificationCode,
                    'method' => 'Resend API'
                ]
            ]);
        } else {
            // Method 2: Try using a different free email service
            $emailData = [
                'to' => $email,
                'subject' => "Nutrisaur Verification Code: $verificationCode",
                'message' => "Hello $username! Your verification code is: $verificationCode"
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'api-key: xkeysib-123456789' // This would be your actual API key
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode == 200 && !$curlError) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Email sent successfully using Brevo API',
                    'data' => [
                        'to' => $email,
                        'username' => $username,
                        'verification_code' => $verificationCode,
                        'method' => 'Brevo API'
                    ]
                ]);
            } else {
                // If all external APIs fail, return the code anyway
                echo json_encode([
                    'success' => true,
                    'message' => 'External email APIs failed, but here is your verification code',
                    'data' => [
                        'to' => $email,
                        'username' => $username,
                        'verification_code' => $verificationCode,
                        'method' => 'Code only (APIs not configured)',
                        'note' => 'Use this code to test verification: ' . $verificationCode,
                        'setup_note' => 'To enable email sending, configure SendGrid, Resend, or Brevo API keys'
                    ]
                ]);
            }
        }
    } else {
        // SendGrid is configured, use it
        $emailData = [
            'personalizations' => [
                [
                    'to' => [
                        ['email' => $email]
                    ]
                ]
            ],
            'from' => [
                'email' => $fromEmail
            ],
            'subject' => "Nutrisaur Verification Code: $verificationCode",
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => "Hello $username! Your verification code is: $verificationCode"
                ]
            ]
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $sendgridApiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 202 && !$curlError) {
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully using SendGrid',
                'data' => [
                    'to' => $email,
                    'username' => $username,
                    'verification_code' => $verificationCode,
                    'method' => 'SendGrid API'
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'SendGrid API failed',
                'data' => [
                    'to' => $email,
                    'username' => $username,
                    'verification_code' => $verificationCode,
                    'method' => 'SendGrid API',
                    'error' => "HTTP $httpCode: $curlError"
                ]
            ]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
