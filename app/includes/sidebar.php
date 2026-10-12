<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

// 2. Fetch User Display Name and Role
if (!isset($displayName) || !isset($displayRole)) {
    $displayName = 'Active Learner';
    $displayRole = 'User';
    
    if ($user_id && isset($conn)) {
        // Updated query to fetch role
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
?>

<style>
/* Outer Sidebar must not hide overflow so tooltips and toggle button can overlap the edge */
#sidebar {
    overflow: visible !important;
}

/* Custom Tooltip Logic - Only active when sidebar is collapsed */
#sidebar.collapsed .sidebar-tooltip {
    position: relative;
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
    z-index: 99999;
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
    z-index: 99999;
}

#sidebar.collapsed .sidebar-tooltip:hover::after,
#sidebar.collapsed .sidebar-tooltip:hover::before {
    opacity: 1;
    transform: translateY(-50%) translateX(4px);
}

/* Collapsed layout fixes */
#sidebar.collapsed .hide-collapsed {
    display: none !important;
}
#sidebar.collapsed .sidebar-row {
    justify-content: center !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}
#sidebar.collapsed .sidebar-gap {
    gap: 0 !important;
}
#sidebar.collapsed #settings-popover {
    left: calc(100% + 12px);
    right: auto;
    bottom: 0;
}
</style>

<!-- Floating Mobile Menu Button -->
<button id="floating-mobile-toggle" aria-label="Open Sidebar" class="md:hidden fixed top-4 left-4 z-40 p-2.5 rounded-xl bg-surface-container-lowest shadow-md border border-outline-variant/30 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors">
    <span class="material-symbols-outlined text-[24px]">menu</span>
</button>

<!-- Mobile Overlay -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>

