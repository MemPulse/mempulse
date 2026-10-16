/**
 * Admin Users: detail drawer + auto-submit filters.
 * Reads window.MemPulseAdminUsers = { users: { [id]: {...} } }. All values are set with
 * textContent, never innerHTML.
 */
(function () {
    'use strict';

    var data = window.MemPulseAdminUsers || { users: {} };
    var drawer = document.getElementById('user-drawer');
    var overlay = document.getElementById('user-drawer-overlay');
    var closeBtn = document.getElementById('user-drawer-close');
    if (!drawer || !overlay) return;

    var STATUS = {
        approved: 'bg-teal-50 text-teal-700 border-teal-200/80',
        pending: 'bg-amber-50 text-amber-700 border-amber-200/80',
        rejected: 'bg-error-container/40 text-error border-error/20'
    };
    var ROLE = {
        admin: 'bg-primary/10 text-primary border-primary/20',
        user: 'bg-surface-container-low text-on-surface-variant border-outline-variant/40'
    };

    var opener = null;

    function field(name) { return drawer.querySelector('[data-field="' + name + '"]'); }
    function setText(name, value) { var el = field(name); if (el) el.textContent = value; }
    function setBadge(name, value, map) {
        var el = field(name);
        el.textContent = value;
        el.className = 'text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border ' + (map[value] || map.user || map.pending);
    }
    function num(n) { return Number(n).toLocaleString(); }

    function fill(u) {
        setText('initial', (u.name || '?').trim().charAt(0).toUpperCase());
        setText('name', u.name);
        setText('email', u.email);
        setBadge('status', u.status, STATUS);
        setBadge('role', u.role, ROLE);
        field('self').classList.toggle('hidden', !u.isSelf);
        setText('decks', num(u.decks));
        setText('cards', num(u.cards));
        setText('sessions', num(u.sessions));
        setText('reviews', num(u.reviews));
        setText('retention', u.retention === null ? '\u2013' : u.retention + '%');
        setText('lastActive', u.lastActive);
        setText('joined', u.joined);
    }

    function open(id, trigger) {
        var u = data.users[id];
        if (!u) return;
        opener = trigger || null;
        fill(u);
        drawer.classList.remove('invisible', 'translate-x-full');
        overlay.classList.remove('hidden');
        requestAnimationFrame(function () { overlay.classList.remove('opacity-0'); });
        closeBtn.focus();
    }

    function close() {
        drawer.classList.add('translate-x-full');
        overlay.classList.add('opacity-0');
        setTimeout(function () {
            drawer.classList.add('invisible');
            overlay.classList.add('hidden');
        }, 300);
        if (opener) opener.focus();
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-user-id]');
        if (btn) open(btn.getAttribute('data-user-id'), btn);
    });
    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', close);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !drawer.classList.contains('invisible')) close();
    });

    // Selects submit their form on change; text and date inputs submit on Enter or Apply.
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
})();