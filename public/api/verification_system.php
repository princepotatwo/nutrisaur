<?php
/**
 * Email Verification System using DatabaseAPI
 * Standard implementation with comprehensive debugging
 */

// Headers are now set by the router

// Debug logging
error_log("=== VERIFICATION SYSTEM DEBUG ===");
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request URI: " . $_SERVER['REQUEST_URI']);
error_log("Script Name: " . $_SERVER['SCRIPT_NAME']);

// Include required files
error_log("Including required files...");
try {
    require_once __DIR__ . "/../config.php";
    error_log("Config included successfully");
    
    require_once __DIR__ . "/DatabaseAPI.php";
    error_log("DatabaseAPI included successfully");
    
    require_once __DIR__ . "/EmailService.php";
    error_log("EmailService included successfully");
    
    require_once __DIR__ . "/../../email_config.php";
    error_log("Email config included successfully");
    
    error_log("All files included successfully");
} catch (Exception $e) {
    error_log("Error including files: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System initialization failed', 'error' => $e->getMessage()]);
    exit;
}

// Initialize DatabaseAPI
error_log("Initializing DatabaseAPI...");
try {
    $db = DatabaseAPI::getInstance();
    error_log("DatabaseAPI initialized");
    
    // Check database connection
    $dbStatus = $db->getDatabaseStatus();
    error_log("Database status: " . json_encode($dbStatus));
    
    if (!$db->isDatabaseAvailable()) {
        error_log("ERROR: Database not available");
        echo json_encode(['success' => false, 'message' => 'Database connection failed', 'debug' => $dbStatus]);
        exit;
    }
} catch (Exception $e) {
    error_log("Error initializing DatabaseAPI: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database initialization failed', 'error' => $e->getMessage()]);
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Processing POST request");
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        $data = $_POST;
        error_log("Using POST data instead of JSON");
    }
    
    error_log("Received data: " . json_encode($data));
    
    $action = $data['action'] ?? '';
    error_log("Action: " . $action);
    
    switch ($action) {
        case 'register':
            error_log("Handling registration...");
            handleRegistration($db, $data);
            break;
        case 'verify':
            error_log("Handling verification...");
            handleVerification($db, $data);
            break;
        case 'resend':
            error_log("Handling resend...");
            handleResend($db, $data);
            break;
        default:
            error_log("Invalid action: " . $action);
            echo json_encode(['success' => false, 'message' => 'Invalid action', 'debug' => ['action' => $action, 'available_actions' => ['register', 'verify', 'resend']]]);
            break;
    }
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method', 'debug' => ['method' => $_SERVER['REQUEST_METHOD']]]);
}

/**
 * Handle user registration with email verification
 */
function handleRegistration($db, $data) {
    error_log("=== REGISTRATION DEBUG ===");
    
    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    error_log("Username: " . $username);
    error_log("Email: " . $email);
    error_log("Password length: " . strlen($password));
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        error_log("Validation failed: Missing required fields");
        echo json_encode(['success' => false, 'message' => 'Please fill in all fields', 'debug' => ['username_empty' => empty($username), 'email_empty' => empty($email), 'password_empty' => empty($password)]]);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Validation failed: Invalid email format");
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        return;
    }
    
    if (strlen($password) < 6) {
        error_log("Validation failed: Password too short");
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
        return;
    }
    
    try {
        error_log("Starting registration process...");
        
        // Use DatabaseAPI's registerUser method
        $result = $db->registerUser($username, $email, $password);
        
        error_log("Registration result: " . json_encode($result));
        
        if ($result['success']) {
            error_log("Registration successful, checking for verification requirement");
            
            if (isset($result['data']['requires_verification']) && $result['data']['requires_verification']) {
                error_log("Verification required, returning verification response");
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Please check your email for verification code.',
                    'requires_verification' => true,
                    'data' => $result['data']
                ]);
            } else {
                error_log("No verification required, redirecting to dashboard");
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Redirecting to dashboard...',
                    'requires_verification' => false,
                    'data' => $result['data']
                ]);
            }
        } else {
            error_log("Registration failed: " . $result['message']);
            echo json_encode($result);
        }
    } catch (Exception $e) {
        error_log("Registration exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage(), 'debug' => ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]]);
    }
}

/**
 * Handle email verification
 */
