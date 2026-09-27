<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = intval($_SESSION['user_id'] ?? 0);
$action  = $_GET['action'] ?? '';

// Parse incoming input (handles both standard POST forms and JSON payloads)
$jsonInput = json_decode(file_get_contents('php://input'), true);
$input     = is_array($jsonInput) ? $jsonInput : $_POST;

if ($action === 'create_deck') {
    $title = trim($input['title'] ?? '');

    if (empty($title)) {
        echo json_encode(['status' => 'error', 'message' => 'Deck title cannot be empty.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO decks (user_id, title, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $title);

    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        echo json_encode([
            'status' => 'success',
            'deck'   => [
                'id'    => $new_id,
                'title' => $title
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

// Check session authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$action  = $_GET['action'] ?? '';

// --- ACTION 1: FETCH DUE CARDS ---
if ($action === 'fetch_cards') {
    $deck_id = intval($_GET['deck_id'] ?? 0);

    if ($deck_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Invalid Deck ID"]);
        exit;
    }

    try {
        // Create review session log
        $session_stmt = $conn->prepare("
            INSERT INTO review_sessions (user_id, deck_id, started_at) 
            VALUES (?, ?, NOW())
        ");
        $session_stmt->bind_param("ii", $user_id, $deck_id);
        $session_stmt->execute();
        $session_id = $conn->insert_id;

        // Fetch due flashcards belonging to the logged-in user
        $sql = "
            SELECT 
                f.id, 
                f.front, 
                f.back, 
                f.interval_days,
                COALESCE(us.remember_multiplier, 2.00) AS remember_multiplier,
                COALESCE(us.forgot_multiplier, 0.50) AS forgot_multiplier
            FROM flashcards f
            JOIN decks d ON f.deck_id = d.id
            LEFT JOIN user_settings us ON d.user_id = us.user_id
            WHERE f.deck_id = ? 
              AND d.user_id = ? 
              AND f.deleted_at IS NULL 
              AND f.next_review_at <= NOW()
            ORDER BY f.next_review_at ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $deck_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $cards = [];
        while ($row = $result->fetch_assoc()) {
            $cards[] = $row;
        }

        echo json_encode([
            "status"     => "success",
            "session_id" => $session_id,
            "cards"      => $cards
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// --- ACTION 2: RATE CARD ---
if ($action === 'rate_card') {
    $input = json_decode(file_get_contents('php://input'), true);

    $session_id        = intval($input['session_id'] ?? 0);
    $card_id           = intval($input['card_id'] ?? 0);
    $rating            = $input['rating'] ?? '';
    $previous_interval = floatval($input['previous_interval'] ?? 1);
    $multiplier        = floatval($input['multiplier'] ?? 1);

    if ($card_id <= 0 || !in_array($rating, ['remembered', 'forgot'])) {
        echo json_encode(["status" => "error", "message" => "Invalid payload"]);
        exit;
    }

    try {
        // Calculate new review interval
        $new_interval = max(1, (int)round($previous_interval * $multiplier));

        // Update flashcard schedule
        $update_card = $conn->prepare("
            UPDATE flashcards f
            JOIN decks d ON f.deck_id = d.id
            SET f.interval_days = ?, 
                f.next_review_at = DATE_ADD(NOW(), INTERVAL ? DAY)
            WHERE f.id = ? AND d.user_id = ?
        ");
        $update_card->bind_param("iiii", $new_interval, $new_interval, $card_id, $user_id);
        $update_card->execute();

        // Update review session stats
        $is_remembered = ($rating === 'remembered') ? 1 : 0;
        $update_session = $conn->prepare("
            UPDATE review_sessions 
            SET total_reviewed = total_reviewed + 1,
                remembered_count = remembered_count + ?,
                ended_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $update_session->bind_param("iii", $is_remembered, $session_id, $user_id);
        $update_session->execute();

        echo json_encode(["status" => "success", "new_interval" => $new_interval]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
// --- ACTION 3: GET DASHBOARD STATS ---
if ($action === 'get_dashboard_stats') {
    try {
        $total_stmt = $conn->prepare("
            SELECT COUNT(f.id) AS total_cards 
            FROM flashcards f
            JOIN decks d ON f.deck_id = d.id
            WHERE d.user_id = ? AND f.deleted_at IS NULL AND d.deleted_at IS NULL
        ");
        $total_stmt->bind_param("i", $user_id);
        $total_stmt->execute();
        $total_cards = $total_stmt->get_result()->fetch_assoc()['total_cards'] ?? 0;

        $due_stmt = $conn->prepare("
            SELECT COUNT(f.id) AS due_today 
            FROM flashcards f
            JOIN decks d ON f.deck_id = d.id
            WHERE d.user_id = ? AND f.deleted_at IS NULL AND d.deleted_at IS NULL AND f.next_review_at <= NOW()
        ");
        $due_stmt->bind_param("i", $user_id);
        $due_stmt->execute();
        $due_today = $due_stmt->get_result()->fetch_assoc()['due_today'] ?? 0;

        $streak_stmt = $conn->prepare("SELECT COALESCE(study_streak, 0) AS streak FROM user_settings WHERE user_id = ?");
        $streak_stmt->bind_param("i", $user_id);
        $streak_stmt->execute();
        $streak = $streak_stmt->get_result()->fetch_assoc()['streak'] ?? 0;

        // Fetch quick decks for sidebar
        $decks_stmt = $conn->prepare("SELECT id, title FROM decks WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at ASC");
        $decks_stmt->bind_param("i", $user_id);
        $decks_stmt->execute();
        $decks_result = $decks_stmt->get_result();

        $quick_decks = [];
        while ($row = $decks_result->fetch_assoc()) {
            $quick_decks[] = $row;
        }

        echo json_encode([
            "status"      => "success",
            "stats"       => [
                "streak"      => $streak,
                "total_cards" => $total_cards,
                "due_today"   => $due_today
            ],
            "quick_decks" => $quick_decks
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
// --- ACTION 4: GET DUE DECKS ---
if ($action === 'get_due_decks') {
    try {
        $stmt = $conn->prepare("
            SELECT d.id AS deck_id, d.title AS deck_title, COUNT(f.id) AS due_count
            FROM decks d
            JOIN flashcards f ON d.id = f.deck_id
            WHERE d.user_id = ? AND d.deleted_at IS NULL AND f.deleted_at IS NULL AND f.next_review_at <= NOW()
            GROUP BY d.id, d.title
            ORDER BY due_count DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $decks = [];
        while ($row = $result->fetch_assoc()) {
            $decks[] = $row;
        }

        echo json_encode(["status" => "success", "decks" => $decks]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// --- ACTION 5: GET RECENT LOGS ---
if ($action === 'get_recent_logs') {
    try {
        $stmt = $conn->prepare("
            SELECT rs.id, d.title AS deck_title, rs.total_reviewed, rs.ended_at,
                   CASE WHEN rs.total_reviewed > 0 
                        THEN (rs.remembered_count / rs.total_reviewed) * 100 
                        ELSE 0 
                   END AS accuracy_percentage
            FROM review_sessions rs
            JOIN decks d ON rs.deck_id = d.id
            WHERE rs.user_id = ?
            ORDER BY rs.started_at DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }

        echo json_encode(["status" => "success", "logs" => $logs]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// Default fallback
echo json_encode(["status" => "error", "message" => "Invalid action"]);
exit;

// --- ACTION 6: ADD CARD ---
if ($action === 'add_card') {
    $input = json_decode(file_get_contents('php://input'), true);

    $deck_id = intval($input['deck_id'] ?? 0);
    $front   = trim($input['front'] ?? '');
    $back    = trim($input['back'] ?? '');

    if ($deck_id <= 0 || empty($front) || empty($back)) {
        echo json_encode(["status" => "error", "message" => "Please select a deck and fill in both front and back fields."]);
        exit;
    }

    try {
        // Validate deck ownership
        $deck_check = $conn->prepare("SELECT id FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        $deck_check->bind_param("ii", $deck_id, $user_id);
        $deck_check->execute();
        if ($deck_check->get_result()->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "Invalid deck selected or access denied."]);
            exit;
        }

        // Insert new flashcard
        $stmt = $conn->prepare("
            INSERT INTO flashcards (deck_id, front, back, interval_days, next_review_at) 
            VALUES (?, ?, ?, 1, NOW())
        ");
        $stmt->bind_param("iss", $deck_id, $front, $back);
        $stmt->execute();

        echo json_encode(["status" => "success", "message" => "Flashcard created successfully!"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// --- ACTION 7: GET USER DECKS ---
if ($action === 'get_user_decks') {
    try {
        $stmt = $conn->prepare("SELECT id, title FROM decks WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $decks = [];
        while ($row = $result->fetch_assoc()) {
            $decks[] = $row;
        }

        echo json_encode(["status" => "success", "decks" => $decks]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

// --- ACTION 8: CREATE DECK ---
if ($action === 'create_deck') {
    $input = json_decode(file_get_contents('php://input'), true);
    $title = trim($input['title'] ?? '');

    if (empty($title)) {
        echo json_encode(["status" => "error", "message" => "Deck title cannot be empty."]);
        exit;
    }

    try {
        // Safe insert for user deck
        $stmt = $conn->prepare("INSERT INTO decks (user_id, title) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $title);
        $stmt->execute();
        $new_id = $conn->insert_id;

        echo json_encode([
            "status"  => "success",
            "message" => "Deck created successfully!",
            "deck"    => ["id" => $new_id, "title" => $title]
        ]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}