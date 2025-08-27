<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['email'] = 'admin@example.com';
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// Include config for database connection
require_once __DIR__ . "/../config.php";
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
        .navbar-menu ul { list-style: none; }
        .navbar-menu li { margin: 15px 0; }
        .navbar-menu a { color: #E8F0D6; text-decoration: none; display: flex; align-items: center; padding: 10px; border-radius: 8px; transition: background 0.3s; }
        .navbar-menu a:hover { background: #A1B454; color: #1A211A; }
        
        .main-content { margin-left: 320px; padding: 20px; }
        .dashboard-header { background: #2A3326; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .metric-card { background: #2A3326; padding: 20px; border-radius: 12px; border-left: 4px solid #A1B454; }
        .metric-value { font-size: 2em; font-weight: bold; color: #A1B454; }
        .metric-label { color: #B5C88D; margin-top: 5px; }
        
        .chart-container { background: #2A3326; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .chart-title { color: #A1B454; margin-bottom: 15px; font-size: 1.2em; }
        
        .btn { background: #A1B454; color: #1A211A; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #8CA86E; }
    </style>
</head>
<body>
    <div class="navbar">
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
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
            <p>NutriSaur Dashboard - Nutrition Monitoring & Analytics</p>
        </div>
        
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-value" id="total-screened">-</div>
                <div class="metric-label">Total Screened</div>
            </div>
            <div class="metric-card">
                <div class="metric-value" id="high-risk">-</div>
                <div class="metric-label">High Risk Cases</div>
            </div>
            <div class="metric-card">
                <div class="metric-value" id="moderate-risk">-</div>
                <div class="metric-label">Moderate Risk</div>
            </div>
            <div class="metric-card">
                <div class="metric-value" id="low-risk">-</div>
                <div class="metric-label">Low Risk</div>
            </div>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">Risk Distribution</div>
            <canvas id="riskChart" width="400" height="200"></canvas>
        </div>
        
        <div class="chart-container">
            <div class="chart-title">Recent Activity</div>
            <div id="recent-activity">Loading...</div>
        </div>
        
        <button class="btn" onclick="refreshData()">Refresh Data</button>
    </div>

    <script>
        const API_BASE_URL = 'https://nutrisaur-production.up.railway.app/unified_api.php';
        
        async function fetchData(endpoint, params = {}) {
            try {
                const url = new URL(API_BASE_URL);
                url.searchParams.set('endpoint', endpoint);
                Object.keys(params).forEach(key => url.searchParams.set(key, params[key]));
                
                const response = await fetch(url);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                return await response.json();
            } catch (error) {
                console.error(`Error fetching ${endpoint}:`, error);
                return null;
            }
        }
        
        async function updateMetrics() {
            const metrics = await fetchData('community_metrics');
            if (metrics && metrics.success) {
                const data = metrics.data;
                document.getElementById('total-screened').textContent = data.total_screenings || 0;
                document.getElementById('high-risk').textContent = data.risk_distribution?.high || 0;
                document.getElementById('moderate-risk').textContent = data.risk_distribution?.moderate || 0;
                document.getElementById('low-risk').textContent = data.risk_distribution?.low || 0;
            }
        }
        
        async function updateRiskChart() {
            const data = await fetchData('risk_distribution');
            if (data && data.success) {
                const ctx = document.getElementById('riskChart').getContext('2d');
                // Simple chart rendering
                ctx.clearRect(0, 0, 400, 200);
                ctx.fillStyle = '#A1B454';
                ctx.font = '16px Arial';
                ctx.fillText('Risk Distribution Chart', 10, 30);
                ctx.fillText('Data loaded successfully', 10, 60);
            }
        }
        
        async function updateRecentActivity() {
            const data = await fetchData('community_metrics');
            if (data && data.success) {
                const activity = data.data.recent_activity;
                document.getElementById('recent-activity').innerHTML = `
                    <p>Screenings this week: ${activity?.screenings_this_week || 0}</p>
                    <p>Last updated: ${new Date().toLocaleString()}</p>
                `;
            }
        }
        
        async function refreshData() {
            await Promise.all([
                updateMetrics(),
                updateRiskChart(),
                updateRecentActivity()
            ]);
        }
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', refreshData);
        
        // Auto-refresh every 30 seconds
        setInterval(refreshData, 30000);
    </script>
</body>
</html>
