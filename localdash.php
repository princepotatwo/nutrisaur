<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Debug: Check session status
echo "<!-- Debug: Session started -->";
echo "<!-- Debug: Session data: " . print_r($_SESSION, true) . " -->";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<!-- Debug: No user_id in session, redirecting to home.php -->";
    // Redirect to login page if not logged in
    header("Location: home.php");
    exit;
}

echo "<!-- Debug: User is logged in, user_id: " . $_SESSION['user_id'] . " -->";

// Get user info from session
$userId = $_SESSION['user_id'] ?? 'unknown';
$username = $_SESSION['username'] ?? 'User';
$email = $_SESSION['email'] ?? 'user@example.com';
$isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

echo "<!-- Debug: User info - userId: $userId, username: $username, email: $email -->";

// Connect to database
$host = "localhost";
$dbname = "nutrisaur_db";
$dbUsername = "root";
$dbPassword = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $dbUsername, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get user profile data from users and user_preferences tables
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
        // Handle case where user_id might not exist
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
    // Destroy the session
    session_unset();
    session_destroy();
    
    // Redirect to login page
    header("Location: home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSaur Dashboard</title>
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
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    transition: background-color 0.4s ease, color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
}

body {
    min-height: 200vh;
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
    background-color: var(--color-highlight);
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

.light-theme body {
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" opacity="0.1"><path d="M10,10 Q50,20 90,10 Q80,50 90,90 Q50,80 10,90 Q20,50 10,10 Z" fill="%2376BB43"/></svg>');
    background-size: 300px;
}

.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.light-theme header {
    background-color: var(--color-card);
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.logo {
    display: flex;
    align-items: center;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background-color: var(--color-highlight);
    border-radius: 8px;
    margin-right: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
}

.light-theme .logo-icon {
    color: white;
}

h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.light-theme h1 {
    color: var(--color-highlight);
}

/* Header user info styles */
header .user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.theme-toggle-btn {
    background: rgba(161, 180, 84, 0.1);
    border: 2px solid var(--color-highlight);
    color: var(--color-highlight);
    border-radius: 10px;
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    margin-right: 15px;
    font-weight: bold;
}

.light-theme .theme-toggle-btn {
    background: rgba(118, 187, 110, 0.1);
}

.theme-toggle-btn:hover {
    background: var(--color-highlight);
    color: var(--color-bg);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.3);
}

.theme-icon {
    font-size: 16px;
    transition: transform 0.3s ease;
}



.light-theme .theme-toggle-btn:hover {
    background: var(--color-highlight);
    color: var(--color-card);
}

header .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    margin-right: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
}

.light-theme header .user-avatar {
    background-color: var(--color-accent1);
    color: white;
    font-weight: bold;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 32px;
    margin-bottom: 15px;
    text-align: center;
    opacity: 0.8;
    transition: transform 0.3s ease;
}

.stat-card:hover .stat-icon {
    transform: scale(1.1);
    opacity: 1;
}

.light-theme .stat-card {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.stat-card h3 {
    font-size: 16px;
    margin-bottom: 10px;
    opacity: 0.95;
    color: var(--color-text);
    font-weight: 500;
}

.stat-value {
    font-size: 32px;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--color-highlight);
}

.light-theme .stat-value {
    color: #75aa50;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
    color: var(--color-text);
}

.light-theme .stat-label {
    opacity: 0.85;
}

.chart-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Improved chart styling */
.chart-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    min-height: 400px;
    max-height: 450px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.chart-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.1);
}

.chart-card h3 {
    font-size: 18px;
    margin-bottom: 10px;
    color: var(--color-text);
}

.light-theme .chart-card h3 {
    color: var(--color-accent3);
}

.chart-description {
    font-size: 13px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 15px;
    line-height: 1.4;
    font-style: italic;
}

.light-theme .chart-description {
    opacity: 0.7;
    color: var(--color-text);
}

/* Improved bar chart styling */
.bar {
    width: 8%;
    background-color: var(--color-accent2);
    border-radius: 8px 8px 0 0;
    position: relative;
    overflow: hidden;
    transition: height 1s cubic-bezier(0.25, 0.8, 0.25, 1), 
                background-color 0.3s ease,
                transform 0.2s ease;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.05));
    cursor: pointer;
}

.bar:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.1));
}

/* Improved line chart styling */
.line-path {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 3;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transition: stroke-dashoffset 2.5s ease;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

/* Performance optimizations */
.chart-card * {
    will-change: transform, opacity;
}

/* Loading state */
.chart-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

/* Chart Labels and Legends */
.chart-labels {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
    padding: 12px;
    background: var(--color-card);
    border-radius: 8px;
    border: 1px solid var(--color-accent3);
}

.label-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--color-text);
}

.label-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    display: inline-block;
}

/* Axis Labels */
.x-axis {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    padding: 0 4px;
}

.axis-label {
    font-size: 11px;
    color: var(--color-text);
    opacity: 0.7;
    text-align: center;
    flex: 1;
}

.y-axis-label {
    position: absolute;
    left: -30px;
    top: 50%;
    transform: rotate(-90deg) translateX(50%);
    font-size: 12px;
    color: var(--color-text);
    opacity: 0.8;
    white-space: nowrap;
}

/* Bar Chart Labels */
.bar-chart-container {
    position: relative;
    padding-left: 40px;
    padding-bottom: 30px;
}

.bar-chart {
    position: relative;
    height: 200px;
    margin-bottom: 8px;
}

/* Line Chart Labels */
.line-chart-container {
    position: relative;
    padding-left: 30px;
    padding-bottom: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.line-chart {
    width: 100%;
    height: 180px;
    margin-bottom: 5px;
    flex: 1;
}

.y-axis-label {
    position: absolute;
    left: -25px;
    top: 50%;
    transform: rotate(-90deg) translateX(50%);
    font-size: 11px;
    color: var(--color-text);
    opacity: 0.8;
    white-space: nowrap;
}

.donut-chart-container {
    position: relative;
    height: 250px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.donut-chart {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    position: relative;
    overflow: visible;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.15);
    filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.05));
    display: grid;
    place-items: center;
}

.light-theme .donut-chart {
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
}

.donut-chart::before {
    content: '';
    position: absolute;
    width: 60%;
    height: 60%;
    background-color: var(--color-card);
    border-radius: 50%;
    top: 20%;
    left: 20%;
    z-index: 2;
    box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.12);
}

.light-theme .donut-chart::before {
    box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.06);
}

.donut-chart-bg {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    z-index: 1;
}

.donut-center-text {
    position: relative;
    z-index: 10;
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
    text-align: center;
    line-height: 1;
    pointer-events: none;
    background-color: var(--color-card);
    padding: 8px 12px;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    width: auto;
    height: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 60px;
    min-height: 40px;
    box-sizing: border-box;
}

.segments {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 20px;
}

.segment {
    display: flex;
    align-items: center;
    margin: 5px 10px;
}

.color-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.bar-chart-container {
    position: relative;
    height: 220px;
    margin-top: 20px;
}

.bar-chart {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 100%;
    padding-bottom: 30px;
}

.bar {
    width: 8%;
    background-color: var(--color-accent2);
    border-radius: 8px 8px 0 0;
    position: relative;
    overflow: hidden;
    transition: height 1s cubic-bezier(0.25, 0.8, 0.25, 1);
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.05));
}

.light-theme .bar {
    background-color: var(--color-accent4);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
}

.bar-liquid {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: var(--color-highlight);
    transform: translateY(100%);
    transition: transform 1.5s cubic-bezier(0.25, 0.8, 0.25, 1);
    filter: blur(0.5px);
}

.bar-wave {
    position: absolute;
    width: 200%;
    height: 20px;
    left: -50%;
    border-radius: 40%;
    background: var(--color-accent2);
    animation: barWave 8s infinite linear;
    opacity: 0.8;
}

.light-theme .bar-wave {
    background: var(--color-accent1);
    opacity: 0.7;
}

@keyframes barWave {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(50%);
    }
}

.bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    color: var(--color-text);
}

.bar-label {
    position: absolute;
    bottom: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    white-space: nowrap;
    color: var(--color-text);
}

.light-theme .bar-label {
    font-weight: 600;
}

.x-axis {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: rgba(232, 240, 214, 0.3);
}

.light-theme .x-axis {
    background-color: rgba(49, 68, 30, 0.2);
}

.line-chart {
    position: relative;
    height: 100%;
}

.line-path {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 2.5;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transition: stroke-dashoffset 2.5s ease;
    filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.1));
}

.light-theme .line-path {
    stroke: var(--color-accent1);
}

.line-area {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    fill: url(#line-gradient);
    opacity: 0.4;
    clip-path: polygon(0 100%, 0 0, 0 0, 0 100%);
    transition: clip-path 2.5s ease;
}

.alert-list {
    list-style: none;
    max-height: 280px;
    overflow-y: auto;
    padding: 0;
    margin: 0;
}

.alert-item {
    background-color: rgba(42, 51, 38, 0.7);
    margin-bottom: 8px;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid var(--color-highlight);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
}

.light-theme .alert-item {
    background-color: var(--color-card);
    border-left: 4px solid var(--color-accent3);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.03);
}

.alert-item.danger {
    border-left-color: var(--color-danger);
}

.alert-item.warning {
    border-left-color: var(--color-warning);
}

.alert-content h4 {
    font-size: 14px;
    margin-bottom: 3px;
    color: var(--color-text);
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 200px;
}

.alert-content p {
    font-size: 12px;
    opacity: 0.7;
    color: var(--color-text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 180px;
}

.alert-time {
    font-size: 12px;
    opacity: 0.6;
    color: var(--color-text);
}

.alert-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
}

.badge-danger {
    background-color: var(--color-danger);
    color: white;
}

.badge-warning {
    background-color: var(--color-warning);
    color: #333;
}

.light-theme .badge-warning {
    color: white;
}

.badge-success {
    background-color: var(--color-highlight);
    color: #333;
}

