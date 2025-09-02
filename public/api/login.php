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
    // Include the centralized Database API
    require_once __DIR__ . "/DatabaseAPI.php";

    // Initialize the database API
    $db = new DatabaseAPI();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            exit;
        }
        
        $usernameOrEmail = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($usernameOrEmail) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please enter both username/email and password']);
            exit;
        }
        
        // Use the centralized authentication method
        $result = $db->authenticateUser($usernameOrEmail, $password);
        
        if ($result['success']) {
            // Set session data based on user type
            if ($result['user_type'] === 'user') {
                $_SESSION['user_id'] = $result['data']['user_id'];
                $_SESSION['username'] = $result['data']['username'];
                $_SESSION['email'] = $result['data']['email'];
                $_SESSION['is_admin'] = $result['data']['is_admin'];
                
                if ($result['data']['is_admin']) {
                    $_SESSION['admin_id'] = $result['data']['admin_data']['admin_id'];
                    $_SESSION['role'] = $result['data']['admin_data']['role'];
                }
            } else {
                $_SESSION['admin_id'] = $result['data']['admin_id'];
                $_SESSION['username'] = $result['data']['username'];
                $_SESSION['email'] = $result['data']['email'];
                $_SESSION['is_admin'] = true;
                $_SESSION['role'] = $result['data']['role'];
            }
            
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

    // Close the database connection
    $db->close();
    
} catch (Exception $e) {
    // Log the error
    error_log("Login API Error: " . $e->getMessage());
    
    // Return a proper JSON error response
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?> 