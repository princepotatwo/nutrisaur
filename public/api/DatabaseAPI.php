<?php
/**
 * Nutrisaur Complete Database API
 * Centralized database operations and API endpoints for the entire application
 * 
 * This single file provides:
 * 1. Database connection and management
 * 2. All database operations (users, admin, FCM, notifications, etc.)
 * 3. API endpoints for mobile app
 * 4. Usage examples and testing
 */

// ========================================
// DATABASE API CLASS
// ========================================

class DatabaseAPI {
    private $pdo;
    private $mysqli;
    private static $instance = null;
    
    public function __construct() {
        // Include the centralized configuration
        require_once __DIR__ . "/../config.php";
        
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
        if (!$this->pdo) {
            return false;
        }
        
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (PDOException $e) {
            error_log("Database connection test failed: " . $e->getMessage());
            // Try to re-establish connection
            $this->pdo = $this->establishPDOConnection();
            return $this->pdo !== null;
        }
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
     * Register new user
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
            
            $this->pdo->beginTransaction();
            
            // Check if username or email already exists
            $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->execute();
            
            $userId = $this->pdo->lastInsertId();
            
            $this->pdo->commit();
            
            // Auto-login the user after successful registration
            $this->autoLoginUser($userId, $username, $email);
            
            return [
                'success' => true,
                'message' => 'Registration successful! You are now logged in.',
                'data' => [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'auto_logged_in' => true
                ]
            ];
            
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
                
                if (isset($userData['admin_data'])) {
                    $_SESSION['admin_id'] = $userData['admin_data']['admin_id'];
                    $_SESSION['role'] = $userData['admin_data']['role'];
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
    public function getCommunityMetrics() {
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
            
            // Total users
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $metrics['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active FCM tokens
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as active FROM fcm_tokens WHERE is_active = 1");
                $stmt->execute();
                $metrics['active_devices'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
            } catch (PDOException $e) {
                $metrics['active_devices'] = 0;
            }
            
            // Users by barangay
            try {
                $stmt = $this->pdo->prepare("SELECT barangay, COUNT(*) as count FROM user_preferences GROUP BY barangay");
                $stmt->execute();
                $metrics['users_by_barangay'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $metrics['users_by_barangay'] = [];
            }
            
            // Recent registrations (last 7 days)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as recent FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute();
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
    public function getGeographicDistribution() {
        try {
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                return [];
            }
            
            $stmt = $this->pdo->prepare("SELECT barangay, COUNT(*) as user_count FROM user_preferences GROUP BY barangay ORDER BY user_count DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Geographic distribution error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get risk distribution
     */
    public function getRiskDistribution() {
        try {
            // Check if database connection is available
            if (!$this->isDatabaseAvailable()) {
                return [
                    'low' => 0,
                    'moderate' => 0,
                    'high' => 0,
                    'severe' => 0
                ];
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
                WHERE risk_score IS NOT NULL 
                GROUP BY risk_level
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            
            // Add time frame filter
            switch($timeFrame) {
                case '1d':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                    break;
                case '1w':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                    break;
                case '1m':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
            }
            
            // Add barangay filter
            if (!empty($barangay)) {
                $whereClause .= " AND barangay = :barangay";
                $params[':barangay'] = $barangay;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    up.*, u.username, u.email,
                    DATE_FORMAT(up.created_at, '%Y-%m-%d') as screening_date
                FROM user_preferences up
                LEFT JOIN users u ON up.user_email = u.email
                $whereClause
                ORDER BY up.created_at DESC
                LIMIT 100
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Detailed screening responses error: " . $e->getMessage());
            return [];
        }
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
                    up.*, u.username, u.email,
                    CASE 
                        WHEN up.risk_score >= 80 THEN 'Severe Risk'
                        WHEN up.risk_score >= 50 THEN 'High Risk'
                        ELSE 'Moderate Risk'
                    END as alert_level
                FROM user_preferences up
                LEFT JOIN users u ON up.user_email = u.email
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
            
            // Add time frame filter
            switch($timeFrame) {
                case '1d':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                    break;
                case '1w':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                    break;
                case '1m':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
            }
            
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
            
            // Add time frame filter
            switch($timeFrame) {
                case '1d':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                    break;
                case '1w':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                    break;
                case '1m':
                    $whereClause .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
            }
            
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
// API ENDPOINTS
// ========================================

// Only process API requests if this file is called directly
if (basename($_SERVER['SCRIPT_NAME']) === 'DatabaseAPI.php') {
    
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
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
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
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                
                if (!$data) {
                    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
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
            $metrics = $db->getCommunityMetrics();
            echo json_encode(['success' => true, 'data' => $metrics]);
            break;
            
        case 'geographic_distribution':
            $distribution = $db->getGeographicDistribution();
            echo json_encode(['success' => true, 'data' => $distribution]);
            break;
            
        case 'risk_distribution':
            $risks = $db->getRiskDistribution();
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
            
        case 'risk_distribution':
            $risks = $db->getRiskDistribution();
            echo json_encode(['success' => true, 'data' => $risks]);
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
            
        case 'time_frame_data':
            $timeFrame = $_GET['time_frame'] ?? $_POST['time_frame'] ?? '1d';
            $barangay = $_GET['barangay'] ?? $_POST['barangay'] ?? '';
            
            $timeFrameData = $db->getTimeFrameData($timeFrame, $barangay);
            echo json_encode(['success' => true, 'data' => $timeFrameData]);
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
                            'login' => 'POST /DatabaseAPI?action=login',
        'register' => 'POST /DatabaseAPI?action=register',
        'register_fcm' => 'POST /DatabaseAPI?action=register_fcm',
        'save_recommendation' => 'POST /DatabaseAPI?action=save_recommendation',
        'get_recommendations' => 'GET /DatabaseAPI?action=get_recommendations&user_email=...',
        'save_preferences' => 'POST /DatabaseAPI?action=save_preferences',
        'get_preferences' => 'GET /DatabaseAPI?action=get_preferences&user_id=...',
        'community_metrics' => 'GET /DatabaseAPI?action=community_metrics',
        'geographic_distribution' => 'GET /DatabaseAPI?action=geographic_distribution',
        'risk_distribution' => 'GET /DatabaseAPI?action=risk_distribution',
        'detailed_screening_responses' => 'GET /DatabaseAPI?action=detailed_screening_responses',
        'critical_alerts' => 'GET /DatabaseAPI?action=critical_alerts',
        'analysis_data' => 'GET /DatabaseAPI?action=analysis_data',
        'ai_food_recommendations' => 'GET /DatabaseAPI?action=get_recommendations&user_email=...',
        'intelligent_programs' => 'GET /DatabaseAPI?action=intelligent_programs',
        'send_notification' => 'POST /DatabaseAPI?action=send_notification',
        'notification_stats' => 'GET /DatabaseAPI?action=notification_stats',
        'recent_notifications' => 'GET /DatabaseAPI?action=recent_notifications&limit=50',
        'test' => 'GET /DatabaseAPI?action=test'
                ],
                'note' => 'This file contains both the DatabaseAPI class and API endpoints. Use ?action=... to access specific endpoints.'
            ], JSON_PRETTY_PRINT);
            break;
    }
    
    // Close the database connection
    $db->close();
}

?>
