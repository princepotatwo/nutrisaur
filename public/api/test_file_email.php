<?php
// Test file-based email system
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
    
    // Create email content
    $subject = "Nutrisaur Verification Code: $verificationCode";
    $message = "Hello $username!\n\nYour Nutrisaur verification code is: $verificationCode\n\nBest regards,\nNutrisaur Team";
    
    // Method 1: Create email file in system mail directory
    $emailSent = false;
    $method = '';
    $error = '';
    
    // Create email file with proper format
    $emailContent = "From: kevinpingol123@gmail.com\n";
    $emailContent .= "To: $email\n";
    $emailContent .= "Subject: $subject\n";
    $emailContent .= "Content-Type: text/plain; charset=UTF-8\n";
    $emailContent .= "Date: " . date('r') . "\n";
    $emailContent .= "Message-ID: <" . time() . "." . rand(1000, 9999) . "@nutrisaur.com>\n";
    $emailContent .= "\n";
    $emailContent .= $message;
    
    // Try to save to mail queue directory
    $mailDir = '/var/mail/';
    if (!is_dir($mailDir)) {
        $mailDir = '/tmp/';
    }
    
    $emailFile = $mailDir . 'nutrisaur_' . time() . '_' . rand(1000, 9999) . '.eml';
    
    if (file_put_contents($emailFile, $emailContent)) {
        // Try to process the email file
        $command = "cat $emailFile | sendmail $email 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        if ($returnCode == 0) {
            $emailSent = true;
            $method = 'File-based sendmail';
        } else {
            $error = "Sendmail failed: " . implode("\n", $output);
        }
        
        // Clean up the file
        unlink($emailFile);
    } else {
        $error = "Failed to create email file";
    }
    
    // Method 2: Try using mail command directly
    if (!$emailSent) {
        $tempFile = tempnam(sys_get_temp_dir(), 'email_');
        file_put_contents($tempFile, $message);
        
        $command = "mail -s \"$subject\" -r kevinpingol123@gmail.com $email < $tempFile 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        unlink($tempFile);
        
        if ($returnCode == 0) {
            $emailSent = true;
            $method = 'File-based mail command';
        } else {
            $error .= "\nMail command failed: " . implode("\n", $output);
        }
    }
    
    // Method 3: Try using mutt if available
    if (!$emailSent) {
        $tempFile = tempnam(sys_get_temp_dir(), 'email_');
        file_put_contents($tempFile, $message);
        
        $command = "mutt -s \"$subject\" -e 'set from=kevinpingol123@gmail.com' $email < $tempFile 2>&1";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        unlink($tempFile);
        
        if ($returnCode == 0) {
            $emailSent = true;
            $method = 'File-based mutt';
        } else {
            $error .= "\nMutt failed: " . implode("\n", $output);
        }
    }
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully using file-based method',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => $method
            ]
        ]);
    } else {
        // If file-based methods fail, return the code anyway
        echo json_encode([
            'success' => true,
            'message' => 'File-based email failed, but here is your verification code',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => 'Code only (file-based failed)',
                'error' => $error,
                'note' => 'Use this code to test verification: ' . $verificationCode
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
