const decksEndpoint = "data/decks.json";
const savedDecksKey = "mempulse-created-decks";
const decksListElement = document.querySelector("#deck-list");
const decksStatusElement = document.querySelector("#decks-status");
const statsElement = document.querySelector("#decks-stats");
let baseDeckData = { stats: {}, decks: [] };

function getSavedDecks() {
    try {
        return JSON.parse(localStorage.getItem(savedDecksKey) || "[]");
    } catch (error) {
        console.error(error);
        return [];
    }
}

function renderStats(stats, deckCount) {
    const statItems = [
        ["Decks", deckCount],
        ["Cards", stats.totalCards || 0],
        ["Due today", stats.cardsDue || 0]
    ];

    statsElement.innerHTML = statItems.map(([label, value]) => `
        <article class="stat-card">
            <p class="eyebrow">${label}</p>
            <strong class="stat-value">${window.mempulse.escapeHtml(value)}</strong>
        </article>
    `).join("");
}

function renderDecks(decks) {
    if (decks.length === 0) {
        decksStatusElement.textContent = "No decks yet.";
        decksListElement.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-rounded" aria-hidden="true">library_add</span>
                <p>Use the + menu to create your first deck.</p>
            </div>
        `;
        return;
    }

    decksStatusElement.textContent = `${decks.length} ${decks.length === 1 ? "deck" : "decks"}`;
    decksListElement.innerHTML = `
        <div class="deck-header" role="row">
            <span role="columnheader">Deck</span>
            <span role="columnheader">New</span>
            <span role="columnheader">Learn</span>
            <span role="columnheader">Due</span>
        </div>
        ${decks.map((deck) => `
            <article class="deck-row" data-deck-id="${window.mempulse.escapeHtml(deck.id)}" role="row">
                <a class="deck-name" href="#" aria-label="Open ${window.mempulse.escapeHtml(deck.name)}" role="cell">${window.mempulse.escapeHtml(deck.name)}</a>
                <strong class="deck-value deck-state-new" role="cell">${window.mempulse.escapeHtml(deck.new || 0)}</strong>
                <strong class="deck-value deck-state-learn" role="cell">${window.mempulse.escapeHtml(deck.learn || 0)}</strong>
                <strong class="deck-value deck-state-due" role="cell">${window.mempulse.escapeHtml(deck.due || 0)}</strong>
            </article>
        `).join("")}
    `;
}

function refreshDecks() {
    const decks = [...baseDeckData.decks, ...getSavedDecks()];
    renderStats(baseDeckData.stats, decks.length);
    renderDecks(decks);
}

async function loadDecks() {
    try {
        const response = await fetch(decksEndpoint);
        if (!response.ok) {
            throw new Error(`Deck request failed with status ${response.status}`);
        }

        baseDeckData = await response.json();
        refreshDecks();
    } catch (error) {
        decksStatusElement.textContent = "Unable to load decks.";
        decksListElement.innerHTML = `
            <div class="empty-state">
                <span class="material-symbols-rounded" aria-hidden="true">cloud_off</span>
                <p>Try again when the deck service is available.</p>
            </div>
        `;
        console.error(error);
    }
}

window.refreshDecks = refreshDecks;
window.addEventListener("mempulse:deck-created", refreshDecks);
loadDecks();
