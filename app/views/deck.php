<?php
// 1. Initialize user session tracking
session_start();

// 2. Route Protection: Redirect to login view if user credentials are uninitialized
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

// 3. Connect to database module and load business services
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/FlashcardService.php';

$userId = (int) $_SESSION['user_id'];

ArchiveService::purgeExpired($conn, $userId);

// 4. Validate and sanitize input deck ID parameter
$deckId = isset($_GET['deck_id']) && ctype_digit((string) $_GET['deck_id']) ? (int) $_GET['deck_id'] : 0;

// 5. Fetch target deck details and verify user ownership
$deck = FlashcardService::getDeckDetails($conn, $deckId, $userId);

// Redirect to deck list if the requested deck is missing or unauthorized
if (!$deck) {
    header("Location: decks.php");
    exit;
}

// 6. Fetch all active flashcards belonging to the deck
$flashcards = FlashcardService::getDeckFlashcards($conn, $deckId);
$archivedFlashcards = FlashcardService::getDeckFlashcards($conn, $deckId, '', true);

// 6.5 Fetch user's active decks for the Move dialog
$userDecks = FlashcardService::getUserDecks($conn, $userId);
$otherDecks = array_filter($userDecks, fn($d) => $d['id'] !== $deckId);

// 7. CSRF token initialization for card mutation requests (Add / Edit / Delete)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle   = $deck['title'] . " - Flashcards";
$currentPage = "decks";
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<!-- BODY: Main App Background & Styling Root -->
<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN: Viewport Shell Offset for Mobile/Desktop Navigation Bar -->
    <main id="main-content" 
    class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <?php include __DIR__ . '/deck-view.php'; ?>
    </main>

    <!-- 1. DATA INJECTION: Server-side PHP variables passed safely to client-side JS -->
    <script>
        window.MemPulseDeckData = Object.assign(window.MemPulseDeckData || {}, {
            csrfToken: <?= json_encode($_SESSION['csrf_token'] ?? '') ?>,
            deckId: <?= $deckId ?>,
            cardCount: <?= (int) $deck['card_count'] ?>,
            archivedCount: <?= (int) $deck['archived_count'] ?>,
            retentionDays: <?= ArchiveService::RETENTION_DAYS ?>,
            otherDecks: <?= json_encode(array_values($otherDecks)) ?>
        });
    </script>

    <!-- 2. CLIENT-SIDE ASSET IMPORTS: Separated standalone JavaScript module -->
    <script src="../assets/js/archive-ui.js" defer></script>
    <script src="../assets/js/deck.js" defer></script>
</body>
</html>