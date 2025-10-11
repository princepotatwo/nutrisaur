<?php
/**
 * Run database migration to allow NULL values in date column
 * This is needed for MHO recommended foods functionality
 */

require_once '../../config.php';

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    echo "🔄 Starting database migration...\n";
    
    // Read the migration SQL
    $migrationSql = file_get_contents('migrate_date_column.sql');
    
    if (!$migrationSql) {
        throw new Exception('Could not read migration file');
    }
    
    // Execute the migration
    $pdo->exec($migrationSql);
    
    echo "✅ Migration completed successfully!\n";
    echo "✅ Date column now allows NULL values for MHO recommended foods\n";
    
    // Verify the change
    $stmt = $pdo->query("DESCRIBE user_food_history");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'date') {
            echo "📋 Date column info:\n";
            echo "   - Field: " . $column['Field'] . "\n";
            echo "   - Type: " . $column['Type'] . "\n";
            echo "   - Null: " . $column['Null'] . "\n";
            echo "   - Key: " . $column['Key'] . "\n";
            echo "   - Default: " . $column['Default'] . "\n";
            break;
        }
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
?>
