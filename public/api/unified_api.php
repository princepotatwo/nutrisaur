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
    // Create database connection with Railway-optimized settings
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
    
    $pdo = new PDO($dsn, $mysql_user, $mysql_password, $pdoOptions);
    
    // Test the connection
    $pdo->query("SELECT 1");
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'details' => [
            'host' => $mysql_host,
            'port' => $mysql_port,
            'database' => $mysql_database,
            'user' => $mysql_user
        ]
    ]);
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
        
    case 'check_table_structure':
        checkTableStructure($pdo);
        break;
        
    case 'test_columns':
        testColumns($pdo);
        break;
        
    case 'height_distribution':
        getHeightDistribution($pdo);
        break;
        
    case 'weight_distribution':
        getWeightDistribution($pdo);
        break;
        
    case 'bmi_distribution':
        getBMIDistribution($pdo);
        break;
        
    case 'malnutrition_risk_distribution':
        getMalnutritionRiskDistribution($pdo);
        break;
        
    case 'income_distribution':
        getIncomeDistribution($pdo);
        break;
        
    case 'swelling_distribution':
        getSwellingDistribution($pdo);
        break;
        
    case 'weight_loss_distribution':
        getWeightLossDistribution($pdo);
        break;
        
    case 'feeding_behavior_distribution':
        getFeedingBehaviorDistribution($pdo);
        break;
        
    case 'physical_signs_distribution':
        getPhysicalSignsDistribution($pdo);
        break;
        
    case 'dietary_diversity_distribution':
        getDietaryDiversityDistribution($pdo);
        break;
        
    case 'clinical_risk_factors_distribution':
        getClinicalRiskFactorsDistribution($pdo);
        break;
        
    case 'generate_test_data':
        generateTestData($pdo);
        break;
        
    case 'add_missing_columns':
        addMissingColumns($pdo);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid endpoint']);
        break;
}

