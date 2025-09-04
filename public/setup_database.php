<?php
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/api/DatabaseAPI.php";

$db = DatabaseAPI::getInstance();
$pdo = $db->getPDO();

if (!$pdo) {
    echo "Database connection failed\n";
    exit;
}

echo "=== Database Setup Script ===\n\n";

// Create users table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `user_id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `last_login` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`user_id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "✅ users table created/verified\n";
} catch (Exception $e) {
    echo "❌ Error creating users table: " . $e->getMessage() . "\n";
}

// Create user_preferences table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_preferences` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "✅ user_preferences table created/verified\n";
} catch (Exception $e) {
    echo "❌ Error creating user_preferences table: " . $e->getMessage() . "\n";
}

// Create admin table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin` (
            `admin_id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `password` varchar(255) NOT NULL,
            `role` varchar(50) DEFAULT 'admin',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`admin_id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "✅ admin table created/verified\n";
} catch (Exception $e) {
    echo "❌ Error creating admin table: " . $e->getMessage() . "\n";
}

// Create fcm_tokens table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `fcm_tokens` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_email` varchar(255) NOT NULL,
            `fcm_token` text NOT NULL,
            `device_type` varchar(50) DEFAULT 'android',
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_user_email` (`user_email`),
            KEY `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "✅ fcm_tokens table created/verified\n";
} catch (Exception $e) {
    echo "❌ Error creating fcm_tokens table: " . $e->getMessage() . "\n";
}

// Create notification_logs table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notification_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `event_id` int(11) DEFAULT NULL,
            `fcm_token` text NOT NULL,
            `title` varchar(255) NOT NULL,
            `body` text NOT NULL,
            `status` varchar(50) DEFAULT 'pending',
            `sent_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_status` (`status`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "✅ notification_logs table created/verified\n";
} catch (Exception $e) {
    echo "❌ Error creating notification_logs table: " . $e->getMessage() . "\n";
}

// Insert default admin user if it doesn't exist
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE email = 'admin@nutrisaur.com'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@nutrisaur.com', $hashedPassword, 'admin']);
        echo "✅ Default admin user created (admin@nutrisaur.com / admin123)\n";
    } else {
        echo "✅ Default admin user already exists\n";
    }
} catch (Exception $e) {
    echo "❌ Error creating default admin: " . $e->getMessage() . "\n";
}

echo "\n=== Database Setup Complete ===\n";
echo "All tables have been created and verified.\n";
echo "You can now test the registration and login functionality.\n";
?>
