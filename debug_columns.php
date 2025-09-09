<?php
// Debug script to check actual column names in community_users table
require_once __DIR__ . '/public/api/DatabaseHelper.php';

try {
    $db = new DatabaseHelper();
    
    if (!$db->isAvailable()) {
        die("Database connection failed!\n");
    }
    
    echo "🔍 Checking community_users table structure...\n\n";
    
    // Get one user to see the actual column names
    $result = $db->select(
        'community_users',
        '*',
        '',
        [],
        'screening_date DESC',
        1
    );
    
    if ($result['success'] && !empty($result['data'])) {
        $user = $result['data'][0];
        
        echo "📊 Sample user data:\n";
        echo "Name: " . ($user['name'] ?? 'N/A') . "\n";
        echo "Email: " . ($user['email'] ?? 'N/A') . "\n";
        echo "Weight: " . ($user['weight'] ?? 'N/A') . "\n";
        echo "Height: " . ($user['height'] ?? 'N/A') . "\n";
        echo "Birthday: " . ($user['birthday'] ?? 'N/A') . "\n";
        echo "Sex: " . ($user['sex'] ?? 'N/A') . "\n\n";
        
        echo "🔍 All available columns:\n";
        foreach ($user as $key => $value) {
            echo "- '{$key}' => " . (is_null($value) ? 'NULL' : "'{$value}'") . "\n";
        }
        
        echo "\n🔍 WHO Growth Standards columns specifically:\n";
        $whoColumns = [
            'weight-for-age',
            'height-for-age', 
            'weight-for-height',
            'weight-for-length',
            'bmi-for-age',
            'bmi_category',
            'nutritional_risk',
            'follow_up_required',
            'notes'
        ];
        
        foreach ($whoColumns as $col) {
            $value = $user[$col] ?? 'NOT_FOUND';
            echo "- '{$col}' => " . (is_null($value) ? 'NULL' : "'{$value}'") . "\n";
        }
        
    } else {
        echo "❌ No users found in database or error occurred\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
