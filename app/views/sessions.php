<?php
/**
 * Session History (list of all completed review sessions)
 * Subpage of Review Hub:  Review Hub > Session History
 * Each row links to session-details.php?id=X
 */
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/SessionService.php';
require_once __DIR__ . '/../api/ReviewHubService.php';

$userId = (int) $_SESSION['user_id'];

// ---- Filters (GET) ----
$validDate = function ($v): string {
    $v = (string) $v;
    $d = DateTime::createFromFormat('Y-m-d', $v);
    return ($d && $d->format('Y-m-d') === $v) ? $v : '';
};
$accOptions = [0 => 'Any accuracy', 50 => '50% or higher', 60 => '60% or higher', 70 => '70% or higher', 80 => '80% or higher', 90 => '90% or higher'];

$q      = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
$from   = $validDate($_GET['from'] ?? '');
$to     = $validDate($_GET['to'] ?? '');
$minAcc = (isset($_GET['acc']) && ctype_digit((string) $_GET['acc']) && isset($accOptions[(int) $_GET['acc']])) ? (int) $_GET['acc'] : 0;
if ($from !== '' && $to !== '' && $from > $to) { [$from, $to] = [$to, $from]; }

$filters    = ['q' => $q, 'from' => $from, 'to' => $to, 'minAccuracy' => $minAcc];
$hasFilters = $q !== '' || $from !== '' || $to !== '' || $minAcc > 0;
$advActive  = ($from !== '' ? 1 : 0) + ($to !== '' ? 1 : 0) + ($minAcc > 0 ? 1 : 0); // filters hidden inside the panel

// ---- Pagination ----
$perPage    = 15;
$totalCount = SessionService::countCompletedSessions($conn, $userId, $filters);
$totalPages = max(1, (int) ceil($totalCount / $perPage));
$page       = isset($_GET['page']) && ctype_digit((string) $_GET['page']) ? (int) $_GET['page'] : 1;
$page       = min(max(1, $page), $totalPages);
$offset     = ($page - 1) * $perPage;

$sessions = SessionService::getSessionHistory($conn, $userId, $perPage, $offset, $filters);

$pageTitle   = "Session History";
$currentPage = "review"; // keep sidebar highlight on Review Hub
$h = [ReviewHubService::class, 'h'];

// Page URLs keep the active filters
$baseQuery = array_filter([
    'q' => $q, 'from' => $from, 'to' => $to, 'acc' => $minAcc > 0 ? (string) $minAcc : '',
], fn($v) => $v !== '');
$pgUrl = function (int $p) use ($baseQuery): string {
    $qs = $baseQuery + ($p > 1 ? ['page' => $p] : []);
    return 'sessions.php' . ($qs ? '?' . http_build_query($qs) : '');
};

$input = 'w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/40';

