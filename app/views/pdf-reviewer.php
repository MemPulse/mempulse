<?php
/**
 * PDF Reviewer (offline, printable study sheet)
 *
 * Two entry points:
 *   1. Deck page bulk-actions bar (selected cards only):
 *        pdf-reviewer.php?deck_id=12&cards=5,9,3[&layout=beside|end][&autoprint=1]
 *   2. Review Hub (every active card of the deck, no selection):
 *        pdf-reviewer.php?deck_id=12&all=1&src=hub[&layout=beside|end][&autoprint=1]
 *
 * - Read-only: nothing is saved and no schedule data is touched.
 * - Standalone: loads only pdf-reviewer.css / pdf-reviewer.js (no app chrome).
 * - Cards are re-fetched server-side, so ownership and "active only" are enforced here,
 *   whatever ids the URL carries.
 */

// 1. Session + route protection (same as deck.php)
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../api/FlashcardService.php';

// Private study data: never cache it
header('Cache-Control: private, no-store');

const PDF_REVIEWER_MAX_CARDS = 300;    // selected cards (ids travel in the URL); keep in sync with PDF_MAX in deck.js
const PDF_REVIEWER_MAX_ALL   = 1000;   // "all active cards" mode (Review Hub): no ids in the URL, server-side safety cap

$userId = (int) $_SESSION['user_id'];

// 2. Deck (ownership verified by getDeckDetails)
$deckId = isset($_GET['deck_id']) && ctype_digit((string) $_GET['deck_id']) ? (int) $_GET['deck_id'] : 0;
$deck   = FlashcardService::getDeckDetails($conn, $deckId, $userId);
if (!$deck) {
    header("Location: decks.php");
    exit;
}

// 3. Which cards?
//    all=1  -> every active card of the deck (Review Hub), oldest first
//    cards= -> the selected ids from the deck page: positive ints, unique, URL order kept, capped
$allMode = isset($_GET['all']) && $_GET['all'] === '1';
$fromHub = $allMode && isset($_GET['src']) && $_GET['src'] === 'hub';

if ($allMode) {
    $cards = FlashcardService::getActiveCardsForPrint($conn, $deckId, $userId, PDF_REVIEWER_MAX_ALL);
} else {
    $ids = [];
    foreach (explode(',', (string) ($_GET['cards'] ?? '')) as $part) {
        $part = trim($part);
        if ($part !== '' && ctype_digit($part) && (int) $part > 0) {
            $ids[(int) $part] = true;
        }
    }
    $ids   = array_slice(array_keys($ids), 0, PDF_REVIEWER_MAX_CARDS);
    $cards = $ids ? FlashcardService::getCardsForPrint($conn, $deckId, $userId, $ids) : [];
}
$total = count($cards);

// 4. Options (the page also lets the student change them live)
$layoutFromUrl = isset($_GET['layout']) && in_array($_GET['layout'], ['beside', 'end'], true);
$layout        = $layoutFromUrl ? $_GET['layout'] : 'beside';
$autoPrint     = (isset($_GET['autoprint']) && $_GET['autoprint'] === '1') ? '1' : '0';

// Chrome/Edge use <title> as the default PDF file name
$docTitle  = $deck['title'] . ' - MemPulse reviewer';
$h         = fn($v) => FlashcardService::h($v);
$backUrl   = $fromHub ? 'review.php' : 'deck.php?deck_id=' . $deckId;
$backLabel = $fromHub ? 'Back to Review' : 'Back to deck';
$dateLabel = date('F j, Y');
$cardWord  = $total === 1 ? 'card' : 'cards';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $h($docTitle) ?></title>
    <link rel="stylesheet" href="../assets/css/pdf-reviewer.css">
</head>

