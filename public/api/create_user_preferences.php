<?php
// API endpoint to create missing user_preferences table
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'config.php';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if user_preferences table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'user_preferences'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo json_encode([
            'success' => true,
            'message' => 'user_preferences table already exists',
            'table_exists' => true
        ]);
    } else {
        // Create user_preferences table
        $sql = "CREATE TABLE IF NOT EXISTS `user_preferences` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_email` varchar(255) NOT NULL,
            `username` varchar(255) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `birthday` date DEFAULT NULL,
            `age` int(11) DEFAULT NULL,
            `gender` varchar(50) DEFAULT NULL,
            `height` float DEFAULT NULL,
            `weight` float DEFAULT NULL,
            `bmi` float DEFAULT NULL,
            `muac` float DEFAULT NULL,
            `swelling` varchar(10) DEFAULT NULL,
            `weight_loss` varchar(20) DEFAULT NULL,
            `dietary_diversity` int(11) DEFAULT NULL,
            `feeding_behavior` varchar(20) DEFAULT NULL,
            `physical_thin` tinyint(1) DEFAULT 0,
            `physical_shorter` tinyint(1) DEFAULT 0,
            `physical_weak` tinyint(1) DEFAULT 0,
            `physical_none` tinyint(1) DEFAULT 0,
            `physical_signs` text DEFAULT NULL,
            `has_recent_illness` tinyint(1) DEFAULT 0,
            `has_eating_difficulty` tinyint(1) DEFAULT 0,
            `has_food_insecurity` tinyint(1) DEFAULT 0,
            `has_micronutrient_deficiency` tinyint(1) DEFAULT 0,
            `has_functional_decline` tinyint(1) DEFAULT 0,
            `goal` varchar(255) DEFAULT NULL,
            `risk_score` int(11) DEFAULT NULL,
            `screening_answers` text DEFAULT NULL,
            `allergies` text DEFAULT NULL,
            `diet_prefs` text DEFAULT NULL,
            `avoid_foods` text DEFAULT NULL,
            `barangay` varchar(255) DEFAULT NULL,
            `income` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_user_email` (`user_email`),
            KEY `idx_barangay` (`barangay`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sql);
        
        // Insert sample data for testing FCM
        $insertSql = "INSERT INTO `user_preferences` (`user_email`, `username`, `barangay`, `created_at`, `updated_at`) VALUES
            ('test1@example.com', 'testuser1', 'A. Rivera (Pob.)', NOW(), NOW()),
            ('test2@example.com', 'testuser2', 'Cupang North', NOW(), NOW())";
        
        $conn->exec($insertSql);
        
        echo json_encode([
            'success' => true,
            'message' => 'user_preferences table created successfully with sample data',
            'table_exists' => false,
            'created' => true
        ]);
    }
    
    // Check FCM tokens and user preferences
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM fcm_tokens WHERE is_active = TRUE");
    $stmt->execute();
    $fcmCount = $stmt->fetchColumn();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_preferences WHERE barangay IS NOT NULL AND barangay != ''");
    $stmt->execute();
    $prefCount = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database setup completed',
        'fcm_tokens_count' => $fcmCount,
        'user_preferences_count' => $prefCount
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
