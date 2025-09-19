<?php
// Debug script to test Age Classification Chart data
require_once 'public/api/DatabaseAPI.php';

echo "🔍 Debugging Age Classification Chart Data\n";
echo "==========================================\n\n";

try {
    $db = DatabaseAPI::getInstance();
    
    // Get users data
    $users = $db->getDetailedScreeningResponses('1d', '');
    echo "📊 Total users found: " . count($users) . "\n\n";
    
    // Show sample user data
    echo "📋 Sample user data (first 3 users):\n";
    for ($i = 0; $i < min(3, count($users)); $i++) {
        $user = $users[$i];
        echo "User " . ($i + 1) . ":\n";
        echo "  - Birthday: " . ($user['birthday'] ?? 'none') . "\n";
        echo "  - Age: " . ($user['age'] ?? 'none') . "\n";
        echo "  - Weight: " . ($user['weight_kg'] ?? 'none') . " kg\n";
        echo "  - Height: " . ($user['height_cm'] ?? 'none') . " cm\n";
        echo "  - BMI Category: " . ($user['bmi_category'] ?? 'none') . "\n";
        echo "  - Screening Date: " . ($user['screening_date'] ?? 'none') . "\n";
        
        // Calculate age in months
        $ageInMonths = 0;
        if (isset($user['birthday']) && $user['birthday'] !== null && $user['birthday'] !== '') {
            try {
                $birthDate = new DateTime($user['birthday']);
                $screeningDate = new DateTime($user['screening_date'] ?? date('Y-m-d H:i:s'));
                $age = $birthDate->diff($screeningDate);
                $ageInMonths = ($age->y * 12) + $age->m;
                
                if ($age->d >= 15) {
                    $ageInMonths += 1;
                }
            } catch (Exception $e) {
                $ageInMonths = intval($user['age'] ?? 0);
            }
        } else {
            $ageInMonths = intval($user['age'] ?? 0);
        }
        
        echo "  - Calculated age in months: " . $ageInMonths . "\n";
        
        // Determine age group
        $ageGroups = [
            '0-6m' => [0, 6],
            '6-12m' => [6, 12],
            '1-2y' => [12, 24],
            '2-3y' => [24, 36],
            '3-4y' => [36, 48],
            '4-5y' => [48, 60],
            '5-6y' => [60, 72]
        ];
        
        $userAgeGroup = null;
        foreach ($ageGroups as $ageGroup => $range) {
            if ($ageInMonths >= $range[0] && $ageInMonths < $range[1]) {
                $userAgeGroup = $ageGroup;
                break;
            }
        }
        
        echo "  - Assigned age group: " . ($userAgeGroup ?? 'none') . "\n";
        echo "  - BMI Category: " . ($user['bmi_category'] ?? 'none') . "\n\n";
    }
    
    // Test the API endpoint
    echo "🌐 Testing API endpoint...\n";
    $_GET['action'] = 'get_age_classification_chart';
    $_GET['barangay'] = '';
    $_GET['time_frame'] = '1d';
    
    ob_start();
    include 'public/api/DatabaseAPI.php';
    $apiResponse = ob_get_clean();
    
    echo "API Response:\n";
    echo $apiResponse . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
