<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "=== Testing Weight-for-Age Classifications ===\n\n";

// Test cases from the table you showed me
$testCases = [
    ['name' => 'Severely Underweight 9mo Boy', 'age' => 9, 'weight' => 6.4, 'sex' => 'Male'],
    ['name' => 'Severely Underweight 8mo Boy', 'age' => 8, 'weight' => 5.5, 'sex' => 'Male'],
    ['name' => 'Underweight 36mo Boy', 'age' => 36, 'weight' => 11.2, 'sex' => 'Male'],
    ['name' => 'Underweight 9mo Boy', 'age' => 9, 'weight' => 7.0, 'sex' => 'Male'],
    ['name' => 'Normal 36mo Boy', 'age' => 36, 'weight' => 14.0, 'sex' => 'Male'],
    ['name' => 'Overweight 71mo Boy', 'age' => 71, 'weight' => 24.1, 'sex' => 'Male'],
];

foreach ($testCases as $case) {
    $result = $who->calculateWeightForAge($case['weight'], $case['age'], $case['sex']);
    echo "{$case['name']}: {$result['classification']} (Range: {$result['weight_range']})\n";
}

echo "\n=== Testing Lookup Table Completeness ===\n";
$boysData = $who->getWeightForAgeBoysLookupTable();
echo "Boys lookup table has " . count($boysData) . " age entries\n";

$girlsData = $who->getWeightForAgeGirlsLookupTable();
echo "Girls lookup table has " . count($girlsData) . " age entries\n";

echo "\n=== Sample Age Ranges ===\n";
echo "Boys Age 9: Normal " . $boysData[9]['normal']['min'] . "-" . $boysData[9]['normal']['max'] . "kg\n";
echo "Boys Age 36: Normal " . $boysData[36]['normal']['min'] . "-" . $boysData[36]['normal']['max'] . "kg\n";
echo "Boys Age 71: Normal " . $boysData[71]['normal']['min'] . "-" . $boysData[71]['normal']['max'] . "kg\n";
?>
