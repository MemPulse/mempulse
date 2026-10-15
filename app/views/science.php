<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$user_id = $_SESSION['user_id'];

// The scheduler animation uses the learner's own multipliers when available (user_settings table).
// Falls back to the schema defaults if the row or connection is unavailable.
$srs = [
    'remember_multiplier'   => 2.00,
    'forgot_multiplier'     => 0.50,
    'initial_interval_days' => 1,
    'maximum_interval_days' => 365,
];

try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->prepare(
            'SELECT remember_multiplier, forgot_multiplier, initial_interval_days, maximum_interval_days
             FROM user_settings WHERE user_id = :uid LIMIT 1'
        );
        $stmt->execute([':uid' => $user_id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $srs = [
                'remember_multiplier'   => (float) $row['remember_multiplier'],
                'forgot_multiplier'     => (float) $row['forgot_multiplier'],
                'initial_interval_days' => (int) $row['initial_interval_days'],
                'maximum_interval_days' => (int) $row['maximum_interval_days'],
            ];
        }
    }
} catch (Throwable $e) {
    // Keep defaults; the science page must never fail because of settings.
}

$pageTitle = "Science";
$currentPage = "science";
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

    <body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">

            <div class="w-full px-3 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
                <div class="flex flex-col gap-10 sm:gap-14 max-w-5xl mx-auto">

                    <!-- ========================= HERO ========================= -->
                    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white p-6 sm:p-12">
                        <div class="absolute -right-10 -top-10 w-40 h-40 rounded-full bg-white/20 blur-3xl" aria-hidden="true"></div>
                        <div class="absolute -left-10 -bottom-10 w-40 h-40 rounded-full bg-white/20 blur-3xl" aria-hidden="true"></div>
                        <div class="relative flex flex-col gap-4 max-w-2xl">
                            <span class="inline-flex items-center gap-1.5 w-fit bg-white/20 rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-wider">
                                <span class="material-symbols-outlined text-[14px]">science</span>
                                Science &middot; 4 min read
                            </span>
                            <h1 class="font-headline-lg text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                                How MemPulse Helps You Remember
                            </h1>
                            <p class="text-lg text-white/80 leading-relaxed">
                                Why your brain forgets things, and how reviewing at just the right time makes them stick.
                            </p>
                            <div class="flex flex-wrap gap-3 pt-2">
                                <a href="#engine" class="inline-flex items-center gap-2 bg-white text-indigo-700 px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-white/70 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">play_circle</span>
                                    Try the scheduler
                                </a>
                                <a href="decks.php" class="inline-flex items-center gap-2 bg-white/20 text-white px-4 py-2.5 rounded-xl font-semibold text-sm hover:bg-white/30 transition-colors">
                                    <span class="material-symbols-outlined text-[18px]">style</span>
                                    Start reviewing
                                </a>
                            </div>
                        </div>
                    </header>

                    <!-- ================= THE SHORT VERSION ================= -->
                    <section class="flex flex-col gap-4" aria-labelledby="short-version">
                        <h2 id="short-version" class="font-headline-lg text-2xl font-extrabold tracking-tight">The short version</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 shadow-sm flex flex-col gap-3">
                                <span class="w-10 h-10 rounded-xl bg-error/10 text-error flex items-center justify-center"><span class="material-symbols-outlined text-[22px]">trending_down</span></span>
                                <h3 class="font-bold">You forget fast</h3>
                                <p class="text-sm text-on-surface-variant leading-relaxed">Without a review, a lot of what you learn fades within a few days.</p>
                            </div>
                            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 shadow-sm flex flex-col gap-3">
                                <span class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center"><span class="material-symbols-outlined text-[22px]">replay</span></span>
                                <h3 class="font-bold">Reviewing resets the clock</h3>
                                <p class="text-sm text-on-surface-variant leading-relaxed">A review right before you forget makes the memory last longer.</p>
                            </div>
                            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 shadow-sm flex flex-col gap-3">
                                <span class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-600 flex items-center justify-center"><span class="material-symbols-outlined text-[22px]">event_repeat</span></span>
                                <h3 class="font-bold">MemPulse picks the day</h3>
                                <p class="text-sm text-on-surface-variant leading-relaxed">It chooses the best day to show each card again, so you don't have to.</p>
                            </div>
                        </div>
                    </section>

                    <!-- ================= 01 FORGETTING CURVE ================= -->
                    <section class="flex flex-col gap-4" aria-labelledby="forgetting">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-primary mb-1">01 &middot; Why we forget</p>
                            <h2 id="forgetting" class="font-headline-lg text-2xl font-extrabold tracking-tight">The forgetting curve</h2>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                            <div class="lg:col-span-5 flex flex-col gap-3 text-on-surface-variant leading-relaxed">
                                <p>
                                    In the 1880s, a German psychologist named Hermann Ebbinghaus tested his own memory. He learned lists of made-up words,
                                    then checked how many he still knew later.
                                </p>
                                <p>
                                    He found that we forget <strong class="text-on-surface">a lot at first, then more slowly</strong>. Scientists call this the
                                    <strong class="text-on-surface">forgetting curve</strong>.
                                </p>
                                <p>
                                    Here is the good news. If you review something just as it starts to fade, the memory resets, and the next fade is
                                    slower. Do that a few times and the memory can last for weeks.
                                </p>
                            </div>

                            <figure class="lg:col-span-7 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm">
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mb-2 text-xs text-on-surface-variant">
                                    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-6 h-1 rounded" style="background: rgb(var(--c-error));"></span>No review</span>
                                    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-6 h-1 rounded" style="background: rgb(var(--c-primary));"></span>With spaced reviews</span>
                                </div>
                                <svg id="curve-chart" viewBox="0 0 640 260" class="w-full h-auto" role="img"
                                    aria-label="Chart of memory over 60 days. Without review, memory falls close to zero. With spaced reviews, memory is topped back up at each review and fades more slowly each time."></svg>
                                <figcaption class="text-xs text-on-surface-variant/80 mt-2 leading-snug">
                                    An example, not your own data. In this model, each review makes the memory last twice as long.
                                </figcaption>
                            </figure>
                        </div>
                    </section>

                    <!-- ================= 02 SPACING VS CRAMMING ================= -->
                    <section class="flex flex-col gap-4" aria-labelledby="spacing">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-primary mb-1">02 &middot; The method</p>
                            <h2 id="spacing" class="font-headline-lg text-2xl font-extrabold tracking-tight">Spacing beats cramming</h2>
                        </div>

                        <p class="text-on-surface-variant leading-relaxed max-w-3xl">
                            <strong class="text-on-surface">Spaced repetition</strong> means reviewing something again after longer and longer gaps:
                            today, tomorrow, in a few days, then weeks. Pair it with <strong class="text-on-surface">active recall</strong>, which means
                            trying to answer from memory <em>before</em> you look at the answer, and you have one of the best ways to learn facts, terms and formulas.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-error/30 bg-error/5 p-5">
                                <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-error">bedtime</span>Cramming</h3>
                                <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">
                                    Study everything the night before. It can get you through tomorrow's quiz, but much of it is gone a week later.
                                </p>
                            </div>
                            <div class="rounded-2xl border border-teal-500/30 bg-teal-500/5 p-5">
                                <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-teal-600">event_repeat</span>Spacing</h3>
                                <p class="text-sm text-on-surface-variant mt-2 leading-relaxed">
                                    Study a little at a time, with gaps in between. Research shows this works better when you need to remember something
                                    for weeks or months (Cepeda et al., 2006).
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div class="flex flex-col gap-2 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                                <span class="material-symbols-outlined text-primary text-[24px]">psychology</span>
                                <h3 class="font-bold">Remember for longer</h3>
                                <p class="text-sm text-on-surface-variant leading-relaxed">Key terms and formulas stay with you for weeks, not just the next day.</p>
                            </div>
                            <div class="flex flex-col gap-2 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                                <span class="material-symbols-outlined text-primary text-[24px]">timer</span>
                                <h3 class="font-bold">Short sessions</h3>
                                <p class="text-sm text-on-surface-variant leading-relaxed">A few minutes a day replaces the all-night study marathon.</p>
                            </div>
                            <div class="flex flex-col gap-2 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                                <span class="material-symbols-outlined text-primary text-[24px]">filter_alt</span>
                                <h3 class="font-bold">Focus on what's hard</h3>
                                <p class="text-sm text-on-surface-variant leading-relaxed">Cards you struggle with come back sooner. Cards you know move further out.</p>
                            </div>
                            <div class="flex flex-col gap-2 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                                <span class="material-symbols-outlined text-primary text-[24px]">spa</span>
                                <h3 class="font-bold">Less stress</h3>
                                <p class="text-sm text-on-surface-variant leading-relaxed">You know every topic has been covered, so you walk into the exam calmer.</p>
                            </div>
                        </div>
                    </section>

                    <!-- ================= 03 TIPS ================= -->
                    <section class="flex flex-col gap-4" aria-labelledby="tips">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-primary mb-1">03 &middot; Getting results</p>
                            <h2 id="tips" class="font-headline-lg text-2xl font-extrabold tracking-tight">4 habits that make it work</h2>
                        </div>

                        <div class="flex gap-3 bg-primary/10 rounded-2xl p-4">
                            <span class="material-symbols-outlined text-primary text-[24px] shrink-0">calendar_month</span>
                            <p class="text-on-surface-variant leading-relaxed">
                                <strong class="text-on-surface">Start early.</strong> The best time to begin is 8 to 12 weeks before your exam, so your cards have time to build up longer gaps.
                                Starting later still helps, just with fewer long gaps.
                            </p>
                        </div>

                        <ol class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <li class="flex gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                                <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-sm font-bold flex items-center justify-center shrink-0" aria-hidden="true">1</span>
                                <p class="text-on-surface-variant leading-relaxed"><strong class="text-on-surface">Make small cards.</strong> One fact per card, like &ldquo;Which enzyme breaks down starch in the mouth?&rdquo; instead of &ldquo;Describe digestion.&rdquo;</p>
                            </li>
                            <li class="flex gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                                <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-sm font-bold flex items-center justify-center shrink-0" aria-hidden="true">2</span>
                                <p class="text-on-surface-variant leading-relaxed"><strong class="text-on-surface">Answer before you flip.</strong> Say or write your answer first. If it feels a little hard, that's a good sign: effort makes memories stronger.</p>
                            </li>
                            <li class="flex gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                                <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-sm font-bold flex items-center justify-center shrink-0" aria-hidden="true">3</span>
                                <p class="text-on-surface-variant leading-relaxed"><strong class="text-on-surface">Be honest.</strong> If you hesitated or guessed, choose Forgot. Honest answers keep your schedule accurate.</p>
                            </li>
                            <li class="flex gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                                <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-sm font-bold flex items-center justify-center shrink-0" aria-hidden="true">4</span>
                                <p class="text-on-surface-variant leading-relaxed"><strong class="text-on-surface">Review what's due, every day.</strong> Ten to twenty minutes is enough. Skipped days let memories fade more than planned.</p>
                            </li>
                        </ol>
                    </section>

                    <!-- ================= 04 ENGINE ================= -->
                    <section id="engine" class="flex flex-col gap-4 scroll-mt-6" aria-labelledby="engine-title">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-primary mb-1">04 &middot; Under the hood</p>
                            <h2 id="engine-title" class="font-headline-lg text-2xl font-extrabold tracking-tight">How MemPulse picks the next review day</h2>
                        </div>

                        <p class="text-on-surface-variant leading-relaxed max-w-3xl">
                            Every card has one number called its <strong class="text-on-surface">interval</strong>. That's how many days MemPulse waits before showing the card again.
                            After you answer, MemPulse multiplies the interval:
                        </p>

                        <p class="text-lg sm:text-xl font-bold text-center py-4 px-4 rounded-2xl bg-surface-container-low">
                            Next wait = Current wait &times; Multiplier
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-teal-500/30 bg-teal-500/5 p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-teal-600">check_circle</span>You remembered</h3>
                                    <span class="font-bold text-teal-700 tabular-nums">&times;<span data-mult="remember">2</span></span>
                                </div>
                                <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">The wait gets longer. With your settings, a 4-day wait becomes <span class="font-semibold text-on-surface" data-example="remember">8 days</span>.</p>
                            </div>
                            <div class="rounded-2xl border border-error/30 bg-error/5 p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-error">cancel</span>You forgot</h3>
                                    <span class="font-bold text-error tabular-nums">&times;<span data-mult="forgot">0.5</span></span>
                                </div>
                                <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">The wait gets shorter. With your settings, a 4-day wait becomes <span class="font-semibold text-on-surface" data-example="forgot">2 days</span>.</p>
                            </div>
                        </div>

                        <!-- ============ Scheduler sandbox ============ -->
                        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 sm:p-6 shadow-md flex flex-col gap-5">
                            <div>
                                <h3 class="font-headline-lg text-xl font-extrabold tracking-tight">Try it yourself</h3>
                                <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">
                                    Pretend you just added a new card. Press a button each time you review it and watch the gaps change.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label for="remember-multiplier-input" class="text-sm font-semibold text-on-surface">Remember multiplier</label>
                                    <div class="flex items-center bg-surface-container-low px-3 py-2 rounded-xl">
                                        <input id="remember-multiplier-input" type="number" min="1.1" max="10" step="0.1"
                                            value="<?php echo htmlspecialchars((string) $srs['remember_multiplier'], ENT_QUOTES); ?>"
                                            class="w-full bg-transparent border-0 p-0 text-headline-sm text-on-surface focus:outline-none" style="box-shadow:none;">
                                        <span class="text-on-surface-variant font-medium">&times;</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="forgot-multiplier-input" class="text-sm font-semibold text-on-surface">Forgot multiplier</label>
                                    <div class="flex items-center bg-surface-container-low px-3 py-2 rounded-xl">
                                        <input id="forgot-multiplier-input" type="number" min="0.1" max="1" step="0.1"
                                            value="<?php echo htmlspecialchars((string) $srs['forgot_multiplier'], ENT_QUOTES); ?>"
                                            class="w-full bg-transparent border-0 p-0 text-headline-sm text-on-surface focus:outline-none" style="box-shadow:none;">
                                        <span class="text-on-surface-variant font-medium">&times;</span>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label for="max-interval-input" class="text-sm font-semibold text-on-surface">Longest wait (days)</label>
                                    <div class="flex items-center bg-surface-container-low px-3 py-2 rounded-xl">
                                        <input id="max-interval-input" type="number" min="1" max="3650" step="1"
                                            value="<?php echo (int) $srs['maximum_interval_days']; ?>"
                                            class="w-full bg-transparent border-0 p-0 text-headline-sm text-on-surface focus:outline-none" style="box-shadow:none;">
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl bg-surface-container-low p-4 sm:p-5 flex flex-col gap-4">
                                <div id="timeline-container" class="flex items-center gap-2 overflow-x-auto pb-2 min-h-[56px]" aria-label="Review timeline"></div>

                                <div class="grid grid-cols-3 gap-3" aria-live="polite">
                                    <div class="rounded-xl bg-surface-container-lowest p-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Reviews so far</p>
                                        <p id="sb-reviews" class="text-xl font-bold tabular-nums mt-1">0</p>
                                    </div>
                                    <div class="rounded-xl bg-surface-container-lowest p-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">Next review in</p>
                                        <p id="sb-next" class="text-xl font-bold tabular-nums mt-1">1 day</p>
                                    </div>
                                    <div class="rounded-xl bg-surface-container-lowest p-3">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant">That's on day</p>
                                        <p id="sb-day" class="text-xl font-bold tabular-nums mt-1">1</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" id="btn-remember" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-teal-500 text-white font-semibold text-sm hover:opacity-90 transition-all active:scale-[0.97]">
                                        <span class="material-symbols-outlined text-[18px]">check</span>I remembered
                                    </button>
                                    <button type="button" id="btn-forget" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-rose-600 text-white font-semibold text-sm hover:bg-rose-700 transition-all active:scale-[0.97]">
                                        <span class="material-symbols-outlined text-[18px]">close</span>I forgot
                                    </button>
                                    <button type="button" id="btn-reset" class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/40 text-on-surface-variant font-semibold text-sm hover:bg-surface-container transition-all active:scale-[0.97]">
                                        <span class="material-symbols-outlined text-[18px]">restart_alt</span>Reset
                                    </button>
                                </div>
                            </div>

                            <p class="text-xs text-on-surface-variant/80">
                                This is a practice area. Changing the numbers here does not change your saved settings.
                            </p>
                        </div>

                        <p class="text-on-surface-variant leading-relaxed max-w-3xl">
                            You can change the real multipliers, the starting wait and the longest wait in
                            <a href="settings.php" class="text-primary font-semibold underline underline-offset-2">Settings</a>.
                            Tip: lower the longest wait before an exam so no card is scheduled after the exam date.
                        </p>
                    </section>

                    <!-- ================= CTA + REFERENCES ================= -->
                    <footer class="flex flex-col gap-5 border-t border-outline-variant/30 pt-6">
                        <div class="flex flex-wrap gap-3">
                            <a href="decks.php" class="inline-flex items-center gap-2 bg-primary text-on-primary px-4 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition-opacity">
                                <span class="material-symbols-outlined text-[18px]">style</span>
                                Start reviewing
                            </a>
                        </div>
                        <p class="text-xs text-on-surface-variant/80 leading-relaxed">
                            Further reading: Ebbinghaus, H. (1885), <em>Memory: A Contribution to Experimental Psychology</em>.
                            Cepeda, N. J., et al. (2006), Distributed practice in verbal recall tasks, <em>Psychological Bulletin, 132</em>(3).
                            Roediger, H. L., &amp; Karpicke, J. D. (2006), Test-enhanced learning, <em>Psychological Science, 17</em>(3).
                            Karpicke, J. D., &amp; Roediger, H. L. (2008), The critical importance of retrieval practice for learning, <em>Science, 319</em>(5865).
                        </p>
                    </footer>

                </div>
            </div>

        </main>

        <script>
        (function () {
            'use strict';

            var SAVED = <?php echo json_encode($srs, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;

            var $ = function (id) { return document.getElementById(id); };
            var clamp = function (n, lo, hi) { return Math.min(hi, Math.max(lo, n)); };
            var fmt = function (n) { return (Math.round(n * 100) / 100).toString(); };
            var days = function (n) { return n + (n === 1 ? ' day' : ' days'); };

            // Show the learner's saved multipliers and a worked example in the Remember / Forgot cards.
            document.querySelectorAll('[data-mult="remember"]').forEach(function (el) { el.textContent = fmt(SAVED.remember_multiplier); });
            document.querySelectorAll('[data-mult="forgot"]').forEach(function (el) { el.textContent = fmt(SAVED.forgot_multiplier); });
            document.querySelectorAll('[data-example]').forEach(function (el) {
                var m = el.getAttribute('data-example') === 'remember' ? SAVED.remember_multiplier : SAVED.forgot_multiplier;
                el.textContent = days(Math.max(1, Math.round(4 * m)));
            });

            /* ---------- Forgetting curve (illustrative) ---------- */
            (function drawCurve() {
                var svg = $('curve-chart');
                if (!svg) { return; }
                var W = 640, H = 260, L = 44, R = 12, T = 12, B = 36;
                var span = 60, cw = W - L - R, ch = H - T - B;
                var x = function (d) { return L + (d / span) * cw; };
                var y = function (p) { return T + (1 - p) * ch; };
                var label = 'style="fill: rgb(var(--c-on-surface-variant))" font-size="11"';
                var out = '';

                [0, 0.5, 1].forEach(function (p) {
                    out += '<line x1="' + L + '" x2="' + (W - R) + '" y1="' + y(p) + '" y2="' + y(p) + '" style="stroke: rgb(var(--c-outline-variant)); opacity:.45"/>';
                    out += '<text x="' + (L - 8) + '" y="' + (y(p) + 4) + '" text-anchor="end" ' + label + '>' + Math.round(p * 100) + '%</text>';
                });
                [0, 15, 30, 45, 60].forEach(function (d) {
                    out += '<text x="' + x(d) + '" y="' + (H - 16) + '" text-anchor="middle" ' + label + '>' + d + '</text>';
                });
                out += '<text x="' + (L + cw / 2) + '" y="' + (H - 2) + '" text-anchor="middle" ' + label + '>Days since learning</text>';

                var base = 3, reviews = [0, 1, 3, 7, 15, 31], t, i, s;
                var d0 = '';
                for (t = 0; t <= span; t += 0.5) { d0 += (t === 0 ? 'M' : 'L') + x(t).toFixed(1) + ' ' + y(Math.exp(-t / base)).toFixed(1) + ' '; }
                out += '<path d="' + d0 + '" fill="none" stroke-width="2.5" stroke-linecap="round" style="stroke: rgb(var(--c-error))"/>';

                var d1 = '';
                for (i = 0; i < reviews.length; i++) {
                    var start = reviews[i], end = i + 1 < reviews.length ? reviews[i + 1] : span;
                    var strength = base * Math.pow(2, i);
                    for (s = start; s <= end + 0.001; s += 0.25) {
                        d1 += (s === start ? 'M' : 'L') + x(s).toFixed(1) + ' ' + y(Math.exp(-(s - start) / strength)).toFixed(1) + ' ';
                    }
                }
                out += '<path d="' + d1 + '" fill="none" stroke-width="2.5" stroke-linejoin="round" style="stroke: rgb(var(--c-primary))"/>';
                reviews.slice(1).forEach(function (rd) { out += '<circle cx="' + x(rd) + '" cy="' + y(1) + '" r="4" style="fill: rgb(var(--c-primary))"/>'; });
                svg.innerHTML = out;
            })();

            /* ---------- Scheduler sandbox ---------- */
            (function sandbox() {
                var timeline = $('timeline-container');
                if (!timeline) { return; }

                var rememberInput = $('remember-multiplier-input');
                var forgotInput = $('forgot-multiplier-input');
                var maxInput = $('max-interval-input');

                var maxSaved = SAVED.maximum_interval_days > 0 ? SAVED.maximum_interval_days : 365;
                var startWait = Math.max(1, SAVED.initial_interval_days || 1);

                // results[i] is how review i went; waits[i] is the gap before review i.
                var results = [];
                var waits = [startWait];

                function num(el, fallback, lo, hi) {
                    var n = parseFloat(el.value);
                    if (isNaN(n)) { n = fallback; }
                    return clamp(n, lo, hi);
                }

                function nextWait(current, type) {
                    var mult = type === 'remember'
                        ? num(rememberInput, SAVED.remember_multiplier, 1.1, 10)
                        : num(forgotInput, SAVED.forgot_multiplier, 0.1, 1);
                    var max = num(maxInput, maxSaved, 1, 3650);
                    return clamp(Math.round(current * mult), 1, max);
                }

                var box = 'shrink-0 w-8 h-8 rounded-lg flex items-center justify-center ';
                function node(kind) {
                    if (kind === 'added') {
                        return '<div class="' + box + 'bg-surface-container-lowest shadow-sm text-primary" title="Card added"><span class="material-symbols-outlined text-[18px]">add</span></div>';
                    }
                    if (kind === 'remember') {
                        return '<div class="' + box + 'bg-surface-container-lowest shadow-sm text-teal-600" title="Remembered"><span class="material-symbols-outlined text-[18px]">check</span></div>';
                    }
                    if (kind === 'forget') {
                        return '<div class="' + box + 'bg-surface-container-lowest shadow-sm text-error" title="Forgot"><span class="material-symbols-outlined text-[18px]">close</span></div>';
                    }
                    return '<div class="' + box + 'bg-surface-container-high border border-dashed border-outline-variant" title="Next review"></div>';
                }
                function arrow(n) {
                    return '<div class="shrink-0 flex items-center gap-1 px-1 text-on-surface-variant">' +
                        '<span class="h-px w-6 bg-outline-variant"></span>' +
                        '<span class="text-[12px] font-medium whitespace-nowrap">' + days(n) + '</span>' +
                        '<span class="material-symbols-outlined text-[14px]">arrow_forward</span></div>';
                }

                function render() {
                    var html = node('added'), i, elapsed = 0;
                    for (i = 0; i < results.length; i++) {
                        html += arrow(waits[i]) + node(results[i]);
                        elapsed += waits[i];
                    }
                    var upcoming = waits[results.length];
                    html += arrow(upcoming) + node('pending');
                    timeline.innerHTML = html;
                    timeline.scrollLeft = timeline.scrollWidth;

                    $('sb-reviews').textContent = results.length;
                    $('sb-next').textContent = days(upcoming);
                    $('sb-day').textContent = elapsed + upcoming;
                }

                function press(type) {
                    var current = waits[waits.length - 1];
                    results.push(type);
                    waits.push(nextWait(current, type));
                    render();
                }

                $('btn-remember').addEventListener('click', function () { press('remember'); });
                $('btn-forget').addEventListener('click', function () { press('forget'); });
                $('btn-reset').addEventListener('click', function () {
                    results = [];
                    waits = [startWait];
                    rememberInput.value = fmt(SAVED.remember_multiplier);
                    forgotInput.value = fmt(SAVED.forgot_multiplier);
                    maxInput.value = maxSaved;
                    render();
                });

                render();
            })();
        })();
        </script>

    </body>

</html>