<?php
require_once 'who_growth_standards.php';

echo "<h2>WHO Growth Standards Test</h2>\n";

$who = new WHOGrowthStandards();

// Test case 1: Mason (38 months, 13.8 kg, Male)
echo "<h3>Test Case 1: Mason (38 months, 13.8 kg, Male)</h3>\n";
$result1 = $who->calculateWeightForAge(13.8, 38, 'Male');
echo "Result: " . json_encode($result1) . "\n";
echo "Expected: Z-score around -1.75, Classification: Normal\n\n";

// Test case 2: Mateo (13 months, 4.8 kg, Male)  
echo "<h3>Test Case 2: Mateo (13 months, 4.8 kg, Male)</h3>\n";
$result2 = $who->calculateWeightForAge(4.8, 13, 'Male');
echo "Result: " . json_encode($result2) . "\n";
echo "Expected: Z-score around -12.75, Classification: Severely Underweight\n\n";

// Test case 3: Miguel (10 months, 4.2 kg, Male)
echo "<h3>Test Case 3: Miguel (10 months, 4.2 kg, Male)</h3>\n";
$result3 = $who->calculateWeightForAge(4.2, 10, 'Male');
echo "Result: " . json_encode($result3) . "\n";
echo "Expected: Z-score around -12.5, Classification: Severely Underweight\n\n";

// Test case 4: Noah (26 months, 11.5 kg, Male)
echo "<h3>Test Case 4: Noah (26 months, 11.5 kg, Male)</h3>\n";
$result4 = $who->calculateWeightForAge(11.5, 26, 'Male');
echo "Result: " . json_encode($result4) . "\n";
echo "Expected: Z-score around 4.75, Classification: Overweight\n\n";

// Test case 5: Owen (56 months, 16.8 kg, Male)
echo "<h3>Test Case 5: Owen (56 months, 16.8 kg, Male)</h3>\n";
$result5 = $who->calculateWeightForAge(16.8, 56, 'Male');
echo "Result: " . json_encode($result5) . "\n";
echo "Expected: Z-score around 5.75, Classification: Overweight\n\n";

// Test case 6: Sebastian (16 months, 10.5 kg, Male)
echo "<h3>Test Case 6: Sebastian (16 months, 10.5 kg, Male)</h3>\n";
$result6 = $who->calculateWeightForAge(10.5, 16, 'Male');
echo "Result: " . json_encode($result6) . "\n";
echo "Expected: Z-score around 0, Classification: Normal\n\n";

echo "<h3>Manual Verification</h3>\n";
echo "Mason (38m): Median=14.5, SD=0.4, Z=(13.8-14.5)/0.4=" . number_format((13.8-14.5)/0.4, 2) . "\n";
echo "Mateo (13m): Median=9.9, SD=0.4, Z=(4.8-9.9)/0.4=" . number_format((4.8-9.9)/0.4, 2) . "\n";
echo "Miguel (10m): Median=9.2, SD=0.4, Z=(4.2-9.2)/0.4=" . number_format((4.2-9.2)/0.4, 2) . "\n";
echo "Noah (26m): Median=12.5, SD=0.4, Z=(11.5-12.5)/0.4=" . number_format((11.5-12.5)/0.4, 2) . "\n";
echo "Owen (56m): Median=17.0, SD=0.4, Z=(16.8-17.0)/0.4=" . number_format((16.8-17.0)/0.4, 2) . "\n";
echo "Sebastian (16m): Median=10.5, SD=0.4, Z=(10.5-10.5)/0.4=" . number_format((10.5-10.5)/0.4, 2) . "\n";
?>
