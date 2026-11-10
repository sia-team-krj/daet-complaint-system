@extends('admin.layouts.app')

@section('title', 'All Complaints - Admin')
@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('admin-content')
<div class="transparency-page">
    {{-- ════════════ BANNER (workspace chrome stays dark; content goes family-light) ════════════ --}}
    <header class="tr-banner">
        <div class="tr-container">
            <div class="tr-admin-banner-row">
                <div>
                    <h1 class="tr-h1">Complaints</h1>
                    <p class="tr-privacy">Filter the municipal queue, reassign ownership, and review every service request.</p>
                </div>
                <a href="{{ route('admin.complaints.export', request()->query()) }}" class="admin-button admin-button--primary admin-export-button">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>Export CSV
                </a>
            </div>
        </div>
    </header>

    {{-- ════════════ STAT STRIP ════════════ --}}
    <div class="tr-container">
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
    {{-- $activeFilters comes from the controller, so the chips and the soft-
         navigation fragments are drawn from exactly the same state. --}}
    <section aria-labelledby="queue-filters-title" class="tr-container queue-section">
        <span id="queue-filters-title" class="sr-only">Complaint filters</span>
        <form data-loading-submit method="GET" action="{{ route('admin.complaints.index') }}" class="tr-card tr-filters" id="filter-bar" role="search" data-filter-form>
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
                    <summary class="tr-clear">More filters</summary>
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
                        <label class="tr-search admin-date-field" for="date-from">
                            <input type="date" id="date-from" name="date_from" class="tr-filter-input tr-filter-input--date" value="{{ request('date_from') }}" aria-label="From date">
                        </label>
                        <label class="tr-search admin-date-field" for="date-to">
                            <input type="date" id="date-to" name="date_to" class="tr-filter-input tr-filter-input--date" value="{{ request('date_to') }}" aria-label="To date">
                        </label>
                    </div>
                </details>

                <button type="submit" class="tr-button">Apply</button>
                @if($activeFilters->isNotEmpty())
                    <a href="{{ route('admin.complaints.index') }}" class="tr-clear">Clear</a>
                @endif
            </div>
        </form>

        {{-- The chips are swapped when a filter changes; the container stays. --}}
        <div data-queue-summary>
            @include('admin.complaints.partials.summary', ['activeFilters' => $activeFilters])
        </div>
    </section>

    {{-- ════════════ QUEUE LIST ════════════ --}}
    <form data-loading-submit method="POST" action="{{ route('admin.complaints.bulk-reassign') }}" data-bulk-form class="tr-container admin-queue-bulk">
        @csrf
        <section class="queue-list" aria-labelledby="complaint-queue-title">
            <div data-queue-list>
                @include('admin.complaints.partials.list', [
                    'complaints' => $complaints,
                    'activeFilters' => $activeFilters,
                ])
            </div>
        </section>

        {{-- Bulk reassignment bar — appears only when something is selected --}}
        <div class="admin-bulk-bar" data-bulk-bar hidden>
            <div class="admin-bulk-bar__count">
                <span class="admin-bulk-bar__number" data-bulk-count>0</span>
                <span data-bulk-noun>selected</span>
            </div>

            <label class="admin-field admin-field--compact">
                <span class="sr-only">Reassign selected complaints to</span>
                <select name="department_id" class="tr-select__native" data-bulk-department aria-label="Reassign selected complaints to">
                    <option value="">Unassigned</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="tr-button" data-bulk-submit>Reassign selected</button>
            <button type="button" class="tr-button" data-bulk-clear>Clear selection</button>
            <p class="admin-bulk-bar__note" data-bulk-note>Ownership moves to the chosen office. The resident sees the update; the change is logged.</p>
        </div>
    </form>

    <div data-queue-pagination>
        @include('admin.complaints.partials.pagination', ['complaints' => $complaints])
    </div>
</div>

