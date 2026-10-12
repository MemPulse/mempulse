<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Make every failed query throw, no matter how config/db.php set things up.
// Without this, a failed INSERT INTO review_logs is silently ignored.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // db.php may have switched it off

$userId = (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}

$token = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Security check failed. Refresh the page and try again.']);
    exit;
}

$deckId = filter_var($input['deck_id'] ?? null, FILTER_VALIDATE_INT);
$results = $input['results'] ?? null;
$durationSeconds = filter_var($input['duration_seconds'] ?? 0, FILTER_VALIDATE_INT);
$durationSeconds = ($durationSeconds === false) ? 0 : max(0, min($durationSeconds, 6 * 3600));

if (!$deckId || $deckId < 1 || !is_array($results) || empty($results)) {
    echo json_encode(['ok' => true, 'saved' => 0, 'msg' => 'No data to save or invalid deck.']);
    exit;
}
if (count($results) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Batch size limited to 1000.']);
    exit;
}

try {
    // Verify ownership
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

    // 1. Sanitize IDs
    $requested = [];
    foreach ($results as $cardId => $status) {
        $cid = filter_var($cardId, FILTER_VALIDATE_INT);
        if ($cid && ($status === 'remembered' || $status === 'forgotten')) {
            $requested[$cid] = $status;
        }
    }
    if (empty($requested)) {
        echo json_encode(['ok' => true, 'saved' => 0, 'msg' => 'No valid cards to save.']);
        exit;
    }

    // 2. Keep only cards that really belong to this deck (and fetch their intervals)
    $idString = implode(',', array_map('intval', array_keys($requested)));
    $cardData = [];
    $res = mysqli_query($conn, "SELECT id, interval_days FROM flashcards
                                WHERE id IN ($idString) AND deck_id = " . (int) $deckId . " AND deleted_at IS NULL");
    while ($row = mysqli_fetch_assoc($res)) {
        $cardData[(int) $row['id']] = (int) $row['interval_days'];
    }

    $toSave = array_intersect_key($requested, $cardData);
    if (empty($toSave)) {
        echo json_encode(['ok' => true, 'saved' => 0, 'msg' => 'No matching cards found in this deck.']);
        exit;
    }

    // Totals come from what will actually be saved (not from what the browser sent)
    $savedCount      = count($toSave);
    $rememberedCount = count(array_filter($toSave, fn($s) => $s === 'remembered'));
    $accuracy        = (int) round(($rememberedCount / $savedCount) * 100);

    mysqli_begin_transaction($conn);

    // 3. Insert session record
    $sessStmt = mysqli_prepare($conn, "INSERT INTO review_sessions
        (user_id, deck_id, total_reviewed, remembered_count, accuracy_percentage, started_at, ended_at)
        VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? SECOND), NOW())");
    mysqli_stmt_bind_param($sessStmt, "iiiiii", $userId, $deckId, $savedCount, $rememberedCount, $accuracy, $durationSeconds);
    mysqli_stmt_execute($sessStmt);
    $sessionId = mysqli_insert_id($conn);
    mysqli_stmt_close($sessStmt);

    // 4. Update each card + write its log row
    $stmtCard = mysqli_prepare($conn, "UPDATE flashcards
        SET review_count = review_count + 1, lapses = lapses + ?, interval_days = ?,
            next_review_at = DATE_ADD(NOW(), INTERVAL ? DAY), updated_at = NOW()
        WHERE id = ? AND deck_id = ?");

    $stmtLog = mysqli_prepare($conn, "INSERT INTO review_logs
        (session_id, card_id, rating, previous_interval, new_interval)
        VALUES (?, ?, ?, ?, ?)");

    foreach ($toSave as $cid => $status) {
        $prevInterval = $cardData[$cid];
        $isLapse      = ($status === 'forgotten') ? 1 : 0;
        $newInterval  = ($status === 'remembered') ? max(1, $prevInterval * 2) : 0;

        // The review_logs.rating ENUM is ('remembered', 'forgot'), NOT 'forgotten'
        $dbRating = ($status === 'remembered') ? 'remembered' : 'forgot';

        mysqli_stmt_bind_param($stmtCard, "iiiii", $isLapse, $newInterval, $newInterval, $cid, $deckId);
        mysqli_stmt_execute($stmtCard);

        mysqli_stmt_bind_param($stmtLog, "iisii", $sessionId, $cid, $dbRating, $prevInterval, $newInterval);
        mysqli_stmt_execute($stmtLog);
    }

    mysqli_stmt_close($stmtCard);
    mysqli_stmt_close($stmtLog);

    mysqli_commit($conn);
    echo json_encode(['ok' => true, 'saved' => $savedCount, 'session_id' => $sessionId]);

} catch (Throwable $e) {
    // Only roll back if a transaction was started; harmless otherwise
    try { mysqli_rollback($conn); } catch (Throwable $ignored) {}
    error_log('save-session.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database exception occurred']);
}