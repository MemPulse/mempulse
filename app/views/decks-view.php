<?php
/**
 * Decks View Template
 * Rendered within main app shell in app/views/decks.php
 */
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col w-full gap-8">

        <!-- Deck Gallery -->
        <section class="flex flex-col gap-6 w-full" id="decks-gallery-section">

            <!-- Section header and controls -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-headline-md text-headline-md font-bold tracking-tight text-on-surface">Decks</h2>
                        <span id="deck-count-badge" class="px-2.5 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold tracking-wide shadow-xs">
                            <?= $deckTotal ?> <?= $deckTotal === 1 ? 'Deck' : 'Decks' ?>
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

            <?php if ($deckTotal === 0): ?>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 transition-opacity duration-200" id="decks-grid-view">
                <?php foreach ($decks as $deck): ?>
                <div data-deck-card="<?= $deck['id'] ?>" data-deck-id="<?= $deck['id'] ?>" class="deck-card deck-item bg-surface-container-lowest border border-outline-variant/30 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex flex-col group">
                    <!-- Cover -->
                    <div class="relative h-24 w-full border-b border-outline-variant/20" style="<?= DeckService::h($deck['cover_style']) ?>">
                        <?php if ($deck['due_count'] > 0): ?>
                            <div class="absolute top-2 left-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-black/50 backdrop-blur-md text-white text-[11px] font-semibold">
                                <span class="material-symbols-outlined text-[14px]">schedule</span>
                                <span><?= $deck['due_count'] ?> due</span>
                            </div>
                        <?php endif; ?>
                        <button class="absolute top-2 right-2 w-7 h-7 rounded-full bg-black/30 backdrop-blur-md text-white hover:bg-black/50 transition-colors flex items-center justify-center" title="Deck Options" type="button" data-deck-menu data-deck-id="<?= $deck['id'] ?>" aria-haspopup="menu" aria-expanded="false">
                            <span class="material-symbols-outlined text-[18px]">more_vert</span>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-3.5 flex flex-col gap-3 flex-1">
                        <div class="min-w-0">
                            <a href="deck.php?deck_id=<?= $deck['id'] ?>" data-deck-title="<?= $deck['id'] ?>" title="<?= DeckService::h($deck['title']) ?>" class="block truncate font-headline-sm text-[15px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors"><?= DeckService::h($deck['title']) ?></a>
                            <div class="mt-1 flex items-center gap-1.5 text-[12px] text-on-surface-variant min-w-0">
                                <span class="font-semibold text-on-surface flex-shrink-0"><?= $deck['card_count'] ?> <?= $deck['card_count'] === 1 ? 'Card' : 'Cards' ?></span>
                                <span class="text-outline-variant">•</span>
                                <span class="truncate"><?= DeckService::h($deck['due_text']) ?></span>
                            </div>
                        </div>

                        <div class="mt-auto pt-3 border-t border-outline-variant/20 flex items-center justify-between gap-2">
                            <!-- Chip is a div on purpose: the archive handler in decks.js looks for a <span> containing '%' -->
                            <div class="inline-flex items-center gap-1 px-2 py-1 rounded-md border text-[12px] font-semibold <?= $deck['theme']['badge'] ?>" title="Accuracy">
                                <span class="material-symbols-outlined text-[14px]"><?= $deck['theme']['icon'] ?></span>
                                <span><?= $deck['accuracy'] === null ? '—' : $deck['accuracy'] . '%' ?></span>
                            </div>

                            <?php if ($deck['due_count'] > 0): ?>
                                <button type="button" data-action="review" data-deck-id="<?= $deck['id'] ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-[13px] font-bold shadow-sm transition-all">
                                    <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                                    <span>Review</span>
                                </button>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 text-[12px] font-medium text-on-surface-variant/70">
                                    <span class="material-symbols-outlined text-[16px]"><?= $deck['card_count'] === 0 ? 'inbox' : 'check_circle' ?></span>
                                    <span><?= $deck['card_count'] === 0 ? 'Empty' : 'Caught up' ?></span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Create new deck tile -->
                <div class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-xl min-h-[14rem] flex flex-col items-center justify-center gap-3 bg-surface-container-low/30 hover:bg-surface-container-low transition-all cursor-pointer group" id="new-deck-tile">
                    <div class="w-14 h-14 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[24px]">add_circle</span>
                    </div>
                    <h4 class="font-headline-sm text-[16px] font-bold text-on-surface group-hover:text-primary transition-colors">Create New Deck</h4>
                </div>
            </div>

            <!-- List view -->
            <div class="flex flex-col gap-3 hidden" id="decks-list-view">
                <?php foreach ($decks as $deck): ?>
                <div data-deck-card="<?= $deck['id'] ?>" data-deck-id="<?= $deck['id'] ?>" class="deck-card deck-item bg-surface-container-lowest border border-outline-variant/30 rounded-xl p-3 shadow-sm hover:shadow-md hover:border-primary-fixed-dim/60 transition-all flex items-center gap-3 group">
                    <div class="hidden sm:block w-12 h-12 rounded-lg flex-shrink-0" style="<?= DeckService::h($deck['cover_style']) ?>; background-position: left center !important;"></div>

                    <div class="flex-1 min-w-0">
                        <a href="deck.php?deck_id=<?= $deck['id'] ?>" data-deck-title="<?= $deck['id'] ?>" title="<?= DeckService::h($deck['title']) ?>" class="block truncate font-headline-sm text-[15px] font-bold text-on-surface tracking-tight group-hover:text-primary transition-colors"><?= DeckService::h($deck['title']) ?></a>
                        <div class="mt-0.5 flex items-center gap-1.5 text-[12px] text-on-surface-variant min-w-0">
                            <span class="font-semibold text-on-surface flex-shrink-0"><?= $deck['card_count'] ?> <?= $deck['card_count'] === 1 ? 'Card' : 'Cards' ?></span>
                            <span class="text-outline-variant">•</span>
                            <span class="truncate"><?= DeckService::h($deck['due_text']) ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="inline-flex items-center gap-1 px-2 py-1 rounded-md border text-[12px] font-semibold <?= $deck['theme']['badge'] ?>" title="Accuracy">
                            <span class="material-symbols-outlined text-[14px] hidden sm:inline-block"><?= $deck['theme']['icon'] ?></span>
                            <span><?= $deck['accuracy'] === null ? '—' : $deck['accuracy'] . '%' ?></span>
                        </div>

                        <?php if ($deck['due_count'] > 0): ?>
                            <button type="button" data-action="review" data-deck-id="<?= $deck['id'] ?>" aria-label="Review" title="Review" class="inline-flex items-center justify-center gap-1 w-9 h-9 sm:w-auto sm:h-auto sm:px-3.5 sm:py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-[13px] font-bold shadow-sm transition-all">
                                <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                                <span class="hidden sm:inline">Review</span>
                            </button>
                        <?php else: ?>
                            <span class="inline-flex items-center justify-center gap-1 w-9 sm:w-auto text-[12px] font-medium text-on-surface-variant/70" title="<?= $deck['card_count'] === 0 ? 'Empty deck' : 'All caught up' ?>">
                                <span class="material-symbols-outlined text-[18px]"><?= $deck['card_count'] === 0 ? 'inbox' : 'check_circle' ?></span>
                                <span class="hidden sm:inline"><?= $deck['card_count'] === 0 ? 'Empty' : 'Caught up' ?></span>
                            </span>
                        <?php endif; ?>

                        <button class="w-8 h-8 rounded-lg hover:bg-surface-container text-on-surface-variant hover:text-on-surface transition-colors flex items-center justify-center" title="Deck Options" type="button" data-deck-menu data-deck-id="<?= $deck['id'] ?>" aria-haspopup="menu" aria-expanded="false">
                            <span class="material-symbols-outlined text-[18px]">more_vert</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Create new deck row -->
                <div class="border-2 border-dashed border-outline-variant/50 hover:border-primary rounded-xl p-3 flex items-center justify-between bg-surface-container-low/30 hover:bg-surface-container-low transition-all cursor-pointer group" id="new-deck-row">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform flex-shrink-0">
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

