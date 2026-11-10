@extends('staff.layouts.app')

@section('title', __('staff.title'))
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    {{-- ════════════ BANNER ════════════ --}}
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <h1 class="tr-h1">{{ __('staff.queue') }}</h1>
            <p class="tr-privacy">{{ $department?->name ?? __('nav.departments') }} — {{ __('staff.banner_sub') }}</p>
        </div>
    </header>

    {{-- ════════════ FILTER BAR ════════════ --}}
    <section aria-labelledby="queue-filters-title" class="tr-register" style="max-width: none;">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            @include('staff.partials.flash')

            <form data-loading-submit method="GET" action="{{ route('staff.complaints.index') }}" class="tr-card tr-filters" id="filter-bar" role="search" style="margin-top: 20px;">
                <span id="queue-filters-title" class="sr-only">Queue filters</span>
                <div class="tr-filters__row1">
                    <label class="tr-search" for="complaint-search">
                        <svg class="tr-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input
                            id="complaint-search"
                            type="search"
                            name="search"
                            value="{{ $search ?? '' }}"
                            placeholder="Ticket ID, title, or description"
                            autocomplete="off"
                        >
                    </label>
                    <div class="tr-selects">
                        <div class="tr-select tr-select--department">
                            <select name="status" class="staff-queue-filter-select" aria-label="Status">
                                <option value="">All statuses</option>
                                @foreach(['Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected'] as $value)
                                    <option value="{{ $value }}" @selected(($status ?? '') === $value)>{{ $value }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--category">
                            <select name="review" aria-label="Review status">
                                <option value="">All review statuses</option>
                                @foreach($reviewStatusOptions as $reviewOption)
                                    <option value="{{ $reviewOption->value }}" @selected(($reviewStatus ?? '') === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--sort">
                            <select name="priority" aria-label="Priority">
                                <option value="">All priorities</option>
                                @foreach($priorityOptions as $priorityOption)
                                    <option value="{{ $priorityOption->value }}" @selected(($priority ?? '') === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                    </div>
                </div>
                <div class="tr-filters__row2">
                    <button type="submit" class="tr-button" style="margin: 0;">Apply</button>
                    <a href="{{ route('staff.complaints.index', array_merge(request()->except(['overdue']), $overdue ? [] : ['overdue' => 1])) }}"
                       class="status-chip {{ $overdue ? 'selected' : '' }}"
                       @if($overdue) aria-current="true" @endif>
                        {{ __('staff.sla.overdue_filter') }}
                    </a>
                    @if(request()->filled('status') || request()->filled('review') || request()->filled('priority') || request()->filled('search') || $overdue)
                        <a href="{{ route('staff.complaints.index') }}" class="tr-clear">Clear</a>
                    @endif
                </div>
            </form>

            {{-- The live queue: the browser polls this endpoint and swaps the
                 rows only when a case actually changed. Two staff members on
                 the same office no longer work the same case. See
                 docs/design-system.md §5.36. --}}
            <div data-live-queue
                 data-endpoint="{{ route('staff.complaints.browser') }}"
                 data-fingerprint="{{ $queueFingerprint }}"
                 data-interval="30000"
                 data-message-changed-one="{{ __('audit.queue_changed_one') }}"
                 data-message-changed-many="{{ __('audit.queue_changed_many') }}"
                 data-message-updated="{{ __('audit.queue_updated') }}">

            <div data-queue-list>
                @include('staff.complaints.partials.list', ['complaints' => $complaints, 'slaSummaries' => $slaSummaries])
            </div>

            {{-- ════════════ RESPONSIVE PAGINATION SECTION ════════════ --}}
            @php
                $total = $complaints->total();
                $perPage = $complaints->perPage();
                $currentPage = $complaints->currentPage();
                $from = $total > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
                $to = min($currentPage * $perPage, $total);
            @endphp
            <div data-queue-pagination>
                @include('staff.complaints.partials.pagination', ['complaints' => $complaints])
            </div>

            <p class="staff-queue-live-status" data-live-queue-status role="status" aria-live="polite"></p>
            </div>

        </div>
    </section>
</div>

<style>
    .staff-content .transparency-page { padding-top: 0; }
    .staff-content .tr-banner { padding-top: 48px; }

    .transparency-page .tr-container { width: 100%; }

    .staff-queue-item {
        border: 1px solid var(--line-soft, #DCE5F1);
        border-radius: var(--radius-lg, 10px);
        background: var(--surface, #FFFFFF);
        box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,0.06));
        overflow: hidden;
    }

    .staff-queue-item + .staff-queue-item { margin-top: 12px; }

    .staff-queue-item--overdue { box-shadow: inset 4px 0 0 #EF4444, var(--shadow-card); }
    .staff-queue-item--aging { box-shadow: inset 4px 0 0 #F59E0B, var(--shadow-card); }

    /* Redesigned Complaint Card Layout */
    .staff-row {
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(0, 1.3fr) minmax(100px, auto);
        gap: 16px;
        align-items: center;
        padding: 16px 20px;
        text-decoration: none;
        color: inherit;
    }

    .staff-row:hover { background: rgba(16,41,75,0.02); }
    .staff-row:focus-visible { outline: none; box-shadow: var(--ring-inset); }

    .staff-row__top {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        min-width: 0;
    }

    .staff-row__icon-wrapper {
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: rgba(16,41,75,0.06);
        color: #10294B;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .staff-row__text {
        min-width: 0;
    }

    .staff-row__title {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.4;
        color: #142D50;
        margin: 0 0 3px 0;
    }

    .staff-row__meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
        font-size: 11px;
        color: #64748B;
    }

    .staff-row__badges {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }

    .staff-row__subtext {
        font-size: 11px;
        color: #64748B;
        white-space: nowrap;
    }

    .staff-priority {
        display: inline-flex;
        align-items: center;
        height: 22px;
        padding: 0 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .staff-priority--routine { background: rgba(100,116,139,0.1); color: #475569; }
    .staff-priority--elevated { background: rgba(37,99,235,0.1); color: #1D4ED8; }
    .staff-priority--urgent { background: rgba(245,158,11,0.15); color: #B45309; }
    .staff-priority--critical { background: rgba(239,68,68,0.15); color: #B91C1C; }

    .staff-row__age {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .staff-age {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 30px;
        padding: 4px 10px;
        border-radius: 6px;
        background: #10294B;
        color: #FFFFFF;
        font-size: 11px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        text-align: center;
    }

    /* Responsive Pagination Styles */
    .staff-pagination-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 20px;
        padding: 16px 20px;
        background: var(--surface, #FFFFFF);
        border: 1px solid var(--line-soft, #DCE5F1);
        border-radius: 10px;
        box-shadow: var(--shadow-card, 0 1px 3px rgba(0,0,0,0.06));
        margin-bottom: 60px;
    }

    .staff-pagination-info {
        font-size: 13px;
        color: #142D50;
    }

    .pagination-range {
        color: #64748B;
    }

    .staff-pagination-desktop {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .staff-pagination-desktop .admin-pager {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .staff-pagination-desktop .admin-pager-pages {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .staff-pagination-desktop .admin-pager-step,
    .staff-pagination-desktop .admin-pager-page {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 40px;
        min-width: 40px;
        padding: 0 12px;
        border: 1px solid var(--line, #dce5f1);
        border-radius: 8px;
        background: var(--surface, #fff);
        color: var(--navy-700, #10294b);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }

    .staff-pagination-desktop .admin-pager-step:hover,
    .staff-pagination-desktop .admin-pager-page:hover {
        background: var(--navy-50, #f4f7fb);
        border-color: var(--navy-100, #dce5f1);
        color: var(--navy-800, #10294b);
    }

    .staff-pagination-desktop .admin-pager-page.is-current {
        background: var(--navy-700, #10294b);
        border-color: var(--navy-700, #10294b);
        color: #fff;
    }

    .staff-pagination-desktop .admin-pager-step.is-disabled {
        background: var(--surface-soft, #f8fafc);
        border-color: var(--line-soft, #e8edf5);
        color: var(--faint, #64748b);
    }

    .staff-pagination-desktop .admin-pager-gap {
        color: var(--faint, #64748b);
        padding: 0 4px;
    }

    .staff-pagination-mobile {
        display: none;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 12px;
        border-top: 1px solid var(--line-soft, #DCE5F1);
        padding-top: 12px;
        margin-top: 4px;
    }

    .staff-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 16px;
        border-radius: 6px;
        background: #10294B;
        color: #FFFFFF;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid #10294B;
        cursor: pointer;
        flex: 1;
        text-align: center;
    }

    .staff-page-btn:hover {
        background: #1B3A61;
        color: #FFFFFF;
    }

    .staff-page-btn--disabled {
        background: #F1F5F9;
        color: #94A3B8;
        border-color: #E2E8F0;
        cursor: not-allowed;
    }

    .staff-page-indicator {
        font-size: 13px;
        font-weight: 700;
        color: #142D50;
        padding: 0 12px;
        white-space: nowrap;
    }

    /* Responsive Queue Card Layout */
    @media (max-width: 1023px) {
        .staff-row {
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            padding: 14px;
        }
        .staff-row__badges {
            grid-column: span 2;
            padding-top: 6px;
            border-top: 1px solid var(--line-soft, #DCE5F1);
        }
        .staff-row__age {
            grid-column: 2;
            grid-row: 1;
        }
    }

    @media (max-width: 599px) {
        .staff-row {
            grid-template-columns: 1fr;
            gap: 10px;
            padding: 12px;
        }
        .staff-row__age {
            grid-column: 1;
            grid-row: auto;
            justify-content: flex-start;
        }
        .staff-row__badges {
            grid-column: 1;
        }
        .staff-pagination-container {
            flex-direction: column;
            align-items: stretch;
            padding: 14px;
        }
        .staff-pagination-desktop {
            display: none;
        }
        .staff-pagination-mobile {
            display: flex;
        }
    }
</style>
@endsection
