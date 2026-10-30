import "./bootstrap";
// Bundles Leaflet and exposes window.whenLeafletReady, so no page has to fetch a
// map library from a CDN. Imported here because both layouts already load this
// bundle, which means the library is present on every page that might need it
// and on no page that does not.
import "./leaflet";

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

// A single-use invitation is consumed on submit. A double click would let the
// second request find the code already redeemed and show a confusing
// "no longer available" error, so the submit control locks once used.
const initializeSubmitGuards = () => {
    document.querySelectorAll('form[data-disable-on-submit]').forEach((form) => {
        if (form.dataset.submitGuarded === 'true') return;
        form.dataset.submitGuarded = 'true';

        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (!button) return;

            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });

        // Restored on bfcache restore so a back-navigation can submit again.
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                form.reset();
                form.querySelectorAll('button[type="submit"]').forEach((button) => {
                    button.disabled = false;
                    button.removeAttribute('aria-disabled');
                });
            }
        });
    });
};

const initializeRegistrationFields = () => {
    initializePasswordRequirements();
    initializeBarangaySearch();
    initializeSubmitGuards();
};

const registerRegistrationFieldLifecycle = () => {
    document.addEventListener('livewire:load', initializeRegistrationFields);
    document.addEventListener('livewire:navigated', initializeRegistrationFields);
    window.addEventListener('pageshow', initializeRegistrationFields);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeRegistrationFields);
    } else {
        initializeRegistrationFields();
    }
};

registerRegistrationFieldLifecycle();
