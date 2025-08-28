<?php
/**
 * Debug file to check file structure and paths
 */

header('Content-Type: application/json');

$debug_info = [
    'current_dir' => __DIR__,
    'sss_path' => __DIR__ . '/../sss/',
    'sss_exists' => is_dir(__DIR__ . '/../sss/'),
    'sss_contents' => [],
    'api_path' => __DIR__ . '/../sss/api/',
    'api_exists' => is_dir(__DIR__ . '/../sss/api/'),
    'api_contents' => [],
    'config_exists' => file_exists(__DIR__ . '/../config.php'),
    'public_contents' => scandir(__DIR__),
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'N/A'
];

// Check sss directory contents
if ($debug_info['sss_exists']) {
    $debug_info['sss_contents'] = scandir(__DIR__ . '/../sss/');
}

// Check api directory contents
if ($debug_info['api_exists']) {
    $debug_info['api_contents'] = scandir(__DIR__ . '/../sss/api/');
}

echo json_encode($debug_info, JSON_PRETTY_PRINT);
?>
