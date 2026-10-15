<?php
/**
 * Admin guard. Include FIRST on every admin page and endpoint, before any output.
 *
 * What it does:
 *  1. Defines URL constants (admin, app, login).
 *  2. Loads the database connection.
 *  3. Verifies the user EVERY request against the database (never trusts a cached role).
 *  4. Exposes $adminUser, $displayName, $displayRole, $adminTheme to the page.
 *  5. Provides CSRF helpers for state-changing requests.
 *
 * JSON endpoints: define('ADMIN_JSON', true) BEFORE including this file so that
 * failures return a JSON 401/403 instead of a redirect.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_URL', '/mempulse/app/');
define('ADMIN_URL', '/mempulse/admin/');
define('LOGIN_URL', '/mempulse/authentication/login.php');

// config/db.php defines BASE_URL as the *app* path when it is not already set.
// Claim it for the admin area BEFORE loading the connection.
if (!defined('BASE_URL')) {
    define('BASE_URL', ADMIN_URL);
}

require_once __DIR__ . '/../../config/db.php';

// Admin pages must never be cached (stops the Back button showing them after logout).
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

/**
 * Stop the request. Pages redirect; JSON endpoints answer with a status code.
 */
function adminDeny(int $status, string $message, string $redirect): void
{
    if (defined('ADMIN_JSON') && ADMIN_JSON) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        header('Location: ' . $redirect);
    }
    exit;
}

/** Escape a value for HTML output. */
function adminEsc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---- 1. Must have a logged-in session ------------------------------------
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_id'])) {
    adminDeny(401, 'Please log in.', LOGIN_URL);
}

$userId = (int) $_SESSION['user_id'];

// ---- 2. Verify the account and role against the database -------------------
$sql = "SELECT u.first_name, u.last_name, u.email, u.role, u.account_status, s.theme
        FROM users u
        LEFT JOIN user_settings s ON s.user_id = u.id
        WHERE u.id = ? AND u.deleted_at IS NULL";

$row = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $firstName, $lastName, $email, $role, $accountStatus, $theme);
    if (mysqli_stmt_fetch($stmt)) {
        $row = [
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'email'     => $email,
            'role'      => $role,
            'status'    => $accountStatus,
            'theme'     => $theme,
        ];
    }
    mysqli_stmt_close($stmt);
}

// Account missing, soft-deleted, or not approved: treat as logged out.
if ($row === null || $row['status'] !== 'approved') {
    adminDeny(401, 'Your session is no longer valid.', LOGIN_URL);
}

// Logged in, but not an admin: send back to the student app.
if ($row['role'] !== 'admin') {
    adminDeny(403, 'You do not have access to this area.', APP_URL . 'views/index.php');
}

// ---- 3. Values shared with pages and includes ----------------------------
$fullName = trim($row['firstName'] . ' ' . $row['lastName']);

$adminUser = [
    'id'    => $userId,
    'name'  => $fullName !== '' ? $fullName : 'Administrator',
    'email' => $row['email'],
    'role'  => 'admin',
];

$displayName = $adminUser['name'];
$displayRole = 'Admin';
$adminTheme  = in_array($row['theme'], ['light', 'dark', 'system'], true) ? $row['theme'] : 'system';

// ---- 4. CSRF (separate token from the student settings token) -------------
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

function adminCsrfToken(): string
{
    return $_SESSION['admin_csrf'];
}

/**
 * Call at the top of every state-changing endpoint, after the method check.
 * Accepts the token from the X-CSRF-Token header or a csrf_token POST field.
 */
function adminRequireCsrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!is_string($sent) || $sent === '' || !hash_equals($_SESSION['admin_csrf'], $sent)) {
        adminDeny(403, 'Invalid security token. Refresh the page and try again.', ADMIN_URL . 'views/index.php');
    }
}