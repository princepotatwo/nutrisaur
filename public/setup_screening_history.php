<?php
/**
 * Setup Screening History Table
 * This script creates the screening_history table for progress tracking
 */

require_once 'config.php';

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/api/create_screening_history_table.sql');
    
    if (!$sql) {
        throw new Exception('Could not read SQL file');
    }
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "✅ screening_history table created successfully!\n";
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'screening_history'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verification: screening_history table exists\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE screening_history");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Table structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    } else {
        echo "❌ Table verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
