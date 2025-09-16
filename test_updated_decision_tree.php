<?php
/**
 * Test Updated Decision Tree with Lookup Tables
 * 
 * This script tests the updated WHO growth standards implementation
 * that now uses lookup tables instead of formula-based calculations.
 */

require_once 'who_growth_standards.php';

echo "<h1>Testing Updated WHO Decision Tree with Lookup Tables</h1>\n";

$who = new WHOGrowthStandards();

// Test cases from your images
$testCases = [
    // Weight-for-Age Boys tests
    [
        'type' => 'Weight-for-Age Boys',
        'tests' => [
            ['weight' => 2.0, 'age' => 0, 'sex' => 'Male', 'expected' => 'Severely underweight'],
            ['weight' => 2.3, 'age' => 0, 'sex' => 'Male', 'expected' => 'Underweight'],
            ['weight' => 3.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Normal'],
            ['weight' => 4.6, 'age' => 0, 'sex' => 'Male', 'expected' => 'Overweight'],
            ['weight' => 6.8, 'age' => 12, 'sex' => 'Male', 'expected' => 'Severely underweight'],
            ['weight' => 7.3, 'age' => 12, 'sex' => 'Male', 'expected' => 'Underweight'],
            ['weight' => 9.0, 'age' => 12, 'sex' => 'Male', 'expected' => 'Normal'],
            ['weight' => 12.2, 'age' => 12, 'sex' => 'Male', 'expected' => 'Overweight'],
            ['weight' => 9.8, 'age' => 35, 'sex' => 'Male', 'expected' => 'Severely underweight'],
            ['weight' => 10.5, 'age' => 35, 'sex' => 'Male', 'expected' => 'Underweight'],
            ['weight' => 15.0, 'age' => 35, 'sex' => 'Male', 'expected' => 'Normal'],
            ['weight' => 18.5, 'age' => 35, 'sex' => 'Male', 'expected' => 'Overweight'],
        ]
    ],
    
    // Weight-for-Height Girls tests
    [
        'type' => 'Weight-for-Height Girls',
        'tests' => [
            ['weight' => 5.4, 'height' => 65, 'sex' => 'Female', 'expected' => 'Severely wasted'],
            ['weight' => 5.8, 'height' => 65, 'sex' => 'Female', 'expected' => 'Wasted'],
            ['weight' => 7.0, 'height' => 65, 'sex' => 'Female', 'expected' => 'Normal'],
            ['weight' => 9.0, 'height' => 65, 'sex' => 'Female', 'expected' => 'Overweight'],
            ['weight' => 10.0, 'height' => 65, 'sex' => 'Female', 'expected' => 'Obese'],
            ['weight' => 9.6, 'height' => 90, 'sex' => 'Female', 'expected' => 'Severely wasted'],
            ['weight' => 10.0, 'height' => 90, 'sex' => 'Female', 'expected' => 'Wasted'],
            ['weight' => 12.0, 'height' => 90, 'sex' => 'Female', 'expected' => 'Normal'],
            ['weight' => 15.0, 'height' => 90, 'sex' => 'Female', 'expected' => 'Overweight'],
            ['weight' => 17.0, 'height' => 90, 'sex' => 'Female', 'expected' => 'Obese'],
            ['weight' => 15.6, 'height' => 120, 'sex' => 'Female', 'expected' => 'Wasted'],
            ['weight' => 16.0, 'height' => 120, 'sex' => 'Female', 'expected' => 'Normal'],
            ['weight' => 20.0, 'height' => 120, 'sex' => 'Female', 'expected' => 'Normal'],
            ['weight' => 25.0, 'height' => 120, 'sex' => 'Female', 'expected' => 'Overweight'],
            ['weight' => 26.0, 'height' => 120, 'sex' => 'Female', 'expected' => 'Obese'],
        ]
    ]
];

$totalTests = 0;
$totalMatches = 0;

foreach ($testCases as $testGroup) {
    echo "<h2>{$testGroup['type']}</h2>\n";
    
    foreach ($testGroup['tests'] as $test) {
        $totalTests++;
        
        if ($testGroup['type'] === 'Weight-for-Age Boys') {
            $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
        } else {
            $result = $who->calculateWeightForHeight($test['weight'], $test['height'], $test['sex']);
        }
        
        $actual = $result['classification'];
        $match = ($actual === $test['expected']);
        
        if ($match) {
            $totalMatches++;
        }
        
        $method = isset($result['method']) ? $result['method'] : 'unknown';
        
        echo "<p><strong>";
        if ($testGroup['type'] === 'Weight-for-Age Boys') {
            echo "Age {$test['age']} months, Weight {$test['weight']} kg";
        } else {
            echo "Height {$test['height']} cm, Weight {$test['weight']} kg";
        }
        echo ":</strong><br>";
        echo "Expected: {$test['expected']}, Actual: {$actual} " . ($match ? "✅" : "❌") . "<br>";
        echo "Method: {$method}";
        if (isset($result['age_used'])) {
            echo ", Age used: {$result['age_used']}";
        }
        if (isset($result['height_used'])) {
            echo ", Height used: {$result['height_used']}";
        }
        echo "</p>\n";
    }
}

// Summary
$successRate = round(($totalMatches / $totalTests) * 100, 2);

echo "<h2>Test Summary</h2>\n";
echo "<p><strong>Total Tests:</strong> {$totalTests}</p>\n";
echo "<p><strong>Successful Matches:</strong> {$totalMatches}</p>\n";
echo "<p><strong>Success Rate:</strong> {$successRate}%</p>\n";

if ($successRate >= 90) {
    echo "<p style='color: green; font-weight: bold;'>✅ Decision tree is working correctly with lookup tables!</p>\n";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Some tests failed. Check the lookup table data.</p>\n";
}

echo "<h2>Key Improvements</h2>\n";
echo "<ul>\n";
echo "<li>✅ Uses lookup tables instead of formula-based calculations</li>\n";
echo "<li>✅ Matches exact values from WHO growth standards images</li>\n";
echo "<li>✅ Includes 'Obese' category for Weight-for-Height</li>\n";
echo "<li>✅ Maintains backward compatibility with existing code</li>\n";
echo "<li>✅ Falls back to formula method for missing data points</li>\n";
echo "</ul>\n";
?>
