@extends($mainLayout)

@section('title', __('transparency.register.all_heading'))

@section('content')
    @push('styles')
        @include('partials.public-ui')
        @include('pages.transparency.partials.component-styles')
        @include('pages.transparency.partials.complaints-styles')
    @endpush

    <section class="complaints-page" style="background: var(--surface); min-height: calc(100svh - var(--navbar-height));">
        <div class="tr-container">
            <h1 class="tr-h1 complaints-page-title">{{ __('transparency.register.all_heading') }}</h1>
        </div>

        <div class="tr-container complaints-wrap">
        <div class="tr-card tr-filters-card">
            <form data-loading-submit method="GET" action="{{ route('transparency.complaints') }}" id="complaints-filter-form">
                {{-- Row 1: search + Filters button --}}
                <div class="cf-row1">
                    <label class="tr-search" for="complaints-search">
                        <span class="sr-only">{{ __('transparency.register.search_label') }}</span>
                        <svg class="tr-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="18" height="18"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input id="complaints-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('transparency.register.search_placeholder_short') }}" maxlength="60" autocomplete="off">
                    </label>
                    <button type="button" class="cf-filters-btn" id="cf-toggle" aria-expanded="false" aria-controls="cf-panel">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="18" height="18"><line x1="4" y1="6" x2="20" y2="6"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="10" y1="18" x2="14" y2="18"/></svg>
                        <span>{{ __('transparency.register.filters') }}</span>
                        @php $panelCount = (int)(($filters['department'] !== '') + ($filters['category'] !== '') + ($filters['sort'] !== 'reported_desc')); @endphp
                        @if($panelCount > 0)
                            <span class="cf-count-badge">{{ $panelCount }}</span>
                        @endif
                    </button>
                </div>

                {{-- Row 2: status chips. The totals follow the other filters, so
                     the row is swapped with the results. --}}
                <div data-cf-chips>
                    @include('pages.transparency.partials.complaints-chips')
                </div>

                {{-- Panel (hidden by default with JS) --}}
                <div class="cf-panel" id="cf-panel">
                    <div class="cf-panel-grid">
                        <label class="cf-field">
                            <span class="cf-label">{{ __('transparency.register.department_label') }}</span>
                            <select name="department">
                                <option value="">{{ __('transparency.register.all_departments') }}</option>
                                @foreach($departmentOptions as $dept)
                                    <option value="{{ $dept }}" @selected($filters['department'] === $dept)>{{ $dept }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="cf-field">
                            <span class="cf-label">{{ __('transparency.register.category_label') }}</span>
                            <select name="category">
                                <option value="">{{ __('transparency.register.all_categories') }}</option>
                                @foreach($categoryOptions as $cat)
                                    <option value="{{ $cat['key'] }}" @selected($filters['category'] === $cat['key'])>{{ $cat['label'] }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="cf-field">
                            <span class="cf-label">{{ __('transparency.register.sort_label') }}</span>
                            <select name="sort">
                                <option value="reported_desc" @selected($filters['sort'] === 'reported_desc')>{{ __('transparency.register.sort_reported_desc') }}</option>
                                <option value="reported_asc" @selected($filters['sort'] === 'reported_asc')>{{ __('transparency.register.sort_reported_asc') }}</option>
                            </select>
                        </label>
                    </div>
                    <div class="cf-panel-actions">
                        <button type="submit" class="cf-apply">{{ __('transparency.register.apply_filters') }}</button>
                        @if($anyFilter)
                            <a href="{{ route('transparency.complaints') }}" class="cf-clear">{{ __('transparency.register.clear_all') }}</a>
                        @endif
                    </div>
                </div>

                {{-- Active filter chips --}}
                <div data-cf-active>
                    @include('pages.transparency.partials.complaints-active')
                </div>
            </form>
        </div>

        <div data-cf-results>
            @include('pages.transparency.partials.complaints-results')
        </div>
    </div>
</section>

    @if($anyFilter)
        @push('head')
            @include('pages.transparency.partials.complaints-head')
        @endpush
    @endif
@endsection

@push('scripts')
<script>
(() => {
    document.documentElement.classList.add('js');

    // ─── The filter panel disclosure ───────────────────────────────────────
    const panel = document.getElementById('cf-panel');
    const btn = document.getElementById('cf-toggle');
    if (panel) panel.setAttribute('hidden', '');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    if (btn && panel) {
        btn.addEventListener('click', () => {
            const open = panel.hasAttribute('hidden');
            if (open) panel.removeAttribute('hidden'); else panel.setAttribute('hidden', '');
            btn.setAttribute('aria-expanded', String(open));
        });
    }

    // ─── Soft navigation ───────────────────────────────────────────────────
    // The feed is drawn once. Filtering, chipping and paging swap the changed
    // regions in place, so a visitor keeps the page, the scroll position and
    // the search text they typed. The URL stays the truth — Back, Forward, a
    // refresh and a shared link all behave — and without JavaScript every link
    // and form is still an ordinary one.
    const pagePath = new URL('{{ route('transparency.complaints') }}', window.location.origin).pathname;
    const endpoint = '{{ route('transparency.complaints.browser') }}';

    const chipsEl = document.querySelector('[data-cf-chips]');
    const activeEl = document.querySelector('[data-cf-active]');
    const resultsEl = document.querySelector('[data-cf-results]');
    const form = document.getElementById('complaints-filter-form');

    const jsonHeaders = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

    function scrollActiveChipIntoView() {
        const active = document.querySelector('.cf-chip--active');
        if (active && active.scrollIntoView) active.scrollIntoView({ inline: 'center', block: 'nearest' });
    }

    async function navigate(url, push) {
        try {
            const target = new URL(endpoint, window.location.origin);
            target.search = url.search;

            const response = await fetch(target, { headers: jsonHeaders });
            if (!response.ok) throw new Error('request failed');

            const payload = await response.json();

            // The page asked for a page past the end: follow it.
            if (payload.redirect) {
                window.location.href = payload.redirect;
                return;
            }

            if (chipsEl) chipsEl.innerHTML = payload.chips;
            if (activeEl) activeEl.innerHTML = payload.active;
            if (resultsEl) resultsEl.innerHTML = payload.results;

            // A filtered feed must not be indexed.
            const robots = document.head.querySelector('meta[name="robots"]');
            if (robots) robots.remove();
            if (payload.head) {
                const holder = document.createElement('div');
                holder.innerHTML = payload.head;
                while (holder.firstChild) document.head.appendChild(holder.firstChild);
            }

            if (push !== false) history.pushState({ soft: true }, '', url.toString());

            scrollActiveChipIntoView();
        } catch (error) {
            // Never leave the visitor looking at a half-updated feed.
            window.location.href = url.toString();
        }
    }

    // The filter form: the same URL a submit would have produced.
    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const url = new URL(form.getAttribute('action'), window.location.origin);
            const data = new FormData(form);

            ['q', 'department', 'category', 'sort'].forEach((key) => {
                const value = data.get(key);
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });

            // A new filter starts at the first page.
            url.searchParams.delete('page');

            navigate(url);
        });

        // The selects apply themselves.
        form.querySelectorAll('select').forEach((sel) => {
            sel.addEventListener('change', () => form.requestSubmit());
        });
    }

    // The search box applies on Enter.
    const search = document.getElementById('complaints-search');
    if (search && form) {
        search.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                form.requestSubmit();
            }
        });
    }

    // Any link to this page is a change of state, not a change of page: the
    // status chips, the active chips, the pager and "clear all" all live on
    // this path. Deciding by path rather than by "is this link inside a
    // region" means a link that moves around the markup still swaps in place.
    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

        const url = new URL(link.getAttribute('href'), window.location.origin);
        if (url.pathname !== pagePath) return;

        event.preventDefault();
        navigate(url);
    });

    // Back and forward move through the same history.
    window.addEventListener('popstate', () => {
        navigate(new URL(window.location.href), false);
    });

    scrollActiveChipIntoView();
})();
</script>
@endpush