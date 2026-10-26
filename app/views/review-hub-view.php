<?php
/** Review Hub dashboard. */
$h = [ReviewHubService::class, 'h'];
$upcomingRows = [];
foreach ($upcomingDecks as $d) {
    $upcomingRows[] = $d;
}
?>
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
  <div class="flex flex-col w-full gap-5 sm:gap-6">
    <h1 class="font-headline-md text-2xl font-bold tracking-tight text-on-surface">Review</h1>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3" aria-label="Review quick actions">
      <a href="<?= $dueTotal > 0 && !empty($dueDecks) ? 'review.php?deck_id=' . (int)$dueDecks[0]['id'] : 'review.php' ?>" class="bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between relative group hover:border-primary/50 hover:shadow-md transition-all min-h-[110px]">
        <div class="absolute right-0 bottom-0 w-16 h-16 bg-primary/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-primary/20 transition-all"></div>
        <div class="relative z-10 flex items-center justify-between w-full">
          <span class="material-symbols-outlined text-primary text-2xl">play_circle</span>
        </div>
        <div class="relative z-10 mt-2">
          <h2 class="font-bold text-on-surface">Review Scheduled Cards</h2>
          <p class="text-sm text-on-surface-variant mt-1"><?= (int)$dueTotal ?> card<?= $dueTotal === 1 ? '' : 's' ?> due now</p>
        </div>
      </a>
      <button type="button" data-open-modal="random-practice-modal" class="text-left bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between relative group hover:border-teal-500/50 hover:shadow-md transition-all min-h-[110px]">
        <div class="absolute right-0 bottom-0 w-16 h-16 bg-teal-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-teal-500/20 transition-all"></div>
        <div class="relative z-10 flex items-center justify-between w-full">
          <span class="material-symbols-outlined text-teal-600 text-2xl">shuffle</span>
        </div>
        <div class="relative z-10 mt-2">
          <h2 class="font-bold text-on-surface">Review Deck in Random</h2>
          <p class="text-sm text-on-surface-variant mt-1">Practice any active cards in shuffled order</p>
        </div>
      </button>
      <button type="button" data-open-modal="upcoming-modal" class="text-left bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between relative group hover:border-error/50 hover:shadow-md transition-all min-h-[110px]">
        <div class="absolute right-0 bottom-0 w-16 h-16 bg-rose-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-rose-500/20 transition-all"></div>
        <div class="relative z-10 flex items-center justify-between w-full">
          <span class="material-symbols-outlined text-error text-2xl">event_upcoming</span>
        </div>
        <div class="relative z-10 mt-2">
          <h2 class="font-bold text-on-surface">View Upcoming</h2>
          <p class="text-sm text-on-surface-variant mt-1"><?= array_sum(array_column($upcomingDecks, 'upcomingCount')) ?> cards scheduled in the next <?= (int)ReviewHubService::UPCOMING_DAYS ?> days</p>
        </div>
      </button>
      <a href="sessions.php" class="bg-gradient-to-br from-indigo-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex flex-col justify-between relative group hover:border-indigo-500/50 hover:shadow-md transition-all min-h-[110px]">
        <div class="absolute right-0 bottom-0 w-16 h-16 bg-indigo-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-indigo-500/20 transition-all"></div>
        <div class="relative z-10 flex items-center justify-between w-full">
          <span class="material-symbols-outlined text-indigo-600 text-2xl">history</span>
        </div>
        <div class="relative z-10 mt-2">
          <h2 class="font-bold text-on-surface">View Session History</h2>
          <p class="text-sm text-on-surface-variant mt-1">Browse completed review sessions</p>
        </div>
      </a>
    </section>

    <section class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest overflow-hidden">
      <div class="p-4 sm:p-5 border-b border-outline-variant/20 flex items-center justify-between gap-3">
        <div><h2 class="text-lg font-bold text-on-surface">Due Now</h2><p class="text-sm text-on-surface-variant mt-1">Choose a deck to start its scheduled review.</p></div>
        <span class="rounded-full px-3 py-1 bg-primary/10 text-primary text-sm font-bold tabular-nums"><?= (int)$dueTotal ?></span>
      </div>
      <?php if (empty($dueDecks)): ?>
        <p class="p-6 text-sm text-on-surface-variant">No cards are due right now. You can use Random Practice or check upcoming cards.</p>
      <?php else: ?>
      <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-surface-container-low text-on-surface-variant"><tr><th class="text-left font-semibold px-4 py-3">Deck</th><th class="text-right font-semibold px-4 py-3">New</th><th class="text-right font-semibold px-4 py-3">Learning</th><th class="text-right font-semibold px-4 py-3">Young</th><th class="text-right font-semibold px-4 py-3">Mastered</th><th class="text-right font-semibold px-4 py-3">Due</th></tr></thead><tbody>
      <?php foreach ($dueDecks as $d): ?><tr class="border-t border-outline-variant/20 hover:bg-surface-container-low/40"><td class="px-4 py-3"><a class="font-semibold text-primary hover:underline" href="review.php?deck_id=<?= (int)$d['id'] ?>"><?= $h($d['title']) ?></a></td><td class="px-4 py-3 text-right tabular-nums"><?= (int)$d['newDueCount'] ?></td><td class="px-4 py-3 text-right tabular-nums"><?= (int)$d['learningDueCount'] ?></td><td class="px-4 py-3 text-right tabular-nums"><?= (int)$d['youngDueCount'] ?></td><td class="px-4 py-3 text-right tabular-nums"><?= (int)$d['masteredDueCount'] ?></td><td class="px-4 py-3 text-right font-bold tabular-nums"><?= (int)$d['dueCount'] ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </section>

    <section class="rounded-2xl border border-outline-variant/30 bg-surface-container-lowest overflow-hidden">
      <div class="p-4 sm:p-5 border-b border-outline-variant/20 flex items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-on-surface">Recent Sessions</h2><p class="text-sm text-on-surface-variant mt-1">Read-only overview of your latest sessions. Random Practice is stored separately from SRS statistics.</p></div><a href="sessions.php" class="text-sm font-semibold text-primary hover:underline">All history</a></div>
      <?php if (empty($recentSessions)): ?><p class="p-6 text-sm text-on-surface-variant">No completed scheduled review sessions yet.</p><?php else: ?>
      <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-surface-container-low text-on-surface-variant"><tr><th class="text-left font-semibold px-4 py-3">Deck</th><th class="text-right font-semibold px-4 py-3">Cards</th><th class="text-right font-semibold px-4 py-3">Accuracy</th><th class="text-right font-semibold px-4 py-3">Duration</th><th class="text-right font-semibold px-4 py-3">When</th></tr></thead><tbody>
      <?php foreach ($recentSessions as $s): ?><tr class="border-t border-outline-variant/20"><td class="px-4 py-3"><a class="font-semibold text-primary hover:underline" href="<?= $s['sessionType'] === 'random' ? 'deck.php?deck_id=' . (int)$s['deckId'] : 'session-details.php?id=' . (int)$s['sessionID'] ?>"><?= $s['sessionType'] === 'random' ? $h($s['sessionLabel'] . ' · ' . $s['deckTitle']) : $h($s['deckTitle']) ?></a></td><td class="px-4 py-3 text-right tabular-nums"><?= (int)$s['cardsReviewed'] ?></td><td class="px-4 py-3 text-right tabular-nums"><?= (int)$s['accuracy'] ?>%</td><td class="px-4 py-3 text-right tabular-nums"><?= $h(ReviewHubService::formatDuration($s['durationSeconds'])) ?></td><td class="px-4 py-3 text-right text-on-surface-variant whitespace-nowrap"><?= $h($s['formattedTime']) ?></td></tr><?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </section>
  </div>
