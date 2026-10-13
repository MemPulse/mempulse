settings.php
```
<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$user_id = $_SESSION['user_id'];

$pageTitle = "Settings"; 
$currentPage = "settings";
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        

        <!-- Page-specific content goes here -->
    </main>

</body>
</html>
```

science.php
```
<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit;
}

require_once __DIR__ . '/../../config/db.php';

$user_id = $_SESSION['user_id'];

$pageTitle = "Science"; 
$currentPage = "science";
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <!-- Page-specific content goes here -->
    </main>

</body>
</html>
```

app.css
```
@layer base {

    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        overflow-y: auto !important;
        overscroll-behavior-y: auto;
    }

    body {
        overscroll-behavior: none;
    }

    main {
        min-height: 100vh;
        overflow-y: auto !important;
    }

    main>:first-child {
        margin-top: 0 !important;
    }

    main>:last-child {
        margin-bottom: 0 !important;
    }
}

/* Subtle custom scrollbar for page scrolling */
html,
body,
main {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.35) transparent;
}

html {
    scroll-behavior: smooth;
}

html::-webkit-scrollbar,
body::-webkit-scrollbar,
main::-webkit-scrollbar {
    display: block;
    width: 6px;
}

html::-webkit-scrollbar-track,
body::-webkit-scrollbar-track,
main::-webkit-scrollbar-track {
    background: transparent;
}

html::-webkit-scrollbar-thumb,
body::-webkit-scrollbar-thumb,
main::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.35);
    border-radius: 9999px;
}

html::-webkit-scrollbar-thumb:hover,
body::-webkit-scrollbar-thumb:hover,
main::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.6);
}

/* Sidebar section divider */
#sidebar .sidebar-section-divider {
    flex-shrink: 0;
    margin: 0 1rem;
    border: 0;
    border-top: 1px solid var(--color-outline-variant, #cbd5e1);
    opacity: 0.4;
    transition: margin 0.3s ease;
}


/* Sidebar Toggle */
/* --- Base Collapsed Sidebar Rules (Desktop Only) --- */
@media (min-width: 768px) {

    /* Prevent initial main-content layout shift */
    html.sidebar-collapsed #main-content {
        padding-left: 80px !important;
    }

    html.sidebar-collapsed #main-content header {
        left: 80px !important;
    }

    #sidebar.collapsed {
        box-sizing: border-box !important;
        width: 80px !important;
        min-width: 80px !important;
        max-width: 80px !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        /* Allow the desktop toggle to protrude beyond the sidebar edge. */
        overflow-x: visible !important;
        overflow-y: visible !important;
    }

    /* Keep the toggle attached to the sidebar edge in either state. */
    #sidebar-toggle {
        position: absolute !important;
        right: -14px !important;
        top: 2rem !important;
        z-index: 60 !important;
    }

    #sidebar.collapsed .sidebar-section-divider {
        margin-left: 12px;
        margin-right: 12px;
    }

    #sidebar.collapsed>.flex.flex-col.gap-4 {
        padding-left: 0 !important;
        padding-right: 0 !important;
        align-items: center !important;
    }

    #sidebar.collapsed .sidebar-navigation {
        box-sizing: border-box !important;
        min-width: 0 !important;
        width: 100% !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        scrollbar-width: thin;
    }

    #sidebar.collapsed .sidebar-navigation>.sidebar-row {
        width: 44px !important;
        min-width: 44px !important;
        max-width: 44px !important;
        flex: 0 0 44px !important;
    }

    /* Hide standard layout text elements */
    #sidebar.collapsed .sidebar-text {
        opacity: 0 !important;
        display: none !important;
    }

    /* Hide the logo name text string when collapsed */
    #sidebar.collapsed .logo-text {
        opacity: 0 !important;
        width: 0 !important;
        display: none !important;
    }

    /* Change header layout structure from a row grid to vertical stack */
    #sidebar.collapsed #sidebar-header {
        flex-direction: column !important;
        gap: 16px !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        align-items: center !important;
    }

    #sidebar.collapsed #sidebar-header>div {
        order: 1 !important;
    }

    #sidebar.collapsed #sidebar-toggle {
        order: 2 !important;
    }

    /* ─── NEW: FORCE MAIN CONTENT AND FIXED HEADER TO SHIFT LEFT ─── */
    main.collapsed {
        padding-left: 80px !important;
        /* Shakes off the pl-72 padding */
    }

    main.collapsed header {
        left: 80px !important;
        /* Shakes off the left-72 fixed offset */
    }
}

/* --- Streak Heatmap Tooltip Styles (Pure CSS Absolute Positioning) --- */
#heatmap-scroll {
    position: relative;
    overflow-x: auto;
}

#heatmap-scroll .heatmap-cell {
    position: relative;
    cursor: pointer;
    outline: none !important;
    -webkit-tap-highlight-color: transparent;
}

#heatmap-scroll .heatmap-cell:hover {
    z-index: 100;
}

/* Tooltip box */
#heatmap-scroll .heatmap-cell::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #1e293b;
    color: #ffffff;
    font-size: 11px;
    line-height: 1.3;
    padding: 6px 10px;
    border-radius: 8px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s ease;
    z-index: 9999;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Tooltip arrow */
#heatmap-scroll .heatmap-cell::before {
    content: '';
    position: absolute;
    bottom: calc(100% + 2px);
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #1e293b;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.15s ease;
    z-index: 101;
}

#heatmap-scroll .heatmap-cell:hover::after,
#heatmap-scroll .heatmap-cell:hover::before {
    opacity: 1;
}

/* --- Universal Heatmap Scrollbar Rules (Visible in All Views) --- */
#heatmap-scroll {
    overflow-x: auto !important;
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.35) transparent;
}

#heatmap-scroll::-webkit-scrollbar {
    display: block !important;
    height: 6px;
}

#heatmap-scroll::-webkit-scrollbar-track {
    background: transparent;
}

#heatmap-scroll::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.35);
    border-radius: 999px;
}

#heatmap-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(100, 116, 139, 0.6);
}

/* --- Archived Decks Button Pulse Animation --- */
@keyframes archivePulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(var(--color-primary, 59, 130, 246), 0.7);
    }

    50% {
        transform: scale(1.05);
        box-shadow: 0 0 12px 4px rgba(var(--color-primary, 59, 130, 246), 0.5);
    }

    100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(var(--color-primary, 59, 130, 246), 0);
    }
}

.animate-archive-pulse {
    animation: archivePulse 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) 2;
    color: currentColor !important;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* ==========================================================================
   Flashcard Table & Resizable Columns
   ========================================================================== */

/* Resizable Header Cell Handle */
.resizable-col {
    position: relative;
    user-select: none;
}

.resizer {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 6px;
    cursor: col-resize;
    user-select: none;
    touch-action: none;
    z-index: 10;
    transition: background-color 0.15s ease;
}

.resizer:hover,
.resizer.resizing {
    background-color: var(--color-primary, #3b82f6);
}

/* Selected Row Highlighting */
#flashcards-table-body tr.selected-row {
    background-color: rgba(var(--color-primary-rgb, 59, 130, 246), 0.08) !important;
    border-left: 3px solid var(--color-primary, #3b82f6);
}

/* Custom Scrollbars for Table Header & Body Cells */
#flashcard-table-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.35) transparent;
}

#flashcard-table-scroll-container::-webkit-scrollbar {
    height: 6px;
}

#flashcard-table-scroll-container::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.35);
    border-radius: 999px;
}

/* ==========================================================================
   Card Editor Panel Drawer
   ========================================================================== */

#card-editor-panel {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
        opacity 0.25s cubic-bezier(0.4, 0, 0.2, 1),
        margin 0.25s cubic-bezier(0.4, 0, 0.2, 1),
        width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform, opacity;
}

#card-editor-panel.collapsed {
    display: none !important;
}

/* ==========================================================================
   Tabulator Grid - MemPulse Custom Styling
   ========================================================================== */

/* Main Table Container Override */
.tabulator {
    background-color: transparent !important;
    border: none !important;
    font-family: inherit !important;
    font-size: 0.875rem !important;
    /* 14px */
}

/* Header Styling */
.tabulator .tabulator-header {
    background-color: var(--color-surface-container-low, #f1f5f9) !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.25) !important;
    color: var(--color-on-surface-variant, #64748b) !important;
    font-weight: 700 !important;
    font-size: 0.75rem !important;
    /* 12px */
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
}

.tabulator .tabulator-header .tabulator-col {
    background-color: transparent !important;
    border-right: 1px solid rgba(148, 163, 184, 0.15) !important;
    padding: 8px 12px !important;
}

.tabulator .tabulator-header .tabulator-col-content {
    padding: 0 !important;
}

/* Header Resizer Drag Handles */
.tabulator .tabulator-header .tabulator-col .tabulator-col-resize-handle {
    width: 6px !important;
    right: 0 !important;
    opacity: 0;
    transition: opacity 0.15s ease, background-color 0.15s ease;
}

.tabulator .tabulator-header .tabulator-col:hover .tabulator-col-resize-handle,
.tabulator .tabulator-header .tabulator-col .tabulator-col-resize-handle:hover {
    opacity: 1;
    background-color: var(--color-primary, #3b82f6) !important;
}

/* Row Styling (Non-Compact Readable Format) */
.tabulator .tabulator-tableholder .tabulator-table .tabulator-row {
    background-color: var(--color-surface-container-lowest, #ffffff) !important;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2) !important;
    color: var(--color-on-surface, #0f172a) !important;
    transition: background-color 0.15s ease;
    min-height: 36px !important;
}

/* Alternate Row Hover */
.tabulator .tabulator-tableholder .tabulator-table .tabulator-row:hover {
    background-color: rgba(241, 245, 249, 0.6) !important;
    cursor: pointer;
}

/* Selected Row Highlight */
.tabulator .tabulator-tableholder .tabulator-table .tabulator-row.tabulator-selected {
    background-color: rgba(59, 130, 246, 0.08) !important;
    border-left: 3px solid var(--color-primary, #3b82f6) !important;
}

/* Cell Padding & Alignment */
.tabulator .tabulator-cell {
    padding: 6px 12px !important;
    border-right: 1px solid rgba(148, 163, 184, 0.12) !important;
    display: inline-block !important;
    vertical-align: middle !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    line-height: 24px !important;
}

/* Cell Editing Focus State */
.tabulator .tabulator-cell.tabulator-editing {
    outline: 2px solid var(--color-primary, #3b82f6) !important;
    outline-offset: -2px !important;
    padding: 8px !important;
    background-color: #ffffff !important;
}

.tabulator .tabulator-cell.tabulator-editing textarea,
.tabulator .tabulator-cell.tabulator-editing input {
    font-family: inherit !important;
    font-size: 0.875rem !important;
    color: #0f172a !important;
    background: transparent !important;
    border: none !important;
    outline: none !important;
    width: 100% !important;
    resize: vertical !important;
}

/* Frozen Actions column: opaque, so scrolled content never shows through */
.tabulator .tabulator-cell.tabulator-frozen {
    background-color: #ffffff !important;
    box-shadow: 2px 0 6px rgba(0, 0, 0, 0.06) !important;
    z-index: 2;
}

.tabulator .tabulator-row:hover .tabulator-cell.tabulator-frozen {
    background-color: #f6f8fb !important;
}

.tabulator .tabulator-row.tabulator-selected .tabulator-cell.tabulator-frozen {
    background-color: #eff5fe !important;
}

.tabulator .tabulator-header .tabulator-col.tabulator-frozen {
    background-color: #f1f5f9 !important;
    /* same as the header background */
    box-shadow: 2px 0 6px rgba(0, 0, 0, 0.06) !important;
    z-index: 3;
}

/* Custom Scrollbar for Grid Container */
.tabulator .tabulator-tableholder {
    scrollbar-width: thin;
    scrollbar-color: rgba(100, 116, 139, 0.35) transparent;
}

.tabulator .tabulator-tableholder::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.tabulator .tabulator-tableholder::-webkit-scrollbar-thumb {
    background: rgba(100, 116, 139, 0.35);
    /* border-radius: 999px; */
}


/* ==========================================================================
   Sidebar Card Editor Collapse State
   ========================================================================== */
#card-editor-sidebar.collapsed #sidebar-content-wrapper {
    display: none !important;
}
```

