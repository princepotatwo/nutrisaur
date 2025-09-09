<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/DatabaseAPI.php';

// Essential nutritional assessment functions (simplified for dashboard)
function calculateAge($birthday) {
    $birthDate = new DateTime($birthday);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y + ($age->m / 12);
}

function calculateBMI($weight, $height) {
    if ($height <= 0) return 0;
    $heightInMeters = $height / 100;
    return round($weight / ($heightInMeters * $heightInMeters), 1);
}

function performNutritionalAssessment($user) {
    $age = calculateAge($user['birthday']);
    $weight = floatval($user['weight']);
    $height = floatval($user['height']);
    $muac = floatval($user['muac']);
    $isPregnant = $user['is_pregnant'] === 'Yes';
    $sex = $user['sex'];
    
    // Simple validation
    if ($age < 0 || $age > 120 || $weight <= 0 || $height <= 0 || $muac <= 0) {
        return [
            'nutritional_status' => 'Invalid Data',
            'risk_level' => 'Unknown',
            'category' => 'Error'
        ];
    }
    
    // Decision Tree
    if ($age < 18) {
        return assessChildAdolescent($age, $weight, $height, $muac, $sex);
    } elseif ($isPregnant) {
        return assessPregnantWoman($muac, $weight);
    } else {
        return assessAdultElderly($weight, $height, $muac);
    }
}

function assessChildAdolescent($age, $weight, $height, $muac, $sex) {
    $bmi = calculateBMI($weight, $height);
    
    // Simplified WHO-based assessment
    if ($age >= 0.5 && $age < 5 && $muac < 11.5) {
        return [
            'nutritional_status' => 'Severe Acute Malnutrition (SAM)',
            'risk_level' => 'High',
            'category' => 'Undernutrition'
        ];
    } elseif ($age >= 0.5 && $age < 5 && $muac >= 11.5 && $muac < 12.5) {
        return [
            'nutritional_status' => 'Moderate Acute Malnutrition (MAM)',
            'risk_level' => 'Medium',
            'category' => 'Undernutrition'
        ];
    } elseif ($age >= 0.5 && $age < 5 && $muac >= 12.5 && $muac < 13.5) {
        return [
            'nutritional_status' => 'Mild Acute Malnutrition (Wasting)',
            'risk_level' => 'Low-Medium',
            'category' => 'Undernutrition'
        ];
    } elseif ($bmi < 18.5) {
        return [
            'nutritional_status' => 'Underweight',
            'risk_level' => 'Medium',
            'category' => 'Undernutrition'
        ];
    } elseif ($bmi >= 18.5 && $bmi < 25) {
        return [
            'nutritional_status' => 'Normal',
            'risk_level' => 'Low',
            'category' => 'Normal'
        ];
    } elseif ($bmi >= 25 && $bmi < 30) {
        return [
            'nutritional_status' => 'Overweight',
            'risk_level' => 'Medium',
            'category' => 'Overnutrition'
        ];
    } else {
        return [
            'nutritional_status' => 'Obesity',
            'risk_level' => 'High',
            'category' => 'Overnutrition'
        ];
    }
}

function assessPregnantWoman($muac, $weight) {
    if ($muac < 23.0) {
        return [
            'nutritional_status' => 'Maternal Undernutrition (At-risk)',
            'risk_level' => 'High',
            'category' => 'Undernutrition'
        ];
    } elseif ($muac >= 23.0 && $muac < 25.0) {
        return [
            'nutritional_status' => 'Maternal At-risk',
            'risk_level' => 'Medium',
            'category' => 'Undernutrition'
        ];
    } else {
        return [
            'nutritional_status' => 'Normal',
            'risk_level' => 'Low',
            'category' => 'Normal'
        ];
    }
}

