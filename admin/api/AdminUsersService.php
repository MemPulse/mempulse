<?php
/**
 * AdminUsersService
 *
 * Industry-standard read & query service for the Admin Users page.
 * Supports active & soft-deleted accounts, role/status/date/search filters,
 * multi-column sorting, per-user SRS aggregate metrics, and per-user audit history.
 */
class AdminUsersService
{
    public const PER_PAGE = 15;
    public const STATUSES = ['pending', 'approved', 'rejected', 'deleted'];
    public const ROLES    = ['admin', 'user'];

    /** Sort key => SQL expression (strict allow-list). */
    private const SORTS = [
        'name'    => 'u.first_name',
        'email'   => 'u.email',
        'role'    => 'u.role',
        'status'  => 'u.account_status',
        'decks'   => 'decks',
        'reviews' => 'reviews',
        'joined'  => 'u.created_at',
    ];

    /**
     * Validate and normalise GET input.
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

    /** Query string builder keeping only non-default parameters. */
    public static function buildQuery(array $f, array $override = []): string
    {
        $f = array_merge($f, $override);
        $q = array_filter([
            'q'      => $f['q'],
            'status' => $f['status'],
            'role'   => $f['role'],
            'from'   => $f['from'],
            'to'     => $f['to'],
            'sort'   => $f['sort'] !== 'joined' ? $f['sort'] : '',
            'dir'    => $f['dir'] !== 'desc' ? $f['dir'] : '',
            'page'   => $f['page'] > 1 ? $f['page'] : '',
        ], fn($v) => $v !== '');
        return $q ? '?' . http_build_query($q) : '';
    }

    /**
     * Account counts per status tab + headline KPIs.
     */
    public static function getStatusCounts(mysqli $conn): array
    {
        $out = [
            'all'      => 0,
            'pending'  => 0,
            'approved' => 0,
            'rejected' => 0,
            'deleted'  => 0,
            'admins'   => 0,
            'active7d' => 0,
        ];

        $rows = self::fetchRows(
            $conn,
            "SELECT
                SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) AS all_live,
                SUM(CASE WHEN deleted_at IS NULL AND account_status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN deleted_at IS NULL AND account_status = 'approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN deleted_at IS NULL AND account_status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS deleted,
                SUM(CASE WHEN deleted_at IS NULL AND role = 'admin' THEN 1 ELSE 0 END) AS admins,
                (SELECT COUNT(DISTINCT rs.user_id)
                   FROM review_sessions rs
                   JOIN users u2 ON u2.id = rs.user_id
                  WHERE u2.deleted_at IS NULL AND rs.started_at >= NOW() - INTERVAL 7 DAY) AS active_7d
             FROM users"
        );

        if ($rows) {
            $r = $rows[0];
            $out['all']      = (int) ($r['all_live'] ?? 0);
            $out['pending']  = (int) ($r['pending'] ?? 0);
            $out['approved'] = (int) ($r['approved'] ?? 0);
            $out['rejected'] = (int) ($r['rejected'] ?? 0);
            $out['deleted']  = (int) ($r['deleted'] ?? 0);
            $out['admins']   = (int) ($r['admins'] ?? 0);
            $out['active7d'] = (int) ($r['active_7d'] ?? 0);
        }

        return $out;
    }

