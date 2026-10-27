<?php
// Expects (from audit.php): $filters, $summary, $actors, $list.
$f   = $filters;
$fmt = fn($n) => number_format((int) $n);
$hasFilters = $f['q'] !== '' || $f['action'] !== '' || $f['admin_id'] > 0 || $f['from'] !== '' || $f['to'] !== '';

$actionBadges = [
    'user_approved'         => 'bg-teal-50 text-teal-700 border-teal-200/80',
    'user_restored'         => 'bg-teal-50 text-teal-700 border-teal-200/80',
    'user_created_by_admin' => 'bg-primary/10 text-primary border-primary/20',
    'role_promoted_admin'   => 'bg-indigo-50 text-indigo-700 border-indigo-200/80',
    'role_demoted_user'     => 'bg-amber-50 text-amber-700 border-amber-200/80',
    'user_rejected'         => 'bg-amber-50 text-amber-700 border-amber-200/80',
    'user_deleted'          => 'bg-error-container/40 text-error border-error/20',
];

$actionIcons = [
    'user_approved'         => 'check_circle',
    'user_restored'         => 'restore_from_trash',
    'user_created_by_admin' => 'person_add',
    'role_promoted_admin'   => 'shield_person',
    'role_demoted_user'     => 'person_remove',
    'user_rejected'         => 'block',
    'user_deleted'          => 'delete',
];

$input = 'w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/40';

$sortUrls = [];
foreach (['when', 'action', 'target', 'admin'] as $key) {
    $nextDir = ($f['sort'] === $key && $f['dir'] === 'asc') ? 'desc' : 'asc';
    $sortUrls[$key] = 'audit.php' . AdminAuditService::buildQuery($f, ['sort' => $key, 'dir' => $nextDir, 'page' => 1]);
}

$exportUrl  = 'audit.php' . AdminAuditService::buildQuery($f, ['export' => 'csv']);
$pageBtn    = 'min-w-8 h-8 px-2 rounded-lg flex items-center justify-center text-xs font-semibold tabular-nums transition-colors ';
$firstShown = max(1, $list['page'] - 2);
$lastShown  = min($list['pages'], $list['page'] + 2);
?>

