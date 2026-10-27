/**
 * Admin Users: details modal, audit history, single & bulk actions (all behind a
 * confirmation dialog), role promotion/demotion, create-user modal, and tooltips.
 *
 * The table and filter regions are swapped by AJAX (see users-view.php), so nothing
 * here may hold references to elements inside them or to data from the first render:
 *   - per-render data is re-read from #users-data on every `users:refreshed`
 *   - selection and clicks use delegated listeners on `document`
 *   - the bulk bar / select-all are looked up at call time
 */
(function () {
    'use strict';

    var cfg = window.MemPulseAdminUsers || {};
    var ui = window.MemPulseAdminUsersUI || { statusBadge: {}, roleBadge: {} };
    var FLASH_KEY = 'adminUsersFlash';

    // `endpoint` and `csrf` are fixed for the page; `users` and `order` change on every swap.
    var data = {
        users: {},
        order: [],
        endpoint: cfg.endpoint,
        learningEndpoint: cfg.learningEndpoint,
        csrf: cfg.csrf
    };

    function loadTableData() {
        var el = document.getElementById('users-data');
        var parsed = null;
        if (el) {
            try { parsed = JSON.parse(el.textContent); } catch (e) { parsed = null; }
        }
        if (!parsed && cfg.users) parsed = { users: cfg.users, order: cfg.order || [] }; // legacy payload
        data.users = (parsed && parsed.users) || {};
        data.order = ((parsed && parsed.order) || []).map(String);
    }
    loadTableData();

    function getUser(id) {
        return id === null || id === undefined ? null : (data.users[String(id)] || null);
    }

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
    var closeTimer = null;
    var pending = null; // { payload, trigger, ids }
    var learningRequest = null;
    var loadedSessionPage = 0;
    var totalSessionPages = 0;

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

    // A click found no data for its user: say so and re-fetch, never fail silently.
    function staleNotice() {
        toast('User list is out of date. Refreshing\u2026', false);
        if (window.MemPulseUsersLoader) window.MemPulseUsersLoader.reload();
        else window.location.reload();
    }

    // ---- Bulk selection (delegated; the bar and checkboxes are swapped by AJAX) ----
    function rowBoxes() { return document.querySelectorAll('.user-row-checkbox'); }

    function getSelectedIds() {
        var ids = [];
        document.querySelectorAll('.user-row-checkbox:checked').forEach(function (cb) {
            ids.push(Number(cb.value));
        });
        return ids;
    }

    function syncBulkBar() {
        var bar = document.getElementById('bulk-action-bar');
        var countEl = document.getElementById('bulk-selected-count');
        var all = document.getElementById('select-all-users');
        var total = rowBoxes().length;
        var ids = getSelectedIds();

        if (all) {
            all.checked = total > 0 && ids.length === total;
            all.indeterminate = ids.length > 0 && ids.length < total;
        }
        if (!bar) return;
        if (ids.length > 0) {
            bar.classList.remove('hidden');
            bar.classList.add('flex');
            if (countEl) countEl.textContent = String(ids.length);
        } else {
            bar.classList.add('hidden');
            bar.classList.remove('flex');
        }
    }

    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t || !t.matches) return;
        if (t.matches('#select-all-users')) {
            rowBoxes().forEach(function (cb) { cb.checked = t.checked; });
            syncBulkBar();
        } else if (t.matches('.user-row-checkbox')) {
            syncBulkBar();
        }
    });

    // ---- Details modal ------------------------------------------------------
    function field(name) { return drawer ? drawer.querySelector('[data-field="' + name + '"]') : null; }
    function setText(name, value) { var el = field(name); if (el) el.textContent = value; }
    // `value` is the text shown; `key` (optional) is the raw value used to look up the badge colours.
    function setBadge(name, value, map, key) {
        var el = field(name);
        if (!el) return;
        el.textContent = value;
        el.className = (ui.badgeBase || '') + ' ' + ((map || {})[key || value] || '');
    }
    function num(n) { return Number(n || 0).toLocaleString(); }
    function isDrawerOpen() { return !!drawer && !drawer.classList.contains('hidden'); }

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

    function makeLearningElement(tag, className, text) {
        var element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined && text !== null) element.textContent = String(text);
        return element;
    }

    function renderPreferences(preferences) {
        var container = document.getElementById('drawer-preferences');
        if (!container) return;
        container.textContent = '';

        [
            ['Theme', preferences.theme.charAt(0).toUpperCase() + preferences.theme.slice(1)],
            ['Remember multiplier', Number(preferences.rememberMultiplier).toLocaleString() + '×'],
            ['Forgot multiplier', Number(preferences.forgotMultiplier).toLocaleString() + '×'],
            ['Starting interval', preferences.initialIntervalDays + (Number(preferences.initialIntervalDays) === 1 ? ' day' : ' days')],
            ['Maximum interval', Number(preferences.maximumIntervalDays).toLocaleString() + ' days']
        ].forEach(function (item) {
            var card = makeLearningElement('div', 'p-3 rounded-xl bg-surface-container-low border border-outline-variant/30');
            card.appendChild(makeLearningElement('dt', 'text-[10px] font-bold uppercase tracking-wider text-on-surface-variant', item[0]));
            card.appendChild(makeLearningElement('dd', 'text-sm font-bold text-on-surface mt-1', item[1]));
            container.appendChild(card);
        });

        container.classList.remove('hidden');
    }

    function renderDecks(decks) {
        var container = document.getElementById('drawer-decks');
        var count = document.getElementById('drawer-deck-count');
        if (!container || !count) return;
        container.textContent = '';
        count.textContent = decks.length + (decks.length === 1 ? ' deck' : ' decks');

        if (!decks.length) {
            container.appendChild(makeLearningElement('p', 'text-xs text-on-surface-variant italic', 'No decks found.'));
            return;
        }

        decks.forEach(function (deck) {
            var details = makeLearningElement('details', 'rounded-xl border border-outline-variant/30 bg-surface-container-lowest');
            var summary = makeLearningElement('summary', 'flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2.5 text-xs font-semibold text-on-surface');
            var title = makeLearningElement('span', 'min-w-0 truncate', deck.title);
            if (deck.archived) {
                title.appendChild(makeLearningElement('span', 'ml-2 rounded bg-surface-container-high px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-on-surface-variant', 'Archived'));
            }
            summary.appendChild(title);
            summary.appendChild(makeLearningElement('span', 'shrink-0 text-[11px] font-medium text-on-surface-variant', deck.cards.length + (deck.cards.length === 1 ? ' card' : ' cards')));
            details.appendChild(summary);

            var cards = makeLearningElement('div', 'flex flex-col gap-2 border-t border-outline-variant/20 p-3');
            if (!deck.cards.length) {
                cards.appendChild(makeLearningElement('p', 'text-xs text-on-surface-variant italic', 'This deck has no flashcards.'));
            }

            deck.cards.forEach(function (card, index) {
                var cardDetails = makeLearningElement('details', 'rounded-lg border border-outline-variant/20 bg-surface-container-low');
                var cardSummaryText = (card.front || '').replace(/\s+/g, ' ').trim() || 'Empty front';
                var cardSummary = makeLearningElement('summary', 'cursor-pointer px-3 py-2 text-xs font-semibold text-on-surface');
                cardSummary.appendChild(makeLearningElement('span', 'mr-2 text-[10px] text-on-surface-variant', '#' + (index + 1)));
                cardSummary.appendChild(document.createTextNode(cardSummaryText));
                if (card.archived) {
                    cardSummary.appendChild(makeLearningElement('span', 'ml-2 rounded bg-surface-container-high px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-on-surface-variant', 'Archived'));
                }
                cardDetails.appendChild(cardSummary);

                var cardBody = makeLearningElement('div', 'flex flex-col gap-2 border-t border-outline-variant/20 px-3 py-2.5 text-xs');
                [['Front', card.front], ['Back', card.back]].forEach(function (side) {
                    var content = makeLearningElement('div', 'learning-card-content break-words');
                    content.appendChild(makeLearningElement('span', 'mb-1 block text-[10px] font-bold uppercase tracking-wider text-on-surface-variant', side[0]));
                    content.appendChild(makeLearningElement('p', 'm-0 text-on-surface', side[1] || ''));
                    cardBody.appendChild(content);
                });
                cardBody.appendChild(makeLearningElement(
                    'p',
                    'm-0 text-[10px] text-on-surface-variant',
                    'Interval: ' + card.intervalDays + ' days · Reviews: ' + card.reviews +
                    ' · Lapses: ' + card.lapses + ' · Next review: ' + (card.nextReviewAt || '—')
                ));
                cardDetails.appendChild(cardBody);
                cards.appendChild(cardDetails);
            });

            details.appendChild(cards);
            container.appendChild(details);
        });
    }

    function formatDuration(seconds) {
        if (seconds === null || seconds === undefined) return 'In progress';
        var total = Math.max(0, Number(seconds));
        var hours = Math.floor(total / 3600);
        var minutes = Math.floor((total % 3600) / 60);
        var remainingSeconds = total % 60;
        return hours
            ? hours + 'h ' + minutes + 'm'
            : (minutes ? minutes + 'm ' : '') + remainingSeconds + 's';
    }

    function renderSessions(sessions, append) {
        var container = document.getElementById('drawer-sessions');
        var empty = document.getElementById('drawer-sessions-empty');
        if (!container || !empty) return;
        if (!append) container.textContent = '';
        sessions.forEach(function (session) {
            var row = document.createElement('tr');
            [
                session.label,
                session.deck,
                session.startedAt || '—',
                Number(session.cardsReviewed).toLocaleString(),
                session.accuracy + '%',
                formatDuration(session.durationSeconds)
            ].forEach(function (value) {
                row.appendChild(makeLearningElement('td', 'py-2 pr-3 align-top text-on-surface', value));
            });
            container.appendChild(row);
        });
        empty.classList.toggle('hidden', container.children.length > 0);
    }

    function loadLearningData(userId, page, append) {
        if (learningRequest) learningRequest.abort();

        var status = drawer && drawer.querySelector('[data-learning-status]');
        var error = drawer && drawer.querySelector('[data-learning-error]');
        var more = document.getElementById('drawer-sessions-more');
        if (!data.learningEndpoint || !status || !error) {
            if (status) status.textContent = 'Learning data is unavailable.';
            return;
        }

        if (!append) {
            loadedSessionPage = 0;
            totalSessionPages = 0;
            var preferences = document.getElementById('drawer-preferences');
            var decks = document.getElementById('drawer-decks');
            var sessions = document.getElementById('drawer-sessions');
            var deckCount = document.getElementById('drawer-deck-count');
            var sessionCount = document.getElementById('drawer-session-count');
            if (preferences) { preferences.textContent = ''; preferences.classList.add('hidden'); }
            if (decks) decks.textContent = '';
            if (sessions) sessions.textContent = '';
            if (deckCount) deckCount.textContent = '';
            if (sessionCount) sessionCount.textContent = '';
            if (more) more.classList.add('hidden');
        }

        status.textContent = append ? 'Loading older sessions…' : 'Loading user learning data…';
        status.classList.remove('hidden');
        error.textContent = '';
        error.classList.add('hidden');

        var controller = new AbortController();
        learningRequest = controller;
        var url = new URL(data.learningEndpoint, window.location.href);
        url.searchParams.set('user_id', String(userId));
        url.searchParams.set('page', String(page));

        fetch(url.toString(), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            cache: 'no-store',
            signal: controller.signal
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (body) {
                if (!response.ok || body.success !== true) {
                    throw new Error(body.message || 'Unable to load this user’s learning data.');
                }
                return body;
            });
        }).then(function (body) {
            if (String(currentId) !== String(userId)) return;
            if (!append) {
                renderPreferences(body.preferences);
                renderDecks(body.decks || []);
            }
            renderSessions(body.sessions || [], append);
            loadedSessionPage = Number(body.sessionPage) || 1;
            totalSessionPages = Number(body.sessionPages) || 1;
            var sessionCount = document.getElementById('drawer-session-count');
            if (sessionCount) sessionCount.textContent = Number(body.sessionTotal).toLocaleString() + ' sessions';
            if (more) {
                more.classList.toggle('hidden', loadedSessionPage >= totalSessionPages);
                more.disabled = false;
                more.textContent = 'Load older sessions (' + loadedSessionPage + ' of ' + totalSessionPages + ')';
            }
            status.classList.add('hidden');
        }).catch(function (requestError) {
            if (requestError.name === 'AbortError' || String(currentId) !== String(userId)) return;
            status.classList.add('hidden');
            error.textContent = requestError.message || 'Unable to load this user’s learning data. Try again.';
            error.classList.remove('hidden');
            if (more) more.disabled = false;
        }).finally(function () {
            if (learningRequest === controller) learningRequest = null;
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

        // Previous / next through the users currently listed
        var idx = data.order.indexOf(String(currentId));
        setText('position', idx >= 0 ? (idx + 1) + ' of ' + data.order.length : '');
        var prev = drawer.querySelector('[data-nav="prev"]');
        var next = drawer.querySelector('[data-nav="next"]');
        if (prev) prev.disabled = idx <= 0;
        if (next) next.disabled = idx < 0 || idx >= data.order.length - 1;

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
        var u = getUser(id);
        if (!u) { staleNotice(); return; }
        opener = trigger || null;
        currentId = String(id);
        fill(u);
        clearTimeout(closeTimer);
        drawer.classList.remove('hidden');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            drawer.classList.add('is-open');
            overlay.classList.remove('opacity-0');
        });
        var scroller = drawer.querySelector('.overflow-y-auto');
        if (scroller) scroller.scrollTop = 0;
        if (closeBtn) closeBtn.focus();
        loadLearningData(id, 1, false);
    }

    function navigateDrawer(step) {
        var idx = data.order.indexOf(String(currentId));
        var nextId = idx < 0 ? undefined : data.order[idx + step];
        var u = getUser(nextId);
        if (!u) return;
        currentId = String(nextId);
        opener = null;
        fill(u);
        loadLearningData(nextId, 1, false);
        var scroller = drawer.querySelector('.overflow-y-auto');
        if (scroller) scroller.scrollTop = 0;
    }

    function closeDrawer() {
        if (!drawer || !overlay || !isDrawerOpen()) return;
        if (learningRequest) {
            learningRequest.abort();
            learningRequest = null;
        }
        drawer.classList.remove('is-open');
        overlay.classList.add('opacity-0');
        clearTimeout(closeTimer);
        closeTimer = setTimeout(function () {
            drawer.classList.add('hidden');
            overlay.classList.add('hidden');
        }, 200);
        document.body.style.overflow = '';
        if (opener && document.contains(opener) && opener.focus) opener.focus();
        currentId = null;
    }

    // ---- Server requests ----------------------------------------------------
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

    // ---- Confirmation dialog: every action, single or bulk, goes through here ----
    var TONE = {
        primary: { button: 'bg-primary text-on-primary', icon: 'bg-primary/10 text-primary' },
        danger: { button: 'bg-error text-on-error', icon: 'bg-error-container/40 text-error' }
    };
    var BUTTON_BASE = 'inline-flex items-center px-4 py-2 rounded-xl hover:opacity-90 disabled:opacity-50 font-semibold text-sm shadow-sm transition-colors ';
    var ICON_BASE = 'w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ';

    function plural(verbPhrase) {
        return function (n) { return verbPhrase.replace('{n}', n); };
    }

    var COPY = {
        approve: {
            tone: 'primary', icon: 'check_circle', button: 'Approve',
            titleOne: 'Approve this account?', titleMany: plural('Approve {n} accounts?'),
            message: function (s) { return s + ' will be able to sign in and use MemPulse.'; }
        },
        restore: {
            tone: 'primary', icon: 'restore_from_trash', button: 'Restore',
            titleOne: 'Restore this account?', titleMany: plural('Restore {n} accounts?'),
            message: function (s) { return s + ' will be restored.'; }
        },
        reject: {
            tone: 'danger', icon: 'block', button: 'Suspend',
            titleOne: 'Suspend this account?', titleMany: plural('Suspend {n} accounts?'),
            message: function (s) { return s + ' will not be able to sign in.'; }
        },
        delete: {
            tone: 'danger', icon: 'delete', button: 'Archive',
            titleOne: 'Archive this account?', titleMany: plural('Archive {n} accounts?'),
            message: function (s) { return s + ' will be archived and hidden from active lists.'; }
        },
        make_admin: {
            tone: 'primary', icon: 'shield_person', button: 'Promote to Admin',
            titleOne: 'Promote to Administrator?', titleMany: plural('Promote {n} accounts to Administrator?'),
            message: function (s) { return s + ' will have full access to the MemPulse Admin dashboard.'; }
        },
        make_user: {
            tone: 'danger', icon: 'person', button: 'Change to User',
            titleOne: 'Demote to regular User?', titleMany: plural('Demote {n} accounts to regular User?'),
            message: function (s) { return s + ' will lose administrator privileges.'; }
        }
    };

    function renderConfirmList(users) {
        var list = document.getElementById('user-action-list');
        if (!list) return;
        list.textContent = '';
        if (!users || users.length === 0) {
            list.classList.add('hidden');
            list.classList.remove('flex');
            return;
        }
        var MAX = 6;
        users.slice(0, MAX).forEach(function (u) {
            var li = document.createElement('li');
            li.className = 'flex items-center justify-between gap-3';
            var name = document.createElement('span');
            name.className = 'font-semibold truncate';
            name.textContent = u.name;
            var email = document.createElement('span');
            email.className = 'text-on-surface-variant truncate';
            email.textContent = u.email;
            li.appendChild(name);
            li.appendChild(email);
            list.appendChild(li);
        });
        if (users.length > MAX) {
            var more = document.createElement('li');
            more.className = 'text-on-surface-variant';
            more.textContent = '+ ' + (users.length - MAX) + ' more';
            list.appendChild(more);
        }
        list.classList.remove('hidden');
        list.classList.add('flex');
    }

    function askConfirm(action, ids, trigger, bulk) {
        var copy = COPY[action];
        if (!copy || !dialog) { toast('That action is not available.', false); return; }

        var users = ids.map(getUser);
        if (users.some(function (u) { return !u; })) { staleNotice(); return; }

        var n = ids.length;
        var subject = bulk ? n + (n === 1 ? ' selected account' : ' selected accounts') : users[0].name;
        var tone = TONE[copy.tone];

        pending = {
            ids: ids.map(String),
            trigger: trigger,
            payload: bulk
                ? { action: action, user_ids: ids.map(Number) }
                : { action: action, user_id: Number(ids[0]) }
        };

        document.getElementById('user-action-title').textContent = n > 1 ? copy.titleMany(n) : copy.titleOne;
        document.getElementById('user-action-message').textContent = copy.message(subject);
        document.getElementById('user-action-icon').textContent = copy.icon;
        document.getElementById('user-action-icon-wrap').className = ICON_BASE + tone.icon;
        var confirmBtn = document.getElementById('user-action-confirm');
        confirmBtn.textContent = copy.button;
        confirmBtn.className = BUTTON_BASE + tone.button;
        confirmBtn.disabled = false;
        renderConfirmList(bulk ? users : null);
        showDialogError('');
        hideTip();
        dialog.showModal();
        // Destructive actions start on Cancel so Enter can't confirm by accident.
        (copy.tone === 'danger' ? document.getElementById('user-action-cancel') : confirmBtn).focus();
    }

    function handleSingleAction(action, id, trigger) {
        if (!getUser(id)) { staleNotice(); return; }
        askConfirm(action, [id], trigger, false);
    }

    function handleBulkAction(action, trigger) {
        var ids = getSelectedIds();
        if (ids.length === 0) return;
        askConfirm(action, ids, trigger, true);
    }

    if (dialog) {
        document.getElementById('user-action-cancel').addEventListener('click', function () { if (!busy) dialog.close(); });
        document.getElementById('user-action-confirm').addEventListener('click', function () {
            if (pending && !busy) executePayload(pending.payload, pending.trigger);
        });
        // Click on the backdrop (the <dialog> itself, outside its content) cancels.
        dialog.addEventListener('click', function (e) { if (e.target === dialog && !busy) dialog.close(); });
        dialog.addEventListener('close', function () { pending = null; showDialogError(''); });
    }

    // ---- Create User modal --------------------------------------------------
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

    // ---- Tooltips (delegated: work on swapped-in rows, hover, keyboard focus, touch) ----
    var TIP_TARGETS = '[data-tip],[title],button[aria-label],a[aria-label],input[aria-label]';
    var tip = null;
    var tipTarget = null;
    var tipShowTimer = null;
    var tipHideTimer = null;
    var tabbed = false;

    function ensureTip() {
        if (tip) return tip;
        var st = document.createElement('style');
        st.textContent =
            '.mp-tip{position:fixed;left:0;top:0;z-index:110;max-width:16rem;padding:6px 10px;border-radius:8px;' +
            'background:#0f172a;color:#fff;font-size:12px;font-weight:600;line-height:1.35;pointer-events:none;' +
            'opacity:0;transition:opacity .12s ease-out}.mp-tip.is-on{opacity:1}' +
            '@media (prefers-reduced-motion:reduce){.mp-tip{transition:none}}';
        document.head.appendChild(st);
        tip = document.createElement('div');
        tip.className = 'mp-tip';
        tip.setAttribute('role', 'tooltip');
        document.body.appendChild(tip);
        return tip;
    }

    function tipTargetFor(node) {
        if (!node || !node.closest) return null;
        var el = node.closest(TIP_TARGETS);
        if (!el || el.closest('dialog')) return null; // dialogs sit in the top layer, above the tooltip
        if (el.hasAttribute('title')) {               // take over the slow native tooltip
            if (!el.hasAttribute('data-tip')) el.setAttribute('data-tip', el.getAttribute('title'));
            el.removeAttribute('title');
        }
        return el;
    }

    function showTip(el) {
        var text = el.getAttribute('data-tip') || el.getAttribute('aria-label') || '';
        if (!text || !document.contains(el)) return;
        var t = ensureTip();
        tipTarget = el;
        t.classList.remove('is-on');
        t.textContent = text;
        t.style.left = '0px';
        t.style.top = '0px';
        var r = el.getBoundingClientRect();
        var w = t.offsetWidth;
        var h = t.offsetHeight;
        var left = Math.max(8, Math.min(r.left + r.width / 2 - w / 2, window.innerWidth - w - 8));
        var top = r.top - h - 8;
        if (top < 8) top = r.bottom + 8;
        t.style.left = left + 'px';
        t.style.top = top + 'px';
        t.classList.add('is-on');
    }

    function hideTip() {
        clearTimeout(tipShowTimer);
        clearTimeout(tipHideTimer);
        tipTarget = null;
        if (tip) tip.classList.remove('is-on');
    }

    document.addEventListener('pointerover', function (e) {
        if (e.pointerType === 'touch') return;
        var el = tipTargetFor(e.target);
        if (!el || el === tipTarget) return;
        hideTip();
        tipShowTimer = setTimeout(function () { showTip(el); }, 300);
    });
    document.addEventListener('pointerout', function (e) {
        if (e.pointerType === 'touch') return;
        if (tipTarget && e.relatedTarget && tipTarget.contains(e.relatedTarget)) return;
        hideTip();
    });
    document.addEventListener('pointerdown', function () { tabbed = false; });
    document.addEventListener('focusin', function (e) {
        if (!tabbed) return; // only for keyboard navigation, not programmatic focus
        var el = tipTargetFor(e.target);
        if (!el) return;
        hideTip();
        showTip(el);
    });
    document.addEventListener('focusout', hideTip);
    window.addEventListener('scroll', hideTip, true);
    window.addEventListener('resize', hideTip);

    // Touch: long-press shows the tip briefly
    document.addEventListener('touchstart', function (e) {
        var el = tipTargetFor(e.target);
        hideTip();
        if (el) tipShowTimer = setTimeout(function () { showTip(el); }, 450);
    }, { passive: true });
    document.addEventListener('touchend', function () {
        clearTimeout(tipShowTimer);
        if (tipTarget) tipHideTimer = setTimeout(hideTip, 1600);
    }, { passive: true });
    document.addEventListener('touchmove', hideTip, { passive: true });

    // ---- Event delegation ---------------------------------------------------
    document.addEventListener('click', function (e) {
        hideTip();

        var sessionsMore = e.target.closest('#drawer-sessions-more');
        if (sessionsMore && currentId && loadedSessionPage < totalSessionPages) {
            e.preventDefault();
            sessionsMore.disabled = true;
            loadLearningData(currentId, loadedSessionPage + 1, true);
            return;
        }

        var bulkBtn = e.target.closest('[data-bulk-action]');
        if (bulkBtn) {
            e.preventDefault();
            handleBulkAction(bulkBtn.getAttribute('data-bulk-action'), bulkBtn);
            return;
        }

        var navBtn = e.target.closest('[data-nav]');
        if (navBtn && drawer && drawer.contains(navBtn)) {
            e.preventDefault();
            navigateDrawer(navBtn.getAttribute('data-nav') === 'prev' ? -1 : 1);
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
        if (e.key === 'Tab') tabbed = true;
        if (e.key === 'Escape') hideTip();

        var modalBlocked = (dialog && dialog.open) || (createDialog && createDialog.open);
        if (!isDrawerOpen() || modalBlocked) return;

        if (e.key === 'Escape') {
            closeDrawer();
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            navigateDrawer(e.key === 'ArrowLeft' ? -1 : 1);
        } else if (e.key === 'Tab') {
            // Keep keyboard focus inside the modal
            var focusable = Array.prototype.filter.call(
                drawer.querySelectorAll('button, summary, [href], input, select, [tabindex]:not([tabindex="-1"])'),
                function (el) { return !el.disabled && el.offsetParent !== null; }
            );
            if (focusable.length === 0) return;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (e.shiftKey && (document.activeElement === first || !drawer.contains(document.activeElement))) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && (document.activeElement === last || !drawer.contains(document.activeElement))) {
                e.preventDefault();
                first.focus();
            }
        }
    });

    // ---- After every AJAX swap of #users-filter / #users-table ---------------
    document.addEventListener('users:refreshed', function () {
        loadTableData();
        hideTip();
        syncBulkBar(); // swapped-in rows start unchecked; this also resets select-all

        if (isDrawerOpen()) {
            var u = getUser(currentId);
            if (u) {
                fill(u);
            } else {
                closeDrawer();
                toast('That user is no longer in this list.', false);
            }
        }
        if (dialog && dialog.open && pending && pending.ids.some(function (id) { return !getUser(id); })) {
            dialog.close();
            toast('The list changed, so the action was cancelled.', false);
        }
    });

    syncBulkBar();
})();