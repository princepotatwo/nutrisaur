<?php
/**
 * Direct PHP Testing of Nutritional Assessment Calculations
 * This script tests the API functions directly without HTTP requests
 */

require_once __DIR__ . '/api/nutritional_assessment_api.php';

echo "<h1>🔬 Direct PHP Testing of Nutritional Assessment API</h1>";
echo "<p><strong>Nutritionist Verification:</strong> Testing calculations directly</p>";

// Test cases for direct calculation verification
$testCases = [
    // CHILDREN TESTS
    [
        'name' => 'Child with SAM (Severe Acute Malnutrition)',
        'age' => 2,
        'weight' => 8.5,
        'height' => 85,
        'muac' => 10.5,
        'sex' => 'male',
        'is_pregnant' => 'No',
        'expected' => 'Severe Acute Malnutrition (SAM)',
        'description' => '2-year-old boy with W/H z-score < -3'
    ],
    [
        'name' => 'Child with MAM (Moderate Acute Malnutrition)',
        'age' => 3,
        'weight' => 12.0,
        'height' => 95,
        'muac' => 12.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected' => 'Moderate Acute Malnutrition (MAM)',
        'description' => '3-year-old girl with W/H z-score between -3 and -2'
    ],
    [
        'name' => 'Child with Stunting (Chronic Malnutrition)',
        'age' => 4,
        'weight' => 15.0,
        'height' => 90,
        'muac' => 13.0,
        'sex' => 'male',
        'is_pregnant' => 'No',
        'expected' => 'Stunting (Chronic Malnutrition)',
        'description' => '4-year-old boy with H/A z-score < -2'
    ],
    [
        'name' => 'Normal Child',
        'age' => 5,
        'weight' => 18.0,
        'height' => 110,
        'muac' => 14.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected' => 'Normal',
        'description' => '5-year-old girl with all z-scores normal'
    ],
    // PREGNANT WOMEN TESTS
    [
        'name' => 'Pregnant Woman with Maternal Undernutrition',
        'age' => 25,
        'weight' => 45.0,
        'height' => 160,
        'muac' => 22.0,
        'sex' => 'female',
        'is_pregnant' => 'Yes',
        'expected' => 'Maternal Undernutrition (At-risk)',
        'description' => 'Pregnant woman with MUAC < 23.0 cm'
    ],
    [
        'name' => 'Pregnant Woman with Maternal At-risk',
        'age' => 28,
        'weight' => 50.0,
        'height' => 165,
        'muac' => 24.0,
        'sex' => 'female',
        'is_pregnant' => 'Yes',
        'expected' => 'Maternal At-risk',
        'description' => 'Pregnant woman with MUAC 23.0-24.9 cm'
    ],
    [
        'name' => 'Normal Pregnant Woman',
        'age' => 30,
        'weight' => 60.0,
        'height' => 170,
        'muac' => 26.0,
        'sex' => 'female',
        'is_pregnant' => 'Yes',
        'expected' => 'Normal',
        'description' => 'Pregnant woman with MUAC ≥ 25.0 cm'
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
        'expected' => 'Severe Underweight',
        'description' => '35-year-old man with BMI 13.8'
    ],
    [
        'name' => 'Adult with Moderate Underweight',
        'age' => 40,
        'weight' => 45.0,
        'height' => 170,
        'muac' => 22.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected' => 'Moderate Underweight',
        'description' => '40-year-old woman with BMI 15.6'
    ],
    [
        'name' => 'Adult with Mild Underweight',
        'age' => 45,
        'weight' => 50.0,
        'height' => 170,
        'muac' => 24.0,
        'sex' => 'male',
        'is_pregnant' => 'No',
        'expected' => 'Mild Underweight',
        'description' => '45-year-old man with BMI 17.3'
    ],
    [
        'name' => 'Normal Adult',
        'age' => 50,
        'weight' => 70.0,
        'height' => 175,
        'muac' => 28.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected' => 'Normal',
        'description' => '50-year-old woman with BMI 22.9'
    ],
    [
        'name' => 'Adult with Overweight',
        'age' => 55,
        'weight' => 80.0,
        'height' => 170,
        'muac' => 30.0,
        'sex' => 'male',
        'is_pregnant' => 'No',
        'expected' => 'Overweight',
        'description' => '55-year-old man with BMI 27.7'
    ],
    [
        'name' => 'Adult with Obesity Class I',
        'age' => 60,
        'weight' => 90.0,
        'height' => 170,
        'muac' => 32.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected' => 'Obesity Class I',
        'description' => '60-year-old woman with BMI 31.1'
    ],
    [
        'name' => 'Adult with Obesity Class II',
        'age' => 65,
        'weight' => 100.0,
        'height' => 170,
        'muac' => 35.0,
        'sex' => 'male',
        'is_pregnant' => 'No',
        'expected' => 'Obesity Class II',
        'description' => '65-year-old man with BMI 34.6'
    ],
    [
        'name' => 'Adult with Obesity Class III (Severe)',
        'age' => 70,
        'weight' => 120.0,
        'height' => 170,
        'muac' => 40.0,
        'sex' => 'female',
        'is_pregnant' => 'No',
        'expected' => 'Obesity Class III (Severe)',
        'description' => '70-year-old woman with BMI 41.5'
    ]
];

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
</style>";

$totalTests = 0;
$passedTests = 0;

foreach ($testCases as $test) {
    $totalTests++;
    
    echo "<div class='test-case'>";
    echo "<h3>{$test['name']}</h3>";
    echo "<p><strong>Description:</strong> {$test['description']}</p>";
    echo "<p><strong>Expected:</strong> {$test['expected']}</p>";
    
    try {
        // Create mock user data
        $user = [
            'birthday' => date('Y-m-d', strtotime("-{$test['age']} years")),
            'weight' => $test['weight'],
            'height' => $test['height'],
            'muac' => $test['muac'],
            'sex' => $test['sex'],
            'is_pregnant' => $test['is_pregnant']
        ];
        
        // Perform assessment
        $assessment = performNutritionalAssessment($user);
        
        $isCorrect = $assessment['nutritional_status'] === $test['expected'];
        if ($isCorrect) $passedTests++;
        
        $statusClass = $isCorrect ? 'success' : 'error';
        $statusIcon = $isCorrect ? '✅' : '❌';
        
        echo "<div class='$statusClass'>";
        echo "<div class='malnutrition-category " . getCategoryClass($assessment['malnutrition_category']) . "'>";
        echo "<strong>Result:</strong> {$assessment['nutritional_status']}";
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
        
        if (isset($assessment['bmi'])) {
            echo "<p><strong>BMI:</strong> {$assessment['bmi']}</p>";
        }
        
        echo "<p><strong>Status:</strong> $statusIcon " . ($isCorrect ? 'CORRECT' : 'INCORRECT') . "</p>";
        
        if (!$isCorrect) {
            echo "<p><strong>Expected:</strong> {$test['expected']}</p>";
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
    echo "<p><strong>🎉 ALL TESTS PASSED! The API is 100% accurate!</strong></p>";
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
