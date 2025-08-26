<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // For development/testing, set default values
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['email'] = 'admin@example.com';
    $_SESSION['is_admin'] = true;
    $_SESSION['role'] = 'admin';
    
    // Uncomment the following lines for production:
    // header("Location: home.php");
    // exit;
}

// Get user info from session
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

// Include the centralized configuration file
require_once __DIR__ . "/../config.php";
    
    // Get user profile data
    $profile = null;
    try {
        $stmt = $conn->prepare("
            SELECT u.*, up.* 
            FROM users u 
            LEFT JOIN user_preferences up ON u.email = up.user_email 
            WHERE u.user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $profile = null;
    }
    
    // Get user nutrition goals
    $stmt = $conn->prepare("SELECT * FROM nutrition_goals WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $goals = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSaur Combined Dashboard</title>
    <style>
        /* Dark Theme (Default) - Softer colors */
        .dark-theme {
            --color-bg: #1A211A;
            --color-card: #2A3326;
            --color-highlight: #A1B454;
            --color-text: #E8F0D6;
            --color-accent1: #8CA86E;
            --color-accent2: #B5C88D;
            --color-accent3: #546048;
            --color-accent4: #C9D8AA;
            --color-danger: #CF8686;
            --color-warning: #E0C989;
        }

        /* Light Theme - Softer colors */
        .light-theme {
            --color-bg: #a0ca3f;
            --color-card: #EAF0DC;
            --color-highlight: #8EB96E;
            --color-text: #415939;
            --color-accent1: #F9B97F;
            --color-accent2: #E9957C;
            --color-accent3: #76BB6E;
            --color-accent4: #D7E3A0;
            --color-danger: #E98D7C;
            --color-warning: #F9C87F;
        }

        .light-theme body {
            background: linear-gradient(135deg, #DCE8C0, #C5DBA1);
            background-size: 400% 400%;
            animation: gradientBackground 15s ease infinite;
            background-image: none;
        }

        @keyframes gradientBackground {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
        }

        body {
            min-height: 100vh;
            background-color: var(--color-bg);
            color: var(--color-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
            padding-left: 320px;
            line-height: 1.6;
            letter-spacing: 0.2px;
        }

        /* Navbar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 300px;
            height: 100vh;
            background-color: var(--color-card);
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
            padding: 0;
            box-sizing: border-box;
            overflow-y: auto;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }

        .navbar-header {
            padding: 30px 25px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(164, 188, 46, 0.2);
        }

        .navbar-logo {
            display: flex;
            align-items: center;
        }

        .navbar-logo-icon {
            width: 45px;
            height: 45px;
            border-radius: 8px;
            margin-right: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--color-text);
            font-weight: bold;
            font-size: 20px;
        }

        .navbar-logo-text {
            font-size: 24px;
            font-weight: 600;
            color: var(--color-text);
        }

        .navbar-menu {
            flex: 1;
            padding: 30px 0;
        }

        .navbar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .navbar li {
            margin-bottom: 5px;
        }

        .navbar a {
            text-decoration: none;
            color: var(--color-text);
            font-size: 17px;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            opacity: 0.9;
            border-radius: 0 8px 8px 0;
            margin-right: 10px;
        }

        .navbar a:hover {
            background-color: rgba(161, 180, 84, 0.08);
            color: var(--color-highlight);
            opacity: 1;
        }

        .navbar a.active {
            background-color: rgba(161, 180, 84, 0.12);
            color: var(--color-highlight);
            opacity: 1;
            font-weight: 500;
            border-left: 3px solid var(--color-highlight);
        }

        .navbar-icon {
            margin-right: 15px;
            width: 24px;
            font-size: 20px;
        }

        .navbar-footer {
            padding: 20px;
            border-top: 1px solid rgba(164, 188, 46, 0.2);
            font-size: 12px;
            opacity: 0.6;
            text-align: center;
        }

        /* Main Dashboard Styles */
        .dashboard {
            padding: 20px;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            font-size: 2.5em;
            color: var(--color-highlight);
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .theme-toggle-btn {
            background: var(--color-card);
            border: 2px solid var(--color-highlight);
            color: var(--color-text);
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .theme-toggle-btn:hover {
            background: var(--color-highlight);
            color: var(--color-card);
            transform: scale(1.1);
        }

        .theme-icon {
            font-size: 18px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            background: var(--color-highlight);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: var(--color-card);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--color-card);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(161, 180, 84, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
            border-color: var(--color-highlight);
        }

        .stat-card h3 {
            color: var(--color-accent1);
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--color-highlight);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--color-text);
            opacity: 0.8;
            font-size: 0.9em;
        }

        /* Chart Rows */
        .chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: var(--color-card);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(161, 180, 84, 0.1);
            transition: all 0.3s ease;
        }

        .chart-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(161, 180, 84, 0.15);
        }

        .chart-card h3 {
            color: var(--color-accent1);
            margin-bottom: 15px;
            font-size: 1.3em;
        }

        .chart-description {
            color: var(--color-text);
            opacity: 0.8;
            margin-bottom: 20px;
            font-size: 0.9em;
            line-height: 1.5;
        }

        /* Donut Chart */
        .donut-chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            position: relative;
        }

        .new-donut-chart {
            max-width: 100%;
            height: auto;
        }

        .donut-hole {
            fill: var(--color-card);
        }

        .donut-center-text {
            font-size: 24px;
            font-weight: bold;
            fill: var(--color-highlight);
        }

        .segments {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 20px;
        }

        .segment-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: rgba(161, 180, 84, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--color-highlight);
        }

        .segment-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }

        .segment-label {
            flex: 1;
            color: var(--color-text);
        }

        .segment-value {
            color: var(--color-highlight);
            font-weight: bold;
        }

        /* Alert List */
        .alert-list {
            list-style: none;
            padding: 0;
        }

        .alert-list li {
            padding: 15px;
            margin-bottom: 10px;
            background: rgba(207, 134, 134, 0.1);
            border-left: 4px solid var(--color-danger);
            border-radius: 8px;
            color: var(--color-text);
        }

        /* Line Charts */
        .line-chart-container {
            position: relative;
            margin: 20px 0;
        }

        .y-axis-label {
            position: absolute;
            left: -30px;
            top: 50%;
            transform: rotate(-90deg);
            color: var(--color-text);
            opacity: 0.7;
            font-size: 12px;
        }

        .line-chart {
            width: 100%;
            height: 200px;
        }

        .x-axis {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding: 0 20px;
        }

        .axis-label {
            color: var(--color-text);
            opacity: 0.7;
            font-size: 12px;
        }

        /* Filter Section */
        .filter-section {
            background: var(--color-card);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 500;
            color: var(--color-accent1);
        }

        .filter-group select {
            padding: 8px 12px;
            border: 1px solid rgba(161, 180, 84, 0.3);
            border-radius: 8px;
            background: var(--color-bg);
            color: var(--color-text);
        }

        .time-frame-buttons {
            display: flex;
            gap: 8px;
        }

        .time-btn {
            padding: 6px 12px;
            border: 1px solid rgba(161, 180, 84, 0.3);
            border-radius: 6px;
            background: var(--color-bg);
            color: var(--color-text);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .time-btn.active,
        .time-btn:hover {
            background: var(--color-highlight);
            color: var(--color-card);
            border-color: var(--color-highlight);
        }

        /* Card Container */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .card {
            background: var(--color-card);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(161, 180, 84, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(161, 180, 84, 0.15);
        }

        .card h2 {
            color: var(--color-accent1);
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .metric-value {
            font-size: 2.2em;
            font-weight: bold;
            color: var(--color-highlight);
            margin-bottom: 8px;
        }

        .metric-change {
            color: var(--color-accent2);
            font-size: 0.9em;
            margin-bottom: 8px;
        }

        .metric-note {
            color: var(--color-text);
            opacity: 0.7;
            font-size: 0.8em;
        }

        .large-card {
            grid-column: span 2;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding-left: 100px;
            }
            
            .navbar {
                width: 80px;
                transform: translateX(0);
                transition: transform 0.3s ease, width 0.3s ease;
            }
            
            .navbar:hover {
                width: 300px;
            }
            
            .navbar-logo-text, .navbar span:not(.navbar-icon) {
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            
            .navbar:hover .navbar-logo-text, 
            .navbar:hover span:not(.navbar-icon) {
                opacity: 1;
            }
            
            .chart-row {
                grid-template-columns: 1fr;
            }
            
            .large-card {
                grid-column: span 1;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        .light-theme .navbar {
            background-color: rgba(234, 240, 220, 0.85);
            backdrop-filter: blur(10px);
            box-shadow: 3px 0 20px rgba(0, 0, 0, 0.06);
        }

        /* Action Button Styles */
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            min-width: 120px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-add {
            background: var(--color-highlight);
            color: white;
        }

        .btn-add:hover {
            background: var(--color-accent1);
        }

        .btn-secondary {
            background: var(--color-accent3);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--color-accent2);
        }

        .btn-danger {
            background: var(--color-danger);
            color: white;
        }

        .btn-danger:hover {
            background: #b85c5c;
        }

        /* Button container styling */
        .action-buttons-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        /* Responsive design for action buttons */
        @media (max-width: 768px) {
            .action-buttons-container {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .btn {
                min-width: 200px;
                padding: 15px 20px;
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .btn {
                min-width: 100%;
                padding: 12px 16px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body class="dark-theme">

    <div class="navbar">
        <div class="navbar-header">
            <div class="navbar-logo">
                <div class="navbar-logo-icon">
                    <img src="logo.png" alt="Logo" style="width: 40px; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash.php"><span class="navbar-icon"></span><span>Dashboard</span></a></li>

                <li><a href="event.php"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="AI.php"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings.php"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout.php" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v1.0 • © 2023</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>
    
    <div class="dashboard">
        <header>
            <div class="dashboard-header">
                <h1>Combined Dashboard</h1>
                <div class="user-info">
                    <button id="theme-toggle" class="theme-toggle-btn" title="Toggle theme">
                        <span class="theme-icon">🌙</span>
                    </button>
                    <div class="user-avatar"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
                    <span><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>
        </header>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <label>Select Barangay:</label>
                <select id="barangay-selector">
                    <option value="">All Barangays</option>
                    <option value="Lamao">Lamao</option>
                    <option value="Pilar">Pilar</option>
                    <option value="Balanga">Balanga</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Time Frame:</label>
                <div class="time-frame-buttons">
                    <button class="time-btn active">1 Day</button>
                    <button class="time-btn">1 Week</button>
                    <button class="time-btn">1 Month</button>
                    <button class="time-btn">3 Months</button>
                    <button class="time-btn">1 Year</button>
                </button>
            </div>
        </div>

        <!-- Action Buttons Section -->
        <div class="action-buttons-container">
            <button class="btn btn-add" onclick="showAddUserModal()">
                <span>+</span>
                Add User
            </button>
            
            <button class="btn btn-secondary" onclick="downloadCSVTemplate()">
                <span>📥</span>
                Download Template
            </button>
            
            <button class="btn btn-secondary" onclick="showCSVImportModal()">
                <span>📁</span>
                Import CSV
            </button>
            
            <button class="btn btn-danger" onclick="deleteUsersByLocation()">
                <span>🗑️</span>
                Delete by Location
            </button>
        </div>

        <!-- Key Metrics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Screened</h3>
                <div class="stat-value" id="total-screened">0</div>
                <div class="stat-label">Children & adults assessed</div>
            </div>
            <div class="stat-card">
                <h3>High Risk Cases</h3>
                <div class="stat-value" id="high-risk">0</div>
                <div class="stat-label">Risk score ≥30 (WHO standard)</div>
            </div>
            <div class="stat-card">
                <h3>SAM Cases</h3>
                <div class="stat-value" id="sam-cases">0</div>
                <div class="stat-label">Severe Acute Malnutrition</div>
            </div>
            <div class="stat-card">
                <h3>Average Risk Score</h3>
                <div class="stat-value" id="avg-risk">0.0</div>
                <div class="stat-label">Community average</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-row">
            <div class="chart-card">
                <h3>Malnutrition Risk Levels</h3>
                <p class="chart-description">Distribution of children by malnutrition risk severity based on screening assessments. Higher risk levels indicate greater nutritional intervention needs.</p>
                <div class="donut-chart-container">
                    <svg class="new-donut-chart" id="new-risk-chart" width="300" height="300" viewBox="0 0 300 300">
                        <circle class="donut-hole" cx="150" cy="150" r="90" fill="var(--color-card)"/>
                        <g id="donut-segments"></g>
                        <text class="donut-center-text" x="150" y="150" text-anchor="middle" dy=".3em" id="new-risk-center-text">--</text>
                    </svg>
                </div>
                <div class="segments" id="new-risk-segments"></div>
            </div>
            
            <div class="chart-card">
                <h3>Critical Alerts</h3>
                <p class="chart-description">Priority cases requiring immediate medical attention based on clinical indicators and screening results.</p>
                <ul class="alert-list" id="critical-alerts">
                    <li>No critical alerts at this time</li>
                </ul>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-card">
                <h3>WHZ Categories</h3>
                <p class="chart-description">Weight-for-Height Z-Score distribution following WHO Growth Standards for precise malnutrition classification.</p>
                <div class="line-chart-container whz-chart-container">
                    <div class="y-axis-label">Count</div>
                    <svg class="line-chart" id="whz-distribution-chart" preserveAspectRatio="none" viewBox="0 0 1000 500">
                        <defs>
                            <linearGradient id="whz-line-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="var(--color-highlight)" stop-opacity="0.8" />
                                <stop offset="100%" stop-color="var(--color-accent3)" stop-opacity="0.1" />
                            </linearGradient>
                        </defs>
                        <path class="line-path" id="whz-line-path" d=""></path>
                        <path class="line-area" id="whz-line-area" d=""></path>
                    </svg>
                    <div class="x-axis">
                        <span class="axis-label">SAM</span>
                        <span class="axis-label">MAM</span>
                        <span class="axis-label">Normal</span>
                        <span class="axis-label">Overweight</span>
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>Age Group Analysis</h3>
                <p class="chart-description">Distribution of malnutrition cases across different age groups based on WHO standards</p>
                <div class="age-distribution" id="age-distribution">
                    <div style="text-align: center; padding: 40px; color: var(--color-text); opacity: 0.7;">
                        Age distribution chart will be displayed here
                    </div>
                </div>
            </div>
        </div>

        <!-- Community Programs Section -->
        <div class="card-container">
            <div class="large-card chart-card">
                <h3>Community Programs & Events</h3>
                <p class="chart-description">Upcoming nutrition programs and community events to support healthy eating habits</p>
                <div style="padding: 20px; background: rgba(161, 180, 84, 0.1); border-radius: 10px; border-left: 4px solid var(--color-highlight);">
                    <h4 style="color: var(--color-highlight); margin-bottom: 15px;">Featured Programs</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 10px 0; border-bottom: 1px solid rgba(161, 180, 84, 0.2);">
                            <strong>Nutrition Workshop</strong> - Every Saturday, 9:00 AM
                        </li>
                        <li style="padding: 10px 0; border-bottom: 1px solid rgba(161, 180, 84, 0.2);">
                            <strong>Healthy Cooking Demo</strong> - Monthly, 2:00 PM
                        </li>
                        <li style="padding: 10px 0;">
                            <strong>Community Garden Project</strong> - Ongoing
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggleBtn = document.getElementById('theme-toggle');
            const themeIcon = document.querySelector('.theme-icon');
            const body = document.body;
            
            // Load saved theme preference
            const savedTheme = localStorage.getItem('nutrisaur-theme') || 'dark';
            if (savedTheme === 'light') {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                themeIcon.textContent = '☀️';
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                themeIcon.textContent = '🌙';
            }
            
            // Theme toggle click handler
            if (themeToggleBtn) {
                themeToggleBtn.addEventListener('click', function() {
                    if (body.classList.contains('dark-theme')) {
                        // Switch to light theme
                        body.classList.remove('dark-theme');
                        body.classList.add('light-theme');
                        themeIcon.textContent = '☀️';
                        localStorage.setItem('nutrisaur-theme', 'light');
                    } else {
                        // Switch to dark theme
                        body.classList.remove('light-theme');
                        body.classList.add('dark-theme');
                        themeIcon.textContent = '🌙';
                        localStorage.setItem('nutrisaur-theme', 'dark');
                    }
                });
            }

            // Time frame button functionality
            const timeButtons = document.querySelectorAll('.time-btn');
            timeButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    timeButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    // Here you would typically reload data based on selected time frame
                });
            });

            // Barangay selector functionality
            const barangaySelector = document.getElementById('barangay-selector');
            if (barangaySelector) {
                barangaySelector.addEventListener('change', function() {
                    // Here you would typically reload data based on selected barangay
                    console.log('Selected barangay:', this.value);
                });
            }

            // Initialize dashboard with sample data
            initializeDashboard();
        });

        function initializeDashboard() {
            // Sample data for demonstration
            document.getElementById('total-screened').textContent = '247';
            document.getElementById('high-risk').textContent = '23';
            document.getElementById('sam-cases').textContent = '8';
            document.getElementById('avg-risk').textContent = '18.5';

            // Create sample donut chart
            createSampleDonutChart();
        }

        function createSampleDonutChart() {
            const data = [
                { label: 'Low Risk', value: 180, color: '#4CAF50' },
                { label: 'Moderate Risk', value: 44, color: '#FF9800' },
                { label: 'High Risk', value: 23, color: '#F44336' }
            ];

            const total = data.reduce((sum, item) => sum + item.value, 0);
            let currentAngle = -90;

            const segmentsContainer = document.getElementById('donut-segments');
            segmentsContainer.innerHTML = '';

            data.forEach(item => {
                const percentage = (item.value / total) * 100;
                const angle = (percentage / 100) * 360;
                
                // Create SVG arc
                const radius = 90;
                const x1 = 150 + radius * Math.cos(currentAngle * Math.PI / 180);
                const y1 = 150 + radius * Math.sin(currentAngle * Math.PI / 180);
                const x2 = 150 + radius * Math.cos((currentAngle + angle) * Math.PI / 180);
                const y2 = 150 + radius * Math.sin((currentAngle + angle) * Math.PI / 180);
                
                const largeArcFlag = angle > 180 ? 1 : 0;
                const pathData = `M ${x1} ${y1} A ${radius} ${radius} 0 ${largeArcFlag} 1 ${x2} ${y2}`;
                
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathData);
                path.setAttribute('stroke', item.color);
                path.setAttribute('stroke-width', '30');
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke-linecap', 'round');
                
                document.getElementById('donut-segments').appendChild(path);
                
                // Create legend item
                const segmentItem = document.createElement('div');
                segmentItem.className = 'segment-item';
                segmentItem.innerHTML = `
                    <div class="segment-color" style="background-color: ${item.color}"></div>
                    <div class="segment-label">${item.label}</div>
                    <div class="segment-value">${item.value}</div>
                `;
                segmentsContainer.appendChild(segmentItem);
                
                currentAngle += angle;
            });

            // Set center text
            document.getElementById('new-risk-center-text').textContent = '247';
        }

        // Add User Modal Function
        function showAddUserModal() {
            alert('Add User functionality - This would open a modal to add new users');
            // In a real implementation, this would show a modal form
        }

        // Download CSV Template Function
        function downloadCSVTemplate() {
            // Create CSV template content
            const csvContent = `Username,Email,Password,Birthday,Gender,Weight (kg),Height (cm),Barangay,Income,Swelling,Weight Loss,Dietary Diversity,Feeding Behavior,MUAC (cm),Physical Signs,Recent Illness,Eating Difficulty,Food Insecurity,Micronutrient Deficiency,Functional Decline,Allergies,Diet Preferences,Foods to Avoid
john_doe,john@example.com,password123,1990-01-01,male,70,175,Lamao,PHP 20,001–40,000/month (Middle),no,none,8,good,25,none,false,false,false,false,false,peanuts;dairy,vegetarian,pork
jane_smith,jane@example.com,password123,1985-05-15,female,60,165,Pilar,PHP 12,031–20,000/month (Low),no,<5%,6,moderate,22,none,false,false,false,false,false,eggs,vegan,shellfish`;

            // Create blob and download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'user_template.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Import CSV Modal Function
        function showCSVImportModal() {
            alert('Import CSV functionality - This would open a modal to import CSV files');
            // In a real implementation, this would show a modal with file upload
        }

        // Delete Users by Location Function
        function deleteUsersByLocation() {
            const location = prompt('Enter location to delete users from (or type "ALL" to delete all users):');
            if (location) {
                if (location.toUpperCase() === 'ALL') {
                    if (confirm('Are you sure you want to delete ALL users? This action cannot be undone!')) {
                        alert('Delete All Users functionality - This would delete all users from the database');
                        // In a real implementation, this would call an API to delete all users
                    }
                } else {
                    if (confirm(`Are you sure you want to delete all users from "${location}"? This action cannot be undone!`)) {
                        alert(`Delete by Location functionality - This would delete all users from ${location}`);
                        // In a real implementation, this would call an API to delete users by location
                    }
                }
            }
        }
    </script>
</body>
</html>
