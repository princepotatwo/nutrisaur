<?php
/**
 * Generate Test Data Script
 * Creates 50 random users with realistic data for testing
 */

header('Content-Type: application/json');

// Database connection
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
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $pdo = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Sample data arrays
$firstNames = ['Anna', 'John', 'Maria', 'Carlos', 'Sarah', 'Michael', 'Isabella', 'David', 'Emma', 'James', 'Sophia', 'Robert', 'Olivia', 'William', 'Ava', 'Christopher', 'Mia', 'Daniel', 'Charlotte', 'Matthew', 'Amelia', 'Andrew', 'Harper', 'Joshua', 'Evelyn', 'Ryan', 'Abigail', 'Nathan', 'Emily', 'Tyler', 'Elizabeth', 'Alexander', 'Sofia', 'Henry', 'Avery', 'Sebastian', 'Ella', 'Jack', 'Madison', 'Owen', 'Scarlett', 'Samuel', 'Victoria', 'Dylan', 'Luna', 'Nathaniel', 'Grace', 'Isaac', 'Chloe', 'Kyle', 'Penelope'];
$lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts'];
$barangays = ['Bangkal', 'Poblacion', 'San Antonio', 'San Isidro', 'San Jose', 'San Miguel', 'San Nicolas', 'San Pedro', 'Santa Ana', 'Santa Cruz', 'Santa Maria', 'Santo Niño', 'Santo Rosario', 'Tibag', 'Tugatog', 'Tumana', 'Vergara', 'Villa Concepcion', 'Villa Hermosa', 'Villa Rosario'];
$municipalities = ['Makati', 'Manila', 'Quezon City', 'Caloocan', 'Pasig', 'Taguig', 'Valenzuela', 'Parañaque', 'Las Piñas', 'Muntinlupa'];
$provinces = ['Metro Manila', 'Cavite', 'Laguna', 'Rizal', 'Batangas', 'Pampanga', 'Bulacan', 'Nueva Ecija', 'Tarlac', 'Zambales'];

function generateRandomUser($index) {
    global $firstNames, $lastNames, $barangays, $municipalities, $provinces;
    
    $firstName = $firstNames[array_rand($firstNames)];
    $lastName = $lastNames[array_rand($lastNames)];
    $barangay = $barangays[array_rand($barangays)];
    $municipality = $municipalities[array_rand($municipalities)];
    $province = $provinces[array_rand($provinces)];
    
    // Generate realistic age (1-85 years)
    $age = rand(1, 85);
    
    // Generate realistic height (50cm for babies to 200cm for adults)
    if ($age < 18) {
        $height_cm = rand(50, 180);
    } else {
        $height_cm = rand(140, 200);
    }
    
    // Generate realistic weight based on age and height
    if ($age < 18) {
        $weight_kg = rand(3, 80);
    } else {
        // Adult weight based on height
        $bmi = rand(18, 35);
        $weight_kg = round(($height_cm / 100) * ($height_cm / 100) * $bmi, 2);
    }
    
    // Calculate BMI
    $bmi = round($weight_kg / (($height_cm / 100) * ($height_cm / 100)), 2);
    
    // Generate risk score based on BMI and age
    $risk_score = 0;
    if ($bmi < 18.5) $risk_score += 30; // Underweight
    if ($bmi > 25) $risk_score += 25; // Overweight
    if ($bmi > 30) $risk_score += 35; // Obese
    if ($age < 5) $risk_score += 20; // Young children
    if ($age > 65) $risk_score += 15; // Elderly
    
    // Cap risk score at 100
    $risk_score = min($risk_score, 100);
    
    // Determine malnutrition risk based on risk score
    if ($risk_score >= 80) $malnutrition_risk = 'critical';
    elseif ($risk_score >= 60) $malnutrition_risk = 'high';
    elseif ($risk_score >= 40) $malnutrition_risk = 'moderate';
    else $malnutrition_risk = 'low';
    
    // Generate random dates within last 6 months
    $created_at = date('Y-m-d H:i:s', strtotime('-' . rand(0, 180) . ' days'));
    $updated_at = date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'));
    
    return [
        'user_email' => strtolower($firstName . $lastName . $index . '@test.com'),
        'age' => $age,
        'gender' => rand(0, 1) ? 'male' : 'female',
        'barangay' => $barangay,
        'municipality' => $municipality,
        'province' => $province,
        'weight_kg' => $weight_kg,
        'height_cm' => $height_cm,
        'bmi' => $bmi,
        'risk_score' => $risk_score,
        'malnutrition_risk' => $malnutrition_risk,
        'screening_date' => date('Y-m-d', strtotime('-' . rand(0, 180) . ' days')),
        'created_at' => $created_at,
        'updated_at' => $updated_at
    ];
}

try {
    // Clear existing test data
    $pdo->exec("DELETE FROM user_preferences WHERE user_email LIKE '%@test.com'");
    
    // Generate and insert 50 random users
    $inserted = 0;
    for ($i = 1; $i <= 50; $i++) {
        $user = generateRandomUser($i);
        
        $sql = "INSERT INTO user_preferences (
            user_email, age, gender, barangay, municipality, province, 
            weight_kg, height_cm, bmi, risk_score, malnutrition_risk, 
            screening_date, created_at, updated_at
        ) VALUES (
            :user_email, :age, :gender, :barangay, :municipality, :province,
            :weight_kg, :height_cm, :bmi, :risk_score, :malnutrition_risk,
            :screening_date, :created_at, :updated_at
        )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($user);
        $inserted++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully generated $inserted test users",
        'users_created' => $inserted,
        'sample_user' => generateRandomUser(999) // Show sample data structure
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating test data: ' . $e->getMessage()
    ]);
}
?>
