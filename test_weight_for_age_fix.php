<?php
require_once 'who_growth_standards.php';

$who = new WhoGrowthStandards();

echo "Testing Weight-for-Age Classifications\n";
echo "=====================================\n\n";

// Test cases for boys
echo "BOYS TEST CASES:\n";
echo "----------------\n";

$boysTestCases = [
    // Case 0 (Birth)
    ['weight' => 2.0, 'age' => 0, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 2.3, 'age' => 0, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 3.0, 'age' => 0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 4.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Case 1 (1 month)
    ['weight' => 2.8, 'age' => 1, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 3.2, 'age' => 1, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 4.0, 'age' => 1, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 6.0, 'age' => 1, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Case 2 (2 months)
    ['weight' => 3.7, 'age' => 2, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 4.0, 'age' => 2, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 5.0, 'age' => 2, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 7.3, 'age' => 2, 'sex' => 'Male', 'expected' => 'Overweight'],
];

foreach ($boysTestCases as $i => $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    $status = ($result['classification'] === $test['expected']) ? '✓' : '✗';
    echo sprintf("%s Test %d: Weight %.1fkg, Age %dmo -> %s (Expected: %s)\n", 
        $status, $i+1, $test['weight'], $test['age'], $result['classification'], $test['expected']);
    if ($result['classification'] !== $test['expected']) {
        echo "   Z-score: " . $result['z_score'] . "\n";
    }
}

echo "\nGIRLS TEST CASES:\n";
echo "-----------------\n";

$girlsTestCases = [
    // Case 0 (Birth)
    ['weight' => 1.9, 'age' => 0, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['weight' => 2.2, 'age' => 0, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 3.0, 'age' => 0, 'sex' => 'Female', 'expected' => 'Normal'],
    ['weight' => 4.4, 'age' => 0, 'sex' => 'Female', 'expected' => 'Overweight'],
    
    // Case 1 (1 month)
    ['weight' => 2.6, 'age' => 1, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['weight' => 2.9, 'age' => 1, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 4.0, 'age' => 1, 'sex' => 'Female', 'expected' => 'Normal'],
    ['weight' => 5.7, 'age' => 1, 'sex' => 'Female', 'expected' => 'Overweight'],
    
    // Case 2 (2 months)
    ['weight' => 3.3, 'age' => 2, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['weight' => 3.6, 'age' => 2, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 5.0, 'age' => 2, 'sex' => 'Female', 'expected' => 'Normal'],
    ['weight' => 6.7, 'age' => 2, 'sex' => 'Female', 'expected' => 'Overweight'],
];

foreach ($girlsTestCases as $i => $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    $status = ($result['classification'] === $test['expected']) ? '✓' : '✗';
    echo sprintf("%s Test %d: Weight %.1fkg, Age %dmo -> %s (Expected: %s)\n", 
        $status, $i+1, $test['weight'], $test['age'], $result['classification'], $test['expected']);
    if ($result['classification'] !== $test['expected']) {
        echo "   Z-score: " . $result['z_score'] . "\n";
    }
}

echo "\nBOUNDARY TEST CASES (Critical for gap detection):\n";
echo "------------------------------------------------\n";

$boundaryTests = [
    // Boys case 2 boundaries
    ['weight' => 3.8, 'age' => 2, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 3.9, 'age' => 2, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 4.2, 'age' => 2, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 4.3, 'age' => 2, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 7.1, 'age' => 2, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 7.2, 'age' => 2, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Girls case 2 boundaries
    ['weight' => 3.4, 'age' => 2, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['weight' => 3.5, 'age' => 2, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 3.9, 'age' => 2, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 4.0, 'age' => 2, 'sex' => 'Female', 'expected' => 'Normal'],
    ['weight' => 6.5, 'age' => 2, 'sex' => 'Female', 'expected' => 'Normal'],
    ['weight' => 6.6, 'age' => 2, 'sex' => 'Female', 'expected' => 'Overweight'],
];

foreach ($boundaryTests as $i => $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    $status = ($result['classification'] === $test['expected']) ? '✓' : '✗';
    echo sprintf("%s Boundary Test %d: Weight %.1fkg, Age %dmo, %s -> %s (Expected: %s)\n", 
        $status, $i+1, $test['weight'], $test['age'], $test['sex'], $result['classification'], $test['expected']);
    if ($result['classification'] !== $test['expected']) {
        echo "   Z-score: " . $result['z_score'] . "\n";
    }
}

echo "\nTest completed!\n";
?>
