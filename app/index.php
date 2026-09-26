<?php
session_start();

// Enforce login authentication
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../authentication/login-page.html");
    exit;
}

$firstName = $_SESSION['first_name'] ?? 'User';$lastName  = $_SESSION['last_name'] ?? '';$fullName  = trim($firstName . ' ' .$lastName);
$initial   = strtoupper(substr($firstName, 0, 1));
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

        .welcome-header h1 {
            font-size: 1.6rem;
            font-weight: 500;
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
                <li><a href="#" class="nav-item"> <span class="nav-icon">🗂️</span> Loading... </a></li>
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
        <div class="welcome-header">
            <h1 id="welcomeTitle">Welcome back, <?php echo htmlspecialchars($firstName); ?> 👋</h1>
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

    async function loadDashboardStats() {
        try {
            const response = await fetch('api_review.php?action=get_dashboard_stats');
            const data = await response.json();

            if (data.status === 'success') {
                document.getElementById('statStreak').innerText = `${data.stats.streak} Days`;
                document.getElementById('statTotalCards').innerText = data.stats.total_cards;
                document.getElementById('statDueCards').innerText = data.stats.due_today;

                const quickList = document.getElementById('quickDecksList');
                if (!data.quick_decks || data.quick_decks.length === 0) {
                    quickList.innerHTML = `<li class="nav-item" style="color: var(--text-secondary);">No decks yet</li>`;
                } else {
                    quickList.innerHTML = data.quick_decks.map(deck => `
                        <li>
                            <a href="review.php?deck_id=${deck.id}" class="nav-item">
                                <span class="nav-icon">🗂️</span> ${escapeHtml(deck.title)}
                            </a>
                        </li>
                    `).join('');
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

    document.addEventListener('DOMContentLoaded', () => {
        loadDashboardStats();
        loadDueDecks();
        loadRecentLogs();
    });
    </script>
</body>

</html>