<?php
/**
 * Email Configuration for Nutrisaur
 * PHPMailer Setup
 */

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');  // Gmail SMTP
define('SMTP_PORT', 587);              // TLS port
define('SMTP_USERNAME', 'kevinpingol123@gmail.com');  // Your Gmail address
define('SMTP_PASSWORD', 'eoax bdlz bogm ikjk');     // Your app password
define('SMTP_SECURE', 'tls');          // TLS encryption

// From email settings
define('FROM_EMAIL', 'kevinpingol123@gmail.com');
define('FROM_NAME', 'Nutrisaur Nutrition App');

// Verification settings
define('VERIFICATION_CODE_EXPIRY', 300); // 5 minutes in seconds
define('VERIFICATION_CODE_LENGTH', 4);

// Email templates
define('VERIFICATION_SUBJECT', 'Verify Your Nutrisaur Account');
define('VERIFICATION_BODY_TEMPLATE', '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
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
    <div class="container">
        <div class="header">
            <h1>Nutrisaur Account Verification</h1>
        </div>
        <div class="content">
            <h2>Hello {username}!</h2>
            <p>Thank you for registering with Nutrisaur. To complete your registration, please use the verification code below:</p>
            
            <div class="verification-code">{verification_code}</div>
            
            <p><strong>Important:</strong></p>
            <ul>
                <li>This code will expire in 5 minutes</li>
                <li>If you didn\'t request this verification, please ignore this email</li>
                <li>For security, never share this code with anyone</li>
            </ul>
            
            <p>If you have any questions, please contact our support team.</p>
        </div>
        <div class="footer">
            <p>&copy; 2025 Nutrisaur. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
');

// Function to get email configuration
function getEmailConfig() {
    return [
        'host' => SMTP_HOST,
        'port' => SMTP_PORT,
        'username' => SMTP_USERNAME,
        'password' => SMTP_PASSWORD,
        'secure' => SMTP_SECURE,
        'from_email' => FROM_EMAIL,
        'from_name' => FROM_NAME
    ];
}

// Function to generate verification code
function generateVerificationCode() {
    return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
}

// Function to get verification expiry time
function getVerificationExpiryTime() {
    return date('Y-m-d H:i:s', time() + VERIFICATION_CODE_EXPIRY);
}

// Function to check if verification code is expired
function isVerificationCodeExpired($expiryTime) {
    return strtotime($expiryTime) < time();
}
?>
