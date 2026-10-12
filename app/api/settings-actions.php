
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (
    ($_SESSION['logged_in'] ?? false) !== true ||
    !isset($_SESSION['user_id'])
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in again.']);
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getSettings(mysqli $conn, int $userId): array {
    $sql = "SELECT remember_multiplier, forgot_multiplier,
                   initial_interval_days, maximum_interval_days
            FROM user_settings WHERE user_id = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result(
        $stmt, $remember, $forgot, $initial, $maximum
    );

    if (!mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);

        return [
            'remember_multiplier' => 2.00,
            'forgot_multiplier' => 0.50,
            'initial_interval_days' => 1,
            'maximum_interval_days' => 365
        ];
    }

    mysqli_stmt_close($stmt);

    return [
        'remember_multiplier' => (float) $remember,
        'forgot_multiplier' => (float) $forgot,
        'initial_interval_days' => (int) $initial,
        'maximum_interval_days' => (int) $maximum
    ];
}

if ($method === 'GET') {
    respond(200, [
        'success' => true,
        'settings' => getSettings($conn, $userId)
    ]);
}

if ($method !== 'POST') {
    header('Allow: GET, POST');
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

if (
    !isset($_SESSION['settings_csrf']) ||
    !hash_equals(
        $_SESSION['settings_csrf'],
        $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''
    )
) {
    respond(403, ['success' => false, 'message' => 'Security token invalid. Refresh the page and try again.']);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond(400, ['success' => false, 'message' => 'Invalid request data.']);
}

$required = [
    'remember_multiplier',
    'forgot_multiplier',
    'initial_interval_days',
    'maximum_interval_days'
];

foreach ($required as $key) {
    if (!isset($data[$key]) || !is_numeric($data[$key])) {
        respond(422, ['success' => false, 'message' => 'Enter valid values for all settings.']);
    }
}

$remember = (float) $data['remember_multiplier'];
$forgot = (float) $data['forgot_multiplier'];
$initial = filter_var($data['initial_interval_days'], FILTER_VALIDATE_INT);
$maximum = filter_var($data['maximum_interval_days'], FILTER_VALIDATE_INT);

if (
    !is_finite($remember) || !is_finite($forgot) ||
    $remember < 0.01 || $remember > 10 ||
    $forgot < 0.01 || $forgot > 10 ||
    $initial === false || $maximum === false ||
    $initial < 1 || $maximum < $initial || $maximum > 36500
) {
    respond(422, [
        'success' => false,
        'message' => 'Check the multipliers and interval limits.'
    ]);
}

$sql = "INSERT INTO user_settings
            (user_id, remember_multiplier, forgot_multiplier,
             initial_interval_days, maximum_interval_days)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            remember_multiplier = VALUES(remember_multiplier),
            forgot_multiplier = VALUES(forgot_multiplier),
            initial_interval_days = VALUES(initial_interval_days),
            maximum_interval_days = VALUES(maximum_interval_days)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
    $stmt, 'iddii', $userId, $remember, $forgot, $initial, $maximum
);

if (!mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    respond(500, ['success' => false, 'message' => 'Unable to save settings.']);
}

mysqli_stmt_close($stmt);

respond(200, [
    'success' => true,
    'message' => 'Learning settings saved.',
    'settings' => getSettings($conn, $userId)
]);
