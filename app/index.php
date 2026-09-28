<?php
// 1. Initialize user session tracking
session_start();

// 2. Route Protection: Redirect to login view if user credentials are uninitialized
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

// 3. Connect to database layout module
require_once __DIR__ . '/../config/db.php'; 

$user_id = $_SESSION['user_id'];

// --- EXTRACTION: LIVE USER PROFILE DETAILS ---
// Pulling your newly added first_name and last_name columns directly from your updated users table
$user_sql  = "SELECT first_name, last_name FROM users WHERE id = ? AND deleted_at IS NULL";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stable_bind_param: mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
mysqli_stmt_bind_result($user_stmt, $first_name, $last_name);
mysqli_stmt_fetch($user_stmt);
mysqli_stmt_close($user_stmt);

// Handle display fallback strings defensively
$display_name = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
if (empty($display_name)) {
    $display_name = "Active Learner";
}

// --- METRIC 1: CARDS DUE TODAY ---
$due_sql = "SELECT COUNT(*) FROM flashcards f 
            JOIN decks d ON f.deck_id = d.id 
            WHERE d.user_id = ? AND f.deleted_at IS NULL AND f.next_review_at <= NOW()";
$due_stmt = mysqli_prepare($conn, $due_sql);
mysqli_stmt_bind_param($due_stmt, "i", $user_id);
mysqli_stmt_execute($due_stmt);
mysqli_stmt_bind_result($due_stmt, $cards_due);
mysqli_stmt_fetch($due_stmt);
mysqli_stmt_close($due_stmt);

// --- METRIC 2: MASTERED CARDS (Interval days >= 21) ---
$mastered_sql = "SELECT COUNT(*) FROM flashcards f 
                 JOIN decks d ON f.deck_id = d.id 
                 WHERE d.user_id = ? AND f.deleted_at IS NULL AND f.interval_days >= 21";
$mastered_stmt = mysqli_prepare($conn, $mastered_sql);
mysqli_stmt_bind_param($mastered_stmt, "i", $user_id);
mysqli_stmt_execute($mastered_stmt);
mysqli_stmt_bind_result($mastered_stmt, $mastered_cards);
mysqli_stmt_fetch($mastered_stmt);
mysqli_stmt_close($mastered_stmt);

// --- METRIC 3: LIFETIME ACCURACY (Average from generated virtual column) ---
$accuracy_sql = "SELECT AVG(accuracy_percentage) FROM review_sessions WHERE user_id = ? AND ended_at IS NOT NULL";
$acc_stmt = mysqli_prepare($conn, $accuracy_sql);
mysqli_stmt_bind_param($acc_stmt, "i", $user_id);
mysqli_stmt_execute($acc_stmt);
mysqli_stmt_bind_result($acc_stmt, $avg_accuracy);
mysqli_stmt_fetch($acc_stmt);
mysqli_stmt_close($acc_stmt);
$avg_accuracy = $avg_accuracy !== null ? round($avg_accuracy, 1) : 0.0;

// --- METRIC 4: TOUGH CARDS / LEECHES (Lapses >= 8) ---
$tough_sql = "SELECT COUNT(*) FROM flashcards f 
              JOIN decks d ON f.deck_id = d.id 
              WHERE d.user_id = ? AND f.deleted_at IS NULL AND f.lapses >= 8";
$tough_stmt = mysqli_prepare($conn, $tough_sql);
mysqli_stmt_bind_param($tough_stmt, "i", $user_id);
mysqli_stmt_execute($tough_stmt);
mysqli_stmt_bind_result($tough_stmt, $tough_cards);
mysqli_stmt_fetch($tough_stmt);
mysqli_stmt_close($tough_stmt);

// --- COMPUTE STREAKS FROM REVIEW SESSIONS HISTORIES ---
$current_streak = 0;
$best_streak = 0;
$streak_accuracy = 0.0;

// 1. Fetch distinct active dates and total performance metrics for completed sessions
$session_sql = "SELECT DATE(started_at) as session_date, 
                       SUM(remembered_count) as total_remembered, 
                       SUM(total_reviewed) as total_reviewed
                FROM review_sessions 
                WHERE user_id = ? 
                  AND ended_at IS NOT NULL
                GROUP BY DATE(started_at)
                HAVING SUM(total_reviewed) > 0
                ORDER BY session_date DESC";

$session_stmt = mysqli_prepare($conn, $session_sql);
mysqli_stmt_bind_param($session_stmt, "i", $user_id);
mysqli_stmt_execute($session_stmt);
$result = mysqli_stmt_get_result($session_stmt);

$activity_history = [];
while ($row = mysqli_fetch_assoc($result)) {
    $activity_history[$row['session_date']] = [
        'remembered' => intval($row['total_remembered']),
        'reviewed' => intval($row['total_reviewed'])
    ];
}
mysqli_stmt_close($session_stmt);

// --- STREAK HEATMAP: LAST 365 DAYS ---
// Reuse the same completed review-session history already loaded above so
// heatmap activity and streak calculations stay based on one source of truth.
$heatmap_end = new DateTime('today');
$heatmap_start = (clone $heatmap_end)->modify('-364 days');
$heatmap_data = [];
$study_days = 0;

foreach ($activity_history as $date_str => $metrics) {
    $activity_date = DateTime::createFromFormat('!Y-m-d', $date_str);
    if (!$activity_date) {
        continue;
    }

    if ($activity_date >= $heatmap_start && $activity_date <= $heatmap_end && $metrics['reviewed'] > 0) {
        $review_count = $metrics['reviewed'];
        $heatmap_data[$date_str] = $review_count;
        $study_days++;
    }
}

