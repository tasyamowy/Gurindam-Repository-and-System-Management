<?php
session_start();

// ==============================
// AUTH CHECK
// ==============================
if (!isset($_SESSION['userID']) || !$_SESSION['admin_logged_in']) {
    header("Location: admin_login.php");
    exit();
}

$adminID = $_SESSION['userID'];

// ==============================
// DATABASE CONNECTION
// ==============================
require_once 'connection.php';

// ==============================
// HANDLE FORM SUBMISSION
// ==============================
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_profile'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $position = $_POST['position'] ?? '';
    $hireDate = $_POST['hireDate'] ?? '';

    // Simple validation
    if ($name && $email && $position && $hireDate) {
        $stmt = $conn->prepare("UPDATE users u 
                                JOIN admins a ON u.userID = a.adminID
                                SET u.name = ?, u.email = ?, a.position = ?, a.hireDate = ?
                                WHERE u.userID = ?");
        $stmt->bind_param("ssssi", $name, $email, $position, $hireDate, $adminID);

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully!";
        } else {
            $error_message = "Failed to update profile: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "All fields are required!";
    }
}

// ==============================
// FETCH ADMIN DATA
// ==============================
$sql = "
    SELECT 
        u.userID,
        u.name,
        u.email,
        u.created_at,
        a.position,
        a.hireDate
    FROM users u
    JOIN admins a ON u.userID = a.adminID
    WHERE a.adminID = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Admin profile not found.");
}

$admin = $result->fetch_assoc();

// Calculate tenure (years since hire)
$hireDate = new DateTime($admin['hireDate']);
$today = new DateTime();
$tenure = $today->diff($hireDate)->y;

// Format dates nicely
$formattedHireDate = date("d M Y", strtotime($admin['hireDate']));
$formattedCreatedDate = date("d M Y", strtotime($admin['created_at']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Profile — Gurindam Repository</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&family=Cormorant+Garamond:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styleManageUser.css">

<!-- SweetAlert2 for nice popup messages -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ===============================
   COLOR VARIABLES - BURGUNDY & CREAM
================================ */
:root {
    --burgundy: #9A1F1A;
    --burgundy-light: #C94A44;
    --burgundy-dark: #7A0F0B;
    --cream: #FDF6E3;
    --cream-light: #FFFBF0;
    --cream-dark: #F5EBD9;
    --gold: #D4AF37;
    --text: #333333;
    --text-light: #666666;
    --white: #ffffff;
    --shadow: rgba(154, 31, 26, 0.1);
}

/* ===============================
   CV STYLE PROFILE LAYOUT
================================ */
.content {
    padding: 40px;
    background: linear-gradient(135deg, var(--cream-light) 0%, var(--cream) 100%);
    min-height: calc(100vh - 160px);
}

.cv-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    gap: 30px;
}

/* ===============================
   LEFT SIDEBAR - CV STYLE - REVERSED COLORS
================================ */
.cv-sidebar {
    flex: 0 0 350px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 20px;
    padding: 40px;
    color: #333333;
    box-shadow: 0 15px 40px rgba(154, 31, 26, 0.15);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(154, 31, 26, 0.1);
}

.cv-sidebar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, 
        #9A1F1A, 
        #C94A44, 
        #9A1F1A);
}

.cv-sidebar::after {
    content: '📜';
    position: absolute;
    bottom: -30px;
    right: -30px;
    font-size: 180px;
    opacity: 0.07;
    color: #9A1F1A;
    transform: rotate(-15deg);
}

