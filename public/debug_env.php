<?php
echo "🔍 Debug Environment Variables\n";
echo "=============================\n\n";

// Show all environment variables
echo "📋 All Environment Variables:\n";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'MYSQL') !== false || strpos($key, 'RAILWAY') !== false) {
        echo "✅ $key = $value\n";
    }
}
echo "\n";

// Show specific MySQL variables
echo "🗄️ MySQL Environment Variables:\n";
$mysql_vars = [
    'MYSQLHOST',
    'MYSQLPORT', 
    'MYSQLUSER',
    'MYSQLPASSWORD',
    'MYSQLDATABASE',
    'MYSQL_URL',
    'MYSQL_PUBLIC_URL'
];

foreach ($mysql_vars as $var) {
    $value = $_ENV[$var] ?? 'NOT SET';
    echo "📍 $var = $value\n";
}
echo "\n";

// Show server info
echo "🌐 Server Information:\n";
echo "📍 Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";
echo "🚪 Server Port: " . ($_SERVER['SERVER_PORT'] ?? 'NOT SET') . "\n";
echo "🔗 Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "\n";

// Test basic connection
echo "🧪 Testing Basic Connection:\n";
if (isset($_ENV['MYSQLHOST']) && isset($_ENV['MYSQLPORT'])) {
    echo "✅ MYSQLHOST and MYSQLPORT are set\n";
    
    $host = $_ENV['MYSQLHOST'];
    $port = $_ENV['MYSQLPORT'];
    $user = $_ENV['MYSQLUSER'] ?? 'root';
    $pass = $_ENV['MYSQLPASSWORD'] ?? '';
    $db = $_ENV['MYSQLDATABASE'] ?? 'railway';
    
    echo "📍 Host: $host\n";
    echo "🚪 Port: $port\n";
    echo "👤 User: $user\n";
    echo "🗄️ Database: $db\n";
    
    // Try socket connection
    echo "\n🔌 Testing socket connection...\n";
    $socket = @fsockopen($host, $port, $errno, $errstr, 5);
    if ($socket) {
        echo "✅ Socket connection successful!\n";
        fclose($socket);
    } else {
        echo "❌ Socket connection failed: $errstr ($errno)\n";
    }
    
} else {
    echo "❌ MYSQLHOST or MYSQLPORT not set\n";
}

echo "\n🎯 Debug complete!\n";
?>