.light-theme .badge-success {
    background-color: var(--color-accent3);
    color: white;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .new-donut-chart {
        width: 400px;
        height: 400px;
    }
    
    .donut-chart-container {
        height: 500px;
    }
    
    .chart-card {
        min-height: 600px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Adjustments for larger screens */
@media (min-width: 1200px) {
    .dashboard {
        max-width: calc(100% - 60px);
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .chart-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-card {
        min-height: 400px;
    }
    
    .donut-chart-container {
        height: 300px;
    }
    
    .donut-chart {
        width: 240px;
        height: 240px;
    }
    
    .bar-chart-container {
        height: 280px;
    }
    
    .line-chart-container {
        height: 250px;
    }
    
    .stat-card, .chart-card {
        padding: 20px;
    }
}

/* Improved navigation bar */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 320px;
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
    background-color: var(--color-highlight);
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

/* Light theme specific styles */
.light-theme .navbar {
    background-color: rgba(234, 240, 220, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 3px 0 20px rgba(0, 0, 0, 0.06);
}

.light-theme .navbar

/* Additional eye-comfort improvements */
.stat-card, .chart-card {
    border-radius: 20px; /* Slightly more rounded corners */
}

.light-theme .stat-value {
    color: #75aa50; /* Less intense highlight color */
}

.light-theme .chart-card,
.light-theme .stat-card {
    background-image: linear-gradient(to bottom right, rgba(255,255,255,0.05), transparent);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Add subtle grain texture to reduce eye strain from solid colors */
.light-theme:before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
    z-index: 1000;
    opacity: 0.06;
}

/* Update the responsive navbar styles */
@media (max-width: 768px) {
    .navbar {
        width: 80px;
        transform: translateX(0);
        transition: transform 0.3s ease, width 0.3s ease;
    }
    
    .navbar:hover {
        width: 320px;
    }
    
    .navbar-logo-text, .navbar span:not(.navbar-icon) {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .navbar:hover .navbar-logo-text, 
    .navbar:hover span:not(.navbar-icon) {
        opacity: 1;
    }
    
    body {
        padding-left: 100px;
    }
}

/* Add this media query for responsive adjustments */
@media (max-width: 768px) {
    .navbar a {
        padding: 12px 25px;  /* Slightly reduce vertical padding for mobile */
    }
    
    .navbar li {
        margin-bottom: 2px;  /* Further reduce spacing on mobile */
    }
}

/* Custom scrollbar - Add this to match USM.html */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #7cb342 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #7cb342 0%, #689f38 100%);
}

/* User section styles for other parts of the dashboard */
.user-section {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.user-section .user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #8bc34a;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 15px;
}

.user-section .user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 5px;
}

.user-email {
    font-size: 14px;
    color: #888;
}

.logout-btn {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.logout-btn:hover {
    background-color: #d32f2f;
}

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

/* Refreshing animations */
@keyframes moonOrbit {
    0% {
        transform: rotate(0deg) translateX(170px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: rotate(360deg) translateX(170px) rotate(-360deg);
        opacity: 0;
    }
}


.refresh-moon {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    background: var(--color-highlight);
    border-radius: 50%;
    transform-origin: center;
    animation: moonOrbit 2s ease-in-out;
    z-index: 10;
}

/* Hover effects for screening issues */
.screening-issues-hover {
    transition: all 0.3s ease;
}

.screening-issues-hover:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
}

.main-issue-hover {
    transition: all 0.3s ease;
}

.main-issue-hover:hover {
    background: rgba(161, 180, 84, 0.2) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.3);
}

.summary-item-hover {
    transition: all 0.3s ease;
}

.summary-item-hover:hover {
    background: rgba(161, 180, 84, 0.15) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.2);
    border-color: var(--color-highlight) !important;
}

/* MUAC Distribution Chart Styles */
.muac-chart-container {
    padding: 20px;
    height: 300px;
}

.muac-legend {
    display: flex;
    justify-content: space-around;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.muac-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--color-text);
}

.muac-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    border: 1px solid rgba(0,0,0,0.1);
}

.muac-bars {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 200px;
    padding: 0 20px;
}

