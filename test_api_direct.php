<?php
/**
 * Direct API test - run this to test the screening history API
 */

echo "🧪 Testing Screening History API Directly\n";
echo "========================================\n\n";

// Test the API endpoint directly
$apiUrl = "https://nutrisaur-production.up.railway.app/api/screening_history_api.php?action=get_history&user_email=kevinpingol123@gmail.com&limit=20";

echo "Testing API URL: $apiUrl\n";
echo "----------------------------------------\n";

// Test with curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "CURL Error: $error\n";
}
echo "Response Length: " . strlen($response) . " characters\n";
echo "----------------------------------------\n";
echo "Raw Response:\n";
echo $response;
echo "\n----------------------------------------\n";

// Try to decode JSON
if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "✅ JSON is valid\n";
        echo "Success: " . ($data['success'] ?? 'unknown') . "\n";
        if (isset($data['error'])) {
            echo "Error: " . $data['error'] . "\n";
        }
        if (isset($data['data']['total_records'])) {
            echo "Total Records: " . $data['data']['total_records'] . "\n";
        }
    } else {
        echo "❌ JSON is invalid\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "❌ No response received\n";
}

echo "\n=== Testing Database Connection Locally ===\n";
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        echo "✅ Local database connection successful\n";
        
        // Test if screening_history table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'screening_history'");
        if ($stmt->rowCount() > 0) {
            echo "✅ screening_history table exists\n";
            
            // Check if there's any data
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM screening_history");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Total records in screening_history: " . $result['count'] . "\n";
            
            // Check for Kevin's data specifically
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM screening_history WHERE user_email = ?");
            $stmt->execute(['kevinpingol123@gmail.com']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Records for Kevin: " . $result['count'] . "\n";
            
            if ($result['count'] > 0) {
                echo "✅ Kevin has screening history data\n";
                
                // Show sample data
                $stmt = $pdo->prepare("SELECT * FROM screening_history WHERE user_email = ? ORDER BY screening_date DESC LIMIT 3");
                $stmt->execute(['kevinpingol123@gmail.com']);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "\n📋 Sample records for Kevin:\n";
                foreach ($records as $record) {
                    echo "- Date: {$record['screening_date']}, Weight: {$record['weight']}kg, BMI: {$record['bmi']}, Classification: {$record['classification']}\n";
                }
            } else {
                echo "❌ Kevin has no screening history data\n";
                echo "💡 You need to add test data first!\n";
            }
            
        } else {
            echo "❌ screening_history table does not exist\n";
            echo "💡 You need to create the table first!\n";
        }
    } else {
        echo "❌ Local database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Local database error: " . $e->getMessage() . "\n";
}

echo "\n=== Recommendations ===\n";
echo "1. If Kevin has no data, run the test_kevin_underweight_data.sql\n";
echo "2. If the API returns 500, check the server error logs\n";
echo "3. If JSON is invalid, there might be PHP errors in the API\n";
echo "4. Test the API URL directly in your browser\n";
?>
