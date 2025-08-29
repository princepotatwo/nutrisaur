<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: home.php");
    exit;
}

// Get user info from session
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSaur Dashboard</title>

    <!-- External CSS file -->
    <link rel="stylesheet" href="./consolidated_styles.css?v=1.0">
    
    <!-- Fallback CSS to ensure basic styling works -->
    <style>
    /* Critical fallback styles */
    body {
        background-color: #1A211A !important;
        color: #E8F0D6 !important;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        padding-left: 320px;
        line-height: 1.6;
        letter-spacing: 0.2px;
    }
    
    .dashboard {
        max-width: calc(100% - 60px);
        width: 100%;
        margin: 0 auto;
        padding: 20px;
        background-color: #1A211A;
        color: #E8F0D6;
    }
    
    .navbar {
        position: fixed;
        top: 0;
        left: 0;
        width: 320px;
        height: 100vh;
        background-color: #2A3326;
        box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
        padding: 0;
        box-sizing: border-box;
        overflow-y: auto;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        backdrop-filter: blur(10px);
    }
    </style>
    
    <!-- Original CSS commented out - now using external file -->
    <!--
    <style>
/* CSS Variables - Applied to root for global access */
:root {
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
    --color-border: rgba(161, 180, 84, 0.2);
    --color-shadow: rgba(0, 0, 0, 0.1);
    
    /* Spacing variables */
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 32px;
    
    /* Border radius variables */
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;
}

/* Ensure CSS variables are applied to html element */
html {
    background-color: var(--color-bg);
    color: var(--color-text);
}

/* Dark Theme (Default) - Softer colors */
.dark-theme {
    --color-bg: #1A211A !important;
    --color-card: #2A3326 !important;
    --color-highlight: #A1B454 !important;
    --color-text: #E8F0D6 !important;
    --color-accent1: #8CA86E !important;
    --color-accent2: #B5C88D !important;
    --color-accent3: #546048 !important;
    --color-accent4: #C9D8AA !important;
    --color-danger: #CF8686 !important;
    --color-warning: #E0C989 !important;
    --color-border: rgba(161, 180, 84, 0.2) !important;
    --color-shadow: rgba(0, 0, 0, 0.1) !important;
}

/* Light Theme - Light Greenish Colors */
.light-theme {
    --color-bg: #F0F7F0 !important;
    --color-card: #FFFFFF !important;
    --color-highlight: #66BB6A !important;
    --color-text: #1B3A1B !important;
    --color-accent1: #81C784 !important;
    --color-accent2: #4CAF50 !important;
    --color-accent3: #2E7D32 !important;
    --color-accent4: #A5D6A7 !important;
    --color-danger: #E57373 !important;
    --color-warning: #FFB74D !important;
    --color-border: #C8E6C9 !important;
    --color-shadow: rgba(76, 175, 80, 0.1) !important;
    --color-hover: rgba(76, 175, 80, 0.08) !important;
    --color-active: rgba(76, 175, 80, 0.15) !important;
}

/* Apply theme colors to body and main elements */
body {
    background-color: var(--color-bg) !important;
    color: var(--color-text) !important;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Force body background and text colors for each theme */
body.dark-theme {
    background-color: #1A211A !important;
    color: #E8F0D6 !important;
}

body.light-theme {
    background-color: #F0F7F0 !important;
    color: #1B3A1B !important;
}

/* Ensure all elements use CSS variables */
* {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
}

/* Base body styles - match dash.php exactly */
body {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding-left: 320px;
    line-height: 1.6;
    letter-spacing: 0.2px;
}

/* Dashboard container - match dash.php exactly */
.dashboard {
    max-width: calc(100% - 60px);
    width: 100%;
    margin: 0 auto;
    padding: 20px;
    background-color: var(--color-bg);
    color: var(--color-text);
}

/* Force dashboard colors for each theme */
.dashboard.dark-theme {
    background-color: #1A211A !important;
    color: #E8F0D6 !important;
}

.dashboard.light-theme {
    background-color: #F0F7F0 !important;
    color: #1B3A1B !important;
}

/* Ensure consistent spacing and layout */
.dashboard > * {
    margin-bottom: 20px;
}

.dashboard > *:last-child {
    margin-bottom: 0;
}

/* ===== NAVBAR STYLES ===== */
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

/* Dark theme navbar styles */
.dark-theme .navbar {
    background-color: var(--color-card);
    box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
}

/* Light theme navbar styles */
.light-theme .navbar {
    background-color: var(--color-card);
    box-shadow: 3px 0 15px var(--color-shadow);
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

/* Dark theme navbar header styles */
.dark-theme .navbar-header {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
    border-bottom-color: rgba(164, 188, 46, 0.15);
}

/* Light theme navbar header styles */
.light-theme .navbar-header {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.05) 0%, transparent 100%);
    border-bottom-color: rgba(102, 187, 106, 0.15);
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

/* Dark theme navbar header after styles */
.dark-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.3), transparent);
}

/* Light theme navbar header after styles */
.light-theme .navbar-header::after {
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.3), transparent);
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
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.1), rgba(102, 187, 106, 0.05));
    border-color: var(--color-border);
    box-shadow: 0 2px 8px var(--color-shadow);
}

.light-theme .navbar-logo:hover .navbar-logo-icon {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.15), rgba(102, 187, 106, 0.08));
    border-color: var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
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
    border-bottom: 1px solid rgba(102, 187, 106, 0.08);
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
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.1), transparent);
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
    background: linear-gradient(90deg, var(--color-hover) 0%, rgba(102, 187, 106, 0.04) 100%);
    color: #1B3A1B;
    box-shadow: 0 4px 15px var(--color-shadow);
}

.light-theme .navbar a.active {
    background: linear-gradient(90deg, var(--color-active) 0%, rgba(102, 187, 106, 0.08) 100%);
    border-left-color: var(--color-highlight);
    box-shadow: 0 6px 20px var(--color-shadow);
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

/* Dark theme navbar footer styles */
.dark-theme .navbar-footer {
    border-top-color: rgba(164, 188, 46, 0.15);
    background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
}

/* Light theme navbar footer styles */
.light-theme .navbar-footer {
    border-top-color: rgba(102, 187, 106, 0.15);
    background: linear-gradient(135deg, transparent 0%, rgba(102, 187, 106, 0.03) 100%);
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

/* Dark theme navbar footer before styles */
.dark-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.2), transparent);
}

/* Light theme navbar footer before styles */
.light-theme .navbar-footer::before {
    background: linear-gradient(90deg, transparent, rgba(102, 187, 106, 0.2), transparent);
}

.navbar-footer div:first-child {
    font-weight: 600;
    color: var(--color-highlight);
    margin-bottom: 8px;
}

/* Dark theme navbar footer text styles */
.dark-theme .navbar-footer div:first-child {
    color: var(--color-highlight);
}

.light-theme .navbar-footer div:first-child {
    color: #1B3A1B;
}

/* Dashboard Header Styles */
.dashboard-header {
    background-color: var(--color-card);
    border-radius: 12px;
    padding: 18px 22px;
    margin-bottom: 25px;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid rgba(161, 180, 84, 0.2);
    transition: all 0.3s ease;
}

.dashboard-header:hover {
    box-shadow: 0 5px 18px rgba(0, 0, 0, 0.12);
    transform: translateY(-1px);
}

.dashboard-title h1 {
    color: var(--color-highlight);
    font-size: 22px;
    font-weight: 600;
    margin: 0;
}

.dashboard-header .user-info {
    display: flex;
    align-items: center;
}

/* User avatar and username styles removed - no longer needed */

/* New Theme toggle button - Orange for moon, Black for sun */
.new-theme-toggle-btn {
    background: #FF9800; /* Default orange for moon icon */
    border: none;
    color: #333; /* Dark color for moon icon */
    padding: 10px 15px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 18px;
    min-width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
    font-weight: bold;
}

.new-theme-toggle-btn:hover {
    background: #F57C00; /* Darker orange on hover */
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
}

.new-theme-toggle-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(255, 152, 0, 0.3);
}

.new-theme-toggle-btn .new-theme-icon {
    font-size: 20px;
    transition: transform 0.3s ease;
}

/* Dark theme: Orange background for moon icon */
.dark-theme .new-theme-toggle-btn {
    background: #FF9800;
    color: #333; /* Dark color for moon icon */
    box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
}

.dark-theme .new-theme-toggle-btn:hover {
    background: #F57C00;
    box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
}

/* Dark theme: Orange moon icon */
.dark-theme .new-theme-toggle-btn .new-theme-icon {
    color: #333;
}

/* Light theme: Black background for sun icon */
.light-theme .new-theme-toggle-btn {
    background: #000000;
    color: #FFFFFF; /* White color for sun icon */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
}

.light-theme .new-theme-toggle-btn:hover {
    background: #333333;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.5);
}

/* Light theme: White sun icon */
.light-theme .new-theme-toggle-btn .new-theme-icon {
    color: #FFFFFF;
}

/* Old theme toggle button styles removed - no longer needed */

/* Light Theme Dashboard Header */
.light-theme .dashboard-header {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 3px 12px var(--color-shadow);
}

.light-theme .dashboard-title h1 {
    color: #1B3A1B;
}

/* Light theme user avatar and username styles removed - no longer needed */

/* Old light theme toggle button styles removed - no longer needed */



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
}

.light-theme .stat-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px var(--color-shadow);
}

.stat-card h3 {
    font-size: 16px;
    margin-bottom: 10px;
    opacity: 0.95;
    color: var(--color-text);
    font-weight: 700;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.stat-value {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--color-highlight);
}

.light-theme .stat-value {
    color: var(--color-highlight);
    font-size: 18px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.8;
    color: var(--color-text);
}

.light-theme .stat-label {
    opacity: 0.95;
    color: var(--color-text);
    font-weight: 600;
    font-size: 14px;
}

.chart-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chart-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
    display: flex;
    flex-direction: column;
    min-height: 350px;
}

.light-theme .chart-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 8px 24px var(--color-shadow);
}

.chart-card h3 {
    font-size: 16px;
    margin-bottom: 20px;
    color: var(--color-text);
}

.light-theme .chart-card h3 {
    color: var(--color-text);
    font-weight: 700;
    font-size: 16px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
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
    overflow: hidden;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.15);
    filter: drop-shadow(0 0 5px rgba(0, 0, 0, 0.05));
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
    z-index: 3;
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
}

.liquid {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(161, 180, 84, 0.5);
    border-radius: 50%;
    transition: top 1.5s cubic-bezier(0.2, 0.8, 0.4, 1);
    z-index: 1;
    filter: blur(1px);
}

.light-theme .liquid {
    background-color: rgba(142, 185, 110, 0.5);
    filter: blur(1px);
}

.wave {
    position: absolute;
    width: 200%;
    height: 200%;
    top: -50%;
    left: -50%;
    border-radius: 40%;
    background: linear-gradient(rgba(161, 180, 84, 0.6), rgba(84, 96, 72, 0.4));
    animation: wave 14s infinite linear;
}

.light-theme .wave {
    background: linear-gradient(rgba(142, 185, 110, 0.6), rgba(107, 150, 80, 0.4));
}

.wave:nth-child(2) {
    animation: wave 10s infinite linear reverse;
    opacity: 0.65;
}

@keyframes wave {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

.donut-center-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 4;
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
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

.line-chart-container {
    position: relative;
    height: 200px;
    margin-top: 30px;
    padding-bottom: 30px;
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
}

.alert-item {
    background-color: rgba(42, 51, 38, 0.7);
    margin-bottom: 14px;
    padding: 18px;
    border-radius: 12px;
    border-left: 4px solid var(--color-highlight);
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.04);
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
    font-size: 16px;
    margin-bottom: 5px;
    color: var(--color-text);
    font-weight: 500;
}

.alert-content p {
    font-size: 14px;
    opacity: 0.7;
    color: var(--color-text);
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

@media (max-width: 1200px) {
    .user-table {
        min-width: 800px;
    }
    
    .user-table th,
    .user-table td {
        padding: 12px 8px;
        font-size: 13px;
    }
    
    .btn-edit, .btn-suspend, .btn-delete {
        padding: 6px 12px;
        font-size: 11px;
        min-width: 50px;
    }
    
    /* Ensure actions column fits */
    .user-table th:last-child,
    .user-table td:last-child {
        min-width: 100px;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .user-management-container {
        padding: 15px;
        margin: 10px;
    }
    
    .table-header {
        flex-direction: column;
        gap: 20px;
        align-items: stretch;
    }
    
    .header-controls {
        flex-direction: column;
        gap: 15px;
    }
    
    .search-row {
        flex-direction: column;
        gap: 12px;
        width: 100%;
    }
    
    .action-row {
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .search-container {
        width: 100%;
    }
    
    .location-filter-container {
        width: 100%;
    }
    
    .user-table {
        min-width: 600px;
    }
    
    .user-table th,
    .user-table td {
        padding: 10px 6px;
        font-size: 12px;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 4px;
    }
    
    .btn-edit, .btn-suspend, .btn-delete {
        padding: 6px 10px;
        font-size: 10px;
        min-width: 45px;
    }
    
    .action-row {
        justify-content: center;
        gap: 8px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .user-table {
        min-width: 500px;
    }
    
    .user-table th,
    .user-table td {
        padding: 8px 4px;
        font-size: 11px;
    }
    
    .risk-badge {
        padding: 4px 8px;
        font-size: 10px;
        min-width: 60px;
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



/* Light theme specific styles */
.light-theme .navbar {
    background-color: var(--color-card);
    box-shadow: 0 3px 15px var(--color-shadow);
    border-right: 1px solid var(--color-border);
}

/* Additional eye-comfort improvements */
.stat-card, .chart-card {
    border-radius: 20px; /* Slightly more rounded corners */
}

.light-theme .stat-value {
    color: var(--color-highlight);
    font-weight: 700;
    font-size: 18px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.light-theme .chart-card,
.light-theme .stat-card {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
}

/* Enhanced form styling for mobile app compatibility */
.modal-content h3 {
    color: var(--color-highlight);
    margin: 20px 0 15px 0;
    border-bottom: 2px solid var(--color-highlight);
    padding-bottom: 5px;
    font-size: 18px;
    font-weight: 600;
}

.light-theme .modal-content h3 {
    color: var(--color-highlight);
    border-bottom-color: var(--color-highlight);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--color-text);
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border-radius: 8px;
    border: 2px solid rgba(161, 180, 84, 0.3);
    background-color: rgba(42, 51, 38, 0.2);
    color: var(--color-text);
    font-size: 14px;
    font-family: inherit;
    transition: all 0.3s ease;
}

.light-theme .form-group input,
.light-theme .form-group select,
.light-theme .form-group textarea {
    background-color: var(--color-card);
    border: 2px solid var(--color-border);
    color: var(--color-text);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.2);
    transform: translateY(-1px);
}

.light-theme .form-group input:focus,
.light-theme .form-group select:focus,
.light-theme .form-group textarea:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 3px var(--color-shadow);
}

/* Checkbox styling for physical signs */
.form-group input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
    transform: scale(1.2);
}

.form-group label[for*="physical"] {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    margin-bottom: 5px;
}

/* Required field indicator */
.form-group label[for*="required"]::after,
.form-group label:contains("*")::after {
    content: " *";
    color: var(--color-danger);
    font-weight: bold;
}

/* Form section spacing */
.modal-content form > div:not(.form-group) {
    margin-bottom: 25px;
}

/* Enhanced button styling */
.btn-submit {
    background: linear-gradient(135deg, var(--color-highlight) 0%, #8ca757 100%);
    color: white;
    padding: 15px 30px;
    width: 100%;
    margin-top: 20px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(161, 180, 84, 0.3);
}

.light-theme .btn-submit {
    background: linear-gradient(135deg, var(--color-accent3) 0%, #6a9d59 100%);
    box-shadow: 0 4px 15px rgba(142, 185, 110, 0.3);
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(161, 180, 84, 0.4);
}

.light-theme .btn-submit:hover {
    box-shadow: 0 6px 20px rgba(142, 185, 110, 0.4);
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



.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    background-color: var(--color-card);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
    border: 1px solid var(--color-border);
    position: relative;
    z-index: 1;
}

/* Dark theme table header styles */
.dark-theme .table-header {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Light theme table header styles */
.light-theme .table-header {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 4px 15px var(--color-shadow);
}

.table-header h2 {
    color: var(--color-highlight);
    font-size: 24px;
    margin: 0;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

        .header-controls {
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
        }

        .search-row {
            display: flex;
            gap: 15px;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
        }

        .action-row {
            display: flex;
            gap: 12px;
            align-items: center;
            width: 100%;
            flex-wrap: wrap;
            justify-content: center;
        }



.btn-add {
    background-color: var(--color-highlight);
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-icon {
    font-size: 16px;
    line-height: 1;
}

.btn-text {
    font-size: 14px;
    font-weight: 600;
}

.btn-secondary {
    background-color: var(--color-accent3);
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-secondary:hover {
    background-color: var(--color-accent2);
    transform: translateY(-1px);
}

.user-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 10px;
    table-layout: auto;
    min-width: 900px;
    border-radius: 15px;
    overflow: hidden;
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 20px var(--color-shadow);
}

.user-table thead { 
    background-color: var(--color-card);
}

.user-table tbody tr:nth-child(odd) {
    background-color: rgba(84, 96, 72, 0.3);
}

.user-table tbody tr:nth-child(even) {
    background-color: rgba(84, 96, 72, 0.1);
}

/* Dark theme table styles */
.dark-theme .user-table {
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.dark-theme .user-table thead {
    background-color: var(--color-card);
}

.dark-theme .user-table tbody tr:nth-child(odd) {
    background-color: rgba(161, 180, 84, 0.1);
}

.dark-theme .user-table tbody tr:nth-child(even) {
    background-color: rgba(161, 180, 84, 0.05);
}

/* Light theme table styles */
.light-theme .user-table {
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 20px var(--color-shadow);
}

.light-theme .user-table thead {
    background-color: var(--color-card);
}

.light-theme .user-table tbody tr:nth-child(odd) {
    background-color: rgba(102, 187, 106, 0.1);
}

.light-theme .user-table tbody tr:nth-child(even) {
    background-color: rgba(102, 187, 106, 0.05);
}

.user-table tbody tr {
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.user-table tbody tr:hover {
    border-left-color: var(--color-highlight);
    background-color: rgba(161, 180, 84, 0.2);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(161, 180, 84, 0.15);
}



.user-table th,
.user-table td {
    padding: 15px 12px;
    text-align: left;
    border-bottom: 1px solid rgba(161, 180, 84, 0.2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 14px;
    font-weight: 500;
    vertical-align: middle;
    position: relative;
}

/* Ensure actions column is always visible */
.user-table th:last-child,
.user-table td:last-child {
    white-space: nowrap;
    overflow: visible;
    text-overflow: clip;
    min-width: 120px;
}

/* Ensure table fits container */
.user-table {
    width: 100%;
    min-width: 900px;
}

/* Responsive table wrapper */
.table-responsive {
    overflow-x: auto;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.user-table td:last-child {
    text-align: center;
}

/* Set specific widths for columns - Balanced for 5 columns */
.user-table th:nth-child(1), .user-table td:nth-child(1) { width: 12%; } /* User ID */
.user-table th:nth-child(2), .user-table td:nth-child(2) { width: 23%; } /* Username */
.user-table th:nth-child(3), .user-table td:nth-child(3) { width: 20%; text-align: center; } /* Risk Level */
.user-table th:nth-child(4), .user-table td:nth-child(4) { width: 25%; } /* Location */
.user-table th:nth-child(5), .user-table td:nth-child(5) { width: 20%; text-align: center; } /* Actions */

.user-table th {
    color: var(--color-highlight);
    font-weight: 700;
    font-size: 16px;
    position: sticky;
    top: 0;
    background-color: var(--color-card);
    z-index: 10;
    border-bottom: 2px solid rgba(161, 180, 84, 0.4);
    padding-bottom: 18px;
    padding-top: 18px;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    backdrop-filter: blur(10px);
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
    align-items: center;
}

.btn {
    padding: 14px 28px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 700;
    font-size: 16px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.btn-edit, .btn-suspend, .btn-delete {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    margin: 0 4px;
    transition: all 0.3s ease;
    cursor: pointer !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    border: none;
    min-width: 60px;
    max-width: 80px;
    display: inline-block !important;
    text-align: center;
    line-height: 1.2;
    position: relative;
    z-index: 10;
}

.btn-edit {
    background-color: rgba(161, 180, 84, 0.15);
    color: var(--color-highlight);
    border: 2px solid rgba(161, 180, 84, 0.4);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(161, 180, 84, 0.2);
}

.btn-suspend {
    background-color: rgba(224, 201, 137, 0.15);
    color: var(--color-warning);
    border: 2px solid rgba(224, 201, 137, 0.4);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(224, 201, 137, 0.2);
}

.btn-delete {
    background-color: rgba(207, 134, 134, 0.15);
    color: var(--color-danger);
    border: 2px solid rgba(207, 134, 134, 0.4);
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(207, 134, 134, 0.2);
}

.light-theme .btn-edit {
    background-color: rgba(102, 187, 106, 0.15);
    color: var(--color-highlight);
    border: 2px solid rgba(102, 187, 106, 0.4);
    font-weight: 600;
}

.light-theme .btn-suspend {
    background-color: rgba(255, 183, 77, 0.15);
    color: var(--color-warning);
    border: 2px solid rgba(255, 183, 77, 0.4);
    font-weight: 600;
}

.light-theme .btn-delete {
    background-color: rgba(229, 115, 115, 0.15);
    color: var(--color-danger);
    border: 2px solid rgba(229, 115, 115, 0.4);
    font-weight: 600;
}

.btn-edit:hover, .btn-suspend:hover, .btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    filter: brightness(1.1);
    transform: translateY(-2px) scale(1.05);
}

.btn-edit:active, .btn-suspend:active, .btn-delete:active {
    transform: translateY(0) scale(0.98);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.btn-edit:focus, .btn-suspend:focus, .btn-delete:focus {
    outline: 2px solid var(--color-highlight);
    outline-offset: 2px;
    transform: translateY(-1px);
}

/* Ensure buttons look clickable */
.btn-edit, .btn-suspend, .btn-delete {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    position: relative;
    overflow: hidden;
    pointer-events: auto !important;
    touch-action: manipulation;
}

/* Add a subtle background pattern to make buttons more visible */
.btn-edit::before, .btn-suspend::before, .btn-delete::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    transition: left 0.5s ease;
    pointer-events: none;
}

.btn-edit:hover::before, .btn-suspend:hover::before, .btn-delete:hover::before {
    left: 100%;
}

/* Ensure buttons are always clickable */
.btn-edit, .btn-suspend, .btn-delete {
    pointer-events: auto !important;
    cursor: pointer !important;
    position: relative;
    z-index: 100;
    /* Make buttons clearly look clickable */
    background-image: linear-gradient(145deg, rgba(255,255,255,0.1), transparent);
    border-style: solid;
    border-width: 2px;
    text-decoration: none;
    /* Prevent text selection */
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Current risk display styling */
.current-risk-info {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(42, 51, 38, 0.05));
    border: 2px solid rgba(161, 180, 84, 0.3);
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0;
    text-align: center;
}

.current-risk-info h4 {
    color: var(--color-highlight);
    margin: 0 0 15px 0;
    font-size: 18px;
    font-weight: 600;
}

.risk-details {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.risk-score {
    font-size: 16px;
    font-weight: 500;
    color: var(--color-text);
}

.risk-breakdown {
    color: var(--color-text-secondary);
    font-size: 12px;
    opacity: 0.8;
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 5px;
}

.status-active {
    background-color: var(--color-highlight);
}

.status-suspended {
    background-color: var(--color-warning);
}

.status-inactive {
    background-color: var(--color-danger);
}

.risk-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    text-align: center;
    min-width: 80px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.risk-badge.good {
    background-color: rgba(161, 180, 84, 0.15);
    color: #A1B454;
}

.risk-badge.at,
.risk-badge.risk {
    background-color: rgba(224, 201, 137, 0.15);
    color: #E0C989;
}

.risk-badge.malnourished {
    background-color: rgba(207, 134, 134, 0.15);
    color: #CF8686;
}

/* Light theme risk badge styles */
.light-theme .risk-badge.good {
    background-color: rgba(102, 187, 106, 0.15);
    color: var(--color-highlight);
    border: 1px solid rgba(102, 187, 106, 0.3);
}

.light-theme .risk-badge.at,
.light-theme .risk-badge.risk {
    background-color: rgba(255, 183, 77, 0.15);
    color: var(--color-warning);
    border: 1px solid rgba(255, 183, 77, 0.3);
}

.light-theme .risk-badge.malnourished {
    background-color: rgba(229, 115, 115, 0.15);
    color: var(--color-danger);
    border: 1px solid rgba(229, 115, 115, 0.3);
}

/* Add styles for active status indicator */
.status-active-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--color-highlight);
    margin-right: 8px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1000;
}

.modal-content {
    background-color: var(--color-card);
    margin: 5% auto;
    padding: 25px;
    border-radius: 12px;
    width: 80%;
    max-width: 900px;
    max-height: 85vh;
    position: relative;
    overflow-y: auto;
    border: 1px solid var(--color-border);
}

/* Dark theme modal styles */
.dark-theme .modal-content {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

/* Light theme modal styles */
.light-theme .modal-content {
    background-color: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 8px 32px var(--color-shadow);
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 2% auto;
        padding: 20px;
        max-height: 90vh;
    }
    
    .csv-preview-table {
        font-size: 11px;
    }
    
    .csv-preview-table th,
    .csv-preview-table td {
        padding: 6px 4px;
        max-width: 100px;
        min-width: 60px;
    }
    
    .confirm-import-btn {
        padding: 10px 20px;
        font-size: 14px;
    }
}

.close {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 28px;
    cursor: pointer;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border-radius: 6px;
    border: 1px solid var(--color-border);
    background-color: var(--color-card);
    color: var(--color-text);
}

/* Dark theme form styles */
.dark-theme .form-group input,
.dark-theme .form-group select {
    background-color: var(--color-card);
    color: var(--color-text);
    border-color: var(--color-border);
}

/* Light theme form styles */
.light-theme .form-group input,
.light-theme .form-group select {
    background-color: var(--color-card);
    color: var(--color-text);
    border-color: var(--color-border);
}

/* Edit modal specific styles */
.screening-options {
    margin-top: 15px;
}

.option-group {
    margin-bottom: 20px;
    padding: 15px;
    background-color: var(--color-accent1);
    border-radius: 8px;
    border: 1px solid var(--color-border);
}

.option-group label {
    font-weight: 600;
    margin-bottom: 10px;
    color: var(--color-highlight);
}

/* Dark theme option group styles */
.dark-theme .option-group {
    background-color: rgba(161, 180, 84, 0.1);
    border-color: var(--color-border);
}

/* Light theme option group styles */
.light-theme .option-group {
    background-color: rgba(102, 187, 106, 0.1);
    border-color: var(--color-border);
}

.radio-group {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.radio-group input[type="radio"] {
    display: none;
}

.radio-group label {
    padding: 8px 16px;
    background-color: var(--color-bg);
    border: 2px solid var(--color-accent3);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: normal;
    color: var(--color-text);
}

.radio-group input[type="radio"]:checked + label {
    background-color: var(--color-highlight);
    color: white;
    border-color: var(--color-highlight);
}

.checkbox-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-item input[type="checkbox"] {
    width: auto;
    margin: 0;
}

.checkbox-item label {
    margin: 0;
    font-weight: normal;
    cursor: pointer;
}

.modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--color-accent3);
}

.modal-buttons .btn {
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    border: none;
    transition: all 0.3s ease;
}

.modal-buttons .btn-primary {
    background-color: var(--color-highlight);
    color: white;
}

.modal-buttons .btn-secondary {
    background-color: var(--color-accent3);
    color: var(--color-text);
}

.modal-buttons .btn:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

/* Optgroup styling for barangay dropdown */
optgroup {
    font-weight: bold;
    color: var(--color-highlight);
    background-color: var(--color-accent1);
}

optgroup option {
    font-weight: normal;
    color: var(--color-text);
    background-color: var(--color-bg);
    padding-left: 20px;
}

.btn-submit {
    width: 100%;
    background-color: var(--color-highlight);
    color: var(--color-text);
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    margin-top: 10px;
}

.btn-submit:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

/* Alert styles */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 10px;
    font-weight: 500;
}

.alert-success {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-highlight);
    border-left: 4px solid var(--color-highlight);
}

.alert-danger {
    background-color: rgba(207, 134, 134, 0.2);
    color: var(--color-danger);
    border-left: 4px solid var(--color-danger);
}

.alert-info {
    background-color: rgba(100, 149, 237, 0.2);
    color: #6495ED;
    border-left: 4px solid #6495ED;
}

.light-theme .alert-success {
    background-color: rgba(142, 185, 110, 0.2);
    color: var(--color-accent3);
    border-left: 4px solid var(--color-accent3);
}

.light-theme .alert-danger {
    background-color: rgba(233, 141, 124, 0.2);
    color: var(--color-danger);
    border-left: 4px solid var(--color-danger);
}

.light-theme .alert-info {
    background-color: rgba(100, 149, 237, 0.1);
    color: #4A7BBF;
    border-left: 4px solid #4A7BBF;
}

/* User Management Styles */
.user-management-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
    border: 1px solid var(--color-border);
    position: relative;
    overflow: hidden;
}

/* Dark theme specific styles */
.dark-theme .user-management-container {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.light-theme .user-management-container {
    background: var(--color-card);
    border: 1px solid var(--color-border);
    box-shadow: 0 6px 20px var(--color-shadow);
}

/* CSV Upload Styles */
.csv-upload-area {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border: 2px dashed rgba(161, 180, 84, 0.4);
    border-radius: 15px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer !important;
    transition: all 0.3s ease;
    margin-bottom: 20px;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    outline: none;
    pointer-events: auto;
    position: relative;
}

.csv-upload-area.dragover {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.2), rgba(161, 180, 84, 0.15));
    border-color: var(--color-highlight);
    transform: scale(1.02);
    box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
}

/* CSV Import Modal Specific Styles */
.csv-import-modal-content {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    margin: 0 !important;
    height: 85vh !important;
    width: 90% !important;
    max-width: 800px !important;
    border-radius: 15px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3) !important;
    overflow: hidden !important;
    z-index: 1001 !important;
}

/* Dark theme CSV upload styles */
.dark-theme .csv-upload-area {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
    border-color: rgba(161, 180, 84, 0.4);
}

/* Light theme CSV upload styles */
.light-theme .csv-upload-area {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.1), rgba(102, 187, 106, 0.05));
    border-color: rgba(102, 187, 106, 0.4);
}

.csv-upload-area:hover {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.15), rgba(161, 180, 84, 0.1));
    border-color: var(--color-highlight);
    transform: translateY(-2px);
}

.csv-upload-area:focus {
    outline: none;
    box-shadow: none;
}

.upload-icon {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.8;
}

.upload-text h4 {
    color: var(--color-highlight);
    font-size: 20px;
    margin-bottom: 10px;
}

.upload-text p {
    color: var(--color-text);
    margin-bottom: 8px;
    opacity: 0.9;
}

.csv-format {
    font-size: 12px;
    opacity: 0.7;
    font-style: italic;
    margin-top: 10px;
}

.csv-format-small {
    font-size: 11px;
    opacity: 0.6;
    font-style: italic;
    margin-top: 5px;
    color: var(--color-highlight);
}

.csv-preview {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid var(--color-border);
}

/* Dark theme CSV preview styles */
.dark-theme .csv-preview {
    background-color: rgba(42, 51, 38, 0.7);
    border-color: var(--color-border);
}

.light-theme .csv-preview {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
}

.csv-preview h4 {
    color: var(--color-highlight);
    margin-bottom: 15px;
    font-size: 16px;
}

.csv-preview-container {
    background-color: var(--color-card);
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 15px;
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid var(--color-border);
}

/* Dark theme CSV preview container styles */
.dark-theme .csv-preview-container {
    background-color: var(--color-card);
    border-color: var(--color-border);
}

/* Light theme CSV preview container styles */
.light-theme .csv-preview-container {
    background-color: var(--color-card);
    border-color: var(--color-border);
}

.csv-preview-actions {
    text-align: center;
    padding-top: 15px;
    border-top: 1px solid rgba(161, 180, 84, 0.1);
}

.csv-table-wrapper {
    overflow-x: auto;
    border-radius: 6px;
    background-color: var(--color-bg);
}

.csv-table-wrapper::-webkit-scrollbar {
    height: 8px;
}

.csv-table-wrapper::-webkit-scrollbar-track {
    background: rgba(161, 180, 84, 0.1);
    border-radius: 4px;
}

.csv-table-wrapper::-webkit-scrollbar-thumb {
    background: var(--color-highlight);
    border-radius: 4px;
}

.csv-table-wrapper::-webkit-scrollbar-thumb:hover {
    background: var(--color-accent3);
}

.confirm-import-btn {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.confirm-import-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
    background: linear-gradient(135deg, #20c997, #28a745);
}

.confirm-import-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.import-success-message {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(32, 201, 151, 0.1));
    border: 2px solid rgba(40, 167, 69, 0.3);
    border-radius: 12px;
    margin-top: 20px;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
    max-height: 200px;
    overflow-y: auto;
}

.preview-table th,
.preview-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid rgba(161, 180, 84, 0.2);
}

