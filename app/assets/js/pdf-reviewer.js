/**
 * PDF Reviewer: layout switch, remembered options and print button.
 * Works without a network; nothing is sent to the server.
 *
 * Rules for the layout:
 *   1. ?layout=beside|end in the URL wins (server marks it with data-layout-source="url")
 *   2. otherwise the last choice saved in localStorage
 *   3. otherwise "beside"
 */
(function () {
    'use strict';

    var KEY_LAYOUT = 'mempulse.pdfReviewer.layout';
    var KEY_LINES = 'mempulse.pdfReviewer.lines';

    var sheet = document.getElementById('pr-sheet');
    if (!sheet) return;

    var radios = Array.prototype.slice.call(document.querySelectorAll('input[name="pr-layout"]'));
    var linesBox = document.getElementById('pr-lines');
    var linesWrap = document.getElementById('pr-lines-wrap');
    var printBtn = document.getElementById('pr-print-btn');

    // localStorage can throw (private mode, blocked storage): never let that break the page
    function readStore(key) {
        try { return window.localStorage.getItem(key); } catch (e) { return null; }
    }
    function writeStore(key, value) {
        try { window.localStorage.setItem(key, value); } catch (e) { /* ignore */ }
    }

    function applyLayout(layout, persist) {
        if (layout !== 'beside' && layout !== 'end') layout = 'beside';
        sheet.setAttribute('data-layout', layout);

        radios.forEach(function (r) { r.checked = (r.value === layout); });
        if (linesWrap) linesWrap.hidden = (layout === 'beside');   // writing space only matters in "end"

        if (persist) {
            writeStore(KEY_LAYOUT, layout);
            // keep the choice if the student refreshes or shares the link
            try {
                var url = new URL(window.location.href);
                url.searchParams.set('layout', layout);
                window.history.replaceState(null, '', url.toString());
            } catch (e) { /* ignore */ }
        }
    }

    function applyLines(on, persist) {
        sheet.setAttribute('data-lines', on ? '1' : '0');
        if (linesBox) linesBox.checked = on;
        if (persist) writeStore(KEY_LINES, on ? '1' : '0');
    }

    // ---- initial state -------------------------------------------------------
    var layoutFromUrl = document.body.getAttribute('data-layout-source') === 'url';
    var startLayout = sheet.getAttribute('data-layout');
    if (!layoutFromUrl) {
        var saved = readStore(KEY_LAYOUT);
        if (saved === 'beside' || saved === 'end') startLayout = saved;
    }
    applyLayout(startLayout, false);
    applyLines(readStore(KEY_LINES) !== '0', false);

    // ---- events --------------------------------------------------------------
    radios.forEach(function (r) {
        r.addEventListener('change', function () {
            if (r.checked) applyLayout(r.value, true);
        });
    });

    if (linesBox) {
        linesBox.addEventListener('change', function () {
            applyLines(linesBox.checked, true);
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', function () { window.print(); });
    }

    // Optional: ?autoprint=1 opens the print dialog once the page is ready
    if (document.body.getAttribute('data-autoprint') === '1') {
        var ready = (document.fonts && document.fonts.ready) ? document.fonts.ready : Promise.resolve();
        ready.then(function () { window.setTimeout(function () { window.print(); }, 250); });
    }
})();