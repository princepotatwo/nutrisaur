<?php
// CONFIGURATION TEMPLATE - Copy this to config.php and fill in your values
// DO NOT commit config.php to GitHub (it's in .gitignore)

// Database configuration
$host = "localhost";           // Change to your production host
$dbname = "nutrisaur_db";      // Change to your production database name
$dbUsername = "root";          // Change to your production username
$dbPassword = "";              // Change to your production password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $dbUsername, $dbPassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}

// Define base URL for the application
// For local development (XAMPP)
$base_url = "http://localhost/thesis355/";

// For production deployment, change this line:
// $base_url = "https://yourdomain.com/";

// API base URL
$api_base_url = $base_url . "api/";

// Web base URL  
$web_base_url = $base_url . "web/";
?>
