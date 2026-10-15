review-hub-view.php
```
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

    <!-- Unified Header Hero Banner -->
    <div class="relative w-full rounded-2xl bg-surface-container-lowest border border-outline-variant/30 p-4 sm:p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">

            <?php if ($dueTotal > 0): ?>
                <!-- A: Cards Due Ready State -->
                <div class="flex items-center gap-3.5 min-w-0">

    <!-- Simple Compact Icon -->
    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-[22px] sm:text-[24px]">alarm</span>
    </div>

    <!-- Clean Text & Action Overview -->
    <div class="flex flex-col min-w-0">
        <div class="flex items-center gap-2 min-w-0">
            <h2 class="text-base sm:text-lg font-bold text-on-surface truncate m-0">
                <span id="stat-due-cta-count" class="font-extrabold text-primary tabular-nums"><?= (int) $dueTotal ?></span>
                <?= $dueTotal === 1 ? 'card' : 'cards' ?> ready to review
            </h2>

            <?php if ($streakAtRisk): ?>
                <span class="shrink-0 px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200/80 text-[10px] font-bold">
                    Streak at risk
                </span>
            <?php endif; ?>
        </div>

        <?php if ($dueSubtitle !== ''): ?>
            <p class="text-xs text-on-surface-variant truncate m-0"><?= $h($dueSubtitle) ?></p>
        <?php endif; ?>
    </div>

</div>

                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end shrink-0">
                    <!-- Keyboard Shortcuts Popover Trigger (Desktop) -->
                    <div class="relative hidden lg:block group">
                        <button type="button" aria-label="View keyboard shortcuts"
                                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-outline-variant/40 bg-surface-container-low text-on-surface-variant text-xs font-semibold hover:text-primary hover:border-primary/40 transition-colors">
                            <span class="material-symbols-outlined text-[16px]">keyboard</span>
                            <span>Shortcuts</span>
                        </button>

                        <!-- Hover/Focus Popover Card -->
                        <div class="absolute right-0 top-full mt-2 w-64 p-3 rounded-xl bg-surface-container-highest border border-outline-variant/40 shadow-lg opacity-0 pointer-events-none group-hover:opacity-100 group-focus-within:opacity-100 group-hover:pointer-events-auto group-focus-within:pointer-events-auto transition-all z-20">
                            <span class="text-xs font-bold text-on-surface block mb-2">Review Controls</span>
                            <ul class="space-y-1.5 text-xs text-on-surface-variant">
                                <li class="flex items-center justify-between"><kbd class="px-1.5 py-0.5 bg-surface-container-high rounded border border-outline-variant/50 font-mono text-[10px]">Space</kbd> Show / Remember</li>
                                <li class="flex items-center justify-between"><kbd class="px-1.5 py-0.5 bg-surface-container-high rounded border border-outline-variant/50 font-mono text-[10px]">F</kbd> Mark Forgotten</li>
                                <li class="flex items-center justify-between"><kbd class="px-1.5 py-0.5 bg-surface-container-high rounded border border-outline-variant/50 font-mono text-[10px]">← / →</kbd> Previous / Next</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Main Review CTA -->
                    <a href="<?= $h($reviewHref) ?>"
                       id="stat-due-cta"
                       aria-label="Review <?= (int) $dueTotal ?> due <?= $dueTotal === 1 ? 'card' : 'cards' ?>"
                       class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-on-primary font-bold text-xs sm:text-sm shadow-xs hover:bg-primary-container transition-colors w-full sm:w-auto">
                        <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                        <span>Start Review</span>
                    </a>
                </div>

            <?php elseif ($nextDueSecs !== null): ?>
                <!-- B: All Caught Up State -->
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[24px]">task_alt</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                        <h2 class="text-base sm:text-lg font-extrabold text-on-surface m-0">All caught up</h2>
                        <?php if ($nextDueLabel !== ''): ?>
                            <p class="text-xs text-on-surface-variant truncate m-0"><?= $h($nextDueLabel) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- C: No Cards Scheduled State -->
                <div class="flex items-center gap-3.5 min-w-0">
                    <div class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-[24px]">add</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                        <h2 class="text-base sm:text-lg font-extrabold text-on-surface m-0">No cards found</h2>
                        <p class="text-xs text-on-surface-variant truncate m-0">Create flashcards to start practicing</p>
                    </div>
                </div>

                <a href="decks.php?add_card=1" id="stat-due-cta"
                   class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-on-primary font-bold text-xs sm:text-sm shadow-xs hover:bg-primary-container transition-colors shrink-0 w-full sm:w-auto">
                    <span class="material-symbols-outlined text-[18px]">add</span>
                    <span>Create Cards</span>
                </a>
            <?php endif; ?>

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
    <!-- DUE NOW                                                                -->
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
            <div class="mp-table rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs overflow-x-auto">
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
            <div class="mp-table rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs overflow-x-auto">
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
            <div class="mp-table rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs overflow-x-auto">
                <div id="recent-sessions-table"></div>
            </div>
        <?php endif; ?>
    </section>

</div>

<!-- Same Tabulator build + theme as deck-view.php -->
<link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_simple.min.css" rel="stylesheet">

<!-- Read-only Tabulator styling: structure + responsive mobile rules -->
<style>
    .mp-table .tabulator { background: transparent; border: 0; font-family: inherit; font-size: .875rem; color: var(--mp-text, inherit); }
    .mp-table .tabulator .tabulator-header { background: var(--mp-head-bg, #f1f1f1) !important; color: var(--mp-head-text, inherit); border-top: 0; border-bottom: 1px solid var(--mp-border, #ddd); }
    .mp-table .tabulator .tabulator-header .tabulator-col { background: transparent !important; border-right: 0; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: .75rem 1rem; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .mp-table .tabulator .tabulator-tableholder,
    .mp-table .tabulator .tabulator-tableholder .tabulator-table { background: transparent; }
    .mp-table .tabulator .tabulator-row,
    .mp-table .tabulator .tabulator-row.tabulator-row-even,
    .mp-table .tabulator .tabulator-row.tabulator-row-odd { background: transparent !important; color: inherit; border-bottom: 1px solid var(--mp-divider, #eee); min-height: 0; }
    .mp-table .tabulator .tabulator-row:hover { background: var(--mp-hover, rgba(0,0,0,.03)) !important; }
    .mp-table .tabulator .tabulator-table > .tabulator-row:last-child { border-bottom: 0; }
    .mp-table .tabulator .tabulator-row .tabulator-cell { padding: .75rem 1rem; border-right: 0; vertical-align: middle; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-resize-handle { display: none; }
    
    @media (max-width: 640px) {
        .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-content,
        .mp-table .tabulator .tabulator-row .tabulator-cell { padding: .5rem .625rem; font-size: .8125rem; }
    }

    #due-now-table .tabulator-row,
    #due-now-table .tabulator-row .tabulator-cell,
    #upcoming-table .tabulator-row,
    #upcoming-table .tabulator-row .tabulator-cell { cursor: default !important; }

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
        new Tabulator(el, Object.assign({}, base, { data: rows, columns: cols }, extra || {}));
    };

    var ensureTabulator = function (done) {
        if (window.Tabulator) return done();
        var sc = document.createElement('script');
        sc.src = 'https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js';
        sc.onload = done;
        sc.onerror = done;
        document.head.appendChild(sc);
    };

    function buildTables() {
    // ---- Due Now ----
    make('#due-now-table', data.due, [
        { title: 'Deck', field: 'title', widthGrow: 3, minWidth: 120, responsive: 0, formatter: linkCell('deckUrl') },
        { title: 'Due', field: 'dueCount', minWidth: 65, hozAlign: 'right', headerHozAlign: 'right', responsive: 1,
          formatter: function (c) { return '<span class="inline-block text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200/80 px-2 py-0.5 rounded-md tabular-nums">' + esc(c.getValue()) + '</span>'; } },
        { title: 'Total cards', field: 'totalCards', minWidth: 85, hozAlign: 'right', headerHozAlign: 'right', responsive: 2,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
        { title: 'Oldest due', field: 'oldestText', minWidth: 95, hozAlign: 'right', headerHozAlign: 'right', responsive: 3,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
        { title: '', field: 'reviewUrl', minWidth: 100, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) {
              var r = c.getRow().getData();
              return '<a href="' + esc(r.reviewUrl) + '" aria-label="Review ' + esc(r.title) + '" title="Start reviewing ' + esc(r.title) + '" ' +
                     'class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-primary text-on-primary text-xs font-bold hover:bg-primary-container transition-colors whitespace-nowrap">' +
                     '<span class="material-symbols-outlined text-[15px]">play_arrow</span><span>Review</span></a>';
          } }
    ]);

    // ---- Upcoming ----
    make('#upcoming-table', data.upcoming, [
        { title: 'Deck', field: 'title', widthGrow: 3, minWidth: 120, responsive: 0,
          formatter: linkCell('deckUrl', function (r) {
              return r.isDueNow ? '<span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 whitespace-nowrap">Due now</span>' : '';
          }) },
        { title: 'Cards soon', field: 'upcomingCount', minWidth: 85, hozAlign: 'right', headerHozAlign: 'right', responsive: 1,
          formatter: function (c) { return '<span class="inline-block text-xs text-on-surface-variant bg-surface-container-high px-2 py-0.5 rounded-md tabular-nums">' + esc(c.getValue()) + '</span>'; } },
        { title: 'Next due', field: 'nextText', minWidth: 95, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } }
    ]);

    // ---- Recent Review Sessions ----
    make('#recent-sessions-table', data.recent, [
        { title: 'Deck', field: 'title', widthGrow: 3, minWidth: 120, responsive: 0, formatter: linkCell('detailsUrl') },
        { title: 'Cards', field: 'cards', minWidth: 65, hozAlign: 'right', headerHozAlign: 'right', responsive: 2,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
        { title: 'Accuracy', field: 'accuracy', minWidth: 80, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) {
              var r = c.getRow().getData();
              return '<span class="inline-block text-xs font-bold border px-2 py-0.5 rounded-md tabular-nums ' + esc(r.accuracyCls) + '">' + esc(r.accuracy) + '%</span>';
          } },
        { title: 'Duration', field: 'duration', minWidth: 75, hozAlign: 'right', headerHozAlign: 'right', responsive: 3,
          formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
        { title: 'When', field: 'when', minWidth: 85, hozAlign: 'right', headerHozAlign: 'right', responsive: 1,
          formatter: function (c) { return '<span class="' + muted + ' text-xs">' + esc(c.getValue()) + '</span>'; } },
        { title: '', field: 'practiceUrl', minWidth: 115, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) {
              var r = c.getRow().getData();
              if (!r.practiceUrl) return '';
              return '<a href="' + esc(r.practiceUrl) + '" title="Practice these cards again without affecting spaced repetition history" ' +
                     'class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-outline-variant text-on-surface text-xs font-bold hover:bg-surface-container-low transition-colors whitespace-nowrap">' +
                     '<span class="material-symbols-outlined text-[15px]">play_arrow</span><span>Practice again</span></a>';
          } },
        { title: 'Action', field: 'detailsUrl', minWidth: 110, hozAlign: 'right', headerHozAlign: 'right', responsive: 0,
          formatter: function (c) {
              var r = c.getRow().getData();
              return '<a href="' + esc(r.detailsUrl) + '" ' +
                     'title="View comprehensive review session breakdown, card history, and performance details for ' + esc(r.title) + '" ' +
                     'aria-label="View session details for ' + esc(r.title) + '" ' +
                     'class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container-high border border-outline-variant/40 text-on-surface hover:text-primary hover:border-primary/50 text-xs font-bold transition-colors whitespace-nowrap">' +
                     '<span>View Details</span><span class="material-symbols-outlined text-[15px]">chevron_right</span></a>';
          } }
        ], {
        rowClick: function (e, row) {
            if (e.target.closest('a')) return;
            window.location.href = row.getData().detailsUrl;
        }
    });
    }

    ensureTabulator(buildTables);
});
</script>
```

