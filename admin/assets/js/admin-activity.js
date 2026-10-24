/**
 * Admin Activity: recall & retention chart (Chart.js) with 7/30/90-day toggle.
 * Mirrors admin-overview.js for visual and behavioral consistency.
 */
(function () {
    'use strict';

    var data = window.MemPulseAdminActivity;
    var canvas = document.getElementById('activity-chart');
    if (!data || !data.trend || !canvas) return;

    var loading = document.getElementById('activity-chart-loading');
    var status = document.getElementById('activity-chart-status');
    var statusTitle = document.getElementById('activity-chart-status-title');
    var statusText = document.getElementById('activity-chart-status-text');
    var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-range]'));
    var seriesButtons = Array.prototype.slice.call(document.querySelectorAll('[data-series]'));
    var seriesIndex = { remembered: 0, forgot: 1, retention: 2 };
    var seriesEnabled = { remembered: true, forgot: true, retention: true };

    var ON = ['bg-primary-fixed', 'text-on-primary-fixed-variant', 'shadow-sm'];
    var OFF = ['text-on-surface-variant', 'hover:bg-surface-container-high'];
    var SERIES_ON = ['shadow-sm'];
    var SERIES_OFF = ['opacity-50'];

    var COLORS = { remembered: '#14b8a6', forgot: '#f59e0b', retention: '#0ea5e9' };

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
            remembered: tail(t.remembered, n),
            forgot: tail(t.forgot, n),
            retention: tail(t.retention, n)
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

    function setSeriesButtons() {
        seriesButtons.forEach(function (b) {
            var key = b.getAttribute('data-series');
            var on = !!seriesEnabled[key];
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
            SERIES_ON.forEach(function (c) { b.classList.toggle(c, on); });
            SERIES_OFF.forEach(function (c) { b.classList.toggle(c, !on); });
        });
    }

    function applySeriesVisibility() {
        if (!chart) return;
        Object.keys(seriesIndex).forEach(function (key) {
            chart.data.datasets[seriesIndex[key]].hidden = !seriesEnabled[key];
        });
        chart.update();
    }

    function render(range) {
        var s = slice(range);
        setButtons(range);

        if (sum(s.remembered) + sum(s.forgot) === 0) {
            if (chart) { chart.destroy(); chart = null; }
            showStatus('No completed reviews in this period yet', 'Completed study sessions will be charted here.', true);
            return;
        }
        hideStatus();

        if (chart) {
            chart.data.labels = s.labels;
            chart.data.datasets[0].data = s.remembered;
            chart.data.datasets[1].data = s.forgot;
            chart.data.datasets[2].data = s.retention;
            Object.keys(seriesIndex).forEach(function (key) {
                chart.data.datasets[seriesIndex[key]].hidden = !seriesEnabled[key];
            });
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
                        type: 'bar', label: 'Remembered', data: s.remembered, yAxisID: 'y',
                        backgroundColor: COLORS.remembered + '66', borderColor: COLORS.remembered, borderWidth: 1, borderRadius: 4, order: 2
                    },
                    {
                        type: 'bar', label: 'Forgot', data: s.forgot, yAxisID: 'y',
                        backgroundColor: COLORS.forgot + '66', borderColor: COLORS.forgot, borderWidth: 1, borderRadius: 4, order: 3
                    },
                    {
                        type: 'line', label: 'Retention %', data: s.retention, yAxisID: 'y1',
                        borderColor: COLORS.retention, backgroundColor: COLORS.retention, tension: 0.35, pointRadius: 0, pointHoverRadius: 4, borderWidth: 2, order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: { padding: 10, cornerRadius: 10 }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: text, maxTicksLimit: 8, maxRotation: 0, font: { size: 10 } } },
                    y: {
                        beginAtZero: true, position: 'left', grid: { color: grid }, border: { display: false },
                        ticks: { color: text, precision: 0, font: { size: 10 } }
                    },
                    y1: {
                        beginAtZero: true, max: 100, position: 'right', grid: { display: false }, border: { display: false },
                        ticks: {
                            color: text, precision: 0, font: { size: 10 },
                            callback: function (v) { return v + '%'; }
                        }
                    }
                }
            }
        });
        Object.keys(seriesIndex).forEach(function (key) {
            chart.data.datasets[seriesIndex[key]].hidden = !seriesEnabled[key];
        });
        chart.update();
    }

    seriesButtons.forEach(function (b) {
        b.addEventListener('click', function () {
            var key = b.getAttribute('data-series');
            if (!Object.prototype.hasOwnProperty.call(seriesEnabled, key)) return;
            seriesEnabled[key] = !seriesEnabled[key];
            setSeriesButtons();
            applySeriesVisibility();
        });
    });

    buttons.forEach(function (b) {
        b.addEventListener('click', function () { render(Number(b.getAttribute('data-range'))); });
    });

    setSeriesButtons();
    render(Number(data.defaultRange) || 30);
})();