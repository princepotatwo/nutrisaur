<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

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
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        handlePostRequest();
        break;
    case 'GET':
        handleGetRequest();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handlePostRequest() {
    global $conn;
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        // Try form data
        $input = $_POST;
    }
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'No data provided']);
        return;
    }
    
    try {
        // Validate required fields
        $required_fields = ['municipality', 'barangay', 'age', 'sex', 'weight', 'height'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: {$field}"]);
                return;
            }
        }
        
        // Calculate BMI
        $weight = floatval($input['weight']);
        $height = floatval($input['height']) / 100; // Convert cm to meters
        $bmi = round($weight / ($height * $height), 2);
        
        // Calculate risk score
        $risk_score = calculateRiskScore($input, $bmi);
        
        // Prepare data for insertion
        $screening_data = [
            'user_id' => $input['user_id'] ?? null,
            'municipality' => $input['municipality'],
            'barangay' => $input['barangay'],
            'age' => intval($input['age']),
            'age_months' => !empty($input['age_months']) ? intval($input['age_months']) : null,
            'sex' => $input['sex'],
            'pregnant' => $input['pregnant'] ?? null,
            'weight' => $weight,
            'height' => floatval($input['height']),
            'bmi' => $bmi,
            'meal_recall' => $input['meal_recall'] ?? null,
            'family_history' => is_array($input['family_history']) ? json_encode($input['family_history']) : $input['family_history'],
            'lifestyle' => $input['lifestyle'] ?? null,
            'lifestyle_other' => $input['lifestyle_other'] ?? null,
            'immunization' => is_array($input['immunization']) ? json_encode($input['immunization']) : $input['immunization'],
            'risk_score' => $risk_score,
            'assessment_summary' => $input['assessment_summary'] ?? null,
            'recommendations' => $input['recommendations'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert into database
        $stmt = $conn->prepare("INSERT INTO screening_assessments (
            user_id, municipality, barangay, age, age_months, sex, pregnant, 
            weight, height, bmi, meal_recall, family_history, lifestyle, 
            lifestyle_other, immunization, risk_score, assessment_summary, 
            recommendations, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $screening_data['user_id'],
            $screening_data['municipality'],
            $screening_data['barangay'],
            $screening_data['age'],
            $screening_data['age_months'],
            $screening_data['sex'],
            $screening_data['pregnant'],
            $screening_data['weight'],
            $screening_data['height'],
            $screening_data['bmi'],
            $screening_data['meal_recall'],
            $screening_data['family_history'],
            $screening_data['lifestyle'],
            $screening_data['lifestyle_other'],
            $screening_data['immunization'],
            $screening_data['risk_score'],
            $screening_data['assessment_summary'],
            $screening_data['recommendations'],
            $screening_data['created_at']
        ]);
        
        $screening_id = $conn->lastInsertId();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Screening assessment saved successfully',
            'screening_id' => $screening_id,
            'risk_score' => $risk_score,
            'bmi' => $bmi,
            'assessment_summary' => $screening_data['assessment_summary'],
            'recommendations' => $screening_data['recommendations']
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error saving screening assessment: ' . $e->getMessage()]);
    }
}

function handleGetRequest() {
    global $conn;
    
    $user_id = $_GET['user_id'] ?? null;
    $screening_id = $_GET['screening_id'] ?? null;
    
    try {
        if ($screening_id) {
            // Get specific screening assessment
            $stmt = $conn->prepare("SELECT * FROM screening_assessments WHERE id = ?");
            $stmt->execute([$screening_id]);
            $assessment = $stmt->fetch();
            
            if (!$assessment) {
                http_response_code(404);
                echo json_encode(['error' => 'Screening assessment not found']);
                return;
            }
            
            echo json_encode($assessment);
            
        } else if ($user_id) {
            // Get all screening assessments for a user
            $stmt = $conn->prepare("SELECT * FROM screening_assessments WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$user_id]);
            $assessments = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'assessments' => $assessments,
                'count' => count($assessments)
            ]);
            
        } else {
            // Get all screening assessments (for admin)
            $stmt = $conn->prepare("SELECT * FROM screening_assessments ORDER BY created_at DESC LIMIT 100");
            $stmt->execute();
            $assessments = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'assessments' => $assessments,
                'count' => count($assessments)
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error retrieving screening assessments: ' . $e->getMessage()]);
    }
}

function calculateRiskScore($input, $bmi) {
    $risk_score = 0;
    
    // BMI risk factors
    if ($bmi < 18.5) {
        $risk_score += 10; // Underweight
    } elseif ($bmi >= 25 && $bmi < 30) {
        $risk_score += 5; // Overweight
    } elseif ($bmi >= 30) {
        $risk_score += 15; // Obese
    }
    
    // Age risk factors
    $age = intval($input['age']);
    if ($age < 5) {
        $risk_score += 10; // Young children
    } elseif ($age > 65) {
        $risk_score += 8; // Elderly
    }
    
    // Family history risk factors
    $family_history = is_array($input['family_history']) ? $input['family_history'] : json_decode($input['family_history'], true);
    if ($family_history && !in_array('None', $family_history)) {
        foreach ($family_history as $condition) {
            switch ($condition) {
                case 'Diabetes':
                    $risk_score += 8;
                    break;
                case 'Hypertension':
                    $risk_score += 6;
                    break;
                case 'Heart Disease':
                    $risk_score += 10;
                    break;
                case 'Kidney Disease':
                    $risk_score += 12;
                    break;
                case 'Tuberculosis':
                    $risk_score += 7;
                    break;
                case 'Obesity':
                    $risk_score += 5;
                    break;
                case 'Malnutrition':
                    $risk_score += 15;
                    break;
            }
        }
    }
    
    // Lifestyle risk factors
    if ($input['lifestyle'] === 'Sedentary') {
        $risk_score += 5;
    }
    
    // Meal balance risk (if meal recall is provided)
    if (!empty($input['meal_recall'])) {
        $meal_text = strtolower($input['meal_recall']);
        $food_groups = [
            'carbs' => ['rice', 'bread', 'pasta', 'potato', 'corn', 'cereal', 'oatmeal'],
            'protein' => ['meat', 'fish', 'chicken', 'pork', 'beef', 'egg', 'milk', 'cheese', 'beans', 'tofu'],
            'vegetables' => ['vegetable', 'carrot', 'broccoli', 'spinach', 'lettuce', 'tomato', 'onion'],
            'fruits' => ['fruit', 'apple', 'banana', 'orange', 'mango', 'grape']
        ];
        
        $found_groups = 0;
        foreach ($food_groups as $group => $foods) {
            foreach ($foods as $food) {
                if (strpos($meal_text, $food) !== false) {
                    $found_groups++;
                    break;
                }
            }
        }
        
        if ($found_groups < 3) {
            $risk_score += 8; // Unbalanced diet
        }
    }
    
    // Immunization risk (for children <= 12)
    if ($age <= 12 && !empty($input['immunization'])) {
        $immunization = is_array($input['immunization']) ? $input['immunization'] : json_decode($input['immunization'], true);
        $required_vaccines = ['BCG', 'DPT', 'Polio', 'Measles', 'Hepatitis B', 'Vitamin A'];
        $missing_vaccines = array_diff($required_vaccines, $immunization);
        
        if (!empty($missing_vaccines)) {
            $risk_score += count($missing_vaccines) * 2; // 2 points per missing vaccine
        }
    }
    
    return $risk_score;
}
?>
