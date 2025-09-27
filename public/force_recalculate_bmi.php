<?php
/**
 * Force recalculation of BMI-for-age classifications using new percentile logic
 * This script will recalculate all BMI-for-age classifications for existing users
 */

require_once __DIR__ . '/api/DatabaseAPI.php';
require_once __DIR__ . '/../who_growth_standards.php';

try {
    $db = new DatabaseAPI();
    
    if (!$db->isDatabaseAvailable()) {
        echo "❌ Database not available\n";
        exit;
    }
    
    echo "🔄 Starting BMI-for-age classification recalculation...\n";
    
    // Get all users from community_users table
    $result = $db->select('community_users', '*', '', [], 'screening_date DESC');
    
    if (!$result['success']) {
        echo "❌ Error getting users from database\n";
        exit;
    }
    
    $users = $result['data'];
    $totalUsers = count($users);
    $processedCount = 0;
    $updatedCount = 0;
    
    echo "📊 Found $totalUsers users to process\n";
    
    // Initialize WHO Growth Standards
    $who = new WHOGrowthStandards();
    
    foreach ($users as $user) {
        $processedCount++;
        
        // Skip users without required data
        if (empty($user['birthday']) || empty($user['weight']) || empty($user['height']) || empty($user['sex'])) {
            echo "⏭️  User $processedCount: Skipping - missing required data\n";
            continue;
        }
        
        try {
            // Calculate age in months
            $ageInMonths = $who->calculateAgeInMonths($user['birthday'], $user['screening_date'] ?? null);
            
            // Only process users eligible for BMI-for-age (5-19 years, 60-228 months)
            if ($ageInMonths < 60 || $ageInMonths >= 228) {
                echo "⏭️  User $processedCount: Skipping - not eligible for BMI-for-age (age: {$ageInMonths} months)\n";
                continue;
            }
            
            // Get comprehensive assessment with new logic
            $assessment = $who->getComprehensiveAssessment(
                floatval($user['weight']),
                floatval($user['height']),
                $user['birthday'],
                $user['sex'],
                $user['screening_date']
            );
            
            if ($assessment['success'] && isset($assessment['results']['bmi_for_age'])) {
                $bmiResult = $assessment['results']['bmi_for_age'];
                $newClassification = $bmiResult['classification'];
                $newZScore = $bmiResult['z_score'];
                
                // Update the user's BMI-for-age classification in database
                $updateData = [
                    'bmi_for_age_classification' => $newClassification,
                    'bmi_for_age_z_score' => $newZScore
                ];
                
                $updateResult = $db->update('community_users', $updateData, "email = ?", [$user['email']]);
                
                if ($updateResult['success']) {
                    $updatedCount++;
                    echo "✅ User $processedCount: Updated BMI classification to '$newClassification' (z-score: $newZScore)\n";
                } else {
                    echo "❌ User $processedCount: Failed to update database\n";
                }
            } else {
                echo "⚠️  User $processedCount: Assessment failed\n";
            }
            
        } catch (Exception $e) {
            echo "❌ User $processedCount: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Recalculation complete!\n";
    echo "📊 Processed: $processedCount users\n";
    echo "✅ Updated: $updatedCount users\n";
    echo "🔄 BMI-for-age classifications now use the new percentile-based logic\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
