<?php
/**
 * Script to clear BMI-for-age classifications so they get recalculated with new percentile logic
 * This will force the system to recalculate BMI-for-age classifications using the updated logic
 */

require_once __DIR__ . '/api/DatabaseAPI.php';

try {
    $db = new DatabaseAPI();
    
    // Clear BMI-for-age classifications from the database
    // This will force the system to recalculate them with the new percentile-based logic
    
    $sql = "UPDATE screening_responses 
            SET bmi_for_age_classification = NULL, 
                bmi_for_age_z_score = NULL,
                bmi_for_age_percentile = NULL
            WHERE bmi_for_age_classification IS NOT NULL";
    
    $result = $db->query($sql);
    
    if ($result) {
        echo "✅ Successfully cleared BMI-for-age classifications from database\n";
        echo "The system will now recalculate BMI-for-age classifications using the new percentile-based logic\n";
        echo "Next time you view the screening table, the classifications will be updated\n";
    } else {
        echo "❌ Error clearing BMI-for-age classifications\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
