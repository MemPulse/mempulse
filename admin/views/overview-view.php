<?php
// Expects (from index.php): $metrics, $pendingUsers, $recentSignups, $recentSessions.
$m   = $metrics;
$fmt = fn($n) => number_format((int) $n);

$initial = function (string $name): string {
    $c = trim($name) === '' ? '?' : (function_exists('mb_substr') ? mb_substr(trim($name), 0, 1) : substr(trim($name), 0, 1));
    return function_exists('mb_strtoupper') ? mb_strtoupper($c) : strtoupper($c);
};

// Full class strings (not built dynamically) so the Tailwind scan finds them.
$accents = [
    'primary' => ['from' => 'from-primary/10',    'blob' => 'bg-primary/10 group-hover:bg-primary/20',       'label' => 'text-primary',    'badge' => 'bg-primary/10 border-primary/20 text-primary'],
    'teal'    => ['from' => 'from-teal-500/10',   'blob' => 'bg-teal-500/10 group-hover:bg-teal-500/20',     'label' => 'text-teal-700',   'badge' => 'bg-teal-50 border-teal-200/80 text-teal-600'],
    'amber'   => ['from' => 'from-amber-500/10',  'blob' => 'bg-amber-500/10 group-hover:bg-amber-500/20',   'label' => 'text-amber-700',  'badge' => 'bg-amber-50 border-amber-200/80 text-amber-600'],
    'indigo'  => ['from' => 'from-indigo-500/10', 'blob' => 'bg-indigo-500/10 group-hover:bg-indigo-500/20', 'label' => 'text-indigo-700', 'badge' => 'bg-indigo-50 border-indigo-200/80 text-indigo-600'],
    'violet'  => ['from' => 'from-violet-500/10', 'blob' => 'bg-violet-500/10 group-hover:bg-violet-500/20', 'label' => 'text-violet-700', 'badge' => 'bg-violet-50 border-violet-200/80 text-violet-600'],
    'cyan'    => ['from' => 'from-cyan-500/10',   'blob' => 'bg-cyan-500/10 group-hover:bg-cyan-500/20',     'label' => 'text-cyan-700',   'badge' => 'bg-cyan-50 border-cyan-200/80 text-cyan-600'],
];
$tones = [
    'up'   => 'bg-teal-50 text-teal-700',
    'down' => 'bg-error-container/40 text-error',
    'flat' => 'bg-surface-container-low text-on-surface-variant',
];
$statusBadge = [
    'approved' => 'bg-teal-50 text-teal-700 border-teal-200/80',
    'pending'  => 'bg-amber-50 text-amber-700 border-amber-200/80',
    'rejected' => 'bg-error-container/40 text-error border-error/20',
];

$newUsersChip = ['text' => '+' . $fmt($m['newUsers7d']) . ' this week', 'tone' => $m['newUsers7d'] > 0 ? 'up' : 'flat'];

$cards = [
    ['accent' => 'primary', 'label' => 'Users',     'icon' => 'group',          'value' => $fmt($m['totalUsers']),   'caption' => 'Approved accounts',
     'tip' => 'Approved accounts that have not been deleted.', 'chip' => $newUsersChip],
    ['accent' => 'teal',    'label' => 'Active',    'icon' => 'bolt',           'value' => $fmt($m['activeToday']),  'caption' => $fmt($m['active7d']) . ' active in 7 days',
     'tip' => 'Distinct learners who started a study session today.', 'chip' => null],
    ['accent' => 'amber',   'label' => 'Pending',   'icon' => 'hourglass_top',  'value' => $fmt($m['pendingUsers']), 'caption' => 'Awaiting approval',
     'tip' => 'Accounts registered but not yet approved.', 'chip' => null],
    ['accent' => 'indigo',  'label' => 'Decks',     'icon' => 'style',          'value' => $fmt($m['decks']),        'caption' => 'Active decks',
     'tip' => 'Decks that have not been deleted, owned by active users.', 'chip' => null],
    ['accent' => 'violet',  'label' => 'Flashcards', 'icon' => 'layers',        'value' => $fmt($m['cards']),        'caption' => 'Across all decks',
     'tip' => 'Cards that have not been deleted, in active decks.', 'chip' => null],
    ['accent' => 'cyan',    'label' => 'Reviews',   'icon' => 'check_circle',   'value' => $fmt($m['reviews7d']),    'caption' => $m['retention30d'] . '% retention (30 days)',
     'tip' => 'Cards reviewed in completed sessions over the last 7 days.', 'chip' => AdminOverviewService::percentChip($m['reviews7d'], $m['reviewsPrev7d'], 'vs prior 7d')],
];

