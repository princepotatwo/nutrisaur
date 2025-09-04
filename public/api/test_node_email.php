<?php
// Test Node.js email service
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
    
    try {
        // Call Node.js email service
        $nodeScript = __DIR__ . "/../../email-service-simple.js";
        $command = "node -e \"
            const emailService = require('$nodeScript');
            emailService.sendVerificationEmail('$email', '$username', '$verificationCode')
                .then(result => {
                    console.log('EMAIL_RESULT:' + result);
                    process.exit(result ? 0 : 1);
                })
                .catch(error => {
                    console.error('EMAIL_ERROR:' + error.message);
                    process.exit(1);
                });
        \"";
        
        $output = [];
        $returnCode = 0;
        exec($command . " 2>&1", $output, $returnCode);
        
        // Check if email was sent successfully
        $emailSent = false;
        foreach ($output as $line) {
            if (strpos($line, 'Email sent successfully') !== false) {
                $emailSent = true;
                break;
            }
        }
        
        if ($emailSent) {
            echo json_encode([
                'success' => true,
                'message' => 'Email sent successfully using Node.js',
                'data' => [
                    'to' => $email,
                    'username' => $username,
                    'verification_code' => $verificationCode,
                    'method' => 'Node.js email service'
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send email using Node.js',
                'data' => [
                    'to' => $email,
                    'username' => $username,
                    'verification_code' => $verificationCode,
                    'method' => 'Node.js email service',
                    'output' => $output,
                    'return_code' => $returnCode
                ]
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error testing Node.js email: ' . $e->getMessage(),
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => 'Node.js email service'
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
