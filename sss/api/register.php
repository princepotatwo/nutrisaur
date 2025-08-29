<?php
header('Content-Type: application/json');
session_start();

// Include the centralized configuration file
require_once __DIR__ . "/../../config.php";

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
    
    try {
        // Get database connection
        $conn = getDatabaseConnection();
        
        if (!$conn) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        // Start transaction
        $conn->beginTransaction();
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->execute();
        
        // Get the new user's ID
        $userId = $conn->lastInsertId();
        
        // User profile data is now stored in user_preferences table
        // No separate user_profiles table needed
        
        // Commit the transaction
        $conn->commit();
        
        // Start session and set user data
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['is_admin'] = false;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Registration successful!',
            'data' => [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback the transaction if something failed
        if (isset($conn) && $conn) {
            $conn->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 