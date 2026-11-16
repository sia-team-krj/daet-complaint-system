// Status toasts (docs/design-system.md §5.48). The markup is an inline notice
// first; this upgrades it to the floating acknowledgement and takes it away
// again. It binds to [data-toast] and does nothing on a page without one.
//
// The component carries `data-toast-duration` (0 means "stay until dismissed",
// which is what errors use). Hovering or focusing the toast pauses the clock,
// so a reader is never chased off the sentence. Escape and the close button
// dismiss it; the node removes itself when the fade finishes.

const REMOVE_FALLBACK_MS = 300;

export const initializeToasts = () => {
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        if (toast.dataset.toastBound === 'true') return;
        toast.dataset.toastBound = 'true';

        const duration = Number(toast.dataset.toastDuration) || 0;
        const close = toast.querySelector('[data-toast-close]');
        let timer = null;
        let leaving = false;

        const remove = () => {
            if (toast.isConnected) toast.remove();
        };

        const dismiss = () => {
            if (leaving) return;
            leaving = true;

            if (timer !== null) {
                window.clearTimeout(timer);
                timer = null;
            }

            toast.classList.remove('is-visible');
            toast.classList.add('is-leaving');

            toast.addEventListener('transitionend', remove, { once: true });
            window.setTimeout(remove, REMOVE_FALLBACK_MS);
        };

        const schedule = () => {
            if (timer !== null) {
                window.clearTimeout(timer);
                timer = null;
            }

            if (duration <= 0) return;

            timer = window.setTimeout(dismiss, duration);
        };

        // Upgrade: the element must be painted in its hidden floating state
        // before the visible class lands, or the fade does not run.
        toast.classList.add('toast--floating');
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => toast.classList.add('is-visible'));
        });

        schedule();

        toast.addEventListener('mouseenter', () => {
            if (timer !== null) {
                window.clearTimeout(timer);
                timer = null;
            }
        });
        toast.addEventListener('mouseleave', schedule);
        toast.addEventListener('focusin', () => {
            if (timer !== null) {
                window.clearTimeout(timer);
                timer = null;
            }
        });
        toast.addEventListener('focusout', (event) => {
            if (!toast.contains(event.relatedTarget)) schedule();
        });
        toast.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') dismiss();
        });
        close?.addEventListener('click', dismiss);
    });
};
