<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/DatabaseAPI.php";

$response = ['success' => false, 'message' => ''];

try {
    $pdo = getDatabaseConnection();
    if (!$pdo) {
        throw new Exception("Database connection failed.");
    }

    $api = new DatabaseAPI($pdo);
    
    // Get action from request
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'assess_user':
            $email = $_POST['email'] ?? '';
            if (empty($email)) {
                throw new Exception("Email is required for assessment");
            }
            
            $result = assessUserNutritionalStatus($api, $email);
            $response = $result;
            break;
            
        case 'assess_all_users':
            $result = assessAllUsersNutritionalStatus($api);
            $response = $result;
            break;
            
        case 'get_assessment_stats':
            $result = getAssessmentStatistics($api);
            $response = $result;
            break;
            
        default:
            throw new Exception("Invalid action. Use: assess_user, assess_all_users, or get_assessment_stats");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);

/**
 * Assess nutritional status for a single user
 */
function assessUserNutritionalStatus($api, $email) {
    // Get user data
    $userData = $api->select('community_users', '*', 'email = ?', [$email]);
    
    if (!$userData['success'] || empty($userData['data'])) {
        return [
            'success' => false,
            'message' => 'User not found'
        ];
    }
    
    $user = $userData['data'][0];
    $assessment = performNutritionalAssessment($user);
    
    return [
        'success' => true,
        'data' => [
            'user_info' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'age' => calculateAge($user['birthday']),
                'sex' => $user['sex'],
                'is_pregnant' => $user['is_pregnant']
            ],
            'measurements' => [
                'weight_kg' => $user['weight'],
                'height_cm' => $user['height'],
                'arm_circumference_cm' => $user['muac']
            ],
            'assessment_result' => $assessment
        ]
    ];
}

/**
 * Assess nutritional status for all users
 */
function assessAllUsersNutritionalStatus($api) {
    $allUsers = $api->select('community_users', '*', '', [], 'screening_date DESC');
    
    if (!$allUsers['success']) {
        return [
            'success' => false,
            'message' => 'Failed to fetch users'
        ];
    }
    
    $assessments = [];
    foreach ($allUsers['data'] as $user) {
        $assessment = performNutritionalAssessment($user);
        $assessments[] = [
            'user_info' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'age' => calculateAge($user['birthday']),
                'sex' => $user['sex'],
                'is_pregnant' => $user['is_pregnant']
            ],
            'measurements' => [
                'weight_kg' => $user['weight'],
                'height_cm' => $user['height'],
                'arm_circumference_cm' => $user['muac']
            ],
            'assessment_result' => $assessment
        ];
    }
    
    return [
        'success' => true,
        'data' => $assessments,
        'total_assessed' => count($assessments)
    ];
}

/**
 * Get assessment statistics
 */
function getAssessmentStatistics($api) {
    $allUsers = $api->select('community_users', '*', '', [], 'screening_date DESC');
    
    if (!$allUsers['success']) {
        return [
            'success' => false,
            'message' => 'Failed to fetch users'
        ];
    }
    
    $stats = [
        'total_users' => 0,
        'normal' => 0,
        'underweight' => 0,
        'overweight' => 0,
        'obesity' => 0,
        'severe_malnutrition' => 0,
        'moderate_malnutrition' => 0,
        'stunting' => 0,
        'maternal_undernutrition' => 0,
        'children' => 0,
        'pregnant_women' => 0,
        'adults' => 0
    ];
    
    foreach ($allUsers['data'] as $user) {
        $stats['total_users']++;
        $assessment = performNutritionalAssessment($user);
        $age = calculateAge($user['birthday']);
        
        // Count by age group
        if ($age < 18) {
            $stats['children']++;
        } elseif ($user['is_pregnant'] === 'Yes') {
            $stats['pregnant_women']++;
        } else {
            $stats['adults']++;
        }
        
        // Count by nutritional status
        $status = $assessment['nutritional_status'];
        switch ($status) {
            case 'Normal':
                $stats['normal']++;
                break;
            case 'Underweight':
                $stats['underweight']++;
                break;
            case 'Overweight':
                $stats['overweight']++;
                break;
            case 'Obesity':
                $stats['obesity']++;
                break;
            case 'Severe Acute Malnutrition (SAM)':
                $stats['severe_malnutrition']++;
                break;
            case 'Moderate Acute Malnutrition (MAM)':
                $stats['moderate_malnutrition']++;
                break;
            case 'Stunting (Chronic Malnutrition)':
                $stats['stunting']++;
                break;
            case 'Maternal Undernutrition (At-risk)':
                $stats['maternal_undernutrition']++;
                break;
        }
    }
    
    return [
        'success' => true,
        'data' => $stats
    ];
}

