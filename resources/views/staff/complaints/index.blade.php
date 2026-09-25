@extends('staff.layouts.app')

@section('title', 'Complaint Queue — Staff — Daet Listens')
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
        <article class="staff-metric"><span>Average days open</span><strong>{{ $stats['avgDays'] ?? '—' }}</strong></article>
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

    <section class="staff-list-section" aria-labelledby="queue-list-title">
        <div class="staff-section-heading">
            <div>
                <h2 id="queue-list-title" class="staff-section-title">All queue items</h2>
                <p class="staff-section-description">{{ $complaints->total() }} {{ Str::plural('complaint', $complaints->total()) }} found.</p>
            </div>
        </div>

        <div class="staff-complaint-list">
            @forelse($complaints as $complaint)
                @php
                    $daysOpen = $complaint->created_at->diffInDays(now());
                    $ageClass = $daysOpen > 14 ? 'staff-complaint-row--overdue' : ($daysOpen > 7 ? 'staff-complaint-row--aging' : 'staff-complaint-row--normal');
                @endphp
                <article class="staff-complaint-row {{ $ageClass }}">
                    <div class="staff-complaint-row-top">
                        <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-ticket-id">{{ $complaint->ticket_id }}</a>
                        <span class="staff-status staff-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span>
                        <span class="staff-status staff-status--{{ str($complaint->review_status)->slug() }}">{{ $complaint->reviewStatusEnum->label() }}</span>
                        <span class="staff-priority-label">Suggested: {{ $complaint->suggestedPriorityEnum->label() }} · Confirmed: {{ $complaint->confirmedPriorityEnum?->label() ?? 'Pending' }}</span>
                        @if($complaint->isModerationFlagged())
                            <span class="staff-status staff-status--warning">{{ $complaint->moderationLabel() }}</span>
                        @endif
                    </div>
                    <div class="staff-complaint-row-body">
                        <div class="staff-complaint-copy">
                            <h3>{{ $complaint->title }}</h3>
                            <p>{{ str($complaint->category)->replace('_', ' ')->title() }}</p>
                        </div>
                        <div class="staff-complaint-meta">
                            <span>Filed {{ $complaint->created_at->format('M j, Y') }}</span>
                            <span class="staff-age-pill">{{ $daysOpen }} {{ Str::plural('day', $daysOpen) }} open</span>
                        </div>
                        <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-button staff-button--compact staff-button--primary">Review complaint</a>
                    </div>
                </article>
            @empty
                <div class="staff-empty-state">
                    <div class="staff-empty-icon" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16v16H4z"></path><path d="M8 8h8"></path><path d="M8 12h5"></path></svg>
                    </div>
                    <h2>No complaints found</h2>
                    <p>There are no complaints matching these filters.</p>
                </div>
            @endforelse
        </div>

        @if($complaints->hasPages())
            <div class="staff-pagination">{{ $complaints->links() }}</div>
        @endif
    </section>
</div>
@endsection
