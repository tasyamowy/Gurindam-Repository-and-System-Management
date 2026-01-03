<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gurindam Login</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        height: 100vh;
        font-family: 'Poppins', sans-serif;
        background: #0a0819;
    }

    .container {
        display: flex;
        height: 100vh;
    }

    /* LEFT PANEL */
    .poetry-panel {
        flex: 1;
        background:
            linear-gradient(rgba(46, 41, 78, 0.7), rgba(30, 27, 56, 0.8)),
            url("../images/gurindam-bg.jpg");
        background-size: cover;
        background-position: center;
        color: #f0eef6;
        padding: 80px;
        position: relative;
        overflow: hidden;
    }

    .poetry-panel::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 5px;
        background: linear-gradient(90deg, #2E294E, #6A5ACD, #2E294E);
    }

    .poetry-panel h1 {
        font-family: 'Playfair Display', serif;
        font-size: 64px;
        margin: 0;
        text-shadow: 0 0 25px rgba(176, 170, 220, 0.6);
        color: #ffffff;
    }

    .tagline {
        font-style: italic;
        margin-bottom: 50px;
        color: #d4d1e6;
        font-size: 20px;
    }

    .poem span {
        display: block;
        font-size: 18px;
        margin-bottom: 10px;
        animation: fadeText 6s infinite;
        color: #eae8f5;
    }

    /* Floating ornaments */
    .ornament {
        position: absolute;
        width: 180px;
        height: 180px;
        border: 2px solid rgba(176, 170, 220, 0.4);
        border-radius: 50%;
        bottom: 60px;
        right: 100px;
        animation: float 8s ease-in-out infinite;
        background: rgba(46, 41, 78, 0.3);
    }

    .ornament.small {
        width: 90px;
        height: 90px;
        bottom: 200px;
        right: 240px;
        animation-duration: 5s;
        border: 2px solid rgba(139, 134, 200, 0.5);
    }

    /* RIGHT PANEL */
    .login-panel {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle, #1a172f, #0a0819);
    }

    .login-card {
        width: 380px;
        padding: 45px;
        background: rgba(255, 255, 255, 0.98);
        border-radius: 20px;
        box-shadow: 0 30px 60px rgba(46, 41, 78, 0.3);
        animation: slideIn 1.2s ease;
        border: 1px solid rgba(46, 41, 78, 0.1);
        position: relative;
    }

    .login-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #2E294E, #6A5ACD, #2E294E);
        border-radius: 20px 20px 0 0;
    }

    .welcome-title {
        font-family: 'Playfair Display', serif;
        text-align: center;
        color: #2E294E;
        margin-bottom: 15px;
        font-size: 28px;
    }

    .welcome-subtitle {
        font-size: 12px;
        color: #7a759f;
        margin-bottom: 20px;
        line-height: 1.4;
        font-weight: 400;
        letter-spacing: 0.3px;
        text-align: center;
        opacity: 0.85;
    }

    .login-card input {
        width: 100%;
        padding: 14px;
        margin-bottom: 18px;
        border-radius: 10px;
        border: 1px solid #c6c2e0;
        font-size: 14px;
        background: #f9f8fd;
        color: #333;
        transition: all 0.3s ease;
    }

    .login-card input:focus {
        outline: none;
        border-color: #2E294E;
        box-shadow: 0 0 10px rgba(46, 41, 78, 0.3);
        background: white;
    }

    .login-card button {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #2E294E, #3D3670);
        color: white;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-top: 10px;
    }

    .login-card button:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(46, 41, 78, 0.4);
        background: linear-gradient(135deg, #3D3670, #2E294E);
    }

    .error {
        background: #f9eaea;
        color: #c62828;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        text-align: center;
        border: 1px solid #ffcdd2;
        font-size: 14px;
    }

    .footer {
        text-align: center;
        margin-top: 20px;
        font-size: 12px;
        color: #666;
        line-height: 1.5;
    }

    /* Animations */
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(40px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-25px); }
    }

    @keyframes fadeText {
        0%,100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
</style>
</head>

<body>

<div class="container">

    <!-- LEFT POETRY PANEL -->
    <div class="poetry-panel">
        <h1>Gurindam and Repository Management System</h1>
        <p class="tagline">Warisan Puisi Melayu</p>

        <div class="poem">
            <span>Barang siapa mengenal diri,</span>
            <span>Maka mengenal Tuhan yang bahari.</span>
        </div>

        <div class="ornament"></div>
        <div class="ornament small"></div>
    </div>

    <!-- RIGHT LOGIN PANEL -->
    <div class="login-panel">
        <div class="login-card">
            <h2 class="welcome-title">Admin Portal</h2>
            <p class="welcome-subtitle">
                Authorized administrators only.<br>
                Please login to continue.
            </p>

            <?php
            if (isset($_SESSION['error'])) {
                echo "<div class='error'>".$_SESSION['error']."</div>";
                unset($_SESSION['error']);
            }
            ?>

            <form action="admin_login_process.php" method="POST">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <p class="footer">Gurindam and Repository Management System<br>© 2025</p>
        </div>
    </div>

</div>

</body>
</html>