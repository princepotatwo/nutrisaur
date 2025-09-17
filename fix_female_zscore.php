<?php
// Script to add z_scores to all female weight-for-age return statements

$file = 'who_growth_standards.php';
$content = file_get_contents($file);

// Pattern to match female return statements without z_score
$patterns = [
    // Severely Underweight
    "/if \(\$weight <= [0-9.]+\) return \['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'\];/",
    // Underweight  
    "/if \(\$weight >= [0-9.]+ && \$weight <= [0-9.]+\) return \['classification' => 'Underweight', 'method' => 'hardcoded_simple'\];/",
    // Normal
    "/if \(\$weight >= [0-9.]+ && \$weight <= [0-9.]+\) return \['classification' => 'Normal', 'method' => 'hardcoded_simple'\];/",
    // Overweight
    "/if \(\$weight >= [0-9.]+\) return \['classification' => 'Overweight', 'method' => 'hardcoded_simple'\];/"
];

$replacements = [
    // Severely Underweight
    "if (\$weight <= $1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];",
    // Underweight
    "if (\$weight >= $1 && \$weight <= $2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];",
    // Normal
    "if (\$weight >= $1 && \$weight <= $2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];",
    // Overweight
    "if (\$weight >= $1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];"
];

// Apply replacements
$newContent = $content;

// Fix Severely Underweight cases
$newContent = preg_replace("/if \(\$weight <= ([0-9.]+)\) return \['classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'\];/", 
    "if (\$weight <= $1) return ['z_score' => -3.0, 'classification' => 'Severely Underweight', 'method' => 'hardcoded_simple'];", $newContent);

// Fix Underweight cases
$newContent = preg_replace("/if \(\$weight >= ([0-9.]+) && \$weight <= ([0-9.]+)\) return \['classification' => 'Underweight', 'method' => 'hardcoded_simple'\];/", 
    "if (\$weight >= $1 && \$weight <= $2) return ['z_score' => -2.5, 'classification' => 'Underweight', 'method' => 'hardcoded_simple'];", $newContent);

// Fix Normal cases
$newContent = preg_replace("/if \(\$weight >= ([0-9.]+) && \$weight <= ([0-9.]+)\) return \['classification' => 'Normal', 'method' => 'hardcoded_simple'\];/", 
    "if (\$weight >= $1 && \$weight <= $2) return ['z_score' => 0.0, 'classification' => 'Normal', 'method' => 'hardcoded_simple'];", $newContent);

// Fix Overweight cases
$newContent = preg_replace("/if \(\$weight >= ([0-9.]+)\) return \['classification' => 'Overweight', 'method' => 'hardcoded_simple'\];/", 
    "if (\$weight >= $1) return ['z_score' => 2.5, 'classification' => 'Overweight', 'method' => 'hardcoded_simple'];", $newContent);

// Write the updated content back
file_put_contents($file, $newContent);

echo "Female weight-for-age return statements updated with z-scores!\n";
?>
