<?php
/**
 * FlashcardService
 * 
 * Centralized service layer for fetching deck details, managing flashcard 
 * operations, and enforcing data integrity and user authorization.
 */
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
                         WHERE f.deck_id = d.id AND f.deleted_at IS NULL AND f.next_review_at <= NOW()) AS due_count
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
    public static function getDeckFlashcards(mysqli $conn, int $deckId, string $search = ''): array
    {
        $search = trim($search);
        
        $sql = "SELECT id, 
                       deck_id, 
                       front, 
                       back, 
                       interval_days, 
                       next_review_at, 
                       review_count, 
                       lapses, 
                       created_at,
                       updated_at 
                  FROM flashcards 
                 WHERE deck_id = ? AND deleted_at IS NULL";

        if ($search !== '') {
            $sql .= " AND (front LIKE ? OR back LIKE ?)";
        }

        $sql .= " ORDER BY id DESC";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return [];
        }

        if ($search !== '') {
            $likeParam = '%' . $search . '%';
            mysqli_stmt_bind_param($stmt, "iss", $deckId, $likeParam, $likeParam);
        } else {
            mysqli_stmt_bind_param($stmt, "i", $deckId);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $cards = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cards[] = [
                'id'             => (int) $row['id'],
                'deck_id'        => (int) $row['deck_id'],
                'front'          => $row['front'],
                'back'           => $row['back'],
                'interval_days'  => (int) $row['interval_days'],
                'next_review_at' => $row['next_review_at'],
                'review_count'   => (int) $row['review_count'],
                'lapses'         => (int) $row['lapses'],
                'created_at'     => $row['created_at'],
                'updated_at'     => $row['updated_at'],
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
}