<?php
require_once "reader-connection.php";

// Start session for user authentication
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);

/* FETCH FEATURED GURINDAMS */
// First check if columns exist, add them if they don't
$checkColumnSQL = "SHOW COLUMNS FROM gurindams LIKE 'isFeatured'";
$columnResult = mysqli_query($conn, $checkColumnSQL);

if (mysqli_num_rows($columnResult) == 0) {
    $addColumnSQL = "ALTER TABLE gurindams ADD COLUMN isFeatured TINYINT(1) DEFAULT 0";
    mysqli_query($conn, $addColumnSQL);
    
    // Also add featuredDate column
    $addDateColumnSQL = "ALTER TABLE gurindams ADD COLUMN featuredDate DATE";
    mysqli_query($conn, $addDateColumnSQL);
    
    // Mark some gurindams as featured for demonstration
    $updateFeaturedSQL = "
        UPDATE gurindams 
        SET isFeatured = 1, 
            featuredDate = submissionDate
        WHERE gurindamID IN (1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23, 25, 27, 29)
    ";
    mysqli_query($conn, $updateFeaturedSQL);
}

/* FETCH FEATURED GURINDAMS */
$sql = "
    SELECT 
        g.gurindamID,
        g.title,
        g.content,
        g.submissionDate,
        g.status,
        t.themeName,
        u.name as authorName,
        g.featuredDate
    FROM gurindams g
    LEFT JOIN themes t ON g.themeID = t.themeID
    LEFT JOIN contributors c ON g.authorID = c.contributorID
    LEFT JOIN users u ON c.contributorID = u.userID
    WHERE g.status = 'Approved' 
    AND g.isFeatured = 1
    ORDER BY g.featuredDate DESC, g.submissionDate DESC
    LIMIT 20
";

$result = mysqli_query($conn, $sql);

// Get featured count
$countSql = "SELECT COUNT(*) as total FROM gurindams WHERE isFeatured = 1 AND status = 'Approved'";
$countResult = mysqli_query($conn, $countSql);
$countRow = mysqli_fetch_assoc($countResult);
$totalFeatured = $countRow['total'];

// Get featured users count
$userSql = "SELECT COUNT(DISTINCT authorID) as users FROM gurindams WHERE isFeatured = 1 AND status = 'Approved'";
$userResult = mysqli_query($conn, $userSql);
$userRow = mysqli_fetch_assoc($userResult);
$totalFeaturedUsers = $userRow['users'];

// Get popular themes for featured section
$themeSQL = "
    SELECT DISTINCT t.themeName 
    FROM themes t 
    JOIN gurindams g ON t.themeID = g.themeID 
    WHERE g.isFeatured = 1 AND g.status = 'Approved' 
    ORDER BY t.themeName
