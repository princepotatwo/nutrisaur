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
        case 'bulk_import_foods':
            bulkImportFoods($pdo, $postData);
            break;
        case 'delete_all_template_foods':
            deleteAllTemplateFoods($pdo, $postData);
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
    
    // Query mho_food_templates table - clean and simple
    $sql = "SELECT id, classification, plan_duration, day_number, meal_category, 
                   food_name, calories, serving_size, protein, carbs, fat, fiber, emoji,
                   created_at, updated_at
            FROM mho_food_templates 
            WHERE classification = ? 
            AND plan_duration = ?
            ORDER BY day_number, meal_category";
    
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
    
    $sql = "SELECT id, classification, plan_duration, day_number, meal_category, 
                   food_name, calories, serving_size, protein, carbs, fat, fiber, emoji,
                   created_at, updated_at
            FROM mho_food_templates 
            WHERE id = ?";
    
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
    
    $sql = "UPDATE mho_food_templates 
            SET food_name = ?, 
                serving_size = ?, 
                calories = ?, 
                protein = ?, 
                carbs = ?, 
                fat = ?, 
                fiber = ?,
                meal_category = ?,
                day_number = ?
            WHERE id = ?";
    
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
        $day_number,
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
    
    $sql = "DELETE FROM mho_food_templates WHERE id = ?";
    
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
    
    $sql = "INSERT INTO mho_food_templates 
            (classification, plan_duration, day_number, meal_category, food_name, 
             calories, serving_size, protein, carbs, fat, fiber, emoji) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $classification,
        $plan_duration,
        $day_number,
        $meal_category,
        $food_name,
        $calories,
        $serving_size,
        $protein,
        $carbs,
        $fat,
        $fiber,
        $emoji
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Food added successfully', 'id' => $pdo->lastInsertId()]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add food']);
    }
}

function bulkImportFoods($pdo, $data) {
    $classification = $data['classification'] ?? '';
    $duration = $data['duration'] ?? 0;
    $foods = $data['foods'] ?? [];
    
    if (empty($classification) || empty($duration) || empty($foods)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }
    
    $imported_count = 0;
    $errors = [];
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        foreach ($foods as $food) {
            $food_name = $food['food_name'] ?? '';
            $serving_size = $food['serving_size'] ?? '';
            $calories = $food['calories'] ?? 0;
            $protein = $food['protein'] ?? 0;
            $carbs = $food['carbs'] ?? 0;
            $fat = $food['fat'] ?? 0;
            $fiber = $food['fiber'] ?? 0;
            $day_number = $food['day_number'] ?? 1;
            $meal_category = $food['meal_category'] ?? 'Breakfast';
            
            if (empty($food_name)) {
                $errors[] = "Missing food_name for row";
                continue;
            }
            
            // Add to mho_food_templates table (like Add Food to Template button)
            $insertSql = "INSERT INTO mho_food_templates 
                         (classification, plan_duration, day_number, meal_category, food_name, 
                          calories, serving_size, protein, carbs, fat, fiber, emoji) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insertStmt = $pdo->prepare($insertSql);
            $result = $insertStmt->execute([
                $classification,
                $duration,
                $day_number,
                $meal_category,
                $food_name,
                $calories,
                $serving_size,
                $protein,
                $carbs,
                $fat,
                $fiber,
                '🍽️' // Default emoji
            ]);
            
            if ($result) {
                $imported_count++;
            } else {
                $errors[] = "Failed to import food: $food_name";
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Bulk import to template completed',
            'imported_count' => $imported_count,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $e->getMessage()]);
    }
}

function deleteAllTemplateFoods($pdo, $data) {
    $classification = $data['classification'] ?? '';
    $duration = $data['duration'] ?? 0;
    
    if (empty($classification) || empty($duration)) {
        echo json_encode(['success' => false, 'error' => 'Missing classification or duration']);
        return;
    }
    
    // Count how many foods will be deleted
    $countSql = "SELECT COUNT(*) as count FROM mho_food_templates 
                 WHERE classification = ? AND plan_duration = ?";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$classification, $duration]);
    $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
    $deletedCount = $countResult['count'];
    
    // Delete all foods matching the classification and duration
    $sql = "DELETE FROM mho_food_templates 
            WHERE classification = ? AND plan_duration = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$classification, $duration]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => "Successfully deleted all foods for $classification ($duration days)",
            'deleted_count' => $deletedCount
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete foods']);
    }
}
?>

