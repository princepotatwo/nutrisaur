<?php
/**
 * Test Dash - Check if sss/dash.php can be found and included
 */

echo "🧪 Test Dash - Can we find and include sss/dash.php?\n";
echo "==================================================\n\n";

// Step 1: Check current directory
echo "1️⃣ Current directory: " . getcwd() . "\n";
echo "📍 __DIR__: " . __DIR__ . "\n\n";

// Step 2: Check if sss/dash.php exists
echo "2️⃣ Checking if sss/dash.php exists...\n";
if (file_exists('sss/dash.php')) {
    echo "✅ sss/dash.php exists in current directory\n";
} else {
    echo "❌ sss/dash.php NOT found in current directory\n";
}

if (file_exists(__DIR__ . '/sss/dash.php')) {
    echo "✅ sss/dash.php exists at " . __DIR__ . '/sss/dash.php' . "\n";
} else {
    echo "❌ sss/dash.php NOT found at " . __DIR__ . '/sss/dash.php' . "\n";
}

if (file_exists('../sss/dash.php')) {
    echo "✅ ../sss/dash.php exists (relative to current)\n";
} else {
    echo "❌ ../sss/dash.php NOT found (relative to current)\n";
}

echo "\n";

// Step 3: List files in sss directory
echo "3️⃣ Listing files in sss directory...\n";
if (is_dir('sss')) {
    $files = scandir('sss');
    echo "✅ sss directory exists, files:\n";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "   - $file\n";
        }
    }
} else {
    echo "❌ sss directory not found\n";
}

if (is_dir('../sss')) {
    $files = scandir('../sss');
    echo "✅ ../sss directory exists, files:\n";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "   - $file\n";
        }
    }
} else {
    echo "❌ ../sss directory not found\n";
}

echo "\n";

// Step 4: Try to include sss/dash.php
echo "4️⃣ Trying to include sss/dash.php...\n";
try {
    include 'sss/dash.php';
    echo "✅ sss/dash.php included successfully\n";
} catch (Exception $e) {
    echo "❌ Exception when including sss/dash.php: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Error when including sss/dash.php: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test complete!\n";
?>
