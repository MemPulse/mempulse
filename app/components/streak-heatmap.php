<div class="lg:col-span-3 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex flex-col h-[300px]">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">calendar_month</span>
                <h3 class="font-headline-sm text-[16px] font-semibold text-on-surface">Streak Heatmap</h3>
            </div>
            <p class="text-[11px] text-on-surface-variant mt-0.5 ml-7">Last 365 days • <span class="font-semibold text-on-surface"><?php echo $study_days; ?> / 365</span> study days</p>
        </div>
        <div class="flex items-center gap-1.5 font-label-sm text-[11px] text-on-surface-variant">
            <span>Less</span>
            <span class="w-3 h-3 rounded bg-surface-container-high"></span>
            <span class="w-3 h-3 rounded bg-primary-fixed"></span>
            <span class="w-3 h-3 rounded bg-primary-fixed-dim"></span>
            <span class="w-3 h-3 rounded bg-primary-container"></span>
            <span class="w-3 h-3 rounded bg-primary"></span>
            <span>More</span>
        </div>
    </div>

    <div id="heatmap-scroll" class="overflow-x-auto pb-1 flex-1 flex flex-col justify-center">
        <div class="min-w-max">
            <!-- Month labels align with the weekly columns below. -->
            <div class="flex gap-1 pl-10 mb-1.5 h-4 text-[11px] font-medium text-on-surface-variant/80">
                <?php
                $label_week_start = clone $heatmap_calendar_start;
                for ($week_index = 0; $week_index < $heatmap_week_count; $week_index++) {
                    $label = '';
                    $week_probe = clone $label_week_start;
                    for ($day_offset = 0; $day_offset < 7; $day_offset++) {
                        $probe_date = clone $week_probe;
                        $probe_date->modify('+' . $day_offset . ' days');
                        if ($probe_date < $heatmap_start || $probe_date > $heatmap_end) {
                            continue;
                        }
                        if ($probe_date->format('j') === '1') {
                            $label = $probe_date->format('M');
                            break;
                        }
                    }
                    echo '<div class="w-5 flex-none overflow-visible whitespace-nowrap">' . htmlspecialchars($label) . '</div>';
                    $label_week_start->modify('+7 days');
                }
                ?>
            </div>

            <div class="flex gap-2 items-start">
                <div class="flex flex-col justify-between text-[11px] font-medium text-on-surface-variant/70 pr-2 select-none py-0.5 h-[168px] w-6 flex-none">
                    <span>Mon</span>
                    <span>Wed</span>
                    <span>Fri</span>
                    <span>Sun</span>
                </div>

                <div class="flex gap-1">
                    <?php
                    $week_start = clone $heatmap_calendar_start;
                    for ($week_index = 0; $week_index < $heatmap_week_count; $week_index++) {
                        echo '<div class="flex flex-col gap-1">';
                        for ($day_offset = 0; $day_offset < 7; $day_offset++) {
                            $cell_date = clone $week_start;
                            $cell_date->modify('+' . $day_offset . ' days');
                            $cell_key = $cell_date->format('Y-m-d');
                            $in_range = $cell_date >= $heatmap_start && $cell_date <= $heatmap_end;
                            $reviews = $in_range ? ($heatmap_data[$cell_key] ?? 0) : 0;

                            if (!$in_range) {
                            echo '<div class="w-5 h-5 flex-none rounded bg-transparent"></div>';
                            continue;
    }

                            $cell_class = $heatmap_level_class($reviews);
                            $date_label = $cell_date->format('M j, Y');
                            $review_label = $reviews === 1 ? '1 review' : $reviews . ' reviews';
                            $title = $reviews > 0 ? $date_label . ' — ' . $review_label : $date_label . ' — No reviews';

                            $tooltip = $reviews > 0
                                ? $date_label . ' • ' . $review_label
                                : $date_label . ' • No reviews';

                            echo '<div class="heatmap-cell w-5 h-5 flex-none rounded ' . $cell_class . '"'
                                . ' data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"'
                                . ' aria-label="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"></div>';                                            }
                        echo '</div>';
                        $week_start->modify('+7 days');
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>