.preview-table th {
    background-color: rgba(161, 180, 84, 0.1);
    color: var(--color-highlight);
    font-weight: 500;
}

.csv-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 20px;
}

.btn-cancel {
    padding: 12px 24px;
    border-radius: 10px;
    border: none;
    font-size: 16px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: rgba(207, 134, 134, 0.2);
    color: var(--color-danger);
    border: 1px solid rgba(207, 134, 134, 0.3);
}

.btn-cancel:hover {
    background-color: rgba(207, 134, 134, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(207, 134, 134, 0.2);
}

/* Drag and drop styles */
.csv-upload-area.dragover {
    background: linear-gradient(135deg, rgba(161, 180, 84, 0.2), rgba(161, 180, 84, 0.15));
    border-color: var(--color-highlight);
    transform: scale(1.02);
}

.light-theme .csv-upload-area {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.1), rgba(102, 187, 106, 0.05));
    border: 2px dashed rgba(102, 187, 106, 0.4);
}

.light-theme .csv-upload-area:hover {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.15), rgba(102, 187, 106, 0.1));
    border-color: var(--color-highlight);
}

    .light-theme .csv-upload-area.dragover {
    background: linear-gradient(135deg, rgba(102, 187, 106, 0.2), rgba(102, 187, 106, 0.15));
    border-color: var(--color-highlight);
}

    /* ===== ENHANCED INTERACTIVITY & ANIMATIONS ===== */
    .fade-in {
        animation: fadeIn var(--transition-normal);
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }



    .slide-in-left {
        animation: slideInLeft var(--transition-normal);
    }

    @keyframes slideInLeft {
        from { opacity: 0; transform: translateX(-30px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Loading States */
    .loading {
        opacity: 0.6;
        pointer-events: none;
        position: relative;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid var(--color-highlight);
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Enhanced Button Hover Effects */
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left var(--transition-slow);
    }

    .btn:hover::before {
        left: 100%;
    }



    /* Table Row Hover Enhancements */
    .user-table tbody tr {
        transition: all var(--transition-normal);
        position: relative;
    }

    .user-table tbody tr::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.05), transparent);
        opacity: 0;
        transition: opacity var(--transition-normal);
    }

    .user-table tbody tr:hover::after {
        opacity: 1;
    }

    /* Risk Badge Enhancements */
    .risk-badge {
        transition: all var(--transition-normal);
        position: relative;
        overflow: hidden;
    }

    .risk-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left var(--transition-slow);
    }

    .risk-badge:hover::before {
        left: 100%;
    }

    .risk-badge:hover {
        transform: scale(1.05);
        box-shadow: var(--shadow-sm);
    }

    /* Modal Enhancements */
    .modal {
        animation: modalFadeIn var(--transition-normal);
    }

    @keyframes modalFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        animation: modalSlideIn var(--transition-normal);
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Form Input Enhancements */
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        transform: translateY(-1px);
        box-shadow: 0 0 0 3px rgba(161, 180, 84, 0.2);
    }

    /* Search Container Enhancements */
    .search-container:focus-within {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    /* Location Filter Enhancements */
    .location-select:focus {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    /* Ripple Effect for Buttons */
    .btn {
        position: relative;
        overflow: hidden;
    }

    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }

    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    /* Enhanced Table Responsiveness */
    .table-responsive {
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }

    /* Enhanced Modal Backdrop */
    .modal {
        backdrop-filter: blur(10px);
    }

    /* Enhanced Form Styling */
    .form-group {
        position: relative;
    }

    .form-group input:focus + label,
    .form-group select:focus + label,
    .form-group textarea:focus + label {
        color: var(--color-highlight);
        transform: translateY(-20px) scale(0.8);
    }

    /* Enhanced Search Results */
    .search-results {
        animation: slideDown var(--transition-normal);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Enhanced Loading States */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(42, 51, 38, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-lg);
        z-index: 10;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid rgba(161, 180, 84, 0.3);
        border-top: 4px solid var(--color-highlight);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* ===== UTILITY CLASSES ===== */
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    
    .mb-0 { margin-bottom: 0; }
    .mb-1 { margin-bottom: var(--spacing-xs); }
    .mb-2 { margin-bottom: var(--spacing-sm); }
    .mb-3 { margin-bottom: var(--spacing-md); }
    .mb-4 { margin-bottom: var(--spacing-lg); }
    
    .mt-0 { margin-top: 0; }
    .mt-1 { margin-top: var(--spacing-xs); }
    .mt-2 { margin-top: var(--spacing-sm); }
    .mt-3 { margin-top: var(--spacing-md); }
    .mt-4 { margin-top: var(--spacing-lg); }
    
    .d-none { display: none; }
    .d-block { display: block; }
    .d-flex { display: flex; }
    .d-inline { display: inline; }
    
    .w-100 { width: 100%; }
    .h-100 { height: 100%; }
    
    .position-relative { position: relative; }
    .position-absolute { position: absolute; }
    .position-fixed { position: fixed; }
    
    .overflow-hidden { overflow: hidden; }
    .overflow-auto { overflow: auto; }
    
    .rounded { border-radius: var(--radius-md); }
    .rounded-lg { border-radius: var(--radius-lg); }
    .rounded-xl { border-radius: var(--radius-xl); }
    
    .shadow { box-shadow: var(--shadow-sm); }
    .shadow-lg { box-shadow: var(--shadow-lg); }
    
    .transition { transition: all var(--transition-normal); }
    .transition-fast { transition: all var(--transition-fast); }
    .transition-slow { transition: all var(--transition-slow); }

    /* ===== RESPONSIVE IMPROVEMENTS ===== */
    @media (max-width: 1200px) {
        .header-controls {
            flex-direction: column;
            align-items: stretch;
            gap: var(--spacing-sm);
        }
        
        .search-container {
            width: 100%;
        }
        
        .search-input {
            min-width: auto;
            flex: 1;
        }
    }

    @media (max-width: 768px) {

        
        .navbar {
            width: 80px;
            transition: width var(--transition-normal);
        }
        
        .navbar:hover {
            width: 320px;
        }
        
        .navbar-logo-text,
        .navbar span:not(.navbar-icon) {
            opacity: 0;
            transition: opacity var(--transition-normal);
        }
        
        .navbar:hover .navbar-logo-text,
        .navbar:hover span:not(.navbar-icon) {
            opacity: 1;
        }
        
        .dashboard {
            padding: var(--spacing-md);
        }
        
        .user-management-container {
            padding: var(--spacing-md);
        }
        
        .table-header {
            flex-direction: column;
            gap: var(--spacing-md);
            align-items: stretch;
        }
        
        .header-controls {
            flex-direction: column;
        }
    }

    @media (max-width: 480px) {
        .dashboard {
            padding: var(--spacing-sm);
        }
        
        .user-management-container {
            padding: var(--spacing-sm);
        }
        
        .bulk-actions {
            flex-direction: column;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }

/* Info Icon and Tooltip Styles */
.info-icon-container {
    position: relative;
    display: inline-block;
}

.info-icon {
    cursor: pointer;
    transition: all 0.3s ease;
}

.info-icon:hover {
    transform: scale(1.1);
}

.info-tooltip {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    background: var(--color-card);
    border: 1px solid var(--color-border);
    border-radius: 10px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    margin-top: 10px;
    animation: tooltipSlideIn 0.3s ease;
}

/* Dark theme tooltip styles */
.dark-theme .info-tooltip {
    background: var(--color-card);
    border-color: var(--color-border);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

@keyframes tooltipSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tooltip-content {
    padding: 20px;
}

.tooltip-content h5 {
    color: var(--color-highlight);
    margin: 0 0 15px 0;
    font-size: 16px;
}

.tooltip-content h6 {
    color: var(--color-accent3);
    margin: 15px 0 8px 0;
    font-size: 14px;
}

.tooltip-content p {
    margin: 5px 0;
    font-size: 13px;
    line-height: 1.4;
}

.light-theme .info-tooltip {
    background: linear-gradient(135deg, rgba(234, 240, 220, 0.95), rgba(234, 240, 220, 0.9));
    border: 1px solid var(--color-border);
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 16px 0;
}

.table-header h2 {
    color: var(--color-highlight);
    font-size: 24px;
    margin: 0;
}

.light-theme .table-header h2 {
    color: var(--color-highlight);
}

.header-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.search-container {
    display: flex;
    align-items: center;
    background: var(--color-card);
    border-radius: 8px;
    padding: 8px 12px;
    border: 1px solid rgba(161, 180, 84, 0.3);
    transition: all 0.2s ease;
    flex: 1;
    min-width: 0;
    max-width: 300px;
}

.search-container:focus-within {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
}

.search-input {
    border: none;
    background: transparent;
    color: var(--color-text);
    padding: 6px 8px;
    font-size: 14px;
    outline: none;
    width: 100%;
    font-weight: 500;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
    font-weight: 400;
}

.search-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

/* Dark theme search input styles */
.dark-theme .search-input {
    background: transparent;
    color: var(--color-text);
}

.dark-theme .search-input::placeholder {
    color: rgba(232, 240, 214, 0.6);
}

/* Light theme search input styles */
.light-theme .search-input {
    background: transparent;
    color: var(--color-text);
}

.light-theme .search-input::placeholder {
    color: rgba(65, 89, 57, 0.6);
}

.search-btn {
    background: var(--color-highlight);
    border: none;
    color: var(--color-bg);
    padding: 6px 8px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s ease;
    font-weight: 600;
    min-width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.search-btn:hover {
    background: var(--color-accent1);
    transform: scale(1.02);
}

.btn {
    padding: 10px 16px;
    border-radius: 8px;
    border: none;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    white-space: nowrap;
}

.btn-add {
    background-color: var(--color-highlight);
    color: white;
}

.light-theme .btn-add {
    background-color: var(--color-highlight);
}

.btn-add:hover {
    background-color: var(--color-accent1);
    transform: translateY(-1px);
}

.light-theme .btn-add:hover {
    background-color: var(--color-accent3);
}

.user-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.user-table th,
.user-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid rgba(161, 180, 84, 0.2);
}

.light-theme .user-table th,
.light-theme .user-table td {
    border-bottom: 1px solid var(--color-border);
}

.user-table th {
    color: var(--color-highlight);
    font-weight: 700;
    font-size: 16px;
    position: sticky;
    top: 0;
    background-color: var(--color-card);
    z-index: 10;
    border-bottom: 2px solid rgba(161, 180, 84, 0.4); /* Make header border more visible */
    padding-bottom: 18px; /* Add more padding at bottom of header */
    padding-top: 18px; /* Add more padding at top of header */
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.light-theme .user-table th {
    color: var(--color-highlight);
    background-color: var(--color-card);
}

.user-table tr:hover {
    background-color: rgba(161, 180, 84, 0.05);
}

.light-theme .user-table tr:hover {
    background-color: var(--color-hover);
}

.risk-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block; /* Ensure proper display */
}

.risk-badge.good {
    background-color: rgba(161, 180, 84, 0.15);
    color: #A1B454;
}

.risk-badge.at,
.risk-badge.risk {
    background-color: rgba(224, 201, 137, 0.15);
    color: #E0C989;
}

.risk-badge.malnourished {
    background-color: rgba(207, 134, 134, 0.15);
    color: #CF8686;
}

.status-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-active {
    background-color: var(--color-highlight);
}

.status-suspended {
    background-color: var(--color-danger);
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

/* Button styles are defined in the main CSS section above */

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    overflow: hidden;
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: var(--color-card);
    margin: 10% auto;
    padding: 30px;
    border-radius: 15px;
    width: 80%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    position: relative;
}

.light-theme .modal-content {
    background-color: var(--color-card);
}

.close {
    position: absolute;
    right: 20px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    color: var(--color-text);
    cursor: pointer;
}

.modal h2 {
    color: var(--color-highlight);
    margin-bottom: 20px;
}

.light-theme .modal h2 {
    color: var(--color-highlight);
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: var(--color-text);
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid rgba(161, 180, 84, 0.3);
    background-color: rgba(42, 51, 38, 0.2);
    color: var(--color-text);
    font-size: 14px;
    font-family: inherit;
}

.light-theme .form-group input,
.light-theme .form-group select,
.light-theme .form-group textarea {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
}

.light-theme .form-group input:focus,
.light-theme .form-group select:focus,
.light-theme .form-group textarea:focus {
    border-color: var(--color-accent3);
    box-shadow: 0 0 0 2px rgba(142, 185, 110, 0.2);
}

.btn-submit {
    background-color: var(--color-highlight);
    color: white;
    padding: 12px 24px;
    width: 100%;
    margin-top: 10px;
}

.light-theme .btn-submit {
    background-color: var(--color-highlight);
}

.btn-submit:hover {
    background-color: var(--color-accent1);
    transform: translateY(-2px);
}

.light-theme .btn-submit:hover {
    background-color: var(--color-accent3);
}

/* Location filter styles */
.location-filter-container {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    max-width: 250px;
}

.location-select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid var(--color-border);
    background-color: var(--color-card);
    color: var(--color-text);
    font-size: 14px;
    width: 100%;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

/* Dark theme location select styles */
.dark-theme .location-select {
    background-color: var(--color-card);
    color: var(--color-text);
    border-color: var(--color-border);
}

.light-theme .location-select {
    background-color: var(--color-card);
    border: 1px solid var(--color-border);
    color: var(--color-text);
}

.location-select:focus {
    outline: none;
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
}

.light-theme .location-select:focus {
    border-color: var(--color-highlight);
    box-shadow: 0 0 0 2px var(--color-shadow);
}

/* Bulk actions styles */
/* Bulk actions styling removed - buttons now integrated into action-row */

/* Light theme bulk actions styling removed - buttons now integrated into action-row */

.btn-danger {
    background-color: rgba(207, 134, 134, 0.15);
    color: var(--color-danger);
    border: 1px solid rgba(207, 134, 134, 0.25);
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
}

.light-theme .btn-danger {
    background-color: rgba(233, 141, 124, 0.2);
    color: var(--color-danger);
    border: 1px solid rgba(233, 141, 124, 0.3);
}

.btn-danger:hover {
    background-color: rgba(207, 134, 134, 0.25);
    transform: translateY(-1px);
    border-color: rgba(207, 134, 134, 0.4);
}

.light-theme .btn-danger:hover {
    background-color: rgba(233, 141, 124, 0.3);
    box-shadow: 0 4px 12px rgba(233, 141, 124, 0.2);
}

/* CSV Import Styles */
.csv-import-info {
    background-color: rgba(161, 180, 84, 0.1);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    border-left: 3px solid var(--color-highlight);
}

/* Dark theme CSV import styles */
.dark-theme .csv-import-info {
    background-color: rgba(161, 180, 84, 0.1);
    border-left-color: var(--color-highlight);
}

.light-theme .csv-import-info {
    background-color: rgba(102, 187, 106, 0.1);
    border-left-color: var(--color-highlight);
}

.csv-import-info ul {
    margin: 10px 0 0 20px;
    padding: 0;
}

.csv-import-info li {
    margin-bottom: 5px;
    font-size: 14px;
}

.csv-import-info strong {
    color: var(--color-highlight);
}

.light-theme .csv-import-info strong {
    color: var(--color-highlight);
}

#csvPreview {
    margin-top: 20px;
    padding: 15px;
    background-color: rgba(161, 180, 84, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(161, 180, 84, 0.2);
}

.light-theme #csvPreview {
    background-color: rgba(102, 187, 106, 0.05);
    border-color: rgba(102, 187, 106, 0.2);
}

#csvPreview h3 {
    margin-bottom: 15px;
    color: var(--color-highlight);
    font-size: 18px;
}

.light-theme #csvPreview h3 {
    color: var(--color-highlight);
}

.csv-preview-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
    font-size: 13px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid rgba(161, 180, 84, 0.3);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.csv-preview-table th,
.csv-preview-table td {
    padding: 10px 8px;
    text-align: left;
    border-bottom: 1px solid rgba(161, 180, 84, 0.2);
    white-space: normal;
    word-wrap: break-word;
    max-width: 150px;
    min-width: 80px;
}

.csv-preview-table th {
    background-color: var(--color-highlight);
    color: white;
    font-weight: 600;
    position: sticky;
    top: 0;
}

.light-theme .csv-preview-table th {
    background-color: var(--color-highlight);
}

.csv-preview-table tr:nth-child(even) {
    background-color: rgba(161, 180, 84, 0.05);
}

.light-theme .csv-preview-table tr:nth-child(even) {
    background-color: rgba(102, 187, 106, 0.05);
}

.csv-preview-table tr:hover {
    background-color: rgba(161, 180, 84, 0.1);
}

.light-theme .csv-preview-table tr:hover {
    background-color: rgba(102, 187, 106, 0.1);
}

.csv-error {
    color: var(--color-danger);
    font-size: 12px;
    margin-top: 5px;
}

.csv-success {
    color: var(--color-highlight);
    font-size: 12px;
    margin-top: 5px;
}

.light-theme .csv-success {
    color: var(--color-highlight);
}

.import-progress {
    margin-top: 15px;
    padding: 10px;
    background-color: rgba(161, 180, 84, 0.1);
    border-radius: 6px;
    text-align: center;
}

.light-theme .import-progress {
    background-color: rgba(102, 187, 106, 0.1);
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: rgba(161, 180, 84, 0.2);
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.progress-fill {
    height: 100%;
    background-color: var(--color-highlight);
    transition: width 0.3s ease;
    border-radius: 10px;
}

.light-theme .progress-fill {
    background-color: var(--color-highlight);
}

.no-data-message {
    text-align: center;
    padding: 30px;
    color: var(--color-text);
    opacity: 0.7;
    font-style: italic;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .user-table {
        min-width: 900px; /* Keep minimum width on smaller screens */
    }
    
    .user-management-container {
        padding: 15px;
        margin: 10px;
        width: calc(100% - 20px); /* Adjust container width to account for margins */
    }
}

@media (max-width: 768px) {
    .user-table th,
    .user-table td {
        padding: 8px 10px;
        font-size: 12px;
    }
    
    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .btn {
        padding: 5px 8px;
        font-size: 11px;
    }
}

.card {
    border-radius: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}
.risk-good {
    background-color: rgba(161, 180, 84, 0.15);
    color: var(--color-highlight);
}
.risk-moderate {
    background-color: rgba(224, 201, 137, 0.15);
    color: var(--color-warning);
}
.risk-high {
    background-color: rgba(207, 134, 134, 0.15);
    color: var(--color-danger);
}
.table-responsive {
    border-radius: 15px;
    overflow: hidden;
    max-width: 100%;
}
.status-badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: bold;
}
.status-active {
    background-color: rgba(161, 180, 84, 0.15);
    color: var(--color-highlight);
}
.status-inactive {
    background-color: rgba(207, 134, 134, 0.15);
    color: var(--color-danger);
}
.risk-status {
    padding: 8px 16px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    display: inline-block;
    white-space: nowrap;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
    transition: all 0.3s ease;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}
.risk-status-good {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-highlight);
    border: 1px solid rgba(161, 180, 84, 0.3);
}
.risk-status-moderate {
    background-color: rgba(224, 201, 137, 0.2);
    color: var(--color-warning);
    border: 1px solid rgba(224, 201, 137, 0.3);
}
.risk-status-high {
    background-color: rgba(207, 134, 134, 0.2);
    color: var(--color-danger);
    border: 1px solid rgba(207, 134, 134, 0.3);
}
.light-theme .risk-status-good {
    background-color: rgba(118, 187, 110, 0.15);
    color: var(--color-accent3);
    border: 1px solid rgba(118, 187, 110, 0.3);
}
.light-theme .risk-status-moderate {
    background-color: rgba(249, 200, 127, 0.15);
    color: var(--color-warning);
    border: 1px solid rgba(249, 200, 127, 0.3);
}
.light-theme .risk-status-high {
    background-color: rgba(233, 141, 124, 0.15);
    color: var(--color-danger);
    border: 1px solid rgba(233, 141, 124, 0.3);
}
.modal-content {
    border-radius: 15px;
}
.btn-action {
    margin-right: 5px;
}

/* Additional table styling */
#usersTable {
    width: 100% !important;
    table-layout: fixed;
}

#usersTable th, 
#usersTable td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#usersTable th:nth-child(1),
#usersTable td:nth-child(1) {
    width: 10%;
}

#usersTable th:nth-child(2),
#usersTable td:nth-child(2) {
    width: 40%;
}

#usersTable th:nth-child(3),
#usersTable td:nth-child(3) {
    width: 30%;
}

#usersTable th:nth-child(4),
#usersTable td:nth-child(4) {
    width: 20%;
    text-align: center;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-add:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Center the risk status column */
.user-table th:nth-child(3),
.user-table td:nth-child(3) {
    text-align: center;
}

/* Preference badge styles */
.pref-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    background-color: rgba(118, 187, 110, 0.2);
    color: var(--color-accent3);
    border: 1px solid rgba(118, 187, 110, 0.3);
}

.pref-badge.empty {
    background-color: rgba(233, 141, 124, 0.2);
    color: var(--color-danger);
    border: 1px solid rgba(233, 141, 124, 0.3);
}

/* User details modal styles */
.user-details-section {
    margin-bottom: 20px;
    padding: 15px;
    background-color: rgba(161, 180, 84, 0.05);
    border-radius: 8px;
    border-left: 3px solid var(--color-highlight);
}

.user-details-section h3 {
    margin-bottom: 10px;
    color: var(--color-highlight);
    font-size: 16px;
}

.user-details-section p {
    margin: 5px 0;
    font-size: 14px;
}

.user-details-section .tag {
    display: inline-block;
    padding: 2px 8px;
    margin: 2px;
    background-color: rgba(161, 180, 84, 0.2);
    border-radius: 12px;
    font-size: 12px;
    color: var(--color-highlight);
}