// ---- Rows for the Tabulator grid (text is pre-formatted here; JS escapes it) ----
$sessionRows = [];
foreach ($sessions as $s) {
    $sessionRows[] = [
        'title'       => (string) $s['deckTitle'],
        'detailsUrl'  => 'session-details.php?id=' . (int) $s['id'],
        // Practice again (read-only replay); sessions with no cards have nothing to replay
        'practiceUrl' => ((int) $s['totalReviewed'] > 0)
            ? 'review.php?mode=practice&from=sessions&session_id=' . (int) $s['id'] : '',
        'date'        => date('M j, Y, g:i A', strtotime($s['startedAt'])),
        'cards'       => (int) $s['totalReviewed'],
        'remembered'  => (int) $s['rememberedCount'],
        'accuracy'    => (int) $s['accuracy'],
        'accuracyCls' => ReviewHubService::accuracyBadgeClass((int) $s['accuracy']),
        'duration'    => ReviewHubService::formatDuration($s['durationSeconds']),
    ];
}
unset($s);
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 transition-all duration-300 ease-in-out">
        <div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
            <div class="flex flex-col w-full gap-4 sm:gap-6">

                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-sm font-medium text-on-surface-variant mb-2" aria-label="Breadcrumb">
                    <a href="review.php" class="hover:text-primary transition-colors flex items-center gap-1">
                        Review
                    </a>
                    <span class="material-symbols-outlined text-[16px] opacity-50">chevron_right</span>
                    <span class="text-on-surface" aria-current="page">Session History</span>
                </nav>

                <!-- Page header -->
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 shadow-sm flex items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-[28px] text-primary">history</span>
                            <h1 class="font-headline-md text-2xl font-bold text-on-surface m-0">Session History</h1>
                        </div>
                        <p class="text-sm text-on-surface-variant">
                            <?php if ($hasFilters): ?>
                                <?= (int) $totalCount ?> matching <?= $totalCount === 1 ? 'session' : 'sessions' ?>
                            <?php else: ?>
                                <?= (int) $totalCount ?> completed <?= $totalCount === 1 ? 'session' : 'sessions' ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Sessions table -->
                <div class="mp-table bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-x-auto">

                    <?php if ($totalCount > 0 || $hasFilters): ?>
                        <!-- Search + filters -->
                        <form id="sess-filter" method="get" action="sessions.php" class="border-b border-outline-variant/30" role="search">
                            <div class="sf-bar">
                                <div class="sf-search">
                                    <span class="material-symbols-outlined text-on-surface-variant" aria-hidden="true">search</span>
                                    <label for="sf-q" class="sr-only">Search by deck title</label>
                                    <input id="sf-q" type="search" name="q" value="<?= $h($q) ?>" maxlength="100" placeholder="Search by deck title…"
                                           class="<?= $input ?> sf-control">
                                </div>

                                <button type="button" id="sess-filter-toggle" aria-expanded="<?= $advActive ? 'true' : 'false' ?>" aria-controls="sess-filter-panel"
                                        class="sf-btn inline-flex items-center gap-1.5 rounded-xl border border-outline-variant/40 bg-surface-container-lowest text-on-surface text-sm font-semibold hover:bg-surface-container-low transition-colors">
                                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">tune</span>
                                    <span>Filters</span>
                                    <?php if ($advActive): ?>
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-primary/10 text-primary tabular-nums"><?= (int) $advActive ?></span>
                                    <?php endif; ?>
                                </button>

                                <button type="submit" class="sf-btn rounded-xl bg-primary text-on-primary text-sm font-semibold hover:opacity-90 transition-opacity">Search</button>

                                <?php if ($hasFilters): ?>
                                    <a href="sessions.php" class="sf-btn inline-flex items-center rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface text-sm font-semibold transition-colors">Reset</a>
                                <?php endif; ?>
                            </div>

                            <div id="sess-filter-panel" class="sf-panel" <?= $advActive ? '' : 'hidden' ?>>
                                <div class="sf-field date">
                                    <label for="sf-from" class="text-xs font-bold text-on-surface-variant">Started From</label>
                                    <input id="sf-from" type="date" name="from" value="<?= $h($from) ?>" class="<?= $input ?> sf-control">
                                </div>
                                <div class="sf-field date">
                                    <label for="sf-to" class="text-xs font-bold text-on-surface-variant">Started To</label>
                                    <input id="sf-to" type="date" name="to" value="<?= $h($to) ?>" class="<?= $input ?> sf-control">
                                </div>
                                <div class="sf-field select">
                                    <label for="sf-acc" class="text-xs font-bold text-on-surface-variant">Accuracy</label>
                                    <select id="sf-acc" name="acc" class="<?= $input ?> sf-control" data-autosubmit>
                                        <?php foreach ($accOptions as $val => $label): ?>
                                            <option value="<?= (int) $val ?>" <?= $minAcc === $val ? 'selected' : '' ?>><?= $h($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (empty($sessions)): ?>
                        <?php if ($hasFilters): ?>
                            <div class="flex flex-col items-center justify-center p-10 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-[36px] text-outline mb-2">search_off</span>
                                <span class="text-sm font-medium text-on-surface">No sessions match these filters.</span>
                                <span class="text-xs mt-1 text-on-surface-variant">Try a different deck title, date range, or accuracy.</span>
                                <a href="sessions.php" class="mt-4 px-4 py-2 rounded-xl bg-primary/10 text-primary text-sm font-bold hover:underline">Reset filters</a>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center p-10 text-center text-on-surface-variant">
                                <span class="material-symbols-outlined text-[36px] text-outline mb-2">history_edu</span>
                                <span class="text-sm font-medium">No review sessions yet.</span>
                                <span class="text-xs mt-1 text-on-surface-variant">Finish a review session and it will show up here.</span>
                                <a href="review.php" class="mt-4 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-bold hover:bg-primary-container transition-colors">Go to Review Hub</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div id="sessions-table"></div>

                        <?php if ($totalPages > 1): ?>
                            <!-- Pagination: Previous · Page [n] of N · Next -->
                            <?php
                            $firstShown = $offset + 1;
                            $lastShown  = min($offset + $perPage, $totalCount);
                            $pgBtn = 'inline-flex items-center gap-1 h-9 px-3 rounded-xl border border-outline-variant/40 text-xs font-semibold transition-colors ';
                            $pgOn  = 'bg-surface-container-lowest text-on-surface hover:bg-surface-container-low hover:text-primary';
                            $pgOff = 'bg-surface-container-lowest text-on-surface-variant/40 pointer-events-none';
                            ?>
                            <nav class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-outline-variant/30 bg-surface-container-lowest" aria-label="Pagination">
                                <p class="text-xs text-on-surface-variant tabular-nums">
                                    Showing <?= number_format($firstShown) ?>&ndash;<?= number_format($lastShown) ?> of <?= number_format($totalCount) ?>
                                </p>

                                <div class="flex items-center gap-2">
                                    <?php if ($page > 1): ?>
                                        <a href="<?= $h($pgUrl($page - 1)) ?>" rel="prev" class="<?= $pgBtn . $pgOn ?>">
                                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">chevron_left</span>Previous
                                        </a>
                                    <?php else: ?>
                                        <span aria-disabled="true" class="<?= $pgBtn . $pgOff ?>">
                                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true">chevron_left</span>Previous
                                        </span>
                                    <?php endif; ?>

                                    <!-- Type a page number and press Enter -->
                                    <form method="get" action="sessions.php" class="flex items-center gap-1.5" title="Type a page number and press Enter">
                                        <?php foreach ($baseQuery as $k => $v): ?>
                                            <input type="hidden" name="<?= $h($k) ?>" value="<?= $h((string) $v) ?>">
                                        <?php endforeach; ?>
                                        <label for="jump-page" class="text-xs text-on-surface-variant">Page</label>
                                        <input id="jump-page" type="number" name="page" min="1" max="<?= (int) $totalPages ?>" value="<?= (int) $page ?>" inputmode="numeric" required
                                               class="w-16 h-9 px-2 rounded-xl border border-outline-variant/40 bg-surface-container-lowest text-sm font-semibold text-on-surface text-center tabular-nums focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/40">
                                        <span class="text-xs text-on-surface-variant tabular-nums">of <?= number_format($totalPages) ?></span>
                                    </form>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="<?= $h($pgUrl($page + 1)) ?>" rel="next" class="<?= $pgBtn . $pgOn ?>">
                                            Next<span class="material-symbols-outlined text-[18px]" aria-hidden="true">chevron_right</span>
                                        </a>
                                    <?php else: ?>
                                        <span aria-disabled="true" class="<?= $pgBtn . $pgOff ?>">
                                            Next<span class="material-symbols-outlined text-[18px]" aria-hidden="true">chevron_right</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>

    <!-- Colour probe: lets the table CSS reuse your Tailwind design tokens -->
    <div id="mp-token-probe" class="hidden" aria-hidden="true">
        <span data-t="head"    class="bg-surface-container text-on-surface-variant"></span>
        <span data-t="text"    class="text-on-surface"></span>
        <span data-t="hover"   class="bg-surface-container-low/50"></span>
        <span data-t="border"  class="border border-outline-variant/30"></span>
        <span data-t="divider" class="border border-outline-variant/20"></span>
    </div>

    <!-- Same Tabulator build + theme as deck-view.php -->
    <link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_simple.min.css" rel="stylesheet">
    <style>
        /* Rows open the session, so they look clickable */
        #sessions-table .tabulator-row,
        #sessions-table .tabulator-row .tabulator-cell { cursor: pointer; }

        /* Search + filter bar (plain CSS so it doesn't depend on compiled Tailwind classes) */
        #sess-filter .sf-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 12px; }
        #sess-filter .sf-search { position: relative; flex: 1 1 240px; min-width: 200px; }
        #sess-filter .sf-search > .material-symbols-outlined {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            font-size: 20px; pointer-events: none;
        }
        #sess-filter .sf-search .sf-control { padding-left: 40px; }
        #sess-filter .sf-control { height: 40px; min-width: 0; }
        #sess-filter .sf-btn { height: 40px; padding: 0 16px; }
        #sess-filter .sf-panel { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px 16px; padding: 0 12px 14px; }
        #sess-filter .sf-panel[hidden] { display: none; }
        #sess-filter .sf-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        #sess-filter .sf-field.date { flex: 0 0 190px; }
        #sess-filter .sf-field.select { flex: 0 1 220px; min-width: 180px; }
        #sess-filter select { padding-right: 2.25rem; }
    </style>

    <script type="application/json" id="sessions-table-data"><?= json_encode($sessionRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
    <script src="../assets/js/mp-tables.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var dataEl = document.getElementById('sessions-table-data');
        if (!dataEl || !document.getElementById('sessions-table')) return;
        var rows = JSON.parse(dataEl.textContent);
        var esc = MPTable.esc;
        var muted = 'text-on-surface-variant tabular-nums whitespace-nowrap';
        var right = { hozAlign: 'right', headerHozAlign: 'right' };
        var mutedCell = function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; };

        MPTable.applyTokens(document.getElementById('mp-token-probe'));
        MPTable.ensure(function () {
            MPTable.make('#sessions-table', rows, [
                { title: 'Deck', field: 'title', widthGrow: 3, minWidth: 120, responsive: 0,
                  formatter: function (c) {
                      var r = c.getRow().getData();
                      return '<a href="' + esc(r.detailsUrl) + '" title="' + esc(r.title) + '" class="block truncate font-semibold text-on-surface hover:text-primary transition-colors">' + esc(r.title) + '</a>';
                  } },
                { title: 'Date', field: 'date', minWidth: 130, responsive: 1, formatter: mutedCell },
                Object.assign({ title: 'Cards', field: 'cards', minWidth: 65, responsive: 2, formatter: mutedCell }, right),
                Object.assign({ title: 'Remembered', field: 'remembered', minWidth: 95, responsive: 4, formatter: mutedCell }, right),
                Object.assign({ title: 'Accuracy', field: 'accuracy', minWidth: 80, responsive: 0,
                  formatter: function (c) {
                      var r = c.getRow().getData();
                      return '<span class="inline-block text-xs font-bold border px-2 py-0.5 rounded-md tabular-nums ' + esc(r.accuracyCls) + '">' + esc(r.accuracy) + '%</span>';
                  } }, right),
                Object.assign({ title: 'Duration', field: 'duration', minWidth: 75, responsive: 3, formatter: mutedCell }, right),
                Object.assign({ title: '', field: 'practiceUrl', minWidth: 115, responsive: 0, formatter: function (c) {
                      var r = c.getRow().getData();
                      if (!r.practiceUrl) return '';
                      return '<a href="' + esc(r.practiceUrl) + '" title="Practice these cards again without affecting spaced repetition history" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-outline-variant bg-primary text-on-primary text-xs shadow-sm hover:bg-primary-container transition-colors whitespace-nowrap"><span class="material-symbols-outlined text-[15px]">visibility</span><span>Preview</span></a>';
                  } }, right),
            ], {
                rowClick: function (e, row) {
                    if (e.target.closest('a')) return;
                    window.location.href = row.getData().detailsUrl;
                }
            });
        });
    });
    </script>
    <script>
    // Filters panel toggle + auto-apply the accuracy dropdown
    (function () {
        var btn = document.getElementById('sess-filter-toggle');
        var panel = document.getElementById('sess-filter-panel');
        if (!btn || !panel) return;
        btn.addEventListener('click', function () {
            var open = panel.hidden;
            panel.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        var acc = document.getElementById('sf-acc');
        if (acc) acc.addEventListener('change', function () { acc.form.submit(); });
    })();
    </script>
</body>
</html>