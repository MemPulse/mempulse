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
    <style>
        :root {
            --bg-main: #f8fafd;
            --bg-sidebar: #f0f4f9;
            --bg-card: #ffffff;
            --text-primary: #1f1f1f;
            --text-secondary: #444746;
            --accent-color: #0b57d0;
            --accent-soft: #e8f0fe;
            --border-color: #e0e2e0;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-pill: 28px;
            --sidebar-width: 260px;
            --danger-color: #c5221f;
            --success-color: #146c2e;
            --success-soft: #e6f4ea;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            display: flex;
            height: 100vh;
            background-color: var(--bg-main);
            color: var(--text-primary);
            overflow: hidden;
        }

        /* --- SIDEBAR --- */
        aside {
            width: var(--sidebar-width);
            background-color: var(--bg-sidebar);
            padding: 16px 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-right: 1px solid var(--border-color);
        }

        .brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--accent-color);
            padding: 12px 16px;
            margin-bottom: 8px;
        }

        .nav-list {
            list-style: none;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            margin-bottom: 4px;
            border-radius: var(--radius-pill);
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.2s, color 0.2s;
        }

        .nav-item:hover {
            background-color: #e1e7ef;
            color: var(--text-primary);
        }

        .nav-item.active {
            background-color: #c2e7ff;
            color: #001d35;
            font-weight: 600;
        }

        .nav-icon {
            margin-right: 12px;
            font-size: 1.1rem;
        }

        .sidebar-section-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            padding: 16px 16px 8px 16px;
            font-weight: 600;
        }

        .user-profile {
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--accent-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.85rem;
        }

        /* --- MAIN DASHBOARD VIEW --- */
        main {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-header h1 {
            font-size: 1.6rem;
            font-weight: 500;
        }

        .btn-create {
            background-color: var(--accent-color);
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: var(--radius-pill);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s;
        }

        .btn-create:hover {
            opacity: 0.9;
        }

        .quote-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            font-style: italic;
            color: var(--text-secondary);
            border-left: 4px solid var(--accent-color);
        }

        .quote-author {
            display: block;
            margin-top: 8px;
            font-style: normal;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .stat-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            background-color: var(--accent-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .stat-info h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .stat-info p {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* DASHBOARD SECTIONS */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .section-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .section-card h2 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* LIST ITEMS */
        .item-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .list-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background-color: var(--bg-main);
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .list-row-info div {
            font-weight: 500;
            font-size: 0.9rem;
        }

        .list-row-info span {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .badge-accuracy {
            background-color: #e6f4ea;
            color: #137333;
            padding: 4px 10px;
            border-radius: var(--radius-pill);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .btn-study {
            background-color: var(--accent-color);
            color: #ffffff;
            border: none;
            padding: 6px 14px;
            border-radius: var(--radius-pill);
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
        }

        .btn-study:hover {
            background-color: #0842a0;
        }

        /* --- MODAL STYLES --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.15s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 480px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            font-size: 1.25rem;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px 8px;
            border-radius: var(--radius-pill);
        }

        .modal-close-btn:hover {
            background-color: var(--bg-sidebar);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            background-color: var(--bg-main);
            color: var(--text-primary);
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            background-color: #fff;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 70px;
        }

        .modal-status {
            padding: 10px 14px;
            border-radius: var(--radius-md);
            font-size: 0.85rem;
            display: none;
        }

        .modal-status.success {
            display: block;
            background-color: var(--success-soft);
            color: var(--success-color);
        }

        .modal-status.error {
            display: block;
            background-color: #fce8e6;
            color: var(--danger-color);
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-secondary {
            background-color: var(--bg-sidebar);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 8px 18px;
            border-radius: var(--radius-pill);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-secondary:hover {
            background-color: #e1e7ef;
        }
    </style>
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

            <div class="sidebar-section-title">Quick Decks</div>
            <ul class="nav-list" id="quickDecksList">
                <?php foreach ($sidebar_decks as $deck): ?>
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

        <!-- Sidebar Quick Decks section header with shortcut -->
        <div class="sidebar-section-title" style="display: flex; justify-content: space-between; align-items: center; padding-right: 16px;">
            <span>Quick Decks</span>
            <button onclick="openCreateDeckModal()" style="background: none; border: none; cursor: pointer; color: var(--text-secondary); font-size: 0.9rem;" title="Create New Deck">＋</button>
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
                                <?php foreach ($sidebar_decks as $deck): ?>
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

    <!-- 2. CREATE DECK MODAL (SEPARATE FORM AT ROOT LEVEL) -->
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
        document.getElementById('deckTitleInput').focus();
    }

    function closeCreateDeckModal() {
        document.getElementById('createDeckModal').style.display = 'none';
        document.getElementById('createDeckForm').reset();
        isCreatingDeckFromCardModal = false;
    }

    async function handleCreateDeck(e) {
        e.preventDefault();

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
                const resDecks = await fetch('api_review.php?action=get_user_decks');
                const dataDecks = await resDecks.json();

                if (dataDecks.status === 'success' && dataDecks.decks) {
                    select.innerHTML = dataDecks.decks.map(d => `<option value="${d.id}">${escapeHtml(d.title)}</option>`).join('');
                    // Auto-select newly created deck
                    select.value = data.deck.id;
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

        // Refresh deck list dynamically in case decks were recently added
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

        document.getElementById('modalFront').focus();
    }

    function closeCreateCardModal() {
        document.getElementById('createCardModal').style.display = 'none';
        document.getElementById('createCardForm').reset();
        loadDashboardStats();
        loadDueDecks();
    }

    async function handleCreateCard(e) {
        e.preventDefault();

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
            const res = await fetch('api_review.php?action=add_card', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ deck_id: deckId, front: front, back: back })
            });

            const data = await res.json();

            if (data.status === 'success') {
                status.className = 'modal-status success';
                status.innerText = '✓ Flashcard added! You can add another below.';

                // Clear input textareas but keep selected deck & modal open
                document.getElementById('modalFront').value = '';
                document.getElementById('modalBack').value = '';
                document.getElementById('modalFront').focus();
            } else {
                status.className = 'modal-status error';
                status.innerText = data.message || 'Error adding card.';
            }
        } catch (err) {
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