<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all data from database
// 1. Theme distribution data
$themeData = [];
$themeQuery = "SELECT 
    t.themeID,
    t.themeName,
    COUNT(g.gurindamID) as work_count
FROM themes t
LEFT JOIN gurindams g ON t.themeID = g.themeID AND g.status = 'Approved'
GROUP BY t.themeID, t.themeName
ORDER BY work_count DESC";

$themeResult = $conn->query($themeQuery);
if ($themeResult && $themeResult->num_rows > 0) {
    while($row = $themeResult->fetch_assoc()) {
        $themeData[] = $row;
    }
}

// 2. Monthly additions data (last 6 months)
$monthlyData = [];
$monthlyQuery = "SELECT 
    DATE_FORMAT(submissionDate, '%b') as month_name,
    DATE_FORMAT(submissionDate, '%m') as month_num,
    DATE_FORMAT(submissionDate, '%Y-%m') as yearmonth,
    COUNT(*) as work_count
FROM gurindams 
WHERE status = 'Approved' 
    AND submissionDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY yearmonth, month_name, month_num
ORDER BY yearmonth ASC
LIMIT 6";

$monthlyResult = $conn->query($monthlyQuery);
if ($monthlyResult && $monthlyResult->num_rows > 0) {
    while($row = $monthlyResult->fetch_assoc()) {
        $monthlyData[] = $row;
    }
}

// 3. Contributor data
$contributorsData = [];
$contributorsQuery = "SELECT 
    u.userID,
    u.name,
    u.email,
    c.contributorSince,
    COUNT(g.gurindamID) as total_works
FROM users u
JOIN contributors c ON u.userID = c.contributorID
LEFT JOIN gurindams g ON u.userID = g.authorID AND g.status = 'Approved'
GROUP BY u.userID, u.name, u.email, c.contributorSince
ORDER BY total_works DESC";

$contributorsResult = $conn->query($contributorsQuery);
if ($contributorsResult && $contributorsResult->num_rows > 0) {
    while($row = $contributorsResult->fetch_assoc()) {
        $contributorsData[] = $row;
    }
}

// 4. Stats for dashboard
// Total works (Approved only)
$totalWorksQuery = "SELECT COUNT(*) as total FROM gurindams WHERE status = 'Approved'";
$totalWorksResult = $conn->query($totalWorksQuery);
$totalWorks = $totalWorksResult->fetch_assoc()['total'] ?? 0;

// Total contributors
$totalContributorsQuery = "SELECT COUNT(*) as total FROM contributors";
$totalContributorsResult = $conn->query($totalContributorsQuery);
$totalContributors = $totalContributorsResult->fetch_assoc()['total'] ?? 0;

// Total users (for registered readers - assuming all users are readers)
$totalUsersQuery = "SELECT COUNT(*) as total FROM users";
$totalUsersResult = $conn->query($totalUsersQuery);
$totalUsers = $totalUsersResult->fetch_assoc()['total'] ?? 0;

// For monthly views, you might need to create a views table. For now, using placeholder.
$monthlyViews = 2847; // Placeholder

// 5. Newly registered users (last 5)
$newUsersData = [];
// First check if created_at column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'");
if ($checkColumn->num_rows == 0) {
    // If no created_at column, use userID as proxy
    $newUsersQuery = "SELECT 
        name,
        email,
        'Recently' as joined_date
    FROM users 
    ORDER BY userID DESC
    LIMIT 5";
} else {
    $newUsersQuery = "SELECT 
        name,
        email,
        created_at as joined_date
    FROM users 
    ORDER BY created_at DESC
    LIMIT 5";
}

$newUsersResult = $conn->query($newUsersQuery);
if ($newUsersResult && $newUsersResult->num_rows > 0) {
    while($row = $newUsersResult->fetch_assoc()) {
        $newUsersData[] = $row;
    }
}

// 6. Pending submissions (last 5)
$pendingData = [];
$pendingQuery = "SELECT 
    g.title,
    u.name as author,
    g.submissionDate as submitted_date,
    g.status
FROM gurindams g
JOIN users u ON g.authorID = u.userID
WHERE g.status = 'Pending'
ORDER BY g.submissionDate DESC
LIMIT 5";

