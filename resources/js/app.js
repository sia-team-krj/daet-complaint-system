import "./bootstrap";

const initializePasswordRequirements = () => {
    document.querySelectorAll('[data-password-requirements]').forEach((requirements) => {
        const input = document.getElementById(requirements.dataset.passwordTarget);
        if (!input || requirements.dataset.initialized === 'true') return;

        requirements.dataset.initialized = 'true';
        const checks = {
            length: requirements.querySelector('[data-password-requirement="length"]'),
            case: requirements.querySelector('[data-password-requirement="case"]'),
            number: requirements.querySelector('[data-password-requirement="number"]'),
        };

        const update = () => {
            const value = input.value;
            const results = {
                length: value.length >= 8,
                case: /[a-z]/.test(value) && /[A-Z]/.test(value),
                number: /[0-9]/.test(value),
            };

            Object.entries(checks).forEach(([key, element]) => {
                if (!element) return;
                element.classList.toggle('is-met', results[key]);
                element.setAttribute('data-met', results[key] ? 'true' : 'false');
            });
        };

        input.addEventListener('input', update);
        update();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePasswordRequirements);
} else {
    initializePasswordRequirements();
}