<style>
    /* The admin shell already offsets the fixed navbar — cancel the page's offset. */
    .admin-workspace .transparency-page,
    .admin-content .transparency-page { padding-top: 0; }

    /* Workspace banners keep 48px breathing room: the shell provides the navbar
       offset, so the public banner's navbar-height term must not be added again. */
    .admin-workspace .tr-banner,
    .admin-content .tr-banner { padding-top: 48px; }

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

    /* The office name. The rule used to say `.admin-queue__deptname` while the
       markup says `.admin-queue-deptname`, so it never matched and the name
       rendered unstyled at body size. */
    .admin-queue-deptname { display: block; margin-top: 2px; font-size: 13px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
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
        z-index: var(--z-overlay);
        display: flex;
        align-items: center;
        gap: 12px;
        width: min(1040px, calc(100vw - 32px));
        max-width: calc(100vw - 32px);
        padding: 14px 16px;
        background: var(--navy-900);
        color: var(--surface);
        border: 1px solid color-mix(in srgb, var(--gold-500) 22%, var(--navy-800));
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lift);
        flex-wrap: wrap;
    }

    .admin-bulk-bar[hidden] { display: none; }
    .admin-bulk-bar__count { display: flex; align-items: baseline; gap: 6px; flex: 0 0 auto; }
    .admin-bulk-bar__number { font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; color: var(--gold-300); }
    .admin-bulk-bar [data-bulk-noun] { font-size: 13px; font-weight: 600; color: var(--surface); }

    .admin-bulk-bar .admin-field--compact { flex: 1 1 220px; min-width: min(220px, 100%); margin: 0; }
    .admin-bulk-bar select[data-bulk-department] {
        width: 100%;
        height: 44px;
        min-width: 0;
        padding: 0 36px 0 12px;
        border: 1px solid color-mix(in srgb, var(--surface) 18%, var(--navy-800));
        border-radius: var(--radius-sm);
        background-color: var(--navy-800);
        color: var(--surface);
        font-family: var(--font-sans);
        font-size: 14px;
        appearance: none;
        cursor: pointer;
    }
    .admin-bulk-bar select[data-bulk-department]:focus {
        outline: none;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--gold-500) 35%, transparent);
        border-color: var(--gold-500);
    }

    .admin-bulk-bar .tr-button {
        margin: 0;
        min-height: 44px;
        border-radius: var(--radius-sm);
        font-family: var(--font-sans);
        font-size: 13px;
        font-weight: 700;
    }
    .admin-bulk-bar [data-bulk-submit] {
        color: var(--navy-950);
        background: var(--gold-500);
        border-color: var(--gold-500);
    }
    .admin-bulk-bar [data-bulk-submit]:hover:not(:disabled) {
        color: var(--navy-950);
        background: var(--gold-300);
        border-color: var(--gold-300);
    }
    .admin-bulk-bar [data-bulk-submit]:disabled {
        color: color-mix(in srgb, var(--surface) 55%, transparent);
        background: var(--navy-800);
        border-color: color-mix(in srgb, var(--surface) 14%, var(--navy-800));
        cursor: not-allowed;
    }
    .admin-bulk-bar [data-bulk-clear] {
        color: var(--surface);
        background: transparent;
        border-color: color-mix(in srgb, var(--surface) 24%, transparent);
    }
    .admin-bulk-bar [data-bulk-clear]:hover {
        color: var(--surface);
        background: color-mix(in srgb, var(--surface) 10%, transparent);
        border-color: color-mix(in srgb, var(--surface) 34%, transparent);
    }

    .admin-bulk-bar__note {
        margin: 0;
        flex-basis: 100%;
        font-size: 12px;
        line-height: 1.45;
        color: color-mix(in srgb, var(--surface) 78%, transparent);
    }

    @media (max-width: 700px) {
        .admin-bulk-bar {
            left: 10px;
            right: 10px;
            bottom: max(10px, env(safe-area-inset-bottom));
            width: auto;
            max-width: none;
            transform: none;
            align-items: stretch;
            gap: 10px;
            padding: 12px;
            border-radius: var(--radius-sm);
        }
        .admin-bulk-bar__count {
            flex: 1 1 100%;
            align-items: center;
            gap: 8px;
        }
        .admin-bulk-bar__number { font-size: 18px; }
        .admin-bulk-bar [data-bulk-noun] { font-size: 13px; }
        .admin-bulk-bar .admin-field--compact,
        .admin-bulk-bar select[data-bulk-department] {
            width: 100%;
            min-width: 0;
            flex: 1 1 100%;
        }
        .admin-bulk-bar [data-bulk-submit],
        .admin-bulk-bar [data-bulk-clear] {
            flex: 1 1 calc(50% - 5px);
            min-width: 0;
            min-height: 42px;
            padding: 8px 8px;
            font-size: 12px;
            white-space: normal;
            line-height: 1.2;
        }
        .admin-bulk-bar__note {
            font-size: 11px;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    }

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
        .tr-admin-banner-row { align-items: flex-start; }

        /* ── The phone row ──────────────────────────────────────────────────
           The shared pattern (guide §5.35) owns the label column and the
           type scale; this block only decides how the queue's own pieces sit
           inside it. One column, the checkbox beside the title, the icon
           beside the title instead of floating in a corner, and the values
           stacked as labelled pairs. */
        .admin-queue-card {
            padding: 14px 16px;
        }

        .admin-queue-card--row { min-height: 0; }

        .admin-queue-link {
            grid-template-columns: minmax(0, 1fr);
            gap: 10px;
        }

        .admin-queue__complaint {
            align-items: flex-start;
            gap: 10px;
            padding-bottom: 2px;
        }

        .admin-queue__title {
            font-size: 15px;
            line-height: 1.35;
        }

        /* The icon is a companion of the title, not a badge in the corner:
           the shared report-row puts it last on a phone, which in this row
           would throw it to the far edge. */
        .admin-queue__complaint .cat-icon {
            order: 0;
            width: 30px;
            height: 30px;
        }

        .admin-queue__complaint .cat-icon svg { width: 15px; height: 15px; }

        /* The office name shares the line with its badge and truncates
           instead of dropping to a second line. */
        body.role-admin.admin-layout .admin-queue-cell--dept .admin-phone-values {
            flex-wrap: nowrap;
        }

        body.role-admin.admin-layout .admin-queue-deptname {
            flex: 1 1 auto;
            min-width: 0;
            margin-top: 0;
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* The select-all control was white on a light page — unreadable. */
        body.role-admin.admin-layout .admin-queue-select-all-mobile__label {
            color: var(--muted);
        }

        body.role-admin.admin-layout .admin-queue-select-all-mobile {
            margin-bottom: 4px;
        }

        /* The queue heading: one line, and the page count beside it. */
        .tr-register-head {
            gap: 4px;
            margin-bottom: 8px;
        }

        .admin-queue-bulk .tr-h2,
        .admin-queue-bulk .queue-list .tr-h2 {
            font-size: 17px;
            line-height: 1.3;
        }

        .admin-queue-bulk .tr-results {
            font-size: 13px;
        }
    }
</style>
@endsection

@push('scripts')
<script>
(() => {
    // ─── Soft navigation ────────────────────────────────────────────────────
    // The queue is drawn once. Changing a filter, turning a page or removing
    // a chip swaps the changed regions in place instead of redrawing the
    // browser. The URL stays the truth, so Back, Forward, a refresh and a
    // shared link behave exactly as before — and with JavaScript off every
    // link and form is still an ordinary link and form.
    const pagePath = new URL('{{ route('admin.complaints.index') }}', window.location.origin).pathname;
    const endpoint = '{{ route('admin.complaints.browser') }}';

    const summaryEl = document.querySelector('[data-queue-summary]');
    const listEl = document.querySelector('[data-queue-list]');
    const paginationEl = document.querySelector('[data-queue-pagination]');
    const filterForm = document.querySelector('[data-filter-form]');

    const jsonHeaders = { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

    async function navigate(url, parts, push) {
        try {
            const target = new URL(endpoint, window.location.origin);
            target.search = url.search;

            const response = await fetch(target, { headers: jsonHeaders });
            if (!response.ok) throw new Error('request failed');

            const payload = await response.json();
            if (parts.indexOf('summary') !== -1 && summaryEl) summaryEl.innerHTML = payload.summary;
            if (parts.indexOf('list') !== -1 && listEl) listEl.innerHTML = payload.list;
            if (parts.indexOf('pagination') !== -1 && paginationEl) paginationEl.innerHTML = payload.pagination;

            if (push !== false) history.pushState({ soft: true }, '', url.toString());

            // The rows were replaced: tell anything that tracks them.
            document.dispatchEvent(new CustomEvent('queue:refreshed'));
        } catch (error) {
            // Never leave the admin looking at a half-updated queue.
            window.location.href = url.toString();
        }
    }

    const queueParts = ['summary', 'list', 'pagination'];

    // The filter chips, the pager and the filter bar's own links.
    document.addEventListener('click', function(event) {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest('a[href]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

        const url = new URL(link.getAttribute('href'), window.location.origin);
        if (url.pathname !== pagePath) return;

        // Any link to this page is a change of state, not a change of page.
        event.preventDefault();
        navigate(url, queueParts);
    });

    // The filter form: the same URL a submit would have produced.
    if (filterForm) {
        filterForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // getAttribute, not .action: a control named "action" would shadow
            // the form's own property.
            const url = new URL(filterForm.getAttribute('action'), window.location.origin);
            const data = new FormData(filterForm);

            ['search', 'department', 'status', 'review', 'priority', 'flagged', 'date_from', 'date_to'].forEach(function(key) {
                const value = data.get(key);
                if (value) {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            });

            // A new filter starts at the first page of the queue.
            url.searchParams.delete('page');

            navigate(url, queueParts);
        });
    }

    // Back and forward move through the same history.
    window.addEventListener('popstate', function() {
        navigate(new URL(window.location.href), queueParts, false);
    });

    // ─── Bulk reassignment ──────────────────────────────────────────────────
    const form = document.querySelector('[data-bulk-form]');
    if (form) {
        const bar = form.querySelector('[data-bulk-bar]');
        const count = form.querySelector('[data-bulk-count]');
        const noun = form.querySelector('[data-bulk-noun]');
        const department = form.querySelector('[data-bulk-department]');
        const submit = form.querySelector('[data-bulk-submit]');
        const clear = form.querySelector('[data-bulk-clear]');
        const note = form.querySelector('[data-bulk-note]');

        if (bar) {
            // Live lookups: the rows are swapped when the filters change, and
            // delegation picks up the new checkboxes without a re-bind.
            const checkboxes = () => [...form.querySelectorAll('[data-complaint-checkbox]')];
            const selectAlls = () => [...form.querySelectorAll('[data-select-all="complaint-checkboxes"]')];

            const syncBulk = () => {
                const boxes = checkboxes();
                const chosen = boxes.filter((checkbox) => checkbox.checked);
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

                selectAlls().forEach((el) => {
                    el.checked = chosen.length === boxes.length && boxes.length > 0;
                    el.indeterminate = chosen.length > 0 && chosen.length < boxes.length;
                });
            };

            form.addEventListener('change', function(event) {
                const target = event.target;

                if (target.matches('[data-select-all="complaint-checkboxes"]')) {
                    checkboxes().forEach((checkbox) => { checkbox.checked = target.checked; });
                }

                syncBulk();
            });

            department?.addEventListener('change', syncBulk);

            clear?.addEventListener('click', function() {
                checkboxes().forEach((checkbox) => { checkbox.checked = false; });
                syncBulk();
            });

            // A swapped list carries no selection; reset the bar.
            document.addEventListener('queue:refreshed', syncBulk);

            // The selects that apply themselves.
            document.querySelectorAll('[data-filter-auto]').forEach((field) => {
                field.addEventListener('change', function() { field.form?.requestSubmit(); });
            });

            const queueFilterDetails = document.querySelectorAll('details.admin-queue-filters-collapsible[data-queue-filters-mobile]');
            const qmq = window.matchMedia('(min-width: 641px)');
            function syncQueueDetails() { queueFilterDetails.forEach(function(d){ if (qmq.matches) d.open = true; }); }
            if (qmq.addEventListener) qmq.addEventListener('change', syncQueueDetails); else qmq.addListener(syncQueueDetails);
            syncQueueDetails();

            syncBulk();
        }
    }
})();
</script>
@endpush
