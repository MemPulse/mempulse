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
                    'accuracy'         => (float) $row['accuracy_percentage'],
                    'durationSeconds'  => (int) $row['duration_seconds'],
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
}