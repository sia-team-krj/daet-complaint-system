import "./bootstrap";
// Bundles Leaflet and exposes window.whenLeafletReady, so no page has to fetch a
// map library from a CDN. Imported here because both layouts already load this
// bundle, which means the library is present on every page that might need it
// and on no page that does not.
import "./leaflet";
// The staff proof-capture picker (docs/design-system.md §5.36). Staff-only:
// it binds to [data-proof-capture] and does nothing on any resident page.
import { initializeProofCaptures } from "./proof-capture";
// The live queue (docs/design-system.md §5.36). Staff- and admin-only: it
// binds to [data-live-queue] and does nothing on a resident page.
import { initializeLiveQueues } from "./live-queue";
// The live ticket panel (docs/design-system.md §5.39). Staff- and admin-only:
// it binds to [data-live-ticket] and never replaces the page's form.
import { initializeLiveTickets } from "./live-ticket";

const normalizeSearchValue = (value) => value.trim().toLocaleLowerCase();

const initializePasswordRequirements = () => {
    document.querySelectorAll('[data-password-requirements]').forEach((requirements) => {
        const input = document.getElementById(requirements.dataset.passwordTarget);
        if (!input || requirements.dataset.initialized === 'true') return;

        requirements.dataset.initialized = 'true';
        const checks = {
            length: requirements.querySelector('[data-password-requirement="length"]'),
            uppercase: requirements.querySelector('[data-password-requirement="uppercase"]'),
            lowercase: requirements.querySelector('[data-password-requirement="lowercase"]'),
            number: requirements.querySelector('[data-password-requirement="number"]'),
        };

        const update = () => {
            const value = input.value;
            const results = {
                length: value.length >= 8,
                uppercase: /[A-Z]/.test(value),
                lowercase: /[a-z]/.test(value),
                number: /[0-9]/.test(value),
            };

            Object.entries(checks).forEach(([key, element]) => {
                if (!element) return;
                element.classList.toggle('is-met', results[key]);
                element.setAttribute('data-met', results[key] ? 'true' : 'false');
            });

            requirements.dataset.complete = String(Object.values(results).every(Boolean));
        };

        input.addEventListener('input', update);
        input.addEventListener('change', update);
        update();
    });
};

// Password visibility toggles (docs/design-system.md §5.41). Binds to
// [data-password-visibility-toggle] — the eye button inside .field-wrap on the
// auth password fields. The button stays hidden until the input has text; from
// then on it flips the input between type="password" and type="text", and the
// stacked eye/eye-off SVGs cross-fade via aria-pressed (CSS). Emptied fields
// re-mask automatically. The localized label (data-label-show / data-label-hide)
// and tabindex follow the same state.
const initializePasswordToggles = () => {
    document.querySelectorAll('[data-password-visibility-toggle]').forEach((btn) => {
        if (btn.dataset.initialized === 'true') return;
        const input = document.getElementById(btn.getAttribute('data-password-visibility-toggle'));
        if (!input) return;
        btn.dataset.initialized = 'true';

        const sync = () => {
            const hasValue = input.value.length > 0;
            if (!hasValue && input.type !== 'password') input.type = 'password';
            if (!hasValue && btn.getAttribute('aria-pressed') === 'true') {
                btn.setAttribute('aria-pressed', 'false');
                btn.setAttribute('aria-label', btn.dataset.labelShow || 'Show password');
            }
            btn.classList.toggle('is-visible', hasValue);
            btn.tabIndex = hasValue ? 0 : -1;
        };

        input.addEventListener('input', sync);
        sync();

        btn.addEventListener('click', () => {
            const revealing = input.type === 'password';
            input.type = revealing ? 'text' : 'password';
            btn.setAttribute('aria-pressed', String(revealing));
            btn.setAttribute('aria-label', revealing
                ? (btn.dataset.labelHide || 'Hide password')
                : (btn.dataset.labelShow || 'Show password'));

            input.focus({ preventScroll: true });
        });
    });
};

