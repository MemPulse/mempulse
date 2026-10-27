<?php
define('ADMIN_JSON', true);
require_once __DIR__ . '/../includes/guard.php';

header('Content-Type: application/json; charset=utf-8');

function userLearningRespond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    userLearningRespond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$targetId = filter_var($_GET['user_id'] ?? null, FILTER_VALIDATE_INT);
if ($targetId === false || $targetId === null || $targetId < 1) {
    userLearningRespond(422, ['success' => false, 'message' => 'A valid user ID is required.']);
}

$requestedPage = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT);
if ($requestedPage === false || $requestedPage < 1) {
    userLearningRespond(422, ['success' => false, 'message' => 'A valid session page is required.']);
}

function userLearningRows(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log('Admin user learning data query prepare failed: ' . mysqli_error($conn));
        userLearningRespond(500, ['success' => false, 'message' => 'Unable to load this user’s learning data.']);
    }

    if ($types !== '' && !mysqli_stmt_bind_param($stmt, $types, ...$params)) {
        error_log('Admin user learning data parameter binding failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        userLearningRespond(500, ['success' => false, 'message' => 'Unable to load this user’s learning data.']);
    }

    if (!mysqli_stmt_execute($stmt)) {
        error_log('Admin user learning data query failed: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        userLearningRespond(500, ['success' => false, 'message' => 'Unable to load this user’s learning data.']);
    }

    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        error_log('Admin user learning data result unavailable: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        userLearningRespond(500, ['success' => false, 'message' => 'Unable to load this user’s learning data.']);
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    mysqli_stmt_close($stmt);
    return $rows;
}

$userRows = userLearningRows(
    $conn,
    'SELECT u.id, s.theme, s.remember_multiplier, s.forgot_multiplier,
            s.initial_interval_days, s.maximum_interval_days
       FROM users u
       LEFT JOIN user_settings s ON s.user_id = u.id
      WHERE u.id = ?
      LIMIT 1',
    'i',
    [$targetId]
);

if (!$userRows) {
    userLearningRespond(404, ['success' => false, 'message' => 'That user could not be found.']);
}

$settings = $userRows[0];
$decksById = [];
$deckRows = userLearningRows(
    $conn,
    'SELECT d.id, d.title, d.created_at, d.deleted_at,
            fc.id AS card_id, fc.front, fc.back, fc.interval_days, fc.next_review_at,
            fc.review_count, fc.lapses, fc.deleted_at AS card_deleted_at
       FROM decks d
       LEFT JOIN flashcards fc ON fc.deck_id = d.id
      WHERE d.user_id = ?
      ORDER BY d.deleted_at IS NOT NULL, d.title ASC, fc.id ASC',
    'i',
    [$targetId]
);

foreach ($deckRows as $row) {
    $deckId = (int) $row['id'];
    if (!isset($decksById[$deckId])) {
        $decksById[$deckId] = [
            'id' => $deckId,
            'title' => (string) $row['title'],
            'createdAt' => $row['created_at'] === null ? null : date('M j, Y', strtotime($row['created_at'])),
            'archived' => $row['deleted_at'] !== null,
            'cards' => [],
        ];
    }

    if ($row['card_id'] !== null) {
        $decksById[$deckId]['cards'][] = [
            'id' => (int) $row['card_id'],
            'front' => (string) $row['front'],
            'back' => (string) $row['back'],
            'intervalDays' => (int) $row['interval_days'],
            'nextReviewAt' => $row['next_review_at'] === null ? null : date('M j, Y g:i A', strtotime($row['next_review_at'])),
            'reviews' => (int) $row['review_count'],
            'lapses' => (int) $row['lapses'],
            'archived' => $row['card_deleted_at'] !== null,
        ];
    }
}

$pageSize = 25;
$countRows = userLearningRows(
    $conn,
    "SELECT COUNT(*) AS total FROM (
            SELECT rs.id
              FROM review_sessions rs
             WHERE rs.user_id = ?
            UNION ALL
            SELECT rp.id
              FROM random_practice_sessions rp
             WHERE rp.user_id = ?
        ) session_history",
    'ii',
    [$targetId, $targetId]
);
$sessionTotal = (int) ($countRows[0]['total'] ?? 0);
$sessionPages = max(1, (int) ceil($sessionTotal / $pageSize));
$sessionPage = min((int) $requestedPage, $sessionPages);
$sessionOffset = ($sessionPage - 1) * $pageSize;

$sessions = userLearningRows(
    $conn,
    "SELECT *
       FROM (
            SELECT 'review' AS session_type, 'Review session' AS label,
                   rs.id AS session_id, d.title AS deck_title,
                   rs.started_at, rs.ended_at, rs.total_reviewed AS cards_reviewed,
                   rs.remembered_count,
                   CASE WHEN rs.total_reviewed > 0
                        THEN ROUND(rs.remembered_count / rs.total_reviewed * 100, 1)
                        ELSE 0 END AS accuracy,
                   CASE WHEN rs.ended_at IS NOT NULL
                        THEN TIMESTAMPDIFF(SECOND, rs.started_at, rs.ended_at)
                        ELSE NULL END AS duration_seconds
              FROM review_sessions rs
              LEFT JOIN decks d ON d.id = rs.deck_id
             WHERE rs.user_id = ?
            UNION ALL
            SELECT 'practice', rp.label,
                   rp.id, d.title, rp.started_at, rp.ended_at, rp.total_rated,
                   rp.remembered_count, rp.accuracy_percentage, rp.duration_seconds
              FROM random_practice_sessions rp
              LEFT JOIN decks d ON d.id = rp.deck_id
             WHERE rp.user_id = ?
        ) session_history
      ORDER BY started_at DESC, session_id DESC
      LIMIT ? OFFSET ?",
    'iiii',
    [$targetId, $targetId, $pageSize, $sessionOffset]
);

$sessionData = [];
foreach ($sessions as $session) {
    $duration = $session['duration_seconds'] === null ? null : (int) $session['duration_seconds'];
    $sessionData[] = [
        'type' => (string) $session['session_type'],
        'label' => (string) $session['label'],
        'deck' => $session['deck_title'] === null ? 'Deck unavailable' : (string) $session['deck_title'],
        'startedAt' => $session['started_at'] === null ? null : date('M j, Y g:i A', strtotime($session['started_at'])),
        'endedAt' => $session['ended_at'] === null ? null : date('M j, Y g:i A', strtotime($session['ended_at'])),
        'cardsReviewed' => (int) $session['cards_reviewed'],
        'remembered' => (int) $session['remembered_count'],
        'accuracy' => (float) $session['accuracy'],
        'durationSeconds' => $duration,
    ];
}

mysqli_close($conn);

userLearningRespond(200, [
    'success' => true,
    'preferences' => [
        'theme' => (string) ($settings['theme'] ?? 'system'),
        'rememberMultiplier' => $settings['remember_multiplier'] === null ? 2.0 : (float) $settings['remember_multiplier'],
        'forgotMultiplier' => $settings['forgot_multiplier'] === null ? 0.5 : (float) $settings['forgot_multiplier'],
        'initialIntervalDays' => $settings['initial_interval_days'] === null ? 1 : (int) $settings['initial_interval_days'],
        'maximumIntervalDays' => $settings['maximum_interval_days'] === null ? 365 : (int) $settings['maximum_interval_days'],
    ],
    'decks' => array_values($decksById),
    'sessions' => $sessionData,
    'sessionPage' => $sessionPage,
    'sessionPages' => $sessionPages,
    'sessionTotal' => $sessionTotal,
]);
