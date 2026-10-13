<?php
/**
 * reports-sessions.php
 *
 * Sessions tab: completion vs abandoned, best study time, session history.
 * Expects: $sessionsReport (from ReportsService::getSessionsReport), $filters
 */
$e    = fn($v) => ReportsService::h($v);
$comp = $sessionsReport['completion'];
$best = $sessionsReport['bestTime'];
$hist = $sessionsReport['history'];
$ins  = $best['insights'];

$accuracyBadge = function (?float $acc): string {
    if ($acc === null) {
        return '<span class="text-on-surface-variant/60">—</span>';
    }
    $tone = $acc >= 80 ? 'bg-teal-50 text-teal-700 border-teal-200'
          : ($acc >= 50 ? 'bg-amber-50 text-amber-700 border-amber-200'
                        : 'bg-rose-50 text-rose-700 border-rose-200');
    $txt  = rtrim(rtrim(number_format($acc, 1), '0'), '.') . '%';

    return '<span class="inline-block px-2 py-0.5 rounded-full border text-xs font-bold ' . $tone . '">' . $txt . '</span>';
};

$sessionsPayload = [
    'completion' => $comp['started'] > 0 ? [
        'labels' => ['Completed', 'Abandoned'],
        'values' => [$comp['completed'], $comp['abandoned']],
        'colors' => ['#14b8a6', '#f43f5e'],
    ] : null,
    'hours' => $best['hasData'] ? $best['hours'] : null,
    'days'  => $best['hasData'] ? $best['days']  : null,
];

$insightRows = [
    ['Most active hour',   $ins['busiestHour']],
    ['Most accurate hour', $ins['accurateHour']],
    ['Most active day',    $ins['busiestDay']],
    ['Most accurate day',  $ins['accurateDay']],
];
?>

<?php if ($comp['started'] === 0 && $hist['total'] === 0): ?>
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-8 text-center text-sm text-on-surface-variant">
        No sessions in this period.
    </div>
