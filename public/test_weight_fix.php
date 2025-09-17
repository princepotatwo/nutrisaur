<?php
require_once '../who_growth_standards.php';

$who = new WhoGrowthStandards();

echo "<h2>Testing Weight-for-Age Classifications</h2>";
echo "<h3>BOYS TEST CASES:</h3>";

$boysTestCases = [
    ['weight' => 2.0, 'age' => 0, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 2.3, 'age' => 0, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 3.0, 'age' => 0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 4.5, 'age' => 0, 'sex' => 'Male', 'expected' => 'Overweight'],
    ['weight' => 2.8, 'age' => 1, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 3.2, 'age' => 1, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 4.0, 'age' => 1, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 6.0, 'age' => 1, 'sex' => 'Male', 'expected' => 'Overweight'],
];

foreach ($boysTestCases as $i => $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    $status = ($result['classification'] === $test['expected']) ? '✓' : '✗';
    echo "<p>{$status} Test " . ($i+1) . ": Weight {$test['weight']}kg, Age {$test['age']}mo -> {$result['classification']} (Expected: {$test['expected']})</p>";
    if ($result['classification'] !== $test['expected']) {
        echo "<p style='color:red'>   Z-score: " . $result['z_score'] . "</p>";
    }
}

echo "<h3>GIRLS TEST CASES:</h3>";

$girlsTestCases = [
    ['weight' => 1.9, 'age' => 0, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['weight' => 2.2, 'age' => 0, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 3.0, 'age' => 0, 'sex' => 'Female', 'expected' => 'Normal'],
    ['weight' => 4.4, 'age' => 0, 'sex' => 'Female', 'expected' => 'Overweight'],
    ['weight' => 2.6, 'age' => 1, 'sex' => 'Female', 'expected' => 'Severely Underweight'],
    ['weight' => 2.9, 'age' => 1, 'sex' => 'Female', 'expected' => 'Underweight'],
    ['weight' => 4.0, 'age' => 1, 'sex' => 'Female', 'expected' => 'Normal'],
    ['weight' => 5.7, 'age' => 1, 'sex' => 'Female', 'expected' => 'Overweight'],
];

foreach ($girlsTestCases as $i => $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], $test['sex']);
    $status = ($result['classification'] === $test['expected']) ? '✓' : '✗';
    echo "<p>{$status} Test " . ($i+1) . ": Weight {$test['weight']}kg, Age {$test['age']}mo -> {$result['classification']} (Expected: {$test['expected']})</p>";
    if ($result['classification'] !== $test['expected']) {
        echo "<p style='color:red'>   Z-score: " . $result['z_score'] . "</p>";
    }
}

echo "<h3>BOUNDARY TEST CASES (Critical for gap detection):</h3>";

$boundaryTests = [
    ['weight' => 3.8, 'age' => 2, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['weight' => 3.9, 'age' => 2, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 4.2, 'age' => 2, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['weight' => 4.3, 'age' => 2, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 7.1, 'age' => 2, 'sex' => 'Male', 'expected' => 'Normal'],
    ['weight' => 7.2, 'age' => 2, 'sex' => 'Male', 'expected' => 'Overweight'],
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
    echo "<p>{$status} Boundary Test " . ($i+1) . ": Weight {$test['weight']}kg, Age {$test['age']}mo, {$test['sex']} -> {$result['classification']} (Expected: {$test['expected']})</p>";
    if ($result['classification'] !== $test['expected']) {
        echo "<p style='color:red'>   Z-score: " . $result['z_score'] . "</p>";
    }
}

echo "<p><strong>Test completed!</strong></p>";
?>
