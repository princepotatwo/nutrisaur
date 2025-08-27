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
            <button class="btn btn-warning" onclick="testAPI()">Test API Connection</button>
        </div>
        
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
                <label for="databaseStatus">Database Status:</label>
                <input type="text" id="databaseStatus" value="Checking..." readonly>
            </div>
            <button class="btn" onclick="checkDatabaseStatus()">Check Database Status</button>
            <button class="btn btn-warning" onclick="refreshSystemInfo()">Refresh System Info</button>
        </div>
        
        <div class="settings-section">
            <div class="section-title">Data Management</div>
            <button class="btn" onclick="exportUserData()">Export User Data</button>
            <button class="btn btn-warning" onclick="backupDatabase()">Backup Database</button>
            <button class="btn btn-danger" onclick="clearTestData()">Clear Test Data</button>
        </div>
        
        <div class="settings-section">
            <div class="section-title">User Statistics</div>
            <div id="userStats">
                <p>Loading user statistics...</p>
            </div>
        </div>
        
        <div id="statusMessage" class="status-message" style="display: none;"></div>
    </div>

    <script>
        const API_BASE_URL = 'https://nutrisaur-production.up.railway.app';
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Settings page loaded');
            checkDatabaseStatus();
            loadUserStats();
        });
        
        // Test API connection
        async function testAPI() {
            try {
                showStatus('Testing API connection...', 'info');
                const response = await fetch(API_BASE_URL + '/unified_api.php?endpoint=community_metrics');
                const data = await response.json();
                
                if (data.success) {
                    showStatus('API connection successful! Database is accessible.', 'success');
                } else {
                    showStatus('API connection failed: ' + (data.message || 'Unknown error'), 'error');
                }
            } catch (error) {
                showStatus('API connection failed: ' + error.message, 'error');
            }
        }
        
        // Check database status
        async function checkDatabaseStatus() {
            try {
                const response = await fetch(API_BASE_URL + '/health');
                const data = await response.json();
                
                if (data.status === 'healthy') {
                    document.getElementById('databaseStatus').value = 'Connected';
                    document.getElementById('databaseStatus').style.color = '#A1B454';
                } else {
                    document.getElementById('databaseStatus').value = 'Error';
                    document.getElementById('databaseStatus').style.color = '#CF8686';
                }
            } catch (error) {
                document.getElementById('databaseStatus').value = 'Connection Failed';
                document.getElementById('databaseStatus').style.color = '#CF8686';
            }
        }
        
        // Load user statistics
        async function loadUserStats() {
            try {
                const response = await fetch(API_BASE_URL + '/unified_api.php?endpoint=community_metrics');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('userStats').innerHTML = `
                        <p><strong>Total Users:</strong> ${stats.total_users || 0}</p>
                        <p><strong>Total Screenings:</strong> ${stats.total_screenings || 0}</p>
                        <p><strong>High Risk Cases:</strong> ${stats.risk_distribution?.high || 0}</p>
                        <p><strong>Moderate Risk Cases:</strong> ${stats.risk_distribution?.moderate || 0}</p>
                        <p><strong>Low Risk Cases:</strong> ${stats.risk_distribution?.low || 0}</p>
                    `;
                } else {
                    document.getElementById('userStats').innerHTML = '<p>Failed to load user statistics</p>';
                }
            } catch (error) {
                document.getElementById('userStats').innerHTML = '<p>Error loading user statistics</p>';
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
        
        // Export user data
        function exportUserData() {
            showStatus('Export functionality coming soon...', 'info');
        }
        
        // Backup database
        function backupDatabase() {
            showStatus('Backup functionality coming soon...', 'info');
        }
        
        // Clear test data
        function clearTestData() {
            if (confirm('Are you sure you want to clear all test data? This action cannot be undone.')) {
                showStatus('Test data clearing functionality coming soon...', 'info');
            }
        }
        
        // Refresh system info
        function refreshSystemInfo() {
            checkDatabaseStatus();
            loadUserStats();
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
