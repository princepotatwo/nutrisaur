<?php
/**
 * Process Test Users with WHO Growth Standards
 * This script processes all test users and saves the WHO Growth Standards results
 */

require_once 'who_growth_standards.php';
require_once 'config.php';

echo "<h1>Processing Test Users with WHO Growth Standards</h1>";

$who = new WHOGrowthStandards();

try {
    $pdo = getDatabaseConnection();
    
    // Get all test users
    $sql = "SELECT screening_id, weight_kg, height_cm, birthday, sex, age FROM community_users WHERE screening_id LIKE 'TEST-%' ORDER BY age";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $testUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($testUsers)) {
        echo "<p style='color: red;'>No test users found. Please run the test_users_who_growth_standards.sql script first.</p>";
        exit;
    }
    
    echo "<p>Processing " . count($testUsers) . " test users...</p>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($testUsers as $user) {
        echo "<p>Processing " . $user['screening_id'] . " (Age: " . $user['age'] . " months, " . $user['sex'] . ")... ";
        
        try {
            // Process and save growth standards
            $result = $who->processAndSaveGrowthStandards($user['screening_id']);
            
            if ($result['success']) {
                echo "<span style='color: green;'>✓ Success</span></p>";
                $successCount++;
                
                // Display key results
                if (isset($result['results'])) {
                    $results = $result['results'];
                    echo "<div style='margin-left: 20px; font-size: 0.9em;'>";
                    echo "WFA: " . ($results['weight_for_age']['z_score'] ?? 'N/A') . " (" . ($results['weight_for_age']['classification'] ?? 'N/A') . "), ";
                    echo "HFA: " . ($results['height_for_age']['z_score'] ?? 'N/A') . " (" . ($results['height_for_age']['classification'] ?? 'N/A') . "), ";
                    echo "WFH: " . ($results['weight_for_height']['z_score'] ?? 'N/A') . " (" . ($results['weight_for_height']['classification'] ?? 'N/A') . "), ";
                    echo "BMI: " . ($results['bmi'] ?? 'N/A') . " (" . ($results['bmi_for_age']['classification'] ?? 'N/A') . ")";
                    echo "</div>";
                }
            } else {
                echo "<span style='color: red;'>✗ Error: " . $result['error'] . "</span></p>";
                $errorCount++;
            }
        } catch (Exception $e) {
            echo "<span style='color: red;'>✗ Exception: " . $e->getMessage() . "</span></p>";
            $errorCount++;
        }
    }
    
    echo "<h2>Processing Summary</h2>";
    echo "<p><strong>Total processed:</strong> " . count($testUsers) . "</p>";
    echo "<p><strong>Successful:</strong> <span style='color: green;'>" . $successCount . "</span></p>";
    echo "<p><strong>Errors:</strong> <span style='color: red;'>" . $errorCount . "</span></p>";
    
    if ($successCount > 0) {
        echo "<h2>Verification Query</h2>";
        echo "<p>Run this query to verify the WHO Growth Standards data was saved:</p>";
        echo "<pre>";
        echo "SELECT \n";
        echo "    screening_id,\n";
        echo "    age,\n";
        echo "    sex,\n";
        echo "    weight_kg,\n";
        echo "    height_cm,\n";
        echo "    bmi,\n";
        echo "    \`weight-for-age\` as wfa_z,\n";
        echo "    \`height-for-age\` as hfa_z,\n";
        echo "    \`weight-for-height\` as wfh_z,\n";
        echo "    \`bmi-for-age\` as bfa_z,\n";
        echo "    bmi_category\n";
        echo "FROM community_users \n";
        echo "WHERE screening_id LIKE 'TEST-%'\n";
        echo "ORDER BY age, sex;";
        echo "</pre>";
        
        echo "<h2>Sample Results</h2>";
        echo "<p>Here are some sample results from the processed test users:</p>";
        
        // Show sample results
        $sampleSql = "SELECT 
            screening_id, age, sex, weight_kg, height_cm, bmi,
            `weight-for-age` as wfa_z, `height-for-age` as hfa_z, 
            `weight-for-height` as wfh_z, `bmi-for-age` as bfa_z, bmi_category
            FROM community_users 
            WHERE screening_id LIKE 'TEST-%' 
            ORDER BY age, sex 
            LIMIT 10";
        
        $stmt = $pdo->prepare($sampleSql);
        $stmt->execute();
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width: 100%;'>";
        echo "<tr><th>Screening ID</th><th>Age</th><th>Sex</th><th>Weight</th><th>Height</th><th>BMI</th><th>WFA Z</th><th>HFA Z</th><th>WFH Z</th><th>BFA Z</th><th>BMI Category</th></tr>";
        
        foreach ($samples as $sample) {
            echo "<tr>";
            echo "<td>" . $sample['screening_id'] . "</td>";
            echo "<td>" . $sample['age'] . "</td>";
            echo "<td>" . $sample['sex'] . "</td>";
            echo "<td>" . $sample['weight_kg'] . "</td>";
            echo "<td>" . $sample['height_cm'] . "</td>";
            echo "<td>" . $sample['bmi'] . "</td>";
            echo "<td>" . ($sample['wfa_z'] ?? 'N/A') . "</td>";
            echo "<td>" . ($sample['hfa_z'] ?? 'N/A') . "</td>";
            echo "<td>" . ($sample['wfh_z'] ?? 'N/A') . "</td>";
            echo "<td>" . ($sample['bfa_z'] ?? 'N/A') . "</td>";
            echo "<td>" . ($sample['bmi_category'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
