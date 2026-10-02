<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user_id = (int) $_SESSION['user_id'];

// CSRF token used by the rename/delete requests
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

// Escape any value before printing it into HTML.
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Colour theme for the accuracy badge and progress bar.
function accuracy_theme(?int $pct): array
{
    if ($pct === null) {
        return [
            'badge' => 'bg-surface-container text-on-surface-variant border-outline-variant/40',
            'bar'   => 'bg-outline-variant',
            'icon'  => 'hourglass_empty',
            'label' => 'No reviews yet',
        ];
    }
    if ($pct >= 90) {
        return ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200/80', 'bar' => 'bg-emerald-500', 'icon' => 'verified', 'label' => $pct . '% Accuracy'];
    }
    if ($pct >= 75) {
        return ['badge' => 'bg-amber-50 text-amber-700 border-amber-200/80', 'bar' => 'bg-amber-500', 'icon' => 'speed', 'label' => $pct . '% Accuracy'];
    }
    return ['badge' => 'bg-rose-50 text-rose-700 border-rose-200/80', 'bar' => 'bg-rose-500', 'icon' => 'trending_down', 'label' => $pct . '% Accuracy'];
}

// "12 due now", "Due in 3 days", "Due in 5 hrs", ...
function due_label(int $dueCount, int $cardCount, ?string $nextDue): string
{
    if ($cardCount === 0) {
        return 'No cards yet';
    }
    if ($dueCount > 0) {
        return $dueCount . ' due now';
    }
    if ($nextDue === null) {
        return 'Nothing due';
    }
    $diff = (new DateTime())->diff(new DateTime($nextDue));
    if ($diff->days >= 1) {
        return 'Due in ' . $diff->days . ($diff->days === 1 ? ' day' : ' days');
    }
    if ($diff->h >= 1) {
        return 'Due in ' . $diff->h . ($diff->h === 1 ? ' hr' : ' hrs');
    }
    return 'Due in under 1 hr';
}

// Formats a timestamp into relative time ("2 hrs ago", "Yesterday", etc.)
function last_reviewed_label(?string $lastReviewed): string
{
    if ($lastReviewed === null) {
        return 'Never reviewed';
    }
    $reviewedDate = new DateTime($lastReviewed);
    $now = new DateTime();
    $diff = $now->diff($reviewedDate);

    if ($diff->days === 0) {
        if ($diff->h === 0) {
            return $diff->i <= 1 ? 'Reviewed just now' : 'Reviewed ' . $diff->i . ' mins ago';
        }
        return 'Reviewed ' . $diff->h . ($diff->h === 1 ? ' hr ago' : ' hrs ago');
    }
    if ($diff->days === 1) {
        return 'Reviewed yesterday';
    }
    if ($diff->days < 30) {
        return 'Reviewed ' . $diff->days . ' days ago';
    }
    return 'Reviewed ' . $reviewedDate->format('M j, Y');
}
// ---------------------------------------------------------------------------
// Load this user's decks with card count, due count, next due time, review sessions and accuracy
// ---------------------------------------------------------------------------
$sql = "SELECT d.id,
               d.title,
               (SELECT COUNT(*) FROM flashcards f
                 WHERE f.deck_id = d.id AND f.deleted_at IS NULL) AS card_count,
               (SELECT COUNT(*) FROM flashcards f
                 WHERE f.deck_id = d.id AND f.deleted_at IS NULL
                   AND f.next_review_at <= NOW()) AS due_count,
               (SELECT MIN(f.next_review_at) FROM flashcards f
                 WHERE f.deck_id = d.id AND f.deleted_at IS NULL) AS next_due,
               (SELECT MAX(rs.ended_at) FROM review_sessions rs
                 WHERE rs.deck_id = d.id AND rs.user_id = d.user_id
                   AND rs.ended_at IS NOT NULL) AS last_reviewed,
               (SELECT SUM(rs.remembered_count) FROM review_sessions rs
                 WHERE rs.deck_id = d.id AND rs.user_id = d.user_id
                   AND rs.ended_at IS NOT NULL) AS remembered,
               (SELECT SUM(rs.total_reviewed) FROM review_sessions rs
                 WHERE rs.deck_id = d.id AND rs.user_id = d.user_id
                   AND rs.ended_at IS NOT NULL) AS reviewed
        FROM decks d
        WHERE d.user_id = ? AND d.deleted_at IS NULL
        ORDER BY d.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// ---------------------------------------------------------------------------
// Load Archived Decks
// ---------------------------------------------------------------------------
$sql_archived = "SELECT d.id,
               d.title,
               d.deleted_at AS archive_date,
               (SELECT COUNT(*) FROM flashcards f WHERE f.deck_id = d.id) AS card_count,
               (SELECT MAX(rs.ended_at) FROM review_sessions rs WHERE rs.deck_id = d.id AND rs.user_id = d.user_id) AS last_reviewed,
               (SELECT SUM(rs.remembered_count) FROM review_sessions rs WHERE rs.deck_id = d.id AND rs.user_id = d.user_id) AS remembered,
               (SELECT SUM(rs.total_reviewed) FROM review_sessions rs WHERE rs.deck_id = d.id AND rs.user_id = d.user_id) AS reviewed
        FROM decks d
        WHERE d.user_id = ? AND d.deleted_at IS NOT NULL
        ORDER BY d.deleted_at DESC";

$stmt_archived = mysqli_prepare($conn, $sql_archived);
mysqli_stmt_bind_param($stmt_archived, "i", $user_id);
mysqli_stmt_execute($stmt_archived);
$result_archived = mysqli_stmt_get_result($stmt_archived);

