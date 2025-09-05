<?php
/**
 * Unified API for NutriSaur Dashboard
 * Handles all API endpoints for the settings.php dashboard
 * Uses DatabaseAPI.php for all database operations
 */

// Start output buffering to prevent any HTML output from interfering with JSON
ob_start();

// Disable error display to prevent HTML error messages
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers for JSON responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if config.php exists
$configPath = __DIR__ . '/../config.php';
if (!file_exists($configPath)) {
    throw new Exception('config.php not found at: ' . $configPath);
}

// Include the DatabaseAPI
$dbApiPath = __DIR__ . '/api/DatabaseAPI.php';
if (!file_exists($dbApiPath)) {
    throw new Exception('DatabaseAPI.php not found at: ' . $dbApiPath);
}
require_once $dbApiPath;

// Start session for authentication
session_start();

// Get the endpoint from the request
$endpoint = $_GET['endpoint'] ?? $_POST['endpoint'] ?? '';

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Get DatabaseAPI instance
    $db = DatabaseAPI::getInstance();
    
    // Get database status for debugging
    $dbStatus = $db->getDatabaseStatus();
    error_log('Database Status: ' . json_encode($dbStatus));
    
    // Check if database is available
    if (!$db->isDatabaseAvailable()) {
        $response['message'] = 'Database connection not available. Status: ' . json_encode($dbStatus);
        error_log('Database not available: ' . json_encode($dbStatus));
    } else {
        // Route to appropriate endpoint handler
        switch ($endpoint) {
            case 'usm':
                handleUSMEndpoint($db, $response);
                break;
                
            case 'add_user':
                handleAddUserEndpoint($db, $response);
                break;
                
            case 'update_user':
                handleUpdateUserEndpoint($db, $response);
                break;
                
            case 'delete_user':
                handleDeleteUserEndpoint($db, $response);
                break;
                
            case 'delete_users_by_location':
                handleDeleteUsersByLocationEndpoint($db, $response);
                break;
                
            case 'delete_all_users':
                handleDeleteAllUsersEndpoint($db, $response);
                break;
                
            case 'test':
                $response['success'] = true;
                $response['message'] = 'API is working correctly';
                $response['data'] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'endpoint' => $endpoint,
                    'database_status' => $dbStatus
                ];
                break;
                
            default:
                $response['message'] = 'Invalid endpoint: ' . $endpoint;
                break;
        }
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    $response['debug'] = [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    error_log('Unified API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}

// Clean any output buffer content and return JSON response
ob_clean();
echo json_encode($response);
exit();

/**
 * Handle USM (User Settings Management) endpoint
 * Returns all users with their details
 */
function handleUSMEndpoint($db, &$response) {
    try {
        $pdo = $db->getPDO();
        if (!$pdo) {
            throw new Exception('Database connection not available');
        }
        
        // Test the connection first
        $pdo->query("SELECT 1");
        
        // Query to get all users with their details
        $sql = "SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.date_of_birth,
                    u.gender,
                    u.weight,
                    u.height,
                    u.barangay,
                    u.municipality,
                    u.income_level,
                    u.created_at,
                    u.last_login,
                    u.is_verified,
                    u.is_active,
                    COUNT(s.id) as screening_count
                FROM users u
                LEFT JOIN screening_responses s ON u.id = s.user_id
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare SQL statement: ' . implode(', ', $pdo->errorInfo()));
        }
        
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception('Failed to execute SQL statement: ' . implode(', ', $stmt->errorInfo()));
        }
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the data for the frontend
        $formattedUsers = [];
        foreach ($users as $user) {
            $formattedUsers[] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'name' => trim($user['first_name'] . ' ' . $user['last_name']),
                'date_of_birth' => $user['date_of_birth'],
                'gender' => $user['gender'],
                'weight' => $user['weight'],
                'height' => $user['height'],
                'barangay' => $user['barangay'],
                'municipality' => $user['municipality'],
                'income_level' => $user['income_level'],
                'created_at' => $user['created_at'],
                'last_login' => $user['last_login'],
                'is_verified' => (bool)$user['is_verified'],
                'is_active' => (bool)$user['is_active'],
                'screening_count' => (int)$user['screening_count']
            ];
        }
        
        $response['success'] = true;
        $response['data'] = $formattedUsers;
        $response['message'] = 'Users retrieved successfully';
        
    } catch (Exception $e) {
        $response['message'] = 'Error retrieving users: ' . $e->getMessage();
        error_log('USM Endpoint Error: ' . $e->getMessage());
    }
}