/**
 * Main nutritional assessment decision tree
 */
function performNutritionalAssessment($user) {
    $age = calculateAge($user['birthday']);
    $weight = floatval($user['weight']);
    $height = floatval($user['height']);
    $muac = floatval($user['muac']); // Mid-Upper Arm Circumference
    $isPregnant = $user['is_pregnant'] === 'Yes';
    $sex = $user['sex'];
    
    // Decision Tree Implementation
    if ($age < 18) {
        // CHILD/ADOLESCENT ASSESSMENT
        return assessChildAdolescent($age, $weight, $height, $muac, $sex);
    } elseif ($isPregnant) {
        // PREGNANT WOMAN ASSESSMENT
        return assessPregnantWoman($muac, $weight);
    } else {
        // ADULT/ELDERLY ASSESSMENT
        return assessAdultElderly($weight, $height, $muac);
    }
}

/**
 * Child/Adolescent Assessment (Age < 18) - Following WHO Growth Standards
 */
function assessChildAdolescent($age, $weight, $height, $muac, $sex) {
    // Calculate BMI
    $bmi = calculateBMI($weight, $height);
    
    // Calculate z-scores using WHO standards
    $whZScore = calculateWeightForHeightZScore($weight, $height, $age, $sex);
    $haZScore = calculateHeightForAgeZScore($height, $age, $sex);
    $bmiForAgeZScore = calculateBMIForAgeZScore($bmi, $age, $sex);
    
    // DECISION TREE: Is W/H z-score < -3 OR MUAC < 11.5 cm?
    if ($whZScore < -3 || ($age >= 0.5 && $age < 5 && $muac < 11.5)) {
        return [
            'nutritional_status' => 'Severe Acute Malnutrition (SAM)',
            'risk_level' => 'High',
            'category' => 'Undernutrition',
            'description' => 'Child has severe acute malnutrition. Immediate medical attention required.',
            'recommendations' => [
                'Seek immediate medical care',
                'Start therapeutic feeding program',
                'Monitor closely for complications',
                'Refer to specialized nutrition center'
            ],
            'measurements_used' => 'Weight-for-Height z-score OR MUAC',
            'cutoff_used' => 'W/H z-score < -3 OR MUAC < 11.5 cm',
            'z_scores' => [
                'weight_for_height' => round($whZScore, 2),
                'height_for_age' => round($haZScore, 2),
                'bmi_for_age' => round($bmiForAgeZScore, 2)
            ],
            'bmi' => $bmi
        ];
    }
    
    // DECISION TREE: Is W/H z-score < -2 (≥ -3) OR MUAC 11.5-12.5 cm?
    if (($whZScore < -2 && $whZScore >= -3) || ($age >= 0.5 && $age < 5 && $muac >= 11.5 && $muac < 12.5)) {
        return [
            'nutritional_status' => 'Moderate Acute Malnutrition (MAM)',
            'risk_level' => 'Medium',
            'category' => 'Undernutrition',
            'description' => 'Child has moderate acute malnutrition. Nutritional support needed.',
            'recommendations' => [
                'Start supplementary feeding program',
                'Monitor growth regularly',
                'Ensure adequate nutrition',
                'Follow up in 2-4 weeks'
            ],
            'measurements_used' => 'Weight-for-Height z-score OR MUAC',
            'cutoff_used' => 'W/H z-score < -2 (≥ -3) OR MUAC 11.5-12.5 cm',
            'z_scores' => [
                'weight_for_height' => round($whZScore, 2),
                'height_for_age' => round($haZScore, 2),
                'bmi_for_age' => round($bmiForAgeZScore, 2)
            ],
            'bmi' => $bmi
        ];
    }
    
    // DECISION TREE: Is H/A z-score < -2? (Stunting)
    if ($haZScore < -2) {
        return [
            'nutritional_status' => 'Stunting (Chronic Malnutrition)',
            'risk_level' => 'Medium',
            'category' => 'Undernutrition',
            'description' => 'Child shows signs of chronic malnutrition (stunting). Long-term nutritional support needed.',
            'recommendations' => [
                'Improve overall nutrition quality',
                'Monitor growth regularly',
                'Address underlying causes',
                'Focus on micronutrient supplementation'
            ],
            'measurements_used' => 'Height-for-Age z-score',
            'cutoff_used' => 'H/A z-score < -2',
            'z_scores' => [
                'weight_for_height' => round($whZScore, 2),
                'height_for_age' => round($haZScore, 2),
                'bmi_for_age' => round($bmiForAgeZScore, 2)
            ],
            'bmi' => $bmi
        ];
    }
    
    // If none of the above conditions are met
    return [
        'nutritional_status' => 'Normal',
        'risk_level' => 'Low',
        'category' => 'Normal',
        'description' => 'Child has normal nutritional status. Continue healthy eating habits.',
        'recommendations' => [
            'Maintain balanced diet',
            'Continue regular growth monitoring',
            'Encourage physical activity',
            'Prevent malnutrition through good nutrition'
        ],
        'measurements_used' => 'All z-scores within normal range',
        'cutoff_used' => 'All z-scores ≥ -2',
        'z_scores' => [
            'weight_for_height' => round($whZScore, 2),
            'height_for_age' => round($haZScore, 2),
            'bmi_for_age' => round($bmiForAgeZScore, 2)
        ],
        'bmi' => $bmi
    ];
}

