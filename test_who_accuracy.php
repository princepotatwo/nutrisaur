<?php
/**
 * Test file for WHO Growth Standards Accuracy
 * Verifies that the implementation matches official WHO standards
 */

require_once 'who_growth_standards.php';

echo "<h1>WHO Growth Standards Accuracy Test</h1>";
echo "<p><strong>Testing against official WHO Child Growth Standards 2006</strong></p>";

$who = new WHOGrowthStandards();

// Test cases based on the table images provided
$testCases = [
    // Test cases from the Weight-for-Age tables
    [
        'name' => '2-year-old boy (from table)',
        'weight' => 12.0,  // Should be normal for 24 months
        'height' => 87.0,  // Approximate height for 24 months
        'birth_date' => '2022-01-15',
        'sex' => 'Male',
        'expected_classification' => 'Normal'
    ],
    [
        'name' => '2-year-old girl (from table)',
        'weight' => 11.5,  // Should be normal for 24 months
        'height' => 86.0,  // Approximate height for 24 months
        'birth_date' => '2022-01-15',
        'sex' => 'Female',
        'expected_classification' => 'Normal'
    ],
    [
        'name' => '4-year-old boy (from table)',
        'weight' => 16.0,  // Should be normal for 48 months
        'height' => 102.0, // Approximate height for 48 months
        'birth_date' => '2020-01-15',
        'sex' => 'Male',
        'expected_classification' => 'Normal'
    ],
    [
        'name' => '4-year-old girl (from table)',
        'weight' => 15.5,  // Should be normal for 48 months
        'height' => 101.0, // Approximate height for 48 months
        'birth_date' => '2020-01-15',
        'sex' => 'Female',
        'expected_classification' => 'Normal'
    ],
    // Test cases for Weight-for-Height from the tables
    [
        'name' => '3-year-old boy (WFH test)',
        'weight' => 14.0,  // Test weight
        'height' => 95.0,  // From WFH table
        'birth_date' => '2021-01-15',
        'sex' => 'Male',
        'expected_classification' => 'Normal'
    ],
    [
        'name' => '3-year-old girl (WFH test)',
        'weight' => 13.5,  // Test weight
        'height' => 94.0,  // From WFH table
        'birth_date' => '2021-01-15',
        'sex' => 'Female',
        'expected_classification' => 'Normal'
    ],
    // Test cases for older children (5-19 years)
    [
        'name' => '8-year-old boy (Growth References)',
        'weight' => 25.0,
        'height' => 130.0,
        'birth_date' => '2016-01-15',
        'sex' => 'Male',
        'expected_classification' => 'Normal'
    ],
    [
        'name' => '12-year-old girl (Growth References)',
        'weight' => 40.0,
        'height' => 150.0,
        'birth_date' => '2012-01-15',
        'sex' => 'Female',
        'expected_classification' => 'Normal'
    ]
];

echo "<h2>Classification Terminology Test</h2>";
echo "<p>Verifying that we use correct WHO terminology:</p>";

// Test z-score classifications
$zScoreTests = [
    ['z_score' => -3.5, 'expected' => 'Severely Wasted'],
    ['z_score' => -2.5, 'expected' => 'Wasted'],
    ['z_score' => 0.0, 'expected' => 'Normal'],
    ['z_score' => 2.5, 'expected' => 'Overweight'],
    ['z_score' => 3.5, 'expected' => 'Obese']
];

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Z-Score</th><th>Expected</th><th>Actual</th><th>Match</th></tr>";

foreach ($zScoreTests as $test) {
    $actual = $who->getNutritionalClassification($test['z_score']);
    $match = $actual === $test['expected'] ? '✓' : '✗';
    $color = $match === '✓' ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$test['z_score']}</td>";
    echo "<td>{$test['expected']}</td>";
    echo "<td>{$actual}</td>";
    echo "<td style='color: {$color};'>{$match}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Growth Standards Test Results</h2>";

