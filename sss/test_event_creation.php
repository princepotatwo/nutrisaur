<?php
// Test script to verify event creation with empty location
require_once __DIR__ . "/../config.php";

echo "<h2>Testing Event Creation with Empty Location</h2>";

try {
    // Test 1: Try to insert an event with empty location
    $stmt = $conn->prepare("
        INSERT INTO programs (title, type, description, date_time, location, organizer, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $testData = [
        'Test Event - All Locations',
        'Test Type',
        'Test Description for All Locations',
        '2024-12-25 10:00:00',
        '', // Empty location
        'Test Organizer'
    ];
    
    $result = $stmt->execute($testData);
    
    if ($result) {
        $eventId = $conn->lastInsertId();
        echo "<p style='color: green;'>✅ Test event created successfully with ID: $eventId</p>";
        echo "<p>Location value stored: '" . $testData[4] . "' (length: " . strlen($testData[4]) . ")</p>";
        
        // Verify the event was stored correctly
        $verifyStmt = $conn->prepare("SELECT * FROM programs WHERE program_id = ?");
        $verifyStmt->execute([$eventId]);
        $event = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event) {
            echo "<p>✅ Event retrieved from database:</p>";
            echo "<ul>";
            echo "<li>Title: " . htmlspecialchars($event['title']) . "</li>";
            echo "<li>Location: '" . htmlspecialchars($event['location']) . "'</li>";
            echo "<li>Location length: " . strlen($event['location']) . "</li>";
            echo "<li>Location is empty: " . (empty($event['location']) ? 'YES' : 'NO') . "</li>";
            echo "</ul>";
        }
        
        // Clean up - delete test event
        $deleteStmt = $conn->prepare("DELETE FROM programs WHERE program_id = ?");
        $deleteStmt->execute([$eventId]);
        echo "<p>🧹 Test event cleaned up</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Failed to create test event</p>";
    }
    
    // Test 2: Check if there are any database constraints
    echo "<h3>Database Schema Check</h3>";
    $schemaStmt = $conn->prepare("DESCRIBE programs");
    $schemaStmt->execute();
    $columns = $schemaStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
