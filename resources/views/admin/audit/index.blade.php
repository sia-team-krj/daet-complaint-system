@extends('admin.layouts.app')

@section('title', __('audit.title').' - Admin')
@section('admin-content')
@php
    // $auditQuery, $selectionParams, $auditActiveFilters, $actionLabelFor and
    // $scopeCount all come from the controller, so the page and the soft-
    // navigation fragments are drawn from exactly the same state.
    $actionOptions = $actions->mapWithKeys(fn (string $action): array => [
        $action => $actionLabelFor($action),
    ]);
@endphp
<div class="admin-page admin-page--audit">
    <header class="admin-page-header admin-page-header--audit">
        <div>
            <h1 class="admin-page-title">{{ __('audit.title') }}</h1>
            <p class="admin-page-description admin-page-description--audit">{{ __('audit.subtitle') }}</p>
        </div>
    </header>

    {{-- ─── The browser: scope, search, and the roster ──────────────────────── --}}
    <section class="admin-panel admin-audit-browser" aria-label="{{ __('audit.browse_directory') }}">

        {{-- What is on screen right now. --}}
        <div class="admin-audit-browser__scope" data-audit-scope>
            @include('admin.audit.partials.scope')
        </div>

        {{-- Find one person without scrolling the roster. --}}
        <div class="admin-audit-browser__bar">
            <form class="admin-audit-browser__search" method="GET" action="{{ route('admin.audit.index', $selectionParams) }}" data-people-search data-people-endpoint="{{ route('admin.audit.people') }}">
                <label class="admin-audit-browser__search-field">
                    <span class="sr-only">{{ __('audit.find_person') }}</span>
                    <svg class="admin-audit-browser__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" name="people_q" value="{{ $peopleQuery }}" placeholder="{{ __('audit.find_person') }}" autocomplete="off" spellcheck="false" data-people-input>
                </label>
                <button type="submit" class="admin-button admin-button--secondary admin-audit-browser__search-submit">{{ __('audit.search_people') }}</button>
            </form>
        </div>

        <details class="admin-audit-browser__disclosure" data-audit-directory-mobile @if($selection['active'] || $peopleQuery !== '') open @endif>
            <summary class="admin-audit-browser__disclosure-toggle">
                <span class="admin-audit-browser__disclosure-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M6 21V5l6-3 6 3v16"/><path d="M9 9h1"/><path d="M14 9h1"/><path d="M9 13h1"/><path d="M14 13h1"/><path d="M9 17h1"/><path d="M14 17h1"/></svg>
                </span>
                <span class="admin-audit-browser__disclosure-label">{{ __('audit.browse_directory') }}</span>
                <span class="admin-audit-browser__disclosure-chevron" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </span>
            </summary>

            <div class="admin-audit-browser__panes">
                {{-- Scopes: everyone, each office, and the records with no actor. --}}
                <nav class="admin-audit-browser__contexts" data-audit-contexts aria-label="{{ __('audit.scope') }}">
                    @include('admin.audit.partials.contexts')
                </nav>

                {{-- People. Bounded preview per group; "See all" opens one, paged. --}}
                <div class="admin-audit-browser__roster" id="admin-audit-browser-roster" data-audit-roster>
                    @if($searchResults !== null)
                        @include('admin.audit.partials.people-results', [
                            'rows' => $searchResults['rows'],
                            'total' => $searchResults['total'],
                            'query' => $peopleQuery,
                        ])
                    @else
                        @include('admin.audit.partials.roster', ['groups' => $groups])
                    @endif
                </div>
            </div>
        </details>
    </section>

    <section class="admin-filter-panel admin-filter-panel--audit" aria-label="{{ __('audit.filters') }}">
        <form data-loading-submit method="GET" action="{{ route('admin.audit.index', $selectionParams) }}" class="admin-filter-grid admin-filter-grid--audit admin-audit-filters" data-filter-form>
            <details class="admin-audit-filters-collapsible" data-audit-filters-mobile @if($auditActiveFilters->isNotEmpty()) open @endif>
                <summary class="admin-audit-filters-toggle">
                    <span class="admin-audit-filters-toggle__icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5h18"/><path d="M6 12h12"/><path d="M10 19h4"/></svg>
                    </span>
                    <span class="admin-audit-filters-toggle__label">{{ __('audit.filters') }}</span>
                    @if($auditActiveFilters->isNotEmpty())
                        <span class="admin-audit-filters-badge">{{ $auditActiveFilters->count() }}</span>
                    @endif
                    <span class="admin-audit-filters-toggle__chevron" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </summary>
                <div class="admin-audit-filters-collapsible__body">
                    <label class="admin-field admin-audit-filter-action">
                        <span>{{ __('audit.action') }}</span>
                        <select name="action" class="admin-input">
                            <option value="">{{ __('audit.all_actions') }}</option>
                            @foreach($actionOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="admin-filter-actions admin-audit-filter-actions">
                        <button type="submit" class="admin-button admin-button--primary">{{ __('audit.filter_trail') }}</button>
                        @if($auditActiveFilters->isNotEmpty())
                            <a href="{{ route('admin.audit.index', $selectionParams) }}" class="admin-button admin-button--secondary">{{ __('audit.clear') }}</a>
                        @endif
                    </div>
                </div>
            </details>

            <label class="admin-field admin-field--wide admin-audit-filter-search">
                <span>{{ __('audit.search') }}</span>
                <input type="search" name="search" class="admin-input" value="{{ request('search') }}" placeholder="{{ __('audit.search_placeholder') }}">
            </label>
        </form>
    </section>

    <div data-audit-trail>
        @include('admin.audit.partials.trail')
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Soft navigation ────────────────────────────────────────────────────
    // The page is drawn once. Every scope change, page turn, filter and
    // search is answered with rendered fragments and swapped in, so the browser
    // never redraws the whole page and the search field keeps its focus.
    // The URL stays the truth: it is updated through the history API, so Back,
    // Forward, a refresh and a shared link all behave exactly as before.
    const auditIndexPath = new URL('{{ route('admin.audit.index') }}', window.location.origin).pathname;
    const browserEndpoint = '{{ route('admin.audit.browser') }}';
    const peopleEndpoint = '{{ route('admin.audit.people') }}';

    const scopeEl = document.querySelector('[data-audit-scope]');
    const railEl = document.querySelector('[data-audit-contexts]');
    const rosterEl = document.querySelector('[data-audit-roster]');
    const trailEl = document.querySelector('[data-audit-trail]');

    const jsonHeaders = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

    async function fetchJson(url) {
        const response = await fetch(url, { headers: jsonHeaders });
        if (!response.ok) throw new Error('request failed');
        return response.json();
    }

    function applyFragments(payload, parts) {
        if (!payload) return;
        if (parts.indexOf('scope') !== -1 && scopeEl) scopeEl.innerHTML = payload.scope;
        if (parts.indexOf('rail') !== -1 && railEl) railEl.innerHTML = payload.rail;
        if (parts.indexOf('roster') !== -1 && rosterEl) rosterEl.innerHTML = payload.roster;
        if (parts.indexOf('trail') !== -1 && trailEl) trailEl.innerHTML = payload.trail;
    }

    function browserUrl(url) {
        const endpoint = new URL(browserEndpoint, window.location.origin);
        endpoint.search = url.search;
        return endpoint.toString();
    }

    async function navigate(url, parts, push) {
        try {
            const payload = await fetchJson(browserUrl(url));
            applyFragments(payload, parts);
            if (push !== false) history.pushState({ soft: true }, '', url.toString());
        } catch (error) {
            // A failed request must never leave the admin looking at stale
            // fragments: fall back to the ordinary page.
            window.location.href = url.toString();
        }
    }

    // Any link inside the browser or the trail is answered in place.
    document.addEventListener('click', function(event) {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

        const url = new URL(link.getAttribute('href'), window.location.origin);
        if (url.pathname !== auditIndexPath) return;

        // Any link to this page is a change of state, not a change of page:
        // a scope, a person, a group page or the trail's pager. Deciding by
        // path rather than by "is this link inside a region" means a link that
        // moves around the markup still swaps in place.
        event.preventDefault();
        navigate(url, ['scope', 'rail', 'roster', 'trail']);
    });

    // Back and forward move through the same history.
    window.addEventListener('popstate', function() {
        navigate(new URL(window.location.href), ['scope', 'rail', 'roster', 'trail'], false);
    });

    // ─── The trail's own filters ────────────────────────────────────────────
    const filterForm = document.querySelector('[data-filter-form]');
    const searchInput = document.querySelector('.admin-audit-filter-search input[name="search"]');

    if (filterForm) {
        filterForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // getAttribute, not .action: the form has a control named "action",
            // and a named control shadows the form's own properties.
            const url = new URL(filterForm.getAttribute('action'), window.location.origin);
            const data = new FormData(filterForm);

            ['action', 'search'].forEach(function(key) {
                const value = data.get(key);
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });

            // A new filter starts at the first page of the trail.
            url.searchParams.delete('page');

            navigate(url, ['scope', 'rail', 'roster', 'trail']);
        });
    }

    if (searchInput && filterForm) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            window.clearTimeout(searchTimeout);
            searchTimeout = window.setTimeout(function() {
                filterForm.requestSubmit();
            }, 500);
        });
    }

    // Desktop: force <details> open (closed details hides content even with display:contents)
    const detailsEls = document.querySelectorAll('details.admin-audit-filters-collapsible[data-audit-filters-mobile], details.admin-audit-browser__disclosure[data-audit-directory-mobile]');
    const mq = window.matchMedia('(min-width: 641px)');
    function syncDetails() {
        detailsEls.forEach(function(d) {
            if (mq.matches) { d.open = true; d.dataset.wasOpenMobile = d.dataset.wasOpenMobile || (d.hasAttribute('open') ? '1' : '0'); }
            else { /* restore server-rendered default: keep current state on resize back */ }
        });
    }
    if (mq.addEventListener) mq.addEventListener('change', syncDetails); else mq.addListener(syncDetails);
    syncDetails();

    // ─── People type-ahead ──────────────────────────────────────────────────
    // The roster is rendered server-side. While the admin types, the same
    // fragment is fetched from the JSON endpoint and swapped in, so one Blade
    // file owns the markup. Without JavaScript the form submits and the page
    // renders the results — the type-ahead is an enhancement, never the only
    // path.
    const peopleForm = document.querySelector('[data-people-search]');
    const peopleInput = peopleForm && peopleForm.querySelector('[data-people-input]');
    const peopleRoster = document.querySelector('[data-audit-roster]');

    if (peopleForm && peopleInput && peopleRoster) {
        let peopleTimer = null;
        let peopleRequest = null;

        // The endpoint answers with the roster for the current scope, so the
        // scope and the trail filters travel with the query.
        function peopleEndpointUrl(query) {
            const url = new URL(peopleEndpoint, window.location.origin);
            const current = new URLSearchParams(window.location.search);

            ['department_id', 'actor_id', 'system', 'residents', 'search', 'action'].forEach(function(key) {
                const value = current.get(key);
                if (value) url.searchParams.set(key, value);
            });

            url.searchParams.set('q', query);

            return url.toString();
        }

        // The address bar mirrors the search, so the view can be shared.
        function peoplePageUrl(query) {
            const url = new URL(window.location.href);

            if (query) {
                url.searchParams.set('people_q', query);
            } else {
                url.searchParams.delete('people_q');
            }

            url.searchParams.delete('people_group');
            url.searchParams.delete('people_page');

            return url.toString();
        }

        async function refreshRoster(query) {
            if (peopleRequest) peopleRequest.abort();
            peopleRequest = new AbortController();

            const response = await fetch(peopleEndpointUrl(query), {
                headers: jsonHeaders,
                signal: peopleRequest.signal,
            });

            if (!response.ok) throw new Error('people search failed');

            const payload = await response.json();
            peopleRoster.innerHTML = payload.roster;
            history.replaceState({ soft: true }, '', peoplePageUrl(query));
        }

        peopleInput.addEventListener('input', function() {
            window.clearTimeout(peopleTimer);
            peopleTimer = window.setTimeout(function() {
                refreshRoster(peopleInput.value.trim()).catch(function() {
                    // A failed request must never blank the roster: the
                    // server-rendered search still works through the form.
                });
            }, 250);
        });

        peopleForm.addEventListener('submit', function(event) {
            event.preventDefault();
            window.clearTimeout(peopleTimer);

            refreshRoster(peopleInput.value.trim()).catch(function() {
                // Fall back to the server-rendered search.
                peopleForm.submit();
            });
        });
    }
});
</script>
@endsection
