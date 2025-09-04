<?php
// Very simple test to identify the issue
error_log("=== SIMPLE TEST START ===");

// Test 1: Basic PHP functionality
echo json_encode([
    'success' => true,
    'message' => 'Basic PHP is working',
    'timestamp' => date('Y-m-d H:i:s')
]);

error_log("=== SIMPLE TEST END ===");
?>
