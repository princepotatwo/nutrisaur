<?php
// Simple test to verify WHO Growth Standards integration
require_once __DIR__ . '/public/api/DatabaseHelper.php';
require_once __DIR__ . '/who_growth_standards.php';

try {
    $db = new DatabaseHelper();
    
    if (!$db->isAvailable()) {
        die("Database connection failed!\n");
    }
    
    echo "🧪 Testing WHO Growth Standards Integration\n";
    echo "==========================================\n\n";
    
    // Test data
    $testUser = [
        'name' => 'Test Child WHO',
        'email' => 'test.who@example.com',
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
    ];
    
    echo "1. Adding test user...\n";
    $result = $db->insert('community_users', $testUser);
    
    if ($result['success']) {
        echo "   ✅ Test user added successfully\n";
        
        echo "\n2. Testing WHO Growth Standards calculation...\n";
        $who = new WHOGrowthStandards();
        
        $assessment = $who->getComprehensiveAssessment(
            floatval($testUser['weight']),
            floatval($testUser['height']),
            $testUser['birthday'],
            $testUser['sex']
        );
        
        if ($assessment['success']) {
            echo "   ✅ WHO Growth Standards calculation successful\n";
            echo "   - Weight-for-Age Z-Score: " . ($assessment['weight_for_age']['z_score'] ?? 'N/A') . "\n";
            echo "   - Height-for-Age Z-Score: " . ($assessment['height_for_age']['z_score'] ?? 'N/A') . "\n";
            echo "   - Weight-for-Height Z-Score: " . ($assessment['weight_for_height']['z_score'] ?? 'N/A') . "\n";
            echo "   - BMI-for-Age Z-Score: " . ($assessment['bmi_for_age']['z_score'] ?? 'N/A') . "\n";
            echo "   - Nutritional Risk: " . ($assessment['nutritional_risk'] ?? 'N/A') . "\n";
            
            echo "\n3. Updating database with WHO Growth Standards data...\n";
            $updateData = [
                'bmi-for-age' => $assessment['bmi_for_age']['z_score'] ?? null,
                'weight-for-height' => $assessment['weight_for_height']['z_score'] ?? null,
                'weight-for-age' => $assessment['weight_for_age']['z_score'] ?? null,
                'weight-for-length' => $assessment['weight_for_length']['z_score'] ?? null,
                'height-for-age' => $assessment['height_for_age']['z_score'] ?? null,
                'bmi' => $assessment['bmi'] ?? null,
                'bmi_category' => $assessment['bmi_for_age']['classification'] ?? null,
                'nutritional_risk' => $assessment['nutritional_risk'] ?? 'Low',
                'follow_up_required' => ($assessment['nutritional_risk'] !== 'Low') ? 1 : 0,
                'notes' => 'Test user processed by WHO Growth Standards'
            ];
            
            $updateResult = $db->update('community_users', $updateData, ['email' => $testUser['email']]);
            
            if ($updateResult['success']) {
                echo "   ✅ Database updated successfully\n";
                
                echo "\n4. Verifying data in database...\n";
                $verifyResult = $db->select('community_users', '*', 'email = ?', [$testUser['email']]);
                
                if ($verifyResult['success'] && !empty($verifyResult['data'])) {
                    $user = $verifyResult['data'][0];
                    echo "   ✅ User data retrieved from database\n";
                    echo "   - Weight-for-Age: " . ($user['weight-for-age'] ?? 'NULL') . "\n";
                    echo "   - Height-for-Age: " . ($user['height-for-age'] ?? 'NULL') . "\n";
                    echo "   - Weight-for-Height: " . ($user['weight-for-height'] ?? 'NULL') . "\n";
                    echo "   - Weight-for-Length: " . ($user['weight-for-length'] ?? 'NULL') . "\n";
                    echo "   - BMI-for-Age: " . ($user['bmi-for-age'] ?? 'NULL') . "\n";
                    echo "   - BMI Category: " . ($user['bmi_category'] ?? 'NULL') . "\n";
                    echo "   - Nutritional Risk: " . ($user['nutritional_risk'] ?? 'NULL') . "\n";
                    
                    echo "\n🎉 WHO Growth Standards integration test completed successfully!\n";
                    echo "The screening.php table should now show real data instead of N/A.\n";
                } else {
                    echo "   ❌ Failed to verify data in database\n";
                }
            } else {
                echo "   ❌ Failed to update database: " . $updateResult['error'] . "\n";
            }
        } else {
            echo "   ❌ WHO Growth Standards calculation failed: " . ($assessment['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "   ❌ Failed to add test user: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
?>
