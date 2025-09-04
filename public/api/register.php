<?php
// Enable error reporting for debugging
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
    // Use the same working approach as debug_database_api.php
    require_once __DIR__ . "/../config.php";
    require_once __DIR__ . "/EmailService.php";
    require_once __DIR__ . "/../../email_config.php";
    
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
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
        
        // Generate verification code
        $verificationCode = generateVerificationCode();
        $verificationExpiry = getVerificationExpiryTime();
        
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
            
            // Send verification email
            try {
                $emailService = new EmailService();
                $emailSent = $emailService->sendVerificationEmail($email, $username, $verificationCode);
                
                if ($emailSent) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Registration successful! Please check your email for verification code.',
                        'data' => [
                            'user_id' => $userId,
                            'username' => $username,
                            'email' => $email,
                            'requires_verification' => true
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
                            'email_sent' => false
                        ]
                    ]);
                }
            } catch (Exception $e) {
                error_log("Email service error: " . $e->getMessage());
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! However, verification email could not be sent. Please contact support.',
                    'data' => [
                        'user_id' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'requires_verification' => true,
                        'email_sent' => false
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
    error_log("Register API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?> 