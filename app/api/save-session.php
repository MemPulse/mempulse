<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$userId = (int) $_SESSION['user_id'];
$deckId = filter_var($input['deck_id'] ?? null, FILTER_VALIDATE_INT);
$results = $input['results'] ?? [];

if (!$deckId || empty($results)) {
    echo json_encode(['ok' => true, 'msg' => 'No data to save']);
    exit;
}

mysqli_begin_transaction($conn);

try {
    $totalReviewed = count($results);
    $rememberedCount = 0;

    // Prepared statements for card mutations
    $stmtRemember = mysqli_prepare($conn, "UPDATE flashcards 
        SET review_count = review_count + 1, 
            interval_days = GREATEST(1, interval_days * 2), 
            next_review_at = DATE_ADD(NOW(), INTERVAL GREATEST(1, interval_days * 2) DAY),
            updated_at = NOW()
        WHERE id = ? AND deck_id = ?");

    $stmtForgot = mysqli_prepare($conn, "UPDATE flashcards 
        SET review_count = review_count + 1, 
            lapses = lapses + 1,
            interval_days = 0, 
            next_review_at = NOW(),
            updated_at = NOW()
        WHERE id = ? AND deck_id = ?");

    foreach ($results as $cardId => $status) {
        $cid = (int) $cardId;
        if ($status === 'remembered') {
            $rememberedCount++;
            mysqli_stmt_bind_param($stmtRemember, "ii", $cid, $deckId);
            mysqli_stmt_execute($stmtRemember);
        } else {
            mysqli_stmt_bind_param($stmtForgot, "ii", $cid, $deckId);
            mysqli_stmt_execute($stmtForgot);
        }
    }

    mysqli_stmt_close($stmtRemember);
    mysqli_stmt_close($stmtForgot);

    // Save overarching Session Log for Dashboard analytics
    $accuracy = ($totalReviewed > 0) ? round(($rememberedCount / $totalReviewed) * 100) : 0;
    
    $logStmt = mysqli_prepare($conn, "INSERT INTO review_sessions 
        (user_id, deck_id, total_reviewed, remembered_count, accuracy_percentage, started_at, ended_at) 
        VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 5 MINUTE), NOW())"); // Rough start estimate for batch
    
    mysqli_stmt_bind_param($logStmt, "iiiii", $userId, $deckId, $totalReviewed, $rememberedCount, $accuracy);
    mysqli_stmt_execute($logStmt);
    mysqli_stmt_close($logStmt);

    mysqli_commit($conn);
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['error' => 'Database exception occurred']);
}