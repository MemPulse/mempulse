const recentActivityEndpoint = "data/recent-activity.json";
const recentListElement = document.querySelector("#recent-list");
const savedRecentKey = "mempulse-recent-activity";

function getSavedRecentActivity() {
    try {
        return JSON.parse(localStorage.getItem(savedRecentKey) || "[]");
    } catch (error) {
        console.error(error);
        return [];
    }
}

function renderRecentActivity(items) {
    if (!recentListElement) {
        return;
    }

    recentListElement.innerHTML = items.length === 0
        ? `<div class="empty-state"><p>No recent activity yet.</p></div>`
        : items.slice(0, 5).map((item) => `
            <article class="recent-item">
                <div>
                    <h3>${window.mempulse.escapeHtml(item.deck)}</h3>
                    <p>Reviewed ${window.mempulse.escapeHtml(item.reviewedCards)} cards</p>
                </div>
                <time datetime="${window.mempulse.escapeHtml(item.datetime || "")}">${window.mempulse.escapeHtml(item.time)}</time>
            </article>
        `).join("");
}

async function loadRecentActivity() {
    try {
        const response = await fetch(recentActivityEndpoint);
        if (!response.ok) {
            throw new Error(`Recent activity request failed with status ${response.status}`);
        }

        const data = await response.json();
        renderRecentActivity([...getSavedRecentActivity(), ...data.items]);
    } catch (error) {
        recentListElement.innerHTML = `<div class="empty-state"><p>Recent activity is unavailable.</p></div>`;
        console.error(error);
    }
}

window.addRecentActivity = (item) => {
    const items = [item, ...getSavedRecentActivity()];
    localStorage.setItem(savedRecentKey, JSON.stringify(items));
    loadRecentActivity();
};

loadRecentActivity();
