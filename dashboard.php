<?php
// ==============================
// SESSION CHECK (OPTIONAL BUT RECOMMENDED)
// ==============================
//require_once 'session_check.php';

// ==============================
// DATABASE CONNECTION
// ==============================
$conn = new mysqli("localhost", "root", "", "gurindam_db");
if ($conn->connect_error) {
    die("Database connection failed");
}

// ==============================
// PAGE IDENTIFIER FOR SIDEBAR
// ==============================
$current_page = 'dashboard';

// ==============================
// SUMMARY COUNTS
// ==============================
$totalGurindams = $conn->query("SELECT COUNT(*) AS c FROM gurindams")->fetch_assoc()['c'];
$pending = $conn->query("SELECT COUNT(*) AS c FROM gurindams WHERE status='Pending'")->fetch_assoc()['c'];
$approved = $conn->query("SELECT COUNT(*) AS c FROM gurindams WHERE status='Approved'")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];

// ==============================
// GET ADMIN USER NAME (Based on email containing 'admin')
// ==============================
// First, try to get the currently logged-in admin from session
session_start();
require_once 'connection.php';
$adminName = "Administrator"; // default

if (isset($_SESSION['user_id'])) {
    // If user is logged in, get their name from database
    $user_id = $_SESSION['user_id'];
    $adminQuery = $conn->prepare("SELECT name, email FROM users WHERE userID = ?");
    $adminQuery->bind_param("i", $user_id);
    $adminQuery->execute();
    $adminResult = $adminQuery->get_result();
    
    if ($adminResult && $adminResult->num_rows > 0) {
        $adminRow = $adminResult->fetch_assoc();
        $adminName = $adminRow['name'];
    }
    $adminQuery->close();
} else {
    // If no session, try to get any user with email containing 'admin'
    $adminQuery = $conn->query("SELECT name FROM users WHERE email LIKE '%admin%' LIMIT 1");
    if ($adminQuery && $adminQuery->num_rows > 0) {
        $adminRow = $adminQuery->fetch_assoc();
        $adminName = $adminRow['name'];
    }
}

// ==============================
// ADDITIONAL STATISTICS
// ==============================
$rejected = $conn->query("SELECT COUNT(*) AS c FROM gurindams WHERE status='Rejected'")->fetch_assoc()['c'];
$todaySubmissions = $conn->query("SELECT COUNT(*) AS c FROM gurindams WHERE DATE(submissionDate) = CURDATE()")->fetch_assoc()['c'];
$recentContributors = $conn->query("SELECT COUNT(DISTINCT authorID) as c FROM gurindams WHERE submissionDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['c'];

// Calculate percentages
$approvalRate = $totalGurindams > 0 ? round(($approved / $totalGurindams) * 100, 1) : 0;
$pendingRate = $totalGurindams > 0 ? round(($pending / $totalGurindams) * 100, 1) : 0;

// Inspiration quotes related to Gurindam and cultural preservation
$inspirationalQuotes = [
    "Preserving wisdom through timeless verses that embody cultural heritage, moral values, and enduring knowledge through generations.",
    "Every Gurindam holds a piece of cultural heritage, reflecting the values, beliefs, and wisdom of its time.",
    "Words that connect generations, bridging the past, present, and future through meaningful expression.",
    "Safeguarding poetic wisdom for future generations so tradition and knowledge may continue to thrive.",
    "Where tradition meets digital preservation, ensuring cultural wisdom remains accessible and relevant.",
    "Celebrating cultural expression through verse that carries meaning, identity, and collective memory.",
    "Nurturing literary heritage in the digital age by preserving timeless teachings through modern platforms.",
    "Wisdom preserved in rhyme and rhythm, offering guidance, reflection, and inspiration across generations.",
];

$randomQuote = $inspirationalQuotes[array_rand($inspirationalQuotes)];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Gurindam Repository</title>

<link rel="icon" type="image/png" href="images/gurindamm.png">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
<!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
   
<!-- SAME STYLE AS admin-pending.php -->
<link rel="stylesheet" href="stylePending.css">
<style>
/* ===============================
   COLOR VARIABLES
================================ */
:root {
    --primary: #9A1F1A;
    --primary-light: #C94A44;
    --primary-dark: #7A0F0B;
    --cream: #FDF6E3;
    --cream-light: #FFFBF0;
    --cream-dark: #F5EBD9;
    --text: #333333;
    --text-light: #666666;
    --text-lighter: #888888;
    --white: #ffffff;
    --success: #28a745;
    --warning: #ffc107;
    --info: #17a2b8;
}

/* ===============================
   WELCOME SECTION (BURGUNDY & CREAM)
================================ */
.welcome-section {
    background: linear-gradient(135deg, var(--white) 0%, var(--cream-light) 100%);
    color: var(--text);
    padding: 2.5rem;
    border-radius: 20px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(154, 31, 26, 0.08);
    position: relative;
    overflow: hidden;
    font-family: 'Inter', sans-serif;
    min-height: 600px;
    border: 1px solid var(--cream-dark);
}

/* Decorative burgundy accent */
.welcome-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

/* Soft burgundy corner accent */
.welcome-section::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, var(--primary), transparent);
    border-bottom-left-radius: 100%;
    opacity: 0.1;
    z-index: 0;
}

