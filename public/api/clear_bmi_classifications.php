<?php
/**
 * API endpoint to clear BMI-for-age classifications
 * This will force the system to recalculate BMI-for-age classifications using the updated logic
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/DatabaseAPI.php';

try {
    $db = new DatabaseAPI();
    
    if (!$db->isDatabaseAvailable()) {
        echo json_encode(['success' => false, 'message' => 'Database not available']);
        exit;
    }
    
    // Clear BMI-for-age classifications from the database
    // This will force the system to recalculate them with the new percentile-based logic
    $pdo = $db->getPDO();
    
    $sql = "UPDATE community_users 
            SET bmi_for_age_classification = NULL, 
                bmi_for_age_z_score = NULL,
                bmi_for_age_percentile = NULL
            WHERE bmi_for_age_classification IS NOT NULL";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        $affectedRows = $stmt->rowCount();
        echo json_encode([
            'success' => true, 
            'message' => "Successfully cleared BMI-for-age classifications from $affectedRows records",
            'affected_rows' => $affectedRows,
            'note' => 'The system will now recalculate BMI-for-age classifications using the new percentile-based logic'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error clearing BMI-for-age classifications']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
