<?php
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../api/AdminOverviewService.php'; // timeAgo()
require_once __DIR__ . '/../api/AdminUsersService.php';

$filters = AdminUsersService::parseFilters($_GET);
$counts  = AdminUsersService::getStatusCounts($conn);
$list    = AdminUsersService::getList($conn, $filters);

$pageTitle   = 'Users';
$currentPage = 'users';

$byId = [];
foreach ($list['rows'] as $row) {
    $byId[$row['id']] = $row + ['isSelf' => $row['id'] === $adminUser['id']];
}
$jsPayload = ['users' => (object) $byId];
$jsVersion = @filemtime(__DIR__ . '/../assets/js/admin-users.js') ?: time();
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/users-view.php'; ?>
    </main>

    <script>
        window.MemPulseAdminUsers = <?= json_encode($jsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= ADMIN_URL ?>assets/js/admin-users.js?v=<?= $jsVersion ?>" defer></script>
</body>
</html>