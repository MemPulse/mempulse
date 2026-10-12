/**
 * MemPulse read-only Tabulator helper (Tabulator 5.5.0, same build as deck-view.php).
 * Shared by sessions.php and session-details.php.
 *
 * Contains NO Tailwind classes on purpose: every class used in a table cell lives in the
 * PHP view that renders it, so Tailwind always sees it.
 *
 * Usage (from a view, on DOMContentLoaded):
 *   MPTable.applyTokens(document.getElementById('mp-token-probe'));
 *   MPTable.ensure(function () { MPTable.make('#my-table', rows, columns, { rowClick: ... }); });
 */
(function () {
    if (window.MPTable) return;

    var TABULATOR_JS = 'https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js';

    // Structure only. Colours come from CSS variables set by applyTokens().
    var CSS = [
        '.mp-table .tabulator { background: transparent; border: 0; font-family: inherit; font-size: .875rem; color: var(--mp-text, inherit); }',
        '.mp-table .tabulator .tabulator-header { background: var(--mp-head-bg, #f1f1f1) !important; color: var(--mp-head-text, inherit); border-top: 0; border-bottom: 1px solid var(--mp-border, #ddd); }',
        '.mp-table .tabulator .tabulator-header .tabulator-col { background: transparent !important; border-right: 0; }',
        '.mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: .75rem 1.25rem; }',
        '.mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }',
        '.mp-table .tabulator .tabulator-tableholder, .mp-table .tabulator .tabulator-tableholder .tabulator-table { background: transparent; }',
        '.mp-table .tabulator .tabulator-row, .mp-table .tabulator .tabulator-row.tabulator-row-even, .mp-table .tabulator .tabulator-row.tabulator-row-odd { background: transparent !important; color: inherit; border-bottom: 1px solid var(--mp-divider, #eee); min-height: 0; }',
        '.mp-table .tabulator .tabulator-row:hover { background: var(--mp-hover, rgba(0,0,0,.03)) !important; }',
        '.mp-table .tabulator .tabulator-table > .tabulator-row:last-child { border-bottom: 0; }',
        '.mp-table .tabulator .tabulator-row .tabulator-cell { padding: .75rem 1.25rem; border-right: 0; vertical-align: middle; }',
        '.mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-resize-handle { display: none; }'
    ].join('\n');

    var styleEl = document.createElement('style');
    styleEl.id = 'mp-table-styles';
    styleEl.textContent = CSS;
    document.head.appendChild(styleEl);

    // Read the Tailwind design tokens from the probe element and expose them as CSS variables
    function applyTokens(probe) {
        if (!probe) return;
        var q = function (t) { return probe.querySelector('[data-t="' + t + '"]'); };
        var s = document.documentElement.style;
        s.setProperty('--mp-head-bg', getComputedStyle(q('head')).backgroundColor);
        s.setProperty('--mp-head-text', getComputedStyle(q('head')).color);
        s.setProperty('--mp-text', getComputedStyle(q('text')).color);
        s.setProperty('--mp-hover', getComputedStyle(q('hover')).backgroundColor);
        s.setProperty('--mp-border', getComputedStyle(q('border')).borderTopColor);
        s.setProperty('--mp-divider', getComputedStyle(q('divider')).borderTopColor);
    }

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // Load Tabulator only if the page doesn't already have it
    function ensure(done) {
        if (window.Tabulator) return done();
        var sc = document.createElement('script');
        sc.src = TABULATOR_JS;
        sc.onload = done;
        sc.onerror = done; // make() shows the fallback message
        document.head.appendChild(sc);
    }

    // Read-only defaults (headerSort / resizable are COLUMN options in Tabulator 5)
    var base = {
        layout: 'fitColumns',
        responsiveLayout: 'hide',
        movableColumns: false,
        selectable: false,
        selectableRows: false,
        columnDefaults: { headerSort: false, resizable: false }
    };

    function make(selector, rows, columns, extra) {
        var el = document.querySelector(selector);
        if (!el) return null;
        if (typeof Tabulator === 'undefined') {
            console.error('Tabulator failed to load.');
            el.innerHTML = '<p style="padding:2rem;text-align:center;font-size:.875rem;opacity:.7">Table could not be loaded.</p>';
            return null;
        }
        var cols = columns.map(function (c) { return Object.assign({ headerSort: false, resizable: false }, c); });
        return new Tabulator(el, Object.assign({}, base, { data: rows, columns: cols }, extra || {}));
    }

    window.MPTable = { applyTokens: applyTokens, esc: esc, ensure: ensure, make: make };
})();