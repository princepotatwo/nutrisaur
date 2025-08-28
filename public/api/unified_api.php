<?php
/**
 * Unified API for Nutrisaur Dashboard
 * Handles all dashboard data requests
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
// Temporarily disabled for testing
/*
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
*/

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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get the endpoint from query parameters
$endpoint = $_GET['endpoint'] ?? '';

// Route to appropriate handler
switch ($endpoint) {
    case 'community_metrics':
        getCommunityMetrics($pdo);
        break;
        
    case 'risk_distribution':
        getRiskDistribution($pdo);
        break;
        
    case 'screening_responses':
        getScreeningResponses($pdo);
        break;
        
    case 'geographic_distribution':
        getGeographicDistribution($pdo);
        break;
        
    case 'critical_alerts':
        getCriticalAlerts($pdo);
        break;
        
    case 'time_frame_data':
        getTimeFrameData($pdo);
        break;
        
    case 'detailed_screening_responses':
        getDetailedScreeningResponses($pdo);
        break;
        
    case 'ai_food_recommendations':
        getAIFoodRecommendations($pdo);
        break;
        
    case 'intelligent_programs':
        getAIFoodRecommendations($pdo); // Redirect to AI food recommendations
        break;
        
    case 'analysis_data':
        getAnalysisData($pdo);
        break;
        
    case 'check_user_data':
        checkUserData($pdo);
        break;
        
    case 'test_municipality':
        testMunicipality($pdo);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        break;
}

function getCommunityMetrics($pdo) {
    try {
        // Get basic counts
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
        $totalUsers = $stmt->fetch()['total_users'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_screenings FROM user_preferences");
        $totalScreenings = $stmt->fetch()['total_screenings'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as high_risk FROM user_preferences WHERE risk_score > 70");
        $highRisk = $stmt->fetch()['high_risk'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as critical FROM user_preferences WHERE risk_score > 90");
        $critical = $stmt->fetch()['critical'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_screened' => $totalScreenings,
                'high_risk_cases' => $highRisk,
                'critical_cases' => $critical,
                'total_users' => $totalUsers
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching community metrics: ' . $e->getMessage()]);
    }
}

function getRiskDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN risk_score < 30 THEN 'Low Risk'
                    WHEN risk_score < 50 THEN 'Moderate Risk'
                    WHEN risk_score < 70 THEN 'High Risk'
                    ELSE 'Critical Risk'
                END as risk_level,
                COUNT(*) as count
            FROM user_preferences 
            WHERE risk_score IS NOT NULL
            GROUP BY risk_level
            ORDER BY 
                CASE risk_level
                    WHEN 'Low Risk' THEN 1
                    WHEN 'Moderate Risk' THEN 2
                    WHEN 'High Risk' THEN 3
                    WHEN 'Critical Risk' THEN 4
                END
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching risk distribution: ' . $e->getMessage()]);
    }
}

function getScreeningResponses($pdo) {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $sql = "SELECT * FROM user_preferences";
        $params = [];
        
        if (!empty($barangay)) {
            $sql .= " WHERE barangay = :barangay";
            $params[':barangay'] = $barangay;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching screening responses: ' . $e->getMessage()]);
    }
}

function getGeographicDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT barangay, COUNT(*) as count
            FROM user_preferences 
            WHERE barangay IS NOT NULL AND barangay != ''
            GROUP BY barangay
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching geographic distribution: ' . $e->getMessage()]);
    }
}

function getCriticalAlerts($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM user_preferences 
            WHERE risk_score > 80
            ORDER BY risk_score DESC, created_at DESC
            LIMIT 10
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching critical alerts: ' . $e->getMessage()]);
    }
}

