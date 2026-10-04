/**
 * MemPulse Deck Flashcards Module
 * Handles resizable table columns, side editor panel interactions,
 * card creation, editing, deletion, and DOM sync via AJAX.
 */
document.addEventListener("DOMContentLoaded", () => {
    const config = window.MemPulseDeckData || {};
    const CSRF_TOKEN = config.csrfToken || '';
    const DECK_ID = config.deckId || null;

    if (!DECK_ID) return;

    // ---------------------------------------------------------------
    // DOM Elements
    // ---------------------------------------------------------------
    const tableWrapper = document.getElementById('table-wrapper');
    const tableScrollContainer = document.getElementById('flashcard-table-scroll-container');
    const tableBody = document.getElementById('flashcards-table-body');
    const emptyState = document.getElementById('no-cards-empty-state');
    const cardCountLabel = document.getElementById('deck-card-count-label');

    // Panel Elements
    const editorPanel = document.getElementById('card-editor-panel');
    const editorPanelTitle = document.getElementById('editor-panel-title');
    const editorPanelForm = document.getElementById('editor-panel-form');
    const panelCardId = document.getElementById('panel-card-id');
    const panelCardFront = document.getElementById('panel-card-front');
    const panelCardBack = document.getElementById('panel-card-back');
    const panelCardDue = document.getElementById('panel-card-due');
    const panelMetadataGroup = document.getElementById('panel-metadata-group');
    const panelError = document.getElementById('editor-panel-error');
    const panelSaveBtn = document.getElementById('panel-save-btn');
    const panelResetBtn = document.getElementById('panel-reset-btn');

    // Buttons & Toggles
    const openAddCardBtn = document.getElementById('open-add-card-btn');
    const toggleEditorBtn = document.getElementById('toggle-editor-panel-btn');
    const closeEditorPanelBtn = document.getElementById('close-editor-panel-btn');

    // Delete Dialog Elements
    const deleteCardDialog = document.getElementById('delete-card-dialog');
    const deleteCardError = document.getElementById('delete-card-error');
    const deleteCardConfirm = document.getElementById('delete-card-confirm');

    let activeSelectedRow = null;
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
        const totalCards = tableBody ? tableBody.querySelectorAll('tr[data-card-item]').length : 0;
        cardCountLabel.textContent = totalCards + (totalCards === 1 ? ' Card' : ' Cards');

        if (totalCards === 0) {
            if (tableScrollContainer) tableScrollContainer.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
        } else {
            if (tableScrollContainer) tableScrollContainer.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function formatDateTimeForInput(dateString) {
        if (!dateString) return '';
        const d = new Date(dateString);
        if (isNaN(d.getTime())) return '';
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function formatDateTimeDisplay(dateString) {
        if (!dateString) return '';
        const d = new Date(dateString);
        if (isNaN(d.getTime())) return dateString;
        const pad = (n) => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    // Close Dialog Listeners
    document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const dlg = btn.closest('dialog');
            if (dlg) dlg.close();
        });
    });

    // ---------------------------------------------------------------
    // 1. Column Width Resizing Logic
    // ---------------------------------------------------------------
    function initResizableColumns() {
        const resizers = document.querySelectorAll('.resizer');
        resizers.forEach((resizer) => {
            let startX, startWidth, colHeader;

            resizer.addEventListener('pointerdown', (e) => {
                e.preventDefault();
                e.stopPropagation();

                colHeader = resizer.closest('th');
                startX = e.clientX;
                startWidth = colHeader.offsetWidth;

                resizer.classList.add('resizing');
                document.body.style.cursor = 'col-resize';

                const onPointerMove = (moveEvent) => {
                    const deltaX = moveEvent.clientX - startX;
                    const minWidth = parseInt(colHeader.style.minWidth) || 80;
                    const newWidth = Math.max(minWidth, startWidth + deltaX);
                    colHeader.style.width = `${newWidth}px`;
                };

                const onPointerUp = () => {
                    resizer.classList.remove('resizing');
                    document.body.style.cursor = '';
                    document.removeEventListener('pointermove', onPointerMove);
                    document.removeEventListener('pointerup', onPointerUp);
                };

                document.addEventListener('pointermove', onPointerMove);
                document.addEventListener('pointerup', onPointerUp);
            });
        });
    }
    initResizableColumns();

    // ---------------------------------------------------------------
    // 2. Editor Side Panel Operations (Create vs Edit Mode)
    // ---------------------------------------------------------------
    function openEditorPanel() {
        if (editorPanel) {
            editorPanel.classList.remove('collapsed');
        }
    }

    function toggleEditorPanel() {
        if (editorPanel) {
            editorPanel.classList.toggle('collapsed');
        }
    }

    function resetEditorToCreateMode() {
        if (activeSelectedRow) {
            activeSelectedRow.classList.remove('selected-row');
            activeSelectedRow = null;
        }

        if (editorPanelTitle) editorPanelTitle.textContent = 'Create Flashcard';
        if (panelCardId) panelCardId.value = '';
        if (panelCardFront) panelCardFront.value = '';
        if (panelCardBack) panelCardBack.value = '';
        if (panelCardDue) panelCardDue.value = formatDateTimeForInput(new Date());
        if (panelError) panelError.classList.add('hidden');
        if (panelMetadataGroup) panelMetadataGroup.classList.add('hidden');

        openEditorPanel();
        if (panelCardFront) panelCardFront.focus();
    }

    function populateEditorForEditMode(cardRow) {
        if (!cardRow) return;

        if (activeSelectedRow) {
            activeSelectedRow.classList.remove('selected-row');
        }
        activeSelectedRow = cardRow;
        activeSelectedRow.classList.add('selected-row');

        const cardId = cardRow.getAttribute('data-card-item');
        const frontEl = cardRow.querySelector(`[data-card-front="${cardId}"]`);
        const backEl = cardRow.querySelector(`[data-card-back="${cardId}"]`);
        const dueEl = cardRow.querySelector(`[data-card-due="${cardId}"]`);

        if (editorPanelTitle) editorPanelTitle.textContent = 'Edit Flashcard';
        if (panelCardId) panelCardId.value = cardId;
        if (panelCardFront) panelCardFront.value = frontEl ? frontEl.textContent : '';
        if (panelCardBack) panelCardBack.value = backEl ? backEl.textContent : '';
        if (panelCardDue) panelCardDue.value = dueEl ? formatDateTimeForInput(dueEl.textContent) : '';
        if (panelError) panelError.classList.add('hidden');

        // Populate metadata elements
        const cells = cardRow.querySelectorAll('td');
        if (cells.length >= 8) {
            document.getElementById('meta-interval').textContent = cells[3].textContent.trim();
            document.getElementById('meta-reviews').textContent = cells[4].textContent.trim();
            document.getElementById('meta-lapses').textContent = cells[5].textContent.trim();
            document.getElementById('meta-updated').textContent = cells[7].textContent.trim();
            if (panelMetadataGroup) panelMetadataGroup.classList.remove('hidden');
        }

        openEditorPanel();
    }

    // Toggle Buttons Events
    if (openAddCardBtn) openAddCardBtn.addEventListener('click', resetEditorToCreateMode);
    if (panelResetBtn) panelResetBtn.addEventListener('click', resetEditorToCreateMode);
    if (toggleEditorBtn) toggleEditorBtn.addEventListener('click', toggleEditorPanel);
    if (closeEditorPanelBtn) closeEditorPanelBtn.addEventListener('click', toggleEditorPanel);
    if (emptyState) emptyState.addEventListener('click', resetEditorToCreateMode);

    // Row Click Listener (Selecting Row Syncs to Editor)
    if (tableBody) {
        tableBody.addEventListener('click', (e) => {
            const row = e.target.closest('tr[data-card-item]');
            if (!row) return;

            // Avoid opening row edit if clicking delete button
            if (e.target.closest('[data-delete-card]')) return;

            populateEditorForEditMode(row);
        });
    }

    // Form Submit (Create or Update Card)
    if (editorPanelForm) {
        editorPanelForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const cardId = panelCardId ? panelCardId.value.trim() : '';
            const front = panelCardFront ? panelCardFront.value.trim() : '';
            const back = panelCardBack ? panelCardBack.value.trim() : '';
            const nextReviewAt = panelCardDue ? panelCardDue.value.trim() : '';

            if (front === '' || back === '') {
                if (panelError) {
                    panelError.textContent = 'Both Front and Back content are required.';
                    panelError.classList.remove('hidden');
                }
                return;
            }

            if (panelSaveBtn) panelSaveBtn.disabled = true;

            try {
                const action = cardId ? 'update' : 'add';
                const payload = { action, card_id: cardId, front, back, next_review_at: nextReviewAt };
                const res = await sendCardAction(payload);
                const card = res.card;

                if (action === 'add') {
                    // Prepend new table row
                    const newRowHTML = `
                        <tr data-card-item="${card.id}" class="hover:bg-surface-container-low/40 transition-colors group cursor-pointer">
                            <td class="p-4 align-top">
                                <p class="text-on-surface font-medium whitespace-pre-wrap leading-relaxed max-h-32 overflow-y-auto" data-card-front="${card.id}">${escapeHtml(card.front)}</p>
                            </td>
                            <td class="p-4 align-top">
                                <p class="text-on-surface-variant whitespace-pre-wrap leading-relaxed max-h-32 overflow-y-auto" data-card-back="${card.id}">${escapeHtml(card.back)}</p>
                            </td>
                            <td class="p-4 align-top text-on-surface font-medium text-[13px] whitespace-nowrap">
                                <span data-card-due="${card.id}">${formatDateTimeDisplay(card.next_review_at)}</span>
                            </td>
                            <td class="p-4 align-top text-on-surface-variant text-[13px] whitespace-nowrap">
                                <span>${card.interval_days} ${card.interval_days === 1 ? 'day' : 'days'}</span>
                            </td>
                            <td class="p-4 align-top text-on-surface-variant text-[13px] whitespace-nowrap">
                                <span>${card.review_count}</span>
                            </td>
                            <td class="p-4 align-top text-on-surface-variant text-[13px] whitespace-nowrap">
                                <span class="${card.lapses > 0 ? 'text-amber-600 font-semibold' : ''}">${card.lapses}</span>
                            </td>
                            <td class="p-4 align-top text-on-surface-variant/80 text-[12px] whitespace-nowrap">
                                <span>${formatDateTimeDisplay(card.created_at)}</span>
                            </td>
                            <td class="p-4 align-top text-on-surface-variant/80 text-[12px] whitespace-nowrap">
                                <span data-card-updated="${card.id}">${formatDateTimeDisplay(card.updated_at)}</span>
                            </td>
                            <td class="p-4 align-top text-center sticky right-0 bg-surface-container-lowest group-hover:bg-surface-container-low/40 transition-colors">
                                <div class="flex items-center justify-center gap-1">
                                    <button type="button" data-edit-card="${card.id}" class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="Edit in Panel">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </button>
                                    <button type="button" data-delete-card="${card.id}" class="p-1.5 rounded-lg text-rose-700 hover:bg-rose-50 transition-colors" title="Delete Card">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>`;

                    if (tableBody) {
                        tableBody.insertAdjacentHTML('afterbegin', newRowHTML);
                        const newRow = tableBody.querySelector(`tr[data-card-item="${card.id}"]`);
                        populateEditorForEditMode(newRow);
                    }
                } else {
                    // Update existing row cells
                    const frontEl = document.querySelector(`[data-card-front="${cardId}"]`);
                    const backEl = document.querySelector(`[data-card-back="${cardId}"]`);
                    const dueEl = document.querySelector(`[data-card-due="${cardId}"]`);
                    const updatedEl = document.querySelector(`[data-card-updated="${cardId}"]`);

                    if (frontEl) frontEl.textContent = card.front;
                    if (backEl) backEl.textContent = card.back;
                    if (dueEl) dueEl.textContent = formatDateTimeDisplay(card.next_review_at);
                    if (updatedEl) updatedEl.textContent = formatDateTimeDisplay(card.updated_at);
                    document.getElementById('meta-updated').textContent = formatDateTimeDisplay(card.updated_at);
                }

                updateCardCountLabel();

            } catch (err) {
                if (panelError) {
                    panelError.textContent = err.message;
                    panelError.classList.remove('hidden');
                }
            } finally {
                if (panelSaveBtn) panelSaveBtn.disabled = false;
            }
        });
    }

    // ---------------------------------------------------------------
    // 3. Delete Card Logic
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

                const itemEl = document.querySelector(`tr[data-card-item="${targetDeleteCardId}"]`);
                if (itemEl) {
                    if (activeSelectedRow === itemEl) {
                        resetEditorToCreateMode();
                    }
                    itemEl.style.transition = 'all 0.2s ease';
                    itemEl.style.opacity = '0';
                    setTimeout(() => {
                        itemEl.remove();
                        updateCardCountLabel();
                    }, 200);
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