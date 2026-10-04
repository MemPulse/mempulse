<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $pageTitle ?? 'My Website'; ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Plus+Jakarta+Sans:wght@600;700&amp;display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet" />
    
    <!-- Sortable for deck and card positioning -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    
    <link rel="stylesheet" href="../assets/css/app.css">
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">tailwind.config = { darkMode: "class", theme: { extend: { "colors": { "primary": "#3525cd", "on-surface-variant": "#464555", "on-secondary-fixed-variant": "#5a00c6", "primary-fixed": "#e2dfff", "on-tertiary-fixed": "#2a1700", "background": "#f8f9ff", "on-surface": "#0b1c30", "secondary-container": "#8a4cfc", "secondary-fixed": "#eaddff", "primary-container": "#4f46e5", "tertiary-fixed": "#ffddb8", "surface-container-low": "#eff4ff", "on-primary-container": "#dad7ff", "inverse-surface": "#213145", "surface-container-lowest": "#ffffff", "on-primary": "#ffffff", "error": "#ba1a1a", "on-tertiary-fixed-variant": "#653e00", "on-tertiary-container": "#ffd4a4", "surface-container-highest": "#d3e4fe", "on-tertiary": "#ffffff", "primary-fixed-dim": "#c3c0ff", "surface-container-high": "#dce9ff", "on-secondary": "#ffffff", "surface-dim": "#cbdbf5", "surface": "#f8f9ff", "tertiary-fixed-dim": "#ffb95f", "outline-variant": "#c7c4d8", "on-secondary-container": "#fffbff", "on-background": "#0b1c30", "surface-bright": "#f8f9ff", "secondary-fixed-dim": "#d2bbff", "secondary": "#712ae2", "on-error-container": "#93000a", "inverse-primary": "#c3c0ff", "surface-tint": "#4d44e3", "surface-container": "#e5eeff", "on-primary-fixed": "#0f0069", "outline": "#777587", "surface-variant": "#d3e4fe", "inverse-on-surface": "#eaf1ff", "on-secondary-fixed": "#25005a", "on-error": "#ffffff", "on-primary-fixed-variant": "#3323cc", "tertiary": "#684000", "tertiary-container": "#885500", "error-container": "#ffdad6" }, "borderRadius": { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" }, "spacing": { "margin": "2rem", "space-sm": "0.5rem", "space-lg": "1.5rem", "space-xl": "2.5rem", "gutter": "1.5rem", "space-md": "1rem", "space-xs": "0.25rem" }, "fontFamily": { "body-md": ["Inter"], "label-sm": ["Inter"], "headline-sm": ["Plus Jakarta Sans"], "headline-md": ["Plus Jakarta Sans"], "label-md": ["Inter"], "display": ["Plus Jakarta Sans"], "headline-lg-mobile": ["Plus Jakarta Sans"], "display-mobile": ["Plus Jakarta Sans"], "headline-lg": ["Plus Jakarta Sans"], "body-lg": ["Inter"], "label-lg": ["Inter"], "body-sm": ["Inter"] }, "fontSize": { "body-md": ["14px", { "lineHeight": "22px", "fontWeight": "400" }], "label-sm": ["11px", { "lineHeight": "14px", "fontWeight": "600" }], "headline-sm": ["18px", { "lineHeight": "26px", "fontWeight": "600" }], "headline-md": ["24px", { "lineHeight": "32px", "fontWeight": "600" }], "label-md": ["12px", { "lineHeight": "16px", "fontWeight": "500" }], "display": ["48px", { "lineHeight": "56px", "fontWeight": "700" }], "headline-lg-mobile": ["26px", { "lineHeight": "34px", "fontWeight": "600" }], "display-mobile": ["36px", { "lineHeight": "44px", "fontWeight": "700" }], "headline-lg": ["32px", { "lineHeight": "40px", "fontWeight": "600" }], "body-lg": ["16px", { "lineHeight": "26px", "fontWeight": "400" }], "label-lg": ["14px", { "lineHeight": "20px", "fontWeight": "500" }], "body-sm": ["12px", { "lineHeight": "18px", "fontWeight": "400" }] } } } };
    </script>

</head>