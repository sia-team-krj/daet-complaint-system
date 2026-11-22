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
    // The field home deep-links into one control of this ticket — #status,
    // #note, #proof-capture or #complaint-update-form. Scrolling to the
    // section is the browser's job; putting the caret in the field the link
    // named is ours, so "Add Findings" lands ready to type rather than
    // merely in view. Anything that is not a form control just scrolls.
    (() => {
        const focusHashTarget = () => {
            const hash = window.location.hash;
            if (!hash || hash.length < 2) return;

            let target;
            try {
                target = document.querySelector(hash);
            } catch (e) {
                return;
            }
            if (!target) return;
            if (!/^(input|textarea|select|button)$/i.test(target.tagName)) return;
            if (target.disabled || target.readOnly) return;

            // The fragment navigation already scrolled; don't scroll twice.
            target.focus({ preventScroll: true });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', focusHashTarget);
        } else {
            focusHashTarget();
        }
    })();
</script>
@endpush
