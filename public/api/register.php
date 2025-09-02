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
?> 