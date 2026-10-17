<?php
/**
 * AdminOverviewService
 *
 * Read-only data for the admin Overview page. Aggregation and clock math happen
 * in SQL (no PHP/MySQL timezone drift). Every method returns a safe empty
 * structure on failure, never null.
 */
class AdminOverviewService
{
    /** Allowed chart ranges in days (fixed allow-list). */
    public const RANGES = [7, 30, 90];

    /**
     * Platform-wide headline numbers. Only approved, non-deleted users are
     * counted; decks and cards belonging to deleted users are excluded.
     *
     * @return array Keys: available, totalUsers, newUsers7d, pendingUsers, activeToday,
     *               active7d, decks, cards, reviews7d, reviewsPrev7d, retention30d.
     */
    public static function getMetrics(mysqli $conn): array
    {
        $empty = [
            'available' => false, 'totalUsers' => 0, 'newUsers7d' => 0, 'pendingUsers' => 0,
            'activeToday' => 0, 'active7d' => 0, 'decks' => 0, 'cards' => 0,
            'reviews7d' => 0, 'reviewsPrev7d' => 0, 'retention30d' => 0.0,
        ];

        $live = "deleted_at IS NULL AND account_status = 'approved'";
        $sql = "SELECT
            (SELECT COUNT(*) FROM users WHERE $live) AS total_users,
            (SELECT COUNT(*) FROM users WHERE $live AND created_at >= NOW() - INTERVAL 7 DAY) AS new_users_7d,
            (SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND account_status = 'pending') AS pending_users,
            (SELECT COUNT(DISTINCT user_id) FROM review_sessions WHERE started_at >= CURDATE()) AS active_today,
            (SELECT COUNT(DISTINCT user_id) FROM review_sessions WHERE started_at >= NOW() - INTERVAL 7 DAY) AS active_7d,
            (SELECT COUNT(*) FROM decks d JOIN users u ON u.id = d.user_id
              WHERE d.deleted_at IS NULL AND u.deleted_at IS NULL) AS decks,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL) AS cards,
            (SELECT COALESCE(SUM(total_reviewed), 0) FROM review_sessions
              WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 7 DAY) AS reviews_7d,
            (SELECT COALESCE(SUM(total_reviewed), 0) FROM review_sessions
              WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 14 DAY
                AND started_at < NOW() - INTERVAL 7 DAY) AS reviews_prev_7d,
            (SELECT COALESCE(ROUND(SUM(remembered_count) / NULLIF(SUM(total_reviewed), 0) * 100, 1), 0)
               FROM review_sessions
              WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 30 DAY) AS retention_30d";

        $res = mysqli_query($conn, $sql); // static SQL, no user input
        if (!$res) {
            return $empty;
        }
        $r = mysqli_fetch_assoc($res);
        mysqli_free_result($res);
        if (!$r) {
            return $empty;
        }

