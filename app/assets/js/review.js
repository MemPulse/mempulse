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

        // Calculate and show projected dates
        const interval = parseInt(card.interval_days) || 1;
        projForgot.textContent = "Due: Today";
        const remDays = interval === 0 ? 1 : interval * 2;
        projRemember.textContent = `Due: ${remDays} ${remDays === 1 ? 'day' : 'days'}`;

        hideAnswer();
    }

    function showAnswer() {
        if (sessionEnded) return;
        isAnswerShown = true;
        elBack.classList.remove('hidden');
        elDivider.classList.remove('hidden');
        btnShow.classList.add('hidden');
        boxRates.classList.remove('hidden');
    }

    function hideAnswer() {
        isAnswerShown = false;
        elBack.classList.add('hidden');
        elDivider.classList.add('hidden');
        btnShow.classList.remove('hidden');
        boxRates.classList.add('hidden');
    }

    // 2. Rating & Tally Logic
    function rateCard(status) {
        const card = queue[currentIndex];
        ratings[card.id] = status;

        updateTally();
        showFeedbackToast(status);

        currentIndex++;
        renderCard();
    }

    function updateTally() {
        let rem = 0, forg = 0;
        Object.values(ratings).forEach(r => r === 'remembered' ? rem++ : forg++);
        txtRem.textContent = rem;
        txtFor.textContent = forg;
    }

    function showFeedbackToast(status) {
        const toast = document.getElementById('review-toast');
        const icon = document.getElementById('review-toast-icon');
        const msg = document.getElementById('review-toast-msg');

        toast.classList.remove('translate-y-12', 'opacity-0', 'bg-emerald-600', 'text-white', 'bg-rose-600');
        if (status === 'remembered') {
            toast.classList.add('bg-emerald-600', 'text-white');
            icon.textContent = 'check_circle';
            msg.textContent = 'Remembered';
        } else {
            toast.classList.add('bg-rose-600', 'text-white');
            icon.textContent = 'cancel';
            msg.textContent = 'Forgotten';
        }

        // Pop in
        setTimeout(() => toast.classList.remove('translate-y-12', 'opacity-0'), 10);
        // Fade out
        setTimeout(() => toast.classList.add('translate-y-12', 'opacity-0'), 1200);
    }

    // 3. Backward/Forward Queue Navigation
    function prevCard() {
        if (currentIndex > 0) {
            currentIndex--;
            renderCard();
        }
    }

    function skipToNext() {
        // Allows skipping forward without rating IF it was already rated
        if (ratings[queue[currentIndex].id]) {
            currentIndex++;
            renderCard();
        }
    }

    // 4. Batch Save & Session End
    async function endSession() {
        sessionEnded = true;
        viewActive.classList.add('hidden');
        viewFinished.classList.remove('hidden');

        const ratedIds = Object.keys(ratings);
        if (ratedIds.length === 0) return;

        // Display Summary
        let remCount = 0;
        Object.values(ratings).forEach(r => r === 'remembered' && remCount++);

        document.getElementById('session-summary-stats').classList.remove('hidden');
        document.getElementById('summary-total').textContent = ratedIds.length;
        document.getElementById('summary-accuracy').textContent = Math.round((remCount / ratedIds.length) * 100) + '%';

        // Fire & Forget API Save
        try {
            await fetch('../api/save-session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    deck_id: data.deckId,
                    results: ratings
                })
            });
        } catch (error) {
            console.error("Failed to save session batch:", error);
        }
    }

    // 5. Event Listeners
    btnShow.addEventListener('click', showAnswer);
    btnForgot.addEventListener('click', () => rateCard('forgotten'));
    btnRemember.addEventListener('click', () => rateCard('remembered'));
    document.getElementById('end-session-btn').addEventListener('click', endSession);

    document.addEventListener('keydown', (e) => {
        if (sessionEnded) return;

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

    // Utilities
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Init
    renderCard();
});