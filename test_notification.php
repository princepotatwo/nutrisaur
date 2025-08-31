<?php
// Simple test script for the notification system
echo "🧪 Testing NutriSaur Notification System\n";
echo "=====================================\n\n";

// Test database connection
echo "1. Testing Database Connection...\n";
$host = 'localhost';
$dbname = 'nutrisaur_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Database connection successful\n";
} catch(PDOException $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test FCM tokens table
echo "\n2. Testing FCM Tokens Table...\n";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM fcm_tokens WHERE is_active = TRUE AND fcm_token IS NOT NULL AND fcm_token != ''");
    $stmt->execute();
    $tokenCount = $stmt->fetchColumn();
    echo "   ✅ Found $tokenCount active FCM tokens\n";
    
    if ($tokenCount > 0) {
        $stmt = $conn->prepare("SELECT fcm_token, user_email, device_name FROM fcm_tokens WHERE is_active = TRUE AND fcm_token IS NOT NULL AND fcm_token != '' LIMIT 3");
        $stmt->execute();
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   📱 Sample tokens:\n";
        foreach ($tokens as $token) {
            echo "      - " . substr($token['fcm_token'], 0, 50) . "... (" . ($token['user_email'] ?? 'No email') . " - " . ($token['device_name'] ?? 'Unknown device') . ")\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error checking FCM tokens: " . $e->getMessage() . "\n";
}

// Test Firebase Admin SDK file
echo "\n3. Testing Firebase Admin SDK...\n";
$adminSdkPath = __DIR__ . '/sss/nutrisaur-ebf29-firebase-adminsdk-fbsvc-8dc50fb07f.json';
if (file_exists($adminSdkPath)) {
    echo "   ✅ Firebase Admin SDK file found\n";
    
    $serviceAccount = json_decode(file_get_contents($adminSdkPath), true);
    if ($serviceAccount && isset($serviceAccount['project_id'])) {
        echo "   ✅ Firebase project ID: " . $serviceAccount['project_id'] . "\n";
        echo "   ✅ Client email: " . $serviceAccount['client_email'] . "\n";
    } else {
        echo "   ❌ Invalid Firebase service account format\n";
    }
} else {
    echo "   ❌ Firebase Admin SDK file not found at: $adminSdkPath\n";
}

// Test notification logs table
echo "\n4. Testing Notification Logs Table...\n";
try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notification_logs");
    $stmt->execute();
    $logCount = $stmt->fetchColumn();
    echo "   ✅ Found $logCount notification logs\n";
    
    if ($logCount > 0) {
        $stmt = $conn->prepare("SELECT notification_type, title, sent_to_count, success_count, failure_count, created_at FROM notification_logs ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   📋 Recent logs:\n";
        foreach ($logs as $log) {
            echo "      - " . $log['title'] . " (" . $log['notification_type'] . ") - Sent: " . $log['sent_to_count'] . ", Success: " . $log['success_count'] . ", Failed: " . $log['failure_count'] . " - " . $log['created_at'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error checking notification logs: " . $e->getMessage() . "\n";
}

// Test specific token from user
echo "\n5. Testing Specific FCM Token...\n";
$specificToken = 'dvMeL_TxQzKV_ScrM_I0L5:APA91bFkZfYXi3EYo7NLMP5isEDt5MRSRrVtGh2FojBW8zamrZT3BYRUfO3kTdiyfWtLmcZleotA9PkKnniji02l7OcrM5vdHCf95OHEsLTDsGwpVt_6q3g';
echo "   📱 Token: " . substr($specificToken, 0, 50) . "...\n";

// Check if this token exists in database
try {
    $stmt = $conn->prepare("SELECT * FROM fcm_tokens WHERE fcm_token = ?");
    $stmt->execute([$specificToken]);
    if ($stmt->rowCount() > 0) {
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   ✅ Token found in database\n";
        echo "      - User: " . ($tokenData['user_email'] ?? 'No email') . "\n";
        echo "      - Device: " . ($tokenData['device_name'] ?? 'Unknown') . "\n";
        echo "      - Barangay: " . ($tokenData['user_barangay'] ?? 'Unknown') . "\n";
    } else {
        echo "   ⚠️  Token not found in database (can still be used for testing)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error checking token: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "🎯 Next Steps:\n";
echo "1. Open http://localhost:8000 in your browser\n";
echo "2. Test sending a notification to all devices\n";
echo "3. Test sending a notification to the specific token\n";
echo "4. Check the notification logs\n";
echo "\n💡 The system is ready for testing!\n";
?>
