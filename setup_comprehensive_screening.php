<?php
// Setup script for comprehensive screening system
echo "Setting up comprehensive screening system...\n";

// Database configuration
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

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
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    echo "Database connection successful.\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Create screening_assessments table
$create_table_sql = "
CREATE TABLE IF NOT EXISTS `screening_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `municipality` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `age_months` int(11) DEFAULT NULL,
  `sex` varchar(10) NOT NULL,
  `pregnant` varchar(20) DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL,
  `height` decimal(5,2) NOT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `meal_recall` text DEFAULT NULL,
  `family_history` json DEFAULT NULL,
  `lifestyle` varchar(50) NOT NULL,
  `lifestyle_other` varchar(255) DEFAULT NULL,
  `immunization` json DEFAULT NULL,
  `risk_score` int(11) DEFAULT 0,
  `assessment_summary` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_municipality` (`municipality`),
  KEY `idx_barangay` (`barangay`),
  KEY `idx_age` (`age`),
  KEY `idx_sex` (`sex`),
  KEY `idx_bmi` (`bmi`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_risk_score` (`risk_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $conn->exec($create_table_sql);
    echo "✓ screening_assessments table created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating screening_assessments table: " . $e->getMessage() . "\n";
}

// Create additional indexes for better performance
$indexes = [
    "CREATE INDEX `idx_screening_user_date` ON `screening_assessments` (`user_id`, `created_at`)",
    "CREATE INDEX `idx_screening_location` ON `screening_assessments` (`municipality`, `barangay`)",
    "CREATE INDEX `idx_screening_demographics` ON `screening_assessments` (`age`, `sex`, `bmi`)"
];

foreach ($indexes as $index_sql) {
    try {
        $conn->exec($index_sql);
        echo "✓ Index created successfully.\n";
    } catch (PDOException $e) {
        // Index might already exist, that's okay
        echo "Note: Index creation skipped (may already exist).\n";
    }
}

// Add table comment
try {
    $conn->exec("ALTER TABLE `screening_assessments` COMMENT = 'Comprehensive nutrition screening assessments with all required fields from DOH guidelines'");
    echo "✓ Table comment added.\n";
} catch (PDOException $e) {
    echo "Note: Could not add table comment.\n";
}

// Verify table structure
try {
    $stmt = $conn->query("DESCRIBE screening_assessments");
    $columns = $stmt->fetchAll();
    
    echo "\nTable structure verification:\n";
    echo "Found " . count($columns) . " columns in screening_assessments table:\n";
    
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
} catch (PDOException $e) {
    echo "Error verifying table structure: " . $e->getMessage() . "\n";
}

// Test insert (optional)
$test_data = [
    'user_id' => 1,
    'municipality' => 'CITY OF BALANGA (Capital)',
    'barangay' => 'Poblacion',
    'age' => 25,
    'age_months' => null,
    'sex' => 'Female',
    'pregnant' => 'No',
    'weight' => 55.5,
    'height' => 160.0,
    'bmi' => 21.68,
    'meal_recall' => 'Breakfast: rice, egg, coffee. Lunch: chicken, vegetables, rice. Dinner: fish, soup.',
    'family_history' => json_encode(['Diabetes', 'Hypertension']),
    'lifestyle' => 'Active',
    'lifestyle_other' => null,
    'immunization' => null,
    'risk_score' => 15,
    'assessment_summary' => 'Normal BMI with balanced diet. Risk factors: family history of diabetes and hypertension.',
    'recommendations' => 'Continue balanced diet. Monitor blood sugar and blood pressure regularly. Regular exercise recommended.',
    'created_at' => date('Y-m-d H:i:s')
];

try {
    $stmt = $conn->prepare("INSERT INTO screening_assessments (
        user_id, municipality, barangay, age, age_months, sex, pregnant, 
        weight, height, bmi, meal_recall, family_history, lifestyle, 
        lifestyle_other, immunization, risk_score, assessment_summary, 
        recommendations, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $test_data['user_id'],
        $test_data['municipality'],
        $test_data['barangay'],
        $test_data['age'],
        $test_data['age_months'],
        $test_data['sex'],
        $test_data['pregnant'],
        $test_data['weight'],
        $test_data['height'],
        $test_data['bmi'],
        $test_data['meal_recall'],
        $test_data['family_history'],
        $test_data['lifestyle'],
        $test_data['lifestyle_other'],
        $test_data['immunization'],
        $test_data['risk_score'],
        $test_data['assessment_summary'],
        $test_data['recommendations'],
        $test_data['created_at']
    ]);
    
    $test_id = $conn->lastInsertId();
    echo "✓ Test data inserted successfully (ID: $test_id).\n";
    
    // Clean up test data
    $conn->exec("DELETE FROM screening_assessments WHERE id = $test_id");
    echo "✓ Test data cleaned up.\n";
    
} catch (PDOException $e) {
    echo "Error with test data: " . $e->getMessage() . "\n";
}

echo "\n🎉 Comprehensive screening system setup completed successfully!\n";
echo "\nNext steps:\n";
echo "1. The web screening page is available at: /screening\n";
echo "2. The API endpoint is available at: /api/comprehensive_screening.php\n";
echo "3. Update your Android app to use the new ComprehensiveScreeningActivity\n";
echo "4. Test the system with real data\n";
?>
