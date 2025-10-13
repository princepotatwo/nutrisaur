<?php
/**
 * Setup System Template User
 * Creates a special system user for storing MHO food templates
 */

require_once __DIR__ . '/../../config.php';

try {
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/create_system_template_user.sql');
    
    if ($sql === false) {
        throw new Exception('Could not read SQL file');
    }
    
    // Execute the SQL
    $result = $pdo->exec($sql);
    
    if ($result !== false) {
        echo "✅ System template user created successfully!\n";
        echo "Email: system@templates.local\n";
        echo "This user will store all MHO food templates.\n";
    } else {
        echo "❌ Error creating system template user.\n";
        $errorInfo = $pdo->errorInfo();
        echo "Error: " . $errorInfo[2] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
