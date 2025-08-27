<?php
/**
 * Login API
 * Handles user authentication
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Try POST data as fallback
    $input = $_POST;
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

// Database connection - Use the same working approach
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

try {
    // Create database connection
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdo = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    // Check if input is email or username
    $isEmail = filter_var($username, FILTER_VALIDATE_EMAIL);
    
    // First check in users table
    if ($isEmail) {
        $stmt = $pdo->prepare("SELECT user_id, username, email, password FROM users WHERE email = :email");
        $stmt->bindParam(':email', $username);
    } else {
        $stmt = $pdo->prepare("SELECT user_id, username, email, password FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
    }
    
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        if (password_verify($password, $user['password'])) {
            // Password is correct, start a new session
            session_regenerate_id();
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = false;
            
            // Check if user is also in admin table
            $adminStmt = $pdo->prepare("SELECT admin_id, role FROM admin WHERE email = :email");
            $adminStmt->bindParam(':email', $user['email']);
            $adminStmt->execute();
            
            if ($adminStmt->rowCount() > 0) {
                $adminData = $adminStmt->fetch();
                $_SESSION['is_admin'] = true;
                $_SESSION['admin_id'] = $adminData['admin_id'];
                $_SESSION['role'] = $adminData['role'];
            }
            
            // Update last login time
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = :user_id");
            $updateStmt->bindParam(':user_id', $user['user_id']);
            $updateStmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'is_admin' => $_SESSION['is_admin']
                ]
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit;
        }
    } else {
        // If not found in users table, check admin table directly
        if ($isEmail) {
            $stmt = $pdo->prepare("SELECT admin_id, username, email, password, role FROM admin WHERE email = :email");
            $stmt->bindParam(':email', $username);
        } else {
            $stmt = $pdo->prepare("SELECT admin_id, username, email, password, role FROM admin WHERE username = :username");
            $stmt->bindParam(':username', $username);
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $admin = $stmt->fetch();
            
            if (password_verify($password, $admin['password'])) {
                // Password is correct, start a new session
                session_regenerate_id();
                
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['is_admin'] = true;
                $_SESSION['role'] = $admin['role'];
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Admin login successful',
                    'user' => [
                        'admin_id' => $admin['admin_id'],
                        'username' => $admin['username'],
                        'email' => $admin['email'],
                        'is_admin' => true,
                        'role' => $admin['role']
                    ]
                ]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during login']);
    exit;
}
?>
