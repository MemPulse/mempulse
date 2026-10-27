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
    /* Shared sidebar behavior and header-aware layout from the user sidebar. */
    :root {
        --app-header-h: 4rem;
        --glass-surface: var(--color-surface-container-lowest, #ffffff);
        --glass-primary: var(--color-primary, #3525cd);
    }
    #app-header { height: var(--app-header-h); }
    #sidebar {
        top: var(--app-header-h);
        height: calc(100vh - var(--app-header-h));
        height: calc(100dvh - var(--app-header-h));
        background-color: color-mix(in srgb, var(--glass-surface) 70%, transparent);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        backdrop-filter: blur(16px) saturate(180%);
        border-right-color: color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
        box-shadow: none;
    }
    #app-header {
        background-color: color-mix(in srgb, var(--glass-surface) 70%, transparent);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        backdrop-filter: blur(16px) saturate(180%);
        border-bottom-color: color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
        box-shadow: 0 4px 24px -8px rgba(15, 23, 42, 0.12);
    }
    #mobile-sidebar-overlay { top: var(--app-header-h); }
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        #app-header, #sidebar { background-color: var(--glass-surface); }
    }
    @media (max-width: 767px) {
        #sidebar { background-color: color-mix(in srgb, var(--glass-surface) 94%, transparent); }
    }
    #settings-popover { z-index: 1000; }
    @media (min-width: 768px) {
        #sidebar.collapsed .sidebar-top,
        #sidebar.collapsed .sidebar-account { padding-left: 0; padding-right: 0; }
        #sidebar.collapsed .sidebar-profile { border-color: transparent; }
    }

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
    .sidebar-top {
        flex-shrink: 0;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
    }
    .sidebar-profile {
        min-height: 3rem;
        padding: 0.5rem 0.625rem;
        border: 1px solid color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
    }
    .sidebar-profile:hover { border-color: color-mix(in srgb, var(--glass-primary) 35%, transparent); }
    .sidebar-account {
        flex-shrink: 0;
        padding: 0.75rem 1rem;
        padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));
        border-top: 1px solid color-mix(in srgb, var(--color-outline-variant, #cbd5e1) 35%, transparent);
    }
    .sidebar-profile .profile-avatar {
        font-size: 34px;
        flex-shrink: 0;
        color: var(--color-primary, #3525cd);
        font-variation-settings: 'FILL' 1;
    }
    #header-brand { min-width: 0; }

    #sidebar.collapsed #profile-popover-trigger .material-symbols-outlined { font-size: 28px !important; }
    #sidebar.collapsed #settings-popover { left: calc(100% + 12px); right: auto; bottom: 0; }

    .sidebar-subtle-link {
        display: flex; align-items: center; gap: 0.75rem;
        padding: 0.5rem 0.875rem; border-radius: 0.75rem;
        font-size: 0.875rem; font-weight: 500;
        color: var(--color-on-surface-variant, #64748b);
        transition: background-color .15s ease, color .15s ease;
    }
    .sidebar-subtle-link:hover {
        background-color: color-mix(in srgb, var(--color-surface-container-low, #f1f5f9) 80%, transparent);
        color: var(--color-on-surface, #0f172a);
    }
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

<!-- TOP ADMIN HEADER -->
<header id="app-header" class="fixed top-0 left-0 right-0 h-16 z-50 px-3 sm:px-6 flex items-center justify-between gap-3 border-b border-outline-variant/40">
    <div id="header-brand" class="flex items-center gap-2.5 sm:gap-3">
        <button id="floating-mobile-toggle" type="button" aria-label="Toggle Sidebar" aria-expanded="false"
            class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors outline-none">
            <span id="mobile-toggle-icon" class="material-symbols-outlined text-[24px]">menu</span>
        </button>
        <a href="<?= ADMIN_URL ?>views/index.php" class="flex items-center gap-2.5 h-8 outline-none group">
            <span class="material-symbols-outlined text-[28px] text-primary memory-pulse-bg">neurology</span>
            <span class="font-headline-sm text-lg sm:text-xl font-bold tracking-tight text-on-surface">MemPulse</span>
        </a>
        <span class="hidden sm:inline-flex px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed-variant text-[10px] font-bold uppercase tracking-wider">Admin</span>
    </div>
    
    <div class="flex items-center gap-2 sm:gap-3 text-sm font-medium text-on-surface-variant">
        <button
            type="button"
            id="global-search-trigger"
            aria-label="Search all admin sections"
            title="Search all admin sections (Ctrl+K)"
            class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-outline-variant/40 hover:border-primary/40 hover:bg-surface-container-low hover:text-primary transition-colors"
        >
            <span class="material-symbols-outlined text-[22px]">search</span>
        </button>

        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
            <span class="hidden sm:inline">Administration</span>
        </div>
    </div>

</header>


<!-- Global Admin Search -->
<div
    id="global-search-modal"
    data-search-endpoint="<?= ADMIN_URL ?>api/global-search.php"
    class="hidden fixed inset-0 z-[100] items-start justify-center bg-black/50 backdrop-blur-sm px-3 pt-[12vh] sm:px-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="global-search-title"
>
    <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-outline-variant/40 bg-surface-container-lowest shadow-2xl">
        <div class="flex items-center gap-3 border-b border-outline-variant/30 px-4">
            <span class="material-symbols-outlined text-[24px] text-primary">search</span>

            <input
                id="global-search-input"
                type="search"
                autocomplete="off"
                maxlength="100"
                placeholder="Search users, content, activity, audit logs..."
                aria-label="Search all admin sections"
                class="min-w-0 flex-1 bg-transparent py-4 text-sm text-on-surface outline-none placeholder:text-on-surface-variant"
            >

            <button
                type="button"
                id="global-search-close"
                aria-label="Close search"
                class="rounded-lg p-2 text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface"
            >
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-outline-variant/20 px-4 py-2">
            <p id="global-search-status" class="text-xs text-on-surface-variant">
                Type at least 2 characters to search all 4 sections.
            </p>
            <span class="text-[11px] text-on-surface-variant">ESC to close</span>
        </div>

        <div id="global-search-results" class="max-h-[60vh] overflow-y-auto p-3">
            <div class="px-3 py-8 text-center text-sm text-on-surface-variant">
                Search across Users, Content, Activity, and Audit Log.
            </div>
        </div>
    </div>
</div>


<!-- Mobile Overlay (below the fixed header) -->
<div id="mobile-sidebar-overlay" class="fixed inset-x-0 top-16 bottom-0 bg-black/50 z-30 hidden md:hidden transition-opacity opacity-0"></div>

<!-- MAIN SIDEBAR CONTAINER; its top and height follow the fixed header -->
<aside id="sidebar" class="fixed left-0 w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-40 flex flex-col transition-all duration-300 ease-in-out transform -translate-x-full md:translate-x-0">

    <!-- Prevent flash of the expanded state -->
    <script>
        if (localStorage.getItem("sidebarCollapsed") === "true" && window.innerWidth >= 768) {
            document.getElementById("sidebar").classList.add("collapsed");
        }
    </script>

    <!-- Border Toggle Button (Desktop Only) -->
    <button id="sidebar-toggle" aria-label="Toggle Sidebar" class="hidden md:flex absolute -right-3.5 top-6 w-7 h-7 bg-surface-container-lowest border border-outline-variant/40 text-on-surface-variant rounded-full items-center justify-center hover:text-primary hover:border-primary shadow-sm transition-colors z-[60]">
        <span class="material-symbols-outlined text-[16px]">left_panel_close</span>
    </button>

    <!-- Admin profile at the top, matching the user sidebar pattern -->
    <div class="sidebar-top">
        <div class="sidebar-row sidebar-profile flex items-center gap-3 rounded-xl">
            <span class="material-symbols-outlined profile-avatar">account_circle</span>
            <span class="hide-collapsed flex flex-col min-w-0">
                <span class="text-sm font-semibold text-on-surface leading-tight truncate"><?= adminEsc($displayName) ?></span>
                <span class="text-[11px] font-medium text-on-surface-variant leading-tight mt-0.5 truncate"><?= adminEsc($displayRole) ?> · Admin</span>
            </span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-navigation flex-1 min-h-0 overflow-y-auto px-4 py-4 flex flex-col gap-1.5" aria-label="Admin navigation">
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
    <div class="sidebar-account relative flex flex-col gap-3">
        <!-- <a data-tooltip="Back to MemPulse app"
           href="<?= APP_URL ?>views/index.php"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors">
            <span class="material-symbols-outlined text-[24px]">arrow_back</span>
            <span class="hide-collapsed">Back to app</span>
        </a> -->

        <div id="sidebar-account-controls" class="flex items-center gap-2">
            <button type="button" id="profile-popover-trigger"
                class="sidebar-tooltip sidebar-row sidebar-subtle-link flex items-center gap-3 text-left group hover:opacity-80 transition-opacity flex-1 min-w-0 outline-none rounded-xl"
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
<dialog id="logout-dialog" class="m-auto rounded-2xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs transition-all">
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
        floatingToggleBtn?.setAttribute('aria-expanded', 'true');
        mobileOverlay?.classList.remove('hidden');
        setTimeout(() => mobileOverlay?.classList.remove('opacity-0'), 10);
    }

    function closeMobileSidebar() {
        sidebar.classList.add('-translate-x-full');
        floatingToggleBtn?.setAttribute('aria-expanded', 'false');
        mobileOverlay?.classList.add('opacity-0');
        setTimeout(() => mobileOverlay?.classList.add('hidden'), 300);
    }

    floatingToggleBtn?.addEventListener('click', openMobileSidebar);
    mobileOverlay?.addEventListener('click', closeMobileSidebar);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMobileSidebar(); });

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

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.getElementById('global-search-trigger');
    const modal = document.getElementById('global-search-modal');
    const input = document.getElementById('global-search-input');
    const closeBtn = document.getElementById('global-search-close');
    const results = document.getElementById('global-search-results');
    const status = document.getElementById('global-search-status');

    if (!trigger || !modal || !input || !results || !status) return;

    const sections = [
        { key: 'users', label: 'Users', icon: 'group' },
        { key: 'content', label: 'Content', icon: 'library_books' },
        { key: 'activity', label: 'Activity', icon: 'monitoring' },
        { key: 'audit', label: 'Audit Log', icon: 'history' }
    ];

    let debounceTimer = null;
    let requestController = null;
    let previousFocus = null;
    let requestNumber = 0;

    function makeElement(tag, className, text) {
        const el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined) el.textContent = text;
        return el;
    }

    function showModal() {
        previousFocus = document.activeElement;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
        input.focus();
    }

    function hideModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');

        if (requestController) {
            requestController.abort();
            requestController = null;
        }

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    function showMessage(message) {
        results.replaceChildren(
            makeElement(
                'div',
                'px-3 py-8 text-center text-sm text-on-surface-variant',
                message
            )
        );
    }

    function renderResults(data) {
        results.replaceChildren();

        let total = 0;

        sections.forEach(section => {
            const items = Array.isArray(data[section.key])
                ? data[section.key]
                : [];

            if (!items.length) return;

            total += items.length;

            const group = makeElement('section', 'mb-3 last:mb-0');
            const heading = makeElement(
                'h3',
                'px-3 pb-2 pt-3 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant',
                section.label
            );

            group.appendChild(heading);

            items.forEach(item => {
                if (
                    !item ||
                    typeof item.title !== 'string' ||
                    typeof item.detail !== 'string' ||
                    typeof item.url !== 'string'
                ) {
                    return;
                }

                const link = makeElement(
                    'a',
                    'flex items-start gap-3 rounded-xl px-3 py-3 hover:bg-surface-container-low focus:bg-surface-container-low focus:outline-none'
                );

                // Use only same-origin paths returned by this admin API.
                let destination;
                try {
                    destination = new URL(item.url, window.location.origin);
                } catch (_) {
                    return;
                }

                if (destination.origin !== window.location.origin) return;

                const icon = makeElement(
                    'span',
                    'material-symbols-outlined mt-0.5 text-[22px] text-primary',
                    section.icon
                );

                const textWrap = makeElement('div', 'min-w-0 flex-1');
                const title = makeElement(
                    'div',
                    'truncate text-sm font-semibold text-on-surface',
                    item.title
                );
                const detail = makeElement(
                    'div',
                    'mt-1 text-xs leading-relaxed text-on-surface-variant',
                    item.detail
                );

                textWrap.append(title, detail);
                link.append(icon, textWrap);
                link.href = destination.pathname + destination.search;
                group.appendChild(link);
            });

            results.appendChild(group);
        });

        status.textContent = total
            ? total + ' result' + (total === 1 ? '' : 's') + ' shown'
            : 'No matching results found in the 4 sections.';

        if (!total) {
            showMessage('No matches found. Try another name, email, session ID, action, or IP address.');
        }
    }

    async function runSearch() {
        const query = input.value.trim();
        const currentRequest = ++requestNumber;

        if (requestController) requestController.abort();

        if (query.length < 2) {
            status.textContent = 'Type at least 2 characters to search all 4 sections.';
            showMessage('Search across Users, Content, Activity, and Audit Log.');
            return;
        }

        requestController = new AbortController();
        status.textContent = 'Searching all 4 sections...';
        showMessage('Searching...');

        try {
            const endpoint = modal.dataset.searchEndpoint;
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('q', query);

            if (url.origin !== window.location.origin) {
                throw new Error('Invalid search endpoint.');
            }

            const response = await fetch(url.href, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' },
                signal: requestController.signal
            });

            if (!response.ok) {
                throw new Error('Search request failed.');
            }

            const data = await response.json();

            if (currentRequest !== requestNumber) return;
            if (data.error) throw new Error(data.error);

            renderResults(data);
        } catch (error) {
            if (error.name === 'AbortError') return;
            status.textContent = 'Search could not be completed.';
            showMessage('Something went wrong. Check the browser console and PHP error log.');
        }
    }

    trigger.addEventListener('click', showModal);
    closeBtn?.addEventListener('click', hideModal);

    modal.addEventListener('click', event => {
        if (event.target === modal) hideModal();
    });

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runSearch, 250);
    });

    document.addEventListener('keydown', event => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();

            if (modal.classList.contains('hidden')) {
                showModal();
            } else {
                input.focus();
            }
        }

        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            hideModal();
        }
    });
});
</script>