.muac-bar {
    width: 60px;
    background: linear-gradient(to top, var(--color-highlight), var(--color-accent2));
    border-radius: 8px 8px 0 0;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.muac-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.muac-bar.normal { background: linear-gradient(to top, #4CAF50, #66BB6A); }
.muac-bar.mam { background: linear-gradient(to top, #FF9800, #FFB74D); }
.muac-bar.sam { background: linear-gradient(to top, #F44336, #EF5350); }

.muac-bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    font-weight: bold;
    color: var(--color-text);
}

.muac-bar-label {
    position: absolute;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    color: var(--color-text);
    text-align: center;
    white-space: nowrap;
}

/* WHZ Categories Chart Styles */
.whz-chart-container {
    padding: 20px;
    height: 300px;
}

.whz-bars {
    display: flex;
    justify-content: space-around;
    align-items: flex-end;
    height: 180px;
    margin-bottom: 40px;
}

.whz-bar {
    width: 50px;
    border-radius: 8px 8px 0 0;
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.whz-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.whz-bar.sam { background: linear-gradient(to top, #F44336, #EF5350); }
.whz-bar.mam { background: linear-gradient(to top, #FF9800, #FFB74D); }
.whz-bar.normal { background: linear-gradient(to top, #4CAF50, #66BB6A); }
.whz-bar.overweight { background: linear-gradient(to top, #2196F3, #42A5F5); }

.whz-bar-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    font-weight: bold;
    color: var(--color-text);
}

.whz-labels {
    display: flex;
    justify-content: space-around;
    text-align: center;
}

.whz-labels span {
    font-size: 11px;
    color: var(--color-text);
    line-height: 1.2;
}

/* Dietary Diversity Score Styles */
.dds-container {
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.dds-score-display {
    text-align: center;
    padding: 20px;
    background: var(--color-card);
    border-radius: 15px;
    border: 2px solid var(--color-highlight);
    min-width: 150px;
}

.dds-number {
    font-size: 36px;
    font-weight: bold;
    color: var(--color-highlight);
    margin-bottom: 5px;
}

.dds-label {
    font-size: 14px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 10px;
}

.dds-status {
    font-size: 16px;
    font-weight: bold;
    padding: 5px 15px;
    border-radius: 20px;
    color: white;
}

.dds-status.inadequate { background: #F44336; }
.dds-status.adequate { background: #4CAF50; }

.dds-food-groups {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    width: 100%;
    max-width: 300px;
}

.dds-food-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px;
    background: var(--color-card);
    border-radius: 8px;
    border: 1px solid var(--color-accent3);
    font-size: 12px;
    color: var(--color-text);
}

.dds-food-check {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
    font-weight: bold;
}

.dds-food-check.checked { background: #4CAF50; }
.dds-food-check.unchecked { background: #ccc; }

/* Geographic Distribution Styles */
.geo-chart-container {
    padding: 15px;
    height: 280px;
    overflow-y: auto;
    flex: 1;
}

.geo-bars {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.geo-bar-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px;
    background: var(--color-card);
    border-radius: 6px;
    border-left: 3px solid var(--color-highlight);
    transition: all 0.3s ease;
    cursor: pointer;
}

.geo-bar-item:hover {
    transform: translateX(3px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.geo-bar-name {
    flex: 1;
    font-size: 13px;
    color: var(--color-text);
    font-weight: 500;
}

.geo-bar-progress {
    flex: 2;
    height: 16px;
    background: rgba(161, 180, 84, 0.2);
    border-radius: 8px;
    overflow: hidden;
    position: relative;
}

.geo-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-highlight), var(--color-accent2));
    border-radius: 8px;
    transition: width 0.8s ease;
}

.geo-bar-percentage {
    font-size: 11px;
    color: var(--color-text);
    font-weight: bold;
    min-width: 35px;
    text-align: right;
}

/* Critical Alerts Styles */
.critical-alerts {
    max-height: 280px;
    overflow-y: auto;
    flex: 1;
}

.alert-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.alert-item {
    background-color: rgba(42, 51, 38, 0.7);
    margin-bottom: 8px;
    padding: 12px;
    border-radius: 8px;
    border-left: 3px solid var(--color-highlight);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.light-theme .alert-item {
    background-color: var(--color-card);
    border-left: 3px solid var(--color-accent3);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
}

.alert-content h4 {
    font-size: 14px;
    margin-bottom: 3px;
    color: var(--color-text);
    font-weight: 500;
}

.alert-content p {
    font-size: 12px;
    opacity: 0.7;
    color: var(--color-text);
}

.alert-time {
    font-size: 11px;
    opacity: 0.6;
    color: var(--color-text);
}

.alert-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 8px;
}

.badge-danger {
    background-color: var(--color-danger);
    color: white;
}

.badge-warning {
    background-color: var(--color-warning);
    color: #333;
}

.light-theme .badge-warning {
    color: white;
}

.badge-success {
    background-color: var(--color-highlight);
    color: #333;
}

.light-theme .badge-success {
    background-color: var(--color-accent3);
    color: white;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .new-donut-chart {
        width: 400px;
        height: 400px;
    }
    
    .donut-chart-container {
        height: 500px;
    }
    
    .chart-card {
        min-height: 600px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Adjustments for larger screens */
@media (min-width: 1200px) {
    .dashboard {
        max-width: calc(100% - 60px);
    }
    
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
    
    .chart-row {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-card {
        min-height: 400px;
    }
    
    .donut-chart-container {
        height: 300px;
    }
    
    .donut-chart {
        width: 240px;
        height: 240px;
    }
    
    .bar-chart-container {
        height: 280px;
    }
    
    .line-chart-container {
        height: 250px;
    }
    
    .stat-card, .chart-card {
        padding: 20px;
    }
}

/* Improved navigation bar */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    width: 320px;
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
    background-color: var(--color-highlight);
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

/* Light theme specific styles */
.light-theme .navbar {
    background-color: rgba(234, 240, 220, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 3px 0 20px rgba(0, 0, 0, 0.06);
}

.light-theme .navbar {
    background-color: rgba(234, 240, 220, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 3px 0 20px rgba(0, 0, 0, 0.06);
}

/* Additional eye-comfort improvements */
.stat-card, .chart-card {
    border-radius: 20px; /* Slightly more rounded corners */
}

.light-theme .stat-value {
    color: #75aa50; /* Less intense highlight color */
}

.light-theme .chart-card,
.light-theme .stat-card {
    background-image: linear-gradient(to bottom right, rgba(255,255,255,0.05), transparent);
    border: 1px solid rgba(255,255,255,0.1);
}

/* Add subtle grain texture to reduce eye strain from solid colors */
.light-theme:before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noiseFilter'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.65' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noiseFilter)' opacity='0.05'/%3E%3C/svg%3E");
    z-index: 1000;
    opacity: 0.06;
}

/* Update the responsive navbar styles */
@media (max-width: 768px) {
    .navbar {
        width: 80px;
        transform: translateX(0);
        transition: transform 0.3s ease, width 0.3s ease;
    }
    
    .navbar:hover {
        width: 320px;
    }
    
    .navbar-logo-text, .navbar span:not(.navbar-icon) {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .navbar:hover .navbar-logo-text, 
    .navbar:hover span:not(.navbar-icon) {
        opacity: 1;
    }
    
    body {
        padding-left: 100px;
    }
}

/* Add this media query for responsive adjustments */
@media (max-width: 768px) {
    .navbar a {
        padding: 12px 25px;  /* Slightly reduce vertical padding for mobile */
    }
    
    .navbar li {
        margin-bottom: 2px;  /* Further reduce spacing on mobile */
    }
}

/* Custom scrollbar - Add this to match USM.html */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: var(--background-color);
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, var(--secondary-color) 0%, #7cb342 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #7cb342 0%, #689f38 100%);
}

/* User section styles for other parts of the dashboard */
.user-section {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 5px;
}

.user-section .user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #8bc34a;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    margin-right: 15px;
}

.user-section .user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: bold;
    font-size: 18px;
    margin-bottom: 5px;
}

.user-email {
    font-size: 14px;
    color: #888;
}

.logout-btn {
    background-color: #f44336;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.logout-btn:hover {
    background-color: #d32f2f;
}

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

/* Refreshing animations */
@keyframes moonOrbit {
    0% {
        transform: rotate(0deg) translateX(170px) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: rotate(360deg) translateX(170px) rotate(-360deg);
        opacity: 0;
    }
}


.refresh-moon {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    background: var(--color-highlight);
    border-radius: 50%;
    transform-origin: center;
    animation: moonOrbit 2s ease-in-out;
    z-index: 10;
}

/* Hover effects for screening issues */
.screening-issues-hover {
    transition: all 0.3s ease;
}

.screening-issues-hover:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
}

.main-issue-hover {
    transition: all 0.3s ease;
}

.main-issue-hover:hover {
    background: rgba(161, 180, 84, 0.2) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.3);
}

.summary-item-hover {
    transition: all 0.3s ease;
}

.summary-item-hover:hover {
    background: rgba(161, 180, 84, 0.15) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.2);
    border-color: var(--color-highlight) !important;
}
/* New Analytics Styles */
.legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 10px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    flex-shrink: 0;
}

.legend-label {
    flex: 1;
    color: var(--color-text);
}

.legend-value {
    color: var(--color-text);
    opacity: 0.8;
    font-weight: 500;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    padding: 10px;
}

.summary-item {
    background: rgba(161, 180, 84, 0.1);
    border: 1px solid var(--color-highlight);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
}

.summary-item h4 {
    margin: 0 0 8px 0;
    color: var(--color-highlight);
    font-size: 14px;
    font-weight: 600;
}

.summary-item p {
    margin: 0;
    color: var(--color-text);
    font-size: 16px;
    font-weight: 700;
}

.chart-card.full-width {
    grid-column: 1 / -1;
}

.pie-chart-container {
    display: flex;
    align-items: center;
    gap: 25px;
    padding: 25px;
    background: rgba(161, 180, 84, 0.03);
    border-radius: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.pie-chart {
    flex-shrink: 0;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    overflow: visible;
    transition: transform 0.3s ease;
}

/* Add smooth transitions for donut chart updates */
.donut-chart-bg, .pie-chart-bg {
    transition: background 0.5s ease-in-out, opacity 0.3s ease-in-out;
}

.segment {
    transition: opacity 0.3s ease-in-out;
}

/* Smooth transitions for center text updates */
#risk-center-text {
    transition: transform 0.15s ease-in-out, color 0.15s ease-in-out;
}

.pie-chart:hover {
    transform: scale(1.02);
}

.pie-center-value {
    font-size: 24px;
    font-weight: 800;
    fill: var(--color-highlight);
    opacity: 1;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    transition: transform 0.15s ease-in-out, fill 0.15s ease-in-out;
}

.light-theme .pie-chart-container {
    background: rgba(118, 187, 110, 0.05);
    border-color: rgba(118, 187, 110, 0.15);
}







.screening-chart {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(161, 180, 84, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
    transition: all 0.3s ease;
}

.screening-chart:hover {
    background: rgba(161, 180, 84, 0.08);
    border-color: rgba(161, 180, 84, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.15);
}











/* Line Chart Styles for WHZ and MUAC */
.line-path {
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 3px;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
    transition: all 0.3s ease;
}

.line-area {
    fill: url(#muac-line-gradient);
    opacity: 0.6;
    transition: all 0.3s ease;
}

#whz-line-area {
    fill: url(#whz-line-gradient);
}

.light-theme .line-path {
    stroke: var(--color-highlight);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.15));
}

.line-path:hover {
    stroke-width: 4px;
    filter: brightness(1.2) drop-shadow(0 4px 8px rgba(161, 180, 84, 0.4));
}

.line-area:hover {
    opacity: 0.8;
}

@media (max-width: 768px) {
    .pie-chart-container {
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }
    
    .pie-chart {
        width: 150px;
        height: 150px;
    }
    
    .summary-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }
    
    .program-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .create-this-program-btn {
        margin-left: 0;
        align-self: flex-start;
    }
}

/* Create Program Button Styles */
.create-program-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1));
    color: var(--color-bg);
    border: none;
    padding: 12px 20px;
    border-radius: 25px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
    text-decoration: none;
}

.create-program-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.4);
    background: linear-gradient(135deg, var(--color-accent1), var(--color-highlight));
}

.create-program-btn:active {
    transform: translateY(0);
}

.btn-icon {
    font-size: 16px;
    font-weight: bold;
}

.btn-text {
    white-space: nowrap;
}

.light-theme .create-program-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
    color: white;
    box-shadow: 0 4px 15px rgba(118, 187, 110, 0.3);
}

.light-theme .create-program-btn:hover {
    box-shadow: 0 6px 20px rgba(118, 187, 110, 0.4);
    background: linear-gradient(135deg, var(--color-accent3), var(--color-highlight));
}

/* Create This Program Button Styles */
.create-this-program-btn {
    background: linear-gradient(135deg, var(--color-accent2), var(--color-accent3));
    color: var(--color-bg);
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(181, 200, 141, 0.3);
    margin-left: 10px;
    white-space: nowrap;
}

.create-this-program-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(181, 200, 141, 0.4);
    background: linear-gradient(135deg, var(--color-accent3), var(--color-accent2));
}

.create-this-program-btn:active {
    transform: translateY(0);
}

.light-theme .create-this-program-btn {
    background: linear-gradient(135deg, var(--color-accent4), var(--color-accent2));
    color: var(--color-text);
    box-shadow: 0 2px 8px rgba(215, 227, 160, 0.3);
}

.light-theme .create-this-program-btn:hover {
    box-shadow: 0 4px 12px rgba(215, 227, 160, 0.4);
    background: linear-gradient(135deg, var(--color-accent2), var(--color-accent4));
}

/* Community Hub Styles */
.filter-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    background-color: var(--color-card);
    padding: 15px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-group label {
    color: var(--color-highlight);
    font-weight: bold;
}

.filter-group select {
    background-color: var(--color-accent3);
    color: var(--color-text);
    border: 1px solid var(--color-accent3);
    padding: 8px 12px;
    border-radius: 6px;
    min-width: 200px;
}

.time-frame-buttons {
    display: flex;
    gap: 8px;
}

.time-btn {
    background-color: var(--color-accent3);
    color: var(--color-text);
    border: 1px solid var(--color-accent3);
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.time-btn.active {
    background-color: var(--color-highlight);
    color: var(--color-bg);
    border-color: var(--color-highlight);
    font-weight: bold;
}

.time-btn:hover {
    background-color: var(--color-accent2);
}

.card-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.card {
    background-color: var(--color-card);
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0px 4px 15px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border-top: 3px solid var(--color-highlight);
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0px 8px 20px rgba(154, 230, 110, 0.4);
}

.card h2 {
    font-size: 18px;
    margin-bottom: 15px;
    color: var(--color-text);
    font-weight: 600;
}

.metric-value {
    font-size: 32px;
    color: var(--color-highlight);
    margin-bottom: 8px;
    font-weight: 700;
}

.metric-change {
    font-size: 14px;
    color: var(--color-highlight);
    margin-bottom: 10px;
    font-weight: 500;
}

        .metric-note {
            font-size: 12px;
            color: var(--color-text);
            opacity: 0.7;
            line-height: 1.4;
        }

        /* Custom Select Styles */
        .custom-select-container {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .select-header {
            background-color: var(--color-card);
            border: 2px solid var(--color-border);
            border-radius: 8px;
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .select-header:hover {
            border-color: var(--color-highlight);
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
        }

        .dropdown-arrow.active {
            transform: rotate(180deg);
        }

        .dropdown-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--color-card);
            border: 2px solid var(--color-border);
            border-radius: 8px;
            margin-top: 4px;
            max-height: 400px;
            overflow: hidden;
            z-index: 1000;
            display: none;
        }

        .dropdown-content.active {
            display: block;
        }

        .search-container {
            padding: 16px;
            border-bottom: 1px solid var(--color-border);
        }

        .search-container input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            background-color: var(--color-background);
            color: var(--color-text);
            font-size: 14px;
        }

        .search-container input:focus {
            outline: none;
            border-color: var(--color-highlight);
        }

        .options-container {
            max-height: 300px;
            overflow-y: auto;
        }

        .option-group {
            border-bottom: 1px solid var(--color-border);
        }

        .option-header {
            padding: 12px 16px 8px;
            font-weight: 600;
            color: var(--color-highlight);
            background-color: rgba(154, 230, 110, 0.1);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .option-item {
            padding: 10px 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-size: 14px;
        }

        .option-item:hover {
            background-color: rgba(154, 230, 110, 0.1);
        }

        .option-item.selected {
            background-color: var(--color-highlight);
            color: var(--color-background);
        }

.program-cards-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 10px;
}

.program-card {
    background-color: rgba(23, 35, 23, 0.7);
    padding: 20px;
    border-radius: 12px;
    display: flex;
    gap: 15px;
    transition: all 0.3s ease;
    border-left: 4px solid var(--color-highlight);
    position: relative;
    overflow: hidden;
}

.program-card:hover {
    transform: translateY(-3px);
    box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.2);
    background-color: rgba(34, 53, 34, 0.7);
}

.program-icon {
    font-size: 24px;
    background-color: rgba(161, 180, 84, 0.15);
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.program-card:hover .program-icon {
    transform: scale(1.1) rotate(5deg);
}

.program-content {
    flex: 1;
}

.program-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 8px;
}

.program-description {
    font-size: 14px;
    color: var(--color-text);
    opacity: 0.8;
    margin-bottom: 15px;
    line-height: 1.4;
}

.program-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}

.priority-tag {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-top: 10px;
    font-weight: bold;
}

.priority-high {
    background-color: var(--color-highlight);
    color: var(--color-bg);
}

.priority-immediate {
    background-color: #dc3545;
    color: #fff;
}

.impact-estimate {
    font-size: 12px;
    color: var(--color-highlight);
}

.progress-container {
    display: flex;
    flex-direction: column;
    gap: 18px;
    margin-top: 15px;
    padding: 5px;
}

.progress-item {
    margin-bottom: 5px;
    transition: transform 0.2s ease;
}

.progress-item:hover {
    transform: translateX(5px);
}

.progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 500;
}

.progress-label span:last-child {
    color: var(--color-highlight);
    font-weight: 600;
}

.progress-bar {
    width: 100%;
    height: 10px;
    background-color: rgba(34, 53, 34, 0.7);
    border-radius: 6px;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
}

.progress-fill {
    height: 100%;
    border-radius: 6px;
    transition: width 0.5s ease;
    position: relative;
}

.progress-high { background-color: #FF6B6B; }
.progress-medium { background-color: #FFC107; }
.progress-low { background-color: #666; }
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
                <li><a href="dash.php" class="active"><span class="navbar-icon">📊</span><span>Dashboard</span></a></li>
                <li><a href="USM.php"><span class="navbar-icon">👥</span><span>User Management</span></a></li>
                <li><a href="NR.php"><span class="navbar-icon">🥗</span><span>Nutritional Analysis</span></a></li>
                <li><a href="event.php"><span class="navbar-icon">⚠️</span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="community_hub.php"><span class="navbar-icon">🏘️</span><span>Community Nutrition Hub</span></a></li>
                <li><a href="FPM.php"><span class="navbar-icon">🍎</span><span>Food Availability</span></a></li>
                <li><a href="AI.php"><span class="navbar-icon">🤖</span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings.php"><span class="navbar-icon">⚙️</span><span>Settings & Admin</span></a></li>
                <li><a href="logout.php" style="color: #ff5252;"><span class="navbar-icon">🚪</span><span>Logout</span></a></li>
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
                <h1>Dashboard</h1>
            </div>
            <div class="user-info">
                <button id="theme-toggle" class="theme-toggle-btn" title="Toggle theme">
                    <span class="theme-icon">🌙</span>
                </button>
                <div class="user-avatar"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </header>

        <!-- Community Filter Section - Moved to Top -->
        <div class="filter-section">
            <div class="filter-group">
                <label>Select Barangay:</label>
                <div class="custom-select-container">
                    <div class="select-header" onclick="toggleDropdown()">
                        <span id="selected-option">All Barangays</span>
                        <span class="dropdown-arrow">▼</span>
                    </div>
                    <div class="dropdown-content" id="dropdown-content">
                        <div class="search-container">
                            <input type="text" id="search-input" placeholder="Search barangay or municipality..." onkeyup="filterOptions()">
                        </div>
                        <div class="options-container">
                            <!-- Municipality Options -->
                            <div class="option-group">
                                <div class="option-header">Municipalities</div>
                                <div class="option-item" data-value="">All Barangays</div>
                                <div class="option-item" data-value="MUNICIPALITY_ABUCAY">ABUCAY (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_BAGAC">BAGAC (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_BALANGA">CITY OF BALANGA (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_DINALUPIHAN">DINALUPIHAN (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_HERMOSA">HERMOSA (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_LIMAY">LIMAY (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_MARIVELES">MARIVELES (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_MORONG">MORONG (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_ORANI">ORANI (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_ORION">ORION (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_PILAR">PILAR (All Barangays)</div>
                                <div class="option-item" data-value="MUNICIPALITY_SAMAL">SAMAL (All Barangays)</div>
                                                        </div>
                            
                            <!-- Individual Barangays by Municipality -->
                            <div class="option-group">
                                <div class="option-header">ABUCAY</div>
                                <div class="option-item" data-value="Bangkal">Bangkal</div>
                                <div class="option-item" data-value="Calaylayan (Pob.)">Calaylayan (Pob.)</div>
                                <div class="option-item" data-value="Capitangan">Capitangan</div>
                                <div class="option-item" data-value="Gabon">Gabon</div>
                                <div class="option-item" data-value="Laon (Pob.)">Laon (Pob.)</div>
                                <div class="option-item" data-value="Mabatang">Mabatang</div>
                                <div class="option-item" data-value="Omboy">Omboy</div>
                                <div class="option-item" data-value="Salian">Salian</div>
                                <div class="option-item" data-value="Wawa (Pob.)">Wawa (Pob.)</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">BAGAC</div>
                                <div class="option-item" data-value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</div>
                                <div class="option-item" data-value="Banawang">Banawang</div>
                                <div class="option-item" data-value="Binuangan">Binuangan</div>
                                <div class="option-item" data-value="Binukawan">Binukawan</div>
                                <div class="option-item" data-value="Ibaba">Ibaba</div>
                                <div class="option-item" data-value="Ibis">Ibis</div>
                                <div class="option-item" data-value="Pag-asa (Wawa-Sibacan)">Pag-asa (Wawa-Sibacan)</div>
                                <div class="option-item" data-value="Parang">Parang</div>
                                <div class="option-item" data-value="Paysawan">Paysawan</div>
                                <div class="option-item" data-value="Quinawan">Quinawan</div>
                                <div class="option-item" data-value="San Antonio">San Antonio</div>
                                <div class="option-item" data-value="Saysain">Saysain</div>
                                <div class="option-item" data-value="Tabing-Ilog (Pob.)">Tabing-Ilog (Pob.)</div>
                                <div class="option-item" data-value="Atilano L. Ricardo">Atilano L. Ricardo</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">CITY OF BALANGA</div>
                                <div class="option-item" data-value="Bagumbayan">Bagumbayan</div>
                                <div class="option-item" data-value="Cabog-Cabog">Cabog-Cabog</div>
                                <div class="option-item" data-value="Munting Batangas (Cadre)">Munting Batangas (Cadre)</div>
                                <div class="option-item" data-value="Cataning">Cataning</div>
                                <div class="option-item" data-value="Central">Central</div>
                                <div class="option-item" data-value="Cupang Proper">Cupang Proper</div>
                                <div class="option-item" data-value="Cupang West">Cupang West</div>
                                <div class="option-item" data-value="Dangcol (Bernabe)">Dangcol (Bernabe)</div>
                                <div class="option-item" data-value="Ibayo">Ibayo</div>
                                <div class="option-item" data-value="Malabia">Malabia</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Pto. Rivas Ibaba">Pto. Rivas Ibaba</div>
                                <div class="option-item" data-value="Pto. Rivas Itaas">Pto. Rivas Itaas</div>
                                <div class="option-item" data-value="San Jose">San Jose</div>
                                <div class="option-item" data-value="Sibacan">Sibacan</div>
                                <div class="option-item" data-value="Camacho">Camacho</div>
                                <div class="option-item" data-value="Talisay">Talisay</div>
                                <div class="option-item" data-value="Tanato">Tanato</div>
                                <div class="option-item" data-value="Tenejero">Tenejero</div>
                                <div class="option-item" data-value="Tortugas">Tortugas</div>
                                <div class="option-item" data-value="Tuyo">Tuyo</div>
                                <div class="option-item" data-value="Bagong Silang">Bagong Silang</div>
                                <div class="option-item" data-value="Cupang North">Cupang North</div>
                                <div class="option-item" data-value="Doña Francisca">Doña Francisca</div>
                                <div class="option-item" data-value="Lote">Lote</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">DINALUPIHAN</div>
                                <div class="option-item" data-value="Bangal">Bangal</div>
                                <div class="option-item" data-value="Bonifacio (Pob.)">Bonifacio (Pob.)</div>
                                <div class="option-item" data-value="Burgos (Pob.)">Burgos (Pob.)</div>
                                <div class="option-item" data-value="Colo">Colo</div>
                                <div class="option-item" data-value="Daang Bago">Daang Bago</div>
                                <div class="option-item" data-value="Dalao">Dalao</div>
                                <div class="option-item" data-value="Del Pilar (Pob.)">Del Pilar (Pob.)</div>
                                <div class="option-item" data-value="Gen. Luna (Pob.)">Gen. Luna (Pob.)</div>
                                <div class="option-item" data-value="Gomez (Pob.)">Gomez (Pob.)</div>
                                <div class="option-item" data-value="Happy Valley">Happy Valley</div>
                                <div class="option-item" data-value="Kataasan">Kataasan</div>
                                <div class="option-item" data-value="Layac">Layac</div>
                                <div class="option-item" data-value="Luacan">Luacan</div>
                                <div class="option-item" data-value="Mabini Proper (Pob.)">Mabini Proper (Pob.)</div>
                                <div class="option-item" data-value="Mabini Ext. (Pob.)">Mabini Ext. (Pob.)</div>
                                <div class="option-item" data-value="Magsaysay">Magsaysay</div>
                                <div class="option-item" data-value="Naparing">Naparing</div>
                                <div class="option-item" data-value="New San Jose">New San Jose</div>
                                <div class="option-item" data-value="Old San Jose">Old San Jose</div>
                                <div class="option-item" data-value="Padre Dandan (Pob.)">Padre Dandan (Pob.)</div>
                                <div class="option-item" data-value="Pag-asa">Pag-asa</div>
                                <div class="option-item" data-value="Pagalanggang">Pagalanggang</div>
                                <div class="option-item" data-value="Pinulot">Pinulot</div>
                                <div class="option-item" data-value="Pita">Pita</div>
                                <div class="option-item" data-value="Rizal (Pob.)">Rizal (Pob.)</div>
                                <div class="option-item" data-value="Roosevelt">Roosevelt</div>
                                <div class="option-item" data-value="Roxas (Pob.)">Roxas (Pob.)</div>
                                <div class="option-item" data-value="Saguing">Saguing</div>
                                <div class="option-item" data-value="San Benito">San Benito</div>
                                <div class="option-item" data-value="San Isidro (Pob.)">San Isidro (Pob.)</div>
                                <div class="option-item" data-value="San Pablo (Bulate)">San Pablo (Bulate)</div>
                                <div class="option-item" data-value="San Ramon">San Ramon</div>
                                <div class="option-item" data-value="San Simon">San Simon</div>
                                <div class="option-item" data-value="Santo Niño">Santo Niño</div>
                                <div class="option-item" data-value="Sapang Balas">Sapang Balas</div>
                                <div class="option-item" data-value="Santa Isabel (Tabacan)">Santa Isabel (Tabacan)</div>
                                <div class="option-item" data-value="Torres Bugauen (Pob.)">Torres Bugauen (Pob.)</div>
                                <div class="option-item" data-value="Tucop">Tucop</div>
                                <div class="option-item" data-value="Zamora (Pob.)">Zamora (Pob.)</div>
                                <div class="option-item" data-value="Aquino">Aquino</div>
                                <div class="option-item" data-value="Bayan-bayanan">Bayan-bayanan</div>
                                <div class="option-item" data-value="Maligaya">Maligaya</div>
                                <div class="option-item" data-value="Payangan">Payangan</div>
                                <div class="option-item" data-value="Pentor">Pentor</div>
                                <div class="option-item" data-value="Tubo-tubo">Tubo-tubo</div>
                                <div class="option-item" data-value="Jose C. Payumo, Jr.">Jose C. Payumo, Jr.</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">HERMOSA</div>
                                <div class="option-item" data-value="A. Rivera (Pob.)">A. Rivera (Pob.)</div>
                                <div class="option-item" data-value="Almacen">Almacen</div>
                                <div class="option-item" data-value="Bacong">Bacong</div>
                                <div class="option-item" data-value="Balsic">Balsic</div>
                                <div class="option-item" data-value="Bamban">Bamban</div>
                                <div class="option-item" data-value="Burgos-Soliman (Pob.)">Burgos-Soliman (Pob.)</div>
                                <div class="option-item" data-value="Cataning (Pob.)">Cataning (Pob.)</div>
                                <div class="option-item" data-value="Culis">Culis</div>
                                <div class="option-item" data-value="Daungan (Pob.)">Daungan (Pob.)</div>
                                <div class="option-item" data-value="Mabiga">Mabiga</div>
                                <div class="option-item" data-value="Mabuco">Mabuco</div>
                                <div class="option-item" data-value="Maite">Maite</div>
                                <div class="option-item" data-value="Mambog - Mandama">Mambog - Mandama</div>
                                <div class="option-item" data-value="Palihan">Palihan</div>
                                <div class="option-item" data-value="Pandatung">Pandatung</div>
                                <div class="option-item" data-value="Pulo">Pulo</div>
                                <div class="option-item" data-value="Saba">Saba</div>
                                <div class="option-item" data-value="San Pedro (Pob.)">San Pedro (Pob.)</div>
                                <div class="option-item" data-value="Santo Cristo (Pob.)">Santo Cristo (Pob.)</div>
                                <div class="option-item" data-value="Sumalo">Sumalo</div>
                                <div class="option-item" data-value="Tipo">Tipo</div>
                                <div class="option-item" data-value="Judge Roman Cruz Sr. (Mandama)">Judge Roman Cruz Sr. (Mandama)</div>
                                <div class="option-item" data-value="Sacrifice Valley">Sacrifice Valley</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">LIMAY</div>
                                <div class="option-item" data-value="Alangan">Alangan</div>
                                <div class="option-item" data-value="Kitang I">Kitang I</div>
                                <div class="option-item" data-value="Kitang 2 & Luz">Kitang 2 & Luz</div>
                                <div class="option-item" data-value="Lamao">Lamao</div>
                                <div class="option-item" data-value="Landing">Landing</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Reformista">Reformista</div>
                                <div class="option-item" data-value="Townsite">Townsite</div>
                                <div class="option-item" data-value="Wawa">Wawa</div>
                                <div class="option-item" data-value="Duale">Duale</div>
                                <div class="option-item" data-value="San Francisco de Asis">San Francisco de Asis</div>
                                <div class="option-item" data-value="St. Francis II">St. Francis II</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">MARIVELES</div>
                                <div class="option-item" data-value="Alas-asin">Alas-asin</div>
                                <div class="option-item" data-value="Alion">Alion</div>
                                <div class="option-item" data-value="Batangas II">Batangas II</div>
                                <div class="option-item" data-value="Cabcaben">Cabcaben</div>
                                <div class="option-item" data-value="Lucanin">Lucanin</div>
                                <div class="option-item" data-value="Baseco Country (Nassco)">Baseco Country (Nassco)</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="San Carlos">San Carlos</div>
                                <div class="option-item" data-value="San Isidro">San Isidro</div>
                                <div class="option-item" data-value="Sisiman">Sisiman</div>
                                <div class="option-item" data-value="Balon-Anito">Balon-Anito</div>
                                <div class="option-item" data-value="Biaan">Biaan</div>
                                <div class="option-item" data-value="Camaya">Camaya</div>
                                <div class="option-item" data-value="Ipag">Ipag</div>
                                <div class="option-item" data-value="Malaya">Malaya</div>
                                <div class="option-item" data-value="Maligaya">Maligaya</div>
                                <div class="option-item" data-value="Mt. View">Mt. View</div>
                                <div class="option-item" data-value="Townsite">Townsite</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">MORONG</div>
                                <div class="option-item" data-value="Binaritan">Binaritan</div>
                                <div class="option-item" data-value="Mabayo">Mabayo</div>
                                <div class="option-item" data-value="Nagbalayong">Nagbalayong</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Sabang">Sabang</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">ORANI</div>
                                <div class="option-item" data-value="Bagong Paraiso (Pob.)">Bagong Paraiso (Pob.)</div>
                                <div class="option-item" data-value="Balut (Pob.)">Balut (Pob.)</div>
                                <div class="option-item" data-value="Bayan (Pob.)">Bayan (Pob.)</div>
                                <div class="option-item" data-value="Calero (Pob.)">Calero (Pob.)</div>
                                <div class="option-item" data-value="Paking-Carbonero (Pob.)">Paking-Carbonero (Pob.)</div>
                                <div class="option-item" data-value="Centro II (Pob.)">Centro II (Pob.)</div>
                                <div class="option-item" data-value="Dona">Dona</div>
                                <div class="option-item" data-value="Kaparangan">Kaparangan</div>
                                <div class="option-item" data-value="Masantol">Masantol</div>
                                <div class="option-item" data-value="Mulawin">Mulawin</div>
                                <div class="option-item" data-value="Pag-asa">Pag-asa</div>
                                <div class="option-item" data-value="Palihan (Pob.)">Palihan (Pob.)</div>
                                <div class="option-item" data-value="Pantalan Bago (Pob.)">Pantalan Bago (Pob.)</div>
                                <div class="option-item" data-value="Pantalan Luma (Pob.)">Pantalan Luma (Pob.)</div>
                                <div class="option-item" data-value="Parang Parang (Pob.)">Parang Parang (Pob.)</div>
                                <div class="option-item" data-value="Centro I (Pob.)">Centro I (Pob.)</div>
                                <div class="option-item" data-value="Sibul">Sibul</div>
                                <div class="option-item" data-value="Silahis">Silahis</div>
                                <div class="option-item" data-value="Tala">Tala</div>
                                <div class="option-item" data-value="Talimundoc">Talimundoc</div>
                                <div class="option-item" data-value="Tapulao">Tapulao</div>
                                <div class="option-item" data-value="Tenejero (Pob.)">Tenejero (Pob.)</div>
                                <div class="option-item" data-value="Tugatog">Tugatog</div>
                                <div class="option-item" data-value="Wawa (Pob.)">Wawa (Pob.)</div>
                                <div class="option-item" data-value="Apollo">Apollo</div>
                                <div class="option-item" data-value="Kabalutan">Kabalutan</div>
                                <div class="option-item" data-value="Maria Fe">Maria Fe</div>
                                <div class="option-item" data-value="Puksuan">Puksuan</div>
                                <div class="option-item" data-value="Tagumpay">Tagumpay</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">ORION</div>
                                <div class="option-item" data-value="Arellano (Pob.)">Arellano (Pob.)</div>
                                <div class="option-item" data-value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</div>
                                <div class="option-item" data-value="Balagtas (Pob.)">Balagtas (Pob.)</div>
                                <div class="option-item" data-value="Balut (Pob.)">Balut (Pob.)</div>
                                <div class="option-item" data-value="Bantan">Bantan</div>
                                <div class="option-item" data-value="Bilolo">Bilolo</div>
                                <div class="option-item" data-value="Calungusan">Calungusan</div>
                                <div class="option-item" data-value="Camachile">Camachile</div>
                                <div class="option-item" data-value="Daang Bago (Pob.)">Daang Bago (Pob.)</div>
                                <div class="option-item" data-value="Daang Bilolo (Pob.)">Daang Bilolo (Pob.)</div>
                                <div class="option-item" data-value="Daang Pare">Daang Pare</div>
                                <div class="option-item" data-value="General Lim (Kaput)">General Lim (Kaput)</div>
                                <div class="option-item" data-value="Kapunitan">Kapunitan</div>
                                <div class="option-item" data-value="Lati (Pob.)">Lati (Pob.)</div>
                                <div class="option-item" data-value="Lusungan (Pob.)">Lusungan (Pob.)</div>
                                <div class="option-item" data-value="Puting Buhangin">Puting Buhangin</div>
                                <div class="option-item" data-value="Sabatan">Sabatan</div>
                                <div class="option-item" data-value="San Vicente (Pob.)">San Vicente (Pob.)</div>
                                <div class="option-item" data-value="Santo Domingo">Santo Domingo</div>
                                <div class="option-item" data-value="Villa Angeles (Pob.)">Villa Angeles (Pob.)</div>
                                <div class="option-item" data-value="Wakas (Pob.)">Wakas (Pob.)</div>
                                <div class="option-item" data-value="Wawa (Pob.)">Wawa (Pob.)</div>
                                <div class="option-item" data-value="Santa Elena">Santa Elena</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">PILAR</div>
                                <div class="option-item" data-value="Ala-uli">Ala-uli</div>
                                <div class="option-item" data-value="Bagumbayan">Bagumbayan</div>
                                <div class="option-item" data-value="Balut I">Balut I</div>
                                <div class="option-item" data-value="Balut II">Balut II</div>
                                <div class="option-item" data-value="Bantan Munti">Bantan Munti</div>
                                <div class="option-item" data-value="Burgos">Burgos</div>
                                <div class="option-item" data-value="Del Rosario (Pob.)">Del Rosario (Pob.)</div>
                                <div class="option-item" data-value="Diwa">Diwa</div>
                                <div class="option-item" data-value="Landing">Landing</div>
                                <div class="option-item" data-value="Liyang">Liyang</div>
                                <div class="option-item" data-value="Nagwaling">Nagwaling</div>
                                <div class="option-item" data-value="Panilao">Panilao</div>
                                <div class="option-item" data-value="Pantingan">Pantingan</div>
                                <div class="option-item" data-value="Poblacion">Poblacion</div>
                                <div class="option-item" data-value="Rizal">Rizal</div>
                                <div class="option-item" data-value="Santa Rosa">Santa Rosa</div>
                                <div class="option-item" data-value="Wakas North">Wakas North</div>
                                <div class="option-item" data-value="Wakas South">Wakas South</div>
                                <div class="option-item" data-value="Wawa">Wawa</div>
                            </div>
                            <div class="option-group">
                                <div class="option-header">SAMAL</div>
                                <div class="option-item" data-value="East Calaguiman (Pob.)">East Calaguiman (Pob.)</div>
                                <div class="option-item" data-value="East Daang Bago (Pob.)">East Daang Bago (Pob.)</div>
                                <div class="option-item" data-value="Ibaba (Pob.)">Ibaba (Pob.)</div>
                                <div class="option-item" data-value="Imelda">Imelda</div>
                                <div class="option-item" data-value="Lalawigan">Lalawigan</div>
                                <div class="option-item" data-value="Palili">Palili</div>
                                <div class="option-item" data-value="San Juan (Pob.)">San Juan (Pob.)</div>
                                <div class="option-item" data-value="San Roque (Pob.)">San Roque (Pob.)</div>
                                <div class="option-item" data-value="Santa Lucia">Santa Lucia</div>
                                <div class="option-item" data-value="Sapa">Sapa</div>
                                <div class="option-item" data-value="Tabing Ilog">Tabing Ilog</div>
                                <div class="option-item" data-value="Gugo">Gugo</div>
                                <div class="option-item" data-value="West Calaguiman (Pob.)">West Calaguiman (Pob.)</div>
                                <div class="option-item" data-value="West Daang Bago (Pob.)">West Daang Bago (Pob.)</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="filter-group">
                <label>Time Frame:</label>
                <div class="time-frame-buttons">
                    <button class="time-btn active">1 Day</button>
                    <button class="time-btn">1 Week</button>
                    <button class="time-btn">1 Month</button>
                    <button class="time-btn">3 Months</button>
                    <button class="time-btn">1 Year</button>
                </div>
            </div>
        </div>

        <!-- Community Metrics Cards - Moved to Top -->
        <div class="card-container" style="gap: 20px;">
            <div class="card">
                <h2>Total Screened</h2>
                <div class="metric-value" id="community-total-screened">-</div>
                <div class="metric-change" id="community-screened-change">Loading...</div>
                <div class="metric-note">Children & adults screened</div>
            </div>
            <div class="card">
                <h2>High Risk Cases</h2>
                <div class="metric-value" id="community-high-risk">-</div>
                <div class="metric-change" id="community-risk-change">Loading...</div>
                <div class="metric-note">Risk score ≥30 (WHO standard)</div>
            </div>
            <div class="card">
                <h2>SAM Cases</h2>
                <div class="metric-value" id="community-sam-cases">-</div>
                <div class="metric-change" id="community-sam-change">Loading...</div>
                <div class="metric-note">Severe Acute Malnutrition (WHZ < -3)</div>
            </div>
            <div class="card">
                <h2>Programs in Barangay</h2>
                <div class="metric-value" id="programs-in-barangay">-</div>
                <div class="metric-change" id="programs-change">Loading...</div>
                <div class="metric-note">Active programs in selected area</div>
            </div>
        </div>



        <div class="chart-row">
            <div class="chart-card">
                <h3>Malnutrition Risk Levels</h3>
                <p class="chart-description">Distribution of children by malnutrition risk severity based on screening assessments. Higher risk levels indicate greater nutritional intervention needs.</p>
                <div class="donut-chart-container">
                    <div class="donut-chart">
                        <div class="donut-chart-bg" id="risk-chart-bg"></div>
                        <div class="donut-center-text" id="risk-center-text">0%</div>
                    </div>
                </div>
                <div class="segments" id="risk-segments"></div>
            </div>
            
            <div class="chart-card">
                <h3>Critical Alerts</h3>
                <p class="chart-description">Priority cases requiring immediate medical attention based on clinical indicators and screening results.</p>
                <ul class="alert-list" id="critical-alerts">
                    <!-- Critical alerts will be added dynamically -->
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
                <h3>MUAC Distribution</h3>
                <p class="chart-description">Mid-Upper Arm Circumference distribution for malnutrition screening and classification.</p>
                <div class="line-chart-container muac-chart-container">
                    <div class="y-axis-label">Count</div>
                    <svg class="line-chart" id="muac-distribution-chart" preserveAspectRatio="none" viewBox="0 0 1000 500">
                        <defs>
                            <linearGradient id="muac-line-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="var(--color-highlight)" stop-opacity="0.8" />
                                <stop offset="100%" stop-color="var(--color-accent3)" stop-opacity="0.1" />
                            </linearGradient>
                        </defs>
                        <path class="line-path" id="muac-line-path" d=""></path>
                        <path class="line-area" id="muac-line-area" d=""></path>
                    </svg>
                    <div class="x-axis">
                        <span class="axis-label">Normal</span>
                        <span class="axis-label">MAM</span>
                        <span class="axis-label">SAM</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-card">
                <h3>Geographic Distribution</h3>
                <p class="chart-description">User distribution by barangay showing percentage of total users. Red indicators show SAM cases per barangay.</p>
                <div class="geo-chart-container">
                    <div class="geo-bars" id="barangay-prevalence"></div>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>Dietary Diversity Score</h3>
                <p class="chart-description">Minimum Dietary Diversity for Women (MDD-W) - evidence-based indicator of dietary quality and nutritional adequacy.</p>
                <div id="dds-issues-chart"></div>
            </div>
        </div>



        <!-- Community Programs Section -->
        <div class="chart-row" style="margin-bottom: 50px; display: block;">
            <div class="chart-card" style="grid-column: 1 / -1; margin-bottom: 0; width: 100%; min-height: 500px; max-height: none !important; padding: 25px; overflow: visible;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h3>Community Programs</h3>
                        <p class="chart-description">Active nutrition intervention programs in the community with impact metrics</p>
                    </div>
                    <button id="create-program-btn" class="create-program-btn" onclick="createNewProgram()">
                        <span class="btn-icon">➕</span>
                        <span class="btn-text">Create Program</span>
                    </button>
                </div>
                
                <div class="program-cards-container" style="gap: 15px; margin-top: 15px;">
                    <div class="program-card" style="margin-bottom: 0; padding: 15px;">
                        <div class="program-icon">🔍</div>
                        <div class="program-content">
                            <div class="program-title">Community-Wide Screening Initiative</div>
                            <div class="program-description">Expand screening coverage beyond current individuals</div>
                            <div class="program-meta">
                                <span class="priority-tag priority-high">Priority</span>
                                <button class="create-this-program-btn" onclick="createProgramFromCard('Community-Wide Screening Initiative', 'Workshop', 'All Barangays', 'Expand screening coverage beyond current individuals', 'Priority')">
                                    Create This Program
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="program-card" style="margin-bottom: 0; padding: 15px;">
                        <div class="program-icon">⚕️</div>
                        <div class="program-content">
                            <div class="program-title">Community Risk Reduction Program</div>
                            <div class="program-description">Community-wide initiative to reduce average risk score to below 30</div>
                            <div class="program-meta">
                                <span class="priority-tag priority-high">Priority</span>
                                <button class="create-this-program-btn" onclick="createProgramFromCard('Community Risk Reduction Program', 'Seminar', 'All Barangays', 'Community-wide initiative to reduce average risk score to below 30', 'Priority')">
                                    Create This Program
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="program-card" style="margin-bottom: 0; padding: 15px;">
                        <div class="program-icon">📚</div>
                        <div class="program-content">
                            <div class="program-title">Nutrition Education Program</div>
                            <div class="program-description">Educational program targeting at-risk individuals</div>
                            <div class="program-meta">
                                <span class="priority-tag priority-immediate">Immediate</span>
                                <button class="create-this-program-btn" onclick="createProgramFromCard('Nutrition Education Program', 'Webinar', 'Online/Community Centers', 'Educational program targeting at-risk individuals', 'Immediate')">
                                    Create This Program
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Section -->
        <div class="chart-row" style="margin-top: 30px; clear: both;">
            <div class="chart-card">
                <h3>Barangay Risk Analysis</h3>
                <p class="chart-description">Distribution of malnutrition risk levels across the selected barangay</p>
                
                <div class="progress-container">
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>High Risk Cases</span>
                            <span id="high-risk-percent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-high" id="high-risk-bar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Moderate Risk Cases</span>
                            <span id="moderate-risk-percent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-medium" id="moderate-risk-bar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Low Risk Cases</span>
                            <span id="low-risk-percent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-low" id="low-risk-bar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>SAM Cases (WHZ < -3)</span>
                            <span id="sam-percent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-high" id="sam-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="chart-card">
                <h3>Community Demographics</h3>
                <p class="chart-description">Age and gender distribution across the selected barangay</p>
                
                <div class="progress-container">
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Children (0-17 years)</span>
                            <span id="children-percent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-high" id="children-bar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Adults (18-59 years)</span>
                            <span id="adults-percent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-medium" id="adults-bar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Elderly (60+ years)</span>
                            <span id="elderly-percent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-low" id="elderly-bar" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div class="progress-item">
                        <div class="progress-label">
                            <span>Gender Distribution</span>
                            <span id="gender-distribution">0% M, 0% F</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-high" id="gender-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="navbar">
        <div class="navbar-header">
            <div class="navbar-logo">
                <div class="navbar-logo-icon" style="background: none;">
                    <img src="logo.png" alt="Logo" style="width: auto; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash.php" class="active"><span class="navbar-icon">📊</span><span>Dashboard</span></a></li>
                <li><a href="community_hub.php"><span class="navbar-icon">🏘️</span><span>Community Nutrition Hub</span></a></li>
                <li><a href="event.php"><span class="navbar-icon">⚠️</span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="USM.php"><span class="navbar-icon">👥</span><span>User Management</span></a></li>
                <li><a href="AI.php"><span class="navbar-icon">🤖</span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings.php"><span class="navbar-icon">⚙️</span><span>Settings & Admin</span></a></li>
                <li><a href="logout.php" style="color: #ff5252;"><span class="navbar-icon">🚪</span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v1.0 • © 2023</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>

    <script>
        // Custom Dropdown Functions
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown-content');
            const arrow = document.querySelector('.dropdown-arrow');
            dropdown.classList.toggle('active');
            arrow.classList.toggle('active');
        }

        function selectOption(value, text) {
            document.getElementById('selected-option').textContent = text;
            document.getElementById('dropdown-content').classList.remove('active');
            document.querySelector('.dropdown-arrow').classList.remove('active');
            
            // Update dashboard data based on selected barangay or municipality
            updateDashboardForBarangay(value);
            
            // Update selected state
            document.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });
            event.target.classList.add('selected');
        }

        function filterOptions() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const optionItems = document.querySelectorAll('.option-item');
            
            optionItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.querySelector('.custom-select-container');
            if (!container.contains(event.target)) {
                document.getElementById('dropdown-content').classList.remove('active');
                document.querySelector('.dropdown-arrow').classList.remove('active');
            }
        });

        // Barangay and Municipality selection handling
        document.addEventListener('DOMContentLoaded', function() {
            // Set up click handlers for option items
            document.querySelectorAll('.option-item').forEach(item => {
                item.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;
                    selectOption(value, text);
                });
            });
        });

        // Function to update dashboard data based on selected barangay
        function updateDashboardForBarangay(barangay) {
            // Update the "Programs in Barangay" metric
            const programsElement = document.getElementById('programs-in-barangay');
            const programsChangeElement = document.getElementById('programs-change');
            
            if (programsElement && programsChangeElement) {
                if (barangay && barangay !== '') {
                    // Handle municipality selections
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                        let programCount = 0;
                        
                        // Calculate total programs for the entire municipality
                        switch (municipality) {
                            case 'ABUCAY':
                                programCount = 10; // 10 barangays × 1 program each
                                break;
                            case 'BAGAC':
                                programCount = 15; // 15 barangays × 1 program each
                                break;
                            case 'BALANGA':
                                programCount = 25; // 25 barangays × 1 program each
                                break;
                            case 'DINALUPIHAN':
                                programCount = 46; // 46 barangays × 1 program each
                                break;
                            case 'HERMOSA':
                                programCount = 23; // 23 barangays × 1 program each
                                break;
                            case 'LIMAY':
                                programCount = 12; // 12 barangays × 1 program each
                                break;
                            case 'MARIVELES':
                                programCount = 19; // 19 barangays × 1 program each
                                break;
                            case 'MORONG':
                                programCount = 6; // 6 barangays × 1 program each
                                break;
                            case 'ORANI':
                                programCount = 32; // 32 barangays × 1 program each
                                break;
                            case 'ORION':
                                programCount = 23; // 23 barangays × 1 program each
                                break;
                            case 'PILAR':
                                programCount = 19; // 19 barangays × 1 program each
                                break;
                            case 'SAMAL':
                                programCount = 14; // 14 barangays × 1 program each
                                break;
                            default:
                                programCount = 0;
                        }
                        
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Municipality';
                    } else {
                        // Handle individual barangay selections
                        let programCount = 0;
                        // Since we removed duplicate municipality names, we can use simpler logic
                        if (barangay.includes('Bagumbayan') || barangay.includes('Poblacion') || barangay.includes('Central')) {
                            programCount = 3; // More programs in major areas
                        } else if (barangay.includes('Bangal') || barangay.includes('Bacong') || barangay.includes('Alangan')) {
                            programCount = 2; // Medium programs
                        } else {
                            programCount = 1; // Basic programs
                        }
                        
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Active';
                    }
                } else {
                    // Show total programs across all barangays
                    programsElement.textContent = '234';
                    programsChangeElement.textContent = 'All areas';
                }
            }
            
            // You can add more barangay-specific data updates here
            // For example, filtering charts, updating metrics, etc.
        }

        // Time frame button handling
        document.addEventListener('DOMContentLoaded', function() {
            const timeButtons = document.querySelectorAll('.time-btn');
            timeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    timeButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get the selected time frame
                    const timeFrame = this.textContent;
                    console.log('Selected time frame:', timeFrame);
                    
                    // Update data based on time frame (you can implement time-based filtering here)
                    updateDashboardForTimeFrame(timeFrame);
                });
            });
        });

        // Function to update dashboard data based on time frame
        function updateDashboardForTimeFrame(timeFrame) {
            console.log('Updating dashboard for time frame:', timeFrame);
            // You can implement time-based data filtering here
            // For example, showing data for the last day, week, month, etc.
        }

        // API Connection and Data Fetching Functions
        const API_BASE_URL = 'http://localhost/thesis355/unified_api.php';

        // Function to fetch data from API
        async function fetchDataFromAPI(endpoint, params = {}) {
            try {
                // Add endpoint to params
                const allParams = { endpoint, ...params };
                const queryString = new URLSearchParams(allParams).toString();
                const url = `${API_BASE_URL}?${queryString}`;
                
                console.log(`Fetching data from: ${url}`);
                console.log(`Endpoint: ${endpoint}, Params:`, params);
                
                const response = await fetch(url);
                console.log(`Response status: ${response.status}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                console.log(`API Response for ${endpoint}:`, data);
                return data;
            } catch (error) {
                console.error(`Error fetching data for ${endpoint}:`, error);
                return null;
            }
        }

        // Function to update community metrics
        async function updateCommunityMetrics(barangay = '') {
            try {
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                }

                const data = await fetchDataFromAPI('community_metrics', params);
                
                if (data && data.success) {
                    // Update Total Screened
                    const totalScreened = document.getElementById('community-total-screened');
                    const screenedChange = document.getElementById('community-screened-change');
                    if (totalScreened && screenedChange) {
                        totalScreened.textContent = data.total_screened || 0;
                        screenedChange.textContent = data.screened_change || 'No change';
                    }

                    // Update High Risk Cases
                    const highRisk = document.getElementById('community-high-risk');
                    const riskChange = document.getElementById('community-risk-change');
                    if (highRisk && riskChange) {
                        highRisk.textContent = data.high_risk_cases || 0;
                        riskChange.textContent = data.risk_change || 'No change';
                    }

                    // Update SAM Cases
                    const samCases = document.getElementById('community-sam-cases');
                    const samChange = document.getElementById('community-sam-change');
                    if (samCases && samChange) {
                        samCases.textContent = data.sam_cases || 0;
                        samChange.textContent = data.sam_change || 'No change';
                    }
                }
            } catch (error) {
                console.error('Error updating community metrics:', error);
            }
        }

        // Function to update charts
        async function updateCharts(barangay = '') {
            try {
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                }

                // Update Risk Distribution Chart
                const riskData = await fetchDataFromAPI('risk_distribution', params);
                if (riskData && riskData.success) {
                    updateRiskChart(riskData.data);
                }

                // Update WHZ Distribution Chart
                const whzData = await fetchDataFromAPI('whz_distribution', params);
                if (whzData && whzData.success) {
                    updateWHZChart(whzData.data);
                }

                // Update MUAC Distribution Chart
                const muacData = await fetchDataFromAPI('muac_distribution', params);
                if (muacData && muacData.success) {
                    updateMUACChart(muacData.data);
                }

                // Update Geographic Distribution
                const geoData = await fetchDataFromAPI('geographic_distribution', params);
                if (geoData && geoData.success) {
                    updateGeographicChart(geoData.data);
                }

                // Update Critical Alerts
                const alertsData = await fetchDataFromAPI('critical_alerts', params);
                if (alertsData && alertsData.success) {
                    updateCriticalAlerts(alertsData.data);
                }
            } catch (error) {
                console.error('Error updating charts:', error);
            }
        }

        // Function to update risk distribution chart
        function updateRiskChart(data) {
            try {
                // Get the donut chart elements
                const chartBg = document.getElementById('risk-chart-bg');
                const centerText = document.getElementById('risk-center-text');
                const segments = document.getElementById('risk-segments');
                
                if (!chartBg || !centerText || !segments) {
                    console.error('Risk chart elements not found:', { chartBg: !!chartBg, centerText: !!centerText, segments: !!segments });
                    return;
                }
                
                // Add loading state to prevent flickering
                chartBg.style.opacity = '0.8';

                console.log('Updating risk chart with data:', data);
                
                // Preserve existing segments for smooth transitions
                const existingSegments = segments.querySelectorAll('.segment');
                existingSegments.forEach(segment => {
                    segment.style.opacity = '0';
                });
                
                // Define colors and labels for risk levels
                const isDarkTheme = document.body.classList.contains('dark-theme');
                const colors = isDarkTheme ? [
                    '#A1B454',      // Green for Low Risk
                    '#F9B97F',      // Yellow for Moderate Risk
                    '#E0C989',      // Orange for High Risk
                    '#CF8686'       // Red for Severe Risk
                ] : [
                    '#76BB6E',      // Green for Low Risk
                    '#F9B97F',      // Yellow for Moderate Risk
                    '#F9C87F',      // Orange for High Risk
                    '#E98D7C'       // Red for Severe Risk
                ];
                
                const labels = [
                    'Low Risk',
                    'Moderate Risk',
                    'High Risk',
                    'Severe Risk'
                ];
                
                // Handle the API data structure correctly
                let riskLevels = [0, 0, 0, 0]; // [Low, Moderate, High, Severe]
                let totalUsers = 0;
                
                if (data && data.length > 0) {
                    // API returns data in format: [{label: 'Low Risk', value: 1, color: '#4CAF50'}, ...]
                    data.forEach(item => {
                        totalUsers += item.value;
                        
                        // Map the label to the correct index
                        if (item.label === 'Low Risk') riskLevels[0] = item.value;
                        else if (item.label === 'Moderate Risk') riskLevels[1] = item.value;
                        else if (item.label === 'High Risk') riskLevels[2] = item.value;
                        else if (item.label === 'Critical Risk') riskLevels[3] = item.value;
                    });
                }
                
                // If no data from API, create sample data for demonstration
                if (totalUsers === 0) {
                    // Create sample data based on typical distribution
                    riskLevels = [3, 4, 3, 2]; // Sample distribution
                    totalUsers = 12;
                }
                
                // Calculate average risk score (weighted average) - this is how it originally worked
                let averageRisk = 0;
                if (totalUsers > 0) {
                    const weightedSum = (riskLevels[0] * 12.5) + (riskLevels[1] * 37.5) + (riskLevels[2] * 62.5) + (riskLevels[3] * 87.5);
                    averageRisk = Math.round(weightedSum / totalUsers);
                }
                
                console.log('Risk levels distribution:', riskLevels);
                console.log('Total users:', totalUsers);
                console.log('Average risk:', averageRisk);
                
                // Update center text with average risk score and smooth animation
                if (centerText.textContent !== averageRisk + '%') {
                    centerText.style.transform = 'scale(1.1)';
                    centerText.style.color = '#FFD700';
                    
                    setTimeout(() => {
                        centerText.textContent = averageRisk + '%';
                        centerText.style.transform = 'scale(1)';
                        centerText.style.color = '';
                    }, 150);
                } else {
                    centerText.textContent = averageRisk + '%';
                }
                
                // Create conic gradient for the donut chart
                let gradientString = '';
                let currentPercent = 0;
                
                riskLevels.forEach((count, index) => {
                    if (count > 0) {
                        const percentage = (count / totalUsers) * 100;
                        const startPercent = currentPercent;
                        currentPercent += percentage;
                        gradientString += `${colors[index]} ${startPercent}% ${currentPercent}%`;
                        if (currentPercent < 100) {
                            gradientString += ', ';
                        }
                    }
                });
                
                // Apply the conic gradient with fallback
                if (gradientString.trim()) {
                    chartBg.style.background = `conic-gradient(${gradientString})`;
                    console.log('Applied gradient:', gradientString);
                } else {
                    // Fallback to a default gradient if no data
                    chartBg.style.background = 'conic-gradient(#e0e0e0 0% 100%)';
                    console.log('Applied fallback gradient');
                }
                
                // Create segments info with labels
                riskLevels.forEach((count, index) => {
                    if (count > 0) {
                        const percentage = (count / totalUsers) * 100;
                        
                        // Try to find existing segment to update
                        let segmentDiv = segments.querySelector(`[data-risk-level="${index}"]`);
                        
                        if (!segmentDiv) {
                            // Create new segment if it doesn't exist
                            segmentDiv = document.createElement('div');
                            segmentDiv.className = 'segment';
                            segmentDiv.setAttribute('data-risk-level', index);
                            segmentDiv.style.opacity = '0';
                            segments.appendChild(segmentDiv);
                        }
                        
                        // Update segment content
                        segmentDiv.innerHTML = `
                            <span class="color-indicator" style="background-color: ${colors[index]}"></span>
                            <span>${labels[index]}: ${count} (${percentage.toFixed(1)}%)</span>
                        `;
                        
                        // Fade in the segment
                        setTimeout(() => {
                            segmentDiv.style.opacity = '1';
                        }, 100);
                        
                        console.log(`Updated segment: ${labels[index]} - ${count} (${percentage.toFixed(1)}%)`);
                    }
                });
                
                // Remove old segments that are no longer needed
                setTimeout(() => {
                    const allSegments = segments.querySelectorAll('.segment');
                    allSegments.forEach(segment => {
                        if (segment.style.opacity === '0') {
                            segment.remove();
                        }
                    });
                    
                    // Restore full opacity after update is complete
                    chartBg.style.opacity = '1';
                }, 500);
                
                console.log('Risk chart updated successfully');
                
            } catch (error) {
                console.error('Error updating risk chart:', error);
            }
        }

        // Function to update WHZ chart
        function updateWHZChart(data) {
            const chart = document.getElementById('whz-distribution-chart');
            if (!chart) return;

            const linePath = chart.querySelector('#whz-line-path');
            const lineArea = chart.querySelector('#whz-line-area');
            
            if (linePath && lineArea) {
                // Create path data for WHZ distribution
                const pathData = createLinePath(data);
                linePath.setAttribute('d', pathData);
                lineArea.setAttribute('d', pathData);
            }
        }

        // Function to update MUAC chart
        function updateMUACChart(data) {
            const chart = document.getElementById('muac-distribution-chart');
            if (!chart) return;

            const linePath = chart.querySelector('#muac-line-path');
            const lineArea = chart.querySelector('#muac-line-area');
            
            if (linePath && lineArea) {
                // Create path data for MUAC distribution
                const pathData = createLinePath(data);
                linePath.setAttribute('d', pathData);
                lineArea.setAttribute('d', pathData);
            }
        }

        // Helper function to create line path
        function createLinePath(data) {
            if (!data || data.length === 0) return '';
            
            const width = 1000;
            const height = 500;
            const padding = 50;
            const chartWidth = width - 2 * padding;
            const chartHeight = height - 2 * padding;
            
            const maxValue = Math.max(...data.map(d => d.value));
            const step = chartWidth / (data.length - 1);
            
            let path = `M ${padding} ${height - padding - (data[0].value / maxValue) * chartHeight}`;
            
            for (let i = 1; i < data.length; i++) {
                const x = padding + i * step;
                const y = height - padding - (data[i].value / maxValue) * chartHeight;
                path += ` L ${x} ${y}`;
            }
            
            return path;
        }

        // Function to update geographic distribution
        function updateGeographicChart(data) {
            const container = document.getElementById('barangay-prevalence');
            if (!container) return;

            container.innerHTML = '';
            
            data.forEach(item => {
                const barItem = document.createElement('div');
                barItem.className = 'geo-bar-item';
                barItem.innerHTML = `
                    <div class="geo-bar-name">${item.barangay}</div>
                    <div class="geo-bar-progress">
                        <div class="geo-bar-fill" style="width: ${item.percentage}%"></div>
                    </div>
                    <div class="geo-bar-percentage">${item.percentage}%</div>
                `;
                container.appendChild(barItem);
            });
        }

        // Function to update critical alerts
        function updateCriticalAlerts(data) {
            const container = document.getElementById('critical-alerts');
            if (!container) return;

            container.innerHTML = '';
            
            // Handle the API data structure correctly
            if (data && data.length > 0) {
                data.forEach(alert => {
                    const alertItem = document.createElement('li');
                    alertItem.className = `alert-item ${alert.type || 'warning'}`;
                    
                    // Use the correct API data structure
                    const title = alert.message || 'High malnutrition risk detected';
                    const user = alert.user || 'Unknown user';
                    const time = alert.time || 'Recent';
                    const type = alert.type || 'critical';
                    
                    alertItem.innerHTML = `
                        <div class="alert-content">
                            <h4>${title}</h4>
                            <p>${user} - Requires immediate attention</p>
                        </div>
                        <div class="alert-time">${time}</div>
                        <span class="alert-badge badge-${type}">${type === 'critical' ? 'Critical' : 'Warning'}</span>
                    `;
                    container.appendChild(alertItem);
                });
            } else {
                // Show no alerts message
                const noAlertsItem = document.createElement('li');
                noAlertsItem.style.cssText = `
                    padding: 15px;
                    text-align: center;
                    color: var(--color-text);
                    opacity: 0.7;
                    font-style: italic;
                `;
                noAlertsItem.textContent = 'No critical alerts at this time';
                container.appendChild(noAlertsItem);
            }
        }

        // Initialize dashboard on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard initialized - DOM loaded');
            
            // Test API connection first
            testAPIConnection();
            
            // Load initial data with error handling
            try {
                console.log('Loading initial dashboard data...');
                updateCommunityMetrics();
                updateCharts();
            } catch (error) {
                console.error('Error loading initial data:', error);
            }
            
            // Set up auto-refresh every 3 seconds with seamless updates
            setInterval(() => {
                try {
                    // Use requestAnimationFrame for smooth, seamless updates
                    requestAnimationFrame(() => {
                        updateCommunityMetrics();
                        updateCharts();
                    });
                } catch (error) {
                    console.error('Error in auto-refresh:', error);
                }
            }, 3000);
        });

        // Test API connection
        async function testAPIConnection() {
            console.log('Testing API connection...');
            try {
                const response = await fetch('http://localhost/thesis355/unified_api.php?test=1');
                console.log('API Response status:', response.status);
                if (response.ok) {
                    const data = await response.text();
                    console.log('API Response:', data);
                } else {
                    console.error('API not responding properly');
                }
            } catch (error) {
                console.error('API Connection failed:', error);
                console.log('Please check if unified_api.php exists and XAMPP is running');
            }
        }

        // Enhanced barangay selection function
        function updateDashboardForBarangay(barangay) {
            // Update programs metric
            updateProgramsMetric(barangay);
            
            // Update all charts and metrics for the selected barangay
            updateCommunityMetrics(barangay);
            updateCharts(barangay);
            
            // Update analysis section
            updateAnalysisSection(barangay);
        }

        // Function to update analysis section
        async function updateAnalysisSection(barangay = '') {
            try {
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                }

                const data = await fetchDataFromAPI('analysis_data', params);
                
                if (data && data.success) {
                    // Update risk analysis
                    updateRiskAnalysis(data.risk_analysis);
                    
                    // Update demographics
                    updateDemographics(data.demographics);
                }
            } catch (error) {
                console.error('Error updating analysis section:', error);
            }
        }

        // Function to update risk analysis
        function updateRiskAnalysis(data) {
            if (!data) return;
            
            // Update progress bars
            const highRiskBar = document.getElementById('high-risk-bar');
            const moderateRiskBar = document.getElementById('moderate-risk-bar');
            const lowRiskBar = document.getElementById('low-risk-bar');
            const samBar = document.getElementById('sam-bar');
            
            if (highRiskBar) {
                highRiskBar.style.width = data.high_risk_percent + '%';
                document.getElementById('high-risk-percent').textContent = data.high_risk_percent + '%';
            }
            
            if (moderateRiskBar) {
                moderateRiskBar.style.width = data.moderate_risk_percent + '%';
                document.getElementById('moderate-risk-percent').textContent = data.moderate_risk_percent + '%';
            }
            
            if (lowRiskBar) {
                lowRiskBar.style.width = data.low_risk_percent + '%';
                document.getElementById('low-risk-percent').textContent = data.low_risk_percent + '%';
            }
            
            if (samBar) {
                samBar.style.width = data.sam_percent + '%';
                document.getElementById('sam-percent').textContent = data.sam_percent + '%';
            }
        }

        // Function to update demographics
        function updateDemographics(data) {
            if (!data) return;
            
            // Update progress bars
            const childrenBar = document.getElementById('children-bar');
            const adultsBar = document.getElementById('adults-bar');
            const elderlyBar = document.getElementById('elderly-bar');
            const genderBar = document.getElementById('gender-bar');
            
            if (childrenBar) {
                childrenBar.style.width = data.children_percent + '%';
                document.getElementById('children-percent').textContent = data.children_percent + '%';
            }
            
            if (adultsBar) {
                adultsBar.style.width = data.adults_percent + '%';
                document.getElementById('adults-percent').textContent = data.adults_percent + '%';
            }
            
            if (elderlyBar) {
                elderlyBar.style.width = data.elderly_percent + '%';
                document.getElementById('elderly-percent').textContent = data.elderly_percent + '%';
            }
            
            if (genderBar) {
                genderBar.style.width = data.gender_percent + '%';
                document.getElementById('gender-distribution').textContent = data.gender_distribution;
            }
        }

        // Function to create new program - redirects to event.php in same tab
        function createNewProgram() {
            // Redirect to event.php in the same tab
            window.location.href = 'event.php';
        }

        // Function to create program from card - redirects to event.php with pre-filled data
        function createProgramFromCard(title, type, location, description, urgency) {
            // Create URL parameters with proper encoding
            const params = new URLSearchParams({
                program: title,
                type: type,
                location: location,
                description: description,
                urgency: urgency
            });
            
            // Redirect to event.php in the same tab with pre-filled form data
            window.location.href = `event.php?${params.toString()}`;
        }

        
    </script>
</body>
</html>

