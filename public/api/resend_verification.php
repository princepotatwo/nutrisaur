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
    require_once __DIR__ . "/../../config.php";
    require_once __DIR__ . "/EmailService.php";
    require_once __DIR__ . "/../../email_config.php";
    
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            exit;
        }
        
        $email = $data['email'] ?? '';
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        // Find user with the given email
        $stmt = $pdo->prepare("SELECT user_id, username, email, email_verified, verification_sent_at FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
        
        if ($user['email_verified']) {
            echo json_encode(['success' => false, 'message' => 'Email is already verified']);
            exit;
        }
        
        // Check if we can resend (prevent spam - limit to once per minute)
        if ($user['verification_sent_at']) {
            $lastSent = strtotime($user['verification_sent_at']);
            $timeDiff = time() - $lastSent;
            
            if ($timeDiff < 60) { // 60 seconds = 1 minute
                $remainingTime = 60 - $timeDiff;
                echo json_encode([
                    'success' => false, 
                    'message' => "Please wait {$remainingTime} seconds before requesting another verification code"
                ]);
                exit;
            }
        }
        
        // Generate new verification code
        $verificationCode = generateVerificationCode();
        $verificationExpiry = getVerificationExpiryTime();
        
        // Update user with new verification code
        $stmt = $pdo->prepare("UPDATE users SET verification_code = :verification_code, verification_code_expires = :verification_expiry, verification_sent_at = NOW() WHERE user_id = :user_id");
        $stmt->bindParam(':verification_code', $verificationCode);
        $stmt->bindParam(':verification_expiry', $verificationExpiry);
        $stmt->bindParam(':user_id', $user['user_id']);
        
        if ($stmt->execute()) {
            // Send new verification email
            try {
                $emailService = new EmailService();
                $emailSent = $emailService->sendVerificationEmail($user['email'], $user['username'], $verificationCode);
                
                if ($emailSent) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Verification code sent successfully! Please check your email.',
                        'data' => [
                            'user_id' => $user['user_id'],
                            'email' => $user['email']
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again later.']);
                }
            } catch (Exception $e) {
                error_log("Resend verification email error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again later.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to generate new verification code']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
    
} catch (Exception $e) {
    error_log("Resend verification API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred. Please try again later.',
        'debug' => $e->getMessage()
    ]);
}
?>
