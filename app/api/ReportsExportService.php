<?php
/**
 * ReportsExportService
 *
 * Builds the CSV datasets for the Reports page. Every query is scoped to the
 * signed-in user and reuses ReportsService's filter scopes, so a CSV always
 * matches what the page shows.
 *
 * Each section is ['title' => string, 'headers' => array, 'rows' => iterable].
 * Rows are produced lazily (generators) so large exports are streamed.
 */
class ReportsExportService
{
    public const DATASETS = ['sessions', 'decks', 'cards', 'logs', 'full'];

    /** First characters that make Excel/Sheets treat a cell as a formula. */
    private const FORMULA_CHARS = "=+-@\t\r";

    /**
     * CSV formula-injection guard. Numbers pass through untouched;
     * text starting with = + - @ (or tab / CR) gets an apostrophe prefix.
     */
    public static function safeCell($value)
    {
        if ($value === null) {
            return '';
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }
        $s = (string) $value;
        if ($s !== '' && strpbrk($s[0], self::FORMULA_CHARS) !== false) {
            return "'" . $s;
        }

        return $s;
    }

    public static function sections(string $dataset, mysqli $conn, int $userId, array $f): array
    {
        switch ($dataset) {
            case 'sessions':
                return [self::sessions($conn, $userId, $f)];
            case 'decks':
                return [self::decks($conn, $userId, $f)];
            case 'cards':
                return [self::cards($conn, $userId, $f)];
            case 'logs':
                return [self::logs($conn, $userId, $f)];
            case 'full':
                return [
                    self::summary($conn, $userId, $f),
                    self::sessions($conn, $userId, $f),
                    self::decks($conn, $userId, $f),
                    self::cards($conn, $userId, $f),
                    self::logs($conn, $userId, $f),
                ];
        }

        return [];
    }

    /* ---------------------------------------------------------------- */
    /*  Summary (full report only)                                       */
    /* ---------------------------------------------------------------- */

    private static function summary(mysqli $conn, int $userId, array $f): array
    {
        $o = ReportsService::getOverview($conn, $userId, $f);
        $k = $o['kpis'];
        $s = $o['streak'];

        return [
            'title'   => 'Summary',
            'headers' => ['Measure', 'Value'],
            'rows'    => [
                ['Total reviews', $k['totalReviews']],
                ['Remembered', $k['remembered']],
                ['Forgotten', $k['forgotten']],
                ['Accuracy (%)', $k['accuracy']],
                ['Study time (seconds)', $k['studySeconds']],
                ['Study time', ReportsService::formatDuration($k['studySeconds'])],
                ['Sessions completed', $k['sessions']],
                ['Sessions abandoned', $k['abandoned']],
                ['Completion rate (%)', $k['completionRate']],
                ['Active days', $k['activeDays']],
                ['Days in period', $k['rangeDays']],
                ['Current streak (days, all time)', $s['current']],
                ['Best streak (days, all time)', $s['best']],
            ],
        ];
    }

    /* ---------------------------------------------------------------- */
    /*  Sessions                                                         */
    /* ---------------------------------------------------------------- */

    private static function sessions(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = ReportsService::sessionScope($userId, $f);

        $sql = "SELECT s.id, s.started_at, s.ended_at, s.total_reviewed, s.remembered_count,
                       d.title AS deck_title, d.deleted_at AS deck_deleted
                FROM review_sessions s
                JOIN decks d ON d.id = s.deck_id
                WHERE $where
                ORDER BY s.started_at DESC, s.id DESC";

        $rows = (function () use ($conn, $sql, $types, $params) {
            foreach (self::stream($conn, $sql, $types, $params) as $r) {
                $reviewed   = (int) $r['total_reviewed'];
                $remembered = (int) $r['remembered_count'];
                $done       = $r['ended_at'] !== null;

                yield [
                    (int) $r['id'],
                    $r['started_at'],
                    $done ? $r['ended_at'] : '',
                    $r['deck_title'],
                    $r['deck_deleted'] !== null ? 'Yes' : 'No',
                    $done ? max(0, strtotime($r['ended_at']) - strtotime($r['started_at'])) : '',
                    $reviewed,
                    $remembered,
                    max(0, $reviewed - $remembered),
                    $reviewed > 0 ? round(($remembered / $reviewed) * 100, 1) : '',
                    $done ? 'Completed' : 'Abandoned',
                ];
            }
        })();

        return [
            'title'   => 'Session history',
            'headers' => ['Session ID', 'Started', 'Ended', 'Deck', 'Deck archived', 'Duration (seconds)',
                          'Cards reviewed', 'Remembered', 'Forgot', 'Accuracy (%)', 'Status'],
            'rows'    => $rows,
        ];
    }

    /* ---------------------------------------------------------------- */
    /*  Decks                                                            */
    /* ---------------------------------------------------------------- */

