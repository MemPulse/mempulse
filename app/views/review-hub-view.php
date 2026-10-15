<?php
/**
 * Review Hub view (STATE A of review.php)
 *
 * Expects these variables from review.php:
 *   $dueDecks, $upcomingDecks, $recentSessions  (see ReviewHubService)
 *   $dueTotal, $nextDueSecs, $curStreak, $reviewedToday
 */

$h = [ReviewHubService::class, 'h'];

// ---- Due Now call-to-action state ----
$dueDeckCount = count($dueDecks);
$reviewHref   = $dueDeckCount > 0 ? 'review.php?deck_id=' . (int) $dueDecks[0]['id'] : 'review.php';
$streakAtRisk = $dueTotal > 0 && $curStreak > 0 && $reviewedToday === 0;

if ($streakAtRisk) {
    $dueSubtitle = 'Keep your ' . $curStreak . '-day streak alive';
} elseif ($dueDeckCount === 1) {
    $dueSubtitle = 'in ' . $dueDecks[0]['title'];
} elseif ($dueDeckCount > 1) {
    $dueSubtitle = 'Starting with ' . $dueDecks[0]['title'] . ' (+' . ($dueDeckCount - 1) . ' more)';
} else {
    $dueSubtitle = '';
}

$nextDueLabel = ($nextDueSecs !== null && $nextDueSecs > 0)
    ? 'Next cards due in ' . ReviewHubService::formatSpan($nextDueSecs)
    : '';
