<?php
/**
 * reports-cards.php
 *
 * Cards tab: maturity breakdown, problem cards, interval growth, response times.
 * Expects: $cardsReport (from ReportsService::getCardsReport), $filters
 */
$e        = fn($v) => ReportsService::h($v);
$maturity = $cardsReport['maturity'];
$leeches  = $cardsReport['leeches'];
$interval = $cardsReport['interval'];
$resp     = $cardsReport['response'];

$secs = fn(?int $ms) => $ms === null ? '—' : number_format($ms / 1000, 1) . 's';
$trim = fn(string $s, int $n) => mb_strimwidth($s, 0, $n, '…', 'UTF-8');

$cardsPayload = [
    'maturity' => $maturity['total'] > 0 ? [
        'labels' => array_column($maturity['segments'], 'label'),
        'values' => array_column($maturity['segments'], 'count'),
        'colors' => array_column($maturity['segments'], 'color'),
    ] : null,
    'interval' => $interval['hasData'] ? [
        'labels' => $interval['labels'],
        'prev'   => $interval['prev'],
        'new'    => $interval['new'],
        'counts' => $interval['counts'],
    ] : null,
];
?>

<?php if ($maturity['total'] === 0): ?>
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-8 text-center text-sm text-on-surface-variant">
        No cards match these filters.
    </div>
