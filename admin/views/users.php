<?php
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../api/AdminOverviewService.php';
require_once __DIR__ . '/../api/AdminUsersService.php';
require_once __DIR__ . '/../api/AdminUserActionsService.php';

$filters = AdminUsersService::parseFilters($_GET);

// Handle CSV Export of the current filtered view
if (($_GET['export'] ?? '') === 'csv') {
    $exportList = AdminUsersService::getList($conn, $filters, true);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mempulse-users-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'First Name', 'Last Name', 'Email', 'Role', 'Status', 'Decks', 'Flashcards', 'Sessions', 'Cards Reviewed', 'Accuracy (%)', 'Last Active', 'Joined Date']);
    foreach ($exportList['rows'] as $r) {
        fputcsv($out, [
            $r['id'], $r['firstName'], $r['lastName'], $r['email'],
            $r['role'], $r['status'], $r['decks'], $r['cards'],
            $r['sessions'], $r['reviews'], $r['accuracy'], $r['lastActive'], $r['joinedIso'],
        ]);
    }
    fclose($out);
    exit;
}

$counts = AdminUsersService::getStatusCounts($conn);
$list   = AdminUsersService::getList($conn, $filters);

$pageTitle   = 'Users';
$currentPage = 'users';

$byId = [];
foreach ($list['rows'] as $row) {
    $isSelf = $row['id'] === $adminUser['id'];
    $byId[$row['id']] = $row + [
        'isSelf'  => $isSelf,
        'actions' => $isSelf ? [] : AdminUserActionsService::allowedActions($row['role'], $row['rawStatus'], $row['isDeleted']),
    ];
}

$jsPayload = [
    'users'    => (object) $byId,
    'order'    => array_column($list['rows'], 'id'),
    'csrf'     => adminCsrfToken(),
    'endpoint' => ADMIN_URL . 'api/user-actions.php',
];
$jsVersion = @filemtime(__DIR__ . '/../assets/js/admin-users.js') ?: time();
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <script>
        window.MemPulseAdminUsers = <?= json_encode($jsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/users-view.php'; ?>
    </main>

    <script src="<?= ADMIN_URL ?>assets/js/admin-users.js?v=<?= $jsVersion ?>" defer></script>
</body>
</html>