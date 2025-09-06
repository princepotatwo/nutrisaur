<?php
/**
 * Simple Settings API - Direct PDO version for testing
 */

// Start the session
session_start();

// Set JSON header
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Simple database connection
try {
    require_once __DIR__ . "/../../config.php";
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Get action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_users':
        case 'usm':
            // Get all user preferences using simple PDO
            $stmt = $pdo->prepare("SELECT id, user_email, username, age, gender, barangay, municipality, province, weight_kg, height_cm, bmi, risk_score, malnutrition_risk, screening_date, created_at, updated_at, name, birthday, income, muac, screening_answers, allergies, diet_prefs, avoid_foods, swelling, weight_loss, feeding_behavior, physical_signs, dietary_diversity, clinical_risk_factors, whz_score, income_level FROM user_preferences ORDER BY updated_at DESC LIMIT 100");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format the data for the frontend
            $formattedUsers = [];
            foreach ($users as $user) {
                $formattedUsers[] = [
                    'id' => $user['id'],
                    'username' => $user['username'] ?? 'N/A',
                    'email' => $user['user_email'],
                    'name' => $user['name'] ?? 'N/A',
                    'age' => $user['age'] ?? 'N/A',
                    'gender' => $user['gender'] ?? 'N/A',
                    'height_cm' => $user['height_cm'] ?? 'N/A',
                    'weight_kg' => $user['weight_kg'] ?? 'N/A',
                    'bmi' => $user['bmi'] ?? 'N/A',
                    'barangay' => $user['barangay'] ?? 'N/A',
                    'municipality' => $user['municipality'] ?? 'N/A',
                    'risk_score' => $user['risk_score'] ?? 'N/A',
                    'malnutrition_risk' => $user['malnutrition_risk'] ?? 'N/A',
                    'created_at' => $user['created_at'] ?? 'N/A',
                    'updated_at' => $user['updated_at'] ?? 'N/A'
                ];
            }
            
            echo json_encode([
                'success' => true,
                'users' => $formattedUsers,
                'data' => $formattedUsers,
                'count' => count($formattedUsers)
            ]);
            break;
            
        case 'add_user':
            // Add new user using simple PDO
            $requiredFields = ['user_email', 'name'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
                    exit;
                }
            }
            
            // Calculate BMI if height and weight are provided
            $bmi = 0;
            if (!empty($_POST['height']) && !empty($_POST['weight'])) {
                $heightMeters = floatval($_POST['height']) / 100.0;
                $bmi = $heightMeters > 0 ? round((floatval($_POST['weight']) / ($heightMeters * $heightMeters)), 1) : 0;
            }
            
            // Check if user already exists
            $checkStmt = $pdo->prepare("SELECT id FROM user_preferences WHERE user_email = ?");
            $checkStmt->execute([$_POST['user_email']]);
            $existingUser = $checkStmt->fetch();
            
            if ($existingUser) {
                // Update existing user
                $updateStmt = $pdo->prepare("UPDATE user_preferences SET 
                    username = ?, name = ?, birthday = ?, age = ?, gender = ?, 
                    height_cm = ?, weight_kg = ?, bmi = ?, muac = ?, risk_score = ?, 
                    allergies = ?, diet_prefs = ?, avoid_foods = ?, barangay = ?, 
                    income = ?, municipality = ?, province = ?, screening_date = ?, 
                    updated_at = ? 
                    WHERE user_email = ?");
                
                $result = $updateStmt->execute([
                    $_POST['username'] ?? explode('@', $_POST['user_email'])[0],
                    $_POST['name'],
                    $_POST['birthday'] ?? null,
                    $_POST['age'] ?? null,
                    $_POST['gender'] ?? '',
                    $_POST['height'] ?? null,
                    $_POST['weight'] ?? null,
                    $bmi,
                    $_POST['muac'] ?? null,
                    $_POST['risk_score'] ?? 0,
                    $_POST['allergies'] ?? '',
                    $_POST['diet_prefs'] ?? '',
                    $_POST['avoid_foods'] ?? '',
                    $_POST['barangay'] ?? '',
                    $_POST['income'] ?? '',
                    $_POST['municipality'] ?? '',
                    $_POST['province'] ?? '',
                    date('Y-m-d'),
                    date('Y-m-d H:i:s'),
                    $_POST['user_email']
                ]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'User updated successfully',
                        'data' => ['user_email' => $_POST['user_email']]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to update user']);
                }
            } else {
                // Insert new user
                $insertStmt = $pdo->prepare("INSERT INTO user_preferences 
                    (user_email, username, name, birthday, age, gender, height_cm, weight_kg, bmi, muac, risk_score, 
                     allergies, diet_prefs, avoid_foods, barangay, income, municipality, province, screening_date, 
                     created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $result = $insertStmt->execute([
                    $_POST['user_email'],
                    $_POST['username'] ?? explode('@', $_POST['user_email'])[0],
                    $_POST['name'],
                    $_POST['birthday'] ?? null,
                    $_POST['age'] ?? null,
                    $_POST['gender'] ?? '',
                    $_POST['height'] ?? null,
                    $_POST['weight'] ?? null,
                    $bmi,
                    $_POST['muac'] ?? null,
                    $_POST['risk_score'] ?? 0,
                    $_POST['allergies'] ?? '',
                    $_POST['diet_prefs'] ?? '',
                    $_POST['avoid_foods'] ?? '',
                    $_POST['barangay'] ?? '',
                    $_POST['income'] ?? '',
                    $_POST['municipality'] ?? '',
                    $_POST['province'] ?? '',
                    date('Y-m-d'),
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s')
                ]);
                
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'User created successfully',
                        'data' => ['user_email' => $_POST['user_email']]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to create user']);
                }
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
?>
