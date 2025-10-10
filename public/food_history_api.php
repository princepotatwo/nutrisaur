<?php
/**
 * Food History API
 * Handles CRUD operations for user food history data
 * Enables admin/BHW to monitor community nutrition intake
 */

require_once __DIR__ . "/../config.php";

// Set content type
header('Content-Type: application/json');

// Enable CORS for mobile app
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get action from query parameter
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            handleAddFood($pdo);
            break;
            
        case 'sync':
            handleBulkSync($pdo);
            break;
            
        case 'get_user_history':
            handleGetUserHistory($pdo);
            break;
            
        case 'get_all_users':
            handleGetAllUsers($pdo);
            break;
            
        case 'update':
            handleUpdateFood($pdo);
            break;
            
        case 'delete':
            handleDeleteFood($pdo);
            break;
            
        case 'flag_food':
            handleFlagFood($pdo);
            break;
            
        case 'flag_day':
            handleFlagDay($pdo);
            break;
            
        case 'unflag_food':
            handleUnflagFood($pdo);
            break;
            
        case 'unflag_day':
            handleUnflagDay($pdo);
            break;
            
        case 'update_serving_size':
            handleUpdateServingSize($pdo);
            break;
            
        case 'add_comment':
            handleAddComment($pdo);
            break;
            
        case 'get_flagged_dates':
            handleGetFlaggedDates($pdo);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Add single food entry
 */
function handleAddFood($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Validate required fields
    $required = ['user_email', 'date', 'meal_category', 'food_name', 'calories', 'serving_size'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate meal category
    $validMeals = ['Breakfast', 'Lunch', 'Dinner', 'Snacks'];
    if (!in_array($data['meal_category'], $validMeals)) {
        throw new Exception('Invalid meal category');
    }
    
    // Check if user exists
    $userCheck = $pdo->prepare("SELECT email FROM community_users WHERE email = ?");
    $userCheck->execute([$data['user_email']]);
    if (!$userCheck->fetch()) {
        throw new Exception('User not found');
    }
    
    // Insert food entry
    $sql = "INSERT INTO user_food_history 
            (user_email, date, meal_category, food_name, calories, serving_size, protein, carbs, fat, fiber) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $data['user_email'],
        $data['date'],
        $data['meal_category'],
        $data['food_name'],
        $data['calories'],
        $data['serving_size'],
        $data['protein'] ?? 0,
        $data['carbs'] ?? 0,
        $data['fat'] ?? 0,
        $data['fiber'] ?? 0
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Food entry added successfully',
            'id' => $pdo->lastInsertId()
        ]);
    } else {
        throw new Exception('Failed to add food entry');
    }
}

/**
 * Bulk sync multiple foods (used for migration)
 */
function handleBulkSync($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['user_email']) || !isset($data['foods'])) {
        throw new Exception('Invalid sync data');
    }
    
    $userEmail = $data['user_email'];
    $foods = $data['foods'];
    
    // Check if user exists
    $userCheck = $pdo->prepare("SELECT email FROM community_users WHERE email = ?");
    $userCheck->execute([$userEmail]);
    if (!$userCheck->fetch()) {
        throw new Exception('User not found');
    }
    
    $pdo->beginTransaction();
    
    try {
        $inserted = 0;
        $updated = 0;
        
        foreach ($foods as $food) {
            // Check if entry already exists
            $checkSql = "SELECT id FROM user_food_history 
                        WHERE user_email = ? AND date = ? AND meal_category = ? AND food_name = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([
                $userEmail,
                $food['date'],
                $food['meal_category'],
                $food['food_name']
            ]);
            
            if ($checkStmt->fetch()) {
                // Update existing entry
                $updateSql = "UPDATE user_food_history SET 
                             calories = ?, serving_size = ?, protein = ?, carbs = ?, fat = ?, fiber = ?
                             WHERE user_email = ? AND date = ? AND meal_category = ? AND food_name = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    $food['calories'],
                    $food['serving_size'],
                    $food['protein'] ?? 0,
                    $food['carbs'] ?? 0,
                    $food['fat'] ?? 0,
                    $food['fiber'] ?? 0,
                    $userEmail,
                    $food['date'],
                    $food['meal_category'],
                    $food['food_name']
                ]);
                $updated++;
            } else {
                // Insert new entry
                $insertSql = "INSERT INTO user_food_history 
                             (user_email, date, meal_category, food_name, calories, serving_size, protein, carbs, fat, fiber) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    $userEmail,
                    $food['date'],
                    $food['meal_category'],
                    $food['food_name'],
                    $food['calories'],
                    $food['serving_size'],
                    $food['protein'] ?? 0,
                    $food['carbs'] ?? 0,
                    $food['fat'] ?? 0,
                    $food['fiber'] ?? 0
                ]);
                $inserted++;
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Bulk sync completed',
            'inserted' => $inserted,
            'updated' => $updated
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get specific user's food history
 */
