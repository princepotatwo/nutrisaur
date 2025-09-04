<?php
// Fast registration API - doesn't wait for email sending
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
            
            // Return success immediately without waiting for email
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! Please check your email for verification code.',
                'data' => [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'requires_verification' => true,
                    'verification_code' => $verificationCode // For testing only
                ]
            ]);
            
            // Send email in background (don't wait for it)
            try {
                // Simple email using PHP mail() - fast and doesn't block
                $subject = "Verify Your Nutrisaur Account";
                $message = "Hello $username! Your verification code is: $verificationCode. This code will expire in 5 minutes.";
                $headers = "From: kevinpingol123@gmail.com\r\n";
                
                // Send email without waiting for result
                mail($email, $subject, $message, $headers);
                
                // Log that email was sent (for debugging)
                error_log("Email sent to: $email with code: $verificationCode");
                
            } catch (Exception $e) {
                // Don't fail registration if email fails
                error_log("Email sending failed: " . $e->getMessage());
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
