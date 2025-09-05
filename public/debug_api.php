<?php
/**
 * Simple test script to check unified_api.php
 * This will help identify what's causing the HTML output instead of JSON
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Unified API...\n";
echo "=====================\n\n";

// Test the API endpoint directly
$url = 'http://localhost/unified_api.php?endpoint=usm';

echo "Testing URL: $url\n";
echo "Making request...\n";

// Use cURL for better error handling
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}

echo "Response:\n";
echo "---------\n";
echo $response . "\n";

// Try to parse as JSON
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize);
$jsonData = json_decode($body, true);

if ($jsonData) {
    echo "\nJSON Parsing: SUCCESS\n";
    echo "Success: " . ($jsonData['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . ($jsonData['message'] ?? 'No message') . "\n";
    if (isset($jsonData['data'])) {
        echo "Data count: " . count($jsonData['data']) . "\n";
    }
} else {
    echo "\nJSON Parsing: FAILED\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}

echo "\nTest complete.\n";
?>
