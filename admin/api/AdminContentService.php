<?php
/**
 * AdminContentService
 *
 * Privacy-first platform content analytics for the Admin Content page.
 * Aggregates deck counts, flashcard counts, SRS interval maturity distribution,
 * due queues, and lapse rates per learner without exposing deck titles or card text.
 */
class AdminContentService
{
    public const PER_PAGE = 15;

    private const SORTS = [
        'name'     => 'u.first_name',
        'decks'    => 'active_decks',
        'cards'    => 'active_cards',
        'due'      => 'due_cards',
        'mature'   => 'mature_cards',
        'interval' => 'avg_interval',
        'lapses'   => 'total_lapses',
    ];

    public static function parseFilters(array $get): array
    {
        $str  = fn($k) => is_string($get[$k] ?? null) ? trim($get[$k]) : '';
        $sort = $str('sort');
        $tier = $str('filter');

        return [
            'q'      => mb_substr($str('q'), 0, 100),
            'filter' => in_array($tier, ['has_decks', 'has_due', 'high_lapses', 'empty'], true) ? $tier : '',
            'sort'   => isset(self::SORTS[$sort]) ? $sort : 'cards',
            'dir'    => $str('dir') === 'asc' ? 'asc' : 'desc',
            'page'   => max(1, (int) ($get['page'] ?? 1)),
        ];
    }

    public static function buildQuery(array $f, array $override = []): string
    {
        $f = array_merge($f, $override);
        $q = array_filter([
            'q'      => $f['q'],
            'filter' => $f['filter'],
            'sort'   => $f['sort'] !== 'cards' ? $f['sort'] : '',
            'dir'    => $f['dir'] !== 'desc' ? $f['dir'] : '',
            'page'   => $f['page'] > 1 ? (string) $f['page'] : '',
        ], fn($v) => $v !== '');
        return $q ? '?' . http_build_query($q) : '';
    }

