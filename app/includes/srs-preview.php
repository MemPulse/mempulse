<?php
/**
 * MemPulse SRS schedule preview (interactive).
 *
 * Put this file in app/includes/ and include it where you want the preview.
 *
 * Basic use (renders its own four inputs, sandbox only, nothing is saved):
 *
 *   $srsPreview = [
 *       'remember' => 2.0,   // Remember multiplier
 *       'forgot'   => 0.5,   // Forgot multiplier
 *       'initial'  => 1,     // initial_interval_days
 *       'max'      => 365,   // maximum_interval_days
 *   ];
 *   include __DIR__ . '/../includes/srs-preview.php';
 *
 * Use on the Settings page (no extra inputs; follows your real fields live):
 *
 *   $srsPreview = [ ...current saved values... ];
 *   $srsPreviewBind = [
 *       'remember' => '#your-remember-input-selector',
 *       'forgot'   => '#your-forgot-input-selector',
 *       'initial'  => '#your-initial-input-selector',
 *       'max'      => '#your-max-input-selector',
 *   ];
 *   include __DIR__ . '/../includes/srs-preview.php';
 *
 * Intervals are whole days: round(current * multiplier), never below 1 and never above max.
 * Keep this in line with how save-session.php / SessionService.php schedule real cards.
 */

$__srspDefaults = ['remember' => 2.0, 'forgot' => 0.5, 'initial' => 1, 'max' => 365];
$__srspCfg  = array_merge($__srspDefaults, (isset($srsPreview) && is_array($srsPreview)) ? $srsPreview : []);
$__srspBind = (isset($srsPreviewBind) && is_array($srsPreviewBind)) ? $srsPreviewBind : null;
$__srspId   = 'srsp' . substr(md5(uniqid('', true)), 0, 8);
$__srspData = ['defaults' => $__srspCfg, 'bind' => $__srspBind];

// Clean values for the input fields.
$__srspRemember = rtrim(rtrim(number_format((float) $__srspCfg['remember'], 2, '.', ''), '0'), '.');
$__srspForgot   = rtrim(rtrim(number_format((float) $__srspCfg['forgot'], 2, '.', ''), '0'), '.');

$__srspInputClass = 'w-full rounded-lg border border-outline-variant/60 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary';

if (!defined('SRSP_STYLE_PRINTED')) {
    define('SRSP_STYLE_PRINTED', true);
    ?>
<style>
    @keyframes srsp-pop {
        from { transform: scale(.7); opacity: 0; }
        to   { transform: scale(1);  opacity: 1; }
    }
    .srsp-new { animation: srsp-pop .25s ease-out; }
    @media (prefers-reduced-motion: reduce) {
        .srsp-new { animation: none; }
    }
</style>
<?php } ?>

