<?php
// Start the session
session_start();

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
    
    if (empty($usernameOrEmail) || empty($password)) {
        $loginError = "Please enter both username/email and password";
    } else {
        if ($pdo === null) {
            $loginError = "Database connection unavailable. Please try again later.";
        } else {
            try {
                // Check if user exists in users table
                $stmt = $pdo->prepare("SELECT user_id, username, email, password, email_verified FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
                $user = $stmt->fetch();
            
                if ($user && password_verify($password, $user['password'])) {
                    // Set session variables regardless of email verification status
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
                    $loginError = "Invalid username/email or password";
                }
            } catch (Exception $e) {
                $loginError = "Login failed: " . $e->getMessage();
                error_log("Login error: " . $e->getMessage());
            }
        }
    }
}

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = trim($_POST['username_register']);
    $email = trim($_POST['email_register']);
    $password = $_POST['password_register'];
    
    if (empty($username) || empty($email) || empty($password)) {
        $registrationError = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registrationError = "Please enter a valid email address";
    } elseif (strlen($password) < 6) {
        $registrationError = "Password must be at least 6 characters long";
    } elseif (strlen($username) < 3) {
        $registrationError = "Username must be at least 3 characters long";
    } else {
        if ($pdo === null) {
            $registrationError = "Database connection unavailable. Please try again later.";
        } else {
            try {
                // Check if user already exists
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                $existingUser = $stmt->fetch();
            
                if ($existingUser) {
                    $registrationError = "User with this email or username already exists";
                } else {
                    // Generate verification code
                    $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                    
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, email_verified, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                    $result = $stmt->execute([$username, $email, $hashedPassword, $verificationCode, $expiresAt]);
                    
                    if ($result) {
                        $userId = $pdo->lastInsertId();
                        
                        // Send verification email
                        $emailSent = sendVerificationEmail($email, $username, $verificationCode);
                        
                        if ($emailSent) {
                            $registrationSuccess = "Registration successful! Please check your email for verification code.";
                        } else {
                            $registrationSuccess = "Registration successful! However, email delivery failed. Your verification code is: " . $verificationCode;
                        }
                    } else {
                        $registrationError = "Failed to create user account";
                    }
                }
            } catch (Exception $e) {
                $registrationError = "Registration failed: " . $e->getMessage();
                error_log("Registration error: " . $e->getMessage());
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
        
        if (empty($googleId) || empty($email) || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Missing required Google OAuth data']);
            exit;
        }
        
        if ($pdo === null) {
            echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
            exit;
        }
        
        // Check if user already exists by Google ID
        $stmt = $pdo->prepare("SELECT user_id, username, email, email_verified FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // User exists, log them in
            $_SESSION['user_id'] = $existingUser['user_id'];
            $_SESSION['username'] = $existingUser['username'];
            $_SESSION['email'] = $existingUser['email'];
            $_SESSION['is_admin'] = false;
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$existingUser['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Google login successful', 'user_type' => 'user']);
        } else {
            // Check if user exists by email
            $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $userByEmail = $stmt->fetch();
            
            if ($userByEmail) {
                // Link Google account to existing user
                $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, google_name = ?, google_picture = ?, email_verified = 1 WHERE user_id = ?");
                $updateStmt->execute([$googleId, $name, $picture, $userByEmail['user_id']]);
                
                $_SESSION['user_id'] = $userByEmail['user_id'];
                $_SESSION['username'] = $userByEmail['username'];
                $_SESSION['email'] = $userByEmail['email'];
                $_SESSION['is_admin'] = false;
                
                echo json_encode(['success' => true, 'message' => 'Google account linked successfully', 'user_type' => 'user']);
            } else {
                // Create new user with Google OAuth
                $username = $givenName ?: explode('@', $email)[0];
                $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
                
                // Ensure username is unique
                $originalUsername = $username;
                $counter = 1;
                while (true) {
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if (!$stmt->fetch()) break;
                    $username = $originalUsername . $counter;
                    $counter++;
                }
                
                // Generate a random password for Google OAuth users (they won't use it)
                $randomPassword = password_hash(uniqid('google_', true), PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, google_id, google_name, google_picture, google_given_name, google_family_name, email_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$username, $email, $randomPassword, $googleId, $name, $picture, $givenName, $familyName, $emailVerified ? 1 : 0]);
                
                if ($result) {
                    $userId = $pdo->lastInsertId();
                    
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['is_admin'] = false;
                    
                    echo json_encode(['success' => true, 'message' => 'Google registration successful', 'user_type' => 'user']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
                }
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
        
        if ($pdo === null) {
            echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
            exit;
        }
        
        // Check if user already exists by Google ID
        $stmt = $pdo->prepare("SELECT user_id, username, email, email_verified FROM users WHERE google_id = ?");
        $stmt->execute([$googleId]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // User exists, log them in
            $_SESSION['user_id'] = $existingUser['user_id'];
            $_SESSION['username'] = $existingUser['username'];
            $_SESSION['email'] = $existingUser['email'];
            $_SESSION['is_admin'] = false;
            
            // Update last login
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->execute([$existingUser['user_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Google login successful', 'user_type' => 'user']);
        } else {
            // Check if user exists by email
            $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $userByEmail = $stmt->fetch();
            
            if ($userByEmail) {
                // Link Google account to existing user
                $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, google_name = ?, google_picture = ?, email_verified = 1 WHERE user_id = ?");
                $updateStmt->execute([$googleId, $name, $picture, $userByEmail['user_id']]);
                
                $_SESSION['user_id'] = $userByEmail['user_id'];
                $_SESSION['username'] = $userByEmail['username'];
                $_SESSION['email'] = $userByEmail['email'];
                $_SESSION['is_admin'] = false;
                
                echo json_encode(['success' => true, 'message' => 'Google account linked successfully', 'user_type' => 'user']);
            } else {
                // Create new user with Google OAuth
                $username = $givenName ?: explode('@', $email)[0];
                $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);
                
                // Ensure username is unique
                $originalUsername = $username;
                $counter = 1;
                while (true) {
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if (!$stmt->fetch()) break;
                    $username = $originalUsername . $counter;
                    $counter++;
                }
                
                // Generate a random password for Google OAuth users (they won't use it)
                $randomPassword = password_hash(uniqid('google_', true), PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, google_id, google_name, google_picture, google_given_name, google_family_name, email_verified, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $result = $stmt->execute([$username, $email, $randomPassword, $googleId, $name, $picture, $givenName, $familyName, $emailVerified ? 1 : 0]);
                
                if ($result) {
                    $userId = $pdo->lastInsertId();
                    
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $username;
                    $_SESSION['email'] = $email;
                    $_SESSION['is_admin'] = false;
                    
                    echo json_encode(['success' => true, 'message' => 'Google registration successful', 'user_type' => 'user']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
                }
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
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if user exists in users table
                $stmt = $pdo->prepare("SELECT user_id, username, email, password, email_verified FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // Set session variables
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
                    echo json_encode(['success' => false, 'message' => 'Invalid username/email or password']);
                    exit;
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Login failed: ' . $e->getMessage()]);
                exit;
            }
            
        case 'register':
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = $_POST['password'];
            
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
            
            if (strlen($username) < 3) {
                echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters long']);
                exit;
            }
            
            if ($pdo === null) {
                echo json_encode(['success' => false, 'message' => 'Database connection unavailable. Please try again later.']);
                exit;
            }
            
            try {
                // Check if user already exists
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                $existingUser = $stmt->fetch();
                
                if ($existingUser) {
                    echo json_encode(['success' => false, 'message' => 'User with this email or username already exists']);
                    exit;
                }
                
                // Generate verification code
                $verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
                
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, email_verified, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                $result = $stmt->execute([$username, $email, $hashedPassword, $verificationCode, $expiresAt]);
                
                if ($result) {
                    $userId = $pdo->lastInsertId();
                    
                    // Send verification email
                    $emailSent = sendVerificationEmail($email, $username, $verificationCode);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => $emailSent ? 
                            'Registration successful! Please check your email for verification code.' : 
                            'Registration successful! However, email delivery failed. Your verification code is: ' . $verificationCode,
                        'requires_verification' => true,
                        'data' => [
                            'user_id' => $userId,
                            'username' => $username,
                            'email' => $email,
                            'email_sent' => $emailSent,
                            'verification_code' => $verificationCode
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
            }
            exit;
            
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
    }
}

/**
 * Exchange Google OAuth authorization code for access token
 */
function exchangeGoogleCodeForToken($code) {
    $clientId = '43537903747-ppt6bbcnfa60p0hchanl32equ9c3b0ao.apps.googleusercontent.com';
    $clientSecret = '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';
    $redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/home.php';
    
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
                'type' => 'text/html',
                'value' => "
                <html>
                <head>
                    <title>Email Verification</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: #2A3326; color: #A1B454; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                        .verification-code { 
                            background: #2A3326; 
                            color: #A1B454; 
                            font-size: 32px; 
                            font-weight: bold; 
                            text-align: center; 
                            padding: 20px; 
                            border-radius: 8px; 
                            margin: 20px 0;
                            letter-spacing: 4px;
                        }
                        .footer { text-align: center; margin-top: 30px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Welcome to NUTRISAUR!</h1>
                        </div>
                        <div class='content'>
                            <p>Hello " . htmlspecialchars($username) . ",</p>
                            <p>Thank you for registering with NUTRISAUR. To complete your registration, please use the verification code below:</p>
                            <div class='verification-code'>" . $verificationCode . "</div>
                            <p><strong>This code will expire in 5 minutes.</strong></p>
                            <p>If you did not create an account with NUTRISAUR, please ignore this email.</p>
                            <div class='footer'>
                                <p>Best regards,<br>NUTRISAUR Team</p>
                            </div>
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
                <button type="button" class="google-btn" data-mode="login">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign in with Google
                </button>
                <a href="#" class="toggle-link" id="toggle-link">No account? Create one!</a>
            </form>
            
            <!-- Hidden registration form - will be shown via JavaScript -->
            <form id="register-form" method="post" action="" autocomplete="off" style="display: none;">
                <div class="input-group">
                    <label for="username_register">Username</label>
                    <input type="text" id="username_register" name="username_register" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group">
                    <label for="email_register">Email</label>
                    <input type="email" id="email_register" name="email_register" required autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                </div>
                <div class="input-group password-field">
                    <label for="password_register">Password</label>
                    <input type="password" id="password_register" name="password_register" required class="password-field" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')" style="background: rgba(255, 255, 255, 0.05); background-color: rgba(255, 255, 255, 0.05); color: #E8F0D6; border: 1px solid rgba(161, 180, 84, 0.3);">
                    <button type="button" class="password-toggle" id="toggle-password-register" data-target="password_register" aria-label="Toggle password visibility" title="Show/Hide password">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    </button>
                </div>
                <button type="submit" class="auth-btn" name="register">Create Account</button>
                <button type="button" class="google-btn" data-mode="register">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign up with Google
                </button>
                <a href="#" class="toggle-link" id="toggle-link-register">Already have an account? Login</a>
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
            
            // Show the login form after styling is applied
            setTimeout(() => {
                const authForm = document.getElementById('auth-form');
                if (authForm) {
                    authForm.style.display = 'block';
                }
            }, 600);
            
            // Apply styling periodically to prevent any override
            setInterval(forceDarkStyling, 2000);
        });

        // Authentication related code
        console.log('Setting up authentication elements...');
        const authForm = document.getElementById('auth-form');
        const registerForm = document.getElementById('register-form');
        const authTitle = document.getElementById('auth-title');
        const authBtn = document.getElementById('auth-btn');
        const toggleLink = document.getElementById('toggle-link');
        const toggleLinkRegister = document.getElementById('toggle-link-register');
        const emailGroup = document.getElementById('email-group');
        const usernameInput = document.getElementById('username');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const messageDiv = document.getElementById('message');

        // Debug element existence
        console.log('Element check:', {
            authForm: !!authForm,
            registerForm: !!registerForm,
            authTitle: !!authTitle,
            authBtn: !!authBtn,
            toggleLink: !!toggleLink,
            toggleLinkRegister: !!toggleLinkRegister,
            emailGroup: !!emailGroup,
            usernameInput: !!usernameInput,
            emailInput: !!emailInput,
            passwordInput: !!passwordInput,
            messageDiv: !!messageDiv
        });

        let isLoginMode = true;

        // Toggle between login and register mode
        if (toggleLink) {
            toggleLink.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Switching to register mode');
                authForm.style.display = 'none';
                registerForm.style.display = 'block';
                authTitle.textContent = 'Register';
            });
        } else {
            console.error('toggleLink element not found!');
        }
        
        if (toggleLinkRegister) {
            toggleLinkRegister.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('Switching to login mode');
                registerForm.style.display = 'none';
                authForm.style.display = 'block';
                authTitle.textContent = 'Login';
            });
        } else {
            console.error('toggleLinkRegister element not found!');
        }

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

        // Register form submission handler
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                console.log('Register form submitted');
                clearMessage();

                // Validate form
                const username = document.getElementById('username_register').value;
                const email = document.getElementById('email_register').value;
                const password = document.getElementById('password_register').value;

                if (!username || !email || !password) {
                    showMessage('Please fill in all fields', 'error');
                    return;
                }

                if (!validateEmail(email)) {
                    showMessage('Please enter a valid email address', 'error');
                    return;
                }

                if (password.length < 6) {
                    showMessage('Password must be at least 6 characters long', 'error');
                    return;
                }


                await register(username, email, password);
            });
        } else {
            console.error('registerForm element not found!');
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

        // Register function - using home.php AJAX
        async function register(username, email, password) {
            try {
                // Show a loading message
                showMessage('Processing registration...', 'info');
                
                const formData = new FormData();
                formData.append('username', username);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('ajax_action', 'register');
                
                const response = await fetch('/home.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (data.requires_verification) {
                        // Show verification screen with code if available
                        const verificationCode = data.data?.verification_code || null;
                        const email = data.data?.email || '';
                        showVerificationScreen(email, verificationCode);
                    } else {
                        showMessage('Registration successful! Redirecting to dashboard...', 'success');
                        // Redirect to dashboard after successful registration
                        setTimeout(() => {
                            window.location.href = '/dash';
                        }, 1000);
                    }
                } else {
                    showMessage(data.message || 'Registration failed. Please try again.', 'error');
                    // Stay on registration form
                    isLoginMode = false;
                    registerForm.style.display = 'block';
                    authForm.style.display = 'none';
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Registration error:', error);
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
            toggleLink.textContent = 'No account? Create one!';
            authForm.reset();
            // Hide registration form and show login form
            registerForm.style.display = 'none';
            authForm.style.display = 'block';
        }

        // Show verification screen
        function showVerificationScreen(email, verificationCode = null) {
            authForm.style.display = 'none';
            registerForm.style.display = 'none';
            document.getElementById('verification-form').style.display = 'block';
            document.getElementById('verification_email').value = email;
            authTitle.textContent = 'Email Verification';
            
            if (verificationCode) {
                showMessage(`Registration successful! Your verification code is: ${verificationCode}`, 'success');
            } else {
                showMessage('Please check your email for the verification code.', 'info');
            }
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

            // Setup for register password field
            const registerPassword = document.getElementById('password_register');
            const registerToggle = document.getElementById('toggle-password-register');
            const registerIconShow = registerToggle?.querySelector('.eye-icon');
            const registerIconHide = registerToggle?.querySelector('.eye-slash-icon');

            if (registerPassword && registerToggle) {
                // Store the current state to prevent auto-hide
                let isPasswordVisible = false;
                
                registerToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Toggle the input type
                    isPasswordVisible = !isPasswordVisible;
                    registerPassword.type = isPasswordVisible ? 'text' : 'password';

                    // Update aria attributes
                    registerToggle.setAttribute('aria-pressed', isPasswordVisible ? 'true' : 'false');
                    registerToggle.setAttribute('aria-label', isPasswordVisible ? 'Hide password' : 'Show password');
                    registerToggle.title = isPasswordVisible ? 'Hide password' : 'Show password';

                    // Swap icons
                    if (isPasswordVisible) {
                        if (registerIconShow) registerIconShow.style.display = 'none';
                        if (registerIconHide) registerIconHide.style.display = 'inline';
                    } else {
                        if (registerIconShow) registerIconShow.style.display = 'inline';
                        if (registerIconHide) registerIconHide.style.display = 'none';
                    }

                    // Keep focus on the input after toggling for better UX
                    setTimeout(() => registerPassword.focus(), 10);
                });

                // Allow keyboard activation
                registerToggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        e.stopPropagation();
                        registerToggle.click();
                    }
                });

                // Prevent form reset from affecting password visibility
                const originalReset = registerPassword.form.reset;
                if (originalReset) {
                    registerPassword.form.reset = function() {
                        originalReset.call(this);
                        // Restore password visibility state after reset
                        setTimeout(() => {
                            if (isPasswordVisible) {
                                registerPassword.type = 'text';
                                if (registerIconShow) registerIconShow.style.display = 'none';
                                if (registerIconHide) registerIconHide.style.display = 'inline';
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
            createParticles();
            setupPasswordToggles();
            setupVerificationForm();
        });


    </script>
</body>
</html>