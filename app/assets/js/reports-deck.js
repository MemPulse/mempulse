/**
 * reports-decks.js
 * Decks tab: sortable deck table + two horizontal bar charts.
 * Requires Chart.js and reports.js (core) to load first.
 */
(function () {
    'use strict';
    if (!window.RP) return;

    var COLORS = {
        primarySoft: 'rgba(53, 37, 205, 0.75)',
        teal: '#14b8a6',
        rest: 'rgba(148, 163, 184, 0.55)',
        grid: 'rgba(148, 163, 184, 0.25)',
        text: '#464555'
    };

    /* ---------------- Sortable table ---------------- */
    (function initSort() {
        var table = document.getElementById('rp-decks-table');
        if (!table || !table.tBodies.length) return;

        var tbody = table.tBodies[0];
        var headers = Array.prototype.slice.call(table.querySelectorAll('thead th[data-sort]'));

        function cellValue(row, idx, type) {
            var cell = row.cells[idx];
            var v = cell ? cell.getAttribute('data-value') : '';
            if (type === 'num') {
                var n = parseFloat(v);
                return isNaN(n) ? -Infinity : n;
            }
            return (v || '').toString();
        }

        headers.forEach(function (th) {
            var btn = th.querySelector('button');
            if (!btn) return;

            btn.addEventListener('click', function () {
                var idx = th.cellIndex;
                var type = th.getAttribute('data-sort');
                // Text columns start ascending, numeric columns start descending.
                var current = th.getAttribute('aria-sort');
                var asc;
                if (current === 'none') asc = (type === 'text');
                else asc = (current === 'descending');

                headers.forEach(function (h) { h.setAttribute('aria-sort', 'none'); });
                th.setAttribute('aria-sort', asc ? 'ascending' : 'descending');

                var rows = Array.prototype.slice.call(tbody.rows);
                rows.sort(function (a, b) {
                    var va = cellValue(a, idx, type);
                    var vb = cellValue(b, idx, type);
                    var cmp = (type === 'num') ? (va - vb) : va.localeCompare(vb);
                    if (cmp === 0 || isNaN(cmp)) {
                        cmp = (a.cells[0].getAttribute('data-value') || '').localeCompare(b.cells[0].getAttribute('data-value') || '');
                    }
                    return asc ? cmp : -cmp;
                });
                rows.forEach(function (r) { tbody.appendChild(r); });
            });
        });
    })();

    /* ---------------- Charts ---------------- */
    function truncate(label, max) {
        label = String(label);
        return label.length > max ? label.slice(0, max - 1) + '…' : label;
    }

    function baseOptions() {
        return {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        };
    }

    function yAxis() {
        return {
            grid: { display: false },
            ticks: { color: COLORS.text, autoSkip: false, callback: function (v) { return truncate(this.getLabelForValue(v), 22); } }
        };
    }

    function buildReviews(data) {
        var canvas = document.getElementById('rp-chart-deck-reviews');
        if (!canvas || !data) return;

        var opts = baseOptions();
        opts.scales = {
            x: { beginAtZero: true, grid: { color: COLORS.grid }, ticks: { color: COLORS.text, precision: 0 } },
            y: yAxis()
        };
        opts.plugins.tooltip = {
            callbacks: {
                title: function (items) { return data.labels[items[0].dataIndex]; },
                afterLabel: function (ctx) {
                    var acc = data.accuracy[ctx.dataIndex];
                    return acc === null || acc === undefined ? 'Accuracy: n/a' : 'Accuracy: ' + acc + '%';
                }
            }
        };

        RP.track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{ label: 'Reviewed', data: data.reviews, backgroundColor: COLORS.primarySoft, borderRadius: 4, maxBarThickness: 22 }]
            },
            options: opts
        }));
    }

    function buildMastery(data) {
        var canvas = document.getElementById('rp-chart-deck-mastery');
        if (!canvas || !data) return;

        var opts = baseOptions();
        opts.plugins.legend = { display: true, position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: COLORS.text } };
        opts.scales = {
            x: { stacked: true, beginAtZero: true, grid: { color: COLORS.grid }, ticks: { color: COLORS.text, precision: 0 } },
            y: Object.assign(yAxis(), { stacked: true })
        };
        opts.plugins.tooltip = {
            callbacks: { title: function (items) { return data.labels[items[0].dataIndex]; } }
        };

        RP.track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'Mastered', data: data.mastered, backgroundColor: COLORS.teal, maxBarThickness: 22 },
                    { label: 'Not yet mastered', data: data.rest, backgroundColor: COLORS.rest, maxBarThickness: 22 }
                ]
            },
            options: opts
        }));
    }

    RP.register('decks', function () {
        if (!window.Chart) {
            console.error('Reports: Chart.js did not load.');
            return;
        }
        var payload = RP.json('rp-decks-data') || {};
        buildReviews(payload.reviews);
        buildMastery(payload.mastery);
    });
})();