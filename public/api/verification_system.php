<?php
/**
 * Email Verification System
 * Works alongside existing DatabaseAPI without modifying core functionality
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . "/EmailService.php";
require_once __DIR__ . "/../../email_config.php";

class VerificationSystem {
    private $pdo;
    private $emailService;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->emailService = new EmailService();
    }
    
    /**
     * Process registration with email verification
     */
    public function processRegistration($username, $email, $password) {
        try {
            // Check if user already exists
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Generate verification code
            $verificationCode = generateVerificationCode();
            $verificationExpiry = getVerificationExpiryTime();
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user with verification data
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, verification_sent_at, email_verified, created_at) VALUES (:username, :email, :password, :verification_code, :verification_expiry, NOW(), 0, NOW())");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':verification_code', $verificationCode);
            $stmt->bindParam(':verification_expiry', $verificationExpiry);
            
            if ($stmt->execute()) {
                $userId = $this->pdo->lastInsertId();
                
                // Send verification email
                $emailSent = $this->emailService->sendVerificationEmail($email, $username, $verificationCode);
                
                if ($emailSent) {
                    return [
                        'success' => true,
                        'message' => 'Registration successful! Please check your email for verification code.',
                        'requires_verification' => true,
                        'data' => [
                            'user_id' => $userId,
                            'username' => $username,
                            'email' => $email
                        ]
                    ];
                } else {
                    return [
                        'success' => true,
                        'message' => 'Registration successful! However, verification email could not be sent. Please contact support.',
                        'requires_verification' => false,
                        'data' => [
                            'user_id' => $userId,
                            'username' => $username,
                            'email' => $email
                        ]
                    ];
                }
            } else {
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Verify email with code
     */
    public function verifyEmail($email, $code) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id, username, verification_code, verification_code_expires, email_verified FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            if ($user['email_verified']) {
                return ['success' => false, 'message' => 'Email is already verified'];
            }
            
            if ($user['verification_code'] !== $code) {
                return ['success' => false, 'message' => 'Invalid verification code'];
            }
            
            if (isVerificationCodeExpired($user['verification_code_expires'])) {
                return ['success' => false, 'message' => 'Verification code has expired'];
            }
            
            // Mark email as verified
            $stmt = $this->pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user['user_id']);
            
            if ($stmt->execute()) {
                // Send welcome email
                $this->emailService->sendWelcomeEmail($email, $user['username']);
                
                return [
                    'success' => true,
                    'message' => 'Email verified successfully! You can now login.',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'email' => $email
                    ]
                ];
            } else {
                return ['success' => false, 'message' => 'Verification failed. Please try again.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Verification error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Resend verification code
     */
    public function resendVerification($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id, username, email_verified, verification_sent_at FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            if ($user['email_verified']) {
                return ['success' => false, 'message' => 'Email is already verified'];
            }
            
            // Check rate limit (1 minute)
            if ($user['verification_sent_at']) {
                $lastSent = new DateTime($user['verification_sent_at']);
                $now = new DateTime();
                $diff = $now->diff($lastSent);
                
                if ($diff->i < 1) {
                    return ['success' => false, 'message' => 'Please wait at least 1 minute before requesting another code'];
                }
            }
            
            // Generate new verification code
            $verificationCode = generateVerificationCode();
            $verificationExpiry = getVerificationExpiryTime();
            
            // Update user with new code
            $stmt = $this->pdo->prepare("UPDATE users SET verification_code = :code, verification_code_expires = :expiry, verification_sent_at = NOW() WHERE user_id = :user_id");
            $stmt->bindParam(':code', $verificationCode);
            $stmt->bindParam(':expiry', $verificationExpiry);
            $stmt->bindParam(':user_id', $user['user_id']);
            
            if ($stmt->execute()) {
                // Send new verification email
                $emailSent = $this->emailService->sendVerificationEmail($email, $user['username'], $verificationCode);
                
                if ($emailSent) {
                    return ['success' => true, 'message' => 'Verification code sent successfully! Please check your email.'];
                } else {
                    return ['success' => false, 'message' => 'Failed to send verification email. Please try again.'];
                }
            } else {
                return ['success' => false, 'message' => 'Failed to update verification code. Please try again.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Resend error: ' . $e->getMessage()];
        }
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        require_once __DIR__ . "/../config.php";
        $pdo = getDatabaseConnection();
        
        if (!$pdo) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        $verificationSystem = new VerificationSystem($pdo);
        
        // Get input data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            $data = $_POST;
        }
        
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'register':
                $username = $data['username'] ?? '';
                $email = $data['email'] ?? '';
                $password = $data['password'] ?? '';
                
                if (empty($username) || empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
                    exit;
                }
                
                $result = $verificationSystem->processRegistration($username, $email, $password);
                echo json_encode($result);
                break;
                
            case 'verify':
                $email = $data['email'] ?? '';
                $code = $data['verification_code'] ?? '';
                
                if (empty($email) || empty($code)) {
                    echo json_encode(['success' => false, 'message' => 'Please provide email and verification code']);
                    exit;
                }
                
                $result = $verificationSystem->verifyEmail($email, $code);
                echo json_encode($result);
                break;
                
            case 'resend':
                $email = $data['email'] ?? '';
                
                if (empty($email)) {
                    echo json_encode(['success' => false, 'message' => 'Please provide email address']);
                    exit;
                }
                
                $result = $verificationSystem->resendVerification($email);
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
