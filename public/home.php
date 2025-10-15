<?php
// Start the session
session_start();

// Debug: Log session info
error_log("Session started - ID: " . session_id());
error_log("Session data at start: " . print_r($_SESSION, true));

// Check if user requires password setup (security check to prevent bypassing)
if (isset($_SESSION['requires_password_setup']) && $_SESSION['requires_password_setup'] === true) {
    // Only allow access to password setup forms and AJAX handlers
    $allowedActions = ['google_setup_password', 'save_personal_info', 'debug_session', 'check_password_setup_required', 'test_basic', 'test_session'];
    $currentAction = $_POST['ajax_action'] ?? '';
    
    // If not an allowed action and not accessing password setup forms, redirect to password setup
    if (!in_array($currentAction, $allowedActions) && !isset($_GET['setup_password'])) {
        // Set a flag to show password setup form
        $_SESSION['force_password_setup'] = true;
    }
}

// Simple session test
if (isset($_POST['test_session'])) {
    $_SESSION['test'] = 'working';
    echo json_encode(['success' => true, 'session_id' => session_id(), 'test_value' => $_SESSION['test']]);
    exit;
}

// Check if user is already logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
if ($isLoggedIn) {
    // Redirect to dashboard if already logged in
    header("Location: /dash");
    exit;
}

// Use the same database connection as the working nutritional assessment system
require_once __DIR__ . "/../config.php";

$dbError = null;
$pdo = getDatabaseConnection(); // Use the working database connection function

if ($pdo === null) {
    $dbError = "Database connection unavailable. Please try again later.";
    error_log("Home page: Database connection failed - using getDatabaseConnection()");
}

$loginError = "";
$registrationError = "";
$registrationSuccess = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $usernameOrEmail = trim($_POST['username_login']);
    $password = $_POST['password_login'];
    
    // Check for hardcoded super admin first
    if ($usernameOrEmail === 'noreply.nutrisaur@gmail.com' && $password === 'admin') {
        // Set super admin session
        $_SESSION['admin_id'] = 'super_admin';
        $_SESSION['username'] = 'Super Admin';
        $_SESSION['email'] = 'noreply.nutrisaur@gmail.com';
        $_SESSION['is_admin'] = true;
        $_SESSION['is_super_admin'] = true;
        
        // Redirect to dashboard
        header("Location: /dash");
        exit;
    } else {
        // Handle regular user login
        if (empty($usernameOrEmail) || empty($password)) {
            $loginError = "Please enter both username/email and password";
        } else {
            if ($pdo === null) {
                $loginError = "Database connection unavailable. Please try again later.";
            } else {
                try {
                    // Check if user exists in users table and is active
                    $stmt = $pdo->prepare("SELECT user_id, username, email, password, email_verified, is_active FROM users WHERE email = ? OR username = ?");
                    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
                    $user = $stmt->fetch();
                
                    if ($user && password_verify($password, $user['password'])) {
                        // Check if user is archived/inactive
                        if (isset($user['is_active']) && $user['is_active'] == 0) {
                            $loginError = "Your account has been archived. Please contact an administrator.";
                        } else {
                        // Check email verification status
                        if ($user['email_verified'] == 1) {
                            // Set session variables only if email is verified
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['is_admin'] = false;
                            
                            // Update last login
                            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                            $updateStmt->execute([$user['user_id']]);
                            
                            // Redirect to dashboard
                            header("Location: /dash");
                            exit;
                        } else {
                            $loginError = "Please verify your email before logging in";
                        }
                        }
                    } else {
                        $loginError = "Invalid username/email or password";
                    }
                } catch (Exception $e) {
                    $loginError = "Login failed: " . $e->getMessage();
                    error_log("Login error: " . $e->getMessage());
                }
            }
        }
    }
}

