<?php
/**
 * Test API Endpoint Access
 * This script tests if the unified API endpoint is accessible
 */

echo "🧪 Testing API Endpoint Access\n";
echo "==============================\n\n";

// Test 1: Check if the file exists
echo "1. Checking if unified_api.php exists:\n";
if (file_exists('api/unified_api.php')) {
    echo "   ✅ api/unified_api.php exists\n";
} else {
    echo "   ❌ api/unified_api.php not found\n";
}

// Test 2: Check if we can include it
echo "\n2. Testing if we can include unified_api.php:\n";
try {
    ob_start();
    include 'api/unified_api.php';
    $output = ob_get_clean();
    echo "   ✅ unified_api.php included successfully\n";
    echo "   📄 Output length: " . strlen($output) . " characters\n";
} catch (Exception $e) {
    echo "   ❌ Error including unified_api.php: " . $e->getMessage() . "\n";
}

// Test 3: Check current working directory
echo "\n3. Current working directory:\n";
echo "   📁 " . getcwd() . "\n";

// Test 4: Check if api directory exists
echo "\n4. Checking api directory:\n";
if (is_dir('api')) {
    echo "   ✅ api directory exists\n";
    echo "   📁 Contents of api directory:\n";
    $files = scandir('api');
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "      - $file\n";
        }
    }
} else {
    echo "   ❌ api directory not found\n";
}

// Test 5: Check if we can access the endpoint via GET
echo "\n5. Testing endpoint access:\n";
if (isset($_GET['endpoint'])) {
    echo "   📝 Endpoint parameter: " . $_GET['endpoint'] . "\n";
} else {
    echo "   📝 No endpoint parameter provided\n";
}

echo "\n✅ Test complete!\n";
?>
