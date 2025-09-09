<?php
require_once 'who_growth_standards.php';

// Test cases from the screening table
$testCases = [
    [
        'name' => 'Mason James Vargas',
        'age' => '3y 2m', // 38 months
        'weight' => 13.8,
        'height' => 94.0,
        'sex' => 'Male'
    ],
    [
        'name' => 'Mateo Alexander Flores', 
        'age' => '1y 1m', // 13 months
        'weight' => 4.8,
        'height' => 65.0,
        'sex' => 'Male'
    ],
    [
        'name' => 'Miguel Angel Cruz',
        'age' => '0y 10m', // 10 months
        'weight' => 4.2,
        'height' => 52.0,
        'sex' => 'Male'
    ],
    [
        'name' => 'Noah Alexander Vega',
        'age' => '2y 2m', // 26 months
        'weight' => 11.5,
        'height' => 84.0,
        'sex' => 'Male'
    ],
    [
        'name' => 'Owen Alexander Salinas',
        'age' => '4y 8m', // 56 months
        'weight' => 16.8,
        'height' => 104.0,
        'sex' => 'Male'
    ],
    [
        'name' => 'Sebastian Lee Rodriguez',
        'age' => '1y 4m', // 16 months
        'weight' => 10.5,
        'height' => 72.0,
        'sex' => 'Male'
    ]
];

echo "<h2>WHO Growth Standards Accuracy Test</h2>\n";
echo "<table border='1' cellpadding='5'>\n";
echo "<tr><th>Name</th><th>Age</th><th>Weight</th><th>Height</th><th>BMI</th><th>WFA Z-Score</th><th>WFA Classification</th><th>Expected</th><th>Match</th></tr>\n";

$who = new WHOGrowthStandards();

foreach ($testCases as $case) {
    // Calculate age in months
    $ageParts = explode('y ', $case['age']);
    $years = intval($ageParts[0]);
    $months = intval(str_replace('m', '', $ageParts[1]));
    $ageInMonths = ($years * 12) + $months;
    
    // Calculate BMI
    $bmi = round($case['weight'] / pow($case['height'] / 100, 2), 1);
    
    // Get Weight-for-Age calculation
    $wfaResult = $who->calculateWeightForAge($case['weight'], $ageInMonths, $case['sex']);
    
    // Expected results from the table (approximate)
    $expectedResults = [
        'Mason James Vargas' => ['z_score' => 8.00, 'classification' => 'Overweight'],
        'Mateo Alexander Flores' => ['z_score' => -8.00, 'classification' => 'Severely Underweight'],
        'Miguel Angel Cruz' => ['z_score' => -11.00, 'classification' => 'Severely Underweight'],
        'Noah Alexander Vega' => ['z_score' => 6.50, 'classification' => 'Overweight'],
        'Owen Alexander Salinas' => ['z_score' => 13.00, 'classification' => 'Overweight'],
        'Sebastian Lee Rodriguez' => ['z_score' => 13.50, 'classification' => 'Overweight']
    ];
    
    $expected = $expectedResults[$case['name']] ?? ['z_score' => 'N/A', 'classification' => 'N/A'];
    
    $zScoreMatch = $wfaResult['z_score'] !== null ? 
        (abs($wfaResult['z_score'] - $expected['z_score']) < 0.1 ? '✓' : '✗') : 'N/A';
    $classificationMatch = $wfaResult['classification'] === $expected['classification'] ? '✓' : '✗';
    
    echo "<tr>";
    echo "<td>" . $case['name'] . "</td>";
    echo "<td>" . $case['age'] . " (" . $ageInMonths . "m)</td>";
    echo "<td>" . $case['weight'] . " kg</td>";
    echo "<td>" . $case['height'] . " cm</td>";
    echo "<td>" . $bmi . "</td>";
    echo "<td>" . ($wfaResult['z_score'] !== null ? number_format($wfaResult['z_score'], 2) : 'N/A') . "</td>";
    echo "<td>" . $wfaResult['classification'] . "</td>";
    echo "<td>Z: " . $expected['z_score'] . " (" . $expected['classification'] . ")</td>";
    echo "<td>Z: " . $zScoreMatch . " Class: " . $classificationMatch . "</td>";
    echo "</tr>\n";
}

echo "</table>\n";

// Test the WHO standards data directly using public methods
echo "<h3>WHO Standards Data Verification</h3>\n";
echo "<p>Testing specific age/weight combinations:</p>\n";

// Test 38 months (Mason) - 3y 2m
$masonResult = $who->calculateWeightForAge(13.8, 38, 'Male');
echo "<p>38 months (Mason): " . json_encode($masonResult) . "</p>\n";

// Test 13 months (Mateo) - 1y 1m  
$mateoResult = $who->calculateWeightForAge(4.8, 13, 'Male');
echo "<p>13 months (Mateo): " . json_encode($mateoResult) . "</p>\n";

// Test 10 months (Miguel) - 0y 10m
$miguelResult = $who->calculateWeightForAge(4.2, 10, 'Male');
echo "<p>10 months (Miguel): " . json_encode($miguelResult) . "</p>\n";

// Test 26 months (Noah) - 2y 2m
$noahResult = $who->calculateWeightForAge(11.5, 26, 'Male');
echo "<p>26 months (Noah): " . json_encode($noahResult) . "</p>\n";

// Test 56 months (Owen) - 4y 8m
$owenResult = $who->calculateWeightForAge(16.8, 56, 'Male');
echo "<p>56 months (Owen): " . json_encode($owenResult) . "</p>\n";

// Test 16 months (Sebastian) - 1y 4m
$sebastianResult = $who->calculateWeightForAge(10.5, 16, 'Male');
echo "<p>16 months (Sebastian): " . json_encode($sebastianResult) . "</p>\n";
?>