<?php
/**
 * Unified API for Nutrisaur Dashboard - Direct File
 * This file exists directly in public/ so Railway can find it
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
$type = $_GET['type'] ?? '';

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
        
            default:
            // Check if it's a type-based request
            if ($type === 'usm') {
                getUserManagementData($pdo);
                break;
            }
            
            // Check if it's a mobile app login request
            if ($type === 'mobile_login') {
                handleMobileLogin($pdo);
                break;
            }
            
            // Check if it's a mobile app signup request
            if ($type === 'mobile_signup') {
                handleMobileSignup($pdo);
                break;
            }
            
            // No valid endpoint or type found
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid endpoint or type']);
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
        
        $stmt = $pdo->query("SELECT COUNT(*) as moderate_risk FROM user_preferences WHERE risk_score BETWEEN 30 AND 70");
        $moderateRisk = $stmt->fetch()['moderate_risk'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as low_risk FROM user_preferences WHERE risk_score < 30");
        $lowRisk = $stmt->fetch()['low_risk'];
        
        // Get recent activity
        $stmt = $pdo->query("SELECT COUNT(*) as recent_screenings FROM user_preferences WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $recentScreenings = $stmt->fetch()['recent_screenings'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'total_screenings' => $totalScreenings,
                'risk_distribution' => [
                    'high' => $highRisk,
                    'moderate' => $moderateRisk,
                    'low' => $lowRisk
                ],
                'recent_activity' => [
                    'screenings_this_week' => $recentScreenings
                ]
            ]
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getRiskDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN risk_score < 30 THEN 'Low'
                    WHEN risk_score BETWEEN 30 AND 70 THEN 'Moderate'
                    ELSE 'High'
                END as risk_level,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY risk_level
            ORDER BY 
                CASE risk_level
                    WHEN 'Low' THEN 1
                    WHEN 'Moderate' THEN 2
                    ELSE 3
                END
        ");
        
        $distribution = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $distribution]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getScreeningResponses($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM user_preferences 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        
        $responses = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $responses]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getDetailedScreeningResponses($pdo) {
    try {
        // Get detailed breakdown of screening responses
        $data = [];
        
        // Age Group Distribution
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN age < 5 THEN 'Under 5'
                    WHEN age BETWEEN 5 AND 12 THEN '5-12 years'
                    WHEN age BETWEEN 13 AND 17 THEN '13-17 years'
                    WHEN age BETWEEN 18 AND 25 THEN '18-25 years'
                    WHEN age BETWEEN 26 AND 35 THEN '26-35 years'
                    WHEN age BETWEEN 36 AND 50 THEN '36-50 years'
                    ELSE 'Over 50'
                END as age_group,
                COUNT(*) as count
            FROM user_preferences 
            WHERE age IS NOT NULL
            GROUP BY age_group
            ORDER BY 
                CASE age_group
                    WHEN 'Under 5' THEN 1
                    WHEN '5-12 years' THEN 2
                    WHEN '13-17 years' THEN 3
                    WHEN '18-25 years' THEN 4
                    WHEN '26-35 years' THEN 5
                    WHEN '36-50 years' THEN 6
                    ELSE 7
                END
        ");
        $data['age_groups'] = $stmt->fetchAll();
        
        // Gender Distribution
        $stmt = $pdo->query("
            SELECT 
                COALESCE(gender, 'Not specified') as gender,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY gender
            ORDER BY count DESC
        ");
        $data['gender'] = $stmt->fetchAll();
        
        // Income Level Distribution (using risk score as proxy)
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN risk_score < 25 THEN 'Low Risk'
                    WHEN risk_score BETWEEN 25 AND 50 THEN 'Moderate Risk'
                    WHEN risk_score BETWEEN 51 AND 75 THEN 'High Risk'
                    ELSE 'Critical Risk'
                END as income_level,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY income_level
            ORDER BY 
                CASE income_level
                    WHEN 'Low Risk' THEN 1
                    WHEN 'Moderate Risk' THEN 2
                    WHEN 'High Risk' THEN 3
                    ELSE 4
                END
        ");
        $data['income_levels'] = $stmt->fetchAll();
        
        // Height Distribution
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN height_cm < 100 THEN 'Under 100cm'
                    WHEN height_cm BETWEEN 100 AND 120 THEN '100-120cm'
                    WHEN height_cm BETWEEN 121 AND 140 THEN '121-140cm'
                    WHEN height_cm BETWEEN 141 AND 160 THEN '141-160cm'
                    WHEN height_cm BETWEEN 161 AND 180 THEN '161-180cm'
                    ELSE 'Over 180cm'
                END as height_range,
                COUNT(*) as count
            FROM user_preferences 
            WHERE height_cm IS NOT NULL
            GROUP BY height_range
            ORDER BY 
                CASE height_range
                    WHEN 'Under 100cm' THEN 1
                    WHEN '100-120cm' THEN 2
                    WHEN '121-140cm' THEN 3
                    WHEN '141-160cm' THEN 4
                    WHEN '161-180cm' THEN 5
                    ELSE 6
                END
        ");
        $data['height'] = $stmt->fetchAll();
        
        // Swelling/Edema (using malnutrition risk as proxy)
        $stmt = $pdo->query("
            SELECT 
                COALESCE(malnutrition_risk, 'Not specified') as swelling_status,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY malnutrition_risk
            ORDER BY 
                CASE malnutrition_risk
                    WHEN 'low' THEN 1
                    WHEN 'moderate' THEN 2
                    WHEN 'high' THEN 3
                    WHEN 'critical' THEN 4
                    ELSE 5
                END
        ");
        $data['swelling'] = $stmt->fetchAll();
        
        // Weight Loss Status (using BMI as proxy)
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN bmi < 18.5 THEN 'Underweight'
                    WHEN bmi BETWEEN 18.5 AND 24.9 THEN 'Normal'
                    WHEN bmi BETWEEN 25.0 AND 29.9 THEN 'Overweight'
                    ELSE 'Obese'
                END as weight_loss_status,
                COUNT(*) as count
            FROM user_preferences 
            WHERE bmi IS NOT NULL
            GROUP BY weight_loss_status
            ORDER BY 
                CASE weight_loss_status
                    WHEN 'Underweight' THEN 1
                    WHEN 'Normal' THEN 2
                    WHEN 'Overweight' THEN 3
                    ELSE 4
                END
        ");
        $data['weight_loss'] = $stmt->fetchAll();
        
        // Feeding Behavior (using risk score as proxy)
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN risk_score < 30 THEN 'Good'
                    WHEN risk_score BETWEEN 30 AND 60 THEN 'Fair'
                    ELSE 'Poor'
                END as feeding_behavior,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY feeding_behavior
            ORDER BY 
                CASE feeding_behavior
                    WHEN 'Good' THEN 1
                    WHEN 'Fair' THEN 2
                    ELSE 3
                END
        ");
        $data['feeding_behavior'] = $stmt->fetchAll();
        
        // Physical Signs (using malnutrition risk as proxy)
        $stmt = $pdo->query("
            SELECT 
                COALESCE(malnutrition_risk, 'Not specified') as physical_sign,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY malnutrition_risk
            ORDER BY 
                CASE malnutrition_risk
                    WHEN 'low' THEN 1
                    WHEN 'moderate' THEN 2
                    WHEN 'high' THEN 3
                    WHEN 'critical' THEN 4
                    ELSE 5
                END
        ");
        $data['physical_signs'] = $stmt->fetchAll();
        
        // Dietary Diversity Score (using risk score as proxy)
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN risk_score < 25 THEN 'High (7-10)'
                    WHEN risk_score BETWEEN 25 AND 50 THEN 'Medium (4-6)'
                    ELSE 'Low (0-3)'
                END as dietary_score,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY dietary_score
            ORDER BY 
                CASE dietary_score
                    WHEN 'High (7-10)' THEN 1
                    WHEN 'Medium (4-6)' THEN 2
                    ELSE 3
                END
        ");
        $data['dietary_diversity'] = $stmt->fetchAll();
        
        // Clinical Risk Factors (using risk score as proxy)
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN risk_score < 25 THEN 'Low'
                    WHEN risk_score BETWEEN 25 AND 50 THEN 'Moderate'
                    WHEN risk_score BETWEEN 51 AND 75 THEN 'High'
                    ELSE 'Critical'
                END as clinical_risk,
                COUNT(*) as count
            FROM user_preferences 
            GROUP BY clinical_risk
            ORDER BY 
                CASE clinical_risk
                    WHEN 'Low' THEN 1
                    WHEN 'Moderate' THEN 2
                    WHEN 'High' THEN 3
                    ELSE 4
                END
        ");
        $data['clinical_risk'] = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getGeographicDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                COALESCE(barangay, 'Not specified') as barangay,
                COUNT(*) as count
            FROM user_preferences 
            WHERE barangay IS NOT NULL AND barangay != ''
            GROUP BY barangay 
            ORDER BY count DESC
        ");
        
        $distribution = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $distribution]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getCriticalAlerts($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                user_email as email,
                barangay,
                risk_score,
                created_at
            FROM user_preferences 
            WHERE risk_score > 70
            ORDER BY risk_score DESC
            LIMIT 10
        ");
        
        $alerts = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $alerts]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getTimeFrameData($pdo) {
    try {
        $timeFrame = $_GET['time_frame'] ?? '7d';
        $barangay = $_GET['barangay'] ?? null;
        
        $whereClause = "";
        $params = [];
        
        if ($barangay) {
            $whereClause = "WHERE u.barangay = ?";
            $params[] = $barangay;
        }
        
        switch ($timeFrame) {
            case '7d':
                $interval = 'INTERVAL 7 DAY';
                break;
            case '30d':
                $interval = 'INTERVAL 30 DAY';
                break;
            case '90d':
                $interval = 'INTERVAL 90 DAY';
                break;
            default:
                $interval = 'INTERVAL 7 DAY';
        }
        
        $sql = "
            SELECT 
                DATE(up.created_at) as date,
                COUNT(*) as count
            FROM user_preferences up
            JOIN users u ON up.user_email = u.email
            $whereClause
            AND up.created_at >= DATE_SUB(NOW(), $interval)
            GROUP BY DATE(up.created_at)
            ORDER BY date DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getAIFoodRecommendations($pdo) {
    try {
        // Get AI food recommendations from the database
        $stmt = $pdo->query("
            SELECT 
                id,
                user_email,
                food_name,
                food_emoji,
                food_description,
                ai_reasoning,
                nutritional_priority,
                ingredients,
                benefits,
                nutritional_impact_score,
                created_at
            FROM ai_food_recommendations 
            ORDER BY created_at DESC
            LIMIT 20
        ");
        
        $recommendations = $stmt->fetchAll();
        
        // If no recommendations exist, return sample data directly
        if (empty($recommendations)) {
            $recommendations = [
                [
                    'id' => 1,
                    'user_email' => 'sample@example.com',
                    'food_name' => 'Nutrient-Rich Smoothie Bowl',
                    'food_emoji' => '🥗',
                    'food_description' => 'A balanced smoothie bowl with fruits, nuts, and seeds',
                    'ai_reasoning' => 'High in vitamins, minerals, and healthy fats for overall nutrition',
                    'nutritional_priority' => 'general',
                    'ingredients' => 'Banana, berries, almond milk, chia seeds, almonds',
                    'benefits' => 'Rich in antioxidants, fiber, and essential nutrients',
                    'nutritional_impact_score' => 85,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 2,
                    'user_email' => 'sample@example.com',
                    'food_name' => 'Protein-Packed Quinoa Bowl',
                    'food_emoji' => '🍚',
                    'food_description' => 'Complete protein source with vegetables and healthy fats',
                    'ai_reasoning' => 'Quinoa provides complete protein, essential for muscle development',
                    'nutritional_priority' => 'protein',
                    'ingredients' => 'Quinoa, chickpeas, spinach, avocado, olive oil',
                    'benefits' => 'Complete protein, fiber, healthy fats, and micronutrients',
                    'nutritional_impact_score' => 90,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 3,
                    'user_email' => 'sample@example.com',
                    'food_name' => 'Iron-Rich Spinach Salad',
                    'food_emoji' => '🥬',
                    'food_description' => 'Dark leafy greens with vitamin C for iron absorption',
                    'ai_reasoning' => 'Addresses iron deficiency common in malnutrition cases',
                    'nutritional_priority' => 'iron',
                    'ingredients' => 'Spinach, oranges, walnuts, lemon juice, olive oil',
                    'benefits' => 'High iron content, vitamin C for absorption, healthy fats',
                    'nutritional_impact_score' => 88,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $recommendations]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getUserManagementData($pdo) {
    try {
        // First, let's check what columns actually exist in the user_preferences table
        $stmt = $pdo->query("DESCRIBE user_preferences");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get all users directly from user_preferences table
        // Use only columns that definitely exist
        $stmt = $pdo->query("
            SELECT 
                id,
                user_email as email,
                risk_score,
                barangay,
                created_at as user_created,
                updated_at as last_screening
            FROM user_preferences 
            WHERE user_email IS NOT NULL AND user_email != ''
            ORDER BY created_at DESC
        ");
        
        $users = $stmt->fetchAll();
        
        // Process each user to add computed fields
        foreach ($users as &$user) {
            // Set preferences_count to 1 since each row represents one screening
            $user['preferences_count'] = 1;
            
            // Use risk_score directly
            $user['max_risk_score'] = $user['risk_score'] ?? null;
            
            // Use updated_at as last screening date
            $user['last_screening'] = $user['updated_at'] ?? null;
            
            // Add user_id if not present (use id from user_preferences)
            if (!isset($user['user_id'])) {
                $user['user_id'] = $user['id'];
            }
            
            // Try to get username from screening_answers JSON if it exists
            if (isset($user['screening_answers'])) {
                $screeningData = json_decode($user['screening_answers'], true);
                if ($screeningData && isset($screeningData['username'])) {
                    $user['username'] = $screeningData['username'];
                } else {
                    $user['username'] = 'User_' . $user['id']; // Fallback username
                }
            } else {
                $user['username'] = 'User_' . $user['id']; // Fallback username
            }
        }
        
        echo json_encode(['success' => true, 'data' => $users]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleMobileLogin($pdo) {
    try {
        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        $usernameOrEmail = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        
        if (empty($usernameOrEmail) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please enter both username/email and password']);
            return;
        }
        
        // Check if input is email or username
        $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);
        
        // First check in users table
        if ($isEmail) {
            $stmt = $pdo->prepare("SELECT user_id, username, email, password FROM users WHERE email = ?");
            $stmt->execute([$usernameOrEmail]);
        } else {
            $stmt = $pdo->prepare("SELECT user_id, username, email, password FROM users WHERE username = ?");
            $stmt->execute([$usernameOrEmail]);
        }
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['password'])) {
                // Password is correct
                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful!',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'email' => $user['email']
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid password']);
            }
        } else {
            // If not found in users table, check admin table
            if ($isEmail) {
                $stmt = $pdo->prepare("SELECT admin_id, username, email, password, role FROM admin WHERE email = ?");
                $stmt->execute([$usernameOrEmail]);
            } else {
                $stmt = $pdo->prepare("SELECT admin_id, username, email, password, role FROM admin WHERE username = ?");
                $stmt->execute([$usernameOrEmail]);
            }
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch();
                
                if (password_verify($password, $admin['password'])) {
                    // Password is correct
                    echo json_encode([
                        'success' => true,
                        'message' => 'Admin login successful!',
                        'data' => [
                            'admin_id' => $admin['admin_id'],
                            'username' => $admin['username'],
                            'email' => $admin['email'],
                            'role' => $admin['role'],
                            'is_admin' => true
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid password']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function handleMobileSignup($pdo) {
    try {
        // Get POST data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }
        
        $action = $data['action'] ?? '';
        
        if ($action === 'save_screening') {
            // Handle user registration/screening data
            $email = $data['email'] ?? '';
            $username = $data['username'] ?? '';
            $screeningData = $data['screening_data'] ?? '{}';
            $riskScore = $data['risk_score'] ?? 0;
            
            if (empty($email) || empty($username)) {
                echo json_encode(['success' => false, 'message' => 'Email and username are required']);
                return;
            }
            
            // Insert into user_preferences table
            $stmt = $pdo->prepare("
                INSERT INTO user_preferences 
                (user_email, username, risk_score, screening_answers, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                username = VALUES(username),
                risk_score = VALUES(risk_score),
                screening_answers = VALUES(screening_answers),
                updated_at = NOW()
            ");
            
            $stmt->execute([$email, $username, $riskScore, $screeningData]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'User screening data saved successfully',
                'data' => [
                    'email' => $email,
                    'username' => $username,
                    'risk_score' => $riskScore
                ]
            ]);
            
        } elseif ($action === 'save_preferences') {
            // Handle food preferences
            $email = $data['email'] ?? '';
            $username = $data['username'] ?? '';
            $allergies = $data['allergies'] ?? '[]';
            $dietPrefs = $data['diet_prefs'] ?? '[]';
            $avoidFoods = $data['avoid_foods'] ?? '';
            $riskScore = $data['risk_score'] ?? 0;
            
            if (empty($email)) {
                echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
                return;
            }
            
            // Update user_preferences table with food preferences
            $stmt = $pdo->prepare("
                INSERT INTO user_preferences 
                (user_email, username, allergies, diet_prefs, avoid_foods, risk_score, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                username = VALUES(username),
                allergies = VALUES(allergies),
                diet_prefs = VALUES(diet_prefs),
                avoid_foods = VALUES(avoid_foods),
                risk_score = VALUES(risk_score),
                updated_at = NOW()
            ");
            
            $stmt->execute([$email, $username, $allergies, $dietPrefs, $avoidFoods, $riskScore]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Food preferences saved successfully',
                'data' => [
                    'email' => $email,
                    'username' => $username
                ]
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>



