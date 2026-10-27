
<?php
require_once __DIR__ . '/../includes/guard.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isset($conn) || !($conn instanceof mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection unavailable.']);
    exit;
}

$q = isset($_GET['q']) && is_string($_GET['q'])
    ? trim($_GET['q'])
    : '';

$q = mb_substr($q, 0, 100);

if (mb_strlen($q) < 2) {
    echo json_encode([
        'users' => [],
        'content' => [],
        'activity' => [],
        'audit' => [],
    ]);
    exit;
}

$like = '%' . $q . '%';

function globalSearchRows(
    mysqli $conn,
    string $sql,
    string $types,
    array $params
): array {
    $rows = [];
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        error_log('Global search prepare failed: ' . mysqli_error($conn));
        return [];
    }

    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($result && ($row = mysqli_fetch_assoc($result))) {
            $rows[] = $row;
        }

        if ($result) {
            mysqli_free_result($result);
        }
    } else {
        error_log('Global search execute failed: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

$term = rawurlencode($q);

/*
 * 1. USERS
 * Search by first name, last name, or email.
 */
$userRows = globalSearchRows(
    $conn,
    "SELECT id, first_name, last_name, email, role,
            account_status, deleted_at
       FROM users
      WHERE first_name LIKE ?
         OR last_name LIKE ?
         OR email LIKE ?
      ORDER BY
        CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END,
        first_name ASC
      LIMIT 8",
    'sss',
    [$like, $like, $like]
);

$users = [];

foreach ($userRows as $r) {
    $name = trim($r['first_name'] . ' ' . $r['last_name']);
    $name = $name !== '' ? $name : $r['email'];

    $status = $r['deleted_at'] !== null
        ? 'Archived'
        : (string) $r['account_status'];

    $users[] = [
        'title' => $name,
        'detail' => $r['email'] . ' · ' . $r['role'] . ' · ' . $status,
        'url' => 'views/users.php?q=' . $term,
        'icon' => 'group',
    ];
}

/*
 * 2. CONTENT
 * Search learners and show their active deck/card counts.
 * Deck titles and flashcard text are intentionally not exposed.
 */
$contentRows = globalSearchRows(
    $conn,
    "SELECT u.id, u.first_name, u.last_name, u.email,
            (SELECT COUNT(*)
               FROM decks d
              WHERE d.user_id = u.id
                AND d.deleted_at IS NULL) AS deck_count,
            (SELECT COUNT(*)
               FROM flashcards fc
               JOIN decks d ON d.id = fc.deck_id
              WHERE d.user_id = u.id
                AND d.deleted_at IS NULL
                AND fc.deleted_at IS NULL) AS card_count
       FROM users u
      WHERE u.deleted_at IS NULL
        AND (
            u.first_name LIKE ?
            OR u.last_name LIKE ?
            OR u.email LIKE ?
        )
      ORDER BY u.first_name ASC
      LIMIT 8",
    'sss',
    [$like, $like, $like]
);

$content = [];

foreach ($contentRows as $r) {
    $name = trim($r['first_name'] . ' ' . $r['last_name']);
    $name = $name !== '' ? $name : $r['email'];

    $content[] = [
        'title' => $name,
        'detail' => number_format((int) $r['deck_count'])
            . ' active decks · '
            . number_format((int) $r['card_count'])
            . ' active flashcards',
        'url' => 'views/content.php?q=' . $term,
        'icon' => 'library_books',
    ];
}

/*
 * 3. ACTIVITY
 * Search by learner name/email or study session ID.
 */
$activityRows = globalSearchRows(
    $conn,
    "SELECT rs.id, rs.started_at, rs.ended_at,
            rs.total_reviewed, u.first_name, u.last_name, u.email
       FROM review_sessions rs
       JOIN users u ON u.id = rs.user_id
      WHERE u.first_name LIKE ?
         OR u.last_name LIKE ?
         OR u.email LIKE ?
         OR CAST(rs.id AS CHAR) LIKE ?
      ORDER BY rs.started_at DESC, rs.id DESC
      LIMIT 8",
    'ssss',
    [$like, $like, $like, $like]
);

$activity = [];

foreach ($activityRows as $r) {
    $name = trim($r['first_name'] . ' ' . $r['last_name']);
    $name = $name !== '' ? $name : $r['email'];

    $state = $r['ended_at'] === null
        ? 'In progress'
        : 'Completed';

    $activity[] = [
        'title' => $name,
        'detail' => 'Session #' . (int) $r['id']
            . ' · ' . $state
            . ' · ' . (int) $r['total_reviewed'] . ' reviewed'
            . ' · ' . (string) $r['started_at'],
        'url' => 'views/activity.php?q=' . $term,
        'icon' => 'monitoring',
    ];
}

/*
 * 4. AUDIT LOG
 * Search target, administrator, action, or IP address.
 */
$auditRows = globalSearchRows(
    $conn,
    "SELECT a.id, a.action, a.target_name, a.target_email,
            a.ip_address, a.created_at,
            adm.first_name AS admin_first_name,
            adm.last_name AS admin_last_name,
            adm.email AS admin_email
       FROM admin_audit_log a
       LEFT JOIN users adm ON adm.id = a.admin_id
      WHERE a.target_name LIKE ?
         OR a.target_email LIKE ?
         OR a.ip_address LIKE ?
         OR a.action LIKE ?
         OR adm.first_name LIKE ?
         OR adm.last_name LIKE ?
         OR adm.email LIKE ?
      ORDER BY a.created_at DESC, a.id DESC
      LIMIT 8",
    'sssssss',
    [$like, $like, $like, $like, $like, $like, $like]
);

$audit = [];

foreach ($auditRows as $r) {
    $target = trim((string) ($r['target_name'] ?? ''));
    if ($target === '') {
        $target = (string) ($r['target_email'] ?? '');
    }
    if ($target === '') {
        $target = 'Audit event #' . (int) $r['id'];
    }

    $adminName = trim(
        (string) ($r['admin_first_name'] ?? '') . ' ' .
        (string) ($r['admin_last_name'] ?? '')
    );

    $details = (string) $r['action'] . ' · ' . $target;

    if ($adminName !== '') {
        $details .= ' · By ' . $adminName;
    }

    if (!empty($r['ip_address'])) {
        $details .= ' · IP ' . $r['ip_address'];
    }

    $audit[] = [
        'title' => $target,
        'detail' => $details . ' · ' . (string) $r['created_at'],
        'url' => 'views/audit.php?q=' . $term,
        'icon' => 'history',
    ];
}

echo json_encode([
    'users' => $users,
    'content' => $content,
    'activity' => $activity,
    'audit' => $audit,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
