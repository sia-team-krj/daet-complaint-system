{{--
    The register's map and filters.

    Both of these used to run once, on page load, and never again. The register
    above them is now a Livewire component that re-renders on a timer, which
    means two things this script has to deal with that it never had to before:

      1. Livewire replaces the elements these listeners are attached to. The map
         container, the search box, and all three selects are inside the
         component, so every poll strips their listeners. The script therefore
         re-binds itself after every update instead of running once.

      2. A poll must not throw away what the reader was doing. Someone who has
         typed half a ticket number into the search box would otherwise watch it
         clear itself every twenty seconds. The filter values and the focused
         field are held here and re-applied after each render.

    The map is the subtler half. Rebuilding it is expensive and visibly rude - it
    would snap the view back to centre under anyone who had panned or zoomed - so
    it is only rebuilt when the data actually moved. An unchanged poll costs
    nothing and leaves the reader alone.
--}}
<script>
(() => {
    'use strict';

    const FIELDS = ['search', 'department', 'category', 'status'];

    // What the reader has entered or chosen. Defaults match what the server
    // renders, so an untouched page is indistinguishable from a tracked one.
    const filters = { search: '', department: '', category: '', status: '' };
    let caret = null;
    let focusedField = null;

    let map = null;
    let onResize = null;
    let onOrientationChange = null;
    let lastMapSignature = null;

    let leafletReady = false;
    const waitingForLeaflet = [];

    const TRANSPARENCY_STRINGS = {
        mapUnavailable: "{{ __('transparency.map.unavailable') }}",
        mapUnavailableLoad: "{{ __('transparency.map.unavailable_load') }}",
        noAreas: "{{ __('transparency.map.no_areas') }}",
        heatUnavailable: "{{ __('transparency.map.heat_unavailable') }}",
        shown: "{{ __('transparency.map.shown_template') }}",
        results: "{{ __('transparency.register.results') }}",
    };

    // Leaflet is bundled rather than fetched from a CDN, but a bundled module is
    // deferred and this inline script is not, so the map is built from a
    // callback the bundle calls instead of by reaching for window.L and finding
    // it undefined on a page that is otherwise fine. Only ever registered once,
    // however many times the register re-renders.
    //
    // The absence of Leaflet is reported through the handshake's own
    // `onUnavailable` option. It used to be handled by an early guard that
    // tested for the global and bailed, and that is the silent failure this page
    // is meant not to have: the guard ran before the bundle had any chance to
    // load, so a perfectly healthy map lost its race against an inline script and
    // the reader got an empty box with no explanation. Waiting is the fix; saying
    // so when waiting fails is the handshake's job.
    //
    // (This comment once named that guard's expression outright, which is why it
    // is described instead: the test that forbids the guard greps the file as
    // plain text, comments included, and quoting it verbatim made this file fail
    // the very check it was explaining.)
    window.whenLeafletReady(() => {
        leafletReady = true;
        waitingForLeaflet.splice(0).forEach((run) => run());
    }, {
        onUnavailable: () => setStatus(TRANSPARENCY_STRINGS.mapUnavailable),
    });

    const whenLeafletReady = (run) => {
        if (leafletReady) {
            run();
            return;
        }
        waitingForLeaflet.push(run);
    };

    const parseAttribute = (element, name, fallback) => {
        try {
            return JSON.parse(element.dataset[name] ?? '') ?? fallback;
        } catch (error) {
            return fallback;
        }
    };

    // The payload lives in data attributes rather than a JSON <script> island
    // because this markup is inside a Livewire component, and Livewire treats a
    // script tag it finds there as code to run. An attribute is just text.
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

    // Queried fresh on every call rather than captured once. The status element
    // lives inside the polled component, so a reference held across a morph
    // points at a node that is no longer in the document.
    const setStatus = (message) => {
        const text = document.querySelector('#transparency-map-status span');

        if (text) text.textContent = message;
    };

    const destroyMap = () => {
        if (onResize) window.removeEventListener('resize', onResize);
        if (onOrientationChange) window.removeEventListener('orientationchange', onOrientationChange);
        onResize = null;
        onOrientationChange = null;

        if (map) {
            // A morph may already have removed everything Leaflet put inside the
            // container. Tearing down in that state is allowed to fail: this is
            // cleanup for an object being discarded either way, and letting it
            // throw would abandon the filter re-binding in the same pass.
            try {
                map.remove();
            } catch (error) {
                // Nothing to salvage - the next build makes a fresh map.
            }

            map = null;
        }
    };

    const buildMap = (payload) => {
        // Re-queried every time: both elements are inside the polled component,
        // so a reference captured on the first build is a reference to a node a
        // later morph has already replaced.
        const mapElement = document.getElementById('transparency-map');

        if (!mapElement) return;

        if (!payload) {
            setStatus(TRANSPARENCY_STRINGS.mapUnavailableLoad);
            return;
        }

        const points = payload.points;
        const center = payload.center;

        whenLeafletReady(() => {
            destroyMap();

            map = window.L.map(mapElement, {
                scrollWheelZoom: false,
                zoomControl: true,
            }).setView(center, 13);

            /*
             * Leaflet measures its container once, when the map is built, and
             * caches that. A desktop viewport has settled by then; a phone has
             * not. The URL bar collapses, scrollbars come and go, a late webfont
             * reflows the card around the map, and the heat layer's own canvas
             * is sized from the same stale figure. Any of those leaves the map
             * drawn for a size the container no longer has, which shows as a
             * blank band or a strip of clipped map. `animate: false` because a
             * re-measure is not a user gesture and should not zoom at them.
             */
            const keepMapSized = () => {
                map.invalidateSize({ animate: false });
            };

            onResize = keepMapSized;
            onOrientationChange = () => {
                window.setTimeout(keepMapSized, 150);
            };

            window.requestAnimationFrame(keepMapSized);
            window.setTimeout(keepMapSized, 300);
            window.addEventListener('resize', onResize);
            window.addEventListener('orientationchange', onOrientationChange);

            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 18,
            }).addTo(map);

            const heatLayerAvailable = typeof window.L.heatLayer === 'function';

            if (points.length > 0 && heatLayerAvailable) {
                window.L.heatLayer(
                    points.map((point) => [
                        Number(point.lat),
                        Number(point.lng),
                        Number(point.intensity || 0.6),
                    ]),
                    {
                        radius: 38,
                        blur: 26,
                        minOpacity: 0.38,
                        maxZoom: 16,
                        gradient: {
                            0.2: '#6abf8a',
                            0.45: '#e2c06a',
                            0.7: '#d98a4a',
                            1: '#c65b5b',
                        },
                    },
                ).addTo(map);
            }

            if (points.length > 0) {
                const bounds = window.L.latLngBounds(
                    points.map((point) => [Number(point.lat), Number(point.lng)]),
                );

                if (bounds.isValid()) {
                    map.fitBounds(bounds.pad(0.15), { maxZoom: 15 });
                }
            }

            if (points.length === 0) {
                setStatus(TRANSPARENCY_STRINGS.noAreas);
            } else if (!heatLayerAvailable) {
                setStatus(TRANSPARENCY_STRINGS.heatUnavailable);
            } else {
                const reportTotal = points.reduce((total, point) => total + (Number(point.count) || 0), 0);
                setStatus(TRANSPARENCY_STRINGS.shown.replace(':count', reportTotal));
            }
        });
    };

    /*
     * Is the built map still attached to the live document?
     *
     * This is the question that a "did the data change?" check cannot answer, and
     * getting it wrong cost the map entirely. Every poll re-renders the whole
     * register component, which replaces its markup wholesale - including the
     * element Leaflet is mounted into. Leaflet does not notice: its own object is
     * perfectly healthy, holding a container node that is no longer in the
     * document. So a poll that found no new complaints left a page with no map
     * on it, with the map status still reading its original "loading" message,
     * and nothing on the page to explain it. It looked fine on first load, which
     * is exactly why it survived being written.
     */
    const mapHasBeenDetached = () => {
        if (!map) return false;

        const container = document.getElementById('transparency-map');

        if (!container || !container.isConnected) {
            return true;
        }

        if (typeof map.getContainer === 'function' && map.getContainer() !== container) {
            return true;
        }

        // The check that actually matters. A morph does not swap the container
        // out for a new node - it reconciles the existing one in place, which
        // means the element is still the very same object while everything
        // Leaflet built inside it has been removed. Comparing nodes says
        // "unchanged" and the map stays gone, so the signal has to be whether
        // Leaflet's own panes are still in there.
        return !container.querySelector('.leaflet-pane');
    };

    /*
     * Rebuild only when it would actually change something: the data moved, or
     * the container the map was mounted into was replaced by a poll.
     *
     * Rebuilding unconditionally would be worse than never rebuilding. It tears
     * down and re-creates the map, which snaps the view back to fit - so anyone
     * who had zoomed into their own barangay would have that yanked away every
     * twenty seconds.
     */
    const refreshMap = () => {
        const payload = readMapPayload();

        if (!payload) return;

        const signature = JSON.stringify(payload);
        const dataMoved = signature !== lastMapSignature;

        if (!dataMoved && !mapHasBeenDetached()) return;

        lastMapSignature = signature;
        buildMap(payload);
    };

    const bindFilters = () => {
        const search = document.getElementById('complaint-search');
        const department = document.getElementById('complaint-department');
        const category = document.getElementById('complaint-category');
        const status = document.getElementById('complaint-status');
        const reset = document.getElementById('complaint-reset');
        const results = document.getElementById('complaint-results');
        const noResults = Array.from(document.querySelectorAll('[data-no-results]'));
        const rows = Array.from(document.querySelectorAll('[data-report-row]'));

        if (!search || !department || !category || !status || !reset || !results || noResults.length === 0 || rows.length === 0) {
            return;
        }

        // Put back what the reader had. These elements were just replaced by the
        // server render, which has no idea anyone was mid-search.
        search.value = filters.search;
        department.value = filters.department;
        category.value = filters.category;
        status.value = filters.status;

        const normalize = (value) => (value || '').trim().toLocaleLowerCase();
        const total = new Set(rows.map((row) => row.dataset.reportId || row.dataset.search)).size;

        const applyFilters = () => {
            const term = normalize(search.value);
            const selectedDepartment = normalize(department.value);
            const selectedCategory = normalize(category.value);
            const selectedStatus = normalize(status.value);
            const reportVisibility = new Map();

            rows.forEach((row) => {
                const matchesSearch = !term || normalize(row.dataset.search).includes(term);
                const matchesDepartment = !selectedDepartment || normalize(row.dataset.department) === selectedDepartment;
                const matchesCategory = !selectedCategory || normalize(row.dataset.category) === selectedCategory;
                const matchesStatus = !selectedStatus || normalize(row.dataset.status) === selectedStatus;
                const matches = matchesSearch && matchesDepartment && matchesCategory && matchesStatus;
                const reportId = row.dataset.reportId || row.dataset.search;

                row.hidden = !matches;
                reportVisibility.set(reportId, matches);
            });

            const visible = Array.from(reportVisibility.values()).filter(Boolean).length;
            results.textContent = TRANSPARENCY_STRINGS.results.replace(':visible', visible).replace(':total', total);
            noResults.forEach((emptyState) => {
                emptyState.hidden = visible !== 0;
            });
        };

        const track = (key) => (event) => {
            filters[key] = event.target.value;
            applyFilters();
        };

        search.addEventListener('input', (event) => {
            caret = event.target.selectionStart;
            track('search')(event);
        });

        [department, category, status].forEach((control, index) => {
            control.addEventListener('change', track(FIELDS[index + 1]));
        });

        reset.addEventListener('click', () => {
            FIELDS.forEach((key) => { filters[key] = ''; });
            caret = null;
            search.value = '';
            department.value = '';
            category.value = '';
            status.value = '';
            applyFilters();
            search.focus();
        });

        // A poll that added rows must respect the filter already in force,
        // otherwise a search would appear to "fail" simply because new data
        // arrived.
        applyFilters();

        // And the caret with it. Losing focus mid-word is the single most
        // irritating thing a polling page can do.
        if (focusedField) {
            const field = document.getElementById(focusedField);

            if (field) {
                field.focus();

                if (typeof field.setSelectionRange === 'function') {
                    const position = caret === null ? (filters.search || '').length : caret;
                    try {
                        field.setSelectionRange(position, position);
                    } catch (error) {
                        // Not every input type supports selection ranges.
                    }
                }
            }
        }
    };

    // Focus is tracked on the document because the field it belongs to is
    // replaced by the time the poll is over - reading activeElement afterwards
    // would only ever find <body>.
    document.addEventListener('focusin', (event) => {
        if (FIELDS.includes(event.target.id.replace('complaint-', ''))) {
            focusedField = event.target.id;
        }
    });

    document.addEventListener('focusout', (event) => {
        if (event.target.id === focusedField) {
            focusedField = null;
            caret = null;
        }
    });

    const refresh = () => {
        refreshMap();
        bindFilters();
    };

    // Livewire 4 has no `livewire:updated` DOM event. It went looking for one
    // because that is what the Livewire 3 docs say, wrote the hook against it,
    // and the map then never refreshed once - silently, because a listener on
    // an event nobody dispatches fails exactly like code that was never written.
    // The event this page actually gets is the `morphed` hook, fired once the
    // component's DOM has been replaced.
    //
    // Guarded, because an unguarded `window.Livewire.hook(...)` throws outright
    // wherever Livewire has not booted, and a throw here takes the rest of this
    // page's initialisation with it - the map and the filters are set up below
    // this line, so the reader would get neither and no indication why. Without
    // the hook the register simply stops refreshing; with the page working, that
    // is the better failure.
    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        window.Livewire.hook('morphed', () => {
            refresh();
        });
    }

    // The first paint is server-rendered, so there is no morph to wait for and
    // nothing below has run yet. This is also harmless when Livewire does boot:
    // a second pass with unchanged data finds the same map payload and leaves
    // the map alone.
    refresh();
})();
</script>