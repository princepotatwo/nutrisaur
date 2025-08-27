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

// Fetch user profile data if database is connected
$userProfile = null;
if ($dbConnected && $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM user_preferences 
            WHERE user_email = :email 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error fetching user profile: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings & Admin - NutriSaur</title>
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
        
        .settings-section { background: #2A3326; padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #A1B454; }
        .section-title { color: #A1B454; font-size: 1.2em; margin-bottom: 15px; }
        
        .btn { background: #A1B454; color: #1A211A; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; transition: background 0.3s; margin-right: 10px; margin-bottom: 10px; }
        .btn:hover { background: #8CA86E; }
        .btn-danger { background: #CF8686; }
        .btn-danger:hover { background: #E57373; }
        .btn-warning { background: #E0C989; }
        .btn-warning:hover { background: #FFB74D; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #B5C88D; }
        .form-group input, .form-group select { width: 100%; padding: 8px; border: 1px solid #546048; border-radius: 4px; background: #1A211A; color: #E8F0D6; }
        
        .user-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .user-table th, .user-table td { padding: 10px; text-align: left; border-bottom: 1px solid #546048; }
        .user-table th { background: #546048; color: #E8F0D6; }
        .user-table tr:hover { background: rgba(161, 180, 84, 0.1); }
        
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
            <h1>Settings & Administration</h1>
            <p>Manage system settings, users, and administrative functions</p>
        </div>
        
        <!-- Database Status Section -->
        <div class="settings-section">
            <div class="section-title">Database Connection Status</div>
            <?php if ($dbConnected): ?>
                <div class="db-status db-connected">
                    ✅ Database Connected Successfully
                </div>
                <p><strong>Host:</strong> <?php echo htmlspecialchars($mysql_host); ?></p>
                <p><strong>Port:</strong> <?php echo htmlspecialchars($mysql_port); ?></p>
                <p><strong>Database:</strong> <?php echo htmlspecialchars($mysql_database); ?></p>
                <p><strong>User:</strong> <?php echo htmlspecialchars($mysql_user); ?></p>
            <?php else: ?>
                <div class="db-status db-error">
                    ❌ Database Connection Failed
                </div>
                <p><strong>Error:</strong> <?php echo htmlspecialchars($dbError ?? 'Unknown error'); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- User Profile Section -->
        <div class="settings-section">
            <div class="section-title">Current User Profile</div>
            <p><strong>User ID:</strong> <?php echo htmlspecialchars($userId); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            
            <?php if ($userProfile): ?>
                <div class="db-status db-connected">
                    ✅ User Profile Found in Database
                </div>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($userProfile['name'] ?? 'N/A'); ?></p>
                <p><strong>Barangay:</strong> <?php echo htmlspecialchars($userProfile['barangay'] ?? 'N/A'); ?></p>
                <p><strong>Risk Score:</strong> <?php echo htmlspecialchars($userProfile['risk_score'] ?? 'N/A'); ?></p>
            <?php else: ?>
                <div class="db-status db-error">
                    ⚠️ No User Profile Found in Database
                </div>
            <?php endif; ?>
        </div>
        
        <!-- System Settings Section -->
        <div class="settings-section">
            <div class="section-title">System Settings</div>
            <div class="form-group">
                <label for="systemName">System Name:</label>
                <input type="text" id="systemName" value="NutriSaur" readonly>
            </div>
            <div class="form-group">
                <label for="systemVersion">System Version:</label>
                <input type="text" id="systemVersion" value="1.0.0" readonly>
            </div>
            <div class="form-group">
                <label for="phpVersion">PHP Version:</label>
                <input type="text" id="phpVersion" value="<?php echo PHP_VERSION; ?>" readonly>
            </div>
            <button class="btn" onclick="refreshSystemInfo()">Refresh System Info</button>
        </div>
        
        <!-- API Test Section -->
        <div class="settings-section">
            <div class="section-title">API Connection Test</div>
            <button class="btn" onclick="testAPI()">Test API Connection</button>
            <button class="btn btn-warning" onclick="testDatabaseAPI()">Test Database API</button>
            <div id="apiStatus" class="status-message" style="display: none;"></div>
        </div>
        
        <!-- User Management Section -->
        <div class="settings-section">
            <div class="section-title">User Management</div>
            <div class="form-group">
                <label for="userEmail">User Email:</label>
                <input type="email" id="userEmail" placeholder="Enter user email">
            </div>
            <div class="form-group">
                <label for="userName">User Name:</label>
                <input type="text" id="userName" placeholder="Enter user name">
            </div>
            <div class="form-group">
                <label for="userRole">User Role:</label>
                <select id="userRole">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="health_worker">Health Worker</option>
                </select>
            </div>
            <button class="btn" onclick="addUser()">Add User</button>
            <button class="btn btn-warning" onclick="loadUsers()">Load Users</button>
        </div>
        
        <!-- User Statistics Section -->
        <div class="settings-section">
            <div class="section-title">User Statistics</div>
            <div id="userStats">
                <p>Click "Load Users" to see statistics</p>
            </div>
        </div>
        
        <div id="statusMessage" class="status-message" style="display: none;"></div>
    </div>

    <script>
        const API_BASE_URL = 'https://nutrisaur-production.up.railway.app';
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Settings page loaded successfully');
            console.log('Database connection status:', <?php echo $dbConnected ? 'true' : 'false'; ?>);
        });
        
        // Test API connection
        async function testAPI() {
            try {
                showStatus('Testing API connection...', 'info');
                const response = await fetch(API_BASE_URL + '/health');
                const data = await response.json();
                
                if (data.status === 'healthy') {
                    showStatus('✅ API connection successful!', 'success');
                } else {
                    showStatus('⚠️ API connection failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showStatus('❌ API connection failed: ' + error.message, 'error');
            }
        }
        
        // Test Database API
        async function testDatabaseAPI() {
            try {
                showStatus('Testing database API...', 'info');
                const response = await fetch(API_BASE_URL + '/unified_api.php?endpoint=community_metrics');
                const data = await response.json();
                
                if (data.success) {
                    showStatus('✅ Database API working! Found ' + (data.data?.total_users || 0) + ' users', 'success');
                } else {
                    showStatus('⚠️ Database API failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showStatus('❌ Database API failed: ' + error.message, 'error');
            }
        }
        
        // Load users
        async function loadUsers() {
            try {
                showStatus('Loading users...', 'info');
                const response = await fetch(API_BASE_URL + '/unified_api.php?endpoint=community_metrics');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('userStats').innerHTML = `
                        <div class="db-status db-connected">
                            <p><strong>Total Users:</strong> ${stats.total_users || 0}</p>
                            <p><strong>Total Screenings:</strong> ${stats.total_screenings || 0}</p>
                            <p><strong>High Risk Cases:</strong> ${stats.risk_distribution?.high || 0}</p>
                            <p><strong>Moderate Risk Cases:</strong> ${stats.risk_distribution?.moderate || 0}</p>
                            <p><strong>Low Risk Cases:</strong> ${stats.risk_distribution?.low || 0}</p>
                        </div>
                    `;
                    showStatus('✅ Users loaded successfully!', 'success');
                } else {
                    document.getElementById('userStats').innerHTML = '<p>Failed to load user statistics</p>';
                    showStatus('⚠️ Failed to load users: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                document.getElementById('userStats').innerHTML = '<p>Error loading user statistics</p>';
                showStatus('❌ Error loading users: ' + error.message, 'error');
            }
        }
        
        // Add user function
        function addUser() {
            const email = document.getElementById('userEmail').value;
            const name = document.getElementById('userName').value;
            const role = document.getElementById('userRole').value;
            
            if (!email || !name) {
                showStatus('Please fill in all required fields', 'error');
                return;
            }
            
            showStatus('User management functionality coming soon...', 'info');
        }
        
        // Refresh system info
        function refreshSystemInfo() {
            showStatus('System information refreshed', 'success');
        }
        
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
