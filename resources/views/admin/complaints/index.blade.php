@extends('admin.layouts.app')

@section('title', 'All Complaints — Admin')
@section('admin-content')
<div class="admin-page">
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

    <section class="admin-filter-panel" aria-label="Complaint filters">
        <form method="GET" action="{{ route('admin.complaints.index') }}" class="admin-filter-grid">
            <label class="admin-field">
                <span>Department</span>
                <select name="department" class="admin-input">
                    <option value="">All departments</option>
                    <option value="unassigned" @selected(request('department') === 'unassigned')>Unassigned</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="admin-field">
                <span>Status</span>
                <select name="status" class="admin-input">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="admin-field">
                <span>Review status</span>
                <select name="review" class="admin-input">
                    <option value="">All review statuses</option>
                    @foreach($reviewStatusOptions as $reviewOption)
                        <option value="{{ $reviewOption->value }}" @selected(request('review') === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="admin-field">
                <span>Confirmed priority</span>
                <select name="priority" class="admin-input">
                    <option value="">All priorities</option>
                    @foreach($priorityOptions as $priorityOption)
                        <option value="{{ $priorityOption->value }}" @selected(request('priority') === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="admin-field">
                <span>Moderation</span>
                <select name="flagged" class="admin-input">
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
            <label class="admin-field admin-field--wide">
                <span>Ticket ID</span>
                <input type="search" name="search" class="admin-input" placeholder="Search by ticket ID" value="{{ request('search') }}">
            </label>
            <div class="admin-filter-actions">
                <button type="submit" class="admin-button admin-button--primary">Apply filters</button>
                @if(request()->hasAny(['department', 'status', 'review', 'priority', 'flagged', 'date_from', 'date_to', 'search']))
                    <a href="{{ route('admin.complaints.index') }}" class="admin-button admin-button--secondary">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <form method="POST" action="{{ route('admin.complaints.bulk-reassign') }}" class="admin-bulk-form">
        @csrf
        <section class="admin-section admin-section--flush" aria-labelledby="complaint-queue-title">
            <div class="admin-section-heading">
                <div>
                    <h2 id="complaint-queue-title" class="admin-section-title">Complaint queue</h2>
                    <p class="admin-section-description">{{ $complaints->total() }} {{ Str::plural('complaint', $complaints->total()) }} found.</p>
                </div>
                <div class="admin-bulk-controls">
                    <label class="admin-field admin-field--compact">
                        <span>Reassign selected to</span>
                        <select name="department_id" class="admin-input">
                            <option value="">Unassigned</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="submit" class="admin-button admin-button--secondary" data-bulk-submit>Reassign selected</button>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="admin-table__checkbox"><input type="checkbox" data-select-all="complaint-checkboxes" aria-label="Select all complaints"></th>
                            <th>Ticket</th><th>Citizen</th><th>Department</th><th>Category</th><th>Status</th><th>Review</th><th>Priority</th><th>Moderation</th><th>Filed</th><th><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complaints as $complaint)
                            <tr>
                                <td class="admin-table__checkbox"><input type="checkbox" name="complaint_ids[]" value="{{ $complaint->id }}" data-complaint-checkbox aria-label="Select {{ $complaint->ticket_id }}"></td>
                                <td><span class="admin-ticket-id">{{ $complaint->ticket_id }}</span></td>
                                <td>{{ $complaint->user?->full_name ?? 'Unknown resident' }}</td>
                                <td>{{ $complaint->department?->name ?? 'Unassigned' }}</td>
                                <td>{{ str($complaint->category)->replace('_', ' ')->title() }}</td>
                                <td><span class="admin-status admin-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span></td>
                                <td><span class="admin-status admin-status--{{ str($complaint->review_status)->slug() }}">{{ $complaint->reviewStatusEnum->label() }}</span></td>
                                <td class="admin-muted-cell">
                                    <span>Suggested: {{ $complaint->suggestedPriorityEnum->label() }}</span><br>
                                    <span>Confirmed: {{ $complaint->confirmedPriorityEnum?->label() ?? 'Pending' }}</span>
                                </td>
                                <td>
                                    @if($complaint->isModerationFlagged())
                                        <span class="admin-status admin-status--warning">{{ $complaint->moderationLabel() }}</span>
                                    @else
                                        <span class="admin-muted-cell">Clear</span>
                                    @endif
                                </td>
                                <td class="admin-muted-cell">{{ $complaint->created_at?->format('M j, Y') }}</td>
                                <td><a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-button admin-button--compact admin-button--secondary">Manage</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="admin-empty-cell">No complaints match the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </form>

    @if($complaints->hasPages())
        <div class="admin-pagination">{{ $complaints->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const selectAll = document.querySelector('[data-select-all="complaint-checkboxes"]');
        const checkboxes = document.querySelectorAll('[data-complaint-checkbox]');
        const bulkSubmit = document.querySelector('[data-bulk-submit]');

        if (!selectAll || !checkboxes.length || !bulkSubmit) return;

        const syncBulkButton = () => {
            bulkSubmit.disabled = ![...checkboxes].some((checkbox) => checkbox.checked);
        };

        selectAll.addEventListener('change', () => {
            checkboxes.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
            syncBulkButton();
        });

        checkboxes.forEach((checkbox) => checkbox.addEventListener('change', syncBulkButton));
        syncBulkButton();
    })();
</script>
@endpush