.profile-avatar-large {
    width: 140px;
    height: 140px;
    background: linear-gradient(135deg, #ffffff 0%, #f5f5f5 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px auto;
    font-size: 60px;
    color: #9A1F1A;
    font-weight: bold;
    border: 5px solid rgba(154, 31, 26, 0.2);
    box-shadow: 0 10px 30px rgba(154, 31, 26, 0.15);
    position: relative;
    z-index: 2;
}

.cv-name {
    text-align: center;
    margin-bottom: 30px;
    position: relative;
    z-index: 2;
}

.cv-name h1 {
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 10px 0;
    color: #222222;
    text-shadow: none;
}

.cv-name .title {
    font-size: 18px;
    color: #9A1F1A;
    font-weight: 600;
    letter-spacing: 1px;
    background: rgba(154, 31, 26, 0.1);
    padding: 6px 16px;
    border-radius: 20px;
    display: inline-block;
    border: 1px solid rgba(154, 31, 26, 0.2);
}

.cv-contact {
    background: rgba(154, 31, 26, 0.05);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    border: 1px solid rgba(154, 31, 26, 0.15);
    backdrop-filter: blur(10px);
    position: relative;
    z-index: 2;
}

.cv-contact h3 {
    font-family: 'Inter', sans-serif;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 20px 0;
    color: #9A1F1A;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}

.cv-contact h3::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 40px;
    height: 2px;
    background: #9A1F1A;
    border-radius: 1px;
}

.cv-contact h3 i {
    font-size: 18px;
    color: #9A1F1A;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 15px;
    color: #444444;
    padding: 8px 0;
    border-bottom: 1px solid rgba(154, 31, 26, 0.1);
    transition: all 0.3s ease;
}

.contact-item:last-child {
    margin-bottom: 0;
    border-bottom: none;
}

.contact-item:hover {
    transform: translateX(5px);
    color: #9A1F1A;
}

.contact-item i {
    color: #9A1F1A;
    font-size: 18px;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    background: rgba(154, 31, 26, 0.1);
}

.contact-item:hover i {
    background: rgba(154, 31, 26, 0.2);
    color: #9A1F1A;
    transform: scale(1.1);
}

.cv-tenure {
    background: rgba(154, 31, 26, 0.05);
    border-radius: 15px;
    padding: 25px;
    border: 1px solid rgba(154, 31, 26, 0.15);
    backdrop-filter: blur(10px);
    position: relative;
    z-index: 2;
}

.tenure-badge {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid rgba(154, 31, 26, 0.2);
}

.tenure-badge .icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #9A1F1A;
    box-shadow: 0 5px 15px rgba(154, 31, 26, 0.15);
    border: 1px solid rgba(154, 31, 26, 0.1);
    transition: all 0.3s ease;
}

.tenure-badge:hover .icon {
    transform: rotate(10deg) scale(1.05);
    box-shadow: 0 8px 20px rgba(154, 31, 26, 0.25);
    background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%);
}

.tenure-badge .tenure-info .years {
    font-size: 36px;
    font-weight: 800;
    color: #9A1F1A;
    line-height: 1;
    text-shadow: 0 2px 4px rgba(154, 31, 26, 0.2);
}

.tenure-badge .tenure-info .label {
    font-size: 14px;
    color: #666666;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 600;
}

.tenure-dates {
    border-top: 1px solid rgba(154, 31, 26, 0.2);
    padding-top: 20px;
}

.date-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    padding: 10px 0;
    font-size: 14px;
    color: #444444;
    border-bottom: 1px solid rgba(154, 31, 26, 0.1);
    transition: all 0.3s ease;
}

.date-item:last-child {
    margin-bottom: 0;
    border-bottom: none;
}

.date-item:hover {
    transform: translateX(5px);
    background: rgba(154, 31, 26, 0.08);
    padding: 10px 15px;
    border-radius: 8px;
    margin-left: -15px;
    margin-right: -15px;
}

.date-item .label {
    color: #666666;
    font-weight: 500;
}
/* ===============================
   RIGHT CONTENT - CV STYLE
================================ */
.cv-content {
    flex: 1;
    background: var(--white);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 15px 40px var(--shadow);
    position: relative;
    overflow: hidden;
}

.cv-content::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: linear-gradient(90deg, var(--burgundy), var(--burgundy-light));
}

.cv-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--cream-dark);
}

.cv-header h2 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    color: var(--burgundy);
    margin: 0;
    position: relative;
    display: inline-block;
}

