<?php
/**
 * Test file for WHO Growth Standards Age Ranges
 * Demonstrates the updated decision tree with age-based routing
 */

require_once 'who_growth_standards.php';

echo "<h1>WHO Growth Standards - Age Range Testing</h1>";

$who = new WHOGrowthStandards();

// Test cases covering different age ranges
$testCases = [
    [
        'name' => '2-year-old (Growth Standards)',
        'weight' => 12.5,
        'height' => 85,
        'birth_date' => '2022-01-15',
        'sex' => 'Male',
        'expected_range' => 'WHO Growth Standards (0-60 months)'
    ],
    [
        'name' => '4-year-old (Growth Standards)',
        'weight' => 16.0,
        'height' => 105,
        'birth_date' => '2020-03-20',
        'sex' => 'Female',
        'expected_range' => 'WHO Growth Standards (0-60 months)'
    ],
    [
        'name' => '6-year-old (Growth References)',
        'weight' => 20.0,
        'height' => 115,
        'birth_date' => '2018-06-10',
        'sex' => 'Male',
        'expected_range' => 'WHO Growth References (5-19 years)'
    ],
    [
        'name' => '12-year-old (Growth References)',
        'weight' => 45.0,
        'height' => 150,
        'birth_date' => '2012-01-01',
        'sex' => 'Female',
        'expected_range' => 'WHO Growth References (5-19 years)'
    ],
    [
        'name' => '18-year-old (Growth References)',
        'weight' => 65.0,
        'height' => 170,
        'birth_date' => '2006-01-01',
        'sex' => 'Male',
        'expected_range' => 'WHO Growth References (5-19 years)'
    ],
    [
        'name' => '25-year-old (Out of Range)',
        'weight' => 70.0,
        'height' => 175,
        'birth_date' => '1999-01-01',
        'sex' => 'Female',
        'expected_range' => 'Out of range'
    ]
];

foreach ($testCases as $i => $test) {
    echo "<h2>Test Case " . ($i + 1) . ": " . $test['name'] . "</h2>";
    echo "<p><strong>Input:</strong> Weight: {$test['weight']} kg, Height: {$test['height']} cm, Birth Date: {$test['birth_date']}, Sex: {$test['sex']}</p>";
    
    $results = $who->processAllGrowthStandards(
        $test['weight'],
        $test['height'],
        $test['birth_date'],
        $test['sex']
    );
    
    echo "<h3>Results:</h3>";
    echo "<ul>";
    echo "<li><strong>Age:</strong> " . $results['age_months'] . " months (" . round($results['age_months'] / 12, 1) . " years)</li>";
    echo "<li><strong>BMI:</strong> " . $results['bmi'] . "</li>";
    echo "<li><strong>Age Range:</strong> " . $results['age_range'] . "</li>";
    echo "</ul>";
    
    // Check if the expected range matches
    if (isset($results['age_range']) && $results['age_range'] === $test['expected_range']) {
        echo "<p style='color: green;'><strong>✓ Correct age range detected</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Incorrect age range detected</strong></p>";
    }
    
    // Show applicable indicators
    echo "<h4>Applicable Growth Indicators:</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Indicator</th><th>Z-Score</th><th>Classification</th><th>Status</th></tr>";
    
    foreach ($results as $key => $value) {
        if (is_array($value) && isset($value['z_score']) && isset($value['classification'])) {
            $indicator = ucwords(str_replace('_', ' ', $key));
            $status = $value['z_score'] !== null ? 'Active' : 'Not applicable';
            echo "<tr>";
            echo "<td>{$indicator}</td>";
            echo "<td>" . ($value['z_score'] ?? 'N/A') . "</td>";
            echo "<td>{$value['classification']}</td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // Show notes if any
    if (isset($results['weight_for_age']['note'])) {
        echo "<p><strong>Note:</strong> " . $results['weight_for_age']['note'] . "</p>";
    }
    
    // Show error if any
    if (isset($results['error'])) {
        echo "<p style='color: red;'><strong>Error:</strong> " . $results['error'] . "</p>";
    }
    
    echo "<hr>";
}

// Test the age range checking functions
echo "<h2>Age Range Function Tests</h2>";

$ageTests = [
    ['age' => 24, 'expected' => 'Growth Standards'],
    ['age' => 60, 'expected' => 'Growth Standards'],
    ['age' => 61, 'expected' => 'Growth References'],
    ['age' => 120, 'expected' => 'Growth References'],
    ['age' => 228, 'expected' => 'Growth References'],
    ['age' => 229, 'expected' => 'Out of Range']
];

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Age (months)</th><th>Growth Standards</th><th>Growth References</th><th>Expected</th></tr>";

foreach ($ageTests as $test) {
    $age = $test['age'];
    $standards = $who->isWithinGrowthStandardsRange($age) ? 'Yes' : 'No';
    $references = $who->isWithinGrowthReferencesRange($age) ? 'Yes' : 'No';
    
    $actual = 'Out of Range';
    if ($who->isWithinGrowthStandardsRange($age)) {
        $actual = 'Growth Standards';
    } elseif ($who->isWithinGrowthReferencesRange($age)) {
        $actual = 'Growth References';
    }
    
    $correct = $actual === $test['expected'] ? '✓' : '✗';
    
    echo "<tr>";
    echo "<td>{$age}</td>";
    echo "<td>{$standards}</td>";
    echo "<td>{$references}</td>";
    echo "<td>{$test['expected']} {$correct}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Summary</h2>";
echo "<ul>";
echo "<li><strong>0-60 months (0-5 years):</strong> Use WHO Growth Standards (WFA, HFA, WFH, WFL, BMI-for-Age)</li>";
echo "<li><strong>61-228 months (5-19 years):</strong> Use WHO Growth References (BMI-for-Age only)</li>";
echo "<li><strong>>228 months (>19 years):</strong> Out of range for WHO child growth assessment</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> This implementation correctly follows WHO guidelines by using different standards and references based on age ranges.</p>";
?>
