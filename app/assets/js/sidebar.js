// Sidebar toggle logic
document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.querySelector("#sidebar");
    const mainContent = document.querySelector("main"); // 1. Target the main element
    const toggleBtn = document.querySelector("#sidebar-toggle");
    const toggleIcon = toggleBtn.querySelector(".material-symbols-outlined");

    toggleBtn.addEventListener("click", () => {
        // Toggle the collapsed layout state class on BOTH elements
        sidebar.classList.toggle("collapsed");
        mainContent.classList.toggle("collapsed"); // 2. Toggle main class

        // Swap the panel icons based on state
        if (sidebar.classList.contains("collapsed")) {
            toggleIcon.textContent = "left_panel_open";
        } else {
            toggleIcon.textContent = "left_panel_close";
        }
    });
});