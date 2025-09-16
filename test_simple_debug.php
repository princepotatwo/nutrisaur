<?php
// Simple test to verify debugging works
require_once 'who_growth_standards.php';

echo "Testing debugging...\n";

$who = new WHOGrowthStandards();

// Test with a simple case
$result = $who->calculateWeightForAge(24.1, 71, 'Male');

echo "Result: " . json_encode($result) . "\n";

// Check if debug log was created
if (file_exists('debug_classification.log')) {
    echo "Debug log created! Contents:\n";
    echo file_get_contents('debug_classification.log');
} else {
    echo "No debug log file found.\n";
}
?>
