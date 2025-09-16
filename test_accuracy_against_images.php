<?php
/**
 * Test Accuracy Against WHO Growth Standards Images
 * 
 * This script tests the updated decision tree against the exact values
 * shown in the WHO growth standards images provided.
 */

require_once 'who_growth_standards.php';

echo "<h1>Testing Accuracy Against WHO Growth Standards Images</h1>\n";

$who = new WHOGrowthStandards();

// Test cases from the boys' image (Weight-for-Age)
echo "<h2>Boys Weight-for-Age Tests (from image)</h2>\n";

$boysTests = [
    // Age 0 months
    ['weight' => 2.0, 'age' => 0, 'expected' => 'Severely underweight', 'description' => 'Age 0, 2.0 kg (should be < 2.1)'],
    ['weight' => 2.1, 'age' => 0, 'expected' => 'Underweight', 'description' => 'Age 0, 2.1 kg (boundary)'],
    ['weight' => 2.3, 'age' => 0, 'expected' => 'Underweight', 'description' => 'Age 0, 2.3 kg (2.2-2.4 range)'],
    ['weight' => 2.4, 'age' => 0, 'expected' => 'Underweight', 'description' => 'Age 0, 2.4 kg (boundary)'],
    ['weight' => 2.5, 'age' => 0, 'expected' => 'Normal', 'description' => 'Age 0, 2.5 kg (boundary)'],
    ['weight' => 3.5, 'age' => 0, 'expected' => 'Normal', 'description' => 'Age 0, 3.5 kg (2.5-4.4 range)'],
    ['weight' => 4.4, 'age' => 0, 'expected' => 'Normal', 'description' => 'Age 0, 4.4 kg (boundary)'],
    ['weight' => 4.5, 'age' => 0, 'expected' => 'Overweight', 'description' => 'Age 0, 4.5 kg (boundary)'],
    ['weight' => 5.0, 'age' => 0, 'expected' => 'Overweight', 'description' => 'Age 0, 5.0 kg (> 4.5)'],
    
    // Age 12 months
    ['weight' => 6.8, 'age' => 12, 'expected' => 'Severely underweight', 'description' => 'Age 12, 6.8 kg (should be < 6.9)'],
    ['weight' => 6.9, 'age' => 12, 'expected' => 'Underweight', 'description' => 'Age 12, 6.9 kg (boundary)'],
    ['weight' => 7.3, 'age' => 12, 'expected' => 'Underweight', 'description' => 'Age 12, 7.3 kg (7.0-7.6 range)'],
    ['weight' => 7.6, 'age' => 12, 'expected' => 'Underweight', 'description' => 'Age 12, 7.6 kg (boundary)'],
    ['weight' => 7.7, 'age' => 12, 'expected' => 'Normal', 'description' => 'Age 12, 7.7 kg (boundary)'],
    ['weight' => 9.0, 'age' => 12, 'expected' => 'Normal', 'description' => 'Age 12, 9.0 kg (7.7-12.0 range)'],
    ['weight' => 12.0, 'age' => 12, 'expected' => 'Normal', 'description' => 'Age 12, 12.0 kg (boundary)'],
    ['weight' => 12.1, 'age' => 12, 'expected' => 'Overweight', 'description' => 'Age 12, 12.1 kg (boundary)'],
    ['weight' => 13.0, 'age' => 12, 'expected' => 'Overweight', 'description' => 'Age 12, 13.0 kg (> 12.1)'],
    
    // Age 35 months
    ['weight' => 9.8, 'age' => 35, 'expected' => 'Severely underweight', 'description' => 'Age 35, 9.8 kg (should be < 9.9)'],
    ['weight' => 9.9, 'age' => 35, 'expected' => 'Underweight', 'description' => 'Age 35, 9.9 kg (boundary)'],
    ['weight' => 10.5, 'age' => 35, 'expected' => 'Underweight', 'description' => 'Age 35, 10.5 kg (10.0-11.1 range)'],
    ['weight' => 11.1, 'age' => 35, 'expected' => 'Underweight', 'description' => 'Age 35, 11.1 kg (boundary)'],
    ['weight' => 11.2, 'age' => 35, 'expected' => 'Normal', 'description' => 'Age 35, 11.2 kg (boundary)'],
    ['weight' => 15.0, 'age' => 35, 'expected' => 'Normal', 'description' => 'Age 35, 15.0 kg (11.2-18.1 range)'],
    ['weight' => 18.1, 'age' => 35, 'expected' => 'Normal', 'description' => 'Age 35, 18.1 kg (boundary)'],
    ['weight' => 18.2, 'age' => 35, 'expected' => 'Overweight', 'description' => 'Age 35, 18.2 kg (boundary)'],
    ['weight' => 20.0, 'age' => 35, 'expected' => 'Overweight', 'description' => 'Age 35, 20.0 kg (> 18.2)'],
    
    // Age 71 months
    ['weight' => 13.8, 'age' => 71, 'expected' => 'Severely underweight', 'description' => 'Age 71, 13.8 kg (should be < 13.9)'],
    ['weight' => 13.9, 'age' => 71, 'expected' => 'Underweight', 'description' => 'Age 71, 13.9 kg (boundary)'],
    ['weight' => 15.0, 'age' => 71, 'expected' => 'Underweight', 'description' => 'Age 71, 15.0 kg (14.0-15.6 range)'],
    ['weight' => 15.6, 'age' => 71, 'expected' => 'Underweight', 'description' => 'Age 71, 15.6 kg (boundary)'],
    ['weight' => 15.7, 'age' => 71, 'expected' => 'Normal', 'description' => 'Age 71, 15.7 kg (boundary)'],
    ['weight' => 17.0, 'age' => 71, 'expected' => 'Normal', 'description' => 'Age 71, 17.0 kg (15.7-18.1 range)'],
    ['weight' => 18.1, 'age' => 71, 'expected' => 'Normal', 'description' => 'Age 71, 18.1 kg (boundary)'],
    ['weight' => 18.2, 'age' => 71, 'expected' => 'Overweight', 'description' => 'Age 71, 18.2 kg (boundary)'],
    ['weight' => 20.0, 'age' => 71, 'expected' => 'Overweight', 'description' => 'Age 71, 20.0 kg (> 18.2)'],
];

