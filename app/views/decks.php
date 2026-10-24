<?php
// 1. Initialize user session tracking
session_start();

// 2. Route Protection: Redirect to login view if user credentials are uninitialized
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

// 3. Connect to database module and load business service
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/DeckService.php';

// CSRF token used by the rename/archive/create/restore/add_card requests
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = (int) $_SESSION['user_id'];

// --- DATA ORCHESTRATION ---
$decks         = DeckService::getUserDecks($conn, $userId);
$archivedDecks = DeckService::getArchivedDecks($conn, $userId);
$deckTotal     = count($decks);

$pageTitle   = "Decks";
$currentPage = "decks";
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<!-- BODY: Main App Background & Styling Root -->
<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN: Viewport Shell Offset for Fixed Top Header (pt-16) and Sidebar (md:pl-72) -->
    <main id="main-content" 
        class="pl-0 md:pl-72 pt-16 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/decks-view.php'; ?>
    </main>

    <!-- 1. DATA INJECTION: Server-side PHP variables passed safely to client-side JS -->
    <script>
        window.MemPulseDeckData = {
            csrfToken: <?= json_encode($_SESSION['csrf_token'] ?? '') ?>,
            createRequested: <?= isset($_GET['create']) && $_GET['create'] === '1' ? 'true' : 'false' ?>,
            addCardRequested: <?= isset($_GET['add_card']) && $_GET['add_card'] === '1' ? 'true' : 'false' ?>,
            requestedDeckId: <?= isset($_GET['deck_id']) && ctype_digit((string) $_GET['deck_id']) ? (int) $_GET['deck_id'] : 'null' ?>
        };
    </script>

    <!-- 2. CLIENT-SIDE ASSET IMPORTS: Separated standalone JavaScript modules -->
    <script src="../assets/js/decks.js" defer></script>
</body>
</html>