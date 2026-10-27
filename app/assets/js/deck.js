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
    let dueCount = Number(config.dueCount) || 0;
    let offscreenList = Array.isArray(config.archivedFlashcards) ? config.archivedFlashcards.slice() : [];
    let viewArchived = false;
    const checkedIds = new Set();          // declared early: grid formatters and the editor read it
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
    const dueCountLabel = document.getElementById('deck-due-count');
    const openAddCardBtn = document.getElementById('open-add-card-btn');
    const toggleArchiveBtn = document.getElementById('toggle-archive-btn');
    const toggleArchiveIcon = document.getElementById('toggle-archive-icon');
    const toggleArchiveLabel = document.getElementById('toggle-archive-label');
    const archivedCountLabel = document.getElementById('archived-count-label');
    const archiveBanner = document.getElementById('archive-banner');
    const startReviewBtn = document.getElementById('start-review-btn');
    const emptyTitle = document.getElementById('empty-state-title');
    const emptyText = document.getElementById('empty-state-text');

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
    const sidebarSaveLabel = document.getElementById('sidebar-save-label');
    const sidebarTitle = document.getElementById('sidebar-title');
    const workspace = document.getElementById('mp-workspace');
    const peekTab = document.getElementById('editor-peek-tab');
    const positionLabel = document.getElementById('sidebar-position');
    const gridFooter = document.getElementById('grid-footer');
    const rangeLabel = document.getElementById('grid-range-label');
    const pageSizeSelect = document.getElementById('grid-page-size');
    const paginationControls = document.getElementById('grid-pagination-controls');
    let pageSize = 25;

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
    const modalSaveLabel = document.getElementById('modal-save-label');
    const modalCancelBtn = document.getElementById('modal-cancel-btn');
    const closeModalBtn = document.getElementById('close-modal-btn');

    // Delete Modal Elements
    const deleteCardDialog = document.getElementById('delete-card-dialog');
    const deleteCardError = document.getElementById('delete-card-error');
    const deleteCardConfirm = document.getElementById('delete-card-confirm');

    // Keyboard hint badges: show the Command key instead of Ctrl on Apple devices
    const IS_APPLE = /Mac|iPhone|iPad|iPod/i.test(navigator.platform || navigator.userAgent || '');
    const MOD_KEY = IS_APPLE ? '⌘' : 'Ctrl';
    document.querySelectorAll('.mp-kbd-mod').forEach((k) => { k.textContent = MOD_KEY; });

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
        if (dueCountLabel) dueCountLabel.textContent = dueCount;
        if (startReviewBtn) startReviewBtn.classList.toggle('hidden', viewArchived || dueCount < 1);

        const visibleCount = viewArchived ? archivedCards : totalCards;
        toggleStateVisibility(visibleCount > 0);
    }

    // Server-side counters (returned by every card action) are the source of truth,
    // so "Due Now" is always the latest after create / edit / archive / restore / bulk actions.
    function applyCounts(counts) {
        if (counts) {
            if (counts.card_count !== undefined) totalCards = Number(counts.card_count) || 0;
            if (counts.archived_count !== undefined) archivedCards = Number(counts.archived_count) || 0;
            if (counts.due_count !== undefined) dueCount = Number(counts.due_count) || 0;
        }
        updateCardCountLabel();
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

    // Button labels follow the mode: a blank form creates, a loaded card saves changes
    function syncSaveLabels() {
        const sidebarNew = !(sidebarCardId && sidebarCardId.value);
        const modalNew = !(modalCardId && modalCardId.value);
        if (sidebarSaveLabel) sidebarSaveLabel.textContent = sidebarNew ? 'Create Card' : 'Save Changes';
        if (sidebarSaveBtn) sidebarSaveBtn.title = `${sidebarNew ? 'Create Card' : 'Save Changes'} (${MOD_KEY}+Return)`;
        if (modalSaveLabel) modalSaveLabel.textContent = modalNew ? 'Create' : 'Save';
        if (modalSaveBtn) modalSaveBtn.title = `${modalNew ? 'Create Card' : 'Save Changes'} (${MOD_KEY}+Return)`;
        if (modalCancelBtn) modalCancelBtn.textContent = modalNew ? 'Done' : 'Cancel';
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
            if (sidebarTitle) sidebarTitle.textContent = (sidebarCardId && sidebarCardId.value) ? 'Card Editor' : 'New Flashcard';
            if (modalTitle) modalTitle.textContent = (modalCardId && modalCardId.value) ? 'View / Edit Flashcard' : 'Create New Flashcard';
        }
        syncSaveLabels();
    }

    // Editor collapse: hides the editor, grid goes full width, a peek tab shows on the right edge
    let isSidebarCollapsed = true;
    function setSidebarCollapsed(collapsed) {
        if (viewArchived) collapsed = true;              // no card editor in the archive
        isSidebarCollapsed = collapsed;
        if (workspace) workspace.classList.toggle('editor-collapsed', collapsed);
        if (sidebarSidebar) sidebarSidebar.classList.toggle('mp-collapsed', collapsed);
        if (peekTab) peekTab.hidden = !collapsed || viewArchived;
        if (tabulatorTable) requestAnimationFrame(() => tabulatorTable.redraw(true));
    }
    if (toggleSidebarCollapseBtn) toggleSidebarCollapseBtn.addEventListener('click', () => setSidebarCollapsed(true));
    if (peekTab) peekTab.addEventListener('click', () => setSidebarCollapsed(false));

    function updatePositionLabel() {
        if (!positionLabel) return;

        const total = viewArchived ? archivedCards : totalCards;

        if (!selectedSidebarCardId || !tabulatorTable) {
            positionLabel.textContent = 'New card';
            return;
        }

        const rows = tabulatorTable.getRows('all');
        const idx = rows.findIndex(
            r => Number(r.getData().id) === selectedSidebarCardId
        );

        positionLabel.textContent = idx === -1
            ? 'Use ↑ ↓ to browse'
            : `Card ${idx + 1} of ${total} · ↑ ↓ to browse`;
    }

    // Populate Sidebar Editor with selected card data & metadata details
    function populateSidebar(cardData, rowInstance = null, { expand = true } = {}) {
        if (!cardData || viewArchived) return;
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

        setEditorReadOnly(viewArchived || checkedIds.size === 0);

        updatePositionLabel();
        if (isSidebarCollapsed && expand) setSidebarCollapsed(false);
    }

    function pageStep() {
        const holder = gridContainer.querySelector('.tabulator-tableholder');
        const first = tabulatorTable.getRows()[0];
        const rowH = first ? first.getElement().offsetHeight : 0;
        if (!holder || !rowH) return 10;
        let perRow = 1;
        if (layoutMode === 'tiles') {
            const table = gridContainer.querySelector('.tabulator-table');
            if (table) perRow = Math.max(1, getComputedStyle(table).gridTemplateColumns.split(' ').length);
        }
        return Math.max(1, (Math.floor(holder.clientHeight / rowH) - 1) * perRow);   // one screenful, minus one row of overlap
    }



    // Single place that makes a row "the" selected row: one highlight, editor in sync
    function activateRow(row, { expand = true, position = 'nearest' } = {}) {
        tabulatorTable.deselectRow(tabulatorTable.getSelectedRows());
        row.select();
        if (isDesktopView()) populateSidebar(row.getData(), row, { expand });
        Promise.resolve(row.scrollTo(position, false)).catch(() => { });
    }

    async function navigateSidebarRow(direction, { expand = true, step = 1 } = {}) {
        if (!tabulatorTable) return;

        let rows = tabulatorTable.getRows('all');
        if (!rows.length) return;

        const idx = rows.findIndex(
            r => Number(r.getData().id) === selectedSidebarCardId
        );

        let targetIdx;
        if (idx === -1) {
            targetIdx = direction === 'next' ? 0 : rows.length - 1;
        } else {
            targetIdx = direction === 'next' ? idx + step : idx - step;
        }

        targetIdx = Math.max(0, Math.min(targetIdx, rows.length - 1));
        if (targetIdx === idx) return;

        const targetPage =
            Math.floor(targetIdx / Math.max(1, tabulatorTable.getPageSize())) + 1;

        if (targetPage !== tabulatorTable.getPage()) {
            await tabulatorTable.setPage(targetPage);
        }

        rows = tabulatorTable.getRows('all');
        activateRow(rows[targetIdx], { expand });
    }

    if (sidebarPrevBtn) {
        sidebarPrevBtn.addEventListener('click', () => navigateSidebarRow('prev'));
    }
    if (sidebarNextBtn) {
        sidebarNextBtn.addEventListener('click', () => navigateSidebarRow('next'));
    }

    // ↑ / ↓ browse cards on desktop (ignored while typing in a field or when a dialog is open)
    const NAV_KEYS = {
        ArrowDown: ['next', false], ArrowUp: ['prev', false],
        PageDown: ['next', true], PageUp: ['prev', true],
    };
    document.addEventListener('keydown', (e) => {
        // 1. Ctrl + Return to save changes
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            if (viewArchived) return;

            // Mobile / tablet modal: create or save from the keyboard
            if (cardModal && cardModal.open) {
                e.preventDefault();
                if (modalSaveBtn && !modalSaveBtn.classList.contains('hidden') && !modalSaveBtn.disabled) {
                    modalSaveBtn.click();
                }
                return;
            }

            // Desktop sidebar (editing is only unlocked for checked cards; creating is always allowed)
            if (isDesktopView() && !isSidebarCollapsed && (checkedIds.size > 0 || !selectedSidebarCardId)) {
                e.preventDefault();
                // If the save button isn't hidden (meaning it's not locked) or mid-save, trigger the click
                if (sidebarSaveBtn && !sidebarSaveBtn.classList.contains('hidden') && !sidebarSaveBtn.disabled) {
                    sidebarSaveBtn.click();
                }
            }
            return;
        }

        // Guard against intercepting keystrokes while typing in an input or a modal
        if (document.querySelector('dialog[open]')) return;
        if (e.target instanceof Element && e.target.closest('input, textarea, select, [contenteditable="true"]')) return;

        // 2. Return key to toggle sidebar collapse
        if (e.key === 'Enter' && !e.ctrlKey && !e.metaKey && !e.shiftKey && !e.altKey) {
            if (!isDesktopView() || viewArchived) return;
            e.preventDefault();
            setSidebarCollapsed(!isSidebarCollapsed);
            return;
        }

        // 3. Arrow Up/Down Navigation
        const nav = NAV_KEYS[e.key];
        if (!nav) return;
        if (!isDesktopView() || viewArchived || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
        e.preventDefault();
        navigateSidebarRow(nav[0], { expand: false, step: nav[1] ? pageStep() : 1 });
    });

    // Modal Control Handlers (For Mobile View)
    // Blank "create" state for the modal; also used after a successful create so the next card can be typed straight away
    function resetModalForCreate() {
        if (modalCardId) modalCardId.value = '';
        if (modalCardFront) modalCardFront.value = '';
        if (modalCardBack) modalCardBack.value = '';
        if (modalCardDue) modalCardDue.value = formatDateTimeForInput(new Date());
        if (modalError) modalError.classList.add('hidden');

        setMinDueDateConstraint();
        setEditorReadOnly(false);
        if (modalCardFront) modalCardFront.focus();
    }

    function openModalForCreate() {
        if (viewArchived) return;
        resetModalForCreate();
        if (cardModal) cardModal.showModal();
        if (modalCardFront) modalCardFront.focus();
    }

    function openModalForEdit(cardData) {
        if (!cardData || viewArchived) return;
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
    // Blank "create" state for the desktop editor; also used after a successful create (fast entry: Ctrl+Return, type, repeat)
    function resetSidebarForCreate() {
        selectedSidebarCardId = null;
        selectedTabulatorRow = null;
        if (tabulatorTable) tabulatorTable.deselectRow();
        if (sidebarCardId) sidebarCardId.value = '';
        if (sidebarCardFront) sidebarCardFront.value = '';
        if (sidebarCardBack) sidebarCardBack.value = '';
        if (sidebarCardDue) sidebarCardDue.value = formatDateTimeForInput(new Date());
        if (sidebarCardError) sidebarCardError.classList.add('hidden');

        if (detLastReviewed) detLastReviewed.textContent = 'Never';
        if (detAccuracy) detAccuracy.textContent = '—';
        if (detSpeed) detSpeed.textContent = '—';
        if (detInterval) detInterval.textContent = '—';
        if (detReviews) detReviews.textContent = '0';
        if (detLapses) detLapses.textContent = '0';

        updatePositionLabel();
        setEditorReadOnly(false);
        if (sidebarCardFront) sidebarCardFront.focus();
    }

    if (openAddCardBtn) openAddCardBtn.addEventListener('click', () => {
        if (isDesktopView()) {
            setSidebarCollapsed(false);
            resetSidebarForCreate();
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
    // Local pagination: all cards stay in Tabulator; only the current page is rendered.
    // ---------------------------------------------------------------------------
    let gridReady = false;

    function updatePaginationUI() {
        if (!gridFooter || !tabulatorTable || !gridReady) return;

        const total = tabulatorTable.getDataCount('active');
        const currentPage = tabulatorTable.getPage() || 1;
        const size = tabulatorTable.getPageSize() || pageSize;
        const pages = Math.max(1, tabulatorTable.getPageMax() || 1);

        if (currentPage > pages) {
            tabulatorTable.setPage(pages).then(updatePaginationUI);
            return;
        }

        const start = total ? ((currentPage - 1) * size) + 1 : 0;
        const end = Math.min(currentPage * size, total);

        gridFooter.hidden = total === 0;

        if (rangeLabel) {
            rangeLabel.textContent = total
                ? `Showing ${start}–${end} of ${total} ${total === 1 ? 'card' : 'cards'} · Page ${currentPage} of ${pages}`
                : 'No cards to display';
        }

        if (pageSizeSelect && Number(pageSizeSelect.value) !== size) {
            pageSizeSelect.value = String(size);
        }

        if (paginationControls) {
            paginationControls.setAttribute(
                'aria-label',
                `Pagination, page ${currentPage} of ${pages}`
            );

            paginationControls.querySelectorAll('.tabulator-page').forEach((button) => {
                const isCurrent =
                    button.getAttribute('aria-current') === 'page' ||
                    button.classList.contains('active');

                if (isCurrent) button.setAttribute('aria-current', 'page');
            });
        }
    }

    if (pageSizeSelect) {
        pageSizeSelect.addEventListener('change', async () => {
            const requested = Number(pageSizeSelect.value);

            if (![10, 25, 50, 100].includes(requested) || !tabulatorTable || !gridReady) {
                return;
            }

            pageSize = requested;
            await tabulatorTable.setPageSize(pageSize);
            await tabulatorTable.setPage(1);
            updatePaginationUI();
        });
    }

    // 1. Tabulator Grid Setup
    const allColumns = [
        {
            title: "",
            field: "check", width: 44, resizable: false, frozen: true,
            headerSort: false, hozAlign: "center", headerHozAlign: "center", cssClass: "mp-check-cell",
            formatter: (cell) => {
                const id = cell.getData().id;
                const checked = checkedIds.has(Number(id)) ? 'checked' : '';
                return `<input type="checkbox" class="mp-check row-check" data-id="${id}" ${checked}>`;
            }
        },
        {
            title: "#",
            formatter: "rownum",
            hozAlign: "center", headerHozAlign: "center",
            width: 56, minWidth: 56, maxWidth: 80,
            resizable: false, frozen: true,
            cssClass: "mp-rownum"
        },

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

    // ---------------------------------------------------------------------------
    // Layout: table (default on wider screens) or tiles (default on phones).
    // bulk actions and the editor all keep working on the same rows.
    // ---------------------------------------------------------------------------
    const LAYOUT_KEY = 'mempulse.deckLayout';
    const mobileMQ = window.matchMedia('(max-width: 767px)');   // phone width: tiles unless the user chose otherwise
    const layoutToggle = document.getElementById('layout-toggle');
    let layoutMode = 'table';
    let needsRenumber = false;      // only set if this Tabulator build cannot watch row positions

    function readSavedLayout() {
        try {
            const v = window.localStorage.getItem(LAYOUT_KEY);
            return v === 'tiles' || v === 'table' ? v : null;
        } catch (_) { return null; }
    }
    function saveLayout(mode) {
        try { window.localStorage.setItem(LAYOUT_KEY, mode); } catch (_) { /* storage unavailable: choice lasts for this visit only */ }
    }
    let layoutChoice = readSavedLayout();   // explicit choice; null = follow the width-based default
    function resolveLayout() {
        return layoutChoice || (mobileMQ.matches ? 'tiles' : 'table');
    }
    function syncLayoutToggle() {
        if (!layoutToggle) return;
        layoutToggle.querySelectorAll('button[data-layout]').forEach((b) => {
            b.setAttribute('aria-pressed', String(b.dataset.layout === layoutMode));
        });
    }

    // ---- Tile rendering (DOM nodes + textContent, so card text is never parsed as HTML) ----
    function mk(tag, cls, text) {
        const n = document.createElement(tag);
        if (cls) n.className = cls;
        if (text !== undefined) n.textContent = text;
        return n;
    }

    function parseDbDate(value) {
        if (!value) return null;
        const d = new Date(String(value).replace(' ', 'T'));
        return isNaN(d.getTime()) ? null : d;
    }

    function dueChipInfo(value) {
        const t = parseDbDate(value);
        const diff = t ? t.getTime() - Date.now() : 0;
        if (diff <= 0) return { text: 'Due now', due: true };
        const mins = Math.max(1, Math.round(diff / 60000));
        if (mins < 60) return { text: `Due in ${mins}m`, due: false };
        const hours = Math.round(mins / 60);
        if (hours < 24) return { text: `Due in ${hours}h`, due: false };
        return { text: `Due in ${Math.round(hours / 24)}d`, due: false };
    }

    function mkChip(text, cls, icon) {
        const c = mk('span', 'mp-chip' + (cls ? ' ' + cls : ''));
        if (icon) c.appendChild(mk('span', 'material-symbols-outlined', icon));
        c.appendChild(document.createTextNode(text));
        return c;
    }

    function buildTile(cell) {
        const d = cell.getData();
        const row = cell.getRow();
        const tile = mk('div', 'mp-tile');

        const top = mk('div', 'mp-tile-top');
        const num = mk('span', 'mp-tile-num');
        if (typeof row.watchPosition === 'function') {
            row.watchPosition((pos) => { num.textContent = '#' + pos; });   // stays correct as cards are added / removed
        } else {
            needsRenumber = true;
            num.textContent = '#' + row.getPosition(true);
        }

        // Same hooks as the table's checkbox cell: .row-check feeds the delegated change handler,
        // .mp-check-cell keeps taps on the checkbox from opening the card.
        const checkWrap = mk('label', 'mp-check-cell mp-tile-check');
        const box = mk('input', 'mp-check row-check');
        box.type = 'checkbox';
        box.dataset.id = String(d.id);
        box.checked = checkedIds.has(Number(d.id));
        box.setAttribute('aria-label', 'Select card');
        checkWrap.appendChild(box);
        top.append(num, checkWrap);

        const meta = mk('div', 'mp-tile-meta');
        if (viewArchived) {
            const left = d.days_left;
            const urgent = left !== null && left !== undefined && Number(left) <= 3;
            meta.appendChild(mkChip(Archive.retentionLabel(left), urgent ? 'is-warn' : '', 'inventory_2'));
        } else {
            const due = dueChipInfo(d.next_review_at);
            meta.appendChild(mkChip(due.text, due.due ? 'is-due' : '', 'schedule'));
        }
        if (d.interval_days !== undefined && d.interval_days !== null) {
            const n = Number(d.interval_days);
            meta.appendChild(mkChip(`Every ${n} ${n === 1 ? 'day' : 'days'}`, '', 'update'));
        }
        if (Number(d.lapses) > 0) {
            const n = Number(d.lapses);
            meta.appendChild(mkChip(`${n} ${n === 1 ? 'lapse' : 'lapses'}`, 'is-due', 'replay'));
        }

        tile.append(top, mk('div', 'mp-tile-front', d.front || ''), mk('div', 'mp-tile-back', d.back || ''), meta);
        return tile;
    }

    const tileColumns = [{
        title: "",
        field: "id",
        resizable: false,
        headerSort: false,
        formatter: (cell) => buildTile(cell)
    }];

    function columnsFor(mode, archived) {
        if (mode === 'tiles') return tileColumns;
        return allColumns.filter((c) => c.field !== (archived ? 'next_review_at' : 'days_left'));
    }

    // ---- Grid construction (rebuildable) ----
    let renumberQueued = false;
    function renumberRows() {
        if (layoutMode === 'tiles' && !needsRenumber) return;   // tiles watch their own position
        if (renumberQueued || !tabulatorTable) return;
        renumberQueued = true;
        requestAnimationFrame(() => {
            renumberQueued = false;
            if (tabulatorTable) tabulatorTable.getRows().forEach((r) => r.reformat());
        });
    }

    // The table keeps Tabulator's virtual rendering; tiles need "basic" rendering because they are a CSS grid.
    // That option is fixed at construction, so changing layout means building a new grid from the same data.
    function createGrid(mode, list, onLoaded) {
        layoutMode = mode;
        gridReady = false;
        if (paginationControls) paginationControls.replaceChildren();
        gridContainer.classList.toggle('mp-tiles', mode === 'tiles');
        syncLayoutToggle();

        tabulatorTable = new Tabulator("#flashcards-tabulator-grid", {
            data: list,
            layout: mode === 'tiles' ? "fitColumns" : "fitDataFill",
            renderVertical: mode === 'tiles' ? "basic" : "virtual",
            height: "100%",                        // fills .mp-pane (replaces maxHeight)
            responsiveLayout: false,
            pagination: "local",
            paginationSize: pageSize,
            paginationButtonCount: 5,
            paginationElement: paginationControls,
            selectable: 1,                         // select-only: the grid has no cell editors
            selectableRollingSelection: true,
            resizableColumnFit: false,
            columnDefaults: { headerSort: false }, // keep navigation order predictable            placeholder: "No Flashcards Available",
            columns: columnsFor(mode, viewArchived),
        });

        tabulatorTable.on("tableBuilt", () => {
            gridReady = true;
            toggleStateVisibility(list.length > 0);
            updatePaginationUI();
            if (onLoaded) onLoaded();
        });

        tabulatorTable.on("pageLoaded", updatePaginationUI);
        tabulatorTable.on("dataProcessed", updatePaginationUI);

        // Desktop: row click fills the editor. Mobile/tablet: row click opens the modal.
        tabulatorTable.on("rowClick", (e, row) => {
            // Ignore clicks anywhere within the checkbox cell, not just the input
            if (e.target.closest('.mp-check-cell')) return;

            const cardData = row.getData();
            if (isDesktopView()) populateSidebar(cardData, row);
            else openModalForEdit(cardData);
        });

        tabulatorTable.on("rowAdded", () => {
            renumberRows();
            updatePaginationUI();
        });

        tabulatorTable.on("rowDeleted", () => {
            renumberRows();
            updatePaginationUI();
        });

        // A tile is drawn from the whole card, so redraw it whenever any field of the row changes
        tabulatorTable.on("rowUpdated", (row) => { if (layoutMode === 'tiles') row.reformat(); });
    }

    function selectFirstCardOnDesktop() {
        if (!isDesktopView()) return;
        const firstRow = tabulatorTable.getRows()[0];
        if (firstRow) {
            firstRow.select();
            populateSidebar(firstRow.getData(), firstRow, { expand: false });
        }
    }

    async function switchLayout(mode) {
        if (!gridContainer || !tabulatorTable || mode === layoutMode) { syncLayoutToggle(); return; }

        const list = tabulatorTable.getData();
        const selected = tabulatorTable.getSelectedRows();
        const keepId = selected.length ? Number(selected[0].getData().id) : null;

        tabulatorTable.destroy();
        selectedTabulatorRow = null;                               // pointed at a row of the destroyed grid

        // Put the highlight back on the same card. The editor is NOT repopulated, so unsaved typing survives.
        const restoreSelection = async () => {
            if (keepId === null) return;
            const idx = list.findIndex((c) => Number(c.id) === keepId);
            if (idx === -1) return;
            const targetPage =
                Math.floor(idx / Math.max(1, tabulatorTable.getPageSize())) + 1;

            if (targetPage !== tabulatorTable.getPage()) {
                await tabulatorTable.setPage(targetPage);
            }

            const row = tabulatorTable.getRows('all')[idx];
            if (!row) return;
            row.select();
            if (selectedSidebarCardId === keepId) selectedTabulatorRow = row;
            Promise.resolve(row.scrollTo('nearest', false)).catch(() => { });
            updatePositionLabel();
        };
        createGrid(mode, list, restoreSelection);
    }

    if (layoutToggle) layoutToggle.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-layout]');
        if (!btn) return;
        layoutChoice = btn.dataset.layout;      // an explicit choice always wins over the width-based default
        saveLayout(layoutChoice);
        switchLayout(layoutChoice);
    });

    // Without an explicit choice, follow the screen: tiles at phone width, table otherwise
    mobileMQ.addEventListener('change', () => {
        if (layoutChoice === null) switchLayout(resolveLayout());
    });

    if (gridContainer) createGrid(resolveLayout(), initialFlashcards, selectFirstCardOnDesktop);

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
                if (tabulatorTable) await tabulatorTable.addRow(res.card, true);
            } else {
                const row = tabulatorTable ? tabulatorTable.getRow(Number(res.card.id)) : null;
                if (row) await row.update(res.card);
                if (isSidebar && isDesktopView()) {
                    populateSidebar(row ? row.getData() : res.card, row || null);   // row data keeps accuracy/speed/etc.
                }
            }

            applyCounts(res.counts);
            if (gridReady) updatePaginationUI();
            if (action === 'add') {
                // Fast entry: clear the fields and put the cursor back on the front side
                if (isSidebar) resetSidebarForCreate();
                else resetModalForCreate();
            } else if (!isSidebar && cardModal) {
                cardModal.close();
            }
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
            const res = await sendCardAction({ action: 'archive', card_id: card.id });
            removeRow(card.id);
            offscreenList.unshift({ ...card, archived_at: new Date().toISOString(), days_left: Archive.RETENTION_DAYS });
            totalCards--;
            archivedCards++;
            applyCounts(res.counts);
            Archive.toast('Card archived', { actionLabel: 'Undo', onAction: () => restoreCard(card.id) });
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        }
    }

    async function restoreCard(cardId) {
        try {
            const res = await sendCardAction({ action: 'restore', card_id: cardId });
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
            applyCounts(res.counts);
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        }
    }

    function setViewMode(archived) {
        viewArchived = archived;

        if (archiveBanner) archiveBanner.classList.toggle('hidden', !archived);
        if (openAddCardBtn) openAddCardBtn.classList.toggle('hidden', archived);
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
            if (layoutMode === 'table') tabulatorTable.setColumns(columnsFor('table', archived));

            const current = tabulatorTable.getData();
            const next = offscreenList;
            offscreenList = current;
            selectedSidebarCardId = null;
            selectedTabulatorRow = null;

            tabulatorTable.setData(next).then(() => {
                tabulatorTable.setPage(1);
                tabulatorTable.redraw(true);
                updatePaginationUI();

                const first = tabulatorTable.getRows('all')[0];

                if (first && isDesktopView() && !viewArchived) {
                    first.select();
                    populateSidebar(first.getData(), first, { expand: false });
                }
            });
        }
        updateCardCountLabel();
    }

    if (toggleArchiveBtn) toggleArchiveBtn.addEventListener('click', () => setViewMode(!viewArchived));

    // Inside DOMContentLoaded
    let editMode = false;
    const otherDecks = config.otherDecks || [];

    // Elements
    const bulkSelectAll = document.getElementById('bulk-select-all');
    const bulkPanel = document.getElementById('bulk-panel');
    const bulkStatus = document.getElementById('bulk-status');
    const PRACTICE_MAX = 300;   // ids travel in the URL; review.php also cuts the list at 300
    const practiceBtn = document.getElementById('bulk-practice-btn');
    const PDF_MAX = 300;       // ids travel in the URL; keep in sync with PDF_REVIEWER_MAX_CARDS in pdf-reviewer.php
    const pdfBtn = document.getElementById('bulk-pdf-btn');
    const bulkActionBtns = [
        pdfBtn,
        practiceBtn,
        document.getElementById('bulk-duplicate-btn'),
        document.getElementById('bulk-move-btn'),
        document.getElementById('bulk-archive-btn'),
        document.getElementById('bulk-reset-btn')
    ];
    const archiveActionBtns = [
        document.getElementById('bulk-restore-btn'),
        document.getElementById('bulk-delete-btn')
    ];
    const bulkClearBtn = document.getElementById('bulk-done-btn');
    const bulkDoneBtn = bulkClearBtn;


    // 1. Toggle Edit Mode
    function setEditMode(active) {
        editMode = active;
        if (!active) {
            checkedIds.clear();
        }
        updateBulkUI();

        if (bulkPanel) bulkPanel.classList.toggle('is-open', active);
        if (workspace) workspace.classList.toggle('workspace-editing', active);

        // Reformat rows if we just turned edit mode off to uncheck boxes visually
        if (!active && tabulatorTable) {
            tabulatorTable.getRows().forEach(r => r.reformat());
        }
    }

    if (bulkDoneBtn) bulkDoneBtn.addEventListener('click', () => setEditMode(false));

    // Wrap setViewMode: selections never carry across views, and the archive has no editor
    const originalSetViewMode = setViewMode;
    setViewMode = function (archived) {
        checkedIds.clear();                 // ids differ between the two views
        originalSetViewMode(archived);
        if (archived) setSidebarCollapsed(true);
        updateBulkUI();
    };

    // 2. Selection Logic
    function updateBulkUI() {
        const total = tabulatorTable ? tabulatorTable.getDataCount('active') : 0; const selected = checkedIds.size;
        const hasSelection = selected > 0;

        const selectAll = document.getElementById('bulk-select-all');
        const selectWrapper = document.getElementById('bulk-select-wrapper');
        const selectSeparator = document.getElementById('bulk-select-separator');

        // Select all / divider / Clear only appear once something is ticked
        if (selectWrapper) selectWrapper.classList.toggle('hidden', !hasSelection);
        if (selectSeparator) selectSeparator.classList.toggle('hidden', !hasSelection);
        if (selectAll) {
            selectAll.indeterminate = false;
            selectAll.checked = hasSelection && selected === total;
        }
        if (bulkClearBtn) bulkClearBtn.hidden = !hasSelection;
        if (bulkStatus) bulkStatus.textContent = `${selected} selected`;

        // Panel buttons depend on the view
        bulkActionBtns.forEach((btn) => { if (btn) { btn.hidden = viewArchived; btn.disabled = !hasSelection; } });
        archiveActionBtns.forEach((btn) => { if (btn) { btn.hidden = !viewArchived; btn.disabled = !hasSelection; } });

        // Practice: needs a selection, and at most PRACTICE_MAX cards
        // PDF: needs a selection, and at most PDF_MAX cards
        if (pdfBtn) {
            if (selected > PDF_MAX) {
                pdfBtn.disabled = true;
                pdfBtn.title = `Select up to ${PDF_MAX} cards`;
            } else {
                pdfBtn.title = 'Download a printable PDF of the selected cards';
            }
        }

        if (practiceBtn) {
            if (selected > PRACTICE_MAX) {
                practiceBtn.disabled = true;
                practiceBtn.title = `Select up to ${PRACTICE_MAX} cards`;
            } else {
                practiceBtn.title = 'Practice selected cards (nothing is saved)';
            }
        }


        const moveBtn = document.getElementById('bulk-move-btn');
        if (moveBtn) {
            if (otherDecks.length === 0) {
                moveBtn.disabled = true;
                moveBtn.title = "No other decks available";
            } else {
                moveBtn.title = "Move to deck...";
            }
        }

        // Card editor: locked in the archive, and until a checkbox is ticked
        if (selectedSidebarCardId !== null) setEditorReadOnly(viewArchived || !hasSelection);
    }


    // Unified delegated listener for both Select All and Row checkboxes
    document.addEventListener('change', (e) => {
        // Handle Bulk Panel "Select All"
        if (e.target.id === 'bulk-select-all') {
            const isChecked = e.target.checked;
            checkedIds.clear();

            if (isChecked && tabulatorTable) {
                const allIds = tabulatorTable.getData().map(r => Number(r.id));
                allIds.forEach(id => checkedIds.add(id));
            }

            updateBulkUI();
            if (tabulatorTable) tabulatorTable.getRows().forEach(r => r.reformat());
        }
        // Handle Individual Row Checks
        else if (e.target.classList.contains('row-check')) {
            const id = Number(e.target.dataset.id);
            if (e.target.checked) checkedIds.add(id);
            else checkedIds.delete(id);
            updateBulkUI();
        }
    });


    // 3. API Handlers
    document.getElementById('bulk-duplicate-btn').addEventListener('click', async () => {
        const ids = Array.from(checkedIds);
        if (!ids.length) return;
        try {
            bulkActionBtns.forEach(b => b.disabled = true);
            const res = await sendCardAction({ action: 'bulk_duplicate', card_ids: ids });

            // Add to top of grid
            if (tabulatorTable) {
                res.cards.reverse().forEach(card => tabulatorTable.addRow(card, true));
            }
            totalCards += res.cards.length;
            applyCounts(res.counts);
            Archive.toast(`${res.cards.length} cards duplicated`);
            setEditMode(false);
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        } finally {
            updateBulkUI();
        }
    });

    // Move
    const moveDialog = document.getElementById('move-cards-dialog');
    const moveDeckSelect = document.getElementById('move-deck-select');

    document.getElementById('bulk-move-btn').addEventListener('click', () => {
        if (otherDecks.length === 0 || checkedIds.size === 0) return;
        moveDeckSelect.innerHTML = otherDecks.map(d => `<option value="${d.id}">${d.title}</option>`).join('');
        moveDialog.showModal();
    });

    document.getElementById('confirm-move-btn').addEventListener('click', async () => {
        const ids = Array.from(checkedIds);
        const targetDeck = Number(moveDeckSelect.value);
        try {
            document.getElementById('confirm-move-btn').disabled = true;
            const res = await sendCardAction({ action: 'bulk_move', card_ids: ids, target_deck_id: targetDeck });

            ids.forEach(id => {
                const row = tabulatorTable.getRow(id);
                if (row) row.delete();

                // If moving the actively viewed card, jump to next or clear
                if (selectedSidebarCardId === id && isDesktopView()) {
                    const firstRow = tabulatorTable.getRows()[0];
                    if (firstRow) activateRow(firstRow, { expand: false });
                    else setSidebarCollapsed(true);
                }
            });

            totalCards -= res.affected;
            applyCounts(res.counts);
            Archive.toast(`${res.affected} cards moved`);
            setEditMode(false);
            moveDialog.close();
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        } finally {
            document.getElementById('confirm-move-btn').disabled = false;
        }
    });

    // Bulk Archive
    document.getElementById('bulk-archive-btn').addEventListener('click', async () => {
        const ids = Array.from(checkedIds);
        if (!ids.length) return;
        try {
            bulkActionBtns.forEach(b => b.disabled = true);
            const res = await sendCardAction({ action: 'bulk_archive', card_ids: ids });

            ids.forEach(id => {
                const row = tabulatorTable.getRow(id);
                if (row) {
                    const data = row.getData();
                    offscreenList.unshift({ ...data, archived_at: new Date().toISOString(), days_left: Archive.RETENTION_DAYS });
                    row.delete();
                }

                // Keep editor in sync if we archived the currently viewed card
                if (selectedSidebarCardId === id && isDesktopView()) {
                    const firstRow = tabulatorTable.getRows()[0];
                    if (firstRow) activateRow(firstRow, { expand: false });
                    else setSidebarCollapsed(true);
                }
            });

            totalCards -= res.affected;
            archivedCards += res.affected;
            applyCounts(res.counts);
            Archive.toast(`${res.affected} cards archived`);
            setEditMode(false);
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        } finally {
            updateBulkUI();
        }
    });

    // Practice: replay the selected cards in the review screen. Nothing is saved.
    // (Reports what it does, so a silent failure can be told apart from a server-side one.)
    if (practiceBtn) {
        practiceBtn.addEventListener('click', () => {
            try {
                if (viewArchived) return;
                // Grid order, not click order (loaded rows first, then the ones not rendered yet)
                const gridOrder = tabulatorTable.getData().map(r => Number(r.id));
                const ids = gridOrder.filter(id => checkedIds.has(id));
                console.log('[practice] selected:', Array.from(checkedIds), 'in grid order:', ids);
                if (ids.length === 0) {
                    Archive.toast('Practice: no matching selected cards found', { type: 'error' });
                    return;
                }
                if (ids.length > PRACTICE_MAX) {
                    Archive.toast(`Select up to ${PRACTICE_MAX} cards`, { type: 'error' });
                    return;
                }
                const url = `review.php?deck_id=${DECK_ID}&mode=practice&from=deck&cards=${ids.join(',')}`;
                console.log('[practice] opening', url);
                window.location.assign(url);
            } catch (err) {
                console.error('[practice] failed', err);
                Archive.toast('Practice failed: ' + err.message, { type: 'error' });
            }
        });
    }

    // PDF reviewer: open the printable page in a new tab. Read-only, nothing is saved.
    if (pdfBtn) {
        pdfBtn.addEventListener('click', () => {
            try {
                if (viewArchived) return;
                // Grid order, not click order
                const gridOrder = tabulatorTable.getData().map(r => Number(r.id));
                const ids = gridOrder.filter(id => checkedIds.has(id));
                if (ids.length === 0) {
                    Archive.toast('PDF: no matching selected cards found', { type: 'error' });
                    return;
                }
                if (ids.length > PDF_MAX) {
                    Archive.toast(`Select up to ${PDF_MAX} cards`, { type: 'error' });
                    return;
                }
                const url = `pdf-reviewer.php?deck_id=${DECK_ID}&cards=${ids.join(',')}`;
                const win = window.open(url, '_blank');
                if (win) {
                    win.opener = null;                 // same-origin page, but it has no need for us
                } else {
                    window.location.assign(url);       // popup blocked: open in this tab instead
                }
            } catch (err) {
                console.error('[pdf] failed', err);
                Archive.toast('PDF failed: ' + err.message, { type: 'error' });
            }
        });
    }

    // Reset
    const resetDialog = document.getElementById('reset-cards-dialog');
    document.getElementById('bulk-reset-btn').addEventListener('click', () => {
        if (checkedIds.size === 0) return;
        resetDialog.showModal();
    });

    document.getElementById('confirm-reset-btn').addEventListener('click', async () => {
        const ids = Array.from(checkedIds);
        try {
            document.getElementById('confirm-reset-btn').disabled = true;
            const res = await sendCardAction({ action: 'bulk_reset', card_ids: ids });

            // Client side reset visualization
            const now = formatDateTimeDisplay(new Date());
            ids.forEach(id => {
                const row = tabulatorTable.getRow(id);
                if (row) {
                    const data = row.getData();
                    row.update({
                        interval_days: 1, // assumption matching DB fallback
                        next_review_at: new Date().toISOString(),
                        review_count: 0,
                        lapses: 0,
                        accuracy: null,
                        avg_response_ms: null,
                        last_reviewed_at: null
                    });
                    if (selectedSidebarCardId === id && isDesktopView()) populateSidebar(row.getData(), row);
                }
            });

            applyCounts(res.counts);   // reset makes the cards due immediately
            Archive.toast(`Progress reset for ${res.affected} cards`);
            setEditMode(false);
            resetDialog.close();
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        } finally {
            document.getElementById('confirm-reset-btn').disabled = false;
        }
    });

    // ---------------------------------------------------------------------------
    // Archive view: bulk Restore / Delete permanently
    // ---------------------------------------------------------------------------
    function takeCards(ids) {
        const taken = [];

        ids.forEach((id) => {
            const row = tabulatorTable.getRow(id);

            if (row) {
                taken.push(row.getData());
                row.delete();
            }
        });

        return taken;
    }

    document.getElementById('bulk-restore-btn').addEventListener('click', async () => {
        const ids = Array.from(checkedIds);
        if (!viewArchived || !ids.length) return;
        try {
            archiveActionBtns.forEach((b) => b.disabled = true);
            const res = await sendCardAction({ action: 'bulk_restore', card_ids: ids });
            const taken = takeCards(ids);
            // while viewing the archive, offscreenList holds the active cards
            offscreenList.unshift(...taken.map((c) => ({ ...c, archived_at: null, days_left: null })));
            totalCards += res.affected;
            archivedCards -= res.affected;
            checkedIds.clear();
            applyCounts(res.counts);
            if (gridReady) updatePaginationUI();
            Archive.toast(`${res.affected} ${res.affected === 1 ? 'card' : 'cards'} restored`);
        } catch (err) {
            Archive.toast(err.message, { type: 'error' });
        } finally {
            updateBulkUI();
        }
    });

    document.getElementById('bulk-delete-btn').addEventListener('click', () => {
        const n = checkedIds.size;
        if (!viewArchived || !n) return;
        const msg = document.getElementById('delete-card-message');
        if (msg) msg.textContent = `${n} ${n === 1 ? 'card' : 'cards'} will be permanently deleted. This action cannot be undone.`;
        if (deleteCardConfirm) deleteCardConfirm.textContent = n === 1 ? 'Delete Card' : `Delete ${n} Cards`;
        if (deleteCardError) deleteCardError.classList.add('hidden');
        if (deleteCardDialog) deleteCardDialog.showModal();
    });

    if (deleteCardConfirm) deleteCardConfirm.addEventListener('click', async () => {
        const ids = Array.from(checkedIds);
        if (!ids.length) return;
        deleteCardConfirm.disabled = true;
        try {
            const res = await sendCardAction({ action: 'bulk_delete_permanent', card_ids: ids });
            takeCards(ids);
            archivedCards -= res.affected;
            checkedIds.clear();
            applyCounts(res.counts);
            if (gridReady) updatePaginationUI();
            if (deleteCardDialog) deleteCardDialog.close();
            Archive.toast(`${res.affected} ${res.affected === 1 ? 'card' : 'cards'} deleted permanently`, { type: 'error' });
        } catch (err) {
            if (deleteCardError) {
                deleteCardError.textContent = err.message;
                deleteCardError.classList.remove('hidden');
            }
        } finally {
            deleteCardConfirm.disabled = false;
            updateBulkUI();
        }
    });

    updateBulkUI();   // initial render of the panel (must stay last)
});