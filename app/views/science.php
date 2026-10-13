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
            <article class="flex flex-col gap-10 sm:gap-14 max-w-3xl mx-auto">

                <!-- ========================= TITLE ========================= -->
                <header class="flex flex-col gap-3">
                    <h1 class="font-headline-lg text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight">
                        MemPulse Spaced Repetition
                    </h1>
                    <p class="text-lg text-on-surface-variant leading-relaxed">
                        How to remember more while studying less, and how MemPulse schedules it for you.
                    </p>
                    <p class="text-sm text-on-surface-variant/80">3 min read</p>
                </header>

                <!-- ================= WHAT IS IT ================= -->
                <section class="flex flex-col gap-3">
                    <h2 class="font-headline-lg text-2xl font-extrabold tracking-tight">What is MemPulse?</h2>
                    <p class="text-on-surface-variant leading-relaxed">
                        MemPulse is a flashcard app that decides <strong class="text-on-surface">when</strong> you should review each card.
                        You answer from memory, tell MemPulse whether you remembered, and it brings the card back at the right time.
                    </p>

                    <h3 class="font-bold text-lg mt-3">What is spaced repetition?</h3>
                    <p class="text-on-surface-variant leading-relaxed">
                        Spaced repetition means reviewing something at gradually longer gaps: today, tomorrow, in a few days, then weeks.
                        Each time you recall it successfully, the next gap gets longer. Pair that with
                        <strong class="text-on-surface">active recall</strong> (answering from memory before you look at the answer)
                        and you get the most effective way to learn facts, terms and formulas.
                    </p>
                </section>

                <!-- ================= FORGETTING CURVE ================= -->
                <section class="flex flex-col gap-4">
                    <h2 class="font-headline-lg text-2xl font-extrabold tracking-tight">The forgetting curve</h2>
                    <p class="text-on-surface-variant leading-relaxed">
                        In the 1880s, psychologist Hermann Ebbinghaus tested his own memory and found that we forget quickly at first, then more slowly.
                        This is the forgetting curve. Reviewing a memory just as it fades resets the curve, and the memory lasts longer than before.
                    </p>

                    <figure class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5">
                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mb-2 text-xs text-on-surface-variant">
                            <span class="inline-flex items-center gap-1.5"><span class="inline-block w-5 h-0.5 rounded" style="background: rgb(var(--c-error));"></span>No review</span>
                            <span class="inline-flex items-center gap-1.5"><span class="inline-block w-5 h-0.5 rounded" style="background: rgb(var(--c-primary));"></span>With spaced reviews</span>
                        </div>
                        <svg id="curve-chart" viewBox="0 0 640 260" class="w-full h-auto" role="img"
                             aria-label="Chart of memory over 60 days. Without review, memory falls close to zero. With spaced reviews, memory is topped back up at each review and fades more slowly each time."></svg>
                        <figcaption class="text-xs text-on-surface-variant/80 mt-2 leading-snug">
                            Illustrative model, not your personal data. Each review doubles how long the memory lasts.
                        </figcaption>
                    </figure>
                </section>

                <!-- ================= WHY BETTER THAN CRAMMING ================= -->
                <section class="flex flex-col gap-4">
                    <h2 class="font-headline-lg text-2xl font-extrabold tracking-tight">Why it beats cramming</h2>
                    <p class="text-on-surface-variant leading-relaxed">
                        Cramming can get you through tomorrow's quiz, but most of it is gone a week later. Spreading study over time
                        works better when you need to remember for weeks or months (Cepeda et al., 2006). Here is what you gain:
                    </p>

                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <li class="flex gap-3 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4">
                            <span class="material-symbols-outlined text-primary text-[24px] shrink-0">psychology</span>
                            <div>
                                <h3 class="font-bold">Better long-term retention</h3>
                                <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Key terms and formulas stay with you for weeks, not just the day after.</p>
                            </div>
                        </li>
                        <li class="flex gap-3 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4">
                            <span class="material-symbols-outlined text-primary text-[24px] shrink-0">timer</span>
                            <div>
                                <h3 class="font-bold">Less total study time</h3>
                                <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Short daily sessions replace long late-night marathons.</p>
                            </div>
                        </li>
                        <li class="flex gap-3 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4">
                            <span class="material-symbols-outlined text-primary text-[24px] shrink-0">filter_alt</span>
                            <div>
                                <h3 class="font-bold">Automatic prioritisation</h3>
                                <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">Cards you struggle with come back sooner. Cards you know move further out.</p>
                            </div>
                        </li>
                        <li class="flex gap-3 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4">
                            <span class="material-symbols-outlined text-primary text-[24px] shrink-0">spa</span>
                            <div>
                                <h3 class="font-bold">Reduced exam anxiety</h3>
                                <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">You know every topic has been covered, so you walk in with confidence.</p>
                            </div>
                        </li>
                    </ul>
                </section>

                <!-- ================= HOW TO USE ================= -->
                <section class="flex flex-col gap-4">
                    <h2 class="font-headline-lg text-2xl font-extrabold tracking-tight">How to use spaced repetition</h2>
                    <p class="text-on-surface-variant leading-relaxed">
                        The best time to start is <strong class="text-on-surface">8 to 12 weeks before your exam</strong>. That gives your
                        cards time to build up longer and longer gaps. Starting later still helps, but you will see fewer long intervals.
                    </p>

                    <ol class="flex flex-col gap-3">
                        <li class="flex gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4">
                            <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-sm font-bold flex items-center justify-center shrink-0" aria-hidden="true">1</span>
                            <p class="text-on-surface-variant leading-relaxed"><strong class="text-on-surface">Make small cards.</strong> One fact per card, such as &ldquo;Which enzyme breaks down starch in the mouth?&rdquo; rather than &ldquo;Describe digestion.&rdquo;</p>
                        </li>
                        <li class="flex gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4">
                            <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-sm font-bold flex items-center justify-center shrink-0" aria-hidden="true">2</span>
                            <p class="text-on-surface-variant leading-relaxed"><strong class="text-on-surface">Answer before you reveal.</strong> Say or write your answer first. Recognising it afterwards doesn't count as recall.</p>
                        </li>
                        <li class="flex gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4">
                            <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-sm font-bold flex items-center justify-center shrink-0" aria-hidden="true">3</span>
                            <p class="text-on-surface-variant leading-relaxed"><strong class="text-on-surface">Grade honestly.</strong> If you hesitated or guessed, choose Forgot. An honest grade keeps the schedule accurate.</p>
                        </li>
                        <li class="flex gap-4 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4">
                            <span class="w-7 h-7 rounded-full bg-primary text-on-primary text-sm font-bold flex items-center justify-center shrink-0" aria-hidden="true">4</span>
                            <p class="text-on-surface-variant leading-relaxed"><strong class="text-on-surface">Review what's due, every day.</strong> Ten to twenty minutes is enough. Skipped reviews let memories fade further than planned.</p>
                        </li>
                    </ol>
                </section>

                <!-- ================= ENGINE ================= -->
                <section id="engine" class="flex flex-col gap-4 scroll-mt-6">
                    <h2 class="font-headline-lg text-2xl font-extrabold tracking-tight">The MemPulse engine</h2>
                    <p class="text-on-surface-variant leading-relaxed">
                        Every card has one number: its <strong class="text-on-surface">interval</strong>, the days until you see it again.
                        After you grade a card, MemPulse multiplies that interval.
                    </p>

                    <p class="text-lg sm:text-xl font-bold text-center py-4 px-4 rounded-2xl bg-surface-container-low">
                        Next interval = Current interval &times; Multiplier
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-teal-500/30 bg-teal-500/5 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-teal-600">check_circle</span>Remember</h3>
                                <span class="font-bold text-teal-700 dark:text-teal-300 tabular-nums">&times;<span data-mult="remember">2.0</span></span>
                            </div>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">The wait grows. At the default, 4 days becomes 8.</p>
                        </div>
                        <div class="rounded-2xl border border-error/30 bg-error/5 p-4">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-[20px] text-error">cancel</span>Forgot</h3>
                                <span class="font-bold text-error tabular-nums">&times;<span data-mult="forgot">0.5</span></span>
                            </div>
                            <p class="text-sm text-on-surface-variant mt-1 leading-relaxed">The wait shrinks. At the default, 4 days becomes 2.</p>
                        </div>
                    </div>

                    <!-- Interactive scheduler preview (shared partial: app/includes/srs-preview.php) -->
                    <div class="flex flex-col gap-3">
                        <h3 class="font-bold">The scheduler in action</h3>
                        <?php
                        $srsPreview = [
                            'remember' => $srs['remember_multiplier'],
                            'forgot'   => $srs['forgot_multiplier'],
                            'initial'  => $srs['initial_interval_days'],
                            'max'      => $srs['maximum_interval_days'],
                        ];
                        include __DIR__ . '/../includes/srs-preview.php';
                        ?>
                        <p class="text-xs text-on-surface-variant/80">This is a sandbox. Changing the numbers here does not change your saved settings.</p>
                    </div>

                    <p class="text-on-surface-variant leading-relaxed">
                        The multipliers are yours to change. Open <a href="settings.php" class="text-primary font-semibold underline underline-offset-2">Settings</a>
                        to adjust the Remember and Forgot multipliers, the starting interval, and the maximum interval, to suit your pace and subject.
                        Lower the maximum before an exam so no card is scheduled after the date.
                    </p>
                </section>

                <!-- ================= CTA + REFERENCES ================= -->
                <footer class="flex flex-col gap-5 border-t border-outline-variant/30 pt-6">
                    <div class="flex flex-wrap gap-3">
                        <a href="decks.php" class="inline-flex items-center gap-2 bg-primary text-on-primary px-4 py-2.5 rounded-xl font-semibold text-sm hover:opacity-90 transition-opacity focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">
                            <span class="material-symbols-outlined text-[18px]">style</span>
                            Start reviewing
                        </a>
                    </div>
                    <p class="text-xs text-on-surface-variant/80 leading-relaxed">
                        Further reading: Ebbinghaus, H. (1885), <em>Memory: A Contribution to Experimental Psychology</em>.
                        Cepeda, N. J., et al. (2006), Distributed practice in verbal recall tasks, <em>Psychological Bulletin, 132</em>(3).
                        Roediger, H. L., &amp; Karpicke, J. D. (2006), Test-enhanced learning, <em>Psychological Science, 17</em>(3).
                    </p>
                </footer>

            </article>
        </div>

    </main>

    <script>
    (function () {
        'use strict';

        var SAVED = <?php echo json_encode($srs, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;

        var $ = function (id) { return document.getElementById(id); };
        var clamp = function (n, lo, hi) { return Math.min(hi, Math.max(lo, n)); };
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // Show the learner's multipliers in the Remember / Forgot cards.
        var fmt = function (n) { return (Math.round(n * 100) / 100).toString(); };
        document.querySelectorAll('[data-mult="remember"]').forEach(function (el) { el.textContent = fmt(SAVED.remember_multiplier); });
        document.querySelectorAll('[data-mult="forgot"]').forEach(function (el) { el.textContent = fmt(SAVED.forgot_multiplier); });

        /* ---------- Forgetting curve (illustrative) ---------- */
        (function drawCurve() {
            var svg = $('curve-chart');
            if (!svg) { return; }
            var W = 640, H = 260, L = 44, R = 12, T = 12, B = 36;
            var days = 60, cw = W - L - R, ch = H - T - B;
            var x = function (d) { return L + (d / days) * cw; };
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
            for (t = 0; t <= days; t += 0.5) { d0 += (t === 0 ? 'M' : 'L') + x(t).toFixed(1) + ' ' + y(Math.exp(-t / base)).toFixed(1) + ' '; }
            out += '<path d="' + d0 + '" fill="none" stroke-width="2.5" stroke-linecap="round" style="stroke: rgb(var(--c-error))"/>';

            var d1 = '';
            for (i = 0; i < reviews.length; i++) {
                var start = reviews[i], end = i + 1 < reviews.length ? reviews[i + 1] : days;
                var strength = base * Math.pow(2, i);
                for (s = start; s <= end + 0.001; s += 0.25) {
                    d1 += (s === start ? 'M' : 'L') + x(s).toFixed(1) + ' ' + y(Math.exp(-(s - start) / strength)).toFixed(1) + ' ';
                }
            }
            out += '<path d="' + d1 + '" fill="none" stroke-width="2.5" stroke-linejoin="round" style="stroke: rgb(var(--c-primary))"/>';
            reviews.slice(1).forEach(function (rd) { out += '<circle cx="' + x(rd) + '" cy="' + y(1) + '" r="4" style="fill: rgb(var(--c-primary))"/>'; });
            svg.innerHTML = out;
        })();
    })();
    </script>

</body>
</html>