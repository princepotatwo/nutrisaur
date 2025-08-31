<?php
header('Content-Type: application/json');
session_start();

// Use direct database connection like the working API
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
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }
    
    $usernameOrEmail = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($usernameOrEmail) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both username/email and password']);
        exit;
    }
    
    // Check if input is email or username
    $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);
    
    try {
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
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful!',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'is_admin' => $_SESSION['is_admin']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password']);
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
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful!',
                        'data' => [
                            'admin_id' => $admin['admin_id'],
                            'username' => $admin['username'],
                            'email' => $admin['email'],
                            'is_admin' => true,
                            'role' => $admin['role']
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid password']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        }
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 