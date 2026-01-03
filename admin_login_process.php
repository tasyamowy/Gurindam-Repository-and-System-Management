<?php
session_start();

// Include connection
require_once "connection.php";

// Check connection
if (!$conn) {
    $_SESSION['error'] = "Database connection failed. Please contact administrator.";
    header("Location: admin_login.php");
    exit();
}

// Validate input
if (empty($_POST['email']) || empty($_POST['password'])) {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: admin_login.php");
    exit();
}

$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = $_POST['password'];

// FIRST: Try new role-based system (if role column exists)
$sql = "SELECT * FROM users WHERE email = ? AND role = 'admin'";
$stmt = mysqli_prepare($conn, $sql);
$use_role_system = false;

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        $use_role_system = true;
    } else {
        // User not found in role system, fall back to old system
        mysqli_stmt_close($stmt);
        $stmt = null;
    }
}

// SECOND: If role system didn't find user, try old system
if (!$use_role_system) {
    $sql = "SELECT u.* FROM users u 
            INNER JOIN admins a ON u.userID = a.adminID 
            WHERE u.email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        $_SESSION['error'] = "Database error. Please try again.";
        header("Location: admin_login.php");
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
    } else {
        // Check if user exists but is not an admin
        $check_user_sql = "SELECT * FROM users WHERE email = ?";
        $check_user_stmt = mysqli_prepare($conn, $check_user_sql);
        mysqli_stmt_bind_param($check_user_stmt, "s", $email);
        mysqli_stmt_execute($check_user_stmt);
        $user_result = mysqli_stmt_get_result($check_user_stmt);
        
        if (mysqli_num_rows($user_result) === 1) {
            $_SESSION['error'] = "Access denied. Admin privileges required.";
        } else {
            $_SESSION['error'] = "Email not found";
        }
        
        header("Location: admin_login.php");
        exit();
    }
}

// At this point, $user should contain valid admin user data
// Plain-text password check
if ($password === $user['password']) {
    // Get admin details
    $admin_sql = "SELECT position, hireDate FROM admins WHERE adminID = ?";
    $admin_stmt = mysqli_prepare($conn, $admin_sql);
    mysqli_stmt_bind_param($admin_stmt, "i", $user['userID']);
    mysqli_stmt_execute($admin_stmt);
    $admin_result = mysqli_stmt_get_result($admin_stmt);
    $admin_info = mysqli_fetch_assoc($admin_result);
    
    // Set session variables
    $_SESSION['userID'] = $user['userID'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['admin_id'] = $user['userID'];
    $_SESSION['position'] = $admin_info['position'] ?? 'Administrator';
    $_SESSION['hireDate'] = $admin_info['hireDate'] ?? date('Y-m-d');
    $_SESSION['admin_logged_in'] = true;
    
    // Add role to session if available in database
    if (isset($user['role'])) {
        $_SESSION['role'] = $user['role'];
    } else {
        // If using old system without role column, set default admin role
        $_SESSION['role'] = 'admin';
    }
    
    // Update last login timestamp (optional)
    if (isset($user['lastLogin'])) {
        // If you have lastLogin column
        $update_sql = "UPDATE users SET lastLogin = NOW() WHERE userID = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, "i", $user['userID']);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);
    }
    
    // Redirect to admin dashboard
    if (isset($_SESSION['redirect_url'])) {
        $redirect_url = $_SESSION['redirect_url'];
        unset($_SESSION['redirect_url']);
        header("Location: " . $redirect_url);
    } else {
        header("Location: dashboard.php");
    }
    exit();
} else {
    $_SESSION['error'] = "Incorrect password";
    header("Location: admin_login.php");
    exit();
}

// Clean up
if (isset($stmt)) mysqli_stmt_close($stmt);
if (isset($admin_stmt)) mysqli_stmt_close($admin_stmt);
if (isset($check_user_stmt)) mysqli_stmt_close($check_user_stmt);
mysqli_close($conn);
?>z