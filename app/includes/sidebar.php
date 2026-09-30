<aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-surface-container-lowest border-r border-outline-variant/40 z-50 flex flex-col justify-between py-6 px-4 shadow-[0_1px_8px_rgba(0,0,0,0.03)] transition-all duration-300 ease-in-out">
        <div class="flex flex-col gap-8">

            <!-- Outer layout section container -->
            <div id="sidebar-header" class="flex items-center justify-between gap-3 px-3 transition-all duration-300">
                <!-- Inner container holding the logo image assets -->
                <div class="flex items-center gap-3 flex-shrink-0">
                    <img alt="MemPulse Logo" class="h-12 w-auto object-contain flex-shrink-0" src="./assets/images/mempulse-logo.png" />
                    <span class="logo-text font-headline-sm text-headline-sm font-semibold tracking-tight text-on-surface transition-all duration-200">MemPulse</span>
                </div>

                <!-- Toggle Action Button element -->
                <button id="sidebar-toggle" class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-colors flex-shrink-0" title="Toggle Sidebar">
                    <span class="material-symbols-outlined text-[24px]">left_panel_close</span>
                </button>
            </div>
            <nav class="flex flex-col gap-1.5" data-active-classes="bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm">
                <!-- Home Page -->
                <a
                    aria-current="<?= $currentPage === 'home' ? 'page' : 'false' ?>"
                    class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group
                    <?= $currentPage === 'home'
                        ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm'
                        : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                    ?>"
                    data-path="home"
                    href="index.php"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">
                            home
                        </span>
                        <span class="sidebar-text transition-opacity duration-200">Home</span>
                    </div>
                </a>

                <!-- Decks Page -->
                <a
                    aria-current="<?= $currentPage === 'decks' ? 'page' : 'false' ?>"
                    class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group
                    <?= $currentPage === 'decks'
                        ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm'
                        : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                    ?>"
                    data-path="decks"
                    href="decks.php"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">
                            style
                        </span>
                        <span class="sidebar-text transition-opacity duration-200">Decks</span>
                    </div>
                </a>

                <!-- Review Page -->
                <a
                    aria-current="<?= $currentPage === 'review' ? 'page' : 'false' ?>"
                    class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group
                    <?= $currentPage === 'review'
                        ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm'
                        : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                    ?>"
                    data-path="review"
                    href="review.php"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">
                            pace
                        </span>

                        <span class="sidebar-text transition-opacity duration-200">
                            Review
                        </span>
                    </div>

                    <!-- Review badge -->
                    <span class="sidebar-text px-2 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm tracking-wide transition-opacity duration-200">
                        42
                    </span>
                </a>

                <!-- Reports -->
                <a
                    aria-current="<?= $currentPage === 'reports' ? 'page' : 'false' ?>"
                    class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group
                    <?= $currentPage === 'reports'
                        ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm'
                        : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                    ?>"
                    data-path="reports"
                    href="reports.php"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">
                            monitoring
                        </span>
                        <span class="sidebar-text transition-opacity duration-200">Reports</span>
                    </div>
                </a>
                <!-- Science -->
                <a
                    aria-current="<?= $currentPage === 'science' ? 'page' : 'false' ?>"
                    class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group
                    <?= $currentPage === 'science'
                        ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm'
                        : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                    ?>"
                    data-path="science"
                    href="science.php"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">
                            psychology
                        </span>
                        <span class="sidebar-text transition-opacity duration-200">Science</span>
                    </div>
                </a>
            </nav>
        </div>
        <div class="flex flex-col gap-2 pt-4 border-t border-outline-variant/30">
            <nav class="flex flex-col gap-1" data-active-classes="bg-surface-container-high text-on-surface font-semibold">
                <!-- Settings -->
                <a
                    aria-current="<?= $currentPage === 'settings' ? 'page' : 'false' ?>"
                    class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group
                    <?= $currentPage === 'settings'
                        ? 'bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm'
                        : 'font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface'
                    ?>"
                    data-path="settings"
                    href="settings.php"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">
                            settings
                        </span>
                        <span class="sidebar-text transition-opacity duration-200">Settings</span>
                    </div>
                </a>
                <a class="flex items-center gap-3 px-3.5 py-2 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-error transition-all" 
                data-path="logout" 
                href="../authentication/logout.php">
                <span class="material-symbols-outlined text-[24px]">logout</span>
                <span class="sidebar-text transition-opacity duration-200">Logout</span>
                </a>

            </nav>
        </div>
    </aside>