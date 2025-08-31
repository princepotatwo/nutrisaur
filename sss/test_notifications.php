<?php
// Test script to verify notification system works
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 NutriSaur Notification System Test</h1>\n";

// Database connection
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "<h2>✅ Database Connected Successfully</h2>\n";
    
    // Check FCM tokens table
    echo "<h3>📱 FCM Tokens in Database:</h3>\n";
    $stmt = $conn->prepare("SELECT user_email, user_barangay, is_active, LENGTH(fcm_token) as token_length FROM fcm_tokens WHERE is_active = TRUE LIMIT 10");
    $stmt->execute();
    $tokens = $stmt->fetchAll();
    
    if (empty($tokens)) {
        echo "<p>❌ No FCM tokens found in database</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Email</th><th>Barangay</th><th>Active</th><th>Token Length</th></tr>\n";
        foreach ($tokens as $token) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($token['user_email']) . "</td>";
            echo "<td>" . htmlspecialchars($token['user_barangay']) . "</td>";
            echo "<td>" . ($token['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "<td>" . $token['token_length'] . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check users table
    echo "<h3>👥 Users in Database:</h3>\n";
    $stmt = $conn->prepare("SELECT email, username FROM users LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>❌ No users found in database</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>\n";
        echo "<tr><th>Email</th><th>Username</th></tr>\n";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Test notification API with first available user
    if (!empty($tokens)) {
        $testUser = $tokens[0];
        echo "<h3>🧪 Testing Notification API:</h3>\n";
        echo "<p>Testing with user: " . htmlspecialchars($testUser['user_email']) . "</p>\n";
        
        $notificationData = [
            'title' => '🧪 Test Event Notification',
            'body' => 'This is a test notification from NutriSaur!',
            'target_user' => $testUser['user_email'],
            'user_name' => $testUser['user_email'],
            'alert_type' => 'test_event'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://nutrisaur-production.up.railway.app/api/send_notification.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'notification_data' => json_encode($notificationData)
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        echo "<p><strong>API Response (HTTP $httpCode):</strong></p>\n";
        if ($error) {
            echo "<p style='color: red;'>❌ cURL Error: " . htmlspecialchars($error) . "</p>\n";
        } else {
            echo "<pre>" . htmlspecialchars($response) . "</pre>\n";
            
            $responseData = json_decode($response, true);
            if (isset($responseData['success']) && $responseData['success']) {
                echo "<p style='color: green;'>✅ Notification sent successfully!</p>\n";
                echo "<p>Check your device for the push notification!</p>\n";
            } else {
                echo "<p style='color: red;'>❌ Notification failed: " . htmlspecialchars($responseData['message'] ?? 'Unknown error') . "</p>\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Database Connection Failed</h2>\n";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Instructions:</strong></p>\n";
echo "<ol>\n";
echo "<li>Make sure your Android app is running and connected</li>\n";
echo "<li>Check if you receive the test notification above</li>\n";
echo "<li>If successful, the event.php notifications should also work</li>\n";
echo "</ol>\n";
?>
