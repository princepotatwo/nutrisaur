<?php
/**
 * Direct API Testing with Real Test Cases
 * This will test the API calculations with actual data
 */

// Include the API functions
require_once __DIR__ . '/api/nutritional_assessment_api.php';

echo "<h1>🧪 Direct API Testing with Real Calculations</h1>";
echo "<style>
    .test-case { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .z-scores { background: #e9ecef; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .malnutrition-category { font-weight: bold; padding: 5px 10px; border-radius: 3px; margin: 5px 0; }
    .undernutrition { background: #d1ecf1; color: #0c5460; }
    .overnutrition { background: #f8d7da; color: #721c24; }
    .normal { background: #d4edda; color: #155724; }
    .error-status { background: #f8d7da; color: #721c24; }
    .calculation { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

// Test cases with expected results
$testCases = [
    // CHILD TESTS
    [
        'name' => '2-year-old boy with SAM',
        'age' => 2,
        'weight' => 8.5,
        'height' => 85,
        'muac' => 10.5,
        'sex' => 'male',
        'is_pregnant' => 'No',
        'expected_status' => 'Severe Acute Malnutrition (SAM)',
        'expected_category' => 'Undernutrition',
        'description' => 'Should have W/H z-score < -3'
    ],
    [
        'name' => '3-year-old girl with MAM',
        'age' => 3,
        'weight' => 12.0,
        'height' => 95,
        'muac' => 12.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected_status' => 'Moderate Acute Malnutrition (MAM)',
        'expected_category' => 'Undernutrition',
        'description' => 'Should have W/H z-score between -3 and -2'
    ],
    [
        'name' => '4-year-old boy with Stunting',
        'age' => 4,
        'weight' => 15.0,
        'height' => 90,
        'muac' => 13.0,
        'sex' => 'male',
        'is_pregnant' => 'No',
        'expected_status' => 'Stunting (Chronic Malnutrition)',
        'expected_category' => 'Undernutrition',
        'description' => 'Should have H/A z-score < -2'
    ],
    [
        'name' => '5-year-old girl Normal',
        'age' => 5,
        'weight' => 18.0,
        'height' => 110,
        'muac' => 14.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected_status' => 'Normal',
        'expected_category' => 'Normal',
        'description' => 'Should have all z-scores normal'
    ],
    // PREGNANT WOMAN TESTS
    [
        'name' => 'Pregnant woman with Maternal Undernutrition',
        'age' => 25,
        'weight' => 45.0,
        'height' => 160,
        'muac' => 22.0,
        'sex' => 'female',
        'is_pregnant' => 'Yes',
        'expected_status' => 'Maternal Undernutrition (At-risk)',
        'expected_category' => 'Undernutrition',
        'description' => 'Should have MUAC < 23.0 cm'
    ],
    [
        'name' => 'Pregnant woman with Maternal At-risk',
        'age' => 28,
        'weight' => 50.0,
        'height' => 165,
        'muac' => 24.0,
        'sex' => 'female',
        'is_pregnant' => 'Yes',
        'expected_status' => 'Maternal At-risk',
        'expected_category' => 'Undernutrition',
        'description' => 'Should have MUAC 23.0-24.9 cm'
    ],
    [
        'name' => 'Normal Pregnant woman',
        'age' => 30,
        'weight' => 60.0,
        'height' => 170,
        'muac' => 26.0,
        'sex' => 'female',
        'is_pregnant' => 'Yes',
        'expected_status' => 'Normal',
        'expected_category' => 'Normal',
        'description' => 'Should have MUAC ≥ 25.0 cm'
    ],
    // ADULT TESTS
    [
        'name' => 'Adult with Severe Underweight',
        'age' => 35,
        'weight' => 40.0,
        'height' => 170,
        'muac' => 20.0,
        'sex' => 'male',
        'is_pregnant' => 'No',
        'expected_status' => 'Severe Underweight',
        'expected_category' => 'Undernutrition',
        'description' => 'Should have BMI < 16.0'
    ],
    [
        'name' => 'Adult with Normal BMI',
        'age' => 50,
        'weight' => 70.0,
        'height' => 175,
        'muac' => 28.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected_status' => 'Normal',
        'expected_category' => 'Normal',
        'description' => 'Should have BMI 18.5-24.9'
    ],
    [
        'name' => 'Adult with Obesity Class I',
        'age' => 60,
        'weight' => 90.0,
        'height' => 170,
        'muac' => 32.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected_status' => 'Obesity Class I',
        'expected_category' => 'Overnutrition',
        'description' => 'Should have BMI 30.0-34.9'
    ]
];

$totalTests = 0;
$passedTests = 0;

foreach ($testCases as $test) {
    $totalTests++;
    
    echo "<div class='test-case'>";
    echo "<h3>{$test['name']}</h3>";
    echo "<p><strong>Description:</strong> {$test['description']}</p>";
    echo "<p><strong>Expected Status:</strong> {$test['expected_status']}</p>";
    echo "<p><strong>Expected Category:</strong> {$test['expected_category']}</p>";
    
    // Create mock user data
    $user = [
        'birthday' => date('Y-m-d', strtotime("-{$test['age']} years")),
        'weight' => $test['weight'],
        'height' => $test['height'],
        'muac' => $test['muac'],
        'sex' => $test['sex'],
        'is_pregnant' => $test['is_pregnant']
    ];
    
    try {
        // Perform assessment
        $assessment = performNutritionalAssessment($user);
        
        // Check if results match expected
        $statusCorrect = $assessment['nutritional_status'] === $test['expected_status'];
        $categoryCorrect = $assessment['malnutrition_category'] === $test['expected_category'];
        $isCorrect = $statusCorrect && $categoryCorrect;
        
        if ($isCorrect) $passedTests++;
        
        $statusClass = $isCorrect ? 'success' : 'error';
        $statusIcon = $isCorrect ? '✅' : '❌';
        
        echo "<div class='$statusClass'>";
        
        // Show calculations for children
        if ($test['age'] < 18) {
            $age = $test['age'];
            $weight = $test['weight'];
            $height = $test['height'];
            $sex = $test['sex'];
            
            $bmi = calculateBMI($weight, $height);
            $whZScore = calculateWeightForHeightZScore($weight, $height, $age, $sex);
            $haZScore = calculateHeightForAgeZScore($height, $age, $sex);
            $bmiForAgeZScore = calculateBMIForAgeZScore($bmi, $age, $sex);
            
            echo "<div class='calculation'>";
            echo "<strong>Manual Calculations:</strong><br>";
            echo "BMI: " . round($bmi, 2) . "<br>";
            echo "W/H z-score: " . round($whZScore, 2) . "<br>";
            echo "H/A z-score: " . round($haZScore, 2) . "<br>";
            echo "BMI-for-Age z-score: " . round($bmiForAgeZScore, 2) . "<br>";
            echo "</div>";
        }
        
        // Show BMI for adults
        if ($test['age'] >= 18) {
            $bmi = calculateBMI($test['weight'], $test['height']);
            echo "<div class='calculation'>";
            echo "<strong>BMI Calculation:</strong> " . round($bmi, 2) . "<br>";
            echo "</div>";
        }
        
        echo "<div class='malnutrition-category " . getCategoryClass($assessment['malnutrition_category']) . "'>";
        echo "<strong>Result Status:</strong> {$assessment['nutritional_status']}";
        echo "</div>";
        
        echo "<div class='malnutrition-category " . getCategoryClass($assessment['malnutrition_category']) . "'>";
        echo "<strong>Category:</strong> {$assessment['malnutrition_category']}";
        echo "</div>";
        
        echo "<div class='malnutrition-category " . getCategoryClass($assessment['malnutrition_category']) . "'>";
        echo "<strong>Type:</strong> " . ($assessment['specific_condition'] ?? $assessment['type'] ?? 'N/A');
        echo "</div>";
        
        echo "<div class='malnutrition-category " . getCategoryClass($assessment['malnutrition_category']) . "'>";
        echo "<strong>Risk Level:</strong> {$assessment['risk_level']}";
        echo "</div>";
        
        echo "<p><strong>Description:</strong> {$assessment['description']}</p>";
        echo "<p><strong>Measurements Used:</strong> {$assessment['measurements_used']}</p>";
        echo "<p><strong>Cutoff Used:</strong> {$assessment['cutoff_used']}</p>";
        
        if (isset($assessment['z_scores'])) {
            echo "<div class='z-scores'>";
            echo "<strong>Z-Scores:</strong><br>";
            echo "Weight-for-Height: {$assessment['z_scores']['weight_for_height']}<br>";
            echo "Height-for-Age: {$assessment['z_scores']['height_for_age']}<br>";
            echo "BMI-for-Age: {$assessment['z_scores']['bmi_for_age']}";
            echo "</div>";
        }
        
        echo "<p><strong>Status:</strong> $statusIcon " . ($isCorrect ? 'CORRECT' : 'INCORRECT') . "</p>";
        
        if (!$isCorrect) {
            echo "<p><strong>Expected Status:</strong> {$test['expected_status']}</p>";
            echo "<p><strong>Expected Category:</strong> {$test['expected_category']}</p>";
            echo "<p><strong>Status Match:</strong> " . ($statusCorrect ? '✅' : '❌') . "</p>";
            echo "<p><strong>Category Match:</strong> " . ($categoryCorrect ? '✅' : '❌') . "</p>";
        }
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Status:</strong> ❌ FAILED</p>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Summary
echo "<div class='test-case success'>";
echo "<h2>📊 Test Summary</h2>";
echo "<p><strong>Total Tests:</strong> $totalTests</p>";
echo "<p><strong>Passed:</strong> $passedTests</p>";
echo "<p><strong>Failed:</strong> " . ($totalTests - $passedTests) . "</p>";
echo "<p><strong>Success Rate:</strong> " . round(($passedTests / $totalTests) * 100, 2) . "%</p>";

if ($passedTests === $totalTests) {
    echo "<p><strong>🎉 ALL TESTS PASSED! The API calculations are 100% correct!</strong></p>";
} else {
    echo "<p><strong>⚠️ Some tests failed. Please review the results above.</strong></p>";
}
echo "</div>";

function getCategoryClass($category) {
    switch($category) {
        case 'Undernutrition': return 'undernutrition';
        case 'Overnutrition': return 'overnutrition';
        case 'Normal': return 'normal';
        default: return 'error-status';
    }
}
?>
