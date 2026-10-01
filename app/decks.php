<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user_id = (int) $_SESSION['user_id'];

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
                            <div class="w-10 h-10 rounded-xl bg-primary-fixed flex items-center justify-center text-on-primary-fixed-variant shadow-xs">
                                <span class="material-symbols-outlined text-[22px]">style</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <h2 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface">Deck Gallery</h2>
                                <span class="px-2.5 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold tracking-wide shadow-xs">
                                    <?= $deck_total ?> <?= $deck_total === 1 ? 'Deck' : 'Decks' ?>
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <!-- View toggle -->
                            <div aria-label="View toggle" class="inline-flex items-center p-1 rounded-xl bg-surface-container border border-outline-variant/30 text-on-surface-variant" role="group">
                                <button aria-pressed="false" class="flex items-center justify-center p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors" id="view-grid-btn" title="Grid View" type="button">
                                    <span class="material-symbols-outlined text-[20px]">grid_view</span>
                                </button>
                                <button aria-pressed="true" class="flex items-center justify-center p-1.5 rounded-lg bg-surface-container-lowest text-primary font-semibold shadow-xs transition-all" id="view-list-btn" title="List View" type="button">
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 transition-opacity duration-200 hidden" id="decks-grid-view">
                        <?php foreach ($decks as $deck): ?>
                            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
                                <!-- Cover -->
                                <div class="relative h-44 w-full overflow-hidden border-b border-outline-variant/20 flex items-center justify-center" style="<?= h($deck['cover_style']) ?>">
                                    <span class="material-symbols-outlined text-white/80 text-[56px]">style</span>
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                                    <button class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/30 backdrop-blur-md text-white hover:bg-black/50 transition-colors flex items-center justify-center" title="Deck Options" type="button" data-deck-id="<?= $deck['id'] ?>">
                                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                    </button>
                                </div>
                                <!-- Metadata -->
                                <div class="p-5 flex-1 flex flex-col justify-between gap-4">
                                    <div>
                                        <h3 class="font-headline-sm text-[18px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors">
                                            <?= h($deck['title']) ?>
                                        </h3>
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
                                        <div class="mt-2 flex items-center gap-1 text-on-surface-variant text-[13px]">
                                            <span class="material-symbols-outlined text-[16px] text-primary">schedule</span>
                                            <?= h($deck['due_text']) ?>
                                        </div>
                                        <div class="w-full bg-surface-container-high rounded-full h-1.5 mt-3 overflow-hidden">
                                            <div class="<?= $deck['theme']['bar'] ?> h-1.5 rounded-full" style="width: <?= (int) ($deck['accuracy'] ?? 0) ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="pt-2 border-t border-outline-variant/20 flex items-center gap-2.5">
                                        <button class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors" type="button" data-action="browse" data-deck-id="<?= $deck['id'] ?>">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            <span>Browse Deck</span>
                                        </button>
                                        <button class="flex-1 inline-flex items-center justify-center gap-1 px-3.5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs" type="button" data-action="add-card" data-deck-id="<?= $deck['id'] ?>">
                                            <span class="material-symbols-outlined text-[18px]">add</span>
                                            <span>Add Card</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- List view (default) -->
                    <div class="flex flex-col gap-4" id="decks-list-view">
                        <?php foreach ($decks as $deck): ?>
                            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 group">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 flex items-center justify-center" style="<?= h($deck['cover_style']) ?>">
                                        <span class="material-symbols-outlined text-white/90 text-[26px]">style</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <h3 class="font-headline-sm text-[17px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors truncate"><?= h($deck['title']) ?></h3>
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
                                        <button class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md font-semibold transition-colors" type="button" data-action="browse" data-deck-id="<?= $deck['id'] ?>">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            <span class="hidden sm:inline">Browse</span>
                                        </button>
                                        <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-label-md text-label-md font-semibold transition-colors shadow-xs" type="button" data-action="add-card" data-deck-id="<?= $deck['id'] ?>">
                                            <span class="material-symbols-outlined text-[18px]">add</span>
                                            <span class="hidden sm:inline">Add Card</span>
                                        </button>
                                        <button class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="Options" type="button" data-deck-id="<?= $deck['id'] ?>">
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

    <script>
        // View toggle (grid vs list)
        const viewGridBtn = document.getElementById('view-grid-btn');
        const viewListBtn = document.getElementById('view-list-btn');
        const gridView = document.getElementById('decks-grid-view');
        const listView = document.getElementById('decks-list-view');

        const activeBtn = "flex items-center justify-center p-1.5 rounded-lg bg-surface-container-lowest text-primary font-semibold shadow-xs transition-all";
        const idleBtn = "flex items-center justify-center p-1.5 rounded-lg text-on-surface-variant hover:text-on-surface transition-colors";

        if (viewGridBtn && viewListBtn && gridView && listView) {
            viewGridBtn.addEventListener('click', () => {
                gridView.classList.remove('hidden');
                listView.classList.add('hidden');
                viewGridBtn.className = activeBtn;
                viewGridBtn.setAttribute('aria-pressed', 'true');
                viewListBtn.className = idleBtn;
                viewListBtn.setAttribute('aria-pressed', 'false');
            });

            viewListBtn.addEventListener('click', () => {
                listView.classList.remove('hidden');
                gridView.classList.add('hidden');
                viewListBtn.className = activeBtn;
                viewListBtn.setAttribute('aria-pressed', 'true');
                viewGridBtn.className = idleBtn;
                viewGridBtn.setAttribute('aria-pressed', 'false');
            });
        }
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>