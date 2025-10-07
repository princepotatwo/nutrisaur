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
    <title>NutriSaur - Settings & Admin Controls</title>
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

.user-info {
    display: flex;
    align-items: center;
}

.user-avatar {
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

.light-theme .user-avatar {
    background-color: var(--color-accent1);
    color: white;
    font-weight: bold;
}

.theme-toggle {
    margin-left: 20px;
    cursor: pointer;
    background-color: var(--color-card);
    border: 2px solid var(--color-highlight);
    color: var(--color-text);
    padding: 8px 12px;
    border-radius: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.theme-toggle:hover {
    background-color: var(--color-highlight);
    color: #fff;
}

.theme-toggle .icon {
    margin-right: 5px;
    font-size: 16px;
}

.light-theme .theme-toggle .moon {
    display: inline;
}

.light-theme .theme-toggle .sun {
    display: none;
}

.dark-theme .theme-toggle .moon {
    display: none;
}

.dark-theme .theme-toggle .sun {
    display: inline;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
    margin-bottom: 30px;
}

.feature-card {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    display: flex;
    flex-direction: column;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
}

.light-theme .feature-card {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background-color: rgba(161, 180, 84, 0.2);
    border-radius: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    font-size: 30px;
}

.light-theme .feature-icon {
    background-color: rgba(142, 185, 110, 0.2);
}

.feature-card h3 {
    font-size: 20px;
    margin-bottom: 15px;
    color: var(--color-highlight);
}

.feature-card p {
    font-size: 15px;
    opacity: 0.9;
    margin-bottom: 20px;
    flex-grow: 1;
}

.feature-action {
    display: flex;
    align-items: center;
    font-weight: 500;
    color: var(--color-highlight);
    margin-top: auto;
}

.feature-action span {
    margin-left: 8px;
    font-size: 18px;
}

/* Admin accounts section */
.admin-accounts-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .admin-accounts-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.admin-accounts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.admin-accounts-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.add-admin-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: var(--color-highlight);
    color: var(--color-text);
    padding: 8px 15px;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    transition: all 0.3s ease;
}

.light-theme .add-admin-btn {
    color: white;
}

.add-admin-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
}

.admin-table th {
    text-align: left;
    padding: 12px 15px;
    border-bottom: 1px solid rgba(161, 180, 84, 0.3);
    font-weight: 500;
    color: var(--color-highlight);
}

.admin-table td {
    padding: 12px 15px;
    border-bottom: 1px solid rgba(161, 180, 84, 0.1);
    vertical-align: middle;
    height: 60px;
    min-height: 60px;
}

/* Ensure admin table action column is properly aligned */
.admin-table th:last-child,
.admin-table td:last-child {
    text-align: center;
    white-space: nowrap;
    overflow: visible;
    text-overflow: clip;
    min-width: 140px;
    vertical-align: middle;
}

.admin-table tr:last-child td {
    border-bottom: none;
}

.admin-table tr:hover td {
    background-color: rgba(161, 180, 84, 0.05);
}

.admin-role {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: inline-block;
}

.role-superadmin {
    background-color: var(--color-highlight);
    color: var(--color-text);
}

.light-theme .role-superadmin {
    color: white;
}

.role-admin {
    background-color: var(--color-accent1);
    color: var(--color-text);
}

.light-theme .role-admin {
    color: white;
}

.role-editor {
    background-color: var(--color-accent3);
    color: var(--color-text);
}

.light-theme .role-editor {
    color: white;
}

