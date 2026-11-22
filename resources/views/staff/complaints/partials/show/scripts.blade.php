@push('scripts')
<script>
    (() => {
        const mapElement = document.getElementById('complaint-map');
        if (!mapElement) return;

        // Map wrapper contract: isolation: isolate; z-index: 0.
        const buildComplaintMap = () => {
            if (mapElement.dataset.mapInitialized === 'true') return;
            mapElement.dataset.mapInitialized = 'true';

            const latitude = Number.parseFloat(mapElement.dataset.latitude);
            const longitude = Number.parseFloat(mapElement.dataset.longitude);
            if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) return;

            const map = window.L.map(mapElement, { scrollWheelZoom: false }).setView([latitude, longitude], 16);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
            window.L.marker([latitude, longitude]).addTo(map);

            // Leaflet measures once, when the map is built. A phone's URL bar
            // collapses and a webfont reflows the card after that, so the map
            // re-measures without animating (a resize is not a user gesture).
            const remeasure = () => map.invalidateSize({ animate: false });
            remeasure();
            window.addEventListener('resize', remeasure);
            window.addEventListener('orientationchange', remeasure);

            if ('IntersectionObserver' in window) {
                new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) remeasure();
                    });
                }, { root: null, threshold: 0.1 }).observe(mapElement);
            }
        };

        if (window.whenLeafletReady) {
            window.whenLeafletReady(buildComplaintMap, {
                // The pin is still reachable through the directions action,
                // so a missing map must say that rather than leave an empty box.
                onUnavailable: () => {
                    mapElement.dataset.mapInitialized = 'false';
                    const message = document.createElement('p');
                    message.className = 'map-unavailable';
                    message.textContent = mapElement.dataset.mapUnavailable || '';
                    mapElement.replaceChildren(message);
                },
            });
        } else {
            document.addEventListener('DOMContentLoaded', buildComplaintMap);
        }

        // Collapsible review/verification body. State lives in localStorage so
        // the staff member's choice survives reloads and Livewire re-renders.
        // Visibility uses [hidden] (never style.display), per §7 of the design
        // system; listeners are bound once per panel.
        (() => {
            const PREFIX = 'daet.review-panel.';
            const init = () => {
                document.querySelectorAll('[data-review-panel]').forEach((panel) => {
                    if (panel.dataset.reviewInit === 'true') return;
                    const toggle = panel.querySelector('[data-review-toggle]');
                    const body = panel.querySelector('[data-review-body]');
                    const ticket = panel.dataset.reviewTicket;
                    if (!toggle || !body || !ticket) return;

                    const key = PREFIX + ticket;
                    const text = toggle.querySelector('.review-toggle__text');

                    const setExpanded = (expanded) => {
                        body.toggleAttribute('hidden', !expanded);
                        toggle.setAttribute('aria-expanded', String(expanded));
                        text.textContent = expanded
                            ? '{{ __('staff.verification.minimize') }}'
                            : '{{ __('staff.verification.show') }}';
                        toggle.setAttribute('aria-label', expanded
                            ? '{{ __('staff.verification.minimize_label') }}'
                            : '{{ __('staff.verification.show_label') }}');
                        try { localStorage.setItem(key, String(expanded)); } catch (e) {}
                    };

                    try {
                        const saved = localStorage.getItem(key);
                        if (saved !== null) setExpanded(saved === 'true');
                    } catch (e) {}

                    toggle.addEventListener('click', () => {
                        setExpanded(toggle.getAttribute('aria-expanded') === 'false');
                    });

                    panel.dataset.reviewInit = 'true';
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            // Livewire-safe: re-apply to any newly rendered panels after a
            // client-side navigation / partial refresh so the toggle keeps
            // working without re-binding existing ones.
            if (window.Livewire) {
                window.Livewire.hook('message.processed', () => init());
            }
        })();
    })();
</script>
@endpush

@push('scripts')
<script>
    // The ticket's quick actions are same-page anchors. Scrolling to the
    // target is the browser's job; putting the caret in the control the
    // action names is ours: "Add Findings" should land ready to type and
    // "Take Photo" should actually open the camera. Everything is a plain
    // fragment link first — with JavaScript off the anchors still work.
    (() => {
        // Focus the first usable form control inside a form.
        const focusIn = (form) => {
            const first = form.querySelector('input:not([type=hidden]), select, textarea, button');
            if (!first) return;
            if (first.disabled) return;
            first.focus();
            openSelectPicker(first);
        };

        // Browsers only open <select> popups for user-activated gestures; a
        // click through the action bar is one, a `hashchange` from an anchor
        // is not. Older browsers and pointer-less contexts ignore it.
        const openSelectPicker = (el) => {
            if (!(el instanceof HTMLSelectElement)) return;
            if (typeof el.showPicker !== 'function') return;
            try { el.showPicker(); } catch (e) { /* no-op where unsupported */ }
        };

        const handleHashTarget = () => {
            const hash = window.location.hash;
            if (!hash || hash.length < 2) return;

            let link;
            try {
                // The action anchors carry what the tap was for ("Add
                // Findings" vs "Submit Report"); the fragment alone can't.
                link = document.querySelector('a[href="' + hash + '"]');
            } catch (e) {
                link = null;
            }
            let target;
            try {
                target = document.querySelector(hash);
            } catch (e) {
                return;
            }
            if (!target) return;

            // "Take Photo": the deep link names the capture zone, not a
            // control — open the camera picker rather than scroll to it.
            if (target.id === 'proof-capture') {
                const camera = target.querySelector('button.proof-capture__action--camera');
                if (camera) camera.click();
                return;
            }

            // "Add Findings" → the official response note; "Submit Report"
            // → the response form's submit button.
            if (target instanceof HTMLFormElement && target.id === 'complaint-update-form') {
                const kind = link?.dataset?.ticketFocus;
                if (kind === 'submit') {
                    const submit = target.querySelector('button[type="submit"]');
                    if (submit && !submit.disabled) submit.focus();
                } else if (kind === 'note') {
                    const note = target.querySelector('#note');
                    if (note && !note.disabled && !note.readOnly) note.focus();
                } else {
                    focusIn(target);
                }
                return;
            }

            if (/^(input|textarea|select|button)$/i.test(target.tagName)) {
                if (target.disabled || target.readOnly) return;
                target.focus();
                openSelectPicker(target);
            }
        };

        const attachActionClicks = () => {
            document.querySelectorAll('.ticket-action').forEach((link) => {
                if (link.dataset.actionBound === 'true') return;
                link.dataset.actionBound = 'true';
                // Clicking already moved location.hash, so let the hashchange
                // handler run on the next tick — it also covers a tap on the
                // same action twice, when the hash does not change.
                link.addEventListener('click', () => { setTimeout(handleHashTarget, 0); });
            });
        };

        const init = () => {
            window.addEventListener('hashchange', handleHashTarget);
            attachActionClicks();
            handleHashTarget();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        window.addEventListener('hashchange', attachActionClicks);
    })();
</script>
@endpush
