<?php
/**
 * Check user data structure and field names
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/public/api/DatabaseAPI.php';

echo "<h2>Check User Data Structure</h2>";

try {
    $db = DatabaseAPI::getInstance();
    
    // Get a sample user from the database
    $result = $db->select('community_users', [], [], 1);
    
    if ($result['success'] && !empty($result['data'])) {
        $user = $result['data'][0];
        
        echo "<h3>Available Fields in User Data:</h3>";
        echo "<ul>";
        foreach ($user as $key => $value) {
            echo "<li><strong>$key:</strong> " . (is_null($value) ? 'NULL' : $value) . "</li>";
        }
        echo "</ul>";
        
        // Check if required fields exist
        $required_fields = ['weight', 'height', 'birthday', 'sex'];
        echo "<h3>Required Fields Check:</h3>";
        foreach ($required_fields as $field) {
            $exists = array_key_exists($field, $user);
            $value = $exists ? $user[$field] : 'NOT FOUND';
            echo "<p><strong>$field:</strong> " . ($exists ? 'EXISTS' : 'MISSING') . " - Value: " . (is_null($value) ? 'NULL' : $value) . "</p>";
        }
        
    } else {
        echo "<p>No users found in database</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