function handleVerification($db, $data) {
    error_log("=== VERIFICATION DEBUG ===");
    
    $email = trim($data['email'] ?? '');
    $code = trim($data['verification_code'] ?? '');
    
    error_log("Email: " . $email);
    error_log("Code: " . $code);
    
    if (empty($email) || empty($code)) {
        error_log("Verification failed: Missing email or code");
        echo json_encode(['success' => false, 'message' => 'Please provide email and verification code']);
        return;
    }
    
    try {
        error_log("Looking up user for verification...");
        
        // Get PDO connection from DatabaseAPI
        $pdo = $db->getPDO();
        
        // Find user
        $stmt = $pdo->prepare("SELECT user_id, username, verification_code, verification_code_expires, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("User lookup result: " . json_encode($user));
        
        if (!$user) {
            error_log("User not found");
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($user['email_verified']) {
            error_log("Email already verified");
            echo json_encode(['success' => false, 'message' => 'Email is already verified']);
            return;
        }
        
        if ($user['verification_code'] !== $code) {
            error_log("Invalid verification code. Expected: " . $user['verification_code'] . ", Got: " . $code);
            echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
            return;
        }
        
        // Check if code is expired
        if (strtotime($user['verification_code_expires']) < time()) {
            error_log("Verification code expired");
            echo json_encode(['success' => false, 'message' => 'Verification code has expired']);
            return;
        }
        
        error_log("Code valid, marking email as verified...");
        
        // Mark email as verified
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = ?");
        
        if ($stmt->execute([$user['user_id']])) {
            error_log("Email verified successfully");
            
            // Send welcome email
            try {
                $emailService = new EmailService();
                $emailService->sendWelcomeEmail($email, $user['username']);
                error_log("Welcome email sent");
            } catch (Exception $e) {
                error_log("Welcome email failed: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Email verified successfully! Redirecting to dashboard...',
                'data' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $email
                ]
            ]);
        } else {
            error_log("Failed to update user verification status");
            echo json_encode(['success' => false, 'message' => 'Verification failed. Please try again.']);
        }
    } catch (Exception $e) {
        error_log("Verification exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage(), 'debug' => ['exception' => $e->getMessage()]]);
    }
}

/**
 * Handle resend verification code
 */
function handleResend($db, $data) {
    error_log("=== RESEND DEBUG ===");
    
    $email = trim($data['email'] ?? '');
    error_log("Email: " . $email);
    
    if (empty($email)) {
        error_log("Resend failed: Missing email");
        echo json_encode(['success' => false, 'message' => 'Please provide email address']);
        return;
    }
    
    try {
        error_log("Looking up user for resend...");
        
        // Get PDO connection from DatabaseAPI
        $pdo = $db->getPDO();
        
        // Find user
        $stmt = $pdo->prepare("SELECT user_id, username, email_verified, verification_sent_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("User lookup result: " . json_encode($user));
        
        if (!$user) {
            error_log("User not found");
            echo json_encode(['success' => false, 'message' => 'User not found']);
            return;
        }
        
        if ($user['email_verified']) {
            error_log("Email already verified");
            echo json_encode(['success' => false, 'message' => 'Email is already verified']);
            return;
        }
        
        // Check rate limit (1 minute)
        if ($user['verification_sent_at']) {
            $lastSent = strtotime($user['verification_sent_at']);
            $timeDiff = time() - $lastSent;
            error_log("Time since last sent: " . $timeDiff . " seconds");
            
            if ($timeDiff < 60) {
                error_log("Rate limit exceeded");
                echo json_encode(['success' => false, 'message' => 'Please wait at least 1 minute before requesting another code']);
                return;
            }
        }
        
        error_log("Generating new verification code...");
        
        // Generate new verification code
        $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiryTime = date('Y-m-d H:i:s', time() + 300); // 5 minutes
        
        error_log("New code: " . $verificationCode);
        error_log("Expiry: " . $expiryTime);
        
        // Update user
        $stmt = $pdo->prepare("UPDATE users SET verification_code = ?, verification_code_expires = ?, verification_sent_at = NOW() WHERE user_id = ?");
        
        if ($stmt->execute([$verificationCode, $expiryTime, $user['user_id']])) {
            error_log("User updated with new code");
            
            // Send new verification email
            try {
                $emailService = new EmailService();
                $emailSent = $emailService->sendVerificationEmail($email, $user['username'], $verificationCode);
                
                if ($emailSent) {
                    error_log("Verification email sent successfully");
                    echo json_encode(['success' => true, 'message' => 'Verification code sent successfully! Please check your email.']);
                } else {
                    error_log("Failed to send verification email");
                    echo json_encode(['success' => false, 'message' => 'Failed to send verification email. Please try again.']);
                }
            } catch (Exception $e) {
                error_log("Email service exception: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to send verification email: ' . $e->getMessage()]);
            }
        } else {
            error_log("Failed to update user with new code");
            echo json_encode(['success' => false, 'message' => 'Failed to update verification code. Please try again.']);
        }
    } catch (Exception $e) {
        error_log("Resend exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to resend verification code: ' . $e->getMessage(), 'debug' => ['exception' => $e->getMessage()]]);
    }
}

error_log("=== VERIFICATION SYSTEM END ===");
?>
