<?php
/**
 * Email Service for Nutrisaur
 * Handles email sending using PHPMailer
 */

require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../../../email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $config;
    
    public function __construct() {
        $this->config = getEmailConfig();
        $this->initializeMailer();
    }
    
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
            $this->mailer->SMTPSecure = $this->config['secure'];
            $this->mailer->Port = $this->config['port'];
            
            // Default settings
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("EmailService initialization failed: " . $e->getMessage());
            throw new Exception("Email service initialization failed");
        }
    }
    
    /**
     * Send verification email
     * @param string $email User's email address
     * @param string $username User's username
     * @param string $verificationCode 4-digit verification code
     * @return bool Success status
     */
    public function sendVerificationEmail($email, $username, $verificationCode) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $username);
            $this->mailer->Subject = VERIFICATION_SUBJECT;
            
            // Prepare email body
            $body = str_replace(
                ['{username}', '{verification_code}'],
                [$username, $verificationCode],
                VERIFICATION_BODY_TEMPLATE
            );
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = "Hello $username! Your verification code is: $verificationCode. This code will expire in 5 minutes.";
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Verification email sent successfully to: $email");
                return true;
            } else {
                error_log("Failed to send verification email to: $email");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email after successful verification
     * @param string $email User's email address
     * @param string $username User's username
     * @return bool Success status
     */
    public function sendWelcomeEmail($email, $username) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $username);
            $this->mailer->Subject = 'Welcome to Nutrisaur!';
            
            $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <title>Welcome to Nutrisaur</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Welcome to Nutrisaur!</h1>
                    </div>
                    <div class='content'>
                        <h2>Hello $username!</h2>
                        <p>Your account has been successfully verified. Welcome to Nutrisaur!</p>
                        <p>You can now:</p>
                        <ul>
                            <li>Complete your nutrition screening</li>
                            <li>Get personalized food recommendations</li>
                            <li>Track your nutrition goals</li>
                            <li>Join nutrition programs</li>
                        </ul>
                        <p>Thank you for choosing Nutrisaur for your nutrition journey!</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2025 Nutrisaur. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $this->mailer->Body = $body;
            $this->mailer->AltBody = "Hello $username! Your account has been successfully verified. Welcome to Nutrisaur!";
            
            $result = $this->mailer->send();
            
            if ($result) {
                error_log("Welcome email sent successfully to: $email");
                return true;
            } else {
                error_log("Failed to send welcome email to: $email");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Welcome email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test email configuration
     * @param string $testEmail Email to send test to
     * @return array Test results
     */
    public function testEmailConfiguration($testEmail) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($testEmail);
            $this->mailer->Subject = 'Nutrisaur Email Test';
            $this->mailer->Body = '<h1>Email Test</h1><p>If you receive this email, your email configuration is working correctly.</p>';
            $this->mailer->AltBody = 'Email Test - If you receive this email, your email configuration is working correctly.';
            
            $result = $this->mailer->send();
            
            return [
                'success' => $result,
                'message' => $result ? 'Test email sent successfully' : 'Failed to send test email',
                'config' => [
                    'host' => $this->config['host'],
                    'port' => $this->config['port'],
                    'username' => $this->config['username'],
                    'secure' => $this->config['secure']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Email test failed: ' . $e->getMessage(),
                'config' => [
                    'host' => $this->config['host'],
                    'port' => $this->config['port'],
                    'username' => $this->config['username'],
                    'secure' => $this->config['secure']
                ]
            ];
        }
    }
}
?>
