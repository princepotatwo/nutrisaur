<?php
/**
 * Check Session API
 * Returns session status for the frontend
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);

if ($isLoggedIn) {
    $response = [
        'success' => true,
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'] ?? null,
        'admin_id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'is_admin' => $_SESSION['is_admin'] ?? false
    ];
} else {
    $response = [
        'success' => true,
        'logged_in' => false,
        'user_id' => null,
        'admin_id' => null,
        'username' => null,
        'email' => null,
        'is_admin' => false
    ];
}

// Return JSON response
echo json_encode($response);
?>
