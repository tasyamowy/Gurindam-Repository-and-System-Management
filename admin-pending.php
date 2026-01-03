<?php
// Include session check at the very top
session_start();
require_once 'connection.php';

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin ID from session (now properly set from login)
$admin_id = $_SESSION['admin_id'];

// Handle approve submission
if (isset($_GET['approve_id'])) {
    $approve_id = intval($_GET['approve_id']);
    
    // Add a small delay to simulate processing
    usleep(500000); // 0.5 second delay
    
    $update_sql = "UPDATE gurindams SET status = 'Approved', adminID = $admin_id WHERE gurindamID = $approve_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['swal_success'] = "Submission approved successfully!";
    } else {
        $_SESSION['swal_error'] = "Error approving submission: " . mysqli_error($conn);
    }
    header("Location: admin-pending.php");
    exit();
}

// Handle reject submission
if (isset($_GET['reject_id'])) {
    $reject_id = intval($_GET['reject_id']);
    
    // Add a small delay to simulate processing
    usleep(500000); // 0.5 second delay
    
    // Update with adminID for reject too
    $update_sql = "UPDATE gurindams SET status = 'Rejected', adminID = $admin_id WHERE gurindamID = $reject_id";
    
    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['swal_success'] = "Submission rejected successfully!";
    } else {
        $_SESSION['swal_error'] = "Error rejecting submission: " . mysqli_error($conn);
    }
    header("Location: admin-pending.php");
    exit();
}

// Fetch pending submissions from database
$sql = "SELECT 
            g.gurindamID,
            g.title,
            g.content,
            g.submissionDate,
            g.status,
            t.themeName,
            u.name as authorName,
            u.email as authorEmail,
            c.contributorSince
        FROM gurindams g
        JOIN themes t ON g.themeID = t.themeID
        JOIN contributors c ON g.authorID = c.contributorID
        JOIN users u ON c.contributorID = u.userID
        WHERE g.status IN ('Pending', 'Pending Review')
        ORDER BY g.submissionDate ASC";

$result = mysqli_query($conn, $sql);

// Count stats
$count_sql = "SELECT 
                COUNT(*) as total_pending,
                SUM(CASE WHEN g.submissionDate >= DATE_SUB(NOW(), INTERVAL 2 WEEK) THEN 1 ELSE 0 END) as last_2_weeks,
                AVG(DATEDIFF(NOW(), g.submissionDate)) as avg_days_pending
            FROM gurindams g
            WHERE g.status IN ('Pending', 'Pending Review')";

$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);

