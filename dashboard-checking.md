index.php
```
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
    <main class="pt-16 pl-0 md:pl-72 transition-all duration-300 ease-in-out">
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
```

dashboard.php
```
<!-- ========================================================================= -->
<!-- MAIN DASHBOARD CONTENT CONTAINER CONTAINER DIV                            -->
<!-- Outer responsive padding shell wrapping top metrics, carousel, and history -->
<!-- ========================================================================= -->
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <!-- DIV: Vertical Layout Flex Shell -->
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <!-- DIV: TOP SECTION - Core Analytics Cards, Action Carousel & Streak Bar -->
        <div class="flex flex-col gap-4 sm:gap-6 w-full">

            <!-- DIV: ROW 1 - Grid Split: 2x2 Metric Grid (Left) vs Hero Carousel (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">
                
                <!-- DIV: LEFT COLUMN - 2x2 Core Metrics Card Grid Container -->
                <div class="lg:col-span-6 flex flex-col justify-between w-full min-w-0">
                    <!-- DIV: 2-Column Responsive Metric Grid Shell -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 w-full h-full">
                        
                        <!-- DIV: CARD 1 - Scheduled Cards Due Metric Container -->
                        <div class="bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-primary/50 hover:shadow-md transition-all">
                            <!-- DIV: Ambient Background Blur Decorative Shape -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-primary/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-primary/20 transition-all"></div>
                            
                            <!-- DIV: Card Header Row Shell (Title & Tooltip) -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <!-- DIV: Label & Information Tooltip Shell -->
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Scheduled Cards Category Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-primary">Scheduled</span>
                                    <!-- DIV: Informational Tooltip Wrapper -->
                                    <div class="relative group/tooltip flex items-center">
                                        <!-- SPAN: Info Graphic Icon -->
                                        <span class="material-symbols-outlined text-[15px] text-primary/60 cursor-help hover:text-primary transition-colors">info</span>
                                        <!-- DIV: Floating Tooltip Hover Content Container -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Total cards scheduled for review today.
                                            <!-- DIV: Tooltip Arrow Point Graphic -->
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- DIV: Metric Card Alarm Icon Container Badge -->
                                <div class="w-8 h-8 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Alarm Clock Icon Graphic -->
                                    <span class="material-symbols-outlined text-[18px]">alarm</span>
                                </div>
                            </div>
                            <!-- DIV: Metric Numerical Display Wrapper -->
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Cards Due Counter Output -->
                                <span id="stat-cards-due" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['cardsDue']; ?>
                                </span>
                                <!-- SPAN: Metric Name Subtitle -->
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Due Today</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 2 - Cards Reviewed Today Metric Container -->
                        <div class="bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-teal-500/50 hover:shadow-md transition-all">
                            <!-- DIV: Ambient Background Glow Graphic -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-teal-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-teal-500/20 transition-all"></div>
                            
                            <!-- DIV: Header Metric Shell -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <!-- DIV: Category Label Group -->
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Category Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-teal-700">Reviewed</span>
                                    <!-- DIV: Tooltip Hover Trigger -->
                                    <div class="relative group/tooltip flex items-center">
                                        <!-- SPAN: Info Graphic Icon -->
                                        <span class="material-symbols-outlined text-[15px] text-teal-700/60 cursor-help hover:text-teal-700 transition-colors">info</span>
                                        <!-- DIV: Floating Tooltip Box -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Total cards reviewed in completed study sessions today.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- DIV: Icon Badge Shell -->
                                <div class="w-8 h-8 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-600 flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Flashcards Graphic Icon -->
                                    <span class="material-symbols-outlined text-[18px]">style</span>
                                </div>
                            </div>
                            <!-- DIV: Numerical Value Group -->
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Cards Reviewed Counter Output -->
                                <span id="stat-reviewed-today" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['cardsReviewedToday']; ?>
                                </span>
                                <!-- SPAN: Metric Name Subtitle -->
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Reviewed Today</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 3 - Retention Rate Percentage Metric Container -->
                        <div class="bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-error/50 hover:shadow-md transition-all">
                            <!-- DIV: Decorative Background Overlay -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-rose-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-rose-500/20 transition-all"></div>
                            
                            <!-- DIV: Header Information Bar Shell -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Retention Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-error">Retention Rate</span>
                                    <div class="relative group/tooltip flex items-center">
                                        <span class="material-symbols-outlined text-[15px] text-error/60 cursor-help hover:text-error transition-colors">info</span>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Percentage of completed reviews where the learner successfully remembered the card.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-8 h-8 rounded-xl bg-red-50 border border-red-200/80 flex items-center justify-center text-error flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Accuracy Target Icon -->
                                    <span class="material-symbols-outlined text-[18px]">track_changes</span>
                                </div>
                            </div>
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Retention Output Percentage -->
                                <span id="stat-retention" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (float) $metricsData['retentionRate']; ?>%
                                </span>
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Retention Rate</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 4 - Mastered Cards Metric Container -->
                        <div class="bg-gradient-to-br from-indigo-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-indigo-500/50 hover:shadow-md transition-all">
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-indigo-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-indigo-500/20 transition-all"></div>
                            
                            <div class="flex items-center justify-between w-full relative z-10">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-indigo-700">Mastered</span>
                                    <div class="relative group/tooltip flex items-center">
                                        <span class="material-symbols-outlined text-[15px] text-indigo-700/60 cursor-help hover:text-indigo-700 transition-colors">info</span>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Cards that have reached a review interval of at least 21 days.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-indigo-600 flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Verified Badge Icon -->
                                    <span class="material-symbols-outlined text-[18px]">verified</span>
                                </div>
                            </div>
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Mastered Cards Count Output -->
                                <span id="stat-mastered" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['masteredCards']; ?>
                                </span>
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Mastered</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- DIV: RIGHT COLUMN - Dynamic Action Hero Carousel Container -->
                <div class="lg:col-span-6 flex w-full min-w-0 h-full">
                    <!-- SECTION: Interactive Carousel Wrapper Shell -->
                    <section class="relative w-full h-full rounded-2xl overflow-hidden shadow-xs border border-outline-variant/30 min-h-[280px] bg-gradient-to-br from-inverse-surface via-slate-900 to-inverse-surface text-inverse-on-surface flex flex-col justify-between" id="hero-carousel" aria-label="Learning Quick Actions Carousel">
                        <!-- DIV: Background Ambient Glow Effects -->
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>

                        <!-- DIV: Carousel Slide Frame Wrapper -->
                        <div class="relative w-full h-full min-h-[280px]">
                            
                            <!-- DIV: SLIDE 0 - Memory Science -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-100 z-10" data-slide-index="0" aria-hidden="false">
                                <!-- DIV: Slide Header & Description Block -->
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-primary/20 border border-primary/30 backdrop-blur-md text-cyan-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">psychology</span>
                                        <span>Memory Science</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Understand How Memory Works
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Explore the science behind spaced repetition, forgetting curves, and effective long-term learning.
                                    </p>
                                </div>
                                <!-- DIV: Slide Action Button & Wayfinder Dots Bar -->
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="science.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Learn More</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <!-- DIV: Wayfinder Pagination Navigation Dots Shell -->
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 1 - Decks -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="1" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-teal-500/20 border border-teal-500/30 backdrop-blur-md text-teal-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">style</span>
                                        <span>Decks</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        View and Organize Your Decks
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Manage your study collections, browse your cards, and keep your learning materials organized.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="decks.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>View Decks</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 2 - Flashcard Creation -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="2" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-indigo-500/20 border border-indigo-500/30 backdrop-blur-md text-indigo-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">edit_note</span>
                                        <span>Flashcard Creation</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Create Your Flashcards
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Build focused study cards and turn complex subjects into concise prompts for active recall.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="decks.php?add_card=1" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Create Flashcards</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 3 - Review & History -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="3" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-amber-500/20 border border-amber-500/30 backdrop-blur-md text-amber-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">pace</span>
                                        <span>Review & History</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Review Cards and Track Your History
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Start a review session, reinforce what you know, and view your completed review history.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="review.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Review Now</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 4 - Reports & Statistics -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="4" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-purple-500/20 border border-purple-500/30 backdrop-blur-md text-purple-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">monitoring</span>
                                        <span>Reports & Statistics</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        See Your Learning Progress
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Explore detailed statistics and reports to understand your review activity and learning progress.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="reports.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>View Reports</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>
                </div>

            </div>

                        <?php
            // ---- "Due Now" call-to-action state ----
            $dueTotal     = (int) ($metricsData['cardsDue'] ?? 0);
            $dueDecks     = $dueByDeck ?? [];
            $nextDueSecs  = $nextDueSecs ?? null;
            $curStreak    = (int) ($streakData['currentStreak'] ?? 0);
            $streakAtRisk = $dueTotal > 0 && $curStreak > 0 && (int) ($metricsData['cardsReviewedToday'] ?? 0) === 0;
            $reviewHref   = count($dueDecks) === 1 ? 'review.php?deck_id=' . (int) $dueDecks[0]['id'] : 'review.php';

            if ($streakAtRisk) {
                $dueSubtitle = 'Keep your ' . $curStreak . '-day streak alive';
            } elseif (count($dueDecks) === 1) {
                $dueSubtitle = 'in ' . $dueDecks[0]['title'];
            } elseif (count($dueDecks) > 1) {
                $dueSubtitle = 'across ' . count($dueDecks) . ' decks';
            } else {
                $dueSubtitle = '';
            }

            $nextDueLabel = '';
            if ($nextDueSecs !== null && $nextDueSecs > 0) {
                if ($nextDueSecs < 3600)       $nextDueLabel = 'Next cards due in ' . max(1, (int) ceil($nextDueSecs / 60)) . ' min';
                elseif ($nextDueSecs < 86400)  $nextDueLabel = 'Next cards due in ' . (int) floor($nextDueSecs / 3600) . ' h';
                else                           $nextDueLabel = 'Next cards due in ' . (int) floor($nextDueSecs / 86400) . ' d';
            }
            ?>

            <!-- DIV: ROW 2 - Grid Split: Streak Stats (Left, matches heatmap) vs Due Now CTA (Right, matches carousel) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">

                <!-- DIV: LEFT COLUMN - Streak Analytics Metric Bar -->
                <div class="lg:col-span-6 flex w-full min-w-0 order-last lg:order-none">
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-2 sm:p-2.5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-1 sm:gap-3 w-full h-full">
                        <!-- DIV: Metric 1 - Best Historical Streak -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-primary/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-primary-fixed/60 border border-primary-fixed-dim/40 flex items-center justify-center text-primary flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">emoji_events</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Best Streak</span>
                                <span id="stat-best-streak" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-primary transition-colors">
                                    <?php echo (int) $streakData['bestStreak']; ?> days
                                </span>
                            </div>
                        </div>
                        <!-- DIV: Vertical Divider -->
                        <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>

                        <!-- DIV: Metric 2 - Current Active Streak -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-amber-500/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-500 flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">local_fire_department</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Current Streak</span>
                                <span id="stat-current-streak" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-amber-600 transition-colors">
                                    <?php echo (int) $streakData['currentStreak']; ?> days
                                </span>
                            </div>
                        </div>
                        <!-- DIV: Vertical Divider -->
                        <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>

                        <!-- DIV: Metric 3 - Streak Accuracy Percentage -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-teal-500/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-500 flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">track_changes</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Streak Acc.</span>
                                <span id="stat-streak-accuracy" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-teal-600 transition-colors">
                                    <?php echo (float) $streakData['streakAccuracy']; ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DIV: RIGHT COLUMN - Due Now Call-To-Action (same width as carousel / recent sessions) -->
                <div class="lg:col-span-6 flex w-full min-w-0 order-first lg:order-none">

                    <?php if ($dueTotal > 0): ?>
                        <!-- A: Cards due - primary call-to-action -->
                        <a href="<?php echo htmlspecialchars($reviewHref, ENT_QUOTES, 'UTF-8'); ?>"
                           id="stat-due-cta"
                           aria-label="Review <?php echo $dueTotal; ?> due <?php echo $dueTotal === 1 ? 'card' : 'cards'; ?>"
                           class="group/cta relative overflow-hidden flex items-center justify-between gap-4 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-on-primary shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                            <!-- DIV: Ambient Glow -->
                            <div class="absolute -right-6 -top-8 w-28 h-28 bg-white/15 rounded-full blur-2xl pointer-events-none"></div>

                            <!-- DIV: Icon + Text -->
                            <div class="relative z-10 flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-[22px]">alarm</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-primary/80">Due Now</span>
                                    <span class="text-base sm:text-lg font-extrabold leading-tight truncate">
                                        <span id="stat-due-cta-count"><?php echo $dueTotal; ?></span>
                                        <?php echo $dueTotal === 1 ? 'card' : 'cards'; ?> ready to review
                                    </span>
                                    <?php if ($dueSubtitle !== ''): ?>
                                        <span class="text-[11px] sm:text-xs text-on-primary/80 truncate"><?php echo htmlspecialchars($dueSubtitle, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- DIV: Action Pill -->
                            <span class="relative z-10 inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl bg-white text-primary font-label-md text-xs font-bold shadow-xs flex-shrink-0">
                                <span class="hidden sm:inline">Start Review</span>
                                <span class="material-symbols-outlined text-[18px] transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                            </span>
                        </a>

                    <?php elseif ($nextDueSecs !== null): ?>
                        <!-- B: Nothing due, cards are scheduled -->
                        <div id="stat-due-cta" class="flex items-center gap-3 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                <span class="material-symbols-outlined text-[22px]">task_alt</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                                <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface">All caught up</span>
                                <?php if ($nextDueLabel !== ''): ?>
                                    <span class="text-[11px] sm:text-xs text-on-surface-variant"><?php echo htmlspecialchars($nextDueLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- C: No cards at all - point to creation -->
                        <a href="decks.php?add_card=1" id="stat-due-cta"
                           class="group/cta flex items-center justify-between gap-4 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs hover:border-primary/50 hover:shadow-md transition-all">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                                    <span class="material-symbols-outlined text-[22px]">add</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">No Due Today</span>
                                    <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface truncate">Create new cards</span>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-[20px] text-primary flex-shrink-0 transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                        </a>
                    <?php endif; ?>

                </div>
            </div>

        </div>

        <!-- DIV: BOTTOM SECTION - Streak Heatmap Grid & Recent Activity History -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 w-full items-stretch mt-2">
            
            <!-- DIV: Streak Heatmap Card Container Shell -->
            <div class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between min-h-[320px] min-w-0 overflow-visible">
                
                <!-- DIV: Heatmap Title & Intensity Legend Wrapper -->
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">calendar_month</span>
                            <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Streak Heatmap</h3>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">
                            Last 365 days • <span class="font-semibold text-on-surface"><?php echo (int) $streakData['studyDays']; ?> / 365</span> study days
                        </p>
                    </div>

                    <!-- DIV: Activity Level Color Legend -->
                    <div class="flex items-center gap-1.5 font-label-sm text-[10px] text-on-surface-variant">
                        <span>Less</span>
                        <span class="w-3 h-3 rounded bg-surface-container-high"></span>
                        <span class="w-3 h-3 rounded bg-primary-fixed"></span>
                        <span class="w-3 h-3 rounded bg-primary-fixed-dim"></span>
                        <span class="w-3 h-3 rounded bg-primary-container"></span>
                        <span class="w-3 h-3 rounded bg-primary"></span>
                        <span>More</span>
                    </div>
                </div>

                <!-- DIV: Heatmap CSS Grid Scroll Area Wrapper -->
                <div id="heatmap-scroll" class="flex-1 flex items-center py-4 w-full overflow-x-auto">
                    <div class="w-max grid justify-start" style="--cell: 16px; gap: 3px; grid-template-columns: auto repeat(53, var(--cell)); grid-template-rows: auto repeat(7, var(--cell));">

                        <?php foreach ([3 => 'Mon', 5 => 'Wed', 7 => 'Fri'] as $row => $name): ?>
                            <!-- SPAN: Day Name Axis Labels -->
                            <span class="flex items-center pr-2 text-[10px] leading-none whitespace-nowrap text-on-surface-variant select-none" style="grid-column: 1; grid-row: <?php echo $row; ?>;">
                                <?php echo $name; ?>
                            </span>
                        <?php endforeach; ?>

                        <?php
                        $weekStart = clone $streakData['calendarStart'];
                        for ($w = 0; $w < 53; $w++) {
                            for ($d = 0; $d < 7; $d++) {
                                $date = clone $weekStart;
                                $date->modify('+' . $d . ' days');

                                if ($date < $streakData['heatmapStart'] || $date > $streakData['heatmapEnd']) continue;

                                if ($date->format('j') === '1' && $w <= 51) {
                                    echo '<div class="text-[10px] leading-none font-medium whitespace-nowrap text-on-surface-variant" style="grid-row: 1; grid-column: ' . ($w + 2) . ' / span 3;">' . htmlspecialchars($date->format('M'), ENT_QUOTES) . '</div>';
                                }

                                $key = $date->format('Y-m-d');
                                $reviews = $streakData['heatmapData'][$key] ?? 0;
                                $tooltip = $date->format('M j, Y') . ' • ' . ($reviews === 0 ? 'No reviews' : ($reviews === 1 ? '1 review' : $reviews . ' reviews'));
                                echo '<div class="heatmap-cell rounded-[4px] transition-transform hover:scale-110 ' . heatmap_level_class($reviews) . '" style="grid-row: ' . ($d + 2) . '; grid-column: ' . ($w + 2) . ';" data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"></div>';
                            }
                            $weekStart->modify('+7 days');
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- DIV: Recent Review Sessions Feed Card Shell -->
            <div class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between min-h-[290px] min-w-0">
                
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">history</span>
                            <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent Review Sessions</h3>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Your latest spaced repetition drills</p>
                    </div>
                    <a href="sessions.php" class="text-xs text-primary hover:underline font-semibold flex items-center gap-1 transition-all">
                        <span>View All</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>

                <!-- DIV: Session Items List Wrapper Container -->
                <div class="flex flex-col gap-2.5 flex-1 justify-center">
                    <?php if (!empty($recentSessions)): ?>
                        <?php foreach ($recentSessions as $session): ?>
                            <!-- DIV: Individual Session Card Shell -->
                            <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low border border-outline-variant/20 hover:border-primary/30 hover:bg-surface-container transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-[18px]">style</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-on-surface truncate">
                                            <?php echo htmlspecialchars($session['deckTitle'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <div class="flex items-center gap-2 text-[11px] text-on-surface-variant mt-0.5">
                                            <span><?php echo (int) $session['cardsReviewed']; ?> cards reviewed</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end flex-shrink-0 ml-2">
                                    <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                                        <?php echo (int) $session['accuracy']; ?>%
                                    </span>
                                    <span class="text-[10px] text-on-surface-variant/70 mt-1">
                                        <?php echo htmlspecialchars($session['formattedTime'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- DIV: Empty State Fallback Shell -->
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant h-full">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2">history_edu</span>
                            <span class="text-sm font-medium">No recent review sessions found.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">Complete a study session to see your history here.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>
```

