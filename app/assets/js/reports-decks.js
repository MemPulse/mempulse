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

    /* ---------------- Row click -> drill-down ---------------- */
    (function initRowLinks() {
        var table = document.getElementById('rp-decks-table');
        if (!table || !table.tBodies.length) return;

        table.tBodies[0].addEventListener('click', function (e) {
            if (e.target.closest('a, button')) return;   // let real links work normally
            var row = e.target.closest('tr[data-href]');
            if (row) window.location.href = row.getAttribute('data-href');
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

    function buildTrend(data) {
        var canvas = document.getElementById('rp-chart-deck-trend');
        if (!canvas || !data) return;

        RP.track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        type: 'bar', label: 'Reviewed', data: data.reviewed, backgroundColor: COLORS.primarySoft,
                        borderRadius: 4, maxBarThickness: 28, yAxisID: 'y', order: 2
                    },
                    {
                        type: 'line', label: 'Accuracy', data: data.accuracy, borderColor: COLORS.teal,
                        backgroundColor: COLORS.teal, borderWidth: 2, pointRadius: 3, tension: 0.25,
                        spanGaps: true, yAxisID: 'y1', order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: COLORS.text } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                if (ctx.parsed.y === null) return null;
                                return ctx.dataset.label + ': ' + ctx.parsed.y + (ctx.dataset.yAxisID === 'y1' ? '%' : '');
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.text, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                    y: { beginAtZero: true, grid: { color: COLORS.grid }, ticks: { color: COLORS.text, precision: 0 } },
                    y1: {
                        position: 'right', min: 0, max: 100, grid: { drawOnChartArea: false },
                        ticks: { color: COLORS.text, callback: function (v) { return v + '%'; } }
                    }
                }
            }
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
        buildTrend(payload.trend);
    });
})();