/**
 * Handle add user endpoint
 * Creates a new user
 */
function handleAddUserEndpoint($db, &$response) {
    try {
        // Check if user is logged in (admin check)
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Authentication required');
        }
        
        $pdo = $db->getPDO();
        if (!$pdo) {
            throw new Exception('Database connection not available');
        }
        
        // Get POST data
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Hash password
        $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
        
        // Insert new user
        $sql = "INSERT INTO users (
                    username, email, password, first_name, last_name,
                    date_of_birth, gender, weight, height, barangay,
                    municipality, income_level, is_verified, is_active
                ) VALUES (
                    :username, :email, :password, :first_name, :last_name,
                    :date_of_birth, :gender, :weight, :height, :barangay,
                    :municipality, :income_level, :is_verified, :is_active
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':username' => $input['username'],
            ':email' => $input['email'],
            ':password' => $hashedPassword,
            ':first_name' => $input['first_name'],
            ':last_name' => $input['last_name'],
            ':date_of_birth' => $input['date_of_birth'] ?? null,
            ':gender' => $input['gender'] ?? null,
            ':weight' => $input['weight'] ?? null,
            ':height' => $input['height'] ?? null,
            ':barangay' => $input['barangay'] ?? null,
            ':municipality' => $input['municipality'] ?? null,
            ':income_level' => $input['income_level'] ?? null,
            ':is_verified' => $input['is_verified'] ?? 1,
            ':is_active' => $input['is_active'] ?? 1
        ]);
        
        $userId = $pdo->lastInsertId();
        
        $response['success'] = true;
        $response['data'] = ['user_id' => $userId];
        $response['message'] = 'User created successfully';
        
    } catch (Exception $e) {
        $response['message'] = 'Error creating user: ' . $e->getMessage();
        error_log('Add User Endpoint Error: ' . $e->getMessage());
    }
}

/**
 * Handle update user endpoint
 * Updates an existing user
 */
function handleUpdateUserEndpoint($db, &$response) {
    try {
        // Check if user is logged in (admin check)
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Authentication required');
        }
        
        $pdo = $db->getPDO();
        if (!$pdo) {
            throw new Exception('Database connection not available');
        }
        
        // Get POST data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['id'])) {
            throw new Exception('User ID is required');
        }
        
        $userId = $input['id'];
        
        // Build update query dynamically based on provided fields
        $updateFields = [];
        $params = [':id' => $userId];
        
        $allowedFields = [
            'username', 'email', 'first_name', 'last_name', 'date_of_birth',
            'gender', 'weight', 'height', 'barangay', 'municipality',
            'income_level', 'is_verified', 'is_active'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }
        
        // Add password update if provided
        if (!empty($input['password'])) {
            $updateFields[] = "password = :password";
            $params[':password'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'User updated successfully';
        } else {
            $response['message'] = 'No changes made or user not found';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error updating user: ' . $e->getMessage();
        error_log('Update User Endpoint Error: ' . $e->getMessage());
    }
}

/**
 * Handle delete user endpoint
 * Deletes a single user
 */