tailwind-input.css
```
@tailwind base;
@tailwind components;
@tailwind utilities;
```

theme.css
```
/* MemPulse theme tokens. Light values come from the old Tailwind config; dark values are new.
   Tailwind colors read these as rgb(var(--c-name) / alpha). */

:root {
    --c-primary: 53 37 205;
    --c-on-surface-variant: 70 69 85;
    --c-on-secondary-fixed-variant: 90 0 198;
    --c-primary-fixed: 226 223 255;
    --c-on-tertiary-fixed: 42 23 0;
    --c-background: 248 249 255;
    --c-on-surface: 11 28 48;
    --c-secondary-container: 138 76 252;
    --c-secondary-fixed: 234 221 255;
    --c-primary-container: 79 70 229;
    --c-tertiary-fixed: 255 221 184;
    --c-surface-container-low: 239 244 255;
    --c-on-primary-container: 218 215 255;
    --c-inverse-surface: 33 49 69;
    --c-surface-container-lowest: 255 255 255;
    --c-on-primary: 255 255 255;
    --c-error: 186 26 26;
    --c-on-tertiary-fixed-variant: 101 62 0;
    --c-on-tertiary-container: 255 212 164;
    --c-surface-container-highest: 211 228 254;
    --c-on-tertiary: 255 255 255;
    --c-primary-fixed-dim: 195 192 255;
    --c-surface-container-high: 220 233 255;
    --c-on-secondary: 255 255 255;
    --c-surface-dim: 203 219 245;
    --c-surface: 248 249 255;
    --c-tertiary-fixed-dim: 255 185 95;
    --c-outline-variant: 199 196 216;
    --c-on-secondary-container: 255 251 255;
    --c-on-background: 11 28 48;
    --c-surface-bright: 248 249 255;
    --c-secondary-fixed-dim: 210 187 255;
    --c-secondary: 113 42 226;
    --c-on-error-container: 147 0 10;
    --c-inverse-primary: 195 192 255;
    --c-surface-tint: 77 68 227;
    --c-surface-container: 229 238 255;
    --c-on-primary-fixed: 15 0 105;
    --c-outline: 119 117 135;
    --c-surface-variant: 211 228 254;
    --c-inverse-on-surface: 234 241 255;
    --c-on-secondary-fixed: 37 0 90;
    --c-on-error: 255 255 255;
    --c-on-primary-fixed-variant: 51 35 204;
    --c-tertiary: 104 64 0;
    --c-tertiary-container: 136 85 0;
    --c-error-container: 255 218 214;
}

html.dark {
    --c-background: 11 18 32;
    --c-surface: 11 18 32;
    --c-surface-dim: 8 14 26;
    --c-surface-bright: 43 54 80;
    --c-surface-container-lowest: 18 26 43;
    --c-surface-container-low: 23 32 51;
    --c-surface-container: 28 38 64;
    --c-surface-container-high: 36 48 73;
    --c-surface-container-highest: 46 59 87;
    --c-surface-variant: 46 59 87;
    --c-on-background: 221 228 245;
    --c-on-surface: 221 228 245;
    --c-on-surface-variant: 199 196 216;
    --c-outline: 145 143 161;
    --c-outline-variant: 70 76 105;
    --c-inverse-surface: 221 228 245;
    --c-inverse-on-surface: 28 38 64;
    --c-inverse-primary: 53 37 205;
    --c-surface-tint: 195 192 255;
    --c-primary: 195 192 255;
    --c-on-primary: 29 0 165;
    --c-primary-container: 79 70 229;
    --c-on-primary-container: 226 223 255;
    --c-primary-fixed: 41 37 110;
    --c-primary-fixed-dim: 195 192 255;
    --c-on-primary-fixed: 226 223 255;
    --c-on-primary-fixed-variant: 195 192 255;
    --c-secondary: 210 187 255;
    --c-on-secondary: 63 0 142;
    --c-secondary-container: 111 54 217;
    --c-on-secondary-container: 243 232 255;
    --c-secondary-fixed: 53 32 107;
    --c-secondary-fixed-dim: 210 187 255;
    --c-on-secondary-fixed: 234 221 255;
    --c-on-secondary-fixed-variant: 210 187 255;
    --c-tertiary: 255 185 95;
    --c-on-tertiary: 71 42 0;
    --c-tertiary-container: 136 85 0;
    --c-on-tertiary-container: 255 226 192;
    --c-tertiary-fixed: 74 46 0;
    --c-tertiary-fixed-dim: 255 185 95;
    --c-on-tertiary-fixed: 255 221 184;
    --c-on-tertiary-fixed-variant: 255 203 133;
    --c-error: 255 180 171;
    --c-on-error: 105 0 5;
    --c-error-container: 147 0 10;
    --c-on-error-container: 255 218 214;

    /* app.css reads these (with light fallbacks) for the flashcard table */
    --color-surface-container-lowest: rgb(var(--c-surface-container-lowest));
    --color-surface-container-low: rgb(var(--c-surface-container-low));
    --color-on-surface: rgb(var(--c-on-surface));
    --color-on-surface-variant: rgb(var(--c-on-surface-variant));
}

/* Dark versions of the colors that are hard-coded in app.css (flashcard table) */
html.dark .tabulator .tabulator-tableholder .tabulator-table .tabulator-row:hover {
    background-color: rgb(var(--c-surface-container-low) / 0.7) !important;
}

html.dark .tabulator .tabulator-tableholder .tabulator-table .tabulator-row.tabulator-selected {
    background-color: rgb(var(--c-primary) / 0.16) !important;
}

html.dark .tabulator .tabulator-cell.tabulator-editing {
    background-color: rgb(var(--c-surface-container-high)) !important;
}

html.dark .tabulator .tabulator-cell.tabulator-editing textarea,
html.dark .tabulator .tabulator-cell.tabulator-editing input {
    color: rgb(var(--c-on-surface)) !important;
}

html.dark .tabulator .tabulator-cell.tabulator-frozen {
    background-color: rgb(var(--c-surface-container-lowest)) !important;
    box-shadow: 2px 0 6px rgba(0, 0, 0, 0.35) !important;
}

html.dark .tabulator .tabulator-row:hover .tabulator-cell.tabulator-frozen {
    background-color: rgb(var(--c-surface-container-low)) !important;
}

html.dark .tabulator .tabulator-row.tabulator-selected .tabulator-cell.tabulator-frozen {
    background-color: rgb(var(--c-surface-container)) !important;
}

html.dark .tabulator .tabulator-header .tabulator-col.tabulator-frozen {
    background-color: rgb(var(--c-surface-container-low)) !important;
    box-shadow: 2px 0 6px rgba(0, 0, 0, 0.35) !important;
}
```

