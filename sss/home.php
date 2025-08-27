<?php
// Start the session
session_start();

// Check if user is already logged in
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    // Redirect to dashboard if already logged in
    header("Location: dash.php");
    exit;
}

// Database connection - Use the same working approach as simple_db_test.php
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// If MYSQL_PUBLIC_URL is set (Railway sets this), parse it
if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        $mysql_user = $matches[1];
        $mysql_password = $matches[2];
        $mysql_host = $matches[3];
        $mysql_port = $matches[4];
        $mysql_database = $matches[5];
    }
}

// Create database connection
try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
} catch (PDOException $e) {
    // If database connection fails, show error but don't crash
    $conn = null;
    $dbError = "Database connection failed: " . $e->getMessage();
}

$loginError = "";
$registrationError = "";
$registrationSuccess = "";

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $usernameOrEmail = $_POST['username_login'];
    $password = $_POST['password_login'];
    
    if (empty($usernameOrEmail) || empty($password)) {
        $loginError = "Please enter both username/email and password";
    } else {
        // Check if input is email or username
        $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);
        
        // First check in users table
        if ($isEmail) {
            $stmt = $conn->prepare("SELECT user_id, username, email, password FROM users WHERE email = :email");
            $stmt->bindParam(':email', $usernameOrEmail);
        } else {
            $stmt = $conn->prepare("SELECT user_id, username, email, password FROM users WHERE username = :username");
            $stmt->bindParam(':username', $usernameOrEmail);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_regenerate_id();
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Check if user is also in admin table
                $adminStmt = $conn->prepare("SELECT admin_id, role FROM admin WHERE email = :email");
                $adminStmt->bindParam(':email', $user['email']);
                $adminStmt->execute();
                
                if ($adminStmt->rowCount() > 0) {
                    $adminData = $adminStmt->fetch(PDO::FETCH_ASSOC);
                    $_SESSION['is_admin'] = true;
                    $_SESSION['admin_id'] = $adminData['admin_id'];
                    $_SESSION['role'] = $adminData['role'];
                } else {
                    $_SESSION['is_admin'] = false;
                }
                
                // Update last login time
                $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id");
                $updateStmt->bindParam(':user_id', $user['user_id']);
                $updateStmt->execute();
                
                // Redirect to dashboard
                header("Location: dash.php");
                exit;
            } else {
                $loginError = "Invalid password";
            }
        } else {
            // If not found in users table, check admin table directly
            if ($isEmail) {
                $stmt = $conn->prepare("SELECT admin_id, username, email, password, role FROM admin WHERE email = :email");
                $stmt->bindParam(':email', $usernameOrEmail);
            } else {
                $stmt = $conn->prepare("SELECT admin_id, username, email, password, role FROM admin WHERE username = :username");
                $stmt->bindParam(':username', $usernameOrEmail);
            }
            
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $admin['password'])) {
                    // Password is correct, start a new session
                    session_regenerate_id();
                    
                    $_SESSION['admin_id'] = $admin['admin_id'];
                    $_SESSION['username'] = $admin['username'];
                    $_SESSION['email'] = $admin['email'];
                    $_SESSION['is_admin'] = true;
                    $_SESSION['role'] = $admin['role'];
                    
                    // Update last login time
                    $updateStmt = $conn->prepare("UPDATE admin SET last_login = CURRENT_TIMESTAMP WHERE admin_id = :admin_id");
                    $updateStmt->bindParam(':admin_id', $admin['admin_id']);
                    $updateStmt->execute();
                    
                    // Redirect to dashboard
                    header("Location: dash.php");
                    exit;
                } else {
                    $loginError = "Invalid password";
                }
            } else {
                $loginError = "User not found";
            }
        }
    }
}

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $username = $_POST['username_register'];
    $email = $_POST['email_register'];
    $password = $_POST['password_register'];
    
    if (empty($username) || empty($email) || empty($password)) {
        $registrationError = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $registrationError = "Please enter a valid email address";
    } elseif (strlen($password) < 6) {
        $registrationError = "Password must be at least 6 characters long";
    } else {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $registrationError = "Username or email already exists";
            } else {
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
                session_start();
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                // Redirect to dashboard
                header("Location: dash.php");
                exit;
            }
        } catch (Exception $e) {
            // Rollback the transaction if something failed
            $conn->rollBack();
            $registrationError = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUTRISAUR Login</title>

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

        .input-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid rgba(161, 180, 84, 0.3);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
            color: var(--color-text);
            box-sizing: border-box;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }

        .input-group input::placeholder {
            color: rgba(232, 240, 214, 0.5);
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
</head>
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
            <form id="auth-form" method="post" action="">
                <div class="input-group">
                    <label for="username">Username/Email</label>
                    <input type="text" id="username" name="username_login" required>
                </div>
                <div class="input-group" id="email-group" style="display: none;">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email_register">
                </div>
                <div class="input-group password-field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password_login" required>
                    <button type="button" class="password-toggle" id="toggle-password-login">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    </button>
                </div>
                <button type="submit" class="auth-btn" id="auth-btn" name="login">Login</button>
                <button type="button" class="google-btn">
                    <img src="google.png" alt="Google Logo">
                    Sign in with Google
                </button>
                <a href="#" class="toggle-link" id="toggle-link">No account? Create one!</a>
            </form>
            
            <!-- Hidden registration form - will be shown via JavaScript -->
            <form id="register-form" method="post" action="" style="display: none;">
                <div class="input-group">
                    <label for="username_register">Username</label>
                    <input type="text" id="username_register" name="username_register" required>
                </div>
                <div class="input-group">
                    <label for="email_register">Email</label>
                    <input type="email" id="email_register" name="email_register" required>
                </div>
                <div class="input-group">
                    <label for="password_register">Password</label>
                    <input type="password" id="password_register" name="password_register" required class="password-field">
                    <button type="button" class="password-toggle" id="toggle-password-register">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        <svg class="eye-slash-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="display: none;">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    </button>
                </div>
                <button type="submit" class="auth-btn" name="register">Create Account</button>
                <button type="button" class="google-btn">
                    <img src="google.png" alt="Google Logo">
                    Sign up with Google
                </button>
                <a href="#" class="toggle-link" id="toggle-link-register">Already have an account? Login</a>
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

        // Initialize particles when page loads
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            setupPasswordToggles();
        });

        // Authentication related code
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

        let isLoginMode = true;

        // Toggle between login and register mode
        toggleLink.addEventListener('click', (e) => {
            e.preventDefault();
            authForm.style.display = 'none';
            registerForm.style.display = 'block';
            authTitle.textContent = 'Register';
        });
        
        toggleLinkRegister.addEventListener('click', (e) => {
            e.preventDefault();
            registerForm.style.display = 'none';
            authForm.style.display = 'block';
            authTitle.textContent = 'Login';
        });

        // Form submission handler
        authForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearMessage();
            
            // Validate form
            if (isLoginMode) {
                // Login mode
                if (!usernameInput.value || !passwordInput.value) {
                    showMessage('Please enter both username/email and password', 'error');
                    return;
                }
                
                await login(usernameInput.value, passwordInput.value);
            } else {
                // Register mode
                if (!usernameInput.value || !emailInput.value || !passwordInput.value) {
                    showMessage('Please fill in all fields', 'error');
                    return;
                }
                
                if (!validateEmail(emailInput.value)) {
                    showMessage('Please enter a valid email address', 'error');
                    return;
                }
                
                if (passwordInput.value.length < 6) {
                    showMessage('Password must be at least 6 characters long', 'error');
                    return;
                }
                
                await register(usernameInput.value, emailInput.value, passwordInput.value);
            }
        });

        // Register form submission handler
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
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

        // Login function
        async function login(username, password) {
            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    
                    // Redirect to dashboard after a short delay
                    setTimeout(() => {
                        window.location.href = 'dash.php';
                    }, 1000);
                } else {
                    showMessage(data.message || 'Login failed. Please try again.', 'error');
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Login error:', error);
            }
        }

        // Register function
        async function register(username, email, password) {
            try {
                // Show a loading message
                showMessage('Processing registration...', 'info');
                
                // Set to false to use the regular endpoint
                const testMode = false;
                const endpoint = testMode ? 'api/register_debug.php' : 'api/register.php';
                
                if (testMode) {
                    // In test mode, use FormData for easier debugging
                    const formData = new FormData();
                    formData.append('username', username);
                    formData.append('email', email);
                    formData.append('password', password);
                    
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        body: formData
                    });
                    
                    // Open the response in a new window for debugging
                    const responseText = await response.text();
                    const debugWindow = window.open('', '_blank');
                    debugWindow.document.write(responseText);
                    debugWindow.document.close();
                    return;
                } else {
                    // In production mode, use JSON
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            username: username,
                            email: email,
                            password: password
                        })
                    });
                    
                    const data = await response.json();
                    
                                    if (data.success) {
                    showMessage('Registration successful! Logging you in automatically...', 'success');
                    
                    // Automatically log in the user after successful registration
                    setTimeout(async () => {
                        try {
                            const loginResponse = await fetch('api/login.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    username: username,
                                    password: password
                                })
                            });
                            
                            const loginData = await loginResponse.json();
                            
                            if (loginData.success) {
                                showMessage('Login successful! Redirecting to dashboard...', 'success');
                                // Redirect to dashboard after successful auto-login
                                setTimeout(() => {
                                    window.location.href = 'dash.php';
                                }, 1000);
                            } else {
                                // If auto-login fails, show error and switch to login mode
                                showMessage('Auto-login failed. Please login manually.', 'error');
                                switchToLoginMode();
                            }
                        } catch (error) {
                            showMessage('Auto-login failed. Please login manually.', 'error');
                            console.error('Auto-login error:', error);
                            switchToLoginMode();
                        }
                    }, 1500);
                } else {
                        showMessage(data.message || 'Registration failed. Please try again.', 'error');
                        // Stay on registration form
                        isLoginMode = false;
                        registerForm.style.display = 'block';
                        authForm.style.display = 'none';
                    }
                }
            } catch (error) {
                showMessage('An error occurred. Please try again later.', 'error');
                console.error('Registration error:', error);
            }
        }

        // Show message in message div
        function showMessage(message, type) {
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

        // Check if user is already logged in
        async function checkSession() {
            try {
                const response = await fetch('api/check_session.php');
                const data = await response.json();
                
                if (data.success) {
                    // User is already logged in, redirect to dashboard
                    window.location.href = 'dash.html';
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

        // Password visibility toggle functionality
        function setupPasswordToggles() {
            const toggleLogin = document.getElementById('toggle-password-login');
            const toggleRegister = document.getElementById('toggle-password-register');
            const passwordLogin = document.getElementById('password');
            const passwordRegister = document.getElementById('password_register');

            // Toggle login password visibility
            toggleLogin.addEventListener('click', function() {
                const type = passwordLogin.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordLogin.setAttribute('type', type);
                
                // Update icon
                const icon = this.querySelector('.eye-icon');
                const eyeSlashIcon = this.querySelector('.eye-slash-icon');
                icon.style.display = type === 'password' ? 'block' : 'none';
                eyeSlashIcon.style.display = type === 'password' ? 'none' : 'block';
                
                // Add subtle animation
                this.style.transform = 'translateY(-50%) scale(1.1)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-50%) scale(1)';
                }, 150);
            });

            // Toggle register password visibility
            toggleRegister.addEventListener('click', function() {
                const type = passwordRegister.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordRegister.setAttribute('type', type);
                
                // Update icon
                const icon = this.querySelector('.eye-icon');
                const eyeSlashIcon = this.querySelector('.eye-slash-icon');
                icon.style.display = type === 'password' ? 'block' : 'none';
                eyeSlashIcon.style.display = type === 'password' ? 'none' : 'block';
                
                // Add subtle animation
                this.style.transform = 'translateY(-50%) scale(1.1)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-50%) scale(1)';
                }, 150);
            });
        }


    </script>
</body>
</html>