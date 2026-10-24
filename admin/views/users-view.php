<?php
// Expects (from users.php): $filters, $counts, $list, $adminUser.
$f   = $filters;
$fmt = fn($n) => number_format((int) $n);
$hasFilters = $f['q'] !== '' || $f['status'] !== '' || $f['role'] !== '' || $f['from'] !== '' || $f['to'] !== '';

$statusBadge = [
    'approved' => 'bg-teal-50 text-teal-700 border-teal-200/80',
    'pending'  => 'bg-amber-50 text-amber-700 border-amber-200/80',
    'rejected' => 'bg-error-container/40 text-error border-error/20',
    'deleted'  => 'bg-surface-container-high text-on-surface-variant border-outline-variant/40',
];
$roleBadge = [
    'admin' => 'bg-primary/10 text-primary border-primary/20',
    'user'  => 'bg-surface-container-low text-on-surface-variant border-outline-variant/40',
];
$tabs = [
    ''         => 'All Live',
    'pending'  => 'Pending',
    'approved' => 'Approved',
    'rejected' => 'Suspended',
    'deleted'  => 'Archived',
];

$input = 'w-full rounded-xl border border-outline-variant/40 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/40';

$sortUrls = [];
foreach (['name', 'role', 'status', 'decks', 'reviews', 'joined'] as $key) {
    $nextDir = ($f['sort'] === $key && $f['dir'] === 'asc') ? 'desc' : 'asc';
    $sortUrls[$key] = 'users.php' . AdminUsersService::buildQuery($f, ['sort' => $key, 'dir' => $nextDir, 'page' => 1]);
}

$exportUrl = 'users.php' . AdminUsersService::buildQuery($f, ['export' => 'csv']);

$ui = [
    'badgeBase'   => 'text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border',
    'statusBadge' => $statusBadge,
    'roleBadge'   => $roleBadge,
    'toastBase'   => 'fixed bottom-6 left-1/2 -translate-x-1/2 z-[100] max-w-[90vw] px-4 py-2.5 rounded-xl shadow-xl text-sm font-semibold',
    'toastOk'     => 'bg-slate-900 text-white',
    'toastErr'    => 'bg-error text-on-error',
];

$pageBtn    = 'min-w-8 h-8 px-2 rounded-lg flex items-center justify-center text-xs font-semibold tabular-nums transition-colors ';
$firstShown = max(1, $list['page'] - 2);
$lastShown  = min($list['pages'], $list['page'] + 2);
?>

<style>
    /* Explicit drawer rules */
    #user-drawer {
        position: fixed;
        top: 0;
        right: 0;
        height: 100vh;
        width: min(100vw, 28rem);
        z-index: 70;
        transform: translateX(100%);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    #user-drawer.is-open {
        transform: translateX(0);
    }
    .admin-users-table th,
    .admin-users-table td {
        vertical-align: middle;
    }
</style>

