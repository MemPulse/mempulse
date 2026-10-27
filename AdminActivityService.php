AdminActivityService.php
```
<?php
/**
 * AdminActivityService
 *
 * Platform-wide study session & review log telemetry for the Admin Activity page.
 * Aggregates retention accuracy, response time (ms), active learners, 90-day trend series,
 * and filterable session history.
 */
class AdminActivityService
{
    public const PER_PAGE = 15;
    public const RANGES   = [7, 30, 90];

    private const SORTS = [
        'started'  => 'rs.started_at',
        'learner'  => 'u.first_name',
        'reviewed' => 'rs.total_reviewed',
        'accuracy' => 'rs.accuracy_percentage',
        'duration' => 'duration_sec',
    ];

    public static function parseFilters(array $get): array
    {
        $str  = fn($k) => is_string($get[$k] ?? null) ? trim($get[$k]) : '';
        $date = function (string $v): string {
            $d = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
            return ($d && $d->format('Y-m-d') === $v) ? $v : '';
        };
        $sort  = $str('sort');
        $state = $str('state');

        return [
            'q'     => mb_substr($str('q'), 0, 100),
            'state' => in_array($state, ['completed', 'in_progress', 'high_acc', 'low_acc'], true) ? $state : '',
            'from'  => $date($str('from')),
            'to'    => $date($str('to')),
            'sort'  => isset(self::SORTS[$sort]) ? $sort : 'started',
            'dir'   => $str('dir') === 'asc' ? 'asc' : 'desc',
            'page'  => max(1, (int) ($get['page'] ?? 1)),
        ];
    }

    public static function buildQuery(array $f, array $override = []): string
    {
        $f = array_merge($f, $override);
        $q = array_filter([
            'q'     => $f['q'],
            'state' => $f['state'],
            'from'  => $f['from'],
            'to'    => $f['to'],
            'sort'  => $f['sort'] !== 'started' ? $f['sort'] : '',
            'dir'   => $f['dir'] !== 'desc' ? $f['dir'] : '',
            'page'  => $f['page'] > 1 ? (string) $f['page'] : '',
        ], fn($v) => $v !== '');
        return $q ? '?' . http_build_query($q) : '';
    }

    public static function getSummary(mysqli $conn): array
    {
        $out = [
            'sessions30d'    => 0,
            'sessionsToday'  => 0,
            'reviews30d'     => 0,
            'retention30d'   => 0.0,
            'avgResponseSec' => 0.0,
            'activeToday'    => 0,
            'active7d'       => 0,
        ];

        $sql = "SELECT
            (SELECT COUNT(*) FROM review_sessions WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 30 DAY) AS sessions_30d,
            (SELECT COUNT(*) FROM review_sessions WHERE ended_at IS NOT NULL AND started_at >= CURDATE()) AS sessions_today,
            (SELECT COALESCE(SUM(total_reviewed), 0) FROM review_sessions WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 30 DAY) AS reviews_30d,
            (SELECT COALESCE(ROUND(SUM(remembered_count) / NULLIF(SUM(total_reviewed), 0) * 100, 1), 0)
               FROM review_sessions WHERE ended_at IS NOT NULL AND started_at >= NOW() - INTERVAL 30 DAY) AS retention_30d,
            (SELECT COALESCE(ROUND(AVG(response_time_ms) / 1000, 2), 0)
               FROM review_logs WHERE response_time_ms IS NOT NULL AND reviewed_at >= NOW() - INTERVAL 30 DAY) AS avg_resp_sec,
            (SELECT COUNT(DISTINCT user_id) FROM review_sessions WHERE started_at >= CURDATE()) AS active_today,
            (SELECT COUNT(DISTINCT user_id) FROM review_sessions WHERE started_at >= NOW() - INTERVAL 7 DAY) AS active_7d";

        $res = mysqli_query($conn, $sql);
        if ($res) {
            if ($r = mysqli_fetch_assoc($res)) {
                $out['sessions30d']    = (int) $r['sessions_30d'];
                $out['sessionsToday']  = (int) $r['sessions_today'];
                $out['reviews30d']     = (int) $r['reviews_30d'];
                $out['retention30d']   = (float) $r['retention_30d'];
                $out['avgResponseSec'] = (float) $r['avg_resp_sec'];
                $out['activeToday']    = (int) $r['active_today'];
                $out['active7d']       = (int) $r['active_7d'];
            }
            mysqli_free_result($res);
        }

        return $out;
    }

    /**
     * Daily series for the Activity chart (Remembered cards, Forgot cards, Retention %).
     *
     * @param int $days One of self::RANGES (defaults to 90 for client-side slicing).
     * @return array Keys: labels, remembered, forgot, retention.
     */
    public static function getTrend(mysqli $conn, int $days = 90): array
    {
        $days = in_array($days, self::RANGES, true) ? $days : 90;
        $out  = ['labels' => [], 'remembered' => [], 'forgot' => [], 'retention' => []];

        $today = null;
        if ($res = mysqli_query($conn, 'SELECT CURDATE()')) {
            $row   = mysqli_fetch_row($res);
            $today = $row[0] ?? null;
            mysqli_free_result($res);
        }
        if (!$today) {
            return $out;
        }

        $offset = $days - 1;
        $rows = self::fetchRows(
            $conn,
            "SELECT DATE(started_at) AS d,
                    COALESCE(SUM(remembered_count), 0) AS rem,
                    COALESCE(SUM(GREATEST(total_reviewed - remembered_count, 0)), 0) AS fgt,
                    COALESCE(ROUND(SUM(remembered_count) / NULLIF(SUM(total_reviewed), 0) * 100, 1), 0) AS ret
               FROM review_sessions
              WHERE ended_at IS NOT NULL AND started_at >= CURDATE() - INTERVAL ? DAY
              GROUP BY d",
            'i',
            [$offset]
        );

        $byDay = [];
        foreach ($rows as $r) {
            $byDay[$r['d']] = [
                'rem' => (int) $r['rem'],
                'fgt' => (int) $r['fgt'],
                'ret' => (float) $r['ret'],
            ];
        }

        $end = new DateTimeImmutable($today);
        for ($i = $offset; $i >= 0; $i--) {
            $key = $end->modify("-$i days")->format('Y-m-d');
            $out['labels'][]     = $key;
            $out['remembered'][] = $byDay[$key]['rem'] ?? 0;
            $out['forgot'][]     = $byDay[$key]['fgt'] ?? 0;
            $out['retention'][]  = $byDay[$key]['ret'] ?? 0;
        }

        return $out;
    }

    public static function getList(mysqli $conn, array $f, bool $exportAll = false): array
    {
        $empty = [
            'available' => false, 'rows' => [], 'total' => 0,
            'page' => 1, 'pages' => 1, 'first' => 0, 'last' => 0,
        ];

        $where  = ['u.deleted_at IS NULL'];
        $types  = '';
        $params = [];

        if ($f['q'] !== '') {
            $like    = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)";
            $types  .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }

        if ($f['state'] === 'completed') {
            $where[] = 'rs.ended_at IS NOT NULL';
        } elseif ($f['state'] === 'in_progress') {
            $where[] = 'rs.ended_at IS NULL';
        } elseif ($f['state'] === 'high_acc') {
            $where[] = 'rs.ended_at IS NOT NULL AND rs.accuracy_percentage >= 85';
        } elseif ($f['state'] === 'low_acc') {
            $where[] = 'rs.ended_at IS NOT NULL AND rs.total_reviewed > 0 AND rs.accuracy_percentage < 60';
        }

        if ($f['from'] !== '') {
            $where[]  = 'rs.started_at >= ?';
            $types   .= 's';
            $params[] = $f['from'] . ' 00:00:00';
        }
        if ($f['to'] !== '') {
            $where[]  = 'rs.started_at < ?';
            $types   .= 's';
            $params[] = (new DateTimeImmutable($f['to']))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        }

        $whereSql = implode(' AND ', $where);

        $c = self::fetchRows(
            $conn,
            "SELECT COUNT(*) AS n FROM review_sessions rs JOIN users u ON u.id = rs.user_id WHERE $whereSql",
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

        $col      = self::SORTS[$f['sort']] ?? 'rs.started_at';
        $dir      = $f['dir'] === 'asc' ? 'ASC' : 'DESC';
        $limitSql = $exportAll ? '' : 'LIMIT ? OFFSET ?';

        $sql = "SELECT rs.id, rs.user_id, rs.started_at, rs.ended_at, rs.total_reviewed,
                       rs.remembered_count, rs.accuracy_percentage,
                       TIMESTAMPDIFF(SECOND, rs.started_at, COALESCE(rs.ended_at, NOW())) AS duration_sec,
                       TIMESTAMPDIFF(SECOND, rs.started_at, NOW()) AS age_seconds,
                       u.first_name, u.last_name, u.email
                  FROM review_sessions rs
                  JOIN users u ON u.id = rs.user_id
                 WHERE $whereSql
                 ORDER BY $col $dir, rs.id DESC
                 $limitSql";

        $qTypes  = $exportAll ? $types : $types . 'ii';
        $qParams = $exportAll ? $params : array_merge($params, [self::PER_PAGE, $offset]);
        $rows    = self::fetchRows($conn, $sql, $qTypes, $qParams);

        $out = [];
        foreach ($rows as $r) {
            $name      = trim($r['first_name'] . ' ' . $r['last_name']);
            $completed = $r['ended_at'] !== null;
            $durSec    = max(0, (int) $r['duration_sec']);
            $durLabel  = $durSec < 60 ? $durSec . 's' : floor($durSec / 60) . 'm ' . ($durSec % 60) . 's';

            $out[] = [
                'id'         => (int) $r['id'],
                'userId'     => (int) $r['user_id'],
                'name'       => $name !== '' ? $name : (string) $r['email'],
                'email'      => (string) $r['email'],
                'started'    => date('M j, Y g:i A', strtotime($r['started_at'])),
                'startedIso' => (string) $r['started_at'],
                'age'        => AdminOverviewService::timeAgo(max(0, (int) $r['age_seconds'])),
                'completed'  => $completed,
                'reviewed'   => (int) $r['total_reviewed'],
                'remembered' => (int) $r['remembered_count'],
                'forgot'     => max(0, (int) $r['total_reviewed'] - (int) $r['remembered_count']),
                'accuracy'   => (float) $r['accuracy_percentage'],
                'duration'   => $completed ? $durLabel : 'In progress',
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
```