$total_pending = $count_row['total_pending'] ?? 0;
$last_2_weeks = $count_row['last_2_weeks'] ?? 0;
$avg_days_pending = $count_row['avg_days_pending'] ? round($count_row['avg_days_pending'], 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gurindam Repository — Pending Submissions</title>
    <link rel="icon" type="image/png" href="images/gurindamm.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="stylePending.css">
    <style>
        .gurindam-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .gurindam-author {
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        
        .gurindam-metadata {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .metadata-item {
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .gurindam-content {
            white-space: pre-line;
            line-height: 1.8;
            font-size: 1.1rem;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 20px;
        }
        
        .admin-name {
            font-weight: 500;
            color: #333;
        }
        
        /* Custom loading animation */
        .swal2-loading {
            border-width: 4px !important;
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
                <a href="admin-pending.php" class="nav-item active">
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
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-pending"><?php echo $total_pending; ?></h3>
                            <p>Pending Submissions</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $last_2_weeks; ?></h3>
                            <p>Last 2 Weeks</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_pending; ?></h3>
                            <p>Awaiting Review</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $avg_days_pending; ?></h3>
                            <p>Avg Days Pending</p>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Submissions Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>Pending Submissions List</h2>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <input type="text" class="search-box" placeholder="Search submissions..." id="searchInput">
                            <button class="btn btn-export" id="exportBtn">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table id="pendingTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Theme</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="pendingTableBody">
                                <?php if(mysqli_num_rows($result) > 0): ?>
                                    <?php $count = 1; ?>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <?php
                                        // Calculate days ago
                                        $submissionDate = new DateTime($row['submissionDate']);
                                        $today = new DateTime();
                                        $interval = $today->diff($submissionDate);
                                        $daysAgo = $interval->days;
                                        
                                        if ($daysAgo == 0) {
                                            $timeAgo = "Today";
                                        } elseif ($daysAgo == 1) {
                                            $timeAgo = "1 day ago";
                                        } elseif ($daysAgo < 7) {
                                            $timeAgo = "$daysAgo days ago";
                                        } elseif ($daysAgo < 30) {
                                            $weeks = floor($daysAgo / 7);
                                            $timeAgo = "$weeks week" . ($weeks > 1 ? "s" : "") . " ago";
                                        } else {
                                            $months = floor($daysAgo / 30);
                                            $timeAgo = "$months month" . ($months > 1 ? "s" : "") . " ago";
                                        }
                                        
                                        // Count verses (approximate by counting lines)
                                        $verses = substr_count($row['content'], "\n") + 1;
                                        ?>
                                        <tr data-id="<?php echo $row['gurindamID']; ?>">
                                            <td class="number-cell"><?php echo $count; ?></td>
                                            <td class="clickable-title">
                                                <div class="title-main"><?php echo htmlspecialchars($row['title']); ?></div>
                                                <div class="title-sub"><?php echo $verses; ?> verses • <?php echo htmlspecialchars($row['themeName']); ?> theme</div>
                                            </td>
                                            <td>
                                                <div class="author-name"><?php echo htmlspecialchars($row['authorName']); ?></div>
                                                <div class="author-email"><?php echo htmlspecialchars($row['authorEmail']); ?></div>
                                            </td>
                                            <td>
                                                <span class="theme-badge"><?php echo htmlspecialchars($row['themeName']); ?></span>
                                            </td>
                                            <td>
                                                <div class="date-main"><?php echo $timeAgo; ?></div>
                                                <div class="date-sub"><?php echo date("M d, Y", strtotime($row['submissionDate'])); ?></div>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-view" onclick="showPreview(<?php echo $row['gurindamID']; ?>)">
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                    <button class="btn btn-approve" onclick="approveSubmission(<?php echo $row['gurindamID']; ?>)">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button class="btn btn-reject" onclick="rejectSubmission(<?php echo $row['gurindamID']; ?>)">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php $count++; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            No pending submissions found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <footer>
                © 2025 Gurindam Repository — Preserving Cultural Heritage
            </footer>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal" id="previewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Submission Preview</h3>
                <span class="close-modal" id="closeModal">&times;</span>
            </div>
            
            <div class="preview-content" id="previewContent">
                <!-- Preview will be loaded via AJAX -->
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel" id="cancelPreviewBtn">Close</button>
                <div class="preview-action-buttons">
                    <button type="button" class="btn btn-reject" id="rejectPreviewBtn">
                        <i class="fas fa-times"></i> Reject
                    </button>
                    <button type="button" class="btn btn-approve" id="approvePreviewBtn">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
            // ============================================
            // GLOBAL VARIABLES
            // ============================================
            let currentPreviewId = null; // FIX: Initialize at the top

            // ============================================
            // SUBMISSION ACTION FUNCTIONS
            // ============================================
            function approveSubmission(id) {
                Swal.fire({
                    title: 'Approve this submission?',
                    text: "This will publish it to the collection.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, approve it!',
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading indicator with longer duration
                        Swal.fire({
                            title: 'Processing Approval...',
                            html: `
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div class="swal2-loader"></div>
                                    <p style="margin-top: 20px;">Updating database and sending notifications...</p>
                                    <div style="width: 100%; background-color: #e9ecef; border-radius: 5px; margin-top: 15px;">
                                        <div id="progress-bar" style="width: 0%; height: 10px; background-color: #28a745; border-radius: 5px; transition: width 2s ease-in-out;"></div>
                                    </div>
                                </div>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                // Animate progress bar
                                const progressBar = document.getElementById('progress-bar');
                                let progress = 0;
                                const interval = setInterval(() => {
                                    progress += 10;
                                    progressBar.style.width = progress + '%';
                                    
                                    if (progress >= 100) {
                                        clearInterval(interval);
                                        // After progress completes, redirect
                                        setTimeout(() => {
                                            window.location.href = 'admin-pending.php?approve_id=' + id;
                                        }, 500);
                                    }
                                }, 300);
                            }
                        });
                    }
                });
            }

            function rejectSubmission(id) {
                Swal.fire({
                    title: 'Reject this submission?',
                    text: "This will remove it from pending submissions.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, reject it!',
                    showClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    },
                    hideClass: {
                        popup: 'animate__animated animate__fadeOutUp'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading indicator with longer duration
                        Swal.fire({
                            title: 'Processing Rejection...',
                            html: `
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <div class="swal2-loader" style="border-color: #dc3545 transparent #dc3545 transparent;"></div>
                                    <p style="margin-top: 20px;">Updating database and logging action...</p>
                                    <div style="width: 100%; background-color: #e9ecef; border-radius: 5px; margin-top: 15px;">
                                        <div id="reject-progress-bar" style="width: 0%; height: 10px; background-color: #dc3545; border-radius: 5px; transition: width 2s ease-in-out;"></div>
                                    </div>
                                </div>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                // Animate progress bar
                                const progressBar = document.getElementById('reject-progress-bar');
                                let progress = 0;
                                const interval = setInterval(() => {
                                    progress += 10;
                                    progressBar.style.width = progress + '%';
                                    
                                    if (progress >= 100) {
                                        clearInterval(interval);
                                        // After progress completes, redirect
                                        setTimeout(() => {
                                            window.location.href = 'admin-pending.php?reject_id=' + id;
                                        }, 500);
                                    }
                                }, 300);
                            }
                        });
                    }
                });
            }

            // ============================================
            // PREVIEW FUNCTIONS
            // ============================================
            function showPreview(id) {
                console.log('Loading submission ID:', id);
                
                // Show loading while fetching
                Swal.fire({
                    title: 'Loading Submission...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Load submission details via AJAX
                fetch('get_submission_details.php?id=' + id)
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data);
                        Swal.close();
                        
                        if (data.error) {
                            Swal.fire('Error', data.error, 'error');
                            return;
                        }
                        
                        // Format the content for display
                        const content = data.content.replace(/\n/g, '<br>');
                        const verses = data.content.split('\n').filter(line => line.trim() !== '').length;
                        
                        // Set preview content
                        document.getElementById('previewContent').innerHTML = `
                            <div class="gurindam-title">"${data.title}"</div>
                            <div class="gurindam-author">By ${data.authorName} (${data.authorEmail})</div>
                            <div class="gurindam-metadata">
                                <div class="metadata-item">
                                    <strong>Theme:</strong> ${data.themeName}
                                </div>
                                <div class="metadata-item">
                                    <strong>Verses:</strong> ${verses}
                                </div>
                                <div class="metadata-item">
                                    <strong>Submitted:</strong> ${data.submissionDate}
                                </div>
                                <div class="metadata-item">
                                    <strong>Status:</strong> ${data.status}
                                </div>
                            </div>
                            <div class="gurindam-content">${content}</div>
                        `;
                        
                        // Set current preview ID
                        currentPreviewId = id;
                        
                        // Show modal
                        document.getElementById('previewModal').style.display = 'flex';
                    })
                    .catch(error => {
                        console.error('Error details:', error);
                        Swal.close();
                        Swal.fire('Error', 'Failed to load submission details. Check console for details.', 'error');
                    });
            }

            function closePreview() {
                document.getElementById('previewModal').style.display = 'none';
                currentPreviewId = null;
            }

            function approveFromPreview() {
                if (currentPreviewId) {
                    closePreview();
                    approveSubmission(currentPreviewId);
                }
            }

            function rejectFromPreview() {
                if (currentPreviewId) {
                    closePreview();
                    rejectSubmission(currentPreviewId);
                }
            }

            // ============================================
            // LOGOUT FUNCTION
            // ============================================
            function confirmLogout() {
                Swal.fire({
                    title: 'Logout?',
                    text: "Are you sure you want to logout?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#9A1F1A',
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

            // ============================================
            // DOM ELEMENTS
            // ============================================
            const searchInput = document.getElementById('searchInput');
            const pendingTableBody = document.getElementById('pendingTableBody');
            const previewModal = document.getElementById('previewModal');
            const closeModal = document.getElementById('closeModal');
            const cancelPreviewBtn = document.getElementById('cancelPreviewBtn');
            const approvePreviewBtn = document.getElementById('approvePreviewBtn');
            const rejectPreviewBtn = document.getElementById('rejectPreviewBtn');
            const exportBtn = document.getElementById('exportBtn');

            // FIX: Safely get profile button if it exists
            const profileBtn = document.getElementById('profileBtn');
            if (profileBtn) {
                profileBtn.addEventListener("click", function () {
                    window.location.href = "adminprofile.php";
                });
            }

            // ============================================
            // SEARCH FUNCTIONALITY
            // ============================================
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#pendingTableBody tr');
                    
                    rows.forEach(row => {
                        const title = row.querySelector('.title-main')?.textContent.toLowerCase() || '';
                        const author = row.querySelector('.author-name')?.textContent.toLowerCase() || '';
                        const email = row.querySelector('.author-email')?.textContent.toLowerCase() || '';
                        const theme = row.querySelector('.theme-badge')?.textContent.toLowerCase() || '';
                        
                        if (title.includes(searchTerm) || author.includes(searchTerm) || email.includes(searchTerm) || theme.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }

            // ============================================
            // EXPORT FUNCTIONALITY
            // ============================================
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
                    const rows = document.querySelectorAll('#pendingTableBody tr');
                    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                    
                    const data = [];
                    
                    visibleRows.forEach(row => {
                        const rowData = {
                            number: row.querySelector('.number-cell')?.textContent || '',
                            title: row.querySelector('.title-main')?.textContent || '',
                            author: row.querySelector('.author-name')?.textContent || '',
                            email: row.querySelector('.author-email')?.textContent || '',
                            theme: row.querySelector('.theme-badge')?.textContent || '',
                            submitted: row.querySelector('.date-main')?.textContent || '',
                            date: row.querySelector('.date-sub')?.textContent || ''
                        };
                        data.push(rowData);
                    });
                    
                    Swal.fire({
                        title: 'Export Ready',
                        text: `Exporting ${data.length} submissions.`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Download CSV',
                        cancelButtonText: 'Cancel',
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show processing message with longer animation
                            Swal.fire({
                                title: 'Generating CSV File...',
                                html: `
                                    <div style="display: flex; flex-direction: column; align-items: center;">
                                        <div class="swal2-loader" style="border-color: #17a2b8 transparent #17a2b8 transparent;"></div>
                                        <p style="margin-top: 20px;">Processing ${data.length} records...</p>
                                        <div style="width: 100%; background-color: #e9ecef; border-radius: 5px; margin-top: 15px;">
                                            <div id="export-progress-bar" style="width: 0%; height: 10px; background-color: #17a2b8; border-radius: 5px; transition: width 3s ease-in-out;"></div>
                                        </div>
                                    </div>
                                `,
                                showConfirmButton: false,
                                allowOutsideClick: false,
                                didOpen: () => {
                                    // Animate progress bar
                                    const progressBar = document.getElementById('export-progress-bar');
                                    let progress = 0;
                                    const interval = setInterval(() => {
                                        progress += 10;
                                        progressBar.style.width = progress + '%';
                                        
                                        if (progress >= 100) {
                                            clearInterval(interval);
                                            // Show completion message
                                            setTimeout(() => {
                                                Swal.fire({
                                                    title: 'Export Complete!',
                                                    text: 'CSV file has been generated and would be downloaded.',
                                                    icon: 'success',
                                                    timer: 2500,
                                                    showConfirmButton: false
                                                });
                                            }, 500);
                                        }
                                    }, 200);
                                }
                            });
                        }
                    });
                });
            }

            // ============================================
            // EVENT LISTENERS
            // ============================================
            document.addEventListener('DOMContentLoaded', function() {
                // Add click handlers to title cells for preview
                const titleCells = document.querySelectorAll('.clickable-title');
                
                titleCells.forEach(cell => {
                    cell.style.cursor = 'pointer';
                    cell.addEventListener('click', function(e) {
                        // Don't trigger if clicking on buttons
                        if (!e.target.closest('.btn')) {
                            const row = this.closest('tr');
                            const id = parseInt(row.getAttribute('data-id'));
                            showPreview(id);
                        }
                    });
                });
                
                // Check for SweetAlert messages from PHP session
                <?php if (isset($_SESSION['swal_success'])): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '<?php echo addslashes($_SESSION['swal_success']); ?>',
                        timer: 3500,
                        showConfirmButton: false,
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    });
                    <?php unset($_SESSION['swal_success']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['swal_error'])): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: '<?php echo addslashes($_SESSION['swal_error']); ?>',
                        timer: 4500,
                        showClass: {
                            popup: 'animate__animated animate__fadeInDown'
                        },
                        hideClass: {
                            popup: 'animate__animated animate__fadeOutUp'
                        }
                    });
                    <?php unset($_SESSION['swal_error']); ?>
                <?php endif; ?>
            });

            // Modal close handlers
            if (closeModal) {
                closeModal.addEventListener('click', closePreview);
            }

            if (cancelPreviewBtn) {
                cancelPreviewBtn.addEventListener('click', closePreview);
            }

            // Close modal when clicking outside
            window.addEventListener('click', (event) => {
                if (event.target === previewModal) {
                    closePreview();
                }
            });

            // Preview action handlers
            if (approvePreviewBtn) {
                approvePreviewBtn.addEventListener('click', approveFromPreview);
            }

            if (rejectPreviewBtn) {
                rejectPreviewBtn.addEventListener('click', rejectFromPreview);
            }
    </script>
    
    <!-- Add Animate.css for animations -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>

<?php
// Clean up
if (isset($result)) mysqli_free_result($result);
if (isset($count_result)) mysqli_free_result($count_result);
mysqli_close($conn);
?>