function getCommunityMetrics($pdo) {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        // Build WHERE clause for barangay filtering
        $whereClause = "";
        $params = [];
        
        if (!empty($barangay)) {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE barangay LIKE :municipality";
                $params[':municipality'] = "%$municipality%";
            } else {
                $whereClause = "WHERE barangay = :barangay";
                $params[':barangay'] = $barangay;
            }
        }
        
        // Get total screenings
        $sql = "SELECT COUNT(*) as total_screenings FROM user_preferences";
        if ($whereClause) {
            $sql .= " " . $whereClause;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $totalScreenings = $stmt->fetch()['total_screenings'];
        
        // Get risk distribution
        $riskSql = "
            SELECT 
                CASE 
                    WHEN risk_score < 30 THEN 'low'
                    WHEN risk_score < 50 THEN 'moderate'
                    WHEN risk_score < 70 THEN 'high'
                    ELSE 'critical'
                END as risk_level,
                COUNT(*) as count
            FROM user_preferences 
            WHERE risk_score IS NOT NULL
        ";
        if ($whereClause) {
            $riskSql .= " AND " . substr($whereClause, 6); // Remove "WHERE " prefix
        }
        $riskSql .= " GROUP BY risk_level";
        
        $stmt = $pdo->prepare($riskSql);
        $stmt->execute($params);
        $riskData = $stmt->fetchAll();
        
        // Convert to expected format
        $riskDistribution = ['low' => 0, 'moderate' => 0, 'high' => 0, 'critical' => 0];
        foreach ($riskData as $risk) {
            $riskDistribution[$risk['risk_level']] = $risk['count'];
        }
        
        // Get recent activity (screenings this week)
        $recentSql = "
            SELECT COUNT(*) as screenings_this_week 
            FROM user_preferences 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
        if ($whereClause) {
            $recentSql .= " AND " . substr($whereClause, 6); // Remove "WHERE " prefix
        }
        
        $stmt = $pdo->prepare($recentSql);
        $stmt->execute($params);
        $recentActivity = $stmt->fetch()['screenings_this_week'];
        
        // Get total users (if users table exists)
        $totalUsers = 0;
        try {
            $userSql = "SELECT COUNT(*) as total_users FROM users";
            $stmt = $pdo->query($userSql);
            $totalUsers = $stmt->fetch()['total_users'];
        } catch (Exception $e) {
            // Users table might not exist, use total_screenings as fallback
            $totalUsers = $totalScreenings;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_screenings' => $totalScreenings,
                'total_screened' => $totalScreenings, // Alias for dashboard compatibility
                'risk_distribution' => $riskDistribution,
                'recent_activity' => [
                    'screenings_this_week' => $recentActivity
                ],
                'total_users' => $totalUsers
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error in getCommunityMetrics: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error fetching community metrics: ' . $e->getMessage()
        ]);
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
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause .= " AND up.barangay LIKE :municipality";
                $params[':municipality'] = "%$municipality%";
            } else {
                $whereClause .= " AND up.barangay = :barangay";
                $params[':barangay'] = $barangay;
            }
        }
        
        // Get community health data using only existing columns
        $query = "
            SELECT 
                up.id,
                up.age,
                up.gender,
                up.risk_score,
                up.bmi,
                up.barangay,
                up.malnutrition_risk,
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
        error_log("Error in getAIFoodRecommendations: " . $e->getMessage());
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
    
    // Analyze community health patterns using existing columns
    $highRiskCount = 0;
    $underweightCount = 0;
    $childrenCount = 0;
    $elderlyCount = 0;
    $totalRiskScore = 0;
    
    foreach ($users as $user) {
        if ($user['risk_score'] >= 50) $highRiskCount++;
        if ($user['bmi'] < 18.5 && $user['bmi'] > 0) $underweightCount++; // Using BMI < 18.5 as underweight indicator
        if ($user['age'] < 18 && $user['age'] > 0) $childrenCount++;
        if ($user['age'] > 65) $elderlyCount++;
        $totalRiskScore += $user['risk_score'] ?? 0;
    }
    
    $totalUsers = count($users);
    $avgRiskScore = $totalUsers > 0 ? round($totalRiskScore / $totalUsers) : 0;
    
    // Generate recommendations based on analysis
    if ($underweightCount > 0) {
        $recommendations[] = [
            'food_emoji' => '🥛',
            'food_name' => 'High-Energy Nutritional Supplement',
            'food_description' => 'High-energy supplement for underweight individuals (BMI < 18.5)',
            'nutritional_priority' => 'Critical',
            'nutritional_impact_score' => 90,
            'ingredients' => 'Fortified milk powder, vegetable oil, sugar, vitamins, minerals',
            'benefits' => 'High energy density, complete protein, essential vitamins and minerals',
            'ai_reasoning' => "Critical intervention for {$underweightCount} underweight individuals (BMI < 18.5). High-energy supplements provide concentrated nutrition for weight gain and recovery."
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

function checkTableStructure($pdo) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM user_preferences");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $columns]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error checking table structure: ' . $e->getMessage()]);
    }
}

function testColumns($pdo) {
    try {
        $columnName = $_GET['column'] ?? '';
        if (empty($columnName)) {
            echo json_encode(['success' => false, 'message' => 'Column name parameter required']);
            return;
        }

        $stmt = $pdo->query("SELECT DISTINCT $columnName FROM user_preferences LIMIT 10");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error testing column: ' . $e->getMessage()]);
    }
}

function getHeightDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT height_cm, COUNT(*) as count
            FROM user_preferences 
            WHERE height_cm IS NOT NULL AND height_cm != ''
            GROUP BY height_cm
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching height distribution: ' . $e->getMessage()]);
    }
}

function getWeightDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT weight_kg, COUNT(*) as count
            FROM user_preferences 
            WHERE weight_kg IS NOT NULL AND weight_kg != ''
            GROUP BY weight_kg
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching weight distribution: ' . $e->getMessage()]);
    }
}

function getBMIDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT bmi, COUNT(*) as count
            FROM user_preferences 
            WHERE bmi IS NOT NULL AND bmi != ''
            GROUP BY bmi
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching BMI distribution: ' . $e->getMessage()]);
    }
}

function getMalnutritionRiskDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT malnutrition_risk, COUNT(*) as count
            FROM user_preferences 
            WHERE malnutrition_risk IS NOT NULL AND malnutrition_risk != ''
            GROUP BY malnutrition_risk
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching malnutrition risk distribution: ' . $e->getMessage()]);
    }
}

function getIncomeDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT income_level, COUNT(*) as count
            FROM user_preferences 
            WHERE income_level IS NOT NULL AND income_level != ''
            GROUP BY income_level
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching income distribution: ' . $e->getMessage()]);
    }
}

function getSwellingDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT swelling, COUNT(*) as count
            FROM user_preferences 
            WHERE swelling IS NOT NULL AND swelling != ''
            GROUP BY swelling
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching swelling distribution: ' . $e->getMessage()]);
    }
}

function getWeightLossDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT weight_loss, COUNT(*) as count
            FROM user_preferences 
            WHERE weight_loss IS NOT NULL AND weight_loss != ''
            GROUP BY weight_loss
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching weight loss distribution: ' . $e->getMessage()]);
    }
}

function getFeedingBehaviorDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT feeding_behavior, COUNT(*) as count
            FROM user_preferences 
            WHERE feeding_behavior IS NOT NULL AND feeding_behavior != ''
            GROUP BY feeding_behavior
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching feeding behavior distribution: ' . $e->getMessage()]);
    }
}

function getPhysicalSignsDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT physical_signs, COUNT(*) as count
            FROM user_preferences 
            WHERE physical_signs IS NOT NULL AND physical_signs != ''
            GROUP BY physical_signs
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching physical signs distribution: ' . $e->getMessage()]);
    }
}

function getDietaryDiversityDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT dietary_diversity, COUNT(*) as count
            FROM user_preferences 
            WHERE dietary_diversity IS NOT NULL AND dietary_diversity != ''
            GROUP BY dietary_diversity
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching dietary diversity distribution: ' . $e->getMessage()]);
    }
}

function getClinicalRiskFactorsDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT clinical_risk_factors, COUNT(*) as count
            FROM user_preferences 
            WHERE clinical_risk_factors IS NOT NULL AND clinical_risk_factors != ''
            GROUP BY clinical_risk_factors
            ORDER BY count DESC
        ");
        
        $data = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error fetching clinical risk factors distribution: ' . $e->getMessage()]);
    }
}