function handleGetUserHistory($pdo) {
    $userEmail = $_GET['user_email'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    
    if (empty($userEmail)) {
        throw new Exception('User email is required');
    }
    
    // Build query to include flag and comment data
    $sql = "SELECT *, 
            is_flagged, 
            is_day_flagged, 
            mho_comment, 
            flagged_by, 
            flagged_at 
            FROM user_food_history WHERE user_email = ?";
    $params = [$userEmail];
    
    if (!empty($startDate)) {
        $sql .= " AND date >= ?";
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $sql .= " AND date <= ?";
        $params[] = $endDate;
    }
    
    $sql .= " ORDER BY date DESC, meal_category, food_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ]);
}

/**
 * Get all users' food history (admin only)
 */
function handleGetAllUsers($pdo) {
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $barangay = $_GET['barangay'] ?? '';
    $minCalories = $_GET['min_calories'] ?? '';
    $maxCalories = $_GET['max_calories'] ?? '';
    
    // Build query with joins
    $sql = "SELECT 
                ufh.*,
                cu.name as user_name,
                cu.barangay,
                cu.municipality,
                cu.weight,
                cu.height,
                cu.bmi_for_age
            FROM user_food_history ufh
            JOIN community_users cu ON ufh.user_email = cu.email";
    
    $conditions = [];
    $params = [];
    
    if (!empty($startDate)) {
        $conditions[] = "ufh.date >= ?";
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $conditions[] = "ufh.date <= ?";
        $params[] = $endDate;
    }
    
    if (!empty($barangay)) {
        $conditions[] = "cu.barangay = ?";
        $params[] = $barangay;
    }
    
    if (!empty($minCalories)) {
        $conditions[] = "ufh.calories >= ?";
        $params[] = $minCalories;
    }
    
    if (!empty($maxCalories)) {
        $conditions[] = "ufh.calories <= ?";
        $params[] = $maxCalories;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY ufh.date DESC, cu.name, ufh.meal_category";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ]);
}

/**
 * Update food entry
 */
function handleUpdateFood($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('Food entry ID is required');
    }
    
    $id = $data['id'];
    unset($data['id']);
    
    // Build update query dynamically
    $fields = [];
    $params = [];
    
    $allowedFields = ['food_name', 'calories', 'serving_size', 'protein', 'carbs', 'fat', 'fiber', 'meal_category'];
    
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
    }
    
    if (empty($fields)) {
        throw new Exception('No valid fields to update');
    }
    
    $params[] = $id;
    
    $sql = "UPDATE user_food_history SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Food entry updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update food entry');
    }
}

/**
 * Delete food entry
 */
function handleDeleteFood($pdo) {
    $id = $_GET['id'] ?? $_POST['id'] ?? '';
    
    if (empty($id)) {
        throw new Exception('Food entry ID is required');
    }
    
    $sql = "DELETE FROM user_food_history WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Food entry deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete food entry');
    }
}

/**
 * Flag individual food item
 */
