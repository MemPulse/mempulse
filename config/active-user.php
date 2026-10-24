<?php
/**
 * Re-checks the signed-in student against the database on every request, so an admin
 * rejecting or deleting an account takes effect immediately (not at the next login).
 *
 * Call it right after session_start() and config/db.php:
 *   require_once __DIR__ . '/../../config/active-user.php';
 *   mempulseEnforceActiveUser($conn);          // pages
 *   mempulseEnforceActiveUser($conn, true);    // JSON endpoints (answers 401 instead of redirecting)
 *
 * Logged-out visitors are left to each page's own guard. If the lookup itself fails
 * (database hiccup) the request is allowed through rather than signing everyone out.
 */
function mempulseEnforceActiveUser(mysqli $conn, bool $json = false): void
{
    if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
        return;
    }

    $userId  = (int) $_SESSION['user_id'];
    $status  = null;
    $checked = false;

    if ($stmt = mysqli_prepare($conn, 'SELECT account_status FROM users WHERE id = ? AND deleted_at IS NULL')) {
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        if (mysqli_stmt_execute($stmt)) {
            $checked = true;
            mysqli_stmt_bind_result($stmt, $status);
            mysqli_stmt_fetch($stmt);
        }
        mysqli_stmt_close($stmt);
    }

    if (!$checked || $status === 'approved') {
        return;
    }

    // Account was rejected, is pending again, or was deleted: end the session.
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();

    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Your session is no longer valid.']);
        exit;
    }

    $error = ($status === 'rejected') ? '?error=account_rejected#login' : '';
    header('Location: /mempulse/authentication/login.php' . $error);
    exit;
}