<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'Path test',
    'current_dir' => __DIR__,
    'database_api_exists' => file_exists(__DIR__ . "/DatabaseAPI.php"),
    'config_exists' => file_exists(__DIR__ . "/../../config.php"),
    'files_in_dir' => scandir(__DIR__)
]);
?>