$pendingResult = $conn->query($pendingQuery);
if ($pendingResult && $pendingResult->num_rows > 0) {
    while($row = $pendingResult->fetch_assoc()) {
        $pendingData[] = $row;
    }
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gurindam Repository — Analytics Report Dashboard</title>
    <link rel="icon" type="image/png" href="images/gurindamm.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styleReport.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Animate.css for animations (optional but nice) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .works-badge {
            display: inline-block;
            background: linear-gradient(135deg, #9A1F1A, #C62828);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            min-width: 40px;
            text-align: center;
        }
        .rank-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            background: #9A1F1A;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 28px;
            font-weight: 600;
            font-size: 14px;
        }
        .status-badge.pending {
            background: linear-gradient(135deg, #FF9800, #FF5722);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-main">
                        Gurindam<span>.</span>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item">
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
                <a href="admin-report.php" class="nav-item active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Report</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <p>© 2025 Gurindam Repository</p>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
        <!-- Header -->
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
            
            <!-- Content Area -->
            <div class="content">
                <!-- Stats Cards -->
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="totalWorksStat"><?php echo $totalWorks; ?></h3>
                            <p>Total Gurindam Works</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="totalContributorsStat"><?php echo $totalContributors; ?></h3>
                            <p>Contributors</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="monthlyViewsStat"><?php echo $monthlyViews; ?></h3>
                            <p>Monthly Views</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="registeredReadersStat"><?php echo $totalUsers; ?></h3>
                            <p>Registered Readers</p>
                        </div>
                    </div>
                </div>
                
                <!-- Report Type Selector -->
                <div class="card">
                    <div class="card-header">
                        <h2>Report Detail</h2>
                        <div class="btn-group" role="group" aria-label="Report type selector">
                            <button type="button" class="btn-outline active" id="repoReportBtn">
                                Repository Report
                            </button>
                            <button type="button" class="btn-outline" id="activityReportBtn">
                                Activity Report
                            </button>
                        </div>
                    </div>
                    
                    <!-- Repository Report (Initially Visible) -->
                    <div id="repositoryReport">
                        <div class="chart-container">
                            <!-- First Row: Theme Distribution (Donut) + Monthly Additions (Horizontal Bar) -->
                            <div class="chart-row">
                                <!-- Theme Distribution (Donut Chart) -->
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h3>Gurindam Works by Theme</h3>
                                        <p>Distribution of <span id="totalWorksText"><?php echo $totalWorks; ?></span> works across different themes</p>
                                    </div>
                                    <div class="pie-chart-section">
                                        <div class="donut-chart-container">
                                            <div class="donut-chart" id="themeDonutChart">
                                                <!-- Donut chart segments will be generated by JavaScript -->
                                                <div class="donut-center">
                                                    <div class="donut-total" id="donutTotal"><?php echo $totalWorks; ?></div>
                                                    <div class="donut-label">Total Works</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="chart-legend" id="themeLegend">
                                            <!-- Legend will be generated by JavaScript -->
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Monthly Additions (Horizontal Bar Chart) -->
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h3>Monthly Additions</h3>
                                        <p>New works added in the last 6 months</p>
                                    </div>
                                    <div class="horizontal-bar-chart" id="monthlyBarChart">
                                        <!-- Horizontal bar chart will be generated by JavaScript -->
                                    </div>
                                    <div class="monthly-stats">
                                        <div class="monthly-stat">
                                            <div class="monthly-stat-value" id="totalLast6Months">0</div>
                                            <div class="monthly-stat-label">Last 6 Months</div>
                                        </div>
                                        <div class="monthly-stat">
                                            <div class="monthly-stat-value" id="avgPerMonth">0</div>
                                            <div class="monthly-stat-label">Avg/Month</div>
                                        </div>
                                        <div class="monthly-stat">
                                            <div class="monthly-stat-value" id="growthRate">0%</div>
                                            <div class="monthly-stat-label">Growth</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Second Row: Theme Distribution Details + Top Contributors by Theme -->
                            <div class="chart-row">
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h3>Theme Distribution Details</h3>
                                        <p>Number of works per theme category with percentages</p>
                                    </div>
                                    <div class="theme-distribution-details" id="themeDistributionDetails">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <div class="chart-card">
                                    <div class="chart-header">
                                        <h3>Top Contributors by Theme</h3>
                                        <p>Top 5 contributors for selected theme</p>
                                    </div>
                                    <div class="theme-select-container">
                                        <div class="theme-select-label">Select Theme:</div>
                                        <select class="theme-select" id="themeSelect">
                                            <!-- Will be populated by JavaScript -->
                                        </select>
                                    </div>
                                    <div class="table-container">
                                        <table class="table" id="themeContributorsTable">
                                            <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Name</th>
                                                    <th>Contributor Since</th>
                                                    <th>Number of Works</th>
                                                </tr>
                                            </thead>
                                            <tbody id="themeContributorsTableBody">
                                                <!-- Top contributors for selected theme will be populated by JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="top-contributors-stats">
                                        <div class="contributor-stat">
                                            <div class="contributor-stat-value" id="totalThemeWorks">0</div>
                                            <div class="contributor-stat-label">Total Works</div>
                                        </div>
                                        <div class="contributor-stat">
                                            <div class="contributor-stat-value" id="avgThemeWorks">0</div>
                                            <div class="contributor-stat-label">Avg/Top 5</div>
                                        </div>
                                        <div class="contributor-stat">
                                            <div class="contributor-stat-value" id="topThemeContributor">0</div>
                                            <div class="contributor-stat-label">Top Contributor</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Activity Report (Initially Hidden) -->
                    <div id="activityReport" style="display: none;">
                        <div class="chart-container">
                            <!-- Third Row: Newly Registered Readers (Lilac) + Pending Submissions (Baby Blue) -->
                            <div class="chart-row">
                                <!-- Newly Registered Users (Lilac Theme) -->
                                <div class="chart-card lilac-table">
                                    <div class="chart-header">
                                        <h3>Newly Registered Users</h3>
                                        <p>Latest 5 users who joined the repository</p>
                                    </div>
                                    <div class="table-container">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No.</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Joined</th>
                                                </tr>
                                            </thead>
                                            <tbody id="newUsersTableBody">
                                                <?php 
                                                $userIndex = 1;
                                                foreach($newUsersData as $user): 
                                                    $joinedText = 'Recently';
                                                    if (isset($user['joined_date']) && $user['joined_date'] != 'Recently') {
                                                        try {
                                                            $joinedDate = new DateTime($user['joined_date']);
                                                            $now = new DateTime();
                                                            $interval = $joinedDate->diff($now);
                                                            
                                                            if ($interval->y > 0) {
                                                                $joinedText = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                                            } elseif ($interval->m > 0) {
                                                                $joinedText = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                                            } elseif ($interval->d > 0) {
                                                                $joinedText = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                            } else {
                                                                $joinedText = 'Today';
                                                            }
                                                        } catch (Exception $e) {
                                                            $joinedText = 'Recently';
                                                        }
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?php echo $userIndex++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo $joinedText; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="view-all-link">
                                        <a href="#">View all users →</a>
                                    </div>
                                </div>
                                
                                <!-- Pending Gurindam Submissions (Baby Blue Theme) -->
                                <div class="chart-card babyblue-table">
                                    <div class="chart-header">
                                        <h3>Pending Gurindam Submissions</h3>
                                        <p>Top 5 submissions awaiting review</p>
                                    </div>
                                    <div class="table-container">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>No.</th>
                                                    <th>Title</th>
                                                    <th>Author</th>
                                                    <th>Submitted</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody id="pendingSubmissionsTableBody">
                                                <?php 
                                                $pendingIndex = 1;
                                                foreach($pendingData as $submission): 
                                                    $submittedText = 'Recently';
                                                    try {
                                                        $submittedDate = new DateTime($submission['submitted_date']);
                                                        $now = new DateTime();
                                                        $interval = $submittedDate->diff($now);
                                                        
                                                        if ($interval->y > 0) {
                                                            $submittedText = $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->m > 0) {
                                                            $submittedText = $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                                        } elseif ($interval->d > 0) {
                                                            $submittedText = $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                                        } else {
                                                            $submittedText = 'Today';
                                                        }
                                                    } catch (Exception $e) {
                                                        $submittedText = 'Recently';
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?php echo $pendingIndex++; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($submission['title']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($submission['author']); ?></td>
                                                    <td><?php echo $submittedText; ?></td>
                                                    <td><span class="status-badge pending"><?php echo ucfirst($submission['status']); ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="view-all-link">
                                        <a href="#">View all pending submissions →</a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Fourth Row: Contributors List (Full Width) -->
                            <div class="chart-row">
                                <div class="chart-card full-width">
                                    <div class="chart-header">
                                        <h2>Contributors List</h2>
                                        <div style="display: flex; gap: 15px; align-items: center;">
                                            <input type="text" class="search-box" placeholder="Search contributors..." id="searchInput">
                                        </div>
                                    </div>
                                    
                                    <div class="table-container">
                                        <table class="table table-striped" id="contributorsTable">
                                            <thead>
                                                <tr>
                                                    <th>No.</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Contributor Since</th>
                                                    <th>Number of Works</th>
                                                </tr>
                                            </thead>
                                            <tbody id="contributorsTableBody">
                                                <!-- Contributor data will be populated by JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
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

    <script>
        // Color mapping for themes
        const themeColors = {
            'Classic': '#9A1F1A',
            'Historical': '#9A1F1A',
            'Religious': '#D4AF37',
            'Spiritual': '#D4AF37',
            'Moral': '#2E294E',
            'Ethical': '#2E294E',
            'Love': '#C62828',
            'Romance': '#C62828',
            'Nature': '#2E7D32',
            'Environment': '#2E7D32',
            'Philosophical': '#0288D1',
            'Other': '#6A1B9A'
        };

        // Data from PHP
        const themeData = <?php echo json_encode($themeData); ?>;
        const monthlyData = <?php echo json_encode($monthlyData); ?>;
        const contributors = <?php echo json_encode($contributorsData); ?>;
        
        // Process theme data for charts
        const processedThemeData = themeData.map(theme => {
            const themeName = theme.themeName;
            // Extract theme key (first part before '/')
            let themeKey = themeName;
            if (themeName.includes('/')) {
                themeKey = themeName.split('/')[0].trim();
            }
            
            // Get color for theme
            let color = '#6A1B9A'; // Default
            for (const [key, value] of Object.entries(themeColors)) {
                if (themeKey.toLowerCase().includes(key.toLowerCase()) || 
                    themeName.toLowerCase().includes(key.toLowerCase())) {
                    color = value;
                    break;
                }
            }
            
            return {
                id: theme.themeID,
                name: themeName,
                key: themeKey,
                value: parseInt(theme.work_count) || 0,
                color: color
            };
        });
        
        // Process monthly data for charts
        const processedMonthlyData = [];
        if (monthlyData && Array.isArray(monthlyData)) {
            monthlyData.forEach(month => {
                // Get color for month (cycling through colors)
                const monthColors = ['#9A1F1A', '#D4AF37', '#2E294E', '#C62828', '#2E7D32', '#0288D1'];
                const monthNum = parseInt(month.month_num) || 1;
                const monthIndex = monthNum - 1;
                const colorIndex = monthIndex % monthColors.length;
                
                processedMonthlyData.push({
                    month: month.month_name || 'Unknown',
                    value: parseInt(month.work_count) || 0,
                    color: monthColors[colorIndex]
                });
            });
        }
        
        // Process contributors data
        const processedContributors = [];
        if (contributors && Array.isArray(contributors)) {
            contributors.forEach(contributor => {
                processedContributors.push({
                    id: contributor.userID,
                    name: contributor.name,
                    email: contributor.email,
                    since: contributor.contributorSince,
                    works: parseInt(contributor.total_works) || 0
                });
            });
        }

        // DOM elements
        const repoReportBtn = document.getElementById('repoReportBtn');
        const activityReportBtn = document.getElementById('activityReportBtn');
        const repositoryReport = document.getElementById('repositoryReport');
        const activityReport = document.getElementById('activityReport');
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('contributorsTableBody');
        const themeSelect = document.getElementById('themeSelect');
        const themeContributorsTableBody = document.getElementById('themeContributorsTableBody');
        const themeDistributionDetails = document.getElementById('themeDistributionDetails');
        
        // Initialize charts and tables
        function initializeCharts() {
            // Check if we have data
            if (!processedThemeData || processedThemeData.length === 0) {
                console.error('No theme data available');
                document.getElementById('themeDonutChart').innerHTML = 
                    '<div class="no-data">No theme data available</div>';
                document.getElementById('themeDistributionDetails').innerHTML = 
                    '<div class="no-data">No theme data available</div>';
            } else {
                createDonutChart('themeDonutChart', processedThemeData);
                createThemeLegend('themeLegend', processedThemeData);
                createThemeDistributionDetails(processedThemeData);
                populateThemeSelect();
                
                // Set first theme as active by default if themes exist
                if (processedThemeData.length > 0) {
                    const firstTheme = processedThemeData[0];
                    themeSelect.value = firstTheme.id;
                    setActiveTheme(firstTheme.key);
                    // Load contributors for first theme
                    updateThemeContributorsTable(firstTheme.id, firstTheme.key);
                }
            }
            
            if (!processedMonthlyData || processedMonthlyData.length === 0) {
                console.error('No monthly data available');
                document.getElementById('monthlyBarChart').innerHTML = 
                    '<div class="no-data">No monthly data available</div>';
            } else {
                createMonthlyBarChart('monthlyBarChart', processedMonthlyData);
                updateMonthlyStats();
            }
            
            if (!processedContributors || processedContributors.length === 0) {
                console.error('No contributors data available');
                tableBody.innerHTML = '<tr><td colspan="5" class="no-data">No contributors found</td></tr>';
            } else {
                renderContributorsTable(processedContributors);
            }
        }
        
        // Create theme distribution details
        function createThemeDistributionDetails(data) {
            const totalWorks = data.reduce((sum, item) => sum + item.value, 0);
            
            themeDistributionDetails.innerHTML = '';
            
            data.forEach(item => {
                const percentage = totalWorks > 0 ? Math.round((item.value / totalWorks) * 100) : 0;
                
                const detailItem = document.createElement('div');
                detailItem.className = 'theme-detail-item';
                detailItem.setAttribute('data-theme', item.key);
                detailItem.setAttribute('data-theme-id', item.id);
                
                detailItem.innerHTML = `
                    <div class="theme-detail-color" style="background-color: ${item.color};"></div>
                    <div class="theme-detail-name">${item.name}</div>
                    <div class="theme-detail-count">${item.value} works</div>
                    <div class="theme-detail-percentage">${percentage}%</div>
                `;
                
                themeDistributionDetails.appendChild(detailItem);
            });
            
            // Add interactivity to theme detail items
            setupThemeDetailInteractivity();
        }
        
        // Populate theme select dropdown
        function populateThemeSelect() {
            themeSelect.innerHTML = '';
            
            processedThemeData.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.setAttribute('data-key', item.key);
                option.textContent = item.name;
                themeSelect.appendChild(option);
            });
        }
        
        // Setup interactivity for theme detail items
        function setupThemeDetailInteractivity() {
            const themeDetailItems = document.querySelectorAll('.theme-detail-item');
            
            themeDetailItems.forEach(item => {
                const themeId = item.getAttribute('data-theme-id');
                const themeKey = item.getAttribute('data-theme');
                
                item.addEventListener('click', function() {
                    handleThemeSelection(themeId, themeKey);
                });
                
                item.addEventListener('mouseenter', function() {
                    const themeKey = this.getAttribute('data-theme');
                    highlightDonutSegment(themeKey);
                });
                
                item.addEventListener('mouseleave', function() {
                    const themeKey = this.getAttribute('data-theme');
                    const activeTheme = document.querySelector('.theme-detail-item.active');
                    
                    if (!activeTheme || activeTheme.getAttribute('data-theme') !== themeKey) {
                        resetDonutHighlight();
                    }
                });
            });
        }
        
        // Handle theme selection
        async function handleThemeSelection(themeId, themeKey) {
            // Update theme select
            themeSelect.value = themeId;
            
            // Fetch top contributors for this theme
            await updateThemeContributorsTable(themeId, themeKey);
            
            // Set active theme
            setActiveTheme(themeKey);
            
            // Highlight donut segment
            highlightDonutSegment(themeKey);
        }
        
        // Fetch top contributors by theme from server
        async function updateThemeContributorsTable(themeId, themeKey) {
            try {
                const response = await fetch(`get_theme_contributors.php?theme_id=${themeId}`);
                const data = await response.json();
                
                // Update table
                themeContributorsTableBody.innerHTML = '';
                
                if (data.error) {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td colspan="4" style="text-align: center; color: var(--text-light); padding: 30px;">
                            ${data.error}
                        </td>
                    `;
                    themeContributorsTableBody.appendChild(row);
                } else if (data.contributors && data.contributors.length > 0) {
                    data.contributors.forEach((contributor, index) => {
                        const row = document.createElement('tr');
                        
                        row.innerHTML = `
                            <td>
                                <span class="rank-badge">${index + 1}</span>
                            </td>
                            <td style="font-weight: 600;">${contributor.name}</td>
                            <td>${formatDate(contributor.contributorSince)}</td>
                            <td>
                                <span class="works-badge">${contributor.theme_works}</span>
                            </td>
                        `;
                        
                        themeContributorsTableBody.appendChild(row);
                    });
                    
                    // Update stats
                    document.getElementById('totalThemeWorks').textContent = data.stats.total_works || 0;
                    document.getElementById('avgThemeWorks').textContent = data.stats.avg_works || 0;
                    document.getElementById('topThemeContributor').textContent = data.stats.top_contributor || 0;
                } else {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td colspan="4" style="text-align: center; color: var(--text-light); padding: 30px;">
                            No contributors found for this theme
                        </td>
                    `;
                    themeContributorsTableBody.appendChild(row);
                    
                    // Reset stats
                    document.getElementById('totalThemeWorks').textContent = 0;
                    document.getElementById('avgThemeWorks').textContent = 0;
                    document.getElementById('topThemeContributor').textContent = 0;
                }
            } catch (error) {
                console.error('Error fetching theme contributors:', error);
                themeContributorsTableBody.innerHTML = `
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-light); padding: 30px;">
                            Error loading data
                        </td>
                    </tr>
                `;
            }
        }
        
        // Set active theme
        function setActiveTheme(themeKey) {
            // Remove active class from all theme items
            document.querySelectorAll('.theme-detail-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelectorAll('.legend-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to selected theme
            document.querySelectorAll(`.theme-detail-item[data-theme="${themeKey}"]`).forEach(item => {
                item.classList.add('active');
            });
            
            document.querySelectorAll(`.legend-item[data-theme="${themeKey}"]`).forEach(item => {
                item.classList.add('active');
            });
        }
        
        // Create donut chart
        function createDonutChart(containerId, data) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            // Clear any existing segments
            const existingSegments = container.querySelectorAll('.donut-base');
            existingSegments.forEach(segment => segment.remove());
            
            // Calculate total
            const total = data.reduce((sum, item) => sum + item.value, 0);
            
            // Update total in center
            document.getElementById('donutTotal').textContent = total;
            
            // Build the conic gradient
            let currentPercent = 0;
            let conicGradient = 'conic-gradient(';
            
            data.forEach((item, index) => {
                const percent = (item.value / total) * 100;
                const start = currentPercent;
                const end = currentPercent + percent;
                
                conicGradient += `${item.color} ${start}% ${end}%`;
                if (index < data.length - 1) {
                    conicGradient += ', ';
                }
                
                currentPercent = end;
            });
            
            conicGradient += ')';
            
            // Create the donut element
            const donutElement = document.createElement('div');
            donutElement.className = 'donut-base';
            donutElement.style.position = 'absolute';
            donutElement.style.top = '0';
            donutElement.style.left = '0';
            donutElement.style.width = '100%';
            donutElement.style.height = '100%';
            donutElement.style.borderRadius = '50%';
            donutElement.style.background = conicGradient;
            donutElement.style.mask = 'radial-gradient(circle at center, transparent 58%, black 58.5%)';
            donutElement.style.webkitMask = 'radial-gradient(circle at center, transparent 58%, black 58.5%)';
            donutElement.style.transition = 'filter 0.3s ease, transform 0.3s ease';
            
            container.appendChild(donutElement);
        }
        
        // Highlight a specific segment in the donut chart
        function highlightDonutSegment(themeKey) {
            const container = document.getElementById('themeDonutChart');
            const donutElement = container.querySelector('.donut-base');
            const themeDataItem = processedThemeData.find(t => t.key === themeKey);
            
            if (donutElement && themeDataItem) {
                // Apply a glowing effect to the segment
                donutElement.style.filter = 'brightness(1.05) drop-shadow(0 0 8px rgba(0, 0, 0, 0.2))';
            }
        }
        
        // Reset donut chart highlight
        function resetDonutHighlight() {
            const container = document.getElementById('themeDonutChart');
            const donutElement = container.querySelector('.donut-base');
            
            if (donutElement) {
                donutElement.style.filter = '';
            }
        }
        
        // Create monthly bar chart (horizontal)
        function createMonthlyBarChart(containerId, data) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            container.innerHTML = '';
            
            data.forEach(item => {
                const barWidth = Math.max(5, (item.value / 30) * 100); // 30 is max expected value
                
                const barContainer = document.createElement('div');
                barContainer.className = 'horizontal-bar-container';
                
                const barLabel = document.createElement('div');
                barLabel.className = 'horizontal-bar-label';
                barLabel.textContent = item.month;
                
                const barWrapper = document.createElement('div');
                barWrapper.className = 'horizontal-bar-wrapper';
                
                const bar = document.createElement('div');
                bar.className = 'horizontal-bar';
                bar.style.width = `${barWidth}%`;
                bar.style.backgroundColor = item.color;
                
                const barValue = document.createElement('div');
                barValue.className = 'horizontal-bar-value';
                barValue.textContent = item.value;
                
                bar.appendChild(barValue);
                barWrapper.appendChild(bar);
                barContainer.appendChild(barLabel);
                barContainer.appendChild(barWrapper);
                container.appendChild(barContainer);
            });
        }
        
        // Create theme legend
        function createThemeLegend(containerId, data) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            container.innerHTML = '';
            
            const totalWorks = data.reduce((sum, item) => sum + item.value, 0);
            
            data.forEach(item => {
                const themeKey = item.key;
                const percentage = totalWorks > 0 ? Math.round((item.value / totalWorks) * 100) : 0;
                
                const legendItem = document.createElement('div');
                legendItem.className = 'legend-item';
                legendItem.setAttribute('data-theme', themeKey);
                legendItem.setAttribute('data-theme-id', item.id);
                
                const colorBox = document.createElement('div');
                colorBox.className = 'legend-color';
                colorBox.style.backgroundColor = item.color;
                
                const label = document.createElement('span');
                label.textContent = `${themeKey} (${percentage}%)`;
                
                legendItem.appendChild(colorBox);
                legendItem.appendChild(label);
                container.appendChild(legendItem);
                
                // Add click event to legend items
                legendItem.addEventListener('click', function() {
                    const themeName = this.getAttribute('data-theme');
                    const themeId = this.getAttribute('data-theme-id');
                    handleThemeSelection(themeId, themeName);
                });
            });
        }
        
        // Update monthly statistics
        function updateMonthlyStats() {
            const total = processedMonthlyData.reduce((sum, item) => sum + item.value, 0);
            const avg = processedMonthlyData.length > 0 ? (total / processedMonthlyData.length).toFixed(1) : 0;
            
            // Calculate growth rate
            let growthRate = '0%';
            if (processedMonthlyData.length >= 2) {
                const current = processedMonthlyData[processedMonthlyData.length - 1].value;
                const previous = processedMonthlyData[processedMonthlyData.length - 2].value;
                if (previous > 0) {
                    const growth = ((current - previous) / previous) * 100;
                    growthRate = (growth > 0 ? '+' : '') + growth.toFixed(0) + '%';
                }
            }
            
            document.getElementById('totalLast6Months').textContent = total;
            document.getElementById('avgPerMonth').textContent = avg;
            document.getElementById('growthRate').textContent = growthRate;
        }
        
        // Format date to readable string
        function formatDate(dateString) {
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
            } catch (e) {
                return dateString;
            }
        }
        
        // Calculate time since contributor joined
        function getTimeSince(dateString) {
            try {
                const joinDate = new Date(dateString);
                const now = new Date();
                const diffTime = Math.abs(now - joinDate);
                const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
                const diffMonths = Math.floor(diffDays / 30);
                const diffYears = Math.floor(diffDays / 365);
                
                if (diffYears > 0) {
                    return `${diffYears} year${diffYears > 1 ? 's' : ''}`;
                } else if (diffMonths > 0) {
                    return `${diffMonths} month${diffMonths > 1 ? 's' : ''}`;
                } else {
                    return `${diffDays} day${diffDays > 1 ? 's' : ''}`;
                }
            } catch (e) {
                return 'Recently';
            }
        }
        
        // Render contributors table (updated with No. column)
        function renderContributorsTable(data) {
            tableBody.innerHTML = '';
            
            data.forEach((contributor, index) => {
                const row = document.createElement('tr');
                
                row.innerHTML = `
                    <td style="font-weight: 600;">${index + 1}</td>
                    <td>
                        <div style="font-weight: 600;">${contributor.name}</div>
                        <div style="font-size: 12px; color: var(--text-light); margin-top: 2px;">
                            Contributor for ${getTimeSince(contributor.since)}
                        </div>
                    </td>
                    <td>${contributor.email}</td>
                    <td>${formatDate(contributor.since)}</td>
                    <td>
                        <span class="works-badge">${contributor.works}</span>
                    </td>
                `;
                
                tableBody.appendChild(row);
            });
        }
        
        // Filter contributors based on search input
        function filterContributors() {
            const searchTerm = searchInput.value.toLowerCase();
            const filtered = processedContributors.filter(contributor => 
                contributor.name.toLowerCase().includes(searchTerm) ||
                contributor.email.toLowerCase().includes(searchTerm)
            );
            
            renderContributorsTable(filtered);
        }
        
        // Switch between report types
        repoReportBtn.addEventListener('click', () => {
            repoReportBtn.classList.add('active');
            activityReportBtn.classList.remove('active');
            repositoryReport.style.display = 'block';
            activityReport.style.display = 'none';
        });
        
        activityReportBtn.addEventListener('click', () => {
            activityReportBtn.classList.add('active');
            repoReportBtn.classList.remove('active');
            activityReport.style.display = 'block';
            repositoryReport.style.display = 'none';
        });
        
        // Search functionality
        searchInput.addEventListener('input', filterContributors);
        
        // Theme select functionality
        themeSelect.addEventListener('change', async function() {
            const themeId = this.value;
            const themeOption = this.options[this.selectedIndex];
            const themeKey = themeOption.getAttribute('data-key');
            
            if (themeKey) {
                await handleThemeSelection(themeId, themeKey);
            }
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initializeCharts);
        
        // Add animation to monthly bars on load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const monthlyBars = document.querySelectorAll('#monthlyBarChart .horizontal-bar');
                monthlyBars.forEach((bar, index) => {
                    const originalWidth = bar.style.width;
                    bar.style.width = '0%';
                    
                    setTimeout(() => {
                        bar.style.transition = 'width 1s ease';
                        bar.style.width = originalWidth;
                    }, index * 150);
                });
            }, 500);
        });

        // Function to logout
        function logout() {
            Swal.fire({
                title: 'Logout?',
                text: "Are you sure you want to logout?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, logout!',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading indicator
                    Swal.fire({
                        title: 'Logging out...',
                        html: `
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <div class="swal2-loader"></div>
                                <p style="margin-top: 20px;">Clearing session and redirecting...</p>
                            </div>
                        `,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        timer: 2000,
                        didOpen: () => {
                            // Redirect after timer
                            setTimeout(() => {
                                window.location.href = 'logout.php';
                            }, 2000);
                        }
                    });
                }
            });
        }

        // Add click event to logout button
        document.addEventListener('DOMContentLoaded', function() {
            const logoutBtn = document.querySelector('.logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', logout);
            }
        });


    </script>



<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</body>
</html>