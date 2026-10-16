<?php
/**
 * AdminUsersService
 *
 * Read-only user listing for the admin Users page. Sort columns and filter
 * values come from fixed allow-lists; all values are bound parameters.
 * Per-user aggregates only: no deck titles or card content.
 */
class AdminUsersService
{
    public const PER_PAGE = 15;
    public const STATUSES = ['pending', 'approved', 'rejected'];
    public const ROLES    = ['admin', 'user'];

    /** Sort key => SQL column (never taken from input directly). */
    private const SORTS = [
        'name'   => 'u.first_name',
        'email'  => 'u.email',
        'role'   => 'u.role',
        'status' => 'u.account_status',
        'joined' => 'u.created_at',
    ];

    /**
     * Validate and normalise GET input.
     *
     * @return array Keys: q, status, role, from, to, sort, dir, page.
     */
    public static function parseFilters(array $get): array
    {
        $str  = fn($k) => is_string($get[$k] ?? null) ? trim($get[$k]) : '';
        $date = function (string $v): string {
            $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
            return ($d && $d->format('Y-m-d') === $v) ? $v : '';
        };
        $sort = $str('sort');

        return [
            'q'      => mb_substr($str('q'), 0, 100),
            'status' => in_array($str('status'), self::STATUSES, true) ? $str('status') : '',
            'role'   => in_array($str('role'), self::ROLES, true) ? $str('role') : '',
            'from'   => $date($str('from')),
            'to'     => $date($str('to')),
            'sort'   => isset(self::SORTS[$sort]) ? $sort : 'joined',
            'dir'    => $str('dir') === 'asc' ? 'asc' : 'desc',
            'page'   => max(1, (int) ($get['page'] ?? 1)),
        ];
    }

    /** Query string for links, keeping only non-default values. */
    public static function buildQuery(array $f, array $override = []): string
    {
        $f = array_merge($f, $override);
        $q = array_filter([
            'q' => $f['q'], 'status' => $f['status'], 'role' => $f['role'],
            'from' => $f['from'], 'to' => $f['to'],
            'sort' => $f['sort'] !== 'joined' ? $f['sort'] : '',
            'dir' => $f['dir'] !== 'desc' ? $f['dir'] : '',
            'page' => $f['page'] > 1 ? $f['page'] : '',
        ], fn($v) => $v !== '');
        return $q ? '?' . http_build_query($q) : '';
    }

    /**
     * Account counts per status (for the tabs), ignoring soft-deleted users.
     *
     * @return array Keys: all, pending, approved, rejected.
     */
    public static function getStatusCounts(mysqli $conn): array
    {
        $out = ['all' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        foreach (self::fetchRows($conn, "SELECT account_status, COUNT(*) AS n FROM users WHERE deleted_at IS NULL GROUP BY account_status") as $r) {
            if (isset($out[$r['account_status']])) {
                $out[$r['account_status']] = (int) $r['n'];
            }
            $out['all'] += (int) $r['n'];
        }
        return $out;
    }

    /**
     * One page of users with per-user aggregates.
     *
     * @return array Keys: available, rows, total, page, pages, first, last.
     */
    public static function getList(mysqli $conn, array $f): array
    {
        $empty = ['available' => false, 'rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'first' => 0, 'last' => 0];

        [$where, $types, $params] = self::buildWhere($f);

        $c = self::fetchRows($conn, "SELECT COUNT(*) AS n FROM users u WHERE $where", $types, $params);
        if (!$c) {
            return $empty;
        }
        $total = (int) $c[0]['n'];
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($f['page'], $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $col = self::SORTS[$f['sort']];
        $dir = $f['dir'] === 'asc' ? 'ASC' : 'DESC';
        $done = "s.user_id = u.id AND s.ended_at IS NOT NULL";

        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.account_status, u.created_at,
                  (SELECT COUNT(*) FROM decks d WHERE d.user_id = u.id AND d.deleted_at IS NULL) AS decks,
                  (SELECT COUNT(*) FROM flashcards fc JOIN decks d ON d.id = fc.deck_id
                    WHERE d.user_id = u.id AND d.deleted_at IS NULL AND fc.deleted_at IS NULL) AS cards,
                  (SELECT COUNT(*) FROM review_sessions s WHERE $done) AS sessions,
                  (SELECT COALESCE(SUM(s.total_reviewed), 0) FROM review_sessions s WHERE $done) AS reviews,
                  (SELECT ROUND(SUM(s.remembered_count) / NULLIF(SUM(s.total_reviewed), 0) * 100, 1)
                     FROM review_sessions s WHERE $done) AS retention,
                  (SELECT TIMESTAMPDIFF(SECOND, MAX(s.started_at), NOW())
                     FROM review_sessions s WHERE s.user_id = u.id) AS last_active_seconds
                FROM users u
                WHERE $where
                ORDER BY $col $dir, u.id DESC
                LIMIT ? OFFSET ?";

        $rows = self::fetchRows($conn, $sql, $types . 'ii', array_merge($params, [self::PER_PAGE, $offset]));

        $out = [];
        foreach ($rows as $r) {
            $name = trim($r['first_name'] . ' ' . $r['last_name']);
            $out[] = [
                'id'         => (int) $r['id'],
                'name'       => $name !== '' ? $name : $r['email'],
                'email'      => (string) $r['email'],
                'role'       => (string) $r['role'],
                'status'     => (string) $r['account_status'],
                'joined'     => date('M j, Y', strtotime($r['created_at'])),
                'decks'      => (int) $r['decks'],
                'cards'      => (int) $r['cards'],
                'sessions'   => (int) $r['sessions'],
                'reviews'    => (int) $r['reviews'],
                'retention'  => $r['retention'] === null ? null : (float) $r['retention'],
                'lastActive' => $r['last_active_seconds'] === null ? 'Never' : AdminOverviewService::timeAgo((int) $r['last_active_seconds']),
            ];
        }

        return [
            'available' => true, 'rows' => $out, 'total' => $total, 'page' => $page, 'pages' => $pages,
            'first' => $total ? $offset + 1 : 0, 'last' => $offset + count($out),
        ];
    }

    // ---- internals --------------------------------------------------------

    /** @return array{0:string,1:string,2:array} where-clause, bind types, params. */
    private static function buildWhere(array $f): array
    {
        $where = ['u.deleted_at IS NULL'];
        $types = '';
        $params = [];

        if ($f['q'] !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $types .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }
        if ($f['status'] !== '') { $where[] = 'u.account_status = ?'; $types .= 's'; $params[] = $f['status']; }
        if ($f['role'] !== '')   { $where[] = 'u.role = ?';           $types .= 's'; $params[] = $f['role']; }
        if ($f['from'] !== '')   { $where[] = 'u.created_at >= ?';    $types .= 's'; $params[] = $f['from'] . ' 00:00:00'; }
        if ($f['to'] !== '') {
            $where[] = 'u.created_at < ?';
            $types .= 's';
            $params[] = (new DateTimeImmutable($f['to']))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        }
        return [implode(' AND ', $where), $types, $params];
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