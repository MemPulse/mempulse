<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/mempulse/app/');
}
$currentPage = $currentPage ?? '';
?>

<!-- Mobile Overlay -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>

<aside id="sidebar" class="fixed left-0 top-0 h-full overflow-y-auto w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-50 flex flex-col justify-between py-6 px-4 shadow-[0_1px_8px_rgba(0,0,0,0.03)] transition-all duration-300 ease-in-out transform -translate-x-full md:translate-x-0">
    <div id="sidebar-header" class="flex items-center justify-between gap-3 px-3 transition-all duration-300">
        <div class="flex items-center gap-3 flex-shrink-0">
            <span class="logo-text font-headline-sm text-headline-sm font-semibold tracking-tight text-on-surface transition-all duration-200">MemPulse</span>
        </div>

        <button id="sidebar-toggle" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors flex-shrink-0" title="Toggle Sidebar">
            <span class="material-symbols-outlined text-[24px]">left_panel_close</span>
        </button>
    </div>

    <nav class="flex flex-col gap-3" data-active-classes="bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm">
        <!-- Home Page -->
        <a aria-current="<?= $currentPage === 'home' ? 'page' : 'false' ?>"
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'home' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="home"
           href="<?php echo BASE_URL; ?>views/index.php">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">home</span>
                <span class="sidebar-text transition-opacity duration-200">Home</span>
            </div>
        </a>

        <!-- Decks Page -->
        <a aria-current="<?= $currentPage === 'decks' ? 'page' : 'false' ?>"
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'decks' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="decks"
           href="<?php echo BASE_URL; ?>views/decks.php">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">style</span>
                <span class="sidebar-text transition-opacity duration-200">Decks</span>
            </div>
        </a>

        <!-- Review Page -->
        <a aria-current="<?= $currentPage === 'review' ? 'page' : 'false' ?>"
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'review' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="review"
           href="<?php echo BASE_URL; ?>views/review.php">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">pace</span>
                <span class="sidebar-text transition-opacity duration-200">Review</span>
            </div>
        </a>

        <!-- Reports -->
        <a aria-current="<?= $currentPage === 'reports' ? 'page' : 'false' ?>"
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'reports' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="reports"
           href="<?php echo BASE_URL; ?>views/reports.php">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">monitoring</span>
                <span class="sidebar-text transition-opacity duration-200">Reports</span>
            </div>
        </a>

        <!-- Science -->
        <a aria-current="<?= $currentPage === 'science' ? 'page' : 'false' ?>"
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'science' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
           data-path="science"
           href="<?php echo BASE_URL; ?>views/science.php">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">psychology</span>
                <span class="sidebar-text transition-opacity duration-200">Science</span>
            </div>
        </a>
    </nav>

    <div class="flex flex-col gap-2 pt-4 border-t border-outline-variant/30">
        <nav class="flex flex-col gap-1" data-active-classes="bg-surface-container-high text-on-surface font-semibold">
            <!-- Settings -->
            <a aria-current="<?= $currentPage === 'settings' ? 'page' : 'false' ?>"
               class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group <?= $currentPage === 'settings' ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm' : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface' ?>"
               data-path="settings"
               href="<?php echo BASE_URL; ?>views/settings.php">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">settings</span>
                    <span class="sidebar-text transition-opacity duration-200">Settings</span>
                </div>
            </a>

            <!-- Logout Trigger -->
            <button type="button" id="logout-btn" class="w-full flex items-center gap-3 px-3.5 py-2 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-error transition-all text-left">
                <span class="material-symbols-outlined text-[24px]">logout</span>
                <span class="sidebar-text transition-opacity duration-200">Logout</span>
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
        const mobileToggleBtn = document.querySelector("#mobile-menu-toggle");
        const mobileOverlay = document.querySelector("#mobile-sidebar-overlay");

        if (!sidebar) return;

        const toggleIcon = toggleBtn ? toggleBtn.querySelector(".material-symbols-outlined") : null;

        const sidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
        if (sidebarCollapsed && window.innerWidth >= 768) {
            sidebar.classList.add("collapsed");
            if (mainContent) {
                mainContent.classList.add("collapsed");
            }
            if (toggleIcon) {
                toggleIcon.textContent = "left_panel_open";
            }
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

        if (mobileToggleBtn) {
            mobileToggleBtn.addEventListener("click", openMobileSidebar);
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