<div id="<?php echo $__srspId; ?>" class="flex flex-col gap-4">

    <?php if ($__srspBind === null): ?>
        <!-- Inputs (hidden when bound to existing Settings fields) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="flex flex-col gap-1.5">
                <label for="<?php echo $__srspId; ?>-remember" class="text-sm font-semibold flex items-center gap-1.5">
                    Remember multiplier
                    <span class="material-symbols-outlined text-[15px] text-on-surface-variant/60 cursor-help" title="Next interval = current interval × this number, after you choose Remember.">help</span>
                </label>
                <input id="<?php echo $__srspId; ?>-remember" data-in="remember" type="number" inputmode="decimal" min="1" step="0.05"
                       value="<?php echo htmlspecialchars($__srspRemember, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $__srspInputClass; ?>">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="<?php echo $__srspId; ?>-forgot" class="text-sm font-semibold flex items-center gap-1.5">
                    Forgot multiplier
                    <span class="material-symbols-outlined text-[15px] text-on-surface-variant/60 cursor-help" title="Next interval = current interval × this number, after you choose Forgot. Use a value below 1.">help</span>
                </label>
                <input id="<?php echo $__srspId; ?>-forgot" data-in="forgot" type="number" inputmode="decimal" min="0.05" max="1" step="0.05"
                       value="<?php echo htmlspecialchars($__srspForgot, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $__srspInputClass; ?>">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="<?php echo $__srspId; ?>-initial" class="text-sm font-semibold flex items-center gap-1.5">
                    Initial interval
                    <span class="material-symbols-outlined text-[15px] text-on-surface-variant/60 cursor-help" title="Days until a new card's first review.">help</span>
                </label>
                <input id="<?php echo $__srspId; ?>-initial" data-in="initial" type="number" inputmode="numeric" min="1" step="1"
                       value="<?php echo (int) $__srspCfg['initial']; ?>" class="<?php echo $__srspInputClass; ?>">
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="<?php echo $__srspId; ?>-max" class="text-sm font-semibold flex items-center gap-1.5">
                    Max interval
                    <span class="material-symbols-outlined text-[15px] text-on-surface-variant/60 cursor-help" title="The longest wait, in days, a card can reach.">help</span>
                </label>
                <input id="<?php echo $__srspId; ?>-max" data-in="max" type="number" inputmode="numeric" min="1" step="1"
                       value="<?php echo (int) $__srspCfg['max']; ?>" class="<?php echo $__srspInputClass; ?>">
            </div>
        </div>
    <?php endif; ?>

    <!-- Preview panel -->
    <div class="rounded-2xl bg-surface-container-low border border-outline-variant/30 p-4 sm:p-5 flex flex-col gap-4">

        <div data-role="scroller" class="overflow-x-auto pb-1" style="scrollbar-width: thin;">
            <div data-role="timeline" class="flex items-start w-max min-w-full" aria-hidden="true"></div>
        </div>

        <p data-role="warn" class="text-xs text-error min-h-[1rem]" role="status"></p>

        <p class="text-sm text-on-surface-variant leading-snug">
            Use the buttons to see how your answers change a card's review schedule. Edit the numbers to see it update.
        </p>

        <p data-role="status" class="text-sm font-semibold" aria-live="polite"></p>
        <p data-role="sr" class="sr-only"></p>

        <div class="flex flex-wrap gap-2">
            <button type="button" data-act="remember" class="inline-flex items-center gap-1.5 rounded-lg border border-teal-500/40 bg-surface-container-lowest text-teal-700 dark:text-teal-300 text-sm font-semibold px-3 py-1.5 hover:bg-teal-500/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500">
                <span class="material-symbols-outlined text-[18px]">check</span>Remember
            </button>
            <button type="button" data-act="forgot" class="inline-flex items-center gap-1.5 rounded-lg border border-error/40 bg-surface-container-lowest text-error text-sm font-semibold px-3 py-1.5 hover:bg-error/10 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-error">
                <span class="material-symbols-outlined text-[18px]">close</span>Forgot
            </button>
            <button type="button" data-act="reset" class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant/60 bg-surface-container-lowest text-on-surface-variant text-sm font-semibold px-3 py-1.5 hover:bg-surface-container transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span class="material-symbols-outlined text-[18px]">restart_alt</span>Reset
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var ID = <?php echo json_encode($__srspId); ?>;
    var CFG = <?php echo json_encode($__srspData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?>;

    var root = document.getElementById(ID);
    if (!root) { return; }

    var q = function (sel) { return root.querySelector(sel); };
    var timelineEl = q('[data-role="timeline"]');
    var scrollerEl = q('[data-role="scroller"]');
    var statusEl = q('[data-role="status"]');
    var warnEl = q('[data-role="warn"]');
    var srEl = q('[data-role="sr"]');
    var btnRemember = q('[data-act="remember"]');
    var btnForgot = q('[data-act="forgot"]');
    var btnReset = q('[data-act="reset"]');

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var MAX_GRADES = 30;
    var grades = [];

    // Find inputs: bound Settings fields if provided, otherwise this component's own inputs.
    var inputs = {};
    ['remember', 'forgot', 'initial', 'max'].forEach(function (k) {
        var bound = CFG.bind && CFG.bind[k] ? document.querySelector(CFG.bind[k]) : null;
        inputs[k] = bound || q('[data-in="' + k + '"]');
    });

    function num(el, fallback) {
        var v = el ? parseFloat(el.value) : NaN;
        return isFinite(v) ? v : fallback;
    }

    function fmt(n) { return (Math.round(n * 100) / 100).toString(); }
    function dayLabel(n) { return n + (n === 1 ? ' day' : ' days'); }

    function settings() {
        var warn = [];
        var r = num(inputs.remember, CFG.defaults.remember);
        if (r < 1) { warn.push('Remember multiplier should be 1 or more.'); r = 1; }
        var f = num(inputs.forgot, CFG.defaults.forgot);
        if (f <= 0 || f > 1) { warn.push('Forgot multiplier should be above 0 and no more than 1.'); f = Math.min(1, Math.max(0.05, f)); }
        var ini = Math.max(1, Math.round(num(inputs.initial, CFG.defaults.initial)));
        var mx = Math.max(1, Math.round(num(inputs.max, CFG.defaults.max)));
        if (ini > mx) { warn.push('Initial interval is above the maximum, so the maximum is used.'); ini = mx; }
        return { r: r, f: f, ini: ini, max: mx, warn: warn };
    }

    // Rebuilds the whole path from the grades so far, so editing a setting updates every step.
    function build(s) {
        var cur = s.ini, day = 0;
        var nodes = [{ type: 'added', day: 0 }];
        var steps = [];
        for (var i = 0; i <= grades.length; i++) {
            day += cur;
            var g = grades[i];
            nodes.push({ type: g || 'pending', day: day, wait: cur });
            if (g) {
                var m = g === 'remember' ? s.r : s.f;
                var raw = cur * m;
                var next = Math.min(s.max, Math.max(1, Math.round(raw)));
                steps.push({ grade: g, from: cur, mult: m, raw: raw, to: next });
                cur = next;
            }
        }
        return { nodes: nodes, steps: steps, day: day };
    }

    var NODE = {
        added:   { cls: 'border-primary/40 bg-primary/10 text-primary', icon: 'add',   label: 'Added' },
        remember:{ cls: 'border-teal-500/50 bg-teal-500/10 text-teal-600 dark:text-teal-300', icon: 'check', label: '' },
        forgot:  { cls: 'border-error/50 bg-error/10 text-error', icon: 'close', label: '' },
        pending: { cls: 'border-dashed border-outline bg-surface-container-lowest text-transparent', icon: 'check', label: '' }
    };

    function nodeHtml(n, isNew) {
        var d = NODE[n.type];
        var caption = n.type === 'added' ? 'Added' : (n.type === 'pending' ? 'Next review' : 'Day ' + n.day);
        var capCls = n.type === 'pending' ? 'text-on-surface-variant' : 'text-on-surface-variant/80';
        return '<div class="flex flex-col items-center gap-1 shrink-0 ' + (isNew ? 'srsp-new' : '') + '" style="width:3.25rem;">' +
               '<div class="w-10 h-10 rounded-lg border flex items-center justify-center ' + d.cls + '">' +
               '<span class="material-symbols-outlined text-[22px]">' + d.icon + '</span></div>' +
               '<span class="text-[10px] leading-tight text-center ' + capCls + '">' + caption + '</span></div>';
    }

    function segHtml(wait, isNew) {
        return '<div class="relative h-10 shrink-0 ' + (isNew ? 'srsp-new' : '') + '" style="width:4.75rem;">' +
               '<span class="absolute left-0 right-0 text-center text-xs text-on-surface-variant tabular-nums" style="bottom:55%;">' + dayLabel(wait) + '</span>' +
               '<svg class="absolute left-0 right-0 text-outline" style="top:50%; margin-top:2px;" width="100%" height="8" viewBox="0 0 76 8" preserveAspectRatio="none" fill="none" stroke="currentColor" stroke-width="1.25">' +
               '<line x1="2" y1="4" x2="72" y2="4"/><polyline points="68,1 73,4 68,7"/></svg></div>';
    }

    function render(pop) {
        var s = settings();
        var b = build(s);
        var html = '', sr = [];

        b.nodes.forEach(function (n, i) {
            var isLast = i === b.nodes.length - 1;
            if (i > 0) { html += segHtml(n.wait, pop && isLast); sr.push('wait ' + dayLabel(n.wait)); }
            html += nodeHtml(n, pop && isLast);
            sr.push(n.type === 'added' ? 'card added' : (n.type === 'pending' ? 'next review' : (n.type === 'remember' ? 'remembered' : 'forgot')) + (n.type === 'pending' ? '' : ' on day ' + n.day));
        });
        timelineEl.innerHTML = html;
        srEl.textContent = sr.join(', ') + '.';

        // Status line
        var last = b.steps[b.steps.length - 1];
        var nextWait = b.nodes[b.nodes.length - 1].wait;
        if (!last) {
            statusEl.textContent = 'First review is due ' + (nextWait === 1 ? 'in 1 day' : 'in ' + nextWait + ' days') + '. Press Remember or Forgot to simulate it.';
        } else {
            var note = '';
            if (Math.round(last.raw) < 1) { note = ' (rounded up to the 1-day minimum)'; }
            else if (Math.round(last.raw) > s.max) { note = ' (capped at your maximum)'; }
            statusEl.textContent = (last.grade === 'remember' ? 'Remember' : 'Forgot') + ': ' +
                last.from + ' \u00d7 ' + fmt(last.mult) + ' = ' + last.to + (last.to === 1 ? ' day' : ' days') + note +
                '. Next review in ' + dayLabel(nextWait) + '.';
        }

        warnEl.textContent = s.warn.join(' ');

        var atLimit = grades.length >= MAX_GRADES;
        [btnRemember, btnForgot].forEach(function (btn) {
            btn.disabled = atLimit;
            btn.classList.toggle('opacity-50', atLimit);
            btn.classList.toggle('cursor-not-allowed', atLimit);
            btn.title = atLimit ? 'Reset to start again' : '';
        });

        if (pop) {
            scrollerEl.scrollTo({ left: scrollerEl.scrollWidth, behavior: reduceMotion ? 'auto' : 'smooth' });
        }
    }

    btnRemember.addEventListener('click', function () { if (grades.length < MAX_GRADES) { grades.push('remember'); render(true); } });
    btnForgot.addEventListener('click', function () { if (grades.length < MAX_GRADES) { grades.push('forgot'); render(true); } });
    btnReset.addEventListener('click', function () { grades = []; render(false); scrollerEl.scrollLeft = 0; });

    Object.keys(inputs).forEach(function (k) {
        if (inputs[k]) {
            inputs[k].addEventListener('input', function () { render(false); });
            inputs[k].addEventListener('change', function () { render(false); });
        }
    });

    render(false);
})();
</script>