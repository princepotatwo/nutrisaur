<?php
/**
 * Test Forgot Password Functionality
 * This script tests the forgot password API after the database fix
 */

require_once 'public/config.php';

// Set content type
header('Content-Type: application/json');

echo "Testing Forgot Password Functionality\n";
echo "=====================================\n\n";

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Check if the required columns exist
    echo "1. Checking database schema...\n";
    $result = $pdo->query("SHOW COLUMNS FROM community_users");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    $hasResetCode = false;
    $hasResetExpires = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'password_reset_code') {
            $hasResetCode = true;
            echo "   ✓ password_reset_code column exists\n";
        }
        if ($column['Field'] === 'password_reset_expires') {
            $hasResetExpires = true;
            echo "   ✓ password_reset_expires column exists\n";
        }
    }
    
    if (!$hasResetCode || !$hasResetExpires) {
        echo "   ❌ Missing required columns!\n";
        echo "   Please run fix_forgot_password.php first\n";
        exit;
    }
    
    // Test the API endpoint
    echo "\n2. Testing forgot password API...\n";
    
    // Simulate a POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['email'] = 'test@example.com';
    $_GET['action'] = 'forgot_password_community';
    
    // Capture the output
    ob_start();
    include 'public/api/DatabaseAPI.php';
    $output = ob_get_clean();
    
    echo "   API Response: " . $output . "\n";
    
    // Parse the response
    $response = json_decode($output, true);
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "   ✓ API is working correctly\n";
        } else {
            echo "   ⚠ API returned error: " . $response['message'] . "\n";
            echo "   This might be expected if the test email doesn't exist\n";
        }
    } else {
        echo "   ❌ API response format is invalid\n";
    }
    
    echo "\n3. Testing with a real user email...\n";
    
    // Get a real user email from the database
    $stmt = $pdo->query("SELECT email FROM community_users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $testEmail = $user['email'];
        echo "   Testing with email: " . $testEmail . "\n";
        
        $_POST['email'] = $testEmail;
        
        ob_start();
        include 'public/api/DatabaseAPI.php';
        $output = ob_get_clean();
        
        echo "   API Response: " . $output . "\n";
        
        $response = json_decode($output, true);
        if ($response && isset($response['success'])) {
            if ($response['success']) {
                echo "   ✓ Forgot password is working correctly!\n";
                echo "   Reset code: " . (isset($response['message']) ? $response['message'] : 'N/A') . "\n";
            } else {
                echo "   ❌ API error: " . $response['message'] . "\n";
            }
        } else {
            echo "   ❌ Invalid API response format\n";
        }
    } else {
        echo "   ⚠ No users found in database to test with\n";
    }
    
    echo "\n🎉 Forgot password functionality test completed!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