.live-indicator {
    font-size: 12px;
    color: #4CAF50;
    font-weight: 600;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.user-info-details {
    margin-top: 10px;
}

.user-info-content {
    background-color: rgba(161, 180, 84, 0.1);
    border-radius: 8px;
    padding: 10px;
    margin-top: 5px;
    font-size: 13px;
    line-height: 1.4;
    max-height: 200px;
    overflow-y: auto;
}

.light-theme .user-info-content {
    background-color: rgba(142, 185, 110, 0.1);
}


    -->
    </style>
</head>
<body class="dark-theme">
    <div class="navbar">
        <div class="navbar-header">
            <div class="navbar-logo">
                <div class="navbar-logo-icon">
                    <img src="/logo.png" alt="Logo" style="width: 40px; height: 40px;">
                </div>
                <div class="navbar-logo-text">NutriSaur</div>
            </div>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="dash"><span class="navbar-icon"></span><span>Dashboard</span></a></li>

                <li><a href="event"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="ai"><span class="navbar-icon"></span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v1.0 • © 2023</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>

    <div class="dashboard">
        <header class="dashboard-header fade-in">
            <div class="dashboard-title">
                <h1>User Management</h1>
            </div>
            <div class="user-info">
                <button id="new-theme-toggle" class="new-theme-toggle-btn" title="Toggle theme">
                    <span class="new-theme-icon">🌙</span>
                </button>
            </div>
        </header>

            <div class="user-management-container">

        
        <div class="table-header">
            <div class="header-controls">
                <div class="search-row" style="justify-content: center; gap: 20px;">
                    <div class="search-container" style="width: 300px;">
                        <input type="text" id="searchInput" placeholder="Search username, email, or location..." class="search-input">
                        <button type="button" onclick="searchUsers()" class="search-btn">🔍</button>
                    </div>
                    <div class="location-filter-container" style="width: 300px;">
                        <select id="locationFilter" onchange="filterUsersByLocation()" class="location-select">
                            <option value="">All Locations</option>
                                <optgroup label="ABUCAY">
                                    <option value="ABUCAY">ABUCAY</option>
                                    <option value="Bangkal">Bangkal</option>
                                    <option value="Calaylayan (Pob.)">Calaylayan (Pob.)</option>
                                    <option value="Capitangan">Capitangan</option>
                                    <option value="Gabon">Gabon</option>
                                    <option value="Laon (Pob.)">Laon (Pob.)</option>
                                    <option value="Mabatang">Mabatang</option>
                                    <option value="Omboy">Omboy</option>
                                    <option value="Salian">Salian</option>
                                    <option value="Wawa (Pob.)">Wawa (Pob.)</option>
                                </optgroup>
                                <optgroup label="BAGAC">
                                    <option value="BAGAC">BAGAC</option>
                                    <option value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</option>
                                    <option value="Banawang">Banawang</option>
                                    <option value="Binuangan">Binuangan</option>
                                    <option value="Binukawan">Binukawan</option>
                                    <option value="Ibaba">Ibaba</option>
                                    <option value="Ibis">Ibis</option>
                                    <option value="Pag-asa (Wawa-Sibacan)">Pag-asa (Wawa-Sibacan)</option>
                                    <option value="Parang">Parang</option>
                                    <option value="Paysawan">Paysawan</option>
                                    <option value="Quinawan">Quinawan</option>
                                    <option value="San Antonio">San Antonio</option>
                                    <option value="Saysain">Saysain</option>
                                    <option value="Tabing-Ilog (Pob.)">Tabing-Ilog (Pob.)</option>
                                    <option value="Atilano L. Ricardo">Atilano L. Ricardo</option>
                                </optgroup>
                                <optgroup label="CITY OF BALANGA (Capital)">
                                    <option value="CITY OF BALANGA (Capital)">CITY OF BALANGA (Capital)</option>
                                    <option value="Bagumbayan">Bagumbayan</option>
                                    <option value="Cabog-Cabog">Cabog-Cabog</option>
                                    <option value="Munting Batangas (Cadre)">Munting Batangas (Cadre)</option>
                                    <option value="Cataning">Cataning</option>
                                    <option value="Central">Central</option>
                                    <option value="Cupang Proper">Cupang Proper</option>
                                    <option value="Cupang West">Cupang West</option>
                                    <option value="Dangcol (Bernabe)">Dangcol (Bernabe)</option>
                                    <option value="Ibayo">Ibayo</option>
                                    <option value="Malabia">Malabia</option>
                                    <option value="Poblacion">Poblacion</option>
                                    <option value="Pto. Rivas Ibaba">Pto. Rivas Ibaba</option>
                                    <option value="Pto. Rivas Itaas">Pto. Rivas Itaas</option>
                                    <option value="San Jose">San Jose</option>
                                    <option value="Sibacan">Sibacan</option>
                                    <option value="Camacho">Camacho</option>
                                    <option value="Talisay">Talisay</option>
                                    <option value="Tanato">Tanato</option>
                                    <option value="Tenejero">Tenejero</option>
                                    <option value="Tortugas">Tortugas</option>
                                    <option value="Tuyo">Tuyo</option>
                                    <option value="Bagong Silang">Bagong Silang</option>
                                    <option value="Cupang North">Cupang North</option>
                                    <option value="Doña Francisca">Doña Francisca</option>
                                    <option value="Lote">Lote</option>
                                </optgroup>
                                <optgroup label="DINALUPIHAN">
                                    <option value="DINALUPIHAN">DINALUPIHAN</option>
                                    <option value="Bangal">Bangal</option>
                                    <option value="Bonifacio (Pob.)">Bonifacio (Pob.)</option>
                                    <option value="Burgos (Pob.)">Burgos (Pob.)</option>
                                    <option value="Colo">Colo</option>
                                    <option value="Daang Bago">Daang Bago</option>
                                    <option value="Dalao">Dalao</option>
                                    <option value="Del Pilar (Pob.)">Del Pilar (Pob.)</option>
                                    <option value="Gen. Luna (Pob.)">Gen. Luna (Pob.)</option>
                                    <option value="Gomez (Pob.)">Gomez (Pob.)</option>
                                    <option value="Happy Valley">Happy Valley</option>
                                    <option value="Kataasan">Kataasan</option>
                                    <option value="Layac">Layac</option>
                                    <option value="Luacan">Luacan</option>
                                    <option value="Mabini Proper (Pob.)">Mabini Proper (Pob.)</option>
                                    <option value="Mabini Ext. (Pob.)">Mabini Ext. (Pob.)</option>
                                    <option value="Magsaysay">Magsaysay</option>
                                    <option value="Naparing">Naparing</option>
                                    <option value="New San Jose">New San Jose</option>
                                    <option value="Old San Jose">Old San Jose</option>
                                    <option value="Padre Dandan (Pob.)">Padre Dandan (Pob.)</option>
                                    <option value="Pag-asa">Pag-asa</option>
                                    <option value="Pagalanggang">Pagalanggang</option>
                                    <option value="Pinulot">Pinulot</option>
                                    <option value="Pita">Pita</option>
                                    <option value="Rizal (Pob.)">Rizal (Pob.)</option>
                                    <option value="Roosevelt">Roosevelt</option>
                                    <option value="Roxas (Pob.)">Roxas (Pob.)</option>
                                    <option value="Saguing">Saguing</option>
                                    <option value="San Benito">San Benito</option>
                                    <option value="San Isidro (Pob.)">San Isidro (Pob.)</option>
                                    <option value="San Pablo (Bulate)">San Pablo (Bulate)</option>
                                    <option value="San Ramon">San Ramon</option>
                                    <option value="San Simon">San Simon</option>
                                    <option value="Santo Niño">Santo Niño</option>
                                    <option value="Sapang Balas">Sapang Balas</option>
                                    <option value="Santa Isabel (Tabacan)">Santa Isabel (Tabacan)</option>
                                    <option value="Torres Bugauen (Pob.)">Torres Bugauen (Pob.)</option>
                                    <option value="Tucop">Tucop</option>
                                    <option value="Zamora (Pob.)">Zamora (Pob.)</option>
                                    <option value="Aquino">Aquino</option>
                                    <option value="Bayan-bayanan">Bayan-bayanan</option>
                                    <option value="Maligaya">Maligaya</option>
                                    <option value="Payangan">Payangan</option>
                                    <option value="Pentor">Pentor</option>
                                    <option value="Tubo-tubo">Tubo-tubo</option>
                                    <option value="Jose C. Payumo, Jr.">Jose C. Payumo, Jr.</option>
                                </optgroup>
                                <optgroup label="HERMOSA">
                                    <option value="HERMOSA">HERMOSA</option>
                                    <option value="A. Rivera (Pob.)">A. Rivera (Pob.)</option>
                                    <option value="Almacen">Almacen</option>
                                    <option value="Bacong">Bacong</option>
                                    <option value="Balsic">Balsic</option>
                                    <option value="Bamban">Bamban</option>
                                    <option value="Burgos-Soliman (Pob.)">Burgos-Soliman (Pob.)</option>
                                    <option value="Cataning (Pob.)">Cataning (Pob.)</option>
                                    <option value="Culis">Culis</option>
                                    <option value="Daungan (Pob.)">Daungan (Pob.)</option>
                                    <option value="Mabiga">Mabiga</option>
                                    <option value="Mabuco">Mabuco</option>
                                    <option value="Maite">Maite</option>
                                    <option value="Mambog - Mandama">Mambog - Mandama</option>
                                    <option value="Palihan">Palihan</option>
                                    <option value="Pandatung">Pandatung</option>
                                    <option value="Pulo">Pulo</option>
                                    <option value="Saba">Saba</option>
                                    <option value="San Pedro (Pob.)">San Pedro (Pob.)</option>
                                    <option value="Santo Cristo (Pob.)">Santo Cristo (Pob.)</option>
                                    <option value="Sumalo">Sumalo</option>
                                    <option value="Tipo">Tipo</option>
                                    <option value="Judge Roman Cruz Sr. (Mandama)">Judge Roman Cruz Sr. (Mandama)</option>
                                    <option value="Sacrifice Valley">Sacrifice Valley</option>
                                </optgroup>
                                <optgroup label="LIMAY">
                                    <option value="LIMAY">LIMAY</option>
                                    <option value="Alangan">Alangan</option>
                                    <option value="Kitang I">Kitang I</option>
                                    <option value="Kitang 2 & Luz">Kitang 2 & Luz</option>
                                    <option value="Lamao">Lamao</option>
                                    <option value="Landing">Landing</option>
                                    <option value="Poblacion">Poblacion</option>
                                    <option value="Reformista">Reformista</option>
                                    <option value="Townsite">Townsite</option>
                                    <option value="Wawa">Wawa</option>
                                    <option value="Duale">Duale</option>
                                    <option value="San Francisco de Asis">San Francisco de Asis</option>
                                    <option value="St. Francis II">St. Francis II</option>
                                </optgroup>
                                <optgroup label="MARIVELES">
                                    <option value="MARIVELES">MARIVELES</option>
                                    <option value="Alas-asin">Alas-asin</option>
                                    <option value="Alion">Alion</option>
                                    <option value="Batangas II">Batangas II</option>
                                    <option value="Cabcaben">Cabcaben</option>
                                    <option value="Lucanin">Lucanin</option>
                                    <option value="Baseco Country (Nassco)">Baseco Country (Nassco)</option>
                                    <option value="Poblacion">Poblacion</option>
                                    <option value="San Carlos">San Carlos</option>
                                    <option value="San Isidro">San Isidro</option>
                                    <option value="Sisiman">Sisiman</option>
                                    <option value="Balon-Anito">Balon-Anito</option>
                                    <option value="Biaan">Biaan</option>
                                    <option value="Camaya">Camaya</option>
                                    <option value="Ipag">Ipag</option>
                                    <option value="Malaya">Malaya</option>
                                    <option value="Maligaya">Maligaya</option>
                                    <option value="Mt. View">Mt. View</option>
                                    <option value="Townsite">Townsite</option>
                                </optgroup>
                                <optgroup label="MORONG">
                                    <option value="MORONG">MORONG</option>
                                    <option value="Binaritan">Binaritan</option>
                                    <option value="Mabayo">Mabayo</option>
                                    <option value="Nagbalayong">Nagbalayong</option>
                                    <option value="Poblacion">Poblacion</option>
                                    <option value="Sabang">Sabang</option>
                                </optgroup>
                                <optgroup label="ORANI">
                                    <option value="ORANI">ORANI</option>
                                    <option value="Bagong Paraiso (Pob.)">Bagong Paraiso (Pob.)</option>
                                    <option value="Balut (Pob.)">Balut (Pob.)</option>
                                    <option value="Bayan (Pob.)">Bayan (Pob.)</option>
                                    <option value="Calero (Pob.)">Calero (Pob.)</option>
                                    <option value="Paking-Carbonero (Pob.)">Paking-Carbonero (Pob.)</option>
                                    <option value="Centro II (Pob.)">Centro II (Pob.)</option>
                                    <option value="Dona">Dona</option>
                                    <option value="Kaparangan">Kaparangan</option>
                                    <option value="Masantol">Masantol</option>
                                    <option value="Mulawin">Mulawin</option>
                                    <option value="Pag-asa">Pag-asa</option>
                                    <option value="Palihan (Pob.)">Palihan (Pob.)</option>
                                    <option value="Pantalan Bago (Pob.)">Pantalan Bago (Pob.)</option>
                                    <option value="Pantalan Luma (Pob.)">Pantalan Luma (Pob.)</option>
                                    <option value="Parang Parang (Pob.)">Parang Parang (Pob.)</option>
                                    <option value="Centro I (Pob.)">Centro I (Pob.)</option>
                                    <option value="Sibul">Sibul</option>
                                    <option value="Silahis">Silahis</option>
                                    <option value="Tala">Tala</option>
                                    <option value="Talimundoc">Talimundoc</option>
                                    <option value="Tapulao">Tapulao</option>
                                    <option value="Tenejero (Pob.)">Tenejero (Pob.)</option>
                                    <option value="Tugatog">Tugatog</option>
                                    <option value="Wawa (Pob.)">Wawa (Pob.)</option>
                                    <option value="Apollo">Apollo</option>
                                    <option value="Kabalutan">Kabalutan</option>
                                    <option value="Maria Fe">Maria Fe</option>
                                    <option value="Puksuan">Puksuan</option>
                                    <option value="Tagumpay">Tagumpay</option>
                                </optgroup>
                                <optgroup label="ORION">
                                    <option value="ORION">ORION</option>
                                    <option value="Arellano (Pob.)">Arellano (Pob.)</option>
                                    <option value="Bagumbayan (Pob.)">Bagumbayan (Pob.)</option>
                                    <option value="Balagtas (Pob.)">Balagtas (Pob.)</option>
                                    <option value="Balut (Pob.)">Balut (Pob.)</option>
                                    <option value="Bantan">Bantan</option>
                                    <option value="Bilolo">Bilolo</option>
                                    <option value="Calungusan">Calungusan</option>
                                    <option value="Camachile">Camachile</option>
                                    <option value="Daang Bago (Pob.)">Daang Bago (Pob.)</option>
                                    <option value="Daang Bilolo (Pob.)">Daang Bilolo (Pob.)</option>
                                    <option value="Daang Pare">Daang Pare</option>
                                    <option value="General Lim (Kaput)">General Lim (Kaput)</option>
                                    <option value="Kapunitan">Kapunitan</option>
                                    <option value="Lati (Pob.)">Lati (Pob.)</option>
                                    <option value="Lusungan (Pob.)">Lusungan (Pob.)</option>
                                    <option value="Puting Buhangin">Puting Buhangin</option>
                                    <option value="Sabatan">Sabatan</option>
                                    <option value="San Vicente (Pob.)">San Vicente (Pob.)</option>
                                    <option value="Santo Domingo">Santo Domingo</option>
                                    <option value="Villa Angeles (Pob.)">Villa Angeles (Pob.)</option>
                                    <option value="Wakas (Pob.)">Wakas (Pob.)</option>
                                    <option value="Wawa (Pob.)">Wawa (Pob.)</option>
                                    <option value="Santa Elena">Santa Elena</option>
                                </optgroup>
                                <optgroup label="PILAR">
                                    <option value="PILAR">PILAR</option>
                                    <option value="Ala-uli">Ala-uli</option>
                                    <option value="Bagumbayan">Bagumbayan</option>
                                    <option value="Balut I">Balut I</option>
                                    <option value="Balut II">Balut II</option>
                                    <option value="Bantan Munti">Bantan Munti</option>
                                    <option value="Burgos">Burgos</option>
                                    <option value="Del Rosario (Pob.)">Del Rosario (Pob.)</option>
                                    <option value="Diwa">Diwa</option>
                                    <option value="Landing">Landing</option>
                                    <option value="Liyang">Liyang</option>
                                    <option value="Nagwaling">Nagwaling</option>
                                    <option value="Panilao">Panilao</option>
                                    <option value="Pantingan">Pantingan</option>
                                    <option value="Poblacion">Poblacion</option>
                                    <option value="Rizal">Rizal</option>
                                    <option value="Santa Rosa">Santa Rosa</option>
                                    <option value="Wakas North">Wakas North</option>
                                    <option value="Wakas South">Wakas South</option>
                                    <option value="Wawa">Wawa</option>
                                </optgroup>
                                <optgroup label="SAMAL">
                                    <option value="SAMAL">SAMAL</option>
                                    <option value="East Calaguiman (Pob.)">East Calaguiman (Pob.)</option>
                                    <option value="East Daang Bago (Pob.)">East Daang Bago (Pob.)</option>
                                    <option value="Ibaba (Pob.)">Ibaba (Pob.)</option>
                                    <option value="Imelda">Imelda</option>
                                    <option value="Lalawigan">Lalawigan</option>
                                    <option value="Palili">Palili</option>
                                    <option value="San Juan (Pob.)">San Juan (Pob.)</option>
                                    <option value="San Roque (Pob.)">San Roque (Pob.)</option>
                                    <option value="Santa Lucia">Santa Lucia</option>
                                    <option value="Sapa">Sapa</option>
                                    <option value="Tabing Ilog">Tabing Ilog</option>
                                    <option value="Gugo">Gugo</option>
                                    <option value="West Calaguiman (Pob.)">West Calaguiman (Pob.)</option>
                                    <option value="West Daang Bago (Pob.)">West Daang Bago (Pob.)</option>
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    <div class="action-row" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(161, 180, 84, 0.2);">
                        <button class="btn btn-add" onclick="showAddUserModal()">
                            <span class="btn-icon">+</span>
                            <span class="btn-text">Add User</span>
                        </button>

                        <button class="btn btn-add" onclick="downloadCSVTemplate()">
                            <span class="btn-icon">📥</span>
                            <span class="btn-text">Download Template</span>
                        </button>
                        <button class="btn btn-add" onclick="showCSVImportModal()">
                            <span class="btn-icon">📁</span>
                            <span class="btn-text">Import CSV</span>
                        </button>
                        <button class="btn btn-danger" onclick="deleteUsersByLocation()">
                            <span class="btn-icon">🗑️</span>
                            <span class="btn-text">Delete by Location</span>
                        </button>
                        <button class="btn btn-danger" onclick="deleteAllUsers()">
                            <span class="btn-icon">🗑️</span>
                            <span class="btn-text">Delete All Users</span>
                        </button>




                    </div>
                </div>
            </div>
            

            

            
            <div id="no-users-message" style="display:none;" class="no-data-message">
                No users found in the database. Add your first user!
            </div>
            
            <table class="user-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Risk Level</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                        <tbody id="usersTableBody">
                    <!-- Table data will be loaded via AJAX -->
                </tbody>
            </table>
            
            <!-- User Details Modal -->
            <div id="userDetailsModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeUserDetailsModal()">&times;</span>
                    <h2>User Details</h2>
                    <div id="userDetailsContent">
                        <!-- User details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditUserModal()">&times;</span>
                <h2>Edit User Profile</h2>
                <form id="editUserFormModal">
                    <!-- Basic User Info -->
                    <div class="form-group">
                        <label for="editUsername">Name *</label>
                        <input type="text" id="editUsername" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="editEmail">Email *</label>
                        <input type="email" id="editEmail" name="user_email" readonly>
                    </div>
                    
                    <!-- Basic Info Section -->
                    <h3 style="color: #8EB56E; margin: 20px 0 15px 0; border-bottom: 2px solid #8EB56E; padding-bottom: 5px;">Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="editBirthday">Birthday *</label>
                        <input type="date" id="editBirthday" name="birthday" required>
                    </div>
                    <div class="form-group">
                        <label for="editAge">Age</label>
                        <input type="number" id="editAge" name="age" min="0" max="120" readonly>
                        <small style="color: #8EB56E;">(Calculated automatically from birthday)</small>
                    </div>
                    <div class="form-group">
                        <label for="editGender">Gender *</label>
                        <select id="editGender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="boy">Boy</option>
                            <option value="girl">Girl</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editWeight">Weight (kg) *</label>
                        <input type="number" id="editWeight" name="weight" step="0.1" min="2" max="300" required>
                    </div>
                    <div class="form-group">
                        <label for="editHeight">Height (cm) *</label>
                        <input type="number" id="editHeight" name="height" step="0.1" min="30" max="250" required>
                    </div>
                    <div class="form-group">
                        <label for="editBmi">BMI</label>
                        <input type="number" id="editBmi" name="bmi" step="0.1" min="10" max="50" readonly>
                        <small style="color: #8EB56E;">(Calculated automatically from weight and height)</small>
                    </div>
                    <div class="form-group">
                        <label for="editMuac">MUAC (cm)</label>
                        <input type="number" id="editMuac" name="muac" step="0.1" min="5" max="50">
                    </div>
                    <div class="form-group">
                        <label for="editGoal">Nutrition Goal</label>
                        <select id="editGoal" name="goal">
                            <option value="">Select Goal</option>
                            <option value="weight_gain">Weight Gain</option>
                            <option value="weight_loss">Weight Loss</option>
                            <option value="maintain">Maintain Weight</option>
                            <option value="muscle_gain">Muscle Gain</option>
                        </select>
                    </div>
                    <!-- Location & Income Section -->
                    <h3 style="color: #8EB56E; margin: 20px 0 15px 0; border-bottom: 2px solid #8EB56E; padding-bottom: 5px;">Location & Income</h3>
                    
                    <div class="form-group">
                        <label for="editBarangay">Barangay *</label>
                        <select id="editBarangay" name="barangay" required>
                            <option value="">Select Barangay</option>
                            <option value="Alion">Alion</option>
                                <option value="Bangkal">Bangkal</option>
                            <option value="Cabcaben">Cabcaben</option>
                                <option value="Camacho">Camacho</option>
                            <option value="Daan Bago">Daan Bago</option>
                                <option value="Daang Bago">Daang Bago</option>
                            <option value="Daang Pare">Daang Pare</option>
                            <option value="Del Pilar">Del Pilar</option>
                            <option value="General Lim">General Lim</option>
                            <option value="Kalaklan">Kalaklan</option>
                                <option value="Lamao">Lamao</option>
                            <option value="Lote">Lote</option>
                            <option value="Luakan">Luakan</option>
                                <option value="Malaya">Malaya</option>
                            <option value="Mountain View">Mountain View</option>
                            <option value="Paco">Paco</option>
                            <option value="Pamantayan">Pamantayan</option>
                                <option value="Poblacion">Poblacion</option>
                            <option value="San Antonio">San Antonio</option>
                            <option value="San Miguel">San Miguel</option>
                            <option value="San Nicolas">San Nicolas</option>
                            <option value="San Pedro">San Pedro</option>
                            <option value="San Roque">San Roque</option>
                            <option value="San Vicente">San Vicente</option>
                            <option value="Santa Rita">Santa Rita</option>
                            <option value="Santo Niño">Santo Niño</option>
                            <option value="Tuyo">Tuyo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editIncome">Household Income *</label>
                        <select id="editIncome" name="income" required>
                            <option value="">Select Income Bracket</option>
                            <option value="Below PHP 12,030/month (Below poverty line)">Below PHP 12,030/month (Below poverty line)</option>
                            <option value="PHP 12,031–20,000/month (Low)">PHP 12,031–20,000/month (Low)</option>
                            <option value="PHP 20,001–40,000/month (Middle)">PHP 20,001–40,000/month (Middle)</option>
                            <option value="Above PHP 40,000/month (High)">Above PHP 40,000/month (High)</option>
                        </select>
                    </div>
                    
                    <!-- Dietary Preferences Section -->
                    <h3 style="color: #8EB56E; margin: 20px 0 15px 0; border-bottom: 2px solid #8EB56E; padding-bottom: 5px;">Dietary Preferences</h3>
                    
                    <div class="form-group">
                        <label for="editAllergies">Food Allergies</label>
                        <input type="text" id="editAllergies" name="allergies" placeholder="e.g., peanuts, dairy, eggs">
                        <small style="color: #8EB56E;">(Separate multiple allergies with commas)</small>
                    </div>
                    <div class="form-group">
                        <label for="editDietPrefs">Dietary Preferences</label>
                        <input type="text" id="editDietPrefs" name="diet_prefs" placeholder="e.g., vegetarian, vegan, halal">
                                </div>
                    <div class="form-group">
                        <label for="editAvoidFoods">Foods to Avoid</label>
                        <input type="text" id="editAvoidFoods" name="avoid_foods" placeholder="e.g., pork, shellfish, alcohol">
                        <small style="color: #8EB56E;">(Separate multiple foods with commas)</small>
                            </div>
                            
                    <!-- Risk Assessment Section -->
                    <h3 style="color: #8EB56E; margin: 20px 0 15px 0; border-bottom: 2px solid #8EB56E; padding-bottom: 5px;">Risk Assessment</h3>
                    
                    <div class="form-group">
                        <label for="editRiskScore">Risk Score</label>
                        <input type="number" id="editRiskScore" name="risk_score" min="0" max="10" readonly>
                        <small style="color: #8EB56E;">(Calculated automatically from BMI and age)</small>
                    </div>
                    

                </form>
                
                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveEditedUser()">Save Changes</button>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div id="addUserModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddUserModal()">&times;</span>
                <h2>Add New User (Mobile App Compatible)</h2>
                <form id="addUserForm">
                    <!-- Basic User Info -->
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <!-- Basic Info Section (Section 0) -->
                    <h3 style="color: var(--color-highlight); margin: 20px 0 15px 0; border-bottom: 2px solid var(--color-highlight); padding-bottom: 5px;">Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="birthday">Birthday *</label>
                        <input type="date" id="birthday" name="birthday" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="boy">Boy</option>
                            <option value="girl">Girl</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="weight">Weight (kg) *</label>
                        <input type="number" id="weight" name="weight" step="0.1" min="2" max="300" required>
                    </div>
                    <div class="form-group">
                        <label for="height">Height (cm) *</label>
                        <input type="number" id="height" name="height" step="0.1" min="30" max="250" required>
                    </div>
                    <div class="form-group">
                        <label for="barangay">Barangay *</label>
                        <select id="barangay" name="barangay" required>
                            <option value="">Select Barangay</option>
                            <option value="Alion">Alion</option>
                            <option value="Bangkal">Bangkal</option>
                            <option value="Cabcaben">Cabcaben</option>
                            <option value="Camacho">Camacho</option>
                            <option value="Daan Bago">Daan Bago</option>
                            <option value="Daang Bago">Daang Bago</option>
                            <option value="Daang Pare">Daang Pare</option>
                            <option value="Del Pilar">Del Pilar</option>
                            <option value="General Lim">General Lim</option>
                            <option value="Kalaklan">Kalaklan</option>
                            <option value="Lamao">Lamao</option>
                            <option value="Lote">Lote</option>
                            <option value="Luakan">Luakan</option>
                            <option value="Malaya">Malaya</option>
                            <option value="Mountain View">Mountain View</option>
                            <option value="Paco">Paco</option>
                            <option value="Pamantayan">Pamantayan</option>
                            <option value="Poblacion">Poblacion</option>
                            <option value="San Antonio">San Antonio</option>
                            <option value="San Miguel">San Miguel</option>
                            <option value="San Nicolas">San Nicolas</option>
                            <option value="San Pedro">San Pedro</option>
                            <option value="San Roque">San Roque</option>
                            <option value="San Vicente">San Vicente</option>
                            <option value="Santa Rita">Santa Rita</option>
                            <option value="Santo Niño">Santo Niño</option>
                            <option value="Tuyo">Tuyo</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="income">Household Income *</label>
                        <select id="income" name="income" required>
                            <option value="">Select Income Bracket</option>
                            <option value="Below PHP 12,030/month (Below poverty line)">Below PHP 12,030/month (Below poverty line)</option>
                            <option value="PHP 12,031–20,000/month (Low)">PHP 12,031–20,000/month (Low)</option>
                            <option value="PHP 20,001–40,000/month (Middle)">PHP 20,001–40,000/month (Middle)</option>
                            <option value="Above PHP 40,000/month (High)">Above PHP 40,000/month (High)</option>
                        </select>
                    </div>
                    
                    <!-- Screening Questions Section (Section 1) -->
                    <h3 style="color: var(--color-highlight); margin: 20px 0 15px 0; border-bottom: 2px solid var(--color-highlight); padding-bottom: 5px;">Screening Questions</h3>
                    
                    <div class="form-group">
                        <label for="swelling">Swelling (Edema) *</label>
                        <select id="swelling" name="swelling" required>
                            <option value="">Select Answer</option>
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="weightLoss">Weight Loss Status *</label>
                        <select id="weightLoss" name="weightLoss" required>
                            <option value="">Select Answer</option>
                            <option value="none">None</option>
                            <option value="<5%">Less than 5%</option>
                            <option value="5-10%">5-10%</option>
                            <option value=">10%">More than 10%</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dietaryDiversity">Dietary Diversity (Food Groups) *</label>
                        <input type="number" id="dietaryDiversity" name="dietaryDiversity" min="0" max="10" required placeholder="0-10 food groups">
                    </div>
                    <div class="form-group">
                        <label for="feedingBehavior">Feeding Behavior *</label>
                        <select id="feedingBehavior" name="feedingBehavior" required>
                            <option value="">Select Answer</option>
                            <option value="good">Good</option>
                            <option value="moderate">Moderate</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="muac">MUAC (Mid-Upper Arm Circumference in cm)</label>
                        <input type="number" id="muac" name="muac" step="0.1" min="0" max="50" placeholder="For children 6-59 months">
                    </div>
                    
                    <!-- Physical Signs -->
                    <div class="form-group">
                        <label>Physical Signs (Select all that apply)</label>
                        <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="physicalThin" value="thin"> Thin
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="physicalShorter" value="shorter"> Shorter
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="physicalWeak" value="weak"> Weak
                            </label>
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="physicalNone" value="none"> None
                            </label>
                        </div>
                    </div>
                    
                    <!-- Clinical Risk Factors -->
                    <h3 style="color: var(--color-highlight); margin: 20px 0 15px 0; border-bottom: 2px solid var(--color-highlight); padding-bottom: 5px;">Clinical Risk Factors</h3>
                    
                    <div class="form-group">
                        <label for="recentIllness">Recent Acute Illness (Past 2 weeks)</label>
                        <select id="recentIllness" name="recentIllness">
                            <option value="false">No</option>
                            <option value="true">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="eatingDifficulty">Difficulty Chewing/Swallowing</label>
                        <select id="eatingDifficulty" name="eatingDifficulty">
                            <option value="false">No</option>
                            <option value="true">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="foodInsecurity">Food Insecurity / Skipped Meals</label>
                        <select id="foodInsecurity" name="foodInsecurity">
                            <option value="false">No</option>
                            <option value="true">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="micronutrientDeficiency">Visible Signs of Micronutrient Deficiency</label>
                        <select id="micronutrientDeficiency" name="micronutrientDeficiency">
                            <option value="false">No</option>
                            <option value="true">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="functionalDecline">Functional Decline (Older Adults)</label>
                        <select id="functionalDecline" name="functionalDecline">
                            <option value="false">No</option>
                            <option value="true">Yes</option>
                        </select>
                    </div>
                    
                    <!-- Dietary Preferences -->
                    <h3 style="color: var(--color-highlight); margin: 20px 0 15px 0; border-bottom: 2px solid var(--color-highlight); padding-bottom: 5px;">Dietary Preferences</h3>
                    
                    <div class="form-group">
                        <label for="allergies">Food Allergies (semicolon separated)</label>
                        <input type="text" id="allergies" name="allergies" placeholder="e.g., Peanuts; Dairy; Eggs">
                    </div>
                    <div class="form-group">
                        <label for="dietPrefs">Diet Preferences (semicolon separated)</label>
                        <input type="text" id="dietPrefs" name="dietPrefs" placeholder="e.g., Vegetarian; Vegan; Halal">
                    </div>
                    <div class="form-group">
                        <label for="avoidFoods">Foods to Avoid</label>
                        <textarea id="avoidFoods" name="avoidFoods" placeholder="List any foods to avoid..."></textarea>
                    </div>
                    
                    <button type="button" class="btn btn-submit" id="addUserBtn">Add User</button>
                </form>
            </div>
        </div>
        
        <!-- Edit User Modal -->
        <div id="editUserModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditUserModal()">&times;</span>
                <h2>Edit User</h2>
                <form id="editUserFormSimple">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-group">
                        <label for="edit_username">Username</label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_riskLevel">Risk Level</label>
                        <select id="edit_riskLevel" name="riskLevel">
                            <option value="Good">Good Status</option>
                            <option value="At Risk">Moderate Risk</option>
                            <option value="Malnourished">High Risk</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-submit" id="saveUserBtn">Save Changes</button>
                </form>
            </div>
        </div>
        
        <!-- CSV Import Modal -->
        <div id="csvImportModal" class="modal">
            <div class="modal-content csv-import-modal-content">
                <span class="close" onclick="closeCSVImportModal()">&times;</span>
                <h2>Import Users from CSV</h2>
                <div style="height: calc(85vh - 120px); overflow-y: auto; padding-right: 10px;">
                
                <!-- Status Message Area -->
                <div id="csvStatusMessage" style="display: none; margin-bottom: 20px; padding: 15px; border-radius: 8px; font-weight: 600;"></div>
                
                <div class="csv-import-info">
                    <div style="background-color: rgba(233, 141, 124, 0.2); border: 2px solid var(--color-danger); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <h4 style="color: var(--color-danger); margin: 0 0 10px 0;">⚠️ CRITICAL: EXACT FORMAT REQUIRED</h4>
                                <p style="margin: 0; color: var(--color-danger); font-weight: 600;">CSV data MUST use EXACTLY the same answer options as the mobile app. Any deviation will cause validation errors and prevent import.</p>
                            </div>
                            <div class="info-icon-container">
                                <span class="info-icon" onclick="toggleCSVInfo()" style="cursor: pointer; font-size: 24px; margin-left: 15px;">ℹ️</span>
                                <div class="info-tooltip" id="csvInfoTooltip" style="display: none;">
                                    <div class="tooltip-content">
                                        <h5>📋 CSV Import Instructions</h5>
                                        <p><strong>1.</strong> Download template with exact mobile app formats</p>
                                        <p><strong>2.</strong> Fill data using ONLY specified answer options</p>
                                        <p><strong>3.</strong> Upload and preview before import</p>
                                        <p><strong>4.</strong> Confirm import to add all users</p>
                                        
                                        <h6>📊 Required Fields:</h6>
                                        <p>user_email, name, birthday, gender, weight, height, barangay, income</p>
                                        
                                        <h6>📍 Valid Barangay Values:</h6>
                                        <p>Alion, Bangkal, Cabcaben, Camacho, Daan Bago, Daang Bago, Daang Pare, Del Pilar, General Lim, Kalaklan, Lamao, Lote, Luakan, Malaya, Mountain View, Paco, Pamantayan, Poblacion, San Antonio, San Miguel, San Nicolas, San Pedro, San Roque, San Vicente, Santa Rita, Santo Niño, Tuyo</p>
                                        
                                        <h6>💰 Valid Income Values:</h6>
                                        <p><strong>Full values:</strong> Below PHP 12,030/month (Below poverty line), PHP 12,031–20,000/month (Low), PHP 20,001–40,000/month (Middle), Above PHP 40,000/month (High)</p>
                                        <p><strong>Simplified values:</strong> Below poverty line, Low, Middle, High</p>
                                        
                                        <h6>⚠️ CRITICAL:</h6>
                                        <p>Use exact values as shown in the template for proper import!</p>
                                        
                                        <h6>💡 Tip:</h6>
                                        <p>Age and BMI are automatically calculated from birthday, weight, and height!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="csvImportForm">
                    <div class="csv-upload-area" id="uploadArea" onclick="document.getElementById('csvFile').click()" style="cursor: pointer;" 
                         ondragover="handleDragOver(event)" 
                         ondrop="handleDrop(event)" 
                         ondragenter="handleDragEnter(event)" 
                         ondragleave="handleDragLeave(event)">
                        <div class="upload-icon">📄</div>
                        <div class="upload-text">
                            <h4>Upload CSV File</h4>
                            <p>Click to select or drag and drop your CSV file here</p>
                            <p class="csv-format">Format: user_email, name, birthday, gender, weight, height, barangay, income</p>
                            <p class="csv-format-small">💡 Click the ℹ️ icon above for detailed field descriptions and valid values</p>
                            <p class="csv-format-small">📍 <strong>Important:</strong> Barangay and Income must use exact values from the dropdown options</p>
                        </div>
                        <input type="file" id="csvFile" name="csvFile" accept=".csv" style="position: absolute; left: -9999px; opacity: 0; pointer-events: none;" onchange="handleFileSelect(this)">
                    </div>
                    
                    <div class="csv-preview" id="csvPreview" style="display: none;">
                        <h4>📋 Preview (First 5 rows)</h4>
                        <div class="csv-preview-container">
                            <div id="csvPreviewContent"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="skipDuplicates" name="skipDuplicates">
                            Skip duplicate emails (recommended)
                        </label>
                        <small style="color: var(--color-warning); display: block; margin-top: 5px;">
                            ⚠️ Duplicate emails will be skipped if checked, or will cause errors if unchecked
                        </small>
                    </div>

                    
                    <div class="csv-actions">
                        <button type="button" class="btn btn-submit" id="importCSVBtn" disabled onclick="processCSVImport()">📥 Import CSV</button>
                        <button type="button" class="btn btn-cancel" id="cancelBtn" style="display: none;" onclick="cancelUpload()">❌ Cancel Upload</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Helper functions
    function randomIntFromRange(min, max) {
        return Math.floor(Math.random() * (max - min + 1) + min);
    }

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Optimized theme loading to prevent flash
    function loadSavedTheme() {
        const savedTheme = localStorage.getItem('nutrisaur-theme');
        const theme = savedTheme === 'light' ? 'light-theme' : 'dark-theme';
        
        // Remove all theme classes first
        document.documentElement.classList.remove('dark-theme', 'light-theme');
        // Add the correct theme class
        document.documentElement.classList.add(theme);
    }

    // Load theme on page load (backup)
    window.addEventListener('DOMContentLoaded', () => {
        loadSavedTheme();
    });

    // User Management Functions
    function showAddUserModal() {
        document.getElementById('addUserModal').style.display = 'block';
    }

    function closeAddUserModal() {
        document.getElementById('addUserModal').style.display = 'none';
        document.getElementById('addUserForm').reset();
    }

    function showEditUserModal() {
        document.getElementById('editUserModal').style.display = 'block';
    }

    function closeEditUserModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }

    // User data model and controller
    var users = [];

    // API URLs
        const API_BASE_URL = window.location.origin;
    const GET_USERS_URL = API_BASE_URL + '/unified_api.php';
    const MANAGE_USER_URL = API_BASE_URL + '/unified_api.php';
    
    // Function to load users from the server with smooth updates
    window.loadUsersInProgress = false;
    window.lastCSVImportTime = 0;
    
    // Function to show real-time status indicator
    function showRealTimeStatus(message = 'Updating data in real-time...') {
        const statusDiv = document.getElementById('realTimeStatus');
        const statusText = document.getElementById('statusText');
        if (statusDiv && statusText) {
            statusText.textContent = message;
            statusDiv.style.display = 'block';
        }
    }
    
    // Function to hide real-time status indicator
    function hideRealTimeStatus() {
        const statusDiv = document.getElementById('realTimeStatus');
        if (statusDiv) {
            statusDiv.style.display = 'none';
        }
    }
    
    function loadUsers() {
        if (window.loadUsersInProgress) {
            console.log('loadUsers already in progress, skipping...');
            return;
        }
        
        window.loadUsersInProgress = true;
        console.log(`loadUsers function called at ${new Date().toLocaleTimeString()} - fetching real-time data...`);
        

        
        // Don't clear the table immediately - let it update smoothly
        const tbody = document.querySelector('#usersTableBody');
        if (!tbody) {
            window.loadUsersInProgress = false;
            return;
        }
        
        // Create an XMLHttpRequest to fetch app users with risk data
        const xhr = new XMLHttpRequest();
        
        xhr.open('GET', API_BASE_URL + '/unified_api.php?endpoint=usm&t=' + Date.now(), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                console.log('XHR readyState:', xhr.readyState, 'Status:', xhr.status);
                
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        console.log('USM API Response:', data);
                        
                        if (data.error) {
                            console.error('API Error:', data.error);
                            const tbody = document.querySelector('#usersTableBody');
                            if (tbody) {
                                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--color-danger);">Error loading users: ' + data.error + '</td></tr>';
                            }
                            console.error('Error loading users:', data.error);
                            return;
                        }
                        
                        // Debug the API response structure
                        console.log('API Response structure:', {
                            hasUsers: !!data.users,
                            usersType: typeof data.users,
                            isArray: Array.isArray(data.users),
                            usersLength: data.users ? (Array.isArray(data.users) ? data.users.length : Object.keys(data.users).length) : 0,
                            responseKeys: Object.keys(data)
                        });
                        
                        // Check if users exist in different possible locations
                        let users = data.users;
                        console.log('Initial users value:', users);
                        console.log('Users type:', typeof users);
                        console.log('Users is array:', Array.isArray(users));
                        console.log('Users length:', users ? users.length : 'null/undefined');
                        
                        if (!users && data.data) {
                            users = data.data; // The API returns users directly in data.data
                            console.log('Found users in data.data');
                        } else if (!users && data.result && data.result.users) {
                            users = data.result.users;
                            console.log('Found users in data.result.users');
                        } else if (!users && data.response && data.response.users) {
                            users = data.response.users;
                            console.log('Found users in data.response.users');
                        }
                        
                        console.log('Final users value after fallback checks:', users);
                        console.log('Final users length:', users ? users.length : 'null/undefined');
                        
                        if (!users || (Array.isArray(users) && users.length === 0)) {
                            console.warn('No users data received from API');
                            
                            // Check if this might be a timing issue after CSV import
                            const currentTime = Date.now();
                            const lastImportTime = window.lastCSVImportTime || 0;
                            const timeSinceImport = currentTime - lastImportTime;
                            
                            if (timeSinceImport < 10000) { // Extended to 10 seconds for better reliability
                                console.log('Possible timing issue after CSV import, retrying in 3 seconds...');
                                console.log('Checking for newly imported users...');
                                
                                setTimeout(() => {
                                    loadUsers(); // Retry loading users
                                }, 3000);
                                return;
                            }
                            
                            // Always clear the table when API returns no users
                            const tbody = document.querySelector('#usersTableBody');
                            if (tbody) {
                                console.log('API returned no users - clearing table...');
                                console.log('Current table rows before clearing:', tbody.children.length);
                                tbody.innerHTML = '<tr data-no-users><td colspan="6" style="text-align: center; padding: 20px; color: var(--color-text); font-style: italic;">No users found in database</td></tr>';
                                console.log('Table cleared - no users found in database');
                                console.log('Table rows after clearing:', tbody.children.length);
                            }
                            return;
                        }
                        
                        // Store users globally for other functions to access
                        window.currentUsers = users;
                        
                        console.log('Users loaded:', typeof users === 'object' && !Array.isArray(users) ? Object.keys(users).length : users.length);
                        

                        
                        // Enhanced debugging for user data structure
                        if (users && Object.keys(users).length > 0) {
                            const sampleUser = Array.isArray(users) ? users[0] : users[Object.keys(users)[0]];
                            console.log('Sample user detailed structure:', {
                                username: sampleUser.username,
                                email: sampleUser.email,
                                risk_score: sampleUser.risk_score,
                                barangay: sampleUser.barangay,
                                birthday: sampleUser.birthday || 'No data',
                                age: sampleUser.age || 'No data',
                                all_keys: Object.keys(sampleUser)
                            });
                        }
                        
                        // Store users globally for other functions to access
                        window.currentUsers = users;
                        
                        // Get existing table body
                        const tbody = document.querySelector('#usersTableBody');
                        if (!tbody) {
                            console.error('Table body element not found');
                            return;
                        }
                        
                        // Get existing rows for comparison
                        const existingRows = Array.from(tbody.querySelectorAll('tr'));
                        
                        // Convert users object to array if needed
                        let usersArray = users;
                        if (typeof users === 'object' && !Array.isArray(users)) {
                            usersArray = Object.values(users);
                        }
                        
                        // Update or add rows smoothly with real-time data
                        usersArray.forEach((user, index) => {
                            console.log(`Processing user ${index + 1}:`, user.username, user.email);
                            
                            const existingRow = existingRows.find(row => {
                                const viewBtn = row.querySelector('.btn-edit');
                                return viewBtn && viewBtn.getAttribute('data-email') === user.email;
                            });
                            
                            // Determine risk level with better fallback logic
                            const riskScore = user.risk_score || 0;
                            let riskLevel = 'Low';
                            let riskClass = 'low-risk';
                            
                            if (riskScore >= 75) {
                                riskLevel = 'Severe';
                                riskClass = 'severe-risk';
                            } else if (riskScore >= 50) {
                                riskLevel = 'High';
                                riskClass = 'high-risk';
                            } else if (riskScore >= 25) {
                                riskLevel = 'Moderate';
                                riskClass = 'moderate-risk';
                            }
                            
                            // Enhanced location extraction logic
                            let userLocation = 'N/A';
                            
                            // Use direct barangay field (no more screening_answers)
                            if (user.barangay && user.barangay !== 'null' && user.barangay !== '') {
                                userLocation = user.barangay;
                            }
                            
                            const newRowHTML = `
                                <td>${index + 1}</td>
                                <td>${user.username || user.name || 'N/A'}</td>
                                <td>${user.email || 'N/A'}</td>
                                <td><span class="risk-badge ${riskClass}">${riskLevel}</span></td>
                                <td>${userLocation}</td>
                                <td>
                                    <button class="btn-edit" data-email="${user.email}" onclick="viewUserDetails('${user.email}')">View</button>
                                    <button class="btn-edit btn-warning" data-email="${user.email}" onclick="editUser('${user.email}')">Edit</button>
                                    <button class="btn-delete" data-email="${user.email}" onclick="deleteUser('${user.email}')">Delete</button>
                                </td>
                            `;
                            
                            if (existingRow) {
                                // Update existing row with new data
                                existingRow.innerHTML = newRowHTML;
                            } else {
                                // Add new row
                                const newRow = document.createElement('tr');
                                newRow.innerHTML = newRowHTML;
                                tbody.appendChild(newRow);
                            }
                        });
                        
                        // Simple table update - no complex row removal needed
                        console.log(`Table updated with ${usersArray.length} users`);
                        
                        // Clear any existing "No users found" message
                        if (tbody) {
                            const noUsersRow = tbody.querySelector('tr td[colspan="5"]');
                            if (noUsersRow && noUsersRow.textContent.includes('No users found')) {
                                noUsersRow.closest('tr').remove();
                            }
                        }
                        
                        // Update user count if element exists
                        const userCountElement = document.getElementById('userCount');
                        if (userCountElement) {
                            userCountElement.textContent = usersArray.length;
                        }
                        
                        // Log success
                        console.log(`Data updated successfully (${usersArray.length} users)`);
                        
                    } catch (error) {
                        console.error('Error parsing USM data:', error);
                        console.error('Error loading data');
                    }
                } else {
                    console.error('Failed to load users:', xhr.status);
                    console.error('Failed to load data');
                }
            }
        };
        
        xhr.send();
        
        // Reset the flag when XHR completes (either success or error)
        xhr.onloadend = function() {
                                        window.loadUsersInProgress = false;
            
        };
    }

    // Manual refresh function removed - no longer needed

    // Function to attach event listeners to table row buttons
    function attachRowEventListeners(row) {
        console.log('Attaching event listeners to row');
        
        // Since we're using event delegation, we don't need to attach individual listeners
        // This function is kept for compatibility but doesn't add listeners
        const viewBtn = row.querySelector('.btn-edit:not(.btn-warning)');
        const editBtn = row.querySelector('.btn-edit.btn-warning');
        const deleteBtn = row.querySelector('.btn-delete');
        
        console.log('Found buttons - View:', !!viewBtn, 'Edit:', !!editBtn, 'Delete:', !!deleteBtn);
        console.log('Using event delegation instead of individual listeners');
    }

    // Helper functions for user data processing
    function calculateAge(birthday) {
        const birthDate = new Date(birthday);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            return age - 1;
        }
        return age;
    }

    function calculateBMI(weight, height) {
        const heightM = height / 100;
        return (weight / (heightM * heightM)).toFixed(2);
    }

    function getPhysicalSignsString(thin, shorter, weak, none) {
        const signs = [];
        if (thin) signs.push('thin');
        if (shorter) signs.push('shorter');
        if (weak) signs.push('weak');
        if (none) signs.push('none');
        return signs.join(', ');
    }

    function getClinicalRiskFactorsString(recentIllness, eatingDifficulty, foodInsecurity, micronutrientDeficiency, functionalDecline) {
        const factors = [];
        if (recentIllness) factors.push('recent_illness');
        if (eatingDifficulty) factors.push('eating_difficulty');
        if (foodInsecurity) factors.push('food_insecurity');
        if (micronutrientDeficiency) factors.push('micronutrient_deficiency');
        if (functionalDecline) factors.push('functional_decline');
        return factors.join(', ');
    }

    function getMalnutritionRiskLevel(riskScore) {
        if (riskScore >= 70) return 'Critical';
        if (riskScore >= 50) return 'High';
        if (riskScore >= 30) return 'Moderate';
        return 'Low';
    }

    // Database modification functions
    function addUserToDatabase(userData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', API_BASE_URL + '/unified_api.php?endpoint=add_user', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resolve(response);
                            } else {
                                reject(new Error(response.error || 'Failed to add user'));
                            }
                        } catch (error) {
                            reject(new Error('Invalid response format'));
                        }
                    } else {
                        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                    }
                }
            };
            
            xhr.onerror = () => reject(new Error('Network error'));
            xhr.send(JSON.stringify(userData));
        });
    }

    function updateUserInDatabase(userData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', API_BASE_URL + '/unified_api.php?endpoint=update_user', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resolve(response);
                            } else {
                                reject(new Error(response.error || 'Failed to update user'));
                            }
                        } catch (error) {
                            reject(new Error('Invalid response format'));
                        }
                    } else {
                        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                    }
                }
            };
            
            xhr.onerror = () => reject(new Error('Network error'));
            xhr.send(JSON.stringify(userData));
        });
    }

    function deleteUserFromDatabase(userEmail) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const url = API_BASE_URL + '/unified_api.php?endpoint=delete_user';
            console.log('deleteUserFromDatabase: Calling URL:', url);
            
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('deleteUserFromDatabase: Response status:', xhr.status);
                    console.log('deleteUserFromDatabase: Response headers:', xhr.getAllResponseHeaders());
                    console.log('deleteUserFromDatabase: Response text (first 200 chars):', xhr.responseText.substring(0, 200));
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resolve(response);
                            } else {
                                reject(new Error(response.error || 'Failed to delete user'));
                            }
                        } catch (error) {
                            console.error('deleteUserFromDatabase: JSON parse error:', error);
                            console.error('deleteUserFromDatabase: Full response text:', xhr.responseText);
                            reject(new Error('Invalid response format: ' + xhr.responseText.substring(0, 100)));
                        }
                    } else {
                        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                    }
                }
            };
            
            xhr.onerror = () => reject(new Error('Network error'));
            xhr.send(JSON.stringify({ user_email: userEmail }));
        });
    }

    function deleteUsersByLocationFromDatabase(location) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const url = API_BASE_URL + '/unified_api.php?endpoint=delete_users_by_location';
            console.log('deleteUsersByLocationFromDatabase: Calling URL:', url);
            
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('deleteUsersByLocationFromDatabase: Response status:', xhr.status);
                    console.log('deleteUsersByLocationFromDatabase: Response headers:', xhr.getAllResponseHeaders());
                    console.log('deleteUsersByLocationFromDatabase: Response text (first 200 chars):', xhr.responseText.substring(0, 200));
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resolve(response);
                            } else {
                                reject(new Error(response.error || 'Failed to delete users by location'));
                            }
                        } catch (error) {
                            console.error('deleteUsersByLocationFromDatabase: JSON parse error:', error);
                            console.error('deleteUsersByLocationFromDatabase: Full response text:', xhr.responseText);
                            reject(new Error('Invalid response format: ' + xhr.responseText.substring(0, 100)));
                        }
                    } else {
                        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                    }
                }
            };
            
            xhr.onerror = () => reject(new Error('Network error'));
            xhr.send(JSON.stringify({ location: location }));
        });
    }

    function deleteAllUsersFromDatabase() {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const url = API_BASE_URL + '/unified_api.php?endpoint=delete_all_users';
            console.log('deleteAllUsersFromDatabase: Calling URL:', url);
            
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    console.log('deleteAllUsersFromDatabase: Response status:', xhr.status);
                    console.log('deleteAllUsersFromDatabase: Response headers:', xhr.getAllResponseHeaders());
                    console.log('deleteAllUsersFromDatabase: Response text (first 200 chars):', xhr.responseText.substring(0, 200));
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                resolve(response);
                            } else {
                                reject(new Error(response.error || 'Failed to delete all users'));
                            }
                        } catch (error) {
                            console.error('deleteAllUsersFromDatabase: JSON parse error:', error);
                            console.error('deleteAllUsersFromDatabase: Full response text:', xhr.responseText);
                            reject(new Error('Invalid response format: ' + xhr.responseText.substring(0, 100)));
                        }
                    } else {
                        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                    }
                }
            };
            
            xhr.onerror = () => reject(new Error('Network error'));
            xhr.send(JSON.stringify({}));
        });
    }

    // Functions for the action buttons
    function deleteUsersByLocation() {
        const locationFilter = document.getElementById('locationFilter').value;
        if (!locationFilter) {
            showAlert('warning', 'Please select a location first');
            return;
        }
        
        if (!confirm(`Are you sure you want to delete ALL users from ${locationFilter}? This action cannot be undone!`)) {
            return;
        }
        
        showAlert('info', `Deleting users from ${locationFilter}...`);
        
        deleteUsersByLocationFromDatabase(locationFilter)
            .then(result => {
                showAlert('success', `Successfully deleted ${result.deleted_count || 0} users from ${locationFilter}`);
                
                // Immediately remove users from the specified location for better UX
                const tbody = document.querySelector('#usersTableBody');
                if (tbody) {
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach(row => {
                        const locationCell = row.querySelector('td:nth-child(5)'); // Location column
                        if (locationCell && locationCell.textContent.trim() === locationFilter) {
                            row.remove();
                        }
                    });
                    
                    // If no rows left, show no users message
                    if (tbody.children.length === 0) {
                        tbody.innerHTML = '<tr data-no-users><td colspan="6" style="text-align: center; padding: 20px; color: var(--color-text); font-style: italic;">No users found in database</td></tr>';
                    }
                }
                
                // Also refresh from server to ensure consistency
                loadUsers();
            })
            .catch(error => {
                console.error('Error deleting users by location:', error);
                showAlert('danger', 'Error deleting users by location: ' + error.message);
            });
    }

    function deleteAllUsers() {
        if (!confirm('Are you sure you want to delete ALL users? This action cannot be undone!')) {
            return;
        }
        
        showAlert('info', 'Deleting all users...');
        
        deleteAllUsersFromDatabase()
            .then(result => {
                showAlert('success', `Successfully deleted ${result.deleted_count || 0} users`);
                
                // Immediately clear the table for better UX
                const tbody = document.querySelector('#usersTableBody');
                if (tbody) {
                    tbody.innerHTML = '<tr data-no-users><td colspan="6" style="text-align: center; padding: 20px; color: var(--color-text); font-style: italic;">No users found in database</td></tr>';
                }
                
                // Also refresh from server to ensure consistency
                loadUsers();
            })
            .catch(error => {
                console.error('Error deleting all users:', error);
                showAlert('danger', 'Error deleting all users: ' + error.message);
            });
    }

    // Function to render users in the table
    function renderUsers(preferences) {
        const tableBody = document.querySelector('#usersTableBody');
        tableBody.innerHTML = '';
        
        if (!users || users.length === 0) {
            document.getElementById('no-users-message').style.display = 'block';
            document.getElementById('no-users-message').textContent = 'No app users with screening data found.';
            return;
        }
        
        document.getElementById('no-users-message').style.display = 'none';
        
        users.forEach(user => {
            const row = document.createElement('tr');
            // Get user preferences
            const userPrefs = preferences.find(p => p.user_email === user.email);
            const hasPrefs = userPrefs && (userPrefs.allergies || userPrefs.diet_prefs);
            row.innerHTML = `
                <td>${user.user_id}</td>
                <td>${user.username}</td>
                <td><span class="risk-status ${user.risk_class}">${user.risk_status} (${user.risk_score}%)</span></td>
                <td>
                    ${hasPrefs ? 
                        '<span class="pref-badge">✓ Set</span>' : 
                        '<span class="pref-badge empty">Not set</span>'
                    }
                </td>
                <td>
                    <button class="btn-edit" data-email="${user.email}" onclick="viewUserDetails('${user.email}')">View Details</button>
                    <button class="btn-edit btn-warning" data-email="${user.email}" onclick="editUser('${user.email}')">Edit</button>
                    <button class="btn-delete" data-email="${user.email}" onclick="deleteUser('${user.email}')">Delete</button>
                </td>
            `;
            tableBody.appendChild(row);
            // Attach event listeners to the new row
            attachRowEventListeners(row);
        });
    }

    // Function to add a new user (simplified - uses the comprehensive addUser function below)
    function addUser() {
        // This function is replaced by the comprehensive async addUser function below
        // Show message to use the proper form
        showAlert('info', 'Please use the Add User form above for comprehensive user creation');
    }

    // Function to edit an existing user (simplified - uses the comprehensive editUser function below)
    function editUser(userId) {
        // This function is replaced by the comprehensive editUser function below
        // Show message to use the proper form
        showAlert('info', 'Please use the Edit User form for comprehensive user editing');
    }

    // Function to save edited user
    function saveUser() {
        const form = document.getElementById('editUserFormSimple');
        const formData = new FormData(form);
        formData.append('action', 'update');
        
        // Get the risk level value and convert it to a score
        const riskLevel = document.getElementById('edit_riskLevel').value;
        let score = 0;
        if (riskLevel === 'Good') score = 1;
        else if (riskLevel === 'At Risk') score = 4;
        else if (riskLevel === 'Malnourished') score = 7;
        
        // Add score to the form data
        formData.append('score', score);
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', MANAGE_USER_URL, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Show success message
                        showAlert('success', response.message);
                        
                        // Close modal
                        closeEditUserModal();
                        
                        // Reload users
                        loadUsers();
                    } else {
                        // Show error message
                        showAlert('danger', response.message || 'Error saving user');
                    }
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    showAlert('danger', 'Error parsing server response');
                }
            } else {
                console.error('Request failed. Status:', xhr.status);
                showAlert('danger', 'Error saving user. Status: ' + xhr.status);
            }
        };
        
        xhr.onerror = function() {
            console.error('Request failed');
            showAlert('danger', 'Network error while saving user');
        };
        
        xhr.send(formData);
    }

    // Function to delete a user
    async function deleteUser(email) {
        console.log('deleteUser called with email:', email);
        
        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }
        
        // Show loading state
        showAlert('info', 'Deleting user...');
        
        try {
            const result = await deleteUserFromDatabase(email);
            if (result.success) {
                showAlert('success', 'User deleted successfully');
                
                // Immediately remove the user row from the table for better UX
                const tbody = document.querySelector('#usersTableBody');
                if (tbody) {
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach(row => {
                        const deleteBtn = row.querySelector('.btn-delete');
                        if (deleteBtn && deleteBtn.getAttribute('data-email') === email) {
                            row.remove();
                        }
                    });
                    
                    // If no rows left, show no users message
                    if (tbody.children.length === 0) {
                        tbody.innerHTML = '<tr data-no-users><td colspan="6" style="text-align: center; padding: 20px; color: var(--color-text); font-style: italic;">No users found in database</td></tr>';
                    }
                }
                
                // Also refresh from server to ensure consistency
                loadUsers();
            } else {
                showAlert('danger', 'Error deleting user: ' + result.error);
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            showAlert('danger', 'Error deleting user: ' + error.message);
        }
    }

            // Function to edit a user
        async function editUser(email) {
            console.log('editUser called with email:', email);
            console.log('Current users available:', window.currentUsers);
        
        try {
            // Show loading state
            showAlert('info', 'Loading user data...');
            
            // Fetch user data for editing using fetch API
            const response = await fetch(API_BASE_URL + '/unified_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_user_data',
                    email: email
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('API response:', data);
            
            if (data.success && data.data) {
                console.log('User data received:', data.data);
                // Populate the edit form with user data
                populateEditForm(data.data);
                // Show the edit modal
                document.getElementById('editUserModal').style.display = 'block';
                // Hide loading alert
                showAlert('success', 'User data loaded successfully');
            } else {
                console.error('API returned error:', data.error || data.message);
                showAlert('danger', data.error || data.message || 'Failed to load user data');
            }
        } catch (error) {
            console.error('Error loading user data:', error);
            showAlert('danger', 'Error loading user data: ' + error.message);
        }
    }

    // Function to populate the edit form with user data
    function populateEditForm(userData) {
        console.log('Populating edit form with user data:', userData);
        
        // For imported users, data is directly in the main fields, not in screening_answers
        console.log('Setting form fields with data:', {
            name: userData.name || '',
            user_email: userData.user_email || '',
            weight: userData.weight || '',
            height: userData.height || '',
            birthday: userData.birthday || '',
            gender: userData.gender || '',
            barangay: userData.barangay || '',
            income: userData.income || '',
            muac: userData.muac || '',
            goal: userData.goal || '',
            allergies: userData.allergies || '',
            diet_prefs: userData.diet_prefs || '',
            avoid_foods: userData.avoid_foods || '',
            risk_score: userData.risk_score || ''
        });
        
        // Set form field values directly from userData
        console.log('Setting form fields...');
        
        const usernameField = document.getElementById('editUsername');
        const emailField = document.getElementById('editEmail');
        const weightField = document.getElementById('editWeight');
        const heightField = document.getElementById('editHeight');
        const birthdayField = document.getElementById('editBirthday');
        const genderField = document.getElementById('editGender');
        const barangayField = document.getElementById('editBarangay');
        const incomeField = document.getElementById('editIncome');
        const muacField = document.getElementById('editMuac');
        const goalField = document.getElementById('editGoal');
        const allergiesField = document.getElementById('editAllergies');
        const dietPrefsField = document.getElementById('editDietPrefs');
        const avoidFoodsField = document.getElementById('editAvoidFoods');
        const riskScoreField = document.getElementById('editRiskScore');
        
        if (usernameField) usernameField.value = userData.name || '';
        if (emailField) emailField.value = userData.user_email || '';
        if (weightField) weightField.value = userData.weight || '';
        if (heightField) heightField.value = userData.height || '';
        if (birthdayField) birthdayField.value = userData.birthday || '';
        if (genderField) genderField.value = userData.gender || '';
        
        // Debug barangay and income population
        console.log('Setting barangay field:', {
            field: barangayField,
            value: userData.barangay,
            userData: userData
        });
        if (barangayField) {
            // Try to find exact match first, then closest match
            const barangayValue = userData.barangay || '';
            if (barangayValue) {
                const exactMatch = Array.from(barangayField.options).find(option => 
                    option.value === barangayValue || option.text === barangayValue
                );
                if (exactMatch) {
                    barangayField.value = exactMatch.value;
                    console.log('Barangay exact match found:', exactMatch.value);
                } else {
                    // Try to find closest match
                    const closestMatch = Array.from(barangayField.options).find(option => 
                        option.value.toLowerCase().includes(barangayValue.toLowerCase()) ||
                        barangayValue.toLowerCase().includes(option.value.toLowerCase())
                    );
                    if (closestMatch) {
                        barangayField.value = closestMatch.value;
                        console.log('Barangay closest match found:', closestMatch.value);
                    } else {
                        barangayField.value = '';
                        console.log('No barangay match found for:', barangayValue);
                    }
                }
            } else {
                barangayField.value = '';
            }
            console.log('Barangay field value set to:', barangayField.value);
        }
        
        console.log('Setting income field:', {
            field: incomeField,
            value: userData.income,
            userData: userData
        });
        if (incomeField) {
            // Try to find exact match first, then closest match for income
            const incomeValue = userData.income || '';
            if (incomeValue) {
                const exactMatch = Array.from(incomeField.options).find(option => 
                    option.value === incomeValue || option.text === incomeValue
                );
                if (exactMatch) {
                    incomeField.value = exactMatch.value;
                    console.log('Income exact match found:', exactMatch.value);
                } else {
                    // Try to find closest match with better logic for income
                    let closestMatch = null;
                    let bestScore = 0;
                    
                    Array.from(incomeField.options).forEach(option => {
                        if (option.value === '') return; // Skip placeholder
                        
                        const optionText = option.value.toLowerCase();
                        const searchValue = incomeValue.toLowerCase();
                        
                        // Check for key terms in income brackets
                        if (searchValue.includes('below') || searchValue.includes('poverty')) {
                            if (optionText.includes('below') || optionText.includes('poverty')) {
                                closestMatch = option;
                                bestScore = 100;
                            }
                        } else if (searchValue.includes('low') || searchValue.includes('12,031')) {
                            if (optionText.includes('low') || optionText.includes('12,031')) {
                                closestMatch = option;
                                bestScore = 100;
                            }
                        } else if (searchValue.includes('middle') || searchValue.includes('20,001')) {
                            if (optionText.includes('middle') || optionText.includes('20,001')) {
                                closestMatch = option;
                                bestScore = 100;
                            }
                        } else if (searchValue.includes('high') || searchValue.includes('above') || searchValue.includes('40,000')) {
                            if (optionText.includes('high') || optionText.includes('above') || optionText.includes('40,000')) {
                                closestMatch = option;
                                bestScore = 100;
                            }
                        }
                        
                        // Additional matching for simplified values
                        if (searchValue === 'low' && optionText.includes('low')) {
                            closestMatch = option;
                            bestScore = 100;
                        } else if (searchValue === 'middle' && optionText.includes('middle')) {
                            closestMatch = option;
                            bestScore = 100;
                        } else if (searchValue === 'high' && optionText.includes('high')) {
                            closestMatch = option;
                            bestScore = 100;
                        }
                        
                        // Fallback: check for partial string matches
                        if (bestScore < 50) {
                            const score = calculateStringSimilarity(searchValue, optionText);
                            if (score > bestScore) {
                                bestScore = score;
                                closestMatch = option;
                            }
                        }
                    });
                    
                    if (closestMatch && bestScore > 30) {
                        incomeField.value = closestMatch.value;
                        console.log('Income closest match found:', closestMatch.value, 'Score:', bestScore);
                    } else {
                        incomeField.value = '';
                        console.log('No income match found for:', incomeValue);
                    }
                }
            } else {
                incomeField.value = '';
            }
            console.log('Income field value set to:', incomeField.value);
        }
        
        if (muacField) muacField.value = userData.muac || '';
        if (goalField) goalField.value = userData.goal || '';
        if (allergiesField) allergiesField.value = userData.allergies || '';
        if (dietPrefsField) dietPrefsField.value = userData.diet_prefs || '';
        if (avoidFoodsField) avoidFoodsField.value = userData.avoid_foods || '';
        if (riskScoreField) riskScoreField.value = userData.risk_score || '';
        
        console.log('Form fields set successfully');
        
        // Small delay to ensure form is fully rendered before calculations
        setTimeout(() => {
            // Calculate and display age and BMI
            calculateAndDisplayAgeAndBMI();
            
            // Update risk display with current data
            updateRiskScoreInRealTime();
        
        // Add event listeners to form fields to update risk score in real-time
        addRiskScoreUpdateListeners();
        }, 100);
    }

    // Function to calculate and display age and BMI
    function calculateAndDisplayAgeAndBMI() {
        const birthday = document.getElementById('editBirthday').value;
        const weight = parseFloat(document.getElementById('editWeight').value) || 0;
        const height = parseFloat(document.getElementById('editHeight').value) || 0;
        
        // Calculate age
        if (birthday) {
            const birthDate = new Date(birthday);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            document.getElementById('editAge').value = age;
        }
        
        // Calculate BMI
        if (weight > 0 && height > 0) {
            const heightMeters = height / 100;
            const bmi = weight / (heightMeters * heightMeters);
            document.getElementById('editBmi').value = bmi.toFixed(1);
        }
    }

    // Function to add real-time risk score update listeners to edit form
    function addRiskScoreUpdateListeners() {
        const formFields = [
            'editWeight', 'editHeight', 'editBirthday', 'editGender', 'editMuac', 'editGoal',
            'editAllergies', 'editDietPrefs', 'editAvoidFoods'
        ];
        
        formFields.forEach(fieldId => {
            const element = document.getElementById(fieldId);
            if (element) {
                if (element.type === 'select-one') {
                    element.addEventListener('change', updateRiskScoreInRealTime);
                } else {
                    element.addEventListener('input', updateRiskScoreInRealTime);
                }
            }
        });
        
        // Add special listeners for weight and height to update BMI
        const weightField = document.getElementById('editWeight');
        const heightField = document.getElementById('editHeight');
        const birthdayField = document.getElementById('editBirthday');
        
        if (weightField) weightField.addEventListener('input', calculateAndDisplayAgeAndBMI);
        if (heightField) heightField.addEventListener('input', calculateAndDisplayAgeAndBMI);
        if (birthdayField) birthdayField.addEventListener('change', calculateAndDisplayAgeAndBMI);
        
        console.log('Added real-time risk score update listeners');
    }
    
    // Function to update risk score in real-time as user types/changes values
    function updateRiskScoreInRealTime() {
        try {
            // Get current form values
            const weight = parseFloat(document.getElementById('editWeight').value) || 0;
            const height = parseFloat(document.getElementById('editHeight').value) || 0;
            const birthday = document.getElementById('editBirthday').value || '';
            let age = parseInt(document.getElementById('editAge').value) || 0;
            let bmi = parseFloat(document.getElementById('editBmi').value) || 0;
            const muac = parseFloat(document.getElementById('editMuac').value) || 0;
            
            // Calculate age from birthday if not already set
            if (birthday && !age) {
                const birthDate = new Date(birthday);
                const today = new Date();
                age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                document.getElementById('editAge').value = age;
            }
            
            // Calculate BMI if not already set
            if (weight > 0 && height > 0 && !bmi) {
                const heightMeters = height / 100;
                bmi = weight / (heightMeters * heightMeters);
                document.getElementById('editBmi').value = bmi.toFixed(1);
            }
            
            // Get clinical risk factors
            // const hasRecentIllness = document.getElementById('editHasRecentIllness').checked;
            // const hasEatingDifficulty = document.getElementById('editHasEatingDifficulty').checked;
            // const hasFoodInsecurity = document.getElementById('editHasFoodInsecurity').checked;
            // const hasMicronutrientDeficiency = document.getElementById('editHasMicronutrientDeficiency').checked;
            // const hasFunctionalDecline = document.getElementById('editHasFunctionalDecline').checked;
            
            // Calculate simple risk score based on BMI and age
            let projectedRiskScore = 0;
            if (weight && height && birthday) {
                // Simple risk calculation based on BMI and age
                if (bmi < 18.5) {
                    projectedRiskScore += 30; // Underweight
                } else if (bmi >= 25) {
                    projectedRiskScore += 20; // Overweight
                }
                
                if (age < 5) {
                    projectedRiskScore += 25; // Young children
                } else if (age > 65) {
                    projectedRiskScore += 20; // Elderly
                }
                
                if (muac < 12.5) {
                    projectedRiskScore += 25; // Low MUAC
                }
                
                console.log('Projected risk score calculated:', projectedRiskScore);
            } else {
                console.log('Cannot calculate projected risk score - missing data:', { weight, height, birthday });
            }
            
            // Risk score calculated successfully
            console.log('Projected risk score calculated:', projectedRiskScore);
            
        } catch (error) {
            console.error('Error updating risk score in real-time:', error);
        }
    }

    // Function to calculate and display current risk score in edit form
    function calculateAndDisplayCurrentRiskScore(screeningData, userData) {
        try {
            // Extract values for risk calculation
            const weight = parseFloat(screeningData.weight || userData.weight) || 0;
            const height = parseFloat(screeningData.height || userData.height) || 0;
            const dietaryDiversity = parseInt(screeningData.dietary_diversity || userData.dietary_diversity) || 0;
            const birthday = screeningData.birthday || userData.birthday || '';
            const swelling = screeningData.swelling || 'no';
            const weightLoss = screeningData.weight_loss || '<5% or none';
            const feedingBehavior = screeningData.feeding_behavior || 'good appetite';
            
            // Parse physical signs
            let physicalSigns = [];
            if (screeningData.physical_signs) {
                try {
                    if (typeof screeningData.physical_signs === 'string' && screeningData.physical_signs.startsWith('[')) {
                        physicalSigns = JSON.parse(screeningData.physical_signs);
                    } else if (Array.isArray(screeningData.physical_signs)) {
                        physicalSigns = screeningData.physical_signs;
                    }
                } catch (e) {
                    console.log('Could not parse physical signs for risk calculation');
                }
            }
            
            const physicalThin = physicalSigns.includes('thin');
            const physicalShorter = physicalSigns.includes('shorter');
            const physicalWeak = physicalSigns.includes('weak');
            
            // Get clinical risk factors
            const hasRecentIllness = screeningData.has_recent_illness || false;
            const hasEatingDifficulty = screeningData.has_eating_difficulty || false;
            const hasFoodInsecurity = screeningData.has_food_insecurity || false;
            const hasMicronutrientDeficiency = screeningData.has_micronutrient_deficiency || false;
            const hasFunctionalDecline = screeningData.has_functional_decline || false;
            
            // Calculate risk score
            let riskScore = 0;
            if (weight && height && dietaryDiversity && birthday) {
                riskScore = calculateRiskScore(
                    weight, height, dietaryDiversity, birthday, swelling, weightLoss, 
                    feedingBehavior, physicalThin, physicalShorter, physicalWeak,
                    hasRecentIllness, hasEatingDifficulty, hasFoodInsecurity,
                    hasMicronutrientDeficiency, hasFunctionalDecline
                );
                console.log('Risk score calculated:', riskScore, 'from data:', { weight, height, dietaryDiversity, birthday, swelling, weightLoss, feedingBehavior, physicalThin, physicalShorter, physicalWeak, hasRecentIllness, hasEatingDifficulty, hasFoodInsecurity, hasMicronutrientDeficiency, hasFunctionalDecline });
            } else {
                // Use existing risk score if calculation not possible
                riskScore = userData.risk_score || 0;
                console.log('Using existing risk score:', riskScore, 'because missing data:', { weight, height, dietaryDiversity, birthday });
            }
            
            // Determine risk level
            let riskLevel = 'Low';
            let riskClass = 'low-risk';
            if (riskScore >= 75) {
                riskLevel = 'Severe';
                riskClass = 'severe-risk';
            } else if (riskScore >= 50) {
                riskLevel = 'High';
                riskClass = 'high-risk';
            } else if (riskScore >= 25) {
                riskLevel = 'Moderate';
                riskClass = 'moderate-risk';
            }
            
            // Current risk calculated successfully
            console.log('Current risk calculated:', { riskScore, riskLevel, riskClass });
            
            // Also update any risk level dropdown if it exists
            const riskLevelDropdown = document.getElementById('editRiskLevel');
            if (riskLevelDropdown) {
                if (riskScore >= 75) {
                    riskLevelDropdown.value = 'severe';
                } else if (riskScore >= 50) {
                    riskLevelDropdown.value = 'high';
                } else if (riskScore >= 25) {
                    riskLevelDropdown.value = 'moderate';
                } else {
                    riskLevelDropdown.value = 'low';
                }
            }
            
            console.log('Current risk calculated:', { riskScore, riskLevel, riskClass });
            
        } catch (error) {
            console.error('Error calculating current risk score:', error);
            // Fallback to existing risk score
            console.log('Using existing risk score:', userData.risk_score || 0);
        }
    }

    // Function to save edited user data
    function saveEditedUser() {
        // Get form data
        const form = document.getElementById('editUserFormModal');
        const formData = new FormData(form);
        
        // Validate required fields
        const requiredFields = ['name', 'weight', 'height', 'birthday', 'gender', 'barangay', 'income'];
        for (const field of requiredFields) {
            if (!formData.get(field)) {
                showAlert('danger', `Please fill in ${field.replace('_', ' ')}`);
                return;
            }
        }
        
        // Calculate age and BMI
        const weight = parseFloat(formData.get('weight'));
        const height = parseFloat(formData.get('height'));
        const birthday = formData.get('birthday');
        const bmi = weight / Math.pow(height / 100, 2);
        
        // Calculate age
        const birthDate = new Date(birthday);
        const today = new Date();
        const age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        // Calculate simple risk score
        let riskScore = 0;
        if (bmi < 18.5) {
            riskScore += 30; // Underweight
        } else if (bmi >= 25) {
            riskScore += 20; // Overweight
        }
        
        if (age < 5) {
            riskScore += 25; // Young children
        } else if (age > 65) {
            riskScore += 20; // Elderly
        }
        
        const muac = parseFloat(formData.get('muac')) || 0;
        if (muac < 12.5) {
            riskScore += 25; // Low MUAC
        }
        
        // Create user data object
        const userData = {
            name: formData.get('name'),
            user_email: formData.get('user_email'),
            birthday: birthday,
            age: age,
            gender: formData.get('gender'),
            height: height,
            weight: weight,
            bmi: bmi,
            muac: muac,
            goal: formData.get('goal') || '',
            risk_score: riskScore,
            allergies: formData.get('allergies') || '',
            diet_prefs: formData.get('diet_prefs') || '',
            avoid_foods: formData.get('avoid_foods') || '',
            barangay: formData.get('barangay'),
            income: formData.get('income')
        };
        
        // Show loading state
        showAlert('info', 'Saving changes...');
        
        // Send update request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'API_BASE_URL + "/unified_api.php"', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        
        const updateData = {
            action: 'update_user',
            user_data: userData
        };
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showAlert('success', 'User updated successfully! Risk Score: ' + riskScore);
                            // Close modal and reload users
                            closeEditUserModal();
                            disableAnimationsTemporarily();
                            loadUsers();
                        } else {
                            showAlert('danger', response.error || 'Failed to update user');
                        }
                    } catch (error) {
                        console.error('Error parsing response:', error);
                        showAlert('danger', 'Error updating user');
                    }
                } else {
                    console.error('Update request failed:', xhr.status);
                    showAlert('danger', 'Failed to update user. Status: ' + xhr.status);
                }
            }
        };
        
        xhr.onerror = function() {
            console.error('Network error during update');
            showAlert('danger', 'Network error while updating user');
        };
        
        xhr.send(JSON.stringify(updateData));
    }

    // Function to close edit user modal
    function closeEditUserModal() {
        document.getElementById('editUserModal').style.display = 'none';
        // Reset form
        document.getElementById('editUserFormModal').reset();
        // Clear all radio buttons and checkboxes
        const radioButtons = document.querySelectorAll('#editUserFormModal input[type="radio"]');
        const checkboxes = document.querySelectorAll('#editUserFormModal input[type="checkbox"]');
        radioButtons.forEach(btn => btn.checked = false);
        checkboxes.forEach(cb => cb.checked = false);
    }

    // Function to calculate risk score (same logic as Android app)
    function calculateRiskScore(screeningData) {
        let score = 0;
        
        // Check for edema first - this overrides everything else
        if (screeningData.swelling === 'yes') {
            return 100; // Immediate severe risk - urgent referral alert
        }
        
        // Calculate age-based anthropometry scoring
        const birthday = new Date(screeningData.birthday);
        const today = new Date();
        const ageInMonths = (today.getFullYear() - birthday.getFullYear()) * 12 + (today.getMonth() - birthday.getMonth());
        
        if (ageInMonths >= 6 && ageInMonths <= 59) {
            // Children 6-59 months: Use weight-for-height
            const wfh = screeningData.weight / (screeningData.height / 100.0);
            if (wfh < 0.8) score += 40;      // Severe acute malnutrition
            else if (wfh < 0.9) score += 25; // Moderate acute malnutrition
            else score += 0;                  // Normal
        } else if (ageInMonths >= 240) {
            // Adults 20+ years: Use BMI
            if (screeningData.bmi < 16.5) score += 40;      // Severe underweight
            else if (screeningData.bmi < 18.5) score += 25; // Moderate underweight
            else score += 0;                   // Normal weight
        } else {
            // Children/adolescents 5-19 years: Use BMI-for-age
            if (screeningData.bmi < 15) score += 40;        // Severe thinness
            else if (screeningData.bmi < 17) score += 30;   // Moderate thinness
            else if (screeningData.bmi < 18.5) score += 20; // Mild thinness
            else score += 0;                   // Normal
        }
        
        // Weight loss scoring
        if (screeningData.weight_loss === '>10%') score += 20;
        else if (screeningData.weight_loss === '5-10%') score += 10;
        else if (screeningData.weight_loss === '<5% or none') score += 0;
        
        // Feeding behavior scoring
        if (screeningData.feeding_behavior === 'poor appetite') score += 8;
        else if (screeningData.feeding_behavior === 'moderate appetite') score += 8;
        else if (screeningData.feeding_behavior === 'good appetite') score += 0;
        
        // Physical signs scoring
        if (screeningData.physical_signs && screeningData.physical_signs.length > 0) {
            if (screeningData.physical_signs.includes('thin')) score += 5;
            if (screeningData.physical_signs.includes('shorter')) score += 5;
            if (screeningData.physical_signs.includes('weak')) score += 5;
        }
        
        // Dietary diversity scoring
        const diversity = parseInt(screeningData.dietary_diversity);
        if (diversity <= 2) score += 10;
        else if (diversity <= 4) score += 5;
        
        // Clinical & Social Risk Factors scoring
        if (screeningData.has_recent_illness) score += 8;
        if (screeningData.has_eating_difficulty) score += 8;
        if (screeningData.has_food_insecurity) score += 10;
        if (screeningData.has_micronutrient_deficiency) score += 6;
        if (screeningData.has_functional_decline) score += 8;
        
        // Ensure score doesn't exceed 100
        return Math.min(score, 100);
    }

    // Function to show alerts
    function showAlert(type, message) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        // Get container and insert alert
        const container = document.querySelector('.user-management-container');
        const existingAlert = container.querySelector('.alert');
        
        if (existingAlert) {
            container.removeChild(existingAlert);
        }
        
        container.insertBefore(alertDiv, container.querySelector('.table-header').nextSibling);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }

            // Function to view user details with real-time data
        async function viewUserDetails(email) {
            console.log('viewUserDetails called with email:', email);
            console.log('Current users available:', window.currentUsers);
        
        try {
            // Show modal immediately with current data if available
            const modalContent = document.getElementById('userDetailsContent');
            const currentUsers = window.currentUsers || {};
            // Convert to array if it's an object
            const currentUsersArray = Array.isArray(currentUsers) ? currentUsers : Object.values(currentUsers);
            const currentUser = currentUsersArray.find(u => u.email === email);
            
            console.log('Current user found:', currentUser);
            
            if (currentUser) {
                // Show current data immediately
                updateUserDetailsDisplay(currentUser);
            }
            
            document.getElementById('userDetailsModal').style.display = 'block';
            
            // Fetch fresh user data from API using POST with get_user_data action
            const response = await fetch('API_BASE_URL + "/unified_api.php"', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_user_data',
                    email: email
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                console.error('Error loading user details:', data.error || data.message);
                showAlert('danger', 'Failed to load user details: ' + (data.error || data.message));
                return;
            }
            
            const user = data.data;
            
            if (!user) {
                showAlert('danger', 'User not found.');
                return;
            }

            // Get user data from direct database fields
            let userInfo = [];
            
            // Basic information
            if (user.name) userInfo.push(`Name: ${user.name}`);
            if (user.birthday) userInfo.push(`Birthday: ${user.birthday}`);
            if (user.age) userInfo.push(`Age: ${user.age} years`);
            if (user.gender) userInfo.push(`Gender: ${user.gender}`);
            if (user.weight) userInfo.push(`Weight: ${user.weight} kg`);
            if (user.height) userInfo.push(`Height: ${user.height} cm`);
            if (user.bmi) userInfo.push(`BMI: ${user.bmi}`);
            if (user.muac) userInfo.push(`MUAC: ${user.muac} cm`);
            if (user.goal) userInfo.push(`Nutrition Goal: ${user.goal}`);
            if (user.barangay) userInfo.push(`Barangay: ${user.barangay}`);
            if (user.income) userInfo.push(`Income: ${user.income}`);
            
            let userInfoDisplay = userInfo.length > 0 ? userInfo.join('<br>') : 'No user information available';

            // Parse preferences
            let allergies = 'None';
            let dietPrefs = 'None';
            
            if (user.allergies && user.allergies !== 'null' && user.allergies !== '[]') {
                try {
                    const allergiesArray = JSON.parse(user.allergies);
                    allergies = allergiesArray.length > 0 ? allergiesArray.join(', ') : 'None';
                } catch (e) {
                    allergies = 'None';
                }
            }
            
            if (user.diet_prefs && user.diet_prefs !== 'null' && user.diet_prefs !== '[]') {
                try {
                    const dietArray = JSON.parse(user.diet_prefs);
                    dietPrefs = dietArray.length > 0 ? dietArray.join(', ') : 'None';
                } catch (e) {
                    dietPrefs = 'None';
                }
            }

            // Use risk score from database
            let riskScore = user.risk_score || 0;
            let riskLevel = 'Low Risk';
            let riskClass = 'good';
            
            // Determine risk level based on database score
            if (riskScore >= 75) {
                riskLevel = 'Severe Risk';
                riskClass = 'malnourished';
            } else if (riskScore >= 50) {
                riskLevel = 'High Risk';
                riskClass = 'risk';
            } else if (riskScore >= 25) {
                riskLevel = 'Moderate Risk';
                riskClass = 'at';
            }

            // Update modal with fresh data
            updateUserDetailsDisplay(user);
            
            console.log('User details loaded with real-time data:', user);
            
            // User details loaded successfully
            
        } catch (error) {
            console.error('Error loading user details:', error);
        }
    }

    // Function to update user details display
    function updateUserDetailsDisplay(user) {
        console.log('Updating user details display for:', user);
        
        // Get user data from direct database fields
        let userInfo = [];
        
        // Basic information
        if (user.name) userInfo.push(`Name: ${user.name}`);
        if (user.birthday) userInfo.push(`Birthday: ${user.birthday}`);
        if (user.age) userInfo.push(`Age: ${user.age} years`);
        if (user.gender) userInfo.push(`Gender: ${user.gender}`);
        if (user.weight) userInfo.push(`Weight: ${user.weight} kg`);
        if (user.height) userInfo.push(`Height: ${user.height} cm`);
        if (user.bmi) userInfo.push(`BMI: ${user.bmi}`);
        if (user.muac) userInfo.push(`MUAC: ${user.muac} cm`);
        if (user.goal) userInfo.push(`Nutrition Goal: ${user.goal}`);
        if (user.barangay) userInfo.push(`Barangay: ${user.barangay}`);
        if (user.income) userInfo.push(`Income: ${user.income}`);
        
        let userInfoDisplay = userInfo.length > 0 ? userInfo.join('<br>') : 'No user information available';

        // Parse preferences
        let allergies = 'None';
        let dietPrefs = 'None';
        
        if (user.allergies && user.allergies !== 'null' && user.allergies !== '[]') {
            try {
                const allergiesArray = JSON.parse(user.allergies);
                allergies = allergiesArray.length > 0 ? allergiesArray.join(', ') : 'None';
            } catch (e) {
                allergies = 'None';
            }
        }
        
        if (user.diet_prefs && user.diet_prefs !== 'null' && user.diet_prefs !== '[]') {
            try {
                const dietArray = JSON.parse(user.diet_prefs);
                dietPrefs = dietArray.length > 0 ? dietArray.join(', ') : 'None';
            } catch (e) {
                dietPrefs = 'None';
            }
        }

        // Determine risk level based on score
        let riskLevel = 'Low Risk';
        let riskClass = 'good';
        if (user.risk_score >= 75) {
            riskLevel = 'Severe Risk';
            riskClass = 'malnourished';
        } else if (user.risk_score >= 50) {
            riskLevel = 'High Risk';
            riskClass = 'risk';
        } else if (user.risk_score >= 25) {
            riskLevel = 'Moderate Risk';
            riskClass = 'at';
        }

        // Update modal content
        const modalContent = document.getElementById('userDetailsContent');
        modalContent.innerHTML = `
            <div class="user-details-section">
                <h3>User Information</h3>
                <p><strong>Username:</strong> ${user.username || 'N/A'}</p>
                <p><strong>Email:</strong> ${user.email}</p>
                <p><strong>Risk Score:</strong> ${user.risk_score || 0}%</p>
                <p><strong>Risk Level:</strong> <span class="risk-badge ${riskClass}">${riskLevel}</span></p>
                <div class="user-info-details">
                    <strong>User Information:</strong><br>
                    <div class="user-info-content">${userInfoDisplay}</div>
                </div>
            </div>
            <div class="user-details-section">
                <h3>Preferences</h3>
                <p><strong>Allergies:</strong> ${allergies}</p>
                <p><strong>Diet Preferences:</strong> ${dietPrefs}</p>
                <p><strong>Avoid Foods:</strong> ${user.avoid_foods || 'None'}</p>
            </div>
            <div class="user-details-section">
                <h3>Account Details</h3>
                <p><strong>Created:</strong> ${new Date(user.created_at).toLocaleDateString()}</p>
                <p><strong>Last Updated:</strong> ${new Date().toLocaleString()}</p>
            </div>
        `;
    }

    // Simple user details modal management
    function closeUserDetailsModal() {
        document.getElementById('userDetailsModal').style.display = 'none';
    }

    // Enhanced auto-refresh functionality for USM - every 3 seconds like dash.php
    let usmRefreshInterval = null;
    let autoRefreshInitialized = false;
    let lastRefreshTime = 0;
    let refreshCounter = 0;
    const REFRESH_COOLDOWN = 2000; // 2 second cooldown to prevent excessive refreshes

    function startUSMAutoRefresh() {
        // Prevent multiple instances
        if (autoRefreshInitialized || usmRefreshInterval) {
            console.log('Auto-refresh already running, skipping...');
            return;
        }
        
        console.log('Starting USM auto-refresh every 3 seconds...');
        autoRefreshInitialized = true;
        
        // Real-time table refresh every 3 seconds with anti-flickering measures
        usmRefreshInterval = setInterval(() => {
            const now = Date.now();
            
            // Prevent excessive refreshes
            if (now - lastRefreshTime < REFRESH_COOLDOWN) {
                console.log('Skipping refresh - cooldown period active');
                return;
            }
            
            refreshCounter++;
            console.log(`Auto-refresh #${refreshCounter} triggered at ${new Date().toLocaleTimeString()} - refreshing table...`);
            
            // Only refresh if page is visible and not currently loading
            if (!document.hidden && !window.loadUsersInProgress) {
                // Always refresh regardless of table content to get latest data
                const tbody = document.querySelector('#usersTableBody');
                if (tbody) {
                    // Use requestAnimationFrame for smooth, seamless updates (like dash.php)
                    requestAnimationFrame(() => {
                        try {
                            // Temporarily disable animations to prevent flickering
                            disableAnimationsTemporarily();
                            
                            // Refresh the table data
                            loadUsers();
                            
                            // Update last refresh time
                            lastRefreshTime = now;
                            
                            console.log(`Auto-refresh complete at ${new Date().toLocaleTimeString()}`);
                        } catch (error) {
                            console.error('Error in auto-refresh:', error);
                        }
                    });
                } else {
                    console.log('Table body not found, skipping auto-refresh');
                }
            }
        }, 3000); // 3 seconds like dash.php
        
        console.log('Auto-refresh setup complete');
        console.log('=== SETTINGS PAGE AUTO-REFRESH INITIALIZATION COMPLETE ===');
        console.log('Auto-refresh: Active every 3 seconds with anti-flickering measures');
        
        // Test heartbeat to verify interval is running
        setTimeout(() => {
            console.log('Auto-refresh heartbeat test - interval should be running...');
        }, 5000);
        
        // Add a simple test to verify the interval is working
        console.log('Auto-refresh interval set to 3000ms (3 seconds)');
        console.log('You should see refresh logs every 3 seconds in the console');
    }

    function stopUSMAutoRefresh() {
        if (usmRefreshInterval) {
            clearInterval(usmRefreshInterval);
            usmRefreshInterval = null;
            autoRefreshInitialized = false;
            console.log('USM auto-refresh stopped');
        }
    }

    // Function removed - use loadUsers() directly for consistency



    // Function to refresh the page to get latest database data (manual refresh)
        function refreshUsersData() {
            console.log('Manual refresh: Refreshing page to get latest data...');
            window.location.reload();
        }

    // Test API function to debug API responses
    function testAPI() {
        console.log('🧪 Testing API endpoint...');
        
        const testBtn = document.getElementById('testAPIBtn');
        if (testBtn) {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="btn-icon">⏳</span><span class="btn-text">Testing...</span>';
        }
        
        // Test the API directly
        fetch(API_BASE_URL + '/unified_api.php?endpoint=usm&t=' + Date.now())
            .then(response => response.json())
            .then(data => {
                console.log('🧪 API Test Response:', data);
                console.log('🧪 API Response Structure:', {
                    hasUsers: !!data.users,
                    usersType: typeof data.users,
                    isArray: Array.isArray(data.users),
                    usersLength: data.users ? (Array.isArray(data.users) ? data.users.length : Object.keys(data.users).length) : 0,
                    responseKeys: Object.keys(data),
                    success: data.success,
                    message: data.message
                });
                
                if (data.users && data.users.length > 0) {
                    console.log('🧪 Found users in API response:', data.users.length);
                    console.log('🧪 First user sample:', data.users[0]);
                } else {
                    console.log('🧪 No users found in API response');
                }
                
                // Re-enable button
                if (testBtn) {
                    testBtn.disabled = false;
                    testBtn.innerHTML = '<span class="btn-icon">🧪</span><span class="btn-text">Test API</span>';
                }
            })
            .catch(error => {
                console.error('🧪 API Test Error:', error);
                
                // Re-enable button
                if (testBtn) {
                    testBtn.disabled = false;
                    testBtn.innerHTML = '<span class="btn-icon">🧪</span><span class="btn-text">Test API</span>';
                }
            });
    }



        // Enhanced function to temporarily disable animations to prevent flickering
        function disableAnimationsTemporarily() {
            const style = document.createElement('style');
            style.id = 'temp-animation-disable';
            style.textContent = `
                * {
                    animation: none !important;
                    transition: none !important;
                    transform: none !important;
                }
                .user-table tbody tr {
                    opacity: 1 !important;
                }
                .user-table tbody tr:hover {
                    transform: none !important;
                    box-shadow: none !important;
                }
            `;
            document.head.appendChild(style);
            
            // Re-enable after 1.5 seconds to ensure smooth transition
            setTimeout(() => {
                const tempStyle = document.getElementById('temp-animation-disable');
                if (tempStyle) {
                    tempStyle.remove();
                }
            }, 1500);
        }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM Content Loaded - Initializing settings page...');
        
        // Check if required elements exist
        const requiredElements = ['addUserBtn', 'saveUserBtn', 'usersTableBody'];
        requiredElements.forEach(id => {
            const element = document.getElementById(id);
            if (!element) {
                console.warn(`Required element not found: ${id}`);
            } else {
                console.log(`Found required element: ${id}`);
            }
        });
        
        // Load users only once on initial page load
        disableAnimationsTemporarily();
        loadUsers();
        
        // Clear any existing "No users found" message after a short delay
        setTimeout(() => {
            const tbody = document.querySelector('#usersTableBody');
            if (tbody) {
                const noUsersRow = tbody.querySelector('tr td[colspan="5"]');
                if (noUsersRow && noUsersRow.textContent.includes('No users found')) {
                    noUsersRow.closest('tr').remove();
                }
            }
        }, 1000);
        
        // Start auto-refresh for subsequent updates (every 3 seconds - table only)
        setTimeout(() => {
            startUSMAutoRefresh();
        }, 2000); // Wait 2 seconds to ensure initial load is complete
        
        // Pause auto-refresh when page is not visible (user is on another page)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                console.log('Page hidden, pausing auto-refresh');
                stopUSMAutoRefresh();
            } else {
                console.log('Page visible, resuming auto-refresh');
                startUSMAutoRefresh();
            }
        });
        
        // Add error handling for auto-refresh
        window.addEventListener('error', function(e) {
            console.error('Global error caught:', e.error);
            if (e.error && e.error.message && e.error.message.includes('users')) {
                console.log('Stopping auto-refresh due to user data error');
                stopUSMAutoRefresh();
            }
        });
        
        // Cleanup when page is unloaded
        window.addEventListener('beforeunload', function() {
            console.log('Page unloading, cleaning up auto-refresh');
            stopUSMAutoRefresh();
        });
        
        // Add user button click
        const addUserBtn = document.getElementById('addUserBtn');
        if (addUserBtn) {
            addUserBtn.addEventListener('click', addUser);
            console.log('Add user button event listener attached');
        }
        
        // Save user button click
        const saveUserBtn = document.getElementById('saveUserBtn');
        if (saveUserBtn) {
            saveUserBtn.addEventListener('click', saveUser);
            console.log('Save user button event listener attached');
        }
        
        // Initialize enhanced interactions
        initializeEnhancedInteractions();
        
        // Simple and direct event handling for buttons
        document.addEventListener('click', function(e) {
            // Check if the clicked element is a button with data-email
            if (e.target.tagName === 'BUTTON' && e.target.hasAttribute('data-email')) {
                const email = e.target.getAttribute('data-email');
                const buttonText = e.target.textContent.trim();
                
                console.log('Button clicked:', buttonText, 'for email:', email);
                
                // Handle different button types
                if (buttonText === 'View' || buttonText === 'View Details') {
                    viewUserDetails(email);
                } else if (buttonText === 'Edit') {
                    editUser(email);
                } else if (buttonText === 'Delete') {
                    deleteUser(email);
                }
            }
        });
        
        console.log('Settings page initialization complete');
        
        // Test functions removed - they were creating test users on every page load
    });

    // Stop auto-refresh when page is hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopUSMAutoRefresh();
        } else {
            startUSMAutoRefresh();
        }
    });

    // Enhanced interactions and animations
    function initializeEnhancedInteractions() {
        // Initialize animations
        initializeAnimations();
        
        // Add enhanced hover effects to table rows
        const tableRows = document.querySelectorAll('.user-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
                this.style.boxShadow = '0 4px 16px rgba(161, 180, 84, 0.2)';
                this.classList.add('fade-in');
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
                this.classList.remove('fade-in');
            });
        });

        // Enhanced button interactions
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('click', function() {
                if (!this.classList.contains('btn-secondary')) {
                    this.classList.add('loading');
                    setTimeout(() => {
                        this.classList.remove('loading');
                    }, 1000);
                }
            });
            
            // Add ripple effect
            button.addEventListener('mousedown', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Enhanced search container interactions
        const searchContainer = document.querySelector('.search-container');
        if (searchContainer) {
            searchContainer.addEventListener('focusin', function() {
                this.style.transform = 'translateY(-1px)';
                this.style.boxShadow = 'var(--shadow-md)';
            });
            
            searchContainer.addEventListener('focusout', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        }

        // Enhanced location filter interactions
        const locationSelect = document.querySelector('.location-select');
        if (locationSelect) {
            locationSelect.addEventListener('focus', function() {
                this.style.transform = 'translateY(-1px)';
                this.style.boxShadow = 'var(--shadow-md)';
            });
            
            locationSelect.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        }
    }

    // Initialize animations with staggered delays
    function initializeAnimations() {
        const animatedElements = document.querySelectorAll('.fade-in, .slide-in-left');
        animatedElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.1}s`;
        });
    }

    // Search functionality
    function searchUsers() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
        const tableBody = document.getElementById('usersTableBody');
        const rows = tableBody.getElementsByTagName('tr');
        
        for (let row of rows) {
            const usernameCell = row.querySelector('td:nth-child(2)'); // Username column
            const emailCell = row.querySelector('td:nth-child(3)'); // Email column
            const locationCell = row.querySelector('td:nth-child(5)'); // Location column (updated index)
            if (usernameCell && emailCell && locationCell) {
                const username = usernameCell.textContent.toLowerCase();
                const email = emailCell.textContent.toLowerCase();
                const location = locationCell.textContent.toLowerCase();
                if (username.includes(searchTerm) || email.includes(searchTerm) || location.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }
        
        // Show/hide no results message
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        const noUsersMessage = document.getElementById('no-users-message');
        if (visibleRows.length === 0 && searchTerm !== '') {
            noUsersMessage.textContent = `No users found matching "${searchTerm}"`;
            noUsersMessage.style.display = 'block';
        } else {
            noUsersMessage.style.display = 'none';
        }
    }

    // Real-time search as user types
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                searchUsers();
            });
        }
    });
    
    // Location filtering function
    function filterUsersByLocation() {
        const locationFilter = document.getElementById('locationFilter').value;
        const tableBody = document.getElementById('usersTableBody');
        const rows = tableBody.getElementsByTagName('tr');
        
        console.log('Filtering by location:', locationFilter); // Debug log
        
        for (let row of rows) {
            const locationCell = row.querySelector('td:nth-child(5)'); // Location column (updated index)
            if (locationCell) {
                const location = locationCell.textContent.trim();
                console.log('Checking row location:', location); // Debug log
                
                let shouldShow = false;
                
                if (!locationFilter) {
                    // No filter selected, show all
                    shouldShow = true;
                } else if (location === locationFilter) {
                    // Exact match
                    shouldShow = true;
                } else if (locationFilter.includes('(Capital)') && location.includes('BALANGA')) {
                    // Handle BALANGA capital city
                    shouldShow = true;
                } else if (locationFilter.includes('(Pob.)')) {
                    // Handle Poblacion areas - check if barangay starts with municipality name
                    const municipalityName = locationFilter.split(' ')[0];
                    if (location.startsWith(municipalityName) || location.includes(municipalityName)) {
                        shouldShow = true;
                    }
                } else {
                    // Check if this is a municipality selection and user belongs to that municipality
                    const municipalityMap = {
                        'ABUCAY': ['ABUCAY', 'Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Omboy', 'Salian', 'Wawa (Pob.)'],
                        'BAGAC': ['BAGAC', 'Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibis', 'Pag-asa (Wawa-Sibacan)', 'Parang', 'Paysawan', 'Quinawan', 'San Antonio', 'Saysain', 'Tabing-Ilog (Pob.)', 'Atilano L. Ricardo'],
                        'CITY OF BALANGA (Capital)': ['CITY OF BALANGA (Capital)', 'Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
                        'DINALUPIHAN': ['DINALUPIHAN', 'Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar (Pob.)', 'Gen. Luna (Pob.)', 'Gomez (Pob.)', 'Happy Valley', 'Kataasan', 'Layac', 'Luacan', 'Mabini Proper (Pob.)', 'Mabini Ext. (Pob.)', 'Magsaysay', 'Naparing', 'New San Jose', 'Old San Jose', 'Padre Dandan (Pob.)', 'Pag-asa', 'Pagalanggang', 'Pinulot', 'Pita', 'Rizal (Pob.)', 'Roosevelt', 'Roxas (Pob.)', 'Saguing', 'San Benito', 'San Isidro (Pob.)', 'San Pablo (Bulate)', 'San Ramon', 'San Simon', 'Santo Niño', 'Sapang Balas', 'Santa Isabel (Tabacan)', 'Torres Bugauen (Pob.)', 'Tucop', 'Zamora (Pob.)', 'Aquino', 'Bayan-bayanan', 'Maligaya', 'Payangan', 'Pentor', 'Tubo-tubo', 'Jose C. Payumo, Jr.'],
                        'HERMOSA': ['HERMOSA', 'A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo', 'Judge Roman Cruz Sr. (Mandama)', 'Sacrifice Valley'],
                        'LIMAY': ['LIMAY', 'Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reformista', 'Townsite', 'Wawa', 'Duale', 'San Francisco de Asis', 'St. Francis II'],
                        'MARIVELES': ['MARIVELES', 'Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
                        'MORONG': ['MORONG', 'Binaritan', 'Bayan', 'Nagbalayong', 'Poblacion', 'Sabang'],
                        'ORANI': ['ORANI', 'Bagong Paraiso (Pob.)', 'Balut (Pob.)', 'Bayan (Pob.)', 'Calero (Pob.)', 'Paking-Carbonero (Pob.)', 'Centro II (Pob.)', 'Dona', 'Kaparangan', 'Masantol', 'Mulawin', 'Pag-asa', 'Palihan (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang Parang (Pob.)', 'Centro I (Pob.)', 'Sibul', 'Silahis', 'Tala', 'Talimundoc', 'Tapulao', 'Tenejero (Pob.)', 'Tugatog', 'Wawa (Pob.)', 'Apollo', 'Kabalutan', 'Maria Fe', 'Puksuan', 'Tagumpay'],
                        'ORION': ['ORION', 'Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago (Pob.)', 'Daang Bilolo (Pob.)', 'Bayan', 'General Lim (Kaput)', 'Kapunitan', 'Lati (Pob.)', 'Lusungan (Pob.)', 'Puting Buhangin', 'Sabatan', 'San Vicente (Pob.)', 'Santo Domingo', 'Villa Angeles (Pob.)', 'Waka (Pob.)', 'Santa Elena'],
                        'PILAR': ['PILAR', 'Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Burgos', 'Del Rosario (Pob.)', 'Diwa', 'Landing', 'Liyang', 'Nagwaling', 'Panilao', 'Pantingan', 'Poblacion', 'Rizal', 'Santa Rosa', 'Wakas North', 'Wakas South', 'Wawa'],
                        'SAMAL': ['SAMAL', 'East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan (Pob.)', 'San Roque (Pob.)', 'Santa Lucia', 'Sapa', 'Tabing Ilog', 'Gugo', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
                    };
                    
                    // Check if selected location is a municipality and user belongs to it
                    if (municipalityMap[locationFilter]) {
                        shouldShow = municipalityMap[locationFilter].includes(location);
                    }
                }
                
                row.style.display = shouldShow ? '' : 'none';
            }
        }
        
        // Update visible count
        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
        console.log('Visible rows after filtering:', visibleRows.length); // Debug log
        
        const noUsersMessage = document.getElementById('no-users-message');
        if (visibleRows.length === 0 && locationFilter !== '') {
            noUsersMessage.textContent = `No users found in ${locationFilter}`;
            noUsersMessage.style.display = 'block';
        } else {
            noUsersMessage.style.display = 'none';
        }
    }
    
    // Bulk delete functions
    function deleteUsersByLocation() {
        const locationFilter = document.getElementById('locationFilter').value;
        if (!locationFilter) {
            showAlert('warning', 'Please select a location first to delete users from that area.');
            return;
        }
        
        const confirmMessage = `Are you sure you want to delete ALL users from ${locationFilter}? This action cannot be undone!`;
        if (confirm(confirmMessage)) {
            // Show loading state
            showAlert('info', `Deleting users from ${locationFilter}...`);
            
            // Get all users from the selected location using municipality mapping
            const currentUsers = window.currentUsers || {};
            const usersArray = Array.isArray(currentUsers) ? currentUsers : Object.values(currentUsers);
            
            // Municipality mapping for accurate location filtering
            const municipalityMap = {
                'ABUCAY': ['ABUCAY', 'Bangkal', 'Calaylayan (Pob.)', 'Capitangan', 'Gabon', 'Laon (Pob.)', 'Mabatang', 'Omboy', 'Salian', 'Wawa (Pob.)'],
                'BAGAC': ['BAGAC', 'Bagumbayan (Pob.)', 'Banawang', 'Binuangan', 'Binukawan', 'Ibaba', 'Ibis', 'Pag-asa (Wawa-Sibacan)', 'Parang', 'Paysawan', 'Quinawan', 'San Antonio', 'Saysain', 'Tabing-Ilog (Pob.)', 'Atilano L. Ricardo'],
                'CITY OF BALANGA (Capital)': ['CITY OF BALANGA (Capital)', 'Bagumbayan', 'Cabog-Cabog', 'Munting Batangas (Cadre)', 'Cataning', 'Central', 'Cupang Proper', 'Cupang West', 'Dangcol (Bernabe)', 'Ibayo', 'Malabia', 'Poblacion', 'Pto. Rivas Ibaba', 'Pto. Rivas Itaas', 'San Jose', 'Sibacan', 'Camacho', 'Talisay', 'Tanato', 'Tenejero', 'Tortugas', 'Tuyo', 'Bagong Silang', 'Cupang North', 'Doña Francisca', 'Lote'],
                'DINALUPIHAN': ['DINALUPIHAN', 'Bangal', 'Bonifacio (Pob.)', 'Burgos (Pob.)', 'Colo', 'Daang Bago', 'Dalao', 'Del Pilar (Pob.)', 'Gen. Luna (Pob.)', 'Gomez (Pob.)', 'Happy Valley', 'Kataasan', 'Layac', 'Luacan', 'Mabini Proper (Pob.)', 'Mabini Ext. (Pob.)', 'Magsaysay', 'Naparing', 'New San Jose', 'Old San Jose', 'Padre Dandan (Pob.)', 'Pag-asa', 'Pagalanggang', 'Pinulot', 'Pita', 'Rizal (Pob.)', 'Roosevelt', 'Roxas (Pob.)', 'Saguing', 'San Benito', 'San Isidro (Pob.)', 'San Pablo (Bulate)', 'San Ramon', 'San Simon', 'Santo Niño', 'Sapang Balas', 'Santa Isabel (Tabacan)', 'Torres Bugauen (Pob.)', 'Tucop', 'Zamora (Pob.)', 'Aquino', 'Bayan-bayanan', 'Maligaya', 'Payangan', 'Pentor', 'Tubo-tubo', 'Jose C. Payumo, Jr.'],
                'HERMOSA': ['HERMOSA', 'A. Rivera (Pob.)', 'Almacen', 'Bacong', 'Balsic', 'Bamban', 'Burgos-Soliman (Pob.)', 'Cataning (Pob.)', 'Culis', 'Daungan (Pob.)', 'Mabiga', 'Mabuco', 'Maite', 'Mambog - Mandama', 'Palihan', 'Pandatung', 'Pulo', 'Saba', 'San Pedro (Pob.)', 'Santo Cristo (Pob.)', 'Sumalo', 'Tipo', 'Judge Roman Cruz Sr. (Mandama)', 'Sacrifice Valley'],
                'LIMAY': ['LIMAY', 'Alangan', 'Kitang I', 'Kitang 2 & Luz', 'Lamao', 'Landing', 'Poblacion', 'Reformista', 'Townsite', 'Wawa', 'Duale', 'San Francisco de Asis', 'St. Francis II'],
                'MARIVELES': ['MARIVELES', 'Alas-asin', 'Alion', 'Batangas II', 'Cabcaben', 'Lucanin', 'Baseco Country (Nassco)', 'Poblacion', 'San Carlos', 'San Isidro', 'Sisiman', 'Balon-Anito', 'Biaan', 'Camaya', 'Ipag', 'Malaya', 'Maligaya', 'Mt. View', 'Townsite'],
                'MORONG': ['MORONG', 'Binaritan', 'Bayan', 'Nagbalayong', 'Poblacion', 'Sabang'],
                'ORANI': ['ORANI', 'Bagong Paraiso (Pob.)', 'Balut (Pob.)', 'Bayan (Pob.)', 'Calero (Pob.)', 'Paking-Carbonero (Pob.)', 'Centro II (Pob.)', 'Dona', 'Kaparangan', 'Masantol', 'Mulawin', 'Pag-asa', 'Palihan (Pob.)', 'Pantalan Bago (Pob.)', 'Pantalan Luma (Pob.)', 'Parang Parang (Pob.)', 'Centro I (Pob.)', 'Sibul', 'Silahis', 'Tala', 'Talimundoc', 'Tapulao', 'Tenejero (Pob.)', 'Tugatog', 'Wawa (Pob.)', 'Apollo', 'Kabalutan', 'Maria Fe', 'Puksuan', 'Tagumpay'],
                'ORION': ['ORION', 'Arellano (Pob.)', 'Bagumbayan (Pob.)', 'Balagtas (Pob.)', 'Balut (Pob.)', 'Bantan', 'Bilolo', 'Calungusan', 'Camachile', 'Daang Bago (Pob.)', 'Daang Bilolo (Pob.)', 'Bayan', 'General Lim (Kaput)', 'Kapunitan', 'Lati (Pob.)', 'Lusungan (Pob.)', 'Puting Buhangin', 'Sabatan', 'San Vicente (Pob.)', 'Santo Domingo', 'Villa Angeles (Pob.)', 'Waka (Pob.)', 'Santa Elena'],
                'PILAR': ['PILAR', 'Ala-uli', 'Bagumbayan', 'Balut I', 'Balut II', 'Bantan Munti', 'Burgos', 'Del Rosario (Pob.)', 'Diwa', 'Landing', 'Liyang', 'Nagwaling', 'Panilao', 'Pantingan', 'Poblacion', 'Rizal', 'Santa Rosa', 'Wakas North', 'Wakas South', 'Wawa'],
                'SAMAL': ['SAMAL', 'East Calaguiman (Pob.)', 'East Daang Bago (Pob.)', 'Ibaba (Pob.)', 'Imelda', 'Lalawigan', 'Palili', 'San Juan (Pob.)', 'San Roque (Pob.)', 'Santa Lucia', 'Sapa', 'Tabing Ilog', 'Gugo', 'West Calaguiman (Pob.)', 'West Daang Bago (Pob.)']
            };
            
            const usersToDelete = usersArray.filter(user => {
                let userLocation = user.barangay || '';
                
                // Check if user belongs to the selected municipality
                if (municipalityMap[locationFilter]) {
                    return municipalityMap[locationFilter].includes(userLocation);
                } else {
                    // For specific barangay selection, exact match
                    return userLocation === locationFilter;
                }
            });
            
            if (usersToDelete.length === 0) {
                showAlert('warning', `No users found in ${locationFilter}`);
                return;
            }
            
            // Delete users one by one and wait for all to complete
            let deletedCount = 0;
            const deletePromises = usersToDelete.map(async (user) => {
                try {
                    const response = await fetch('API_BASE_URL + "/unified_api.php"', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete_user',
                            email: user.email
                        })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        deletedCount++;
                        return true;
                    }
                    return false;
                } catch (error) {
                    console.error('Error deleting user:', error);
                    return false;
                }
            });
            
            // Wait for all deletions to complete, then refresh
            Promise.all(deletePromises).then(() => {
                showAlert('success', `Successfully deleted ${deletedCount} users from ${locationFilter}`);
                disableAnimationsTemporarily();
                loadUsers();
                document.getElementById('locationFilter').value = '';
            });
        }
    }
    
    function deleteAllUsers() {
        const confirmMessage = 'Are you sure you want to delete ALL users from the system? This action cannot be undone and will remove all user data permanently!';
        if (confirm(confirmMessage)) {
            // Show loading state
            showAlert('info', 'Deleting all users... This may take a while.');
            
            // Get all users
            const currentUsers = window.currentUsers || {};
            const usersArray = Array.isArray(currentUsers) ? currentUsers : Object.values(currentUsers);
            
            if (usersArray.length === 0) {
                showAlert('warning', 'No users found to delete');
                return;
            }
            
            // Delete all users and wait for all to complete
            let deletedCount = 0;
            const deletePromises = usersArray.map(async (user) => {
                try {
                    const response = await fetch(API_BASE_URL + "/unified_api.php?endpoint=delete_user", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            user_email: user.email
                        })
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        deletedCount++;
                        return true;
                    }
                    return false;
                } catch (error) {
                    console.error('Error deleting user:', error);
                    return false;
                }
            });
            
            // Wait for all deletions to complete, then refresh
            Promise.all(deletePromises).then(() => {
                showAlert('success', `Successfully deleted ${deletedCount} users from the system`);
                disableAnimationsTemporarily();
                loadUsers();
                document.getElementById('locationFilter').value = '';
            });
        }
    }
    
    function parseCSVToUsers(csvText) {
        try {
            const lines = csvText.split('\n').filter(line => line.trim());
            if (lines.length < 2) return [];
            
            const headers = lines[0].split(',').map(h => h.trim());
            console.log('CSV Headers found:', headers);
        
        // Validate CSV structure - Updated to match actual database columns
        const expectedHeaders = [
            'user_email', 'name', 'birthday', 'age', 'gender', 'height', 'weight', 
            'bmi', 'muac', 'goal', 'allergies', 'diet_prefs', 
            'avoid_foods', 'barangay', 'income'
        ];
        
        const missingHeaders = expectedHeaders.filter(h => !headers.includes(h));
        if (missingHeaders.length > 0) {
            console.warn('Missing CSV headers:', missingHeaders);
            showAlert('warning', `CSV is missing some expected columns: ${missingHeaders.join(', ')}`);
        }
        
        const users = [];
        
        for (let i = 1; i < lines.length; i++) {
            // Use a more robust CSV parsing that handles commas within fields
            const values = parseCSVLine(lines[i]);
            if (values.length >= headers.length) {
                const user = {};
                headers.forEach((header, index) => {
                    let value = values[index] || '';
                    
                    // Handle special field processing for database columns
                    switch (header) {
                        case 'birthday':
                            // Ensure birthday is in YYYY-MM-DD format
                            if (value && value !== '') {
                                try {
                                    const date = new Date(value);
                                    if (!isNaN(date.getTime())) {
                                        value = date.toISOString().split('T')[0];
                                    }
                                } catch (e) {
                                    console.warn('Invalid birthday format:', value);
                                }
                            }
                            break;
                        case 'age':
                            // Ensure age is an integer
                            value = parseInt(value) || 0;
                            break;
                        case 'weight':
                        case 'height':
                        case 'bmi':
                        case 'muac':
                            // Ensure numeric values
                            value = parseFloat(value) || 0;
                            break;
                        case 'risk_score':
                            // Ensure risk score is an integer
                            value = parseInt(value) || 0;
                            break;
                        case 'gender':
                            // Ensure gender is valid
                            if (value && !['boy', 'girl', 'male', 'female'].includes(value.toLowerCase())) {
                                console.warn(`Invalid gender value: ${value}. Must be "boy", "girl", "male", or "female"`);
                                value = 'boy'; // Default to safe value
                            }
                            break;
                        case 'goal':
                            // Ensure goal is valid
                            if (value && !['weight_gain', 'weight_loss', 'maintain', 'muscle_gain'].includes(value.toLowerCase())) {
                                console.warn(`Invalid goal value: ${value}. Must be "weight_gain", "weight_loss", "maintain", or "muscle_gain"`);
                                value = 'maintain'; // Default to safe value
                            }
                            break;
                        case 'allergies':
                        case 'diet_prefs':
                        case 'avoid_foods':
                            // Handle comma-separated values
                            if (value && value.includes(';')) {
                                value = value.replace(/;/g, ',');
                            }
                            break;
                    }
                    
                    user[header] = value;
                });
                
                // Calculate risk score for each user based on their data
                if (user.weight && user.height && user.birthday) {
                    try {
                        // Calculate age from birthday
                        const birthDate = new Date(user.birthday);
                        
                        // Validate that the date is valid
                        if (isNaN(birthDate.getTime())) {
                            console.warn('Invalid birthday for user:', user.user_email, 'Birthday:', user.birthday);
                            user.birthday = null;
                            user.age = 0;
                            user.risk_score = 0;
                            continue; // Skip to next user
                        }
                        
                        const today = new Date();
                        let age = today.getFullYear() - birthDate.getFullYear();
                        const monthDiff = today.getMonth() - birthDate.getMonth();
                        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                            age--;
                        }
                        user.age = age;
                    
                    // Calculate BMI if not provided
                    if (!user.bmi && user.weight && user.height) {
                        const heightMeters = parseFloat(user.height) / 100.0;
                        user.bmi = heightMeters > 0 ? round((parseFloat(user.weight) / (heightMeters * heightMeters)), 1) : 0;
                    }
                    
                    // OFFICIAL MHO RISK SCORE CALCULATION
                    let riskScore = 0;
                    
                    // Calculate age in months for proper MHO assessment
                    const ageInMs = today - birthDate;
                    const ageInMonths = ageInMs / (1000 * 60 * 60 * 24 * 30.44);
                    const ageMonths = Math.floor(ageInMonths);
                    
                    // MHO Age-based risk assessment
                    if (ageMonths >= 6 && ageMonths <= 59) {
                        // Children 6-59 months: Use MUAC thresholds (MHO Standard)
                        if (user.muac > 0) {
                            if (user.muac < 11.5) riskScore += 40;      // Severe acute malnutrition
                            else if (user.muac < 12.5) riskScore += 25; // Moderate acute malnutrition
                            else riskScore += 0;                         // Normal
                        } else {
                            // If MUAC not provided, use weight-for-height approximation
                            const heightMeters = parseFloat(user.height) / 100;
                            const wfh = parseFloat(user.weight) / heightMeters;
                            if (wfh < 0.8) riskScore += 40;      // Severe acute malnutrition
                            else if (wfh < 0.9) riskScore += 25; // Moderate acute malnutrition
                            else riskScore += 0;                  // Normal
                        }
                    } else if (ageMonths < 240) {
                        // Children/adolescents 5-19 years (BMI-for-age, WHO MHO Standard)
                        if (user.bmi < 15) riskScore += 40;        // Severe thinness
                        else if (user.bmi < 17) riskScore += 30;   // Moderate thinness
                        else if (user.bmi < 18.5) riskScore += 20; // Mild thinness
                        else riskScore += 0;                        // Normal
                    } else {
                        // Adults 20+ (BMI, WHO MHO Standard)
                        if (user.bmi < 16.5) riskScore += 40;      // Severe thinness
                        else if (user.bmi < 18.5) riskScore += 25; // Moderate thinness
                        else riskScore += 0;                        // Normal weight
                    }
                    
                    // Additional MHO risk factors
                    if (user.allergies && user.allergies !== 'none' && user.allergies !== '') {
                        riskScore += 5; // Food allergies increase risk
                    }
                    
                    if (user.diet_prefs && (user.diet_prefs === 'vegan' || user.diet_prefs === 'vegetarian')) {
                        riskScore += 3; // Restricted diets may increase risk
                    }
                    
                    // Cap score at 100
                    user.risk_score = Math.min(riskScore, 100);
                    } catch (error) {
                        console.error('Error calculating risk score for user:', user.user_email, error);
                        // Set default values on error
                        user.age = 0;
                        user.risk_score = 0;
                    }
                }
                
                // Log the parsed user data for debugging
                console.log('Parsed user from CSV:', {
                    name: user.name,
                    user_email: user.user_email,
                    risk_score: user.risk_score,
                    barangay: user.barangay,
                    income: user.income,
                    basic_fields: {
                        gender: user.gender,
                        weight: user.weight,
                        height: user.height,
                        bmi: user.bmi,
                        muac: user.muac,
                        goal: user.goal,
                        allergies: user.allergies,
                        diet_prefs: user.diet_prefs,
                        avoid_foods: user.avoid_foods
                    }
                });
                
                users.push(user);
            }
        }
        
        return users;
        } catch (error) {
            console.error('Error parsing CSV:', error);
            showAlert('danger', 'Error parsing CSV: ' + error.message);
            return [];
        }
    }
    
    // Helper function to properly parse CSV lines with commas within fields
    function parseCSVLine(line) {
        const result = [];
        let current = '';
        let inQuotes = false;
        
        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            
            if (char === '"') {
                inQuotes = !inQuotes;
            } else if (char === ',' && !inQuotes) {
                result.push(current.trim());
                current = '';
            } else {
                current += char;
            }
        }
        
        // Add the last field
        result.push(current.trim());
        
        // Remove quotes from each field and clean up
        return result.map(field => {
            field = field.replace(/^"|"$/g, '');
            // Handle escaped quotes and other common CSV issues
            field = field.replace(/\\"/g, '"');
            return field;
        });
    }
    
    function validateUserData(users) {
        const errors = [];
        
        // EXACT mobile app answer options
        const exactAnswers = {
            gender: ['boy', 'girl'],
            swelling: ['yes', 'no'],
            weight_loss: ['<5% or none', '5-10%', '>10%'],
            feeding_behavior: ['good appetite', 'moderate appetite', 'poor appetite'],
            physical_signs: ['thin', 'shorter', 'weak', 'none'],
            boolean_values: ['true', 'false']
        };
        
        // EXACT income brackets from mobile app
        const exactIncomes = [
            'Below PHP 12,030/month (Below poverty line)',
            'PHP 12,031–20,000/month (Low)',
            'PHP 20,001–40,000/month (Middle)',
            'Above PHP 40,000/month (High)'
        ];
        
        // EXACT barangay names from mobile app dropdown
        const exactBarangays = [
            'Alion', 'Bangkal', 'Cabcaben', 'Camacho', 'Daan Bago', 'Daang Bago', 'Daang Pare', 
            'Del Pilar', 'General Lim', 'Kalaklan', 'Lamao', 'Lote', 'Luakan', 'Malaya', 
            'Mountain View', 'Paco', 'Pamantayan', 'Poblacion', 'San Antonio', 'San Miguel', 
            'San Nicolas', 'San Pedro', 'San Roque', 'San Vicente', 'Santa Rita', 'Santo Niño', 'Tuyo'
        ];
        
        users.forEach((user, index) => {
            // Required fields validation (matching mobile app requirements)
            const requiredFields = ['username', 'email', 'password', 'birthday', 'gender', 'weight', 'height', 'barangay', 'income', 'swelling', 'weight_loss', 'dietary_diversity', 'feeding_behavior'];
            requiredFields.forEach(field => {
                if (!user[field] || user[field] === '') {
                    errors.push(`Row ${index + 2}: Missing required field: ${field}`);
                }
            });
            
            if (user.email && !isValidEmail(user.email)) {
                errors.push(`Row ${index + 2}: Invalid email format`);
            }
            
            // Birthday validation - EXACT format
            if (user.birthday && user.birthday !== '') {
                const date = new Date(user.birthday);
                if (isNaN(date.getTime())) {
                    errors.push(`Row ${index + 2}: Invalid birthday format (use YYYY-MM-DD)`);
                }
            }
            
            // Numeric field validation with EXACT ranges
            if (user.weight && (isNaN(user.weight) || user.weight < 2 || user.weight > 300)) {
                errors.push(`Row ${index + 2}: Weight must be between 2-300 kg`);
            }
            
            if (user.height && (isNaN(user.height) || user.height < 30 || user.height > 250)) {
                errors.push(`Row ${index + 2}: Height must be between 30-250 cm`);
            }
            
            if (user.muac && (isNaN(user.muac) || user.muac < 0 || user.muac > 50)) {
                errors.push(`Row ${index + 2}: MUAC must be between 0-50 cm`);
            }
            
            if (user.dietary_diversity && (isNaN(user.dietary_diversity) || user.dietary_diversity < 0 || user.dietary_diversity > 10)) {
                errors.push(`Row ${index + 2}: Dietary diversity must be 0-10`);
            }
            
            // EXACT gender validation (case-sensitive)
            if (user.gender && !exactAnswers.gender.includes(user.gender)) {
                errors.push(`Row ${index + 2}: Gender must be EXACTLY "boy" or "girl" (lowercase)`);
            }
            
            // EXACT swelling validation (case-sensitive)
            if (user.swelling && !exactAnswers.swelling.includes(user.swelling)) {
                errors.push(`Row ${index + 2}: Swelling must be EXACTLY "yes" or "no" (lowercase)`);
            }
            
            // EXACT weight loss validation (case-sensitive)
            if (user.weight_loss && !exactAnswers.weight_loss.includes(user.weight_loss)) {
                errors.push(`Row ${index + 2}: Weight loss must be EXACTLY "<5% or none", "5-10%", or ">10%"`);
            }
            
            // EXACT feeding behavior validation (case-sensitive)
            if (user.feeding_behavior && !exactAnswers.feeding_behavior.includes(user.feeding_behavior)) {
                errors.push(`Row ${index + 2}: Feeding behavior must be EXACTLY "good appetite", "moderate appetite", or "poor appetite"`);
            }
            
            // EXACT barangay validation (case-sensitive)
            if (user.barangay && !exactBarangays.includes(user.barangay)) {
                errors.push(`Row ${index + 2}: Invalid barangay. Must be EXACTLY one of: ${exactBarangays.join(', ')}`);
            }
            
            // EXACT income validation (case-sensitive)
            if (user.income && !exactIncomes.includes(user.income)) {
                errors.push(`Row ${index + 2}: Invalid income bracket. Must be EXACTLY one of: ${exactIncomes.join(', ')}`);
            }
            
            // Physical signs validation (comma-separated, must be valid options)
            if (user.physical_signs && user.physical_signs !== '') {
                const signs = user.physical_signs.split(',').map(s => s.trim());
                const invalidSigns = signs.filter(sign => !exactAnswers.physical_signs.includes(sign));
                if (invalidSigns.length > 0) {
                    errors.push(`Row ${index + 2}: Invalid physical signs: ${invalidSigns.join(', ')}. Must be: ${exactAnswers.physical_signs.join(', ')}`);
                }
            }
            
            // EXACT boolean field validation (case-sensitive)
            const booleanFields = ['has_recent_illness', 'has_eating_difficulty', 'has_food_insecurity', 'has_micronutrient_deficiency', 'has_functional_decline'];
            booleanFields.forEach(field => {
                if (user[field] !== undefined && user[field] !== null && !exactAnswers.boolean_values.includes(user[field].toString())) {
                    errors.push(`Row ${index + 2}: ${field} must be EXACTLY "true" or "false" (lowercase)`);
                }
            });
        });
        
        return errors;
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    async function importUsers(users, skipDuplicates, progressDiv) {
        const results = { success: 0, failed: 0, skipped: 0, errors: [] };
        const totalUsers = users.length;
        
        for (let i = 0; i < users.length; i++) {
            const user = users[i];
            const progress = ((i + 1) / totalUsers) * 100;
            
            // Update progress bar
            const progressFill = progressDiv.querySelector('.progress-fill');
            if (progressFill) {
                progressFill.style.width = progress + '%';
            }
            
            try {
                // Check for duplicates if enabled
                if (skipDuplicates && await checkUserExists(user.user_email || user.email)) {
                    results.skipped++;
                    console.log(`Skipped duplicate user: ${user.user_email || user.email}`);
                    continue;
                }
                
                // Import user
                const success = await addUserFromCSV(user);
                console.log(`Import result for ${user.user_email || user.email}:`, success ? 'SUCCESS' : 'FAILED');
                
                if (success) {
                    results.success++;
                } else {
                    results.failed++;
                    results.errors.push(`Failed to import ${user.name || user.user_email || user.email}`);
                }
                
            } catch (error) {
                results.failed++;
                results.errors.push(`Error importing ${user.name || user.user_email || user.email}: ${error.message}`);
            }
            
            // Small delay to show progress
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        return results;
    }
    
    async function checkUserExists(email) {
        try {
            const response = await fetch(API_BASE_URL + '/unified_api.php?endpoint=usm&t=' + Date.now());
            const data = await response.json();
            
            if (data.users) {
                const users = Array.isArray(data.users) ? data.users : Object.values(data.users);
                return users.some(u => u.email === email);
            }
            return false;
        } catch (error) {
            console.error('Error checking user existence:', error);
            return false;
        }
    }
    
    async function addUserFromCSV(userData) {
        try {
            console.log('addUserFromCSV called with userData:', userData);
            
            // Calculate BMI if weight and height are provided
            let bmi = 0;
            if (userData.weight && userData.height) {
                const heightMeters = parseFloat(userData.height) / 100.0;
                bmi = heightMeters > 0 ? round((parseFloat(userData.weight) / (heightMeters * heightMeters)), 1) : 0;
            }
            
            // Calculate age from birthday if not provided
            let age = userData.age;
            if (!age && userData.birthday) {
                const birthDate = new Date(userData.birthday);
                const today = new Date();
                age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
            }
            
            // Create user with ALL data matching the actual database structure
            console.log('Creating user with database-compatible data...');
            
            const requestBody = {
                user_email: userData.user_email,
                name: userData.name,
                birthday: userData.birthday,
                age: age,
                gender: userData.gender,
                height_cm: parseFloat(userData.height) || 0,
                weight_kg: parseFloat(userData.weight) || 0,
                bmi: bmi,
                muac: parseFloat(userData.muac) || 0,
                risk_score: parseInt(userData.risk_score) || 0,
                allergies: userData.allergies || '',
                diet_prefs: userData.diet_prefs || '',
                avoid_foods: userData.avoid_foods || '',
                barangay: userData.barangay || '',
                income: userData.income || '',
                municipality: userData.municipality || '',
                province: userData.province || '',
                screening_date: new Date().toISOString().split('T')[0]
            };
            
            console.log('Sending to API:', requestBody);
            
            const userResponse = await fetch(API_BASE_URL + '/unified_api.php?endpoint=add_user', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestBody)
            });
            
            console.log('User API response status:', userResponse.status);
            console.log('User API response headers:', userResponse.headers);
            
            // Get response text first to debug
            const responseText = await userResponse.text();
            console.log('User API response text (first 200 chars):', responseText.substring(0, 200));
            
            let userResult;
            try {
                userResult = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Full response text:', responseText);
                throw new Error('Invalid JSON response: ' + responseText.substring(0, 100));
            }
            console.log('User API response:', userResult);
            console.log('Response keys:', Object.keys(userResult));
            console.log('Response success flag:', userResult.success);
            console.log('Response message:', userResult.message);
            
            // Check for various success indicators - API returns 200 status means success
            if (userResponse.status === 200) {
                // Check if we have any success indicators in the response
                if (userResult.success || userResult.user_id || userResult.message === 'User created successfully' || userResult.message === 'User added successfully') {
                    console.log('User created successfully with screening data');
                    return true;
                } else if (userResult.error) {
                    console.error('API returned error:', userResult.error);
                    return false;
                } else {
                    // If status is 200 and no error, consider it success
                    console.log('User created successfully (200 status, no errors)');
                    return true;
                }
            } else {
                console.error('Failed to create user - HTTP status:', userResponse.status);
                return false;
            }
            
        } catch (error) {
            console.error('Error adding user from CSV:', error);
            return false;
        }
    }
    
    // Helper function to round numbers
    function round(num, decimals) {
        return Math.round((num + Number.EPSILON) * Math.pow(10, decimals)) / Math.pow(10, decimals);
    }
    
    // Helper function to calculate string similarity for better matching
    function calculateStringSimilarity(str1, str2) {
        if (str1 === str2) return 100;
        if (str1.length === 0 || str2.length === 0) return 0;
        
        const longer = str1.length > str2.length ? str1 : str2;
        const shorter = str1.length > str2.length ? str2 : str1;
        
        if (longer.length === 0) return 100;
        
        // Calculate Levenshtein distance
        const matrix = [];
        for (let i = 0; i <= shorter.length; i++) {
            matrix[i] = [i];
        }
        for (let j = 0; j <= longer.length; j++) {
            matrix[0][j] = j;
        }
        
        for (let i = 1; i <= shorter.length; i++) {
            for (let j = 1; j <= longer.length; j++) {
                if (shorter.charAt(i - 1) === longer.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        
        const distance = matrix[shorter.length][longer.length];
        const similarity = ((longer.length - distance) / longer.length) * 100;
        
        return Math.round(similarity);
    }
    
    function showImportResults(results) {
        console.log('Showing import results:', results);
        
        let message = `📥 CSV Import completed! Success: ${results.success}`;
        
        if (results.skipped > 0) {
            message += `, Skipped: ${results.skipped}`;
        }
        
        if (results.failed > 0) {
            message += `, Failed: ${results.failed}`;
        }
        
        const alertType = results.failed === 0 ? 'success' : 'warning';
        
        showAlert(alertType, message);
        
        if (results.errors.length > 0) {
            console.log('Import errors:', results.errors);
            // Show detailed errors in console for debugging
            results.errors.forEach((error, index) => {
                console.error(`Import Error ${index + 1}:`, error);
            });
        }
        
        // Show real-time status for import completion
        if (results.success > 0) {
            console.log(`Imported ${results.success} users successfully!`);
            
            // Force refresh the table immediately after successful import
            console.log('Forcing immediate table refresh after CSV import...');
            window.lastCSVImportTime = Date.now(); // Set timestamp for import
            setTimeout(() => {
                loadUsers();
            }, 1000); // Wait 1 second for database to update
        } else if (results.failed > 0) {
            console.log(`Import failed for ${results.failed} users`);
        }
        
        // Auto-close modal after showing results
        setTimeout(() => {
            closeCSVImportModal();
        }, 3000);
    }

    // Updated Add User function with mobile app compatible data structure and risk score calculation
    async function addUser() {
        const form = document.getElementById('addUserForm');
        const formData = new FormData(form);
        
        // Validate required fields
        const username = formData.get('username').trim();
        const email = formData.get('email').trim();
        const password = formData.get('password');
        const birthday = formData.get('birthday');
        const gender = formData.get('gender');
        const weight = parseFloat(formData.get('weight')) || 0;
        const height = parseFloat(formData.get('height')) || 0;
        const barangay = formData.get('barangay');
        const income = formData.get('income');
        const swelling = formData.get('swelling');
        const weightLoss = formData.get('weightLoss');
        const dietaryDiversity = parseInt(formData.get('dietaryDiversity')) || 0;
        const feedingBehavior = formData.get('feedingBehavior');
        const muac = parseFloat(formData.get('muac')) || 0;
        
        // Physical signs checkboxes (EXACT mobile app format)
        const physicalThin = formData.get('physicalThin') === 'thin';
        const physicalShorter = formData.get('physicalShorter') === 'shorter';
        const physicalWeak = formData.get('physicalWeak') === 'weak';
        const physicalNone = formData.get('physicalNone') === 'none';
        
        // Clinical risk factors
        const recentIllness = formData.get('recentIllness') === 'true';
        const eatingDifficulty = formData.get('eatingDifficulty') === 'true';
        const foodInsecurity = formData.get('foodInsecurity') === 'true';
        const micronutrientDeficiency = formData.get('micronutrientDeficiency') === 'true';
        const functionalDecline = formData.get('functionalDecline') === 'true';
        
        // Dietary preferences
        const allergies = formData.get('allergies').trim();
        const dietPrefs = formData.get('dietPrefs').trim();
        const avoidFoods = formData.get('avoidFoods').trim();
        
        // Validate required fields
        if (!username || !email || !password || !birthday || !gender || !weight || !height || !barangay || !income || !swelling || !weightLoss || !dietaryDiversity || !feedingBehavior) {
            showAlert('danger', 'Please fill in all required fields marked with *');
            return;
        }
        
        try {
            // Calculate risk score exactly like mobile app
            const riskScore = calculateRiskScore(weight, height, dietaryDiversity, birthday, swelling, weightLoss, feedingBehavior, physicalThin, physicalShorter, physicalWeak, recentIllness, eatingDifficulty, foodInsecurity, micronutrientDeficiency, functionalDecline);
            
            // Create comprehensive screening data matching mobile app structure
            const screeningData = {
                gender: gender,
                birthday: birthday,
                weight: weight,
                height: height,
                dietary_diversity: dietaryDiversity,
                muac: muac,
                barangay: barangay,
                income: income,
                swelling: swelling,
                weight_loss: weightLoss,
                feeding_behavior: feedingBehavior,
                physical_signs: getPhysicalSignsString(physicalThin, physicalShorter, physicalWeak, physicalNone),
                has_recent_illness: recentIllness,
                has_eating_difficulty: eatingDifficulty,
                has_food_insecurity: foodInsecurity,
                has_micronutrient_deficiency: micronutrientDeficiency,
                has_functional_decline: functionalDecline
            };
            
            // Create user data object
            const userData = {
                username: username,
                email: email,
                password: password,
                risk_score: riskScore,
                allergies: allergies ? allergies.split(';').map(a => a.trim()) : [],
                diet_prefs: dietPrefs ? dietPrefs.split(';').map(d => d.trim()) : [],
                avoid_foods: avoidFoods
            };
            
            // Prepare user data for database insertion
            const userDataForDB = {
                user_email: email,
                name: username,
                birthday: birthday,
                age: calculateAge(birthday),
                gender: gender,
                weight_kg: weight,
                height_cm: height,
                bmi: calculateBMI(weight, height),
                muac: muac,
                barangay: barangay,
                income: income,
                swelling: swelling,
                weight_loss: weightLoss,
                feeding_behavior: feedingBehavior,
                physical_signs: getPhysicalSignsString(physicalThin, physicalShorter, physicalWeak, physicalNone),
                dietary_diversity: dietaryDiversity,
                clinical_risk_factors: getClinicalRiskFactorsString(recentIllness, eatingDifficulty, foodInsecurity, micronutrientDeficiency, functionalDecline),
                allergies: allergies,
                diet_prefs: dietPrefs,
                avoid_foods: avoidFoods,
                risk_score: riskScore,
                malnutrition_risk: getMalnutritionRiskLevel(riskScore),
                screening_date: new Date().toISOString().split('T')[0]
            };
            
            // Use the database function
            const result = await addUserToDatabase(userDataForDB);
            
            if (result.success) {
                showAlert('success', `User added successfully! Risk Score: ${riskScore}%`);
                closeAddUserModal();
                form.reset();
                disableAnimationsTemporarily();
                loadUsers(); // Refresh the table
            } else {
                showAlert('danger', result.message || 'Failed to add user');
            }
            
        } catch (error) {
            console.error('Error adding user:', error);
            showAlert('danger', 'Error adding user. Please try again.');
        }
    }
    
    // Risk score calculation function matching mobile app exactly
    function calculateRiskScore(weight, height, dietaryGroups, birthday, swelling, weightLoss, feedingBehavior, physicalThin, physicalShorter, physicalWeak, recentIllness, eatingDifficulty, foodInsecurity, micronutrientDeficiency, functionalDecline) {
        let score = 0;
        
        // Check for edema first - this overrides everything else
        if (swelling === 'yes') {
            return 100; // Immediate severe risk - urgent referral alert
        }
        
        // Calculate age in months
        const ageMonths = calculateAgeInMonths(birthday);
        const heightMeters = height / 100.0;
        const bmi = heightMeters > 0 ? weight / (heightMeters * heightMeters) : 0;
        
        // Validate input ranges (WHO plausible ranges)
        if (weight < 2 || weight > 300 || height < 30 || height > 250) {
            return 100; // Out of plausible range, return max risk
        }
        
        // Age-based risk assessment (Updated to match verified system)
        if (ageMonths >= 6 && ageMonths <= 59) {
            // Children 6-59 months: Use MUAC thresholds
            // Note: MUAC would be available here if provided
            const muac = parseFloat(document.getElementById('muac')?.value || document.getElementById('editMuac')?.value) || 0;
            if (muac > 0) {
                if (muac < 11.5) score += 40;      // Severe acute malnutrition (MUAC < 11.5 cm)
                else if (muac < 12.5) score += 25; // Moderate acute malnutrition (MUAC 11.5-12.5 cm)
                else score += 0;                    // Normal (MUAC ≥ 12.5 cm)
            } else {
                // If MUAC not provided, use weight-for-height approximation
                const wfh = weight / heightMeters;
                if (wfh < 0.8) score += 40;      // Severe acute malnutrition
                else if (wfh < 0.9) score += 25; // Moderate acute malnutrition
                else score += 0;                  // Normal
            }
        } else if (ageMonths < 240) {
            // Children/adolescents 5-19 years (BMI-for-age, WHO)
            if (bmi < 15) score += 40;        // Severe thinness
            else if (bmi < 17) score += 30;   // Moderate thinness
            else if (bmi < 18.5) score += 20; // Mild thinness
            else score += 0;                  // Normal
        } else {
            // Adults 20+ (BMI, WHO) - Updated to match verified system
            if (bmi < 16.5) score += 40;      // Severe thinness
            else if (bmi < 18.5) score += 25; // Moderate thinness
            else score += 0;                  // Normal weight
        }
        
        // Weight loss scoring (EXACT mobile app format)
        if (weightLoss === ">10%") score += 20;
        else if (weightLoss === "5-10%") score += 10;
        else if (weightLoss === "<5% or none") score += 0;
        
        // Feeding behavior scoring (EXACT mobile app format)
        if (feedingBehavior === "poor appetite") score += 8;
        else if (feedingBehavior === "moderate appetite") score += 8;
        else score += 0; // Good feeding behavior
        
        // Physical signs scoring (Updated to match verified system)
        if (physicalThin) score += 8;
        if (physicalShorter) score += 8;
        if (physicalWeak) score += 8;
        
        // Additional Clinical Risk Factors (New implementation)
        if (recentIllness) score += 8;           // Recent acute illness (past 2 weeks)
        if (eatingDifficulty) score += 8;        // Difficulty chewing/swallowing
        if (foodInsecurity) score += 10;         // Food insecurity / skipped meals
        if (micronutrientDeficiency) score += 6; // Visible signs of micronutrient deficiency
        if (functionalDecline) score += 8;       // Functional decline (older adults only)
        
        // Dietary diversity scoring (Updated to match verified system)
        if (dietaryGroups < 4) score += 10;
        else if (dietaryGroups < 6) score += 5;
        else score += 0; // 6+ food groups
        
        // Cap score at 100
        return Math.min(score, 100);
    }
    
    // Helper function to calculate age in months
    function calculateAgeInMonths(birthday) {
        if (!birthday) return 0;
        const birthDate = new Date(birthday);
        const today = new Date();
        const ageInMs = today - birthDate;
        const ageInMonths = ageInMs / (1000 * 60 * 60 * 24 * 30.44); // Average days per month
        return Math.floor(ageInMonths);
    }
    
    // Helper function to get physical signs string
    function getPhysicalSignsString(thin, shorter, weak, none) {
        const signs = [];
        if (thin) signs.push('thin');
        if (shorter) signs.push('shorter');
        if (weak) signs.push('weak');
        if (none) signs.push('none');
        return signs.length > 0 ? signs.join(',') : '';
    }
    
    // Manual extraction function for when JSON parsing fails
    function extractScreeningDataManually(screeningAnswersRaw) {
        const screening = {};
        
        try {
            // Extract key fields using regex patterns
            const patterns = {
                gender: /"gender":"([^"]*)"/,
                weight: /"weight":(\d+(?:\.\d+)?)/,
                height: /"height":(\d+(?:\.\d+)?)/,
                bmi: /"bmi":(\d+(?:\.\d+)?)/,
                birthday: /"birthday":"([^"]*)"/,
                barangay: /"barangay":"([^"]*)"/,
                income: /"income":"([^"]*)"/,
                allergies: /"allergies":"([^"]*)"/,
                diet_prefs: /"diet_prefs":"([^"]*)"/,
                avoid_foods: /"avoid_foods":"([^"]*)"/,
                swelling: /"swelling":"([^"]*)"/,
                weight_loss: /"weight_loss":"([^"]*)"/,
                feeding_behavior: /"feeding_behavior":"([^"]*)"/,
                physical_signs: /"physical_signs":"(\[[^"]*\])"/,
                dietary_diversity: /"dietary_diversity":"([^"]*)"/,
                has_recent_illness: /"has_recent_illness":(true|false)/,
                has_eating_difficulty: /"has_eating_difficulty":(true|false)/,
                has_food_insecurity: /"has_food_insecurity":(true|false)/,
                has_micronutrient_deficiency: /"has_micronutrient_deficiency":(true|false)/,
                has_functional_decline: /"has_functional_decline":(true|false)/
            };
            
            for (const [field, pattern] of Object.entries(patterns)) {
                const match = screeningAnswersRaw.match(pattern);
                if (match) {
                    if (field === 'weight' || field === 'height' || field === 'bmi') {
                        screening[field] = parseFloat(match[1]);
                    } else if (field.includes('has_')) {
                        screening[field] = match[1] === 'true';
                    } else {
                        screening[field] = match[1];
                    }
                }
            }
            
            console.log('Manual extraction successful:', screening);
            return screening;
            
        } catch (error) {
            console.error('Manual extraction error:', error);
            return {};
        }
    }

    // Updated Save User function
    async function saveUser() {
        const form = document.getElementById('editUserFormSimple');
        const formData = new FormData(form);
        
        const userId = formData.get('user_id');
        const username = formData.get('username').trim();
        const email = formData.get('email').trim();
        const riskLevel = formData.get('riskLevel');
        
        if (!username || !email) {
            showAlert('danger', 'Please fill in all required fields');
            return;
        }
        
        try {
            const response = await fetch('API_BASE_URL + "/unified_api.php"', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_user',
                    user_id: userId,
                    username: username,
                    email: email,
                    risk_level: riskLevel
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', 'User updated successfully!');
                closeEditUserModal();
                loadUsers(); // Refresh the table
            } else {
                showAlert('danger', result.message || 'Failed to update user');
            }
            
        } catch (error) {
            console.error('Error updating user:', error);
            showAlert('danger', 'Error updating user. Please try again.');
        }
    }
    
    // Test functions removed - they were creating test users on every page load
    
        // Debug and test functions removed - no longer needed
        
        // NEW SIMPLE THEME TOGGLE - Guaranteed to work!
        function newToggleTheme() {
            console.log('=== NEW THEME TOGGLE FUNCTION CALLED ===');
            
            const body = document.body;
            const toggleBtn = document.getElementById('new-theme-toggle');
            const icon = toggleBtn.querySelector('.new-theme-icon');
            
            // Check current theme
            const isCurrentlyLight = body.classList.contains('light-theme');
            
            if (isCurrentlyLight) {
                // Switch to dark theme
                console.log('Switching from LIGHT to DARK theme');
                
                // Remove light theme, add dark theme
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                
                // Update icon to moon (indicating you can switch to light)
                icon.textContent = '🌙';
                
                // Save preference
                localStorage.setItem('nutrisaur-theme', 'dark');
                
                // Apply dark theme colors directly
                body.style.backgroundColor = '#1A211A';
                body.style.color = '#E8F0D6';
                
                // Update button color
                toggleBtn.style.backgroundColor = '#FF9800';
                
                console.log('✅ Dark theme applied successfully!');
                
            } else {
                // Switch to light theme
                console.log('Switching from DARK to LIGHT theme');
                
                // Remove dark theme, add light theme
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                
                // Update icon to sun (indicating you can switch to dark)
                icon.textContent = '☀';
                
                // Save preference
                localStorage.setItem('nutrisaur-theme', 'light');
                
                // Apply light theme colors directly
                body.style.backgroundColor = '#F0F7F0';
                body.style.color = '#1B3A1B';
                
                // Update button color
                toggleBtn.style.backgroundColor = '#000000';
                
                console.log('✅ Light theme applied successfully!');
            }
            
            // Force a repaint
            body.offsetHeight;
            
            console.log('Final body classes:', body.className);
            console.log('Final icon:', icon.textContent);
            console.log('Final background color:', body.style.backgroundColor);
            console.log('Final text color:', body.style.color);
        }
        
        // OLD COMPLEX THEME TOGGLE (keeping for reference)
        function forceCSSUpdate() {
            const root = document.documentElement;
            const computedStyle = getComputedStyle(root);
            
            // Force a repaint by temporarily changing a property
            root.style.setProperty('--force-update', Date.now().toString());
            root.offsetHeight; // Force reflow
            root.style.removeProperty('--force-update');
        }
        
        // Theme toggle functionality
        function toggleTheme() {
            console.log('toggleTheme function called');
            const body = document.body;
            const themeToggle = document.getElementById('theme-toggle');
            const themeIcon = themeToggle.querySelector('.theme-icon');
            
            console.log('Current theme classes:', body.className);
            console.log('Theme toggle element:', themeToggle);
            console.log('Theme icon element:', themeIcon);
            console.log('Current icon text:', themeIcon.textContent);
            
            if (body.classList.contains('light-theme')) {
                console.log('Switching from light to dark theme');
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                themeIcon.textContent = '🌙';
                localStorage.setItem('nutrisaur-theme', 'dark');
                
                // Update CSS custom properties for dark theme
                document.documentElement.style.setProperty('--color-bg', '#1A211A');
                document.documentElement.style.setProperty('--color-card', '#2A3326');
                document.documentElement.style.setProperty('--color-text', '#E8F0D6');
                document.documentElement.style.setProperty('--color-highlight', '#A1B454');
                document.documentElement.style.setProperty('--color-accent1', '#8CA86E');
                document.documentElement.style.setProperty('--color-accent2', '#B5C88D');
                document.documentElement.style.setProperty('--color-accent3', '#546048');
                document.documentElement.style.setProperty('--color-accent4', '#C9D8AA');
                document.documentElement.style.setProperty('--color-border', 'rgba(161, 180, 84, 0.2)');
                document.documentElement.style.setProperty('--color-shadow', 'rgba(0, 0, 0, 0.1)');
                
                // Force immediate update of CSS variables
                document.documentElement.style.setProperty('--color-bg', '#1A211A', 'important');
                document.documentElement.style.setProperty('--color-card', '#2A3326', 'important');
                document.documentElement.style.setProperty('--color-text', '#E8F0D6', 'important');
                
                // Force CSS update
                forceCSSUpdate();
                
                // Directly set body colors to ensure they change
                body.style.backgroundColor = '#1A211A';
                body.style.color = '#E8F0D6';
                
                console.log('Dark theme applied, body classes:', body.className);
                console.log('Icon updated to:', themeIcon.textContent);
            } else {
                console.log('Switching from dark to light theme');
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                themeIcon.textContent = '☀';
                localStorage.setItem('nutrisaur-theme', 'light');
                
                // Update CSS custom properties for light theme
                document.documentElement.style.setProperty('--color-bg', '#F0F7F0');
                document.documentElement.style.setProperty('--color-card', '#FFFFFF');
                document.documentElement.style.setProperty('--color-text', '#1B3A1B');
                document.documentElement.style.setProperty('--color-highlight', '#66BB6A');
                document.documentElement.style.setProperty('--color-accent1', '#81C784');
                document.documentElement.style.setProperty('--color-accent2', '#4CAF50');
                document.documentElement.style.setProperty('--color-accent3', '#2E7D32');
                document.documentElement.style.setProperty('--color-accent4', '#A5D6A7');
                document.documentElement.style.setProperty('--color-border', '#C8E6C9');
                document.documentElement.style.setProperty('--color-shadow', 'rgba(76, 175, 80, 0.1)');
                
                // Force immediate update of CSS variables
                document.documentElement.style.setProperty('--color-bg', '#F0F7F0', 'important');
                document.documentElement.style.setProperty('--color-card', '#FFFFFF', 'important');
                document.documentElement.style.setProperty('--color-text', '#1B3A1B', 'important');
                
                // Force CSS update
                forceCSSUpdate();
                
                // Directly set body colors to ensure they change
                body.style.backgroundColor = '#F0F7F0';
                body.style.color = '#1B3A1B';
                
                console.log('Light theme applied, body classes:', body.className);
                console.log('Icon updated to:', themeIcon.textContent);
            }
            
            // Force a repaint to ensure theme is applied
            body.offsetHeight;
            
            console.log('New theme classes:', body.className);
            console.log('Theme saved to localStorage:', localStorage.getItem('nutrisaur-theme'));
            console.log('Final icon text:', themeIcon.textContent);
            
            // Debug: Check if CSS variables are being applied
            const computedStyle = getComputedStyle(body);
            console.log('Background color:', computedStyle.backgroundColor);
            console.log('Color:', computedStyle.color);
        }
    
        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing theme...');
            
            const savedTheme = localStorage.getItem('nutrisaur-theme');
            const newToggleBtn = document.getElementById('new-theme-toggle');
            const newIcon = newToggleBtn.querySelector('.new-theme-icon');
            // Debug and test buttons removed - no longer needed
            
            console.log('Saved theme from localStorage:', savedTheme);
            console.log('New theme toggle element:', newToggleBtn);
            console.log('New theme icon element:', newIcon);
            
            if (savedTheme === 'light') {
                console.log('Applying light theme');
                document.body.classList.remove('dark-theme');
                document.body.classList.add('light-theme');
                newIcon.textContent = '☀';
                
                // Update new toggle button
                newIcon.textContent = '☀';
                newToggleBtn.style.backgroundColor = '#000000';
                
                // Apply light theme colors directly
                document.body.style.backgroundColor = '#F0F7F0';
                document.body.style.color = '#1B3A1B';
                
                // Set CSS custom properties for light theme
                document.documentElement.style.setProperty('--color-bg', '#F0F7F0');
                document.documentElement.style.setProperty('--color-card', '#FFFFFF');
                document.documentElement.style.setProperty('--color-text', '#1B3A1B');
                document.documentElement.style.setProperty('--color-highlight', '#66BB6A');
                document.documentElement.style.setProperty('--color-accent1', '#81C784');
                document.documentElement.style.setProperty('--color-accent2', '#4CAF50');
                document.documentElement.style.setProperty('--color-accent3', '#2E7D32');
                document.documentElement.style.setProperty('--color-accent4', '#A5D6A7');
                document.documentElement.style.setProperty('--color-border', '#C8E6C9');
                document.documentElement.style.setProperty('--color-shadow', 'rgba(76, 175, 80, 0.1)');
                
                // Force CSS update
                forceCSSUpdate();
                
                // Directly set body colors to ensure they change
                document.body.style.backgroundColor = '#F0F7F0';
                document.body.style.color = '#1B3A1B';

            } else {
                // Default to dark theme (matches the HTML body class)
                console.log('Applying dark theme (default)');
                document.body.classList.remove('light-theme');
                document.body.classList.add('dark-theme');
                newIcon.textContent = '🌙';
                
                // Update new toggle button
                newIcon.textContent = '🌙';
                newToggleBtn.style.backgroundColor = '#FF9800';
                
                // Apply dark theme colors directly
                document.body.style.backgroundColor = '#1A211A';
                document.body.style.color = '#E8F0D6';
                
                // Set CSS custom properties for dark theme
                document.documentElement.style.setProperty('--color-bg', '#1A211A');
                document.documentElement.style.setProperty('--color-card', '#2A3326');
                document.documentElement.style.setProperty('--color-text', '#E8F0D6');
                document.documentElement.style.setProperty('--color-highlight', '#A1B454');
                document.documentElement.style.setProperty('--color-accent1', '#8CA86E');
                document.documentElement.style.setProperty('--color-accent2', '#B5C88D');
                document.documentElement.style.setProperty('--color-accent3', '#546048');
                document.documentElement.style.setProperty('--color-accent4', '#C9D8AA');
                document.documentElement.style.setProperty('--color-border', 'rgba(161, 180, 84, 0.2)');
                document.documentElement.style.setProperty('--color-shadow', 'rgba(0, 0, 0, 0.1)');
                
                // Force CSS update
                forceCSSUpdate();
                
                // Directly set body colors to ensure they change
                document.body.style.backgroundColor = '#1A211A';
                document.body.style.color = '#E8F0D6';

            }
            
            // Force a repaint to ensure theme is applied
            document.body.offsetHeight;
            console.log('Final body classes:', document.body.className);
            console.log('New theme icon text:', newIcon.textContent);
            
            // Add click event to NEW theme toggle button
            console.log('Setting up NEW theme toggle event listener');
            console.log('New toggle button element:', newToggleBtn);
            newToggleBtn.addEventListener('click', newToggleTheme);
            console.log('NEW theme toggle event listener added successfully');
            
            // Debug and test button event listeners removed - no longer needed
            
            // OLD theme toggle button removed - no longer exists
            console.log('Old theme toggle button removed from DOM');
            
            // Debug: Check initial CSS variables
            const computedStyle = getComputedStyle(document.body);
            console.log('Initial background color:', computedStyle.backgroundColor);
            console.log('Initial color:', computedStyle.color);
            
            // Force theme application to ensure CSS variables are set
            const currentTheme = document.body.classList.contains('light-theme') ? 'light' : 'dark';
            if (currentTheme === 'light') {
                // Re-apply light theme CSS variables
                document.documentElement.style.setProperty('--color-bg', '#F0F7F0');
                document.documentElement.style.setProperty('--color-card', '#FFFFFF');
                document.documentElement.style.setProperty('--color-text', '#1B3A1B');
                document.documentElement.style.setProperty('--color-highlight', '#66BB6A');
                document.documentElement.style.setProperty('--color-accent1', '#81C784');
                document.documentElement.style.setProperty('--color-accent2', '#4CAF50');
                document.documentElement.style.setProperty('--color-accent3', '#2E7D32');
                document.documentElement.style.setProperty('--color-accent4', '#A5D6A7');
                document.documentElement.style.setProperty('--color-border', '#C8E6C9');
                document.documentElement.style.setProperty('--color-shadow', 'rgba(76, 175, 80, 0.1)');
                
                // Force CSS update
                forceCSSUpdate();
                
                // Directly set body colors to ensure they change
                document.body.style.backgroundColor = '#F0F7F0';
                document.body.style.color = '#1B3A1B';
            } else {
                // Re-apply dark theme CSS variables
                document.documentElement.style.setProperty('--color-bg', '#1A211A');
                document.documentElement.style.setProperty('--color-card', '#2A3326');
                document.documentElement.style.setProperty('--color-text', '#E8F0D6');
                document.documentElement.style.setProperty('--color-highlight', '#A1B454');
                document.documentElement.style.setProperty('--color-accent1', '#8CA86E');
                document.documentElement.style.setProperty('--color-accent2', '#B5C88D');
                document.documentElement.style.setProperty('--color-accent3', '#546048');
                document.documentElement.style.setProperty('--color-accent4', '#C9D8AA');
                document.documentElement.style.setProperty('--color-border', 'rgba(161, 180, 84, 0.2)');
                document.documentElement.style.setProperty('--color-shadow', 'rgba(0, 0, 0, 0.1)');
                
                // Force CSS update
                forceCSSUpdate();
                
                // Directly set body colors to ensure they change
                document.body.style.backgroundColor = '#1A211A';
                document.body.style.color = '#E8F0D6';
            }
        });
        
        // Add User Modal Function
        function showAddUserModal() {
            alert('Add User functionality - This would open a modal to add new users');
            // In a real implementation, this would show a modal form
        }

        // Download CSV Template Function
        function downloadCSVTemplate() {
            // Create CSV template content - using the format that was working before
            const csvContent = `user_email,name,birthday,gender,weight,height,barangay,income
john_doe@example.com,John Doe,1990-01-01,male,70,175,Lamao,PHP 20,001–40,000/month (Middle)
jane_smith@example.com,Jane Smith,1985-05-15,female,60,165,Pilar,PHP 12,031–20,000/month (Low)`;

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
            const modal = document.getElementById('csvImportModal');
            if (modal) {
                modal.style.display = 'block';
                // Reset the form and preview
                document.getElementById('csvImportForm').reset();
                document.getElementById('csvPreview').style.display = 'none';
                document.getElementById('importCSVBtn').disabled = true;
                document.getElementById('cancelBtn').style.display = 'none';
                // Show upload area
                document.getElementById('uploadArea').style.display = 'block';
            }
        }

        // Close CSV Import Modal Function
        function closeCSVImportModal() {
            const modal = document.getElementById('csvImportModal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        // Toggle CSV Info Function
        function toggleCSVInfo() {
            const infoTooltip = document.getElementById('csvInfoTooltip');
            if (infoTooltip) {
                infoTooltip.style.display = infoTooltip.style.display === 'none' ? 'block' : 'none';
            }
        }

        // Cancel Upload Function
        function cancelUpload() {
            const cancelBtn = document.getElementById('cancelBtn');
            const uploadArea = document.getElementById('csvUploadArea');
            if (cancelBtn) {
                cancelBtn.style.display = 'none';
            }
            if (uploadArea) {
                uploadArea.style.display = 'block';
            }
            // Reset any upload state
            console.log('Upload cancelled');
        }

        // Handle CSV File Selection
        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) return;
            processCSVFile(file);
        }

        // Process CSV File (used by both file input and drag & drop)
        function processCSVFile(file) {
            // Check if it's a CSV file
            if (!file.name.toLowerCase().endsWith('.csv')) {
                showCSVStatus('error', 'Please select a CSV file');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const csvContent = e.target.result;
                    const lines = csvContent.split('\n');
                    const headers = lines[0].split(',').map(h => h.trim());
                    
                    // Basic validation
                    if (lines.length < 2) {
                        showCSVStatus('error', 'CSV file must contain at least headers and one data row');
                        return;
                    }

                    // Show preview
                    showCSVPreview(lines, headers);
                    
                    // Enable import button
                    document.getElementById('importCSVBtn').disabled = false;
                    
                } catch (error) {
                    console.error('Error reading CSV:', error);
                    alert('Error reading CSV file. Please check the file format.');
                }
            };
            
            reader.readAsText(file);
        }

        // Show CSV Preview
        function showCSVPreview(lines, headers) {
            const previewDiv = document.getElementById('csvPreview');
            const contentDiv = document.getElementById('csvPreviewContent');
            
            if (!previewDiv || !contentDiv) return;

            // Create preview table
            let tableHTML = '<table class="csv-preview-table">';
            
            // Headers
            tableHTML += '<thead><tr>';
            headers.forEach(header => {
                tableHTML += `<th>${header}</th>`;
            });
            tableHTML += '</tr></thead>';
            
            // Data rows (first 5 rows)
            tableHTML += '<tbody>';
            const maxRows = Math.min(6, lines.length); // Headers + 5 data rows
            for (let i = 1; i < maxRows; i++) {
                if (lines[i].trim()) {
                    const cells = lines[i].split(',').map(cell => cell.trim());
                    tableHTML += '<tr>';
                    cells.forEach(cell => {
                        tableHTML += `<td>${cell}</td>`;
                    });
                    tableHTML += '</tr>';
                }
            }
            tableHTML += '</tbody></table>';
            
            contentDiv.innerHTML = tableHTML;
            previewDiv.style.display = 'block';
            

        }

        // Process CSV Import - Using the REAL working logic
        async function processCSVImport() {
            const fileInput = document.getElementById('csvFile');
            const file = fileInput.files[0];
            if (!file) {
                showCSVStatus('error', 'Please select a CSV file first');
                return;
            }

            const skipDuplicates = document.getElementById('skipDuplicates').checked;
            
            try {
                // Show loading state
                document.getElementById('importCSVBtn').disabled = true;
                document.getElementById('importCSVBtn').textContent = '🔄 Processing...';
                
                const csvContent = await readFileAsText(file);
                const lines = csvContent.split('\n');
                const headers = lines[0].split(',').map(h => h.trim());
                
                // Validate headers - using the format that was working before
                const requiredHeaders = ['user_email', 'name', 'birthday', 'gender', 'weight', 'height', 'barangay', 'income'];
                const missingHeaders = requiredHeaders.filter(h => !headers.includes(h));
                
                if (missingHeaders.length > 0) {
                    showCSVStatus('error', `Missing required headers: ${missingHeaders.join(', ')}`);
                    return;
                }
                
                // Process each row using the REAL working logic
                let successCount = 0;
                let errorCount = 0;
                let skippedCount = 0;
                const errors = [];
                const duplicates = [];
                
                for (let i = 1; i < lines.length; i++) {
                    if (lines[i].trim()) {
                        try {
                            const cells = lines[i].split(',').map(cell => cell.trim());
                            const userData = {};
                            
                            headers.forEach((header, index) => {
                                userData[header] = cells[index] || '';
                            });
                            
                            // Basic validation
                            if (!userData.user_email || !userData.name || !userData.birthday) {
                                errors.push(`Row ${i + 1}: Missing required fields`);
                                errorCount++;
                                continue;
                            }
                            
                            // Check for duplicates before importing
                            const existingUser = await checkUserExists(userData.user_email);
                            if (existingUser) {
                                if (skipDuplicates) {
                                    skippedCount++;
                                    duplicates.push(`Row ${i + 1}: ${userData.user_email} (skipped)`);
                                    continue;
                                } else {
                                    errors.push(`Row ${i + 1}: User with email ${userData.user_email} already exists`);
                                    errorCount++;
                                    continue;
                                }
                            }
                            
                            // Use the REAL working function to add the user
                            const success = await addUserFromCSV(userData);
                            if (success) {
                                successCount++;
                            } else {
                                errorCount++;
                                errors.push(`Row ${i + 1}: Failed to import user`);
                            }
                            
                        } catch (error) {
                            errors.push(`Row ${i + 1}: ${error.message}`);
                            errorCount++;
                        }
                    }
                }
                
                // Show results
                let message = `Import completed! Success: ${successCount}`;
                if (skippedCount > 0) {
                    message += `, Skipped: ${skippedCount}`;
                }
                if (errorCount > 0) {
                    message += `, Errors: ${errorCount}`;
                }
                
                // Show detailed results in modal
                if (duplicates.length > 0) {
                    showCSVStatus('warning', `Import completed with duplicates! Success: ${successCount}, Skipped: ${skippedCount}, Errors: ${errorCount}. Check console for details.`);
                    console.log('Duplicates found:', duplicates);
                } else if (errorCount > 0) {
                    showCSVStatus('warning', message);
                } else {
                    showCSVStatus('success', message);
                }
                
                // Close modal and reload page if successful
                if (successCount > 0) {
                    setTimeout(() => {
                        closeCSVImportModal();
                        // Refresh the table to show new users
                        disableAnimationsTemporarily();
                        loadUsers();
                    }, 2000); // Wait 2 seconds to show success message
                }
                
            } catch (error) {
                console.error('CSV import error:', error);
                showCSVStatus('error', 'Error processing CSV file: ' + error.message);
            } finally {
                // Reset button state
                document.getElementById('importCSVBtn').disabled = false;
                document.getElementById('importCSVBtn').textContent = '📥 Import CSV';
            }
        }

        // Helper function to read file as text
        function readFileAsText(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = e => resolve(e.target.result);
                reader.onerror = e => reject(new Error('Failed to read file'));
                reader.readAsText(file);
            });
        }

        // Show status message in modal instead of popup
        function showCSVStatus(type, message) {
            const statusDiv = document.getElementById('csvStatusMessage');
            if (statusDiv) {
                statusDiv.style.display = 'block';
                
                // Set colors based on type
                if (type === 'success') {
                    statusDiv.style.backgroundColor = 'rgba(161, 180, 84, 0.2)';
                    statusDiv.style.color = 'var(--color-highlight)';
                    statusDiv.style.border = '2px solid var(--color-highlight)';
                } else if (type === 'warning') {
                    statusDiv.style.backgroundColor = 'rgba(224, 201, 137, 0.2)';
                    statusDiv.style.color = 'var(--color-warning)';
                    statusDiv.style.border = '2px solid var(--color-warning)';
                } else if (type === 'error') {
                    statusDiv.style.backgroundColor = 'rgba(207, 134, 134, 0.2)';
                    statusDiv.style.color = 'var(--color-danger)';
                    statusDiv.style.border = '2px solid var(--color-danger)';
                } else {
                    statusDiv.style.backgroundColor = 'rgba(161, 180, 84, 0.1)';
                    statusDiv.style.color = 'var(--color-text)';
                    statusDiv.style.border = '2px solid rgba(161, 180, 84, 0.3)';
                }
                
                statusDiv.textContent = message;
                
                // Auto-hide success messages after 3 seconds
                if (type === 'success') {
                    setTimeout(() => {
                        statusDiv.style.display = 'none';
                    }, 3000);
                }
            }
        }

        // Drag and Drop Event Handlers
        function handleDragOver(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.style.borderColor = 'var(--color-highlight)';
            event.currentTarget.style.backgroundColor = 'rgba(161, 180, 84, 0.1)';
        }

        function handleDragEnter(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.style.borderColor = 'var(--color-highlight)';
            event.currentTarget.style.backgroundColor = 'rgba(161, 180, 84, 0.15)';
        }

        function handleDragLeave(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.style.borderColor = 'rgba(161, 180, 84, 0.4)';
            event.currentTarget.style.backgroundColor = 'rgba(161, 180, 84, 0.05)';
        }

        function handleDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            
            // Reset styling
            event.currentTarget.style.borderColor = 'rgba(161, 180, 84, 0.4)';
            event.currentTarget.style.backgroundColor = 'rgba(161, 180, 84, 0.05)';
            
            const files = event.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];
                if (file.type === 'text/csv' || file.name.toLowerCase().endsWith('.csv')) {
                    // Process the dropped CSV file
                    processCSVFile(file);
                    
                    // Update the file input to show the selected file
                    const fileInput = document.getElementById('csvFile');
                    if (fileInput) {
                        // Create a new FileList-like object
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(file);
                        fileInput.files = dataTransfer.files;
                    }
                } else {
                    showCSVStatus('error', 'Please drop a CSV file');
                }
            }
        }
        

    </script>
</body>
</html>
                                                                        