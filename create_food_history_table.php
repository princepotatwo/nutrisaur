<?php
/**
 * Create user_food_history table
 * Run this script to create the required table for food history functionality
 */

require_once 'config.php';

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // SQL to create the table
    $sql = "CREATE TABLE IF NOT EXISTS user_food_history (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_email VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        date DATE NOT NULL,
        meal_category ENUM('Breakfast', 'Lunch', 'Dinner', 'Snacks') NOT NULL,
        food_name VARCHAR(255) NOT NULL,
        calories INT NOT NULL,
        serving_size VARCHAR(100) NOT NULL,
        protein DECIMAL(6,2) DEFAULT 0,
        carbs DECIMAL(6,2) DEFAULT 0,
        fat DECIMAL(6,2) DEFAULT 0,
        fiber DECIMAL(6,2) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_email) REFERENCES community_users(email) ON DELETE CASCADE,
        INDEX idx_user_date (user_email, date),
        INDEX idx_date (date),
        INDEX idx_calories (calories)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $pdo->exec($sql);
    
    echo "✅ user_food_history table created successfully!\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_food_history'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verification: user_food_history table exists\n";
    } else {
        echo "❌ Table verification failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
