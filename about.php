<?php
require_once "reader-connection.php";

// Start session for user authentication
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>About - Gurindam Repository</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="viewGurindam.css">
<link rel="stylesheet" href="about.css">

</head>

<body>

<!-- NAVIGATION BAR -->
<nav class="navbar">
    <a href="index.php" class="nav-brand">
        <i class="fas fa-book-open"></i> Gurindam.
    </a>
    
    <div class="nav-links">
        <a href="ViewGurindam.php" class="nav-link">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="featured.php" class="nav-link">
            <i class="fas fa-star"></i> Featured
        </a>
        <a href="about.php" class="nav-link active">
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
<div class="page-header">
    <h1>About Our Repository</h1>
    <p>Preserving Cultural Wisdom for Future Generations</p>
</div>

<!-- MAIN CONTENT -->
<div class="container">
    <div class="about-content">
        
        <!-- MISSION SECTION -->
        <section class="mission-section">
            <div class="mission-icon">
                <i class="fas fa-bullseye"></i>
            </div>
            <h2>Our Mission</h2>
            <p>
                The Gurindam Repository is dedicated to preserving and promoting the rich cultural heritage 
                of traditional Malay wisdom through digital means. We believe that the timeless teachings 
                of Gurindam hold valuable lessons for modern society and deserve to be accessible to 
                everyone, everywhere.
            </p>
        </section>

        <!-- FEATURES SECTION -->
        <section class="features-section">
            <h2 class="section-title">What We Offer</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3>Digital Preservation</h3>
                    <p>Securely store and archive Gurindam collections using modern digital technology to prevent loss of cultural heritage.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Easy Discovery</h3>
                    <p>Powerful search and filter tools to help you find Gurindam based on themes, keywords, or categories.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Community Contributions</h3>
                    <p>Anyone can submit Gurindam, creating a living repository that grows with community participation.</p>
                </div>
                
                
            </div>
        </section>

        <!-- STATS SECTION -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>500+</h3>
                    <p>Gurindam Collections</p>
                </div>
                <div class="stat-item">
                    <h3>15+</h3>
                    <p>Themes & Categories</p>
                </div>
                <div class="stat-item">
                    <h3>1,200+</h3>
                    <p>Active Users</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>Accessibility</p>
                </div>
            </div>
        </section>

        <!-- TEAM SECTION -->
        <section class="team-section">
            <h2 class="section-title">Our Team</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Dr. Ahmad Rizal</h3>
                    <div class="role">Cultural Historian</div>
                    <p>Expert in Malay literature with 20+ years of experience in cultural preservation.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3>Siti Norhaliza</h3>
                    <div class="role">Digital Archivist</div>
                    <p>Specializes in digital preservation and database management for cultural artifacts.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-avatar">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h3>Prof. Tan Sri Omar</h3>
                    <div class="role">Literary Consultant</div>
                    <p>Former professor of Malay Studies with extensive knowledge in traditional poetry.</p>
                </div>
            </div>
        </section>

        <!-- CONTACT SECTION -->
        <section class="contact-section">
            <h2>Contact Us</h2>
            <p>Have questions or suggestions? We'd love to hear from you.</p>
            <div class="contact-info">
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <p>info@gurindamrepo.org</p>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <p>+603-1234 5678</p>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <p>Kuala Lumpur, Malaysia</p>
                </div>
            </div>
        </section>

        <!-- CTA SECTION -->
        <section class="cta-section">
            <h2>Join Our Community</h2>
            <p>Be part of this important cultural preservation effort. Whether you want to contribute Gurindam, conduct research, or simply explore our collection, there's a place for you here.</p>
            <div class="cta-buttons">
                <button class="cta-btn primary-cta" onclick="location.href='login.php'">
                    <i class="fas fa-plus-circle"></i> Submit Gurindam
                </button>
                <button class="cta-btn secondary-cta" onclick="location.href='viewGurindam.php'">
                    <i class="fas fa-book-open"></i> Explore Collection
                </button>
                <?php if (!$isLoggedIn): ?>
                <button class="cta-btn secondary-cta" onclick="location.href='register.php'">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
                <?php endif; ?>
            </div>
        </section>

    </div>
</div>

<footer>
    <p>© 2025 Gurindam Repository & Management System | Preserving Cultural Heritage Through Digital Innovation</p>
</footer>

</body>
</html>