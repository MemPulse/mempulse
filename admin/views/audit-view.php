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
        <section aria-label="Audit summary metrics" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Total Events</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($summary['total']) ?></p>
                    <span class="text-[11px] text-on-surface-variant"><?= $fmt($summary['last24h']) ?> in last 24h</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">policy</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700">Approvals &amp; Restores</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($summary['approvals']) ?></p>
                    <span class="text-[11px] text-on-surface-variant">Accounts activated</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-200/80 text-teal-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">how_to_reg</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Suspensions &amp; Archives</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($summary['moderation']) ?></p>
                    <span class="text-[11px] text-on-surface-variant">Access revoked</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">gpp_maybe</span>
                </div>
            </div>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Role Changes</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($summary['roles']) ?></p>
                    <span class="text-[11px] text-on-surface-variant"><?= $fmt($summary['last7d']) ?> total actions (7d)</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200/80 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">admin_panel_settings</span>
                </div>
            </div>
        </section>

        <!-- FILTER + TABLE SECTION -->
        <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden min-w-0" aria-label="Audit log entries">

            <!-- Filter Bar -->
            <form method="get" action="audit.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 p-4 border-b border-outline-variant/30">
                <input type="hidden" name="sort" value="<?= adminEsc($f['sort']) ?>">
                <input type="hidden" name="dir" value="<?= adminEsc($f['dir']) ?>">

                <div class="lg:col-span-3">
                    <label for="flt-q" class="sr-only">Search target, admin, or IP</label>
                    <input id="flt-q" type="search" name="q" value="<?= adminEsc($f['q']) ?>" maxlength="100" placeholder="Search user, email, or IP…" class="<?= $input ?>">
                </div>

                <div class="lg:col-span-3">
                    <label for="flt-action" class="sr-only">Action Type</label>
                    <select id="flt-action" name="action" class="<?= $input ?>" onchange="this.form.submit()">
                        <option value="">All action types</option>
                        <?php foreach (AdminAuditService::ACTIONS as $actKey => $actLabel): ?>
                            <option value="<?= adminEsc($actKey) ?>" <?= $f['action'] === $actKey ? 'selected' : '' ?>><?= adminEsc($actLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lg:col-span-2">
                    <label for="flt-admin" class="sr-only">Performed by Admin</label>
                    <select id="flt-admin" name="admin_id" class="<?= $input ?>" onchange="this.form.submit()">
                        <option value="">All admins</option>
                        <?php foreach ($actors as $actor): ?>
                            <option value="<?= (int) $actor['id'] ?>" <?= $f['admin_id'] === (int) $actor['id'] ? 'selected' : '' ?>><?= adminEsc($actor['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <input type="date" name="from" value="<?= adminEsc($f['from']) ?>" title="From date" class="<?= $input ?>">
                    <input type="date" name="to" value="<?= adminEsc($f['to']) ?>" title="To date" class="<?= $input ?>">
                </div>

                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:opacity-90 transition-opacity">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="audit.php" class="px-3 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface text-sm font-semibold transition-colors">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <?php if ($list['rows']): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/30 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <?php
                                $headers = [
                                    'when'   => ['label' => 'Timestamp',   'class' => 'py-3 pl-4 pr-3'],
                                    'action' => ['label' => 'Action',      'class' => 'py-3 px-3'],
                                    'target' => ['label' => 'Target User', 'class' => 'py-3 px-3'],
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
                                <th class="py-3 px-3 hidden md:table-cell">State Change</th>
                                <th class="py-3 px-3">
                                    <a href="<?= adminEsc($sortUrls['admin']) ?>" class="inline-flex items-center gap-1 hover:text-on-surface transition-colors">
                                        <span>Performed By</span>
                                        <span class="material-symbols-outlined text-[14px] <?= $f['sort'] === 'admin' ? 'text-primary' : 'opacity-40' ?>">
                                            <?= $f['sort'] === 'admin' ? ($f['dir'] === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more' ?>
                                        </span>
                                    </a>
                                </th>
                                <th class="py-3 pl-3 pr-4 text-right hidden lg:table-cell">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20 text-sm">
                            <?php foreach ($list['rows'] as $r):
                                $badgeCls = $actionBadges[$r['action']] ?? 'bg-surface-container-low text-on-surface-variant border-outline-variant/40';
                                $iconName = $actionIcons[$r['action']] ?? 'history';
                            ?>
                                <tr class="hover:bg-surface-container-low/50 transition-colors">
                                    <td class="py-3 pl-4 pr-3 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-semibold text-on-surface tabular-nums"><?= adminEsc($r['timestamp']) ?></span>
                                            <span class="text-[11px] text-on-surface-variant"><?= adminEsc($r['age']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-lg border <?= $badgeCls ?>">
                                            <span class="material-symbols-outlined text-[14px]" aria-hidden="true"><?= $iconName ?></span>
                                            <?= adminEsc($r['actionLabel']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex flex-col min-w-0">
                                            <a href="users.php?q=<?= urlencode($r['targetEmail']) ?>" class="text-xs font-bold text-on-surface hover:text-primary hover:underline truncate">
                                                <?= adminEsc($r['targetName']) ?>
                                            </a>
                                            <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($r['targetEmail']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 hidden md:table-cell whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 text-xs font-mono text-on-surface-variant bg-surface-container-low px-2.5 py-1 rounded-lg border border-outline-variant/20">
                                            <span><?= adminEsc($r['prevStatus']) ?></span>
                                            <span class="material-symbols-outlined text-[13px] text-outline">arrow_forward</span>
                                            <span class="font-bold text-on-surface"><?= adminEsc($r['newStatus']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-xs font-semibold text-on-surface truncate"><?= adminEsc($r['adminName']) ?></span>
                                            <?php if ($r['adminEmail'] !== ''): ?>
                                                <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($r['adminEmail']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-3 pl-3 pr-4 text-right hidden lg:table-cell whitespace-nowrap">
                                        <span class="text-xs font-mono text-on-surface-variant"><?= adminEsc($r['ip']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-outline-variant/30" aria-label="Pagination">
                    <p class="text-xs text-on-surface-variant">
                        Showing <?= $fmt($list['first']) ?>&ndash;<?= $fmt($list['last']) ?> of <?= $fmt($list['total']) ?> events
                    </p>
                    <?php if ($list['pages'] > 1): ?>
                        <div class="flex items-center gap-1">
                            <?php if ($list['page'] > 1): ?>
                                <a href="audit.php<?= adminEsc(AdminAuditService::buildQuery($f, ['page' => $list['page'] - 1])) ?>" aria-label="Previous page"
                                   class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                                </a>
                            <?php endif; ?>
                            <?php for ($p = $firstShown; $p <= $lastShown; $p++): ?>
                                <?php if ($p === $list['page']): ?>
                                    <span aria-current="page" class="<?= $pageBtn ?>bg-primary text-on-primary"><?= $p ?></span>
                                <?php else: ?>
                                    <a href="audit.php<?= adminEsc(AdminAuditService::buildQuery($f, ['page' => $p])) ?>" class="<?= $pageBtn ?>text-on-surface-variant hover:bg-surface-container-high hover:text-primary"><?= $p ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($list['page'] < $list['pages']): ?>
                                <a href="audit.php<?= adminEsc(AdminAuditService::buildQuery($f, ['page' => $list['page'] + 1])) ?>" aria-label="Next page"
                                   class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                                </a>
                            <?php endif; ?>
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

    </div>
</div>