$boysMatches = 0;
$boysTotal = count($boysTests);

foreach ($boysTests as $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
    $actual = $result['classification'];
    $match = ($actual === $test['expected']);
    
    if ($match) {
        $boysMatches++;
    }
    
    $method = isset($result['method']) ? $result['method'] : 'unknown';
    $ageUsed = isset($result['age_used']) ? $result['age_used'] : $test['age'];
    
    echo "<p><strong>{$test['description']}</strong><br>";
    echo "Expected: {$test['expected']}, Actual: {$actual} " . ($match ? "✅" : "❌") . "<br>";
    echo "Method: {$method}, Age used: {$ageUsed}</p>\n";
}

// Test cases from the girls' image (Weight-for-Height)
echo "<h2>Girls Weight-for-Height Tests (from image)</h2>\n";

$girlsTests = [
    // Height 65 cm
    ['weight' => 5.4, 'height' => 65, 'expected' => 'Severely wasted', 'description' => 'Height 65 cm, 5.4 kg (should be < 5.5)'],
    ['weight' => 5.5, 'height' => 65, 'expected' => 'Wasted', 'description' => 'Height 65 cm, 5.5 kg (boundary)'],
    ['weight' => 5.8, 'height' => 65, 'expected' => 'Wasted', 'description' => 'Height 65 cm, 5.8 kg (5.6-6.0 range)'],
    ['weight' => 6.0, 'height' => 65, 'expected' => 'Wasted', 'description' => 'Height 65 cm, 6.0 kg (boundary)'],
    ['weight' => 6.1, 'height' => 65, 'expected' => 'Normal', 'description' => 'Height 65 cm, 6.1 kg (boundary)'],
    ['weight' => 7.0, 'height' => 65, 'expected' => 'Normal', 'description' => 'Height 65 cm, 7.0 kg (6.1-8.7 range)'],
    ['weight' => 8.7, 'height' => 65, 'expected' => 'Normal', 'description' => 'Height 65 cm, 8.7 kg (boundary)'],
    ['weight' => 8.8, 'height' => 65, 'expected' => 'Overweight', 'description' => 'Height 65 cm, 8.8 kg (boundary)'],
    ['weight' => 9.7, 'height' => 65, 'expected' => 'Overweight', 'description' => 'Height 65 cm, 9.7 kg (boundary)'],
    ['weight' => 9.8, 'height' => 65, 'expected' => 'Obese', 'description' => 'Height 65 cm, 9.8 kg (boundary)'],
    ['weight' => 10.0, 'height' => 65, 'expected' => 'Obese', 'description' => 'Height 65 cm, 10.0 kg (> 9.8)'],
    
    // Height 90 cm
    ['weight' => 9.6, 'height' => 90, 'expected' => 'Severely wasted', 'description' => 'Height 90 cm, 9.6 kg (should be < 9.7)'],
    ['weight' => 9.7, 'height' => 90, 'expected' => 'Wasted', 'description' => 'Height 90 cm, 9.7 kg (boundary)'],
    ['weight' => 10.0, 'height' => 90, 'expected' => 'Wasted', 'description' => 'Height 90 cm, 10.0 kg (9.8-10.4 range)'],
    ['weight' => 10.4, 'height' => 90, 'expected' => 'Wasted', 'description' => 'Height 90 cm, 10.4 kg (boundary)'],
    ['weight' => 10.5, 'height' => 90, 'expected' => 'Normal', 'description' => 'Height 90 cm, 10.5 kg (boundary)'],
    ['weight' => 12.0, 'height' => 90, 'expected' => 'Normal', 'description' => 'Height 90 cm, 12.0 kg (10.5-14.8 range)'],
    ['weight' => 14.8, 'height' => 90, 'expected' => 'Normal', 'description' => 'Height 90 cm, 14.8 kg (boundary)'],
    ['weight' => 14.9, 'height' => 90, 'expected' => 'Overweight', 'description' => 'Height 90 cm, 14.9 kg (boundary)'],
    ['weight' => 16.3, 'height' => 90, 'expected' => 'Overweight', 'description' => 'Height 90 cm, 16.3 kg (boundary)'],
    ['weight' => 16.4, 'height' => 90, 'expected' => 'Obese', 'description' => 'Height 90 cm, 16.4 kg (boundary)'],
    ['weight' => 17.0, 'height' => 90, 'expected' => 'Obese', 'description' => 'Height 90 cm, 17.0 kg (> 16.4)'],
    
    // Height 120 cm
    ['weight' => 17.1, 'height' => 120, 'expected' => 'Severely wasted', 'description' => 'Height 120 cm, 17.1 kg (should be < 17.2)'],
    ['weight' => 17.2, 'height' => 120, 'expected' => 'Wasted', 'description' => 'Height 120 cm, 17.2 kg (boundary)'],
    ['weight' => 18.0, 'height' => 120, 'expected' => 'Wasted', 'description' => 'Height 120 cm, 18.0 kg (17.3-18.8 range)'],
    ['weight' => 18.8, 'height' => 120, 'expected' => 'Wasted', 'description' => 'Height 120 cm, 18.8 kg (boundary)'],
    ['weight' => 18.9, 'height' => 120, 'expected' => 'Normal', 'description' => 'Height 120 cm, 18.9 kg (boundary)'],
    ['weight' => 22.0, 'height' => 120, 'expected' => 'Normal', 'description' => 'Height 120 cm, 22.0 kg (18.9-28.0 range)'],
    ['weight' => 28.0, 'height' => 120, 'expected' => 'Normal', 'description' => 'Height 120 cm, 28.0 kg (boundary)'],
    ['weight' => 28.1, 'height' => 120, 'expected' => 'Overweight', 'description' => 'Height 120 cm, 28.1 kg (boundary)'],
    ['weight' => 31.2, 'height' => 120, 'expected' => 'Overweight', 'description' => 'Height 120 cm, 31.2 kg (boundary)'],
    ['weight' => 31.3, 'height' => 120, 'expected' => 'Obese', 'description' => 'Height 120 cm, 31.3 kg (boundary)'],
    ['weight' => 32.0, 'height' => 120, 'expected' => 'Obese', 'description' => 'Height 120 cm, 32.0 kg (> 31.3)'],
];

