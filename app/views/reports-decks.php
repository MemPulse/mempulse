<?php
/**
 * reports-decks.php
 *
 * Decks tab: reviews/accuracy per deck, mastery per deck, sortable deck table.
 * Expects: $decksReport (from ReportsService::getDecksReport), $filters
 */
$rows   = $decksReport['rows'];
$totals = $decksReport['totals'];
$charts = $decksReport['charts'];

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

$chartHeight = function (?array $chart): int {
    return $chart ? max(160, 56 + 30 * count($chart['labels'])) : 0;
};
?>

<?php if (empty($rows)): ?>
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-8 text-center text-sm text-on-surface-variant">
        No decks match these filters.
    </div>
<?php else: ?>
<div class="flex flex-col gap-4 sm:gap-6">

    <?php if ($filters['deckId'] > 0): ?>
        <div class="rp-no-print flex items-center justify-between gap-3 rounded-xl bg-primary/5 border border-primary/20 px-4 py-2.5 text-sm text-on-surface">
            <span>Showing one deck: <strong><?= ReportsService::h($filters['deckTitle']) ?></strong></span>
            <a href="reports.php?<?= ReportsService::h(ReportsService::buildQuery($filters, ['deck' => null, 'tab' => 'decks'])) ?>"
               class="font-semibold text-primary hover:underline">Show all decks</a>
        </div>
    <?php endif; ?>

    <!-- CHARTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">Reviews by deck</h3>
            <p class="text-xs text-on-surface-variant mb-3">Cards reviewed in the period (top 10). Hover for accuracy.</p>
            <?php if ($charts['reviews']): ?>
                <div class="rp-chart-box relative" style="height: <?= $chartHeight($charts['reviews']) ?>px">
                    <canvas id="rp-chart-deck-reviews" aria-label="Cards reviewed per deck" role="img"></canvas>
                </div>
            <?php else: ?>
                <div class="h-32 flex items-center justify-center text-sm text-on-surface-variant text-center">No reviews in this period.</div>
            <?php endif; ?>
        </div>

        <div class="rp-avoid-break bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 min-w-0">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">Mastery by deck</h3>
            <p class="text-xs text-on-surface-variant mb-3">Mastered cards (interval 21+ days) vs. the rest (top 10 by size). Not affected by the period.</p>
            <?php if ($charts['mastery']): ?>
                <div class="rp-chart-box relative" style="height: <?= $chartHeight($charts['mastery']) ?>px">
                    <canvas id="rp-chart-deck-mastery" aria-label="Mastered versus remaining cards per deck" role="img"></canvas>
                </div>
            <?php else: ?>
                <div class="h-32 flex items-center justify-center text-sm text-on-surface-variant text-center">No cards yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- DECK TABLE -->
    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden">
        <div class="px-4 sm:px-5 pt-4 pb-3">
            <h3 class="font-headline-sm text-base font-bold text-on-surface">Deck summary</h3>
            <p class="text-xs text-on-surface-variant">
                Card columns show the current state. Reviews, accuracy, sessions and study time cover the selected period.
                <span class="rp-no-print">Click a column heading to sort.</span>
            </p>
        </div>

        <div class="rp-table-wrap overflow-x-auto">
            <table id="rp-decks-table" class="rp-table w-full text-sm">
                <thead>
                    <tr class="bg-surface-container-low text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                        <th class="text-left px-4 py-2.5" data-sort="text" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Deck</button></th>
                        <th class="text-right px-3 py-2.5" data-sort="num" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Cards</button></th>
                        <th class="text-right px-3 py-2.5" data-sort="num" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Due</button></th>
                        <th class="text-left px-3 py-2.5" data-sort="num" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Mastered</button></th>
                        <th class="text-right px-3 py-2.5" data-sort="num" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Tough</button></th>
                        <th class="text-right px-3 py-2.5" data-sort="num" aria-sort="descending"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Reviews</button></th>
                        <th class="text-right px-3 py-2.5" data-sort="num" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Accuracy</button></th>
                        <th class="text-right px-3 py-2.5" data-sort="num" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Sessions</button></th>
                        <th class="text-right px-3 py-2.5" data-sort="num" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Study time</button></th>
                        <th class="text-left px-3 py-2.5" data-sort="num" aria-sort="none"><button type="button" class="rp-sort-btn font-bold uppercase tracking-wider">Last studied</button></th>
                        <th class="rp-no-print px-3 py-2.5"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    <?php foreach ($rows as $r):
                        $lastTs = $r['lastStudied'] ? strtotime($r['lastStudied']) : 0;
                    ?>
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="px-4 py-2.5 font-semibold text-on-surface" data-value="<?= ReportsService::h(mb_strtolower($r['title'])) ?>">
                                <?= ReportsService::h($r['title']) ?>
                                <?php if ($r['archived']): ?><span class="ml-1 text-[10px] font-bold uppercase text-on-surface-variant/70">archived</span><?php endif; ?>
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums" data-value="<?= $r['cards'] ?>"><?= number_format($r['cards']) ?></td>
                            <td class="px-3 py-2.5 text-right tabular-nums" data-value="<?= $r['dueNow'] ?>"><?= number_format($r['dueNow']) ?></td>
                            <td class="px-3 py-2.5" data-value="<?= $r['masteryPct'] ?>">
                                <div class="flex items-center gap-2 min-w-[7rem]">
                                    <div class="rp-bar flex-1 h-1.5 rounded-full bg-surface-container-high overflow-hidden">
                                        <div class="h-full rounded-full bg-teal-500" style="width: <?= $r['masteryPct'] ?>%"></div>
                                    </div>
                                    <span class="text-xs tabular-nums text-on-surface-variant whitespace-nowrap"><?= number_format($r['mastered']) ?> (<?= $r['masteryPct'] ?>%)</span>
                                </div>
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums <?= $r['leeches'] > 0 ? 'text-error font-semibold' : '' ?>" data-value="<?= $r['leeches'] ?>"><?= number_format($r['leeches']) ?></td>
                            <td class="px-3 py-2.5 text-right tabular-nums" data-value="<?= $r['reviews'] ?>"><?= number_format($r['reviews']) ?></td>
                            <td class="px-3 py-2.5 text-right" data-value="<?= $r['accuracy'] === null ? -1 : $r['accuracy'] ?>"><?= $accuracyBadge($r['accuracy']) ?></td>
                            <td class="px-3 py-2.5 text-right tabular-nums" data-value="<?= $r['sessions'] ?>"><?= number_format($r['sessions']) ?></td>
                            <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap" data-value="<?= $r['seconds'] ?>"><?= $r['seconds'] > 0 ? ReportsService::h(ReportsService::formatDuration($r['seconds'])) : '—' ?></td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-on-surface-variant" data-value="<?= $lastTs ?>"><?= $lastTs ? ReportsService::h(date('M j, Y', $lastTs)) : '—' ?></td>
                            <td class="rp-no-print px-3 py-2.5 whitespace-nowrap text-right">
                                <?php if ($filters['deckId'] !== $r['id']): ?>
                                    <a href="reports.php?<?= ReportsService::h(ReportsService::buildQuery($filters, ['deck' => $r['id'], 'tab' => 'overview'])) ?>"
                                       class="text-xs font-semibold text-primary hover:underline" title="Show this deck's overview">Focus</a>
                                <?php endif; ?>
                                <?php if (!$r['archived']): ?>
                                    <a href="deck.php?deck_id=<?= (int) $r['id'] ?>"
                                       class="ml-2 text-xs font-semibold text-on-surface-variant hover:text-primary hover:underline" title="Open deck">Open</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if ($totals && count($rows) > 1): ?>
                <tfoot>
                    <tr class="bg-surface-container-low font-bold text-on-surface">
                        <td class="px-4 py-2.5">Total (<?= number_format($totals['decks']) ?> decks)</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($totals['cards']) ?></td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($totals['dueNow']) ?></td>
                        <td class="px-3 py-2.5 text-xs tabular-nums whitespace-nowrap"><?= number_format($totals['mastered']) ?> (<?= $totals['masteryPct'] ?>%)</td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($totals['leeches']) ?></td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($totals['reviews']) ?></td>
                        <td class="px-3 py-2.5 text-right"><?= $accuracyBadge($totals['accuracy']) ?></td>
                        <td class="px-3 py-2.5 text-right tabular-nums"><?= number_format($totals['sessions']) ?></td>
                        <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap"><?= $totals['seconds'] > 0 ? ReportsService::h(ReportsService::formatDuration($totals['seconds'])) : '—' ?></td>
                        <td class="px-3 py-2.5"></td>
                        <td class="rp-no-print"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$decksPayload = ['reviews' => $charts['reviews'] ?? null, 'mastery' => $charts['mastery'] ?? null];
?>
<script type="application/json" id="rp-decks-data"><?= json_encode($decksPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>