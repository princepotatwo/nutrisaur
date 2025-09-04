<?php
// Verify 4-digit code (no email required)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers are now set by the router
session_start();

try {
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
        
        $email = $data['email'] ?? '';
        $verificationCode = $data['verification_code'] ?? '';
        
        if (empty($email) || empty($verificationCode)) {
            echo json_encode(['success' => false, 'message' => 'Please provide email and verification code']);
            exit;
        }
        
        // Find user with this email and verification code
        $stmt = $pdo->prepare("SELECT user_id, username, email, verification_code, verification_code_expires FROM users WHERE email = :email AND verification_code = :verification_code");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':verification_code', $verificationCode);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check if verification code is expired
            if (strtotime($user['verification_code_expires']) < time()) {
                echo json_encode(['success' => false, 'message' => 'Verification code has expired. Please register again.']);
                exit;
            }
            
            // Mark user as verified
            $updateStmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = :user_id");
            $updateStmt->bindParam(':user_id', $user['user_id']);
            
            if ($updateStmt->execute()) {
                // Auto-login the user
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Email verified successfully! You are now logged in.',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'email' => $user['email']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Verification failed. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code or email.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log("Verify Code Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?>
