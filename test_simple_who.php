<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing WHO Growth Standards...\n\n";

try {
    require_once 'who_growth_standards.php';
    echo "✅ WHO Growth Standards loaded successfully\n";
    
    $who = new WHOGrowthStandards();
    echo "✅ WHO Growth Standards class instantiated\n";
    
    // Test individual functions one by one
    echo "\n--- Testing Individual Functions ---\n";
    
    // Test weight-for-age (this one works)
    echo "1. Weight-for-Age: ";
    try {
        $wfa = $who->calculateWeightForAge(15.0, 48, 'Male');
        echo "✅ " . $wfa['classification'] . " (z-score: " . ($wfa['z_score'] ?? 'N/A') . ")\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Test height-for-age
    echo "2. Height-for-Age: ";
    try {
        $hfa = $who->calculateHeightForAge(100.0, 48, 'Male');
        echo "✅ " . $hfa['classification'] . " (z-score: " . ($hfa['z_score'] ?? 'N/A') . ")\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Test weight-for-height
    echo "3. Weight-for-Height: ";
    try {
        $wfh = $who->calculateWeightForHeight(15.0, 100.0, 'Male');
        echo "✅ " . $wfh['classification'] . " (z-score: " . ($wfh['z_score'] ?? 'N/A') . ")\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Test BMI-for-age
    echo "4. BMI-for-Age: ";
    try {
        $bmi = $who->calculateBMIForAge(15.0, 100.0, '2020-01-01', 'Male', '2024-01-01');
        echo "✅ " . $bmi['classification'] . " (z-score: " . ($bmi['z_score'] ?? 'N/A') . ")\n";
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
    
    // Test comprehensive assessment
    echo "\n--- Testing Comprehensive Assessment ---\n";
    try {
        $assessment = $who->getComprehensiveAssessment(15.0, 100.0, '2020-01-01', 'Male', '2024-01-01');
        if ($assessment['success']) {
            echo "✅ Comprehensive Assessment successful\n";
            echo "Weight-for-Age: " . $assessment['results']['weight_for_age']['classification'] . "\n";
            echo "Height-for-Age: " . $assessment['results']['height_for_age']['classification'] . "\n";
            echo "Weight-for-Height: " . $assessment['results']['weight_for_height']['classification'] . "\n";
            echo "BMI-for-Age: " . $assessment['results']['bmi_for_age']['classification'] . "\n";
        } else {
            echo "❌ Comprehensive Assessment failed: " . ($assessment['error'] ?? 'Unknown error') . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Error in Comprehensive Assessment: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
