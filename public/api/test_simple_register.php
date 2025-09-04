<?php
// Simple registration test without email
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
    error_log("=== SIMPLE REGISTRATION TEST START ===");
    
    // Only include config and database
    require_once __DIR__ . "/../config.php";
    
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Try to get JSON data first
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // If JSON decoding fails, try form data
        if (!$data) {
            $data = $_POST;
        }
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'No data received']);
            exit;
        }
        
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
            exit;
        }
        
        error_log("Registration attempt - Username: $username, Email: $email");
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        
        // Generate verification code (4 digits)
        $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $verificationExpiry = date('Y-m-d H:i:s', time() + 300); // 5 minutes
        
        // Hash password and insert user with verification data
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, created_at) VALUES (:username, :email, :password, :verification_code, :verification_expiry, NOW())");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':verification_code', $verificationCode);
        $stmt->bindParam(':verification_expiry', $verificationExpiry);
        
        if ($stmt->execute()) {
            $userId = $pdo->lastInsertId();
            
            error_log("User registered successfully - ID: $userId, Code: $verificationCode");
            
            // Return success without email sending
            echo json_encode([
                'success' => true,
                'message' => 'Registration successful! Please use the verification code below.',
                'data' => [
                    'user_id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'requires_verification' => true,
                    'verification_code' => $verificationCode,
                    'verification_expires' => $verificationExpiry,
                    'email_sent' => false
                ]
            ]);
        } else {
            error_log("Database insert failed");
            echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log("Simple Registration Error: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
