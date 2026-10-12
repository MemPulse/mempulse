<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!defined('BASE_URL')) {
    define('BASE_URL', '/mempulse/app/');
}

// Ensure database connection is present if not already included
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

// 2. Fetch User Display Name (Moved from Header)
if (!isset($displayName)) {
    $displayName = 'Active Learner';
    if ($user_id && isset($conn)) {
        $user_sql = "SELECT first_name, last_name FROM users WHERE id = ? AND deleted_at IS NULL";
        if ($user_stmt = mysqli_prepare($conn, $user_sql)) {
            mysqli_stmt_bind_param($user_stmt, "i", $user_id);
            mysqli_stmt_execute($user_stmt);
            mysqli_stmt_bind_result($user_stmt, $first_name, $last_name);
            mysqli_stmt_fetch($user_stmt);
            mysqli_stmt_close($user_stmt);

            $fullName = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
            if ($fullName !== '') {
                $displayName = $fullName;
            }
        }
    }
}

// 3. Fetch Active Streak (Moved from Header)
if (!isset($current_streak)) {
    $current_streak = 0;
    if ($user_id && isset($conn)) {
        $session_sql = "SELECT DATE(started_at) AS session_date
                        FROM review_sessions
                        WHERE user_id = ?
                          AND ended_at IS NOT NULL
                          AND total_reviewed > 0
                        GROUP BY DATE(started_at)
                        ORDER BY session_date DESC";

        if ($session_stmt = mysqli_prepare($conn, $session_sql)) {
            mysqli_stmt_bind_param($session_stmt, "i", $user_id);
            mysqli_stmt_execute($session_stmt);
            $result = mysqli_stmt_get_result($session_stmt);

            $activity_dates = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $activity_dates[] = $row['session_date'];
            }
            mysqli_stmt_close($session_stmt);

            if (!empty($activity_dates)) {
                $today = date('Y-m-d');
                $yesterday = date('Y-m-d', strtotime('-1 day'));

                if ($activity_dates[0] === $today || $activity_dates[0] === $yesterday) {
                    $check_date = new DateTime($activity_dates[0]);
                    foreach ($activity_dates as $date) {
                        if ($date === $check_date->format('Y-m-d')) {
                            $current_streak++;
                            $check_date->modify('-1 day');
                        } else {
                            break;
                        }
                    }
                }
            }
        }
    }
}
?>

<style>
/* Custom Tooltip Logic - Only active when sidebar is collapsed */
#sidebar.collapsed .sidebar-tooltip {
    position: relative;
    overflow: visible;
}

#sidebar.collapsed .sidebar-tooltip::after {
    content: attr(data-tooltip);
    position: absolute;
    left: calc(100% + 14px);
    top: 50%;
    transform: translateY(-50%);
    background: #1e293b;
    color: #ffffff;
    font-size: 12px;
    font-weight: 500;
    padding: 6px 10px;
    border-radius: 8px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
    z-index: 9999;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

#sidebar.collapsed .sidebar-tooltip::before {
    content: '';
    position: absolute;
    left: calc(100% + 6px);
    top: 50%;
    transform: translateY(-50%);
    border: 4px solid transparent;
    border-right-color: #1e293b;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    z-index: 10000;
}

#sidebar.collapsed .sidebar-tooltip:hover::after {
    opacity: 1;
    transform: translateY(-50%) translateX(4px);
}
#sidebar.collapsed .sidebar-tooltip:hover::before {
    opacity: 1;
    transform: translateY(-50%) translateX(4px);
}

/* Collapsed visibility utilities */
#sidebar.collapsed .hide-collapsed {
    display: none !important;
}
#sidebar.collapsed .sidebar-row {
    justify-content: center;
    padding-left: 0;
    padding-right: 0;
}
#sidebar.collapsed .sidebar-gap {
    gap: 0;
}
</style>

<!-- Floating Mobile Menu Button (Since Header is Removed) -->
<button id="floating-mobile-toggle" type="button" class="md:hidden fixed top-4 left-4 z-40 p-2.5 rounded-xl bg-surface-container-lowest shadow-md border border-outline-variant/30 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors">
    <span class="material-symbols-outlined text-[24px]">menu</span>
</button>

<!-- Mobile Overlay -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>