</div>

<!-- Upcoming cards modal -->
<div id="upcoming-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="upcoming-modal-title">
 <div class="w-full max-w-3xl max-h-[90dvh] overflow-hidden rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xl flex flex-col">
  <div class="flex items-start justify-between gap-4 p-5 border-b border-outline-variant/20"><div><h2 id="upcoming-modal-title" class="text-lg font-bold text-on-surface">Upcoming Cards</h2><p class="text-sm text-on-surface-variant mt-1">Cards scheduled within the next <?= (int)ReviewHubService::UPCOMING_DAYS ?> days.</p></div><button type="button" data-close-modal class="rounded-lg p-2 hover:bg-surface-container-high" aria-label="Close upcoming cards"><span class="material-symbols-outlined">close</span></button></div>
  <div class="overflow-auto p-4 sm:p-5">
   <?php if (empty($upcomingDecks)): ?><p class="text-sm text-on-surface-variant py-8 text-center">No upcoming cards in the next <?= (int)ReviewHubService::UPCOMING_DAYS ?> days.</p><?php else: ?>
   <table class="w-full text-sm"><thead class="bg-surface-container-low text-on-surface-variant"><tr><th class="text-left px-3 py-3">Deck</th><th class="text-right px-3 py-3">Cards soon</th><th class="text-right px-3 py-3">Next due</th></tr></thead><tbody>
   <?php foreach ($upcomingDecks as $d): ?><tr class="border-t border-outline-variant/20"><td class="px-3 py-3"><a class="font-semibold text-primary hover:underline" href="review.php?deck_id=<?= (int)$d['id'] ?>"><?= $h($d['title']) ?></a><?php if ((int)$d['dueCount'] > 0): ?><span class="ml-2 text-xs text-rose-700 font-semibold">Due now too</span><?php endif; ?></td><td class="px-3 py-3 text-right tabular-nums"><?= (int)$d['upcomingCount'] ?></td><td class="px-3 py-3 text-right text-on-surface-variant whitespace-nowrap"><?= $d['nextDueSeconds'] !== null ? $h(ReviewHubService::formatSpan((int)$d['nextDueSeconds'])) : '—' ?></td></tr><?php endforeach; ?>
   </tbody></table><?php endif; ?>
  </div>
  <div class="p-4 border-t border-outline-variant/20 flex justify-end"><button type="button" data-close-modal class="px-4 py-2 rounded-xl border border-outline-variant text-sm font-semibold hover:bg-surface-container-low">Close</button></div>
 </div>
