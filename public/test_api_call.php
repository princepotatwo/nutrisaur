<?php
/**
 * Test API Call - See exactly what path is received
 */

echo "🧪 Testing API Call Path\n";
echo "========================\n\n";

// Show all server variables related to the request
echo "📋 REQUEST DETAILS:\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'NOT SET') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n\n";

// Test path parsing exactly like index.php does
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

echo "🎯 PATH PARSING (like index.php):\n";
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

// Test if we can access the API file
echo "🔍 API FILE ACCESS:\n";
if (file_exists('api/unified_api.php')) {
    echo "✅ api/unified_api.php exists\n";
    echo "📄 File size: " . filesize('api/unified_api.php') . " bytes\n";
} else {
    echo "❌ api/unified_api.php not found\n";
}

echo "\n✅ Test complete!\n";
?>