<!-- Shared kebab menu -->
<div id="deck-menu" class="hidden fixed z-50 w-44 rounded-xl bg-surface-container-lowest border border-outline-variant/30 shadow-lg py-1" role="menu">
    <button type="button" role="menuitem" data-menu-action="rename" class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-on-surface hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-[18px]">edit</span>
        <span>Rename Deck</span>
    </button>
    <button type="button" role="menuitem" data-menu-action="archive" class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-amber-700 hover:bg-amber-50 transition-colors">
        <span class="material-symbols-outlined text-[18px]">archive</span>
        <span>Archive Deck</span>
    </button>
    <button type="button" role="menuitem" data-menu-action="move-pos" class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-on-surface hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-[18px]">drag_indicator</span>
        <span>Move Deck Position</span>
    </button>
    <button type="button" role="menuitem" data-menu-action="edit-banner" class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-on-surface hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-[18px]">image</span>
        <span>Edit Cover Image</span>
    </button>
    <hr class="my-1 border-outline-variant/20">
    <button type="button" role="menuitem" data-menu-action="add-card" class="w-full flex items-center gap-2 px-3 py-2 text-left text-xs text-on-surface hover:bg-surface-container transition-colors">
        <span class="material-symbols-outlined text-[18px]">add</span>
        <span>Add Card</span>
    </button>
