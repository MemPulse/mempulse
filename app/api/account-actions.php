<?php
/**
 * account-actions.php  (put in app/api/)
 *
 * Backend for the Profile, Security and Appearance tabs in Settings.
 *
 *   GET  -> returns the signed-in user's account details
 *   POST -> JSON body with an "action":
 *             update_profile   first_name, last_name, email, [current_password]
 *             change_password  current_password, new_password, confirm_password
 *             update_theme     theme (light | dark | system)
 */
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (($_SESSION['logged_in'] ?? false) !== true || !isset($_SESSION['user_id'])) {
    respond(401, ['success' => false, 'message' => 'Please log in again.']);
}

require_once __DIR__ . '/../../config/db.php';

// Make every failed query throw, even if db.php turned that off.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

const ACCOUNT_PASSWORD_MIN_LENGTH = 8;
const ACCOUNT_PASSWORD_MAX_BYTES  = 72;   // bcrypt ignores everything past 72 bytes
const ACCOUNT_PASSWORD_MAX_TRIES  = 5;    // wrong "current password" guesses allowed
const ACCOUNT_PASSWORD_LOCK_SECS  = 900;  // then locked for 15 minutes

/* ------------------------------------------------------------------ */
/* Helpers                                                             */
/* ------------------------------------------------------------------ */

function getAccount(mysqli $conn, int $userId): ?array
{
    $stmt = mysqli_prepare($conn,
        "SELECT u.first_name, u.last_name, u.email, u.role,
                u.created_at, u.password_changed_at,
                COALESCE(s.theme, 'system')
         FROM users u
         LEFT JOIN user_settings s ON s.user_id = u.id
         WHERE u.id = ? AND u.deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $first, $last, $email, $role, $created, $pwChanged, $theme);

    if (!mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);
        return null;
    }
    mysqli_stmt_close($stmt);

    return [
        'first_name'          => $first,
        'last_name'           => $last,
        'email'               => $email,
        'role'                => $role,
        'member_since'        => $created,
        'password_changed_at' => $pwChanged,
        'theme'               => $theme,
    ];
}

function cleanName($value): ?string
{
    if (!is_string($value)) {
        return null;
    }
    $value = preg_replace('/\s+/u', ' ', $value);   // null if not valid UTF-8
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    if ($value === '' || mb_strlen($value) > 100 || preg_match('/[\x00-\x1F\x7F]/u', $value)) {
        return null;
    }
    return $value;
}

/**
 * Stops the request unless $password is the user's current password.
 * Wrong guesses are counted in the session; after 5 the user is locked out for 15 minutes.
 */
