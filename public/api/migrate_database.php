<?php
/**
 * Database migration endpoint
 * Run this to fix the date column to allow NULL values
 */

require_once '../../config.php';

// Set content type
header('Content-Type: application/json');

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    echo "🔄 Starting database migration...\n";
    
    // Execute the migration
    $sql = "ALTER TABLE user_food_history MODIFY COLUMN date DATE NULL";
    $pdo->exec($sql);
    
    echo "✅ Migration completed successfully!\n";
    echo "✅ Date column now allows NULL values for MHO recommended foods\n";
    
    // Verify the change
    $stmt = $pdo->query("DESCRIBE user_food_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'date') {
            echo "📋 Date column info:\n";
            echo "   - Field: " . $column['Field'] . "\n";
            echo "   - Type: " . $column['Field'] . "\n";
            echo "   - Null: " . $column['Null'] . "\n";
            echo "   - Key: " . $column['Key'] . "\n";
            echo "   - Default: " . $column['Default'] . "\n";
            break;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database migration completed successfully',
        'date_column_nullable' => true
    ]);
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
