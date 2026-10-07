<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$userId = (int) $_SESSION['user_id'];
$deckId = isset($_GET['deck_id']) && ctype_digit((string) $_GET['deck_id']) ? (int) $_GET['deck_id'] : null;

$isReviewing = false;
$dueCards = [];
$hubDecks = [];

if ($deckId) {
    // ACTIVE REVIEW STATE: Fetch due cards for the specific deck
    $isReviewing = true;
    $sql = "SELECT id, front, back, interval_days, next_review_at FROM flashcards 
            WHERE deck_id = ? AND deleted_at IS NULL AND next_review_at <= NOW() 
            ORDER BY next_review_at ASC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $deckId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $dueCards[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // HUB STATE: Fetch decks with due counts and upcoming counts
    $sql = "SELECT d.id, d.title, 
            SUM(CASE WHEN f.next_review_at <= NOW() THEN 1 ELSE 0 END) as due_count,
            SUM(CASE WHEN f.next_review_at > NOW() AND f.next_review_at <= DATE_ADD(NOW(), INTERVAL 2 DAY) THEN 1 ELSE 0 END) as upcoming_count
            FROM decks d
            LEFT JOIN flashcards f ON d.id = f.deck_id AND f.deleted_at IS NULL
            WHERE d.user_id = ? AND d.deleted_at IS NULL
            GROUP BY d.id HAVING due_count > 0 OR upcoming_count > 0
            ORDER BY due_count DESC, upcoming_count DESC";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $hubDecks[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
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
        
        <div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <?php if (!$isReviewing): ?>
                <!-- STATE A: REVIEW HUB -->
                <div class="max-w-5xl mx-auto flex flex-col gap-8">
                    <div>
                        <h2 class="font-headline-md text-2xl font-bold tracking-tight text-on-surface">Review Hub</h2>
                        <p class="text-on-surface-variant text-sm mt-1">Select a deck to start your spaced repetition session.</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Left Column: Due & Upcoming Decks -->
                        <div class="lg:col-span-8 flex flex-col gap-6">
                            <!-- Due Section -->
                            <section>
                                <h3 class="font-semibold text-lg text-rose-700 flex items-center gap-2 mb-3">
                                    <span class="material-symbols-outlined">schedule</span> Due Now
                                </h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <?php 
                                    $hasDue = false;
                                    foreach ($hubDecks as $d): 
                                        if ($d['due_count'] > 0): $hasDue = true; 
                                    ?>
                                        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 shadow-sm flex flex-col justify-between hover:border-primary/50 transition-colors">
                                            <h4 class="font-bold text-lg truncate"><?= htmlspecialchars($d['title']) ?></h4>
                                            <div class="mt-4 flex items-center justify-between">
                                                <span class="text-sm font-semibold text-on-surface-variant"><?= $d['due_count'] ?> cards due</span>
                                                <a href="review.php?deck_id=<?= $d['id'] ?>" class="px-4 py-2 rounded-xl bg-primary text-on-primary font-bold text-sm shadow-sm hover:bg-primary-container transition-all">Start</a>
                                            </div>
                                        </div>
                                    <?php 
                                        endif; 
                                    endforeach; 
                                    if (!$hasDue) echo "<p class=\"text-on-surface-variant text-sm\">You are all caught up for now!</p>";
                                    ?>
                                </div>
                            </section>

                            <!-- Upcoming Section -->
                            <section class="mt-4">
                                <h3 class="font-semibold text-lg text-on-surface-variant flex items-center gap-2 mb-3">
                                    <span class="material-symbols-outlined">update</span> Upcoming (Next 48h)
                                </h3>
                                <div class="flex flex-col gap-3">
                                    <?php 
                                    $hasUpcoming = false;
                                    foreach ($hubDecks as $d): 
                                        if ($d['due_count'] == 0 && $d['upcoming_count'] > 0): $hasUpcoming = true; 
                                    ?>
                                        <div class="flex items-center justify-between p-3 bg-surface-container-low rounded-xl border border-outline-variant/20">
                                            <span class="font-semibold text-on-surface"><?= htmlspecialchars($d['title']) ?></span>
                                            <span class="text-xs text-on-surface-variant bg-surface-container-high px-2 py-1 rounded-md"><?= $d['upcoming_count'] ?> cards soon</span>
                                        </div>
                                    <?php 
                                        endif; 
                                    endforeach; 
                                    if (!$hasUpcoming) echo "<p class=\"text-on-surface-variant text-sm\">No cards scheduled for the next 48 hours.</p>";
                                    ?>
                                </div>
                            </section>
                        </div>

                        <!-- Right Column: Info/Logs -->
                        <div class="lg:col-span-4">
                            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 shadow-sm">
                                <h3 class="font-semibold text-base mb-2">Review Keyboard Controls</h3>
                                <ul class="text-sm text-on-surface-variant flex flex-col gap-3 mt-4">
                                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border font-mono text-xs">Space</kbd> Show Answer / Remember</li>
                                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border font-mono text-xs">F</kbd> Mark as Forgotten</li>
                                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border font-mono text-xs">←</kbd> Previous Card</li>
                                    <li class="flex items-center gap-3"><kbd class="px-2 py-1 bg-surface-container-high rounded border font-mono text-xs">→</kbd> Next Card</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- STATE B: ACTIVE REVIEW INTERFACE -->
                <div id="active-review-container" class="max-w-3xl mx-auto flex flex-col h-full <?= empty($dueCards) ? 'hidden' : '' ?>">
                    
                    <!-- Progress & Tally Header -->
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4 flex-1">
                            <button id="end-session-btn" class="text-on-surface-variant hover:text-rose-600 transition-colors" title="End Session Early">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                            <div class="flex-1 max-w-xs">
                                <div class="flex justify-between text-xs font-semibold mb-1">
                                    <span id="progress-text" class="text-on-surface-variant">Card 1 of <?= count($dueCards) ?></span>
                                </div>
                                <div class="w-full bg-surface-container-high rounded-full h-1.5 overflow-hidden">
                                    <div id="progress-bar" class="bg-primary h-1.5 rounded-full" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Real-time Tally Counters -->
                        <div class="flex items-center gap-3 text-sm font-bold bg-surface-container-lowest border border-outline-variant/30 px-3 py-1.5 rounded-xl shadow-xs">
                            <span class="text-emerald-600 flex items-center gap-1" title="Remembered">
                                <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                <span id="tally-remembered">0</span>
                            </span>
                            <span class="text-outline-variant">|</span>
                            <span class="text-rose-600 flex items-center gap-1" title="Forgotten">
                                <span class="material-symbols-outlined text-[16px]">cancel</span>
                                <span id="tally-forgotten">0</span>
                            </span>
                        </div>
                    </div>

                    <!-- Flashcard Stage -->
                    <div class="flex-1 flex flex-col items-center justify-center min-h-[350px]">
                        <div id="flashcard-stage" class="w-full bg-surface-container-lowest border border-outline-variant/30 shadow-md rounded-2xl p-8 sm:p-12 text-center relative overflow-hidden transition-all">
                            
                            <!-- Rating Badge (shows briefly when navigating backwards) -->
                            <div id="historical-rating-badge" class="hidden absolute top-4 right-4 px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider"></div>

                            <!-- Front -->
                            <div id="card-front-content" class="text-xl sm:text-2xl font-medium text-on-surface"></div>
                            
                            <!-- Divider -->
                            <hr id="card-divider" class="my-8 border-outline-variant/30 hidden">
                            
                            <!-- Back -->
                            <div id="card-back-content" class="text-lg sm:text-xl text-on-surface-variant hidden"></div>
                        </div>
                    </div>

                    <!-- Controls Action Bar -->
                    <div class="mt-8 flex justify-center min-h-[60px]">
                        <!-- State 1: Show Answer -->
                        <button id="show-answer-btn" class="px-8 py-3 rounded-xl bg-primary text-on-primary font-bold shadow-md hover:bg-primary-container hover:-translate-y-0.5 transition-all w-full max-w-sm flex items-center justify-center gap-2">
                            <span>Show Answer</span>
                            <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-mono bg-white/20 rounded">Space</kbd>
                        </button>

                        <!-- State 2: Rate Actions (Hidden initially) -->
                        <div id="rate-actions" class="hidden flex gap-4 w-full max-w-md">
                            <button id="rate-forgot-btn" class="flex-1 flex flex-col items-center justify-center py-2.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 transition-colors shadow-sm relative group">
                                <span class="font-bold">Forgot</span>
                                <span id="projected-forgot-date" class="text-[11px] font-medium opacity-80 mt-0.5">Due: Today</span>
                                <kbd class="absolute -top-2 -right-2 px-1.5 py-0.5 text-[10px] font-mono bg-surface border border-outline-variant rounded text-on-surface-variant opacity-0 group-hover:opacity-100 transition-opacity">F</kbd>
                            </button>
                            <button id="rate-remember-btn" class="flex-1 flex flex-col items-center justify-center py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 transition-colors shadow-sm relative group">
                                <span class="font-bold">Remember</span>
                                <span id="projected-remember-date" class="text-[11px] font-medium opacity-80 mt-0.5">Due: Next</span>
                                <kbd class="absolute -top-2 -right-2 px-1.5 py-0.5 text-[10px] font-mono bg-surface border border-outline-variant rounded text-on-surface-variant opacity-0 group-hover:opacity-100 transition-opacity">Space</kbd>
                            </button>
                        </div>
                    </div>

                </div>

                <!-- STATE C: ZERO CARDS / FINISHED SCREEN -->
                <div id="review-finished-screen" class="max-w-2xl mx-auto flex-col items-center justify-center text-center h-full pt-12 <?= empty($dueCards) ? 'flex' : 'hidden' ?>">
                    <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-[40px]">task_alt</span>
                    </div>
                    <h2 class="font-headline-md text-3xl font-extrabold text-on-surface mb-2">Session Complete!</h2>
                    <p class="text-on-surface-variant mb-8">You've reached the end of the queue. Great work.</p>
                    
                    <div id="session-summary-stats" class="grid grid-cols-2 gap-4 w-full max-w-sm mb-8 hidden">
                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/30">
                            <span class="block text-[11px] font-bold uppercase text-on-surface-variant tracking-wider">Accuracy</span>
                            <span id="summary-accuracy" class="block text-2xl font-extrabold text-on-surface mt-1">0%</span>
                        </div>
                        <div class="bg-surface-container-low p-4 rounded-xl border border-outline-variant/30">
                            <span class="block text-[11px] font-bold uppercase text-on-surface-variant tracking-wider">Total Rated</span>
                            <span id="summary-total" class="block text-2xl font-extrabold text-on-surface mt-1">0</span>
                        </div>
                    </div>

                    <a href="review.php" class="px-6 py-3 rounded-xl bg-primary text-on-primary font-bold shadow-sm hover:bg-primary-container transition-colors">
                        Return to Review Hub
                    </a>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Feedback Toast -->
    <div id="review-toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 transition-all duration-300 transform translate-y-12 opacity-0 px-4 py-2.5 rounded-full font-semibold text-sm shadow-lg flex items-center gap-2 pointer-events-none">
        <span id="review-toast-icon" class="material-symbols-outlined text-[18px]"></span>
        <span id="review-toast-msg"></span>
    </div>

    <!-- Data Injection -->
    <script>
        window.MemPulseReviewData = {
            deckId: <?= json_encode($deckId) ?>,
            dueCards: <?= json_encode($dueCards ?? []) ?>
        };
    </script>
    <script src="../assets/js/review.js" defer></script>
</body>
</html>