reports-view.php
```
<?php
/**
 * reports-view.php
 *
 * Shell for the Reports page: print header, filters, tab bar and tab panels.
 * Expects (set in reports.php): $filters, $deckOptions, $profile, $overview
 */
$h = fn($v) => ReportsService::h($v);

$rangeLabels = [
    '7d'     => 'Last 7 days',
    '30d'    => 'Last 30 days',
    '90d'    => 'Last 90 days',
    'all'    => 'All time',
    'custom' => 'Custom range',
];

$tabs = [
    'overview' => ['label' => 'Overview', 'icon' => 'insights'],
    'decks'    => ['label' => 'Decks',    'icon' => 'style'],
    'cards'    => ['label' => 'Cards',    'icon' => 'psychology'],
    'sessions' => ['label' => 'Sessions', 'icon' => 'history'],
];

$exportDatasets = [
    'sessions' => ['Session history',  'One row per study session'],
    'decks'    => ['Deck summary',     'One row per deck, with totals'],
    'cards'    => ['Card performance', 'Front, back, interval, lapses, reviews, next review'],
    'logs'     => ['Review log detail', 'One row per rating'],
    'full'     => ['Full report',      'Summary plus all of the above in one file'],
];
// Current filters, without tab or drill-down deck.
$exportBase = ReportsService::buildQuery($filters, ['tab' => null, 'focus' => null]);

$activeTab   = $filters['tab'];
$isCustom    = ($filters['range'] === 'custom');
$periodLabel = ($filters['range'] === 'all' ? 'All time' : $rangeLabels[$filters['range']])
             . ' (' . date('M j, Y', strtotime($filters['from'])) . ' – ' . date('M j, Y', strtotime($filters['to'])) . ')';
?>
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8" id="reports-root">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <!-- PRINT-ONLY HEADER (hidden on screen) -->
        <div class="rp-print-only rp-print-header">
            <h1>MemPulse Study Report</h1>
            <table>
                <tr><th>Student</th><td><?= $h($profile['displayName']) ?></td></tr>
                <tr><th>Period</th><td><?= $h($periodLabel) ?></td></tr>
                <tr><th>Deck</th><td><?= $h($filters['deckTitle']) ?><?= $filters['includeArchived'] ? ' (archived decks included)' : '' ?></td></tr>
                <tr><th>Generated</th><td><?= $h(date('M j, Y g:i A')) ?></td></tr>
            </table>
        </div>

        <!-- PAGE HEADER -->
        <div class="rp-no-print flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
            <div>
                <h1 class="font-headline-md text-2xl font-bold text-on-surface">Reports</h1>
                <p class="text-sm text-on-surface-variant mt-0.5">Your study activity, retention and card progress.</p>
            </div>
            <div class="flex items-center gap-2">
                <details id="rp-export-menu" class="relative">
                    <summary class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant/40 bg-surface-container-lowest text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors cursor-pointer select-none">
                        <span class="material-symbols-outlined text-[18px]">download</span>
                        <span>Export CSV</span>
                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                    </summary>
                    <div class="absolute right-0 mt-2 w-80 max-w-[90vw] z-30 rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-lg p-2">
                        <?php foreach ($exportDatasets as $key => [$label, $hint]): ?>
                            <a data-export="<?= $h($key) ?>"
                               href="../api/report-export.php?<?= $h($exportBase . '&dataset=' . $key) ?>"
                               class="block rounded-lg px-3 py-2 hover:bg-surface-container-low">
                                <span class="block text-sm font-semibold text-on-surface"><?= $h($label) ?></span>
                                <span class="block text-xs text-on-surface-variant"><?= $h($hint) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <label class="flex items-center gap-2 px-3 pt-2 pb-1 mt-1 border-t border-outline-variant/30 text-xs text-on-surface-variant cursor-pointer">
                            <input type="checkbox" id="rp-export-meta" class="rounded border-outline-variant/60 text-primary focus:ring-primary">
                            Add student, period and filters at the top
                        </label>
                    </div>
                </details>                <button type="button" id="rp-print-btn"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-outline-variant/40 bg-surface-container-lowest text-sm font-semibold text-on-surface hover:bg-surface-container-low transition-colors">
                    <span class="material-symbols-outlined text-[18px]">print</span>
                    <span>Print / Save as PDF</span>
                </button>
            </div>
        </div>

        <!-- FILTER BAR -->
        <form id="rp-filters" method="get" action="reports.php"
              class="rp-no-print flex flex-wrap items-end gap-3 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 sm:p-4">
            <input type="hidden" name="tab" id="rp-filter-tab" value="<?= $h($activeTab) ?>">

            <label class="flex flex-col gap-1 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                Period
                <select name="range" id="rp-range" class="rounded-xl border-outline-variant/40 text-sm font-medium normal-case tracking-normal text-on-surface py-2">
                    <?php foreach ($rangeLabels as $key => $label): ?>
                        <option value="<?= $h($key) ?>" <?= $filters['range'] === $key ? 'selected' : '' ?>><?= $h($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div id="rp-custom-dates" class="flex items-end gap-3 <?= $isCustom ? '' : 'hidden' ?>">
                <label class="flex flex-col gap-1 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                    From
                    <input type="date" name="from" id="rp-from" value="<?= $h($filters['from']) ?>" max="<?= $h(date('Y-m-d')) ?>"
                           class="rounded-xl border-outline-variant/40 text-sm font-medium normal-case tracking-normal text-on-surface py-2" <?= $isCustom ? '' : 'disabled' ?>>
                </label>
                <label class="flex flex-col gap-1 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                    To
                    <input type="date" name="to" id="rp-to" value="<?= $h($filters['to']) ?>" max="<?= $h(date('Y-m-d')) ?>"
                           class="rounded-xl border-outline-variant/40 text-sm font-medium normal-case tracking-normal text-on-surface py-2" <?= $isCustom ? '' : 'disabled' ?>>
                </label>
                <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:bg-primary-container transition-colors">Apply</button>
            </div>

            <label class="flex flex-col gap-1 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant min-w-[10rem]">
                Deck
                <select name="deck" id="rp-deck" class="rounded-xl border-outline-variant/40 text-sm font-medium normal-case tracking-normal text-on-surface py-2">
                    <option value="0">All decks</option>
                    <?php foreach ($deckOptions as $d): ?>
                        <option value="<?= (int) $d['id'] ?>" <?= $filters['deckId'] === $d['id'] ? 'selected' : '' ?>>
                            <?= $h($d['title']) ?><?= $d['archived'] ? ' (archived)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="flex items-center gap-2 pb-2 text-sm font-medium text-on-surface cursor-pointer">
                <input type="checkbox" name="archived" id="rp-archived" value="1" <?= $filters['includeArchived'] ? 'checked' : '' ?>
                       class="rounded border-outline-variant/60 text-primary focus:ring-primary">
                Include archived decks
            </label>
        </form>

        <!-- TAB BAR -->
        <div class="rp-no-print border-b border-outline-variant/30 overflow-x-auto">
            <div role="tablist" aria-label="Report sections" class="flex gap-1 min-w-max">
                <?php foreach ($tabs as $key => $tab): $isActive = ($key === $activeTab); ?>
                    <a role="tab" id="tab-<?= $key ?>" data-tab="<?= $key ?>"
                       href="reports.php?<?= $h(ReportsService::buildQuery($filters, ['tab' => $key])) ?>"
                       aria-controls="panel-<?= $key ?>" aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                       tabindex="<?= $isActive ? '0' : '-1' ?>"
                       class="rp-tab inline-flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 -mb-px transition-colors
                              <?= $isActive ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
                        <span class="material-symbols-outlined text-[18px]"><?= $h($tab['icon']) ?></span>
                        <?= $h($tab['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TAB PANELS (all rendered; only the active one is visible on screen, all print) -->
        <section role="tabpanel" id="panel-overview" data-tab="overview" aria-labelledby="tab-overview"
                 class="rp-panel <?= $activeTab === 'overview' ? '' : 'hidden' ?>">
            <h2 class="rp-print-only rp-print-section">Overview</h2>
            <?php include __DIR__ . '/reports-overview.php'; ?>
        </section>

        <section role="tabpanel" id="panel-decks" data-tab="decks" aria-labelledby="tab-decks"
                 class="rp-panel <?= $activeTab === 'decks' ? '' : 'hidden' ?>">
            <h2 class="rp-print-only rp-print-section">Decks</h2>
            <?php include __DIR__ . '/reports-decks.php'; ?>
        </section>

        <section role="tabpanel" id="panel-cards" data-tab="cards" aria-labelledby="tab-cards"
                 class="rp-panel <?= $activeTab === 'cards' ? '' : 'hidden' ?>">
            <?php /* include __DIR__ . '/reports-cards.php'; (task 3) */ ?>
            <h2 class="rp-print-only rp-print-section">Cards</h2>
            <?php include __DIR__ . '/reports-cards.php'; ?>        
        </section>

        <section role="tabpanel" id="panel-sessions" data-tab="sessions" aria-labelledby="tab-sessions"
                 class="rp-panel <?= $activeTab === 'sessions' ? '' : 'hidden' ?>">
            <?php /* include __DIR__ . '/reports-sessions.php'; (task 4) */ ?>
            <h2 class="rp-print-only rp-print-section">Sessions</h2>
            <?php include __DIR__ . '/reports-sessions.php'; ?>        
        </section>

    </div>
</div>
```

