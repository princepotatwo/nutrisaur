<?php
/**
 * Debug Routing - Comprehensive routing analysis
 * This script shows exactly what's happening with path parsing
 */

echo "🔍 COMPREHENSIVE ROUTING DEBUG\n";
echo "==============================\n\n";

// Show all server variables related to routing
echo "📋 SERVER VARIABLES:\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'NOT SET') . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'NOT SET') . "\n";
echo "ORIG_PATH_INFO: " . ($_SERVER['ORIG_PATH_INFO'] ?? 'NOT SET') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n\n";

// Test path parsing
echo "🎯 PATH PARSING TEST:\n";
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
echo "Raw REQUEST_URI: '$request_uri'\n";

$path = parse_url($request_uri, PHP_URL_PATH);
echo "parse_url(PHP_URL_PATH): '$path'\n";

$path = trim($path, '/');
echo "After trim(): '$path'\n";

$path_length = strlen($path);
echo "Path length: $path_length\n";

// Check if it's an API route
$is_api_route = strpos($path, 'api/') === 0;
echo "Is API route: " . ($is_api_route ? 'YES' : 'NO') . "\n";

// Check if it matches our switch cases
echo "\n🔍 SWITCH CASE MATCHING:\n";
echo "Looking for case: 'api/unified_api'\n";
echo "Current path: '$path'\n";
echo "Exact match: " . ($path === 'api/unified_api' ? 'YES' : 'NO') . "\n";

// Test the switch logic
echo "\n🧪 SWITCH LOGIC TEST:\n";
switch ($path) {
    case 'api/unified_api':
        echo "✅ MATCHED: api/unified_api\n";
        break;
    case 'api/login':
        echo "✅ MATCHED: api/login\n";
        break;
    case 'api/check_session':
        echo "✅ MATCHED: api/check_session\n";
        break;
    default:
        echo "❌ NO MATCH - falling through to default\n";
        break;
}

// Check if files exist
echo "\n📁 FILE EXISTENCE TEST:\n";
$api_file = 'api/unified_api.php';
echo "Checking: $api_file\n";
if (file_exists($api_file)) {
    echo "✅ File exists\n";
    echo "File size: " . filesize($api_file) . " bytes\n";
    echo "File permissions: " . substr(sprintf('%o', fileperms($api_file)), -4) . "\n";
} else {
    echo "❌ File does not exist\n";
}

// Check current working directory
echo "\n📂 DIRECTORY INFO:\n";
echo "Current working directory: " . getcwd() . "\n";
echo "Script directory: " . __DIR__ . "\n";

// List contents of current directory
echo "\n📋 CURRENT DIRECTORY CONTENTS:\n";
$files = scandir('.');
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $type = is_dir($file) ? 'DIR' : 'FILE';
        echo "  [$type] $file\n";
    }
}

// List contents of api directory if it exists
if (is_dir('api')) {
    echo "\n📋 API DIRECTORY CONTENTS:\n";
    $api_files = scandir('api');
    foreach ($api_files as $file) {
        if ($file !== '.' && $file !== '..') {
            $type = is_dir($file) ? 'DIR' : 'FILE';
            echo "  [$type] $file\n";
        }
    }
}

echo "\n✅ Routing debug complete!\n";
?>
