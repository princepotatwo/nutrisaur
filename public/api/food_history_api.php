<?php
/**
 * Food History API
 * Handles CRUD operations for user food history data
 * Enables admin/BHW to monitor community nutrition intake
 */

require_once '../../config.php';

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
            
        case 'delete_all_user_foods':
            handleDeleteAllUserFoods($pdo);
            break;
            
    case 'delete_meal_foods':
        handleDeleteMealFoods($pdo);
        break;
    
    case 'debug_user_foods':
        handleDebugUserFoods($pdo);
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
            
        case 'get_recommended_foods':
            handleGetRecommendedFoods($pdo);
            break;
            
        case 'add_recommended_to_meal':
            handleAddRecommendedToMeal($pdo);
            break;
            
        case 'get_user_count_by_classification':
            handleGetUserCountByClassification($pdo);
            break;
            
        case 'add_bulk_recommendation':
            handleAddBulkRecommendation($pdo);
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
    $required = ['user_email', 'meal_category', 'food_name', 'calories', 'serving_size'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Special validation for date field
    if (!isset($data['is_mho_recommended']) || $data['is_mho_recommended'] != 1) {
        // For regular foods, date is required
        if (!isset($data['date']) || empty($data['date'])) {
            throw new Exception("Missing required field: date");
        }
    }
    // For MHO recommended foods, date can be null
    
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
    
        // Handle MHO recommended foods separately
        if (isset($data['is_mho_recommended']) && $data['is_mho_recommended'] == 1) {
            // For MHO recommended foods, store with NULL date and mark as recommended
            $sql = "INSERT INTO user_food_history 
                    (user_email, date, meal_category, food_name, calories, serving_size, protein, carbs, fat, fiber, is_mho_recommended, emoji) 
                    VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $data['user_email'],
                $data['meal_category'],
                $data['food_name'],
                $data['calories'],
                $data['serving_size'],
                $data['protein'] ?? 0,
                $data['carbs'] ?? 0,
                $data['fat'] ?? 0,
                $data['fiber'] ?? 0,
                $data['emoji'] ?? null
            ]);
        } else {
        // Regular food history entry
        $sql = "INSERT INTO user_food_history 
                (user_email, date, meal_category, food_name, calories, serving_size, protein, carbs, fat, fiber, is_mho_recommended, emoji) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)";
        
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
            $data['fiber'] ?? 0,
            $data['emoji'] ?? null
        ]);
    }
    
    if ($result) {
        $message = (isset($data['is_mho_recommended']) && $data['is_mho_recommended'] == 1) 
            ? 'MHO recommended food added successfully' 
            : 'Food entry added successfully';
        
        echo json_encode([
            'success' => true,
            'message' => $message,
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
                             calories = ?, serving_size = ?, protein = ?, carbs = ?, fat = ?, fiber = ?, emoji = ?
                             WHERE user_email = ? AND date = ? AND meal_category = ? AND food_name = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    $food['calories'],
                    $food['serving_size'],
                    $food['protein'] ?? 0,
                    $food['carbs'] ?? 0,
                    $food['fat'] ?? 0,
                    $food['fiber'] ?? 0,
                    $food['emoji'] ?? null,
                    $userEmail,
                    $food['date'],
                    $food['meal_category'],
                    $food['food_name']
                ]);
                $updated++;
            } else {
                // Insert new entry
                $insertSql = "INSERT INTO user_food_history 
                             (user_email, date, meal_category, food_name, calories, serving_size, protein, carbs, fat, fiber, emoji) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
                    $food['fiber'] ?? 0,
                    $food['emoji'] ?? null
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
    // Support both ID-based deletion and parameter-based deletion
    $id = $_GET['id'] ?? $_POST['id'] ?? '';
    $userEmail = $_GET['user_email'] ?? $_POST['user_email'] ?? '';
    $date = $_GET['date'] ?? $_POST['date'] ?? '';
    $foodName = $_GET['food_name'] ?? $_POST['food_name'] ?? '';
    $mealCategory = $_GET['meal_category'] ?? $_POST['meal_category'] ?? '';
    
    // Debug logging
    error_log("Delete Food Debug - ID: " . $id . ", User: " . $userEmail . ", Date: " . $date . ", Food: " . $foodName . ", Meal: " . $mealCategory);
    
    if (!empty($id)) {
        // ID-based deletion (for web interface)
        $sql = "DELETE FROM user_food_history WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$id]);
        error_log("Delete by ID - SQL executed, result: " . ($result ? 'true' : 'false'));
    } else if (!empty($userEmail) && !empty($date) && !empty($foodName) && !empty($mealCategory)) {
        // Parameter-based deletion (for Android app)
        // First, let's check what exists in the database
        $checkSql = "SELECT id, user_email, date, food_name, meal_category FROM user_food_history WHERE user_email = ? AND date = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$userEmail, $date]);
        $existingRecords = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Delete Debug - Found " . count($existingRecords) . " records for user " . $userEmail . " on date " . $date);
        foreach ($existingRecords as $record) {
            error_log("Delete Debug - Record: ID=" . $record['id'] . ", Food=" . $record['food_name'] . ", Meal=" . $record['meal_category']);
        }
        
        $sql = "DELETE FROM user_food_history WHERE user_email = ? AND date = ? AND food_name = ? AND meal_category = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$userEmail, $date, $foodName, $mealCategory]);
        error_log("Delete by params - SQL executed, result: " . ($result ? 'true' : 'false') . ", rows affected: " . $stmt->rowCount());
    } else {
        throw new Exception('Either food entry ID or (user_email, date, food_name, meal_category) are required');
    }
    
    if ($result) {
        $deletedCount = $stmt->rowCount();
        echo json_encode([
            'success' => true,
            'message' => 'Food entry deleted successfully',
            'deleted_count' => $deletedCount
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

/**
 * Handle deleting all foods for a user on a specific date
 */
function handleDeleteAllUserFoods($pdo) {
    try {
        $userEmail = $_GET['user_email'] ?? '';
        $date = $_GET['date'] ?? '';
        
        if (empty($userEmail) || empty($date)) {
            throw new Exception('User email and date are required');
        }
        
        // Delete all foods for the user on the specified date
        $sql = "DELETE FROM user_food_history WHERE user_email = ? AND date = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$userEmail, $date]);
        
        if ($result) {
            $deletedCount = $stmt->rowCount();
            echo json_encode([
                'success' => true,
                'message' => "Deleted {$deletedCount} food entries",
                'deleted_count' => $deletedCount
            ]);
        } else {
            throw new Exception('Failed to delete foods');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Debug function to show all foods for a user on a specific date
 */
function handleDebugUserFoods($pdo) {
    try {
        $userEmail = $_GET['user_email'] ?? '';
        $date = $_GET['date'] ?? '';
        
        if (empty($userEmail) || empty($date)) {
            throw new Exception('User email and date are required');
        }
        
        // Get all foods for the user on the specified date
        $sql = "SELECT id, user_email, date, food_name, meal_category, calories, serving_size FROM user_food_history WHERE user_email = ? AND date = ? ORDER BY meal_category, food_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userEmail, $date]);
        $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => "Found " . count($foods) . " foods for user on " . $date,
            'user_email' => $userEmail,
            'date' => $date,
            'foods' => $foods
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Handle deleting all foods for a specific meal category
 */
function handleDeleteMealFoods($pdo) {
    try {
        $userEmail = $_GET['user_email'] ?? '';
        $date = $_GET['date'] ?? '';
        $mealCategory = $_GET['meal_category'] ?? '';
        
        if (empty($userEmail) || empty($date) || empty($mealCategory)) {
            throw new Exception('User email, date, and meal category are required');
        }
        
        // Delete all foods for the user on the specified date and meal category
        $sql = "DELETE FROM user_food_history WHERE user_email = ? AND date = ? AND meal_category = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$userEmail, $date, $mealCategory]);
        
        if ($result) {
            $deletedCount = $stmt->rowCount();
            echo json_encode([
                'success' => true,
                'message' => "Deleted {$deletedCount} food entries from {$mealCategory}",
                'deleted_count' => $deletedCount
            ]);
        } else {
            throw new Exception('Failed to delete meal foods');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Get MHO recommended foods for a user
 */
function handleGetRecommendedFoods($pdo) {
    try {
        $userEmail = $_GET['user_email'] ?? '';
        
        if (empty($userEmail)) {
            throw new Exception('User email is required');
        }
        
        // First, get the user's classification from community_users
        $userSql = "SELECT * FROM community_users WHERE email = ?";
        $userStmt = $pdo->prepare($userSql);
        $userStmt->execute([$userEmail]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Determine user's classification from their BMI or WHO standards
        $userClassification = determineUserClassification($user);
        
        if (empty($userClassification)) {
            // If no classification determined, return empty result
            echo json_encode([
                'success' => true,
                'message' => 'No recommendations available - user classification not determined',
                'data' => [],
                'count' => 0
            ]);
            return;
        }
        
        // Determine which WHO standard to use based on user's data
        $whoStandard = determineWHOStandard($user);
        
        if (empty($whoStandard)) {
            // Fallback to BMI-for-age
            $whoStandard = 'bmi-for-age';
        }
        
        // Create combined classification key
        $combinedClassification = $whoStandard . '-' . $userClassification;
        
        // Get recommended foods from mho_food_templates table based on user's classification
        // Default to 7-day plan
        $planDuration = 7;
        
        $sql = "SELECT id, day_number, meal_category, food_name, calories, serving_size,
                       protein, carbs, fat, fiber, emoji, classification, plan_duration
                FROM mho_food_templates 
                WHERE classification = ? AND plan_duration = ?
                ORDER BY day_number, meal_category, food_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$combinedClassification, $planDuration]);
        $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group foods by meal category
        $groupedFoods = [];
        foreach ($foods as $food) {
            $mealCategory = $food['meal_category'];
            if (!isset($groupedFoods[$mealCategory])) {
                $groupedFoods[$mealCategory] = [];
            }
            $groupedFoods[$mealCategory][] = $food;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Recommended foods retrieved successfully',
            'data' => $groupedFoods,
            'count' => count($foods),
            'user_classification' => $classification
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Determine user's classification based on their screening data
 */
function determineUserClassification($user) {
    // Check if user has WHO standard classifications
    $whoClassifications = [
        'bmi-for-age' => $user['bmi-for-age'] ?? '',
        'weight-for-age' => $user['weight-for-age'] ?? '',
        'height-for-age' => $user['height-for-age'] ?? '',
        'weight-for-height' => $user['weight-for-height'] ?? ''
    ];
    
    // Priority: BMI-for-age > Weight-for-height > Weight-for-age
    if (!empty($whoClassifications['bmi-for-age'])) {
        return normalizeClassification($whoClassifications['bmi-for-age']);
    }
    
    if (!empty($whoClassifications['weight-for-height'])) {
        return normalizeClassification($whoClassifications['weight-for-height']);
    }
    
    if (!empty($whoClassifications['weight-for-age'])) {
        return normalizeClassification($whoClassifications['weight-for-age']);
    }
    
    // Fallback: Calculate BMI classification for adults
    $weight = floatval($user['weight'] ?? 0);
    $height = floatval($user['height'] ?? 0);
    
    if ($weight > 0 && $height > 0) {
        $heightInMeters = $height / 100;
        $bmi = $weight / ($heightInMeters * $heightInMeters);
        
        if ($bmi < 18.5) return 'underweight';
        if ($bmi < 25) return 'normal';
        if ($bmi < 30) return 'overweight';
        if ($bmi < 40) return 'obese';
        return 'severely_obese';
    }
    
    return ''; // No classification determined
}

/**
 * Determine which WHO standard to use based on user's data
 */
function determineWHOStandard($user) {
    // Check if user has WHO standard classifications
    $whoClassifications = [
        'bmi-for-age' => $user['bmi-for-age'] ?? '',
        'weight-for-age' => $user['weight-for-age'] ?? '',
        'height-for-age' => $user['height-for-age'] ?? '',
        'weight-for-height' => $user['weight-for-height'] ?? ''
    ];
    
    // Priority order for WHO standards
    $priority = ['bmi-for-age', 'weight-for-height', 'weight-for-age', 'height-for-age'];
    
    foreach ($priority as $standard) {
        if (!empty($whoClassifications[$standard])) {
            return $standard;
        }
    }
    
    // Fallback: determine by age
    $ageInMonths = calculateAgeInMonths($user['birthday'] ?? '1970-01-01');
    
    if ($ageInMonths < 24) {
        // Under 2 years: use weight-for-height
        return 'weight-for-height';
    } elseif ($ageInMonths < 60) {
        // 2-5 years: use weight-for-height or BMI-for-age
        return 'bmi-for-age';
    } else {
        // 5+ years: use BMI-for-age
        return 'bmi-for-age';
    }
}

/**
 * Calculate age in months
 */
function calculateAgeInMonths($birthDate, $screeningDate = null) {
    if (!$screeningDate) {
        $screeningDate = date('Y-m-d');
    }
    
    $birth = new DateTime($birthDate);
    $screen = new DateTime($screeningDate);
    $diff = $birth->diff($screen);
    
    return ($diff->y * 12) + $diff->m;
}

/**
 * Normalize classification names to match template categories
 */
function normalizeClassification($classification) {
    $classification = strtolower(trim($classification));
    
    // Map WHO classifications to template categories
    $mapping = [
        'normal' => 'normal',
        'underweight' => 'underweight',
        'severely underweight' => 'severely_underweight',
        'overweight' => 'overweight',
        'obese' => 'obese',
        'severely obese' => 'severely_obese',
        'obesity' => 'obese',
        'wasted' => 'wasted',
        'severely wasted' => 'severely_wasted',
        'stunted' => 'stunted',
        'severely stunted' => 'severely_stunted',
        'tall' => 'tall'
    ];
    
    return $mapping[$classification] ?? $classification;
}

function handleAddRecommendedToMeal($pdo) {
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('Invalid JSON data');
        }
        
        $userEmail = $data['user_email'] ?? '';
        $date = $data['date'] ?? '';
        $recommendedFoodId = $data['recommended_food_id'] ?? '';
        
        if (empty($userEmail) || empty($date) || empty($recommendedFoodId)) {
            throw new Exception('Missing required fields');
        }
        
        // Get the recommended food details (only those with date IS NULL)
        $sql = "SELECT * FROM user_food_history WHERE id = ? AND user_email = ? AND is_mho_recommended = 1 AND date IS NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$recommendedFoodId, $userEmail]);
        $recommendedFood = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$recommendedFood) {
            throw new Exception('Recommended food not found');
        }
        
        // Allow duplicate MHO recommended foods - users can add the same food multiple times
        // Commented out duplicate check to allow multiple entries of the same MHO recommended food
        /*
        // Check if already added to this date
        $sql = "SELECT COUNT(*) FROM user_food_history 
                WHERE user_email = ? AND date = ? AND meal_category = ? AND food_name = ? AND is_mho_recommended = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userEmail, $date, $recommendedFood['meal_category'], $recommendedFood['food_name']]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            throw new Exception('This food is already added to your meal plan for this date');
        }
        */
        
        // Create a copy of the recommended food with real date and is_mho_recommended = 0
        $sql = "INSERT INTO user_food_history 
                (user_email, date, meal_category, food_name, calories, serving_size, protein, carbs, fat, fiber, is_mho_recommended) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $userEmail,
            $date,
            $recommendedFood['meal_category'],
            $recommendedFood['food_name'],
            $recommendedFood['calories'],
            $recommendedFood['serving_size'],
            $recommendedFood['protein'],
            $recommendedFood['carbs'],
            $recommendedFood['fat'],
            $recommendedFood['fiber']
        ]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Recommended food added to your meal plan successfully',
                'id' => $pdo->lastInsertId()
            ]);
        } else {
            throw new Exception('Failed to add recommended food to meal plan');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleGetUserCountByClassification($pdo) {
    try {
        $classificationType = $_GET['classification_type'] ?? '';
        $category = $_GET['category'] ?? '';
        
        if (empty($classificationType) || empty($category)) {
            throw new Exception('Classification type and category are required');
        }
        
        error_log("🔍 DEBUG: Classification query - Type: $classificationType, Category: $category");
        
        // Get all users from community_users table
        $sql = "SELECT email, weight, height, birthday, sex, screening_date FROM community_users WHERE weight IS NOT NULL AND height IS NOT NULL AND birthday IS NOT NULL ORDER BY screening_date DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("🔍 DEBUG: Found " . count($users) . " users in community_users table");
        
        if (empty($users)) {
            error_log("🔍 DEBUG: No users found in community_users table");
            echo json_encode([
                'success' => true,
                'count' => 0
            ]);
            return;
        }
        
        // Use WHO Growth Standards to calculate classifications
        require_once __DIR__ . '/../../who_growth_standards.php';
        $who = new WHOGrowthStandards();
        
        $matchingUsers = 0;
        $processedUsers = 0;
        $eligibleUsers = 0;
        $debugCount = 0;
        
        foreach ($users as $user) {
            try {
                $processedUsers++;
                
                // Calculate age in months
                $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                
                // Debug first few users
                if ($debugCount < 3) {
                    error_log("🔍 DEBUG: User $processedUsers - Email: {$user['email']}, Age: " . round($ageInMonths/12, 1) . " years ($ageInMonths months), Weight: {$user['weight']}, Height: {$user['height']}");
                }
                
                // Check if user is eligible for this classification type
                $isEligible = false;
                switch ($classificationType) {
                    case 'bmi_adult':
                        $isEligible = ($ageInMonths >= 228); // 19+ years
                        break;
                    case 'bmi_children':
                        $isEligible = ($ageInMonths >= 60 && $ageInMonths < 228); // 5-18 years
                        break;
                    case 'weight_for_age':
                        $isEligible = ($ageInMonths >= 0 && $ageInMonths <= 71); // 0-5 years
                        break;
                    case 'height_for_age':
                        $isEligible = ($ageInMonths >= 0 && $ageInMonths <= 71); // 0-5 years
                        break;
                }
                
                if ($debugCount < 3) {
                    error_log("🔍 DEBUG: User $processedUsers - Eligible for $classificationType: " . ($isEligible ? 'YES' : 'NO'));
                }
                
                if (!$isEligible) continue;
                $eligibleUsers++;
                
                // Get comprehensive assessment
                $assessment = $who->getComprehensiveAssessment(
                    floatval($user['weight']),
                    floatval($user['height']),
                    $user['birthday'],
                    $user['sex'],
                    $user['screening_date'] ?? null
                );
                
                if ($assessment['success'] && isset($assessment['results'])) {
                    $results = $assessment['results'];
                    $classification = null;
                    
                    switch ($classificationType) {
                        case 'bmi_adult':
                            // Calculate BMI for adults
                            $bmi = floatval($user['weight']) / pow(floatval($user['height']) / 100, 2);
                            if ($bmi < 18.5) $classification = 'Underweight';
                            else if ($bmi < 25) $classification = 'Normal';
                            else if ($bmi < 30) $classification = 'Overweight';
                            else $classification = 'Obese';
                            
                            if ($debugCount < 3) {
                                error_log("🔍 DEBUG: User $processedUsers - BMI Adult: $bmi, Classification: $classification");
                            }
                            break;
                        case 'bmi_children':
                            if (isset($results['bmi_for_age']['classification'])) {
                                $classification = $results['bmi_for_age']['classification'];
                            }
                            
                            if ($debugCount < 3) {
                                error_log("🔍 DEBUG: User $processedUsers - BMI Children Classification: $classification");
                            }
                            break;
                        case 'weight_for_age':
                            if (isset($results['weight_for_age']['classification'])) {
                                $classification = $results['weight_for_age']['classification'];
                            }
                            
                            if ($debugCount < 3) {
                                error_log("🔍 DEBUG: User $processedUsers - Weight for Age Classification: $classification");
                            }
                            break;
                        case 'height_for_age':
                            if (isset($results['height_for_age']['classification'])) {
                                $classification = $results['height_for_age']['classification'];
                            }
                            
                            if ($debugCount < 3) {
                                error_log("🔍 DEBUG: User $processedUsers - Height for Age Classification: $classification");
                            }
                            break;
                    }
                    
                    // Check if classification matches the requested category (case-insensitive)
                    if ($classification && strtolower($classification) === strtolower($category)) {
                        $matchingUsers++;
                        if ($debugCount < 3) {
                            error_log("🔍 DEBUG: User $processedUsers - MATCH! Classification: $classification, Category: $category");
                        }
                    } else {
                        if ($debugCount < 3) {
                            error_log("🔍 DEBUG: User $processedUsers - NO MATCH. Classification: $classification, Category: $category");
                        }
                    }
                } else {
                    if ($debugCount < 3) {
                        error_log("🔍 DEBUG: User $processedUsers - Assessment failed: " . ($assessment['error'] ?? 'Unknown error'));
                    }
                }
                
                $debugCount++;
            } catch (Exception $e) {
                // Skip users with processing errors
                continue;
            }
        }
        
        error_log("🔍 DEBUG: Final Results - Processed: $processedUsers, Eligible: $eligibleUsers, Matching: $matchingUsers");
        
        echo json_encode([
            'success' => true,
            'count' => $matchingUsers
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

function handleAddBulkRecommendation($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            throw new Exception('Invalid JSON data');
        }
        
        // Validate required fields
        $required = ['classification_type', 'category', 'meal_category', 'food_name', 'calories', 'serving_size'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Get all users from community_users table
        $sql = "SELECT email, weight, height, birthday, sex, screening_date FROM community_users WHERE weight IS NOT NULL AND height IS NOT NULL AND birthday IS NOT NULL ORDER BY screening_date DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($users)) {
            throw new Exception('No users found in the system');
        }
        
        // Use WHO Growth Standards to calculate classifications
        require_once __DIR__ . '/../../who_growth_standards.php';
        $who = new WHOGrowthStandards();
        
        $matchingUsers = [];
        
        foreach ($users as $user) {
            try {
                // Calculate age in months
                $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
                
                // Check if user is eligible for this classification type
                $isEligible = false;
                switch ($data['classification_type']) {
                    case 'bmi_adult':
                        $isEligible = ($ageInMonths >= 228); // 19+ years
                        break;
                    case 'bmi_children':
                        $isEligible = ($ageInMonths >= 60 && $ageInMonths < 228); // 5-18 years
                        break;
                    case 'weight_for_age':
                        $isEligible = ($ageInMonths >= 0 && $ageInMonths <= 71); // 0-5 years
                        break;
                    case 'height_for_age':
                        $isEligible = ($ageInMonths >= 0 && $ageInMonths <= 71); // 0-5 years
                        break;
                }
                
                if (!$isEligible) continue;
                
                // Get comprehensive assessment
                $assessment = $who->getComprehensiveAssessment(
                    floatval($user['weight']),
                    floatval($user['height']),
                    $user['birthday'],
                    $user['sex'],
                    $user['screening_date'] ?? null
                );
                
                if ($assessment['success'] && isset($assessment['results'])) {
                    $results = $assessment['results'];
                    $classification = null;
                    
                    switch ($data['classification_type']) {
                        case 'bmi_adult':
                            // Calculate BMI for adults
                            $bmi = floatval($user['weight']) / pow(floatval($user['height']) / 100, 2);
                            if ($bmi < 18.5) $classification = 'Underweight';
                            else if ($bmi < 25) $classification = 'Normal';
                            else if ($bmi < 30) $classification = 'Overweight';
                            else $classification = 'Obese';
                            break;
                        case 'bmi_children':
                            if (isset($results['bmi_for_age']['classification'])) {
                                $classification = $results['bmi_for_age']['classification'];
                            }
                            break;
                        case 'weight_for_age':
                            if (isset($results['weight_for_age']['classification'])) {
                                $classification = $results['weight_for_age']['classification'];
                            }
                            break;
                        case 'height_for_age':
                            if (isset($results['height_for_age']['classification'])) {
                                $classification = $results['height_for_age']['classification'];
                            }
                            break;
                    }
                    
                    // Check if classification matches the requested category (case-insensitive)
                    if ($classification && strtolower($classification) === strtolower($data['category'])) {
                        $matchingUsers[] = $user['email'];
                    }
                }
            } catch (Exception $e) {
                // Skip users with processing errors
                continue;
            }
        }
        
        if (empty($matchingUsers)) {
            throw new Exception('No users found matching the selected classification');
        }
        
        // Insert recommended food for each matching user
        $insertSql = "INSERT INTO user_food_history 
                      (user_email, date, meal_category, food_name, calories, serving_size, protein, carbs, fat, fiber, is_mho_recommended) 
                      VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $insertStmt = $pdo->prepare($insertSql);
        $affectedUsers = 0;
        
        foreach ($matchingUsers as $userEmail) {
            try {
                $result = $insertStmt->execute([
                    $userEmail,
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
                    $affectedUsers++;
                }
            } catch (Exception $e) {
                // Log error but continue with other users
                error_log("Failed to add recommendation for user $userEmail: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully added recommendation to $affectedUsers users",
            'affected_users' => $affectedUsers
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
