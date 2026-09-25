const greetingElement = document.querySelector("#decks-greeting");
const usernameElement = document.querySelector("#decks-username");
const dateElement = document.querySelector("#decks-date");
const quoteElement = document.querySelector("#decks-quote");
const greetingUsersEndpoint = "data/users.json";
const motivationalQuotes = [
    "Small reviews build lasting knowledge.",
    "A few focused cards can move a whole subject forward.",
    "Keep the rhythm steady and let recall do the work."
];

function getTimeBasedGreeting(date = new Date()) {
    const hour = date.getHours();

    if (hour < 12) {
        return "Good morning";
    }

    if (hour < 18) {
        return "Good afternoon";
    }

    return "Good evening";
}

async function loadGreeting() {
    const now = new Date();

    if (greetingElement) {
        greetingElement.textContent = getTimeBasedGreeting(now);
    }

    if (dateElement) {
        dateElement.textContent = new Intl.DateTimeFormat(undefined, {
            weekday: "long",
            month: "long",
            day: "numeric"
        }).format(now);
    }

    if (quoteElement) {
        quoteElement.textContent = motivationalQuotes[now.getDate() % motivationalQuotes.length];
    }

    try {
        const response = await fetch(greetingUsersEndpoint);
        if (!response.ok) {
            throw new Error(`User request failed with status ${response.status}`);
        }

        const usersData = await response.json();
        const user = usersData.secondaryUser;

        if (usernameElement) {
            usernameElement.textContent = user.name;
        }

        if (dateElement) {
            dateElement.innerHTML = `${dateElement.textContent} <span aria-hidden="true">&middot;</span> You have <strong>${window.mempulse.escapeHtml(user.dueCards)} cards due today.</strong>`;
        }
    } catch (error) {
        console.error(error);
    }
}

loadGreeting();
