/**
 * Admin Overview: activity chart (Chart.js) with 7/30/90-day toggle.
 * Reads window.MemPulseAdminOverview = { trend: {labels, reviews, activeUsers, signups}, defaultRange }.
 * Chart.js is loaded before this file (both deferred, in order).
 */
(function () {
    'use strict';

    var data = window.MemPulseAdminOverview;
    var canvas = document.getElementById('overview-chart');
    if (!data || !data.trend || !canvas) return;

    var loading = document.getElementById('overview-chart-loading');
    var status = document.getElementById('overview-chart-status');
    var statusTitle = document.getElementById('overview-chart-status-title');
    var statusText = document.getElementById('overview-chart-status-text');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-range]'));

    var ON = ['bg-primary-fixed', 'text-on-primary-fixed-variant', 'shadow-sm'];
    var OFF = ['text-on-surface-variant', 'hover:bg-surface-container-high'];

    // Series colors (chart.js needs real color values; these match the app's accents).
    var COLORS = { reviews: '#0ea5e9', active: '#14b8a6', signups: '#f59e0b' };

    function showStatus(title, text, hideCanvas) {
        loading.classList.add('hidden');
        statusTitle.textContent = title;
        statusText.textContent = text;
        status.classList.remove('hidden');
        status.classList.add('flex');
        canvas.classList.toggle('invisible', !!hideCanvas);
    }

    function hideStatus() {
        loading.classList.add('hidden');
        status.classList.add('hidden');
        status.classList.remove('flex');
        canvas.classList.remove('invisible');
    }

    if (typeof window.Chart === 'undefined') {
        showStatus("Chart couldn't load", 'Check your connection and refresh the page.', true);
        return;
    }

    var t = data.trend;
    var chart = null;

    function tail(arr, n) { return arr.slice(-n); }

    function fmtDate(iso) {
        var d = new Date(iso + 'T00:00:00');
        return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
    }

    function sum(arr) { return arr.reduce(function (a, b) { return a + b; }, 0); }

    function slice(n) {
        return {
            labels: tail(t.labels, n).map(fmtDate),
            reviews: tail(t.reviews, n),
            active: tail(t.activeUsers, n),
            signups: tail(t.signups, n)
        };
    }

    function setButtons(range) {
        buttons.forEach(function (b) {
            var on = Number(b.getAttribute('data-range')) === range;
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            ON.forEach(function (c) { b.classList.toggle(c, on); });
            OFF.forEach(function (c) { b.classList.toggle(c, !on); });
        });
    }

    function render(range) {
        var s = slice(range);
        setButtons(range);

        if (sum(s.reviews) + sum(s.active) + sum(s.signups) === 0) {
            if (chart) { chart.destroy(); chart = null; }
            showStatus('No activity in this period yet', 'Reviews and sign-ups will be charted here.', true);
            return;
        }
        hideStatus();

        if (chart) {
            chart.data.labels = s.labels;
            chart.data.datasets[0].data = s.reviews;
            chart.data.datasets[1].data = s.active;
            chart.data.datasets[2].data = s.signups;
            chart.update();
            return;
        }

        var text = getComputedStyle(document.body).color || '#64748b';
        var grid = 'rgba(148, 163, 184, 0.2)';

        chart = new Chart(canvas, {
            data: {
                labels: s.labels,
                datasets: [
                    {
                        type: 'bar', label: 'Cards reviewed', data: s.reviews, yAxisID: 'y',
                        backgroundColor: COLORS.reviews + '55', borderColor: COLORS.reviews, borderWidth: 1, borderRadius: 4, order: 3
                    },
                    {
                        type: 'line', label: 'Active learners', data: s.active, yAxisID: 'y1',
                        borderColor: COLORS.active, backgroundColor: COLORS.active, tension: 0.35, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2, order: 1
                    },
                    {
                        type: 'line', label: 'New sign-ups', data: s.signups, yAxisID: 'y1',
                        borderColor: COLORS.signups, backgroundColor: COLORS.signups, tension: 0.35, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2, order: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { color: text, usePointStyle: true, boxWidth: 8, font: { size: 11 } } },
                    tooltip: { padding: 10, cornerRadius: 10 }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: text, maxTicksLimit: 8, maxRotation: 0, font: { size: 10 } } },
                    y: {
                        beginAtZero: true, position: 'left', grid: { color: grid }, border: { display: false },
                        ticks: { color: text, precision: 0, font: { size: 10 } }
                    },
                    y1: {
                        beginAtZero: true, position: 'right', grid: { display: false }, border: { display: false },
                        ticks: { color: text, precision: 0, font: { size: 10 } }
                    }
                }
            }
        });
    }

    buttons.forEach(function (b) {
        b.addEventListener('click', function () { render(Number(b.getAttribute('data-range'))); });
    });

    render(Number(data.defaultRange) || 30);
})();