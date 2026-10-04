<?php
/**
 * Deck View Template
 * Rendered within main app shell in app/views/deck.php
 */
?>
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    <div class="flex flex-col w-full gap-6">

        <!-- 1. BREADCRUMB NAVIGATION -->
        <nav class="flex items-center gap-2 text-sm text-on-surface-variant font-medium" aria-label="Breadcrumb">
            <!-- <a href="dashboard.php" class="hover:text-primary transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[18px]">dashboard</span>
                <span>Dashboard</span>
            </a>
            <span class="material-symbols-outlined text-[16px] text-outline">chevron_right</span> -->
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
                
                <!-- Create Card Button -->
                <button type="button" id="open-add-card-btn" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold transition-all shadow-xs">
                    <span class="material-symbols-outlined text-[19px]">add</span>
                    <span>Create Card</span>
                </button>

                <!-- Toggle Right Side Editor Panel -->
                <button type="button" id="toggle-editor-panel-btn" class="inline-flex items-center gap-2 px-3 py-2.5 rounded-xl bg-surface-container-high hover:bg-surface-container-highest text-on-surface font-semibold transition-all" title="Toggle Card Editor Panel">
                    <span class="material-symbols-outlined text-[20px]">side_navigation</span>
                </button>
            </div>
        </div>

        <!-- 3. MAIN SPLIT WORKSPACE: TABLE + EDITOR PANEL -->
        <div class="flex gap-6 items-start relative w-full">

            <!-- LEFT CONTAINER: RESIZABLE DATA TABLE -->
            <div id="table-wrapper" class="flex-1 overflow-hidden rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs transition-all">
                
                <?php if (empty($flashcards)): ?>
                    <!-- Empty State -->
                    <div id="no-cards-empty-state" class="p-12 flex flex-col items-center justify-center gap-3 text-center bg-surface-container-low/20 transition-all cursor-pointer group">
                        <div class="w-14 h-14 rounded-xl bg-surface-container-lowest border border-outline-variant/30 flex items-center justify-center text-primary group-hover:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[28px]">note_add</span>
                        </div>
                        <div>
                            <h3 class="font-headline-sm text-[18px] font-bold text-on-surface group-hover:text-primary transition-colors">No flashcards yet</h3>
                            <p class="text-on-surface-variant text-[14px] mt-0.5">Use the Card Editor on the right or click here to create your first flashcard.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="flashcard-table-scroll-container" class="overflow-x-auto w-full <?= empty($flashcards) ? 'hidden' : '' ?>">
                    <table class="w-full text-left border-collapse min-w-[1100px]" id="flashcards-table">
                        <thead>
                            <tr class="border-b border-outline-variant/30 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant bg-surface-container-low/50 select-none">
                                <th class="p-4 relative resizable-col min-w-[180px]" style="width: 260px;">
                                    <div class="flex items-center justify-between">
                                        <span>Front</span>
                                        <div class="resizer" title="Drag to resize"></div>
                                    </div>
                                </th>
                                <th class="p-4 relative resizable-col min-w-[180px]" style="width: 260px;">
                                    <div class="flex items-center justify-between">
                                        <span>Back</span>
                                        <div class="resizer" title="Drag to resize"></div>
                                    </div>
                                </th>
                                <th class="p-4 relative resizable-col min-w-[160px]" style="width: 175px;">
                                    <div class="flex items-center justify-between">
                                        <span>Next Review (Due)</span>
                                        <div class="resizer" title="Drag to resize"></div>
                                    </div>
                                </th>
                                <th class="p-4 relative resizable-col min-w-[100px]" style="width: 110px;">
                                    <div class="flex items-center justify-between">
                                        <span>Interval</span>
                                        <div class="resizer" title="Drag to resize"></div>
                                    </div>
                                </th>
                                <th class="p-4 relative resizable-col min-w-[90px]" style="width: 95px;">
                                    <div class="flex items-center justify-between">
                                        <span>Reviews</span>
                                        <div class="resizer" title="Drag to resize"></div>
                                    </div>
                                </th>
                                <th class="p-4 relative resizable-col min-w-[80px]" style="width: 85px;">
                                    <div class="flex items-center justify-between">
                                        <span>Lapses</span>
                                        <div class="resizer" title="Drag to resize"></div>
                                    </div>
                                </th>
                                <th class="p-4 relative resizable-col min-w-[130px]" style="width: 145px;">
                                    <div class="flex items-center justify-between">
                                        <span>Created At</span>
                                        <div class="resizer" title="Drag to resize"></div>
                                    </div>
                                </th>
                                <th class="p-4 relative resizable-col min-w-[130px]" style="width: 145px;">
                                    <div class="flex items-center justify-between">
                                        <span>Updated At</span>
                                        <div class="resizer" title="Drag to resize"></div>
                                    </div>
                                </th>
                                <th class="p-4 w-20 text-center sticky right-0 bg-surface-container-low/90 backdrop-blur-xs shadow-xs">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="flashcards-table-body" class="divide-y divide-outline-variant/20 text-sm">
                            <?php foreach ($flashcards as $card): ?>
                                <tr data-card-item="<?= $card['id'] ?>" class="hover:bg-surface-container-low/40 transition-colors group cursor-pointer">
                                    
                                    <!-- Editable: Front -->
                                    <td class="p-4 align-top">
                                        <p class="text-on-surface font-medium whitespace-pre-wrap leading-relaxed max-h-32 overflow-y-auto" data-card-front="<?= $card['id'] ?>"><?= FlashcardService::h($card['front']) ?></p>
                                    </td>

                                    <!-- Editable: Back -->
                                    <td class="p-4 align-top">
                                        <p class="text-on-surface-variant whitespace-pre-wrap leading-relaxed max-h-32 overflow-y-auto" data-card-back="<?= $card['id'] ?>"><?= FlashcardService::h($card['back']) ?></p>
                                    </td>

                                    <!-- Editable: Next Review / Due Date -->
                                    <td class="p-4 align-top text-on-surface font-medium text-[13px] whitespace-nowrap">
                                        <span data-card-due="<?= $card['id'] ?>"><?= date('Y-m-d H:i', strtotime($card['next_review_at'])) ?></span>
                                    </td>

                                    <!-- Read-only: Interval -->
                                    <td class="p-4 align-top text-on-surface-variant text-[13px] whitespace-nowrap">
                                        <span><?= $card['interval_days'] ?> <?= $card['interval_days'] === 1 ? 'day' : 'days' ?></span>
                                    </td>

                                    <!-- Read-only: Review Count -->
                                    <td class="p-4 align-top text-on-surface-variant text-[13px] whitespace-nowrap">
                                        <span><?= $card['review_count'] ?></span>
                                    </td>

                                    <!-- Read-only: Lapses -->
                                    <td class="p-4 align-top text-on-surface-variant text-[13px] whitespace-nowrap">
                                        <span class="<?= $card['lapses'] > 0 ? 'text-amber-600 font-semibold' : '' ?>"><?= $card['lapses'] ?></span>
                                    </td>

                                    <!-- Read-only: Created At -->
                                    <td class="p-4 align-top text-on-surface-variant/80 text-[12px] whitespace-nowrap">
                                        <span><?= date('Y-m-d H:i', strtotime($card['created_at'])) ?></span>
                                    </td>

                                    <!-- Read-only: Updated At -->
                                    <td class="p-4 align-top text-on-surface-variant/80 text-[12px] whitespace-nowrap">
                                        <span data-card-updated="<?= $card['id'] ?>"><?= date('Y-m-d H:i', strtotime($card['updated_at'])) ?></span>
                                    </td>

                                    <!-- Action Icons -->
                                    <td class="p-4 align-top text-center sticky right-0 bg-surface-container-lowest group-hover:bg-surface-container-low/40 transition-colors">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" data-edit-card="<?= $card['id'] ?>" class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="Edit in Panel">
                                                <span class="material-symbols-outlined text-[18px]">edit</span>
                                            </button>
                                            <button type="button" data-delete-card="<?= $card['id'] ?>" class="p-1.5 rounded-lg text-rose-700 hover:bg-rose-50 transition-colors" title="Delete Card">
                                                <span class="material-symbols-outlined text-[18px]">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RIGHT CONTAINER: CARD EDITOR PANEL -->
            <aside id="card-editor-panel" class="w-80 lg:w-96 flex-shrink-0 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 shadow-xs transition-all flex flex-col gap-4">
                
                <!-- Panel Header -->
                <div class="flex items-center justify-between border-b border-outline-variant/20 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-[20px]">edit_note</span>
                        <h3 id="editor-panel-title" class="font-bold text-on-surface text-base">Create Flashcard</h3>
                    </div>
                    <button type="button" id="close-editor-panel-btn" class="p-1 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="Collapse Panel">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>

                <!-- Editor Form -->
                <form id="editor-panel-form" class="flex flex-col gap-4" novalidate>
                    <input type="hidden" id="panel-card-id" value="">

                    <!-- Front Input -->
                    <div class="flex flex-col gap-1.5">
                        <label for="panel-card-front" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Front (Editable)</label>
                        <textarea id="panel-card-front" rows="3" maxlength="5000" placeholder="Question or prompt..." class="w-full p-3 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary text-sm resize-y transition-colors"></textarea>
                    </div>

                    <!-- Back Input -->
                    <div class="flex flex-col gap-1.5">
                        <label for="panel-card-back" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Back (Editable)</label>
                        <textarea id="panel-card-back" rows="3" maxlength="5000" placeholder="Answer..." class="w-full p-3 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary text-sm resize-y transition-colors"></textarea>
                    </div>

                    <!-- Next Review / Due Date Input -->
                    <div class="flex flex-col gap-1.5">
                        <label for="panel-card-due" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Next Review Date (Editable)</label>
                        <input type="datetime-local" id="panel-card-due" class="w-full p-2.5 rounded-xl border border-outline-variant/50 bg-surface text-on-surface focus:outline-none focus:border-primary text-sm transition-colors">
                    </div>

                    <!-- Read-Only Metadata Indicators (Visible in Edit Mode) -->
                    <div id="panel-metadata-group" class="hidden grid-cols-2 gap-2 p-3 rounded-xl bg-surface-container-low/50 border border-outline-variant/20 text-xs text-on-surface-variant">
                        <div><span class="font-semibold text-on-surface">Interval:</span> <span id="meta-interval">-</span></div>
                        <div><span class="font-semibold text-on-surface">Reviews:</span> <span id="meta-reviews">-</span></div>
                        <div><span class="font-semibold text-on-surface">Lapses:</span> <span id="meta-lapses">-</span></div>
                        <div><span class="font-semibold text-on-surface">Updated:</span> <span id="meta-updated">-</span></div>
                    </div>

                    <p id="editor-panel-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>

                    <!-- Form Buttons -->
                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-outline-variant/20">
                        <button type="button" id="panel-reset-btn" class="px-3 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-xs transition-colors flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">add</span>
                            <span>New Card</span>
                        </button>

                        <div class="flex items-center gap-2">
                            <button type="submit" id="panel-save-btn" class="px-4 py-2 rounded-xl bg-primary hover:bg-primary-container text-on-primary font-semibold text-xs transition-colors disabled:opacity-60 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">save</span>
                                <span>Save Card</span>
                            </button>
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>
</div>

<!-- 4. DELETE CONFIRMATION DIALOG -->
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