<!-- MAIN SIDEBAR CONTAINER -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-50 flex flex-col transition-all duration-300 ease-in-out transform -translate-x-full md:translate-x-0 shadow-[0_1px_8px_rgba(0,0,0,0.03)]">
    
    <!-- Synchronous script to prevent FOUC -->
    <script>
        if (localStorage.getItem("sidebarCollapsed") === "true" && window.innerWidth >= 768) {
            document.getElementById("sidebar").classList.add("collapsed");
        }
    </script>

    <!-- Border Toggle Button (Desktop Only) -->
    <button id="sidebar-toggle" aria-label="Toggle Sidebar" class="hidden md:flex absolute -right-3.5 top-8 w-7 h-7 bg-surface-container-lowest border border-outline-variant/40 text-on-surface-variant rounded-full items-center justify-center hover:text-primary hover:border-primary shadow-sm transition-colors z-[60]">
        <span class="material-symbols-outlined text-[16px]">left_panel_close</span>
    </button>

    <!-- Top Section: Logo & Search -->
    <div class="flex flex-col gap-4 px-4 pt-6 pb-2 transition-all">
        <!-- Logo -->
        <div class="sidebar-row flex items-center gap-3 h-8">
            <span class="material-symbols-outlined text-[28px] text-primary hide-collapsed" style="display: none;">neurology</span>
            <span class="logo-text font-headline-sm text-xl font-bold tracking-tight text-on-surface transition-all duration-200">MemPulse</span>
            <span class="material-symbols-outlined text-primary text-[28px] md:hidden hidden block-collapsed">neurology</span>
        </div>

        <!-- Search Command Palette -->
        <div data-tooltip="Search (CTRL+K)" class="sidebar-tooltip sidebar-row flex items-center gap-2 px-3 py-2 rounded-xl bg-surface-container-low border border-outline-variant/30 text-on-surface-variant cursor-pointer hover:border-primary/40 hover:text-on-surface transition-colors">
            <span class="material-symbols-outlined text-[20px] flex-shrink-0">search</span>
            <span class="font-body-sm flex-1 select-none truncate hide-collapsed">Search...</span>
        </div>
    </div>

    <!-- Middle Section: Scrollable Navigation -->
    <nav class="flex-1 overflow-y-auto overflow-x-visible px-4 py-2 flex flex-col gap-1.5" data-active-classes="bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm">
        
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

        <a data-tooltip="Science" aria-current="<?= $currentPage === 'science' ? 'page' : 'false' ?>"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'science' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           href="<?php echo BASE_URL; ?>views/science.php">
            <div class="flex items-center gap-3 sidebar-gap">
                <span class="material-symbols-outlined text-[24px] <?= $currentPage === 'science' ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors">psychology</span>
                <span class="hide-collapsed transition-opacity duration-200">Science</span>
            </div>
        </a>
    </nav>

    <!-- Bottom Section: Profile & Settings Anchor -->
    <div class="border-t border-outline-variant/30 p-4 relative">
        <div class="flex items-center justify-between">
            <!-- Left: Avatar, Name & Dynamic Role -->
            <button id="profile-popover-trigger" class="sidebar-tooltip sidebar-row flex items-center gap-3 text-left group hover:opacity-80 transition-opacity w-full outline-none" data-tooltip="Account Settings">
                <img alt="User Avatar" class="w-9 h-9 rounded-full object-cover ring-2 ring-surface-container-highest group-hover:ring-primary/50 transition-all flex-shrink-0" src="../assets/images/profile-picture.webp.jpg">
                <div class="flex flex-col min-w-0 hide-collapsed">
                    <span class="font-label-sm font-semibold text-on-surface leading-tight truncate">
                        <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="text-[11px] text-on-surface-variant font-medium leading-none tracking-wide mt-1">
                        <?php echo htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
            </button>
            
            <!-- Right: Settings Menu Icon (Visible when expanded) -->
            <button id="settings-popover-trigger" class="hide-collapsed p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors flex-shrink-0" aria-label="Settings Menu">
                <span class="material-symbols-outlined text-[22px]">more_vert</span>
            </button>
        </div>

        <!-- Popover Menu (Hidden by default) -->
        <div id="settings-popover" class="hidden absolute bottom-[calc(100%+8px)] right-4 w-52 bg-surface-container-lowest border border-outline-variant/30 shadow-xl rounded-xl py-1.5 z-[100] transform origin-bottom-right">
            <div class="px-3 py-2 border-b border-outline-variant/20 mb-1 hide-collapsed">
                <p class="text-xs font-semibold text-on-surface truncate"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="text-[10px] text-on-surface-variant truncate"><?php echo htmlspecialchars($displayRole, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <a href="<?php echo BASE_URL; ?>views/settings.php" class="flex items-center gap-3 px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[18px]">settings</span>
                System Settings
            </a>
            <a href="<?php echo BASE_URL; ?>views/account.php" class="flex items-center gap-3 px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[18px]">manage_accounts</span>
                Account Settings
            </a>
            <div class="h-px bg-outline-variant/20 my-1"></div>
            <button type="button" id="logout-btn" class="w-full flex items-center gap-3 px-4 py-2 text-sm font-medium text-error hover:bg-rose-50 hover:text-error transition-colors text-left">
                <span class="material-symbols-outlined text-[18px]">logout</span>
                Logout
            </button>
        </div>
    </div>
</aside>

<!-- Logout Confirmation Dialog -->
<dialog id="logout-dialog" class="rounded-2xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs transition-all">
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

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const sidebar = document.querySelector("#sidebar");
        const mainContent = document.querySelector("main");
        const toggleBtn = document.querySelector("#sidebar-toggle");
        
        // Mobile elements
        const floatingToggleBtn = document.querySelector("#floating-mobile-toggle");
        const mobileOverlay = document.querySelector("#mobile-sidebar-overlay");
        const reviewDot = document.querySelector("#review-dot");

        // Popover Elements
        const profileTrigger = document.querySelector("#profile-popover-trigger");
        const settingsTrigger = document.querySelector("#settings-popover-trigger");
        const settingsPopover = document.querySelector("#settings-popover");

        if (!sidebar) return;

        const toggleIcon = toggleBtn ? toggleBtn.querySelector(".material-symbols-outlined") : null;

        // Apply visual collapsed state
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
                if (mainContent) {
                    mainContent.classList.toggle("collapsed", isCollapsed);
                }
                if (toggleIcon) {
                    toggleIcon.textContent = isCollapsed ? "left_panel_open" : "left_panel_close";
                }
                if (reviewDot) {
                    isCollapsed ? reviewDot.classList.remove("hidden") : reviewDot.classList.add("hidden");
                }
                // Close popover if sidebar collapses
                settingsPopover.classList.add('hidden');
                
                localStorage.setItem("sidebarCollapsed", isCollapsed);
            });
        }

        // Mobile Toggle Interactions
        function openMobileSidebar() {
            sidebar.classList.remove("-translate-x-full", "collapsed"); 
            mobileOverlay.classList.remove("hidden");
            setTimeout(() => mobileOverlay.classList.remove("opacity-0"), 10);
        }

        function closeMobileSidebar() {
            sidebar.classList.add("-translate-x-full");
            mobileOverlay.classList.add("opacity-0");
            setTimeout(() => mobileOverlay.classList.add("hidden"), 300);
        }

        if (floatingToggleBtn) floatingToggleBtn.addEventListener("click", openMobileSidebar);
        if (mobileOverlay) mobileOverlay.addEventListener("click", closeMobileSidebar);

        // Popover Toggle Logic
        function togglePopover(e) {
            e.stopPropagation();
            settingsPopover.classList.toggle('hidden');
        }

        if (profileTrigger) profileTrigger.addEventListener('click', togglePopover);
        if (settingsTrigger) settingsTrigger.addEventListener('click', togglePopover);

        // Click outside to close popover
        document.addEventListener('click', (e) => {
            if (!settingsPopover.contains(e.target) && !profileTrigger.contains(e.target) && !settingsTrigger.contains(e.target)) {
                settingsPopover.classList.add('hidden');
            }
        });

        // Logout Dialog Logic
        const logoutBtn = document.getElementById('logout-btn');
        const logoutDialog = document.getElementById('logout-dialog');
        const logoutCancel = document.getElementById('logout-cancel');

        if (logoutBtn && logoutDialog) {
            logoutBtn.addEventListener('click', () => {
                settingsPopover.classList.add('hidden'); // Close popover
                logoutDialog.showModal();
            });

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