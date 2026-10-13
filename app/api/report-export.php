<?php
/**
 * report-export.php
 *
 * GET report-export.php?dataset=sessions|decks|cards|logs|full
 *                      &range=..&from=..&to=..&deck=..&archived=1&meta=1
 *
 * Session-guarded, read-only, scoped to the signed-in user.
 * Streams a UTF-8 CSV (with BOM) named mempulse-{dataset}-{YYYY-MM-DD}.csv
 */
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Please sign in again.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Method not allowed.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/DashboardService.php';
require_once __DIR__ . '/ReportsService.php';
require_once __DIR__ . '/ReportsExportService.php';

$userId = (int) $_SESSION['user_id'];
session_write_close();   // do not hold the session lock during a long export

$dataset = (string) ($_GET['dataset'] ?? '');
if (!in_array($dataset, ReportsExportService::DATASETS, true)) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Unknown dataset.');
}

// Same validation as the page: bad dates, foreign deck ids, etc. fall back safely.
$filters  = ReportsService::parseFilters($conn, $userId, $_GET);
$withMeta = ($dataset === 'full') || (($_GET['meta'] ?? '') === '1');
$sections = ReportsExportService::sections($dataset, $conn, $userId, $filters);

function csvRow($out, array $row): void
{
    fputcsv($out, array_map([ReportsExportService::class, 'safeCell'], $row), ',', '"', '\\');
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = 'mempulse-' . $dataset . '-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows accents correctly

if ($withMeta) {
    $profile = DashboardService::getUserProfile($conn, $userId);
    csvRow($out, ['MemPulse report']);
    csvRow($out, ['Student', $profile['displayName'] ?? '']);
    csvRow($out, ['Period', $filters['from'] . ' to ' . $filters['to']]);
    csvRow($out, ['Deck', $filters['deckTitle']]);
    csvRow($out, ['Archived decks included', $filters['includeArchived'] ? 'Yes' : 'No']);
    csvRow($out, ['Generated', date('Y-m-d H:i')]);
    fwrite($out, "\n");
}

$titled = ($dataset === 'full');   // title rows only separate the sections of the full report

foreach ($sections as $section) {
    if ($titled) {
        csvRow($out, [$section['title']]);
    }
    csvRow($out, $section['headers']);
    foreach ($section['rows'] as $row) {
        csvRow($out, $row);
    }
    if ($titled) {
        fwrite($out, "\n");
    }
}

fclose($out);
exit;