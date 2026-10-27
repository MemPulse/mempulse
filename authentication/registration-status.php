<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

$userId = filter_var($_SESSION['demo_pending_user_id'] ?? null, FILTER_VALIDATE_INT);
if ($userId === false || $userId === null || $userId < 1) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No pending registration was found.']);
    exit;
}

require_once '../config/db.php';

$stmt = mysqli_prepare($conn, 'SELECT account_status, deleted_at FROM users WHERE id = ? LIMIT 1');
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to check approval status.']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $userId);
if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to check approval status.']);
    exit;
}

mysqli_stmt_bind_result($stmt, $accountStatus, $deletedAt);
$found = mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (!$found || $deletedAt !== null || $accountStatus === 'rejected') {
    unset($_SESSION['demo_pending_user_id'], $_SESSION['demo_approval_token']);
    $status = 'rejected';
} elseif ($accountStatus === 'approved') {
    unset($_SESSION['demo_pending_user_id'], $_SESSION['demo_approval_token']);
    $status = 'approved';
} else {
    $status = 'pending';
}

mysqli_close($conn);
session_write_close();

echo json_encode(['status' => $status]);