/* ===============================
   TEXT & INSPIRATION
================================ */
.welcome-content {
    position: relative;
    z-index: 2;
}

.welcome-header {
    margin-bottom: 2.5rem;
}

.welcome-section h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
    color: var(--primary);
    position: relative;
    display: inline-block;
}

.welcome-section h1 .admin-name {
    color: var(--text);
    font-weight: 600;
}

.welcome-section .subtitle {
    color: var(--text-light);
    font-size: 1.1rem;
    max-width: 800px;
    margin-top: 1rem;
    line-height: 1.6;
}

.inspiration-quote {
    font-style: italic;
    color: var(--primary);
    font-size: 1.05rem;
    margin: 2rem 0 2.5rem 0;
    padding: 1.5rem;
    background: rgba(154, 31, 26, 0.05);
    border-radius: 12px;
    border-left: 4px solid var(--primary);
    font-family: 'Playfair Display', serif;
    position: relative;
    border: 1px solid rgba(154, 31, 26, 0.1);
}

.inspiration-quote::before {
    content: '"';
    position: absolute;
    top: 10px;
    left: 10px;
    font-size: 3rem;
    color: rgba(154, 31, 26, 0.2);
    font-family: serif;
    line-height: 1;
}

/* ===============================
   QUICK STATS
================================ */
.quick-stats {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 2.5rem;
    position: relative;
    z-index: 2;
}

.stat-badge {
    background: linear-gradient(135deg, rgba(154, 31, 26, 0.1), rgba(201, 74, 68, 0.1));
    border: 1px solid rgba(154, 31, 26, 0.2);
    color: var(--primary);
    padding: 0.8rem 1.5rem;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}

.stat-badge:hover {
    background: linear-gradient(135deg, rgba(154, 31, 26, 0.15), rgba(201, 74, 68, 0.15));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(154, 31, 26, 0.1);
    border-color: rgba(154, 31, 26, 0.3);
}

.stat-badge i {
    color: var(--primary);
    font-size: 1.1rem;
}

/* ===============================
   MAIN STATS GRID
================================ */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    position: relative;
    z-index: 2;
}

.stat-card-large {
    background: var(--white);
    border: 1px solid var(--cream-dark);
    border-radius: 16px;
    padding: 2rem;
    transition: all 0.3s ease;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    position: relative;
    overflow: hidden;
}

.stat-card-large::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--primary-light));
}

.stat-card-large:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 25px rgba(154, 31, 26, 0.12);
    border-color: rgba(154, 31, 26, 0.2);
}

.stat-card-large .stat-icon {
    font-size: 2.5rem;
    color: var(--white);
    margin-bottom: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(154, 31, 26, 0.3);
}

.stat-card-large:hover .stat-icon {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 6px 20px rgba(154, 31, 26, 0.4);
}

.stat-card-large .stat-info h3 {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
    margin: 0.5rem 0;
    position: relative;
    display: inline-block;
}

.stat-card-large .stat-info h3::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 25%;
    width: 50%;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--primary), transparent);
}

.stat-card-large .stat-info p {
    color: var(--text-light);
    font-size: 1rem;
    font-weight: 600;
    margin: 0.5rem 0 0 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Additional info under each stat card */
.stat-extra {
    margin-top: 0.75rem;
    font-size: 0.85rem;
    color: var(--primary-light);
    opacity: 0.9;
    font-weight: 500;
}

/* ===============================
   SYSTEM STATUS
================================ */
.system-status {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--cream-dark);
    position: relative;
    z-index: 2;
}

.system-status h3 {
    color: var(--primary);
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.system-status h3 i {
    color: var(--primary);
}

.status-items {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    background: rgba(154, 31, 26, 0.03);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid rgba(154, 31, 26, 0.1);
}

.status-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--text-light);
    font-size: 0.95rem;
    font-weight: 500;
}

