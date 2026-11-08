@extends('admin.layouts.app')

@section('title', 'All Complaints - Admin')
@section('admin-content')
<div class="admin-page admin-page--directory">

    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">All complaints</h1>
            <p class="admin-page-description">Filter the municipal queue, reassign ownership, and review every service request.</p>
        </div>
        <a href="{{ route('admin.complaints.export', request()->query()) }}" class="admin-button admin-button--secondary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"></path><path d="m7 10 5 5 5-5"></path><path d="M5 21h14"></path></svg>
            Export CSV
        </a>
    </header>

    @include('admin.partials.flash')

    {{-- ══ Filtering ══════════════════════════════════════════

         Every filter the page always had is still here. What changed is that the
         admin can now see what is currently narrowing the queue.

         The old panel was eight controls and an "Apply filters" button with no
         statement of the resulting state: after applying, the only way to know
         the list was narrowed was to read eight dropdowns back. This states it,
         with one-click removal per filter.

         Three filters lead because they are the ones a triage pass uses, and the
         rest sit behind a disclosure rather than pushing the queue off the
         screen. Every one of them still applies - and the active summary proves
         it, so hiding them costs nothing. --}}
    @php
        // `::cases()` hands back a plain array, so the enum lookups are wrapped
        // before firstWhere(). Only $departments is already a Collection.
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

    <section class="admin-filter-panel" aria-label="Complaint filters">
        <form method="GET" action="{{ route('admin.complaints.index') }}" class="admin-filter-grid admin-complaints-filter-grid admin-queue-filters" data-filter-form>
            <label class="admin-field admin-field--wide admin-queue-filter-search">
                <span>Ticket ID</span>
                <input type="search" name="search" class="admin-input" placeholder="Search by ticket ID" value="{{ request('search') }}">
            </label>

            <details class="admin-queue-filters-collapsible" data-queue-filters-mobile @if($activeFilters->except(['search'])->isNotEmpty()) open @endif>
                <summary class="admin-button admin-button--secondary admin-queue-filters-toggle">
                    Filters
                    @if($activeFilters->except(['search'])->isNotEmpty())
                        <span class="admin-queue-filters-badge">{{ $activeFilters->except(['search'])->count() }}</span>
                    @endif
                </summary>
                <div class="admin-queue-filters-collapsible__body">
                    <label class="admin-field">
                        <span>Department</span>
                        <select name="department" class="admin-input" data-filter-auto>
                            <option value="">All departments</option>
                            <option value="unassigned" @selected(request('department') === 'unassigned')>Unassigned</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="admin-field">
                        <span>Status</span>
                        <select name="status" class="admin-input" data-filter-auto>
                            <option value="">All statuses</option>
                            @foreach($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </label>

                    {{-- Second row: the disclosure and the submit pair share it, so the
                         apply control never ends up stranded on a line of its own. --}}
                    <div class="admin-filter-secondary">
                        <details class="admin-filter-more" @if($activeFilters->hasAny(['review', 'priority', 'flagged', 'date_from', 'date_to'])) open @endif>
                            <summary class="admin-button admin-button--compact admin-button--secondary">
                                More filters
                                @if($activeFilters->intersect(array_keys(['review', 'priority', 'flagged', 'date_from', 'date_to']))->isNotEmpty())
                                    <span class="admin-filter-more__dot" aria-hidden="true"></span>
                                @endif
                            </summary>

                            <div class="admin-filter-more__body">
                                <label class="admin-field">
                                    <span>Review status</span>
                                    <select name="review" class="admin-input" data-filter-auto>
                                        <option value="">All review statuses</option>
                                        @foreach($reviewStatusOptions as $reviewOption)
                                            <option value="{{ $reviewOption->value }}" @selected(request('review') === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="admin-field">
                                    <span>Confirmed priority</span>
                                    <select name="priority" class="admin-input" data-filter-auto>
                                        <option value="">All priorities</option>
                                        @foreach($priorityOptions as $priorityOption)
                                            <option value="{{ $priorityOption->value }}" @selected(request('priority') === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="admin-field">
                                    <span>Moderation</span>
                                    <select name="flagged" class="admin-input" data-filter-auto>
                                        <option value="">All complaints</option>
                                        <option value="1" @selected(request('flagged') === '1')>Flagged only</option>
                                    </select>
                                </label>

                                <label class="admin-field">
                                    <span>From</span>
                                    <input type="date" name="date_from" class="admin-input" value="{{ request('date_from') }}">
                                </label>

                                <label class="admin-field">
                                    <span>To</span>
                                    <input type="date" name="date_to" class="admin-input" value="{{ request('date_to') }}">
                                </label>
                            </div>
                        </details>

                        <div class="admin-filter-actions">
                            <button type="submit" class="admin-button admin-button--primary">Apply</button>
                            @if($activeFilters->isNotEmpty())
                                <a href="{{ route('admin.complaints.index') }}" class="admin-button admin-button--secondary">Clear</a>
                            @endif
                        </div>
                    </div>
                </div>
            </details>
        </form>

        @if($activeFilters->isNotEmpty())
            {{-- The statement the old page never made. Without it a filtered
                 queue looks identical to an unfiltered one, and an admin has no
                 way to tell whether they are looking at 12 cases or all 340. --}}
            <div class="admin-filter-summary" role="status">
                <span class="admin-filter-summary__label">Filtering by</span>
                @foreach($activeFilters as $key => $value)
                    @php
                        // Drop just this filter, keep the rest.
                        $remaining = request()->except($key);
                    @endphp
                    <a href="{{ route('admin.complaints.index', $remaining) }}" class="admin-filter-summary__chip">
                        {{ $value }}
                        <span aria-hidden="true">×</span>
                        <span class="sr-only">Remove this filter</span>
                    </a>
                @endforeach
                <a href="{{ route('admin.complaints.index') }}" class="admin-filter-summary__clear">Clear all</a>
            </div>
        @endif
    </section>

    <form method="POST" action="{{ route('admin.complaints.bulk-reassign') }}" class="admin-bulk-form" data-bulk-form>
        @csrf
        <section class="admin-section admin-section--flush" aria-labelledby="complaint-queue-title">
            <div class="admin-section-heading admin-queue-section-heading">
                <div class="admin-section-heading__copy">
                    <h2 id="complaint-queue-title" class="admin-section-title">Complaint queue</h2>
                    <p class="admin-section-description">{{ $complaints->total() }} {{ Str::plural('complaint', $complaints->total()) }} found.</p>
                </div>
            </div>

            {{-- ══ The queue ══════════════════════════════════════

                 Eleven columns meant a queue that could not be scanned. It was
                 ordered by data-model field, so the two things that decide
                 whether a case needs an admin - its review state and whether it
                 is flagged - sat in columns seven and nine, behind department
                 and category. And the priority cell printed two lines, so every
                 single row was double height.

                 This keeps every field and reorders the row around the triage
                 decision instead: what the complaint is, what state it is in,
                 what it needs, how long it has waited.

                   - The ticket is the link. A "Manage" button repeated on every
                     row was a control where the ticket number already was.
                   - The title is here now. It was absent entirely, which meant
                     reading the queue required opening every single case.
                   - Moderation moved next to the complaint it concerns and only
                     appears when there is something to report; "Clear" on every
                     row was noise that buried the flagged ones.
                   - Priority is one line, and the suggested value only appears
                     when an admin has overridden it - which is the interesting
                     case. --}}
            <div class="admin-table-wrap admin-queue-wrap">
                <div class="admin-queue-select-all-mobile" data-bulk-select-all-mobile>
                    <label class="admin-queue-select-all-mobile__label">
                        <input type="checkbox" class="admin-queue-checkbox" data-select-all="complaint-checkboxes" aria-label="Select all complaints on this page">
                        <span>Select all</span>
                    </label>
                </div>
                <table class="admin-table admin-queue admin-table--queue-mobile">
                    {{-- Column widths live here, not on the cells.

                         With `table-layout: fixed` the browser sizes columns from
                         the first row or from <col>, and ignores widths declared
                         on body cells - so setting them on the `td`s collapsed
                         every column and the cells overlapped. This is the one
                         place a fixed layout reads widths from. --}}
                    <colgroup>
                        <col class="admin-queue__col-check">
                        <col class="admin-queue__col-complaint">
                        <col class="admin-queue__col-department">
                        <col class="admin-queue__col-state">
                        <col class="admin-queue__col-priority">
                        <col class="admin-queue__col-age">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="admin-table__checkbox"><input type="checkbox" class="admin-queue-checkbox" data-select-all="complaint-checkboxes" data-select-all-desktop aria-label="Select all complaints on this page"></th>
                            <th>Complaint</th>
                            <th>Department</th>
                            <th>State</th>
                            <th>Priority</th>
                            <th>Waiting</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complaints as $complaint)
                            @php
                                $qPriority = $complaint->confirmedPriorityEnum?->label() ?? 'Pending';
                                $qPrioritySlug = str($complaint->confirmedPriorityEnum?->value ?? 'pending')->slug();
                                $qWaiting = $complaint->created_at?->diffForHumans(short: true, syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) ?? '—';
                            @endphp
                            <tr class="admin-queue-row">
                                <td class="admin-table__checkbox admin-queue-cell--check" data-label="Select">
                                    <label class="admin-queue-check">
                                        <input type="checkbox" class="admin-queue-checkbox" name="complaint_ids[]" value="{{ $complaint->id }}" data-complaint-checkbox aria-label="Select {{ $complaint->ticket_id }}">
                                        <span class="admin-queue-check__box" aria-hidden="true"></span>
                                    </label>
                                </td>

                                <td class="admin-queue__complaint-cell admin-queue-cell--complaint" data-label="Complaint">
                                    <div class="admin-queue__complaint admin-queue-card">
                                        <div class="admin-queue-card__line1">
                                            <a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-ticket-id admin-queue-card__ticket">{{ $complaint->ticket_id }}</a>
                                            <span class="admin-queue-card__status admin-queue-mobile-only">
                                                <span class="admin-status admin-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span>
                                            </span>
                                        </div>
                                        <a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-queue__title admin-queue-card__title">{{ $complaint->title }}</a>
                                        <p class="admin-queue__meta admin-queue-card__meta">
                                            <span>{{ str($complaint->category)->replace('_', ' ')->title() }}</span>
                                            <span aria-hidden="true">·</span>
                                            <span>{{ $complaint->user?->full_name ?? 'Unknown resident' }}</span>
                                        </p>
                                        <div class="admin-queue-card__footer admin-queue-mobile-only">
                                            <span class="admin-queue-card__dept">
                                                @if($complaint->department)
                                                    <span class="dept-indicator" data-dept="{{ $complaint->department->code }}"><span class="dept-dot" data-dept="{{ $complaint->department->code }}" aria-hidden="true"></span><span class="dept-indicator__name">{{ $complaint->department->name }}</span></span>
                                                @else
                                                    <span class="admin-queue__unassigned">Unassigned</span>
                                                @endif
                                                <span class="admin-queue-card__filed">{{ $complaint->created_at?->format('M j') }}</span>
                                            </span>
                                            <span class="admin-queue-card__right">
                                                <span class="admin-queue-priority-chip admin-queue-priority-chip--{{ $qPrioritySlug }}">{{ $qPriority }}</span>
                                                <span class="admin-queue-card__wait">{{ $qWaiting }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <td class="admin-queue__department-cell admin-queue-cell--department admin-queue-desktop-only" data-label="Department">
                                    @if($complaint->department)
                                        <span class="admin-queue__department dept-indicator" data-dept="{{ $complaint->department->code }}">
                                            <span class="dept-dot" data-dept="{{ $complaint->department->code }}" aria-hidden="true"></span>
                                            <span class="dept-indicator__name">{{ $complaint->department->name }}</span>
                                        </span>
                                    @else
                                        <span class="admin-queue__unassigned">Unassigned</span>
                                    @endif
                                </td>

                                <td class="admin-queue-cell--state admin-queue-desktop-only" data-label="State">
                                    <div class="admin-queue__state">
                                        <span class="admin-status admin-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span>
                                        <span class="admin-status admin-status--{{ str($complaint->review_status)->slug() }}">{{ $complaint->reviewStatusEnum->label() }}</span>
                                        @if($complaint->isModerationFlagged())
                                            <span class="admin-status admin-status--warning">{{ $complaint->moderationLabel() }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="admin-queue-cell--priority admin-queue-desktop-only" data-label="Priority">
                                    <div class="admin-queue__priority">
                                        <span class="admin-queue__priority-value">{{ $complaint->confirmedPriorityEnum?->label() ?? 'Pending' }}</span>
                                        @if($complaint->confirmedPriorityEnum !== null
                                            && $complaint->confirmedPriorityEnum->value !== $complaint->suggested_priority)
                                            <span class="admin-queue__priority-note">suggested {{ str($complaint->suggested_priority)->title() }}</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="admin-queue-cell--age admin-queue-desktop-only" data-label="Waiting">
                                    <div class="admin-queue__age">
                                        <span>{{ $complaint->created_at?->diffForHumans(short: true, syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }}</span>
                                        <span class="admin-queue__age-date">{{ $complaint->created_at?->format('M j, Y') }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="admin-empty-cell">
                                @if($activeFilters->isNotEmpty())
                                    No complaints match these filters.
                                    <a href="{{ route('admin.complaints.index') }}" class="admin-queue__reset">Clear the filters</a> and try again.
                                @else
                                    No complaints have been filed yet.
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ══ Bulk reassignment ══════════════════════════════════

             Reassigning ownership is a mutating action that can hit every selected
             case at once, and it used to sit in the section heading beside a
             read-only result count - the last place a destructive control should
             be. It now appears only once something is selected, states how much
             is affected, and says what it will do before it is pressed. --}}
        <div class="admin-bulk-bar" data-bulk-bar hidden>
            <div class="admin-bulk-bar__count">
                <span class="admin-bulk-bar__number" data-bulk-count>0</span>
                <span data-bulk-noun>selected</span>
            </div>

            <label class="admin-field admin-field--compact">
                <span class="sr-only">Reassign selected complaints to</span>
                <select name="department_id" class="admin-input" data-bulk-department>
                    <option value="">Unassigned</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="admin-button admin-button--primary" data-bulk-submit>
                Reassign selected
            </button>

            <button type="button" class="admin-button admin-button--secondary" data-bulk-clear>
                Clear selection
            </button>

            <p class="admin-bulk-bar__note" data-bulk-note>
                Ownership moves to the chosen office. The resident sees the update; the change is logged.
            </p>
        </div>
    </form>

    @if($complaints->hasPages())
        <div class="admin-pagination admin-pagination--queue">
            <p class="admin-pagination__info-mobile">Page {{ $complaints->currentPage() }} of {{ $complaints->lastPage() }}</p>
            {{ $complaints->links() }}
        </div>
    @endif
</div>
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

        // The bar states the size of the change and names the destination, so
        // pressing the button is never a guess about what it will do.
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

        // Filters apply as they are changed. Triage is a narrowing pass, and
        // making an admin press a button after every dropdown undercuts it.
        // The Apply button stays for the date and text fields, which need typing.
        document.querySelectorAll('[data-filter-auto]').forEach((field) => {
            field.addEventListener('change', () => field.form?.requestSubmit());
        });

        // Desktop: force mobile <details> filter panel open (closed details hides content
        // even with display:contents). Matches audit trail fix.
        const queueFilterDetails = document.querySelectorAll('details.admin-queue-filters-collapsible[data-queue-filters-mobile]');
        const qmq = window.matchMedia('(min-width: 641px)');
        function syncQueueDetails() { queueFilterDetails.forEach(function(d){ if (qmq.matches) d.open = true; }); }
        if (qmq.addEventListener) qmq.addEventListener('change', syncQueueDetails); else qmq.addListener(syncQueueDetails);
        syncQueueDetails();

        syncBulk();
    })();
</script>
@endpush