    /**
     * Platform-wide content & SRS maturity metrics.
     */
    public static function getSummary(mysqli $conn): array
    {
        $out = [
            'activeDecks'   => 0,
            'archivedDecks' => 0,
            'activeCards'   => 0,
            'archivedCards' => 0,
            'dueNow'        => 0,
            'newCards'      => 0,
            'learningCards' => 0,
            'youngCards'    => 0,
            'matureCards'   => 0,
            'avgInterval'   => 0.0,
            'totalLapses'   => 0,
            'created7d'     => 0,
        ];

        $sql = "SELECT
            (SELECT COUNT(*) FROM decks d JOIN users u ON u.id = d.user_id
              WHERE d.deleted_at IS NULL AND u.deleted_at IS NULL) AS active_decks,
            (SELECT COUNT(*) FROM decks d JOIN users u ON u.id = d.user_id
              WHERE d.deleted_at IS NOT NULL AND u.deleted_at IS NULL) AS archived_decks,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL) AS active_cards,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE (f.deleted_at IS NOT NULL OR d.deleted_at IS NOT NULL) AND u.deleted_at IS NULL) AS archived_cards,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL
                AND f.next_review_at <= NOW()) AS due_now,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL
                AND f.review_count = 0) AS new_cards,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL
                AND f.review_count > 0 AND f.interval_days <= 3) AS learning_cards,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL
                AND f.review_count > 0 AND f.interval_days BETWEEN 4 AND 20) AS young_cards,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL
                AND f.review_count > 0 AND f.interval_days >= 21) AS mature_cards,
            (SELECT COALESCE(ROUND(AVG(f.interval_days), 1), 0)
               FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL) AS avg_interval,
            (SELECT COALESCE(SUM(f.lapses), 0)
               FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL) AS total_lapses,
            (SELECT COUNT(*) FROM flashcards f JOIN decks d ON d.id = f.deck_id JOIN users u ON u.id = d.user_id
              WHERE f.deleted_at IS NULL AND d.deleted_at IS NULL AND u.deleted_at IS NULL
                AND f.created_at >= NOW() - INTERVAL 7 DAY) AS created_7d";

        $res = mysqli_query($conn, $sql);
        if ($res) {
            if ($r = mysqli_fetch_assoc($res)) {
                $out['activeDecks']   = (int) $r['active_decks'];
                $out['archivedDecks'] = (int) $r['archived_decks'];
                $out['activeCards']   = (int) $r['active_cards'];
                $out['archivedCards'] = (int) $r['archived_cards'];
                $out['dueNow']        = (int) $r['due_now'];
                $out['newCards']      = (int) $r['new_cards'];
                $out['learningCards'] = (int) $r['learning_cards'];
                $out['youngCards']    = (int) $r['young_cards'];
                $out['matureCards']   = (int) $r['mature_cards'];
                $out['avgInterval']   = (float) $r['avg_interval'];
                $out['totalLapses']   = (int) $r['total_lapses'];
                $out['created7d']     = (int) $r['created_7d'];
            }
            mysqli_free_result($res);
        }

        return $out;
    }

    /**
     * Per-user content inventory (strictly aggregate metrics, no deck/card text).
     */
    public static function getList(mysqli $conn, array $f, bool $exportAll = false): array
    {
        $empty = [
            'available' => false, 'rows' => [], 'total' => 0,
            'page' => 1, 'pages' => 1, 'first' => 0, 'last' => 0,
        ];

        $where  = ["u.deleted_at IS NULL AND u.account_status = 'approved'"];
        $types  = '';
        $params = [];

        if ($f['q'] !== '') {
            $like    = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $types  .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }

        $having = '';
        if ($f['filter'] === 'has_decks') {
            $having = 'HAVING active_decks > 0';
        } elseif ($f['filter'] === 'has_due') {
            $having = 'HAVING due_cards > 0';
        } elseif ($f['filter'] === 'high_lapses') {
            $having = 'HAVING total_lapses >= 10';
        } elseif ($f['filter'] === 'empty') {
            $having = 'HAVING active_decks = 0';
        }

        $whereSql = implode(' AND ', $where);

        $baseSelect = "SELECT u.id, u.first_name, u.last_name, u.email,
            (SELECT COUNT(*) FROM decks d WHERE d.user_id = u.id AND d.deleted_at IS NULL) AS active_decks,
            (SELECT COUNT(*) FROM decks d WHERE d.user_id = u.id AND d.deleted_at IS NOT NULL) AS archived_decks,
            (SELECT COUNT(*) FROM flashcards fc JOIN decks d ON d.id = fc.deck_id
              WHERE d.user_id = u.id AND d.deleted_at IS NULL AND fc.deleted_at IS NULL) AS active_cards,
            (SELECT COUNT(*) FROM flashcards fc JOIN decks d ON d.id = fc.deck_id
              WHERE d.user_id = u.id AND d.deleted_at IS NULL AND fc.deleted_at IS NULL
                AND fc.next_review_at <= NOW()) AS due_cards,
            (SELECT COUNT(*) FROM flashcards fc JOIN decks d ON d.id = fc.deck_id
              WHERE d.user_id = u.id AND d.deleted_at IS NULL AND fc.deleted_at IS NULL
                AND fc.review_count > 0 AND fc.interval_days >= 21) AS mature_cards,
            (SELECT COALESCE(ROUND(AVG(fc.interval_days), 1), 0)
               FROM flashcards fc JOIN decks d ON d.id = fc.deck_id
              WHERE d.user_id = u.id AND d.deleted_at IS NULL AND fc.deleted_at IS NULL) AS avg_interval,
            (SELECT COALESCE(SUM(fc.lapses), 0)
               FROM flashcards fc JOIN decks d ON d.id = fc.deck_id
              WHERE d.user_id = u.id AND d.deleted_at IS NULL AND fc.deleted_at IS NULL) AS total_lapses
            FROM users u
            WHERE $whereSql
            $having";

        $countRows = self::fetchRows($conn, "SELECT COUNT(*) AS n FROM ($baseSelect) AS sub", $types, $params);
        if (!$countRows) {
            return $empty;
        }

        $total  = (int) $countRows[0]['n'];
        $pages  = max(1, (int) ceil($total / self::PER_PAGE));
        $page   = min($f['page'], $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $col      = self::SORTS[$f['sort']] ?? 'active_cards';
        $dir      = $f['dir'] === 'asc' ? 'ASC' : 'DESC';
        $limitSql = $exportAll ? '' : 'LIMIT ? OFFSET ?';

        $sql     = "$baseSelect ORDER BY $col $dir, u.id DESC $limitSql";
        $qTypes  = $exportAll ? $types : $types . 'ii';
        $qParams = $exportAll ? $params : array_merge($params, [self::PER_PAGE, $offset]);
        $rows    = self::fetchRows($conn, $sql, $qTypes, $qParams);

        $out = [];
        foreach ($rows as $r) {
            $name = trim($r['first_name'] . ' ' . $r['last_name']);
            $out[] = [
                'id'            => (int) $r['id'],
                'name'          => $name !== '' ? $name : (string) $r['email'],
                'email'         => (string) $r['email'],
                'activeDecks'   => (int) $r['active_decks'],
                'archivedDecks' => (int) $r['archived_decks'],
                'activeCards'   => (int) $r['active_cards'],
                'dueCards'      => (int) $r['due_cards'],
                'matureCards'   => (int) $r['mature_cards'],
                'avgInterval'   => (float) $r['avg_interval'],
                'totalLapses'   => (int) $r['total_lapses'],
            ];
        }

        return [
            'available' => true,
            'rows'      => $out,
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'first'     => $total ? $offset + 1 : 0,
            'last'      => $offset + count($out),
        ];
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