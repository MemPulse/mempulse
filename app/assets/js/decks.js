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
    // Helper: Toast Notifications inside Archived Modal
    // ---------------------------------------------------------------
    const archivedToast = document.getElementById('archived-toast');
    const archivedToastMsg = document.getElementById('archived-toast-message');

    function showArchivedToast(message, isSuccess = true) {
        if (!archivedToast || !archivedToastMsg) return;
        archivedToastMsg.textContent = message;
        archivedToast.className = isSuccess
            ? "px-4 py-3 rounded-xl font-semibold text-sm transition-all flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200"
            : "px-4 py-3 rounded-xl font-semibold text-sm transition-all flex items-center justify-between bg-rose-50 text-rose-800 border border-rose-200";
        archivedToast.classList.remove('hidden');
    }

    // Trigger pulse highlight animation on Archived Decks button
    function triggerArchiveButtonPulse() {
        const btn = document.getElementById('open-archived-btn');
        if (!btn) return;

        btn.classList.add('animate-archive-pulse', 'bg-primary-container', 'text-on-primary-container', 'ring-2', 'ring-primary/40');
        setTimeout(() => {
            btn.classList.remove('animate-archive-pulse', 'bg-primary-container', 'text-on-primary-container', 'ring-2', 'ring-primary/40');
        }, 1500);
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

    const archivedDecksDialog = document.getElementById('archived-decks-dialog');
    const openArchivedBtn = document.getElementById('open-archived-btn');

    if (openArchivedBtn && archivedDecksDialog) {
        openArchivedBtn.addEventListener('click', () => {
            archivedDecksDialog.showModal();
        });
    }

    // Restore Deck Action Handler
    document.addEventListener('click', async (e) => {
        const restoreBtn = e.target.closest('[data-restore-deck]');
        if (!restoreBtn) return;

        const deckId = restoreBtn.getAttribute('data-restore-deck');
        const itemEl = document.querySelector(`[data-archived-item="${deckId}"]`);
        const deckTitleEl = document.querySelector(`[data-archived-title="${deckId}"]`);
        const deckTitle = deckTitleEl ? deckTitleEl.textContent.trim() : 'Deck';

        restoreBtn.disabled = true;
        restoreBtn.textContent = "Restoring...";

        try {
            await sendDeckAction({ action: 'restore', deck_id: deckId });

            if (itemEl) itemEl.remove();

            const listContainer = document.getElementById('archived-decks-list-container');
            if (listContainer && listContainer.children.length === 0) {
                listContainer.innerHTML = '<p id="no-archived-msg" class="text-on-surface-variant text-center py-6 text-sm">No archived decks found.</p>';
            }

            showArchivedToast(`"${deckTitle}" has been restored to active decks.`, true);
            window.needMainReload = true;
        } catch (err) {
            showArchivedToast("Failed to restore deck: " + err.message, false);
            restoreBtn.disabled = false;
            restoreBtn.textContent = "Restore";
        }
    });

    // Permanent Delete Handler for Archived Decks
    const permDeleteDialog = document.getElementById('permanent-delete-dialog');
    const permDeleteName = document.getElementById('perm-delete-name');
    const permDeleteError = document.getElementById('perm-delete-error');
    const permDeleteConfirm = document.getElementById('perm-delete-confirm');
    let targetDeleteDeckId = null;

    document.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('[data-delete-archived]');
        if (!deleteBtn) return;

        targetDeleteDeckId = deleteBtn.getAttribute('data-delete-archived');
        const deckTitleEl = document.querySelector(`[data-archived-title="${targetDeleteDeckId}"]`);
        const deckTitle = deckTitleEl ? deckTitleEl.textContent.trim() : 'Deck';

        if (permDeleteName) permDeleteName.textContent = `"${deckTitle}"`;
        if (permDeleteError) permDeleteError.classList.add('hidden');
        if (permDeleteDialog) permDeleteDialog.showModal();
    });

    if (permDeleteConfirm) {
        permDeleteConfirm.addEventListener('click', async () => {
            if (!targetDeleteDeckId) return;

            permDeleteConfirm.disabled = true;
            try {
                const deckTitleEl = document.querySelector(`[data-archived-title="${targetDeleteDeckId}"]`);
                const deckTitle = deckTitleEl ? deckTitleEl.textContent.trim() : 'Deck';

                await sendDeckAction({ action: 'permanent_delete', deck_id: targetDeleteDeckId });

                const itemEl = document.querySelector(`[data-archived-item="${targetDeleteDeckId}"]`);
                if (itemEl) itemEl.remove();

                const listContainer = document.getElementById('archived-decks-list-container');
                if (listContainer && listContainer.children.length === 0) {
                    listContainer.innerHTML = '<p id="no-archived-msg" class="text-on-surface-variant text-center py-6 text-sm">No archived decks found.</p>';
                }

                if (permDeleteDialog) permDeleteDialog.close();
                showArchivedToast(`"${deckTitle}" has been permanently deleted.`, false);
            } catch (err) {
                if (permDeleteError) {
                    permDeleteError.textContent = err.message;
                    permDeleteError.classList.remove('hidden');
                }
            } finally {
                permDeleteConfirm.disabled = false;
            }
        });
    }

    if (archivedDecksDialog) {
        archivedDecksDialog.addEventListener('close', () => {
            if (window.needMainReload) {
                window.location.reload();
            }
        });
    }

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
            } else if (action === 'edit-banner' && bannerDialog) {
                if (bannerError) bannerError.classList.add('hidden');

                // Highlight current selection if any
                document.querySelectorAll('.banner-option').forEach(b => {
                    b.classList.remove('border-primary');
                    b.classList.add('border-transparent');
                });

                bannerDialog.showModal();
            }
        });
    }

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

    // ---------------------------------------------------------------
    // 3. Inline Archiving Action (Animates Card Out & Updates Archive List)
    // ---------------------------------------------------------------
    if (archiveConfirm) {
        archiveConfirm.addEventListener('click', async () => {
            archiveConfirm.disabled = true;
            try {
                const action = archiveConfirm.id === 'delete-confirm' ? 'delete' : 'archive';
                const deckTitle = getDeckTitle(activeDeckId) || 'Deck';

                // Get metadata from active card element
                const activeCard = document.querySelector(`[data-deck-card="${activeDeckId}"]`);
                let cardCount = '0';
                let accuracy = 'N/A';

                if (activeCard) {
                    const cardCountEl = activeCard.querySelector('.font-semibold.text-on-surface');
                    if (cardCountEl) cardCount = cardCountEl.textContent.trim();

                    // Safely find the accuracy badge element without invalid query selector syntax
                    const accEl = activeCard.querySelector('[class*="badge"]') ||
                        Array.from(activeCard.querySelectorAll('span')).find(el => el.textContent.includes('%'));
                    if (accEl) accuracy = accEl.textContent.trim();
                }

                await sendDeckAction({ action, deck_id: activeDeckId });

                if (archiveDialog) archiveDialog.close();

                // Fade out card elements in grid & list view
                const cardsToRemove = document.querySelectorAll(`[data-deck-card="${activeDeckId}"]`);
                cardsToRemove.forEach(card => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 300);
                });

                // Dynamically insert into Archived Decks Modal
                const listContainer = document.getElementById('archived-decks-list-container');
                if (listContainer) {
                    const noMsg = document.getElementById('no-archived-msg');
                    if (noMsg) noMsg.remove();

                    const today = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const newItemHTML = `
                        <div data-archived-item="${activeDeckId}" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-outline-variant/40 bg-surface gap-4 hover:border-primary/50 transition-colors">
                            <div>
                                <h4 class="font-bold text-on-surface text-[15px]" data-archived-title="${activeDeckId}">${deckTitle}</h4>
                                <div class="text-[13px] text-on-surface-variant mt-1.5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1">
                                    <span><strong>Cards:</strong> ${cardCount}</span>
                                    <span><strong>Accuracy:</strong> ${accuracy}</span>
                                    <span><strong>Archived:</strong> ${today}</span>
                                    <span><strong>Reviewed:</strong> Just now</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button type="button" data-restore-deck="${activeDeckId}" class="px-3.5 py-2 rounded-xl bg-primary-container text-on-primary-container hover:bg-primary hover:text-on-primary text-sm font-semibold transition-colors whitespace-nowrap shadow-sm">
                                    Restore
                                </button>
                                <button type="button" data-delete-archived="${activeDeckId}" class="px-3.5 py-2 rounded-xl border border-rose-200 text-rose-700 hover:bg-rose-50 text-sm font-semibold transition-colors whitespace-nowrap" title="Delete Permanently">
                                    Delete
                                </button>
                            </div>
                        </div>`;
                    listContainer.insertAdjacentHTML('afterbegin', newItemHTML);
                }

                // Trigger Archive Button Highlight Animation
                triggerArchiveButtonPulse();

                // Update remaining deck badge count
                setTimeout(() => {
                    const remaining = document.querySelectorAll('#decks-grid-view [data-deck-card]').length;
                    if (remaining === 0) {
                        window.location.reload();
                    } else if (countBadge) {
                        countBadge.textContent = remaining + (remaining === 1 ? ' Deck' : ' Decks');
                    }
                }, 350);

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
    // 4. Create Deck Dialog Handler
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
    // 5. Review Button Handler
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
    // 6. Add Card Modal Handler
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
    // 7. Custom Deck Position Management via LocalStorage
    // ---------------------------------------------------------------
    const ORDER_KEY = 'mempulse_deck_order';
    const gridContainer = document.getElementById('decks-grid-view');
    const listContainer = document.getElementById('decks-list-view');

    const movePosDialog = document.getElementById('move-pos-dialog');
    const movePosForm = document.getElementById('move-pos-form');
    const movePosName = document.getElementById('move-pos-deck-name');
    const movePosInput = document.getElementById('move-pos-input');
    const movePosHint = document.getElementById('move-pos-hint');

    function getDeckElements(container) {
        if (!container) return [];
        return Array.from(container.querySelectorAll('.deck-card[data-deck-id]'));
    }

    function applySavedDeckOrder(container) {
        if (!container) return;

        let savedOrder = null;
        try {
            savedOrder = JSON.parse(localStorage.getItem(ORDER_KEY));
        } catch (_) { }

        if (!Array.isArray(savedOrder) || savedOrder.length === 0) return;

        const items = getDeckElements(container);
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
            if (createBtn) container.insertBefore(el, createBtn);
            else container.appendChild(el);
        });

        if (createBtn) container.appendChild(createBtn);
    }

    function saveCurrentDeckOrder(container) {
        if (!container) return;
        const items = getDeckElements(container);
        const order = items.map(el => Number(el.dataset.deckId));
        try {
            localStorage.setItem(ORDER_KEY, JSON.stringify(order));
        } catch (_) { }
    }

    function moveDeckToPosition(targetDeckId, newPos) {
        [gridContainer, listContainer].forEach(container => {
            if (!container) return;
            const items = getDeckElements(container);
            const targetEl = container.querySelector(`.deck-card[data-deck-id="${targetDeckId}"]`);
            if (!targetEl || items.length === 0) return;

            const targetIdx = Math.max(0, Math.min(newPos - 1, items.length - 1));
            const createBtn = container.querySelector('#new-deck-tile, #new-deck-row');

            const remaining = items.filter(el => String(el.dataset.deckId) !== String(targetDeckId));
            remaining.splice(targetIdx, 0, targetEl);

            remaining.forEach(el => {
                if (createBtn) container.insertBefore(el, createBtn);
                else container.appendChild(el);
            });
            if (createBtn) container.appendChild(createBtn);

            saveCurrentDeckOrder(container);
        });
    }

    // Apply saved order on load
    applySavedDeckOrder(gridContainer);
    applySavedDeckOrder(listContainer);

    // Kebab Menu Handler for Move Position
    if (deckMenu) {
        deckMenu.addEventListener('click', (e) => {
            const item = e.target.closest('[data-menu-action="move-pos"]');
            if (!item || !activeDeckId) return;

            const deckTitle = getDeckTitle(activeDeckId) || 'Deck';
            const currentItems = getDeckElements(gridContainer || listContainer);
            const currentIdx = currentItems.findIndex(el => String(el.dataset.deckId) === String(activeDeckId));

            if (movePosName) movePosName.textContent = `"${deckTitle}"`;
            if (movePosInput) {
                movePosInput.value = currentIdx !== -1 ? currentIdx + 1 : 1;
                movePosInput.max = currentItems.length;
            }
            if (movePosHint) movePosHint.textContent = `Enter position between 1 and ${currentItems.length}.`;

            if (movePosDialog) movePosDialog.showModal();
        });
    }

    if (movePosForm) {
        movePosForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const newPos = parseInt(movePosInput ? movePosInput.value : '1', 10);
            if (!isNaN(newPos) && activeDeckId) {
                moveDeckToPosition(activeDeckId, newPos);
                if (movePosDialog) movePosDialog.close();
            }
        });
    }
});

