<?php
echo "<h2>Fix Decimal Keys in Weight-for-Height Data</h2>";

// Read the current file
$file = file_get_contents('who_growth_standards.php');

// Find the getWeightForHeightGirls method and replace decimal keys with string keys
$pattern = '/(\d+\.5)\s*=>/';
$replacement = '"$1" =>';

$newFile = preg_replace($pattern, $replacement, $file);

// Write the fixed file
file_put_contents('who_growth_standards.php', $newFile);

echo "<p>Fixed decimal keys in Weight-for-Height data!</p>";

// Also fix the boys data if it has decimal keys
$pattern2 = '/(\d+\.5)\s*=>/';
$replacement2 = '"$1" =>';

$newFile2 = preg_replace($pattern2, $replacement2, $newFile);

file_put_contents('who_growth_standards.php', $newFile2);

echo "<p>Fixed decimal keys in Weight-for-Height boys data too!</p>";
echo "<p>All decimal keys have been converted to string keys to avoid PHP deprecation warnings.</p>";
?>
