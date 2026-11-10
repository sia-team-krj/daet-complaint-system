/**
 * Live ticket updates.
 *
 * A ticket is worked by more than the person looking at it. Somebody in the
 * same office can move the case, settle it or leave a note while this page is
 * open, and a staff member who finds out only when the server refuses their
 * update has already wasted the work.
 *
 * This polls the ticket's fingerprint and, when it differs, says who changed
 * what and offers a reload. **It never rewrites the page.** The page holds a
 * form somebody may be halfway through; a live update must not take the words
 * out of their mouth. The only things touched are the status pill in the
 * banner (display-only) and the notice itself.
 *
 * The rules it keeps:
 *
 * 1. An unchanged ticket costs one small request and touches no DOM.
 * 2. A hidden tab is not polled; it catches up when the tab comes back.
 * 3. The server stays the only renderer and the only source of the message.
 * 4. Without JavaScript the ticket is still a ticket — the server renders it
 *    and the reader reloads when they choose to. Nothing here is required.
 * 5. A dismissed notice does not come back for the same change; a newer
 *    change is a new fact and may speak again.
 *
 * The contract is the markup:
 *
 *   [data-live-ticket]          the root; carries data-endpoint (the URL that
 *                               answers {fingerprint, changed, message,
 *                               status_key, status_label}), data-fingerprint
 *                               (what is on screen) and optionally
 *                               data-interval (milliseconds)
 *   [data-live-ticket-notice]   the notice; hidden until something changes
 *   [data-live-ticket-message]  the sentence the server sent
 *
 * Docs: docs/design-system.md §5.39.
 */

const DEFAULT_INTERVAL = 20000;

/**
 * Wire one live ticket.
 *
 * @param {HTMLElement} root
 */
const createLiveTicket = (root) => {
    const endpoint = root.dataset.endpoint;

    if (!endpoint) return null;

    const noticeEl = root.querySelector('[data-live-ticket-notice]');
    const messageEl = root.querySelector('[data-live-ticket-message]');
    const dismissEl = root.querySelector('[data-live-ticket-dismiss]');
    const interval = Number(root.dataset.interval) || DEFAULT_INTERVAL;

    let fingerprint = root.dataset.fingerprint || '';
    let timer = null;
    let dismissed = '';

    const notice = (message) => {
        if (!noticeEl || !messageEl || !message) return;

        messageEl.textContent = message;
        noticeEl.hidden = false;
    };

    const syncStatusPill = (payload) => {
        const pill = document.querySelector('[data-ticket-status-pill]');
        const label = document.querySelector('[data-ticket-status-label]');

        if (pill && payload.status_key) {
            [...pill.classList]
                .filter((name) => name !== 'status-pill')
                .forEach((name) => pill.classList.remove(name));

            pill.classList.add(payload.status_key);
        }

        if (label && payload.status_label) label.textContent = payload.status_label;
    };

    const poll = async () => {
        if (document.visibilityState === 'hidden') return;

        const url = new URL(endpoint, window.location.origin);
        if (fingerprint) url.searchParams.set('fingerprint', fingerprint);

        try {
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) return;

            const payload = await response.json();

            // Nothing changed: leave the page alone.
            if (!payload.fingerprint || payload.fingerprint === fingerprint) return;
            if (payload.changed !== true) return;

            fingerprint = payload.fingerprint;
            root.dataset.fingerprint = fingerprint;

            syncStatusPill(payload);

            if (dismissed === fingerprint) return;

            notice(payload.message);
        } catch (error) {
            // A failed poll is not worth interrupting the ticket over.
        }
    };

    const start = () => {
        if (timer) window.clearInterval(timer);
        timer = window.setInterval(poll, interval);
    };

    const stop = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
    };

    if (dismissEl && noticeEl) {
        dismissEl.addEventListener('click', () => {
            dismissed = fingerprint;
            noticeEl.hidden = true;
        });
    }

    // Catch up when the tab comes back, rather than waiting for the next tick.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') poll();
    });

    root.__daetLiveTicket = { poll, start, stop };

    return { poll, start, stop };
};

/**
 * Wire every live ticket on the page.
 *
 * @param {ParentNode} scope
 * @returns {number} how many were wired
 */
export const initializeLiveTickets = (scope = document) => {
    const roots = scope.querySelectorAll('[data-live-ticket]');
    let wired = 0;

    roots.forEach((root) => {
        if (root.dataset.liveTicketBound === '1') return;

        root.dataset.liveTicketBound = '1';

        const ticket = createLiveTicket(root);
        if (ticket) {
            ticket.start();
            wired += 1;
        }
    });

    return wired;
};