// Handle Google OAuth requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['google_oauth'])) {
    header('Content-Type: application/json');
    
    try {
        $googleId = $_POST['google_id'];
        $email = $_POST['email'];
        $name = $_POST['name'];
        $picture = $_POST['picture'] ?? '';
        $givenName = $_POST['given_name'] ?? '';
        $familyName = $_POST['family_name'] ?? '';
        $emailVerified = $_POST['email_verified'] === '1';
        
        // Check if this is the super admin email
        if ($email === 'noreply.nutrisaur@gmail.com' && $emailVerified) {
            $_SESSION['admin_id'] = 'super_admin';
            $_SESSION['username'] = 'Super Admin';
            $_SESSION['email'] = 'noreply.nutrisaur@gmail.com';
            $_SESSION['is_admin'] = true;
            $_SESSION['is_super_admin'] = true;
            
            echo json_encode(['success' => true, 'message' => 'Super admin Google login successful', 'user_type' => 'super_admin']);
            exit;
        }
        
        if (empty($googleId) || empty($email) || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Missing required Google OAuth data']);
            exit;
        }
        
        if ($pdo === null) {
            echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
            exit;
        }
        
        // Check if user already exists by Google ID
        $stmt = $pdo->prepare("SELECT user_id, username, email, email_verified, is_active, password FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Check if user is archived/inactive
            if (isset($existingUser['is_active']) && $existingUser['is_active'] == 0) {
                echo json_encode(['success' => false, 'message' => 'Your account has been archived. Please contact an administrator.']);
                exit;
            }
            
            // Check if user still has default password
            $hasDefaultPassword = password_verify('mho123', $existingUser['password']);
            
            // User exists, log them in
            $_SESSION['user_id'] = $existingUser['user_id'];
            $_SESSION['username'] = $existingUser['username'];
            $_SESSION['email'] = $existingUser['email'];
            $_SESSION['is_admin'] = false;
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$existingUser['user_id']]);
            
            if ($hasDefaultPassword) {
                // Set session flag to require password setup
                $_SESSION['requires_password_setup'] = true;
                echo json_encode(['success' => true, 'message' => 'Google login successful', 'user_type' => 'user', 'needs_password_change' => true]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Google login successful', 'user_type' => 'user']);
            }
        } else {
            // Check if user exists by email
            $stmt = $pdo->prepare("SELECT user_id, username, email, is_active, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $userByEmail = $stmt->fetch();
            
            if ($userByEmail) {
                // Check if user is archived/inactive
                if (isset($userByEmail['is_active']) && $userByEmail['is_active'] == 0) {
                    echo json_encode(['success' => false, 'message' => 'Your account has been archived. Please contact an administrator.']);
                    exit;
                }
                
                // Check if user still has default password
                $hasDefaultPassword = password_verify('mho123', $userByEmail['password']);
                
                // Link Google account to existing user
                $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, google_name = ?, google_picture = ?, email_verified = 1 WHERE user_id = ?");
                $updateStmt->execute([$googleId, $name, $picture, $userByEmail['user_id']]);
                
                $_SESSION['user_id'] = $userByEmail['user_id'];
                $_SESSION['username'] = $userByEmail['username'];
                $_SESSION['email'] = $userByEmail['email'];
                $_SESSION['is_admin'] = false;
                
                if ($hasDefaultPassword) {
                    // Set session flag to require password setup
                    $_SESSION['requires_password_setup'] = true;
                    echo json_encode(['success' => true, 'message' => 'Google account linked successfully', 'user_type' => 'user', 'needs_password_change' => true]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Google account linked successfully', 'user_type' => 'user']);
                }
            } else {
                // User doesn't exist - only allow existing users to login
                echo json_encode(['success' => false, 'message' => 'Account not found. Please contact an administrator to create your account.']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Google OAuth failed: ' . $e->getMessage()]);
        error_log("Google OAuth error: " . $e->getMessage());
    }
    exit;
}

// Handle Google OAuth code exchange
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['google_oauth_code'])) {
    header('Content-Type: application/json');
    
    try {
        $code = $_POST['code'];
        
        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Missing authorization code']);
            exit;
        }
        
        // Exchange code for token
        $tokenData = exchangeGoogleCodeForToken($code);
        
        if (!$tokenData) {
            echo json_encode(['success' => false, 'message' => 'Failed to exchange code for token']);
            exit;
        }
        
        // Get user info from Google
        $userInfo = getGoogleUserInfo($tokenData['access_token']);
        
        if (!$userInfo) {
            echo json_encode(['success' => false, 'message' => 'Failed to get user information from Google']);
            exit;
        }
        
        // Process the user (same logic as above)
        $googleId = $userInfo['id'];
        $email = $userInfo['email'];
        $name = $userInfo['name'];
        $picture = $userInfo['picture'] ?? '';
        $givenName = $userInfo['given_name'] ?? '';
        $familyName = $userInfo['family_name'] ?? '';
        $emailVerified = $userInfo['verified_email'] ?? false;
        
        // Check if this is the super admin email
        if ($email === 'noreply.nutrisaur@gmail.com' && $emailVerified) {
            $_SESSION['admin_id'] = 'super_admin';
            $_SESSION['username'] = 'Super Admin';
            $_SESSION['email'] = 'noreply.nutrisaur@gmail.com';
            $_SESSION['is_admin'] = true;
            $_SESSION['is_super_admin'] = true;
            
            echo json_encode(['success' => true, 'message' => 'Super admin Google login successful', 'user_type' => 'super_admin']);
            exit;
        }
        
        if ($pdo === null) {
            echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
            exit;
        }
        
        // Check if user already exists by Google ID
        $stmt = $pdo->prepare("SELECT user_id, username, email, email_verified, is_active, password FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Check if user is archived/inactive
            if (isset($existingUser['is_active']) && $existingUser['is_active'] == 0) {
                echo json_encode(['success' => false, 'message' => 'Your account has been archived. Please contact an administrator.']);
                exit;
            }
            
            // Check if user still has default password
            $hasDefaultPassword = password_verify('mho123', $existingUser['password']);
            
            // User exists, log them in
            $_SESSION['user_id'] = $existingUser['user_id'];
            $_SESSION['username'] = $existingUser['username'];
            $_SESSION['email'] = $existingUser['email'];
            $_SESSION['is_admin'] = false;
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$existingUser['user_id']]);
            
            if ($hasDefaultPassword) {
                // Set session flag to require password setup
                $_SESSION['requires_password_setup'] = true;
                echo json_encode(['success' => true, 'message' => 'Google login successful', 'user_type' => 'user', 'needs_password_change' => true]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Google login successful', 'user_type' => 'user']);
            }
        } else {
            // Check if user exists by email
            $stmt = $pdo->prepare("SELECT user_id, username, email, is_active, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $userByEmail = $stmt->fetch();
            
            if ($userByEmail) {
                // Check if user is archived/inactive
                if (isset($userByEmail['is_active']) && $userByEmail['is_active'] == 0) {
                    echo json_encode(['success' => false, 'message' => 'Your account has been archived. Please contact an administrator.']);
                    exit;
                }
                
                // Check if user still has default password
                $hasDefaultPassword = password_verify('mho123', $userByEmail['password']);
                
                // Link Google account to existing user
                $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, google_name = ?, google_picture = ?, email_verified = 1 WHERE user_id = ?");
                $updateStmt->execute([$googleId, $name, $picture, $userByEmail['user_id']]);
                
                $_SESSION['user_id'] = $userByEmail['user_id'];
                $_SESSION['username'] = $userByEmail['username'];
                $_SESSION['email'] = $userByEmail['email'];
                $_SESSION['is_admin'] = false;
                
                if ($hasDefaultPassword) {
                    // Set session flag to require password setup
                    $_SESSION['requires_password_setup'] = true;
                    echo json_encode(['success' => true, 'message' => 'Google account linked successfully', 'user_type' => 'user', 'needs_password_change' => true]);
                } else {
                    echo json_encode(['success' => true, 'message' => 'Google account linked successfully', 'user_type' => 'user']);
                }
            } else {
                // User doesn't exist - only allow existing users to login
                echo json_encode(['success' => false, 'message' => 'Account not found. Please contact an administrator to create your account.']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Google OAuth failed: ' . $e->getMessage()]);
        error_log("Google OAuth code exchange error: " . $e->getMessage());
    }
    exit;
}

// Handle AJAX requests for better user experience
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax_action']) {
        case 'login':
            $usernameOrEmail = trim($_POST['username']);
            $password = $_POST['password'];
            
            if (empty($usernameOrEmail) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Please enter both username/email and password']);
                exit;
            }
            
            // Check for hardcoded super admin first
            if ($usernameOrEmail === 'noreply.nutrisaur@gmail.com' && $password === 'admin') {
                // Set super admin session
                $_SESSION['admin_id'] = 'super_admin';
                $_SESSION['username'] = 'Super Admin';
                $_SESSION['email'] = 'noreply.nutrisaur@gmail.com';
                $_SESSION['is_admin'] = true;
                $_SESSION['is_super_admin'] = true;
                
                echo json_encode(['success' => true, 'message' => 'Super admin login successful', 'user_type' => 'super_admin']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if user exists in users table and is active
                $stmt = $pdo->prepare("SELECT user_id, username, email, password, email_verified, is_active FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Check if user is archived/inactive
                    if (isset($user['is_active']) && $user['is_active'] == 0) {
                        echo json_encode(['success' => false, 'message' => 'Your account has been archived. Please contact an administrator.']);
                        exit;
                    }
                    
                    // Check email verification status
                    if ($user['email_verified'] == 1) {
                        // Set session variables only if email is verified
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['is_admin'] = false;
                        
                        // Update last login
                        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                        $updateStmt->execute([$user['user_id']]);
                        
                        echo json_encode(['success' => true, 'message' => 'Login successful', 'user_type' => 'user', 'data' => $user]);
                        exit;
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Please verify your email before logging in']);
                        exit;
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid username/email or password']);
                    exit;
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
                exit;
            }
            
            
        case 'check_session':
            $loggedIn = isset($_SESSION['user_id']);
            echo json_encode([
                'success' => true,
                'logged_in' => $loggedIn,
                'user_id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? null,
                'email' => $_SESSION['email'] ?? null,
                'is_admin' => false
            ]);
            exit;
            
        case 'verify_email':
            $email = trim($_POST['email']);
            $verificationCode = trim($_POST['verification_code']);
            
            if (empty($email) || empty($verificationCode)) {
                echo json_encode(['success' => false, 'message' => 'Please provide email and verification code']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if verification code is valid and not expired
                $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE email = ? AND verification_code = ? AND verification_code_expires > NOW() AND email_verified = 0");
                $stmt->execute([$email, $verificationCode]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Mark email as verified
                    $updateStmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = ?");
                    $updateStmt->execute([$user['user_id']]);
                    
                    // Log the user in after successful verification
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $email;
                    $_SESSION['is_admin'] = false;
                    
                    echo json_encode(['success' => true, 'message' => 'Email verified successfully! You are now logged in.', 'redirect' => '/dash']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired verification code']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
            }
            exit;
            
        case 'resend_verification':
            $email = trim($_POST['email']);
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Please provide email address']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if user exists and is not verified
                $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE email = ? AND email_verified = 0");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate new verification code
                    $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    // Update verification code
                    $updateStmt = $pdo->prepare("UPDATE users SET verification_code = ?, verification_code_expires = ? WHERE user_id = ?");
                    $updateStmt->execute([$verificationCode, $expiresAt, $user['user_id']]);
                    
                    // Send verification email
                    $emailSent = sendVerificationEmail($email, $user['username'], $verificationCode);
                    
                    if ($emailSent) {
                        echo json_encode(['success' => true, 'message' => 'Verification code sent successfully!']);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'Verification code updated. Code: ' . $verificationCode]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found or already verified']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to resend verification: ' . $e->getMessage()]);
            }
            exit;
            
        case 'forgot_password':
            $email = trim($_POST['email']);
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Please provide email address']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if user exists
                $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate reset code
                    $resetCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    
                    // Update reset code
                    $updateStmt = $pdo->prepare("UPDATE users SET password_reset_code = ?, password_reset_expires = ? WHERE user_id = ?");
                    $updateStmt->execute([$resetCode, $expiresAt, $user['user_id']]);
                    
                    // Send reset email
                    $emailSent = sendPasswordResetEmail($email, $user['username'], $resetCode);
                    
                    if ($emailSent) {
                        echo json_encode(['success' => true, 'message' => 'Password reset code sent to your email!']);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'Password reset code: ' . $resetCode]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'No account found with this email address']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to send reset code: ' . $e->getMessage()]);
            }
            exit;
            
        case 'verify_reset_code':
            $email = trim($_POST['email']);
            $resetCode = trim($_POST['reset_code']);
            
            if (empty($email) || empty($resetCode)) {
                echo json_encode(['success' => false, 'message' => 'Please provide email and reset code']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if reset code is valid and not expired
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND password_reset_code = ? AND password_reset_expires > NOW()");
                $stmt->execute([$email, $resetCode]);
                $user = $stmt->fetch();
                
                if ($user) {
                    echo json_encode(['success' => true, 'message' => 'Reset code verified successfully!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired reset code']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
            }
            exit;
            
        case 'update_password':
            $email = trim($_POST['email']);
            $resetCode = trim($_POST['reset_code']);
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($email) || empty($resetCode) || empty($newPassword) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                exit;
            }
            
            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                exit;
            }
            
            if (strlen($newPassword) > 20) {
                echo json_encode(['success' => false, 'message' => 'Password must be 20 characters or less']);
                exit;
            }
            
            // Check for at least one uppercase letter
            if (!preg_match('/[A-Z]/', $newPassword)) {
                echo json_encode(['success' => false, 'message' => 'Password must contain at least one uppercase letter']);
                exit;
            }
            
            // Check for at least one symbol
            if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $newPassword)) {
                echo json_encode(['success' => false, 'message' => 'Password must contain at least one symbol (!@#$%^&*()_+-=[]{}|;\':",./<>?)']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Verify reset code again
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND password_reset_code = ? AND password_reset_expires > NOW()");
                $stmt->execute([$email, $resetCode]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Hash new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update password and clear reset code
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ?, password_reset_code = NULL, password_reset_expires = NULL WHERE user_id = ?");
                    $updateStmt->execute([$hashedPassword, $user['user_id']]);
                    
                    echo json_encode(['success' => true, 'message' => 'Password updated successfully! You can now login with your new password.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired reset code']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Password update failed: ' . $e->getMessage()]);
            }
            exit;
            
        case 'setup_password':
            $resetToken = trim($_POST['reset_token']);
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($resetToken) || empty($newPassword) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                exit;
            }
            
            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                exit;
            }
            
            if (strlen($newPassword) > 20) {
                echo json_encode(['success' => false, 'message' => 'Password must be 20 characters or less']);
                exit;
            }
            
            // Check for at least one uppercase letter
            if (!preg_match('/[A-Z]/', $newPassword)) {
                echo json_encode(['success' => false, 'message' => 'Password must contain at least one uppercase letter']);
                exit;
            }
            
            // Check for at least one symbol
            if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $newPassword)) {
                echo json_encode(['success' => false, 'message' => 'Password must contain at least one symbol (!@#$%^&*()_+-=[]{}|;\':",./<>?)']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Verify reset token and get user info including personal_email and full_name
                $stmt = $pdo->prepare("SELECT user_id, username, email, personal_email, full_name FROM users WHERE password_reset_code = ? AND password_reset_expires > NOW()");
                $stmt->execute([$resetToken]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Hash new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update password and clear reset token
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ?, password_reset_code = NULL, password_reset_expires = NULL WHERE user_id = ?");
                    $updateStmt->execute([$hashedPassword, $user['user_id']]);
                    
                    // Log the user in after successful password setup
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['is_admin'] = true;
                    
                    // Check if personal info is missing
                    $needsPersonalInfo = empty($user['personal_email']) || empty($user['full_name']);
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Password set up successfully! You are now logged in.',
                        'needs_personal_info' => $needsPersonalInfo,
                        'redirect' => $needsPersonalInfo ? null : '/dash'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid or expired setup link']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Password setup failed: ' . $e->getMessage()]);
            }
            exit;
            
        case 'google_setup_password':
            // Debug: Log the request
            error_log("Google setup password request received");
            error_log("Session data: " . print_r($_SESSION, true));
            error_log("POST data: " . print_r($_POST, true));
            
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (empty($newPassword) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
                exit;
            }
            
            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                exit;
            }
            
            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
                exit;
            }
            
            if (strlen($newPassword) > 20) {
                echo json_encode(['success' => false, 'message' => 'Password must be 20 characters or less']);
                exit;
            }
            
            // Check for at least one uppercase letter
            if (!preg_match('/[A-Z]/', $newPassword)) {
                echo json_encode(['success' => false, 'message' => 'Password must contain at least one uppercase letter']);
                exit;
            }
            
            // Check for at least one symbol
            if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $newPassword)) {
                echo json_encode(['success' => false, 'message' => 'Password must contain at least one symbol (!@#$%^&*()_+-=[]{}|;\':",./<>?)']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if user is logged in
                error_log("Checking session - user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
                if (!isset($_SESSION['user_id'])) {
                    error_log("User not logged in for password setup");
                    echo json_encode(['success' => false, 'message' => 'You must be logged in to set up your password']);
                    exit;
                }
                
                // Get user info including personal_email and full_name
                $stmt = $pdo->prepare("SELECT user_id, username, email, personal_email, full_name FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Hash new password
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    
                    // Update password
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $result = $updateStmt->execute([$hashedPassword, $user['user_id']]);
                    
                    if ($result) {
                        // Clear the password setup requirement flag
                        unset($_SESSION['requires_password_setup']);
                        
                        // Check if personal info is missing
                        $needsPersonalInfo = empty($user['personal_email']) || empty($user['full_name']);
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Password set up successfully!',
                            'needs_personal_info' => $needsPersonalInfo,
                            'redirect' => $needsPersonalInfo ? null : '/dash'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Password setup failed: ' . $e->getMessage()]);
            }
            exit;
            
        case 'check_password_setup_required':
            // Check if user requires password setup
            error_log("Checking password setup requirement - Session ID: " . session_id());
            error_log("Session data: " . print_r($_SESSION, true));
            
            if (isset($_SESSION['requires_password_setup']) && $_SESSION['requires_password_setup'] === true) {
                echo json_encode(['requires_password_setup' => true]);
            } else {
                echo json_encode(['requires_password_setup' => false]);
            }
            exit;
            
        case 'debug_session':
            // Debug session data
            error_log("Debug session called - Session ID: " . session_id());
            error_log("Full session data: " . print_r($_SESSION, true));
            
            echo json_encode([
                'session_id' => session_id(),
                'session_data' => $_SESSION,
                'user_id_set' => isset($_SESSION['user_id']),
                'requires_password_setup' => isset($_SESSION['requires_password_setup']),
                'user_id_value' => $_SESSION['user_id'] ?? 'NOT SET'
            ]);
            exit;
            
        case 'test_basic':
            // Basic test without sessions
            echo json_encode([
                'success' => true,
                'message' => 'Basic PHP is working',
                'timestamp' => time()
            ]);
            exit;
            
        case 'test_session':
            // Simple session test
            try {
                $_SESSION['test_value'] = 'session_working_' . time();
                echo json_encode([
                    'success' => true,
                    'session_id' => session_id(),
                    'test_value' => $_SESSION['test_value'],
                    'session_data' => $_SESSION
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Session test failed: ' . $e->getMessage()
                ]);
            }
            exit;
            
        case 'save_personal_info':
            $personalEmail = trim($_POST['personal_email']);
            $fullName = trim($_POST['full_name']);
            
            if (empty($personalEmail) || empty($fullName)) {
                echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
                exit;
            }
            
            if (!filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if user is logged in
                if (!isset($_SESSION['user_id'])) {
                    echo json_encode(['success' => false, 'message' => 'You must be logged in to save personal information']);
                    exit;
                }
                
                // Update user's personal information
                $updateStmt = $pdo->prepare("UPDATE users SET personal_email = ?, full_name = ? WHERE user_id = ?");
                $result = $updateStmt->execute([$personalEmail, $fullName, $_SESSION['user_id']]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Personal information saved successfully!',
                        'redirect' => '/dash'
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save personal information']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Failed to save personal information: ' . $e->getMessage()]);
            }
            exit;
    }
}

/**
 * Exchange Google OAuth authorization code for access token
 */
function exchangeGoogleCodeForToken($code) {
    $clientId = '43537903747-ppt6bbcnfa60p0hchanl32equ9c3b0ao.apps.googleusercontent.com';
    $clientSecret = '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';
    $redirectUri = 'postmessage'; // Use postmessage for popup mode
    
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $data = [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'code' => $code,
        'grant_type' => 'authorization_code',
        'redirect_uri' => $redirectUri
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Google OAuth token exchange cURL error: " . $curlError);
        return false;
    }
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        if (isset($tokenData['access_token'])) {
            return $tokenData;
        }
    }
    
    error_log("Google OAuth token exchange failed. HTTP Code: $httpCode, Response: $response");
    return false;
}

