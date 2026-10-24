<?php
/**
 * AdminAuditService
 *
 * Read-only query service for the append-only admin_audit_log table.
 * Supports action tabs, admin actor filter, date range, search (target name/email/IP),
 * pagination, and full CSV export.
 */
class AdminAuditService
{
    public const PER_PAGE = 20;

    public const ACTIONS = [
        'user_approved'        => 'Approved User',
        'user_rejected'        => 'Suspended User',
        'user_deleted'         => 'Archived User',
        'user_restored'        => 'Restored User',
        'role_promoted_admin'  => 'Promoted to Admin',
        'role_demoted_user'    => 'Demoted to User',
        'user_created_by_admin'=> 'Created User',
    ];

    private const SORTS = [
        'when'   => 'a.created_at',
        'action' => 'a.action',
        'target' => 'a.target_name',
        'admin'  => 'adm.first_name',
    ];

    /**
     * Validate and normalise GET parameters.
     */
    public static function parseFilters(array $get): array
    {
        $str  = fn($k) => is_string($get[$k] ?? null) ? trim($get[$k]) : '';
        $date = function (string $v): string {
            $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
            return ($d && $d->format('Y-m-d') === $v) ? $v : '';
        };
        $sort    = $str('sort');
        $adminId = filter_var($get['admin_id'] ?? null, FILTER_VALIDATE_INT);

        return [
            'q'        => mb_substr($str('q'), 0, 100),
            'action'   => isset(self::ACTIONS[$str('action')]) ? $str('action') : '',
            'admin_id' => ($adminId && $adminId > 0) ? (int) $adminId : 0,
            'from'     => $date($str('from')),
            'to'       => $date($str('to')),
            'sort'     => isset(self::SORTS[$sort]) ? $sort : 'when',
            'dir'      => $str('dir') === 'asc' ? 'asc' : 'desc',
            'page'     => max(1, (int) ($get['page'] ?? 1)),
        ];
    }

    /** Build URL query string preserving active filters. */
    public static function buildQuery(array $f, array $override = []): string
    {
        $f = array_merge($f, $override);
        $q = array_filter([
            'q'        => $f['q'],
            'action'   => $f['action'],
            'admin_id' => $f['admin_id'] > 0 ? (string) $f['admin_id'] : '',
            'from'     => $f['from'],
            'to'       => $f['to'],
            'sort'     => $f['sort'] !== 'when' ? $f['sort'] : '',
            'dir'      => $f['dir'] !== 'desc' ? $f['dir'] : '',
            'page'     => $f['page'] > 1 ? (string) $f['page'] : '',
        ], fn($v) => $v !== '');
        return $q ? '?' . http_build_query($q) : '';
    }

    /**
     * Summary KPI counts for the Audit Log header cards.
     */
    public static function getSummary(mysqli $conn): array
    {
        $out = [
            'total'      => 0,
            'last24h'    => 0,
            'last7d'     => 0,
            'approvals'  => 0,
            'moderation' => 0,
            'roles'      => 0,
        ];

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN created_at >= NOW() - INTERVAL 1 DAY THEN 1 ELSE 0 END) AS last_24h,
                    SUM(CASE WHEN created_at >= NOW() - INTERVAL 7 DAY THEN 1 ELSE 0 END) AS last_7d,
                    SUM(CASE WHEN action IN ('user_approved', 'user_created_by_admin', 'user_restored') THEN 1 ELSE 0 END) AS approvals,
                    SUM(CASE WHEN action IN ('user_rejected', 'user_deleted') THEN 1 ELSE 0 END) AS moderation,
                    SUM(CASE WHEN action IN ('role_promoted_admin', 'role_demoted_user') THEN 1 ELSE 0 END) AS roles
                FROM admin_audit_log";

        $rows = self::fetchRows($conn, $sql);
        if ($rows) {
            $r = $rows[0];
            $out['total']      = (int) ($r['total'] ?? 0);
            $out['last24h']    = (int) ($r['last_24h'] ?? 0);
            $out['last7d']     = (int) ($r['last_7d'] ?? 0);
            $out['approvals']  = (int) ($r['approvals'] ?? 0);
            $out['moderation'] = (int) ($r['moderation'] ?? 0);
            $out['roles']      = (int) ($r['roles'] ?? 0);
        }

