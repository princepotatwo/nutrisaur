<?php
// Comprehensive Database Test File
// Access this at: /test_database

header('Content-Type: application/json');

echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'test' => 'Database Connection and Structure Test'
], JSON_PRETTY_PRINT);

echo "\n\n";

// Test 1: Environment Variables
echo "=== ENVIRONMENT VARIABLES ===\n";
$env_vars = [
    'MYSQL_PUBLIC_URL' => isset($_ENV['MYSQL_PUBLIC_URL']) ? substr($_ENV['MYSQL_PUBLIC_URL'], 0, 20) . '...' : 'NOT_SET',
    'MYSQL_HOST' => $_ENV['MYSQL_HOST'] ?? 'NOT_SET',
    'MYSQL_PORT' => $_ENV['MYSQL_PORT'] ?? 'NOT_SET',
    'MYSQL_DATABASE' => $_ENV['MYSQL_DATABASE'] ?? 'NOT_SET',
    'MYSQL_USER' => $_ENV['MYSQL_USER'] ?? 'NOT_SET',
    'MYSQL_PASSWORD' => $_ENV['MYSQL_PASSWORD'] ?? 'NOT_SET'
];

foreach ($env_vars as $key => $value) {
    echo "$key: $value\n";
}

echo "\n";

// Test 2: Load config and DatabaseAPI
echo "=== LOADING CONFIG AND DATABASEAPI ===\n";
try {
    require_once __DIR__ . "/config.php";
    require_once __DIR__ . "/api/DatabaseAPI.php";
    echo "✅ Config and DatabaseAPI loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "\n";
    exit;
}

echo "\n";

// Test 3: Create DatabaseAPI instance
echo "=== CREATING DATABASEAPI INSTANCE ===\n";
try {
    $db = DatabaseAPI::getInstance();
    echo "✅ DatabaseAPI instance created successfully\n";
} catch (Exception $e) {
    echo "❌ Error creating DatabaseAPI instance: " . $e->getMessage() . "\n";
    exit;
}

echo "\n";

// Test 4: Check database status
echo "=== DATABASE STATUS ===\n";
try {
    $status = $db->getDatabaseStatus();
    echo "Database Status:\n";
    foreach ($status as $key => $value) {
        echo "  $key: " . ($value ? '✅ true' : '❌ false') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error getting database status: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Get PDO connection
echo "=== PDO CONNECTION TEST ===\n";
try {
    $pdo = $db->getPDO();
    if ($pdo) {
        echo "✅ PDO connection available\n";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Simple query test: " . $result['test'] . "\n";
    } else {
        echo "❌ PDO connection is null\n";
    }
} catch (Exception $e) {
    echo "❌ PDO connection error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 6: List all tables
echo "=== LISTING ALL TABLES ===\n";
try {
    if ($pdo) {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "❌ No tables found in database\n";
        } else {
            echo "✅ Found " . count($tables) . " tables:\n";
            foreach ($tables as $table) {
                echo "  - $table\n";
            }
        }
    } else {
        echo "❌ Cannot list tables - no PDO connection\n";
    }
} catch (Exception $e) {
    echo "❌ Error listing tables: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 7: Check specific tables and their columns
echo "=== TABLE STRUCTURE ANALYSIS ===\n";
$important_tables = ['users', 'user_preferences', 'screening_assessments', 'fcm_tokens', 'admin', 'events'];

foreach ($important_tables as $table) {
    echo "\n--- $table table ---\n";
    try {
        if ($pdo) {
            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table exists\n";
                
                // Get table structure
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "Columns:\n";
                foreach ($columns as $column) {
                    echo "  - {$column['Field']} ({$column['Type']}) - {$column['Null']} - {$column['Key']}\n";
                }
                
                // Get row count
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "Row count: $count\n";
                
            } else {
                echo "❌ Table does not exist\n";
            }
        } else {
            echo "❌ Cannot check table - no PDO connection\n";
        }
    } catch (Exception $e) {
        echo "❌ Error checking $table: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 8: Test specific API endpoints
echo "=== TESTING API ENDPOINTS ===\n";
$test_endpoints = [
    'community_metrics',
    'risk_distribution', 
    'geographic_distribution',
    'critical_alerts',
    'detailed_screening_responses'
];

foreach ($test_endpoints as $endpoint) {
    echo "\n--- Testing $endpoint ---\n";
    try {
        switch ($endpoint) {
            case 'community_metrics':
                $result = $db->getCommunityMetrics();
                break;
            case 'risk_distribution':
                $result = $db->getRiskDistribution();
                break;
            case 'geographic_distribution':
                $result = $db->getGeographicDistribution();
                break;
            case 'critical_alerts':
                $result = $db->getCriticalAlerts();
                break;
            case 'detailed_screening_responses':
                $result = $db->getDetailedScreeningResponses();
                break;
        }
        
        if (is_array($result)) {
            echo "✅ Success - returned " . count($result) . " items\n";
            if (count($result) > 0) {
                echo "Sample data: " . json_encode(array_slice($result, 0, 2)) . "\n";
            }
        } else {
            echo "✅ Success - returned: " . json_encode($result) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Error testing $endpoint: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 9: Check for any recent errors in logs
echo "=== RECENT ERROR SUMMARY ===\n";
echo "Check Railway logs for recent database connection errors\n";
echo "Common issues:\n";
echo "- Connection limits reached\n";
echo "- Database service down\n";
echo "- Wrong credentials\n";
echo "- Network connectivity issues\n";

echo "\n=== TEST COMPLETE ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
?>
