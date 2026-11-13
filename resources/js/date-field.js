// The date field (docs/design-system.md §5.42).
//
// The visible field is a text input masked to dd/mm/yyyy, because the
// native date input shows the browser's locale rather than ours — the same
// office would read 12/15/2026 on one device and 15/12/2026 on another.
// The calendar beside it is a native date input kept in the form: the button
// opens the OS calendar (the wheel picker on a phone), and the native input
// carries the value the server receives.
//
// Binds to [data-date-field]; does nothing on a page without one.

const DAYS_IN_MONTH = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

const isLeapYear = (year) => (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;

const daysInMonth = (month, year) => {
    if (month === 2) {
        return isLeapYear(year) ? 29 : 28;
    }

    return DAYS_IN_MONTH[month - 1];
};

const pad = (value) => String(value).padStart(2, '0');

/**
 * Keep only the digits, and put the slashes in as the office types.
 */
const maskDigits = (raw) => {
    const digits = raw.replace(/\D/g, '').slice(0, 8);

    if (digits.length <= 2) {
        return digits;
    }

    if (digits.length <= 4) {
        return `${digits.slice(0, 2)}/${digits.slice(2)}`;
    }

    return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
};

/**
 * Read a dd/mm/yyyy string back into yyyy-mm-dd, or null when it is not a
 * date that exists — 31/02 is not a date, and the field must say so rather
 * than quietly rolling over into March.
 */
const toIso = (display) => {
    const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(display.trim());

    if (!match) {
        return null;
    }

    const day = Number(match[1]);
    const month = Number(match[2]);
    const year = Number(match[3]);

    if (month < 1 || month > 12 || year < 1900) {
        return null;
    }

    if (day < 1 || day > daysInMonth(month, year)) {
        return null;
    }

    return `${year}-${pad(month)}-${pad(day)}`;
};

const toDisplay = (iso) => {
    if (!iso) {
        return '';
    }

    const [year, month, day] = iso.split('-');

    return `${day}/${month}/${year}`;
};

const initializeDateField = (field) => {
    if (field.dataset.initialized === 'true') {
        return;
    }

    field.dataset.initialized = 'true';

    const display = field.querySelector('[data-date-field-display]');
    const native = field.querySelector('[data-date-field-native]');
    const open = field.querySelector('[data-date-field-open]');
    const clear = field.querySelector('[data-date-field-clear]');
    const error = field.querySelector('[data-date-field-error]');

    if (!display || !native) {
        return;
    }

    const setError = (invalid) => {
        display.setAttribute('aria-invalid', invalid ? 'true' : 'false');

        if (error) {
            error.hidden = !invalid;
        }

        field.classList.toggle('date-field--invalid', invalid);
    };

    const syncFromDisplay = () => {
        const masked = maskDigits(display.value);

        if (masked !== display.value) {
            display.value = masked;
        }

        if (masked === '') {
            native.value = '';
            setError(false);
            if (clear) clear.hidden = true;
            return;
        }

        const iso = toIso(masked);

        if (iso === null) {
            setError(true);
            return;
        }

        native.value = iso;
        setError(false);
        if (clear) clear.hidden = false;
    };

    const syncFromNative = () => {
        display.value = toDisplay(native.value);
        setError(false);
        if (clear) clear.hidden = !native.value;
    };

    display.addEventListener('input', syncFromDisplay);
    display.addEventListener('blur', syncFromDisplay);

    native.addEventListener('change', syncFromNative);

    if (open) {
        open.addEventListener('click', () => {
            // showPicker() is refused on an element that is not being
            // rendered, which is why the native input is hidden by opacity
            // rather than by display:none.
            if (typeof native.showPicker === 'function') {
                try {
                    native.showPicker();
                    return;
                } catch (error) {
                    // Fall through to focusing it: the browser's own picker
                    // icon is still reachable from the keyboard.
                }
            }

            native.focus();
        });
    }

    if (clear) {
        clear.addEventListener('click', () => {
            display.value = '';
            native.value = '';
            setError(false);
            clear.hidden = true;
            display.focus();
        });
    }

    // The form must never post a value the office has not finished typing.
    const form = display.closest('form');

    if (form) {
        form.addEventListener('submit', (event) => {
            syncFromDisplay();

            if (display.getAttribute('aria-invalid') === 'true') {
                event.preventDefault();
                display.focus();
            }
        });
    }

    syncFromNative();
};

export const initializeDateFields = () => {
    document.querySelectorAll('[data-date-field]').forEach(initializeDateField);
};
