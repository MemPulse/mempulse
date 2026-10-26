<?php
// Expects guard.php to be included ($displayName, $displayRole) and $currentPage set by the page.
$currentPage = $currentPage ?? '';

// One place to define navigation. Set 'ready' => true when the page exists.
// Inside admin/includes/sidebar.php:
$adminNav = [
    ['key' => 'overview', 'label' => 'Overview',  'icon' => 'dashboard',     'href' => 'views/index.php',    'ready' => true],
    ['key' => 'users',    'label' => 'Users',     'icon' => 'group',         'href' => 'views/users.php',    'ready' => true],
    ['key' => 'content',  'label' => 'Content',   'icon' => 'library_books', 'href' => 'views/content.php',  'ready' => true],
    ['key' => 'activity', 'label' => 'Activity',  'icon' => 'monitoring',    'href' => 'views/activity.php', 'ready' => true],
    ['key' => 'audit',    'label' => 'Audit log', 'icon' => 'history',       'href' => 'views/audit.php',    'ready' => true],
];

$navBase     = 'sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group';
$navActive   = 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm';
$navInactive = 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface';
?>

<style>
    /* Same collapsed-sidebar and tooltip behavior as the app sidebar
       (trimmed: no settings modal, no review badge). */
    #sidebar { box-sizing: border-box; overflow-x: visible; overflow-y: visible; }
    #sidebar.collapsed .hide-collapsed { display: none !important; }
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
    #sidebar.collapsed .sidebar-gap { gap: 0 !important; }
    #sidebar-account-controls { min-width: 0; }
    #sidebar.collapsed #profile-popover-trigger .material-symbols-outlined { font-size: 28px !important; }
    #sidebar.collapsed #settings-popover { left: calc(100% + 12px); right: auto; bottom: 0; }

    #sidebar-floating-tooltip {
        position: fixed; z-index: 99999;
        max-width: min(280px, calc(100vw - 24px));
        padding: 6px 10px; border-radius: 8px;
        background: #1e293b; color: #fff;
        font-size: 12px; font-weight: 500; line-height: 1.4; white-space: nowrap;
        pointer-events: none; opacity: 0; visibility: hidden;
        transform: translateY(-50%);
        box-shadow: 0 4px 10px rgba(0, 0, 0, .18);
        transition: opacity .12s ease;
    }
    #sidebar-floating-tooltip.is-visible { opacity: 1; visibility: visible; }
    #sidebar-floating-tooltip::before {
        content: ''; position: absolute; top: 50%; left: -8px; transform: translateY(-50%);
        border: 4px solid transparent; border-right-color: #1e293b;
    }
    #sidebar-floating-tooltip.tooltip-left::before {
        left: auto; right: -8px; border-right-color: transparent; border-left-color: #1e293b;
    }

    @keyframes memory-pulse {
        0%, 100% { background-color: rgba(56, 189, 248, 0.10); }
        50% { background-color: rgba(56, 189, 248, 0); }
    }
    .memory-pulse-bg { animation: memory-pulse 2.5s ease-in-out infinite; border-radius: 50%; padding: 4px; margin-left: -4px; }
</style>

<!-- Floating Mobile Menu Button -->
<button id="floating-mobile-toggle" aria-label="Open Sidebar" class="md:hidden fixed top-4 left-4 z-40 p-2.5 rounded-xl bg-surface-container-lowest shadow-md border border-outline-variant/30 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors">
    <span class="material-symbols-outlined text-[24px]">menu</span>
</button>

<!-- Mobile Overlay -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>

