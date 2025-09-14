 <?php
// Test script to verify FCM token functionality with community_users table
require_once 'public/api/DatabaseAPI.php';

echo "=== Testing FCM Token Functionality with Community Users Table ===\n\n";

try {
    $db = DatabaseAPI::getInstance();
    
    if (!$db->isDatabaseAvailable()) {
        echo "❌ Database not available\n";
        exit(1);
    }
    
    echo "✅ Database connection successful\n\n";
    
    // Test 1: Register FCM token for existing user
    echo "1. Testing FCM token registration for existing user...\n";
    $testEmail = 'test@example.com';
    $testToken = 'test_fcm_token_' . time();
    $testDevice = 'Test Device';
    $testBarangay = 'Test Barangay';
    
    // First, create a test user in community_users
    $stmt = $db->getPDO()->prepare("
        INSERT INTO community_users 
        (email, municipality, barangay, sex, birthday, age, weight_kg, height_cm, muac_cm, status) 
        VALUES (?, 'Test Municipality', ?, 'Male', '1990-01-01', 34, 70.0, 175.0, 25.0, 'active')
        ON DUPLICATE KEY UPDATE email = email
    ");
    $stmt->execute([$testEmail, $testBarangay]);
    
    // Register FCM token
    $result = $db->registerFCMToken($testToken, $testDevice, $testEmail, $testBarangay, '1.0', 'android');
    if ($result['success']) {
        echo "✅ FCM token registered successfully\n";
    } else {
        echo "❌ FCM token registration failed: " . $result['message'] . "\n";
    }
    
    // Test 2: Get active FCM tokens
    echo "\n2. Testing getActiveFCMTokens...\n";
    $tokens = $db->getActiveFCMTokens();
    echo "Found " . count($tokens) . " active FCM tokens\n";
    
    if (count($tokens) > 0) {
        $found = false;
        foreach ($tokens as $token) {
            if ($token['fcm_token'] === $testToken) {
                echo "✅ Test token found in active tokens\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "❌ Test token not found in active tokens\n";
        }
    }
    
    // Test 3: Get FCM tokens by barangay
    echo "\n3. Testing getFCMTokensByBarangay...\n";
    $barangayTokens = $db->getFCMTokensByBarangay($testBarangay);
    echo "Found " . count($barangayTokens) . " FCM tokens for barangay: $testBarangay\n";
    
    if (count($barangayTokens) > 0) {
        $found = false;
        foreach ($barangayTokens as $token) {
            if ($token['fcm_token'] === $testToken) {
                echo "✅ Test token found in barangay tokens\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "❌ Test token not found in barangay tokens\n";
        }
    }
    
    // Test 4: Test notification sending
    echo "\n4. Testing notification sending...\n";
    $notificationData = [
        'title' => 'Test Notification',
        'body' => 'This is a test notification',
        'target_user' => $testEmail
    ];
    
    // Simulate the notification sending process
    $stmt = $db->getPDO()->prepare("SELECT fcm_token FROM community_users WHERE email = :email AND status = 'active' AND fcm_token IS NOT NULL AND fcm_token != ''");
    $stmt->bindParam(':email', $testEmail);
    $stmt->execute();
    $fcmTokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($fcmTokens) > 0) {
        echo "✅ Found " . count($fcmTokens) . " FCM token(s) for notification\n";
        echo "   Token: " . substr($fcmTokens[0], 0, 50) . "...\n";
    } else {
        echo "❌ No FCM tokens found for notification\n";
    }
    
    // Test 5: Deactivate FCM token
    echo "\n5. Testing FCM token deactivation...\n";
    $deactivateResult = $db->deactivateFCMToken($testToken);
    if ($deactivateResult) {
        echo "✅ FCM token deactivated successfully\n";
        
        // Verify token is no longer active
        $stmt = $db->getPDO()->prepare("SELECT fcm_token FROM community_users WHERE fcm_token = :token");
        $stmt->bindParam(':token', $testToken);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['fcm_token'] === null) {
            echo "✅ Token successfully removed from database\n";
        } else {
            echo "❌ Token still exists in database\n";
        }
    } else {
        echo "❌ FCM token deactivation failed\n";
    }
    
    // Clean up test data
    echo "\n6. Cleaning up test data...\n";
    $stmt = $db->getPDO()->prepare("DELETE FROM community_users WHERE email = ?");
    $stmt->execute([$testEmail]);
    echo "✅ Test data cleaned up\n";
    
    echo "\n=== All Tests Completed ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