<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <!-- PAGE HEADER -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="material-symbols-outlined text-[28px] text-primary" aria-hidden="true">history</span>
                    <h1 class="font-headline-md text-xl sm:text-2xl font-bold text-on-surface m-0">Admin Audit Log</h1>
                </div>
                <p class="text-xs sm:text-sm text-on-surface-variant">
                    Append-only security record of every user approval, suspension, archive, and role change across MemPulse.
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="<?= adminEsc($exportUrl) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/30 text-on-surface text-xs font-semibold transition-colors">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">download</span>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <!-- 4 KPI SUMMARY CARDS -->
        <section aria-label="Audit summary metrics" class="grid grid-cols-4 gap-2 sm:gap-3 lg:gap-4" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr))">

            <!-- Total Events (primary) -->
            <div class="min-w-0 bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-primary/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-primary">Total Events</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($summary['total']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant"><?= $fmt($summary['last24h']) ?> in last 24h</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-primary/10 group-hover:bg-primary/20 text-primary flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">policy</span>
                </div>
            </div>

            <!-- Approvals & Restores (rose) -->
            <div class="min-w-0 bg-gradient-to-br from-rose-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-rose-500/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-rose-700">Approvals &amp; Restores</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($summary['approvals']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant">Accounts activated</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-rose-500/10 group-hover:bg-rose-500/20 text-rose-700 flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">how_to_reg</span>
                </div>
            </div>

            <!-- Suspensions & Archives (teal) -->
            <div class="min-w-0 bg-gradient-to-br from-teal-500/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-teal-500/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-teal-700">Suspensions &amp; Archives</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($summary['moderation']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant">Access revoked</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-teal-500/10 group-hover:bg-teal-500/20 text-teal-600 flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">gpp_maybe</span>
                </div>
            </div>

            <!-- Role Changes (primary) -->
            <div class="min-w-0 bg-gradient-to-br from-primary/10 via-surface-container-lowest to-surface-container-lowest border border-outline-variant/30 rounded-2xl p-3 shadow-xs flex items-center justify-between gap-2 hover:border-primary/50 transition-all group">
                <div class="min-w-0">
                    <span class="block truncate text-[10px] sm:text-[11px] font-bold uppercase tracking-wide text-primary">Role Changes</span>
                    <p class="font-headline-md text-xl sm:text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($summary['roles']) ?></p>
                    <span class="block truncate text-[10px] sm:text-[11px] text-on-surface-variant"><?= $fmt($summary['last7d']) ?> total actions (7d)</span>
                </div>
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-primary/10 group-hover:bg-primary/20 text-primary flex items-center justify-center shrink-0 transition-colors">
                    <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                </div>
            </div>

        </section>

        <!-- FILTER + TABLE SECTION -->
        <?php
        // Pagination helper: first / prev / 1 … window … last / next / last
        $curPage   = (int) $list['page'];
        $lastPage  = (int) $list['pages'];
        $pgUrl     = fn($p) => 'audit.php' . AdminAuditService::buildQuery($f, ['page' => $p]);
        $pageItems = [];
        $prevShown = 0;
        for ($p = 1; $p <= $lastPage; $p++) {
            if ($p === 1 || $p === $lastPage || abs($p - $curPage) <= 2) {
                if ($prevShown && $p - $prevShown > 1) $pageItems[] = '…';
                $pageItems[] = $p;
                $prevShown = $p;
            }
        }
        $navBtn      = 'w-8 h-8 rounded-lg flex items-center justify-center transition-colors ';
        $navActive   = 'text-on-surface-variant hover:bg-surface-container-high hover:text-primary';
        $navDisabled = 'text-on-surface-variant/30 pointer-events-none';
        ?>
        <style>
            /* ---------- Filter bar ---------- */
            #audit-log .aud-filter { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px 16px; padding: 16px; }
            #audit-log .aud-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
            #audit-log .aud-field.grow   { flex: 1 1 240px; }
            #audit-log .aud-field.select { flex: 0 1 220px; min-width: 180px; }
            #audit-log .aud-field.date   { flex: 0 0 180px; }
            #audit-log .aud-actions { display: flex; align-items: center; gap: 8px; flex: none; }

            /* ---------- Compact, fixed-width table ---------- */
            #audit-log .aud-table { table-layout: fixed; width: 100%; min-width: 760px; border-collapse: collapse; }
            #audit-log .aud-table th,
            #audit-log .aud-table td {
                padding: 6px 12px;
                vertical-align: middle;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            #audit-log .aud-table th { padding-top: 8px; padding-bottom: 8px; }
            #audit-log .aud-table .c-when,
            #audit-log .aud-table th.c-when { padding-left: 16px; }
            #audit-log .aud-table .c-ip { padding-right: 16px; text-align: right; }

            /* Column widths (Target User and Performed By share the remaining space) */
            #audit-log col.c-when   { width: 230px; }
            #audit-log col.c-action { width: 170px; }
            #audit-log col.c-state  { width: 190px; }
            #audit-log col.c-ip     { width: 130px; }

            /* Responsive column hiding (<col>, <th> and <td> together so the layout stays in sync) */
            #audit-log .aud-table .col-md,
            #audit-log .aud-table .col-lg { display: none; }
            @media (min-width: 768px) {
                #audit-log .aud-table th.col-md,
                #audit-log .aud-table td.col-md { display: table-cell; }
                #audit-log .aud-table col.col-md { display: table-column; }
            }
            @media (min-width: 1024px) {
                #audit-log .aud-table th.col-lg,
                #audit-log .aud-table td.col-lg { display: table-cell; }
                #audit-log .aud-table col.col-lg { display: table-column; }
            }

            /* Single-line person cell: name + email */
            #audit-log .person-cell { display: flex; align-items: baseline; gap: 8px; min-width: 0; }
            #audit-log .person-name { flex: 0 1 auto; max-width: 60%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            #audit-log .person-email { flex: 1 1 0; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

            /* Loading state while the table refreshes */
            #audit-log .aud-table-wrap { transition: opacity .15s; }
            #audit-log.is-loading .aud-table-wrap { opacity: .5; pointer-events: none; }
        </style>

        <section id="audit-log" class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden min-w-0" aria-label="Audit log entries">

            <!-- Filter Bar -->
            <form data-ajax method="get" action="audit.php" class="aud-filter border-b border-outline-variant/30">
                <input type="hidden" name="sort" value="<?= adminEsc($f['sort']) ?>">
                <input type="hidden" name="dir" value="<?= adminEsc($f['dir']) ?>">

                <div class="aud-field grow">
                    <label for="flt-q" class="text-xs font-bold text-on-surface-variant">Search</label>
                    <input id="flt-q" type="search" name="q" value="<?= adminEsc($f['q']) ?>" maxlength="100" placeholder="User, email, or IP…" class="<?= $input ?> min-w-0">
                </div>

                <div class="aud-field select">
                    <label for="flt-action" class="text-xs font-bold text-on-surface-variant">Action Type</label>
                    <select id="flt-action" name="action" class="<?= $input ?> min-w-0" data-autosubmit>
                        <option value="">All action types</option>
                        <?php foreach (AdminAuditService::ACTIONS as $actKey => $actLabel): ?>
                            <option value="<?= adminEsc($actKey) ?>" <?= $f['action'] === $actKey ? 'selected' : '' ?>><?= adminEsc($actLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="aud-field select">
                    <label for="flt-admin" class="text-xs font-bold text-on-surface-variant">Performed By</label>
                    <select id="flt-admin" name="admin_id" class="<?= $input ?> min-w-0" data-autosubmit>
                        <option value="">All admins</option>
                        <?php foreach ($actors as $actor): ?>
                            <option value="<?= (int) $actor['id'] ?>" <?= $f['admin_id'] === (int) $actor['id'] ? 'selected' : '' ?>><?= adminEsc($actor['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="aud-field date">
                    <label for="flt-from" class="text-xs font-bold text-on-surface-variant">From</label>
                    <input id="flt-from" type="date" name="from" value="<?= adminEsc($f['from']) ?>" class="<?= $input ?> min-w-0">
                </div>

                <div class="aud-field date">
                    <label for="flt-to" class="text-xs font-bold text-on-surface-variant">To</label>
                    <input id="flt-to" type="date" name="to" value="<?= adminEsc($f['to']) ?>" class="<?= $input ?> min-w-0">
                </div>

                <div class="aud-actions">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:opacity-90 transition-opacity h-[38px]">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="audit.php" class="px-4 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface text-sm font-semibold transition-colors flex items-center justify-center h-[38px]">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($list['rows']): ?>
                <div class="overflow-x-auto aud-table-wrap">
                    <table class="aud-table text-left">
                        <colgroup>
                            <col class="c-when">
                            <col class="c-action">
                            <col>
                            <col class="c-state col-md">
                            <col>
                            <col class="c-ip col-lg">
                        </colgroup>
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/30 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <?php
                                $headers = [
                                    'when'   => ['label' => 'Timestamp',    'class' => 'c-when'],
                                    'action' => ['label' => 'Action',       'class' => 'c-action'],
                                    'target' => ['label' => 'Target User',  'class' => ''],
                                ];
                                foreach ($headers as $key => $h):
                                    $active = $f['sort'] === $key;
                                    $icon   = $active ? ($f['dir'] === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more';
                                ?>
                                    <th class="<?= $h['class'] ?>">
                                        <a href="<?= adminEsc($sortUrls[$key]) ?>" class="inline-flex items-center gap-1 hover:text-on-surface transition-colors">
                                            <span><?= adminEsc($h['label']) ?></span>
                                            <span class="material-symbols-outlined text-[14px] <?= $active ? 'text-primary' : 'opacity-40' ?>"><?= $icon ?></span>
                                        </a>
                                    </th>
                                <?php endforeach; ?>
                                <th class="c-state col-md">State Change</th>
                                <th>
                                    <a href="<?= adminEsc($sortUrls['admin']) ?>" class="inline-flex items-center gap-1 hover:text-on-surface transition-colors">
                                        <span>Performed By</span>
                                        <span class="material-symbols-outlined text-[14px] <?= $f['sort'] === 'admin' ? 'text-primary' : 'opacity-40' ?>">
                                            <?= $f['sort'] === 'admin' ? ($f['dir'] === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' ?>
                                        </span>
                                    </a>
                                </th>
                                <th class="c-ip col-lg">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20 text-sm">
                            <?php foreach ($list['rows'] as $r):
                                $badgeCls = $actionBadges[$r['action']] ?? 'bg-surface-container-low text-on-surface-variant border-outline-variant/40';
                                $iconName = $actionIcons[$r['action']] ?? 'history';
                            ?>
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="c-when tabular-nums">
                                        <span class="text-xs font-semibold text-on-surface"><?= adminEsc($r['timestamp']) ?></span>
                                        <span class="text-[11px] text-on-surface-variant ml-1.5"><?= adminEsc($r['age']) ?></span>
                                    </td>
                                    <td class="c-action">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2 py-0.5 rounded-md border <?= $badgeCls ?>">
                                            <span class="material-symbols-outlined text-[13px]" aria-hidden="true"><?= $iconName ?></span>
                                            <?= adminEsc($r['actionLabel']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="person-cell">
                                            <a href="users.php?q=<?= urlencode($r['targetEmail']) ?>"
                                            class="person-name text-xs font-bold text-on-surface hover:text-primary hover:underline"
                                            title="<?= adminEsc($r['targetName']) ?>"><?= adminEsc($r['targetName']) ?></a>
                                            <span class="person-email text-[11px] text-on-surface-variant"
                                                title="<?= adminEsc($r['targetEmail']) ?>"><?= adminEsc($r['targetEmail']) ?></span>
                                        </div>
                                    </td>
                                    <td class="c-state col-md">
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-mono text-on-surface-variant bg-surface-container-low px-2 py-0.5 rounded-md border border-outline-variant/20">
                                            <span><?= adminEsc($r['prevStatus']) ?></span>
                                            <span class="material-symbols-outlined text-[12px] text-outline">arrow_forward</span>
                                            <span class="font-bold text-on-surface"><?= adminEsc($r['newStatus']) ?></span>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="person-cell">
                                            <span class="person-name text-xs font-semibold text-on-surface"
                                                title="<?= adminEsc($r['adminName']) ?>"><?= adminEsc($r['adminName']) ?></span>
                                            <?php if ($r['adminEmail'] !== ''): ?>
                                                <span class="person-email text-[11px] text-on-surface-variant"
                                                    title="<?= adminEsc($r['adminEmail']) ?>"><?= adminEsc($r['adminEmail']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="c-ip col-lg">
                                        <span class="text-xs font-mono text-on-surface-variant"><?= adminEsc($r['ip']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="flex flex-wrap items-center justify-between gap-3 px-5 py-2.5 border-t border-outline-variant/30" aria-label="Pagination">
                    <p class="text-xs text-on-surface-variant">
                        Showing <?= $fmt($list['first']) ?>&ndash;<?= $fmt($list['last']) ?> of <?= $fmt($list['total']) ?> events
                    </p>

                    <?php if ($lastPage > 1): ?>
                        <div class="flex flex-wrap items-center gap-3">

                            <!-- Page buttons -->
                            <div class="flex items-center gap-1">
                                <!-- First / Previous -->
                                <?php if ($curPage > 1): ?>
                                    <a href="<?= adminEsc($pgUrl(1)) ?>" aria-label="First page" title="First page" class="<?= $navBtn . $navActive ?>">
                                        <span class="material-symbols-outlined text-[20px]">first_page</span>
                                    </a>
                                    <a href="<?= adminEsc($pgUrl($curPage - 1)) ?>" aria-label="Previous page" title="Previous page" class="<?= $navBtn . $navActive ?>">
                                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                                    </a>
                                <?php else: ?>
                                    <span aria-hidden="true" class="<?= $navBtn . $navDisabled ?>"><span class="material-symbols-outlined text-[20px]">first_page</span></span>
                                    <span aria-hidden="true" class="<?= $navBtn . $navDisabled ?>"><span class="material-symbols-outlined text-[20px]">chevron_left</span></span>
                                <?php endif; ?>

                                <!-- Page numbers with ellipsis (always shows first and last page) -->
                                <?php foreach ($pageItems as $item): ?>
                                    <?php if ($item === '…'): ?>
                                        <span class="min-w-6 h-8 flex items-center justify-center text-xs text-on-surface-variant">…</span>
                                    <?php elseif ($item === $curPage): ?>
                                        <span aria-current="page" class="<?= $pageBtn ?>bg-primary text-on-primary"><?= $item ?></span>
                                    <?php else: ?>
                                        <a href="<?= adminEsc($pgUrl($item)) ?>" class="<?= $pageBtn ?>text-on-surface-variant hover:bg-surface-container-high hover:text-primary"><?= $item ?></a>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <!-- Next / Last -->
                                <?php if ($curPage < $lastPage): ?>
                                    <a href="<?= adminEsc($pgUrl($curPage + 1)) ?>" aria-label="Next page" title="Next page" class="<?= $navBtn . $navActive ?>">
                                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                    </a>
                                    <a href="<?= adminEsc($pgUrl($lastPage)) ?>" aria-label="Last page" title="Last page" class="<?= $navBtn . $navActive ?>">
                                        <span class="material-symbols-outlined text-[20px]">last_page</span>
                                    </a>
                                <?php else: ?>
                                    <span aria-hidden="true" class="<?= $navBtn . $navDisabled ?>"><span class="material-symbols-outlined text-[20px]">chevron_right</span></span>
                                    <span aria-hidden="true" class="<?= $navBtn . $navDisabled ?>"><span class="material-symbols-outlined text-[20px]">last_page</span></span>
                                <?php endif; ?>
                            </div>

                            <!-- Jump to page -->
                            <form data-ajax method="get" action="audit.php" class="flex items-center gap-2">
                                <input type="hidden" name="sort"     value="<?= adminEsc($f['sort']) ?>">
                                <input type="hidden" name="dir"      value="<?= adminEsc($f['dir']) ?>">
                                <input type="hidden" name="q"        value="<?= adminEsc($f['q']) ?>">
                                <input type="hidden" name="action"   value="<?= adminEsc($f['action']) ?>">
                                <input type="hidden" name="admin_id" value="<?= adminEsc((string) $f['admin_id']) ?>">
                                <input type="hidden" name="from"     value="<?= adminEsc($f['from']) ?>">
                                <input type="hidden" name="to"       value="<?= adminEsc($f['to']) ?>">

                                <label for="jump-page" class="text-xs text-on-surface-variant whitespace-nowrap">Go to</label>
                                <input id="jump-page" type="number" name="page" min="1" max="<?= $lastPage ?>" value="<?= $curPage ?>" inputmode="numeric" required
                                    class="<?= $input ?> tabular-nums text-center" style="width:72px;height:32px;padding:0 8px;">
                                <span class="text-xs text-on-surface-variant whitespace-nowrap">of <?= $fmt($lastPage) ?></span>
                                <button type="submit" class="px-3 h-8 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-on-surface text-xs font-semibold transition-colors">Go</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </nav>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center p-12 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[36px] text-outline mb-2">history</span>
                    <span class="text-sm font-semibold text-on-surface"><?= $hasFilters ? 'No audit events match these filters.' : 'No admin actions have been logged yet.' ?></span>
                    <span class="text-xs text-on-surface-variant mt-1">Approvals, suspensions, archives, and role changes will appear here automatically.</span>
                    <?php if ($hasFilters): ?>
                        <a href="audit.php" class="mt-3 px-3.5 py-1.5 rounded-xl bg-primary/10 text-primary text-xs font-bold hover:underline">Reset filters</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>

        <script>
        (function () {
            var region = document.getElementById('audit-log');
            if (!region) return;

            var controller = null;
            history.replaceState({ auditAjax: true }, '', location.href);

            function load(url, push) {
                if (controller) controller.abort();
                var ctrl = controller = new AbortController();
                region.classList.add('is-loading');

                fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'fetch' },
                    signal: ctrl.signal
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.text();
                    })
                    .then(function (html) {
                        var doc   = new DOMParser().parseFromString(html, 'text/html');
                        var fresh = doc.getElementById('audit-log');
                        if (!fresh) throw new Error('Section not found');

                        region.innerHTML = fresh.innerHTML;
                        if (push) history.pushState({ auditAjax: true }, '', url);
                    })
                    .catch(function (err) {
                        if (err.name !== 'AbortError') window.location.href = url; // fall back to a normal page load
                    })
                    .finally(function () {
                        if (controller === ctrl) {
                            controller = null;
                            region.classList.remove('is-loading');
                        }
                    });
            }

            // Sort headers, pagination, Reset
            region.addEventListener('click', function (e) {
                var a = e.target.closest('a[href]');
                if (!a || !region.contains(a)) return;
                if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

                var url = new URL(a.href, location.href);
                if (url.origin !== location.origin || !/audit\.php$/.test(url.pathname)) return; // skip users.php links

                e.preventDefault();
                load(url.href, true);
            });

            // Filter form
            region.addEventListener('submit', function (e) {
                var form = e.target;
                if (!form.hasAttribute('data-ajax')) return;
                e.preventDefault();

                var url = new URL(form.getAttribute('action'), location.href);
                url.search = new URLSearchParams(new FormData(form)).toString();
                load(url.href, true);
            });

            // Action / admin dropdowns apply immediately, without a full reload
            region.addEventListener('change', function (e) {
                var el = e.target;
                if (!el.matches || !el.matches('[data-autosubmit]') || !el.form) return;
                if (el.form.requestSubmit) el.form.requestSubmit(); else el.form.submit();
            });

            // Back / forward buttons
            window.addEventListener('popstate', function (e) {
                if (e.state && e.state.auditAjax) load(location.href, false);
            });
        })();
        </script>

    </div>
</div>