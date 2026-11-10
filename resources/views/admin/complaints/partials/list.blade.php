{{--
    The queue list: the heading, the rows and the empty state.

    Swapped when the filters or the page change. The rows keep their checkboxes
    so the bulk bar keeps working; the bar's own JavaScript listens by
    delegation, so new rows are picked up without a re-bind.
--}}
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
                        <div class="admin-queue-card admin-queue-card--row admin-phone-card">
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
                                <span class="admin-queue-cell admin-queue-cell--dept admin-phone-cell" data-label="Department">
                                    <span class="admin-phone-values">
                                        @if($complaint->department)
                                            <span class="dept-badge" data-dept="{{ $complaint->department->code }}">{{ $complaint->department->code }}</span>
                                            <span class="admin-queue-deptname">{{ $complaint->department->name }}</span>
                                        @else
                                            <span class="admin-queue__unassigned">Unassigned</span>
                                        @endif
                                    </span>
                                </span>
                                <span class="admin-queue-cell admin-queue-cell--state admin-phone-cell" data-label="State">
                                    <span class="admin-phone-values">
                                        <span class="status-pill {{ $statusKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->statusEnum->label() }}</span>
                                        <span class="status-pill {{ $reviewKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->reviewStatusEnum->label() }}</span>
                                        @if($complaint->isModerationFlagged())
                                            <span class="status-pill rejected"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->moderationLabel() }}</span>
                                        @endif
                                    </span>
                                </span>
                                <span class="admin-queue-cell admin-queue-cell--priority admin-phone-cell" data-label="Priority">
                                    <span class="admin-phone-values">
                                        <span class="staff-priority staff-priority--{{ $qPrioritySlug }}">{{ $qPriority }}</span>
                                        @if($complaint->confirmedPriorityEnum !== null && $complaint->confirmedPriorityEnum->value !== $complaint->suggested_priority)
                                            <span class="admin-queue-subtext">Suggested: {{ str($complaint->suggested_priority)->title() }}</span>
                                        @endif
                                    </span>
                                </span>
                                <span class="admin-queue-cell admin-queue-cell--age admin-phone-cell" data-label="Waiting">
                                    <span class="admin-phone-values">
                                        <span class="admin-queue-agevalue">{{ $qWaiting }}</span>
                                        <span class="admin-queue-subtext">{{ $complaint->created_at?->format('M j, Y') }}</span>
                                    </span>
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
