<?php
/**
 * Flashcard Actions API Endpoint
 * 
 * Secure HTTP POST JSON endpoint handling asynchronous flashcard mutations 
 * including addition, editing, and soft-deletion.
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

/**
 * Send a structured JSON HTTP response and terminate request execution.
 * 
 * @param int $status HTTP status code.
 * @param array $body Response payload data.
 * @return void
 */
function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

// 1. Request Method Verification
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed.']);
}

// 2. Authentication Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    respond(401, ['error' => 'Please log in again.']);
}

// 3. Input Data Parsing
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(400, ['error' => 'Invalid request structure.']);
}

// 4. Anti-CSRF Token Verification
$token = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    respond(403, ['error' => 'Security check failed. Refresh the page and try again.']);
}

require_once __DIR__ . '/../../config/db.php';

$user_id = (int) $_SESSION['user_id'];
$action  = $input['action'] ?? '';
$deck_id = filter_var($input['deck_id'] ?? null, FILTER_VALIDATE_INT);

if (!$deck_id || $deck_id < 1) {
    respond(400, ['error' => 'Invalid deck selection.']);
}

// 5. Verify User Ownership of Target Deck
$checkStmt = mysqli_prepare($conn, "SELECT id FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
mysqli_stmt_bind_param($checkStmt, "ii", $deck_id, $user_id);
mysqli_stmt_execute($checkStmt);
mysqli_stmt_store_result($checkStmt);
$ownsDeck = mysqli_stmt_num_rows($checkStmt) === 1;
mysqli_stmt_close($checkStmt);

if (!$ownsDeck) {
    respond(404, ['error' => 'Deck not found or access denied.']);
}

// ---------------------------------------------------------------------------
// Action Processor
// ---------------------------------------------------------------------------
try {
    // Action: Add Flashcard
    if ($action === 'add') {
        $front = trim((string) ($input['front'] ?? ''));
        $back  = trim((string) ($input['back'] ?? ''));

        if ($front === '' || $back === '') {
            respond(422, ['error' => 'Both the front and back of the card are required.']);
        }
        if (mb_strlen($front, 'UTF-8') > 5000 || mb_strlen($back, 'UTF-8') > 5000) {
            respond(422, ['error' => 'Each side must be 5000 characters or fewer.']);
        }

        $stmt = mysqli_prepare($conn, "INSERT INTO flashcards (deck_id, front, back) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iss", $deck_id, $front, $back);
        mysqli_stmt_execute($stmt);
        $newCardId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Fetch newly created card record with defaults
        $fetchStmt = mysqli_prepare($conn, "SELECT id, deck_id, front, back, interval_days, next_review_at, review_count, lapses, created_at, updated_at FROM flashcards WHERE id = ?");
        mysqli_stmt_bind_param($fetchStmt, "i", $newCardId);
        mysqli_stmt_execute($fetchStmt);
        $card = mysqli_fetch_assoc(mysqli_stmt_get_result($fetchStmt));
        mysqli_stmt_close($fetchStmt);

        respond(200, [
            'ok'   => true,
            'card' => $card
        ]);
    }

    // Action: Update Flashcard
    if ($action === 'update') {
        $card_id        = filter_var($input['card_id'] ?? null, FILTER_VALIDATE_INT);
        $front          = trim((string) ($input['front'] ?? ''));
        $back           = trim((string) ($input['back'] ?? ''));
        $next_review_at = trim((string) ($input['next_review_at'] ?? ''));

        if (!$card_id || $card_id < 1) {
            respond(400, ['error' => 'Invalid card specified.']);
        }
        if ($front === '' || $back === '') {
            respond(422, ['error' => 'Both the front and back of the card are required.']);
        }
        if (mb_strlen($front, 'UTF-8') > 5000 || mb_strlen($back, 'UTF-8') > 5000) {
            respond(422, ['error' => 'Each side must be 5000 characters or fewer.']);
        }

        // Validate and sanitize custom next_review_at if provided
        $formattedNextReview = null;
        if ($next_review_at !== '') {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $next_review_at) 
               ?: DateTime::createFromFormat('Y-m-d H:i:s', $next_review_at);
            if ($dt !== false) {
                $formattedNextReview = $dt->format('Y-m-d H:i:s');
            }
        }

        if ($formattedNextReview !== null) {
            $stmt = mysqli_prepare($conn, "UPDATE flashcards SET front = ?, back = ?, next_review_at = ? WHERE id = ? AND deck_id = ? AND deleted_at IS NULL");
            mysqli_stmt_bind_param($stmt, "sssii", $front, $back, $formattedNextReview, $card_id, $deck_id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE flashcards SET front = ?, back = ? WHERE id = ? AND deck_id = ? AND deleted_at IS NULL");
            mysqli_stmt_bind_param($stmt, "ssii", $front, $back, $card_id, $deck_id);
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Fetch refreshed card record
        $fetchStmt = mysqli_prepare($conn, "SELECT id, deck_id, front, back, interval_days, next_review_at, review_count, lapses, created_at, updated_at FROM flashcards WHERE id = ?");
        mysqli_stmt_bind_param($fetchStmt, "i", $card_id);
        mysqli_stmt_execute($fetchStmt);
        $card = mysqli_fetch_assoc(mysqli_stmt_get_result($fetchStmt));
        mysqli_stmt_close($fetchStmt);

        respond(200, [
            'ok'   => true,
            'card' => $card
        ]);
    }

    // Action: Soft Delete Flashcard
    if ($action === 'delete') {
        $card_id = filter_var($input['card_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$card_id || $card_id < 1) {
            respond(400, ['error' => 'Invalid card specified.']);
        }

        $stmt = mysqli_prepare($conn, "UPDATE flashcards SET deleted_at = NOW() WHERE id = ? AND deck_id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, "ii", $card_id, $deck_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        respond(200, ['ok' => true, 'card_id' => $card_id]);
    }

    respond(400, ['error' => 'Unknown action.']);

} catch (Throwable $e) {
    error_log('flashcard-actions.php: ' . $e->getMessage());
    respond(500, ['error' => 'Something went wrong. Please try again.']);
}