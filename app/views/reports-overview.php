<?php
/**
 * reports-overview.php
 *
 * Overview tab: KPI cards, reviews/accuracy over time, review forecast.
 * Expects: $overview (from ReportsService::getOverview), $filters
 */
$k  = $overview['kpis'];
$tl = $overview['timeline'];
$fc = $overview['forecast'];
$st = $overview['streak'];

$hasActivity = array_sum($tl['reviewed']) > 0;
$hasForecast = $fc['next30'] > 0;
$avgSession  = $k['sessions'] > 0 ? (int) round($k['studySeconds'] / $k['sessions']) : 0;

$kpiCards = [
    [
        'label' => 'Total reviews',
        'value' => number_format($k['totalReviews']),
        'sub'   => number_format($k['remembered']) . ' remembered · ' . number_format($k['forgotten']) . ' forgotten',
        'icon'  => 'style',
        'tone'  => 'text-primary bg-primary/10 border-primary/20',
        'tip'   => 'Cards rated in completed sessions within the selected period.',
    ],
    [
        'label' => 'Accuracy',
        'value' => rtrim(rtrim(number_format($k['accuracy'], 1), '0'), '.') . '%',
        'sub'   => 'Remembered ÷ reviewed',
        'icon'  => 'target',
        'tone'  => 'text-teal-700 bg-teal-50 border-teal-200',
        'tip'   => 'Share of reviews rated "remembered".',
    ],
    [
        'label' => 'Study time',
        'value' => ReportsService::formatDuration($k['studySeconds']),
        'sub'   => $k['sessions'] > 0 ? 'Avg ' . ReportsService::formatDuration($avgSession) . ' per session' : 'No completed sessions',
        'icon'  => 'schedule',
        'tone'  => 'text-amber-700 bg-amber-50 border-amber-200',
        'tip'   => 'Time between session start and finish, completed sessions only.',
    ],
    [
        'label' => 'Sessions',
        'value' => number_format($k['sessions']),
        'sub'   => number_format($k['abandoned']) . ' abandoned · ' . rtrim(rtrim(number_format($k['completionRate'], 1), '0'), '.') . '% completed',
        'icon'  => 'event_repeat',
        'tone'  => 'text-violet-700 bg-violet-50 border-violet-200',
        'tip'   => 'Completed sessions. A session that was started but never finished counts as abandoned.',
    ],
    [
        'label' => 'Active days',
        'value' => number_format($k['activeDays']),
        'sub'   => 'of ' . number_format($k['rangeDays']) . ' days in period',
        'icon'  => 'calendar_month',
        'tone'  => 'text-sky-700 bg-sky-50 border-sky-200',
        'tip'   => 'Days with at least one reviewed card.',
    ],
    [
        'label' => 'Current streak',
        'value' => number_format($st['current']) . ($st['current'] === 1 ? ' day' : ' days'),
        'sub'   => 'Best: ' . number_format($st['best']) . ($st['best'] === 1 ? ' day' : ' days') . ' · all time',
        'icon'  => 'local_fire_department',
        'tone'  => 'text-rose-700 bg-rose-50 border-rose-200',
        'tip'   => 'All-time streak. Not affected by the filters above.',
    ],
];
?>

<div class="flex flex-col gap-4 sm:gap-6">

    <!-- KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4 rp-kpi-grid">
        <?php foreach ($kpiCards as $card): ?>
            <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 flex flex-col gap-3 shadow-xs" title="<?= ReportsService::h($card['tip']) ?>">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant"><?= ReportsService::h($card['label']) ?></span>
                    <span class="w-8 h-8 rounded-xl border flex items-center justify-center flex-shrink-0 <?= $card['tone'] ?>">
                        <span class="material-symbols-outlined text-[18px]"><?= $card['icon'] ?></span>
                    </span>
                </div>
                <div class="flex flex-col">
                    <span class="font-headline-lg text-2xl font-extrabold tracking-tight text-on-surface"><?= ReportsService::h($card['value']) ?></span>
                    <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5"><?= ReportsService::h($card['sub']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ACTIVITY + FORECAST -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6">

        <!-- Reviews & accuracy over time -->
        <div class="rp-avoid-break lg:col-span-7 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div>
                    <h3 class="font-headline-sm text-base font-bold text-on-surface">Reviews &amp; accuracy over time</h3>
                    <p class="text-xs text-on-surface-variant">Cards reviewed per <?= $tl['granularity'] === 'day' ? 'day' : 'week (week starting Monday)' ?>, with accuracy %</p>
                </div>
            </div>
            <?php if ($hasActivity): ?>
                <div class="rp-chart-box relative h-64 sm:h-72">
                    <canvas id="rp-chart-timeline" aria-label="Reviews and accuracy over time" role="img"></canvas>
                </div>
            <?php else: ?>
                <div class="h-40 flex items-center justify-center text-sm text-on-surface-variant text-center">
                    No completed sessions in this period. Try a longer range or a different deck.
                </div>
            <?php endif; ?>
        </div>

        <!-- Review forecast -->
        <div class="rp-avoid-break lg:col-span-5 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <div class="mb-3">
                <h3 class="font-headline-sm text-base font-bold text-on-surface">Review forecast</h3>
                <p class="text-xs text-on-surface-variant">Cards coming due. Not affected by the period filter.</p>
            </div>

            <div class="grid grid-cols-3 gap-2 mb-4">
                <?php foreach ([['Due now', $fc['dueNow']], ['Next 7 days', $fc['next7']], ['Next 30 days', $fc['next30']]] as [$lbl, $num]): ?>
                    <div class="rounded-xl bg-surface-container-low px-3 py-2">
                        <div class="text-lg font-extrabold text-on-surface leading-tight"><?= number_format($num) ?></div>
                        <div class="text-[11px] font-medium text-on-surface-variant"><?= ReportsService::h($lbl) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($hasForecast): ?>
                <div class="rp-chart-box relative h-48 sm:h-52">
                    <canvas id="rp-chart-forecast" aria-label="Cards due over the next 7 days" role="img"></canvas>
                </div>
            <?php else: ?>
                <div class="h-32 flex items-center justify-center text-sm text-on-surface-variant text-center">
                    Nothing is scheduled in the next 30 days.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Chart data for reports-overview.js (HEX flags make the JSON safe inside <script>).
$chartPayload = [
    'timeline'    => $hasActivity ? $tl : null,
    'forecast'    => $hasForecast ? ['labels' => $fc['labels'], 'values' => $fc['values']] : null,
];
?>
<script type="application/json" id="rp-overview-data"><?= json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>