<?php
/**
 * Keep-Alive Script for Railway
 * Call this to prevent container from sleeping
 */

// Set content type
header('Content-Type: application/json');

// Get current time
$currentTime = date('Y-m-d H:i:s');

// Log the keep-alive request
error_log("Keep-alive request received at: $currentTime");

// Return success response
echo json_encode([
    'status' => 'awake',
    'message' => 'Container kept alive',
    'timestamp' => $currentTime,
    'next_ping' => 'Call this every 10-15 minutes to prevent sleep'
]);
?>