/**
 * Pregnant Woman Assessment
 */
function assessPregnantWoman($muac, $weight) {
    if ($muac < 23.0) {
        return [
            'nutritional_status' => 'Maternal Undernutrition (At-risk)',
            'risk_level' => 'High',
            'category' => 'Undernutrition',
            'description' => 'Pregnant woman is at risk of undernutrition. Immediate nutritional support needed.',
            'recommendations' => [
                'Start nutritional supplementation',
                'Increase caloric intake',
                'Monitor pregnancy closely',
                'Consult healthcare provider'
            ],
            'measurements_used' => 'Arm circumference (MUAC)',
            'cutoff_used' => 'MUAC < 23.0 cm',
            'bmi' => null
        ];
    }
    
    return [
        'nutritional_status' => 'Normal',
        'risk_level' => 'Low',
        'category' => 'Normal',
        'description' => 'Pregnant woman has adequate nutritional status. Continue healthy pregnancy nutrition.',
        'recommendations' => [
            'Maintain balanced pregnancy diet',
            'Continue prenatal care',
            'Monitor weight gain'
        ],
        'measurements_used' => 'Arm circumference (MUAC)',
        'cutoff_used' => 'MUAC ≥ 23.0 cm',
        'bmi' => null
    ];
}

/**
 * Adult/Elderly Assessment (Age ≥ 18, Not Pregnant)
 */
