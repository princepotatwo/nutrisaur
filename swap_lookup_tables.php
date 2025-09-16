<?php
// Script to swap the lookup table data to fix the gender mismatch

$file = 'who_growth_standards.php';
$content = file_get_contents($file);

// Find the boys lookup table function
$boysStart = strpos($content, 'public function getWeightForAgeBoysLookupTable() {');
$boysEnd = strpos($content, '    }', $boysStart + 50) + 4;

// Find the girls lookup table function  
$girlsStart = strpos($content, 'public function getWeightForAgeGirlsLookupTable() {');
$girlsEnd = strpos($content, '    }', $girlsStart + 50) + 4;

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

echo "Lookup tables swapped successfully!\n";
echo "Boys function now contains girls' data\n";
echo "Girls function now contains boys' data\n";
?>