.admin-status {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

.status-active {
    background-color: var(--color-accent3);
}

.status-inactive {
    background-color: var(--color-danger);
}

.admin-actions {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
    align-items: center;
    vertical-align: middle;
    padding: 4px;
    width: 100%;
    box-sizing: border-box;
    height: 100%;
    min-height: 52px;
}

.admin-action-btn {
    background-color: var(--color-highlight);
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    flex-shrink: 0;
    min-width: 55px;
    height: 28px;
    margin-right: 4px;
}

.admin-action-btn:hover {
    background-color: #8CA86E;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(161, 180, 84, 0.3);
}

/* System configurations section */
.system-config-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .system-config-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.system-config-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.system-config-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.config-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.config-tab {
    padding: 8px 15px;
    border-radius: 8px;
    background-color: rgba(161, 180, 84, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
}

.config-tab.active {
    background-color: var(--color-highlight);
    color: white;
}

.light-theme .config-tab.active {
    color: var(--color-text);
}

.config-tab:hover:not(.active) {
    background-color: rgba(161, 180, 84, 0.2);
}

.config-section {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
}

.light-theme .config-section {
    background-color: rgba(234, 240, 220, 0.7);
}

.config-section-title {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.config-items {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.config-item {
    margin-bottom: 15px;
}

.config-item-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    font-size: 14px;
}

.config-item-desc {
    font-size: 12px;
    opacity: 0.7;
    margin-bottom: 8px;
}

.config-item-input {
    width: 100%;
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid rgba(161, 180, 84, 0.3);
    background-color: rgba(42, 51, 38, 0.5);
    color: var(--color-text);
}

.light-theme .config-item-input {
    background-color: rgba(255, 255, 255, 0.9);
    color: var(--color-text);
}

.config-item-slider {
    width: 100%;
    height: 8px;
    border-radius: 4px;
    background: rgba(161, 180, 84, 0.2);
    outline: none;
}

.config-item-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--color-highlight);
    cursor: pointer;
}

.config-item-slider::-moz-range-thumb {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--color-highlight);
    cursor: pointer;
}

.config-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 20px;
}

.config-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
}

.config-btn-save {
    background-color: var(--color-highlight);
    color: var(--color-text);
}

.light-theme .config-btn-save {
    color: white;
}

.config-btn-reset {
    background-color: rgba(161, 180, 84, 0.1);
    color: var(--color-text);
}

.config-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Maintenance section */
.maintenance-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .maintenance-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.maintenance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.maintenance-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.maintenance-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.maintenance-card {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.light-theme .maintenance-card {
    background-color: rgba(234, 240, 220, 0.7);
}

.maintenance-card-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.maintenance-card-icon {
    font-size: 24px;
    color: var(--color-highlight);
    width: 45px;
    height: 45px;
    background-color: rgba(161, 180, 84, 0.2);
    border-radius: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.maintenance-card-title {
    font-size: 18px;
    font-weight: 500;
}

.maintenance-card-content {
    flex: 1;
    margin-bottom: 15px;
    font-size: 14px;
    opacity: 0.9;
}

.maintenance-card-footer {
    margin-top: auto;
}

.maintenance-status {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    font-size: 14px;
}

.status-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.status-good {
    background-color: var(--color-accent3);
}

.status-warning {
    background-color: var(--color-warning);
}

.status-error {
    background-color: var(--color-danger);
}

.maintenance-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    background-color: var(--color-highlight);
    color: var(--color-text);
    transition: all 0.3s ease;
    width: 100%;
}

.light-theme .maintenance-btn {
    color: white;
}

.maintenance-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
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

.light-theme .navbar-logo-icon {
    color: white;
}

.navbar-logo-text {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-text);
}

.light-theme .navbar-logo-text {
    color: var(--color-highlight);
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

    .navbar a {
        padding: 12px 25px;
    }
    
    .navbar li {
        margin-bottom: 2px;
    }

    .feature-grid, .config-items, .maintenance-grid {
        grid-template-columns: 1fr;
    }
}

