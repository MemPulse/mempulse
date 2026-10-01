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

// ---------------------------------------------------------------------------
// Load this user's decks with card count, due count, next due time and accuracy
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

$decks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $card_count = (int) $row['card_count'];
    $reviewed   = (int) $row['reviewed'];
    $accuracy   = $reviewed > 0 ? (int) round(((int) $row['remembered'] / $reviewed) * 100) : null;
    $hue        = ((int) $row['id'] * 47) % 360;

    $decks[] = [
        'id'          => (int) $row['id'],
        'title'       => $row['title'],
        'card_count'  => $card_count,
        'due_count'   => (int) $row['due_count'],
        'accuracy'    => $accuracy,
        'theme'       => accuracy_theme($accuracy),
        'due_text'    => due_label((int) $row['due_count'], $card_count, $row['next_due']),
        // Decks have no cover image column yet, so each one gets a gradient based on its id.
        'cover_style' => "background: linear-gradient(135deg, hsl({$hue} 65% 45%), hsl(" . (($hue + 45) % 360) . " 65% 28%));",
    ];
}
mysqli_stmt_close($stmt);

$deck_total = count($decks);

$pageTitle = "Decks";
$currentPage = "decks";
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
                            <!-- View toggle -->
                            <div aria-label="View toggle" class="inline-flex items-center p-1 rounded-xl bg-surface-container border border-outline-variant/30 text-on-surface-variant" role="group">
                                <button aria-pressed="true" class="flex items-center justify-center p-1.5 rounded-lg bg-surface-container-lowest text-primary font-semibold shadow-xs transition-all" id="view-grid-btn" title="Grid View" type="button">
                                    <span class="material-symbols-outlined text-[20px]">grid_view</span>
                                </button>
                                <button aria-pressed="false" class="flex items-center justify-center p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors" id="view-list-btn" title="List View" type="button">
                                    <span class="material-symbols-outlined text-[20px]">view_list</span>
                                </button>
                            </div>
                            <button class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold shadow-sm hover:shadow transition-all" type="button" id="new-deck-btn">
                                <span class="material-symbols-outlined text-[18px]">add</span>
                                <span>New Deck</span>
                            </button>
                        </div>
                    </div>

                    <?php if ($deck_total === 0): ?>
                        <!-- Empty state -->
                        <div class="border-2 border-dashed border-outline-variant/50 rounded-2xl p-10 flex flex-col items-center justify-center gap-3 text-center bg-surface-container-low/30">
                            <span class="material-symbols-outlined text-primary text-[40px]">add_circle</span>
                            <h3 class="font-headline-sm text-[18px] font-bold text-on-surface">No decks yet</h3>
                            <p class="text-on-surface-variant">Create your first deck to start adding flashcards.</p>
                        </div>
                    <?php else: ?>

                    <!-- Grid view (hidden by default) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 transition-opacity duration-200" id="decks-grid-view">
                        <?php foreach ($decks as $deck): ?>
                            <div data-deck-card="<?= $deck['id'] ?>" class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
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
                                        <?php endif; ?>
                                    </div>
                                    <div class="pt-2 border-t border-outline-variant/20 flex items-center gap-2.5">
                                        <!-- <button class="flex-1 inline-flex items-center justify-center gap-1 px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs" type="button" data-action="add-card" data-deck-id="<?= $deck['id'] ?>">
                                            <span class="material-symbols-outlined text-[18px]">add</span>
                                            <span>Add Card</span>
                                        </button> -->
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

                    <!-- List view (default) -->
                    <div class="flex flex-col gap-4 hidden" id="decks-list-view">
                        <?php foreach ($decks as $deck): ?>
                            <div data-deck-card="<?= $deck['id'] ?>" class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 group">
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
                                        <?php if ($deck['due_count'] > 0): ?>
                                            <button
                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-bold shadow-sm hover:shadow-md transition-all"
                                                type="button"
                                                data-action="review"
                                                data-deck-id="<?= $deck['id'] ?>"
                                            >
                                                <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                                                <span>Review <?= $deck['due_count'] ?> Due</span>
                                            </button>
                                        <?php endif; ?>
                                        <!-- <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs" type="button" data-action="add-card" data-deck-id="<?= $deck['id'] ?>">
                                            <span class="material-symbols-outlined text-[18px]">add</span>
                                            <span class="hidden sm:inline">Add Card</span>
                                        </button> -->
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
        <button type="button" role="menuitem" data-menu-action="add-card" class="w-full flex items-center gap-2 px-3 py-2 text-left text-on-surface hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Add Card</span>
        </button>
        <button type="button" role="menuitem" data-menu-action="rename" class="w-full flex items-center gap-2 px-3 py-2 text-left text-on-surface hover:bg-surface-container transition-colors">
            <span class="material-symbols-outlined text-[18px]">edit</span>
            <span>Rename</span>
        </button>
        <button type="button" role="menuitem" data-menu-action="delete" class="w-full flex items-center gap-2 px-3 py-2 text-left text-rose-700 hover:bg-rose-50 transition-colors">
            <span class="material-symbols-outlined text-[18px]">delete</span>
            <span>Delete</span>
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

    <!-- Delete dialog -->
    <dialog id="delete-dialog" class="rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
        <div class="p-6 flex flex-col gap-4">
            <h3 class="font-headline-sm text-[18px] font-bold">Delete deck?</h3>
            <p class="text-on-surface-variant">
                <span id="delete-name" class="font-semibold text-on-surface"></span>
                and all of its cards will be removed from your decks.
            </p>
            <p id="delete-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
            <div class="flex justify-end gap-2.5">
                <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold transition-colors">Cancel</button>
                <button type="button" id="delete-confirm" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold transition-colors disabled:opacity-60">Delete</button>
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
                <p class="text-on-surface-variant text-[13px] mt-1">to <span id="card-deck-name" class="font-semibold text-on-surface"></span></p>
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
            <div class="flex justify-end gap-2.5">
                <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold transition-colors">Close</button>
                <button type="submit" id="card-save" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold transition-colors disabled:opacity-60">Add card</button>
            </div>
        </form>
    </dialog>

    <script>
        // View toggle (grid vs list), remembered in localStorage
        const viewGridBtn = document.getElementById('view-grid-btn');
        const viewListBtn = document.getElementById('view-list-btn');
        const gridView = document.getElementById('decks-grid-view');
        const listView = document.getElementById('decks-list-view');

        const VIEW_KEY = 'mempulse_decks_view';
        const activeBtn = "flex items-center justify-center p-1.5 rounded-lg bg-surface-container-lowest text-primary font-semibold shadow-xs transition-all";
        const idleBtn = "flex items-center justify-center p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors";

        function setView(mode) {
            const isGrid = mode !== 'list';
            gridView.classList.toggle('hidden', !isGrid);
            listView.classList.toggle('hidden', isGrid);
            viewGridBtn.className = isGrid ? activeBtn : idleBtn;
            viewGridBtn.setAttribute('aria-pressed', String(isGrid));
            viewListBtn.className = isGrid ? idleBtn : activeBtn;
            viewListBtn.setAttribute('aria-pressed', String(!isGrid));
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
        // Deck kebab menu: rename and delete
        // ---------------------------------------------------------------
        const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token']) ?>;

        const deckMenu = document.getElementById('deck-menu');
        const renameDialog = document.getElementById('rename-dialog');
        const renameForm = document.getElementById('rename-form');
        const renameInput = document.getElementById('rename-input');
        const renameError = document.getElementById('rename-error');
        const renameSave = document.getElementById('rename-save');
        const deleteDialog = document.getElementById('delete-dialog');
        const deleteName = document.getElementById('delete-name');
        const deleteError = document.getElementById('delete-error');
        const deleteConfirm = document.getElementById('delete-confirm');
        const countBadge = document.getElementById('deck-count-badge');

        let activeDeckId = null;
        let menuButton = null;

        function getDeckTitle(id) {
            const el = document.querySelector('[data-deck-title="' + id + '"]');
            return el ? el.textContent.trim() : '';
        }

        function closeMenu() {
            deckMenu.classList.add('hidden');
            if (menuButton) menuButton.setAttribute('aria-expanded', 'false');
            menuButton = null;
        }

        function openMenu(btn) {
            closeMenu();
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

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-deck-menu]');
            if (btn) {
                if (menuButton === btn) closeMenu(); else openMenu(btn);
            } else if (!deckMenu.contains(e.target)) {
                closeMenu();
            }
        });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
        window.addEventListener('resize', closeMenu);
        window.addEventListener('scroll', closeMenu, true);

        document.querySelectorAll('[data-close-dialog]').forEach((b) => {
            b.addEventListener('click', () => b.closest('dialog').close());
        });

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

        deckMenu.addEventListener('click', (e) => {
            const item = e.target.closest('[data-menu-action]');
            if (!item || !activeDeckId) return;
            const action = item.dataset.menuAction;
            closeMenu();

            if (action === 'rename') {
                renameInput.value = getDeckTitle(activeDeckId);
                renameError.classList.add('hidden');
                renameDialog.showModal();
                renameInput.select();
            } else if (action === 'delete') {
                deleteName.textContent = '"' + getDeckTitle(activeDeckId) + '"';
                deleteError.classList.add('hidden');
                deleteDialog.showModal();
            } else if (action === 'add-card') {
                openCardDialog(activeDeckId);
            }
        });

        renameForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const title = renameInput.value.trim();
            if (title === '') {
                renameError.textContent = 'Deck name cannot be empty.';
                renameError.classList.remove('hidden');
                return;
            }
            renameSave.disabled = true;
            try {
                const data = await sendDeckAction({ action: 'rename', deck_id: activeDeckId, title });
                document.querySelectorAll('[data-deck-title="' + activeDeckId + '"]').forEach((el) => {
                    el.textContent = data.title;
                });
                renameDialog.close();
            } catch (err) {
                showFieldError(renameError, renameInput, err);
            } finally {
                renameSave.disabled = false;
            }
        });

        deleteConfirm.addEventListener('click', async () => {
            deleteConfirm.disabled = true;
            try {
                await sendDeckAction({ action: 'delete', deck_id: activeDeckId });
                document.querySelectorAll('[data-deck-card="' + activeDeckId + '"]').forEach((el) => el.remove());
                deleteDialog.close();

                const remaining = document.querySelectorAll('#decks-grid-view [data-deck-card]').length;
                if (remaining === 0) {
                    window.location.reload(); // shows the empty state
                } else if (countBadge) {
                    countBadge.textContent = remaining + (remaining === 1 ? ' Deck' : ' Decks');
                }
            } catch (err) {
                deleteError.textContent = err.message;
                deleteError.classList.remove('hidden');
            } finally {
                deleteConfirm.disabled = false;
            }
        });

        // ---------------------------------------------------------------
        // Create deck (header button, grid tile, list row)
        // ---------------------------------------------------------------
        const createDialog = document.getElementById('create-dialog');
        const createForm = document.getElementById('create-form');
        const createInput = document.getElementById('create-input');
        const createError = document.getElementById('create-error');
        const createSave = document.getElementById('create-save');

        function openCreateDialog() {
            createInput.value = '';
            createError.classList.add('hidden');
            createDialog.showModal();
            createInput.focus();
        }

        ['new-deck-btn', 'new-deck-tile', 'new-deck-row'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', openCreateDialog);
        });

        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const title = createInput.value.trim();
            if (title === '') {
                createError.textContent = 'Deck name cannot be empty.';
                createError.classList.remove('hidden');
                return;
            }
            createSave.disabled = true;
            try {
                await sendDeckAction({ action: 'create', title });
                window.location.reload(); // new deck is drawn by the server in both views
            } catch (err) {
                showFieldError(createError, createInput, err);
                createSave.disabled = false;
            }
        });

        // ---------------------------------------------------------------
        // Review due cards
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
        // Add card (any element with data-action="add-card" data-deck-id="...")
        // ---------------------------------------------------------------
        const cardDialog = document.getElementById('card-dialog');
    const cardForm = document.getElementById('card-form');
    const cardFront = document.getElementById('card-front');
    const cardBack = document.getElementById('card-back');
    const cardError = document.getElementById('card-error');
    const cardSuccess = document.getElementById('card-success');
    const cardSave = document.getElementById('card-save');
    const cardDeckName = document.getElementById('card-deck-name');

    let cardDeckId = null;
    let cardsAdded = false;

    function openCardDialog(deckId) {
        cardDeckId = deckId;
        cardsAdded = false;
        cardDeckName.textContent = getDeckTitle(deckId);
        cardFront.value = '';
        cardBack.value = '';
        cardError.classList.add('hidden');
        cardSuccess.classList.add('hidden');
        cardDialog.showModal();
        cardFront.focus();
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action="add-card"]');
        if (btn) openCardDialog(btn.dataset.deckId);
    });

    // Ctrl/Cmd + Enter saves from either textarea
    [cardFront, cardBack].forEach((field) => {
        field.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                cardForm.requestSubmit();
            }
        });
    });

    cardForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const front = cardFront.value.trim();
        const back = cardBack.value.trim();

        cardSuccess.classList.add('hidden');
        if (front === '' || back === '') {
            cardError.textContent = 'Both the front and back are required.';
            cardError.classList.remove('hidden');
            return;
        }
        cardError.classList.add('hidden');

        cardSave.disabled = true;
        try {
            await sendDeckAction({ action: 'add_card', deck_id: cardDeckId, front, back });
            cardsAdded = true;
            cardFront.value = '';
            cardBack.value = '';
            cardSuccess.classList.remove('hidden');
            cardFront.focus();
        } catch (err) {
            cardError.textContent = err.message;
            cardError.classList.remove('hidden');
        } finally {
            cardSave.disabled = false;
        }
    });

    // Shows an error and, if the server suggested a name, a one-click "Use ..." button.
    function showFieldError(errorEl, input, err) {
        errorEl.textContent = err.message;
        if (err.suggestion) {
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

    // Refresh counts once the dialog closes (Close button, Esc, or backdrop)
    cardDialog.addEventListener('close', () => {
        if (cardsAdded) window.location.reload();
    });
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>