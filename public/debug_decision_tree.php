<?php
require_once '../who_growth_standards.php';

$who = new WHOGrowthStandards();

// Test case: 0-month-old boy with 2.0kg (should be Severely Underweight)
echo "<h2>Testing Decision Tree</h2>";

$testCases = [
    ['age' => 0, 'weight' => 2.0, 'expected' => 'Severely Underweight'],
    ['age' => 0, 'weight' => 2.3, 'expected' => 'Underweight'],
    ['age' => 0, 'weight' => 3.5, 'expected' => 'Normal'],
    ['age' => 0, 'weight' => 4.6, 'expected' => 'Overweight'],
    ['age' => 6, 'weight' => 4.0, 'expected' => 'Severely Underweight'],
    ['age' => 6, 'weight' => 8.8, 'expected' => 'Overweight']
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Age</th><th>Weight</th><th>Expected</th><th>Actual</th><th>Method</th><th>Age Used</th><th>Result</th></tr>";

foreach ($testCases as $test) {
    $result = $who->calculateWeightForAge($test['weight'], $test['age'], 'Male');
    
    $actual = $result['classification'] ?? 'NULL';
    $method = $result['method'] ?? 'NULL';
    $ageUsed = $result['age_used'] ?? 'NULL';
    $isCorrect = ($actual === $test['expected']);
    
    echo "<tr>";
    echo "<td>{$test['age']} months</td>";
    echo "<td>{$test['weight']}kg</td>";
    echo "<td>{$test['expected']}</td>";
    echo "<td>$actual</td>";
    echo "<td>$method</td>";
    echo "<td>$ageUsed</td>";
    echo "<td>" . ($isCorrect ? "✅ CORRECT" : "❌ WRONG") . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test with actual birth dates like in the CSV
echo "<h2>Testing with Actual Birth Dates (like CSV)</h2>";

$csvTestCases = [
    ['birth_date' => '2024-09-15', 'weight' => 2.0, 'expected' => 'Severely Underweight', 'name' => 'Severely Underweight 0mo'],
    ['birth_date' => '2024-09-15', 'weight' => 4.6, 'expected' => 'Overweight', 'name' => 'Overweight 0mo'],
    ['birth_date' => '2024-03-15', 'weight' => 4.0, 'expected' => 'Severely Underweight', 'name' => 'Severely Underweight 6mo'],
    ['birth_date' => '2024-03-15', 'weight' => 8.8, 'expected' => 'Overweight', 'name' => 'Overweight 6mo']
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Name</th><th>Birth Date</th><th>Weight</th><th>Expected</th><th>Actual</th><th>Age Months</th><th>Result</th></tr>";

foreach ($csvTestCases as $test) {
    $ageInMonths = $who->calculateAgeInMonths($test['birth_date'], '2024-09-15 10:00:00');
    $result = $who->calculateWeightForAge($test['weight'], $ageInMonths, 'Male');
    
    $actual = $result['classification'] ?? 'NULL';
    $isCorrect = ($actual === $test['expected']);
    
    echo "<tr>";
    echo "<td>{$test['name']}</td>";
    echo "<td>{$test['birth_date']}</td>";
    echo "<td>{$test['weight']}kg</td>";
    echo "<td>{$test['expected']}</td>";
    echo "<td>$actual</td>";
    echo "<td>$ageInMonths</td>";
    echo "<td>" . ($isCorrect ? "✅ CORRECT" : "❌ WRONG") . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