function generateTestData($pdo) {
    try {
        // Clear existing test data first
        $pdo->exec("DELETE FROM user_preferences WHERE user_email LIKE '%@test.com'");
        
        // Sample data arrays
        $firstNames = ['Anna', 'John', 'Maria', 'Carlos', 'Sarah', 'Michael', 'Isabella', 'David', 'Emma', 'James', 'Sophia', 'Robert', 'Olivia', 'William', 'Ava', 'Christopher', 'Mia', 'Daniel', 'Charlotte', 'Matthew', 'Amelia', 'Andrew', 'Harper', 'Joshua', 'Evelyn', 'Ryan', 'Abigail', 'Nathan', 'Emily', 'Tyler', 'Elizabeth', 'Alexander', 'Sofia', 'Henry', 'Avery', 'Sebastian', 'Ella', 'Jack', 'Madison', 'Owen', 'Scarlett', 'Samuel', 'Victoria', 'Dylan', 'Luna', 'Nathaniel', 'Grace', 'Isaac', 'Chloe', 'Kyle', 'Penelope'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts'];
        $barangays = ['Bangkal', 'Poblacion', 'San Antonio', 'San Isidro', 'San Jose', 'San Miguel', 'San Nicolas', 'San Pedro', 'Santa Ana', 'Santa Cruz', 'Santa Maria', 'Santo Niño', 'Santo Rosario', 'Tibag', 'Tugatog', 'Tumana', 'Vergara', 'Villa Concepcion', 'Villa Hermosa', 'Villa Rosario'];
        $municipalities = ['Makati', 'Manila', 'Quezon City', 'Caloocan', 'Pasig', 'Taguig', 'Valenzuela', 'Parañaque', 'Las Piñas', 'Muntinlupa'];
        $provinces = ['Metro Manila', 'Cavite', 'Laguna', 'Rizal', 'Batangas', 'Pampanga', 'Bulacan', 'Nueva Ecija', 'Tarlac', 'Zambales'];
        $incomeLevels = ['low', 'medium', 'high'];
        $swellings = ['yes', 'no'];
        $weightLosses = ['yes', 'no'];
        $feedingBehaviors = ['normal', 'poor', 'good'];
        $allergies = ['None', 'Peanuts', 'Dairy', 'Eggs', 'Shellfish', 'Wheat', 'Soy'];
        $dietPrefs = ['Regular', 'Vegetarian', 'Vegan', 'Low-sodium', 'Low-fat', 'Gluten-free'];
        
        $inserted = 0;
        for ($i = 1; $i <= 50; $i++) {
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
            
            // Generate MUAC (Mid-Upper Arm Circumference) - realistic values
            $muac = round(rand(80, 350) / 10, 1); // 8.0 to 35.0 cm
            
            // Generate WHZ score (Weight-for-Height Z-score) - realistic values
            $whz_score = round(rand(-300, 200) / 100, 2); // -3.00 to 2.00
            
            // Generate risk score based on BMI, age, and other factors
            $risk_score = 0;
            if ($bmi < 18.5) $risk_score += 30; // Underweight
            if ($bmi > 25) $risk_score += 25; // Overweight
            if ($bmi > 30) $risk_score += 35; // Obese
            if ($age < 5) $risk_score += 20; // Young children
            if ($age > 65) $risk_score += 15; // Elderly
            if ($muac < 11.5) $risk_score += 25; // Low MUAC
            if ($whz_score < -2) $risk_score += 20; // Low WHZ
            
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
            
            // Generate screening answers JSON
            $screeningAnswers = [
                'swelling' => $swellings[array_rand($swellings)],
                'weight_loss' => $weightLosses[array_rand($weightLosses)],
                'feeding_behavior' => $feedingBehaviors[array_rand($feedingBehaviors)],
                'dietary_diversity' => rand(1, 8),
                'physical_signs' => rand(0, 1) ? 'Thin appearance' : 'Normal',
                'clinical_risk_factors' => rand(0, 1) ? 'Recent illness' : 'None'
            ];
            
            $sql = "INSERT INTO user_preferences (
                user_email, name, age, gender, barangay, municipality, province, 
                weight_kg, height_cm, bmi, muac, whz_score, risk_score, malnutrition_risk,
                income_level, swelling, weight_loss, feeding_behavior, dietary_diversity,
                physical_signs, clinical_risk_factors, screening_answers, allergies,
                diet_prefs, avoid_foods, screening_date, created_at, updated_at
            ) VALUES (
                :user_email, :name, :age, :gender, :barangay, :municipality, :province,
                :weight_kg, :height_cm, :bmi, :muac, :whz_score, :risk_score, :malnutrition_risk,
                :income_level, :swelling, :weight_loss, :feeding_behavior, :dietary_diversity,
                :physical_signs, :clinical_risk_factors, :screening_answers, :allergies,
                :diet_prefs, :avoid_foods, :screening_date, :created_at, :updated_at
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_email' => strtolower($firstName . $lastName . $i . '@test.com'),
                ':name' => $firstName . ' ' . $lastName,
                ':age' => $age,
                ':gender' => rand(0, 1) ? 'male' : 'female',
                ':barangay' => $barangay,
                ':municipality' => $municipality,
                ':province' => $province,
                ':weight_kg' => $weight_kg,
                ':height_cm' => $height_cm,
                ':bmi' => $bmi,
                ':muac' => $muac,
                ':whz_score' => $whz_score,
                ':risk_score' => $risk_score,
                ':malnutrition_risk' => $malnutrition_risk,
                ':income_level' => $incomeLevels[array_rand($incomeLevels)],
                ':swelling' => $swellings[array_rand($swellings)],
                ':weight_loss' => $weightLosses[array_rand($weightLosses)],
                ':feeding_behavior' => $feedingBehaviors[array_rand($feedingBehaviors)],
                ':dietary_diversity' => rand(1, 8),
                ':physical_signs' => rand(0, 1) ? 'Thin appearance' : 'Normal',
                ':clinical_risk_factors' => rand(0, 1) ? 'Recent illness' : 'None',
                ':screening_answers' => json_encode($screeningAnswers),
                ':allergies' => $allergies[array_rand($allergies)],
                ':diet_prefs' => $dietPrefs[array_rand($dietPrefs)],
                ':avoid_foods' => rand(0, 1) ? 'Spicy foods' : 'None',
                ':screening_date' => date('Y-m-d', strtotime('-' . rand(0, 180) . ' days')),
                ':created_at' => $created_at,
                ':updated_at' => $updated_at
            ]);
            
            $inserted++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully generated $inserted test users with comprehensive data",
            'users_created' => $inserted
        ]);
        
    } catch (Exception $e) {
        error_log("Error generating test data: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error generating test data: ' . $e->getMessage()
        ]);
    }
}

