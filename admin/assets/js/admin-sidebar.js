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