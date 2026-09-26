<?php
// 1. Session check to protect the page from logged-out users
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // If they aren't logged in, redirect to the login page
    header("Location: ../authentication/login-page.html");
    exit;
}

// Obtain logged-in user ID dynamically from session
$user_id = intval($_SESSION['user_id']);

// Locate and include db.php safely
if (file_exists(__DIR__ . '/../config/db.php')) {
    require_once __DIR__ . '/../config/db.php';
} elseif (file_exists(__DIR__ . '/../../config/db.php')) {
    require_once __DIR__ . '/../../config/db.php';
} elseif (file_exists(__DIR__ . '/db.php')) {
    require_once __DIR__ . '/db.php';
} else {
    die("Database configuration file (db.php) could not be located.");
}

// 2. Fetch all active decks for the logged-in user
$decks_stmt = $conn->prepare("SELECT id, title FROM decks WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at ASC");
$decks_stmt->bind_param("i", $user_id);
$decks_stmt->execute();
$decks_result = $decks_stmt->get_result();

$all_decks = [];
while ($row = $decks_result->fetch_assoc()) {
    $all_decks[] = $row;
}

// 3. Determine active deck ID
$selected_deck_id = isset($_GET['deck_id']) ? intval($_GET['deck_id']) : ($all_decks[0]['id'] ?? 0);

// Find active deck title
$selected_deck_title = "No Deck Selected";
foreach ($all_decks as $deck) {
    if ($deck['id'] === $selected_deck_id) {
        $selected_deck_title = $deck['title'];
        break;
    }
}

// 4. Fetch flashcards for selected deck owned by current user
$cards = [];
if ($selected_deck_id > 0) {
    $cards_sql = "SELECT f.id, f.front, f.back, f.interval_days, f.next_review_at 
                  FROM flashcards f
                  JOIN decks d ON f.deck_id = d.id
                  WHERE f.deck_id = ? AND d.user_id = ? AND f.deleted_at IS NULL 
                  ORDER BY f.id DESC";
    $cards_stmt = $conn->prepare($cards_sql);
    $cards_stmt->bind_param("ii", $selected_deck_id, $user_id);
    $cards_stmt->execute();
    $cards_result = $cards_stmt->get_result();

    while ($card = $cards_result->fetch_assoc()) {
        $cards[] = $card;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemPulse - Decks & Flashcards</title>
    <style>
        /* Gemini-inspired Clean Design Variables */
        :root {
            --bg-main: #f8fafd;
            --bg-sidebar: #f0f4f9;
            --bg-card: #ffffff;
            --text-primary: #1f1f1f;
            --text-secondary: #444746;
            --accent-color: #0b57d0;
            --accent-hover: #e8def8;
            --border-color: #e0e2e0;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-pill: 28px;
            --sidebar-width: 260px;
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
            transition: background-color 0.2s ease, color 0.2s ease;
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

        /* --- MAIN VIEW --- */
        main {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-title-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .view-title h1 {
            font-size: 1.5rem;
            font-weight: 500;
        }

        .btn-study {
            background-color: var(--accent-color);
            color: #ffffff;
            text-decoration: none;
            padding: 8px 20px;
            border-radius: var(--radius-pill);
            font-size: 0.85rem;
            font-weight: 600;
            transition: opacity 0.2s ease;
        }

        .btn-study:hover {
            opacity: 0.9;
        }

        .view-controls {
            display: flex;
            gap: 8px;
            background-color: var(--bg-sidebar);
            padding: 4px;
            border-radius: var(--radius-pill);
        }

        .toggle-btn {
            border: none;
            background: transparent;
            padding: 6px 16px;
            border-radius: var(--radius-pill);
            font-size: 0.85rem;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.2s ease;
        }

        .toggle-btn.active {
            background-color: var(--bg-card);
            color: var(--text-primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }

        /* --- TILE / GRID VIEW --- */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .flashcard-item {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 140px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .flashcard-item:hover {
            border-color: #b4b8b5;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .card-front {
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 500;
        }

        .badge-due {
            background-color: #fce8e6;
            color: #b3261e;
        }

        .badge-later {
            background-color: #e8f0fe;
            color: var(--accent-color);
        }

        .empty-state {
            padding: 48px 0;
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        /* --- LIST VIEW OVERRIDE CLASS --- */
        .cards-grid.list-view {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cards-grid.list-view .flashcard-item {
            min-height: auto;
            flex-direction: row;
            align-items: center;
            padding: 12px 20px;
        }

        .cards-grid.list-view .card-meta {
            margin-top: 0;
            gap: 16px;
        }
    </style>
</head>

<body>

    <!-- LEFT SIDEBAR -->
    <aside>
        <div>
            <div class="brand">MemPulse</div>
            <ul class="nav-list">
                <li><a href="index.php" class="nav-item"> <span class="nav-icon">🏠</span> Home </a></li>
                <li><a href="decks.php" class="nav-item active"> <span class="nav-icon">📚</span> Decks </a></li>
            </ul>

            <div class="sidebar-section-title">Quick Decks</div>
            <ul class="nav-list">
                <?php foreach ($all_decks as $deck): ?>
                <li>
                    <a href="decks.php?deck_id=<?= $deck['id'] ?>" class="nav-item <?= ($deck['id'] === $selected_deck_id) ? 'active' : '' ?>">
                        <span class="nav-icon">🗂️</span>
                        <?= htmlspecialchars($deck['title']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <?php if (empty($all_decks)): ?>
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
            <div class="avatar">U</div>
            <div style="font-size: 0.85rem; color: var(--text-secondary);">User Account</div>
        </div>
    </aside>

    <!-- MAIN VIEW -->
    <main>
        <div class="header-bar">
            <div class="view-title-group">
                <div class="view-title">
                    <h1>
                        <?= htmlspecialchars($selected_deck_title) ?>
                    </h1>
                </div>
                <?php if ($selected_deck_id > 0): ?>
                <a href="review.php?deck_id=<?= $selected_deck_id ?>" class="btn-study">▶ Review Deck</a>
                <?php endif; ?>
            </div>

            <!-- Tile / List View Switcher -->
            <div class="view-controls">
                <button class="toggle-btn active" onclick="setView('tile', this)">Tile View</button>
                <button class="toggle-btn" onclick="setView('list', this)">List View</button>
            </div>
        </div>

        <!-- FLASHCARDS CONTAINER -->
        <div id="cardsContainer" class="cards-grid">
            <?php if (!empty($cards)): ?>
            <?php foreach ($cards as $card): ?>
            <?php 
                        $is_due = strtotime($card['next_review_at']) <= time();
                    ?>
            <div class="flashcard-item">
                <div class="card-front">
                    <?= htmlspecialchars($card['front']) ?>
                </div>
                <div class="card-meta">
                    <span>Interval:
                        <?= intval($card['interval_days']) ?> day
                        <?= $card['interval_days'] > 1 ? 's' : '' ?>
                    </span>
                    <?php if ($is_due): ?>
                    <span class="badge badge-due">Due Today</span>
                    <?php else: ?>
                    <span class="badge badge-later">Review Later</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="empty-state">
                No flashcards found in this deck. Add cards to get started!
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function setView(type, btn) {
            const container = document.getElementById('cardsContainer');
            const buttons = document.querySelectorAll('.toggle-btn');

            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            if (type === 'list') {
                container.classList.add('list-view');
            } else {
                container.classList.remove('list-view');
            }
        }
    </script>
</body>

</html>