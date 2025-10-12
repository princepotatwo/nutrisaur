<?php
/**
 * Test adding emoji column using DatabaseAPI.php
 */

$url = 'https://nutrisaur-production.up.railway.app/api/DatabaseAPI.php?action=query';

$data = [
    'sql' => 'ALTER TABLE user_food_history ADD COLUMN emoji VARCHAR(10) NULL DEFAULT NULL AFTER is_mho_recommended',
    'params' => []
];

$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "ERROR: Failed to execute SQL query\n";
} else {
    echo "Response: " . $result . "\n";
}
?>