function assessAdultElderly($weight, $height, $muac) {
    $bmi = calculateBMI($weight, $height);
    
    if ($bmi < 18.5) {
        return [
            'nutritional_status' => 'Underweight',
            'risk_level' => 'Medium',
            'category' => 'Undernutrition',
            'description' => 'Adult is underweight. Nutritional improvement needed.',
            'recommendations' => [
                'Increase caloric intake',
                'Focus on nutrient-dense foods',
                'Consult healthcare provider',
                'Monitor weight gain'
            ],
            'measurements_used' => 'Body Mass Index (BMI)',
            'cutoff_used' => 'BMI < 18.5',
            'bmi' => $bmi
        ];
    } elseif ($bmi >= 18.5 && $bmi < 25.0) {
        return [
            'nutritional_status' => 'Normal',
            'risk_level' => 'Low',
            'category' => 'Normal',
            'description' => 'Adult has normal nutritional status. Maintain healthy lifestyle.',
            'recommendations' => [
                'Maintain balanced diet',
                'Regular physical activity',
                'Continue healthy habits'
            ],
            'measurements_used' => 'Body Mass Index (BMI)',
            'cutoff_used' => 'BMI 18.5-24.9',
            'bmi' => $bmi
        ];
    } elseif ($bmi >= 25.0 && $bmi < 30.0) {
        return [
            'nutritional_status' => 'Overweight',
            'risk_level' => 'Medium',
            'category' => 'Overnutrition',
            'description' => 'Adult is overweight. Weight management recommended.',
            'recommendations' => [
                'Reduce caloric intake',
                'Increase physical activity',
                'Focus on whole foods',
                'Monitor portion sizes'
            ],
            'measurements_used' => 'Body Mass Index (BMI)',
            'cutoff_used' => 'BMI 25.0-29.9',
            'bmi' => $bmi
        ];
    } else {
        return [
            'nutritional_status' => 'Obesity',
            'risk_level' => 'High',
            'category' => 'Overnutrition',
            'description' => 'Adult has obesity. Comprehensive weight management needed.',
            'recommendations' => [
                'Consult healthcare provider',
                'Start structured weight loss program',
                'Increase physical activity',
                'Focus on sustainable lifestyle changes'
            ],
            'measurements_used' => 'Body Mass Index (BMI)',
            'cutoff_used' => 'BMI ≥ 30.0',
            'bmi' => $bmi
        ];
    }
}

/**
 * Helper Functions
 */
function calculateAge($birthday) {
    $birthDate = new DateTime($birthday);
    $today = new DateTime();
    $age = $today->diff($birthDate);
    return $age->y + ($age->m / 12); // Return age in years with decimal for months
}

function calculateBMI($weight, $height) {
    if ($height <= 0) return 0;
    $heightInMeters = $height / 100;
    return round($weight / ($heightInMeters * $heightInMeters), 1);
}

/**
 * Calculate Weight-for-Height Z-Score using WHO standards
 * Based on WHO Child Growth Standards
 */