function addMissingColumns($pdo) {
    try {
        // List of columns to add based on local API requirements
        $columnsToAdd = [
            'name' => 'VARCHAR(255) NULL',
            'birthday' => 'DATE NULL',
            'income' => 'VARCHAR(100) NULL',
            'muac' => 'DECIMAL(4,2) NULL',
            'screening_answers' => 'TEXT NULL',
            'allergies' => 'TEXT NULL',
            'diet_prefs' => 'TEXT NULL',
            'avoid_foods' => 'TEXT NULL',
            'swelling' => 'ENUM("yes", "no") NULL',
            'weight_loss' => 'ENUM("yes", "no") NULL',
            'feeding_behavior' => 'ENUM("normal", "poor", "good") NULL',
            'physical_signs' => 'TEXT NULL',
            'dietary_diversity' => 'INT NULL',
            'clinical_risk_factors' => 'TEXT NULL',
            'whz_score' => 'DECIMAL(4,2) NULL',
            'income_level' => 'ENUM("low", "medium", "high") NULL'
        ];
        
        $addedColumns = [];
        $existingColumns = [];
        
        // Check which columns already exist
        $stmt = $pdo->query("SHOW COLUMNS FROM user_preferences");
        $existingColumnsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $existingColumnNames = array_column($existingColumnsData, 'Field');
        
        foreach ($columnsToAdd as $columnName => $columnDefinition) {
            if (!in_array($columnName, $existingColumnNames)) {
                try {
                    $sql = "ALTER TABLE user_preferences ADD COLUMN $columnName $columnDefinition";
                    $pdo->exec($sql);
                    $addedColumns[] = $columnName;
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Error adding column $columnName: " . $e->getMessage()
                    ]);
                    exit;
                }
            } else {
                $existingColumns[] = $columnName;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Database schema updated successfully',
            'added_columns' => $addedColumns,
            'existing_columns' => $existingColumns,
            'total_columns_added' => count($addedColumns)
        ]);
        
    } catch (Exception $e) {
        error_log("Error adding missing columns: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error updating database schema: ' . $e->getMessage()
        ]);
    }
}
?>
