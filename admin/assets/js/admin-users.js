/**
 * Admin Users: detail drawer, audit log history, single & bulk actions,
 * role promotion/demotion, and admin user creation modal.
 */
(function () {
    'use strict';

    var data = window.MemPulseAdminUsers || { users: {}, order: [] };
    var ui = window.MemPulseAdminUsersUI || { statusBadge: {}, roleBadge: {} };
    var FLASH_KEY = 'adminUsersFlash';

    var drawer = document.getElementById('user-drawer');
    var overlay = document.getElementById('user-drawer-overlay');
    var closeBtn = document.getElementById('user-drawer-close');
    var dialog = document.getElementById('user-action-dialog');
    var createDialog = document.getElementById('create-user-dialog');
    var createForm = document.getElementById('create-user-form');
    var toastEl = document.getElementById('admin-toast');

    var opener = null;
    var currentId = null;
    var busy = false;
    var toastTimer = null;
    var pending = null;

    // ---- Toast --------------------------------------------------------------
    function toast(message, ok) {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.className = (ui.toastBase || '') + ' ' + (ok ? ui.toastOk : ui.toastErr);
        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastEl.className = 'hidden'; }, 4500);
    }

    try {
        var flash = sessionStorage.getItem(FLASH_KEY);
        if (flash) {
            sessionStorage.removeItem(FLASH_KEY);
            toast(flash, true);
        }
    } catch (e) { }

    // ---- Auto-submit select filters -----------------------------------------
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    // ---- Bulk Selection Checkboxes ------------------------------------------
    var selectAllBox = document.getElementById('select-all-users');
    var bulkBar = document.getElementById('bulk-action-bar');
    var bulkCountEl = document.getElementById('bulk-selected-count');

    function getSelectedIds() {
        var ids = [];
        document.querySelectorAll('.user-row-checkbox:checked').forEach(function (cb) {
            ids.push(Number(cb.value));
        });
        return ids;
    }

    function syncBulkBar() {
        var ids = getSelectedIds();
        if (!bulkBar) return;
        if (ids.length > 0) {
            bulkBar.classList.remove('hidden');
            bulkBar.classList.add('flex');
            if (bulkCountEl) bulkCountEl.textContent = String(ids.length);
        } else {
            bulkBar.classList.add('hidden');
            bulkBar.classList.remove('flex');
        }
    }

    if (selectAllBox) {
        selectAllBox.addEventListener('change', function () {
            document.querySelectorAll('.user-row-checkbox').forEach(function (cb) {
                cb.checked = selectAllBox.checked;
            });
            syncBulkBar();
        });
    }

    document.querySelectorAll('.user-row-checkbox').forEach(function (cb) {
        cb.addEventListener('change', syncBulkBar);
    });

    // ---- Drawer -------------------------------------------------------------
    function field(name) { return drawer ? drawer.querySelector('[data-field="' + name + '"]') : null; }
    function setText(name, value) { var el = field(name); if (el) el.textContent = value; }
    // `value` is the text shown; `key` (optional) is the raw value used to look up the badge colours.
    function setBadge(name, value, map, key) {
        var el = field(name);
        if (!el) return;
        el.textContent = value;
        el.className = (ui.badgeBase || '') + ' ' + (map[key || value] || '');
    }
    function num(n) { return Number(n || 0).toLocaleString(); }

    var AUDIT_LABELS = {
        user_approved: 'Approved account',
        user_rejected: 'Suspended account',
        user_deleted: 'Archived account',
        user_restored: 'Restored account',
        role_promoted_admin: 'Promoted to Admin',
        role_demoted_user: 'Changed role to User',
        user_created_by_admin: 'Created by Admin'
    };

    function renderAudit(items) {
        var container = document.getElementById('drawer-audit-list');
        if (!container) return;
        container.textContent = '';

        if (!items || items.length === 0) {
            var empty = document.createElement('p');
            empty.className = 'text-xs text-on-surface-variant italic py-2';
            empty.textContent = 'No admin actions recorded for this account yet.';
            container.appendChild(empty);
            return;
        }

        items.forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'p-2.5 rounded-xl bg-surface-container-low border border-outline-variant/20 flex items-center justify-between gap-2 text-xs';

            var left = document.createElement('div');
            left.className = 'flex flex-col min-w-0';

            var title = document.createElement('span');
            title.className = 'font-bold text-on-surface truncate';
            title.textContent = AUDIT_LABELS[item.action] || item.action;

            var sub = document.createElement('span');
            sub.className = 'text-[11px] text-on-surface-variant truncate';
            sub.textContent = 'By ' + (item.by || 'Admin');

            left.appendChild(title);
            left.appendChild(sub);

            var right = document.createElement('span');
            right.className = 'text-[10px] text-on-surface-variant whitespace-nowrap';
            right.textContent = item.when || '';

            row.appendChild(left);
            row.appendChild(right);
            container.appendChild(row);
        });
    }

    function fill(u) {
        setText('initial', (u.name || '?').trim().charAt(0).toUpperCase());
        setText('name', u.name);
        setText('email', u.email);
        setBadge('status', (ui.statusLabels && ui.statusLabels[u.status]) || u.status, ui.statusBadge, u.status);
        setBadge('role', u.role, ui.roleBadge);
        var selfEl = field('self');
        if (selfEl) selfEl.classList.toggle('hidden', !u.isSelf);

        setText('decks', num(u.decks));
        setText('cards', num(u.cards));
        setText('sessions', num(u.sessions));
        setText('reviews', num(u.reviews) + (u.accuracy ? ' (' + u.accuracy + '%)' : ''));
        setText('lastActive', u.lastActive);
        setText('joined', u.joined);

        renderAudit(u.audit || []);

        var allowed = u.actions || ['approve', 'reject', 'delete', 'restore', 'make_admin', 'make_user'];
        drawer.querySelectorAll('#drawer-actions [data-action]').forEach(function (b) {
            b.classList.toggle('hidden', u.isSelf || allowed.indexOf(b.getAttribute('data-action')) === -1);
            b.disabled = false;
        });

        var locked = field('locked');
        if (locked) {
            if (u.isSelf) {
                locked.textContent = 'You cannot modify or archive your own account.';
                locked.classList.remove('hidden');
            } else {
                locked.classList.add('hidden');
            }
        }
    }

    function openDrawer(id, trigger) {
        if (!drawer || !overlay) return;
        var u = data.users[id];
        if (!u) return;
        opener = trigger || null;
        currentId = id;
        fill(u);
        drawer.classList.remove('hidden');
        overlay.classList.remove('hidden');
        requestAnimationFrame(function () {
            drawer.classList.add('is-open');
            overlay.classList.remove('opacity-0');
        });
        if (closeBtn) closeBtn.focus();
    }

    function closeDrawer() {
        if (!drawer || !overlay) return;
        drawer.classList.remove('is-open');
        overlay.classList.add('opacity-0');
        setTimeout(function () {
            drawer.classList.add('hidden');
            overlay.classList.add('hidden');
        }, 250);
        if (opener) opener.focus();
        currentId = null;
    }

    // ---- Server Requests ----------------------------------------------------
    function postJson(payload) {
        return fetch(data.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': data.csrf
            },
            body: JSON.stringify(payload)
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (body) {
                return { ok: res.ok && body.success === true, status: res.status, message: body.message };
            });
        });
    }

    function setBusy(on, trigger) {
        busy = on;
        if (trigger) trigger.disabled = on;
        var confirmBtn = document.getElementById('user-action-confirm');
        if (confirmBtn) confirmBtn.disabled = on;
    }

    function showDialogError(message) {
        var el = document.getElementById('user-action-error');
        if (!el) return;
        el.textContent = message || '';
        el.classList.toggle('hidden', !message);
    }

    function executePayload(payload, trigger) {
        if (busy) return;
        setBusy(true, trigger);
        postJson(payload).then(function (r) {
            if (r.ok) {
                try { sessionStorage.setItem(FLASH_KEY, r.message || 'Saved.'); } catch (e) { }
                window.location.reload();
                return;
            }
            setBusy(false, trigger);
            var msg = r.status === 401
                ? 'Your session has ended. Refresh the page and sign in again.'
                : (r.message || 'That action could not be completed.');
            if (dialog && dialog.open) { showDialogError(msg); } else { toast(msg, false); }
        }).catch(function () {
            setBusy(false, trigger);
            var msg = 'Could not reach the server. Check your connection and try again.';
            if (dialog && dialog.open) { showDialogError(msg); } else { toast(msg, false); }
        });
    }

    var COPY = {
        reject: {
            title: 'Suspend this account?',
            message: function (n) { return n + ' will not be able to sign in.'; },
            button: 'Suspend'
        },
        delete: {
            title: 'Archive this account?',
            message: function (n) { return n + ' will be archived and hidden from active lists.'; },
            button: 'Archive'
        },
        make_admin: {
            title: 'Promote to Administrator?',
            message: function (n) { return n + ' will have full access to the MemPulse Admin dashboard.'; },
            button: 'Promote to Admin'
        },
        make_user: {
            title: 'Demote to regular User?',
            message: function (n) { return n + ' will lose administrator privileges.'; },
            button: 'Change to User'
        }
    };

    function handleSingleAction(action, id, trigger) {
        var u = data.users[id];
        if (!u) return;

        if (action === 'approve' || action === 'restore' || !dialog || !COPY[action]) {
            executePayload({ action: action, user_id: Number(id) }, trigger);
            return;
        }

        pending = { payload: { action: action, user_id: Number(id) }, trigger: trigger };
        document.getElementById('user-action-title').textContent = COPY[action].title;
        document.getElementById('user-action-message').textContent = COPY[action].message(u.name);
        document.getElementById('user-action-confirm').textContent = COPY[action].button;
        showDialogError('');
        dialog.showModal();
    }

    function handleBulkAction(action, trigger) {
        var ids = getSelectedIds();
        if (ids.length === 0) return;

        if (action === 'approve' || action === 'restore' || !dialog) {
            executePayload({ action: action, user_ids: ids }, trigger);
            return;
        }

        var label = ids.length + (ids.length === 1 ? ' selected account' : ' selected accounts');
        pending = { payload: { action: action, user_ids: ids }, trigger: trigger };
        document.getElementById('user-action-title').textContent = COPY[action].title;
        document.getElementById('user-action-message').textContent = COPY[action].message(label);
        document.getElementById('user-action-confirm').textContent = COPY[action].button;
        showDialogError('');
        dialog.showModal();
    }

    if (dialog) {
        document.getElementById('user-action-cancel').addEventListener('click', function () { dialog.close(); });
        document.getElementById('user-action-confirm').addEventListener('click', function () {
            if (pending) executePayload(pending.payload, pending.trigger);
        });
        dialog.addEventListener('close', function () { pending = null; showDialogError(''); });
    }

    // ---- Create User Modal --------------------------------------------------
    var openCreateBtn = document.getElementById('open-create-user-btn');
    var createErrorEl = document.getElementById('create-user-error');

    if (openCreateBtn && createDialog) {
        openCreateBtn.addEventListener('click', function () {
            if (createForm) createForm.reset();
            if (createErrorEl) createErrorEl.classList.add('hidden');
            createDialog.showModal();
        });
        document.getElementById('create-user-close')?.addEventListener('click', function () { createDialog.close(); });
        document.getElementById('create-user-cancel')?.addEventListener('click', function () { createDialog.close(); });

        createForm?.addEventListener('submit', function (e) {
            e.preventDefault();
            if (busy) return;
            var submitBtn = document.getElementById('create-user-submit');
            var payload = {
                action: 'create_user',
                first_name: document.getElementById('cu-first').value.trim(),
                last_name: document.getElementById('cu-last').value.trim(),
                email: document.getElementById('cu-email').value.trim(),
                password: document.getElementById('cu-password').value,
                role: document.getElementById('cu-role').value,
                status: document.getElementById('cu-status').value
            };
            setBusy(true, submitBtn);
            postJson(payload).then(function (r) {
                if (r.ok) {
                    try { sessionStorage.setItem(FLASH_KEY, r.message || 'User created.'); } catch (err) { }
                    window.location.reload();
                    return;
                }
                setBusy(false, submitBtn);
                if (createErrorEl) {
                    createErrorEl.textContent = r.message || 'Could not create user.';
                    createErrorEl.classList.remove('hidden');
                }
            }).catch(function () {
                setBusy(false, submitBtn);
                if (createErrorEl) {
                    createErrorEl.textContent = 'Network error. Please try again.';
                    createErrorEl.classList.remove('hidden');
                }
            });
        });
    }

    // ---- Event Delegation ---------------------------------------------------
    document.addEventListener('click', function (e) {
        var bulkBtn = e.target.closest('[data-bulk-action]');
        if (bulkBtn) {
            e.preventDefault();
            handleBulkAction(bulkBtn.getAttribute('data-bulk-action'), bulkBtn);
            return;
        }

        var actBtn = e.target.closest('[data-action]');
        if (actBtn) {
            e.preventDefault();
            handleSingleAction(
                actBtn.getAttribute('data-action'),
                actBtn.getAttribute('data-user-id') || currentId,
                actBtn
            );
            return;
        }

        var manageBtn = e.target.closest('[data-user-id]');
        if (manageBtn) {
            e.preventDefault();
            openDrawer(manageBtn.getAttribute('data-user-id'), manageBtn);
            return;
        }

        var row = e.target.closest('[data-row-user-id]');
        if (row && !e.target.closest('button, a, input, label, [data-no-row]')) {
            openDrawer(row.getAttribute('data-row-user-id'), row);
        }
    });

    closeBtn?.addEventListener('click', closeDrawer);
    overlay?.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer && !drawer.classList.contains('hidden') && !(dialog && dialog.open)) {
            closeDrawer();
        }
    });
})();