// Registration live validation (docs/design-system.md §12 · 2026-11-10).
// Flashes per-field errors while typing — never for the email field: email
// stays submit-only so this form cannot be used as an oracle for whether an
// address is registered (account enumeration). The inline rules mirror the
// server ones (AuthController@register) exactly; this is UX, the server stays
// authoritative and still re-validates everything. A field only shows its
// error after it has been touched (blur) — typing into a pristine field never
// yells at you. The barangay allow-list is read from the suggestions already
// rendered in the DOM, so there is no second list to drift.
const initializeRegisterLiveValidation = () => {
    const form = document.querySelector('form[data-live-validate]');
    if (!form || form.dataset.liveValidationBound === 'true') return;
    form.dataset.liveValidationBound = 'true';

    const msg = {
        required: form.dataset.msgRequired || 'This field is required.',
        firstNameRegex: form.dataset.msgFirstNameRegex,
        lastNameRegex: form.dataset.msgLastNameRegex,
        phoneRegex: form.dataset.msgPhoneRegex,
        barangayInvalid: form.dataset.msgBarangayInvalid,
        passwordMin8: form.dataset.msgPasswordMin8,
        passwordConfirmed: form.dataset.msgPasswordConfirmed,
        termsAccepted: form.dataset.msgTermsAccepted,
    };
    const barangayOptions = Array.from(form.querySelectorAll('#barangay-suggestions [data-search-value]'))
        .map((li) => li.dataset.searchValue);
    const passwordInput = form.querySelector('[name="password"]');
    const touched = new Set();

    const namePattern = /^[\p{L}\s\-.]+$/u;

    const validators = {
        first_name: (value) => {
            const v = value.trim();
            if (!v) return msg.required;
            return namePattern.test(v) ? null : msg.firstNameRegex;
        },
        last_name: (value) => {
            const v = value.trim();
            if (!v) return msg.required;
            return namePattern.test(v) ? null : msg.lastNameRegex;
        },
        // NOTE: no email validator here on purpose — see the block comment.
        phone: (value) => {
            const v = value.trim();
            if (!v) return null; // nullable server-side
            return /^09\d{9}$/.test(v) ? null : msg.phoneRegex;
        },
        barangay: (value) => {
            const v = value.trim();
            if (!v) return null; // nullable server-side
            return barangayOptions.includes(v) ? null : msg.barangayInvalid;
        },
        password: (value) => {
            if (!value) return msg.required;
            const ok = value.length >= 8 && /[a-z]/.test(value) && /[A-Z]/.test(value) && /\d/.test(value);
            return ok ? null : msg.passwordMin8;
        },
        password_confirmation: (value) => {
            if (!value) return msg.required;
            return passwordInput && value !== passwordInput.value ? msg.passwordConfirmed : null;
        },
        terms: (_value, input) => (input.checked ? null : msg.termsAccepted),
    };

    const setError = (input, message) => {
        const error = form.querySelector(`[data-error-for="${input.name}"]`);
        if (!error) return;
        const invalid = Boolean(message);
        error.textContent = message || '';
        error.hidden = !invalid;
        input.setAttribute('aria-invalid', invalid ? 'true' : 'false');
        input.closest('.field-group')?.classList.toggle('field-group--invalid', invalid);
    };

    const validate = (input) => {
        const validator = validators[input.name];
        if (!validator) return true;
        const message = validator(input.value, input);
        setError(input, touched.has(input.name) ? message : null);
        return !message;
    };

    Object.keys(validators).forEach((name) => {
        const input = form.querySelector(`[name="${name}"]`);
        if (!input) return;

        input.addEventListener('input', () => {
            validate(input);
            if (name === 'password' && touched.has('password_confirmation')) {
                validate(form.querySelector('[name="password_confirmation"]'));
            }
        });
        input.addEventListener('focusout', () => {
            touched.add(name);
            validate(input);
        });
        input.addEventListener('change', () => {
            if (input.type === 'checkbox') touched.add(name);
            validate(input);
        });
    });

    form.addEventListener('submit', () => {
        Object.keys(validators).forEach((name) => touched.add(name));
        Object.keys(validators).forEach((name) => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) validate(input);
        });
        // Never preventDefault: native constraints + the server stay authoritative.
    });
};

