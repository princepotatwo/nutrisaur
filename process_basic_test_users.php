<?php
/**
 * Process Basic Test Users with WHO Growth Standards
 * This script processes the 40 basic test users and calculates all nutritional data
 */

require_once 'who_growth_standards.php';
require_once 'config.php';

echo "<h1>Processing Basic Test Users with WHO Growth Standards</h1>";
echo "<p>This script will calculate all nutritional data for 40 diverse test users</p>";

$who = new WHOGrowthStandards();

try {
    $pdo = getDatabaseConnection();
    
    // Get all test users (those with TEST- screening_id)
    $sql = "SELECT * FROM community_users WHERE screening_id LIKE 'TEST-%' ORDER BY age, sex";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $testUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($testUsers)) {
        echo "<p style='color: red;'>No test users found. Please run the test_users_basic_fields.sql script first.</p>";
        exit;
    }
    
    echo "<p>Found " . count($testUsers) . " test users to process</p>";
    
    $successCount = 0;
    $errorCount = 0;
    $results = [];
    
    foreach ($testUsers as $index => $user) {
        echo "<div style='border: 1px solid #ddd; margin: 5px 0; padding: 10px; background: #f9f9f9;'>";
        echo "<h3>Processing User " . ($index + 1) . ": " . $user['name'] . "</h3>";
        
        // Display basic info
        $ageInMonths = $user['age']; // Age is already calculated in the database
        echo "<p><strong>Basic Info:</strong> ";
        echo "Screening ID: {$user['screening_id']}, ";
        echo "Age: {$ageInMonths} months, ";
        echo "Sex: {$user['sex']}, ";
        echo "Weight: {$user['weight_kg']} kg, ";
        echo "Height: {$user['height_cm']} cm</p>";
        
        try {
            // Process growth standards
            $growthResults = $who->processAllGrowthStandards(
                $user['weight_kg'],
                $user['height_cm'],
                $user['birthday'],
                $user['sex']
            );
            
            // Get comprehensive assessment
            $assessment = $who->getComprehensiveAssessment(
                $user['weight_kg'],
                $user['height_cm'],
                $user['birthday'],
                $user['sex']
            );
            
            if ($assessment['success']) {
                echo "<p style='color: green;'>✓ WHO Growth Standards calculated successfully</p>";
                
                // Display key results
                echo "<div style='margin-left: 20px;'>";
                echo "<p><strong>Growth Standards Results:</strong></p>";
                echo "<ul>";
                
                if (isset($growthResults['weight_for_age']['z_score'])) {
                    echo "<li>Weight-for-Age: " . $growthResults['weight_for_age']['z_score'] . " (" . $growthResults['weight_for_age']['classification'] . ")</li>";
                }
                
                if (isset($growthResults['height_for_age']['z_score'])) {
                    echo "<li>Height-for-Age: " . $growthResults['height_for_age']['z_score'] . " (" . $growthResults['height_for_age']['classification'] . ")</li>";
                }
                
                if (isset($growthResults['weight_for_height']['z_score'])) {
                    echo "<li>Weight-for-Height: " . $growthResults['weight_for_height']['z_score'] . " (" . $growthResults['weight_for_height']['classification'] . ")</li>";
                }
                
                if (isset($growthResults['bmi_for_age']['z_score'])) {
                    echo "<li>BMI-for-Age: " . $growthResults['bmi_for_age']['z_score'] . " (" . $growthResults['bmi_for_age']['classification'] . ")</li>";
                }
                
                echo "</ul>";
                
                echo "<p><strong>Nutritional Risk:</strong> <span style='color: " . getColorForRisk($assessment['nutritional_risk']) . "; font-weight: bold;'>" . $assessment['nutritional_risk'] . "</span></p>";
                
                if (!empty($assessment['risk_factors'])) {
                    echo "<p><strong>Risk Factors:</strong> " . implode(', ', $assessment['risk_factors']) . "</p>";
                }
                
                echo "</div>";
                
                // Update database with calculated values
                $updateSql = "UPDATE community_users SET 
                    age = :age,
                    bmi = :bmi,
                    bmi_category = :bmi_category,
                    `bmi-for-age` = :bmi_for_age_z,
                    `weight-for-height` = :weight_for_height_z,
                    `weight-for-age` = :weight_for_age_z,
                    `weight-for-length` = :weight_for_length_z,
                    `height-for-age` = :height_for_age_z,
                    nutritional_risk = :nutritional_risk,
                    muac_cm = :muac_cm,
                    muac_category = :muac_category,
                    follow_up_required = :follow_up_required,
                    notes = :notes
                    WHERE email = :email";
                
                $updateStmt = $pdo->prepare($updateSql);
                
                // Calculate MUAC (simplified estimation)
                $muac = calculateMUAC($user['weight_kg'], $user['height_cm'], $ageInMonths);
                $muacCategory = getMUACCategory($muac, $ageInMonths);
                
                $updateStmt->execute([
                    ':age' => $ageInMonths,
                    ':bmi' => $growthResults['bmi'],
                    ':bmi_category' => $growthResults['bmi_for_age']['classification'],
                    ':bmi_for_age_z' => $growthResults['bmi_for_age']['z_score'],
                    ':weight_for_height_z' => $growthResults['weight_for_height']['z_score'],
                    ':weight_for_age_z' => $growthResults['weight_for_age']['z_score'],
                    ':weight_for_length_z' => $growthResults['weight_for_length']['z_score'],
                    ':height_for_age_z' => $growthResults['height_for_age']['z_score'],
                    ':nutritional_risk' => $assessment['nutritional_risk'],
                    ':muac_cm' => $muac,
                    ':muac_category' => $muacCategory,
                    ':follow_up_required' => ($assessment['nutritional_risk'] !== 'Low') ? 1 : 0,
                    ':notes' => 'Processed by WHO Growth Standards - ' . implode(', ', $assessment['recommendations']),
                    ':email' => $user['screening_id']
                ]);
                
                echo "<p style='color: green;'>✓ Database updated successfully</p>";
                $successCount++;
                
                // Store results for summary
                $results[] = [
                    'name' => $user['screening_id'],
                    'age' => $ageInMonths,
                    'sex' => $user['sex'],
                    'risk' => $assessment['nutritional_risk'],
                    'wfa' => $growthResults['weight_for_age']['classification'],
                    'hfa' => $growthResults['height_for_age']['classification'],
                    'wfh' => $growthResults['weight_for_height']['classification'],
                    'bmi' => $growthResults['bmi_for_age']['classification']
                ];
                
            } else {
                echo "<p style='color: red;'>✗ Error in assessment: " . implode(', ', $assessment['errors']) . "</p>";
                $errorCount++;
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Exception: " . $e->getMessage() . "</p>";
            $errorCount++;
        }
        
        echo "</div>";
    }
    
    // Summary
    echo "<h2>Processing Summary</h2>";
    echo "<p><strong>Total processed:</strong> " . count($testUsers) . "</p>";
    echo "<p><strong>Successful:</strong> <span style='color: green;'>" . $successCount . "</span></p>";
    echo "<p><strong>Errors:</strong> <span style='color: red;'>" . $errorCount . "</span></p>";
    
    if ($successCount > 0) {
        // Risk distribution
        $riskCounts = [];
        foreach ($results as $result) {
            $risk = $result['risk'];
            $riskCounts[$risk] = ($riskCounts[$risk] ?? 0) + 1;
        }
        
        echo "<h3>Nutritional Risk Distribution</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Risk Level</th><th>Count</th><th>Percentage</th></tr>";
        foreach ($riskCounts as $risk => $count) {
            $percentage = round(($count / $successCount) * 100, 1);
            echo "<tr>";
            echo "<td style='color: " . getColorForRisk($risk) . ";'>" . $risk . "</td>";
            echo "<td>" . $count . "</td>";
            echo "<td>" . $percentage . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Age group distribution
        echo "<h3>Age Group Distribution</h3>";
        $ageGroups = [
            'Newborns (0-3m)' => 0,
            'Infants (4-12m)' => 0,
            'Toddlers (13-24m)' => 0,
            'Preschoolers (25-36m)' => 0,
            'Older Preschoolers (37-60m)' => 0,
            'Edge Cases (61-71m)' => 0
        ];
        
        foreach ($results as $result) {
            $age = $result['age'];
            if ($age <= 3) $ageGroups['Newborns (0-3m)']++;
            elseif ($age <= 12) $ageGroups['Infants (4-12m)']++;
            elseif ($age <= 24) $ageGroups['Toddlers (13-24m)']++;
            elseif ($age <= 36) $ageGroups['Preschoolers (25-36m)']++;
            elseif ($age <= 60) $ageGroups['Older Preschoolers (37-60m)']++;
            else $ageGroups['Edge Cases (61-71m)']++;
        }
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Age Group</th><th>Count</th><th>Percentage</th></tr>";
        foreach ($ageGroups as $group => $count) {
            $percentage = round(($count / $successCount) * 100, 1);
            echo "<tr>";
            echo "<td>" . $group . "</td>";
            echo "<td>" . $count . "</td>";
            echo "<td>" . $percentage . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Sample Results</h3>";
        echo "<p>Here are some sample results from the processed users:</p>";
        
        // Show first 10 results
        $samples = array_slice($results, 0, 10);
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
        echo "<tr><th>Name</th><th>Age</th><th>Sex</th><th>Risk</th><th>WFA</th><th>HFA</th><th>WFH</th><th>BMI</th></tr>";
        
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>" . $sample['name'] . "</td>";
            echo "<td>" . $sample['age'] . "m</td>";
            echo "<td>" . $sample['sex'] . "</td>";
            echo "<td style='color: " . getColorForRisk($sample['risk']) . ";'>" . $sample['risk'] . "</td>";
            echo "<td>" . $sample['wfa'] . "</td>";
            echo "<td>" . $sample['hfa'] . "</td>";
            echo "<td>" . $sample['wfh'] . "</td>";
            echo "<td>" . $sample['bmi'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
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

function calculateMUAC($weight, $height, $ageInMonths) {
    // Simplified MUAC calculation based on weight and height
    // This is an approximation - in real scenarios, MUAC should be measured directly
    $bmi = $weight / pow($height / 100, 2);
    
    if ($ageInMonths <= 12) {
        // For infants, MUAC is typically 10-16 cm
        return round(10 + ($bmi - 13) * 2, 1);
    } elseif ($ageInMonths <= 24) {
        // For toddlers, MUAC is typically 12-18 cm
        return round(12 + ($bmi - 14) * 2, 1);
    } else {
        // For older children, MUAC is typically 14-20 cm
        return round(14 + ($bmi - 15) * 2, 1);
    }
}

function getMUACCategory($muac, $ageInMonths) {
    if ($ageInMonths <= 60) { // Under 5 years
        if ($muac < 11.5) return 'Severe';
        if ($muac < 12.5) return 'Moderate';
        return 'Normal';
    } else {
        // For older children, use different thresholds
        if ($muac < 13.5) return 'Severe';
        if ($muac < 14.5) return 'Moderate';
        return 'Normal';
    }
}
?>