<?php else: ?>
<div class="flex flex-col gap-4 sm:gap-6">

    <!-- MATURITY + INTERVAL GROWTH -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">

        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">Card maturity</h3>
            <p class="text-xs text-on-surface-variant mb-3">
                <?= number_format($maturity['total']) ?> cards by how well they are learned. Current state, not affected by the period.
            </p>
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <div class="rp-chart-box relative shrink-0" style="height: 200px; width: 200px">
                    <canvas id="rp-chart-maturity" aria-label="Cards by maturity" role="img"></canvas>
                </div>
                <ul class="flex-1 w-full flex flex-col gap-2">
                    <?php foreach ($maturity['segments'] as $s): ?>
                        <li class="flex items-center gap-3 text-sm">
                            <span class="inline-block w-3 h-3 rounded-sm shrink-0" style="background: <?= $e($s['color']) ?>"></span>
                            <span class="flex-1 min-w-0">
                                <span class="font-semibold text-on-surface"><?= $e($s['label']) ?></span>
                                <span class="block text-[11px] text-on-surface-variant"><?= $e($s['rule']) ?></span>
                            </span>
                            <span class="tabular-nums font-semibold text-on-surface whitespace-nowrap">
                                <?= number_format($s['count']) ?>
                                <span class="text-xs font-normal text-on-surface-variant">(<?= $s['pct'] ?>%)</span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">Interval growth</h3>
            <p class="text-xs text-on-surface-variant mb-3">
                Weekly average interval before and after each review. If spacing works, the "after" line sits above the "before" line.
            </p>
            <?php if ($interval['hasData']): ?>
                <div class="rp-chart-box relative" style="height: 220px">
                    <canvas id="rp-chart-interval" aria-label="Average interval before and after review, per week" role="img"></canvas>
                </div>
                <p class="text-[11px] text-on-surface-variant mt-2">
                    Uses per-card review detail. Cards reset from the deck page lose their detail, so this can differ from session totals.
                </p>
            <?php else: ?>
                <div class="h-32 flex items-center justify-center text-sm text-on-surface-variant text-center">No review detail in this period.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PROBLEM CARDS -->
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden">
        <div class="px-4 sm:px-5 pt-4 pb-3">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">
                Problem cards
                <span class="ml-1 text-sm font-semibold <?= $maturity['leechTotal'] > 0 ? 'text-error' : 'text-on-surface-variant' ?>">(<?= number_format($maturity['leechTotal']) ?>)</span>
            </h3>
            <p class="text-xs text-on-surface-variant">Cards forgotten 8 or more times. Consider rewording or splitting them.</p>
        </div>

        <?php if ($leeches): ?>
            <div class="rp-table-wrap overflow-x-auto">
                <table class="rp-table w-full text-sm">
                    <thead>
                        <tr class="bg-surface-container-low text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                            <th class="text-left px-4 py-2.5">Card</th>
                            <th class="text-left px-3 py-2.5">Deck</th>
                            <th class="text-right px-3 py-2.5">Lapses</th>
                            <th class="text-right px-3 py-2.5">Reviews</th>
                            <th class="text-right px-3 py-2.5">Interval</th>
                            <th class="rp-no-print px-3 py-2.5"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        <?php foreach ($leeches as $c): ?>
                            <tr class="hover:bg-surface-container-low/60 transition-colors">
                                <td class="px-4 py-2.5 text-on-surface" title="<?= $e($c['front']) ?>"><?= $e($trim($c['front'], 80)) ?></td>
                                <td class="px-3 py-2.5 text-on-surface-variant">
                                    <?= $e($c['deck']) ?><?php if ($c['archived']): ?> <span class="text-[10px] font-bold uppercase text-on-surface-variant/70">archived</span><?php endif; ?>
                                </td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-error font-semibold"><?= number_format($c['lapses']) ?></td>
                                <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($c['reviews']) ?></td>
                                <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap"><?= number_format($c['interval']) ?>d</td>
                                <td class="rp-no-print px-3 py-2.5 text-right whitespace-nowrap">
                                    <?php if (!$c['archived']): ?>
                                        <a href="deck.php?deck_id=<?= (int) $c['deckId'] ?>" class="text-xs font-semibold text-on-surface-variant hover:text-primary hover:underline">Open deck</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($maturity['leechTotal'] > count($leeches)): ?>
                <p class="px-4 sm:px-5 py-3 text-xs text-on-surface-variant">
                    Showing the top <?= count($leeches) ?> of <?= number_format($maturity['leechTotal']) ?>. The CSV export has the full list.
                </p>
            <?php endif; ?>
        <?php else: ?>
            <div class="h-28 flex items-center justify-center text-sm text-on-surface-variant text-center px-4">No problem cards. Nice work.</div>
        <?php endif; ?>
    </div>

    <!-- RESPONSE TIME -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">

        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden min-w-0">
            <div class="px-4 sm:px-5 pt-4 pb-3">
                <h3 class="font-headline-sm text-base font-bold text-on-surface">Response time</h3>
                <p class="text-xs text-on-surface-variant">Time to answer, by result, in the selected period. Reviews without a recorded time are ignored.</p>
            </div>
            <?php if ($resp['hasData']): ?>
                <div class="rp-table-wrap overflow-x-auto">
                    <table class="rp-table w-full text-sm">
                        <thead>
                            <tr class="bg-surface-container-low text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <th class="text-left px-4 py-2.5">Result</th>
                                <th class="text-right px-3 py-2.5">Timed reviews</th>
                                <th class="text-right px-3 py-2.5">Average</th>
                                <th class="text-right px-3 py-2.5">Median</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php foreach (['remembered' => 'Remembered', 'forgot' => 'Forgot'] as $key => $label): $r = $resp[$key]; ?>
                                <tr>
                                    <td class="px-4 py-2.5 font-semibold text-on-surface"><?= $e($label) ?></td>
                                    <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($r['timed']) ?> <span class="text-xs text-on-surface-variant">of <?= number_format($r['total']) ?></span></td>
                                    <td class="px-3 py-2.5 text-right tabular-nums"><?= $secs($r['avg']) ?></td>
                                    <td class="px-3 py-2.5 text-right tabular-nums"><?= $secs($r['median']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="h-28 flex items-center justify-center text-sm text-on-surface-variant text-center px-4">No review detail in this period.</div>
            <?php endif; ?>
        </div>

        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden min-w-0">
            <div class="px-4 sm:px-5 pt-4 pb-3">
                <h3 class="font-headline-sm text-base font-bold text-on-surface">Slowest cards</h3>
                <p class="text-xs text-on-surface-variant">Highest average answer time (cards reviewed at least twice in the period).</p>
            </div>
            <?php if ($resp['slowest']): ?>
                <div class="rp-table-wrap overflow-x-auto">
                    <table class="rp-table w-full text-sm">
                        <thead>
                            <tr class="bg-surface-container-low text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <th class="text-left px-4 py-2.5">Card</th>
                                <th class="text-left px-3 py-2.5">Deck</th>
                                <th class="text-right px-3 py-2.5">Avg time</th>
                                <th class="text-right px-3 py-2.5">Reviews</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php foreach ($resp['slowest'] as $c): ?>
                                <tr>
                                    <td class="px-4 py-2.5 text-on-surface" title="<?= $e($c['front']) ?>"><?= $e($trim($c['front'], 50)) ?></td>
                                    <td class="px-3 py-2.5 text-on-surface-variant"><?= $e($trim($c['deck'], 24)) ?></td>
                                    <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap"><?= $secs($c['avg']) ?></td>
                                    <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($c['n']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="h-28 flex items-center justify-center text-sm text-on-surface-variant text-center px-4">Not enough timed reviews yet.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script type="application/json" id="rp-cards-data"><?= json_encode($cardsPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>