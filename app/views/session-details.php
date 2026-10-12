<?php
/**
 * Session Details (single session)
 * Subpage of Session History:  Review Hub > Session History > Session Details
 * This is the OLD sessions.php, renamed.
 */
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/SessionService.php';
require_once __DIR__ . '/../api/ReviewHubService.php';

$userId    = (int) $_SESSION['user_id'];
$sessionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$session = SessionService::getSessionDetails($conn, $sessionId, $userId);
if (!$session) {
    header("Location: sessions.php"); // invalid id or belongs to someone else -> back to the history list
    exit;
}

$logs = SessionService::getSessionLogs($conn, $sessionId);

$pageTitle   = "Session Details";
$currentPage = "review"; // Keep navigation active on Review Hub
$h = [ReviewHubService::class, 'h'];
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
                    <a href="sessions.php" class="hover:text-primary transition-colors">Session History</a>
                    <span class="material-symbols-outlined text-[16px] opacity-50">chevron_right</span>
                    <span class="text-on-surface" aria-current="page">Session Details</span>
                </nav>

                <!-- Session Metadata Header -->
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-[28px] text-primary">history_edu</span>
                            <h1 class="font-headline-md text-2xl font-bold text-on-surface m-0">Session Details</h1>
                        </div>
                        <p class="text-sm text-on-surface-variant">
                            Deck: <a href="deck.php?deck_id=<?= (int) $session['deckId'] ?>" class="font-semibold text-on-surface hover:text-primary hover:underline transition-colors"><?= $h($session['deckTitle']) ?></a>
                            <span class="mx-2">•</span>
                            <?= $h(date('M j, Y, g:i A', strtotime($session['startedAt']))) ?>
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <div class="bg-surface-container-high px-4 py-2.5 rounded-xl border border-outline-variant/30 flex flex-col items-center min-w-[80px]">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Duration</span>
                            <span class="text-lg font-extrabold text-on-surface mt-0.5 tabular-nums"><?= $h(ReviewHubService::formatDuration($session['durationSeconds'])) ?></span>
                        </div>
                        <div class="bg-surface-container-high px-4 py-2.5 rounded-xl border border-outline-variant/30 flex flex-col items-center min-w-[80px]">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Cards</span>
                            <span class="text-lg font-extrabold text-on-surface mt-0.5 tabular-nums"><?= (int) $session['totalReviewed'] ?></span>
                        </div>
                        <div class="<?= ReviewHubService::accuracyBadgeClass($session['accuracy']) ?> px-4 py-2.5 rounded-xl border flex flex-col items-center min-w-[80px]">
                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-80">Accuracy</span>
                            <span class="text-lg font-extrabold mt-0.5 tabular-nums"><?= (int) $session['accuracy'] ?>%</span>
                        </div>
                    </div>
                </div>

                <!-- Cards Table -->
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden">
                    <div class="px-5 py-4 border-b border-outline-variant/30 bg-surface-container-lowest">
                        <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Cards Reviewed</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-on-surface-variant bg-surface-container border-b border-outline-variant/30">
                                    <th scope="col" class="py-3 px-5 font-bold w-1/3">Front</th>
                                    <th scope="col" class="py-3 px-5 font-bold w-1/3">Back</th>
                                    <th scope="col" class="py-3 px-5 font-bold">Result</th>
                                    <th scope="col" class="py-3 px-5 font-bold text-right">Next Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant/20">
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                                        <td class="py-3 px-5 align-top">
                                            <?php if ($log['isDeleted']): ?>
                                                <span class="inline-block mb-1 text-[10px] font-bold uppercase text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">Deleted</span><br>
                                            <?php endif; ?>
                                            <div class="line-clamp-2" title="<?= $h($log['front']) ?>"><?= nl2br($h($log['front'])) ?></div>
                                        </td>
                                        <td class="py-3 px-5 align-top text-on-surface-variant">
                                            <div class="line-clamp-2" title="<?= $h($log['back']) ?>"><?= nl2br($h($log['back'])) ?></div>
                                        </td>
                                        <td class="py-3 px-5 align-top">
                                            <?php if ($log['rating'] === 'remembered'): ?>
                                                <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-1 rounded-md">
                                                    <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                                    Remembered
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-1 rounded-md">
                                                    <span class="material-symbols-outlined text-[14px]">cancel</span>
                                                    Forgotten
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-5 align-top text-right text-on-surface-variant whitespace-nowrap">
                                            <?php
                                                if ($log['nextDueSeconds'] !== null) {
                                                    if ($log['nextDueSeconds'] <= 0) {
                                                        echo '<span class="text-rose-600 font-medium">Due Now</span>';
                                                    } else {
                                                        echo 'in ' . $h(ReviewHubService::formatSpan($log['nextDueSeconds']));
                                                    }
                                                } else {
                                                    echo '—';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>