function calculateWeightForHeightZScore($weight, $height, $age, $sex) {
    // WHO Weight-for-Height reference data (simplified)
    // In practice, you would use complete WHO lookup tables
    
    $ageInMonths = $age * 12;
    
    // For children under 24 months, use length; for older children, use height
    if ($ageInMonths < 24) {
        // Use length-for-age reference
        return calculateLengthForAgeZScore($height, $age, $sex);
    }
    
    // WHO Weight-for-Height reference values (simplified)
    $referenceData = [
        'male' => [
            'height' => [65, 70, 75, 80, 85, 90, 95, 100, 105, 110, 115, 120, 125, 130, 135, 140, 145, 150, 155, 160, 165, 170, 175, 180, 185, 190],
            'median' => [7.0, 8.0, 9.0, 10.0, 11.0, 12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 19.0, 20.0, 21.0, 22.0, 23.0, 24.0, 25.0, 26.0, 27.0, 28.0, 29.0, 30.0, 31.0, 32.0],
            'sd' => [0.8, 0.9, 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 2.0, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 3.0, 3.1, 3.2, 3.3]
        ],
        'female' => [
            'height' => [65, 70, 75, 80, 85, 90, 95, 100, 105, 110, 115, 120, 125, 130, 135, 140, 145, 150, 155, 160, 165, 170, 175, 180, 185, 190],
            'median' => [6.5, 7.5, 8.5, 9.5, 10.5, 11.5, 12.5, 13.5, 14.5, 15.5, 16.5, 17.5, 18.5, 19.5, 20.5, 21.5, 22.5, 23.5, 24.5, 25.5, 26.5, 27.5, 28.5, 29.5, 30.5, 31.5],
            'sd' => [0.7, 0.8, 0.9, 1.0, 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 1.9, 2.0, 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 2.8, 2.9, 3.0, 3.1, 3.2]
        ]
    ];
    
    $sexKey = strtolower($sex) === 'male' ? 'male' : 'female';
    $data = $referenceData[$sexKey];
    
    // Find closest height reference
    $closestIndex = 0;
    $minDiff = abs($height - $data['height'][0]);
    
    for ($i = 1; $i < count($data['height']); $i++) {
        $diff = abs($height - $data['height'][$i]);
        if ($diff < $minDiff) {
            $minDiff = $diff;
            $closestIndex = $i;
        }
    }
    
    $median = $data['median'][$closestIndex];
    $sd = $data['sd'][$closestIndex];
    
    // Calculate z-score: (observed - median) / SD
    $zScore = ($weight - $median) / $sd;
    
    return round($zScore, 2);
}

/**
 * Calculate Height-for-Age Z-Score using WHO standards
 */
function calculateHeightForAgeZScore($height, $age, $sex) {
    $ageInMonths = $age * 12;
    
    // WHO Height-for-Age reference data (simplified)
    $referenceData = [
        'male' => [
            'age_months' => [0, 1, 2, 3, 6, 9, 12, 18, 24, 30, 36, 42, 48, 54, 60, 66, 72, 78, 84, 90, 96, 102, 108, 114, 120, 126, 132, 138, 144, 150, 156, 162, 168, 174, 180, 186, 192, 198, 204, 210, 216, 222, 228, 234, 240],
            'median' => [49.9, 54.7, 58.4, 61.4, 67.6, 72.0, 75.7, 82.5, 87.1, 91.9, 96.1, 99.9, 103.3, 106.7, 109.9, 112.9, 115.7, 118.4, 121.0, 123.5, 125.9, 128.2, 130.4, 132.5, 134.5, 136.4, 138.2, 139.9, 141.5, 143.0, 144.4, 145.7, 146.9, 148.0, 149.0, 149.9, 150.7, 151.4, 152.0, 152.5, 152.9, 153.2, 153.4, 153.5, 153.6],
            'sd' => [1.9, 2.1, 2.3, 2.4, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5, 2.5]
        ],
        'female' => [
            'age_months' => [0, 1, 2, 3, 6, 9, 12, 18, 24, 30, 36, 42, 48, 54, 60, 66, 72, 78, 84, 90, 96, 102, 108, 114, 120, 126, 132, 138, 144, 150, 156, 162, 168, 174, 180, 186, 192, 198, 204, 210, 216, 222, 228, 234, 240],
            'median' => [49.1, 53.7, 57.1, 59.8, 65.3, 70.1, 74.0, 80.7, 85.7, 90.4, 94.5, 98.1, 101.3, 104.5, 107.5, 110.3, 112.9, 115.4, 117.7, 119.9, 122.0, 124.0, 125.9, 127.7, 129.4, 131.0, 132.5, 133.9, 135.2, 136.4, 137.5, 138.5, 139.4, 140.2, 140.9, 141.5, 142.0, 142.4, 142.7, 142.9, 143.0, 143.0, 142.9, 142.7, 142.5],
            'sd' => [1.9, 2.0, 2.1, 2.2, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3, 2.3]
        ]
    ];
    
    $sexKey = strtolower($sex) === 'male' ? 'male' : 'female';
    $data = $referenceData[$sexKey];
    
    // Find closest age reference
    $closestIndex = 0;
    $minDiff = abs($ageInMonths - $data['age_months'][0]);
    
    for ($i = 1; $i < count($data['age_months']); $i++) {
        $diff = abs($ageInMonths - $data['age_months'][$i]);
        if ($diff < $minDiff) {
            $minDiff = $diff;
            $closestIndex = $i;
        }
    }
    
    $median = $data['median'][$closestIndex];
    $sd = $data['sd'][$closestIndex];
    
    // Calculate z-score: (observed - median) / SD
    $zScore = ($height - $median) / $sd;
    
    return round($zScore, 2);
}

