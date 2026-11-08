@extends($mainLayout)

@section('title', __('my_complaints.title'))

{{--
    The resident's register, redesigned to the same family as the public
    transparency register. The list reuses the shared report-row component
    (partials/report-row.blade.php), the filters round-trip through the
    query string so they survive refresh and can be shared, and the stat
    strip is computed server-side from the same query as the list.
--}}
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')
@push('styles')
    <style>
        /* ── Resident: visibility + needs-response pills ─────────── */
        /* Resident pills share line 1 — after the ticket, before the status */
        .report-row__meta .visibility-pill,
        .report-row__meta .needs-response { order: 2; }

        .visibility-pill {
            display: inline-flex;
            align-items: center;
            height: 22px;
            padding: 0 8px;
            border-radius: var(--radius-pill);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .visibility-pill--public { background: var(--navy-50); color: var(--navy-700); }
        .visibility-pill--private { background: var(--cat-other-bg); color: var(--cat-other-fg); }

        .needs-response {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 22px;
            padding: 0 8px;
            border-radius: var(--radius-pill);
            background: var(--status-progress-bg);
            color: var(--status-progress-text);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .needs-response__dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--status-progress-dot);
        }

        .report-row__updated {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: var(--faint);
            white-space: normal;
        }

        .report-row__reported { white-space: normal; }

        .report-row__reported { white-space: normal; }

        /* Filter chips are <a> here — keep them underline-free */
        .tr-chips a.status-chip { text-decoration: none; }

        .tr-banner__cta { margin-top: 16px; }

        /* ── Pagination ───────────────────────────────────────────── */
        .tr-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .tr-showing { margin: 0; color: var(--muted); font-size: 13px; font-variant-numeric: tabular-nums; }

        .tr-pagination nav { display: flex; align-items: center; gap: 4px; }
        .tr-pagination ul { display: flex; align-items: center; gap: 4px; list-style: none; margin: 0; padding: 0; }
        .tr-pagination li a,
        .tr-pagination li span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 10px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .tr-pagination li a:hover { background: var(--navy-50); color: var(--navy-700); }
        .tr-pagination li[aria-current="page"] span {
            background: var(--navy-700);
            border-color: var(--navy-700);
            color: var(--surface);
        }
        .tr-pagination li.disabled span { opacity: 0.45; }

        /* Shared chrome the transparency page gets from its component. The
           banner layout here matches it 1:1 so the two pages agree. */
        .mc-content { padding-bottom: 48px; }
    </style>
@endpush

