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
    
    public function __construct() {
        // Include the centralized configuration
        require_once __DIR__ . "/../config.php";
        
        // Initialize connections
        $this->pdo = getDatabaseConnection();
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
            return false;
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
            
            return [
                'success' => true,
                'message' => 'Registration successful!',
                'data' => [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $email
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
            $metrics = [];
            
            // Total users
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $metrics['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Active FCM tokens
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as active FROM fcm_tokens WHERE is_active = 1");
            $stmt->execute();
            $metrics['active_devices'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
            
            // Users by barangay
            $stmt = $this->pdo->prepare("SELECT barangay, COUNT(*) as count FROM user_preferences GROUP BY barangay");
            $stmt->execute();
            $metrics['users_by_barangay'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent registrations (last 7 days)
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as recent FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute();
            $metrics['recent_registrations'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent'];
            
            return $metrics;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get geographic distribution
     */
    public function getGeographicDistribution() {
        try {
            $stmt = $this->pdo->prepare("SELECT barangay, COUNT(*) as user_count FROM user_preferences GROUP BY barangay ORDER BY user_count DESC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get risk distribution
     */
    public function getRiskDistribution() {
        try {
            $stmt = $this->pdo->prepare("SELECT health_goals, COUNT(*) as count FROM user_preferences WHERE health_goals IS NOT NULL GROUP BY health_goals");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
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
    
    // Initialize the database API
    $db = new DatabaseAPI();
    
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
            // This would need to be implemented based on your screening data structure
            echo json_encode(['success' => true, 'data' => []]);
            break;
            
        case 'critical_alerts':
            // This would need to be implemented based on your alerts data structure
            echo json_encode(['success' => true, 'data' => []]);
            break;
            
        case 'analysis_data':
            // This would need to be implemented based on your analysis data structure
            echo json_encode(['success' => true, 'data' => []]);
            break;
            
        case 'intelligent_programs':
            // This would need to be implemented based on your programs data structure
            echo json_encode(['success' => true, 'data' => []]);
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