<!-- MAIN SIDEBAR CONTAINER -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-50 flex flex-col transition-all duration-300 ease-in-out transform -translate-x-full md:translate-x-0 shadow-[0_1px_8px_rgba(0,0,0,0.03)]">

    <!-- Prevent flash of the expanded state -->
    <script>
        if (localStorage.getItem("sidebarCollapsed") === "true" && window.innerWidth >= 768) {
            document.getElementById("sidebar").classList.add("collapsed");
        }
    </script>

    <!-- Border Toggle Button (Desktop Only) -->
    <button id="sidebar-toggle" aria-label="Toggle Sidebar" class="hidden md:flex absolute -right-3.5 top-8 w-7 h-7 bg-surface-container-lowest border border-outline-variant/40 text-on-surface-variant rounded-full items-center justify-center hover:text-primary hover:border-primary shadow-sm transition-colors z-[60]">
        <span class="material-symbols-outlined text-[16px]">left_panel_close</span>
    </button>

    <!-- Logo + Admin badge -->
    <div class="flex flex-col gap-4 px-4 pt-6 pb-2">
        <div class="sidebar-row flex items-center gap-3 h-8">
            <span class="material-symbols-outlined text-[28px] text-primary memory-pulse-bg">neurology</span>
            <span class="hide-collapsed font-headline-sm text-xl font-bold tracking-tight text-on-surface">MemPulse</span>
            <span class="hide-collapsed px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed-variant text-[10px] font-bold uppercase tracking-wider">Admin</span>
        </div>
    </div>

    <hr class="mx-4 border-0 border-t border-outline-variant/40">

    <!-- Navigation -->
    <nav class="sidebar-navigation flex-1 overflow-y-auto px-4 py-2 flex flex-col gap-1.5" aria-label="Admin navigation">
        <?php foreach ($adminNav as $item): $active = $currentPage === $item['key']; ?>
            <?php if ($item['ready']): ?>
                <a data-tooltip="<?= adminEsc($item['label']) ?>"
                   aria-current="<?= $active ? 'page' : 'false' ?>"
                   class="<?= $navBase ?> <?= $active ? $navActive : $navInactive ?>"
                   href="<?= ADMIN_URL . $item['href'] ?>">
                    <div class="flex items-center gap-3 sidebar-gap">
                        <span class="material-symbols-outlined text-[24px] <?= $active ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors"><?= $item['icon'] ?></span>
                        <span class="hide-collapsed"><?= adminEsc($item['label']) ?></span>
                    </div>
                </a>
            <?php else: ?>
                <div data-tooltip="<?= adminEsc($item['label']) ?> (coming soon)"
                     aria-disabled="true"
                     class="<?= $navBase ?> <?= $navInactive ?> opacity-50 cursor-not-allowed hover:bg-transparent">
                    <div class="flex items-center gap-3 sidebar-gap">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant"><?= $item['icon'] ?></span>
                        <span class="hide-collapsed"><?= adminEsc($item['label']) ?></span>
                    </div>
                    <span class="hide-collapsed px-2 py-0.5 rounded-full bg-surface-container-low border border-outline-variant/30 text-[10px] font-semibold uppercase tracking-wide">Soon</span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom: back to app + account -->
    <div class="border-t border-outline-variant/30 p-4 relative flex flex-col gap-3">
        <!-- <a data-tooltip="Back to MemPulse app"
           href="<?= APP_URL ?>views/index.php"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors">
            <span class="material-symbols-outlined text-[24px]">arrow_back</span>
            <span class="hide-collapsed">Back to app</span>
        </a> -->

        <div id="sidebar-account-controls" class="flex items-center gap-2">
            <button type="button" id="profile-popover-trigger"
                class="sidebar-tooltip sidebar-row flex items-center gap-3 text-left group hover:opacity-80 transition-opacity flex-1 min-w-0 outline-none rounded-xl"
                data-tooltip="Account menu" title="Account menu" aria-label="Open account menu"
                aria-haspopup="true" aria-controls="settings-popover" aria-expanded="false">
                <span class="material-symbols-outlined text-[32px] flex-shrink-0 text-primary transition-all group-hover:scale-105">account_circle</span>
                <div class="flex flex-col min-w-0 hide-collapsed">
                    <span class="font-label-sm font-semibold text-on-surface leading-tight truncate"><?= adminEsc($displayName) ?></span>
                    <span class="text-[11px] text-on-surface-variant font-medium leading-none tracking-wide mt-1"><?= adminEsc($displayRole) ?></span>
                </div>
            </button>
        </div>

        <!-- Account menu -->
        <div id="settings-popover"
            class="hidden absolute bottom-[calc(100%+8px)] right-4 w-52 bg-surface-container-lowest border border-outline-variant/30 shadow-xl rounded-xl py-1.5 z-[100] transform origin-bottom-right">
            <div class="px-3 py-2 border-b border-outline-variant/20 mb-1">
                <p class="text-xs font-semibold text-on-surface truncate"><?= adminEsc($displayName) ?></p>
                <p class="text-[10px] text-on-surface-variant truncate"><?= adminEsc($adminUser['email']) ?></p>
            </div>
            <a href="<?= APP_URL ?>views/index.php"
               class="w-full flex items-center gap-3 px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[18px]">school</span>
                Student app
            </a>
            <div class="h-px bg-outline-variant/20 my-1"></div>
            <button type="button" id="logout-btn"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm font-medium text-error hover:bg-error-container/40 transition-colors text-left">
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
            You will need to sign in again to access the admin area.
        </p>
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
            <button type="button" id="logout-cancel" class="px-4 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-sm transition-colors">Cancel</button>
            <a href="/mempulse/authentication/logout.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-error hover:opacity-90 text-on-error font-semibold text-sm shadow-sm transition-colors">Log out</a>
        </div>
    </div>
</dialog>

