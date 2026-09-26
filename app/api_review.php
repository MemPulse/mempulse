<?php
// app/api_review.php
session_start();

// Ensure output header is JSON
header("Content-Type: application/json");

// 1. Session Protection
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Load Database Config cleanly
if (file_exists(__DIR__ . "/../config/db.php")) {
    require_once __DIR__ . "/../config/db.php";
} elseif (file_exists(__DIR__ . "/config/db.php")) {
    require_once __DIR__ . "/config/db.php";
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database config not found."]);
    exit;
}

$action = $_GET['action'] ?? '';

// --- ACTION 1: FETCH DUE CARDS ---
if ($action === 'fetch_cards') {
    $deck_id = isset($_GET['deck_id']) ? intval($_GET['deck_id']) : 0;

    if ($deck_id <= 0) {
        echo json_encode(["error" => "Invalid Deck ID"]);
        exit;
    }

    $sql = "SELECT f.id, f.front, f.back, f.interval_days, 
                   COALESCE(us.remember_multiplier, 2.00) AS remember_multiplier,
                   COALESCE(us.forgot_multiplier, 0.50) AS forgot_multiplier
            FROM flashcards f
            JOIN decks d ON f.deck_id = d.id
            LEFT JOIN user_settings us ON d.user_id = us.user_id
            WHERE f.deck_id = ? 
              AND d.user_id = ?
              AND f.deleted_at IS NULL 
              AND f.next_review_at <= NOW()
            ORDER BY f.next_review_at ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $deck_id, $user_id);
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
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true) ?? $_POST;

    $session_id = intval($data['session_id'] ?? 0);
    $card_id = intval($data['card_id'] ?? 0);
    $rating = $data['rating'] ?? 'remembered'; 
    $prev_interval = intval($data['previous_interval'] ?? 1);
    $multiplier = floatval($data['multiplier'] ?? 1.0);

    $new_interval = max(1, (int)round($prev_interval * $multiplier));

    $conn->begin_transaction();

    try {
        $log_sql = "INSERT INTO review_logs (session_id, card_id, rating, previous_interval, new_interval, reviewed_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("iisii", $session_id, $card_id, $rating, $prev_interval, $new_interval);
        $log_stmt->execute();

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

        $rem_inc = ($rating === 'remembered') ? 1 : 0;
        $sess_sql = "UPDATE review_sessions 
                     SET total_reviewed = total_reviewed + 1,
                         remembered_count = remembered_count + ?
                     WHERE id = ? AND user_id = ?";
        $sess_stmt = $conn->prepare($sess_sql);
        $sess_stmt->bind_param("iii", $rem_inc, $session_id, $user_id);
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