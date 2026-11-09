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

    {{-- ════════════ STAT STRIP (Compact) ════════════ --}}
    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        <div class="tr-stats" role="list" aria-label="{{ __('staff.queue') }}">
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--navy">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5v.01"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['new'] ?? 0 }}</span>
                    <span class="tr-stat__label">{{ __('staff.stats.pending_review') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.56"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['inProgress'] ?? 0 }}</span>
                    <span class="tr-stat__label">{{ __('staff.stats.in_progress') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['resolvedThisMonth'] ?? 0 }}</span>
                    <span class="tr-stat__label">{{ __('staff.stats.resolved_this_month') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--sky">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['avgDays'] ?? '—' }}</span>
                    <span class="tr-stat__label">{{ __('staff.stats.avg_days_open') }}</span>
                </span>
            </div>
        </div>
    </div>

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
                            <select name="status" class="staff-queue-filter-select" aria-label="Status" onchange="this.form.submit()">
                                <option value="">All statuses</option>
                                @foreach(['Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected'] as $value)
                                    <option value="{{ $value }}" @selected(($status ?? '') === $value)>{{ $value }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--category">
                            <select name="review" aria-label="Review status" onchange="this.form.submit()">
                                <option value="">All review statuses</option>
                                @foreach($reviewStatusOptions as $reviewOption)
                                    <option value="{{ $reviewOption->value }}" @selected(($reviewStatus ?? '') === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--sort">
                            <select name="priority" aria-label="Priority" onchange="this.form.submit()">
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
                    @if(request()->filled('status') || request()->filled('review') || request()->filled('priority') || request()->filled('search'))
                        <a href="{{ route('staff.complaints.index') }}" class="tr-clear">Clear</a>
                    @endif
                </div>
            </form>

            <div class="tr-register-head">
                <h2 class="tr-h2" id="queue-list-title">{{ __('staff.queue') }} — {{ $complaints->total() }} {{ \Illuminate\Support\Str::plural('complaint', $complaints->total()) }}</h2>
                @if($complaints->hasPages())
                    <p class="tr-results">Page {{ $complaints->currentPage() }} of {{ $complaints->lastPage() }}</p>
                @endif
            </div>

            {{-- ════════════ QUEUE LIST (Redesigned Cards, 3 per page) ════════════ --}}
            <ul class="report-list" id="complaint-table-body">
                @forelse($complaints as $complaint)
                    @php
                        $statusKey = \Illuminate\Support\Str::slug((string) $complaint->status, '_');
                        $reviewKey = \Illuminate\Support\Str::slug((string) $complaint->review_status, '_');
                        $ageNow = now();
                        $ageSeconds = max(0, (int) $complaint->created_at->diffInSeconds($ageNow));
                        $ageDays = (int) floor($ageSeconds / 86400);
                        $ageLabel = $ageDays >= 1
                            ? $ageDays.' day'.($ageDays === 1 ? '' : 's')
                            : ($ageSeconds < 3600
                                ? max(1, (int) floor($ageSeconds / 60)).' minute'.(floor($ageSeconds / 60) === 1.0 ? '' : 's')
                                : (int) floor($ageSeconds / 3600).' hour'.(floor($ageSeconds / 3600) === 1.0 ? '' : 's'));
                        $priorityValue = $complaint->confirmedPriorityEnum?->value ?? $complaint->suggested_priority ?? 'unset';
                        $priority = $complaint->confirmedPriorityEnum?->label() ?? $complaint->suggestedPriorityEnum->label();
                        $waiting = $complaint->informationRequests->first();
                        $waitingDays = $waiting ? max(0, (int) floor($waiting->created_at->diffInSeconds($ageNow) / 86400)) : null;
                    @endphp
                    <li class="staff-queue-item {{ $ageDays > 14 ? 'staff-queue-item--overdue' : ($ageDays > 7 ? 'staff-queue-item--aging' : '') }}"
                        data-report-row
                        data-report-id="{{ $complaint->ticket_id }}"
                        data-department="{{ $department?->name ?? '' }}"
                        data-category="{{ $complaint->category }}"
                        data-status="{{ $statusKey }}"
                        data-search="{{ \Illuminate\Support\Str::lower($complaint->ticket_id.' '.$complaint->title.' '.$complaint->description.' '.$complaint->category) }}"
                        data-iso-date="{{ $complaint->created_at?->toAtomString() ?? '' }}"
                    >
                        <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-row" wire:navigate>
                            
                            {{-- Top Section: Category Icon, Title, Ticket ID & Category --}}
                            <div class="staff-row__top">
                                <div class="staff-row__icon-wrapper">
                                    @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                </div>
                                <div class="staff-row__text">
                                    <h3 class="staff-row__title">{{ \Illuminate\Support\Str::limit($complaint->title, 90) }}</h3>
                                    <div class="staff-row__meta">
                                        <span class="ticket-id" style="font-family: var(--font-mono, monospace); font-weight: 700; color: #10294B;">{{ $complaint->ticket_id }}</span>
                                        <span aria-hidden="true"> · </span>
                                        <span>{{ str($complaint->category)->replace('_', ' ')->title() }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Middle Section: Status, Review Status & Priority Badges --}}
                            <div class="staff-row__badges">
                                <span class="status-pill {{ $statusKey }}">
                                    <span class="status-pill__dot" aria-hidden="true"></span>
                                    {{ $complaint->statusEnum->label() }}
                                </span>

                                @if($complaint->isModerationFlagged())
                                    <span class="status-pill rejected">
                                        <span class="status-pill__dot" aria-hidden="true"></span>
                                        {{ $complaint->moderationLabel() }}
                                    </span>
                                @endif

                                <span class="status-pill {{ $reviewKey }}">
                                    <span class="status-pill__dot" aria-hidden="true"></span>
                                    {{ $complaint->reviewStatusEnum->label() }}
                                </span>

                                @if($waiting)
                                    <span class="staff-row__subtext" style="color: #D97706; font-weight: 600;">
                                        Waiting {{ $waitingDays }}d{{ $waiting->isStale() ? ' · overdue' : '' }}
                                    </span>
                                @endif

                                <span class="staff-priority staff-priority--{{ \Illuminate\Support\Str::slug($priorityValue) }}">
                                    {{ $priority }}
                                </span>

                                @if($complaint->suggestedPriorityEnum && $complaint->suggestedPriorityEnum->value !== $priorityValue)
                                    <span class="staff-row__subtext">Suggested: {{ $complaint->suggestedPriorityEnum->label() }}</span>
                                @endif
                            </div>

                            {{-- Bottom / Right Section: Age & Filing Duration --}}
                            <div class="staff-row__age">
                                <span class="staff-age">{{ $ageLabel }} open</span>
                            </div>

                        </a>
                    </li>
                @empty
                    <li>
                        <div class="empty-state">
                            <span class="empty-state__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                            </span>
                            <strong>{{ __('staff.empty_title') }}</strong>
                            <p>{{ __('staff.empty_desc') }}</p>
                        </div>
                    </li>
                @endforelse
            </ul>

            {{-- ════════════ RESPONSIVE PAGINATION SECTION ════════════ --}}
            @php
                $total = $complaints->total();
                $perPage = $complaints->perPage();
                $currentPage = $complaints->currentPage();
                $from = $total > 0 ? ($currentPage - 1) * $perPage + 1 : 0;
                $to = min($currentPage * $perPage, $total);
            @endphp
            <div class="staff-pagination-container">
                <div class="staff-pagination-info">
                    <strong>{{ $total }}</strong> {{ \Illuminate\Support\Str::plural('complaint', $total) }} found
                    @if($total > 0)
                        <span class="pagination-range">· Showing {{ $from }}–{{ $to }} of {{ $total }} · {{ $perPage }} per page</span>
                    @endif
                </div>

                @if($complaints->hasPages())
                    {{-- Desktop / Tablet Numbered Pagination --}}
                    <div class="staff-pagination-desktop">
                        {{ $complaints->appends(request()->query())->links() }}
                    </div>

                    {{-- Mobile Compact Pagination --}}
                    <div class="staff-pagination-mobile">
                        @if($complaints->onFirstPage())
                            <button type="button" class="staff-page-btn staff-page-btn--disabled" disabled>Previous</button>
                        @else
                            <a href="{{ $complaints->previousPageUrl() }}" class="staff-page-btn">Previous</a>
                        @endif

                        <span class="staff-page-indicator">
                            Page {{ $currentPage }} of {{ $complaints->lastPage() }}
                        </span>

                        @if($complaints->hasMorePages())
                            <a href="{{ $complaints->nextPageUrl() }}" class="staff-page-btn">Next</a>
                        @else
                            <button type="button" class="staff-page-btn staff-page-btn--disabled" disabled>Next</button>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </section>
</div>

<style>
    .staff-content .transparency-page { padding-top: 0; }
    .staff-content .tr-banner { padding-top: 48px; }

    .transparency-page .tr-stats,
    .transparency-page .tr-container { width: 100%; }

    /* Compact Stat Cards */
    .transparency-page .tr-stats {
        margin-top: -24px;
        gap: 12px;
    }
    .transparency-page .tr-stat {
        padding: 10px 14px;
        gap: 10px;
    }
    .transparency-page .tr-stat__value {
        font-size: 18px;
        line-height: 1.1;
    }
    .transparency-page .tr-stat__label {
        font-size: 11px;
    }
    .transparency-page .tr-stat__icon {
        width: 30px;
        height: 30px;
    }
    .transparency-page .tr-stat__icon svg {
        width: 15px;
        height: 15px;
    }

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