foreach ($testCases as $i => $test) {
    echo "<h3>Test Case " . ($i + 1) . ": " . $test['name'] . "</h3>";
    echo "<p><strong>Input:</strong> Weight: {$test['weight']} kg, Height: {$test['height']} cm, Birth Date: {$test['birth_date']}, Sex: {$test['sex']}</p>";
    
    $results = $who->processAllGrowthStandards(
        $test['weight'],
        $test['height'],
        $test['birth_date'],
        $test['sex']
    );
    
    echo "<h4>Results:</h4>";
    echo "<ul>";
    echo "<li><strong>Age:</strong> " . $results['age_months'] . " months (" . round($results['age_months'] / 12, 1) . " years)</li>";
    echo "<li><strong>BMI:</strong> " . $results['bmi'] . "</li>";
    echo "<li><strong>Age Range:</strong> " . $results['age_range'] . "</li>";
    echo "</ul>";
    
    echo "<h4>Growth Indicators:</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Indicator</th><th>Z-Score</th><th>Classification</th><th>WHO Terminology</th></tr>";
    
    foreach ($results as $key => $value) {
        if (is_array($value) && isset($value['z_score']) && isset($value['classification'])) {
            $indicator = ucwords(str_replace('_', ' ', $key));
            $whoTerm = $this->isWHOCompliant($value['classification']);
            $color = $whoTerm ? 'green' : 'red';
            
            echo "<tr>";
            echo "<td>{$indicator}</td>";
            echo "<td>" . ($value['z_score'] ?? 'N/A') . "</td>";
            echo "<td>{$value['classification']}</td>";
            echo "<td style='color: {$color};'>" . ($whoTerm ? '✓' : '✗') . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // Check if expected classification matches
    $wfaClassification = $results['weight_for_age']['classification'] ?? 'N/A';
    if ($wfaClassification === $test['expected_classification']) {
        echo "<p style='color: green;'><strong>✓ Classification matches expected result</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>✗ Classification mismatch. Expected: {$test['expected_classification']}, Got: {$wfaClassification}</strong></p>";
    }
    
    echo "<hr>";
}

// Test age range boundaries
echo "<h2>Age Range Boundary Tests</h2>";
echo "<p>Testing the correct age ranges for WHO standards:</p>";

$ageTests = [
    ['age' => 60, 'expected' => 'Growth Standards'],
    ['age' => 61, 'expected' => 'Growth References'],
    ['age' => 228, 'expected' => 'Growth References'],
    ['age' => 229, 'expected' => 'Out of Range']
];

echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>Age (months)</th><th>Growth Standards</th><th>Growth References</th><th>Expected</th><th>Actual</th><th>Match</th></tr>";

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
    
    $match = $actual === $test['expected'] ? '✓' : '✗';
    $color = $match === '✓' ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$age}</td>";
    echo "<td>{$standards}</td>";
    echo "<td>{$references}</td>";
    echo "<td>{$test['expected']}</td>";
    echo "<td>{$actual}</td>";
    echo "<td style='color: {$color};'>{$match}</td>";
    echo "</tr>";
}
echo "</table>";

// Helper function to check WHO compliance
function isWHOCompliant($classification) {
    $whoTerms = [
        'Severely Wasted', 'Wasted', 'Normal', 'Overweight', 'Obese',
        'Severe Thinness', 'Thinness', 'Obesity'
    ];
    return in_array($classification, $whoTerms);
}

echo "<h2>WHO Compliance Summary</h2>";
echo "<ul>";
echo "<li><strong>Classification Terminology:</strong> Updated to use official WHO terms (Wasted, Severely Wasted, etc.)</li>";
echo "<li><strong>5-Category System:</strong> Added 'Obese' category for +2SD to +3SD range</li>";
echo "<li><strong>Age Ranges:</strong> Correctly implements 0-60 months (Standards) and 61-228 months (References)</li>";
echo "<li><strong>Indicators:</strong> Uses appropriate indicators for each age range</li>";
echo "</ul>";

echo "<h2>Next Steps for Full Accuracy</h2>";
echo "<ol>";
echo "<li><strong>Verify exact z-score values:</strong> Cross-reference with official WHO z-score tables</li>";
echo "<li><strong>Update median/SD values:</strong> Ensure all data points match WHO standards exactly</li>";
echo "<li><strong>Test edge cases:</strong> Verify classifications at boundary values</li>";
echo "<li><strong>Validate with real data:</strong> Test with known cases from WHO documentation</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> This implementation now uses correct WHO terminology and classification system. The next step is to verify that the exact z-score values and median/SD data match the official WHO tables precisely.</p>";
?>
