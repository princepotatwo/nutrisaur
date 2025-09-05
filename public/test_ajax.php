<?php
session_start();

// Test AJAX response
header('Content-Type: application/json');

$response = [
    'success' => true,
    'message' => 'AJAX test working',
    'session_user_id' => $_SESSION['user_id'] ?? 'not set',
    'session_username' => $_SESSION['username'] ?? 'not set',
    'is_ajax' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest',
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'post_action' => $_POST['action'] ?? 'not set',
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response);
?>
