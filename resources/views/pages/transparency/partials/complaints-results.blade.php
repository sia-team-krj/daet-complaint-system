{{--
    The public feed's results: the count, the list (or the empty state) and the
    pager.

    Swapped as one region so the count, the list and the pager can never
    disagree with each other.
--}}
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
