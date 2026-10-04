/**
 * MemPulse Deck Flashcards Module (Tabulator + Modal Editor)
 */
document.addEventListener("DOMContentLoaded", () => {
    const config = window.MemPulseDeckData || {};
    const CSRF_TOKEN = config.csrfToken || '';
    const DECK_ID = config.deckId || null;
    const initialFlashcards = config.flashcards || [];

    if (!DECK_ID) return;

    // DOM Elements
    const gridContainer = document.getElementById('flashcards-tabulator-grid');
    const emptyState = document.getElementById('no-cards-empty-state');
    const cardCountLabel = document.getElementById('deck-card-count-label');
    const openAddCardBtn = document.getElementById('open-add-card-btn');

    // Modal Elements
    const cardModal = document.getElementById('card-editor-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalForm = document.getElementById('modal-card-form');
    const modalCardId = document.getElementById('modal-card-id');
    const modalCardFront = document.getElementById('modal-card-front');
    const modalCardBack = document.getElementById('modal-card-back');
    const modalCardDue = document.getElementById('modal-card-due');
    const modalError = document.getElementById('modal-card-error');
    const modalSaveBtn = document.getElementById('modal-save-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');

    // Delete Modal Elements
    const deleteCardDialog = document.getElementById('delete-card-dialog');
    const deleteCardError = document.getElementById('delete-card-error');
    const deleteCardConfirm = document.getElementById('delete-card-confirm');

    let targetDeleteCardId = null;
    let tabulatorTable = null;

    // Initialize HTML5 datetime-local minimum constraint (Today + Future days only)
    function setMinDueDateConstraint() {
        if (!modalCardDue) return;
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const minFormatted = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T00:00`;
        modalCardDue.min = minFormatted;
    }

    setMinDueDateConstraint();

    // API Helper with Database Connectivity Verification
    async function sendCardAction(payload) {
        try {
            const res = await fetch('../api/flashcard-actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...payload, csrf_token: CSRF_TOKEN, deck_id: DECK_ID })
            });
            let data = {};
            try { data = await res.json(); } catch (_) { }

            if (!res.ok || !data.ok) {
                throw new Error(data.error || `Database communication error (HTTP ${res.status}).`);
            }
            return data;
        } catch (error) {
            console.error("Database connection failure:", error);
            throw error;
        }
    }

    function updateCardCountLabel() {
        if (!cardCountLabel || !tabulatorTable) return;
        const totalCards = tabulatorTable.getDataCount();
        cardCountLabel.textContent = totalCards + (totalCards === 1 ? ' Card' : ' Cards');

        if (totalCards === 0) {
            if (gridContainer) gridContainer.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
        } else {
            if (gridContainer) gridContainer.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');
        }
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

    // Modal Control Handlers
    function openModalForCreate() {
        if (modalTitle) modalTitle.textContent = 'Create New Flashcard';
        if (modalCardId) modalCardId.value = '';
        if (modalCardFront) modalCardFront.value = '';
        if (modalCardBack) modalCardBack.value = '';
        if (modalCardDue) modalCardDue.value = formatDateTimeForInput(new Date());
        if (modalError) modalError.classList.add('hidden');

        setMinDueDateConstraint();
        if (cardModal) cardModal.showModal();
    }

    function openModalForEdit(cardData) {
        if (!cardData) return;
        if (modalTitle) modalTitle.textContent = 'View / Edit Flashcard';
        if (modalCardId) modalCardId.value = cardData.id || '';
        if (modalCardFront) modalCardFront.value = cardData.front || '';
        if (modalCardBack) modalCardBack.value = cardData.back || '';
        if (modalCardDue) modalCardDue.value = formatDateTimeForInput(cardData.next_review_at);
        if (modalError) modalError.classList.add('hidden');

        setMinDueDateConstraint();
        if (cardModal) cardModal.showModal();
    }

    if (closeModalBtn) closeModalBtn.addEventListener('click', () => cardModal && cardModal.close());
    if (openAddCardBtn) openAddCardBtn.addEventListener('click', openModalForCreate);
    if (emptyState) emptyState.addEventListener('click', openModalForCreate);

    document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const dlg = btn.closest('dialog');
            if (dlg) dlg.close();
        });
    });

    // 1. Tabulator Grid Setup
    if (gridContainer) {
        tabulatorTable = new Tabulator("#flashcards-tabulator-grid", {
            data: initialFlashcards,
            layout: "fitDataFill",
            responsiveLayout: false,
            selectable: 1,
            resizableColumnFit: false,
            placeholder: "No Flashcards Available",
            columns: [
                { title: "Front", field: "front", formatter: "textarea", minWidth: 220, width: 320 },
                { title: "Back", field: "back", formatter: "textarea", minWidth: 220, width: 320 },
                {
                    title: "Next Review (Due)",
                    field: "next_review_at",
                    minWidth: 160,
                    width: 180,
                    formatter: (cell) => formatDateTimeDisplay(cell.getValue())
                },
                {
                    title: "Interval",
                    field: "interval_days",
                    minWidth: 90,
                    width: 110,
                    formatter: (cell) => `${cell.getValue()} ${cell.getValue() === 1 ? 'day' : 'days'}`
                },
                { title: "Reviews", field: "review_count", minWidth: 80, width: 95 },
                {
                    title: "Lapses",
                    field: "lapses",
                    minWidth: 80,
                    width: 90,
                    formatter: (cell) => cell.getValue() > 0 ? `<span class="text-amber-600 font-semibold">${cell.getValue()}</span>` : cell.getValue()
                },
                {
                    title: "Actions",
                    headerSort: false,
                    frozen: true,
                    width: 90,
                    hozAlign: "center",
                    formatter: (cell) => {
                        const cardId = cell.getRow().getData().id;
                        return `
                            <div class="flex items-center justify-center gap-1">
                                <button type="button" data-edit-card="${cardId}" class="p-1 rounded-lg text-on-surface-variant hover:text-primary transition-colors" title="View/Edit Card">
                                    <span class="material-symbols-outlined text-[18px]">visibility</span>
                                </button>
                                <button type="button" data-delete-card="${cardId}" class="p-1 rounded-lg text-rose-700 hover:bg-rose-50 transition-colors" title="Delete Card">
                                    <span class="material-symbols-outlined text-[18px]">delete</span>
                                </button>
                            </div>
                        `;
                    },
                    cellClick: (e, cell) => {
                        const editBtn = e.target.closest('[data-edit-card]');
                        const deleteBtn = e.target.closest('[data-delete-card]');

                        if (editBtn) {
                            e.stopPropagation();
                            openModalForEdit(cell.getRow().getData());
                        } else if (deleteBtn) {
                            e.stopPropagation();
                            targetDeleteCardId = deleteBtn.getAttribute('data-delete-card');
                            if (deleteCardError) deleteCardError.classList.add('hidden');
                            if (deleteCardDialog) deleteCardDialog.showModal();
                        }
                    }
                }
            ]
        });

        // Open modal when clicking any row
        tabulatorTable.on("rowClick", (e, row) => {
            openModalForEdit(row.getData());
        });
    }

    // 2. Save Button & Modal Form Submission
    if (modalForm) {
        modalForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const cardId = modalCardId ? modalCardId.value.trim() : '';
            const front = modalCardFront ? modalCardFront.value.trim() : '';
            const back = modalCardBack ? modalCardBack.value.trim() : '';
            const nextReviewAt = modalCardDue ? modalCardDue.value.trim() : '';

            if (front === '' || back === '') {
                if (modalError) {
                    modalError.textContent = 'Both Front and Back content are required.';
                    modalError.classList.remove('hidden');
                }
                return;
            }

            if (modalSaveBtn) modalSaveBtn.disabled = true;

            try {
                const action = cardId ? 'update' : 'add';
                const res = await sendCardAction({ action, card_id: cardId, front, back, next_review_at: nextReviewAt });

                if (action === 'add') {
                    if (tabulatorTable) await tabulatorTable.addRow(res.card, true);
                } else {
                    if (tabulatorTable) {
                        const row = tabulatorTable.getRow(res.card.id);
                        if (row) row.update(res.card);
                    }
                }

                updateCardCountLabel();
                if (cardModal) cardModal.close();
            } catch (err) {
                if (modalError) {
                    modalError.textContent = err.message;
                    modalError.classList.remove('hidden');
                }
            } finally {
                if (modalSaveBtn) modalSaveBtn.disabled = false;
            }
        });
    }

    // 3. Delete Card Execution
    if (deleteCardConfirm) {
        deleteCardConfirm.addEventListener('click', async () => {
            if (!targetDeleteCardId) return;
            deleteCardConfirm.disabled = true;
            try {
                await sendCardAction({ action: 'delete', card_id: targetDeleteCardId });
                if (tabulatorTable) {
                    const row = tabulatorTable.getRow(targetDeleteCardId);
                    if (row) row.delete();
                }
                updateCardCountLabel();
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