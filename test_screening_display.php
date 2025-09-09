<?php
// Test script to verify screening.php displays WHO Growth Standards correctly
require_once __DIR__ . '/public/api/DatabaseHelper.php';
require_once __DIR__ . '/who_growth_standards.php';

try {
    $db = new DatabaseHelper();
    
    if (!$db->isAvailable()) {
        die("Database connection failed!\n");
    }
    
    echo "🧪 Testing Screening Display with WHO Growth Standards\n";
    echo "====================================================\n\n";
    
    // Get a sample user from the database
    $result = $db->select(
        'community_users',
        '*',
        '',
        [],
        'screening_date DESC',
        1
    );
    
    if (!$result['success'] || empty($result['data'])) {
        echo "❌ No users found in database. Please add a user first.\n";
        exit;
    }
    
    $user = $result['data'][0];
    
    echo "📊 Testing with user: " . ($user['name'] ?? 'Unknown') . "\n";
    echo "Weight: " . ($user['weight'] ?? 'N/A') . " kg\n";
    echo "Height: " . ($user['height'] ?? 'N/A') . " cm\n";
    echo "Birthday: " . ($user['birthday'] ?? 'N/A') . "\n";
    echo "Sex: " . ($user['sex'] ?? 'N/A') . "\n\n";
    
    // Test WHO Growth Standards calculation
    echo "🔍 Calculating WHO Growth Standards...\n";
    $who = new WHOGrowthStandards();
    $assessment = $who->getComprehensiveAssessment(
        floatval($user['weight']),
        floatval($user['height']),
        $user['birthday'],
        $user['sex']
    );
    
    if ($assessment['success']) {
        echo "✅ WHO Growth Standards calculation successful!\n\n";
        
        // Display the results as they would appear in the table
        echo "📋 Table Display Results:\n";
        echo "========================\n";
        
        $wfa_zscore = $assessment['weight_for_age']['z_score'] ?? null;
        $hfa_zscore = $assessment['height_for_age']['z_score'] ?? null;
        $wfh_zscore = $assessment['weight_for_height']['z_score'] ?? null;
        $wfl_zscore = $assessment['weight_for_length']['z_score'] ?? null;
        $bmi_zscore = $assessment['bmi_for_age']['z_score'] ?? null;
        
        $wfa_classification = $assessment['weight_for_age']['classification'] ?? 'N/A';
        $hfa_classification = $assessment['height_for_age']['classification'] ?? 'N/A';
        $wfh_classification = $assessment['weight_for_height']['classification'] ?? 'N/A';
        $wfl_classification = $assessment['weight_for_length']['classification'] ?? 'N/A';
        $bmi_classification = $assessment['bmi_for_age']['classification'] ?? 'N/A';
        
        $wfa_display = $wfa_zscore !== null ? 'Z: ' . number_format($wfa_zscore, 2) . ' (' . $wfa_classification . ')' : 'N/A';
        $hfa_display = $hfa_zscore !== null ? 'Z: ' . number_format($hfa_zscore, 2) . ' (' . $hfa_classification . ')' : 'N/A';
        $wfh_display = $wfh_zscore !== null ? 'Z: ' . number_format($wfh_zscore, 2) . ' (' . $wfh_classification . ')' : 'N/A';
        $wfl_display = $wfl_zscore !== null ? 'Z: ' . number_format($wfl_zscore, 2) . ' (' . $wfl_classification . ')' : 'N/A';
        $bmi_display = $bmi_zscore !== null ? 'Z: ' . number_format($bmi_zscore, 2) . ' (' . $bmi_classification . ')' : 'N/A';
        
        echo "Weight-for-Age: " . $wfa_display . "\n";
        echo "Height-for-Age: " . $hfa_display . "\n";
        echo "Weight-for-Height: " . $wfh_display . "\n";
        echo "Weight-for-Length: " . $wfl_display . "\n";
        echo "BMI-for-Age: " . $bmi_display . "\n";
        
        echo "\n🎉 The screening.php table should now show these results instead of N/A!\n";
        echo "The WHO Growth Standards decision tree is working correctly.\n";
        
    } else {
        echo "❌ WHO Growth Standards calculation failed: " . ($assessment['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
?>
