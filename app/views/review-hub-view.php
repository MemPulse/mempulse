<?php
/**
 * Review Hub view (STATE A of review.php)
 *
 * Expects these variables from review.php:
 *   $dueDecks, $upcomingDecks, $recentSessions  (see ReviewHubService)
 *   $dueTotal, $nextDueSecs, $curStreak, $reviewedToday
 */

$h = [ReviewHubService::class, 'h'];

// ---- Due Now call-to-action state ----
$dueDeckCount = count($dueDecks);
$reviewHref   = $dueDeckCount > 0 ? 'review.php?deck_id=' . (int) $dueDecks[0]['id'] : 'review.php';
$streakAtRisk = $dueTotal > 0 && $curStreak > 0 && $reviewedToday === 0;

if ($streakAtRisk) {
    $dueSubtitle = 'Keep your ' . $curStreak . '-day streak alive';
} elseif ($dueDeckCount === 1) {
    $dueSubtitle = 'in ' . $dueDecks[0]['title'];
} elseif ($dueDeckCount > 1) {
    $dueSubtitle = 'Starting with ' . $dueDecks[0]['title'] . ' (+' . ($dueDeckCount - 1) . ' more)';
} else {
    $dueSubtitle = '';
}

$nextDueLabel = ($nextDueSecs !== null && $nextDueSecs > 0)
    ? 'Next cards due in ' . ReviewHubService::formatSpan($nextDueSecs)
    : '';
