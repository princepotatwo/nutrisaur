<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $pdo = getDatabaseConnection();
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // Get POST data for JSON requests
    $postData = json_decode(file_get_contents('php://input'), true);
    if ($postData && isset($postData['action'])) {
        $action = $postData['action'];
    }
    
    switch ($action) {
        case 'get_template_foods':
            getTemplateFoods($pdo);
            break;
        case 'get_food_details':
            getFoodDetails($pdo);
            break;
        case 'update_template_food':
            updateTemplateFood($pdo, $postData);
            break;
        case 'delete_template_food':
            deleteTemplateFood($pdo, $postData);
            break;
        case 'add_template_food':
            addTemplateFood($pdo, $postData);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("MHO Food Manager API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function getTemplateFoods($pdo) {
    $classification = $_GET['classification'] ?? '';
    $duration = $_GET['duration'] ?? '';
    
    if (empty($classification) || empty($duration)) {
        echo json_encode(['success' => false, 'error' => 'Missing classification or duration']);
        return;
    }
    
    // Query user_food_history table with special template markers
    // Use a system template approach - templates are stored with NULL user_email
    $sql = "SELECT id, user_email, date, meal_category, food_name, calories, serving_size, 
                   protein, carbs, fat, fiber, emoji, classification, plan_duration,
                   DATEDIFF(date, '1970-01-01') as day_number
            FROM user_food_history 
            WHERE user_email IS NULL 
            AND classification = ? 
            AND plan_duration = ?
            AND is_mho_recommended = 1
            ORDER BY date, meal_category";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$classification, $duration]);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $foods
    ]);
}

function getFoodDetails($pdo) {
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Missing food ID']);
        return;
    }
    
    $sql = "SELECT id, user_email, date, meal_category, food_name, calories, serving_size, 
                   protein, carbs, fat, fiber, emoji, classification, plan_duration,
                   DATEDIFF(date, '1970-01-01') as day_number
            FROM user_food_history 
            WHERE id = ? AND user_email IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $food = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($food) {
        echo json_encode(['success' => true, 'food' => $food]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Food not found']);
    }
}

function updateTemplateFood($pdo, $data) {
    $id = $data['id'] ?? '';
    $food_name = $data['food_name'] ?? '';
    $serving_size = $data['serving_size'] ?? '';
    $calories = $data['calories'] ?? 0;
    $protein = $data['protein'] ?? 0;
    $carbs = $data['carbs'] ?? 0;
    $fat = $data['fat'] ?? 0;
    $fiber = $data['fiber'] ?? 0;
    $day_number = $data['day_number'] ?? 1;
    $meal_category = $data['meal_category'] ?? 'Breakfast';
    
    if (empty($id) || empty($food_name)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    // Calculate date from day_number (days since epoch 1970-01-01)
    $date = date('Y-m-d', strtotime('1970-01-01 +' . ($day_number - 1) . ' days'));
    
    $sql = "UPDATE user_food_history 
            SET food_name = ?, 
                serving_size = ?, 
                calories = ?, 
                protein = ?, 
                carbs = ?, 
                fat = ?, 
                fiber = ?,
                meal_category = ?,
                date = ?
            WHERE id = ? AND user_email IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $food_name, 
        $serving_size, 
        $calories, 
        $protein, 
        $carbs, 
        $fat, 
        $fiber,
        $meal_category,
        $date,
        $id
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Food updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update food']);
    }
}

function deleteTemplateFood($pdo, $data) {
    $id = $data['id'] ?? '';
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Missing food ID']);
        return;
    }
    
    $sql = "DELETE FROM user_food_history 
            WHERE id = ? AND user_email IS NULL";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Food deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete food']);
    }
}

function addTemplateFood($pdo, $data) {
    $classification = $data['classification'] ?? '';
    $plan_duration = $data['plan_duration'] ?? 0;
    $food_name = $data['food_name'] ?? '';
    $serving_size = $data['serving_size'] ?? '';
    $calories = $data['calories'] ?? 0;
    $protein = $data['protein'] ?? 0;
    $carbs = $data['carbs'] ?? 0;
    $fat = $data['fat'] ?? 0;
    $fiber = $data['fiber'] ?? 0;
    $day_number = $data['day_number'] ?? 1;
    $meal_category = $data['meal_category'] ?? 'Breakfast';
    $emoji = $data['emoji'] ?? '🍽️';
    
    if (empty($classification) || empty($plan_duration) || empty($food_name)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    // Calculate date from day_number (days since epoch 1970-01-01)
    $date = date('Y-m-d', strtotime('1970-01-01 +' . ($day_number - 1) . ' days'));
    
    $sql = "INSERT INTO user_food_history 
            (user_email, date, meal_category, food_name, calories, serving_size, 
             protein, carbs, fat, fiber, emoji, is_mho_recommended, classification, plan_duration) 
            VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $date,
        $meal_category,
        $food_name,
        $calories,
        $serving_size,
        $protein,
        $carbs,
        $fat,
        $fiber,
        $emoji,
        $classification,
        $plan_duration
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Food added successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add food']);
    }
}
?>

