<?php
// Expects (from content.php): $filters, $summary, $list.
$f   = $filters;
$s   = $summary;
$fmt = fn($n) => number_format((int) $n);
$hasFilters = $f['q'] !== '' || $f['filter'] !== '';

$totalCards = max(1, $s['activeCards']);
$tiers = [
    ['label' => 'New (Unreviewed)',    'count' => $s['newCards'],      'pct' => round(($s['newCards'] / $totalCards) * 100),      'bar' => 'bg-slate-400',   'badge' => 'bg-surface-container-low text-on-surface-variant'],
    ['label' => 'Learning (1–3 days)', 'count' => $s['learningCards'], 'pct' => round(($s['learningCards'] / $totalCards) * 100), 'bar' => 'bg-amber-500',   'badge' => 'bg-amber-50 text-amber-700'],
    ['label' => 'Young (4–20 days)',   'count' => $s['youngCards'],    'pct' => round(($s['youngCards'] / $totalCards) * 100),    'bar' => 'bg-primary',     'badge' => 'bg-primary/10 text-primary'],
    ['label' => 'Mature (21+ days)',   'count' => $s['matureCards'],   'pct' => round(($s['matureCards'] / $totalCards) * 100),   'bar' => 'bg-teal-500',    'badge' => 'bg-teal-50 text-teal-700'],
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

        <!-- 4 KPI CARDS -->
        <section aria-label="Content summary metrics" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Active Decks</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['activeDecks']) ?></p>
                    <span class="text-[11px] text-on-surface-variant"><?= $fmt($s['archivedDecks']) ?> archived</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">style</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Active Flashcards</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['activeCards']) ?></p>
                    <span class="text-[11px] text-indigo-700 font-medium">+<?= $fmt($s['created7d']) ?> added this week</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200/80 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">layers</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Cards Due Now</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['dueNow']) ?></p>
                    <span class="text-[11px] text-on-surface-variant">Ready for review across decks</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">schedule</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700">Avg SRS Interval</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= number_format($s['avgInterval'], 1) ?>d</p>
                    <span class="text-[11px] text-on-surface-variant"><?= $fmt($s['totalLapses']) ?> total lapses</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-200/80 text-teal-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">timeline</span>
                </div>
            </div>
        </section>

        <!-- SRS CARD MATURITY DISTRIBUTION BAR -->
        <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <div>
                    <h2 class="font-headline-sm text-base font-bold text-on-surface m-0">Spaced-Repetition Maturity Breakdown</h2>
                    <p class="text-xs text-on-surface-variant mt-0.5">Distribution of active flashcards by current memory interval</p>
                </div>
                <span class="text-xs font-bold text-on-surface-variant tabular-nums"><?= $fmt($s['activeCards']) ?> total active cards</span>
            </div>

            <!-- Stacked Bar -->
            <div class="w-full h-3 rounded-full bg-surface-container-low overflow-hidden flex mb-4">
                <?php foreach ($tiers as $t): if ($t['pct'] > 0): ?>
                    <div class="<?= $t['bar'] ?> h-full transition-all" style="width: <?= (int) $t['pct'] ?>%" title="<?= adminEsc($t['label']) ?>: <?= $fmt($t['count']) ?> (<?= (int) $t['pct'] ?>%)"></div>
                <?php endif; endforeach; ?>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <?php foreach ($tiers as $t): ?>
                    <div class="p-3 rounded-xl bg-surface-container-low border border-outline-variant/20 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-on-surface block"><?= adminEsc($t['label']) ?></span>
                            <span class="text-sm font-extrabold text-on-surface tabular-nums"><?= $fmt($t['count']) ?></span>
                        </div>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-md <?= $t['badge'] ?> tabular-nums"><?= (int) $t['pct'] ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- PER-USER CONTENT INVENTORY TABLE -->
        <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden min-w-0" aria-label="Per-user content footprint">

            <!-- Filter Tabs -->
            <nav class="flex gap-1 overflow-x-auto px-3 pt-3 border-b border-outline-variant/30">
                <?php foreach ($tabs as $key => $label):
                    $on = $f['filter'] === $key; ?>
                    <a href="content.php<?= adminEsc(AdminContentService::buildQuery($f, ['filter' => $key, 'page' => 1])) ?>"
                       class="flex items-center gap-2 px-3 py-2 text-sm font-semibold whitespace-nowrap border-b-2 -mb-px transition-colors <?= $on ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
                        <?= adminEsc($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Search Bar -->
            <form method="get" action="content.php" class="flex flex-wrap items-center gap-3 p-4 border-b border-outline-variant/30">
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
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/30 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <?php
                                $headers = [
                                    'name'     => ['label' => 'Learner',            'class' => 'py-3 pl-4 pr-3'],
                                    'decks'    => ['label' => 'Active Decks',       'class' => 'py-3 px-3'],
                                    'cards'    => ['label' => 'Active Cards',       'class' => 'py-3 px-3'],
                                    'due'      => ['label' => 'Due Now',            'class' => 'py-3 px-3'],
                                    'mature'   => ['label' => 'Mature (21d+)',      'class' => 'py-3 px-3 hidden md:table-cell'],
                                    'interval' => ['label' => 'Avg Interval',       'class' => 'py-3 px-3 hidden lg:table-cell'],
                                    'lapses'   => ['label' => 'Total Lapses',       'class' => 'py-3 pl-3 pr-4 text-right'],
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
                                    <td class="py-3 pl-4 pr-3">
                                        <div class="flex flex-col min-w-0">
                                            <a href="users.php?q=<?= urlencode($r['email']) ?>" class="text-xs font-bold text-on-surface hover:text-primary hover:underline truncate">
                                                <?= adminEsc($r['name']) ?>
                                            </a>
                                            <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($r['email']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 tabular-nums text-xs">
                                        <span class="font-bold text-on-surface"><?= $fmt($r['activeDecks']) ?></span>
                                        <?php if ($r['archivedDecks'] > 0): ?>
                                            <span class="text-[11px] text-on-surface-variant ml-1">(+<?= $fmt($r['archivedDecks']) ?> arch)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 tabular-nums text-xs font-bold text-on-surface">
                                        <?= $fmt($r['activeCards']) ?>
                                    </td>
                                    <td class="py-3 px-3 tabular-nums text-xs">
                                        <?php if ($r['dueCards'] > 0): ?>
                                            <span class="px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200/80 text-amber-700 font-bold"><?= $fmt($r['dueCards']) ?> due</span>
                                        <?php else: ?>
                                            <span class="text-on-surface-variant">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3 hidden md:table-cell tabular-nums text-xs">
                                        <span class="text-teal-700 font-semibold"><?= $fmt($r['matureCards']) ?></span>
                                    </td>
                                    <td class="py-3 px-3 hidden lg:table-cell tabular-nums text-xs text-on-surface-variant">
                                        <?= number_format($r['avgInterval'], 1) ?> days
                                    </td>
                                    <td class="py-3 pl-3 pr-4 text-right tabular-nums text-xs font-semibold <?= $r['totalLapses'] >= 10 ? 'text-error' : 'text-on-surface-variant' ?>">
                                        <?= $fmt($r['totalLapses']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-outline-variant/30" aria-label="Pagination">
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

    </div>
</div>