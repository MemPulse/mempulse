<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$user_id = $_SESSION['user_id'];

// Default SRS multipliers; falls back if connection or settings row is unavailable.
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
    // Keep defaults; science page must run reliably regardless of DB state.
}

$pageTitle = "Science";
$currentPage = "science";
?>
<!DOCTYPE html>
<html lang="en-PH" class="scroll-smooth">

<?php include __DIR__ . '/../includes/head.php'; ?>

<!-- Styling, Typography & Icons matching index.html -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<style>
    :root {
        color-scheme: light;
        --surface: #faf8ff;
        --surface-low: #f2f3ff;
        --surface-card: #ffffff;
        --surface-container: #eaedff;
        --surface-high: #e2e7ff;
        --surface-highest: #dae2fd;
        --ink: #131b2e;
        --muted: #464555;
        --muted-soft: #777587;
        --primary: #3525cd;
        --primary-deep: #1e00a9;
        --secondary: #4b41e1;
        --secondary-fixed: #e2dfff;
        --mint: #4edea3;
        --mint-dark: #006c48;
        --mint-pale: #d6f8e9;
        --error: #ba1a1a;
        --error-pale: #ffdad6;
        --radius-lg: 1rem;
        --radius-xl: 1.25rem;
        --shadow-soft: 0 8px 30px rgba(53, 37, 205, .07);
        --shadow-card: 0 20px 50px rgba(53, 37, 205, .10);
    }

    body {
        background: var(--surface);
        color: var(--ink);
        font-family: "Inter", sans-serif;
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
    }

    .font-jakarta { font-family: "Plus Jakarta Sans", sans-serif; }

    .badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        background: var(--surface-high);
        color: var(--ink);
        font: 700 0.7rem/1.2 "Plus Jakarta Sans", sans-serif;
        letter-spacing: 0.075em;
        text-transform: uppercase;
    }

    .btn-minimal {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        border-radius: 999px;
        padding: 0.65rem 1.3rem;
        font: 700 0.88rem/1.2 "Plus Jakarta Sans", sans-serif;
        transition: transform 0.2s ease, background 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-minimal:hover { transform: translateY(-2px); }
    
    .btn-primary-filled {
        background: var(--primary);
        color: #ffffff;
        box-shadow: 0 8px 22px rgba(53, 37, 205, 0.22);
    }
    .btn-primary-filled:hover {
        background: var(--secondary);
        box-shadow: 0 12px 26px rgba(75, 65, 225, 0.28);
        color: #ffffff;
    }
    
    .btn-outlined-light {
        color: var(--ink);
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid #d9d9ed;
        box-shadow: 0 3px 12px rgba(19, 27, 46, 0.04);
    }
    .btn-outlined-light:hover {
        background: var(--surface-low);
        border-color: #c7c4d8;
    }

    .card-bento {
        background: var(--surface-card);
        border: 1px solid rgba(199, 196, 216, 0.48);
        border-radius: var(--radius-xl);
        padding: 1.75rem;
        box-shadow: 0 4px 18px rgba(19, 27, 46, 0.035);
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }
    .card-bento:hover {
        border-color: rgba(75, 65, 225, 0.25);
        box-shadow: var(--shadow-soft);
    }

    .hero-card {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        border-radius: var(--radius-xl);
        background: linear-gradient(135deg, var(--surface-card) 0%, var(--surface-low) 100%);
        border: 1px solid rgba(199, 196, 216, 0.5);
        padding: clamp(2rem, 5vw, 3.25rem);
        box-shadow: var(--shadow-card);
    }
    .hero-card::before {
        content: "";
        position: absolute;
        z-index: -1;
        width: 320px;
        height: 320px;
        top: -120px;
        right: -100px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(218, 226, 253, 0.8) 0%, rgba(226, 223, 255, 0) 70%);
        pointer-events: none;
    }

    .input-minimal {
        width: 100%;
        background: var(--surface-low);
        border: 1px solid rgba(199, 196, 216, 0.5);
        border-radius: 0.75rem;
        padding: 0.6rem 0.85rem;
        font-family: "Plus Jakarta Sans", sans-serif;
        font-weight: 700;
        color: var(--ink);
        outline: none;
        transition: border-color 0.2s ease, background 0.2s ease;
    }
    .input-minimal:focus {
        border-color: var(--primary);
        background: #ffffff;
    }
