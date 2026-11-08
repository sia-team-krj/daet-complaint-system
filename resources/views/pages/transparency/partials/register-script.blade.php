{{--
    The register's map and filters.

    The register above is a Livewire component that re-renders on a timer,
    and the morph reconciles the existing nodes in place: the search box and
    the filter controls are the same objects after a poll, with their
    contents replaced by the server. That rules out attaching listeners per
    refresh - they would pile up on a node that never goes away. So every
    interaction is delegated from the document, which is bound exactly once,
    and the per-refresh work is limited to re-applying the reader's filter
    state and rebuilding the map only when its payload moved.
--}}
<script>
(() => {
    'use strict';

    // What the reader has entered or chosen. Defaults match what the server
    // renders, so an untouched page is indistinguishable from a tracked one.
    const filters = { search: '', department: '', category: '', status: '', sort: 'reported_desc', barangay: '' };
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
        results: "{{ __('transparency.register.results') }}",
    };

    /* ── Small helpers ─────────────────────────────────────────────── */

    const parseAttribute = (element, name, fallback) => {
        try {
            return JSON.parse(element.dataset[name] ?? '') ?? fallback;
        } catch (error) {
            return fallback;
        }
    };

    // The heat colours, read from the shared tokens rather than hard-coded,
    // so a palette change is one file, not two.
    const token = (name, fallback) => {
        try {
            const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            return value !== '' ? value : fallback;
        } catch (error) {
            return fallback;
        }
    };

    const normalize = (value) => (value || '').trim().toLocaleLowerCase();

    /* ── Leaflet handshake ─────────────────────────────────────────── */

    // Leaflet is bundled rather than fetched from a CDN, but a bundled
    // module is deferred and this inline script is not, so the map is built
    // from a callback the bundle calls instead of by reaching for window.L
    // and finding it undefined on a page that is otherwise fine. Only ever
    // registered once, however many times the register re-renders.
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

    /* ── The map ───────────────────────────────────────────────────── */

    // The payload lives in data attributes rather than a JSON <script>
    // island because this markup is inside a Livewire component, and
    // Livewire treats a script tag it finds there as code to run. An
    // attribute is just text.
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
            // A morph may already have removed everything Leaflet put inside
            // the container. Tearing down in that state is allowed to fail:
            // this is cleanup for an object being discarded either way, and
            // letting it throw would abandon the rest of the refresh pass.
            try {
                map.remove();
            } catch (error) {
                // Nothing to salvage - the next build makes a fresh map.
            }

            map = null;
        }
    };

    const buildMap = (payload) => {
        // Re-queried every time: the map mounts into the polled component,
        // so a captured reference goes stale on the next morph.
        const mapElement = document.getElementById('transparency-map');

        if (!mapElement) return;

        if (!payload) {
            setStatus(TRANSPARENCY_STRINGS.mapUnavailableLoad);
            return;
        }

        const points = payload.points;
        const center = payload.center;
        const isMobile = (typeof window.L !== 'undefined' && window.L.Browser && window.L.Browser.mobile)
            || (typeof window.matchMedia === 'function' && window.matchMedia('(pointer: coarse)').matches);

        whenLeafletReady(() => {
            destroyMap();

            map = window.L.map(mapElement, {
                scrollWheelZoom: false,
                zoomControl: true,
                // One-finger pan would trap the page scroll under the map on
                // touch screens, so mobile pans with two fingers instead and
                // a hint on the card says so.
                dragging: !isMobile,
                tap: false,
            }).setView(center, 13);

            // Two-finger pan: drag only while two or more fingers are down,
            // so a single finger scrolls the page past the map.
            if (isMobile) {
                const el = map.getContainer();

                el.addEventListener('touchstart', (event) => {
                    if (event.touches.length >= 2 && map) map.dragging.enable();
                    // Two-finger pan discovered once — retire the hint.
                    const hint = document.getElementById('map-hint');
                    if (hint) hint.style.display = 'none';
                }, { passive: true });

                el.addEventListener('touchend', (event) => {
                    if (event.touches.length < 2 && map) map.dragging.disable();
                }, { passive: true });
            }

            /*
             * Leaflet measures its container once, when the map is built,
             * and caches that. A desktop viewport has settled by then; a
             * phone has not. The URL bar collapses, scrollbars come and go,
             * a late webfont reflows the card around the map, and the heat
             * layer's own canvas is sized from the same stale figure. Any
             * of those leaves the map drawn for a size the container no
             * longer has.
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

            // No keyless, calm raster style is available right now (CARTO
            // basemaps moved behind an API key), so the stock OpenStreetMap
            // tiles are used with a muted tile pane (see .leaflet-tile-pane
            // in the component CSS) — the deliberate trade: full attribution
            // stays on the map, and the heat colours still carry the signal.
            window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                maxZoom: 19,
            }).addTo(map);

            // Hide the loading skeleton once the map is successfully built.
            hideMapStatus();

            const heatLayerAvailable = typeof window.L.heatLayer === 'function';

            if (points.length > 0 && heatLayerAvailable) {
                // Read the heat ramp from the shared tokens so a palette
                // change lands in one file. If any token is missing, fall
                // back to Leaflet's default ramp rather than a second
                // hard-coded palette.
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
                        ...(heatGradient ? { gradient: heatGradient } : {}),
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
            }
        });
    };

    // Is the built map still attached to the live document? A morph
    // reconciles the container in place: the node can be the same object
    // while everything Leaflet built inside it has been removed, so the
    // signal has to be Leaflet's own panes.
    const mapHasBeenDetached = () => {
        if (!map) return false;

        const container = document.getElementById('transparency-map');

        if (!container || !container.isConnected) {
            return true;
        }

        if (typeof map.getContainer === 'function' && map.getContainer() !== container) {
            return true;
        }

        return !container.querySelector('.leaflet-pane');
    };

    // Rebuilt only when it would actually change something: the data moved,
    // or the container the map was mounted into was replaced by a poll.
    const refreshMap = () => {
        const payload = readMapPayload();

        if (!payload) return;

        const signature = JSON.stringify(payload);
        const dataMoved = signature !== lastMapSignature;

        if (!dataMoved && !mapHasBeenDetached()) return;

        lastMapSignature = signature;
        buildMap(payload);
    };

    /* ── Filters ────────────────────────────────────────────────────── */

    const applyFilters = () => {
        const search = document.getElementById('complaint-search');
        const department = document.getElementById('complaint-department');
        const category = document.getElementById('complaint-category');
        const sort = document.getElementById('complaint-sort');
        const clearFilters = document.getElementById('clear-filters');
        const results = document.getElementById('results-summary');
        const noResults = Array.from(document.querySelectorAll('[data-no-results]'));
        const rows = Array.from(document.querySelectorAll('[data-report-row]'));
        const statusChips = Array.from(document.querySelectorAll('.status-chip'));

        if (!search || !department || !category || !sort || !clearFilters || !results) {
            return;
        }

        // Restore what the reader had, in case these nodes were re-created.
        if (search.value !== filters.search) search.value = filters.search;
        if (department.value !== filters.department) department.value = filters.department;
        if (category.value !== filters.category) category.value = filters.category;
        if (sort.value !== filters.sort) sort.value = filters.sort;

        statusChips.forEach((chip) => {
            chip.classList.toggle('selected', chip.dataset.value === filters.status);
        });

        const term = normalize(filters.search);
        const selectedDepartment = normalize(filters.department);
        const selectedCategory = normalize(filters.category);
        const selectedStatus = normalize(filters.status);
        const selectedBarangay = normalize(filters.barangay);
        const selectedSort = filters.sort;
        let visible = 0;

        rows.forEach((row) => {
            const matchesSearch = !term || normalize(row.dataset.search).includes(term);
            const matchesDepartment = !selectedDepartment || normalize(row.dataset.department) === selectedDepartment;
            const matchesCategory = !selectedCategory || normalize(row.dataset.category) === selectedCategory;
            const matchesStatus = !selectedStatus || normalize(row.dataset.status) === selectedStatus;
            const matchesBarangay = !selectedBarangay || normalize(row.dataset.barangay) === selectedBarangay;
            const matches = matchesSearch && matchesDepartment && matchesCategory && matchesStatus && matchesBarangay;

            row.hidden = !matches;
            if (matches) visible += 1;
        });

        results.textContent = TRANSPARENCY_STRINGS.results
            .replace(':visible', String(visible))
            .replace(':total', String(rows.length));

        noResults.forEach((emptyState) => {
            // Only meaningful when the register actually has rows to filter —
            // with zero rows at all, the "no complaints yet" state speaks.
            emptyState.hidden = visible !== 0 || rows.length === 0;
        });

        const sortedRows = rows
            .filter((row) => !row.hidden)
            .sort((a, b) => {
                if (selectedSort === 'reported_desc') {
                    return (b.dataset.isoDate || '') < (a.dataset.isoDate || '') ? -1 : 1;
                }
                if (selectedSort === 'reported_asc') {
                    return (a.dataset.isoDate || '') < (b.dataset.isoDate || '') ? -1 : 1;
                }
                if (selectedSort === 'status_asc') {
                    const sa = (a.dataset.status || '').localeCompare(b.dataset.status || '');
                    if (sa !== 0) return sa;
                    return (a.dataset.reportId || '').localeCompare(b.dataset.reportId || '');
                }
                return 0;
            });

        const list = document.getElementById('complaint-table-body');
        if (list) {
            sortedRows.forEach((row) => list.appendChild(row));
        }

        const anyFilterActive =
            filters.search !== '' ||
            filters.department !== '' ||
            filters.category !== '' ||
            filters.status !== '' ||
            filters.barangay !== '' ||
            filters.sort !== 'reported_desc';

        if (clearFilters) clearFilters.hidden = !anyFilterActive;

        // Keep the barangay chip and the Top areas pressed state in step.
        const chip = document.getElementById('barangay-chip');
        if (chip) {
            chip.hidden = filters.barangay === '';
            if (filters.barangay !== '') {
                const button = document.querySelector('[data-barangay-filter="' + CSS.escape(filters.barangay) + '"]');
                const label = chip.querySelector('.tr-barangay-chip__label');
                if (label) label.textContent = button && button.dataset.barangayLabel ? button.dataset.barangayLabel : filters.barangay;
            }
        }

        document.querySelectorAll('[data-barangay-filter]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.barangayFilter === filters.barangay));
        });
    };

    const resetFilters = () => {
        filters.search = '';
        filters.department = '';
        filters.category = '';
        filters.status = '';
        filters.barangay = '';
        filters.sort = 'reported_desc';
        caret = null;
        applyFilters();
    };

    /* ── Delegated interaction (bound once) ────────────────────────── */

    document.addEventListener('input', (event) => {
        if (event.target.id !== 'complaint-search') return;
        caret = event.target.selectionStart;
        filters.search = event.target.value;
        applyFilters();
    });

    document.addEventListener('change', (event) => {
        const id = event.target.id;

        if (id === 'complaint-department') filters.department = event.target.value;
        else if (id === 'complaint-category') filters.category = event.target.value;
        else if (id === 'complaint-sort') filters.sort = event.target.value;
        else return;

        applyFilters();
    });

    document.addEventListener('click', (event) => {
        const chip = event.target.closest('.status-chip');

        if (chip && chip.closest('#filter-bar')) {
            filters.status = chip.dataset.value || '';
            applyFilters();
            return;
        }

        if (event.target.closest('#clear-filters') || event.target.closest('#clear-filters-list')) {
            resetFilters();
            const search = document.getElementById('complaint-search');
            if (search) search.focus();
            return;
        }

        const area = event.target.closest('[data-barangay-filter]');

        if (area) {
            filters.barangay = filters.barangay === area.dataset.barangayFilter
                ? ''
                : area.dataset.barangayFilter;
            applyFilters();
            return;
        }

        if (event.target.closest('#barangay-chip')) {
            filters.barangay = '';
            applyFilters();
            return;
        }

        const toggle = event.target.closest('#top-areas-toggle');

        if (toggle) {
            const areas = document.getElementById('top-areas');
            const label = toggle.querySelector('.tr-areas__toggle-label');

            if (areas) {
                const expanded = areas.dataset.expanded === 'true';
                areas.dataset.expanded = String(!expanded);
                toggle.setAttribute('aria-expanded', String(!expanded));

                if (label) {
                    label.textContent = !expanded
                        ? (label.dataset.showLess ?? label.textContent)
                        : (label.dataset.showAll ?? label.textContent);
                }
            }

            return;
        }

        if (event.target.closest('[data-retry]')) {
            window.location.reload();
        }
    });

    // Focus is tracked on the document: after a morph the "current" element
    // is whatever Livewire left under the caret, not the field the reader
    // picked.
    const TRACKED = ['complaint-search', 'complaint-department', 'complaint-category', 'complaint-sort'];

    document.addEventListener('focusin', (event) => {
        if (TRACKED.includes(event.target.id)) {
            focusedField = event.target.id;
        }
    });

    document.addEventListener('focusout', (event) => {
        if (event.target.id === focusedField) {
            focusedField = null;
            caret = null;
        }
    });

    /* ── Per-render work ───────────────────────────────────────────── */

    const showError = () => {
        const error = document.querySelector('[data-error]');

        if (error) error.hidden = false;
    };

    const refresh = () => {
        try {
            refreshMap();
            applyFilters();
        } catch (error) {
            // A failed refresh must not take the register with it, but it
            // must say something rather than freeze silently.
            showError();
            return;
        }

        // The caret is restored on every morph, so a reader mid-word is
        // not thrown to the top of the field.
        if (focusedField) {
            const field = document.getElementById(focusedField);

            if (field) {
                field.focus();

                if (typeof field.setSelectionRange === 'function') {
                    const position = caret === null ? (filters.search || '').length : caret;
                    try {
                        field.setSelectionRange(position, position);
                    } catch (selectionError) {
                        // Not every input type supports selection ranges.
                    }
                }
            }
        }
    };

    // Livewire 4's morphed hook is what this page actually gets once the
    // component's DOM has been replaced. Guarded, because an unguarded
    // window.Livewire.hook(...) throws outright wherever Livewire has not
    // booted.
    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        window.Livewire.hook('morphed', () => {
            refresh();
        });
    }

    // The first paint is server-rendered, so there is no morph to wait for
    // and nothing below has run yet.
    refresh();
})();
</script>
