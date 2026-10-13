/**
 * account-settings.js  (put in app/assets/js/)
 *
 * Runs the Appearance, Profile and Security tabs of the Settings modal.
 * Loaded by includes/sidebar.php.
 */
(() => {
    'use strict';

    /* ---------------------------------------------------------------
       Theme: light | dark | system
       --------------------------------------------------------------- */
    const THEME_KEY = 'mempulseTheme';
    const THEMES = ['light', 'dark', 'system'];
    const root = document.documentElement;
    const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

    function readStoredTheme() {
        try {
            const value = localStorage.getItem(THEME_KEY);
            return THEMES.includes(value) ? value : 'system';
        } catch (e) {
            return 'system';
        }
    }

    function storeTheme(pref) {
        try { localStorage.setItem(THEME_KEY, pref); } catch (e) { /* storage blocked */ }
    }

    function applyTheme(pref) {
        const isDark = pref === 'dark' || (pref === 'system' && darkQuery.matches);
        root.classList.toggle('dark', isDark);
        root.dataset.themePref = pref;
        root.style.colorScheme = isDark ? 'dark' : 'light';
    }

    let currentTheme = readStoredTheme();
    applyTheme(currentTheme);

    // "System" follows the device live, e.g. when the OS switches at sunset.
    darkQuery.addEventListener('change', () => {
        if (currentTheme === 'system') applyTheme('system');
    });

    /* ---------------------------------------------------------------
       Settings modal
       --------------------------------------------------------------- */
    function init() {
        const dialog = document.getElementById('settings-dialog');
        if (!dialog) return;

        const api = dialog.dataset.accountApi;
        const csrf = dialog.dataset.csrf;
        const $ = (id) => document.getElementById(id);

        // The account's saved theme wins (matters on a device you haven't used before).
        const savedTheme = dialog.dataset.theme;
        if (THEMES.includes(savedTheme) && savedTheme !== currentTheme) {
            currentTheme = savedTheme;
            storeTheme(currentTheme);
            applyTheme(currentTheme);
        }

        /* ---- shared helpers ---- */
        function showStatus(el, message, isError = false) {
            if (!el) return;
            el.textContent = message;
            el.classList.toggle('text-error', isError);
            el.classList.toggle('text-primary', !isError);
        }

        async function request(options = {}) {
            const response = await fetch(api, { credentials: 'same-origin', ...options });
            let result = null;
            try { result = await response.json(); } catch (e) { /* not JSON */ }

            if (!response.ok || !result || !result.success) {
                throw new Error((result && result.message) || 'Something went wrong. Please try again.');
            }
            return result;
        }

        const post = (payload) => request({
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify(payload)
        });

        function formatDate(value) {
            const date = new Date(String(value).replace(' ', 'T'));
            return Number.isNaN(date.getTime())
                ? ''
                : date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
        }

        /* ---- Appearance ---- */
        const themeRadios = dialog.querySelectorAll('input[name="appearance"]');
        const appearanceStatus = $('appearance-status');

        themeRadios.forEach((radio) => { radio.checked = radio.value === currentTheme; });

        themeRadios.forEach((radio) => {
            radio.addEventListener('change', async () => {
                if (!radio.checked) return;

                currentTheme = radio.value;
                storeTheme(currentTheme);
                applyTheme(currentTheme);          // instant, no waiting for the server

                showStatus(appearanceStatus, 'Saving...');
                try {
                    await post({ action: 'update_theme', theme: currentTheme });
                    showStatus(appearanceStatus, 'Appearance saved.');
                } catch (error) {
                    showStatus(appearanceStatus,
                        'Applied on this device, but not saved to your account: ' + error.message, true);
                }
            });
        });

        /* ---- Profile ---- */
        const profileForm = $('profile-form');
        const firstName = $('profile-first-name');
        const lastName = $('profile-last-name');
        const email = $('profile-email');
        const emailPasswordWrap = $('profile-password-wrap');
        const emailPassword = $('profile-password');
        const profileMeta = $('profile-meta');
        const profileStatus = $('profile-status');
        const profileSave = $('profile-save');

        const passwordMeta = $('pw-meta');
        let account = null;

        function updatePasswordMeta(data) {
            if (!passwordMeta) return;
            const when = data.password_changed_at ? formatDate(data.password_changed_at) : '';
            passwordMeta.textContent = when
                ? 'Password last changed on ' + when + '.'
                : 'Your password has not been changed since you signed up.';
        }

        function fillAccount(data) {
            account = data;

            firstName.value = data.first_name;
            lastName.value = data.last_name;
            email.value = data.email;

            emailPassword.value = '';
            emailPassword.required = false;
            emailPasswordWrap.classList.add('hidden');

            const since = data.member_since ? formatDate(data.member_since) : '';
            profileMeta.textContent = since ? 'Member since ' + since + '.' : '';

            updatePasswordMeta(data);

            // Keep the sidebar name in sync without a page reload.
            const fullName = (data.first_name + ' ' + data.last_name).trim();
            document.querySelectorAll('[data-user-name]').forEach((el) => { el.textContent = fullName; });
        }

        async function loadAccount() {
            try {
                const result = await request();
                fillAccount(result.account);
            } catch (error) {
                showStatus(profileStatus, error.message, true);
            }
        }

        // Ask for the password only when the email address was actually changed.
        email.addEventListener('input', () => {
            const changed = !!account &&
                email.value.trim().toLowerCase() !== account.email.toLowerCase();
            emailPasswordWrap.classList.toggle('hidden', !changed);
            emailPassword.required = changed;
            if (!changed) emailPassword.value = '';
        });

        profileForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!profileForm.checkValidity()) {
                profileForm.reportValidity();
                return;
            }

            profileSave.disabled = true;
            showStatus(profileStatus, 'Saving...');
            try {
                const result = await post({
                    action: 'update_profile',
                    first_name: firstName.value,
                    last_name: lastName.value,
                    email: email.value,
                    current_password: emailPassword.value
                });
                fillAccount(result.account);
                showStatus(profileStatus, result.message);
            } catch (error) {
                showStatus(profileStatus, error.message, true);
            } finally {
                profileSave.disabled = false;
            }
        });

        /* ---- Security: change password ---- */
        const passwordForm = $('password-form');
        const pwCurrent = $('pw-current');
        const pwNew = $('pw-new');
        const pwConfirm = $('pw-confirm');
        const pwShow = $('pw-show');
        const pwStatus = $('pw-status');
        const pwSave = $('pw-save');
        const pwFields = [pwCurrent, pwNew, pwConfirm];

        pwShow.addEventListener('change', () => {
            pwFields.forEach((input) => { input.type = pwShow.checked ? 'text' : 'password'; });
        });

        passwordForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (pwNew.value.length < 8) {
                showStatus(pwStatus, 'Use at least 8 characters for the new password.', true);
                pwNew.focus();
                return;
            }
            if (new TextEncoder().encode(pwNew.value).length > 72) {
                showStatus(pwStatus, 'The new password is too long. Use at most 72 bytes.', true);
                pwNew.focus();
                return;
            }
            if (pwNew.value !== pwConfirm.value) {
                showStatus(pwStatus, 'The new passwords do not match.', true);
                pwConfirm.focus();
                return;
            }

            pwSave.disabled = true;
            showStatus(pwStatus, 'Changing password...');
            try {
                const result = await post({
                    action: 'change_password',
                    current_password: pwCurrent.value,
                    new_password: pwNew.value,
                    confirm_password: pwConfirm.value
                });
                resetPasswordForm();
                updatePasswordMeta(result.account);
                account = account ? { ...account, ...result.account } : result.account;
                showStatus(pwStatus, result.message);
            } catch (error) {
                showStatus(pwStatus, error.message, true);
            } finally {
                pwSave.disabled = false;
            }
        });

        function resetPasswordForm() {
            passwordForm.reset();
            pwFields.forEach((input) => { input.type = 'password'; });
        }

        /* ---- open / close ---- */
        document.querySelectorAll('[data-open-settings]').forEach((button) => {
            button.addEventListener('click', loadAccount);
        });

        dialog.addEventListener('close', () => {
            resetPasswordForm();
            [appearanceStatus, profileStatus, pwStatus].forEach((el) => showStatus(el, ''));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();