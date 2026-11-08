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

    @push('styles')
    <style>
        /* ── Design tokens ── */
        :root {
            --bg: #f6f8fb;
            --card: #ffffff;
            --border: #cdd5e1;
            --ink: #172b49;
            --muted: #64748b;
            --navy: #0b1f3a;
            --gold: #8a6a12;
            --gold-light: #e2c06a;
            --radius: 12px;
        }

        *,
        *::before,
        *::after { box-sizing: border-box; }

        body { margin: 0; background: var(--bg); color: var(--ink); font-family: "Inter", ui-sans-serif, system-ui, sans-serif; font-size: 16px; line-height: 1.5; }

        .transparency-page { min-height: 100vh; display: flex; flex-direction: column; }

        .transparency-container {
            width: 100%;
            max-width: 1040px;
            margin: 0 auto;
            padding: 0 24px;
        }

        @media (max-width: 768px) {
            .transparency-container { padding: 0 16px; }
        }

        /* ── Page header (compact, no hero) ── */
        .transparency-header { padding: 32px 0 24px; }

        .transparency-header .kicker {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 10px;
        }

        .transparency-header h1 {
            margin: 0 0 12px;
            font-size: clamp(32px, 5vw, 40px);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        .transparency-header .privacy {
            margin: 0 0 16px;
            font-size: 14px;
            color: var(--muted);
            line-height: 1.6;
            max-width: 700px;
        }

        .transparency-header .privacy a {
            color: var(--ink);
            text-decoration: underline;
            text-underline-offset: 2px;
            font-weight: 600;
        }

        .transparency-header .meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
            color: var(--muted);
        }

        .transparency-header .meta svg {
            width: 14px;
            height: 14px;
            color: var(--gold);
            flex-shrink: 0;
        }

        /* ── Map section ── */
        .map-section { padding: 24px 0 32px; }

        .map-section .section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .map-section .kicker {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .map-section h2 {
            margin: 0;
            font-size: clamp(20px, 3vw, 24px);
            font-weight: 700;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .map-card {
            height: 360px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--card);
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 768px) {
            .map-card { height: 280px; }
        }

        .map-status {
            position: absolute;
            z-index: 10;
            top: 12px;
            left: 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid rgba(11, 31, 58, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.85);
            color: var(--ink);
            font-size: 12px;
            line-height: 1.4;
            pointer-events: none;
            backdrop-filter: blur(4px);
        }

        .map-status--loading {
            background: rgba(255, 255, 255, 0.95);
        }

        .loading-skeleton {
            width: 120px;
            height: 16px;
            background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
        }

        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        /* ── Register section ── */
        .register-section { padding: 0 0 64px; }

        .register-section .section-head { margin-bottom: 20px; }

        .register-section .kicker {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .register-section h2 {
            margin: 0 0 8px;
            font-size: clamp(20px, 3vw, 24px);
            font-weight: 700;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        /* ── Filter bar ── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--card);
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-bar .search { flex: 1 1 280px; min-width: 0; }

        .filter-bar .filters-chip-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-bar .status-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
            margin-right: 8px;
        }

        .filter-bar .status-chip {
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: 20px;
            background: var(--card);
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            cursor: pointer;
            white-space: nowrap;
        }

        .filter-bar .status-chip.selected {
            background: var(--navy);
            color: #fff;
            border-color: var(--navy);
        }

        .filter-bar .filter-selects {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar .filter-selects select {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--card);
            font-size: 12px;
            color: var(--ink);
            cursor: pointer;
            min-width: 130px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2364748b'%3e%3cpath d='M6 8l2 2 2-2'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            padding-right: 32px;
        }

        .filter-bar .clear-filters,
        .filter-bar .apply-filters {
            font-size: 11px;
            font-weight: 600;
            color: var(--ink);
            text-decoration: underline;
            text-underline-offset: 2px;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .filter-bar .clear-filters:hidden,
        .filter-bar .apply-filters:hidden { display: none; }

        /* ── Results summary ── */
        .results-summary {
            margin-bottom: 16px;
            font-size: 13px;
            color: var(--muted);
            font-weight: 600;
        }

        /* ── Desktop table ── */
        .desktop-table { display: block; overflow-x: auto; }

        .desktop-table table { width: 100%; border-collapse: collapse; }

        .desktop-table th {
            text-align: left;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--muted);
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 2;
        }

        .desktop-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            color: var(--ink);
            vertical-align: middle;
        }

        .desktop-table tr:hover { background: #f1f5f9; }

        .desktop-table tr a { text-decoration: none; color: inherit; }

        .desktop-table .ticket-id {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12px;
            font-weight: 700;
            color: var(--navy);
        }

        .desktop-table .title {
            font-weight: 600;
            color: var(--ink);
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .desktop-table .location-line {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: var(--muted);
            font-weight: 400;
        }

        .desktop-table .category,
        .desktop-table .department,
        .desktop-table .status,
        .desktop-table .reported { font-size: 12px; }

        /* ── Mobile cards ── */
        .mobile-list { display: none; }

        @media (max-width: 768px) {
            .desktop-table { display: none; }
            .mobile-list { display: block; }
        }

        /* ── Empty states ── */
        .empty-state { text-align: center; padding: 64px 20px; }

        .empty-state strong {
            display: block;
            margin-bottom: 8px;
            font-size: 20px;
            font-weight: 700;
            color: var(--ink);
        }

        .empty-state p {
            margin: 0;
            font-size: 14px;
            color: var(--muted);
        }

        /* ── Status pills ── */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 1.5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .status-pill.submitted   { background: #dbeafe; color: #1d4ed8; }
        .status-pill.verified    { background: #dcfce7; color: #15803d; }
        .status-pill.assigned    { background: #eff6ff; color: #1e3a8a; }
        .status-pill.in_progress { background: #fef3c7; color: #92400e; }
        .status-pill.resolved    { background: #dcfce7; color: #15803d; }
        .status-pill.rejected    { background: #fee2e2; color: #991b1b; }
        .status-pill.closed      { background: #f1f5f9; color: #475569; }
        .status-pill.default     { background: #f1f5f9; color: #475569; }

        /* ── Focus styles ── */
        :focus-visible { outline: 2px solid var(--gold); outline-offset: 2px; }

        /* ── Map attribution ── */
        .leaflet-control-attribution { font-size: 10px !important; }

        /* ── List styling ── */
        ul, ol { list-style: none; padding: 0; margin: 0; }
    </style>
    @endpush

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
                <h2 id="map-heading">{{ __('transparency.map.heading') }}</h2>
            </div>
            <div class="map-card" id="map-container">
                <div id="transparency-map" style="width: 100%; height: 100%;"></div>
                <div id="map-status" class="map-status map-status--loading" aria-live="polite" role="status">
                    <span class="sr-only">{{ __('transparency.map.loading') }}</span>
                    <div class="loading-skeleton"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Register Section ── -->
    <section class="register-section" id="register" aria-labelledby="register-heading">
        <div class="transparency-container">
            <div class="section-head">
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
