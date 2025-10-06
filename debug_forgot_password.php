<?php
/**
 * Debug Forgot Password API
 * This script will help us debug the 500 error
 */

require_once 'public/config.php';

// Set content type
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Forgot Password API\n";
echo "========================\n\n";

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    echo "✅ Database connection successful\n";
    
    // Check if the password reset columns exist
    echo "\n1. Checking if password reset columns exist...\n";
    
    $result = $pdo->query("SHOW COLUMNS FROM community_users");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $hasResetCode = false;
    $hasResetExpires = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'password_reset_code') {
            $hasResetCode = true;
            echo "✅ password_reset_code column exists\n";
        }
        if ($column['Field'] === 'password_reset_expires') {
            $hasResetExpires = true;
            echo "✅ password_reset_expires column exists\n";
        }
    }
    
    if (!$hasResetCode || !$hasResetExpires) {
        echo "❌ Missing password reset columns!\n";
        echo "password_reset_code exists: " . ($hasResetCode ? "YES" : "NO") . "\n";
        echo "password_reset_expires exists: " . ($hasResetExpires ? "YES" : "NO") . "\n";
        exit;
    }
    
    // Test the user lookup query
    echo "\n2. Testing user lookup query...\n";
    $testEmail = 'kevinpingol123@gmail.com';
    
    $stmt = $pdo->prepare("SELECT email, name FROM community_users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User found: " . $user['email'] . " - " . $user['name'] . "\n";
    } else {
        echo "❌ User not found: " . $testEmail . "\n";
    }
    
    // Test the password reset update query
    echo "\n3. Testing password reset update query...\n";
    
    $resetCode = '1234';
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    try {
        $updateStmt = $pdo->prepare("UPDATE community_users SET password_reset_code = ?, password_reset_expires = ? WHERE email = ?");
        $updateStmt->execute([$resetCode, $expiresAt, $testEmail]);
        echo "✅ Password reset update query successful\n";
    } catch (Exception $e) {
        echo "❌ Password reset update query failed: " . $e->getMessage() . "\n";
    }
    
    // Test the actual API endpoint
    echo "\n4. Testing actual API endpoint...\n";
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['email'] = $testEmail;
    $_GET['action'] = 'forgot_password_community';
    
    ob_start();
    include 'public/api/DatabaseAPI.php';
    $output = ob_get_clean();
    
    echo "API Response: " . $output . "\n";
    
    $response = json_decode($output, true);
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "✅ API test successful!\n";
        } else {
            echo "❌ API returned error: " . $response['message'] . "\n";
        }
    } else {
        echo "❌ Invalid API response format\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
