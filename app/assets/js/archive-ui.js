/**
 * MemPulse shared archive UI helpers (toast + retention label).
 * Used by the deck page (cards) and reusable by the decks page (decks).
 * Reads retentionDays from window.MemPulseDeckData or window.MemPulseArchiveConfig.
 */
(function () {
    const cfg = window.MemPulseDeckData || window.MemPulseArchiveConfig || {};
    const RETENTION_DAYS = Number(cfg.retentionDays) || 30;

    function retentionLabel(daysLeft) {
        if (daysLeft === null || daysLeft === undefined) return '';
        if (daysLeft <= 0) return 'Today';
        return daysLeft + (daysLeft === 1 ? ' day' : ' days');
    }

    function toast(message, opts = {}) {
        const { actionLabel, onAction, type = 'info', duration = 6000 } = opts;

        let root = document.getElementById('mp-toast-root');
        if (!root) {
            root = document.createElement('div');
            root.id = 'mp-toast-root';
            root.className = 'fixed bottom-4 left-1/2 -translate-x-1/2 z-[100] flex flex-col items-center gap-2 px-4';
            document.body.appendChild(root);
        }

        const el = document.createElement('div');
        el.setAttribute('role', 'status');
        el.className = 'flex items-center gap-3 pl-4 pr-2 py-2 rounded-xl shadow-xl text-sm font-medium '
            + (type === 'error' ? 'bg-rose-700 text-white' : 'bg-inverse-surface text-inverse-on-surface');

        const text = document.createElement('span');
        text.textContent = message;
        el.appendChild(text);

        const remove = () => el.remove();

        if (actionLabel && typeof onAction === 'function') {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = actionLabel;
            btn.className = 'px-2.5 py-1 rounded-lg font-semibold text-primary-fixed-dim hover:bg-white/10';
            btn.addEventListener('click', () => { remove(); onAction(); });
            el.appendChild(btn);
        }

        root.appendChild(el);
        setTimeout(remove, duration);
    }

    window.MemPulseArchive = { RETENTION_DAYS, retentionLabel, toast };
})();