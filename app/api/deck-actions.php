<?php
/**
 * Deck Actions API Endpoint
 * 
 * Secure HTTP POST JSON endpoint handling asynchronous deck and flashcard mutation 
 * actions including creation, renaming, archiving, restoring, permanent deletion, 
 * and flashcard addition.
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

/**
 * Generate a unique deck title suggestion when a collision occurs.
 * 
 * @param callable $exists Callback function checking title existence.
 * @param string $title Target base deck title.
 * @param int $max Maximum allowed title character length.
 * @return string Available deck title candidate.
 */
function suggest_unique(callable $exists, string $title, int $max = 150): string
{
    $base = preg_replace('/\s*\(\d+\)$/u', '', $title);
    $base = mb_substr($base, 0, $max - 8, 'UTF-8');
    for ($n = 2; $n < 1000; $n++) {
        $candidate = $base . ' (' . $n . ')';
        if (!$exists($candidate)) {
            return $candidate;
        }
    }
    return $base . ' (' . bin2hex(random_bytes(2)) . ')';
}

// ---------------------------------------------------------------------------
// Request Validation & Authentication
// ---------------------------------------------------------------------------

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

// Anti-CSRF Token Verification
$token = $input['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    respond(403, ['error' => 'Security check failed. Refresh the page and try again.']);
}

require_once __DIR__ . '/../../config/db.php';

$user_id = (int) $_SESSION['user_id'];
$deck_id = filter_var($input['deck_id'] ?? null, FILTER_VALIDATE_INT);
$action  = $input['action'] ?? '';

/**
 * Helper Closure: Check if a live deck title exists for the authenticated user.
 * 
 * @param string $t Deck title string.
 * @param int $exclude_id Optional deck ID to ignore (for renaming).
 * @return bool True if collision exists, false otherwise.
 */
$deck_exists = function (string $t, int $exclude_id = 0) use ($conn, $user_id): bool {
    $s = mysqli_prepare($conn, "SELECT 1 FROM decks WHERE user_id = ? AND title = ? AND id <> ? AND deleted_at IS NULL LIMIT 1");
    mysqli_stmt_bind_param($s, "isi", $user_id, $t, $exclude_id);
    mysqli_stmt_execute($s);
    mysqli_stmt_store_result($s);
    $found = mysqli_stmt_num_rows($s) > 0;
    mysqli_stmt_close($s);
    return $found;
};

// ---------------------------------------------------------------------------
// Action Processor: Create Deck
// ---------------------------------------------------------------------------
if ($action === 'create') {
    $title = trim((string) ($input['title'] ?? ''));

    if ($title === '') {
        respond(422, ['error' => 'Deck name cannot be empty.']);
    }
    if (mb_strlen($title, 'UTF-8') > 150) {
        respond(422, ['error' => 'Deck name must be 150 characters or fewer.']);
    }
    
    if ($deck_exists($title)) {
        respond(409, [
            'error' => 'You already have a deck named "' . $title . '".',
            'suggestion' => suggest_unique($deck_exists, $title)
        ]);
    }
    
    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO decks (user_id, title) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "is", $user_id, $title);
        mysqli_stmt_execute($stmt);
        $new_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        respond(200, ['ok' => true, 'id' => $new_id, 'title' => $title]);
    } catch (Throwable $e) {
        error_log('deck-actions.php (create): ' . $e->getMessage());
        respond(500, ['error' => 'Something went wrong. Please try again.']);
    }
}

// Validate Deck ID for deck-specific actions
if (!$deck_id || $deck_id < 1) {
    respond(400, ['error' => 'Invalid deck.']);
}

// Verify User Ownership over the Target Deck
$check = mysqli_prepare($conn, "SELECT id FROM decks WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($check, "ii", $deck_id, $user_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
$owns_deck = mysqli_stmt_num_rows($check) === 1;
mysqli_stmt_close($check);

if (!$owns_deck) {
    respond(404, ['error' => 'Deck not found.']);
}

// ---------------------------------------------------------------------------
// Action Processor: Targeted Deck Operations
// ---------------------------------------------------------------------------
try {
    // Action: Rename Deck
    if ($action === 'rename') {
        $title = trim((string) ($input['title'] ?? ''));

        if ($title === '') {
            respond(422, ['error' => 'Deck name cannot be empty.']);
        }
        if (mb_strlen($title, 'UTF-8') > 150) {
            respond(422, ['error' => 'Deck name must be 150 characters or fewer.']);
        }

        $same_name = fn(string $t): bool => $deck_exists($t, $deck_id);
        if ($same_name($title)) {
            respond(409, [
                'error' => 'You already have a deck named "' . $title . '".',
                'suggestion' => suggest_unique($same_name, $title)
            ]);
        }

        $stmt = mysqli_prepare($conn, "UPDATE decks SET title = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, "sii", $title, $deck_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        respond(200, ['ok' => true, 'title' => $title]);
    }

    // Action: Soft Delete / Archive Deck and Flashcards
    if ($action === 'archive') {
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

    // Action: Restore Archived Deck and Associated Cards
    if ($action === 'restore') {
        mysqli_begin_transaction($conn);

        // Only cards archived together with the deck (not ones archived individually earlier)
        $stmt = mysqli_prepare($conn, "UPDATE flashcards f
                                        JOIN decks d ON d.id = f.deck_id
                                        SET f.deleted_at = NULL
                                        WHERE f.deck_id = ? AND d.user_id = ?
                                        AND d.deleted_at IS NOT NULL
                                        AND f.deleted_at >= d.deleted_at");
        mysqli_stmt_bind_param($stmt, "ii", $deck_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "UPDATE decks SET deleted_at = NULL WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL");
        mysqli_stmt_bind_param($stmt, "ii", $deck_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        respond(200, ['ok' => true]);
    }
    
    // Action: Permanent Delete Deck and Flashcards
    if ($action === 'permanent_delete') {
        mysqli_begin_transaction($conn);
        
        $stmt = mysqli_prepare($conn, "DELETE FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL");
        mysqli_stmt_bind_param($stmt, "ii", $deck_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        mysqli_commit($conn);
        respond(200, ['ok' => true]);
    }

    // Action: Add Flashcard to Deck
    if ($action === 'add_card') {
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
        mysqli_stmt_close($stmt);

        respond(200, ['ok' => true]);
    }

    // Action: Update Deck Banner Cover
    if ($action === 'update_cover') {
        $cover_image = trim((string) ($input['cover_image'] ?? ''));

        // Validate path security to prevent path traversal (.jpg, .jpeg, .png, .webp)
        if ($cover_image !== '' && !preg_match('/^\.\.\/assets\/images\/deck-header-banner\/[a-zA-Z0-9_\-]+\.(jpg|jpeg|png|webp)$/i', $cover_image)) {
            respond(422, ['error' => 'Invalid image selected.']);
        }

        $db_val = $cover_image === '' ? NULL : $cover_image;

        $stmt = mysqli_prepare($conn, "UPDATE decks SET cover_image = ? WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, "sii", $db_val, $deck_id, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        respond(200, ['ok' => true, 'cover_image' => $cover_image]);
    }

    respond(400, ['error' => 'Unknown action.']);
} catch (Throwable $e) {
    if ($action === 'archive' || $action === 'restore') {
        mysqli_rollback($conn);
    }
    error_log('deck-actions.php: ' . $e->getMessage());
    respond(500, ['error' => 'Something went wrong. Please try again.']);
}