dashboard.php
```
<!-- ========================================================================= -->
<!-- MAIN DASHBOARD CONTENT CONTAINER CONTAINER DIV                            -->
<!-- Outer responsive padding shell wrapping top metrics, carousel, and history -->
<!-- ========================================================================= -->
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <!-- DIV: Vertical Layout Flex Shell -->
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <!-- DIV: TOP SECTION - Core Analytics Cards, Action Carousel & Streak Bar -->
        <div class="flex flex-col gap-4 sm:gap-6 w-full">

            <!-- DIV: ROW 1 - Grid Split: 2x2 Metric Grid (Left) vs Hero Carousel (Right) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">
                
                <!-- DIV: LEFT COLUMN - 2x2 Core Metrics Card Grid Container -->
                <div class="lg:col-span-6 flex flex-col justify-between w-full min-w-0">
                    <!-- DIV: 2-Column Responsive Metric Grid Shell -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 w-full h-full">
                        
                        <!-- DIV: CARD 1 - Scheduled Cards Due Metric Container -->
                        <div class="bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-primary/50 hover:shadow-md transition-all">
                            <!-- DIV: Ambient Background Blur Decorative Shape -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-primary/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-primary/20 transition-all"></div>
                            
                            <!-- DIV: Card Header Row Shell (Title & Tooltip) -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <!-- DIV: Label & Information Tooltip Shell -->
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Scheduled Cards Category Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-primary">Scheduled</span>
                                    <!-- DIV: Informational Tooltip Wrapper -->
                                    <div class="relative group/tooltip flex items-center">
                                        <!-- SPAN: Info Graphic Icon -->
                                        <span class="material-symbols-outlined text-[15px] text-primary/60 cursor-help hover:text-primary transition-colors">info</span>
                                        <!-- DIV: Floating Tooltip Hover Content Container -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Total cards scheduled for review today.
                                            <!-- DIV: Tooltip Arrow Point Graphic -->
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- DIV: Metric Card Alarm Icon Container Badge -->
                                <div class="w-8 h-8 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Alarm Clock Icon Graphic -->
                                    <span class="material-symbols-outlined text-[18px]">alarm</span>
                                </div>
                            </div>
                            <!-- DIV: Metric Numerical Display Wrapper -->
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Cards Due Counter Output -->
                                <span id="stat-cards-due" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['cardsDue']; ?>
                                </span>
                                <!-- SPAN: Metric Name Subtitle -->
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Due Today</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 2 - Cards Reviewed Today Metric Container -->
                        <div class="bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-teal-500/50 hover:shadow-md transition-all">
                            <!-- DIV: Ambient Background Glow Graphic -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-teal-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-teal-500/20 transition-all"></div>
                            
                            <!-- DIV: Header Metric Shell -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <!-- DIV: Category Label Group -->
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Category Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-teal-700">Reviewed</span>
                                    <!-- DIV: Tooltip Hover Trigger -->
                                    <div class="relative group/tooltip flex items-center">
                                        <!-- SPAN: Info Graphic Icon -->
                                        <span class="material-symbols-outlined text-[15px] text-teal-700/60 cursor-help hover:text-teal-700 transition-colors">info</span>
                                        <!-- DIV: Floating Tooltip Box -->
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Total cards reviewed in completed study sessions today.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- DIV: Icon Badge Shell -->
                                <div class="w-8 h-8 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-600 flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Flashcards Graphic Icon -->
                                    <span class="material-symbols-outlined text-[18px]">style</span>
                                </div>
                            </div>
                            <!-- DIV: Numerical Value Group -->
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Cards Reviewed Counter Output -->
                                <span id="stat-reviewed-today" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['cardsReviewedToday']; ?>
                                </span>
                                <!-- SPAN: Metric Name Subtitle -->
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Reviewed Today</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 3 - Retention Rate Percentage Metric Container -->
                        <div class="bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-error/50 hover:shadow-md transition-all">
                            <!-- DIV: Decorative Background Overlay -->
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-rose-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-rose-500/20 transition-all"></div>
                            
                            <!-- DIV: Header Information Bar Shell -->
                            <div class="flex items-center justify-between w-full relative z-10">
                                <div class="flex items-center gap-1.5">
                                    <!-- SPAN: Retention Tag -->
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-error">Retention Rate</span>
                                    <div class="relative group/tooltip flex items-center">
                                        <span class="material-symbols-outlined text-[15px] text-error/60 cursor-help hover:text-error transition-colors">info</span>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Percentage of completed reviews where the learner successfully remembered the card.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-8 h-8 rounded-xl bg-red-50 border border-red-200/80 flex items-center justify-center text-error flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Accuracy Target Icon -->
                                    <span class="material-symbols-outlined text-[18px]">track_changes</span>
                                </div>
                            </div>
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Retention Output Percentage -->
                                <span id="stat-retention" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (float) $metricsData['retentionRate']; ?>%
                                </span>
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Retention Rate</span>
                            </div>
                        </div>

                        <!-- DIV: CARD 4 - Mastered Cards Metric Container -->
                        <div class="bg-gradient-to-br from-indigo-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-indigo-500/50 hover:shadow-md transition-all">
                            <div class="absolute right-0 bottom-0 w-20 h-20 bg-indigo-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-indigo-500/20 transition-all"></div>
                            
                            <div class="flex items-center justify-between w-full relative z-10">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-indigo-700">Mastered</span>
                                    <div class="relative group/tooltip flex items-center">
                                        <span class="material-symbols-outlined text-[15px] text-indigo-700/60 cursor-help hover:text-indigo-700 transition-colors">info</span>
                                        <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                            Cards that have reached a review interval of at least 21 days.
                                            <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-indigo-600 flex-shrink-0 shadow-xs">
                                    <!-- SPAN: Verified Badge Icon -->
                                    <span class="material-symbols-outlined text-[18px]">verified</span>
                                </div>
                            </div>
                            <div class="flex flex-col mt-2 relative z-10">
                                <!-- SPAN: Dynamic Mastered Cards Count Output -->
                                <span id="stat-mastered" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">
                                    <?php echo (int) $metricsData['masteredCards']; ?>
                                </span>
                                <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Mastered</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- DIV: RIGHT COLUMN - Dynamic Action Hero Carousel Container -->
                <div class="lg:col-span-6 flex w-full min-w-0 h-full">
                    <!-- SECTION: Interactive Carousel Wrapper Shell -->
                    <section class="relative w-full h-full rounded-2xl overflow-hidden shadow-xs border border-outline-variant/30 min-h-[280px] bg-gradient-to-br from-inverse-surface via-slate-900 to-inverse-surface text-inverse-on-surface flex flex-col justify-between" id="hero-carousel" aria-label="Learning Quick Actions Carousel">
                        <!-- DIV: Background Ambient Glow Effects -->
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>

                        <!-- DIV: Carousel Slide Frame Wrapper -->
                        <div class="relative w-full h-full min-h-[280px]">
                            
                            <!-- DIV: SLIDE 0 - Memory Science -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-100 z-10" data-slide-index="0" aria-hidden="false">
                                <!-- DIV: Slide Header & Description Block -->
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-primary/20 border border-primary/30 backdrop-blur-md text-cyan-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">psychology</span>
                                        <span>Memory Science</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Understand How Memory Works
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Explore the science behind spaced repetition, forgetting curves, and effective long-term learning.
                                    </p>
                                </div>
                                <!-- DIV: Slide Action Button & Wayfinder Dots Bar -->
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="science.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Learn More</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <!-- DIV: Wayfinder Pagination Navigation Dots Shell -->
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 1 - Decks -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="1" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-teal-500/20 border border-teal-500/30 backdrop-blur-md text-teal-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">style</span>
                                        <span>Decks</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        View and Organize Your Decks
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Manage your study collections, browse your cards, and keep your learning materials organized.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="decks.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>View Decks</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 2 - Flashcard Creation -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="2" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-indigo-500/20 border border-indigo-500/30 backdrop-blur-md text-indigo-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">edit_note</span>
                                        <span>Flashcard Creation</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Create Your Flashcards
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Build focused study cards and turn complex subjects into concise prompts for active recall.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="decks.php?add_card=1" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Create Flashcards</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 3 - Review & History -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="3" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-amber-500/20 border border-amber-500/30 backdrop-blur-md text-amber-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">pace</span>
                                        <span>Review & History</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        Review Cards and Track Your History
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Start a review session, reinforce what you know, and view your completed review history.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="review.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>Review Now</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                            <!-- DIV: SLIDE 4 - Reports & Statistics -->
                            <div class="hero-slide absolute inset-0 transition-opacity duration-500 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="4" aria-hidden="true">
                                <div class="relative z-10 flex flex-col gap-2">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-purple-500/20 border border-purple-500/30 backdrop-blur-md text-purple-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                        <span class="material-symbols-outlined text-[14px]">monitoring</span>
                                        <span>Reports & Statistics</span>
                                    </div>
                                    <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                        See Your Learning Progress
                                    </h2>
                                    <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                        Explore detailed statistics and reports to understand your review activity and learning progress.
                                    </p>
                                </div>
                                <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                    <a href="reports.php" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                        <span>View Reports</span>
                                        <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                    </a>
                                    <div class="flex items-center gap-2 hero-dots-container" aria-label="Carousel navigation">
                                        <button type="button" title="Memory Science" aria-label="Slide 1: Memory Science" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0" aria-current="true"></button>
                                        <button type="button" title="Decks" aria-label="Slide 2: Decks" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1" aria-current="false"></button>
                                        <button type="button" title="Create Flashcards" aria-label="Slide 3: Create Flashcards" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2" aria-current="false"></button>
                                        <button type="button" title="Review & History" aria-label="Slide 4: Review & History" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="3" aria-current="false"></button>
                                        <button type="button" title="Reports & Statistics" aria-label="Slide 5: Reports & Statistics" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="4" aria-current="false"></button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>
                </div>

            </div>

                        <?php
            // ---- "Due Now" call-to-action state ----
            $dueTotal     = (int) ($metricsData['cardsDue'] ?? 0);
            $dueDecks     = $dueByDeck ?? [];
            $nextDueSecs  = $nextDueSecs ?? null;
            $curStreak    = (int) ($streakData['currentStreak'] ?? 0);
            $streakAtRisk = $dueTotal > 0 && $curStreak > 0 && (int) ($metricsData['cardsReviewedToday'] ?? 0) === 0;
            $reviewHref   = count($dueDecks) === 1 ? 'review.php?deck_id=' . (int) $dueDecks[0]['id'] : 'review.php';

            if ($streakAtRisk) {
                $dueSubtitle = 'Keep your ' . $curStreak . '-day streak alive';
            } elseif (count($dueDecks) === 1) {
                $dueSubtitle = 'in ' . $dueDecks[0]['title'];
            } elseif (count($dueDecks) > 1) {
                $dueSubtitle = 'across ' . count($dueDecks) . ' decks';
            } else {
                $dueSubtitle = '';
            }

            $nextDueLabel = '';
            if ($nextDueSecs !== null && $nextDueSecs > 0) {
                if ($nextDueSecs < 3600)       $nextDueLabel = 'Next cards due in ' . max(1, (int) ceil($nextDueSecs / 60)) . ' min';
                elseif ($nextDueSecs < 86400)  $nextDueLabel = 'Next cards due in ' . (int) floor($nextDueSecs / 3600) . ' h';
                else                           $nextDueLabel = 'Next cards due in ' . (int) floor($nextDueSecs / 86400) . ' d';
            }
            ?>

            <!-- DIV: ROW 2 - Grid Split: Streak Stats (Left, matches heatmap) vs Due Now CTA (Right, matches carousel) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full">

                <!-- DIV: LEFT COLUMN - Streak Analytics Metric Bar -->
                <div class="lg:col-span-6 flex w-full min-w-0 order-last lg:order-none">
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-2 sm:p-2.5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-1 sm:gap-3 w-full h-full">
                        <!-- DIV: Metric 1 - Best Historical Streak -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-primary/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-primary-fixed/60 border border-primary-fixed-dim/40 flex items-center justify-center text-primary flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">emoji_events</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Best Streak</span>
                                <span id="stat-best-streak" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-primary transition-colors">
                                    <?php echo (int) $streakData['bestStreak']; ?> days
                                </span>
                            </div>
                        </div>
                        <!-- DIV: Vertical Divider -->
                        <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>

                        <!-- DIV: Metric 2 - Current Active Streak -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-amber-500/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-500 flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">local_fire_department</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Current Streak</span>
                                <span id="stat-current-streak" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-amber-600 transition-colors">
                                    <?php echo (int) $streakData['currentStreak']; ?> days
                                </span>
                            </div>
                        </div>
                        <!-- DIV: Vertical Divider -->
                        <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>

                        <!-- DIV: Metric 3 - Streak Accuracy Percentage -->
                        <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-teal-500/5 transition-all group/streak cursor-default">
                            <div class="w-8 h-8 rounded-lg bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-500 flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-[18px]">track_changes</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Streak Acc.</span>
                                <span id="stat-streak-accuracy" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-teal-600 transition-colors">
                                    <?php echo (float) $streakData['streakAccuracy']; ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DIV: RIGHT COLUMN - Due Now Call-To-Action (same width as carousel / recent sessions) -->
                <div class="lg:col-span-6 flex w-full min-w-0 order-first lg:order-none">

                    <?php if ($dueTotal > 0): ?>
                        <!-- A: Cards due - primary call-to-action -->
                        <a href="<?php echo htmlspecialchars($reviewHref, ENT_QUOTES, 'UTF-8'); ?>"
                           id="stat-due-cta"
                           aria-label="Review <?php echo $dueTotal; ?> due <?php echo $dueTotal === 1 ? 'card' : 'cards'; ?>"
                           class="group/cta relative overflow-hidden flex items-center justify-between gap-4 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-gradient-to-r from-primary to-primary-container text-on-primary shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                            <!-- DIV: Ambient Glow -->
                            <div class="absolute -right-6 -top-8 w-28 h-28 bg-white/15 rounded-full blur-2xl pointer-events-none"></div>

                            <!-- DIV: Icon + Text -->
                            <div class="relative z-10 flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-[22px]">alarm</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-primary/80">Due Now</span>
                                    <span class="text-base sm:text-lg font-extrabold leading-tight truncate">
                                        <span id="stat-due-cta-count"><?php echo $dueTotal; ?></span>
                                        <?php echo $dueTotal === 1 ? 'card' : 'cards'; ?> ready to review
                                    </span>
                                    <?php if ($dueSubtitle !== ''): ?>
                                        <span class="text-[11px] sm:text-xs text-on-primary/80 truncate"><?php echo htmlspecialchars($dueSubtitle, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- DIV: Action Pill -->
                            <span class="relative z-10 inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 rounded-xl bg-white text-primary font-label-md text-xs font-bold shadow-xs flex-shrink-0">
                                <span class="hidden sm:inline">Start Review</span>
                                <span class="material-symbols-outlined text-[18px] transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                            </span>
                        </a>

                    <?php elseif ($nextDueSecs !== null): ?>
                        <!-- B: Nothing due, cards are scheduled -->
                        <div id="stat-due-cta" class="flex items-center gap-3 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-200/80 flex items-center justify-center text-emerald-600 flex-shrink-0">
                                <span class="material-symbols-outlined text-[22px]">task_alt</span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Due Now</span>
                                <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface">All caught up</span>
                                <?php if ($nextDueLabel !== ''): ?>
                                    <span class="text-[11px] sm:text-xs text-on-surface-variant"><?php echo htmlspecialchars($nextDueLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- C: No cards at all - point to creation -->
                        <a href="decks.php?add_card=1" id="stat-due-cta"
                           class="group/cta flex items-center justify-between gap-4 w-full h-full min-h-[64px] px-4 sm:px-5 py-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30 shadow-xs hover:border-primary/50 hover:shadow-md transition-all">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                                    <span class="material-symbols-outlined text-[22px]">add</span>
                                </div>
                                <div class="flex flex-col min-w-0">
                                    <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">No Due Today</span>
                                    <span class="text-base sm:text-lg font-extrabold leading-tight text-on-surface truncate">Create new cards</span>
                                </div>
                            </div>
                            <span class="material-symbols-outlined text-[20px] text-primary flex-shrink-0 transition-transform group-hover/cta:translate-x-1">arrow_forward</span>
                        </a>
                    <?php endif; ?>

                </div>
            </div>

        </div>

        <!-- DIV: BOTTOM SECTION - Streak Heatmap Grid & Recent Activity History -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 w-full items-stretch mt-2">
            
            <!-- DIV: Streak Heatmap Card Container Shell -->
            <div class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between min-h-[320px] min-w-0 overflow-visible">
                
                <!-- DIV: Heatmap Title & Intensity Legend Wrapper -->
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">calendar_month</span>
                            <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Streak Heatmap</h3>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">
                            Last 365 days • <span class="font-semibold text-on-surface"><?php echo (int) $streakData['studyDays']; ?> / 365</span> study days
                        </p>
                    </div>

                    <!-- DIV: Activity Level Color Legend -->
                    <div class="flex items-center gap-1.5 font-label-sm text-[10px] text-on-surface-variant">
                        <span>Less</span>
                        <span class="w-3 h-3 rounded bg-surface-container-high"></span>
                        <span class="w-3 h-3 rounded bg-primary-fixed"></span>
                        <span class="w-3 h-3 rounded bg-primary-fixed-dim"></span>
                        <span class="w-3 h-3 rounded bg-primary-container"></span>
                        <span class="w-3 h-3 rounded bg-primary"></span>
                        <span>More</span>
                    </div>
                </div>

                <!-- DIV: Heatmap CSS Grid Scroll Area Wrapper -->
                <div id="heatmap-scroll" class="flex-1 flex items-center py-4 w-full overflow-x-auto">
                    <div class="w-max grid justify-start" style="--cell: 16px; gap: 3px; grid-template-columns: auto repeat(53, var(--cell)); grid-template-rows: auto repeat(7, var(--cell));">

                        <?php foreach ([3 => 'Mon', 5 => 'Wed', 7 => 'Fri'] as $row => $name): ?>
                            <!-- SPAN: Day Name Axis Labels -->
                            <span class="flex items-center pr-2 text-[10px] leading-none whitespace-nowrap text-on-surface-variant select-none" style="grid-column: 1; grid-row: <?php echo $row; ?>;">
                                <?php echo $name; ?>
                            </span>
                        <?php endforeach; ?>

                        <?php
                        $weekStart = clone $streakData['calendarStart'];
                        for ($w = 0; $w < 53; $w++) {
                            for ($d = 0; $d < 7; $d++) {
                                $date = clone $weekStart;
                                $date->modify('+' . $d . ' days');

                                if ($date < $streakData['heatmapStart'] || $date > $streakData['heatmapEnd']) continue;

                                if ($date->format('j') === '1' && $w <= 51) {
                                    echo '<div class="text-[10px] leading-none font-medium whitespace-nowrap text-on-surface-variant" style="grid-row: 1; grid-column: ' . ($w + 2) . ' / span 3;">' . htmlspecialchars($date->format('M'), ENT_QUOTES) . '</div>';
                                }

                                $key = $date->format('Y-m-d');
                                $reviews = $streakData['heatmapData'][$key] ?? 0;
                                $tooltip = $date->format('M j, Y') . ' • ' . ($reviews === 0 ? 'No reviews' : ($reviews === 1 ? '1 review' : $reviews . ' reviews'));
                                echo '<div class="heatmap-cell rounded-[4px] transition-transform hover:scale-110 ' . heatmap_level_class($reviews) . '" style="grid-row: ' . ($d + 2) . '; grid-column: ' . ($w + 2) . ';" data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"></div>';
                            }
                            $weekStart->modify('+7 days');
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- DIV: Recent Review Sessions Feed Card Shell -->
            <div class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between min-h-[290px] min-w-0">
                
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">history</span>
                            <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Recent Review Sessions</h3>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">Your latest spaced repetition drills</p>
                    </div>
                    <a href="sessions.php" class="text-xs text-primary hover:underline font-semibold flex items-center gap-1 transition-all">
                        <span>View All</span>
                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                    </a>
                </div>

                <!-- DIV: Session Items List Wrapper Container -->
                <div class="flex flex-col gap-2.5 flex-1 justify-center">
                    <?php if (!empty($recentSessions)): ?>
                        <?php foreach ($recentSessions as $session): ?>
                            <!-- DIV: Individual Session Card Shell -->
                            <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low border border-outline-variant/20 hover:border-primary/30 hover:bg-surface-container transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-[18px]">style</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-on-surface truncate">
                                            <?php echo htmlspecialchars($session['deckTitle'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <div class="flex items-center gap-2 text-[11px] text-on-surface-variant mt-0.5">
                                            <span><?php echo (int) $session['cardsReviewed']; ?> cards reviewed</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end flex-shrink-0 ml-2">
                                    <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                                        <?php echo (int) $session['accuracy']; ?>%
                                    </span>
                                    <span class="text-[10px] text-on-surface-variant/70 mt-1">
                                        <?php echo htmlspecialchars($session['formattedTime'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- DIV: Empty State Fallback Shell -->
                        <div class="flex flex-col items-center justify-center p-6 text-center text-on-surface-variant h-full">
                            <span class="material-symbols-outlined text-[32px] text-outline mb-2">history_edu</span>
                            <span class="text-sm font-medium">No recent review sessions found.</span>
                            <span class="text-xs mt-1 text-on-surface-variant/70">Complete a study session to see your history here.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>
```

