<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo json_encode([
    'debug' => 'Notification API Debug',
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
]);

// Show all received data
echo "\n" . json_encode([
    'post_data' => $_POST,
    'raw_input' => file_get_contents('php://input'),
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'NOT_SET',
    'http_method' => $_SERVER['REQUEST_METHOD']
]);

// Test JSON parsing
if (isset($_POST['notification_data'])) {
    $parsed = json_decode($_POST['notification_data'], true);
    echo "\n" . json_encode([
        'json_parse_result' => $parsed,
        'json_last_error' => json_last_error(),
        'json_last_error_msg' => json_last_error_msg()
    ]);
    
    if ($parsed) {
        echo "\n" . json_encode([
            'parsed_data' => [
                'target_user' => $parsed['target_user'] ?? 'MISSING',
                'title' => $parsed['title'] ?? 'MISSING',
                'body' => $parsed['body'] ?? 'MISSING',
                'user_name' => $parsed['user_name'] ?? 'MISSING'
            ]
        ]);
    }
} else {
    echo "\n" . json_encode([
        'error' => 'notification_data not found in POST data'
    ]);
}
?>
