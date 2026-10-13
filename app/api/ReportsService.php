<?php
/**
 * ReportsService
 *
 * Read-only analytics queries for the Reports page.
 * Every query is scoped to the authenticated user and uses prepared statements.
 *
 * Conventions (same as DashboardService):
 *  - A "completed" session is one with ended_at IS NOT NULL.
 *  - Totals and accuracy come from review_sessions (they survive a card Reset).
 *  - review_logs is only for card-level detail (used by later tabs).
 */
class ReportsService
{
    public const RANGES = ['7d' => 7, '30d' => 30, '90d' => 90, 'all' => 0, 'custom' => 0];
    public const TABS   = ['overview', 'decks', 'cards', 'sessions'];

    /* ------------------------------------------------------------------ */
    /*  FILTERS                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Validate $_GET input into a safe filter array.
     */
    public static function parseFilters(mysqli $conn, int $userId, array $get): array
    {
        $range = (string) ($get['range'] ?? '30d');
        if (!array_key_exists($range, self::RANGES)) {
            $range = '30d';
        }

        $today = new DateTimeImmutable('today');
        $from  = $today->modify('-29 days');
        $to    = $today;

        if ($range === '7d' || $range === '30d' || $range === '90d') {
            $from = $today->modify('-' . (self::RANGES[$range] - 1) . ' days');
        } elseif ($range === 'all') {
            $first = self::firstSessionDate($conn, $userId);
            if ($first !== null) {
                $from = $first;
            }
        } else { // custom
            $f = self::parseDate((string) ($get['from'] ?? ''));
            $t = self::parseDate((string) ($get['to'] ?? ''));
            if ($f !== null && $t !== null) {
                if ($f > $t) {
                    [$f, $t] = [$t, $f];
                }
                if ($t > $today) {
                    $t = $today;
                }
                if ($f > $t) {
                    $f = $t;
                }
                $from = $f;
                $to   = $t;
            } else {
                $range = '30d'; // incomplete custom range falls back safely
            }
        }

        // Deck filter: must belong to this user. An archived deck switches the archived toggle on.
        $deckId          = 0;
        $deckTitle       = 'All decks';
        $includeArchived = (($get['archived'] ?? '') === '1');
        $rawDeck         = (string) ($get['deck'] ?? '');

        if ($rawDeck !== '' && ctype_digit($rawDeck) && (int) $rawDeck > 0) {
            $deck = self::fetchDeckOwned($conn, $userId, (int) $rawDeck);
            if ($deck !== null) {
                $deckId    = (int) $deck['id'];
                $deckTitle = $deck['title'];
                if ($deck['deleted_at'] !== null) {
                    $includeArchived = true;
                }
            }
        }

        // Drill-down deck (separate from the deck filter so the table keeps all decks).
        $focusId    = 0;
        $focusTitle = '';
        $rawFocus   = (string) ($get['focus'] ?? '');

        if ($rawFocus !== '' && ctype_digit($rawFocus) && (int) $rawFocus > 0) {
            $fd = self::fetchDeckOwned($conn, $userId, (int) $rawFocus);
            if ($fd !== null && ($deckId === 0 || $deckId === (int) $fd['id'])) {
                $focusId    = (int) $fd['id'];
                $focusTitle = $fd['title'];
                if ($fd['deleted_at'] !== null) {
                    $includeArchived = true;
                }
            }
        }

        $tab = (string) ($get['tab'] ?? 'overview');
        if (!in_array($tab, self::TABS, true)) {
            $tab = 'overview';
        }

        return [
            'range'           => $range,
            'from'            => $from->format('Y-m-d'),
            'to'              => $to->format('Y-m-d'),
            'fromDt'          => $from->format('Y-m-d') . ' 00:00:00',
            'toExclusive'     => $to->modify('+1 day')->format('Y-m-d') . ' 00:00:00',
            'days'            => (int) $from->diff($to)->days + 1,
            'deckId'          => $deckId,
            'deckTitle'       => $deckTitle,
            'includeArchived' => $includeArchived,
            'tab'             => $tab,
            'focusId'         => $focusId,
            'focusTitle'      => $focusTitle,
        ];
    }

    /**
     * Decks for the filter dropdown.
     */
    public static function getDeckOptions(mysqli $conn, int $userId, bool $includeArchived): array
    {
        $sql = "SELECT id, title, deleted_at FROM decks WHERE user_id = ?"
             . ($includeArchived ? "" : " AND deleted_at IS NULL")
             . " ORDER BY title ASC";
        $rows = self::fetchAll($conn, $sql, 'i', [$userId]);

        return array_map(fn($r) => [
            'id'       => (int) $r['id'],
            'title'    => $r['title'],
            'archived' => $r['deleted_at'] !== null,
        ], $rows);
    }

