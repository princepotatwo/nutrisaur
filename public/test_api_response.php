<?php
// Test API Response Format
// Access this at: /test_api_response

header('Content-Type: application/json');

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/api/DatabaseAPI.php";

$db = DatabaseAPI::getInstance();

// Test the exact API response format
$risk_data = $db->getRiskDistribution();

echo json_encode([
    'success' => true,
    'data' => $risk_data
], JSON_PRETTY_PRINT);
?>
