/**
 * Live queue updates.
 *
 * A queue that shows stale rows is a queue that gets worked twice: two staff
 * members can pick up the same case because neither saw the other's move. This
 * polls the server for the list's fingerprint and swaps the rows **only when
 * something really changed**.
 *
 * The rules it keeps:
 *
 * 1. A poll that finds nothing changes nothing. The client compares a
 *    fingerprint, so an unchanged queue costs one small request and touches no
 *    DOM — a row never moves under the cursor, and a staff member who is
 *    typing is never interrupted.
 * 2. A hidden tab is not polled. The poll resumes (and catches up) when the
 *    tab comes back.
 * 3. The server stays the only renderer. The endpoint answers with a
 *    fingerprint and the rendered fragments; this file only swaps them in.
 * 4. Without JavaScript the queue is still a queue: the server renders the
 *    rows and the reader refreshes. Nothing here is required to use the page.
 *
 * The contract is the markup:
 *
 *   [data-live-queue]        the root; carries data-endpoint (the URL that
 *                            answers with {fingerprint, list, pagination}),
 *                            data-fingerprint (what is on screen now) and
 *                            optionally data-interval (milliseconds)
 *   [data-queue-list]        the region holding the rows
 *   [data-queue-pagination]  the region holding the pager
 *   [data-live-queue-status]  a polite live region that says what changed
 *
 * Docs: docs/design-system.md §5.36.
 */

const DEFAULT_INTERVAL = 30000;

/**
 * The ticket ids currently on screen.
 *
 * @param {ParentNode|null} scope
 * @returns {Set<string>}
 */
const ticketIds = (scope) => {
    const ids = new Set();

    if (!scope) return ids;

    scope.querySelectorAll('[data-report-id]').forEach((row) => {
        if (row.dataset.reportId) ids.add(row.dataset.reportId);
    });

    return ids;
};

/**
 * How many cases appeared and disappeared.
 *
 * @param {Set<string>} before
 * @param {string} html
 * @returns {{added: number, removed: number}}
 */
const diffCases = (before, html) => {
    const holder = document.createElement('div');
    holder.innerHTML = html;

    const after = ticketIds(holder);
    let added = 0;
    let removed = 0;

    after.forEach((id) => {
        if (!before.has(id)) added += 1;
    });
    before.forEach((id) => {
        if (!after.has(id)) removed += 1;
    });

    return { added, removed };
};

/**
 * Wire one live queue.
 *
 * @param {HTMLElement} root
 */
const createLiveQueue = (root) => {
    const endpoint = root.dataset.endpoint;

    if (!endpoint) return null;

    const listEl = root.querySelector('[data-queue-list]');
    const pagerEl = root.querySelector('[data-queue-pagination]');
    const statusEl = root.querySelector('[data-live-queue-status]');
    const interval = Number(root.dataset.interval) || DEFAULT_INTERVAL;

    let fingerprint = root.dataset.fingerprint || '';
    let timer = null;

    const announce = (message) => {
        if (!statusEl || !message) return;

        statusEl.textContent = message;

        window.setTimeout(() => {
            if (statusEl.textContent === message) statusEl.textContent = '';
        }, 10000);
    };

    const poll = async () => {
        // A hidden tab is not polled, and a queue the admin is working inside
        // is not swapped under them.
        if (document.visibilityState === 'hidden') return;

        const active = document.activeElement;
        if (active && listEl && listEl.contains(active)) return;
        if (window.getSelection && String(window.getSelection()).length > 0) return;

        const url = new URL(endpoint, window.location.origin);
        url.search = window.location.search;

        try {
            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok) return;

            const payload = await response.json();

            // Nothing changed: leave the DOM alone.
            if (!payload.fingerprint || payload.fingerprint === fingerprint) return;

            fingerprint = payload.fingerprint;

            const before = ticketIds(listEl);
            const changed = diffCases(before, payload.list || '');
            const total = changed.added + changed.removed;

            if (listEl && typeof payload.list === 'string') listEl.innerHTML = payload.list;
            if (pagerEl && typeof payload.pagination === 'string') pagerEl.innerHTML = payload.pagination;

            if (total === 1) {
                announce(root.dataset.messageChangedOne || '');
            } else if (total > 1) {
                announce((root.dataset.messageChangedMany || '').replace(':count', String(total)));
            } else {
                announce(root.dataset.messageUpdated || '');
            }
        } catch (error) {
            // A failed poll is not worth breaking the queue over.
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

    // Catch up when the tab comes back.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') poll();
    });

    root.__daetLiveQueue = { poll, start, stop };

    return { poll, start, stop };
};

/**
 * Wire every live queue on the page.
 *
 * @param {ParentNode} scope
 * @returns {number} how many were wired
 */
export const initializeLiveQueues = (scope = document) => {
    const roots = scope.querySelectorAll('[data-live-queue]');
    let wired = 0;

    roots.forEach((root) => {
        if (root.dataset.liveQueueBound === '1') return;

        root.dataset.liveQueueBound = '1';

        const queue = createLiveQueue(root);
        if (queue) {
            queue.start();
            wired += 1;
        }
    });

    return wired;
};
