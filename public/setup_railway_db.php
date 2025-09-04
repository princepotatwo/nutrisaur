<?php
require_once __DIR__ . "/../config.php";

echo "=== Railway Database Setup ===\n\n";

// Check if database environment variables are set
$envVars = [
    'MYSQL_HOST', 'MYSQL_PORT', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD'
];

$missingVars = [];
foreach ($envVars as $var) {
    if (!isset($_ENV[$var])) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    echo "❌ Missing database environment variables:\n";
    foreach ($missingVars as $var) {
        echo "   - $var\n";
    }
    echo "\nPlease add a MySQL database service to your Railway project first.\n";
    echo "Railway will automatically set these environment variables.\n";
    exit(1);
}

echo "✅ Database environment variables are set\n\n";

// Test connection
try {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        echo "❌ Failed to connect to database\n";
        exit(1);
    }
    echo "✅ Database connection successful\n\n";
} catch (Exception $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
    exit(1);
}

// Create tables
$tables = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )
    ",
    
    'admin' => "
        CREATE TABLE IF NOT EXISTS admin (
            admin_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )
    ",
    
    'user_preferences' => "
        CREATE TABLE IF NOT EXISTS user_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            dietary_restrictions TEXT,
            health_goals TEXT,
            activity_level VARCHAR(50),
            age INT,
            weight DECIMAL(5,2),
            height DECIMAL(5,2),
            gender VARCHAR(20),
            barangay VARCHAR(100),
            risk_score INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ",
    
    'fcm_tokens' => "
        CREATE TABLE IF NOT EXISTS fcm_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fcm_token VARCHAR(500) UNIQUE NOT NULL,
            device_name VARCHAR(100),
            user_email VARCHAR(100),
            user_barangay VARCHAR(100),
            app_version VARCHAR(20),
            platform VARCHAR(20),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ",
    
    'notification_logs' => "
        CREATE TABLE IF NOT EXISTS notification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id VARCHAR(100),
            fcm_token VARCHAR(500),
            title VARCHAR(200),
            body TEXT,
            status VARCHAR(20),
            response TEXT,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ",
    
    'ai_food_recommendations' => "
        CREATE TABLE IF NOT EXISTS ai_food_recommendations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_email VARCHAR(100) NOT NULL,
            food_name VARCHAR(200) NOT NULL,
            food_emoji VARCHAR(10),
            food_description TEXT,
            ai_reasoning TEXT,
            nutritional_priority VARCHAR(50),
            ingredients TEXT,
            benefits TEXT,
            nutritional_impact_score INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    "
];

echo "Creating database tables...\n\n";

foreach ($tables as $tableName => $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ Created table: $tableName\n";
    } catch (Exception $e) {
        echo "❌ Error creating table $tableName: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Insert default admin user
try {
    $adminEmail = 'admin@nutrisaur.com';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE email = ?");
    $stmt->execute([$adminEmail]);
    
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO admin (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $adminEmail, $adminPassword, 'admin']);
        echo "✅ Created default admin user (admin@nutrisaur.com / admin123)\n";
    } else {
        echo "ℹ️  Default admin user already exists\n";
    }
} catch (Exception $e) {
    echo "❌ Error creating admin user: " . $e->getMessage() . "\n";
}

echo "\n";

// Verify tables
echo "Verifying tables...\n";
$stmt = $pdo->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach (array_keys($tables) as $tableName) {
    if (in_array($tableName, $existingTables)) {
        echo "✅ Table exists: $tableName\n";
    } else {
        echo "❌ Table missing: $tableName\n";
    }
}

echo "\n=== Database Setup Complete ===\n";
echo "Your Railway database is now ready!\n";
echo "The dashboard should now display data properly.\n";
?>
