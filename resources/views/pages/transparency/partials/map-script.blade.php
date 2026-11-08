{{--
    Map-only script for the Transparency summary page.

    The full filter/sort logic lives in register-script.blade.php and is used
    by the all-complaints page. This page only needs the map, so it ships a
    trimmed copy to avoid dragging dead filter code into the summary payload.
--}}
<script>
(() => {
    'use strict';

    const filters = {};

    const TRANSPARENCY_STRINGS = {
        mapUnavailable: "{{ __('transparency.map.unavailable') }}",
        mapUnavailableLoad: "{{ __('transparency.map.unavailable_load') }}",
        noAreas: "{{ __('transparency.map.no_areas') }}",
        heatUnavailable: "{{ __('transparency.map.heat_unavailable') }}",
    };

    const parseAttribute = (element, name, fallback) => {
        try {
            return JSON.parse(element.dataset[name] ?? '') ?? fallback;
        } catch (error) {
            return fallback;
        }
    };

    const token = (name, fallback) => {
        try {
            const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            return value !== '' ? value : fallback;
        } catch (error) {
            return fallback;
        }
    };

    let map = null;
    let onResize = null;
    let onOrientationChange = null;
    let lastMapSignature = null;
    let leafletReady = false;
    const waitingForLeaflet = [];

    window.whenLeafletReady(() => {
        leafletReady = true;
        waitingForLeaflet.splice(0).forEach((run) => run());
    }, {
        onUnavailable: () => setStatus(TRANSPARENCY_STRINGS.mapUnavailable),
    });

    const whenLeafletReady = (run) => {
        if (leafletReady) { run(); return; }
        waitingForLeaflet.push(run);
    };

    const setStatus = (message) => {
        const el = document.getElementById('map-status');
        if (!el) return;
        el.replaceChildren();
        el.classList.add('tr-map-status--message');
        const text = document.createElement('p');
        text.className = 'tr-map-status__message';
        text.textContent = message;
        el.appendChild(text);
        el.hidden = false;
    };

    const hideMapStatus = () => {
        const el = document.getElementById('map-status');
        if (el) el.hidden = true;
    };

    const destroyMap = () => {
        if (onResize) window.removeEventListener('resize', onResize);
        if (onOrientationChange) window.removeEventListener('orientationchange', onOrientationChange);
        onResize = null;
        onOrientationChange = null;
        if (map) {
            try { map.remove(); } catch (error) {}
            map = null;
        }
    };

    const readMapPayload = () => {
        const element = document.getElementById('transparency-map-data');
        if (!element) return null;
        const points = parseAttribute(element, 'points', []);
        const center = parseAttribute(element, 'center', [14.1122, 122.9553]);
        return {
            points: Array.isArray(points) ? points : [],
            center: Array.isArray(center) ? center : [14.1122, 122.9553],
        };
    };

    const mapHasBeenDetached = () => {
        if (!map) return false;
        const container = document.getElementById('transparency-map');
        if (!container || !container.isConnected) return true;
        if (typeof map.getContainer === 'function' && map.getContainer() !== container) return true;
        return !container.querySelector('.leaflet-pane');
    };

    const buildMap = (payload) => {
        const mapElement = document.getElementById('transparency-map');
        if (!mapElement) return;
        if (!payload) { setStatus(TRANSPARENCY_STRINGS.mapUnavailableLoad); return; }
        const points = payload.points;
        const center = payload.center;
        const isMobile = (typeof window.L !== 'undefined' && window.L.Browser && window.L.Browser.mobile)
            || (typeof window.matchMedia === 'function' && window.matchMedia('(pointer: coarse)').matches);

        whenLeafletReady(() => {
            destroyMap();
            map = window.L.map(mapElement, {
                scrollWheelZoom: false,
                zoomControl: true,
                dragging: !isMobile,
                tap: false,
            }).setView(center, 13);

            if (isMobile) {
                const el = map.getContainer();
                el.addEventListener('touchstart', (event) => {
                    if (event.touches.length >= 2 && map) map.dragging.enable();
                    const hint = document.getElementById('map-hint');
                    if (hint) hint.style.display = 'none';
                }, { passive: true });
                el.addEventListener('touchend', (event) => {
                    if (event.touches.length < 2 && map) map.dragging.disable();
                }, { passive: true });
            }

            const keepMapSized = () => { map.invalidateSize({ animate: false }); };
            onResize = keepMapSized;
            onOrientationChange = () => { window.setTimeout(keepMapSized, 150); };
            window.requestAnimationFrame(keepMapSized);
            window.setTimeout(keepMapSized, 300);
            window.addEventListener('resize', onResize);
            window.addEventListener('orientationchange', onOrientationChange);

            window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            hideMapStatus();

            const heatLayerAvailable = typeof window.L.heatLayer === 'function';
            if (points.length > 0 && heatLayerAvailable) {
                const heatGradient = ['--heat-1', '--heat-2', '--heat-3', '--heat-4']
                    .map((name) => token(name, ''))
                    .every((value) => value !== '')
                    ? {
                        0.2: token('--heat-1', ''),
                        0.45: token('--heat-2', ''),
                        0.7: token('--heat-3', ''),
                        1: token('--heat-4', ''),
                    }
                    : undefined;

                window.L.heatLayer(
                    points.map((point) => [Number(point.lat), Number(point.lng), Number(point.intensity || 0.6)]),
                    { radius: 38, blur: 26, minOpacity: 0.38, maxZoom: 16, ...(heatGradient ? { gradient: heatGradient } : {}) },
                ).addTo(map);
            }

            if (points.length > 0) {
                const bounds = window.L.latLngBounds(points.map((point) => [Number(point.lat), Number(point.lng)]));
                if (bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.15), { maxZoom: 15 });
                }
            }

            if (points.length === 0) {
                setStatus(TRANSPARENCY_STRINGS.noAreas);
            } else if (!heatLayerAvailable) {
                setStatus(TRANSPARENCY_STRINGS.heatUnavailable);
            }
        });
    };

    const refreshMap = () => {
        const payload = readMapPayload();
        if (!payload) return;
        const signature = JSON.stringify(payload);
        const dataMoved = signature !== lastMapSignature;
        if (!dataMoved && !mapHasBeenDetached()) return;
        lastMapSignature = signature;
        buildMap(payload);
    };

    const refresh = () => {
        try { refreshMap(); } catch (error) {}
    };

    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        window.Livewire.hook('morphed', () => { refresh(); });
    }

    refresh();
})();
</script>
