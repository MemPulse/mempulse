<div class="lg:col-span-1 flex flex-col gap-3 h-[300px]">
    <!-- Top Row: Compact Current & Best Streaks -->
    <div class="grid grid-cols-2 gap-3 h-[120px]">
        <!-- Current Streak Card -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-sm flex flex-col justify-between relative overflow-hidden group hover:border-amber-300 transition-colors">
            <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-amber-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <span class="font-label-sm text-[10px] font-semibold uppercase tracking-wider text-amber-600 bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-full">
                    Current
                </span>
                <div class="w-6 h-6 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-500 shadow-xs">
                    <span class="material-symbols-outlined text-[14px]">local_fire_department</span>
                </div>
            </div>
            <div class="flex items-baseline gap-1 mt-auto">
                <!-- ID ADDED: Target with JS -->
                <span id="stat-current-streak" class="font-headline-lg text-2xl font-bold tracking-tight text-on-surface">--</span>
                <span class="font-label-md text-[11px] text-on-surface-variant font-medium">Days</span>
            </div>
        </div>

        <!-- Best Streak Card -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-sm flex flex-col justify-between relative overflow-hidden group hover:border-primary-container/40 transition-colors">
            <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-primary/10 rounded-full blur-xl pointer-events-none"></div>
            <div class="flex items-center justify-between">
                <span class="font-label-sm text-[10px] font-semibold uppercase tracking-wider text-primary bg-primary-fixed/60 border border-primary-fixed-dim/40 px-2 py-0.5 rounded-full">
                    Best
                </span>
                <div class="w-6 h-6 rounded-lg bg-primary-fixed/60 border border-primary-fixed-dim/40 flex items-center justify-center text-primary shadow-xs">
                    <span class="material-symbols-outlined text-[14px]">emoji_events</span>
                </div>
            </div>
            <div class="flex items-baseline gap-1 mt-auto">
                <!-- ID ADDED: Target with JS -->
                <span id="stat-best-streak" class="font-headline-lg text-2xl font-bold tracking-tight text-on-surface">--</span>
                <span class="font-label-md text-[11px] text-on-surface-variant font-medium">Days</span>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Current Streak Accuracy Card -->
    <div class="flex-1 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex flex-col justify-between relative overflow-hidden group hover:border-teal-300 transition-colors">
        <div class="absolute -right-4 -bottom-4 w-20 h-20 bg-teal-500/10 rounded-full blur-xl pointer-events-none"></div>
        <div class="flex items-center justify-between">
            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-teal-600 bg-teal-50 border border-teal-200/60 px-2.5 py-0.5 rounded-full">
                Streak Accuracy
            </span>
            <div class="w-8 h-8 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-500 shadow-xs">
                <span class="material-symbols-outlined text-[18px]">track_changes</span>
            </div>
        </div>

        <!-- Accuracy Details -->
        <div class="my-auto pt-2 flex items-center justify-between gap-4">
            <div>
                <div class="flex items-baseline gap-1">
                    <!-- ID ADDED: Target with JS -->
                    <span id="stat-streak-accuracy" class="font-headline-lg text-3xl font-bold tracking-tight text-on-surface">--</span>
                    <span class="font-label-md text-label-md text-on-surface-variant font-semibold">%</span>
                </div>
                <!-- ID ADDED: Target for helper subtitle text strings if needed -->
                <p id="stat-accuracy-subtitle" class="text-[11px] text-on-surface-variant mt-0.5">Calculated over the current streak</p>
            </div>
        </div>
    </div>
</div>