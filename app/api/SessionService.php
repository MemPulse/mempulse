<?php
class SessionService
{
    /**
     * Get metadata for a specific session.
     */
    public static function getSessionDetails(mysqli $conn, int $sessionId, int $userId): ?array
    {
        $sql = "SELECT rs.*, d.title AS deck_title,
                       TIMESTAMPDIFF(SECOND, rs.started_at, rs.ended_at) AS duration_seconds
                FROM review_sessions rs
                JOIN decks d ON rs.deck_id = d.id
                WHERE rs.id = ? AND rs.user_id = ?";

        $session = null;
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $sessionId, $userId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $session = [
                    'id'               => (int) $row['id'],
                    'deckId'           => (int) $row['deck_id'],
                    'deckTitle'        => (string) $row['deck_title'],
                    'startedAt'        => $row['started_at'],
                    'totalReviewed'    => (int) $row['total_reviewed'],
                    'rememberedCount'  => (int) $row['remembered_count'],
                    // int, because ReviewHubService::accuracyBadgeClass() expects an int
                    'accuracy'         => (int) round((float) $row['accuracy_percentage']),
                    'durationSeconds'  => $row['duration_seconds'] === null ? null : (int) $row['duration_seconds'],
                ];
            }
            mysqli_stmt_close($stmt);
        }
        return $session;
    }

    /**
     * Get the individual card logs for a session.
     */
    public static function getSessionLogs(mysqli $conn, int $sessionId): array
    {
        $sql = "SELECT rl.rating, rl.previous_interval, rl.new_interval, rl.reviewed_at,
                       f.front, f.back, f.next_review_at, f.deleted_at,
                       TIMESTAMPDIFF(SECOND, NOW(), f.next_review_at) AS next_due_seconds
                FROM review_logs rl
                LEFT JOIN flashcards f ON rl.card_id = f.id
                WHERE rl.session_id = ?
                ORDER BY rl.reviewed_at ASC";

        $logs = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $sessionId);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $logs[] = [
                    'front'             => (string) $row['front'],
                    'back'              => (string) $row['back'],
                    'rating'            => (string) $row['rating'],
                    'previousInterval'  => (int) $row['previous_interval'],
                    'newInterval'       => (int) $row['new_interval'],
                    'nextDueSeconds'    => $row['next_due_seconds'] !== null ? (int) $row['next_due_seconds'] : null,
                    'isDeleted'         => $row['deleted_at'] !== null
                ];
            }
            mysqli_stmt_close($stmt);
        }
        return $logs;
    }

    // -----------------------------------------------------------------------
    // Session History (list page)
    // -----------------------------------------------------------------------

    /**
     * Shared WHERE clause for the history list and its count.
     *
     * Supported filters (all optional):
     *   q           deck title contains this text
     *   from / to   Y-m-d, inclusive, matched against the session start date
     *   minAccuracy 0-100, sessions at or above this accuracy
     *
     * @return array{0:string,1:string,2:array}  [where sql, bind types, bind values]
     */
    private static function historyWhere(int $userId, array $filters): array
    {
        $where  = "rs.user_id = ? AND rs.ended_at IS NOT NULL";
        $types  = "i";
        $params = [$userId];

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where   .= " AND d.title LIKE ?";
            $types   .= "s";
            $params[] = '%' . addcslashes($q, '%_\\') . '%';
        }

        $from = (string) ($filters['from'] ?? '');
        if ($from !== '' && strtotime($from) !== false) {
            $where   .= " AND rs.started_at >= ?";
            $types   .= "s";
            $params[] = date('Y-m-d', strtotime($from)) . ' 00:00:00';
        }

        $to = (string) ($filters['to'] ?? '');
        if ($to !== '' && strtotime($to) !== false) {
            $where   .= " AND rs.started_at < ?";
            $types   .= "s";
            $params[] = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00';
        }

        $minAccuracy = (int) ($filters['minAccuracy'] ?? 0);
        if ($minAccuracy > 0) {
            $where   .= " AND rs.accuracy_percentage >= ?";
            $types   .= "i";
            $params[] = min($minAccuracy, 100);
        }

        return [$where, $types, $params];
    }

    /**
     * Number of completed sessions for a user (used for pagination).
     * Uses the same "completed" rule as the Review Hub (ended_at IS NOT NULL).
     * Joins decks so the count always matches the rows getSessionHistory() can return.
     */
    public static function countCompletedSessions(mysqli $conn, int $userId, array $filters = []): int
    {
        [$where, $types, $params] = self::historyWhere($userId, $filters);

        $sql = "SELECT COUNT(*)
                FROM review_sessions rs
                JOIN decks d ON d.id = rs.deck_id
                WHERE $where";

        $total = 0;
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $total);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);
        }
        return (int) $total;
    }

    /**
     * One page of completed sessions, newest first.
     *
     * @return array<int, array{
     *   id:int, deckId:int, deckTitle:string, startedAt:string,
     *   totalReviewed:int, rememberedCount:int, accuracy:int, durationSeconds:?int
     * }>
     */
    public static function getSessionHistory(mysqli $conn, int $userId, int $limit = 15, int $offset = 0, array $filters = []): array
    {
        $limit  = max(1, min($limit, 100));
        $offset = max(0, $offset);

        [$where, $types, $params] = self::historyWhere($userId, $filters);
        $types   .= "ii";
        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT rs.id, rs.deck_id, d.title AS deck_title, rs.started_at,
                       rs.total_reviewed, rs.remembered_count, rs.accuracy_percentage,
                       TIMESTAMPDIFF(SECOND, rs.started_at, rs.ended_at) AS duration_seconds
                FROM review_sessions rs
                JOIN decks d ON d.id = rs.deck_id
                WHERE $where
                ORDER BY rs.ended_at DESC, rs.id DESC
                LIMIT ? OFFSET ?";

        $sessions = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $sessions[] = [
                    'id'              => (int) $row['id'],
                    'deckId'          => (int) $row['deck_id'],
                    'deckTitle'       => (string) $row['deck_title'],
                    'startedAt'       => (string) $row['started_at'],
                    'totalReviewed'   => (int) $row['total_reviewed'],
                    'rememberedCount' => (int) $row['remembered_count'],
                    'accuracy'        => (int) round((float) $row['accuracy_percentage']),
                    'durationSeconds' => $row['duration_seconds'] === null ? null : (int) $row['duration_seconds'],
                ];
            }
            mysqli_stmt_close($stmt);
        }
        return $sessions;
    }
}