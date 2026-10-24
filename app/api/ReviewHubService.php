<?php
/**
 * ReviewHubService
 *
 * Read-only data + formatting helpers for the Review Hub page (review.php, no deck_id).
 * All "seconds" values are computed in SQL (TIMESTAMPDIFF) so PHP/MySQL timezone
 * differences cannot skew them.
 *
 * Place next to FlashcardService.php (the api/ folder).
 */
class ReviewHubService
{
    /** Cards scheduled within this many days count as "upcoming". */
    public const UPCOMING_DAYS = 2;

    /**
     * Schema-derived card buckets used by the Due Now table.
     *
     * The schema has no explicit learning/young/mastered state column, so these
     * buckets are inferred from review_count and interval_days:
     * - new: never reviewed
     * - learning: reviewed and interval_days <= 1
     * - young: interval_days 2..20
     * - mastered: interval_days >= 21
     *
     * Adjust these thresholds if the application defines different SRS rules.
     */
    public const YOUNG_MAX_INTERVAL_DAYS = 20;
    public const MASTERED_MIN_INTERVAL_DAYS = 21;

    // -----------------------------------------------------------------------
    // Queries
    // -----------------------------------------------------------------------

    /**
     * Every non-archived deck that has cards due now or coming up soon.
     *
     * @return array<int, array{
     *   id:int, title:string, totalCards:int, dueCount:int, upcomingCount:int,
     *   newDueCount:int, learningDueCount:int, youngDueCount:int, masteredDueCount:int,
     *   overdueSeconds:?int, nextDueSeconds:?int
     * }>
     */
    public static function getHubDecks(mysqli $conn, int $userId): array
    {
        $days = (int) self::UPCOMING_DAYS;
        $youngMax = (int) self::YOUNG_MAX_INTERVAL_DAYS;
        $masteredMin = (int) self::MASTERED_MIN_INTERVAL_DAYS;

        $sql = "SELECT d.id, d.title,
                       COUNT(f.id) AS total_cards,
                       COALESCE(SUM(f.next_review_at <= NOW()), 0) AS due_count,
                       COALESCE(SUM(f.next_review_at <= NOW() AND f.review_count = 0), 0) AS new_due_count,
                       COALESCE(SUM(f.next_review_at <= NOW()
                                    AND f.review_count > 0 AND f.interval_days <= 1), 0) AS learning_due_count,
                       COALESCE(SUM(f.next_review_at <= NOW()
                                    AND f.review_count > 0 AND f.interval_days BETWEEN 2 AND $youngMax), 0) AS young_due_count,
                       COALESCE(SUM(f.next_review_at <= NOW()
                                    AND f.review_count > 0 AND f.interval_days >= $masteredMin), 0) AS mastered_due_count,
                       COALESCE(SUM(f.next_review_at > NOW()
                                    AND f.next_review_at <= DATE_ADD(NOW(), INTERVAL $days DAY)), 0) AS upcoming_count,
                       TIMESTAMPDIFF(SECOND,
                           MIN(CASE WHEN f.next_review_at <= NOW() THEN f.next_review_at END),
                           NOW()) AS overdue_seconds,
                       TIMESTAMPDIFF(SECOND, NOW(),
                           MIN(CASE WHEN f.next_review_at > NOW()
                                     AND f.next_review_at <= DATE_ADD(NOW(), INTERVAL $days DAY)
                                    THEN f.next_review_at END)) AS next_due_seconds
                FROM decks d
                LEFT JOIN flashcards f ON f.deck_id = d.id AND f.deleted_at IS NULL
                WHERE d.user_id = ? AND d.deleted_at IS NULL
                GROUP BY d.id, d.title
                HAVING due_count > 0 OR upcoming_count > 0";

        $decks = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $decks[] = [
                    'id'             => (int) $row['id'],
                    'title'          => (string) $row['title'],
                    'totalCards'     => (int) $row['total_cards'],
                    'dueCount'       => (int) $row['due_count'],
                    'newDueCount'    => (int) $row['new_due_count'],
                    'learningDueCount' => (int) $row['learning_due_count'],
                    'youngDueCount'  => (int) $row['young_due_count'],
                    'masteredDueCount' => (int) $row['mastered_due_count'],
                    'upcomingCount'  => (int) $row['upcoming_count'],
                    'overdueSeconds' => $row['overdue_seconds'] === null ? null : (int) $row['overdue_seconds'],
                    'nextDueSeconds' => $row['next_due_seconds'] === null ? null : (int) $row['next_due_seconds'],
                ];
            }
            mysqli_stmt_close($stmt);
        }
        return $decks;
    }

    /**
     * Seconds until the user's next card becomes due (no 48h limit), or null
     * when there are no scheduled cards at all.
     */
    public static function getNextDueSeconds(mysqli $conn, int $userId): ?int
    {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), MIN(f.next_review_at))
                FROM flashcards f
                JOIN decks d ON d.id = f.deck_id
                WHERE d.user_id = ? AND d.deleted_at IS NULL
                  AND f.deleted_at IS NULL AND f.next_review_at > NOW()";

        $secs = null;
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $secs);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }
        return $secs === null ? null : (int) $secs;
    }

    /**
     * Latest completed review sessions.
     *
     * @return array<int, array{
     *   deckId:int, deckTitle:string, cardsReviewed:int, rememberedCount:int,
     *   accuracy:int, durationSeconds:?int, formattedTime:string
     * }>
     */
    public static function getRecentSessions(mysqli $conn, int $userId, int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));

        $sql = "SELECT rs.id AS session_id, rs.deck_id, d.title AS deck_title, rs.total_reviewed, rs.remembered_count,
                       rs.accuracy_percentage,
                       TIMESTAMPDIFF(SECOND, rs.started_at, rs.ended_at) AS duration_seconds,
                       TIMESTAMPDIFF(SECOND, rs.ended_at, NOW()) AS ended_ago_seconds
                FROM review_sessions rs
                JOIN decks d ON d.id = rs.deck_id
                WHERE rs.user_id = ? AND rs.ended_at IS NOT NULL
                ORDER BY rs.ended_at DESC, rs.id DESC
                LIMIT ?";

        $sessions = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $sessions[] = [
                    'sessionID'       => (int) $row['session_id'],
                    'deckId'          => (int) $row['deck_id'],
                    'deckTitle'       => (string) $row['deck_title'],
                    'cardsReviewed'   => (int) $row['total_reviewed'],
                    'rememberedCount' => (int) $row['remembered_count'],
                    'accuracy'        => (int) $row['accuracy_percentage'],
                    'durationSeconds' => $row['duration_seconds'] === null ? null : (int) $row['duration_seconds'],
                    'formattedTime'   => self::formatAgo((int) $row['ended_ago_seconds']),
                ];
            }
            mysqli_stmt_close($stmt);
        }
        return $sessions;
    }

    /**
     * Cards reviewed today and the current study-day streak.
     * Same rules as the dashboard: a day counts when a completed session
     * reviewed at least one card; the streak is alive if the latest study day
     * is today or yesterday.
     *
     * @return array{reviewedToday:int, currentStreak:int}
     */
    public static function getStreakInfo(mysqli $conn, int $userId): array
    {
        $sql = "SELECT DATE(started_at) AS study_date, SUM(total_reviewed) AS reviewed
                FROM review_sessions
                WHERE user_id = ? AND ended_at IS NOT NULL
                GROUP BY DATE(started_at)
                HAVING SUM(total_reviewed) > 0
                ORDER BY study_date DESC";

        $days = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $days[$row['study_date']] = (int) $row['reviewed'];
            }
            mysqli_stmt_close($stmt);
        }

        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $streak = 0;
        if (isset($days[$today]) || isset($days[$yesterday])) {
            $cursor = new DateTime(isset($days[$today]) ? $today : $yesterday);
            while (isset($days[$cursor->format('Y-m-d')])) {
                $streak++;
                $cursor->modify('-1 day');
            }
        }

        return [
            'reviewedToday' => $days[$today] ?? 0,
            'currentStreak' => $streak,
        ];
    }

    // -----------------------------------------------------------------------
    // Table helpers (pure PHP, no DB)
    // -----------------------------------------------------------------------

    /** Decks with cards due, most due first (oldest overdue breaks ties). */
    public static function dueDecks(array $hubDecks): array
    {
        $due = array_values(array_filter($hubDecks, fn($d) => $d['dueCount'] > 0));
        usort($due, function ($a, $b) {
            return [$b['dueCount'], $b['overdueSeconds'] ?? 0]
               <=> [$a['dueCount'], $a['overdueSeconds'] ?? 0];
        });
        return $due;
    }

    /** Decks with cards coming up soon, soonest first. */
    public static function upcomingDecks(array $hubDecks): array
    {
        $up = array_values(array_filter($hubDecks, fn($d) => $d['upcomingCount'] > 0));
        usort($up, fn($a, $b) => ($a['nextDueSeconds'] ?? PHP_INT_MAX) <=> ($b['nextDueSeconds'] ?? PHP_INT_MAX));
        return $up;
    }

    // -----------------------------------------------------------------------
    // Formatting
    // -----------------------------------------------------------------------

    /** "5 min", "3 h", "2 d" (never shows 0). */
    public static function formatSpan(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds < 3600)  return max(1, (int) floor($seconds / 60)) . ' min';
        if ($seconds < 86400) return (int) floor($seconds / 3600) . ' h';
        return (int) floor($seconds / 86400) . ' d';
    }

    /** "just now", "5 mins ago", "1 hour ago", "3 days ago". */
    public static function formatAgo(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds < 60) return 'just now';

        if ($seconds < 3600) {
            $n = (int) floor($seconds / 60);
            return $n . ($n === 1 ? ' min ago' : ' mins ago');
        }
        if ($seconds < 86400) {
            $n = (int) floor($seconds / 3600);
            return $n . ($n === 1 ? ' hour ago' : ' hours ago');
        }
        $n = (int) floor($seconds / 86400);
        return $n . ($n === 1 ? ' day ago' : ' days ago');
    }

    /** "45s", "4m 12s", "1h 5m", or an em dash when unknown. */
    public static function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) return '—';
        if ($seconds < 60) return $seconds . 's';
        if ($seconds < 3600) {
            $m = (int) floor($seconds / 60);
            $s = $seconds % 60;
            return $s > 0 ? "{$m}m {$s}s" : "{$m}m";
        }
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    /** Tailwind classes for an accuracy badge (matches the deck grid thresholds). */
    public static function accuracyBadgeClass(int $accuracy): string
    {
        if ($accuracy >= 80) return 'text-emerald-700 bg-emerald-50 border-emerald-200/80';
        if ($accuracy >= 50) return 'text-amber-700 bg-amber-50 border-amber-200/80';
        return 'text-rose-700 bg-rose-50 border-rose-200/80';
    }

    /** Shorthand for htmlspecialchars with the project's flags. */
    public static function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}