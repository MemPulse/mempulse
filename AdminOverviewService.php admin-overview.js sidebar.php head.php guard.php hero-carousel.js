AdminOverviewService.php
```
<?php
/**
 * AdminOverviewService
 *
 * Read-only data for the admin Overview page. Aggregation and clock math happen
 * in SQL (no PHP/MySQL timezone drift). Every method returns a safe empty
 * structure on failure, never null.
 */
class AdminOverviewService
{
    /** Allowed chart ranges in days (fixed allow-list). */
    public const RANGES = [7, 30, 90];

    /**
     * Platform-wide headline numbers. Only approved, non-deleted users are
     * counted; decks and cards belonging to deleted users are excluded.
     *
     * @return array Keys: available, totalUsers, newUsers7d, pendingUsers, activeToday,
     *               active7d, decks, cards, reviews7d, reviewsPrev7d, retention30d.
     */
    public static function getMetrics(mysqli $conn): array
    {
        $empty = [
            'available' => false, 'totalUsers' => 0, 'newUsers7d' => 0, 'pendingUsers' => 0,
            'activeToday' => 0, 'active7d' => 0, 'decks' => 0, 'cards' => 0,
            'reviews7d' => 0, 'reviewsPrev7d' => 0, 'retention30d' => 0.0,
        ];

        $live = "deleted_at IS NULL AND account_status = 'approved'";
        $sql = "SELECT
            (SELECT COUNT(*) FROM users WHERE $live) AS total_users,
            (SELECT COUNT(*) FROM users WHERE $live AND created_at >= NOW() - INTERVAL 7 DAY) AS new_users_7d,
            (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND account_status = 'pending') AS pending_users,
            (SELECT COUNT(DISTINCT user_id) FROM review_sessions WHERE started_at >= CURDATE()) AS active_today,
            (SELECT COUNT(DISTINCT user_id) FROM review_sessions WHERE started_at >= NOW() - INTERVAL 7 DAY) AS active_7d,
            (SELECT COUNT(*) FROM decks d JOIN users u ON u.id = d.user_id
              WHERE d.deleted_at IS NULL AND u.deleted_at IS NULL) AS decks,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL) AS cards,
            (SELECT COALESCE(SUM(total_reviewed), 0) FROM review_sessions
              WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 7 DAY) AS reviews_7d,
            (SELECT COALESCE(SUM(total_reviewed), 0) FROM review_sessions
              WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 14 DAY
                AND started_at < NOW() - INTERVAL 7 DAY) AS reviews_prev_7d,
            (SELECT COALESCE(ROUND(SUM(remembered_count) / NULLIF(SUM(total_reviewed), 0) * 100, 1), 0)
               FROM review_sessions
              WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 30 DAY) AS retention_30d";

        $res = mysqli_query($conn, $sql); // static SQL, no user input
        if (!$res) {
            return $empty;
        }
        $r = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if (!$r) {
            return $empty;
        }

        return [
            'available'     => true,
            'totalUsers'    => (int) $r['total_users'],
            'newUsers7d'    => (int) $r['new_users_7d'],
            'pendingUsers'  => (int) $r['pending_users'],
            'activeToday'   => (int) $r['active_today'],
            'active7d'      => (int) $r['active_7d'],
            'decks'         => (int) $r['decks'],
            'cards'         => (int) $r['cards'],
            'reviews7d'     => (int) $r['reviews_7d'],
            'reviewsPrev7d' => (int) $r['reviews_prev_7d'],
            'retention30d'  => (float) $r['retention_30d'],
        ];
    }

    /**
     * Daily series with empty days filled so the chart axis is continuous.
     *
     * @param int $days One of self::RANGES (anything else falls back to 30).
     * @return array Keys: labels (Y-m-d), reviews, activeUsers, signups (parallel int arrays).
     */
    public static function getTrend(mysqli $conn, int $days = 30): array
    {
        $days = in_array($days, self::RANGES, true) ? $days : 30;
        $out = ['labels' => [], 'reviews' => [], 'activeUsers' => [], 'signups' => []];

        $today = null;
        if ($res = mysqli_query($conn, 'SELECT CURDATE()')) {
            $row = mysqli_fetch_row($res);
            $today = $row[0] ?? null;
            mysqli_free_result($res);
        }
        if (!$today) {
            return $out;
        }

        $offset = $days - 1;
        $sessions = self::fetchRows($conn,
            "SELECT DATE(started_at) AS d, SUM(total_reviewed) AS reviews, COUNT(DISTINCT user_id) AS active
             FROM review_sessions
             WHERE ended_at IS NOT NULL AND started_at >= CURDATE() - INTERVAL ? DAY
             GROUP BY d", 'i', [$offset]);
        $signups = self::fetchRows($conn,
            "SELECT DATE(created_at) AS d, COUNT(*) AS signups
             FROM users
             WHERE deleted_at IS NULL AND created_at >= CURDATE() - INTERVAL ? DAY
             GROUP BY d", 'i', [$offset]);

        $byDay = [];
        foreach ($sessions as $s) {
            $byDay[$s['d']]['reviews'] = (int) $s['reviews'];
            $byDay[$s['d']]['active']  = (int) $s['active'];
        }
        foreach ($signups as $s) {
            $byDay[$s['d']]['signups'] = (int) $s['signups'];
        }

        $end = new DateTimeImmutable($today);
        for ($i = $offset; $i >= 0; $i--) {
            $key = $end->modify("-$i days")->format('Y-m-d');
            $out['labels'][]      = $key;
            $out['reviews'][]     = $byDay[$key]['reviews'] ?? 0;
            $out['activeUsers'][] = $byDay[$key]['active'] ?? 0;
            $out['signups'][]     = $byDay[$key]['signups'] ?? 0;
        }
        return $out;
    }

    /**
     * Newest accounts (any status), for the sign-ups feed.
     *
     * @return array<int, array{id:int,name:string,email:string,role:string,status:string,age:string}>
     */
    public static function getRecentSignups(mysqli $conn, int $limit = 6): array
    {
        return self::userRows($conn, '', 'u.created_at DESC', $limit);
    }

    /**
     * Accounts waiting for approval, oldest first (the longest-waiting person is first in the queue).
     *
     * @return array<int, array{id:int,name:string,email:string,role:string,status:string,age:string}>
     */
    public static function getPendingUsers(mysqli $conn, int $limit = 5): array
    {
        return self::userRows($conn, "AND u.account_status = 'pending'", 'u.created_at ASC', $limit);
    }

    /**
     * Latest completed sessions platform-wide. Aggregates only: no deck titles or card content.
     *
     * @return array<int, array{name:string,cardsReviewed:int,accuracy:int,age:string}>
     */
    public static function getRecentSessions(mysqli $conn, int $limit = 6): array
    {
        $limit = max(1, min($limit, 50));
        $rows = self::fetchRows($conn,
            "SELECT u.first_name, u.last_name, u.email, rs.total_reviewed, rs.accuracy_percentage,
                    TIMESTAMPDIFF(SECOND, rs.started_at, NOW()) AS age_seconds
             FROM review_sessions rs
             JOIN users u ON u.id = rs.user_id
             WHERE rs.ended_at IS NOT NULL AND u.deleted_at IS NULL
             ORDER BY rs.started_at DESC LIMIT ?", 'i', [$limit]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'name'          => self::displayName($r),
                'cardsReviewed' => (int) $r['total_reviewed'],
                'accuracy'      => (int) round((float) $r['accuracy_percentage']),
                'age'           => self::timeAgo((int) $r['age_seconds']),
            ];
        }
        return $out;
    }

    /**
     * Percentage change chip data, or null when there is nothing to compare.
     *
     * @return array{text:string,tone:string}|null tone is up, down or flat.
     */
    public static function percentChip(int $current, int $previous, string $suffix): ?array
    {
        if ($previous === 0) {
            return $current > 0 ? ['text' => 'New activity', 'tone' => 'up'] : null;
        }
        $pct = (int) round((($current - $previous) / $previous) * 100);
        if ($pct === 0) {
            return ['text' => '0% ' . $suffix, 'tone' => 'flat'];
        }
        return ['text' => ($pct > 0 ? '+' : '') . $pct . '% ' . $suffix, 'tone' => $pct > 0 ? 'up' : 'down'];
    }

    /** Human-friendly relative time from a seconds-ago value computed in SQL. */
    public static function timeAgo(int $seconds): string
    {
        if ($seconds < 60)    return 'Just now';
        if ($seconds < 3600)  return floor($seconds / 60) . ' min ago';
        if ($seconds < 86400) return floor($seconds / 3600) . ' hr ago';
        $d = (int) floor($seconds / 86400);
        return $d . ($d === 1 ? ' day ago' : ' days ago');
    }

    // ---- internals --------------------------------------------------------

    private static function userRows(mysqli $conn, string $extraWhere, string $order, int $limit): array
    {
        $limit = max(1, min($limit, 50));
        // $extraWhere and $order are fixed strings from the two callers above, never user input.
        $rows = self::fetchRows($conn,
            "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.account_status,
                    TIMESTAMPDIFF(SECOND, u.created_at, NOW()) AS age_seconds
             FROM users u
             WHERE u.deleted_at IS NULL $extraWhere
             ORDER BY $order LIMIT ?", 'i', [$limit]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'     => (int) $r['id'],
                'name'   => self::displayName($r),
                'email'  => (string) $r['email'],
                'role'   => (string) $r['role'],
                'status' => (string) $r['account_status'],
                'age'    => self::timeAgo((int) $r['age_seconds']),
            ];
        }
        return $out;
    }

    private static function displayName(array $r): string
    {
        $n = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        return $n !== '' ? $n : (string) ($r['email'] ?? 'Unknown user');
    }

    private static function fetchRows(mysqli $conn, string $sql, string $types = '', array $params = []): array
    {
        $rows = [];
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return $rows;
        }
        if ($types !== '') {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            while ($res && ($row = mysqli_fetch_assoc($res))) {
                $rows[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }
}
```

