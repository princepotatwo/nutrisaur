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

// Include the centralized configuration file
require_once __DIR__ . "/../config.php";
    
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

/* Navbar Styles - EXACT COPY FROM AI.php */
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
    padding: 35px 25px;
    display: flex;
    align-items: center;
    border-bottom: 2px solid rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    position: relative;
    overflow: hidden;
}

.navbar-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

.light-theme .navbar-header {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.05) 0%, transparent 100%);
}

.light-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.3), transparent);
}

.navbar-logo {
    display: flex;
    align-items: center;
    transition: transform 0.3s ease;
}

.navbar-logo:hover {
    transform: scale(1.05);
}

.navbar-logo-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-right: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 20px;
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.1);
}

.navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.08));
    border-color: rgba(161, 180, 84, 0.3);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.2);
}

.light-theme .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.1), rgba(142, 185, 110, 0.05));
    border-color: rgba(142, 185, 110, 0.2);
    box-shadow: 0 2px 8px rgba(142, 185, 110, 0.1);
}

.light-theme .navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(142, 185, 110, 0.15), rgba(142, 185, 110, 0.08));
    border-color: rgba(142, 185, 110, 0.3);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.2);
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
    margin-bottom: 2px;
    position: relative;
    transition: all 0.3s ease;
}

.navbar li:hover {
    transform: translateX(5px);
}

.navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(161, 180, 84, 0.08);
}

.light-theme .navbar li:not(:last-child) {
    border-bottom: 1px solid rgba(142, 185, 110, 0.08);
}

.navbar a {
    text-decoration: none;
    color: var(--color-text);
    font-size: 17px;
    padding: 18px 25px;
    display: flex;
    align-items: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    opacity: 0.9;
    border-radius: 0 12px 12px 0;
    margin-right: 10px;
    overflow: hidden;
    background: linear-gradient(90deg, transparent 0%, transparent 100%);
}

.navbar a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
    transition: left 0.5s ease;
}

.light-theme .navbar a::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.1), transparent);
}

.navbar a:hover {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.08) 0%, rgba(161, 180, 84, 0.04) 100%);
    color: var(--color-highlight);
    opacity: 1;
    transform: translateX(3px);
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.15);
}

.navbar a:hover::before {
    left: 100%;
}

.navbar a.active {
    background: linear-gradient(90deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.08) 100%);
    color: var(--color-highlight);
    opacity: 1;
    font-weight: 600;
    border-left: 4px solid var(--color-highlight);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.2);
    transform: translateX(2px);
}

.light-theme .navbar a:hover {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.08) 0%, rgba(142, 185, 110, 0.04) 100%);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.15);
}

.light-theme .navbar a.active {
    background: linear-gradient(90deg, rgba(142, 185, 110, 0.15) 0%, rgba(142, 185, 110, 0.08) 100%);
    border-left-color: var(--color-accent3);
    box-shadow: 0 6px 20px rgba(142, 185, 110, 0.2);
}

.navbar-icon {
    margin-right: 15px;
    width: 24px;
    font-size: 20px;
}

.navbar-footer {
    padding: 25px 20px;
    border-top: 2px solid rgba(164, 188, 46, 0.15);
    font-size: 12px;
    opacity: 0.7;
    text-align: center;
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
    position: relative;
}

.navbar-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

.light-theme .navbar-footer {
    background: linear-gradient(135deg, transparent 0%, rgba(142, 185, 110, 0.03) 100%);
}

.light-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(142, 185, 110, 0.2), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

.light-theme .navbar-footer div:first-child {
    color: var(--color-accent3);
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
    fill: none;
    stroke: var(--color-highlight);
    stroke-width: 3;
    stroke-dasharray: 1000;
    stroke-dashoffset: 1000;
    transition: stroke-dashoffset 2.5s ease;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    /* Debug: Ensure visibility */
    opacity: 1;
    visibility: visible;
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
    position: relative;
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
    fill: url(#line-gradient);
    opacity: 0.4;
    clip-path: polygon(0 100%, 0 0, 0 0, 0 100%);
    transition: clip-path 2.5s ease;
    /* Debug: Ensure visibility */
    visibility: visible;
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
    background-color: rgba(207, 134, 134, 0.1);
    border-left-width: 4px;
}

.alert-item.danger .alert-content h4 {
    color: var(--color-danger);
    font-weight: 600;
}

.alert-item.danger .alert-time {
    color: var(--color-danger);
    opacity: 0.8;
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

/* Nutritional Status Overview - Ultra-Compact Professional Design */
.nutrition-status-container {
    padding: 16px;
    max-height: 400px;
    overflow: hidden;
}

.nutrition-compact {
    display: flex;
    flex-direction: column;
    gap: 16px;
    height: 100%;
}

/* Combined Grid Layout - Space Efficient */
.nutrition-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    flex: 1;
}

/* WHZ Section - Compact 2x2 Grid */
.whz-section {
    background: var(--color-card);
    border-radius: 10px;
    padding: 16px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.section-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text);
    margin-bottom: 12px;
    text-align: center;
    opacity: 0.9;
}

.whz-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.whz-item {
    background: rgba(0, 0, 0, 0.03);
    border-radius: 8px;
    padding: 10px;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
    position: relative;
}

.whz-item:hover {
    background: rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}

.whz-item.sam {
    border-left-color: #F44336;
}

.whz-item.mam {
    border-left-color: #FF9800;
}

.whz-item.normal {
    border-left-color: #4CAF50;
}

.whz-item.overweight {
    border-left-color: #FF5722;
}

.whz-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    margin-bottom: 6px;
    position: relative;
}

.whz-item.sam .whz-dot {
    background: #F44336;
    box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.2);
}

.whz-item.mam .whz-dot {
    background: #FF9800;
    box-shadow: 0 0 0 2px rgba(255, 152, 0, 0.2);
}

.whz-item.normal .whz-dot {
    background: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.whz-item.overweight .whz-dot {
    background: #FF5722;
    box-shadow: 0 0 0 2px rgba(255, 87, 34, 0.2);
}

.whz-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.whz-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--color-text);
    line-height: 1.2;
    opacity: 0.9;
}

.whz-value {
    font-size: 18px;
    font-weight: 700;
    color: var(--color-highlight);
    line-height: 1;
}

.light-theme .whz-value {
    color: var(--color-accent3);
}

.whz-range {
    font-size: 9px;
    color: var(--color-text);
    opacity: 0.6;
    font-family: 'Courier New', monospace;
    background: rgba(0, 0, 0, 0.03);
    padding: 2px 4px;
    border-radius: 3px;
    display: inline-block;
    width: fit-content;
}

.light-theme .whz-range {
    background: rgba(0, 0, 0, 0.06);
}

/* MUAC Section - Compact Side Panel */
.muac-section {
    background: var(--color-card);
    border-radius: 10px;
    padding: 16px;
    border: 1px solid rgba(161, 180, 84, 0.1);
}

