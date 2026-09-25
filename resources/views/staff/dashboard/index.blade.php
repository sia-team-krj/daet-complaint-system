@extends('staff.layouts.app')

@section('title', 'Staff Dashboard — Daet Listens')
@section('staff-content')
<div class="staff-page">
    <header class="staff-page-header">
        <div>
            <h1 class="staff-page-title">Department overview</h1>
            <p class="staff-page-description">Welcome back, {{ $user->first_name }}. Here is the current service picture for {{ $department?->name ?? 'your department' }}.</p>
        </div>
    </header>

    @include('staff.partials.flash')

    <section class="staff-metric-grid" aria-label="Department overview metrics">
        <article class="staff-metric"><span>Pending review</span><strong>{{ $stats['new'] ?? 0 }}</strong></article>
        <article class="staff-metric"><span>In progress</span><strong>{{ $stats['inProgress'] ?? 0 }}</strong></article>
        <article class="staff-metric"><span>Resolved this month</span><strong>{{ $stats['resolvedThisMonth'] ?? 0 }}</strong></article>
        <article class="staff-metric"><span>Average days open</span><strong>{{ $stats['avgDays'] ?? '—' }}</strong></article>
    </section>

    <div class="staff-overview-grid">
        <section class="staff-list-section" aria-labelledby="priority-complaints-title">
            <div class="staff-section-heading">
                <div>
                    <h2 id="priority-complaints-title" class="staff-section-title">Priority complaints</h2>
                    <p class="staff-section-description">Oldest unresolved requests that need attention first.</p>
                </div>
                <a href="{{ route('staff.complaints.index') }}" class="staff-text-link">Open full queue <span aria-hidden="true">→</span></a>
            </div>

            <div class="staff-complaint-list">
                @forelse($priorityComplaints as $complaint)
                    @php $daysOpen = $complaint->created_at->diffInDays(now()); @endphp
                    <article class="staff-complaint-row {{ $daysOpen > 14 ? 'staff-complaint-row--overdue' : ($daysOpen > 7 ? 'staff-complaint-row--aging' : 'staff-complaint-row--normal') }}">
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
                            <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-button staff-button--compact staff-button--primary">Review</a>
                        </div>
                    </article>
                @empty
                    <div class="staff-empty-state">
                        <div class="staff-empty-icon" aria-hidden="true">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                        </div>
                        <h2>Queue is clear</h2>
                        <p>There are no unresolved complaints in this department.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="staff-activity-panel" aria-labelledby="department-activity-title">
            <div class="staff-section-heading">
                <div>
                    <h2 id="department-activity-title" class="staff-section-title">Recent department activity</h2>
                    <p class="staff-section-description">Latest updates from your team.</p>
                </div>
            </div>
            <div class="staff-activity-feed">
                @forelse($recentActivity as $log)
                    <div class="staff-activity-item">
                        <span class="staff-activity-marker" aria-hidden="true"></span>
                        <div>
                            <p class="staff-activity-item-title">{{ $log->actor?->full_name ?? 'A staff member' }} updated {{ $log->complaint?->ticket_id ?? 'a complaint' }}</p>
                            <p class="staff-activity-item-copy">{{ $log->comment ?: 'Status changed.' }}</p>
                            <time>{{ $log->created_at?->diffForHumans() }}</time>
                        </div>
                    </div>
                @empty
                    <p class="staff-empty-copy">No department activity has been recorded yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
