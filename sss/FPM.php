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
    <title>NutriSaur - Food Availability & Price Monitoring</title>
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

.price-trends-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .price-trends-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.price-trends-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.price-trends-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.price-controls {
    display: flex;
    gap: 15px;
    align-items: center;
}

.price-filter {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: rgba(161, 180, 84, 0.1);
    padding: 8px 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.price-filter:hover {
    background-color: rgba(161, 180, 84, 0.2);
}

.price-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.price-chart {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 20px;
    height: 350px;
}

.light-theme .price-chart {
    background-color: rgba(234, 240, 220, 0.7);
}

.price-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.price-chart-title {
    font-size: 16px;
    font-weight: 500;
}

.price-chart-legend {
    display: flex;
    gap: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.price-chart-placeholder {
    height: 280px;
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    opacity: 0.7;
}

.predictions-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .predictions-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.predictions-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.predictions-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.predictions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.prediction-card {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 20px;
    display: flex;
    flex-direction: column;
}

.light-theme .prediction-card {
    background-color: rgba(234, 240, 220, 0.7);
}

.prediction-card.warning {
    border-left: 4px solid var(--color-warning);
}

.prediction-card.danger {
    border-left: 4px solid var(--color-danger);
}

.prediction-card.normal {
    border-left: 4px solid var(--color-highlight);
}

.prediction-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.prediction-title {
    font-size: 16px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.prediction-badge {
    font-size: 12px;
    padding: 3px 8px;
    border-radius: 4px;
}

.badge-warning {
    background-color: var(--color-warning);
    color: #333;
}

.light-theme .badge-warning {
    color: white;
}

.badge-danger {
    background-color: var(--color-danger);
    color: white;
}

.badge-normal {
    background-color: var(--color-highlight);
    color: white;
}

.prediction-date {
    font-size: 12px;
    opacity: 0.7;
}

.prediction-content {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 15px;
}

.prediction-stats {
    display: flex;
    gap: 15px;
    margin: 15px 0;
}

.prediction-stat {
    flex: 1;
}

.stat-label {
    font-size: 12px;
    opacity: 0.7;
}

.stat-value {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-highlight);
}

.stat-value.up {
    color: var(--color-danger);
}

.stat-value.down {
    color: var(--color-accent3);
}

.stat-trend {
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.recommendations-container {
    background-color: var(--color-card);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 30px;
}

.light-theme .recommendations-container {
    border: none;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.04);
}

.recommendations-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.recommendations-title {
    font-size: 20px;
    color: var(--color-highlight);
}

.add-note-btn {
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

.light-theme .add-note-btn {
    color: white;
}

.add-note-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
}

.notes-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.note-item {
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 15px;
    padding: 20px;
    position: relative;
}

.light-theme .note-item {
    background-color: rgba(234, 240, 220, 0.7);
}

.note-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.note-author {
    display: flex;
    align-items: center;
    gap: 10px;
}

.author-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background-color: var(--color-accent3);
    display: flex;
    justify-content: center;
    align-items: center;
    color: var(--color-text);
    font-weight: bold;
    font-size: 14px;
}

.light-theme .author-avatar {
    color: white;
}

.author-name {
    font-weight: 500;
}

.note-date {
    font-size: 12px;
    opacity: 0.7;
}

.note-content {
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.note-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.note-tag {
    font-size: 12px;
    padding: 3px 10px;
    border-radius: 15px;
    background-color: rgba(161, 180, 84, 0.2);
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

    .feature-grid, .price-grid {
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

/* New styles for Limay Food Availability */
.alerts-container {
    background-color: var(--color-card);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.alert-item.critical {
    background-color: rgba(207, 134, 134, 0.2);
    border-left: 4px solid var(--color-danger);
}

.alert-icon {
    font-size: 24px;
}

.alert-title {
    font-weight: 600;
    color: var(--color-danger);
    margin-bottom: 5px;
}

.alert-details {
    font-size: 14px;
    opacity: 0.9;
}

.section-title {
    font-size: 20px;
    color: var(--color-highlight);
    margin-bottom: 20px;
    font-weight: 600;
}

.food-status-container {
    background-color: var(--color-card);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.food-status-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.food-status-table th,
.food-status-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid rgba(161, 180, 84, 0.1);
}

.food-status-table th {
    font-weight: 500;
    color: var(--color-highlight);
    background-color: rgba(161, 180, 84, 0.1);
}

.food-status-table tr:hover {
    background-color: rgba(161, 180, 84, 0.05);
}

.stock-low {
    color: var(--color-warning);
    font-weight: 500;
}

.stock-adequate {
    color: var(--color-accent3);
    font-weight: 500;
}

.trend-up {
    color: var(--color-danger);
    font-weight: 500;
}

.trend-down {
    color: var(--color-accent3);
    font-weight: 500;
}

.trend-stable {
    color: var(--color-highlight);
    font-weight: 500;
}

.map-container {
    background-color: var(--color-card);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.map-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.map-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background-color: rgba(42, 51, 38, 0.7);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.light-theme .map-item {
    background-color: rgba(234, 240, 220, 0.7);
}

.map-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.map-icon {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background-color: rgba(161, 180, 84, 0.1);
}

.map-icon.critical {
    color: var(--color-danger);
}

.map-icon.low {
    color: var(--color-warning);
}

.map-icon.adequate {
    color: var(--color-accent3);
}

.map-label {
    font-weight: 500;
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    justify-content: center;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    background-color: var(--color-highlight);
    color: var(--color-text);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.light-theme .action-btn {
    color: white;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.btn-icon {
    font-size: 18px;
}

@media (max-width: 768px) {
    .map-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
}
    </style>
</head>
<body class="dark-theme">
    <div class="dashboard">
        <header>
            <div class="logo">
                <h1>Food Availability</h1>
                        </div>
            <div class="user-info">
                <div class="user-avatar"><?php echo htmlspecialchars(substr($username, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </header>

        <div class="alerts-container">
            <div class="alert-item critical">
                <span class="alert-icon">⚠️</span>
                <div class="alert-content">
                    <div class="alert-title">CRITICAL: Rice supply in Lamao</div>
                    <div class="alert-details">3 days remaining stock - Contact NFA immediately</div>
                    </div>
                    </div>
                </div>
                
        <div class="food-status-container">
            <h2 class="section-title">Staple Food Status</h2>
            <table class="food-status-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Current Price (₱)</th>
                        <th>Stock Level</th>
                        <th>Price Trend</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Rice</td>
                        <td>45/kg</td>
                        <td class="stock-low">Low Stock 📉</td>
                        <td class="trend-up">+12% vs May</td>
                    </tr>
                    <tr>
                        <td>Eggs</td>
                        <td>8/pc</td>
                        <td class="stock-adequate">Adequate</td>
                        <td class="trend-stable">Stable</td>
                    </tr>
                    <tr>
                        <td>Milkfish</td>
                        <td>120/kg</td>
                        <td class="stock-adequate">Adequate</td>
                        <td class="trend-down">-5% vs May</td>
                    </tr>
                    <tr>
                        <td>Bananas</td>
                        <td>25/kg</td>
                        <td class="stock-adequate">Adequate</td>
                        <td class="trend-stable">Stable</td>
                    </tr>
                    <tr>
                        <td>Leafy Greens</td>
                        <td>35/kg</td>
                        <td class="stock-low">Low Stock 📉</td>
                        <td class="trend-up">+8% vs May</td>
                    </tr>
                </tbody>
            </table>
                </div>
                
        <div class="map-container">
            <h2 class="section-title">Barangay Availability Map</h2>
            <div class="map-grid">
                <div class="map-item">
                    <div class="map-icon critical">🔴</div>
                    <div class="map-label">Lamao - Rice</div>
                        </div>
                <div class="map-item">
                    <div class="map-icon adequate">🟢</div>
                    <div class="map-label">Town Proper - Eggs</div>
                    </div>
                <div class="map-item">
                    <div class="map-icon adequate">🟢</div>
                    <div class="map-label">Alangan - Bananas</div>
                    </div>
                <div class="map-item">
                    <div class="map-icon low">🟠</div>
                    <div class="map-label">Wawa - Leafy Greens</div>
                    </div>
                </div>
            </div>

        <div class="action-buttons">
            <button class="action-btn">
                <span class="btn-icon">📞</span>
                <span>Contact Supplier</span>
            </button>
            <button class="action-btn">
                <span class="btn-icon">📊</span>
                <span>Update Price Data</span>
            </button>
            <button class="action-btn">
                <span class="btn-icon">🖨️</span>
                <span>Print Weekly Report</span>
            </button>
        </div>
    </div>
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
        // Remove the theme toggle event listener and keep only the theme loading code
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

        // Load saved theme on page load
        window.addEventListener('DOMContentLoaded', () => {
            loadSavedTheme();
        });
    </script>
</body>
</html>
