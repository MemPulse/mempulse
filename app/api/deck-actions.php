<?php
// app/api/deck-actions.php
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
    // Returns "Title (2)", "Title (3)", ... the first one $exists() says is free.
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

require_once __DIR__ . '/../../config/db.php';

$user_id = (int) $_SESSION['user_id'];
$deck_id = filter_var($input['deck_id'] ?? null, FILTER_VALIDATE_INT);
$action  = $input['action'] ?? '';

// Does this user already have a live deck with this name? (optionally ignoring one deck id)
$deck_exists = function (string $t, int $exclude_id = 0) use ($conn, $user_id): bool {
    $s = mysqli_prepare($conn, "SELECT 1 FROM decks WHERE user_id = ? AND title = ? AND id <> ? AND deleted_at IS NULL LIMIT 1");
    mysqli_stmt_bind_param($s, "isi", $user_id, $t, $exclude_id);
    mysqli_stmt_execute($s);
    mysqli_stmt_store_result($s);
    $found = mysqli_stmt_num_rows($s) > 0;
    mysqli_stmt_close($s);
    return $found;
};

// Create Deck
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

    // Delete Deck
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
        
        // Add Card
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

    respond(400, ['error' => 'Unknown action.']);
} catch (Throwable $e) {
    if ($action === 'delete') {
        mysqli_rollback($conn);
    }
    error_log('deck-actions.php: ' . $e->getMessage());
    respond(500, ['error' => 'Something went wrong. Please try again.']);
}