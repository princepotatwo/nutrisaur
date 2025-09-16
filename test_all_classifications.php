<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "=== TESTING ALL CLASSIFICATIONS ===\n\n";

// Test cases from the CSV file
$testCases = [
    // Age 0 months
    ['name' => 'Severely Underweight 0mo Boy', 'age' => 0, 'weight' => 2.0, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 0mo Boy', 'age' => 0, 'weight' => 2.3, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 0mo Boy', 'age' => 0, 'weight' => 3.0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 0mo Boy', 'age' => 0, 'weight' => 4.5, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Age 9 months
    ['name' => 'Severely Underweight 9mo Boy', 'age' => 9, 'weight' => 6.4, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 9mo Boy', 'age' => 9, 'weight' => 7.0, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 9mo Boy', 'age' => 9, 'weight' => 9.0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 9mo Boy', 'age' => 9, 'weight' => 11.1, 'sex' => 'Male', 'expected' => 'Overweight'],
    
    // Age 71 months
    ['name' => 'Severely Underweight 71mo Boy', 'age' => 71, 'weight' => 13.5, 'sex' => 'Male', 'expected' => 'Severely Underweight'],
    ['name' => 'Underweight 71mo Boy', 'age' => 71, 'weight' => 15.0, 'sex' => 'Male', 'expected' => 'Underweight'],
    ['name' => 'Normal 71mo Boy', 'age' => 71, 'weight' => 20.0, 'sex' => 'Male', 'expected' => 'Normal'],
    ['name' => 'Overweight 71mo Boy', 'age' => 71, 'weight' => 24.1, 'sex' => 'Male', 'expected' => 'Overweight'],
];

$correct = 0;
$total = count($testCases);

foreach ($testCases as $case) {
    $result = $who->calculateWeightForAge($case['weight'], $case['age'], $case['sex']);
    $isCorrect = ($result['classification'] === $case['expected']);
    $status = $isCorrect ? '✅ CORRECT' : '❌ WRONG';
    
    if ($isCorrect) $correct++;
    
    echo "{$case['name']}: {$result['classification']} (Expected: {$case['expected']}) $status\n";
    echo "  Range: {$result['weight_range']}\n\n";
}

echo "=== SUMMARY ===\n";
echo "Correct: $correct/$total (" . round(($correct/$total)*100, 1) . "%)\n";

if ($correct < $total) {
    echo "❌ STILL HAVE ISSUES - Need to fix more ages in boys' lookup table\n";
} else {
    echo "✅ ALL CLASSIFICATIONS WORKING CORRECTLY!\n";
}
?>
