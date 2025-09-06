<?php
// Start session and check admin access
session_start();

// Simple admin check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header('Location: /home');
    exit;
}

// Use centralized database
require_once __DIR__ . '/api/ScreeningManager.php';

$screeningManager = ScreeningManager::getInstance();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['ajax_action']) {
        case 'get_screenings':
            $data = $screeningManager->getScreeningData();
            echo json_encode(['success' => true, 'data' => $data]);
            exit;
            
        case 'get_stats':
            $stats = $screeningManager->getScreeningStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            exit;
            
        case 'ensure_table':
            $result = $screeningManager->ensureScreeningTableExists();
            echo json_encode(['success' => $result, 'message' => $result ? 'Table ready' : 'Failed']);
            exit;
    }
}

// Get current stats
$stats = $screeningManager->getScreeningStats();
$username = $_SESSION['username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screening Administration - Nutrisaur</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .risk-severe { color: #f44336; }
        .risk-high { color: #ff9800; }
        .risk-moderate { color: #ffc107; }
        .risk-low { color: #4caf50; }

        .actions-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .btn {
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-weight: 600;
        }

        .btn-primary {
            background: #4CAF50;
            color: white;
        }

        .btn-secondary {
            background: #2196F3;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .data-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }

        .risk-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .api-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #2196F3;
        }

        .code-block {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🩺 Screening Administration</h1>
            <p>Welcome, <?php echo htmlspecialchars($username); ?>! Manage comprehensive nutrition screenings easily.</p>
        </div>

        <!-- Statistics Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_screenings'] ?? 0; ?></div>
                <div class="stat-label">Total Screenings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number risk-severe"><?php echo $stats['severe_risk'] ?? 0; ?></div>
                <div class="stat-label">Severe Risk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number risk-high"><?php echo $stats['high_risk'] ?? 0; ?></div>
                <div class="stat-label">High Risk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number risk-moderate"><?php echo $stats['moderate_risk'] ?? 0; ?></div>
                <div class="stat-label">Moderate Risk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number risk-low"><?php echo $stats['low_risk'] ?? 0; ?></div>
                <div class="stat-label">Low Risk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['intervention_needed'] ?? 0; ?></div>
                <div class="stat-label">Need Intervention</div>
            </div>
        </div>

        <!-- Actions Section -->
        <div class="actions-section">
            <h2>🛠️ System Management</h2>
            <p>Manage your screening system with these automated tools:</p>
            
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="ensureTable()">
                    🔄 Setup/Update Database
                </button>
                <button class="btn btn-secondary" onclick="refreshStats()">
                    📊 Refresh Statistics
                </button>
                <button class="btn btn-info" onclick="loadScreenings()">
                    📋 View All Screenings
                </button>
                <a href="/api/comprehensive_screening.php?action=test" class="btn btn-warning" target="_blank">
                    🧪 Test API System
                </a>
            </div>

            <div id="action-messages"></div>
        </div>

        <!-- API Information -->
        <div class="api-info">
            <h3>📱 Android Integration</h3>
            <p><strong>Your screening system is now super flexible!</strong> You can modify questions anytime without changing database structure.</p>
            
            <div class="code-block">
POST to: <?php echo $_SERVER['HTTP_HOST']; ?>/api/comprehensive_screening.php?action=save
Content-Type: application/json

{
    "email": "user@example.com",
    "municipality": "Your Municipality",
    "barangay": "Your Barangay",
    "age": 25,
    "weight": 60,
    "height": 165,
    "food_carbs": true,
    "family_diabetes": true,
    ... any other fields you add
}
            </div>
            
            <p><strong>Benefits:</strong></p>
            <ul>
                <li>✅ Add new questions without database changes</li>
                <li>✅ Remove questions without breaking anything</li>
                <li>✅ Automatic risk scoring adjustment</li>
                <li>✅ No connection issues with Railway</li>
                <li>✅ All data preserved in flexible JSON fields</li>
            </ul>
        </div>

        <!-- Data Section -->
        <div class="data-section">
            <h2>📋 Recent Screenings</h2>
            <div id="screenings-container">
                <div class="loading">Click "View All Screenings" to load data...</div>
            </div>
        </div>
    </div>

    <script>
        function showMessage(message, type = 'success') {
            const container = document.getElementById('action-messages');
            const className = type === 'success' ? 'success-message' : 'error-message';
            container.innerHTML = `<div class="${className}">${message}</div>`;
            setTimeout(() => container.innerHTML = '', 5000);
        }

        function ensureTable() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'ajax_action=ensure_table'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('✅ Database table ready! You can now use the screening system.');
                    refreshStats();
                } else {
                    showMessage('❌ Failed to setup table: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('❌ Error: ' + error.message, 'error');
            });
        }

        function refreshStats() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'ajax_action=get_stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Simple refresh for now
                } else {
                    showMessage('❌ Failed to refresh stats', 'error');
                }
            })
            .catch(error => {
                showMessage('❌ Error refreshing stats: ' + error.message, 'error');
            });
        }

        function loadScreenings() {
            const container = document.getElementById('screenings-container');
            container.innerHTML = '<div class="loading">Loading screenings...</div>';

            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'ajax_action=get_screenings'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    let html = `
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Location</th>
                                    <th>Age</th>
                                    <th>BMI</th>
                                    <th>Risk Score</th>
                                    <th>Risk Level</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                    data.data.forEach(screening => {
                        const riskClass = screening.risk_level ? screening.risk_level.toLowerCase() : 'low';
                        html += `
                            <tr>
                                <td>${screening.user_email || 'N/A'}</td>
                                <td>${screening.municipality || 'N/A'}, ${screening.barangay || 'N/A'}</td>
                                <td>${screening.age || 'N/A'}</td>
                                <td>${screening.bmi || 'N/A'}</td>
                                <td>${screening.risk_score || 0}</td>
                                <td><span class="risk-badge risk-${riskClass}">${screening.risk_level || 'Unknown'}</span></td>
                                <td>${new Date(screening.created_at).toLocaleDateString()}</td>
                            </tr>
                        `;
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                    showMessage(`✅ Loaded ${data.data.length} screening records`);
                } else {
                    container.innerHTML = '<div class="loading">No screening data found. Use the Android app to create some screenings!</div>';
                }
            })
            .catch(error => {
                container.innerHTML = '<div class="loading">Error loading data: ' + error.message + '</div>';
                showMessage('❌ Error loading screenings: ' + error.message, 'error');
            });
        }

        // Auto-load basic stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Screening Admin Panel loaded');
        });
    </script>
</body>
</html>
