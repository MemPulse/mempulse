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

            <!-- DIV: ROW 2 - Streak Analytics Horizontal Metric Bar -->
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-2 sm:p-2.5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-1 sm:gap-3 w-full">
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