function assessAdultElderly($weight, $height, $muac) {
    $bmi = calculateBMI($weight, $height);
    
    if ($bmi < 16.0) {
        return [
            'nutritional_status' => 'Severe Underweight',
            'risk_level' => 'High',
            'category' => 'Undernutrition'
        ];
    } elseif ($bmi >= 16.0 && $bmi < 17.0) {
        return [
            'nutritional_status' => 'Moderate Underweight',
            'risk_level' => 'High',
            'category' => 'Undernutrition'
        ];
    } elseif ($bmi >= 17.0 && $bmi < 18.5) {
        return [
            'nutritional_status' => 'Mild Underweight',
            'risk_level' => 'Medium',
            'category' => 'Undernutrition'
        ];
    } elseif ($bmi >= 18.5 && $bmi < 25.0) {
        return [
            'nutritional_status' => 'Normal',
            'risk_level' => 'Low',
            'category' => 'Normal'
        ];
    } elseif ($bmi >= 25.0 && $bmi < 30.0) {
        return [
            'nutritional_status' => 'Overweight',
            'risk_level' => 'Medium',
            'category' => 'Overnutrition'
        ];
    } elseif ($bmi >= 30.0 && $bmi < 35.0) {
        return [
            'nutritional_status' => 'Obesity Class I',
            'risk_level' => 'High',
            'category' => 'Overnutrition'
        ];
    } elseif ($bmi >= 35.0 && $bmi < 40.0) {
        return [
            'nutritional_status' => 'Obesity Class II',
            'risk_level' => 'High',
            'category' => 'Overnutrition'
        ];
    } else {
        return [
            'nutritional_status' => 'Obesity Class III (Severe)',
            'risk_level' => 'Very High',
            'category' => 'Overnutrition'
        ];
    }
}

