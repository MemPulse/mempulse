<?php
// Expects (from users.php): $filters, $counts, $list, $adminUser.
$f   = $filters;
$fmt = fn($n) => number_format((int) $n);
$hasFilters = $f['q'] !== '' || $f['status'] !== '' || $f['role'] !== '' || $f['from'] !== '' || $f['to'] !== '';

$initial = function (string $n): string {
    $c = trim($n) === '' ? '?' : (function_exists('mb_substr') ? mb_substr(trim($n), 0, 1) : substr(trim($n), 0, 1));
    return function_exists('mb_strtoupper') ? mb_strtoupper($c) : strtoupper($c);
};

$statusBadge = [
    'approved' => 'bg-teal-50 text-teal-700 border-teal-200/80',
    'pending'  => 'bg-amber-50 text-amber-700 border-amber-200/80',
    'rejected' => 'bg-error-container/40 text-error border-error/20',
];
$roleBadge = [
    'admin' => 'bg-primary/10 text-primary border-primary/20',
    'user'  => 'bg-surface-container-low text-on-surface-variant border-outline-variant/40',
];
$tabs = ['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

$panel = 'bg-surface-container-lowest rounded-2xl shadow-premium-panel min-w-0';
$input = 'w-full rounded-xl border border-outline-variant/60 bg-surface-container-lowest px-3 py-2 text-sm text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary/40';

$sortLink = function (string $key, string $label, string $cls = '') use ($f): string {
    $active = $f['sort'] === $key;
    $nextDir = ($active && $f['dir'] === 'asc') ? 'desc' : 'asc';
    $href = 'users.php' . AdminUsersService::buildQuery($f, ['sort' => $key, 'dir' => $nextDir, 'page' => 1]);
    $icon = $active ? ($f['dir'] === 'asc' ? 'arrow_upward' : 'arrow_downward') : 'unfold_more';
    $aria = $active ? ($f['dir'] === 'asc' ? 'ascending' : 'descending') : 'none';
    return '<th scope="col" aria-sort="' . $aria . '" class="px-4 py-3 font-semibold ' . $cls . '"><a href="' . adminEsc($href) . '" class="inline-flex items-center gap-1 hover:text-on-surface transition-colors">'
        . adminEsc($label) . '<span class="material-symbols-outlined text-[14px] ' . ($active ? 'text-primary' : 'opacity-50') . '" aria-hidden="true">' . $icon . '</span></a></th>';
};
?>
<div class="w-full px-2 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8 overflow-visible">
    <div class="flex flex-col w-full gap-4 sm:gap-6">

        <header class="flex flex-col gap-1">
            <h1 class="font-headline-md text-headline-lg-mobile sm:text-headline-lg font-bold tracking-tight text-on-surface">Users</h1>
            <p class="text-sm text-on-surface-variant">Find accounts and see how they use MemPulse. Study content is never shown here.</p>
        </header>

        <?php if (!$list['available']): ?>
            <div role="alert" class="flex items-start gap-3 p-4 rounded-2xl bg-error-container/40 border border-error/20 text-sm text-on-surface">
                <span class="material-symbols-outlined text-[20px] text-error" aria-hidden="true">error</span>
                <span>We couldn't load the user list. Refresh the page, and check the server log if it keeps happening.</span>
            </div>
        <?php endif; ?>

        <section class="<?= $panel ?> overflow-hidden" aria-label="User list">

            <!-- Status tabs -->
            <nav class="flex gap-1 overflow-x-auto px-3 pt-3 border-b border-outline-variant/40" aria-label="Filter by status">
                <?php foreach ($tabs as $key => $label):
                    $on = $f['status'] === $key;
                    $n  = $key === '' ? $counts['all'] : $counts[$key]; ?>
                    <a href="users.php<?= adminEsc(AdminUsersService::buildQuery($f, ['status' => $key, 'page' => 1])) ?>"
                       aria-current="<?= $on ? 'page' : 'false' ?>"
                       class="flex items-center gap-2 px-3 py-2 text-sm font-semibold whitespace-nowrap border-b-2 -mb-px transition-colors <?= $on ? 'border-primary text-primary' : 'border-transparent text-on-surface-variant hover:text-on-surface' ?>">
                        <?= adminEsc($label) ?>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md <?= $on ? 'bg-primary/10 text-primary' : 'bg-surface-container-low text-on-surface-variant' ?>"><?= $fmt($n) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Filters -->
            <form method="get" action="users.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 p-4 border-b border-outline-variant/40">
                <input type="hidden" name="status" value="<?= adminEsc($f['status']) ?>">
                <input type="hidden" name="sort" value="<?= adminEsc($f['sort']) ?>">
                <input type="hidden" name="dir" value="<?= adminEsc($f['dir']) ?>">

                <div class="lg:col-span-4">
                    <label for="flt-q" class="sr-only">Search name or email</label>
                    <input id="flt-q" type="search" name="q" value="<?= adminEsc($f['q']) ?>" maxlength="100" placeholder="Search name or email" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2">
                    <label for="flt-role" class="sr-only">Role</label>
                    <select id="flt-role" name="role" class="<?= $input ?>" data-autosubmit>
                        <option value="">All roles</option>
                        <?php foreach (AdminUsersService::ROLES as $r): ?>
                            <option value="<?= $r ?>" <?= $f['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="flt-from" class="sr-only">Joined from</label>
                    <input id="flt-from" type="date" name="from" value="<?= adminEsc($f['from']) ?>" title="Joined from" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2">
                    <label for="flt-to" class="sr-only">Joined to</label>
                    <input id="flt-to" type="date" name="to" value="<?= adminEsc($f['to']) ?>" title="Joined to" class="<?= $input ?>">
                </div>
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-primary text-on-primary text-sm font-semibold hover:opacity-90 transition-opacity">Apply</button>
                    <?php if ($hasFilters): ?>
                        <a href="users.php" class="px-3 py-2 rounded-xl bg-surface-container-low hover:bg-surface-container-high text-on-surface text-sm font-semibold transition-colors">Clear</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Table -->
            <?php if ($list['rows']): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-[11px] uppercase tracking-wider text-on-surface-variant bg-surface-container-low/60">
                            <tr>
                                <?= $sortLink('name', 'User') ?>
                                <?= $sortLink('role', 'Role', 'hidden md:table-cell') ?>
                                <?= $sortLink('status', 'Status') ?>
                                <?= $sortLink('joined', 'Joined', 'hidden sm:table-cell') ?>
                                <th scope="col" class="px-4 py-3 font-semibold hidden lg:table-cell">Last active</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Details</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($list['rows'] as $u): $self = $u['id'] === $adminUser['id']; ?>
                                <tr class="border-t border-outline-variant/20 hover:bg-slate-50/60 transition-colors group">
                                    <td class="px-4 py-2">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center flex-shrink-0 text-xs font-bold"><?= adminEsc($initial($u['name'])) ?></div>
                                            <div class="flex flex-col min-w-0">
                                                <span class="text-xs font-bold text-slate-900 truncate max-w-[14rem]"><?= adminEsc($u['name']) ?><?= $self ? ' <span class="ml-1 text-[10px] font-semibold text-primary">(you)</span>' : '' ?></span>
                                                <span class="text-[11px] text-slate-500 truncate max-w-[14rem]"><?= adminEsc($u['email']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 hidden md:table-cell">
                                        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border <?= $roleBadge[$u['role']] ?? $roleBadge['user'] ?>"><?= adminEsc($u['role']) ?></span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border <?= $statusBadge[$u['status']] ?? $statusBadge['pending'] ?>"><?= adminEsc($u['status']) ?></span>
                                    </td>
                                    <td class="px-4 py-2 text-xs text-on-surface-variant whitespace-nowrap hidden sm:table-cell"><?= adminEsc($u['joined']) ?></td>
                                    <td class="px-4 py-2 text-xs text-on-surface-variant whitespace-nowrap hidden lg:table-cell"><?= adminEsc($u['lastActive']) ?></td>
                                    <td class="px-4 py-2 text-right">
                                        <button type="button" data-user-id="<?= (int) $u['id'] ?>" aria-label="View details for <?= adminEsc($u['name']) ?>"
                                            class="px-3 py-1.5 rounded-lg bg-surface-container-low hover:bg-surface-container-high text-xs font-semibold text-on-surface transition-colors">Details</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-outline-variant/40 text-xs text-on-surface-variant">
                    <span>Showing <?= $fmt($list['first']) ?>&ndash;<?= $fmt($list['last']) ?> of <?= $fmt($list['total']) ?></span>
                    <div class="flex items-center gap-2">
                        <?php
                        $pageBtn = 'inline-flex items-center gap-1 px-3 py-1.5 rounded-lg font-semibold transition-colors ';
                        $prev = $list['page'] - 1; $next = $list['page'] + 1;
                        ?>
                        <?php if ($prev >= 1): ?>
                            <a href="users.php<?= adminEsc(AdminUsersService::buildQuery($f, ['page' => $prev])) ?>" class="<?= $pageBtn ?>bg-surface-container-low hover:bg-surface-container-high text-on-surface"><span class="material-symbols-outlined text-[16px]" aria-hidden="true">chevron_left</span>Prev</a>
                        <?php else: ?>
                            <span class="<?= $pageBtn ?>opacity-40 cursor-not-allowed"><span class="material-symbols-outlined text-[16px]" aria-hidden="true">chevron_left</span>Prev</span>
                        <?php endif; ?>
                        <span>Page <?= (int) $list['page'] ?> of <?= (int) $list['pages'] ?></span>
                        <?php if ($next <= $list['pages']): ?>
                            <a href="users.php<?= adminEsc(AdminUsersService::buildQuery($f, ['page' => $next])) ?>" class="<?= $pageBtn ?>bg-surface-container-low hover:bg-surface-container-high text-on-surface">Next<span class="material-symbols-outlined text-[16px]" aria-hidden="true">chevron_right</span></a>
                        <?php else: ?>
                            <span class="<?= $pageBtn ?>opacity-40 cursor-not-allowed">Next<span class="material-symbols-outlined text-[16px]" aria-hidden="true">chevron_right</span></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($list['available']): ?>
                <div class="flex flex-col items-center justify-center p-10 text-center text-on-surface-variant">
                    <span class="material-symbols-outlined text-[36px] text-outline mb-2" aria-hidden="true">person_search</span>
                    <span class="text-sm font-medium"><?= $hasFilters ? 'No users match these filters.' : 'No users yet.' ?></span>
                    <?php if ($hasFilters): ?><a href="users.php" class="text-xs text-primary font-semibold mt-2 hover:underline">Clear filters</a><?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<!-- Detail drawer -->
<div id="user-drawer-overlay" class="fixed inset-0 z-[60] hidden opacity-0 transition-opacity drawer-backdrop"></div>
<aside id="user-drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-name" tabindex="-1"
       class="fixed right-0 top-0 h-full w-full sm:w-[26rem] bg-surface-container-lowest border-l border-outline-variant/60 shadow-2xl z-[70] translate-x-full transition-transform duration-300 flex flex-col invisible">
    <div class="flex items-start justify-between gap-3 p-5 border-b border-outline-variant/40">
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
        <div class="flex flex-wrap items-center gap-2">
            <span data-field="status" class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border"></span>
            <span data-field="role" class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md border"></span>
            <span data-field="self" class="hidden text-[10px] font-semibold text-primary">This is your account</span>
        </div>

        <dl class="grid grid-cols-2 gap-3">
            <?php foreach (['decks' => 'Decks', 'cards' => 'Flashcards', 'sessions' => 'Sessions', 'reviews' => 'Cards reviewed', 'retention' => 'Retention', 'lastActive' => 'Last active'] as $k => $label): ?>
                <div class="p-3 rounded-xl bg-surface-container-low border border-outline-variant/40">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant"><?= $label ?></dt>
                    <dd data-field="<?= $k ?>" class="text-lg font-extrabold text-on-surface mt-0.5"></dd>
                </div>
            <?php endforeach; ?>
        </dl>

        <p class="text-xs text-on-surface-variant">Joined <span data-field="joined" class="font-semibold text-on-surface"></span></p>

        <div class="flex items-start gap-2 p-3 rounded-xl bg-surface-container-low border border-outline-variant/40 text-[11px] text-on-surface-variant leading-snug">
            <span class="material-symbols-outlined text-[16px]" aria-hidden="true">lock</span>
            <span>Only aggregates are shown. Deck and card content stays private.</span>
        </div>
    </div>
</aside>