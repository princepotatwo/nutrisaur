<?php
/**
 * Simple Email Verification System
 * Standard implementation following common patterns
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include required files
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/EmailService.php";
require_once __DIR__ . "/../../email_config.php";

// Get database connection
$pdo = getDatabaseConnection();

if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'register':
            handleRegistration($pdo, $data);
            break;
        case 'verify':
            handleVerification($pdo, $data);
            break;
        case 'resend':
            handleResend($pdo, $data);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

/**
 * Handle user registration with email verification
 */
function handleRegistration($pdo, $data) {
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        return;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        return;
    }
    
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            return;
        }
        
        // Generate verification code
        $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiryTime = date('Y-m-d H:i:s', time() + 300); // 5 minutes
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, verification_sent_at, email_verified, created_at) VALUES (?, ?, ?, ?, ?, NOW(), 0, NOW())");
        
        if ($stmt->execute([$username, $email, $hashedPassword, $verificationCode, $expiryTime])) {
            $userId = $pdo->lastInsertId();
            
            // Send verification email
            $emailService = new EmailService();
            $emailSent = $emailService->sendVerificationEmail($email, $username, $verificationCode);
            
            if ($emailSent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Please check your email for the verification code.',
                    'requires_verification' => true,
                    'data' => [
                        'user_id' => $userId,
                        'username' => $username,
                        'email' => $email
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! However, verification email could not be sent. Please contact support.',
                    'requires_verification' => false,
                    'data' => [
                        'user_id' => $userId,
                        'username' => $username,
                        'email' => $email
                    ]
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
        }
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
    }
}

/**
 * Handle email verification
 */
function handleVerification($pdo, $data) {
    $email = trim($data['email'] ?? '');
    $code = trim($data['verification_code'] ?? '');
    
    if (empty($email) || empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Please provide email and verification code']);
        return;
    }
    
    try {
        // Find user
        $stmt = $pdo->prepare("SELECT user_id, username, verification_code, verification_code_expires, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($user['email_verified']) {
            echo json_encode(['success' => false, 'message' => 'Email is already verified']);
            return;
        }
        
        if ($user['verification_code'] !== $code) {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
            return;
        }
        
        // Check if code is expired
        if (strtotime($user['verification_code_expires']) < time()) {
            echo json_encode(['success' => false, 'message' => 'Verification code has expired']);
            return;
        }
        
        // Mark email as verified
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = ?");
        
        if ($stmt->execute([$user['user_id']])) {
            // Send welcome email
            $emailService = new EmailService();
            $emailService->sendWelcomeEmail($email, $user['username']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Email verified successfully! You can now login.',
                'data' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $email
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Verification failed. Please try again.']);
        }
    } catch (Exception $e) {
        error_log("Verification error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Verification failed. Please try again.']);
    }
}

/**
 * Handle resend verification code
 */
function handleResend($pdo, $data) {
    $email = trim($data['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please provide email address']);
        return;
    }
    
    try {
        // Find user
        $stmt = $pdo->prepare("SELECT user_id, username, email_verified, verification_sent_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($user['email_verified']) {
            echo json_encode(['success' => false, 'message' => 'Email is already verified']);
            return;
        }
        
        // Check rate limit (1 minute)
        if ($user['verification_sent_at']) {
            $lastSent = strtotime($user['verification_sent_at']);
            if (time() - $lastSent < 60) {
                echo json_encode(['success' => false, 'message' => 'Please wait at least 1 minute before requesting another code']);
                return;
            }
        }
        
        // Generate new verification code
        $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiryTime = date('Y-m-d H:i:s', time() + 300); // 5 minutes
        
        // Update user
        $stmt = $pdo->prepare("UPDATE users SET verification_code = ?, verification_code_expires = ?, verification_sent_at = NOW() WHERE user_id = ?");
        
        if ($stmt->execute([$verificationCode, $expiryTime, $user['user_id']])) {
            // Send new verification email
            $emailService = new EmailService();
            $emailSent = $emailService->sendVerificationEmail($email, $user['username'], $verificationCode);
            
            if ($emailSent) {
                echo json_encode(['success' => true, 'message' => 'Verification code sent successfully! Please check your email.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update verification code. Please try again.']);
        }
    } catch (Exception $e) {
        error_log("Resend error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to resend verification code. Please try again.']);
    }
}
?>
