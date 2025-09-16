<?php
require_once '../who_growth_standards.php';

$who = new WHOGrowthStandards();

echo "<h1>Debug Lookup Table Selection</h1>";

// Test the lookup table functions directly
echo "<h2>Boys Lookup Table - Age 71</h2>";
$boysRanges = $who->getWeightForAgeBoysLookupTable();
if (isset($boysRanges[71])) {
    echo "<pre>" . print_r($boysRanges[71], true) . "</pre>";
} else {
    echo "<p>Age 71 not found in boys lookup table</p>";
    echo "<p>Available ages: " . implode(', ', array_keys($boysRanges)) . "</p>";
}

echo "<h2>Girls Lookup Table - Age 71</h2>";
$girlsRanges = $who->getWeightForAgeGirlsLookupTable();
if (isset($girlsRanges[71])) {
    echo "<pre>" . print_r($girlsRanges[71], true) . "</pre>";
} else {
    echo "<p>Age 71 not found in girls lookup table</p>";
    echo "<p>Available ages: " . implode(', ', array_keys($girlsRanges)) . "</p>";
}

echo "<h2>Test calculateWeightForAge for Male</h2>";
$result = $who->calculateWeightForAge(24.1, 71, 'Male');
echo "<pre>" . print_r($result, true) . "</pre>";

echo "<h2>Test calculateWeightForAge for Female</h2>";
$result = $who->calculateWeightForAge(24.1, 71, 'Female');
echo "<pre>" . print_r($result, true) . "</pre>";

// Test findClosestAge function
echo "<h2>Test findClosestAge for Boys</h2>";
$boysRanges = $who->getWeightForAgeBoysLookupTable();
$closestAge = $who->findClosestAge($boysRanges, 71);
echo "<p>Closest age for 71 in boys table: $closestAge</p>";

echo "<h2>Test findClosestAge for Girls</h2>";
$girlsRanges = $who->getWeightForAgeGirlsLookupTable();
$closestAge = $who->findClosestAge($girlsRanges, 71);
echo "<p>Closest age for 71 in girls table: $closestAge</p>";
?>
