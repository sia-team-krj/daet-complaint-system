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
            <span class="tr-goldbar" aria-hidden="true"></span>
            <p class="tr-privacy">{{ $department?->name ?? __('nav.departments') }} — {{ __('staff.banner_sub') }}</p>
        </div>
    </header>

    {{-- ════════════ STAT STRIP ════════════ --}}
    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        <div class="tr-stats" role="list" aria-label="{{ __('staff.queue') }}">
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--navy">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5v.01"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['new'] ?? 0 }}</span>
                    <span class="tr-stat__label">{{ __('staff.stats.pending_review') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.56"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['inProgress'] ?? 0 }}</span>
                    <span class="tr-stat__label">{{ __('staff.stats.in_progress') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span>
                    <span class="tr-stat__value">{{ $stats['resolvedThisMonth'] ?? 0 }}</span>
                    <span class="tr-stat__label">{{ __('staff.stats.resolved_this_month') }}</span>
                </span>
            </div>
            <div class="tr-stat" role="listitem">
                <span class="tr-stat__icon tr-stat__icon--sky">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
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

            <form method="GET" action="{{ route('staff.complaints.index') }}" class="tr-card tr-filters" id="filter-bar" role="search" style="margin-top: 24px;">
                <span id="queue-filters-title" class="sr-only">Queue filters</span>
                <div class="tr-filters__row1">
                    <label class="tr-search" for="queue-search">
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

            {{-- ════════════ QUEUE LIST ════════════ --}}
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
                            <span class="staff-row__complaint">
                                @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                <span class="staff-row__text">
                                    <span class="staff-row__title">{{ \Illuminate\Support\Str::limit($complaint->title, 90) }}</span>
                                    <span class="staff-row__meta"><span class="ticket-id">{{ $complaint->ticket_id }}</span><span aria-hidden="true"> · </span>{{ str($complaint->category)->replace('_', ' ')->title() }}</span>
                                </span>
                            </span>

                            <span class="staff-row__cell">
                                <span class="status-pill {{ $statusKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->statusEnum->label() }}</span>
                                @if($complaint->isModerationFlagged())
                                    <span class="status-pill rejected staff-row__subpill"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->moderationLabel() }}</span>
                                @endif
                            </span>

                            <span class="staff-row__cell">
                                <span class="status-pill {{ $reviewKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->reviewStatusEnum->label() }}</span>
                                @if($waiting)
                                    <span class="staff-row__subtext">Waiting {{ $waitingDays }}d{{ $waiting->isStale() ? ' · overdue' : '' }}</span>
                                @endif
                            </span>

                            <span class="staff-row__cell">
                                <span class="staff-priority staff-priority--{{ \Illuminate\Support\Str::slug($priorityValue) }}">{{ $priority }}</span>
                                <span class="staff-row__subtext">Suggested: {{ $complaint->suggestedPriorityEnum->label() }}</span>
                            </span>

                            <span class="staff-row__age">
                                <span class="staff-age">{{ $ageLabel }} open</span>
                            </span>
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

            <div class="tr-pagination">
                    <p class="tr-showing">{{ $complaints->total() }} {{ $complaints->total() === 1 ? 'complaint' : 'complaints' }} found{{ $complaints->hasPages() ? ' · Page '.$complaints->currentPage().' of '.$complaints->lastPage() : '' }} · 5 per page</p>
                    @if($complaints->hasPages())
                        <div class="tr-pagination-nav">{{ $complaints->links() }}</div>
                    @endif
                </div>
        </div>
    </section>
</div>

<style>
    /* The staff shell already offsets the fixed navbar — cancel the page's own offset. */
    .staff-content .transparency-page { padding-top: 0; }

    /* Light-workspace content over the dark staff chrome: the transparency
       recipe, one horizontal scroll region instead of a wide table. */
    .transparency-page .tr-stats,
    .transparency-page .tr-container { width: 100%; }

    .staff-queue-item {
        border: 1px solid var(--line-soft);
        border-radius: var(--radius-lg);
        background: var(--surface);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .staff-queue-item + .staff-queue-item { margin-top: 12px; }

    .staff-queue-item--overdue { box-shadow: inset 4px 0 0 var(--status-rejected-dot), var(--shadow-card); }
    .staff-queue-item--aging { box-shadow: inset 4px 0 0 var(--status-progress-dot), var(--shadow-card); }

    .staff-row {
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(0, 1fr) minmax(152px, 1.1fr) minmax(0, 1fr) minmax(88px, auto);
        gap: 12px;
        align-items: center;
        padding: 16px;
        text-decoration: none;
        color: inherit;
    }

    .staff-row:hover { background: var(--navy-50); }
    .staff-row:focus-visible { outline: none; box-shadow: var(--ring-inset); }

    .staff-row__complaint { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .staff-row__text { min-width: 0; }

    .staff-row__title {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.4;
        color: var(--text);
    }

    .staff-row__meta {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: var(--muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .staff-row__cell { display: flex; flex-direction: column; gap: 6px; align-items: flex-start; min-width: 0; }
    .staff-row__subpill { margin-top: 2px; }
    .staff-row__subtext { font-size: 12px; color: var(--muted); white-space: nowrap; }

    .staff-priority {
        display: inline-flex;
        align-items: center;
        height: 22px;
        padding: 0 8px;
        border-radius: var(--radius-pill);
        background: var(--cat-flood-bg);
        color: var(--cat-flood-fg);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .staff-priority--routine { background: var(--cat-other-bg); color: var(--cat-other-fg); }
    .staff-priority--elevated { background: var(--cat-flood-bg); color: var(--cat-flood-fg); }
    .staff-priority--urgent { background: var(--status-progress-bg); color: var(--status-progress-text); }
    .staff-priority--critical { background: var(--status-rejected-bg); color: var(--status-rejected-text); }

    .staff-row__age { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
    .staff-age {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 4px 8px;
        border-radius: var(--radius-sm);
        background: var(--navy-700);
        color: var(--surface);
        font-size: 12px;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        white-space: normal;
        text-align: center;
        line-height: 1.25;
    }

    @media (max-width: 1100px) {
        .staff-row { grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr) minmax(0, 1fr); row-gap: 12px; }
        .staff-row__cell:nth-child(4),
        .staff-row__cell:nth-child(5) { grid-column: span 1; }
        .staff-row__age { grid-column: 3; }
    }

    @media (max-width: 640px) {
        .staff-row {
            grid-template-columns: 1fr;
            padding: 16px;
            row-gap: 10px;
        }
        .staff-row__cell { flex-direction: row; align-items: center; flex-wrap: wrap; gap: 8px 12px; }
        .staff-row__age { flex-direction: row; align-items: center; gap: 8px; }
    }
</style>
@endsection
