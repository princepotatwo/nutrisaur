<?php
// Start the session
session_start();

// Check if user is already logged in
$isLoggedIn = isset($_SESSION['user_id']);
if ($isLoggedIn) {
    // Redirect to dashboard if already logged in
    header("Location: /dash");
    exit;
}

    // Database connection
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
            
            // Always in login mode for auth-form
            if (!usernameInput.value || !passwordInput.value) {
                showMessage('Please enter both username/email and password', 'error');
                return;
            }
            
            await login(usernameInput.value, passwordInput.value);
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

        // Register function
        async function register(username, email, password) {
            try {
                // Show a loading message
                showMessage('Processing registration...', 'info');
                
                const endpoint = 'api/register.php';
                
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
                                    window.location.href = '/dash';
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
                
                // Only redirect if user is actually logged in
                if (data.success && data.logged_in && (data.user_id || data.admin_id)) {
                    // User is already logged in, redirect to dashboard
                    window.location.href = 'dash.php';
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