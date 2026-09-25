<?php
header("Content-Type: application/json");
require_once "/mempulse/config/db.php";
$action = $_GET['action'] ?? '';

// --- ACTION 1: FETCH DUE CARDS & START SESSION ---
if ($action === 'fetch_cards') {
    $user_id = 1; // Replace with session user_id e.g., $_SESSION['user_id']
    $deck_id = isset($_GET['deck_id']) ? intval($_GET['deck_id']) : 0;

    if ($deck_id <= 0) {
        echo json_encode(["error" => "Invalid Deck ID"]);
        exit;
    }

    // 1. Fetch due flashcards along with the user's custom SRS settings
    $sql = "SELECT f.id, f.front, f.back, f.interval_days, 
                   COALESCE(us.remember_multiplier, 2.00) AS remember_multiplier,
                   COALESCE(us.forgot_multiplier, 0.50) AS forgot_multiplier
            FROM flashcards f
            JOIN decks d ON f.deck_id = d.id
            LEFT JOIN user_settings us ON d.user_id = us.user_id
            WHERE f.deck_id = ? 
              AND f.deleted_at IS NULL 
              AND f.next_review_at <= NOW()
            ORDER BY f.next_review_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deck_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $cards = [];
    while ($row = $result->fetch_assoc()) {
        $cards[] = $row;
    }

    if (count($cards) === 0) {
        echo json_encode(["session_id" => null, "cards" => []]);
        exit;
    }

    // 2. Create a new record in review_sessions
    $session_sql = "INSERT INTO review_sessions (user_id, deck_id, started_at) VALUES (?, ?, NOW())";
    $session_stmt = $conn->prepare($session_sql);
    $session_stmt->bind_param("ii", $user_id, $deck_id);
    $session_stmt->execute();
    $session_id = $session_stmt->insert_id;

    echo json_encode([
        "session_id" => $session_id,
        "cards" => $cards
    ]);
    exit;
}

// --- ACTION 2: SAVE CARD RATING ---
if ($action === 'rate_card') {
    $data = json_decode(file_get_contents("php_input"), true) ?? $_POST;

    $session_id = intval($data['session_id']);
    $card_id = intval($data['card_id']);
    $rating = $data['rating']; // 'remembered' or 'forgot'
    $prev_interval = intval($data['previous_interval']);
    $multiplier = floatval($data['multiplier']);

    // Calculate new interval (minimum 1 day)
    $new_interval = max(1, (int)round($prev_interval * $multiplier));

    $conn->begin_transaction();

    try {
        // 1. Insert into review_logs
        $log_sql = "INSERT INTO review_logs (session_id, card_id, rating, previous_interval, new_interval, reviewed_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("iisiI", $session_id, $card_id, $rating, $prev_interval, $new_interval);
        $log_stmt->execute();

        // 2. Update flashcard metrics
        $lapses_inc = ($rating === 'forgot') ? 1 : 0;
        $card_sql = "UPDATE flashcards 
                     SET interval_days = ?, 
                         next_review_at = DATE_ADD(NOW(), INTERVAL ? DAY), 
                         review_count = review_count + 1,
                         lapses = lapses + ?
                     WHERE id = ?";
        $card_stmt = $conn->prepare($card_sql);
        $card_stmt->bind_param("iiii", $new_interval, $new_interval, $lapses_inc, $card_id);
        $card_stmt->execute();

        // 3. Update active session counters
        $rem_inc = ($rating === 'remembered') ? 1 : 0;
        $sess_sql = "UPDATE review_sessions 
                     SET total_reviewed = total_reviewed + 1,
                         remembered_count = remembered_count + ?
                     WHERE id = ?";
        $sess_stmt = $conn->prepare($sess_sql);
        $sess_stmt->bind_param("ii", $rem_inc, $session_id);
        $sess_stmt->execute();

        $conn->commit();
        echo json_encode(["status" => "success", "new_interval" => $new_interval]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}
?>