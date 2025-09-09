<?php
// Test script to verify WHO Growth Standards integration
require_once __DIR__ . '/public/api/DatabaseHelper.php';
require_once __DIR__ . '/who_growth_standards.php';

try {
    $db = new DatabaseHelper();
    
    // Test data - a few diverse users
    $testUsers = [
        [
            'name' => 'Test Child 1',
            'email' => 'test1@example.com',
            'password' => 'password123',
            'municipality' => 'CITY OF BALANGA',
            'barangay' => 'Central',
            'sex' => 'Male',
            'birthday' => '2020-01-15', // 4 years old
            'is_pregnant' => null,
            'weight' => 15.5,
            'height' => 102.0,
            'screening_date' => date('Y-m-d H:i:s'),
            'fcm_token' => null
        ],
        [
            'name' => 'Test Child 2',
            'email' => 'test2@example.com',
            'password' => 'password123',
            'municipality' => 'CITY OF BALANGA',
            'barangay' => 'Central',
            'sex' => 'Female',
            'birthday' => '2019-06-20', // 5 years old
            'is_pregnant' => null,
            'weight' => 18.2,
            'height' => 110.5,
            'screening_date' => date('Y-m-d H:i:s'),
            'fcm_token' => null
        ],
        [
            'name' => 'Test Child 3',
            'email' => 'test3@example.com',
            'password' => 'password123',
            'municipality' => 'CITY OF BALANGA',
            'barangay' => 'Central',
            'sex' => 'Male',
            'birthday' => '2021-03-10', // 3 years old
            'is_pregnant' => null,
            'weight' => 12.8,
            'height' => 95.0,
            'screening_date' => date('Y-m-d H:i:s'),
            'fcm_token' => null
        ]
    ];
    
    echo "Adding test users...\n";
    
    foreach ($testUsers as $user) {
        // Insert user
        $result = $db->insert('community_users', $user);
        
        if ($result['success']) {
            echo "✓ Added user: " . $user['name'] . "\n";
            
            // Process with WHO Growth Standards
            $who = new WHOGrowthStandards();
            
            // Calculate age in months
            $birthDate = new DateTime($user['birthday']);
            $today = new DateTime();
            $age = $today->diff($birthDate);
            $ageInMonths = ($age->y * 12) + $age->m;
            
            // Get comprehensive assessment
            $assessment = $who->getComprehensiveAssessment(
                floatval($user['weight']),
                floatval($user['height']),
                $user['birthday'],
                $user['sex']
            );
            
            if ($assessment['success']) {
                // Update user with WHO Growth Standards data
                $updateData = [
                    'bmi-for-age' => $assessment['bmi_for_age']['z_score'],
                    'weight-for-height' => $assessment['weight_for_height']['z_score'],
                    'weight-for-age' => $assessment['weight_for_age']['z_score'],
                    'weight-for-length' => $assessment['weight_for_length']['z_score'],
                    'height-for-age' => $assessment['height_for_age']['z_score'],
                    'bmi' => $assessment['bmi'],
                    'bmi_category' => $assessment['bmi_for_age']['classification'],
                    'nutritional_risk' => $assessment['nutritional_risk'],
                    'follow_up_required' => ($assessment['nutritional_risk'] !== 'Low') ? 1 : 0,
                    'notes' => 'Processed by WHO Growth Standards - ' . implode(', ', $assessment['recommendations'])
                ];
                
                $updateResult = $db->update('community_users', $updateData, ['email' => $user['email']]);
                
                if ($updateResult['success']) {
                    echo "✓ Processed WHO Growth Standards for: " . $user['name'] . "\n";
                    echo "  - Weight-for-Age Z-Score: " . $assessment['weight_for_age']['z_score'] . " (" . $assessment['weight_for_age']['classification'] . ")\n";
                    echo "  - Height-for-Age Z-Score: " . $assessment['height_for_age']['z_score'] . " (" . $assessment['height_for_age']['classification'] . ")\n";
                    echo "  - Weight-for-Height Z-Score: " . $assessment['weight_for_height']['z_score'] . " (" . $assessment['weight_for_height']['classification'] . ")\n";
                    echo "  - BMI-for-Age Z-Score: " . $assessment['bmi_for_age']['z_score'] . " (" . $assessment['bmi_for_age']['classification'] . ")\n";
                    echo "  - Nutritional Risk: " . $assessment['nutritional_risk'] . "\n";
                } else {
                    echo "✗ Failed to update WHO Growth Standards for: " . $user['name'] . "\n";
                    echo "  Error: " . $updateResult['error'] . "\n";
                }
            } else {
                echo "✗ WHO Growth Standards assessment failed for: " . $user['name'] . "\n";
                echo "  Error: " . ($assessment['error'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "✗ Failed to add user: " . $user['name'] . "\n";
            echo "  Error: " . $result['error'] . "\n";
        }
        
        echo "\n";
    }
    
    echo "Test completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