</style>

<body class="antialiased min-h-screen selection:bg-surface-highest selection:text-primary-deep">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">

        <div class="w-full px-4 sm:px-6 lg:px-8 py-6 md:py-10 max-w-6xl mx-auto">
            <div class="flex flex-col gap-10 md:gap-14">

                <!-- ========================= HERO (EXECUTIVE SUMMARY) ========================= -->
                <header class="hero-card flex flex-col items-start gap-6">
                    <div class="flex flex-wrap items-center justify-between w-full gap-3">
                        <span class="badge-pill">
                            <span class="material-symbols-outlined text-[16px] text-primary">psychology</span>
                            Evidence-Based Learning
                        </span>
                        <span class="text-xs font-semibold text-muted-soft font-jakarta">3-Second Read</span>
                    </div>

                    <div class="max-w-3xl flex flex-col gap-3">
                        <h1 class="font-jakarta text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight text-ink">
                            The Neuroscience of Long-Term Retention
                        </h1>
                        <p class="text-base sm:text-lg text-muted leading-relaxed">
                            How spaced active recall flattens memory decay and eliminates study burnout.
                        </p>
                    </div>

                    <!-- 3 High-Impact Stat Badges -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full pt-2">
                        <div class="bg-surface-card border border-surface-high p-4 rounded-2xl flex flex-col gap-1 shadow-sm">
                            <span class="text-2xl sm:text-3xl font-extrabold font-jakarta text-error">70%</span>
                            <span class="text-xs font-semibold text-muted leading-snug">Memory decayed within 24h without review</span>
                        </div>
                        <div class="bg-surface-card border border-surface-high p-4 rounded-2xl flex flex-col gap-1 shadow-sm">
                            <span class="text-2xl sm:text-3xl font-extrabold font-jakarta text-primary">2.0x</span>
                            <span class="text-xs font-semibold text-muted leading-snug">Interval multiplier per successful recall</span>
                        </div>
                        <div class="bg-surface-card border border-surface-high p-4 rounded-2xl flex flex-col gap-1 shadow-sm">
                            <span class="text-2xl sm:text-3xl font-extrabold font-jakarta text-mint-dark">15 Mins</span>
                            <span class="text-xs font-semibold text-muted leading-snug">Daily queue needed for 90%+ exam retention</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <a href="#engine" class="btn-minimal btn-primary-filled">
                            <span class="material-symbols-outlined text-[18px]">tune</span>
                            Try the Engine Sandbox
                        </a>
                        <a href="decks.php" class="btn-minimal btn-outlined-light">
                            <span class="material-symbols-outlined text-[18px]">style</span>
                            Start Reviewing
                        </a>
                    </div>
                </header>

                <!-- ================= 01 THE FORGETTING CURVE ================= -->
                <section class="flex flex-col gap-6" aria-labelledby="forgetting">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-bold font-jakarta uppercase tracking-wider text-primary">01 &middot; Visual Proof</span>
                        <h2 id="forgetting" class="font-jakarta text-2xl sm:text-3xl font-bold tracking-tight text-ink">The Forgetting Curve in an Instant</h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                        <!-- Left: Visual Comparison Cards -->
                        <div class="lg:col-span-5 flex flex-col gap-4">
                            <div class="card-bento border-error/30 bg-error-pale/20 flex flex-col gap-2 p-5">
                                <div class="flex items-center gap-2 text-error font-jakarta font-bold">
                                    <span class="material-symbols-outlined text-[20px]">trending_down</span>
                                    Unspaced Cramming
                                </div>
                                <p class="text-sm text-muted leading-relaxed">
                                    Mass study sessions create temporary familiarity. Memory collapses exponentially down to <strong class="text-ink">10-20%</strong> within a week.
                                </p>
                            </div>

                            <div class="card-bento border-mint-dark/30 bg-mint-pale/20 flex flex-col gap-2 p-5">
                                <div class="flex items-center gap-2 text-mint-dark font-jakarta font-bold">
                                    <span class="material-symbols-outlined text-[20px]">event_repeat</span>
                                    Spaced MemPulse Reviews
                                </div>
                                <p class="text-sm text-muted leading-relaxed">
                                    Reviews performed right at the verge of decay reset memory strength, maintaining <strong class="text-ink">90%+ stability</strong> permanently.
                                </p>
                            </div>
                        </div>

                        <!-- Right: Dynamic Chart -->
                        <figure class="lg:col-span-7 card-bento flex flex-col gap-3 justify-between">
                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs font-medium text-muted">
                                <span class="font-jakarta font-bold text-ink">60-Day Decay &amp; Recall Trajectory</span>
                                <div class="flex items-center gap-4">
                                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-error inline-block"></span> Unspaced</span>
                                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-primary inline-block"></span> Spaced</span>
                                </div>
                            </div>
                            <div class="w-full bg-surface-low p-3 rounded-xl border border-surface-high">
                                <svg id="curve-chart" viewBox="0 0 640 260" class="w-full h-auto" role="img"
                                    aria-label="Chart displaying exponential memory decay versus stabilized retention over spaced reviews over 60 days."></svg>
                            </div>
                            <figcaption class="text-xs text-muted-soft leading-snug">
                                Model adapted from Hermann Ebbinghaus' memory experiments. Each review flattens subsequent memory decay.
                            </figcaption>
                        </figure>
                    </div>
                </section>

                <!-- ================= 02 HOW IT WORKS FOR STUDENTS ================= -->
                <section class="flex flex-col gap-6" aria-labelledby="students">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-bold font-jakarta uppercase tracking-wider text-primary">02 &middot; 3-Step Workflow</span>
                        <h2 id="students" class="font-jakarta text-2xl sm:text-3xl font-bold tracking-tight text-ink">How MemPulse Works for Students</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="card-bento flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="w-10 h-10 rounded-xl bg-surface-high text-primary font-jakarta font-bold text-base flex items-center justify-center">1</span>
                                <span class="material-symbols-outlined text-muted-soft">grid_view</span>
                            </div>
                            <h3 class="font-jakarta font-bold text-lg text-ink">Atomic Flashcards</h3>
                            <p class="text-sm text-muted leading-relaxed">
                                Break heavy lecture notes into bite-sized single concepts. One question, one clear answer—eliminating cognitive overload.
                            </p>
                        </div>

                        <div class="card-bento flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="w-10 h-10 rounded-xl bg-surface-high text-primary font-jakarta font-bold text-base flex items-center justify-center">2</span>
                                <span class="material-symbols-outlined text-muted-soft">psychology</span>
                            </div>
                            <h3 class="font-jakarta font-bold text-lg text-ink">Active Recall</h3>
                            <p class="text-sm text-muted leading-relaxed">
                                Attempt to answer from memory <em>before</em> checking the card back. Mental exertion triggers deep neural encoding.
                            </p>
                        </div>

                        <div class="card-bento flex flex-col gap-3">
                            <div class="flex items-center justify-between">
                                <span class="w-10 h-10 rounded-xl bg-surface-high text-primary font-jakarta font-bold text-base flex items-center justify-center">3</span>
                                <span class="material-symbols-outlined text-muted-soft">schedule</span>
                            </div>
                            <h3 class="font-jakarta font-bold text-lg text-ink">Smart Scheduling</h3>
                            <p class="text-sm text-muted leading-relaxed">
                                Skip cards you already know. MemPulse queues cards only when they reach the decay threshold, saving hours of study time.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- ================= 03 THE MEMPULSE ENGINE ================= -->
                <section id="engine" class="flex flex-col gap-6 scroll-mt-8" aria-labelledby="engine-title">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs font-bold font-jakarta uppercase tracking-wider text-primary">03 &middot; Algorithmic Core</span>
                        <h2 id="engine-title" class="font-jakarta text-2xl sm:text-3xl font-bold tracking-tight text-ink">The MemPulse Engine IN AN INSTANT</h2>
                    </div>

                    <!-- Formula Highlight Banner -->
                    <div class="p-5 sm:p-6 rounded-2xl bg-surface-high border border-surface-highest flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex flex-col gap-1 text-center sm:text-left">
                            <span class="text-xs font-bold font-jakarta uppercase text-primary">Core Multiplier Formula</span>
                            <p class="font-jakarta font-extrabold text-xl sm:text-2xl text-primary-deep tracking-tight">
                                Next Interval = Current Interval &times; Multiplier
                            </p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="px-3 py-1.5 rounded-xl bg-mint-pale text-mint-dark font-jakarta font-bold text-xs">&check; Remember: &times;<span data-mult="remember">2.0</span></span>
                            <span class="px-3 py-1.5 rounded-xl bg-error-pale text-error font-jakarta font-bold text-xs">&times; Forgot: &times;<span data-mult="forgot">0.5</span></span>
                        </div>
                    </div>

                    <!-- Sandbox Dashboard Widget -->
                    <div class="card-bento bg-surface-card border-surface-high flex flex-col gap-6 shadow-card">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 class="font-jakarta text-xl font-bold text-ink">Interactive Engine Sandbox</h3>
                                <p class="text-sm text-muted">Test how card intervals expand or contract dynamically based on user ratings.</p>
                            </div>
                            <button type="button" id="btn-reset" class="btn-minimal btn-outlined-light text-xs py-2">
                                <span class="material-symbols-outlined text-[16px]">restart_alt</span> Reset
                            </button>
                        </div>

                        <!-- Multiplier Controls -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="flex flex-col gap-1.5">
                                <label for="remember-multiplier-input" class="text-xs font-bold font-jakarta text-ink">Remember Multiplier</label>
                                <div class="flex items-center gap-2">
                                    <input id="remember-multiplier-input" type="number" min="1.1" max="10" step="0.1"
                                        value="<?php echo htmlspecialchars((string) $srs['remember_multiplier'], ENT_QUOTES); ?>"
                                        class="input-minimal">
                                    <span class="font-bold text-muted">&times;</span>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="forgot-multiplier-input" class="text-xs font-bold font-jakarta text-ink">Forgot Multiplier</label>
                                <div class="flex items-center gap-2">
                                    <input id="forgot-multiplier-input" type="number" min="0.1" max="1" step="0.1"
                                        value="<?php echo htmlspecialchars((string) $srs['forgot_multiplier'], ENT_QUOTES); ?>"
                                        class="input-minimal">
                                    <span class="font-bold text-muted">&times;</span>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label for="max-interval-input" class="text-xs font-bold font-jakarta text-ink">Max Cap (Days)</label>
                                <input id="max-interval-input" type="number" min="1" max="3650" step="1"
                                    value="<?php echo (int) $srs['maximum_interval_days']; ?>"
                                    class="input-minimal">
                            </div>
                        </div>

                        <!-- Live Timeline Bar & Stats -->
                        <div class="p-5 rounded-2xl bg-surface-low border border-surface-container flex flex-col gap-5">
                            <div id="timeline-container" class="flex items-center gap-2 overflow-x-auto pb-2 min-h-[60px]" aria-label="Review timeline"></div>

                            <div class="grid grid-cols-3 gap-3 text-center">
                                <div class="bg-surface-card p-3 rounded-xl border border-surface-high">
                                    <p class="text-[10px] font-bold font-jakarta uppercase tracking-wider text-muted">Reviews Logged</p>
                                    <p id="sb-reviews" class="text-xl font-extrabold font-jakarta text-ink mt-0.5">0</p>
                                </div>
                                <div class="bg-surface-card p-3 rounded-xl border border-surface-high">
                                    <p class="text-[10px] font-bold font-jakarta uppercase tracking-wider text-muted">Next Wait Interval</p>
                                    <p id="sb-next" class="text-xl font-extrabold font-jakarta text-primary mt-0.5">1 day</p>
                                </div>
                                <div class="bg-surface-card p-3 rounded-xl border border-surface-high">
                                    <p class="text-[10px] font-bold font-jakarta uppercase tracking-wider text-muted">Cumulative Day</p>
                                    <p id="sb-day" class="text-xl font-extrabold font-jakarta text-ink mt-0.5">1</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-center gap-3">
                                <button type="button" id="btn-remember" class="btn-minimal btn-primary-filled !bg-mint-dark hover:!bg-emerald-700">
                                    <span class="material-symbols-outlined text-[18px]">check</span> I Remembered (+2.0x)
                                </button>
                                <button type="button" id="btn-forget" class="btn-minimal btn-primary-filled !bg-error hover:!bg-red-700">
                                    <span class="material-symbols-outlined text-[18px]">close</span> I Forgot (0.5x)
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ================= FOOTER & REFERENCES ================= -->
                <footer class="flex flex-col gap-5 border-t border-surface-high pt-8 pb-12">
                    <div class="flex flex-wrap gap-4">
                        <a href="decks.php" class="btn-minimal btn-primary-filled">
                            <span class="material-symbols-outlined text-[18px]">style</span>
                            Start Reviewing Your Decks
                        </a>
                        <a href="settings.php" class="btn-minimal btn-outlined-light">
                            <span class="material-symbols-outlined text-[18px]">settings</span>
                            Adjust Engine Settings
                        </a>
                    </div>
                    <p class="text-xs text-muted-soft leading-relaxed max-w-4xl">
                        <strong class="text-muted">Academic References:</strong> Ebbinghaus, H. (1885). <em>Memory: A Contribution to Experimental Psychology</em>. Cepeda, N. J., et al. (2006). Distributed practice in verbal recall tasks, <em>Psychological Bulletin, 132</em>(3). Roediger, H. L., &amp; Karpicke, J. D. (2006). Test-enhanced learning, <em>Psychological Science, 17</em>(3).
                    </p>
                </footer>

            </div>
        </div>

    </main>

    <!-- JS Logic for Engine & Curve Visualizer -->
    <script>
    (function () {
        'use strict';

        var SAVED = <?php echo json_encode($srs, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;

        var $ = function (id) { return document.getElementById(id); };
        var clamp = function (n, lo, hi) { return Math.min(hi, Math.max(lo, n)); };
        var fmt = function (n) { return (Math.round(n * 100) / 100).toString(); };
        var days = function (n) { return n + (n === 1 ? ' day' : ' days'); };

        document.querySelectorAll('[data-mult="remember"]').forEach(function (el) { el.textContent = fmt(SAVED.remember_multiplier); });
        document.querySelectorAll('[data-mult="forgot"]').forEach(function (el) { el.textContent = fmt(SAVED.forgot_multiplier); });

        /* Forgetting Curve Chart */
        (function drawCurve() {
            var svg = $('curve-chart');
            if (!svg) { return; }
            var W = 640, H = 260, L = 44, R = 12, T = 12, B = 36;
            var span = 60, cw = W - L - R, ch = H - T - B;
            var x = function (d) { return L + (d / span) * cw; };
            var y = function (p) { return T + (1 - p) * ch; };
            var label = 'style="fill: #777587; font-family: Inter, sans-serif;" font-size="11"';
            var out = '';

            [0, 0.5, 1].forEach(function (p) {
                out += '<line x1="' + L + '" x2="' + (W - R) + '" y1="' + y(p) + '" y2="' + y(p) + '" style="stroke: #e2e7ff; stroke-dasharray: 4;"/>';
                out += '<text x="' + (L - 8) + '" y="' + (y(p) + 4) + '" text-anchor="end" ' + label + '>' + Math.round(p * 100) + '%</text>';
            });
            [0, 15, 30, 45, 60].forEach(function (d) {
                out += '<text x="' + x(d) + '" y="' + (H - 12) + '" text-anchor="middle" ' + label + '>' + d + 'd</text>';
            });

            var base = 3, reviews = [0, 1, 3, 7, 15, 31], t, i, s;
            var d0 = '';
            for (t = 0; t <= span; t += 0.5) { d0 += (t === 0 ? 'M' : 'L') + x(t).toFixed(1) + ' ' + y(Math.exp(-t / base)).toFixed(1) + ' '; }
            out += '<path d="' + d0 + '" fill="none" stroke-width="2.5" stroke-linecap="round" style="stroke: var(--error)"/>';

            var d1 = '';
            for (i = 0; i < reviews.length; i++) {
                var start = reviews[i], end = i + 1 < reviews.length ? reviews[i + 1] : span;
                var strength = base * Math.pow(2, i);
                for (s = start; s <= end + 0.001; s += 0.25) {
                    d1 += (s === start ? 'M' : 'L') + x(s).toFixed(1) + ' ' + y(Math.exp(-(s - start) / strength)).toFixed(1) + ' ';
                }
            }
            out += '<path d="' + d1 + '" fill="none" stroke-width="2.5" stroke-linejoin="round" style="stroke: var(--primary)"/>';
            reviews.slice(1).forEach(function (rd) { out += '<circle cx="' + x(rd) + '" cy="' + y(1) + '" r="4" style="fill: var(--primary)"/>'; });
            svg.innerHTML = out;
        })();

        /* Scheduler Sandbox */
        (function sandbox() {
            var timeline = $('timeline-container');
            if (!timeline) { return; }

            var rememberInput = $('remember-multiplier-input');
            var forgotInput = $('forgot-multiplier-input');
            var maxInput = $('max-interval-input');

            var maxSaved = SAVED.maximum_interval_days > 0 ? SAVED.maximum_interval_days : 365;
            var startWait = Math.max(1, SAVED.initial_interval_days || 1);

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

            var box = 'shrink-0 w-9 h-9 rounded-xl flex items-center justify-center font-bold ';
            function node(kind) {
                if (kind === 'added') {
                    return '<div class="' + box + 'bg-surface-card text-primary shadow-sm border border-surface-high" title="Card added"><span class="material-symbols-outlined text-[18px]">add</span></div>';
                }
                if (kind === 'remember') {
                    return '<div class="' + box + 'bg-mint-pale text-mint-dark shadow-sm" title="Remembered"><span class="material-symbols-outlined text-[18px]">check</span></div>';
                }
                if (kind === 'forget') {
                    return '<div class="' + box + 'bg-error-pale text-error shadow-sm" title="Forgot"><span class="material-symbols-outlined text-[18px]">close</span></div>';
                }
                return '<div class="' + box + 'bg-surface-high border border-dashed border-muted-soft/40" title="Next review"></div>';
            }
            function arrow(n) {
                return '<div class="shrink-0 flex items-center gap-1 px-1 text-muted">' +
                    '<span class="h-0.5 w-6 bg-surface-highest"></span>' +
                    '<span class="text-xs font-bold font-jakarta whitespace-nowrap">' + days(n) + '</span>' +
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