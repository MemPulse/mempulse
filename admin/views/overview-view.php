<?php
// Expects (from index.php): $metrics, $pendingUsers, $recentSignups, $recentSessions.
$m   = $metrics;
$fmt = fn($n) => number_format((int) $n);

$initial = function (string $name): string {
    $c = trim($name) === '' ? '?' : (function_exists('mb_substr') ? mb_substr(trim($name), 0, 1) : substr(trim($name), 0, 1));
    return function_exists('mb_strtoupper') ? mb_strtoupper($c) : strtoupper($c);
};

// Colour accents. Same four palettes the student dashboard uses, written as full
// literal class strings so the Tailwind scanner always finds them.
$accents = [
    'primary' => [
        'card'  => 'from-primary/10 hover:border-primary/50',
        'blob'  => 'bg-primary/10 group-hover:bg-primary/20',
        'label' => 'text-primary',
        'info'  => 'text-primary/60 hover:text-primary',
        'badge' => 'bg-primary/10 border-primary/20 text-primary',
    ],
    'teal' => [
        'card'  => 'from-teal-500/10 hover:border-teal-500/50',
        'blob'  => 'bg-teal-500/10 group-hover:bg-teal-500/20',
        'label' => 'text-teal-700',
        'info'  => 'text-teal-700/60 hover:text-teal-700',
        'badge' => 'bg-teal-50 border-teal-200/80 text-teal-600',
    ],
    'indigo' => [
        'card'  => 'from-indigo-500/10 hover:border-indigo-500/50',
        'blob'  => 'bg-indigo-500/10 group-hover:bg-indigo-500/20',
        'label' => 'text-indigo-700',
        'info'  => 'text-indigo-700/60 hover:text-indigo-700',
        'badge' => 'bg-indigo-50 border-indigo-200/80 text-indigo-600',
    ],
    'rose' => [
        'card'  => 'from-rose-500/10 hover:border-error/50',
        'blob'  => 'bg-rose-500/10 group-hover:bg-rose-500/20',
        'label' => 'text-error',
        'info'  => 'text-error/60 hover:text-error',
        'badge' => 'bg-red-50 border-red-200/80 text-error',
    ],
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

// ---- Row 1: four core platform metrics (2x2) --------------------------------
$newUsersChip = $m['newUsers7d'] > 0
    ? ['text' => '+' . $fmt($m['newUsers7d']) . ' this week', 'tone' => 'up']
    : null;

$cards = [
    ['accent' => 'primary', 'tag' => 'Users',      'icon' => 'group',  'value' => $fmt($m['totalUsers']),  'caption' => 'Approved Users',
     'tip' => 'Approved accounts that have not been deleted.', 'chip' => $newUsersChip],
    ['accent' => 'teal',    'tag' => 'Active',     'icon' => 'bolt',   'value' => $fmt($m['activeToday']), 'caption' => 'Active Today · ' . $fmt($m['active7d']) . ' in 7 days',
     'tip' => 'Distinct learners who started a study session today.', 'chip' => null],
    ['accent' => 'indigo',  'tag' => 'Decks',      'icon' => 'style',  'value' => $fmt($m['decks']),       'caption' => 'Active Decks',
     'tip' => 'Decks that have not been deleted, owned by active users.', 'chip' => null],
    ['accent' => 'rose',    'tag' => 'Flashcards', 'icon' => 'layers', 'value' => $fmt($m['cards']),       'caption' => 'Flashcards Across All Decks',
     'tip' => 'Cards that have not been deleted, in active decks.', 'chip' => null],
];

// ---- Row 1: hero carousel slides (admin quick actions) ----------------------
$pendingCount = (int) $m['pendingUsers'];
$heroSlides = [
    [
        'badge' => 'bg-amber-500/20 border-amber-500/30 text-amber-200',
        'icon'  => 'how_to_reg',
        'tag'   => 'Approvals',
        'title' => $pendingCount > 0 ? 'Review Pending Accounts' : 'No Accounts Waiting',
        'text'  => $pendingCount > 0
            ? $fmt($pendingCount) . ($pendingCount === 1 ? ' new account is' : ' new accounts are') . ' waiting for your decision before they can start studying.'
            : 'Every registered account has been reviewed. New sign-ups that need approval will show up here.',
        'href'  => ADMIN_URL . 'views/users.php' . ($pendingCount > 0 ? '?status=pending' : ''),
        'cta'   => $pendingCount > 0 ? 'Review Queue' : 'Open Users',
        'dot'   => 'Approvals',
    ],
    [
        'badge' => 'bg-primary/20 border-primary/30 text-cyan-200',
        'icon'  => 'group',
        'tag'   => 'Users',
        'title' => 'Manage Your Users',
        'text'  => 'Browse every account, check its status and keep the platform membership organized.',
        'href'  => ADMIN_URL . 'views/users.php',
        'cta'   => 'Manage Users',
        'dot'   => 'Users',
    ],
];

// ---- Row 2: "Needs Approval" call-to-action state ---------------------------
$oldestWait = $pendingUsers[0]['age'] ?? '';
$ctaHref    = ADMIN_URL . 'views/users.php?status=pending';

// Shared list styling (matches the dashboard's session rows).
$panel = 'bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs min-w-0';
$row   = 'flex items-center justify-between gap-3 p-3 rounded-xl bg-surface-container-low border border-outline-variant/20 hover:border-primary/30 hover:bg-surface-container transition-all';

$reviewsChip = AdminOverviewService::percentChip($m['reviews7d'], $m['reviewsPrev7d'], 'vs prior 7d');
?>
<!-- ========================================================================= -->
<!-- ADMIN OVERVIEW CONTAINER                                                  -->
<!-- Same shell, grid and card language as app/views/dashboard.php             -->
<!-- ========================================================================= -->
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <h1 class="sr-only">Admin Overview</h1>

        <?php if (!$m['available']): ?>
            <!-- DIV: Metrics Unavailable Alert -->
            <div role="alert" class="flex items-start gap-3 p-4 rounded-2xl bg-error-container/40 border border-error/20 text-sm text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-error">error</span>
                <span>We couldn't load the platform metrics. Refresh the page, and check the server log if it keeps happening.</span>
            </div>
        <?php endif; ?>

        <!-- DIV: TOP SECTION - Core Metrics, Action Carousel & Approval Bar -->
        <div class="flex flex-col gap-4 sm:gap-6 w-full">

            <!-- DIV: ROW 1 - Grid Split: 2x2 Metric Grid (Left) vs Hero Carousel (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">

                <!-- DIV: LEFT COLUMN - 2x2 Core Metrics -->
                <div class="lg:col-span-6 flex flex-col justify-between w-full min-w-0">
                    <section aria-label="Key metrics" class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 w-full h-full">
                        <?php foreach ($cards as $c): $a = $accents[$c['accent']]; ?>
                            <!-- DIV: Metric Card -->
                            <div class="bg-gradient-to-br <?= $a['card'] ?> via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:shadow-md transition-all">
                                <!-- DIV: Ambient Background Blur Decorative Shape -->
                                <div class="absolute right-0 bottom-0 w-20 h-20 <?= $a['blob'] ?> rounded-br-2xl rounded-tl-full blur-md pointer-events-none transition-all"></div>

                                <!-- DIV: Card Header Row (Label, Tooltip, Icon Badge) -->
                                <div class="flex items-center justify-between w-full relative z-10">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider <?= $a['label'] ?>"><?= adminEsc($c['tag']) ?></span>
                                        <!-- DIV: Informational Tooltip -->
                                        <div class="relative group/tooltip flex items-center">
                                            <span class="material-symbols-outlined text-[15px] <?= $a['info'] ?> cursor-help transition-colors" aria-hidden="true">info</span>
                                            <div role="tooltip" class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                                <?= adminEsc($c['tip']) ?>
                                                <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-8 h-8 rounded-xl border <?= $a['badge'] ?> flex items-center justify-center flex-shrink-0 shadow-xs">
                                        <span class="material-symbols-outlined text-[18px]" aria-hidden="true"><?= $c['icon'] ?></span>
                                    </div>
                                </div>

                                <!-- DIV: Metric Value + Caption -->
                                <div class="flex flex-col mt-2 relative z-10">
                                    <div class="flex items-end justify-between gap-2">
                                        <span class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface tabular-nums"><?= adminEsc($c['value']) ?></span>
                                        <?php if ($c['chip']): ?>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md whitespace-nowrap <?= $tones[$c['chip']['tone']] ?>"><?= adminEsc($c['chip']['text']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5 truncate"><?= adminEsc($c['caption']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </section>
                </div>

                <!-- DIV: RIGHT COLUMN - Admin Quick Actions Carousel -->
                <div class="lg:col-span-6 flex w-full min-w-0 h-full">
                    <section class="relative w-full h-full rounded-2xl overflow-hidden shadow-xs border border-outline-variant/30 min-h-[280px] bg-gradient-to-br from-inverse-surface via-slate-900 to-inverse-surface text-inverse-on-surface flex flex-col justify-between" id="hero-carousel" aria-label="Admin Quick Actions Carousel">
                        <!-- DIV: Background Ambient Glow Effects -->
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>

                        <!-- DIV: Carousel Slide Frame (driven by app/assets/js/hero-carousel.js) -->
                        <div class="relative w-full h-full min-h-[280px]">
                            <?php foreach ($heroSlides as $i => $s): $first = $i === 0; ?>
                                <!-- DIV: SLIDE <?= $i ?> - <?= adminEsc($s['tag']) ?> -->
                                <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 <?= $first ? 'opacity-100 z-10' : 'opacity-0 pointer-events-none z-0' ?>" data-slide-index="<?= $i ?>" aria-hidden="<?= $first ? 'false' : 'true' ?>">
                                    <div class="relative z-10 flex flex-col gap-2">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg border backdrop-blur-md font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit <?= $s['badge'] ?>">
                                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true"><?= $s['icon'] ?></span>
                                            <span><?= adminEsc($s['tag']) ?></span>
                                        </div>
                                        <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1"><?= adminEsc($s['title']) ?></h2>
                                        <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg"><?= adminEsc($s['text']) ?></p>
                                    </div>
                                    <!-- DIV: Slide Action Button & Wayfinder Dots -->
                                    <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                        <a href="<?= adminEsc($s['href']) ?>" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                            <span><?= adminEsc($s['cta']) ?></span>
                                            <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1" aria-hidden="true">arrow_forward</span>
                                        </a>
                                        <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                            <?php foreach ($heroSlides as $d => $ds): ?>
                                                <button type="button" title="<?= adminEsc($ds['dot']) ?>" aria-label="Slide <?= $d + 1 ?>: <?= adminEsc($ds['dot']) ?>" class="hero-dot h-2 rounded-full transition-all duration-300 <?= $d === $i ? 'bg-cyan-400 w-6' : 'hover:bg-white/70 bg-white/30 w-2' ?>" data-dot-index="<?= $d ?>" aria-current="<?= $d === $i ? 'true' : 'false' ?>"></button>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>
            </div>

            <!-- DIV: ROW 2 - Grid Split: Platform Stat Bar (Left) vs Needs Approval CTA (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">

                <!-- DIV: LEFT COLUMN - Platform Stat Bar -->
                <div class="lg:col-span-6 flex w-full min-w-0 order-last lg:order-none">
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-2 sm:p-2.5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-1 sm:gap-3 w-full h-full">
                        <!-- DIV: Stat 1 - Pending Accounts -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-amber-500/5 transition-all group/stat cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-500 flex-shrink-0 group-hover/stat:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">hourglass_top</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Pending</span>
                                <span class="font-headline-sm text-sm font-bold text-on-surface group-hover/stat:text-amber-600 transition-colors"><?= $fmt($m['pendingUsers']) ?></span>
                            </div>
                        </div>
                        <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>

                        <!-- DIV: Stat 2 - New Users This Week -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-primary/5 transition-all group/stat cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-primary-fixed/60 border border-primary-fixed-dim/40 flex items-center justify-center text-primary flex-shrink-0 group-hover/stat:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">person_add</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">New (7d)</span>
                                <span class="font-headline-sm text-sm font-bold text-on-surface group-hover/stat:text-primary transition-colors">+<?= $fmt($m['newUsers7d']) ?></span>
                            </div>
                        </div>
                        <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>

                        <!-- DIV: Stat 3 - Reviews Last 7 Days -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-teal-500/5 transition-all group/stat cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-500 flex-shrink-0 group-hover/stat:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">check_circle</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Reviews (7d)</span>
                                <span class="flex items-center gap-1.5">
                                    <span class="font-headline-sm text-sm font-bold text-on-surface group-hover/stat:text-teal-600 transition-colors"><?= $fmt($m['reviews7d']) ?></span>
                                    <?php if ($reviewsChip): ?>
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-md whitespace-nowrap <?= $tones[$reviewsChip['tone']] ?>"><?= adminEsc($reviewsChip['text']) ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DIV: RIGHT COLUMN - Needs Approval Call-To-Action -->
                <div class="lg:col-span-6 flex w-full min-w-0 order-first lg:order-none">

                    <?php if ($pendingCount > 0): ?>
                        <!-- A: Accounts waiting - primary call-to-action -->
                        <a href="<?= adminEsc($ctaHref) ?>" id="stat-approval-cta"
                           aria-label="Review <?= $pendingCount ?> pending <?= $pendingCount === 1 ? 'account' : 'accounts' ?>"
                           class="group/cta relative overflow-hidden flex items-center justify-between gap-4 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-on-primary shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                            <div class="absolute -right-6 -top-8 w-28 h-28 bg-white/15 rounded-full blur-2xl pointer-events-none"></div>
                            <div class="relative z-10 flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">how_to_reg</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-primary/80">Needs Approval</span>
                                    <span class="text-base sm:text-lg font-extrabold leading-tight truncate">
                                        <?= $fmt($pendingCount) ?> <?= $pendingCount === 1 ? 'account' : 'accounts' ?> waiting for review
                                    </span>
                                    <?php if ($oldestWait !== ''): ?>
                                        <span class="text-[11px] sm:text-xs text-on-primary/80 truncate">Oldest request <?= adminEsc($oldestWait) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="relative z-10 inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl bg-white text-primary font-label-md text-xs font-bold shadow-xs flex-shrink-0">
                                <span class="hidden sm:inline">Review Queue</span>
                                <span class="material-symbols-outlined text-[18px] transition-transform group-hover/cta:translate-x-1" aria-hidden="true">arrow_forward</span>
                            </span>
                        </a>

                    <?php elseif ($m['available'] && $m['totalUsers'] > 0): ?>
                        <!-- B: Nothing pending -->
                        <div id="stat-approval-cta" class="flex items-center gap-3 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                <span class="material-symbols-outlined text-[22px]" aria-hidden="true">task_alt</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Needs Approval</span>
                                <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface">All caught up</span>
                                <span class="text-[11px] sm:text-xs text-on-surface-variant">No accounts are waiting for review</span>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- C: No accounts yet, or metrics unavailable -->
                        <div id="stat-approval-cta" class="flex items-center gap-3 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                            <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                                <span class="material-symbols-outlined text-[22px]" aria-hidden="true">group_off</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Needs Approval</span>
                                <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface truncate"><?= $m['available'] ? 'No accounts yet' : 'Approval queue unavailable' ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- DIV: MIDDLE SECTION - Platform Activity Chart & Recent Study Sessions -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 w-full items-stretch mt-2">

            <!-- SECTION: Platform Activity Chart (ids and data attributes are used by admin-overview.js) -->
            <section class="lg:col-span-6 <?= $panel ?> flex flex-col min-h-[320px]" aria-labelledby="activity-title">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">monitoring</span>
                            <h2 id="activity-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Platform Activity</h2>
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

                <div class="flex flex-wrap items-center gap-2 mb-3" role="group" aria-label="Toggle chart series">
                    <span class="text-xs font-semibold text-on-surface-variant mr-1">Show:</span>
                    <button type="button" data-series="reviews" aria-pressed="true" class="inline-flex items-center gap-1.5 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-xs font-semibold text-sky-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><span class="w-2 h-2 rounded-sm bg-sky-500" aria-hidden="true"></span>Reviews</button>
                    <button type="button" data-series="active" aria-pressed="true" class="inline-flex items-center gap-1.5 rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1.5 text-xs font-semibold text-teal-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><span class="w-2 h-2 rounded-full bg-teal-500" aria-hidden="true"></span>Active learners</button>
                    <button type="button" data-series="signups" aria-pressed="true" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"><span class="w-2 h-2 rounded-full bg-amber-500" aria-hidden="true"></span>New sign-ups</button>
                </div>

                <div class="relative w-full flex-1 min-h-[220px] min-w-0">
                    <div id="overview-chart-loading" class="absolute inset-0 flex items-center justify-center text-sm text-on-surface-variant">Loading chart…</div>
                    <div id="overview-chart-status" class="hidden absolute inset-0 flex-col items-center justify-center text-center text-on-surface-variant gap-1 p-6 z-10">
                        <span class="material-symbols-outlined text-[32px] text-outline" aria-hidden="true">insert_chart</span>
                        <span id="overview-chart-status-title" class="text-sm font-medium"></span>
                        <span id="overview-chart-status-text" class="text-xs text-on-surface-variant/70"></span>
                    </div>
                    <canvas id="overview-chart" role="img" aria-label="Daily reviews, active learners and sign-ups"></canvas>
                </div>
            </section>

            <!-- SECTION: Recent Study Sessions Feed -->
            <section class="lg:col-span-6 <?= $panel ?> flex flex-col justify-between min-h-[290px]" aria-labelledby="sessions-title">
                <div class="mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">history</span>
                        <h2 id="sessions-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent Study Sessions</h2>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Completed sessions across the platform</p>
                </div>

                <div class="flex flex-col gap-2.5 flex-1 justify-center">
                    <?php if ($recentSessions): foreach ($recentSessions as $s): ?>
                        <!-- DIV: Individual Session Row -->
                        <div class="<?= $row ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">style</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($s['name']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant mt-0.5"><?= (int) $s['cardsReviewed'] ?> cards reviewed</span>
                                </div>
                            </div>
                            <div class="flex flex-col items-end flex-shrink-0 ml-2">
                                <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md"><?= (int) $s['accuracy'] ?>%</span>
                                <span class="text-[10px] text-on-surface-variant/70 mt-1"><?= adminEsc($s['age']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <!-- DIV: Empty State -->
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant h-full">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">history_edu</span>
                            <span class="text-sm font-medium">No completed sessions yet.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">Sessions appear here once learners finish studying.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <!-- DIV: BOTTOM SECTION - Approval Queue & Recent Sign-ups -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 w-full items-stretch">

            <!-- SECTION: Needs Approval Queue -->
            <section class="lg:col-span-6 <?= $panel ?> flex flex-col min-h-[290px]" aria-labelledby="pending-title">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-600 text-[22px]" aria-hidden="true">how_to_reg</span>
                            <h2 id="pending-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Needs Approval</h2>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Longest-waiting first</p>
                    </div>
                    <?php if ($pendingCount > 0): ?>
                        <a href="<?= adminEsc($ctaHref) ?>" class="text-xs text-primary hover:underline font-semibold flex items-center gap-1 transition-all">
                            <span>View All</span>
                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">arrow_forward</span>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-2.5 flex-1 justify-center">
                    <?php if ($pendingUsers): foreach ($pendingUsers as $u): ?>
                        <div class="<?= $row ?>">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-amber-50 text-amber-700 flex items-center justify-center flex-shrink-0 text-xs font-bold"><?= adminEsc($initial($u['name'])) ?></div>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($u['name']) ?></span>
                                    <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($u['email']) ?></span>
                                </div>
                            </div>
                            <span class="text-[10px] text-on-surface-variant/70 whitespace-nowrap flex-shrink-0"><?= adminEsc($u['age']) ?></span>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant h-full">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">task_alt</span>
                            <span class="text-sm font-medium">You're all caught up.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">New sign-ups awaiting approval will appear here.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- SECTION: Recent Sign-ups -->
            <section class="lg:col-span-6 <?= $panel ?> flex flex-col min-h-[290px]" aria-labelledby="signups-title">
                <div class="mb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[22px]" aria-hidden="true">person_add</span>
                        <h2 id="signups-title" class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent Sign-ups</h2>
                    </div>
                    <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Newest accounts, any status</p>
                </div>

                <div class="flex flex-col gap-2.5 flex-1 justify-center">
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
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant h-full">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2" aria-hidden="true">group_off</span>
                            <span class="text-sm font-medium">No accounts yet.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>

    </div>
</div>