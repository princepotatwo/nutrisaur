<?php
/**
 * Debug script to test WHO Growth Standards integration with screening.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/who_growth_standards.php';
require_once __DIR__ . '/public/api/DatabaseAPI.php';

echo "<h2>Debug WHO Growth Standards Integration</h2>";

try {
    $db = DatabaseAPI::getInstance();
    
    // Get a sample user from the database
    $result = $db->select('community_users', [], [], 1);
    
    if ($result['success'] && !empty($result['data'])) {
        $user = $result['data'][0];
        
        echo "<h3>Sample User Data:</h3>";
        echo "<pre>" . print_r($user, true) . "</pre>";
        
        // Test WHO Growth Standards calculation
        $who = new WHOGrowthStandards();
        
        echo "<h3>Testing WHO Growth Standards Calculation:</h3>";
        echo "<p>Weight: {$user['weight']} kg</p>";
        echo "<p>Height: {$user['height']} cm</p>";
        echo "<p>Birthday: {$user['birthday']}</p>";
        echo "<p>Sex: {$user['sex']}</p>";
        
        $assessment = $who->getComprehensiveAssessment(
            floatval($user['weight']),
            floatval($user['height']),
            $user['birthday'],
            $user['sex']
        );
        
        echo "<h3>WHO Growth Standards Assessment Result:</h3>";
        echo "<pre>" . print_r($assessment, true) . "</pre>";
        
        if ($assessment['success']) {
            $results = $assessment['results'];
            echo "<h3>Individual Z-Scores:</h3>";
            echo "<p>Weight-for-Age: " . ($results['weight_for_age']['z_score'] ?? 'N/A') . " (" . ($results['weight_for_age']['classification'] ?? 'N/A') . ")</p>";
            echo "<p>Height-for-Age: " . ($results['height_for_age']['z_score'] ?? 'N/A') . " (" . ($results['height_for_age']['classification'] ?? 'N/A') . ")</p>";
            echo "<p>Weight-for-Height: " . ($results['weight_for_height']['z_score'] ?? 'N/A') . " (" . ($results['weight_for_height']['classification'] ?? 'N/A') . ")</p>";
            echo "<p>Weight-for-Length: " . ($results['weight_for_length']['z_score'] ?? 'N/A') . " (" . ($results['weight_for_length']['classification'] ?? 'N/A') . ")</p>";
            echo "<p>BMI-for-Age: " . ($results['bmi_for_age']['z_score'] ?? 'N/A') . " (" . ($results['bmi_for_age']['classification'] ?? 'N/A') . ")</p>";
        } else {
            echo "<h3>Error in WHO Growth Standards Calculation:</h3>";
            echo "<pre>" . print_r($assessment, true) . "</pre>";
        }
        
    } else {
        echo "<p>No users found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
