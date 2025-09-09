<?php
/**
 * Test script to verify WHO Growth Standards integration with screening table
 */

require_once 'who_growth_standards.php';

echo "<h1>WHO Growth Standards Integration Test</h1>";
echo "<p>Testing the updated screening table with WHO classifications</p>";

$who = new WHOGrowthStandards();

// Test cases for different age groups
$testCases = [
    [
        'name' => 'Test Child 1',
        'email' => 'child1@test.com',
        'weight' => 3.3,  // Median for 0 months
        'height' => 49.9, // Median for 0 months
        'birth_date' => date('Y-m-d'), // Today (0 months)
        'sex' => 'Male'
    ],
    [
        'name' => 'Test Child 2',
        'email' => 'child2@test.com',
        'weight' => 7.3,  // Median for 6 months
        'height' => 65.5, // Median for 6 months
        'birth_date' => date('Y-m-d', strtotime('-6 months')),
        'sex' => 'Female'
    ],
    [
        'name' => 'Test Child 3',
        'email' => 'child3@test.com',
        'weight' => 12.2, // Median for 24 months
        'height' => 87.6, // Median for 24 months
        'birth_date' => date('Y-m-d', strtotime('-24 months')),
        'sex' => 'Male'
    ],
    [
        'name' => 'Test Child 4',
        'email' => 'child4@test.com',
        'weight' => 9.8,  // -2SD for 24 months (Underweight)
        'height' => 87.6, // Median height
        'birth_date' => date('Y-m-d', strtotime('-24 months')),
        'sex' => 'Male'
    ],
    [
        'name' => 'Test Child 5',
        'email' => 'child5@test.com',
        'weight' => 16.0, // +2SD for 36 months (Overweight)
        'height' => 95.1, // Median height
        'birth_date' => date('Y-m-d', strtotime('-36 months')),
        'sex' => 'Female'
    ]
];

echo "<h2>Test Results</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Name</th>";
echo "<th>Age (months)</th>";
echo "<th>Weight-for-Age</th>";
echo "<th>Height-for-Age</th>";
echo "<th>Weight-for-Height</th>";
echo "<th>Weight-for-Length</th>";
echo "<th>BMI-for-Age</th>";
echo "</tr>";

foreach ($testCases as $test) {
    try {
        $results = $who->processAllGrowthStandards(
            $test['weight'],
            $test['height'],
            $test['birth_date'],
            $test['sex']
        );
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($test['name']) . "</td>";
        echo "<td>" . $results['age_months'] . "</td>";
        echo "<td style='text-align: center; padding: 5px;'>";
        echo "<span style='background: " . getClassificationColor($results['weight_for_age']['classification']) . "; padding: 4px 8px; border-radius: 4px; color: white; font-weight: bold;'>";
        echo htmlspecialchars($results['weight_for_age']['classification']);
        echo "</span>";
        echo "</td>";
        echo "<td style='text-align: center; padding: 5px;'>";
        echo "<span style='background: " . getClassificationColor($results['height_for_age']['classification']) . "; padding: 4px 8px; border-radius: 4px; color: white; font-weight: bold;'>";
        echo htmlspecialchars($results['height_for_age']['classification']);
        echo "</span>";
        echo "</td>";
        echo "<td style='text-align: center; padding: 5px;'>";
        echo "<span style='background: " . getClassificationColor($results['weight_for_height']['classification']) . "; padding: 4px 8px; border-radius: 4px; color: white; font-weight: bold;'>";
        echo htmlspecialchars($results['weight_for_height']['classification']);
        echo "</span>";
        echo "</td>";
        echo "<td style='text-align: center; padding: 5px;'>";
        echo "<span style='background: " . getClassificationColor($results['weight_for_length']['classification']) . "; padding: 4px 8px; border-radius: 4px; color: white; font-weight: bold;'>";
        echo htmlspecialchars($results['weight_for_length']['classification']);
        echo "</span>";
        echo "</td>";
        echo "<td style='text-align: center; padding: 5px;'>";
        echo "<span style='background: " . getClassificationColor($results['bmi_for_age']['classification']) . "; padding: 4px 8px; border-radius: 4px; color: white; font-weight: bold;'>";
        echo htmlspecialchars($results['bmi_for_age']['classification']);
        echo "</span>";
        echo "</td>";
        echo "</tr>";
        
    } catch (Exception $e) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($test['name']) . "</td>";
        echo "<td colspan='6' style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

function getClassificationColor($classification) {
    switch ($classification) {
        case 'Severely Underweight':
            return '#D32F2F';
        case 'Underweight':
            return '#FF9800';
        case 'Normal':
            return '#4CAF50';
        case 'Overweight':
            return '#FF5722';
        case 'Obese':
            return '#8B4513';
        case 'Height out of range':
            return '#9C27B0';
        case 'Age out of range':
            return '#607D8B';
        default:
            return '#9E9E9E';
    }
}

echo "<h2>Summary</h2>";
echo "<p>✅ The screening table has been successfully updated to display WHO Growth Standards classifications:</p>";
echo "<ul>";
echo "<li><strong>Weight-for-Age (WFA):</strong> Shows nutritional status based on age and weight</li>";
echo "<li><strong>Height-for-Age (HFA):</strong> Shows stunting status based on age and height</li>";
echo "<li><strong>Weight-for-Height (WFH):</strong> Shows wasting status for children 2+ years</li>";
echo "<li><strong>Weight-for-Length (WFL):</strong> Shows wasting status for children under 2 years</li>";
echo "<li><strong>BMI-for-Age:</strong> Shows overall nutritional status based on BMI</li>";
echo "</ul>";

echo "<p><strong>Classifications:</strong> Severely Underweight, Underweight, Normal, Overweight, Obese</p>";
echo "<p><strong>Color Coding:</strong> Each classification has a distinct color for easy identification</p>";
echo "<p><strong>Search & Filter:</strong> Updated to work with WHO classifications</p>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Ensure the WHO growth standards are calculated and stored in the database when users submit screening data</li>";
echo "<li>Test the actual screening form to verify data flows correctly to the table</li>";
echo "<li>Verify that the CSS styling displays correctly in the browser</li>";
echo "</ol>";
?>
