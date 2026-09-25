const themeToggle = document.querySelector("#theme-toggle");
const root = document.documentElement;
const themeIcon = themeToggle?.querySelector(".material-symbols-rounded");

function setTheme(isDark) {
    root.classList.toggle("dark", isDark);

    if (!themeToggle || !themeIcon) {
        return;
    }

    const nextTheme = isDark ? "light" : "dark";
    themeIcon.textContent = isDark ? "light_mode" : "dark_mode";
    themeToggle.setAttribute("aria-label", `Switch to ${nextTheme} mode`);
    themeToggle.setAttribute("title", `Switch to ${nextTheme} mode`);
    themeToggle.setAttribute("aria-pressed", String(isDark));
}

const savedTheme = localStorage.getItem("mempulse-theme");
setTheme(savedTheme === "dark");

themeToggle?.addEventListener("click", () => {
    const isDark = !root.classList.contains("dark");
    setTheme(isDark);
    localStorage.setItem("mempulse-theme", isDark ? "dark" : "light");
});

function escapeHtml(value) {
    return String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

window.mempulse = {
    escapeHtml
};