<?php
/**
 * Add emoji column to user_food_history table
 */

// Database connection
$host = 'viaduct.pro';
$port = '3306';
$dbname = 'railway';
$username = 'root';
$password = 'password';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully.\n";
    
    // Add emoji column
    $sql = "ALTER TABLE user_food_history ADD COLUMN emoji VARCHAR(10) NULL DEFAULT NULL AFTER is_mho_recommended";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo "SUCCESS: Added emoji column to user_food_history table\n";
    } else {
        echo "ERROR: Failed to add emoji column\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
