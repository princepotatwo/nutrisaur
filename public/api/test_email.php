<?php
// Simple email test endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
    error_log("=== EMAIL TEST START ===");
    
    // Test 1: Check if email_config.php exists
    $emailConfigPath = __DIR__ . "/../../../email_config.php";
    error_log("Looking for email_config.php at: $emailConfigPath");
    
    if (!file_exists($emailConfigPath)) {
        error_log("ERROR: email_config.php not found at $emailConfigPath");
        echo json_encode(['success' => false, 'error' => 'email_config.php not found', 'path' => $emailConfigPath]);
        exit;
    }
    
    error_log("email_config.php found successfully");
    
    // Test 2: Include email config
    try {
        require_once $emailConfigPath;
        error_log("email_config.php included successfully");
        error_log("SMTP_USERNAME: " . SMTP_USERNAME);
        error_log("SMTP_HOST: " . SMTP_HOST);
        error_log("SMTP_PORT: " . SMTP_PORT);
    } catch (Exception $e) {
        error_log("ERROR including email_config.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to include email_config.php', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 3: Check PHPMailer files
    $phpmailerPath = __DIR__ . "/../../vendor/phpmailer/phpmailer/src/";
    error_log("Looking for PHPMailer at: $phpmailerPath");
    
    if (!file_exists($phpmailerPath . "PHPMailer.php")) {
        error_log("ERROR: PHPMailer.php not found");
        echo json_encode(['success' => false, 'error' => 'PHPMailer not found']);
        exit;
    }
    
    error_log("PHPMailer files found");
    
    // Test 4: Include PHPMailer
    try {
        require_once $phpmailerPath . "Exception.php";
        require_once $phpmailerPath . "PHPMailer.php";
        require_once $phpmailerPath . "SMTP.php";
        error_log("PHPMailer files included successfully");
    } catch (Exception $e) {
        error_log("ERROR including PHPMailer: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to include PHPMailer', 'details' => $e->getMessage()]);
        exit;
    }
    
    // Test 5: Create and send test email
    try {
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\SMTP;
        use PHPMailer\PHPMailer\Exception;
        
        $mail = new PHPMailer(true);
        
        error_log("PHPMailer object created");
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        error_log("SMTP settings configured");
        
        // Recipients
        $mail->setFrom(SMTP_USERNAME, 'Nutrisaur Test');
        $mail->addAddress('kevinpingol123@gmail.com', 'Kevin Pingol');
        
        error_log("Recipients configured");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Nutrisaur Email Test - ' . date('Y-m-d H:i:s');
        $mail->Body = '
        <h2>Email Test Successful!</h2>
        <p>This is a test email from Nutrisaur to verify that the email system is working correctly.</p>
        <p><strong>Test Details:</strong></p>
        <ul>
            <li>From: ' . SMTP_USERNAME . '</li>
            <li>To: kevinpingol123@gmail.com</li>
            <li>Time: ' . date('Y-m-d H:i:s') . '</li>
            <li>SMTP Host: ' . SMTP_HOST . '</li>
            <li>SMTP Port: ' . SMTP_PORT . '</li>
        </ul>
        <p>If you received this email, the email system is working correctly!</p>
        ';
        
        $mail->AltBody = 'This is a test email from Nutrisaur. If you received this, the email system is working!';
        
        error_log("Email content prepared");
        
        // Send email
        $result = $mail->send();
        
        if ($result) {
            error_log("Email sent successfully!");
            echo json_encode([
                'success' => true,
                'message' => 'Test email sent successfully to kevinpingol123@gmail.com',
                'details' => [
                    'from' => SMTP_USERNAME,
                    'to' => 'kevinpingol123@gmail.com',
                    'subject' => $mail->Subject,
                    'smtp_host' => SMTP_HOST,
                    'smtp_port' => SMTP_PORT
                ]
            ]);
        } else {
            error_log("Email sending failed");
            echo json_encode(['success' => false, 'error' => 'Email sending failed', 'mailer_error' => $mail->ErrorInfo]);
        }
        
    } catch (Exception $e) {
        error_log("ERROR sending email: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Email sending failed',
            'details' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
    
} catch (Exception $e) {
    error_log("=== EMAIL TEST ERROR ===");
    error_log("Unexpected error: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    
    echo json_encode([
        'success' => false,
        'error' => 'Unexpected error during email test',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
