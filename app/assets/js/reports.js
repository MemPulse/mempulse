/**
 * reports.js (core)
 *
 * - Filter form behaviour (auto-submit, custom date toggle)
 * - Accessible tabs (click, arrow keys, URL ?tab= sync)
 * - Chart registry so each tab builds its charts lazily, and ALL tabs
 *   build/resize their charts before printing.
 *
 * Tab scripts call: RP.register('overview', function () { ...build charts... });
 * and return Chart instances from RP.track(chart).
 */
(function () {
    'use strict';

    var TAB_ORDER = ['overview', 'decks', 'cards', 'sessions'];
    var initFns = {};      // tab -> init function
    var initDone = {};     // tab -> true once initialised
    var charts = [];       // every Chart instance, for resize on print

    var RP = {
        register: function (tab, fn) {
            initFns[tab] = fn;
            // If the tab is already visible when its script loads, init now.
            var panel = document.getElementById('panel-' + tab);
            if (panel && !panel.classList.contains('hidden')) {
                RP.init(tab);
            }
        },
        init: function (tab) {
            if (initDone[tab] || !initFns[tab]) return;
            initDone[tab] = true;
            try { initFns[tab](); } catch (e) { console.error('Reports: init failed for ' + tab, e); }
        },
        track: function (chart) {
            if (chart) charts.push(chart);
            return chart;
        },
        resizeAll: function () {
            charts.forEach(function (c) { try { c.resize(); } catch (e) { /* ignore */ } });
        },
        json: function (id) {
            var el = document.getElementById(id);
            if (!el) return null;
            try { return JSON.parse(el.textContent); } catch (e) { return null; }
        }
    };
    window.RP = RP;

    /* ---------------- Filters ---------------- */
    var form = document.getElementById('rp-filters');
    if (form) {
        var range = document.getElementById('rp-range');
        var custom = document.getElementById('rp-custom-dates');
        var from = document.getElementById('rp-from');
        var to = document.getElementById('rp-to');

        function setCustomVisible(show) {
            custom.classList.toggle('hidden', !show);
            from.disabled = !show;
            to.disabled = !show;
        }

        range.addEventListener('change', function () {
            if (range.value === 'custom') {
                setCustomVisible(true);   // wait for the user to press Apply
            } else {
                setCustomVisible(false);
                form.submit();
            }
        });

        ['rp-deck', 'rp-archived'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', function () { form.submit(); });
        });
    }

    /* ---------------- Tabs ---------------- */
    var tabs = Array.prototype.slice.call(document.querySelectorAll('[role="tab"][data-tab]'));
    var panels = {};
    document.querySelectorAll('.rp-panel').forEach(function (p) { panels[p.dataset.tab] = p; });
    var tabInput = document.getElementById('rp-filter-tab');

    var ACTIVE = ['border-primary', 'text-primary'];
    var INACTIVE = ['border-transparent', 'text-on-surface-variant', 'hover:text-on-surface'];

    function showTab(name, opts) {
        opts = opts || {};
        if (!panels[name]) return;

        tabs.forEach(function (t) {
            var on = t.dataset.tab === name;
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
            ACTIVE.forEach(function (c) { t.classList.toggle(c, on); });
            INACTIVE.forEach(function (c) { t.classList.toggle(c, !on); });
        });

        Object.keys(panels).forEach(function (key) {
            panels[key].classList.toggle('hidden', key !== name);
        });

        if (tabInput) tabInput.value = name;

        // Keep the URL shareable / refresh-safe without a reload.
        var url = new URL(window.location.href);
        url.searchParams.set('tab', name);
        window.history.replaceState(null, '', url.toString());

        // Build charts the first time a tab is shown; resize otherwise
        // (charts drawn in a hidden panel measure 0px wide).
        RP.init(name);
        RP.resizeAll();

        if (opts.focus) {
            var t = tabs.filter(function (x) { return x.dataset.tab === name; })[0];
            if (t) t.focus();
        }
    }

    tabs.forEach(function (t, i) {
        t.addEventListener('click', function (e) {
            e.preventDefault();
            showTab(t.dataset.tab);
        });
        t.addEventListener('keydown', function (e) {
            var idx = null;
            if (e.key === 'ArrowRight') idx = (i + 1) % tabs.length;
            else if (e.key === 'ArrowLeft') idx = (i - 1 + tabs.length) % tabs.length;
            else if (e.key === 'Home') idx = 0;
            else if (e.key === 'End') idx = tabs.length - 1;
            if (idx !== null) {
                e.preventDefault();
                showTab(tabs[idx].dataset.tab, { focus: true });
            }
        });
    });

    /* ---------------- Print ---------------- */
    var printBtn = document.getElementById('rp-print-btn');
    if (printBtn) printBtn.addEventListener('click', function () { window.print(); });

    // Print shows every panel. Canvases drawn at screen size (or inside hidden tabs)
    // print badly, so for printing we: reveal all panels, draw every tab's charts
    // without animation, and swap each canvas for a scalable <img> snapshot.
    var printState = null;

    window.addEventListener('beforeprint', function () {
        if (printState) return;

        var prevAnimation = window.Chart ? Chart.defaults.animation : undefined;
        if (window.Chart) Chart.defaults.animation = false;

        var revealed = [];
        Object.keys(panels).forEach(function (key) {
            if (panels[key].classList.contains('hidden')) {
                panels[key].classList.remove('hidden');
                revealed.push(key);
            }
        });

        TAB_ORDER.forEach(function (t) { RP.init(t); });

        var imgs = [];
        charts.forEach(function (c) {
            try {
                c.stop();
                c.resize();
                c.update('none');
                var img = document.createElement('img');
                img.className = 'rp-chart-img';
                img.alt = c.canvas.getAttribute('aria-label') || 'Chart';
                img.src = c.toBase64Image();
                c.canvas.parentNode.appendChild(img);
                imgs.push(img);
            } catch (e) { /* skip this chart */ }
        });

        document.documentElement.classList.add('rp-printing');
        printState = { revealed: revealed, imgs: imgs, prevAnimation: prevAnimation };
    });

    window.addEventListener('afterprint', function () {
        if (!printState) return;

        printState.imgs.forEach(function (img) { img.remove(); });
        printState.revealed.forEach(function (key) { panels[key].classList.add('hidden'); });
        document.documentElement.classList.remove('rp-printing');
        if (window.Chart) Chart.defaults.animation = printState.prevAnimation;
        printState = null;
        RP.resizeAll();
    });
})();