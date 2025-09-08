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

// Use the centralized Database API with Railway configuration
require_once __DIR__ . "/api/DatabaseHelper.php";
require_once __DIR__ . "/api/DatabaseAPI.php";

$db = DatabaseHelper::getInstance();

// Check database availability
$dbError = null;
if (!$db->isAvailable()) {
    $dbError = "Database connection failed";
    error_log("Home page: Database connection not available");
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
        // Use the centralized authentication method through DatabaseAPI
        $dbAPI = DatabaseAPI::getInstance();
        $result = $dbAPI->authenticateUser($usernameOrEmail, $password);
        
        if ($result['success']) {
            // Use centralized session management
            $dbAPI->setUserSession($result['data'], $result['user_type'] === 'admin');
            
            // Redirect to dashboard
            header("Location: /dash");
            exit;
        } else {
            $loginError = $result['message'];
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
        // Use the centralized registration method
        $result = $db->registerUser($username, $email, $password);
        
        if ($result['success']) {
            // Session is now handled automatically by DatabaseAPI
            // Redirect to dashboard
            header("Location: /dash");
            exit;
        } else {
            $registrationError = $result['message'];
        }
    }
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
            
            $result = $db->authenticateUser($usernameOrEmail, $password);
            
            if ($result['success']) {
                // Use centralized session management
                $db->setUserSession($result['data'], $result['user_type'] === 'admin');
            }
            
            echo json_encode($result);
            exit;
            
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
            
            $result = $db->registerUser($username, $email, $password);
            
            // Session is now handled automatically by DatabaseAPI
            echo json_encode($result);
            exit;
            
        case 'check_session':
            $sessionData = $db->getCurrentUserSession();
            echo json_encode([
                'success' => true,
                'logged_in' => $db->isUserLoggedIn(),
                'user_id' => $sessionData['user_id'],
                'admin_id' => $sessionData['admin_id'],
                'username' => $sessionData['username'],
                'is_admin' => $sessionData['is_admin']
            ]);
            exit;
    }
}

// Database connection is managed automatically by DatabaseAPI singleton
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUTRISAUR Login</title>

   
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
                    <input type="email" id="verification_email" name="verification_email" readonly>
                </div>
                <div class="input-group">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" id="verification_code" name="verification_code" placeholder="Enter 4-digit code" maxlength="4" pattern="[0-9]{4}" required>
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

        // Initialize particles when page loads
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            setupPasswordToggles();
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

        // Login function - using direct API endpoint
        async function login(username, password) {
            try {
                const formData = new FormData();
                formData.append('username', username);
                formData.append('password', password);
                
                const response = await fetch('/api/login.php', {
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

        // Register function - using new verification system with fallback
        async function register(username, email, password) {
            try {
                // Show a loading message
                showMessage('Processing registration...', 'info');
                
                // Use Resend registration system
                console.log('Using Resend registration system...');
                const formData = {
                    username: username,
                    email: email,
                    password: password
                };
                
                let response = await fetch('/api/DatabaseAPI.php?action=register_resend', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                console.log('Resend registration response status:', response.status);
                
                let responseText = await response.text();
                console.log('Resend registration response text:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Resend registration failed');
                    showMessage('Registration service unavailable. Please try again later.', 'error');
                    return;
                }
                
                console.log('Registration parsed data:', data);
                console.log('requires_verification check:', data.requires_verification);
                console.log('data.data check:', data.data);
                
                if (data.success) {
                    if (data.requires_verification || (data.data && data.data.requires_verification)) {
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

        // Check if user is already logged in - using direct API endpoint
        async function checkSession() {
            try {
                console.log('Checking session...');
                const response = await fetch('/api/check_session.php', {
                    method: 'GET'
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse JSON:', parseError);
                    console.error('Response was:', responseText);
                    return;
                }
                
                console.log('Parsed data:', data);
                
                // Only redirect if user is actually logged in
                if (data.success && data.logged_in && (data.user_id || data.admin_id)) {
                    // User is already logged in, redirect to dashboard
                    window.location.href = '/dash';
                }
            } catch (error) {
                console.error('Session check error:', error);
            }
        }

        // Test API connectivity
        async function testAPI() {
            try {
                console.log('Testing API connectivity...');
                const response = await fetch('/api/health.php', {
                    method: 'GET'
                });
                
                console.log('API test response status:', response.status);
                const responseText = await response.text();
                console.log('API test response text:', responseText);
                
                try {
                    const data = JSON.parse(responseText);
                    console.log('API test successful:', data);
                } catch (parseError) {
                    console.error('API test failed to parse JSON:', parseError);
                }
            } catch (error) {
                console.error('API test error:', error);
            }
        }

        // Check session on page load
        checkSession();
        
        // Test API connectivity
        testAPI();

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

        // Verify email function - using Resend verification endpoint
        async function verifyEmail(email, code) {
            try {
                const formData = {
                    email: email,
                    verification_code: code
                };
                
                const response = await fetch('/api/DatabaseAPI.php?action=verify_resend', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const responseText = await response.text();
                console.log('Resend verification response:', responseText);
                
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse verification response:', parseError);
                    showMessage('Server returned invalid response. Please try again.', 'error');
                    return;
                }
                
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

        // Resend verification code function - using Resend API
        async function resendVerificationCode(email) {
            try {
                const formData = {
                    email: email,
                    username: 'User' // We'll get this from the database
                };
                
                const response = await fetch('/api/DatabaseAPI.php?action=resend_verification_resend', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
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