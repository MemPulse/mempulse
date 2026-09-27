<?php
session_start();

// Enforce login authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id   = intval($_SESSION['user_id'] ?? 0);
$firstName = $_SESSION['first_name'] ?? 'User';
$lastName  = $_SESSION['last_name'] ?? '';
$fullName  = trim($firstName . ' ' . $lastName);
$initial   = strtoupper(substr($firstName, 0, 1));

// Include db.php safely
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
} elseif (file_exists(__DIR__ . '/../../config/db.php')) {
    require_once __DIR__ . '/../../config/db.php';
} elseif (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
} else {
    die("Database configuration file (db.php) could not be located.");
}

// Fetch active decks server-side for initial render
$sidebar_decks = [];
if ($user_id > 0) {
    $sidebar_stmt = $conn->prepare("SELECT id, title FROM decks WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at ASC");
    $sidebar_stmt->bind_param("i", $user_id);
    $sidebar_stmt->execute();
    $sidebar_result = $sidebar_stmt->get_result();
    while ($row = $sidebar_result->fetch_assoc()) {
        $sidebar_decks[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemPulse - Dashboard</title>
    <link rel="stylesheet" href="./styles/style.css">
</head>

<body>

    <!-- LEFT SIDEBAR -->
    <aside>
        <div>
            <div class="brand">MemPulse</div>
            <ul class="nav-list">
                <li><a href="index.php" class="nav-item active"> <span class="nav-icon">🏠</span> Home </a></li>
                <li><a href="decks.php" class="nav-item"> <span class="nav-icon">📚</span> Decks </a></li>
            </ul>

            <!-- Sidebar Quick Decks Header with Shortcut -->
            <div class="sidebar-section-title" style="display: flex; justify-content: space-between; align-items: center; padding-right: 16px;">
                <span>Quick Decks</span>
                <button onclick="openCreateDeckModal()" style="background: none; border: none; cursor: pointer; color: var(--text-secondary); font-size: 0.9rem;" title="Create New Deck">＋</button>
            </div>
            <ul class="nav-list" id="quickDecksList">
                <?php foreach ($sidebar_decks as$deck): ?>
                <li>
                    <a href="decks.php?deck_id=<?= $deck['id'] ?>" class="nav-item">
                        <span class="nav-icon">🗂️</span>
                        <?= htmlspecialchars($deck['title']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <?php if (empty($sidebar_decks)): ?>
                <li style="padding: 0 16px; font-size: 0.8rem; color: var(--text-secondary);">No decks available.</li>
                <?php endif; ?>
            </ul>

            <div class="sidebar-section-title">Resources</div>
            <ul class="nav-list">
                <li><a href="science.php" class="nav-item"> <span class="nav-icon">💡</span> Science </a></li>
                <li><a href="settings.php" class="nav-item"> <span class="nav-icon">⚙️</span> Settings </a></li>
            </ul>
        </div>

        <div class="user-profile">
            <div class="avatar" id="userAvatar"><?php echo htmlspecialchars($initial); ?></div>
            <div style="font-size: 0.85rem; color: var(--text-secondary);" id="userName"><?php echo htmlspecialchars($fullName); ?></div>
        </div>
    </aside>

    <!-- MAIN DASHBOARD CONTENT -->
    <main>
        <!-- Header with dual action buttons -->
        <div class="welcome-header">
            <h1 id="welcomeTitle">Welcome back, <?php echo htmlspecialchars($firstName); ?> 👋</h1>
            <div style="display: flex; gap: 10px;">
                <button class="btn-secondary" style="border-radius: var(--radius-pill); font-weight: 600;" onclick="openCreateDeckModal()">＋ New Deck</button>
                <button class="btn-create" onclick="openCreateCardModal()">＋ Add Flashcard</button>
            </div>
        </div>

        <!-- Motivational Quote -->
        <div class="quote-card">
            "Repetition is the mother of learning, the father of action, which makes it the architect of accomplishment."
            <span class="quote-author">— Zig Ziglar</span>
        </div>

        <!-- Dynamic Stats Row -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-info">
                    <h3 id="statStreak">0 Days</h3>
                    <p>Study Streak</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🎴</div>
                <div class="stat-info">
                    <h3 id="statTotalCards">0</h3>
                    <p>Total Cards</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-info">
                    <h3 id="statDueCards">0</h3>
                    <p>Cards Due Today</p>
                </div>
            </div>
        </div>

        <!-- Main Grid: Due Sessions & Recent Logs -->
        <div class="dashboard-grid">
            <!-- Due Deck Sessions Section -->
            <div class="section-card">
                <h2>Decks Ready for Review</h2>
                <ul class="item-list" id="dueDecksList">
                    <li class="list-row" style="justify-content: center; color: var(--text-secondary);">
                        Loading due sessions...
                    </li>
                </ul>
            </div>

            <!-- Recent Session Logs Section -->
            <div class="section-card">
                <h2>Recent Activity Logs</h2>
                <ul class="item-list" id="recentLogsList">
                    <li class="list-row" style="justify-content: center; color: var(--text-secondary);">
                        Loading activity logs...
                    </li>
                </ul>
            </div>
        </div>
    </main>

    <!-- 1. CREATE FLASHCARD MODAL -->
    <div id="createCardModal" class="modal-overlay" style="display: none; z-index: 1000;">
        <div class="modal-card">
            <div class="modal-header">
                <h2>Create New Flashcard</h2>
                <button type="button" class="modal-close-btn" onclick="closeCreateCardModal()">✕</button>
            </div>

            <div id="modalStatus" class="modal-status"></div>

            <form id="createCardForm" onsubmit="handleCreateCard(event)">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="modalDeckSelect">Target Deck</label>
                    <div style="display: flex; gap: 8px;">
                        <select id="modalDeckSelect" class="form-control" required style="flex: 1;">
                            <?php if (!empty($sidebar_decks)): ?>
                                <?php foreach ($sidebar_decks as$deck): ?>
                                    <option value="<?= $deck['id'] ?>"><?= htmlspecialchars($deck['title']) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No decks found. Create one!</option>
                            <?php endif; ?>
                        </select>
                        <button type="button" class="btn-secondary" onclick="openCreateDeckModal(true)" style="padding: 0 12px; white-space: nowrap;">＋ New</button>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="modalFront">Front Plaintext</label>
                    <textarea id="modalFront" class="form-control" placeholder="Enter front prompt..." required></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="modalBack">Back Plaintext</label>
                    <textarea id="modalBack" class="form-control" placeholder="Enter back answer..." required></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeCreateCardModal()">Done / Close</button>
                    <button type="submit" class="btn-create" id="btnSaveCard">Add Flashcard</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. CREATE DECK MODAL -->
    <div id="createDeckModal" class="modal-overlay" style="display: none; z-index: 1100;">
        <div class="modal-card" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Create New Deck</h2>
                <button type="button" class="modal-close-btn" onclick="closeCreateDeckModal()">✕</button>
            </div>

            <div id="deckModalStatus" class="modal-status"></div>

            <form id="createDeckForm" onsubmit="handleCreateDeck(event)">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="deckTitleInput">Deck Title</label>
                    <input type="text" id="deckTitleInput" class="form-control" placeholder="e.g. Spanish Vocabulary, Biology 101..." required />
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeCreateDeckModal()">Cancel</button>
                    <button type="submit" class="btn-create" id="btnSaveDeck">Create Deck</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    function formatTimeAgo(dateString) {
        if (!dateString) return 'Recently';
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} mins ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} hours ago`;
        if (diffInSeconds < 172800) return 'Yesterday';
        return `${Math.floor(diffInSeconds / 86400)} days ago`;
    }

    let isCreatingDeckFromCardModal = false;

    function openCreateDeckModal(fromCardModal = false) {
        isCreatingDeckFromCardModal = fromCardModal;
        const modal = document.getElementById('createDeckModal');
        const status = document.getElementById('deckModalStatus');
        
        status.className = 'modal-status';
        status.style.display = 'none';
        document.getElementById('createDeckForm').reset();
        
        modal.style.display = 'flex';
        setTimeout(() => {
            document.getElementById('deckTitleInput').focus();
        }, 50);
    }

    function closeCreateDeckModal() {
        document.getElementById('createDeckModal').style.display = 'none';
        document.getElementById('createDeckForm').reset();
        isCreatingDeckFromCardModal = false;
    }

    async function handleCreateDeck(e) {
        if (e) e.preventDefault();

        const titleInput = document.getElementById('deckTitleInput');
        const title = titleInput.value.trim();
        const status = document.getElementById('deckModalStatus');
        const saveBtn = document.getElementById('btnSaveDeck');

        if (!title) return;

        saveBtn.disabled = true;
        saveBtn.innerText = 'Creating...';

        try {
            const res = await fetch('api_review.php?action=create_deck', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: title })
            });

            const data = await res.json();

            if (data.status === 'success') {
                closeCreateDeckModal();
                
                // Refresh sidebar quick decks & dashboard stats
                await loadDashboardStats();

                // Refresh target deck select dropdown inside Flashcard Modal
                const select = document.getElementById('modalDeckSelect');
                if (select) {
                    const resDecks = await fetch('api_review.php?action=get_user_decks');
                    const dataDecks = await resDecks.json();

                    if (dataDecks.status === 'success' && dataDecks.decks) {
                        select.innerHTML = dataDecks.decks.map(d => `<option value="${d.id}">${escapeHtml(d.title)}</option>`).join('');
                        select.value = data.deck.id;
                    }
                }

                // If triggered from Flashcard Modal, return focus to card input
                if (isCreatingDeckFromCardModal) {
                    document.getElementById('modalFront').focus();
                }
            } else {
                status.className = 'modal-status error';
                status.innerText = data.message || 'Error creating deck.';
            }
        } catch (err) {
            status.className = 'modal-status error';
            status.innerText = 'Network error while creating deck.';
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerText = 'Create Deck';
        }
    }

    async function loadDashboardStats() {
        try {
            const response = await fetch('api_review.php?action=get_dashboard_stats');
            const data = await response.json();

            if (data.status === 'success') {
                document.getElementById('statStreak').innerText = `${data.stats.streak} Days`;
                document.getElementById('statTotalCards').innerText = data.stats.total_cards;
                document.getElementById('statDueCards').innerText = data.stats.due_today;

                const quickList = document.getElementById('quickDecksList');
                if (data.quick_decks) {
                    if (data.quick_decks.length === 0) {
                        quickList.innerHTML = `<li style="padding: 0 16px; font-size: 0.8rem; color: var(--text-secondary);">No decks available.</li>`;
                    } else {
                        quickList.innerHTML = data.quick_decks.map(deck => `
                            <li>
                                <a href="decks.php?deck_id=${deck.id}" class="nav-item">
                                    <span class="nav-icon">🗂️</span> ${escapeHtml(deck.title)}
                                </a>
                            </li>
                        `).join('');
                    }
                }
            }
        } catch (err) {
            console.error('Error loading dashboard stats:', err);
        }
    }

    async function loadDueDecks() {
        const listContainer = document.getElementById('dueDecksList');

        try {
            const response = await fetch('api_review.php?action=get_due_decks');
            const data = await response.json();

            if (!data.decks || data.decks.length === 0) {
                listContainer.innerHTML = `
                    <li class="list-row" style="justify-content: center; padding: 16px;">
                        <div style="color: var(--text-secondary);">🎉 All caught up! No decks due for review.</div>
                    </li>`;
                return;
            }

            listContainer.innerHTML = data.decks.map(deck => `
                <li class="list-row">
                    <div class="list-row-info">
                        <div>${escapeHtml(deck.deck_title)}</div>
                        <span>${deck.due_count} card${deck.due_count > 1 ? 's' : ''} ready</span>
                    </div>
                    <a href="review.php?deck_id=${deck.deck_id}" class="btn-study">Start Review</a>
                </li>
            `).join('');

        } catch (err) {
            console.error('Error fetching due decks:', err);
            listContainer.innerHTML = `
                <li class="list-row" style="color: var(--danger-color); justify-content: center;">
                    Failed to load due decks.
                </li>`;
        }
    }

    async function loadRecentLogs() {
        const listContainer = document.getElementById('recentLogsList');

        try {
            const response = await fetch('api_review.php?action=get_recent_logs');
            const data = await response.json();

            if (!data.logs || data.logs.length === 0) {
                listContainer.innerHTML = `
                    <li class="list-row" style="justify-content: center; padding: 16px;">
                        <div style="color: var(--text-secondary);">No recent study activity recorded.</div>
                    </li>`;
                return;
            }

            listContainer.innerHTML = data.logs.map(log => {
                const accuracy = parseFloat(log.accuracy_percentage || 0).toFixed(1);
                return `
                    <li class="list-row">
                        <div class="list-row-info">
                            <div>${escapeHtml(log.deck_title)}</div>
                            <span>${formatTimeAgo(log.ended_at)} • ${log.total_reviewed} cards reviewed</span>
                        </div>
                        <span class="badge-accuracy">${accuracy}% Acc.</span>
                    </li>
                `;
            }).join('');

        } catch (err) {
            console.error('Error fetching recent logs:', err);
            listContainer.innerHTML = `
                <li class="list-row" style="color: var(--danger-color); justify-content: center;">
                    Failed to load recent logs.
                </li>`;
        }
    }

    // --- MODAL CONTROLS ---
    async function openCreateCardModal() {
        const modal = document.getElementById('createCardModal');
        const select = document.getElementById('modalDeckSelect');
        const status = document.getElementById('modalStatus');

        status.className = 'modal-status';
        status.style.display = 'none';
        modal.style.display = 'flex';

        try {
            const res = await fetch('api_review.php?action=get_user_decks');
            const data = await res.json();

            if (data.status === 'success' && data.decks && data.decks.length > 0) {
                const currentSelected = select.value;
                select.innerHTML = data.decks.map(d => `<option value="${d.id}">${escapeHtml(d.title)}</option>`).join('');
                if (currentSelected) select.value = currentSelected;
            }
        } catch (err) {
            console.warn('Could not refresh deck options dynamically, using pre-rendered options.', err);
        }

        setTimeout(() => {
            document.getElementById('modalFront').focus();
        }, 50);
    }

    function closeCreateCardModal() {
        document.getElementById('createCardModal').style.display = 'none';
        document.getElementById('createCardForm').reset();
        loadDashboardStats();
        loadDueDecks();
    }

  async function handleCreateDeck(e) {
    if (e) e.preventDefault();

    const titleInput = document.getElementById('deckTitleInput');
    const title = titleInput.value.trim();
    const status = document.getElementById('deckModalStatus');
    const saveBtn = document.getElementById('btnSaveDeck');

    if (!title) {
        status.className = 'modal-status error';
        status.innerText = 'Please enter a deck title.';
        return;
    }

    saveBtn.disabled = true;
    saveBtn.innerText = 'Creating...';

    try {
        // Send as FormData so standard PHP $_POST reads it seamlessly
        const formData = new FormData();
        formData.append('title', title);

        const res = await fetch('api_review.php?action=create_deck', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.status === 'success') {
            closeCreateDeckModal();
            
            // Refresh dashboard counters & sidebar
            await loadDashboardStats();

            // Refresh target deck dropdown in Flashcard Modal
            const select = document.getElementById('modalDeckSelect');
            if (select) {
                const resDecks = await fetch('api_review.php?action=get_user_decks');
                const dataDecks = await resDecks.json();

                if (dataDecks.status === 'success' && dataDecks.decks) {
                    select.innerHTML = dataDecks.decks.map(d => `<option value="${d.id}">${escapeHtml(d.title)}</option>`).join('');
                    select.value = data.deck ? data.deck.id : data.deck_id;
                }
            }

            if (isCreatingDeckFromCardModal) {
                document.getElementById('modalFront').focus();
            }
        } else {
            status.className = 'modal-status error';
            status.innerText = data.message || 'Error creating deck.';
        }
    } catch (err) {
        console.error('Deck creation error:', err);
        status.className = 'modal-status error';
        status.innerText = 'Server error. Check console for details.';
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Create Deck';
    }
}

async function handleCreateCard(e) {
    if (e) e.preventDefault();

    const deckId = document.getElementById('modalDeckSelect').value;
    const front = document.getElementById('modalFront').value.trim();
    const back = document.getElementById('modalBack').value.trim();
    const status = document.getElementById('modalStatus');
    const saveBtn = document.getElementById('btnSaveCard');

    if (!deckId || !front || !back) {
        status.className = 'modal-status error';
        status.innerText = 'Please select a deck and fill in both plaintext fields.';
        return;
    }

    saveBtn.disabled = true;
    saveBtn.innerText = 'Adding...';

    try {
        const formData = new FormData();
        formData.append('deck_id', deckId);
        formData.append('front', front);
        formData.append('back', back);

        const res = await fetch('api_review.php?action=add_card', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.status === 'success') {
            status.className = 'modal-status success';
            status.innerText = '✓ Flashcard added! You can add another below.';

            document.getElementById('modalFront').value = '';
            document.getElementById('modalBack').value = '';
            document.getElementById('modalFront').focus();
        } else {
            status.className = 'modal-status error';
            status.innerText = data.message || 'Error adding card.';
        }
    } catch (err) {
        console.error('Card creation error:', err);
        status.className = 'modal-status error';
        status.innerText = 'Failed to submit card. Check database/connection.';
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Add Flashcard';
    }
}

    document.addEventListener('DOMContentLoaded', () => {
        loadDashboardStats();
        loadDueDecks();
        loadRecentLogs();
    });
    </script>
</body>

</html>