<?php
require_once 'who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "=== Testing 71-month boy with 24.1kg (should be Overweight) ===\n";
$result = $who->calculateWeightForAge(24.1, 71, 'Male');
echo "Classification: " . $result['classification'] . "\n";
echo "Weight Range: " . $result['weight_range'] . "\n";
echo "Z-Score: " . $result['z_score'] . "\n";

echo "\n=== Testing 71-month girl with 24.1kg (should be Normal) ===\n";
$result = $who->calculateWeightForAge(24.1, 71, 'Female');
echo "Classification: " . $result['classification'] . "\n";
echo "Weight Range: " . $result['weight_range'] . "\n";
echo "Z-Score: " . $result['z_score'] . "\n";

echo "\n=== Testing lookup tables directly ===\n";
$boysData = $who->getWeightForAgeBoysLookupTable();
$girlsData = $who->getWeightForAgeGirlsLookupTable();

echo "Boys Age 71 Normal Range: " . $boysData[71]['normal']['min'] . "-" . $boysData[71]['normal']['max'] . "kg\n";
echo "Girls Age 71 Normal Range: " . $girlsData[71]['normal']['min'] . "-" . $girlsData[71]['normal']['max'] . "kg\n";
?>
