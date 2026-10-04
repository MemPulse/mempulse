/**
 * MemPulse Deck Flashcards Module
 * Handles flashcard addition, editing, deletion, and DOM updates via AJAX.
 */
document.addEventListener("DOMContentLoaded", () => {
    const config = window.MemPulseDeckData || {};
    const CSRF_TOKEN = config.csrfToken || '';
    const DECK_ID = config.deckId || null;

    if (!DECK_ID) return;

    // ---------------------------------------------------------------
    // DOM Elements
    // ---------------------------------------------------------------
    const cardModalDialog = document.getElementById('card-modal-dialog');
    const cardModalForm = document.getElementById('card-modal-form');
    const cardModalTitle = document.getElementById('card-modal-title');
    const cardModalId = document.getElementById('card-modal-id');
    const cardModalFront = document.getElementById('card-modal-front');
    const cardModalBack = document.getElementById('card-modal-back');
    const cardModalError = document.getElementById('card-modal-error');
    const cardModalSave = document.getElementById('card-modal-save');

    const deleteCardDialog = document.getElementById('delete-card-dialog');
    const deleteCardError = document.getElementById('delete-card-error');
    const deleteCardConfirm = document.getElementById('delete-card-confirm');

    const openAddCardBtn = document.getElementById('open-add-card-btn');
    const listContainer = document.getElementById('flashcards-list-container');
    const emptyState = document.getElementById('no-cards-empty-state');
    const cardCountLabel = document.getElementById('deck-card-count-label');

    let targetDeleteCardId = null;

    // ---------------------------------------------------------------
    // Helper Functions
    // ---------------------------------------------------------------
    async function sendCardAction(payload) {
        const res = await fetch('../api/flashcard-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ...payload, csrf_token: CSRF_TOKEN, deck_id: DECK_ID })
        });
        let data = {};
        try { data = await res.json(); } catch (_) { }
        if (!res.ok || !data.ok) {
            throw new Error(data.error || 'Request failed (HTTP ' + res.status + ').');
        }
        return data;
    }

    function updateCardCountLabel() {
        if (!cardCountLabel) return;
        const totalCards = document.querySelectorAll('[data-card-item]').length;
        cardCountLabel.textContent = totalCards + (totalCards === 1 ? ' Card' : ' Cards');

        if (totalCards === 0 && !emptyState) {
            window.location.reload();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Close Dialog Buttons
    document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const dlg = btn.closest('dialog');
            if (dlg) dlg.close();
        });
    });

    // ---------------------------------------------------------------
    // 1. Add / Edit Card Modal Logic
    // ---------------------------------------------------------------
    function openCardModal(cardId = null, front = '', back = '') {
        if (!cardModalDialog) return;

        if (cardModalError) cardModalError.classList.add('hidden');
        if (cardModalId) cardModalId.value = cardId || '';
        if (cardModalFront) cardModalFront.value = front;
        if (cardModalBack) cardModalBack.value = back;

        if (cardModalTitle) {
            cardModalTitle.textContent = cardId ? 'Edit Flashcard' : 'Add Flashcard';
        }

        cardModalDialog.showModal();
        if (cardModalFront) cardModalFront.focus();
    }

    if (openAddCardBtn) {
        openAddCardBtn.addEventListener('click', () => openCardModal());
    }

    // Ctrl + Enter Keyboard Shortcut
    [cardModalFront, cardModalBack].forEach((field) => {
        if (field) {
            field.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    if (cardModalForm) cardModalForm.requestSubmit();
                }
            });
        }
    });

    // Form Submit (Add or Update)
    if (cardModalForm) {
        cardModalForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const cardId = cardModalId ? cardModalId.value.trim() : '';
            const front = cardModalFront ? cardModalFront.value.trim() : '';
            const back = cardModalBack ? cardModalBack.value.trim() : '';

            if (front === '' || back === '') {
                if (cardModalError) {
                    cardModalError.textContent = 'Both the front and back of the card are required.';
                    cardModalError.classList.remove('hidden');
                }
                return;
            }

            if (cardModalSave) cardModalSave.disabled = true;

            try {
                const action = cardId ? 'update' : 'add';
                const res = await sendCardAction({ action, card_id: cardId, front, back });

                if (action === 'add') {
                    // Dynamically append new card item to list
                    if (emptyState) emptyState.remove();

                    const newCardHTML = `
                        <div data-card-item="${res.card.id}" class="p-5 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 hover:border-primary/50 transition-colors">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 flex-1 w-full">
                                <div>
                                    <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Front</span>
                                    <p class="text-on-surface font-medium mt-1 whitespace-pre-wrap" data-card-front="${res.card.id}">${escapeHtml(res.card.front)}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Back</span>
                                    <p class="text-on-surface-variant mt-1 whitespace-pre-wrap" data-card-back="${res.card.id}">${escapeHtml(res.card.back)}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-center">
                                <button type="button" data-edit-card="${res.card.id}" class="p-2 rounded-xl text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="Edit Card">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </button>
                                <button type="button" data-delete-card="${res.card.id}" class="p-2 rounded-xl text-rose-700 hover:bg-rose-50 transition-colors" title="Delete Card">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </div>
                        </div>`;

                    if (listContainer) {
                        listContainer.insertAdjacentHTML('afterbegin', newCardHTML);
                    } else {
                        window.location.reload();
                    }
                } else {
                    // Update existing DOM elements
                    const frontEl = document.querySelector(`[data-card-front="${cardId}"]`);
                    const backEl = document.querySelector(`[data-card-back="${cardId}"]`);
                    if (frontEl) frontEl.textContent = res.card.front;
                    if (backEl) backEl.textContent = res.card.back;
                }

                if (cardModalDialog) cardModalDialog.close();
                updateCardCountLabel();

            } catch (err) {
                if (cardModalError) {
                    cardModalError.textContent = err.message;
                    cardModalError.classList.remove('hidden');
                }
            } finally {
                if (cardModalSave) cardModalSave.disabled = false;
            }
        });
    }

    // Delegated Event Handler for Edit Buttons
    document.addEventListener('click', (e) => {
        const editBtn = e.target.closest('[data-edit-card]');
        if (!editBtn) return;

        const cardId = editBtn.getAttribute('data-edit-card');
        const frontEl = document.querySelector(`[data-card-front="${cardId}"]`);
        const backEl = document.querySelector(`[data-card-back="${cardId}"]`);

        const front = frontEl ? frontEl.textContent : '';
        const back = backEl ? backEl.textContent : '';

        openCardModal(cardId, front, back);
    });

    // ---------------------------------------------------------------
    // 2. Delete Card Logic
    // ---------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const deleteBtn = e.target.closest('[data-delete-card]');
        if (!deleteBtn) return;

        targetDeleteCardId = deleteBtn.getAttribute('data-delete-card');
        if (deleteCardError) deleteCardError.classList.add('hidden');
        if (deleteCardDialog) deleteCardDialog.showModal();
    });

    if (deleteCardConfirm) {
        deleteCardConfirm.addEventListener('click', async () => {
            if (!targetDeleteCardId) return;

            deleteCardConfirm.disabled = true;
            try {
                await sendCardAction({ action: 'delete', card_id: targetDeleteCardId });

                const itemEl = document.querySelector(`[data-card-item="${targetDeleteCardId}"]`);
                if (itemEl) {
                    itemEl.style.transition = 'all 0.3s ease';
                    itemEl.style.opacity = '0';
                    itemEl.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        itemEl.remove();
                        updateCardCountLabel();
                    }, 300);
                }

                if (deleteCardDialog) deleteCardDialog.close();

            } catch (err) {
                if (deleteCardError) {
                    deleteCardError.textContent = err.message;
                    deleteCardError.classList.remove('hidden');
                }
            } finally {
                deleteCardConfirm.disabled = false;
            }
        });
    }
});