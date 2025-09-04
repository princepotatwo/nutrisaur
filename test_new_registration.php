<?php
// Test the new simple working registration API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing New Simple Working Registration API\n";
echo "==========================================\n\n";

// Test data
$testData = [
    'username' => 'testuser_' . time(),
    'email' => 'kevinpingol123@gmail.com',
    'password' => 'testpassword123'
];

echo "Test Data:\n";
echo "Username: " . $testData['username'] . "\n";
echo "Email: " . $testData['email'] . "\n";
echo "Password: " . $testData['password'] . "\n\n";

// Test the new registration API
$url = 'http://localhost/api/register_simple_working.php';
$data = json_encode($testData);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data)
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Sending registration request to new API...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n\n";

if ($response) {
    $result = json_decode($response, true);
    if ($result && isset($result['success'])) {
        if ($result['success']) {
            echo "✅ Registration successful!\n";
            if (isset($result['data']['email_sent']) && $result['data']['email_sent']) {
                echo "✅ Email sent successfully!\n";
            } else {
                echo "⚠️  Email not sent (but registration completed)\n";
            }
            
            if (isset($result['data']['verification_code'])) {
                echo "🔑 Verification Code: " . $result['data']['verification_code'] . "\n";
            }
        } else {
            echo "❌ Registration failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Invalid response format\n";
    }
} else {
    echo "❌ No response received\n";
}

echo "\nTest completed.\n";
?>