?>
<!-- ========================================================================= -->
<!-- STATE A: REVIEW HUB (dashboard-style layout, table lists)                 -->
<!-- ========================================================================= -->
<div class="flex flex-col w-full gap-4 sm:gap-6">

    <!-- DIV: ROW 1 - Due Now call-to-action (left) vs Keyboard controls (right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">

        <!-- DIV: LEFT - Due Now call-to-action -->
        <div class="lg:col-span-6 flex w-full min-w-0">

            <?php if ($dueTotal > 0): ?>
                <!-- A: Cards due - primary call-to-action -->
                <a href="<?= $h($reviewHref) ?>"
                   id="stat-due-cta"
                   aria-label="Review <?= (int) $dueTotal ?> due <?= $dueTotal === 1 ? 'card' : 'cards' ?>"
                   class="group/cta relative overflow-hidden flex items-center justify-between gap-4 w-full h-full min-h-[84px] px-4 sm:px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-on-primary shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                    <div class="absolute -right-6 -top-8 w-28 h-28 bg-white/15 rounded-full blur-2xl pointer-events-none"></div>

                    <div class="relative z-10 flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-[22px]">alarm</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-primary/80">Due Now</span>
                            <span class="text-base sm:text-lg font-extrabold leading-tight truncate">
                                <span id="stat-due-cta-count"><?= (int) $dueTotal ?></span>
                                <?= $dueTotal === 1 ? 'card' : 'cards' ?> ready to review
                            </span>
                            <?php if ($dueSubtitle !== ''): ?>
                                <span class="text-[11px] sm:text-xs text-on-primary/80 truncate"><?= $h($dueSubtitle) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="relative z-10 inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl bg-white text-primary font-label-md text-xs font-bold shadow-xs flex-shrink-0">
                        <span class="hidden sm:inline">Start Review</span>
                        <span class="material-symbols-outlined text-[18px] transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                    </span>
                </a>

            <?php elseif ($nextDueSecs !== null): ?>
                <!-- B: Nothing due, cards are scheduled -->
                <div id="stat-due-cta" class="flex items-center gap-3 w-full h-full min-h-[84px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 flex-shrink-0">
                        <span class="material-symbols-outlined text-[22px]">task_alt</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                        <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface">All caught up</span>
                        <?php if ($nextDueLabel !== ''): ?>
                            <span class="text-[11px] sm:text-xs text-on-surface-variant"><?= $h($nextDueLabel) ?></span>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <!-- C: No scheduled cards at all - point to creation -->
                <a href="decks.php?add_card=1" id="stat-due-cta"
                   class="group/cta flex items-center justify-between gap-4 w-full h-full min-h-[84px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs hover:border-primary/50 hover:shadow-md transition-all">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                            <span class="material-symbols-outlined text-[22px]">add</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                            <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface truncate">Create your first cards</span>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-[20px] text-primary flex-shrink-0 transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                </a>
            <?php endif; ?>

        </div>

        <!-- DIV: RIGHT - Keyboard controls (desktop only: useless on touch screens) -->
        <div class="lg:col-span-6 hidden lg:flex w-full min-w-0">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs w-full flex flex-col justify-center">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-[22px]">keyboard</span>
                    <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Review Keyboard Controls</h3>
                </div>
                <ul class="grid grid-cols-2 gap-x-6 gap-y-2.5 text-sm text-on-surface-variant">
                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border border-outline-variant/40 font-mono text-xs">Space</kbd> Show Answer / Remember</li>
                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border border-outline-variant/40 font-mono text-xs">F</kbd> Mark as Forgotten</li>
                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border border-outline-variant/40 font-mono text-xs">←</kbd> Previous Card</li>
                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border border-outline-variant/40 font-mono text-xs">→</kbd> Next Card</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- DIV: ROW 2 - Due Now table (full width) -->
    <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-w-0">
        <div class="flex items-center justify-between mb-3">
            <div>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[22px]">schedule</span>
                    <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Due Now</h3>
                </div>
                <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Decks with cards ready to review</p>
            </div>
            <a href="decks.php" class="text-xs text-primary hover:underline font-semibold flex items-center gap-1 transition-all">
                <span>All Decks</span>
                <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="sr-only">Decks with cards due now</caption>
                <thead>
                    <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">
                        <th scope="col" class="py-2 pr-3 font-bold">Deck</th>
                        <th scope="col" class="py-2 px-3 font-bold text-right">Due</th>
                        <th scope="col" class="hidden md:table-cell py-2 px-3 font-bold text-right">Oldest due</th>
                        <th scope="col" class="hidden sm:table-cell py-2 px-3 font-bold text-right">Total cards</th>
                        <th scope="col" class="py-2 pl-3 font-bold text-right"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dueDecks)): ?>
                        <tr class="border-t border-outline-variant/20">
                            <td colspan="5" class="py-8 text-center text-on-surface-variant text-sm">You are all caught up for now!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dueDecks as $d): ?>
                            <tr class="border-t border-outline-variant/20 hover:bg-surface-container-low transition-colors">
                                <td class="py-2.5 pr-3">
                                    <a href="deck.php?deck_id=<?= (int) $d['id'] ?>"
                                       title="<?= $h($d['title']) ?>"
                                       class="block truncate max-w-[12rem] sm:max-w-xs lg:max-w-md font-semibold text-on-surface hover:text-primary transition-colors">
                                        <?= $h($d['title']) ?>
                                    </a>
                                </td>
                                <td class="py-2.5 px-3 text-right">
                                    <span class="inline-block text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200/80 px-2 py-0.5 rounded-md tabular-nums">
                                        <?= (int) $d['dueCount'] ?>
                                    </span>
                                </td>
                                <td class="hidden md:table-cell py-2.5 px-3 text-right text-on-surface-variant tabular-nums whitespace-nowrap">
                                    <?= $d['overdueSeconds'] === null ? '—' : $h(ReviewHubService::formatSpan($d['overdueSeconds']) . ' ago') ?>
                                </td>
                                <td class="hidden sm:table-cell py-2.5 px-3 text-right text-on-surface-variant tabular-nums">
                                    <?= (int) $d['totalCards'] ?>
                                </td>
                                <td class="py-2.5 pl-3 text-right">
                                    <a href="review.php?deck_id=<?= (int) $d['id'] ?>"
                                       aria-label="Review <?= $h($d['title']) ?>"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary text-on-primary text-xs font-bold hover:bg-primary-container transition-colors">
                                        <span>Review</span>
                                        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- DIV: ROW 3 - Upcoming (left) vs Recent Review Sessions (right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">

        <!-- DIV: LEFT - Upcoming table -->
        <section class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-w-0">
            <div class="mb-3">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-[22px]">update</span>
                    <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Upcoming (Next <?= (int) (ReviewHubService::UPCOMING_DAYS * 24) ?>h)</h3>
                </div>
                <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Cards that will be due soon</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <caption class="sr-only">Decks with cards coming up in the next 48 hours</caption>
                    <thead>
                        <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">
                            <th scope="col" class="py-2 pr-3 font-bold">Deck</th>
                            <th scope="col" class="py-2 px-3 font-bold text-right">Cards soon</th>
                            <th scope="col" class="py-2 pl-3 font-bold text-right">Next due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($upcomingDecks)): ?>
                            <tr class="border-t border-outline-variant/20">
                                <td colspan="3" class="py-8 text-center text-on-surface-variant text-sm">No cards scheduled for the next <?= (int) (ReviewHubService::UPCOMING_DAYS * 24) ?> hours.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($upcomingDecks as $d): ?>
                                <tr class="border-t border-outline-variant/20 hover:bg-surface-container-low transition-colors">
                                    <td class="py-2.5 pr-3">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <a href="deck.php?deck_id=<?= (int) $d['id'] ?>"
                                               title="<?= $h($d['title']) ?>"
                                               class="block truncate max-w-[9rem] sm:max-w-[14rem] font-semibold text-on-surface hover:text-primary transition-colors">
                                                <?= $h($d['title']) ?>
                                            </a>
                                            <?php if ($d['dueCount'] > 0): ?>
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 whitespace-nowrap">Due now</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        <span class="inline-block text-xs text-on-surface-variant bg-surface-container-high px-2 py-0.5 rounded-md tabular-nums">
                                            <?= (int) $d['upcomingCount'] ?>
                                        </span>
                                    </td>
                                    <td class="py-2.5 pl-3 text-right text-on-surface-variant whitespace-nowrap">
                                        <?= $d['nextDueSeconds'] === null ? '—' : $h('in ' . ReviewHubService::formatSpan($d['nextDueSeconds'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- DIV: RIGHT - Recent Review Sessions table -->
        <section class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col min-w-0">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]">history</span>
                        <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent Review Sessions</h3>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Your latest spaced repetition drills</p>
                </div>
                <a href="sessions.php" class="text-xs text-primary hover:underline font-semibold flex items-center gap-1 transition-all">
                    <span>View All</span>
                    <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                </a>
            </div>

            <?php if (empty($recentSessions)): ?>
                <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant flex-1">
                    <span class="material-symbols-outlined text-[32px] text-outline mb-2">history_edu</span>
                    <span class="text-sm font-medium">No recent review sessions found.</span>
                    <span class="text-xs mt-1 text-on-surface-variant/70">Complete a study session to see your history here.</span>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <caption class="sr-only">Most recent completed review sessions</caption>
                        <thead>
                            <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">
                                <th scope="col" class="py-2 pr-3 font-bold">Deck</th>
                                <th scope="col" class="py-2 px-3 font-bold text-right">Cards</th>
                                <th scope="col" class="py-2 px-3 font-bold text-right">Accuracy</th>
                                <th scope="col" class="hidden xl:table-cell py-2 px-3 font-bold text-right">Duration</th>
                                <th scope="col" class="py-2 pl-3 font-bold text-right">When</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSessions as $s): ?>
                                <tr class="border-t border-outline-variant/20 hover:bg-surface-container-low transition-colors">
                                    <td class="py-2.5 pr-3">
                                        <span class="block truncate max-w-[8rem] sm:max-w-[12rem] font-semibold text-on-surface" title="<?= $h($s['deckTitle']) ?>">
                                            <?= $h($s['deckTitle']) ?>
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-right text-on-surface-variant tabular-nums"><?= (int) $s['cardsReviewed'] ?></td>
                                    <td class="py-2.5 px-3 text-right">
                                        <span class="inline-block text-xs font-bold border px-2 py-0.5 rounded-md tabular-nums <?= ReviewHubService::accuracyBadgeClass($s['accuracy']) ?>">
                                            <?= (int) $s['accuracy'] ?>%
                                        </span>
                                    </td>
                                    <td class="hidden xl:table-cell py-2.5 px-3 text-right text-on-surface-variant tabular-nums whitespace-nowrap">
                                        <?= $h(ReviewHubService::formatDuration($s['durationSeconds'])) ?>
                                    </td>
                                    <td class="py-2.5 pl-3 text-right text-on-surface-variant whitespace-nowrap text-xs">
                                        <?= $h($s['formattedTime']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>