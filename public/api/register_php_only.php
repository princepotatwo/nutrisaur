<?php
// PHP-only registration API - no Node.js dependencies
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

session_start();

// Simple email function using PHP mail()
function sendSimpleEmail($to, $subject, $message) {
    $headers = "From: kevinpingol123@gmail.com\r\n";
    $headers .= "Reply-To: kevinpingol123@gmail.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Generate verification email HTML
function createVerificationEmail($username, $verificationCode) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
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
        <div class='container'>
            <div class='header'>
                <h1>Nutrisaur Account Verification</h1>
            </div>
            <div class='content'>
                <h2>Hello $username!</h2>
                <p>Thank you for registering with Nutrisaur. To complete your registration, please use the verification code below:</p>
                
                <div class='verification-code'>$verificationCode</div>
                
                <p><strong>Important:</strong></p>
                <ul>
                    <li>This code will expire in 5 minutes</li>
                    <li>If you didn't request this verification, please ignore this email</li>
                    <li>For security, never share this code with anyone</li>
                </ul>
                
                <p>If you have any questions, please contact our support team.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 Nutrisaur. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

try {
    require_once __DIR__ . "/../../config.php";
    
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'No data received']);
            exit;
        }
        
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
            exit;
        }
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        
        // Generate verification code (4 digits)
        $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $verificationExpiry = date('Y-m-d H:i:s', time() + 300); // 5 minutes
        
        // Hash password and insert user with verification data
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, verification_sent_at, created_at) VALUES (:username, :email, :password, :verification_code, :verification_expiry, NOW(), NOW())");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':verification_code', $verificationCode);
        $stmt->bindParam(':verification_expiry', $verificationExpiry);
        
        if ($stmt->execute()) {
            $userId = $pdo->lastInsertId();
            
            // Send verification email using PHP mail()
            $emailSubject = "Verify Your Nutrisaur Account";
            $emailBody = createVerificationEmail($username, $verificationCode);
            
            $emailSent = sendSimpleEmail($email, $emailSubject, $emailBody);
            
            if ($emailSent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Please check your email for verification code.',
                    'data' => [
                        'user_id' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'requires_verification' => true,
                        'email_sent' => true,
                        'verification_code' => $verificationCode // For testing only
                    ]
                ]);
            } else {
                // If email fails, still create account but mark as unverified
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! However, verification email could not be sent. Please contact support.',
                    'data' => [
                        'user_id' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'requires_verification' => true,
                        'email_sent' => false,
                        'verification_code' => $verificationCode // For testing only
                    ]
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?>
