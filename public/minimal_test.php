<?php
/**
 * Minimal Test - Just include config.php and see what happens
 */

echo "🧪 Minimal Test - Just config.php\n";
echo "================================\n\n";

// Step 1: Check current directory
echo "1️⃣ Current directory: " . getcwd() . "\n";
echo "📍 __DIR__: " . __DIR__ . "\n\n";

// Step 2: Check if config.php exists
echo "2️⃣ Checking if config.php exists...\n";
if (file_exists('config.php')) {
    echo "✅ config.php exists in current directory\n";
} else {
    echo "❌ config.php NOT found in current directory\n";
}

if (file_exists(__DIR__ . '/config.php')) {
    echo "✅ config.php exists at " . __DIR__ . '/config.php' . "\n";
} else {
    echo "❌ config.php NOT found at " . __DIR__ . '/config.php' . "\n";
}

echo "\n";

// Step 3: Try to include config.php
echo "3️⃣ Including config.php...\n";
try {
    include 'config.php';
    echo "✅ config.php included successfully\n\n";
} catch (Exception $e) {
    echo "❌ Exception when including config.php: " . $e->getMessage() . "\n\n";
} catch (Error $e) {
    echo "❌ Error when including config.php: " . $e->getMessage() . "\n\n";
}

// Step 4: Check if variables are set
echo "4️⃣ Checking variables after include...\n";
echo "📍 mysql_host: " . (isset($mysql_host) ? $mysql_host : 'NOT SET') . "\n";
echo "🚪 mysql_port: " . (isset($mysql_port) ? $mysql_port : 'NOT SET') . "\n";
echo "👤 mysql_user: " . (isset($mysql_user) ? $mysql_user : 'NOT SET') . "\n";
echo "🗄️ mysql_database: " . (isset($mysql_database) ? $mysql_database : 'NOT SET') . "\n\n";

echo "🎯 Test complete!\n";
?>
