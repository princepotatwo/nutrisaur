<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    // Redirect to login page if not logged in
    header('Location: /');
    exit;
}

// Database connection - Use the same working approach as dash.php
$mysql_host = 'mainline.proxy.rlwy.net';
$mysql_port = 26063;
$mysql_user = 'root';
$mysql_password = 'nZhQwfTnAJfFieCpIclAMtOQbBxcjwgy';
$mysql_database = 'railway';

// If MYSQL_PUBLIC_URL is set (Railway sets this), parse it
if (isset($_ENV['MYSQL_PUBLIC_URL'])) {
    $mysql_url = $_ENV['MYSQL_PUBLIC_URL'];
    $pattern = '/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/';
    if (preg_match($pattern, $mysql_url, $matches)) {
        $mysql_user = $matches[1];
        $mysql_password = $matches[2];
        $mysql_host = $matches[3];
        $mysql_port = $matches[4];
        $mysql_database = $matches[5];
    }
}

// Create database connection
try {
    $dsn = "mysql:host={$mysql_host};port={$mysql_port};dbname={$mysql_database};charset=utf8mb4";
    $conn = new PDO($dsn, $mysql_user, $mysql_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    $dbConnected = true;
} catch (PDOException $e) {
    // If database connection fails, show error but don't crash
    $conn = null;
    $dbConnected = false;
    $dbError = "Database connection failed: " . $e->getMessage();
}

// Get user info from session
$userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1;
$username = $_SESSION['username'] ?? 'admin';
$email = $_SESSION['email'] ?? 'admin@example.com';

// Fetch basic statistics if database is connected
$totalUsers = 0;
$totalScreenings = 0;
$highRiskCases = 0;
$moderateRiskCases = 0;
$lowRiskCases = 0;

if ($dbConnected && $conn) {
    try {
        // Get total users
        $stmt = $conn->query("SELECT COUNT(DISTINCT user_email) as total_users FROM user_preferences");
        $result = $stmt->fetch();
        $totalUsers = $result['total_users'] ?? 0;
        
        // Get total screenings
        $stmt = $conn->query("SELECT COUNT(*) as total_screenings FROM user_preferences");
        $result = $stmt->fetch();
        $totalScreenings = $result['total_screenings'] ?? 0;
        
        // Get risk distribution
        $stmt = $conn->query("
            SELECT 
                CASE 
                    WHEN risk_score >= 75 THEN 'high'
                    WHEN risk_score >= 50 THEN 'moderate'
                    ELSE 'low'
                END as risk_level,
                COUNT(*) as count
            FROM user_preferences 
            WHERE risk_score IS NOT NULL 
            GROUP BY risk_level
        ");
        $riskResults = $stmt->fetchAll();
        
        foreach ($riskResults as $row) {
            if ($row['risk_level'] === 'high') $highRiskCases = $row['count'];
            elseif ($row['risk_level'] === 'moderate') $moderateRiskCases = $row['count'];
            else $lowRiskCases = $row['count'];
        }
        
    } catch (PDOException $e) {
        error_log("Error fetching dashboard data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NutriSaur</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1A211A; color: #E8F0D6; }
        
        .navbar {
            position: fixed; top: 0; left: 0; width: 320px; height: 100vh;
            background: #2A3326; border-right: 2px solid #A1B454; padding: 20px;
        }
        .navbar-logo {
            display: flex; align-items: center; margin-bottom: 30px;
        }
        .navbar-logo-icon { margin-right: 15px; }
        .navbar-logo-text { font-size: 1.5em; font-weight: bold; color: #A1B454; }
        .navbar-menu ul { list-style: none; }
        .navbar-menu li { margin: 15px 0; }
        .navbar-menu a { color: #E8F0D6; text-decoration: none; display: flex; align-items: center; padding: 10px; border-radius: 8px; transition: background 0.3s; }
        .navbar-menu a:hover { background: #A1B454; color: #1A211A; }
        
        .main-content { margin-left: 320px; padding: 20px; }
        .page-header { background: #2A3326; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .dashboard-card { background: #2A3326; padding: 20px; border-radius: 12px; border-left: 4px solid #A1B454; }
        .card-title { color: #A1B454; font-size: 1.2em; margin-bottom: 15px; }
        .card-value { font-size: 2em; font-weight: bold; color: #E8F0D6; margin-bottom: 10px; }
        .card-description { color: #B5C88D; font-size: 0.9em; }
        
        .status-message { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .status-success { background: rgba(161, 180, 84, 0.2); color: #A1B454; border: 1px solid #A1B454; }
        .status-error { background: rgba(207, 134, 134, 0.2); color: #CF8686; border: 1px solid #CF8686; }
        .status-info { background: rgba(224, 201, 137, 0.2); color: #E0C989; border: 1px solid #E0C989; }
        
        .db-status { padding: 10px; border-radius: 4px; margin: 10px 0; }
        .db-connected { background: rgba(161, 180, 84, 0.2); color: #A1B454; border: 1px solid #A1B454; }
        .db-error { background: rgba(207, 134, 134, 0.2); color: #CF8686; border: 1px solid #CF8686; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-logo">
            <div class="navbar-logo-icon">
                <img src="../sss/logo.png" alt="Logo" style="width: 40px; height: 40px;">
            </div>
            <div class="navbar-logo-text">NutriSaur</div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash"><span>Dashboard</span></a></li>
                <li><a href="event"><span>Nutrition Event Notifications</span></a></li>
                <li><a href="ai"><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings"><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span>Logout</span></a></li>
            </ul>
        </div>
    </div>
    
    <div class="main-content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($username); ?>! Here's your nutrition community overview.</p>
        </div>
        
        <!-- Database Status Section -->
        <div class="dashboard-card">
            <div class="card-title">Database Connection Status</div>
            <?php if ($dbConnected): ?>
                <div class="db-status db-connected">
                    ✅ Database Connected Successfully
                </div>
                <p><strong>Host:</strong> <?php echo htmlspecialchars($mysql_host); ?></p>
                <p><strong>Database:</strong> <?php echo htmlspecialchars($mysql_database); ?></p>
            <?php else: ?>
                <div class="db-status db-error">
                    ❌ Database Connection Failed
                </div>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($dbError ?? 'Unknown error'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Community Overview -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-title">Total Community Members</div>
                <div class="card-value"><?php echo $totalUsers; ?></div>
                <div class="card-description">Registered users in the system</div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-title">Total Screenings</div>
                <div class="card-value"><?php echo $totalScreenings; ?></div>
                <div class="card-description">Nutrition assessments completed</div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-title">High Risk Cases</div>
                <div class="card-value" style="color: #CF8686;"><?php echo $highRiskCases; ?></div>
                <div class="card-description">Require immediate attention</div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-title">Moderate Risk Cases</div>
                <div class="card-value" style="color: #E0C989;"><?php echo $moderateRiskCases; ?></div>
                <div class="card-description">Need monitoring</div>
            </div>
            
            <div class="dashboard-card">
                <div class="card-title">Low Risk Cases</div>
                <div class="card-value" style="color: #A1B454;"><?php echo $lowRiskCases; ?></div>
                <div class="card-description">Healthy status</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-title">Quick Actions</div>
            <button onclick="location.href='event'" style="background: #A1B454; color: #1A211A; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-right: 10px; margin-bottom: 10px;">View Events</button>
            <button onclick="location.href='ai'" style="background: #A1B454; color: #1A211A; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-right: 10px; margin-bottom: 10px;">AI Chatbot</button>
            <button onclick="location.href='settings'" style="background: #A1B454; color: #1A211A; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; margin-right: 10px; margin-bottom: 10px;">Settings</button>
        </div>
        
        <div id="statusMessage" class="status-message" style="display: none;"></div>
    </div>

    <script>
        const API_BASE_URL = 'https://nutrisaur-production.up.railway.app';
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard loaded successfully');
            console.log('Database connection status:', <?php echo $dbConnected ? 'true' : 'false'; ?>);
            console.log('Total users:', <?php echo $totalUsers; ?>);
            console.log('Total screenings:', <?php echo $totalScreenings; ?>);
        });
        
        // Show status message
        function showStatus(message, type) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.textContent = message;
            statusDiv.className = `status-message status-${type}`;
            statusDiv.style.display = 'block';
            
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 5000);
        }
    </script>
</body>
</html>
