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
                <a aria-current="page" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition-all group bg-primary-fixed text-on-primary-fixed-variant font-semibold shadow-sm" data-path="home" href="#">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">home</span>
                        <span class="sidebar-text transition-opacity duration-200">Home</span>
                    </div>
                </a>
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all group" data-path="decks" href="#">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">style</span>
                        <span class="sidebar-text transition-opacity duration-200">Decks</span>
                    </div>
                </a>
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all group" data-path="review" href="#">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[24px] text-on-surface-variant group-hover:text-on-surface transition-colors">pace</span>
                        <span class="sidebar-text transition-opacity duration-200">Review</span>
                    </div>
                    <!-- FIX 2: Wrapped review badge in sidebar-text container so it disappears when collapsed -->
                    <span class="sidebar-text px-2 py-0.5 rounded-full bg-primary-container text-on-primary font-label-sm text-label-sm tracking-wide transition-opacity duration-200">42</span>
                </a>
            </nav>
        </div>
        <div class="flex flex-col gap-2 pt-4 border-t border-outline-variant/30">
            <nav class="flex flex-col gap-1" data-active-classes="bg-surface-container-high text-on-surface font-semibold">
                <a class="flex items-center gap-3 px-3.5 py-2 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface transition-all" data-path="settings" href="#">
                    <span class="material-symbols-outlined text-[24px]">settings</span>
                    <span class="sidebar-text transition-opacity duration-200">Settings</span>
                </a>
                <a class="flex items-center gap-3 px-3.5 py-2 rounded-xl font-label-lg text-label-lg text-on-surface-variant hover:bg-surface-container-low hover:text-error transition-all" data-path="logout" href="../authentication/logout.php">
                <span class="material-symbols-outlined text-[24px]">logout</span>
                <span class="sidebar-text transition-opacity duration-200">Logout</span>
                </a>

            </nav>
            <!-- <div class="mt-2 pt-2 px-2 flex items-center justify-between rounded-xl bg-surface-container-low/60 p-2.5">
                <div class="flex items-center gap-3 overflow-hidden"> -->
                    <!-- FIX 3: Removed the layout wrapping error. Image stays visible, texts go inside the target wrapper block -->
                    <!-- <img alt="Profile" class="w-8 h-8 rounded-full object-cover ring-1 ring-outline-variant/50 flex-shrink-0" src="./assets/images/profile-picture.webp.jpg" />

                    <div class="sidebar-text flex flex-col min-w-0 transition-opacity duration-200">
                        <span id="sidebar-profile-name" class="font-label-md text-label-md text-on-surface truncate font-semibold">--</span>
                        <span id="sidebar-profile-role" class="font-body-sm text-body-sm text-on-surface-variant truncate">Active Learner</span>
                    </div>
                </div> -->
                <!-- FIX 4: Wrapped more options action button in sidebar-text element -->
                <!-- <a class="sidebar-text text-on-surface-variant hover:text-primary transition-colors transition-opacity duration-200" data-path="user-account" href="#" title="Account details">
                    <span class="material-symbols-outlined text-[18px]">more_vert</span>
                </a>
            </div> -->
        </div>
    </aside>