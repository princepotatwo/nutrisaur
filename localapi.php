<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = "localhost";
$dbname = "nutrisaur_db";
$dbUsername = "root";
$dbPassword = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $dbUsername, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Test endpoint
if (isset($_GET['test'])) {
    echo json_encode([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'method' => $_SERVER['REQUEST_METHOD'],
        'test_param' => $_GET['test']
    ]);
    exit;
}

// Check for specific endpoint types - support both 'type' and 'endpoint' parameters for compatibility
$endpoint = $_GET['endpoint'] ?? $_GET['type'] ?? 'dashboard';

// Handle POST requests with actions (for Android app compatibility)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $postData = json_decode($input, true);
    
    if ($postData && isset($postData['action'])) {
        $action = $postData['action'];
        
        if ($action === 'save_screening') {
            try {
                // Extract screening data
                $userEmail = $postData['user_email'] ?? $postData['email'] ?? '';
                $screeningData = $postData['screening_data'] ?? [];
                $riskScore = $postData['risk_score'] ?? 0;
                
                if (!$userEmail) {
                    echo json_encode(['error' => 'User email is required']);
                    exit;
                }
                
                // Check if user exists, if not create basic record
                $stmt = $conn->prepare("SELECT id FROM user_preferences WHERE user_email = ?");
                $stmt->execute([$userEmail]);
                $existingUser = $stmt->fetch();
                
                if ($existingUser) {
                    // Update existing user
                    $stmt = $conn->prepare("
                        UPDATE user_preferences SET 
                            screening_answers = ?,
                            risk_score = ?,
                            updated_at = NOW()
                        WHERE user_email = ?
                    ");
                    $stmt->execute([json_encode($screeningData), $riskScore, $userEmail]);
                } else {
                    // Create new user record
                    $stmt = $conn->prepare("
                        INSERT INTO user_preferences (
                            user_email, screening_answers, risk_score, created_at, updated_at
                        ) VALUES (?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([$userEmail, json_encode($screeningData), $riskScore]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Screening data saved successfully',
                    'user_email' => $userEmail,
                    'risk_score' => $riskScore
                ]);
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        if ($action === 'get_screening_data') {
            try {
                $userEmail = $postData['user_email'] ?? $postData['email'] ?? '';
                
                if (!$userEmail) {
                    echo json_encode(['error' => 'User email is required']);
                    exit;
                }
                
                $stmt = $conn->prepare("
                    SELECT 
                        screening_answers, 
                        risk_score, 
                        created_at, 
                        updated_at,
                        gender,
                        barangay,
                        income,
                        weight,
                        height,
                        bmi,
                        muac,
                        name,
                        birthday,
                        allergies,
                        diet_prefs,
                        avoid_foods
                    FROM user_preferences 
                    WHERE user_email = ?
                ");
                $stmt->execute([$userEmail]);
                $screeningData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($screeningData) {
                    // Parse the screening answers JSON - handle double-escaped JSON
                    $screeningAnswersRaw = $screeningData['screening_answers'];
                    
                    // First, try to decode normally
                    $screeningAnswers = json_decode($screeningAnswersRaw, true);
                    
                    // If first decode returns a string (not an array), decode it again
                    if (is_string($screeningAnswers)) {
                        $screeningAnswers = json_decode($screeningAnswers, true);
                    }
                    
                    // Ensure screeningAnswers is an array
                    if (!is_array($screeningAnswers)) {
                        $screeningAnswers = [];
                    }
                    
                    // Create a comprehensive user data object that matches what the Android app expects
                    $userData = array_merge($screeningAnswers, [
                        'user_email' => $userEmail,
                        'risk_score' => $screeningData['risk_score'],
                        'created_at' => $screeningData['created_at'],
                        'updated_at' => $screeningData['updated_at'],
                        'gender' => $screeningData['gender'],
                        'barangay' => $screeningData['barangay'],
                        'income' => $screeningData['income'],
                        'weight' => $screeningData['weight'],
                        'height' => $screeningData['height'],
                        'bmi' => $screeningData['bmi'],
                        'muac' => $screeningData['muac'],
                        'name' => $screeningData['name'],
                        'birthday' => $screeningData['birthday'],
                        'allergies' => $screeningData['allergies'],
                        'diet_prefs' => $screeningData['diet_prefs'],
                        'avoid_foods' => $screeningData['avoid_foods']
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $userData
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No screening data found for this user'
                    ]);
                }
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        if ($action === 'update_fcm_token') {
            try {
                $userEmail = $postData['email'] ?? '';
                $fcmToken = $postData['fcm_token'] ?? '';
                
                if (!$userEmail || !$fcmToken) {
                    echo json_encode(['error' => 'Email and FCM token are required']);
                    exit;
                }
                
                // Use the existing user_fcm_tokens table instead of user_preferences
                // Check if token already exists
                $stmt = $conn->prepare("SELECT id FROM user_fcm_tokens WHERE fcm_token = ?");
                $stmt->execute([$fcmToken]);
                $existingToken = $stmt->fetch();
                
                if ($existingToken) {
                    // Update existing token with new user email
                    $stmt = $conn->prepare("
                        UPDATE user_fcm_tokens SET 
                            user_email = ?,
                            updated_at = NOW()
                        WHERE fcm_token = ?
                    ");
                    $stmt->execute([$userEmail, $fcmToken]);
                } else {
                    // Insert new token
                    $stmt = $conn->prepare("
                        INSERT INTO user_fcm_tokens (
                            user_email, fcm_token, created_at, updated_at
                        ) VALUES (?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([$userEmail, $fcmToken]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'FCM token updated successfully',
                    'user_email' => $userEmail
                ]);
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        if ($action === 'get_user_data') {
            try {
                $userEmail = $postData['user_email'] ?? $postData['email'] ?? '';
                
                if (!$userEmail) {
                    echo json_encode(['error' => 'User email is required']);
                    exit;
                }
                
                $stmt = $conn->prepare("
                    SELECT 
                        screening_answers, 
                        risk_score, 
                        created_at, 
                        updated_at,
                        gender,
                        barangay,
                        income,
                        weight,
                        height,
                        bmi,
                        muac,
                        name,
                        birthday,
                        allergies,
                        diet_prefs,
                        avoid_foods
                    FROM user_preferences 
                    WHERE user_email = ?
                ");
                $stmt->execute([$userEmail]);
                $screeningData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($screeningData) {
                    // Parse the screening answers JSON - handle double-encoded JSON
                    $screeningAnswersRaw = $screeningData['screening_answers'];
                    
                    // First, try to decode normally
                    $screeningAnswers = json_decode($screeningAnswersRaw, true);
                    
                    // If first decode returns a string (not an array), decode it again
                    if (is_string($screeningAnswers)) {
                        $screeningAnswers = json_decode($screeningAnswers, true);
                    }
                    
                    // Ensure screeningAnswers is an array
                    if (!is_array($screeningAnswers)) {
                        $screeningAnswers = [];
                    }
                    
                    // Create a comprehensive user data object that matches what the Android app expects
                    $userData = array_merge($screeningAnswers, [
                        'user_email' => $userEmail,
                        'risk_score' => $screeningData['risk_score'],
                        'created_at' => $screeningData['created_at'],
                        'updated_at' => $screeningData['updated_at'],
                        'gender' => $screeningData['gender'],
                        'barangay' => $screeningData['barangay'],
                        'income' => $screeningData['income'],
                        'weight' => $screeningData['weight'],
                        'height' => $screeningData['height'],
                        'bmi' => $screeningData['bmi'],
                        'muac' => $screeningData['muac'],
                        'name' => $screeningData['name'],
                        'birthday' => $screeningData['birthday'],
                        'allergies' => $screeningData['allergies'],
                        'diet_prefs' => $screeningData['diet_prefs'],
                        'avoid_foods' => $screeningData['avoid_foods']
                    ]);
                    
                    echo json_encode([
                        'success' => true,
                        'user_data' => $userData
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'No user data found for this user'
                    ]);
                }
                
            } catch(PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        }
        
        // Unknown action
        echo json_encode(['error' => 'Unknown action: ' . $action]);
        exit;
    }
}

// Handle specific dashboard endpoints
if ($endpoint === 'community_metrics') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        // Get community metrics based on barangay filter
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                // Municipality level - get all barangays in that municipality
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE up.barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                // Individual barangay
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                COUNT(*) as total_screened,
                AVG(up.risk_score) as avg_risk_score,
                SUM(CASE WHEN up.risk_score >= 30 THEN 1 ELSE 0 END) as high_risk_cases,
                SUM(CASE WHEN up.risk_score >= 50 THEN 1 ELSE 0 END) as sam_cases
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.risk_score IS NOT NULL";
        } else {
            $sql .= " WHERE up.risk_score IS NOT NULL";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $metrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate changes (placeholder for now)
        $screenedChange = '+5';
        $riskChange = '+2.1';
        $samChange = 'No change';
        
        echo json_encode([
            'success' => true,
            'total_screened' => intval($metrics['total_screened']),
            'screened_change' => $screenedChange,
            'high_risk_cases' => intval($metrics['high_risk_cases']),
            'risk_change' => $riskChange,
            'sam_cases' => intval($metrics['sam_cases']),
            'sam_change' => $samChange,
            'barangay_filter' => $barangay
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'risk_distribution') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE up.barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                CASE 
                    WHEN up.risk_score < 20 THEN 'Low Risk'
                    WHEN up.risk_score < 40 THEN 'Moderate Risk'
                    WHEN up.risk_score < 60 THEN 'High Risk'
                    ELSE 'Critical Risk'
                END as risk_level,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.risk_score IS NOT NULL";
        } else {
            $sql .= " WHERE up.risk_score IS NOT NULL";
        }
        
        $sql .= "
            GROUP BY risk_level
            ORDER BY 
                CASE risk_level
                    WHEN 'Critical Risk' THEN 1
                    WHEN 'High Risk' THEN 2
                    WHEN 'Moderate Risk' THEN 3
                    WHEN 'Low Risk' THEN 4
                END
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $riskData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [];
        $colors = ['#4CAF50', '#FFC107', '#FF9800', '#F44336'];
        foreach ($riskData as $index => $item) {
            $chartData[] = [
                'label' => $item['risk_level'],
                'value' => intval($item['count']),
                'color' => $colors[$index] ?? '#999'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chartData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'whz_distribution') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE up.barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        // Calculate WHZ scores from height, weight, and age data
        $sql = "
            SELECT 
                CASE 
                    WHEN up.bmi < 16 THEN 'SAM'
                    WHEN up.bmi < 18.5 THEN 'MAM'
                    WHEN up.bmi < 25 THEN 'Normal'
                    ELSE 'Overweight'
                END as whz_category,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.bmi IS NOT NULL";
        } else {
            $sql .= " WHERE up.bmi IS NOT NULL";
        }
        
        $sql .= "
            GROUP BY whz_category
            ORDER BY 
                CASE whz_category
                    WHEN 'SAM' THEN 1
                    WHEN 'MAM' THEN 2
                    WHEN 'Normal' THEN 3
                    WHEN 'Overweight' THEN 4
                END
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $whzData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [];
        $colors = ['#F44336', '#FF9800', '#4CAF50', '#FFC107'];
        foreach ($whzData as $index => $item) {
            $chartData[] = [
                'label' => $item['whz_category'],
                'value' => intval($item['count']),
                'color' => $colors[$index] ?? '#999'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chartData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'muac_distribution') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE up.barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                CASE 
                    WHEN up.muac < 11.5 THEN 'SAM'
                    WHEN up.muac < 12.5 THEN 'MAM'
                    ELSE 'Normal'
                END as muac_category,
                COUNT(*) as count
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.muac IS NOT NULL";
        } else {
            $sql .= " WHERE up.muac IS NOT NULL";
        }
        
        $sql .= "
            GROUP BY muac_category
            ORDER BY 
                CASE muac_category
                    WHEN 'SAM' THEN 1
                    WHEN 'MAM' THEN 2
                    WHEN 'Normal' THEN 3
                END
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $muacData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [];
        $colors = ['#F44336', '#FF9800', '#4CAF50'];
        foreach ($muacData as $index => $item) {
            $chartData[] = [
                'label' => $item['muac_category'],
                'value' => intval($item['count']),
                'color' => $colors[$index] ?? '#999'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chartData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'geographic_distribution') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE up.barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                up.barangay,
                COUNT(*) as count,
                AVG(up.risk_score) as avg_risk
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.risk_score IS NOT NULL";
        } else {
            $sql .= " WHERE up.risk_score IS NOT NULL";
        }
        
        $sql .= "
            GROUP BY up.barangay
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $geoData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for chart
        $chartData = [];
        foreach ($geoData as $item) {
            $chartData[] = [
                'barangay' => $item['barangay'],
                'count' => intval($item['count']),
                'avg_risk' => round($item['avg_risk'], 1)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $chartData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'critical_alerts') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE up.barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        $sql = "
            SELECT 
                up.id,
                up.name,
                up.user_email,
                up.barangay,
                up.risk_score,
                up.bmi,
                up.muac,
                up.created_at
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND (up.risk_score >= 70 OR up.bmi < 16 OR up.muac < 11.5)";
        } else {
            $sql .= " WHERE (up.risk_score >= 70 OR up.bmi < 16 OR up.muac < 11.5)";
        }
        
        $sql .= "
            ORDER BY up.risk_score DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format data for alerts - match the dashboard format
        $alertData = [];
        foreach ($alerts as $alert) {
            // Determine the specific risk factor
            $riskFactors = [];
            if ($alert['risk_score'] >= 70) $riskFactors[] = 'High Risk Score (' . $alert['risk_score'] . ')';
            if ($alert['bmi'] < 16) $riskFactors[] = 'Low BMI (' . $alert['bmi'] . ')';
            if ($alert['muac'] < 11.5) $riskFactors[] = 'Critical MUAC (' . $alert['muac'] . 'cm)';
            
            $riskDescription = implode(', ', $riskFactors);
            
            $alertData[] = [
                'type' => 'critical',
                'message' => 'High malnutrition risk: ' . $riskDescription,
                'user' => $alert['name'] ?: $alert['user_email'] ?: 'User ' . $alert['id'],
                'user_email' => $alert['user_email'], // Add user_email for notifications
                'time' => date('M j, Y', strtotime($alert['created_at']))
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $alertData
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($endpoint === 'analysis_data') {
    try {
        $barangay = $_GET['barangay'] ?? '';
        
        $whereClause = "";
        $params = [];
        
        if ($barangay && $barangay !== '') {
            if (strpos($barangay, 'MUNICIPALITY_') === 0) {
                $municipality = str_replace('MUNICIPALITY_', '', $barangay);
                $whereClause = "WHERE up.barangay LIKE ?";
                $params = ["%$municipality%"];
            } else {
                $whereClause = "WHERE up.barangay = ?";
                $params = [$barangay];
            }
        }
        
        // Get risk analysis data
        $sql = "
            SELECT 
                COUNT(*) as total_users,
                AVG(up.risk_score) as avg_risk,
                SUM(CASE WHEN up.risk_score >= 30 THEN 1 ELSE 0 END) as at_risk_users
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.risk_score IS NOT NULL";
        } else {
            $sql .= " WHERE up.risk_score IS NOT NULL";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $riskAnalysis = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get demographics data
        $sql = "
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN up.age < 6 THEN 1 ELSE 0 END) as age_0_5,
                SUM(CASE WHEN up.age >= 6 AND up.age < 13 THEN 1 ELSE 0 END) as age_6_12,
                SUM(CASE WHEN up.age >= 13 AND up.age < 18 THEN 1 ELSE 0 END) as age_13_17,
                SUM(CASE WHEN up.age >= 18 AND up.age < 60 THEN 1 ELSE 0 END) as age_18_59,
                SUM(CASE WHEN up.age >= 60 THEN 1 ELSE 0 END) as age_60_plus
            FROM user_preferences up
        ";
        
        if ($whereClause) {
            $sql .= " " . $whereClause . " AND up.age IS NOT NULL";
        } else {
            $sql .= " WHERE up.age IS NOT NULL";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $demographics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'risk_analysis' => [
                'total_users' => intval($riskAnalysis['total_users']),
                'avg_risk' => round($riskAnalysis['avg_risk'], 1),
                'at_risk_users' => intval($riskAnalysis['at_risk_users'])
            ],
            'demographics' => [
                'total_users' => intval($demographics['total_users']),
                'age_0_5' => intval($demographics['age_0_5']),
                'age_6_12' => intval($demographics['age_6_12']),
                'age_13_17' => intval($demographics['age_13_17']),
                'age_18_59' => intval($demographics['age_18_59']),
                'age_60_plus' => intval($demographics['age_60_plus'])
            ]
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle USM endpoint for User Screening Module
if ($endpoint === 'usm') {
    try {
        // Get all users with their screening data and preferences
        // Use a more flexible query that handles missing columns gracefully
        $stmt = $conn->prepare("
            SELECT 
                up.id,
                up.name,
                up.user_email as email,
                up.birthday,
                up.gender,
                up.weight,
                up.height,
                up.bmi,
                up.muac,
                up.barangay,
                up.income,
                up.risk_score,
                up.screening_answers,
                up.allergies,
                up.diet_prefs,
                up.avoid_foods,
                up.created_at,
                up.updated_at
            FROM user_preferences up
            ORDER BY up.created_at DESC
        ");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format users data for USM
        $usersData = [];
        foreach ($users as $user) {
            // Extract additional fields from screening_answers JSON if available
            $screeningAnswers = [];
            if ($user['screening_answers']) {
                try {
                    $screeningAnswers = json_decode($user['screening_answers'], true) ?: [];
                } catch (Exception $e) {
                    $screeningAnswers = [];
                }
            }
            
            $usersData[] = [
                'id' => intval($user['id']),
                'username' => $user['name'] ?: 'User ' . $user['id'],
                'email' => $user['email'],
                'birthday' => $user['birthday'],
                'gender' => $user['gender'],
                'weight' => $user['weight'],
                'height' => $user['height'],
                'bmi' => $user['bmi'],
                'muac' => $user['muac'],
                'barangay' => $user['barangay'],
                'income' => $user['income'],
                'risk_score' => $user['risk_score'],
                'swelling' => $screeningAnswers['swelling'] ?? null,
                'weight_loss' => $screeningAnswers['weight_loss'] ?? null,
                'dietary_diversity' => $screeningAnswers['dietary_diversity'] ?? null,
                'feeding_behavior' => $screeningAnswers['feeding_behavior'] ?? null,
                'screening_answers' => $user['screening_answers'],
                'allergies' => $user['allergies'],
                'diet_prefs' => $user['diet_prefs'],
                'avoid_foods' => $user['avoid_foods'],
                'has_recent_illness' => $screeningAnswers['has_recent_illness'] ?? false,
                'has_eating_difficulty' => $screeningAnswers['has_eating_difficulty'] ?? false,
                'has_food_insecurity' => $screeningAnswers['has_food_insecurity'] ?? false,
                'has_micronutrient_deficiency' => $screeningAnswers['has_micronutrient_deficiency'] ?? false,
                'has_functional_decline' => $screeningAnswers['has_functional_decline'] ?? false,
                'created_at' => $user['created_at'],
                'updated_at' => $user['updated_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'users' => $usersData,
            'total_users' => count($usersData)
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle events endpoint for Android app - FIXED to support both 'type' and 'endpoint' parameters
if ($endpoint === 'events') {
    try {
        // Get all upcoming programs/events
        $stmt = $conn->prepare("
            SELECT 
                p.program_id,
                p.title,
                p.type,
                p.description,
                p.date_time,
                p.location,
                p.organizer,
                p.created_at
            FROM programs p
            WHERE p.date_time >= NOW()
            ORDER BY p.date_time ASC
        ");
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format events data
        $eventsData = [];
        foreach ($events as $event) {
            $eventsData[] = [
                'id' => intval($event['program_id']),
                'title' => $event['title'],
                'type' => $event['type'],
                'description' => $event['description'],
                'date_time' => $event['date_time'],
                'location' => $event['location'],
                'organizer' => $event['organizer'],
                'created_at' => $event['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'events' => $eventsData,
            'total_events' => count($eventsData)
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle dashboard endpoint for web dashboard
if ($endpoint === 'dashboard') {
    try {
        $userEmail = $_GET['user_email'] ?? '';
        
        // Get user preferences data
        $sql = "SELECT * FROM user_preferences";
        $params = [];
        
        if ($userEmail) {
            $sql .= " WHERE user_email = ?";
            $params = [$userEmail];
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate dashboard metrics
        $totalUsers = count($preferences);
        $totalScreened = $totalUsers;
        $samCases = 0;
        $meanWHZ = 0;
        $referralCases = 0;
        $averageRisk = 0;
        $totalRisk = 0;
        $usersWithRisk = 0;
        
        // Risk distribution for chart
        $riskDistribution = array_fill(0, 10, 0);
        
        foreach ($preferences as $pref) {
            if ($pref['risk_score'] !== null) {
                $totalRisk += $pref['risk_score'];
                $usersWithRisk++;
                
                // Categorize risk scores
                $riskIndex = min(floor($pref['risk_score'] / 10), 9);
                $riskDistribution[$riskIndex]++;
                
                // Count SAM cases (risk score >= 70)
                if ($pref['risk_score'] >= 70) {
                    $samCases++;
                }
                
                // Count referral cases (risk score >= 50)
                if ($pref['risk_score'] >= 50) {
                    $referralCases++;
                }
            }
            
            // Calculate mean WHZ from BMI (approximation)
            if ($pref['bmi'] !== null) {
                $meanWHZ += $pref['bmi'];
            }
        }
        
        $averageRisk = $usersWithRisk > 0 ? round($totalRisk / $usersWithRisk, 1) : 0;
        $meanWHZ = $totalUsers > 0 ? round($meanWHZ / $totalUsers, 1) : 0;
        
        // Get barangay data
        $barangayData = [];
        foreach ($preferences as $pref) {
            if ($pref['barangay']) {
                if (!isset($barangayData[$pref['barangay']])) {
                    $barangayData[$pref['barangay']] = ['total' => 0, 'sam' => 0];
                }
                $barangayData[$pref['barangay']]['total']++;
                if ($pref['risk_score'] >= 70) {
                    $barangayData[$pref['barangay']]['sam']++;
                }
            }
        }
        
        // Get critical alerts
        $criticalAlerts = [];
        foreach ($preferences as $pref) {
            if ($pref['risk_score'] >= 70 || ($pref['bmi'] && $pref['bmi'] < 16) || ($pref['muac'] && $pref['muac'] < 11.5)) {
                $criticalAlerts[] = [
                    'type' => 'critical',
                    'message' => 'High malnutrition risk detected',
                    'user' => $pref['name'] ?? $pref['user_email'],
                    'time' => date('M j, Y', strtotime($pref['created_at']))
                ];
            }
        }
        
        // Limit alerts to prevent overflow
        $criticalAlerts = array_slice($criticalAlerts, 0, 4);
        
        echo json_encode([
            'success' => true,
            'total_users' => $totalUsers,
            'total_screened' => $totalScreened,
            'sam_cases' => $samCases,
            'mean_whz' => $meanWHZ,
            'referral_cases' => $referralCases,
            'average_risk' => $averageRisk,
            'preferences' => $preferences,
            'risk_distribution' => $riskDistribution,
            'barangay_data' => $barangayData,
            'critical_alerts' => $criticalAlerts
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Default dashboard response (fallback)
echo json_encode([
    'success' => true,
    'message' => 'Endpoint not found or not specified',
    'available_endpoints' => [
        'community_metrics',
        'risk_distribution', 
        'whz_distribution',
        'muac_distribution',
        'geographic_distribution',
        'critical_alerts',
        'analysis_data',
        'events',
        'usm',
        'dashboard'
    ],
    'note' => 'Use either ?endpoint=X or ?type=X parameter'
]);
?> 