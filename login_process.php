<?php
session_start();

// Include connection
require_once "reader-connection.php";

// Check connection
if (!$conn) {
    $_SESSION['error'] = "Database connection failed. Please contact administrator.";
    header("Location: login.php");
    exit();
}

// Validate input
if (empty($_POST['email']) || empty($_POST['password'])) {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: login.php");
    exit();
}

$email = mysqli_real_escape_string($conn, $_POST['email']);
$password = $_POST['password'];
$requested_role = isset($_POST['role']) ? $_POST['role'] : 'reader'; // Default to reader if not specified

// Only allow reader or contributor roles
if (!in_array($requested_role, ['reader', 'contributor'])) {
    $_SESSION['error'] = "Invalid role selected.";
    header("Location: login.php");
    exit();
}

// Check if user exists and verify role
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    $_SESSION['error'] = "Database error. Please try again.";
    header("Location: login.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);
    
    // Plain-text password check (consider implementing password hashing for security)
    if ($password === $user['password']) {
        // Verify the user's role matches what they're trying to login as
        $user_role = $user['role'];
        
        // Only accept reader or contributor roles
        if (!in_array($user_role, ['reader', 'contributor'])) {
            $_SESSION['error'] = "This account is not authorized for public login.";
            header("Location: login.php");
            exit();
        }
        
        // Check if user is trying to login with correct role
        if ($requested_role !== $user_role) {
            $_SESSION['error'] = "You are registered as a " . ucfirst($user_role) . ". Please login with the correct role.";
            header("Location: login.php");
            exit();
        }
        
        // Set common session variables
        $_SESSION['userID'] = $user['userID'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user_role;
        $_SESSION['logged_in'] = true;
        
        // Role-specific session variables and redirection
        if ($user_role === 'reader') {
            // Get reader-specific information
            $reader_sql = "SELECT readerSince FROM readers WHERE readerID = ?";
            $reader_stmt = mysqli_prepare($conn, $reader_sql);
            mysqli_stmt_bind_param($reader_stmt, "i", $user['userID']);
            mysqli_stmt_execute($reader_stmt);
            $reader_result = mysqli_stmt_get_result($reader_stmt);
            
            if (mysqli_num_rows($reader_result) === 1) {
                $reader_info = mysqli_fetch_assoc($reader_result);
                $_SESSION['readerSince'] = $reader_info['readerSince'];
            }
            
            $_SESSION['user_type'] = 'reader';
            header("Location: viewGurindam.php");
            
            // Clean up
            if (isset($reader_stmt)) mysqli_stmt_close($reader_stmt);
            
        } elseif ($user_role === 'contributor') {
            // Get contributor-specific information
            $contributor_sql = "SELECT bio, contributorSince FROM contributors WHERE contributorID = ?";
            $contributor_stmt = mysqli_prepare($conn, $contributor_sql);
            mysqli_stmt_bind_param($contributor_stmt, "i", $user['userID']);
            mysqli_stmt_execute($contributor_stmt);
            $contributor_result = mysqli_stmt_get_result($contributor_stmt);
            
            if (mysqli_num_rows($contributor_result) === 1) {
                $contributor_info = mysqli_fetch_assoc($contributor_result);
                $_SESSION['bio'] = $contributor_info['bio'];
                $_SESSION['contributorSince'] = $contributor_info['contributorSince'];
            }
            
            $_SESSION['user_type'] = 'contributor';
            header("Location: contributor_dashboard.php");
            
            // Clean up
            if (isset($contributor_stmt)) mysqli_stmt_close($contributor_stmt);
        }
        
        exit();
        
    } else {
        $_SESSION['error'] = "Incorrect password";
        header("Location: login.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Email not found";
    header("Location: login.php");
    exit();
}

// Clean up
if (isset($stmt)) mysqli_stmt_close($stmt);
mysqli_close($conn);
?>