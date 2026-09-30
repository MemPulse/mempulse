<!-- Spaced Repetition Core Database Metrics Grid -->
                <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 w-full">

                    <!-- 1. Cards Due Today (Workload) -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-primary/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary flex-shrink-0">
                            <span class="material-symbols-outlined text-[26px]">alarm</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">Cards Due</span>
                            <!-- ADDED TARGET ID HERE -->
                            <span id="stat-cards-due" class="font-headline-md text-xl font-bold tracking-tight text-on-surface mt-0.5">--</span>
                        </div>
                    </div>

                    <!-- 2. Mastered Cards (Retention) -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-teal-500/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 border border-teal-100 flex-shrink-0">
                            <span class="material-symbols-outlined text-[26px]">verified</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">Mastered</span>
                            <!-- ADDED TARGET ID HERE -->
                            <span id="stat-mastered" class="font-headline-md text-xl font-bold tracking-tight text-on-surface mt-0.5">--</span>
                        </div>
                    </div>

                    <!-- 3. Lifetime Accuracy (Performance) -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-indigo-500/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 flex-shrink-0">
                            <span class="material-symbols-outlined text-[26px]">analytics</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">Avg Accuracy</span>
                            <!-- ADDED TARGET ID HERE -->
                            <span id="stat-accuracy" class="font-headline-md text-xl font-bold tracking-tight text-on-surface mt-0.5">--%</span>
                        </div>
                    </div>

                    <!-- 4. Tough Cards / Leeches (Troubleshooting) -->
                    <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-error/50 transition-all">
                        <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-error border border-red-100 flex-shrink-0">
                            <span class="material-symbols-outlined text-[26px]">psychology_alt</span>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">Tough Cards</span>
                            <!-- ADDED TARGET ID HERE -->
                            <span id="stat-tough" class="font-headline-md text-xl font-bold tracking-tight text-on-surface mt-0.5">--</span>
                        </div>
                    </div>

                </section>