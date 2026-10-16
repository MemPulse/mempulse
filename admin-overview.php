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
```

index.php
```
<?php
// 1. Guard first: verifies session + admin role against the database, loads db.php.
require_once __DIR__ . '/../includes/guard.php';
require_once __DIR__ . '/../api/AdminOverviewService.php';

// 2. Fetch all data up front; the markup contains no queries.
$metrics        = AdminOverviewService::getMetrics($conn);
$trend          = AdminOverviewService::getTrend($conn, 90); // sliced client-side for 7/30/90
$pendingUsers   = AdminOverviewService::getPendingUsers($conn, 5);
$recentSignups  = AdminOverviewService::getRecentSignups($conn, 6);
$recentSessions = AdminOverviewService::getRecentSessions($conn, 6);

// 3. Page settings used by head.php and sidebar.php.
$pageTitle   = 'Overview';
$currentPage = 'overview';

$jsPayload = ['trend' => $trend, 'defaultRange' => 30];
$jsVersion = @filemtime(__DIR__ . '/../assets/js/admin-overview.js') ?: time();
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/overview-view.php'; ?>
    </main>

    <script>
        window.MemPulseAdminOverview = <?= json_encode($jsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <!-- Pin this to the same Chart.js version the reports page uses. -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
    <script src="<?= ADMIN_URL ?>assets/js/admin-overview.js?v=<?= $jsVersion ?>" defer></script>
</body>
</html>
```

overview-view.php
```
<?php
// Expects (from index.php): $metrics, $pendingUsers, $recentSignups, $recentSessions.
$m   = $metrics;
$fmt = fn($n) => number_format((int) $n);

$initial = function (string $name): string {
    $c = trim($name) === '' ? '?' : (function_exists('mb_substr') ? mb_substr(trim($name), 0, 1) : substr(trim($name), 0, 1));
    return function_exists('mb_strtoupper') ? mb_strtoupper($c) : strtoupper($c);
};

// Full class strings (not built dynamically) so the Tailwind scan finds them.
$accents = [
    'primary' => ['from' => 'from-primary/10',    'blob' => 'bg-primary/10 group-hover:bg-primary/20',       'label' => 'text-primary',    'badge' => 'bg-primary/10 border-primary/20 text-primary'],
    'teal'    => ['from' => 'from-teal-500/10',   'blob' => 'bg-teal-500/10 group-hover:bg-teal-500/20',     'label' => 'text-teal-700',   'badge' => 'bg-teal-50 border-teal-200/80 text-teal-600'],
    'amber'   => ['from' => 'from-amber-500/10',  'blob' => 'bg-amber-500/10 group-hover:bg-amber-500/20',   'label' => 'text-amber-700',  'badge' => 'bg-amber-50 border-amber-200/80 text-amber-600'],
    'indigo'  => ['from' => 'from-indigo-500/10', 'blob' => 'bg-indigo-500/10 group-hover:bg-indigo-500/20', 'label' => 'text-indigo-700', 'badge' => 'bg-indigo-50 border-indigo-200/80 text-indigo-600'],
    'violet'  => ['from' => 'from-violet-500/10', 'blob' => 'bg-violet-500/10 group-hover:bg-violet-500/20', 'label' => 'text-violet-700', 'badge' => 'bg-violet-50 border-violet-200/80 text-violet-600'],
    'cyan'    => ['from' => 'from-cyan-500/10',   'blob' => 'bg-cyan-500/10 group-hover:bg-cyan-500/20',     'label' => 'text-cyan-700',   'badge' => 'bg-cyan-50 border-cyan-200/80 text-cyan-600'],
];
$tones = [
    'up'   => 'bg-teal-50 text-teal-700',
    'down' => 'bg-error-container/40 text-error',
    'flat' => 'bg-surface-container-low text-on-surface-variant',
];
$statusBadge = [
    'approved' => 'bg-teal-50 text-teal-700 border-teal-200/80',
    'pending'  => 'bg-amber-50 text-amber-700 border-amber-200/80',
    'rejected' => 'bg-error-container/40 text-error border-error/20',
];

$newUsersChip = ['text' => '+' . $fmt($m['newUsers7d']) . ' this week', 'tone' => $m['newUsers7d'] > 0 ? 'up' : 'flat'];

$cards = [
    ['accent' => 'primary', 'label' => 'Users',     'icon' => 'group',          'value' => $fmt($m['totalUsers']),   'caption' => 'Approved accounts',
     'tip' => 'Approved accounts that have not been deleted.', 'chip' => $newUsersChip],
    ['accent' => 'teal',    'label' => 'Active',    'icon' => 'bolt',           'value' => $fmt($m['activeToday']),  'caption' => $fmt($m['active7d']) . ' active in 7 days',
     'tip' => 'Distinct learners who started a study session today.', 'chip' => null],
    ['accent' => 'amber',   'label' => 'Pending',   'icon' => 'hourglass_top',  'value' => $fmt($m['pendingUsers']), 'caption' => 'Awaiting approval',
     'tip' => 'Accounts registered but not yet approved.', 'chip' => null],
    ['accent' => 'indigo',  'label' => 'Decks',     'icon' => 'style',          'value' => $fmt($m['decks']),        'caption' => 'Active decks',
     'tip' => 'Decks that have not been deleted, owned by active users.', 'chip' => null],
    ['accent' => 'violet',  'label' => 'Flashcards', 'icon' => 'layers',        'value' => $fmt($m['cards']),        'caption' => 'Across all decks',
     'tip' => 'Cards that have not been deleted, in active decks.', 'chip' => null],
    ['accent' => 'cyan',    'label' => 'Reviews',   'icon' => 'check_circle',   'value' => $fmt($m['reviews7d']),    'caption' => $m['retention30d'] . '% retention (30 days)',
     'tip' => 'Cards reviewed in completed sessions over the last 7 days.', 'chip' => AdminOverviewService::percentChip($m['reviews7d'], $m['reviewsPrev7d'], 'vs prior 7d')],
];

$panel = 'bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 sm:p-5 shadow-sm ring-1 ring-black/5 dark:ring-white/5 min-w-0';
$row   = 'flex items-center justify-between gap-3 p-3 rounded-xl bg-surface-container-low border border-outline-variant/40';
?>
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <header class="flex flex-col gap-1">
            <h1 class="font-headline-md text-headline-lg-mobile sm:text-headline-lg font-bold tracking-tight text-on-surface">Admin Overview</h1>
            <p class="text-sm text-on-surface-variant">Platform health at a glance. Study activity is shown as aggregates only.</p>
        </header>

        <?php if (!$m['available']): ?>
            <div role="alert" class="flex items-start gap-3 p-4 rounded-2xl bg-error-container/40 border border-error/20 text-sm text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-error">error</span>
                <span>We couldn't load the platform metrics. Refresh the page, and check the server log if it keeps happening.</span>
            </div>
        <?php endif; ?>

        <!-- Metric cards -->
        <section aria-label="Key metrics" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
            <?php foreach ($cards as $c): $a = $accents[$c['accent']]; ?>
                <div class="bg-gradient-to-br <?= $a['from'] ?> via-surface-container-lowest to-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 sm:p-5 shadow-sm ring-1 ring-black/5 dark:ring-white/5 flex flex-col justify-between h-32 sm:h-36 relative group hover:border-primary/40 hover:shadow-md transition-all">
                    <div class="absolute right-0 bottom-0 w-20 h-20 <?= $a['blob'] ?> rounded-br-2xl rounded-tl-full blur-md pointer-events-none transition-all"></div>

                    <div class="flex items-center justify-between w-full relative z-10">
                        <div class="flex items-center gap-1.5">
                            <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider <?= $a['label'] ?>"><?= adminEsc($c['label']) ?></span>
                            <div class="relative group/tooltip flex items-center">
                                <span class="material-symbols-outlined text-[15px] text-on-surface-variant/60 cursor-help hover:text-on-surface transition-colors" aria-hidden="true">info</span>
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                    <?= adminEsc($c['tip']) ?>
                                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                </div>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-xl border <?= $a['badge'] ?> flex items-center justify-center flex-shrink-0 shadow-xs">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true"><?= $c['icon'] ?></span>
                        </div>
                    </div>

                    <div class="flex items-end justify-between gap-2 mt-2 relative z-10">
                        <div class="flex flex-col min-w-0">
                            <span class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface"><?= adminEsc($c['value']) ?></span>
                            <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5 truncate"><?= adminEsc($c['caption']) ?></span>
                        </div>
                        <?php if ($c['chip']): ?>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md whitespace-nowrap <?= $tones[$c['chip']['tone']] ?>"><?= adminEsc($c['chip']['text']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Activity chart + approval queue -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch">

            <section class="lg:col-span-8 <?= $panel ?> flex flex-col" aria-labelledby="activity-title">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">monitoring</span>
                            <h2 id="activity-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Platform activity</h2>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Reviews, active learners and sign-ups per day</p>
                    </div>
                    <div class="inline-flex p-1 rounded-xl bg-surface-container-low border border-outline-variant/30" role="group" aria-label="Chart range">
                        <?php foreach ([7, 30, 90] as $r): ?>
                            <button type="button" data-range="<?= $r ?>" aria-pressed="false"
                                class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><?= $r ?>d</button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="relative w-full h-64 sm:h-72 lg:h-80 min-w-0">
                    <div id="overview-chart-loading" class="absolute inset-0 flex items-center justify-center text-sm text-on-surface-variant">Loading chart…</div>
                    <div id="overview-chart-status" class="hidden absolute inset-0 flex-col items-center justify-center text-center text-on-surface-variant gap-1 p-6 z-10">
                        <span class="material-symbols-outlined text-[32px] text-outline" aria-hidden="true">insert_chart</span>
                        <span id="overview-chart-status-title" class="text-sm font-medium"></span>
                        <span id="overview-chart-status-text" class="text-xs text-on-surface-variant/70"></span>
                    </div>
                    <canvas id="overview-chart" role="img" aria-label="Daily reviews, active learners and sign-ups"></canvas>
                </div>
            </section>

            <section class="lg:col-span-4 <?= $panel ?> flex flex-col" aria-labelledby="pending-title">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600 text-[22px]" aria-hidden="true">how_to_reg</span>
                            <h2 id="pending-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Needs approval</h2>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Longest-waiting first</p>
                    </div>
                    <?php if ($m['pendingUsers'] > 0): ?>
                        <span class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-md"><?= $fmt($m['pendingUsers']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-2.5 flex-1 <?= $pendingUsers ? '' : 'justify-center' ?>">
                    <?php if ($pendingUsers): foreach ($pendingUsers as $u): ?>
                        <div class="<?= $row ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0 text-xs font-bold"><?= adminEsc($initial($u['name'])) ?></div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($u['name']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($u['email']) ?></span>
                                </div>
                            </div>
                            <span class="text-[10px] text-on-surface-variant/70 whitespace-nowrap"><?= adminEsc($u['age']) ?></span>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">task_alt</span>
                            <span class="text-sm font-medium">You're all caught up.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">New sign-ups awaiting approval will appear here.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Recent sign-ups + recent sessions -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch">

            <section class="lg:col-span-6 <?= $panel ?>" aria-labelledby="signups-title">
                <div class="mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">person_add</span>
                        <h2 id="signups-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent sign-ups</h2>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Newest accounts, any status</p>
                </div>
                <div class="flex flex-col gap-2.5">
                    <?php if ($recentSignups): foreach ($recentSignups as $u): ?>
                        <div class="<?= $row ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-xs font-bold"><?= adminEsc($initial($u['name'])) ?></div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($u['name']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($u['email']) ?></span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border <?= $statusBadge[$u['status']] ?? $statusBadge['pending'] ?>"><?= adminEsc($u['status']) ?></span>
                                <span class="text-[10px] text-on-surface-variant/70"><?= adminEsc($u['age']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">group_off</span>
                            <span class="text-sm font-medium">No accounts yet.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lg:col-span-6 <?= $panel ?>" aria-labelledby="sessions-title">
                <div class="mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">history</span>
                        <h2 id="sessions-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent study sessions</h2>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Completed sessions across the platform</p>
                </div>
                <div class="flex flex-col gap-2.5">
                    <?php if ($recentSessions): foreach ($recentSessions as $s): ?>
                        <div class="<?= $row ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">style</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($s['name']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant"><?= (int) $s['cardsReviewed'] ?> cards reviewed</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end flex-shrink-0">
                                <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md"><?= (int) $s['accuracy'] ?>%</span>
                                <span class="text-[10px] text-on-surface-variant/70 mt-1"><?= adminEsc($s['age']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">history_edu</span>
                            <span class="text-sm font-medium">No completed sessions yet.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">Sessions appear here once learners finish studying.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    </div>
</div>
```

