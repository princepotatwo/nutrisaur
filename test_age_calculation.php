<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

// Test age calculation for the table data
$birthDate = '2024-01-15'; // Birth date from table
$screeningDate = '2024-01-15 10:30:00'; // Screening date from table

echo "Testing Age Calculation:\n";
echo "=======================\n";
echo "Birth Date: $birthDate\n";
echo "Screening Date: $screeningDate\n\n";

$ageInMonths = $who->calculateAgeInMonths($birthDate, $screeningDate);
echo "Calculated Age in Months: $ageInMonths\n";

// Test weight-for-age for female
$weight = 1.9;
$sex = 'Female';

echo "\nTesting Weight-for-Age:\n";
echo "Weight: $weight kg\n";
echo "Sex: $sex\n";
echo "Age in Months: $ageInMonths\n\n";

$result = $who->calculateWeightForAge($weight, $ageInMonths, $sex);
echo "Result:\n";
print_r($result);
?>
