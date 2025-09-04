<?php
/**
 * Simple Email Verification Endpoint
 * Works with existing routing and uses DatabaseAPI
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
require_once __DIR__ . "/DatabaseAPI.php";
require_once __DIR__ . "/EmailService.php";
require_once __DIR__ . "/../../email_config.php";

// Initialize DatabaseAPI
$db = DatabaseAPI::getInstance();

if (!$db->isDatabaseAvailable()) {
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
            handleRegistration($db, $data);
            break;
        case 'verify':
            handleVerification($db, $data);
            break;
        case 'resend':
            handleResend($db, $data);
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
function handleRegistration($db, $data) {
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
        // Use DatabaseAPI's registerUser method
        $result = $db->registerUser($username, $email, $password);
        
        if ($result['success']) {
            if (isset($result['data']['requires_verification']) && $result['data']['requires_verification']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Please check your email for verification code.',
                    'requires_verification' => true,
                    'data' => $result['data']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Redirecting to dashboard...',
                    'requires_verification' => false,
                    'data' => $result['data']
                ]);
            }
        } else {
            echo json_encode($result);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
}

/**
 * Handle email verification
 */
function handleVerification($db, $data) {
    $email = trim($data['email'] ?? '');
    $code = trim($data['verification_code'] ?? '');
    
    if (empty($email) || empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Please provide email and verification code']);
        return;
    }
    
    try {
        // Get PDO connection from DatabaseAPI
        $pdo = $db->getPDO();
        
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
            try {
                $emailService = new EmailService();
                $emailService->sendWelcomeEmail($email, $user['username']);
            } catch (Exception $e) {
                // Log error but don't fail verification
                error_log("Welcome email failed: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Email verified successfully! Redirecting to dashboard...',
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
        echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
    }
}

/**
 * Handle resend verification code
 */
function handleResend($db, $data) {
    $email = trim($data['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Please provide email address']);
        return;
    }
    
    try {
        // Get PDO connection from DatabaseAPI
        $pdo = $db->getPDO();
        
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
            try {
                $emailService = new EmailService();
                $emailSent = $emailService->sendVerificationEmail($email, $user['username'], $verificationCode);
                
                if ($emailSent) {
                    echo json_encode(['success' => true, 'message' => 'Verification code sent successfully! Please check your email.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again.']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to send verification email: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update verification code. Please try again.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to resend verification code: ' . $e->getMessage()]);
    }
}
?>
