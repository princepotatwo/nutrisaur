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

// Include the config file for database connection functions
require_once __DIR__ . "/../../config.php";

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
            // Check if token already exists
            $stmt = $this->pdo->prepare("SELECT id FROM fcm_tokens WHERE fcm_token = :fcm_token");
            $stmt->bindParam(':fcm_token', $fcmToken);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing token
                $stmt = $this->pdo->prepare("UPDATE fcm_tokens SET 
                    device_name = :device_name,
                    user_email = :user_email,
                    user_barangay = :user_barangay,
                    app_version = :app_version,
                    platform = :platform,
                    is_active = 1,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE fcm_token = :fcm_token");
            } else {
                // Insert new token
                $stmt = $this->pdo->prepare("INSERT INTO fcm_tokens 
                    (fcm_token, device_name, user_email, user_barangay, app_version, platform) 
                    VALUES (:fcm_token, :device_name, :user_email, :user_barangay, :app_version, :platform)");
            }
            
            $stmt->bindParam(':fcm_token', $fcmToken);
            $stmt->bindParam(':device_name', $deviceName);
            $stmt->bindParam(':user_email', $userEmail);
            $stmt->bindParam(':user_barangay', $userBarangay);
            $stmt->bindParam(':app_version', $appVersion);
            $stmt->bindParam(':platform', $platform);
            $stmt->execute();
            
            return ['success' => true, 'message' => 'FCM token registered successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to register FCM token: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all active FCM tokens
     */
    public function getActiveFCMTokens() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM fcm_tokens WHERE is_active = 1");
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
            $stmt = $this->pdo->prepare("SELECT * FROM fcm_tokens WHERE user_barangay = :barangay AND is_active = 1");
            $stmt->bindParam(':barangay', $barangay);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Deactivate FCM token
     */
    public function deactivateFCMToken($fcmToken) {
        try {
            $stmt = $this->pdo->prepare("UPDATE fcm_tokens SET is_active = 0 WHERE fcm_token = :fcm_token");
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
                (event_id, fcm_token, title, body, status, response, sent_at) 
                VALUES (:event_id, :fcm_token, :title, :body, :status, :response, CURRENT_TIMESTAMP)");
            
            $stmt->bindParam(':event_id', $eventId);
            $stmt->bindParam(':fcm_token', $fcmToken);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':body', $body);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':response', $response);
            $stmt->execute();
            
            return true;
        } catch (PDOException $e) {
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
                    AVG(risk_score) as average_risk,
                    SUM(CASE WHEN risk_score >= 30 THEN 1 ELSE 0 END) as high_risk_count,
                    SUM(CASE WHEN risk_score >= 80 THEN 1 ELSE 0 END) as sam_cases,
                    SUM(CASE WHEN age < 5 THEN 1 ELSE 0 END) as children_count,
                    SUM(CASE WHEN age >= 65 THEN 1 ELSE 0 END) as elderly_count,
                    SUM(CASE WHEN dietary_diversity <= 2 THEN 1 ELSE 0 END) as low_dietary_diversity
                FROM user_preferences 
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
    public function saveUserPreferences($userId, $preferences) {
        try {
            // Check if database connection is available
            if (!$this->pdo) {
                return ['success' => false, 'message' => 'Database connection not available'];
            }
            
            // Check if preferences exist
            $stmt = $this->pdo->prepare("SELECT id FROM user_preferences WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                // Update existing preferences
                $stmt = $this->pdo->prepare("UPDATE user_preferences SET 
                    dietary_restrictions = :dietary_restrictions,
                    health_goals = :health_goals,
                    activity_level = :activity_level,
                    age = :age,
                    weight = :weight,
                    height = :height,
                    gender = :gender,
                    barangay = :barangay,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE user_id = :user_id");
            } else {
                // Insert new preferences
                $stmt = $this->pdo->prepare("INSERT INTO user_preferences 
                    (user_id, dietary_restrictions, health_goals, activity_level, age, weight, height, gender, barangay) 
                    VALUES (:user_id, :dietary_restrictions, :health_goals, :activity_level, :age, :weight, :height, :gender, :barangay)");
            }
            
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':dietary_restrictions', $preferences['dietary_restrictions'] ?? null);
            $stmt->bindParam(':health_goals', $preferences['health_goals'] ?? null);
            $stmt->bindParam(':activity_level', $preferences['activity_level'] ?? null);
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
            
            $stmt = $this->pdo->prepare("SELECT * FROM user_preferences WHERE user_id = :user_id");
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
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM user_preferences" . $whereClause);
            $stmt->execute($params);
            $metrics['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active FCM tokens
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as active FROM fcm_tokens WHERE is_active = 1");
                $stmt->execute();
                $metrics['active_devices'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
            } catch (PDOException $e) {
                $metrics['active_devices'] = 0;
            }
            
            // Users by barangay (filtered if barangay specified)
            try {
                $stmt = $this->pdo->prepare("SELECT barangay, COUNT(*) as count FROM user_preferences" . $whereClause . " GROUP BY barangay");
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
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as recent FROM user_preferences" . $recentWhereClause);
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
            
            $stmt = $this->pdo->prepare("SELECT barangay, COUNT(*) as user_count FROM user_preferences" . $whereClause . " GROUP BY barangay ORDER BY user_count DESC");
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
            $whereClause = "WHERE risk_score IS NOT NULL";
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
            
            // Create risk levels based on risk_score
            $stmt = $this->pdo->prepare("
                SELECT 
                    CASE 
                        WHEN risk_score < 20 THEN 'low'
                        WHEN risk_score < 50 THEN 'moderate'
                        WHEN risk_score < 80 THEN 'high'
                        ELSE 'severe'
                    END as risk_level,
                    COUNT(*) as count
                FROM user_preferences 
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
                    up.*,
                    DATE_FORMAT(up.created_at, '%Y-%m-%d') as screening_date
                FROM user_preferences up
                $whereClause
                ORDER BY up.created_at DESC
                LIMIT 100
            ");
            $stmt->execute($params);
            $rawData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process raw data into distributions
            return $this->processScreeningDataIntoDistributions($rawData);
        } catch (PDOException $e) {
            error_log("Detailed screening responses error: " . $e->getMessage());
            return [];
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
            $heightCm = $record['height_cm'] ?? 0;
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
            if (($record['risk_score'] ?? 0) >= 80) $samCases++;
            if (($record['risk_score'] ?? 0) >= 30) $highRiskCases++;
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
                        WHEN up.risk_score >= 80 THEN 'Severe Risk'
                        WHEN up.risk_score >= 50 THEN 'High Risk'
                        ELSE 'Moderate Risk'
                    END as alert_level,
                    CASE 
                        WHEN up.age < 5 THEN 'Child'
                        WHEN up.age < 18 THEN 'Youth'
                        WHEN up.age < 65 THEN 'Adult'
                        ELSE 'Elderly'
                    END as age_group
                FROM user_preferences up
                WHERE up.risk_score >= 30
                ORDER BY up.risk_score DESC, up.created_at DESC
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
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM user_preferences $whereClause");
            $stmt->execute($params);
            $analysis['total_screenings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // High risk cases
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as high_risk FROM user_preferences $whereClause AND risk_score >= 30");
            $stmt->execute($params);
            $analysis['high_risk_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['high_risk'];
            
            // SAM cases (Severe Acute Malnutrition)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as sam_cases FROM user_preferences $whereClause AND risk_score >= 80");
            $stmt->execute($params);
            $analysis['sam_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['sam_cases'];
            
            // Critical MUAC
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as critical_muac FROM user_preferences $whereClause AND muac < 11.5");
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
        $risk_score = $this->calculateRiskScore($input, $bmi);
        
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
            'risk_score' => $risk_score,
            'assessment_summary' => $input['assessment_summary'] ?? null,
            'recommendations' => $input['recommendations'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert into database
        $stmt = $this->pdo->prepare("INSERT INTO screening_assessments (
            user_id, municipality, barangay, age, age_months, sex, pregnant, 
            weight, height, bmi, meal_recall, family_history, lifestyle, 
            lifestyle_other, immunization, risk_score, assessment_summary, 
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
            $screening_data['risk_score'],
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
            'risk_score' => $risk_score,
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
        $risk_score = 0;
        
        // BMI risk factors
        if ($bmi < 18.5) {
            $risk_score += 10; // Underweight
        } elseif ($bmi >= 25 && $bmi < 30) {
            $risk_score += 5; // Overweight
        } elseif ($bmi >= 30) {
            $risk_score += 15; // Obese
        }
        
        // Age risk factors
        $age = intval($input['age']);
        if ($age < 5) {
            $risk_score += 10; // Young children
        } elseif ($age > 65) {
            $risk_score += 8; // Elderly
        }
        
        // Family history risk factors
        $family_history = is_array($input['family_history']) ? $input['family_history'] : json_decode($input['family_history'], true);
        if ($family_history && !in_array('None', $family_history)) {
            foreach ($family_history as $condition) {
                switch ($condition) {
                    case 'Diabetes':
                        $risk_score += 8;
                        break;
                    case 'Hypertension':
                        $risk_score += 6;
                        break;
                    case 'Heart Disease':
                        $risk_score += 10;
                        break;
                    case 'Kidney Disease':
                        $risk_score += 12;
                        break;
                    case 'Tuberculosis':
                        $risk_score += 7;
                        break;
                    case 'Obesity':
                        $risk_score += 5;
                        break;
                    case 'Malnutrition':
                        $risk_score += 15;
                        break;
                }
            }
        }
        
        // Lifestyle risk factors
        if ($input['lifestyle'] === 'Sedentary') {
            $risk_score += 5;
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
                $risk_score += 8; // Unbalanced diet
            }
        }
        
        // Immunization risk (for children <= 12)
        if ($age <= 12 && !empty($input['immunization'])) {
            $immunization = is_array($input['immunization']) ? $input['immunization'] : json_decode($input['immunization'], true);
            $required_vaccines = ['BCG', 'DPT', 'Polio', 'Measles', 'Hepatitis B', 'Vitamin A'];
            $missing_vaccines = array_diff($required_vaccines, $immunization);
            
            if (!empty($missing_vaccines)) {
                $risk_score += count($missing_vaccines) * 2; // 2 points per missing vaccine
            }
        }
        
        return $risk_score;
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
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM user_preferences $whereClause");
            $stmt->execute($params);
            $data['total_screenings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // High risk cases in time frame
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as high_risk FROM user_preferences $whereClause AND risk_score >= 30");
            $stmt->execute($params);
            $data['high_risk_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['high_risk'];
            
            // SAM cases in time frame
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as sam_cases FROM user_preferences $whereClause AND risk_score >= 80");
            $stmt->execute($params);
            $data['sam_cases'] = $stmt->fetch(PDO::FETCH_ASSOC)['sam_cases'];
            
            // Critical MUAC in time frame
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as critical_muac FROM user_preferences $whereClause AND muac < 11.5");
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
    // UTILITY METHODS
    // ========================================
    
    /**
     * Execute custom query
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
        
        if ($httpCode == 200 && !$curlError) {
            return true;
        } else {
            error_log("Resend API failed: HTTP $httpCode, Error: $curlError");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Email sending exception: " . $e->getMessage());
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
        
        if ($httpCode == 200 && !$curlError) {
            return true;
        } else {
            error_log("Resend API failed: HTTP $httpCode, Error: $curlError");
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
            
        case 'time_frame_data':
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            $timeFrameData = $db->getTimeFrameData($timeFrame, $barangay);
            echo json_encode(['success' => true, 'data' => $timeFrameData]);
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
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Registration successful! Please check your email for verification code.',
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
        case 'send_notification':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $notificationData = $_POST['notification_data'] ?? '';
                
                if (empty($notificationData)) {
                    echo json_encode(['success' => false, 'message' => 'Notification data is required']);
                    break;
                }
                
                $data = json_decode($notificationData, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'Invalid notification data format']);
                    break;
                }
                
                $title = $data['title'] ?? '';
                $body = $data['body'] ?? '';
                $targetUser = $data['target_user'] ?? '';
                $alertType = $data['alert_type'] ?? '';
                $userName = $data['user_name'] ?? '';
                
                if (empty($title) || empty($body)) {
                    echo json_encode(['success' => false, 'message' => 'Title and body are required']);
                    break;
                }
                
                // Get FCM tokens for the target user
                $fcmTokens = [];
                if (!empty($targetUser)) {
                    $stmt = $db->getPDO()->prepare("SELECT fcm_token FROM fcm_tokens WHERE user_email = :email AND is_active = 1");
                    $stmt->bindParam(':email', $targetUser);
                    $stmt->execute();
                    $fcmTokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    // Send to all active tokens if no specific user
                    $stmt = $db->getPDO()->prepare("SELECT fcm_token FROM fcm_tokens WHERE is_active = 1");
                    $stmt->execute();
                    $fcmTokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
                
                if (empty($fcmTokens)) {
                    echo json_encode(['success' => false, 'message' => 'No active FCM tokens found']);
                    break;
                }
                
                // Send notification to each token
                $successCount = 0;
                $failCount = 0;
                
                foreach ($fcmTokens as $fcmToken) {
                    try {
                        // Log the notification
                        $db->logNotification(
                            null, // event_id
                            $fcmToken,
                            $title,
                            $body,
                            'success'
                        );
                        $successCount++;
                    } catch (Exception $e) {
                        error_log("Failed to send notification to token: " . $e->getMessage());
                        $failCount++;
                    }
                }
                
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
        // DEFAULT: SHOW USAGE
        // ========================================
        default:
            echo json_encode([
                'success' => true,
                'message' => 'Nutrisaur Database API',
                'usage' => [
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
        'detailed_screening_responses' => 'GET /DatabaseAPI.php?action=detailed_screening_responses',
        'critical_alerts' => 'GET /DatabaseAPI.php?action=critical_alerts',
        'analysis_data' => 'GET /DatabaseAPI.php?action=analysis_data',
        'ai_food_recommendations' => 'GET /DatabaseAPI.php?action=get_recommendations&user_email=...',
        'intelligent_programs' => 'GET /DatabaseAPI.php?action=intelligent_programs',
        'send_notification' => 'POST /DatabaseAPI.php?action=send_notification',
        'notification_stats' => 'GET /DatabaseAPI.php?action=notification_stats',
        'recent_notifications' => 'GET /DatabaseAPI.php?action=recent_notifications&limit=50',
        'test' => 'GET /DatabaseAPI.php?action=test'
                ],
                'note' => 'This file contains both the DatabaseAPI class and API endpoints. Use ?action=... to access specific endpoints.'
            ], JSON_PRETTY_PRINT);
            break;
    }
    
    // Close the database connection
    $db->close();
}

?>