.status-item i {
    color: var(--success);
    font-size: 1rem;
    background: rgba(40, 167, 69, 0.1);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ===============================
   RESPONSIVE
================================ */
@media (max-width: 1024px) {
    .welcome-section {
        min-height: auto;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .welcome-section {
        padding: 2rem;
        border-radius: 16px;
    }

    .welcome-section h1 {
        font-size: 2rem;
    }
    
    .welcome-section::after {
        width: 80px;
        height: 80px;
    }

    .quick-stats {
        flex-direction: column;
    }

    .stat-badge {
        justify-content: center;
        text-align: center;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    
    .stat-card-large {
        padding: 1.75rem;
    }
    
    .stat-card-large .stat-info h3 {
        font-size: 2.2rem;
    }
    
    .status-items {
        flex-direction: column;
        gap: 1rem;
    }
}

@media (max-width: 480px) {
    .welcome-section {
        padding: 1.5rem;
    }

    .welcome-section h1 {
        font-size: 1.75rem;
    }
    
    .stat-card-large .stat-icon {
        width: 60px;
        height: 60px;
        font-size: 2rem;
    }
    
    .inspiration-quote {
        padding: 1.25rem 1rem 1.25rem 2rem;
        font-size: 0.95rem;
    }
}
</style>

</head>

<body>

<div class="dashboard-container">

    <!-- ================= SIDEBAR ================= -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <div class="logo-main">
                    Gurindam<span>.</span>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item <?= ($current_page == 'dashboard') ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="admin-manageuser.php" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Manage Users</span>
            </a>
            <a href="admin-pending.php" class="nav-item">
                <i class="fas fa-book"></i>
                <span>Pending Gurindam</span>
            </a>
            <a href="admin-report.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Report</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <p>© 2025 Gurindam Repository</p>
        </div>
    </div>

    <!-- ================= MAIN CONTENT ================= -->
    <div class="main-content">

        <!-- HEADER -->
        <header class="header">
            <h1>Admin Dashboard</h1>
            <div class="header-actions">
                <div class="profile-btn" title="Dashboard" onclick="location.href='adminprofile.php'">
                    <i class="fas fa-user"></i>
                </div>
                <div class="logout-btn" title="Logout" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <div class="content">

            <!-- EXPANDED WELCOME SECTION -->
            <div class="welcome-section">
                <div class="welcome-content">
                    <div class="welcome-header">
                        <h1>Welcome back, <span class="admin-name"><?= htmlspecialchars($adminName) ?></span> </h1>
                        <div class="subtitle">
                            Manage and monitor the Gurindam Repository, preserving cultural wisdom one verse at a time.
                        </div>
                        
                        <!-- Inspiration Quote -->
                        <div class="inspiration-quote">
                            <?= $randomQuote ?>
                        </div>
                    </div>
                    
                    <!-- QUICK STATS -->
                    <div class="quick-stats">
                        <div class="stat-badge">
                            <i class="fas fa-clock"></i>
                            <?= $pending ?> Pending Reviews
                        </div>
                        <div class="stat-badge">
                            <i class="fas fa-calendar-day"></i>
                            <?= $todaySubmissions ?> Today's Submissions
                        </div>
                        <div class="stat-badge">
                            <i class="fas fa-percentage"></i>
                            <?= $approvalRate ?>% Approval Rate
                        </div>
                        <div class="stat-badge">
                            <i class="fas fa-user-clock"></i>
                            <?= $recentContributors ?> Recent Contributors
                        </div>
                    </div>
                    
                    <!-- MAIN STATISTICS GRID -->
                    <div class="stats-grid">
                        <div class="stat-card-large">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?= $totalGurindams ?></h3>
                                <p>Total Gurindam</p>
                            </div>
                            <div class="stat-extra">
                                Preserved cultural verses
                            </div>
                        </div>
                        
                        <div class="stat-card-large">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?= $pending ?></h3>
                                <p>Pending Review</p>
                            </div>
                            <div class="stat-extra">
                                Awaiting your attention
                            </div>
                        </div>
                        
                        <div class="stat-card-large">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?= $approved ?></h3>
                                <p>Approved</p>
                            </div>
                            <div class="stat-extra">
                                Published to repository
                            </div>
                        </div>
                        
                        <div class="stat-card-large">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?= $totalUsers ?></h3>
                                <p>Total Users</p>
                            </div>
                            <div class="stat-extra">
                                Community members
                            </div>
                        </div>
                    </div>
                    
                    <!-- SYSTEM STATUS -->
                    <div class="system-status">
                        <h3><i class="fas fa-server"></i> System Status</h3>
                        <div class="status-items">
                            <div class="status-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Database Connection: Active</span>
                            </div>
                            <div class="status-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Repository: <?= $totalGurindams ?> entries</span>
                            </div>
                            <div class="status-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Community: <?= $totalUsers ?> members</span>
                            </div>
                            <div class="status-item">
                                <i class="fas fa-check-circle"></i>
                                <span>Last Updated: Today</span>
                            </div>
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
<!-- Add SweetAlert2 JS BEFORE your custom script -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Simple, direct logout function
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

document.getElementById("profileBtn").addEventListener("click", function () {
    window.location.href = "adminprofile.php";
});
</script>

</body>
</html>

<?php
$conn->close();
?>
