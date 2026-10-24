<?php
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../api/AdminOverviewService.php';
require_once __DIR__ . '/../api/AdminActivityService.php';

$filters = AdminActivityService::parseFilters($_GET);

if (($_GET['export'] ?? '') === 'csv') {
    $exportList = AdminActivityService::getList($conn, $filters, true);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mempulse-study-activity-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Session ID', 'Started At', 'Learner Name', 'Email', 'Status', 'Cards Reviewed', 'Remembered', 'Forgot', 'Accuracy (%)', 'Duration']);
    foreach ($exportList['rows'] as $r) {
        fputcsv($out, [
            $r['id'], $r['startedIso'], $r['name'], $r['email'],
            $r['completed'] ? 'Completed' : 'In Progress',
            $r['reviewed'], $r['remembered'], $r['forgot'],
            $r['accuracy'], $r['duration'],
        ]);
    }
    fclose($out);
    exit;
}

$summary = AdminActivityService::getSummary($conn);
$trend   = AdminActivityService::getTrend($conn, 90);
$list    = AdminActivityService::getList($conn, $filters);

$pageTitle   = 'Activity';
$currentPage = 'activity';

$jsPayload = ['trend' => $trend, 'defaultRange' => 30];
$jsVersion = @filemtime(__DIR__ . '/../assets/js/admin-activity.js') ?: time();
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/activity-view.php'; ?>
    </main>

    <script>
        window.MemPulseAdminActivity = <?= json_encode($jsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script src="<?= ADMIN_URL ?>assets/js/admin-activity.js?v=<?= $jsVersion ?>" defer></script>
</body>
</html>