function requireCurrentPassword(mysqli $conn, int $userId, string $password): void
{
    $lockedUntil = (int) ($_SESSION['account_pw_locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        $minutes = (int) ceil(($lockedUntil - time()) / 60);
        respond(429, ['success' => false,
            'message' => "Too many incorrect attempts. Try again in {$minutes} minute(s)."]);
    }

    if ($password === '') {
        respond(422, ['success' => false, 'message' => 'Enter your current password.']);
    }

    $stmt = mysqli_prepare($conn, "SELECT password_hash FROM users WHERE id = ? AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $hash);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($found && password_verify($password, $hash)) {
        unset($_SESSION['account_pw_attempts'], $_SESSION['account_pw_locked_until']);
        return;
    }

    $attempts = (int) ($_SESSION['account_pw_attempts'] ?? 0) + 1;
    if ($attempts >= ACCOUNT_PASSWORD_MAX_TRIES) {
        $_SESSION['account_pw_attempts'] = 0;
        $_SESSION['account_pw_locked_until'] = time() + ACCOUNT_PASSWORD_LOCK_SECS;
        respond(429, ['success' => false,
            'message' => 'Too many incorrect attempts. Try again in 15 minutes.']);
    }
    $_SESSION['account_pw_attempts'] = $attempts;

    respond(422, ['success' => false, 'message' => 'Your current password is incorrect.']);
}

/* ------------------------------------------------------------------ */
/* Actions                                                             */
/* ------------------------------------------------------------------ */

function handleUpdateProfile(mysqli $conn, int $userId, array $data): void
{
    $first = cleanName($data['first_name'] ?? null);
    $last  = cleanName($data['last_name'] ?? null);
    $email = is_string($data['email'] ?? null) ? mb_strtolower(trim($data['email'])) : '';

    if ($first === null || $last === null) {
        respond(422, ['success' => false,
            'message' => 'Enter a first and last name, up to 100 characters each.']);
    }
    if ($email === '' || mb_strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, ['success' => false, 'message' => 'Enter a valid email address.']);
    }

    $account = getAccount($conn, $userId);
    if ($account === null) {
        respond(404, ['success' => false, 'message' => 'Account not found.']);
    }

    // Changing the email address requires the current password.
    if ($email !== mb_strtolower($account['email'])) {
        $current = is_string($data['current_password'] ?? null) ? $data['current_password'] : '';
        requireCurrentPassword($conn, $userId, $current);

        $stmt = mysqli_prepare($conn, "SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'si', $email, $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $taken = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($taken) {
            respond(409, ['success' => false, 'message' => 'That email address is already in use.']);
        }
    }

    $stmt = mysqli_prepare($conn,
        "UPDATE users SET first_name = ?, last_name = ?, email = ?
         WHERE id = ? AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'sssi', $first, $last, $email, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    respond(200, [
        'success' => true,
        'message' => 'Profile saved.',
        'account' => getAccount($conn, $userId),
    ]);
}

function handleChangePassword(mysqli $conn, int $userId, array $data): void
{
    $current = is_string($data['current_password'] ?? null) ? $data['current_password'] : '';
    $new     = is_string($data['new_password'] ?? null) ? $data['new_password'] : '';
    $confirm = is_string($data['confirm_password'] ?? null) ? $data['confirm_password'] : '';

    if ($new !== $confirm) {
        respond(422, ['success' => false, 'message' => 'The new passwords do not match.']);
    }
    if (mb_strlen($new) < ACCOUNT_PASSWORD_MIN_LENGTH) {
        respond(422, ['success' => false,
            'message' => 'Use at least ' . ACCOUNT_PASSWORD_MIN_LENGTH . ' characters for the new password.']);
    }
    if (strlen($new) > ACCOUNT_PASSWORD_MAX_BYTES) {
        respond(422, ['success' => false,
            'message' => 'The new password is too long. Use at most ' . ACCOUNT_PASSWORD_MAX_BYTES . ' bytes.']);
    }

    requireCurrentPassword($conn, $userId, $current);

    if (hash_equals($current, $new)) {
        respond(422, ['success' => false,
            'message' => 'Choose a password that is different from your current one.']);
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare($conn,
        "UPDATE users SET password_hash = ?, password_changed_at = NOW()
         WHERE id = ? AND deleted_at IS NULL");
    mysqli_stmt_bind_param($stmt, 'si', $hash, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // New session id after a credential change; the user stays signed in here.
    session_regenerate_id(true);

    respond(200, [
        'success' => true,
        'message' => 'Password changed.',
        'account' => getAccount($conn, $userId),
    ]);
}

function handleUpdateTheme(mysqli $conn, int $userId, array $data): void
{
    $theme = $data['theme'] ?? '';
    if (!in_array($theme, ['light', 'dark', 'system'], true)) {
        respond(422, ['success' => false, 'message' => 'Choose light, dark, or system.']);
    }

    $stmt = mysqli_prepare($conn,
        "INSERT INTO user_settings (user_id, theme) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE theme = VALUES(theme)");
    mysqli_stmt_bind_param($stmt, 'is', $userId, $theme);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    respond(200, ['success' => true, 'message' => 'Appearance saved.', 'theme' => $theme]);
}

/* ------------------------------------------------------------------ */
/* Router                                                              */
/* ------------------------------------------------------------------ */

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

try {
    if ($method === 'GET') {
        $account = getAccount($conn, $userId);
        if ($account === null) {
            respond(404, ['success' => false, 'message' => 'Account not found.']);
        }
        respond(200, ['success' => true, 'account' => $account]);
    }

    // POST: same CSRF token the learning-settings form already uses.
    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['settings_csrf']) || !hash_equals($_SESSION['settings_csrf'], $sentToken)) {
        respond(403, ['success' => false,
            'message' => 'Security token invalid. Refresh the page and try again.']);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respond(400, ['success' => false, 'message' => 'Invalid request data.']);
    }

    switch ($data['action'] ?? '') {
        case 'update_profile':
            handleUpdateProfile($conn, $userId, $data);
            break;
        case 'change_password':
            handleChangePassword($conn, $userId, $data);
            break;
        case 'update_theme':
            handleUpdateTheme($conn, $userId, $data);
            break;
        default:
            respond(400, ['success' => false, 'message' => 'Unknown action.']);
    }
} catch (mysqli_sql_exception $e) {
    if ((int) $e->getCode() === 1062) {   // duplicate email (unique index)
        respond(409, ['success' => false, 'message' => 'That email address is already in use.']);
    }
    error_log('account-actions.php SQL error: ' . $e->getMessage());
    respond(500, ['success' => false, 'message' => 'Something went wrong. Please try again.']);
} catch (Throwable $e) {
    error_log('account-actions.php error: ' . $e->getMessage());
    respond(500, ['success' => false, 'message' => 'Something went wrong. Please try again.']);
}