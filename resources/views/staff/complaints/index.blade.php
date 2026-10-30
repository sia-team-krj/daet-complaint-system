@extends('staff.layouts.app')

@section('title', 'Complaint Queue - Staff - Daet Listens')
@section('staff-content')
<div class="staff-page">
    <header class="staff-page-header">
        <div>
            <h1 class="staff-page-title">Complaint queue</h1>
            <p class="staff-page-description">All {{ $department?->name ?? 'department' }} complaints currently assigned to your office.</p>
        </div>
    </header>

    @include('staff.partials.flash')

    <section class="staff-metric-grid" aria-label="Queue metrics">
        <article class="staff-metric"><span>Pending review</span><strong>{{ $stats['new'] ?? 0 }}</strong></article>
        <article class="staff-metric"><span>In progress</span><strong>{{ $stats['inProgress'] ?? 0 }}</strong></article>
        <article class="staff-metric"><span>Resolved this month</span><strong>{{ $stats['resolvedThisMonth'] ?? 0 }}</strong></article>
        <article class="staff-metric"><span>Average days open</span><strong>{{ $stats['avgDays'] ?? '-' }}</strong></article>
    </section>

    <section class="staff-filter-panel" aria-label="Queue filters">
        <form method="GET" action="{{ route('staff.complaints.index') }}" class="staff-filter-form">
            <label class="staff-field">
                <span>Status</span>
                <select name="status" class="staff-input">
                    <option value="">All statuses</option>
                    <option value="Submitted" @selected(($status ?? '') === 'Submitted')>Submitted</option>
                    <option value="Under Review" @selected(($status ?? '') === 'Under Review')>Under review</option>
                    <option value="In Progress" @selected(($status ?? '') === 'In Progress')>In progress</option>
                    <option value="Resolved" @selected(($status ?? '') === 'Resolved')>Resolved</option>
                    <option value="Rejected" @selected(($status ?? '') === 'Rejected')>Rejected</option>
                </select>
            </label>
            <label class="staff-field">
                <span>Review status</span>
                <select name="review" class="staff-input">
                    <option value="">All review statuses</option>
                    @foreach($reviewStatusOptions as $reviewOption)
                        <option value="{{ $reviewOption->value }}" @selected(($reviewStatus ?? '') === $reviewOption->value)>{{ $reviewOption->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="staff-field">
                <span>Confirmed priority</span>
                <select name="priority" class="staff-input">
                    <option value="">All priorities</option>
                    @foreach($priorityOptions as $priorityOption)
                        <option value="{{ $priorityOption->value }}" @selected(($priority ?? '') === $priorityOption->value)>{{ $priorityOption->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="staff-field staff-field--search">
                <span>Search queue</span>
                <input type="search" name="search" class="staff-input" value="{{ $search ?? '' }}" placeholder="Ticket ID, title, or description">
            </label>
            <button type="submit" class="staff-button staff-button--primary">Apply filters</button>
            @if(request()->filled('status') || request()->filled('review') || request()->filled('priority') || request()->filled('search'))
                <a href="{{ route('staff.complaints.index') }}" class="staff-button staff-button--secondary">Clear</a>
            @endif
        </form>
    </section>

    <section class="staff-list-section staff-queue-section" aria-labelledby="queue-list-title">
        <div class="staff-section-heading">
            <div>
                <h2 id="queue-list-title" class="staff-section-title">Complaint queue table</h2>
                <p class="staff-section-description">{{ $complaints->total() }} {{ Str::plural('complaint', $complaints->total()) }} found · 5 per page.</p>
            </div>
            @if($complaints->hasPages())
                <span class="staff-queue-page-label">Page {{ $complaints->currentPage() }} of {{ $complaints->lastPage() }}</span>
            @endif
        </div>

        <div class="admin-table-wrap staff-queue-table-wrap">
            <table class="admin-table staff-queue-table">
                <caption class="sr-only">Complaint queue. Five complaints are shown per page.</caption>
                <thead>
                    <tr>
                        <th scope="col">Ticket</th>
                        <th scope="col">Complaint</th>
                        <th scope="col">Status</th>
                        <th scope="col">Review</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Age</th>
                        <th scope="col"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                        @php
                            $ageNow = now();
                            $ageSeconds = max(0, (int) $complaint->created_at->diffInSeconds($ageNow));
                            $ageDays = (int) floor($ageSeconds / 86400);
                            $ageClass = $ageDays > 14 ? 'staff-queue-row--overdue' : ($ageDays > 7 ? 'staff-queue-row--aging' : 'staff-queue-row--normal');

                            if ($ageSeconds < 60) {
                                $ageValue = 1;
                                $ageUnit = 'minute';
                            } elseif ($ageSeconds < 3600) {
                                $ageValue = max(1, (int) floor($ageSeconds / 60));
                                $ageUnit = 'minute';
                            } elseif ($ageSeconds < 86400) {
                                $ageValue = max(1, (int) floor($ageSeconds / 3600));
                                $ageUnit = 'hour';
                            } else {
                                $ageValue = max(1, (int) floor($ageSeconds / 86400));
                                $ageUnit = 'day';
                            }

                            $ageLabel = $ageValue.' '.Str::plural($ageUnit, $ageValue);
                            $priority = $complaint->confirmedPriorityEnum?->label() ?? $complaint->suggestedPriorityEnum->label();
                            $priorityValue = $complaint->confirmedPriorityEnum?->value ?? $complaint->suggested_priority ?? 'unset';
                        @endphp
                        <tr class="staff-queue-row {{ $ageClass }}">
                            <td data-label="Ticket">
                                <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-ticket-id">{{ $complaint->ticket_id }}</a>
                                <span class="staff-queue-subtext">Filed {{ $complaint->created_at->format('M j, Y') }}</span>
                            </td>
                            <td data-label="Complaint">
                                <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-queue-title">{{ $complaint->title }}</a>
                                <span class="staff-queue-subtext">{{ str($complaint->category)->replace('_', ' ')->title() }}</span>
                            </td>
                            <td data-label="Status">
                                <span class="staff-status staff-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span>
                                @if($complaint->isModerationFlagged())
                                    <span class="staff-status staff-status--warning staff-queue-secondary-status">{{ $complaint->moderationLabel() }}</span>
                                @endif
                            </td>
                            <td data-label="Review">
                                <span class="staff-status staff-status--{{ str($complaint->review_status)->slug() }}">{{ $complaint->reviewStatusEnum->label() }}</span>
                                @php
                                    $waitingOn = $complaint->informationRequests->first();
                                    // Same compact units as the Age column, so one
                                    // table never mixes "5d ago" with "5 days".
                                    $waitingDays = $waitingOn
                                        ? max(0, (int) floor($waitingOn->created_at->diffInSeconds($ageNow) / 86400))
                                        : 0;
                                    $waitingLabel = $waitingOn
                                        ? 'Waiting '.$waitingDays.' '.Str::plural('day', $waitingDays)
                                        : null;
                                @endphp
                                @if($waitingOn)
                                    <span class="staff-queue-subtext {{ $waitingOn->isStale() ? 'staff-queue-subtext--stale' : '' }}">
                                        {{ $waitingLabel }}@if($waitingOn->isStale()) · overdue @endif
                                    </span>
                                @endif
                            </td>
                            <td data-label="Priority">
                                <span class="staff-queue-priority staff-queue-priority--{{ str($priorityValue)->slug() }}">{{ $priority }}</span>
                                <span class="staff-queue-subtext">Suggested: {{ $complaint->suggestedPriorityEnum->label() }}</span>
                            </td>
                            <td data-label="Age">
                                <span class="staff-age-pill">{{ $ageLabel }} open</span>
                            </td>
                            <td data-label="Action">
                                <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-button staff-button--compact staff-button--primary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="staff-queue-empty">
                                <div class="staff-empty-icon" aria-hidden="true">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"></path><path d="M8 8h8"></path><path d="M8 12h5"></path></svg>
                                </div>
                                <strong>No complaints found</strong>
                                <span>There are no complaints matching these filters.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($complaints->hasPages())
            <div class="staff-pagination">{{ $complaints->links('pagination::workspace', ['ariaLabel' => 'Complaint queue pages']) }}</div>
        @endif
    </section>
</div>
@endsection
