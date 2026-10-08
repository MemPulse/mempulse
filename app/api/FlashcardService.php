<?php
/**
 * FlashcardService
 * 
 * Centralized service layer for fetching deck details, managing flashcard 
 * operations, and enforcing data integrity and user authorization.
 */
require_once __DIR__ . '/ArchiveService.php';

class FlashcardService
{
    /**
     * Retrieve single deck details and verify user ownership.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $deckId Target deck ID.
     * @param int $userId Authenticated user ID.
     * @return array|null Returns deck array if authorized and active, null otherwise.
     */
    public static function getDeckDetails(mysqli $conn, int $deckId, int $userId): ?array
    {
        $sql = "SELECT d.id, 
                       d.title, 
                       d.created_at,
                       (SELECT COUNT(*) 
                          FROM flashcards f 
                         WHERE f.deck_id = d.id AND f.deleted_at IS NULL) AS card_count,
                       (SELECT COUNT(*) 
                          FROM flashcards f 
                         WHERE f.deck_id = d.id AND f.deleted_at IS NULL AND f.next_review_at <= NOW()) AS due_count,
                       (SELECT COUNT(*)
                          FROM flashcards f
                         WHERE f.deck_id = d.id AND f.deleted_at IS NOT NULL) AS archived_count
                  FROM decks d
                 WHERE d.id = ? AND d.user_id = ? AND d.deleted_at IS NULL";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "ii", $deckId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $deck = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $deck ?: null;
    }

    /**
     * Fetch active flashcards belonging to a specific deck with optional search filtering.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $deckId Target deck ID.
     * @param string $search Search query to match against front or back content.
     * @return array Array of flashcard records.
     */
        public static function getDeckFlashcards(mysqli $conn, int $deckId, string $search = '', bool $archived = false): array
    {
        $search      = trim($search);
        $deletedCond = $archived ? 'IS NOT NULL' : 'IS NULL';
        $orderBy     = $archived ? 'f.deleted_at DESC, f.id DESC' : 'f.id DESC';
        $retention   = ArchiveService::RETENTION_DAYS;

        $sql = "SELECT f.id, f.deck_id, f.front, f.back, f.interval_days, f.next_review_at,
                    f.review_count, f.lapses, f.created_at, f.updated_at, f.deleted_at,
                    GREATEST(0, ? - TIMESTAMPDIFF(DAY, f.deleted_at, NOW())) AS days_left,
                    (SELECT MAX(l.reviewed_at)
                        FROM review_logs l WHERE l.card_id = f.id) AS last_reviewed_at,
                    (SELECT ROUND(100 * SUM(l.rating = 'remembered') / COUNT(*))
                        FROM review_logs l WHERE l.card_id = f.id) AS accuracy,
                    (SELECT ROUND(AVG(l.response_time_ms))
                        FROM review_logs l WHERE l.card_id = f.id) AS avg_response_ms
                FROM flashcards f
                WHERE f.deck_id = ? AND f.deleted_at {$deletedCond}";

        if ($search !== '') {
            $sql .= " AND (f.front LIKE ? OR f.back LIKE ?)";
        }
        $sql .= " ORDER BY {$orderBy}";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return [];
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            mysqli_stmt_bind_param($stmt, "iiss", $retention, $deckId, $like, $like);
        } else {
            mysqli_stmt_bind_param($stmt, "ii", $retention, $deckId);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $cards = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cards[] = [
                'id'               => (int) $row['id'],
                'deck_id'          => (int) $row['deck_id'],
                'front'            => $row['front'],
                'back'             => $row['back'],
                'interval_days'    => (int) $row['interval_days'],
                'next_review_at'   => $row['next_review_at'],
                'review_count'     => (int) $row['review_count'],
                'lapses'           => (int) $row['lapses'],
                'created_at'       => $row['created_at'],
                'updated_at'       => $row['updated_at'],
                'archived_at'      => $row['deleted_at'],
                'days_left'        => $row['days_left'] !== null ? (int) $row['days_left'] : null,
                'last_reviewed_at' => $row['last_reviewed_at'],
                'accuracy'         => $row['accuracy'] !== null ? (int) $row['accuracy'] : null,
                'avg_response_ms'  => $row['avg_response_ms'] !== null ? (int) $row['avg_response_ms'] : null,
            ];
        }
        mysqli_stmt_close($stmt);