";
$themeResult = mysqli_query($conn, $themeSQL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Featured Gurindam - Gurindam Repository</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="viewGurindam.css">
<link rel="stylesheet" href="featured.css">

</head>

<body>

<!-- NAVIGATION BAR -->
<nav class="navbar">
    <a href="index.php" class="nav-brand">
        <i class="fas fa-book-open"></i> Gurindam.
    </a>
    
    <div class="nav-links">
        <a href="viewGurindam.php" class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="featured.php" class="nav-link active">
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

<!-- PAGE HEADER -->
<div class="featured-header">
    <h1>Featured Gurindam</h1>
    <p>Discover the most exceptional and inspiring Gurindam from our collection, selected for their wisdom, cultural significance, and artistic expression.</p>
    <div class="featured-badge">
        <i class="fas fa-star"></i>
        <span><?php echo $totalFeatured; ?> Featured Collections</span>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="container">
    
    <!-- STATISTICS -->
    <div class="featured-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-crown"></i>
            </div>
            <h3><?php echo $totalFeatured; ?></h3>
            <p>Featured Gurindam</p>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <h3><?php echo $totalFeaturedUsers; ?></h3>
            <p>Featured Authors</p>
        </div>
    
    </div>

    <!-- SEARCH & FILTER -->
    <div class="filter-panel">
        <div class="filter-header">
            <h2>Find Featured Gurindam</h2>
            <p>Search by keyword or filter by theme</p>
            <div class="filter-tags">
                <button class="tag-filter active" onclick="filterByTheme('all')">All</button>
                <?php
                if (mysqli_num_rows($themeResult) > 0):
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
                <label>Search Featured Gurindam</label>
                <div class="filter-input-group">
                    <input type="text" id="searchInput" placeholder="Enter title, author, or keyword...">
                    <button class="action-btn search-btn" onclick="searchFeatured()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>

            <div class="filter-row">
                <label>Sort By</label>
                <div class="filter-input-group">
                    <select id="sortSelect" onchange="sortFeatured()">
                        <option value="title_asc">Title A-Z</option>
                        <option value="title_desc">Title Z-A</option>
                        <option value="author">Author Name</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- FEATURED GURINDAM LIST -->
    <div id="featuredResults">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <div class="gurindam-grid">
                <?php while ($row = mysqli_fetch_assoc($result)): 
                    $featuredDate = $row['featuredDate'] ? date('M d, Y', strtotime($row['featuredDate'])) : date('M d, Y', strtotime($row['submissionDate']));
                ?>
                    <div class="featured-card" data-theme="<?= strtolower($row['themeName']); ?>">
                        <div class="featured-badge-top">
                            <i class="fas fa-star"></i>
                            Featured
                        </div>
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
                                <span class="date-label">Featured On</span>
                                <span class="date-value"><?= $featuredDate; ?></span>
                            </div>
                            <a class="read-btn" href="gurindamContent.php?id=<?= $row['gurindamID']; ?>">
                                <i class="fas fa-book-reader"></i> Read Full
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-star"></i>
                <h3>No Featured Gurindam Yet</h3>
                <p>Check back later to see featured collections, or submit your own Gurindam for consideration!</p>
                <button class="read-btn" onclick="location.href='submit.php'">
                    <i class="fas fa-plus-circle"></i> Submit Gurindam
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- FEATURED CRITERIA -->
    <div class="criteria-section">
        <h2>Featured Selection Criteria</h2>
        <div class="criteria-grid">
            <div class="criteria-item">
                <i class="fas fa-lightbulb"></i>
                <h4>Wisdom & Insight</h4>
                <p>Gurindam that offer profound life lessons and philosophical depth.</p>
            </div>
            <div class="criteria-item">
                <i class="fas fa-language"></i>
                <h4>Cultural Significance</h4>
                <p>Pieces that preserve and celebrate traditional Malay heritage.</p>
            </div>
            <div class="criteria-item">
                <i class="fas fa-pen-fancy"></i>
                <h4>Artistic Expression</h4>
                <p>Exceptional use of language, rhythm, and poetic structure.</p>
            </div>
            <div class="criteria-item">
                <i class="fas fa-users"></i>
                <h4>Community Impact</h4>
                <p>Gurindam that resonate with and inspire our community members.</p>
            </div>
        </div>
    </div>

</div>

<footer>
    <p>© 2025 Gurindam Repository & Management System | Celebrating Excellence in Traditional Wisdom</p>
</footer>

<script>
function searchFeatured() {
    const keyword = document.getElementById("searchInput").value.toLowerCase();
    const cards = document.querySelectorAll(".featured-card");
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
    const cards = document.querySelectorAll(".featured-card");
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

function sortFeatured() {
    const sortBy = document.getElementById("sortSelect").value;
    const container = document.querySelector(".gurindam-grid");
    const cards = Array.from(document.querySelectorAll(".featured-card"));
    
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

function readFeaturedGurindam(button) {
    const card = button.closest('.featured-card');
    const title = card.querySelector('h3').textContent;
    const theme = card.querySelector('.card-theme').textContent;
    const author = card.querySelector('.card-author span').textContent;
    const content = card.querySelector('.card-content p').textContent;
    const featuredDate = card.querySelector('.date-value').textContent;
    
    alert(
        `✨ FEATURED GURINDAM ✨\n\n` +
        `Title: ${title}\n` +
        `Theme: ${theme}\n` +
        `Author: ${author}\n` +
        `Featured: ${featuredDate}\n\n` +
        `Content:\n${content}`
    );
}

function showNoResults() {
    const resultsDiv = document.getElementById("featuredResults");
    resultsDiv.innerHTML = `
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>No Featured Gurindam Found</h3>
            <p>Try different keywords or browse all themes to discover featured content.</p>
            <button class="read-btn" onclick="location.href='index.php'">
                <i class="fas fa-home"></i> Back to Home
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