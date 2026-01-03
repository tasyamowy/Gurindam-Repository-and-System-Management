<?php
// view_gurindam.php
require_once "reader-connection.php";
session_start();

// Check if ID parameter exists
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ViewGurindam.php");
    exit();
}

$gurindamID = (int)$_GET['id'];

// Fetch the specific Gurindam
$sql = "
    SELECT 
        g.gurindamID,
        g.title,
        g.content,
        g.submissionDate,
        g.status,
        t.themeName,
        u.name as authorName,
        u.userID
    FROM gurindams g
    LEFT JOIN themes t ON g.themeID = t.themeID
    LEFT JOIN contributors c ON g.authorID = c.contributorID
    LEFT JOIN users u ON c.contributorID = u.userID
    WHERE g.gurindamID = ? AND g.status = 'Approved'
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $gurindamID);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    // Gurindam not found or not approved
    header("Location: ViewGurindam.php");
    exit();
}

$row = mysqli_fetch_assoc($result);
$submissionDate = date('F d, Y', strtotime($row['submissionDate']));
$isLoggedIn = isset($_SESSION['user_id']);

// Check if current user is the author
$isAuthor = $isLoggedIn && $_SESSION['user_id'] == $row['userID'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($row['title']) ?> - Gurindam Repository</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="gurindamContent.css">
</head>

<body>

<!-- NAVIGATION BAR -->
<nav class="navbar">
    <a href="ViewGurindam.php" class="nav-brand">
        Gurindam.
    </a>
    
    <div class="nav-links">
        <a href="ViewGurindam.php" class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="featured.php" class="nav-link">
            <i class="fas fa-star"></i> Featured
        </a>
        <a href="about.php" class="nav-link">
            <i class="fas fa-info-circle"></i> About
        </a>
        
        <?php if ($isLoggedIn): ?>
            <a href="submit.php" class="nav-link">
                <i class="fas fa-plus-circle"></i> Submit
            </a>
            <a href="mygurindam.php" class="nav-link">
                <i class="fas fa-user-circle"></i> My Gurindams
            </a>
        <?php endif; ?>
    </div>
    
    <div class="nav-actions">
        <button class="nav-icon-btn profile-icon" title="Profile"
            onclick="location.href='viewprofile.php'">
            <i class="fas fa-user"></i>
        </button>

        <button class="nav-icon-btn logout-icon" title="Logout"
            onclick="confirmLogout()">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container">
    
    <!-- GURINDAM DETAIL CARD -->
    <div class="gurindam-detail-card">
        <div class="detail-header">
            <h1><?= htmlspecialchars($row['title']) ?></h1>
            <div class="theme-badge"><?= htmlspecialchars($row['themeName']) ?></div>
            <div class="author-info">
                <div class="author-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <span>By <?= htmlspecialchars($row['authorName'] ?? 'Anonymous') ?></span>
            </div>
        </div>
        
        <div class="detail-content">
            <?= nl2br(htmlspecialchars($row['content'])) ?>
        </div>
        
        <div class="detail-meta">
            <div class="meta-info">
                <span class="meta-label">Submitted On</span>
                <span class="meta-value"><?= $submissionDate ?></span>
            </div>
            
            <div class="action-buttons">
                <a href="ViewGurindam.php" class="action-btn back-btn">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                
                <?php if ($isAuthor): ?>
                    <a href="edit_gurindam.php?id=<?= $gurindamID ?>" class="action-btn edit-btn">
                        <i class="fas fa-edit"></i> Edit Gurindam
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<footer>
    <p>© 2025 Gurindam Repository & Management System | Celebrating Traditional Wisdom</p>
</footer>

<script>
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'login.php';
    }
}
</script>

</body>
</html>