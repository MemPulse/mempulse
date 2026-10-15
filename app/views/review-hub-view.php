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
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8" id="review-hub-root">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

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
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-primary">Due Now</span>
                            <div class="flex items-center gap-2 min-w-0">
                                <h2 class="text-base sm:text-lg font-extrabold text-on-surface leading-tight truncate m-0">
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
                                <p class="text-xs text-on-surface-variant/80 font-medium truncate m-0 mt-0.5"><?= $h($dueSubtitle) ?></p>
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
                           class="inline-flex items-center justify-center gap-2 px-4 py-2 sm:py-2.5 rounded-xl bg-primary text-on-primary font-bold text-xs sm:text-sm shadow-xs hover:bg-primary-container transition-colors w-full sm:w-auto">
                            <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                            <span>Start Review</span>
                        </a>
                    </div>

                <?php elseif ($nextDueSecs !== null): ?>
                    <!-- B: All Caught Up State -->
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[22px] sm:text-[24px]">task_alt</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-emerald-700">Due Now</span>
                            <h2 class="text-base sm:text-lg font-extrabold text-on-surface leading-tight m-0">All caught up</h2>
                            <?php if ($nextDueLabel !== ''): ?>
                                <p class="text-xs text-on-surface-variant/80 font-medium truncate m-0 mt-0.5"><?= $h($nextDueLabel) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- C: No Cards Scheduled State -->
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-[22px] sm:text-[24px]">add</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-primary">Due Now</span>
                            <h2 class="text-base sm:text-lg font-extrabold text-on-surface leading-tight m-0">No cards found</h2>
                            <p class="text-xs text-on-surface-variant/80 font-medium truncate m-0 mt-0.5">Create flashcards to start practicing</p>
                        </div>
                    </div>

                    <a href="decks.php?add_card=1" id="stat-due-cta"
                       class="inline-flex items-center justify-center gap-2 px-4 py-2 sm:py-2.5 rounded-xl bg-primary text-on-primary font-bold text-xs sm:text-sm shadow-xs hover:bg-primary-container transition-colors shrink-0 w-full sm:w-auto">
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

$badgeCls = 'px-2 py-0.5 rounded-full bg-primary/10 text-primary text-[11px] font-bold tracking-wide whitespace-nowrap';
?>

        <!-- Colour probe: lets the table CSS reuse your Tailwind design tokens -->
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
        <section class="flex flex-col gap-3 sm:gap-4 w-full min-w-0" id="due-now-section">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5 min-w-0">
                    <h2 class="text-base sm:text-lg font-extrabold tracking-tight text-on-surface whitespace-nowrap m-0">Due Now</h2>
                    <span class="<?= $badgeCls ?>"><?= count($dueRows) ?> <?= count($dueRows) === 1 ? 'Deck' : 'Decks' ?></span>
                    <span class="hidden sm:block w-px h-4 bg-outline-variant/40 shrink-0" aria-hidden="true"></span>
                    <p class="hidden sm:block truncate text-xs text-on-surface-variant/80 font-medium m-0">Decks with cards ready to review</p>
                </div>
            </div>

            <?php if (empty($dueRows)): ?>
                <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs py-8 px-4 flex flex-col items-center justify-center text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[28px] text-outline/80 mb-1.5">task_alt</span>
                    <span class="text-sm font-semibold text-on-surface">All caught up!</span>
                    <span class="text-xs text-on-surface-variant/70 mt-0.5">You have no cards due for review right now.</span>
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
        <section class="flex flex-col gap-3 sm:gap-4 w-full min-w-0" id="upcoming-section">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5 min-w-0">
                    <h2 class="text-base sm:text-lg font-extrabold tracking-tight text-on-surface whitespace-nowrap m-0">Upcoming (Next <?= (int) (ReviewHubService::UPCOMING_DAYS * 24) ?>h)</h2>
                    <span class="<?= $badgeCls ?>"><?= count($upcomingRows) ?> <?= count($upcomingRows) === 1 ? 'Deck' : 'Decks' ?></span>
                    <span class="hidden sm:block w-px h-4 bg-outline-variant/40 shrink-0" aria-hidden="true"></span>
                    <p class="hidden sm:block truncate text-xs text-on-surface-variant/80 font-medium m-0">Cards that will be due soon</p>
                </div>
            </div>

            <?php if (empty($upcomingRows)): ?>
                <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs py-8 px-4 flex flex-col items-center justify-center text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[28px] text-outline/80 mb-1.5">schedule</span>
                    <span class="text-sm font-semibold text-on-surface">No upcoming reviews</span>
                    <span class="text-xs text-on-surface-variant/70 mt-0.5">No cards scheduled for the next <?= (int) (ReviewHubService::UPCOMING_DAYS * 24) ?> hours.</span>
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
        <section class="flex flex-col gap-3 sm:gap-4 w-full min-w-0" id="recent-sessions-section">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5 min-w-0">
                    <h2 class="text-base sm:text-lg font-extrabold tracking-tight text-on-surface whitespace-nowrap m-0">Recent Review Sessions</h2>
                    <span class="<?= $badgeCls ?>"><?= count($recentRows) ?> <?= count($recentRows) === 1 ? 'Session' : 'Sessions' ?></span>
                    <span class="hidden sm:block w-px h-4 bg-outline-variant/40 shrink-0" aria-hidden="true"></span>
                    <p class="hidden sm:block truncate text-xs text-on-surface-variant/80 font-medium m-0">Your latest spaced repetition drills</p>
                </div>
                <a href="sessions.php" class="shrink-0 text-xs text-primary hover:underline font-semibold flex items-center gap-1 transition-all whitespace-nowrap">
                    <span>View History</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>

            <?php if (empty($recentRows)): ?>
                <div class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs py-8 px-4 flex flex-col items-center justify-center text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[28px] text-outline/80 mb-1.5">history_edu</span>
                    <span class="text-sm font-semibold text-on-surface">No recent review sessions found</span>
                    <span class="text-xs text-on-surface-variant/70 mt-0.5">Complete a study session to see your history here.</span>
                </div>
            <?php else: ?>
                <div class="mp-table rounded-2xl border border-outline-variant/30 bg-surface-container-lowest shadow-xs overflow-x-auto">
                    <div id="recent-sessions-table"></div>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<!-- Tabulator CSS stylesheet -->
