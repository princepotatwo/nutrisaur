<?php
require_once 'who_growth_standards.php';

echo "<h2>Expand Lookup Tables to Cover All Ages</h2>";

$who = new WHOGrowthStandards();

// Get the formula data for all ages
$boysData = $who->getWeightForAgeBoys();
$girlsData = $who->getWeightForAgeGirls();

echo "<h3>Boys Weight-for-Age Data (0-71 months):</h3>";
echo "<pre>";
foreach ($boysData as $age => $data) {
    echo "Age {$age}: median={$data['median']}, sd={$data['sd']}\n";
}
echo "</pre>";

echo "<h3>Girls Weight-for-Age Data (0-71 months):</h3>";
echo "<pre>";
foreach ($girlsData as $age => $data) {
    echo "Age {$age}: median={$data['median']}, sd={$data['sd']}\n";
}
echo "</pre>";

// Create expanded lookup tables
echo "<h3>Creating Expanded Lookup Tables...</h3>";

// For boys, create lookup table for all ages
$boysLookup = [];
foreach ($boysData as $age => $data) {
    $median = $data['median'];
    $sd = $data['sd'];
    
    // Calculate Z-score boundaries
    $severely_underweight_max = $median - (3 * $sd);
    $underweight_max = $median - (2 * $sd);
    $normal_max = $median + (2 * $sd);
    $overweight_max = $median + (3 * $sd);
    
    $boysLookup[$age] = [
        'severely_underweight' => ['min' => 0, 'max' => $severely_underweight_max],
        'underweight' => ['min' => $severely_underweight_max + 0.1, 'max' => $underweight_max],
        'normal' => ['min' => $underweight_max + 0.1, 'max' => $normal_max],
        'overweight' => ['min' => $normal_max + 0.1, 'max' => 999]
    ];
}

echo "<h3>Sample Boys Lookup Table (ages 0, 12, 24, 36, 48, 60, 71):</h3>";
$sampleAges = [0, 12, 24, 36, 48, 60, 71];
foreach ($sampleAges as $age) {
    if (isset($boysLookup[$age])) {
        echo "<p><strong>Age {$age} months:</strong></p>";
        foreach ($boysLookup[$age] as $category => $range) {
            echo "<p>&nbsp;&nbsp;{$category}: {$range['min']} - {$range['max']}</p>";
        }
    }
}

echo "<h3>This expanded lookup table should fix the calculation issues!</h3>";
?>
