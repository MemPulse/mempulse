/**
 * reports-overview.js
 * Builds the Overview tab charts. Requires Chart.js and reports.js (core) to load first.
 */
(function () {
    'use strict';
    if (!window.RP) return;

    var COLORS = {
        primary: '#3525cd',
        primarySoft: 'rgba(53, 37, 205, 0.75)',
        teal: '#0d9488',
        grid: 'rgba(148, 163, 184, 0.25)',
        text: '#464555',
        due: '#ba1a1a'
    };

    function baseOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: COLORS.text } }
            }
        };
    }

    function buildTimeline(data) {
        var canvas = document.getElementById('rp-chart-timeline');
        if (!canvas || !data) return;

        var opts = baseOptions();
        opts.scales = {
            x: { grid: { display: false }, ticks: { color: COLORS.text, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 } },
            y: {
                beginAtZero: true,
                grid: { color: COLORS.grid },
                ticks: { color: COLORS.text, precision: 0 },
                title: { display: true, text: 'Cards reviewed', color: COLORS.text }
            },
            y1: {
                position: 'right',
                min: 0,
                max: 100,
                grid: { drawOnChartArea: false },
                ticks: { color: COLORS.text, callback: function (v) { return v + '%'; } },
                title: { display: true, text: 'Accuracy', color: COLORS.text }
            }
        };
        opts.plugins.tooltip = {
            callbacks: {
                label: function (ctx) {
                    if (ctx.parsed.y === null) return null;
                    return ctx.dataset.label + ': ' + ctx.parsed.y + (ctx.dataset.yAxisID === 'y1' ? '%' : '');
                }
            }
        };

        RP.track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Reviewed',
                        data: data.reviewed,
                        backgroundColor: COLORS.primarySoft,
                        borderRadius: 4,
                        maxBarThickness: 28,
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        type: 'line',
                        label: 'Accuracy',
                        data: data.accuracy,          // null = no reviews that bucket (gap in the line)
                        borderColor: COLORS.teal,
                        backgroundColor: COLORS.teal,
                        borderWidth: 2,
                        pointRadius: 3,
                        tension: 0.25,
                        spanGaps: true,
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: opts
        }));
    }

    function buildForecast(data) {
        var canvas = document.getElementById('rp-chart-forecast');
        if (!canvas || !data) return;

        var opts = baseOptions();
        opts.plugins.legend = { display: false };
        opts.scales = {
            x: { grid: { display: false }, ticks: { color: COLORS.text, maxRotation: 0, autoSkip: false, font: { size: 10 } } },
            y: { beginAtZero: true, grid: { color: COLORS.grid }, ticks: { color: COLORS.text, precision: 0 } }
        };

        // First bar ("Due now") is highlighted.
        var bg = data.values.map(function (_, i) { return i === 0 ? COLORS.due : COLORS.primarySoft; });

        RP.track(new Chart(canvas, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{ label: 'Cards due', data: data.values, backgroundColor: bg, borderRadius: 4, maxBarThickness: 36 }]
            },
            options: opts
        }));
    }

    RP.register('overview', function () {
        if (!window.Chart) {
            console.error('Reports: Chart.js did not load.');
            return;
        }
        var payload = RP.json('rp-overview-data') || {};
        buildTimeline(payload.timeline);
        buildForecast(payload.forecast);
    });
})();