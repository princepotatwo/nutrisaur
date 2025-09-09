<?php
// Test script for verification code functionality
session_start();

// Test database connection
echo "<h2>Testing NUTRISAUR Verification System</h2>";

// Database connection with environment variables
$dbError = null;
$pdo = null;

try {
    // Try to get database config from environment variables first, then fallback to defaults
    $host = $_ENV['DB_HOST'] ?? $_ENV['MYSQL_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? $_ENV['MYSQL_DATABASE'] ?? 'nutrisaur_db';
    $username = $_ENV['DB_USER'] ?? $_ENV['MYSQL_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? $_ENV['MYSQL_PASSWORD'] ?? '';
    $port = $_ENV['DB_PORT'] ?? $_ENV['MYSQL_PORT'] ?? '3306';
    
    // Try different connection methods
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    // First try with port
    try {
        $pdo = new PDO($dsn, $username, $password);
    } catch (PDOException $e) {
        // If that fails, try without port (for socket connections)
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
    }
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dbError = "Database connection failed: " . $e->getMessage();
    error_log("Test page: Database connection failed - " . $e->getMessage());
    $pdo = null;
}

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
if ($pdo !== null) {
    echo "✅ Database connection successful<br>";
    echo "Host: $host<br>";
    echo "Database: $dbname<br>";
    echo "User: $username<br>";
} else {
    echo "❌ Database connection failed: " . ($dbError ?? 'Unknown error') . "<br>";
    echo "<p>This means the verification system won't work until database connection is fixed.</p>";
}

// Test 2: Email Function Test
echo "<h3>2. Email Function Test</h3>";
function testSendVerificationEmail($email, $username, $verificationCode) {
    // Simple email sending using PHP's mail() function
    $subject = "NUTRISAUR - Email Verification Test";
    $message = "
    <html>
    <head>
        <title>Email Verification Test</title>
    </head>
    <body>
        <h2>Welcome to NUTRISAUR!</h2>
        <p>Hello " . htmlspecialchars($username) . ",</p>
        <p>This is a test email for verification code:</p>
        <h3 style='color: #A1B454; font-size: 24px; text-align: center; padding: 10px; background: #2A3326; border-radius: 8px;'>" . $verificationCode . "</h3>
        <p>This code will expire in 5 minutes.</p>
        <br>
        <p>Best regards,<br>NUTRISAUR Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: NUTRISAUR <noreply@nutrisaur.com>" . "\r\n";
    
    // Try to send email
    try {
        $result = mail($email, $subject, $message, $headers);
        return $result;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

$testEmail = "test@example.com";
$testUsername = "TestUser";
$testCode = "1234";

echo "Testing email sending to: $testEmail<br>";
echo "Test verification code: $testCode<br>";

$emailSent = testSendVerificationEmail($testEmail, $testUsername, $testCode);
if ($emailSent) {
    echo "✅ Email function executed successfully<br>";
    echo "<small>Note: Email may not actually be delivered depending on server configuration</small><br>";
} else {
    echo "❌ Email function failed<br>";
}

// Test 3: Verification Code Generation
echo "<h3>3. Verification Code Generation Test</h3>";
$verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
echo "Generated verification code: <strong>$verificationCode</strong><br>";
echo "Code length: " . strlen($verificationCode) . " characters<br>";

if (strlen($verificationCode) === 4 && is_numeric($verificationCode)) {
    echo "✅ Verification code generation working correctly<br>";
} else {
    echo "❌ Verification code generation failed<br>";
}

// Test 4: Database Operations (if connection available)
if ($pdo !== null) {
    echo "<h3>4. Database Operations Test</h3>";
    
    try {
        // Test if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $tableExists = $stmt->fetch();
        
        if ($tableExists) {
            echo "✅ Users table exists<br>";
            
            // Test table structure
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredColumns = ['user_id', 'username', 'email', 'password', 'verification_code', 'verification_code_expires', 'email_verified'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                echo "✅ All required columns exist in users table<br>";
            } else {
                echo "❌ Missing columns: " . implode(', ', $missingColumns) . "<br>";
            }
            
            // Test inserting a test user
            echo "<h4>Testing User Registration Process:</h4>";
            $testUserEmail = "testuser" . time() . "@example.com";
            $testUserUsername = "testuser" . time();
            $testUserPassword = password_hash("testpass123", PASSWORD_DEFAULT);
            $testVerificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            
            // Check if test user already exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$testUserEmail, $testUserUsername]);
            $existingUser = $stmt->fetch();
            
            if (!$existingUser) {
                // Insert test user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_code, verification_code_expires, email_verified, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                $result = $stmt->execute([$testUserUsername, $testUserEmail, $testUserPassword, $testVerificationCode, $expiresAt]);
                
                if ($result) {
                    $userId = $pdo->lastInsertId();
                    echo "✅ Test user created successfully (ID: $userId)<br>";
                    echo "Test user email: $testUserEmail<br>";
                    echo "Test verification code: $testVerificationCode<br>";
                    
                    // Test verification process
                    echo "<h4>Testing Verification Process:</h4>";
                    $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE email = ? AND verification_code = ? AND verification_code_expires > NOW() AND email_verified = 0");
                    $stmt->execute([$testUserEmail, $testVerificationCode]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        echo "✅ Verification code validation working<br>";
                        
                        // Mark as verified
                        $updateStmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_code = NULL, verification_code_expires = NULL WHERE user_id = ?");
                        $updateStmt->execute([$user['user_id']]);
                        echo "✅ Email verification process completed<br>";
                    } else {
                        echo "❌ Verification code validation failed<br>";
                    }
                    
                    // Clean up test user
                    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $deleteStmt->execute([$userId]);
                    echo "✅ Test user cleaned up<br>";
                } else {
                    echo "❌ Failed to create test user<br>";
                }
            } else {
                echo "⚠️ Test user already exists, skipping creation test<br>";
            }
            
        } else {
            echo "❌ Users table does not exist<br>";
        }
        
        // Test if admins table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'admins'");
        $adminTableExists = $stmt->fetch();
        
        if ($adminTableExists) {
            echo "✅ Admins table exists<br>";
        } else {
            echo "❌ Admins table does not exist<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Database operation failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "<h3>4. Database Operations Test</h3>";
    echo "❌ Skipped - No database connection<br>";
}

// Test 5: Environment Variables
echo "<h3>5. Environment Variables Test</h3>";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'Not set (using: localhost)') . "<br>";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'Not set (using: nutrisaur_db)') . "<br>";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'Not set (using: root)') . "<br>";
echo "DB_PASS: " . (empty($_ENV['DB_PASS']) ? 'Not set (using: empty)' : 'Set') . "<br>";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'Not set (using: 3306)') . "<br>";

// Test 6: PHP Configuration
echo "<h3>6. PHP Configuration Test</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "PDO MySQL available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";
echo "Mail function available: " . (function_exists('mail') ? 'Yes' : 'No') . "<br>";

// Test 7: AJAX Endpoint Test
echo "<h3>7. AJAX Endpoint Test</h3>";
echo "Testing check_session endpoint...<br>";

// Simulate AJAX request
$_POST['ajax_action'] = 'check_session';
ob_start();
include __DIR__ . "/home.php";
$output = ob_get_clean();

if (strpos($output, '{"success":true') !== false) {
    echo "✅ check_session endpoint working<br>";
} else {
    echo "❌ check_session endpoint failed<br>";
    echo "Response: " . htmlspecialchars($output) . "<br>";
}

echo "<h2>Test Complete!</h2>";
echo "<p><strong>Summary:</strong></p>";
echo "<ul>";
echo "<li>If database connection shows ✅, the verification system can store and retrieve data</li>";
echo "<li>If email function shows ✅, verification emails can be sent</li>";
echo "<li>If verification code generation shows ✅, codes are generated correctly</li>";
echo "<li>If database operations show ✅, the full registration and verification flow works</li>";
echo "</ul>";

echo "<p><a href='home.php'>← Back to Home Page</a></p>";
?>
