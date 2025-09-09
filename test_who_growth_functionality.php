<?php
/**
 * Test Script for WHO Growth Standards Functionality
 * This script tests the WHO Growth Standards with the diverse test users
 */

require_once 'who_growth_standards.php';
require_once 'config.php';

echo "<h1>WHO Growth Standards Test Results</h1>";
echo "<p>Testing with diverse test users to validate functionality</p>";

$who = new WHOGrowthStandards();

// Get all test users from database
try {
    $pdo = getDatabaseConnection();
    $sql = "SELECT * FROM community_users WHERE screening_id LIKE 'TEST-%' ORDER BY age, sex";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $testUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($testUsers)) {
        echo "<p style='color: red;'>No test users found. Please run the test_users_who_growth_standards.sql script first.</p>";
        exit;
    }
    
    echo "<p>Found " . count($testUsers) . " test users</p>";
    
    // Process each test user
    foreach ($testUsers as $index => $user) {
        echo "<div style='border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #f9f9f9;'>";
        echo "<h3>Test Case " . ($index + 1) . ": " . $user['screening_id'] . "</h3>";
        
        // Display basic info
        echo "<p><strong>Basic Info:</strong> ";
        echo "Age: {$user['age']} months, ";
        echo "Sex: {$user['sex']}, ";
        echo "Weight: {$user['weight_kg']} kg, ";
        echo "Height: {$user['height_cm']} cm, ";
        echo "BMI: {$user['bmi']}</p>";
        
        // Process growth standards
        $results = $who->processAllGrowthStandards(
            $user['weight_kg'],
            $user['height_cm'],
            $user['birthday'],
            $user['sex']
        );
        
        // Display results
        echo "<h4>WHO Growth Standards Results:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
        echo "<tr><th>Indicator</th><th>Z-Score</th><th>Classification</th><th>Median</th><th>SD</th></tr>";
        
        // Weight-for-Age
        if (isset($results['weight_for_age']['z_score'])) {
            echo "<tr>";
            echo "<td>Weight-for-Age</td>";
            echo "<td>" . $results['weight_for_age']['z_score'] . "</td>";
            echo "<td style='color: " . getColorForClassification($results['weight_for_age']['classification']) . ";'>" . $results['weight_for_age']['classification'] . "</td>";
            echo "<td>" . $results['weight_for_age']['median'] . "</td>";
            echo "<td>" . $results['weight_for_age']['sd'] . "</td>";
            echo "</tr>";
        }
        
        // Height-for-Age
        if (isset($results['height_for_age']['z_score'])) {
            echo "<tr>";
            echo "<td>Height-for-Age</td>";
            echo "<td>" . $results['height_for_age']['z_score'] . "</td>";
            echo "<td style='color: " . getColorForClassification($results['height_for_age']['classification']) . ";'>" . $results['height_for_age']['classification'] . "</td>";
            echo "<td>" . $results['height_for_age']['median'] . "</td>";
            echo "<td>" . $results['height_for_age']['sd'] . "</td>";
            echo "</tr>";
        }
        
        // Weight-for-Height
        if (isset($results['weight_for_height']['z_score'])) {
            echo "<tr>";
            echo "<td>Weight-for-Height</td>";
            echo "<td>" . $results['weight_for_height']['z_score'] . "</td>";
            echo "<td style='color: " . getColorForClassification($results['weight_for_height']['classification']) . ";'>" . $results['weight_for_height']['classification'] . "</td>";
            echo "<td>" . $results['weight_for_height']['median'] . "</td>";
            echo "<td>" . $results['weight_for_height']['sd'] . "</td>";
            echo "</tr>";
        }
        
        // Weight-for-Length
        if (isset($results['weight_for_length']['z_score'])) {
            echo "<tr>";
            echo "<td>Weight-for-Length</td>";
            echo "<td>" . $results['weight_for_length']['z_score'] . "</td>";
            echo "<td style='color: " . getColorForClassification($results['weight_for_length']['classification']) . ";'>" . $results['weight_for_length']['classification'] . "</td>";
            echo "<td>" . $results['weight_for_length']['median'] . "</td>";
            echo "<td>" . $results['weight_for_length']['sd'] . "</td>";
            echo "</tr>";
        }
        
        // BMI-for-Age
        if (isset($results['bmi_for_age']['z_score'])) {
            echo "<tr>";
            echo "<td>BMI-for-Age</td>";
            echo "<td>" . $results['bmi_for_age']['z_score'] . "</td>";
            echo "<td style='color: " . getColorForClassification($results['bmi_for_age']['classification']) . ";'>" . $results['bmi_for_age']['classification'] . "</td>";
            echo "<td>" . $results['bmi_for_age']['median'] . "</td>";
            echo "<td>" . $results['bmi_for_age']['sd'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Get comprehensive assessment
        $assessment = $who->getComprehensiveAssessment(
            $user['weight_kg'],
            $user['height_cm'],
            $user['birthday'],
            $user['sex']
        );
        
        if ($assessment['success']) {
            echo "<h4>Nutritional Risk Assessment:</h4>";
            echo "<p><strong>Risk Level:</strong> <span style='color: " . getColorForRisk($assessment['nutritional_risk']) . "; font-weight: bold;'>" . $assessment['nutritional_risk'] . "</span></p>";
            
            if (!empty($assessment['risk_factors'])) {
                echo "<p><strong>Risk Factors:</strong></p><ul>";
                foreach ($assessment['risk_factors'] as $factor) {
                    echo "<li>" . $factor . "</li>";
                }
                echo "</ul>";
            }
            
            if (!empty($assessment['recommendations'])) {
                echo "<p><strong>Recommendations:</strong></p><ul>";
                foreach ($assessment['recommendations'] as $recommendation) {
                    echo "<li>" . $recommendation . "</li>";
                }
                echo "</ul>";
            }
        }
        
        // Save results to database
        $saveResult = $who->saveGrowthStandardsToDatabase($user['screening_id'], $results);
        if ($saveResult['success']) {
            echo "<p style='color: green;'>✓ Results saved to database successfully</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to save results: " . $saveResult['error'] . "</p>";
        }
        
        echo "<p><strong>Notes:</strong> " . $user['notes'] . "</p>";
        echo "</div>";
    }
    
    // Summary statistics
    echo "<h2>Summary Statistics</h2>";
    
    // Count by nutritional risk
    $riskCounts = [];
    foreach ($testUsers as $user) {
        $risk = $user['nutritional_risk'];
        $riskCounts[$risk] = ($riskCounts[$risk] ?? 0) + 1;
    }
    
    echo "<h3>Nutritional Risk Distribution:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Risk Level</th><th>Count</th><th>Percentage</th></tr>";
    $total = count($testUsers);
    foreach ($riskCounts as $risk => $count) {
        $percentage = round(($count / $total) * 100, 1);
        echo "<tr>";
        echo "<td style='color: " . getColorForRisk($risk) . ";'>" . $risk . "</td>";
        echo "<td>" . $count . "</td>";
        echo "<td>" . $percentage . "%</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Age group distribution
    echo "<h3>Age Group Distribution:</h3>";
    $ageGroups = [
        'Infants (0-12m)' => 0,
        'Toddlers (13-24m)' => 0,
        'Preschoolers (25-36m)' => 0,
        'Older Preschoolers (37-60m)' => 0,
        'Edge Cases (61-71m)' => 0
    ];
    
    foreach ($testUsers as $user) {
        $age = $user['age'];
        if ($age <= 12) $ageGroups['Infants (0-12m)']++;
        elseif ($age <= 24) $ageGroups['Toddlers (13-24m)']++;
        elseif ($age <= 36) $ageGroups['Preschoolers (25-36m)']++;
        elseif ($age <= 60) $ageGroups['Older Preschoolers (37-60m)']++;
        else $ageGroups['Edge Cases (61-71m)']++;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Age Group</th><th>Count</th><th>Percentage</th></tr>";
    foreach ($ageGroups as $group => $count) {
        $percentage = round(($count / $total) * 100, 1);
        echo "<tr>";
        echo "<td>" . $group . "</td>";
        echo "<td>" . $count . "</td>";
        echo "<td>" . $percentage . "%</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

function getColorForClassification($classification) {
    switch ($classification) {
        case 'Severely Underweight':
            return 'red';
        case 'Underweight':
            return 'orange';
        case 'Normal':
            return 'green';
        case 'Overweight':
            return 'purple';
        default:
            return 'black';
    }
}

function getColorForRisk($risk) {
    switch ($risk) {
        case 'Severe':
            return 'red';
        case 'Moderate':
            return 'orange';
        case 'Low':
            return 'green';
        default:
            return 'black';
    }
}
?>
