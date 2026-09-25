<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MemPulse - Flashcard Review</title>
  <style>
    :root {
      --bg-main: #f8fafd;
      --bg-sidebar: #f0f4f9;
      --bg-card: #ffffff;
      --text-primary: #1f1f1f;
      --text-secondary: #444746;
      --accent-color: #0b57d0;
      --accent-soft: #e8f0fe;
      --danger-color: #b3261e;
      --danger-soft: #fce8e6;
      --success-color: #146c2e;
      --success-soft: #e6f4ea;
      --border-color: #e0e2e0;
      --radius-lg: 16px;
      --radius-md: 12px;
      --radius-pill: 28px;
      --sidebar-width: 260px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { display: flex; height: 100vh; background-color: var(--bg-main); color: var(--text-primary); overflow: hidden; }

    /* SIDEBAR */
    aside {
      width: var(--sidebar-width);
      background-color: var(--bg-sidebar);
      padding: 16px 12px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      border-right: 1px solid var(--border-color);
    }
    .brand { font-size: 1.25rem; font-weight: 600; color: var(--accent-color); padding: 12px 16px; }
    .nav-list { list-style: none; }
    .nav-item {
      display: flex; align-items: center; padding: 10px 16px; margin-bottom: 4px;
      border-radius: var(--radius-pill); color: var(--text-secondary); font-size: 0.9rem; font-weight: 500;
      text-decoration: none; cursor: pointer;
    }
    .nav-item.active { background-color: #c2e7ff; color: #001d35; font-weight: 600; }
    .nav-icon { margin-right: 12px; }

    /* REVIEW MAIN CONTENT */
    main {
      flex: 1; padding: 32px; display: flex; flex-direction: column;
      align-items: center; justify-content: space-between; max-width: 900px; margin: 0 auto;
    }
    .session-header { width: 100%; display: flex; justify-content: space-between; align-items: center; }
    .nav-controls { display: flex; align-items: center; gap: 12px; }
    .session-counter { font-size: 0.85rem; color: var(--text-secondary); font-weight: 500; min-width: 90px; text-align: center; }

    .btn-skip {
      border: 1px solid var(--border-color); background-color: var(--bg-card);
      padding: 6px 14px; border-radius: var(--radius-pill); font-size: 0.85rem; cursor: pointer;
    }
    .btn-skip:disabled { opacity: 0.3; cursor: not-allowed; border-color: transparent; background: transparent; }

    .exit-btn { color: var(--text-secondary); text-decoration: none; font-size: 0.85rem; padding: 6px 12px; border-radius: var(--radius-pill); background-color: var(--bg-sidebar); }

    /* CARD DISPLAY */
    .flashcard-wrapper { width: 100%; max-width: 600px; height: 320px; margin: auto 0; }
    .flashcard {
      width: 100%; height: 100%; background-color: var(--bg-card); border: 1px solid var(--border-color);
      border-radius: var(--radius-lg); padding: 32px; display: flex; flex-direction: column;
      justify-content: center; align-items: center; text-align: center;
    }
    .card-label { font-size: 0.75rem; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 16px; font-weight: 600; }
    .card-text { font-size: 1.35rem; font-weight: 500; }
    .card-answer { margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-color); width: 100%; color: var(--accent-color); font-size: 1.15rem; display: none; }

    /* CONTROLS */
    .controls-container { width: 100%; max-width: 600px; display: flex; justify-content: center; gap: 16px; min-height: 52px; }
    .btn { border: none; padding: 12px 28px; border-radius: var(--radius-pill); font-size: 0.9rem; font-weight: 600; cursor: pointer; }
    .btn-reveal { background-color: var(--accent-color); color: #fff; width: 100%; }
    .btn-forgot { background-color: var(--danger-soft); color: var(--danger-color); flex: 1; }
    .btn-remembered { background-color: var(--success-soft); color: var(--success-color); flex: 1; }
    .multiplier-hint { display: block; font-size: 0.7rem; font-weight: 400; margin-top: 2px; }
  </style>
</head>
<body>

  <aside>
    <div>
      <div class="brand">MemPulse</div>
      <ul class="nav-list">
        <li><a href="#" class="nav-item"> <span class="nav-icon">🏠</span> Home </a></li>
        <li><a href="#" class="nav-item active"> <span class="nav-icon">📚</span> Decks </a></li>
      </ul>
    </div>
  </aside>

  <main>
    <div class="session-header">
      <h1>Flashcard Review</h1>
      <div class="nav-controls">
        <button id="prevBtn" class="btn-skip" onclick="navigateCard(-1)" disabled>← Prev</button>
        <span id="sessionCounter" class="session-counter">Loading...</span>
        <button id="nextBtn" class="btn-skip" onclick="navigateCard(1)" disabled>Next →</button>
      </div>
      <a href="dashboard.php" class="exit-btn">✕ Exit</a>
    </div>

    <div class="flashcard-wrapper">
      <div class="flashcard">
        <div class="card-label">Front</div>
        <div id="cardFront" class="card-text">Loading due cards...</div>
        
        <div id="cardAnswer" class="card-answer">
          <div class="card-label" style="color: var(--accent-color);">Back</div>
          <div id="cardBack">---</div>
        </div>
      </div>
    </div>

    <div class="controls-container">
      <button id="revealBtn" class="btn btn-reveal" onclick="revealCard()">Show Answer</button>

      <button id="forgotBtn" class="btn btn-forgot" style="display: none;" onclick="rateCard('forgot')">
        Forgot
        <span id="forgotHint" class="multiplier-hint"></span>
      </button>

      <button id="rememberedBtn" class="btn btn-remembered" style="display: none;" onclick="rateCard('remembered')">
        Remembered
        <span id="rememberedHint" class="multiplier-hint"></span>
      </button>
    </div>
  </main>

  <script>
    const deckId = 1; // Replace dynamically from URL query params (e.g. ?deck_id=1)
    let cards = [];
    let sessionId = null;
    let currentIndex = 0;

    // Fetch due cards on load
    async function loadSession() {
      try {
        const response = await fetch(`api_review.php?action=fetch_cards&deck_id=${deckId}`);
        const data = await response.json();

        if (data.cards && data.cards.length > 0) {
          cards = data.cards;
          sessionId = data.session_id;
          renderCard();
        } else {
          document.getElementById('cardFront').innerText = "🎉 All done! No cards due for review in this deck.";
          document.getElementById('sessionCounter').innerText = "0 of 0";
          document.getElementById('revealBtn').disabled = true;
        }
      } catch (err) {
        document.getElementById('cardFront').innerText = "Error loading cards. Check database connection.";
      }
    }

    function renderCard() {
      const currentCard = cards[currentIndex];

      document.getElementById('cardFront').innerText = currentCard.front;
      document.getElementById('cardBack').innerText = currentCard.back;
      document.getElementById('sessionCounter').innerText = `Card ${currentIndex + 1} of ${cards.length}`;

      // Calculate predicted intervals for hints
      const forgotNext = Math.max(1, Math.round(currentCard.interval_days * currentCard.forgot_multiplier));
      const remNext = Math.max(1, Math.round(currentCard.interval_days * currentCard.remember_multiplier));

      document.getElementById('forgotHint').innerText = `Next: ${forgotNext}d (x${currentCard.forgot_multiplier})`;
      document.getElementById('rememberedHint').innerText = `Next: ${remNext}d (x${currentCard.remember_multiplier})`;

      // Reset reveal
      document.getElementById('cardAnswer').style.display = 'none';
      document.getElementById('revealBtn').style.display = 'block';
      document.getElementById('forgotBtn').style.display = 'none';
      document.getElementById('rememberedBtn').style.display = 'none';

      // Update button states
      document.getElementById('prevBtn').disabled = currentIndex === 0;
      document.getElementById('nextBtn').disabled = currentIndex === cards.length - 1;
    }

    function navigateCard(dir) {
      if (currentIndex + dir >= 0 && currentIndex + dir < cards.length) {
        currentIndex += dir;
        renderCard();
      }
    }

    function revealCard() {
      document.getElementById('cardAnswer').style.display = 'block';
      document.getElementById('revealBtn').style.display = 'none';
      document.getElementById('forgotBtn').style.display = 'block';
      document.getElementById('rememberedBtn').style.display = 'block';
    }

    async function rateCard(rating) {
      const card = cards[currentIndex];
      const mult = rating === 'remembered' ? card.remember_multiplier : card.forgot_multiplier;

      const payload = {
        session_id: sessionId,
        card_id: card.id,
        rating: rating,
        previous_interval: card.interval_days,
        multiplier: mult
      };

      await fetch('api_review.php?action=rate_card', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      // Move to next card or complete session
      if (currentIndex < cards.length - 1) {
        cards.splice(currentIndex, 1); // Remove reviewed card from dynamic queue
        renderCard();
      } else {
        cards.splice(currentIndex, 1);
        if (cards.length === 0) {
          alert("Review session complete!");
          window.location.reload();
        } else {
          currentIndex = 0;
          renderCard();
        }
      }
    }

    loadSession();
  </script>
</body>
</html>