/**
 * Get user information from Google using access token
 */
function getGoogleUserInfo($accessToken) {
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . urlencode($accessToken);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Google user info cURL error: " . $curlError);
        return false;
    }
    
    if ($httpCode === 200) {
        $userInfo = json_decode($response, true);
        if (isset($userInfo['id']) && isset($userInfo['email'])) {
            return $userInfo;
        }
    }
    
    error_log("Google user info failed. HTTP Code: $httpCode, Response: $response");
    return false;
}

/**
 * Send verification email using SendGrid API
 */
function sendVerificationEmail($email, $username, $verificationCode) {
    // SendGrid API configuration
    $apiKey = $_ENV['SENDGRID_API_KEY'] ?? 'YOUR_SENDGRID_API_KEY_HERE';
    $apiUrl = 'https://api.sendgrid.com/v3/mail/send';
    
    $emailData = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $email, 'name' => $username]
                ],
                'subject' => 'NUTRISAUR - Email Verification'
            ]
        ],
        'from' => [
            'email' => 'noreply.nutrisaur@gmail.com',
            'name' => 'NUTRISAUR'
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => "Hello " . htmlspecialchars($username) . ",\n\nThank you for registering with NUTRISAUR. To complete your registration, please use the verification code below:\n\nVerification Code: " . $verificationCode . "\n\nThis code will expire in 5 minutes.\n\nIf you did not create an account with NUTRISAUR, please ignore this email.\n\nBest regards,\nNUTRISAUR Team"
            ],
            [
                'type' => 'text/html',
                'value' => "
                <html>
                <head>
                    <title>NUTRISAUR Email Verification</title>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;'>
                    <div style='max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                        <div style='text-align: center; background-color: #2A3326; color: #A1B454; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px;'>
                            <h1 style='margin: 0; font-size: 24px;'>NUTRISAUR</h1>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p>Hello " . htmlspecialchars($username) . ",</p>
                            <p>Thank you for registering with NUTRISAUR. To complete your registration, please use the verification code below:</p>
                            <div style='background-color: #f8f9fa; border: 2px solid #2A3326; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;'>
                                <span style='font-size: 28px; font-weight: bold; color: #2A3326; letter-spacing: 4px;'>" . $verificationCode . "</span>
                            </div>
                            <p><strong>This code will expire in 5 minutes.</strong></p>
                            <p>If you did not create an account with NUTRISAUR, please ignore this email.</p>
                        </div>
                        <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                            <p>Best regards,<br>NUTRISAUR Team</p>
                        </div>
                    </div>
                </body>
                </html>
                "
            ]
        ]
    ];
    
    // Send email via SendGrid API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Enhanced error logging
    error_log("SendGrid API Response: HTTP $httpCode, Response: $response, Error: " . ($curlError ?: 'None'));
    
    if ($curlError) {
        error_log("SendGrid cURL error: " . $curlError);
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("Email sent successfully via SendGrid API");
        return true;
    }
    
    error_log("SendGrid API failed. HTTP Code: $httpCode, Response: $response");
    return false;
}

/**
 * Send password reset email using SendGrid API
 */
