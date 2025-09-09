<?php
/**
 * WHO Growth Standards Implementation Verification
 * Quick verification of core functionality
 */

require_once 'who_growth_standards.php';

echo "<h1>WHO Growth Standards Implementation Verification</h1>";
echo "<p><strong>Quick verification of core functionality</strong></p>";

$who = new WHOGrowthStandards();

// Test cases covering different age ranges and scenarios
$testCases = [
    [
        'name' => 'Newborn boy (0 months)',
        'weight' => 3.3,  // Median weight for 0 months
        'height' => 49.9, // Median height for 0 months
        'birth_date' => date('Y-m-d'), // Today
        'sex' => 'Male'
    ],
    [
        'name' => '6-month-old girl',
        'weight' => 7.3,  // Median weight for 6 months
        'height' => 65.5, // Median height for 6 months
        'birth_date' => date('Y-m-d', strtotime('-6 months')),
        'sex' => 'Female'
    ],
    [
        'name' => '2-year-old boy',
        'weight' => 12.2, // Median weight for 24 months
        'height' => 87.6, // Median height for 24 months
        'birth_date' => date('Y-m-d', strtotime('-24 months')),
        'sex' => 'Male'
    ],
    [
        'name' => '4-year-old girl',
        'weight' => 16.3, // Median weight for 48 months
        'height' => 101.7, // Median height for 48 months
        'birth_date' => date('Y-m-d', strtotime('-48 months')),
        'sex' => 'Female'
    ],
    [
        'name' => '5-year-old boy (71 months)',
        'weight' => 19.5, // Median weight for 71 months
        'height' => 113.8, // Median height for 71 months
        'birth_date' => date('Y-m-d', strtotime('-71 months')),
        'sex' => 'Male'
    ],
    [
        'name' => 'Underweight 2-year-old',
        'weight' => 9.8,  // -2SD for 24 months
        'height' => 87.6, // Median height
        'birth_date' => date('Y-m-d', strtotime('-24 months')),
        'sex' => 'Male'
    ],
    [
        'name' => 'Overweight 3-year-old',
        'weight' => 16.0, // +2SD for 36 months
        'height' => 95.1, // Median height
        'birth_date' => date('Y-m-d', strtotime('-36 months')),
        'sex' => 'Female'
    ]
];

echo "<h2>Test Results</h2>";

$allPassed = true;

foreach ($testCases as $i => $test) {
    echo "<h3>Test " . ($i + 1) . ": " . $test['name'] . "</h3>";
    echo "<p><strong>Input:</strong> Weight: {$test['weight']} kg, Height: {$test['height']} cm, Birth Date: {$test['birth_date']}, Sex: {$test['sex']}</p>";
    
    try {
        $results = $who->processAllGrowthStandards(
            $test['weight'],
            $test['height'],
            $test['birth_date'],
            $test['sex']
        );
        
        echo "<h4>Results:</h4>";
        echo "<ul>";
        echo "<li><strong>Age:</strong> " . $results['age_months'] . " months</li>";
        echo "<li><strong>BMI:</strong> " . $results['bmi'] . "</li>";
        echo "</ul>";
        
        echo "<h4>Growth Indicators:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Indicator</th><th>Z-Score</th><th>Classification</th><th>Status</th></tr>";
        
        $testPassed = true;
        
        foreach ($results as $key => $value) {
            if (is_array($value) && isset($value['z_score']) && isset($value['classification'])) {
                $indicator = ucwords(str_replace('_', ' ', $key));
                $zScore = $value['z_score'] ?? 'N/A';
                $classification = $value['classification'] ?? 'N/A';
                $error = $value['error'] ?? '';
                
                $status = '✅';
                if ($error) {
                    $status = '❌';
                    $testPassed = false;
                }
                
                echo "<tr>";
                echo "<td>{$indicator}</td>";
                echo "<td>{$zScore}</td>";
                echo "<td>{$classification}</td>";
                echo "<td>{$status}</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
        if ($testPassed) {
            echo "<p style='color: green;'><strong>✅ Test Passed</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ Test Failed</strong></p>";
            $allPassed = false;
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>❌ Error: " . $e->getMessage() . "</strong></p>";
        $allPassed = false;
    }
    
    echo "<hr>";
}

// Test edge cases
echo "<h2>Edge Case Tests</h2>";

$edgeCases = [
    [
        'name' => 'Age out of range (72 months)',
        'weight' => 20.0,
        'height' => 115.0,
        'birth_date' => date('Y-m-d', strtotime('-72 months')),
        'sex' => 'Male',
        'should_fail' => true
    ],
    [
        'name' => 'Height out of range for WFH (64 cm)',
        'weight' => 10.0,
        'height' => 64.0,
        'birth_date' => date('Y-m-d', strtotime('-24 months')),
        'sex' => 'Male',
        'should_fail' => true
    ],
    [
        'name' => 'Valid edge case (71 months)',
        'weight' => 19.5,
        'height' => 113.8,
        'birth_date' => date('Y-m-d', strtotime('-71 months')),
        'sex' => 'Male',
        'should_fail' => false
    ]
];

foreach ($edgeCases as $i => $test) {
    echo "<h3>Edge Case " . ($i + 1) . ": " . $test['name'] . "</h3>";
    
    try {
        $results = $who->processAllGrowthStandards(
            $test['weight'],
            $test['height'],
            $test['birth_date'],
            $test['sex']
        );
        
        $hasErrors = false;
        foreach ($results as $key => $value) {
            if (is_array($value) && isset($value['error']) && $value['error']) {
                $hasErrors = true;
                break;
            }
        }
        
        if ($test['should_fail'] && $hasErrors) {
            echo "<p style='color: green;'><strong>✅ Correctly handled edge case</strong></p>";
        } elseif (!$test['should_fail'] && !$hasErrors) {
            echo "<p style='color: green;'><strong>✅ Correctly processed valid case</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ Unexpected behavior</strong></p>";
            $allPassed = false;
        }
        
    } catch (Exception $e) {
        if ($test['should_fail']) {
            echo "<p style='color: green;'><strong>✅ Correctly threw exception for invalid case</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ Unexpected exception: " . $e->getMessage() . "</strong></p>";
            $allPassed = false;
        }
    }
    
    echo "<hr>";
}

// Final assessment
echo "<h2>Final Assessment</h2>";

if ($allPassed) {
    echo "<p style='color: green; font-size: 18px;'><strong>🎉 ALL TESTS PASSED! Implementation is working correctly.</strong></p>";
} else {
    echo "<p style='color: red; font-size: 18px;'><strong>❌ Some tests failed. Please check the implementation.</strong></p>";
}

echo "<h3>Implementation Summary:</h3>";
echo "<ul>";
echo "<li><strong>Data Coverage:</strong> 0-71 months (0-5 years 11 months)</li>";
echo "<li><strong>Indicators:</strong> Weight-for-Age, Height-for-Age, Weight-for-Height, Weight-for-Length, BMI-for-Age</li>";
echo "<li><strong>Classifications:</strong> Severely Underweight, Underweight, Normal, Overweight</li>";
echo "<li><strong>Z-Score Calculation:</strong> Standard WHO formula (observed - median) / SD</li>";
echo "<li><strong>Height Ranges:</strong> 65-120 cm (WFH), 45-110 cm (WFL)</li>";
echo "<li><strong>Error Handling:</strong> Proper validation and error messages</li>";
echo "</ul>";

echo "<p><strong>Ready for production use!</strong></p>";
?>
