{{--
    The Leaflet handshake.

    Leaflet is bundled, which is right - it removes a third-party request from
    the critical path of a complaint being filed. But Vite emits
    `type="module"`, and the browser defers modules until after the document is
    parsed, while the per-page inline scripts that draw the maps are ordinary
    classic scripts that run first. So a page that reaches for `window.L`
    directly finds it undefined, renders no map, and reports no error: an empty
    box that a resident cannot distinguish from a map that captured their
    location.

    This is the queue those pages use instead. It is a classic script, in the
    document head, so it is guaranteed to exist before any page script runs. It
    knows nothing about Leaflet - only that something will arrive and flush it.
--}}
<script>
    (() => {
        const TIMEOUT_MS = 8000;

        window.__leafletWaiters = window.__leafletWaiters || [];

        window.whenLeafletReady = function whenLeafletReady(callback, options) {
            // Already arrived - this is a hard refresh on a page that also
            // loads the module normally.
            if (window.leafletReady) {
                callback(window.L);
                return;
            }

            const waiter = { callback, options: options || {} };
            window.__leafletWaiters.push(waiter);

            // A map that never initialises and says nothing is the failure this
            // whole mechanism exists to prevent. If the bundle does not turn
            // up, say so rather than leave an empty rectangle.
            window.setTimeout(() => {
                const index = window.__leafletWaiters.indexOf(waiter);
                if (index === -1) {
                    return; // already flushed
                }

                window.__leafletWaiters.splice(index, 1);

                if (waiter.options.onUnavailable) {
                    waiter.options.onUnavailable();
                }
            }, TIMEOUT_MS);
        };

        window.__flushLeafletWaiters = function flushLeafletWaiters() {
            const waiters = window.__leafletWaiters.splice(0);

            waiters.forEach((waiter) => {
                try {
                    waiter.callback(window.L);
                } catch (error) {
                    // One page's map failing must not stop the others.
                    console.error('Map initialisation failed.', error);
                }
            });
        };
    })();
</script>
