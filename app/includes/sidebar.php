<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['settings_csrf'])) {
    $_SESSION['settings_csrf'] = bin2hex(random_bytes(32));
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/mempulse/app/');
}

// Ensure database connection is present
if (!isset($conn)) {
    @include_once __DIR__ . '/../../config/db.php';
}

$currentPage = $currentPage ?? '';
$user_id = $_SESSION['user_id'] ?? null;

// 1. Calculate total due cards globally for the sidebar badge
$sidebarDueCount = 0;
if ($user_id && isset($conn)) {
    $sidebarSql = "SELECT COUNT(*) FROM flashcards f 
                   JOIN decks d ON f.deck_id = d.id 
                   WHERE d.user_id = ? AND f.deleted_at IS NULL AND f.next_review_at <= NOW()";
    if ($sidebarStmt = mysqli_prepare($conn, $sidebarSql)) {
        mysqli_stmt_bind_param($sidebarStmt, "i", $user_id);
        mysqli_stmt_execute($sidebarStmt);
        mysqli_stmt_bind_result($sidebarStmt, $sidebarDueCount);
        mysqli_stmt_fetch($sidebarStmt);
        mysqli_stmt_close($sidebarStmt);
    }
}

// 1b. Current streak for the header chip (reuses the dashboard's value when it is already loaded)
$headerStreak = 0;
if (isset($streakData['currentStreak'])) {
    $headerStreak = (int) $streakData['currentStreak'];
} elseif ($user_id && isset($conn)) {
    try {
        $dashboardServiceFile = __DIR__ . '/../api/DashboardService.php';
        if (!class_exists('DashboardService') && is_file($dashboardServiceFile)) {
            require_once $dashboardServiceFile;
        }
        if (class_exists('DashboardService')) {
            $headerStreakData = DashboardService::getStreakAndHeatmapData($conn, (int) $user_id);
            $headerStreak = (int) ($headerStreakData['currentStreak'] ?? 0);
        }
    } catch (Throwable $e) {
        // streak is decorative: hide silently on failure
    }
}

// 2. Fetch User Display Name and Role
if (!isset($displayName) || !isset($displayRole)) {
    $displayName = 'Active Learner';
    $displayRole = 'User';
    
    if ($user_id && isset($conn)) {
        $user_sql = "SELECT first_name, last_name, role FROM users WHERE id = ? AND deleted_at IS NULL";
        if ($user_stmt = mysqli_prepare($conn, $user_sql)) {
            mysqli_stmt_bind_param($user_stmt, "i", $user_id);
            mysqli_stmt_execute($user_stmt);
            mysqli_stmt_bind_result($user_stmt, $first_name, $last_name, $role);
            mysqli_stmt_fetch($user_stmt);
            mysqli_stmt_close($user_stmt);

            $fullName = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
            if ($fullName !== '') {
                $displayName = $fullName;
            }
            if (!empty($role)) {
                $displayRole = ucfirst(strtolower($role));
            }
        }
    }
}

// 3. Saved appearance preference (light | dark | system)
$userTheme = 'system';
if ($user_id && isset($conn)) {
    try {
        if ($theme_stmt = mysqli_prepare($conn, "SELECT theme FROM user_settings WHERE user_id = ?")) {
            mysqli_stmt_bind_param($theme_stmt, "i", $user_id);
            mysqli_stmt_execute($theme_stmt);
            mysqli_stmt_bind_result($theme_stmt, $savedTheme);
            if (mysqli_stmt_fetch($theme_stmt) && in_array($savedTheme, ['light', 'dark', 'system'], true)) {
                $userTheme = $savedTheme;
            }
            mysqli_stmt_close($theme_stmt);
        }
    } catch (Throwable $e) {
        // theme column not added yet: fall back to "system"
    }
}

// 4. Fetch lightweight search data (Decks & Flashcards) for the Header Search Dropdown
$headerSearchDecks = [];
$headerSearchCards = [];
if ($user_id && isset($conn)) {
    try {
        $deckSearchSql = "SELECT d.id, d.title, COUNT(f.id) AS card_count
                          FROM decks d
                          LEFT JOIN flashcards f ON f.deck_id = d.id AND f.deleted_at IS NULL
                          WHERE d.user_id = ? AND d.deleted_at IS NULL
                          GROUP BY d.id, d.title
                          ORDER BY d.updated_at DESC
                          LIMIT 20";
        if ($dsStmt = mysqli_prepare($conn, $deckSearchSql)) {
            mysqli_stmt_bind_param($dsStmt, "i", $user_id);
            mysqli_stmt_execute($dsStmt);
            mysqli_stmt_bind_result($dsStmt, $dId, $dTitle, $dCount);
            while (mysqli_stmt_fetch($dsStmt)) {
                $headerSearchDecks[] = [
                    'id'    => (int) $dId,
                    'title' => $dTitle,
                    'count' => (int) $dCount,
                ];
            }
            mysqli_stmt_close($dsStmt);
        }

        $cardSearchSql = "SELECT f.id, f.deck_id, f.front, f.back, d.title AS deck_title
                          FROM flashcards f
                          JOIN decks d ON f.deck_id = d.id
                          WHERE d.user_id = ? AND d.deleted_at IS NULL AND f.deleted_at IS NULL
                          ORDER BY f.updated_at DESC
                          LIMIT 40";
        if ($csStmt = mysqli_prepare($conn, $cardSearchSql)) {
            mysqli_stmt_bind_param($csStmt, "i", $user_id);
            mysqli_stmt_execute($csStmt);
            mysqli_stmt_bind_result($csStmt, $cId, $cDeckId, $cFront, $cBack, $cDeckTitle);
            while (mysqli_stmt_fetch($csStmt)) {
                $headerSearchCards[] = [
                    'id'        => (int) $cId,
                    'deckId'    => (int) $cDeckId,
                    'front'     => mb_substr(trim(strip_tags((string) $cFront)), 0, 100),
                    'back'      => mb_substr(trim(strip_tags((string) $cBack)), 0, 100),
                    'deckTitle' => $cDeckTitle,
                ];
            }
            mysqli_stmt_close($csStmt);
        }
    } catch (Throwable $e) {
        // Gracefully ignore if tables are unavailable
    }
}
?>

