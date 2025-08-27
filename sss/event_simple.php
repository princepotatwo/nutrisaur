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
    <title>Nutrition Events - NutriSaur</title>
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
        
        .event-card { background: #2A3326; padding: 20px; border-radius: 12px; margin-bottom: 20px; border-left: 4px solid #A1B454; }
        .event-title { color: #A1B454; font-size: 1.2em; margin-bottom: 10px; }
        .event-description { color: #B5C88D; margin-bottom: 15px; }
        .event-meta { color: #8CA86E; font-size: 0.9em; }
        
        .btn { background: #A1B454; color: #1A211A; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; transition: background 0.3s; margin-right: 10px; }
        .btn:hover { background: #8CA86E; }
    </style>
</head>
<body>
    <div class="navbar">
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
            <h1>Nutrition Event Notifications</h1>
            <p>Manage and send nutrition-related notifications to users</p>
        </div>
        
        <div class="event-card">
            <div class="event-title">Sample Nutrition Workshop</div>
            <div class="event-description">
                Join us for a comprehensive nutrition workshop covering healthy eating habits, 
                meal planning, and dietary guidelines for different age groups.
            </div>
            <div class="event-meta">
                <strong>Date:</strong> September 15, 2024<br>
                <strong>Location:</strong> Community Health Center<br>
                <strong>Target:</strong> All registered users
            </div>
            <div style="margin-top: 15px;">
                <button class="btn">Send Notification</button>
                <button class="btn">Edit Event</button>
                <button class="btn">View Responses</button>
            </div>
        </div>
        
        <div class="event-card">
            <div class="event-title">Nutrition Screening Day</div>
            <div class="event-description">
                Free nutrition screening for children and adults. Includes BMI calculation, 
                dietary assessment, and personalized nutrition advice.
            </div>
            <div class="event-meta">
                <strong>Date:</strong> September 20, 2024<br>
                <strong>Location:</strong> Various Barangay Centers<br>
                <strong>Target:</strong> High-risk communities
            </div>
            <div style="margin-top: 15px;">
                <button class="btn">Send Notification</button>
                <button class="btn">Edit Event</button>
                <button class="btn">View Responses</button>
            </div>
        </div>
        
        <div class="event-card">
            <div class="event-title">Healthy Cooking Class</div>
            <div class="event-description">
                Learn to prepare nutritious meals on a budget. Hands-on cooking session 
                with local ingredients and traditional Filipino recipes.
            </div>
            <div class="event-meta">
                <strong>Date:</strong> September 25, 2024<br>
                <strong>Location:</strong> Community Kitchen<br>
                <strong>Target:</strong> Parents and caregivers
            </div>
            <div style="margin-top: 15px;">
                <button class="btn">Send Notification</button>
                <button class="btn">Edit Event</button>
                <button class="btn">View Responses</button>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <button class="btn" style="background: #4CAF50;">Create New Event</button>
            <button class="btn" style="background: #2196F3;">Import Events</button>
            <button class="btn" style="background: #FF9800;">Test Notifications</button>
        </div>
    </div>
</body>
</html>