app.css
```
@layer base {

    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        overflow-y: auto !important;
        overscroll-behavior-y: auto;
    }

    body {
        overscroll-behavior: none;
    }

    main {
        min-height: 100vh;
        overflow-y: auto !important;
    }

    main>:first-child {
        margin-top: 0 !important;
    }

    main>:last-child {
        margin-bottom: 0 !important;
    }
}

/* Hide normal page scrollbars */
html,
body,
main {
    scrollbar-width: none;
}

html::-webkit-scrollbar,
body::-webkit-scrollbar,
main::-webkit-scrollbar {
    display: none;
}


/* Sidebar Toggle */
/* --- Base Collapsed Sidebar Rules (Desktop Only) --- */
@media (min-width: 768px) {
    #sidebar.collapsed {
        width: 80px !important;
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    /* Hide standard layout text elements */
    #sidebar.collapsed .sidebar-text {
        opacity: 0 !important;
        display: none !important;
    }

    /* Hide the logo name text string when collapsed */
    #sidebar.collapsed .logo-text {
        opacity: 0 !important;
        width: 0 !important;
        display: none !important;
    }

    /* Change header layout structure from a row grid to vertical stack */
    #sidebar.collapsed #sidebar-header {
        flex-direction: column !important;
        gap: 16px !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        align-items: center !important;
    }

    #sidebar.collapsed #sidebar-header>div {
        order: 1 !important;
    }

    #sidebar.collapsed #sidebar-toggle {
        order: 2 !important;
    }

    /* ─── NEW: FORCE MAIN CONTENT AND FIXED HEADER TO SHIFT LEFT ─── */
    main.collapsed {
        padding-left: 80px !important;
        /* Shakes off the pl-72 padding */
    }

    main.collapsed header {
        left: 80px !important;
        /* Shakes off the left-72 fixed offset */
    }
}

/* --- Streak Heatmap Tooltip Styles (Pure CSS Absolute Positioning) --- */
#heatmap-scroll {
    position: relative;
    overflow-x: auto;
}

#heatmap-scroll .heatmap-cell {
    position: relative;
    cursor: pointer;
    outline: none !important;
    -webkit-tap-highlight-color: transparent;
}

#heatmap-scroll .heatmap-cell:hover {
    z-index: 100;
}

/* Tooltip box */
#heatmap-scroll .heatmap-cell::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #ffffff;
    font-size: 11px;
    line-height: 1.3;
    padding: 6px 10px;
    border-radius: 8px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s ease;
    z-index: 9999;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Tooltip arrow */
#heatmap-scroll .heatmap-cell::before {
    content: '';
    position: absolute;
    bottom: calc(100% + 2px);
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #1e293b;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s ease;
    z-index: 101;
}

#heatmap-scroll .heatmap-cell:hover::after,
#heatmap-scroll .heatmap-cell:hover::before {
    opacity: 1;
}

/* --- Universal Heatmap Scrollbar Rules (Visible in All Views) --- */
#heatmap-scroll {
    overflow-x: auto !important;
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.35) transparent;
}

#heatmap-scroll::-webkit-scrollbar {
    display: block !important;
    height: 6px;
}

#heatmap-scroll::-webkit-scrollbar-track {
    background: transparent;
}

#heatmap-scroll::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.35);
    border-radius: 999px;
}

#heatmap-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(100, 116, 139, 0.6);
}

/* --- Archived Decks Button Pulse Animation --- */
@keyframes archivePulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(var(--color-primary, 59, 130, 246), 0.7);
    }

    50% {
        transform: scale(1.05);
        box-shadow: 0 0 12px 4px rgba(var(--color-primary, 59, 130, 246), 0.5);
    }

    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(var(--color-primary, 59, 130, 246), 0);
    }
}

.animate-archive-pulse {
    animation: archivePulse 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 2;
    color: currentColor !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* ==========================================================================
   Flashcard Table & Resizable Columns
   ========================================================================== */

/* Resizable Header Cell Handle */
.resizable-col {
    position: relative;
    user-select: none;
}

.resizer {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 6px;
    cursor: col-resize;
    user-select: none;
    touch-action: none;
    z-index: 10;
    transition: background-color 0.15s ease;
}

.resizer:hover,
.resizer.resizing {
    background-color: var(--color-primary, #3b82f6);
}

/* Selected Row Highlighting */
#flashcards-table-body tr.selected-row {
    background-color: rgba(var(--color-primary-rgb, 59, 130, 246), 0.08) !important;
    border-left: 3px solid var(--color-primary, #3b82f6);
}

/* Custom Scrollbars for Table Header & Body Cells */
#flashcard-table-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.35) transparent;
}

#flashcard-table-scroll-container::-webkit-scrollbar {
    height: 6px;
}

#flashcard-table-scroll-container::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.35);
    border-radius: 999px;
}

/* ==========================================================================
   Card Editor Panel Drawer
   ========================================================================== */

#card-editor-panel {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
        opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1),
        margin 0.25s cubic-bezier(0.4, 0, 0.2, 1),
        width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform, opacity;
}

#card-editor-panel.collapsed {
    display: none !important;
}

/* ==========================================================================
   Tabulator Grid - MemPulse Custom Styling
   ========================================================================== */

/* Main Table Container Override */
.tabulator {
    background-color: transparent !important;
    border: none !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    /* 14px */
}

/* Header Styling */
.tabulator .tabulator-header {
    background-color: var(--color-surface-container-low, #f1f5f9) !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.25) !important;
    color: var(--color-on-surface-variant, #64748b) !important;
    font-weight: 700 !important;
    font-size: 0.75rem !important;
    /* 12px */
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}

.tabulator .tabulator-header .tabulator-col {
    background-color: transparent !important;
    border-right: 1px solid rgba(148, 163, 184, 0.15) !important;
    padding: 8px 12px !important;
}

.tabulator .tabulator-header .tabulator-col-content {
    padding: 0 !important;
}

/* Header Resizer Drag Handles */
.tabulator .tabulator-header .tabulator-col .tabulator-col-resize-handle {
    width: 6px !important;
    right: 0 !important;
    opacity: 0;
    transition: opacity 0.15s ease, background-color 0.15s ease;
}

.tabulator .tabulator-header .tabulator-col:hover .tabulator-col-resize-handle,
.tabulator .tabulator-header .tabulator-col .tabulator-col-resize-handle:hover {
    opacity: 1;
    background-color: var(--color-primary, #3b82f6) !important;
}

/* Row Styling (Non-Compact Readable Format) */
.tabulator .tabulator-tableholder .tabulator-table .tabulator-row {
    background-color: var(--color-surface-container-lowest, #ffffff) !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2) !important;
    color: var(--color-on-surface, #0f172a) !important;
    transition: background-color 0.15s ease;
    min-height: 36px !important;
}

/* Alternate Row Hover */
.tabulator .tabulator-tableholder .tabulator-table .tabulator-row:hover {
    background-color: rgba(241, 245, 249, 0.6) !important;
    cursor: pointer;
}

/* Selected Row Highlight */
.tabulator .tabulator-tableholder .tabulator-table .tabulator-row.tabulator-selected {
    background-color: rgba(59, 130, 246, 0.08) !important;
    border-left: 3px solid var(--color-primary, #3b82f6) !important;
}

/* Cell Padding & Alignment */
.tabulator .tabulator-cell {
    padding: 6px 12px !important;
    border-right: 1px solid rgba(148, 163, 184, 0.12) !important;
    display: inline-block !important;
    vertical-align: middle !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    line-height: 24px !important;
}

/* Cell Editing Focus State */
.tabulator .tabulator-cell.tabulator-editing {
    outline: 2px solid var(--color-primary, #3b82f6) !important;
    outline-offset: -2px !important;
    padding: 8px !important;
    background-color: #ffffff !important;
}

.tabulator .tabulator-cell.tabulator-editing textarea,
.tabulator .tabulator-cell.tabulator-editing input {
    font-family: inherit !important;
    font-size: 0.875rem !important;
    color: #0f172a !important;
    background: transparent !important;
    border: none !important;
    outline: none !important;
    width: 100% !important;
    resize: vertical !important;
}

/* Frozen Actions column: opaque, so scrolled content never shows through */
.tabulator .tabulator-cell.tabulator-frozen {
    background-color: #ffffff !important;
    box-shadow: 2px 0 6px rgba(0, 0, 0, 0.06) !important;
    z-index: 2;
}

.tabulator .tabulator-row:hover .tabulator-cell.tabulator-frozen {
    background-color: #f6f8fb !important;
}

.tabulator .tabulator-row.tabulator-selected .tabulator-cell.tabulator-frozen {
    background-color: #eff5fe !important;
}

.tabulator .tabulator-header .tabulator-col.tabulator-frozen {
    background-color: #f1f5f9 !important;
    /* same as the header background */
    box-shadow: 2px 0 6px rgba(0, 0, 0, 0.06) !important;
    z-index: 3;
}

/* Custom Scrollbar for Grid Container */
.tabulator .tabulator-tableholder {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.35) transparent;
}

.tabulator .tabulator-tableholder::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.tabulator .tabulator-tableholder::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.35);
    /* border-radius: 999px; */
}


/* ==========================================================================
   Sidebar Card Editor Collapse State
   ========================================================================== */
#card-editor-sidebar.collapsed #sidebar-content-wrapper {
    display: none !important;
}
```

