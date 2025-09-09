<?php
// Test script for verification code functionality
session_start();

// Include the home.php file to test the functions
require_once __DIR__ . "/public/home.php";

echo "<h2>Testing NUTRISAUR Verification System</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
if ($pdo !== null) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed: " . ($dbError ?? 'Unknown error') . "<br>";
    exit;
}

// Test email sending function
echo "<h3>2. Email Sending Test</h3>";
$testEmail = "test@example.com";
$testUsername = "TestUser";
$testCode = "1234";

echo "Testing email sending to: $testEmail<br>";
echo "Test verification code: $testCode<br>";

$emailSent = sendVerificationEmail($testEmail, $testUsername, $testCode);
if ($emailSent) {
    echo "✅ Email sent successfully<br>";
} else {
    echo "❌ Email sending failed<br>";
}

// Test verification code generation
echo "<h3>3. Verification Code Generation Test</h3>";
$verificationCode = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
echo "Generated verification code: $verificationCode<br>";
echo "Code length: " . strlen($verificationCode) . " characters<br>";

if (strlen($verificationCode) === 4 && is_numeric($verificationCode)) {
    echo "✅ Verification code generation working correctly<br>";
} else {
    echo "❌ Verification code generation failed<br>";
}

// Test database operations
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

// Test AJAX endpoints
echo "<h3>5. AJAX Endpoints Test</h3>";

// Test check_session endpoint
echo "Testing check_session endpoint...<br>";
$_POST['ajax_action'] = 'check_session';
ob_start();
include __DIR__ . "/public/home.php";
$output = ob_get_clean();

if (strpos($output, '{"success":true') !== false) {
    echo "✅ check_session endpoint working<br>";
} else {
    echo "❌ check_session endpoint failed<br>";
}

echo "<h3>6. Environment Variables Test</h3>";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'Not set') . "<br>";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'Not set') . "<br>";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'Not set') . "<br>";
echo "DB_PASS: " . (empty($_ENV['DB_PASS']) ? 'Not set' : 'Set') . "<br>";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'Not set') . "<br>";

echo "<h3>7. PHP Configuration Test</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "PDO MySQL available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "<br>";
echo "Mail function available: " . (function_exists('mail') ? 'Yes' : 'No') . "<br>";

echo "<h2>Test Complete!</h2>";
echo "<p>If all tests show ✅, your verification system should be working correctly.</p>";
?>
