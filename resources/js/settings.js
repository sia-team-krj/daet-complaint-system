// The settings page (docs/design-system.md §5.45).
//
// Binds to [data-settings-form] and does nothing on any other page.
//
// Two things live here. The save row: a form that has changed says so, in
// place, and offers a discard — no sticky bar over the page and no browser
// "leave site?" prompt, because a resident who typed something and wandered
// off should not be chased by the browser for it. And the live name rule:
// the same one the registration form enforces, so a name that can be created
// at sign-up is the same name that can be set later.

const NAME_PATTERN = /^[\p{L}\s\-.]+$/u;

const initializeNameField = (input) => {
    if (input.dataset.nameReady === 'true') {
        return;
    }

    input.dataset.nameReady = 'true';

    const field = input.closest('.settings-field');
    const error = field?.querySelector('[data-name-error]');

    if (!error) {
        return;
    }

    const message = input.dataset.nameMessage || error.textContent;

    const check = () => {
        const value = input.value.trim();

        if (value === '') {
            // The server answers the empty case; this is only about the rule.
            error.hidden = true;
            input.removeAttribute('aria-invalid');
            return;
        }

        const valid = NAME_PATTERN.test(value);

        error.hidden = valid;
        error.textContent = message;

        if (valid) {
            input.removeAttribute('aria-invalid');
        } else {
            input.setAttribute('aria-invalid', 'true');
        }
    };

    input.addEventListener('input', check);
    input.addEventListener('blur', check);

    check();
};

const initializeSaveRows = () => {
    document.querySelectorAll('[data-settings-form]').forEach((form) => {
        if (form.dataset.saveReady === 'true') {
            return;
        }

        form.dataset.saveReady = 'true';

        const row = form.closest('.settings-card')?.querySelector('.settings-save-row');
        const state = row?.querySelector('[data-save-state]');
        const discard = row?.querySelector('[data-discard]');
        const button = form.querySelector('[data-save-button]');
        const initial = new FormData(form);

        const changed = () => {
            const current = new FormData(form);

            for (const [key, value] of current.entries()) {
                if (initial.get(key) !== value) {
                    return true;
                }
            }

            for (const key of initial.keys()) {
                if (!current.has(key)) {
                    return true;
                }
            }

            return false;
        };

        const markDirty = () => {
            const dirty = changed();

            form.closest('.settings-card')?.classList.toggle('is-dirty', dirty);

            if (discard) {
                discard.hidden = !dirty;
            }

            if (state && dirty) {
                state.textContent = state.dataset.dirtyMessage || '';
                state.classList.add('is-dirty');
            }
        };

        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);

        form.addEventListener('submit', () => {
            if (button) {
                button.classList.add('is-loading');
                button.disabled = true;
            }
        });

        if (discard) {
            discard.addEventListener('click', () => {
                form.reset();
                markDirty();
            });
        }
    });
};

const initializePasswordToggles = () => {
    document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
        if (btn.dataset.toggleReady === 'true') {
            return;
        }

        btn.dataset.toggleReady = 'true';

        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.getAttribute('data-password-toggle'));

            if (!input) {
                return;
            }

            const showing = input.type === 'text';

            input.type = showing ? 'password' : 'text';
            btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
            btn.textContent = showing ? btn.dataset.labelShow || 'Show' : btn.dataset.labelHide || 'Hide';
            input.focus();
        });
    });
};

const initializeSessionList = () => {
    const button = document.getElementById('settings-show-more');

    if (!button || button.dataset.ready === 'true') {
        return;
    }

    button.dataset.ready = 'true';

    button.addEventListener('click', () => {
        document.querySelectorAll('[data-session-extra]').forEach((el) => {
            el.hidden = false;
        });
        button.hidden = true;
    });
};

const initializeLogoutDialog = () => {
    const button = document.getElementById('logout-other-btn');
    const dialog = document.getElementById('logout-other-dialog');

    if (!button || !dialog) {
        return;
    }

    button.addEventListener('click', () => {
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else if (window.confirm('Log out other devices?')) {
            document.getElementById('logout-other-form').submit();
        }
    });
};

const initializePreferences = () => {
    const form = document.getElementById('preferences-form');

    if (!form || form.dataset.ready === 'true') {
        return;
    }

    form.dataset.ready = 'true';

    const state = form.parentElement?.querySelector('[data-save-state]');

    form.querySelectorAll('[data-preference-field]').forEach((field) => {
        field.addEventListener('change', () => {
            const data = new FormData(form);
            const token = form.querySelector('input[name="_token"]')?.value || '';

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: data,
            })
                .then((response) => response.json())
                .then((json) => {
                    if (state) {
                        state.textContent = json.message || 'Saved.';
                        state.classList.remove('is-dirty');
                    }

                    if (field.name === 'locale') {
                        try {
                            localStorage.setItem('daet_locale', field.value);
                        } catch (error) {
                            // A browser that will not store the preference still
                            // gets the new language from the response.
                        }
                        document.documentElement.lang = field.value;
                        setTimeout(() => window.location.reload(), 500);
                    }

                    if (field.name === 'text_size') {
                        document.body.classList.toggle('text-size-large', field.value === 'large');
                    }
                })
                .catch(() => form.submit());
        });
    });
};

const initializeTabs = () => {
    const tabs = Array.from(document.querySelectorAll('[data-settings-tab]'));

    if (tabs.length === 0) {
        return;
    }

    // The tab you are on is scrolled into view on a phone, where the strip
    // scrolls sideways.
    const active = document.querySelector('.settings-tab.is-active');

    if (active) {
        try {
            active.scrollIntoView({ block: 'nearest', inline: 'center' });
        } catch (error) {
            // A browser without scrollIntoView options is not a reason to
            // break the page.
        }
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                (tabs[index + 1] || tabs[0]).focus();
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                (tabs[index - 1] || tabs[tabs.length - 1]).focus();
            }
        });
    });
};

export const initializeSettings = () => {
    document.querySelectorAll('[data-name-field]').forEach(initializeNameField);
    initializeTabs();
    initializeSaveRows();
    initializePasswordToggles();
    initializeSessionList();
    initializeLogoutDialog();
    initializePreferences();
};
