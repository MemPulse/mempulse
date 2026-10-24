<?php
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../api/AdminOverviewService.php';
require_once __DIR__ . '/../api/AdminAuditService.php';

$filters = AdminAuditService::parseFilters($_GET);

// Handle CSV Export of the filtered audit trail
if (($_GET['export'] ?? '') === 'csv') {
    $exportList = AdminAuditService::getList($conn, $filters, true);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mempulse-audit-log-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Log ID', 'Timestamp', 'Action', 'Admin Name', 'Admin Email', 'Target User ID', 'Target Name', 'Target Email', 'Previous State', 'New State', 'IP Address']);
    foreach ($exportList['rows'] as $r) {
        fputcsv($out, [
            $r['id'],
            $r['timestampIso'],
            $r['actionLabel'],
            $r['adminName'],
            $r['adminEmail'],
            $r['targetUserId'] ?? '',
            $r['targetName'],
            $r['targetEmail'],
            $r['prevStatus'],
            $r['newStatus'],
            $r['ip'],
        ]);
    }
    fclose($out);
    exit;
}

$summary = AdminAuditService::getSummary($conn);
$actors  = AdminAuditService::getActors($conn);
$list    = AdminAuditService::getList($conn, $filters);

$pageTitle   = 'Audit Log';
$currentPage = 'audit';
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/audit-view.php'; ?>
    </main>

</body>
</html>