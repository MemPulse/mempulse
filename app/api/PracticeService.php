<?php
/**
 * PracticeService: READ-ONLY card lookups for Practice mode.
 * Nothing in this file writes to the database (SELECT statements only).
 * Place next to ReviewHubService.php (app/api/).
 */
class PracticeService
{
    const MAX_CARDS = 300;

    /** Which cards fields review.js needs (same shape as the due-cards query). */
    private const CARD_COLS = 'f.id, f.front, f.back, f.interval_days, f.next_review_at';

    /**
     * Parse "5,9,14" into positive unique ints, original order, first MAX_CARDS only.
     */
    public static function parseIds($raw): array
    {
        if (!is_string($raw) || $raw === '') {
            return [];
        }
        $ids = [];
        foreach (explode(',', $raw) as $part) {
            $part = trim($part);
            if ($part !== '' && ctype_digit($part)) {
                $id = (int) $part;
                if ($id > 0) {
                    $ids[$id] = true; // key = dedupe, keeps first-seen order
                }
            }
            if (count($ids) >= self::MAX_CARDS) {
                break;
            }
        }
        return array_keys($ids);
    }

    /**
     * Cards by id: only this user's, in this deck, not archived/deleted.
     * Returned in the same order as $ids (grid order from the deck page).
     */
    public static function cardsByIds($conn, int $userId, int $deckId, array $ids): array
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0))), 0, self::MAX_CARDS);
        if (!$ids) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT " . self::CARD_COLS . "
                FROM flashcards f
                JOIN decks d ON d.id = f.deck_id
                WHERE f.id IN ($marks) AND f.deck_id = ? AND d.user_id = ?
                  AND d.deleted_at IS NULL AND f.deleted_at IS NULL";
        $params = array_merge($ids, [$deckId, $userId]);
        $types  = str_repeat('i', count($params));

        $byId = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $byId[(int) $row['id']] = $row;
            }
            mysqli_stmt_close($stmt);
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    }

    /**
     * The session's deck and how many distinct cards it logged (including ones whose
     * card_id is now NULL because the card was permanently deleted).
     * Returns null if the session does not exist or is not this user's.
     */
    public static function sessionInfo($conn, int $userId, int $sessionId): ?array
    {
        $sql = "SELECT rs.deck_id, d.title,
                       COUNT(DISTINCT rl.card_id) + SUM(rl.card_id IS NULL) AS requested
                FROM review_sessions rs
                JOIN decks d ON d.id = rs.deck_id
                LEFT JOIN review_logs rl ON rl.session_id = rs.id
                WHERE rs.id = ? AND rs.user_id = ? AND d.user_id = ?
                GROUP BY rs.id, rs.deck_id, d.title";
        $info = null;
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iii", $sessionId, $userId, $userId);
            mysqli_stmt_execute($stmt);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if ($row) {
                $info = [
                    'deckId'    => (int) $row['deck_id'],
                    'deckTitle' => $row['title'],
                    'requested' => (int) $row['requested'],
                ];
            }
        }
        return $info;
    }

    /**
     * Cards of one of the user's sessions, in the order they were reviewed.
     * Archived/deleted cards are left out. A card logged twice appears once.
     */
    public static function cardsFromSession($conn, int $userId, int $sessionId): array
    {
        $sql = "SELECT " . self::CARD_COLS . "
                FROM review_sessions rs
                JOIN review_logs rl ON rl.session_id = rs.id
                JOIN flashcards f ON f.id = rl.card_id
                JOIN decks d ON d.id = f.deck_id
                WHERE rs.id = ? AND rs.user_id = ? AND d.user_id = ?
                  AND d.deleted_at IS NULL AND f.deleted_at IS NULL
                ORDER BY rl.id ASC";
        $cards = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "iii", $sessionId, $userId, $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $cards[(int) $row['id']] ??= $row; // first occurrence wins
            }
            mysqli_stmt_close($stmt);
        }
        return array_slice(array_values($cards), 0, self::MAX_CARDS);
    }

    /**
     * Where "Back" goes. The server decides from an allow-list; never a free URL.
     */
    public static function returnUrl(string $from, int $deckId, int $sessionId): string
    {
        switch ($from) {
            case 'deck':
                return $deckId > 0 ? 'deck.php?deck_id=' . $deckId : 'review.php';
            case 'sessions':
                return 'sessions.php';
            case 'reports':
                return 'reports.php';
            case 'session':
                return $sessionId > 0 ? 'session-details.php?id=' . $sessionId : 'sessions.php';
            default:
                return 'review.php';
        }
    }
}