<style>
    /* Keep the sidebar itself from creating horizontal scrolling. Tooltips are
       rendered under <body> with position: fixed, outside this clipping area. */
    #sidebar {
        box-sizing: border-box;
        /* Do not clip the desktop collapse toggle hanging off the right edge. */
        overflow-x: visible;
        overflow-y: visible;
    }

    #sidebar.collapsed .hide-collapsed {
        display: none !important;
    }

    #sidebar.collapsed .sidebar-row {
        box-sizing: border-box !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 0 0 44px !important;
        width: 44px !important;
        min-width: 44px !important;
        max-width: 44px !important;
        height: 44px !important;
        min-height: 44px !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    #sidebar.collapsed .sidebar-gap {
        gap: 0 !important;
    }

    /* Floating tooltip lives under document.body, so sidebar overflow and its
       scroll container cannot clip it. */
    #sidebar-floating-tooltip {
        position: fixed;
        z-index: 99999;
        max-width: min(280px, calc(100vw - 24px));
        padding: 6px 10px;
        border-radius: 8px;
        background: #1e293b;
        color: #fff;
        font-size: 12px;
        font-weight: 500;
        line-height: 1.4;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-50%);
        box-shadow: 0 4px 10px rgba(0, 0, 0, .18);
        transition: opacity .12s ease;
    }

    #sidebar-floating-tooltip.is-visible {
        opacity: 1;
        visibility: visible;
    }

    #sidebar-floating-tooltip::before {
        content: '';
        position: absolute;
        top: 50%;
        left: -8px;
        transform: translateY(-50%);
        border: 4px solid transparent;
        border-right-color: #1e293b;
    }

    #sidebar-floating-tooltip.tooltip-left::before {
        left: auto;
        right: -8px;
        border-right-color: transparent;
        border-left-color: #1e293b;
    }

    @keyframes memory-pulse {
        0%, 100% { background-color: rgba(56, 189, 248, 0.10); } 
        50% { background-color: rgba(56, 189, 248, 0); }
    }

    .memory-pulse-bg {
        animation: memory-pulse 2.5s ease-in-out infinite;
        border-radius: 50%;
        padding: 4px;
        margin-left: -4px; 
    }

    /* ---------------------------------------------------------------------
       Header-aware layout
       One header height drives the sidebar, drawer overlay, account menu,
       search dropdown and Settings modal, so nothing slides under the header.
       --------------------------------------------------------------------- */
    :root {
        --app-header-h: 4rem; /* matches h-16 on #app-header */
    }

    #app-header {
        height: var(--app-header-h);
    }

    /* Sidebar starts exactly where the header ends */
    #sidebar {
        top: var(--app-header-h);
        height: calc(100vh - var(--app-header-h));
        height: calc(100dvh - var(--app-header-h));
    }

    #mobile-sidebar-overlay {
        top: var(--app-header-h);
    }

    /* Search results open just below the header */
    #header-search-dropdown {
        top: calc(100% + (var(--app-header-h) - 2.5rem) / 2 + 0.5rem);
    }

    /* Settings modal sits below the header instead of being centred over it */
    #settings-dialog {
        margin: calc(var(--app-header-h) + 1rem) auto 1rem;
        max-height: calc(100vh - var(--app-header-h) - 2rem);
        max-height: calc(100dvh - var(--app-header-h) - 2rem);
    }

    #settings-dialog > div {
        max-height: inherit;
    }

    /* ---------------------------------------------------------------------
       Frosted glass: header and sidebar share the effect but look different.
       Header  = neutral, more see-through, stronger blur, floating shadow.
       Sidebar = primary-tinted, a bit more solid, softer blur, right hairline.
       Colours come from the theme tokens, so dark mode follows automatically.
       --------------------------------------------------------------------- */
    :root {
        --glass-surface: var(--color-surface-container-lowest, #ffffff);
        --glass-primary: var(--color-primary, #3525cd);
    }

    /* Soft ambient glows so the sidebar glass has something to blur */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: -1;
        pointer-events: none;
        background:
            radial-gradient(520px circle at 6% 22%, color-mix(in srgb, var(--glass-primary) 14%, transparent), transparent 70%),
            radial-gradient(480px circle at 4% 88%, rgba(20, 184, 166, 0.12), transparent 70%);
    }

    #app-header {
        background-color: color-mix(in srgb, var(--glass-surface) 70%, transparent);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        backdrop-filter: blur(16px) saturate(180%);
        border-bottom-color: color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
        box-shadow: 0 4px 24px -8px rgba(15, 23, 42, 0.12);
    }

    #sidebar {
        background-color: color-mix(in srgb, color-mix(in srgb, var(--glass-primary) 6%, var(--glass-surface)) 82%, transparent);
        background-image: linear-gradient(180deg, rgba(255, 255, 255, 0.35), rgba(255, 255, 255, 0));
        -webkit-backdrop-filter: blur(10px) saturate(140%);
        backdrop-filter: blur(10px) saturate(140%);
        border-right-color: color-mix(in srgb, var(--glass-primary) 14%, transparent);
        box-shadow: none;
    }

    @media (max-width: 767px) {
        /* Drawer sits over content, so keep it readable */
        #sidebar {
            background-color: color-mix(in srgb, color-mix(in srgb, var(--glass-primary) 6%, var(--glass-surface)) 94%, transparent);
        }
    }

    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        #app-header { background-color: var(--glass-surface); }
        #sidebar { background-color: color-mix(in srgb, var(--glass-primary) 6%, var(--glass-surface)); }
    }

    /* Sidebar footer links */
    .sidebar-subtle-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.875rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--color-on-surface-variant, #64748b);
        transition: background-color .15s ease, color .15s ease;
    }

    .sidebar-subtle-link:hover {
        background-color: color-mix(in srgb, var(--color-surface-container-low, #f1f5f9) 80%, transparent);
        color: var(--color-on-surface, #0f172a);
    }

    /* Streak chip (header, right side) */
    .header-streak {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        height: 2.25rem;
        padding: 0 0.75rem;
        border-radius: 0.75rem;
        font-size: 0.875rem;
        font-weight: 700;
        line-height: 1;
        color: color-mix(in srgb, #d97706 80%, var(--color-on-surface, #0f172a));
        background-color: color-mix(in srgb, #f59e0b 12%, transparent);
        border: 1px solid color-mix(in srgb, #f59e0b 30%, transparent);
        cursor: default;
        user-select: none;
    }

    .header-streak.is-empty {
        color: var(--color-on-surface-variant, #64748b);
        background-color: transparent;
        border-color: color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 40%, transparent);
    }

    /* ---------------------------------------------------------------------
       Sidebar account area (bottom)
       Expanded  : profile card, then Settings | Logout side by side
       Collapsed : three 44px icon buttons stacked (profile, Settings, Logout)
       Mobile    : profile card, then Settings and Logout stacked, 44px targets,
                   padded for the phone's home indicator
       --------------------------------------------------------------------- */
    .sidebar-account {
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));
        border-top: 1px solid color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
    }

    .sidebar-profile {
        min-height: 3rem;
        padding: 0.5rem 0.625rem;
        border: 1px solid color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
        background-color: color-mix(in srgb, var(--color-surface-container-low, #f1f5f9) 55%, transparent);
    }

    .sidebar-profile:hover {
        border-color: color-mix(in srgb, var(--glass-primary) 35%, transparent);
    }

    .sidebar-profile .profile-avatar {
        font-size: 34px;
        flex-shrink: 0;
        color: var(--color-primary, #3525cd);
        font-variation-settings: 'FILL' 1;
    }

    .sidebar-actions {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .sidebar-actions .sidebar-subtle-link {
        min-height: 2.75rem;
    }

    .sidebar-actions .sidebar-logout {
        color: var(--color-error, #dc2626);
    }

    .sidebar-actions .sidebar-logout:hover {
        background-color: color-mix(in srgb, var(--color-error, #dc2626) 10%, transparent);
        color: var(--color-error, #dc2626);
    }

    @media (min-width: 768px) {
        /* Expanded desktop: two equal buttons side by side */
        #sidebar:not(.collapsed) .sidebar-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        #sidebar:not(.collapsed) .sidebar-actions .sidebar-subtle-link {
            justify-content: center;
            min-height: 2.5rem;
            padding: 0.5rem 0.75rem;
            border: 1px solid color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
        }

        #sidebar:not(.collapsed) .sidebar-actions .sidebar-logout:hover {
            border-color: color-mix(in srgb, var(--color-error, #dc2626) 35%, transparent);
        }

        /* Collapsed rail: centred icon column */
        #sidebar.collapsed .sidebar-account {
            align-items: center;
            padding-left: 0;
            padding-right: 0;
        }

        #sidebar.collapsed .sidebar-profile {
            border-color: transparent;
            background-color: transparent;
        }

        #sidebar.collapsed .sidebar-actions .sidebar-subtle-link {
            min-height: 44px;
        }
    }

    /* Keep the header un-dimmed while the Settings modal is open */
    #settings-dialog::backdrop {
        clip-path: inset(4rem 0 0 0); /* = --app-header-h */
    }
</style>

<!-- TOP APPLICATION HEADER -->
<header id="app-header" class="fixed top-0 left-0 right-0 h-16 bg-surface-container-lowest border-b border-outline-variant/40 z-50 px-3 sm:px-6 flex items-center justify-between gap-2 sm:gap-6 shadow-[0_1px_8px_rgba(0,0,0,0.03)]">

    <!-- Left Section: Mobile Sidebar Button & MemPulse Logo -->
    <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
        <!-- Mobile Sidebar Trigger (Included in Header on Mobile Width) -->
        <button id="floating-mobile-toggle" type="button" aria-label="Toggle Sidebar" aria-expanded="false"
            class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors outline-none">
            <span id="mobile-toggle-icon" class="material-symbols-outlined text-[24px]">menu</span>
        </button>

        <!-- MemPulse Brand Logo -->
        <a href="<?php echo BASE_URL; ?>views/index.php" class="flex items-center gap-2.5 sm:gap-3 h-8 outline-none group">
            <span class="material-symbols-outlined text-[28px] text-primary memory-pulse-bg">neurology</span>
            <span class="logo-text font-headline-sm text-lg sm:text-xl font-bold tracking-tight text-on-surface transition-all duration-200">MemPulse</span>
        </a>
    </div>

    <!-- Center Section: Search (Ctrl+K) -->
    <div id="header-search-container" class="relative flex-1 max-w-md lg:max-w-lg mx-1 sm:mx-4">
        <div class="flex items-center gap-2 h-10 px-3 rounded-xl bg-surface-container-low border border-outline-variant/30 text-on-surface-variant focus-within:border-primary/60 focus-within:bg-surface-container-lowest focus-within:ring-2 focus-within:ring-primary/20 hover:border-primary/40 transition-all">
            <span class="material-symbols-outlined text-[20px] flex-shrink-0">search</span>
            <input id="header-search-input" type="text"
                placeholder="Search decks, cards, pages (Ctrl+K)"
                autocomplete="off"
                aria-label="Search decks, flashcards, or pages (Ctrl+K)"
                aria-keyshortcuts="Control+K"
                aria-autocomplete="list"
                aria-controls="header-search-dropdown"
                aria-expanded="false"
                class="w-full bg-transparent border-none outline-none font-body-sm text-sm text-on-surface placeholder:text-on-surface-variant min-w-0 truncate">
            <button type="button" id="header-search-clear" aria-label="Clear search"
                class="hidden p-0.5 rounded-md text-on-surface-variant hover:text-on-surface transition-colors flex-shrink-0">
                <span class="material-symbols-outlined text-[16px] block">close</span>
            </button>
        </div>

        <!-- Search Results Dropdown (same surface style as the account menu) -->
        <div id="header-search-dropdown" role="listbox"
            class="hidden absolute left-0 right-0 bg-surface-container-lowest border border-outline-variant/30 rounded-xl shadow-xl z-[100] overflow-hidden">
            <div id="header-search-results" class="overflow-y-auto py-1.5 max-h-[60vh]">
                <!-- Dynamically populated via JS -->
            </div>
        </div>
    </div>

    <!-- Right Section: Current streak -->
    <div class="flex items-center flex-shrink-0">
        <div class="header-streak <?= $headerStreak > 0 ? '' : 'is-empty' ?>" role="status"
             title="<?= $headerStreak > 0 ? $headerStreak . '-day streak' : 'No active streak. Review a card to start one.' ?>"
             aria-label="<?= $headerStreak > 0 ? 'Current streak: ' . $headerStreak . ($headerStreak === 1 ? ' day' : ' days') : 'No active streak' ?>">
            <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
            <span><?= $headerStreak ?><span class="hidden sm:inline font-semibold"> <?= $headerStreak === 1 ? 'day' : 'days' ?></span></span>
        </div>
    </div>

</header>

<!-- Mobile Sidebar Backdrop Overlay (Below Header) -->
<div id="mobile-sidebar-overlay" class="fixed inset-x-0 top-16 bottom-0 bg-black/50 z-30 hidden md:hidden transition-opacity opacity-0"></div>

<!-- MAIN SIDEBAR CONTAINER (top/height come from --app-header-h in the <style> block above) -->
<aside id="sidebar" class="fixed left-0 w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-40 flex flex-col transition-all duration-300 ease-in-out transform -translate-x-full md:translate-x-0 shadow-[0_1px_8px_rgba(0,0,0,0.03)]">
    
    <!-- Synchronous script to prevent FOUC -->
    <script>
        if (localStorage.getItem("sidebarCollapsed") === "true" && window.innerWidth >= 768) {
            document.getElementById("sidebar").classList.add("collapsed");
        }
    </script>

    <!-- Border Toggle Button (Desktop Only) -->
    <button id="sidebar-toggle" aria-label="Toggle Sidebar" class="hidden md:flex absolute -right-3.5 top-6 w-7 h-7 bg-surface-container-lowest border border-outline-variant/40 text-on-surface-variant rounded-full items-center justify-center hover:text-primary hover:border-primary shadow-sm transition-colors z-[60]">
        <span class="material-symbols-outlined text-[16px]">left_panel_close</span>
    </button>

    <!-- Scrollable Navigation -->
    <nav class="sidebar-navigation flex-1 min-h-0 overflow-y-auto px-4 py-4 flex flex-col gap-1.5" data-active-classes="bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm">
        
        <a data-tooltip="Home" aria-current="<?= $currentPage === 'home' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'home' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           href="<?php echo BASE_URL; ?>views/index.php">
            <div class="flex items-center gap-3 sidebar-gap">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'home' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">home</span>
                <span class="hide-collapsed transition-opacity duration-200">Home</span>
            </div>
        </a>

        <a data-tooltip="Decks" aria-current="<?= $currentPage === 'decks' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'decks' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           href="<?php echo BASE_URL; ?>views/decks.php">
            <div class="flex items-center gap-3 sidebar-gap">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'decks' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">style</span>
                <span class="hide-collapsed transition-opacity duration-200">Decks</span>
            </div>
        </a>

        <a data-tooltip="Review <?= $sidebarDueCount > 0 ? '('.$sidebarDueCount.')' : '' ?>" aria-current="<?= $currentPage === 'review' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'review' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           href="<?php echo BASE_URL; ?>views/review.php">
            <div class="flex items-center gap-3 sidebar-gap relative">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'review' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">pace</span>
                <span class="hide-collapsed transition-opacity duration-200">Review</span>
                <!-- Notification dot for collapsed view -->
                <?php if ($sidebarDueCount > 0): ?>
                    <span class="md:hidden hidden absolute top-0 right-0 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-surface-container-lowest" id="review-dot"></span>
                <?php endif; ?>
            </div>
            <?php if ($sidebarDueCount > 0): ?>
                <div class="hide-collapsed transition-opacity duration-200">
                    <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200/50 font-bold text-[11px] shadow-sm">
                        <?= $sidebarDueCount ?>
                    </span>
                </div>
            <?php endif; ?>
        </a>

        <a data-tooltip="Reports" aria-current="<?= $currentPage === 'reports' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'reports' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           href="<?php echo BASE_URL; ?>views/reports.php">
            <div class="flex items-center gap-3 sidebar-gap">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'reports' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">monitoring</span>
                <span class="hide-collapsed transition-opacity duration-200">Reports</span>
            </div>
        </a>


    </nav>

    <!-- Account area: pinned to the bottom of the sidebar -->
    <div class="sidebar-account">
        <button type="button" data-open-settings="account" data-tooltip="Account settings"
            class="sidebar-tooltip sidebar-row sidebar-subtle-link sidebar-profile w-full text-left"
            title="Account settings" aria-label="Open account settings">
            <span class="material-symbols-outlined profile-avatar">account_circle</span>
            <span class="hide-collapsed flex flex-col min-w-0">
                <span data-user-name class="text-sm font-semibold text-on-surface leading-tight truncate">
                    <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <span class="text-[11px] font-medium text-on-surface-variant leading-tight mt-0.5 truncate">
                    <?php echo htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </span>
        </button>

        <div class="sidebar-actions">
            <button type="button" data-open-settings="system" data-tooltip="Settings"
               class="sidebar-tooltip sidebar-row sidebar-subtle-link text-left">
                <span class="material-symbols-outlined text-[20px]">settings</span>
                <span class="hide-collapsed">Settings</span>
            </button>
            <button type="button" data-logout data-tooltip="Logout"
               class="sidebar-tooltip sidebar-row sidebar-subtle-link sidebar-logout text-left">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                <span class="hide-collapsed">Logout</span>
            </button>
        </div>
    </div>
</aside>

<!-- Logout Confirmation Dialog -->
<dialog id="logout-dialog" class="m-auto rounded-2xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs transition-all">
    <div class="p-6 flex flex-col gap-5">
        <h3 class="font-headline-sm text-lg font-bold text-on-surface tracking-tight">Log out?</h3>
        <p class="text-sm text-on-surface-variant font-medium leading-relaxed">
            Are you sure you want to exit? You will need to sign back in to access your flashcards.
        </p>
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
            <button type="button" id="logout-cancel" class="px-4 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-sm transition-colors">
                Cancel
            </button>
            <a href="/mempulse/authentication/logout.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-error hover:bg-rose-700 text-white font-semibold text-sm shadow-sm transition-colors">
                Log out
            </a>
        </div>
    </div>
</dialog>


<!-- Settings Modal -->
<dialog id="settings-dialog"
    aria-labelledby="settings-title"
    class="w-[calc(100%-2rem)] max-w-2xl p-0
           rounded-2xl bg-surface-container-lowest text-on-surface
           border border-outline-variant/40 shadow-2xl
           backdrop:bg-black/50 backdrop:backdrop-blur-sm"
    data-csrf="<?= htmlspecialchars($_SESSION['settings_csrf'], ENT_QUOTES, 'UTF-8') ?>"
    data-account-api="<?= htmlspecialchars(BASE_URL . 'api/account-actions.php', ENT_QUOTES, 'UTF-8') ?>"
    data-theme="<?= htmlspecialchars($userTheme, ENT_QUOTES, 'UTF-8') ?>">

    <div class="flex flex-col">

        <!-- Header -->
        <header class="flex items-start justify-between gap-4 p-6 border-b border-outline-variant/30">
            <div>
                <h2 id="settings-title"
                    class="text-xl font-bold tracking-tight">
                    Settings
                </h2>
                <p class="text-sm text-on-surface-variant mt-1">
                    Manage your account and learning preferences.
                </p>
            </div>

            <button type="button" id="settings-close"
                aria-label="Close settings"
                class="p-2 rounded-lg hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>

        <!-- Tabs -->
        <div class="px-6 pt-4">
            <div class="flex gap-2 border-b border-outline-variant/30 overflow-x-auto"
                 role="tablist" aria-label="Settings sections">

                <button type="button" id="settings-tab-system"
                    role="tab" aria-controls="settings-panel-system"
                    aria-selected="true" data-settings-tab="system"
                    class="px-4 py-3 text-sm font-semibold border-b-2 border-primary text-primary shrink-0">
                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">tune</span>
                    System
                </button>

                <button type="button" id="settings-tab-appearance"
                    role="tab" aria-controls="settings-panel-appearance"
                    aria-selected="false" data-settings-tab="appearance"
                    class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-on-surface-variant shrink-0">
                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">palette</span>
                    Appearance
                </button>

                <button type="button" id="settings-tab-account"
                    role="tab" aria-controls="settings-panel-account"
                    aria-selected="false" data-settings-tab="account"
                    class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-on-surface-variant shrink-0">
                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">person</span>
                    Profile
                </button>

                <button type="button" id="settings-tab-security"
                    role="tab" aria-controls="settings-panel-security"
                    aria-selected="false" data-settings-tab="security"
                    class="px-4 py-3 text-sm font-medium border-b-2 border-transparent text-on-surface-variant shrink-0">
                    <span class="material-symbols-outlined text-[18px] align-middle mr-1">lock</span>
                    Security
                </button>
            </div>
        </div>

        <!-- Scrollable Content -->
        <div class="overflow-y-auto p-6 flex-1">

            <!-- System Settings -->
            <section id="settings-panel-system"
                role="tabpanel" aria-labelledby="settings-tab-system"
                class="space-y-5">

                <div>
                    <h3 class="font-semibold text-base">Learning preferences</h3>
                    <p class="text-sm text-on-surface-variant mt-1">
                        Configure how MemPulse schedules your flashcard reviews.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">Remember multiplier</span>
                        <input id="remember-multiplier" name="remember_multiplier" type="number" step="0.01" min="0.01" max="10" required
                            value="2.00"
                            class="w-full rounded-xl border border-outline-variant/50
                                   bg-surface-container-low px-3 py-2.5 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <span class="block text-xs text-on-surface-variant">
                            Default: 2.00
                        </span>
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">Forgot multiplier</span>
                        <input id="forgot-multiplier" name="forgot_multiplier" type="number" step="0.01" min="0.01" max="10" required
                            value="0.50"
                            class="w-full rounded-xl border border-outline-variant/50
                                   bg-surface-container-low px-3 py-2.5 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <span class="block text-xs text-on-surface-variant">
                            Default: 0.50
                        </span>
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">Initial interval (days)</span>
                        <input id="initial-interval" name="initial_interval_days" type="number" min="1" max="36500" step="1" value="1" required
                            class="w-full rounded-xl border border-outline-variant/50
                                   bg-surface-container-low px-3 py-2.5 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">Maximum interval (days)</span>
                        <input id="maximum-interval" name="maximum_interval_days" type="number" min="1" max="36500" step="1" value="365" required
                            class="w-full rounded-xl border border-outline-variant/50
                                   bg-surface-container-low px-3 py-2.5 text-sm
                                   focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </label>

                </div>
            </section>

            <!-- Appearance -->
            <section id="settings-panel-appearance"
                role="tabpanel" aria-labelledby="settings-tab-appearance"
                class="hidden space-y-5">

                <div>
                    <h3 class="font-semibold text-base">Appearance</h3>
                    <p class="text-sm text-on-surface-variant mt-1">
                        Choose how MemPulse looks. Your choice is saved to your account.
                    </p>
                </div>

                <fieldset class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <legend class="sr-only">Theme</legend>
                    <label class="cursor-pointer">
                        <input type="radio" name="appearance" value="light" class="peer sr-only">
                        <span class="flex flex-col items-center gap-2 p-4 rounded-xl border border-outline-variant/50
                                     bg-surface-container-low text-sm font-medium transition-colors
                                     peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/40
                                     peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40">
                            <span class="material-symbols-outlined text-[28px]">light_mode</span>
                            Light
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="appearance" value="dark" class="peer sr-only">
                        <span class="flex flex-col items-center gap-2 p-4 rounded-xl border border-outline-variant/50
                                     bg-surface-container-low text-sm font-medium transition-colors
                                     peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/40
                                     peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40">
                            <span class="material-symbols-outlined text-[28px]">dark_mode</span>
                            Dark
                        </span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="appearance" value="system" class="peer sr-only">
                        <span class="flex flex-col items-center gap-2 p-4 rounded-xl border border-outline-variant/50
                                     bg-surface-container-low text-sm font-medium transition-colors
                                     peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary/40
                                     peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40">
                            <span class="material-symbols-outlined text-[28px]">brightness_auto</span>
                            Use device setting
                        </span>
                    </label>
                </fieldset>

                <p id="appearance-status" class="text-sm" role="status" aria-live="polite"></p>
            </section>

            <!-- Profile -->
            <section id="settings-panel-account"
                role="tabpanel" aria-labelledby="settings-tab-account"
                class="hidden space-y-5">

                <div>
                    <h3 class="font-semibold text-base">Profile</h3>
                    <p class="text-sm text-on-surface-variant mt-1">
                        Update your name and email address.
                    </p>
                </div>

                <form id="profile-form" class="space-y-4" novalidate>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="block space-y-2">
                            <span class="text-sm font-medium">First name</span>
                            <input id="profile-first-name" type="text" maxlength="100" required autocomplete="given-name"
                                class="w-full rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-medium">Last name</span>
                            <input id="profile-last-name" type="text" maxlength="100" required autocomplete="family-name"
                                class="w-full rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                        </label>

                        <label class="block space-y-2 sm:col-span-2">
                            <span class="text-sm font-medium">Email address</span>
                            <input id="profile-email" type="email" maxlength="255" required autocomplete="email"
                                class="w-full rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                        </label>

                        <label id="profile-password-wrap" class="hidden block space-y-2 sm:col-span-2">
                            <span class="text-sm font-medium">Current password</span>
                            <input id="profile-password" type="password" autocomplete="current-password"
                                class="w-full rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <span class="block text-xs text-on-surface-variant">
                                Enter your password to confirm the new email address.
                            </span>
                        </label>
                    </div>

                    <p id="profile-meta" class="text-xs text-on-surface-variant"></p>

                    <div class="flex items-center justify-between gap-3">
                        <p id="profile-status" class="text-sm" role="status" aria-live="polite"></p>
                        <button type="submit" id="profile-save" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary text-on-primary hover:opacity-90 disabled:opacity-60">Save profile</button>
                    </div>
                </form>
            </section>

            <!-- Security -->
            <section id="settings-panel-security"
                role="tabpanel" aria-labelledby="settings-tab-security"
                class="hidden space-y-5">

                <div>
                    <h3 class="font-semibold text-base">Password and security</h3>
                    <p class="text-sm text-on-surface-variant mt-1">
                        Change your password. You stay signed in on this device.
                    </p>
                </div>

                <form id="password-form" class="space-y-4" novalidate>
                    <label class="block space-y-2">
                        <span class="text-sm font-medium">Current password</span>
                        <input id="pw-current" type="password" required autocomplete="current-password"
                            class="w-full rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="block space-y-2">
                            <span class="text-sm font-medium">New password</span>
                            <input id="pw-new" type="password" required minlength="8" autocomplete="new-password"
                                class="w-full rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <span class="block text-xs text-on-surface-variant">At least 8 characters.</span>
                        </label>

                        <label class="block space-y-2">
                            <span class="text-sm font-medium">Confirm new password</span>
                            <input id="pw-confirm" type="password" required autocomplete="new-password"
                                class="w-full rounded-xl border border-outline-variant/50 bg-surface-container-low px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                        </label>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm text-on-surface-variant cursor-pointer">
                        <input id="pw-show" type="checkbox" class="rounded border-outline-variant/50">
                        Show passwords
                    </label>

                    <p id="pw-meta" class="text-xs text-on-surface-variant"></p>

                    <div class="flex items-center justify-between gap-3">
                        <p id="pw-status" class="text-sm" role="status" aria-live="polite"></p>
                        <button type="submit" id="pw-save" class="px-4 py-2.5 rounded-xl text-sm font-semibold bg-primary text-on-primary hover:opacity-90 disabled:opacity-60">Change password</button>
                    </div>
                </form>
            </section>
        </div>

        
        <p id="settings-status" class="px-6 pb-2 text-sm"
            role="status" aria-live="polite"></p>

        <!-- Footer -->
        <footer class="flex justify-end gap-3 p-4 sm:px-6 border-t border-outline-variant/30">
            <button type="button" id="settings-cancel"
                class="px-4 py-2.5 rounded-xl text-sm font-semibold
                       bg-surface-container-low hover:bg-surface-container-high">
                Close
            </button>

            <button type="button" id="settings-save"
                class="px-4 py-2.5 rounded-xl text-sm font-semibold
                    bg-primary text-on-primary hover:opacity-90">
                Save changes
            </button>
        </footer>

    </div>
</dialog>

<script>
    window.MemPulseHeaderSearchData = {
        baseUrl: <?= json_encode(BASE_URL) ?>,
        decks: <?= json_encode($headerSearchDecks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
        cards: <?= json_encode($headerSearchCards, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
    };

    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.querySelector("#sidebar");
        const mainContent = document.querySelector("main");
        const toggleBtn = document.querySelector("#sidebar-toggle");
        
        // Mobile elements (Trigger inside Header)
        const floatingToggleBtn = document.querySelector("#floating-mobile-toggle");
        const mobileToggleIcon = document.querySelector("#mobile-toggle-icon");
        const mobileOverlay = document.querySelector("#mobile-sidebar-overlay");
        const reviewDot = document.querySelector("#review-dot");

        if (!sidebar) return;

        // Body-level fixed tooltip for collapsed desktop sidebar
        let floatingTooltip = document.getElementById("sidebar-floating-tooltip");
        if (!floatingTooltip) {
            floatingTooltip = document.createElement("div");
            floatingTooltip.id = "sidebar-floating-tooltip";
            floatingTooltip.setAttribute("role", "tooltip");
            document.body.appendChild(floatingTooltip);
        }

        let tooltipTarget = null;
        function hideSidebarTooltip() {
            floatingTooltip.classList.remove("is-visible");
            tooltipTarget = null;
        }
        function showSidebarTooltip(target) {
            const label = target.getAttribute("data-tooltip") || target.getAttribute("aria-label");
            if (!label || !sidebar.classList.contains("collapsed") || window.innerWidth < 768) {
                hideSidebarTooltip();
                return;
            }
            tooltipTarget = target;
            floatingTooltip.textContent = label;
            floatingTooltip.style.left = "0px";
            floatingTooltip.style.top = "0px";
            floatingTooltip.classList.add("is-visible");
            const rect = target.getBoundingClientRect();
            const tipRect = floatingTooltip.getBoundingClientRect();
            const gap = 12;
            let left = rect.right + gap;
            if (left + tipRect.width > window.innerWidth - 8) {
                left = Math.max(8, rect.left - tipRect.width - gap);
                floatingTooltip.classList.add("tooltip-left");
            } else {
                floatingTooltip.classList.remove("tooltip-left");
            }
            const top = Math.min(window.innerHeight - 8, Math.max(8, rect.top + rect.height / 2));
            floatingTooltip.style.left = `${left}px`;
            floatingTooltip.style.top = `${top}px`;
        }

        sidebar.querySelectorAll(".sidebar-tooltip").forEach((item) => {
            item.addEventListener("mouseenter", () => showSidebarTooltip(item));
            item.addEventListener("mouseleave", hideSidebarTooltip);
            item.addEventListener("focus", () => showSidebarTooltip(item));
            item.addEventListener("blur", hideSidebarTooltip);
        });
        sidebar.addEventListener("scroll", hideSidebarTooltip, true);
        window.addEventListener("resize", hideSidebarTooltip);
        window.addEventListener("scroll", () => {
            if (tooltipTarget) showSidebarTooltip(tooltipTarget);
        }, true);

        const toggleIcon = toggleBtn ? toggleBtn.querySelector(".material-symbols-outlined") : null;

        // Apply visual collapsed state on desktop
        const sidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
        if (sidebarCollapsed && window.innerWidth >= 768) {
            sidebar.classList.add("collapsed");
            if (mainContent) mainContent.classList.add("collapsed");
            if (toggleIcon) toggleIcon.textContent = "left_panel_open";
            if (reviewDot) reviewDot.classList.remove("hidden");
        }

        // Desktop Border Toggle Interaction
        if (toggleBtn) {
            toggleBtn.addEventListener("click", () => {
                const isCollapsed = sidebar.classList.toggle("collapsed");
                
                document.documentElement.classList.toggle(
                    "sidebar-collapsed",
                    isCollapsed
                );
                
                hideSidebarTooltip();
                if (mainContent) {
                    mainContent.classList.toggle("collapsed", isCollapsed);
                }
                if (toggleIcon) {
                    toggleIcon.textContent = isCollapsed ? "left_panel_open" : "left_panel_close";
                }
                if (reviewDot) {
                    isCollapsed ? reviewDot.classList.remove("hidden") : reviewDot.classList.add("hidden");
                }
                localStorage.setItem("sidebarCollapsed", isCollapsed);
            });
        }

        // Mobile Sidebar Drawer Toggle (Inside Header)
        function isMobileSidebarOpen() {
            return !sidebar.classList.contains("-translate-x-full");
        }

        function openMobileSidebar() {
            sidebar.classList.remove("-translate-x-full", "collapsed");
            mobileOverlay.classList.remove("hidden");
            if (floatingToggleBtn) floatingToggleBtn.setAttribute("aria-expanded", "true");
            if (mobileToggleIcon) mobileToggleIcon.textContent = "close";
            setTimeout(() => mobileOverlay.classList.remove("opacity-0"), 10);
        }

        function closeMobileSidebar() {
            sidebar.classList.add("-translate-x-full");
            mobileOverlay.classList.add("opacity-0");
            if (floatingToggleBtn) floatingToggleBtn.setAttribute("aria-expanded", "false");
            if (mobileToggleIcon) mobileToggleIcon.textContent = "menu";
            setTimeout(() => mobileOverlay.classList.add("hidden"), 300);
        }

        if (floatingToggleBtn) {
            floatingToggleBtn.addEventListener("click", () => {
                if (isMobileSidebarOpen()) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            });
        }
        if (mobileOverlay) mobileOverlay.addEventListener("click", closeMobileSidebar);

        // --- Header Search Dropdown Logic ---
        const searchContainer = document.getElementById('header-search-container');
        const searchInput = document.getElementById('header-search-input');
        const searchClear = document.getElementById('header-search-clear');
        const searchDropdown = document.getElementById('header-search-dropdown');
        const searchResults = document.getElementById('header-search-results');

        const searchData = window.MemPulseHeaderSearchData || { baseUrl: '/mempulse/app/', decks: [], cards: [] };
        const baseUrl = searchData.baseUrl;

        const quickPages = [
            { title: 'Home Dashboard', subtitle: 'Overview & streak metrics', icon: 'home', href: `${baseUrl}views/index.php` },
            { title: 'Decks', subtitle: 'Manage your flashcard collections', icon: 'style', href: `${baseUrl}views/decks.php` },
            { title: 'Create Flashcards', subtitle: 'Add new cards to your decks', icon: 'edit_note', href: `${baseUrl}views/decks.php?add_card=1` },
            { title: 'Review', subtitle: 'Practice due flashcards now', icon: 'pace', href: `${baseUrl}views/review.php` },
            { title: 'Reports', subtitle: 'View accuracy & learning analytics', icon: 'monitoring', href: `${baseUrl}views/reports.php` },
            { title: 'Memory Science', subtitle: 'How spaced repetition works', icon: 'psychology', href: `${baseUrl}views/science.php` },
            { title: 'Account Settings', subtitle: 'Profile, email & security', icon: 'manage_accounts', action: 'account' },
            { title: 'System Settings', subtitle: 'SRS intervals & multipliers', icon: 'settings', action: 'system' }
        ];

        let activeSearchIndex = -1;

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str ?? '';
            return div.innerHTML;
        }

        function closeSearchDropdown() {
            if (!searchDropdown || !searchInput) return;
            searchDropdown.classList.add('hidden');
            searchInput.setAttribute('aria-expanded', 'false');
            activeSearchIndex = -1;
        }

        function openSearchDropdown() {
            if (!searchDropdown || !searchInput) return;
            renderSearchResults(searchInput.value.trim());
            searchDropdown.classList.remove('hidden');
            searchInput.setAttribute('aria-expanded', 'true');
        }

        function resultRow({ href, action, icon, title, meta }) {
            return `
                <a href="${href ? escapeHtml(href) : '#'}" role="option"
                   ${action ? `data-search-settings="${escapeHtml(action)}"` : ''}
                   class="search-result-item flex items-center gap-3 px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined text-[18px] flex-shrink-0">${escapeHtml(icon)}</span>
                    <span class="flex-1 min-w-0 truncate">${escapeHtml(title)}</span>
                    ${meta ? `<span class="text-xs font-normal text-on-surface-variant/80 truncate max-w-[40%] flex-shrink-0">${escapeHtml(meta)}</span>` : ''}
                </a>
            `;
        }

        function resultGroup(label, rows) {
            if (rows.length === 0) return '';
            return `
                <div>
                    <p class="px-4 pt-1.5 pb-1 text-xs font-semibold text-on-surface-variant/70">${escapeHtml(label)}</p>
                    ${rows.join('')}
                </div>
            `;
        }

        function renderSearchResults(query) {
            if (!searchResults) return;
            const q = query.toLowerCase();
            activeSearchIndex = -1;

            const matchedPages = q === ''
                ? quickPages.slice(0, 5)
                : quickPages.filter(p => p.title.toLowerCase().includes(q) || p.subtitle.toLowerCase().includes(q));

            const matchedDecks = q === ''
                ? (searchData.decks || []).slice(0, 4)
                : (searchData.decks || []).filter(d => (d.title || '').toLowerCase().includes(q)).slice(0, 6);

            const matchedCards = q === ''
                ? []
                : (searchData.cards || []).filter(c =>
                    (c.front || '').toLowerCase().includes(q) ||
                    (c.back || '').toLowerCase().includes(q) ||
                    (c.deckTitle || '').toLowerCase().includes(q)
                ).slice(0, 6);

            const groups = [
                resultGroup(q === '' ? 'Quick links' : 'Pages', matchedPages.map(p => resultRow({
                    href: p.href, action: p.action, icon: p.icon, title: p.title
                }))),
                resultGroup('Decks', matchedDecks.map(d => resultRow({
                    href: `${baseUrl}views/deck.php?deck_id=${d.id}&id=${d.id}`,
                    icon: 'style',
                    title: d.title,
                    meta: `${d.count} ${d.count === 1 ? 'card' : 'cards'}`
                }))),
                resultGroup('Flashcards', matchedCards.map(c => resultRow({
                    href: `${baseUrl}views/deck.php?deck_id=${c.deckId}&id=${c.deckId}`,
                    icon: 'quiz',
                    title: c.front,
                    meta: c.deckTitle
                })))
            ].filter(Boolean);

            if (groups.length === 0) {
                searchResults.innerHTML = `
                    <p class="px-4 py-6 text-center text-sm text-on-surface-variant">
                        No results for "${escapeHtml(query)}"
                    </p>
                `;
                return;
            }

            searchResults.innerHTML = groups.join('<div class="h-px bg-outline-variant/20 my-1"></div>');

            searchResults.querySelectorAll('[data-search-settings]').forEach(el => {
                el.addEventListener('click', (ev) => {
                    ev.preventDefault();
                    const tab = el.getAttribute('data-search-settings');
                    closeSearchDropdown();
                    openSettingsModal(tab);
                });
            });
        }

        function updateSearchHighlight(items) {
            items.forEach((el, idx) => {
                if (idx === activeSearchIndex) {
                    el.classList.add('bg-surface-container-low');
                    el.scrollIntoView({ block: 'nearest' });
                } else {
                    el.classList.remove('bg-surface-container-low');
                }
            });
        }

        if (searchInput) {
            searchInput.addEventListener('focus', openSearchDropdown);
            searchInput.addEventListener('input', () => {
                const val = searchInput.value;
                searchClear?.classList.toggle('hidden', val.length === 0);
                openSearchDropdown();
            });

            searchInput.addEventListener('keydown', (e) => {
                if (!searchDropdown || searchDropdown.classList.contains('hidden')) return;
                const items = Array.from(searchResults.querySelectorAll('.search-result-item'));
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (items.length > 0) {
                        activeSearchIndex = (activeSearchIndex + 1) % items.length;
                        updateSearchHighlight(items);
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (items.length > 0) {
                        activeSearchIndex = (activeSearchIndex - 1 + items.length) % items.length;
                        updateSearchHighlight(items);
                    }
                } else if (e.key === 'Enter' && activeSearchIndex >= 0 && items[activeSearchIndex]) {
                    e.preventDefault();
                    items[activeSearchIndex].click();
                } else if (e.key === 'Escape') {
                    closeSearchDropdown();
                    searchInput.blur();
                }
            });
        }

        if (searchClear) {
            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchClear.classList.add('hidden');
                searchInput.focus();
                renderSearchResults('');
            });
        }

        // Global CTRL+K / CMD+K shortcut to focus header search
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                searchInput?.focus();
                searchInput?.select();
            }
        });

        document.addEventListener('click', (e) => {
            if (searchContainer && !searchContainer.contains(e.target)) {
                closeSearchDropdown();
            }
        });

        // --- Settings Modal Logic ---
        const settingsDialog = document.getElementById('settings-dialog');
        const settingsClose = document.getElementById('settings-close');
        const settingsCancel = document.getElementById('settings-cancel');

        const settingsTabs = document.querySelectorAll('[data-settings-tab]');
        const settingsOpeners = document.querySelectorAll('[data-open-settings]');

        function switchSettingsTab(tabName) {
            document.getElementById('settings-save')?.classList.toggle('hidden', tabName !== 'system');
            settingsTabs.forEach((tab) => {
                const active = tab.dataset.settingsTab === tabName;

                tab.setAttribute('aria-selected', String(active));
                tab.classList.toggle('border-primary', active);
                tab.classList.toggle('text-primary', active);
                tab.classList.toggle('font-semibold', active);
                tab.classList.toggle('border-transparent', !active);
                tab.classList.toggle('text-on-surface-variant', !active);
                tab.classList.toggle('font-medium', !active);

                const panel = document.getElementById(
                    `settings-panel-${tab.dataset.settingsTab}`
                );

                if (panel) {
                    panel.classList.toggle('hidden', !active);
                }
            });
        }

        const settingsSave = document.getElementById('settings-save');
        const settingsStatus = document.getElementById('settings-status');

        const settingsFields = {
            remember_multiplier: document.getElementById('remember-multiplier'),
            forgot_multiplier: document.getElementById('forgot-multiplier'),
            initial_interval_days: document.getElementById('initial-interval'),
            maximum_interval_days: document.getElementById('maximum-interval')
        };

        const settingsApi = <?= json_encode(BASE_URL . 'api/settings-actions.php') ?>;

        function showSettingsStatus(message, isError = false) {
            if (!settingsStatus) return;

            settingsStatus.textContent = message;
            settingsStatus.classList.toggle('text-error', isError);
            settingsStatus.classList.toggle('text-primary', !isError);
        }

        function displaySettings(settings) {
            Object.entries(settingsFields).forEach(([key, input]) => {
                if (input) input.value = settings[key];
            });
        }

        async function loadSettings() {
            showSettingsStatus('Loading settings...');

            try {
                const response = await fetch(settingsApi, {
                    credentials: 'same-origin'
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Could not load settings.');
                }

                displaySettings(result.settings);
                showSettingsStatus('');
            } catch (error) {
                showSettingsStatus(error.message, true);
            }
        }

        async function saveSettings() {
            const payload = {};

            for (const [key, input] of Object.entries(settingsFields)) {
                if (!input || !input.checkValidity()) {
                    input?.reportValidity();
                    return;
                }

                payload[key] = input.value;
            }

            const initialValue = Number(payload.initial_interval_days);
            const maximumValue = Number(payload.maximum_interval_days);
            if (maximumValue < initialValue) {
                settingsFields.maximum_interval_days.setCustomValidity('Maximum interval must be at least the initial interval.');
                settingsFields.maximum_interval_days.reportValidity();
                settingsFields.maximum_interval_days.setCustomValidity('');
                return;
            }

            settingsSave.disabled = true;
            showSettingsStatus('Saving settings...');

            try {
                const response = await fetch(settingsApi, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': settingsDialog.dataset.csrf
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Could not save settings.');
                }

                displaySettings(result.settings);
                showSettingsStatus(result.message);
            } catch (error) {
                showSettingsStatus(error.message, true);
            } finally {
                settingsSave.disabled = false;
            }
        }

        settingsSave?.addEventListener('click', saveSettings);

        function openSettingsModal(tabName) {
            switchSettingsTab(tabName);

            if (settingsDialog) {
                if (!settingsDialog.open) {
                    settingsDialog.showModal();
                }
                loadSettings();
            }
        }

        settingsOpeners.forEach((button) => {
            button.addEventListener('click', () => {
                openSettingsModal(button.dataset.openSettings);
            });
        });

        settingsTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                switchSettingsTab(tab.dataset.settingsTab);
            });
        });

        function closeSettings() {
            if (settingsDialog?.open) {
                settingsDialog.close();
            }
        }

        settingsClose?.addEventListener('click', closeSettings);
        settingsCancel?.addEventListener('click', closeSettings);

        settingsDialog?.addEventListener('click', (event) => {
            if (event.target === settingsDialog) {
                closeSettings();
            }
        });

        // Logout Dialog Logic
        const logoutBtns = document.querySelectorAll('[data-logout]');
        const logoutDialog = document.getElementById('logout-dialog');
        const logoutCancel = document.getElementById('logout-cancel');

        if (logoutBtns.length && logoutDialog) {
            logoutBtns.forEach((btn) => btn.addEventListener('click', () => {
                if (window.innerWidth < 768) closeMobileSidebar();
                logoutDialog.showModal();
            }));

            if (logoutCancel) {
                logoutCancel.addEventListener('click', () => logoutDialog.close());
            }

            logoutDialog.addEventListener('click', (e) => {
                const rect = logoutDialog.getBoundingClientRect();
                if (e.clientX < rect.left || e.clientX > rect.right || e.clientY < rect.top || e.clientY > rect.bottom) {
                    logoutDialog.close();
                }
            });
        }
    });
</script>

<script src="<?php echo BASE_URL; ?>assets/js/account-settings.js" defer></script>