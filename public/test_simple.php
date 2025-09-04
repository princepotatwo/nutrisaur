<?php
echo "<h1>Simple Database Test</h1>\n";
echo "<pre>\n";

// Test 1: Direct connection
echo "=== Test 1: Direct Connection ===\n";
require_once __DIR__ . "/config.php";

try {
    $pdo = getDatabaseConnection();
    if ($pdo) {
        echo "✅ Direct connection SUCCESS\n";
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ Query result: " . $result['test'] . "\n";
    } else {
        echo "❌ Direct connection FAILED\n";
    }
} catch (Exception $e) {
    echo "❌ Direct connection ERROR: " . $e->getMessage() . "\n";
}

// Test 2: DatabaseAPI
echo "\n=== Test 2: DatabaseAPI ===\n";
require_once __DIR__ . "/api/DatabaseAPI.php";

try {
    $db = new DatabaseAPI();
    if ($db->testConnection()) {
        echo "✅ DatabaseAPI SUCCESS\n";
        $pdo = $db->getPDO();
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "✅ Query result: " . $result['test'] . "\n";
    } else {
        echo "❌ DatabaseAPI FAILED\n";
    }
} catch (Exception $e) {
    echo "❌ DatabaseAPI ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Environment check
echo "\n=== Test 3: Environment ===\n";
echo "MYSQL_PUBLIC_URL: " . ($_ENV['MYSQL_PUBLIC_URL'] ?? 'NOT_SET') . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";

echo "</pre>\n";
?>