    /**
     * Paginated (or unpaginated for CSV export) user list with per-user aggregates.
     */
    public static function getList(mysqli $conn, array $f, bool $exportAll = false): array
    {
        $empty = [
            'available' => false, 'rows' => [], 'total' => 0,
            'page' => 1, 'pages' => 1, 'first' => 0, 'last' => 0,
        ];

        [$where, $types, $params] = self::buildWhere($f);

        $c = self::fetchRows($conn, "SELECT COUNT(*) AS n FROM users u WHERE $where", $types, $params);
        if (!$c) {
            return $empty;
        }
        $total  = (int) $c[0]['n'];
        $pages  = max(1, (int) ceil($total / self::PER_PAGE));
        $page   = min($f['page'], $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $col  = self::SORTS[$f['sort']] ?? 'u.created_at';
        $dir  = $f['dir'] === 'asc' ? 'ASC' : 'DESC';
        $done = "s.user_id = u.id AND s.ended_at IS NOT NULL";

        $limitSql = $exportAll ? '' : 'LIMIT ? OFFSET ?';
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.role, u.account_status,
                       u.created_at, u.deleted_at,
                  (SELECT COUNT(*) FROM decks d WHERE d.user_id = u.id AND d.deleted_at IS NULL) AS decks,
                  (SELECT COUNT(*) FROM flashcards fc JOIN decks d ON d.id = fc.deck_id
                    WHERE d.user_id = u.id AND d.deleted_at IS NULL AND fc.deleted_at IS NULL) AS cards,
                  (SELECT COUNT(*) FROM review_sessions s WHERE $done) AS sessions,
                  (SELECT COALESCE(SUM(s.total_reviewed), 0) FROM review_sessions s WHERE $done) AS reviews,
                  (SELECT COALESCE(ROUND(SUM(s.remembered_count) / NULLIF(SUM(s.total_reviewed), 0) * 100, 1), 0)
                     FROM review_sessions s WHERE $done) AS accuracy,
                  (SELECT TIMESTAMPDIFF(SECOND, MAX(s.started_at), NOW())
                     FROM review_sessions s WHERE s.user_id = u.id) AS last_active_seconds
                FROM users u
                WHERE $where
                ORDER BY $col $dir, u.id DESC
                $limitSql";

        $queryTypes  = $exportAll ? $types : $types . 'ii';
        $queryParams = $exportAll ? $params : array_merge($params, [self::PER_PAGE, $offset]);
        $rows        = self::fetchRows($conn, $sql, $queryTypes, $queryParams);

        // Fetch recent audit logs for the returned users in one query
        $userIds   = array_map(fn($r) => (int) $r['id'], $rows);
        $auditByUser = self::getAuditHistoryForUsers($conn, $userIds);

        $out = [];
        foreach ($rows as $r) {
            $id        = (int) $r['id'];
            $name      = trim($r['first_name'] . ' ' . $r['last_name']);
            $isDeleted = $r['deleted_at'] !== null;
            $effStatus = $isDeleted ? 'deleted' : (string) $r['account_status'];

            $out[] = [
                'id'         => $id,
                'firstName'  => (string) $r['first_name'],
                'lastName'   => (string) $r['last_name'],
                'name'       => $name !== '' ? $name : (string) $r['email'],
                'email'      => (string) $r['email'],
                'role'       => (string) $r['role'],
                'status'     => $effStatus,
                'rawStatus'  => (string) $r['account_status'],
                'isDeleted'  => $isDeleted,
                'joined'     => date('M j, Y', strtotime($r['created_at'])),
                'joinedIso'  => date('Y-m-d', strtotime($r['created_at'])),
                'deletedAt'  => $isDeleted ? date('M j, Y', strtotime($r['deleted_at'])) : null,
                'decks'      => (int) $r['decks'],
                'cards'      => (int) $r['cards'],
                'sessions'   => (int) $r['sessions'],
                'reviews'    => (int) $r['reviews'],
                'accuracy'   => (float) $r['accuracy'],
                'lastActive' => $r['last_active_seconds'] === null
                    ? 'Never'
                    : AdminOverviewService::timeAgo((int) $r['last_active_seconds']),
                'audit'      => $auditByUser[$id] ?? [],
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

    /**
     * Fetch up to 8 recent audit events per user for the drawer timeline.
     */
    private static function getAuditHistoryForUsers(mysqli $conn, array $userIds): array
    {
        if (!$userIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $types        = str_repeat('i', count($userIds));
        $sql          = "SELECT a.target_user_id, a.action, a.previous_status, a.new_status, a.created_at,
                                CONCAT(COALESCE(adm.first_name,''), ' ', COALESCE(adm.last_name,'')) AS admin_name
                           FROM admin_audit_log a
                           LEFT JOIN users adm ON adm.id = a.admin_id
                          WHERE a.target_user_id IN ($placeholders)
                          ORDER BY a.created_at DESC";

        $rows = self::fetchRows($conn, $sql, $types, $userIds);
        $map  = [];
        foreach ($rows as $r) {
            $uid = (int) $r['target_user_id'];
            if (count($map[$uid] ?? []) >= 6) {
                continue;
            }
            $adminName = trim((string) $r['admin_name']);
            $map[$uid][] = [
                'action' => (string) $r['action'],
                'from'   => $r['previous_status'],
                'to'     => $r['new_status'],
                'by'     => $adminName !== '' ? $adminName : 'Admin',
                'when'   => date('M j, Y g:i A', strtotime($r['created_at'])),
            ];
        }
        return $map;
    }

    /** @return array{0:string,1:string,2:array} */
    private static function buildWhere(array $f): array
    {
        $where  = [];
        $types  = '';
        $params = [];

        if ($f['status'] === 'deleted') {
            $where[] = 'u.deleted_at IS NOT NULL';
        } else {
            $where[] = 'u.deleted_at IS NULL';
            if ($f['status'] !== '') {
                $where[]  = 'u.account_status = ?';
                $types   .= 's';
                $params[] = $f['status'];
            }
        }

        if ($f['q'] !== '') {
            $like    = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $types  .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }
        if ($f['role'] !== '') {
            $where[]  = 'u.role = ?';
            $types   .= 's';
            $params[] = $f['role'];
        }
        if ($f['from'] !== '') {
            $where[]  = 'u.created_at >= ?';
            $types   .= 's';
            $params[] = $f['from'] . ' 00:00:00';
        }
        if ($f['to'] !== '') {
            $where[]  = 'u.created_at < ?';
            $types   .= 's';
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