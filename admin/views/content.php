<?php
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../api/AdminContentService.php';

$filters = AdminContentService::parseFilters($_GET);

if (($_GET['export'] ?? '') === 'csv') {
    $exportList = AdminContentService::getList($conn, $filters, true);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mempulse-content-inventory-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['User ID', 'Learner Name', 'Email', 'Active Decks', 'Archived Decks', 'Active Cards', 'Due Now', 'Mature Cards (21d+)', 'Avg Interval (Days)', 'Total Lapses']);
    foreach ($exportList['rows'] as $r) {
        fputcsv($out, [
            $r['id'], $r['name'], $r['email'],
            $r['activeDecks'], $r['archivedDecks'], $r['activeCards'],
            $r['dueCards'], $r['matureCards'], $r['avgInterval'], $r['totalLapses'],
        ]);
    }
    fclose($out);
    exit;
}

$summary = AdminContentService::getSummary($conn);
$list    = AdminContentService::getList($conn, $filters);

$pageTitle   = 'Content';
$currentPage = 'content';
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/content-view.php'; ?>
    </main>

</body>
</html>