<aside id="sidebar" class="fixed left-0 top-0 h-full overflow-y-auto w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-50 flex flex-col justify-between py-6 px-4 shadow-[0_1px_8px_rgba(0,0,0,0.03)] transition-all duration-300 ease-in-out transform -translate-x-full md:translate-x-0">
    
    <!-- Synchronous script to prevent FOUC when reloading page -->
    <script>
        if (localStorage.getItem("sidebarCollapsed") === "true" && window.innerWidth >= 768) {
            document.getElementById("sidebar").classList.add("collapsed");
        }
    </script>

    <div id="sidebar-header" class="flex items-center justify-between gap-3 px-3 transition-all duration-300">
        <div class="flex items-center gap-3 flex-shrink-0">
            <span class="logo-text font-headline-sm text-headline-sm font-semibold tracking-tight text-on-surface transition-all duration-200">MemPulse</span>
        </div>

        <button id="sidebar-toggle" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors flex-shrink-0">
            <span class="material-symbols-outlined text-[24px]">left_panel_close</span>
        </button>
    </div>

    <!-- Integrated Header Elements (Profile, Streak, Search) -->
    <div class="flex flex-col gap-2 mt-6 pb-4 border-b border-outline-variant/30 transition-all">
        
        <!-- User Profile -->
        <a data-tooltip="Profile" href="<?php echo BASE_URL; ?>views/account.php" class="sidebar-tooltip sidebar-row flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-surface-container-low transition-colors group">
            <img alt="User Avatar" class="w-8 h-8 rounded-full object-cover ring-2 ring-primary-fixed flex-shrink-0" src="../assets/images/profile-picture.webp.jpg">
            <div class="flex flex-col min-w-0 hide-collapsed">
                <span class="font-label-sm font-semibold text-on-surface leading-tight truncate">
                    <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <span class="text-[10px] text-on-surface-variant font-medium leading-none tracking-wide mt-1">
                    User Account
                </span>
            </div>
        </a>

        <!-- Streak Badge -->
        <div data-tooltip="Streak: <?php echo $current_streak; ?> Days" class="sidebar-tooltip sidebar-row flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-surface-container-low transition-colors group">
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-[#fffbeb] border border-amber-200/80 shadow-sm flex-shrink-0">
                <span class="material-symbols-outlined text-amber-500 text-[18px]">local_fire_department</span>
            </div>
            <span class="font-label-sm font-semibold tracking-wide text-amber-700 hide-collapsed">
                <?php echo $current_streak; ?> Day<?php echo $current_streak == 1 ? '' : 's'; ?>
            </span>
        </div>

        <!-- Search Command Palette -->
        <div data-tooltip="Search (CTRL+K)" class="sidebar-tooltip sidebar-row flex items-center gap-3 px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant/30 text-on-surface-variant cursor-pointer hover:border-primary/40 transition-colors mt-1">
            <span class="material-symbols-outlined text-[20px] flex-shrink-0">search</span>
            <span class="font-body-sm flex-1 select-none truncate hide-collapsed">Search...</span>
            <span class="hidden md:inline-block font-label-sm text-[10px] px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant/80 border border-outline-variant/40 hide-collapsed">CTRL K</span>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="flex flex-col gap-2 pt-4 flex-1" data-active-classes="bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm">
        
        <a data-tooltip="Home" aria-current="<?= $currentPage === 'home' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'home' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="home"
           href="<?php echo BASE_URL; ?>views/index.php">
            <div class="flex items-center gap-3 sidebar-gap">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'home' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">home</span>
                <span class="sidebar-text hide-collapsed transition-opacity duration-200">Home</span>
            </div>
        </a>

        <a data-tooltip="Decks" aria-current="<?= $currentPage === 'decks' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'decks' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="decks"
           href="<?php echo BASE_URL; ?>views/decks.php">
            <div class="flex items-center gap-3 sidebar-gap">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'decks' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">style</span>
                <span class="sidebar-text hide-collapsed transition-opacity duration-200">Decks</span>
            </div>
        </a>

        <a data-tooltip="Review <?= $sidebarDueCount > 0 ? '('.$sidebarDueCount.')' : '' ?>" aria-current="<?= $currentPage === 'review' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'review' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="review"
           href="<?php echo BASE_URL; ?>views/review.php">
            <div class="flex items-center gap-3 sidebar-gap relative">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'review' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">pace</span>
                <span class="sidebar-text hide-collapsed transition-opacity duration-200">Review</span>
                
                <!-- Notification dot for collapsed view -->
                <?php if ($sidebarDueCount > 0): ?>
                    <span class="md:hidden hidden absolute top-0 right-0 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-surface-container-lowest" id="review-dot"></span>
                <?php endif; ?>
            </div>
            
            <?php if ($sidebarDueCount > 0): ?>
                <div class="sidebar-text hide-collapsed transition-opacity duration-200">
                    <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200/50 font-bold text-[11px] shadow-sm">
                        <?= $sidebarDueCount ?>
                    </span>
                </div>
            <?php endif; ?>
        </a>

        <a data-tooltip="Reports" aria-current="<?= $currentPage === 'reports' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'reports' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="reports"
           href="<?php echo BASE_URL; ?>views/reports.php">
            <div class="flex items-center gap-3 sidebar-gap">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'reports' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">monitoring</span>
                <span class="sidebar-text hide-collapsed transition-opacity duration-200">Reports</span>
            </div>
        </a>

        <a data-tooltip="Science" aria-current="<?= $currentPage === 'science' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'science' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="science"
           href="<?php echo BASE_URL; ?>views/science.php">
            <div class="flex items-center gap-3 sidebar-gap">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'science' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">psychology</span>
                <span class="sidebar-text hide-collapsed transition-opacity duration-200">Science</span>
            </div>
        </a>
    </nav>

    <div class="flex flex-col gap-2 pt-4 border-t border-outline-variant/30">
        <nav class="flex flex-col gap-1" data-active-classes="bg-surface-container-high text-on-surface font-semibold">
            
            <a data-tooltip="Settings" aria-current="<?= $currentPage === 'settings' ? 'page' : 'false' ?>"
               class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'settings' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
               data-path="settings"
               href="<?php echo BASE_URL; ?>views/settings.php">
                <div class="flex items-center gap-3 sidebar-gap">
                    <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'settings' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">settings</span>
                    <span class="sidebar-text hide-collapsed transition-opacity duration-200">Settings</span>
                </div>
            </a>

            <button data-tooltip="Logout" type="button" id="logout-btn" class="sidebar-tooltip sidebar-row sidebar-gap w-full flex items-center gap-3 px-3.5 py-2 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-error transition-all text-left">
                <span class="material-symbols-outlined text-[24px]">logout</span>
                <span class="sidebar-text hide-collapsed transition-opacity duration-200">Logout</span>
            </button>
        </nav>
    </div>
</aside>

<!-- Logout Confirmation Dialog -->
<dialog id="logout-dialog" class="rounded-2xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs transition-all">
    <div class="p-6 flex flex-col gap-5">
        <div class="flex items-start gap-4">
            <div class="flex flex-col">
                <h3 class="font-headline-sm text-lg font-bold text-on-surface tracking-tight">Log out?</h3>
                <p class="text-xs text-on-surface-variant/80 font-medium leading-relaxed mt-1">
                    Are you sure you want to exit? You will need to sign back in to access your flashcards and study statistics.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-outline-variant/20">
            <button type="button" id="logout-cancel" class="px-4 py-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-xs transition-colors cursor-pointer">
                Cancel
            </button>
            <a href="/mempulse/authentication/logout.php" id="logout-confirm" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-error hover:bg-rose-700 text-white font-semibold text-xs shadow-xs hover:shadow-md transition-all cursor-pointer">
                <span>Log out</span>
            </a>
        </div>
    </div>
</dialog>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.querySelector("#sidebar");
        const mainContent = document.querySelector("main");
        const toggleBtn = document.querySelector("#sidebar-toggle");
        
        // Mobile layout bindings
        const floatingToggleBtn = document.querySelector("#floating-mobile-toggle");
        const mobileOverlay = document.querySelector("#mobile-sidebar-overlay");
        const reviewDot = document.querySelector("#review-dot");

        if (!sidebar) return;

        const toggleIcon = toggleBtn ? toggleBtn.querySelector(".material-symbols-outlined") : null;

        // Re-check state just in case DOM loaded faster than FOUC script
        const sidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
        if (sidebarCollapsed && window.innerWidth >= 768) {
            sidebar.classList.add("collapsed");
            if (mainContent) mainContent.classList.add("collapsed");
            if (toggleIcon) toggleIcon.textContent = "left_panel_open";
            if (reviewDot) reviewDot.classList.remove("hidden");
        }

        if (toggleBtn) {
            toggleBtn.addEventListener("click", () => {
                if (window.innerWidth < 768) {
                    closeMobileSidebar();
                    return;
                }
                
                const isCollapsed = sidebar.classList.toggle("collapsed");
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

        function openMobileSidebar() {
            sidebar.classList.remove("-translate-x-full");
            sidebar.classList.remove("collapsed"); 
            mobileOverlay.classList.remove("hidden");
            
            if (toggleIcon) toggleIcon.textContent = "close";
            setTimeout(() => mobileOverlay.classList.remove("opacity-0"), 10);
        }

        function closeMobileSidebar() {
            sidebar.classList.add("-translate-x-full");
            mobileOverlay.classList.add("opacity-0");

            if (toggleIcon) {
                toggleIcon.textContent = sidebar.classList.contains("collapsed") ? "left_panel_open" : "left_panel_close";
            }
            setTimeout(() => mobileOverlay.classList.add("hidden"), 300);
        }

        if (floatingToggleBtn) {
            floatingToggleBtn.addEventListener("click", openMobileSidebar);
        }
        
        if (mobileOverlay) {
            mobileOverlay.addEventListener("click", closeMobileSidebar);
        }

        const logoutBtn = document.getElementById('logout-btn');
        const logoutDialog = document.getElementById('logout-dialog');
        const logoutCancel = document.getElementById('logout-cancel');

        if (logoutBtn && logoutDialog) {
            logoutBtn.addEventListener('click', () => {
                logoutDialog.showModal();
            });

            if (logoutCancel) {
                logoutCancel.addEventListener('click', () => {
                    logoutDialog.close();
                });
            }

            logoutDialog.addEventListener('click', (e) => {
                const dialogDimensions = logoutDialog.getBoundingClientRect();
                if (
                    e.clientX < dialogDimensions.left ||
                    e.clientX > dialogDimensions.right ||
                    e.clientY < dialogDimensions.top ||
                    e.clientY > dialogDimensions.bottom
                ) {
                    logoutDialog.close();
                }
            });
        }
    });
</script>