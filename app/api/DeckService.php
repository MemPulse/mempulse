<?php
/**
 * DeckService
 * 
 * Centralized service layer for fetching active/archived user decks,
 * computing accuracy metrics, and formatting UI labels.
 */
class DeckService
{
    /**
     * Retrieve active decks for a user with card counts, due counts, and review stats.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @return array Array of formatted deck data objects.
     */
    public static function getUserDecks(mysqli $conn, int $userId): array
    {
        $sql = "SELECT d.id,
                       d.title,
                       (SELECT COUNT(*) FROM flashcards f
                         WHERE f.deck_id = d.id AND f.deleted_at IS NULL) AS card_count,
                       (SELECT COUNT(*) FROM flashcards f
                         WHERE f.deck_id = d.id AND f.deleted_at IS NULL
                           AND f.next_review_at <= NOW()) AS due_count,
                       (SELECT MIN(f.next_review_at) FROM flashcards f
                         WHERE f.deck_id = d.id AND f.deleted_at IS NULL) AS next_due,
                       (SELECT MAX(rs.ended_at) FROM review_sessions rs
                         WHERE rs.deck_id = d.id AND rs.user_id = d.user_id
                           AND rs.ended_at IS NOT NULL) AS last_reviewed,
                       (SELECT SUM(rs.remembered_count) FROM review_sessions rs
                         WHERE rs.deck_id = d.id AND rs.user_id = d.user_id
                           AND rs.ended_at IS NOT NULL) AS remembered,
                       (SELECT SUM(rs.total_reviewed) FROM review_sessions rs
                         WHERE rs.deck_id = d.id AND rs.user_id = d.user_id
                           AND rs.ended_at IS NOT NULL) AS reviewed
                FROM decks d
                WHERE d.user_id = ? AND d.deleted_at IS NULL
                ORDER BY d.created_at DESC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $decks = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $cardCount = (int) $row['card_count'];
            $reviewed  = (int) $row['reviewed'];
            $accuracy  = $reviewed > 0 ? (int) round(((int) $row['remembered'] / $reviewed) * 100) : null;
            $hue       = ((int) $row['id'] * 47) % 360;

            $decks[] = [
                'id'                 => (int) $row['id'],
                'title'              => $row['title'],
                'card_count'         => $cardCount,
                'due_count'          => (int) $row['due_count'],
                'accuracy'           => $accuracy,
                'theme'              => self::accuracyTheme($accuracy),
                'due_text'           => self::dueLabel((int) $row['due_count'], $cardCount, $row['next_due']),
                'last_reviewed_text' => self::lastReviewedLabel($row['last_reviewed']),
                'cover_style'        => "background: linear-gradient(135deg, hsl({$hue} 65% 45%), hsl(" . (($hue + 45) % 360) . " 65% 28%));",
            ];
        }
        mysqli_stmt_close($stmt);

        return $decks;
    }

    /**
     * Retrieve archived (soft-deleted) decks for a user.
     * 
     * @param mysqli $conn Active database connection instance.
     * @param int $userId Authenticated user ID.
     * @return array Array of archived deck objects.
     */
    public static function getArchivedDecks(mysqli $conn, int $userId): array
    {
        $sql = "SELECT d.id,
                       d.title,
                       d.deleted_at AS archive_date,
                       (SELECT COUNT(*) FROM flashcards f WHERE f.deck_id = d.id) AS card_count,
                       (SELECT MAX(rs.ended_at) FROM review_sessions rs WHERE rs.deck_id = d.id AND rs.user_id = d.user_id) AS last_reviewed,
                       (SELECT SUM(rs.remembered_count) FROM review_sessions rs WHERE rs.deck_id = d.id AND rs.user_id = d.user_id) AS remembered,
                       (SELECT SUM(rs.total_reviewed) FROM review_sessions rs WHERE rs.deck_id = d.id AND rs.user_id = d.user_id) AS reviewed
                FROM decks d
                WHERE d.user_id = ? AND d.deleted_at IS NOT NULL
                ORDER BY d.deleted_at DESC";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $archived = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reviewed = (int) $row['reviewed'];
            $accuracy = $reviewed > 0 ? (int) round(((int) $row['remembered'] / $reviewed) * 100) : null;
            $row['accuracy'] = $accuracy;
            $archived[] = $row;
        }
        mysqli_stmt_close($stmt);

        return $archived;
    }

    /**
     * Escape HTML output safely.
     * 
     * @param mixed $value Input value to escape.
     * @return string Safe HTML entity output.
     */
    public static function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Color theme configuration for accuracy badge and progress bar.
     * 
     * @param int|null $pct Accuracy percentage.
     * @return array Class names, icons, and labels.
     */
    public static function accuracyTheme(?int $pct): array
    {
        if ($pct === null) {
            return [
                'badge' => 'bg-surface-container text-on-surface-variant border-outline-variant/40',
                'bar'   => 'bg-outline-variant',
                'icon'  => 'hourglass_empty',
                'label' => 'No reviews yet',
            ];
        }
        if ($pct >= 90) {
            return ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80', 'bar' => 'bg-emerald-500', 'icon' => 'verified', 'label' => $pct . '% Accuracy'];
        }
        if ($pct >= 75) {
            return ['badge' => 'bg-amber-50 text-amber-700 border-amber-200/80', 'bar' => 'bg-amber-500', 'icon' => 'speed', 'label' => $pct . '% Accuracy'];
        }
        return ['badge' => 'bg-rose-50 text-rose-700 border-rose-200/80', 'bar' => 'bg-rose-500', 'icon' => 'trending_down', 'label' => $pct . '% Accuracy'];
    }

    /**
     * Generate human-readable due status label.
     * 
     * @param int $dueCount Cards due now.
     * @param int $cardCount Total cards in deck.
     * @param string|null $nextDue Timestamp of next due card.
     * @return string Formatted string.
     */
    public static function dueLabel(int $dueCount, int $cardCount, ?string $nextDue): string
    {
        if ($cardCount === 0) {
            return 'No cards yet';
        }
        if ($dueCount > 0) {
            return $dueCount . ' due now';
        }
        if ($nextDue === null) {
            return 'Nothing due';
        }
        $diff = (new DateTime())->diff(new DateTime($nextDue));
        if ($diff->days >= 1) {
            return 'Due in ' . $diff->days . ($diff->days === 1 ? ' day' : ' days');
        }
        if ($diff->h >= 1) {
            return 'Due in ' . $diff->h . ($diff->h === 1 ? ' hr' : ' hrs');
        }
        return 'Due in under 1 hr';
    }

    /**
     * Generate human-readable relative time string for last review.
     * 
     * @param string|null $lastReviewed Timestamp of last review session.
     * @return string Relative time string.
     */
    public static function lastReviewedLabel(?string $lastReviewed): string
    {
        if ($lastReviewed === null) {
            return 'Never reviewed';
        }
        $reviewedDate = new DateTime($lastReviewed);
        $now = new DateTime();
        $diff = $now->diff($reviewedDate);

        if ($diff->days === 0) {
            if ($diff->h === 0) {
                return $diff->i <= 1 ? 'Reviewed just now' : 'Reviewed ' . $diff->i . ' mins ago';
            }
            return 'Reviewed ' . $diff->h . ($diff->h === 1 ? ' hr ago' : ' hrs ago');
        }
        if ($diff->days === 1) {
            return 'Reviewed yesterday';
        }
        if ($diff->days < 30) {
            return 'Reviewed ' . $diff->days . ' days ago';
        }
        return 'Reviewed ' . $reviewedDate->format('M j, Y');
    }
}