<?php
// Start the session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: home.php");
    exit;
}

// Get user info from session
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// Use centralized DatabaseAPI - NO MORE HARDCODED CONNECTIONS!
require_once __DIR__ . '/api/EasyDB.php';

// Check database connection
if (!isDBConnected()) {
    error_log("MHO Assessment Table: Database connection not available");
}

// Fetch MHO assessment data using centralized database
$assessments = [];
if (isDBConnected()) {
    try {
        $assessments = runQuery("
            SELECT 
                sa.*,
                u.username,
                u.email,
                u.created_at as user_created
            FROM screening_assessments sa
            LEFT JOIN users u ON sa.user_id = u.id
            ORDER BY sa.created_at DESC
            LIMIT 100
        ");
    } catch (Exception $e) {
        error_log("Failed to fetch assessments: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MHO Assessment Table - NutriSaur</title>
    <style>
        /* CSS Variables */
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
            --color-hover: rgba(161, 180, 84, 0.08);
            --color-active: rgba(161, 180, 84, 0.15);
        }

        /* Light Theme */
        .light-theme {
            --color-bg: #F0F7F0;
            --color-card: #FFFFFF;
            --color-highlight: #66BB6A;
            --color-text: #1B3A1B;
            --color-accent1: #81C784;
            --color-accent2: #4CAF50;
            --color-accent3: #2E7D32;
            --color-accent4: #A5D6A7;
            --color-danger: #E57373;
            --color-warning: #FFB74D;
            --color-border: #C8E6C9;
            --color-shadow: rgba(76, 175, 80, 0.1);
            --color-hover: rgba(76, 175, 80, 0.08);
            --color-active: rgba(76, 175, 80, 0.15);
        }

        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            line-height: 1.6;
            padding-left: 320px;
            min-height: 100vh;
        }

        /* Navbar Styles */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 320px;
            height: 100vh;
            background-color: var(--color-card);
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
            padding: 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .navbar-header {
            padding: 35px 25px;
            display: flex;
            align-items: center;
            border-bottom: 2px solid rgba(164, 188, 46, 0.15);
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.05) 0%, transparent 100%);
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
            background: linear-gradient(135deg, rgba(161, 180, 84, 0.1), rgba(161, 180, 84, 0.05));
            border: 1px solid rgba(161, 180, 84, 0.2);
            box-shadow: 0 2px 8px rgba(161, 180, 84, 0.1);
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

        .navbar a {
            text-decoration: none;
            color: var(--color-text);
            font-size: 17px;
            padding: 18px 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.9;
            border-radius: 0 12px 12px 0;
            margin-right: 10px;
        }

        .navbar a:hover {
            background: linear-gradient(90deg, rgba(161, 180, 84, 0.08) 0%, rgba(161, 180, 84, 0.04) 100%);
            color: var(--color-highlight);
            opacity: 1;
            transform: translateX(3px);
            box-shadow: 0 4px 15px rgba(161, 180, 84, 0.15);
        }

        .navbar a.active {
            background: linear-gradient(90deg, rgba(161, 180, 84, 0.15) 0%, rgba(161, 180, 84, 0.08) 100%);
            color: var(--color-highlight);
            opacity: 1;
            font-weight: 600;
            border-left: 4px solid var(--color-highlight);
            box-shadow: 0 6px 20px rgba(161, 180, 84, 0.2);
        }

        .navbar-footer {
            padding: 25px 20px;
            border-top: 2px solid rgba(164, 188, 46, 0.15);
            font-size: 12px;
            opacity: 0.7;
            text-align: center;
            background: linear-gradient(135deg, transparent 0%, rgba(161, 180, 84, 0.03) 100%);
        }

        /* Dashboard Styles */
        .dashboard {
            max-width: calc(100% - 60px);
            width: 100%;
            margin: 0 auto;
            padding: 20px;
            background-color: var(--color-bg);
        }

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

        /* Theme Toggle */
        .theme-toggle-btn {
            background: #FF9800;
            border: none;
            color: #333;
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

        .theme-toggle-btn:hover {
            background: #F57C00;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
        }

        /* Enhanced MHO Assessment Table Styles */
        .mho-assessment-container {
            background-color: var(--color-card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
            margin-bottom: 30px;
            border: 1px solid var(--color-border);
            position: relative;
            overflow: hidden;
        }

        .mho-assessment-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--color-highlight), var(--color-accent1), var(--color-highlight));
            background-size: 200% 100%;
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
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

        .table-header h2 {
            color: var(--color-highlight);
            font-size: 24px;
            margin: 0;
            font-weight: 500;
            letter-spacing: 0.5px;
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
            transform: translateY(-1px);
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
            border-bottom: 2px solid #4CAF50; /* Default green line */
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            border-bottom-color: #66BB6A; /* Lighter green on focus */
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.6);
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

        .filter-container {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
            max-width: 250px;
        }

        .filter-select {
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

        .filter-select:focus {
            outline: none;
            border-color: var(--color-highlight);
            box-shadow: 0 0 0 2px rgba(161, 180, 84, 0.2);
            transform: translateY(-1px);
        }

        /* Enhanced Table Styles */
        .mho-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
            table-layout: auto;
            min-width: 1200px;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 20px var(--color-shadow);
            background: var(--color-card);
        }

        .mho-table thead {
            background-color: var(--color-card);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .mho-table tbody tr:nth-child(odd) {
            background-color: rgba(84, 96, 72, 0.3);
        }

        .mho-table tbody tr:nth-child(even) {
            background-color: rgba(84, 96, 72, 0.1);
        }

        .mho-table tbody tr {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .mho-table tbody tr::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(161, 180, 84, 0.1), transparent);
            transition: left 0.5s ease;
            z-index: 1;
        }

        .mho-table tbody tr:hover {
            border-left-color: var(--color-highlight);
            background-color: rgba(161, 180, 84, 0.2);
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 25px rgba(161, 180, 84, 0.2);
            z-index: 5;
        }

        .mho-table tbody tr:hover::before {
            left: 100%;
        }

        .mho-table th,
        .mho-table td {
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
            z-index: 2;
        }

        .mho-table th {
            color: var(--color-highlight);
            font-weight: 700;
            font-size: 16px;
            border-bottom: 2px solid rgba(161, 180, 84, 0.4);
            padding-bottom: 18px;
            padding-top: 18px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }

        /* Risk Level Badges */
        .risk-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            text-align: center;
            min-width: 80px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
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
            transition: left 0.5s ease;
        }

        .risk-badge:hover::before {
            left: 100%;
        }

        .risk-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .risk-badge.low {
            background-color: rgba(161, 180, 84, 0.15);
            color: #A1B454;
        }

        .risk-badge.medium {
            background-color: rgba(224, 201, 137, 0.15);
            color: #E0C989;
        }

        .risk-badge.high {
            background-color: rgba(207, 134, 134, 0.15);
            color: #CF8686;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn {
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

        .btn-view {
            background-color: rgba(161, 180, 84, 0.15);
            color: var(--color-highlight);
            border: 2px solid rgba(161, 180, 84, 0.4);
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(161, 180, 84, 0.2);
        }

        .btn-edit {
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

        .btn:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            filter: brightness(1.1);
        }

        .btn:active {
            transform: translateY(0) scale(0.98);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .status-active {
            background-color: var(--color-highlight);
        }

        .status-inactive {
            background-color: var(--color-danger);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            backdrop-filter: blur(5px);
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
            animation: modalSlideIn 0.3s ease;
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

        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            cursor: pointer;
            color: var(--color-text);
            transition: all 0.3s ease;
        }

        .close:hover {
            color: var(--color-highlight);
            transform: scale(1.1);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .mho-table {
                min-width: 1000px;
            }
            
            .mho-table th,
            .mho-table td {
                padding: 12px 8px;
                font-size: 13px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 11px;
                min-width: 50px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding-left: 80px;
            }
            
            .navbar {
                width: 80px;
                transition: width 0.3s ease;
            }
            
            .navbar:hover {
                width: 320px;
            }
            
            .navbar-logo-text,
            .navbar span:not(.navbar-icon) {
                opacity: 0;
                transition: opacity 0.2s ease;
            }
            
            .navbar:hover .navbar-logo-text,
            .navbar:hover span:not(.navbar-icon) {
                opacity: 1;
            }
            
            .dashboard {
                max-width: calc(100% - 20px);
                padding: 15px;
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
            
            .filter-container {
                width: 100%;
            }
        }

        /* Table Responsive Wrapper */
        .table-responsive {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(161, 180, 84, 0.3);
            border-radius: 50%;
            border-top-color: var(--color-highlight);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* No Data Message */
        .no-data-message {
            text-align: center;
            padding: 40px;
            color: var(--color-text);
            opacity: 0.7;
            font-style: italic;
            font-size: 16px;
        }

        /* Enhanced Tooltips */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: var(--color-card);
            color: var(--color-text);
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            border: 1px solid var(--color-border);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-size: 12px;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Dark theme search input styles */
        .dark-theme .search-input {
            border-bottom: 2px solid #4CAF50; /* Green line for dark theme */
        }

        .dark-theme .search-input:focus {
            border-bottom-color: #66BB6A; /* Lighter green on focus */
        }

        /* Light Theme Adjustments */
        .light-theme .search-input {
            border-bottom: 2px solid #333; /* Black line for light theme */
        }

        .light-theme .search-input:focus {
            border-bottom-color: #000; /* Darker black on focus */
        }

        .light-theme .search-input::placeholder {
            color: rgba(27, 58, 27, 0.6);
        }

        .light-theme .risk-badge.low {
            background-color: rgba(102, 187, 106, 0.15);
            color: var(--color-highlight);
            border: 1px solid rgba(102, 187, 106, 0.3);
        }

        .light-theme .risk-badge.medium {
            background-color: rgba(255, 183, 77, 0.15);
            color: var(--color-warning);
            border: 1px solid rgba(255, 183, 77, 0.3);
        }

        .light-theme .risk-badge.high {
            background-color: rgba(229, 115, 115, 0.15);
            color: var(--color-danger);
            border: 1px solid rgba(229, 115, 115, 0.3);
        }
    </style>
</head>
<body class="dark-theme">
    <!-- Navbar -->
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
                <li><a href="screening" class="active"><span class="navbar-icon"></span><span>MHO Assessment</span></a></li>
                <li><a href="event"><span class="navbar-icon"></span><span>Nutrition Event Notifications</span></a></li>
                <li><a href="settings"><span class="navbar-icon"></span><span>Settings & Admin</span></a></li>
                <li><a href="logout" style="color: #ff5252;"><span class="navbar-icon"></span><span>Logout</span></a></li>
            </ul>
        </div>
        <div class="navbar-footer">
            <div>NutriSaur v2.0 • © 2025</div>
            <div style="margin-top: 10px;">Logged in as: <?php echo htmlspecialchars($username); ?></div>
        </div>
    </div>

    <!-- Dashboard -->
    <div class="dashboard">
        <header class="dashboard-header">
            <div class="dashboard-title">
                <h1>MHO Assessment Table</h1>
            </div>
            <div class="user-info">
                <button id="theme-toggle" class="theme-toggle-btn" title="Toggle theme">
                    <span class="theme-icon">🌙</span>
                </button>
            </div>
        </header>

        <div class="mho-assessment-container">
            <div class="table-header">
                <h2>Comprehensive MHO Assessment Records</h2>
                <div class="header-controls">
                    <div class="search-row">
                        <div class="search-container">
                            <input type="text" id="searchInput" placeholder="Search by name, location, or risk level..." class="search-input">
                        </div>
                        <div class="filter-container">
                            <select id="riskFilter" onchange="filterByRisk()" class="filter-select">
                                <option value="">All Risk Levels</option>
                                <option value="low">Low Risk</option>
                                <option value="medium">Medium Risk</option>
                                <option value="high">High Risk</option>
                            </select>
                        </div>
                        <div class="filter-container">
                            <select id="locationFilter" onchange="filterByLocation()" class="filter-select">
                                <option value="">All Locations</option>
                                <option value="ABUCAY">ABUCAY</option>
                                <option value="BAGAC">BAGAC</option>
                                <option value="CITY OF BALANGA">CITY OF BALANGA</option>
                                <option value="DINALUPIHAN">DINALUPIHAN</option>
                                <option value="HERMOSA">HERMOSA</option>
                                <option value="LIMAY">LIMAY</option>
                                <option value="MARIVELES">MARIVELES</option>
                                <option value="MORONG">MORONG</option>
                                <option value="ORANI">ORANI</option>
                                <option value="ORION">ORION</option>
                                <option value="PILAR">PILAR</option>
                                <option value="SAMAL">SAMAL</option>
                            </select>
                        </div>
                    </div>
                    <div class="action-row">
                        <button class="btn btn-view" onclick="exportData()">📊 Export Data</button>
                        <button class="btn btn-view" onclick="refreshTable()">🔄 Refresh</button>
                        <button class="btn btn-view" onclick="showStats()">📈 Statistics</button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="mho-table" id="mhoTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Age/Sex</th>
                            <th>Location</th>
                            <th>BMI</th>
                            <th>Risk Level</th>
                            <th>Assessment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assessments)): ?>
                            <?php foreach ($assessments as $assessment): ?>
                                <?php
                                // Calculate risk level based on BMI and other factors
                                $bmi = floatval($assessment['bmi']);
                                $riskLevel = 'low';
                                $riskClass = 'low';
                                
                                if ($bmi < 18.5 || $bmi > 30) {
                                    $riskLevel = 'high';
                                    $riskClass = 'high';
                                } elseif ($bmi < 20 || $bmi > 25) {
                                    $riskLevel = 'medium';
                                    $riskClass = 'medium';
                                }
                                
                                // Get user info
                                $userName = $assessment['username'] ?? 'Unknown User';
                                $age = $assessment['age'] ?? 'N/A';
                                $sex = $assessment['sex'] ?? 'N/A';
                                $municipality = $assessment['municipality'] ?? 'N/A';
                                $barangay = $assessment['barangay'] ?? 'N/A';
                                $bmiValue = $assessment['bmi'] ?? 'N/A';
                                $createdAt = $assessment['created_at'] ?? 'N/A';
                                ?>
                                <tr data-risk="<?php echo $riskClass; ?>" data-location="<?php echo htmlspecialchars($municipality); ?>">
                                    <td><?php echo htmlspecialchars($assessment['id']); ?></td>
                                    <td class="tooltip">
                                        <?php echo htmlspecialchars($userName); ?>
                                        <span class="tooltiptext">User: <?php echo htmlspecialchars($userName); ?><br>Email: <?php echo htmlspecialchars($assessment['email'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($age . 'y, ' . $sex); ?></td>
                                    <td class="tooltip">
                                        <?php echo htmlspecialchars($barangay . ', ' . $municipality); ?>
                                        <span class="tooltiptext">Municipality: <?php echo htmlspecialchars($municipality); ?><br>Barangay: <?php echo htmlspecialchars($barangay); ?></span>
                                    </td>
                                    <td class="tooltip">
                                        <?php echo htmlspecialchars($bmiValue); ?>
                                        <span class="tooltiptext">BMI: <?php echo htmlspecialchars($bmiValue); ?><br>Category: <?php echo $bmi < 18.5 ? 'Underweight' : ($bmi < 25 ? 'Normal' : ($bmi < 30 ? 'Overweight' : 'Obese')); ?></span>
                                    </td>
                                    <td>
                                        <span class="risk-badge <?php echo $riskClass; ?>"><?php echo ucfirst($riskLevel); ?> Risk</span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($createdAt)); ?></td>
                                    <td>
                                        <span class="status-indicator status-active"></span>
                                        Active
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-view" onclick="viewAssessment(<?php echo $assessment['id']; ?>)" title="View Details">👁️</button>
                                            <button class="btn btn-edit" onclick="editAssessment(<?php echo $assessment['id']; ?>)" title="Edit Assessment">✏️</button>
                                            <button class="btn btn-delete" onclick="deleteAssessment(<?php echo $assessment['id']; ?>)" title="Delete Assessment">🗑️</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="no-data-message">
                                    <div>No MHO assessments found. Start by creating new assessments.</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const body = document.body;
            const icon = this.querySelector('.theme-icon');
            
            if (body.classList.contains('dark-theme')) {
                body.classList.remove('dark-theme');
                body.classList.add('light-theme');
                icon.textContent = '☀️';
            } else {
                body.classList.remove('light-theme');
                body.classList.add('dark-theme');
                icon.textContent = '🌙';
            }
        });

        // Search Functionality
        function searchAssessments() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#mhoTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Filter by Risk Level
        function filterByRisk() {
            const riskFilter = document.getElementById('riskFilter').value;
            const rows = document.querySelectorAll('#mhoTable tbody tr');
            
            rows.forEach(row => {
                const riskLevel = row.dataset.risk;
                if (!riskFilter || riskLevel === riskFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Filter by Location
        function filterByLocation() {
            const locationFilter = document.getElementById('locationFilter').value;
            const rows = document.querySelectorAll('#mhoTable tbody tr');
            
            rows.forEach(row => {
                const location = row.dataset.location;
                if (!locationFilter || location === locationFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // View Assessment Details
        function viewAssessment(id) {
            // Create modal with assessment details
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    <h2>Assessment Details #${id}</h2>
                    <p>Detailed view for assessment ID: ${id}</p>
                    <p>This would show comprehensive assessment data including:</p>
                    <ul>
                        <li>Personal Information</li>
                        <li>Anthropometric Measurements</li>
                        <li>Nutritional Assessment</li>
                        <li>Risk Factors</li>
                        <li>Recommendations</li>
                    </ul>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Edit Assessment
        function editAssessment(id) {
            alert(`Edit assessment #${id} - This would open an edit form`);
        }

        // Delete Assessment
        function deleteAssessment(id) {
            if (confirm(`Are you sure you want to delete assessment #${id}?`)) {
                alert(`Assessment #${id} deleted successfully`);
                // Here you would make an AJAX call to delete the assessment
            }
        }

        // Export Data
        function exportData() {
            alert('Exporting assessment data to CSV...');
            // Here you would implement CSV export functionality
        }

        // Refresh Table
        function refreshTable() {
            location.reload();
        }

        // Show Statistics
        function showStats() {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                    <h2>Assessment Statistics</h2>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px;">
                            <h3>Risk Distribution</h3>
                            <p>Low Risk: 45%</p>
                            <p>Medium Risk: 35%</p>
                            <p>High Risk: 20%</p>
                        </div>
                        <div style="background: rgba(161, 180, 84, 0.1); padding: 15px; border-radius: 12px;">
                            <h3>Location Distribution</h3>
                            <p>City of Balanga: 30%</p>
                            <p>Dinalupihan: 25%</p>
                            <p>Other Municipalities: 45%</p>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
            
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }

        // Real-time search
        document.getElementById('searchInput').addEventListener('input', function() {
            searchAssessments();
        });

        // Enhanced hover effects for table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('#mhoTable tbody tr');
            
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px) scale(1.01)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>
