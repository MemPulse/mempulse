/**
 * MemPulse Deck Flashcards Module (Tabulator + Persistent Sidebar & Mobile Modal)
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
    let selectedSidebarCardId = null;
    const Archive = window.MemPulseArchive;
    // Navigation state variables
    const sidebarPrevBtn = document.getElementById('sidebar-prev-btn');
    const sidebarNextBtn = document.getElementById('sidebar-next-btn');
    let selectedTabulatorRow = null;

    if (!DECK_ID) return;

    // DOM Elements
    const gridContainer = document.getElementById('flashcards-tabulator-grid');
    const tableWrapper = document.getElementById('table-wrapper');
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

    // Sidebar Editor Elements
    const sidebarSidebar = document.getElementById('card-editor-sidebar');
    const toggleSidebarCollapseBtn = document.getElementById('toggle-sidebar-collapse');
    const toggleSidebarIcon = document.getElementById('toggle-sidebar-icon');
    const sidebarContentWrapper = document.getElementById('sidebar-content-wrapper');
    const sidebarForm = document.getElementById('sidebar-card-form');
    const sidebarCardId = document.getElementById('sidebar-card-id');
    const sidebarCardFront = document.getElementById('sidebar-card-front');
    const sidebarCardBack = document.getElementById('sidebar-card-back');
    const sidebarCardDue = document.getElementById('sidebar-card-due');
    const sidebarCardError = document.getElementById('sidebar-card-error');
    const sidebarSaveBtn = document.getElementById('sidebar-save-btn');
    const sidebarTitle = document.getElementById('sidebar-title');
    const workspace = document.getElementById('mp-workspace');
    const peekTab = document.getElementById('editor-peek-tab');
    const positionLabel = document.getElementById('sidebar-position');
    const gridFooter = document.getElementById('grid-footer');
    const rangeLabel = document.getElementById('grid-range-label');
    const progressBar = document.getElementById('grid-progress-bar');
    const loadMoreBtn = document.getElementById('grid-load-more-btn');
    const loadMoreLabel = document.getElementById('grid-load-more-label');
    const loadMoreIcon = document.getElementById('grid-load-more-icon');
    const topBtn = document.getElementById('grid-top-btn');

    // Sidebar Details Elements
    const detLastReviewed = document.getElementById('det-last-reviewed');
    const detAccuracy = document.getElementById('det-accuracy');
    const detSpeed = document.getElementById('det-speed');
    const detInterval = document.getElementById('det-interval');
    const detReviews = document.getElementById('det-reviews');
    const detLapses = document.getElementById('det-lapses');

    // Modal Elements (Mobile Only)
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

    // Card editor sidebar is desktop-only (lg = 1024px). Mobile and tablet use the modal.
    const desktopMQ = window.matchMedia('(min-width: 1024px)');
    function isDesktopView() { return desktopMQ.matches; }

    // Initialize HTML5 datetime-local minimum constraint
    function setMinDueDateConstraint() {
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const minFormatted = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T00:00`;
        if (sidebarCardDue) sidebarCardDue.min = minFormatted;
        if (modalCardDue) modalCardDue.min = minFormatted;
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

    // Read-only support for archived cards
    function setEditorReadOnly(readOnly) {
        [sidebarCardFront, sidebarCardBack, modalCardFront, modalCardBack].forEach((el) => { if (el) el.readOnly = readOnly; });
        if (sidebarCardDue) sidebarCardDue.disabled = readOnly;
        if (modalCardDue) modalCardDue.disabled = readOnly;
        if (sidebarSaveBtn) sidebarSaveBtn.classList.toggle('hidden', readOnly);
        if (modalSaveBtn) modalSaveBtn.classList.toggle('hidden', readOnly);

        if (readOnly) {
            if (sidebarTitle) sidebarTitle.textContent = 'Archived Card Details';
            if (modalTitle) modalTitle.textContent = 'Archived Flashcard';
        } else {
            if (sidebarTitle) sidebarTitle.textContent = 'Card Editor';
            if (modalTitle) modalTitle.textContent = 'View / Edit Flashcard';
        }
    }

    // Editor collapse: hides the editor, grid goes full width, a peek tab shows on the right edge
    let isSidebarCollapsed = false;
    function setSidebarCollapsed(collapsed) {
        isSidebarCollapsed = collapsed;
        if (workspace) workspace.classList.toggle('editor-collapsed', collapsed);
        if (sidebarSidebar) sidebarSidebar.classList.toggle('mp-collapsed', collapsed);
        if (peekTab) peekTab.hidden = !collapsed;
        if (tabulatorTable) requestAnimationFrame(() => tabulatorTable.redraw(true));
    }
    if (toggleSidebarCollapseBtn) toggleSidebarCollapseBtn.addEventListener('click', () => setSidebarCollapsed(true));
    if (peekTab) peekTab.addEventListener('click', () => setSidebarCollapsed(false));

    function updatePositionLabel() {
        if (!positionLabel) return;
        const total = viewArchived ? archivedCards : totalCards;
        if (!selectedSidebarCardId || !tabulatorTable) { positionLabel.textContent = 'New card'; return; }
        const idx = tabulatorTable.getRows().findIndex(r => Number(r.getData().id) === selectedSidebarCardId);
        positionLabel.textContent = idx === -1 ? 'Use ↑ ↓ to browse' : `Card ${idx + 1} of ${total} · ↑ ↓ to browse`;
    }

    // Populate Sidebar Editor with selected card data & metadata details
    function populateSidebar(cardData, rowInstance = null, { expand = true } = {}) {
        if (!cardData) return;
        if (!rowInstance && tabulatorTable) rowInstance = tabulatorTable.getRow(Number(cardData.id)) || null;
        selectedSidebarCardId = cardData.id != null ? Number(cardData.id) : null;
        if (rowInstance) selectedTabulatorRow = rowInstance;

        if (sidebarCardId) sidebarCardId.value = cardData.id || '';
        if (sidebarCardFront) sidebarCardFront.value = cardData.front || '';
        if (sidebarCardBack) sidebarCardBack.value = cardData.back || '';
        if (sidebarCardDue) sidebarCardDue.value = formatDateTimeForInput(cardData.next_review_at);
        if (sidebarCardError) sidebarCardError.classList.add('hidden');

        if (detLastReviewed) detLastReviewed.textContent = cardData.last_reviewed_at ? formatDateTimeDisplay(cardData.last_reviewed_at) : 'Never';
        if (detAccuracy) detAccuracy.textContent = cardData.accuracy !== null && cardData.accuracy !== undefined ? cardData.accuracy + '%' : '—';
        if (detSpeed) detSpeed.textContent = cardData.avg_response_ms !== null && cardData.avg_response_ms !== undefined ? (cardData.avg_response_ms / 1000).toFixed(1) + 's' : '—';
        if (detInterval) detInterval.textContent = cardData.interval_days !== undefined ? cardData.interval_days + (Number(cardData.interval_days) === 1 ? ' day' : ' days') : '—';
        if (detReviews) detReviews.textContent = cardData.review_count !== undefined ? cardData.review_count : '0';
        if (detLapses) detLapses.textContent = cardData.lapses !== undefined ? cardData.lapses : '0';

        setEditorReadOnly(viewArchived);
        updatePositionLabel();
        if (isSidebarCollapsed && expand) setSidebarCollapsed(false);
    }

    // Row Navigation Function (Up / Down)
    async function navigateSidebarRow(direction, { expand = true } = {}) {
        if (!tabulatorTable) return;
        let rows = tabulatorTable.getRows();
        if (!rows.length) return;

        const idx = rows.findIndex(r => Number(r.getData().id) === selectedSidebarCardId);
        let target;
        if (idx === -1) {
            target = direction === 'next' ? rows[0] : rows[rows.length - 1];
        } else if (direction === 'next') {
            if (idx === rows.length - 1 && pending.length) {   // end of loaded slice: pull the next 50
                await loadMore();
                rows = tabulatorTable.getRows();
            }
            target = rows[idx + 1];
        } else {
            target = rows[idx - 1];
        }
        if (!target) return;                                   // already at first/last card

        target.select();
        populateSidebar(target.getData(), target, { expand });
        Promise.resolve(target.scrollTo('nearest', false)).catch(() => { });
    }

    if (sidebarPrevBtn) {
        sidebarPrevBtn.addEventListener('click', () => navigateSidebarRow('prev'));
    }
    if (sidebarNextBtn) {
        sidebarNextBtn.addEventListener('click', () => navigateSidebarRow('next'));
    }

    // ↑ / ↓ browse cards on desktop (ignored while typing in a field or when a dialog is open)
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
        if (!isDesktopView() || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
        if (document.querySelector('dialog[open]')) return;
        if (e.target instanceof Element && e.target.closest('input, textarea, select, [contenteditable="true"]')) return;
        e.preventDefault();
        navigateSidebarRow(e.key === 'ArrowDown' ? 'next' : 'prev', { expand: false });
    });

    // Modal Control Handlers (For Mobile View)
    function openModalForCreate() {
        if (viewArchived) return;
        if (modalTitle) modalTitle.textContent = 'Create New Flashcard';
        if (modalCardId) modalCardId.value = '';
        if (modalCardFront) modalCardFront.value = '';
        if (modalCardBack) modalCardBack.value = '';
        if (modalCardDue) modalCardDue.value = formatDateTimeForInput(new Date());
        if (modalError) modalError.classList.add('hidden');

        setMinDueDateConstraint();
        setEditorReadOnly(false);
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
        setEditorReadOnly(viewArchived);
        if (cardModal) cardModal.showModal();
    }

    if (closeModalBtn) closeModalBtn.addEventListener('click', () => cardModal && cardModal.close());
    if (openAddCardBtn) openAddCardBtn.addEventListener('click', () => {
        if (isDesktopView()) {
            setSidebarCollapsed(false);
            selectedSidebarCardId = null;
            selectedTabulatorRow = null;
            if (tabulatorTable) tabulatorTable.deselectRow();
            if (sidebarCardId) sidebarCardId.value = '';
            if (sidebarCardFront) sidebarCardFront.value = '';
            if (sidebarCardBack) sidebarCardBack.value = '';
            if (sidebarCardDue) sidebarCardDue.value = formatDateTimeForInput(new Date());
            if (sidebarCardError) sidebarCardError.classList.add('hidden');
            if (sidebarTitle) sidebarTitle.textContent = 'New Flashcard';
            updatePositionLabel();
            if (sidebarCardFront) sidebarCardFront.focus();
        } else {
            openModalForCreate();
        }
    });

    document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const dlg = btn.closest('dialog');
            if (dlg) dlg.close();
        });
    });

    // ---------------------------------------------------------------------------
    // Lazy loading: render 50 cards at a time
    // ---------------------------------------------------------------------------
    const PAGE_SIZE = 50;
    let pending = [];          // cards of the current view that aren't rendered yet
    let loadingMore = false;
    let gridReady = false;

    function updateLoadMoreUI() {
        if (!gridFooter || !tabulatorTable) return;
        const loaded = tabulatorTable.getDataCount();
        const remaining = pending.length;
        const total = loaded + remaining;
        const noun = total === 1 ? 'card' : 'cards';

        gridFooter.hidden = total === 0;
        rangeLabel.textContent = remaining ? `Showing ${loaded} of ${total} ${noun}` : `All ${total} ${noun} loaded`;
        progressBar.style.width = total ? `${Math.round((loaded / total) * 100)}%` : '0%';
        loadMoreBtn.hidden = remaining === 0;
        loadMoreLabel.textContent = loadingMore ? 'Loading…' : `Load ${Math.min(PAGE_SIZE, remaining)} more`;
        loadMoreBtn.disabled = loadingMore;
        loadMoreIcon.textContent = loadingMore ? 'progress_activity' : 'expand_more';
        loadMoreIcon.classList.toggle('mp-spin', loadingMore);
    }

    function loadFirstPage(list) {
        pending = list.slice(PAGE_SIZE);
        return tabulatorTable.setData(list.slice(0, PAGE_SIZE)).then(updateLoadMoreUI);
    }

    async function loadMore() {
        if (loadingMore || !pending.length || !tabulatorTable) return;
        loadingMore = true;
        updateLoadMoreUI();
        try {
            await tabulatorTable.addData(pending.splice(0, PAGE_SIZE), false); // false = append at bottom
        } finally {
            loadingMore = false;
            updateLoadMoreUI();
        }
    }

    function bindGridScroll() {
        const holder = gridContainer.querySelector('.tabulator-tableholder');
        if (!holder) return;
        holder.addEventListener('scroll', () => {
            if (topBtn) topBtn.hidden = holder.scrollTop < 200;
            if (holder.scrollTop + holder.clientHeight >= holder.scrollHeight - 160) loadMore();
        }, { passive: true });
        if (topBtn) topBtn.addEventListener('click', () => holder.scrollTo({ top: 0, behavior: 'smooth' }));
    }
    if (loadMoreBtn) loadMoreBtn.addEventListener('click', loadMore);

    // 1. Tabulator Grid Setup
    const allColumns = [
        {
            title: "Front",
            field: "front",
            formatter: "plaintext",
            tooltip: true, minWidth: 200, width: 280
        },
        {
            title: "Back",
            field: "back",
            formatter: "plaintext",
            tooltip: true, minWidth: 200, width: 280
        },
        {
            title: "Next Review (Due)",
            field: "next_review_at",
            minWidth: 150,
            width: 170,
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
    ];

    if (gridContainer) {
        tabulatorTable = new Tabulator("#flashcards-tabulator-grid", {
            data: [],                              // filled in tableBuilt, first 50 only
            layout: "fitDataFill",
            height: "100%",                        // fills .mp-pane (replaces maxHeight)
            responsiveLayout: false,
            selectable: 1,                         // select-only: the grid has no cell editors
            resizableColumnFit: false,
            columnDefaults: { headerSort: false }, // sorting 50 of N rows would mislead
            placeholder: "No Flashcards Available",
            columns: allColumns.filter((c) => c.field !== 'days_left'),
        });

        tabulatorTable.on("tableBuilt", () => {
            gridReady = true;
            bindGridScroll();
            if (initialFlashcards.length > 0) {
                toggleStateVisibility(true);
                loadFirstPage(initialFlashcards).then(() => {
                    if (!isDesktopView()) return;
                    const firstRow = tabulatorTable.getRows()[0];
                    if (firstRow) {
                        firstRow.select();
                        populateSidebar(firstRow.getData(), firstRow);
                    }
                });
            }
        });

        // Desktop: row click fills the editor. Mobile/tablet: row click opens the modal.
        tabulatorTable.on("rowClick", (e, row) => {
            const cardData = row.getData();
            if (isDesktopView()) populateSidebar(cardData, row);
            else openModalForEdit(cardData);
        });
    }

    // Crossing the breakpoint: the modal never coexists with the editor
    desktopMQ.addEventListener('change', (e) => {
        if (e.matches && cardModal && cardModal.open) cardModal.close();
        if (tabulatorTable) requestAnimationFrame(() => tabulatorTable.redraw(true));
    });

    // 2. Save Form Handler (Shared by Sidebar & Modal)
    async function handleCardSave(cardId, front, back, nextReviewAt, errorEl, saveBtnEl, isSidebar) {
        if (front === '' || back === '') {
            if (errorEl) {
                errorEl.textContent = 'Both Front and Back content are required.';
                errorEl.classList.remove('hidden');
            }
            return;
        }

        if (saveBtnEl) saveBtnEl.disabled = true;

        try {
            const action = cardId ? 'update' : 'add';
            const res = await sendCardAction({ action, card_id: cardId, front, back, next_review_at: nextReviewAt });

            if (action === 'add') {
                totalCards++;
                if (tabulatorTable) {
                    const row = await tabulatorTable.addRow(res.card, true);
                    if (isSidebar && row) {                 // bind the editor to the new card so Save can't duplicate it
                        row.select();
                        populateSidebar(row.getData(), row);
                    }
                }
            } else {
                const row = tabulatorTable ? tabulatorTable.getRow(Number(res.card.id)) : null;
                if (row) await row.update(res.card);
                if (isSidebar && isDesktopView()) {
                    populateSidebar(row ? row.getData() : res.card, row || null);   // row data keeps accuracy/speed/etc.
                }
            }

            updateCardCountLabel();
            if (gridReady) updateLoadMoreUI();
            if (!isSidebar && cardModal) cardModal.close();
            Archive.toast(action === 'add' ? 'Card created successfully' : 'Card updated successfully');
        } catch (err) {
            if (errorEl) {
                errorEl.textContent = err.message;
                errorEl.classList.remove('hidden');
            }
        } finally {
            if (saveBtnEl) saveBtnEl.disabled = false;
        }
    }

    if (sidebarForm) {
        sidebarForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const cardId = sidebarCardId ? sidebarCardId.value.trim() : '';
            const front = sidebarCardFront ? sidebarCardFront.value.trim() : '';
            const back = sidebarCardBack ? sidebarCardBack.value.trim() : '';
            const nextReviewAt = sidebarCardDue ? sidebarCardDue.value.trim() : '';
            await handleCardSave(cardId, front, back, nextReviewAt, sidebarCardError, sidebarSaveBtn, true);
        });
    }

    if (modalForm) {
        modalForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const cardId = modalCardId ? modalCardId.value.trim() : '';
            const front = modalCardFront ? modalCardFront.value.trim() : '';
            const back = modalCardBack ? modalCardBack.value.trim() : '';
            const nextReviewAt = modalCardDue ? modalCardDue.value.trim() : '';
            await handleCardSave(cardId, front, back, nextReviewAt, modalError, modalSaveBtn, false);
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
                Archive.toast('Card deleted permanently', { type: 'error' });
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
                const row = tabulatorTable.getRow(id);
                const data = row ? row.getData() : null;
                if (row) row.delete();
                if (data) offscreenList.unshift({ ...data, archived_at: null, days_left: null });
                Archive.toast('Card restored');
            } else {
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
            tabulatorTable.setColumns(
                allColumns.filter((c) => c.field !== (archived ? 'next_review_at' : 'days_left'))
            );

            const current = tabulatorTable.getData().concat(pending); // full list of the view we're leaving
            const next = offscreenList;
            offscreenList = current;
            selectedSidebarCardId = null;
            selectedTabulatorRow = null;

            loadFirstPage(next).then(() => {
                tabulatorTable.redraw(true);
                const first = tabulatorTable.getRows()[0];
                if (first && isDesktopView()) {
                    first.select();
                    populateSidebar(first.getData(), first);
                }
            });
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
                    if (row) await row.update(res.card);
                    if (isDesktopView() && selectedSidebarCardId === Number(id)) {
                        populateSidebar(row ? row.getData() : res.card, row || null);
                    }
                    Archive.toast('Progress reset. The card is due now.');
                }
            } catch (err) {
                Archive.toast(err.message, { type: 'error' });
            }
        });
    }
});