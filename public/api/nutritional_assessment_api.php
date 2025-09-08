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
 * Child/Adolescent Assessment (Age < 18)
 */
function assessChildAdolescent($age, $weight, $height, $muac, $sex) {
    // Calculate BMI
    $bmi = calculateBMI($weight, $height);
    
    // For children 6-59 months, use MUAC
    if ($age >= 0.5 && $age < 5) { // 6 months to 5 years
        if ($muac < 11.5) {
            return [
                'nutritional_status' => 'Severe Acute Malnutrition (SAM)',
                'risk_level' => 'High',
                'category' => 'Undernutrition',
                'description' => 'Child has severe acute malnutrition. Immediate medical attention required.',
                'recommendations' => [
                    'Seek immediate medical care',
                    'Start therapeutic feeding program',
                    'Monitor closely for complications'
                ],
                'measurements_used' => 'Arm circumference (MUAC)',
                'cutoff_used' => 'MUAC < 11.5 cm',
                'bmi' => $bmi
            ];
        } elseif ($muac >= 11.5 && $muac < 12.5) {
            return [
                'nutritional_status' => 'Moderate Acute Malnutrition (MAM)',
                'risk_level' => 'Medium',
                'category' => 'Undernutrition',
                'description' => 'Child has moderate acute malnutrition. Nutritional support needed.',
                'recommendations' => [
                    'Start supplementary feeding program',
                    'Monitor growth regularly',
                    'Ensure adequate nutrition'
                ],
                'measurements_used' => 'Arm circumference (MUAC)',
                'cutoff_used' => 'MUAC 11.5-12.5 cm',
                'bmi' => $bmi
            ];
        }
    }
    
    // For older children, use BMI-for-age (simplified)
    $bmiForAge = calculateBMIForAge($bmi, $age, $sex);
    
    if ($bmiForAge < -2) {
        return [
            'nutritional_status' => 'Stunting (Chronic Malnutrition)',
            'risk_level' => 'Medium',
            'category' => 'Undernutrition',
            'description' => 'Child shows signs of chronic malnutrition (stunting). Long-term nutritional support needed.',
            'recommendations' => [
                'Improve overall nutrition quality',
                'Monitor growth regularly',
                'Address underlying causes'
            ],
            'measurements_used' => 'BMI-for-age',
            'cutoff_used' => 'BMI-for-age < -2',
            'bmi' => $bmi
        ];
    }
    
    return [
        'nutritional_status' => 'Normal',
        'risk_level' => 'Low',
        'category' => 'Normal',
        'description' => 'Child has normal nutritional status. Continue healthy eating habits.',
        'recommendations' => [
            'Maintain balanced diet',
            'Continue regular growth monitoring',
            'Encourage physical activity'
        ],
        'measurements_used' => 'BMI-for-age',
        'cutoff_used' => 'Normal range',
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
    return $age->y;
}

function calculateBMI($weight, $height) {
    if ($height <= 0) return 0;
    $heightInMeters = $height / 100;
    return round($weight / ($heightInMeters * $heightInMeters), 1);
}

function calculateBMIForAge($bmi, $age, $sex) {
    // Simplified BMI-for-age calculation
    // In a real implementation, you would use WHO growth standards
    $expectedBMI = 18.5 + ($age * 0.5); // Simplified growth curve
    return ($bmi - $expectedBMI) / $expectedBMI;
}
?>
