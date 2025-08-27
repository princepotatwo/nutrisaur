<?php
// Main entry point for Nutrisaur Web Application
session_start();

// Set headers for CORS and security
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: text/html; charset=UTF-8');

// Check if it's a preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set working directory to public/ so relative paths work correctly
chdir(__DIR__);

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Only show debug output for test routes
$is_test_route = in_array($path, ['test_config', 'minimal_test', 'debug_config', 'debug_env', 'simple_db_test', 'test_db_connection']);

if ($is_test_route) {
    // Debug routing for test routes
    echo "🔍 Routing Debug:\n";
    echo "📍 REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "🌐 SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
    echo "📁 PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
    echo "🎯 Parsed Path: '$path'\n";
    echo "📏 Path Length: " . strlen($path) . "\n\n";
}

// Route to appropriate file
switch ($path) {
    case '':
    case 'index':
        if ($is_test_route) echo "🏠 Routing to: Home/Index\n";
        // Include the original home.php from sss directory
        include '../sss/home.php';
        break;
        
    case 'home':
        if ($is_test_route) echo "🏠 Routing to: Home\n";
        include '../sss/home.php';
        break;
        
    case 'dash':
    case 'dashboard':
        if ($is_test_route) echo "📊 Routing to: Dashboard\n";
        include '../sss/dash.php';
        break;
        
    case 'event':
    case 'events':
        if ($is_test_route) echo "📅 Routing to: Events\n";
        include '../sss/event.php';
        break;
        
    case 'settings':
        if ($is_test_route) echo "⚙️ Routing to: Settings\n";
        include '../sss/settings.php';
        break;
        
    case 'ai':
        if ($is_test_route) echo "🤖 Routing to: AI\n";
        include '../sss/AI.php';
        break;
        
    case 'fpm':
        if ($is_test_route) echo "📋 Routing to: FPM\n";
        include '../sss/FPM.php';
        break;
        
    case 'nr':
        if ($is_test_route) echo "📊 Routing to: NR\n";
        include '../sss/NR.php';
        break;
        
    case 'logout':
        if ($is_test_route) echo "🚪 Routing to: Logout\n";
        include '../sss/logout.php';
        break;
        
    case 'test_db_connection':
        if ($is_test_route) echo "🗄️ Routing to: Test DB Connection\n";
        include 'test_db_connection.php';
        break;
        
    case 'import_database':
        if ($is_test_route) echo "📥 Routing to: Import Database\n";
        include 'import_database.php';
        break;
        
    case 'simple_db_test':
        if ($is_test_route) echo "🗄️ Routing to: Simple DB Test\n";
        include 'simple_db_test.php';
        break;
        
    case 'minimal_test':
        if ($is_test_route) echo "🧪 Routing to: Minimal Test\n";
        include 'minimal_test.php';
        break;
        
    case 'debug_config':
        if ($is_test_route) echo "🔧 Routing to: Debug Config\n";
        include 'debug_config.php';
        break;
        
    case 'test_config':
        if ($is_test_route) echo "🧪 Routing to: Test Config\n";
        include 'test_config.php';
        break;
        
    case 'debug_env':
        if ($is_test_route) echo "🔍 Routing to: Debug Environment\n";
        include 'debug_env.php';
        break;
        
    case 'health':
        if ($is_test_route) echo "❤️ Routing to: Health Check\n";
        include 'health.php';
        break;
        
    case 'api/check_session':
        if ($is_test_route) echo "🔐 Routing to: Check Session API\n";
        include 'api/check_session.php';
        break;
        
    default:
        if ($is_test_route) {
            echo "❓ No route match, trying default handler\n";
        }
        // First check if it's a direct sss file
        if (file_exists("../sss/$path.php")) {
            if ($is_test_route) echo "📁 Found file in sss: ../sss/$path.php\n";
            include "../sss/$path.php";
        } elseif (file_exists("$path.php")) {
            if ($is_test_route) echo "📁 Found file: $path.php\n";
            include "$path.php";
        } else {
            if ($is_test_route) echo "❌ File not found: $path.php or ../sss/$path.php\n";
            http_response_code(404);
            echo '<h1>404 - Page Not Found</h1>';
            echo '<p>The requested page could not be found.</p>';
            echo '<p><a href="/">Go to Home</a></p>';
        }
        break;
}
?>