@section('content')
<div class="transparency-page">
    {{-- ════════════ BANNER ════════════ --}}
    <header class="tr-banner">
        <div class="tr-container">
            <h1 class="tr-h1">{{ __('my_complaints.banner_title') }}</h1>
            <span class="tr-goldbar" aria-hidden="true"></span>
            <p class="tr-privacy">{{ __('my_complaints.banner_sub') }}</p>

            <div class="tr-banner__cta">
                <a href="{{ route('complaints.create') }}" wire:navigate class="btn-nav-cta" style="text-transform:none; letter-spacing:0; font-size:14px; border-radius: var(--radius-sm); padding: 11px 18px;">
                    {{ __('my_complaints.file_cta') }}
                </a>
            </div>

            <p class="tr-updated">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ __('my_complaints.updated') }} {{ now()->locale(app()->getLocale())->translatedFormat('M j, Y') }}
            </p>
        </div>
    </header>

    {{-- ════════════ STAT STRIP ════════════ --}}
    <div class="tr-container">
        <div class="tr-stats" role="list" aria-label="{{ __('my_complaints.stat_total') }}">
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--navy">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $counts['all'] }}</span>
                    <span class="tr-stat__label">{{ __('my_complaints.stat_total') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.56"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $counts['open'] }}</span>
                    <span class="tr-stat__label">{{ __('my_complaints.stat_open') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $counts['resolved'] }}</span>
                    <span class="tr-stat__label">{{ __('my_complaints.stat_resolved') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--sky">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $avgDays === null ? __('my_complaints.avg_none') : __('my_complaints.avg_days', ['days' => $avgDays]) }}</span>
                    <span class="tr-stat__label">{{ __('my_complaints.stat_avg') }}</span>
                </span>
            </div>
        </div>
    </div>

    <section class="tr-register mc-content" aria-labelledby="register-heading">
        <div class="tr-container">
            <div class="tr-register-head">
                <h2 class="tr-h2" id="register-heading">{{ __('my_complaints.register_heading') }}</h2>
                <p class="tr-results" id="results-summary" aria-live="polite">
                    {{ __('my_complaints.results', ['visible' => $complaints->total(), 'total' => $counts['all']]) }}
                </p>
            </div>

            {{-- Filter bar: GET round-trip so filters live in the URL --}}
            <form method="GET" action="{{ route('complaints.index') }}" class="tr-card tr-filters" id="filter-bar" role="search">
                <div class="tr-filters__row1">
                    <label class="tr-search" for="complaint-search">
                        <span class="sr-only">{{ __('my_complaints.search_label') }}</span>
                        <svg class="tr-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input
                            id="complaint-search"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="{{ __('my_complaints.search_placeholder') }}"
                            autocomplete="off"
                        >
                    </label>
                    <div class="tr-selects">
                        <div class="tr-select tr-select--department">
                            <span class="sr-only" id="ds-department-label">{{ __('transparency.register.department_label') }}</span>
                            <select id="complaint-department" name="department" aria-labelledby="ds-department-label" onchange="this.form.submit()">
                                <option value="">{{ __('my_complaints.all_departments') }}</option>
                                @foreach($departmentOptions as $department)
                                    <option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--category">
                            <span class="sr-only" id="ds-category-label">{{ __('transparency.register.category_label') }}</span>
                            <select id="complaint-category" name="category" aria-labelledby="ds-category-label" onchange="this.form.submit()">
                                <option value="">{{ __('my_complaints.all_categories') }}</option>
                                @foreach($categoryOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--sort">
                            <span class="sr-only" id="ds-sort-label">{{ __('transparency.register.sort_label') }}</span>
                            <select id="complaint-sort" name="sort" aria-labelledby="ds-sort-label" onchange="this.form.submit()">
                                <option value="reported_desc" @selected(request('sort', 'reported_desc') === 'reported_desc')>{{ __('my_complaints.sort_reported_desc') }}</option>
                                <option value="reported_asc" @selected(request('sort') === 'reported_asc')>{{ __('my_complaints.sort_reported_asc') }}</option>
                                <option value="status_asc" @selected(request('sort') === 'status_asc')>{{ __('my_complaints.sort_status_asc') }}</option>
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </div>
                </div>

                <div class="tr-filters__row2">
                    <div class="tr-chips" role="group" aria-label="{{ __('transparency.register.status_label') }}">
                        @php
                            $baseQuery = array_filter(['q' => request('q'), 'sort' => request('sort'), 'department' => request('department'), 'category' => request('category')]);
                            $chips = [
                                'all' => __('my_complaints.all_statuses'),
                                'open' => __('my_complaints.stat_open'),
                                'submitted' => __('statuses.complaint_status.submitted'),
                                'under_review' => __('statuses.complaint_status.under_review'),
                                'in_progress' => __('statuses.complaint_status.in_progress'),
                                'resolved' => __('statuses.complaint_status.resolved'),
                            ];
                        @endphp
                        @foreach($chips as $key => $label)
                            <a href="{{ route('complaints.index', array_filter(array_merge($baseQuery, ['status' => $key === 'all' ? null : $key]))) }}" class="status-chip {{ (request('status') ?: 'all') === $key || (!request('status') && $key === 'all') ? 'selected' : '' }}">{{ $label }}@if(isset($counts[$key]))&nbsp;{{ $counts[$key] }}@endif</a>
                        @endforeach
                    </div>
                    @if(request()->anyFilled(['q', 'sort', 'department', 'category']) || filled(request('status')))
                        <a href="{{ route('complaints.index') }}" class="tr-clear" id="clear-filters">{{ __('my_complaints.clear_filters') }}</a>
                    @endif
                </div>
            </form>

            {{-- List card --}}
            <div class="tr-card tr-list-card">
                <div class="report-list__head" aria-hidden="true">
                    <span>{{ __('transparency.register.table.complaint') }}</span>
                    <span>{{ __('transparency.register.table.department') }}</span>
                    <span>{{ __('transparency.register.table.status') }}</span>
                    <span>{{ __('transparency.register.table.reported') }}</span>
                    <span></span>
                </div>

                @php
                    $hasFilters = request()->anyFilled(['q', 'department', 'category'])
                        || (filled(request('status')) && request('status') !== 'all');
                @endphp
                @if($complaints->total() === 0 && !$hasFilters)
                    <div class="empty-state">
                        <span class="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </span>
                        <strong>{{ __('my_complaints.empty.title') }}</strong>
                        <p>{{ __('my_complaints.empty.body') }}</p>
                        <a href="{{ route('complaints.create') }}" wire:navigate class="tr-button">{{ __('my_complaints.empty.cta') }}</a>
                    </div>
                @elseif($complaints->isEmpty())
                    <div class="empty-state">
                        <span class="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                        </span>
                        <strong>{{ __('my_complaints.no_results.title') }}</strong>
                        <p>{{ __('my_complaints.no_results.body') }}</p>
                        <a href="{{ route('complaints.index') }}" class="tr-button">{{ __('my_complaints.clear_filters') }}</a>
                    </div>
                @else
                    <ul class="report-list" id="complaint-table-body">
                        @foreach($complaints as $complaint)
                            @include('partials.report-row', ['complaint' => $complaint])
                        @endforeach
                    </ul>
                @endif
            </div>

            @if($complaints->total() > 0)
                <div class="tr-pagination">
                    <p class="tr-showing">{{ __('my_complaints.showing', ['from' => $complaints->firstItem(), 'to' => $complaints->lastItem(), 'total' => $complaints->total()]) }}</p>
                    @if($complaints->hasPages())
                        <div class="tr-pagination-nav">
                            {{ $complaints->onEachSide(1)->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
