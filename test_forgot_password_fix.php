<?php
/**
 * Test the forgot password fix
 * This script tests the API after fixing the 'id' column issue
 */

require_once 'public/config.php';

// Set content type
header('Content-Type: application/json');

echo "Testing Forgot Password Fix\n";
echo "==========================\n\n";

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Test the fixed API endpoint
    echo "1. Testing forgot password API with fixed queries...\n";
    
    // Simulate a POST request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['email'] = 'kevinpingol123@gmail.com';
    $_GET['action'] = 'forgot_password_community';
    
    // Capture the output
    ob_start();
    include 'public/api/DatabaseAPI.php';
    $output = ob_get_clean();
    
    echo "API Response: " . $output . "\n";
    
    // Parse the response
    $response = json_decode($output, true);
    if ($response && isset($response['success'])) {
        if ($response['success']) {
            echo "✅ SUCCESS: Forgot password is now working!\n";
            echo "Message: " . $response['message'] . "\n";
        } else {
            echo "❌ Still failing: " . $response['message'] . "\n";
        }
    } else {
        echo "❌ Invalid API response format\n";
    }
    
    echo "\n2. Testing with a real user email from database...\n";
    
    // Get a real user email from the database
    $stmt = $pdo->query("SELECT email FROM community_users LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $testEmail = $user['email'];
        echo "Testing with email: " . $testEmail . "\n";
        
        $_POST['email'] = $testEmail;
        
        ob_start();
        include 'public/api/DatabaseAPI.php';
        $output = ob_get_clean();
        
        echo "API Response: " . $output . "\n";
        
        $response = json_decode($output, true);
        if ($response && isset($response['success'])) {
            if ($response['success']) {
                echo "✅ SUCCESS: Forgot password works with real user!\n";
            } else {
                echo "❌ Failed with real user: " . $response['message'] . "\n";
            }
        } else {
            echo "❌ Invalid API response format\n";
        }
    } else {
        echo "⚠️ No users found in database to test with\n";
    }
    
    echo "\n🎉 Forgot password fix test completed!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
