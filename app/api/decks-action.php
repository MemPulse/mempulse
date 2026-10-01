<?php
// app/deck-actions.php
// JSON endpoint for deck actions: rename and delete (soft delete).
session_start();
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed.']);
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    respond(401, ['error' => 'Please log in again.']);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(400, ['error' => 'Invalid request.']);
}

// CSRF check
$token = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    respond(403, ['error' => 'Security check failed. Refresh the page and try again.']);
}

require_once __DIR__ . '/../config/db.php';

$user_id = (int) $_SESSION['user_id'];
$deck_id = filter_var($input['deck_id'] ?? null, FILTER_VALIDATE_INT);
$action  = $input['action'] ?? '';

if (!$deck_id || $deck_id < 1) {
    respond(400, ['error' => 'Invalid deck.']);
}

// The deck must belong to the logged-in user and not already be deleted.
$check = mysqli_prepare($conn, "SELECT id FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
mysqli_stmt_bind_param($check, "ii", $deck_id, $user_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
$owns_deck = mysqli_stmt_num_rows($check) === 1;
mysqli_stmt_close($check);

if (!$owns_deck) {
    respond(404, ['error' => 'Deck not found.']);
}

try {
    if ($action === 'rename') {
        $title = trim((string) ($input['title'] ?? ''));

        if ($title === '') {
            respond(422, ['error' => 'Deck name cannot be empty.']);
        }
        if (mb_strlen($title, 'UTF-8') > 150) {
            respond(422, ['error' => 'Deck name must be 150 characters or fewer.']);
        }

        $stmt = mysqli_prepare($conn, "UPDATE decks SET title = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, "sii", $title, $deck_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        respond(200, ['ok' => true, 'title' => $title]);
    }

    if ($action === 'delete') {
        // Soft delete the deck and its cards together so dashboard metrics
        // (due, mastered, tough cards) stop counting them.
        mysqli_begin_transaction($conn);

        $stmt = mysqli_prepare($conn, "UPDATE decks SET deleted_at = NOW() WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, "ii", $deck_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE flashcards SET deleted_at = NOW() WHERE deck_id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, "i", $deck_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);

        respond(200, ['ok' => true]);
    }

    respond(400, ['error' => 'Unknown action.']);
} catch (Throwable $e) {
    if ($action === 'delete') {
        mysqli_rollback($conn);
    }
    error_log('deck-actions.php: ' . $e->getMessage());
    respond(500, ['error' => 'Something went wrong. Please try again.']);
}