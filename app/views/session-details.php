<?php
/**
 * Session Details (single session)
 * Subpage of Session History:  Review Hub > Session History > Session Details
 * This is the OLD sessions.php, renamed.
 */
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/SessionService.php';
require_once __DIR__ . '/../api/ReviewHubService.php';

$userId    = (int) $_SESSION['user_id'];
$sessionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$session = SessionService::getSessionDetails($conn, $sessionId, $userId);
if (!$session) {
    header("Location: sessions.php"); // invalid id or belongs to someone else -> back to the history list
    exit;
}

$logs = SessionService::getSessionLogs($conn, $sessionId);

$pageTitle   = "Session Details";
$currentPage = "review"; // Keep navigation active on Review Hub
$h = [ReviewHubService::class, 'h'];

// ---- Rows for the Tabulator grid (text is pre-formatted here; JS escapes it) ----
$logRows = [];
foreach ($logs as $log) {
    $secs = $log['nextDueSeconds'];
    $logRows[] = [
        'front'     => (string) $log['front'],
        'back'      => (string) $log['back'],
        'isDeleted' => (bool) $log['isDeleted'],
        'remembered'=> $log['rating'] === 'remembered',
        'isDueNow'  => $secs !== null && $secs <= 0,
        'nextText'  => $secs === null ? '—' : ($secs <= 0 ? 'Due Now' : 'in ' . ReviewHubService::formatSpan($secs)),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
            <div class="flex flex-col w-full gap-4 sm:gap-6">                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-sm font-medium text-on-surface-variant mb-2" aria-label="Breadcrumb">
                    <a href="review.php" class="hover:text-primary transition-colors flex items-center gap-1">
                        <!-- <span class="material-symbols-outlined text-[18px]">home</span> -->
                        Review
                    </a>
                    <span class="material-symbols-outlined text-[16px] opacity-50">chevron_right</span>
                    <a href="sessions.php" class="hover:text-primary transition-colors">Session History</a>
                    <span class="material-symbols-outlined text-[16px] opacity-50">chevron_right</span>
                    <span class="text-on-surface" aria-current="page">Session Details</span>
                </nav>

                <!-- Session Metadata Header -->
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-[28px] text-primary">history_edu</span>
                            <h1 class="font-headline-md text-2xl font-bold text-on-surface m-0">Session Details</h1>
                        </div>
                        <p class="text-sm text-on-surface-variant">
                            Deck: <a href="deck.php?deck_id=<?= (int) $session['deckId'] ?>" class="font-semibold text-on-surface hover:text-primary hover:underline transition-colors"><?= $h($session['deckTitle']) ?></a>
                            <span class="mx-2">•</span>
                            <?= $h(date('M j, Y, g:i A', strtotime($session['startedAt']))) ?>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="bg-surface-container-high px-4 py-2.5 rounded-xl border border-outline-variant/30 flex flex-col items-center min-w-[80px]">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Duration</span>
                            <span class="text-lg font-extrabold text-on-surface mt-0.5 tabular-nums"><?= $h(ReviewHubService::formatDuration($session['durationSeconds'])) ?></span>
                        </div>
                        <div class="bg-surface-container-high px-4 py-2.5 rounded-xl border border-outline-variant/30 flex flex-col items-center min-w-[80px]">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Cards</span>
                            <span class="text-lg font-extrabold text-on-surface mt-0.5 tabular-nums"><?= (int) $session['totalReviewed'] ?></span>
                        </div>
                        <div class="<?= ReviewHubService::accuracyBadgeClass($session['accuracy']) ?> px-4 py-2.5 rounded-xl border flex flex-col items-center min-w-[80px]">
                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">Accuracy</span>
                            <span class="text-lg font-extrabold mt-0.5 tabular-nums"><?= (int) $session['accuracy'] ?>%</span>
                        </div>
                        <?php if (!empty($logRows)): ?>
                            <!-- Practice again: read-only replay of this session's cards -->
                            <a href="review.php?mode=practice&amp;from=session&amp;session_id=<?= (int) $sessionId ?>"
                               title="Practice these cards again (nothing is saved)"
                               class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-primary text-on-primary text-sm font-bold shadow-sm hover:bg-primary-container transition-colors whitespace-nowrap">
                                <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                                Practice again
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cards Reviewed: title above the table, table below -->
                <section class="flex flex-col gap-4 w-full min-w-0" id="cards-reviewed-section">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <h2 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface whitespace-nowrap">Cards Reviewed</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold tracking-wide shadow-xs whitespace-nowrap">
                            <?= count($logRows) ?> <?= count($logRows) === 1 ? 'Card' : 'Cards' ?>
                        </span>
                    </div>

                    <?php if (empty($logRows)): ?>
                        <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs py-10 text-center text-on-surface-variant text-sm">
                            No card records were saved for this session.
                        </div>
                    <?php else: ?>
                        <div class="mp-table rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs overflow-hidden">
                            <div id="session-cards-table"></div>
                        </div>
                    <?php endif; ?>
                </section>

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
        /* Read-only records: plain cursor */
        #session-cards-table .tabulator-row,
        #session-cards-table .tabulator-row .tabulator-cell { cursor: default; }
    </style>

    <script type="application/json" id="session-cards-data"><?= json_encode($logRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
    <script src="../assets/js/mp-tables.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var dataEl = document.getElementById('session-cards-data');
        if (!dataEl || !document.getElementById('session-cards-table')) return;
        var rows = JSON.parse(dataEl.textContent);
        var esc = MPTable.esc;

        // Card text: keep line breaks, show at most 2 lines, full text on hover
        var textCell = function (key, extraCls) {
            return function (c) {
                var r = c.getRow().getData();
                var badge = (key === 'front' && r.isDeleted)
                    ? '<span class="inline-block mb-1 text-[10px] font-bold uppercase text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">Deleted</span>'
                    : '';
                return badge + '<div class="line-clamp-2 whitespace-pre-line break-words ' + extraCls + '" title="' + esc(r[key]) + '">' + esc(r[key]) + '</div>';
            };
        };

        MPTable.applyTokens(document.getElementById('mp-token-probe'));
        MPTable.ensure(function () {
            MPTable.make('#session-cards-table', rows, [
                { title: 'Front', field: 'front', widthGrow: 3, minWidth: 160, responsive: 0, formatter: textCell('front', '') },
                { title: 'Back',  field: 'back',  widthGrow: 3, minWidth: 160, responsive: 1, formatter: textCell('back', 'text-on-surface-variant') },
                { title: 'Result', field: 'remembered', width: 160, responsive: 0,
                  formatter: function (c) {
                      return c.getValue()
                          ? '<span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-md"><span class="material-symbols-outlined text-[14px]">check_circle</span>Remembered</span>'
                          : '<span class="inline-flex items-center gap-1 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-1 rounded-md"><span class="material-symbols-outlined text-[14px]">cancel</span>Forgotten</span>';
                  } },
                { title: 'Next Due', field: 'nextText', width: 140, hozAlign: 'right', headerHozAlign: 'right', responsive: 2,
                  formatter: function (c) {
                      var r = c.getRow().getData();
                      return r.isDueNow
                          ? '<span class="text-rose-600 font-medium">Due Now</span>'
                          : '<span class="text-on-surface-variant tabular-nums whitespace-nowrap">' + esc(r.nextText) + '</span>';
                  } }
            ]);
        });
    });
    </script>
</body>
</html>