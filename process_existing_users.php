<?php
// Script to process existing users in community_users table with WHO Growth Standards
require_once __DIR__ . '/public/api/DatabaseHelper.php';
require_once __DIR__ . '/who_growth_standards.php';

try {
    $db = new DatabaseHelper();
    
    if (!$db->isAvailable()) {
        die("Database connection failed!\n");
    }
    
    echo "🔍 Fetching existing users from community_users table...\n";
    
    // Get all users from community_users table
    $result = $db->select(
        'community_users',
        '*',
        '',
        [],
        'screening_date DESC'
    );
    
    if (!$result['success']) {
        die("Error fetching users: " . $result['error'] . "\n");
    }
    
    $users = $result['data'];
    $totalUsers = count($users);
    
    echo "📊 Found {$totalUsers} users to process\n\n";
    
    if ($totalUsers === 0) {
        echo "No users found in the database.\n";
        exit;
    }
    
    $who = new WHOGrowthStandards();
    $processedCount = 0;
    $errorCount = 0;
    
    foreach ($users as $user) {
        echo "Processing: " . ($user['name'] ?? 'Unknown') . " (" . ($user['email'] ?? 'No email') . ")\n";
        
        try {
            // Validate required fields
            if (empty($user['weight']) || empty($user['height']) || empty($user['birthday']) || empty($user['sex'])) {
                echo "  ⚠️  Skipping - Missing required data (weight, height, birthday, or sex)\n";
                continue;
            }
            
            // Get comprehensive WHO Growth Standards assessment
            $assessment = $who->getComprehensiveAssessment(
                floatval($user['weight']),
                floatval($user['height']),
                $user['birthday'],
                $user['sex']
            );
            
            if ($assessment['success']) {
                // Update user with WHO Growth Standards data
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
                    'notes' => 'Processed by WHO Growth Standards - ' . implode(', ', $assessment['recommendations'] ?? [])
                ];
                
                $updateResult = $db->update('community_users', $updateData, ['email' => $user['email']]);
                
                if ($updateResult['success']) {
                    echo "  ✅ Successfully processed WHO Growth Standards\n";
                    echo "    - Weight-for-Age: " . ($assessment['weight_for_age']['z_score'] ?? 'N/A') . " (" . ($assessment['weight_for_age']['classification'] ?? 'N/A') . ")\n";
                    echo "    - Height-for-Age: " . ($assessment['height_for_age']['z_score'] ?? 'N/A') . " (" . ($assessment['height_for_age']['classification'] ?? 'N/A') . ")\n";
                    echo "    - Weight-for-Height: " . ($assessment['weight_for_height']['z_score'] ?? 'N/A') . " (" . ($assessment['weight_for_height']['classification'] ?? 'N/A') . ")\n";
                    echo "    - BMI-for-Age: " . ($assessment['bmi_for_age']['z_score'] ?? 'N/A') . " (" . ($assessment['bmi_for_age']['classification'] ?? 'N/A') . ")\n";
                    echo "    - Nutritional Risk: " . ($assessment['nutritional_risk'] ?? 'N/A') . "\n";
                    $processedCount++;
                } else {
                    echo "  ❌ Failed to update database: " . $updateResult['error'] . "\n";
                    $errorCount++;
                }
            } else {
                echo "  ❌ WHO Growth Standards assessment failed: " . ($assessment['error'] ?? 'Unknown error') . "\n";
                $errorCount++;
            }
            
        } catch (Exception $e) {
            echo "  ❌ Error processing user: " . $e->getMessage() . "\n";
            $errorCount++;
        }
        
        echo "\n";
    }
    
    echo "🎉 Processing complete!\n";
    echo "✅ Successfully processed: {$processedCount} users\n";
    echo "❌ Errors: {$errorCount} users\n";
    echo "📊 Total users: {$totalUsers}\n";
    
    if ($processedCount > 0) {
        echo "\n🔄 The screening.php table should now show WHO Growth Standards data instead of N/A!\n";
    }
    
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
?>
