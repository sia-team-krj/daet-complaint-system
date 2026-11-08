{{--
    The live public register.

    Everything in here is derived from one query in TransparencyFeed, so all of
    it re-renders together. Anything on this page that does NOT come from that
    query - the styles, the map and filter scripts - stays in the page view,
    because a component that re-renders a timer does not need to re-render the
    static chrome along with it.

    The map payload below is a data attribute rather than a <script> island on
    purpose: Livewire treats a <script> it finds inside a component as code to
    evaluate, and this file reads it with JSON.parse.

    Row markup is rendered exactly once. register-script.blade.php moves every
    [data-report-row] element into #complaint-table-body when it re-sorts, so a
    second mobile-only copy of the rows would land in the wrong place. The
    same <a> is a grid on desktop and a flex card on mobile; CSS decides which.
--}}
<div class="transparency-page"
     wire:poll.{{ \App\Livewire\TransparencyRegister::REFRESH_SECONDS }}s.visible>

    @push('styles')
        @include('pages.transparency.partials.component-styles')
    @endpush

    {{-- ════════════ BANNER ════════════ --}}
    <header class="tr-banner">
        <div class="tr-container">
            <h1 class="tr-h1">{{ __('transparency.header.title') }}</h1>
            <span class="tr-goldbar" aria-hidden="true"></span>
            <p class="tr-privacy">{{ __('transparency.header.privacy') }}</p>

            <details class="tr-how" wire:ignore>
                <summary>
                    {{ __('transparency.header.privacy_link') }}
                    <svg class="tr-how__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="tr-how__card">
                    <ul>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg>
                            <span>{{ __('transparency.v2.how_bullet_1') }}</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg>
                            <span>{{ __('transparency.v2.how_bullet_2') }}</span>
                        </li>
                        <li>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg>
                            <span>{{ __('transparency.v2.how_bullet_3') }}</span>
                        </li>
                    </ul>
                </div>
            </details>

            <p class="tr-updated">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                @if($lastUpdated)
                    {{ __('transparency.header.updated') }} {{ $lastUpdated }}
                @else
                    {{ __('transparency.v2.no_reports_yet') }}
                @endif
            </p>
        </div>
    </header>

    {{-- ════════════ STAT STRIP (overlapping the banner) ════════════ --}}
    <div class="tr-container">
        <div class="tr-stats" role="list" aria-label="{{ __('transparency.v2.stat_total') }}">
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--navy">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['total'] }}</span>
                    <span class="tr-stat__label">{{ __('transparency.v2.stat_total') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.56"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['in_progress'] }}</span>
                    <span class="tr-stat__label">{{ __('transparency.v2.stat_in_progress') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['resolved'] }}</span>
                    <span class="tr-stat__label">{{ __('transparency.v2.stat_resolved') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--sky">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['resolution_rate'] === null ? '—' : round($stats['resolution_rate'] * 100) . '%' }}</span>
                    <span class="tr-stat__label">{{ __('transparency.v2.stat_rate') }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- ════════════ MAP + TOP AREAS ════════════ --}}
    <section aria-labelledby="map-heading">
        <div class="tr-container">
            <div class="tr-map-grid">
                <div class="tr-card tr-map-card">
                    <div class="tr-map-card__head">
                        <h2 class="tr-h2" id="map-heading">{{ __('transparency.map.heading') }}</h2>
                        <div class="tr-legend">
                            <span class="tr-legend__label">{{ __('transparency.map.legend_low') }}</span>
                            <span class="tr-legend__bar" role="img" aria-label="{{ __('transparency.v2.legend_gradient_aria') }}"></span>
                            <span class="tr-legend__label">{{ __('transparency.map.legend_high') }}</span>
                        </div>
                    </div>
                    <div class="tr-map" id="map-container">
                        <div id="transparency-map" class="tr-map__canvas"></div>
                        <div id="map-status" class="tr-map-status" aria-live="polite" role="status">
                            <span class="sr-only">{{ __('transparency.map.loading') }}</span>
                            <span class="tr-map-status__skeleton" aria-hidden="true"></span>
                        </div>
                        <p class="tr-map__hint" id="map-hint">{{ __('transparency.v2.two_finger') }}</p>
                    </div>
                </div>

                <aside class="tr-card tr-areas" id="top-areas" aria-labelledby="top-areas-heading">
                    <h2 class="tr-h2" id="top-areas-heading">{{ __('transparency.v2.top_areas') }}</h2>

                    @if($topAreas->isNotEmpty())
                        <ol class="tr-areas__list" id="top-areas-list">
                            @foreach($topAreas as $index => $area)
                                <li class="tr-areas__item {{ $index >= 3 ? 'tr-areas__item--extra' : '' }}">
                                    <button
                                        type="button"
                                        class="tr-areas__row"
                                        data-barangay-filter="{{ $area['barangay'] }}"
                                        data-barangay-label="{{ $area['label'] }}"
                                        aria-pressed="false"
                                    >
                                        <span class="tr-areas__rank">{{ $index + 1 }}</span>
                                        <span class="tr-areas__name">{{ $area['label'] }}</span>
                                        <span class="tr-areas__count">{{ __('transparency.v2.reports_count', ['count' => $area['count']]) }}</span>
                                        <span class="tr-areas__bar" aria-hidden="true">
                                            <span class="tr-areas__fill" style="width: {{ $area['share'] }}%"></span>
                                        </span>
                                    </button>
                                </li>
                            @endforeach
                        </ol>
                        @if($topAreas->count() > 3)
                            <button type="button" class="tr-areas__toggle" id="top-areas-toggle" aria-expanded="false" aria-controls="top-areas-list">
                                <span class="tr-areas__toggle-label" data-show-all="{{ __('transparency.v2.show_all') }}" data-show-less="{{ __('transparency.v2.show_less') }}">{{ __('transparency.v2.show_all') }}</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                        @endif
                    @else
                        <div class="tr-areas__empty">
                            <span class="tr-areas__empty-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            </span>
                            <p>{{ __('transparency.v2.top_areas_empty') }}</p>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>

    {{-- ════════════ REGISTER ════════════ --}}
    <section class="tr-register" aria-labelledby="register-heading">
        <div class="tr-container">
            <div class="tr-register-head">
                <h2 class="tr-h2" id="register-heading">{{ __('transparency.register.heading') }}</h2>
                <p class="tr-results" id="results-summary" aria-live="polite">
                    {{ __('transparency.register.results', ['visible' => $reports->count(), 'total' => $reports->count()]) }}
                </p>
            </div>

            {{-- Filter card --}}
            <div class="tr-card tr-filters" id="filter-bar">
                <div class="tr-filters__row1">
                    <label class="tr-search" for="complaint-search">
                        <span class="sr-only">{{ __('transparency.register.search_label') }}</span>
                        <svg class="tr-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input
                            id="complaint-search"
                            type="search"
                            placeholder="{{ __('transparency.register.search_placeholder') }}"
                            autocomplete="off"
                        >
                    </label>
                    <div class="tr-selects">
                        <div class="tr-select tr-select--department">
                            <span class="sr-only" id="department-label">{{ __('transparency.register.department_label') }}</span>
                            <select id="complaint-department" aria-labelledby="department-label">
                                <option value="">{{ __('transparency.register.all_departments') }}</option>
                                @foreach($departmentOptions as $department)
                                    <option value="{{ $department }}">{{ $department }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--category">
                            <span class="sr-only" id="category-label">{{ __('transparency.register.category_label') }}</span>
                            <select id="complaint-category" aria-labelledby="category-label">
                                <option value="">{{ __('transparency.register.all_categories') }}</option>
                                @foreach($categoryOptions as $category)
                                    <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--sort">
                            <span class="sr-only" id="sort-label">{{ __('transparency.register.sort_label') }}</span>
                            <select id="complaint-sort" aria-labelledby="sort-label">
                                <option value="reported_desc">{{ __('transparency.register.sort_reported_desc') }}</option>
                                <option value="reported_asc">{{ __('transparency.register.sort_reported_asc') }}</option>
                                <option value="status_asc">{{ __('transparency.register.sort_status_asc') }}</option>
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </div>
                </div>

                <div class="tr-filters__row2">
                    <div class="tr-chips" role="group" aria-label="{{ __('transparency.register.status_label') }}">
                        <button type="button" class="status-chip selected" data-value="">{{ __('transparency.register.all_statuses') }}</button>
                        @foreach($statusOptions as $status)
                            <button type="button" class="status-chip" data-value="{{ $status['key'] }}">{{ $status['label'] }}</button>
                        @endforeach
                    </div>

                    <button type="button" class="tr-barangay-chip" id="barangay-chip" hidden>
                        <span class="tr-barangay-chip__label"></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>

                    <button type="button" id="clear-filters" class="tr-clear" hidden>{{ __('transparency.register.clear_filters') }}</button>
                </div>
            </div>

            {{-- List card --}}
            <div class="tr-card tr-list-card">
                <div class="report-list__head" aria-hidden="true">
                    <span>{{ __('transparency.register.table.complaint') }}</span>
                    <span>{{ __('transparency.register.table.department') }}</span>
                    <span>{{ __('transparency.register.table.status') }}</span>
                    <span>{{ __('transparency.register.table.reported') }}</span>
                    <span></span>
                </div>

                <ul class="report-list" id="complaint-table-body">
                    @forelse($reports as $report)
                        @php
                            $step = $report['progress_step'];
                            $statusKey = $report['status_key'];
                            $closed = $statusKey === 'closed';
                            $isRejected = $statusKey === 'rejected' || $step === null;
                        @endphp
                        <li
                            data-report-row
                            data-report-id="{{ $report['ticket_id'] }}"
                            data-search="{{ Str::lower($report['search_text']) }}"
                            data-department="{{ $report['department'] }}"
                            data-category="{{ $report['category'] }}"
                            data-status="{{ $report['status_key'] }}"
                            data-iso-date="{{ $report['iso_date'] }}"
                            data-barangay="{{ $report['barangay'] }}"
                        >
                            <a class="report-row" href="{{ $report['url'] }}" wire:navigate>
                                {{-- Complaint cell: icon + title + (ticket · pin barangay).
                                     On mobile the wrappers dissolve (display: contents) and the
                                     pieces join the wrapping flex line in order. --}}
                                <span class="report-row__complaint">
                                    @include('pages.transparency.partials.category-icon', ['category' => $report['category']])
                                    <span class="report-row__text">
                                        <span class="report-row__title">{{ $report['title'] }}</span>
                                        <span class="report-row__meta">
                                            <span class="ticket-id">{{ $report['ticket_id'] }}</span>
                                            <span class="report-row__meta-dot" aria-hidden="true">·</span>
                                            <svg class="report-row__meta-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <span class="report-row__brgy">{{ $report['barangay_label'] }}</span>
                                        </span>
                                    </span>
                                </span>

                                {{-- Department cell: code badge + name --}}
                                <span class="report-row__dept">
                                    @if($report['department_code'])
                                        <span class="dept-badge" data-dept="{{ $report['department_code'] }}">{{ $report['department_code'] }}</span>
                                    @endif
                                    <span class="report-row__dept-name">{{ $report['department'] }}</span>
                                </span>

                                {{-- Status cell: pill + optional Verified + 5-segment bar (desktop mini) --}}
                                <span class="report-row__status">
                                    <span class="status-pill {{ $report['status_key'] }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $report['status_label'] }}</span>
                                    @if($report['verified'])
                                        <span class="report-row__verified">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 10"/></svg>
                                            {{ __('transparency.v2.verified') }}
                                        </span>
                                    @endif
                                    <span class="progress progress--mini" aria-hidden="true">
                                        @for($segment = 1; $segment <= 5; $segment++)
                                            <span class="progress__seg {{ $isRejected ? '' : ($segment < $step || $closed ? 'progress__seg--complete' : ($segment === $step ? 'progress__seg--current' : '')) }}"></span>
                                        @endfor
                                    </span>
                                </span>

                                {{-- Reported cell --}}
                                <span class="report-row__reported">
                                    <time datetime="{{ $report['iso_date'] }}">{{ $report['date'] }}</time>
                                </span>

                                {{-- Mobile-only line 4: full-width bar with step text --}}
                                <span
                                    class="report-row__progressline"
                                    role="img"
                                    aria-label="{{ $isRejected ? $report['status_label'] : __('transparency.v2.progress_aria', ['step' => $step, 'total' => 5, 'status' => $report['status_label']]) }}"
                                >
                                    <span class="progress__text">
                                        @if($isRejected)
                                            {{ $report['status_label'] }}
                                        @else
                                            {{ __('transparency.v2.step_status', ['step' => $step, 'total' => 5, 'status' => $report['status_label']]) }}
                                        @endif
                                    </span>
                                    <span class="progress progress--full" aria-hidden="true">
                                        @for($segment = 1; $segment <= 5; $segment++)
                                            <span class="progress__seg {{ $isRejected ? '' : ($segment < $step || $closed ? 'progress__seg--complete' : ($segment === $step ? 'progress__seg--current' : '')) }}"></span>
                                        @endfor
                                    </span>
                                </span>

                                {{-- Desktop-only chevron --}}
                                <svg class="report-row__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                            </a>
                        </li>
                    @empty
                        <li>
                            <div class="empty-state">
                                <span class="empty-state__icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 6 8.38 8.38 0 0 1-3.8-.9L3 21l1.6-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 6-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                                </span>
                                <strong>{{ __('transparency.register.empty.title') }}</strong>
                                <p>{{ __('transparency.register.empty.body') }}</p>
                            </div>
                        </li>
                    @endforelse

                    <li data-no-results hidden>
                        <div class="empty-state">
                            <span class="empty-state__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                            </span>
                            <strong>{{ __('transparency.register.no_results.title') }}</strong>
                            <p>{{ __('transparency.register.no_results.body') }}</p>
                            <button type="button" id="clear-filters-list" class="tr-button">{{ __('transparency.register.clear_filters') }}</button>
                        </div>
                    </li>

                    <li data-error hidden>
                        <div class="empty-state">
                            <span class="empty-state__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            </span>
                            <strong>{{ __('transparency.v2.error_title') }}</strong>
                            <p>{{ __('transparency.v2.error_body') }}</p>
                            <button type="button" class="tr-button" data-retry>{{ __('transparency.v2.retry') }}</button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- The map's payload. It lives INSIDE the polled root on purpose: it is the
         same data as the rows, and if it sat outside, the rows would refresh
         around a heat map still describing the complaints it was first handed.
         Read by the page script as data attributes rather than a JSON <script>
         island, because Livewire treats a script tag inside a component as code
         to evaluate and this is not code. --}}
    <div id="transparency-map-data"
         data-center="{{ json_encode($mapCenter) }}"
         data-points="{{ json_encode($mapPoints) }}"></div>
</div>
