<?php
// Expects (from content.php): $filters, $summary, $list.
$f   = $filters;
$s   = $summary;
$fmt = fn($n) => number_format((int) $n);
$hasFilters = $f['q'] !== '' || $f['filter'] !== '';

$totalCards = max(1, $s['activeCards']);
$tiers = [
    [
        'label'    => 'New (Unreviewed)',
        'count'    => $s['newCards'],
        'pct'      => round(($s['newCards'] / $totalCards) * 100),
        'bar'      => 'bg-primary',
        'card'     => 'from-primary/10 hover:border-primary/50',
        'text'     => 'text-primary',
        'tile'     => 'bg-primary/10 group-hover:bg-primary/20 text-primary',
    ],
    [
        'label'    => 'Learning (1–3 days)',
        'count'    => $s['learningCards'],
        'pct'      => round(($s['learningCards'] / $totalCards) * 100),
        'bar'      => 'bg-rose-500',
        'card'     => 'from-rose-500/10 hover:border-rose-500/50',
        'text'     => 'text-rose-700',
        'tile'     => 'bg-rose-500/10 group-hover:bg-rose-500/20 text-rose-700',
    ],
    [
        'label'    => 'Young (4–20 days)',
        'count'    => $s['youngCards'],
        'pct'      => round(($s['youngCards'] / $totalCards) * 100),
        'bar'      => 'bg-teal-500',
        'card'     => 'from-teal-500/10 hover:border-teal-500/50',
        'text'     => 'text-teal-700',
        'tile'     => 'bg-teal-500/10 group-hover:bg-teal-500/20 text-teal-600',
    ],
    [
        'label'    => 'Mature (21+ days)',
        'count'    => $s['matureCards'],
        'pct'      => round(($s['matureCards'] / $totalCards) * 100),
        'bar'      => 'bg-primary',
        'card'     => 'from-primary/10 hover:border-primary/50',
        'text'     => 'text-primary',
        'tile'     => 'bg-primary/10 group-hover:bg-primary/20 text-primary',
    ],
];

$tabs = [
    ''            => 'All Learners',
    'has_decks'   => 'With Active Decks',
    'has_due'     => 'With Cards Due',
    'high_lapses' => 'High Lapses (10+)',
    'empty'       => 'No Decks Yet',
];

$sortUrls = [];
foreach (['name', 'decks', 'cards', 'due', 'mature', 'interval', 'lapses'] as $key) {
    $nextDir = ($f['sort'] === $key && $f['dir'] === 'asc') ? 'desc' : 'asc';
    $sortUrls[$key] = 'content.php' . AdminContentService::buildQuery($f, ['sort' => $key, 'dir' => $nextDir, 'page' => 1]);
}

$exportUrl  = 'content.php' . AdminContentService::buildQuery($f, ['export' => 'csv']);
$input      = 'w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/40';
$pageBtn    = 'min-w-8 h-8 px-2 rounded-lg flex items-center justify-center text-xs font-semibold tabular-nums transition-colors ';
$firstShown = max(1, $list['page'] - 2);
$lastShown  = min($list['pages'], $list['page'] + 2);
?>