DashboardService.php
```
<?php
/**
 * DashboardService
 * 
 * Centralized service layer for handling database reads, user profiling,
 * spaced repetition metric analytics, and streak tracking.
 */
class DashboardService
{
    /**
     * Retrieve authenticated user profile details.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @return array Profile metadata containing display name.
     */
    public static function getUserProfile(mysqli $conn, int $userId): array
    {
        $sql = "SELECT first_name, last_name FROM users WHERE id = ? AND deleted_at IS NULL";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            return ['displayName' => 'Active Learner'];
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $firstName, $lastName);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        
        return [
            'displayName' => !empty($fullName) ? $fullName : 'Active Learner'
        ];
    }

    /**
     * Fetch core flashcard and study session metrics.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @return array Calculated user metrics.
     */
    public static function getMetrics(mysqli $conn, int $userId): array
    {
        // 1. Cards Due Today
        $due = self::fetchCount($conn, 
            "SELECT COUNT(*) FROM flashcards f 
             JOIN decks d ON f.deck_id = d.id
             WHERE d.user_id = ? AND d.deleted_at IS NULL AND f.deleted_at IS NULL AND f.next_review_at <= NOW()", 
            $userId
        );

        // 2. Mastered Cards (Interval >= 21 days)
        $mastered = self::fetchCount($conn, 
            "SELECT COUNT(*) FROM flashcards f 
             JOIN decks d ON f.deck_id = d.id 
             WHERE d.user_id = ? AND d.deleted_at IS NULL AND f.deleted_at IS NULL AND f.interval_days >= 21", 
            $userId
        );

        // 3. Tough Cards / Leeches (Lapses >= 8)
        $tough = self::fetchCount($conn, 
            "SELECT COUNT(*) FROM flashcards f 
             JOIN decks d ON f.deck_id = d.id 
             WHERE d.user_id = ? AND d.deleted_at IS NULL AND f.deleted_at IS NULL AND f.lapses >= 8", 
            $userId
        );

        // 4. Cards Reviewed Today
        $reviewedToday = self::fetchCount($conn, 
            "SELECT COALESCE(SUM(total_reviewed), 0) FROM review_sessions 
             WHERE user_id = ? AND ended_at IS NOT NULL AND DATE(started_at) = CURDATE()", 
            $userId
        );

        // 5. Retention Rate Percentage
        $retentionRate = 0.0;
        $retentionSql = "SELECT COALESCE(SUM(remembered_count), 0), COALESCE(SUM(total_reviewed), 0) 
                         FROM review_sessions 
                         WHERE user_id = ? AND ended_at IS NOT NULL";
                         
        if ($stmt = mysqli_prepare($conn, $retentionSql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $remembered, $reviewed);
            if (mysqli_stmt_fetch($stmt) && $reviewed > 0) {
                $retentionRate = round(($remembered / $reviewed) * 100, 1);
            }
            mysqli_stmt_close($stmt);
        }

        return [
            'cardsDue' => $due,
            'masteredCards' => $mastered,
            'toughCards' => $tough,
            'cardsReviewedToday' => $reviewedToday,
            'retentionRate' => $retentionRate
        ];
    }

    /**
     * Compute activity history, streaks, and heatmap data.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @return array Streak counters and calendar heatmap data.
     */
    public static function getStreakAndHeatmapData(mysqli $conn, int $userId): array
    {
        $sessionSql = "SELECT DATE(started_at) as session_date, 
                              SUM(remembered_count) as total_remembered, 
                              SUM(total_reviewed) as total_reviewed
                       FROM review_sessions 
                       WHERE user_id = ? AND ended_at IS NOT NULL
                       GROUP BY DATE(started_at)
                       HAVING SUM(total_reviewed) > 0
                       ORDER BY session_date DESC";

        $stmt = mysqli_prepare($conn, $sessionSql);
        if (!$stmt) {
            return self::emptyStreakState();
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $activityHistory = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $activityHistory[$row['session_date']] = [
                'remembered' => (int) $row['total_remembered'],
                'reviewed' => (int) $row['total_reviewed']
            ];
        }
        mysqli_stmt_close($stmt);

        if (empty($activityHistory)) {
            return self::emptyStreakState();
        }

        // Streak Analytics Processing
        $todayStr = date('Y-m-d');
        $yesterdayStr = date('Y-m-d', strtotime('-1 day'));
        
        $currentStreak = 0;
        $bestStreak = 0;
        $streakAccuracy = 0.0;
        
        $dates = array_keys($activityHistory);
        $latestDate = $dates[0];

        // Active Streak calculation
        if ($latestDate === $todayStr || $latestDate === $yesterdayStr) {
            $checkDate = new DateTime($latestDate);
            $streakRemembered = 0;
            $streakReviewed = 0;

            while (true) {
                $dateKey = $checkDate->format('Y-m-d');
                if (isset($activityHistory[$dateKey])) {
                    $currentStreak++;
                    $streakRemembered += $activityHistory[$dateKey]['remembered'];
                    $streakReviewed += $activityHistory[$dateKey]['reviewed'];
                    $checkDate->modify('-1 day');
                } else {
                    break;
                }
            }

            if ($streakReviewed > 0) {
                $streakAccuracy = round(($streakRemembered / $streakReviewed) * 100, 1);
            }
        }

        // Best Historical Streak calculation
        $tempStreak = 0;
        $expectedDate = null;

        foreach (array_reverse($dates) as $dateStr) {
            $currentDate = new DateTime($dateStr);
            if ($expectedDate === null || $currentDate == $expectedDate) {
                $tempStreak++;
            } else {
                if ($tempStreak > $bestStreak) {
                    $bestStreak = $tempStreak;
                }
                $tempStreak = 1;
            }
            $expectedDate = clone $currentDate;
            $expectedDate->modify('+1 day');
        }

        if ($tempStreak > $bestStreak) {
            $bestStreak = $tempStreak;
        }

        // Heatmap bounds calculation
        $heatmapEnd = new DateTime('today');
        $heatmapStart = (clone $heatmapEnd)->modify('-364 days');
        $heatmapData = [];
        $studyDays = 0;

        foreach ($activityHistory as $dateStr => $metrics) {
            $activityDate = DateTime::createFromFormat('!Y-m-d', $dateStr);
            if ($activityDate && $activityDate >= $heatmapStart && $activityDate <= $heatmapEnd && $metrics['reviewed'] > 0) {
                $heatmapData[$dateStr] = $metrics['reviewed'];
                $studyDays++;
            }
        }

        $calendarStart = clone $heatmapStart;
        $calendarStart->modify('monday this week');

        return [
            'currentStreak' => $currentStreak,
            'bestStreak' => $bestStreak,
            'streakAccuracy' => $streakAccuracy,
            'studyDays' => $studyDays,
            'heatmapData' => $heatmapData,
            'heatmapStart' => $heatmapStart,
            'heatmapEnd' => $heatmapEnd,
            'calendarStart' => $calendarStart
        ];
    }

    /**
     * Retrieve recent completed study sessions for the activity feed.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @param int $limit Max sessions to return.
     * @return array Session history items.
     */
    public static function getRecentSessions(mysqli $conn, int $userId, int $limit = 4): array
    {
        $sql = "SELECT rs.id, d.title as deck_title, rs.total_reviewed as cards_reviewed, 
                       rs.accuracy_percentage as accuracy, rs.started_at 
                FROM review_sessions rs 
                JOIN decks d ON rs.deck_id = d.id 
                WHERE rs.user_id = ? AND rs.ended_at IS NOT NULL 
                ORDER BY rs.started_at DESC LIMIT ?";

        $sessions = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $timeDiff = time() - strtotime($row['started_at']);
                if ($timeDiff < 3600) {
                    $formatted = floor($timeDiff / 60) . ' mins ago';
                } elseif ($timeDiff < 86400) {
                    $formatted = floor($timeDiff / 3600) . ' hours ago';
                } else {
                    $formatted = floor($timeDiff / 86400) . ' days ago';
                }

                $sessions[] = [
                    'deckTitle' => $row['deck_title'],
                    'cardsReviewed' => (int) $row['cards_reviewed'],
                    'accuracy' => (int) $row['accuracy'],
                    'formattedTime' => $formatted
                ];
            }
            mysqli_stmt_close($stmt);
        }

        return $sessions;
    }

        /**
     * Decks that currently have cards due, most due first.
     *
     * @return array<int, array{id:int,title:string,dueCount:int}>
     */
    public static function getDueByDeck(mysqli $conn, int $userId): array
    {
        $sql = "SELECT d.id, d.title, COUNT(*) AS due_count
                FROM flashcards f
                JOIN decks d ON f.deck_id = d.id
                WHERE d.user_id = ? AND d.deleted_at IS NULL
                  AND f.deleted_at IS NULL AND f.next_review_at <= NOW()
                GROUP BY d.id, d.title
                ORDER BY due_count DESC, d.title ASC";

        $decks = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $decks[] = [
                    'id'       => (int) $row['id'],
                    'title'    => $row['title'],
                    'dueCount' => (int) $row['due_count'],
                ];
            }
            mysqli_stmt_close($stmt);
        }
        return $decks;
    }

    /**
     * Seconds until the next card becomes due, or null if the user has no upcoming cards.
     * Computed in SQL so PHP/MySQL timezone differences cannot skew it.
     */
    public static function getNextDueSeconds(mysqli $conn, int $userId): ?int
    {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), MIN(f.next_review_at))
                FROM flashcards f
                JOIN decks d ON f.deck_id = d.id
                WHERE d.user_id = ? AND d.deleted_at IS NULL
                  AND f.deleted_at IS NULL AND f.next_review_at > NOW()";

        $secs = null;
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $secs);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }
        return $secs === null ? null : (int) $secs;
    }
    private static function fetchCount(mysqli $conn, string $query, int $userId): int
    {
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $count);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            return (int) $count;
        }
        return 0;
    }

    private static function emptyStreakState(): array
    {
        return [
            'currentStreak' => 0,
            'bestStreak' => 0,
            'streakAccuracy' => 0.0,
            'studyDays' => 0,
            'heatmapData' => [],
            'heatmapStart' => new DateTime('-364 days'),
            'heatmapEnd' => new DateTime('today'),
            'calendarStart' => (new DateTime('-364 days'))->modify('monday this week')
        ];
    }
}
```

