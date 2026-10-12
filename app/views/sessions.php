<?php
/**
 * Session History (list of all completed review sessions)
 * Subpage of Review Hub:  Review Hub > Session History
 * Each row links to session-details.php?id=X
 */
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/SessionService.php';
require_once __DIR__ . '/../api/ReviewHubService.php';

$userId = (int) $_SESSION['user_id'];

// ---- Pagination ----
$perPage    = 15;
$totalCount = SessionService::countCompletedSessions($conn, $userId);
$totalPages = max(1, (int) ceil($totalCount / $perPage));
$page       = isset($_GET['page']) && ctype_digit((string) $_GET['page']) ? (int) $_GET['page'] : 1;
$page       = min(max(1, $page), $totalPages);
$offset     = ($page - 1) * $perPage;

$sessions = SessionService::getSessionHistory($conn, $userId, $perPage, $offset);

$pageTitle   = "Session History";
$currentPage = "review"; // keep sidebar highlight on Review Hub
$h = [ReviewHubService::class, 'h'];

// Page-number window (current page +/- 2)
$firstShown = max(1, $page - 2);
$lastShown  = min($totalPages, $page + 2);
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="pt-16 pl-0 md:pl-72 transition-all duration-300 ease-in-out min-h-screen flex flex-col">
        <?php include __DIR__ . '/../includes/header-data.php'; ?>
        <?php include __DIR__ . '/../includes/header.php'; ?>

        <div class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6">

            <div class="max-w-5xl mx-auto flex flex-col gap-6">

                <!-- Breadcrumb -->
                <nav class="flex items-center gap-2 text-sm font-medium text-on-surface-variant mb-2" aria-label="Breadcrumb">
                    <a href="review.php" class="hover:text-primary transition-colors flex items-center gap-1">
                        <!-- <span class="material-symbols-outlined text-[18px]">home</span> -->
                        Review
                    </a>
                    <span class="material-symbols-outlined text-[16px] opacity-50">chevron_right</span>
                    <span class="text-on-surface" aria-current="page">Session History</span>
                </nav>

                <!-- Page header -->
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 shadow-sm flex items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-[28px] text-primary">history</span>
                            <h1 class="font-headline-md text-2xl font-bold text-on-surface m-0">Session History</h1>
                        </div>
                        <p class="text-sm text-on-surface-variant">
                            <?= (int) $totalCount ?> completed <?= $totalCount === 1 ? 'session' : 'sessions' ?>
                        </p>
                    </div>
                    <!-- <a href="review.php" class="hidden sm:inline-flex items-center gap-1 px-4 py-2 rounded-xl bg-surface-container-high border border-outline-variant/30 text-sm font-semibold text-on-surface hover:text-primary transition-colors">
                        <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                        Back to Review Hub
                    </a> -->
                </div>

                <!-- Sessions table -->
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden">

                    <?php if (empty($sessions)): ?>
                        <div class="flex flex-col items-center justify-center p-10 text-center text-on-surface-variant">
                            <span class="material-symbols-outlined text-[36px] text-outline mb-2">history_edu</span>
                            <span class="text-sm font-medium">No review sessions yet.</span>
                            <span class="text-xs mt-1 text-on-surface-variant">Finish a review session and it will show up here.</span>
                            <a href="review.php" class="mt-4 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-bold hover:bg-primary-container transition-colors">Go to Review Hub</a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <caption class="sr-only">All completed review sessions, newest first</caption>
                                <thead>
                                    <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-on-surface-variant bg-surface-container border-b border-outline-variant/30">
                                        <th scope="col" class="py-3 px-5 font-bold">Deck</th>
                                        <th scope="col" class="hidden sm:table-cell py-3 px-3 font-bold">Date</th>
                                        <th scope="col" class="py-3 px-3 font-bold text-right">Cards</th>
                                        <th scope="col" class="hidden md:table-cell py-3 px-3 font-bold text-right">Remembered</th>
                                        <th scope="col" class="py-3 px-3 font-bold text-right">Accuracy</th>
                                        <th scope="col" class="hidden md:table-cell py-3 px-3 font-bold text-right">Duration</th>
                                        <th scope="col" class="py-3 px-5 font-bold text-right"><span class="sr-only">View</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-outline-variant/20">
                                    <?php foreach ($sessions as $s): ?>
                                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                                            <td class="py-3 px-5">
                                                <a href="session-details.php?id=<?= (int) $s['id'] ?>"
                                                   title="<?= $h($s['deckTitle']) ?>"
                                                   class="block truncate max-w-[10rem] sm:max-w-xs font-semibold text-on-surface hover:text-primary transition-colors">
                                                    <?= $h($s['deckTitle']) ?>
                                                </a>
                                                <span class="sm:hidden text-[11px] text-on-surface-variant">
                                                    <?= $h(date('M j, Y', strtotime($s['startedAt']))) ?>
                                                </span>
                                            </td>
                                            <td class="hidden sm:table-cell py-3 px-3 text-on-surface-variant whitespace-nowrap">
                                                <?= $h(date('M j, Y, g:i A', strtotime($s['startedAt']))) ?>
                                            </td>
                                            <td class="py-3 px-3 text-right text-on-surface-variant tabular-nums"><?= (int) $s['totalReviewed'] ?></td>
                                            <td class="hidden md:table-cell py-3 px-3 text-right text-on-surface-variant tabular-nums"><?= (int) $s['rememberedCount'] ?></td>
                                            <td class="py-3 px-3 text-right">
                                                <span class="inline-block text-xs font-bold border px-2 py-0.5 rounded-md tabular-nums <?= ReviewHubService::accuracyBadgeClass($s['accuracy']) ?>">
                                                    <?= (int) $s['accuracy'] ?>%
                                                </span>
                                            </td>
                                            <td class="hidden md:table-cell py-3 px-3 text-right text-on-surface-variant tabular-nums whitespace-nowrap">
                                                <?= $h(ReviewHubService::formatDuration($s['durationSeconds'])) ?>
                                            </td>
                                            <td class="py-3 px-5 text-right">
                                                <a href="session-details.php?id=<?= (int) $s['id'] ?>"
                                                   aria-label="View details for <?= $h($s['deckTitle']) ?>"
                                                   class="inline-flex items-center text-on-surface-variant hover:text-primary transition-colors">
                                                    <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <!-- Pagination -->
                            <nav class="flex items-center justify-between gap-3 px-5 py-3 border-t border-outline-variant/30 bg-surface-container-lowest" aria-label="Pagination">
                                <p class="text-xs text-on-surface-variant">
                                    Page <?= (int) $page ?> of <?= (int) $totalPages ?>
                                </p>
                                <div class="flex items-center gap-1">
                                    <?php if ($page > 1): ?>
                                        <a href="sessions.php?page=<?= $page - 1 ?>" aria-label="Previous page"
                                           class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($p = $firstShown; $p <= $lastShown; $p++): ?>
                                        <?php if ($p === $page): ?>
                                            <span aria-current="page" class="min-w-8 h-8 px-2 rounded-lg flex items-center justify-center bg-primary text-on-primary text-xs font-bold tabular-nums"><?= $p ?></span>
                                        <?php else: ?>
                                            <a href="sessions.php?page=<?= $p ?>"
                                               class="min-w-8 h-8 px-2 rounded-lg flex items-center justify-center text-xs font-semibold text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors tabular-nums"><?= $p ?></a>
                                        <?php endif; ?>
                                    <?php endfor; ?>

                                    <?php if ($page < $totalPages): ?>
                                        <a href="sessions.php?page=<?= $page + 1 ?>" aria-label="Next page"
                                           class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>
</body>
</html>