MempulseTree.txt
```
PS C:\xampp\htdocs\mempulse> tree /f
Folder PATH listing
Volume serial number is 8E4B-9250
C:.
│   comebine_files.py
│   database-design.md
│   decks-ui layout.txt
│   generate_mock.py
│   MempulseTree.txt
│   mempulse_mock_data.sql
│   reports-page.txt
│   settings-save-session-database.txt
│   settings.txt
│   tailwind.config.js
│   tailwindcss.exe
│   
├───app
│   ├───api
│   │       account-actions.php
│   │       ArchiveService.php
│   │       DashboardService.php
│   │       deck-actions.php
│   │       DeckService.php
│   │       flashcard-actions.php
│   │       FlashcardService.php
│   │       ReportsService.php
│   │       ReviewHubService.php
│   │       save-session.php
│   │       SessionService.php
│   │       settings-actions.php
│   │       
│   ├───assets
│   │   ├───css
│   │   │       app.css
│   │   │       reports.css
│   │   │       tailwind-input.css
│   │   │       tailwind.css
│   │   │       theme.css
│   │   │       
│   │   ├───images
│   │   │   │   brain-dark.svg
│   │   │   │   brain-white.svg
│   │   │   │   decks-hero-icon.png
│   │   │   │   flashcards-hero-icon.png
│   │   │   │   forgetting-curve-hero-image.webp
│   │   │   │   mempulse-logo.png
│   │   │   │   profile-picture.webp.jpg
│   │   │   │   
│   │   │   └───deck-header-banner
│   │   │           dh1.jpg
│   │   │           dh10.jpg
│   │   │           dh11.jpg
│   │   │           dh12.jpg
│   │   │           dh2.jpg
│   │   │           dh3.jpg
│   │   │           dh5.webp
│   │   │           dh6.webp
│   │   │           dh7.jpg
│   │   │           dh8.jpg
│   │   │           dh9.jpg
│   │   │           
│   │   └───js
│   │           account-settings.js
│   │           archive-ui.js
│   │           deck.js
│   │           decks.js
│   │           hero-carousel.js
│   │           mp-tables.js
│   │           reports-cards.js
│   │           reports-decks.js
│   │           reports-overview.js
│   │           reports-sessions.js
│   │           reports.js
│   │           review.js
│   │           
│   ├───includes
│   │       head.php
│   │       sidebar.php
│   │       
│   └───views
│           dashboard.php
│           deck-view.php
│           deck.php
│           decks-view.php
│           decks.php
│           index.php
│           reports-cards.php
│           reports-decks.php
│           reports-overview.php
│           reports-sessions.php
│           reports-view.php
│           reports.php
│           review-hub-view.php
│           review.php
│           science.php
│           session-details.php
│           sessions.php
│           settings.php
│           
├───authentication
│       login.php
│       logout.php
│       privacy-policy.html
│       register.php
│       script.js
│       styles.css
│       terms.html
│       
├───config
│       db.php
│       
└───landing-page
        about.html
        index.html
        style.css
        
```

