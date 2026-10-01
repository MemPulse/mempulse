const MemPulseDashboard = {
    updateAll(appPayload) {
        if (!appPayload) return;

        this.syncDatabaseMetrics(appPayload.stats);
        this.syncStreakMetrics(appPayload.stats);
    },

    syncDatabaseMetrics(stats) {
        if (!stats) return;

        const cardsDue = document.getElementById('stat-cards-due');
        const mastered = document.getElementById('stat-mastered');
        const accuracy = document.getElementById('stat-accuracy');
        const tough = document.getElementById('stat-tough');

        if (cardsDue) cardsDue.textContent = stats.cardsDue ?? '--';
        if (mastered) mastered.textContent = stats.masteredCards ?? '--';
        if (accuracy) {
            accuracy.textContent =
                stats.lifetimeAccuracy !== undefined
                    ? `${stats.lifetimeAccuracy}%`
                    : '--%';
        }
        if (tough) tough.textContent = stats.toughCards ?? '--';
    },

    syncStreakMetrics(stats) {
        if (!stats) return;

        const currentStreak = document.getElementById('stat-current-streak');
        const bestStreak = document.getElementById('stat-best-streak');
        const streakAccuracy = document.getElementById('stat-streak-accuracy');

        if (currentStreak) {
            currentStreak.textContent = stats.currentStreak ?? '--';
        }

        if (bestStreak) {
            bestStreak.textContent = stats.bestStreak ?? '--';
        }

        if (streakAccuracy) {
            streakAccuracy.textContent = stats.streakAccuracy ?? '--';
        }
    }
};

document.addEventListener("DOMContentLoaded", () => {
    const heatmapScroller = document.getElementById("heatmap-scroll");

    if (heatmapScroller) {
        requestAnimationFrame(() => {
            heatmapScroller.scrollLeft =
                heatmapScroller.scrollWidth - heatmapScroller.clientWidth;
        });
    }

    document.querySelectorAll("#heatmap-scroll .heatmap-cell").forEach(cell => {
        cell.addEventListener("mouseenter", () => {
            const rect = cell.getBoundingClientRect();

            cell.style.setProperty(
                "--tooltip-left",
                `${rect.left + rect.width / 2}px`
            );

            cell.style.setProperty(
                "--tooltip-top",
                `${rect.top - 8}px`
            );
        });
    });

    MemPulseDashboard.updateAll(window.MemPulseHomeData);
});