$girlsMatches = 0;
$girlsTotal = count($girlsTests);

foreach ($girlsTests as $test) {
    $result = $who->calculateWeightForHeight($test['weight'], $test['height'], 'Female');
    $actual = $result['classification'];
    $match = ($actual === $test['expected']);
    
    if ($match) {
        $girlsMatches++;
    }
    
    $method = isset($result['method']) ? $result['method'] : 'unknown';
    $heightUsed = isset($result['height_used']) ? $result['height_used'] : $test['height'];
    
    echo "<p><strong>{$test['description']}</strong><br>";
    echo "Expected: {$test['expected']}, Actual: {$actual} " . ($match ? "✅" : "❌") . "<br>";
    echo "Method: {$method}, Height used: {$heightUsed}</p>\n";
}

// Summary
$totalTests = $boysTotal + $girlsTotal;
$totalMatches = $boysMatches + $girlsMatches;
$successRate = round(($totalMatches / $totalTests) * 100, 2);

echo "<h2>Accuracy Summary</h2>\n";
echo "<p><strong>Boys Weight-for-Age:</strong> {$boysMatches}/{$boysTotal} matches (" . round(($boysMatches / $boysTotal) * 100, 2) . "%)</p>\n";
echo "<p><strong>Girls Weight-for-Height:</strong> {$girlsMatches}/{$girlsTotal} matches (" . round(($girlsMatches / $girlsTotal) * 100, 2) . "%)</p>\n";
echo "<p><strong>Overall Accuracy:</strong> {$totalMatches}/{$totalTests} matches ({$successRate}%)</p>\n";

