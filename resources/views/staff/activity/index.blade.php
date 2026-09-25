@extends('staff.layouts.app')

@section('title', 'My Activity — Staff — Daet Listens')
@section('staff-content')
<div class="staff-page">
    <header class="staff-page-header">
        <div>
            <h1 class="staff-page-title">Activity history</h1>
            <p class="staff-page-description">Review complaints where you added a response or status update.</p>
        </div>
    </header>

    @include('staff.partials.flash')

    <section class="staff-metric-grid staff-metric-grid--three" aria-label="Personal activity metrics">
        <article class="staff-metric"><span>Total responded</span><strong>{{ $stats['totalResponded'] ?? 0 }}</strong></article>
        <article class="staff-metric"><span>Resolved by me</span><strong>{{ $stats['resolvedByMe'] ?? 0 }}</strong></article>
        <article class="staff-metric"><span>Last activity</span><strong class="staff-metric-text">{{ $stats['lastActivity']?->diffForHumans() ?? '—' }}</strong></article>
    </section>

    <section class="staff-list-section" aria-labelledby="activity-list-title">
        <div class="staff-section-heading">
            <div>
                <h2 id="activity-list-title" class="staff-section-title">My handled complaints</h2>
                <p class="staff-section-description">Only activity linked to your staff account is shown.</p>
            </div>
        </div>

        <div class="staff-complaint-list">
            @forelse($complaints as $complaint)
                @php
                    $myLastLog = $complaint->logs->first();
                    $statusEnum = $complaint->statusEnum;
                @endphp
                <article class="staff-complaint-row staff-complaint-row--normal">
                    <div class="staff-complaint-row-top">
                        <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-ticket-id">{{ $complaint->ticket_id }}</a>
                        <span class="staff-status staff-status--{{ str($complaint->status)->slug() }}">{{ $statusEnum->label() }}</span>
                        <span class="staff-status staff-status--{{ str($complaint->review_status)->slug() }}">{{ $complaint->reviewStatusEnum->label() }}</span>
                        <span class="staff-priority-label">Suggested: {{ $complaint->suggestedPriorityEnum->label() }} · Confirmed: {{ $complaint->confirmedPriorityEnum?->label() ?? 'Pending' }}</span>
                        @if($complaint->isModerationFlagged())
                            <span class="staff-status staff-status--warning">{{ $complaint->moderationLabel() }}</span>
                        @endif
                    </div>
                    <div class="staff-complaint-row-body">
                        <div class="staff-complaint-copy">
                            <h3>{{ $complaint->title }}</h3>
                            <p>{{ $myLastLog?->comment ? Str::limit($myLastLog->comment, 100) : 'No response recorded yet.' }}</p>
                        </div>
                        <div class="staff-complaint-meta">
                            <span>{{ $myLastLog?->created_at?->diffForHumans() ?? 'No activity date' }}</span>
                        </div>
                        <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-button staff-button--compact staff-button--secondary">View complaint</a>
                    </div>
                </article>
            @empty
                <div class="staff-empty-state">
                    <div class="staff-empty-icon" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg>
                    </div>
                    <h2>No activity yet</h2>
                    <p>You have not responded to any complaints yet.</p>
                </div>
            @endforelse
        </div>

        @if($complaints->hasPages())
            <div class="staff-pagination">{{ $complaints->links() }}</div>
        @endif
    </section>
</div>
@endsection
