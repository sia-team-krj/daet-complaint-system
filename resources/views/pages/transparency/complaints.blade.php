@extends($mainLayout)

@section('title', __('transparency.register.all_heading'))

@section('content')
    @push('styles')
        @include('partials.public-ui')
        @include('pages.transparency.partials.component-styles')
        @include('pages.transparency.partials.complaints-styles')
    @endpush

    <div class="tr-container" style="padding-top: 12px; padding-bottom: 8px;">
        <a href="{{ route('transparency') }}" class="tr-back">{{ __('transparency.register.back_to_transparency') }}</a>
    </div>

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

                {{-- Row 2: status chips --}}
                <div class="cf-chips-row">
                    @php
                        $chipBase = array_filter($filters, fn ($v, $k) => ! in_array($k, ['status', 'page'], true) && $v !== '', ARRAY_FILTER_USE_BOTH);
                        $chipUrl = fn ($s) => route('transparency.complaints', array_merge($chipBase, $s === '' ? [] : ['status' => $s]));
                    @endphp
                    <a href="{{ $chipUrl('') }}" class="cf-chip @if($filters['status'] === '') cf-chip--active @endif" @if($filters['status'] === '') aria-current="true" @endif>
                        {{ __('transparency.register.all_statuses_with_count', ['n' => array_sum($statusCounts ?? [])]) }}
                    </a>
                    @foreach($statusOptions as $st)
                        @php $cnt = (int) (($statusCounts ?? [])[$st['key']] ?? 0); @endphp
                        @if($cnt > 0)
                            <a href="{{ $chipUrl($st['key']) }}" class="cf-chip @if($filters['status'] === $st['key']) cf-chip--active @endif" @if($filters['status'] === $st['key']) aria-current="true" @endif>
                                {{ $st['label'] }} {{ $cnt }}
                            </a>
                        @endif
                    @endforeach
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
                @if($anyFilter)
                    <div class="cf-active-chips">
                        @if($filters['q'] !== '')
                            <a href="{{ route('transparency.complaints', array_merge(array_filter($filters, fn($v,$k)=> !in_array($k,['q','page'],true) && $v!=='', ARRAY_FILTER_USE_BOTH))) }}" class="cf-active-chip" aria-label="{{ __('transparency.register.remove_filter', ['name' => $filters['q']]) }}">
                                {{ __('transparency.register.search_label') }}: {{ $filters['q'] }} ×
                            </a>
                        @endif
                        @if($filters['department'] !== '')
                            <a href="{{ route('transparency.complaints', array_merge(array_filter($filters, fn($v,$k)=> !in_array($k,['department','page'],true) && $v!=='', ARRAY_FILTER_USE_BOTH))) }}" class="cf-active-chip" aria-label="{{ __('transparency.register.remove_filter', ['name' => $filters['department']]) }}">
                                {{ $filters['department'] }} ×
                            </a>
                        @endif
                        @if($filters['category'] !== '')
                            @php $catLabel = collect($categoryOptions)->firstWhere('key', $filters['category'])['label'] ?? $filters['category']; @endphp
                            <a href="{{ route('transparency.complaints', array_merge(array_filter($filters, fn($v,$k)=> !in_array($k,['category','page'],true) && $v!=='', ARRAY_FILTER_USE_BOTH))) }}" class="cf-active-chip" aria-label="{{ __('transparency.register.remove_filter', ['name' => $catLabel]) }}">
                                {{ $catLabel }} ×
                            </a>
                        @endif
                        @if($filters['status'] !== '')
                            @php $statusLabel = collect($statusOptions)->firstWhere('key', $filters['status'])['label'] ?? $filters['status']; @endphp
                            <a href="{{ route('transparency.complaints', array_merge(array_filter($filters, fn($v,$k)=> !in_array($k,['status','page'],true) && $v!=='', ARRAY_FILTER_USE_BOTH))) }}" class="cf-active-chip" aria-label="{{ __('transparency.register.remove_filter', ['name' => $statusLabel]) }}">
                                {{ $statusLabel }} ×
                            </a>
                        @endif
                        <a href="{{ route('transparency.complaints') }}" class="cf-active-chip cf-active-chip--clear">{{ __('transparency.register.clear_all') }}</a>
                    </div>
                @endif
            </form>
        </div>

        <p id="results" class="cf-results" style="scroll-margin-top: 80px;">
            @if($total === 0)
                {{ __('transparency.register.no_results.title') }}
            @else
                @php
                    $from = ($paginator->currentPage() - 1) * $paginator->perPage() + 1;
                    $to = min($paginator->currentPage() * $paginator->perPage(), $paginator->total());
                @endphp
                {{ __('transparency.register.results_paginated', ['start' => $from, 'end' => $to, 'total' => $total]) }}
            @endif
        </p>

        @if($reports->isEmpty())
            <div class="tr-card cf-empty">
                <div class="empty-state">
                    <span class="empty-state__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="28" height="28"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                    </span>
                    <strong>{{ __('transparency.register.no_results.title') }}</strong>
                    <p>{{ __('transparency.register.no_results.body') }}</p>
                    @if($anyFilter)
                        <a href="{{ route('transparency.complaints') }}" class="tr-button">{{ __('transparency.register.clear_all') }}</a>
                    @else
                        <p class="cf-empty-zero">{{ __('transparency.register.empty_zero') }}</p>
                    @endif
                </div>
            </div>
        @else
            <ul class="complaint-list" role="list">
                @foreach($reports as $report)
                    @php
                        $step = $report['progress_step'];
                        $statusKey = $report['status_key'];
                        $closed = $statusKey === 'closed';
                        $isRejected = $statusKey === 'rejected' || $step === null;
                        $rawTitle = $report['raw_title'] ?? $report['title'];
                        $displayTitle = (trim($rawTitle) === '' || mb_strlen($rawTitle) > 70)
                            ? $report['category_label']
                            : $report['title'];
                        $brgy = preg_replace('/^Barangay\s+/i', '', (string) $report['barangay_label']);
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

            @if($paginator->hasPages())
                <nav class="complaints-pagination" aria-label="Pagination">
                    @if($paginator->onFirstPage())
                        <span class="page-btn page-btn--disabled" aria-disabled="true">{{ __('transparency.register.prev') }}</span>
                    @else
                        <a class="page-btn" href="{{ $paginator->previousPageUrl() }}">{{ __('transparency.register.prev') }}</a>
                    @endif

                    <div class="page-numbers">
                        @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                            @if($page == $paginator->currentPage())
                                <span class="page-btn page-btn--current" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="page-btn" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    </div>

                    <span class="page-mobile-label">{{ __('transparency.register.page_of', ['page' => $paginator->currentPage(), 'total' => $paginator->lastPage()]) }}</span>

                    @if($paginator->hasMorePages())
                        <a class="page-btn" href="{{ $paginator->nextPageUrl() }}">{{ __('transparency.register.next') }}</a>
                    @else
                        <span class="page-btn page-btn--disabled" aria-disabled="true">{{ __('transparency.register.next') }}</span>
                    @endif
                </nav>
            @endif
        @endif
    </div>

    @if($anyFilter)
        @push('head')
            <meta name="robots" content="noindex,follow">
        @endpush
    @endif
@endsection

@push('scripts')
<script>
(() => {
    document.documentElement.classList.add('js');
    const panel = document.getElementById('cf-panel');
    const btn = document.getElementById('cf-toggle');
    if (panel) panel.setAttribute('hidden', '');
    if (btn) btn.setAttribute('aria-expanded', 'false');
    const btn = document.getElementById('cf-toggle');
    const panel = document.getElementById('cf-panel');
    if (btn && panel) {
        btn.addEventListener('click', () => {
            const open = panel.hasAttribute('hidden');
            if (open) panel.removeAttribute('hidden'); else panel.setAttribute('hidden', '');
            btn.setAttribute('aria-expanded', String(open));
        });
    }
    const form = document.getElementById('complaints-filter-form');
    if (form) {
        form.querySelectorAll('select').forEach((sel) => {
            sel.addEventListener('change', () => form.submit());
        });
    }
    const search = document.getElementById('complaints-search');
    if (search) {
        search.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') { e.preventDefault(); form.submit(); }
        });
    }
    // Scroll active chip into view on mobile
    const active = document.querySelector('.cf-chip--active');
    if (active && active.scrollIntoView) active.scrollIntoView({ inline: 'center', block: 'nearest' });
})();
</script>
@endpush