database-design.md
```
Schema
```sql
CREATE DATABASE IF NOT EXISTS mempulse_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE mempulse_db;


-- 1. USERS TABLE
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    password_changed_at DATETIME NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    INDEX idx_users_active (deleted_at)
) ENGINE=InnoDB;


-- 2. USER SETTINGS TABLE (SRS Configuration)
CREATE TABLE user_settings (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    remember_multiplier DECIMAL(4,2) NOT NULL DEFAULT 2.00,
    forgot_multiplier DECIMAL(4,2) NOT NULL DEFAULT 0.50,
    initial_interval_days INT UNSIGNED NOT NULL DEFAULT 1,
    maximum_interval_days INT UNSIGNED NOT NULL DEFAULT 365,
    theme ENUM('light', 'dark', 'system')NOT NULL DEFAULT 'system',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- 3. DECKS TABLE
CREATE TABLE decks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_decks_user_active (user_id, deleted_at)
) ENGINE=InnoDB;


-- 4. FLASHCARDS TABLE
CREATE TABLE flashcards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    deck_id BIGINT UNSIGNED NOT NULL,
    front TEXT NOT NULL,
    back TEXT NOT NULL,
    interval_days INT UNSIGNED NOT NULL DEFAULT 1,
    next_review_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    review_count INT UNSIGNED NOT NULL DEFAULT 0,
    lapses INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (deck_id)
        REFERENCES decks(id)
        ON DELETE CASCADE,
    INDEX idx_cards_deck_due (deck_id, deleted_at, next_review_at)
) ENGINE=InnoDB;


