<?php
session_start();
require_once '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 2. Grab inputs matching your exact HTML `name` attributes
    $email = trim($_POST['login-email']);
    $password =$_POST['login-password'];

    // 3. Validation
    if (empty($email)|| empty($password)) {
        die("Please fill out all fields.");
    }

    // 4. Look up the user by email
    $sql  = "SELECT id, first_name, last_name, password_hash FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn,$sql);
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    
    // Bind the result columns to PHP variables
    mysqli_stmt_bind_result($stmt, $user_id,$first_name, $last_name,$hashed_password);
    
    // Fetch the data
    if (mysqli_stmt_fetch($stmt)) {
        // 5. Verify if the entered password matches the Bcrypt hash
        if (password_verify($password,$hashed_password)) {
            
            // 6. Save user data into Session variables
            $_SESSION['user_id']    =$user_id;
            $_SESSION['first_name'] =$first_name;
            $_SESSION['last_name']  =$last_name;
            $_SESSION['logged_in']  = true;

            // Close connections before redirecting
            mysqli_stmt_close($stmt);
            mysqli_close($conn);

            // 7. Redirect smoothly to your main page or dashboard
            header("Location: ../app/index.php");
            exit;
        } else {
            die("Invalid email or password.");
        }
    } else {
        die("Invalid email or password."); 
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
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
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <main class="auth-shell" id="auth-shell">
        <section class="story-panel" aria-label="MemPulse introduction">
            <a class="brand" href="../landing-page/index.html" aria-label="MemPulse home">
                <span class="brand-mark" aria-hidden="true">M</span>
                <span>MemPulse</span>
            </a>

            <div class="story-copy story-copy-signup">
                <p class="eyebrow">A calmer way to study</p>
                <h1>Make every review <em>count.</em></h1>
                <p>Turn scattered notes into a steady rhythm of small wins, with memory science quietly working in the background.</p>
            </div>

            <div class="story-copy story-copy-login" aria-hidden="true">
                <p class="eyebrow">Welcome back to your rhythm</p>
                <h1>Your next <em>breakthrough</em> is waiting.</h1>
                <p>Pick up exactly where you left off and keep your hard-won knowledge moving forward.</p>
            </div>

            <div class="memory-scene" aria-hidden="true">
                <span class="orbit orbit-one"></span>
                <span class="orbit orbit-two"></span>
                <span class="memory-card memory-card-main"><strong>JavaScript</strong><small>14 cards due</small><span class="progress"><i></i></span></span>
                <span class="memory-card memory-card-small">+ 3 new decks</span>
                <span class="scene-dot scene-dot-coral"></span>
                <span class="scene-dot scene-dot-yellow"></span>
            </div>

            <div class="story-footer">
                <span>Built for curious minds</span>
                <!-- <span class="footer-dots" aria-hidden="true"><i></i><i></i><i></i></span> -->
            </div>
        </section>

        <section class="form-panel">
            <div class="form-panel-inner">
                <div class="form-view signup-view">
                    <p class="eyebrow">Start learning smarter!</p>
                    <h2>Create an account</h2>
                    <p class="form-intro">Already have an account? <button class="text-button switch-to-login" type="button">Log in</button></p>

                    <form class="auth-form" id="signup-form" method="POST" action="register.php">
                        <div class="field-row">
                            <label><span class="field-label">First name <span class="required-indicator" aria-hidden="true">*</span></span><input type="text" name="first-name" autocomplete="given-name" placeholder="Juan" required></label>
                            <label><span class="field-label">Last name <span class="required-indicator" aria-hidden="true">*</span></span><input type="text" name="last-name" autocomplete="family-name" placeholder="Dela Cruz" required></label>
                        </div>
                        <label><span class="field-label">Email address <span class="required-indicator" aria-hidden="true">*</span></span><input type="email" name="email" autocomplete="email" placeholder="juandelacruz@example.com" required></label>
                        <label><span class="field-label">Password <span class="required-indicator" aria-hidden="true">*</span></span><input type="password" name="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required></label>
                        <label class="check-label"><input type="checkbox" name="terms" required><span>I agree to the <a href="terms.html">Terms &amp; Conditions</a> <span class="required-indicator" aria-hidden="true">*</span></span></label>
                        <button class="primary-button" type="submit">Create account <span aria-hidden="true">→</span></button>
                    </form>

                </div>

                <div class="form-view login-view" aria-hidden="true">
                    <p class="eyebrow">Good to see you again!</p>
                    <h2>Log in</h2>
                    <p class="form-intro">New to MemPulse? <button class="text-button switch-to-signup" type="button">Create an account</button></p>

                    <!-- Added action and method attributes below -->
                    <form class="auth-form" id="login-form" action="login.php" method="POST">
                        <label>
                            <span class="field-label">Email address <span class="required-indicator" aria-hidden="true">*</span></span>
                            <input type="email" name="login-email" autocomplete="email" placeholder="juandelacruz@example.com" required>
                        </label>
                        <label>
                            <span class="field-label">Password <span class="required-indicator" aria-hidden="true">*</span></span>
                            <input type="password" name="login-password" autocomplete="current-password" placeholder="Your password" required>
                        </label>
                        <div class="form-options">
                            <!-- <label class="check-label">
                                <input type="checkbox" name="remember"><span>Remember me</span>
                            </label> -->
                            <a href="#forgot-password" id="forgot-password-link">Forgot password?</a>
                        </div>
                        <button class="primary-button" type="submit">Log in <span aria-hidden="true">→</span></button>
                    </form>

                </div>
            </div>
            <p class="form-note">By continuing, you agree to MemPulse's <a href="#privacy">Privacy Policy</a>.</p>
        </section>
    </main>

    <script src="script.js"></script>
</body>

</html>