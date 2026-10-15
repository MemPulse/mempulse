<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim($_POST['login-email'] ?? '');
    $password = $_POST['login-password'] ?? '';

    if (empty($email) || empty($password)) {
        header("Location: login.php?error=empty_fields");
        exit;
    }

    $sql  = "SELECT id, first_name, last_name, password_hash, account_status, role FROM users WHERE email = ? AND deleted_at IS NULL";
    $stmt = mysqli_prepare($conn, $sql);
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_id, $first_name, $last_name, $hashed_password, $account_status, $role);
    
    if (mysqli_stmt_fetch($stmt)) {
        if (password_verify($password, $hashed_password)) {
            if ($account_status !== 'approved') {
                mysqli_stmt_close($stmt);
                mysqli_close($conn);
                // Rejected accounts get their own message; everything else is still pending.
                $reason = ($account_status === 'rejected') ? 'account_rejected' : 'pending_approval';
                header("Location: login.php?error=" . $reason . "#login");
                exit;
            }

            // Prevent Session Fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id']    = $user_id;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name']  = $last_name;
            $_SESSION['logged_in']  = true;

            mysqli_stmt_close($stmt);
            mysqli_close($conn);

            // Admins go to the admin area; everyone else to the student app.
            // (The admin guard re-verifies the role in the database on every request.)
            header("Location: " . ($role === 'admin' ? "../admin/views/index.php" : "../app/views/index.php"));
            exit;
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    // Invalid credentials back-route
    header("Location: login.php?error=invalid_credentials");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in or create an account for MemPulse.">
    <title>MemPulse | Welcome</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <style>
        :root {
            color-scheme: light;
            --surface: #faf8ff;
            --surface-card: #ffffff;
            --surface-container: #eaedff;
            --surface-pulse: #d2d8ff;
            --ink: #131b2e;
            --muted: #464555;
            --primary: #3525cd;
            --primary-deep: #1e00a9;
            --secondary: #4b41e1;
            --radius-lg: 1rem;
            --radius-xl: 1.25rem;
            --shadow-card: 0 20px 50px rgba(53, 37, 205, .10);
            --line: rgba(199, 196, 216, .48);
            --error: #d93025;
            --error-bg: #fce8e6;
        }

        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--surface);
            color: var(--ink);
            font-family: "Inter", sans-serif;
            font-size: 1rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .material-symbols-outlined {
            font-family: "Material Symbols Outlined";
            font-weight: normal;
            font-style: normal;
            font-size: 24px;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            font-feature-settings: "liga";
            -webkit-font-feature-settings: "liga";
            -webkit-font-smoothing: antialiased;
        }

        /* App Header with Centered Nav & Backlink */
        .app-header {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(250, 248, 255, .86);
            border-bottom: 1px solid rgba(199, 196, 216, .38);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }
        .header-container {
            width: min(100% - 3rem, 80rem);
            min-height: 5rem;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 1.5rem;
        }
        .logo-link {
            display: inline-flex;
            align-items: center;
            gap: .7rem;
            font-family: "Plus Jakarta Sans", sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            letter-spacing: -.04em;
            white-space: nowrap;
            color: var(--ink);
            text-decoration: none;
        }
        .logo-icon {
            display: grid;
            width: 2.6rem;
            height: 2.6rem;
            place-items: center;
            border-radius: .85rem;
            background: var(--surface-container);
            box-shadow: 0 3px 12px rgba(53, 37, 205, .10);
            font-size: 1.35rem;
            color: rgb(53, 37, 205);
        }

        .logo-link {
            justify-self: start;
        }

        .main-nav {
            justify-self: center;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .nav-links a {
            color: var(--muted);
            font-size: .88rem;
            font-weight: 600;
            text-decoration: none;
            transition: color .2s ease;
        }
        .nav-links a:hover, .nav-links a:focus-visible { color: var(--primary); }
        .header-actions { display: flex; align-items: center; justify-content: flex-end; justify-self: end; gap: .8rem; }
        /* Main Content Wrapper */
        .main-content-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            border: 1px solid var(--line);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .brand-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 2rem;
        }

        @keyframes pulse-bg {
            0%, 100% { background-color: var(--surface-container); }
            50% { background-color: var(--surface-pulse); }
        }

        .auth-container .logo-icon {
            width: 3.5rem;
            height: 3.5rem;
            font-size: 2rem;
            margin-bottom: 1rem;
            animation: pulse-bg 2.5s infinite ease-in-out;
        }

        .brand-header h1 {
            margin: 0;
            font-family: "Plus Jakarta Sans", sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: -.04em;
        }

        .brand-header p {
            margin: 0.5rem 0 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.15rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group label:not(.check-label) {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--ink);
        }

        .required-indicator { color: var(--error); }

        .form-group input:not([type="checkbox"]) {
            width: 100%;
            height: 3rem;
            border: 1px solid var(--line);
            border-radius: 0.5rem;
            padding: 0 1rem;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:not([type="checkbox"]):focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(53, 37, 205, 0.15);
        }

        .form-group input.input-error {
            border-color: var(--error) !important;
        }
        .form-group input.input-error:focus {
            box-shadow: 0 0 0 3px rgba(217, 48, 37, 0.15) !important;
        }
        
        .error-text {
            color: var(--error);
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: -0.1rem;
            display: none;
        }
        .error-text.active { display: block; }
        
        .check-error-text {
            color: var(--error);
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.2rem;
            display: none;
            margin-left: 1.6rem;
        }
        .check-error-text.active { display: block; }

        .btn-primary {
            min-height: 3rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 999px;
            padding: 0.7rem 1.25rem;
            background: var(--primary);
            color: #fff;
            font: 700 0.95rem/1.2 "Plus Jakarta Sans", sans-serif;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 8px 22px rgba(53, 37, 205, 0.22);
            margin-top: 0.5rem;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(75, 65, 225, 0.28);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            margin-top: -0.5rem;
        }

        .check-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--muted);
            cursor: pointer;
            font-size: 0.85rem;
        }
        
        .check-label input[type="checkbox"] {
            width: 1.1rem;
            height: 1.1rem;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .auth-container a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .auth-container a:hover { text-decoration: underline; }

        .auth-message {
            font-size: 0.85rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: none;
        }

        .auth-message-error {
            display: block;
            background: var(--error-bg);
            color: #c5221f;
            border: 1px solid #f9d0c4;
        }
        
        .auth-message-success {
            display: block;
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #ceead6;
        }

        .toggle-view {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .view-section { display: none; }
        .view-section.active { display: block; }
        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .header-container { width: min(100% - 2rem, 80rem); }
            .nav-links { gap: 1.1rem; }
        }

        @media (max-width: 768px) {
            .header-container { grid-template-columns: 1fr auto; row-gap: .5rem; min-height: 0; padding: .75rem 0; }
            .main-nav { grid-column: 1 / -1; grid-row: 2; }
        }

        @media (max-width: 400px) {
            .nav-links { gap: .85rem; }
            .nav-links a { font-size: .82rem; }
        }
    </style>
</head>
<body>

    <!-- Header with Centered Nav & Landing Page Backlink -->
    <header class="app-header">
        <div class="header-container">
            <a href="../landing-page/index.html" class="logo-link" aria-label="MemPulse home">
                <span class="material-symbols-outlined logo-icon" aria-hidden="true">neurology</span>
                <span>MemPulse</span>
            </a>

            <nav class="main-nav" aria-label="Main navigation">
                <ul class="nav-links">
                    <li><a href="../landing-page/index.html#features">Features</a></li>
                    <li><a href="../landing-page/index.html#testimonials">Reviews</a></li>
                    <li><a href="../landing-page/pricing.html">Pricing</a></li>
                    <li><a href="../landing-page/about.html">About</a></li>
                </ul>
            </nav>

            <div class="header-actions"></div>
        </div>
    </header>

    <div class="main-content-wrapper">
        <main class="auth-container">
            <div class="brand-header">
                <span class="material-symbols-outlined logo-icon" aria-hidden="true">neurology</span>
                <h1>MemPulse</h1>
                <p id="header-desc">Welcome back to your rhythm.</p>
            </div>

            <!-- Login View -->
            <section id="login-view" class="view-section active">
                <?php if (isset($_GET['error'])): ?>
                    <div class="auth-message auth-message-error" role="alert">
                        <?php 
                            if ($_GET['error'] === 'pending_approval') echo "Your account is waiting for approval. Please try again after it has been approved.";
                            elseif ($_GET['error'] === 'account_rejected') echo "This account was not approved. Please contact support.";
                            elseif ($_GET['error'] === 'invalid_credentials') echo "Invalid email or password.";
                            elseif ($_GET['error'] === 'empty_fields') echo "Please enter your email and password.";
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['registered']) && $_GET['registered'] === 'approved'): ?>
                    <div class="auth-message auth-message-success" role="status">
                        Registration confirmed. You can now log in.
                    </div>
                <?php endif; ?>

                <form class="auth-form" id="form-login" action="login.php" method="POST" autocomplete="off" novalidate>
                    <div class="form-group">
                        <label>Email address <span class="required-indicator">*</span></label>
                        <input type="email" id="login-email" name="login-email" placeholder="student@university.edu" autocomplete="off">
                        <span class="error-text" id="err-login-email">Please enter a valid email address.</span>
                    </div>
                    <div class="form-group">
                        <label>Password <span class="required-indicator">*</span></label>
                        <input type="password" id="login-password" name="login-password" placeholder="••••••••" autocomplete="new-password">
                        <span class="error-text" id="err-login-password">Password is required.</span>
                    </div>
                    
                    <div class="form-options">
                        <label class="check-label">
                            <input type="checkbox" onchange="document.getElementById('login-password').type = this.checked ? 'text' : 'password'">
                            <span>Show password</span>
                        </label>
                        <a href="#forgot">Forgot password?</a>
                    </div>

                    <button class="btn-primary" type="submit">Log In</button>
                    <!-- Back Link button style for the landing page -->
                    <!-- <a href="../landing-page/index.html" class="btn-primary" style="background: var(--surface-container); color: var(--ink); box-shadow: none; text-align: center;">Back to Landing Page</a> -->
                </form>

                <div class="toggle-view">
                    New to MemPulse? <a href="#" onclick="toggleView('signup'); return false;">Create an account</a>
                </div>
            </section>

            <!-- Signup View -->
            <section id="signup-view" class="view-section">
                <form class="auth-form" id="form-signup" action="register.php" method="POST" autocomplete="off" novalidate>
                    <div class="field-row">
                        <div class="form-group">
                            <label>First name <span class="required-indicator">*</span></label>
                            <input type="text" id="first-name" name="first-name" autocomplete="off">
                            <span class="error-text" id="err-first-name">First name is required.</span>
                        </div>
                        <div class="form-group">
                            <label>Last name <span class="required-indicator">*</span></label>
                            <input type="text" id="last-name" name="last-name" autocomplete="off">
                            <span class="error-text" id="err-last-name">Last name is required.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email address <span class="required-indicator">*</span></label>
                        <input type="email" id="signup-email" name="email" autocomplete="off">
                        <span class="error-text" id="err-signup-email">Please enter a valid email address.</span>
                    </div>
                    <div class="form-group">
                        <label>Password <span class="required-indicator">*</span></label>
                        <input type="password" id="signup-password" name="password" autocomplete="new-password">
                        <span class="error-text" id="err-signup-password">Password must be at least 8 characters.</span>
                    </div>
                    <div class="form-group">
                        <label>Confirm password <span class="required-indicator">*</span></label>
                        <input type="password" id="confirm-password" name="confirm-password" autocomplete="new-password">
                        <span class="error-text" id="err-confirm-password">Passwords do not match.</span>
                    </div>
                    
                    <div class="form-options">
                        <label class="check-label">
                            <input type="checkbox" onchange="
                                const isChecked = this.checked;
                                document.getElementById('signup-password').type = isChecked ? 'text' : 'password';
                                document.getElementById('confirm-password').type = isChecked ? 'text' : 'password';
                            ">
                            <span>Show passwords</span>
                        </label>
                    </div>
                    
                    <div>
                        <label class="check-label" style="margin-top: 0.5rem;">
                            <input type="checkbox" id="terms" name="terms">
                            <span>I agree to the <a href="terms.html">Terms &amp; Conditions</a> and <a href="privacy-policy.html">Privacy Policy</a> <span class="required-indicator">*</span></span>
                        </label>
                        <span class="check-error-text" id="err-terms">You must agree to the terms to continue.</span>
                    </div>

                    <button class="btn-primary" type="submit">Create Account</button>
                    <!-- Back Link button style for the landing page -->
                    <!-- <a href="../landing-page/index.html" class="btn-primary" style="background: var(--surface-container); color: var(--ink); box-shadow: none; text-align: center;">Back to Landing Page</a> -->
                </form>

                <div class="toggle-view">
                    Already have an account? <a href="#" onclick="toggleView('login'); return false;">Log in</a>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Form View Toggling
        function toggleView(view) {
            document.getElementById('login-view').classList.remove('active');
            document.getElementById('signup-view').classList.remove('active');
            
            document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
            document.querySelectorAll('.error-text, .check-error-text').forEach(el => el.classList.remove('active'));
            
            if(view === 'signup') {
                document.getElementById('signup-view').classList.add('active');
                document.getElementById('header-desc').innerText = 'Start learning smarter.';
            } else {
                document.getElementById('login-view').classList.add('active');
                document.getElementById('header-desc').innerText = 'Welcome back to your rhythm.';
            }
        }
        
        if (window.location.hash === '#signup') {
            toggleView('signup');
        } else if (window.location.hash === '#login') {
            toggleView('login');
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        function showError(inputId, errorId, show = true) {
            const input = document.getElementById(inputId);
            const errorMsg = document.getElementById(errorId);
            if (show) {
                if(input) input.classList.add('input-error');
                if(errorMsg) errorMsg.classList.add('active');
            } else {
                if(input) input.classList.remove('input-error');
                if(errorMsg) errorMsg.classList.remove('active');
            }
        }

        document.getElementById('form-login').addEventListener('submit', function(e) {
            let isValid = true;
            const email = document.getElementById('login-email').value.trim();
            const password = document.getElementById('login-password').value;

            showError('login-email', 'err-login-email', false);
            showError('login-password', 'err-login-password', false);

            if (!emailRegex.test(email)) {
                showError('login-email', 'err-login-email');
                isValid = false;
            }

            if (password === '') {
                showError('login-password', 'err-login-password');
                isValid = false;
            }

            if (!isValid) e.preventDefault();
        });

        document.getElementById('form-signup').addEventListener('submit', function(e) {
            let isValid = true;
            
            const fname = document.getElementById('first-name').value.trim();
            const lname = document.getElementById('last-name').value.trim();
            const email = document.getElementById('signup-email').value.trim();
            const pass = document.getElementById('signup-password').value;
            const confirmPass = document.getElementById('confirm-password').value;
            const terms = document.getElementById('terms').checked;

            ['first-name', 'last-name', 'signup-email', 'signup-password', 'confirm-password'].forEach(id => {
                showError(id, 'err-' + id, false);
            });
            document.getElementById('err-terms').classList.remove('active');

            if (fname === '') { showError('first-name', 'err-first-name'); isValid = false; }
            if (lname === '') { showError('last-name', 'err-last-name'); isValid = false; }
            if (!emailRegex.test(email)) { showError('signup-email', 'err-signup-email'); isValid = false; }
            if (pass.length < 8) { showError('signup-password', 'err-signup-password'); isValid = false; }
            if (pass !== confirmPass || confirmPass === '') { 
                showError('confirm-password', 'err-confirm-password'); 
                isValid = false; 
            }
            if (!terms) { 
                document.getElementById('err-terms').classList.add('active'); 
                isValid = false; 
            }

            if (!isValid) e.preventDefault();
        });

        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('input-error');
                const err = document.getElementById('err-' + this.id);
                if (err) err.classList.remove('active');
                
                if (this.id === 'terms') {
                    document.getElementById('err-terms').classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>