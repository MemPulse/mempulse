<?php
// 1. Initialize user session tracking
session_start();

// 2. Route Protection: Redirect to login view if user credentials are uninitialized
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

// 3. Connect to database layout module
require_once __DIR__ . '/../config/db.php'; 

$user_id = $_SESSION['user_id'];

// --- EXTRACTION: LIVE USER PROFILE DETAILS ---
// Pulling your newly added first_name and last_name columns directly from your updated users table
$user_sql  = "SELECT first_name, last_name FROM users WHERE id = ? AND deleted_at IS NULL";
$user_stmt = mysqli_prepare($conn, $user_sql);
mysqli_stable_bind_param: mysqli_stmt_bind_param($user_stmt, "i", $user_id);
mysqli_stmt_execute($user_stmt);
mysqli_stmt_bind_result($user_stmt, $first_name, $last_name);
mysqli_stmt_fetch($user_stmt);
mysqli_stmt_close($user_stmt);

// Handle display fallback strings defensively
$display_name = trim(($first_name ?? '') . ' ' . ($last_name ?? ''));
if (empty($display_name)) {
    $display_name = "Active Learner";
}

// --- METRIC 1: CARDS DUE TODAY ---
$due_sql = "SELECT COUNT(*) FROM flashcards f 
            JOIN decks d ON f.deck_id = d.id 
            WHERE d.user_id = ? AND f.deleted_at IS NULL AND f.next_review_at <= NOW()";
$due_stmt = mysqli_prepare($conn, $due_sql);
mysqli_stmt_bind_param($due_stmt, "i", $user_id);
mysqli_stmt_execute($due_stmt);
mysqli_stmt_bind_result($due_stmt, $cards_due);
mysqli_stmt_fetch($due_stmt);
mysqli_stmt_close($due_stmt);

// --- METRIC 2: MASTERED CARDS (Interval days >= 21) ---
$mastered_sql = "SELECT COUNT(*) FROM flashcards f 
                 JOIN decks d ON f.deck_id = d.id 
                 WHERE d.user_id = ? AND f.deleted_at IS NULL AND f.interval_days >= 21";
$mastered_stmt = mysqli_prepare($conn, $mastered_sql);
mysqli_stmt_bind_param($mastered_stmt, "i", $user_id);
mysqli_stmt_execute($mastered_stmt);
mysqli_stmt_bind_result($mastered_stmt, $mastered_cards);
mysqli_stmt_fetch($mastered_stmt);
mysqli_stmt_close($mastered_stmt);

// --- METRIC 3: LIFETIME ACCURACY (Average from generated virtual column) ---
$accuracy_sql = "SELECT AVG(accuracy_percentage) FROM review_sessions WHERE user_id = ? AND ended_at IS NOT NULL";
$acc_stmt = mysqli_prepare($conn, $accuracy_sql);
mysqli_stmt_bind_param($acc_stmt, "i", $user_id);
mysqli_stmt_execute($acc_stmt);
mysqli_stmt_bind_result($acc_stmt, $avg_accuracy);
mysqli_stmt_fetch($acc_stmt);
mysqli_stmt_close($acc_stmt);
$avg_accuracy = $avg_accuracy !== null ? round($avg_accuracy, 1) : 0.0;

// --- METRIC 4: TOUGH CARDS / LEECHES (Lapses >= 8) ---
$tough_sql = "SELECT COUNT(*) FROM flashcards f 
              JOIN decks d ON f.deck_id = d.id 
              WHERE d.user_id = ? AND f.deleted_at IS NULL AND f.lapses >= 8";
$tough_stmt = mysqli_prepare($conn, $tough_sql);
mysqli_stmt_bind_param($tough_stmt, "i", $user_id);
mysqli_stmt_execute($tough_stmt);
mysqli_stmt_bind_result($tough_stmt, $tough_cards);
mysqli_stmt_fetch($tough_stmt);
mysqli_stmt_close($tough_stmt);

// --- COMPUTE STREAKS FROM REVIEW SESSIONS HISTORIES ---
$current_streak = 0;
$best_streak = 0;
$streak_accuracy = 0.0;

// 1. Fetch distinct active dates and total performance metrics for completed sessions
$session_sql = "SELECT DATE(started_at) as session_date, 
                       SUM(remembered_count) as total_remembered, 
                       SUM(total_reviewed) as total_reviewed
                FROM review_sessions 
                WHERE user_id = ? 
                  AND ended_at IS NOT NULL
                GROUP BY DATE(started_at)
                HAVING SUM(total_reviewed) > 0
                ORDER BY session_date DESC";

$session_stmt = mysqli_prepare($conn, $session_sql);
mysqli_stmt_bind_param($session_stmt, "i", $user_id);
mysqli_stmt_execute($session_stmt);
$result = mysqli_stmt_get_result($session_stmt);

$activity_history = [];
while ($row = mysqli_fetch_assoc($result)) {
    $activity_history[$row['session_date']] = [
        'remembered' => intval($row['total_remembered']),
        'reviewed' => intval($row['total_reviewed'])
    ];
}
mysqli_stmt_close($session_stmt);

// --- STREAK HEATMAP: LAST 365 DAYS ---
// Reuse the same completed review-session history already loaded above so
// heatmap activity and streak calculations stay based on one source of truth.
$heatmap_end = new DateTime('today');
$heatmap_start = (clone $heatmap_end)->modify('-364 days');
$heatmap_data = [];
$study_days = 0;

