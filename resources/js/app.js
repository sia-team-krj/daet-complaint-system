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
    initializeSubmitGuards();
};

const initializeSharedComponents = () => {
    initializeRegistrationFields();
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