<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <!-- TOP HEADER + PRIMARY ACTIONS -->
        <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <span class="material-symbols-outlined text-[28px] text-primary" aria-hidden="true">group</span>
                    <h1 class="font-headline-md text-xl sm:text-2xl font-bold text-on-surface m-0">User Management</h1>
                </div>
                <p class="text-xs sm:text-sm text-on-surface-variant">
                    Manage account approvals, roles, security status, and platform-wide learner activity. Study content remains private.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="<?= adminEsc($exportUrl) ?>" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/30 text-on-surface text-xs font-semibold transition-colors">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">download</span>
                    <span>Export CSV</span>
                </a>
                <button type="button" id="open-create-user-btn" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary hover:opacity-90 text-on-primary text-xs font-semibold shadow-xs transition-opacity">
                    <span class="material-symbols-outlined text-[18px]" aria-hidden="true">person_add</span>
                    <span>Add User</span>
                </button>
            </div>
        </div>

        <!-- 4 KPI SUMMARY CARDS -->
        <section aria-label="User summary metrics" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Total Live Users</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($counts['all']) ?></p>
                    <span class="text-[11px] text-on-surface-variant"><?= $fmt($counts['approved']) ?> approved</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">groups</span>
                </div>
            </div>

            <a href="users.php?status=pending" class="bg-surface-container-lowest border border-outline-variant/30 hover:border-amber-400 rounded-2xl p-4 shadow-xs flex items-center justify-between transition-colors">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Pending Approval</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($counts['pending']) ?></p>
                    <span class="text-[11px] text-amber-700 font-medium"><?= $counts['pending'] > 0 ? 'Needs review →' : 'Queue clear' ?></span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-700 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">how_to_reg</span>
                </div>
            </a>

            <div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-4 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-teal-700">Active (7 Days)</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($counts['active7d']) ?></p>
                    <span class="text-[11px] text-on-surface-variant">Studied this week</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-teal-50 border border-teal-200/80 text-teal-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">bolt</span>
                </div>
            </div>

            <a href="users.php?role=admin" class="bg-surface-container-lowest border border-outline-variant/30 hover:border-primary/40 rounded-2xl p-4 shadow-xs flex items-center justify-between transition-colors">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Administrators</span>
                    <p class="font-headline-md text-2xl font-extrabold text-on-surface mt-1 tabular-nums"><?= $fmt($counts['admins']) ?></p>
                    <span class="text-[11px] text-on-surface-variant"><?= $fmt($counts['deleted']) ?> archived accounts</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-200/80 text-indigo-600 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[22px]">shield_person</span>
                </div>
            </a>
        </section>

        <?php if (!$list['available']): ?>
            <div role="alert" class="flex items-start gap-3 p-4 rounded-2xl bg-error-container/40 border border-error/20 text-sm text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-error" aria-hidden="true">error</span>
                <span>We couldn't load the user list. Refresh the page, and check the server log if it keeps happening.</span>
            </div>
        <?php endif; ?>

        <!-- MAIN TABLE CARD -->
        <section class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl shadow-xs overflow-hidden min-w-0" aria-label="User list">

            <!-- Status Tabs -->
            <nav class="flex gap-1 overflow-x-auto px-3 pt-3 border-b border-outline-variant/30 scrollbar-none" aria-label="Filter by status">
                <?php foreach ($tabs as $key => $label):
                    $on = $f['status'] === $key;
                    $n  = $key === '' ? $counts['all'] : ($counts[$key] ?? 0); ?>
                    <a href="users.php<?= adminEsc(AdminUsersService::buildQuery($f, ['status' => $key, 'page' => 1])) ?>"
                       aria-current="<?= $on ? 'page' : 'false' ?>"
                       class="flex items-center gap-2 px-3 py-2 text-sm font-semibold whitespace-nowrap border-b-2 -mb-px transition-colors <?= $on ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
                        <?= adminEsc($label) ?>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md <?= $on ? 'bg-primary/10 text-primary' : 'bg-surface-container-low text-on-surface-variant' ?>"><?= $fmt($n) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Improved Search & Filter Bar with Visible Labels -->
            <form method="get" action="users.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5 p-4 border-b border-outline-variant/30 bg-surface-container-lowest items-end">
                <input type="hidden" name="status" value="<?= adminEsc($f['status']) ?>">
                <input type="hidden" name="sort" value="<?= adminEsc($f['sort']) ?>">
                <input type="hidden" name="dir" value="<?= adminEsc($f['dir']) ?>">

                <div class="lg:col-span-4 flex flex-col gap-1.5">
                    <label for="flt-q" class="text-xs font-bold text-on-surface-variant">Search</label>
                    <input id="flt-q" type="search" name="q" value="<?= adminEsc($f['q']) ?>" maxlength="100" placeholder="Name or email…" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2 flex flex-col gap-1.5">
                    <label for="flt-role" class="text-xs font-bold text-on-surface-variant">Role</label>
                    <select id="flt-role" name="role" class="<?= $input ?>" data-autosubmit>
                        <option value="">All roles</option>
                        <?php foreach (AdminUsersService::ROLES as $r): ?>
                            <option value="<?= $r ?>" <?= $f['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-2 flex flex-col gap-1.5">
                    <label for="flt-from" class="text-xs font-bold text-on-surface-variant">Joined From</label>
                    <input id="flt-from" type="date" name="from" value="<?= adminEsc($f['from']) ?>" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2 flex flex-col gap-1.5">
                    <label for="flt-to" class="text-xs font-bold text-on-surface-variant">Joined To</label>
                    <input id="flt-to" type="date" name="to" value="<?= adminEsc($f['to']) ?>" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:opacity-90 transition-opacity h-[38px]">Filter</button>
                    <?php if ($hasFilters): ?>
                        <a href="users.php" class="px-3 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface text-sm font-semibold transition-colors flex items-center justify-center h-[38px]">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Bulk Action Toolbar -->
            <div id="bulk-action-bar" class="hidden items-center justify-between gap-3 px-4 py-2.5 bg-primary-fixed/40 border-b border-primary/20">
                <div class="flex items-center gap-2 text-xs font-bold text-on-surface">
                    <span id="bulk-selected-count" class="px-2 py-0.5 rounded-md bg-primary text-on-primary">0</span>
                    <span>selected</span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <?php if ($f['status'] === 'deleted'): ?>
                        <button type="button" data-bulk-action="restore" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-teal-50 border border-teal-200 text-teal-700 text-xs font-bold hover:bg-teal-100 transition-colors">
                            <span class="material-symbols-outlined text-[15px]">restore_from_trash</span>Restore Selected
                        </button>
                    <?php else: ?>
                        <button type="button" data-bulk-action="approve" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-teal-50 border border-teal-200 text-teal-700 text-xs font-bold hover:bg-teal-100 transition-colors">
                            <span class="material-symbols-outlined text-[15px]">check</span>Approve
                        </button>
                        <button type="button" data-bulk-action="reject" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-surface-container-lowest border border-outline-variant text-on-surface text-xs font-bold hover:bg-surface-container-low transition-colors">
                            <span class="material-symbols-outlined text-[15px]">block</span>Suspend
                        </button>
                        <button type="button" data-bulk-action="delete" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-error-container/40 border border-error/20 text-error text-xs font-bold hover:bg-error-container/70 transition-colors">
                            <span class="material-symbols-outlined text-[15px]">delete</span>Archive
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($list['rows']): ?>
                <!-- NATIVE RESPONSIVE DATA TABLE -->
                <div class="overflow-x-auto">
                    <table class="admin-users-table w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/30 text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">
                                <th class="py-3 pl-4 pr-2 w-9">
                                    <input type="checkbox" id="select-all-users" aria-label="Select all users on this page" class="w-4 h-4 rounded accent-primary cursor-pointer">
                                </th>
                                <?php
                                $headers = [
                                    'name'    => ['label' => 'User',           'class' => 'py-3 px-3 min-w-[200px]'],
                                    'role'    => ['label' => 'Role',           'class' => 'py-3 px-3'],
                                    'status'  => ['label' => 'Status',         'class' => 'py-3 px-3'],
                                    'decks'   => ['label' => 'Study Content',  'class' => 'py-3 px-3 hidden md:table-cell'],
                                    'reviews' => ['label' => 'Activity',       'class' => 'py-3 px-3 hidden lg:table-cell'],
                                    'joined'  => ['label' => 'Joined',         'class' => 'py-3 px-3 hidden sm:table-cell'],
                                ];
                                foreach ($headers as $key => $h):
                                    $active = $f['sort'] === $key;
                                    $icon   = $active ? ($f['dir'] === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more';
                                ?>
                                    <th class="<?= $h['class'] ?>">
                                        <a href="<?= adminEsc($sortUrls[$key]) ?>" class="inline-flex items-center gap-1 hover:text-on-surface transition-colors">
                                            <span><?= adminEsc($h['label']) ?></span>
                                            <span class="material-symbols-outlined text-[14px] <?= $active ? 'text-primary' : 'opacity-40' ?>" aria-hidden="true"><?= $icon ?></span>
                                        </a>
                                    </th>
                                <?php endforeach; ?>
                                <th class="py-3 pl-3 pr-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20 text-sm">
                            <?php foreach ($list['rows'] as $r):
                                $id       = (int) $r['id'];
                                $isSelf   = $id === $adminUser['id'];
                                $actions  = $isSelf ? ['approve', 'reject', 'delete', 'restore', 'make_admin', 'make_user'] : AdminUserActionsService::allowedActions($r['role'], $r['rawStatus'], $r['isDeleted']);
                                if ($isSelf) { $actions = []; } // self protection
                                $firstChr = mb_strtoupper(mb_substr(trim($r['name']), 0, 1) ?: '?');
                            ?>
                                <tr data-row-user-id="<?= $id ?>" class="hover:bg-surface-container-low/60 transition-colors cursor-pointer">
                                    <td class="py-3 pl-4 pr-2" onclick="event.stopPropagation()">
                                        <?php if (!$isSelf): ?>
                                            <input type="checkbox" class="user-row-checkbox w-4 h-4 rounded accent-primary cursor-pointer" value="<?= $id ?>" aria-label="Select <?= adminEsc($r['name']) ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-xs font-bold">
                                                <?= adminEsc($firstChr) ?>
                                            </div>
                                            <div class="flex flex-col min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-xs font-bold text-on-surface truncate"><?= adminEsc($r['name']) ?></span>
                                                    <?php if ($isSelf): ?>
                                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-primary/10 text-primary">You</span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="text-[11px] text-on-surface-variant truncate"><?= adminEsc($r['email']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="<?= $ui['badgeBase'] ?> <?= $roleBadge[$r['role']] ?? $roleBadge['user'] ?>">
                                            <?= adminEsc($r['role']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="<?= $ui['badgeBase'] ?> <?= $statusBadge[$r['status']] ?? $statusBadge['pending'] ?>">
                                            <?= adminEsc($r['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 hidden md:table-cell">
                                        <div class="flex flex-col text-xs tabular-nums">
                                            <span class="font-semibold text-on-surface"><?= $fmt($r['decks']) ?> decks</span>
                                            <span class="text-[11px] text-on-surface-variant"><?= $fmt($r['cards']) ?> cards</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 hidden lg:table-cell">
                                        <div class="flex flex-col text-xs tabular-nums">
                                            <span class="font-semibold text-on-surface"><?= $fmt($r['reviews']) ?> reviews</span>
                                            <span class="text-[11px] text-on-surface-variant">Active: <?= adminEsc($r['lastActive']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 hidden sm:table-cell text-xs text-on-surface-variant tabular-nums whitespace-nowrap">
                                        <?= adminEsc($r['joined']) ?>
                                    </td>
                                    <td class="py-3 pl-3 pr-4 text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                        <div class="inline-flex items-center justify-end gap-1.5">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <button type="button" data-action="approve" data-user-id="<?= $id ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-teal-50 border border-teal-200/80 text-teal-700 text-xs font-bold hover:bg-teal-100 transition-colors">
                                                    Approve
                                                </button>
                                                <button type="button" data-action="reject" data-user-id="<?= $id ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-outline-variant text-on-surface text-xs font-bold hover:bg-surface-container-low transition-colors">
                                                    Reject
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" data-user-id="<?= $id ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/40 text-on-surface hover:text-primary text-xs font-bold transition-colors">
                                                <span>Manage</span>
                                                <span class="material-symbols-outlined text-[15px]" aria-hidden="true">chevron_right</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-outline-variant/30" aria-label="Pagination">
                    <p class="text-xs text-on-surface-variant">
                        Showing <?= $fmt($list['first']) ?>&ndash;<?= $fmt($list['last']) ?> of <?= $fmt($list['total']) ?> accounts
                    </p>
                    <?php if ($list['pages'] > 1): ?>
                        <div class="flex items-center gap-1">
                            <?php if ($list['page'] > 1): ?>
                                <a href="users.php<?= adminEsc(AdminUsersService::buildQuery($f, ['page' => $list['page'] - 1])) ?>" aria-label="Previous page"
                                   class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">chevron_left</span>
                                </a>
                            <?php endif; ?>
                            <?php for ($p = $firstShown; $p <= $lastShown; $p++): ?>
                                <?php if ($p === $list['page']): ?>
                                    <span aria-current="page" class="<?= $pageBtn ?>bg-primary text-on-primary"><?= $p ?></span>
                                <?php else: ?>
                                    <a href="users.php<?= adminEsc(AdminUsersService::buildQuery($f, ['page' => $p])) ?>" class="<?= $pageBtn ?>text-on-surface-variant hover:bg-surface-container-high hover:text-primary"><?= $p ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <?php if ($list['page'] < $list['pages']): ?>
                                <a href="users.php<?= adminEsc(AdminUsersService::buildQuery($f, ['page' => $list['page'] + 1])) ?>" aria-label="Next page"
                                   class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface-variant hover:bg-surface-container-high hover:text-primary transition-colors">
                                    <span class="material-symbols-outlined text-[20px]" aria-hidden="true">chevron_right</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </nav>
            <?php elseif ($list['available']): ?>
                <div class="flex flex-col items-center justify-center p-12 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[36px] text-outline mb-2" aria-hidden="true">person_search</span>
                    <span class="text-sm font-semibold text-on-surface"><?= $hasFilters ? 'No accounts match these filters.' : 'No accounts found.' ?></span>
                    <span class="text-xs text-on-surface-variant mt-1">Try adjusting your search query, role, or status tab.</span>
                    <?php if ($hasFilters): ?>
                        <a href="users.php" class="mt-3 px-3.5 py-1.5 rounded-xl bg-primary/10 text-primary text-xs font-bold hover:underline">Reset all filters</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- SLIDE-OVER USER INSPECTOR DRAWER -->
<div id="user-drawer-overlay" class="fixed inset-0 z-[60] hidden opacity-0 transition-opacity drawer-backdrop"></div>
<aside id="user-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-name" tabindex="-1"
       class="hidden bg-surface-container-lowest border-l border-outline-variant/40 shadow-2xl flex flex-col">
    <div class="flex items-start justify-between gap-3 p-5 border-b border-outline-variant/30">
        <div class="flex items-center gap-3 min-w-0">
            <div data-field="initial" class="w-11 h-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0 text-sm font-bold"></div>
            <div class="flex flex-col min-w-0">
                <h2 id="drawer-name" data-field="name" class="font-headline-sm text-base font-bold text-on-surface truncate"></h2>
                <span data-field="email" class="text-xs text-on-surface-variant truncate"></span>
            </div>
        </div>
        <button type="button" id="user-drawer-close" aria-label="Close details" class="p-1.5 rounded-lg hover:bg-surface-container-low text-on-surface-variant transition-colors">
            <span class="material-symbols-outlined text-[22px]" aria-hidden="true">close</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-5 flex flex-col gap-5">
        <!-- Badges -->
        <div class="flex flex-wrap items-center gap-2">
            <span data-field="status"></span>
            <span data-field="role"></span>
            <span data-field="self" class="hidden text-[10px] font-bold px-2 py-0.5 rounded-md bg-primary/10 text-primary">Your Account</span>
        </div>

        <!-- 6 Study & Account Telemetry Cards -->
        <div>
            <h3 class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-2.5">Account &amp; Study Telemetry</h3>
            <dl class="grid grid-cols-2 gap-2.5">
                <?php foreach ([
                    'decks'      => 'Active Decks',
                    'cards'      => 'Flashcards',
                    'sessions'   => 'Sessions Done',
                    'reviews'    => 'Cards Reviewed',
                    'lastActive' => 'Last Active',
                    'joined'     => 'Joined Date',
                ] as $k => $label): ?>
                    <div class="p-3 rounded-xl bg-surface-container-low border border-outline-variant/30">
                        <dt class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant"><?= $label ?></dt>
                        <dd data-field="<?= $k ?>" class="text-sm font-extrabold text-on-surface mt-0.5 tabular-nums"></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        </div>

        <!-- Per-User Admin Audit History -->
        <div>
            <h3 class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-2.5">Recent Admin Audit Trail</h3>
            <div id="drawer-audit-list" class="flex flex-col gap-2"></div>
        </div>

        <div class="flex items-start gap-2 p-3 rounded-xl bg-surface-container-low border border-outline-variant/30 text-[11px] text-on-surface-variant leading-snug">
            <span class="material-symbols-outlined text-[16px]" aria-hidden="true">lock</span>
            <span>Only aggregate study counts are visible to administrators. Deck titles and flashcard content remain private.</span>
        </div>
    </div>

    <!-- Drawer Footer Actions -->
    <div class="p-5 border-t border-outline-variant/30 flex flex-col gap-3 bg-surface-container-lowest">
        <p data-field="locked" class="hidden text-xs text-on-surface-variant leading-snug"></p>
        <div id="drawer-actions" class="flex flex-wrap items-center gap-2">
            <button type="button" data-action="approve" class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary text-on-primary text-xs font-bold hover:opacity-90 disabled:opacity-50 transition-opacity">
                <span class="material-symbols-outlined text-[16px]">check</span>Approve
            </button>
            <button type="button" data-action="reject" class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-outline-variant text-on-surface text-xs font-bold hover:bg-surface-container-low disabled:opacity-50 transition-colors">
                <span class="material-symbols-outlined text-[16px]">block</span>Suspend
            </button>
            <button type="button" data-action="restore" class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-50 border border-teal-200 text-teal-700 text-xs font-bold hover:bg-teal-100 disabled:opacity-50 transition-colors">
                <span class="material-symbols-outlined text-[16px]">restore_from_trash</span>Restore Account
            </button>
            <button type="button" data-action="make_admin" class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-primary/30 bg-primary/10 text-primary text-xs font-bold hover:bg-primary/20 disabled:opacity-50 transition-colors">
                <span class="material-symbols-outlined text-[16px]">shield_person</span>Make Admin
            </button>
            <button type="button" data-action="make_user" class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-outline-variant text-on-surface text-xs font-bold hover:bg-surface-container-low disabled:opacity-50 transition-colors">
                <span class="material-symbols-outlined text-[16px]">person</span>Demote to User
            </button>
            <button type="button" data-action="delete" class="hidden inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-error text-xs font-bold hover:bg-error-container/40 disabled:opacity-50 transition-colors sm:ml-auto">
                <span class="material-symbols-outlined text-[16px]">delete</span>Archive
            </button>
        </div>
    </div>
</aside>

<!-- CREATE USER MODAL -->
<dialog id="create-user-dialog" aria-labelledby="create-user-title" class="rounded-2xl p-0 w-full max-w-md bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs">
    <form id="create-user-form" class="p-6 flex flex-col gap-4" novalidate>
        <div class="flex items-center justify-between">
            <h3 id="create-user-title" class="font-headline-sm text-lg font-bold text-on-surface">Create New User</h3>
            <button type="button" id="create-user-close" class="p-1 rounded-lg text-on-surface-variant hover:bg-surface-container-low">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1" for="cu-first">First name *</label>
                <input id="cu-first" name="first_name" type="text" required class="<?= $input ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1" for="cu-last">Last name *</label>
                <input id="cu-last" name="last_name" type="text" required class="<?= $input ?>">
            </div>
        </div>
        <div>
            <label class="block text-xs font-bold text-on-surface-variant mb-1" for="cu-email">Email address *</label>
            <input id="cu-email" name="email" type="email" required placeholder="learner@example.com" class="<?= $input ?>">
        </div>
        <div>
            <label class="block text-xs font-bold text-on-surface-variant mb-1" for="cu-password">Temporary password (min 8 chars) *</label>
            <input id="cu-password" name="password" type="password" required minlength="8" class="<?= $input ?>">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1" for="cu-role">Role</label>
                <select id="cu-role" name="role" class="<?= $input ?>">
                    <option value="user">User (Learner)</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant mb-1" for="cu-status">Initial Status</label>
                <select id="cu-status" name="status" class="<?= $input ?>">
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
        </div>
        <p id="create-user-error" role="alert" class="hidden text-xs font-semibold text-error"></p>
        <div class="flex items-center justify-end gap-2 pt-3 border-t border-outline-variant/20">
            <button type="button" id="create-user-cancel" class="px-4 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-xs">Cancel</button>
            <button type="submit" id="create-user-submit" class="px-4 py-2 rounded-xl bg-primary hover:opacity-90 text-on-primary font-semibold text-xs">Create Account</button>
        </div>
    </form>
</dialog>

<!-- CONFIRM ACTION DIALOG -->
<dialog id="user-action-dialog" aria-labelledby="user-action-title" class="rounded-2xl p-0 w-full max-w-sm bg-surface-container-lowest text-on-surface border border-outline-variant/30 shadow-2xl backdrop:bg-black/50 backdrop:backdrop-blur-xs transition-all">
    <div class="p-6 flex flex-col gap-4">
        <h3 id="user-action-title" class="font-headline-sm text-lg font-bold text-on-surface tracking-tight"></h3>
        <p id="user-action-message" class="text-sm text-on-surface-variant font-medium leading-relaxed"></p>
        <p id="user-action-error" role="alert" class="hidden text-xs font-semibold text-error"></p>
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-outline-variant/20">
            <button type="button" id="user-action-cancel" class="px-4 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface font-semibold text-sm transition-colors">Cancel</button>
            <button type="button" id="user-action-confirm" class="inline-flex items-center px-4 py-2 rounded-xl bg-error hover:opacity-90 disabled:opacity-50 text-on-error font-semibold text-sm shadow-sm transition-colors"></button>
        </div>
    </div>
</dialog>

<!-- TOAST -->
<div id="admin-toast" role="status" aria-live="polite" class="hidden"></div>

<script>
    window.MemPulseAdminUsersUI = <?= json_encode($ui, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>