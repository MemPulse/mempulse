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

// ---- Pagination ----
$perPage    = 15;
$totalCount = SessionService::countCompletedSessions($conn, $userId);
$totalPages = max(1, (int) ceil($totalCount / $perPage));
$page       = isset($_GET['page']) && ctype_digit((string) $_GET['page']) ? (int) $_GET['page'] : 1;
$page       = min(max(1, $page), $totalPages);
$offset     = ($page - 1) * $perPage;

$sessions = SessionService::getSessionHistory($conn, $userId, $perPage, $offset);

$pageTitle   = "Session History";
$currentPage = "review"; // keep sidebar highlight on Review Hub
$h = [ReviewHubService::class, 'h'];

// Page-number window (current page +/- 2)
$firstShown = max(1, $page - 2);
$lastShown  = min($totalPages, $page + 2);

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

    <main class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">

        <div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6">

            <div class="max-w-5xl mx-auto flex flex-col gap-6">

                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-sm font-medium text-on-surface-variant mb-2" aria-label="Breadcrumb">
                    <a href="review.php" class="hover:text-primary transition-colors flex items-center gap-1">
                        <!-- <span class="material-symbols-outlined text-[18px]">home</span> -->
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
                            <?= (int) $totalCount ?> completed <?= $totalCount === 1 ? 'session' : 'sessions' ?>
                        </p>
                    </div>
                </div>

                <!-- Sessions table -->
                <div class="mp-table bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden">

                    <?php if (empty($sessions)): ?>
                        <div class="flex flex-col items-center justify-center p-10 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[36px] text-outline mb-2">history_edu</span>
                            <span class="text-sm font-medium">No review sessions yet.</span>
                            <span class="text-xs mt-1 text-on-surface-variant">Finish a review session and it will show up here.</span>
                            <a href="review.php" class="mt-4 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-bold hover:bg-primary-container transition-colors">Go to Review Hub</a>
                        </div>
                    <?php else: ?>
                        <div id="sessions-table"></div>

                        <?php if ($totalPages > 1): ?>
                            <!-- Pagination -->
                            <nav class="flex items-center justify-between gap-3 px-5 py-3 border-t border-outline-variant/30 bg-surface-container-lowest" aria-label="Pagination">
                                <p class="text-xs text-on-surface-variant">
                                    Page <?= (int) $page ?> of <?= (int) $totalPages ?>
                                </p>
                                <div class="flex items-center gap-1">
                                    <?php if ($page > 1): ?>
                                        <a href="sessions.php?page=<?= $page - 1 ?>" aria-label="Previous page"
                                           class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($p = $firstShown; $p <= $lastShown; $p++): ?>
                                        <?php if ($p === $page): ?>
                                            <span aria-current="page" class="min-w-8 h-8 px-2 rounded-lg flex items-center justify-center bg-primary text-on-primary text-xs font-bold tabular-nums"><?= $p ?></span>
                                        <?php else: ?>
                                            <a href="sessions.php?page=<?= $p ?>"
                                               class="min-w-8 h-8 px-2 rounded-lg flex items-center justify-center text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors tabular-nums"><?= $p ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="sessions.php?page=<?= $page + 1 ?>" aria-label="Next page"
                                           class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                        </a>
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
                { title: 'Deck', field: 'title', widthGrow: 3, minWidth: 150, responsive: 0,
                  formatter: function (c) {
                      var r = c.getRow().getData();
                      return '<a href="' + esc(r.detailsUrl) + '" title="' + esc(r.title) + '" class="block truncate font-semibold text-on-surface hover:text-primary transition-colors">' + esc(r.title) + '</a>';
                  } },
                { title: 'Date', field: 'date', width: 190, responsive: 1, formatter: mutedCell },
                Object.assign({ title: 'Cards', field: 'cards', width: 100, responsive: 2, formatter: mutedCell }, right),
                Object.assign({ title: 'Remembered', field: 'remembered', width: 140, responsive: 4, formatter: mutedCell }, right),
                Object.assign({ title: 'Accuracy', field: 'accuracy', width: 120, responsive: 0,
                  formatter: function (c) {
                      var r = c.getRow().getData();
                      return '<span class="inline-block text-xs font-bold border px-2 py-0.5 rounded-md tabular-nums ' + esc(r.accuracyCls) + '">' + esc(r.accuracy) + '%</span>';
                  } }, right),
                Object.assign({ title: 'Duration', field: 'duration', width: 120, responsive: 3, formatter: mutedCell }, right),
                Object.assign({ title: '', field: 'practiceUrl', width: 160, responsive: 0, formatter: function (c) {
                      var r = c.getRow().getData();
                      if (!r.practiceUrl) return '';
                      return '<a href="' + esc(r.practiceUrl) + '" title="Practice these cards again (nothing is saved)" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-outline-variant text-on-surface text-xs font-bold hover:bg-surface-container-low transition-colors whitespace-nowrap"><span class="material-symbols-outlined text-[16px]">play_arrow</span><span>Practice again</span></a>';
                  } }, right),
                Object.assign({ title: '', field: 'detailsUrl', width: 70, responsive: 0,
                  formatter: function (c) {
                      var r = c.getRow().getData();
                      return '<a href="' + esc(r.detailsUrl) + '" aria-label="View details for ' + esc(r.title) + '" class="inline-flex items-center text-on-surface-variant hover:text-primary transition-colors"><span class="material-symbols-outlined text-[20px]">chevron_right</span></a>';
                  } }, right)
            ], {
                rowClick: function (e, row) {
                    if (e.target.closest('a')) return;               // links handle themselves
                    window.location.href = row.getData().detailsUrl; // anywhere else on the row opens details
                }
            });
        });
    });
    </script>
</body>
</html>