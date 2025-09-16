<?php
require_once 'who_growth_standards.php';

echo "<h2>Simple Test - Check Lookup Table Usage</h2>";

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
    
    // Test lookup table directly
    $lookup = $who->getWeightForAgeBoysLookup();
    echo "<p><strong>Available ages in lookup:</strong> " . implode(', ', array_keys($lookup)) . "</p>";
    
    $closestAge = $who->findClosestAge($lookup, $test['age']);
    echo "<p><strong>Closest age found:</strong> " . ($closestAge ?? 'null') . "</p>";
    
    if ($closestAge !== null) {
        $ranges = $lookup[$closestAge];
        echo "<p><strong>Ranges for age {$closestAge}:</strong></p>";
        foreach ($ranges as $category => $range) {
            $inRange = ($test['weight'] >= $range['min'] && $test['weight'] <= $range['max']);
            echo "<p>&nbsp;&nbsp;{$category}: {$range['min']}-{$range['max']} | Weight {$test['weight']} in range: " . ($inRange ? 'YES' : 'NO') . "</p>";
        }
    }
    
    // Test the actual calculation
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    echo "<p><strong>Result:</strong></p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    echo "<hr>";
}
?>
