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
                <!-- <p class="text-sm text-on-surface-variant mt-0.5">Your study activity, retention and card progress.</p> -->
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