const initializeBarangaySearch = () => {
    document.querySelectorAll('[data-barangay-search]').forEach((search) => {
        const input = search.querySelector('[data-barangay-input]');
        const list = document.getElementById(search.dataset.suggestions);
        const status = document.getElementById(search.dataset.status);

        if (!input || !list || !status || search.dataset.initialized === 'true') return;

        search.dataset.initialized = 'true';
        const options = Array.from(list.querySelectorAll('[role="option"]'));
        let activeIndex = -1;

        const visibleOptions = () => options.filter((option) => !option.hidden);

        const close = () => {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            options.forEach((option) => option.setAttribute('aria-selected', 'false'));
            activeIndex = -1;
        };

        const setActiveOption = (option) => {
            options.forEach((candidate) => {
                const isActive = candidate === option;
                candidate.classList.toggle('is-active', isActive);
                candidate.setAttribute('aria-selected', String(isActive));
            });

            activeIndex = option ? visibleOptions().indexOf(option) : -1;
            if (option) {
                input.setAttribute('aria-activedescendant', option.id);

                const listBounds = list.getBoundingClientRect();
                const optionBounds = option.getBoundingClientRect();
                if (optionBounds.top < listBounds.top) {
                    list.scrollTop -= listBounds.top - optionBounds.top;
                } else if (optionBounds.bottom > listBounds.bottom) {
                    list.scrollTop += optionBounds.bottom - listBounds.bottom;
                }
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        };

        const selectOption = (option) => {
            if (!option) return;

            input.value = option.dataset.searchValue;
            search.dataset.selected = 'true';
            input.focus();
            close();
            status.textContent = `${input.value} selected.`;
        };

        const filterOptions = () => {
            const query = normalizeSearchValue(input.value);
            let matchCount = 0;

            options.forEach((option) => {
                const matches = query.length > 0
                    && normalizeSearchValue(option.dataset.searchValue).includes(query);
                option.hidden = !matches;
                option.classList.remove('is-active');
                option.setAttribute('aria-selected', 'false');
                if (matches) matchCount += 1;
            });

            setActiveOption(null);

            if (query.length === 0) {
                close();
                status.textContent = 'Type a barangay to see suggestions.';
                return;
            }

            if (matchCount === 0) {
                close();
                status.textContent = 'No matching barangay found.';
                return;
            }

            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            status.textContent = `${matchCount} suggestion${matchCount === 1 ? '' : 's'} available. Use the arrow keys to choose.`;
        };

        input.addEventListener('input', () => {
            search.dataset.selected = 'false';
            filterOptions();
        });
        input.addEventListener('change', filterOptions);
        input.addEventListener('search', filterOptions);
        input.addEventListener('focus', () => {
            if (input.value.trim()) filterOptions();
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
                return;
            }

            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
                if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    selectOption(visibleOptions()[activeIndex]);
                }
                return;
            }

            event.preventDefault();
            if (list.hidden) filterOptions();
            const matches = visibleOptions();
            if (matches.length === 0) return;

            const currentIndex = matches.findIndex(
                (option) => option.id === input.getAttribute('aria-activedescendant'),
            );
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            const nextIndex = currentIndex < 0
                ? (direction === 1 ? 0 : matches.length - 1)
                : (currentIndex + direction + matches.length) % matches.length;

            setActiveOption(matches[nextIndex]);
        });

        list.addEventListener('pointerdown', (event) => {
            const target = event.target instanceof Element ? event.target : null;
            const option = target?.closest('[role="option"]');
            if (!option || option.hidden) return;

            event.preventDefault();
            selectOption(option);
        });

        search.addEventListener('focusout', () => {
            window.requestAnimationFrame(() => {
                if (!search.contains(document.activeElement)) close();
            });
        });

        filterOptions();
    });
};

