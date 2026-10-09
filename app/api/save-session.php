<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$userId = (int) $_SESSION['user_id'];

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

// Anti-CSRF: same session token the card endpoints use
$token = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Security check failed. Refresh the page and try again.']);
    exit;
}

$deckId  = filter_var($input['deck_id'] ?? null, FILTER_VALIDATE_INT);
$results = $input['results'] ?? null;

$durationSeconds = filter_var($input['duration_seconds'] ?? 0, FILTER_VALIDATE_INT);
$durationSeconds = ($durationSeconds === false) ? 0 : max(0, min($durationSeconds, 6 * 3600));

if (!$deckId || $deckId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid deck.']);
    exit;
}
if (!is_array($results) || empty($results)) {
    echo json_encode(['ok' => true, 'saved' => 0, 'msg' => 'No data to save']);
    exit;
}
if (count($results) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Batch size limited to 1000.']);
    exit;
}

// Verify the deck belongs to this user (and is not archived)
$ownStmt = mysqli_prepare($conn, "SELECT id FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
mysqli_stmt_bind_param($ownStmt, "ii", $deckId, $userId);
mysqli_stmt_execute($ownStmt);
mysqli_stmt_store_result($ownStmt);
$ownsDeck = mysqli_stmt_num_rows($ownStmt) === 1;
mysqli_stmt_close($ownStmt);

if (!$ownsDeck) {
    http_response_code(404);
    echo json_encode(['error' => 'Deck not found or access denied.']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    // NOTE: single-table SET assignments run left to right, so next_review_at
    // must come BEFORE interval_days to see the old interval. Keep this order,
    // and do not turn these into JOIN updates (order is not guaranteed there).
    $stmtRemember = mysqli_prepare($conn, "UPDATE flashcards
        SET review_count = review_count + 1,
            next_review_at = DATE_ADD(NOW(), INTERVAL GREATEST(1, interval_days * 2) DAY),
            interval_days = GREATEST(1, interval_days * 2),
            updated_at = NOW()
        WHERE id = ? AND deck_id = ? AND deleted_at IS NULL AND next_review_at <= NOW()");

    $stmtForgot = mysqli_prepare($conn, "UPDATE flashcards
        SET review_count = review_count + 1,
            lapses = lapses + 1,
            interval_days = 0,
            next_review_at = NOW(),
            updated_at = NOW()
        WHERE id = ? AND deck_id = ? AND deleted_at IS NULL AND next_review_at <= NOW()");

    if (!$stmtRemember || !$stmtForgot) {
        throw new RuntimeException('Could not prepare statements.');
    }

    $saved = 0;
    $rememberedCount = 0;

    foreach ($results as $cardId => $status) {
        $cid = filter_var($cardId, FILTER_VALIDATE_INT);
        if (!$cid || $cid < 1) continue;

        if ($status === 'remembered')    $stmt = $stmtRemember;
        elseif ($status === 'forgotten') $stmt = $stmtForgot;
        else continue;

        mysqli_stmt_bind_param($stmt, "ii", $cid, $deckId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new RuntimeException('Card update failed.');
        }
        if (mysqli_stmt_affected_rows($stmt) > 0) {   // only count cards actually updated
            $saved++;
            if ($status === 'remembered') $rememberedCount++;
        }
    }

    mysqli_stmt_close($stmtRemember);
    mysqli_stmt_close($stmtForgot);

    if ($saved === 0) {
        mysqli_rollback($conn);
        echo json_encode(['ok' => true, 'saved' => 0, 'msg' => 'Nothing to save']);
        exit;
    }

    // Session log for dashboard analytics
    $accuracy = (int) round(($rememberedCount / $saved) * 100);

    $logStmt = mysqli_prepare($conn, "INSERT INTO review_sessions
        (user_id, deck_id, total_reviewed, remembered_count, accuracy_percentage, started_at, ended_at)
        VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? SECOND), NOW())");
    if (!$logStmt) {
        throw new RuntimeException('Could not prepare session log.');
    }
    mysqli_stmt_bind_param($logStmt, "iiiiii", $userId, $deckId, $saved, $rememberedCount, $accuracy, $durationSeconds);
    if (!mysqli_stmt_execute($logStmt)) {
        throw new RuntimeException('Session log failed.');
    }
    mysqli_stmt_close($logStmt);

    mysqli_commit($conn);
    echo json_encode(['ok' => true, 'saved' => $saved]);

} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('save-session.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database exception occurred']);
}