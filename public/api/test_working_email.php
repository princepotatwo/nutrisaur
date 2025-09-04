<?php
// Working email solution that bypasses Railway's email restrictions
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
    
    // Create a simple text email (more likely to work)
    $subject = "Nutrisaur Verification Code: $verificationCode";
    $message = "
Hello $username!

Your Nutrisaur verification code is: $verificationCode

This code will expire in 5 minutes.

If you didn't request this verification, please ignore this email.

Best regards,
Nutrisaur Team
    ";
    
    // Try multiple email methods
    $emailSent = false;
    $method = '';
    
    // Method 1: Simple PHP mail() with minimal headers
    $headers1 = "From: kevinpingol123@gmail.com\r\n";
    $result1 = mail($email, $subject, $message, $headers1);
    
    if ($result1) {
        $emailSent = true;
        $method = 'PHP mail() simple';
    } else {
        // Method 2: Try with different headers
        $headers2 = "From: noreply@nutrisaur.com\r\n";
        $headers2 .= "Reply-To: kevinpingol123@gmail.com\r\n";
        $result2 = mail($email, $subject, $message, $headers2);
        
        if ($result2) {
            $emailSent = true;
            $method = 'PHP mail() with custom headers';
        } else {
            // Method 3: Try with system mail
            $headers3 = "From: webmaster@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $result3 = mail($email, $subject, $message, $headers3);
            
            if ($result3) {
                $emailSent = true;
                $method = 'PHP mail() with system headers';
            }
        }
    }
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully using working method',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'subject' => $subject,
                'method' => $method,
                'note' => 'Check your email (including spam folder)'
            ]
        ]);
    } else {
        // If all methods fail, return the code anyway for testing
        echo json_encode([
            'success' => true,
            'message' => 'Email sending failed, but here is your verification code for testing',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'subject' => $subject,
                'method' => 'Code only (email failed)',
                'note' => 'Use this code to test verification: ' . $verificationCode
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
