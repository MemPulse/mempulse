<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || empty($_SESSION['demo_pending_user_id'])
    || empty($_SESSION['demo_approval_token'])
    || empty($_POST['approval_token'])
    || !hash_equals($_SESSION['demo_approval_token'], $_POST['approval_token'])) {
    http_response_code(403);
    exit('Invalid or expired confirmation request.');
}

$user_id = (int) $_SESSION['demo_pending_user_id'];
$status = 'approved';
$sql = "UPDATE users SET account_status = ? WHERE id = ? AND account_status = 'pending'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'si', $status, $user_id);
$ok = mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);
mysqli_close($conn);
unset($_SESSION['demo_pending_user_id'], $_SESSION['demo_approval_token']);

if (!$ok) {
    http_response_code(500);
    exit('Unable to confirm the account right now.');
}

header('Location: login.php?registered=approved#login');
exit;
?>
