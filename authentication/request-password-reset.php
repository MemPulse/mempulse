<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/password-reset-mail.php';

if (empty($_SESSION['password_reset_csrf'])) {
    $_SESSION['password_reset_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sentToken = $_POST['csrf_token'] ?? '';
    if (!is_string($sentToken) || !hash_equals($_SESSION['password_reset_csrf'], $sentToken)) {
        http_response_code(403);
        exit('Security check failed. Please return to the reset form and try again.');
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $normalizedEmail = strtolower($email);
        $emailHash = hash('sha256', $normalizedEmail);
        $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $limited = false;

        $rateStmt = mysqli_prepare(
            $conn,
            'SELECT COUNT(*) FROM password_reset_tokens
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
               AND (email_hash = ? OR request_ip_hash = ?)'
        );
        if (!$rateStmt) {
            error_log('Password reset rate-limit query could not be prepared: ' . mysqli_error($conn));
            $limited = true;
        } else {
            mysqli_stmt_bind_param($rateStmt, 'ss', $emailHash, $ipHash);
            mysqli_stmt_execute($rateStmt);
            mysqli_stmt_bind_result($rateStmt, $recentRequests);
            mysqli_stmt_fetch($rateStmt);
            mysqli_stmt_close($rateStmt);
            $limited = (int) $recentRequests >= 3;
        }

        if (!$limited) {
            $userStmt = mysqli_prepare(
                $conn,
                'SELECT id, email FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1'
            );
            if ($userStmt) {
                mysqli_stmt_bind_param($userStmt, 's', $email);
                mysqli_stmt_execute($userStmt);
                mysqli_stmt_bind_result($userStmt, $userId, $userEmail);
                $hasUser = mysqli_stmt_fetch($userStmt);
                mysqli_stmt_close($userStmt);
            } else {
                $hasUser = false;
                error_log('Password reset user lookup failed: ' . mysqli_error($conn));
            }

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $nullableUserId = $hasUser ? (int) $userId : null;
            $nullableTokenHash = $hasUser ? $tokenHash : null;
            $insertStmt = mysqli_prepare(
                $conn,
                'INSERT INTO password_reset_tokens (user_id, email_hash, request_ip_hash, token_hash, expires_at)
                 VALUES (?, ?, ?, ?, IF(? = 1, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NULL))'
            );

            if ($insertStmt) {
                mysqli_stmt_bind_param(
                    $insertStmt,
                    'isssi',
                    $nullableUserId,
                    $emailHash,
                    $ipHash,
                    $nullableTokenHash,
                    $hasUser
                );
                $inserted = mysqli_stmt_execute($insertStmt);
                $resetId = mysqli_insert_id($conn);
                if (!$inserted) {
                    error_log('Password reset request could not be recorded: ' . mysqli_stmt_error($insertStmt));
                }
                mysqli_stmt_close($insertStmt);

                if ($inserted && $hasUser) {
                    $mailSent = false;
                    try {
                        $resetUrl = passwordResetPublicUrl(
                            'password-reset.php?token=' . rawurlencode($token)
                        );
                        sendPasswordResetEmail((string) $userEmail, $resetUrl);
                        $mailSent = true;
                    } catch (Throwable $exception) {
                        error_log('Password reset email could not be sent: ' . $exception->getMessage());
                        $invalidateFailedStmt = mysqli_prepare(
                            $conn,
                            'UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL'
                        );
                        if ($invalidateFailedStmt) {
                            mysqli_stmt_bind_param($invalidateFailedStmt, 'i', $resetId);
                            mysqli_stmt_execute($invalidateFailedStmt);
                            mysqli_stmt_close($invalidateFailedStmt);
                        }
                    }

                    if ($mailSent) {
                        $invalidateStmt = mysqli_prepare(
                            $conn,
                            'UPDATE password_reset_tokens SET used_at = NOW()
                             WHERE user_id = ? AND id <> ? AND used_at IS NULL AND token_hash IS NOT NULL'
                        );
                        if (!$invalidateStmt) {
                            error_log('Previous password reset tokens could not be invalidated: ' . mysqli_error($conn));
                        } else {
                            mysqli_stmt_bind_param($invalidateStmt, 'ii', $nullableUserId, $resetId);
                            if (!mysqli_stmt_execute($invalidateStmt)) {
                                error_log('Previous password reset tokens could not be invalidated: ' . mysqli_stmt_error($invalidateStmt));
                            }
                            mysqli_stmt_close($invalidateStmt);
                        }
                    }
                }
            } else {
                error_log('Password reset request insert could not be prepared: ' . mysqli_error($conn));
            }
        }
    }

    mysqli_close($conn);
    header('Location: request-password-reset.php?sent=1');
    exit;
}

$sent = isset($_GET['sent']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password | MemPulse</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.25rem; background: #f5f6ff; color: #131b2e; font: 16px/1.6 Inter, sans-serif; }
        main { width: min(100%, 28rem); padding: 2rem; border: 1px solid #d8dced; border-radius: 1.25rem; background: #fff; box-shadow: 0 20px 50px #3525cd18; }
        h1 { margin: 0 0 .5rem; font: 800 1.65rem/1.25 "Plus Jakarta Sans", sans-serif; }
        p { color: #464555; }
        label { display: block; margin: 1rem 0 .4rem; font-weight: 600; }
        input { display: block; width: 100%; padding: .8rem .9rem; border: 1px solid #aeb4c9; border-radius: .6rem; font: inherit; }
        input:focus { outline: 3px solid #3525cd30; border-color: #3525cd; }
        button { width: 100%; margin-top: 1.25rem; padding: .85rem 1rem; border: 0; border-radius: 999px; background: #3525cd; color: white; font: 700 1rem "Plus Jakarta Sans", sans-serif; cursor: pointer; }
        .notice { padding: .8rem; border-radius: .6rem; background: #e6f4ea; color: #137333; }
        .back { display: block; margin-top: 1.25rem; text-align: center; color: #3525cd; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<main>
    <h1>Forgot your password?</h1>
    <?php if ($sent): ?>
        <p class="notice" role="status">If an active account matches that email, we’ll send a password reset link shortly. Check your inbox and spam folder.</p>
        <p>The link expires after 30 minutes and can only be used once.</p>
    <?php else: ?>
        <p>Enter the email address associated with your account and we’ll send you a reset link.</p>
        <form method="post" action="request-password-reset.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['password_reset_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" maxlength="255" autocomplete="email" required>
            <button type="submit">Send reset link</button>
        </form>
    <?php endif; ?>
    <a class="back" href="login.php">Back to login</a>
</main>
</body>
</html>
