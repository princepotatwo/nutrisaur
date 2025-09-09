<?php
require_once 'who_growth_standards.php';

echo "<h2>Manual WHO Growth Standards Test</h2>\n";

$who = new WHOGrowthStandards();

// Test the specific cases from the table
$testCases = [
    ['name' => 'Mason James Vargas', 'age_months' => 38, 'weight' => 13.8, 'height' => 94.0, 'sex' => 'Male'],
    ['name' => 'Mateo Alexander Flores', 'age_months' => 13, 'weight' => 4.8, 'height' => 65.0, 'sex' => 'Male'],
    ['name' => 'Miguel Angel Cruz', 'age_months' => 10, 'weight' => 4.2, 'height' => 52.0, 'sex' => 'Male'],
    ['name' => 'Noah Alexander Vega', 'age_months' => 26, 'weight' => 11.5, 'height' => 84.0, 'sex' => 'Male'],
    ['name' => 'Owen Alexander Salinas', 'age_months' => 56, 'weight' => 16.8, 'height' => 104.0, 'sex' => 'Male'],
    ['name' => 'Sebastian Lee Rodriguez', 'age_months' => 16, 'weight' => 10.5, 'height' => 72.0, 'sex' => 'Male']
];

echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Name</th><th>Age (months)</th><th>Weight (kg)</th><th>Height (cm)</th><th>BMI</th><th>WFA Z-Score</th><th>WFA Classification</th><th>Manual Z-Score</th><th>Match</th></tr>\n";

foreach ($testCases as $case) {
    $result = $who->calculateWeightForAge($case['weight'], $case['age_months'], $case['sex']);
    $bmi = round($case['weight'] / pow($case['height'] / 100, 2), 1);
    
    // Manual calculation for verification
    $manualZ = null;
    if ($case['sex'] === 'Male') {
        $standards = [
            10 => ['median' => 9.2, 'sd' => 0.4],
            13 => ['median' => 9.9, 'sd' => 0.4],
            16 => ['median' => 10.5, 'sd' => 0.4],
            26 => ['median' => 12.5, 'sd' => 0.4],
            38 => ['median' => 14.5, 'sd' => 0.4],
            56 => ['median' => 17.0, 'sd' => 0.4]
        ];
        
        if (isset($standards[$case['age_months']])) {
            $manualZ = round(($case['weight'] - $standards[$case['age_months']]['median']) / $standards[$case['age_months']]['sd'], 2);
        }
    }
    
    $match = ($manualZ !== null && abs($result['z_score'] - $manualZ) < 0.1) ? '✓' : '✗';
    
    echo "<tr>";
    echo "<td>" . $case['name'] . "</td>";
    echo "<td>" . $case['age_months'] . "</td>";
    echo "<td>" . $case['weight'] . "</td>";
    echo "<td>" . $case['height'] . "</td>";
    echo "<td>" . $bmi . "</td>";
    echo "<td>" . number_format($result['z_score'], 2) . "</td>";
    echo "<td>" . $result['classification'] . "</td>";
    echo "<td>" . ($manualZ !== null ? number_format($manualZ, 2) : 'N/A') . "</td>";
    echo "<td>" . $match . "</td>";
    echo "</tr>\n";
}

echo "</table>\n";

// Test the WHO standards data directly
echo "<h3>WHO Standards Data Verification</h3>\n";
echo "<p>Testing specific age/weight combinations:</p>\n";

// Test 38 months (Mason)
$masonResult = $who->calculateWeightForAge(13.8, 38, 'Male');
echo "<p>Mason (38m, 13.8kg): " . json_encode($masonResult) . "</p>\n";

// Test 13 months (Mateo)
$mateoResult = $who->calculateWeightForAge(4.8, 13, 'Male');
echo "<p>Mateo (13m, 4.8kg): " . json_encode($mateoResult) . "</p>\n";

// Test 10 months (Miguel)
$miguelResult = $who->calculateWeightForAge(4.2, 10, 'Male');
echo "<p>Miguel (10m, 4.2kg): " . json_encode($miguelResult) . "</p>\n";

echo "<h3>Expected vs Actual</h3>\n";
echo "<p>Mason: Expected Z ≈ -1.75 (Normal), Actual: " . number_format($masonResult['z_score'], 2) . " (" . $masonResult['classification'] . ")</p>\n";
echo "<p>Mateo: Expected Z ≈ -12.75 (Severely Underweight), Actual: " . number_format($mateoResult['z_score'], 2) . " (" . $mateoResult['classification'] . ")</p>\n";
echo "<p>Miguel: Expected Z ≈ -12.5 (Severely Underweight), Actual: " . number_format($miguelResult['z_score'], 2) . " (" . $miguelResult['classification'] . ")</p>\n";
?>
