<?php
// Test the API directly
echo "Testing API endpoint...\n";

$url = 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=register_community_user';

$data = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => 'testpassword123',
    'municipality' => 'Test Municipality',
    'barangay' => 'Test Barangay',
    'sex' => 'Male',
    'birthday' => '1990-01-01',
    'is_pregnant' => 'No',
    'weight' => '70',
    'height' => '175'
];

$options = [
    'http' => [
        'header' => "Content-Type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "Response: " . $result . "\n";

// Also test with a simple GET to see if endpoint exists
echo "\nTesting GET request...\n";
$get_result = file_get_contents('https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=register_community_user');
echo "GET Response: " . $get_result . "\n";
?>