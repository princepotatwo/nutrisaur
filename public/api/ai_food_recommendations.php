<?php
/**
 * AI Food Recommendations API
 * Provides intelligent food recommendations based on community health data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    
    // Railway-specific PDO options for better connection stability
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 30, // Increased timeout for Railway
        PDO::ATTR_PERSISTENT => false, // Disable persistent connections for Railway
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ];
    
    $conn = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    
    // Test the connection
    $conn->query("SELECT 1");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage(),
        'details' => [
            'host' => $mysql_host,
            'port' => $mysql_port,
            'database' => $mysql_database,
            'user' => $mysql_user
        ]
    ]);
    exit;
}

// Get parameters
$barangay = $_GET['barangay'] ?? '';
$municipality = $_GET['municipality'] ?? '';

try {
    // Build query based on location filter
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($barangay)) {
        $whereClause .= " AND up.barangay = :barangay";
        $params[':barangay'] = $barangay;
    }
    
    if (!empty($municipality)) {
        $whereClause .= " AND up.municipality = :municipality";
        $params[':municipality'] = $municipality;
    }
    
    // Get community health data - using correct column names from working queries
    $query = "
        SELECT 
            up.id,
            up.age,
            up.gender,
            up.risk_score,
            up.whz_score,
            up.muac,
            up.barangay,
            up.dietary_diversity_score,
            up.swelling,
            up.weight_loss,
            up.feeding_behavior,
            up.created_at
        FROM user_preferences up
        $whereClause
        ORDER BY up.risk_score DESC, up.created_at DESC
        LIMIT 50
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        // Return sample data for demonstration
        $recommendations = generateSampleRecommendations();
    } else {
        // Generate real recommendations based on community data
        $recommendations = generateIntelligentRecommendations($users);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $recommendations,
        'total_users' => count($users),
        'message' => 'AI food recommendations generated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating recommendations',
        'error' => $e->getMessage()
    ]);
}

/**
 * Generate intelligent food recommendations based on community health data
 */
function generateIntelligentRecommendations($users) {
    $recommendations = [];
    
    // Analyze community health patterns
    $highRiskCount = 0;
    $totalRiskScore = 0;
    
    foreach ($users as $user) {
        if ($user['risk_score'] >= 50) $highRiskCount++;
        if ($user['whz_score'] < -3 && $user['whz_score'] !== null) $samCount++; // Using WHZ < -3 as SAM indicator
        if ($user['age'] < 18 && $user['age'] > 0) $childrenCount++;
        if ($user['age'] > 65) $elderlyCount++;
        if ($user['dietary_diversity_score'] < 5 && $user['dietary_diversity_score'] > 0) $lowDietaryDiversity++;
        $totalRiskScore += $user['risk_score'] ?? 0;
    }
    
    $avgRiskScore = count($users) > 0 ? $totalRiskScore / count($users) : 0;
    
    // Generate targeted recommendations
    if ($highRiskCount > 0) {
        $recommendations[] = [
            'food_emoji' => '🥬',
            'food_name' => 'High-Protein Vegetable Soup',
            'food_description' => 'Nutrient-rich soup with leafy greens and lean protein for high-risk individuals',
            'nutritional_priority' => 'High',
            'nutritional_impact_score' => 85,
            'ingredients' => 'Spinach, kale, chicken breast, carrots, onions, garlic',
            'benefits' => 'High protein, iron, vitamins A, C, K, and antioxidants',
            'ai_reasoning' => "Generated for {$highRiskCount} high-risk individuals (risk score ≥50). This soup provides essential nutrients to support recovery and improve nutritional status."
        ];
    }
    
    if ($samCount > 0) {
        $recommendations[] = [
            'food_emoji' => '🥛',
            'food_name' => 'Therapeutic Milk Formula',
            'food_description' => 'High-energy therapeutic milk for severe acute malnutrition cases (WHZ < -3)',
            'nutritional_priority' => 'Critical',
            'nutritional_impact_score' => 95,
            'ingredients' => 'Fortified milk powder, vegetable oil, sugar, vitamins, minerals',
            'benefits' => 'High energy density, complete protein, essential vitamins and minerals',
            'ai_reasoning' => "Critical intervention for {$samCount} SAM cases (WHZ < -3). Therapeutic milk provides concentrated nutrition for rapid recovery."
        ];
    }
    
    if ($childrenCount > 0) {
        $recommendations[] = [
            'food_emoji' => '🍎',
            'food_name' => 'Child-Friendly Fruit Smoothie',
            'food_description' => 'Delicious smoothie packed with essential nutrients for growing children',
            'nutritional_priority' => 'Medium',
            'nutritional_impact_score' => 75,
            'ingredients' => 'Banana, apple, yogurt, honey, chia seeds, milk',
            'benefits' => 'Calcium, protein, fiber, vitamins C and B6, natural sweetness',
            'ai_reasoning' => "Designed for {$childrenCount} children under 18. Smoothie format ensures easy consumption while providing essential nutrients for growth and development."
        ];
    }
    
    if ($lowDietaryDiversity > 0) {
        $recommendations[] = [
            'food_emoji' => '🥗',
            'food_name' => 'Diverse Vegetable Salad',
            'food_description' => 'Colorful salad with multiple vegetable types to improve dietary diversity',
            'nutritional_priority' => 'Medium',
            'nutritional_impact_score' => 70,
            'ingredients' => 'Mixed greens, tomatoes, cucumbers, bell peppers, carrots, avocado',
            'benefits' => 'Multiple vitamins, minerals, fiber, and phytonutrients',
            'ai_reasoning' => "Addresses low dietary diversity in {$lowDietaryDiversity} users. Variety ensures comprehensive nutrient intake and better health outcomes."
        ];
    }
    
    // Add general community health recommendation
    $recommendations[] = [
        'food_emoji' => '🌾',
        'food_name' => 'Whole Grain Porridge',
        'food_description' => 'Nutritious whole grain porridge for general community health improvement',
        'nutritional_priority' => 'Medium',
        'nutritional_impact_score' => 65,
        'ingredients' => 'Oats, quinoa, brown rice, nuts, dried fruits, honey',
        'benefits' => 'Complex carbohydrates, fiber, protein, B vitamins, minerals',
        'ai_reasoning' => "General community health recommendation based on average risk score of " . round($avgRiskScore, 1) . ". Whole grains provide sustained energy and essential nutrients."
    ];
    
    return $recommendations;
}

/**
 * Generate sample recommendations when no community data is available
 */
function generateSampleRecommendations() {
    return [
        [
            'food_emoji' => '🥬',
            'food_name' => 'Community Health Starter Pack',
            'food_description' => 'Basic nutrition program to kickstart community health initiatives',
            'nutritional_priority' => 'Medium',
            'nutritional_impact_score' => 70,
            'ingredients' => 'Leafy greens, lean proteins, whole grains, fruits',
            'benefits' => 'Balanced nutrition, essential vitamins, minerals, and fiber',
            'ai_reasoning' => 'Sample recommendation for new communities. Register users to get personalized recommendations based on real health data.'
        ],
        [
            'food_emoji' => '🥛',
            'food_name' => 'Nutrition Education Program',
            'food_description' => 'Educational initiative to improve community nutrition knowledge',
            'nutritional_priority' => 'High',
            'nutritional_impact_score' => 80,
            'ingredients' => 'Knowledge sharing, cooking demonstrations, health workshops',
            'benefits' => 'Improved nutrition literacy, better food choices, long-term health outcomes',
            'ai_reasoning' => 'Education is key to sustainable nutrition improvement. This program will help communities make informed food choices.'
        ]
    ];
}
?>
