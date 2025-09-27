<?php
/**
 * Test script to verify BMI-for-age classification logic
 */

require_once __DIR__ . '/../who_growth_standards.php';

echo "🧪 Testing BMI-for-age classification logic...\n\n";

try {
    $who = new WHOGrowthStandards();
    
    // Test cases with different z-scores
    $testCases = [
        ['z_score' => -2.0, 'expected' => 'Underweight', 'description' => 'Z-score -2.0 (should be Underweight)'],
        ['z_score' => -1.0, 'expected' => 'Normal', 'description' => 'Z-score -1.0 (should be Normal)'],
        ['z_score' => 0.0, 'expected' => 'Normal', 'description' => 'Z-score 0.0 (should be Normal)'],
        ['z_score' => 1.0, 'expected' => 'Normal', 'description' => 'Z-score 1.0 (should be Normal)'],
        ['z_score' => 1.2, 'expected' => 'Overweight', 'description' => 'Z-score 1.2 (should be Overweight)'],
        ['z_score' => 1.7, 'expected' => 'Obese', 'description' => 'Z-score 1.7 (should be Obese)'],
        ['z_score' => 2.0, 'expected' => 'Obese', 'description' => 'Z-score 2.0 (should be Obese)']
    ];
    
    echo "📊 Testing BMI classification with new percentile-based logic:\n";
    echo "Expected ranges:\n";
    echo "  - < 5th percentile (z-score < -1.645) → Underweight\n";
    echo "  - 5th – 85th percentile (z-score -1.645 to +1.036) → Normal\n";
    echo "  - 85th – 95th percentile (z-score +1.036 to +1.645) → Overweight\n";
    echo "  - > 95th percentile (z-score > +1.645) → Obese\n\n";
    
    foreach ($testCases as $test) {
        $result = $who->getBMIClassification($test['z_score']);
        $status = ($result === $test['expected']) ? '✅' : '❌';
        
        echo "$status {$test['description']}\n";
        echo "   Expected: {$test['expected']}, Got: $result\n";
        
        if ($result !== $test['expected']) {
            echo "   ⚠️  MISMATCH!\n";
        }
        echo "\n";
    }
    
    // Test with actual user data
    echo "🧪 Testing with actual user data...\n";
    
    // Test case: 16-year-old female, BMI 22.9 (should be Normal, not Obese)
    $testUser = [
        'weight' => 55,
        'height' => 155,
        'birthday' => '2008-01-01', // 16 years old
        'sex' => 'Female',
        'screening_date' => '2024-01-01'
    ];
    
    echo "Test user: 16-year-old female, 55kg, 155cm, BMI 22.9\n";
    
    $assessment = $who->getComprehensiveAssessment(
        $testUser['weight'],
        $testUser['height'],
        $testUser['birthday'],
        $testUser['sex'],
        $testUser['screening_date']
    );
    
    if ($assessment['success']) {
        $bmiResult = $assessment['results']['bmi_for_age'] ?? null;
        if ($bmiResult) {
            echo "BMI-for-age result:\n";
            echo "  - Z-score: " . ($bmiResult['z_score'] ?? 'N/A') . "\n";
            echo "  - Classification: " . ($bmiResult['classification'] ?? 'N/A') . "\n";
            echo "  - BMI: " . ($bmiResult['bmi'] ?? 'N/A') . "\n";
        } else {
            echo "❌ No BMI-for-age result found\n";
        }
    } else {
        echo "❌ Assessment failed: " . ($assessment['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
