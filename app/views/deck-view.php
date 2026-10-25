<?php
/**
 * Deck View Template with Tabulator Data Grid & Persistent Sidebar Card Editor / Mobile Modal
 */
?>
<link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_simple.min.css" rel="stylesheet">
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>

<script>
    window.MemPulseDeckData = window.MemPulseDeckData || {};
    window.MemPulseDeckData.flashcards = <?= json_encode(array_values($flashcards ?? []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.MemPulseDeckData.archivedFlashcards = <?= json_encode(array_values($archivedFlashcards ?? []), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<style>
:root{
  --mp-g1:#4f46e5; --mp-g2:#7c3aed; --mp-g3:#d946ef;   /* change these 3 to re-theme */
  --mp-pane-h:clamp(440px, calc(100vh - 19.5rem), 780px); /* grid + editor height */
}
@supports (height:100dvh){ :root{ --mp-pane-h:clamp(440px, calc(100dvh - 19.5rem), 780px); } }

/* Layout: editor column exists on desktop only */
.mp-workspace{display:grid;grid-template-columns:minmax(0,1fr);gap:1.5rem;align-items:stretch}
@media (min-width:1024px){
  .mp-workspace{grid-template-columns:minmax(0,1fr) clamp(340px,30vw,440px)}
  .mp-workspace.editor-collapsed{grid-template-columns:minmax(0,1fr)}
}
.mp-pane{height:var(--mp-pane-h);min-height:0}
.mp-table-pane{display:flex;flex-direction:column;overflow:hidden;border-radius:1rem}
.mp-grid-fill{flex:1 1 0;min-height:0}
.mp-grid-row{display:flex;flex:1 1 0;min-height:0}
.mp-grid-row:has(#flashcards-tabulator-grid.hidden){display:none}
.mp-grid-row > #flashcards-tabulator-grid{min-width:0}
.mp-rail{flex:0 0 auto;width:2.75rem;display:flex;flex-direction:column;gap:2px;padding:.4rem .25rem;overflow-y:auto;scrollbar-width:none;border-left:1px solid rgba(124,58,237,.15);background:rgba(124,58,237,.03)}
.mp-rail[hidden]{display:none}
.mp-rail button{flex:0 0 auto;border:0;border-radius:.5rem;padding:.35rem 0;font-size:.6875rem;font-weight:600;line-height:1;font-variant-numeric:tabular-nums;color:#64748b;background:transparent;cursor:pointer}
.mp-rail button:hover{background:rgba(124,58,237,.12);color:var(--mp-g2)}
.mp-rail button.is-active{color:#fff;background:linear-gradient(135deg,var(--mp-g1),var(--mp-g3))}
.mp-rail button:focus-visible{outline:2px solid var(--mp-g2);outline-offset:1px}
#flashcards-tabulator-grid .mp-rownum{color:#94a3b8;font-size:.75rem;font-variant-numeric:tabular-nums}
.mp-editor{display:none}
@media (min-width:1024px){ .mp-editor{display:flex} }
.mp-editor.mp-collapsed{display:none!important}
.mp-footer[hidden],.mp-footer [hidden],.mp-peek[hidden]{display:none!important}

/* Gradient shell (editor + modal) */
.mp-shell{padding:1.5px;border-radius:1.125rem;
  background:linear-gradient(135deg,var(--mp-g1),var(--mp-g2) 50%,var(--mp-g3));
  box-shadow:0 18px 40px -18px rgba(99,102,241,.55),0 6px 16px -8px rgba(0,0,0,.18)}
.mp-shell-inner{display:flex;flex-direction:column;height:100%;height:100%;min-height:0;overflow:hidden;border-radius:calc(1.125rem - 1.5px)}
.mp-head{position:relative;display:flex;align-items:center;justify-content:space-between;gap:.5rem;padding:.9rem 1rem;color:#fff;
  background:linear-gradient(135deg,var(--mp-g1) 0%,var(--mp-g2) 55%,var(--mp-g3) 100%)}
.mp-head::after{content:"";position:absolute;inset:0;pointer-events:none;
  background:radial-gradient(120% 140% at 100% 0%,rgba(255,255,255,.25),transparent 55%)}
.mp-head>*{position:relative;z-index:1}
.mp-head-btn{width:2rem;height:2rem;display:inline-flex;align-items:center;justify-content:center;border-radius:.6rem;
  background:rgba(255,255,255,.16);color:#fff;transition:background .15s}
.mp-head-btn:hover{background:rgba(255,255,255,.3)}
.mp-body{flex:1 1 auto;min-height:0;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:1rem}
.mp-foot{display:flex;justify-content:flex-end;gap:.5rem;padding:.75rem 1rem;border-top:1px solid rgba(124,58,237,.15);background:rgba(124,58,237,.04)}
.mp-field:focus{outline:none;border-color:var(--mp-g2);box-shadow:0 0 0 3px rgba(124,58,237,.18)}

/* Buttons */
.mp-btn{display:inline-flex;align-items:center;gap:.375rem;padding:.5rem .9rem;border-radius:.75rem;font-size:.8125rem;font-weight:600;transition:transform .15s,box-shadow .15s,opacity .15s}
.mp-btn-icon{padding:.45rem}
.mp-btn-gradient{color:#fff;background:linear-gradient(135deg,var(--mp-g1),var(--mp-g2) 55%,var(--mp-g3));box-shadow:0 6px 16px -6px rgba(124,58,237,.7)}
.mp-btn-gradient:hover{transform:translateY(-1px);box-shadow:0 10px 20px -8px rgba(124,58,237,.8)}
.mp-btn-gradient:disabled{opacity:.6;cursor:not-allowed;transform:none}
.mp-btn-ghost{color:var(--mp-g2);border:1px solid rgba(124,58,237,.3);background:transparent}
.mp-btn-ghost:hover{background:rgba(124,58,237,.08)}
.mp-spin{animation:mp-spin 1s linear infinite}
@keyframes mp-spin{to{transform:rotate(360deg)}}

/* Keyboard hint badges (hidden on touch-only devices) */
.mp-kbd-group{display:inline-flex;align-items:center;gap:.2rem;margin-left:.35rem}
.mp-kbd{display:inline-flex;align-items:center;padding:.05rem .35rem;border-radius:.35rem;font:600 .625rem/1.3 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;
  color:#fff;background:rgba(255,255,255,.22);border:1px solid rgba(255,255,255,.35)}
@media (hover:none){.mp-kbd-group{display:none}}

/* Lazy-load footer */
.mp-footer{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.625rem .875rem;
  border-top:1px solid rgba(124,58,237,.18);background:linear-gradient(90deg,rgba(79,70,229,.06),rgba(217,70,239,.06))}
.mp-footer-info{display:flex;flex-direction:column;gap:.35rem;min-width:0;flex:1}
.mp-progress{height:4px;max-width:260px;border-radius:999px;background:rgba(124,58,237,.15);overflow:hidden}
.mp-progress>span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,var(--mp-g1),var(--mp-g3));transition:width .3s ease}

/* Peek tab (collapsed editor, desktop only) */
.mp-peek{position:fixed;right:0;top:50%;z-index:40;transform:translate(8px,-50%);display:flex;flex-direction:column;align-items:center;gap:.5rem;
  padding:.9rem .55rem;border-radius:.9rem 0 0 .9rem;color:#fff;font-weight:700;font-size:.75rem;letter-spacing:.08em;
  background:linear-gradient(180deg,var(--mp-g1),var(--mp-g2) 55%,var(--mp-g3));box-shadow:-8px 8px 24px -8px rgba(99,102,241,.6);transition:transform .2s}
.mp-peek:hover,.mp-peek:focus-visible{transform:translate(0,-50%)}
.mp-peek-label{writing-mode:vertical-rl;text-transform:uppercase}
@media (max-width:1023.98px){ .mp-peek{display:none!important} }

/* Modal (mobile + tablet) */
dialog.mp-modal{position:fixed;inset:0;margin:auto;padding:0;border:0;background:transparent;color:inherit;
  width:min(94vw,38rem);max-width:none;max-height:92vh;max-height:92dvh;overflow:visible}
dialog.mp-modal::backdrop{background:rgba(15,23,42,.6);backdrop-filter:blur(6px)}
dialog.mp-modal[open]{animation:mp-pop .18s ease-out}
dialog.mp-modal .mp-shell-inner{max-height:calc(92vh - 3px);max-height:calc(92dvh - 3px)}
@keyframes mp-pop{from{opacity:0;transform:translateY(8px) scale(.98)}to{opacity:1;transform:none}}

/* Grid: view-only look */
#flashcards-tabulator-grid .tabulator-row{cursor:pointer}
#flashcards-tabulator-grid .tabulator-row.tabulator-selected,
#flashcards-tabulator-grid .tabulator-row.tabulator-selected:hover{
  background:linear-gradient(90deg,rgba(99,102,241,.22),rgba(217,70,239,.10))!important;box-shadow:inset 3px 0 0 var(--mp-g2)}

.mp-check { width: 1.1rem; height: 1.1rem; accent-color: var(--mp-g2); cursor: pointer; }
.workspace-editing { --mp-pane-h: clamp(380px, calc(100vh - 23.5rem), 720px); }

#bulk-panel { 
    display: flex; 
    align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; 
    padding: 0.25rem 1rem; 
    border: 1px solid rgba(124, 58, 237, 0.2); 
    border-radius: 0.75rem; 
    margin-bottom: -10px; 
    color: inherit;
    background: rgba(128, 128, 128, 0.08); /* Neutral background */
}

#bulk-panel [hidden] { display: none !important; }

</style>

<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
    <div class="flex flex-col w-full gap-4 sm:gap-6">
        <!-- DECK HEADER: breadcrumb + action bar -->
        <div class="flex flex-col gap-3">

            <!-- BREADCRUMB -->
            <nav aria-label="Breadcrumb" class="flex items-center gap-2 min-w-0 px-1 text-sm text-on-surface-variant">
                <a href="decks.php" class="hover:text-primary transition-colors">Decks</a>
                <span class="material-symbols-outlined text-[16px] text-outline">chevron_right</span>
                <h1 class="font-headline-sm text-headline-sm font-bold tracking-tight text-on-surface truncate"
                    title="<?= FlashcardService::h($deck['title']) ?>">
                    <?= FlashcardService::h($deck['title']) ?>
                </h1>
            </nav>

            <!-- ACTION BAR -->
            <div class="flex items-center justify-between gap-3 px-4 py-2.5 rounded-lg bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                <div class="flex items-center gap-3 min-w-0 text-sm text-on-surface-variant">
                    <span class="inline-flex items-center gap-1 font-semibold text-on-surface whitespace-nowrap">
                        <span class="material-symbols-outlined text-primary text-[18px]">style</span>
                        <span id="deck-card-count-label"><?= $deck['card_count'] ?> <?= $deck['card_count'] === 1 ? 'Card' : 'Cards' ?></span>
                    </span>
                    <span class="text-outline">•</span>
                    <span id="deck-due-label" class="text-primary font-semibold whitespace-nowrap"><span id="deck-due-count"><?= (int) $deck['due_count'] ?></span> Due<span class="hidden sm:inline"> Now</span></span>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" id="toggle-archive-btn"
                            title="View archive" aria-label="View archive" aria-pressed="false"
                            class="inline-flex items-center justify-center gap-2 px-2.5 lg:px-3.5 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-sm transition-colors">
                        <span id="toggle-archive-icon" class="material-symbols-outlined text-[19px]">inventory_2</span>
                        <span id="toggle-archive-label" class="hidden lg:inline">Archive</span>
                        <span id="archived-count-label" class="text-xs px-1.5 py-0.5 rounded-full bg-surface-container-highest"><?= (int) $deck['archived_count'] ?></span>
                    </button>
                    <a href="review.php?deck_id=<?= $deck['id'] ?>"
                        id="start-review-btn"
                        title="Start Review" aria-label="Start Review"
                        class="<?= (int) $deck['due_count'] > 0 ? '' : 'hidden ' ?>inline-flex items-center justify-center gap-2 px-2.5 lg:px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm shadow-sm transition-all">
                        <span class="material-symbols-outlined text-[19px]">play_arrow</span>
                        <span class="hidden lg:inline">Start Review</span>
                    </a>

                    <button type="button" id="open-add-card-btn"
                            title="Create Card" aria-label="Create Card"
                            class="inline-flex items-center justify-center gap-2 px-2.5 lg:px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary font-semibold text-sm transition-all shadow-xs">
                        <span class="material-symbols-outlined text-[19px]">add</span>
                        <span class="hidden lg:inline">Create Card</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ARCHIVE NOTICE -->
        <div id="archive-banner" class="hidden flex items-center gap-2 px-4 py-2.5 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-sm">
            <span class="material-symbols-outlined text-[18px]">info</span>
            <span>Archived cards are permanently deleted after <?= ArchiveService::RETENTION_DAYS ?> days. Restore a card to keep it.</span>
        </div>

        <!-- MAIN WORKSPACE: view-only grid + desktop-only card editor -->
        <!-- BULK ACTIONS PANEL -->
        <div id="bulk-panel">
            <div class="flex items-center gap-3 font-semibold text-sm text-on-surface">                
                <!-- Explicit Select All trigger (hidden by default) -->
                <label id="bulk-select-wrapper" class="hidden flex items-center gap-2 cursor-pointer select-none text-primary hover:text-primary-container transition-colors">
                    <input type="checkbox" id="bulk-select-all" class="mp-check">
                    <span>Select all</span>
                </label>
                
                <!-- Visual Divider (hidden by default) -->
                <div id="bulk-select-separator" class="hidden w-px h-4 bg-outline-variant/60"></div>

                <!-- Status Tracker -->
                <div class="flex items-center gap-2 text-on-surface-variant">
                    <span class="material-symbols-outlined text-[20px]">checklist</span>
                    <span id="bulk-status">0 selected</span>
                </div>
                <button type="button" id="bulk-done-btn" hidden
                    class="mp-btn px-2 py-1 bg-transparent hover:bg-surface-container-high text-primary transition-colors"
                    title="Clear selection">Clear
                </button>
            </div>
            
            <div class="flex items-center gap-2">
                <button type="button" id="bulk-practice-btn" class="mp-btn mp-btn-icon lg:mp-btn-text bg-transparent hover:bg-surface-container-high active:bg-surface-container-highest text-on-surface-variant hover:text-on-surface disabled:opacity-50 transition-colors" title="Practice selected cards (nothing is saved)">
                    <span class="material-symbols-outlined text-[18px]">play_arrow</span>
                    <span class="hidden lg:inline">Practice</span>
                </button>
                <button type="button" id="bulk-duplicate-btn" class="mp-btn mp-btn-icon lg:mp-btn-text bg-transparent hover:bg-surface-container-high active:bg-surface-container-highest text-on-surface-variant hover:text-on-surface disabled:opacity-50 transition-colors" title="Duplicate selected">
                    <span class="material-symbols-outlined text-[18px]">content_copy</span>
                    <span class="hidden lg:inline">Duplicate</span>
                </button>
                <button type="button" id="bulk-move-btn" class="mp-btn mp-btn-icon lg:mp-btn-text bg-transparent hover:bg-surface-container-high active:bg-surface-container-highest text-on-surface-variant hover:text-on-surface disabled:opacity-50 transition-colors" title="Move to deck...">
                    <span class="material-symbols-outlined text-[18px]">drive_file_move</span>
                    <span class="hidden lg:inline">Move...</span>
                </button>
                <button type="button" id="bulk-archive-btn" class="mp-btn mp-btn-icon lg:mp-btn-text bg-transparent hover:bg-surface-container-high active:bg-surface-container-highest text-on-surface-variant hover:text-on-surface disabled:opacity-50 transition-colors" title="Archive selected">
                    <span class="material-symbols-outlined text-[18px]">inventory_2</span>
                    <span class="hidden lg:inline">Archive</span>
                </button>
                <button type="button" id="bulk-reset-btn" class="mp-btn mp-btn-icon lg:mp-btn-text bg-transparent hover:bg-surface-container-high active:bg-surface-container-highest text-on-surface-variant hover:text-on-surface disabled:opacity-50 transition-colors" title="Reset progress">
                    <span class="material-symbols-outlined text-[18px]">restart_alt</span>
                    <span class="hidden lg:inline">Reset</span>
                </button>
                <button type="button" id="bulk-restore-btn" hidden class="mp-btn mp-btn-icon lg:mp-btn-text bg-transparent hover:bg-surface-container-high active:bg-surface-container-highest text-on-surface-variant hover:text-on-surface disabled:opacity-50 transition-colors" title="Restore selected">
                    <span class="material-symbols-outlined text-[18px]">restore_from_trash</span>
                    <span class="hidden lg:inline">Restore</span>
                </button>
                <button type="button" id="bulk-delete-btn" hidden class="mp-btn mp-btn-icon lg:mp-btn-text bg-transparent hover:bg-rose-50 active:bg-rose-100 text-rose-700 disabled:opacity-50 transition-colors" title="Delete permanently">
                    <span class="material-symbols-outlined text-[18px]">delete_forever</span>
                    <span class="hidden lg:inline">Delete</span>
                </button>
            </div>
        </div>         


        <div id="mp-workspace" class="mp-workspace editor-collapsed">

            <!-- LEFT: VIEW-ONLY TABULATOR GRID -->
            <div id="table-wrapper" class="mp-pane mp-table-pane bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                <div id="no-cards-empty-state" class="flex-1 p-12 flex-col items-center justify-center gap-3 text-center bg-surface-container-low/20 <?= empty($flashcards) ? 'flex' : 'hidden' ?>">
                    <h3 id="empty-state-title" class="font-bold text-on-surface">No flashcards yet</h3>
                    <p id="empty-state-text" class="text-on-surface-variant text-sm">Click "Create Card" to add your first card.</p>
                </div>

                <div class="mp-grid-row">
                    <div id="flashcards-tabulator-grid" class="mp-grid-fill w-full <?= empty($flashcards) ? 'hidden' : '' ?>"></div>
                    <nav id="grid-jump-rail" class="mp-rail" aria-label="Jump to card" hidden></nav>
                </div>
                <!-- Lazy-load footer: progress + wayfinding -->
                <div id="grid-footer" class="mp-footer" <?= empty($flashcards) ? 'hidden' : '' ?>>
                    <div class="mp-footer-info">
                        <span id="grid-range-label" class="text-xs font-semibold text-on-surface-variant">Showing cards…</span>
                        <div class="mp-progress"><span id="grid-progress-bar" style="width:0%"></span></div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: CARD EDITOR (desktop only, hidden below 1024px via CSS) -->
            <aside id="card-editor-sidebar" class="mp-editor mp-pane mp-shell mp-collapsed" aria-label="Card editor">
                <div class="mp-shell-inner bg-surface-container-lowest w-full flex-1">

                    <div class="mp-head">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="material-symbols-outlined text-[24px]">edit_note</span>
                            <div class="min-w-0">
                                <h3 id="sidebar-title" class="text-[15px] font-bold leading-tight truncate">Card Editor</h3>
                                <p id="sidebar-position" class="text-[11px] text-white/80 leading-tight truncate">Use ↑ ↓ to browse</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button type="button" id="sidebar-prev-btn" class="mp-head-btn" title="Previous card (↑)" aria-label="Previous card">
                                <span class="material-symbols-outlined text-[20px]">keyboard_arrow_up</span>
                            </button>
                            <button type="button" id="sidebar-next-btn" class="mp-head-btn" title="Next card (↓)" aria-label="Next card">
                                <span class="material-symbols-outlined text-[20px]">keyboard_arrow_down</span>
                            </button>
                            <button type="button" id="toggle-sidebar-collapse" class="mp-head-btn" title="Collapse editor" aria-label="Collapse editor">
                                <span id="toggle-sidebar-icon" class="material-symbols-outlined text-[20px]">chevron_right</span>
                            </button>
                        </div>
                    </div>

                    <div id="sidebar-content-wrapper" class="flex flex-col flex-1 min-h-0">
                        <form id="sidebar-card-form" class="flex flex-col flex-1 min-h-0" novalidate>
                            <div class="mp-body">
                                <input type="hidden" id="sidebar-card-id" value="">

                                <div class="flex flex-col gap-1.5">
                                    <label for="sidebar-card-front" class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Front Content</label>
                                    <textarea id="sidebar-card-front" rows="4" maxlength="5000" placeholder="Select a card to edit..." class="mp-field w-full p-3 rounded-xl border border-outline-variant/50 bg-surface text-on-surface text-base font-medium resize-y transition-colors"></textarea>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="sidebar-card-back" class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Back Content</label>
                                    <textarea id="sidebar-card-back" rows="4" maxlength="5000" placeholder="Select a card to edit..." class="mp-field w-full p-3 rounded-xl border border-outline-variant/50 bg-surface text-on-surface text-base font-medium resize-y transition-colors"></textarea>
                                </div>

                                <div class="flex flex-col gap-1.5">
                                    <label for="sidebar-card-due" class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Next Review Date (Due)</label>
                                    <input type="datetime-local" id="sidebar-card-due" class="mp-field w-full p-2.5 rounded-xl border border-outline-variant/50 bg-surface text-on-surface text-sm transition-colors">
                                </div>

                                <p id="sidebar-card-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>

                                <div id="sidebar-card-details" class="flex flex-col gap-2 pt-3 border-t border-outline-variant/20 text-xs text-on-surface-variant">
                                    <span class="font-bold uppercase tracking-wider text-[10px] text-on-surface">Card Details</span>
                                    <div class="grid grid-cols-2 gap-2 bg-surface-container-low p-3 rounded-xl border border-outline-variant/20">
                                        <div><span class="text-outline">Last Reviewed:</span> <span id="det-last-reviewed" class="font-semibold text-on-surface">Never</span></div>
                                        <div><span class="text-outline">Accuracy:</span> <span id="det-accuracy" class="font-semibold text-on-surface">—</span></div>
                                        <div><span class="text-outline">Speed:</span> <span id="det-speed" class="font-semibold text-on-surface">—</span></div>
                                        <div><span class="text-outline">Interval:</span> <span id="det-interval" class="font-semibold text-on-surface">—</span></div>
                                        <div><span class="text-outline">Reviews:</span> <span id="det-reviews" class="font-semibold text-on-surface">0</span></div>
                                        <div><span class="text-outline">Lapses:</span> <span id="det-lapses" class="font-semibold text-on-surface">0</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="mp-foot">
                                <button type="submit" id="sidebar-save-btn" class="mp-btn mp-btn-gradient disabled:opacity-60" title="Save Changes (Ctrl+Return)">
                                    <span id="sidebar-save-label">Save Changes</span>
                                    <span class="mp-kbd-group" aria-hidden="true"><kbd class="mp-kbd mp-kbd-mod">Ctrl</kbd><kbd class="mp-kbd">Return</kbd></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Peek tab: shown only when the editor is collapsed (desktop) -->
        <button type="button" id="editor-peek-tab" class="mp-peek" title="Open card editor" aria-label="Open card editor">
            <span class="material-symbols-outlined text-[20px]">edit_note</span>
            <span class="mp-peek-label">Card Editor</span>
            <span class="material-symbols-outlined text-[18px]">chevron_left</span>
        </button>
    </div>
</div>

<!-- CARD MODAL (mobile + tablet) -->
<dialog id="card-editor-modal" class="mp-modal">
    <div class="mp-shell">
        <div class="mp-shell-inner bg-surface-container-lowest">
            <div class="mp-head">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="material-symbols-outlined text-[24px]">style</span>
                    <h3 id="modal-title" class="text-[17px] font-bold truncate">View / Edit Flashcard</h3>
                </div>
                <button type="button" id="close-modal-btn" class="mp-head-btn" title="Close" aria-label="Close">
                    <span class="material-symbols-outlined text-[22px]">close</span>
                </button>
            </div>

            <form id="modal-card-form" class="flex flex-col flex-1 min-h-0" novalidate>
                <div class="mp-body">
                    <input type="hidden" id="modal-card-id" value="">

                    <div class="flex flex-col gap-1.5">
                        <label for="modal-card-front" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Front Content</label>
                        <textarea id="modal-card-front" rows="4" maxlength="5000" placeholder="Type front side content..." class="mp-field w-full p-3 rounded-xl border border-outline-variant/50 bg-surface text-on-surface text-base font-medium resize-y transition-colors"></textarea>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="modal-card-back" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Back Content</label>
                        <textarea id="modal-card-back" rows="4" maxlength="5000" placeholder="Type back side content..." class="mp-field w-full p-3 rounded-xl border border-outline-variant/50 bg-surface text-on-surface text-base font-medium resize-y transition-colors"></textarea>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="modal-card-due" class="text-[12px] font-bold uppercase tracking-wider text-on-surface-variant">Next Review Date (Due)</label>
                        <input type="datetime-local" id="modal-card-due" class="mp-field w-full p-2.5 rounded-xl border border-outline-variant/50 bg-surface text-on-surface text-sm transition-colors">
                    </div>

                    <p id="modal-card-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
                </div>

                <div class="mp-foot">
                    <button type="button" id="modal-cancel-btn" data-close-dialog class="mp-btn mp-btn-ghost">Cancel</button>
                    <button type="submit" id="modal-save-btn" class="mp-btn mp-btn-gradient">
                        <span class="material-symbols-outlined text-[18px]">save</span>
                        <span id="modal-save-label">Save</span>
                        <span class="mp-kbd-group" aria-hidden="true"><kbd class="mp-kbd mp-kbd-mod">Ctrl</kbd><kbd class="mp-kbd">Return</kbd></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</dialog>

<!-- DELETE CONFIRMATION DIALOG -->
<dialog id="delete-card-dialog" class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 m-0 rounded-lg p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
    <div class="p-6 flex flex-col gap-4">
        <h3 class="font-headline-sm text-[18px] font-bold text-rose-700 flex items-center gap-2">
            <span class="material-symbols-outlined text-[22px]">warning</span>
            <span>Delete permanently?</span>
        </h3>
        <p id="delete-card-message" class="text-on-surface-variant text-[14px]">
            This card will be permanently deleted. This action cannot be undone.
        </p>
        <p id="delete-card-error" class="hidden text-rose-700 text-[13px]" role="alert"></p>
        <div class="flex justify-end gap-2.5">
            <button type="button" data-close-dialog class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface font-semibold text-[14px] transition-colors">Cancel</button>
            <button type="button" id="delete-card-confirm" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-[14px] transition-colors disabled:opacity-60">Delete Card</button>
        </div>
    </div>
</dialog>

<!-- BULK MOVE DIALOG -->
<dialog id="move-cards-dialog" class="fixed inset-0 m-auto rounded-xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
    <div class="p-5 flex flex-col gap-4">
        <h3 class="font-bold text-[16px] text-on-surface">Move Cards</h3>
        <select id="move-deck-select" class="mp-field w-full p-2.5 rounded-lg border border-outline-variant/50 bg-surface text-on-surface text-sm"></select>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-close-dialog class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-sm font-semibold">Cancel</button>
            <button type="button" id="confirm-move-btn" class="px-4 py-2 rounded-lg bg-primary hover:bg-primary-container text-on-primary text-sm font-semibold">Move</button>
        </div>
    </div>
</dialog>

<!-- BULK RESET DIALOG -->
<dialog id="reset-cards-dialog" class="fixed inset-0 m-auto rounded-xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-xl backdrop:bg-black/40">
    <div class="p-5 flex flex-col gap-4">
        <h3 class="font-bold text-[16px] text-rose-700">Reset Progress?</h3>
        <p class="text-sm text-on-surface-variant">This will reset scheduling and history for the selected cards. They will be due immediately. This cannot be undone.</p>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" data-close-dialog class="px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-sm font-semibold">Cancel</button>
            <button type="button" id="confirm-reset-btn" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold">Reset Progress</button>
        </div>
    </div>
</dialog>