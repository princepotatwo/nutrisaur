<?php
header('Content-Type: application/json');
session_start();

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
?> 