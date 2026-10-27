<?php
// Expects (from activity.php): $filters, $summary, $list.
$f   = $filters;
$s   = $summary;
$fmt = fn($n) => number_format((int) $n);
$hasFilters = $f['q'] !== '' || $f['state'] !== '' || $f['from'] !== '' || $f['to'] !== '';

$tabs = [
    ''            => 'All Sessions',
    'completed'   => 'Completed',
    'in_progress' => 'In Progress',
    'high_acc'    => 'High Retention (85%+)',
    'low_acc'     => 'Needs Practice (<60%)',
];

$sortUrls = [];
foreach (['started', 'learner', 'reviewed', 'accuracy', 'duration'] as $key) {
    $nextDir = ($f['sort'] === $key && $f['dir'] === 'asc') ? 'desc' : 'asc';
    $sortUrls[$key] = 'activity.php' . AdminActivityService::buildQuery($f, ['sort' => $key, 'dir' => $nextDir, 'page' => 1]);
}

$exportUrl  = 'activity.php' . AdminActivityService::buildQuery($f, ['export' => 'csv']);
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
                    <span class="material-symbols-outlined text-[28px] text-primary" aria-hidden="true">monitoring</span>
                    <h1 class="font-headline-md text-xl sm:text-2xl font-bold text-on-surface m-0">Platform Study Activity</h1>
                </div>
                <p class="text-xs sm:text-sm text-on-surface-variant">
                    Monitor platform-wide study sessions, 30-day memory retention accuracy, and learner recall speed.
                </p>
            </div>
            <a href="<?= adminEsc($exportUrl) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/30 text-on-surface text-xs font-semibold transition-colors">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Export CSV</span>
            </a>
        </div>

        <!-- 4 KPI CARDS -->
        <section aria-label="Activity summary metrics" class="grid grid-cols-4 gap-2 sm:gap-3 lg:gap-4" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr))">

            <!-- Sessions (30d) (primary) -->
            <div class="min-w-0 bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-primary/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-primary">Sessions (30d)</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['sessions30d']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant"><?= $fmt($s['sessionsToday']) ?> completed today</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-primary/10 group-hover:bg-primary/20 text-primary flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">history_edu</span>
                </div>
            </div>

            <!-- Reviews (30d) (rose) -->
            <div class="min-w-0 bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-rose-500/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-rose-700">Reviews (30d)</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['reviews30d']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant"><?= $fmt($s['activeToday']) ?> active today · <?= $fmt($s['active7d']) ?> in 7d</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-rose-500/10 group-hover:bg-rose-500/20 text-rose-700 flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">bolt</span>
                </div>
            </div>

            <!-- 30d Retention Rate (teal) -->
            <div class="min-w-0 bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-teal-500/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-teal-700">30d Retention Rate</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= number_format($s['retention30d'], 1) ?>%</p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant">Platform recall accuracy</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-teal-500/10 group-hover:bg-teal-500/20 text-teal-600 flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">verified</span>
                </div>
            </div>

            <!-- Avg Recall Speed (primary) -->
            <div class="min-w-0 bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-primary/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-primary">Avg Recall Speed</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= number_format($s['avgResponseSec'], 2) ?>s</p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant">Per flashcard (30d)</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-primary/10 group-hover:bg-primary/20 text-primary flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">timer</span>
                </div>
            </div>

        </section>

        <!-- INTERACTIVE STUDY & RETENTION TREND CHART -->
        <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-h-[320px]" aria-labelledby="activity-chart-title">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">insights</span>
                        <h2 id="activity-chart-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Daily Recall &amp; Retention Trend</h2>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Remembered vs. forgotten reviews and daily recall accuracy</p>
                </div>
                <div class="inline-flex p-1 rounded-xl bg-surface-container-low border border-outline-variant/30" role="group" aria-label="Chart range">
                    <?php foreach ([7, 30, 90] as $r): ?>
                        <button type="button" data-range="<?= $r ?>" aria-pressed="false"
                            class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><?= $r ?>d</button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 mb-3" role="group" aria-label="Toggle chart series">
                <span class="text-xs font-semibold text-on-surface-variant mr-1">Show:</span>
                <button type="button" data-series="remembered" aria-pressed="true" class="inline-flex items-center gap-1.5 rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1.5 text-xs font-semibold text-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><span class="w-2 h-2 rounded-sm bg-teal-500" aria-hidden="true"></span>Remembered</button>
                <button type="button" data-series="forgot" aria-pressed="true" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><span class="w-2 h-2 rounded-sm bg-amber-500" aria-hidden="true"></span>Forgot</button>
                <button type="button" data-series="retention" aria-pressed="true" class="inline-flex items-center gap-1.5 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-xs font-semibold text-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><span class="w-2 h-2 rounded-full bg-sky-500" aria-hidden="true"></span>Retention %</button>
            </div>

            <div class="relative w-full flex-1 min-h-[220px] min-w-0">
                <div id="activity-chart-loading" class="absolute inset-0 flex items-center justify-center text-sm text-on-surface-variant">Loading chart…</div>
                <div id="activity-chart-status" class="hidden absolute inset-0 flex-col items-center justify-center text-center text-on-surface-variant gap-1 p-6 z-10">
                    <span class="material-symbols-outlined text-[32px] text-outline" aria-hidden="true">insert_chart</span>
                    <span id="activity-chart-status-title" class="text-sm font-medium"></span>
                    <span id="activity-chart-status-text" class="text-xs text-on-surface-variant/70"></span>
                </div>
                <canvas id="activity-chart" role="img" aria-label="Daily remembered cards, forgotten cards, and retention percentage"></canvas>
            </div>
        </section>
        
        <!-- SESSIONS TABLE -->
        <style>
            /* ---------- Filter bar ---------- */
            #activity-sessions .act-filter { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px 16px; padding: 16px; }
            #activity-sessions .act-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
            #activity-sessions .act-field.grow { flex: 1 1 260px; }
            #activity-sessions .act-field.date { flex: 0 0 190px; }
            #activity-sessions .act-actions { display: flex; align-items: center; gap: 8px; flex: none; }

            /* ---------- Compact, fixed-width table ---------- */
            #activity-sessions .act-table { table-layout: fixed; width: 100%; min-width: 760px; border-collapse: collapse; }
            #activity-sessions .act-table th,
            #activity-sessions .act-table td {
                padding: 6px 12px;
                vertical-align: middle;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            #activity-sessions .act-table th { padding-top: 8px; padding-bottom: 8px; }
            #activity-sessions .act-table .c-started,
            #activity-sessions .act-table th.c-started { padding-left: 16px; }
            #activity-sessions .act-table .c-duration { padding-right: 16px; text-align: right; }

            /* Column widths (Learner takes the remaining space) */
            #activity-sessions col.c-started  { width: 220px; }
            #activity-sessions col.c-reviewed { width: 290px; }
            #activity-sessions col.c-retention { width: 120px; }
            #activity-sessions col.c-duration { width: 100px; }

            /* Single-line learner cell: name + email */
            #activity-sessions .learner-cell { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
            #activity-sessions .learner-name { flex: 0 1 auto; max-width: 55%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            #activity-sessions .learner-email { flex: 1 1 0; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

            /* Loading state while the table refreshes */
            #activity-sessions .act-table-wrap { transition: opacity .15s; }
            #activity-sessions.is-loading .act-table-wrap { opacity: .5; pointer-events: none; }
        </style>

        <section id="activity-sessions" class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden min-w-0" aria-label="Study session log">

            <!-- State Tabs -->
            <nav data-tabs class="flex gap-1 overflow-x-auto px-3 pt-3 border-b border-outline-variant/30">
                <?php foreach ($tabs as $key => $label):
                    $on = $f['state'] === $key; ?>
                    <a href="activity.php<?= adminEsc(AdminActivityService::buildQuery($f, ['state' => $key, 'page' => 1])) ?>"
                    class="flex items-center gap-2 px-3 py-2 text-sm font-semibold whitespace-nowrap border-b-2 -mb-px transition-colors <?= $on ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
                        <?= adminEsc($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Search & Date Filter -->
            <form data-ajax method="get" action="activity.php" class="act-filter border-b border-outline-variant/30">
                <input type="hidden" name="state" value="<?= adminEsc($f['state']) ?>">
                <input type="hidden" name="sort" value="<?= adminEsc($f['sort']) ?>">
                <input type="hidden" name="dir" value="<?= adminEsc($f['dir']) ?>">

                <div class="act-field grow">
                    <label for="act-q" class="text-xs font-bold text-on-surface-variant">Search</label>
                    <input id="act-q" type="search" name="q" value="<?= adminEsc($f['q']) ?>" maxlength="100" placeholder="Learner name or email…" class="<?= $input ?> min-w-0">
                </div>
                <div class="act-field date">
                    <label for="act-from" class="text-xs font-bold text-on-surface-variant">Started From</label>
                    <input id="act-from" type="date" name="from" value="<?= adminEsc($f['from']) ?>" class="<?= $input ?> min-w-0">
                </div>
                <div class="act-field date">
                    <label for="act-to" class="text-xs font-bold text-on-surface-variant">Started To</label>
                    <input id="act-to" type="date" name="to" value="<?= adminEsc($f['to']) ?>" class="<?= $input ?> min-w-0">
                </div>
                <div class="act-actions">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:opacity-90 transition-opacity h-[38px]">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="activity.php" class="px-4 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface text-sm font-semibold transition-colors flex items-center justify-center h-[38px]">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($list['rows']): ?>
                <div class="overflow-x-auto act-table-wrap">
                    <table class="act-table text-left">
                        <colgroup>
                            <col class="c-started">
                            <col>
                            <col class="c-reviewed">
                            <col class="c-retention">
                            <col class="c-duration">
                        </colgroup>
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/30 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <?php
                                $headers = [
                                    'started'  => ['label' => 'Started',        'class' => 'c-started'],
                                    'learner'  => ['label' => 'Learner',        'class' => ''],
                                    'reviewed' => ['label' => 'Cards Reviewed', 'class' => 'c-reviewed'],
                                    'accuracy' => ['label' => 'Retention',      'class' => 'c-retention'],
                                    'duration' => ['label' => 'Duration',       'class' => 'c-duration'],
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
                            <?php foreach ($list['rows'] as $r):
                                $accCls = $r['accuracy'] >= 80
                                    ? 'bg-teal-50 text-teal-700 border-teal-200/80'
                                    : ($r['accuracy'] >= 60 ? 'bg-amber-50 text-amber-700 border-amber-200/80' : 'bg-error-container/40 text-error border-error/20');
                            ?>
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="c-started tabular-nums">
                                        <span class="text-xs font-semibold text-on-surface"><?= adminEsc($r['started']) ?></span>
                                        <span class="text-[11px] text-on-surface-variant ml-1.5"><?= adminEsc($r['age']) ?></span>
                                    </td>
                                    <td>
                                        <div class="learner-cell">
                                            <a href="users.php?q=<?= urlencode($r['email']) ?>"
                                            class="learner-name text-xs font-bold text-on-surface hover:text-primary hover:underline"
                                            title="<?= adminEsc($r['name']) ?>"><?= adminEsc($r['name']) ?></a>
                                            <span class="learner-email text-[11px] text-on-surface-variant"
                                                title="<?= adminEsc($r['email']) ?>"><?= adminEsc($r['email']) ?></span>
                                        </div>
                                    </td>
                                    <td class="c-reviewed tabular-nums text-xs">
                                        <span class="font-bold text-on-surface"><?= $fmt($r['reviewed']) ?> cards</span>
                                        <span class="text-[11px] text-on-surface-variant ml-1.5"><?= $fmt($r['remembered']) ?> remembered · <?= $fmt($r['forgot']) ?> forgot</span>
                                    </td>
                                    <td class="c-retention tabular-nums">
                                        <?php if ($r['completed']): ?>
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-md border <?= $accCls ?>">
                                                <?= number_format($r['accuracy'], 1) ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200/80 text-amber-700">Active now</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="c-duration tabular-nums text-xs text-on-surface-variant">
                                        <?= adminEsc($r['duration']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php
                $curPage  = (int) $list['page'];
                $lastPage = (int) $list['pages'];
                $pgUrl    = fn($p) => 'activity.php' . AdminActivityService::buildQuery($f, ['page' => $p]);

                // 1 … window around current … last
                $pageItems = [];
                $prevShown = 0;
                for ($p = 1; $p <= $lastPage; $p++) {
                    if ($p === 1 || $p === $lastPage || abs($p - $curPage) <= 2) {
                        if ($prevShown && $p - $prevShown > 1) $pageItems[] = '…';
                        $pageItems[] = $p;
                        $prevShown = $p;
                    }
                }
                $navBtn      = 'w-8 h-8 rounded-lg flex items-center justify-center transition-colors ';
                $navActive   = 'text-on-surface-variant hover:bg-surface-container-high hover:text-primary';
                $navDisabled = 'text-on-surface-variant/30 pointer-events-none';
                ?>
                <nav class="flex flex-wrap items-center justify-between gap-3 px-5 py-2.5 border-t border-outline-variant/30" aria-label="Pagination">
                    <p class="text-xs text-on-surface-variant">
                        Showing <?= $fmt($list['first']) ?>&ndash;<?= $fmt($list['last']) ?> of <?= $fmt($list['total']) ?> sessions
                    </p>

                    <?php if ($lastPage > 1): ?>
                        <div class="flex flex-wrap items-center gap-3">

                            <!-- Page buttons -->
                            <div class="flex items-center gap-1">
                                <?php if ($curPage > 1): ?>
                                    <a href="<?= adminEsc($pgUrl(1)) ?>" aria-label="First page" title="First page" class="<?= $navBtn . $navActive ?>"><span class="material-symbols-outlined text-[20px]">first_page</span></a>
                                    <a href="<?= adminEsc($pgUrl($curPage - 1)) ?>" aria-label="Previous page" title="Previous page" class="<?= $navBtn . $navActive ?>"><span class="material-symbols-outlined text-[20px]">chevron_left</span></a>
                                <?php else: ?>
                                    <span aria-hidden="true" class="<?= $navBtn . $navDisabled ?>"><span class="material-symbols-outlined text-[20px]">first_page</span></span>
                                    <span aria-hidden="true" class="<?= $navBtn . $navDisabled ?>"><span class="material-symbols-outlined text-[20px]">chevron_left</span></span>
                                <?php endif; ?>

                                <?php foreach ($pageItems as $item): ?>
                                    <?php if ($item === '…'): ?>
                                        <span class="min-w-6 h-8 flex items-center justify-center text-xs text-on-surface-variant">…</span>
                                    <?php elseif ($item === $curPage): ?>
                                        <span aria-current="page" class="<?= $pageBtn ?>bg-primary text-on-primary"><?= $item ?></span>
                                    <?php else: ?>
                                        <a href="<?= adminEsc($pgUrl($item)) ?>" class="<?= $pageBtn ?>text-on-surface-variant hover:bg-surface-container-high hover:text-primary"><?= $item ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <?php if ($curPage < $lastPage): ?>
                                    <a href="<?= adminEsc($pgUrl($curPage + 1)) ?>" aria-label="Next page" title="Next page" class="<?= $navBtn . $navActive ?>"><span class="material-symbols-outlined text-[20px]">chevron_right</span></a>
                                    <a href="<?= adminEsc($pgUrl($lastPage)) ?>" aria-label="Last page" title="Last page" class="<?= $navBtn . $navActive ?>"><span class="material-symbols-outlined text-[20px]">last_page</span></a>
                                <?php else: ?>
                                    <span aria-hidden="true" class="<?= $navBtn . $navDisabled ?>"><span class="material-symbols-outlined text-[20px]">chevron_right</span></span>
                                    <span aria-hidden="true" class="<?= $navBtn . $navDisabled ?>"><span class="material-symbols-outlined text-[20px]">last_page</span></span>
                                <?php endif; ?>
                            </div>

                            <!-- Jump to page -->
                            <form data-ajax method="get" action="activity.php" class="flex items-center gap-2">
                                <input type="hidden" name="state" value="<?= adminEsc($f['state']) ?>">
                                <input type="hidden" name="sort"  value="<?= adminEsc($f['sort']) ?>">
                                <input type="hidden" name="dir"   value="<?= adminEsc($f['dir']) ?>">
                                <input type="hidden" name="q"     value="<?= adminEsc($f['q']) ?>">
                                <input type="hidden" name="from"  value="<?= adminEsc($f['from']) ?>">
                                <input type="hidden" name="to"    value="<?= adminEsc($f['to']) ?>">

                                <label for="jump-page" class="text-xs text-on-surface-variant whitespace-nowrap">Go to</label>
                                <input id="jump-page" type="number" name="page" min="1" max="<?= $lastPage ?>" value="<?= $curPage ?>" inputmode="numeric" required
                                    class="<?= $input ?> tabular-nums text-center" style="width:72px;height:32px;padding:0 8px;">
                                <span class="text-xs text-on-surface-variant whitespace-nowrap">of <?= $fmt($lastPage) ?></span>
                                <button type="submit" class="px-3 h-8 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors">Go</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </nav>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center p-12 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[36px] text-outline mb-2">monitoring</span>
                    <span class="text-sm font-semibold text-on-surface">No study sessions match these filters.</span>
                </div>
            <?php endif; ?>
        </section>

        <script>
        (function () {
            var region = document.getElementById('activity-sessions');
            if (!region) return;

            var controller = null;
            history.replaceState({ actAjax: true }, '', location.href);

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
                        var fresh = doc.getElementById('activity-sessions');
                        if (!fresh) throw new Error('Section not found');

                        var nav = region.querySelector('[data-tabs]');
                        var tabScroll = nav ? nav.scrollLeft : 0;

                        region.innerHTML = fresh.innerHTML;

                        var newNav = region.querySelector('[data-tabs]');
                        if (newNav) newNav.scrollLeft = tabScroll;

                        if (push) history.pushState({ actAjax: true }, '', url);
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
                if (url.origin !== location.origin || !/activity\.php$/.test(url.pathname)) return; // skip users.php links

                e.preventDefault();
                load(url.href, true);
            });

            // Filter form
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
                if (e.state && e.state.actAjax) load(location.href, false);
            });
        })();
        </script>

    </div>
</div>