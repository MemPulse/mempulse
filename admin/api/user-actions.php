<?php
/**
 * POST /admin/api/user-actions.php
 * Supports:
 *  1. Single action: { "action": "approve"|"reject"|"delete"|"restore"|"make_admin"|"make_user", "user_id": 123 }
 *  2. Bulk action:   { "action": "approve"|"reject"|"delete"|"restore", "user_ids": [1, 2, 3] }
 *  3. Create user:   { "action": "create_user", "first_name": "...", "last_name": "...", "email": "...", "password": "...", "role": "user", "status": "approved" }
 */
define('ADMIN_JSON', true);
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/AdminUserActionsService.php';

header('Content-Type: application/json; charset=utf-8');

function userActionsRespond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    userActionsRespond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

adminRequireCsrf();

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = is_string($input['action'] ?? null) ? $input['action'] : '';
$ip     = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

if ($action === 'create_user') {
    $result = AdminUserActionsService::createUser($conn, $adminUser['id'], $input, $ip);
} elseif (!empty($input['user_ids']) && is_array($input['user_ids'])) {
    $result = AdminUserActionsService::performBulk($conn, $adminUser['id'], $input['user_ids'], $action, $ip);
} else {
    $targetId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
    $targetId = $targetId === false ? 0 : (int) $targetId;
    $result   = AdminUserActionsService::perform($conn, $adminUser['id'], $targetId, $action, $ip);
}

userActionsRespond($result['code'], [
    'success' => $result['ok'],
    'message' => $result['message'],
    'status'  => $result['status'] ?? null,
]);