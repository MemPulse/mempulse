<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['password_reset_csrf'])) {
    $_SESSION['password_reset_csrf'] = bin2hex(random_bytes(32));
}

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$tokenIsWellFormed = preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
$error = '';
$complete = false;
$linkInvalid = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!is_string($csrf) || !hash_equals($_SESSION['password_reset_csrf'], $csrf)) {
        http_response_code(403);
        exit('Security check failed. Please reopen the reset link and try again.');
    }

    if (!$tokenIsWellFormed) {
        $error = 'This reset link is invalid or has expired. Request a new one to continue.';
        $linkInvalid = true;
    } elseif (strlen($password) < 8) {
        $error = 'Choose a password with at least 8 characters.';
    } elseif (!hash_equals($password, $confirmPassword)) {
        $error = 'The passwords do not match.';
    } else {
        $tokenHash = hash('sha256', $token);
        if (!mysqli_begin_transaction($conn)) {
            error_log('Password reset transaction could not start: ' . mysqli_error($conn));
            http_response_code(500);
            $error = 'We could not update your password right now. Please try again later.';
        } else {
            try {
                $lookup = mysqli_prepare(
                    $conn,
                    'SELECT t.id, t.user_id
                     FROM password_reset_tokens t
                     INNER JOIN users u ON u.id = t.user_id
                     WHERE t.token_hash = ? AND t.used_at IS NULL
                       AND t.expires_at > NOW() AND u.deleted_at IS NULL
                     LIMIT 1 FOR UPDATE'
                );
                if (!$lookup) {
                    throw new RuntimeException('Password reset token lookup failed: ' . mysqli_error($conn));
                }

                mysqli_stmt_bind_param($lookup, 's', $tokenHash);
                mysqli_stmt_execute($lookup);
                mysqli_stmt_bind_result($lookup, $resetId, $userId);
                $validToken = mysqli_stmt_fetch($lookup);
                mysqli_stmt_close($lookup);

                if (!$validToken) {
                    mysqli_rollback($conn);
                    $error = 'This reset link is invalid or has expired. Request a new one to continue.';
                    $linkInvalid = true;
                } else {
                    $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                    if ($newPasswordHash === false) {
                        throw new RuntimeException('Password reset could not hash the new password.');
                    }

                    $updateUser = mysqli_prepare(
                        $conn,
                        'UPDATE users SET password_hash = ?, password_changed_at = NOW()
                         WHERE id = ? AND deleted_at IS NULL'
                    );
                    if (!$updateUser) {
                        throw new RuntimeException('Password reset user update could not be prepared: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($updateUser, 'si', $newPasswordHash, $userId);
                    if (!mysqli_stmt_execute($updateUser) || mysqli_stmt_affected_rows($updateUser) !== 1) {
                        $failure = mysqli_stmt_error($updateUser);
                        mysqli_stmt_close($updateUser);
                        throw new RuntimeException('Password reset user update failed: ' . $failure);
                    }
                    mysqli_stmt_close($updateUser);

                    $invalidate = mysqli_prepare(
                        $conn,
                        'UPDATE password_reset_tokens SET used_at = NOW()
                         WHERE user_id = ? AND used_at IS NULL'
                    );
                    if (!$invalidate) {
                        throw new RuntimeException('Password reset token invalidation could not be prepared: ' . mysqli_error($conn));
                    }
                    mysqli_stmt_bind_param($invalidate, 'i', $userId);
                    if (!mysqli_stmt_execute($invalidate)) {
                        $failure = mysqli_stmt_error($invalidate);
                        mysqli_stmt_close($invalidate);
                        throw new RuntimeException('Password reset token invalidation failed: ' . $failure);
                    }
                    mysqli_stmt_close($invalidate);

                    if (!mysqli_commit($conn)) {
                        throw new RuntimeException('Password reset transaction could not commit: ' . mysqli_error($conn));
                    }
                    $complete = true;
                    $_SESSION['password_reset_csrf'] = bin2hex(random_bytes(32));
                }
            } catch (Throwable $exception) {
                mysqli_rollback($conn);
                error_log($exception->getMessage());
                http_response_code(500);
                $error = 'We could not update your password right now. Please try again later.';
            }
        }
    }
}

if (!$complete && $error === '' && $tokenIsWellFormed) {
    $tokenHash = hash('sha256', $token);
    $check = mysqli_prepare(
        $conn,
        'SELECT t.id FROM password_reset_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW()
           AND u.deleted_at IS NULL LIMIT 1'
    );
    if (!$check) {
        error_log('Password reset page token check failed: ' . mysqli_error($conn));
        http_response_code(500);
        $error = 'We could not verify this reset link right now.';
    } else {
        mysqli_stmt_bind_param($check, 's', $tokenHash);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) !== 1) {
            $error = 'This reset link is invalid or has expired. Request a new one to continue.';
            $linkInvalid = true;
        }
        mysqli_stmt_close($check);
    }
} elseif (!$complete && $error === '') {
    $error = 'This reset link is invalid or has expired. Request a new one to continue.';
    $linkInvalid = true;
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>Choose a new password | MemPulse</title>
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
        .show-password { display: flex; align-items: center; gap: .5rem; margin-top: .75rem; font-weight: 500; cursor: pointer; }
        .show-password input { display: inline; width: auto; margin: 0; padding: 0; }
        button { width: 100%; margin-top: 1.25rem; padding: .85rem 1rem; border: 0; border-radius: 999px; background: #3525cd; color: white; font: 700 1rem "Plus Jakarta Sans", sans-serif; cursor: pointer; }
        .notice { padding: .8rem; border-radius: .6rem; background: #fce8e6; color: #a32018; }
        .success { background: #e6f4ea; color: #137333; }
        .back { display: block; margin-top: 1.25rem; text-align: center; color: #3525cd; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<main>
    <?php if ($complete): ?>
        <h1>Password updated</h1>
        <p class="notice success" role="status">Your password has been changed. You can now sign in with your new password.</p>
        <a class="back" href="login.php">Go to login</a>
    <?php elseif ($linkInvalid): ?>
        <h1>Reset link unavailable</h1>
        <p class="notice" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <a class="back" href="request-password-reset.php">Request another reset link</a>
    <?php else: ?>
        <h1>Choose a new password</h1>
        <p>Use at least 8 characters. Your reset link can only be used once.</p>
        <?php if ($error !== ''): ?>
            <p class="notice" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form method="post" action="password-reset.php">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['password_reset_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <label for="password">New password</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
            <label for="confirm-password">Confirm new password</label>
            <input id="confirm-password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required>
            <label class="show-password">
                <input id="show-passwords" type="checkbox" aria-controls="password confirm-password">
                Show passwords
            </label>
            <button type="submit">Update password</button>
        </form>
        <a class="back" href="login.php">Back to login</a>
    <?php endif; ?>
</main>
<script>
    const showPasswords = document.getElementById("show-passwords");
    if (showPasswords) {
        showPasswords.addEventListener("change", () => {
            const type = showPasswords.checked ? "text" : "password";
            document.getElementById("password").type = type;
            document.getElementById("confirm-password").type = type;
        });
    }
</script>
</body>
</html>