// Shared busy/loading state for action buttons (docs/design-system.md §5.31).
// Prevents double submits, shows the `.is-loading` spinner (currentColor), and
// restores on bfcache return. Opt-in: forms with `data-loading-submit` or the
// legacy `data-disable-on-submit`; plus all non-GET method forms by default
// (state-changing). Opt-out: `data-loading="off"` or `data-loading-managed`.
const setActionBusy = (el, busy) => {
    if (!el) return;
    if (busy) {
        el.classList.add('is-loading');
        el.setAttribute('aria-busy', 'true');
        el.dataset.actionBusy = 'true';
        if ('disabled' in el) el.disabled = true;
        else el.setAttribute('aria-disabled', 'true');
    } else {
        el.classList.remove('is-loading');
        el.removeAttribute('aria-busy');
        delete el.dataset.actionBusy;
        if ('disabled' in el) el.disabled = false;
        el.removeAttribute('aria-disabled');
    }
};
window.daetActionLoading = { setBusy: setActionBusy };

const resolveSubmitter = (form, event) => {
    if (event && event.submitter) return event.submitter;
    const active = document.activeElement;
    if (active && form.contains(active) && (active.type === 'submit' || active.getAttribute('type') === 'submit')) return active;
    return form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');
};

const initializeSubmitGuards = () => {
    const clearBusy = (root) => {
        (root || document).querySelectorAll('[data-action-busy="true"]').forEach((el) => setActionBusy(el, false));
    };

    if (window.__daetActionBusyPageshow !== true) {
        window.__daetActionBusyPageshow = true;
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) clearBusy(document);
        });
    }

    document.querySelectorAll('form').forEach((form) => {
        if (form.dataset.actionLoadingBound === 'true') return;
        const method = (form.getAttribute('method') || 'get').toLowerCase();
        const explicit = form.hasAttribute('data-loading-submit') || form.hasAttribute('data-disable-on-submit');
        const managed = form.getAttribute('data-loading') === 'off' || form.hasAttribute('data-loading-managed') || form.hasAttribute('data-preferences-autosave');
        if (managed) return;
        if (!explicit && method === 'get') return;
        if (!explicit && method !== 'get' && form.closest('[data-no-action-loading]')) return;
        form.dataset.actionLoadingBound = 'true';

        // Legacy single-use guard behavior: keep form reset on bfcache restore.
        if (form.hasAttribute('data-disable-on-submit') && form.dataset.submitGuarded !== 'true') {
            form.dataset.submitGuarded = 'true';
            window.addEventListener('pageshow', (event) => {
                if (event.persisted) form.reset();
            });
        }

        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) return;
            if (form.hasAttribute('data-loading-managed')) return;
            const button = resolveSubmitter(form, event);
            if (!button || button.dataset.actionBusy === 'true') return;
            // Set immediately so feedback is visible before navigation; if another
            // handler prevents default (validation, custom flow), clear on next tick.
            setActionBusy(button, true);
            window.setTimeout(() => {
                if (event.defaultPrevented && form.isConnected) setActionBusy(button, false);
            }, 0);
        });
    });
};

const initializeRegistrationFields = () => {
    initializePasswordRequirements();
    initializeBarangaySearch();
    // Live inline errors on the register form — everything except email (§12).
    initializeRegisterLiveValidation();
    initializeSubmitGuards();
};

const initializeSharedComponents = () => {
    initializeRegistrationFields();
    // Password eye toggles on the auth password fields (§5.41).
    initializePasswordToggles();
    // Staff-only: finds nothing on a resident page.
    initializeProofCaptures();
    // Staff- and admin-only: finds nothing on a resident page.
    initializeLiveQueues();
    initializeLiveTickets();
};

const registerRegistrationFieldLifecycle = () => {
    document.addEventListener('livewire:load', initializeSharedComponents);
    document.addEventListener('livewire:navigated', initializeSharedComponents);
    window.addEventListener('pageshow', initializeSharedComponents);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSharedComponents);
    } else {
        initializeSharedComponents();
    }
};

registerRegistrationFieldLifecycle();