// ---------------------------------------------------------------
// Edit Banner Modal Handler
// ---------------------------------------------------------------
const bannerDialog = document.getElementById('banner-dialog');
const bannerSave = document.getElementById('banner-save');
const bannerError = document.getElementById('banner-error');
let selectedBannerPath = null;

// Delegate banner image clicks inside the dialog
if (bannerDialog) {
    bannerDialog.addEventListener('click', (e) => {
        const optionBtn = e.target.closest('.banner-option');
        if (!optionBtn) return;

        document.querySelectorAll('.banner-option').forEach(b => {
            b.classList.remove('border-primary');
            b.classList.add('border-transparent');
        });

        optionBtn.classList.remove('border-transparent');
        optionBtn.classList.add('border-primary');
        selectedBannerPath = optionBtn.dataset.bannerPath;
    });
}

if (bannerSave) {
    bannerSave.addEventListener('click', async () => {
        if (selectedBannerPath === null) {
            if (bannerError) {
                bannerError.textContent = "Please select a banner image or default gradient.";
                bannerError.classList.remove('hidden');
            }
            return;
        }

        bannerSave.disabled = true;
        try {
            await sendDeckAction({
                action: 'update_cover',
                deck_id: activeDeckId,
                cover_image: selectedBannerPath
            });

            if (bannerDialog) bannerDialog.close();
            window.location.reload();
        } catch (err) {
            if (bannerError) {
                bannerError.textContent = err.message;
                bannerError.classList.remove('hidden');
            }
        } finally {
            bannerSave.disabled = false;
        }
    });
}