.muac-compact {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.muac-row {
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.muac-row:hover {
    transform: translateX(3px);
}

.muac-label {
    font-size: 10px;
    font-weight: 500;
    color: var(--color-text);
    opacity: 0.8;
    min-width: 80px;
    flex-shrink: 0;
}

.muac-bar {
    height: 6px;
    background: rgba(0, 0, 0, 0.06);
    border-radius: 3px;
    overflow: hidden;
    flex: 1;
    min-width: 60px;
}

.light-theme .muac-bar {
    background: rgba(0, 0, 0, 0.1);
}

.muac-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 8px;
}

.muac-fill.normal {
    background: linear-gradient(90deg, #4CAF50, #66BB6A);
}

.muac-fill.mam {
    background: linear-gradient(90deg, #FF9800, #FFB74D);
}

.muac-fill.sam {
    background: linear-gradient(90deg, #F44336, #EF5350);
}

.muac-count {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-highlight);
    min-width: 25px;
    text-align: right;
    flex-shrink: 0;
}

.light-theme .muac-count {
    color: var(--color-accent3);
}

/* Summary Bar - Ultra Compact */
.summary-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    background: var(--color-card);
    border-radius: 10px;
    padding: 12px;
    border: 1px solid rgba(161, 180, 84, 0.1);
    margin-top: auto;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.summary-label {
    font-size: 11px;
    font-weight: 500;
    color: var(--color-text);
    opacity: 0.7;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.summary-value {
    font-size: 16px;
    font-weight: 700;
    color: var(--color-highlight);
    line-height: 1;
}

.light-theme .summary-value {
    color: var(--color-accent3);
}

.summary-divider {
    width: 1px;
    height: 24px;
    background: rgba(161, 180, 84, 0.2);
    border-radius: 1px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .nutrition-status-container {
        padding: 12px;
        max-height: 350px;
    }
    
    .nutrition-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .whz-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }
    
    .whz-item, .muac-section {
        padding: 12px;
    }
    
    .whz-value {
        font-size: 16px;
    }
    
    .summary-bar {
        padding: 10px;
        gap: 16px;
    }
}

@media (max-width: 480px) {
    .nutrition-status-container {
        padding: 10px;
        max-height: 300px;
    }
    
    .whz-item, .muac-section, .summary-bar {
        padding: 10px;
    }
    
    .whz-grid {
        gap: 6px;
    }
    
    .muac-compact {
        gap: 10px;
    }
    
    .summary-bar {
        gap: 14px;
    }
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

.light-theme .generate-programs-btn {
    background: linear-gradient(135deg, var(--color-highlight), var(--color-accent3));
    color: white;
    box-shadow: 0 4px 15px rgba(118, 187, 110, 0.3);
}

.light-theme .generate-programs-btn:hover {
    box-shadow: 0 6px 20px rgba(118, 187, 110, 0.4);
    background: linear-gradient(135deg, var(--color-accent3), var(--color-highlight));
}

/* Loading Spinner Styles */
.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(161, 180, 84, 0.2);
    border-top: 4px solid var(--color-highlight);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.programs-loading {
    background: rgba(161, 180, 84, 0.02);
    border-radius: 10px;
    border: 1px dashed rgba(161, 180, 84, 0.2);
}



/* Generate Programs Button Styles */
.generate-programs-btn {
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

.generate-programs-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.4);
    background: linear-gradient(135deg, var(--color-accent1), var(--color-highlight));
}

.generate-programs-btn:active {
    transform: translateY(0);
}

.generate-programs-btn .btn-text {
    white-space: nowrap;
}

/* Show AI Reasoning Button Styles */
.show-reasoning-btn {
    background: linear-gradient(135deg, #81d4fa, #4fc3f7);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(129, 212, 250, 0.3);
    text-decoration: none;
}

.show-reasoning-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(129, 212, 250, 0.4);
    background: linear-gradient(135deg, #4fc3f7, #81d4fa);
}

.show-reasoning-btn:active {
    transform: translateY(0);
}

.show-reasoning-btn .btn-text {
    white-space: nowrap;
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

.priority-medium {
    background-color: var(--color-warning);
    color: #333;
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
                        <h3>Intelligent Community Programs</h3>
                        <p class="chart-description">AI-generated nutrition intervention programs based on real-time community data analysis</p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button id="generate-programs-btn" class="generate-programs-btn" onclick="generateIntelligentPrograms()">
                            <span class="btn-text">Generate Programs</span>
                        </button>
                        <button id="create-program-btn" class="create-program-btn" onclick="createNewProgram()">
                            <span class="btn-icon">➕</span>
                            <span class="btn-text">Create Program</span>
                        </button>
                    </div>
                </div>
                
                <!-- Initial State -->
                <div id="programs-loading" class="programs-loading" style="display: flex; justify-content: center; align-items: center; height: 200px;">
                    <div style="text-align: center;">
                        <div class="loading-spinner"></div>
                        <p style="margin-top: 15px; color: var(--color-text); opacity: 0.7;">Analyzing community data and generating intelligent programs...</p>
                    </div>
                </div>
                
                <!-- Dynamic Program Cards Container -->
                <div id="intelligent-program-cards" class="program-cards-container" style="gap: 15px; margin-top: 15px; display: none;">
                    <!-- Programs will be dynamically generated here -->
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

    <script>
        // Custom Dropdown Functions
        function toggleDropdown() {
            console.log('toggleDropdown called');
            const dropdown = document.getElementById('dropdown-content');
            const arrow = document.querySelector('.dropdown-arrow');
            const selectHeader = document.querySelector('.select-header');
            
            console.log('Dropdown elements found:', { 
                dropdown: !!dropdown, 
                arrow: !!arrow, 
                selectHeader: !!selectHeader 
            });
            
            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('active');
                console.log('Dropdown toggled, active state:', dropdown.classList.contains('active'));
                
                // Log the current state
                if (dropdown.classList.contains('active')) {
                    console.log('Dropdown is now OPEN');
                    console.log('Dropdown content:', dropdown.innerHTML);
                } else {
                    console.log('Dropdown is now CLOSED');
                }
            } else {
                console.error('Dropdown elements not found:', { dropdown: !!dropdown, arrow: !!arrow });
            }
        }

        function selectOption(value, text) {
            console.log('selectOption called with:', { value, text });
            
            const selectedOption = document.getElementById('selected-option');
            const dropdownContent = document.getElementById('dropdown-content');
            const dropdownArrow = document.querySelector('.dropdown-arrow');
            
            console.log('Elements found:', { 
                selectedOption: !!selectedOption, 
                dropdownContent: !!dropdownContent, 
                dropdownArrow: !!dropdownArrow 
            });
            
            if (selectedOption && dropdownContent && dropdownArrow) {
                selectedOption.textContent = text;
                dropdownContent.classList.remove('active');
                dropdownArrow.classList.remove('active');
                
                console.log('Updated selected option to:', text);
                console.log('Closed dropdown');
                
                // Update dashboard data based on selected barangay or municipality
                console.log('Calling updateDashboardForBarangay with value:', value);
                updateDashboardForBarangay(value);
                
                // Test municipality filtering if a municipality is selected
                if (value && value.startsWith('MUNICIPALITY_')) {
                    console.log('Testing municipality filtering...');
                    testMunicipalityFiltering(value);
                }
                
                // Test user data consistency for debugging
                console.log('Testing user data consistency...');
                testUserDataConsistency('hwheh@ushs.dijs'); // Test with the user from logs
                
                // Update selected state
                document.querySelectorAll('.option-item').forEach(item => {
                    item.classList.remove('selected');
                });
                
                // Try to find the clicked item and mark it as selected
                const clickedItem = document.querySelector(`[data-value="${value}"]`);
                if (clickedItem) {
                    clickedItem.classList.add('selected');
                    console.log('Marked item as selected:', clickedItem.textContent);
                } else {
                    console.error('Could not find clicked item to mark as selected');
                }
                
                // If "All Barangays" is selected, clear the localStorage
                if (!value || value === '') {
                    localStorage.removeItem('selectedBarangay');
                    console.log('Cleared barangay selection from localStorage (All Barangays selected)');
                }
            } else {
                console.error('Required elements not found for selectOption');
            }
        }

        function filterOptions() {
            const searchInput = document.getElementById('search-input');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const optionItems = document.querySelectorAll('.option-item');
            
            console.log('filterOptions called');
            console.log('Search input element:', !!searchInput);
            console.log('Search term:', searchTerm);
            console.log('Total option items:', optionItems.length);
            
            if (optionItems.length === 0) {
                console.error('No option items found for filtering!');
                return;
            }
            
            let visibleCount = 0;
            optionItems.forEach((item, index) => {
                const text = item.textContent.toLowerCase();
                const matches = text.includes(searchTerm);
                
                if (matches) {
                    item.style.display = 'block';
                    visibleCount++;
                    console.log(`Option ${index} visible:`, text);
                } else {
                    item.style.display = 'none';
                    console.log(`Option ${index} hidden:`, text);
                }
            });
            
            console.log('Visible options after filtering:', visibleCount);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            console.log('Document click event:', event.target);
            
            const container = document.querySelector('.custom-select-container');
            console.log('Container found:', !!container);
            
            if (container && !container.contains(event.target)) {
                console.log('Click outside container detected');
                const dropdown = document.getElementById('dropdown-content');
                const arrow = document.querySelector('.dropdown-arrow');
                
                if (dropdown && arrow) {
                    dropdown.classList.remove('active');
                    arrow.classList.remove('active');
                    console.log('Dropdown closed by outside click');
                } else {
                    console.error('Dropdown elements not found for outside click close');
                }
            }
        });

        // Barangay and Municipality selection handling
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Setting up barangay selection handlers...');
            
            // Set up click handlers for option items
            const optionItems = document.querySelectorAll('.option-item');
            console.log('Found option items:', optionItems.length);
            
            if (optionItems.length === 0) {
                console.error('No option items found! This means the dropdown is not working properly.');
                console.log('Checking if dropdown container exists...');
                const dropdownContainer = document.querySelector('.dropdown-content');
                console.log('Dropdown container:', dropdownContainer);
                
                if (dropdownContainer) {
                    console.log('Dropdown container HTML:', dropdownContainer.innerHTML);
                }
                
                // Try to find option items with a different selector
                const alternativeOptions = document.querySelectorAll('[data-value]');
                console.log('Alternative options found:', alternativeOptions.length);
                
                if (alternativeOptions.length > 0) {
                    console.log('Using alternative options...');
                    alternativeOptions.forEach((item, index) => {
                        const value = item.getAttribute('data-value');
                        const text = item.textContent;
                        console.log(`Alternative option ${index}:`, { value, text });
                        
                        item.addEventListener('click', function() {
                            const value = this.getAttribute('data-value');
                            const text = this.textContent;
                            console.log('Alternative barangay selected:', { value, text });
                            selectOption(value, text);
                        });
                    });
                }
            } else {
                optionItems.forEach((item, index) => {
                    const value = item.getAttribute('data-value');
                    const text = item.textContent;
                    console.log(`Setting up option ${index}:`, { value, text });
                    
                    item.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        const text = this.textContent;
                        console.log('Barangay selected:', { value, text });
                        selectOption(value, text);
                    });
                });
            }
            
            console.log('Barangay selection handlers setup complete');
        });

        // Global variable to store the currently selected barangay
        let currentSelectedBarangay = '';
        
        // Function to restore selected barangay from localStorage
        function restoreSelectedBarangay() {
            try {
                const savedBarangay = localStorage.getItem('selectedBarangay');
                if (savedBarangay) {
                    console.log('Found saved barangay in localStorage:', savedBarangay);
                    currentSelectedBarangay = savedBarangay;
                    
                    // Update the dropdown display to show the saved selection
                    const selectedOptionElement = document.getElementById('selected-option');
                    if (selectedOptionElement) {
                        // Find the corresponding option text for the saved value
                        const optionItem = document.querySelector(`[data-value="${savedBarangay}"]`);
                        if (optionItem) {
                            selectedOptionElement.textContent = optionItem.textContent;
                            console.log('Restored dropdown display to:', optionItem.textContent);
                        } else {
                            console.log('Could not find option item for saved value:', savedBarangay);
                        }
                    }
                    
                    // Mark the saved option as selected in the dropdown
                    document.querySelectorAll('.option-item').forEach(item => {
                        item.classList.remove('selected');
                        if (item.getAttribute('data-value') === savedBarangay) {
                            item.classList.add('selected');
                            console.log('Marked saved option as selected:', item.textContent);
                        }
                    });
                    
                    console.log('Successfully restored barangay selection:', savedBarangay);
                    return true; // Indicate successful restoration
                } else {
                    console.log('No saved barangay found in localStorage');
                    return false; // Indicate no restoration needed
                }
            } catch (error) {
                console.error('Error restoring barangay selection:', error);
                return false; // Indicate restoration failed
            }
        }
        
        // Function to clear barangay selection
        function clearBarangaySelection() {
            currentSelectedBarangay = '';
            localStorage.removeItem('selectedBarangay');
            console.log('Cleared barangay selection');
            
            // Reset dropdown display
            const selectedOptionElement = document.getElementById('selected-option');
            if (selectedOptionElement) {
                selectedOptionElement.textContent = 'All Barangays';
            }
            
            // Clear selected state from all options
            document.querySelectorAll('.option-item').forEach(item => {
                item.classList.remove('selected');
            });
            
            // Refresh dashboard with no barangay filter
            updateDashboardForBarangay('');
        }
        
        // Function to get current barangay selection
        function getCurrentBarangay() {
            return currentSelectedBarangay;
        }
        
        // Function to check if a barangay is currently selected
        function isBarangaySelected() {
            return currentSelectedBarangay && currentSelectedBarangay !== '';
        }
        
        // Function to update dashboard data based on selected barangay
        function updateDashboardForBarangay(barangay) {
            console.log('=== UPDATING DASHBOARD FOR BARANGAY ===');
            console.log('Barangay parameter:', barangay);
            console.log('Barangay type:', typeof barangay);
            console.log('Barangay length:', barangay ? barangay.length : 0);
            
            // Store the selected barangay globally
            if (barangay !== undefined && barangay !== null) {
                currentSelectedBarangay = barangay;
                console.log('Stored current barangay:', currentSelectedBarangay);
                
                // Also store in localStorage for persistence across page refreshes
                if (barangay !== '') {
                    localStorage.setItem('selectedBarangay', barangay);
                    console.log('Saved barangay to localStorage:', barangay);
                } else {
                    localStorage.removeItem('selectedBarangay');
                    console.log('Cleared barangay from localStorage (All Barangays selected)');
                }
            }
            
            // Update the "Programs in Barangay" metric
            console.log('Calling updateProgramsMetric...');
            updateProgramsMetric(barangay);
            
            // Update all charts and metrics for the selected barangay
            console.log('Calling updateCommunityMetrics...');
            updateCommunityMetrics(barangay);
            
            // Update all charts and metrics for the selected barangay
            console.log('Calling updateCharts...');
            updateCharts(barangay);
            
            // Update analysis section
            console.log('Calling updateAnalysisSection...');
            updateAnalysisSection(barangay);
            
            // Update geographic distribution chart
            console.log('Calling updateGeographicChart...');
            updateGeographicChart(barangay);
            
            // Update critical alerts
            console.log('Calling updateCriticalAlerts...');
            updateCriticalAlerts(barangay);
            
            // Automatically refresh intelligent programs for the selected location
            console.log('Calling updateIntelligentPrograms...');
            updateIntelligentPrograms(barangay);
            
            console.log('=== DASHBOARD UPDATE COMPLETE ===');
        }

        // Function to calculate total programs across all areas
        async function calculateTotalPrograms() {
            try {
                // Get total users to estimate programs
                const data = await fetchDataFromAPI('community_metrics');
                if (data && data.success && data.total_screened > 0) {
                    // Estimate 1 program per 10 users, minimum 1
                    return Math.max(1, Math.ceil(data.total_screened / 10));
                } else {
                    // No users = no programs
                    return 0;
                }
            } catch (error) {
                console.error('Error calculating total programs:', error);
                return 0;
            }
        }

        // Function to update programs metric
        function updateProgramsMetric(barangay) {
            console.log('updateProgramsMetric called with barangay:', barangay);
            
            const programsElement = document.getElementById('programs-in-barangay');
            const programsChangeElement = document.getElementById('programs-change');
            
            if (programsElement && programsChangeElement) {
                console.log('Programs elements found, updating...');
                
                if (barangay && barangay !== '') {
                    console.log('Processing specific barangay/municipality:', barangay);
                    
                    // Handle municipality selections
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                        let programCount = 0;
                        
                        console.log('Processing municipality:', municipality);
                        
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
                        
                        console.log(`Municipality ${municipality} has ${programCount} programs`);
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Municipality';
                    } else {
                        // Handle individual barangay selections
                        console.log('Processing individual barangay:', barangay);
                        let programCount = 0;
                        // Since we removed duplicate municipality names, we can use simpler logic
                        if (barangay.includes('Bagumbayan') || barangay.includes('Poblacion') || barangay.includes('Central')) {
                            programCount = 3; // More programs in major areas
                        } else if (barangay.includes('Bangal') || barangay.includes('Bacong') || barangay.includes('Alangan')) {
                            programCount = 2; // Medium programs
                        } else {
                            programCount = 1; // Basic programs
                        }
                        
                        console.log(`Barangay ${barangay} has ${programCount} programs`);
                        programsElement.textContent = programCount;
                        programsChangeElement.textContent = 'Active';
                    }
                } else {
                    // Show total programs across all barangays
                    console.log('No barangay selected, showing total programs');
                    // Calculate total programs based on actual data instead of hardcoded value
                    const totalPrograms = await calculateTotalPrograms();
                    programsElement.textContent = totalPrograms;
                    programsChangeElement.textContent = 'All areas';
                }
            } else {
                console.error('Programs elements not found:', { programsElement: !!programsElement, programsChangeElement: !!programsChangeElement });
            }
        }

        // Function to update community metrics
        async function updateCommunityMetrics(barangay = '') {
            try {
                console.log('updateCommunityMetrics called with barangay:', barangay);
                console.log('Barangay type:', typeof barangay);
                console.log('Barangay truthy check:', !!barangay);
                console.log('Barangay empty check:', barangay !== '');
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to params:', barangay);
                    
                    // Add debugging for municipality filtering
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        const municipality = barangay.replace('MUNICIPALITY_', '');
                        console.log('Municipality selected:', municipality);
                        console.log('This should filter for all barangays in:', municipality);
                    }
                } else {
                    console.log('No barangay filter applied');
                }

                console.log('Final params object:', params);
                console.log('Fetching community metrics with params:', params);
                const data = await fetchDataFromAPI('community_metrics', params);
                
                if (data && data.success) {
                    console.log('Community metrics data received:', data);
                    console.log('Total screened:', data.total_screened);
                    console.log('High risk cases:', data.high_risk_cases);
                    console.log('SAM cases:', data.sam_cases);
                    console.log('Barangay filter applied:', data.barangay_filter);
                    console.log('Average risk score from API:', data.avg_risk_score);
                    
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
                    
                    // Store the average risk score globally for use in charts
                    window.globalAverageRiskScore = data.avg_risk_score || 0;
                    console.log('Stored global average risk score:', window.globalAverageRiskScore);
                    
                    // Update the risk chart center text immediately if it exists
                    const riskCenterText = document.getElementById('risk-center-text');
                    if (riskCenterText && window.globalAverageRiskScore > 0) {
                        riskCenterText.textContent = Math.round(window.globalAverageRiskScore) + '%';
                        console.log('Updated risk chart center text to:', riskCenterText.textContent);
                    }
                } else {
                    console.error('Failed to fetch community metrics:', data);
                }
            } catch (error) {
                console.error('Error updating community metrics:', error);
            }
        }

        // Function to update charts
        async function updateCharts(barangay = '') {
            try {
                console.log('updateCharts called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to chart params:', barangay);
                } else {
                    console.log('No barangay filter for charts');
                }

                console.log('Chart params object:', params);
                console.log('Updating charts with barangay filter:', barangay);

                // Update Risk Distribution Chart
                console.log('Fetching risk distribution...');
                const riskData = await fetchDataFromAPI('risk_distribution', params);
                if (riskData && riskData.success) {
                    console.log('Risk distribution data:', riskData);
                    updateRiskChart(riskData.data);
                } else {
                    console.log('Risk distribution failed or no data:', riskData);
                }

                // Update WHZ Distribution Chart
                console.log('Fetching WHZ distribution...');
                const whzData = await fetchDataFromAPI('whz_distribution', params);
                if (whzData && whzData.success) {
                    console.log('WHZ distribution data:', whzData);
                    console.log('WHZ data structure:', JSON.stringify(whzData.data, null, 2));
                    updateWHZChart(whzData.data);
                } else {
                    console.log('WHZ distribution failed or no data:', whzData);
                }

                // Update MUAC Distribution Chart
                console.log('Fetching MUAC distribution...');
                const muacData = await fetchDataFromAPI('muac_distribution', params);
                if (muacData && muacData.success) {
                    console.log('MUAC distribution data:', muacData);
                    console.log('MUAC data structure:', JSON.stringify(muacData.data, null, 2));
                    updateMUACChart(muacData.data);
                } else {
                    console.log('MUAC distribution failed or no data:', muacData);
                }
                
                // Update Nutritional Status Overview Card
                console.log('Updating Nutritional Status Overview Card...');
                updateNutritionalStatusCard(whzData?.data, muacData?.data);
            } catch (error) {
                console.error('Error updating charts:', error);
            }
        }

        // Function to update geographic distribution
        async function updateGeographicChart(barangay = '') {
            try {
                console.log('updateGeographicChart called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to geographic params:', barangay);
                } else {
                    console.log('No barangay filter for geographic chart');
                }

                console.log('Geographic chart params:', params);
                const data = await fetchDataFromAPI('geographic_distribution', params);
                if (data && data.success) {
                    console.log('Geographic data received:', data);
                    updateGeographicChartDisplay(data.data);
                } else {
                    console.log('Geographic data failed or no data:', data);
                }
            } catch (error) {
                console.error('Error updating geographic chart:', error);
            }
        }

        // Function to update geographic distribution display
        function updateGeographicChartDisplay(data) {
            const container = document.getElementById('barangay-prevalence');
            if (!container) return;

            container.innerHTML = '';
            
            if (data && data.length > 0) {
                data.forEach(item => {
                    const barItem = document.createElement('div');
                    barItem.className = 'geo-bar-item';
                    
                    // Calculate percentage based on count
                    const maxCount = Math.max(...data.map(d => d.count));
                    const percentage = maxCount > 0 ? Math.round((item.count / maxCount) * 100) : 0;
                    
                    barItem.innerHTML = `
                        <div class="geo-bar-name">${item.barangay}</div>
                        <div class="geo-bar-progress">
                            <div class="geo-bar-fill" style="width: ${percentage}%"></div>
                        </div>
                        <div class="geo-bar-percentage">${item.count}</div>
                    `;
                    container.appendChild(barItem);
                });
            } else {
                // Show no data message
                const noDataItem = document.createElement('div');
                noDataItem.style.cssText = `
                    padding: 15px;
                    text-align: center;
                    color: var(--color-text);
                    opacity: 0.7;
                    font-style: italic;
                `;
                noDataItem.textContent = 'No geographic data available for selected area';
                container.appendChild(noDataItem);
            }
        }

        // Function to update critical alerts
        async function updateCriticalAlerts(barangay = '') {
            try {
                console.log('updateCriticalAlerts called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to critical alerts params:', barangay);
                } else {
                    console.log('No barangay filter for critical alerts');
                }

                console.log('Critical alerts params:', params);
                const data = await fetchDataFromAPI('critical_alerts', params);
                if (data && data.success) {
                    console.log('Critical alerts data received:', data);
                    updateCriticalAlertsDisplay(data.data);
                } else {
                    console.log('Critical alerts failed or no data:', data);
                }
            } catch (error) {
                console.error('Error updating critical alerts:', error);
            }
        }

        // Function to generate intelligent programs (manual trigger)
        async function generateIntelligentPrograms(barangay = null) {
            // Use passed barangay parameter or fall back to currently selected barangay
            const targetBarangay = barangay !== null ? barangay : currentSelectedBarangay;
            console.log('generateIntelligentPrograms called with barangay:', targetBarangay);
            await updateIntelligentPrograms(targetBarangay);
        }

        // Function to show AI reasoning in a popup
        function showAIReasoning(title, reasoning) {
            // Create modal overlay
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            // Create modal content
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: var(--color-bg, white);
                padding: 25px;
                border-radius: 15px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 10px 30px rgba(161, 180, 84, 0.2);
                border: 1px solid rgba(161, 180, 84, 0.1);
                transform: translateY(20px);
                transition: transform 0.3s ease;
            `;
            
            modalContent.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: var(--color-highlight); font-size: 18px; font-weight: 600;">AI Reasoning</h3>
                    <button onclick="this.closest('.ai-reasoning-modal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--color-text); opacity: 0.7; transition: opacity 0.2s ease;">&times;</button>
                </div>
                <div style="margin-bottom: 15px; padding: 12px; background: rgba(161, 180, 84, 0.05); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.1);">
                    <strong style="color: var(--color-highlight); font-size: 14px;">Program:</strong>
                    <span style="color: var(--color-text); margin-left: 8px; font-weight: 500;">${title}</span>
                </div>
                <div style="background: rgba(161, 180, 84, 0.08); padding: 18px; border-radius: 12px; border-left: 4px solid var(--color-highlight); box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <p style="margin: 0; color: var(--color-text); line-height: 1.6; font-style: italic; font-size: 14px;">${reasoning}</p>
                </div>
                <div style="text-align: center; margin-top: 25px;">
                    <button onclick="this.closest('.ai-reasoning-modal').remove()" style="background: linear-gradient(135deg, var(--color-highlight), var(--color-accent1)); color: white; border: none; padding: 12px 24px; border-radius: 25px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);">
                        Close
                    </button>
                </div>
            `;
            
            // Add modal to page
            modal.appendChild(modalContent);
            modal.className = 'ai-reasoning-modal';
            document.body.appendChild(modal);
            
            // Animate in
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.style.transform = 'translateY(0)';
            }, 10);
            
            // Close on background click
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Function to update intelligent programs
        async function updateIntelligentPrograms(barangay = '') {
            try {
                console.log('updateIntelligentPrograms called with barangay:', barangay);
                
                // Show loading state
                const loadingElement = document.getElementById('programs-loading');
                const programsContainer = document.getElementById('intelligent-program-cards');
                
                if (loadingElement && programsContainer) {
                    loadingElement.style.display = 'flex';
                    programsContainer.style.display = 'none';
                }
                
                const params = {};
                if (barangay && barangay !== '') {
                    if (barangay.startsWith('MUNICIPALITY_')) {
                        params.municipality = barangay.replace('MUNICIPALITY_', '');
                    } else {
                        params.barangay = barangay;
                    }
                    console.log('Added location to intelligent programs params:', params);
                } else {
                    console.log('No location filter for intelligent programs');
                }

                console.log('Intelligent programs params:', params);
                const data = await fetchDataFromAPI('intelligent_programs', params);
                
                if (data && data.success) {
                    console.log('Intelligent programs data received:', data);
                    updateIntelligentProgramsDisplay(data.programs, data.data_analysis);
                } else {
                    console.log('Intelligent programs failed or no data:', data);
                    // Show appropriate no-data message
                    showFallbackPrograms();
                }
            } catch (error) {
                console.error('Error updating intelligent programs:', error);
                // Show fallback programs on error
                showFallbackPrograms();
            }
        }

        // Function to update intelligent programs display
        function updateIntelligentProgramsDisplay(programs, analysis) {
            const loadingElement = document.getElementById('programs-loading');
            const programsContainer = document.getElementById('intelligent-program-cards');
            
            if (!loadingElement || !programsContainer) {
                console.error('Intelligent programs elements not found');
                return;
            }
            
            // Hide loading, show programs
            loadingElement.style.display = 'none';
            programsContainer.style.display = 'block';
            
            // Clear existing programs
            programsContainer.innerHTML = '';
            
            // Check if this is a no-data response
            if (analysis && analysis.no_data) {
                // Show no-data message
                const noDataCard = document.createElement('div');
                noDataCard.className = 'program-card';
                noDataCard.style.cssText = 'text-align: center; padding: 40px 20px; opacity: 0.8;';
                
                noDataCard.innerHTML = `
                    <div style="font-size: 48px; margin-bottom: 20px;">📊</div>
                    <div style="font-size: 18px; font-weight: 600; color: var(--color-highlight); margin-bottom: 10px;">
                        No Data Available
                    </div>
                    <div style="font-size: 14px; color: var(--color-text); opacity: 0.8; line-height: 1.5;">
                        ${analysis.message || 'No users found in the selected area. Programs will be generated once users are registered.'}
                    </div>
                    <div style="margin-top: 20px; padding: 12px; background: rgba(161, 180, 84, 0.1); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.2);">
                        <div style="font-size: 12px; color: var(--color-text); opacity: 0.7;">
                            <strong>Tip:</strong> Register users in this area to generate intelligent nutrition programs based on real data.
                        </div>
                    </div>
                `;
                
                programsContainer.appendChild(noDataCard);
                console.log('Displayed no-data message');
                return;
            }
            
            if (programs && programs.length > 0) {
                programs.forEach((program, index) => {
                    const programCard = createProgramCard(program, index);
                    programsContainer.appendChild(programCard);
                });
                
                console.log(`Generated ${programs.length} intelligent program cards`);
            } else {
                console.log('No programs to display');
                showFallbackPrograms();
            }
        }

        // Function to create individual program card
        function createProgramCard(program, index) {
            const card = document.createElement('div');
            card.className = 'program-card';
            card.style.cssText = 'margin-bottom: 8px; padding: 10px; font-size: 0.9em;';
            
            // Determine priority class
            let priorityClass = 'priority-medium';
            if (program.priority === 'Critical') priorityClass = 'priority-immediate';
            else if (program.priority === 'High') priorityClass = 'priority-high';
            
            card.innerHTML = `
                <div class="program-icon">${program.icon}</div>
                <div class="program-content">
                    <div class="program-title">${program.title}</div>
                    <div style="font-size: 10px; color: var(--color-highlight); margin-bottom: 8px; display: flex; align-items: center; gap: 4px;">
                        📍 <strong>Targeting:</strong> ${program.location}
                    </div>
                    <div class="program-description">${program.description}</div>
                    <div class="program-meta">
                        <span class="priority-tag ${priorityClass}">${program.priority}</span>
                        <div class="program-details" style="margin-top: 6px; font-size: 11px; opacity: 0.8;">
                            <div><strong>Type:</strong> ${program.type}</div>
                            <div><strong>Duration:</strong> ${program.duration}</div>
                            <div><strong>Target:</strong> ${program.target_audience}</div>
                            <div style="background: rgba(161, 180, 84, 0.2); padding: 4px 8px; border-radius: 6px; margin-top: 4px; border-left: 3px solid var(--color-highlight);">
                                <strong style="color: var(--color-highlight);">🎯 Target Location:</strong> ${program.location}
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; margin-top: 8px;">
                            <button class="show-reasoning-btn" onclick="showAIReasoning('${program.title}', '${program.reasoning}')">
                                Show AI Reasoning
                            </button>
                            <button class="create-this-program-btn" onclick="createProgramFromCard('${program.title}', '${program.type}', '${program.location}', '${program.description}', '${program.priority}')">
                                Create This Program
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add smooth animation delay
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            card.style.transition = 'all 0.3s ease';
            
            // Use requestAnimationFrame for smoother animation
            requestAnimationFrame(() => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            return card;
        }



        // Function to show fallback programs when API fails
        function showFallbackPrograms() {
            const loadingElement = document.getElementById('programs-loading');
            const programsContainer = document.getElementById('intelligent-program-cards');
            
            if (!loadingElement || !programsContainer) return;
            
            loadingElement.style.display = 'none';
            programsContainer.style.display = 'block';
            
            // Show appropriate message based on whether we have data or not
            const noDataCard = document.createElement('div');
            noDataCard.className = 'program-card';
            noDataCard.style.cssText = 'text-align: center; padding: 40px 20px; opacity: 0.8;';
            
            noDataCard.innerHTML = `
                <div style="font-size: 48px; margin-bottom: 20px;">📊</div>
                <div style="font-size: 18px; font-weight: 600; color: var(--color-highlight); margin-bottom: 10px;">
                    No Community Data Available
                </div>
                <div style="font-size: 14px; color: var(--color-text); opacity: 0.8; line-height: 1.5;">
                    The intelligent programs system requires community data to generate targeted nutrition interventions. 
                    Programs will be automatically generated once users are registered and screened in the selected area.
                </div>
                <div style="margin-top: 20px; padding: 12px; background: rgba(161, 180, 84, 0.1); border-radius: 8px; border: 1px solid rgba(161, 180, 84, 0.2);">
                    <div style="font-size: 12px; color: var(--color-text); opacity: 0.7;">
                        <strong>Next Steps:</strong> Register users in this area to enable intelligent program generation based on real community health data.
                    </div>
                </div>
            `;
            
            programsContainer.innerHTML = '';
            programsContainer.appendChild(noDataCard);
            
            console.log('Showing no-data message instead of fallback programs');
        }

        // Function to update critical alerts display
        function updateCriticalAlertsDisplay(data) {
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

        // Function to update analysis section
        async function updateAnalysisSection(barangay = '') {
            try {
                console.log('updateAnalysisSection called with barangay:', barangay);
                
                const params = {};
                if (barangay && barangay !== '') {
                    params.barangay = barangay;
                    console.log('Added barangay to analysis params:', barangay);
                } else {
                    console.log('No barangay filter for analysis section');
                }

                console.log('Analysis section params:', params);
                const data = await fetchDataFromAPI('analysis_data', params);
                
                if (data && data.success) {
                    console.log('Analysis data received:', data);
                    // Update risk analysis
                    updateRiskAnalysis(data.risk_analysis);
                    
                    // Update demographics
                    updateDemographics(data.demographics);
                } else {
                    console.log('Analysis data failed or no data:', data);
                }
            } catch (error) {
                console.error('Error updating analysis section:', error);
            }
        }

        // Function to update risk analysis
        function updateRiskAnalysis(data) {
            if (!data) return;
            
            const totalUsers = data.total_users || 0;
            const atRiskUsers = data.at_risk_users || 0;
            
            // Calculate percentages
            const highRiskPercent = totalUsers > 0 ? Math.round((atRiskUsers / totalUsers) * 100) : 0;
            const moderateRiskPercent = totalUsers > 0 ? Math.round(((totalUsers - atRiskUsers) * 0.6 / totalUsers) * 100) : 0;
            const lowRiskPercent = totalUsers > 0 ? Math.round(((totalUsers - atRiskUsers) * 0.4 / totalUsers) * 100) : 0;
            const samPercent = totalUsers > 0 ? Math.round((atRiskUsers * 0.3 / totalUsers) * 100) : 0;
            
            // Update progress bars
            const highRiskBar = document.getElementById('high-risk-bar');
            const moderateRiskBar = document.getElementById('moderate-risk-bar');
            const lowRiskBar = document.getElementById('low-risk-bar');
            const samBar = document.getElementById('sam-bar');
            
            if (highRiskBar) {
                highRiskBar.style.width = highRiskPercent + '%';
                document.getElementById('high-risk-percent').textContent = highRiskPercent + '%';
            }
            
            if (moderateRiskBar) {
                moderateRiskBar.style.width = moderateRiskPercent + '%';
                document.getElementById('moderate-risk-percent').textContent = moderateRiskPercent + '%';
            }
            
            if (lowRiskBar) {
                lowRiskBar.style.width = lowRiskPercent + '%';
                document.getElementById('low-risk-percent').textContent = lowRiskPercent + '%';
            }
            
            if (samBar) {
                samBar.style.width = samPercent + '%';
                document.getElementById('sam-percent').textContent = samPercent + '%';
            }
        }

        // Function to update demographics
        function updateDemographics(data) {
            if (!data) return;
            
            const totalUsers = data.total_users || 0;
            
            if (totalUsers > 0) {
                // Calculate percentages for age groups
                const childrenPercent = Math.round(((data.age_0_5 || 0) + (data.age_6_12 || 0) + (data.age_13_17 || 0)) / totalUsers * 100);
                const adultsPercent = Math.round((data.age_18_59 || 0) / totalUsers * 100);
                const elderlyPercent = Math.round((data.age_60_plus || 0) / totalUsers * 100);
                
                // Update progress bars
                const childrenBar = document.getElementById('children-bar');
                const adultsBar = document.getElementById('adults-bar');
                const elderlyBar = document.getElementById('elderly-bar');
                const genderBar = document.getElementById('gender-bar');
                
                if (childrenBar) {
                    childrenBar.style.width = childrenPercent + '%';
                    document.getElementById('children-percent').textContent = childrenPercent + '%';
                }
                
                if (adultsBar) {
                    adultsBar.style.width = adultsPercent + '%';
                    document.getElementById('adults-percent').textContent = adultsPercent + '%';
                }
                
                if (elderlyBar) {
                    elderlyBar.style.width = elderlyPercent + '%';
                    document.getElementById('elderly-percent').textContent = elderlyPercent + '%';
                }
                
                if (genderBar) {
                    // For now, show a balanced distribution
                    genderBar.style.width = '50%';
                    document.getElementById('gender-distribution').textContent = '50% M, 50% F';
                }
            }
        }

        // Time frame button handling
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Setting up time frame button handlers...');
            const timeButtons = document.querySelectorAll('.time-btn');
            console.log('Found time buttons:', timeButtons.length);
            
            if (timeButtons.length === 0) {
                console.error('No time buttons found!');
            } else {
                timeButtons.forEach((button, index) => {
                    console.log(`Setting up time button ${index}:`, button.textContent);
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
            }
            
            console.log('Time frame button handlers setup complete');
        });

        // Function to update dashboard data based on time frame
        function updateDashboardForTimeFrame(timeFrame) {
            console.log('=== UPDATING DASHBOARD FOR TIME FRAME ===');
            console.log('Time frame parameter:', timeFrame);
            console.log('Time frame type:', typeof timeFrame);
            
            // You can implement time-based data filtering here
            // For example, showing data for the last day, week, month, etc.
            
            console.log('Time frame update complete (placeholder implementation)');
            console.log('=== TIME FRAME UPDATE COMPLETE ===');
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
                console.log(`Full URL: ${url}`);
                
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
                let actualRiskScores = []; // Store actual risk scores from API
                
                if (data && data.length > 0) {
                    // API returns data in format: [{label: 'Low Risk', value: 1, color: '#4CAF50', risk_score: 51}, ...]
                    // OR: [{label: 'Low Risk', value: 1, color: '#4CAF50', risk_scores: [51, 45, 38]}, ...]
                    // The risk_score field should contain the actual risk percentage (0-100) for each user
                    data.forEach(item => {
                        totalUsers += item.value;
                        
                        // Map the label to the correct index
                        if (item.label === 'Low Risk') riskLevels[0] = item.value;
                        else if (item.label === 'Moderate Risk') riskLevels[1] = item.value;
                        else if (item.label === 'High Risk') riskLevels[2] = item.value;
                        else if (item.label === 'Critical Risk') riskLevels[3] = item.value;
                        
                        // Store actual risk scores for each user
                        if (item.risk_score !== undefined) {
                            // If risk_score is a single value, repeat it for each user
                            for (let i = 0; i < item.value; i++) {
                                actualRiskScores.push(item.risk_score);
                            }
                        } else if (item.risk_scores && Array.isArray(item.risk_scores)) {
                            // If risk_scores is an array, add all scores
                            actualRiskScores.push(...item.risk_scores);
                        }
                    });
                }
                
                // If no data from API, create sample data for demonstration
                if (totalUsers === 0) {
                    // Create sample data based on typical distribution
                    riskLevels = [3, 4, 3, 2]; // Sample distribution
                    totalUsers = 12;
                    
                    // Create sample risk scores for demonstration
                    actualRiskScores = [25, 35, 45, 55, 65, 75, 85, 95, 15, 30, 40, 50];
                    
                    // Show "No Data" message in center
                    centerText.textContent = 'No Data';
                    centerText.style.color = '#999';
                    
                    // Apply fallback gradient
                    chartBg.style.background = 'conic-gradient(#e0e0e0 0% 100%)';
                    
                    // Clear segments
                    segments.innerHTML = '<div style="text-align: center; color: #999; font-style: italic;">No data available for selected area</div>';
                    
                    // Restore opacity and return
                    chartBg.style.opacity = '1';
                    return;
                }
                
                // Calculate both: percentage of users at risk AND average risk score
                let atRiskPercentage = 0;
                let averageRisk = 0;
                
                if (totalUsers > 0) {
                    // Count users in moderate, high, and severe risk
                    const atRiskUsers = riskLevels[1] + riskLevels[2] + riskLevels[3];
                    atRiskPercentage = Math.round((atRiskUsers / totalUsers) * 100);
                    
                    // Use the global average risk score from community metrics (more accurate)
                    if (window.globalAverageRiskScore !== undefined && window.globalAverageRiskScore > 0) {
                        averageRisk = Math.round(window.globalAverageRiskScore);
                        console.log('Using global average risk score from community metrics:', averageRisk);
                    } else if (actualRiskScores.length > 0) {
                        // Fallback to actual risk scores from chart data if available
                        const sum = actualRiskScores.reduce((total, score) => total + score, 0);
                        averageRisk = Math.round(sum / actualRiskScores.length);
                        console.log('Using actual risk scores from chart data:', actualRiskScores);
                        console.log('Calculated average from actual scores:', averageRisk);
                    } else {
                        // Final fallback to weighted average if no actual scores available
                        // For 1 user with 100% risk, this should give 100
                        const weightedSum = (riskLevels[0] * 12.5) + (riskLevels[1] * 37.5) + (riskLevels[2] * 62.5) + (riskLevels[3] * 100);
                        averageRisk = Math.round(weightedSum / totalUsers);
                        console.log('Using fallback weighted average calculation');
                        console.log('Note: API should provide risk_score field for accurate calculations');
                    }
                }
                
                console.log('Risk levels distribution:', riskLevels);
                console.log('Total users:', totalUsers);
                console.log('At risk percentage:', atRiskPercentage);
                console.log('Average risk score:', averageRisk);
                console.log('Global average risk score available:', window.globalAverageRiskScore);
                console.log('Actual risk scores from API:', actualRiskScores);
                console.log('Raw API data received:', data);
                
                // Update center text with average risk score (this is what you want)
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
                segments.innerHTML = ''; // Clear existing segments
                riskLevels.forEach((count, index) => {
                    if (count > 0) {
                        const percentage = (count / totalUsers) * 100;
                        
                        // Create new segment
                        const segmentDiv = document.createElement('div');
                        segmentDiv.className = 'segment';
                        segmentDiv.setAttribute('data-risk-level', index);
                        segmentDiv.style.opacity = '0';
                        
                        // Update segment content
                        segmentDiv.innerHTML = `
                            <span class="color-indicator" style="background-color: ${colors[index]}"></span>
                            <span>${labels[index]}: ${count} (${percentage.toFixed(1)}%)</span>
                        `;
                        
                        segments.appendChild(segmentDiv);
                        
                        // Fade in the segment
                        setTimeout(() => {
                            segmentDiv.style.opacity = '1';
                        }, 100);
                        
                        console.log(`Updated segment: ${labels[index]} - ${count} (${percentage.toFixed(1)}%)`);
                    }
                });
                
                // Restore full opacity after update is complete
                setTimeout(() => {
                    chartBg.style.opacity = '1';
                }, 500);
                
                console.log('Risk chart updated successfully');
                
            } catch (error) {
                console.error('Error updating risk chart:', error);
            }
        }

        // Function to update WHZ chart (Bar Chart)
        function updateWHZChart(data) {
            console.log('updateWHZChart called with data:', data);
            
            const chart = document.getElementById('whz-bar-chart');
            if (!chart) {
                console.error('WHZ bar chart element not found');
                return;
            }

            if (data && data.length > 0) {
                // Clear existing bars
                chart.innerHTML = '';
                
                // Create bars for each category
                data.forEach((item, index) => {
                    const bar = document.createElement('div');
                    bar.className = 'bar';
                    
                    // Calculate bar height based on value
                    const maxValue = Math.max(...data.map(d => d.value));
                    const height = Math.max(20, (item.value / maxValue) * 160); // Min height 20px, max 160px
                    
                    bar.style.height = `${height}px`;
                    bar.style.backgroundColor = item.color || 'var(--color-highlight)';
                    
                    // Add bar value and label
                    const barValue = document.createElement('div');
                    barValue.className = 'bar-value';
                    barValue.textContent = item.value;
                    
                    const barLabel = document.createElement('div');
                    barLabel.className = 'bar-label';
                    barLabel.textContent = item.label;
                    
                    bar.appendChild(barValue);
                    bar.appendChild(barLabel);
                    chart.appendChild(bar);
                });
                
                // Show the chart
                chart.style.opacity = '1';
                console.log('WHZ bar chart updated successfully');
            } else {
                // Hide chart if no data
                chart.style.opacity = '0.3';
                console.log('WHZ chart hidden due to no data');
            }
        }

        // Function to update MUAC chart
        function updateMUACChart(data) {
            console.log('updateMUACChart called with data:', data);
            
            const chart = document.getElementById('muac-distribution-chart');
            if (!chart) {
                console.error('MUAC chart element not found');
                return;
            }

            const linePath = chart.querySelector('#muac-line-path');
            const lineArea = chart.querySelector('#muac-line-area');
            
            if (linePath && lineArea) {
                // Create path data for MUAC distribution
                const pathData = createLinePath(data);
                console.log('MUAC path data created:', pathData);
                
                if (pathData && pathData.path) {
                    linePath.setAttribute('d', pathData.path);
                    lineArea.setAttribute('d', pathData.area);
                    
                    // Add animation (like dashold.php)
                    linePath.style.strokeDashoffset = '0';
                    lineArea.style.clipPath = 'polygon(0 100%, 0 0, 100% 0, 100% 100%)';
                    
                    // Show the chart
                    chart.style.opacity = '1';
                    
                    // Debug: Check if paths are visible
                    console.log('MUAC linePath element:', linePath);
                    console.log('MUAC lineArea element:', lineArea);
                    console.log('MUAC path d attribute:', linePath.getAttribute('d'));
                    console.log('MUAC area d attribute:', lineArea.getAttribute('d'));
                    
                    console.log('MUAC chart updated successfully');
                } else {
                    // Hide chart if no data
                    chart.style.opacity = '0.3';
                    console.log('MUAC chart hidden due to no data');
                }
            } else {
                console.error('MUAC chart path elements not found');
            }
        }

        // Function to update Nutritional Status Overview Card
        function updateNutritionalStatusCard(whzData, muacData) {
            console.log('updateNutritionalStatusCard called with:', { whzData, muacData });
            
            try {
                // Update WHZ Categories
                if (whzData && whzData.length > 0) {
                    whzData.forEach(item => {
                        // Map the labels to the new IDs
                        let elementId = '';
                        if (item.label === 'Severe Acute Malnutrition') elementId = 'whz-sam-count';
                        else if (item.label === 'Moderate Acute Malnutrition') elementId = 'whz-mam-count';
                        else if (item.label === 'Normal Growth') elementId = 'whz-normal-count';
                        else if (item.label === 'Overweight') elementId = 'whz-overweight-count';
                        
                        const element = document.getElementById(elementId);
                        if (element) {
                            element.textContent = item.value || 0;
                        }
                    });
                }
                
                // Update MUAC Distribution
                if (muacData && muacData.length > 0) {
                    muacData.forEach(item => {
                        // Map the labels to the new IDs
                        let elementId = '';
                        if (item.label === 'Normal') elementId = 'muac-normal-count';
                        else if (item.label === 'MAM') elementId = 'muac-mam-count';
                        else if (item.label === 'SAM') elementId = 'muac-sam-count';
                        
                        const element = document.getElementById(elementId);
                        if (element) {
                            element.textContent = item.value || 0;
                        }
                        
                        // Update MUAC progress bars
                        let fillId = '';
                        if (item.label === 'Normal') fillId = 'muac-normal-fill';
                        else if (item.label === 'MAM') fillId = 'muac-mam-fill';
                        else if (item.label === 'SAM') fillId = 'muac-sam-fill';
                        
                        const fillElement = document.getElementById(fillId);
                        if (fillElement) {
                            // Calculate percentage based on total
                            const total = muacData.reduce((sum, d) => sum + d.value, 0);
                            const percentage = total > 0 ? (item.value / total) * 100 : 0;
                            fillElement.style.width = `${percentage}%`;
                        }
                    });
                }
                
                // Update Summary Statistics
                updateNutritionalSummary(whzData, muacData);
                
                console.log('Nutritional Status Overview Card updated successfully');
            } catch (error) {
                console.error('Error updating Nutritional Status Overview Card:', error);
            }
        }
        
        // Function to update Nutritional Summary
        function updateNutritionalSummary(whzData, muacData) {
            try {
                // Calculate Total At Risk (SAM + MAM from both WHZ and MUAC)
                let totalAtRisk = 0;
                let totalCases = 0;
                
                if (whzData) {
                    whzData.forEach(item => {
                        if (item.label === 'Severe Acute Malnutrition' || item.label === 'Moderate Acute Malnutrition') {
                            totalAtRisk += item.value || 0;
                        }
                        totalCases += item.value || 0;
                    });
                }
                
                if (muacData) {
                    muacData.forEach(item => {
                        if (item.label === 'SAM' || item.label === 'MAM') {
                            totalAtRisk += item.value || 0;
                        }
                        totalCases += item.value || 0;
                    });
                }
                
                // Update Total At Risk
                const totalAtRiskElement = document.getElementById('total-at-risk');
                if (totalAtRiskElement) {
                    totalAtRiskElement.textContent = totalAtRisk;
                }
                
                // Update Intervention Priority
                const interventionPriorityElement = document.getElementById('intervention-priority');
                if (interventionPriorityElement) {
                    let priority = 'Low';
                    if (totalAtRisk > 0) {
                        const riskPercentage = (totalAtRisk / totalCases) * 100;
                        if (riskPercentage >= 20) priority = 'Critical';
                        else if (riskPercentage >= 10) priority = 'High';
                        else if (riskPercentage >= 5) priority = 'Medium';
                    }
                    interventionPriorityElement.textContent = priority;
                }
                
                // Update Recovery Rate (placeholder - you can implement actual recovery tracking)
                const recoveryRateElement = document.getElementById('recovery-rate');
                if (recoveryRateElement) {
                    // This would typically come from historical data
                    // For now, showing a placeholder
                    recoveryRateElement.textContent = '85%';
                }
                
            } catch (error) {
                console.error('Error updating Nutritional Summary:', error);
            }
        }

        // Helper function to create line path (copied from dashold.php design)
        function createLinePath(data) {
            console.log('createLinePath called with data:', data);
            
            if (!data || data.length === 0) {
                console.log('No data provided to createLinePath, returning empty string');
                return '';
            }
            
            const width = 1000;
            const height = 500;
            
            console.log('Chart dimensions:', { width, height });
            
            const maxValue = Math.max(...data.map(d => d.value));
            
            // Handle single data point case
            if (data.length === 1) {
                const x = width / 2; // Center horizontally
                const y = height - (data[0].value / maxValue) * height;
                const path = `M ${x},${y} L ${x},${y}`; // Single point as small line
                console.log('Single data point path:', path);
                return path;
            }
            
            const xStep = width / (data.length - 1);
            const yScale = height / maxValue;
            
            console.log('Path calculation:', { maxValue, xStep, yScale, dataLength: data.length });
            
            // Create path from left to right (like dashold.php)
            let pathString = `M 0,${height - data[0].value * yScale}`;
            let areaString = `M 0,${height - data[0].value * yScale}`;
            
            for (let i = 1; i < data.length; i++) {
                const x = i * xStep;
                const y = height - data[i].value * yScale;
                pathString += ` L ${x},${y}`;
                areaString += ` L ${x},${y}`;
            }
            
            // Close the area path to the bottom (like dashold.php)
            areaString += ` L ${width},${height} L 0,${height} Z`;
            
            console.log('Generated path:', pathString);
            console.log('Generated area:', areaString);
            
            return { path: pathString, area: areaString };
        }

        // Initialize dashboard on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DASHBOARD INITIALIZATION STARTED ===');
            console.log('Dashboard initialized - DOM loaded');
            
            // Initialize global average risk score
            window.globalAverageRiskScore = 0;
            console.log('Initialized global average risk score:', window.globalAverageRiskScore);
            
            // Check if key DOM elements exist
            console.log('Checking key DOM elements...');
            const keyElements = {
                'community-total-screened': document.getElementById('community-total-screened'),
                'community-high-risk': document.getElementById('community-high-risk'),
                'community-sam-cases': document.getElementById('community-sam-cases'),
                'programs-in-barangay': document.getElementById('programs-in-barangay'),
                'risk-chart-bg': document.getElementById('risk-chart-bg'),
                'risk-center-text': document.getElementById('risk-center-text'),
                'risk-segments': document.getElementById('risk-segments'),
                'critical-alerts': document.getElementById('critical-alerts'),
                'barangay-prevalence': document.getElementById('barangay-prevalence'),
                'theme-toggle': document.getElementById('theme-toggle'),
                'dropdown-content': document.getElementById('dropdown-content'),
                'selected-option': document.getElementById('selected-option'),
                'dropdown-arrow': document.querySelector('.dropdown-arrow'),
                'search-input': document.getElementById('search-input')
            };
            
            console.log('Key DOM elements status:', keyElements);
            
            // Check dropdown elements specifically
            console.log('Checking dropdown elements...');
            const dropdownElements = {
                'custom-select-container': document.querySelector('.custom-select-container'),
                'options-container': document.querySelector('.options-container'),
                'option-items': document.querySelectorAll('.option-item')
            };
            
            console.log('Dropdown elements status:', dropdownElements);
            
            // Test API connection first
            console.log('Testing API connection...');
            testAPIConnection();
            
            // Restore selected barangay from localStorage if available
            const barangayRestored = restoreSelectedBarangay();
            
            // Load initial data with error handling
            try {
                console.log('Loading initial dashboard data...');
                
                if (barangayRestored && currentSelectedBarangay) {
                    console.log('Loading data for previously selected barangay:', currentSelectedBarangay);
                    updateDashboardForBarangay(currentSelectedBarangay);
                } else {
                    console.log('Loading data for: All Barangays (default)');
                    
                    console.log('Calling updateCommunityMetrics()...');
                    updateCommunityMetrics();
                    
                    console.log('Calling updateCharts()...');
                    updateCharts();
                    
                    console.log('Calling updateGeographicChart()...');
                    updateGeographicChart();
                    
                    console.log('Calling updateCriticalAlerts()...');
                    updateCriticalAlerts();
                    
                    console.log('Calling updateAnalysisSection()...');
                    updateAnalysisSection();
                    
                    console.log('Auto-generating intelligent programs...');
                    generateIntelligentPrograms(currentSelectedBarangay);
                }
                
                console.log('Initial data loading complete');
            } catch (error) {
                console.error('Error loading initial data:', error);
            }
            
            // Set up auto-refresh every 3 seconds with seamless updates
            console.log('Setting up auto-refresh every 3 seconds...');
            setInterval(() => {
                try {
                    console.log('Auto-refresh triggered');
                    // Use requestAnimationFrame for smooth, seamless updates
                    requestAnimationFrame(() => {
                        console.log('Auto-refresh: updating community metrics...');
                        updateCommunityMetrics(currentSelectedBarangay);
                        console.log('Auto-refresh: updating charts...');
                        updateCharts(currentSelectedBarangay);
                        console.log('Auto-refresh complete');
                    });
                } catch (error) {
                    console.error('Error in auto-refresh:', error);
                }
            }, 3000);
            console.log('Auto-refresh setup complete');
            
            console.log('=== DASHBOARD INITIALIZATION COMPLETE ===');
            
            // Test intelligent programs API connection
            console.log('Testing intelligent programs API connection...');
            testIntelligentProgramsAPI();
        });
        
        // Theme toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Setting up theme toggle...');
            const themeToggle = document.getElementById('theme-toggle');
            if (themeToggle) {
                console.log('Theme toggle element found, setting up click handler');
                themeToggle.addEventListener('click', function() {
                    console.log('Theme toggle clicked');
                    const body = document.body;
                    const themeIcon = this.querySelector('.theme-icon');
                    
                    if (body.classList.contains('dark-theme')) {
                        console.log('Switching to light theme');
                        body.classList.remove('dark-theme');
                        body.classList.add('light-theme');
                        themeIcon.textContent = '☀️';
                        this.title = 'Switch to dark theme';
                    } else {
                        console.log('Switching to dark theme');
                        body.classList.remove('light-theme');
                        body.classList.add('dark-theme');
                        themeIcon.textContent = '🌙';
                        this.title = 'Switch to dark theme';
                    }
                });
                console.log('Theme toggle handler setup complete');
            } else {
                console.error('Theme toggle element not found');
            }
        });
        
        // Additional debugging for dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== ADDITIONAL DROPDOWN DEBUGGING ===');
            
            // Check if dropdown elements exist after a short delay
            setTimeout(() => {
                console.log('Checking dropdown elements after delay...');
                
                const dropdownContent = document.getElementById('dropdown-content');
                const optionItems = document.querySelectorAll('.option-item');
                const selectHeader = document.querySelector('.select-header');
                
                console.log('Dropdown elements after delay:', {
                    dropdownContent: !!dropdownContent,
                    optionItemsCount: optionItems.length,
                    selectHeader: !!selectHeader
                });
                
                if (dropdownContent) {
                    console.log('Dropdown content HTML:', dropdownContent.innerHTML);
                }
                
                if (optionItems.length > 0) {
                    console.log('First few option items:');
                    // Convert NodeList to Array before using slice
                    Array.from(optionItems).slice(0, 3).forEach((item, index) => {
                        console.log(`Option ${index}:`, { 
                            text: item.textContent, 
                            value: item.getAttribute('data-value'), 
                            classes: item.className 
                        });
                    });
                }
                
                // Try to find any elements with data-value attribute
                const allDataValueElements = document.querySelectorAll('[data-value]');
                console.log('All elements with data-value attribute:', allDataValueElements.length);
                
                if (allDataValueElements.length > 0) {
                    console.log('First few data-value elements:');
                    // Convert NodeList to Array before using slice
                    Array.from(allDataValueElements).slice(0, 5).forEach((item, index) => {
                        console.log(`Data-value element ${index}:`, { 
                            text: item.textContent, 
                            value: item.getAttribute('data-value'), 
                            tagName: item.tagName,
                            classes: item.className 
                        });
                    });
                }
                
                // After checking elements, try to restore barangay selection if it was delayed
                if (currentSelectedBarangay && !document.querySelector('.option-item.selected')) {
                    console.log('Attempting to restore barangay selection after delay...');
                    restoreSelectedBarangay();
                }
                
                // If we still have a selected barangay but no visual indication, update the dashboard
                if (currentSelectedBarangay && currentSelectedBarangay !== '') {
                    console.log('Ensuring dashboard reflects current barangay selection...');
                    updateDashboardForBarangay(currentSelectedBarangay);
                }
                
                console.log('=== DROPDOWN DEBUGGING COMPLETE ===');
            }, 1000);
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
                    console.log('API connection successful');
                } else {
                    console.error('API not responding properly');
                }
            } catch (error) {
                console.error('API Connection failed:', error);
                console.log('Please check if unified_api.php exists and XAMPP is running');
            }
        }
        
        // Test municipality filtering
        async function testMunicipalityFiltering(barangay) {
            console.log('Testing municipality filtering for:', barangay);
            try {
                const response = await fetch(`http://localhost/thesis355/unified_api.php?endpoint=test_municipality&barangay=${encodeURIComponent(barangay)}`);
                if (response.ok) {
                    const data = await response.json();
                    console.log('Municipality test response:', data);
                    
                    if (data.success) {
                        console.log('Municipality test successful');
                        console.log('Is municipality:', data.is_municipality);
                        console.log('Municipality name:', data.municipality_name);
                        console.log('Barangays in municipality:', data.barangays_in_municipality);
                        console.log('Total users in municipality:', data.total_users_in_municipality);
                        
                        if (data.sql_query) {
                            console.log('SQL query used:', data.sql_query);
                            console.log('SQL parameters:', data.params);
                        }
                    }
                } else {
                    console.error('Municipality test failed');
                }
            } catch (error) {
                console.error('Error testing municipality filtering:', error);
            }
        }
        
        // Test intelligent programs API
        async function testIntelligentProgramsAPI() {
            console.log('Testing intelligent programs API...');
            try {
                // Test general endpoint
                const response = await fetch('http://localhost/thesis355/unified_api.php?endpoint=intelligent_programs');
                if (response.ok) {
                    const data = await response.json();
                    console.log('Intelligent programs API test successful:', data);
                    
                    if (data.success && data.programs) {
                        console.log(`API returned ${data.programs.length} programs`);
                        console.log('Sample program:', data.programs[0]);
                    } else {
                        console.warn('API returned success but no programs');
                    }
                } else {
                    console.error('Intelligent programs API test failed:', response.status);
                }
                
                // Test with barangay parameter
                const barangayResponse = await fetch('http://localhost/thesis355/unified_api.php?endpoint=intelligent_programs&barangay=Bangkal');
                if (barangayResponse.ok) {
                    const barangayData = await barangayResponse.json();
                    console.log('Barangay-specific programs test:', barangayData);
                }
                
            } catch (error) {
                console.error('Error testing intelligent programs API:', error);
            }
        }

        // Test user data consistency
        async function testUserDataConsistency(email) {
            console.log('Testing user data consistency for:', email);
            try {
                const response = await fetch(`http://localhost/thesis355/unified_api.php?endpoint=check_user_data&email=${encodeURIComponent(email)}`);
                if (response.ok) {
                    const data = await response.json();
                    console.log('User data consistency test response:', data);
                    
                    if (data.success) {
                        console.log('User data consistency test successful');
                        console.log('Individual column barangay:', data.user_data.barangay);
                        console.log('Screening answers barangay:', data.screening_answers_parsed.barangay);
                        console.log('Barangay match:', data.comparison.barangay_match);
                        
                        if (!data.comparison.barangay_match) {
                            console.warn('BARANGAY MISMATCH DETECTED!');
                            console.warn('Individual column:', data.user_data.barangay);
                            console.warn('Screening answers:', data.screening_answers_parsed.barangay);
                        }
                    }
                } else {
                    console.error('User data consistency test failed');
                }
            } catch (error) {
                console.error('Error testing user data consistency:', error);
            }
        }

        // Function to create new program - redirects to event.php in same tab
        function createNewProgram() {
            console.log('createNewProgram called');
            // Redirect to event.php in the same tab
            window.location.href = 'event.php';
        }

        // Function to create program from card - redirects to event.php with pre-filled data
        function createProgramFromCard(title, type, location, description, urgency) {
            console.log('createProgramFromCard called with:', { title, type, location, description, urgency });
            
            // Create URL parameters with proper encoding
            const params = new URLSearchParams({
                program: title,
                type: type,
                location: location,
                description: description,
                urgency: urgency
            });
            
            const url = `event.php?${params.toString()}`;
            console.log('Redirecting to:', url);
            
            // Redirect to event.php in the same tab with pre-filled form data
            window.location.href = url;
        }

        // Function to test risk calculation (for debugging)
        function testRiskCalculation() {
            console.log('=== TESTING RISK CALCULATION ===');
            console.log('Global average risk score:', window.globalAverageRiskScore);
            
            // Test with sample data
            const sampleData = [
                { label: 'Low Risk', value: 1, risk_scores: [51] }
            ];
            
            console.log('Sample data:', sampleData);
            
            // Simulate the risk calculation
            let actualRiskScores = [];
            sampleData.forEach(item => {
                if (item.risk_scores && Array.isArray(item.risk_scores)) {
                    actualRiskScores.push(...item.risk_scores);
                }
            });
            
            if (actualRiskScores.length > 0) {
                const sum = actualRiskScores.reduce((total, score) => total + score, 0);
                const calculatedAverage = Math.round(sum / actualRiskScores.length);
                console.log('Calculated average from sample data:', calculatedAverage);
                console.log('Expected: 51%');
                console.log('Actual: ' + calculatedAverage + '%');
                console.log('Match:', calculatedAverage === 51 ? '✅ YES' : '❌ NO');
            }
            
            console.log('=== RISK CALCULATION TEST COMPLETE ===');
        }
        
        // Expose test function globally for console testing
        window.testRiskCalculation = testRiskCalculation;
        
    </script>
</body>
</html>