.cv-header h2::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--burgundy);
}

/* ===============================
   CV SECTIONS
================================ */
.cv-section {
    margin-bottom: 40px;
}

.cv-section:last-child {
    margin-bottom: 0;
}

.cv-section h3 {
    font-family: 'Inter', sans-serif;
    font-size: 18px;
    font-weight: 600;
    color: var(--burgundy);
    margin: 0 0 25px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid var(--cream-dark);
    text-transform: uppercase;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.cv-section h3 i {
    color: var(--burgundy-light);
}

.cv-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

.info-item {
    background: var(--cream-light);
    border-radius: 12px;
    padding: 20px;
    border: 1px solid var(--cream-dark);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.info-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px var(--shadow);
    border-color: var(--burgundy-light);
}

.info-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    background: var(--burgundy);
}

.info-item .label {
    font-size: 13px;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
    font-weight: 600;
}

.info-item .value {
    font-size: 18px;
    color: var(--text);
    font-weight: 500;
    font-family: 'Cormorant Garamond', serif;
}

/* ===============================
   ADMIN BADGE
================================ */
.admin-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, var(--burgundy), var(--burgundy-dark));
    color: var(--white);
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
    box-shadow: 0 4px 15px rgba(154, 31, 26, 0.3);
    border: 2px solid var(--gold);
}

/* ===============================
   EDIT BUTTON
================================ */
.edit-profile-btn {
    padding: 12px 30px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    border: none;
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, var(--burgundy), var(--burgundy-light));
    color: var(--white);
    box-shadow: 0 6px 20px rgba(154, 31, 26, 0.2);
}

.edit-profile-btn:hover {
    background: linear-gradient(135deg, var(--burgundy-dark), var(--burgundy));
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(154, 31, 26, 0.3);
}

/* ===============================
   MODAL - CV STYLE
================================ */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    inset: 0;
    background: rgba(20, 20, 20, 0.45); /* neutral dark */
    backdrop-filter: blur(6px);
    align-items: center;
    justify-content: center;
}



.modal-content {
    background: var(--white);
    padding: 32px 36px;
    border-radius: 18px;
    max-width: 480px;
    width: 92%;
    position: relative;
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.25);
    border: 1.5px solid var(--gold);
    animation: modalSlide 0.3s ease-out;
}


.modal-content {
    background: var(--white);
    padding: 32px 36px;
    border-radius: 18px;
    max-width: 480px;
    width: 92%;
    position: relative;
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.25);
    border: 1.5px solid var(--gold);
    animation: modalSlide 0.3s ease-out;
}


@keyframes modalSlide {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.close-modal {
    position: absolute;
    top: 25px;
    right: 25px;
    font-size: 28px;
    cursor: pointer;
    color: var(--burgundy);
    transition: color 0.2s;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: var(--cream-light);
}

.close-modal:hover {
    color: var(--burgundy-dark);
    background: var(--cream-dark);
}

.modal-content h2 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    color: var(--burgundy);
    margin: 0 0 35px 0;
    text-align: center;
    position: relative;
    padding-bottom: 15px;
}

.modal-content h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 3px;
    background: var(--burgundy);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 18px;
}

.form-group {
    position: relative;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--burgundy);
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group label i {
    color: var(--burgundy-light);
    font-size: 16px;
}

.form-group input {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid var(--cream-dark);
    border-radius: 10px;
    font-size: 15px;
}


.form-group input:focus {
    outline: none;
    border-color: var(--burgundy);
    box-shadow: 0 0 0 4px rgba(154, 31, 26, 0.1);
    background: var(--white);
}

.modal-actions {
    display: flex;
    gap: 15px;
    margin-top: 40px;
}

.modal-actions .btn {
    flex: 1;
    padding: 13px;
    border-radius: 10px;
    font-size: 15px;
}


.modal-actions .btn:first-child {
    background: linear-gradient(135deg, var(--burgundy), var(--burgundy-light));
    color: var(--white);
}

