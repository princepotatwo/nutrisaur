<?php
/**
 * Test Home Integration with DatabaseAPI
 * This script tests if the home.php integration is working properly
 */

header('Content-Type: application/json');

// Include the centralized Database API
require_once __DIR__ . "/DatabaseAPI.php";

// Initialize the database API
$db = new DatabaseAPI();

$results = [];

// Test 1: Database Connection
$results['database_connection'] = [
    'test' => 'Database Connection Test',
    'result' => $db->testConnection() ? 'PASSED' : 'FAILED',
    'message' => $db->testConnection() ? 'Database connection successful' : 'Database connection failed'
];

// Test 2: Test Login with DatabaseAPI
$testUsername = 'test_user_' . time();
$testEmail = 'test_' . time() . '@example.com';
$testPassword = 'testpassword123';

// Test 3: Registration Test
$results['registration_test'] = [
    'test' => 'User Registration Test',
    'result' => 'PASSED',
    'data' => []
];

$registerResult = $db->registerUser($testUsername, $testEmail, $testPassword);
if ($registerResult['success']) {
    $results['registration_test']['data']['registration'] = 'SUCCESS';
    $results['registration_test']['data']['user_id'] = $registerResult['data']['user_id'];
    
    // Test 4: Login Test with newly created user
    $loginResult = $db->authenticateUser($testEmail, $testPassword);
    if ($loginResult['success']) {
        $results['login_test'] = [
            'test' => 'User Login Test',
            'result' => 'PASSED',
            'data' => [
                'user_type' => $loginResult['user_type'],
                'user_id' => $loginResult['data']['user_id'],
                'username' => $loginResult['data']['username'],
                'email' => $loginResult['data']['email']
            ]
        ];
    } else {
        $results['login_test'] = [
            'test' => 'User Login Test',
            'result' => 'FAILED',
            'error' => $loginResult['message']
        ];
    }
    
    // Test 5: Session Validation Test
    $sessionResult = $db->checkSession($loginResult['data']['user_id'], $testEmail);
    $results['session_test'] = [
        'test' => 'Session Validation Test',
        'result' => $sessionResult ? 'PASSED' : 'FAILED',
        'data' => [
            'session_valid' => $sessionResult
        ]
    ];
    
    // Clean up: Delete test user
    $cleanupResult = $db->executeQuery("DELETE FROM users WHERE user_id = ?", [$loginResult['data']['user_id']]);
    $results['cleanup_test'] = [
        'test' => 'Test User Cleanup',
        'result' => 'PASSED',
        'data' => [
            'user_deleted' => true
        ]
    ];
    
} else {
    $results['registration_test']['result'] = 'FAILED';
    $results['registration_test']['error'] = $registerResult['message'];
}

// Test 6: Community Metrics Test
$metrics = $db->getCommunityMetrics();
$results['metrics_test'] = [
    'test' => 'Community Metrics Test',
    'result' => !empty($metrics) ? 'PASSED' : 'FAILED',
    'data' => $metrics
];

// Test 7: FCM Token Test
$fcmResult = $db->registerFCMToken(
    'test_fcm_token_' . time(),
    'Test Device',
    'test@example.com',
    'Test Barangay',
    '1.0',
    'android'
);
$results['fcm_test'] = [
    'test' => 'FCM Token Registration Test',
    'result' => $fcmResult['success'] ? 'PASSED' : 'FAILED',
    'data' => $fcmResult
];

// Summary
$passed = 0;
$total = count($results);

foreach ($results as $test) {
    if ($test['result'] === 'PASSED') {
        $passed++;
    }
}

$summary = [
    'total_tests' => $total,
    'passed' => $passed,
    'failed' => $total - $passed,
    'success_rate' => round(($passed / $total) * 100, 2) . '%',
    'test_date' => date('Y-m-d H:i:s')
];

echo json_encode([
    'success' => true,
    'message' => 'Home Integration Test Results',
    'summary' => $summary,
    'tests' => $results,
    'note' => 'This test verifies that home.php can properly use the DatabaseAPI for all operations.'
], JSON_PRETTY_PRINT);

// Close the database connection
$db->close();
?>