</div>

<!-- Edit Banner Dialog -->
<dialog id="banner-dialog" class="m-auto rounded-2xl p-0 w-full max-w-xl bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
    <div class="p-6 flex flex-col gap-5">
        <div class="flex justify-between items-center">
            <h3 class="font-headline-sm text-[18px] font-bold">Select Deck Banner</h3>
            <button type="button" data-close-dialog class="text-on-surface-variant hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <p id="banner-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>

        <!-- Preset Gallery Grid (Dynamically Scanned) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-72 overflow-y-auto p-1">
            <!-- Default Gradient Preset Option -->
            <button type="button" data-banner-path="" class="banner-option border-2 border-primary rounded-xl h-20 flex items-center justify-center bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-semibold text-xs shadow-sm hover:opacity-90 transition-all">
                Default Gradient
            </button>

            <?php
            $bannerDir = __DIR__ . '/../assets/images/deck-header-banner/';
            $bannerFiles = glob($bannerDir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);

            // Sort files numerically (e.g., dh1, dh2, dh10)
            natsort($bannerFiles);

            foreach ($bannerFiles as $filePath):
                $fileName = basename($filePath);
                $relativePath = '../assets/images/deck-header-banner/' . $fileName;
            ?>
                <button type="button" data-banner-path="<?= DeckService::h($relativePath) ?>" class="banner-option border-2 border-transparent hover:border-primary rounded-xl h-20 overflow-hidden relative transition-all group">
                    <img src="<?= DeckService::h($relativePath) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200" alt="<?= DeckService::h($fileName) ?>">
                </button>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end gap-2.5 pt-2 border-t border-outline-variant/20">
            <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-sm transition-colors">Cancel</button>
            <button type="button" id="banner-save" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-colors">Save Banner</button>
        </div>
    </div>
</dialog>

<!-- Rename dialog -->
<dialog id="rename-dialog" class="m-auto rounded-2xl p-0 w-full max-w-xl bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
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
<dialog id="archive-dialog" class="m-auto rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
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
<dialog id="create-dialog" class="m-auto rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
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
<dialog id="card-dialog" class="m-auto rounded-2xl p-0 w-full max-w-lg bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
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
                    <option value="<?= $deck['id'] ?>"><?= DeckService::h($deck['title']) ?></option>
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

