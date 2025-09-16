<?php
// Direct test of the API endpoint
require_once 'public/api/DatabaseAPI.php';

echo "=== TESTING API DIRECTLY ===\n";

$api = new DatabaseAPI();

// Test the getWHOClassifications method directly
$result = $api->getWHOClassifications('weight-for-age', '1d', '');

echo "API Result:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

// If debug_info exists, show it
if (isset($result['data']['debug_info'])) {
    echo "\n=== DEBUG INFO ===\n";
    foreach ($result['data']['debug_info'] as $i => $debug) {
        echo "User $i: " . json_encode($debug, JSON_PRETTY_PRINT) . "\n";
    }
}
?>
