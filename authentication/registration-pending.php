
<?php
session_start();
if (empty($_SESSION['demo_pending_user_id']) || empty($_SESSION['demo_approval_token'])) {
    header('Location: login.php#login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemPulse | Registration Pending</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <main class="auth-shell" style="display:block;max-width:620px;min-height:0;padding:clamp(2rem,5vw,4rem);">
        <p class="eyebrow">Registration complete</p>
        <h1 style="margin-top:.6rem;color:#1b477f;">Your account is pending approval</h1>
        <p style="margin-top:1rem;color:#68727d;">Your account has been registered but cannot log in until it is approved. For this development session, use the temporary button below to simulate admin approval.</p>
        <form method="POST" action="confirm-registration.php" style="margin-top:1.5rem;">
            <input type="hidden" name="approval_token" value="<?php echo htmlspecialchars($_SESSION['demo_approval_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <button class="primary-button" type="submit">Confirm registered account <span aria-hidden="true">→</span></button>
        </form>
        <p style="margin-top:1rem;color:#9a9da0;font-size:.8rem;">Demo only: this button is not a secure substitute for a real administrator approval workflow.</p>
        <p style="margin-top:1rem;"><a href="login.php#login">Go to login</a></p>
    </main>
</body>
</html>
