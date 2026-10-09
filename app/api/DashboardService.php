<?php
/**
 * DashboardService
 * 
 * Centralized service layer for handling database reads, user profiling,
 * spaced repetition metric analytics, and streak tracking.
 */
class DashboardService
{
    /**
     * Retrieve authenticated user profile details.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @return array Profile metadata containing display name.
     */
    public static function getUserProfile(mysqli $conn, int $userId): array
    {
        $sql = "SELECT first_name, last_name FROM users WHERE id = ? AND deleted_at IS NULL";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            return ['displayName' => 'Active Learner'];
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $firstName, $lastName);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
        
        return [
            'displayName' => !empty($fullName) ? $fullName : 'Active Learner'
        ];
    }

    /**
     * Fetch core flashcard and study session metrics.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @return array Calculated user metrics.
     */
    public static function getMetrics(mysqli $conn, int $userId): array
    {
        // 1. Cards Due Today
        $due = self::fetchCount($conn, 
            "SELECT COUNT(*) FROM flashcards f 
             JOIN decks d ON f.deck_id = d.id
             WHERE d.user_id = ? AND d.deleted_at IS NULL AND f.deleted_at IS NULL AND f.next_review_at <= NOW()", 
            $userId
        );

        // 2. Mastered Cards (Interval >= 21 days)
        $mastered = self::fetchCount($conn, 
            "SELECT COUNT(*) FROM flashcards f 
             JOIN decks d ON f.deck_id = d.id 
             WHERE d.user_id = ? AND d.deleted_at IS NULL AND f.deleted_at IS NULL AND f.interval_days >= 21", 
            $userId
        );

        // 3. Tough Cards / Leeches (Lapses >= 8)
        $tough = self::fetchCount($conn, 
            "SELECT COUNT(*) FROM flashcards f 
             JOIN decks d ON f.deck_id = d.id 
             WHERE d.user_id = ? AND d.deleted_at IS NULL AND f.deleted_at IS NULL AND f.lapses >= 8", 
            $userId
        );

        // 4. Cards Reviewed Today
        $reviewedToday = self::fetchCount($conn, 
            "SELECT COALESCE(SUM(total_reviewed), 0) FROM review_sessions 
             WHERE user_id = ? AND ended_at IS NOT NULL AND DATE(started_at) = CURDATE()", 
            $userId
        );

        // 5. Retention Rate Percentage
        $retentionRate = 0.0;
        $retentionSql = "SELECT COALESCE(SUM(remembered_count), 0), COALESCE(SUM(total_reviewed), 0) 
                         FROM review_sessions 
                         WHERE user_id = ? AND ended_at IS NOT NULL";
                         
        if ($stmt = mysqli_prepare($conn, $retentionSql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $remembered, $reviewed);
            if (mysqli_stmt_fetch($stmt) && $reviewed > 0) {
                $retentionRate = round(($remembered / $reviewed) * 100, 1);
            }
            mysqli_stmt_close($stmt);
        }

        return [
            'cardsDue' => $due,
            'masteredCards' => $mastered,
            'toughCards' => $tough,
            'cardsReviewedToday' => $reviewedToday,
            'retentionRate' => $retentionRate
        ];
    }

    /**
     * Compute activity history, streaks, and heatmap data.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @return array Streak counters and calendar heatmap data.
     */
    public static function getStreakAndHeatmapData(mysqli $conn, int $userId): array
    {
        $sessionSql = "SELECT DATE(started_at) as session_date, 
                              SUM(remembered_count) as total_remembered, 
                              SUM(total_reviewed) as total_reviewed
                       FROM review_sessions 
                       WHERE user_id = ? AND ended_at IS NOT NULL
                       GROUP BY DATE(started_at)
                       HAVING SUM(total_reviewed) > 0
                       ORDER BY session_date DESC";

        $stmt = mysqli_prepare($conn, $sessionSql);
        if (!$stmt) {
            return self::emptyStreakState();
        }

        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $activityHistory = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $activityHistory[$row['session_date']] = [
                'remembered' => (int) $row['total_remembered'],
                'reviewed' => (int) $row['total_reviewed']
            ];
        }
        mysqli_stmt_close($stmt);

        if (empty($activityHistory)) {
            return self::emptyStreakState();
        }

        // Streak Analytics Processing
        $todayStr = date('Y-m-d');
        $yesterdayStr = date('Y-m-d', strtotime('-1 day'));
        
        $currentStreak = 0;
        $bestStreak = 0;
        $streakAccuracy = 0.0;
        
        $dates = array_keys($activityHistory);
        $latestDate = $dates[0];

        // Active Streak calculation
        if ($latestDate === $todayStr || $latestDate === $yesterdayStr) {
            $checkDate = new DateTime($latestDate);
            $streakRemembered = 0;
            $streakReviewed = 0;

            while (true) {
                $dateKey = $checkDate->format('Y-m-d');
                if (isset($activityHistory[$dateKey])) {
                    $currentStreak++;
                    $streakRemembered += $activityHistory[$dateKey]['remembered'];
                    $streakReviewed += $activityHistory[$dateKey]['reviewed'];
                    $checkDate->modify('-1 day');
                } else {
                    break;
                }
            }

            if ($streakReviewed > 0) {
                $streakAccuracy = round(($streakRemembered / $streakReviewed) * 100, 1);
            }
        }

        // Best Historical Streak calculation
        $tempStreak = 0;
        $expectedDate = null;

        foreach (array_reverse($dates) as $dateStr) {
            $currentDate = new DateTime($dateStr);
            if ($expectedDate === null || $currentDate == $expectedDate) {
                $tempStreak++;
            } else {
                if ($tempStreak > $bestStreak) {
                    $bestStreak = $tempStreak;
                }
                $tempStreak = 1;
            }
            $expectedDate = clone $currentDate;
            $expectedDate->modify('+1 day');
        }

        if ($tempStreak > $bestStreak) {
            $bestStreak = $tempStreak;
        }

        // Heatmap bounds calculation
        $heatmapEnd = new DateTime('today');
        $heatmapStart = (clone $heatmapEnd)->modify('-364 days');
        $heatmapData = [];
        $studyDays = 0;

        foreach ($activityHistory as $dateStr => $metrics) {
            $activityDate = DateTime::createFromFormat('!Y-m-d', $dateStr);
            if ($activityDate && $activityDate >= $heatmapStart && $activityDate <= $heatmapEnd && $metrics['reviewed'] > 0) {
                $heatmapData[$dateStr] = $metrics['reviewed'];
                $studyDays++;
            }
        }

        $calendarStart = clone $heatmapStart;
        $calendarStart->modify('monday this week');

        return [
            'currentStreak' => $currentStreak,
            'bestStreak' => $bestStreak,
            'streakAccuracy' => $streakAccuracy,
            'studyDays' => $studyDays,
            'heatmapData' => $heatmapData,
            'heatmapStart' => $heatmapStart,
            'heatmapEnd' => $heatmapEnd,
            'calendarStart' => $calendarStart
        ];
    }

    /**
     * Retrieve recent completed study sessions for the activity feed.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @param int $limit Max sessions to return.
     * @return array Session history items.
     */
    public static function getRecentSessions(mysqli $conn, int $userId, int $limit = 4): array
    {
        $sql = "SELECT rs.id, d.title as deck_title, rs.total_reviewed as cards_reviewed, 
                       rs.accuracy_percentage as accuracy, rs.started_at 
                FROM review_sessions rs 
                JOIN decks d ON rs.deck_id = d.id 
                WHERE rs.user_id = ? AND rs.ended_at IS NOT NULL 
                ORDER BY rs.started_at DESC LIMIT ?";

        $sessions = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $timeDiff = time() - strtotime($row['started_at']);
                if ($timeDiff < 3600) {
                    $formatted = floor($timeDiff / 60) . ' mins ago';
                } elseif ($timeDiff < 86400) {
                    $formatted = floor($timeDiff / 3600) . ' hours ago';
                } else {
                    $formatted = floor($timeDiff / 86400) . ' days ago';
                }

                $sessions[] = [
                    'deckTitle' => $row['deck_title'],
                    'cardsReviewed' => (int) $row['cards_reviewed'],
                    'accuracy' => (int) $row['accuracy'],
                    'formattedTime' => $formatted
                ];
            }
            mysqli_stmt_close($stmt);
        }

        return $sessions;
    }

        /**
     * Decks that currently have cards due, most due first.
     *
     * @return array<int, array{id:int,title:string,dueCount:int}>
     */
    public static function getDueByDeck(mysqli $conn, int $userId): array
    {
        $sql = "SELECT d.id, d.title, COUNT(*) AS due_count
                FROM flashcards f
                JOIN decks d ON f.deck_id = d.id
                WHERE d.user_id = ? AND d.deleted_at IS NULL
                  AND f.deleted_at IS NULL AND f.next_review_at <= NOW()
                GROUP BY d.id, d.title
                ORDER BY due_count DESC, d.title ASC";

        $decks = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $decks[] = [
                    'id'       => (int) $row['id'],
                    'title'    => $row['title'],
                    'dueCount' => (int) $row['due_count'],
                ];
            }
            mysqli_stmt_close($stmt);
        }
        return $decks;
    }

    /**
     * Seconds until the next card becomes due, or null if the user has no upcoming cards.
     * Computed in SQL so PHP/MySQL timezone differences cannot skew it.
     */
    public static function getNextDueSeconds(mysqli $conn, int $userId): ?int
    {
        $sql = "SELECT TIMESTAMPDIFF(SECOND, NOW(), MIN(f.next_review_at))
                FROM flashcards f
                JOIN decks d ON f.deck_id = d.id
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
    private static function fetchCount(mysqli $conn, string $query, int $userId): int
    {
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $count);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
            return (int) $count;
        }
        return 0;
    }

    private static function emptyStreakState(): array
    {
        return [
            'currentStreak' => 0,
            'bestStreak' => 0,
            'streakAccuracy' => 0.0,
            'studyDays' => 0,
            'heatmapData' => [],
            'heatmapStart' => new DateTime('-364 days'),
            'heatmapEnd' => new DateTime('today'),
            'calendarStart' => (new DateTime('-364 days'))->modify('monday this week')
        ];
    }
}