$archived_decks = [];
while ($row = mysqli_fetch_assoc($result_archived)) {
    $reviewed = (int) $row['reviewed'];
    $accuracy = $reviewed > 0 ? (int) round(((int) $row['remembered'] / $reviewed) * 100) : null;
    $row['accuracy'] = $accuracy;
    $archived_decks[] = $row;
}
mysqli_stmt_close($stmt_archived);

$decks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $card_count = (int) $row['card_count'];
    $reviewed   = (int) $row['reviewed'];
    $accuracy   = $reviewed > 0 ? (int) round(((int) $row['remembered'] / $reviewed) * 100) : null;
    $hue        = ((int) $row['id'] * 47) % 360;

   $decks[] = [
        'id'                  => (int) $row['id'],
        'title'               => $row['title'],
        'card_count'          => $card_count,
        'due_count'           => (int) $row['due_count'],
        'accuracy'            => $accuracy,
        'theme'               => accuracy_theme($accuracy),
        'due_text'            => due_label((int) $row['due_count'], $card_count, $row['next_due']),
        'last_reviewed_text'  => last_reviewed_label($row['last_reviewed']),
        'cover_style'         => "background: linear-gradient(135deg, hsl({$hue} 65% 45%), hsl(" . (($hue + 45) % 360) . " 65% 28%));",
    ];
}
mysqli_stmt_close($stmt);

$deck_total = count($decks);

$pageTitle = "Decks";
$currentPage = "decks";

