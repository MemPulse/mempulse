<?php
// 1. Initialize user session tracking
session_start();

// 2. Route Protection: Redirect to login view if user credentials are uninitialized
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

// 3. Connect to database module and load business service
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/DashboardService.php';

$userId = (int) $_SESSION['user_id'];

// --- DATA ORCHESTRATION ---
$profileData    = DashboardService::getUserProfile($conn, $userId);
$metricsData    = DashboardService::getMetrics($conn, $userId);
$streakData     = DashboardService::getStreakAndHeatmapData($conn, $userId);
$recentSessions = DashboardService::getRecentSessions($conn, $userId, 4);

// Extracted variables for template includes
$displayName    = $profileData['displayName'];
$current_streak  = $streakData['currentStreak'];
$pageTitle      = "Home Dashboard";
$currentPage    = 'home';

/**
 * Helper function for Heatmap grid CSS class matching review intensity.
 */
if (!function_exists('heatmap_level_class')) {
    function heatmap_level_class(int $reviews): string {
        if ($reviews <= 0) return 'bg-surface-container-high';
        if ($reviews <= 14) return 'bg-primary-fixed';
        if ($reviews <= 39) return 'bg-primary-fixed-dim';
        if ($reviews <= 79) return 'bg-primary-container';
        return 'bg-primary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<!-- BODY: Main App Background & Styling Root -->
<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN: Viewport Shell Offset for Mobile/Desktop Navigation Bar -->
    <main class="pt-16 pl-0 md:pl-56 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/../includes/header-data.php'; ?>    
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <?php include __DIR__ . '/dashboard.php'; ?>                
    </main>

    <!-- 1. DATA INJECTION: Server-side PHP metrics passed safely to client-side JS -->
    <script>
        window.MemPulseHomeData = {
            stats: {
                cardsDue: <?php echo (int) $metricsData['cardsDue']; ?>,
                masteredCards: <?php echo (int) $metricsData['masteredCards']; ?>,
                toughCards: <?php echo (int) $metricsData['toughCards']; ?>,
                currentStreak: <?php echo (int) $streakData['currentStreak']; ?>,
                bestStreak: <?php echo (int) $streakData['bestStreak']; ?>,
                streakAccuracy: <?php echo (float) $streakData['streakAccuracy']; ?>
            }
        };

        /**
         * Global Dashboard UI Manager
         * Synchronizes dynamic metrics across header, sidebar, and metric panels.
         */
        const MemPulseDashboard = {
            updateAll(payload) {
                if (!payload) return;
                this.syncHeader(payload.user);
                this.syncSidebarProfile(payload.user);
                this.syncMetrics(payload.stats);
            },

            syncMetrics(stats) {
                if (!stats) return;
                this.setElText('stat-cards-due', stats.cardsDue);
                this.setElText('stat-mastered', stats.masteredCards);
                this.setElText('stat-tough', stats.toughCards);
                this.setElText('stat-current-streak', stats.currentStreak);
                this.setElText('stat-best-streak', stats.bestStreak);
                this.setElText('stat-streak-accuracy', stats.streakAccuracy);
            },

            setElText(id, value) {
                const el = document.getElementById(id);
                if (el) el.textContent = value ?? '--';
            }
        };

        document.addEventListener("DOMContentLoaded", () => {
            MemPulseDashboard.updateAll(window.MemPulseHomeData);

            const heatmapScroller = document.getElementById("heatmap-scroll");
            if (heatmapScroller) {
                requestAnimationFrame(() => {
                    heatmapScroller.scrollLeft = heatmapScroller.scrollWidth - heatmapScroller.clientWidth;
                });
            }
        });
    </script>

    <!-- 2. CLIENT-SIDE ASSET IMPORTS: Separated standalone JavaScript modules -->
    <script src="../assets/js/hero-carousel.js" defer></script>
</body>
</html>