<?php
/**
 * Nutrisaur Unified Database API
 * Single source of truth for all database operations
 * 
 * This file provides:
 * 1. Centralized database connection management
 * 2. All database operations (users, admin, FCM, notifications, etc.)
 * 3. API endpoints for mobile app and web dashboard
 * 4. Railway-optimized connection handling
 */

// Set timezone for DateTime calculations
date_default_timezone_set('Asia/Manila');

// FCM Notification Sending Function
function sendFCMNotification($fcmToken, $title, $body) {
    try {
        // Firebase Server Key (you should use Firebase Admin SDK in production)
        $serverKey = 'AAAAm8qJh5E:APA91bHGxsIiGsI7MF9C5-tHr2Q7a07TKvj2DaAVl6G9jpTH4hrFMRHswYAaIuPLpcU7vGxYQF9aMpap6-_J0I43oK07fpk4D13jU5Uj1WUza8rzuoRbx6U';
        
        $url = 'https://fcm.googleapis.com/fcm/send';
        
        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => 1
        ];
        
        $data = [
            'title' => $title,
            'body' => $body,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];
        
        $fields = [
            'to' => $fcmToken,
            'notification' => $notification,
            'data' => $data,
            'priority' => 'high'
        ];
        
        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $response = json_decode($result, true);
            if (isset($response['success']) && $response['success'] == 1) {
                return ['success' => true, 'response' => $result];
            } else {
                return ['success' => false, 'error' => $result];
            }
        } else {
            return ['success' => false, 'error' => "HTTP $httpCode: $result"];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// FCM Notification Sending Function using Firebase Admin SDK
function sendFCMNotificationToToken($fcmToken, $title, $body) {
    try {
        // Firebase Admin SDK Service Account Key
        $serviceAccountKey = [
            "type" => "service_account",
            "project_id" => "nutrisaur-ebf29",
            "private_key_id" => "1c2fa5d5bbf9ac2a6c0284101b5d1d256be9eafe",
            "private_key" => "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCbGuEbYIOTu6k8\naAkyfD1wS5wqsMoz/HoCP2yTA2cWVOzTTlWminXfzA4PxSKbU4NNATOzDm4C1Grt\nwIrYndO23PQnsSyMTk26eZoBU3Hu4yerDZBgPlNq5YmjzZO/VPkzWZRuf258TcNO\neS/bD81tc9KFshaVPEfBDDwlgvQMfPL5Gf93UurAAyOSwDT5a0QgpFdu95d4p/cV\nnPHD4QZUmUn8QaemTO8mez1m820nTxB17SXcdAG0++0n/dm6Ob1YBAt5Ey1mpHP5\nItKb7Ysh78OVcbO9pdMahwdrp7wJ9sEmFgojgp8uaR9ewjicxF5MmJVpfOzx7Qfb\nnQTtenDpAgMBAAECggEAAyKYdHHpE7/iOSV0zIkkjsc6EvmiCxbD4PuNaZO5VJy6\nPfOVf9L8Dffob6fEis8Co1HuZksMEzeRXIvpr1ye7msDh/5Cg1vqO0yaipzre4oq\nf5nvFkFV21FkP/Dd8MSgouNhJv8Hz/02AOyQxV585wT4nbMBvNnlxpoSNZBMXlvS\nIUoKKifJywnLR0w7uxAFdMH6PN9oPY4FzjEH/xddc4/vzvFfKrgcHji9E/e5+BEU\nVV56Z8KqmVdc+Njngzljfe7SSJmNMTs8FtoAu3b+9HHljS6DI3+l6xzz461UX7LW\nseQtBOFAWFxXh4svZ3Hf0bVnJyqlj9nxbcOE0MLX+QKBgQDVO+JtBiqiwWPl3II2\nU/l+ynH29eVpriNLaj2DJeSrnmUp1/s9M5HOrg07P5AQIiAJS/yAS21OjbnOYT22\n7lCENPlHYEXMz7Fs6/9+lxRGh/X3tLcyJQkvVtEi7v1Tixw3IEqGzS/x7LUIci//\nxiRwX/Xq3acxGmULoWVaQy/H5QKBgQC6NngCzJzrF/Wyze63r2f4KnUgcGeX6ah8\n3HiJqv+/UCZfKjYzPO8ueZ3vT4aMHe4Jm9cQspR/Az5ZaCOmFgDazofauPZPODtM\nrrKWqIH96+x2dwbu5aAEsAEk5FJuK/JKfsGxoSLhgm0DfF3ca3iXI3TfgML7nX8y\nOHMdYg/stQKBgAUvsbApKDxRK9bZaCleHYFh9yekj3HklGMvMFPSRh+OeLNt12SD\nrpYyUYwRXbWmvtS7Dmcobn4soEpOvyuF3Ft61l1QECKNIqmdi9dOYWXdxLPDp3kG\nwZRvLiMFYQ/5IDSPCoEA2JuvwC92Z4h3D0fUbazKu1hMZgzEXiy12aGpAoGAIU2m\njxGbKuyhE7aC8DUdyiOFySRxUpkGejZQFIcRsFycUD7TbLyEJnK3zVoSvTKJJQzL\nHQBjUIf6+bCHV6ftxTRU1chovOhYqrE/3XQLs6cjJljJU6abxNrZiYiQOYYAklQz\nPhqMi3pxFsOCYe6Spa1AtMxpkuirHAc+h03HfVUCgYEAk1q6ay6UCpdnWYNd9tT3\ndX4KWNaOC02cTIUKBNYiwtsUf4x4e+OL4Ol1N+gwSN3tyU0tZD89lfBVdmrq50EU\nKl4pUrzlIaLS3Z5EumgFA/4ptyzAXOFLtyrpjVFHjk+Rxt51TpMi4VuMHFZ4m3WW\nAhYxFogqCIeG0/J/9Q4sWpg=\n-----END PRIVATE KEY-----\n",
            "client_email" => "firebase-adminsdk-fbsvc@nutrisaur-ebf29.iam.gserviceaccount.com",
            "client_id" => "107962791067736498847",
            "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
            "token_uri" => "https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
            "client_x509_cert_url" => "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40nutrisaur-ebf29.iam.gserviceaccount.com",
            "universe_domain" => "googleapis.com"
        ];
        
        // Get access token using service account
        $accessToken = getFirebaseAccessToken($serviceAccountKey);
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Failed to get access token'];
        }
        
        $url = 'https://fcm.googleapis.com/v1/projects/nutrisaur-ebf29/messages:send';
        
        $message = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => [
                    'title' => $title,
                    'body' => $body,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ],
                'android' => [
                    'notification' => [
                        'sound' => 'default',
                        'notification_priority' => 'PRIORITY_HIGH'
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1
                        ]
                    ]
                ]
            ]
        ];
        
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            return ['success' => true, 'response' => $result];
        } else {
            return ['success' => false, 'error' => "HTTP $httpCode: $result"];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Helper function to get Firebase access token
function getFirebaseAccessToken($serviceAccountKey) {
    try {
        // Create JWT token
        $jwt = createJWT($serviceAccountKey);
        
        // Exchange JWT for access token
        $tokenUrl = $serviceAccountKey['token_uri'];
        $tokenData = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $response = json_decode($result, true);
            return $response['access_token'] ?? null;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error getting Firebase access token: " . $e->getMessage());
        return null;
    }
}

// Helper function to create JWT token
function createJWT($serviceAccountKey) {
    try {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $now = time();
        $payload = json_encode([
            'iss' => $serviceAccountKey['client_email'],
            'sub' => $serviceAccountKey['client_email'],
            'aud' => $serviceAccountKey['token_uri'],
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
        ]);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = '';
        $privateKey = $serviceAccountKey['private_key'];
        openssl_sign($base64Header . '.' . $base64Payload, $signature, $privateKey, 'SHA256');
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    } catch (Exception $e) {
        error_log("Error creating JWT: " . $e->getMessage());
        return null;
    }
}

// Include the config file for database connection functions
require_once __DIR__ . "/../../config.php";

// ========================================
// HELPER FUNCTIONS
// ========================================

/**
 * Parse pregnant status to fit database column size
 */
function parsePregnantStatus($pregnantString) {
    if (empty($pregnantString) || strtolower($pregnantString) === 'not applicable') {
        return 'N/A'; // Short form for not applicable
    }
    
    $pregnant = strtolower(trim($pregnantString));
    if (in_array($pregnant, ['yes', 'true', '1', 'pregnant'])) {
        return 'Yes';
    } elseif (in_array($pregnant, ['no', 'false', '0', 'not pregnant'])) {
        return 'No';
    }
    
    return 'N/A'; // Default to N/A if unclear
}

// ========================================
// DATABASE API CLASS
// ========================================

class DatabaseAPI {
    private $pdo;
    private $mysqli;
    private static $instance = null;
    
    public function __construct() {
        // Initialize connections with retry logic
        $this->pdo = $this->establishPDOConnection();
        $this->mysqli = getMysqliConnection();
        
        // Ensure PDO connection is properly set up
        if ($this->pdo) {
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        
        // Debug: Log connection status
        error_log("DatabaseAPI Constructor - PDO: " . ($this->pdo ? 'success' : 'failed') . ", MySQLi: " . ($this->mysqli ? 'success' : 'failed'));
        
        // If both connections failed, log a warning but don't crash
        if (!$this->pdo && !$this->mysqli) {
            error_log("Warning: Database connections failed. Application will run in limited mode.");
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
     * Establish PDO connection with retry logic
     */
    private function establishPDOConnection() {
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $pdo = getDatabaseConnection();
                if ($pdo) {
                    // Test the connection
                    $pdo->query("SELECT 1");
                    error_log("DatabaseAPI: PDO connection established on attempt $attempt");
                    return $pdo;
                }
            } catch (Exception $e) {
                error_log("DatabaseAPI: PDO connection attempt $attempt failed: " . $e->getMessage());
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                }
            }
        }
        
        error_log("DatabaseAPI: Failed to establish PDO connection after $maxRetries attempts");
        return null;
    }
    
    /**
     * Get PDO connection
     */
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Get MySQLi connection
     */
    public function getMysqli() {
        return $this->mysqli;
    }
    
    /**
     * Test database connection
     */
    public function testConnection() {
        if ($this->pdo) {
            try {
                $this->pdo->query("SELECT 1");
                return true;
            } catch (PDOException $e) {
                return false;
            }
        }
        return false;
    }
    
    /**
     * Get database configuration for debugging
     */
    public function getDatabaseConfig() {
        return getDatabaseConfig();
    }
    
    /**
     * Check if database is available
     */
    public function isDatabaseAvailable() {
        return $this->pdo !== null && $this->testConnection();
    }
    
    /**
     * Get database status
     */
    public function getDatabaseStatus() {
        return [
            'available' => $this->isDatabaseAvailable(),
            'pdo_connected' => $this->pdo !== null,
            'mysqli_connected' => $this->mysqli !== null,
            'test_passed' => $this->testConnection()
        ];
    }
    
    // ========================================
    // USER MANAGEMENT
    // ========================================
    
    /**
     * Authenticate user (login)
     */
    public function authenticateUser($usernameOrEmail, $password) {
        try {
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                return [
                    'success' => false, 
                    'message' => 'Database connection not available. Please check your database configuration.',
                    'database_status' => $this->getDatabaseStatus()
                ];
            }
            
            $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);
            
            // First check in users table
            if ($isEmail) {
                $stmt = $this->pdo->prepare("SELECT user_id, username, email, password FROM users WHERE email = :email");
                $stmt->bindParam(':email', $usernameOrEmail);
            } else {
                $stmt = $this->pdo->prepare("SELECT user_id, username, email, password FROM users WHERE username = :username");
                $stmt->bindParam(':username', $usernameOrEmail);
            }
            
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password'])) {
                    // Login successful - no email verification required for existing users
                    
                    // Check if user is also admin
                    $adminData = $this->getAdminByEmail($user['email']);
                    
                    // Update last login
                    $this->updateUserLastLogin($user['user_id']);
                    
                    return [
                        'success' => true,
                        'user_type' => 'user',
                        'data' => [
                            'user_id' => $user['user_id'],
                            'username' => $user['username'],
                            'email' => $user['email'],
                            'is_admin' => !empty($adminData),
                            'admin_data' => $adminData
                        ]
                    ];
                }
            }
            
            // Check admin table
            $adminData = $this->authenticateAdmin($usernameOrEmail, $password);
            if ($adminData['success']) {
                return $adminData;
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Register new user with email verification
     */
    public function registerUser($username, $email, $password) {
        try {
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                return [
                    'success' => false, 
                    'message' => 'Database connection not available. Please check your database configuration.',
                    'database_status' => $this->getDatabaseStatus()
                ];
            }
            
            // Include email verification files
            require_once __DIR__ . "/EmailService.php";
            require_once __DIR__ . "/../../email_config.php";
            
            $this->pdo->beginTransaction();
            
            // Check if username or email already exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Generate verification code
            $verificationCode = generateVerificationCode();
            $verificationExpiry = getVerificationExpiryTime();
            
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
            
            $this->pdo->commit();
            
            // Send verification email
            try {
                $emailService = new EmailService();
                $emailSent = $emailService->sendVerificationEmail($email, $username, $verificationCode);
                
                if ($emailSent) {
                    return [
                        'success' => true,
                        'message' => 'Registration successful! Please check your email for verification code.',
                        'data' => [
                            'user_id' => $userId,
                            'username' => $username,
                            'email' => $email,
                            'requires_verification' => true
                        ]
                    ];
                } else {
                    // If email fails, still create account but mark as unverified
                    return [
                        'success' => true,
                        'message' => 'Registration successful! However, verification email could not be sent. Please contact support.',
                        'data' => [
                            'user_id' => $userId,
                            'username' => $username,
                            'email' => $email,
                            'requires_verification' => true,
                            'email_sent' => false
                        ]
                    ];
                }
            } catch (Exception $e) {
                error_log("Email service error: " . $e->getMessage());
                return [
                    'success' => true,
                    'message' => 'Registration successful! However, verification email could not be sent. Please contact support.',
                    'data' => [
                        'user_id' => $userId,
                        'username' => $username,
                        'email' => $email,
                        'requires_verification' => true,
                        'email_sent' => false
                    ]
                ];
            }
            
        } catch (Exception $e) {
            if ($this->pdo) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id, username, email, created_at, last_login FROM users WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Update user last login
     */
    public function updateUserLastLogin($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Auto-login user after registration
     */
    public function autoLoginUser($userId, $username, $email) {
        try {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Set session data
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['is_admin'] = false;
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Update last login
            $this->updateUserLastLogin($userId);
            
            // Ensure session is written
            session_write_close();
            
            return true;
        } catch (Exception $e) {
            error_log("Auto-login failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Centralized session management - ensures consistent session handling across all pages
     */
    public function ensureSessionStarted() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return true;
    }
    
    /**
     * Set user session data consistently
     */
    public function setUserSession($userData, $isAdmin = false) {
        try {
            $this->ensureSessionStarted();
            
            if ($isAdmin) {
                $_SESSION['admin_id'] = $userData['admin_id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['email'] = $userData['email'];
                $_SESSION['is_admin'] = true;
                $_SESSION['role'] = $userData['role'];
            } else {
                $_SESSION['user_id'] = $userData['user_id'];
                $_SESSION['username'] = $userData['username'];
                $_SESSION['email'] = $userData['email'];
                $_SESSION['is_admin'] = $userData['is_admin'] ?? false;
                
                if (isset($userData['admin_data']) && is_array($userData['admin_data']) && !empty($userData['admin_data'])) {
                    $_SESSION['admin_id'] = $userData['admin_data']['admin_id'] ?? null;
                    $_SESSION['role'] = $userData['admin_data']['role'] ?? null;
                }
            }
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            // Update last login for regular users
            if (!$isAdmin && isset($userData['user_id'])) {
                $this->updateUserLastLogin($userData['user_id']);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Session setting failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isUserLoggedIn() {
        $this->ensureSessionStarted();
        return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
    }
    
    /**
     * Get current user session data
     */
    public function getCurrentUserSession() {
        $this->ensureSessionStarted();
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'admin_id' => $_SESSION['admin_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'is_admin' => $_SESSION['is_admin'] ?? false,
            'role' => $_SESSION['role'] ?? null
        ];
    }
    
    // ========================================
    // ADMIN MANAGEMENT
    // ========================================
    
    /**
     * Authenticate admin
     */
    public function authenticateAdmin($usernameOrEmail, $password) {
        try {
            $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);
            
            if ($isEmail) {
                $stmt = $this->pdo->prepare("SELECT admin_id, username, email, password, role FROM admin WHERE email = :email");
                $stmt->bindParam(':email', $usernameOrEmail);
            } else {
                $stmt = $this->pdo->prepare("SELECT admin_id, username, email, password, role FROM admin WHERE username = :username");
                $stmt->bindParam(':username', $usernameOrEmail);
            }
            
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $admin['password'])) {
                    // Update last login
                    $this->updateAdminLastLogin($admin['admin_id']);
                    
                    return [
                        'success' => true,
                        'user_type' => 'admin',
                        'data' => [
                            'admin_id' => $admin['admin_id'],
                            'username' => $admin['username'],
                            'email' => $admin['email'],
                            'is_admin' => true,
                            'role' => $admin['role']
                        ]
                    ];
                }
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get admin by email
     */
    public function getAdminByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT admin_id, username, email, role FROM admin WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Update admin last login
     */
    public function updateAdminLastLogin($adminId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE admin SET last_login = CURRENT_TIMESTAMP WHERE admin_id = :admin_id");
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ========================================
    // FCM TOKEN MANAGEMENT
    // ========================================
    
    /**
     * Register FCM token
     */
    public function registerFCMToken($fcmToken, $deviceName, $userEmail, $userBarangay, $appVersion, $platform) {
        try {
            // Debug logging
            error_log("FCM_DEBUG: Starting FCM token registration");
            error_log("FCM_DEBUG: FCM Token: " . $fcmToken);
            error_log("FCM_DEBUG: User Email: " . $userEmail);
            error_log("FCM_DEBUG: User Barangay: " . $userBarangay);
            error_log("FCM_DEBUG: Device Name: " . $deviceName);
            error_log("FCM_DEBUG: App Version: " . $appVersion);
            error_log("FCM_DEBUG: Platform: " . $platform);
            
            // Check if user exists in community_users table
            $stmt = $this->pdo->prepare("SELECT email FROM community_users WHERE email = :email");
            $stmt->bindParam(':email', $userEmail);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("FCM_DEBUG: User exists check - Found: " . ($user ? 'YES' : 'NO'));
            if ($user) {
                error_log("FCM_DEBUG: User found: " . $user['email']);
            }
            
            if ($user) {
                // Update existing user with FCM token
                error_log("FCM_DEBUG: Updating existing user with FCM token");
                $stmt = $this->pdo->prepare("UPDATE community_users SET 
                    fcm_token = :fcm_token
                    WHERE email = :email");
            } else {
                // Insert new user with FCM token (minimal data)
                error_log("FCM_DEBUG: Creating new user with FCM token");
                $stmt = $this->pdo->prepare("INSERT INTO community_users 
                    (email, fcm_token, barangay, screening_date) 
                    VALUES (:email, :fcm_token, :user_barangay, CURRENT_TIMESTAMP)");
            }
            
            // First, clear any existing FCM tokens for this user to prevent duplicates
            $clearStmt = $this->pdo->prepare("UPDATE community_users SET fcm_token = NULL WHERE email = :email");
            $clearStmt->bindParam(':email', $userEmail);
            $clearStmt->execute();
            
            $stmt->bindParam(':fcm_token', $fcmToken);
            $stmt->bindParam(':user_barangay', $userBarangay);
            $stmt->bindParam(':email', $userEmail);
            $stmt->execute();
            
            error_log("FCM_DEBUG: FCM token registration completed successfully");
            return ['success' => true, 'message' => 'FCM token registered successfully'];
            
        } catch (PDOException $e) {
            error_log("FCM_DEBUG: FCM token registration failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to register FCM token: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all active FCM tokens
     */
    public function getActiveFCMTokens() {
        try {
            // Get only the latest FCM token per user to avoid duplicates
            $stmt = $this->pdo->prepare("
                SELECT email as user_email, barangay as user_barangay, fcm_token 
                FROM community_users 
                WHERE fcm_token IS NOT NULL AND fcm_token != ''
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get FCM tokens by barangay
     */
    public function getFCMTokensByBarangay($barangay) {
        try {
            // Get only the latest FCM token per user for the specific barangay
            $stmt = $this->pdo->prepare("
                SELECT email as user_email, barangay as user_barangay, fcm_token 
                FROM community_users 
                WHERE barangay = :barangay 
                AND fcm_token IS NOT NULL AND fcm_token != ''
            ");
            $stmt->bindParam(':barangay', $barangay);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get FCM tokens by municipality (all barangays in that municipality)
     */
    public function getFCMTokensByMunicipality($municipality) {
        try {
            // Define municipality to barangay mapping
            $municipalityBarangays = [
                'HERMOSA' => [
                    'A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)',
                    'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite',
                    'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro', 'Sumalo', 'Tipo'
                ],
                'LIMAY' => [
                    'Alas-asin', 'Anonang', 'Bataan', 'Bayan-bayanan', 'Binuangan', 'Cacabasan',
                    'Duale', 'Kitang 2', 'Kitang 2 & Luz', 'Lamao', 'Luz', 'Mabayo', 'Malaya',
                    'Mountain View', 'Poblacion', 'Reformista', 'San Isidro', 'Santiago', 'Tuyan', 'Villa Angeles'
                ],
                'MARIVELES' => [
                    'Alion', 'Balon-Anito', 'Baseco', 'Batan', 'Biaan', 'Cabcaben', 'Camaya',
                    'Iba', 'Lamao', 'Lucanin', 'Mabayo', 'Malusak', 'Poblacion', 'San Carlos',
                    'San Isidro', 'Sisiman', 'Townsite'
                ],
                'MORONG' => [
                    'Binaritan', 'Mabayo', 'Nagbalayong', 'Poblacion', 'Sabang', 'San Jose'
                ],
                'ORANI' => [
                    'Bagong Paraiso', 'Balut', 'Bayorbor', 'Calungusan', 'Camacho', 'Daang Bago',
                    'Dona', 'Kaparangan', 'Mabayo', 'Masagana', 'Mulawin', 'Paglalaban', 'Palawe',
                    'Pantalan Bago', 'Poblacion', 'Saguing', 'Tagumpay', 'Tala', 'Tapulao', 'Tenejero', 'Wawa'
                ],
                'ORION' => [
                    'Balut', 'Bantan', 'Burgos', 'Calungusan', 'Camacho', 'Capunitan', 'Daan Bilolo',
                    'Daan Pare', 'General Lim', 'Kapunitan', 'Lati', 'Luyahan', 'Mabayo', 'Maligaya',
                    'Poblacion', 'Sabatan', 'San Vicente', 'Santo Domingo', 'Villa Angeles', 'Wawa'
                ],
                'PILAR' => [
                    'Bagumbayan', 'Balanoy', 'Bantan Munti', 'Bantan Grande', 'Burgos', 'Del Rosario',
                    'Diwa', 'Fatima', 'Landing', 'Liwa-liwa', 'Nagwaling', 'Panilao', 'Poblacion',
                    'Rizal', 'Santo Niño', 'Wawa'
                ],
                'SAMAL' => [
                    'Bagong Silang', 'Bangkong', 'Burgos', 'Calaguiman', 'Calantas', 'Daan Bilolo',
                    'Daang Pare', 'Del Pilar', 'General Lim', 'Imelda', 'Lourdes', 'Mabatang',
                    'Maligaya', 'Poblacion', 'San Juan', 'San Roque', 'Santo Niño', 'Sulong'
                ]
            ];
            
            if (!isset($municipalityBarangays[$municipality])) {
                error_log("⚠️ Unknown municipality: $municipality");
                return [];
            }
            
            $barangays = $municipalityBarangays[$municipality];
            $placeholders = str_repeat('?,', count($barangays) - 1) . '?';
            
            $stmt = $this->pdo->prepare("
                SELECT email as user_email, barangay as user_barangay, fcm_token 
                FROM community_users 
                WHERE barangay IN ($placeholders)
                AND fcm_token IS NOT NULL AND fcm_token != ''
            ");
            $stmt->execute($barangays);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting FCM tokens by municipality: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Deactivate FCM token
     */
    public function deactivateFCMToken($fcmToken) {
        try {
            $stmt = $this->pdo->prepare("UPDATE community_users SET fcm_token = NULL WHERE fcm_token = :fcm_token");
            $stmt->bindParam(':fcm_token', $fcmToken);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ========================================
    // NOTIFICATION MANAGEMENT
    // ========================================
    
    /**
     * Log notification
     */
    public function logNotification($eventId, $fcmToken, $title, $body, $status, $response = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO notification_logs 
                (event_id, notification_type, target_type, target_value, tokens_sent, success, error_message) 
                VALUES (:event_id, :notification_type, :target_type, :target_value, :tokens_sent, :success, :error_message)");
            
            $notificationType = $title; // Use title as notification type
            $targetType = 'specific';
            $targetValue = $fcmToken; // Use FCM token as target value
            $tokensSent = 1;
            $success = ($status === 'success') ? 1 : 0;
            $errorMessage = ($status === 'success') ? null : $response;
            
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':notification_type', $notificationType);
            $stmt->bindParam(':target_type', $targetType);
            $stmt->bindParam(':target_value', $targetValue);
            $stmt->bindParam(':tokens_sent', $tokensSent);
            $stmt->bindParam(':success', $success);
            $stmt->bindParam(':error_message', $errorMessage);
            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
            error_log("Failed to log notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStats() {
        try {
            $stats = [];
            
            // Total notifications sent
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM notification_logs");
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Successful notifications
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as successful FROM notification_logs WHERE status = 'success'");
            $stmt->execute();
            $stats['successful'] = $stmt->fetch(PDO::FETCH_ASSOC)['successful'];
            
            // Failed notifications
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as failed FROM notification_logs WHERE status = 'failed'");
            $stmt->execute();
            $stats['failed'] = $stmt->fetch(PDO::FETCH_ASSOC)['failed'];
            
            // Recent notifications (last 24 hours)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as recent FROM notification_logs WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stmt->execute();
            $stats['recent_24h'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
            
            return $stats;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get recent notification logs
     */
    public function getRecentNotificationLogs($limit = 50) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM notification_logs ORDER BY sent_at DESC LIMIT :limit");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // ========================================
    // AI FOOD RECOMMENDATIONS
    // ========================================
    
    /**
     * Save AI food recommendation
     */
    public function saveAIRecommendation($userEmail, $foodName, $foodEmoji, $foodDescription, $aiReasoning, $nutritionalPriority, $ingredients, $benefits, $nutritionalImpactScore) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO ai_food_recommendations 
                (user_email, food_name, food_emoji, food_description, ai_reasoning, nutritional_priority, ingredients, benefits, nutritional_impact_score) 
                VALUES (:user_email, :food_name, :food_emoji, :food_description, :ai_reasoning, :nutritional_priority, :ingredients, :benefits, :nutritional_impact_score)");
            
            $stmt->bindParam(':user_email', $userEmail);
            $stmt->bindParam(':food_name', $foodName);
            $stmt->bindParam(':food_emoji', $foodEmoji);
            $stmt->bindParam(':food_description', $foodDescription);
            $stmt->bindParam(':ai_reasoning', $aiReasoning);
            $stmt->bindParam(':nutritional_priority', $nutritionalPriority);
            $stmt->bindParam(':ingredients', $ingredients);
            $stmt->bindParam(':benefits', $benefits);
            $stmt->bindParam(':nutritional_impact_score', $nutritionalImpactScore);
            $stmt->execute();
            
            return ['success' => true, 'id' => $this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to save recommendation: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get AI recommendations for user
     */
    public function getAIRecommendations($userEmail, $limit = 10) {
        try {
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM ai_food_recommendations WHERE user_email = :user_email ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindParam(':user_email', $userEmail);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get intelligent programs based on community data
     */
    public function getIntelligentPrograms($barangay = '', $municipality = '') {
        try {
            if (!$this->isDatabaseAvailable()) {
                return [
                    'programs' => [],
                    'data_analysis' => [
                        'total_users' => 0,
                        'high_risk_percentage' => 0,
                        'sam_cases' => 0,
                        'children_count' => 0,
                        'elderly_count' => 0,
                        'low_dietary_diversity' => 0,
                        'average_risk' => 0,
                        'community_health_status' => 'No Data',
                        'message' => 'No community data available'
                    ]
                ];
            }
            
            // Build where clause
            $whereClause = "WHERE 1=1";
            $params = [];
            
            if (!empty($barangay)) {
                $whereClause .= " AND barangay = :barangay";
                $params[':barangay'] = $barangay;
            }
            
            if (!empty($municipality)) {
                $whereClause .= " AND municipality = :municipality";
                $params[':municipality'] = $municipality;
            }
            
            // Get community data
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN bmi IN ('High', 'Severe') THEN 1 END) as average_risk,
                    SUM(CASE WHEN bmi IN ('High', 'Severe') THEN 1 ELSE 0 END) as high_risk_count,
                    SUM(CASE WHEN bmi = 'Severe' THEN 1 ELSE 0 END) as sam_cases,
                    SUM(CASE WHEN age < 5 THEN 1 ELSE 0 END) as children_count,
                    SUM(CASE WHEN age >= 65 THEN 1 ELSE 0 END) as elderly_count,
                    0 as low_dietary_diversity
                FROM community_users 
                $whereClause
            ");
            $stmt->execute($params);
            $communityData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate percentages
            $totalUsers = $communityData['total_users'] ?? 0;
            $highRiskPercentage = $totalUsers > 0 ? round(($communityData['high_risk_count'] / $totalUsers) * 100, 1) : 0;
            $averageRisk = round($communityData['average_risk'] ?? 0, 1);
            
            // Determine community health status
            $communityHealthStatus = 'Good';
            if ($highRiskPercentage > 50) {
                $communityHealthStatus = 'Critical';
            } elseif ($highRiskPercentage > 30) {
                $communityHealthStatus = 'High Risk';
            } elseif ($highRiskPercentage > 15) {
                $communityHealthStatus = 'Moderate Risk';
            }
            
            // Generate programs based on data
            $programs = [];
            
            if ($totalUsers > 0) {
                // SAM Cases Program
                if ($communityData['sam_cases'] > 0) {
                    $programs[] = [
                        'id' => 'sam_intervention',
                        'title' => 'Severe Acute Malnutrition (SAM) Intervention',
                        'description' => 'Emergency nutrition program for ' . $communityData['sam_cases'] . ' SAM cases',
                        'priority' => 'Critical',
                        'target_group' => 'Children under 5',
                        'duration' => '3-6 months',
                        'status' => 'Active',
                        'ai_reasoning' => 'Based on ' . $communityData['sam_cases'] . ' SAM cases detected in the community. Immediate intervention required.'
                    ];
                }
                
                // High Risk Program
                if ($communityData['high_risk_count'] > 0) {
                    $programs[] = [
                        'id' => 'high_risk_nutrition',
                        'title' => 'High Risk Nutrition Support',
                        'description' => 'Targeted nutrition program for ' . $communityData['high_risk_count'] . ' high-risk individuals',
                        'priority' => 'High',
                        'target_group' => 'High-risk individuals',
                        'duration' => '6-12 months',
                        'status' => 'Active',
                        'ai_reasoning' => 'Based on ' . $highRiskPercentage . '% high-risk cases. Preventive measures needed.'
                    ];
                }
                
                // Children Nutrition Program
                if ($communityData['children_count'] > 0) {
                    $programs[] = [
                        'id' => 'children_nutrition',
                        'title' => 'Children Nutrition Program',
                        'description' => 'Specialized nutrition program for ' . $communityData['children_count'] . ' children under 5',
                        'priority' => 'High',
                        'target_group' => 'Children under 5',
                        'duration' => '12 months',
                        'status' => 'Active',
                        'ai_reasoning' => 'Based on ' . $communityData['children_count'] . ' children in the community. Early intervention crucial.'
                    ];
                }
                
                // Elderly Nutrition Program
                if ($communityData['elderly_count'] > 0) {
                    $programs[] = [
                        'id' => 'elderly_nutrition',
                        'title' => 'Elderly Nutrition Support',
                        'description' => 'Specialized nutrition program for ' . $communityData['elderly_count'] . ' elderly individuals',
                        'priority' => 'Medium',
                        'target_group' => 'Elderly (65+)',
                        'duration' => 'Ongoing',
                        'status' => 'Active',
                        'ai_reasoning' => 'Based on ' . $communityData['elderly_count'] . ' elderly individuals. Age-appropriate nutrition needed.'
                    ];
                }
                
                // Dietary Diversity Program
                if ($communityData['low_dietary_diversity'] > 0) {
                    $programs[] = [
                        'id' => 'dietary_diversity',
                        'title' => 'Dietary Diversity Improvement',
                        'description' => 'Program to improve dietary diversity for ' . $communityData['low_dietary_diversity'] . ' individuals',
                        'priority' => 'Medium',
                        'target_group' => 'Low dietary diversity',
                        'duration' => '6 months',
                        'status' => 'Active',
                        'ai_reasoning' => 'Based on ' . $communityData['low_dietary_diversity'] . ' individuals with low dietary diversity. Education and support needed.'
                    ];
                }
            }
            
            // If no specific programs, create a general community program
            if (empty($programs) && $totalUsers > 0) {
                $programs[] = [
                    'id' => 'general_community',
                    'title' => 'General Community Nutrition Program',
                    'description' => 'Comprehensive nutrition program for the entire community',
                    'priority' => 'Medium',
                    'target_group' => 'All community members',
                    'duration' => '12 months',
                    'status' => 'Active',
                    'ai_reasoning' => 'Based on community data analysis. General nutrition improvement program.'
                ];
            }
            
            return [
                'programs' => $programs,
                'data_analysis' => [
                    'total_users' => $totalUsers,
                    'high_risk_percentage' => $highRiskPercentage,
                    'sam_cases' => $communityData['sam_cases'] ?? 0,
                    'children_count' => $communityData['children_count'] ?? 0,
                    'elderly_count' => $communityData['elderly_count'] ?? 0,
                    'low_dietary_diversity' => $communityData['low_dietary_diversity'] ?? 0,
                    'average_risk' => $averageRisk,
                    'community_health_status' => $communityHealthStatus,
                    'message' => $totalUsers > 0 ? "Generated " . count($programs) . " programs based on community data analysis" : 'No community data available'
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Error getting intelligent programs: " . $e->getMessage());
            return [
                'programs' => [],
                'data_analysis' => [
                    'total_users' => 0,
                    'high_risk_percentage' => 0,
                    'sam_cases' => 0,
                    'children_count' => 0,
                    'elderly_count' => 0,
                    'low_dietary_diversity' => 0,
                    'average_risk' => 0,
                    'community_health_status' => 'Error',
                    'message' => 'Error analyzing community data'
                ]
            ];
        }
    }
    
    // ========================================
    // USER PREFERENCES
    // ========================================
    
    /**
     * Save user preferences
     */
    public function saveUserPreferences($userEmail, $preferences) {
        try {
            // Check if database connection is available
            if (!$this->pdo) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            // Check if preferences exist
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM community_users WHERE email = :user_email");
            $stmt->bindParam(':user_email', $userEmail);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                // Update existing preferences
                $stmt = $this->pdo->prepare("UPDATE community_users SET 
                    age = :age,
                    weight = :weight,
                    height = :height,
                    sex = :gender,
                    barangay = :barangay,
                    screening_date = CURRENT_TIMESTAMP
                    WHERE email = :user_email");
            } else {
                // Insert new preferences
                $stmt = $this->pdo->prepare("INSERT INTO community_users 
                    (email, age, weight, height, sex, barangay, screening_date) 
                    VALUES (:user_email, :age, :weight, :height, :gender, :barangay, NOW())");
            }
            
            $stmt->bindParam(':user_email', $userEmail);
            $stmt->bindParam(':age', $preferences['age'] ?? null);
            $stmt->bindParam(':weight', $preferences['weight'] ?? null);
            $stmt->bindParam(':height', $preferences['height'] ?? null);
            $stmt->bindParam(':gender', $preferences['gender'] ?? null);
            $stmt->bindParam(':barangay', $preferences['barangay'] ?? null);
            $stmt->execute();
            
            return ['success' => true];
        } catch (PDOException $e) {
            if ($this->pdo) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'message' => 'Failed to save preferences: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get user preferences
     */
    public function getUserPreferences($userId) {
        try {
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                return null;
            }
            
            $stmt = $this->pdo->prepare("SELECT * FROM community_users WHERE email = :user_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    // ========================================
    // ANALYTICS & METRICS
    // ========================================
    
    /**
     * Get community metrics
     */
    public function getCommunityMetrics($barangay = '') {
        try {
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                return [
                    'total_users' => 0,
                    'active_devices' => 0,
                    'users_by_barangay' => [],
                    'recent_registrations' => 0
                ];
            }
            
            $metrics = [];
            
            // Build WHERE clause for barangay filtering
            $whereClause = "";
            $params = [];
            if (!empty($barangay)) {
                if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                    // Handle municipality-level filtering
                    $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                    $whereClause = " WHERE barangay LIKE :municipality";
                    $params[':municipality'] = $municipality . '%';
                } else {
                    // Handle specific barangay filtering
                    $whereClause = " WHERE barangay = :barangay";
                    $params[':barangay'] = $barangay;
                }
            }
            
            // Total users (filtered by barangay if specified)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM community_users" . $whereClause);
            $stmt->execute($params);
            $metrics['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active FCM tokens
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as active FROM community_users WHERE status = 'active' AND fcm_token IS NOT NULL AND fcm_token != ''");
                $stmt->execute();
                $metrics['active_devices'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
            } catch (PDOException $e) {
                $metrics['active_devices'] = 0;
            }
            
            // Users by barangay (filtered if barangay specified)
            try {
                $stmt = $this->pdo->prepare("SELECT barangay, COUNT(*) as count FROM community_users" . $whereClause . " GROUP BY barangay");
                $stmt->execute($params);
                $metrics['users_by_barangay'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $metrics['users_by_barangay'] = [];
            }
            
            // Recent registrations (all time, filtered by barangay if specified)
            $recentWhereClause = $whereClause;
            if (empty($recentWhereClause)) {
                $recentWhereClause = " WHERE 1=1";
            }
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as recent FROM community_users" . $recentWhereClause);
            $stmt->execute($params);
            $metrics['recent_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
            
            return $metrics;
        } catch (PDOException $e) {
            error_log("Community metrics error: " . $e->getMessage());
            return [
                'total_users' => 0,
                'active_devices' => 0,
                'users_by_barangay' => [],
                'recent_registrations' => 0
            ];
        }
    }
    
    /**
     * Get geographic distribution
     */
    public function getGeographicDistribution($barangay = '') {
        try {
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                return [];
            }
            
            // Build WHERE clause for barangay filtering
            $whereClause = "";
            $params = [];
            if (!empty($barangay)) {
                if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                    // Handle municipality-level filtering
                    $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                    $whereClause = " WHERE barangay LIKE :municipality";
                    $params[':municipality'] = $municipality . '%';
                } else {
                    // Handle specific barangay filtering
                    $whereClause = " WHERE barangay = :barangay";
                    $params[':barangay'] = $barangay;
                }
            }
            
            $stmt = $this->pdo->prepare("SELECT barangay, COUNT(*) as count FROM community_users" . $whereClause . " GROUP BY barangay ORDER BY count DESC");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Geographic distribution error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get risk distribution
     */
    public function getRiskDistribution($barangay = '') {
        try {
            error_log("getRiskDistribution: Starting method");
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                error_log("getRiskDistribution: Database not available");
                return [
                    'low' => 0,
                    'moderate' => 0,
                    'high' => 0,
                    'severe' => 0
                ];
            }
            
            // Build WHERE clause for barangay filtering
            $whereClause = "WHERE bmi IS NOT NULL AND bmi != ''";
            $params = [];
            if (!empty($barangay)) {
                if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                    // Handle municipality-level filtering
                    $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                    $whereClause .= " AND barangay LIKE :municipality";
                    $params[':municipality'] = $municipality . '%';
                } else {
                    // Handle specific barangay filtering
                    $whereClause .= " AND barangay = :barangay";
                    $params[':barangay'] = $barangay;
                }
            }
            
            // Create risk levels based on bmi
            $stmt = $this->pdo->prepare("
                SELECT 
                    CASE 
                        WHEN bmi = 'Low' THEN 'low'
                        WHEN bmi = 'Moderate' THEN 'moderate'
                        WHEN bmi = 'High' THEN 'high'
                        WHEN bmi = 'Severe' THEN 'severe'
                        ELSE 'unknown'
                    END as risk_level,
                    COUNT(*) as count
                FROM community_users 
                $whereClause
                GROUP BY risk_level
            ");
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("getRiskDistribution: Raw SQL results: " . json_encode($results));
            
            // Convert to expected format
            $distribution = [
                'low' => 0,
                'moderate' => 0,
                'high' => 0,
                'severe' => 0
            ];
            
            foreach ($results as $row) {
                $distribution[$row['risk_level']] = (int)$row['count'];
            }
            
            error_log("getRiskDistribution: Final distribution: " . json_encode($distribution));
            return $distribution;
        } catch (PDOException $e) {
            error_log("Risk distribution error: " . $e->getMessage());
            return [
                'low' => 0,
                'moderate' => 0,
                'high' => 0,
                'severe' => 0
            ];
        }
    }
    
    /**
     * Get detailed screening responses
     */
    public function getDetailedScreeningResponses($timeFrame = '1d', $barangay = '') {
        try {
            if (!$this->isDatabaseAvailable()) {
                return [];
            }
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // REMOVED: Date filtering to show all data
            // Add time frame filter
            // switch($timeFrame) {
            //     case '1d':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            //         break;
            //     case '1w':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            //         break;
            //     case '1m':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            //         break;
            // }
            
            // Add barangay filter
            if (!empty($barangay)) {
                $whereClause .= " AND barangay = :barangay";
                $params[':barangay'] = $barangay;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    cu.*,
                    cu.email as user_email,
                    DATE_FORMAT(cu.screening_date, '%Y-%m-%d') as screening_date
                FROM community_users cu
                $whereClause
                ORDER BY cu.screening_date DESC
                LIMIT 100
            ");
            $stmt->execute($params);
            $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return raw data for dashboard, processed data for other uses
            return $rawData;
        } catch (PDOException $e) {
            error_log("Detailed screening responses error: " . $e->getMessage());
            return [];
        }
    }

    public function getWHOClassifications($whoStandard, $timeFrame = '1d', $barangay = '') {
        try {
            // OPTIMIZED: Get all users in single query with proper filtering
            $users = $this->getDetailedScreeningResponses($timeFrame, $barangay);
            
            if (empty($users)) {
                return [
                    'success' => true,
                    'data' => [
                        'classifications' => [],
                        'total' => 0
                    ]
                ];
            }
            
            // Initialize classifications
            $classifications = [
                'Severely Underweight' => 0,
                'Underweight' => 0,
                'Normal' => 0,
                'Overweight' => 0,
                'Obese' => 0,
                'Severely Wasted' => 0,
                'Wasted' => 0,
                'Severely Stunted' => 0,
                'Stunted' => 0,
                'Tall' => 0,
                'No Data' => 0
            ];
            
            $totalProcessed = 0;
            $totalUsers = count($users);
            $eligibleUsers = 0;
            
            // OPTIMIZED: Bulk process users with single WHO instance
            $who = new WHOGrowthStandards();
            $debugInfo = [];
            
            // Pre-calculate age restrictions to avoid repeated calculations
            $ageRestrictions = $this->getAgeRestrictions($whoStandard);
            
            foreach ($users as $user) {
                try {
                    // Calculate age in months from birthday
                    $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                    
                    // Apply age restrictions exactly like screening.php
                    $shouldProcess = false;
                    if ($whoStandard === 'weight-for-age' || $whoStandard === 'height-for-age') {
                        // These standards are for children 0-71 months only
                        $shouldProcess = ($ageInMonths >= 0 && $ageInMonths <= 71);
                    } elseif ($whoStandard === 'bmi-for-age') {
                        // BMI-for-Age: 2-19 years (24-228 months) - exactly like screening.php
                        $shouldProcess = ($ageInMonths >= 24 && $ageInMonths < 228);
                    } elseif ($whoStandard === 'bmi-adult') {
                        // BMI Adult: ≥19 years (228+ months) - exactly like screening.php
                        $shouldProcess = ($ageInMonths >= 228);
                    } elseif ($whoStandard === 'weight-for-height') {
                        // Weight-for-Height: 0-60 months (0-5 years) - exactly like screening.php
                        $shouldProcess = ($ageInMonths >= 0 && $ageInMonths <= 60);
                    }
                    
                    // Only process users who meet the age criteria
                    if (!$shouldProcess) {
                        continue; // Skip this user
                    }
                    
                    $eligibleUsers++; // Count eligible users
                    
                    $assessment = $who->getComprehensiveAssessment(
                        floatval($user['weight']),
                        floatval($user['height']),
                        $user['birthday'],
                        $user['sex'],
                        $user['screening_date'] ?? null
                    );
                    
                    if ($assessment['success'] && isset($assessment['results'])) {
                        $results = $assessment['results'];
                        $classification = 'Normal'; // default
                        
                        // DEBUG: Log user data and assessment results
                        $debugInfo[] = [
                            'name' => $user['name'] ?? 'Unknown',
                            'age_months' => $ageInMonths,
                            'weight' => $user['weight'],
                            'sex' => $user['sex'],
                            'birthday' => $user['birthday'],
                            'screening_date' => $user['screening_date'] ?? null,
                            'weight_for_age_result' => $results['weight_for_age'] ?? 'Not found',
                            'assessment_success' => $assessment['success'],
                            'eligible_for_standard' => true
                        ];
                        
                        // Get classification based on selected WHO standard (same as screening.php)
                        if ($whoStandard === 'weight-for-age' && isset($results['weight_for_age'])) {
                            $classification = $results['weight_for_age']['classification'] ?? 'Normal';
                        } else if ($whoStandard === 'height-for-age' && isset($results['height_for_age'])) {
                            $classification = $results['height_for_age']['classification'] ?? 'Normal';
                        } else if ($whoStandard === 'weight-for-height' && isset($results['weight_for_height'])) {
                            $classification = $results['weight_for_height']['classification'] ?? 'Normal';
                        } else if ($whoStandard === 'bmi-for-age' && isset($results['bmi_for_age'])) {
                            $classification = $results['bmi_for_age']['classification'] ?? 'Normal';
                        } else if ($whoStandard === 'bmi-adult') {
                            // For BMI-adult, use adult BMI classification
                            $bmi = floatval($user['weight']) / pow(floatval($user['height']) / 100, 2);
                            if ($bmi < 18.5) $classification = 'Underweight';
                            else if ($bmi < 25) $classification = 'Normal';
                            else if ($bmi < 30) $classification = 'Overweight';
                            else $classification = 'Obese';
                        }
                        
                        // Count the classification
                        if (isset($classifications[$classification])) {
                            $classifications[$classification]++;
                        } else {
                            $classifications['No Data']++;
                        }
                        
                        $totalProcessed++;
                    } else {
                        $classifications['No Data']++;
                        $debugInfo[] = [
                            'name' => $user['name'] ?? 'Unknown',
                            'error' => 'Assessment failed',
                            'assessment' => $assessment,
                            'eligible_for_standard' => true
                        ];
                    }
                } catch (Exception $e) {
                    $classifications['No Data']++;
                    $debugInfo[] = [
                        'name' => $user['name'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Add debugging output
            error_log("📊 WHO Classification Summary for $whoStandard:");
            error_log("  - Total users in database: $totalUsers");
            error_log("  - Users eligible for standard: $eligibleUsers");
            error_log("  - Users processed (with classifications): $totalProcessed");
            error_log("  - Classifications: " . json_encode($classifications));
            
            return [
                'success' => true,
                'data' => [
                    'classifications' => $classifications,
                    'total' => $totalProcessed,
                    'debug_info' => $debugInfo
                ]
            ];
            
        } catch (Exception $e) {
            error_log("WHO classifications error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process raw screening data into distributions for UI display
     */
    private function processScreeningDataIntoDistributions($rawData) {
        if (empty($rawData)) {
            return [
                'total_screened' => 0,
                'age_groups' => [],
                'gender' => [],
                'income_levels' => [],
                'height' => [],
                'swelling' => [],
                'weight_loss' => [],
                'feeding_behavior' => [],
                'physical_signs' => [],
                'dietary_diversity' => [],
                'clinical_risk' => [],
                'sam_cases' => 0,
                'high_risk_cases' => 0,
                'critical_muac' => 0,
                'latest_update' => date('Y-m-d H:i:s')
            ];
        }
        
        $totalScreened = count($rawData);
        $ageGroups = [];
        $gender = [];
        $incomeLevels = [];
        $height = [];
        $swelling = [];
        $weightLoss = [];
        $feedingBehavior = [];
        $physicalSigns = [];
        $dietaryDiversity = [];
        $clinicalRisk = [];
        $samCases = 0;
        $highRiskCases = 0;
        $criticalMuac = 0;
        
        foreach ($rawData as $record) {
            // Age groups
            $age = $record['age'] ?? 0;
            if ($age < 5) $ageGroup = 'Under 5';
            elseif ($age < 12) $ageGroup = '5-11';
            elseif ($age < 18) $ageGroup = '12-17';
            elseif ($age < 30) $ageGroup = '18-29';
            elseif ($age < 50) $ageGroup = '30-49';
            elseif ($age < 65) $ageGroup = '50-64';
            else $ageGroup = '65+';
            
            $ageGroups[$ageGroup] = ($ageGroups[$ageGroup] ?? 0) + 1;
            
            // Gender
            $genderValue = $record['gender'] ?? 'Unknown';
            $gender[$genderValue] = ($gender[$genderValue] ?? 0) + 1;
            
            // Income levels
            $incomeLevel = $record['income_level'] ?? 'Unknown';
            $incomeLevels[$incomeLevel] = ($incomeLevels[$incomeLevel] ?? 0) + 1;
            
            // Height distribution
            $heightCm = $record['height'] ?? 0;
            if ($heightCm < 100) $heightRange = 'Under 100 cm';
            elseif ($heightCm < 120) $heightRange = '100-119 cm';
            elseif ($heightCm < 140) $heightRange = '120-139 cm';
            elseif ($heightCm < 160) $heightRange = '140-159 cm';
            elseif ($heightCm < 180) $heightRange = '160-179 cm';
            else $heightRange = '180+ cm';
            
            $height[$heightRange] = ($height[$heightRange] ?? 0) + 1;
            
            // Swelling (Edema)
            $swellingValue = $record['swelling_edema'] ?? 0;
            $swellingStatus = $swellingValue ? 'Present' : 'Absent';
            $swelling[$swellingStatus] = ($swelling[$swellingStatus] ?? 0) + 1;
            
            // Weight loss
            $weightLossValue = $record['recent_weight_loss'] ?? 0;
            $weightLossStatus = $weightLossValue ? 'Yes' : 'No';
            $weightLoss[$weightLossStatus] = ($weightLoss[$weightLossStatus] ?? 0) + 1;
            
            // Feeding behavior
            $feedingValue = $record['appetite_changes'] ?? 0;
            $feedingStatus = $feedingValue ? 'Poor' : 'Normal';
            $feedingBehavior[$feedingStatus] = ($feedingBehavior[$feedingStatus] ?? 0) + 1;
            
            // Physical signs - handle both text field and individual boolean columns
            $physicalSignsList = [];
            
            // Check if physical_signs is a text field with comma-separated values
            if (!empty($record['physical_signs']) && $record['physical_signs'] !== 'None') {
                $signs = explode(',', $record['physical_signs']);
                foreach ($signs as $sign) {
                    $cleanSign = trim($sign);
                    if (!empty($cleanSign) && $cleanSign !== 'None') {
                        $physicalSignsList[] = $cleanSign;
                    }
                }
            } else {
                // Fallback to individual boolean columns
                if ($record['fatigue'] ?? 0) $physicalSignsList[] = 'Fatigue';
                if ($record['weakness'] ?? 0) $physicalSignsList[] = 'Weakness';
                if ($record['dizziness'] ?? 0) $physicalSignsList[] = 'Dizziness';
                if ($record['headache'] ?? 0) $physicalSignsList[] = 'Headache';
                if ($record['abdominal_pain'] ?? 0) $physicalSignsList[] = 'Abdominal Pain';
                if ($record['nausea'] ?? 0) $physicalSignsList[] = 'Nausea';
                if ($record['diarrhea'] ?? 0) $physicalSignsList[] = 'Diarrhea';
                if ($record['dental_problems'] ?? 0) $physicalSignsList[] = 'Dental Problems';
                if ($record['skin_problems'] ?? 0) $physicalSignsList[] = 'Skin Problems';
                if ($record['hair_loss'] ?? 0) $physicalSignsList[] = 'Hair Loss';
                if ($record['nail_changes'] ?? 0) $physicalSignsList[] = 'Nail Changes';
                if ($record['bone_pain'] ?? 0) $physicalSignsList[] = 'Bone Pain';
                if ($record['joint_pain'] ?? 0) $physicalSignsList[] = 'Joint Pain';
                if ($record['muscle_cramps'] ?? 0) $physicalSignsList[] = 'Muscle Cramps';
            }
            
            foreach ($physicalSignsList as $sign) {
                $physicalSigns[$sign] = ($physicalSigns[$sign] ?? 0) + 1;
            }
            
            // Dietary diversity
            $dietaryScore = $record['dietary_diversity_score'] ?? 0;
            if ($dietaryScore <= 2) $dietaryCategory = 'Low (0-2)';
            elseif ($dietaryScore <= 4) $dietaryCategory = 'Moderate (3-4)';
            else $dietaryCategory = 'High (5+)';
            
            $dietaryDiversity[$dietaryCategory] = ($dietaryDiversity[$dietaryCategory] ?? 0) + 1;
            
            // Clinical risk factors
            $clinicalRiskList = [];
            if ($record['chronic_illness'] ?? 0) $clinicalRiskList[] = 'Chronic Illness';
            if ($record['medication_use'] ?? 0) $clinicalRiskList[] = 'Medication Use';
            if ($record['mental_health_concerns'] ?? 0) $clinicalRiskList[] = 'Mental Health';
            if ($record['family_history_diabetes'] ?? 0) $clinicalRiskList[] = 'Diabetes History';
            if ($record['family_history_heart_disease'] ?? 0) $clinicalRiskList[] = 'Heart Disease History';
            if ($record['family_history_cancer'] ?? 0) $clinicalRiskList[] = 'Cancer History';
            if ($record['family_history_obesity'] ?? 0) $clinicalRiskList[] = 'Obesity History';
            if ($record['disability'] ?? 0) $clinicalRiskList[] = 'Disability';
            if ($record['pregnant'] ?? 0) $clinicalRiskList[] = 'Pregnancy';
            if ($record['lactating'] ?? 0) $clinicalRiskList[] = 'Lactating';
            
            foreach ($clinicalRiskList as $risk) {
                $clinicalRisk[$risk] = ($clinicalRisk[$risk] ?? 0) + 1;
            }
            
            // Count SAM cases, high risk cases, and critical MUAC
            if (($record['bmi'] ?? '') === 'Severe') $samCases++;
            if (in_array($record['bmi'] ?? '', ['High', 'Severe'])) $highRiskCases++;
            if (($record['muac_cm'] ?? 0) < 11.5) $criticalMuac++;
        }
        
        // Convert associative arrays to indexed arrays for UI
        $convertToIndexed = function($assocArray) {
            $result = [];
            foreach ($assocArray as $key => $count) {
                $result[] = ['name' => $key, 'count' => $count];
            }
            return $result;
        };
        
        return [
            'total_screened' => $totalScreened,
            'age_groups' => $convertToIndexed($ageGroups),
            'gender' => $convertToIndexed($gender),
            'income_levels' => $convertToIndexed($incomeLevels),
            'height' => $convertToIndexed($height),
            'swelling' => $convertToIndexed($swelling),
            'weight_loss' => $convertToIndexed($weightLoss),
            'feeding_behavior' => $convertToIndexed($feedingBehavior),
            'physical_signs' => $convertToIndexed($physicalSigns),
            'dietary_diversity' => $convertToIndexed($dietaryDiversity),
            'clinical_risk' => $convertToIndexed($clinicalRisk),
            'sam_cases' => $samCases,
            'high_risk_cases' => $highRiskCases,
            'critical_muac' => $criticalMuac,
            'latest_update' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get critical alerts
     */
    public function getCriticalAlerts() {
        try {
            if (!$this->isDatabaseAvailable()) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    up.*,
                    CASE 
                        WHEN up.bmi = 'Severe' THEN 'Severe Risk'
                        WHEN up.bmi = 'High' THEN 'High Risk'
                        ELSE 'Moderate Risk'
                    END as alert_level,
                    CASE 
                        WHEN up.age < 5 THEN 'Child'
                        WHEN up.age < 18 THEN 'Youth'
                        WHEN up.age < 65 THEN 'Adult'
                        ELSE 'Elderly'
                    END as age_group
                FROM community_users up
                WHERE up.bmi IN ('High', 'Severe')
                ORDER BY up.bmi DESC, up.screening_date DESC
                LIMIT 50
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Critical alerts error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get analysis data
     */
    public function getAnalysisData($timeFrame = '1d', $barangay = '') {
        try {
            if (!$this->isDatabaseAvailable()) {
                return [];
            }
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // REMOVED: Date filtering to show all data
            // Add time frame filter
            // switch($timeFrame) {
            //     case '1d':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            //         break;
            //     case '1w':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            //         break;
            //     case '1m':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            //         break;
            // }
            
            // Add barangay filter
            if (!empty($barangay)) {
                $whereClause .= " AND barangay = :barangay";
                $params[':barangay'] = $barangay;
            }
            
            $analysis = [];
            
            // Total screenings
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM community_users $whereClause");
            $stmt->execute($params);
            $analysis['total_screenings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // High risk cases
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as high_risk FROM community_users $whereClause AND bmi IN ('High', 'Severe')");
            $stmt->execute($params);
            $analysis['high_risk_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['high_risk'];
            
            // SAM cases (Severe Acute Malnutrition)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as sam_cases FROM community_users $whereClause AND bmi = 'Severe'");
            $stmt->execute($params);
            $analysis['sam_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['sam_cases'];
            
            // Critical MUAC
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as critical_muac FROM community_users $whereClause AND muac < 11.5");
            $stmt->execute($params);
            $analysis['critical_muac'] = $stmt->fetch(PDO::FETCH_ASSOC)['critical_muac'];
            
            return $analysis;
        } catch (PDOException $e) {
            error_log("Analysis data error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Handle comprehensive screening
     */
    public function handleComprehensiveScreening() {
        try {
            if (!$this->isDatabaseAvailable()) {
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed']);
                return;
            }
            
            $method = $_SERVER['REQUEST_METHOD'];
            
            switch ($method) {
                case 'POST':
                    $this->handleScreeningPost();
                    break;
                case 'GET':
                    $this->handleScreeningGet();
                    break;
                default:
                    http_response_code(405);
                    echo json_encode(['error' => 'Method not allowed']);
                    break;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error in comprehensive screening: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Handle POST request for screening
     */
    private function handleScreeningPost() {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            // Try form data
            $input = $_POST;
        }
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'No data provided']);
            return;
        }
        
        // Validate required fields
        $required_fields = ['municipality', 'barangay', 'age', 'sex', 'weight', 'height'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: {$field}"]);
                return;
            }
        }
        
        // Calculate BMI
        $weight = floatval($input['weight']);
        $height = floatval($input['height']) / 100; // Convert cm to meters
        $bmi = round($weight / ($height * $height), 2);
        
        // Calculate risk score
        $bmi = $this->calculateRiskScore($input, $bmi);
        
        // Prepare data for insertion
        $screening_data = [
            'user_id' => $input['user_id'] ?? null,
            'municipality' => $input['municipality'],
            'barangay' => $input['barangay'],
            'age' => intval($input['age']),
            'age_months' => !empty($input['age_months']) ? intval($input['age_months']) : null,
            'sex' => $input['sex'],
            'pregnant' => $input['pregnant'] ?? null,
            'weight' => $weight,
            'height' => floatval($input['height']),
            'bmi' => $bmi,
            'meal_recall' => $input['meal_recall'] ?? null,
            'family_history' => is_array($input['family_history']) ? json_encode($input['family_history']) : $input['family_history'],
            'lifestyle' => $input['lifestyle'] ?? null,
            'lifestyle_other' => $input['lifestyle_other'] ?? null,
            'immunization' => is_array($input['immunization']) ? json_encode($input['immunization']) : $input['immunization'],
            'bmi' => $bmi,
            'assessment_summary' => $input['assessment_summary'] ?? null,
            'recommendations' => $input['recommendations'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert into database
        $stmt = $this->pdo->prepare("INSERT INTO screening_assessments (
            user_id, municipality, barangay, age, age_months, sex, pregnant, 
            weight, height, bmi, meal_recall, family_history, lifestyle, 
            lifestyle_other, immunization, bmi, assessment_summary, 
            recommendations, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $screening_data['user_id'],
            $screening_data['municipality'],
            $screening_data['barangay'],
            $screening_data['age'],
            $screening_data['age_months'],
            $screening_data['sex'],
            $screening_data['pregnant'],
            $screening_data['weight'],
            $screening_data['height'],
            $screening_data['bmi'],
            $screening_data['meal_recall'],
            $screening_data['family_history'],
            $screening_data['lifestyle'],
            $screening_data['lifestyle_other'],
            $screening_data['immunization'],
            $screening_data['bmi'],
            $screening_data['assessment_summary'],
            $screening_data['recommendations'],
            $screening_data['created_at']
        ]);
        
        $screening_id = $this->pdo->lastInsertId();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Screening assessment saved successfully',
            'screening_id' => $screening_id,
            'bmi' => $bmi,
            'bmi' => $bmi,
            'assessment_summary' => $screening_data['assessment_summary'],
            'recommendations' => $screening_data['recommendations']
        ]);
    }
    
    /**
     * Handle GET request for screening
     */
    private function handleScreeningGet() {
        $user_id = $_GET['user_id'] ?? null;
        $screening_id = $_GET['screening_id'] ?? null;
        
        if ($screening_id) {
            // Get specific screening assessment
            $stmt = $this->pdo->prepare("SELECT * FROM screening_assessments WHERE id = ?");
            $stmt->execute([$screening_id]);
            $assessment = $stmt->fetch();
            
            if (!$assessment) {
                http_response_code(404);
                echo json_encode(['error' => 'Screening assessment not found']);
                return;
            }
            
            echo json_encode($assessment);
            
        } else if ($user_id) {
            // Get all screening assessments for a user
            $stmt = $this->pdo->prepare("SELECT * FROM screening_assessments WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $assessments = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'assessments' => $assessments,
                'count' => count($assessments)
            ]);
            
        } else {
            // Get all screening assessments (for admin)
            $stmt = $this->pdo->prepare("SELECT * FROM screening_assessments ORDER BY created_at DESC LIMIT 100");
            $stmt->execute();
            $assessments = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'assessments' => $assessments,
                'count' => count($assessments)
            ]);
        }
    }
    
    /**
     * Calculate risk score for screening
     */
    private function calculateRiskScore($input, $bmi) {
        $bmi = 0;
        
        // BMI risk factors
        if ($bmi < 18.5) {
            $bmi += 10; // Underweight
        } elseif ($bmi >= 25 && $bmi < 30) {
            $bmi += 5; // Overweight
        } elseif ($bmi >= 30) {
            $bmi += 15; // Obese
        }
        
        // Age risk factors
        $age = intval($input['age']);
        if ($age < 5) {
            $bmi += 10; // Young children
        } elseif ($age > 65) {
            $bmi += 8; // Elderly
        }
        
        // Family history risk factors
        $family_history = is_array($input['family_history']) ? $input['family_history'] : json_decode($input['family_history'], true);
        if ($family_history && !in_array('None', $family_history)) {
            foreach ($family_history as $condition) {
                switch ($condition) {
                    case 'Diabetes':
                        $bmi += 8;
                        break;
                    case 'Hypertension':
                        $bmi += 6;
                        break;
                    case 'Heart Disease':
                        $bmi += 10;
                        break;
                    case 'Kidney Disease':
                        $bmi += 12;
                        break;
                    case 'Tuberculosis':
                        $bmi += 7;
                        break;
                    case 'Obesity':
                        $bmi += 5;
                        break;
                    case 'Malnutrition':
                        $bmi += 15;
                        break;
                }
            }
        }
        
        // Lifestyle risk factors
        if ($input['lifestyle'] === 'Sedentary') {
            $bmi += 5;
        }
        
        // Meal balance risk (if meal recall is provided)
        if (!empty($input['meal_recall'])) {
            $meal_text = strtolower($input['meal_recall']);
            $food_groups = [
                'carbs' => ['rice', 'bread', 'pasta', 'potato', 'corn', 'cereal', 'oatmeal'],
                'protein' => ['meat', 'fish', 'chicken', 'pork', 'beef', 'egg', 'milk', 'cheese', 'beans', 'tofu'],
                'vegetables' => ['vegetable', 'carrot', 'broccoli', 'spinach', 'lettuce', 'tomato', 'onion'],
                'fruits' => ['fruit', 'apple', 'banana', 'orange', 'mango', 'grape']
            ];
            
            $found_groups = 0;
            foreach ($food_groups as $group => $foods) {
                foreach ($foods as $food) {
                    if (strpos($meal_text, $food) !== false) {
                        $found_groups++;
                        break;
                    }
                }
            }
            
            if ($found_groups < 3) {
                $bmi += 8; // Unbalanced diet
            }
        }
        
        // Immunization risk (for children <= 12)
        if ($age <= 12 && !empty($input['immunization'])) {
            $immunization = is_array($input['immunization']) ? $input['immunization'] : json_decode($input['immunization'], true);
            $required_vaccines = ['BCG', 'DPT', 'Polio', 'Measles', 'Hepatitis B', 'Vitamin A'];
            $missing_vaccines = array_diff($required_vaccines, $immunization);
            
            if (!empty($missing_vaccines)) {
                $bmi += count($missing_vaccines) * 2; // 2 points per missing vaccine
            }
        }
        
        return $bmi;
    }
    
    /**
     * Get time frame data
     */
    public function getTimeFrameData($timeFrame = '1d', $barangay = '') {
        try {
            if (!$this->isDatabaseAvailable()) {
                return [];
            }
            
            $whereClause = "WHERE 1=1";
            $params = [];
            
            // REMOVED: Date filtering to show all data
            // Add time frame filter
            // switch($timeFrame) {
            //     case '1d':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            //         break;
            //     case '1w':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            //         break;
            //     case '1m':
            //         $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            //         break;
            // }
            
            // Add barangay filter
            if (!empty($barangay)) {
                $whereClause .= " AND barangay = :barangay";
                $params[':barangay'] = $barangay;
            }
            
            $data = [];
            
            // Total screenings in time frame
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM community_users $whereClause");
            $stmt->execute($params);
            $data['total_screenings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // High risk cases in time frame
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as high_risk FROM community_users $whereClause AND bmi IN ('High', 'Severe')");
            $stmt->execute($params);
            $data['high_risk_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['high_risk'];
            
            // SAM cases in time frame
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as sam_cases FROM community_users $whereClause AND bmi = 'Severe'");
            $stmt->execute($params);
            $data['sam_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['sam_cases'];
            
            // Critical MUAC in time frame
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as critical_muac FROM community_users $whereClause AND muac < 11.5");
            $stmt->execute($params);
            $data['critical_muac'] = $stmt->fetch(PDO::FETCH_ASSOC)['critical_muac'];
            
            return $data;
        } catch (PDOException $e) {
            error_log("Time frame data error: " . $e->getMessage());
            return [];
        }
    }
    
    // ========================================
    // SESSION MANAGEMENT
    // ========================================
    
    /**
     * Check if user session is valid
     */
    public function checkSession($userId, $email) {
        try {
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE user_id = :user_id AND email = :email");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Check if admin session is valid
     */
    public function checkAdminSession($adminId, $email) {
        try {
            $stmt = $this->pdo->prepare("SELECT admin_id FROM admin WHERE admin_id = :admin_id AND email = :email");
            $stmt->bindParam(':admin_id', $adminId);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // ========================================
    // UNIVERSAL DATABASE OPERATIONS
    // ========================================
    
    /**
     * Universal SELECT operation
     */
    public function universalSelect($table, $columns = '*', $where = '', $orderBy = '', $limit = '', $params = []) {
        try {
            if (!$this->isDatabaseAvailable()) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            // Build SQL query
            $sql = "SELECT $columns FROM $table";
            
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            
            if (!empty($orderBy)) {
                $sql .= " ORDER BY $orderBy";
            }
            
            if (!empty($limit)) {
                $sql .= " LIMIT $limit";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $results,
                'count' => count($results),
                'sql' => $sql
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Universal INSERT operation
     */
    public function universalInsert($table, $data) {
        try {
            if (!$this->isDatabaseAvailable()) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            if (empty($data) || !is_array($data)) {
                return ['success' => false, 'message' => 'Data must be a non-empty array'];
            }
            
            $columns = array_keys($data);
            $placeholders = ':' . implode(', :', $columns);
            $columnsList = implode(', ', $columns);
            
            $sql = "INSERT INTO $table ($columnsList) VALUES ($placeholders)";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind parameters
            $params = [];
            foreach ($data as $key => $value) {
                $params[':' . $key] = $value;
            }
            
            $result = $stmt->execute($params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Record inserted successfully',
                    'insert_id' => $this->pdo->lastInsertId(),
                    'sql' => $sql
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to insert record'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Universal UPDATE operation
     */
    public function universalUpdate($table, $data, $where, $whereParams = []) {
        try {
            if (!$this->isDatabaseAvailable()) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            if (empty($data) || !is_array($data)) {
                return ['success' => false, 'message' => 'Data must be a non-empty array'];
            }
            
            if (empty($where)) {
                return ['success' => false, 'message' => 'WHERE condition is required for UPDATE'];
            }
            
            $setParts = [];
            $params = [];
            
            foreach ($data as $key => $value) {
                $setParts[] = "$key = ?";
                $params[] = $value;
            }
            
            // Merge WHERE parameters
            $params = array_merge($params, $whereParams);
            
            $setClause = implode(', ', $setParts);
            $sql = "UPDATE $table SET $setClause WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Record updated successfully',
                    'affected_rows' => $stmt->rowCount(),
                    'sql' => $sql
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to update record'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Universal DELETE operation
     */
    public function universalDelete($table, $where, $params = []) {
        try {
            if (!$this->isDatabaseAvailable()) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            if (empty($where)) {
                return ['success' => false, 'message' => 'WHERE condition is required for DELETE'];
            }
            
            $sql = "DELETE FROM $table WHERE $where";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Record deleted successfully',
                    'affected_rows' => $stmt->rowCount(),
                    'sql' => $sql
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to delete record'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Universal custom query operation
     */
    public function universalQuery($sql, $params = []) {
        try {
            if (!$this->isDatabaseAvailable()) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            // Check if it's a SELECT query
            if (stripos(trim($sql), 'SELECT') === 0) {
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return [
                    'success' => true,
                    'data' => $data,
                    'count' => count($data),
                    'sql' => $sql
                ];
            } else {
                // For INSERT, UPDATE, DELETE
                return [
                    'success' => true,
                    'message' => 'Query executed successfully',
                    'affected_rows' => $stmt->rowCount(),
                    'insert_id' => $this->pdo->lastInsertId(),
                    'sql' => $sql
                ];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Describe table structure
     */
    public function describeTable($table) {
        try {
            if (!$this->isDatabaseAvailable()) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            $stmt = $this->pdo->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'table' => $table,
                'columns' => $columns,
                'count' => count($columns)
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * List all tables in database
     */
    public function listTables() {
        try {
            if (!$this->isDatabaseAvailable()) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            $stmt = $this->pdo->prepare("SHOW TABLES");
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return [
                'success' => true,
                'tables' => $tables,
                'count' => count($tables)
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    // ========================================
    // UTILITY METHODS
    // ========================================
    
    /**
     * Execute custom query (legacy method)
     */
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    
    /**
     * Close connections
     */
    public function close() {
        $this->pdo = null;
        $this->mysqli = null;
    }
    
    /**
     * Get age restrictions for WHO standard (optimization helper)
     */
    private function getAgeRestrictions($whoStandard) {
        switch ($whoStandard) {
            case 'weight-for-age':
            case 'height-for-age':
                return ['min' => 0, 'max' => 71]; // 0-71 months (0-5.9 years)
            case 'weight-for-height':
                return ['min' => 0, 'max' => 60]; // 0-60 months (0-5 years)
            case 'bmi-for-age':
                return ['min' => 24, 'max' => 228]; // 24-228 months (2-19 years)
            case 'bmi-adult':
                return ['min' => 228, 'max' => 999]; // 228+ months (19+ years)
            default:
                return ['min' => 0, 'max' => 999];
        }
    }
    
    /**
     * Filter users by age eligibility for specific WHO standards
     */
    private function filterUsersByAgeEligibility($users, $whoStandard) {
        require_once __DIR__ . '/../../who_growth_standards.php';
        $who = new WHOGrowthStandards();
        
        $filteredUsers = [];
        
        foreach ($users as $user) {
            $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
            $isEligible = false;
            
            switch ($whoStandard) {
                case 'weight-for-age':
                case 'height-for-age':
                    // 0-71 months (0-5 years 11 months)
                    $isEligible = $ageInMonths >= 0 && $ageInMonths <= 71;
                    break;
                    
                case 'weight-for-height':
                    // 45-120cm height (no age restriction, height-based)
                    $height = floatval($user['height'] ?? 0);
                    $isEligible = $height >= 45 && $height <= 120;
                    break;
                    
                case 'bmi-for-age':
                    // 2-19 years old (24-228 months)
                    $isEligible = $ageInMonths >= 24 && $ageInMonths <= 228;
                    break;
                    
                case 'bmi-adult':
                    // 19+ years old (228+ months)
                    $isEligible = $ageInMonths >= 228;
                    break;
                    
                default:
                    // No filtering for unknown standards
                    $isEligible = true;
                    break;
            }
            
            if ($isEligible) {
                $filteredUsers[] = $user;
            }
        }
        
        error_log("WHO Standard '$whoStandard' filtering: " . count($users) . " total users, " . count($filteredUsers) . " eligible users");
        
        return $filteredUsers;
    }

    /**
     * OPTIMIZED: Get all WHO classifications in bulk (professional approach)
     * This replaces multiple individual API calls with a single bulk operation
     */
    public function getAllWHOClassificationsBulk($timeFrame = '1d', $barangay = '', $whoStandard = '') {
        try {
            // Single query to get all users
            $users = $this->getDetailedScreeningResponses($timeFrame, $barangay);
            
            // Filter users by age eligibility for the specific WHO standard
            if (!empty($whoStandard) && !empty($users)) {
                $users = $this->filterUsersByAgeEligibility($users, $whoStandard);
            }
            
            if (empty($users)) {
                return [
                    'success' => true,
                    'data' => [
                        'weight_for_age' => [],
                        'height_for_age' => [],
                        'weight_for_height' => [],
                        'bmi_for_age' => [],
                        'bmi_adult' => [],
                        'total_users' => 0
                    ]
                ];
            }
            
            // Initialize all classification arrays
            $allClassifications = [
                'weight_for_age' => [
                    'Severely Underweight' => 0, 'Underweight' => 0, 'Normal' => 0, 
                    'Overweight' => 0, 'Obese' => 0, 'No Data' => 0
                ],
                'height_for_age' => [
                    'Severely Stunted' => 0, 'Stunted' => 0, 'Normal' => 0, 
                    'Tall' => 0, 'No Data' => 0
                ],
                'weight_for_height' => [
                    'Severely Wasted' => 0, 'Wasted' => 0, 'Normal' => 0, 
                    'Overweight' => 0, 'Obese' => 0, 'No Data' => 0
                ],
                'bmi_for_age' => [
                    'Severely Underweight' => 0, 'Underweight' => 0, 'Normal' => 0, 
                    'Overweight' => 0, 'Obese' => 0, 'No Data' => 0
                ],
                'bmi_adult' => [
                    'Underweight' => 0, 'Normal' => 0, 'Overweight' => 0, 
                    'Obese' => 0, 'No Data' => 0
                ]
            ];
            
            // Single WHO instance for all calculations
            require_once __DIR__ . '/../../who_growth_standards.php';
            $who = new WHOGrowthStandards();
            
            $totalProcessed = 0;
            $totalUsers = count($users);
            
            // Process all users in one pass
            foreach ($users as $user) {
                try {
                    // Calculate age once
                    $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                    
                    // Get comprehensive assessment once for all standards
                    $assessment = $who->getComprehensiveAssessment(
                        floatval($user['weight']),
                        floatval($user['height']),
                        $user['birthday'],
                        $user['sex'],
                        $user['screening_date'] ?? null
                    );
                    
                    if ($assessment['success'] && isset($assessment['results'])) {
                        $results = $assessment['results'];
                        
                        // Process each WHO standard
                        $this->processWHOStandard($allClassifications['weight_for_age'], $results, 'weight_for_age', $ageInMonths, $user);
                        $this->processWHOStandard($allClassifications['height_for_age'], $results, 'height_for_age', $ageInMonths, $user);
                        $this->processWHOStandard($allClassifications['weight_for_height'], $results, 'weight_for_height', $ageInMonths, $user);
                        $this->processWHOStandard($allClassifications['bmi_for_age'], $results, 'bmi_for_age', $ageInMonths, $user);
                        $this->processBMIAdult($allClassifications['bmi_adult'], $user, $ageInMonths);
                        
                        $totalProcessed++;
                    } else {
                        // Mark all as no data if assessment failed
                        foreach ($allClassifications as &$classifications) {
                            $classifications['No Data']++;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Bulk WHO processing error for user {$user['email']}: " . $e->getMessage());
                    foreach ($allClassifications as &$classifications) {
                        $classifications['No Data']++;
                    }
                }
            }
            
            return [
                'success' => true,
                'data' => $allClassifications,
                'total_users' => $totalUsers,
                'processed_users' => $totalProcessed
            ];
            
        } catch (Exception $e) {
            error_log("Bulk WHO classifications error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Bulk processing failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process individual WHO standard classification
     */
    private function processWHOStandard(&$classifications, $results, $standard, $ageInMonths, $user) {
        // Age restrictions
        $ageRestrictions = $this->getAgeRestrictions($standard);
        if ($ageInMonths < $ageRestrictions['min'] || $ageInMonths > $ageRestrictions['max']) {
            $classifications['No Data']++;
            return;
        }
        
        // Get classification from results
        $classification = 'No Data';
        if (isset($results[$standard])) {
            $classification = $results[$standard]['classification'] ?? 'No Data';
        }
        
        // Count classification
        if (isset($classifications[$classification])) {
            $classifications[$classification]++;
        } else {
            $classifications['No Data']++;
        }
    }
    
    /**
     * OPTIMIZED: Get barangay distribution data in bulk (no time frame)
     * This follows the same efficient pattern as getAllWHOClassificationsBulk
     */
    public function getBarangayDistributionBulk($barangay = '') {
        try {
            // Use the same method as other bulk APIs - get all users (no time filtering)
            $users = $this->getDetailedScreeningResponses('1d', $barangay);
            
            if (empty($users)) {
                return [
                    'success' => true,
                    'data' => [
                        'barangay_distribution' => [],
                        'municipality_distribution' => [],
                        'total_users' => 0
                    ]
                ];
            }
            
            // Initialize distribution arrays
            $barangayDistribution = [];
            $municipalityDistribution = [];
            
            // Process all users in one pass
            foreach ($users as $user) {
                // Barangay distribution
                if (!empty($user['barangay'])) {
                    if (!isset($barangayDistribution[$user['barangay']])) {
                        $barangayDistribution[$user['barangay']] = 0;
                    }
                    $barangayDistribution[$user['barangay']]++;
                }
                
                // Municipality distribution
                if (!empty($user['municipality'])) {
                    if (!isset($municipalityDistribution[$user['municipality']])) {
                        $municipalityDistribution[$user['municipality']] = 0;
                    }
                    $municipalityDistribution[$user['municipality']]++;
                }
            }
            
            // Sort distributions by count (descending)
            arsort($barangayDistribution);
            arsort($municipalityDistribution);
            
            return [
                'success' => true,
                'data' => [
                    'barangay_distribution' => $barangayDistribution,
                    'municipality_distribution' => $municipalityDistribution,
                    'total_users' => count($users)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Barangay distribution bulk error: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Bulk processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * OPTIMIZED: Get gender distribution data in bulk (no time frame)
     * This follows the same efficient pattern as getAllWHOClassificationsBulk
     */
    public function getGenderDistributionBulk($barangay = '') {
        try {
            // Use the same method as other bulk APIs - get all users (no time filtering)
            $users = $this->getDetailedScreeningResponses('1d', $barangay);
            
            if (empty($users)) {
                return [
                    'success' => true,
                    'data' => [
                        'gender_distribution' => [],
                        'total_users' => 0
                    ]
                ];
            }
            
            // Initialize gender distribution array
            $genderDistribution = [
                'Male' => 0,
                'Female' => 0
            ];
            
            // Process all users in one pass
            foreach ($users as $user) {
                if (!empty($user['sex'])) {
                    $sex = ucfirst(strtolower(trim($user['sex'])));
                    
                    // Map common variations to standard values - only count Male/Female
                    switch ($sex) {
                        case 'M':
                        case 'Male':
                        case 'MALE':
                            $genderDistribution['Male']++;
                            break;
                        case 'F':
                        case 'Female':
                        case 'FEMALE':
                            $genderDistribution['Female']++;
                            break;
                        // Skip all other values (Other, No Data, etc.)
                        default:
                            break;
                    }
                }
                // Skip users with empty sex field
            }
            
            return [
                'success' => true,
                'data' => [
                    'gender_distribution' => $genderDistribution,
                    'total_users' => count($users)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Gender distribution bulk error: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => 'Bulk processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process BMI adult classification
     */
    private function processBMIAdult(&$classifications, $user, $ageInMonths) {
        // BMI adult is for 6+ years (72+ months)
        if ($ageInMonths < 72) {
            $classifications['No Data']++;
            return;
        }
        
        $bmi = floatval($user['weight']) / pow(floatval($user['height']) / 100, 2);
        
        if ($bmi < 18.5) $classification = 'Underweight';
        else if ($bmi < 25) $classification = 'Normal';
        else if ($bmi < 30) $classification = 'Overweight';
        else $classification = 'Obese';
        
        if (isset($classifications[$classification])) {
            $classifications[$classification]++;
        } else {
            $classifications['No Data']++;
        }
    }
}

// ========================================
// EMAIL FUNCTIONS (RESEND API)
// ========================================

/**
 * Send email using Resend API
 */
function sendResendEmail($email, $username, $verificationCode) {
    try {
        // Resend API configuration
        $resendApiKey = 're_Vk6LhArD_KSi2P8EiHxz2CSwh9N2cAUZB';
        $fromEmail = 'onboarding@resend.dev';
        
        // Validate API key format
        if (!preg_match('/^re_[a-zA-Z0-9_]+$/', $resendApiKey)) {
            error_log("Invalid Resend API key format: " . $resendApiKey);
            return false;
        }
        
        // Create email data
        $emailData = [
            'from' => $fromEmail,
            'to' => [$email],
            'subject' => "Nutrisaur Verification Code: $verificationCode",
            'html' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0;'>🧪 Nutrisaur</h1>
                </div>
                <div style='background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #333;'>Hello $username!</h2>
                    <p style='color: #666; font-size: 16px;'>Your verification code is:</p>
                    <div style='background: #f8f9fa; border: 2px solid #4CAF50; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;'>
                        <span style='font-size: 32px; font-weight: bold; color: #4CAF50; letter-spacing: 5px;'>$verificationCode</span>
                    </div>
                    <p style='color: #666; font-size: 14px;'>This code will expire in 5 minutes.</p>
                    <p style='color: #666; font-size: 14px;'>If you didn't request this verification, please ignore this email.</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                    <p style='color: #999; font-size: 12px; text-align: center;'>Best regards,<br>Nutrisaur Team</p>
                </div>
            </div>
            "
        ];
        
        // Send email using Resend API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $resendApiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log the response for debugging
        error_log("Resend API Response: HTTP $httpCode, Response: $response, Error: $curlError");
        
        if ($httpCode == 200 && !$curlError) {
            $responseData = json_decode($response, true);
            if ($responseData && isset($responseData['id'])) {
                error_log("Email sent successfully via Resend. Email ID: " . $responseData['id']);
                return true;
            } else {
                error_log("Resend API returned 200 but no email ID in response: " . $response);
                return false;
            }
        } else {
            error_log("Resend API failed: HTTP $httpCode, Response: $response, Error: $curlError");
            
            // Fallback to PHP mail function
            error_log("Attempting fallback to PHP mail function");
            return sendFallbackEmail($email, $username, $verificationCode);
        }
        
    } catch (Exception $e) {
        error_log("Email sending exception: " . $e->getMessage());
        
        // Fallback to PHP mail function
        error_log("Attempting fallback to PHP mail function after exception");
        return sendFallbackEmail($email, $username, $verificationCode);
    }
}

/**
 * Fallback email function using PHP mail()
 */
function sendFallbackEmail($email, $username, $verificationCode) {
    try {
        $subject = "Nutrisaur Verification Code: $verificationCode";
        $message = "
        <html>
        <head>
            <title>Nutrisaur Verification</title>
        </head>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                <h1 style='margin: 0;'>🧪 Nutrisaur</h1>
            </div>
            <div style='background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
                <h2 style='color: #333;'>Hello $username!</h2>
                <p style='color: #666; font-size: 16px;'>Your verification code is:</p>
                <div style='background: #f8f9fa; border: 2px solid #4CAF50; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;'>
                    <span style='font-size: 32px; font-weight: bold; color: #4CAF50; letter-spacing: 5px;'>$verificationCode</span>
                </div>
                <p style='color: #666; font-size: 14px;'>This code will expire in 5 minutes.</p>
                <p style='color: #666; font-size: 14px;'>If you didn't request this verification, please ignore this email.</p>
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                <p style='color: #999; font-size: 12px; text-align: center;'>Best regards,<br>Nutrisaur Team</p>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: Nutrisaur <noreply@nutrisaur.com>" . "\r\n";
        $headers .= "Reply-To: noreply@nutrisaur.com" . "\r\n";
        
        $result = mail($email, $subject, $message, $headers);
        
        if ($result) {
            error_log("Fallback email sent successfully to $email");
            return true;
        } else {
            error_log("Fallback email failed to send to $email");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Fallback email exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Send welcome email using Resend API
 */
function sendWelcomeEmail($email, $username) {
    try {
        // Resend API configuration
        $resendApiKey = 're_Vk6LhArD_KSi2P8EiHxz2CSwh9N2cAUZB';
        $fromEmail = 'onboarding@resend.dev';
        
        // Create email data
        $emailData = [
            'from' => $fromEmail,
            'to' => [$email],
            'subject' => "Welcome to Nutrisaur! 🧪",
            'html' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: #4CAF50; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0;'>🧪 Nutrisaur</h1>
                </div>
                <div style='background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #333;'>Welcome to Nutrisaur, $username! 🎉</h2>
                    <p style='color: #666; font-size: 16px;'>Your email has been successfully verified!</p>
                    <p style='color: #666; font-size: 16px;'>You can now access all features of Nutrisaur:</p>
                    <ul style='color: #666; font-size: 16px;'>
                        <li>📊 Nutrition screening and assessment</li>
                        <li>🍎 AI-powered food recommendations</li>
                        <li>📈 Health tracking and analytics</li>
                        <li>🔔 Personalized notifications</li>
                    </ul>
                    <div style='background: #f8f9fa; border: 2px solid #4CAF50; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0;'>
                        <p style='color: #4CAF50; font-size: 18px; font-weight: bold; margin: 0;'>Ready to start your nutrition journey!</p>
                    </div>
                    <p style='color: #666; font-size: 14px;'>Thank you for choosing Nutrisaur for your health and nutrition needs.</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                    <p style='color: #999; font-size: 12px; text-align: center;'>Best regards,<br>Nutrisaur Team</p>
                </div>
            </div>
            "
        ];
        
        // Send email using Resend API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $resendApiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log the response for debugging
        error_log("Resend API Response: HTTP $httpCode, Response: $response, Error: $curlError");
        
        if ($httpCode == 200 && !$curlError) {
            $responseData = json_decode($response, true);
            if ($responseData && isset($responseData['id'])) {
                error_log("Email sent successfully via Resend. Email ID: " . $responseData['id']);
                return true;
            } else {
                error_log("Resend API returned 200 but no email ID in response: " . $response);
                return false;
            }
        } else {
            error_log("Resend API failed: HTTP $httpCode, Response: $response, Error: $curlError");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Welcome email sending exception: " . $e->getMessage());
        return false;
    }
}

// ========================================
// API ENDPOINTS
// ========================================

// Only process API requests if this file is called directly
if (basename($_SERVER['SCRIPT_NAME']) === 'DatabaseAPI.php' || basename($_SERVER['SCRIPT_NAME']) === 'DatabaseAPI') {
    
    // Set headers for API responses
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
    

    // Initialize the database API using singleton pattern
    $db = DatabaseAPI::getInstance();
    
    // Get the action from query parameter or POST data
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        
        // ========================================
        // COMMUNITY USER LOGIN API
        // ========================================
        case 'login_community_user':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Try to get JSON data first
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                // If JSON parsing fails, try form data
                if (!$data) {
                    $data = $_POST;
                }
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'No data provided']);
                    break;
                }
                
                $email = $data['email'] ?? '';
                $password = $data['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Please enter both email and password']);
                    break;
                }
                
                // Get user from community_users table
                $result = $db->universalSelect('community_users', '*', 'email = ?', '', '', [$email]);
                
                if (!$result['success'] || empty($result['data'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                    break;
                }
                
                $user = $result['data'][0];
                
                // Verify password
                if (!password_verify($password, $user['password'])) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                    break;
                }
                
                // Return user data (without password)
                unset($user['password']);
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $user
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        // ========================================
        // LOGIN API
        // ========================================
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Try to get JSON data first
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                // If JSON parsing fails, try form data
                if (!$data) {
                    $data = $_POST;
                }
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'No data provided']);
                    break;
                }
                
                $usernameOrEmail = $data['username'] ?? '';
                $password = $data['password'] ?? '';
                
                if (empty($usernameOrEmail) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Please enter both username/email and password']);
                    break;
                }
                
                // Use the centralized authentication method
                $result = $db->authenticateUser($usernameOrEmail, $password);
                
                if ($result['success']) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful!',
                        'data' => $result['data']
                    ]);
                } else {
                    echo json_encode($result);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        // ========================================
        // COMMUNITY USER REGISTRATION API
        // ========================================
        case 'register_community_user':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    // Get JSON data
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    
                    if (!$data) {
                        echo json_encode(['success' => false, 'message' => 'Invalid JSON data provided']);
                        break;
                    }
                    
                    $email = $data['email'] ?? '';
                    $password = $data['password'] ?? '';
                    $name = $data['name'] ?? $data['username'] ?? '';
                    
                    if (empty($email) || empty($password) || empty($name)) {
                        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
                        break;
                    }
                    
                    // For basic signup, allow empty optional fields
                    $municipality = !empty($data['municipality']) ? $data['municipality'] : 'Not specified';
                    $barangay = !empty($data['barangay']) ? $data['barangay'] : 'Not specified';
                    $sex = !empty($data['sex']) ? $data['sex'] : 'Not specified';
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
                        break;
                    }
                    
                    if (strlen($password) < 6) {
                        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                        break;
                    }
                    
                    // Check if user already exists
                    $checkResult = $db->universalSelect('community_users', 'email', 'email = ?', '', '', [$email]);
                    if ($checkResult['success'] && !empty($checkResult['data'])) {
                        echo json_encode(['success' => false, 'message' => 'User with this email already exists']);
                        break;
                    }
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user into community_users table
                    $insertData = [
                        'name' => $name,
                        'email' => $email,
                        'password' => $hashedPassword,
                        'municipality' => $municipality,
                        'barangay' => $barangay,
                        'sex' => $sex,
                        'birthday' => $data['birth_date'] ?? $data['birthday'] ?? '1900-01-01',
                        'is_pregnant' => ($data['is_pregnant'] ?? 'No') === 'Yes' ? 'Yes' : 'No',
                        'weight' => !empty($data['weight']) ? $data['weight'] : '0',
                        'height' => !empty($data['height']) ? $data['height'] : '0',
                        'screening_date' => date('Y-m-d H:i:s')
                    ];
                    
                    // Insert into community_users table
                    $result = $db->universalInsert('community_users', $insertData);
                    
                    // Log the result for debugging
                    error_log("User registration result: " . print_r($result, true));
                    
                    if ($result['success']) {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'User registered successfully',
                            'user' => [
                                'email' => $email,
                                'name' => $name
                            ]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to register user: ' . $result['message']]);
                    }
                } catch (Exception $e) {
                    error_log("Registration error: " . $e->getMessage());
                    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
        break;
            
        // ========================================
        // COMMUNITY USER LOGIN API
        // ========================================
        case 'login_community_user':
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Get JSON data
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    
                    if (!$data) {
                        echo json_encode(['success' => false, 'message' => 'No data provided']);
                        break;
                    }
                    
                    $email = $data['email'] ?? $data['username'] ?? '';
                    $password = $data['password'] ?? '';
                    
                    if (empty($email) || empty($password)) {
                        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                        break;
                    }
                    
                    // Direct database query for community_users
                    $pdo = $db->getPDO();
                    $stmt = $pdo->prepare("SELECT * FROM community_users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user && password_verify($password, $user['password'])) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Login successful',
                            'user' => [
                                'name' => $user['name'],
                                'email' => $user['email'],
                                'municipality' => $user['municipality'],
                                'barangay' => $user['barangay']
                            ]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                }
            } catch (Exception $e) {
                error_log("Community login error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // WORKING DATA RETRIEVAL
        // ========================================
        case 'get_user_data_working':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                $email = $data['email'] ?? '';
                
                $pdo = $db->getPDO();
                $stmt = $pdo->prepare("SELECT name, email, municipality, barangay, sex, birthday, is_pregnant, weight, height, screening_date FROM community_users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'User data retrieved successfully',
                        'user' => [
                            'name' => $user['name'],
                            'email' => $user['email'],
                            'municipality' => $user['municipality'],
                            'barangay' => $user['barangay'],
                            'sex' => $user['sex'],
                            'birthday' => $user['birthday'],
                            'age' => '',
                            'is_pregnant' => $user['is_pregnant'],
                            'weight' => $user['weight'],
                            'height' => $user['height'],
                            'muac_cm' => '',
                            'bmi' => '',
                            'bmi_category' => '',
                            'muac_category' => '',
                            'bmi' => '',
                            'screening_date' => $user['screening_date'],
                            'screened_by' => '',
                            'notes' => '',
                            'status' => '',
                            'created_at' => '',
                            'updated_at' => ''
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            }
            break;
        
        // ========================================
        // SIMPLE WEIGHT HEIGHT TEST
        // ========================================
        case 'simple_test':
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    $email = $data['email'] ?? '';
                    
                    $pdo = $db->getPDO();
                    $stmt = $pdo->prepare("SELECT weight, height FROM community_users WHERE email = ?");
                    $stmt->execute([$email]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo json_encode([
                        'success' => true,
                        'weight' => $result['weight'] ?? 'NULL',
                        'height' => $result['height'] ?? 'NULL'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // TEST DATA RETRIEVAL API
        // ========================================
        case 'test_get_data':
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    $email = $data['email'] ?? '';
                    
                    if (empty($email)) {
                        echo json_encode(['success' => false, 'message' => 'Email is required']);
                        break;
                    }
                    
                    // Direct database query
                    $pdo = $db->getPDO();
                    $stmt = $pdo->prepare("SELECT name, email, weight, height, municipality, barangay FROM community_users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        echo json_encode([
                            'success' => true,
                            'raw_data' => $user,
                            'weight' => $user['weight'],
                            'height' => $user['height']
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // DEDICATED SCREENING SAVE API
        // ========================================
        case 'save_screening_direct':
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Get JSON data
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    
                    if (!$data) {
                        echo json_encode(['success' => false, 'message' => 'No data provided']);
                        break;
                    }
                    
                    $email = $data['email'] ?? '';
                    $name = $data['name'] ?? '';
                    $municipality = $data['municipality'] ?? '';
                    $barangay = $data['barangay'] ?? '';
                    $sex = $data['sex'] ?? '';
                    $birthday = $data['birthday'] ?? '';
                    $is_pregnant = $data['is_pregnant'] ?? 'No';
                    $weight = $data['weight'] ?? '';
                    $height = $data['height'] ?? '';
                    $muac = $data['muac'] ?? '';
                    
                    if (empty($email)) {
                        echo json_encode(['success' => false, 'message' => 'Email is required']);
                        break;
                    }
                    
                    // Direct database connection
                    $pdo = $db->getPDO();
                    
                    // Check if user exists
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM community_users WHERE email = ?");
                    $checkStmt->execute([$email]);
                    $userExists = $checkStmt->fetchColumn() > 0;
                    
                    if ($userExists) {
                        // Update existing user with direct SQL
                        $updateSql = "UPDATE community_users SET 
                                        municipality = ?, 
                                        barangay = ?, 
                                        sex = ?, 
                                        birthday = ?, 
                                        is_pregnant = ?, 
                                        weight = ?, 
                                        height = ?, 
                                        muac = ?, 
                                        screening_date = NOW()
                                      WHERE email = ?";
                        
                        $updateStmt = $pdo->prepare($updateSql);
                        $result = $updateStmt->execute([
                            $municipality, $barangay, $sex, $birthday, $is_pregnant, 
                            $weight, $height, $muac, $email
                        ]);
                        
                        if ($result) {
                            // Verify the update worked
                            $verifyStmt = $pdo->prepare("SELECT weight, height, muac_cm FROM community_users WHERE email = ?");
                            $verifyStmt->execute([$email]);
                            $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo json_encode([
                                'success' => true, 
                                'message' => 'Screening data updated successfully',
                                'action' => 'updated',
                                'saved_data' => $savedData,
                                'email' => $email
                            ]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to update screening data']);
                        }
                    } else {
                        // Create new user with direct SQL
                        $insertSql = "INSERT INTO community_users 
                                     (name, email, municipality, barangay, sex, birthday, is_pregnant, weight, height, muac_cm, screening_date) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                        
                        $insertStmt = $pdo->prepare($insertSql);
                        $result = $insertStmt->execute([
                            $name, $email, $municipality, $barangay, $sex, $birthday, 
                            $is_pregnant, $weight, $height, $muac
                        ]);
                        
                        if ($result) {
                            // Verify the insert worked
                            $verifyStmt = $pdo->prepare("SELECT weight, height, muac_cm FROM community_users WHERE email = ?");
                            $verifyStmt->execute([$email]);
                            $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                            
                            echo json_encode([
                                'success' => true, 
                                'message' => 'User created and screening data saved successfully',
                                'action' => 'created',
                                'saved_data' => $savedData,
                                'email' => $email
                            ]);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
                        }
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                }
            } catch (Exception $e) {
                error_log("Screening direct error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // SAVE SCREENING DATA API
        // ========================================
        case 'save_screening':
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Try to get JSON data first
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    
                    // If JSON parsing fails, try form data
                    if (!$data) {
                        $data = $_POST;
                    }
                    
                    if (!$data) {
                        echo json_encode(['success' => false, 'message' => 'No data provided']);
                        break;
                    }
                    
                    error_log("Save screening data: " . print_r($data, true));
                
                $email = $data['email'] ?? '';
                $name = $data['name'] ?? '';
                $password = $data['password'] ?? '';
                $municipality = $data['municipality'] ?? '';
                $barangay = $data['barangay'] ?? '';
                $sex = $data['sex'] ?? '';
                $birthday = $data['birthday'] ?? '';
                $is_pregnant = parsePregnantStatus($data['is_pregnant'] ?? 'No');
                $weight = $data['weight'] ?? '0';
                $height = $data['height'] ?? '0';
                
                if (empty($email)) {
                    echo json_encode(['success' => false, 'message' => 'Email is required']);
                    break;
                }
                
                // Check if user exists
                error_log("Checking if user exists for email: " . $email);
                $checkResult = $db->universalSelect('community_users', 'email', 'email = ?', '', '', [$email]);
                error_log("Check result: " . print_r($checkResult, true));
                
                if ($checkResult['success'] && !empty($checkResult['data'])) {
                    // User exists, update their screening data
                    $updateData = [
                        'municipality' => $municipality,
                        'barangay' => $barangay,
                        'sex' => $sex,
                        'birthday' => $birthday,
                        'is_pregnant' => $is_pregnant,
                        'weight' => $weight,
                        'height' => $height,
                        'screening_date' => date('Y-m-d H:i:s')
                    ];
                    
                    // Use direct SQL instead of universalUpdate
                    try {
                        $pdo = $db->getPDO();
                        $updateSql = "UPDATE community_users SET 
                                        municipality = ?, 
                                        barangay = ?, 
                                        sex = ?, 
                                        birthday = ?, 
                                        is_pregnant = ?, 
                                        weight = ?, 
                                        height = ?, 
                                        screening_date = NOW()
                                      WHERE email = ?";
                        
                        $updateStmt = $pdo->prepare($updateSql);
                        $result = $updateStmt->execute([
                            $municipality, $barangay, $sex, $birthday, $is_pregnant, 
                            $weight, $height, $email
                        ]);
                        
                        // Verify the update worked
                        if ($result) {
                            $verifyStmt = $pdo->prepare("SELECT weight, height FROM community_users WHERE email = ?");
                            $verifyStmt->execute([$email]);
                            $savedData = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                            error_log("Data saved verification: " . print_r($savedData, true));
                        }
                    } catch (Exception $directUpdateError) {
                        error_log("Direct update error: " . $directUpdateError->getMessage());
                        $result = ['success' => false, 'message' => $directUpdateError->getMessage()];
                    }
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Screening data updated successfully',
                            'data' => $updateData
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update screening data']);
                    }
                } else {
                    // User doesn't exist, create new user with screening data
                    if (empty($name)) {
                        echo json_encode(['success' => false, 'message' => 'Name is required for new users']);
                        break;
                    }
                    
                    // Generate a default password if none provided (for screening-only users)
                    if (empty($password)) {
                        $password = 'screening_user_' . time() . '_' . rand(1000, 9999);
                    }
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insertData = [
                        'name' => $name,
                        'email' => $email,
                        'password' => $hashedPassword,
                        'municipality' => $municipality,
                        'barangay' => $barangay,
                        'sex' => $sex,
                        'birthday' => $birthday,
                        'is_pregnant' => $is_pregnant,
                        'weight' => $weight,
                        'height' => $height,
                        'screening_date' => date('Y-m-d H:i:s')
                    ];
                    
                    $result = $db->universalInsert('community_users', $insertData);
                    
                    if ($result['success']) {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'User created and screening data saved successfully',
                            'data' => $insertData
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to create user: ' . $result['message']]);
                    }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            } catch (Exception $e) {
                error_log("Save screening error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // REGISTER API
        // ========================================
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Try to get JSON data first
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                // If JSON parsing fails, try form data
                if (!$data) {
                    $data = $_POST;
                }
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'No data provided']);
                    break;
                }
                
                $username = $data['username'] ?? '';
                $email = $data['email'] ?? '';
                $password = $data['password'] ?? '';
                
                if (empty($username) || empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
                    break;
                }
                
                if (strlen($password) < 6) {
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                    break;
                }
                
                $result = $db->registerUser($username, $email, $password);
                
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        // ========================================
        // FCM TOKEN REGISTRATION API
        // ========================================
        case 'register_fcm':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
                    break;
                }
                
                $fcmToken = $data['fcm_token'] ?? '';
                $deviceName = $data['device_name'] ?? '';
                $userEmail = $data['user_email'] ?? '';
                $userBarangay = $data['user_barangay'] ?? '';
                $appVersion = $data['app_version'] ?? '1.0';
                $platform = $data['platform'] ?? 'android';
                
                if (empty($fcmToken)) {
                    echo json_encode(['success' => false, 'message' => 'FCM token is required']);
                    break;
                }
                
                $result = $db->registerFCMToken($fcmToken, $deviceName, $userEmail, $userBarangay, $appVersion, $platform);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        // ========================================
        // AI FOOD RECOMMENDATIONS API
        // ========================================
        case 'save_recommendation':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
                    break;
                }
                
                $result = $db->saveAIRecommendation(
                    $data['user_email'] ?? '',
                    $data['food_name'] ?? '',
                    $data['food_emoji'] ?? '',
                    $data['food_description'] ?? '',
                    $data['ai_reasoning'] ?? '',
                    $data['nutritional_priority'] ?? 'general',
                    $data['ingredients'] ?? '',
                    $data['benefits'] ?? '',
                    $data['nutritional_impact_score'] ?? 0
                );
                
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        case 'get_recommendations':
            $userEmail = $_GET['user_email'] ?? $_POST['user_email'] ?? '';
            $limit = $_GET['limit'] ?? $_POST['limit'] ?? 10;
            
            if (empty($userEmail)) {
                echo json_encode(['success' => false, 'message' => 'User email is required']);
                break;
            }
            
            $recommendations = $db->getAIRecommendations($userEmail, $limit);
            echo json_encode(['success' => true, 'data' => $recommendations]);
            break;
            
        // ========================================
        // USER PREFERENCES API
        // ========================================
        case 'save_preferences':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
                    break;
                }
                
                $userId = $data['user_id'] ?? '';
                $preferences = $data['preferences'] ?? [];
                
                if (empty($userId)) {
                    echo json_encode(['success' => false, 'message' => 'User ID is required']);
                    break;
                }
                
                $result = $db->saveUserPreferences($userId, $preferences);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        case 'get_preferences':
            $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
            
            if (empty($userId)) {
                echo json_encode(['success' => false, 'message' => 'User ID is required']);
                break;
            }
            
            $preferences = $db->getUserPreferences($userId);
            echo json_encode(['success' => true, 'data' => $preferences]);
            break;
            
        // ========================================
        // ANALYTICS API
        // ========================================
        case 'community_metrics':
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            $metrics = $db->getCommunityMetrics($barangay);
            echo json_encode(['success' => true, 'data' => $metrics]);
            break;
            
        case 'geographic_distribution':
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            $distribution = $db->getGeographicDistribution($barangay);
            echo json_encode(['success' => true, 'data' => $distribution]);
            break;
            
        case 'risk_distribution':
            error_log("API: risk_distribution called");
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            $risks = $db->getRiskDistribution($barangay);
            error_log("API: risk_distribution result: " . json_encode($risks));
            echo json_encode(['success' => true, 'data' => $risks]);
            break;
            
        case 'notification_stats':
            $stats = $db->getNotificationStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'recent_notifications':
            $limit = $_GET['limit'] ?? $_POST['limit'] ?? 50;
            $logs = $db->getRecentNotificationLogs($limit);
            echo json_encode(['success' => true, 'data' => $logs]);
            break;
            
        case 'detailed_screening_responses':
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            $responses = $db->getDetailedScreeningResponses($timeFrame, $barangay);
            echo json_encode(['success' => true, 'data' => $responses]);
            break;
            
        case 'get_who_classifications':
            $whoStandard = $_GET['who_standard'] ?? $_POST['who_standard'] ?? 'weight-for-age';
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            $result = $db->getWHOClassifications($whoStandard, $timeFrame, $barangay);
            echo json_encode($result);
            break;
            
        case 'get_all_who_classifications_bulk':
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            $whoStandard = $_GET['who_standard'] ?? $_POST['who_standard'] ?? '';
            
            $result = $db->getAllWHOClassificationsBulk($timeFrame, $barangay, $whoStandard);
            echo json_encode($result);
            break;
            
        case 'get_barangay_distribution_bulk':
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            $result = $db->getBarangayDistributionBulk($barangay);
            echo json_encode($result);
            break;
            
        case 'get_gender_distribution_bulk':
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            $result = $db->getGenderDistributionBulk($barangay);
            echo json_encode($result);
            break;
            
        case 'get_all_classifications':
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            // Get classifications from all WHO standards
            $whoStandards = ['weight-for-age', 'height-for-age', 'weight-for-height', 'bmi-for-age'];
            $allClassifications = [];
            
            foreach ($whoStandards as $standard) {
                $result = $db->getWHOClassifications($standard, $timeFrame, $barangay);
                if ($result['success'] && isset($result['data']['classifications'])) {
                    foreach ($result['data']['classifications'] as $classification => $count) {
                        if ($count > 0) {
                            $key = $classification . '_' . $standard;
                            $allClassifications[$key] = [
                                'classification' => $classification,
                                'count' => $count,
                                'standard' => $standard,
                                'standard_label' => strtoupper(str_replace('-', '', substr($standard, 0, 1)) . substr($standard, strpos($standard, '-') + 1, 1) . substr($standard, strrpos($standard, '-') + 1, 1))
                            ];
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'data' => $allClassifications]);
            break;
            
        case 'critical_alerts':
            $alerts = $db->getCriticalAlerts();
            echo json_encode(['success' => true, 'data' => $alerts]);
            break;
            
        case 'analysis_data':
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            $analysis = $db->getAnalysisData($timeFrame, $barangay);
            echo json_encode(['success' => true, 'data' => $analysis]);
            break;
            
        case 'intelligent_programs':
            $userEmail = $_GET['user_email'] ?? $_POST['user_email'] ?? '';
            $limit = $_GET['limit'] ?? $_POST['limit'] ?? 10;
            
            $programs = $db->getAIRecommendations($userEmail, $limit);
            echo json_encode(['success' => true, 'data' => $programs]);
            break;
            
        case 'comprehensive_screening':
            $db->handleComprehensiveScreening();
            break;
            
        case 'ai_food_recommendations':
            $userEmail = $_GET['user_email'] ?? $_POST['user_email'] ?? '';
            $limit = $_GET['limit'] ?? $_POST['limit'] ?? 10;
            
            if (empty($userEmail)) {
                echo json_encode(['success' => false, 'message' => 'User email is required']);
                break;
            }
            
            $recommendations = $db->getAIRecommendations($userEmail, $limit);
            echo json_encode(['success' => true, 'data' => $recommendations]);
            break;
            
        case 'intelligent_programs':
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            $municipality = $_GET['municipality'] ?? $_POST['municipality'] ?? '';
            
            $programs = $db->getIntelligentPrograms($barangay, $municipality);
            echo json_encode(['success' => true, 'data' => $programs]);
            break;
            
        case 'who_classification_data':
            $whoStandard = $_GET['who_standard'] ?? $_POST['who_standard'] ?? 'weight-for-age';
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            // Include the WHO classification functions (no HTML output)
            require_once __DIR__ . '/who_classification_functions.php';
            
            // Use DatabaseHelper for data operations
            $dbHelper = DatabaseHelper::getInstance();
            $data = getWHOClassificationData($dbHelper, $timeFrame, $barangay, $whoStandard);
            echo json_encode($data);
            break;
            
        case 'time_frame_data':
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            $timeFrameData = $db->getTimeFrameData($timeFrame, $barangay);
            echo json_encode(['success' => true, 'data' => $timeFrameData]);
            break;
            
        case 'get_community_users':
            // Get all user preferences data from community_users table (NOT users table)
            // This is used by settings.php to display user management interface
            try {
                $pdo = $db->getPDO();
                if (!$pdo) {
                    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                    break;
                }
                
                $sql = "SELECT 
                            id,
                            user_email,
                            username,
                            name,
                            barangay,
                            income,
                            bmi,
                            created_at,
                            updated_at
                        FROM community_users
                        ORDER BY updated_at DESC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format users data for settings.php
                $formattedUsers = [];
                foreach ($users as $user) {
                    $formattedUsers[] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['user_email'],
                        'name' => $user['name'] ?? 'N/A',
                        'barangay' => $user['barangay'],
                        'municipality' => 'N/A', // Municipality not stored in community_users table
                        'income' => $user['income'],
                        'bmi' => $user['bmi'],
                        'created_at' => $user['created_at'],
                        'updated_at' => $user['updated_at']
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $formattedUsers,
                    'message' => 'User preferences retrieved successfully'
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => 'Failed to get users: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // EMAIL VERIFICATION API (RESEND)
        // ========================================
        case 'register_resend':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'No data received']);
                    break;
                }
                
                $username = trim($data['username'] ?? '');
                $email = trim($data['email'] ?? '');
                $password = $data['password'] ?? '';
                
                // Validation
                if (empty($username) || empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
                    break;
                }
                
                if (strlen($password) < 6) {
                    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                    break;
                }
                
                try {
                    // Check if user already exists
                    $pdo = $db->getPDO();
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
                    $stmt->execute([$email, $username]);
                    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingUser) {
                        echo json_encode(['success' => false, 'message' => 'User with this email or username already exists']);
                        break;
                    }
                    
                    // Generate verification code
                    $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, email_verified, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                    $result = $stmt->execute([$username, $email, $hashedPassword, $verificationCode, $expiresAt]);
                    
                    if (!$result) {
                        echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
                        break;
                    }
                    
                    $userId = $pdo->lastInsertId();
                    
                    // Send email using Resend API
                    $emailSent = sendResendEmail($email, $username, $verificationCode);
                    
                    if (!$emailSent) {
                        // If email sending failed, still return success but warn user
                        error_log("Email sending failed for user $username ($email) but registration completed");
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => $emailSent ? 
                            'Registration successful! Please check your email for verification code.' : 
                            'Registration successful! However, email delivery failed. Your verification code is: ' . $verificationCode,
                        'requires_verification' => true,
                        'data' => [
                            'user_id' => $userId,
                            'username' => $username,
                            'email' => $email,
                            'email_sent' => $emailSent,
                            'verification_code' => $verificationCode // For testing purposes
                        ]
                    ]);
                    
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        case 'verify_resend':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'No data received']);
                    break;
                }
                
                $email = trim($data['email'] ?? '');
                $verificationCode = trim($data['verification_code'] ?? '');
                
                // Validation
                if (empty($email) || empty($verificationCode)) {
                    echo json_encode(['success' => false, 'message' => 'Please provide email and verification code']);
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
                    break;
                }
                
                try {
                    $pdo = $db->getPDO();
                    
                    // Find user
                    $stmt = $pdo->prepare("SELECT user_id, username, verification_code, verification_code_expires, email_verified FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$user) {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        break;
                    }
                    
                    if ($user['email_verified']) {
                        echo json_encode(['success' => false, 'message' => 'Email is already verified']);
                        break;
                    }
                    
                    // Check if code is expired
                    if (strtotime($user['verification_code_expires']) < time()) {
                        echo json_encode(['success' => false, 'message' => 'Verification code has expired']);
                        break;
                    }
                    
                    // Verify code
                    if ($user['verification_code'] !== $verificationCode) {
                        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
                        break;
                    }
                    
                    // Mark email as verified
                    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = ?");
                    $result = $stmt->execute([$user['user_id']]);
                    
                    if (!$result) {
                        echo json_encode(['success' => false, 'message' => 'Failed to verify email']);
                        break;
                    }
                    
                    // Send welcome email using Resend
                    sendWelcomeEmail($email, $user['username']);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Email verified successfully! Welcome to Nutrisaur.',
                        'data' => [
                            'user_id' => $user['user_id'],
                            'username' => $user['username'],
                            'email' => $email,
                            'email_verified' => true
                        ]
                    ]);
                    
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        case 'resend_verification_resend':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'No data received']);
                    break;
                }
                
                $email = trim($data['email'] ?? '');
                
                // Validation
                if (empty($email)) {
                    echo json_encode(['success' => false, 'message' => 'Email is required']);
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
                    break;
                }
                
                try {
                    $pdo = $db->getPDO();
                    
                    // Find user
                    $stmt = $pdo->prepare("SELECT user_id, username, email_verified FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$user) {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        break;
                    }
                    
                    if ($user['email_verified']) {
                        echo json_encode(['success' => false, 'message' => 'Email is already verified']);
                        break;
                    }
                    
                    // Generate new verification code
                    $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    // Update user with new verification code
                    $stmt = $pdo->prepare("UPDATE users SET verification_code = ?, verification_code_expires = ? WHERE user_id = ?");
                    $result = $stmt->execute([$verificationCode, $expiresAt, $user['user_id']]);
                    
                    if (!$result) {
                        echo json_encode(['success' => false, 'message' => 'Failed to update verification code']);
                        break;
                    }
                    
                    // Send email using Resend API
                    $emailSent = sendResendEmail($email, $user['username'], $verificationCode);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Verification code sent successfully! Please check your email.',
                        'data' => [
                            'email' => $email,
                            'email_sent' => $emailSent,
                            'verification_code' => $verificationCode // For testing purposes
                        ]
                    ]);
                    
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Failed to resend verification code: ' . $e->getMessage()]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        // ========================================
        // TEST API
        // ========================================
        case 'test':
            $results = [];
            
            // Test database connection
            $results['connection_test'] = [
                'test' => 'Database Connection',
                'result' => $db->testConnection() ? 'PASSED' : 'FAILED'
            ];
            
            // Test community metrics
            $results['community_metrics'] = [
                'test' => 'Community Metrics',
                'result' => 'PASSED',
                'data' => $db->getCommunityMetrics()
            ];
            
            // Test notification stats
            $results['notification_stats'] = [
                'test' => 'Notification Statistics',
                'result' => 'PASSED',
                'data' => $db->getNotificationStats()
            ];
            
            // Test geographic distribution
            $results['geographic_distribution'] = [
                'test' => 'Geographic Distribution',
                'result' => 'PASSED',
                'data' => $db->getGeographicDistribution()
            ];
            
            // Test active FCM tokens
            $results['active_fcm_tokens'] = [
                'test' => 'Active FCM Tokens',
                'result' => 'PASSED',
                'data' => $db->getActiveFCMTokens()
            ];
            
            // Test recent notification logs
            $results['recent_notifications'] = [
                'test' => 'Recent Notification Logs',
                'result' => 'PASSED',
                'data' => $db->getRecentNotificationLogs(5)
            ];
            
            // Test custom query
            $results['custom_query'] = [
                'test' => 'Custom Query Execution',
                'result' => 'PASSED',
                'data' => $db->executeQuery("SELECT COUNT(*) as total_users FROM users")
            ];
            
            // Summary
            $passed = 0;
            $total = count($results);
            
            foreach ($results as $test) {
                if ($test['result'] === 'PASSED') {
                    $passed++;
                }
            }
            
            $summary = [
                'total_tests' => $total,
                'passed' => $passed,
                'failed' => $total - $passed,
                'success_rate' => round(($passed / $total) * 100, 2) . '%'
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'Database API Test Results',
                'summary' => $summary,
                'tests' => $results
            ], JSON_PRETTY_PRINT);
            break;
            
        // ========================================
        // SEND NOTIFICATION API
        // ========================================
        case 'test_notification':
            echo json_encode(['success' => true, 'message' => 'Test notification action works']);
            break;
            
        case 'send_notification':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $notificationData = $_POST['notification_data'] ?? '';
                
                // Debug logging
                error_log("NOTIFICATION_DEBUG: Raw notification data: " . $notificationData);
                
                if (empty($notificationData)) {
                    error_log("NOTIFICATION_DEBUG: No notification data received");
                    echo json_encode(['success' => false, 'message' => 'Notification data is required']);
                    break;
                }
                
                $data = json_decode($notificationData, true);
                
                if (!$data) {
                    error_log("NOTIFICATION_DEBUG: Invalid JSON format");
                    echo json_encode(['success' => false, 'message' => 'Invalid notification data format']);
                    break;
                }
                
                $title = $data['title'] ?? '';
                $body = $data['body'] ?? '';
                $targetUser = $data['target_user'] ?? '';
                $alertType = $data['alert_type'] ?? '';
                $userName = $data['user_name'] ?? '';
                
                // Debug logging
                error_log("NOTIFICATION_DEBUG: Parsed data - Title: $title, Body: $body, Target User: $targetUser");
                
                if (empty($title) || empty($body)) {
                    error_log("NOTIFICATION_DEBUG: Missing title or body");
                    echo json_encode(['success' => false, 'message' => 'Title and body are required']);
                    break;
                }
                
                // Get FCM tokens for the target user from community_users table
                $fcmTokens = [];
                if (!empty($targetUser) && $targetUser !== 'all') {
                    error_log("NOTIFICATION_DEBUG: Looking for FCM tokens for specific user: $targetUser");
                    $stmt = $db->getPDO()->prepare("SELECT fcm_token FROM community_users WHERE email = :email AND fcm_token IS NOT NULL AND fcm_token != ''");
                    $stmt->bindParam(':email', $targetUser);
                    $stmt->execute();
                    $fcmTokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    error_log("NOTIFICATION_DEBUG: Found " . count($fcmTokens) . " FCM tokens for user $targetUser");
                } else {
                    error_log("NOTIFICATION_DEBUG: Looking for all FCM tokens");
                    // Send to all tokens if no specific user or target is 'all'
                    $stmt = $db->getPDO()->prepare("SELECT fcm_token FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != ''");
                    $stmt->execute();
                    $fcmTokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    error_log("NOTIFICATION_DEBUG: Found " . count($fcmTokens) . " total active FCM tokens");
                    
                    // Debug: Check what's actually in the community_users table
                    $debugStmt = $db->getPDO()->prepare("SELECT COUNT(*) as total FROM community_users");
                    $debugStmt->execute();
                    $totalUsers = $debugStmt->fetch(PDO::FETCH_ASSOC)['total'];
                    error_log("NOTIFICATION_DEBUG: Total users in community_users table: $totalUsers");
                    
                    $debugStmt2 = $db->getPDO()->prepare("SELECT COUNT(*) as total FROM community_users WHERE fcm_token IS NOT NULL");
                    $debugStmt2->execute();
                    $usersWithFCM = $debugStmt2->fetch(PDO::FETCH_ASSOC)['total'];
                    error_log("NOTIFICATION_DEBUG: Users with FCM tokens: $usersWithFCM");
                    
                    $debugStmt3 = $db->getPDO()->prepare("SELECT email, fcm_token FROM community_users WHERE fcm_token IS NOT NULL AND fcm_token != '' LIMIT 5");
                    $debugStmt3->execute();
                    $sampleTokens = $debugStmt3->fetchAll(PDO::FETCH_ASSOC);
                    error_log("NOTIFICATION_DEBUG: Sample FCM tokens: " . json_encode($sampleTokens));
                }
                
                if (empty($fcmTokens)) {
                    error_log("NOTIFICATION_DEBUG: No FCM tokens found");
                    echo json_encode(['success' => false, 'message' => 'No active FCM tokens found']);
                    break;
                }
                
                // Send notification to each token
                $successCount = 0;
                $failCount = 0;
                
                error_log("NOTIFICATION_DEBUG: Starting to send notifications to " . count($fcmTokens) . " tokens");
                
                foreach ($fcmTokens as $fcmToken) {
                    try {
                        error_log("NOTIFICATION_DEBUG: Sending notification to token: " . substr($fcmToken, 0, 20) . "...");
                        
                        // Send actual FCM notification using Firebase Admin SDK
                        $fcmResult = sendFCMNotificationToToken($fcmToken, $title, $body);
                        
                        if ($fcmResult['success']) {
                            // Log successful notification
                            $db->logNotification(
                                null, // event_id
                                $fcmToken,
                                $title,
                                $body,
                                'success',
                                $fcmResult['response']
                            );
                            $successCount++;
                            error_log("NOTIFICATION_DEBUG: Successfully sent FCM notification");
                        } else {
                            // Log failed notification
                            $db->logNotification(
                                null, // event_id
                                $fcmToken,
                                $title,
                                $body,
                                'failed',
                                $fcmResult['error']
                            );
                            $failCount++;
                            error_log("NOTIFICATION_DEBUG: FCM notification failed: " . $fcmResult['error']);
                        }
                    } catch (Exception $e) {
                        error_log("NOTIFICATION_DEBUG: Exception sending notification to token: " . $e->getMessage());
                        $failCount++;
                    }
                }
                
                error_log("NOTIFICATION_DEBUG: Notification sending completed - Success: $successCount, Failed: $failCount");
                
                echo json_encode([
                    'success' => true,
                    'message' => "Notification sent successfully to {$successCount} devices",
                    'success_count' => $successCount,
                    'fail_count' => $failCount
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            }
            break;
            
        // ========================================
        // UNIVERSAL DATABASE OPERATIONS
        // ========================================
        case 'select':
            // Universal SELECT operation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                
                $table = $input['table'] ?? '';
                $columns = $input['columns'] ?? '*';
                $where = $input['where'] ?? '';
                $orderBy = $input['order_by'] ?? '';
                $limit = $input['limit'] ?? '';
                $params = $input['params'] ?? [];
                
                if (empty($table)) {
                    echo json_encode(['success' => false, 'message' => 'Table name is required']);
                    break;
                }
                
                $result = $db->universalSelect($table, $columns, $where, $orderBy, $limit, $params);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'insert':
            // Universal INSERT operation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                
                $table = $input['table'] ?? '';
                $data = $input['data'] ?? [];
                
                if (empty($table) || empty($data)) {
                    echo json_encode(['success' => false, 'message' => 'Table and data are required']);
                    break;
                }
                
                $result = $db->universalInsert($table, $data);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'update':
            // Universal UPDATE operation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                
                $table = $input['table'] ?? '';
                $data = $input['data'] ?? [];
                $where = $input['where'] ?? '';
                $params = $input['params'] ?? [];
                
                if (empty($table) || empty($data) || empty($where)) {
                    echo json_encode(['success' => false, 'message' => 'Table, data, and where conditions are required']);
                    break;
                }
                
                $result = $db->universalUpdate($table, $data, $where, $params);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'delete':
            // Universal DELETE operation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                
                $table = $input['table'] ?? '';
                $where = $input['where'] ?? '';
                $params = $input['params'] ?? [];
                
                if (empty($table) || empty($where)) {
                    echo json_encode(['success' => false, 'message' => 'Table and where conditions are required']);
                    break;
                }
                
                $result = $db->universalDelete($table, $where, $params);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'query':
            // Universal custom query operation
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                
                $sql = $input['sql'] ?? '';
                $params = $input['params'] ?? [];
                
                if (empty($sql)) {
                    echo json_encode(['success' => false, 'message' => 'SQL query is required']);
                    break;
                }
                
                $result = $db->universalQuery($sql, $params);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
            }
            break;
            
        case 'describe':
            // Get table structure
            if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
                $table = $_GET['table'] ?? $_POST['table'] ?? '';
                
                if (empty($table)) {
                    echo json_encode(['success' => false, 'message' => 'Table name is required']);
                    break;
                }
                
                $result = $db->describeTable($table);
                echo json_encode($result);
            } else {
                echo json_encode(['success' => false, 'message' => 'GET or POST method required']);
            }
            break;
            
        case 'tables':
            // List all tables
            $result = $db->listTables();
            echo json_encode($result);
            break;
            
        // ========================================
        // GET COMMUNITY USER DATA API
        // ========================================
        case 'get_community_user_data':
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    $email = $data['email'] ?? '';
                    
                    if (empty($email)) {
                        echo json_encode(['success' => false, 'message' => 'Email is required']);
                        break;
                    }
                    
                    $pdo = $db->getPDO();
                    $stmt = $pdo->prepare("SELECT * FROM community_users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'User data retrieved successfully',
                            'user' => [
                                'name' => $user['name'] ?? '',
                                'email' => $user['email'] ?? '',
                                'municipality' => $user['municipality'] ?? '',
                                'barangay' => $user['barangay'] ?? '',
                                'sex' => $user['sex'] ?? '',
                                'birthday' => $user['birthday'] ?? '',
                                'age' => $user['age'] ?? '',
                                'is_pregnant' => $user['is_pregnant'] ?? '',
                                'weight' => $user['weight'] ?? '',
                                'height' => $user['height'] ?? '',
                                'muac_cm' => '',
                                'bmi' => $user['bmi'] ?? '',
                                'bmi_category' => $user['bmi_category'] ?? '',
                                'muac_category' => $user['muac_category'] ?? '',
                                'bmi' => $user['bmi'] ?? '',
                                'screening_date' => $user['screening_date'] ?? '',
                                'screened_by' => $user['screened_by'] ?? '',
                                'notes' => $user['notes'] ?? '',
                                'status' => $user['status'] ?? '',
                                'created_at' => $user['created_at'] ?? '',
                                'updated_at' => $user['updated_at'] ?? ''
                            ]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'POST method required']);
                }
            } catch (Exception $e) {
                error_log("Get community user data error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error retrieving user data: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // UPDATE COMMUNITY USER API
        // ========================================
        case 'update_community_user':
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    $originalEmail = $data['original_email'] ?? '';
                    $newEmail = $data['email'] ?? '';
                    
                    error_log("UPDATE_DEBUG: Received data: " . json_encode($data));
                    error_log("UPDATE_DEBUG: Original email: " . $originalEmail);
                    error_log("UPDATE_DEBUG: New email: " . $newEmail);
                    
                    if (empty($originalEmail)) {
                        error_log("UPDATE_DEBUG: Original email is empty");
                        echo json_encode(['success' => false, 'message' => 'Original email is required']);
                        break;
                    }
                    
                    $pdo = $db->getPDO();
                    if (!$pdo) {
                        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
                        break;
                    }
                    
                    // Check if user exists (using original email)
                    error_log("UPDATE_DEBUG: Checking if user exists with original email: " . $originalEmail);
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM community_users WHERE email = ?");
                    $checkStmt->execute([$originalEmail]);
                    $userExists = $checkStmt->fetchColumn() > 0;
                    error_log("UPDATE_DEBUG: User exists check result: " . ($userExists ? 'YES' : 'NO'));
                    
                    if (!$userExists) {
                        error_log("UPDATE_DEBUG: User not found for original email: " . $originalEmail);
                        echo json_encode(['success' => false, 'message' => 'User not found']);
                        break;
                    }
                    
                    // Prepare update data
                    $updateFields = [];
                    $updateValues = [];
                    
                    if (isset($data['name']) && !empty($data['name'])) {
                        $updateFields[] = "name = ?";
                        $updateValues[] = $data['name'];
                    }
                    
                    if (isset($data['height']) && !empty($data['height'])) {
                        $updateFields[] = "height = ?";
                        $updateValues[] = $data['height'];
                    }
                    
                    if (isset($data['weight']) && !empty($data['weight'])) {
                        $updateFields[] = "weight = ?";
                        $updateValues[] = $data['weight'];
                    }
                    
                    if (isset($data['birthday']) && !empty($data['birthday'])) {
                        $updateFields[] = "birthday = ?";
                        $updateValues[] = $data['birthday'];
                    }
                    
                    if (isset($data['sex']) && !empty($data['sex'])) {
                        $updateFields[] = "sex = ?";
                        $updateValues[] = $data['sex'];
                    }
                    
                    if (isset($data['municipality']) && !empty($data['municipality'])) {
                        $updateFields[] = "municipality = ?";
                        $updateValues[] = $data['municipality'];
                    }
                    
                    if (isset($data['barangay']) && !empty($data['barangay'])) {
                        $updateFields[] = "barangay = ?";
                        $updateValues[] = $data['barangay'];
                    }
                    
                    if (isset($data['is_pregnant']) && !empty($data['is_pregnant'])) {
                        $isPregnant = ($data['is_pregnant'] === 'Yes') ? 1 : 0;
                        $updateFields[] = "is_pregnant = ?";
                        $updateValues[] = $isPregnant;
                    }
                    
                    if (isset($data['muac']) && !empty($data['muac'])) {
                        $updateFields[] = "muac = ?";
                        $updateValues[] = $data['muac'];
                    }
                    
                    if (isset($data['email']) && !empty($data['email']) && $data['email'] !== $originalEmail) {
                        $updateFields[] = "email = ?";
                        $updateValues[] = $data['email'];
                    }
                    
                    // Always update screening_date
                    $updateFields[] = "screening_date = NOW()";
                    
                    if (empty($updateFields)) {
                        echo json_encode(['success' => false, 'message' => 'No fields to update']);
                        break;
                    }
                    
                    // Add original email for WHERE clause
                    $updateValues[] = $originalEmail;
                    
                    $updateSql = "UPDATE community_users SET " . implode(", ", $updateFields) . " WHERE email = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $result = $updateStmt->execute($updateValues);
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'User profile updated successfully',
                            'updated_fields' => $updateFields
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update user profile']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'POST method required']);
                }
            } catch (Exception $e) {
                error_log("Update community user error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error updating user profile: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // CHECK EMAIL EXISTS API
        // ========================================
        case 'check_email_exists':
            try {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $input = file_get_contents('php://input');
                    $data = json_decode($input, true);
                    $email = $data['email'] ?? '';
                    
                    if (empty($email)) {
                        echo json_encode(['success' => false, 'message' => 'Email is required']);
                        break;
                    }
                    
                    $pdo = $db->getPDO();
                    if (!$pdo) {
                        echo json_encode(['success' => false, 'message' => 'Database connection not available']);
                        break;
                    }
                    
                    // Check if email exists in community_users table
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM community_users WHERE email = ?");
                    $stmt->execute([$email]);
                    $count = $stmt->fetchColumn();
                    
                    echo json_encode([
                        'success' => true,
                        'exists' => $count > 0,
                        'message' => $count > 0 ? 'Email already exists' : 'Email is available'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'POST method required']);
                }
            } catch (Exception $e) {
                error_log("Check email exists error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Error checking email: ' . $e->getMessage()]);
            }
            break;
            
        // ========================================
        // GET AGE CLASSIFICATIONS API
        // ========================================
        case 'get_age_classifications':
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            $ageFromMonths = $_GET['age_from_months'] ?? $_POST['age_from_months'] ?? 0;
            $ageToMonths = $_GET['age_to_months'] ?? $_POST['age_to_months'] ?? 71;
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            
            // Function to generate age groups based on custom range with intelligent granularity
            function generateAgeGroups($fromMonths, $toMonths) {
                $ageGroups = [];
                $currentMonth = $fromMonths;
                
                error_log("Generating age groups from $fromMonths to $toMonths months");
                
                // Always create granular groups to capture all data
                if (($toMonths - $fromMonths) <= 6) {
                    // Very small range: monthly groups
                    while ($currentMonth < $toMonths) {
                        $nextMonth = min($currentMonth + 1, $toMonths);
                        $label = $currentMonth . 'm';
                        $ageGroups[$label] = [$currentMonth, $nextMonth];
                        $currentMonth = $nextMonth;
                    }
                } elseif (($toMonths - $fromMonths) <= 24) {
                    // Small range: 2-month groups
                    while ($currentMonth < $toMonths) {
                        $nextMonth = min($currentMonth + 2, $toMonths);
                        $label = $currentMonth . 'm-' . ($nextMonth - 1) . 'm';
                        $ageGroups[$label] = [$currentMonth, $nextMonth];
                        $currentMonth = $nextMonth;
                    }
                } elseif (($toMonths - $fromMonths) <= 60) {
                    // Medium range: 3-month groups
                    while ($currentMonth < $toMonths) {
                        $nextMonth = min($currentMonth + 3, $toMonths);
                        $label = $currentMonth . 'm-' . ($nextMonth - 1) . 'm';
                        $ageGroups[$label] = [$currentMonth, $nextMonth];
                        $currentMonth = $nextMonth;
                    }
                } else {
                    // Large range: 6-month groups
                    while ($currentMonth < $toMonths) {
                        $nextMonth = min($currentMonth + 6, $toMonths);
                        $fromYears = floor($currentMonth / 12);
                        $toYears = floor(($nextMonth - 1) / 12);
                        
                        if ($fromYears == $toYears) {
                            $label = $fromYears . 'y';
                        } else {
                            $label = $fromYears . 'y-' . $toYears . 'y';
                        }
                        
                        $ageGroups[$label] = [$currentMonth, $nextMonth];
                        $currentMonth = $nextMonth;
                        
                        // Safety check to prevent infinite loops
                        if ($currentMonth >= $toMonths) {
                            break;
                        }
                    }
                }
                
                error_log("Generated " . count($ageGroups) . " age groups: " . json_encode($ageGroups));
                return $ageGroups;
            }
            
            // Helper function to calculate age (same as dashboard_assessment_stats.php)
            function calculateAge($birthday) {
                $birthDate = new DateTime($birthday);
                $today = new DateTime();
                $age = $today->diff($birthDate);
                return $age->y + ($age->m / 12);
            }
            
            try {
                // Generate age groups based on custom range
                $ageGroups = generateAgeGroups($ageFromMonths, $ageToMonths);
                
                // Debug: Log the generated age groups
                error_log("Generated age groups for range $ageFromMonths to $ageToMonths: " . json_encode($ageGroups));
                
                // Debug: Log the age group ranges in detail
                foreach ($ageGroups as $label => $range) {
                    error_log("Age group '$label': months {$range[0]} to {$range[1]}");
                }
                
                $classifications = ['Normal', 'Overweight', 'Obese', 'Underweight', 'Severely Underweight', 'Stunted', 'Severely Stunted', 'Wasted', 'Severely Wasted', 'Tall'];
                
                $ageClassificationData = [];
                
                // Build WHERE clause
                $whereClause = "1=1";
                $params = [];
                
                // Add barangay filter
                if (!empty($barangay)) {
                    $whereClause .= " AND barangay = :barangay";
                    $params[':barangay'] = $barangay;
                }
                
                // Add time frame filter (same logic as other APIs)
                $timeFrameCondition = '';
                switch ($timeFrame) {
                    case '1d':
                        $timeFrameCondition = "screening_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                        break;
                    case '7d':
                        $timeFrameCondition = "screening_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        break;
                    case '30d':
                        $timeFrameCondition = "screening_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                        break;
                    case '90d':
                        $timeFrameCondition = "screening_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
                        break;
                    case '1y':
                        $timeFrameCondition = "screening_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                        break;
                    default:
                        $timeFrameCondition = "screening_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                        break;
                }
                
                if (!empty($timeFrameCondition)) {
                    $whereClause .= " AND $timeFrameCondition";
                }
                
                // Get all users in the filtered group with weight and height data
                $userQuery = "SELECT email, birthday, sex, screening_date, weight, height FROM community_users WHERE $whereClause";
                $userResult = $db->executeQuery($userQuery, $params);
                
                
                // Check if executeQuery returned an error
                if (isset($userResult['error']) || empty($userResult)) {
                    echo json_encode(['success' => true, 'data' => []]);
                    break;
                }
                
                $users = $userResult;
                $totalUsers = count($users);
                
                // Log basic info for debugging
                error_log("Age Classification API - Total users: $totalUsers");
                
                // Debug: Log first few users' data
                if ($totalUsers > 0) {
                    error_log("Sample user data: " . json_encode(array_slice($users, 0, 3)));
                    
                    // Debug: Calculate age for first few users
                    for ($i = 0; $i < min(3, count($users)); $i++) {
                        $user = $users[$i];
                        $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                        error_log("User $i: birthday={$user['birthday']}, screening_date={$user['screening_date']}, ageInMonths=$ageInMonths");
                    }
                }
                
                // Initialize WHO growth standards
                require_once __DIR__ . '/../../who_growth_standards.php';
                $who = new WHOGrowthStandards();
                
                // Process each age group
                foreach ($ageGroups as $ageGroup => $ageRange) {
                    $ageGroupUsers = [];
                    
                    // Filter users by age group using screening date
                    $usersInRange = 0;
                    foreach ($users as $user) {
                        // Use screening date for age calculation
                        $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                        
                        // Debug: Log age calculation for first few users
                        if ($usersInRange < 3) {
                            error_log("User age calculation: birthday={$user['birthday']}, screening_date={$user['screening_date']}, ageInMonths=$ageInMonths, ageGroup=$ageGroup, range=[{$ageRange[0]}, {$ageRange[1]})");
                        }
                        
                        if ($ageInMonths >= $ageRange[0] && $ageInMonths < $ageRange[1]) {
                            $ageGroupUsers[] = $user;
                            $usersInRange++;
                        }
                    }
                    
                    // Debug: Log how many users were found in this age range
                    error_log("Age group $ageGroup: Found $usersInRange users in range [{$ageRange[0]}, {$ageRange[1]})");
                    
                    if (empty($ageGroupUsers)) {
                        // No users in this age group, set all classifications to 0
                        error_log("Age group $ageGroup: No users found");
                        foreach ($classifications as $classification) {
                            $ageClassificationData["{$ageGroup}_{$classification}"] = 0;
                        }
                        continue;
                    }
                    
                    error_log("Age group $ageGroup: " . count($ageGroupUsers) . " users found");
                    
                    // Calculate classifications for THIS age group only (simplified to weight-for-age only)
                    $ageGroupClassifications = [];
                    
                    // Process each user in this age group
                    foreach ($ageGroupUsers as $user) {
                        try {
                            // Use getComprehensiveAssessment for weight-for-age only (simplified)
                            $assessment = $who->getComprehensiveAssessment(
                                floatval($user['weight']), 
                                floatval($user['height']), 
                                $user['birthday'], 
                                $user['sex'],
                                $user['screening_date'] ?? null
                            );
                            
                            if ($assessment['success'] && isset($assessment['results'])) {
                                $results = $assessment['results'];
                                $classification = $results['weight_for_age']['classification'] ?? 'No Data';
                                
                                if ($classification && $classification !== 'No Data') {
                                    if (!isset($ageGroupClassifications[$classification])) {
                                        $ageGroupClassifications[$classification] = 0;
                                    }
                                    $ageGroupClassifications[$classification]++;
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Error processing user for age classification: " . $e->getMessage());
                            continue;
                        }
                    }
                    
                    // Calculate percentages for this age group
                    $ageGroupTotal = array_sum($ageGroupClassifications);
                    
                    // Debug: Log age group classification results
                    error_log("Age group $ageGroup classifications: " . json_encode($ageGroupClassifications) . " (total: $ageGroupTotal)");
                    
                    foreach ($classifications as $classification) {
                        $count = $ageGroupClassifications[$classification] ?? 0;
                        $percentage = $ageGroupTotal > 0 ? round(($count / $ageGroupTotal) * 100, 1) : 0;
                        $ageClassificationData["{$ageGroup}_{$classification}"] = $percentage;
                    }
                }
                
                // Debug: Log the age classification data
                error_log("Age Classification Data: " . json_encode($ageClassificationData));
                
                echo json_encode([
                    'success' => true,
                    'data' => $ageClassificationData
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error getting age classifications: ' . $e->getMessage()
                ]);
            }
            break;
            
        // ========================================
        // GET USERS FOR AGE CLASSIFICATION API
        // ========================================
        case 'get_users_for_age_classification':
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            try {
                // Get all users with their basic info for age classification
                $users = $db->getDetailedScreeningResponses($timeFrame, $barangay);
                
                if (empty($users)) {
                    echo json_encode([
                        'success' => true,
                        'data' => []
                    ]);
                    break;
                }
                
                // Return users with their basic info for frontend processing
                echo json_encode([
                    'success' => true,
                    'data' => $users
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error getting users for age classification: ' . $e->getMessage()
                ]);
            }
            break;
            
        // ========================================
        // AGE CLASSIFICATION CHART API
        // ========================================
    case 'get_age_classification_chart':
        try {
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $fromMonths = isset($_GET['from_months']) ? intval($_GET['from_months']) : 0;
            $toMonths = isset($_GET['to_months']) ? intval($_GET['to_months']) : 71;
            $whoStandard = $_GET['who_standard'] ?? $_POST['who_standard'] ?? 'weight-for-age';

            error_log("🔍 Age Classification Chart API - Starting");
        error_log("  - Barangay: " . ($barangay ?: 'empty'));
        error_log("  - Time Frame: " . $timeFrame);
        error_log("  - Age Range: {$fromMonths} to {$toMonths} months");
        error_log("  - WHO Standard: " . $whoStandard);
            
            try {
                // Get users data using the same method as other functions
                $users = $db->getDetailedScreeningResponses($timeFrame, $barangay);
                
                error_log("  - Users found: " . count($users));
                
                // Apply age filtering based on the requested range
                require_once __DIR__ . '/../../who_growth_standards.php';
                $who = new WHOGrowthStandards();
                $filteredUsers = [];
                
                // Use the requested range directly for user filtering (no WHO limits)
                $effectiveFromMonths = max($fromMonths, 0);
                $effectiveToMonths = $toMonths;
                
                foreach ($users as $user) {
                    $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                    // Filter by the effective age range
                    if ($ageInMonths >= $effectiveFromMonths && $ageInMonths <= $effectiveToMonths) {
                        $filteredUsers[] = $user;
                    }
                }
                $users = $filteredUsers;
                
                error_log("  - Users after age filtering: " . count($users));
                
                if (empty($users)) {
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'ageGroups' => [],
                            'classifications' => [],
                            'chartData' => []
                        ]
                    ]);
                    break;
                }
                
                // Create dynamic age groups - always 10 equal columns for any range
                $ageGroups = [];
                $numGroups = 10; // Always create 10 age groups
                
                // Calculate the range span
                $rangeSpan = $toMonths - $fromMonths;
                $groupSize = $rangeSpan / $numGroups;
                
                // Create 10 equal age groups
                for ($i = 0; $i < $numGroups; $i++) {
                    $groupStart = $fromMonths + ($i * $groupSize);
                    $groupEnd = $fromMonths + (($i + 1) * $groupSize);
                    
                    // Format the label based on the range and values
                    // Always show months for ranges that include 0-2 years (0-24 months)
                    if ($fromMonths < 24 && $toMonths <= 24) {
                        // For ranges within 0-24 months, always show in months
                        $label = round($groupStart) . '-' . round($groupEnd) . 'm';
                    } elseif ($rangeSpan <= 12) {
                        // For small ranges (≤12 months), show in months
                        $label = round($groupStart) . '-' . round($groupEnd) . 'm';
                    } elseif ($rangeSpan <= 120) {
                        // For medium ranges (≤10 years), show in years with decimals
                        $startYears = round($groupStart / 12, 1);
                        $endYears = round($groupEnd / 12, 1);
                        $label = $startYears . '-' . $endYears . 'y';
                    } else {
                        // For large ranges (>10 years), show in years as integers
                        $startYears = round($groupStart / 12);
                        $endYears = round($groupEnd / 12);
                        $label = $startYears . '-' . $endYears . 'y';
                    }
                    
                    // Ensure labels are unique and properly formatted
                    if ($groupStart == $groupEnd) {
                        $label = round($groupStart) . 'm';
                    }
                    
                    $ageGroups[$label] = [round($groupStart), round($groupEnd)];
                }
                
                // Define classifications based on WHO standard
                $allClassifications = [
                    'weight-for-age' => ['Severely Underweight', 'Underweight', 'Normal', 'Overweight', 'Obese'],
                    'height-for-age' => ['Severely Stunted', 'Stunted', 'Normal', 'Tall'],
                    'weight-for-height' => ['Severely Wasted', 'Wasted', 'Normal', 'Overweight', 'Obese'],
                    'bmi-for-age' => ['Severely Underweight', 'Underweight', 'Normal', 'Overweight', 'Obese'],
                    'bmi-adult' => ['Underweight', 'Normal', 'Overweight', 'Obese']
                ];
                
                $classifications = $allClassifications[$whoStandard] ?? $allClassifications['weight-for-age'];
                error_log("  - Using classifications for $whoStandard: " . json_encode($classifications));
                
                // Initialize chart data structure
                $chartData = [];
                foreach ($ageGroups as $ageGroup => $range) {
                    $chartData[$ageGroup] = [];
                    foreach ($classifications as $classification) {
                        $chartData[$ageGroup][$classification] = 0;
                    }
                }
                
                // Process each user using the EXACT same logic as getAllWHOClassificationsBulk
                foreach ($users as $user) {
                    try {
                        // Calculate age in months (same as donut chart)
                        $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                        
                        // Determine which age group this user belongs to
                        $userAgeGroup = null;
                        foreach ($ageGroups as $ageGroup => $range) {
                            if ($ageInMonths >= $range[0] && $ageInMonths < $range[1]) {
                                $userAgeGroup = $ageGroup;
                                break;
                            }
                        }
                        
                        if (!$userAgeGroup) {
                            continue; // Skip users outside the age range
                        }
                        
                        // Process only the specific WHO standard
                        $classification = null;
                        
                        // Check age eligibility for the specific WHO standard
                        $isEligible = false;
                        switch ($whoStandard) {
                            case 'weight-for-age':
                            case 'height-for-age':
                                $isEligible = ($ageInMonths >= 0 && $ageInMonths <= 71);
                                break;
                            case 'weight-for-height':
                                $isEligible = ($ageInMonths >= 0 && $ageInMonths <= 60);
                                break;
                            case 'bmi-for-age':
                                $isEligible = ($ageInMonths >= 24 && $ageInMonths <= 228);
                                break;
                            case 'bmi-adult':
                                $isEligible = ($ageInMonths >= 228);
                                break;
                        }
                        
                        if ($isEligible) {
                            // Get assessment for the specific WHO standard
                            $assessment = $who->getComprehensiveAssessment(
                                floatval($user['weight']),
                                floatval($user['height']),
                                $user['birthday'],
                                $user['sex'],
                                $user['screening_date'] ?? null
                            );
                            
                            if ($assessment['success'] && isset($assessment['results'])) {
                                $results = $assessment['results'];
                                $standardKey = str_replace('-', '_', $whoStandard);
                                
                                if (isset($results[$standardKey]['classification'])) {
                                    $classification = $results[$standardKey]['classification'];
                                }
                            }
                        }
                        
                        if ($classification && in_array($classification, $classifications)) {
                            $chartData[$userAgeGroup][$classification]++;
                        }

                    } catch (Exception $e) {
                        error_log("  - Error processing user: " . $e->getMessage());
                        continue;
                    }
                }
                
                // Use the actual filtered users count instead of total users
                $filteredUsersCount = count($users);
                
                // Calculate population increment based on filtered users
                $populationIncrement = max(1, ceil($filteredUsersCount / 10));
                
                // Debug: Log the population calculation
                error_log("🔍 Population Calculation:");
                error_log("  - Filtered users count: " . $filteredUsersCount);
                error_log("  - Population increment: " . $populationIncrement);
                
                // Create 10-row scale: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
                $populationScale = [];
                for ($i = 1; $i <= 10; $i++) {
                    $populationScale[] = $i * $populationIncrement;
                }
                
                // Prepare data for Chart.js line chart with population increments
                $lineChartData = [];
                foreach ($classifications as $classification) {
                    $lineChartData[$classification] = [];
                    foreach ($ageGroups as $ageGroup => $range) {
                        $rawCount = $chartData[$ageGroup][$classification] ?? 0;
                        // Convert to population increment (1-10 scale)
                        $populationValue = min(10, ceil($rawCount / $populationIncrement));
                        $lineChartData[$classification][] = $populationValue;
                    }
                }
                
                // Debug: Log the response data
                error_log("🔍 Age Classification Chart API - Response Data:");
                error_log("  - Age Groups: " . json_encode(array_keys($ageGroups)));
                error_log("  - Classifications: " . json_encode($classifications));
                error_log("  - Filtered Users: " . $filteredUsersCount);
                error_log("  - Population Increment: " . $populationIncrement);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'ageGroups' => array_keys($ageGroups),
                        'classifications' => $classifications,
                        'chartData' => $lineChartData,
                        'rawData' => $chartData,
                        'totalUsers' => $filteredUsersCount,
                        'populationIncrement' => $populationIncrement,
                        'populationScale' => $populationScale
                    ]
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error getting age classification chart data: ' . $e->getMessage()
                ]);
            }
        } catch (Exception $e) {
            error_log("🔍 Age Classification Chart API - Fatal Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Fatal error in age classification chart API: ' . $e->getMessage()
            ]);
        }
        break;
            
        // ========================================
        // DEFAULT: SHOW USAGE - Fixed syntax errors
        // ========================================
        default:
            echo json_encode([
                'success' => true,
                'message' => 'Nutrisaur Universal Database API',
                'universal_operations' => [
                    'select' => 'POST /DatabaseAPI.php?action=select (table, columns, where, order_by, limit, params)',
                    'insert' => 'POST /DatabaseAPI.php?action=insert (table, data)',
                    'update' => 'POST /DatabaseAPI.php?action=update (table, data, where, params)',
                    'delete' => 'POST /DatabaseAPI.php?action=delete (table, where, params)',
                    'query' => 'POST /DatabaseAPI.php?action=query (sql, params)',
                    'describe' => 'GET /DatabaseAPI.php?action=describe&table=table_name',
                    'tables' => 'GET /DatabaseAPI.php?action=tables'
                ],
                'legacy_operations' => [
                    'login' => 'POST /DatabaseAPI.php?action=login',
                    'register' => 'POST /DatabaseAPI.php?action=register',
                    'register_fcm' => 'POST /DatabaseAPI.php?action=register_fcm',
                    'save_recommendation' => 'POST /DatabaseAPI.php?action=save_recommendation',
                    'get_recommendations' => 'GET /DatabaseAPI.php?action=get_recommendations&user_email=...',
                    'save_preferences' => 'POST /DatabaseAPI.php?action=save_preferences',
                    'get_preferences' => 'GET /DatabaseAPI.php?action=get_preferences&user_id=...',
                    'community_metrics' => 'GET /DatabaseAPI.php?action=community_metrics',
                    'geographic_distribution' => 'GET /DatabaseAPI.php?action=geographic_distribution',
                    'risk_distribution' => 'GET /DatabaseAPI.php?action=risk_distribution',
                    'get_community_users' => 'GET /DatabaseAPI.php?action=get_community_users',
                    'test' => 'GET /DatabaseAPI.php?action=test'
                ],
                'note' => 'Universal Database API - handles ALL database operations centrally. No more hardcoded connections!'
            ], JSON_PRETTY_PRINT);
            break;
    }
    
    // Close the database connection
    $db->close();
}

?>
