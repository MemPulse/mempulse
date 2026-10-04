<?php
/**
 * Deck View Template with Tabulator Data Grid & Card View Modal
 */
?>
<link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_simple.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>

<script>
    window.MemPulseDeckData = window.MemPulseDeckData || {};
    // Normalize $flashcards array indices using array_values()
    window.MemPulseDeckData.flashcards = <?= json_encode(array_values($flashcards ?? []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col w-full gap-6">

        <!-- BREADCRUMB NAVIGATION -->
        <nav class="flex items-center gap-2 text-sm text-on-surface-variant font-medium" aria-label="Breadcrumb">
            <a href="dashboard.php" class="hover:text-primary transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                <span>Dashboard</span>
            </a>
            <span class="material-symbols-outlined text-[16px] text-outline">chevron_right</span>
            <a href="decks.php" class="hover:text-primary transition-colors">Decks</a>
            <span class="material-symbols-outlined text-[16px] text-outline">chevron_right</span>
            <span class="text-on-surface font-semibold truncate max-w-[200px] sm:max-w-xs"><?= FlashcardService::h($deck['title']) ?></span>
        </nav>

        <!-- DECK HEADER BAR (single row) -->
        <div class="flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
            <div class="flex items-center gap-3 min-w-0 text-sm text-on-surface-variant">
                <h1 class="font-headline-sm text-headline-sm font-bold tracking-tight text-on-surface truncate"
                    title="<?= FlashcardService::h($deck['title']) ?>">
                    <?= FlashcardService::h($deck['title']) ?>
                </h1>
                <span class="text-outline">•</span>
                <span class="inline-flex items-center gap-1 font-semibold text-on-surface whitespace-nowrap">
                    <span class="material-symbols-outlined text-primary text-[18px]">style</span>
                    <span id="deck-card-count-label"><?= $deck['card_count'] ?> <?= $deck['card_count'] === 1 ? 'Card' : 'Cards' ?></span>
                </span>
                <span class="text-outline">•</span>
                <span class="text-primary font-semibold whitespace-nowrap"><?= $deck['due_count'] ?> Due<span class="hidden sm:inline"> Now</span></span>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <?php if ($deck['due_count'] > 0): ?>
                    <a href="review.php?deck_id=<?= $deck['id'] ?>"
                    title="Start Review" aria-label="Start Review"
                    class="inline-flex items-center justify-center gap-2 px-2.5 lg:px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm shadow-sm transition-all">
                        <span class="material-symbols-outlined text-[19px]">play_arrow</span>
                        <span class="hidden lg:inline">Start Review</span>
                    </a>
                <?php endif; ?>

                <button type="button" id="open-add-card-btn"
                        title="Create Card" aria-label="Create Card"
                        class="inline-flex items-center justify-center gap-2 px-2.5 lg:px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs">
                    <span class="material-symbols-outlined text-[19px]">add</span>
                    <span class="hidden lg:inline">Create Card</span>
                </button>
            </div>
        </div>

        <!-- MAIN TABULATOR GRID WORKSPACE -->
        <div id="table-wrapper" class="w-full overflow-hidden rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs flex flex-col min-h-[300px]">
            <!-- Empty state displayed when flashcards array is empty -->
            <div id="no-cards-empty-state" class="p-12 flex-col items-center justify-center gap-3 text-center bg-surface-container-low/20 <?= empty($flashcards) ? 'flex' : 'hidden' ?>">
                <h3 class="font-bold text-on-surface">No flashcards yet</h3>
                <p class="text-on-surface-variant text-sm">Click "Create Card" to add your first card.</p>
            </div>

            <!-- Tabulator table target -->
            <div id="flashcards-tabulator-grid" class="w-full <?= empty($flashcards) ? 'hidden' : '' ?>"></div>
        </div>
    </div>
</div>

<!-- FLASHCARD VIEW / EDIT MODAL DIALOG -->
<dialog id="card-editor-modal" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 m-0 rounded-2xl p-0 w-full max-w-xl bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50">
    <div class="p-6 flex flex-col gap-5 relative">
        
        <!-- Modal Header with Right-Aligned 'x' Icon -->
        <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3 pr-8">
            <h3 id="modal-title" class="font-headline-sm text-[18px] font-bold text-on-surface">View / Edit Flashcard</h3>
            <button type="button" id="close-modal-btn" class="absolute top-5 right-5 p-1 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="Close">
                <span class="material-symbols-outlined text-[22px]">close</span>
            </button>
        </div>

        <!-- Form Body -->
        <form id="modal-card-form" class="flex flex-col gap-4" novalidate>
            <input type="hidden" id="modal-card-id" value="">

            <!-- Front Textarea -->
            <div class="flex flex-col gap-1.5">
                <label for="modal-card-front" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Front Content</label>
                <textarea id="modal-card-front" rows="3" maxlength="5000" placeholder="Type or click to edit front side content..." class="w-full p-3 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary text-sm resize-y transition-colors"></textarea>
            </div>

            <!-- Back Textarea -->
            <div class="flex flex-col gap-1.5">
                <label for="modal-card-back" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Back Content</label>
                <textarea id="modal-card-back" rows="3" maxlength="5000" placeholder="Type or click to edit back side content..." class="w-full p-3 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary text-sm resize-y transition-colors"></textarea>
            </div>

            <!-- Due Date Input (Today + Future Calendar Constraint) -->
            <div class="flex flex-col gap-1.5">
                <label for="modal-card-due" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Next Review Date (Due)</label>
                <input type="datetime-local" id="modal-card-due" class="w-full p-2.5 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary text-sm transition-colors">
            </div>

            <p id="modal-card-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>

            <!-- Save Action Button -->
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant/20">
                <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-xs transition-colors">Cancel</button>
                <button type="submit" id="modal-save-btn" class="px-5 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs transition-colors disabled:opacity-60 flex items-center gap-1.5 shadow-xs">
                    <span class="material-symbols-outlined text-[16px]">save</span>
                    <span>Save</span>
                </button>
            </div>
        </form>
    </div>
</dialog>

<!-- DELETE CONFIRMATION DIALOG -->
<dialog id="delete-card-dialog" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 m-0 rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
    <div class="p-6 flex flex-col gap-4">
        <h3 class="font-headline-sm text-[18px] font-bold text-rose-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-[22px]">warning</span>
            <span>Delete Flashcard?</span>
        </h3>
        <p class="text-on-surface-variant text-[14px]">
            Are you sure you want to delete this flashcard? This action cannot be undone.
        </p>
        <p id="delete-card-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
        <div class="flex justify-end gap-2.5">
            <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-[14px] transition-colors">Cancel</button>
            <button type="button" id="delete-card-confirm" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-[14px] transition-colors disabled:opacity-60">Delete Card</button>
        </div>
    </div>
</dialog>