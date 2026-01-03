<?php
session_start();

// ==============================
// DATABASE CONNECTION
// ==============================
$conn = new mysqli("localhost", "root", "", "gurindam_db");
if ($conn->connect_error) {
    die("Database connection failed");
}

// ==============================
// CHECK LOGIN
// ==============================
if (!isset($_SESSION['userID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['userID'];

// ==============================
// PROFILE UPDATE HANDLER
// ==============================
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    $errors = [];
    
    if (empty($name) || strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters long";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }
    }
    
    if (empty($errors)) {
        // Check if email already exists for another user
        $check_sql = "SELECT userID FROM users WHERE email = ? AND userID != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $userID);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $message = "Email already in use by another account";
            $message_type = "error";
        } else {
            // Update user information
            if (!empty($password)) {
                // Update with password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET name = ?, email = ?, password = ? WHERE userID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssi", $name, $email, $hashed_password, $userID);
            } else {
                // Update without password
                $update_sql = "UPDATE users SET name = ?, email = ? WHERE userID = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssi", $name, $email, $userID);
            }
            
            if ($update_stmt->execute()) {
                $message = "Profile updated successfully!";
                $message_type = "success";
                // Update session name if needed
                $_SESSION['name'] = $name;
            } else {
                $message = "Failed to update profile. Please try again.";
                $message_type = "error";
            }
            
            if (isset($update_stmt)) $update_stmt->close();
        }
        
        $check_stmt->close();
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}

// ==============================
// FETCH USER PROFILE
// ==============================
$sql = "
    SELECT 
        u.userID,
        u.name,
        u.email,
        u.created_at,
        r.readerSince
    FROM users u
    LEFT JOIN readers r ON u.userID = r.readerID
    WHERE u.userID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$stmt->close();
$conn->close();

// Format dates
$created_at = date('F j, Y', strtotime($user['created_at']));
$readerSince = $user['readerSince'] ? date('F j, Y', strtotime($user['readerSince'])) : 'Not available';

// Check if edit mode is active
$edit_mode = isset($_GET['edit']) && $_GET['edit'] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $edit_mode ? 'Edit Profile' : 'My Profile'; ?> | Gurindam Repository</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="viewprofile.css">

<style>
/* Additional styles for the new readerSince field */
.detail-row .detail-label i {
    margin-right: 8px;
    width: 20px;
    text-align: center;
}

.reader-since-badge {
    display: inline-flex;
    align-items: center;
    background: #f0f9ff;
    color: #0366d6;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    border: 1px solid #d1e7ff;
}

.reader-since-badge i {
    margin-right: 6px;
    color: #0366d6;
}

.member-duration {
    font-size: 13px;
    color: #666;
    margin-top: 4px;
    font-style: italic;
}
</style>

</head>

<body>

<!-- NAVIGATION BAR -->
<nav class="navbar">
    <a href="viewGurindam.php" class="nav-brand">
        <i class="fas fa-book-open"></i> Gurindam.
    </a>
    
    <div class="nav-links">
        <a href="viewGurindam.php" class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="featured.php" class="nav-link">
            <i class="fas fa-star"></i> Featured
        </a>
        <a href="about.php" class="nav-link">
            <i class="fas fa-info-circle"></i> About
        </a>
    </div>
    
    <div class="nav-actions">
        <button class="nav-icon-btn profile-icon" title="Profile" 
                onclick="location.href='viewprofile.php'" disabled>
            <i class="fas fa-user"></i>
        </button>

        <button class="nav-icon-btn logout-icon" title="Logout"
                onclick="confirmLogout()">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</nav>

<!-- HEADER -->
<div class="profile-header">
    <?php if ($edit_mode): ?>
    <a href="?edit=false" class="header-back-btn">
        <i class="fas fa-arrow-left"></i> Back to Profile
    </a>
    <?php endif; ?>
    
    <h1><?php echo $edit_mode ? 'Edit Profile' : 'My Profile'; ?></h1>
    <p><?php echo $edit_mode ? 'Update your personal information and settings' : 'Manage your Gurindam Repository account'; ?></p>
</div>

<!-- PROFILE CARD -->
<div class="profile-container">
    <div class="profile-card">
        
        <!-- Avatar Section -->
        <div class="avatar-section">
            <div class="avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="reader-since-badge">
                    <i class="fas fa-calendar-alt"></i>
                    Reader since: <?php echo $readerSince; ?>
                </div>
                <?php if ($user['readerSince']): ?>
                <?php 
                    $reader_date = new DateTime($user['readerSince']);
                    $current_date = new DateTime();
                    $interval = $reader_date->diff($current_date);
                    $years = $interval->y;
                    $months = $interval->m;
                ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Profile Content -->
        <div class="profile-content">
            <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($edit_mode): ?>
            <!-- EDIT PROFILE FORM -->
            <form method="POST" action="">
                <div class="details-section">
                    <h3 class="section-title">
                        <i class="fas fa-user-edit"></i> Personal Information
                    </h3>
                    
                    <div class="form-row">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <div class="form-input">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            <small>Your full name as displayed on the platform</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <div class="form-input">
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <small>This will be used for account communication</small>
                        </div>
                    </div>
                </div>
                
                <div class="details-section">
                    <h3 class="section-title">
                        <i class="fas fa-lock"></i> Security Settings
                    </h3>
                    
                    <div class="form-row">
                        <label class="form-label">
                            <i class="fas fa-key"></i> New Password
                        </label>
                        <div class="form-input">
                            <input type="password" name="password" placeholder="Leave blank to keep current password">
                            <small>Minimum 6 characters. Leave empty if you don't want to change</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <label class="form-label">
                            <i class="fas fa-key"></i> Confirm Password
                        </label>
                        <div class="form-input">
                            <input type="password" name="confirm_password" placeholder="Confirm new password">
                            <small>Re-enter the new password</small>
                        </div>
                    </div>
                </div>
                
                <div class="actions">
                    <button type="submit" name="update_profile" class="action-btn primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="?edit=false" class="action-btn secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
            
            <?php else: ?>
            <!-- VIEW PROFILE DETAILS -->
            <div class="details-section">
                <h3 class="section-title">
                    <i class="fas fa-user-circle"></i> Profile Details
                </h3>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-user-tag"></i> Role
                    </span>
                    <div class="detail-value">
                        <div class="role-badge">
                            <i class="fas fa-user-reader"></i>
                            <span>Reader</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-user"></i> Full Name
                    </span>
                    <span class="detail-value"><?php echo htmlspecialchars($user['name']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">
                        <i class="fas fa-calendar-alt"></i> Reader Since
                    </span>
                    <span class="detail-value">
                        <?php echo $readerSince; ?>
                        <?php if ($user['readerSince']): ?>
                        <div class="member-duration" style="margin-top: 5px;">
                            (<?php 
                            if ($years > 0) {
                                echo $years . " year" . ($years > 1 ? "s" : "");
                                if ($months > 0) {
                                    echo " and " . $months . " month" . ($months > 1 ? "s" : "");
                                }
                            } else {
                                echo $months . " month" . ($months > 1 ? "s" : "");
                            }
                            ?>)
                        </div>
                        <?php endif; ?>
                    </span>
                </div>
                
            </div>
            
            <div class="actions">
                <a href="?edit=true" class="action-btn primary">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                <a href="viewGurindam.php" class="action-btn secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
</script>

</body>
</html>