<?php
/**
 * Test Railway Routing
 * Simple test to see how Railway handles different paths
 */

echo "🧪 Railway Routing Test\n";
echo "======================\n\n";

// Show the current request details
echo "📋 REQUEST DETAILS:\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'NOT SET') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n\n";

// Test path parsing
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

echo "🎯 PATH PARSING:\n";
echo "Raw REQUEST_URI: '$request_uri'\n";
echo "parse_url(PHP_URL_PATH): '$path'\n";
echo "After trim(): '$path'\n";
echo "Path length: " . strlen($path) . "\n\n";

// Test if this looks like an API route
$is_api_route = strpos($path, 'api/') === 0;
echo "🔍 API ROUTE DETECTION:\n";
echo "Is API route: " . ($is_api_route ? 'YES' : 'NO') . "\n";
echo "Path starts with 'api/': " . (strpos($path, 'api/') === 0 ? 'YES' : 'NO') . "\n\n";

// Test the exact match
echo "🧪 EXACT MATCH TEST:\n";
echo "Looking for: 'api/unified_api'\n";
echo "Current path: '$path'\n";
echo "Exact match: " . ($path === 'api/unified_api' ? 'YES' : 'NO') . "\n";
echo "String comparison: " . (strcmp($path, 'api/unified_api') === 0 ? 'EQUAL' : 'NOT EQUAL') . "\n\n";

// Show current working directory
echo "📂 DIRECTORY INFO:\n";
echo "Current working directory: " . getcwd() . "\n";
echo "Script directory: " . __DIR__ . "\n\n";

echo "✅ Test complete!\n";
?>
