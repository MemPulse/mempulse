document.addEventListener("DOMContentLoaded", () => {
    const data = window.MemPulseReviewData;
    if (!data.deckId || !data.dueCards || data.dueCards.length === 0) return;

    let queue = data.dueCards;
    let currentIndex = 0;

    // State storage: track how the user rated each card ID
    // Format: { cardId: 'remembered' | 'forgotten' }
    let ratings = {};
    let isAnswerShown = false;
    let sessionEnded = false;

    const csrfToken = data.csrfToken || '';
    let sessionStartedAt = Date.now();
    let sessionDuration = 0;
    let unsavedRatings = false;

    // Scheduling rules, same as save-session.php. These defaults are replaced
    // by the user's saved settings as soon as they load (see loadSrsSettings).
    let srs = {
        remember_multiplier: 2,
        forgot_multiplier: 0.5,
        initial_interval_days: 1,
        maximum_interval_days: 365
    };

    function nextInterval(prevDays, status) {
        const multiplier = status === 'remembered' ? srs.remember_multiplier : srs.forgot_multiplier;
        const days = Math.round(prevDays * multiplier);
        return Math.max(srs.initial_interval_days, Math.min(srs.maximum_interval_days, days));
    }

    const formatDays = (d) => `in ${d} ${d === 1 ? 'day' : 'days'}`;

    function updateProjections() {
        const card = queue[currentIndex];
        if (!card) return;
        const interval = parseInt(card.interval_days, 10) || 0;
        projForgot.textContent = formatDays(nextInterval(interval, 'forgotten'));
        projRemember.textContent = formatDays(nextInterval(interval, 'remembered'));
    }

    async function loadSrsSettings() {
        try {
            const res = await fetch('../api/settings-actions.php', { credentials: 'same-origin' });
            const body = await res.json();
            if (res.ok && body.success && body.settings) {
                srs = { ...srs, ...body.settings };
                updateProjections();
            }
        } catch (_) { /* keep the defaults */ }
    }

    // DOM Elements
    const elFront = document.getElementById('card-front-content');
    const elBack = document.getElementById('card-back-content');
    const elDivider = document.getElementById('card-divider');
    const btnShow = document.getElementById('show-answer-btn');
    const boxRates = document.getElementById('rate-actions');
    const btnForgot = document.getElementById('rate-forgot-btn');
    const btnRemember = document.getElementById('rate-remember-btn');
    const projForgot = document.getElementById('projected-forgot-date');
    const projRemember = document.getElementById('projected-remember-date');

    const txtProgress = document.getElementById('progress-text');
    const barProgress = document.getElementById('progress-bar');
    const txtRem = document.getElementById('tally-remembered');
    const txtFor = document.getElementById('tally-forgotten');
    const badgeHist = document.getElementById('historical-rating-badge');

    const viewActive = document.getElementById('active-review-container');
    const viewFinished = document.getElementById('review-finished-screen');

    const stage = document.getElementById('flashcard-stage');
    const hintReveal = document.getElementById('hint-reveal');
    const hintRate = document.getElementById('hint-rate');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let isAnimating = false;

    const btnMissed = document.getElementById('review-missed-btn');
    const returnLink = document.getElementById('return-hub-link');
    const sumAccuracy = document.getElementById('summary-accuracy');
    const sumRem = document.getElementById('summary-remembered');
    const sumFor = document.getElementById('summary-forgotten');
    let missedCount = 0;
    let statsDone = Promise.resolve();

    const btnPrev = document.getElementById('prev-card-btn');
    const btnNext = document.getElementById('next-card-btn');

    // 1. Core Navigation & Display Logic
    function renderCard() {
        if (currentIndex >= queue.length) {
            endSession();
            return;
        }

        const card = queue[currentIndex];
        elFront.innerHTML = escapeHtml(card.front).replace(/\n/g, '<br>');
        elBack.innerHTML = escapeHtml(card.back).replace(/\n/g, '<br>');

        txtProgress.textContent = `Card ${currentIndex + 1} of ${queue.length}`;
        barProgress.style.width = `${((currentIndex) / queue.length) * 100}%`;

        // Check if previously rated (via back navigation)
        const prevRating = ratings[card.id];
        if (prevRating) {
            badgeHist.classList.remove('hidden', 'bg-emerald-100', 'text-emerald-700', 'bg-rose-100', 'text-rose-700');
            if (prevRating === 'remembered') {
                badgeHist.textContent = 'Rated: Remembered';
                badgeHist.classList.add('bg-emerald-100', 'text-emerald-700');
            } else {
                badgeHist.textContent = 'Rated: Forgotten';
                badgeHist.classList.add('bg-rose-100', 'text-rose-700');
            }
        } else {
            badgeHist.classList.add('hidden');
        }

        // Show when this card will come back for each rating
        updateProjections();

        hideAnswer();
        updateNavButtons();
    }

    function showAnswer() {
        if (sessionEnded || isAnimating || isAnswerShown) return;
        isAnswerShown = true;
        elBack.classList.remove('hidden');
        elDivider.classList.remove('hidden');
        btnShow.classList.add('hidden');
        boxRates.classList.remove('hidden');
        hintReveal.classList.add('hidden');
        hintRate.classList.remove('hidden');
        if (!reduceMotion) {
            elBack.animate(
                [{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'translateY(0)' }],
                { duration: 200, easing: 'ease-out' });
        }
    }

    function hideAnswer() {
        isAnswerShown = false;
        elBack.classList.add('hidden');
        elDivider.classList.add('hidden');
        btnShow.classList.remove('hidden');
        boxRates.classList.add('hidden');
        hintReveal.classList.remove('hidden');
        hintRate.classList.add('hidden');
    }

    // 2. Rating & Tally Logic
    async function rateCard(status) {
        if (sessionEnded || isAnimating) return;
        const card = queue[currentIndex];
        ratings[card.id] = status;
        unsavedRatings = true;
        updateTally(status);

        if (reduceMotion) {
            currentIndex++;
            renderCard();
            return;
        }

        isAnimating = true;
        const dir = status === 'remembered' ? 1 : -1;
        const glow = status === 'remembered' ? 'rgba(16,185,129,0.35)' : 'rgba(244,63,94,0.35)';

        const out = stage.animate([
            { opacity: 1, transform: 'translateX(0) rotate(0deg)', boxShadow: `0 0 0 0 ${glow}` },
            { opacity: 0, transform: `translateX(${dir * 64}px) rotate(${dir * 2}deg)`, boxShadow: `0 0 0 6px ${glow}` }
        ], { duration: 220, easing: 'ease-in', fill: 'forwards' });
        await out.finished;

        currentIndex++;
        renderCard();
        out.cancel();

        if (!sessionEnded) {
            stage.animate([
                { opacity: 0, transform: 'translateY(14px) scale(0.98)' },
                { opacity: 1, transform: 'translateY(0) scale(1)' }
            ], { duration: 240, easing: 'ease-out' });
        }
        isAnimating = false;
    }

    function updateTally(bumpStatus) {
        let rem = 0, forg = 0;
        Object.values(ratings).forEach(r => r === 'remembered' ? rem++ : forg++);
        txtRem.textContent = rem;
        txtFor.textContent = forg;
        if (bumpStatus && !reduceMotion) {
            (bumpStatus === 'remembered' ? txtRem : txtFor).animate(
                [{ transform: 'scale(1)' }, { transform: 'scale(1.4)' }, { transform: 'scale(1)' }],
                { duration: 280, easing: 'ease-out' });
        }
    }

    // 3. Backward/Forward Queue Navigation
    function prevCard() {
        if (sessionEnded || isAnimating || currentIndex === 0) return; {
            currentIndex--;
            renderCard();
            slideIn(-1);
        }
    }

    // Back is available after the first card; Next only once the current card has a rating
    function updateNavButtons() {
        btnPrev.disabled = currentIndex === 0;
        const card = queue[currentIndex];
        btnNext.disabled = !(card && ratings[card.id]);
    }

    function slideIn(dir) {
        if (reduceMotion) return;
        stage.animate([
            { opacity: 0, transform: `translateX(${dir * 28}px)` },
            { opacity: 1, transform: 'translateX(0)' }
        ], { duration: 200, easing: 'ease-out' });
    }

    function skipToNext() {
        if (sessionEnded || isAnimating) return;
        const card = queue[currentIndex];
        if (!card || !ratings[card.id]) return;   // only skip cards already rated
        currentIndex++;
        renderCard();
        if (!sessionEnded) slideIn(1);
    }

    // 4. Batch Save & Session End
    async function saveSession() {
        const res = await fetch('../api/save-session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                deck_id: data.deckId,
                results: ratings,
                csrf_token: csrfToken,
                duration_seconds: sessionDuration
            })
        });
        let body = {};
        try { body = await res.json(); } catch (_) { }
        if (!res.ok || !body.ok) {
            throw new Error(body.error || `Save failed (HTTP ${res.status}).`);
        }
        unsavedRatings = false;
        return body;
    }

    function showSaveError(message) {
        let box = document.getElementById('save-status');
        if (!box) {
            box = document.createElement('div');
            box.id = 'save-status';
            box.className = 'w-full max-w-sm mb-6 p-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 text-sm flex flex-col gap-3';
            viewFinished.insertBefore(box, document.getElementById('finished-actions'));
        }
        box.textContent = '';

        const p = document.createElement('p');
        p.textContent = `Your session wasn't saved: ${message}`;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold';
        btn.textContent = 'Retry save';
        btn.addEventListener('click', async () => {
            btn.disabled = true;
            try {
                await saveSession(); box.remove(); armMissedButton();
            } catch (err) { showSaveError(err.message); }
        });

        box.append(p, btn);
    }

    async function endSession() {
        if (sessionEnded) return;
        sessionEnded = true;
        sessionDuration = Math.round((Date.now() - sessionStartedAt) / 1000);
        viewActive.classList.add('hidden');
        viewFinished.classList.remove('hidden');
        viewFinished.classList.add('flex');   // fixes the off-center completion screen

        if (!reduceMotion) {
            document.getElementById('finished-icon').animate(
                [{ transform: 'scale(0.6)', opacity: 0 }, { transform: 'scale(1.08)', opacity: 1 }, { transform: 'scale(1)' }],
                { duration: 450, easing: 'ease-out' });
        }

        const ratedIds = Object.keys(ratings);
        if (ratedIds.length === 0) return;

        const total = ratedIds.length;
        const remCount = Object.values(ratings).filter(r => r === 'remembered').length;
        const forgCount = total - remCount;
        const accuracy = Math.round((remCount / total) * 100);

        // document.getElementById('finished-headline').textContent = headlineFor(accuracy);
        document.getElementById('session-summary-stats').classList.remove('hidden');

        if (forgCount > 0) {
            missedCount = forgCount;
            btnMissed.disabled = true;
            btnMissed.textContent = 'Saving…';
            btnMissed.classList.remove('hidden');
            // Demote "Return to Review Hub" to a secondary button
            returnLink.classList.remove('bg-primary', 'text-on-primary', 'shadow-sm', 'hover:bg-primary-container');
            returnLink.classList.add('border', 'border-outline-variant', 'text-on-surface', 'hover:bg-surface-container-low');
        }

        // Confetti pop + stats showcase run alongside the save
        // confettiBurst({ x: 0.5, y: 0.35, count: accuracy >= 80 ? 170 : 120, power: 17 });
        statsDone = showStats(remCount, forgCount, accuracy);

        try {
            await saveSession();
            armMissedButton();
        } catch (error) {
            console.error("Failed to save session batch:", error);
            showSaveError(error.message);
        }
    }

    // 5. Event Listeners
    btnShow.addEventListener('click', showAnswer);
    btnForgot.addEventListener('click', () => rateCard('forgotten'));
    btnRemember.addEventListener('click', () => rateCard('remembered'));
    document.getElementById('end-session-btn').addEventListener('click', endSession);
    btnPrev.addEventListener('click', e => { prevCard(); e.currentTarget.blur(); });
    btnNext.addEventListener('click', e => { skipToNext(); e.currentTarget.blur(); });

    document.addEventListener('keydown', (e) => {
        if (sessionEnded || isAnimating) return;

        switch (e.key) {
            case ' ':
                e.preventDefault();
                if (!isAnswerShown) showAnswer();
                else rateCard('remembered'); // Second spacebar defaults to remember & advance
                break;
            case 'f':
            case 'F':
                if (isAnswerShown) rateCard('forgotten');
                break;
            case 'ArrowLeft':
                prevCard();
                break;
            case 'ArrowRight':
                skipToNext();
                break;
        }
    });

    window.addEventListener('beforeunload', (e) => {
        if (unsavedRatings) { e.preventDefault(); e.returnValue = ''; }
    });

    // -----------------------------------------------------------------------
    // Finished screen: headline, missed-cards button, stats showcase
    // -----------------------------------------------------------------------
    const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

    function headlineFor(accuracy) {
        if (accuracy >= 80) return 'Solid session';
        if (accuracy >= 50) return 'Good progress';
        return 'Those will come back soon';
    }

    // Enabled only after the save succeeded, so forgotten cards are due again on reload
    async function armMissedButton() {
        await statsDone;               // don't let the user jump away mid-stamp
        if (missedCount < 1) return;
        btnMissed.textContent = `Review ${missedCount} missed ${missedCount === 1 ? 'card' : 'cards'}`;
        btnMissed.disabled = false;
    }

    function startMissedSession() {
        const missed = queue.filter(c => ratings[c.id] === 'forgotten');
        if (missed.length === 0) return;

        // save-session.php just gave these cards their "forgot" interval, so mirror it locally
        missed.forEach(c => { c.interval_days = nextInterval(parseInt(c.interval_days, 10) || 0, 'forgotten'); });

        // Fresh session state
        queue = missed;
        currentIndex = 0;
        ratings = {};
        isAnswerShown = false;
        sessionEnded = false;
        unsavedRatings = false;
        sessionStartedAt = Date.now();
        sessionDuration = 0;
        missedCount = 0;

        // Reset header tally and finished screen
        txtRem.textContent = '0';
        txtFor.textContent = '0';
        sumAccuracy.textContent = '0%';
        sumRem.textContent = '0';
        sumFor.textContent = '0';
        document.getElementById('summary-accuracy-bar').style.width = '0%';
        document.getElementById('session-summary-stats').classList.add('hidden');
        document.getElementById('finished-headline').textContent = 'Session Complete!';
        document.getElementById('save-status')?.remove();
        btnMissed.classList.add('hidden');
        returnLink.classList.add('bg-primary', 'text-on-primary', 'shadow-sm', 'hover:bg-primary-container');
        returnLink.classList.remove('border', 'border-outline-variant', 'text-on-surface', 'hover:bg-surface-container-low');

        // Swap views and deal the first missed card
        viewFinished.classList.add('hidden');
        viewFinished.classList.remove('flex');
        viewActive.classList.remove('hidden');
        renderCard();
    }
    btnMissed.addEventListener('click', startMissedSession);

    // function countUp(el, to, { duration = 900, suffix = '' } = {}) {
    //     if (reduceMotion || to === 0) {
    //         el.textContent = to + suffix;
    //         return Promise.resolve();
    //     }
    //     return new Promise(resolve => {
    //         const t0 = performance.now();
    //         const tick = now => {
    //             const p = Math.min(1, (now - t0) / duration);
    //             el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3))) + suffix;
    //             p < 1 ? requestAnimationFrame(tick) : resolve();
    //         };
    //         requestAnimationFrame(tick);
    //     });
    // }

    async function showStats(rem, forg, accuracy) {
        const headline = document.getElementById('finished-headline');
        const bar = document.getElementById('summary-accuracy-bar');
        const tiles = document.querySelectorAll('#session-summary-stats [data-stat-tile]');
        const [tileAcc, tileRem, tileFor] = tiles;   // DOM order: Accuracy, Remembered, Forgotten

        sumAccuracy.textContent = accuracy + '%';
        sumRem.textContent = rem;
        sumFor.textContent = forg;

        if (reduceMotion) {
            bar.style.width = accuracy + '%';
            headline.textContent = headlineFor(accuracy);
            return;
        }

        // Empty frames fade in first; each number stays hidden until its stamp lands
        [sumAccuracy, sumRem, sumFor].forEach(el => { el.style.opacity = '0'; });
        bar.style.width = '0%';
        tiles.forEach(tile => tile.animate(
            [{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'translateY(0)' }],
            { duration: 350, easing: 'ease-out', fill: 'backwards' }));

        await sleep(500);
        await stampStat(sumRem, tileRem, 'border-emerald-400', { scale: 3, duration: 520, shakePx: 4 });
        await sleep(300);
        await stampStat(sumFor, tileFor, 'border-rose-400', { scale: 3, duration: 520, shakePx: 4 });
        await sleep(650);   // beat of suspense before the hero number
        await stampStat(sumAccuracy, tileAcc, 'border-primary', { scale: 4.5, duration: 720, shakePx: 10, hero: true });

        // Climax: bar fills, headline appears, confetti explodes
        bar.animate([{ width: '0%' }, { width: accuracy + '%' }],
            { duration: 900, easing: 'cubic-bezier(.2,.8,.2,1)' });
        bar.style.width = accuracy + '%';

        headline.textContent = headlineFor(accuracy);
        headline.animate(
            [{ opacity: 0, transform: 'translateY(10px)' }, { opacity: 1, transform: 'translateY(0)' }],
            { duration: 350, easing: 'ease-out' });

        confettiBurst({ x: 0.5, y: 0.3, count: accuracy >= 80 ? 170 : 120, power: 17 });
        const n = accuracy >= 80 ? 90 : 60;
        confettiBurst({ x: 0, y: 0.75, count: n, angle: -Math.PI / 3, spread: Math.PI / 3, power: 22 });
        confettiBurst({ x: 1, y: 0.75, count: n, angle: -2 * Math.PI / 3, spread: Math.PI / 3, power: 22 });
    }

    // A number slams in from huge and blurry, squashes on impact, then settles.
    // Resolves at the moment of impact so the caller can chain the next beat.
    function stampStat(el, tile, ringClass, { scale = 3, duration = 520, shakePx = 5, hero = false } = {}) {
        return new Promise(resolve => {
            el.style.opacity = '1';
            el.animate([
                { opacity: 0, transform: `scale(${scale}) rotate(-14deg)`, filter: 'blur(6px)', offset: 0, easing: 'cubic-bezier(.5,0,1,.6)' },
                { opacity: 1, transform: 'scale(0.9) rotate(2deg)', filter: 'blur(0px)', offset: 0.55 },
                { transform: 'scale(1.06) rotate(-1deg)', offset: 0.75 },
                { transform: 'scale(1) rotate(0deg)', offset: 1 }
            ], { duration, easing: 'linear' });

            setTimeout(() => {
                impactRing(tile, ringClass);
                if (hero) setTimeout(() => impactRing(tile, ringClass), 130);
                tile.animate(
                    [{ transform: 'scale(1)' }, { transform: 'scale(0.96)' }, { transform: 'scale(1)' }],
                    { duration: 260, easing: 'ease-out' });
                shake(viewFinished, shakePx);
                resolve();
            }, duration * 0.55);
        });
    }

    function impactRing(tile, colorClass) {
        tile.classList.add('relative');
        const ring = document.createElement('div');
        ring.className = `absolute inset-0 rounded-2xl border-2 pointer-events-none ${colorClass}`;
        tile.appendChild(ring);
        ring.animate(
            [{ opacity: 0.9, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(1.35)' }],
            { duration: 520, easing: 'ease-out' }
        ).finished.then(() => ring.remove());
    }

    function shake(el, px = 6, duration = 380) {
        el.animate([
            { transform: 'translate(0, 0)' },
            { transform: `translate(${-px}px, ${px * 0.5}px)` },
            { transform: `translate(${px}px, ${-px * 0.5}px)` },
            { transform: `translate(${-px * 0.6}px, 0)` },
            { transform: `translate(${px * 0.4}px, 0)` },
            { transform: 'translate(0, 0)' }
        ], { duration, easing: 'ease-out' });
    }

    // -----------------------------------------------------------------------
    // Confetti (tiny canvas engine, no dependencies)
    // -----------------------------------------------------------------------
    const CONFETTI_COLORS = ['#10b981', '#34d399', '#6366f1', '#f59e0b', '#f43f5e', '#38bdf8'];
    let confettiCanvas = null, confettiCtx = null, confettiParts = [], confettiRaf = null;

    function confettiBurst({ x = 0.5, y = 0.4, count = 120, angle = -Math.PI / 2, spread = Math.PI * 2, power = 15 } = {}) {
        if (reduceMotion) return;

        const dpr = window.devicePixelRatio || 1;
        if (!confettiCanvas) {
            confettiCanvas = document.createElement('canvas');
            confettiCanvas.className = 'fixed inset-0 w-full h-full pointer-events-none z-[60]';
            document.body.appendChild(confettiCanvas);
            confettiCtx = confettiCanvas.getContext('2d');
        }
        if (confettiCanvas.width !== Math.round(innerWidth * dpr)) {
            confettiCanvas.width = Math.round(innerWidth * dpr);
            confettiCanvas.height = Math.round(innerHeight * dpr);
        }

        for (let i = 0; i < count; i++) {
            const a = angle + (Math.random() - 0.5) * spread;
            const v = power * (0.5 + Math.random() * 0.7);
            confettiParts.push({
                x: x * innerWidth, y: y * innerHeight,
                vx: Math.cos(a) * v, vy: Math.sin(a) * v,
                w: 6 + Math.random() * 6, h: 4 + Math.random() * 5,
                rot: Math.random() * Math.PI * 2, vr: (Math.random() - 0.5) * 0.35,
                wobble: Math.random() * Math.PI * 2,
                color: CONFETTI_COLORS[(Math.random() * CONFETTI_COLORS.length) | 0],
                life: 0, ttl: 110 + Math.random() * 60
            });
        }
        if (!confettiRaf) confettiRaf = requestAnimationFrame(stepConfetti);
    }

    function stepConfetti() {
        const dpr = window.devicePixelRatio || 1;
        confettiCtx.setTransform(dpr, 0, 0, dpr, 0, 0);
        confettiCtx.clearRect(0, 0, innerWidth, innerHeight);

        confettiParts = confettiParts.filter(p => p.life < p.ttl && p.y < innerHeight + 40);
        for (const p of confettiParts) {
            p.life++;
            p.vx *= 0.985;
            p.vy = p.vy * 0.985 + 0.38;                 // drag + gravity
            p.wobble += 0.12;
            p.x += p.vx + Math.sin(p.wobble) * 0.6;
            p.y += p.vy;
            p.rot += p.vr;

            confettiCtx.save();
            confettiCtx.globalAlpha = Math.min(1, (p.ttl - p.life) / 30);
            confettiCtx.translate(p.x, p.y);
            confettiCtx.rotate(p.rot);
            confettiCtx.scale(1, Math.abs(Math.cos(p.wobble)));   // flutter
            confettiCtx.fillStyle = p.color;
            confettiCtx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            confettiCtx.restore();
        }

        if (confettiParts.length) {
            confettiRaf = requestAnimationFrame(stepConfetti);
        } else {
            confettiRaf = null;
            confettiCanvas.remove();
            confettiCanvas = null;
        }
    }

    // Utilities
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Init
    loadSrsSettings();
    renderCard();
});