admin-overview.js
```
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
    var seriesButtons = Array.prototype.slice.call(document.querySelectorAll('[data-series]'));
    var seriesIndex = { reviews: 0, active: 1, signups: 2 };
    var seriesEnabled = { reviews: true, active: true, signups: true };

    var ON = ['bg-primary-fixed', 'text-on-primary-fixed-variant', 'shadow-sm'];
    var OFF = ['text-on-surface-variant', 'hover:bg-surface-container-high'];
    var SERIES_ON = ['shadow-sm'];
    var SERIES_OFF = ['opacity-50'];

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
                        beginAtZero: true, position: 'right', grid: { display: false }, border: { display: false },
                        ticks: { color: text, precision: 0, font: { size: 10 } }
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
```

sidebar.php
```
<?php
// Expects guard.php to be included ($displayName, $displayRole) and $currentPage set by the page.
$currentPage = $currentPage ?? '';

// One place to define navigation. Set 'ready' => true when the page exists.
$adminNav = [
    ['key' => 'overview', 'label' => 'Overview',  'icon' => 'dashboard',     'href' => 'views/index.php',    'ready' => true],
    ['key' => 'users',    'label' => 'Users',     'icon' => 'group',         'href' => 'views/users.php',    'ready' => true],
    ['key' => 'content',  'label' => 'Content',   'icon' => 'library_books', 'href' => 'views/content.php',  'ready' => false],
    ['key' => 'activity', 'label' => 'Activity',  'icon' => 'monitoring',    'href' => 'views/activity.php', 'ready' => false],
    ['key' => 'audit',    'label' => 'Audit log', 'icon' => 'history',       'href' => 'views/audit.php',    'ready' => false],
];

$navBase     = 'sidebar-tooltip sidebar-row sidebar-gap flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group';
$navActive   = 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm';
$navInactive = 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface';
?>

<style>
    /* Same collapsed-sidebar and tooltip behavior as the app sidebar
       (trimmed: no settings modal, no review badge). */
    #sidebar { box-sizing: border-box; overflow-x: visible; overflow-y: visible; }
    #sidebar.collapsed .hide-collapsed { display: none !important; }
    #sidebar.collapsed .sidebar-row {
        box-sizing: border-box !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex: 0 0 44px !important;
        width: 44px !important;
        min-width: 44px !important;
        max-width: 44px !important;
        height: 44px !important;
        min-height: 44px !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }
    #sidebar.collapsed .sidebar-gap { gap: 0 !important; }
    #sidebar-account-controls { min-width: 0; }
    #sidebar.collapsed #profile-popover-trigger .material-symbols-outlined { font-size: 28px !important; }
    #sidebar.collapsed #settings-popover { left: calc(100% + 12px); right: auto; bottom: 0; }

    #sidebar-floating-tooltip {
        position: fixed; z-index: 99999;
        max-width: min(280px, calc(100vw - 24px));
        padding: 6px 10px; border-radius: 8px;
        background: #1e293b; color: #fff;
        font-size: 12px; font-weight: 500; line-height: 1.4; white-space: nowrap;
        pointer-events: none; opacity: 0; visibility: hidden;
        transform: translateY(-50%);
        box-shadow: 0 4px 10px rgba(0, 0, 0, .18);
        transition: opacity .12s ease;
    }
    #sidebar-floating-tooltip.is-visible { opacity: 1; visibility: visible; }
    #sidebar-floating-tooltip::before {
        content: ''; position: absolute; top: 50%; left: -8px; transform: translateY(-50%);
        border: 4px solid transparent; border-right-color: #1e293b;
    }
    #sidebar-floating-tooltip.tooltip-left::before {
        left: auto; right: -8px; border-right-color: transparent; border-left-color: #1e293b;
    }

    @keyframes memory-pulse {
        0%, 100% { background-color: rgba(56, 189, 248, 0.10); }
        50% { background-color: rgba(56, 189, 248, 0); }
    }
    .memory-pulse-bg { animation: memory-pulse 2.5s ease-in-out infinite; border-radius: 50%; padding: 4px; margin-left: -4px; }
</style>

<!-- Floating Mobile Menu Button -->
<button id="floating-mobile-toggle" aria-label="Open Sidebar" class="md:hidden fixed top-4 left-4 z-40 p-2.5 rounded-xl bg-surface-container-lowest shadow-md border border-outline-variant/30 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-low transition-colors">
    <span class="material-symbols-outlined text-[24px]">menu</span>
</button>

<!-- Mobile Overlay -->
<div id="mobile-sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>

<!-- MAIN SIDEBAR CONTAINER -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-50 flex flex-col transition-all duration-300 ease-in-out transform -translate-x-full md:translate-x-0 shadow-[0_1px_8px_rgba(0,0,0,0.03)]">

    <!-- Prevent flash of the expanded state -->
    <script>
        if (localStorage.getItem("sidebarCollapsed") === "true" && window.innerWidth >= 768) {
            document.getElementById("sidebar").classList.add("collapsed");
        }
    </script>

    <!-- Border Toggle Button (Desktop Only) -->
    <button id="sidebar-toggle" aria-label="Toggle Sidebar" class="hidden md:flex absolute -right-3.5 top-8 w-7 h-7 bg-surface-container-lowest border border-outline-variant/40 text-on-surface-variant rounded-full items-center justify-center hover:text-primary hover:border-primary shadow-sm transition-colors z-[60]">
        <span class="material-symbols-outlined text-[16px]">left_panel_close</span>
    </button>

    <!-- Logo + Admin badge -->
    <div class="flex flex-col gap-4 px-4 pt-6 pb-2">
        <div class="sidebar-row flex items-center gap-3 h-8">
            <span class="material-symbols-outlined text-[28px] text-primary memory-pulse-bg">neurology</span>
            <span class="hide-collapsed font-headline-sm text-xl font-bold tracking-tight text-on-surface">MemPulse</span>
            <span class="hide-collapsed px-2 py-0.5 rounded-full bg-primary-fixed text-on-primary-fixed-variant text-[10px] font-bold uppercase tracking-wider">Admin</span>
        </div>
    </div>

    <hr class="mx-4 border-0 border-t border-outline-variant/40">

    <!-- Navigation -->
    <nav class="sidebar-navigation flex-1 overflow-y-auto px-4 py-2 flex flex-col gap-1.5" aria-label="Admin navigation">
        <?php foreach ($adminNav as $item): $active = $currentPage === $item['key']; ?>
            <?php if ($item['ready']): ?>
                <a data-tooltip="<?= adminEsc($item['label']) ?>"
                   aria-current="<?= $active ? 'page' : 'false' ?>"
                   class="<?= $navBase ?> <?= $active ? $navActive : $navInactive ?>"
                   href="<?= ADMIN_URL . $item['href'] ?>">
                    <div class="flex items-center gap-3 sidebar-gap">
                        <span class="material-symbols-outlined text-[24px] <?= $active ? '' : 'text-on-surface-variant group-hover:text-on-surface' ?> transition-colors"><?= $item['icon'] ?></span>
                        <span class="hide-collapsed"><?= adminEsc($item['label']) ?></span>
                    </div>
                </a>
            <?php else: ?>
                <div data-tooltip="<?= adminEsc($item['label']) ?> (coming soon)"
                     aria-disabled="true"
                     class="<?= $navBase ?> <?= $navInactive ?> opacity-50 cursor-not-allowed hover:bg-transparent">
                    <div class="flex items-center gap-3 sidebar-gap">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant"><?= $item['icon'] ?></span>
                        <span class="hide-collapsed"><?= adminEsc($item['label']) ?></span>
                    </div>
                    <span class="hide-collapsed px-2 py-0.5 rounded-full bg-surface-container-low border border-outline-variant/30 text-[10px] font-semibold uppercase tracking-wide">Soon</span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom: back to app + account -->
    <div class="border-t border-outline-variant/30 p-4 relative flex flex-col gap-3">
        <a data-tooltip="Back to MemPulse app"
           href="<?= APP_URL ?>views/index.php"
           class="sidebar-tooltip sidebar-row sidebar-gap flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors">
            <span class="material-symbols-outlined text-[24px]">arrow_back</span>
            <span class="hide-collapsed">Back to app</span>
        </a>

        <div id="sidebar-account-controls" class="flex items-center gap-2">
            <button type="button" id="profile-popover-trigger"
                class="sidebar-tooltip sidebar-row flex items-center gap-3 text-left group hover:opacity-80 transition-opacity flex-1 min-w-0 outline-none rounded-xl"
                data-tooltip="Account menu" title="Account menu" aria-label="Open account menu"
                aria-haspopup="true" aria-controls="settings-popover" aria-expanded="false">
                <span class="material-symbols-outlined text-[32px] flex-shrink-0 text-primary transition-all group-hover:scale-105">account_circle</span>
                <div class="flex flex-col min-w-0 hide-collapsed">
                    <span class="font-label-sm font-semibold text-on-surface leading-tight truncate"><?= adminEsc($displayName) ?></span>
                    <span class="text-[11px] text-on-surface-variant font-medium leading-none tracking-wide mt-1"><?= adminEsc($displayRole) ?></span>
                </div>
            </button>
        </div>

        <!-- Account menu -->
        <div id="settings-popover"
            class="hidden absolute bottom-[calc(100%+8px)] right-4 w-52 bg-surface-container-lowest border border-outline-variant/30 shadow-xl rounded-xl py-1.5 z-[100] transform origin-bottom-right">
            <div class="px-3 py-2 border-b border-outline-variant/20 mb-1">
                <p class="text-xs font-semibold text-on-surface truncate"><?= adminEsc($displayName) ?></p>
                <p class="text-[10px] text-on-surface-variant truncate"><?= adminEsc($adminUser['email']) ?></p>
            </div>
            <a href="<?= APP_URL ?>views/index.php"
               class="w-full flex items-center gap-3 px-4 py-2 text-sm font-medium text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined text-[18px]">school</span>
                Student app
            </a>
            <div class="h-px bg-outline-variant/20 my-1"></div>
            <button type="button" id="logout-btn"
                class="w-full flex items-center gap-3 px-4 py-2 text-sm font-medium text-error hover:bg-error-container/40 transition-colors text-left">
                <span class="material-symbols-outlined text-[18px]">logout</span>
                Logout
            </button>
        </div>
    </div>
</aside>

<!-- Logout Confirmation Dialog -->
<dialog id="logout-dialog" class="rounded-2xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs transition-all">
    <div class="p-6 flex flex-col gap-5">
        <h3 class="font-headline-sm text-lg font-bold text-on-surface tracking-tight">Log out?</h3>
        <p class="text-sm text-on-surface-variant font-medium leading-relaxed">
            You will need to sign in again to access the admin area.
        </p>
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
            <button type="button" id="logout-cancel" class="px-4 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-sm transition-colors">Cancel</button>
            <a href="/mempulse/authentication/logout.php" class="inline-flex items-center px-4 py-2 rounded-xl bg-error hover:opacity-90 text-on-error font-semibold text-sm shadow-sm transition-colors">Log out</a>
        </div>
    </div>
</dialog>

<script src="<?= ADMIN_URL ?>assets/js/admin-sidebar.js?v=<?= @filemtime(__DIR__ . '/../assets/js/admin-sidebar.js') ?: time() ?>" defer></script>
```