.modal-actions .btn:first-child:hover {
    background: linear-gradient(135deg, var(--burgundy-dark), var(--burgundy));
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(154, 31, 26, 0.3);
}

.modal-actions .btn.cancel {
    background: var(--cream-light);
    color: var(--text);
    border: 2px solid var(--cream-dark);
}

.modal-actions .btn.cancel:hover {
    background: var(--cream-dark);
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* ===============================
   RESPONSIVE DESIGN
================================ */
@media (max-width: 1024px) {
    .cv-container {
        flex-direction: column;
    }
    
    .cv-sidebar {
        flex: none;
    }
    
    .cv-info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .content {
        padding: 20px;
    }
    
    .cv-sidebar,
    .cv-content {
        padding: 30px;
    }
    
    .cv-header {
        flex-direction: column;
        gap: 20px;
        text-align: center;
    }
    
    .cv-header h2::after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    .modal-content {
        padding: 30px 20px;
    }
    
    .modal-actions {
        flex-direction: column;
    }
}

/* ===============================
   FOOTER - SIDEBAR COLOR
================================ */

</style>
</head>
<body>
<div class="dashboard-container">

    <!-- Sidebar - Original Colors -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">Gurindam.<span></span></div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="admin-manageuser.php" class="nav-item"><i class="fas fa-users"></i> Manage Users</a>
            <a href="admin-pending.php" class="nav-item"><i class="fas fa-book"></i> Pending Gurindam</a>
            <a href="admin-report.php" class="nav-item"><i class="fas fa-chart-bar"></i> Report</a>
        </nav>
        <div class="sidebar-footer"><p>© 2025 Gurindam Repository</p></div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <h1>Admin Profile</h1>
            <div class="header-actions">
                <div class="profile-btn" title="Dashboard" onclick="location.href='dashboard.php'">
                    <i class="fas fa-user"></i>
                </div>
                <div class="logout-btn" title="Logout" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
        </header>

        <div class="content">
            <!-- CV Style Profile -->
            <div class="cv-container">
                <!-- Left Sidebar (Burgundy) -->
                <div class="cv-sidebar">
                    <div class="profile-avatar-large">
                        <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                    </div>
                    
                    <div class="cv-name">
                        <h1><?= htmlspecialchars($admin['name']) ?></h1>
                        <div class="title"><?= htmlspecialchars($admin['position']) ?></div>
                    </div>
                    
                    <div class="cv-contact">
                        <h3><i class="fas fa-address-card"></i>Additional</h3>
                        <div class="contact-item">
                            
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-user-shield"></i>
                            <span>Administrator Account</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-database"></i>
                            <span>Gurindam Repository</span>
                        </div>
                    </div>
                    
                    <div class="cv-tenure">
                        <div class="tenure-badge">
                            <div class="icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="tenure-info">
                                <div class="years"><?= $tenure ?> Year<?= $tenure !== 1 ? 's' : '' ?></div>
                                <div class="label">Service Tenure</div>
                            </div>
                        </div>
                        
                        <div class="tenure-dates">
                            <div class="date-item">
                                <span class="label">Hired:</span>
                                <span class="value"><?= $formattedHireDate ?></span>
                            </div>
                            <div class="date-item">
                                <span class="label">Account Created:</span>
                                <span class="value"><?= $formattedCreatedDate ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Content (White) -->
                <div class="cv-content">
                    <div class="cv-header">
                        <h2>Administrator Profile</h2>
                        <span class="admin-badge">
                            <i class="fas fa-shield-alt"></i>
                            System Administrator
                        </span>
                    </div>
                    
                    <!-- Personal Information Section -->
                    <div class="cv-section">
                        <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
                        <div class="cv-info-grid">
                            <div class="info-item">
                                <div class="label">Full Name</div>
                                <div class="value"><?= htmlspecialchars($admin['name']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Email Address</div>
                                <div class="value"><?= htmlspecialchars($admin['email']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Position</div>
                                <div class="value"><?= htmlspecialchars($admin['position']) ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Admin ID</div>
                                <div class="value">#<?= htmlspecialchars($admin['userID']) ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employment Timeline Section -->
                    <div class="cv-section">
                        <h3><i class="fas fa-history"></i> Employment Timeline</h3>
                        <div class="cv-info-grid">
                            <div class="info-item">
                                <div class="label">Hire Date</div>
                                <div class="value"><?= $formattedHireDate ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Account Created</div>
                                <div class="value"><?= $formattedCreatedDate ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Tenure Duration</div>
                                <div class="value"><?= $tenure ?> Year<?= $tenure !== 1 ? 's' : '' ?></div>
                            </div>
                            <div class="info-item">
                                <div class="label">Status</div>
                                <div class="value" style="color: var(--burgundy); font-weight: 600;">
                                    <i class="fas fa-check-circle"></i> Active
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Access Section -->
                    <div class="cv-section">
                        <h3><i class="fas fa-cogs"></i> System Access</h3>
                        <div style="text-align: center; margin-top: 30px;">
                            <button class="edit-profile-btn" id="editProfileBtn">
                                <i class="fas fa-edit"></i>
                                Edit Profile Information
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            © 2025 Gurindam Repository — Preserving Cultural Heritage
        </footer>
    </div>
</div>

<!-- Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <span class="close-modal" id="closeModal">&times;</span>
        <h2>Edit Administrator Profile</h2>
        <form method="POST">
            <input type="hidden" name="edit_profile" value="1">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="position"><i class="fas fa-briefcase"></i> Position / Title</label>
                    <input type="text" name="position" value="<?= htmlspecialchars($admin['position']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="hireDate"><i class="fas fa-calendar-alt"></i> Hire Date</label>
                    <input type="date" name="hireDate" value="<?= $admin['hireDate'] ?>" required>
                </div>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
                <button type="button" class="btn cancel" id="cancelEditBtn">
                    <i class="fas fa-times"></i>
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functionality
const editBtn = document.getElementById('editProfileBtn');
const modal = document.getElementById('editModal');
const closeBtn = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelEditBtn');

editBtn.addEventListener('click', () => {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
});

function closeModal() {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

closeBtn.addEventListener('click', closeModal);
cancelBtn.addEventListener('click', closeModal);

window.addEventListener('click', (e) => { 
    if (e.target === modal) closeModal(); 
});

// Notifications with matching theme
<?php if($success_message): ?>
Swal.fire({
    icon: 'success',
    title: 'Profile Updated',
    text: '<?= addslashes($success_message) ?>',
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    background: 'var(--white)',
    color: 'var(--text)',
    iconColor: 'var(--burgundy)'
});
<?php endif; ?>

<?php if($error_message): ?>
Swal.fire({
    icon: 'error',
    title: 'Update Failed',
    text: '<?= addslashes($error_message) ?>',
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    background: 'var(--white)',
    color: 'var(--text)',
    iconColor: 'var(--burgundy)'
});
<?php endif; ?>

// Add hover effects
document.addEventListener('DOMContentLoaded', function() {
    const infoItems = document.querySelectorAll('.info-item');
    
    infoItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
function confirmLogout() {
    // First, let's test if SweetAlert2 is loaded
    if (typeof Swal === 'undefined') {
        // SweetAlert2 not loaded, use native confirm
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
        return;
    }
    
    // Use SweetAlert2
    Swal.fire({
        title: 'Logout?',
        text: "Are you sure you want to logout?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#9A1F1A', // Using your burgundy color
        cancelButtonColor: '#666',
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Logging out...',
                timer: 1500,
                timerProgressBar: true,
                showConfirmButton: false,
                allowOutsideClick: false,
                willOpen: () => {
                    Swal.showLoading();
                },
                willClose: () => {
                    window.location.href = 'logout.php';
                }
            });
        }
    });
}

// Alternative: Simple version without SweetAlert2
function simpleLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
    }
}
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>