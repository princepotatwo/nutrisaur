<?php
/**
 * Email Configuration Test Page
 * Use this page to test your email setup
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/api/EmailService.php';
require_once __DIR__ . '/../email_config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testEmail = $_POST['test_email'] ?? '';
    
    if (empty($testEmail)) {
        $error = 'Please enter a test email address';
    } else {
        try {
            $emailService = new EmailService();
            $result = $emailService->testEmailConfiguration($testEmail);
            
            if ($result['success']) {
                $message = 'Test email sent successfully! Check your inbox.';
            } else {
                $error = 'Failed to send test email: ' . $result['message'];
            }
        } catch (Exception $e) {
            $error = 'Email service error: ' . $e->getMessage();
        }
    }
}

// Get current email configuration
$emailConfig = getEmailConfig();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration Test - Nutrisaur</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 30px;
        }
        .config-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .config-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .config-label {
            font-weight: bold;
            color: #333;
        }
        .config-value {
            color: #666;
            font-family: monospace;
        }
        .test-form {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #45a049;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .steps {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .steps h3 {
            color: #1976d2;
            margin-top: 0;
        }
        .steps ol {
            margin: 0;
            padding-left: 20px;
        }
        .steps li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Email Configuration Test</h1>
        
        <div class="warning">
            <strong>Important:</strong> Before testing, make sure you have updated the email configuration in <code>email_config.php</code> with your actual Gmail credentials.
        </div>
        
        <div class="steps">
            <h3>Setup Instructions:</h3>
            <ol>
                <li>Edit <code>email_config.php</code> and replace <code>your-email@gmail.com</code> with your Gmail address</li>
                <li>Replace <code>your-app-password</code> with your Gmail App Password (not your regular password)</li>
                <li>To get an App Password:
                    <ul>
                        <li>Go to your Google Account settings</li>
                        <li>Enable 2-Step Verification if not already enabled</li>
                        <li>Go to Security → App passwords</li>
                        <li>Generate a new app password for "Mail"</li>
                    </ul>
                </li>
                <li>Save the file and test below</li>
            </ol>
        </div>
        
        <div class="config-section">
            <h3>Current Email Configuration:</h3>
            <div class="config-item">
                <span class="config-label">SMTP Host:</span>
                <span class="config-value"><?php echo htmlspecialchars($emailConfig['host']); ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP Port:</span>
                <span class="config-value"><?php echo htmlspecialchars($emailConfig['port']); ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP Username:</span>
                <span class="config-value"><?php echo htmlspecialchars($emailConfig['username']); ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">SMTP Password:</span>
                <span class="config-value"><?php echo $emailConfig['password'] === 'your-app-password' ? 'Not configured' : '***configured***'; ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">Security:</span>
                <span class="config-value"><?php echo htmlspecialchars($emailConfig['secure']); ?></span>
            </div>
            <div class="config-item">
                <span class="config-label">From Email:</span>
                <span class="config-value"><?php echo htmlspecialchars($emailConfig['from_email']); ?></span>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="test-form">
            <h3>Send Test Email:</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="test_email">Test Email Address:</label>
                    <input type="email" id="test_email" name="test_email" placeholder="Enter your email address" required>
                </div>
                <button type="submit">Send Test Email</button>
            </form>
        </div>
        
        <div class="config-section">
            <h3>API Endpoints:</h3>
            <p><strong>Registration:</strong> <code>POST /api/register.php</code></p>
            <p><strong>Email Verification:</strong> <code>POST /api/verify_email.php</code></p>
            <p><strong>Resend Verification:</strong> <code>POST /api/resend_verification.php</code></p>
            <p><strong>Login:</strong> <code>POST /api/login.php</code></p>
        </div>
    </div>
</body>
</html>