head.php
```
<?php
// Expects admin/includes/guard.php to be included already ($adminTheme, APP_URL, ADMIN_URL).
$pageTitle = $pageTitle ?? 'Overview';

// Shared styles live in app/ so the two areas can never drift apart.
$sharedCss = __DIR__ . '/../../app/assets/css/';
$cssVersion = function (string $file) use ($sharedCss) {
    return @filemtime($sharedCss . $file) ?: time();
};
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <script>
        (function () {
            var root = document.documentElement;

            // The saved preference lives in the database; mirror it into localStorage
            // so a fresh browser starts with the right theme (same key the app uses).
            try {
                localStorage.setItem('mempulseTheme', <?= json_encode($adminTheme) ?>);
            } catch (e) {}

            // Apply the theme before first paint (identical to app/includes/head.php).
            var pref = 'system';
            try {
                pref = localStorage.getItem('mempulseTheme') || 'system';
            } catch (e) {}

            var dark = pref === 'dark' ||
                (pref === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)').matches);

            root.classList.toggle('dark', dark);
            root.style.colorScheme = dark ? 'dark' : 'light';

            // Apply the sidebar preference before first paint.
            try {
                var collapsed =
                    window.innerWidth >= 768 &&
                    localStorage.getItem('sidebarCollapsed') === 'true';
                root.classList.toggle('sidebar-collapsed', collapsed);
            } catch (e) {
                root.classList.remove('sidebar-collapsed');
            }
        })();
    </script>

    <title><?= adminEsc($pageTitle) ?> | MemPulse Admin</title>

    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Plus+Jakarta+Sans:wght@600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />

    <!-- Shared design system, loaded from the app folder -->
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/app.css">
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/tailwind.css?v=<?= $cssVersion('tailwind.css') ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/theme.css?v=<?= $cssVersion('theme.css') ?>">
</head>
```

