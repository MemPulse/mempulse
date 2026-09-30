<header class="fixed top-0 left-72 right-0 h-16 bg-surface/85 backdrop-blur-md z-40 flex items-center justify-between px-8 border-b border-outline-variant/20 transition-all duration-300 ease-in-out">
            <div class="flex items-center gap-3 w-80">
                <div class="flex items-center gap-2 w-full px-3 py-1.5 rounded-lg bg-surface-container-low border border-outline-variant/30 text-on-surface-variant">
                    <span class="material-symbols-outlined text-[18px]">search</span>
                    <span class="font-body-sm text-body-sm flex-1 select-none">Search cards, tags, decks...</span>
                    <span class="font-label-sm text-label-sm px-1.5 py-0.5 rounded bg-surface-container text-on-surface-variant/80 border border-outline-variant/40">⌘K</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <!-- Streak Indicator Badge Wrapper -->
                <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-[#fffbeb] border border-amber-200/80 text-tertiary shadow-sm">
                    <span class="material-symbols-outlined text-amber-500 text-[18px]">local_fire_department</span>
                    <!-- ID ADDED: Targets the display text node inside your top layout block -->
                    <span id="header-streak-count" class="font-label-md text-label-md font-semibold tracking-wide">-- Days</span>
                </div>

                <!-- User Account Action Link -->
                <a class="flex items-center gap-2" data-path="user-account" href="#" title="View Account Details">
                    <!-- ID ADDED: Targets the source parameter image node directly -->
                    <img id="header-profile-img" alt="Profile" class="w-8 h-8 rounded-full object-cover ring-2 ring-primary-fixed" src="./assets/images/profile-picture.webp.jpg">
                </a>
            </div>
        </header>