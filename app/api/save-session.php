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

// 1. Sanitize IDs and fetch current interval data
$validIds = [];
$rememberedCount = 0;
foreach ($results as $cardId => $status) {
    $cid = filter_var($cardId, FILTER_VALIDATE_INT);
    if ($cid && ($status === 'remembered' || $status === 'forgotten')) {
        $validIds[] = $cid;
        if ($status === 'remembered') $rememberedCount++;
    }
}
$savedCount = count($validIds);

if ($savedCount === 0) {
    echo json_encode(['ok' => true, 'saved' => 0, 'msg' => 'No valid cards to save.']);
    exit;
}

$idString = implode(',', $validIds);
$cardData = [];
$res = mysqli_query($conn, "SELECT id, interval_days FROM flashcards WHERE id IN ($idString) AND deck_id = $deckId AND deleted_at IS NULL");
while ($row = mysqli_fetch_assoc($res)) {
    $cardData[$row['id']] = (int) $row['interval_days'];
}

mysqli_begin_transaction($conn);

try {
    // 2. Insert Session Record First
    $accuracy = (int) round(($rememberedCount / $savedCount) * 100);
    $logStmt = mysqli_prepare($conn, "INSERT INTO review_sessions 
        (user_id, deck_id, total_reviewed, remembered_count, accuracy_percentage, started_at, ended_at) 
        VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? SECOND), NOW())");
    
    mysqli_stmt_bind_param($logStmt, "iiiiii", $userId, $deckId, $savedCount, $rememberedCount, $accuracy, $durationSeconds);
    mysqli_stmt_execute($logStmt);
    $sessionId = mysqli_insert_id($conn);
    mysqli_stmt_close($logStmt);

    // 3. Prepare Statements for Cards and Logs
    $stmtCard = mysqli_prepare($conn, "UPDATE flashcards 
        SET review_count = review_count + 1, lapses = lapses + ?, interval_days = ?, next_review_at = DATE_ADD(NOW(), INTERVAL ? DAY), updated_at = NOW() 
        WHERE id = ? AND deck_id = ?");
        
    $stmtLog = mysqli_prepare($conn, "INSERT INTO review_logs 
        (session_id, card_id, rating, previous_interval, new_interval) 
        VALUES (?, ?, ?, ?, ?)");

    $actuallySaved = 0;

    foreach ($validIds as $cid) {
        if (!isset($cardData[$cid])) continue; // Skip if card was deleted or doesn't belong to deck
        
        $status = $results[$cid];
        $prevInterval = $cardData[$cid];
        
        // Calculate intervals
        $isLapse = ($status === 'forgotten') ? 1 : 0;
        $newInterval = ($status === 'remembered') ? max(1, $prevInterval * 2) : 0;
        
        // Update Card
        mysqli_stmt_bind_param($stmtCard, "iiiii", $isLapse, $newInterval, $newInterval, $cid, $deckId);
        mysqli_stmt_execute($stmtCard);
        
        if (mysqli_stmt_affected_rows($stmtCard) > 0) {
            // Insert Log
            mysqli_stmt_bind_param($stmtLog, "iisii", $sessionId, $cid, $status, $prevInterval, $newInterval);
            mysqli_stmt_execute($stmtLog);
            $actuallySaved++;
        }
    }

    mysqli_stmt_close($stmtCard);
    mysqli_stmt_close($stmtLog);

    mysqli_commit($conn);
    echo json_encode(['ok' => true, 'saved' => $actuallySaved]);

} catch (Throwable $e) {
    mysqli_rollback($conn);
    error_log('save-session.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database exception occurred']);
}