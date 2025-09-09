<?php
/**
 * Verify Test Results
 * This script displays the processed test users and their WHO Growth Standards results
 */

require_once 'config.php';

echo "<h1>Test Users - WHO Growth Standards Results</h1>";
echo "<p>This shows the processed test users with all calculated nutritional data</p>";

try {
    $pdo = getDatabaseConnection();
    
    // Get all processed test users
    $sql = "SELECT 
        screening_id, municipality, barangay, sex, birthday, age, weight_kg, height_cm, bmi,
        `weight-for-age` as wfa_z, `height-for-age` as hfa_z, 
        `weight-for-height` as wfh_z, `bmi-for-age` as bfa_z,
        bmi_category, nutritional_risk, muac_cm, muac_category,
        follow_up_required, notes
        FROM community_users 
        WHERE screening_id LIKE 'TEST-%' 
        ORDER BY age, sex";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $testUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($testUsers)) {
        echo "<p style='color: red;'>No processed test users found. Please run process_basic_test_users.php first.</p>";
        exit;
    }
    
    echo "<p>Found " . count($testUsers) . " processed test users</p>";
    
    // Display all users
    foreach ($testUsers as $index => $user) {
        echo "<div style='border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #f9f9f9;'>";
        echo "<h3>" . ($index + 1) . ". " . $user['screening_id'] . " (" . $user['municipality'] . ", " . $user['barangay'] . ")</h3>";
        
        // Basic info
        echo "<div style='display: flex; gap: 20px; margin-bottom: 10px;'>";
        echo "<div><strong>Age:</strong> " . $user['age'] . " months</div>";
        echo "<div><strong>Sex:</strong> " . $user['sex'] . "</div>";
        echo "<div><strong>Weight:</strong> " . $user['weight_kg'] . " kg</div>";
        echo "<div><strong>Height:</strong> " . $user['height_cm'] . " cm</div>";
        echo "<div><strong>BMI:</strong> " . $user['bmi'] . "</div>";
        echo "</div>";
        
        // Growth standards
        echo "<h4>WHO Growth Standards Z-Scores:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%; margin-bottom: 10px;'>";
        echo "<tr><th>Indicator</th><th>Z-Score</th><th>Classification</th></tr>";
        
        echo "<tr>";
        echo "<td>Weight-for-Age</td>";
        echo "<td>" . ($user['wfa_z'] ?? 'N/A') . "</td>";
        echo "<td style='color: " . getColorForClassification($user['bmi_category']) . ";'>" . getClassificationFromZScore($user['wfa_z']) . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td>Height-for-Age</td>";
        echo "<td>" . ($user['hfa_z'] ?? 'N/A') . "</td>";
        echo "<td style='color: " . getColorForClassification($user['bmi_category']) . ";'>" . getClassificationFromZScore($user['hfa_z']) . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td>Weight-for-Height</td>";
        echo "<td>" . ($user['wfh_z'] ?? 'N/A') . "</td>";
        echo "<td style='color: " . getColorForClassification($user['bmi_category']) . ";'>" . getClassificationFromZScore($user['wfh_z']) . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td>BMI-for-Age</td>";
        echo "<td>" . ($user['bfa_z'] ?? 'N/A') . "</td>";
        echo "<td style='color: " . getColorForClassification($user['bmi_category']) . ";'>" . $user['bmi_category'] . "</td>";
        echo "</tr>";
        
        echo "</table>";
        
        // Nutritional assessment
        echo "<div style='display: flex; gap: 20px; margin-bottom: 10px;'>";
        echo "<div><strong>Nutritional Risk:</strong> <span style='color: " . getColorForRisk($user['nutritional_risk']) . "; font-weight: bold;'>" . $user['nutritional_risk'] . "</span></div>";
        echo "<div><strong>MUAC:</strong> " . $user['muac_cm'] . " cm (" . $user['muac_category'] . ")</div>";
        echo "<div><strong>Follow-up Required:</strong> " . ($user['follow_up_required'] ? 'Yes' : 'No') . "</div>";
        echo "</div>";
        
        if ($user['notes']) {
            echo "<p><strong>Notes:</strong> " . $user['notes'] . "</p>";
        }
        
        echo "</div>";
    }
    
    // Summary statistics
    echo "<h2>Summary Statistics</h2>";
    
    // Risk distribution
    $riskCounts = [];
    foreach ($testUsers as $user) {
        $risk = $user['nutritional_risk'];
        $riskCounts[$risk] = ($riskCounts[$risk] ?? 0) + 1;
    }
    
    echo "<h3>Nutritional Risk Distribution</h3>";
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
    echo "<h3>Age Group Distribution</h3>";
    $ageGroups = [
        'Newborns (0-3m)' => 0,
        'Infants (4-12m)' => 0,
        'Toddlers (13-24m)' => 0,
        'Preschoolers (25-36m)' => 0,
        'Older Preschoolers (37-60m)' => 0,
        'Edge Cases (61-71m)' => 0
    ];
    
    foreach ($testUsers as $user) {
        $age = $user['age'];
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
        $percentage = round(($count / $total) * 100, 1);
        echo "<tr>";
        echo "<td>" . $group . "</td>";
        echo "<td>" . $count . "</td>";
        echo "<td>" . $percentage . "%</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Follow-up required
    $followUpCount = count(array_filter($testUsers, function($user) {
        return $user['follow_up_required'] == 1;
    }));
    
    echo "<h3>Follow-up Requirements</h3>";
    echo "<p><strong>Users requiring follow-up:</strong> " . $followUpCount . " out of " . $total . " (" . round(($followUpCount / $total) * 100, 1) . "%)</p>";
    
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

function getClassificationFromZScore($zScore) {
    if ($zScore === null || $zScore === '') return 'N/A';
    
    $z = floatval($zScore);
    if ($z < -3) return 'Severely Underweight';
    if ($z < -2) return 'Underweight';
    if ($z <= 2) return 'Normal';
    return 'Overweight';
}
?>
