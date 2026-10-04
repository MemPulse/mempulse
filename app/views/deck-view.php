<?php
/**
 * Deck View Template
 * Rendered within main app shell in app/views/deck.php
 */
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col w-full gap-6">

        <!-- 1. BREADCRUMB NAVIGATION -->
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

        <!-- 2. DECK HEADER BANNER -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
            <div class="flex flex-col gap-1">
                <h1 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface">
                    <?= FlashcardService::h($deck['title']) ?>
                </h1>
                <div class="flex items-center gap-3 text-sm text-on-surface-variant">
                    <span class="inline-flex items-center gap-1 font-semibold text-on-surface">
                        <span class="material-symbols-outlined text-primary text-[18px]">style</span>
                        <span id="deck-card-count-label"><?= $deck['card_count'] ?> <?= $deck['card_count'] === 1 ? 'Card' : 'Cards' ?></span>
                    </span>
                    <span>•</span>
                    <span class="text-primary font-semibold"><?= $deck['due_count'] ?> Due Now</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <?php if ($deck['due_count'] > 0): ?>
                    <a href="review.php?deck_id=<?= $deck['id'] ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold shadow-sm transition-all">
                        <span class="material-symbols-outlined text-[19px]">play_arrow</span>
                        <span>Start Review</span>
                    </a>
                <?php endif; ?>
                <button type="button" id="open-add-card-btn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold transition-all">
                    <span class="material-symbols-outlined text-[19px]">add</span>
                    <span>Add Flashcard</span>
                </button>
            </div>
        </div>

        <!-- 3. FLASHCARDS SECTION HEADER -->
        <section class="flex flex-col gap-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 class="text-lg font-bold text-on-surface">Cards in Deck</h2>
            </div>

            <?php if (empty($flashcards)): ?>
                <!-- Empty State -->
                <div id="no-cards-empty-state" class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-2xl p-10 flex flex-col items-center justify-center gap-3 text-center bg-surface-container-low/30 transition-all cursor-pointer group" onclick="document.getElementById('open-add-card-btn').click()">
                    <div class="w-14 h-14 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[28px]">add_circle</span>
                    </div>
                    <div>
                        <h3 class="font-headline-sm text-[18px] font-bold text-on-surface group-hover:text-primary transition-colors">No flashcards yet</h3>
                        <p class="text-on-surface-variant text-[14px] mt-0.5">Click here to create your first card in this deck.</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Flashcard List Container -->
                <div id="flashcards-list-container" class="grid grid-cols-1 gap-4">
                    <?php foreach ($flashcards as $card): ?>
                        <div data-card-item="<?= $card['id'] ?>" class="p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:border-primary/50 transition-colors">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1 w-full">
                                <div>
                                    <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Front</span>
                                    <p class="text-on-surface font-medium mt-1 whitespace-pre-wrap" data-card-front="<?= $card['id'] ?>"><?= FlashcardService::h($card['front']) ?></p>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Back</span>
                                    <p class="text-on-surface-variant mt-1 whitespace-pre-wrap" data-card-back="<?= $card['id'] ?>"><?= FlashcardService::h($card['back']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-center">
                                <button type="button" data-edit-card="<?= $card['id'] ?>" class="p-2 rounded-xl text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="Edit Card">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>
                                <button type="button" data-delete-card="<?= $card['id'] ?>" class="p-2 rounded-xl text-rose-700 hover:bg-rose-50 transition-colors" title="Delete Card">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- 4. MODALS & DIALOGS -->

<!-- Add / Edit Flashcard Dialog -->
<dialog id="card-modal-dialog" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 m-0 rounded-2xl p-0 w-full max-w-lg bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
    <form id="card-modal-form" class="p-6 flex flex-col gap-4" novalidate>
        <h3 id="card-modal-title" class="font-headline-sm text-[18px] font-bold">Add Flashcard</h3>
        
        <input type="hidden" id="card-modal-id" value="">

        <div class="flex flex-col gap-1.5">
            <label for="card-modal-front" class="text-on-surface-variant text-[13px] font-semibold">Front (Question or Prompt)</label>
            <textarea id="card-modal-front" rows="3" maxlength="5000" placeholder="e.g. What is the derivative of sin(x)?" class="w-full px-3 py-2 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary resize-y"></textarea>
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="card-modal-back" class="text-on-surface-variant text-[13px] font-semibold">Back (Answer)</label>
            <textarea id="card-modal-back" rows="3" maxlength="5000" placeholder="e.g. cos(x)" class="w-full px-3 py-2 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary resize-y"></textarea>
        </div>

        <p id="card-modal-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>

        <div class="flex items-center justify-between gap-2.5 pt-2 border-t border-outline-variant/20">
            <span class="text-on-surface-variant/70 text-[12px] font-medium inline-flex items-center gap-1.5">
                <kbd class="px-1.5 py-0.5 text-[11px] font-mono font-semibold text-on-surface-variant bg-surface-container-high border border-outline-variant/60 rounded-md shadow-2xs">Ctrl</kbd>
                <span>+</span>
                <kbd class="px-1.5 py-0.5 text-[11px] font-mono font-semibold text-on-surface-variant bg-surface-container-high border border-outline-variant/60 rounded-md shadow-2xs">Enter</kbd>
                <span class="ml-0.5">to save</span>
            </span>

            <div class="flex justify-end gap-2.5">
                <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-[14px] transition-colors">Cancel</button>
                <button type="submit" id="card-modal-save" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-[14px] transition-colors disabled:opacity-60">Save Card</button>
            </div>
        </div>
    </form>
</dialog>

<!-- Delete Flashcard Confirmation Dialog -->
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