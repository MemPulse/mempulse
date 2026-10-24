<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/DashboardService.php';
require_once __DIR__ . '/../api/ReportsService.php';

$userId = (int) $_SESSION['user_id'];

$filters     = ReportsService::parseFilters($conn, $userId, $_GET);
$deckOptions = ReportsService::getDeckOptions($conn, $userId, $filters['includeArchived']);
$profile     = DashboardService::getUserProfile($conn, $userId);
$overview    = ReportsService::getOverview($conn, $userId, $filters);
$decksReport = ReportsService::getDecksReport($conn, $userId, $filters);
$drilldown   = ReportsService::getDeckDrilldown($conn, $userId, $filters);
$cardsReport = ReportsService::getCardsReport($conn, $userId, $filters);
$sessionsReport = ReportsService::getSessionsReport($conn, $userId, $filters);

$pageTitle   = "Reports";
$currentPage = "reports";

?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">
    <link rel="stylesheet" href="../assets/css/reports.css?v=<?= filemtime(__DIR__ . '/../assets/css/reports.css') ?>">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 transition-all duration-300 ease-in-out">
        
    <?php include __DIR__ . '/reports-view.php'; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.js" defer></script>
    <script src="../assets/js/reports.js?v=<?= filemtime(__DIR__ . '/../assets/js/reports.js') ?>" defer></script>
    <script src="../assets/js/reports-overview.js?v=<?= filemtime(__DIR__ . '/../assets/js/reports-overview.js') ?>" defer></script>
    <script src="../assets/js/reports-decks.js?v=<?= filemtime(__DIR__ . '/../assets/js/reports-decks.js') ?>" defer></script>
    <script src="../assets/js/reports-cards.js?v=<?= filemtime(__DIR__ . '/../assets/js/reports-cards.js') ?>" defer></script>
    <script src="../assets/js/reports-sessions.js?v=<?= filemtime(__DIR__ . '/../assets/js/reports-sessions.js') ?>" defer></script>
</body>
</html>