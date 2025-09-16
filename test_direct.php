<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "=== DIRECT TEST ===\n\n";

// Test the specific case that should be Severely Underweight
$result = $who->calculateWeightForAge(6.4, 9, 'Male');
echo "Age 9, Weight 6.4kg, Male:\n";
print_r($result);

echo "\n=== TESTING LOOKUP TABLE ===\n";
$boysTable = $who->getWeightForAgeBoysLookupTable();
echo "Age 9 data:\n";
print_r($boysTable[9]);

echo "\n=== TESTING AGE CALCULATION ===\n";
$age = $who->calculateAgeInMonths('2023-12-15', '2024-09-15');
echo "Age calculation: $age months\n";
?>
