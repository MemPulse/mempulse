const currentStreakElement = document.querySelector("#current-streak");
const bestStreakElement = document.querySelector("#best-streak");
const streakMessageElement = document.querySelector("#streak-message");
const activityMapElement = document.querySelector("#activity-map");
const dayInMilliseconds = 24 * 60 * 60 * 1000;
const activityDayLimit = 30;

function getActivityData() {
    return [...(activityMapElement?.querySelectorAll(".activity-cell") || [])]
        .map((cell) => ({
            date: cell.dataset.date,
            reviewedCards: Number(cell.dataset.reviewedCards) || 0
        }))
        .filter((day) => day.date)
        .sort((firstDay, secondDay) => firstDay.date.localeCompare(secondDay.date));
}

function getActivityLevel(reviewedCards) {
    if (reviewedCards === 0) {
        return 0;
    }

    if (reviewedCards < 4) {
        return 1;
    }

    if (reviewedCards < 8) {
        return 2;
    }

    if (reviewedCards < 13) {
        return 3;
    }

    return 4;
}

function renderActivity(activityData) {
    if (!activityMapElement) {
        return;
    }

    const visibleDays = activityData.slice(-activityDayLimit);
    activityMapElement.innerHTML = visibleDays.map((day) => {
        const reviewedCards = Number(day.reviewedCards) || 0;
        const formattedDate = new Intl.DateTimeFormat(undefined, {
            month: "long",
            day: "numeric",
            year: "numeric"
        }).format(new Date(`${day.date}T00:00:00`));

        return `<span class="activity-cell level-${getActivityLevel(reviewedCards)}"
            role="listitem"
            data-date="${window.mempulse.escapeHtml(day.date)}"
            data-reviewed-cards="${reviewedCards}"
            aria-label="${window.mempulse.escapeHtml(formattedDate)}: ${reviewedCards} cards reviewed"></span>`;
    }).join("");
}

function getDayDifference(firstDate, secondDate) {
    return Math.round((new Date(secondDate) - new Date(firstDate)) / dayInMilliseconds);
}

function calculateStreaks(activityData) {
    let currentStreak = 0;
    let bestStreak = 0;
    let runningStreak = 0;

    activityData.forEach((day, index) => {
        const previousDay = activityData[index - 1];
        const continuesStreak = previousDay && getDayDifference(previousDay.date, day.date) === 1;

        runningStreak = day.reviewedCards > 0 && continuesStreak ? runningStreak + 1 : day.reviewedCards > 0 ? 1 : 0;
        bestStreak = Math.max(bestStreak, runningStreak);
    });

    for (let index = activityData.length - 1; index >= 0; index -= 1) {
        const day = activityData[index];
        const previousDay = activityData[index - 1];
        const continuesStreak = previousDay && getDayDifference(previousDay.date, day.date) === 1;

        if (day.reviewedCards === 0 || (currentStreak > 0 && !continuesStreak)) {
            break;
        }

        currentStreak += 1;
    }

    return { currentStreak, bestStreak };
}

function renderStreak(activityData) {
    const { currentStreak, bestStreak } = calculateStreaks(activityData);

    if (currentStreakElement) {
        currentStreakElement.textContent = currentStreak;
    }

    if (bestStreakElement) {
        bestStreakElement.textContent = `${bestStreak} ${bestStreak === 1 ? "day" : "days"}`;
    }

    if (streakMessageElement) {
        streakMessageElement.textContent = currentStreak > 0
            ? "Keep the rhythm going."
            : "Start a review session to begin your streak.";
    }
}

window.updateStreak = (activityData) => {
    renderActivity(activityData);
    renderStreak(activityData);
};

const initialActivityData = getActivityData();
renderActivity(initialActivityData);
renderStreak(initialActivityData);
