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
        
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
            exit;
        }
        
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
            exit;
        }
        
        // Use the centralized registration method
        $result = $db->registerUser($username, $email, $password);
        
        if ($result['success']) {
            // Start session and set user data
            $_SESSION['user_id'] = $result['data']['user_id'];
            $_SESSION['username'] = $result['data']['username'];
            $_SESSION['email'] = $result['data']['email'];
            $_SESSION['is_admin'] = false;
            
            echo json_encode($result);
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
    error_log("Register API Error: " . $e->getMessage());
    
    // Return a proper JSON error response
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?> 