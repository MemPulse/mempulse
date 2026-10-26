<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed.']); exit; }
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
require_once __DIR__ . '/../../config/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$userId = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) { http_response_code(400); echo json_encode(['error' => 'Invalid request.']); exit; }
$token = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) { http_response_code(403); echo json_encode(['error' => 'Security check failed. Refresh the page and try again.']); exit; }
$deckId = filter_var($input['deck_id'] ?? null, FILTER_VALIDATE_INT);
$results = $input['results'] ?? [];
$duration = filter_var($input['duration_seconds'] ?? 0, FILTER_VALIDATE_INT);
$duration = $duration === false ? 0 : max(0, min((int)$duration, 21600));
$label = 'Random Practice';
if (!$deckId || $deckId < 1 || !is_array($results) || count($results) > 1000) { http_response_code(400); echo json_encode(['error' => 'Invalid practice session.']); exit; }
try {
    $stmt = mysqli_prepare($conn, 'SELECT id FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    mysqli_stmt_bind_param($stmt, 'ii', $deckId, $userId); mysqli_stmt_execute($stmt); mysqli_stmt_store_result($stmt);
    $ownsDeck = mysqli_stmt_num_rows($stmt) === 1; mysqli_stmt_close($stmt);
    if (!$ownsDeck) { http_response_code(404); echo json_encode(['error' => 'Deck not found or access denied.']); exit; }
    $clean = [];
    foreach ($results as $cardId => $rating) {
        $id = filter_var($cardId, FILTER_VALIDATE_INT);
        if ($id && $id > 0 && ($rating === 'remembered' || $rating === 'forgotten')) $clean[$id] = $rating;
    }
    $ids = array_keys($clean); $remembered = count(array_filter($clean, fn($r) => $r === 'remembered')); $total = count($clean);
    if ($ids) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT COUNT(*) FROM flashcards WHERE deck_id = ? AND deleted_at IS NULL AND id IN ($marks)";
        $params = array_merge([(int)$deckId], $ids); $types = str_repeat('i', count($params));
        $stmt = mysqli_prepare($conn, $sql); mysqli_stmt_bind_param($stmt, $types, ...$params); mysqli_stmt_execute($stmt); mysqli_stmt_bind_result($stmt, $validCount); mysqli_stmt_fetch($stmt); mysqli_stmt_close($stmt);
        if ((int)$validCount !== $total) { http_response_code(400); echo json_encode(['error' => 'One or more cards are not active in this deck.']); exit; }
    }
    $accuracy = $total > 0 ? (int)round(($remembered / $total) * 100) : 0;
    $stmt = mysqli_prepare($conn, 'INSERT INTO random_practice_sessions (user_id, deck_id, label, total_rated, remembered_count, accuracy_percentage, duration_seconds, started_at, ended_at) VALUES (?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? SECOND), NOW())');
    mysqli_stmt_bind_param($stmt, 'iisiiiii', $userId, $deckId, $label, $total, $remembered, $accuracy, $duration, $duration);
    mysqli_stmt_execute($stmt); $sessionId = mysqli_insert_id($conn); mysqli_stmt_close($stmt);
    echo json_encode(['ok' => true, 'saved' => $total, 'session_id' => $sessionId, 'label' => $label]);
} catch (Throwable $e) {
    error_log('save-random-practice.php error: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'Could not save random practice session.']);
}
