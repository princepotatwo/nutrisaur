<?php
/**
 * Test API endpoint
 */

// Test the screening history API
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
?>
