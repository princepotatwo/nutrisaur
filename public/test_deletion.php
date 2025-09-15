<?php
// Test deletion functionality
require_once 'public/api/DatabaseAPI.php';

echo "=== Testing Event Deletion ===\n";

// Test 1: Check if the database connection works
echo "1. Testing database connection...\n";
$db = DatabaseAPI::getInstance();
if ($db->isDatabaseAvailable()) {
    echo "✅ Database connection successful\n";
} else {
    echo "❌ Database connection failed\n";
    exit(1);
}

// Test 2: Check current events
echo "\n2. Current events in database:\n";
$result = $db->universalQuery("SELECT program_id, title FROM programs ORDER BY program_id DESC LIMIT 5");
if ($result['success']) {
    foreach ($result['data'] as $event) {
        echo "   - ID: {$event['program_id']}, Title: {$event['title']}\n";
    }
} else {
    echo "❌ Failed to query events: " . $result['message'] . "\n";
    exit(1);
}

// Test 3: Test deletion method directly
echo "\n3. Testing universalDelete method...\n";
$testId = 13; // Event ID to delete
$deleteResult = $db->universalDelete('programs', 'program_id = ?', [$testId]);
echo "Delete result: " . json_encode($deleteResult, JSON_PRETTY_PRINT) . "\n";

// Test 4: Check if deletion worked
echo "\n4. Checking if event was deleted...\n";
$checkResult = $db->universalQuery("SELECT COUNT(*) as count FROM programs WHERE program_id = ?", [$testId]);
if ($checkResult['success']) {
    $count = $checkResult['data'][0]['count'];
    if ($count == 0) {
        echo "✅ Event successfully deleted\n";
    } else {
        echo "❌ Event still exists\n";
    }
} else {
    echo "❌ Failed to check deletion: " . $checkResult['message'] . "\n";
}

echo "\n=== Test Complete ===\n";
?>