<?php else: ?>
<div class="flex flex-col gap-4 sm:gap-6">

    <!-- COMPLETION + BEST-TIME HIGHLIGHTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">

        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">Session completion</h3>
            <p class="text-xs text-on-surface-variant mb-3">A session that was started but never finished counts as abandoned.</p>
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <div class="rp-chart-box relative shrink-0" style="height: 160px; width: 160px">
                    <canvas id="rp-chart-completion" aria-label="Completed versus abandoned sessions" role="img"></canvas>
                </div>
                <div class="flex-1 w-full">
                    <p class="text-3xl font-bold text-on-surface tabular-nums"><?= rtrim(rtrim(number_format($comp['rate'], 1), '0'), '.') ?>%</p>
                    <p class="text-xs text-on-surface-variant mb-3">completion rate</p>
                    <ul class="flex flex-col gap-1.5 text-sm">
                        <li class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-sm" style="background:#14b8a6"></span>
                            <span class="flex-1 text-on-surface">Completed</span>
                            <span class="tabular-nums font-semibold"><?= number_format($comp['completed']) ?></span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-sm" style="background:#f43f5e"></span>
                            <span class="flex-1 text-on-surface">Abandoned</span>
                            <span class="tabular-nums font-semibold"><?= number_format($comp['abandoned']) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">Best study time</h3>
            <p class="text-xs text-on-surface-variant mb-3">
                "Most accurate" needs at least <?= (int) $best['minSample'] ?> reviews in that hour or day.
            </p>
            <?php if ($best['hasData']): ?>
                <dl class="flex flex-col divide-y divide-outline-variant/20">
                    <?php foreach ($insightRows as [$label, $item]): ?>
                        <div class="flex items-center justify-between gap-3 py-2 text-sm">
                            <dt class="text-on-surface-variant"><?= $e($label) ?></dt>
                            <dd class="font-semibold text-on-surface text-right">
                                <?php if ($item): ?>
                                    <?= $e($item['label']) ?> <span class="text-xs font-normal text-on-surface-variant">(<?= $e($item['value']) ?>)</span>
                                <?php else: ?>
                                    <span class="font-normal text-on-surface-variant">Not enough data</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php else: ?>
                <div class="h-28 flex items-center justify-center text-sm text-on-surface-variant text-center">No review detail in this period.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- HOUR + WEEKDAY CHARTS -->
    <?php if ($best['hasData']): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">By hour of day</h3>
            <p class="text-xs text-on-surface-variant mb-3">Cards reviewed (bars) and accuracy (line).</p>
            <div class="rp-chart-box relative" style="height: 240px">
                <canvas id="rp-chart-hour" aria-label="Reviews and accuracy by hour of day" role="img"></canvas>
            </div>
        </div>
        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">By weekday</h3>
            <p class="text-xs text-on-surface-variant mb-3">Cards reviewed (bars) and accuracy (line).</p>
            <div class="rp-chart-box relative" style="height: 240px">
                <canvas id="rp-chart-weekday" aria-label="Reviews and accuracy by weekday" role="img"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SESSION HISTORY -->
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden">
        <div class="px-4 sm:px-5 pt-4 pb-3">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">Session history</h3>
            <p class="text-xs text-on-surface-variant">One row per session in the selected period, newest first.</p>
        </div>

        <?php if ($hist['rows']): ?>
            <div class="rp-table-wrap overflow-x-auto">
                <table class="rp-table w-full text-sm">
                    <thead>
                        <tr class="bg-surface-container-low text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                            <th class="text-left px-4 py-2.5">Date</th>
                            <th class="text-left px-3 py-2.5">Deck</th>
                            <th class="text-right px-3 py-2.5">Duration</th>
                            <th class="text-right px-3 py-2.5">Reviewed</th>
                            <th class="text-right px-3 py-2.5">Remembered</th>
                            <th class="text-right px-3 py-2.5">Accuracy</th>
                            <th class="text-left px-3 py-2.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php foreach ($hist['rows'] as $i => $s): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors <?= $i >= 25 ? 'rp-print-extra' : '' ?>">
                                <td class="px-4 py-2.5 whitespace-nowrap text-on-surface"><?= $e(date('M j, Y g:i A', strtotime($s['startedAt']))) ?></td>
                                <td class="px-3 py-2.5 font-semibold text-on-surface">
                                    <?= $e($s['deck']) ?><?php if ($s['archived']): ?> <span class="text-[10px] font-bold uppercase text-on-surface-variant/70">archived</span><?php endif; ?>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap"><?= $s['completed'] ? $e(ReportsService::formatDuration($s['seconds'])) : '—' ?></td>
                                <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($s['reviewed']) ?></td>
                                <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($s['remembered']) ?></td>
                                <td class="px-3 py-2.5 text-right"><?= $accuracyBadge($s['accuracy']) ?></td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <?php if ($s['completed']): ?>
                                        <span class="text-xs font-semibold text-teal-700">Completed</span>
                                    <?php else: ?>
                                        <span class="text-xs font-semibold text-rose-600">Abandoned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($hist['total'] > count($hist['rows'])): ?>
                <p class="rp-no-print px-4 sm:px-5 py-3 text-xs text-on-surface-variant">
                    Showing the latest <?= count($hist['rows']) ?> of <?= number_format($hist['total']) ?> sessions. The CSV export has the full list.
                </p>
            <?php endif; ?>
            <?php if ($hist['total'] > 25): ?>
                <p class="rp-print-only px-4 py-2 text-xs">
                    Showing the latest 25 of <?= number_format($hist['total']) ?> sessions. The CSV export has the full list.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <div class="h-28 flex items-center justify-center text-sm text-on-surface-variant text-center px-4">No sessions in this period.</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script type="application/json" id="rp-sessions-data"><?= json_encode($sessionsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>