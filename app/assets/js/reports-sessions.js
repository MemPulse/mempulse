/**
 * reports-sessions.js
 * Sessions tab charts: completion doughnut + reviews/accuracy by hour and weekday.
 * Requires Chart.js and reports.js (core) to load first.
 */
(function () {
    'use strict';
    if (!window.RP) return;

    var COLORS = {
        primarySoft: 'rgba(53, 37, 205, 0.75)',
        teal: '#0d9488',
        grid: 'rgba(148, 163, 184, 0.25)',
        text: '#464555'
    };

    function buildCompletion(data) {
        var canvas = document.getElementById('rp-chart-completion');
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
                    legend: { display: false },
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

    // Shared by the hour and weekday charts: bars = reviews, line = accuracy.
    function buildActivity(canvasId, data, tickLimit) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !data) return;

        RP.track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        type: 'bar', label: 'Reviewed', data: data.reviews, backgroundColor: COLORS.primarySoft,
                        borderRadius: 4, maxBarThickness: 24, yAxisID: 'y', order: 2
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
                    x: { grid: { display: false }, ticks: { color: COLORS.text, maxRotation: 0, autoSkip: true, maxTicksLimit: tickLimit } },
                    y: { beginAtZero: true, grid: { color: COLORS.grid }, ticks: { color: COLORS.text, precision: 0 } },
                    y1: {
                        position: 'right', min: 0, max: 100, grid: { drawOnChartArea: false },
                        ticks: { color: COLORS.text, callback: function (v) { return v + '%'; } }
                    }
                }
            }
        }));
    }

    RP.register('sessions', function () {
        if (!window.Chart) {
            console.error('Reports: Chart.js did not load.');
            return;
        }
        var payload = RP.json('rp-sessions-data') || {};
        buildCompletion(payload.completion);
        buildActivity('rp-chart-hour', payload.hours, 12);
        buildActivity('rp-chart-weekday', payload.days, 7);
    });
})();