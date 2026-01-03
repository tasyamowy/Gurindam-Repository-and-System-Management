<?php
session_start();
require_once 'reader-connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];

/* =====================
   VALIDATION
===================== */
if ($name === '') {
    $errors[] = "Full name is required.";
}

if ($email === '') {
    $errors[] = "Email address is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

if ($password === '') {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

/* =====================
   NAME & EMAIL CHECK
===================== */
if (empty($errors)) {
    $stmt = $conn->prepare("
        SELECT userID 
        FROM users 
        WHERE email = ? OR name = ?
    ");
    $stmt->bind_param("ss", $email, $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {

        // Find which one exists
        $stmt2 = $conn->prepare("
            SELECT email, name 
            FROM users 
            WHERE email = ? OR name = ?
        ");
        $stmt2->bind_param("ss", $email, $name);
        $stmt2->execute();
        $result = $stmt2->get_result();

        while ($row = $result->fetch_assoc()) {
            if ($row['email'] === $email) {
                $errors[] = "Email already registered.";
            }
            if (strcasecmp($row['name'], $name) === 0) {
                $errors[] = "Name already registered.";
            }
        }

        $stmt2->close();
    }

    $stmt->close();
}


/* =====================
   HANDLE ERRORS
===================== */
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old_name'] = $name;
    $_SESSION['old_email'] = $email;
    $_SESSION['show_register'] = true;

    header("Location: login.php");
    exit();
}

/* =====================
   INSERT USER
===================== */
/* ⚠️ Assignment only (plain text password) */
$stmt = $conn->prepare("
    INSERT INTO users (name, email, password, role, created_at)
    VALUES (?, ?, ?, 'reader', NOW())
");
$stmt->bind_param("sss", $name, $email, $password);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    $_SESSION['success'] = "Registration successful! Please login.";
     // ✅ CLEAR OLD FORM DATA
    unset($_SESSION['old_name']);
    unset($_SESSION['old_email']);
    
} else {
    $_SESSION['errors'] = ["Registration failed. Please try again."];
    $_SESSION['show_register'] = true;
}

$stmt->close();
$conn->close();

header("Location: login.php");
exit();
