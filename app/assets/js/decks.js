/**
 * MemPulse Decks Module
 * Handles view toggles, kebab menu dropdowns, modal dialogs, and AJAX actions.
 */
document.addEventListener("DOMContentLoaded", () => {
    const config = window.MemPulseDeckData || {};
    const CSRF_TOKEN = config.csrfToken || '';

    // ---------------------------------------------------------------
    // 1. View toggle (grid vs list), remembered in localStorage
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
        try { localStorage.setItem(VIEW_KEY, mode); } catch (_) { }
    }

    if (viewGridBtn && viewListBtn && gridView && listView) {
        let saved = null;
        try { saved = localStorage.getItem(VIEW_KEY); } catch (_) { }
        setView(saved === 'list' ? 'list' : 'grid');

        viewGridBtn.addEventListener('click', () => { setView('grid'); saveView('grid'); });
        viewListBtn.addEventListener('click', () => { setView('list'); saveView('list'); });
    }

    // ---------------------------------------------------------------
    // 2. Deck Kebab Menu & Modals Logic
    // ---------------------------------------------------------------
    const deckMenu = document.getElementById('deck-menu');
    const renameDialog = document.getElementById('rename-dialog');
    const renameForm = document.getElementById('rename-form');
    const renameInput = document.getElementById('rename-input');
    const renameError = document.getElementById('rename-error');
    const renameSave = document.getElementById('rename-save');

    const archiveDialog = document.getElementById('archive-dialog') || document.getElementById('delete-dialog');
    const archiveName = document.getElementById('archive-name') || document.getElementById('delete-name');
    const archiveError = document.getElementById('archive-error') || document.getElementById('delete-error');
    const archiveConfirm = document.getElementById('archive-confirm') || document.getElementById('delete-confirm');
    const countBadge = document.getElementById('deck-count-badge');

    let activeDeckId = null;
    let menuButton = null;

    // Archived Decks Modal
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

    // Global listeners for Kebab Menu
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
        const res = await fetch('../api/deck-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...payload, csrf_token: CSRF_TOKEN })
        });
        let data = {};
        try { data = await res.json(); } catch (_) { }
        if (!res.ok || !data.ok) {
            const err = new Error(data.error || 'Request failed (HTTP ' + res.status + ').');
            err.suggestion = data.suggestion || null;
            throw err;
        }
        return data;
    }

    // Handle Kebab dropdown clicks
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

    // Rename Form Handler
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

    // Archive Confirm Handler
    if (archiveConfirm) {
        archiveConfirm.addEventListener('click', async () => {
            archiveConfirm.disabled = true;
            try {
                const action = archiveConfirm.id === 'delete-confirm' ? 'delete' : 'archive';
                await sendDeckAction({ action, deck_id: activeDeckId });

                if (archiveDialog) archiveDialog.close();

                // Reload page so PHP re-fetches archived decks list & updates the modal
                window.location.reload();
            } catch (err) {
                if (archiveError) {
                    archiveError.textContent = err.message;
                    archiveError.classList.remove('hidden');
                }
                archiveConfirm.disabled = false;
            }
        });
    }

    // ---------------------------------------------------------------
    // 3. Create Deck Dialog Handler
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
    // 4. Review Button Handler
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
    // 5. Add Card Modal Handler
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

    // Auto-open modals via URL params
    if (config.createRequested) {
        openCreateDialog();
    } else if (config.addCardRequested) {
        openCardDialog(config.requestedDeckId);
    }

    // ---------------------------------------------------------------
    // 6. Custom Deck Reordering via LocalStorage & SortableJS
    // ---------------------------------------------------------------
    const ORDER_KEY = 'mempulse_deck_order';

    function applySavedDeckOrder(container) {
        if (!container) return;

        let savedOrder = null;
        try {
            savedOrder = JSON.parse(localStorage.getItem(ORDER_KEY));
        } catch (_) { }

        if (!Array.isArray(savedOrder) || savedOrder.length === 0) return;

        const items = Array.from(container.querySelectorAll('.deck-card[data-deck-id]'));
        const itemMap = new Map();
        items.forEach(item => itemMap.set(String(item.dataset.deckId), item));

        savedOrder.forEach(deckId => {
            const el = itemMap.get(String(deckId));
            if (el) {
                container.appendChild(el);
                itemMap.delete(String(deckId));
            }
        });

        const createBtn = container.querySelector('#new-deck-tile, #new-deck-row');
        itemMap.forEach(el => {
            if (createBtn) {
                container.insertBefore(el, createBtn);
            } else {
                container.appendChild(el);
            }
        });

        if (createBtn) {
            container.appendChild(createBtn);
        }
    }

    function saveCurrentDeckOrder(container) {
        if (!container) return;
        const items = Array.from(container.querySelectorAll('.deck-card[data-deck-id]'));
        const order = items.map(el => Number(el.dataset.deckId));
        try {
            localStorage.setItem(ORDER_KEY, JSON.stringify(order));
        } catch (_) { }
    }

    // Initialize Drag & Drop
    const gridContainer = document.getElementById('decks-grid-view');
    const listContainer = document.getElementById('decks-list-view');

    applySavedDeckOrder(gridContainer);
    applySavedDeckOrder(listContainer);

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