guard.php
```
<?php
/**
 * Admin guard. Include FIRST on every admin page and endpoint, before any output.
 *
 * What it does:
 *  1. Defines URL constants (admin, app, login).
 *  2. Loads the database connection.
 *  3. Verifies the user EVERY request against the database (never trusts a cached role).
 *  4. Exposes $adminUser, $displayName, $displayRole, $adminTheme to the page.
 *  5. Provides CSRF helpers for state-changing requests.
 *
 * JSON endpoints: define('ADMIN_JSON', true) BEFORE including this file so that
 * failures return a JSON 401/403 instead of a redirect.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_URL', '/mempulse/app/');
define('ADMIN_URL', '/mempulse/admin/');
define('LOGIN_URL', '/mempulse/authentication/login.php');

// config/db.php defines BASE_URL as the *app* path when it is not already set.
// Claim it for the admin area BEFORE loading the connection.
if (!defined('BASE_URL')) {
    define('BASE_URL', ADMIN_URL);
}

require_once __DIR__ . '/../../config/db.php';

// Admin pages must never be cached (stops the Back button showing them after logout).
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

/**
 * Stop the request. Pages redirect; JSON endpoints answer with a status code.
 */
function adminDeny(int $status, string $message, string $redirect): void
{
    if (defined('ADMIN_JSON') && ADMIN_JSON) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
    } else {
        header('Location: ' . $redirect);
    }
    exit;
}

/** Escape a value for HTML output. */
function adminEsc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---- 1. Must have a logged-in session ------------------------------------
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || empty($_SESSION['user_id'])) {
    adminDeny(401, 'Please log in.', LOGIN_URL);
}

$userId = (int) $_SESSION['user_id'];

// ---- 2. Verify the account and role against the database -------------------
$sql = "SELECT u.first_name, u.last_name, u.email, u.role, u.account_status, s.theme
        FROM users u
        LEFT JOIN user_settings s ON s.user_id = u.id
        WHERE u.id = ? AND u.deleted_at IS NULL";

$row = null;
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $firstName, $lastName, $email, $role, $accountStatus, $theme);
    if (mysqli_stmt_fetch($stmt)) {
        $row = [
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'email'     => $email,
            'role'      => $role,
            'status'    => $accountStatus,
            'theme'     => $theme,
        ];
    }
    mysqli_stmt_close($stmt);
}

// Account missing, soft-deleted, or not approved: treat as logged out.
if ($row === null || $row['status'] !== 'approved') {
    adminDeny(401, 'Your session is no longer valid.', LOGIN_URL);
}

// Logged in, but not an admin: send back to the student app.
if ($row['role'] !== 'admin') {
    adminDeny(403, 'You do not have access to this area.', APP_URL . 'views/index.php');
}

// ---- 3. Values shared with pages and includes ----------------------------
$fullName = trim($row['firstName'] . ' ' . $row['lastName']);

$adminUser = [
    'id'    => $userId,
    'name'  => $fullName !== '' ? $fullName : 'Administrator',
    'email' => $row['email'],
    'role'  => 'admin',
];

$displayName = $adminUser['name'];
$displayRole = 'Admin';
$adminTheme  = in_array($row['theme'], ['light', 'dark', 'system'], true) ? $row['theme'] : 'system';

// ---- 4. CSRF (separate token from the student settings token) -------------
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

function adminCsrfToken(): string
{
    return $_SESSION['admin_csrf'];
}

/**
 * Call at the top of every state-changing endpoint, after the method check.
 * Accepts the token from the X-CSRF-Token header or a csrf_token POST field.
 */
function adminRequireCsrf(): void
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!is_string($sent) || $sent === '' || !hash_equals($_SESSION['admin_csrf'], $sent)) {
        adminDeny(403, 'Invalid security token. Refresh the page and try again.', ADMIN_URL . 'views/index.php');
    }
}
```