dashboard.php
```
<!-- ========================================================================= -->
<!-- MAIN DASHBOARD CONTENT CONTAINER CONTAINER DIV                            -->
<!-- Outer responsive padding shell wrapping top metrics, carousel, and history -->
<!-- ========================================================================= -->
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <!-- DIV: Vertical Layout Flex Shell -->
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <!-- DIV: TOP SECTION - Core Analytics Cards, Action Carousel & Streak Bar -->
        <div class="flex flex-col gap-4 sm:gap-6 w-full">

            <!-- DIV: ROW 1 - Grid Split: 2x2 Metric Grid (Left) vs Hero Carousel (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">
                
                <!-- DIV: LEFT COLUMN - 2x2 Core Metrics Card Grid Container -->
                <div class="lg:col-span-6 flex flex-col justify-between w-full min-w-0">
                    <!-- DIV: 2-Column Responsive Metric Grid Shell -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 w-full h-full">
                        
                        <!-- DIV: CARD 1 - Scheduled Cards Due Metric Container -->
                        <div class="bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-primary/50 hover:shadow-md transition-all">
                            <!-- DIV: Ambient Background Blur Decorative Shape -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-primary/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-primary/20 transition-all"></div>
                            
                            <!-- DIV: Card Header Row Shell (Title & Tooltip) -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <!-- DIV: Label & Information Tooltip Shell -->
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Scheduled Cards Category Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-primary">Scheduled</span>
                                    <!-- DIV: Informational Tooltip Wrapper -->
                                    <div class="relative group/tooltip flex items-center">
                                        <!-- SPAN: Info Graphic Icon -->
                                        <span class="material-symbols-outlined text-[15px] text-primary/60 cursor-help hover:text-primary transition-colors">info</span>
                                        <!-- DIV: Floating Tooltip Hover Content Container -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Total cards scheduled for review today.
                                            <!-- DIV: Tooltip Arrow Point Graphic -->
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- DIV: Metric Card Alarm Icon Container Badge -->
                                <div class="w-8 h-8 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Alarm Clock Icon Graphic -->
                                    <span class="material-symbols-outlined text-[18px]">alarm</span>
                                </div>
                            </div>
                            <!-- DIV: Metric Numerical Display Wrapper -->
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Cards Due Counter Output -->
                                <span id="stat-cards-due" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['cardsDue']; ?>
                                </span>
                                <!-- SPAN: Metric Name Subtitle -->
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Due Today</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 2 - Cards Reviewed Today Metric Container -->
                        <div class="bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-teal-500/50 hover:shadow-md transition-all">
                            <!-- DIV: Ambient Background Glow Graphic -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-teal-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-teal-500/20 transition-all"></div>
                            
                            <!-- DIV: Header Metric Shell -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <!-- DIV: Category Label Group -->
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Category Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-teal-700">Reviewed</span>
                                    <!-- DIV: Tooltip Hover Trigger -->
                                    <div class="relative group/tooltip flex items-center">
                                        <!-- SPAN: Info Graphic Icon -->
                                        <span class="material-symbols-outlined text-[15px] text-teal-700/60 cursor-help hover:text-teal-700 transition-colors">info</span>
                                        <!-- DIV: Floating Tooltip Box -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Total cards reviewed in completed study sessions today.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- DIV: Icon Badge Shell -->
                                <div class="w-8 h-8 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-600 flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Flashcards Graphic Icon -->
                                    <span class="material-symbols-outlined text-[18px]">style</span>
                                </div>
                            </div>
                            <!-- DIV: Numerical Value Group -->
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Cards Reviewed Counter Output -->
                                <span id="stat-reviewed-today" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['cardsReviewedToday']; ?>
                                </span>
                                <!-- SPAN: Metric Name Subtitle -->
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Reviewed Today</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 3 - Retention Rate Percentage Metric Container -->
                        <div class="bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-error/50 hover:shadow-md transition-all">
                            <!-- DIV: Decorative Background Overlay -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-rose-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-rose-500/20 transition-all"></div>
                            
                            <!-- DIV: Header Information Bar Shell -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Retention Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-error">Retention Rate</span>
                                    <div class="relative group/tooltip flex items-center">
                                        <span class="material-symbols-outlined text-[15px] text-error/60 cursor-help hover:text-error transition-colors">info</span>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Percentage of completed reviews where the learner successfully remembered the card.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-8 h-8 rounded-xl bg-red-50 border border-red-200/80 flex items-center justify-center text-error flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Accuracy Target Icon -->
                                    <span class="material-symbols-outlined text-[18px]">track_changes</span>
                                </div>
                            </div>
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Retention Output Percentage -->
                                <span id="stat-retention" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (float) $metricsData['retentionRate']; ?>%
                                </span>
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Retention Rate</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 4 - Mastered Cards Metric Container -->
                        <div class="bg-gradient-to-br from-indigo-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-indigo-500/50 hover:shadow-md transition-all">
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-indigo-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-indigo-500/20 transition-all"></div>
                            
                            <div class="flex items-center justify-between w-full relative z-10">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-indigo-700">Mastered</span>
                                    <div class="relative group/tooltip flex items-center">
                                        <span class="material-symbols-outlined text-[15px] text-indigo-700/60 cursor-help hover:text-indigo-700 transition-colors">info</span>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Cards that have reached a review interval of at least 21 days.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-indigo-600 flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Verified Badge Icon -->
                                    <span class="material-symbols-outlined text-[18px]">verified</span>
                                </div>
                            </div>
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Mastered Cards Count Output -->
                                <span id="stat-mastered" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['masteredCards']; ?>
                                </span>
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Mastered</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- DIV: RIGHT COLUMN - Dynamic Action Hero Carousel Container -->
                <div class="lg:col-span-6 flex w-full min-w-0 h-full">
                    <!-- SECTION: Interactive Carousel Wrapper Shell -->
                    <section class="relative w-full h-full rounded-2xl overflow-hidden shadow-xs border border-outline-variant/30 min-h-[280px] bg-gradient-to-br from-inverse-surface via-slate-900 to-inverse-surface text-inverse-on-surface flex flex-col justify-between" id="hero-carousel" aria-label="Learning Quick Actions Carousel">
                        <!-- DIV: Background Ambient Glow Effects -->
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>

                        <!-- DIV: Carousel Slide Frame Wrapper -->
                        <div class="relative w-full h-full min-h-[280px]">
                            
                            <!-- DIV: SLIDE 0 - Memory Science -->
                            <!-- <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-100 z-10" data-slide-index="0" aria-hidden="false"> -->
                                <!-- DIV: Slide Header & Description Block -->
                                <!-- <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-primary/20 border border-primary/30 backdrop-blur-md text-cyan-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">psychology</span>
                                        <span>Memory Science</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Understand How Memory Works
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Explore the science behind spaced repetition, forgetting curves, and effective long-term learning.
                                    </p>
                                </div> -->
                                <!-- DIV: Slide Action Button & Wayfinder Dots Bar -->
                                <!-- <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="science.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Learn More</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a> -->
                                    <!-- DIV: Wayfinder Pagination Navigation Dots Shell -->
                                    <!-- <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div> -->

                            <!-- DIV: SLIDE 1 - Decks -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="1" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-teal-500/20 border border-teal-500/30 backdrop-blur-md text-teal-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">style</span>
                                        <span>Decks</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        View and Organize Your Decks
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Manage your study collections, browse your cards, and keep your learning materials organized.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="decks.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>View Decks</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 2 - Flashcard Creation -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="2" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-indigo-500/20 border border-indigo-500/30 backdrop-blur-md text-indigo-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">edit_note</span>
                                        <span>Flashcard Creation</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Create Your Flashcards
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Build focused study cards and turn complex subjects into concise prompts for active recall.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="decks.php?add_card=1" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Create Flashcards</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 3 - Review & History -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="3" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-amber-500/20 border border-amber-500/30 backdrop-blur-md text-amber-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">pace</span>
                                        <span>Review & History</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Review Cards and Track Your History
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Start a review session, reinforce what you know, and view your completed review history.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="review.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Review Now</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 4 - Reports & Statistics -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="4" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-purple-500/20 border border-purple-500/30 backdrop-blur-md text-purple-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">monitoring</span>
                                        <span>Reports & Statistics</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        See Your Learning Progress
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Explore detailed statistics and reports to understand your review activity and learning progress.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="reports.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>View Reports</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>
                </div>

            </div>

                        <?php
            // ---- "Due Now" call-to-action state ----
            $dueTotal     = (int) ($metricsData['cardsDue'] ?? 0);
            $dueDecks     = $dueByDeck ?? [];
            $nextDueSecs  = $nextDueSecs ?? null;
            $curStreak    = (int) ($streakData['currentStreak'] ?? 0);
            $streakAtRisk = $dueTotal > 0 && $curStreak > 0 && (int) ($metricsData['cardsReviewedToday'] ?? 0) === 0;
            $reviewHref   = count($dueDecks) === 1 ? 'review.php?deck_id=' . (int) $dueDecks[0]['id'] : 'review.php';

            if ($streakAtRisk) {
                $dueSubtitle = 'Keep your ' . $curStreak . '-day streak alive';
            } elseif (count($dueDecks) === 1) {
                $dueSubtitle = 'in ' . $dueDecks[0]['title'];
            } elseif (count($dueDecks) > 1) {
                $dueSubtitle = 'across ' . count($dueDecks) . ' decks';
            } else {
                $dueSubtitle = '';
            }

            $nextDueLabel = '';
            if ($nextDueSecs !== null && $nextDueSecs > 0) {
                if ($nextDueSecs < 3600)       $nextDueLabel = 'Next cards due in ' . max(1, (int) ceil($nextDueSecs / 60)) . ' min';
                elseif ($nextDueSecs < 86400)  $nextDueLabel = 'Next cards due in ' . (int) floor($nextDueSecs / 3600) . ' h';
                else                           $nextDueLabel = 'Next cards due in ' . (int) floor($nextDueSecs / 86400) . ' d';
            }
            ?>

            <!-- DIV: ROW 2 - Grid Split: Streak Stats (Left, matches heatmap) vs Due Now CTA (Right, matches carousel) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">

                <!-- DIV: LEFT COLUMN - Streak Analytics Metric Bar -->
                <div class="lg:col-span-6 flex w-full min-w-0 order-last lg:order-none">
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-2 sm:p-2.5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-1 sm:gap-3 w-full h-full">
                        <!-- DIV: Metric 1 - Best Historical Streak -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-primary/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-primary-fixed/60 border border-primary-fixed-dim/40 flex items-center justify-center text-primary flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">emoji_events</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Best Streak</span>
                                <span id="stat-best-streak" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-primary transition-colors">
                                    <?php echo (int) $streakData['bestStreak']; ?> days
                                </span>
                            </div>
                        </div>
                        <!-- DIV: Vertical Divider -->
                        <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>

                        <!-- DIV: Metric 2 - Current Active Streak -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-amber-500/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-500 flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">local_fire_department</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Current Streak</span>
                                <span id="stat-current-streak" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-amber-600 transition-colors">
                                    <?php echo (int) $streakData['currentStreak']; ?> days
                                </span>
                            </div>
                        </div>
                        <!-- DIV: Vertical Divider -->
                        <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>

                        <!-- DIV: Metric 3 - Streak Accuracy Percentage -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-teal-500/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-500 flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">track_changes</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Streak Acc.</span>
                                <span id="stat-streak-accuracy" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-teal-600 transition-colors">
                                    <?php echo (float) $streakData['streakAccuracy']; ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DIV: RIGHT COLUMN - Due Now Call-To-Action (same width as carousel / recent sessions) -->
                <div class="lg:col-span-6 flex w-full min-w-0 order-first lg:order-none">

                    <?php if ($dueTotal > 0): ?>
                        <!-- A: Cards due - primary call-to-action -->
                        <a href="<?php echo htmlspecialchars($reviewHref, ENT_QUOTES, 'UTF-8'); ?>"
                           id="stat-due-cta"
                           aria-label="Review <?php echo $dueTotal; ?> due <?php echo $dueTotal === 1 ? 'card' : 'cards'; ?>"
                           class="group/cta relative overflow-hidden flex items-center justify-between gap-4 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-on-primary shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                            <!-- DIV: Ambient Glow -->
                            <div class="absolute -right-6 -top-8 w-28 h-28 bg-white/15 rounded-full blur-2xl pointer-events-none"></div>

                            <!-- DIV: Icon + Text -->
                            <div class="relative z-10 flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-[22px]">alarm</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-primary/80">Due Now</span>
                                    <span class="text-base sm:text-lg font-extrabold leading-tight truncate">
                                        <span id="stat-due-cta-count"><?php echo $dueTotal; ?></span>
                                        <?php echo $dueTotal === 1 ? 'card' : 'cards'; ?> ready to review
                                    </span>
                                    <?php if ($dueSubtitle !== ''): ?>
                                        <span class="text-[11px] sm:text-xs text-on-primary/80 truncate"><?php echo htmlspecialchars($dueSubtitle, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- DIV: Action Pill -->
                            <span class="relative z-10 inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl bg-white text-primary font-label-md text-xs font-bold shadow-xs flex-shrink-0">
                                <span class="hidden sm:inline">Start Review</span>
                                <span class="material-symbols-outlined text-[18px] transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                            </span>
                        </a>

                    <?php elseif ($nextDueSecs !== null): ?>
                        <!-- B: Nothing due, cards are scheduled -->
                        <div id="stat-due-cta" class="flex items-center gap-3 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                <span class="material-symbols-outlined text-[22px]">task_alt</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                                <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface">All caught up</span>
                                <?php if ($nextDueLabel !== ''): ?>
                                    <span class="text-[11px] sm:text-xs text-on-surface-variant"><?php echo htmlspecialchars($nextDueLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- C: No cards at all - point to creation -->
                        <a href="decks.php?add_card=1" id="stat-due-cta"
                           class="group/cta flex items-center justify-between gap-4 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs hover:border-primary/50 hover:shadow-md transition-all">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                                    <span class="material-symbols-outlined text-[22px]">add</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">No Due Today</span>
                                    <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface truncate">Create new cards</span>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-[20px] text-primary flex-shrink-0 transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                        </a>
                    <?php endif; ?>

                </div>
            </div>

        </div>

        <!-- DIV: BOTTOM SECTION - Streak Heatmap Grid & Recent Activity History -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 w-full items-stretch mt-2">
            
            <!-- DIV: Streak Heatmap Card Container Shell -->
            <div class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between min-h-[320px] min-w-0 overflow-visible">
                
                <!-- DIV: Heatmap Title & Intensity Legend Wrapper -->
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">calendar_month</span>
                            <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Streak Heatmap</h3>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">
                            Last 365 days • <span class="font-semibold text-on-surface"><?php echo (int) $streakData['studyDays']; ?> / 365</span> study days
                        </p>
                    </div>

                    <div class="flex items-center gap-1.5 font-label-sm text-[10px] text-on-surface-variant">
                        <span>Less</span>
                        <span class="w-3 h-3 rounded" style="background-color: transparent; border: 1px solid rgba(150,150,150,0.3);" title="0 reviews"></span>
                        <span class="w-3 h-3 rounded" style="background-color: #86efac;" title="1-14 reviews"></span>
                        <span class="w-3 h-3 rounded" style="background-color: #4ade80;" title="15-39 reviews"></span>
                        <span class="w-3 h-3 rounded" style="background-color: #16a34a;" title="40-79 reviews"></span>
                        <span class="w-3 h-3 rounded" style="background-color: #15803d;" title="80+ reviews"></span>
                        <span>More</span>
                    </div>
                </div>

                <!-- DIV: Heatmap CSS Grid Scroll Area Wrapper -->
                <div id="heatmap-scroll" class="flex-1 flex items-center py-4 w-full overflow-x-auto">
                    <div class="w-max grid justify-start" style="--cell: 16px; gap: 3px; grid-template-columns: auto repeat(53, var(--cell)); grid-template-rows: auto repeat(7, var(--cell));">

                        <?php foreach ([3 => 'Mon', 5 => 'Wed', 7 => 'Fri'] as $row => $name): ?>
                            <!-- SPAN: Day Name Axis Labels -->
                            <span class="flex items-center pr-2 text-[10px] leading-none whitespace-nowrap text-on-surface-variant select-none" style="grid-column: 1; grid-row: <?php echo $row; ?>;">
                                <?php echo $name; ?>
                            </span>
                        <?php endforeach; ?>

                        <?php
                        $weekStart = clone $streakData['calendarStart'];
                        for ($w = 0; $w < 53; $w++) {
                            for ($d = 0; $d < 7; $d++) {
                                $date = clone $weekStart;
                                $date->modify('+' . $d . ' days');

                                if ($date < $streakData['heatmapStart'] || $date > $streakData['heatmapEnd']) continue;

                                if ($date->format('j') === '1' && $w <= 51) {
                                    echo '<div class="text-[10px] leading-none font-medium whitespace-nowrap text-on-surface-variant" style="grid-row: 1; grid-column: ' . ($w + 2) . ' / span 3;">' . htmlspecialchars($date->format('M'), ENT_QUOTES) . '</div>';
                                }

                                $key = $date->format('Y-m-d');
                                $reviews = $streakData['heatmapData'][$key] ?? 0;
                                $tooltip = $date->format('M j, Y') . ' • ' . ($reviews === 0 ? 'No reviews' : ($reviews === 1 ? '1 review' : $reviews . ' reviews'));
                                echo '<div class="heatmap-cell rounded-[4px] transition-transform hover:scale-110" style="grid-row: ' . ($d + 2) . '; grid-column: ' . ($w + 2) . '; ' . heatmap_level_style($reviews) . '" data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"></div>';                            }
                            $weekStart->modify('+7 days');
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- DIV: Recent Review Sessions Feed Card Shell -->
            <div class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between min-h-[290px] min-w-0">
                
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">history</span>
                            <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent Review Sessions</h3>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Your latest spaced repetition drills</p>
                    </div>
                    <a href="sessions.php" class="text-xs text-primary hover:underline font-semibold flex items-center gap-1 transition-all">
                        <span>View All</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>

                <!-- DIV: Session Items List Wrapper Container -->
                <div class="flex flex-col gap-2.5 flex-1 justify-center">
                    <?php if (!empty($recentSessions)): ?>
                        <?php foreach ($recentSessions as $session): ?>
                            <!-- DIV: Individual Session Card Shell -->
                            <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low border border-outline-variant/20 hover:border-primary/30 hover:bg-surface-container transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-[18px]">style</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-on-surface truncate">
                                            <?php echo htmlspecialchars($session['deckTitle'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <div class="flex items-center gap-2 text-[11px] text-on-surface-variant mt-0.5">
                                            <span><?php echo (int) $session['cardsReviewed']; ?> cards reviewed</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end flex-shrink-0 ml-2">
                                    <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                                        <?php echo (int) $session['accuracy']; ?>%
                                    </span>
                                    <span class="text-[10px] text-on-surface-variant/70 mt-1">
                                        <?php echo htmlspecialchars($session['formattedTime'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- DIV: Empty State Fallback Shell -->
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant h-full">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2">history_edu</span>
                            <span class="text-sm font-medium">No recent review sessions found.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">Complete a study session to see your history here.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>
```