// Align the visual grid to Monday-Sunday while keeping exactly the requested
// 365-day data window. Padded days outside that window render invisibly.
$heatmap_calendar_start = clone $heatmap_start;
$heatmap_calendar_start->modify('monday this week');
$heatmap_calendar_end = clone $heatmap_end;
$heatmap_calendar_end->modify('sunday this week');
$heatmap_week_count = (int) floor(($heatmap_calendar_end->diff($heatmap_calendar_start)->days + 1) / 7);

// Fixed, human-readable activity bands. The cell color is still data-driven.
$heatmap_level_class = static function (int $reviews): string {
    if ($reviews <= 0) {
        return 'bg-surface-container-high';
    }
    if ($reviews <= 5) {
        return 'bg-primary-fixed';
    }
    if ($reviews <= 15) {
        return 'bg-primary-fixed-dim';
    }
    if ($reviews <= 30) {
        return 'bg-primary-container';
    }
    return 'bg-primary';
};

if (!empty($activity_history)) {
    $today_str = date('Y-m-d');
    $yesterday_str = date('Y-m-d', strtotime('-1 day'));
    
    // 2. Compute Current Streak & Tracking Parameters
    $tracking_date = current(array_keys($activity_history));
    
    // The current streak is alive if the user reviewed today OR yesterday
    if ($tracking_date === $today_str || $tracking_date === $yesterday_str) {
        $check_date = new DateTime($tracking_date);
        $streak_remembered = 0;
        $streak_reviewed = 0;

        while (true) {
            $date_str = $check_date->format('Y-m-d');
            if (isset($activity_history[$date_str])) {
                $current_streak++;
                $streak_remembered += $activity_history[$date_str]['remembered'];
                $streak_reviewed += $activity_history[$date_str]['reviewed'];
                $check_date->modify('-1 day');
            } else {
                break;
            }
        }
        
        // Calculate the accuracy over the current streak block
        if ($streak_reviewed > 0) {
            $streak_accuracy = round(($streak_remembered / $streak_reviewed) * 100, 1);
        }
    }

    // 3. Compute Best Streak Historically
    $temp_streak = 0;
    $expected_date = null;

    // Read chronologically from past to present to find the max streak sequence
    foreach (array_reverse(array_keys($activity_history)) as $date_str) {
        $current_date = new DateTime($date_str);
        
        if ($expected_date === null || $current_date == $expected_date) {
            $temp_streak++;
        } else {
            if ($temp_streak > $best_streak) {
                $best_streak = $temp_streak;
            }
            $temp_streak = 1; // Reset sequence counter
        }
        
        // Setup expected next date (tomorrow in chronological sequence loop)
        $expected_date = clone $current_date;
        $expected_date->modify('+1 day');
    }
    
    // Final check for the last calculated streak block loop
    if ($temp_streak > $best_streak) {
        $best_streak = $temp_streak;
    }
} else {
    $best_streak = 0;
    $current_streak = 0;
    $streak_accuracy = 0.0;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemPulse Sidebar</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Plus+Jakarta+Sans:wght@600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    <style>
        @layer base {

            html,
            body {
                margin: 0;
                padding: 0;
            }

            body {
                overscroll-behavior: none;
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

        /* Show scrollbar only for the heatmap */
        #heatmap-scroll {
            scrollbar-width: thin;
            scrollbar-color: rgba(100, 116, 139, 0.35) transparent;
        }

        #heatmap-scroll::-webkit-scrollbar {
            display: block;
            height: 8px;
        }

        #heatmap-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        #heatmap-scroll::-webkit-scrollbar-thumb {
            background: rgba(100, 116, 139, 0.35);
            border-radius: 999px;
        }

        #heatmap-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(100, 116, 139, 0.55);
        }
        
        #heatmap-scroll .heatmap-cell {
            position: relative;
            cursor: pointer;
            overflow: visible;
        }

        #heatmap-scroll .heatmap-cell::after {
            content: attr(data-tooltip);
            position: fixed;
            left: var(--tooltip-left);
            top: var(--tooltip-top);
            transform: translate(-50%, -100%);
            background: #213145;
            color: #ffffff;
            font-size: 11px;
            line-height: 1.3;
            padding: 6px 8px;
            border-radius: 7px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.15s ease;
            z-index: 9999;
        }

        #heatmap-scroll .heatmap-cell:hover::after {
            opacity: 1;
        }

        

        .stroke-dash-96 {
            stroke-dasharray: 96, 100;
        }

        /* Sidebar Toggle */
        /* --- Base Collapsed Sidebar Rules --- */
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
    </style>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">tailwind.config = { darkMode: "class", theme: { extend: { "colors": { "primary": "#3525cd", "on-surface-variant": "#464555", "on-secondary-fixed-variant": "#5a00c6", "primary-fixed": "#e2dfff", "on-tertiary-fixed": "#2a1700", "background": "#f8f9ff", "on-surface": "#0b1c30", "secondary-container": "#8a4cfc", "secondary-fixed": "#eaddff", "primary-container": "#4f46e5", "tertiary-fixed": "#ffddb8", "surface-container-low": "#eff4ff", "on-primary-container": "#dad7ff", "inverse-surface": "#213145", "surface-container-lowest": "#ffffff", "on-primary": "#ffffff", "error": "#ba1a1a", "on-tertiary-fixed-variant": "#653e00", "on-tertiary-container": "#ffd4a4", "surface-container-highest": "#d3e4fe", "on-tertiary": "#ffffff", "primary-fixed-dim": "#c3c0ff", "surface-container-high": "#dce9ff", "on-secondary": "#ffffff", "surface-dim": "#cbdbf5", "surface": "#f8f9ff", "tertiary-fixed-dim": "#ffb95f", "outline-variant": "#c7c4d8", "on-secondary-container": "#fffbff", "on-background": "#0b1c30", "surface-bright": "#f8f9ff", "secondary-fixed-dim": "#d2bbff", "secondary": "#712ae2", "on-error-container": "#93000a", "inverse-primary": "#c3c0ff", "surface-tint": "#4d44e3", "surface-container": "#e5eeff", "on-primary-fixed": "#0f0069", "outline": "#777587", "surface-variant": "#d3e4fe", "inverse-on-surface": "#eaf1ff", "on-secondary-fixed": "#25005a", "on-error": "#ffffff", "on-primary-fixed-variant": "#3323cc", "tertiary": "#684000", "tertiary-container": "#885500", "error-container": "#ffdad6" }, "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" }, "spacing": { "margin": "2rem", "space-sm": "0.5rem", "space-lg": "1.5rem", "space-xl": "2.5rem", "gutter": "1.5rem", "space-md": "1rem", "space-xs": "0.25rem" }, "fontFamily": { "body-md": ["Inter"], "label-sm": ["Inter"], "headline-sm": ["Plus Jakarta Sans"], "headline-md": ["Plus Jakarta Sans"], "label-md": ["Inter"], "display": ["Plus Jakarta Sans"], "headline-lg-mobile": ["Plus Jakarta Sans"], "display-mobile": ["Plus Jakarta Sans"], "headline-lg": ["Plus Jakarta Sans"], "body-lg": ["Inter"], "label-lg": ["Inter"], "body-sm": ["Inter"] }, "fontSize": { "body-md": ["14px", { "lineHeight": "22px", "fontWeight": "400" }], "label-sm": ["11px", { "lineHeight": "14px", "fontWeight": "600" }], "headline-sm": ["18px", { "lineHeight": "26px", "fontWeight": "600" }], "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }], "label-md": ["12px", { "lineHeight": "16px", "fontWeight": "500" }], "display": ["48px", { "lineHeight": "56px", "fontWeight": "700" }], "headline-lg-mobile": ["26px", { "lineHeight": "34px", "fontWeight": "600" }], "display-mobile": ["36px", { "lineHeight": "44px", "fontWeight": "700" }], "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "600" }], "body-lg": ["16px", { "lineHeight": "26px", "fontWeight": "400" }], "label-lg": ["14px", { "lineHeight": "20px", "fontWeight": "500" }], "body-sm": ["12px", { "lineHeight": "18px", "fontWeight": "400" }] } } } };</script>