    /**
     * Build a query string that keeps the current filters (used by tab links and exports).
     */
    public static function buildQuery(array $f, array $override = []): string
    {
        $q = ['range' => $f['range']];
        if ($f['range'] === 'custom') {
            $q['from'] = $f['from'];
            $q['to']   = $f['to'];
        }
        if ($f['deckId'] > 0) {
            $q['deck'] = $f['deckId'];
        }
        if ($f['includeArchived']) {
            $q['archived'] = '1';
        }
        if (($f['focusId'] ?? 0) > 0) {
            $q['focus'] = $f['focusId'];
        }
        $q['tab'] = $f['tab'];

        return http_build_query(array_merge($q, $override));
    }

    /* ------------------------------------------------------------------ */
    /*  OVERVIEW TAB                                                       */
    /* ------------------------------------------------------------------ */

    public static function getOverview(mysqli $conn, int $userId, array $f): array
    {
        return [
            'kpis'     => self::getKpis($conn, $userId, $f),
            'timeline' => self::getTimeline($conn, $userId, $f),
            'forecast' => self::getForecast($conn, $userId, $f),
            'streak'   => self::getStreak($conn, $userId),
        ];
    }

    private static function getKpis(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = self::sessionScope($userId, $f);

        $sql = "SELECT
                    COALESCE(SUM(CASE WHEN s.ended_at IS NOT NULL THEN s.total_reviewed END), 0)   AS reviewed,
                    COALESCE(SUM(CASE WHEN s.ended_at IS NOT NULL THEN s.remembered_count END), 0) AS remembered,
                    COALESCE(SUM(CASE WHEN s.ended_at IS NOT NULL
                                      THEN TIMESTAMPDIFF(SECOND, s.started_at, s.ended_at) END), 0) AS seconds,
                    COUNT(CASE WHEN s.ended_at IS NOT NULL THEN 1 END) AS completed,
                    COUNT(CASE WHEN s.ended_at IS NULL THEN 1 END)     AS abandoned,
                    COUNT(DISTINCT CASE WHEN s.ended_at IS NOT NULL AND s.total_reviewed > 0
                                        THEN DATE(s.started_at) END)   AS active_days
                FROM review_sessions s
                JOIN decks d ON d.id = s.deck_id
                WHERE $where";

        $row = self::fetchAll($conn, $sql, $types, $params)[0] ?? [];

        $reviewed   = (int) ($row['reviewed'] ?? 0);
        $remembered = (int) ($row['remembered'] ?? 0);
        $completed  = (int) ($row['completed'] ?? 0);
        $abandoned  = (int) ($row['abandoned'] ?? 0);
        $started    = $completed + $abandoned;

        return [
            'totalReviews'   => $reviewed,
            'remembered'     => $remembered,
            'forgotten'      => max(0, $reviewed - $remembered),
            'accuracy'       => $reviewed > 0 ? round(($remembered / $reviewed) * 100, 1) : 0.0,
            'studySeconds'   => (int) ($row['seconds'] ?? 0),
            'sessions'       => $completed,
            'abandoned'      => $abandoned,
            'completionRate' => $started > 0 ? round(($completed / $started) * 100, 1) : 0.0,
            'activeDays'     => (int) ($row['active_days'] ?? 0),
            'rangeDays'      => (int) $f['days'],
        ];
    }

    /**
     * Reviews + accuracy per day (range <= 31 days) or per week (longer ranges).
     * Empty buckets are filled so the chart has a continuous axis.
     */
    private static function getTimeline(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = self::sessionScope($userId, $f);

        $granularity = ($f['days'] <= 31) ? 'day' : 'week';
        $bucketExpr  = ($granularity === 'day')
            ? "DATE(s.started_at)"
            : "DATE_SUB(DATE(s.started_at), INTERVAL WEEKDAY(s.started_at) DAY)";

        $sql = "SELECT $bucketExpr AS bucket,
                       SUM(s.total_reviewed)   AS reviewed,
                       SUM(s.remembered_count) AS remembered
                FROM review_sessions s
                JOIN decks d ON d.id = s.deck_id
                WHERE $where AND s.ended_at IS NOT NULL
                GROUP BY bucket
                ORDER BY bucket ASC";

        $byBucket = [];
        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            $byBucket[$r['bucket']] = [(int) $r['reviewed'], (int) $r['remembered']];
        }

        $from = new DateTimeImmutable($f['from']);
        $to   = new DateTimeImmutable($f['to']);

        if ($granularity === 'week') {
            // Snap to Monday so keys match MySQL WEEKDAY() bucketing.
            $from = $from->modify('-' . ((int) $from->format('N') - 1) . ' days');
        }

        $step = ($granularity === 'day') ? '+1 day' : '+7 days';
        $labels = $reviewed = $accuracy = [];

