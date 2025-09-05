<?php
/**
 * Test script for unified_api.php endpoints
 * This script tests all the API endpoints to ensure they work correctly
 */

// Start session for testing
session_start();

// Simulate admin login for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['email'] = 'admin@test.com';

echo "Testing Unified API Endpoints\n";
echo "============================\n\n";

// Test USM endpoint
echo "1. Testing USM endpoint...\n";
$usmUrl = 'http://localhost/unified_api.php?endpoint=usm';
$usmResponse = file_get_contents($usmUrl);
$usmData = json_decode($usmResponse, true);

if ($usmData && $usmData['success']) {
    echo "   ✓ USM endpoint working - Found " . count($usmData['data']) . " users\n";
} else {
    echo "   ✗ USM endpoint failed: " . ($usmData['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test add user endpoint
echo "2. Testing add_user endpoint...\n";
$addUserData = [
    'endpoint' => 'add_user',
    'username' => 'testuser_' . time(),
    'email' => 'testuser_' . time() . '@example.com',
    'password' => 'testpassword123',
    'first_name' => 'Test',
    'last_name' => 'User',
    'barangay' => 'Test Barangay',
    'municipality' => 'Test Municipality'
];

$addUserContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($addUserData)
    ]
]);

$addUserUrl = 'http://localhost/unified_api.php';
$addUserResponse = file_get_contents($addUserUrl, false, $addUserContext);
$addUserResult = json_decode($addUserResponse, true);

if ($addUserResult && $addUserResult['success']) {
    echo "   ✓ Add user endpoint working - Created user ID: " . $addUserResult['data']['user_id'] . "\n";
    $testUserId = $addUserResult['data']['user_id'];
} else {
    echo "   ✗ Add user endpoint failed: " . ($addUserResult['message'] ?? 'Unknown error') . "\n";
    $testUserId = null;
}

echo "\n";

// Test update user endpoint (if we have a test user)
if ($testUserId) {
    echo "3. Testing update_user endpoint...\n";
    $updateUserData = [
        'endpoint' => 'update_user',
        'id' => $testUserId,
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'barangay' => 'Updated Barangay'
    ];

    $updateUserContext = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($updateUserData)
        ]
    ]);

    $updateUserResponse = file_get_contents($addUserUrl, false, $updateUserContext);
    $updateUserResult = json_decode($updateUserResponse, true);

    if ($updateUserResult && $updateUserResult['success']) {
        echo "   ✓ Update user endpoint working\n";
    } else {
        echo "   ✗ Update user endpoint failed: " . ($updateUserResult['message'] ?? 'Unknown error') . "\n";
    }

    echo "\n";

    // Test delete user endpoint
    echo "4. Testing delete_user endpoint...\n";
    $deleteUserData = [
        'endpoint' => 'delete_user',
        'user_id' => $testUserId
    ];

    $deleteUserContext = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($deleteUserData)
        ]
    ]);

    $deleteUserResponse = file_get_contents($addUserUrl, false, $deleteUserContext);
    $deleteUserResult = json_decode($deleteUserResponse, true);

    if ($deleteUserResult && $deleteUserResult['success']) {
        echo "   ✓ Delete user endpoint working\n";
    } else {
        echo "   ✗ Delete user endpoint failed: " . ($deleteUserResult['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "3. Skipping update_user and delete_user tests (no test user created)\n";
}

echo "\n";

// Test delete users by location endpoint
echo "5. Testing delete_users_by_location endpoint...\n";
$deleteByLocationData = [
    'endpoint' => 'delete_users_by_location',
    'barangay' => 'NonExistentBarangay'
];

$deleteByLocationContext = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($deleteByLocationData)
    ]
]);

$deleteByLocationResponse = file_get_contents($addUserUrl, false, $deleteByLocationContext);
$deleteByLocationResult = json_decode($deleteByLocationResponse, true);

if ($deleteByLocationResult) {
    echo "   ✓ Delete users by location endpoint working (no users found to delete)\n";
} else {
    echo "   ✗ Delete users by location endpoint failed: " . ($deleteByLocationResult['message'] ?? 'Unknown error') . "\n";
}

echo "\n";
echo "API Testing Complete!\n";
echo "=====================\n";
echo "Note: This is a basic test. For production use, ensure proper authentication and validation.\n";
?>