hero-carousel.js
```
/**
 * Hero Carousel Management Module
 * Standardized module for managing slide transitions, autoplay timers,
 * keyboard accessibility, and wayfinder dot state synchronization.
 */
(($) => {
    'use strict';

    class HeroCarousel {
        constructor(carouselElement, autoPlayDelay = 7000) {
            if (!carouselElement) return;

            this.carousel = carouselElement;
            this.delay = autoPlayDelay;
            this.slides = Array.from(this.carousel.querySelectorAll('.hero-slide'));
            this.dotGroups = this.slides.map(slide =>
                Array.from(slide.querySelectorAll('.hero-dot'))
            );

            this.currentIndex = 0;
            this.timer = null;

            this.init();
        }

        init() {
            if (this.slides.length === 0) return;

            this.bindEvents();
            this.goToSlide(0, false);
            this.startAutoplay();
        }

        goToSlide(index, resetTimer = true) {
            this.currentIndex = (index + this.slides.length) % this.slides.length;

            // Update slide visibilities & accessibility attributes
            this.slides.forEach((slide, i) => {
                const isActive = i === this.currentIndex;
                slide.classList.toggle('opacity-100', isActive);
                slide.classList.toggle('z-10', isActive);
                slide.classList.toggle('opacity-0', !isActive);
                slide.classList.toggle('pointer-events-none', !isActive);
                slide.classList.toggle('z-0', !isActive);

                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            // Update wayfinder dot indicators across all duplicated slide footers
            this.dotGroups.forEach((group) => {
                group.forEach((dot, dotIdx) => {
                    const isActive = dotIdx === this.currentIndex;
                    dot.classList.toggle('bg-cyan-400', isActive);
                    dot.classList.toggle('w-6', isActive);
                    dot.classList.toggle('bg-white/30', !isActive);
                    dot.classList.toggle('w-2', !isActive);

                    dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
            });

            if (resetTimer) {
                this.startAutoplay();
            }
        }

        startAutoplay() {
            this.stopAutoplay();
            this.timer = setInterval(() => {
                this.goToSlide(this.currentIndex + 1, false);
            }, this.delay);
        }

        stopAutoplay() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        }

        bindEvents() {
            // Click handlers on wayfinder dots
            this.dotGroups.forEach((group) => {
                group.forEach((dot) => {
                    dot.addEventListener('click', (e) => {
                        e.preventDefault();
                        const targetIndex = parseInt(dot.dataset.dotIndex, 10);
                        if (!isNaN(targetIndex)) {
                            this.goToSlide(targetIndex);
                        }
                    });
                });
            });

            // Accessibility: Pause autoplay on hover and focus
            this.carousel.addEventListener('mouseenter', () => this.stopAutoplay());
            this.carousel.addEventListener('mouseleave', () => this.startAutoplay());
            this.carousel.addEventListener('focusin', () => this.stopAutoplay());
            this.carousel.addEventListener('focusout', (e) => {
                if (!this.carousel.contains(e.relatedTarget)) {
                    this.startAutoplay();
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const carouselEl = document.getElementById('hero-carousel');
        if (carouselEl) {
            new HeroCarousel(carouselEl, 7000);
        }
    });
})(window);
```

