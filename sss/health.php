<?php
/**
 * Enhanced Health Check for Railway
 * Keeps container alive and provides performance metrics
 */

// Set content type
header('Content-Type: application/json');

// Get performance metrics
$startTime = microtime(true);
$memoryUsage = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);

// Database connection test (optional)
$dbStatus = 'unknown';
$dbResponseTime = 0;

try {
    if (function_exists('getDatabaseConnection')) {
        $dbStart = microtime(true);
        $conn = getDatabaseConnection();
        if ($conn) {
            $stmt = $conn->query("SELECT 1 as test");
            $result = $stmt->fetch();
            $dbStatus = 'connected';
        } else {
            $dbStatus = 'failed';
        }
        $dbResponseTime = (microtime(true) - $dbStart) * 1000; // in milliseconds
    }
} catch (Exception $e) {
    $dbStatus = 'error: ' . $e->getMessage();
}

// Calculate total response time
$totalTime = (microtime(true) - $startTime) * 1000;

// Performance data
$performance = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'response_time_ms' => round($totalTime, 2),
    'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
    'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
    'database' => [
        'status' => $dbStatus,
        'response_time_ms' => round($dbResponseTime, 2)
    ],
    'environment' => [
        'php_version' => PHP_VERSION,
        'server_time' => date('Y-m-d H:i:s'),
        'timezone' => date_default_timezone_get()
    ]
];

// Return performance data
echo json_encode($performance, JSON_PRETTY_PRINT);
?>
