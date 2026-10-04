<!-- ========================================================================= -->
<!-- HEADER CONTAINER DIV                                                       -->
<!-- Fixed navigation bar locked to top; adjusts on desktop sidebar toggle      -->
<!-- ========================================================================= -->
<header class="fixed top-0 left-0 md:left-72 right-0 h-16 bg-surface/85 backdrop-blur-md z-40 flex items-center justify-between px-3 sm:px-4 md:px-8 border-b border-outline-variant/20 transition-all duration-300 ease-in-out">
    
    <!-- BUTTON: Mobile Navigation Drawer Toggle Button -->
    <button id="mobile-menu-toggle" type="button" aria-label="Toggle Navigation Drawer" class="md:hidden p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low transition-colors flex-shrink-0">
        <!-- SPAN: Hamburger Icon Graphic -->
        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">menu</span>
    </button>

    <!-- DIV: Global Search Trigger Wrapper -->
    <div class="flex items-center gap-2 mx-1 sm:mx-2 md:mx-0 flex-1 min-w-0 max-w-[140px] sm:max-w-[220px] md:w-80">
        <!-- DIV: Interactive Search Command Palette Box -->
        <div class="flex items-center gap-2 w-full px-2.5 py-1.5 rounded-lg bg-surface-container-low border border-outline-variant/30 text-on-surface-variant cursor-pointer hover:border-primary/40 transition-colors">
            <!-- SPAN: Search Lens Graphic -->
            <span class="material-symbols-outlined text-[18px] flex-shrink-0" aria-hidden="true">search</span>
            <!-- SPAN: Search Placeholder Description -->
            <span class="font-body-sm text-body-sm flex-1 select-none truncate">Search...</span>
            <!-- SPAN: Keyboard Shortcut Hint Badge -->
            <span class="hidden md:inline-block font-label-sm text-label-sm px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant/80 border border-outline-variant/40">CTRL K</span>
        </div>
    </div>

    <!-- DIV: Dynamic Profile & Streak Counter Container -->
    <div class="flex items-center gap-1.5 sm:gap-2 md:gap-4 flex-shrink-0">
        
        <!-- DIV: Active Streak Visual Badge -->
        <div class="flex items-center gap-1 px-2 py-1 rounded-full bg-[#fffbeb] border border-amber-200/80 text-tertiary shadow-sm">
            <!-- SPAN: Flame Metric Icon -->
            <span class="material-symbols-outlined text-amber-500 text-[16px]" aria-hidden="true">local_fire_department</span>
            <!-- SPAN: Dynamic Active Streak Counter Output -->
            <span id="header-streak-count" class="font-label-sm text-label-sm font-semibold tracking-wide">
                <?php echo htmlspecialchars((string) ($current_streak ?? 0), ENT_QUOTES, 'UTF-8'); ?>
            </span>
        </div>

        <!-- ANCHOR: User Account Dashboard Profile Link -->
        <a class="flex items-center gap-2" data-path="user-account" href="account.php" title="View Account Details">
            <!-- IMG: User Avatar Display Thumbnail -->
            <img id="header-profile-img" alt="User Profile Avatar" class="w-7 h-7 md:w-8 md:h-8 rounded-full object-cover ring-2 ring-primary-fixed flex-shrink-0" src="../assets/images/profile-picture.webp.jpg">
            
            <!-- DIV: Profile Description Wrapper -->
            <div class="flex flex-col min-w-0">
                <!-- SPAN: Sanitized Dynamic User Full Name Display -->
                <span id="header-user-name" class="font-label-sm sm:font-label-md text-label-sm sm:text-label-md font-semibold text-on-surface leading-tight truncate max-w-[90px] sm:max-w-none">
                    <?php echo htmlspecialchars($displayName ?? 'Active Learner', ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <!-- SPAN: Static Subtext User Role Label -->
                <span class="text-[10px] text-on-surface-variant font-medium leading-none tracking-wide">
                    User Account
                </span>
            </div>
        </a>
    </div>
</header>