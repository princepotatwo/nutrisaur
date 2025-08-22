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
    <title>NutriSaur - Nutritional Analysis</title>
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

.recent-plans {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .recent-plans {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.recent-plans h2 {
    font-size: 20px;
    margin-bottom: 20px;
    color: var(--color-highlight);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-all {
            font-size: 14px;
    color: var(--color-accent1);
            cursor: pointer;
        }

.plans-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
        }

        .plan-item {
            display: flex;
            align-items: center;
            padding: 15px;
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
            transition: background-color 0.3s ease;
        }

.light-theme .plan-item {
    background-color: rgba(234, 240, 220, 0.7);
        }

        .plan-item:hover {
    background-color: rgba(161, 180, 84, 0.1);
        }

        .plan-avatar {
    width: 50px;
    height: 50px;
            border-radius: 50%;
    background-color: var(--color-accent1);
            display: flex;
    justify-content: center;
            align-items: center;
    color: var(--color-text);
            font-weight: bold;
            margin-right: 15px;
    font-size: 18px;
}

.light-theme .plan-avatar {
    color: white;
        }

        .plan-details {
            flex: 1;
        }

        .plan-name {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
        }

        .plan-meta {
    font-size: 14px;
    opacity: 0.8;
        }

        .plan-actions {
            display: flex;
            gap: 10px;
        }

        .plan-action-btn {
            background: none;
            border: none;
    color: var(--color-accent1);
            cursor: pointer;
    font-size: 16px;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
            transition: background-color 0.3s ease;
        }

        .plan-action-btn:hover {
    background-color: rgba(161, 180, 84, 0.1);
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

    .feature-grid {
        grid-template-columns: 1fr;
    }

    .navbar a {
        padding: 12px 25px;
    }
    
    .navbar li {
        margin-bottom: 2px;
    }
}

.light-theme .navbar {
    background-color: rgba(234, 240, 220, 0.85);
    backdrop-filter: blur(10px);
    box-shadow: 3px 0 20px rgba(0, 0, 0, 0.06);
}

.action-button {
    background-color: var(--color-highlight);
    color: var(--color-text);
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
}

.light-theme .action-button {
    color: white;
}

.action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.action-button span {
    margin-right: 8px;
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

/* New Styles for Nutritional Analysis Dashboard */
.search-filter-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 25px;
}

.light-theme .search-filter-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.search-section {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.search-bar {
    display: flex;
    gap: 10px;
}

.search-bar input {
    flex: 1;
    padding: 12px 15px;
    border-radius: 8px;
    border: 1px solid rgba(161, 180, 84, 0.3);
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    font-size: 15px;
}

.light-theme .search-bar input {
    background-color: rgba(234, 240, 220, 0.7);
    color: var(--color-text);
}

.search-button {
    padding: 0 15px;
    border-radius: 8px;
    border: none;
    background-color: var(--color-highlight);
    color: var(--color-text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.light-theme .search-button {
    color: white;
}

.search-button:hover {
    background-color: var(--color-accent3);
}

.filter-options {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.filter-group label {
    font-size: 14px;
    opacity: 0.8;
}

.filter-group select,
.date-input {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid rgba(161, 180, 84, 0.3);
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    min-width: 150px;
}

.light-theme .filter-group select,
.light-theme .date-input {
    background-color: rgba(234, 240, 220, 0.7);
    color: var(--color-text);
}

.date-range {
    display: flex;
    align-items: center;
    gap: 10px;
}

.date-range span {
    opacity: 0.7;
}

.date-input {
    width: 140px;
}

.apply-filters {
    margin-left: auto;
    padding: 8px 15px;
    border-radius: 6px;
    border: none;
    background-color: var(--color-highlight);
    color: var(--color-text);
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.light-theme .apply-filters {
    color: white;
}

.apply-filters:hover {
    background-color: var(--color-accent3);
    transform: translateY(-2px);
}

/* Deficiency Dashboard Styles */
.deficiency-dashboard {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .deficiency-dashboard {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 20px;
    color: var(--color-highlight);
}

.export-btn {
    padding: 8px 15px;
    border-radius: 6px;
    border: none;
    background-color: var(--color-accent3);
    color: var(--color-text);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.light-theme .export-btn {
    color: white;
}

.export-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Data Table Styles */
.data-table {
    overflow-x: auto;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead th {
    background-color: rgba(161, 180, 84, 0.1);
    color: var(--color-highlight);
    text-align: left;
    padding: 12px 15px;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
}

tbody tr {
    border-bottom: 1px solid rgba(161, 180, 84, 0.1);
    cursor: pointer;
    transition: background-color 0.3s ease;
}

tbody tr:hover:not(.user-details) {
    background-color: rgba(161, 180, 84, 0.05);
}

tbody td {
    padding: 12px 15px;
}

.view-btn {
    padding: 5px 10px;
    border-radius: 4px;
    border: none;
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-text);
    cursor: pointer;
    transition: all 0.3s ease;
}

.view-btn:hover {
    background-color: var(--color-highlight);
}

.light-theme .view-btn:hover {
    color: white;
}

/* Risk Level Styling */
.severe-risk {
    background-color: rgba(207, 134, 134, 0.15);
}

.moderate-risk {
    background-color: rgba(224, 201, 137, 0.15);
}

.low-risk {
    background-color: rgba(161, 180, 84, 0.15);
}

/* User Details Panel */
.user-details {
    display: none;
    background-color: rgba(42, 51, 38, 0.3);
}

.light-theme .user-details {
    background-color: rgba(234, 240, 220, 0.5);
}

.user-details.active {
    display: table-row;
}

.detail-panels {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Image Analysis Panel */
.image-analysis-panel h3, 
.food-log-panel h3 {
    font-size: 16px;
    color: var(--color-highlight);
    margin-bottom: 15px;
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.image-card {
    background-color: rgba(42, 51, 38, 0.5);
    border-radius: 10px;
    overflow: hidden;
}

.light-theme .image-card {
    background-color: rgba(234, 240, 220, 0.7);
}

.image-container {
    width: 100%;
    height: 200px;
    overflow: hidden;
}

.image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-annotations {
    padding: 15px;
}

.annotation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(161, 180, 84, 0.1);
}

.annotation:last-child {
    border-bottom: none;
}

.annotation-text {
    font-size: 14px;
    flex: 1;
}

.reclassify-btn {
    background-color: rgba(161, 180, 84, 0.2);
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    color: var(--color-text);
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.reclassify-btn:hover {
    background-color: var(--color-highlight);
}

.light-theme .reclassify-btn:hover {
    color: white;
}

/* Food Log Discrepancies */
.food-log-panel {
    background-color: rgba(42, 51, 38, 0.5);
    border-radius: 10px;
    padding: 20px;
}

.light-theme .food-log-panel {
    background-color: rgba(234, 240, 220, 0.7);
}

.discrepancy-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.discrepancy-item {
    display: flex;
    gap: 15px;
}

.discrepancy-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.discrepancy-icon.warning {
    color: var(--color-warning);
}

.discrepancy-icon.info {
    color: var(--color-accent1);
}

.discrepancy-content {
    flex: 1;
}

.discrepancy-title {
    font-weight: 500;
    margin-bottom: 5px;
}

.discrepancy-details {
    font-size: 14px;
    opacity: 0.8;
}

/* Detail Action Buttons */
.detail-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.export-pdf,
.message-user,
.schedule-follow {
    padding: 8px 15px;
    border-radius: 6px;
    border: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.export-pdf {
    background-color: var(--color-highlight);
    color: var(--color-text);
}

.message-user {
    background-color: var(--color-accent1);
    color: var(--color-text);
}

.schedule-follow {
    background-color: var(--color-accent3);
    color: var(--color-text);
}

.light-theme .export-pdf,
.light-theme .message-user,
.light-theme .schedule-follow {
    color: white;
}

.export-pdf:hover,
.message-user:hover,
.schedule-follow:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-top: 20px;
}

.pagination-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid rgba(161, 180, 84, 0.3);
    background-color: rgba(42, 51, 38, 0.3);
    color: var(--color-text);
    cursor: pointer;
    transition: all 0.3s ease;
}

.light-theme .pagination-btn {
    background-color: rgba(234, 240, 220, 0.7);
}

.pagination-btn:hover {
    background-color: rgba(161, 180, 84, 0.2);
}

.page-numbers {
    display: flex;
    gap: 5px;
    align-items: center;
}

.page-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    border: none;
    background-color: transparent;
    color: var(--color-text);
    cursor: pointer;
    transition: all 0.3s ease;
}

.page-number.active,
.page-number:hover {
    background-color: var(--color-highlight);
}

.light-theme .page-number.active,
.light-theme .page-number:hover {
    color: white;
}

.risk-users {
    margin-bottom: 30px;
}

.risk-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
}

.risk-tab {
    padding: 10px 20px;
    border: 1px solid rgba(161, 180, 84, 0.3);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.risk-tab.active {
    background-color: var(--color-highlight);
    color: var(--color-text);
}

.risk-tab:hover {
    background-color: rgba(161, 180, 84, 0.05);
}

.risk-tab .risk-count {
    margin-left: 5px;
    font-size: 0.8em;
    opacity: 0.7;
}

.users-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.user-item {
    display: flex;
    align-items: center;
    padding: 10px;
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

.user-item:hover {
    background-color: rgba(161, 180, 84, 0.05);
}

.user-profile {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: var(--color-accent1);
    margin-right: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
}

.light-theme .user-profile {
    background-color: var(--color-accent1);
    color: white;
}

.user-details {
    flex: 1;
}

.user-name {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
}

.user-info-row {
    display: flex;
    gap: 10px;
}

.user-info-item {
    font-size: 14px;
    opacity: 0.8;
}

.user-actions {
    display: flex;
    gap: 10px;
}

.user-action-btn {
    background: none;
    border: none;
    color: var(--color-accent1);
    cursor: pointer;
    font-size: 16px;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    transition: background-color 0.3s ease;
}

.user-action-btn:hover {
    background-color: rgba(161, 180, 84, 0.1);
}

.recent-alerts {
    margin-bottom: 30px;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.alert-item {
    padding: 10px;
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 8px;
    transition: background-color 0.3s ease;
}

.alert-item:hover {
    background-color: rgba(161, 180, 84, 0.05);
}

.alert-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.alert-title {
    font-weight: 500;
}

.alert-time {
    font-size: 0.8em;
    opacity: 0.7;
}

.alert-content {
    margin-bottom: 10px;
}

.alert-actions {
    display: flex;
    gap: 10px;
}

.alert-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.alert-btn.secondary {
    background-color: rgba(161, 180, 84, 0.2);
    color: var(--color-text);
}

.alert-btn.primary {
    background-color: var(--color-highlight);
    color: var(--color-text);
}

.alert-btn.secondary:hover {
    background-color: rgba(161, 180, 84, 0.3);
}

.alert-btn.primary:hover {
    background-color: var(--color-accent3);
}

/* Add these new styles for dark theme */
.dark-theme .risk-users,
.dark-theme .recent-alerts {
    background-color: var(--color-card) !important;
    border: none !important;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06) !important;
}

.dark-theme .risk-users h2,
.dark-theme .recent-alerts h2 {
    color: var(--color-highlight) !important;
}

.dark-theme .risk-tabs {
    border-bottom: 1px solid rgba(161, 180, 84, 0.2) !important;
}

.dark-theme .risk-count {
    background-color: rgba(0, 0, 0, 0.1) !important;
}

.dark-theme .user-item,
.dark-theme .alert-item {
    background-color: rgba(42, 51, 38, 0.7) !important;
}
    </style>
</head>
<body class="dark-theme">
    <div class="dashboard">
        <header>
            <div class="logo">
                <h1>Nutritional Analysis</h1>
        </div>
            <div class="user-info">
                <div class="user-avatar"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </header>

        <div class="search-filter-container">
            <div class="search-section">
                <div class="search-bar">
                    <input type="text" placeholder="Search by User ID, Name or Location...">
                    <button class="search-button">
                        <span>🔍</span>
                    </button>
                </div>
                <div class="filter-options">
                    <div class="filter-group">
                        <label>Risk Level</label>
                        <select>
                            <option value="">All Risk Levels</option>
                            <option value="severe">Severe</option>
                            <option value="moderate">Moderate</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Location</label>
                        <select>
                            <option value="">All Locations</option>
                            <option value="lamao">Lamao</option>
                            <option value="town_proper">Town Proper</option>
                            <option value="alangan">Alangan</option>
                            <option value="kitang">Kitang</option>
                            <option value="poblacion">Poblacion</option>
                            <option value="st_francis">St. Francis</option>
                            <option value="st_peter">St. Peter</option>
                            <option value="st_joseph">St. Joseph</option>
                            <option value="st_john">St. John</option>
                            <option value="st_mary">St. Mary</option>
                            <option value="st_anne">St. Anne</option>
                            <option value="st_anthony">St. Anthony</option>
                            <option value="st_ignatius">St. Ignatius</option>
                            <option value="st_teresa">St. Teresa</option>
                            <option value="st_vincent">St. Vincent</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Date Range</label>
                        <div class="date-range">
                            <input type="date" class="date-input">
                            <span>to</span>
                            <input type="date" class="date-input">
                        </div>
                    </div>
                    <button class="apply-filters">Apply Filters</button>
                </div>
        </div>
    </div>

        <div class="deficiency-dashboard">
            <div class="section-header">
                <h2>Deficiency Table</h2>
                <div class="actions">
                    <button class="export-btn">
                        <span>📊</span> Bulk CSV Export
                    </button>
                </div>
            </div>
            
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Age</th>
                            <th>Gender</th>
                            <th>Location</th>
                            <th>Top Deficiency</th>
                            <th>Risk Score</th>
                            <th>Last Scan</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="severe-risk" onclick="toggleUserDetails('user001')">
                            <td>NS-001</td>
                            <td>34</td>
                            <td>M</td>
                            <td>Lamao</td>
                            <td>Protein Deficiency</td>
                            <td>0.89</td>
                            <td>2023-10-15</td>
                            <td><button class="view-btn">View</button></td>
                        </tr>
                        <tr id="user001-details" class="user-details">
                            <td colspan="7">
                                <div class="detail-panels">
                                    <div class="image-analysis-panel">
                                        <h3>Image Analysis</h3>
                                        <div class="image-grid">
                                            <div class="image-card">
                                                <div class="image-container">
                                                    <img src="https://placehold.co/300x200/333/FFF?text=Kevin+Profile" alt="Kevin's Analysis">
                                                </div>
                                                <div class="image-annotations">
                                                    <div class="annotation">
                                                        <span class="annotation-text">BMI: 16.2 (Severely Underweight)</span>
                                                        <button class="reclassify-btn">Reclassify</button>
                                                    </div>
                                                    <div class="annotation">
                                                        <span class="annotation-text">Iron Levels: Low</span>
                                                        <button class="reclassify-btn">Reclassify</button>
                                                    </div>
                                                </div>
                                            </div>
                </div>
            </div>
            
                                    <div class="food-log-panel">
                                        <h3>Food Log Discrepancies</h3>
                                        <div class="discrepancy-list">
                                            <div class="discrepancy-item">
                                                <div class="discrepancy-icon warning">❗️</div>
                                                <div class="discrepancy-content">
                                                    <div class="discrepancy-title">Critical Protein Deficiency</div>
                                                    <div class="discrepancy-details">
                                                        Protein levels below 20% of recommended daily intake for 5 consecutive days.
                </div>
            </div>
        </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        <tr class="severe-risk" onclick="toggleUserDetails('user002')">
                            <td>NS-002</td>
                            <td>28</td>
                            <td>M</td>
                            <td>Town Proper</td>
                            <td>Vitamin A Deficiency</td>
                            <td>0.85</td>
                            <td>2023-10-10</td>
                            <td><button class="view-btn">View</button></td>
                        </tr>
                        <tr id="user002-details" class="user-details">
                            <td colspan="7">
                                <div class="detail-panels">
                                    <div class="image-analysis-panel">
                                        <h3>Image Analysis</h3>
                                        <div class="image-grid">
                                            <div class="image-card">
                                                <div class="image-container">
                                                    <img src="https://placehold.co/300x200/333/FFF?text=Joshua+Profile" alt="Joshua's Analysis">
                                                </div>
                                                <div class="image-annotations">
                                                    <div class="annotation">
                                                        <span class="annotation-text">BMI: 17.5 (Underweight)</span>
                                                        <button class="reclassify-btn">Reclassify</button>
                                                    </div>
                                                    <div class="annotation">
                                                        <span class="annotation-text">Vitamin A: Critical</span>
                                                        <button class="reclassify-btn">Reclassify</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="food-log-panel">
                                        <h3>Food Log Discrepancies</h3>
                                        <div class="discrepancy-list">
                                            <div class="discrepancy-item">
                                                <div class="discrepancy-icon warning">❗️</div>
                                                <div class="discrepancy-content">
                                                    <div class="discrepancy-title">Vitamin A Deficiency Warning</div>
                                                    <div class="discrepancy-details">
                                                        Consistent low levels of Vitamin A over the past 2 weeks. Recommended to adjust meal plan and consider supplements.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        <tr class="severe-risk" onclick="toggleUserDetails('user003')">
                            <td>NS-003</td>
                            <td>31</td>
                            <td>M</td>
                            <td>Alangan</td>
                            <td>Protein & B12 Deficiency</td>
                            <td>0.92</td>
                            <td>2023-10-08</td>
                            <td><button class="view-btn">View</button></td>
                        </tr>
                        <tr id="user003-details" class="user-details">
                            <td colspan="7">
                                <div class="detail-panels">
                                    <div class="image-analysis-panel">
                                        <h3>Image Analysis</h3>
                                        <div class="image-grid">
                                            <div class="image-card">
                                                <div class="image-container">
                                                    <img src="https://placehold.co/300x200/333/FFF?text=Ramz+Profile" alt="Ramz's Analysis">
                                                </div>
                                                <div class="image-annotations">
                                                    <div class="annotation">
                                                        <span class="annotation-text">BMI: 15.8 (Severely Underweight)</span>
                                                        <button class="reclassify-btn">Reclassify</button>
                                                    </div>
                                                    <div class="annotation">
                                                        <span class="annotation-text">Protein: Critical</span>
                                                        <button class="reclassify-btn">Reclassify</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="food-log-panel">
                                        <h3>Food Log Discrepancies</h3>
                                        <div class="discrepancy-list">
                                            <div class="discrepancy-item">
                                                <div class="discrepancy-icon warning">❗️</div>
                                                <div class="discrepancy-content">
                                                    <div class="discrepancy-title">Iron Intake Below Threshold</div>
                                                    <div class="discrepancy-details">
                                                        Iron intake consistently below recommended levels. Current levels at 65% of daily recommended intake.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        <tr class="severe-risk" onclick="toggleUserDetails('user004')">
                            <td>NS-004</td>
                            <td>57</td>
                            <td>F</td>
                            <td>Kitang</td>
                            <td>Calcium Deficiency</td>
                            <td>0.91</td>
                            <td>2023-10-05</td>
                            <td><button class="view-btn">View</button></td>
                        </tr>
                        <tr id="user004-details" class="user-details">
                            <td colspan="7">
                                <div class="detail-panels">
                                    <!-- User details panels would go here -->
                                </div>
                            </td>
                        </tr>
                        
                        <tr class="moderate-risk" onclick="toggleUserDetails('user005')">
                            <td>NS-005</td>
                            <td>39</td>
                            <td>M</td>
                            <td>Poblacion</td>
                            <td>Zinc Deficiency</td>
                            <td>0.58</td>
                            <td>2023-10-01</td>
                            <td><button class="view-btn">View</button></td>
                        </tr>
                        <tr id="user005-details" class="user-details">
                            <td colspan="7">
                                <div class="detail-panels">
                                    <!-- User details panels would go here -->
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="pagination">
                <button class="pagination-btn">Previous</button>
                <div class="page-numbers">
                    <button class="page-number active">1</button>
                    <button class="page-number">2</button>
                    <button class="page-number">3</button>
                    <span>...</span>
                    <button class="page-number">10</button>
                </div>
                <button class="pagination-btn">Next</button>
            </div>
        </div>

        <div class="risk-users" style="border-radius: 20px; padding: 25px; margin-top: 30px;">
            <h2>
                At-Risk Users
                <span class="view-all">View All →</span>
            </h2>
            
            <div class="risk-tabs">
                <div class="risk-tab severe active">Severe Risk <span class="risk-count">6</span></div>
                <div class="risk-tab moderate">Moderate Risk <span class="risk-count">12</span></div>
                <div class="risk-tab low">Low Risk <span class="risk-count">23</span></div>
                    </div>
            
            <div class="users-list">
                <div class="user-item severe">
                    <div class="user-profile">K</div>
                    <div class="user-details">
                        <div class="user-name">
                            Kevin
                            <span class="risk-badge severe-badge">Severe</span>
                        </div>
                        <div class="user-info-row">
                            <div class="user-info-item">
                                BMI: <span class="user-info-value">16.2</span>
                            </div>
                            <div class="user-info-item">
                                Iron: <span class="user-info-value">Low</span>
                            </div>
                            <div class="user-info-item">
                                Protein: <span class="user-info-value">Deficient</span>
                            </div>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="user-action-btn">📊</button>
                        <button class="user-action-btn">✉️</button>
                        <button class="user-action-btn">📑</button>
                    </div>
                </div>
                
                <div class="user-item severe">
                    <div class="user-profile">J</div>
                    <div class="user-details">
                        <div class="user-name">
                            Joshua
                            <span class="risk-badge severe-badge">Severe</span>
                    </div>
                        <div class="user-info-row">
                            <div class="user-info-item">
                                BMI: <span class="user-info-value">17.5</span>
                            </div>
                            <div class="user-info-item">
                                Vit A: <span class="user-info-value">Critical</span>
                            </div>
                            <div class="user-info-item">
                                Calcium: <span class="user-info-value">Low</span>
                            </div>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="user-action-btn">📊</button>
                        <button class="user-action-btn">✉️</button>
                        <button class="user-action-btn">📑</button>
                    </div>
                </div>
                
                <div class="user-item severe">
                    <div class="user-profile">R</div>
                    <div class="user-details">
                        <div class="user-name">
                            Ramz
                            <span class="risk-badge severe-badge">Severe</span>
                    </div>
                        <div class="user-info-row">
                            <div class="user-info-item">
                                BMI: <span class="user-info-value">15.8</span>
                    </div>
                            <div class="user-info-item">
                                Protein: <span class="user-info-value">Critical</span>
                </div>
                            <div class="user-info-item">
                                B12: <span class="user-info-value">Deficient</span>
                            </div>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="user-action-btn">📊</button>
                        <button class="user-action-btn">✉️</button>
                        <button class="user-action-btn">📑</button>
                    </div>
                </div>

                <div class="user-item moderate">
                    <div class="user-profile">M</div>
                    <div class="user-details">
                        <div class="user-name">
                            Maria
                            <span class="risk-badge moderate-badge">Moderate</span>
                        </div>
                        <div class="user-info-row">
                            <div class="user-info-item">
                                BMI: <span class="user-info-value">18.2</span>
                            </div>
                            <div class="user-info-item">
                                Iron: <span class="user-info-value">Low</span>
                            </div>
                            <div class="user-info-item">
                                Calcium: <span class="user-info-value">Deficient</span>
                            </div>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="user-action-btn">📊</button>
                        <button class="user-action-btn">✉️</button>
                        <button class="user-action-btn">📑</button>
                    </div>
                </div>

                <div class="user-item moderate">
                    <div class="user-profile">L</div>
                    <div class="user-details">
                        <div class="user-name">
                            Liza
                            <span class="risk-badge moderate-badge">Moderate</span>
                        </div>
                        <div class="user-info-row">
                            <div class="user-info-item">
                                BMI: <span class="user-info-value">18.5</span>
                            </div>
                            <div class="user-info-item">
                                Protein: <span class="user-info-value">Low</span>
                            </div>
                            <div class="user-info-item">
                                Vit D: <span class="user-info-value">Deficient</span>
                            </div>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="user-action-btn">📊</button>
                        <button class="user-action-btn">✉️</button>
                        <button class="user-action-btn">📑</button>
                    </div>
                </div>

                <div class="user-item low">
                    <div class="user-profile">A</div>
                    <div class="user-details">
                        <div class="user-name">
                            Anna
                            <span class="risk-badge low-badge">Low</span>
                        </div>
                        <div class="user-info-row">
                            <div class="user-info-item">
                                BMI: <span class="user-info-value">19.2</span>
                            </div>
                            <div class="user-info-item">
                                Iron: <span class="user-info-value">Low</span>
                            </div>
                            <div class="user-info-item">
                                B12: <span class="user-info-value">Low</span>
                            </div>
                        </div>
                    </div>
                    <div class="user-actions">
                        <button class="user-action-btn">📊</button>
                        <button class="user-action-btn">✉️</button>
                        <button class="user-action-btn">📑</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="recent-alerts" style="border-radius: 20px; padding: 25px; margin-top: 30px;">
            <h2>
                Recent Alerts
                <span class="view-all">View All →</span>
            </h2>
            
            <div class="alerts-list">
                <div class="alert-item severe">
                    <div class="alert-header">
                        <div class="alert-title">
                            Critical Protein Deficiency
                            <span class="risk-badge severe-badge">Urgent</span>
                        </div>
                        <div class="alert-time">10 minutes ago</div>
                    </div>
                    <div class="alert-content">
                        Kevin's protein levels have fallen below 20% of recommended daily intake for 5 consecutive days. Immediate nutritional intervention recommended.
                    </div>
                    <div class="alert-actions">
                        <button class="alert-btn secondary">Dismiss</button>
                        <button class="alert-btn primary">Send Alert to Healthcare Provider</button>
                    </div>
                </div>
                
                <div class="alert-item moderate">
                    <div class="alert-header">
                        <div class="alert-title">
                            Vitamin D Deficiency Warning
                            <span class="risk-badge moderate-badge">Warning</span>
                        </div>
                        <div class="alert-time">1 hour ago</div>
                    </div>
                    <div class="alert-content">
                        Joshua shows consistent low levels of Vitamin D over the past 2 weeks. Recommended to adjust meal plan and consider supplements.
                    </div>
                    <div class="alert-actions">
                        <button class="alert-btn secondary">Dismiss</button>
                        <button class="alert-btn primary">Send Notification</button>
                    </div>
                </div>
                
                <div class="alert-item low">
                    <div class="alert-header">
                        <div class="alert-title">
                            Iron Intake Below Threshold
                            <span class="risk-badge low-badge">Monitor</span>
                        </div>
                        <div class="alert-time">3 hours ago</div>
                    </div>
                    <div class="alert-content">
                        Ramz's iron intake has been consistently below recommended levels. Current levels are at 65% of daily recommended intake.
                    </div>
                    <div class="alert-actions">
                        <button class="alert-btn secondary">Dismiss</button>
                        <button class="alert-btn primary">Adjust Meal Plan</button>
                    </div>
                </div>
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

    <script>
        // Remove the theme toggle code and replace with this
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

        // Function to toggle user details visibility
        function toggleUserDetails(userId) {
            const detailsRow = document.getElementById(`${userId}-details`);
            
            // Close any other open details first
            document.querySelectorAll('.user-details.active').forEach(row => {
                if (row.id !== `${userId}-details`) {
                    row.classList.remove('active');
                }
            });
            
            // Toggle the clicked details
            detailsRow.classList.toggle('active');
        }
    </script>
</body>
</html>
