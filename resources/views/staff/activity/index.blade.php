@extends('staff.layouts.app')

@section('title', 'My Activity - Staff - Daet Listens')
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <h1 class="tr-h1">Activity history</h1>
            <p class="tr-privacy">Review complaints where you added a response or status update.</p>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        {{-- Existing three statistics preserved exactly as implemented (no new stat cards, no icons added, data unchanged) --}}
        <section class="staff-metric-grid staff-metric-grid--three" aria-label="Personal activity metrics" style="margin-top: -16px; margin-bottom: 24px;">
            <article class="staff-metric"><span>Total responded</span><strong>{{ $stats['totalResponded'] ?? 0 }}</strong></article>
            <article class="staff-metric"><span>Resolved by me</span><strong>{{ $stats['resolvedByMe'] ?? 0 }}</strong></article>
            <article class="staff-metric"><span>Last activity</span><strong class="staff-metric-text">{{ $stats['lastActivity']?->diffForHumans() ?? '-' }}</strong></article>
        </section>

        @include('staff.partials.flash')

        <div class="tr-register-head" style="margin-top: 16px;">
            <h2 class="tr-h2" id="activity-list-title">My handled complaints</h2>
            <p class="tr-results">Only activity linked to your staff account is shown.</p>
        </div>

        <ul class="report-list" style="margin-top: 12px;">
            @forelse($complaints as $complaint)
                @php
                    $myLastLog = $complaint->logs->first();
                    $statusKey = str($complaint->status ?? 'submitted')->slug('_')->toString();
                    $reviewKey = str($complaint->review_status ?? 'pending')->slug('_')->toString();
                    $categoryLabel = $complaint->category ? str($complaint->category)->replace('_', ' ')->title()->toString() : __('staff.dashboard.category_fallback');
                    $activityText = $myLastLog?->comment ? Str::limit($myLastLog->comment, 100) : 'No response recorded yet.';
                    $activityTime = $myLastLog?->created_at?->diffForHumans() ?? 'No activity date';
                @endphp
                <li class="staff-queue-item">
                    <a href="{{ route('staff.complaints.show', $complaint) }}" class="staff-row">
                        {{-- 1. Primary information: Category icon, title, ticket ID, category --}}
                        <div class="staff-row__top">
                            <div class="staff-row__icon-wrapper">
                                @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                            </div>
                            <div class="staff-row__text">
                                <h3 class="staff-row__title">{{ Str::limit($complaint->title, 90) }}</h3>
                                <div class="staff-row__meta">
                                    <span class="ticket-id" style="font-family: var(--font-mono); font-weight: 700; color: var(--navy-800);">{{ $complaint->ticket_id }}</span>
                                    <span aria-hidden="true"> · </span>
                                    <span>{{ $categoryLabel }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- 2. Status information & 3. Activity information --}}
                        <div class="staff-row__badges">
                            <span class="status-pill {{ $statusKey }}">
                                <span class="status-pill__dot" aria-hidden="true"></span>
                                {{ $complaint->statusEnum->label() }}
                            </span>
                            <span class="status-pill {{ $reviewKey }}">
                                <span class="status-pill__dot" aria-hidden="true"></span>
                                {{ $complaint->reviewStatusEnum->label() }}
                            </span>
                            @if($complaint->isModerationFlagged())
                                <span class="status-pill rejected">
                                    <span class="status-pill__dot" aria-hidden="true"></span>
                                    {{ $complaint->moderationLabel() }}
                                </span>
                            @endif

                            {{-- Activity information: latest activity description & relative timestamp --}}
                            <div class="staff-activity-detail" style="width: 100%; margin-top: 6px; padding-top: 6px; border-top: 1px dashed var(--line-soft); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <span class="staff-row__subtext" style="color: var(--text); font-style: italic;">"{{ $activityText }}"</span>
                                <span class="staff-row__subtext" style="font-weight: 600; color: var(--muted);">{{ $activityTime }}</span>
                            </div>
                        </div>

                        {{-- 4. Action: View complaint button --}}
                        <div class="staff-row__age">
                            <span class="staff-button staff-button--compact staff-button--secondary" style="display: inline-flex; align-items: center; justify-content: center; min-height: 32px; padding: 4px 12px; border-radius: var(--radius-sm); background: var(--surface); color: var(--navy-800); border: 1px solid var(--line); font-size: 12px; font-weight: 600; text-decoration: none;">View complaint</span>
                        </div>
                    </a>
                </li>
            @empty
                <li>
                    <div class="empty-state">
                        <span class="empty-state__icon" aria-hidden="true">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg>
                        </span>
                        <strong>No activity yet</strong>
                        <p>You have not responded to any complaints yet.</p>
                    </div>
                </li>
            @endforelse
        </ul>

        @if($complaints->hasPages())
            <div class="staff-pagination-container" style="margin-top: 20px; margin-bottom: 60px;">
                <div class="staff-pagination-info">
                    <strong>{{ $complaints->total() }}</strong> handled complaints found
                </div>
                <div class="staff-pagination-desktop">
                    {{ $complaints->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<style>
    .staff-content .transparency-page { padding-top: 0; }
    .staff-content .tr-banner { padding-top: 32px; padding-bottom: 24px; }
    .staff-queue-item {
        border: 1px solid var(--line-soft);
        border-radius: var(--radius-lg);
        background: var(--surface);
        box-shadow: var(--shadow-card);
        overflow: hidden;
    }
    .staff-queue-item + .staff-queue-item { margin-top: 12px; }
    .staff-row {
        display: grid;
        grid-template-columns: minmax(0, 1.8fr) minmax(0, 1.4fr) minmax(100px, auto);
        gap: 16px;
        align-items: center;
        padding: 16px 20px;
        text-decoration: none;
        color: inherit;
    }
    .staff-row:hover { background: var(--navy-50); }
    .staff-row:focus-visible { outline: none; box-shadow: var(--ring-inset); }
    .staff-row__top { display: flex; align-items: flex-start; gap: 14px; min-width: 0; }
    .staff-row__icon-wrapper {
        flex-shrink: 0; width: 36px; height: 36px; border-radius: var(--radius-sm);
        background: var(--navy-50); color: var(--navy-800);
        display: flex; align-items: center; justify-content: center;
    }
    .staff-row__text { min-width: 0; }
    .staff-row__title {
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
        overflow: hidden; font-size: 14px; font-weight: 700; line-height: 1.4; color: var(--text); margin: 0 0 3px 0;
    }
    .staff-row__meta { display: flex; align-items: center; flex-wrap: wrap; gap: 4px; font-size: 11px; color: var(--muted); }
    .staff-row__badges { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; min-width: 0; }
    .staff-row__subtext { font-size: 11px; color: var(--muted); }
    .staff-row__age { display: flex; justify-content: flex-end; align-items: center; }
    @media (max-width: 1023px) {
        .staff-row { grid-template-columns: minmax(0, 1fr) auto; gap: 12px; padding: 14px; }
        .staff-row__badges { grid-column: span 2; padding-top: 6px; border-top: 1px solid var(--line-soft); }
        .staff-row__age { grid-column: 2; grid-row: 1; }
    }
    @media (max-width: 599px) {
        .staff-row { grid-template-columns: 1fr; gap: 10px; padding: 12px; }
        .staff-row__age { grid-column: 1; grid-row: auto; justify-content: flex-start; }
        .staff-row__badges { grid-column: 1; }
    }
</style>
@endsection
