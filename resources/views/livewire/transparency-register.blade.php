{{--
    The live public register.

    Everything in here is derived from one query in TransparencyFeed, so all of
    it re-renders together. Anything on this page that does NOT come from that
    query - the styles, the footer, the map and filter scripts - stays in the
    page view, because a component that re-renders a timer does not need to
    re-render the static chrome along with it.

    The map payload below is a data attribute rather than the
    <script type="application/json"> island it used to be, and that is not a
    stylistic choice. Livewire treats a <script> it finds inside a component as
    code to evaluate, and this file is JSON, not code. An attribute has no such
    ambiguity: it is text, it round-trips through the morph untouched, and the
    page script reads it with JSON.parse.
--}}
<div class="transparency-page"
     wire:poll.{{ \App\Livewire\TransparencyRegister::REFRESH_SECONDS }}s.visible>

    <!-- ── Page Header ── -->
    <header class="transparency-header">
        <div class="transparency-container">
            <span class="kicker">{{ __('transparency.header.kicker') }}</span>
            <h1>{{ __('transparency.header.title') }}</h1>
            <p class="privacy">
                {{ __('transparency.header.privacy') }}
                <a href="#" data-popover-trigger>{{ __('transparency.header.privacy_link') }}</a>
            </p>
            <div class="meta">
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v4l3 3"></path><path d="M19 12H3"></path></svg>
                    {{ $reports->count() }} {{ $reports->count() === 1 ? __('transparency.header.report_one') : __('transparency.header.report_other') }}
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    {{ __('transparency.header.updated') }} {{ $lastUpdated }}
                </span>
            </div>
        </div>
    </header>

    <!-- ── Map Section ── -->
    <section class="map-section" aria-label="{{ __('transparency.map.map_aria') }}">
        <div class="transparency-container">
            <div class="section-head">
                <span class="kicker">{{ __('transparency.map.kicker') }}</span>
                <h2 id="map-heading">{{ __('transparency.map.heading') }}</h2>
            </div>
            <div class="map-card" id="map-container">
                <div id="transparency-map"></div>
                <div id="map-status" class="map-status" aria-live="polite">{{ __('transparency.map.loading') }}</div>
            </div>
        </div>
    </section>

    <!-- ── Register Section ── -->
    <section class="register-section" id="register" aria-labelledby="register-heading">
        <div class="transparency-container">
            <div class="section-head">
                <span class="kicker">{{ __('transparency.register.kicker') }}</span>
                <h2 id="register-heading">{{ __('transparency.register.heading') }}</h2>
            </div>

            <!-- Filter bar -->
            <div class="filter-bar" id="filter-bar">
                <div class="search">
                    <input
                        id="complaint-search"
                        type="search"
                        placeholder="{{ __('transparency.register.search_placeholder') }}"
                        autocomplete="off"
                        class="w-full rounded-10 border border-navy-200 bg-white px-3 py-2 text-sm text-navy-900 placeholder-navy-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy-900/20"
                    >
                </div>
                <div class="filters-chip-group">
                    <span class="status-label">{{ __('transparency.register.status_label') }}</span>
                    <button type="button" class="status-chip selected" data-value="">{{ __('transparency.register.all_statuses') }}</button>
                    @foreach($statusOptions as $status)
                        <button type="button" class="status-chip" data-value="{{ $status['key'] }}">{{ $status['label'] }}</button>
                    @endforeach
                </div>
                <div class="filter-selects">
                    <select id="complaint-department" class="block w-full rounded-10 border border-navy-200 bg-white px-3 py-2 text-sm text-navy-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy-900/20">
                        <option value="">{{ __('transparency.register.all_departments') }}</option>
                        @foreach($departmentOptions as $department)
                            <option value="{{ $department }}">{{ $department }}</option>
                        @endforeach
                    </select>
                    <select id="complaint-category" class="block w-full rounded-10 border border-navy-200 bg-white px-3 py-2 text-sm text-navy-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy-900/20">
                        <option value="">{{ __('transparency.register.all_categories') }}</option>
                        @foreach($categoryOptions as $category)
                            <option value="{{ $category['key'] }}">{{ $category['label'] }}</option>
                        @endforeach
                    </select>
                    <select id="complaint-sort" class="block w-full rounded-10 border border-navy-200 bg-white px-3 py-2 text-sm text-navy-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-navy-900/20">
                        <option value="reported_desc">{{ __('transparency.register.sort_reported_desc') }}</option>
                        <option value="reported_asc">{{ __('transparency.register.sort_reported_asc') }}</option>
                        <option value="status_asc">{{ __('transparency.register.sort_status_asc') }}</option>
                    </select>
                </div>
                <button type="button" id="clear-filters" class="clear-filters" hidden>{{ __('transparency.register.clear_filters') }}</button>
            </div>

            <!-- Results summary -->
            <div class="results-summary" id="results-summary">
                {{ __('transparency.register.results', ['visible' => $reports->count(), 'total' => $reports->count()]) }}
            </div>

            <!-- Desktop table -->
            <div class="desktop-table" id="desktop-table">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">{{ __('transparency.register.table.ticket') }}</th>
                            <th scope="col">{{ __('transparency.register.table.complaint') }}</th>
                            <th scope="col">{{ __('transparency.register.table.category') }}</th>
                            <th scope="col">{{ __('transparency.register.table.department') }}</th>
                            <th scope="col">{{ __('transparency.register.table.status') }}</th>
                            <th scope="col">{{ __('transparency.register.table.reported') }}</th>
                        </tr>
                    </thead>
                    <tbody id="complaint-table-body">
                        @forelse($reports as $report)
                            <tr
                                data-report-row
                                data-isodate="{{ $report['iso_date'] }}"
                                data-report-id="{{ $report['ticket_id'] }}"
                                data-search="{{ strtolower(implode(' ', [$report['ticket_id'], $report['title'], $report['category_label'], $report['department'], $report['location']])) }}"
                                data-department="{{ $report['department'] }}"
                                data-category="{{ $report['category'] }}"
                                data-status="{{ $report['status_key'] }}"
                                class="group"
                            >
                                <td class="ticket-id">
                                    <a href="{{ $report['url'] }}" wire:navigate>{{ $report['ticket_id'] }}</a>
                                </td>
                                <td class="title">
                                    <a href="{{ $report['url'] }}" wire:navigate class="block">{{ $report['title'] }}</a>
                                    <span class="location-line">{{ $report['location'] }}</span>
                                </td>
                                <td class="category">{{ $report['category_label'] }}</td>
                                <td class="department">{{ $report['department'] }}</td>
                                <td class="status">
                                    <span class="status-pill {{ $report['status_key'] }}">{{ $report['status_label'] }}</span>
                                </td>
                                <td class="reported"><time datetime="{{ $report['iso_date'] }}">{{ $report['date'] }}</time></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="empty-cell">
                                    <div class="empty-state">
                                        <strong>{{ __('transparency.register.empty.title') }}</strong>
                                        <p>{{ __('transparency.register.empty.body') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="complaint-no-results" data-no-results hidden>
                            <td colspan="6" class="empty-cell">
                                <div class="empty-state">
                                    <strong>{{ __('transparency.register.no_results.title') }}</strong>
                                    <p>{{ __('transparency.register.no_results.body') }}</p>
                                    <button type="button" id="clear-filters-table" class="clear-filters">{{ __('transparency.register.clear_filters') }}</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="mobile-list" aria-label="{{ __('transparency.register.mobile_list') }}" id="mobile-list">
                @forelse($reports as $report)
                    <x-report-card :report="$report" />
                @empty
                    <div class="empty-state">
                        <strong>{{ __('transparency.register.empty.title') }}</strong>
                        <p>{{ __('transparency.register.empty.body') }}</p>
                    </div>
                @endforelse
                <div data-no-results hidden class="empty-state">
                    <strong>{{ __('transparency.register.no_results.title') }}</strong>
                    <p>{{ __('transparency.register.no_results.body') }}</p>
                    <button type="button" id="clear-filters-card" class="clear-filters">{{ __('transparency.register.clear_filters') }}</button>
                </div>
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