?>
<!-- ========================================================================= -->
<!-- STATE A: REVIEW HUB (dashboard-style layout, table lists)                 -->
<!-- ========================================================================= -->
<div class="flex flex-col w-full gap-6 sm:gap-8">

    <!-- DIV: ROW 1 - Due Now call-to-action (left) vs Keyboard controls (right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">

        <!-- DIV: LEFT - Due Now call-to-action -->
        <div class="lg:col-span-6 flex w-full min-w-0">

            <?php if ($dueTotal > 0): ?>
                <!-- A: Cards due - primary call-to-action -->
                <a href="<?= $h($reviewHref) ?>"
                   id="stat-due-cta"
                   aria-label="Review <?= (int) $dueTotal ?> due <?= $dueTotal === 1 ? 'card' : 'cards' ?>"
                   class="group/cta relative overflow-hidden flex items-center justify-between gap-4 w-full h-full min-h-[84px] px-4 sm:px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-on-primary shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                    <div class="absolute -right-6 -top-8 w-28 h-28 bg-white/15 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="relative z-10 flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-[22px]">alarm</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-primary/80">Due Now</span>
                            <span class="text-base sm:text-lg font-extrabold leading-tight truncate">
                                <span id="stat-due-cta-count"><?= (int) $dueTotal ?></span>
                                <?= $dueTotal === 1 ? 'card' : 'cards' ?> ready to review
                            </span>
                            <?php if ($dueSubtitle !== ''): ?>
                                <span class="text-[11px] sm:text-xs text-on-primary/80 truncate"><?= $h($dueSubtitle) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="relative z-10 inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl bg-white text-primary font-label-md text-xs font-bold shadow-xs flex-shrink-0">
                        <span class="hidden sm:inline">Start Review</span>
                        <span class="material-symbols-outlined text-[18px] transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                    </span>
                </a>

            <?php elseif ($nextDueSecs !== null): ?>
                <!-- B: Nothing due, cards are scheduled -->
                <div id="stat-due-cta" class="flex items-center gap-3 w-full h-full min-h-[84px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 flex-shrink-0">
                        <span class="material-symbols-outlined text-[22px]">task_alt</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                        <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface">All caught up</span>
                        <?php if ($nextDueLabel !== ''): ?>
                            <span class="text-[11px] sm:text-xs text-on-surface-variant"><?= $h($nextDueLabel) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- C: No scheduled cards at all - point to creation -->
                <a href="decks.php?add_card=1" id="stat-due-cta"
                   class="group/cta flex items-center justify-between gap-4 w-full h-full min-h-[84px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs hover:border-primary/50 hover:shadow-md transition-all">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                            <span class="material-symbols-outlined text-[22px]">add</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                            <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface truncate">Create your first cards</span>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-[20px] text-primary flex-shrink-0 transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                </a>
            <?php endif; ?>

        </div>

        <!-- DIV: RIGHT - Keyboard controls (desktop only: useless on touch screens) -->
        <div class="lg:col-span-6 hidden lg:flex w-full min-w-0">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs w-full flex flex-col justify-center">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-[22px]">keyboard</span>
                    <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Review Keyboard Controls</h3>
                </div>
                <ul class="grid grid-cols-2 gap-x-6 gap-y-2.5 text-sm text-on-surface-variant">
                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border border-outline-variant/40 font-mono text-xs">Space</kbd> Show Answer / Remember</li>
                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border border-outline-variant/40 font-mono text-xs">F</kbd> Mark as Forgotten</li>
                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border border-outline-variant/40 font-mono text-xs">←</kbd> Previous Card</li>
                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border border-outline-variant/40 font-mono text-xs">→</kbd> Next Card</li>
                </ul>
            </div>
        </div>
    </div>

<?php
/* ------------------------------------------------------------------------
 * Table data for Tabulator (read-only). All text is pre-formatted here so the
 * JS only has to place it; the JS escapes every string before rendering.
 * ---------------------------------------------------------------------- */
$dueRows = [];
foreach ($dueDecks as $d) {
    $dueRows[] = [
        'title'      => (string) $d['title'],
        'deckUrl'    => 'deck.php?deck_id=' . (int) $d['id'],
        'reviewUrl'  => 'review.php?deck_id=' . (int) $d['id'],
        'dueCount'   => (int) $d['dueCount'],
        'oldestText' => $d['overdueSeconds'] === null ? '—' : ReviewHubService::formatSpan($d['overdueSeconds']) . ' ago',
        'totalCards' => (int) $d['totalCards'],
    ];
}

$upcomingRows = [];
foreach ($upcomingDecks as $d) {
    $upcomingRows[] = [
        'title'         => (string) $d['title'],
        'deckUrl'       => 'deck.php?deck_id=' . (int) $d['id'],
        'isDueNow'      => $d['dueCount'] > 0,
        'upcomingCount' => (int) $d['upcomingCount'],
        'nextText'      => $d['nextDueSeconds'] === null ? '—' : 'in ' . ReviewHubService::formatSpan($d['nextDueSeconds']),
    ];
}

$recentRows = [];
foreach ($recentSessions as $s) {
    $recentRows[] = [
        'title'       => (string) $s['deckTitle'],
        'detailsUrl'  => 'session-details.php?id=' . (int) $s['sessionID'],
        'practiceUrl' => ((int) $s['cardsReviewed'] > 0)
            ? 'review.php?mode=practice&from=sessions&session_id=' . (int) $s['sessionID'] : '',
        'cards'       => (int) $s['cardsReviewed'],
        'accuracy'    => (int) $s['accuracy'],
        'accuracyCls' => ReviewHubService::accuracyBadgeClass((int) $s['accuracy']),
        'duration'    => ReviewHubService::formatDuration($s['durationSeconds']),
        'when'        => (string) $s['formattedTime'],
    ];
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

$badgeCls = 'px-2.5 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold tracking-wide shadow-xs whitespace-nowrap';
?>

    <!-- Colour probe: lets the table CSS reuse your Tailwind design tokens (read once by the script below) -->
    <div id="mp-token-probe" class="hidden" aria-hidden="true">
        <span data-t="head"    class="bg-surface-container text-on-surface-variant"></span>
        <span data-t="text"    class="text-on-surface"></span>
        <span data-t="hover"   class="bg-surface-container-low/50"></span>
        <span data-t="card"    class="bg-surface-container-lowest"></span>
        <span data-t="border"  class="border border-outline-variant/30"></span>
        <span data-t="divider" class="border border-outline-variant/20"></span>
    </div>

    <!-- ===================================================================== -->
    <!-- DUE NOW  (title above the table, table below)                          -->
    <!-- ===================================================================== -->
    <section class="flex flex-col gap-4 w-full min-w-0" id="due-now-section">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2.5 min-w-0">
                <h2 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface whitespace-nowrap">Due Now</h2>
                <span class="<?= $badgeCls ?>"><?= count($dueRows) ?> <?= count($dueRows) === 1 ? 'Deck' : 'Decks' ?></span>
                <span class="hidden sm:block w-px h-4 bg-outline-variant shrink-0" aria-hidden="true"></span>
                <p class="hidden sm:block truncate text-sm text-on-surface-variant m-0">Decks with cards ready to review</p>
            </div>
        </div>

        <?php if (empty($dueRows)): ?>
            <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs py-10 text-center text-on-surface-variant text-sm">
                You are all caught up for now!
            </div>
        <?php else: ?>
            <div class="mp-table rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs overflow-hidden">
                <div id="due-now-table"></div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ===================================================================== -->
    <!-- UPCOMING                                                               -->
    <!-- ===================================================================== -->
    <section class="flex flex-col gap-4 w-full min-w-0" id="upcoming-section">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2.5 min-w-0">
                <h2 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface whitespace-nowrap">Upcoming (Next <?= (int) (ReviewHubService::UPCOMING_DAYS * 24) ?>h)</h2>
                <span class="<?= $badgeCls ?>"><?= count($upcomingRows) ?> <?= count($upcomingRows) === 1 ? 'Deck' : 'Decks' ?></span>
                <span class="hidden sm:block w-px h-4 bg-outline-variant shrink-0" aria-hidden="true"></span>
                <p class="hidden sm:block truncate text-sm text-on-surface-variant m-0">Cards that will be due soon</p>
            </div>
        </div>

        <?php if (empty($upcomingRows)): ?>
            <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs py-10 text-center text-on-surface-variant text-sm">
                No cards scheduled for the next <?= (int) (ReviewHubService::UPCOMING_DAYS * 24) ?> hours.
            </div>
        <?php else: ?>
            <div class="mp-table rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs overflow-hidden">
                <div id="upcoming-table"></div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ===================================================================== -->
    <!-- RECENT REVIEW SESSIONS                                                 -->
    <!-- ===================================================================== -->
    <section class="flex flex-col gap-4 w-full min-w-0" id="recent-sessions-section">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2.5 min-w-0">
                <h2 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface whitespace-nowrap">Recent Review Sessions</h2>
                <span class="<?= $badgeCls ?>"><?= count($recentRows) ?> <?= count($recentRows) === 1 ? 'Session' : 'Sessions' ?></span>
                <span class="hidden sm:block w-px h-4 bg-outline-variant shrink-0" aria-hidden="true"></span>
                <p class="hidden sm:block truncate text-sm text-on-surface-variant m-0">Your latest spaced repetition drills</p>
            </div>
            <a href="sessions.php" class="shrink-0 text-xs text-primary hover:underline font-semibold flex items-center gap-1 transition-all whitespace-nowrap">
                <span>View History</span>
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </div>

        <?php if (empty($recentRows)): ?>
            <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs flex flex-col items-center justify-center p-10 text-center text-on-surface-variant">
                <span class="material-symbols-outlined text-[32px] text-outline mb-2">history_edu</span>
                <span class="text-sm font-medium">No recent review sessions found.</span>
                <span class="text-xs mt-1 text-on-surface-variant">Complete a study session to see your history here.</span>
            </div>
        <?php else: ?>
            <div class="mp-table rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs overflow-hidden">
                <div id="recent-sessions-table"></div>
            </div>
        <?php endif; ?>
    </section>

</div>

<!-- Same Tabulator build + theme as deck-view.php -->
<link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_simple.min.css" rel="stylesheet">

<!-- Read-only Tabulator styling: structure only, colours come from the probe above -->
<style>
    .mp-table .tabulator { background: transparent; border: 0; font-family: inherit; font-size: .875rem; color: var(--mp-text, inherit); }
    .mp-table .tabulator .tabulator-header { background: var(--mp-head-bg, #f1f1f1) !important; color: var(--mp-head-text, inherit); border-top: 0; border-bottom: 1px solid var(--mp-border, #ddd); }
    .mp-table .tabulator .tabulator-header .tabulator-col { background: transparent !important; border-right: 0; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: .75rem 1.25rem; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .mp-table .tabulator .tabulator-tableholder,
    .mp-table .tabulator .tabulator-tableholder .tabulator-table { background: transparent; }
    .mp-table .tabulator .tabulator-row,
    .mp-table .tabulator .tabulator-row.tabulator-row-even,
    .mp-table .tabulator .tabulator-row.tabulator-row-odd { background: transparent !important; color: inherit; border-bottom: 1px solid var(--mp-divider, #eee); min-height: 0; }
    .mp-table .tabulator .tabulator-row:hover { background: var(--mp-hover, rgba(0,0,0,.03)) !important; }
    .mp-table .tabulator .tabulator-table > .tabulator-row:last-child { border-bottom: 0; }
    .mp-table .tabulator .tabulator-row .tabulator-cell { padding: .75rem 1.25rem; border-right: 0; vertical-align: middle; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-resize-handle { display: none; }
    
    /* Due Now + Upcoming: plain cursor (links and buttons inside keep their own pointer) */
    #due-now-table .tabulator-row,
    #due-now-table .tabulator-row .tabulator-cell,
    #upcoming-table .tabulator-row,
    #upcoming-table .tabulator-row .tabulator-cell { cursor: default !important; }

    /* Recent Review Sessions: clickable rows */
    #recent-sessions-table .tabulator-row,
    #recent-sessions-table .tabulator-row .tabulator-cell { cursor: pointer; }
</style>

<!-- Tabulator tables (readable mode: no sorting, resizing, moving, editing or selection) -->
<script type="application/json" id="hub-table-data"><?= json_encode(['due' => $dueRows, 'upcoming' => $upcomingRows, 'recent' => $recentRows], $jsonFlags) ?></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dataEl = document.getElementById('hub-table-data');
    if (!dataEl) return;
    var data = JSON.parse(dataEl.textContent);

    // ---- Reuse the Tailwind design tokens as CSS variables for the table chrome ----
    var probe = document.getElementById('mp-token-probe');
    if (probe) {
        var q = function (t) { return probe.querySelector('[data-t="' + t + '"]'); };
        var s = document.documentElement.style;
        s.setProperty('--mp-head-bg',   getComputedStyle(q('head')).backgroundColor);
        s.setProperty('--mp-head-text', getComputedStyle(q('head')).color);
        s.setProperty('--mp-text',      getComputedStyle(q('text')).color);
        s.setProperty('--mp-hover',     getComputedStyle(q('hover')).backgroundColor);
        s.setProperty('--mp-border',    getComputedStyle(q('border')).borderTopColor);
        s.setProperty('--mp-divider',   getComputedStyle(q('divider')).borderTopColor);
    }

    var esc = function (v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    var muted = 'text-on-surface-variant tabular-nums whitespace-nowrap';

    // Options shared by every table: read-only
    var base = {
        layout: 'fitColumns',
        responsiveLayout: 'hide',
        movableColumns: false,
        selectable: false,
        selectableRows: false,
        columnDefaults: { headerSort: false, resizable: false }
    };

    var linkCell = function (urlField, extra) {
        return function (cell) {
            var r = cell.getRow().getData();
            var tag = (extra && extra(r)) || '';
            return '<div class="flex items-center gap-2 min-w-0">' +
                   '<a href="' + esc(r[urlField]) + '" title="' + esc(r.title) + '" ' +
                   'class="block truncate font-semibold text-on-surface hover:text-primary transition-colors">' + esc(r.title) + '</a>' + tag + '</div>';
        };
    };

    var make = function (selector, rows, columns, extra) {
        var el = document.querySelector(selector);
        if (!el) return;
        if (typeof Tabulator === 'undefined') {
            console.error('Tabulator failed to load.');
            el.innerHTML = '<p class="py-8 text-center text-sm text-on-surface-variant">Table could not be loaded.</p>';
            return;
        }
        var cols = columns.map(function (c) { return Object.assign({ headerSort: false, resizable: false }, c); });
        new Tabulator(el, Object.assign({}, base, { data: rows, columns: cols }, extra || {}));    };

    // Load Tabulator 5.5.0 (same file deck-view.php uses) unless head.php already provides it
    var ensureTabulator = function (done) {
        if (window.Tabulator) return done();
        var sc = document.createElement('script');
        sc.src = 'https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js';
        sc.onload = done;
        sc.onerror = done; // make() shows the fallback message
        document.head.appendChild(sc);
    };

    function buildTables() {
    // ---- Due Now ----
    make('#due-now-table', data.due, [
        { title: 'Deck', field: 'title', widthGrow: 3, minWidth: 150, responsive: 0, formatter: linkCell('deckUrl') },
        { title: 'Due', field: 'dueCount', width: 100, hozAlign: 'right', headerHozAlign: 'right', responsive: 1,
          formatter: function (c) { return '<span class="inline-block text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200/80 px-2 py-0.5 rounded-md tabular-nums">' + esc(c.getValue()) + '</span>'; } },
        { title: 'Total cards', field: 'totalCards', width: 130, hozAlign: 'right', headerHozAlign: 'right', responsive: 2,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
        { title: 'Oldest due', field: 'oldestText', width: 140, hozAlign: 'right', headerHozAlign: 'right', responsive: 3,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
        { title: '', field: 'reviewUrl', width: 140, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) {
              var r = c.getRow().getData();
              return '<a href="' + esc(r.reviewUrl) + '" aria-label="Review ' + esc(r.title) + '" ' +
                     'class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary text-on-primary text-xs font-bold hover:bg-primary-container transition-colors">' +
                     '<span class="material-symbols-outlined text-[16px]">play_arrow</span><span>Review</span></a>';
          } }
    ]);

    // ---- Upcoming ----
    make('#upcoming-table', data.upcoming, [
        { title: 'Deck', field: 'title', widthGrow: 3, minWidth: 150, responsive: 0,
          formatter: linkCell('deckUrl', function (r) {
              return r.isDueNow ? '<span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 whitespace-nowrap">Due now</span>' : '';
          }) },
        { title: 'Cards soon', field: 'upcomingCount', width: 130, hozAlign: 'right', headerHozAlign: 'right', responsive: 1,
          formatter: function (c) { return '<span class="inline-block text-xs text-on-surface-variant bg-surface-container-high px-2 py-0.5 rounded-md tabular-nums">' + esc(c.getValue()) + '</span>'; } },
        { title: 'Next due', field: 'nextText', width: 140, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } }
    ]);

    // ---- Recent Review Sessions ----
    make('#recent-sessions-table', data.recent, [
        { title: 'Deck', field: 'title', widthGrow: 3, minWidth: 150, responsive: 0, formatter: linkCell('detailsUrl') },
        { title: 'Cards', field: 'cards', width: 100, hozAlign: 'right', headerHozAlign: 'right', responsive: 2,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
        { title: 'Accuracy', field: 'accuracy', width: 120, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) {
              var r = c.getRow().getData();
              return '<span class="inline-block text-xs font-bold border px-2 py-0.5 rounded-md tabular-nums ' + esc(r.accuracyCls) + '">' + esc(r.accuracy) + '%</span>';
          } },
        { title: 'Duration', field: 'duration', width: 120, hozAlign: 'right', headerHozAlign: 'right', responsive: 3,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
        { title: 'When', field: 'when', width: 130, hozAlign: 'right', headerHozAlign: 'right', responsive: 1,
          formatter: function (c) { return '<span class="' + muted + ' text-xs">' + esc(c.getValue()) + '</span>'; } },
        { title: '', field: 'practiceUrl', width: 160, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) {
              var r = c.getRow().getData();
              if (!r.practiceUrl) return '';
              return '<a href="' + esc(r.practiceUrl) + '" title="Practice these cards again (nothing is saved)" ' +
                     'class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-outline-variant text-on-surface text-xs font-bold hover:bg-surface-container-low transition-colors whitespace-nowrap">' +
                     '<span class="material-symbols-outlined text-[16px]">play_arrow</span><span>Practice again</span></a>';
          } },
        { title: '', field: 'detailsUrl', width: 70, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) {
              var r = c.getRow().getData();
              return '<a href="' + esc(r.detailsUrl) + '" aria-label="View session details for ' + esc(r.title) + '" class="inline-flex items-center text-on-surface-variant hover:text-primary transition-colors">' +
                     '<span class="material-symbols-outlined text-[20px]">chevron_right</span></a>';
          } }
        ], {
        rowClick: function (e, row) {
            if (e.target.closest('a')) return;               // the links handle themselves
            window.location.href = row.getData().detailsUrl; // anywhere else on the row opens details
        }
    });
    }

    ensureTabulator(buildTables);
});
</script>