        for ($cursor = $from; $cursor <= $to; $cursor = $cursor->modify($step)) {
            $key = $cursor->format('Y-m-d');
            [$rv, $rm] = $byBucket[$key] ?? [0, 0];

            $labels[]   = $cursor->format('M j');
            $reviewed[] = $rv;
            $accuracy[] = $rv > 0 ? round(($rm / $rv) * 100, 1) : null;
        }

        return [
            'granularity' => $granularity,
            'labels'      => $labels,
            'reviewed'    => $reviewed,
            'accuracy'    => $accuracy,
        ];
    }

    /**
     * Cards coming due. Forward-looking, so the date range does not apply
     * (deck and archived filters do).
     */
    private static function getForecast(mysqli $conn, int $userId, array $f): array
    {
        $where  = "d.user_id = ? AND f.deleted_at IS NULL";
        $types  = 'i';
        $params = [$userId];

        if ($f['deckId'] > 0) {
            $where   .= " AND d.id = ?";
            $types   .= 'i';
            $params[] = $f['deckId'];
        }
        if (!$f['includeArchived']) {
            $where .= " AND d.deleted_at IS NULL";
        }

        $sql = "SELECT CASE WHEN f.next_review_at <= NOW() THEN -1
                            ELSE DATEDIFF(DATE(f.next_review_at), CURDATE()) END AS day_offset,
                       COUNT(*) AS cnt
                FROM flashcards f
                JOIN decks d ON d.id = f.deck_id
                WHERE $where AND f.next_review_at < (CURDATE() + INTERVAL 30 DAY)
                GROUP BY day_offset";

        $counts = [];
        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            $counts[(int) $r['day_offset']] = (int) $r['cnt'];
        }

        $dueNow = $counts[-1] ?? 0;
        $next7  = $dueNow;
        $next30 = $dueNow;
        $labels = ['Due now'];
        $values = [$dueNow];
        $today  = new DateTimeImmutable('today');

        for ($i = 0; $i < 30; $i++) {
            $c = $counts[$i] ?? 0;
            $next30 += $c;
            if ($i < 7) {
                $next7   += $c;
                $labels[] = ($i === 0) ? 'Later today' : (($i === 1) ? 'Tomorrow' : $today->modify("+$i days")->format('D j'));
                $values[] = $c;
            }
        }

        return [
            'dueNow' => $dueNow,
            'next7'  => $next7,
            'next30' => $next30,
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * All-time streaks (not affected by filters), reusing the dashboard logic.
     */
    private static function getStreak(mysqli $conn, int $userId): array
    {
        $s = DashboardService::getStreakAndHeatmapData($conn, $userId);

        return [
            'current' => (int) ($s['currentStreak'] ?? 0),
            'best'    => (int) ($s['bestStreak'] ?? 0),
        ];
    }

     /* ------------------------------------------------------------------ */
    /*  DECKS TAB                                                          */
    /* ------------------------------------------------------------------ */
 
    /**
     * One row per deck: card stats (current state, not date-bound) plus
     * review activity inside the selected period.
     */
    public static function getDecksReport(mysqli $conn, int $userId, array $f): array
    {
        // 1) Card stats per deck. LEFT JOIN so empty decks still appear.
        $where  = "d.user_id = ?";
        $types  = 'i';
        $params = [$userId];
 
        if ($f['deckId'] > 0) {
            $where   .= " AND d.id = ?";
            $types   .= 'i';
            $params[] = $f['deckId'];
        }
        if (!$f['includeArchived']) {
            $where .= " AND d.deleted_at IS NULL";
        }
 
        $sql = "SELECT d.id, d.title, d.deleted_at,
                       COUNT(f.id) AS cards,
                       COALESCE(SUM(f.next_review_at <= NOW()), 0) AS due_now,
                       COALESCE(SUM(f.interval_days >= 21), 0)     AS mastered,
                       COALESCE(SUM(f.lapses >= 8), 0)             AS leeches
                FROM decks d
                LEFT JOIN flashcards f ON f.deck_id = d.id AND f.deleted_at IS NULL
                WHERE $where
                GROUP BY d.id, d.title, d.deleted_at
                ORDER BY d.title ASC";
 
        $decks = [];
        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            $id = (int) $r['id'];
            $decks[$id] = [
                'id'          => $id,
                'title'       => $r['title'],
                'archived'    => $r['deleted_at'] !== null,
                'cards'       => (int) $r['cards'],
                'dueNow'      => (int) $r['due_now'],
                'mastered'    => (int) $r['mastered'],
                'leeches'     => (int) $r['leeches'],
                'masteryPct'  => ((int) $r['cards']) > 0 ? (int) round(((int) $r['mastered'] / (int) $r['cards']) * 100) : 0,
                'reviews'     => 0,
                'remembered'  => 0,
                'sessions'    => 0,
                'seconds'     => 0,
                'accuracy'    => null,
                'lastStudied' => null,
            ];
        }
 
        if (empty($decks)) {
            return ['rows' => [], 'totals' => null, 'charts' => ['reviews' => null, 'mastery' => null]];
        }
 
        // 2) Completed-session activity inside the period.
        [$sWhere, $sTypes, $sParams] = self::sessionScope($userId, $f);
        $sql = "SELECT s.deck_id,
                       SUM(s.total_reviewed)   AS reviewed,
                       SUM(s.remembered_count) AS remembered,
                       COUNT(*)                AS sessions,
                       SUM(TIMESTAMPDIFF(SECOND, s.started_at, s.ended_at)) AS seconds
                FROM review_sessions s
                JOIN decks d ON d.id = s.deck_id
                WHERE $sWhere AND s.ended_at IS NOT NULL
                GROUP BY s.deck_id";
 
        foreach (self::fetchAll($conn, $sql, $sTypes, $sParams) as $r) {
            $id = (int) $r['deck_id'];
            if (!isset($decks[$id])) {
                continue;
            }
            $reviewed   = (int) $r['reviewed'];
            $remembered = (int) $r['remembered'];
 
            $decks[$id]['reviews']    = $reviewed;
            $decks[$id]['remembered'] = $remembered;
            $decks[$id]['sessions']   = (int) $r['sessions'];
            $decks[$id]['seconds']    = (int) $r['seconds'];
            $decks[$id]['accuracy']   = $reviewed > 0 ? round(($remembered / $reviewed) * 100, 1) : null;
        }
 
        // 3) Last studied: all time, not limited by the period.
        $sql = "SELECT s.deck_id, MAX(s.ended_at) AS last_at
                FROM review_sessions s
                WHERE s.user_id = ? AND s.ended_at IS NOT NULL
                GROUP BY s.deck_id";
 
        foreach (self::fetchAll($conn, $sql, 'i', [$userId]) as $r) {
            $id = (int) $r['deck_id'];
            if (isset($decks[$id])) {
                $decks[$id]['lastStudied'] = $r['last_at'];
            }
        }
 
        $rows = array_values($decks);
 
        // Default order: most reviewed first, then by title.
        usort($rows, function ($a, $b) {
            return [$b['reviews'], $a['title']] <=> [$a['reviews'], $b['title']];
        });
 
        // Totals row.
        $totals = ['decks' => count($rows), 'cards' => 0, 'dueNow' => 0, 'mastered' => 0, 'leeches' => 0,
                   'reviews' => 0, 'remembered' => 0, 'sessions' => 0, 'seconds' => 0];
        foreach ($rows as $r) {
            foreach (['cards', 'dueNow', 'mastered', 'leeches', 'reviews', 'remembered', 'sessions', 'seconds'] as $key) {
                $totals[$key] += $r[$key];
            }
        }
        $totals['accuracy']   = $totals['reviews'] > 0 ? round(($totals['remembered'] / $totals['reviews']) * 100, 1) : null;
        $totals['masteryPct'] = $totals['cards'] > 0 ? (int) round(($totals['mastered'] / $totals['cards']) * 100) : 0;
 
        // Chart data (top 10 each).
        $byReviews = array_values(array_filter($rows, fn($r) => $r['reviews'] > 0));
        $byReviews = array_slice($byReviews, 0, 10);
 
        $byCards = array_values(array_filter($rows, fn($r) => $r['cards'] > 0));
        usort($byCards, fn($a, $b) => [$b['cards'], $a['title']] <=> [$a['cards'], $b['title']]);
        $byCards = array_slice($byCards, 0, 10);
 
        return [
            'rows'   => $rows,
            'totals' => $totals,
            'charts' => [
                'reviews' => $byReviews ? [
                    'labels'   => array_column($byReviews, 'title'),
                    'reviews'  => array_column($byReviews, 'reviews'),
                    'accuracy' => array_column($byReviews, 'accuracy'),
                ] : null,
                'mastery' => $byCards ? [
                    'labels'   => array_column($byCards, 'title'),
                    'mastered' => array_column($byCards, 'mastered'),
                    'rest'     => array_map(fn($r) => $r['cards'] - $r['mastered'], $byCards),
                ] : null,
            ],
        ];
    }

    /**
     * Drill-down for the focused deck: accuracy trend in the period + hardest cards.
     * Returns null when no deck is focused.
     */
    public static function getDeckDrilldown(mysqli $conn, int $userId, array $f): ?array
    {
        if (($f['focusId'] ?? 0) <= 0) {
            return null;
        }

        // Same day/week bucketing as the Overview chart, scoped to the focused deck.
        $trend = self::getTimeline($conn, $userId, array_merge($f, ['deckId' => $f['focusId']]));

        $sql = "SELECT f.id, f.front, f.lapses, f.review_count, f.interval_days, f.next_review_at
                FROM flashcards f
                JOIN decks d ON d.id = f.deck_id
                WHERE d.user_id = ? AND f.deck_id = ? AND f.deleted_at IS NULL AND f.lapses > 0
                ORDER BY f.lapses DESC, f.review_count DESC, f.id ASC
                LIMIT 10";

        $cards = [];
        foreach (self::fetchAll($conn, $sql, 'ii', [$userId, $f['focusId']]) as $r) {
            $cards[] = [
                'id'       => (int) $r['id'],
                'front'    => (string) $r['front'],
                'lapses'   => (int) $r['lapses'],
                'reviews'  => (int) $r['review_count'],
                'interval' => (int) $r['interval_days'],
                'next'     => $r['next_review_at'],
            ];
        }

        return [
            'trend'    => $trend,
            'hasTrend' => array_sum($trend['reviewed']) > 0,
            'cards'    => $cards,
        ];
    }

        /* ------------------------------------------------------------------ */
    /*  CARDS TAB                                                          */
    /* ------------------------------------------------------------------ */

    public static function getCardsReport(mysqli $conn, int $userId, array $f): array
    {
        return [
            'maturity' => self::getMaturity($conn, $userId, $f),
            'leeches'  => self::getLeeches($conn, $userId, $f),
            'interval' => self::getIntervalGrowth($conn, $userId, $f),
            'response' => self::getResponseTimes($conn, $userId, $f),
        ];
    }

    /**
     * New = never reviewed. Learning < 7 days, Young 7-20, Mastered 21+.
     * Current state: the date range does not apply.
     */
    private static function getMaturity(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = self::cardScope($userId, $f);

        $sql = "SELECT COUNT(*) AS total,
                       COALESCE(SUM(f.review_count = 0), 0) AS new_cnt,
                       COALESCE(SUM(f.review_count > 0 AND f.interval_days < 7), 0) AS learning,
                       COALESCE(SUM(f.review_count > 0 AND f.interval_days >= 7 AND f.interval_days < 21), 0) AS young,
                       COALESCE(SUM(f.review_count > 0 AND f.interval_days >= 21), 0) AS mastered,
                       COALESCE(SUM(f.lapses >= 8), 0) AS leeches
                FROM flashcards f
                JOIN decks d ON d.id = f.deck_id
                WHERE $where";

        $row   = self::fetchAll($conn, $sql, $types, $params)[0] ?? [];
        $total = (int) ($row['total'] ?? 0);

        $defs = [
            ['New',      'Never reviewed',       (int) ($row['new_cnt'] ?? 0),  '#94a3b8'],
            ['Learning', 'Interval under 7 days', (int) ($row['learning'] ?? 0), '#f59e0b'],
            ['Young',    'Interval 7-20 days',    (int) ($row['young'] ?? 0),    '#6366f1'],
            ['Mastered', 'Interval 21+ days',     (int) ($row['mastered'] ?? 0), '#14b8a6'],
        ];

        $segments = [];
        foreach ($defs as [$label, $rule, $count, $color]) {
            $segments[] = [
                'label' => $label,
                'rule'  => $rule,
                'count' => $count,
                'pct'   => $total > 0 ? (int) round(($count / $total) * 100) : 0,
                'color' => $color,
            ];
        }

        return [
            'total'       => $total,
            'segments'    => $segments,
            'leechTotal'  => (int) ($row['leeches'] ?? 0),
        ];
    }

    /**
     * Cards with lapses >= 8 (same threshold as the dashboard). Top 25.
     */
    private static function getLeeches(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = self::cardScope($userId, $f);

        $sql = "SELECT f.id, f.front, f.lapses, f.review_count, f.interval_days,
                       d.id AS deck_id, d.title AS deck_title, d.deleted_at AS deck_deleted
                FROM flashcards f
                JOIN decks d ON d.id = f.deck_id
                WHERE $where AND f.lapses >= 8
                ORDER BY f.lapses DESC, f.review_count DESC, f.id ASC
                LIMIT 25";

        $rows = [];
        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            $rows[] = [
                'id'       => (int) $r['id'],
                'front'    => (string) $r['front'],
                'lapses'   => (int) $r['lapses'],
                'reviews'  => (int) $r['review_count'],
                'interval' => (int) $r['interval_days'],
                'deckId'   => (int) $r['deck_id'],
                'deck'     => $r['deck_title'],
                'archived' => $r['deck_deleted'] !== null,
            ];
        }

        return $rows;
    }

    /**
     * Average previous vs new interval per week (Monday buckets) from review_logs.
     */
    private static function getIntervalGrowth(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = self::logScope($userId, $f);

        $sql = "SELECT DATE_SUB(DATE(l.reviewed_at), INTERVAL WEEKDAY(l.reviewed_at) DAY) AS bucket,
                       AVG(l.previous_interval) AS avg_prev,
                       AVG(l.new_interval)      AS avg_new,
                       COUNT(*)                 AS n
                FROM review_logs l
                JOIN review_sessions s ON s.id = l.session_id
                JOIN decks d ON d.id = s.deck_id
                WHERE $where
                GROUP BY bucket
                ORDER BY bucket ASC";

        $byWeek = [];
        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            $byWeek[$r['bucket']] = [round((float) $r['avg_prev'], 1), round((float) $r['avg_new'], 1), (int) $r['n']];
        }

        $from = new DateTimeImmutable($f['from']);
        $to   = new DateTimeImmutable($f['to']);
        $from = $from->modify('-' . ((int) $from->format('N') - 1) . ' days'); // snap to Monday

        $labels = $prev = $new = $counts = [];
        for ($cursor = $from; $cursor <= $to; $cursor = $cursor->modify('+7 days')) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('M j');
            $prev[]   = $byWeek[$key][0] ?? null;
            $new[]    = $byWeek[$key][1] ?? null;
            $counts[] = $byWeek[$key][2] ?? 0;
        }

        return [
            'hasData' => !empty($byWeek),
            'labels'  => $labels,
            'prev'    => $prev,
            'new'     => $new,
            'counts'  => $counts,
        ];
    }

    /**
     * Average and median response time (ms) by rating, plus the slowest cards.
     * Median uses window functions (MariaDB 10.2+ / MySQL 8). If the server
     * lacks them the query fails quietly and the median shows as n/a.
     */
    private static function getResponseTimes(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = self::logScope($userId, $f);

        $out = [];
        foreach (['remembered', 'forgot'] as $rating) {
            $out[$rating] = ['total' => 0, 'timed' => 0, 'avg' => null, 'median' => null];
        }

        // COUNT(col) and AVG(col) both skip NULL response times.
        $sql = "SELECT l.rating,
                       COUNT(*) AS total,
                       COUNT(l.response_time_ms) AS timed,
                       AVG(l.response_time_ms)   AS avg_ms
                FROM review_logs l
                JOIN review_sessions s ON s.id = l.session_id
                JOIN decks d ON d.id = s.deck_id
                WHERE $where
                GROUP BY l.rating";

        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            if (!isset($out[$r['rating']])) {
                continue;
            }
            $out[$r['rating']]['total'] = (int) $r['total'];
            $out[$r['rating']]['timed'] = (int) $r['timed'];
            $out[$r['rating']]['avg']   = $r['avg_ms'] !== null ? (int) round((float) $r['avg_ms']) : null;
        }

        $sql = "SELECT t.rating, AVG(t.ms) AS med
                FROM (
                    SELECT l.rating, l.response_time_ms AS ms,
                           ROW_NUMBER() OVER (PARTITION BY l.rating ORDER BY l.response_time_ms) AS rn,
                           COUNT(*)     OVER (PARTITION BY l.rating) AS cnt
                    FROM review_logs l
                    JOIN review_sessions s ON s.id = l.session_id
                    JOIN decks d ON d.id = s.deck_id
                    WHERE $where AND l.response_time_ms IS NOT NULL
                ) t
                WHERE t.rn IN (FLOOR((t.cnt + 1) / 2), FLOOR(t.cnt / 2) + 1)
                GROUP BY t.rating";

        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            if (isset($out[$r['rating']])) {
                $out[$r['rating']]['median'] = (int) round((float) $r['med']);
            }
        }

        // Slowest cards: average time per card, needs 2+ timed reviews.
        $sql = "SELECT f.id, f.front, fd.title AS deck_title,
                       AVG(l.response_time_ms) AS avg_ms, COUNT(*) AS n
                FROM review_logs l
                JOIN review_sessions s ON s.id = l.session_id
                JOIN decks d ON d.id = s.deck_id
                JOIN flashcards f ON f.id = l.card_id AND f.deleted_at IS NULL
                JOIN decks fd ON fd.id = f.deck_id
                WHERE $where AND l.response_time_ms IS NOT NULL
                GROUP BY f.id, f.front, fd.title
                HAVING COUNT(*) >= 2
                ORDER BY avg_ms DESC, f.id ASC
                LIMIT 10";

        $slowest = [];
        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            $slowest[] = [
                'id'    => (int) $r['id'],
                'front' => (string) $r['front'],
                'deck'  => $r['deck_title'],
                'avg'   => (int) round((float) $r['avg_ms']),
                'n'     => (int) $r['n'],
            ];
        }

        return [
            'remembered' => $out['remembered'],
            'forgot'     => $out['forgot'],
            'hasData'    => ($out['remembered']['total'] + $out['forgot']['total']) > 0,
            'slowest'    => $slowest,
        ];
    }
 
        /* ------------------------------------------------------------------ */
    /*  SESSIONS TAB                                                       */
    /* ------------------------------------------------------------------ */

    public static function getSessionsReport(mysqli $conn, int $userId, array $f): array
    {
        $k = self::getKpis($conn, $userId, $f);   // same numbers as the Overview tab

        return [
            'completion' => [
                'completed' => $k['sessions'],
                'abandoned' => $k['abandoned'],
                'started'   => $k['sessions'] + $k['abandoned'],
                'rate'      => $k['completionRate'],
            ],
            'bestTime' => self::getBestTime($conn, $userId, $f),
            'history'  => self::getSessionHistory($conn, $userId, $f),
        ];
    }

    /**
     * Reviews and accuracy by hour of day and by weekday (from review_logs.reviewed_at).
     */
    private static function getBestTime(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = self::logScope($userId, $f);

        $sql = "SELECT HOUR(l.reviewed_at) AS hr, WEEKDAY(l.reviewed_at) AS wd,
                       COUNT(*) AS n, SUM(l.rating = 'remembered') AS ok
                FROM review_logs l
                JOIN review_sessions s ON s.id = l.session_id
                JOIN decks d ON d.id = s.deck_id
                WHERE $where
                GROUP BY hr, wd";

        $hourN = array_fill(0, 24, 0);
        $hourOk = array_fill(0, 24, 0);
        $dayN  = array_fill(0, 7, 0);
        $dayOk = array_fill(0, 7, 0);

        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            $hr = (int) $r['hr'];
            $wd = (int) $r['wd'];   // 0 = Monday
            $hourN[$hr]  += (int) $r['n'];
            $hourOk[$hr] += (int) $r['ok'];
            $dayN[$wd]   += (int) $r['n'];
            $dayOk[$wd]  += (int) $r['ok'];
        }

        $acc = fn($ok, $n) => $n > 0 ? round(($ok / $n) * 100, 1) : null;

        $hourLabels = $hourAcc = [];
        for ($i = 0; $i < 24; $i++) {
            $hourLabels[] = (($i % 12) === 0 ? 12 : $i % 12) . ($i < 12 ? 'a' : 'p');
            $hourAcc[]    = $acc($hourOk[$i], $hourN[$i]);
        }
        $dayAcc = [];
        for ($i = 0; $i < 7; $i++) {
            $dayAcc[] = $acc($dayOk[$i], $dayN[$i]);
        }

        $dayNames   = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $minSample  = 20;

        // Index with the most reviews, or the best accuracy among buckets with enough reviews.
        $pick = function (array $n, array $ok, bool $byAccuracy) use ($minSample): ?int {
            $best = null;
            $bestVal = -1;
            foreach ($n as $i => $cnt) {
                if ($byAccuracy) {
                    if ($cnt < $minSample) {
                        continue;
                    }
                    $val = $ok[$i] / $cnt;
                } else {
                    if ($cnt === 0) {
                        continue;
                    }
                    $val = $cnt;
                }
                if ($val > $bestVal) {
                    $bestVal = $val;
                    $best    = $i;
                }
            }
            return $best;
        };

        $hourName = fn(int $h) => date('g A', mktime($h, 0, 0, 1, 1, 2000));

        $bh = $pick($hourN, $hourOk, false);
        $ah = $pick($hourN, $hourOk, true);
        $bd = $pick($dayN, $dayOk, false);
        $ad = $pick($dayN, $dayOk, true);

        return [
            'hasData'  => array_sum($hourN) > 0,
            'hours'    => ['labels' => $hourLabels, 'reviews' => $hourN, 'accuracy' => $hourAcc],
            'days'     => ['labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 'reviews' => $dayN, 'accuracy' => $dayAcc],
            'insights' => [
                'busiestHour'  => $bh === null ? null : ['label' => $hourName($bh), 'value' => number_format($hourN[$bh]) . ' reviews'],
                'accurateHour' => $ah === null ? null : ['label' => $hourName($ah), 'value' => $acc($hourOk[$ah], $hourN[$ah]) . '%'],
                'busiestDay'   => $bd === null ? null : ['label' => $dayNames[$bd], 'value' => number_format($dayN[$bd]) . ' reviews'],
                'accurateDay'  => $ad === null ? null : ['label' => $dayNames[$ad], 'value' => $acc($dayOk[$ad], $dayN[$ad]) . '%'],
            ],
            'minSample' => $minSample,
        ];
    }

    /**
     * One row per session in the period, newest first (latest 100) plus the total count.
     */
    private static function getSessionHistory(mysqli $conn, int $userId, array $f): array
    {
        [$where, $types, $params] = self::sessionScope($userId, $f);

        $count = self::fetchAll(
            $conn,
            "SELECT COUNT(*) AS c FROM review_sessions s JOIN decks d ON d.id = s.deck_id WHERE $where",
            $types,
            $params
        );
        $total = (int) ($count[0]['c'] ?? 0);

        $sql = "SELECT s.id, s.started_at, s.ended_at, s.total_reviewed, s.remembered_count,
                       d.title AS deck_title, d.deleted_at AS deck_deleted
                FROM review_sessions s
                JOIN decks d ON d.id = s.deck_id
                WHERE $where
                ORDER BY s.started_at DESC, s.id DESC
                LIMIT 100";

        $rows = [];
        foreach (self::fetchAll($conn, $sql, $types, $params) as $r) {
            $reviewed   = (int) $r['total_reviewed'];
            $remembered = (int) $r['remembered_count'];
            $done       = $r['ended_at'] !== null;

            $rows[] = [
                'id'         => (int) $r['id'],
                'startedAt'  => $r['started_at'],
                'completed'  => $done,
                'seconds'    => $done ? max(0, strtotime($r['ended_at']) - strtotime($r['started_at'])) : 0,
                'reviewed'   => $reviewed,
                'remembered' => $remembered,
                'accuracy'   => $reviewed > 0 ? round(($remembered / $reviewed) * 100, 1) : null,
                'deck'       => $r['deck_title'],
                'archived'   => $r['deck_deleted'] !== null,
            ];
        }

        return ['total' => $total, 'rows' => $rows];
    }

    /* ------------------------------------------------------------------ */
    /*  HELPERS                                                            */
    /* ------------------------------------------------------------------ */

    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);

        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    public static function h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Shared WHERE clause for review_sessions s (joined to decks d).
     * Returns [sql, bindTypes, bindParams].
     */
    private static function sessionScope(int $userId, array $f): array
    {
        $sql    = "s.user_id = ? AND s.started_at >= ? AND s.started_at < ?";
        $types  = 'iss';
        $params = [$userId, $f['fromDt'], $f['toExclusive']];

        if ($f['deckId'] > 0) {
            $sql    .= " AND s.deck_id = ?";
            $types  .= 'i';
            $params[] = $f['deckId'];
        }
        if (!$f['includeArchived']) {
            $sql .= " AND d.deleted_at IS NULL";
        }

        return [$sql, $types, $params];
    }

        /**
     * WHERE for flashcards f (joined to decks d): current state, active cards only.
     */
    private static function cardScope(int $userId, array $f): array
    {
        $sql    = "d.user_id = ? AND f.deleted_at IS NULL";
        $types  = 'i';
        $params = [$userId];

        if ($f['deckId'] > 0) {
            $sql    .= " AND d.id = ?";
            $types  .= 'i';
            $params[] = $f['deckId'];
        }
        if (!$f['includeArchived']) {
            $sql .= " AND d.deleted_at IS NULL";
        }

        return [$sql, $types, $params];
    }

    /**
     * WHERE for review_logs l joined to review_sessions s and decks d (the session's deck).
     * Scoped to the user through the session; period applies to reviewed_at.
     */
    private static function logScope(int $userId, array $f): array
    {
        $sql    = "s.user_id = ? AND l.reviewed_at >= ? AND l.reviewed_at < ?";
        $types  = 'iss';
        $params = [$userId, $f['fromDt'], $f['toExclusive']];

        if ($f['deckId'] > 0) {
            $sql    .= " AND s.deck_id = ?";
            $types  .= 'i';
            $params[] = $f['deckId'];
        }
        if (!$f['includeArchived']) {
            $sql .= " AND d.deleted_at IS NULL";
        }

        return [$sql, $types, $params];
    }

    private static function parseDate(string $value): ?DateTimeImmutable
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return ($d !== false && $d->format('Y-m-d') === $value) ? $d : null;
    }

    private static function firstSessionDate(mysqli $conn, int $userId): ?DateTimeImmutable
    {
        $rows = self::fetchAll($conn, "SELECT MIN(DATE(started_at)) AS first_day FROM review_sessions WHERE user_id = ?", 'i', [$userId]);
        $first = $rows[0]['first_day'] ?? null;

        return $first !== null ? self::parseDate((string) $first) : null;
    }

    private static function fetchDeckOwned(mysqli $conn, int $userId, int $deckId): ?array
    {
        $rows = self::fetchAll($conn, "SELECT id, title, deleted_at FROM decks WHERE id = ? AND user_id = ?", 'ii', [$deckId, $userId]);

        return $rows[0] ?? null;
    }

    private static function fetchAll(mysqli $conn, string $sql, string $types, array $params): array
    {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '') {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows   = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
        mysqli_stmt_close($stmt);

        return $rows;
    }
}