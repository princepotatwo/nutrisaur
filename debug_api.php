<?php
/**
 * Debug API endpoint
 */

// Test the screening history API directly
$testEmail = 'kevinpingol123@gmail.com';
$apiUrl = "api/screening_history_api.php?action=get_history&user_email=" . urlencode($testEmail);

echo "Testing API: $apiUrl\n";
echo "================================\n";

// Test with curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response;
echo "\n\n";

// Try to decode JSON
$data = json_decode($response, true);
if ($data) {
    echo "✅ JSON is valid\n";
    if (isset($data['success'])) {
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if (isset($data['error'])) {
            echo "Error: " . $data['error'] . "\n";
        }
    }
} else {
    echo "❌ JSON is invalid\n";
    echo "Raw response (first 500 chars):\n";
    echo substr($response, 0, 500) . "\n";
}

// Test database connection
echo "\n=== Testing Database Connection ===\n";
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        echo "✅ Database connection successful\n";
        
        // Test if screening_history table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'screening_history'");
        if ($stmt->rowCount() > 0) {
            echo "✅ screening_history table exists\n";
            
            // Check if there's any data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM screening_history");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Records in screening_history: " . $result['count'] . "\n";
            
            // Check for Kevin's data specifically
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM screening_history WHERE user_email = ?");
            $stmt->execute([$testEmail]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Records for Kevin: " . $result['count'] . "\n";
            
        } else {
            echo "❌ screening_history table does not exist\n";
        }
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
