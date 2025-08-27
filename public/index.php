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

// Also show debug for API routes
$is_api_route = strpos($path, 'api/') === 0;

if ($is_test_route || $is_api_route) {
    // Debug routing for test routes and API routes
    echo "🔍 Routing Debug:\n";
    echo "📍 REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
    echo "🌐 SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
    echo "📁 PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
    echo "🎯 Parsed Path: '$path'\n";
    echo "📏 Path Length: " . strlen($path) . "\n";
    echo "🔗 Is API Route: " . ($is_api_route ? 'Yes' : 'No') . "\n";
    echo "🔍 Path starts with 'api/': " . (strpos($path, 'api/') === 0 ? 'Yes' : 'No') . "\n";
    echo "🔍 Raw path check: " . (strpos($path, 'api/') === 0 ? 'YES' : 'NO') . "\n\n";
}

// Route to appropriate file
switch ($path) {
    case '':
    case 'index':
        if ($is_test_route) echo "🏠 Routing to: Home/Index\n";
        // Suppress PHP notices for main website
        error_reporting(E_ERROR | E_PARSE);
        // Include the original home.php from sss directory
        include '../sss/home.php';
        break;
        
    case 'home':
    case 'home.php':
        if ($is_test_route) echo "🏠 Routing to: Home\n";
        // Suppress PHP notices for main website
        error_reporting(E_ERROR | E_PARSE);
        include '../sss/home.php';
        break;
        
    case 'dash':
    case 'dash.php':
        if ($is_test_route) echo "📊 Routing to: Dashboard\n";
        include '../sss/dash.php';
        break;
        
    case 'event':
    case 'events':
    case 'event.php':
        if ($is_test_route) echo "📅 Routing to: Events\n";
        include '../sss/event.php';
        break;
        
    case 'settings':
    case 'settings.php':
        if ($is_test_route) echo "⚙️ Routing to: Settings\n";
        include '../sss/settings.php';
        break;
        
    case 'ai':
    case 'AI.php':
        if ($is_test_route) echo "🤖 Routing to: AI\n";
        include '../sss/AI.php';
        break;
        
    case 'fpm':
    case 'FPM.php':
        if ($is_test_route) echo "📋 Routing to: FPM\n";
        include '../sss/FPM.php';
        break;
        
    case 'nr':
    case 'NR.php':
        if ($is_test_route) echo "📊 Routing to: NR\n";
        include '../sss/NR.php';
        break;
        
    case 'logout':
    case 'logout.php':
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
        
    case 'test_dash':
        if ($is_test_route) echo "🧪 Routing to: Test Dash\n";
        include 'test_dash.php';
        break;
        
    case 'test_dashboard':
        if ($is_test_route) echo "🧪 Routing to: Test Dashboard Direct\n";
        if (file_exists("../sss/dash.php")) {
            echo "✅ dash.php exists in sss directory\n";
            echo "📁 Current working directory: " . getcwd() . "\n";
            echo "🔍 Trying to include dash.php...\n";
            include "../sss/dash.php";
        } else {
            echo "❌ dash.php not found in sss directory\n";
            echo "📁 Contents of sss directory:\n";
            $files = scandir("../sss/");
            foreach ($files as $file) {
                if (strpos($file, 'dash') !== false) {
                    echo "  - $file\n";
                }
            }
        }
        break;
        
    case 'test_dashboard_db':
        if ($is_test_route) echo "🧪 Routing to: Test Dashboard Database\n";
        include 'test_dashboard_db.php';
        break;
        
    case 'test_api_endpoint':
        if ($is_test_route) echo "🧪 Routing to: Test API Endpoint\n";
        include 'test_api_endpoint.php';
        break;
        
    case 'debug_routing':
        echo "🧪 Routing to: Comprehensive Routing Debug\n";
        include 'debug_routing.php';
        break;
        
    case 'test_railway_routing':
        echo "🧪 Routing to: Railway Routing Test\n";
        include 'test_railway_routing.php';
        break;
        
    case 'test_api_routing':
        echo "🧪 Testing API Routing Specifically\n";
        echo "🔍 Testing /api/unified_api route:\n";
        echo "📍 REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
        echo "🎯 Parsed Path: '$path'\n";
        echo "🔍 Path starts with 'api/': " . (strpos($path, 'api/') === 0 ? 'Yes' : 'No') . "\n";
        echo "🔍 Exact match for 'api/unified_api': " . ($path === 'api/unified_api' ? 'Yes' : 'No') . "\n";
        echo "🔍 String comparison: " . (strcmp($path, 'api/unified_api') === 0 ? 'EQUAL' : 'NOT EQUAL') . "\n";
        echo "🔍 Path length: " . strlen($path) . "\n";
        echo "🔍 Path bytes: " . bin2hex($path) . "\n";
        break;
        
    case 'test_api_call':
        echo "🧪 Testing API Call Path\n";
        include 'test_api_call.php';
        break;
        
    case 'test_tables':
        echo "🧪 Testing Railway Database Tables\n";
        include 'test_tables.php';
        break;
        
    case 'create_missing_tables':
        echo "🔧 Creating Missing Tables\n";
        include 'create_missing_tables.php';
        break;
        
    case 'test_screening_data':
        echo "🧪 Testing Screening Data\n";
        include 'test_screening_data.php';
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
        
    case 'api/login':
        if ($is_test_route) echo "🔐 Routing to: Login API\n";
        include 'api/login.php';
        break;
        
    case 'api/unified_api':
        if ($is_test_route) echo "🔗 Routing to: Unified API\n";
        echo "🎯 API ROUTE REACHED: api/unified_api\n";
        echo "📍 REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
        echo "🎯 Parsed Path: '$path'\n";
        echo "🔍 Path starts with 'api/': " . (strpos($path, 'api/') === 0 ? 'Yes' : 'No') . "\n";
        echo "🔍 Exact match for 'api/unified_api': " . ($path === 'api/unified_api' ? 'Yes' : 'No') . "\n";
        echo "🔍 String comparison: " . (strcmp($path, 'api/unified_api') === 0 ? 'EQUAL' : 'NOT EQUAL') . "\n";
        echo "🔍 Path length: " . strlen($path) . "\n";
        echo "🔍 Path bytes: " . bin2hex($path) . "\n";
        echo "🔍 Including api/unified_api.php...\n";
        include 'api/unified_api.php';
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
