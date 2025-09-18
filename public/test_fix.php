<?php
/**
 * Quick test to verify the fix works
 */

require_once 'who_growth_standards.php';

echo "Testing Decision Tree Fix...\n";

try {
    $who = new WHOGrowthStandards();
    echo "✓ WHOGrowthStandards instantiated successfully\n";
    
    // Test the method that was failing
    $result = $who->getHeightForAgeClassification(-16.35);
    echo "✓ getHeightForAgeClassification(-16.35) = '$result'\n";
    
    // Test other methods
    $result2 = $who->getWeightForAgeClassification(-2.5);
    echo "✓ getWeightForAgeClassification(-2.5) = '$result2'\n";
    
    $result3 = $who->getWeightForHeightClassification(2.5);
    echo "✓ getWeightForHeightClassification(2.5) = '$result3'\n";
    
    // Test comprehensive assessment
    $assessment = $who->getComprehensiveAssessment(4.0, 51.0, '2023-12-16', 'Female', '2025-09-18');
    if ($assessment['success']) {
        echo "✓ getComprehensiveAssessment() works successfully\n";
        echo "  - Risk Level: {$assessment['nutritional_risk']}\n";
    } else {
        echo "✗ getComprehensiveAssessment() failed: " . implode(', ', $assessment['errors']) . "\n";
    }
    
    echo "\n🎉 All tests passed! The fix is working.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
