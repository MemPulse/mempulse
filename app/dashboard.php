<?php
// Fetch Recent Review Sessions dynamically based on the current user
$recent_sessions = [];
$recent_sql = "SELECT rs.id, d.title as deck_title, rs.total_reviewed as cards_reviewed, rs.accuracy_percentage as accuracy, rs.started_at 
               FROM review_sessions rs 
               JOIN decks d ON rs.deck_id = d.id 
               WHERE rs.user_id = ? AND rs.ended_at IS NOT NULL 
               ORDER BY rs.started_at DESC LIMIT 4";

$recent_stmt = mysqli_prepare($conn, $recent_sql);
if ($recent_stmt) {
    mysqli_stmt_bind_param($recent_stmt, "i", $user_id);
    mysqli_stmt_execute($recent_stmt);
    $rs_result = mysqli_stmt_get_result($recent_stmt);
    
    while ($r = mysqli_fetch_assoc($rs_result)) {
        // Simple relative time formatter
        $time_diff = time() - strtotime($r['started_at']);
        if ($time_diff < 3600) {
            $formatted = floor($time_diff / 60) . ' mins ago';
        } elseif ($time_diff < 86400) {
            $formatted = floor($time_diff / 3600) . ' hours ago';
        } else {
            $formatted = floor($time_diff / 86400) . ' days ago';
        }
        
        $recent_sessions[] = [
            'deck_title' => $r['deck_title'],
            'cards_reviewed' => $r['cards_reviewed'],
            'accuracy' => $r['accuracy'],
            'formatted_time' => $formatted
        ];
    }
    mysqli_stmt_close($recent_stmt);
}
?>

