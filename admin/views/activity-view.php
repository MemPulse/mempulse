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
        <section aria-label="Activity summary metrics" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Sessions (30d)</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['sessions30d']) ?></p>
                    <span class="text-[11px] text-on-surface-variant"><?= $fmt($s['sessionsToday']) ?> completed today</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">history_edu</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Reviews (30d)</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($s['reviews30d']) ?></p>
                    <span class="text-[11px] text-on-surface-variant"><?= $fmt($s['activeToday']) ?> active today · <?= $fmt($s['active7d']) ?> in 7d</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200/80 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">bolt</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700">30d Retention Rate</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= number_format($s['retention30d'], 1) ?>%</p>
                    <span class="text-[11px] text-on-surface-variant">Platform recall accuracy</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-200/80 text-teal-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">verified</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Avg Recall Speed</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= number_format($s['avgResponseSec'], 2) ?>s</p>
                    <span class="text-[11px] text-on-surface-variant">Per flashcard (30d)</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">timer</span>
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
        <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden min-w-0" aria-label="Study session log">

            <!-- State Tabs -->
            <nav class="flex gap-1 overflow-x-auto px-3 pt-3 border-b border-outline-variant/30">
                <?php foreach ($tabs as $key => $label):
                    $on = $f['state'] === $key; ?>
                    <a href="activity.php<?= adminEsc(AdminActivityService::buildQuery($f, ['state' => $key, 'page' => 1])) ?>"
                       class="flex items-center gap-2 px-3 py-2 text-sm font-semibold whitespace-nowrap border-b-2 -mb-px transition-colors <?= $on ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
                        <?= adminEsc($label) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Search & Date Filter -->
            <form method="get" action="activity.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 p-4 border-b border-outline-variant/30">
                <input type="hidden" name="state" value="<?= adminEsc($f['state']) ?>">
                <input type="hidden" name="sort" value="<?= adminEsc($f['sort']) ?>">
                <input type="hidden" name="dir" value="<?= adminEsc($f['dir']) ?>">

                <div class="lg:col-span-6">
                    <input type="search" name="q" value="<?= adminEsc($f['q']) ?>" maxlength="100" placeholder="Search learner by name or email…" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2">
                    <input type="date" name="from" value="<?= adminEsc($f['from']) ?>" title="Started from" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2">
                    <input type="date" name="to" value="<?= adminEsc($f['to']) ?>" title="Started to" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:opacity-90 transition-opacity">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="activity.php" class="px-3 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface text-sm font-semibold transition-colors">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($list['rows']): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/30 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <?php
                                $headers = [
                                    'started'  => ['label' => 'Started',        'class' => 'py-3 pl-4 pr-3'],
                                    'learner'  => ['label' => 'Learner',        'class' => 'py-3 px-3'],
                                    'reviewed' => ['label' => 'Cards Reviewed', 'class' => 'py-3 px-3'],
                                    'accuracy' => ['label' => 'Retention',      'class' => 'py-3 px-3'],
                                    'duration' => ['label' => 'Duration',       'class' => 'py-3 pl-3 pr-4 text-right'],
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
                                    <td class="py-3 pl-4 pr-3 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-semibold text-on-surface tabular-nums"><?= adminEsc($r['started']) ?></span>
                                            <span class="text-[11px] text-on-surface-variant"><?= adminEsc($r['age']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex flex-col min-w-0">
                                            <a href="users.php?q=<?= urlencode($r['email']) ?>" class="text-xs font-bold text-on-surface hover:text-primary hover:underline truncate">
                                                <?= adminEsc($r['name']) ?>
                                            </a>
                                            <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($r['email']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 tabular-nums text-xs">
                                        <span class="font-bold text-on-surface"><?= $fmt($r['reviewed']) ?> cards</span>
                                        <span class="text-[11px] text-on-surface-variant block">
                                            <?= $fmt($r['remembered']) ?> remembered · <?= $fmt($r['forgot']) ?> forgot
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 tabular-nums">
                                        <?php if ($r['completed']): ?>
                                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-md border <?= $accCls ?>">
                                                <?= number_format($r['accuracy'], 1) ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200/80 text-amber-700">Active now</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 pl-3 pr-4 text-right tabular-nums text-xs text-on-surface-variant whitespace-nowrap">
                                        <?= adminEsc($r['duration']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-outline-variant/30" aria-label="Pagination">
                    <p class="text-xs text-on-surface-variant">
                        Showing <?= $fmt($list['first']) ?>&ndash;<?= $fmt($list['last']) ?> of <?= $fmt($list['total']) ?> sessions
                    </p>
                    <?php if ($list['pages'] > 1): ?>
                        <div class="flex items-center gap-1">
                            <?php for ($p = $firstShown; $p <= $lastShown; $p++): ?>
                                <?php if ($p === $list['page']): ?>
                                    <span aria-current="page" class="<?= $pageBtn ?>bg-primary text-on-primary"><?= $p ?></span>
                                <?php else: ?>
                                    <a href="activity.php<?= adminEsc(AdminActivityService::buildQuery($f, ['page' => $p])) ?>" class="<?= $pageBtn ?>text-on-surface-variant hover:bg-surface-container-high hover:text-primary"><?= $p ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
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

    </div>
</div>