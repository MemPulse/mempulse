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
        'bg'    => 'bg-gradient-to-br from-indigo-500/10 via-surface-container-lowest to-surface-container-lowest',
        'border'=> 'border-outline-variant/30 hover:border-indigo-500/50',
        'decor' => 'bg-indigo-500/10 group-hover:bg-indigo-500/20',
        'labelColor' => 'text-indigo-700',
        'iconBg' => 'bg-indigo-50 border border-indigo-200/80 text-indigo-600',
        'tip'   => 'Cards rated in completed sessions within the selected period.',
    ],
    [
        'label' => 'Accuracy',
        'value' => rtrim(rtrim(number_format($k['accuracy'], 1), '0'), '.') . '%',
        'sub'   => 'Remembered ÷ reviewed',
        'icon'  => 'target',
        'bg'    => 'bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest',
        'border'=> 'border-outline-variant/30 hover:border-teal-500/50',
        'decor' => 'bg-teal-500/10 group-hover:bg-teal-500/20',
        'labelColor' => 'text-teal-700',
        'iconBg' => 'bg-teal-50 border border-teal-200/80 text-teal-600',
        'tip'   => 'Share of reviews rated "remembered".',
    ],
    [
        'label' => 'Study time',
        'value' => ReportsService::formatDuration($k['studySeconds']),
        'sub'   => $k['sessions'] > 0 ? 'Avg ' . ReportsService::formatDuration($avgSession) . ' per session' : 'No completed sessions',
        'icon'  => 'schedule',
        'bg'    => 'bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest',
        'border'=> 'border-outline-variant/30 hover:border-error/50',
        'decor' => 'bg-rose-500/10 group-hover:bg-rose-500/20',
        'labelColor' => 'text-error',
        'iconBg' => 'bg-red-50 border border-red-200/80 text-error',
        'tip'   => 'Time between session start and finish, completed sessions only.',
    ],
    [
        'label' => 'Sessions',
        'value' => number_format($k['sessions']),
        'sub'   => number_format($k['abandoned']) . ' abandoned · ' . rtrim(rtrim(number_format($k['completionRate'], 1), '0'), '.') . '% completed',
        'icon'  => 'event_repeat',
        'bg'    => 'bg-gradient-to-br from-indigo-500/10 via-surface-container-lowest to-surface-container-lowest',
        'border'=> 'border-outline-variant/30 hover:border-indigo-500/50',
        'decor' => 'bg-indigo-500/10 group-hover:bg-indigo-500/20',
        'labelColor' => 'text-indigo-700',
        'iconBg' => 'bg-indigo-50 border border-indigo-200/80 text-indigo-600',
        'tip'   => 'Completed sessions. A session that was started but never finished counts as abandoned.',
    ],
    [
        'label' => 'Active days',
        'value' => number_format($k['activeDays']),
        'sub'   => 'of ' . number_format($k['rangeDays']) . ' days in period',
        'icon'  => 'calendar_month',
        'bg'    => 'bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest',
        'border'=> 'border-outline-variant/30 hover:border-teal-500/50',
        'decor' => 'bg-teal-500/10 group-hover:bg-teal-500/20',
        'labelColor' => 'text-teal-700',
        'iconBg' => 'bg-teal-50 border border-teal-200/80 text-teal-600',
        'tip'   => 'Days with at least one reviewed card.',
    ],
    [
        'label' => 'Current streak',
        'value' => number_format($st['current']) . ($st['current'] === 1 ? ' day' : ' days'),
        'sub'   => 'Best: ' . number_format($st['best']) . ($st['best'] === 1 ? ' day' : ' days') . ' · all time',
        'icon'  => 'local_fire_department',
        'bg'    => 'bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest',
        'border'=> 'border-outline-variant/30 hover:border-error/50',
        'decor' => 'bg-rose-500/10 group-hover:bg-rose-500/20',
        'labelColor' => 'text-error',
        'iconBg' => 'bg-red-50 border border-red-200/80 text-error',
        'tip'   => 'All-time streak. Not affected by the filters above.',
    ],
];
?>

<div class="flex flex-col gap-4 sm:gap-6">

    <!-- KPI CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 sm:gap-4 rp-kpi-grid">
        <?php foreach ($kpiCards as $card): ?>
            <div class="rp-avoid-break <?= $card['bg'] ?> border <?= $card['border'] ?> rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:shadow-md transition-all" title="<?= ReportsService::h($card['tip']) ?>">
                <div class="absolute right-0 bottom-0 w-16 h-16 <?= $card['decor'] ?> rounded-br-2xl rounded-tl-full blur-md pointer-events-none transition-all"></div>
                <div class="flex items-center justify-between w-full relative z-10">
                    <div class="flex items-center gap-1.5">
                        <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider <?= $card['labelColor'] ?>"><?= ReportsService::h($card['label']) ?></span>
                    </div>
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 shadow-xs <?= $card['iconBg'] ?>">
                        <span class="material-symbols-outlined text-[18px]"><?= $card['icon'] ?></span>
                    </div>
                </div>
                <div class="flex flex-col mt-2 relative z-10">
                    <span class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface"><?= ReportsService::h($card['value']) ?></span>
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