function sendPasswordResetEmail($email, $username, $resetCode) {
    // SendGrid API configuration
    $apiKey = $_ENV['SENDGRID_API_KEY'] ?? 'YOUR_SENDGRID_API_KEY_HERE';
    $apiUrl = 'https://api.sendgrid.com/v3/mail/send';
    
    $emailData = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $email, 'name' => $username]
                ],
                'subject' => 'NUTRISAUR - Password Reset Code'
            ]
        ],
        'from' => [
            'email' => 'noreply.nutrisaur@gmail.com',
            'name' => 'NUTRISAUR'
        ],
        'content' => [
            [
                'type' => 'text/plain',
                'value' => "Hello " . htmlspecialchars($username) . ",\n\nYou requested a password reset for your NUTRISAUR account. Please use the reset code below:\n\nReset Code: " . $resetCode . "\n\nThis code will expire in 15 minutes.\n\nIf you did not request this password reset, please ignore this email.\n\nBest regards,\nNUTRISAUR Team"
            ],
            [
                'type' => 'text/html',
                'value' => "
                <html>
                <head>
                    <title>NUTRISAUR Password Reset</title>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                </head>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4;'>
                    <div style='max-width: 600px; margin: 20px auto; background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                        <div style='text-align: center; background-color: #2A3326; color: #A1B454; padding: 20px; border-radius: 8px 8px 0 0; margin: -20px -20px 20px -20px;'>
                            <h1 style='margin: 0; font-size: 24px;'>NUTRISAUR</h1>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p>Hello " . htmlspecialchars($username) . ",</p>
                            <p>You requested a password reset for your NUTRISAUR account. Please use the reset code below:</p>
                            <div style='background-color: #f8f9fa; border: 2px solid #2A3326; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;'>
                                <span style='font-size: 28px; font-weight: bold; color: #2A3326; letter-spacing: 4px;'>" . $resetCode . "</span>
                            </div>
                            <p><strong>This code will expire in 15 minutes.</strong></p>
                            <p>If you did not request this password reset, please ignore this email.</p>
                        </div>
                        <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 14px;'>
                            <p>Best regards,<br>NUTRISAUR Team</p>
                        </div>
                    </div>
                </body>
                </html>
                "
            ]
        ]
    ];
    
    // Send email via SendGrid API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Enhanced error logging
    error_log("SendGrid Password Reset API Response: HTTP $httpCode, Response: $response, Error: " . ($curlError ?: 'None'));
    
    if ($curlError) {
        error_log("SendGrid password reset cURL error: " . $curlError);
        return false;
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("Password reset email sent successfully via SendGrid API");
        return true;
    }
    
    error_log("SendGrid password reset API failed. HTTP Code: $httpCode, Response: $response");
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUTRISAUR Login</title>
    
    <!-- Google OAuth Configuration -->
    <script src="google-oauth-config.js"></script>
    
    <!-- Immediate styling fix for username field -->
    <script>
        // Apply styling immediately before page renders
        (function() {
            const style = document.createElement('style');
            style.textContent = `
                #username {
                    background: rgba(255, 255, 255, 0.05) !important;
                    background-color: rgba(255, 255, 255, 0.05) !important;
                    color: #E8F0D6 !important;
                    border: 1px solid rgba(161, 180, 84, 0.3) !important;
                }
                #username:focus {
                    background: rgba(255, 255, 255, 0.08) !important;
                    background-color: rgba(255, 255, 255, 0.08) !important;
                    color: #E8F0D6 !important;
                    border: 1px solid rgba(161, 180, 84, 0.5) !important;
                }
                #username:hover {
                    background: rgba(255, 255, 255, 0.05) !important;
                    background-color: rgba(255, 255, 255, 0.05) !important;
                    color: #E8F0D6 !important;
                    border: 1px solid rgba(161, 180, 84, 0.3) !important;
                }
            `;
            document.head.appendChild(style);
        })();
    </script>
