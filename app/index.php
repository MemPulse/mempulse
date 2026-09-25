<?php
// 1. Session check to protect the page from logged-out users
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // If they aren't logged in, boot them to the login page
    header("Location: ../authentication/login-page.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MemPulse - Spaced Repetition Flashcard</title>
    <link rel="stylesheet" href="styles/normalize.css">
    <link rel="stylesheet" href="styles/base.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0">
</head>

<body>

    <header class="site-header section">
        <nav>
            <a href="#" id="logo" class="logo" aria-label="MemPulse home">MemPulse</a>

            <ul class="primary-navigation" aria-label="Primary navigation">
                <li><a href="#" class="active" aria-current="page">Decks</a></li>
                <li><a href="#">Browse</a></li>
                <li><a href="#">Science</a></li>
            </ul>

            <div class="header-actions">
                <button id="create-menu-button" class="cta-button" type="button" aria-label="Create" title="Create" aria-haspopup="true" aria-expanded="false">
                    <span class="material-symbols-rounded" aria-hidden="true">add</span>
                </button>
                <div id="create-menu" class="create-menu" hidden>
                    <button type="button" data-create="card">Card</button>
                    <button type="button" data-create="deck">Deck</button>
                </div>
                <button id="theme-toggle" class="icon-button" type="button" aria-label="Switch to dark mode" aria-pressed="false" title="Switch to dark mode">
                    <span class="material-symbols-rounded" aria-hidden="true">dark_mode</span>
                </button>
                <a class="icon-button" href="#" aria-label="Account" title="Account">
                    <span class="material-symbols-rounded" aria-hidden="true">account_circle</span>
                </a>
                <a class="icon-button" href="../authentication/logout.php" aria-label="Account" title="Log out">
    <span class="material-symbols-rounded" aria-hidden="true">logout</span>
</a>

            </div>
        </nav>
    </header>
    <main class="dashboard decks-page" aria-labelledby="decks-heading">
        <section class="dashboard-intro" aria-labelledby="decks-heading">
            <p class="eyebrow">Your study space</p>
            <h1 id="decks-heading"><span id="decks-greeting">Good morning</span>, <span id="decks-username">Username</span></h1>
            <p id="decks-date" class="dashboard-date">Loading today...</p>
            <p id="decks-quote" class="dashboard-quote">Small reviews build lasting knowledge.</p>
        </section>

        <section id="decks-stats" class="stat-grid" aria-label="Deck summary"></section>

        <section class="decks-section" aria-labelledby="deck-list-heading">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Your decks</p>
                    <h2 id="deck-list-heading">Start studying</h2>
                </div>
            </div>
            <p id="decks-status" class="decks-status" role="status">Loading decks...</p>
            <div id="deck-list" class="deck-list" aria-live="polite"></div>
        </section>
        <section class="recent-section" aria-labelledby="recent-heading">
            <div class="section-heading">
                <h2 id="recent-heading">Recent activity</h2>
            </div>
            <div id="recent-list" class="recent-list" aria-live="polite"></div>
        </section>
        <section class="streak-section" aria-labelledby="streak-heading">
            <div class="section-heading">
                <h2 id="streak-heading">Study streak</h2>
                <span class="activity-range">Last 30 days</span>
            </div>

            <div class="streak-card">
                <div class="streak-current">
                    <p class="eyebrow">Current streak</p>
                    <div class="streak-value"><strong id="current-streak">0</strong><span>days</span></div>
                    <p id="streak-message" class="streak-message">Start a review session to begin your streak.</p>
                </div>
                <div class="streak-best">
                    <span>Best streak</span>
                    <strong id="best-streak">0 days</strong>
                </div>
            </div>
            <div class="activity-map-scroll">
                <div id="activity-map" class="activity-map" role="list" aria-label="Cards reviewed over the last 30 days">
                    <span class="activity-cell level-1" role="listitem" data-date="2026-07-16" data-reviewed-cards="2" aria-label="July 16, 2026: 2 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-07-17" data-reviewed-cards="8" aria-label="July 17, 2026: 8 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-07-18" data-reviewed-cards="5" aria-label="July 18, 2026: 5 cards reviewed"></span>
                    <span class="activity-cell level-0" role="listitem" data-date="2026-07-19" data-reviewed-cards="0" aria-label="July 19, 2026: 0 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-07-20" data-reviewed-cards="14" aria-label="July 20, 2026: 14 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-07-21" data-reviewed-cards="4" aria-label="July 21, 2026: 4 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-07-22" data-reviewed-cards="1" aria-label="July 22, 2026: 1 card reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-07-23" data-reviewed-cards="9" aria-label="July 23, 2026: 9 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-07-24" data-reviewed-cards="6" aria-label="July 24, 2026: 6 cards reviewed"></span>
                    <span class="activity-cell level-0" role="listitem" data-date="2026-07-25" data-reviewed-cards="0" aria-label="July 25, 2026: 0 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-07-26" data-reviewed-cards="3" aria-label="July 26, 2026: 3 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-07-27" data-reviewed-cards="16" aria-label="July 27, 2026: 16 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-07-28" data-reviewed-cards="10" aria-label="July 28, 2026: 10 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-07-29" data-reviewed-cards="5" aria-label="July 29, 2026: 5 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-07-30" data-reviewed-cards="12" aria-label="July 30, 2026: 12 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-07-31" data-reviewed-cards="2" aria-label="July 31, 2026: 2 cards reviewed"></span>
                    <span class="activity-cell level-0" role="listitem" data-date="2026-08-01" data-reviewed-cards="0" aria-label="August 1, 2026: 0 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-08-02" data-reviewed-cards="4" aria-label="August 2, 2026: 4 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-08-03" data-reviewed-cards="7" aria-label="August 3, 2026: 7 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-08-04" data-reviewed-cards="1" aria-label="August 4, 2026: 1 card reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-08-05" data-reviewed-cards="15" aria-label="August 5, 2026: 15 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-08-06" data-reviewed-cards="5" aria-label="August 6, 2026: 5 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-08-07" data-reviewed-cards="8" aria-label="August 7, 2026: 8 cards reviewed"></span>
                    <span class="activity-cell level-0" role="listitem" data-date="2026-08-08" data-reviewed-cards="0" aria-label="August 8, 2026: 0 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-08-09" data-reviewed-cards="3" aria-label="August 9, 2026: 3 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-08-10" data-reviewed-cards="18" aria-label="August 10, 2026: 18 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-08-11" data-reviewed-cards="9" aria-label="August 11, 2026: 9 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-08-12" data-reviewed-cards="4" aria-label="August 12, 2026: 4 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-08-13" data-reviewed-cards="2" aria-label="August 13, 2026: 2 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-08-14" data-reviewed-cards="7" aria-label="August 14, 2026: 7 cards reviewed"></span>
                    <span class="activity-cell level-0" role="listitem" data-date="2026-08-15" data-reviewed-cards="0" aria-label="August 15, 2026: 0 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-08-16" data-reviewed-cards="5" aria-label="August 16, 2026: 5 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-08-17" data-reviewed-cards="13" aria-label="August 17, 2026: 13 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-08-18" data-reviewed-cards="8" aria-label="August 18, 2026: 8 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-08-19" data-reviewed-cards="2" aria-label="August 19, 2026: 2 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-08-20" data-reviewed-cards="6" aria-label="August 20, 2026: 6 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-08-21" data-reviewed-cards="17" aria-label="August 21, 2026: 17 cards reviewed"></span>
                    <span class="activity-cell level-0" role="listitem" data-date="2026-08-22" data-reviewed-cards="0" aria-label="August 22, 2026: 0 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-08-23" data-reviewed-cards="1" aria-label="August 23, 2026: 1 card reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-08-24" data-reviewed-cards="10" aria-label="August 24, 2026: 10 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-08-25" data-reviewed-cards="14" aria-label="August 25, 2026: 14 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-08-26" data-reviewed-cards="4" aria-label="August 26, 2026: 4 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-08-27" data-reviewed-cards="8" aria-label="August 27, 2026: 8 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-08-28" data-reviewed-cards="2" aria-label="August 28, 2026: 2 cards reviewed"></span>
                    <span class="activity-cell level-0" role="listitem" data-date="2026-08-29" data-reviewed-cards="0" aria-label="August 29, 2026: 0 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-08-30" data-reviewed-cards="5" aria-label="August 30, 2026: 5 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-08-31" data-reviewed-cards="16" aria-label="August 31, 2026: 16 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-09-01" data-reviewed-cards="9" aria-label="September 1, 2026: 9 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-09-02" data-reviewed-cards="6" aria-label="September 2, 2026: 6 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-09-03" data-reviewed-cards="12" aria-label="September 3, 2026: 12 cards reviewed"></span>
                    <span class="activity-cell level-1" role="listitem" data-date="2026-09-04" data-reviewed-cards="2" aria-label="September 4, 2026: 2 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-09-05" data-reviewed-cards="8" aria-label="September 5, 2026: 8 cards reviewed"></span>
                    <span class="activity-cell level-0" role="listitem" data-date="2026-09-06" data-reviewed-cards="0" aria-label="September 6, 2026: 0 cards reviewed"></span>
                    <span class="activity-cell level-2" role="listitem" data-date="2026-09-07" data-reviewed-cards="4" aria-label="September 7, 2026: 4 cards reviewed"></span>
                    <span class="activity-cell level-4" role="listitem" data-date="2026-09-08" data-reviewed-cards="14" aria-label="September 8, 2026: 14 cards reviewed"></span>
                    <span class="activity-cell level-3" role="listitem" data-date="2026-09-09" data-reviewed-cards="11" aria-label="September 9, 2026: 11 cards reviewed"></span>
                </div>
            </div>
        </section>
    </main>

    <dialog id="card-dialog" class="creation-dialog" aria-labelledby="card-dialog-title">
        <form id="card-form" method="dialog">
            <div class="dialog-heading">
                <div>
                    <p class="eyebrow">New flashcard</p>
                    <h2 id="card-dialog-title">Create a card</h2>
                </div>
                <button class="dialog-close" type="button" data-dialog-close aria-label="Close card form">&times;</button>
            </div>
            <label for="card-front">Front</label>
            <textarea id="card-front" name="front" rows="3" required></textarea>
            <label for="card-back">Back</label>
            <textarea id="card-back" name="back" rows="3" required></textarea>
            <p class="dialog-feedback" data-dialog-feedback></p>
            <div class="dialog-actions">
                <button class="dialog-secondary" type="button" data-dialog-close>Cancel</button>
                <button class="primary-cta" type="submit">Save card</button>
            </div>
        </form>
    </dialog>

    <dialog id="deck-dialog" class="creation-dialog" aria-labelledby="deck-dialog-title">
        <form id="deck-form" method="dialog">
            <div class="dialog-heading">
                <div>
                    <p class="eyebrow">New study space</p>
                    <h2 id="deck-dialog-title">Create a deck</h2>
                </div>
                <button class="dialog-close" type="button" data-dialog-close aria-label="Close deck form">&times;</button>
            </div>
            <label for="deck-name">Deck name</label>
            <input id="deck-name" name="name" type="text" required maxlength="80" autocomplete="off">
            <p class="dialog-feedback" data-dialog-feedback></p>
            <div class="dialog-actions">
                <button class="dialog-secondary" type="button" data-dialog-close>Cancel</button>
                <button class="primary-cta" type="submit">Save deck</button>
            </div>
        </form>
    </dialog>

    <script src="scripts/app.js"></script>
    <script src="scripts/greet.js"></script>
    <script src="scripts/decks.js"></script>
    <script src="scripts/recent.js"></script>
    <script src="scripts/streak.js"></script>
    <script src="scripts/create.js"></script>
</body>

</html>