<link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_simple.min.css" rel="stylesheet">

<!-- Read-only Tabulator styling: structure + responsive mobile rules -->
<style>
    .mp-table .tabulator { background: transparent; border: 0; font-family: inherit; font-size: .8125rem; color: var(--mp-text, inherit); }
    .mp-table .tabulator .tabulator-header { background: var(--mp-head-bg, #f1f1f1) !important; color: var(--mp-head-text, inherit); border-top: 0; border-bottom: 1px solid var(--mp-border, #ddd); }
    .mp-table .tabulator .tabulator-header .tabulator-col { background: transparent !important; border-right: 0; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-content { padding: .625rem 1rem; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .mp-table .tabulator .tabulator-tableholder,
    .mp-table .tabulator .tabulator-tableholder .tabulator-table { background: transparent; }
    .mp-table .tabulator .tabulator-row,
    .mp-table .tabulator .tabulator-row.tabulator-row-even,
    .mp-table .tabulator .tabulator-row.tabulator-row-odd { background: transparent !important; color: inherit; border-bottom: 1px solid var(--mp-divider, #eee); min-height: 0; }
    .mp-table .tabulator .tabulator-row:hover { background: var(--mp-hover, rgba(0,0,0,.03)) !important; }
    .mp-table .tabulator .tabulator-table > .tabulator-row:last-child { border-bottom: 0; }
    .mp-table .tabulator .tabulator-row .tabulator-cell { padding: .625rem 1rem; border-right: 0; vertical-align: middle; }
    .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-resize-handle { display: none; }
    
    @media (max-width: 640px) {
        .mp-table .tabulator .tabulator-header .tabulator-col .tabulator-col-content,
        .mp-table .tabulator .tabulator-row .tabulator-cell { padding: .5rem .625rem; font-size: .75rem; }
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

    var muted = 'text-xs text-on-surface-variant tabular-nums whitespace-nowrap';

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
              formatter: function (c) { return '<span class="inline-block text-[11px] font-bold text-rose-700 bg-rose-50 border border-rose-200/80 px-2 py-0.5 rounded-md tabular-nums">' + esc(c.getValue()) + '</span>'; } },
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
              formatter: function (c) { return '<span class="inline-block text-[11px] font-bold text-on-surface-variant bg-surface-container-high px-2 py-0.5 rounded-md tabular-nums">' + esc(c.getValue()) + '</span>'; } },
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
                  return '<span class="inline-block text-[11px] font-bold border px-2 py-0.5 rounded-md tabular-nums ' + esc(r.accuracyCls) + '">' + esc(r.accuracy) + '%</span>';
              } },
            { title: 'Duration', field: 'duration', minWidth: 75, hozAlign: 'right', headerHozAlign: 'right', responsive: 3,
              formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
            { title: 'When', field: 'when', minWidth: 85, hozAlign: 'right', headerHozAlign: 'right', responsive: 1,
              formatter: function (c) { return '<span class="' + muted + '">' + esc(c.getValue()) + '</span>'; } },
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