<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <!-- TOP ROW: Core Metrics & Carousel -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-start w-full">
            
            <!-- LEFT COLUMN -->
            <div class="lg:col-span-6 flex flex-col justify-between gap-4 w-full min-w-0">
                
                <!-- 2x2 Core Metrics Grid (Changes to 1 column on extra-small mobile screens) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 w-full">
                    
                    <!-- 1. Cards Due Today (SCHEDULED) -->
                    <div class="bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-primary/50 hover:shadow-md transition-all">
                        <div class="absolute right-0 bottom-0 w-20 h-20 bg-primary/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-primary/20 transition-all"></div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <div class="flex items-center gap-1.5">
                                <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-primary">Scheduled</span>
                                <div class="relative group/tooltip flex items-center">
                                    <span class="material-symbols-outlined text-[15px] text-primary/60 cursor-help hover:text-primary transition-colors">info</span>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                        Total cards scheduled for review today.
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-8 h-8 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary flex-shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[18px]">alarm</span>
                            </div>
                        </div>
                        <div class="flex flex-col mt-2 relative z-10">
                            <span id="stat-cards-due" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">--</span>
                            <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Cards Due Today</span>
                        </div>
                    </div>

                    <!-- 2. Mastered Cards (PERMANENT) -->
                    <div class="bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-teal-500/50 hover:shadow-md transition-all">
                        <div class="absolute right-0 bottom-0 w-20 h-20 bg-teal-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-teal-500/20 transition-all"></div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <div class="flex items-center gap-1.5">
                                <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-teal-700">Permanent</span>
                                <div class="relative group/tooltip flex items-center">
                                    <span class="material-symbols-outlined text-[15px] text-teal-700/60 cursor-help hover:text-teal-700 transition-colors">info</span>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                        Flashcards successfully moved into long-term retention.
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-8 h-8 rounded-xl bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-600 flex-shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[18px]">verified</span>
                            </div>
                        </div>
                        <div class="flex flex-col mt-2 relative z-10">
                            <span id="stat-mastered" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">--</span>
                            <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Mastered Cards</span>
                        </div>
                    </div>

                    <!-- 3. Tough Cards (ATTENTION) -->
                    <div class="bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-error/50 hover:shadow-md transition-all">
                        <div class="absolute right-0 bottom-0 w-20 h-20 bg-rose-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-rose-500/20 transition-all"></div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <div class="flex items-center gap-1.5">
                                <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-error">Attention</span>
                                <div class="relative group/tooltip flex items-center">
                                    <span class="material-symbols-outlined text-[15px] text-error/60 cursor-help hover:text-error transition-colors">info</span>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                        Cards with frequent review lapses needing focused attention.
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-8 h-8 rounded-xl bg-red-50 border border-red-200/80 flex items-center justify-center text-error flex-shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[18px]">psychology_alt</span>
                            </div>
                        </div>
                        <div class="flex flex-col mt-2 relative z-10">
                            <span id="stat-tough" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">--</span>
                            <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Tough Cards</span>
                        </div>
                    </div>

                    <!-- 4. Average Accuracy (ACCURACY) -->
                    <div class="bg-gradient-to-br from-indigo-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between h-32 sm:h-36 relative group hover:border-indigo-500/50 hover:shadow-md transition-all">
                        <div class="absolute right-0 bottom-0 w-20 h-20 bg-indigo-500/10 rounded-br-2xl rounded-tl-full blur-md pointer-events-none group-hover:bg-indigo-500/20 transition-all"></div>
                        <div class="flex items-center justify-between w-full relative z-10">
                            <div class="flex items-center gap-1.5">
                                <span class="font-label-sm text-[11px] font-bold uppercase tracking-wider text-indigo-700">Accuracy</span>
                                <div class="relative group/tooltip flex items-center">
                                    <span class="material-symbols-outlined text-[15px] text-indigo-700/60 cursor-help hover:text-indigo-700 transition-colors">info</span>
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover/tooltip:block w-52 p-2.5 bg-slate-900 text-white text-[11px] font-medium rounded-xl shadow-xl z-50 pointer-events-none leading-snug text-center">
                                        Overall percentage of correct recalls across all study sessions.
                                        <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-slate-900"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-200/80 flex items-center justify-center text-indigo-600 flex-shrink-0 shadow-xs">
                                <span class="material-symbols-outlined text-[18px]">analytics</span>
                            </div>
                        </div>
                        <div class="flex flex-col mt-2 relative z-10">
                            <span id="stat-accuracy" class="font-headline-lg text-2xl sm:text-3xl font-extrabold tracking-tight text-on-surface">--%</span>
                            <span class="text-xs text-on-surface-variant/80 font-medium mt-0.5">Average Accuracy</span>
                        </div>
                    </div>
                </div>

                <!-- Single Long Streak Card (Stack on mobile, row on tablet/desktop) -->
                <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-2 sm:p-2.5 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-1 sm:gap-3 w-full">
                    <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-primary/5 transition-all group/streak cursor-default">
                        <div class="w-8 h-8 rounded-lg bg-primary-fixed/60 border border-primary-fixed-dim/40 flex items-center justify-center text-primary flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[18px]">emoji_events</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Best Streak</span>
                            <span id="stat-best-streak" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-primary transition-colors">-- days</span>
                        </div>
                    </div>
                    <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>
                    <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-amber-500/5 transition-all group/streak cursor-default">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 border border-amber-200/80 flex items-center justify-center text-amber-500 flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[18px]">local_fire_department</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Current Streak</span>
                            <span id="stat-current-streak" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-amber-600 transition-colors">-- days</span>
                        </div>
                    </div>
                    <div class="hidden sm:block w-[1px] h-8 bg-outline-variant/30"></div>
                    <div class="flex items-center gap-3 w-full sm:flex-1 justify-start p-2 sm:p-2.5 rounded-xl hover:bg-teal-500/5 transition-all group/streak cursor-default">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 border border-teal-200/80 flex items-center justify-center text-teal-500 flex-shrink-0 group-hover/streak:scale-105 transition-transform">
                            <span class="material-symbols-outlined text-[18px]">track_changes</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="font-label-sm text-[10px] font-bold uppercase tracking-wider text-on-surface-variant/70">Streak Acc.</span>
                            <span id="stat-streak-accuracy" class="font-headline-sm text-sm font-bold text-on-surface group-hover/streak:text-teal-600 transition-colors">--%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Carousel -->
            <div class="lg:col-span-6 flex w-full min-w-0">
                <section class="relative w-full rounded-2xl overflow-hidden shadow-xs border border-outline-variant/30 min-h-[260px] bg-gradient-to-br from-inverse-surface via-slate-900 to-inverse-surface text-inverse-on-surface flex flex-col justify-between" id="hero-carousel">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-secondary-container/20 rounded-full blur-3xl pointer-events-none"></div>

                    <div class="relative w-full h-full min-h-[260px]">
                        <!-- Slide 1 -->
                        <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-100 z-10" data-slide-index="0">
                            <div class="relative z-10 flex flex-col gap-2">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-primary/20 border border-primary/30 backdrop-blur-md text-cyan-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                    <span class="material-symbols-outlined text-[14px]">psychology</span>
                                    <span>Memory Science</span>
                                </div>
                                <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                    Beat the Forgetting Curve with Spaced-Repetition
                                </h2>
                                <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                    Review concepts before you forget them to lock knowledge into long-term memory with mathematical precision.
                                </p>
                            </div>
                            <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                <button class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all group">
                                    <span>Learn More</span>
                                    <span class="material-symbols-outlined text-[16px] transition-transform group-hover:translate-x-1">arrow_forward</span>
                                </button>
                                <div class="flex items-center gap-2 hero-dots-container">
                                    <button aria-label="Slide 1" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="0"></button>
                                    <button aria-label="Slide 2" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1"></button>
                                    <button aria-label="Slide 3" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2"></button>
                                </div>
                            </div>
                        </div>

                        <!-- Slide 2 -->
                        <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="1">
                            <div class="relative z-10 flex flex-col gap-2">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-teal-500/20 border border-teal-500/30 backdrop-blur-md text-teal-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                    <span class="material-symbols-outlined text-[14px]">style</span>
                                    <span>Deck Architecture</span>
                                </div>
                                <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                    Organize Your Flashcards with Decks
                                </h2>
                                <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                    Transform complex topics into organized, custom study decks tailored to your workflow.
                                </p>
                            </div>
                            <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                <a href="decks.php?create=1" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    <span>Create Deck</span>
                                </a>
                                <div class="flex items-center gap-2 hero-dots-container">
                                    <button aria-label="Slide 1" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="0"></button>
                                    <button aria-label="Slide 2" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="1"></button>
                                    <button aria-label="Slide 3" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="2"></button>
                                </div>
                            </div>
                        </div>

                        <!-- Slide 3 -->
                        <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-in-out flex flex-col justify-between p-5 sm:p-6 opacity-0 pointer-events-none z-0" data-slide-index="2">
                            <div class="relative z-10 flex flex-col gap-2">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-indigo-500/20 border border-indigo-500/30 backdrop-blur-md text-indigo-200 font-label-sm text-[10px] font-bold tracking-wider uppercase w-fit">
                                    <span class="material-symbols-outlined text-[14px]">edit_note</span>
                                    <span>Flashcard Design</span>
                                </div>
                                <h2 class="font-headline-md text-lg sm:text-2xl font-extrabold tracking-tight text-white leading-snug mt-1">
                                    Build Flashcards with Precision
                                </h2>
                                <p class="font-body-sm text-xs sm:text-sm text-slate-300 leading-relaxed max-w-lg">
                                    Structure complex subjects into bite-sized study cards for maximum retention.
                                </p>
                            </div>
                            <div class="relative z-10 flex items-center justify-between pt-4 border-t border-white/10">
                                <a href="decks.php?add_card=1" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-label-md text-xs font-bold shadow-xs transition-all">
                                    <span class="material-symbols-outlined text-[16px]">add</span>
                                    <span>Add Card</span>
                                </a>
                                <div class="flex items-center gap-2 hero-dots-container">
                                    <button aria-label="Slide 1" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="0"></button>
                                    <button aria-label="Slide 2" class="hero-dot h-2 rounded-full transition-all duration-300 hover:bg-white/70 bg-white/30 w-2" data-dot-index="1"></button>
                                    <button aria-label="Slide 3" class="hero-dot h-2 rounded-full transition-all duration-300 bg-cyan-400 w-6" data-dot-index="2"></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

        </div>

        <!-- BOTTOM ROW: Streak Heatmap & Recent Sessions -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 w-full items-stretch mt-2">
            
            <!-- Streak Heatmap Card Container -->
            <div class="lg:col-span-6 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 sm:p-5 shadow-xs flex flex-col justify-between min-h-[320px] min-w-0 overflow-visible">
                
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary text-[22px]">calendar_month</span>
                            <h3 class="font-headline-sm text-base font-semibold text-on-surface m-0">Streak Heatmap</h3>
                        </div>
                        <p class="text-[11px] text-on-surface-variant mt-1 ml-7">
                            Last 365 days • <span class="font-semibold text-on-surface"><?php echo $study_days ?? 0; ?> / 365</span> study days
                        </p>
                    </div>

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

                <!-- Heatmap Scroll / Container Area -->
                <div id="heatmap-scroll" class="flex-1 flex items-center py-4 w-full overflow-x-auto">
                    <div class="w-max grid justify-start"
                        style="--cell: 16px; gap: 3px;
                            grid-template-columns: auto repeat(53, var(--cell));
                            grid-template-rows: auto repeat(7, var(--cell));">

                        <?php foreach ([3 => 'Mon', 5 => 'Wed', 7 => 'Fri'] as $row => $name): ?>
                            <span class="flex items-center pr-2 text-[10px] leading-none whitespace-nowrap text-on-surface-variant select-none"
                                style="grid-column: 1; grid-row: <?php echo $row; ?>;">
                                <?php echo $name; ?>
                            </span>
                        <?php endforeach; ?>

                        <?php
                        if (isset($heatmap_calendar_start)) {
                            $week_start = clone $heatmap_calendar_start;
                            for ($w = 0; $w < 53; $w++) {
                                for ($d = 0; $d < 7; $d++) {
                                    $date = clone $week_start;
                                    $date->modify('+' . $d . ' days');

                                    if ($date < $heatmap_start || $date > $heatmap_end) continue;

                                    if ($date->format('j') === '1' && $w <= 51) {
                                        echo '<div class="text-[10px] leading-none font-medium whitespace-nowrap text-on-surface-variant" style="grid-row: 1; grid-column: ' . ($w + 2) . ' / span 3;">' . htmlspecialchars($date->format('M')) . '</div>';
                                    }

                                    $key = $date->format('Y-m-d');
                                    $reviews = $heatmap_data[$key] ?? 0;
                                    $tooltip = $date->format('M j, Y') . ' • ' . ($reviews === 0 ? 'No reviews' : ($reviews === 1 ? '1 review' : $reviews . ' reviews'));
                                    echo '<div class="heatmap-cell rounded-[4px] transition-transform hover:scale-110 ' . heatmap_level_class($reviews) . '" style="grid-row: ' . ($d + 2) . '; grid-column: ' . ($w + 2) . ';" data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"></div>';
                                }
                                $week_start->modify('+7 days');
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Recent Review Sessions -->
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

                <div class="flex flex-col gap-2.5 flex-1 justify-center">
                    <?php if (!empty($recent_sessions)): ?>
                        <?php foreach ($recent_sessions as $session): ?>
                            <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low border border-outline-variant/20 hover:border-primary/30 hover:bg-surface-container transition-all">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-[18px]">style</span>
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-xs font-bold text-on-surface truncate">
                                            <?php echo htmlspecialchars($session['deck_title']); ?>
                                        </span>
                                        <div class="flex items-center gap-2 text-[11px] text-on-surface-variant mt-0.5">
                                            <span><?php echo (int)($session['cards_reviewed']); ?> cards reviewed</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end flex-shrink-0 ml-2">
                                    <span class="text-xs font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-md">
                                        <?php echo (int)($session['accuracy']); ?>%
                                    </span>
                                    <span class="text-[10px] text-on-surface-variant/70 mt-1">
                                        <?php echo htmlspecialchars($session['formatted_time']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
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
