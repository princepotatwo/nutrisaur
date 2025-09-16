<?php
// Comprehensive script to swap all lookup table data

$file = 'who_growth_standards.php';
$content = file_get_contents($file);

// Find the boys lookup table function (starts around line 130)
$boysStart = strpos($content, 'public function getWeightForAgeBoysLookupTable() {');
$boysEnd = strpos($content, '    }', $boysStart + 200) + 4;

// Find the girls lookup table function (starts around line 293)  
$girlsStart = strpos($content, 'public function getWeightForAgeGirlsLookupTable() {');
$girlsEnd = strpos($content, '    }', $girlsStart + 200) + 4;

// Extract the function bodies
$boysFunction = substr($content, $boysStart, $boysEnd - $boysStart);
$girlsFunction = substr($content, $girlsStart, $girlsEnd - $girlsStart);

// Extract just the data arrays (between the return [ and ];)
$boysDataStart = strpos($boysFunction, 'return [') + 8;
$boysDataEnd = strrpos($boysFunction, ']') - 1;
$boysData = substr($boysFunction, $boysDataStart, $boysDataEnd - $boysDataStart + 1);

$girlsDataStart = strpos($girlsFunction, 'return [') + 8;
$girlsDataEnd = strrpos($girlsFunction, ']') - 1;
$girlsData = substr($girlsFunction, $girlsDataStart, $girlsDataEnd - $girlsDataStart + 1);

// Create new function bodies with swapped data
$newBoysFunction = str_replace($boysData, $girlsData, $boysFunction);
$newGirlsFunction = str_replace($girlsData, $boysData, $girlsFunction);

// Replace in the original content
$newContent = str_replace($boysFunction, $newBoysFunction, $content);
$newContent = str_replace($girlsFunction, $newGirlsFunction, $newContent);

// Write back to file
file_put_contents($file, $newContent);

echo "All lookup tables swapped successfully!\n";
echo "Boys function now contains correct boys' data\n";
echo "Girls function now contains correct girls' data\n";

// Verify the swap worked by checking age 6
require_once 'who_growth_standards.php';
$who = new WHOGrowthStandards();

$boysData = $who->getWeightForAgeBoysLookupTable();
$girlsData = $who->getWeightForAgeGirlsLookupTable();

echo "\nVerification - Age 6 months:\n";
echo "Boys Normal Range: " . $boysData[6]['normal']['min'] . "-" . $boysData[6]['normal']['max'] . "kg\n";
echo "Girls Normal Range: " . $girlsData[6]['normal']['min'] . "-" . $girlsData[6]['normal']['max'] . "kg\n";

echo "\nVerification - Age 71 months:\n";
echo "Boys Normal Range: " . $boysData[71]['normal']['min'] . "-" . $boysData[71]['normal']['max'] . "kg\n";
echo "Girls Normal Range: " . $girlsData[71]['normal']['min'] . "-" . $girlsData[71]['normal']['max'] . "kg\n";
?>