function getTimeFrameData($pdo) {
    try {
        $timeFrame = $_GET['time_frame'] ?? '1d';
        $barangay = $_GET['barangay'] ?? '';
        
        $sql = "SELECT * FROM user_preferences WHERE 1=1";
        $params = [];
        
        if (!empty($barangay)) {
            $sql .= " AND barangay = :barangay";
            $params[':barangay'] = $barangay;
        }
        
        // Add time filtering based on timeFrame
        switch ($timeFrame) {
            case '1d':
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case '1w':
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case '1m':
                $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching time frame data: ' . $e->getMessage()]);
    }
}

function getDetailedScreeningResponses($pdo) {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                $whereClause = "WHERE barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "SELECT * FROM user_preferences";
        if ($whereClause) {
            $sql .= " " . $whereClause;
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        // Process data into the format expected by the dashboard
        $processedData = [
            'age_groups' => [],
            'gender' => [],
            'income_levels' => [],
            'height' => [],
            'swelling' => [],
            'weight_loss' => [],
            'feeding_behavior' => [],
            'physical_signs' => [],
            'dietary_diversity' => [],
            'clinical_risk' => []
        ];
        
        // Process age groups
        $ageGroups = [];
        foreach ($data as $row) {
            if (isset($row['age']) && $row['age'] > 0) {
                $age = intval($row['age']);
                if ($age < 6) $group = '0-5 years';
                elseif ($age < 13) $group = '6-12 years';
                elseif ($age < 18) $group = '13-17 years';
                elseif ($age < 60) $group = '18-59 years';
                else $group = '60+ years';
                
                if (!isset($ageGroups[$group])) $ageGroups[$group] = 0;
                $ageGroups[$group]++;
            }
        }
        foreach ($ageGroups as $group => $count) {
            $processedData['age_groups'][] = ['age_group' => $group, 'count' => $count];
        }
        
        // Process gender
        $genderCounts = [];
        foreach ($data as $row) {
            if (isset($row['gender']) && $row['gender']) {
                $gender = ucfirst(strtolower($row['gender']));
                if (!isset($genderCounts[$gender])) $genderCounts[$gender] = 0;
                $genderCounts[$gender]++;
            }
        }
        foreach ($genderCounts as $gender => $count) {
            $processedData['gender'][] = ['gender' => $gender, 'count' => $count];
        }
        
        // Process other categories similarly
        $processedData['income_levels'] = [];
        $processedData['height'] = [];
        $processedData['swelling'] = [];
        $processedData['weight_loss'] = [];
        $processedData['feeding_behavior'] = [];
        $processedData['physical_signs'] = [];
        $processedData['dietary_diversity'] = [];
        $processedData['clinical_risk'] = [];
        
        echo json_encode([
            'success' => true,
            'data' => $processedData
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getAIFoodRecommendations($pdo) {
    try {
        $barangay = $_GET['barangay'] ?? '';
        $municipality = $_GET['municipality'] ?? '';
        
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
        
        // Get community health data using correct column names
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
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll();
        
        if (empty($users)) {
            // Return sample data for demonstration
            $recommendations = generateSampleRecommendations();
        } else {
            // Generate intelligent recommendations based on real data
            $recommendations = generateIntelligentRecommendations($users);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $recommendations
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error generating recommendations',
            'error' => $e->getMessage()
        ]);
    }
}

function generateIntelligentRecommendations($users) {
    $recommendations = [];
    
    // Analyze community health patterns
    $highRiskCount = 0;
    $samCount = 0;
    $childrenCount = 0;
    $elderlyCount = 0;
    $lowDietaryDiversity = 0;
    $totalRiskScore = 0;
    
    foreach ($users as $user) {
        if ($user['risk_score'] >= 50) $highRiskCount++;
        if ($user['whz_score'] < -3 && $user['whz_score'] !== null) $samCount++;
        if ($user['age'] < 18 && $user['age'] > 0) $childrenCount++;
        if ($user['age'] > 65) $elderlyCount++;
        if ($user['dietary_diversity_score'] < 5 && $user['dietary_diversity_score'] > 0) $lowDietaryDiversity++;
        $totalRiskScore += $user['risk_score'] ?? 0;
    }
    
    $totalUsers = count($users);
    $avgRiskScore = $totalUsers > 0 ? round($totalRiskScore / $totalUsers) : 0;
    
    // Generate recommendations based on analysis
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
    
    if ($highRiskCount > 0) {
        $recommendations[] = [
            'food_emoji' => '🥚',
            'food_name' => 'High-Protein Nutritional Supplement',
            'food_description' => 'Protein-rich supplement for high-risk individuals (risk score ≥ 50)',
            'nutritional_priority' => 'High',
            'nutritional_impact_score' => 85,
            'ingredients' => 'Whey protein, casein protein, essential amino acids, vitamins B12, D, iron',
            'benefits' => 'Muscle building, immune support, energy boost, weight management',
            'ai_reasoning' => "Targeted intervention for {$highRiskCount} high-risk individuals (avg risk score: {$avgRiskScore}). High-protein supplements address malnutrition and support recovery."
        ];
    }
    
    if ($lowDietaryDiversity > 0) {
        $recommendations[] = [
            'food_emoji' => '🥗',
            'food_name' => 'Diverse Food Group Program',
            'food_description' => 'Program to increase dietary diversity (currently < 5 food groups)',
            'nutritional_priority' => 'Medium',
            'nutritional_impact_score' => 75,
            'ingredients' => 'Mixed vegetables, fruits, grains, proteins, dairy alternatives',
            'benefits' => 'Improved nutrient intake, better health outcomes, reduced malnutrition risk',
            'ai_reasoning' => "Addressing low dietary diversity in {$lowDietaryDiversity} individuals. Diverse food groups ensure comprehensive nutrition and prevent deficiencies."
        ];
    }
    
    if ($childrenCount > 0) {
        $recommendations[] = [
            'food_emoji' => '🍎',
            'food_name' => 'Child Nutrition Program',
            'food_description' => 'Age-appropriate nutrition for children under 18',
            'nutritional_priority' => 'High',
            'nutritional_impact_score' => 80,
            'ingredients' => 'Fortified cereals, fruits, vegetables, lean proteins, dairy',
            'benefits' => 'Growth support, cognitive development, immune system strengthening',
            'ai_reasoning' => "Specialized nutrition for {$childrenCount} children. Early intervention prevents stunting and supports healthy development."
        ];
    }
    
    if ($elderlyCount > 0) {
        $recommendations[] = [
            'food_emoji' => '🥛',
            'food_name' => 'Senior Nutrition Support',
            'food_description' => 'Nutritional support for elderly individuals (65+)',
            'nutritional_priority' => 'Medium',
            'nutritional_impact_score' => 70,
            'ingredients' => 'Calcium-rich foods, vitamin D, omega-3, fiber, antioxidants',
            'benefits' => 'Bone health, cognitive function, immune support, digestive health',
            'ai_reasoning' => "Targeted nutrition for {$elderlyCount} elderly individuals. Addresses age-related nutritional needs and health maintenance."
        ];
    }
    
    // Default recommendation if no specific cases found
    if (empty($recommendations)) {
        $recommendations[] = [
            'food_emoji' => '🥗',
            'food_name' => 'Community Wellness Program',
            'food_description' => 'General nutrition improvement for community health',
            'nutritional_priority' => 'Medium',
            'nutritional_impact_score' => 65,
            'ingredients' => 'Balanced meals, local produce, fortified foods, clean water',
            'benefits' => 'Overall health improvement, disease prevention, community resilience',
            'ai_reasoning' => "General wellness program for {$totalUsers} community members. Promotes overall health and prevents malnutrition."
        ];
    }
    
    return $recommendations;
}

function generateSampleRecommendations() {
    return [
        [
            'food_emoji' => '🥛',
            'food_name' => 'Sample Therapeutic Milk',
            'food_description' => 'Sample high-energy therapeutic milk for demonstration',
            'nutritional_priority' => 'Critical',
            'nutritional_impact_score' => 90,
            'ingredients' => 'Fortified milk powder, vegetable oil, sugar, vitamins, minerals',
            'benefits' => 'High energy density, complete protein, essential vitamins and minerals',
            'ai_reasoning' => 'Sample recommendation for demonstration purposes.'
        ]
    ];
}

function getAnalysisData($pdo) {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                $whereClause = "WHERE barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                barangay,
                COUNT(*) as total_users,
                AVG(risk_score) as avg_risk_score,
                COUNT(CASE WHEN risk_score >= 70 THEN 1 END) as high_risk_count,
                COUNT(CASE WHEN risk_score BETWEEN 30 AND 69 THEN 1 END) as moderate_risk_count,
                COUNT(CASE WHEN risk_score < 30 THEN 1 END) as low_risk_count
            FROM user_preferences
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause;
        }
        
        $sql .= " GROUP BY barangay ORDER BY total_users DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $analysisData = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'data' => $analysisData
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function checkUserData($pdo) {
    try {
        $email = $_GET['email'] ?? '';
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email parameter required']);
            return;
        }
        
        // Check if user exists in user_preferences
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_preferences WHERE user_email = ?");
        $stmt->execute([$email]);
        $userExists = $stmt->fetch()['count'] > 0;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'email' => $email,
                'exists' => $userExists,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function testMunicipality($pdo) {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        if (empty($barangay)) {
            echo json_encode(['success' => false, 'message' => 'Barangay parameter required']);
            return;
        }
        
        $whereClause = "";
        $params = [];
        
        if (strpos($barangay, 'MUNICIPALITY_') === 0) {
            $municipality = str_replace('MUNICIPALITY_', '', $barangay);
            $whereClause = "WHERE barangay LIKE ?";
            $params = ["%$municipality%"];
        } else {
            $whereClause = "WHERE barangay = ?";
            $params = [$barangay];
        }
        
        $sql = "SELECT COUNT(*) as count FROM user_preferences " . $whereClause;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'barangay' => $barangay,
                'user_count' => $count,
                'test_result' => 'success'
            ]
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
