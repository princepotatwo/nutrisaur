<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/api/DatabaseAPI.php";

$db = new DatabaseAPI();
$pdo = $db->getPDO();

if (!$pdo) {
    echo "Database connection failed\n";
    exit;
}

echo "=== Database Table Structure Check ===\n\n";

// Check if user_preferences table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
    if ($stmt->rowCount() > 0) {
        echo "✅ user_preferences table exists\n";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE user_preferences");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nColumns in user_preferences table:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check for specific columns that might be missing
        $columnNames = array_column($columns, 'Field');
        $requiredColumns = ['height', 'weight', 'bmi', 'muac', 'swelling', 'weight_loss', 'feeding_behavior'];
        
        echo "\nChecking for required columns:\n";
        foreach ($requiredColumns as $col) {
            if (in_array($col, $columnNames)) {
                echo "✅ {$col} - exists\n";
            } else {
                echo "❌ {$col} - MISSING\n";
            }
        }
        
    } else {
        echo "❌ user_preferences table does not exist\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
}

// Check if users table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "\n✅ users table exists\n";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nColumns in users table:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        echo "\n❌ users table does not exist\n";
    }
} catch (Exception $e) {
    echo "\n❌ Error checking users table: " . $e->getMessage() . "\n";
}

echo "\n=== End of Database Check ===\n";
?>
