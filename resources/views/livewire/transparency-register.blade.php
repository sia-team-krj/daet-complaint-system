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
        @include('partials.public-ui')
        @include('pages.transparency.partials.component-styles')
        @include('pages.transparency.partials.complaints-styles')
    @endpush

    {{-- ════════════ BANNER ════════════ --}}
    <header class="tr-banner">
        <div class="tr-container">
            <h1 class="tr-h1">{{ __('transparency.header.title') }}</h1>
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
                        <p class="tr-areas__none">{{ __('transparency.v2.top_areas_none') }}</p>
                    @endif
                    <p class="tr-areas__privacy-note">{{ __('transparency.v2.top_areas_privacy_note') }}</p>
                </aside>
            </div>
        </div>
    </section>

    {{-- ════════════ RECENT PUBLIC COMPLAINTS ════════════ --}}
    @if($reports->isNotEmpty())
    <section class="tr-register" aria-labelledby="recent-heading">
        <div class="tr-container">
            <div class="tr-register-head">
                <h2 class="tr-h2" id="recent-heading">{{ __('transparency.register.recent_heading') }}</h2>
                <p class="tr-results" id="results-summary" aria-live="polite">
                    {{ __('transparency.register.results', ['visible' => $reports->count(), 'total' => $totalPublic ?? $reports->count()]) }}
                </p>
            </div>

            <ul class="complaint-list" role="list">
                @foreach($reports as $report)
                    @php
                        $rawTitle = $report['raw_title'] ?? $report['title'];
                        $displayTitle = (trim($rawTitle) === '' || mb_strlen($rawTitle) > 70)
                            ? $report['category_label']
                            : $report['title'];
                        $brgy = preg_replace('/^Barangay\\s+/i', '', (string) $report['barangay_label']);
                        $brgyDisplay = (strcasecmp($brgy, 'Daet') === 0) ? 'Daet' : 'Barangay ' . $brgy;
                    @endphp
                    <li class="complaint-card">
                        <a class="complaint-card__link" href="{{ $report['url'] }}" wire:navigate>
                            <div class="cc-row1">
                                @include('pages.transparency.partials.category-icon', ['category' => $report['category']])
                                <div class="cc-title-col">
                                    <span class="cc-title" title="{{ $displayTitle }}">{{ $displayTitle }}</span>
                                    <span class="cc-meta">
                                        <span class="ticket-id">{{ $report['ticket_id'] }}</span>
                                        <span class="cc-dot" aria-hidden="true">·</span>
                                        <span class="cc-brgy" title="{{ $brgyDisplay }}">{{ $brgyDisplay }}</span>
                                    </span>
                                </div>
                            </div>
                            <div class="cc-divider" aria-hidden="true"></div>
                            <div class="cc-row2">
                                <span class="status-pill {{ $report['status_key'] }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $report['status_label'] }}</span>
                                @if($report['verified'])
                                    <span class="verified-tick" title="{{ __('transparency.register.verified') }}" aria-label="{{ __('transparency.register.verified') }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16"><circle cx="12" cy="12" r="10"/><polyline points="20 6 9 17 4 10"/></svg>
                                    </span>
                                @endif
                                @if($report['department_code'])
                                    <span class="dept-badge" data-dept="{{ $report['department_code'] }}" title="{{ $report['department'] }}">{{ $report['department_code'] }}</span>
                                @endif
                                <span class="cc-date">
                                    <time datetime="{{ $report['iso_date'] }}" title="{{ $report['date'] }}">{{ \Illuminate\Support\Carbon::parse($report['iso_date'])->format('M j') }}{{ \Illuminate\Support\Carbon::parse($report['iso_date'])->format('Y') !== now()->format('Y') ? ', '.\Illuminate\Support\Carbon::parse($report['iso_date'])->format('Y') : '' }}</time>
                                </span>
                                <svg class="cc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16"><polyline points="9 18 15 12 9 6"/></svg>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tr-view-all">
                <a href="{{ route('transparency.complaints') }}" class="tr-view-all__btn">
                    {{ __('transparency.register.view_all', ['count' => $totalPublic ?? $reports->count()]) }}
                </a>
            </div>
        </div>
    </section>
    @else
    <section class="tr-register" aria-labelledby="recent-heading">
        <div class="tr-container">
            <div class="tr-card tr-list-card">
                <div class="empty-state">
                    <span class="empty-state__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 6 8.38 8.38 0 0 1-3.8-.9L3 21l1.6-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 6-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    </span>
                    <strong>{{ __('transparency.register.empty.title') }}</strong>
                    <p>{{ __('transparency.register.empty.body') }}</p>
                </div>
            </div>
        </div>
    </section>
    @endif

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