<body class="pr-body"
      data-autoprint="<?= $autoPrint ?>"
      data-layout-source="<?= $layoutFromUrl ? 'url' : 'default' ?>">

    <!-- TOOLBAR (screen only) -->
    <header class="pr-toolbar pr-no-print">
        <a class="pr-back" href="<?= $h($backUrl) ?>">&larr; <?= $h($backLabel) ?></a>

        <?php if ($total > 0): ?>
        <div class="pr-options">
            <fieldset class="pr-seg">
                <legend class="pr-sr">Where should the answers go?</legend>
                <label class="pr-seg-opt">
                    <input type="radio" name="pr-layout" value="beside" <?= $layout === 'beside' ? 'checked' : '' ?>>
                    <span>Answers beside</span>
                </label>
                <label class="pr-seg-opt">
                    <input type="radio" name="pr-layout" value="end" <?= $layout === 'end' ? 'checked' : '' ?>>
                    <span>Answers at the end</span>
                </label>
            </fieldset>

            <label class="pr-toggle" id="pr-lines-wrap" <?= $layout === 'beside' ? 'hidden' : '' ?>>
                <input type="checkbox" id="pr-lines" checked>
                <span>Space to write answers</span>
            </label>
        </div>

        <button type="button" id="pr-print-btn" class="pr-print-btn">Print / Save as PDF</button>
        <?php endif; ?>
    </header>

    <main class="pr-stage">

        <?php if ($total === 0): ?>
        <!-- EMPTY STATE -->
        <section class="pr-empty">
            <h1>No cards to print</h1>
            <?php if ($allMode): ?>
            <p>This deck has no active cards. Archived cards are not included.</p>
            <?php else: ?>
            <p>The selected cards could not be found. They may have been archived or moved.</p>
            <?php endif; ?>
            <p><a class="pr-print-btn" href="<?= $h($backUrl) ?>"><?= $h($backLabel) ?></a></p>
        </section>

        <?php else: ?>
        <p class="pr-hint pr-no-print">
            In the print dialog choose <strong>Save as PDF</strong>, and switch off
            &ldquo;Headers and footers&rdquo; for a clean page.
        </p>

        <article class="pr-sheet" id="pr-sheet" data-layout="<?= $h($layout) ?>" data-lines="1">

            <!-- DOCUMENT HEAD -->
            <header class="pr-doc-head">
                <p class="pr-brand">MemPulse reviewer</p>
                <h1 class="pr-title"><?= $h($deck['title']) ?></h1>
                <p class="pr-meta">
                    <span><?= $total ?> <?= $cardWord ?></span>
                    <span><?= $h($dateLabel) ?></span>
                    <span class="pr-only-beside">Score <i class="pr-blank"></i> / <?= $total ?></span>
                </p>
                <p class="pr-tip pr-only-beside">Cover the Answer column, say each answer out loud, then check yourself and tick what you got right.</p>
                <p class="pr-tip pr-only-end">Answer every question first. Then turn to the answer key and tick what you got right.</p>
            </header>

            <!-- LAYOUT A: answers beside the questions -->
            <section class="pr-section pr-section--beside" aria-label="Questions and answers">
                <table class="pr-table">
                    <colgroup>
                        <col class="pr-col-num"><col class="pr-col-q"><col><col class="pr-col-box">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col" class="pr-num">#</th>
                            <th scope="col">Question</th>
                            <th scope="col">Answer</th>
                            <th scope="col" class="pr-box-col">Got it</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $i => $card): ?>
                        <tr>
                            <td class="pr-num"><?= $i + 1 ?></td>
                            <td class="pr-q"><div class="pr-text"><?= $h($card['front']) ?></div></td>
                            <td class="pr-a"><div class="pr-text"><?= $h($card['back']) ?></div></td>
                            <td class="pr-box-col"><span class="pr-box" aria-hidden="true"></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <!-- LAYOUT B (part 1): questions only, with optional writing space -->
            <section class="pr-section pr-section--questions" aria-label="Questions">
                <table class="pr-table">
                    <colgroup>
                        <col class="pr-col-num"><col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col" class="pr-num">#</th>
                            <th scope="col">Question</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $i => $card): ?>
                        <tr>
                            <td class="pr-num"><?= $i + 1 ?></td>
                            <td class="pr-q">
                                <div class="pr-text"><?= $h($card['front']) ?></div>
                                <div class="pr-write" aria-hidden="true"></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <!-- LAYOUT B (part 2): answer key, always starts on a new page -->
            <section class="pr-section pr-section--key" aria-label="Answer key">
                <div class="pr-key-head">
                    <h2>Answer key</h2>
                    <p class="pr-score">Score <i class="pr-blank"></i> / <?= $total ?></p>
                </div>
                <table class="pr-table">
                    <colgroup>
                        <col class="pr-col-num"><col class="pr-col-q"><col><col class="pr-col-box">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col" class="pr-num">#</th>
                            <th scope="col">Question</th>
                            <th scope="col">Answer</th>
                            <th scope="col" class="pr-box-col">Got it</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cards as $i => $card): ?>
                        <tr>
                            <td class="pr-num"><?= $i + 1 ?></td>
                            <td class="pr-q pr-q--ref"><div class="pr-text"><?= $h($card['front']) ?></div></td>
                            <td class="pr-a"><div class="pr-text"><?= $h($card['back']) ?></div></td>
                            <td class="pr-box-col"><span class="pr-box" aria-hidden="true"></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

        </article>
        <?php endif; ?>

    </main>

    <?php if ($total > 0): ?>
    <script src="../assets/js/pdf-reviewer.js" defer></script>
    <?php endif; ?>
</body>

</html>