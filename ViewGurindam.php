<?php
require_once "reader-connection.php";

// Start session for user authentication
session_start();

// Pagination settings
$results_per_page = 12; // Number of gurindams per page

// Get current page from URL, default to page 1
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Calculate offset for SQL query
$offset = ($page - 1) * $results_per_page;

/* FETCH TOTAL COUNT OF APPROVED GURINDAMS */
$countSql = "SELECT COUNT(*) as total FROM gurindams WHERE status = 'Approved'";
$countResult = mysqli_query($conn, $countSql);
$totalRow = mysqli_fetch_assoc($countResult);
$total_gurindams = $totalRow['total'];

// Calculate total pages
$total_pages = ceil($total_gurindams / $results_per_page);

/* FETCH GURINDAMS FOR CURRENT PAGE */
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
    WHERE g.status = 'Approved'
    ORDER BY g.submissionDate DESC
    LIMIT $results_per_page OFFSET $offset
";

$result = mysqli_query($conn, $sql);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Get all themes for filter
$themeSQL = "SELECT * FROM themes";
$themeResult = mysqli_query($conn, $themeSQL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gurindam Repository</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="viewGurindam.css">

</head>

<body>

<!-- NAVIGATION BAR -->
<nav class="navbar">
    <a href="ViewGurindam.php" class="nav-brand">
        Gurindam.
    </a>
    
    <div class="nav-links">
        <a href="ViewGurindam.php" class="nav-link active">
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
            onclick="location.href='viewprofile.php'">
            <i class="fas fa-user"></i>
        </button>

        <button class="nav-icon-btn logout-icon" title="Logout"
            onclick="confirmLogout()">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</nav>

<!-- HEADER CONTENT -->
<div class="header-content">
    <h1>GURINDAM REPOSITORY</h1>
    <p>Timeless Wisdom • Cultural Heritage • Digital Preservation</p>
</div>

<!-- MAIN CONTENT -->
<div class="container">
    
    <!-- SEARCH & FILTER -->
    <div class="filter-panel">
        <div class="filter-header">
            <h2>Explore Gurindam Collection</h2>
            <p>Search by keyword or filter by theme</p>
            <div class="filter-tags">
                <button class="tag-filter active" onclick="filterByTheme('all')">All</button>
                <?php
                if (mysqli_num_rows($themeResult) > 0):
                    mysqli_data_seek($themeResult, 0); // Reset pointer
                    while ($theme = mysqli_fetch_assoc($themeResult)):
                        $themeName = htmlspecialchars($theme['themeName']);
                        echo "<button class='tag-filter' onclick=\"filterByTheme('$themeName')\">$themeName</button>";
                    endwhile;
                endif;
                ?>
            </div>
        </div>

        <div class="filter-grid">
            <div class="filter-row">
                <label>Search Gurindam</label>
                <div class="filter-input-group">
                    <input type="text" id="searchInput" placeholder="Enter title, author, or keyword...">
                    <button class="action-btn search-btn" onclick="searchGurindam()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>

            <div class="filter-row">
                <label>Sort By</label>
                <div class="filter-input-group">
                    <select id="sortSelect" onchange="sortGurindam()">
                        <option value="title_asc">Title A-Z</option>
                        <option value="title_desc">Title Z-A</option>
                        <option value="author">Author Name</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- GURINDAM LIST -->
    <div id="gurindamResults">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="gurindam-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $submissionDate = date('M d, Y', strtotime($row['submissionDate']));
                    $gurindamID = $row['gurindamID'];
                ?>
                    <div class="gurindam-card" data-theme="<?= strtolower($row['themeName']); ?>">
                        <div class="card-header">
                            <h3><?= htmlspecialchars($row['title']); ?></h3>
                            <span class="card-theme"><?= htmlspecialchars($row['themeName']); ?></span>
                            <div class="card-author">
                                <div class="author-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span>By <?= htmlspecialchars($row['authorName'] ?? 'Anonymous'); ?></span>
                            </div>
                        </div>
                        <div class="card-content">
                            <p><?= nl2br(htmlspecialchars($row['content'])); ?></p>
                        </div>
                        <div class="card-meta">
                            <div class="meta-date">
                                <span class="date-label">Submitted On</span>
                                <span class="date-value"><?= $submissionDate; ?></span>
                            </div>
                            <a href="gurindamContent.php?id=<?= $gurindamID ?>" class="read-btn">
                                <i class="fas fa-book-reader"></i> Read Full
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- PAGINATION CONTROLS -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1" class="page-link first" title="First Page">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="?page=<?= $page - 1 ?>" class="page-link prev" title="Previous Page">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <div class="page-numbers">
                        <?php 
                        // Show page numbers
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1) {
                            echo '<span class="page-dots">...</span>';
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                            $active_class = ($i == $page) ? 'active' : '';
                        ?>
                            <a href="?page=<?= $i ?>" class="page-link number <?= $active_class ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; 
                        
                        if ($end_page < $total_pages) {
                            echo '<span class="page-dots">...</span>';
                        }
                        ?>
                    </div>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="page-link next" title="Next Page">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="?page=<?= $total_pages ?>" class="page-link last" title="Last Page">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="page-info">
                    <p>Page <?= $page ?> of <?= $total_pages ?> | Showing <?= mysqli_num_rows($result) ?> of <?= $total_gurindams ?> gurindams</p>
                </div>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-book-open"></i>
                <h3>No Gurindam Available</h3>
                <p>Check back later or submit your own Gurindam!</p>
                <?php if ($isLoggedIn): ?>
                    <a href="submit.php" class="read-btn">
                        <i class="fas fa-plus-circle"></i> Submit Gurindam
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<footer>
    <p>© 2025 Gurindam Repository & Management System | Celebrating Traditional Wisdom</p>
</footer>

<script>
function searchGurindam() {
    const keyword = document.getElementById("searchInput").value.toLowerCase();
    const cards = document.querySelectorAll(".gurindam-card");
    let found = 0;

    cards.forEach(card => {
        if (card.innerText.toLowerCase().includes(keyword)) {
            card.style.display = "block";
            found++;
        } else {
            card.style.display = "none";
        }
    });

    if (found === 0) {
        showNoResults();
    }
}

function filterByTheme(theme) {
    const cards = document.querySelectorAll(".gurindam-card");
    const buttons = document.querySelectorAll(".tag-filter");
    
    // Update active button
    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase() === theme.toLowerCase() || 
            (theme === 'all' && btn.textContent.toLowerCase() === 'all')) {
            btn.classList.add('active');
        }
    });
    
    let found = 0;
    cards.forEach(card => {
        if (theme === 'all' || card.dataset.theme === theme.toLowerCase()) {
            card.style.display = "block";
            found++;
        } else {
            card.style.display = "none";
        }
    });
    
    if (found === 0) {
        showNoResults();
    }
}

