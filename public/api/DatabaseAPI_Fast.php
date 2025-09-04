<?php
/**
 * Fast Database API for Nutrisaur
 * Optimized for speed - no retry delays or connection testing
 */

// Include the config file for database connection functions
require_once __DIR__ . "/../../config.php";

// ========================================
// FAST DATABASE API CLASS
// ========================================

class DatabaseAPI_Fast {
    private $pdo;
    private static $instance = null;
    
    public function __construct() {
        // Fast connection - no retries, no delays
        $this->pdo = getDatabaseConnection();
        
        // Set PDO attributes if connection exists
        if ($this->pdo) {
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Test database connection (fast version)
     */
    public function testConnection() {
        return $this->pdo !== null;
    }
    
    /**
     * Register new user (fast version)
     */
    public function registerUser($username, $email, $password) {
        try {
            if (!$this->pdo) {
                return [
                    'success' => false, 
                    'message' => 'Database connection not available.'
                ];
            }
            
            // Check if username or email already exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Generate verification code
            $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $verificationExpiry = date('Y-m-d H:i:s', time() + 300); // 5 minutes
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user with verification data
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, verification_sent_at, created_at) VALUES (:username, :email, :password, :verification_code, :verification_expiry, NOW(), NOW())");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':verification_code', $verificationCode);
            $stmt->bindParam(':verification_expiry', $verificationExpiry);
            $stmt->execute();
            
            $userId = $this->pdo->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Registration successful!',
                'data' => [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'requires_verification' => true,
                    'verification_code' => $verificationCode
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Authenticate user (fast version)
     */
    public function authenticateUser($usernameOrEmail, $password) {
        try {
            if (!$this->pdo) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            // Check if input is email or username
            $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);
            
            if ($isEmail) {
                $stmt = $this->pdo->prepare("SELECT user_id, username, email, password, email_verified FROM users WHERE email = :email");
                $stmt->bindParam(':email', $usernameOrEmail);
            } else {
                $stmt = $this->pdo->prepare("SELECT user_id, username, email, password, email_verified FROM users WHERE username = :username");
                $stmt->bindParam(':username', $usernameOrEmail);
            }
            
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if (!$user['email_verified']) {
                    return [
                        'success' => false,
                        'message' => 'Please verify your email address before logging in.',
                        'requires_verification' => true,
                        'data' => [
                            'user_id' => $user['user_id'],
                            'email' => $user['email']
                        ]
                    ];
                }
                
                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user_type' => 'user',
                    'data' => $user
                ];
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication failed'];
        }
    }
    
    /**
     * Set user session
     */
    public function setUserSession($userData, $isAdmin = false) {
        if ($isAdmin) {
            $_SESSION['admin_id'] = $userData['user_id'];
            $_SESSION['admin_username'] = $userData['username'];
        } else {
            $_SESSION['user_id'] = $userData['user_id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['email'] = $userData['email'];
        }
    }
    
    /**
     * Verify email
     */
    public function verifyEmail($email, $verificationCode) {
        try {
            if (!$this->pdo) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            $stmt = $this->pdo->prepare("SELECT user_id, username, email, verification_code, verification_code_expires, email_verified FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            if ($user['email_verified']) {
                return ['success' => false, 'message' => 'Email is already verified'];
            }
            
            if ($user['verification_code'] !== $verificationCode) {
                return ['success' => false, 'message' => 'Invalid verification code'];
            }
            
            if (strtotime($user['verification_code_expires']) < time()) {
                return ['success' => false, 'message' => 'Verification code has expired'];
            }
            
            // Mark email as verified
            $stmt = $this->pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user['user_id']);
            $stmt->execute();
            
            return [
                'success' => true,
                'message' => 'Email verified successfully!',
                'data' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'email_verified' => true
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Verification error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed'];
        }
    }
}
?>
