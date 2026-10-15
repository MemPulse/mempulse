<?php
// 1. Guard first: verifies session + admin role against the database, loads db.php.
require_once __DIR__ . '/../includes/guard.php';

// 2. Page settings used by head.php and sidebar.php.
$pageTitle   = 'Overview';
$currentPage = 'overview';

// Shell checks shown on the placeholder (remove once real content lands).
$shellChecks = [
    ['icon' => 'verified_user', 'label' => 'Role verified against the database', 'value' => 'Signed in as ' . $adminUser['name']],
    ['icon' => 'palette',       'label' => 'Theme loaded from your saved setting', 'value' => ucfirst($adminTheme)],
    ['icon' => 'key',           'label' => 'CSRF token issued for admin actions', 'value' => 'Ready'],
];
?>
<!DOCTYPE html>
<html lang="en">

<?php include __DIR__ . '/../includes/head.php'; ?>

<body class="bg-background font-body-md text-on-surface antialiased min-h-screen selection:bg-primary-fixed selection:text-on-primary-fixed">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main id="main-content" class="pl-0 md:pl-72 pt-16 md:pt-6 transition-all duration-300 ease-in-out">
        <div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
            <div class="flex flex-col w-full gap-4 sm:gap-6">

                <!-- Page header -->
                <header class="flex flex-col gap-1">
                    <h1 class="font-headline-md text-headline-lg-mobile sm:text-headline-lg font-bold tracking-tight text-on-surface">Admin Overview</h1>
                    <p class="text-sm text-on-surface-variant">Platform metrics and activity will appear here.</p>
                </header>

                <!-- Shell status panel -->
                <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-6 shadow-xs">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-[18px]">check_circle</span>
                        </div>
                        <h2 class="font-headline-sm text-lg font-bold text-on-surface">Admin shell is working</h2>
                    </div>

                    <ul class="divide-y divide-outline-variant/20">
                        <?php foreach ($shellChecks as $check): ?>
                            <li class="flex items-center justify-between gap-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="material-symbols-outlined text-[20px] text-on-surface-variant"><?= $check['icon'] ?></span>
                                    <span class="text-sm font-medium text-on-surface truncate"><?= adminEsc($check['label']) ?></span>
                                </div>
                                <span class="text-xs font-semibold text-on-surface-variant whitespace-nowrap"><?= adminEsc($check['value']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

            </div>
        </div>
    </main>

</body>
</html>