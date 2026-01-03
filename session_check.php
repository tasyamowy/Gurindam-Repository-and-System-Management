<?php
// session_check.php - Include this at the top of all admin pages
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Store the requested URL for redirecting back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Redirect to login page
    header("Location: admin_login.php");
    exit();
}

// Verify user has admin role (added for role-based system)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_destroy();
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: admin_login.php");
    exit();
}

// Get admin ID from session
$admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;

// If no admin ID, logout
if ($admin_id === 0) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
?>