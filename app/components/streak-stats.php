<div class="lg:col-span-1 flex flex-col gap-3" style="height: 240px;">

    <!-- Current Streak -->
    <div class="flex-1 min-h-0 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl px-4 py-3 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-amber-300 transition-colors">
        <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-amber-500/10 rounded-full blur-xl pointer-events-none"></div>

        <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-500 shadow-xs flex-shrink-0">
            <span class="material-symbols-outlined text-[26px]">local_fire_department</span>
        </div>

        <div class="flex flex-col min-w-0">
            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">
                Current Streak
            </span>
            <span id="stat-current-streak" class="font-headline-md text-xl font-bold tracking-tight text-on-surface leading-tight mt-0.5">
                --
            </span>
        </div>
    </div>

    <!-- Best Streak -->
    <div class="flex-1 min-h-0 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl px-4 py-3 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-primary-container/40 transition-colors">
        <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-primary/10 rounded-full blur-xl pointer-events-none"></div>

        <div class="w-12 h-12 rounded-xl bg-primary-fixed/60 border border-primary-fixed-dim/40 flex items-center justify-center text-primary shadow-xs flex-shrink-0">
            <span class="material-symbols-outlined text-[26px]">emoji_events</span>
        </div>

        <div class="flex flex-col min-w-0">
            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">
                Best Streak
            </span>
            <span id="stat-best-streak" class="font-headline-md text-xl font-bold tracking-tight text-on-surface leading-tight mt-0.5">
                --
            </span>
        </div>
    </div>

    <!-- Streak Accuracy -->
    <div class="flex-1 min-h-0 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl px-4 py-3 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-teal-300 transition-colors">
        <div class="absolute -right-4 -bottom-4 w-12 h-12 bg-teal-500/10 rounded-full blur-xl pointer-events-none"></div>

        <div class="w-12 h-12 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-500 shadow-xs flex-shrink-0">
            <span class="material-symbols-outlined text-[26px]">track_changes</span>
        </div>

        <div class="flex flex-col min-w-0">
            <span class="font-label-sm text-[11px] font-semibold uppercase tracking-wider text-on-surface-variant/70">
                Streak Accuracy
            </span>
            <div class="flex items-baseline gap-1">
                <span id="stat-streak-accuracy" class="font-headline-md text-xl font-bold tracking-tight text-on-surface leading-tight mt-0.5">
                    --
                </span>
                <span class="font-label-md text-label-md text-on-surface-variant font-semibold">%</span>
            </div>
        </div>
    </div>

</div>