function sortGurindam() {
    const sortBy = document.getElementById("sortSelect").value;
    const container = document.querySelector(".gurindam-grid");
    const cards = Array.from(document.querySelectorAll(".gurindam-card"));
    
    cards.sort((a, b) => {
        const titleA = a.querySelector('h3').textContent.toLowerCase();
        const titleB = b.querySelector('h3').textContent.toLowerCase();
        const authorA = a.querySelector('.card-author span').textContent.toLowerCase();
        const authorB = b.querySelector('.card-author span').textContent.toLowerCase();
        
        switch(sortBy) {
            case 'title_asc':
                return titleA.localeCompare(titleB);
            case 'title_desc':
                return titleB.localeCompare(titleA);
            case 'author':
                return authorA.localeCompare(authorB);
            default:
                return 0;
        }
    });
    
    // Reappend sorted cards
    cards.forEach(card => container.appendChild(card));
}

function showNoResults() {
    const resultsDiv = document.getElementById("gurindamResults");
    resultsDiv.innerHTML = `
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>No Gurindam Found</h3>
            <p>Try different keywords or browse all themes to discover content.</p>
            <button class="read-btn" onclick="location.href='ViewGurindam.php'">
                <i class="fas fa-redo"></i> Show All
            </button>
        </div>
    `;
}

function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'login.php';
    }
}
</script>

</body>
</html>