</head>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-50 flex flex-col justify-between py-6 px-4 shadow-[0_1px_8px_rgba(0,0,0,0.03)] transition-all duration-300 ease-in-out">
        <div class="flex flex-col gap-8">

            <!-- Outer layout section container -->
            <div id="sidebar-header" class="flex items-center justify-between gap-3 px-3 transition-all duration-300">
                <!-- Inner container holding the logo image assets -->
                <div class="flex items-center gap-3 flex-shrink-0">
                    <img alt="MemPulse Logo" class="h-12 w-auto object-contain flex-shrink-0" src="./assets/images/mempulse-logo.png" />
                    <span class="logo-text font-headline-sm text-headline-sm font-semibold tracking-tight text-on-surface transition-all duration-200">MemPulse</span>
                </div>

                <!-- Toggle Action Button element -->
                <button id="sidebar-toggle" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors flex-shrink-0" title="Toggle Sidebar">
                    <span class="material-symbols-outlined text-[24px]">left_panel_close</span>
                </button>
            </div>
            <nav class="flex flex-col gap-1.5" data-active-classes="bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm">
                <a aria-current="page" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm" data-path="home" href="#">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">home</span>
                        <span class="sidebar-text transition-opacity duration-200">Home</span>
                    </div>
                </a>
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all group" data-path="decks" href="#">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">style</span>
                        <span class="sidebar-text transition-opacity duration-200">Decks</span>
                    </div>
                </a>
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all group" data-path="review" href="#">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">pace</span>
                        <span class="sidebar-text transition-opacity duration-200">Review</span>
                    </div>
                    <!-- FIX 2: Wrapped review badge in sidebar-text container so it disappears when collapsed -->
                    <span class="sidebar-text px-2 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm tracking-wide transition-opacity duration-200">42</span>
                </a>
            </nav>
        </div>
        <div class="flex flex-col gap-2 pt-4 border-t border-outline-variant/30">
            <nav class="flex flex-col gap-1" data-active-classes="bg-surface-container-high text-on-surface font-semibold">
                <a class="flex items-center gap-3 px-3.5 py-2 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all" data-path="settings" href="#">
                    <span class="material-symbols-outlined text-[24px]">settings</span>
                    <span class="sidebar-text transition-opacity duration-200">Settings</span>
                </a>
                <a class="flex items-center gap-3 px-3.5 py-2 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-error transition-all" data-path="logout" href="../authentication/logout.php">
                <span class="material-symbols-outlined text-[24px]">logout</span>
                <span class="sidebar-text transition-opacity duration-200">Logout</span>
                </a>

            </nav>
            <!-- <div class="mt-2 pt-2 px-2 flex items-center justify-between rounded-xl bg-surface-container-low/60 p-2.5">
                <div class="flex items-center gap-3 overflow-hidden"> -->
                    <!-- FIX 3: Removed the layout wrapping error. Image stays visible, texts go inside the target wrapper block -->
                    <!-- <img alt="Profile" class="w-8 h-8 rounded-full object-cover ring-1 ring-outline-variant/50 flex-shrink-0" src="./assets/images/profile-picture.webp.jpg" />

                    <div class="sidebar-text flex flex-col min-w-0 transition-opacity duration-200">
                        <span id="sidebar-profile-name" class="font-label-md text-label-md text-on-surface truncate font-semibold">--</span>
                        <span id="sidebar-profile-role" class="font-body-sm text-body-sm text-on-surface-variant truncate">Active Learner</span>
                    </div>
                </div> -->
                <!-- FIX 4: Wrapped more options action button in sidebar-text element -->
                <!-- <a class="sidebar-text text-on-surface-variant hover:text-primary transition-colors transition-opacity duration-200" data-path="user-account" href="#" title="Account details">
                    <span class="material-symbols-outlined text-[18px]">more_vert</span>
                </a>
            </div> -->
        </div>
    </aside>


    <main class="pl-72 pt-12 transition-all duration-300 ease-in-out">
        <header class="fixed top-0 left-72 right-0 h-16 bg-surface/85 backdrop-blur-md z-40 flex items-center justify-between px-8 border-b border-outline-variant/20 transition-all duration-300 ease-in-out">
            <div class="flex items-center gap-3 w-80">
                <div class="flex items-center gap-2 w-full px-3 py-1.5 rounded-lg bg-surface-container-low border border-outline-variant/30 text-on-surface-variant">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                    <span class="font-body-sm text-body-sm flex-1 select-none">Search cards, tags, decks...</span>
                    <span class="font-label-sm text-label-sm px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant/80 border border-outline-variant/40">⌘K</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <!-- Streak Indicator Badge Wrapper -->
                <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-[#fffbeb] border border-amber-200/80 text-tertiary shadow-sm">
                    <span class="material-symbols-outlined text-amber-500 text-[18px]">local_fire_department</span>
                    <!-- ID ADDED: Targets the display text node inside your top layout block -->
                    <span id="header-streak-count" class="font-label-md text-label-md font-semibold tracking-wide">-- Days</span>
                </div>

                <!-- User Account Action Link -->
                <a class="flex items-center gap-2" data-path="user-account" href="#" title="View Account Details">
                    <!-- ID ADDED: Targets the source parameter image node directly -->
                    <img id="header-profile-img" alt="Profile" class="w-8 h-8 rounded-full object-cover ring-2 ring-primary-fixed" src="./assets/images/profile-picture.webp.jpg">
                </a>
            </div>
        </header>


        <div class="max-w-7xl mx-auto px-8 py-8">
            <div class="flex flex-col w-full gap-8">
                <!-- Interactive Learning Science Hero Carousel (Top Position) - Lowered min-height -->
                <section class="relative w-full rounded-2xl overflow-hidden shadow-lg min-h-[300px] bg-inverse-surface text-inverse-on-surface" id="hero-carousel">
                    <!-- Slides Container - Lowered min-height -->
                    <div class="relative w-full min-h-[300px]">

                        <!-- Slide 1 -->
                        <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-in-out flex items-center opacity-100 z-10" data-slide-index="0">
                            <div class="absolute inset-0 z-0 bg-inverse-surface">
                                <!-- Image Wrapper: Increased size constraints to make the chart look larger -->
                                <div class="absolute right-0 top-0 bottom-0 w-full lg:w-1/2 p-4 md:p-6 flex items-center justify-center">
                                    <!-- Increased max-height constraint from 180px to 240px -->
                                    <div class="w-full max-h-[240px] flex items-center justify-center overflow-hidden">
                                        <img alt="Forgetting Curve Memory Retention Diagram" class="max-h-[240px] w-full object-contain bg-white rounded-xl p-3 shadow-lg border border-white/20" src="./assets/images/forgetting-curve-hero-image.webp">
                                    </div>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-r from-inverse-surface via-inverse-surface/90 to-transparent lg:w-3/5 z-0"></div>
                            </div>
                            <!-- Text Content: Standardized padding to p-6 md:p-8 and removed empty markup gaps -->
                            <div class="relative z-10 p-6 md:p-8 max-w-2xl flex flex-col gap-3">
                                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-secondary-container/50 backdrop-blur-md text-on-secondary-container font-label-sm text-label-sm w-fit border border-secondary-fixed/20 shadow-sm">
                                    <span class="material-symbols-outlined text-[16px] text-cyan-300">psychology</span>
                                    <span>Memory Science • Ebbinghaus Forgetting Curve</span>
                                </div>
                                <h2 class="font-headline-lg text-headline-lg font-bold tracking-tight text-white leading-tight">
                                    Beat the Forgetting Curve with <br>Spaced-Repetition
                                </h2>
                                <p class="font-body-md text-body-md text-slate-200 leading-relaxed">
                                    Review concepts before you forget them to lock knowledge into <br>long-term memory.
                                </p>
                                <div class="flex flex-wrap items-center gap-3 pt-1">
                                    <button class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-lg text-label-lg shadow-md hover:shadow-lg transition-all group">
                                        <span>Learn More</span>
                                        <span class="material-symbols-outlined text-[18px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Slide 2 -->
                        <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-in-out flex items-center opacity-0 pointer-events-none z-0" data-slide-index="1">
                            <div class="absolute inset-0 z-0 bg-inverse-surface">
                                <!-- Image Wrapper: Lowered max-height and set uniform p-6 -->
                                <div class="absolute right-0 top-0 bottom-0 w-full lg:w-1/2 p-6 flex items-center justify-center">
                                    <div class="relative flex items-center justify-center w-full h-full max-h-[220px]">
                                        <div class="absolute w-48 h-48 rounded-full bg-secondary-container/50 blur-3xl pointer-events-none"></div>
                                        <img alt="MemPulse Deck Architecture Icon" class="relative z-10 max-h-[200px] w-auto object-contain transition-transform duration-500 hover:scale-105" src="./assets/images/decks-hero-icon.png">
                                    </div>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-r from-inverse-surface via-inverse-surface/90 to-transparent lg:w-3/5 z-0"></div>
                                <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface/90 via-transparent to-transparent z-0"></div>
                            </div>
                            <!-- Text Content: Standardized padding to p-6 md:p-8 -->
                            <div class="relative z-10 p-6 md:p-8 max-w-2xl flex flex-col gap-3">
                                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-secondary-container/50 backdrop-blur-md text-on-secondary-container font-label-sm text-label-sm w-fit border border-secondary-fixed/20 shadow-sm">
                                    <span class="material-symbols-outlined text-[16px] text-cyan-300">style</span>
                                    <span>Deck Architecture • Smart Organization</span>
                                </div>
                                <h2 class="font-headline-lg text-headline-lg font-bold tracking-tight text-white leading-tight">
                                    Organize Your Flashcards with Decks
                                </h2>
                                <p class="font-body-md text-body-md text-slate-200 leading-relaxed">Transform topics into organized, custom study decks.</p>
                                <div class="flex flex-wrap items-center gap-3 pt-1">
                                    <button class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-lg text-label-lg shadow-md hover:shadow-lg transition-all group">
                                        <span class="material-symbols-outlined text-[18px]">add</span>
                                        <span>Create Deck</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Slide 3 -->
                        <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-in-out flex items-center opacity-0 pointer-events-none z-0" data-slide-index="2">
                            <div class="absolute inset-0 z-0 bg-inverse-surface">
                                <!-- Image Wrapper: Lowered max-height and set uniform p-6 -->
                                <div class="absolute right-0 top-0 bottom-0 w-full lg:w-1/2 p-6 flex items-center justify-center">
                                    <div class="relative flex items-center justify-center w-full h-full max-h-[220px]">
                                        <div class="absolute w-48 h-48 rounded-full bg-primary-container/20 blur-3xl pointer-events-none"></div>
                                        <img alt="MemPulse Colorful Flashcard Stack" class="relative z-10 max-h-[200px] w-auto object-contain transition-transform duration-500 hover:scale-105" src="./assets/images/flashcards-hero-icon.png">
                                    </div>
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-r from-inverse-surface via-inverse-surface/90 to-transparent lg:w-3/5 z-0"></div>
                                <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface/90 via-transparent to-transparent z-0"></div>
                            </div>
                            <!-- Text Content: Standardized padding to p-6 md:p-8 -->
                            <div class="relative z-10 p-6 md:p-8 max-w-2xl flex flex-col gap-3">
                                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-secondary-container/50 backdrop-blur-md text-on-secondary-container font-label-sm text-label-sm w-fit border border-secondary-fixed/20 shadow-sm">
                                    <span class="material-symbols-outlined text-[16px] text-cyan-300">style</span>
                                    <span>Flashcard Design • Atomic Learning</span>
                                </div>
                                <h2 class="font-headline-lg text-headline-lg font-bold tracking-tight text-white leading-tight">
                                    Build Your Flashcards with Precision
                                </h2>
                                <p class="font-body-md text-body-md text-slate-200 leading-relaxed">Structure complex subjects into bite-sized, high-retention study cards.</p>
                                <div class="flex flex-wrap items-center gap-3 pt-1">
                                    <button class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-lg text-label-lg shadow-md hover:shadow-lg transition-all group">
                                        <span class="material-symbols-outlined text-[18px]">add</span>
                                        <span>Create Card</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carousel Pagination Indicators -->
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2" id="hero-dots">
                        <button aria-label="Slide 1" class="hero-dot h-2.5 rounded-full transition-all duration-300 bg-cyan-400 w-8" data-dot-index="0"></button>
                        <button aria-label="Slide 2" class="hero-dot h-2.5 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/40 w-2.5" data-dot-index="1"></button>
                        <button aria-label="Slide 3" class="hero-dot h-2.5 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/40 w-2.5" data-dot-index="2"></button>
                    </div>
                </section>
                <!-- Spaced Repetition Core Database Metrics Grid -->
                <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 w-full">

                    <!-- 1. Cards Due Today (Workload) -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-primary/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                            <span class="material-symbols-outlined text-[26px]">alarm</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">Cards Due</span>
                            <!-- ADDED TARGET ID HERE -->
                            <span id="stat-cards-due" class="font-headline-md text-xl font-bold tracking-tight text-on-surface mt-0.5">--</span>
                        </div>
                    </div>

                    <!-- 2. Mastered Cards (Retention) -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-teal-500/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 border border-teal-100 flex-shrink-0">
                            <span class="material-symbols-outlined text-[26px]">verified</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">Mastered</span>
                            <!-- ADDED TARGET ID HERE -->
                            <span id="stat-mastered" class="font-headline-md text-xl font-bold tracking-tight text-on-surface mt-0.5">--</span>
                        </div>
                    </div>

                    <!-- 3. Lifetime Accuracy (Performance) -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-indigo-500/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 flex-shrink-0">
                            <span class="material-symbols-outlined text-[26px]">analytics</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">Avg Accuracy</span>
                            <!-- ADDED TARGET ID HERE -->
                            <span id="stat-accuracy" class="font-headline-md text-xl font-bold tracking-tight text-on-surface mt-0.5">--%</span>
                        </div>
                    </div>

                    <!-- 4. Tough Cards / Leeches (Troubleshooting) -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-error/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-error border border-red-100 flex-shrink-0">
                            <span class="material-symbols-outlined text-[26px]">psychology_alt</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">Tough Cards</span>
                            <!-- ADDED TARGET ID HERE -->
                            <span id="stat-tough" class="font-headline-md text-xl font-bold tracking-tight text-on-surface mt-0.5">--</span>
                        </div>
                    </div>

                </section>

                <section class="grid grid-cols-1 lg:grid-cols-4 gap-6 w-full">
                    <!-- Streak Heatmap (3/4 width on desktop) -->
                    <div class="lg:col-span-3 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex flex-col h-[300px]">
                        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">calendar_month</span>
                                    <h3 class="font-headline-sm text-[16px] font-semibold text-on-surface">Streak Heatmap</h3>
                                </div>
                                <p class="text-[11px] text-on-surface-variant mt-0.5 ml-7">Last 365 days • <span class="font-semibold text-on-surface"><?php echo $study_days; ?> / 365</span> study days</p>
                            </div>
                            <div class="flex items-center gap-1.5 font-label-sm text-[11px] text-on-surface-variant">
                                <span>Less</span>
                                <span class="w-3 h-3 rounded bg-surface-container-high"></span>
                                <span class="w-3 h-3 rounded bg-primary-fixed"></span>
                                <span class="w-3 h-3 rounded bg-primary-fixed-dim"></span>
                                <span class="w-3 h-3 rounded bg-primary-container"></span>
                                <span class="w-3 h-3 rounded bg-primary"></span>
                                <span>More</span>
                            </div>
                        </div>

                        <div id="heatmap-scroll" class="overflow-x-auto pb-1 flex-1 flex flex-col justify-center">
                            <div class="min-w-max">
                                <!-- Month labels align with the weekly columns below. -->
                                <div class="flex gap-1 pl-10 mb-1.5 h-4 text-[11px] font-medium text-on-surface-variant/80">
                                    <?php
                                    $label_week_start = clone $heatmap_calendar_start;
                                    for ($week_index = 0; $week_index < $heatmap_week_count; $week_index++) {
                                        $label = '';
                                        $week_probe = clone $label_week_start;
                                        for ($day_offset = 0; $day_offset < 7; $day_offset++) {
                                            $probe_date = clone $week_probe;
                                            $probe_date->modify('+' . $day_offset . ' days');
                                            if ($probe_date < $heatmap_start || $probe_date > $heatmap_end) {
                                                continue;
                                            }
                                            if ($probe_date->format('j') === '1') {
                                                $label = $probe_date->format('M');
                                                break;
                                            }
                                        }
                                        echo '<div class="w-5 flex-none overflow-visible whitespace-nowrap">' . htmlspecialchars($label) . '</div>';
                                        $label_week_start->modify('+7 days');
                                    }
                                    ?>
                                </div>

                                <div class="flex gap-2 items-start">
                                    <div class="flex flex-col justify-between text-[11px] font-medium text-on-surface-variant/70 pr-2 select-none py-0.5 h-[168px] w-6 flex-none">
                                        <span>Mon</span>
                                        <span>Wed</span>
                                        <span>Fri</span>
                                        <span>Sun</span>
                                    </div>

                                    <div class="flex gap-1">
                                        <?php
                                        $week_start = clone $heatmap_calendar_start;
                                        for ($week_index = 0; $week_index < $heatmap_week_count; $week_index++) {
                                            echo '<div class="flex flex-col gap-1">';
                                            for ($day_offset = 0; $day_offset < 7; $day_offset++) {
                                                $cell_date = clone $week_start;
                                                $cell_date->modify('+' . $day_offset . ' days');
                                                $cell_key = $cell_date->format('Y-m-d');
                                                $in_range = $cell_date >= $heatmap_start && $cell_date <= $heatmap_end;
                                                $reviews = $in_range ? ($heatmap_data[$cell_key] ?? 0) : 0;

                                                if (!$in_range) {
                                                echo '<div class="w-5 h-5 flex-none rounded bg-transparent"></div>';
                                                continue;
}

                                                $cell_class = $heatmap_level_class($reviews);
                                                $date_label = $cell_date->format('M j, Y');
                                                $review_label = $reviews === 1 ? '1 review' : $reviews . ' reviews';
                                                $title = $reviews > 0 ? $date_label . ' — ' . $review_label : $date_label . ' — No reviews';

                                                $tooltip = $reviews > 0
                                                    ? $date_label . ' • ' . $review_label
                                                    : $date_label . ' • No reviews';

                                                echo '<div class="heatmap-cell w-5 h-5 flex-none rounded ' . $cell_class . '"'
                                                    . ' data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"'
                                                    . ' aria-label="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"></div>';                                            }
                                            echo '</div>';
                                            $week_start->modify('+7 days');
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Streak & Accuracy Stats (1/4 width on desktop) -->
                    <div class="lg:col-span-1 flex flex-col gap-3 h-[300px]">
                        <!-- Top Row: Compact Current & Best Streaks -->
                        <div class="grid grid-cols-2 gap-3 h-[120px]">
                            <!-- Current Streak Card -->
                            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-sm flex flex-col justify-between relative overflow-hidden group hover:border-amber-300 transition-colors">
                                <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-amber-500/10 rounded-full blur-xl pointer-events-none"></div>
                                <div class="flex items-center justify-between">
                                    <span class="font-label-sm text-[10px] font-semibold uppercase tracking-wider text-amber-600 bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-full">
                                        Current
                                    </span>
                                    <div class="w-6 h-6 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-500 shadow-xs">
                                        <span class="material-symbols-outlined text-[14px]">local_fire_department</span>
                                    </div>
                                </div>
                                <div class="flex items-baseline gap-1 mt-auto">
                                    <!-- ID ADDED: Target with JS -->
                                    <span id="stat-current-streak" class="font-headline-lg text-2xl font-bold tracking-tight text-on-surface">--</span>
                                    <span class="font-label-md text-[11px] text-on-surface-variant font-medium">Days</span>
                                </div>
                            </div>

                            <!-- Best Streak Card -->
                            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-sm flex flex-col justify-between relative overflow-hidden group hover:border-primary-container/40 transition-colors">
                                <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-primary/10 rounded-full blur-xl pointer-events-none"></div>
                                <div class="flex items-center justify-between">
                                    <span class="font-label-sm text-[10px] font-semibold uppercase tracking-wider text-primary bg-primary-fixed/60 border border-primary-fixed-dim/40 px-2 py-0.5 rounded-full">
                                        Best
                                    </span>
                                    <div class="w-6 h-6 rounded-lg bg-primary-fixed/60 border border-primary-fixed-dim/40 flex items-center justify-center text-primary shadow-xs">
                                        <span class="material-symbols-outlined text-[14px]">emoji_events</span>
                                    </div>
                                </div>
                                <div class="flex items-baseline gap-1 mt-auto">
                                    <!-- ID ADDED: Target with JS -->
                                    <span id="stat-best-streak" class="font-headline-lg text-2xl font-bold tracking-tight text-on-surface">--</span>
                                    <span class="font-label-md text-[11px] text-on-surface-variant font-medium">Days</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Section: Current Streak Accuracy Card -->
                        <div class="flex-1 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex flex-col justify-between relative overflow-hidden group hover:border-teal-300 transition-colors">
                            <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-teal-500/10 rounded-full blur-xl pointer-events-none"></div>
                            <div class="flex items-center justify-between">
                                <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-teal-600 bg-teal-50 border border-teal-200/60 px-2.5 py-0.5 rounded-full">
                                    Streak Accuracy
                                </span>
                                <div class="w-8 h-8 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-500 shadow-xs">
                                    <span class="material-symbols-outlined text-[18px]">track_changes</span>
                                </div>
                            </div>

                            <!-- Accuracy Details -->
                            <div class="my-auto pt-2 flex items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-baseline gap-1">
                                        <!-- ID ADDED: Target with JS -->
                                        <span id="stat-streak-accuracy" class="font-headline-lg text-3xl font-bold tracking-tight text-on-surface">--</span>
                                        <span class="font-label-md text-label-md text-on-surface-variant font-semibold">%</span>
                                    </div>
                                    <!-- ID ADDED: Target for helper subtitle text strings if needed -->
                                    <p id="stat-accuracy-subtitle" class="text-[11px] text-on-surface-variant mt-0.5">Calculated over the current streak</p>
                                </div>
                            </div>
                        </div>
                    </div>


                </section>
            </div>
        </div>
    </main>

    <script>
        // Sidebar toggle logic
        document.addEventListener("DOMContentLoaded", () => {
            const sidebar = document.querySelector("#sidebar");
            const mainContent = document.querySelector("main"); // 1. Target the main element
            const toggleBtn = document.querySelector("#sidebar-toggle");
            const toggleIcon = toggleBtn.querySelector(".material-symbols-outlined");

            toggleBtn.addEventListener("click", () => {
                // Toggle the collapsed layout state class on BOTH elements
                sidebar.classList.toggle("collapsed");
                mainContent.classList.toggle("collapsed"); // 2. Toggle main class

                // Swap the panel icons based on state
                if (sidebar.classList.contains("collapsed")) {
                    toggleIcon.textContent = "left_panel_open";
                } else {
                    toggleIcon.textContent = "left_panel_close";
                }
            });
        });

        // Function to update the top navigation header dynamically
        function updateHeaderData(userData) {
            const headerStreak = document.getElementById('header-streak-count');
            const headerProfileImg = document.getElementById('header-profile-img');

            // 1. Update Streak Count Display Node smoothly
            if (headerStreak) {
                headerStreak.textContent = userData.currentStreak + (userData.currentStreak === 1 ? ' Day' : ' Days');
            }

            // 2. Update Image Path Assets dynamically
            if (headerProfileImg && userData.profileImageUrl) {
                headerProfileImg.src = userData.profileImageUrl;
            }
        }

        // Example Integration Test Payload
        document.addEventListener("DOMContentLoaded", () => {
            const userPayload = {
                currentStreak: 24,
                profileImageUrl: "./assets/images/profile-picture.webp.jpg"
            };

            // Run the updater sequence
            // updateHeaderData(userPayload);
        });

        // Simple smooth scroll controls for deck carousel
        const track = document.getElementById('deck-track');
        const scrollLeftBtn = document.getElementById('deck-scroll-left');
        const scrollRightBtn = document.getElementById('deck-scroll-right');

        if (track && scrollLeftBtn && scrollRightBtn) {
            scrollLeftBtn.addEventListener('click', () => {
                track.scrollBy({ left: -340, behavior: 'smooth' });
            });
            scrollRightBtn.addEventListener('click', () => {
                track.scrollBy({ left: 340, behavior: 'smooth' });
            });
        }

        // Hero carousel controller
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.hero-dot');
        const heroPrev = document.getElementById('hero-prev');
        const heroNext = document.getElementById('hero-next');
        let currentSlide = 0;
        const totalSlides = slides.length;

        // Auto-play Configuration
        let autoPlayTimer = null;
        const SLIDE_DELAY = 5000; // 5 seconds interval time

        function showSlide(index) {
            currentSlide = (index + totalSlides) % totalSlides;
            slides.forEach((slide, idx) => {
                if (idx === currentSlide) {
                    slide.classList.remove('opacity-0', 'pointer-events-none', 'z-0');
                    slide.classList.add('opacity-100', 'z-10');
                } else {
                    slide.classList.remove('opacity-100', 'z-10');
                    slide.classList.add('opacity-0', 'pointer-events-none', 'z-0');
                }
            });

            dots.forEach((dot, idx) => {
                if (idx === currentSlide) {
                    dot.classList.remove('bg-white/40', 'w-2.5');
                    dot.classList.add('bg-cyan-400', 'w-8');
                } else {
                    dot.classList.remove('bg-cyan-400', 'w-8');
                    dot.classList.add('bg-white/40', 'w-2.5');
                }
            });
        }

        // Starts or restarts the auto-advance timer
        function startAutoPlay() {
            stopAutoPlay(); // Safety clear
            autoPlayTimer = setInterval(() => {
                showSlide(currentSlide + 1);
            }, SLIDE_DELAY);
        }

        // Stops the timer loop cleanly
        function stopAutoPlay() {
            if (autoPlayTimer) {
                clearInterval(autoPlayTimer);
            }
        }

        // Navigation button behaviors with user interaction reset
        if (heroPrev && heroNext) {
            heroPrev.addEventListener('click', () => {
                showSlide(currentSlide - 1);
                startAutoPlay(); // Restart timer sequence on manual interaction
            });
            heroNext.addEventListener('click', () => {
                showSlide(currentSlide + 1);
                startAutoPlay(); // Restart timer sequence on manual interaction
            });
        }

        // Interactive pagination click event listeners
        dots.forEach((dot, idx) => {
            dot.addEventListener('click', () => {
                showSlide(idx);
                startAutoPlay(); // Restart timer sequence on manual interaction
            });
        });

        // Initialize the slide loop on page load
        startAutoPlay();
    </script>
    <script>
        /**
     * Global Dashboard Manager Module
     * Coordinates active dynamic interface state tracking for MemPulse
     */
        const MemPulseDashboard = {
            // 1. Core State Updater Method
            updateAll(appPayload) {
                if (!appPayload) return;

                this.syncHeader(appPayload.user);
                this.syncSidebarProfile(appPayload.user);
                this.syncDatabaseMetrics(appPayload.stats);
                this.syncStreakMetrics(appPayload.stats);
            },

            // 2. Synchronize Top Fixed Navigation Header Elements
            syncHeader(user) {
                if (!user) return;
                const headerStreak = document.getElementById('header-streak-count');
                const headerImg = document.getElementById('header-profile-img');

                if (headerStreak) {
                    headerStreak.textContent = user.currentStreak + (user.currentStreak === 1 ? ' Day' : ' Days');
                }
                if (headerImg && user.profileImageUrl) {
                    headerImg.src = user.profileImageUrl;
                }
            },

            // 3. Synchronize Bottom Expanded/Collapsed Sidebar Components
            syncSidebarProfile(user) {
                if (!user) return;
                const sidebarName = document.getElementById('sidebar-profile-name');
                const sidebarRole = document.getElementById('sidebar-profile-role');
                const sidebarImg = document.getElementById('sidebar-profile-img');

                if (sidebarName) sidebarName.textContent = user.displayName;
                if (sidebarRole) sidebarRole.textContent = user.displayRole;
                if (sidebarImg && user.profileImageUrl) {
                    sidebarImg.src = user.profileImageUrl;
                }
            },

            // 4. Update the Spaced-Repetition Core Database Analytics Rows
            syncDatabaseMetrics(stats) {
                if (!stats) return;
                const cardsDue = document.getElementById('stat-cards-due');
                const mastered = document.getElementById('stat-mastered');
                const accuracy = document.getElementById('stat-accuracy');
                const tough = document.getElementById('stat-tough');

                if (cardsDue) cardsDue.textContent = stats.cardsDue ?? '--';
                if (mastered) mastered.textContent = stats.masteredCards ?? '--';
                if (accuracy) accuracy.textContent = (stats.lifetimeAccuracy !== undefined) ? `${stats.lifetimeAccuracy}%` : '--%';
                if (tough) tough.textContent = stats.toughCards ?? '--';
            },

            // 5. Update Streak and Performance Calculation Cards
            syncStreakMetrics(stats) {
                if (!stats) return;
                const currentStreak = document.getElementById('stat-current-streak');
                const bestStreak = document.getElementById('stat-best-streak');
                const streakAccuracy = document.getElementById('stat-streak-accuracy');

                if (currentStreak) currentStreak.textContent = stats.currentStreak ?? '--';
                if (bestStreak) bestStreak.textContent = stats.bestStreak ?? '--';
                if (streakAccuracy) streakAccuracy.textContent = stats.streakAccuracy ?? '--';
            }
        };

        // ─── INITIALIZATION / LIVE SQL DATABASE RUNNER ───
document.addEventListener("DOMContentLoaded", () => {
    // This section at the bottom of your file now instantly matches your dynamic PHP calculations!
    const incomingAppPayload = {
        user: {
            displayName: <?php echo json_encode($display_name); ?>,
            displayRole: "Active Learner",
            currentStreak: <?php echo intval($current_streak); ?>,
            profileImageUrl: "./assets/images/profile-picture.webp.jpg" 
        },
        stats: {
            cardsDue: <?php echo intval($cards_due); ?>,
            masteredCards: <?php echo intval($mastered_cards); ?>,
            lifetimeAccuracy: <?php echo floatval($avg_accuracy); ?>,
            toughCards: <?php echo intval($tough_cards); ?>,
            currentStreak: <?php echo intval($current_streak); ?>,
            bestStreak: <?php echo intval($best_streak); ?>,
            streakAccuracy: <?php echo floatval($streak_accuracy); ?>
        }
    };

    // Default the heatmap to the most recent study days
    document.addEventListener("DOMContentLoaded", () => {
    const heatmapScroller = document.getElementById("heatmap-scroll");

    if (heatmapScroller) {
        requestAnimationFrame(() => {
            heatmapScroller.scrollLeft =
                heatmapScroller.scrollWidth - heatmapScroller.clientWidth;
        });
    }
});

    document.querySelectorAll("#heatmap-scroll .heatmap-cell").forEach(cell => {
        cell.addEventListener("mouseenter", () => {
            const rect = cell.getBoundingClientRect();

            cell.style.setProperty("--tooltip-left", `${rect.left + rect.width / 2}px`);
            cell.style.setProperty("--tooltip-top", `${rect.top - 8}px`);
        });
    });

    // Run deep script synchronization across Header, Sidebar, and Stats Row
    MemPulseDashboard.updateAll(incomingAppPayload);
});

    </script>
</body>


</html>