<script>
    /**
 * Admin sidebar behavior. Mirrors the inline script in app/includes/sidebar.php:
 * floating tooltips, desktop collapse, mobile drawer, account popover, logout dialog.
 * (Settings modal and review badge are intentionally left out.)
 */
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('#sidebar');
    if (!sidebar) return;

    const mainContent = document.querySelector('main');
    const toggleBtn = document.querySelector('#sidebar-toggle');
    const toggleIcon = toggleBtn ? toggleBtn.querySelector('.material-symbols-outlined') : null;
    const floatingToggleBtn = document.querySelector('#floating-mobile-toggle');
    const mobileOverlay = document.querySelector('#mobile-sidebar-overlay');
    const profileTrigger = document.querySelector('#profile-popover-trigger');
    const accountPopover = document.querySelector('#settings-popover');

    // ---- Floating tooltip (rendered under <body> so the sidebar cannot clip it)
    let tooltip = document.getElementById('sidebar-floating-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.id = 'sidebar-floating-tooltip';
        tooltip.setAttribute('role', 'tooltip');
        document.body.appendChild(tooltip);
    }

    let tooltipTarget = null;

    function hideTooltip() {
        tooltip.classList.remove('is-visible');
        tooltipTarget = null;
    }

    function showTooltip(target) {
        const label = target.getAttribute('data-tooltip') || target.getAttribute('aria-label');
        if (!label || !sidebar.classList.contains('collapsed') || window.innerWidth < 768) {
            hideTooltip();
            return;
        }
        tooltipTarget = target;
        tooltip.textContent = label;
        tooltip.style.left = '0px';
        tooltip.style.top = '0px';
        tooltip.classList.add('is-visible');

        const rect = target.getBoundingClientRect();
        const tipRect = tooltip.getBoundingClientRect();
        const gap = 12;
        let left = rect.right + gap;

        if (left + tipRect.width > window.innerWidth - 8) {
            left = Math.max(8, rect.left - tipRect.width - gap);
            tooltip.classList.add('tooltip-left');
        } else {
            tooltip.classList.remove('tooltip-left');
        }

        const top = Math.min(window.innerHeight - 8, Math.max(8, rect.top + rect.height / 2));
        tooltip.style.left = `${left}px`;
        tooltip.style.top = `${top}px`;
    }

    sidebar.querySelectorAll('.sidebar-tooltip').forEach((item) => {
        item.addEventListener('mouseenter', () => showTooltip(item));
        item.addEventListener('mouseleave', hideTooltip);
        item.addEventListener('focus', () => showTooltip(item));
        item.addEventListener('blur', hideTooltip);
    });
    sidebar.addEventListener('scroll', hideTooltip, true);
    window.addEventListener('resize', hideTooltip);

    // ---- Account popover
    function setAccountPopoverOpen(isOpen) {
        if (!accountPopover || !profileTrigger) return;
        accountPopover.classList.toggle('hidden', !isOpen);
        profileTrigger.setAttribute('aria-expanded', String(isOpen));
    }

    profileTrigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        setAccountPopoverOpen(accountPopover?.classList.contains('hidden'));
    });

    document.addEventListener('click', (e) => {
        if (accountPopover && !accountPopover.contains(e.target) && !profileTrigger?.contains(e.target)) {
            setAccountPopoverOpen(false);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setAccountPopoverOpen(false);
    });

    // ---- Desktop collapse (state is shared with the app via localStorage)
    if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth >= 768) {
        sidebar.classList.add('collapsed');
        mainContent?.classList.add('collapsed');
        if (toggleIcon) toggleIcon.textContent = 'left_panel_open';
    }

    toggleBtn?.addEventListener('click', () => {
        const isCollapsed = sidebar.classList.toggle('collapsed');
        document.documentElement.classList.toggle('sidebar-collapsed', isCollapsed);
        mainContent?.classList.toggle('collapsed', isCollapsed);
        if (toggleIcon) toggleIcon.textContent = isCollapsed ? 'left_panel_open' : 'left_panel_close';
        hideTooltip();
        setAccountPopoverOpen(false);
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });

    // ---- Mobile drawer
    function openMobileSidebar() {
        sidebar.classList.remove('-translate-x-full', 'collapsed');
        mobileOverlay?.classList.remove('hidden');
        setTimeout(() => mobileOverlay?.classList.remove('opacity-0'), 10);
    }

    function closeMobileSidebar() {
        sidebar.classList.add('-translate-x-full');
        mobileOverlay?.classList.add('opacity-0');
        setTimeout(() => mobileOverlay?.classList.add('hidden'), 300);
    }

    floatingToggleBtn?.addEventListener('click', openMobileSidebar);
    mobileOverlay?.addEventListener('click', closeMobileSidebar);

    // ---- Logout dialog
    const logoutBtn = document.getElementById('logout-btn');
    const logoutDialog = document.getElementById('logout-dialog');
    const logoutCancel = document.getElementById('logout-cancel');

    if (logoutBtn && logoutDialog) {
        logoutBtn.addEventListener('click', () => {
            setAccountPopoverOpen(false);
            logoutDialog.showModal();
        });
        logoutCancel?.addEventListener('click', () => logoutDialog.close());
        logoutDialog.addEventListener('click', (e) => {
            const r = logoutDialog.getBoundingClientRect();
            if (e.clientX < r.left || e.clientX > r.right || e.clientY < r.top || e.clientY > r.bottom) {
                logoutDialog.close();
            }
        });
    }
});
</script>