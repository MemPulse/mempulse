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

// --- STREAK HEATMAP: LAST 120 DAYS ---
$heatmap_end = new DateTime('today');
$heatmap_start = (clone $heatmap_end)->modify('-119 days'); // Changed to 120 days
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

// Align visual grid to Monday-Sunday
$heatmap_calendar_start = clone $heatmap_start;
$heatmap_calendar_start->modify('monday this week');
$heatmap_calendar_end = clone $heatmap_end;
$heatmap_calendar_end->modify('sunday this week');
$heatmap_week_count = (int) floor(($heatmap_calendar_end->diff($heatmap_calendar_start)->days + 1) / 7);

// Fix: Use a standard named function so dashboard.php can properly call it
if (!function_exists('heatmap_level_class')) {
    function heatmap_level_class(int $reviews): string {
        if ($reviews <= 0) return 'bg-surface-container-high';
        if ($reviews <= 14) return 'bg-primary-fixed';
        if ($reviews <= 39) return 'bg-primary-fixed-dim';
        if ($reviews <= 79) return 'bg-primary-container';
        return 'bg-primary';
    }
}

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

    <main class="pt-16 pl-0 md:pl-72 transition-all duration-300 ease-in-out">
        
        <?php include __DIR__ . '\includes\header-data.php'; ?>
        <?php include __DIR__ . '\includes\header.php'; ?>
        <?php include __DIR__ . '/dashboard.php'; ?>                
              
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
        const MemPulseDashboard = {
            updateAll(appPayload) {
                if (!appPayload) return;

                this.syncDatabaseMetrics(appPayload.stats);
                this.syncStreakMetrics(appPayload.stats);
            },

            syncDatabaseMetrics(stats) {
                if (!stats) return;

                const cardsDue = document.getElementById('stat-cards-due');
                const mastered = document.getElementById('stat-mastered');
                const accuracy = document.getElementById('stat-accuracy');
                const tough = document.getElementById('stat-tough');

                if (cardsDue) cardsDue.textContent = stats.cardsDue ?? '--';
                if (mastered) mastered.textContent = stats.masteredCards ?? '--';
                if (accuracy) {
                    accuracy.textContent =
                        stats.lifetimeAccuracy !== undefined
                            ? `${stats.lifetimeAccuracy}%`
                            : '--%';
                }
                if (tough) tough.textContent = stats.toughCards ?? '--';
            },

            syncStreakMetrics(stats) {
                if (!stats) return;

                const currentStreak = document.getElementById('stat-current-streak');
                const bestStreak = document.getElementById('stat-best-streak');
                const streakAccuracy = document.getElementById('stat-streak-accuracy');

                if (currentStreak) {
                    currentStreak.textContent = stats.currentStreak ?? '--';
                }

                if (bestStreak) {
                    bestStreak.textContent = stats.bestStreak ?? '--';
                }

                if (streakAccuracy) {
                    streakAccuracy.textContent = stats.streakAccuracy ?? '--';
                }
            }
        };

        document.addEventListener("DOMContentLoaded", () => {
            const heatmapScroller = document.getElementById("heatmap-scroll");

            if (heatmapScroller) {
                requestAnimationFrame(() => {
                    heatmapScroller.scrollLeft =
                        heatmapScroller.scrollWidth - heatmapScroller.clientWidth;
                });
            }

            document.querySelectorAll("#heatmap-scroll .heatmap-cell").forEach(cell => {
                cell.addEventListener("mouseenter", () => {
                    const rect = cell.getBoundingClientRect();

                    cell.style.setProperty(
                        "--tooltip-left",
                        `${rect.left + rect.width / 2}px`
                    );

                    cell.style.setProperty(
                        "--tooltip-top",
                        `${rect.top - 8}px`
                    );
                });
            });

            MemPulseDashboard.updateAll(window.MemPulseHomeData);
        });
    </script>

    <script>
        // Function to update the top navigation header dynamically
        function updateHeaderData(userData) {
            const headerStreak = document.getElementById('header-streak-count');
            const headerProfileImg = document.getElementById('header-profile-img');

            // 1. Update Streak Count Display Node smoothly
            if (headerStreak) {
                headerStreak.textContent = userData.currentStreak + (userData.currentStreak === 1 ? ' Day' : ' Days');
            }

            // 2. Update Image Path Assets dynamically
            if (headerProfileImg && userData.profileImageUrl) {
                headerProfileImg.src = userData.profileImageUrl;
            }
        }
        </script>

        <script>
            /**
         * Global Dashboard Manager Module
         * Coordinates active dynamic interface state tracking for MemPulse
         */
        const MemPulseDashboard = {
            // 1. Core State Updater Method
            updateAll(appPayload) {
                if (!appPayload) return;

                this.syncHeader(appPayload.user);
                this.syncSidebarProfile(appPayload.user);
                this.syncDatabaseMetrics(appPayload.stats);
                this.syncStreakMetrics(appPayload.stats);
            },

            // 2. Synchronize Top Fixed Navigation Header Elements
            syncHeader(user) {
                if (!user) return;
                const headerStreak = document.getElementById('header-streak-count');
                const headerImg = document.getElementById('header-profile-img');

                if (headerStreak) {
                    headerStreak.textContent = user.currentStreak + (user.currentStreak === 1 ? ' Day' : ' Days');
                }
                if (headerImg && user.profileImageUrl) {
                    headerImg.src = user.profileImageUrl;
                }
            },

            // 3. Synchronize Bottom Expanded/Collapsed Sidebar Components
            syncSidebarProfile(user) {
                if (!user) return;
                const sidebarName = document.getElementById('sidebar-profile-name');
                const sidebarRole = document.getElementById('sidebar-profile-role');
                const sidebarImg = document.getElementById('sidebar-profile-img');

                if (sidebarName) sidebarName.textContent = user.displayName;
                if (sidebarRole) sidebarRole.textContent = user.displayRole;
                if (sidebarImg && user.profileImageUrl) {
                    sidebarImg.src = user.profileImageUrl;
                }
            },

            // 4. Update the Spaced-Repetition Core Database Analytics Rows
            syncDatabaseMetrics(stats) {
                if (!stats) return;
                const cardsDue = document.getElementById('stat-cards-due');
                const mastered = document.getElementById('stat-mastered');
                const accuracy = document.getElementById('stat-accuracy');
                const tough = document.getElementById('stat-tough');

                if (cardsDue) cardsDue.textContent = stats.cardsDue ?? '--';
                if (mastered) mastered.textContent = stats.masteredCards ?? '--';
                if (accuracy) accuracy.textContent = (stats.lifetimeAccuracy !== undefined) ? `${stats.lifetimeAccuracy}%` : '--%';
                if (tough) tough.textContent = stats.toughCards ?? '--';
            },

            // 5. Update Streak and Performance Calculation Cards
            syncStreakMetrics(stats) {
                if (!stats) return;
                const currentStreak = document.getElementById('stat-current-streak');
                const bestStreak = document.getElementById('stat-best-streak');
                const streakAccuracy = document.getElementById('stat-streak-accuracy');

                if (currentStreak) currentStreak.textContent = stats.currentStreak ?? '--';
                if (bestStreak) bestStreak.textContent = stats.bestStreak ?? '--';
                if (streakAccuracy) streakAccuracy.textContent = stats.streakAccuracy ?? '--';
            }
        };

        // ─── INITIALIZATION / LIVE SQL DATABASE RUNNER ───
        document.addEventListener("DOMContentLoaded", () => {
            // This section at the bottom of your file now instantly matches your dynamic PHP calculations!
            const incomingAppPayload = {
                user: {
                    displayName: <?php echo json_encode($display_name); ?>,
                    displayRole: "User",
                    currentStreak: <?php echo intval($current_streak); ?>,
                    profileImageUrl: "./assets/images/profile-picture.webp.jpg" 
                },
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

            // Default the heatmap to the most recent study days
            document.addEventListener("DOMContentLoaded", () => {
            const heatmapScroller = document.getElementById("heatmap-scroll");

            if (heatmapScroller) {
                requestAnimationFrame(() => {
                    heatmapScroller.scrollLeft =
                        heatmapScroller.scrollWidth - heatmapScroller.clientWidth;
                    });
            }
            });

            document.querySelectorAll("#heatmap-scroll .heatmap-cell").forEach(cell => {
                cell.addEventListener("mouseenter", () => {
                    const rect = cell.getBoundingClientRect();

                    cell.style.setProperty("--tooltip-left", `${rect.left + rect.width / 2}px`);
                    cell.style.setProperty("--tooltip-top", `${rect.top - 8}px`);
                });
            });

            // Run deep script synchronization across Header, Sidebar, and Stats Row
            MemPulseDashboard.updateAll(incomingAppPayload);
        });
    </script>
    </body>

</html>