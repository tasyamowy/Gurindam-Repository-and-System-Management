<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gurindam Login</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="login.css">

</head>

<body>

<div class="container">
    <!-- LEFT HERO PANEL -->
    <div class="hero-panel">
        <div class="hero-content">
            <div class="brand">
                <div class="brand-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="brand-text">GURINDAM<span>.</span></div>
            </div>
            
            <h1 class="hero-title">Malay Poetry<br>Digital Archive</h1>
            <p class="hero-subtitle">Preserving centuries of traditional Gurindam poetry through modern technology.</p>
        </div>
        
        <div class="poem-card">
            <p class="poem-text">Barang siapa mengenal diri,<br>Maka mengenal Tuhan yang bahari.</p>
        </div>
    </div>

    <!-- RIGHT COMPACT LOGIN PANEL -->
    <div class="login-panel">
        <div class="login-header">
            <h2 class="login-title">Access Portal</h2>
            <p class="login-subtitle">Select your role to continue to the Gurindam repository system</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <!-- Compact Role Selector -->
        <div class="role-selector">
            <div class="role-option active" onclick="showLoginForm('reader')">
                <i class="fas fa-user-reader"></i> Reader
            </div>
            <div class="role-option" onclick="showLoginForm('contributor')">
                <i class="fas fa-feather-alt"></i> Contributor
            </div>
        </div>

        <!-- READER LOGIN FORM -->
        <form id="reader-form" class="login-form active" action="login_process.php" method="POST">
            <input type="hidden" name="role" value="reader">
            
            <div class="role-info">
                <strong>Reader Access</strong> Browse, search, and read from our extensive collection of Gurindam poetry.
            </div>
            
            <div class="form-group">
                <label for="reader-email">EMAIL ADDRESS</label>
                <input type="email" id="reader-email" name="email" placeholder="reader@example.com" required>
            </div>
            
            <div class="form-group">
                <label for="reader-password">PASSWORD</label>
                <input type="password" id="reader-password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i> ENTER AS READER
            </button>
            
            <div class="register-container">
                <p class="register-text">Don't have a reader account?</p>
                <button type="button" class="register-btn" onclick="showRegistrationForm()">
                    <i class="fas fa-user-plus"></i> REGISTER AS READER
                </button>
            </div>
        </form>

        <!-- CONTRIBUTOR LOGIN FORM -->
        <form id="contributor-form" class="login-form" action="login_process.php" method="POST">
            <input type="hidden" name="role" value="contributor">
            
            <div class="role-info contributor">
                <strong>Contributor Access</strong> Submit, edit, and manage your Gurindam submissions in our repository.
            </div>
            
            <div class="form-group">
                <label for="contributor-email">EMAIL ADDRESS</label>
                <input type="email" id="contributor-email" name="email" placeholder="contributor@example.com" required>
            </div>
            
            <div class="form-group">
                <label for="contributor-password">PASSWORD</label>
                <input type="password" id="contributor-password" name="password" placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="login-btn contributor">
                <i class="fas fa-feather-alt"></i> ENTER AS CONTRIBUTOR
            </button>
            
            <div class="register-container">
                <p class="register-text" style="color: #D67D77; font-size: 10px;">
                    <i class="fas fa-info-circle"></i> Contributor accounts require administrator approval
                </p>
            </div>
        </form>

        <!-- REGISTRATION FORM (READER ONLY) -->
        <form id="registration-form" class="registration-form" action="register.php" method="POST">
            <?php if (isset($_SESSION['errors'])): ?>
                <div class="error-message">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <?php echo htmlspecialchars($error); ?><br>
                    <?php endforeach; ?>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="reg-name">FULL NAME</label>
                <input type="text" id="reg-name" name="name"
                    placeholder="Enter your full name"
                    value="<?php echo $_SESSION['old_name'] ?? ''; ?>"
                    required>
            </div>
            
            <div class="form-group">
                <label for="reg-email">EMAIL ADDRESS</label>
                <input type="email" id="reg-email" name="email"
                    placeholder="your.email@example.com"
                    value="<?php echo $_SESSION['old_email'] ?? ''; ?>"
                    required>
            </div>
            
            <div class="form-group">
                <label for="reg-password">PASSWORD</label>
                <input type="password" id="reg-password" name="password" placeholder="Minimum 8 characters" required minlength="8">
            </div>
            
            <div class="form-group">
                <label for="reg-confirm-password">CONFIRM PASSWORD</label>
                <input type="password" id="reg-confirm-password" name="confirm_password" placeholder="Re-enter your password" required>
            </div>
            
            <button type="submit" class="register-submit-btn">
                <i class="fas fa-user-check"></i> CREATE READER ACCOUNT
            </button>
            
            <div class="back-to-login">
                <button type="button" class="back-btn" onclick="backToLogin()">
                    <i class="fas fa-arrow-left"></i> BACK TO LOGIN
                </button>
            </div>
        </form>

       
    </div>
</div>

<script>
function showLoginForm(role) {
    document.querySelectorAll('.login-form, .registration-form').forEach(form => {
        form.classList.remove('active');
    });

    document.querySelectorAll('.role-option').forEach(option => {
        option.classList.remove('active');
    });

    document.getElementById(role + '-form').classList.add('active');
    document.querySelector('.role-option[onclick*="' + role + '"]').classList.add('active');

    // REMOVE register mode
    document.querySelector('.login-panel').classList.remove('register-mode');

    document.querySelector('.login-title').textContent = 'Access Portal';
    document.querySelector('.login-subtitle').textContent =
        'Select your role to continue to the Gurindam repository system';
}


function showRegistrationForm() {

    // REMOVE SUCCESS MESSAGE
    const successMsg = document.querySelector('.success-message');
    if (successMsg) successMsg.remove();
    // Hide all forms
    document.querySelectorAll('.login-form, .registration-form').forEach(form => {
        form.classList.remove('active');
    });

    // Show registration form
    document.getElementById('registration-form').classList.add('active');

    // Add register mode (THIS IS THE FIX)
    document.querySelector('.login-panel').classList.add('register-mode');

    // Update header
    document.querySelector('.login-title').textContent = 'Reader Registration';
    document.querySelector('.login-subtitle').textContent =
        'Create your free reader account to access our collection';
}

function backToLogin() {
     document.getElementById('registration-form').reset();
    // Go back to reader login form
    showLoginForm('reader');
}

// Password confirmation validation
document.getElementById('registration-form').addEventListener('submit', function(e) {
    const password = document.getElementById('reg-password').value;
    const confirmPassword = document.getElementById('reg-confirm-password').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match! Please make sure both passwords are identical.');
        document.getElementById('reg-confirm-password').focus();
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long!');
        document.getElementById('reg-password').focus();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    <?php if (isset($_SESSION['show_register'])): ?>
        showRegistrationForm();
        <?php unset($_SESSION['show_register']); ?>
    <?php else: ?>
        showLoginForm('reader');
    <?php endif; ?>
});

</script>

</body>
</html>