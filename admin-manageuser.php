<?php
session_start();
require_once 'connection.php';

if (!isset($_SESSION['admin_logged_in']) || !isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();}

// Handle delete contributor
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // First delete from contributors table
    $delete_sql1 = "DELETE FROM contributors WHERE contributorID = $delete_id";
    
    if (mysqli_query($conn, $delete_sql1)) {
        // Then delete from users table
        $delete_sql2 = "DELETE FROM users WHERE userID = $delete_id";
        
        if (mysqli_query($conn, $delete_sql2)) {
            // Store success message in session
            $_SESSION['success_message'] = "Contributor deleted successfully!";
            header("Location: admin-manageuser.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error deleting user from database";
            header("Location: admin-manageuser.php");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Error deleting contributor from database";
        header("Location: admin-manageuser.php");
        exit();
    }
}

// Handle add contributor form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_contributor'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']); // NO HASHING
    $bio = !empty($_POST['bio']) ? mysqli_real_escape_string($conn, $_POST['bio']) : 'This contributor does not set bio.';
    $contributorSince = $_POST['contributorSince'];
    
    // Check if email already exists
    $check_sql = "SELECT userID FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error_message'] = "Email already exists!";
    } else {
        // Insert into users table WITH ROLE SET TO 'contributor'  <-- ONLY CHANGE HERE
        $user_sql = "INSERT INTO users (name, email, password, role) 
                    VALUES ('$name', '$email', '$password', 'contributor')";
        
        if (mysqli_query($conn, $user_sql)) {
            $user_id = mysqli_insert_id($conn);
            
            // Insert into contributors table
            $contributor_sql = "INSERT INTO contributors (contributorID, bio, contributorSince) 
                               VALUES ($user_id, '$bio', '$contributorSince')";
            
            if (mysqli_query($conn, $contributor_sql)) {
                $_SESSION['success_message'] = "Contributor added successfully!";
                header("Location: admin-manageuser.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Error adding to contributors: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['error_message'] = "Error adding user: " . mysqli_error($conn);
        }
    }
}

// Fetch contributors from database
$sql = "SELECT 
            u.userID,
            u.name,
            u.email,
            c.bio,
            c.contributorSince
        FROM contributors c
        JOIN users u ON u.userID = c.contributorID
        ORDER BY c.contributorSince DESC";

$result = mysqli_query($conn, $sql);

// Count total contributors
$count_sql = "SELECT COUNT(*) AS total FROM contributors";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_contributors = $count_row['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gurindam Repository — Admin Dashboard</title>
    <link rel="icon" type="image/png" href="images/gurindamm.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="styleManageUser.css">
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
                <a href="admin-manageuser.php" class="nav-item active">
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
        
        <!-- Main Content -->
        <div class="main-content">
        <!-- Header -->
         <header class="header">
            <h1>Manage User</h1>
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
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-contributors"><?php echo $total_contributors; ?></h3>
                            <p>Total Contributors</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="stat-info">
                            <h3>142</h3>
                            <p>Total Works</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-info">
                            <h3>24</h3>
                            <p>Featured Works</p>
                        </div>
                    </div>
                </div>
                
                <!-- User Management Card -->
                <div class="card">
                    <div class="card-header">
                        <h2>Contributors List</h2>
                        <div style="display: flex; gap: 15px; align-items: center;">
                            <input type="text" class="search-box" placeholder="Search contributors..." id="searchInput">
                            <button class="btn btn-add" id="addContributorBtn">
                                <i class="fas fa-plus"></i> Add Contributor
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table id="contributorsTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Bio</th>
                                    <th>Contributor Since</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="contributorsTableBody">
                                <?php if(mysqli_num_rows($result) > 0): ?>
                                    <?php $count = 1; ?>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                        <?php
                                        // Calculate duration
                                        $years = floor((time() - strtotime($row['contributorSince'])) / 31536000);
                                        if ($years > 0) {
                                            $duration = "Contributor for $years year" . ($years > 1 ? "s" : "");
                                        } else {
                                            $months = floor((time() - strtotime($row['contributorSince'])) / 2592000);
                                            $duration = "Contributor for $months month" . ($months > 1 ? "s" : "");
                                        }
                                        ?>
                                        <tr>
                                            <td class="number-cell"><?php echo $count; ?></td>
                                            <td>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></div>
                                                <div style="font-size: 12px; color: var(--text-light); margin-top: 2px;">
                                                    <?php echo $duration; ?>
                                                </div>
                                            </td>
                                            <td class="email-cell"><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td class="bio-cell" title="<?php echo htmlspecialchars($row['bio']); ?>">
                                                <?php echo htmlspecialchars($row['bio']); ?>
                                            </td>
                                            <td><?php echo date("M d, Y", strtotime($row['contributorSince'])); ?></td>
                                            <td>
                                                <button class="btn btn-remove" 
                                                        onclick="deleteContributor(<?php echo $row['userID']; ?>)">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        <?php $count++; ?>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            No contributors found in the database.
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
    
    <!-- Add Contributor Modal -->
    <div class="modal" id="addContributorModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Contributor</h3>
                <span class="close-modal" id="closeModal">&times;</span>
            </div>
            
            <form method="POST" action="admin-manageuser.php" id="contributorForm">
                <div class="form-group">
                    <label for="contributorName">Name *</label>
                    <input type="text" id="contributorName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="contributorEmail">Email Address *</label>
                    <input type="email" id="contributorEmail" name="email" required>
                </div>

                <div class="form-group">
                    <label for="contributorPassword">Password *</label>
                    <input type="password" id="contributorPassword" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="contributorBio">Bio (Leave blank for default)</label>
                    <textarea id="contributorBio" name="bio" placeholder="Short bio about the contributor..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="contributorSince">Contributor Since *</label>
                    <input type="date" id="contributorSince" name="contributorSince" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-cancel" id="cancelBtn">Cancel</button>
                    <button type="submit" name="add_contributor" class="btn btn-add">Add Contributor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Function to handle delete with confirmation using SweetAlert
        function deleteContributor(userId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to delete URL
                    window.location.href = 'admin-manageuser.php?delete_id=' + userId;
                }
            });
        }
        
        // Check for session messages and show SweetAlert
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['success_message'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo addslashes($_SESSION['success_message']); ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?php echo addslashes($_SESSION['error_message']); ?>',
                    timer: 4000
                });
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
        });
    </script>
    
    <script>
        // JavaScript for modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const addContributorBtn = document.getElementById('addContributorBtn');
            const addContributorModal = document.getElementById('addContributorModal');
            const closeModal = document.getElementById('closeModal');
            const cancelBtn = document.getElementById('cancelBtn');
            const contributorForm = document.getElementById('contributorForm');
            const searchInput = document.getElementById('searchInput');
            
            // Search functionality
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#contributorsTableBody tr');
                    
                    rows.forEach(row => {
                        const name = row.cells[1].textContent.toLowerCase();
                        const email = row.cells[2].textContent.toLowerCase();
                        const bio = row.cells[3].textContent.toLowerCase();
                        
                        if (name.includes(searchTerm) || email.includes(searchTerm) || bio.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
            
            // Open modal
            if (addContributorBtn && addContributorModal) {
                addContributorBtn.addEventListener('click', () => {
                    addContributorModal.style.display = 'flex';
                    // Set default date to today
                    const dateInput = document.getElementById('contributorSince');
                    if (dateInput) {
                        dateInput.valueAsDate = new Date();
                    }
                });
            }
            
            // Close modal with X button
            if (closeModal && addContributorModal) {
                closeModal.addEventListener('click', () => {
                    addContributorModal.style.display = 'none';
                    if (contributorForm) {
                        contributorForm.reset();
                    }
                });
            }
            
            // Close modal with Cancel button
            if (cancelBtn && addContributorModal) {
                cancelBtn.addEventListener('click', () => {
                    addContributorModal.style.display = 'none';
                    if (contributorForm) {
                        contributorForm.reset();
                    }
                });
            }
            
            // Form validation
            if (contributorForm) {
                contributorForm.addEventListener('submit', function(event) {
                    const password = document.getElementById('contributorPassword').value;
                    
                    // Simple password validation
                    if (password.length < 8) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Weak Password',
                            text: 'Password must be at least 8 characters long!'
                        });
                        event.preventDefault();
                        return;
                    }
                    
                    // Check for at least one uppercase letter, one lowercase letter, and one number
                    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
                    if (!passwordRegex.test(password)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Password Requirements',
                            html: 'Password must contain:<br>• At least 8 characters<br>• One uppercase letter<br>• One lowercase letter<br>• One number'
                        });
                        event.preventDefault();
                        return;
                    }
                    
                    // If validation passes, form will submit normally
                    addContributorModal.style.display = 'none';
                });
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', (event) => {
                const modal = document.getElementById('addContributorModal');
                if (modal && event.target === modal) {
                    modal.style.display = 'none';
                    if (contributorForm) {
                        contributorForm.reset();
                    }
                }
            });
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
</body>
</html>

<?php
// Clean up
if (isset($result)) mysqli_free_result($result);
if (isset($count_result)) mysqli_free_result($count_result);
mysqli_close($conn);
?>