        return [
            'available'     => true,
            'totalUsers'    => (int) $r['total_users'],
            'newUsers7d'    => (int) $r['new_users_7d'],
            'pendingUsers'  => (int) $r['pending_users'],
            'activeToday'   => (int) $r['active_today'],
            'active7d'      => (int) $r['active_7d'],
            'decks'         => (int) $r['decks'],
            'cards'         => (int) $r['cards'],
            'reviews7d'     => (int) $r['reviews_7d'],
            'reviewsPrev7d' => (int) $r['reviews_prev_7d'],
            'retention30d'  => (float) $r['retention_30d'],
        ];
    }

    /**
     * Daily series with empty days filled so the chart axis is continuous.
     *
     * @param int $days One of self::RANGES (anything else falls back to 30).
     * @return array Keys: labels (Y-m-d), reviews, activeUsers, signups (parallel int arrays).
     */
    public static function getTrend(mysqli $conn, int $days = 30): array
    {
        $days = in_array($days, self::RANGES, true) ? $days : 30;
        $out = ['labels' => [], 'reviews' => [], 'activeUsers' => [], 'signups' => []];

        $today = null;
        if ($res = mysqli_query($conn, 'SELECT CURDATE()')) {
            $row = mysqli_fetch_row($res);
            $today = $row[0] ?? null;
            mysqli_free_result($res);
        }
        if (!$today) {
            return $out;
        }

        $offset = $days - 1;
        $sessions = self::fetchRows($conn,
            "SELECT DATE(started_at) AS d, SUM(total_reviewed) AS reviews, COUNT(DISTINCT user_id) AS active
             FROM review_sessions
             WHERE ended_at IS NOT NULL AND started_at >= CURDATE() - INTERVAL ? DAY
             GROUP BY d", 'i', [$offset]);
        $signups = self::fetchRows($conn,
            "SELECT DATE(created_at) AS d, COUNT(*) AS signups
             FROM users
             WHERE deleted_at IS NULL AND created_at >= CURDATE() - INTERVAL ? DAY
             GROUP BY d", 'i', [$offset]);

        $byDay = [];
        foreach ($sessions as $s) {
            $byDay[$s['d']]['reviews'] = (int) $s['reviews'];
            $byDay[$s['d']]['active']  = (int) $s['active'];
        }
        foreach ($signups as $s) {
            $byDay[$s['d']]['signups'] = (int) $s['signups'];
        }

        $end = new DateTimeImmutable($today);
        for ($i = $offset; $i >= 0; $i--) {
            $key = $end->modify("-$i days")->format('Y-m-d');
            $out['labels'][]      = $key;
            $out['reviews'][]     = $byDay[$key]['reviews'] ?? 0;
            $out['activeUsers'][] = $byDay[$key]['active'] ?? 0;
            $out['signups'][]     = $byDay[$key]['signups'] ?? 0;
        }
        return $out;
    }

    /**
     * Newest accounts (any status), for the sign-ups feed.
     *
     * @return array<int, array{id:int,name:string,email:string,role:string,status:string,age:string}>
     */
    public static function getRecentSignups(mysqli $conn, int $limit = 6): array
    {
        return self::userRows($conn, '', 'u.created_at DESC', $limit);
    }

    /**
     * Accounts waiting for approval, oldest first (the longest-waiting person is first in the queue).
     *
     * @return array<int, array{id:int,name:string,email:string,role:string,status:string,age:string}>
     */
    public static function getPendingUsers(mysqli $conn, int $limit = 5): array
    {
        return self::userRows($conn, "AND u.account_status = 'pending'", 'u.created_at ASC', $limit);
    }

    /**
     * Latest completed sessions platform-wide. Aggregates only: no deck titles or card content.
     *
     * @return array<int, array{name:string,cardsReviewed:int,accuracy:int,age:string}>
     */
    public static function getRecentSessions(mysqli $conn, int $limit = 6): array
    {
        $limit = max(1, min($limit, 50));
        $rows = self::fetchRows($conn,
            "SELECT u.first_name, u.last_name, u.email, rs.total_reviewed, rs.accuracy_percentage,
                    TIMESTAMPDIFF(SECOND, rs.started_at, NOW()) AS age_seconds
             FROM review_sessions rs
             JOIN users u ON u.id = rs.user_id
             WHERE rs.ended_at IS NOT NULL AND u.deleted_at IS NULL
             ORDER BY rs.started_at DESC LIMIT ?", 'i', [$limit]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'name'          => self::displayName($r),
                'cardsReviewed' => (int) $r['total_reviewed'],
                'accuracy'      => (int) round((float) $r['accuracy_percentage']),
                'age'           => self::timeAgo((int) $r['age_seconds']),
            ];
        }
        return $out;
    }

    /**
     * Percentage change chip data, or null when there is nothing to compare.
     *
     * @return array{text:string,tone:string}|null tone is up, down or flat.
     */
    public static function percentChip(int $current, int $previous, string $suffix): ?array
    {
        if ($previous === 0) {
            return $current > 0 ? ['text' => 'New activity', 'tone' => 'up'] : null;
        }
        $pct = (int) round((($current - $previous) / $previous) * 100);
        if ($pct === 0) {
            return ['text' => '0% ' . $suffix, 'tone' => 'flat'];
        }
        return ['text' => ($pct > 0 ? '+' : '') . $pct . '% ' . $suffix, 'tone' => $pct > 0 ? 'up' : 'down'];
    }

    /** Human-friendly relative time from a seconds-ago value computed in SQL. */
    public static function timeAgo(int $seconds): string
    {
        if ($seconds < 60)    return 'Just now';
        if ($seconds < 3600)  return floor($seconds / 60) . ' min ago';
        if ($seconds < 86400) return floor($seconds / 3600) . ' hr ago';
        $d = (int) floor($seconds / 86400);
        return $d . ($d === 1 ? ' day ago' : ' days ago');
    }

    // ---- internals --------------------------------------------------------

    private static function userRows(mysqli $conn, string $extraWhere, string $order, int $limit): array
    {
        $limit = max(1, min($limit, 50));
        // $extraWhere and $order are fixed strings from the two callers above, never user input.
        $rows = self::fetchRows($conn,
            "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.account_status,
                    TIMESTAMPDIFF(SECOND, u.created_at, NOW()) AS age_seconds
             FROM users u
             WHERE u.deleted_at IS NULL $extraWhere
             ORDER BY $order LIMIT ?", 'i', [$limit]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'     => (int) $r['id'],
                'name'   => self::displayName($r),
                'email'  => (string) $r['email'],
                'role'   => (string) $r['role'],
                'status' => (string) $r['account_status'],
                'age'    => self::timeAgo((int) $r['age_seconds']),
            ];
        }
        return $out;
    }

    private static function displayName(array $r): string
    {
        $n = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        return $n !== '' ? $n : (string) ($r['email'] ?? 'Unknown user');
    }

    private static function fetchRows(mysqli $conn, string $sql, string $types = '', array $params = []): array
    {
        $rows = [];
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return $rows;
        }
        if ($types !== '') {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            while ($res && ($row = mysqli_fetch_assoc($res))) {
                $rows[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
        return $rows;
    }
}
