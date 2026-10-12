<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/ReviewHubService.php';

// Anti-CSRF token, sent along with the batch save in review.js
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int) $_SESSION['user_id'];
$deckId = isset($_GET['deck_id']) && ctype_digit((string) $_GET['deck_id']) ? (int) $_GET['deck_id'] : null;

$isReviewing = false;
$dueCards = [];
$hubDecks = [];

if ($deckId) {
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

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="pt-16 pl-0 md:pl-72 transition-all duration-300 ease-in-out h-screen flex flex-col">
        <?php include __DIR__ . '/../includes/header-data.php'; ?>    
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <div class="flex-1 overflow-y-auto overflow-x-hidden px-4 sm:px-6 lg:px-8 py-6">
                        
            <?php if (!$isReviewing): ?>
                <?php include __DIR__ . '/review-hub-view.php'; ?>

            <?php else: ?>
                <!-- STATE B: ACTIVE REVIEW INTERFACE -->
                <div id="active-review-container" class="max-w-[55rem] mx-auto flex flex-col h-full <?= empty($dueCards) ? 'hidden' : '' ?>">
                    
                    <!-- Session Header -->
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

                            <div id="rate-actions" class="hidden flex gap-3 w-full max-w-md">
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
                                <kbd class="px-1.5 py-0.5 rounded border border-outline-variant/50 bg-surface-container-low font-mono text-[10px]">F</kbd> forgot
                                <span class="mx-1" aria-hidden="true">·</span>
                                <kbd class="px-1.5 py-0.5 rounded border border-outline-variant/50 bg-surface-container-low font-mono text-[10px]">Space</kbd> remember
                            </span>
                        </p>
                    </div>

                </div>

                <!-- STATE C: ZERO CARDS / FINISHED SCREEN -->
                <div id="review-finished-screen" class="max-w-2xl mx-auto flex-col items-center justify-center text-center h-full pb-12 <?= empty($dueCards) ? 'flex' : 'hidden' ?>">
                    <div id="finished-icon" class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-6">
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
                        <a id="return-hub-link" href="review.php" class="px-6 py-3 rounded-xl bg-primary text-on-primary font-bold shadow-sm hover:bg-primary-container transition-colors">
                            Return to Review Hub
                        </a>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <!-- Data Injection -->
    <script>
        window.MemPulseReviewData = {
            deckId: <?= json_encode($deckId) ?>,
            dueCards: <?= json_encode($dueCards ?? []) ?>,
            csrfToken: <?= json_encode($_SESSION['csrf_token'] ?? '') ?>
        };
    </script>
    <script src="../assets/js/review.js" defer></script>
</body>
</html>