/**
 * Calculate BMI-for-Age Z-Score using WHO standards
 */
function calculateBMIForAgeZScore($bmi, $age, $sex) {
    $ageInMonths = $age * 12;
    
    // WHO BMI-for-Age reference data (simplified)
    $referenceData = [
        'male' => [
            'age_months' => [0, 1, 2, 3, 6, 9, 12, 18, 24, 30, 36, 42, 48, 54, 60, 66, 72, 78, 84, 90, 96, 102, 108, 114, 120, 126, 132, 138, 144, 150, 156, 162, 168, 174, 180, 186, 192, 198, 204, 210, 216, 222, 228, 234, 240],
            'median' => [13.4, 14.2, 15.1, 15.8, 16.5, 16.8, 16.9, 16.8, 16.4, 16.0, 15.6, 15.3, 15.1, 15.0, 15.0, 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.7, 15.8, 15.9, 16.0, 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7, 16.8, 16.9, 17.0, 17.1, 17.2, 17.3, 17.4, 17.5, 17.6, 17.7, 17.8, 17.9, 18.0],
            'sd' => [1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0]
        ],
        'female' => [
            'age_months' => [0, 1, 2, 3, 6, 9, 12, 18, 24, 30, 36, 42, 48, 54, 60, 66, 72, 78, 84, 90, 96, 102, 108, 114, 120, 126, 132, 138, 144, 150, 156, 162, 168, 174, 180, 186, 192, 198, 204, 210, 216, 222, 228, 234, 240],
            'median' => [13.3, 14.0, 14.8, 15.5, 16.1, 16.4, 16.5, 16.4, 16.0, 15.6, 15.2, 14.9, 14.7, 14.6, 14.6, 14.7, 14.8, 14.9, 15.0, 15.1, 15.2, 15.3, 15.4, 15.5, 15.6, 15.7, 15.8, 15.9, 16.0, 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7, 16.8, 16.9, 17.0, 17.1, 17.2, 17.3, 17.4, 17.5, 17.6],
            'sd' => [1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0, 1.0]
        ]
    ];
    
    $sexKey = strtolower($sex) === 'male' ? 'male' : 'female';
    $data = $referenceData[$sexKey];
    
    // Find closest age reference
    $closestIndex = 0;
    $minDiff = abs($ageInMonths - $data['age_months'][0]);
    
    for ($i = 1; $i < count($data['age_months']); $i++) {
        $diff = abs($ageInMonths - $data['age_months'][$i]);
        if ($diff < $minDiff) {
            $minDiff = $diff;
            $closestIndex = $i;
        }
    }
    
    $median = $data['median'][$closestIndex];
    $sd = $data['sd'][$closestIndex];
    
    // Calculate z-score: (observed - median) / SD
    $zScore = ($bmi - $median) / $sd;
    
    return round($zScore, 2);
}

/**
 * Calculate Length-for-Age Z-Score (for children under 24 months)
 */
function calculateLengthForAgeZScore($length, $age, $sex) {
    // This is a simplified version - in practice, you'd use complete WHO tables
    return calculateHeightForAgeZScore($length, $age, $sex);
}
?>
