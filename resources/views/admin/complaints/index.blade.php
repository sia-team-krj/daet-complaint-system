@extends('admin.layouts.app')

@section('title', 'All Complaints - Admin')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('admin-content')
<div class="transparency-page">
    {{-- ════════════ BANNER (workspace chrome stays dark; content goes family-light) ════════════ --}}
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <div class="tr-admin-banner-row">
                <div>
                    <h1 class="tr-h1">Complaints</h1>
                    <span class="tr-goldbar" aria-hidden="true"></span>
                    <p class="tr-privacy">Filter the municipal queue, reassign ownership, and review every service request.</p>
                </div>
                <a href="{{ route('admin.complaints.export', request()->query()) }}" class="btn-nav-cta" style="text-transform:none; letter-spacing:0; font-size:14px; border-radius: var(--radius-sm); padding: 11px 18px; white-space: nowrap;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="vertical-align:-3px; margin-right:8px;"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>Export CSV
                </a>
            </div>
        </div>
    </header>

    {{-- ════════════ STAT STRIP ════════════ --}}
    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        @php
            $adminStats = [
                ['navy', 'Total in queue', $stats['total']],
                ['amber', 'Pending review', $stats['pending_review']],
                ['sky', 'In progress', $stats['in_progress']],
                ['green', 'Resolved this month', $stats['resolved_this_month']],
            ];
        @endphp
        <div class="tr-stats" role="list" aria-label="Queue metrics">
            @foreach($adminStats as [$tone, $label, $value])
                <div class="tr-stat" role="listitem">
                    <span class="tr-stat__icon tr-stat__icon--{{ $tone }}">
                        @if($tone === 'navy')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        @elseif($tone === 'amber')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5"/><path d="M12 16.5v.01"/></svg>
                        @elseif($tone === 'sky')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M21 12a9 9 0 1 1-6.2-8.56"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        @endif
                    </span>
                    <span>
                        <span class="tr-stat__value">{{ $value }}</span>
                        <span class="tr-stat__label">{{ $label }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    @include('admin.partials.flash')

    {{-- ════════════ FILTER CARD ════════════ --}}
    @php
        $activeFilters = collect([
            'department' => request('department') === 'unassigned'
                ? 'Unassigned'
                : $departments->firstWhere('id', (int) request('department'))?->name,
            'status' => collect($statuses)->firstWhere('value', request('status'))?->label(),
            'review' => collect($reviewStatusOptions)->firstWhere('value', request('review'))?->label(),
            'priority' => collect($priorityOptions)->firstWhere('value', request('priority'))?->label(),
            'flagged' => request('flagged') === '1' ? 'Flagged only' : null,
            'date_from' => request('date_from') ? 'From '.request('date_from') : null,
            'date_to' => request('date_to') ? 'To '.request('date_to') : null,
            'search' => request('search') ? 'Ticket "'.request('search').'"' : null,
        ])->filter();
    @endphp

    <section aria-labelledby="queue-filters-title" class="tr-container queue-section" style="max-width: none; padding: 0 24px;">
        <span id="queue-filters-title" class="sr-only">Complaint filters</span>
        <form method="GET" action="{{ route('admin.complaints.index') }}" class="tr-card tr-filters" id="filter-bar" role="search" data-filter-form style="margin-top: 24px;">
            <div class="tr-filters__row1">
                <label class="tr-search" for="complaint-search">
                    <svg class="tr-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="complaint-search" name="search" class="tr-filter-input" value="{{ request('search') }}" placeholder="Search by ticket ID" autocomplete="off">
                </label>

                <div class="tr-selects">
                    <div class="tr-select tr-select--department">
                        <select name="department" aria-label="Department" data-filter-auto>
                            <option value="">All departments</option>
                            <option value="unassigned" @selected(request('department') === 'unassigned')>Unassigned</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                        <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="tr-select tr-select--category">
                        <select name="status" aria-label="Status" data-filter-auto>
                            <option value="">All statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="tr-select tr-select--sort">
                        <select name="review" aria-label="Review status" data-filter-auto>
                            <option value="">All review statuses</option>
                            @foreach($reviewStatusOptions as $reviewOption)
                                <option value="{{ $reviewOption->value }}" @selected(request('review') === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                            @endforeach
                        </select>
                        <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                </div>
            </div>

            <div class="tr-filters__row2">
                <details class="admin-queue-filters-collapsible" data-queue-filters-mobile @if($activeFilters->except(['search'])->isNotEmpty()) open @endif>
                    <summary class="tr-clear" style="text-decoration:none">More filters</summary>
                    <div class="admin-queue-filters-collapsible__body">
                        <div class="tr-select tr-select--sort">
                            <select name="priority" aria-label="Priority" data-filter-auto>
                                <option value="">All priorities</option>
                                @foreach($priorityOptions as $priorityOption)
                                    <option value="{{ $priorityOption->value }}" @selected(request('priority') === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                                @endforeach
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <div class="tr-select tr-select--sort">
                            <select name="flagged" aria-label="Moderation" data-filter-auto>
                                <option value="">All complaints</option>
                                <option value="1" @selected(request('flagged') === '1')>Flagged only</option>
                            </select>
                            <svg class="tr-select__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 12 15 18 9"/></svg>
                        </div>
                        <label class="tr-search" for="date-from" style="flex: 1; min-width: 140px;">
                            <input type="date" id="date-from" name="date_from" class="tr-filter-input tr-filter-input--date" value="{{ request('date_from') }}" aria-label="From date">
                        </label>
                        <label class="tr-search" for="date-to" style="flex: 1; min-width: 140px;">
                            <input type="date" id="date-to" name="date_to" class="tr-filter-input tr-filter-input--date" value="{{ request('date_to') }}" aria-label="To date">
                        </label>
                    </div>
                </details>

                <button type="submit" class="tr-button" style="margin: 0;">Apply</button>
                @if($activeFilters->isNotEmpty())
                    <a href="{{ route('admin.complaints.index') }}" class="tr-clear">Clear</a>
                @endif
            </div>
        </form>

        @if($activeFilters->isNotEmpty())
            <div class="admin-filter-summary" role="status">
                <span class="admin-filter-summary__label">Filtering by</span>
                @foreach($activeFilters as $key => $value)
                    @php $remaining = request()->except($key); @endphp
                    <a href="{{ route('admin.complaints.index', $remaining) }}" class="admin-filter-summary__chip">{{ $value }}<span aria-hidden="true"> ×</span><span class="sr-only">Remove this filter</span></a>
                @endforeach
                <a href="{{ route('admin.complaints.index') }}" class="admin-filter-summary__clear">Clear all</a>
            </div>
        @endif
    </section>

    {{-- ════════════ QUEUE LIST ════════════ --}}
    <form method="POST" action="{{ route('admin.complaints.bulk-reassign') }}" data-bulk-form class="tr-container" style="max-width: none; padding: 0 24px 48px;">
        @csrf
        <section class="queue-list" aria-labelledby="complaint-queue-title">
            <div class="tr-register-head">
                <h2 id="complaint-queue-title" class="tr-h2">Complaint queue — {{ $complaints->total() }} {{ $complaints->total() === 1 ? 'complaint' : 'complaints' }}</h2>
                @if($complaints->hasPages())
                    <p class="tr-results">Page {{ $complaints->currentPage() }} of {{ $complaints->lastPage() }}</p>
                @endif
            </div>

            <div class="admin-queue-select-all-mobile" data-bulk-select-all-mobile>
                <label class="admin-queue-select-all-mobile__label">
                    <input type="checkbox" class="admin-queue-checkbox" data-select-all="complaint-checkboxes" aria-label="Select all complaints on this page">
                    <span>Select all</span>
                </label>
            </div>

            <ul class="report-list admin-list" id="complaint-table-body">
                <li class="admin-list-head admin-queue-desktop-only" aria-hidden="true">
                    <span class="admin-list-head__check"><input type="checkbox" class="admin-queue-checkbox" data-select-all="complaint-checkboxes" data-select-all-desktop aria-label="Select all complaints on this page"></span>
                    <span>Complaint</span>
                    <span>Department</span>
                    <span>State</span>
                    <span>Priority</span>
                    <span>Waiting</span>
                </li>

                @forelse($complaints as $complaint)
                    @php
                        $statusKey = \Illuminate\Support\Str::slug((string) $complaint->status, '_');
                        $reviewKey = \Illuminate\Support\Str::slug((string) $complaint->review_status, '_');
                        $qPriority = $complaint->confirmedPriorityEnum?->label() ?? 'Pending';
                        $qPrioritySlug = str($complaint->confirmedPriorityEnum?->value ?? 'pending')->slug();
                        $qWaiting = $complaint->created_at?->diffForHumans(short: true, syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) ?? '—';
                    @endphp
                    <li class="admin-queue-item"
                        data-report-row
                        data-report-id="{{ $complaint->ticket_id }}"
                        data-department="{{ $complaint->department?->name ?? '' }}"
                        data-category="{{ $complaint->category }}"
                        data-status="{{ $statusKey }}"
                        data-search="{{ \Illuminate\Support\Str::lower($complaint->ticket_id.' '.$complaint->title.' '.($complaint->user?->full_name ?? '').' '.$complaint->category) }}"
                        data-iso-date="{{ $complaint->created_at?->toAtomString() ?? '' }}">
                        <div class="admin-queue-card admin-queue-card--row">
                            <label class="admin-queue-check">
                                <input type="checkbox" class="admin-queue-checkbox" name="complaint_ids[]" value="{{ $complaint->id }}" data-complaint-checkbox aria-label="Select {{ $complaint->ticket_id }}">
                                <span class="admin-queue-check__box" aria-hidden="true"></span>
                            </label>
                            <a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-queue-link" wire:navigate>
                                <span class="admin-queue__complaint">
                                    @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                    <span class="admin-queue__text">
                                        <span class="admin-queue__title">{{ \Illuminate\Support\Str::limit($complaint->title, 90) }}</span>
                                        <span class="admin-queue__meta"><span class="ticket-id">{{ $complaint->ticket_id }}</span><span aria-hidden="true"> · </span>{{ str($complaint->category)->replace('_', ' ')->title() }}<span aria-hidden="true"> · </span>{{ $complaint->user?->full_name ?? 'Unknown resident' }}</span>
                                    </span>
                                </span>
                                <span class="admin-queue-cell admin-queue-cell--dept">
                                    @if($complaint->department)
                                        <span class="dept-badge" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span>
                                        <span class="admin-queue-deptname">{{ $complaint->department->name }}</span>
                                    @else
                                        <span class="admin-queue__unassigned">Unassigned</span>
                                    @endif
                                </span>
                                <span class="admin-queue-cell admin-queue-cell--state">
                                    <span class="status-pill {{ $statusKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->statusEnum->label() }}</span>
                                    <span class="status-pill {{ $reviewKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->reviewStatusEnum->label() }}</span>
                                    @if($complaint->isModerationFlagged())
                                        <span class="status-pill rejected"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->moderationLabel() }}</span>
                                    @endif
                                </span>
                                <span class="admin-queue-cell admin-queue-cell--priority">
                                    <span class="staff-priority staff-priority--{{ $qPrioritySlug }}">{{ $qPriority }}</span>
                                    @if($complaint->confirmedPriorityEnum !== null && $complaint->confirmedPriorityEnum->value !== $complaint->suggested_priority)
                                        <span class="admin-queue-subtext">Suggested: {{ str($complaint->suggested_priority)->title() }}</span>
                                    @endif
                                </span>
                                <span class="admin-queue-cell admin-queue-cell--age">
                                    <span class="admin-queue-agevalue">{{ $qWaiting }}</span>
                                    <span class="admin-queue-subtext">{{ $complaint->created_at?->format('M j, Y') }}</span>
                                </span>
                            </a>
                        </div>
                    </li>
                @empty
                    <li>
                        <div class="empty-state">
                            <span class="empty-state__icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v16H4z"/><path d="M8 8h8"/><path d="M8 12h5"/></svg>
                            </span>
                            @if($activeFilters->isNotEmpty())
                                <strong>No complaints match these filters.</strong>
                                <p><a href="{{ route('admin.complaints.index') }}" class="tr-button">Clear the filters</a></p>
                            @else
                                <strong>No complaints have been filed yet.</strong>
                            @endif
                        </div>
                    </li>
                @endforelse
            </ul>
        </section>

        {{-- Bulk reassignment bar — appears only when something is selected --}}
        <div class="admin-bulk-bar" data-bulk-bar hidden>
            <div class="admin-bulk-bar__count">
                <span class="admin-bulk-bar__number" data-bulk-count>0</span>
                <span data-bulk-noun>selected</span>
            </div>

            <label class="admin-field admin-field--compact" style="margin: 0;">
                <span class="sr-only">Reassign selected complaints to</span>
                <select name="department_id" class="tr-select__native" data-bulk-department aria-label="Reassign selected complaints to">
                    <option value="">Unassigned</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="tr-button" style="margin: 0;" data-bulk-submit>Reassign selected</button>
            <button type="button" class="tr-button" style="margin: 0;" data-bulk-clear>Clear selection</button>
            <p class="admin-bulk-bar__note" data-bulk-note>Ownership moves to the chosen office. The resident sees the update; the change is logged.</p>
        </div>
    </form>

    @if($complaints->hasPages())
        <div class="tr-container tr-pagination" style="max-width: none; padding: 0 24px 48px;">
            <p class="tr-showing">Page {{ $complaints->currentPage() }} of {{ $complaints->lastPage() }} · 5 per page</p>
            <div class="tr-pagination-nav">{{ $complaints->links() }}</div>
        </div>
    @endif
</div>

<style>
    /* The admin shell already offsets the fixed navbar — cancel the page's offset. */
    .admin-workspace .transparency-page,
    .admin-content .transparency-page { padding-top: 0; }

    /* Admin queue — light family over the dark admin workspace chrome */
    .tr-admin-banner-row { display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
    .queue-list { margin-top: 24px; margin-bottom: 24px; }
    .queue-section { margin-bottom: 16px; }

    .tr-filter-input,
    select[name="status"],
    select[name="department"],
    select[name="review"],
    select[name="priority"],
    select[name="flagged"] {
        box-sizing: border-box;
        width: 100%;
        height: 44px;
        padding: 0 36px 0 12px;
        border: 1px solid var(--line);
        border-radius: var(--radius-sm);
        background: var(--surface);
        color: var(--text);
        font-family: var(--font-sans);
        font-size: 14px;
        outline: none;
        appearance: none;
        cursor: pointer;
    }

    .tr-filter-input { padding: 0 12px; }
    .tr-filter-input[type="search"] { padding: 0 12px 0 42px; }
    .tr-filter-input--date { padding: 0 10px; font-size: 13px; }

    .admin-filter-summary {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 12px;
        padding: 8px 12px;
        border: 1px solid var(--navy-100);
        border-radius: var(--radius-sm);
        background: var(--navy-50);
    }

    .admin-filter-summary__label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--navy-700);
    }

    .admin-filter-summary__chip {
        display: inline-flex;
        align-items: center;
        min-height: 32px;
        padding: 0 12px;
        border-radius: var(--radius-pill);
        background: var(--surface);
        color: var(--navy-800);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
    }

    .admin-filter-summary__chip:hover { background: var(--navy-100); }
    .admin-filter-summary__chip:focus-visible { outline: none; box-shadow: var(--ring); }

    .admin-filter-summary__clear {
        margin-left: auto;
        font-size: 13px;
        font-weight: 600;
        color: var(--gold-700);
        text-decoration: underline;
        text-underline-offset: 3px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
    }

    .admin-queue-filters-collapsible > summary { list-style: none; cursor: pointer; }
    .admin-queue-filters-collapsible > summary::-webkit-details-marker { display: none; }
    .admin-queue-filters-collapsible__body { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; padding-top: 12px; }
    .admin-queue-filters-collapsible__body .tr-select { min-width: 180px; }

    .admin-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 12px; }

    .admin-list-head {
        display: grid;
        grid-template-columns: 40px minmax(0, 2.2fr) minmax(0, 1.4fr) minmax(0, 1.5fr) minmax(0, 1.1fr) minmax(0, 1fr);
        gap: 12px;
        padding: 10px 20px;
        background: var(--navy-50);
        border-radius: var(--radius-sm);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--muted);
    }

    .admin-list-head__check { display: flex; align-items: center; }

    .admin-queue-item {
        border: 1px solid var(--line-soft);
        border-radius: var(--radius-lg);
        background: var(--surface);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }

    .admin-queue-card {
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr);
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
    }

    .admin-queue-card--row { min-height: 76px; }

    .admin-queue-check { display: flex; align-items: center; cursor: pointer; }
    .admin-queue-checkbox {
        width: 20px;
        height: 20px;
        accent-color: var(--navy-700);
        cursor: pointer;
    }

    .admin-queue-link {
        display: grid;
        grid-template-columns: minmax(0, 2.2fr) minmax(0, 1.4fr) minmax(0, 1.5fr) minmax(0, 1.1fr) minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        min-width: 0;
        text-decoration: none;
        color: inherit;
    }

    .admin-queue-link:hover { background: var(--navy-50); }
    .admin-queue-link:focus-visible { outline: none; box-shadow: var(--ring-inset); }

    .admin-queue__complaint { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .admin-queue__text { min-width: 0; }

    .admin-queue__title {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.4;
        color: var(--text);
    }

    .admin-queue__meta {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: var(--muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .admin-queue__deptname { display: block; margin-top: 2px; font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .admin-queue__unassigned { font-size: 13px; color: var(--faint); font-weight: 600; }
    .admin-queue-subtext { display: block; margin-top: 4px; font-size: 11px; color: var(--faint); white-space: nowrap; }
    .admin-queue-cell { display: flex; flex-direction: column; gap: 4px; align-items: flex-start; min-width: 0; }
    .admin-queue-agevalue { font-size: 13px; font-weight: 600; color: var(--text); font-variant-numeric: tabular-nums; white-space: nowrap; }

    .staff-priority {
        display: inline-flex;
        align-items: center;
        height: 22px;
        padding: 0 8px;
        border-radius: var(--radius-pill);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        white-space: nowrap;
        background: var(--cat-flood-bg);
        color: var(--cat-flood-fg);
    }

    .staff-priority--routine { background: var(--cat-other-bg); color: var(--cat-other-fg); }
    .staff-priority--elevated { background: var(--cat-flood-bg); color: var(--cat-flood-fg); }
    .staff-priority--urgent { background: var(--status-progress-bg); color: var(--status-progress-text); }
    .staff-priority--critical { background: var(--status-rejected-bg); color: var(--status-rejected-text); }

    .admin-queue-select-all-mobile { display: none; margin-bottom: 8px; }

    .admin-bulk-bar {
        position: fixed;
        left: 50%;
        bottom: 24px;
        transform: translateX(-50%);
        z-index: var(--z-overlay, 1100);
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: var(--navy-900);
        color: var(--surface);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lift);
        max-width: min(1040px, calc(100vw - 32px));
        flex-wrap: wrap;
    }

    .admin-bulk-bar[hidden] { display: none; }
    .admin-bulk-bar__count { display: flex; align-items: baseline; gap: 6px; }
    .admin-bulk-bar__number { font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; }
    .admin-bulk-bar .tr-button { background: var(--navy-700); border-color: var(--navy-600); color: var(--surface); }
    .admin-bulk-bar .tr-button:hover { background: var(--navy-800); }
    .admin-bulk-bar select[data-bulk-department] {
        height: 44px;
        min-width: 200px;
        padding: 0 36px 0 12px;
        border: 1px solid var(--navy-700);
        border-radius: var(--radius-sm);
        background: var(--navy-800);
        color: var(--surface);
        font-family: var(--font-sans);
        font-size: 14px;
        appearance: none;
        cursor: pointer;
    }
    .admin-bulk-bar__note { margin: 0; flex-basis: 100%; font-size: 12px; color: rgba(255, 255, 255, 0.72); }

    @media (max-width: 1100px) {
        .admin-list-head { display: none; }
        .admin-queue-link {
            grid-template-columns: 1fr 1fr;
            row-gap: 12px;
        }
        .admin-queue__complaint { grid-column: 1 / -1; }
        .admin-queue-select-all-mobile { display: block; }
    }

    @media (max-width: 640px) {
        .admin-queue-link { grid-template-columns: 1fr; }
        .tr-admin-banner-row { align-items: flex-start; }
    }
</style>
@endsection

@push('scripts')
<script>
    (() => {
        const form = document.querySelector('[data-bulk-form]');
        if (!form) return;

        const selectAlls = [...form.querySelectorAll('[data-select-all="complaint-checkboxes"]')];
        const checkboxes = [...form.querySelectorAll('[data-complaint-checkbox]')];
        const bar = form.querySelector('[data-bulk-bar]');
        const count = form.querySelector('[data-bulk-count]');
        const noun = form.querySelector('[data-bulk-noun]');
        const department = form.querySelector('[data-bulk-department]');
        const submit = form.querySelector('[data-bulk-submit]');
        const clear = form.querySelector('[data-bulk-clear]');
        const note = form.querySelector('[data-bulk-note]');

        if (!selectAlls.length || !checkboxes.length || !bar) return;

        const selected = () => checkboxes.filter((checkbox) => checkbox.checked);

        const syncBulk = () => {
            const chosen = selected();
            const destination = department?.selectedOptions?.[0]?.textContent?.trim();

            bar.hidden = chosen.length === 0;
            count.textContent = String(chosen.length);
            noun.textContent = chosen.length === 1 ? 'complaint selected' : 'complaints selected';
            submit.disabled = !destination || chosen.length === 0;
            submit.textContent = destination
                ? `Reassign ${chosen.length} to ${destination}`
                : 'Reassign selected';

            if (note) {
                note.textContent = chosen.length === 0
                    ? ''
                    : destination === 'Unassigned'
                        ? 'Ownership will be cleared. The case returns to the unassigned queue.'
                        : `Ownership moves to ${destination}. The resident sees the update; the change is logged.`;
            }

            selectAlls.forEach((el) => {
                el.checked = chosen.length === checkboxes.length && checkboxes.length > 0;
                el.indeterminate = chosen.length > 0 && chosen.length < checkboxes.length;
            });
        };

        selectAlls.forEach((selectAll) => selectAll.addEventListener('change', () => {
            checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
            syncBulk();
        }));

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', syncBulk));
        department?.addEventListener('change', syncBulk);

        clear?.addEventListener('click', () => {
            checkboxes.forEach((checkbox) => { checkbox.checked = false; });
            syncBulk();
        });

        document.querySelectorAll('[data-filter-auto]').forEach((field) => {
            field.addEventListener('change', () => field.form?.requestSubmit());
        });

        const queueFilterDetails = document.querySelectorAll('details.admin-queue-filters-collapsible[data-queue-filters-mobile]');
        const qmq = window.matchMedia('(min-width: 641px)');
        function syncQueueDetails() { queueFilterDetails.forEach(function(d){ if (qmq.matches) d.open = true; }); }
        if (qmq.addEventListener) qmq.addEventListener('change', syncQueueDetails); else qmq.addListener(syncQueueDetails);
        syncQueueDetails();

        syncBulk();
    })();
</script>
@endpush
