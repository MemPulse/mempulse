<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/mempulse/app/');
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        (function () {
        var pref = 'system';
        try { pref = localStorage.getItem('mempulseTheme') || 'system'; } catch (e) {}
        var dark = pref === 'dark' || (pref === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        })();
    </script>

    <title><?php echo $pageTitle ?? 'MemPulse'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Plus+Jakarta+Sans:wght@600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    
    <!-- Sortable for deck and card positioning -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    
    <!-- Use BASE_URL for css assets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/app.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/tailwind.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/tailwind.css') ?: time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/theme.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/theme.css') ?: time(); ?>">
    
</head>