    private static function decks(mysqli $conn, int $userId, array $f): array
    {
        $report = ReportsService::getDecksReport($conn, $userId, $f);
        $rows   = [];

        foreach ($report['rows'] as $r) {
            $rows[] = [
                $r['title'],
                $r['archived'] ? 'Yes' : 'No',
                $r['cards'],
                $r['dueNow'],
                $r['mastered'],
                $r['masteryPct'],
                $r['leeches'],
                $r['reviews'],
                $r['remembered'],
                $r['accuracy'] === null ? '' : $r['accuracy'],
                $r['sessions'],
                $r['seconds'],
                $r['lastStudied'] === null ? '' : $r['lastStudied'],
            ];
        }

        $t = $report['totals'];
        if ($t && count($report['rows']) > 1) {
            $rows[] = [
                'Total (' . $t['decks'] . ' decks)', '',
                $t['cards'], $t['dueNow'], $t['mastered'], $t['masteryPct'], $t['leeches'],
                $t['reviews'], $t['remembered'], $t['accuracy'] === null ? '' : $t['accuracy'],
                $t['sessions'], $t['seconds'], '',
            ];
        }

        return [
            'title'   => 'Deck summary',
            'headers' => ['Deck', 'Archived', 'Cards', 'Due now', 'Mastered', 'Mastered (%)', 'Problem cards',
                          'Reviews (period)', 'Remembered (period)', 'Accuracy (%)', 'Sessions (period)',
                          'Study time (seconds)', 'Last studied'],
            'rows'    => $rows,
        ];
    }

    /* ---------------------------------------------------------------- */
    /*  Cards                                                            */
    /* ---------------------------------------------------------------- */

    private static function cards(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = ReportsService::cardScope($userId, $f);

        $sql = "SELECT f.id, d.title AS deck_title, f.front, f.back, f.interval_days, f.review_count,
                       f.lapses, f.next_review_at, f.created_at,
                       CASE WHEN f.review_count = 0 THEN 'New'
                            WHEN f.interval_days < 7 THEN 'Learning'
                            WHEN f.interval_days < 21 THEN 'Young'
                            ELSE 'Mastered' END AS maturity
                FROM flashcards f
                JOIN decks d ON d.id = f.deck_id
                WHERE $where
                ORDER BY d.title ASC, f.id ASC";

        $rows = (function () use ($conn, $sql, $types, $params) {
            foreach (self::stream($conn, $sql, $types, $params) as $r) {
                yield [
                    (int) $r['id'],
                    $r['deck_title'],
                    $r['front'],
                    $r['back'],
                    $r['maturity'],
                    (int) $r['interval_days'],
                    (int) $r['lapses'],
                    ((int) $r['lapses']) >= 8 ? 'Yes' : 'No',
                    (int) $r['review_count'],
                    $r['next_review_at'],
                    $r['created_at'],
                ];
            }
        })();

        return [
            'title'   => 'Card performance',
            'headers' => ['Card ID', 'Deck', 'Front', 'Back', 'Maturity', 'Interval (days)', 'Lapses',
                          'Problem card', 'Review count', 'Next review', 'Created'],
            'rows'    => $rows,
        ];
    }

    /* ---------------------------------------------------------------- */
    /*  Review log detail                                                */
    /* ---------------------------------------------------------------- */

    private static function logs(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = ReportsService::logScope($userId, $f);

        // LEFT JOIN: reviews of purged cards (card_id NULL) must stay in the export.
        $sql = "SELECT l.id, l.reviewed_at, l.session_id, d.title AS deck_title, l.card_id, f.front,
                       l.rating, l.previous_interval, l.new_interval, l.response_time_ms
                FROM review_logs l
                JOIN review_sessions s ON s.id = l.session_id
                JOIN decks d ON d.id = s.deck_id
                LEFT JOIN flashcards f ON f.id = l.card_id
                WHERE $where
                ORDER BY l.reviewed_at ASC, l.id ASC";

        $rows = (function () use ($conn, $sql, $types, $params) {
            foreach (self::stream($conn, $sql, $types, $params) as $r) {
                yield [
                    (int) $r['id'],
                    $r['reviewed_at'],
                    (int) $r['session_id'],
                    $r['deck_title'],
                    $r['card_id'] === null ? '' : (int) $r['card_id'],
                    $r['card_id'] === null ? 'Deleted card' : (string) $r['front'],
                    $r['rating'] === 'remembered' ? 'Remembered' : 'Forgot',
                    (int) $r['previous_interval'],
                    (int) $r['new_interval'],
                    $r['response_time_ms'] === null ? '' : (int) $r['response_time_ms'],
                ];
            }
        })();

        return [
            'title'   => 'Review log detail',
            'headers' => ['Review ID', 'Reviewed at', 'Session ID', 'Deck', 'Card ID', 'Card', 'Rating',
                          'Previous interval (days)', 'New interval (days)', 'Response time (ms)'],
            'rows'    => $rows,
        ];
    }

    /* ---------------------------------------------------------------- */
    /*  Helpers                                                          */
    /* ---------------------------------------------------------------- */

    /**
     * Run a prepared statement and yield one associative row at a time.
     */
    private static function stream(mysqli $conn, string $sql, string $types, array $params): Generator
    {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return;
        }
        if ($types !== '') {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                yield $row;
            }
            mysqli_free_result($result);
        }
        mysqli_stmt_close($stmt);
    }
}