<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <!-- HEADER -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="material-symbols-outlined text-[28px] text-primary" aria-hidden="true">library_books</span>
                    <h1 class="font-headline-md text-xl sm:text-2xl font-bold text-on-surface m-0">Platform Content &amp; SRS Health</h1>
                </div>
                <p class="text-xs sm:text-sm text-on-surface-variant">
                    Aggregate deck volume, spaced-repetition card maturity, and review queue health. Individual deck titles and card text remain private.
                </p>
            </div>
            <a href="<?= adminEsc($exportUrl) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/30 text-on-surface text-xs font-semibold transition-colors">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Export CSV</span>
            </a>
        </div>

        <!-- 4 KPI SUMMARY CARDS -->
        <section aria-label="Content summary metrics" class="grid grid-cols-4 gap-2 sm:gap-3 lg:gap-4" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr))">

            <!-- Active Decks (primary) -->
            <div class="min-w-0 bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-primary/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-primary">Active Decks</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['activeDecks']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant"><?= $fmt($s['archivedDecks']) ?> archived</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-primary/10 group-hover:bg-primary/20 text-primary flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">style</span>
                </div>
            </div>

            <!-- Active Flashcards (rose) -->
            <div class="min-w-0 bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-rose-500/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-rose-700">Active Flashcards</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['activeCards']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-rose-700 font-medium">+<?= $fmt($s['created7d']) ?> added this week</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-rose-500/10 group-hover:bg-rose-500/20 text-rose-700 flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">layers</span>
                </div>
            </div>

            <!-- Cards Due Now (teal) -->
            <div class="min-w-0 bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-teal-500/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-teal-700">Cards Due Now</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['dueNow']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant">Ready for review across decks</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-teal-500/10 group-hover:bg-teal-500/20 text-teal-600 flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">schedule</span>
                </div>
            </div>

            <!-- Avg SRS Interval (primary) -->
            <div class="min-w-0 bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-primary/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-primary">Avg SRS Interval</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= number_format($s['avgInterval'], 1) ?>d</p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant"><?= $fmt($s['totalLapses']) ?> total lapses</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-primary/10 group-hover:bg-primary/20 text-primary flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">timeline</span>
                </div>
            </div>

        </section>

      <!-- SRS CARD MATURITY DISTRIBUTION CHART -->
        <section id="maturity-section" class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="font-headline-sm text-base font-bold text-on-surface m-0">Spaced-Repetition Maturity Breakdown</h2>
                    <p class="text-xs text-on-surface-variant mt-0.5">Distribution of active flashcards by current memory interval</p>
                </div>
                <span class="text-xs font-bold text-on-surface-variant tabular-nums"><?= $fmt($s['activeCards']) ?> total active cards</span>
            </div>

            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:1.5rem;">

                <!-- Doughnut -->
                <div style="position:relative;flex:0 0 220px;width:220px;height:220px;margin:0 auto;">
                    <canvas id="maturity-chart" aria-label="Flashcard maturity distribution" role="img"></canvas>
                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
                        <span class="font-headline-md text-2xl font-extrabold text-on-surface tabular-nums"><?= $fmt($s['activeCards']) ?></span>
                        <span class="text-[11px] font-bold uppercase tracking-wide text-on-surface-variant">Active cards</span>
                    </div>
                </div>

                <!-- Tier cards (act as the legend) -->
                <div style="flex:1 1 320px;min-width:0;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.75rem;">
                    <?php foreach ($tiers as $i => $t): ?>
                        <div data-tier="<?= $i ?>" class="min-w-0 bg-gradient-to-br <?= $t['card'] ?> via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 transition-all group">
                            <div class="min-w-0">
                                <span class="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-bold uppercase tracking-wide <?= $t['text'] ?>">
                                    <span data-swatch class="<?= $t['bar'] ?>" style="display:inline-block;width:8px;height:8px;border-radius:9999px;flex:none;"></span>
                                    <span class="truncate"><?= adminEsc($t['label']) ?></span>
                                </span>
                                <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($t['count']) ?></p>
                                <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant">of active cards</span>
                            </div>
                            <div class="min-w-8 h-8 sm:min-w-10 sm:h-10 px-1.5 rounded-xl <?= $t['tile'] ?> flex items-center justify-center shrink-0 transition-colors">
                                <span class="text-[10px] sm:text-xs font-bold tabular-nums"><?= (int) $t['pct'] ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <script>
        (function () {
            function init() {
                var canvas  = document.getElementById('maturity-chart');
                var section = document.getElementById('maturity-section');
                if (!canvas || !section || typeof Chart === 'undefined') return;

                var labels = <?= json_encode(array_column($tiers, 'label'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
                var counts = <?= json_encode(array_map('intval', array_column($tiers, 'count'))) ?>;
                var total  = counts.reduce(function (a, b) { return a + b; }, 0);

                // Read the segment colors from the legend dots so the chart follows your theme (incl. bg-primary)
                var colors = Array.prototype.map.call(section.querySelectorAll('[data-swatch]'), function (el) {
                    return getComputedStyle(el).backgroundColor;
                });
                var gap = getComputedStyle(section).backgroundColor;

                var empty = total === 0;
                var chart = new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: empty ? [1] : counts,
                            backgroundColor: empty ? ['#e2e8f0'] : colors,
                            borderColor: gap,
                            borderWidth: 3,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        cutout: '68%',
                        maintainAspectRatio: false,
                        animation: { duration: 600 },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: !empty,
                                callbacks: {
                                    label: function (ctx) {
                                        var v   = ctx.parsed;
                                        var pct = total ? Math.round(v / total * 100) : 0;
                                        return ' ' + v.toLocaleString() + ' cards (' + pct + '%)';
                                    }
                                }
                            }
                        }
                    }
                });

                // Hovering a tier card highlights its segment
                if (!empty) {
                    section.querySelectorAll('[data-tier]').forEach(function (card) {
                        var i = parseInt(card.getAttribute('data-tier'), 10);
                        card.addEventListener('mouseenter', function () {
                            chart.setActiveElements([{ datasetIndex: 0, index: i }]);
                            chart.update();
                        });
                        card.addEventListener('mouseleave', function () {
                            chart.setActiveElements([]);
                            chart.update();
                        });
                    });
                }
            }

            // `defer` scripts run before DOMContentLoaded, so this is safe whether Chart.js is in head.php or already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
        </script>

        <!-- PER-USER CONTENT INVENTORY TABLE -->
        <style>
            /* Fixed column widths + compact rows (plain CSS so it doesn't depend on compiled Tailwind) */
            #content-inventory .content-table { table-layout: fixed; width: 100%; min-width: 640px; border-collapse: collapse; }
            #content-inventory .content-table th,
            #content-inventory .content-table td {
                padding: 6px 12px;
                vertical-align: middle;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            #content-inventory .content-table th { padding-top: 8px; padding-bottom: 8px; }
            #content-inventory .content-table th.c-learner,
            #content-inventory .content-table td.c-learner { padding-left: 16px; }
            #content-inventory .content-table th.c-lapses,
            #content-inventory .content-table td.c-lapses { padding-right: 16px; text-align: right; }

            /* Column widths (Learner takes the remaining space) */
            #content-inventory col.c-decks    { width: 140px; }
            #content-inventory col.c-cards    { width: 100px; }
            #content-inventory col.c-due      { width: 100px; }
            #content-inventory col.c-mature   { width: 120px; }
            #content-inventory col.c-interval { width: 120px; }
            #content-inventory col.c-lapses   { width: 100px; }

            /* Responsive column hiding (applies to <col>, <th> and <td> so the layout stays in sync) */
            #content-inventory .content-table .col-md,
            #content-inventory .content-table .col-lg { display: none; }
            @media (min-width: 768px) {
                #content-inventory .content-table th.col-md,
                #content-inventory .content-table td.col-md { display: table-cell; }
                #content-inventory .content-table col.col-md { display: table-column; }
            }
            @media (min-width: 1024px) {
                #content-inventory .content-table th.col-lg,
                #content-inventory .content-table td.col-lg { display: table-cell; }
                #content-inventory .content-table col.col-lg { display: table-column; }
            }

            /* Single-line learner cell: name + email */
            #content-inventory .learner-cell { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
            #content-inventory .learner-name {
                flex: 0 1 auto; max-width: 55%;
                overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            }
            #content-inventory .learner-email {
                flex: 1 1 0; min-width: 0;
                overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            }

            /* Loading state while the table refreshes */
            #content-inventory .content-table-wrap { transition: opacity .15s; }
            #content-inventory.is-loading .content-table-wrap { opacity: .5; pointer-events: none; }
        </style>

        <section id="content-inventory" class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden min-w-0" aria-label="Per-user content footprint">

            <!-- Filter Tabs -->
            <nav data-tabs class="flex gap-1 overflow-x-auto px-3 pt-3 border-b border-outline-variant/30">
                <?php foreach ($tabs as $key => $label):
                    $on = $f['filter'] === $key; ?>
                    <a href="content.php<?= adminEsc(AdminContentService::buildQuery($f, ['filter' => $key, 'page' => 1])) ?>"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-semibold whitespace-nowrap border-b-2 -mb-px transition-colors <?= $on ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
                        <?= adminEsc($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Search Bar -->
            <form data-ajax method="get" action="content.php" class="flex flex-wrap items-center gap-3 p-4 border-b border-outline-variant/30">
                <input type="hidden" name="filter" value="<?= adminEsc($f['filter']) ?>">
                <input type="hidden" name="sort" value="<?= adminEsc($f['sort']) ?>">
                <input type="hidden" name="dir" value="<?= adminEsc($f['dir']) ?>">

                <div class="flex-1 min-w-[220px]">
                    <input type="search" name="q" value="<?= adminEsc($f['q']) ?>" maxlength="100" placeholder="Search learner by name or email…" class="<?= $input ?>">
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:opacity-90 transition-opacity">Search</button>
                <?php if ($hasFilters): ?>
                    <a href="content.php" class="px-3 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface text-sm font-semibold transition-colors">Reset</a>
                <?php endif; ?>
            </form>

            <?php if ($list['rows']): ?>
                <div class="overflow-x-auto content-table-wrap">
                    <table class="content-table text-left">
                        <colgroup>
                            <col class="c-learner">
                            <col class="c-decks">
                            <col class="c-cards">
                            <col class="c-due">
                            <col class="c-mature col-md">
                            <col class="c-interval col-lg">
                            <col class="c-lapses">
                        </colgroup>
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/30 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <?php
                                $headers = [
                                    'name'     => ['label' => 'Learner',       'class' => 'c-learner'],
                                    'decks'    => ['label' => 'Active Decks',  'class' => 'c-decks'],
                                    'cards'    => ['label' => 'Active Cards',  'class' => 'c-cards'],
                                    'due'      => ['label' => 'Due Now',       'class' => 'c-due'],
                                    'mature'   => ['label' => 'Mature (21d+)', 'class' => 'c-mature col-md'],
                                    'interval' => ['label' => 'Avg Interval',  'class' => 'c-interval col-lg'],
                                    'lapses'   => ['label' => 'Total Lapses',  'class' => 'c-lapses'],
                                ];
                                foreach ($headers as $key => $h):
                                    $active = $f['sort'] === $key;
                                    $icon   = $active ? ($f['dir'] === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more';
                                ?>
                                    <th class="<?= $h['class'] ?>">
                                        <a href="<?= adminEsc($sortUrls[$key]) ?>" class="inline-flex items-center gap-1 hover:text-on-surface transition-colors">
                                            <span><?= adminEsc($h['label']) ?></span>
                                            <span class="material-symbols-outlined text-[14px] <?= $active ? 'text-primary' : 'opacity-40' ?>"><?= $icon ?></span>
                                        </a>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20 text-sm">
                            <?php foreach ($list['rows'] as $r): ?>
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="c-learner">
                                        <div class="learner-cell">
                                            <a href="users.php?q=<?= urlencode($r['email']) ?>"
                                            class="learner-name text-xs font-bold text-on-surface hover:text-primary hover:underline"
                                            title="<?= adminEsc($r['name']) ?>"><?= adminEsc($r['name']) ?></a>
                                            <span class="learner-email text-[11px] text-on-surface-variant"
                                                title="<?= adminEsc($r['email']) ?>"><?= adminEsc($r['email']) ?></span>
                                        </div>
                                    </td>
                                    <td class="c-decks tabular-nums text-xs">
                                        <span class="font-bold text-on-surface"><?= $fmt($r['activeDecks']) ?></span>
                                        <?php if ($r['archivedDecks'] > 0): ?>
                                            <span class="text-[11px] text-on-surface-variant ml-1">(+<?= $fmt($r['archivedDecks']) ?> arch)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="c-cards tabular-nums text-xs font-bold text-on-surface">
                                        <?= $fmt($r['activeCards']) ?>
                                    </td>
                                    <td class="c-due tabular-nums text-xs">
                                        <?php if ($r['dueCards'] > 0): ?>
                                            <span class="px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200/80 text-amber-700 text-[11px] font-bold"><?= $fmt($r['dueCards']) ?> due</span>
                                        <?php else: ?>
                                            <span class="text-on-surface-variant">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="c-mature col-md tabular-nums text-xs">
                                        <span class="text-teal-700 font-semibold"><?= $fmt($r['matureCards']) ?></span>
                                    </td>
                                    <td class="c-interval col-lg tabular-nums text-xs text-on-surface-variant">
                                        <?= number_format($r['avgInterval'], 1) ?> days
                                    </td>
                                    <td class="c-lapses tabular-nums text-xs font-semibold <?= $r['totalLapses'] >= 10 ? 'text-error' : 'text-on-surface-variant' ?>">
                                        <?= $fmt($r['totalLapses']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="flex flex-wrap items-center justify-between gap-3 px-5 py-2.5 border-t border-outline-variant/30" aria-label="Pagination">
                    <p class="text-xs text-on-surface-variant">
                        Showing <?= $fmt($list['first']) ?>&ndash;<?= $fmt($list['last']) ?> of <?= $fmt($list['total']) ?> learners
                    </p>
                    <?php if ($list['pages'] > 1): ?>
                        <div class="flex items-center gap-1">
                            <?php for ($p = $firstShown; $p <= $lastShown; $p++): ?>
                                <?php if ($p === $list['page']): ?>
                                    <span aria-current="page" class="<?= $pageBtn ?>bg-primary text-on-primary"><?= $p ?></span>
                                <?php else: ?>
                                    <a href="content.php<?= adminEsc(AdminContentService::buildQuery($f, ['page' => $p])) ?>" class="<?= $pageBtn ?>text-on-surface-variant hover:bg-surface-container-high hover:text-primary"><?= $p ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </nav>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center p-12 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[36px] text-outline mb-2">library_books</span>
                    <span class="text-sm font-semibold text-on-surface">No learner content matches these filters.</span>
                </div>
            <?php endif; ?>
        </section>

        <script>
        (function () {
            var region = document.getElementById('content-inventory');
            if (!region) return;

            var controller = null;
            history.replaceState({ ajax: true }, '', location.href);

            function load(url, push) {
                if (controller) controller.abort();
                var ctrl = controller = new AbortController();
                region.classList.add('is-loading');

                fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'fetch' },
                    signal: ctrl.signal
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.text();
                    })
                    .then(function (html) {
                        var doc   = new DOMParser().parseFromString(html, 'text/html');
                        var fresh = doc.getElementById('content-inventory');
                        if (!fresh) throw new Error('Section not found');

                        var nav = region.querySelector('[data-tabs]');
                        var tabScroll = nav ? nav.scrollLeft : 0;

                        region.innerHTML = fresh.innerHTML;

                        var newNav = region.querySelector('[data-tabs]');
                        if (newNav) newNav.scrollLeft = tabScroll;

                        if (push) history.pushState({ ajax: true }, '', url);
                    })
                    .catch(function (err) {
                        if (err.name !== 'AbortError') window.location.href = url; // fall back to a normal page load
                    })
                    .finally(function () {
                        if (controller === ctrl) {
                            controller = null;
                            region.classList.remove('is-loading');
                        }
                    });
            }

            // Tabs, sort headers, pagination, Reset
            region.addEventListener('click', function (e) {
                var a = e.target.closest('a[href]');
                if (!a || !region.contains(a)) return;
                if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

                var url = new URL(a.href, location.href);
                if (url.origin !== location.origin || !/content\.php$/.test(url.pathname)) return; // e.g. skip users.php links

                e.preventDefault();
                load(url.href, true);
            });

            // Search form
            region.addEventListener('submit', function (e) {
                var form = e.target;
                if (!form.hasAttribute('data-ajax')) return;
                e.preventDefault();

                var url = new URL(form.getAttribute('action'), location.href);
                url.search = new URLSearchParams(new FormData(form)).toString();
                load(url.href, true);
            });

            // Back / forward buttons
            window.addEventListener('popstate', function (e) {
                if (e.state && e.state.ajax) load(location.href, false);
            });
        })();
        </script>

    </div>
</div>