.light-theme .navbar {
    background-color: rgba(234, 240, 220, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 3px 0 20px rgba(0, 0, 0, 0.06);
}

/* Add this custom scrollbar styling */
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
    </style>
</head>
<body class="dark-theme">
    <div class="dashboard">
        <header>
            <div class="logo">
               
                    <h1>NutriSaur Dashboard</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($username); ?></span>
                <button class="theme-toggle" id="theme-toggle">
                    <span class="icon sun">☀️</span>
                    <span class="icon moon">🌙</span>
                    <span class="text">Switch Theme</span>
                </button>
            </div>
        </header>

        <div class="admin-accounts-container">
            <div class="admin-accounts-header">
                <div class="admin-accounts-title">Admin Accounts & Permissions</div>
                <button class="add-admin-btn">
                    <span>+</span>
                    <span>Add Admin</span>
                </button>
            </div>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>kevin123</td>
                        <td>Kevin</td>
                        <td>kevin@gmail.com</td>
                        <td><span class="admin-role role-superadmin">Super Admin</span></td>
                        <td><span class="admin-status status-active"></span> Active</td>
                        <td>Today, 09:45 AM</td>
                        <td class="admin-actions">
                            <button class="admin-action-btn">Edit</button>
                            <button class="admin-action-btn">Permissions</button>
                        </td>
                    </tr>
                    <tr>
                        <td>joshua456</td>
                        <td>Joshua</td>
                        <td>joshua@gmail.com</td>
                        <td><span class="admin-role role-admin">Admin</span></td>
                        <td><span class="admin-status status-active"></span> Active</td>
                        <td>Yesterday, 15:22 PM</td>
                        <td class="admin-actions">
                            <button class="admin-action-btn">Edit</button>
                            <button class="admin-action-btn">Permissions</button>
                        </td>
                    </tr>
                    <tr>
                        <td>ramz789</td>
                        <td>Ramz</td>
                        <td>ramz@gmail.com</td>
                        <td><span class="admin-role role-editor">Editor</span></td>
                        <td><span class="admin-status status-active"></span> Active</td>
                        <td>3 days ago, 11:30 AM</td>
                        <td class="admin-actions">
                            <button class="admin-action-btn">Edit</button>
                            <button class="admin-action-btn">Permissions</button>
                        </td>
                    </tr>
                    <tr>
                        <td>arjon012</td>
                        <td>Arjon</td>
                        <td>arjon@gmail.com</td>
                        <td><span class="admin-role role-editor">Editor</span></td>
                        <td><span class="admin-status status-inactive"></span> Inactive</td>
                        <td>2 weeks ago, 14:05 PM</td>
                        <td class="admin-actions">
                            <button class="admin-action-btn">Edit</button>
                            <button class="admin-action-btn">Permissions</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="system-config-container">
            <div class="system-config-header">
                <div class="system-config-title">System Configuration</div>
            </div>
            
            <div class="config-tabs">
                <div class="config-tab active">Malnutrition Alerts</div>
                <div class="config-tab">AI Parameters</div>
                <div class="config-tab">Notifications</div>
                <div class="config-tab">Privacy & Data</div>
            </div>
            
            <div class="config-section">
                <h3 class="config-section-title">
                    <span>Malnutrition Risk Thresholds</span>
                </h3>
                
                <div class="config-items">
                    <div class="config-item">
                        <label class="config-item-label">Severe Risk Threshold (%)</label>
                        <div class="config-item-desc">BMI or other measurements below this percentage of recommended values will trigger severe risk alerts</div>
                        <input type="range" min="10" max="40" value="30" class="config-item-slider" id="severe-threshold">
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <span>10%</span>
                            <span id="severe-value">30%</span>
                            <span>40%</span>
                        </div>
                    </div>
                    
                    <div class="config-item">
                        <label class="config-item-label">Moderate Risk Threshold (%)</label>
                        <div class="config-item-desc">BMI or other measurements below this percentage of recommended values will trigger moderate risk alerts</div>
                        <input type="range" min="40" max="70" value="60" class="config-item-slider" id="moderate-threshold">
                        <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                            <span>40%</span>
                            <span id="moderate-value">60%</span>
                            <span>70%</span>
                        </div>
                    </div>
                    
                    <div class="config-item">
                        <label class="config-item-label">Alert Reevaluation Period (days)</label>
                        <div class="config-item-desc">Number of days after which the system will reevaluate a user's risk level</div>
                        <input type="number" min="1" max="30" value="7" class="config-item-input">
                    </div>
                    
                    <div class="config-item">
                        <label class="config-item-label">Minimum Data Points Required</label>
                        <div class="config-item-desc">Minimum number of nutrition data points required before triggering risk assessments</div>
                        <input type="number" min="1" max="20" value="5" class="config-item-input">
                    </div>
                </div>
            </div>
            
            <div class="config-section">
                <h3 class="config-section-title">
                    <span>Alert Notification Settings</span>
                </h3>
                
                <div class="config-items">
                    <div class="config-item">
                        <label class="config-item-label">Notify Health Providers for Severe Risks</label>
                        <div class="config-item-desc">Automatically send notifications to designated health providers when severe risk is detected</div>
                        <select class="config-item-input">
                            <option>Yes - Immediately</option>
                            <option>Yes - Daily Digest</option>
                            <option>No - Manual Sending Only</option>
                        </select>
                    </div>
                    
                    <div class="config-item">
                        <label class="config-item-label">User Alert Method</label>
                        <div class="config-item-desc">How users will be notified about their malnutrition risk status</div>
                        <select class="config-item-input">
                            <option>In-App + Email</option>
                            <option>In-App Only</option>
                            <option>Email Only</option>
                            <option>SMS + Email</option>
                        </select>
                    </div>
                    
                    <div class="config-item">
                        <label class="config-item-label">Emergency Contact Threshold</label>
                        <div class="config-item-desc">Risk level at which emergency contacts will be notified (if provided by user)</div>
                        <select class="config-item-input">
                            <option>Severe Risk Only</option>
                            <option>Moderate & Severe Risks</option>
                            <option>All Risk Levels</option>
                            <option>Never Contact</option>
                        </select>
                    </div>
                    
                    <div class="config-item">
                        <label class="config-item-label">Alert Message Template</label>
                        <div class="config-item-desc">Template for alert messages sent to users (variables: {name}, {risk_level}, {recommendations})</div>
                        <textarea class="config-item-input" rows="3">Hi {name}, our system has detected a {risk_level} nutritional risk in your recent data. We recommend: {recommendations}</textarea>
                    </div>
                </div>
            </div>
            
            <div class="config-actions">
                <button class="config-btn config-btn-reset">Reset to Defaults</button>
                <button class="config-btn config-btn-save">Save Changes</button>
            </div>
        </div>
    </div>
    
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
                <li><a href="dash.php"><span class="navbar-icon">📊</span><span>Dashboard</span></a></li>
                <li><a href="community_hub.php"><span class="navbar-icon">🏘️</span><span>Community Nutrition Hub</span></a></li>
                <li><a href="event.php"><span class="navbar-icon">⚠️</span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="USM.php"><span class="navbar-icon">👥</span><span>User Management</span></a></li>
                <li><a href="AI.php"><span class="navbar-icon">🤖</span><span>Chatbot & AI Logs</span></a></li>
                <li><a href="settings.php" class="active"><span class="navbar-icon">⚙️</span><span>Settings & Admin</span></a></li>
                <li><a href="logout.php" style="color: #ff5252;"><span class="navbar-icon">🚪</span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v2.0 • © 2025</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>

    <script>
        // Theme Toggle with broadcast
        const themeToggle = document.getElementById('theme-toggle');
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            document.body.classList.toggle('light-theme');
            
            // Save preference to localStorage and broadcast the change
            const currentTheme = document.body.classList.contains('dark-theme') ? 'dark' : 'light';
            localStorage.setItem('nutrisaur-theme', currentTheme);
            
            // Optional: Show a small notification that theme has been changed
            showThemeChangeNotification(currentTheme);
        });

        // Function to show theme change notification
        function showThemeChangeNotification(theme) {
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.padding = '10px 20px';
            notification.style.backgroundColor = 'var(--color-card)';
            notification.style.borderRadius = '8px';
            notification.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
            notification.style.zIndex = '9999';
            notification.textContent = `Theme changed to ${theme} mode`;
            
            document.body.appendChild(notification);
            
            // Remove notification after 2 seconds
            setTimeout(() => {
                notification.remove();
            }, 2000);
        }

        // Load saved theme function
        function loadSavedTheme() {
            const savedTheme = localStorage.getItem('nutrisaur-theme');
            if (savedTheme === 'light') {
                document.body.classList.remove('dark-theme');
                document.body.classList.add('light-theme');
            } else {
                document.body.classList.add('dark-theme');
                document.body.classList.remove('light-theme');
            }
        }

        // Load theme on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadSavedTheme();
        });

        // Update threshold values display
        const severeSlider = document.getElementById('severe-threshold');
        const severeValue = document.getElementById('severe-value');
        severeSlider.addEventListener('input', () => {
            severeValue.textContent = severeSlider.value + '%';
        });

        const moderateSlider = document.getElementById('moderate-threshold');
        const moderateValue = document.getElementById('moderate-value');
        moderateSlider.addEventListener('input', () => {
            moderateValue.textContent = moderateSlider.value + '%';
        });

        // Config tabs functionality
        const configTabs = document.querySelectorAll('.config-tab');
        configTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                configTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                // In a real implementation, this would show/hide the relevant config sections
            });
        });
    </script>
</body>
</html>