        return $out;
    }

    /**
     * List of administrators who have recorded audit entries (for the filter dropdown).
     */
    public static function getActors(mysqli $conn): array
    {
        $sql = "SELECT DISTINCT u.id, u.first_name, u.last_name, u.email
                  FROM admin_audit_log a
                  JOIN users u ON u.id = a.admin_id
                 ORDER BY u.first_name ASC, u.last_name ASC";
        $rows = self::fetchRows($conn, $sql);
        $out  = [];
        foreach ($rows as $r) {
            $name = trim($r['first_name'] . ' ' . $r['last_name']);
            $out[] = [
                'id'   => (int) $r['id'],
                'name' => $name !== '' ? $name : (string) $r['email'],
            ];
        }
        return $out;
    }

    /**
     * Paginated (or unpaginated for CSV export) audit log records.
     */
    public static function getList(mysqli $conn, array $f, bool $exportAll = false): array
    {
        $empty = [
            'available' => false, 'rows' => [], 'total' => 0,
            'page' => 1, 'pages' => 1, 'first' => 0, 'last' => 0,
        ];

        [$where, $types, $params] = self::buildWhere($f);

        $c = self::fetchRows(
            $conn,
            "SELECT COUNT(*) AS n
               FROM admin_audit_log a
               LEFT JOIN users adm ON adm.id = a.admin_id
              WHERE $where",
            $types,
            $params
        );
        if (!$c) {
            return $empty;
        }

        $total  = (int) $c[0]['n'];
        $pages  = max(1, (int) ceil($total / self::PER_PAGE));
        $page   = min($f['page'], $pages);
        $offset = ($page - 1) * self::PER_PAGE;

        $col = self::SORTS[$f['sort']] ?? 'a.created_at';
        $dir = $f['dir'] === 'asc' ? 'ASC' : 'DESC';

        $limitSql = $exportAll ? '' : 'LIMIT ? OFFSET ?';
        $sql = "SELECT a.id, a.admin_id, a.action, a.target_user_id, a.target_email, a.target_name,
                       a.previous_status, a.new_status, a.ip_address, a.created_at,
                       TIMESTAMPDIFF(SECOND, a.created_at, NOW()) AS age_seconds,
                       adm.first_name AS admin_first, adm.last_name AS admin_last, adm.email AS admin_email
                  FROM admin_audit_log a
                  LEFT JOIN users adm ON adm.id = a.admin_id
                 WHERE $where
                 ORDER BY $col $dir, a.id DESC
                 $limitSql";

        $qTypes  = $exportAll ? $types : $types . 'ii';
        $qParams = $exportAll ? $params : array_merge($params, [self::PER_PAGE, $offset]);
        $rows    = self::fetchRows($conn, $sql, $qTypes, $qParams);

        $out = [];
        foreach ($rows as $r) {
            $adminName = trim(($r['admin_first'] ?? '') . ' ' . ($r['admin_last'] ?? ''));
            if ($adminName === '') {
                $adminName = (string) ($r['admin_email'] ?? 'System / Deleted Admin');
            }

            $actionKey = (string) $r['action'];
            $out[] = [
                'id'            => (int) $r['id'],
                'adminId'       => $r['admin_id'] !== null ? (int) $r['admin_id'] : null,
                'adminName'     => $adminName,
                'adminEmail'    => (string) ($r['admin_email'] ?? ''),
                'action'        => $actionKey,
                'actionLabel'   => self::ACTIONS[$actionKey] ?? ucwords(str_replace('_', ' ', $actionKey)),
                'targetUserId'  => $r['target_user_id'] !== null ? (int) $r['target_user_id'] : null,
                'targetName'    => (string) $r['target_name'],
                'targetEmail'   => (string) $r['target_email'],
                'prevStatus'    => $r['previous_status'] !== null ? (string) $r['previous_status'] : '—',
                'newStatus'     => $r['new_status'] !== null ? (string) $r['new_status'] : '—',
                'ip'            => $r['ip_address'] !== null && $r['ip_address'] !== '' ? (string) $r['ip_address'] : '—',
                'timestamp'     => date('M j, Y g:i A', strtotime($r['created_at'])),
                'timestampIso'  => (string) $r['created_at'],
                'age'           => AdminOverviewService::timeAgo(max(0, (int) $r['age_seconds'])),
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

    /** @return array{0:string,1:string,2:array} */
    private static function buildWhere(array $f): array
    {
        $where  = ['1=1'];
        $types  = '';
        $params = [];

        if ($f['q'] !== '') {
            $like    = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = "(a.target_name LIKE ? OR a.target_email LIKE ? OR a.ip_address LIKE ? OR CONCAT(COALESCE(adm.first_name,''), ' ', COALESCE(adm.last_name,'')) LIKE ?)";
            $types  .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }
        if ($f['action'] !== '') {
            $where[]  = 'a.action = ?';
            $types   .= 's';
            $params[] = $f['action'];
        }
        if ($f['admin_id'] > 0) {
            $where[]  = 'a.admin_id = ?';
            $types   .= 'i';
            $params[] = $f['admin_id'];
        }
        if ($f['from'] !== '') {
            $where[]  = 'a.created_at >= ?';
            $types   .= 's';
            $params[] = $f['from'] . ' 00:00:00';
        }
        if ($f['to'] !== '') {
            $where[]  = 'a.created_at < ?';
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