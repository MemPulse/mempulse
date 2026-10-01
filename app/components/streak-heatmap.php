<?php
$heatmap_end   = new DateTime('today');
$heatmap_start = (clone $heatmap_end)->modify('-364 days');   // last 365 days

// GitHub weeks start on Sunday, so back up to the Sunday on or before the start
$heatmap_calendar_start = clone $heatmap_start;
$heatmap_calendar_start->modify('-' . $heatmap_start->format('w') . ' days');

$total_days         = $heatmap_calendar_start->diff($heatmap_end)->days + 1;
$heatmap_week_count = (int) ceil($total_days / 7);   // usually 53
?>

<div class="lg:col-span-5 bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-sm flex flex-col self-start" style="height: 240px;">
        <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[20px]">
                    calendar_month
                </span>

                <h3 class="font-headline-sm text-[16px] font-semibold text-on-surface">
                    Streak Heatmap
                </h3>
            </div>

            <p class="text-[11px] text-on-surface-variant mt-0.5 ml-7">
                Last 365 days •
                <span class="font-semibold text-on-surface">
                    <?php echo $study_days; ?> / 365
                </span>
                study days
            </p>
        </div>

        <!-- Legend -->
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


    <!-- GitHub-style heatmap -->
    <div id="heatmap-scroll" class="flex-1 flex items-center overflow-x-auto">

    <!-- ONE grid: column 1 = weekday labels, row 1 = month labels -->
    <div class="w-max mx-auto grid"
         style="--cell: 14px; gap: 3px;
                grid-template-columns: auto repeat(<?php echo $heatmap_week_count; ?>, var(--cell));
                grid-template-rows: auto repeat(7, var(--cell));">

        <!-- Weekday labels: rows 3, 5, 7 are Mon, Wed, Fri (row 2 = Sunday) -->
        <?php foreach ([3 => 'Mon', 5 => 'Wed', 7 => 'Fri'] as $row => $name): ?>
            <span class="flex items-center pr-2 text-[10px] leading-none whitespace-nowrap text-on-surface-variant select-none"
                  style="grid-column: 1; grid-row: <?php echo $row; ?>;">
                <?php echo $name; ?>
            </span>
        <?php endforeach; ?>

        <?php
        $week_start = clone $heatmap_calendar_start;

        for ($w = 0; $w < $heatmap_week_count; $w++) {
            for ($d = 0; $d < 7; $d++) {

                $date = clone $week_start;
                $date->modify('+' . $d . ' days');

                // GitHub-style: days outside the 365 range simply don't exist
                if ($date < $heatmap_start || $date > $heatmap_end) {
                    continue;
                }

                // Month label: spans 3 columns so the text has room,
                // skipped near the end so it can't create extra columns
                if ($date->format('j') === '1' && $w <= $heatmap_week_count - 3) {
                    echo '<div class="text-[10px] leading-none whitespace-nowrap text-on-surface-variant"
                               style="grid-row: 1; grid-column: ' . ($w + 2) . ' / span 3;">'
                        . htmlspecialchars($date->format('M'))
                        . '</div>';
                }

                $key     = $date->format('Y-m-d');
                $reviews = $heatmap_data[$key] ?? 0;

                $tooltip = $date->format('M j, Y') . ' • '
                    . ($reviews === 0 ? 'No reviews' : ($reviews === 1 ? '1 review' : $reviews . ' reviews'));

                echo '<div class="heatmap-cell rounded-[3px] ' . $heatmap_level_class($reviews) . '"
                           style="grid-row: ' . ($d + 2) . '; grid-column: ' . ($w + 2) . ';"
                           data-tooltip="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"
                           aria-label="' . htmlspecialchars($tooltip, ENT_QUOTES) . '"></div>';
            }
            $week_start->modify('+7 days');
        }
        ?>
    </div>
</div>

</div>

<script>
    const s = document.getElementById('heatmap-scroll');
    s.scrollLeft = s.scrollWidth;
</script>