$panel = 'bg-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 sm:p-5 shadow-sm ring-1 ring-black/5 dark:ring-white/5 min-w-0';
$row   = 'flex items-center justify-between gap-3 p-3 rounded-xl bg-surface-container-low border border-outline-variant/40';
?>
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <header class="flex flex-col gap-1">
            <h1 class="font-headline-md text-headline-lg-mobile sm:text-headline-lg font-bold tracking-tight text-on-surface">Admin Overview</h1>
            <p class="text-sm text-on-surface-variant">Platform health at a glance. Study activity is shown as aggregates only.</p>
        </header>

        <?php if (!$m['available']): ?>
            <div role="alert" class="flex items-start gap-3 p-4 rounded-2xl bg-error-container/40 border border-error/20 text-sm text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-error">error</span>
                <span>We couldn't load the platform metrics. Refresh the page, and check the server log if it keeps happening.</span>
            </div>
        <?php endif; ?>

        <!-- Metric cards -->
        <section aria-label="Key metrics" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3 sm:gap-4">
            <?php foreach ($cards as $c): $a = $accents[$c['accent']]; ?>
                <div class="bg-gradient-to-br <?= $a['from'] ?> via-surface-container-lowest to-surface-container-lowest border border-outline-variant/60 rounded-2xl p-4 sm:p-5 shadow-sm ring-1 ring-black/5 dark:ring-white/5 flex flex-col justify-between h-32 sm:h-36 relative group hover:border-primary/40 hover:shadow-md transition-all">
                    <div class="absolute right-0 bottom-0 w-20 h-20 <?= $a['blob'] ?> rounded-br-2xl rounded-tl-full blur-md pointer-events-none transition-all"></div>

                    <div class="flex items-center justify-between w-full relative z-10">
                        <div class="flex items-center gap-1.5">
                            <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider <?= $a['label'] ?>"><?= adminEsc($c['label']) ?></span>
                            <div class="relative group/tooltip flex items-center">
                                <span class="material-symbols-outlined text-[15px] text-on-surface-variant/60 cursor-help hover:text-on-surface transition-colors" aria-hidden="true">info</span>
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                    <?= adminEsc($c['tip']) ?>
                                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                </div>
                            </div>
                        </div>
                        <div class="w-8 h-8 rounded-xl border <?= $a['badge'] ?> flex items-center justify-center flex-shrink-0 shadow-xs">
                            <span class="material-symbols-outlined text-[18px]" aria-hidden="true"><?= $c['icon'] ?></span>
                        </div>
                    </div>

                    <div class="flex items-end justify-between gap-2 mt-2 relative z-10">
                        <div class="flex flex-col min-w-0">
                            <span class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface"><?= adminEsc($c['value']) ?></span>
                            <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5 truncate"><?= adminEsc($c['caption']) ?></span>
                        </div>
                        <?php if ($c['chip']): ?>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-md whitespace-nowrap <?= $tones[$c['chip']['tone']] ?>"><?= adminEsc($c['chip']['text']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Activity chart + approval queue -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch">

            <section class="lg:col-span-8 <?= $panel ?> flex flex-col" aria-labelledby="activity-title">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">monitoring</span>
                            <h2 id="activity-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Platform activity</h2>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Reviews, active learners and sign-ups per day</p>
                    </div>
                    <div class="inline-flex p-1 rounded-xl bg-surface-container-low border border-outline-variant/30" role="group" aria-label="Chart range">
                        <?php foreach ([7, 30, 90] as $r): ?>
                            <button type="button" data-range="<?= $r ?>" aria-pressed="false"
                                class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><?= $r ?>d</button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="relative w-full h-64 sm:h-72 lg:h-80 min-w-0">
                    <div id="overview-chart-loading" class="absolute inset-0 flex items-center justify-center text-sm text-on-surface-variant">Loading chart…</div>
                    <div id="overview-chart-status" class="hidden absolute inset-0 flex-col items-center justify-center text-center text-on-surface-variant gap-1 p-6 z-10">
                        <span class="material-symbols-outlined text-[32px] text-outline" aria-hidden="true">insert_chart</span>
                        <span id="overview-chart-status-title" class="text-sm font-medium"></span>
                        <span id="overview-chart-status-text" class="text-xs text-on-surface-variant/70"></span>
                    </div>
                    <canvas id="overview-chart" role="img" aria-label="Daily reviews, active learners and sign-ups"></canvas>
                </div>
            </section>

            <section class="lg:col-span-4 <?= $panel ?> flex flex-col" aria-labelledby="pending-title">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600 text-[22px]" aria-hidden="true">how_to_reg</span>
                            <h2 id="pending-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Needs approval</h2>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Longest-waiting first</p>
                    </div>
                    <?php if ($m['pendingUsers'] > 0): ?>
                        <span class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-md"><?= $fmt($m['pendingUsers']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-2.5 flex-1 <?= $pendingUsers ? '' : 'justify-center' ?>">
                    <?php if ($pendingUsers): foreach ($pendingUsers as $u): ?>
                        <div class="<?= $row ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0 text-xs font-bold"><?= adminEsc($initial($u['name'])) ?></div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($u['name']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($u['email']) ?></span>
                                </div>
                            </div>
                            <span class="text-[10px] text-on-surface-variant/70 whitespace-nowrap"><?= adminEsc($u['age']) ?></span>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">task_alt</span>
                            <span class="text-sm font-medium">You're all caught up.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">New sign-ups awaiting approval will appear here.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- Recent sign-ups + recent sessions -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch">

            <section class="lg:col-span-6 <?= $panel ?>" aria-labelledby="signups-title">
                <div class="mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">person_add</span>
                        <h2 id="signups-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent sign-ups</h2>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Newest accounts, any status</p>
                </div>
                <div class="flex flex-col gap-2.5">
                    <?php if ($recentSignups): foreach ($recentSignups as $u): ?>
                        <div class="<?= $row ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-xs font-bold"><?= adminEsc($initial($u['name'])) ?></div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($u['name']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($u['email']) ?></span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border <?= $statusBadge[$u['status']] ?? $statusBadge['pending'] ?>"><?= adminEsc($u['status']) ?></span>
                                <span class="text-[10px] text-on-surface-variant/70"><?= adminEsc($u['age']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">group_off</span>
                            <span class="text-sm font-medium">No accounts yet.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lg:col-span-6 <?= $panel ?>" aria-labelledby="sessions-title">
                <div class="mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">history</span>
                        <h2 id="sessions-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent study sessions</h2>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Completed sessions across the platform</p>
                </div>
                <div class="flex flex-col gap-2.5">
                    <?php if ($recentSessions): foreach ($recentSessions as $s): ?>
                        <div class="<?= $row ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">style</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($s['name']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant"><?= (int) $s['cardsReviewed'] ?> cards reviewed</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end flex-shrink-0">
                                <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md"><?= (int) $s['accuracy'] ?>%</span>
                                <span class="text-[10px] text-on-surface-variant/70 mt-1"><?= adminEsc($s['age']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">history_edu</span>
                            <span class="text-sm font-medium">No completed sessions yet.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">Sessions appear here once learners finish studying.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    </div>
</div>