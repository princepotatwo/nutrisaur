<?php
// Railway-specific email solution
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
    
    // Check Railway environment variables
    $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort = getenv('SMTP_PORT') ?: '587';
    $smtpUsername = getenv('SMTP_USERNAME') ?: 'kevinpingol123@gmail.com';
    $smtpPassword = getenv('SMTP_PASSWORD') ?: ''; // This should be your app password
    $fromEmail = getenv('FROM_EMAIL') ?: 'kevinpingol123@gmail.com';
    
    $emailSent = false;
    $method = '';
    $error = '';
    
    // Method 1: Try using PHPMailer with Railway environment
    if (!empty($smtpPassword)) {
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;
            
            $mail->setFrom($fromEmail, 'Nutrisaur');
            $mail->addAddress($email, $username);
            $mail->Subject = "Nutrisaur Verification Code: $verificationCode";
            $mail->Body = "Hello $username!\n\nYour Nutrisaur verification code is: $verificationCode\n\nBest regards,\nNutrisaur Team";
            
            if ($mail->send()) {
                $emailSent = true;
                $method = 'PHPMailer with Railway SMTP';
            } else {
                $error = "PHPMailer failed: " . $mail->ErrorInfo;
            }
        } catch (Exception $e) {
            $error = "PHPMailer exception: " . $e->getMessage();
        }
    }
    
    // Method 2: Try using a free email service API
    if (!$emailSent) {
        $emailData = [
            'to' => $email,
            'from' => $fromEmail,
            'subject' => "Nutrisaur Verification Code: $verificationCode",
            'text' => "Hello $username! Your verification code is: $verificationCode"
        ];
        
        // Try Resend API (free tier available)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . (getenv('RESEND_API_KEY') ?: 're_123456789')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 200 && !$curlError) {
            $emailSent = true;
            $method = 'Resend API';
        } else {
            $error .= "\nResend API failed: HTTP $httpCode, Error: $curlError";
        }
    }
    
    // Method 3: Try using Brevo (formerly Sendinblue) API
    if (!$emailSent) {
        $emailData = [
            'sender' => [
                'name' => 'Nutrisaur',
                'email' => $fromEmail
            ],
            'to' => [
                [
                    'email' => $email,
                    'name' => $username
                ]
            ],
            'subject' => "Nutrisaur Verification Code: $verificationCode",
            'textContent' => "Hello $username! Your verification code is: $verificationCode"
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . (getenv('BREVO_API_KEY') ?: 'xkeysib-123456789')
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode == 201 && !$curlError) {
            $emailSent = true;
            $method = 'Brevo API';
        } else {
            $error .= "\nBrevo API failed: HTTP $httpCode, Error: $curlError";
        }
    }
    
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully using Railway method',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => $method
            ]
        ]);
    } else {
        // Always return the verification code for testing
        echo json_encode([
            'success' => true,
            'message' => 'Email sending failed, but here is your verification code',
            'data' => [
                'to' => $email,
                'username' => $username,
                'verification_code' => $verificationCode,
                'method' => 'Code only (email failed)',
                'error' => $error,
                'note' => 'Use this code to test verification: ' . $verificationCode,
                'setup_instructions' => [
                    '1. Go to Railway Dashboard',
                    '2. Add environment variables:',
                    '   - SMTP_HOST=smtp.gmail.com',
                    '   - SMTP_PORT=587',
                    '   - SMTP_USERNAME=kevinpingol123@gmail.com',
                    '   - SMTP_PASSWORD=your_app_password',
                    '   - FROM_EMAIL=kevinpingol123@gmail.com',
                    '3. Or use Resend/Brevo API keys'
                ]
            ]
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
