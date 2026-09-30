document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector("#sidebar");
    const mainContent = document.querySelector("main");
    const toggleBtn = document.querySelector("#sidebar-toggle");

    if (!sidebar || !toggleBtn) return;

    const toggleIcon = toggleBtn.querySelector(".material-symbols-outlined");

    // Restore sidebar state
    const sidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true";

    if (sidebarCollapsed) {
        sidebar.classList.add("collapsed");
        if (mainContent) {
            mainContent.classList.add("collapsed");
        }

        if (toggleIcon) {
            toggleIcon.textContent = "left_panel_open";
        }
    }

    // Toggle sidebar
    toggleBtn.addEventListener("click", () => {
        const isCollapsed = sidebar.classList.toggle("collapsed");

        if (mainContent) {
            mainContent.classList.toggle("collapsed", isCollapsed);
        }

        if (toggleIcon) {
            toggleIcon.textContent = isCollapsed
                ? "left_panel_open"
                : "left_panel_close";
        }

        // Save state for other pages
        localStorage.setItem("sidebarCollapsed", isCollapsed);
    });
});