function handleDeleteUserEndpoint($db, &$response) {
    try {
        // Check if user is logged in (admin check)
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Authentication required');
        }
        
        $pdo = $db->getPDO();
        if (!$pdo) {
            throw new Exception('Database connection not available');
        }
        
        // Get user ID from POST data or GET parameter
        $userId = $_POST['user_id'] ?? $_GET['user_id'] ?? null;
        
        if (empty($userId)) {
            throw new Exception('User ID is required');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Delete related records first
            $pdo->exec("DELETE FROM screening_responses WHERE user_id = $userId");
            $pdo->exec("DELETE FROM fcm_tokens WHERE user_id = $userId");
            $pdo->exec("DELETE FROM ai_recommendations WHERE user_email = (SELECT email FROM users WHERE id = $userId)");
            $pdo->exec("DELETE FROM user_preferences WHERE user_id = $userId");
            
            // Delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            
            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                $response['success'] = true;
                $response['message'] = 'User deleted successfully';
            } else {
                $pdo->rollback();
                $response['message'] = 'User not found';
            }
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error deleting user: ' . $e->getMessage();
        error_log('Delete User Endpoint Error: ' . $e->getMessage());
    }
}

/**
 * Handle delete users by location endpoint
 * Deletes all users from a specific location
 */
function handleDeleteUsersByLocationEndpoint($db, &$response) {
    try {
        // Check if user is logged in (admin check)
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Authentication required');
        }
        
        $pdo = $db->getPDO();
        if (!$pdo) {
            throw new Exception('Database connection not available');
        }
        
        // Get location parameters
        $barangay = $_POST['barangay'] ?? $_GET['barangay'] ?? null;
        $municipality = $_POST['municipality'] ?? $_GET['municipality'] ?? null;
        
        if (empty($barangay) && empty($municipality)) {
            throw new Exception('Barangay or municipality is required');
        }
        
        // Build WHERE clause
        $whereConditions = [];
        $params = [];
        
        if (!empty($barangay)) {
            $whereConditions[] = "barangay = :barangay";
            $params[':barangay'] = $barangay;
        }
        
        if (!empty($municipality)) {
            $whereConditions[] = "municipality = :municipality";
            $params[':municipality'] = $municipality;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Get user IDs to delete
        $sql = "SELECT id FROM users WHERE $whereClause";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($userIds)) {
            $response['message'] = 'No users found for the specified location';
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            $deletedCount = 0;
            
            foreach ($userIds as $userId) {
                // Delete related records
                $pdo->exec("DELETE FROM screening_responses WHERE user_id = $userId");
                $pdo->exec("DELETE FROM fcm_tokens WHERE user_id = $userId");
                $pdo->exec("DELETE FROM ai_recommendations WHERE user_email = (SELECT email FROM users WHERE id = $userId)");
                $pdo->exec("DELETE FROM user_preferences WHERE user_id = $userId");
                
                // Delete the user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $deletedCount += $stmt->rowCount();
            }
            
            $pdo->commit();
            $response['success'] = true;
            $response['data'] = ['deleted_count' => $deletedCount];
            $response['message'] = "Deleted $deletedCount users from the specified location";
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error deleting users by location: ' . $e->getMessage();
        error_log('Delete Users By Location Endpoint Error: ' . $e->getMessage());
    }
}

/**
 * Handle delete all users endpoint
 * Deletes all users (use with extreme caution)
 */
function handleDeleteAllUsersEndpoint($db, &$response) {
    try {
        // Check if user is logged in (admin check)
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Authentication required');
        }
        
        $pdo = $db->getPDO();
        if (!$pdo) {
            throw new Exception('Database connection not available');
        }
        
        // Additional safety check - require confirmation
        $confirm = $_POST['confirm'] ?? $_GET['confirm'] ?? false;
        if (!$confirm) {
            throw new Exception('Confirmation required for deleting all users');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Delete all related records
            $pdo->exec("DELETE FROM screening_responses");
            $pdo->exec("DELETE FROM fcm_tokens");
            $pdo->exec("DELETE FROM ai_recommendations");
            $pdo->exec("DELETE FROM user_preferences");
            
            // Delete all users
            $stmt = $pdo->prepare("DELETE FROM users");
            $stmt->execute();
            $deletedCount = $stmt->rowCount();
            
            $pdo->commit();
            $response['success'] = true;
            $response['data'] = ['deleted_count' => $deletedCount];
            $response['message'] = "Deleted all $deletedCount users";
            
        } catch (Exception $e) {
            $pdo->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Error deleting all users: ' . $e->getMessage();
        error_log('Delete All Users Endpoint Error: ' . $e->getMessage());
    }
}
?>