</div>

<!-- Random Practice deck selector -->
<div id="random-practice-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/50 p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="random-practice-title">
 <form id="random-practice-form" class="w-full max-w-lg rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xl overflow-hidden">
  <div class="flex items-start justify-between gap-4 p-5 border-b border-outline-variant/20"><div><h2 id="random-practice-title" class="text-lg font-bold text-on-surface">Review Deck in Random</h2><p class="text-sm text-on-surface-variant mt-1">All active cards are shuffled. This practice is saved with a label but never changes SRS scheduling or statistics.</p></div><button type="button" data-close-modal class="rounded-lg p-2 hover:bg-surface-container-high" aria-label="Close deck selector"><span class="material-symbols-outlined">close</span></button></div>
  <div class="p-5"><label for="random-practice-deck" class="block text-sm font-semibold text-on-surface mb-2">Choose a deck</label><select id="random-practice-deck" required class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest p-3 text-sm text-on-surface"><option value="">Select a deck</option><?php foreach ($practiceDecks as $d): ?><option value="<?= (int)$d['id'] ?>"><?= $h($d['title']) ?> (<?= (int)$d['activeCount'] ?> active cards)</option><?php endforeach; ?></select><?php if (empty($practiceDecks)): ?><p class="text-sm text-on-surface-variant mt-3">No decks have active cards yet.</p><?php endif; ?></div>
  <div class="p-4 border-t border-outline-variant/20 flex justify-end gap-2"><button type="button" data-close-modal class="px-4 py-2 rounded-xl border border-outline-variant text-sm font-semibold hover:bg-surface-container-low">Cancel</button><button type="submit" <?= empty($practiceDecks) ? 'disabled' : '' ?> class="px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-bold disabled:opacity-50">Start Random Practice</button></div>
 </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-open-modal]').forEach(function (btn) { btn.addEventListener('click', function () { var m = document.getElementById(btn.dataset.openModal); if (m) { m.classList.remove('hidden'); m.classList.add('flex'); var focus = m.querySelector('button,select'); if (focus) focus.focus(); } }); });
  document.querySelectorAll('[data-close-modal]').forEach(function (btn) { btn.addEventListener('click', function () { var m = btn.closest('[role="dialog"]'); if (m) { m.classList.add('hidden'); m.classList.remove('flex'); } }); });
  document.querySelectorAll('[role="dialog"]').forEach(function (m) { m.addEventListener('click', function (e) { if (e.target === m) { m.classList.add('hidden'); m.classList.remove('flex'); } }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach(function (m) { m.classList.add('hidden'); m.classList.remove('flex'); }); });
  var form = document.getElementById('random-practice-form');
  if (form) form.addEventListener('submit', function (e) { e.preventDefault(); var id = document.getElementById('random-practice-deck').value; if (id) window.location.href = 'review.php?mode=random&deck_id=' + encodeURIComponent(id); });
});
</script>
