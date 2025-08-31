<?php
// Fix FCM tokens table structure
header('Content-Type: application/json');

// Include database configuration
require_once __DIR__ . '/../config.php';

// Get database connection
$conn = getDatabaseConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Check current table structure
    $stmt = $conn->prepare("DESCRIBE fcm_tokens");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasAutoIncrement = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id' && strpos($column['Extra'], 'auto_increment') !== false) {
            $hasAutoIncrement = true;
            break;
        }
    }
    
    if (!$hasAutoIncrement) {
        // Fix the table structure
        $sql = "ALTER TABLE fcm_tokens MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY";
        $conn->exec($sql);
        echo json_encode([
            'success' => true, 
            'message' => 'FCM tokens table structure fixed - added auto_increment to id field'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'FCM tokens table structure is already correct'
        ]);
    }
    
    // Show current table structure
    $stmt = $conn->prepare("DESCRIBE fcm_tokens");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Table structure check completed',
        'table_structure' => $columns
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