foreach ($activity_history as $date_str => $metrics) {
    $activity_date = DateTime::createFromFormat('!Y-m-d', $date_str);
    if (!$activity_date) {
        continue;
    }

    if ($activity_date >= $heatmap_start && $activity_date <= $heatmap_end && $metrics['reviewed'] > 0) {
        $review_count = $metrics['reviewed'];
        $heatmap_data[$date_str] = $review_count;
        $study_days++;
    }
}

// Align the visual grid to Monday-Sunday while keeping exactly the requested
// 365-day data window. Padded days outside that window render invisibly.
$heatmap_calendar_start = clone $heatmap_start;
$heatmap_calendar_start->modify('monday this week');
$heatmap_calendar_end = clone $heatmap_end;
$heatmap_calendar_end->modify('sunday this week');
$heatmap_week_count = (int) floor(($heatmap_calendar_end->diff($heatmap_calendar_start)->days + 1) / 7);

// Fixed, human-readable activity bands. The cell color is still data-driven.
$heatmap_level_class = static function (int $reviews): string {
    if ($reviews <= 0) {
        return 'bg-surface-container-high';
    }
    if ($reviews <= 5) {
        return 'bg-primary-fixed';
    }
    if ($reviews <= 15) {
        return 'bg-primary-fixed-dim';
    }
    if ($reviews <= 30) {
        return 'bg-primary-container';
    }
    return 'bg-primary';
};

if (!empty($activity_history)) {
    $today_str = date('Y-m-d');
    $yesterday_str = date('Y-m-d', strtotime('-1 day'));
    
    // 2. Compute Current Streak & Tracking Parameters
    $tracking_date = current(array_keys($activity_history));
    
    // The current streak is alive if the user reviewed today OR yesterday
    if ($tracking_date === $today_str || $tracking_date === $yesterday_str) {
        $check_date = new DateTime($tracking_date);
        $streak_remembered = 0;
        $streak_reviewed = 0;

        while (true) {
            $date_str = $check_date->format('Y-m-d');
            if (isset($activity_history[$date_str])) {
                $current_streak++;
                $streak_remembered += $activity_history[$date_str]['remembered'];
                $streak_reviewed += $activity_history[$date_str]['reviewed'];
                $check_date->modify('-1 day');
            } else {
                break;
            }
        }
        
        // Calculate the accuracy over the current streak block
        if ($streak_reviewed > 0) {
            $streak_accuracy = round(($streak_remembered / $streak_reviewed) * 100, 1);
        }
    }

    // 3. Compute Best Streak Historically
    $temp_streak = 0;
    $expected_date = null;

    // Read chronologically from past to present to find the max streak sequence
    foreach (array_reverse(array_keys($activity_history)) as $date_str) {
        $current_date = new DateTime($date_str);
        
        if ($expected_date === null || $current_date == $expected_date) {
            $temp_streak++;
        } else {
            if ($temp_streak > $best_streak) {
                $best_streak = $temp_streak;
            }
            $temp_streak = 1; // Reset sequence counter
        }
        
        // Setup expected next date (tomorrow in chronological sequence loop)
        $expected_date = clone $current_date;
        $expected_date->modify('+1 day');
    }
    
    // Final check for the last calculated streak block loop
    if ($temp_streak > $best_streak) {
        $best_streak = $temp_streak;
    }
} else {
    $best_streak = 0;
    $current_streak = 0;
    $streak_accuracy = 0.0;
}

$pageTitle = "Home Dashboard";
$currentPage = 'home';
?>

<!DOCTYPE html>
<html lang="en">


<?php include __DIR__ . '\includes\head.php';?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '\includes\sidebar.php'; ?>

    <main class="pl-72 pt-12 transition-all duration-300 ease-in-out">
        
        <?php include __DIR__ . '\includes\header-data.php'; ?>
        <?php include __DIR__ . '\includes\header.php'; ?>

        <div class="max-w-7xl mx-auto px-8 py-8">
            <div class="flex flex-col w-full gap-8">
                
            <?php include __DIR__ . '\components\hero-carousel.php'; ?>
            
            <?php include __DIR__ . '\components\metric-card.php'; ?>
                     
            <section class="grid grid-cols-1 lg:grid-cols-4 gap-6 w-full">
                <!-- Streak Heatmap (3/4 width on desktop) -->
                    <?php include __DIR__ . '\components\streak-heatmap.php'; ?>
                    
                    <!-- Streak & Accuracy Stats (1/4 width on desktop) -->
                    <?php include __DIR__ . '\components\streak-stats.php'; ?>
                    
                </section>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <script>
        window.MemPulseHomeData = {
            stats: {
                cardsDue: <?php echo intval($cards_due); ?>,
                masteredCards: <?php echo intval($mastered_cards); ?>,
                lifetimeAccuracy: <?php echo floatval($avg_accuracy); ?>,
                toughCards: <?php echo intval($tough_cards); ?>,
                currentStreak: <?php echo intval($current_streak); ?>,
                bestStreak: <?php echo intval($best_streak); ?>,
                streakAccuracy: <?php echo floatval($streak_accuracy); ?>
            }
        };
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    </body>

</html>