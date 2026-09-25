const createMenuButton = document.querySelector("#create-menu-button");
const createMenu = document.querySelector("#create-menu");
const creationDialogs = {
    card: document.querySelector("#card-dialog"),
    deck: document.querySelector("#deck-dialog")
};
const cardForm = document.querySelector("#card-form");
const deckForm = document.querySelector("#deck-form");
const createdDecksKey = "mempulse-created-decks";
const createdCardsKey = "mempulse-created-cards";

function setMenuOpen(isOpen) {
    createMenu.hidden = !isOpen;
    createMenuButton.setAttribute("aria-expanded", String(isOpen));
}

function openCreationDialog(type) {
    setMenuOpen(false);
    creationDialogs[type]?.showModal();
}

function closeDialog(dialog) {
    dialog.close();
    dialog.querySelector("form").reset();
    dialog.querySelector("[data-dialog-feedback]").textContent = "";
}

function getStoredItems(key) {
    try {
        return JSON.parse(localStorage.getItem(key) || "[]");
    } catch (error) {
        console.error(error);
        return [];
    }
}

createMenuButton.addEventListener("click", () => {
    setMenuOpen(createMenu.hidden);
});

createMenu.addEventListener("click", (event) => {
    const action = event.target.closest("[data-create]");
    if (action) {
        openCreationDialog(action.dataset.create);
    }
});

document.addEventListener("click", (event) => {
    if (!createMenu.hidden && !createMenu.contains(event.target) && !createMenuButton.contains(event.target)) {
        setMenuOpen(false);
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        setMenuOpen(false);
    }
});

document.querySelectorAll("[data-dialog-close]").forEach((button) => {
    button.addEventListener("click", () => closeDialog(button.closest("dialog")));
});

cardForm.addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(cardForm);
    const cards = getStoredItems(createdCardsKey);

    cards.unshift({
        id: crypto.randomUUID(),
        front: formData.get("front").trim(),
        back: formData.get("back").trim(),
        createdAt: new Date().toISOString()
    });
    localStorage.setItem(createdCardsKey, JSON.stringify(cards));
    closeDialog(creationDialogs.card);
    window.alert("Card saved. Card review is still in development.");
});

deckForm.addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(deckForm);
    const name = formData.get("name").trim();
    const decks = getStoredItems(createdDecksKey);

    decks.unshift({
        id: `local-${crypto.randomUUID()}`,
        name,
        new: 0,
        learn: 0,
        due: 0
    });
    localStorage.setItem(createdDecksKey, JSON.stringify(decks));
    closeDialog(creationDialogs.deck);
    window.dispatchEvent(new CustomEvent("mempulse:deck-created"));
});