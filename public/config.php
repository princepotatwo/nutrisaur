<?php
/**
 * Nutrisaur Configuration File
 * Railway Production Environment
 * 
 * This file now uses the unified DatabaseAPI for all database operations
 */

// Application Configuration
define('APP_NAME', 'Nutrisaur');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'production');

// Base URL for production
$base_url = 'https://nutrisaur-production.up.railway.app/';

// Error reporting for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session configuration for Railway
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600); // 1 hour
ini_set('session.cookie_lifetime', 3600); // 1 hour

// ========================================
// UNIFIED DATABASE CONNECTION FUNCTIONS
// ========================================

// Include the unified DatabaseAPI
require_once __DIR__ . "/api/DatabaseAPI.php";

// Database connection function - now uses unified API
function getDatabaseConnection() {
    $db = new DatabaseAPI();
    return $db->getPDO();
}

// Legacy mysqli connection for backward compatibility
function getMysqliConnection() {
    $db = new DatabaseAPI();
    return $db->getMysqli();
}

// Test database connection
function testDatabaseConnection() {
    $db = new DatabaseAPI();
    return $db->testConnection();
}

// Debug function to show current database configuration
function getDatabaseConfig() {
    $db = new DatabaseAPI();
    return $db->getDatabaseConfig();
}
?>
