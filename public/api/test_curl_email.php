<?php
// Test cURL email service using external APIs
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
    
    // Try multiple external email services
    $emailSent = false;
    $method = '';
    $error = '';
    
    // Method 1: Use a free email API service
    $emailData = [
        'to' => $email,
        'subject' => "Nutrisaur Verification Code: $verificationCode",
        'message' => "Hello $username! Your verification code is: $verificationCode"
    ];
    
    // Try using a simple email service (example with a free API)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/sandbox.mailgun.org/messages');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($emailData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode == 200 && !$curlError) {
        $emailSent = true;
        $method = 'cURL Mailgun API';
    } else {
        $error = "cURL failed: HTTP $httpCode, Error: $curlError";
        
        // Method 2: Try using a different approach - send to a local mail server
        $mailData = "To: $email\r\n";
        $mailData .= "From: kevinpingol123@gmail.com\r\n";
        $mailData .= "Subject: Nutrisaur Verification Code: $verificationCode\r\n";
        $mailData .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $mailData .= "\r\n";
        $mailData .= "Hello $username!\r\n\r\n";
        $mailData .= "Your Nutrisaur verification code is: $verificationCode\r\n\r\n";
        $mailData .= "Best regards,\r\nNutrisaur Team\r\n";
        
        // Try to send via local mail command
        $tempFile = tempnam(sys_get_temp_dir(), 'email_');
        file_put_contents($tempFile, $mailData);
        
        $command = "cat $tempFile | sendmail $email 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        unlink($tempFile); // Clean up
        
        if ($returnCode == 0) {
            $emailSent = true;
            $method = 'cURL sendmail';
        } else {
            $error .= "\nSendmail failed: " . implode("\n", $output);
        }
    }
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully using cURL',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => $method
            ]
        ]);
    } else {
        // If cURL methods fail, return the code anyway
        echo json_encode([
            'success' => true,
            'message' => 'cURL email failed, but here is your verification code',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => 'Code only (cURL failed)',
                'error' => $error,
                'note' => 'Use this code to test verification: ' . $verificationCode
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