try {
    $db = DatabaseAPI::getInstance();
    $pdo = $db->getPDO();
    
    // Get parameters
    $barangay = $_GET['barangay'] ?? '';
    $timeFrame = $_GET['timeframe'] ?? '1d';
    
    // Calculate date range
    $now = new DateTime();
    $startDate = new DateTime();
    
    switch($timeFrame) {
        case '1d':
            $startDate->modify('-1 day');
            break;
        case '1w':
            $startDate->modify('-1 week');
            break;
        case '1m':
            $startDate->modify('-1 month');
            break;
        case '3m':
            $startDate->modify('-3 months');
            break;
        case '1y':
            $startDate->modify('-1 year');
            break;
        default:
            $startDate->modify('-1 day');
    }
    
    $startDateStr = $startDate->format('Y-m-d H:i:s');
    $endDateStr = $now->format('Y-m-d H:i:s');
    
    // Build query
    $whereClause = "WHERE cu.screening_date BETWEEN :start_date AND :end_date";
    $params = [':start_date' => $startDateStr, ':end_date' => $endDateStr];
    
    if ($barangay && $barangay !== '') {
        $whereClause .= " AND cu.barangay = :barangay";
        $params[':barangay'] = $barangay;
    }
    
    // Get all users in time frame
    $query = "
        SELECT 
            cu.*
        FROM community_users cu
        $whereClause
        ORDER BY cu.screening_date DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize counters
    $totalScreened = count($users);
    $highRiskCases = 0;
    $samCases = 0;
    $criticalMuac = 0;
    
    // Critical alerts data
    $criticalAlerts = [];
    
    // Risk level counters
    $riskLevels = [
        'low' => 0,
        'low_medium' => 0,
        'medium' => 0,
        'high' => 0,
        'very_high' => 0
    ];
    
    // Nutritional status counters
    $nutritionalStatus = [
        'Normal' => 0,
        'Underweight' => 0,
        'Overweight' => 0,
        'Obesity' => 0,
        'Severe Acute Malnutrition' => 0,
        'Moderate Acute Malnutrition' => 0,
        'Stunting' => 0,
        'Maternal Undernutrition' => 0
    ];
    
    // Age group counters
    $ageGroups = [
        'Under 1 year' => 0,
        '1-5 years' => 0,
        '6-12 years' => 0,
        '13-17 years' => 0,
        '18-59 years' => 0,
        '60+ years' => 0
    ];
    
    // Process each user
    foreach ($users as $user) {
        // Perform nutritional assessment
        $assessment = performNutritionalAssessment($user);
        $age = calculateAge($user['birthday']);
        
        // Count high risk cases (High or Very High risk level)
        if (in_array($assessment['risk_level'], ['High', 'Very High'])) {
            $highRiskCases++;
        }
        
        // Count SAM cases (Severe Acute Malnutrition)
        if (strpos($assessment['nutritional_status'], 'Severe Acute Malnutrition') !== false) {
            $samCases++;
        }
        
        // Count critical MUAC cases (High risk malnutrition)
        if (in_array($assessment['risk_level'], ['High', 'Very High']) && 
            strpos($assessment['nutritional_status'], 'Malnutrition') !== false) {
            $criticalMuac++;
        }
        
        // Add to critical alerts if high risk or severe risk
        if (in_array($assessment['risk_level'], ['High', 'Very High'])) {
            $criticalAlerts[] = [
                'user_id' => $user['user_id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'fcm_token' => $user['fcm_token'],
                'barangay' => $user['barangay'],
                'municipality' => $user['municipality'],
                'age' => round($age, 1),
                'sex' => $user['sex'],
                'nutritional_status' => $assessment['nutritional_status'],
                'risk_level' => $assessment['risk_level'],
                'category' => $assessment['category'],
                'alert_type' => $assessment['risk_level'] === 'Very High' ? 'severe' : 'high',
                'alert_title' => $assessment['risk_level'] === 'Very High' ? 'Severe Risk Case' : 'High Risk Case',
                'alert_description' => $assessment['nutritional_status'] . ' - ' . $assessment['risk_level'] . ' risk level',
                'screening_date' => $user['screening_date'],
                'bmi' => calculateBMI($user['weight'], $user['height']),
                'muac' => $user['muac'],
                'weight' => $user['weight'],
                'height' => $user['height']
            ];
        }
        
        // Count risk levels
        $riskLevel = strtolower(str_replace('-', '_', $assessment['risk_level']));
        if (isset($riskLevels[$riskLevel])) {
            $riskLevels[$riskLevel]++;
        }
        
        // Count nutritional status
        $status = $assessment['nutritional_status'];
        if (strpos($status, 'Normal') !== false) {
            $nutritionalStatus['Normal']++;
        } elseif (strpos($status, 'Underweight') !== false) {
            $nutritionalStatus['Underweight']++;
        } elseif (strpos($status, 'Overweight') !== false) {
            $nutritionalStatus['Overweight']++;
        } elseif (strpos($status, 'Obesity') !== false) {
            $nutritionalStatus['Obesity']++;
        } elseif (strpos($status, 'Severe Acute Malnutrition') !== false) {
            $nutritionalStatus['Severe Acute Malnutrition']++;
        } elseif (strpos($status, 'Moderate Acute Malnutrition') !== false) {
            $nutritionalStatus['Moderate Acute Malnutrition']++;
        } elseif (strpos($status, 'Stunting') !== false) {
            $nutritionalStatus['Stunting']++;
        } elseif (strpos($status, 'Maternal Undernutrition') !== false) {
            $nutritionalStatus['Maternal Undernutrition']++;
        }
        
        // Count age groups
        if ($age < 1) {
            $ageGroups['Under 1 year']++;
        } elseif ($age < 6) {
            $ageGroups['1-5 years']++;
        } elseif ($age < 13) {
            $ageGroups['6-12 years']++;
        } elseif ($age < 18) {
            $ageGroups['13-17 years']++;
        } elseif ($age < 60) {
            $ageGroups['18-59 years']++;
        } else {
            $ageGroups['60+ years']++;
        }
    }
    
    // Calculate percentages
    $riskPercentages = [];
    foreach ($riskLevels as $level => $count) {
        $riskPercentages[$level] = $totalScreened > 0 ? round(($count / $totalScreened) * 100, 1) : 0;
    }
    
    $nutritionalPercentages = [];
    foreach ($nutritionalStatus as $status => $count) {
        $nutritionalPercentages[$status] = $totalScreened > 0 ? round(($count / $totalScreened) * 100, 1) : 0;
    }
    
    $ageGroupPercentages = [];
    foreach ($ageGroups as $group => $count) {
        $ageGroupPercentages[$group] = $totalScreened > 0 ? round(($count / $totalScreened) * 100, 1) : 0;
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'total_screened' => $totalScreened,
            'high_risk_cases' => $highRiskCases,
            'sam_cases' => $samCases,
            'critical_muac' => $criticalMuac,
            'risk_levels' => $riskLevels,
            'risk_percentages' => $riskPercentages,
            'nutritional_status' => $nutritionalStatus,
            'nutritional_percentages' => $nutritionalPercentages,
            'age_groups' => $ageGroups,
            'age_group_percentages' => $ageGroupPercentages,
            'critical_alerts' => $criticalAlerts,
            'time_frame' => $timeFrame,
            'start_date' => $startDateStr,
            'end_date' => $endDateStr,
            'start_date_formatted' => $startDate->format('M j, Y'),
            'end_date_formatted' => $now->format('M j, Y')
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Dashboard Assessment Stats Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch assessment statistics',
        'message' => $e->getMessage()
    ]);
}
?>