-- 5. REVIEW SESSIONS TABLE
CREATE TABLE review_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    deck_id BIGINT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,
    total_reviewed INT UNSIGNED NOT NULL DEFAULT 0,
    remembered_count INT UNSIGNED NOT NULL DEFAULT 0,
    accuracy_percentage DECIMAL(5,2)
        GENERATED ALWAYS AS (
            CASE
                WHEN total_reviewed > 0
                    THEN (remembered_count / total_reviewed) * 100
                ELSE 0.00
            END
        ) STORED,
    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (deck_id)
        REFERENCES decks(id)
        ON DELETE CASCADE,
    INDEX idx_user_sessions (user_id, started_at)
) ENGINE=InnoDB;


-- 6. REVIEW LOGS TABLE
CREATE TABLE review_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    card_id BIGINT UNSIGNED NULL,
    rating ENUM('remembered', 'forgot') NOT NULL,
    previous_interval INT UNSIGNED NOT NULL,
    new_interval INT UNSIGNED NOT NULL,
    response_time_ms INT UNSIGNED NULL,
    reviewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id)
        REFERENCES review_sessions(id)
        ON DELETE CASCADE,
    FOREIGN KEY (card_id)
        REFERENCES flashcards(id)
        ON DELETE SET NULL,
    INDEX idx_session_logs (session_id)
) ENGINE=InnoDB;
```
```

