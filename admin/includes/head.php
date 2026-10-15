<?php
// Expects admin/includes/guard.php to be included already ($adminTheme, APP_URL, ADMIN_URL).
$pageTitle = $pageTitle ?? 'Overview';

// Shared styles live in app/ so the two areas can never drift apart.
$sharedCss = __DIR__ . '/../../app/assets/css/';
$cssVersion = function (string $file) use ($sharedCss) {
    return @filemtime($sharedCss . $file) ?: time();
};
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <script>
        (function () {
            var root = document.documentElement;

            // The saved preference lives in the database; mirror it into localStorage
            // so a fresh browser starts with the right theme (same key the app uses).
            try {
                localStorage.setItem('mempulseTheme', <?= json_encode($adminTheme) ?>);
            } catch (e) {}

            // Apply the theme before first paint (identical to app/includes/head.php).
            var pref = 'system';
            try {
                pref = localStorage.getItem('mempulseTheme') || 'system';
            } catch (e) {}

            var dark = pref === 'dark' ||
                (pref === 'system' &&
                window.matchMedia('(prefers-color-scheme: dark)').matches);

            root.classList.toggle('dark', dark);
            root.style.colorScheme = dark ? 'dark' : 'light';

            // Apply the sidebar preference before first paint.
            try {
                var collapsed =
                    window.innerWidth >= 768 &&
                    localStorage.getItem('sidebarCollapsed') === 'true';
                root.classList.toggle('sidebar-collapsed', collapsed);
            } catch (e) {
                root.classList.remove('sidebar-collapsed');
            }
        })();
    </script>

    <title><?= adminEsc($pageTitle) ?> | MemPulse Admin</title>

    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Plus+Jakarta+Sans:wght@600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />

    <!-- Shared design system, loaded from the app folder -->
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/app.css">
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/tailwind.css?v=<?= $cssVersion('tailwind.css') ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/theme.css?v=<?= $cssVersion('theme.css') ?>">
</head>