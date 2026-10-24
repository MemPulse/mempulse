<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/ReviewHubService.php';
require_once __DIR__ . '/../api/PracticeService.php';

// Anti-CSRF token, sent along with the batch save in review.js
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int) $_SESSION['user_id'];
$deckId = isset($_GET['deck_id']) && ctype_digit((string) $_GET['deck_id']) ? (int) $_GET['deck_id'] : null;

$isReviewing = false;
$dueCards = [];
$hubDecks = [];

// PRACTICE MODE (read-only): replay chosen cards without touching the schedule.
//   review.php?deck_id=12&mode=practice&from=deck&cards=5,9,14
//   review.php?mode=practice&from=sessions&session_id=88
$isPractice   = (($_GET['mode'] ?? '') === 'practice');
$practiceSkipped = 0;
$returnUrl    = 'review.php';
$practiceSessionId = 0;

if ($isPractice) {
    $isReviewing = true;
    $from = (string) ($_GET['from'] ?? '');
    $practiceSessionId = isset($_GET['session_id']) && ctype_digit((string) $_GET['session_id']) ? (int) $_GET['session_id'] : 0;

    if ($practiceSessionId > 0) {
        // From session history: all cards of that session, in the order they were reviewed
        $info = PracticeService::sessionInfo($conn, $userId, $practiceSessionId);
        if ($info === null) {
            header("Location: review.php");
            exit;
        }
        $deckId   = $info['deckId'];
        $deckName = $info['deckTitle'];
        $dueCards = PracticeService::cardsFromSession($conn, $userId, $practiceSessionId);
        $practiceSkipped = max(0, $info['requested'] - count($dueCards));
    } else {
        // From the deck page: the selected card ids, in grid order
        $ids = PracticeService::parseIds($_GET['cards'] ?? '');
        if (!$deckId || !$ids) {
            header("Location: review.php");
            exit;
        }
        $deckName = null;
        if ($stmt = mysqli_prepare($conn, "SELECT title FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NULL")) {
            mysqli_stmt_bind_param($stmt, "ii", $deckId, $userId);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            $deckName = $row['title'] ?? null;
            mysqli_stmt_close($stmt);
        }
        if ($deckName === null) {
            header("Location: review.php");
            exit;
        }
        $dueCards = PracticeService::cardsByIds($conn, $userId, $deckId, $ids);
        $practiceSkipped = max(0, count($ids) - count($dueCards));
    }
    $returnUrl = PracticeService::returnUrl($from, $deckId ?? 0, $practiceSessionId);

} elseif ($deckId) {
    // ACTIVE REVIEW STATE: Fetch due cards for the specific deck
    $isReviewing = true;
    // Ownership check: only this user's non-archived decks, non-archived cards
    $sql = "SELECT f.id, f.front, f.back, f.interval_days, f.next_review_at
            FROM flashcards f
            JOIN decks d ON d.id = f.deck_id
            WHERE f.deck_id = ? AND d.user_id = ? AND d.deleted_at IS NULL
              AND f.deleted_at IS NULL AND f.next_review_at <= NOW()
            ORDER BY f.next_review_at ASC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $deckId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $dueCards[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
    $deckName = null;
    if ($stmt = mysqli_prepare($conn, "SELECT title FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NULL")) {
        mysqli_stmt_bind_param($stmt, "ii", $deckId, $userId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $deckName = $row['title'] ?? null;
        mysqli_stmt_close($stmt);
    }
    if ($deckName === null) {
        header("Location: review.php");
        exit;
    }
} else {
    // HUB STATE: table data + call-to-action data
    $hubDecks       = ReviewHubService::getHubDecks($conn, $userId);
    $dueDecks       = ReviewHubService::dueDecks($hubDecks);
    $upcomingDecks  = ReviewHubService::upcomingDecks($hubDecks);
    $recentSessions = ReviewHubService::getRecentSessions($conn, $userId, 8);

    $streakInfo     = ReviewHubService::getStreakInfo($conn, $userId);
    $curStreak      = $streakInfo['currentStreak'];
    $reviewedToday  = $streakInfo['reviewedToday'];

    $dueTotal       = array_sum(array_column($dueDecks, 'dueCount'));
    $nextDueSecs    = $dueTotal === 0 ? ReviewHubService::getNextDueSeconds($conn, $userId) : null;
}

$pageTitle = $isReviewing ? "Active Review" : "Review Hub"; 
$currentPage = "review";
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen">
    <?php if ($isReviewing): ?>
        <script>
            // Collapse the sidebar for the live session; remember the user's own preference.
            if (sessionStorage.getItem('sidebarPrev') === null) {
                sessionStorage.setItem('sidebarPrev', localStorage.getItem('sidebarCollapsed') ?? 'false');
            }
            localStorage.setItem('sidebarCollapsed', 'true');
        </script>
    <?php endif; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 transition-all duration-300 ease-in-out">
                                
            <?php if (!$isReviewing): ?>
                <?php include __DIR__ . '/review-hub-view.php'; ?>

            <?php else: ?>
                <!-- Practice mode banner (JS also shows it when a finished review switches to practice) -->
                <div id="practice-banner" class="<?= $isPractice ? '' : 'hidden' ?> max-w-[55rem] mx-auto mb-6 sm:px-14">
                    <div class="rounded-xl bg-surface-container-low border border-outline-variant/30 px-4 py-2.5 text-sm text-on-surface">
                        <p class="font-bold flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] text-primary">play_arrow</span>
                            Practice mode: results are not saved
                        </p>
                        <?php if ($isPractice && $practiceSkipped > 0): ?>
                            <p id="practice-skipped" class="mt-1 text-xs text-on-surface-variant">
                                <?= $practiceSkipped === 1
                                    ? '1 card was skipped because it was archived or deleted.'
                                    : (int) $practiceSkipped . ' cards were skipped because they were archived or deleted.' ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($isPractice && empty($dueCards)): ?>
                    <!-- Practice with no cards left -->
                    <div id="practice-empty" class="max-w-2xl mx-auto flex flex-col items-center justify-center text-center pb-12">
                        <div class="w-20 h-20 bg-surface-container-high text-on-surface-variant rounded-full flex items-center justify-center mb-6">
                            <span class="material-symbols-outlined text-[40px]">inventory_2</span>
                        </div>
                        <h2 class="font-headline-md text-3xl font-extrabold text-on-surface mb-2">No cards to practice</h2>
                        <p class="text-on-surface-variant mb-8">These cards have been archived or deleted since the session.</p>
                        <a href="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>" class="px-6 py-3 rounded-xl bg-primary text-on-primary font-bold shadow-sm hover:bg-primary-container transition-colors">Back</a>
                    </div>
                <?php endif; ?>

                <!-- STATE B: ACTIVE REVIEW INTERFACE -->
                <div id="active-review-container" class="max-w-[55rem] mx-auto flex flex-col pt-6 sm:pt-10 min-h-[calc(100dvh-var(--app-header-h)-3rem)] <?= empty($dueCards) ? 'hidden' : '' ?>">                    <!-- Session Header -->
                    <div class="mb-6 sm:px-14">
                        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                            <button id="end-session-btn" class="justify-self-start p-1 -ml-1 text-on-surface-variant hover:text-rose-600 transition-colors" title="End session">
                                <span class="material-symbols-outlined">close</span>
                            </button>

                            <div class="text-center min-w-0 max-w-[55vw] sm:max-w-sm">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-primary truncate"><?= htmlspecialchars($deckName, ENT_QUOTES, 'UTF-8') ?></p>
                                <p id="progress-text" class="text-xs text-on-surface-variant">Card 1 of <?= count($dueCards) ?></p>
                            </div>

                            <div class="justify-self-end flex items-center gap-2.5 text-sm font-bold bg-surface-container-lowest border border-outline-variant/30 px-3 py-1.5 rounded-xl">
                                <span class="text-emerald-600 flex items-center gap-1" title="Remembered">
                                    <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                    <span id="tally-remembered" class="inline-block tabular-nums">0</span>
                                </span>
                                <span class="text-outline-variant">|</span>
                                <span class="text-rose-600 flex items-center gap-1" title="Forgotten">
                                    <span class="material-symbols-outlined text-[16px]">cancel</span>
                                    <span id="tally-forgotten" class="inline-block tabular-nums">0</span>
                                </span>
                            </div>
                        </div>

                        <div class="mt-3 w-full bg-surface-container-high rounded-full h-1 overflow-hidden">
                            <div id="progress-bar" class="bg-primary h-1 rounded-full transition-[width] duration-300 ease-out" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Flashcard Stage -->
                    <div class="flex-1 flex flex-col items-center justify-center min-h-[350px]">
                        <div class="flex items-center gap-2 sm:gap-4 w-full">

                            <!-- Prev: left margin -->
                            <button id="prev-card-btn" type="button" title="Previous card (←)" aria-label="Previous card"
                                class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center bg-surface-container-lowest border border-outline-variant/40 text-on-surface-variant hover:text-primary hover:border-primary/50 hover:bg-surface-container-low transition disabled:opacity-30 disabled:pointer-events-none">
                                <span class="material-symbols-outlined text-[22px]">chevron_left</span>
                            </button>

                            <div id="flashcard-stage" class="flex-1 min-w-0 bg-surface-container-lowest border border-outline-variant/30 shadow-md rounded-2xl p-8 sm:p-12 text-center relative overflow-hidden">

                                <!-- Rating Badge (shows briefly when navigating backwards) -->
                                <div id="historical-rating-badge" class="hidden absolute top-4 right-4 px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider"></div>

                                <!-- Front -->
                                <div id="card-front-content" class="text-xl sm:text-2xl font-medium text-on-surface"></div>

                                <!-- Divider -->
                                <hr id="card-divider" class="my-8 border-outline-variant/30 hidden">

                                <!-- Back -->
                                <div id="card-back-content" class="text-lg sm:text-xl text-on-surface-variant hidden"></div>
                            </div>

                            <!-- Next: right margin -->
                            <button id="next-card-btn" type="button" title="Next card (→)" aria-label="Next card"
                                class="shrink-0 w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center bg-surface-container-lowest border border-outline-variant/40 text-on-surface-variant hover:text-primary hover:border-primary/50 hover:bg-surface-container-low transition disabled:opacity-30 disabled:pointer-events-none">
                                <span class="material-symbols-outlined text-[22px]">chevron_right</span>
                            </button>

                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="mt-6 flex flex-col items-center gap-3 sm:px-14">
                        <div class="flex justify-center w-full min-h-[56px]">
                            <button id="show-answer-btn" class="px-8 py-3 rounded-xl bg-primary text-on-primary font-bold shadow-md hover:bg-primary-container hover:-translate-y-0.5 transition-all w-full max-w-sm">
                                Show Answer
                            </button>

                            <div id="rate-actions" class="hidden flex gap-3 w-full max-w-lg">
                                <button id="rate-again-btn" type="button" class="flex-1 flex flex-col items-center justify-center py-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 active:scale-[0.97] transition shadow-sm">
                                    <span class="font-bold">Again</span>
                                    <span id="projected-again-date" class="text-[11px] font-medium opacity-80 mt-0.5">this session</span>
                                </button>
                                <button id="rate-forgot-btn" class="flex-1 flex flex-col items-center justify-center py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 active:scale-[0.97] transition shadow-sm">
                                    <span class="font-bold">Forgot</span>
                                    <span id="projected-forgot-date" class="text-[11px] font-medium opacity-80 mt-0.5">Again today</span>
                                </button>
                                <button id="rate-remember-btn" class="flex-1 flex flex-col items-center justify-center py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 active:scale-[0.97] transition shadow-sm">
                                    <span class="font-bold">Remember</span>
                                    <span id="projected-remember-date" class="text-[11px] font-medium opacity-80 mt-0.5">in 2 days</span>
                                </button>
                            </div>
                        </div>

                        <p class="hidden md:block text-[11px] text-on-surface-variant/60 select-none">
                            <span id="hint-reveal"><kbd class="px-1.5 py-0.5 rounded border border-outline-variant/50 bg-surface-container-low font-mono text-[10px]">Space</kbd> reveal</span>
                            <span id="hint-rate" class="hidden">
                                <kbd class="px-1.5 py-0.5 rounded border border-outline-variant/50 bg-surface-container-low font-mono text-[10px]">A</kbd> again
                                <span class="mx-1" aria-hidden="true">·</span>
                                <kbd class="px-1.5 py-0.5 rounded border border-outline-variant/50 bg-surface-container-low font-mono text-[10px]">F</kbd> forgot
                                <span class="mx-1" aria-hidden="true">·</span>
                                <kbd class="px-1.5 py-0.5 rounded border border-outline-variant/50 bg-surface-container-low font-mono text-[10px]">Space</kbd> remember
                            </span>
                        </p>
                    </div>

                </div>

                <!-- STATE C: ZERO CARDS / FINISHED SCREEN -->
                <div id="review-finished-screen" class="max-w-2xl mx-auto flex-col items-center justify-center text-center pt-6 sm:pt-10 min-h-[calc(100dvh-var(--app-header-h)-3rem)] pb-12 <?= (empty($dueCards) && !$isPractice) ? 'flex' : 'hidden' ?>">                    <div id="finished-icon" class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-6">
                    <span class="material-symbols-outlined text-[40px]">task_alt</span>
                    </div>
                    <h2 id="finished-headline" class="font-headline-md text-3xl font-extrabold text-on-surface mb-2">Session Complete!</h2>
                    <p class="text-on-surface-variant mb-8">You've finished <span class="font-semibold text-on-surface"><?= htmlspecialchars($deckName, ENT_QUOTES, 'UTF-8') ?></span>. Great work.</p>

                    <div id="session-summary-stats" class="grid grid-cols-3 gap-3 w-full max-w-md mb-8 hidden">
                        <div data-stat-tile class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Accuracy</span>
                            <span id="summary-accuracy" class="block text-4xl font-extrabold text-on-surface mt-1 tabular-nums">0%</span>
                            <div class="mt-3 h-1 rounded-full bg-surface-container-high overflow-hidden">
                                <div id="summary-accuracy-bar" class="h-1 rounded-full bg-primary" style="width: 0%"></div>
                            </div>
                        </div>
                        <div data-stat-tile class="bg-emerald-50 border border-emerald-200/80 rounded-2xl p-4 shadow-sm">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-emerald-700">Remembered</span>
                            <span id="summary-remembered" class="block text-4xl font-extrabold text-emerald-600 mt-1 tabular-nums">0</span>
                        </div>
                        <div data-stat-tile class="bg-rose-50 border border-rose-200/80 rounded-2xl p-4 shadow-sm">
                            <span class="block text-[11px] font-bold uppercase tracking-wider text-rose-700">Forgotten</span>
                            <span id="summary-forgotten" class="block text-4xl font-extrabold text-rose-600 mt-1 tabular-nums">0</span>
                        </div>
                    </div>

                    <div id="finished-actions" class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        <button id="review-missed-btn" type="button" disabled class="hidden px-6 py-3 rounded-xl bg-primary text-on-primary font-bold shadow-sm hover:bg-primary-container transition-colors disabled:opacity-60 disabled:cursor-wait">
                            Saving…
                        </button>
                        <button id="practice-again-btn" type="button" disabled class="hidden px-6 py-3 rounded-xl font-bold border border-outline-variant text-on-surface hover:bg-surface-container-low transition-colors disabled:opacity-60 disabled:cursor-wait">
                            Practice again
                        </button>
                        <a id="return-hub-link" href="review.php" class="px-6 py-3 rounded-xl bg-primary text-on-primary font-bold shadow-sm hover:bg-primary-container transition-colors">
                            Return to Review Hub
                        </a>
                    </div>
                </div>

            <?php endif; ?>
    </main>

    <!-- Data Injection -->
    <script>
        window.MemPulseReviewData = {
            deckId: <?= json_encode($deckId) ?>,
            dueCards: <?= json_encode($dueCards ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            csrfToken: <?= json_encode($_SESSION['csrf_token'] ?? '') ?>,
            mode: <?= json_encode($isPractice ? 'practice' : null) ?>,
            skipped: <?= (int) $practiceSkipped ?>,
            returnUrl: <?= json_encode($returnUrl) ?>
        };
    </script>
    <script src="../assets/js/review.js" defer></script>
</body>
</html>