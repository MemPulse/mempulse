/**
 * MemPulse Deck Flashcards Module (Tabulator + Modal Editor)
 */
document.addEventListener("DOMContentLoaded", () => {
    const config = window.MemPulseDeckData || {};
    const CSRF_TOKEN = config.csrfToken || '';
    const DECK_ID = config.deckId || null;
    const initialFlashcards = Array.isArray(config.flashcards) ? config.flashcards : [];
    let totalCards = initialFlashcards.length;
    let archivedCards = Array.isArray(config.archivedFlashcards) ? config.archivedFlashcards.length : 0;
    let offscreenList = Array.isArray(config.archivedFlashcards) ? config.archivedFlashcards.slice() : [];
    let viewArchived = false;
    let menuCardId = null;
    const Archive = window.MemPulseArchive;

    if (!DECK_ID) return;

    // DOM Elements
    const gridContainer = document.getElementById('flashcards-tabulator-grid');
    const emptyState = document.getElementById('no-cards-empty-state');
    const cardCountLabel = document.getElementById('deck-card-count-label');
    const openAddCardBtn = document.getElementById('open-add-card-btn');
    const toggleArchiveBtn = document.getElementById('toggle-archive-btn');
    const toggleArchiveIcon = document.getElementById('toggle-archive-icon');
    const toggleArchiveLabel = document.getElementById('toggle-archive-label');
    const archivedCountLabel = document.getElementById('archived-count-label');
    const archiveBanner = document.getElementById('archive-banner');
    const startReviewBtn = document.getElementById('start-review-btn');
    const emptyTitle = document.getElementById('empty-state-title');
    const emptyText = document.getElementById('empty-state-text');
    const actionsMenu = document.getElementById('card-actions-menu');

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

    // Toggle grid vs empty state container visibility
    function toggleStateVisibility(hasCards) {
        if (hasCards) {
            if (gridContainer) gridContainer.classList.remove('hidden');
            if (emptyState) emptyState.classList.add('hidden');
        } else {
            if (gridContainer) gridContainer.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
        }
    }

    // Synchronize initial layout visibility
    toggleStateVisibility(initialFlashcards.length > 0);

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
        if (cardCountLabel) {
            cardCountLabel.textContent = totalCards + (totalCards === 1 ? ' Card' : ' Cards');
        }
        if (archivedCountLabel) archivedCountLabel.textContent = archivedCards;

        const visibleCount = viewArchived ? archivedCards : totalCards;
        toggleStateVisibility(visibleCount > 0);
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
    //Modal read-only support
    function setModalReadOnly(readOnly) {
        [modalCardFront, modalCardBack].forEach((el) => { if (el) el.readOnly = readOnly; });
        if (modalCardDue) modalCardDue.disabled = readOnly;
        if (modalSaveBtn) modalSaveBtn.classList.toggle('hidden', readOnly);
        if (readOnly && modalTitle) modalTitle.textContent = 'Archived Flashcard';
    }

    // Modal Control Handlers
    function openModalForCreate() {
        if (viewArchived) return;
        if (modalTitle) modalTitle.textContent = 'Create New Flashcard';
        if (modalCardId) modalCardId.value = '';
        if (modalCardFront) modalCardFront.value = '';
        if (modalCardBack) modalCardBack.value = '';
        if (modalCardDue) modalCardDue.value = formatDateTimeForInput(new Date());
        if (modalError) modalError.classList.add('hidden');

        setMinDueDateConstraint();
        setModalReadOnly(false);
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
        setModalReadOnly(viewArchived);
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
    const allColumns = [
        {
            title: "Front",
            field: "front",
            formatter: "plaintext",
            tooltip: true, minWidth: 220, width: 320
        },
        {
            title: "Back",
            field: "back",
            formatter: "plaintext",
            tooltip: true, minWidth: 220, width: 320
        },
        {
            title: "Next Review (Due)",
            field: "next_review_at",
            minWidth: 160,
            width: 180,
            formatter: (cell) => formatDateTimeDisplay(cell.getValue())
        },
        {
            title: "Purges In",
            field: "days_left",
            minWidth: 90, width: 110, maxWidth: 200,
            hozAlign: "center",
            formatter: (cell) => {
                const d = cell.getValue();
                const cls = d !== null && d <= 3 ? 'text-rose-700' : 'text-on-surface-variant';
                return `<span class="font-semibold ${cls}">${Archive.retentionLabel(d)}</span>`;
            }
        },
        {
            title: "Last Reviewed",
            field: "last_reviewed_at",
            minWidth: 140, width: 160, maxWidth: 260,
            formatter: (cell) => cell.getValue()
                ? formatDateTimeDisplay(cell.getValue())
                : '<span class="text-on-surface-variant">Never</span>'
        },
        {
            title: "Accuracy",
            field: "accuracy",
            minWidth: 80, width: 100, maxWidth: 180,
            hozAlign: "center",
            formatter: (cell) => {
                const v = cell.getValue();
                if (v === null || v === undefined) return '—';
                const cls = v >= 80 ? 'text-emerald-700' : v >= 50 ? 'text-amber-600' : 'text-rose-700';
                return `<span class="font-semibold ${cls}">${v}%</span>`;
            }
        },
        {
            title: "Speed",
            field: "avg_response_ms",
            minWidth: 70, width: 90, maxWidth: 180,
            hozAlign: "center",
            formatter: (cell) => {
                const v = cell.getValue();
                return (v === null || v === undefined) ? '—' : (v / 1000).toFixed(1) + 's';
            }
        },
        {
            title: "Interval",
            field: "interval_days",
            minWidth: 80, width: 100, maxWidth: 220,
            formatter: (cell) => `${cell.getValue()} ${cell.getValue() === 1 ? 'day' : 'days'}`
        },
        {
            title: "Reviews", field: "review_count",
            minWidth: 70, width: 90, maxWidth: 200
        },
        {
            title: "Lapses",
            field: "lapses",
            minWidth: 70, width: 90, maxWidth: 200,
            formatter: (cell) => cell.getValue() > 0 ? `<span class="text-amber-600 font-semibold">${cell.getValue()}</span>` : cell.getValue()
        }
    ]
    if (gridContainer) {
        tabulatorTable = new Tabulator("#flashcards-tabulator-grid", {
            data: initialFlashcards,
            layout: "fitDataFill",
            maxHeight: "calc(100vh - 300px)",
            responsiveLayout: false,
            selectable: 1,
            resizableColumnFit: false,
            placeholder: "No Flashcards Available",
            columns: allColumns.filter((c) => c.field !== 'days_left'),
        });

        // Ensure flashcards are properly populated and rendered once Tabulator initializes
        tabulatorTable.on("tableBuilt", () => {
            if (initialFlashcards.length > 0) {
                toggleStateVisibility(true);
                tabulatorTable.setData(initialFlashcards);
            }
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
                        const row = tabulatorTable.getRow(Number(res.card.id));
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
                await sendCardAction({ action: 'delete_permanent', card_id: targetDeleteCardId });
                if (tabulatorTable) {
                    const row = tabulatorTable.getRow(Number(targetDeleteCardId));
                    if (row) row.delete();
                    archivedCards--;
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
    // ---------------------------------------------------------------------------
    // Archive: archive / restore / view switching
    // ---------------------------------------------------------------------------
    function removeRow(id) {
        if (!tabulatorTable) return;
        const row = tabulatorTable.getRow(Number(id));
        if (row) row.delete();
    }

    async function archiveCard(card) {
        try {
            await sendCardAction({ action: 'archive', card_id: card.id });
            removeRow(card.id);
            // keep the off-screen archive list in sync
            offscreenList.unshift({ ...card, archived_at: new Date().toISOString(), days_left: Archive.RETENTION_DAYS });
            totalCards--;
            archivedCards++;
            updateCardCountLabel();
            Archive.toast('Card archived', { actionLabel: 'Undo', onAction: () => restoreCard(card.id) });
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        }
    }

    async function restoreCard(cardId) {
        try {
            await sendCardAction({ action: 'restore', card_id: cardId });
            const id = Number(cardId);

            if (viewArchived) {
                // restoring from the archive view: move the row to the off-screen active list
                const row = tabulatorTable.getRow(id);
                const data = row ? row.getData() : null;
                if (row) row.delete();
                if (data) offscreenList.unshift({ ...data, archived_at: null, days_left: null });
                Archive.toast('Card restored');
            } else {
                // undo from the active view: pull it back from the off-screen archive list
                const idx = offscreenList.findIndex((c) => Number(c.id) === id);
                if (idx !== -1) {
                    const [data] = offscreenList.splice(idx, 1);
                    await tabulatorTable.addRow({ ...data, archived_at: null, days_left: null }, true);
                }
            }

            totalCards++;
            archivedCards--;
            updateCardCountLabel();
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        }
    }

    function setViewMode(archived) {
        viewArchived = archived;
        closeActionsMenu();

        if (archiveBanner) archiveBanner.classList.toggle('hidden', !archived);
        if (openAddCardBtn) openAddCardBtn.classList.toggle('hidden', archived);
        if (startReviewBtn) startReviewBtn.classList.toggle('hidden', archived);
        if (toggleArchiveIcon) toggleArchiveIcon.textContent = archived ? 'arrow_back' : 'inventory_2';
        if (toggleArchiveLabel) toggleArchiveLabel.textContent = archived ? 'Back to cards' : 'Archive';
        if (toggleArchiveBtn) {
            const label = archived ? 'Back to cards' : 'View archive';
            toggleArchiveBtn.title = label;
            toggleArchiveBtn.setAttribute('aria-label', label);
            toggleArchiveBtn.setAttribute('aria-pressed', String(archived));
        }
        if (emptyTitle) emptyTitle.textContent = archived ? 'Archive is empty' : 'No flashcards yet';
        if (emptyText) {
            emptyText.textContent = archived
                ? `Archived cards appear here for ${Archive.RETENTION_DAYS} days before they are permanently deleted.`
                : 'Click "Create Card" to add your first card.';
        }

        if (tabulatorTable) {
            // rebuild columns for this view (more reliable than hide/show with a frozen column)
            tabulatorTable.setColumns(
                allColumns.filter((c) => c.field !== (archived ? 'next_review_at' : 'days_left'))
            );

            // swap the on-screen list with the off-screen one (snapshot first so edits aren't lost)
            const current = tabulatorTable.getData();
            tabulatorTable.setData(offscreenList).then(() => tabulatorTable.redraw(true));
            offscreenList = current;
        }
        updateCardCountLabel();
    }

    if (toggleArchiveBtn) toggleArchiveBtn.addEventListener('click', () => setViewMode(!viewArchived));

    // ---------------------------------------------------------------------------
    // Row overflow menu (Duplicate / Reset progress)
    // ---------------------------------------------------------------------------
    function openActionsMenu(btn, cardId) {
        if (!actionsMenu) return;
        menuCardId = cardId;
        const r = btn.getBoundingClientRect();
        actionsMenu.classList.remove('hidden');
        const w = actionsMenu.offsetWidth;
        actionsMenu.style.top = `${r.bottom + 4}px`;
        actionsMenu.style.left = `${Math.max(8, r.right - w)}px`;
    }

    function closeActionsMenu() {
        if (actionsMenu) actionsMenu.classList.add('hidden');
        menuCardId = null;
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#card-actions-menu') && !e.target.closest('[data-more-card]')) closeActionsMenu();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeActionsMenu(); });
    window.addEventListener('resize', closeActionsMenu);
    if (tabulatorTable) {
        tabulatorTable.on('scrollVertical', closeActionsMenu);
        tabulatorTable.on('scrollHorizontal', closeActionsMenu);
    }

    if (actionsMenu) {
        actionsMenu.addEventListener('click', async (e) => {
            const item = e.target.closest('[data-menu-action]');
            if (!item || !menuCardId) return;
            const id = menuCardId;
            const act = item.dataset.menuAction;
            closeActionsMenu();

            try {
                if (act === 'duplicate') {
                    const res = await sendCardAction({ action: 'duplicate', card_id: id });
                    if (tabulatorTable) await tabulatorTable.addRow(res.card, true);
                    totalCards++;
                    updateCardCountLabel();
                    Archive.toast('Card duplicated');
                } else if (act === 'reset') {
                    const res = await sendCardAction({ action: 'reset_progress', card_id: id });
                    const row = tabulatorTable ? tabulatorTable.getRow(Number(id)) : null;
                    if (row) row.update(res.card);
                    Archive.toast('Progress reset. The card is due now.');
                }
            } catch (err) {
                Archive.toast(err.message, { type: 'error' });
            }
        });
    }
});