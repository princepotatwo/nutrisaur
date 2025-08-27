<?php
/**
 * Create Missing Tables - Fix the missing user_preferences table
 */

echo "🔧 Creating Missing Tables\n";
echo "==========================\n\n";

// Database connection - Use the same working approach
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// If MYSQL_PUBLIC_URL is set (Railway sets this), parse it
if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        $mysql_user = $matches[1];
        $mysql_password = $matches[2];
        $mysql_host = $matches[3];
        $mysql_port = $matches[4];
        $mysql_database = $matches[5];
    }
}

try {
    // Create database connection
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdo = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ Database connection successful\n\n";
    
    // Check if user_preferences table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
    if ($stmt->rowCount() > 0) {
        echo "✅ user_preferences table already exists\n";
    } else {
        echo "🔧 Creating user_preferences table...\n";
        
        // Create the user_preferences table with the correct structure
        $sql = "
        CREATE TABLE user_preferences (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_email varchar(255) NOT NULL,
            age int(11) DEFAULT NULL,
            gender enum('male','female','other') DEFAULT NULL,
            barangay varchar(255) DEFAULT NULL,
            municipality varchar(255) DEFAULT NULL,
            province varchar(255) DEFAULT NULL,
            weight_kg decimal(5,2) DEFAULT NULL,
            height_cm decimal(5,2) DEFAULT NULL,
            bmi decimal(4,2) DEFAULT NULL,
            risk_score int(11) DEFAULT 0,
            malnutrition_risk enum('low','moderate','high','critical') DEFAULT 'low',
            screening_date date DEFAULT NULL,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_email (user_email),
            KEY barangay (barangay),
            KEY risk_score (risk_score),
            KEY created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        
        $pdo->exec($sql);
        echo "✅ user_preferences table created successfully\n";
        
        // Insert some sample data for testing
        echo "📊 Inserting sample data...\n";
        
        $sampleData = [
            [
                'user_email' => 'test1@example.com',
                'age' => 25,
                'gender' => 'female',
                'barangay' => 'Poblacion',
                'municipality' => 'Sample City',
                'province' => 'Sample Province',
                'weight_kg' => 55.0,
                'height_cm' => 160.0,
                'bmi' => 21.48,
                'risk_score' => 25,
                'malnutrition_risk' => 'low',
                'screening_date' => '2025-08-26'
            ],
            [
                'user_email' => 'test2@example.com',
                'age' => 35,
                'gender' => 'male',
                'barangay' => 'Cupang North',
                'municipality' => 'Sample City',
                'province' => 'Sample Province',
                'weight_kg' => 70.0,
                'height_cm' => 175.0,
                'bmi' => 22.86,
                'risk_score' => 45,
                'malnutrition_risk' => 'moderate',
                'screening_date' => '2025-08-26'
            ],
            [
                'user_email' => 'test3@example.com',
                'age' => 45,
                'gender' => 'female',
                'barangay' => 'Bangkal',
                'municipality' => 'Sample City',
                'province' => 'Sample Province',
                'weight_kg' => 48.0,
                'height_cm' => 155.0,
                'bmi' => 19.99,
                'risk_score' => 75,
                'malnutrition_risk' => 'high',
                'screening_date' => '2025-08-26'
            ]
        ];
        
        $insertStmt = $pdo->prepare("
            INSERT INTO user_preferences (
                user_email, age, gender, barangay, municipality, province,
                weight_kg, height_cm, bmi, risk_score, malnutrition_risk, screening_date
            ) VALUES (
                :user_email, :age, :gender, :barangay, :municipality, :province,
                :weight_kg, :height_cm, :bmi, :risk_score, :malnutrition_risk, :screening_date
            )
        ");
        
        foreach ($sampleData as $data) {
            $insertStmt->execute($data);
        }
        
        echo "✅ Sample data inserted successfully\n";
    }
    
    // Verify the table was created
    echo "\n🔍 VERIFYING TABLE:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_preferences'");
    if ($stmt->rowCount() > 0) {
        echo "✅ user_preferences table exists\n";
        
        // Show table structure
        $stmt2 = $pdo->query("DESCRIBE user_preferences");
        $columns = $stmt2->fetchAll();
        echo "📊 Columns: " . count($columns) . "\n";
        
        // Show row count
        $stmt3 = $pdo->query("SELECT COUNT(*) as count FROM user_preferences");
        $count = $stmt3->fetch()['count'];
        echo "📊 Rows: $count\n";
        
        // Show sample data
        $stmt4 = $pdo->query("SELECT user_email, barangay, risk_score, malnutrition_risk FROM user_preferences LIMIT 3");
        $sampleRows = $stmt4->fetchAll();
        echo "📋 Sample data:\n";
        foreach ($sampleRows as $row) {
            echo "  - {$row['user_email']} ({$row['barangay']}) - Risk: {$row['risk_score']} ({$row['malnutrition_risk']})\n";
        }
    } else {
        echo "❌ user_preferences table still missing\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Table creation complete!\n";
?>