        return $cards;
    }

    /**
     * Fetch a single flashcard record and verify deck ownership.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $cardId Target flashcard ID.
     * @param int $userId Authenticated user ID.
     * @return array|null Returns flashcard array if found and authorized, null otherwise.
     */
    public static function getFlashcardById(mysqli $conn, int $cardId, int $userId): ?array
    {
        $sql = "SELECT f.id, f.deck_id, f.front, f.back, f.next_review_at, f.interval_days, f.review_count, f.lapses, f.created_at, f.updated_at
                  FROM flashcards f
                  JOIN decks d ON f.deck_id = d.id
                 WHERE f.id = ? AND d.user_id = ? AND f.deleted_at IS NULL AND d.deleted_at IS NULL";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "ii", $cardId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $card = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $card ?: null;
    }

    /**
     * Safely escape HTML string values for view rendering.
     * 
     * @param mixed $value Raw string content.
     * @return string Escaped HTML string.
     */
    public static function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Fetch all active decks for a user (used for the Move dialog).
     */
    public static function getUserDecks(mysqli $conn, int $userId): array
    {
        $sql = "SELECT id, title FROM decks WHERE user_id = ? AND deleted_at IS NULL ORDER BY title ASC";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $decks = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $decks[] = ['id' => (int)$row['id'], 'title' => $row['title']];
        }
        mysqli_stmt_close($stmt);
        return $decks;
    }

    /**
     * Bulk Duplicate: Copies front/back of specified cards into the same deck as new cards.
     */
    public static function bulkDuplicate(mysqli $conn, int $deckId, array $cardIds, int $userId): array
    {
        if (empty($cardIds)) return [];

        $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
        $types = str_repeat('i', count($cardIds)) . 'ii';
        $params = array_merge($cardIds, [$deckId, $userId]);

        // Fetch original content first to ensure we only copy owned, non-deleted cards
        $fetchSql = "SELECT f.front, f.back FROM flashcards f 
                     JOIN decks d ON f.deck_id = d.id 
                     WHERE f.id IN ($placeholders) AND f.deck_id = ? AND d.user_id = ? AND f.deleted_at IS NULL";

        $stmt = mysqli_prepare($conn, $fetchSql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $cardsToInsert = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cardsToInsert[] = $row;
        }
        mysqli_stmt_close($stmt);

        if (empty($cardsToInsert)) return [];

        mysqli_begin_transaction($conn);
        $newIds = [];
        try {
            $insertStmt = mysqli_prepare($conn, "INSERT INTO flashcards (deck_id, front, back) VALUES (?, ?, ?)");
            foreach ($cardsToInsert as $card) {
                mysqli_stmt_bind_param($insertStmt, "iss", $deckId, $card['front'], $card['back']);
                mysqli_stmt_execute($insertStmt);
                $newIds[] = mysqli_insert_id($conn);
            }
            mysqli_stmt_close($insertStmt);
            mysqli_commit($conn);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            throw $e;
        }

        // Return full card objects for the UI
        $newPlaceholders = implode(',', array_fill(0, count($newIds), '?'));
        $newTypes = str_repeat('i', count($newIds));
        $newStmt = mysqli_prepare($conn, "SELECT id, deck_id, front, back, interval_days, next_review_at, review_count, lapses, created_at, updated_at, deleted_at AS archived_at FROM flashcards WHERE id IN ($newPlaceholders) ORDER BY id DESC");
        mysqli_stmt_bind_param($newStmt, $newTypes, ...$newIds);
        mysqli_stmt_execute($newStmt);
        $newResult = mysqli_stmt_get_result($newStmt);

        $insertedCards = [];
        while ($row = mysqli_fetch_assoc($newResult)) {
            $row['accuracy'] = null;
            $row['avg_response_ms'] = null;
            $row['last_reviewed_at'] = null;
            $row['days_left'] = null;
            $insertedCards[] = $row;
        }
        mysqli_stmt_close($newStmt);
        return $insertedCards;
    }

    /**
     * Bulk Move: Moves specified cards to another owned deck.
     */
    public static function bulkMove(mysqli $conn, int $sourceDeckId, int $targetDeckId, array $cardIds, int $userId): int
    {
        if (empty($cardIds) || $sourceDeckId === $targetDeckId) return 0;

        // Verify target deck ownership
        $checkStmt = mysqli_prepare($conn, "SELECT id FROM decks WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($checkStmt, "ii", $targetDeckId, $userId);
        mysqli_stmt_execute($checkStmt);
        $ownsTarget = mysqli_stmt_num_rows(mysqli_stmt_get_result($checkStmt)) === 1;
        mysqli_stmt_close($checkStmt);
        if (!$ownsTarget) return 0;

        $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
        $types = 'i' . str_repeat('i', count($cardIds)) . 'ii';
        $params = array_merge([$targetDeckId], $cardIds, [$sourceDeckId, $userId]);

        $sql = "UPDATE flashcards f 
                JOIN decks d ON f.deck_id = d.id 
                SET f.deck_id = ? 
                WHERE f.id IN ($placeholders) AND f.deck_id = ? AND d.user_id = ? AND f.deleted_at IS NULL";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected;
    }

    /**
     * Bulk Reset: Resets progress metrics for specified cards.
     */
    public static function bulkReset(mysqli $conn, int $deckId, array $cardIds, int $userId): int
    {
        if (empty($cardIds)) return 0;

        $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
        $types = 'i' . str_repeat('i', count($cardIds)) . 'ii';
        $params = array_merge([$userId], $cardIds, [$deckId, $userId]);

        $sql = "UPDATE flashcards f 
                JOIN decks d ON f.deck_id = d.id 
                SET f.interval_days = COALESCE((SELECT initial_interval_days FROM user_settings WHERE user_id = ?), 1),
                    f.next_review_at = NOW(),
                    f.review_count = 0,
                    f.lapses = 0
                WHERE f.id IN ($placeholders) AND f.deck_id = ? AND d.user_id = ? AND f.deleted_at IS NULL";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return $affected;
    }
}
