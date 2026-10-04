<?php
/**
 * ArchiveService
 *
 * Reusable archive / restore / permanent-delete / auto-purge logic built on the
 * soft-delete column (deleted_at). Register new entities in entity().
 */
class ArchiveService
{
    /** Days an archived record is kept before it is permanently purged. */
    public const RETENTION_DAYS = 30;

    /** Whitelisted entities. Ownership is always checked against the user. */
    private static function entity(string $type): ?array
    {
        switch ($type) {
            case 'flashcards':
                return [
                    'table' => 'flashcards',
                    'join'  => 'JOIN decks d ON d.id = t.deck_id',
                    'owner' => 'd.user_id = ?',
                ];
            case 'decks':
                return [
                    'table' => 'decks',
                    'join'  => '',
                    'owner' => 't.user_id = ?',
                ];
        }
        return null;
    }

    private static function run(mysqli $conn, string $sql, string $types, array $params): int
    {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return max(0, (int) $affected);
    }

    /** Archive (soft delete). */
    public static function archive(mysqli $conn, string $type, int $id, int $userId): bool
    {
        $e = self::entity($type);
        if (!$e) return false;
        $sql = "UPDATE {$e['table']} t {$e['join']}
                   SET t.deleted_at = NOW()
                 WHERE t.id = ? AND {$e['owner']} AND t.deleted_at IS NULL";
        return self::run($conn, $sql, 'ii', [$id, $userId]) > 0;
    }

    /** Restore an archived record. */
    public static function restore(mysqli $conn, string $type, int $id, int $userId): bool
    {
        $e = self::entity($type);
        if (!$e) return false;
        $sql = "UPDATE {$e['table']} t {$e['join']}
                   SET t.deleted_at = NULL
                 WHERE t.id = ? AND {$e['owner']} AND t.deleted_at IS NOT NULL";
        return self::run($conn, $sql, 'ii', [$id, $userId]) > 0;
    }

    /** Permanently delete. Only works on records that are already archived. */
    public static function deletePermanently(mysqli $conn, string $type, int $id, int $userId): bool
    {
        $e = self::entity($type);
        if (!$e) return false;
        $sql = "DELETE t FROM {$e['table']} t {$e['join']}
                 WHERE t.id = ? AND {$e['owner']} AND t.deleted_at IS NOT NULL";
        return self::run($conn, $sql, 'ii', [$id, $userId]) > 0;
    }

    /** Permanently remove this user's records archived longer than RETENTION_DAYS. */
    public static function purgeExpired(mysqli $conn, int $userId, array $types = ['flashcards']): void
    {
        foreach ($types as $type) {
            $e = self::entity($type);
            if (!$e) continue;
            $sql = "DELETE t FROM {$e['table']} t {$e['join']}
                     WHERE {$e['owner']}
                       AND t.deleted_at IS NOT NULL
                       AND t.deleted_at < (NOW() - INTERVAL ? DAY)";
            self::run($conn, $sql, 'ii', [$userId, self::RETENTION_DAYS]);
        }
    }
}