<?php
// Headers are now set by the router
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'logged_in' => true,
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'is_admin' => $_SESSION['is_admin'] ?? false
    ]);
} else {
    echo json_encode([
        'success' => false,
        'logged_in' => false,
        'message' => 'User not logged in'
    ]);
}
?> 