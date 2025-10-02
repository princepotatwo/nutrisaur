<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
    // Use the same working approach as debug_database_api.php
    require_once __DIR__ . "/../../config.php";
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
        
        $usernameOrEmail = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($usernameOrEmail) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please enter both username/email and password']);
            exit;
        }
        
        // Simple authentication logic
        $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);
        
        if ($isEmail) {
            $stmt = $pdo->prepare("SELECT user_id, username, email, password, is_active FROM users WHERE email = :email");
            $stmt->bindParam(':email', $usernameOrEmail);
        } else {
            $stmt = $pdo->prepare("SELECT user_id, username, email, password, is_active FROM users WHERE username = :username");
            $stmt->bindParam(':username', $usernameOrEmail);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                // Check if user is archived/inactive
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    echo json_encode(['success' => false, 'message' => 'Your account has been archived. Please contact an administrator.']);
                    exit;
                }
                
                // Login successful - no email verification required for existing users
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful!',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'email' => $user['email']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log("Login API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?> 