<?php
require_once 'who_growth_standards.php';

echo "<h2>Test with Exact Lookup Table Ages</h2>";

$who = new WHOGrowthStandards();

// Test with exact ages from lookup table
$testCases = [
    ['weight' => 3.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 9.0, 'age' => 12, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 15.0, 'age' => 35, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 17.0, 'age' => 71, 'sex' => 'Male', 'expected' => 'Normal'],
];

foreach ($testCases as $test) {
    echo "<h3>Weight: {$test['weight']} kg, Age: {$test['age']} months, Sex: {$test['sex']}</h3>";
    
    // Test the actual calculation
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    echo "<p><strong>Result:</strong></p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    // Check if lookup table was used
    $method = $result['method'] ?? 'unknown';
    echo "<p><strong>Method used:</strong> {$method}</p>";
    
    echo "<hr>";
}
?>
