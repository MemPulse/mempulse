/**
 * reports-cards.js
 * Cards tab charts: maturity doughnut + interval growth lines.
 * Requires Chart.js and reports.js (core) to load first.
 */
(function () {
    'use strict';
    if (!window.RP) return;

    var COLORS = {
        prev: '#94a3b8',
        next: '#3525cd',
        grid: 'rgba(148, 163, 184, 0.25)',
        text: '#464555'
    };

    function buildMaturity(data) {
        var canvas = document.getElementById('rp-chart-maturity');
        if (!canvas || !data) return;

        var total = data.values.reduce(function (a, b) { return a + b; }, 0);

        RP.track(new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{ data: data.values, backgroundColor: data.colors, borderWidth: 2, borderColor: '#ffffff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },   // the HTML legend beside the chart is used instead
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var pct = total > 0 ? Math.round((ctx.parsed / total) * 100) : 0;
                                return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        }));
    }

    function buildInterval(data) {
        var canvas = document.getElementById('rp-chart-interval');
        if (!canvas || !data) return;

        RP.track(new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Interval before review', data: data.prev, borderColor: COLORS.prev, backgroundColor: COLORS.prev,
                        borderWidth: 2, pointRadius: 3, tension: 0.25, spanGaps: true
                    },
                    {
                        label: 'Interval after review', data: data.new, borderColor: COLORS.next, backgroundColor: COLORS.next,
                        borderWidth: 2, pointRadius: 3, tension: 0.25, spanGaps: true
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
                            title: function (items) { return 'Week of ' + items[0].label; },
                            label: function (ctx) {
                                return ctx.parsed.y === null ? null : ctx.dataset.label + ': ' + ctx.parsed.y + ' days';
                            },
                            afterBody: function (items) {
                                var n = data.counts[items[0].dataIndex];
                                return n ? n + ' reviews' : '';
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: COLORS.text, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
                    y: {
                        beginAtZero: true, grid: { color: COLORS.grid }, ticks: { color: COLORS.text, precision: 0 },
                        title: { display: true, text: 'Days', color: COLORS.text }
                    }
                }
            }
        }));
    }

    RP.register('cards', function () {
        if (!window.Chart) {
            console.error('Reports: Chart.js did not load.');
            return;
        }
        var payload = RP.json('rp-cards-data') || {};
        buildMaturity(payload.maturity);
        buildInterval(payload.interval);
    });
})();