</head>
<style>
        /* Dark Theme - Aligned with dash.php */
        :root {
            --color-bg: #1A211A;
            --color-card: #2A3326;
            --color-highlight: #A1B454;
            --color-text: #E8F0D6;
            --color-accent1: #8CA86E;
            --color-accent2: #B5C88D;
            --color-accent3: #546048;
            --color-accent4: #C9D8AA;
            --color-danger: #CF8686;
            --color-warning: #E0C989;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
        }

        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            line-height: 1.6;
            letter-spacing: 0.2px;
        }

        /* Subtle background pattern */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
            z-index: -1;
            opacity: 0.06;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 90%;
            max-width: 1200px;
            background: rgba(42, 51, 38, 0.1);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            z-index: 1000;
            position: relative;
            border: 1px solid rgba(161, 180, 84, 0.1);
        }

        .content, .login-box {
            flex: 1;
            margin: 0 20px;
        }

        .content {
            max-width: 60%;
            color: var(--color-text);
        }

        .content h1 {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--color-highlight);
            font-weight: 600;
        }

        .content p {
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.9;
        }

        .login-box {
            background: var(--color-card);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 100%;
            text-align: center;
            margin: 0 20px;
            box-sizing: border-box;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
            border: 1px solid rgba(161, 180, 84, 0.1);
        }

        .login-box h2 {
            color: var(--color-highlight);
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 600;
        }

        .input-group {
            margin-bottom: 25px;
            text-align: left;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            position: relative;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--color-text);
            font-weight: 500;
            opacity: 0.9;
        }

        input,
        .login-box .input-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid rgba(161, 180, 84, 0.3) !important;
            border-radius: 12px !important;
            font-size: 16px !important;
            transition: all 0.3s ease !important;
            background: rgba(255, 255, 255, 0.05) !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: var(--color-text) !important;
            box-sizing: border-box !important;
            appearance: none !important;
            -webkit-appearance: none !important;
        }

        .login-box .input-group input:focus,
        input:focus {
            outline: none;
            border-color: var(--color-highlight) !important;
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.1) !important;
            background: rgba(255, 255, 255, 0.08) !important;
            background-color: rgba(255, 255, 255, 0.08) !important;
        }

        .login-box .input-group input::placeholder {
            color: rgba(232, 240, 214, 0.5) !important;
        }

        /* Ensure consistent dark styling for browser autofill */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active,
        textarea:-webkit-autofill,
        textarea:-webkit-autofill:hover,
        textarea:-webkit-autofill:focus,
        textarea:-webkit-autofill:active,
        select:-webkit-autofill,
        select:-webkit-autofill:hover,
        select:-webkit-autofill:focus,
        select:-webkit-autofill:active {
            -webkit-text-fill-color: #E8F0D6 !important;
            caret-color: #E8F0D6 !important;
            transition: background-color 9999s ease-in-out 0s !important;
            -webkit-box-shadow: 0 0 0px 1000px rgba(255, 255, 255, 0.05) inset !important;
            box-shadow: 0 0 0px 1000px rgba(255, 255, 255, 0.05) inset !important;
            border: 1px solid rgba(161, 180, 84, 0.3) !important;
            background: rgba(255, 255, 255, 0.05) !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: #E8F0D6 !important;
        }

        /* Additional autofill text visibility fix */
        input[readonly]:-webkit-autofill,
        input[readonly]:-webkit-autofill:hover,
        input[readonly]:-webkit-autofill:focus,
        input[readonly]:-webkit-autofill:active {
            -webkit-text-fill-color: #E8F0D6 !important;
            color: #E8F0D6 !important;
            caret-color: #E8F0D6 !important;
        }

        /* Force text visibility for all input states */
        input, input:focus, input:active, input:hover, input:visited {
            color: #E8F0D6 !important;
            -webkit-text-fill-color: #E8F0D6 !important;
        }

        /* Specific autofill text visibility with maximum specificity */
        .login-box input:-webkit-autofill,
        .login-box input:-webkit-autofill:hover,
        .login-box input:-webkit-autofill:focus,
        .login-box input:-webkit-autofill:active {
            -webkit-text-fill-color: #E8F0D6 !important;
            color: #E8F0D6 !important;
            caret-color: #E8F0D6 !important;
        }

        /* Firefox autofill text visibility */
        input:-moz-autofill,
        input:-moz-autofill:hover,
        input:-moz-autofill:focus,
        input:-moz-autofill:active {
            color: #E8F0D6 !important;
            background: rgba(255, 255, 255, 0.05) !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        /* Autofill detection animation */
        @keyframes onAutoFillStart {
            from { /**/ }
            to { /**/ }
        }

        input:-webkit-autofill {
            animation-name: onAutoFillStart;
            animation-duration: 0.001s;
        }

        /* Additional autofill prevention */
        input[autocomplete="off"] {
            -webkit-autocomplete: off !important;
            -moz-autocomplete: off !important;
        }

        /* Force dark styling on all input states */
        input, input:focus, input:active, input:hover {
            background: rgba(255, 255, 255, 0.05) !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: var(--color-text) !important;
            border: 1px solid rgba(161, 180, 84, 0.3) !important;
        }

        /* Specific targeting for username field with maximum specificity */
        .login-box #username,
        .login-box #username:focus,
        .login-box #username:hover,
        .login-box #username:active,
        .login-box #username:visited,
        .login-box #username:link {
            background: rgba(255, 255, 255, 0.05) !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: #E8F0D6 !important;
            border: 1px solid rgba(161, 180, 84, 0.3) !important;
        }

        /* Firefox and general fallback */
        .input-group input:-moz-ui-invalid {
            box-shadow: none;
        }

        /* Password reveal toggle styles */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: calc(50% + 15px);
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--color-text);
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
            opacity: 0.7;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
        }

        .password-toggle:hover {
            opacity: 1;
            color: var(--color-highlight);
            background: rgba(161, 180, 84, 0.1);
        }

        .password-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.3);
        }

        /* Adjust input padding for password fields to accommodate the toggle button */
        .input-group.password-field input {
            padding-right: 50px;
        }

        /* Ensure the input group has proper positioning for the absolute positioned toggle */
        .input-group.password-field {
            position: relative;
        }

        .auth-btn {
            width: 100%;
            padding: 15px;
            background: var(--color-highlight);
            color: var(--color-bg);
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .auth-btn:hover {
            background: var(--color-accent1);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
        }

        .google-btn {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--color-text);
            border: 1px solid rgba(161, 180, 84, 0.3);
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .google-btn img {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .google-btn:hover {
            background: rgba(161, 180, 84, 0.1);
            border-color: var(--color-highlight);
        }

        .toggle-link {
            display: block;
            margin-top: 20px;
            color: var(--color-highlight);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .toggle-link:hover {
            color: var(--color-accent1);
            text-decoration: underline;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 12px;
            display: none;
            font-weight: 500;
        }

        .error {
            background-color: rgba(207, 134, 134, 0.1);
            color: var(--color-danger);
            border: 1px solid rgba(207, 134, 134, 0.3);
        }

        .success {
            background-color: rgba(161, 180, 84, 0.1);
            color: var(--color-highlight);
            border: 1px solid rgba(161, 180, 84, 0.3);
        }

        .info {
            background-color: rgba(66, 133, 244, 0.1);
            color: #4285F4;
            border: 1px solid rgba(66, 133, 244, 0.3);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 95%;
                padding: 20px;
            }

            .content {
                max-width: 100%;
                margin-bottom: 30px;
                text-align: center;
            }

            .content h1 {
                font-size: 36px;
            }

            .content p {
                font-size: 16px;
            }

            .login-box {
                width: 100%;
                margin: 0;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }

            .content h1 {
                font-size: 28px;
            }

            .login-box {
                padding: 20px;
            }
        }

        /* Animated background particles */
        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--color-highlight);
            border-radius: 50%;
            opacity: 0.3;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(odd) {
            background: var(--color-accent1);
            animation-duration: 8s;
        }

        .particle:nth-child(3n) {
            background: var(--color-accent2);
            animation-duration: 10s;
        }

        .particle:nth-child(4n) {
            background: var(--color-accent3);
            animation-duration: 12s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px) translateX(0px);
                opacity: 0.3;
            }
            25% {
                transform: translateY(-20px) translateX(10px);
                opacity: 0.6;
            }
            50% {
                transform: translateY(-40px) translateX(-5px);
                opacity: 0.8;
            }
            75% {
                transform: translateY(-20px) translateX(-15px);
                opacity: 0.6;
            }
        }

        /* Animated gradient background */
        body {
            background: linear-gradient(-45deg, var(--color-bg), #2A3326, #1A211A, #2A3326);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        /* Container animations */
        .container {
            animation: slideInUp 1s ease-out;
            position: relative;
        }

        .container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--color-highlight), var(--color-accent1), var(--color-accent2), var(--color-highlight));
            background-size: 400% 400%;
            border-radius: 22px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
            opacity: 0.3;
        }

        @keyframes borderGlow {
            0%, 100% {
                background-position: 0% 50%;
                opacity: 0.3;
            }
            50% {
                background-position: 100% 50%;
                opacity: 0.5;
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Content animations */
        .content h1 {
            animation: fadeInLeft 1s ease-out 0.3s both;
            position: relative;
        }

        .content h1::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 3px;
            background: var(--color-highlight);
            animation: expandWidth 1s ease-out 1s forwards;
        }

        @keyframes expandWidth {
            to {
                width: 100%;
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .content p {
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Login box animations */
        .login-box {
            animation: slideInRight 1s ease-out 0.9s both;
            position: relative;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(140, 168, 110, 0.05));
            border-radius: 20px;
            z-index: -1;
            animation: subtleGlow 4s ease-in-out infinite;
        }

        @keyframes subtleGlow {
            0%, 100% {
                opacity: 0.3;
            }
            50% {
                opacity: 0.6;
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Input field animations */
        .input-group {
            animation: fadeInUp 0.6s ease-out both;
        }

        .input-group:nth-child(1) { animation-delay: 1.2s; }
        .input-group:nth-child(2) { animation-delay: 1.4s; }
        .input-group:nth-child(3) { animation-delay: 1.6s; }

        .input-group input {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .input-group input:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
        }

        /* Button animations */
        .auth-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .auth-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .auth-btn:hover::before {
            left: 100%;
        }

        .auth-btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(161, 180, 84, 0.4);
        }

        .google-btn {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .google-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(161, 180, 84, 0.2);
        }

        /* Toggle link animation */
        .toggle-link {
            position: relative;
            transition: all 0.3s ease;
        }

        .toggle-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--color-highlight);
            transition: width 0.3s ease;
        }

        .toggle-link:hover::after {
            width: 100%;
        }

        /* Message animations */
        .message {
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Pulse animation for important elements */
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .content h1:hover {
            animation: pulse 2s ease-in-out infinite;
        }

        /* Floating animation for the entire container */
        .container {
            animation: slideInUp 1s ease-out, float 6s ease-in-out infinite 1s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        /* Glow effect for the logo text */
        .content h1 {
            text-shadow: 0 0 20px rgba(161, 180, 84, 0.3);
        }

        /* Interactive cursor effects */
        .container {
            cursor: default;
        }

        .container:hover {
            transform: scale(1.01);
            transition: transform 0.3s ease;
        }
    </style>
<body>
    <!-- Animated background particles -->
    <div class="particles-container" id="particles-container"></div>

    <div class="container">
        <div class="content">
            <h1>NUTRISAUR</h1>
            <p>Welcome to NUTRISAUR: Advanced Malnutrition Screening & Nutrition Management System. Our platform provides comprehensive malnutrition screening tools, personalized nutrition recommendations, and AI-powered food suggestions to help healthcare workers and communities identify and address nutritional deficiencies. Join us in promoting better health outcomes through data-driven nutrition assessment.</p>
        </div>
        <div class="login-box">
            <h2 id="auth-title">Login</h2>
            <div id="message" class="message">
                <?php 
                    if (!empty($dbError)) {
                        echo '<div class="error">' . htmlspecialchars($dbError) . '</div>';
                    }
                    if (!empty($loginError)) {
                        echo '<div class="error">' . htmlspecialchars($loginError) . '</div>';
                    }
                    if (!empty($registrationError)) {
                        echo '<div class="error">' . htmlspecialchars($registrationError) . '</div>';
                    }
                    if (!empty($registrationSuccess)) {
                        echo '<div class="success">' . htmlspecialchars($registrationSuccess) . '</div>';
                    }
                ?>
            </div>
            <form id="auth-form" method="post" action="" autocomplete="off" style="display: none;">
                <div class="input-group">
                    <label for="username">Username/Email</label>
                    <input type="text" id="username" name="username_login" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group" id="email-group" style="display: none;">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email_register" autocomplete="off" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group password-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password_login" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                    <button type="button" class="password-toggle" id="toggle-password-login" data-target="password" aria-label="Toggle password visibility" title="Show/Hide password">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    </button>
                </div>
                <button type="submit" class="auth-btn" id="auth-btn" name="login">Login</button>
                <a href="#" class="toggle-link" id="forgot-password-link" style="margin-top: 0; margin-bottom: 2px; display: block; text-align: right;">Forgot Password?</a>
                <button type="button" class="google-btn" data-mode="login">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign in with Google
                </button>
            </form>
            
            
            <!-- Hidden verification form -->
            <form id="verification-form" method="post" action="" style="display: none;">
                <div class="input-group">
                    <label for="verification_email">Email</label>
                    <input type="email" id="verification_email" name="verification_email" readonly autocomplete="off" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" id="verification_code" name="verification_code" placeholder="Enter 4-digit code" maxlength="4" pattern="[0-9]{4}" required autocomplete="off" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <button type="submit" class="auth-btn" id="verify-btn">Verify Email</button>
                <button type="button" class="google-btn" id="resend-btn">Resend Code</button>
                <a href="#" class="toggle-link" id="back-to-login">Back to Login</a>
            </form>
            
            <!-- Hidden forgot password form -->
            <form id="forgot-password-form" method="post" action="" style="display: none;">
                <div class="input-group">
                    <label for="forgot_email">Email</label>
                    <input type="email" id="forgot_email" name="forgot_email" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <button type="submit" class="auth-btn" id="send-reset-btn">Send Reset Code</button>
                <a href="#" class="toggle-link" id="back-to-login-from-forgot">Back to Login</a>
            </form>
            
            <!-- Hidden reset code verification form -->
            <form id="reset-code-form" method="post" action="" style="display: none;">
                <div class="input-group">
                    <label for="reset_email">Email</label>
                    <input type="email" id="reset_email" name="reset_email" readonly autocomplete="off" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group">
                    <label for="reset_code">Reset Code</label>
                    <input type="text" id="reset_code" name="reset_code" placeholder="Enter 4-digit code" maxlength="4" pattern="[0-9]{4}" required autocomplete="off" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <button type="submit" class="auth-btn" id="verify-reset-btn">Verify Code</button>
                <button type="button" class="google-btn" id="resend-reset-btn">Resend Code</button>
                <a href="#" class="toggle-link" id="back-to-forgot">Back to Forgot Password</a>
            </form>
            
            <!-- Hidden new password form -->
            <form id="new-password-form" method="post" action="" style="display: none;">
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group password-field">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                    <button type="button" class="password-toggle" id="toggle-password-confirm" data-target="confirm_password" aria-label="Toggle password visibility" title="Show/Hide password">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    </button>
                </div>
                <button type="submit" class="auth-btn" id="update-password-btn">Update Password</button>
                <a href="#" class="toggle-link" id="back-to-login-from-password">Back to Login</a>
            </form>
            
            <!-- Hidden password setup form -->
            <form id="password-setup-form" method="post" action="" style="display: none;">
                <div class="input-group">
                    <label for="setup_new_password">New Password</label>
                    <input type="password" id="setup_new_password" name="new_password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group password-field">
                    <label for="setup_confirm_password">Confirm Password</label>
                    <input type="password" id="setup_confirm_password" name="confirm_password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                    <button type="button" class="password-toggle" id="toggle-password-setup-confirm" data-target="setup_confirm_password" aria-label="Toggle password visibility" title="Show/Hide password">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    </button>
                </div>
                <input type="hidden" id="reset_token" name="reset_token">
                <button type="submit" class="auth-btn" id="setup-password-btn">Set Up Password</button>
            </form>
            
            <!-- Google OAuth Password Setup Form -->
            <form id="google-password-setup-form" method="post" action="" style="display: none;">
                <div class="input-group">
                    <label for="google_setup_new_password">New Password</label>
                    <input type="password" id="google_setup_new_password" name="new_password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group password-field">
                    <label for="google_setup_confirm_password">Confirm Password</label>
                    <input type="password" id="google_setup_confirm_password" name="confirm_password" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                    <button type="button" class="password-toggle" id="toggle-google-password-setup-confirm" data-target="google_setup_confirm_password" aria-label="Toggle password visibility" title="Show/Hide password">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    </button>
                </div>
                <button type="submit" class="auth-btn" id="google-setup-password-btn">Set Up Password</button>
            </form>
            
            <!-- Personal Information Collection Form -->
            <form id="personal-info-form" method="post" action="" style="display: none;">
                <div class="input-group">
                    <label for="personal_email">Personal Email Address</label>
                    <input type="email" id="personal_email" name="personal_email" required autocomplete="email" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required autocomplete="name" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <button type="submit" class="auth-btn" id="submit-personal-info-btn">Complete Setup</button>
            </form>
        </div>
    </div>

    <script>
        // Create animated background particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles-container');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random position
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                
                // Random animation delay
                particle.style.animationDelay = Math.random() * 6 + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Force dark styling on inputs - using same approach as working email field
        function forceDarkStyling() {
            const inputIds = ['username', 'email', 'password', 'username_register', 'email_register', 'password_register', 'verification_email', 'verification_code'];
            
            inputIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    // Use same approach as working email field - no !important
                    el.style.background = 'rgba(255, 255, 255, 0.05)';
                    el.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
                    el.style.color = '#E8F0D6';
                    el.style.border = '1px solid rgba(161, 180, 84, 0.3)';
                    
                    // Add event listeners to maintain styling
                    el.addEventListener('focus', function() {
                        this.style.background = 'rgba(255, 255, 255, 0.08)';
                        this.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
                        this.style.color = '#E8F0D6';
                        this.style.border = '1px solid rgba(161, 180, 84, 0.5)';
                    });
                    
                    el.addEventListener('blur', function() {
                        this.style.background = 'rgba(255, 255, 255, 0.05)';
                        this.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
                        this.style.color = '#E8F0D6';
                        this.style.border = '1px solid rgba(161, 180, 84, 0.3)';
                    });
                    
                    // Prevent autofill styling
                    el.addEventListener('animationstart', function(e) {
                        if (e.animationName === 'onAutoFillStart') {
                            this.style.background = 'rgba(255, 255, 255, 0.05)';
                            this.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
                            this.style.color = '#E8F0D6';
                        }
                    });
                }
            });
        }

        // Apply styling immediately when script loads - simplified approach like email field
        (function() {
            // Apply styling as soon as possible - using same approach as working email field
            function applyInputStyling() {
                const inputIds = ['username', 'password', 'username_register', 'email_register', 'password_register', 'verification_email', 'verification_code'];
                
                inputIds.forEach(id => {
                    const field = document.getElementById(id);
                    if (field) {
                        // Use same approach as working email field - no !important
                        field.style.background = 'rgba(255, 255, 255, 0.05)';
                        field.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
                        field.style.color = '#E8F0D6';
                        field.style.border = '1px solid rgba(161, 180, 84, 0.3)';
                        
                        // Ensure autofill text is visible
                        field.addEventListener('input', function() {
                            this.style.color = '#E8F0D6';
                            this.style.setProperty('-webkit-text-fill-color', '#E8F0D6', 'important');
                        });
                        
                        // Handle focus to remove readonly and ensure text visibility
                        field.addEventListener('focus', function() {
                            this.removeAttribute('readonly');
                            this.style.color = '#E8F0D6';
                            this.style.setProperty('-webkit-text-fill-color', '#E8F0D6', 'important');
                            this.style.background = 'rgba(255, 255, 255, 0.08)';
                            this.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
                        });
                        
                        // Handle autofill detection and force text visibility
                        field.addEventListener('animationstart', function(e) {
                            if (e.animationName === 'onAutoFillStart') {
                                this.style.color = '#E8F0D6';
                                this.style.setProperty('-webkit-text-fill-color', '#E8F0D6', 'important');
                            }
                        });
                        
                        // Force text visibility periodically
                        setInterval(() => {
                            if (field.value && field.value.length > 0) {
                                field.style.color = '#E8F0D6';
                                field.style.setProperty('-webkit-text-fill-color', '#E8F0D6', 'important');
                            }
                        }, 100);
                    }
                });
            }
            
            // Try to apply immediately
            applyInputStyling();
            
            // Apply when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', applyInputStyling);
            } else {
                applyInputStyling();
            }
            
            // Apply a couple of times to ensure it sticks
            setTimeout(applyInputStyling, 50);
            setTimeout(applyInputStyling, 200);
        })();

        // Initialize particles when page loads
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            setupPasswordToggles();
            
            // Force styling immediately
            forceDarkStyling();
            
            // Force styling again after a short delay to ensure it sticks
            setTimeout(forceDarkStyling, 100);
            setTimeout(forceDarkStyling, 500);
            
            // Show the login form after styling is applied (only if no password setup token)
            setTimeout(() => {
                const urlParams = new URLSearchParams(window.location.search);
                const setupToken = urlParams.get('setup_password');
                
                if (!setupToken) {
                    const authForm = document.getElementById('auth-form');
                    if (authForm) {
                        authForm.style.display = 'block';
                    }
                }
            }, 600);
            
            // Apply styling periodically to prevent any override
            setInterval(forceDarkStyling, 2000);
        });

        // Authentication related code
        console.log('Setting up authentication elements...');
        const authForm = document.getElementById('auth-form');
        const authTitle = document.getElementById('auth-title');
        const authBtn = document.getElementById('auth-btn');
        const emailGroup = document.getElementById('email-group');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const messageDiv = document.getElementById('message');

        // Debug element existence
        console.log('Element check:', {
            authForm: !!authForm,
            authTitle: !!authTitle,
            authBtn: !!authBtn,
            emailGroup: !!emailGroup,
            usernameInput: !!usernameInput,
            emailInput: !!emailInput,
            passwordInput: !!passwordInput,
            messageDiv: !!messageDiv
        });

        let isLoginMode = true;


        // Form submission handler
        if (authForm) {
            authForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                console.log('Login form submitted');
                clearMessage();
                
                // Always in login mode for auth-form
                if (!usernameInput.value || !passwordInput.value) {
                    showMessage('Please enter both username/email and password', 'error');
                    return;
                }
                
                await login(usernameInput.value, passwordInput.value);
            });
        } else {
            console.error('authForm element not found!');
        }


        // Login function - using home.php AJAX
        async function login(username, password) {
            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);
                formData.append('ajax_action', 'login');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    
                    // Redirect to dashboard after a short delay
                    setTimeout(() => {
                        window.location.href = '/dash';
                    }, 1000);
                } else {
                    showMessage(data.message || 'Login failed. Please try again.', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Login error:', error);
            }
        }


        // Show message in message div
        function showMessage(message, type) {
            console.log('Showing message:', message, 'Type:', type);
            messageDiv.textContent = message;
            messageDiv.className = `message ${type}`;
            messageDiv.style.display = 'block';
        }

        // Clear message
        function clearMessage() {
            messageDiv.textContent = '';
            messageDiv.className = 'message';
            messageDiv.style.display = 'none';
        }

        // Email validation
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        // Check if user is already logged in - using home.php AJAX
        async function checkSession() {
            try {
                console.log('Checking session...');
                const formData = new FormData();
                formData.append('ajax_action', 'check_session');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                // Only redirect if user is actually logged in
                if (data.success && data.logged_in && (data.user_id || data.admin_id)) {
                    // User is already logged in, redirect to dashboard
                    window.location.href = '/dash';
                }
            } catch (error) {
                console.error('Session check error:', error);
            }
        }

        // Check session on page load
        checkSession();

        // Function to switch to login mode
        function switchToLoginMode() {
            isLoginMode = true;
            authTitle.textContent = 'Login';
            authBtn.textContent = 'Login';
            emailGroup.style.display = 'none';
            authForm.reset();
            // Show login form
            authForm.style.display = 'block';
        }

        // Show verification screen
        function showVerificationScreen(email, verificationCode = null) {
            authForm.style.display = 'none';
            document.getElementById('verification-form').style.display = 'block';
            document.getElementById('verification_email').value = email;
            authTitle.textContent = 'Email Verification';
            
            showMessage('Please check your email inbox or spam folder for the verification code.', 'success');
        }

        // Hide verification screen and show login
        function hideVerificationScreen() {
            document.getElementById('verification-form').style.display = 'none';
            authForm.style.display = 'block';
            authTitle.textContent = 'Login';
            clearMessage();
        }

        // Verify email function - using home.php AJAX
        async function verifyEmail(email, code) {
            try {
                const formData = new FormData();
                formData.append('email', email);
                formData.append('verification_code', code);
                formData.append('ajax_action', 'verify_email');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Email verified successfully! Welcome to Nutrisaur!', 'success');
                    setTimeout(() => {
                        window.location.href = '/dash';
                    }, 2000);
                } else {
                    showMessage(data.message || 'Verification failed. Please try again.', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Verification error:', error);
            }
        }

        // Resend verification code function - using home.php AJAX
        async function resendVerificationCode(email) {
            try {
                const formData = new FormData();
                formData.append('email', email);
                formData.append('ajax_action', 'resend_verification');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Verification code sent successfully! Please check your email.', 'success');
                } else {
                    showMessage(data.message || 'Failed to resend verification code.', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Resend error:', error);
            }
        }

        // Password visibility toggle functionality - robust approach
        function setupPasswordToggles() {
            // Setup for login password field
            const loginPassword = document.getElementById('password');
            const loginToggle = document.getElementById('toggle-password-login');
            const loginIconShow = loginToggle?.querySelector('.eye-icon');
            const loginIconHide = loginToggle?.querySelector('.eye-slash-icon');

            if (loginPassword && loginToggle) {
                // Store the current state to prevent auto-hide
                let isPasswordVisible = false;
                
                loginToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle the input type
                    isPasswordVisible = !isPasswordVisible;
                    loginPassword.type = isPasswordVisible ? 'text' : 'password';

                    // Update aria attributes
                    loginToggle.setAttribute('aria-pressed', isPasswordVisible ? 'true' : 'false');
                    loginToggle.setAttribute('aria-label', isPasswordVisible ? 'Hide password' : 'Show password');
                    loginToggle.title = isPasswordVisible ? 'Hide password' : 'Show password';

                    // Swap icons
                    if (isPasswordVisible) {
                        if (loginIconShow) loginIconShow.style.display = 'none';
                        if (loginIconHide) loginIconHide.style.display = 'inline';
                    } else {
                        if (loginIconShow) loginIconShow.style.display = 'inline';
                        if (loginIconHide) loginIconHide.style.display = 'none';
                    }

                    // Keep focus on the input after toggling for better UX
                    setTimeout(() => loginPassword.focus(), 10);
                });

                // Allow keyboard activation
                loginToggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        e.stopPropagation();
                        loginToggle.click();
                    }
                });

                // Prevent form reset from affecting password visibility
                const originalReset = loginPassword.form.reset;
                if (originalReset) {
                    loginPassword.form.reset = function() {
                        originalReset.call(this);
                        // Restore password visibility state after reset
                        setTimeout(() => {
                            if (isPasswordVisible) {
                                loginPassword.type = 'text';
                                if (loginIconShow) loginIconShow.style.display = 'none';
                                if (loginIconHide) loginIconHide.style.display = 'inline';
                            }
                        }, 10);
                    };
                }
            }

            
            // Setup for confirm password field
            const confirmPassword = document.getElementById('confirm_password');
            const confirmToggle = document.getElementById('toggle-password-confirm');
            const confirmIconShow = confirmToggle?.querySelector('.eye-icon');
            const confirmIconHide = confirmToggle?.querySelector('.eye-slash-icon');

            if (confirmPassword && confirmToggle) {
                // Store the current state to prevent auto-hide
                let isPasswordVisible = false;
                
                confirmToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle the input type
                    isPasswordVisible = !isPasswordVisible;
                    confirmPassword.type = isPasswordVisible ? 'text' : 'password';

                    // Update aria attributes
                    confirmToggle.setAttribute('aria-pressed', isPasswordVisible ? 'true' : 'false');
                    confirmToggle.setAttribute('aria-label', isPasswordVisible ? 'Hide password' : 'Show password');
                    confirmToggle.title = isPasswordVisible ? 'Hide password' : 'Show password';

                    // Swap icons
                    if (isPasswordVisible) {
                        if (confirmIconShow) confirmIconShow.style.display = 'none';
                        if (confirmIconHide) confirmIconHide.style.display = 'inline';
                    } else {
                        if (confirmIconShow) confirmIconShow.style.display = 'inline';
                        if (confirmIconHide) confirmIconHide.style.display = 'none';
                    }

                    // Keep focus on the input after toggling for better UX
                    setTimeout(() => confirmPassword.focus(), 10);
                });

                // Allow keyboard activation
                confirmToggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        e.stopPropagation();
                        confirmToggle.click();
                    }
                });

                // Prevent form reset from affecting password visibility
                const originalReset = confirmPassword.form.reset;
                if (originalReset) {
                    confirmPassword.form.reset = function() {
                        originalReset.call(this);
                        // Restore password visibility state after reset
                        setTimeout(() => {
                            if (isPasswordVisible) {
                                confirmPassword.type = 'text';
                                if (confirmIconShow) confirmIconShow.style.display = 'none';
                                if (confirmIconHide) confirmIconHide.style.display = 'inline';
                            }
                        }, 10);
                    };
                }
            }
        }

        // Setup verification form event listeners
        function setupVerificationForm() {
            const verificationForm = document.getElementById('verification-form');
            const verifyBtn = document.getElementById('verify-btn');
            const resendBtn = document.getElementById('resend-btn');
            const backToLoginBtn = document.getElementById('back-to-login');

            // Verification form submission
            verificationForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = document.getElementById('verification_email').value;
                const code = document.getElementById('verification_code').value;
                
                if (!code || code.length !== 4) {
                    showMessage('Please enter a valid 4-digit verification code.', 'error');
                    return;
                }
                
                await verifyEmail(email, code);
            });

            // Resend verification code
            resendBtn.addEventListener('click', async () => {
                const email = document.getElementById('verification_email').value;
                await resendVerificationCode(email);
            });

            // Back to login
            backToLoginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                hideVerificationScreen();
            });
        }

        // Initialize all event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Check for password setup token FIRST, before any other initialization
            checkPasswordSetupToken();
            
            // Check if user is forced to setup password (security check)
            checkForcedPasswordSetup();
            
            createParticles();
            setupPasswordToggles();
            setupVerificationForm();
            setupForgotPasswordForm();
            setupPasswordSetupForm();
            setupGooglePasswordSetupForm();
            setupPersonalInfoForm();
            
            // Only show login form if no password setup token
            const urlParams = new URLSearchParams(window.location.search);
            const setupToken = urlParams.get('setup_password');
            if (!setupToken) {
                // Show login form only if no password setup token
                const authForm = document.getElementById('auth-form');
                if (authForm) {
                    authForm.style.display = 'block';
                }
            }
        });
        
        // Setup forgot password functionality
        function setupForgotPasswordForm() {
            const forgotPasswordLink = document.getElementById('forgot-password-link');
            const forgotPasswordForm = document.getElementById('forgot-password-form');
            const resetCodeForm = document.getElementById('reset-code-form');
            const newPasswordForm = document.getElementById('new-password-form');
            const authForm = document.getElementById('auth-form');
            const verificationForm = document.getElementById('verification-form');
            const authTitle = document.getElementById('auth-title');
            
            // Forgot password link click
            if (forgotPasswordLink) {
                forgotPasswordLink.addEventListener('click', (e) => {
                    e.preventDefault();
                    hideAllForms();
                    forgotPasswordForm.style.display = 'block';
                    authTitle.textContent = 'Forgot Password';
                });
            }
            
            // Back to login from forgot password
            const backToLoginFromForgot = document.getElementById('back-to-login-from-forgot');
            if (backToLoginFromForgot) {
                backToLoginFromForgot.addEventListener('click', (e) => {
                    e.preventDefault();
                    hideAllForms();
                    authForm.style.display = 'block';
                    authTitle.textContent = 'Login';
                });
            }
            
            // Back to forgot password from reset code
            const backToForgot = document.getElementById('back-to-forgot');
            if (backToForgot) {
                backToForgot.addEventListener('click', (e) => {
                    e.preventDefault();
                    hideAllForms();
                    forgotPasswordForm.style.display = 'block';
                    authTitle.textContent = 'Forgot Password';
                });
            }
            
            // Back to login from new password
            const backToLoginFromPassword = document.getElementById('back-to-login-from-password');
            if (backToLoginFromPassword) {
                backToLoginFromPassword.addEventListener('click', (e) => {
                    e.preventDefault();
                    hideAllForms();
                    authForm.style.display = 'block';
                    authTitle.textContent = 'Login';
                });
            }
            
            // Forgot password form submission
            if (forgotPasswordForm) {
                forgotPasswordForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const email = document.getElementById('forgot_email').value;
                    
                    if (!email) {
                        showMessage('Please enter your email address', 'error');
                        return;
                    }
                    
                    if (!validateEmail(email)) {
                        showMessage('Please enter a valid email address', 'error');
                        return;
                    }
                    
                    await sendPasswordResetCode(email);
                });
            }
            
            // Reset code form submission
            if (resetCodeForm) {
                resetCodeForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const email = document.getElementById('reset_email').value;
                    const resetCode = document.getElementById('reset_code').value;
                    
                    if (!resetCode || resetCode.length !== 4) {
                        showMessage('Please enter a valid 4-digit reset code', 'error');
                        return;
                    }
                    
                    await verifyResetCode(email, resetCode);
                });
            }
            
            // New password form submission
            if (newPasswordForm) {
                newPasswordForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const email = document.getElementById('reset_email').value;
                    const resetCode = document.getElementById('reset_code').value;
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (!newPassword || !confirmPassword) {
                        showMessage('Please fill in all password fields', 'error');
                        return;
                    }
                    
                    if (newPassword !== confirmPassword) {
                        showMessage('Passwords do not match', 'error');
                        return;
                    }
                    
                    if (newPassword.length < 6) {
                        showMessage('Password must be at least 6 characters long', 'error');
                        return;
                    }
                    
                    await updatePassword(email, resetCode, newPassword, confirmPassword);
                });
            }
            
            // Resend reset code
            const resendResetBtn = document.getElementById('resend-reset-btn');
            if (resendResetBtn) {
                resendResetBtn.addEventListener('click', async () => {
                    const email = document.getElementById('reset_email').value;
                    await sendPasswordResetCode(email);
                });
            }
        }
        
        // Hide all forms
        function hideAllForms() {
            const forms = ['auth-form', 'verification-form', 'forgot-password-form', 'reset-code-form', 'new-password-form', 'password-setup-form', 'google-password-setup-form', 'personal-info-form'];
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) form.style.display = 'none';
            });
        }
        
        // Send password reset code
        async function sendPasswordResetCode(email) {
            try {
                showMessage('Sending reset code...', 'info');
                
                const formData = new FormData();
                formData.append('email', email);
                formData.append('ajax_action', 'forgot_password');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Reset code sent to your email!', 'success');
                    // Switch to reset code form
                    hideAllForms();
                    document.getElementById('reset-code-form').style.display = 'block';
                    document.getElementById('reset_email').value = email;
                    document.getElementById('auth-title').textContent = 'Enter Reset Code';
                } else {
                    showMessage(data.message || 'Failed to send reset code', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Send reset code error:', error);
            }
        }
        
        // Verify reset code
        async function verifyResetCode(email, resetCode) {
            try {
                showMessage('Verifying reset code...', 'info');
                
                const formData = new FormData();
                formData.append('email', email);
                formData.append('reset_code', resetCode);
                formData.append('ajax_action', 'verify_reset_code');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Reset code verified! Please enter your new password.', 'success');
                    // Switch to new password form
                    hideAllForms();
                    document.getElementById('new-password-form').style.display = 'block';
                    document.getElementById('auth-title').textContent = 'Set New Password';
                } else {
                    showMessage(data.message || 'Invalid or expired reset code', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Verify reset code error:', error);
            }
        }
        
        // Update password
        async function updatePassword(email, resetCode, newPassword, confirmPassword) {
            try {
                showMessage('Updating password...', 'info');
                
                const formData = new FormData();
                formData.append('email', email);
                formData.append('reset_code', resetCode);
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);
                formData.append('ajax_action', 'update_password');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Password updated successfully! You can now login with your new password.', 'success');
                    // Switch back to login form
                    setTimeout(() => {
                        hideAllForms();
                        document.getElementById('auth-form').style.display = 'block';
                        document.getElementById('auth-title').textContent = 'Login';
                        clearMessage();
                    }, 2000);
                } else {
                    showMessage(data.message || 'Failed to update password', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Update password error:', error);
            }
        }
        
        // Setup password setup form
        function setupPasswordSetupForm() {
            const passwordSetupForm = document.getElementById('password-setup-form');
            const setupPasswordBtn = document.getElementById('setup-password-btn');
            
            if (passwordSetupForm) {
                passwordSetupForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const resetToken = document.getElementById('reset_token').value;
                    const newPassword = document.getElementById('setup_new_password').value;
                    const confirmPassword = document.getElementById('setup_confirm_password').value;
                    
                    if (!newPassword || !confirmPassword) {
                        showMessage('Please fill in all password fields', 'error');
                        return;
                    }
                    
                    if (newPassword !== confirmPassword) {
                        showMessage('Passwords do not match', 'error');
                        return;
                    }
                    
                    if (newPassword.length < 6) {
                        showMessage('Password must be at least 6 characters long', 'error');
                        return;
                    }
                    
                    await setupPassword(resetToken, newPassword, confirmPassword);
                });
            }
        }
        
        // Setup password function
        async function setupPassword(resetToken, newPassword, confirmPassword) {
            try {
                showMessage('Setting up password...', 'info');
                
                const formData = new FormData();
                formData.append('reset_token', resetToken);
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);
                formData.append('ajax_action', 'setup_password');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Password set up successfully! You are now logged in.', 'success');
                    if (data.needs_personal_info) {
                        // Transition to personal info form
                        setTimeout(() => {
                            showPersonalInfoForm();
                        }, 1500);
                    } else if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    }
                } else {
                    showMessage(data.message || 'Failed to set up password', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Setup password error:', error);
            }
        }
        
        // Check for password setup token in URL
        function checkPasswordSetupToken() {
            const urlParams = new URLSearchParams(window.location.search);
            const setupToken = urlParams.get('setup_password');
            
            if (setupToken) {
                // Hide all forms first
                hideAllForms();
                
                // Show only password setup form
                document.getElementById('password-setup-form').style.display = 'block';
                document.getElementById('reset_token').value = setupToken;
                document.getElementById('auth-title').textContent = 'Set Up Your Password';
                showMessage('Please set up your password to complete your account setup.', 'info');
                
                // Ensure login form is completely hidden
                const authForm = document.getElementById('auth-form');
                if (authForm) {
                    authForm.style.display = 'none';
                }
            }
        }
        
        // Check if user is forced to setup password (security check)
        function checkForcedPasswordSetup() {
            // Check if server has flagged this user for password setup
            fetch('/home.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax_action=check_password_setup_required'
            })
            .then(response => response.json())
            .then(data => {
                if (data.requires_password_setup) {
                    // Force show password setup form
                    hideAllForms();
                    document.getElementById('google-password-setup-form').style.display = 'block';
                    document.getElementById('auth-title').textContent = 'Set Up Your Password';
                    showMessage('You must set up your password before accessing the system.', 'info');
                }
            })
            .catch(error => {
                console.error('Error checking password setup requirement:', error);
            });
        }
        
        // Show personal info form
        function showPersonalInfoForm() {
            hideAllForms();
            document.getElementById('personal-info-form').style.display = 'block';
            document.getElementById('auth-title').textContent = 'Complete Your Profile';
            showMessage('Please provide your personal information to complete your account setup.', 'info');
        }
        
        // Setup Google OAuth password setup form
        function setupGooglePasswordSetupForm() {
            const googlePasswordSetupForm = document.getElementById('google-password-setup-form');
            
            if (googlePasswordSetupForm) {
                googlePasswordSetupForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const newPassword = document.getElementById('google_setup_new_password').value;
                    const confirmPassword = document.getElementById('google_setup_confirm_password').value;
                    
                    if (!newPassword || !confirmPassword) {
                        showMessage('Please fill in all password fields', 'error');
                        return;
                    }
                    
                    if (newPassword !== confirmPassword) {
                        showMessage('Passwords do not match', 'error');
                        return;
                    }
                    
                    if (newPassword.length < 6) {
                        showMessage('Password must be at least 6 characters long', 'error');
                        return;
                    }
                    
                    await setupGooglePassword(newPassword, confirmPassword);
                });
            }
        }
        
        // Setup personal info form
        function setupPersonalInfoForm() {
            const personalInfoForm = document.getElementById('personal-info-form');
            
            if (personalInfoForm) {
                personalInfoForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const personalEmail = document.getElementById('personal_email').value;
                    const fullName = document.getElementById('full_name').value;
                    
                    if (!personalEmail || !fullName) {
                        showMessage('Please fill in all fields', 'error');
                        return;
                    }
                    
                    if (!isValidEmail(personalEmail)) {
                        showMessage('Please enter a valid email address', 'error');
                        return;
                    }
                    
                    await savePersonalInfo(personalEmail, fullName);
                });
            }
        }
        
        // Save personal info function
        async function savePersonalInfo(personalEmail, fullName) {
            try {
                showMessage('Saving personal information...', 'info');
                
                const formData = new FormData();
                formData.append('personal_email', personalEmail);
                formData.append('full_name', fullName);
                formData.append('ajax_action', 'save_personal_info');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Personal information saved successfully! Redirecting to dashboard...', 'success');
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    }
                } else {
                    showMessage(data.message || 'Failed to save personal information', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Save personal info error:', error);
            }
        }
        
        // Setup Google password function
        async function setupGooglePassword(newPassword, confirmPassword) {
            try {
                showMessage('Setting up password...', 'info');
                
                // Debug: Test basic PHP first
                try {
                    console.log('Testing basic PHP...');
                    const basicResponse = await fetch('/home.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'ajax_action=test_basic'
                    });
                    const basicData = await basicResponse.json();
                    console.log('Basic test:', basicData);
                    
                    // Now test session
                    console.log('Testing session...');
                    const testResponse = await fetch('/home.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'ajax_action=test_session'
                    });
                    const testData = await testResponse.json();
                    console.log('Session test:', testData);
                    
                    // Now check session debug
                    console.log('Testing debug session...');
                    const debugResponse = await fetch('/home.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'ajax_action=debug_session'
                    });
                    const debugData = await debugResponse.json();
                    console.log('Session debug:', debugData);
                } catch (error) {
                    console.error('Debug test failed:', error);
                    console.error('Error details:', error.message);
                    
                    // Try to get the raw response to see what we're actually getting
                    try {
                        const rawResponse = await fetch('/home.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'ajax_action=test_basic'
                        });
                        const rawText = await rawResponse.text();
                        console.error('Raw response:', rawText.substring(0, 500)); // First 500 chars
                    } catch (rawError) {
                        console.error('Could not get raw response:', rawError);
                    }
                }
                
                const formData = new FormData();
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);
                formData.append('ajax_action', 'google_setup_password');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Password set up successfully!', 'success');
                    if (data.needs_personal_info) {
                        // Transition to personal info form
                        setTimeout(() => {
                            showPersonalInfoForm();
                        }, 1500);
                    } else if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
                    }
                } else {
                    showMessage(data.message || 'Failed to set up password', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Setup Google password error:', error);
            }
        }
        
        // Email validation helper
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }


    </script>
</body>
</html>