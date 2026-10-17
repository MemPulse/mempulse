<?php
// 1. Guard first: verifies session + admin role against the database, loads db.php.
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../api/AdminOverviewService.php';

// 2. Fetch all data up front; the markup contains no queries.
$metrics        = AdminOverviewService::getMetrics($conn);
$trend          = AdminOverviewService::getTrend($conn, 90); // sliced client-side for 7/30/90
$pendingUsers   = AdminOverviewService::getPendingUsers($conn, 5);
$recentSignups  = AdminOverviewService::getRecentSignups($conn, 6);
$recentSessions = AdminOverviewService::getRecentSessions($conn, 6);

// 3. Page settings used by head.php and sidebar.php.
$pageTitle   = 'Overview';
$currentPage = 'overview';

$jsPayload = ['trend' => $trend, 'defaultRange' => 30];
$jsVersion = @filemtime(__DIR__ . '/../assets/js/admin-overview.js') ?: time();
// The hero carousel script is shared with the student app (it auto-starts on #hero-carousel).
$carouselVersion = @filemtime(__DIR__ . '/../../app/assets/js/hero-carousel.js') ?: time();
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/overview-view.php'; ?>
    </main>

    <script>
        window.MemPulseAdminOverview = <?= json_encode($jsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="<?= APP_URL ?>assets/js/hero-carousel.js?v=<?= $carouselVersion ?>" defer></script>
    <!-- Pin this to the same Chart.js version the reports page uses. -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script src="<?= ADMIN_URL ?>assets/js/admin-overview.js?v=<?= $jsVersion ?>" defer></script>
</body>
</html>