function handleFlagFood($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id']) || !isset($data['mho_email'])) {
        throw new Exception('Food ID and MHO email are required');
    }
    
    $id = $data['id'];
    $mhoEmail = $data['mho_email'];
    $comment = $data['comment'] ?? '';
    
    $sql = "UPDATE user_food_history SET 
            is_flagged = 1, 
            mho_comment = ?, 
            flagged_by = ?, 
            flagged_at = NOW() 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$comment, $mhoEmail, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Food item flagged successfully'
        ]);
    } else {
        throw new Exception('Failed to flag food item');
    }
}

/**
 * Flag entire day for a user
 */
function handleFlagDay($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['user_email']) || !isset($data['date']) || !isset($data['mho_email'])) {
        throw new Exception('User email, date, and MHO email are required');
    }
    
    $userEmail = $data['user_email'];
    $date = $data['date'];
    $mhoEmail = $data['mho_email'];
    $comment = $data['comment'] ?? '';
    
    $sql = "UPDATE user_food_history SET 
            is_day_flagged = 1, 
            mho_comment = ?, 
            flagged_by = ?, 
            flagged_at = NOW() 
            WHERE user_email = ? AND date = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$comment, $mhoEmail, $userEmail, $date]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Day flagged successfully',
            'affected_rows' => $stmt->rowCount()
        ]);
    } else {
        throw new Exception('Failed to flag day');
    }
}

/**
 * Unflag individual food item
 */
function handleUnflagFood($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id'])) {
        throw new Exception('Food ID is required');
    }
    
    $id = $data['id'];
    
    $sql = "UPDATE user_food_history SET 
            is_flagged = 0, 
            mho_comment = NULL, 
            flagged_by = NULL, 
            flagged_at = NULL 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Food item unflagged successfully'
        ]);
    } else {
        throw new Exception('Failed to unflag food item');
    }
}

/**
 * Unflag entire day for a user
 */
function handleUnflagDay($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['user_email']) || !isset($data['date'])) {
        throw new Exception('User email and date are required');
    }
    
    $userEmail = $data['user_email'];
    $date = $data['date'];
    
    $sql = "UPDATE user_food_history SET 
            is_day_flagged = 0, 
            mho_comment = NULL, 
            flagged_by = NULL, 
            flagged_at = NULL 
            WHERE user_email = ? AND date = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$userEmail, $date]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Day unflagged successfully',
            'affected_rows' => $stmt->rowCount()
        ]);
    } else {
        throw new Exception('Failed to unflag day');
    }
}

/**
 * Update serving size of food item
 */
function handleUpdateServingSize($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id']) || !isset($data['serving_size'])) {
        throw new Exception('Food ID and serving size are required');
    }
    
    $id = $data['id'];
    $servingSize = $data['serving_size'];
    
    $sql = "UPDATE user_food_history SET serving_size = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$servingSize, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Serving size updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update serving size');
    }
}

/**
 * Add or update comment
 */
function handleAddComment($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['id']) || !isset($data['comment']) || !isset($data['mho_email'])) {
        throw new Exception('Food ID, comment, and MHO email are required');
    }
    
    $id = $data['id'];
    $comment = $data['comment'];
    $mhoEmail = $data['mho_email'];
    
    $sql = "UPDATE user_food_history SET 
            mho_comment = ?, 
            flagged_by = ?, 
            flagged_at = NOW() 
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$comment, $mhoEmail, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully'
        ]);
    } else {
        throw new Exception('Failed to add comment');
    }
}

/**
 * Get flagged dates for a user
 */
function handleGetFlaggedDates($pdo) {
    $userEmail = $_GET['user_email'] ?? '';
    
    if (empty($userEmail)) {
        throw new Exception('User email is required');
    }
    
    $sql = "SELECT DISTINCT date, is_day_flagged, mho_comment, flagged_by, flagged_at 
            FROM user_food_history 
            WHERE user_email = ? AND (is_flagged = 1 OR is_day_flagged = 1)
            ORDER BY date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userEmail]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $results,
        'count' => count($results)
    ]);
}
?>