<!-- Archived Decks Modal (Center-aligned using m-auto) -->
<dialog id="archived-decks-dialog" class="m-auto rounded-2xl p-0 w-full max-w-2xl bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
    <div class="p-6 flex flex-col gap-4 max-h-[80vh] overflow-y-auto">
        <div class="flex justify-between items-center">
            <h3 class="font-headline-sm text-[18px] font-bold">Archived Decks</h3>
            <button type="button" data-close-dialog class="text-on-surface-variant hover:text-on-surface transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- Toast Feedback Bar -->
        <div id="archived-toast" class="hidden px-4 py-3 rounded-xl font-semibold text-sm transition-all flex items-center justify-between">
            <span id="archived-toast-message"></span>
            <button type="button" onclick="document.getElementById('archived-toast').classList.add('hidden')" class="text-xs underline ml-4">Dismiss</button>
        </div>

        <div class="flex flex-col gap-3" id="archived-decks-list-container">
            <?php if (empty($archivedDecks)): ?>
                <p id="no-archived-msg" class="text-on-surface-variant text-center py-6 text-sm">No archived decks found.</p>
            <?php else: ?>
                <?php foreach ($archivedDecks as $ad): ?>
                    <div data-archived-item="<?= $ad['id'] ?>" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-outline-variant/40 bg-surface gap-4 hover:border-primary/50 transition-colors">
                        <div>
                            <h4 class="font-bold text-on-surface text-[15px]" data-archived-title="<?= $ad['id'] ?>"><?= DeckService::h($ad['title']) ?></h4>
                            <div class="text-[13px] text-on-surface-variant mt-1.5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1">
                                <span><strong>Cards:</strong> <?= $ad['card_count'] ?></span>
                                <span><strong>Accuracy:</strong> <?= $ad['accuracy'] !== null ? $ad['accuracy'] . '%' : 'N/A' ?></span>
                                <span><strong>Archived:</strong> <?= date('M j, Y', strtotime($ad['archive_date'])) ?></span>
                                <span><strong>Reviewed:</strong> <?= $ad['last_reviewed'] ? DeckService::lastReviewedLabel($ad['last_reviewed']) : 'Never' ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button type="button" data-restore-deck="<?= $ad['id'] ?>" class="px-3.5 py-2 rounded-xl bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary text-sm font-semibold transition-colors whitespace-nowrap shadow-sm">
                                Restore
                            </button>
                            <button type="button" data-delete-archived="<?= $ad['id'] ?>" class="px-3.5 py-2 rounded-xl border border-rose-200 text-rose-700 hover:bg-rose-50 text-sm font-semibold transition-colors whitespace-nowrap" title="Delete Permanently">
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</dialog>

<!-- Permanent Delete Confirmation Dialog -->
<dialog id="permanent-delete-dialog" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[60] m-0 rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50">
    <div class="p-6 flex flex-col gap-4">
        <h3 class="font-headline-sm text-[18px] font-bold text-rose-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-[22px]">warning</span>
            <span>Permanently Delete Deck?</span>
        </h3>
        <p class="text-on-surface-variant text-[14px]">
            Are you sure you want to permanently delete <span id="perm-delete-name" class="font-semibold text-on-surface"></span>?
            This will permanently erase the deck and all associated flashcards. This action cannot be undone.
        </p>
        <p id="perm-delete-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
        <div class="flex justify-end gap-2.5">
            <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-[14px] transition-colors">Cancel</button>
            <button type="button" id="perm-delete-confirm" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-[14px] transition-colors disabled:opacity-60">Delete Permanently</button>
        </div>
    </div>
</dialog>

<!-- Move Deck Position Dialog -->
<dialog id="move-pos-dialog" class="m-auto rounded-2xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
    <form id="move-pos-form" class="p-6 flex flex-col gap-4" novalidate>
        <h3 class="font-headline-sm text-[18px] font-bold">Move Deck Position</h3>
        <p class="text-on-surface-variant text-[13px]">
            Reorder <span id="move-pos-deck-name" class="font-semibold text-on-surface"></span> in your deck gallery.
        </p>
        <div class="flex flex-col gap-1.5">
            <label for="move-pos-input" class="text-on-surface-variant text-[13px] font-semibold">New Position Number</label>
            <input id="move-pos-input" type="number" min="1" step="1" class="w-full px-3 py-2 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary">
            <p id="move-pos-hint" class="text-on-surface-variant/70 text-[12px]">Position 1 appears first.</p>
        </div>
        <div class="flex justify-end gap-2.5 pt-2">
            <button type="button" data-close-dialog class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-sm transition-colors">Cancel</button>
            <button type="submit" id="move-pos-save" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-colors">Apply</button>
        </div>
    </form>
</dialog>