if ($successRate >= 95) {
    echo "<p style='color: green; font-weight: bold;'>✅ Excellent! The decision tree is highly accurate against the WHO growth standards images.</p>\n";
} elseif ($successRate >= 90) {
    echo "<p style='color: orange; font-weight: bold;'>⚠️ Good accuracy, but some values need adjustment.</p>\n";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Poor accuracy. The lookup tables need to be updated with the correct values.</p>\n";
}

// Check if lookup tables are being used
echo "<h2>Method Usage Analysis</h2>\n";
$lookupUsage = 0;
$formulaUsage = 0;

foreach ($boysTests as $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
    if (isset($result['method'])) {
        if ($result['method'] === 'lookup_table') {
            $lookupUsage++;
        } else {
            $formulaUsage++;
        }
    }
}

foreach ($girlsTests as $test) {
    $result = $who->calculateWeightForHeight($test['weight'], $test['height'], 'Female');
    if (isset($result['method'])) {
        if ($result['method'] === 'lookup_table') {
            $lookupUsage++;
        } else {
            $formulaUsage++;
        }
    }
}

echo "<p><strong>Lookup Table Usage:</strong> {$lookupUsage} tests</p>\n";
echo "<p><strong>Formula Usage:</strong> {$formulaUsage} tests</p>\n";

if ($lookupUsage > $formulaUsage) {
    echo "<p style='color: green;'>✅ Lookup tables are being used more than formulas - this is good for accuracy!</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Formulas are being used more than lookup tables - consider adding more lookup data.</p>\n";
}
?>