$cards_due = $cards_due ?? 0;
$mastered_cards = $mastered_cards ?? 0;
$avg_accuracy = $avg_accuracy ?? 0;
$tough_cards = $tough_cards ?? 0;
$best_streak = $best_streak ?? 0;
$streak_accuracy = $streak_accuracy ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<?php include __DIR__ . '/includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="pl-72 pt-12 transition-all duration-300 ease-in-out">

        <?php include __DIR__ . '/includes/header-data.php'; ?>
        <?php include __DIR__ . '/includes/header.php'; ?>

        <div class="max-w-7xl mx-auto px-8 py-8">
            <div class="flex flex-col w-full gap-8">

                <!-- Deck Gallery -->
                <section class="flex flex-col gap-6 w-full" id="decks-gallery-section">

                    <!-- Section header and controls -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center gap-2.5">
                                <h2 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface">Decks</h2>
                                <span id="deck-count-badge" class="px-2.5 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold tracking-wide shadow-xs">
                                    <?= $deck_total ?> <?= $deck_total === 1 ? 'Deck' : 'Decks' ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- View toggles -->
                            <div aria-label="View toggle" class="inline-flex items-center p-1 rounded-xl bg-surface-container border border-outline-variant/30 text-on-surface-variant" role="group">
                                <button class="p-1.5 rounded-lg bg-surface-container-lowest text-primary" id="view-grid-btn" title="Grid View" type="button"><span class="material-symbols-outlined text-[20px]">grid_view</span></button>
                                <button class="p-1.5 rounded-lg text-on-surface-variant" id="view-list-btn" title="List View" type="button"><span class="material-symbols-outlined text-[20px]">view_list</span></button>
                            </div>

                            <!-- Archived Decks Button -->
                            <button type="button" id="open-archived-btn" class="inline-flex items-center gap-2 p-2 sm:px-3 sm:py-2 rounded-xl bg-surface-container border border-outline-variant/30 text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-all" title="View Archived Decks">
                                <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                                <span class="text-sm font-semibold hidden sm:inline">Archived Decks</span>
                            </button>

                            <button class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold shadow-sm" type="button" id="new-deck-btn">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                <span>New Deck</span>
                            </button>
                        </div>
                    </div>

                    <?php if ($deck_total === 0): ?>
                    <!-- Grid view empty state -->
                    <div id="decks-grid-view">
                        <div id="new-deck-empty-grid" class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-2xl p-10 flex flex-col items-center justify-center gap-3 text-center bg-surface-container-low/30 hover:bg-surface-container-low transition-all cursor-pointer group">
                            <div class="w-14 h-14 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[28px]">add_circle</span>
                            </div>
                            <div>
                                <h3 class="font-headline-sm text-[18px] font-bold text-on-surface group-hover:text-primary transition-colors">No decks yet</h3>
                                <p class="text-on-surface-variant text-[14px] mt-0.5">Create your first deck to start adding flashcards.</p>
                            </div>
                        </div>
                    </div>

                    <!-- List view empty state -->
                    <div id="decks-list-view" class="flex flex-col gap-4 hidden">
                        <div id="new-deck-empty-list" class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-2xl p-5 flex items-center justify-between bg-surface-container-low/30 hover:bg-surface-container-low transition-all cursor-pointer group">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform flex-shrink-0">
                                    <span class="material-symbols-outlined text-[24px]">add_circle</span>
                                </div>
                                <div>
                                    <h4 class="font-headline-sm text-[16px] font-bold text-on-surface group-hover:text-primary transition-colors">No decks yet</h4>
                                    <p class="text-on-surface-variant text-[13px]">Create your first deck to start adding flashcards.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>

                    <!-- Grid view -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 transition-opacity duration-200" id="decks-grid-view">
                        <?php foreach ($decks as $deck): ?>
                            <div data-deck-card="<?= $deck['id'] ?>" data-deck-id="<?= $deck['id'] ?>" class="deck-card deck-item cursor-grab active:cursor-grabbing bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
                                <!-- Cover -->
                                <div class="relative h-44 w-full overflow-hidden border-b border-outline-variant/20 flex items-center justify-center" style="<?= h($deck['cover_style']) ?>">
                                    <?php if ($deck['due_count'] > 0): ?>
                                        <div class="absolute top-3 left-3 z-10 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/45 backdrop-blur-md text-white text-[12px] font-semibold shadow-sm">
                                            <span class="material-symbols-outlined text-[16px]">schedule</span>
                                            <span><?= $deck['due_count'] ?> due now</span>
                                        </div>
                                    <?php endif; ?>
                                    <span class="material-symbols-outlined text-white/80 text-[56px]">style</span>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                                    <button class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/30 backdrop-blur-md text-white hover:bg-black/50 transition-colors flex items-center justify-center" title="Deck Options" type="button" data-deck-menu data-deck-id="<?= $deck['id'] ?>" aria-haspopup="menu" aria-expanded="false">
                                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                    </button>
                                </div>
                                <!-- Metadata -->
                                <div class="p-5 flex-1 flex flex-col justify-between gap-4">
                                    <div>
                                        <a
                                            href="deck.php?deck_id=<?= $deck['id'] ?>"
                                            data-deck-title="<?= $deck['id'] ?>"
                                            class="font-headline-sm text-[18px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors"
                                        >
                                            <?= h(mb_strimwidth($deck['title'], 0, 45, '...')) ?>
                                        </a>
                                        <div class="mt-2.5 flex items-center justify-between font-label-md text-label-md text-on-surface-variant">
                                            <div class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-primary text-[18px]">style</span>
                                                <span class="font-semibold text-on-surface"><?= $deck['card_count'] ?> <?= $deck['card_count'] === 1 ? 'Card' : 'Cards' ?></span>
                                            </div>
                                            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-md border font-semibold text-[12px] <?= $deck['theme']['badge'] ?>">
                                                <span class="material-symbols-outlined text-[16px]"><?= $deck['theme']['icon'] ?></span>
                                                <span><?= h($deck['theme']['label']) ?></span>
                                            </div>
                                        </div>
                                        <div class="w-full bg-surface-container-high rounded-full h-1.5 mt-3 overflow-hidden">
                                            <div class="<?= $deck['theme']['bar'] ?> h-1.5 rounded-full" style="width: <?= (int) ($deck['accuracy'] ?? 0) ?>%"></div>
                                        </div>

                                        <!-- Grid View Action Button -->
                                        <?php if ($deck['due_count'] > 0): ?>
                                            <button
                                                type="button"
                                                data-action="review"
                                                data-deck-id="<?= $deck['id'] ?>"
                                                class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-bold shadow-sm hover:shadow-md transition-all"
                                            >
                                                <span class="material-symbols-outlined text-[19px]">play_arrow</span>
                                                <span>Review <?= $deck['due_count'] ?> Due</span>
                                            </button>
                                        <?php elseif ($deck['card_count'] === 0): ?>
                                            <button
                                                type="button"
                                                disabled
                                                class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-surface-container-high text-on-surface-variant/60 font-label-md text-label-md font-semibold cursor-not-allowed"
                                            >
                                                <span class="material-symbols-outlined text-[19px]">inbox</span>
                                                <span>Empty deck</span>
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                disabled
                                                class="mt-4 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-surface-container-high text-on-surface-variant/60 font-label-md text-label-md font-semibold cursor-not-allowed"
                                            >
                                                <span class="material-symbols-outlined text-[19px]">check_circle</span>
                                                <span>All caught up!</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pt-2 border-t border-outline-variant/20 flex items-center gap-2.5">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <!-- Create new deck tile -->
                        <div class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-2xl min-h-[16rem] flex flex-col items-center justify-center gap-3 bg-surface-container-low/30 hover:bg-surface-container-low transition-all cursor-pointer group" id="new-deck-tile">
                            <div class="w-14 h-14 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[24px]">add_circle</span>
                            </div>
                            <h4 class="font-headline-sm text-[16px] font-bold text-on-surface group-hover:text-primary transition-colors">Create New Deck</h4>
                        </div>
                    </div>

                    <!-- List view -->
                    <div class="flex flex-col gap-4 hidden" id="decks-list-view">
                        <?php foreach ($decks as $deck): ?>
                            <div data-deck-card="<?= $deck['id'] ?>" data-deck-id="<?= $deck['id'] ?>" class="deck-card deck-item cursor-grab active:cursor-grabbing bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 group">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center" style="<?= h($deck['cover_style']) ?>">
                                        <span class="material-symbols-outlined text-white/90 text-[26px]">style</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <a
                                            href="deck.php?deck_id=<?= $deck['id'] ?>"
                                            data-deck-title="<?= $deck['id'] ?>"
                                            class="font-headline-sm text-[18px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors"
                                        >
                                            <?= h(mb_strimwidth($deck['title'], 0, 45, '...')) ?>
                                        </a>
                                        <div class="flex items-center gap-4 mt-1 font-body-sm text-body-sm text-on-surface-variant">
                                            <span class="inline-flex items-center gap-1 font-semibold text-on-surface">
                                                <span class="material-symbols-outlined text-[16px] text-primary">style</span>
                                                <?= $deck['card_count'] ?> <?= $deck['card_count'] === 1 ? 'Card' : 'Cards' ?>
                                            </span>
                                            <span class="text-outline-variant">•</span>
                                            <span class="inline-flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[16px] text-primary">schedule</span>
                                                <?= h($deck['due_text']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between lg:justify-end gap-6 pt-3 lg:pt-0 border-t lg:border-t-0 border-outline-variant/20">
                                    <div class="flex flex-col gap-1.5 w-36 sm:w-44">
                                        <div class="flex items-center justify-between text-label-sm font-semibold">
                                            <span class="text-on-surface-variant">Accuracy</span>
                                            <span class="px-1.5 py-0.5 rounded border <?= $deck['theme']['badge'] ?>">
                                                <?= $deck['accuracy'] === null ? '—' : $deck['accuracy'] . '%' ?>
                                            </span>
                                        </div>
                                        <div class="w-full bg-surface-container-high rounded-full h-1.5 overflow-hidden">
                                            <div class="<?= $deck['theme']['bar'] ?> h-1.5 rounded-full" style="width: <?= (int) ($deck['accuracy'] ?? 0) ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <!-- List View Action Button -->
                                        <?php if ($deck['due_count'] > 0): ?>
                                            <button
                                                type="button"
                                                data-action="review"
                                                data-deck-id="<?= $deck['id'] ?>"
                                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-bold shadow-sm hover:shadow-md transition-all"
                                            >
                                                <span class="material-symbols-outlined text-[19px]">play_arrow</span>
                                                <span>Review</span>
                                            </button>
                                        <?php elseif ($deck['card_count'] === 0): ?>
                                            <button
                                                type="button"
                                                disabled
                                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-surface-container-high text-on-surface-variant/60 font-label-md text-label-md font-semibold cursor-not-allowed"
                                            >
                                                <span class="material-symbols-outlined text-[19px]">inbox</span>
                                                <span>Empty deck</span>
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                disabled
                                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-surface-container-high text-on-surface-variant/60 font-label-md text-label-md font-semibold cursor-not-allowed"
                                            >
                                                <span class="material-symbols-outlined text-[19px]">check_circle</span>
                                                <span>Reviewed</span>
                                            </button>
                                        <?php endif; ?>
                                        <button class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="Deck Options" type="button" data-deck-menu data-deck-id="<?= $deck['id'] ?>" aria-haspopup="menu" aria-expanded="false">
                                            <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Create new deck row -->
                        <div class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-2xl p-4 sm:p-5 flex items-center justify-between bg-surface-container-low/30 hover:bg-surface-container-low transition-all cursor-pointer group" id="new-deck-row">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform flex-shrink-0">
                                    <span class="material-symbols-outlined text-[24px]">add_circle</span>
                                </div>
                                <h4 class="font-headline-sm text-[16px] font-bold text-on-surface group-hover:text-primary transition-colors">Create New Deck</h4>
                            </div>
                        </div>
                    </div>

                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <!-- Shared kebab menu (positioned by JS so card overflow can't clip it) -->
    <div id="deck-menu" class="hidden fixed z-50 w-44 rounded-xl bg-surface-container-lowest border border-outline-variant/30 shadow-lg py-1" role="menu">
        <button type="button" role="menuitem" data-menu-action="rename" class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-on-surface hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[18px]">edit</span>
            <span>Rename</span>
        </button>
        <button type="button" role="menuitem" data-menu-action="archive" class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-amber-700 hover:bg-amber-50 transition-colors">
        <span class="material-symbols-outlined text-[18px]">archive</span>
        <span>Archive</span>
        </button>
        <hr>
        <button type="button" role="menuitem" data-menu-action="add-card" class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-on-surface hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Add Card</span>
        </button>
    </div>

    <!-- Rename dialog -->
    <dialog id="rename-dialog" class="rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
        <form id="rename-form" class="p-6 flex flex-col gap-4" novalidate>
            <h3 class="font-headline-sm text-[18px] font-bold">Rename deck</h3>
            <div class="flex flex-col gap-1.5">
                <label for="rename-input" class="text-on-surface-variant text-[13px] font-semibold">Deck name</label>
                <input id="rename-input" type="text" maxlength="150" autocomplete="off" class="w-full px-3 py-2 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary">
                <p id="rename-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold transition-colors">Cancel</button>
                <button type="submit" id="rename-save" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold transition-colors disabled:opacity-60">Save</button>
            </div>
        </form>
    </dialog>

    <!-- Archive dialog -->
    <dialog id="archive-dialog" class="rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
        <div class="p-6 flex flex-col gap-4">
            <h3 class="font-headline-sm text-[18px] font-bold">Archive deck?</h3>
            <p class="text-on-surface-variant text-[14px]">
                <span id="archive-name" class="font-semibold text-on-surface"></span>
                will be hidden from your active decks and review queue. You can restore it anytime from the Archive List.
            </p>
            <p id="archive-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
            <div class="flex justify-end gap-2.5">
                <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-[14px] transition-colors">Cancel</button>
                <button type="button" id="archive-confirm" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-[14px] transition-colors disabled:opacity-60">Archive</button>
            </div>
        </div>
    </dialog>

    <!-- Create deck dialog -->
    <dialog id="create-dialog" class="rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
        <form id="create-form" class="p-6 flex flex-col gap-4" novalidate>
            <h3 class="font-headline-sm text-[18px] font-bold">New deck</h3>
            <div class="flex flex-col gap-1.5">
                <label for="create-input" class="text-on-surface-variant text-[13px] font-semibold">Deck name</label>
                <input id="create-input" type="text" maxlength="150" autocomplete="off" placeholder="e.g. Biology Chapter 3" class="w-full px-3 py-2 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary">
                <p id="create-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
            </div>
            <div class="flex justify-end gap-2.5">
                <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold transition-colors">Cancel</button>
                <button type="submit" id="create-save" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold transition-colors disabled:opacity-60">Create deck</button>
            </div>
        </form>
    </dialog>

    <!-- Add card dialog -->
    <dialog id="card-dialog" class="rounded-2xl p-0 w-full max-w-lg bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
        <form id="card-form" class="p-6 flex flex-col gap-4" novalidate>
            <div>
                <h3 class="font-headline-sm text-[18px] font-bold">Add card</h3>
                <p class="text-on-surface-variant text-[13px] mt-1">Choose a deck, then add your flashcard.</p>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="card-deck-select" class="text-on-surface-variant text-[13px] font-semibold">Deck</label>
                <select id="card-deck-select" class="w-full px-3 py-2 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary">
                    <option value="" selected disabled>Select a deck</option>
                    <?php foreach ($decks as $deck): ?>
                        <option value="<?= $deck['id'] ?>"><?= h($deck['title']) ?></option>
                    <?php endforeach; ?>
                    <option value="__create__">＋ Create new deck</option>
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="card-front" class="text-on-surface-variant text-[13px] font-semibold">Front</label>
                <textarea id="card-front" rows="3" maxlength="5000" placeholder="Question or prompt" class="w-full px-3 py-2 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary resize-y"></textarea>
            </div>
            <div class="flex flex-col gap-1.5">
                <label for="card-back" class="text-on-surface-variant text-[13px] font-semibold">Back</label>
                <textarea id="card-back" rows="3" maxlength="5000" placeholder="Answer" class="w-full px-3 py-2 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary resize-y"></textarea>
            </div>
            <p id="card-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
            <p id="card-success" class="hidden text-emerald-700 text-[13px]" role="status">Card added. Add another, or close when you're done.</p>
            <div class="flex items-center justify-between gap-2.5 pt-2 border-t border-outline-variant/20">
                <span class="text-on-surface-variant/70 text-[12px] font-medium inline-flex items-center gap-1.5">
                    <kbd class="px-1.5 py-0.5 text-[11px] font-mono font-semibold text-on-surface-variant bg-surface-container-high border border-outline-variant/60 rounded-md shadow-2xs">Ctrl</kbd>
                    <span>+</span>
                    <kbd class="px-1.5 py-0.5 text-[11px] font-mono font-semibold text-on-surface-variant bg-surface-container-high border border-outline-variant/60 rounded-md shadow-2xs">Enter</kbd>
                    <span class="ml-0.5">to save</span>
                </span>

                <div class="flex items-center gap-2.5">
                    <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-[14px] transition-colors">
                        Close
                    </button>
                    <button type="submit" id="card-save" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-[14px] transition-colors disabled:opacity-60">
                        Add card
                    </button>
                </div>
            </div>
        </form>
    </dialog>

    <!-- Archived Decks Modal -->
    <dialog id="archived-decks-dialog" class="rounded-2xl p-0 w-full max-w-2xl bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
        <div class="p-6 flex flex-col gap-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center">
                <h3 class="font-headline-sm text-[18px] font-bold">Archived Decks</h3>
                <button type="button" data-close-dialog class="text-on-surface-variant hover:text-on-surface transition-colors">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <div class="flex flex-col gap-3">
                <?php if (empty($archived_decks)): ?>
                    <p class="text-on-surface-variant text-center py-6 text-sm">No archived decks found.</p>
                <?php else: ?>
                    <?php foreach ($archived_decks as $ad): ?>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-outline-variant/40 bg-surface gap-4 hover:border-primary/50 transition-colors">
                            <div>
                                <h4 class="font-bold text-on-surface text-[15px]"><?= h($ad['title']) ?></h4>
                                <div class="text-[13px] text-on-surface-variant mt-1.5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1">
                                    <span><strong>Cards:</strong> <?= $ad['card_count'] ?></span>
                                    <span><strong>Accuracy:</strong> <?= $ad['accuracy'] !== null ? $ad['accuracy'] . '%' : 'N/A' ?></span>
                                    <span><strong>Archived:</strong> <?= date('M j, Y', strtotime($ad['archive_date'])) ?></span>
                                    <span><strong>Reviewed:</strong> <?= $ad['last_reviewed'] ? last_reviewed_label($ad['last_reviewed']) : 'Never' ?></span>
                                </div>
                            </div>
                            <button type="button" data-restore-deck="<?= $ad['id'] ?>" class="px-4 py-2 rounded-xl bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary text-sm font-semibold transition-colors whitespace-nowrap shadow-sm">
                                Restore Deck
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </dialog>

    <script>
        // ---------------------------------------------------------------
        // View toggle (grid vs list), remembered in localStorage
        // ---------------------------------------------------------------
        const viewGridBtn = document.getElementById('view-grid-btn');
        const viewListBtn = document.getElementById('view-list-btn');
        const gridView = document.getElementById('decks-grid-view');
        const listView = document.getElementById('decks-list-view');

        const VIEW_KEY = 'mempulse_decks_view';
        const activeBtn = "flex items-center justify-center p-1.5 rounded-lg bg-surface-container-lowest text-primary font-semibold shadow-xs transition-all";
        const idleBtn = "flex items-center justify-center p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors";

        function setView(mode) {
            const isGrid = mode !== 'list';
            if (gridView) gridView.classList.toggle('hidden', !isGrid);
            if (listView) listView.classList.toggle('hidden', isGrid);
            if (viewGridBtn) {
                viewGridBtn.className = isGrid ? activeBtn : idleBtn;
                viewGridBtn.setAttribute('aria-pressed', String(isGrid));
            }
            if (viewListBtn) {
                viewListBtn.className = isGrid ? idleBtn : activeBtn;
                viewListBtn.setAttribute('aria-pressed', String(!isGrid));
            }
        }

        function saveView(mode) {
            try { localStorage.setItem(VIEW_KEY, mode); } catch (_) { /* storage blocked */ }
        }

        if (viewGridBtn && viewListBtn && gridView && listView) {
            let saved = null;
            try { saved = localStorage.getItem(VIEW_KEY); } catch (_) { /* storage blocked */ }
            setView(saved === 'list' ? 'list' : 'grid');

            viewGridBtn.addEventListener('click', () => { setView('grid'); saveView('grid'); });
            viewListBtn.addEventListener('click', () => { setView('list'); saveView('list'); });
        }

        // ---------------------------------------------------------------
        // Deck Kebab Menu Handler (Rename & Archive / Delete)
        // ---------------------------------------------------------------
        const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;

        const deckMenu = document.getElementById('deck-menu');
        const renameDialog = document.getElementById('rename-dialog');
        const renameForm = document.getElementById('rename-form');
        const renameInput = document.getElementById('rename-input');
        const renameError = document.getElementById('rename-error');
        const renameSave = document.getElementById('rename-save');

        // Archive / Delete Modal Elements
        const archiveDialog = document.getElementById('archive-dialog') || document.getElementById('delete-dialog');
        const archiveName = document.getElementById('archive-name') || document.getElementById('delete-name');
        const archiveError = document.getElementById('archive-error') || document.getElementById('delete-error');
        const archiveConfirm = document.getElementById('archive-confirm') || document.getElementById('delete-confirm');
        const countBadge = document.getElementById('deck-count-badge');

        let activeDeckId = null;
        let menuButton = null;

        // ---------------------------------------------------------------
        // Archived Decks Modal & Restore Logic
        // ---------------------------------------------------------------
        const archivedDecksDialog = document.getElementById('archived-decks-dialog');
        const openArchivedBtn = document.getElementById('open-archived-btn');

        if (openArchivedBtn && archivedDecksDialog) {
            openArchivedBtn.addEventListener('click', () => {
                archivedDecksDialog.showModal();
            });
        }

        document.querySelectorAll('[data-restore-deck]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const deckId = btn.getAttribute('data-restore-deck');
                const originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = "Restoring...";
                
                try {
                    await sendDeckAction({ action: 'restore', deck_id: deckId });
                    window.location.reload();
                } catch (err) {
                    alert("Failed to restore deck: " + err.message);
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            });
        });

        function getDeckTitle(id) {
            const el = document.querySelector('[data-deck-title="' + id + '"]');
            return el ? el.textContent.trim() : '';
        }

        function closeMenu() {
            if (!deckMenu) return;
            deckMenu.classList.add('hidden');
            if (menuButton) menuButton.setAttribute('aria-expanded', 'false');
            menuButton = null;
        }

        function openMenu(btn) {
            closeMenu();
            if (!deckMenu) return;
            menuButton = btn;
            activeDeckId = btn.dataset.deckId;
            btn.setAttribute('aria-expanded', 'true');
            deckMenu.classList.remove('hidden');

            const rect = btn.getBoundingClientRect();
            const w = deckMenu.offsetWidth;
            const h = deckMenu.offsetHeight;
            const left = Math.min(Math.max(8, rect.right - w), window.innerWidth - w - 8);
            let top = rect.bottom + 6;
            if (top + h > window.innerHeight - 8) top = rect.top - h - 6;
            deckMenu.style.left = left + 'px';
            deckMenu.style.top = Math.max(8, top) + 'px';
        }

        // Global listeners for Kebab Menu toggle & dismiss
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-deck-menu]');
            if (btn) {
                if (menuButton === btn) closeMenu(); else openMenu(btn);
            } else if (deckMenu && !deckMenu.contains(e.target)) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
        window.addEventListener('resize', closeMenu);
        window.addEventListener('scroll', closeMenu, true);

        document.querySelectorAll('[data-close-dialog]').forEach((b) => {
            b.addEventListener('click', () => {
                const dlg = b.closest('dialog');
                if (dlg) dlg.close();
            });
        });

        // Unified API Fetch Function
        async function sendDeckAction(payload) {
            const res = await fetch('api/deck-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...payload, csrf_token: CSRF_TOKEN })
            });
            let data = {};
            try { data = await res.json(); } catch (_) { /* non-JSON response */ }
            if (!res.ok || !data.ok) {
                const err = new Error(data.error || 'Request failed (HTTP ' + res.status + ').');
                err.suggestion = data.suggestion || null;
                throw err;
            }
            return data;
        }

        // Handle Kebab dropdown item clicks
        if (deckMenu) {
            deckMenu.addEventListener('click', (e) => {
                const item = e.target.closest('[data-menu-action]');
                if (!item || !activeDeckId) return;
                const action = item.dataset.menuAction;
                closeMenu();

                if (action === 'rename' && renameDialog) {
                    if (renameInput) renameInput.value = getDeckTitle(activeDeckId);
                    if (renameError) renameError.classList.add('hidden');
                    renameDialog.showModal();
                    if (renameInput) renameInput.select();
                } else if ((action === 'delete' || action === 'archive') && archiveDialog) {
                    if (archiveName) archiveName.textContent = '"' + getDeckTitle(activeDeckId) + '"';
                    if (archiveError) archiveError.classList.add('hidden');
                    archiveDialog.showModal();
                } else if (action === 'add-card') {
                    openCardDialog(activeDeckId);
                }
            });
        }

        // Rename Form Submit Handler
        if (renameForm) {
            renameForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const title = renameInput ? renameInput.value.trim() : '';
                if (title === '') {
                    if (renameError) {
                        renameError.textContent = 'Deck name cannot be empty.';
                        renameError.classList.remove('hidden');
                    }
                    return;
                }
                if (renameSave) renameSave.disabled = true;
                try {
                    const data = await sendDeckAction({ action: 'rename', deck_id: activeDeckId, title });
                    document.querySelectorAll('[data-deck-title="' + activeDeckId + '"]').forEach((el) => {
                        el.textContent = data.title;
                    });
                    if (renameDialog) renameDialog.close();
                } catch (err) {
                    showFieldError(renameError, renameInput, err);
                } finally {
                    if (renameSave) renameSave.disabled = false;
                }
            });
        }

        // Archive / Delete Confirm Handler
        if (archiveConfirm) {
            archiveConfirm.addEventListener('click', async () => {
                archiveConfirm.disabled = true;
                try {
                    const action = archiveConfirm.id === 'delete-confirm' ? 'delete' : 'archive';
                    await sendDeckAction({ action, deck_id: activeDeckId });
                    document.querySelectorAll('[data-deck-card="' + activeDeckId + '"]').forEach((el) => el.remove());

                    if (typeof cardDeckSelect !== 'undefined' && cardDeckSelect) {
                        const deletedDeckOption = cardDeckSelect.querySelector('option[value="' + activeDeckId + '"]');
                        if (deletedDeckOption) deletedDeckOption.remove();
                        if (cardDeckId === Number(activeDeckId)) {
                            cardDeckId = null;
                            cardDeckSelect.value = '';
                        }
                    }

                    if (archiveDialog) archiveDialog.close();

                    const remaining = document.querySelectorAll('#decks-grid-view [data-deck-card]').length;
                    if (remaining === 0) {
                        window.location.reload();
                    } else if (countBadge) {
                        countBadge.textContent = remaining + (remaining === 1 ? ' Deck' : ' Decks');
                    }
                } catch (err) {
                    if (archiveError) {
                        archiveError.textContent = err.message;
                        archiveError.classList.remove('hidden');
                    }
                } finally {
                    archiveConfirm.disabled = false;
                }
            });
        }

        // ---------------------------------------------------------------
        // Create Deck Dialog Handler
        // ---------------------------------------------------------------
        const createDialog = document.getElementById('create-dialog');
        const createForm = document.getElementById('create-form');
        const createInput = document.getElementById('create-input');
        const createError = document.getElementById('create-error');
        const createSave = document.getElementById('create-save');

        let creatingDeckForCard = false;
        let suppressCardResume = false;

        function openCreateDialog(fromCard = false) {
            creatingDeckForCard = fromCard;

            if (fromCard && typeof cardDialog !== 'undefined' && cardDialog && cardDialog.open) {
                suppressCardResume = true;
                cardDialog.close();
                suppressCardResume = false;
            }

            if (createInput) createInput.value = '';
            if (createError) createError.classList.add('hidden');
            if (createSave) createSave.disabled = false;
            if (createDialog) {
                createDialog.showModal();
                if (createInput) createInput.focus();
            }
        }

        ['new-deck-btn', 'new-deck-tile', 'new-deck-row', 'new-deck-empty-grid', 'new-deck-empty-list'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', () => openCreateDialog(false));
        });

        if (createForm) {
            createForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const title = createInput ? createInput.value.trim() : '';
                if (title === '') {
                    if (createError) {
                        createError.textContent = 'Deck name cannot be empty.';
                        createError.classList.remove('hidden');
                    }
                    return;
                }
                if (createSave) createSave.disabled = true;
                try {
                    const data = await sendDeckAction({ action: 'create', title });

                    if (creatingDeckForCard) {
                        window.location.href = 'decks.php?add_card=1&deck_id=' + encodeURIComponent(data.id);
                    } else {
                        window.location.reload();
                    }
                } catch (err) {
                    showFieldError(createError, createInput, err);
                    if (createSave) createSave.disabled = false;
                }
            });
        }

        if (createDialog) {
            createDialog.addEventListener('close', () => {
                if (creatingDeckForCard && !suppressCardResume) {
                    creatingDeckForCard = false;
                    if (typeof cardDeckSelect !== 'undefined' && cardDeckSelect) {
                        cardDeckSelect.value = cardDeckId ? String(cardDeckId) : '';
                    }
                    if (typeof cardDialog !== 'undefined' && cardDialog) {
                        cardDialog.showModal();
                        if (cardFront) cardFront.focus();
                    }
                }
            });
        }

        // ---------------------------------------------------------------
        // Review button handler
        // ---------------------------------------------------------------
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="review"]');
            if (!btn) return;

            const deckId = btn.dataset.deckId;
            if (deckId) {
                window.location.href = 'review.php?deck_id=' + encodeURIComponent(deckId);
            }
        });

        // ---------------------------------------------------------------
        // Add Card Modal Handler
        // ---------------------------------------------------------------
        const cardDialog = document.getElementById('card-dialog');
        const cardForm = document.getElementById('card-form');
        const cardDeckSelect = document.getElementById('card-deck-select');
        const cardFront = document.getElementById('card-front');
        const cardBack = document.getElementById('card-back');
        const cardError = document.getElementById('card-error');
        const cardSuccess = document.getElementById('card-success');
        const cardSave = document.getElementById('card-save');

        let cardDeckId = null;
        let cardsAdded = false;

        function openCardDialog(deckId = null) {
            if (!cardDialog) return;
            const selectedId = Number(deckId);
            cardDeckId = Number.isInteger(selectedId) && selectedId > 0 ? selectedId : null;
            cardsAdded = false;

            if (cardDeckSelect) cardDeckSelect.value = cardDeckId ? String(cardDeckId) : '';
            if (cardFront) cardFront.value = '';
            if (cardBack) cardBack.value = '';
            if (cardError) cardError.classList.add('hidden');
            if (cardSuccess) cardSuccess.classList.add('hidden');
            if (cardSave) cardSave.disabled = false;
            
            cardDialog.showModal();

            if (cardDeckId && cardFront) {
                cardFront.focus();
            } else if (cardDeckSelect) {
                cardDeckSelect.focus();
            }
        }

        if (cardDeckSelect) {
            cardDeckSelect.addEventListener('change', () => {
                if (cardDeckSelect.value === '__create__') {
                    cardDeckSelect.value = cardDeckId ? String(cardDeckId) : '';
                    openCreateDialog(true);
                    return;
                }

                const selectedId = Number(cardDeckSelect.value);
                cardDeckId = Number.isInteger(selectedId) && selectedId > 0 ? selectedId : null;
                if (cardError) cardError.classList.add('hidden');
            });
        }

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="add-card"]');
            if (btn) openCardDialog(btn.dataset.deckId);
        });

        [cardFront, cardBack].forEach((field) => {
            if (field) {
                field.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                        e.preventDefault();
                        if (cardForm) cardForm.requestSubmit();
                    }
                });
            }
        });

        if (cardForm) {
            cardForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const front = cardFront ? cardFront.value.trim() : '';
                const back = cardBack ? cardBack.value.trim() : '';
                const selectedDeckId = cardDeckSelect ? Number(cardDeckSelect.value) : null;

                if (cardSuccess) cardSuccess.classList.add('hidden');

                if (!Number.isInteger(selectedDeckId) || selectedDeckId <= 0) {
                    cardDeckId = null;
                    if (cardError) {
                        cardError.textContent = 'Please choose a deck before adding the card.';
                        cardError.classList.remove('hidden');
                    }
                    if (cardDeckSelect) cardDeckSelect.focus();
                    return;
                }

                cardDeckId = selectedDeckId;

                if (front === '' || back === '') {
                    if (cardError) {
                        cardError.textContent = 'Both the front and back are required.';
                        cardError.classList.remove('hidden');
                    }
                    return;
                }
                if (cardError) cardError.classList.add('hidden');

                if (cardSave) cardSave.disabled = true;
                try {
                    await sendDeckAction({ action: 'add_card', deck_id: cardDeckId, front, back });
                    cardsAdded = true;
                    if (cardFront) cardFront.value = '';
                    if (cardBack) cardBack.value = '';
                    if (cardSuccess) cardSuccess.classList.remove('hidden');
                    if (cardFront) cardFront.focus();
                } catch (err) {
                    if (cardError) {
                        cardError.textContent = err.message;
                        cardError.classList.remove('hidden');
                    }
                } finally {
                    if (cardSave) cardSave.disabled = false;
                }
            });
        }

        function showFieldError(errorEl, input, err) {
            if (!errorEl) return;
            errorEl.textContent = err.message;
            if (err.suggestion && input) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ml-2 underline font-semibold';
                btn.textContent = 'Use "' + err.suggestion + '"';
                btn.addEventListener('click', () => {
                    input.value = err.suggestion;
                    errorEl.classList.add('hidden');
                    input.focus();
                });
                errorEl.appendChild(btn);
            }
            errorEl.classList.remove('hidden');
        }

        if (cardDialog) {
            cardDialog.addEventListener('close', () => {
                if (cardsAdded) {
                    const cleanUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, cleanUrl);
                    window.location.reload();
                }
            });
        }

        // Auto-open modals via URL params (e.g. ?create=1 or ?add_card=1)
        const createRequested = <?= isset($_GET['create']) && $_GET['create'] === '1' ? 'true' : 'false' ?>;
        const addCardRequested = <?= isset($_GET['add_card']) && $_GET['add_card'] === '1' ? 'true' : 'false' ?>;
        const requestedDeckId = <?= isset($_GET['deck_id']) && ctype_digit((string) $_GET['deck_id']) ? (int) $_GET['deck_id'] : 'null' ?>;

        if (createRequested) {
            openCreateDialog();
        } else if (addCardRequested) {
            openCardDialog(requestedDeckId);
        }

        // ---------------------------------------------------------------
        // Custom Deck Reordering via LocalStorage & SortableJS
        // ---------------------------------------------------------------
        const ORDER_KEY = 'mempulse_deck_order';

        // Load saved order and sort DOM elements
        function applySavedDeckOrder(container) {
            if (!container) return;
            
            let savedOrder = null;
            try {
                savedOrder = JSON.parse(localStorage.getItem(ORDER_KEY));
            } catch (_) {}

            if (!Array.isArray(savedOrder) || savedOrder.length === 0) return;

            // Target top-level .deck-card containers explicitly
            const items = Array.from(container.querySelectorAll('.deck-card[data-deck-id]'));
            const itemMap = new Map();
            items.forEach(item => itemMap.set(String(item.dataset.deckId), item));

            // Re-append items in saved order
            savedOrder.forEach(deckId => {
                const el = itemMap.get(String(deckId));
                if (el) {
                    container.appendChild(el);
                    itemMap.delete(String(deckId));
                }
            });

            // Append any newly created/untracked decks at the end (before creation tile)
            const createBtn = container.querySelector('#new-deck-tile, #new-deck-row');
            itemMap.forEach(el => {
                if (createBtn) {
                    container.insertBefore(el, createBtn);
                } else {
                    container.appendChild(el);
                }
            });

            // Keep creation tile at the end
            if (createBtn) {
                container.appendChild(createBtn);
            }
        }

        // Extract current deck order from DOM and save to localStorage
        function saveCurrentDeckOrder(container) {
            if (!container) return;
            const items = Array.from(container.querySelectorAll('.deck-card[data-deck-id]'));
            const order = items.map(el => Number(el.dataset.deckId));
            try {
                localStorage.setItem(ORDER_KEY, JSON.stringify(order));
            } catch (_) { /* storage disabled */ }
        }

        // Initialize Drag & Drop
        document.addEventListener('DOMContentLoaded', () => {
            const gridContainer = document.getElementById('decks-grid-view');
            const listContainer = document.getElementById('decks-list-view');

            // 1. Apply saved position order on load
            applySavedDeckOrder(gridContainer);
            applySavedDeckOrder(listContainer);

            // 2. Initialize SortableJS on Grid View
            if (gridContainer && typeof Sortable !== 'undefined') {
                new Sortable(gridContainer, {
                    animation: 150,
                    draggable: '.deck-card',
                    filter: '[data-deck-menu], dialog, button, a',
                    preventOnFilter: false,
                    onEnd: () => {
                        saveCurrentDeckOrder(gridContainer);
                        applySavedDeckOrder(listContainer);
                    }
                });
            }

            // 3. Initialize SortableJS on List View
            if (listContainer && typeof Sortable !== 'undefined') {
                new Sortable(listContainer, {
                    animation: 150,
                    draggable: '.deck-card',
                    filter: '[data-deck-menu], dialog, button, a',
                    preventOnFilter: false,
                    onEnd: () => {
                        saveCurrentDeckOrder(listContainer);
                        applySavedDeckOrder(gridContainer);
                    }
                });
            }
        });
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>