@extends('staff.layouts.app')

@section('title', __('staff.activity.department_title') . ' - ' . __('staff.title'))
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@section('staff-content')
<div class="transparency-page">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <h1 class="tr-h1">{{ __('staff.activity.department_title') }}</h1>
            <p class="tr-privacy">
                {{ $department->name }}
                — {{ trans_choice('staff.activity.department_lede', $stats['actions'], ['count' => $stats['actions']]) }}
            </p>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        <section class="dept-stats" aria-label="{{ __('staff.activity.department_title') }}" style="margin-top: -16px; margin-bottom: 24px;">
            <div class="dept-stat">
                <span class="tr-stat__icon tr-stat__icon--navy dept-si dept-si--navy" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>
                </span>
                <span>
                    <span class="dept-stat__value">{{ $stats['actions'] }}</span>
                    <span class="dept-stat__label">{{ __('staff.activity.metric_actions') }}</span>
                </span>
            </div>
            <div class="dept-stat">
                <span class="tr-stat__icon tr-stat__icon--sky dept-si dept-si--sky" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>
                </span>
                <span>
                    <span class="dept-stat__value">{{ $stats['complaints'] }}</span>
                    <span class="dept-stat__label">{{ __('staff.activity.metric_complaints') }}</span>
                </span>
            </div>
            <div class="dept-stat">
                <span class="tr-stat__icon tr-stat__icon--amber dept-si dept-si--amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
                </span>
                <span>
                    <span class="dept-stat__value">{{ $stats['lastAction']?->diffForHumans() ?? '-' }}</span>
                    <span class="dept-stat__label">{{ __('staff.activity.metric_last') }}</span>
                </span>
            </div>
        </section>

        @include('staff.partials.flash')

        <div class="tr-register-head" style="margin-top: 16px;">
            <h2 class="tr-h2" id="department-activity-title">{{ __('staff.activity.department_feed_title') }}</h2>
            <p class="tr-results">{{ __('staff.activity.department_feed_lede') }}</p>
        </div>

        <ul class="report-list" style="margin-top: 12px;">
            @forelse($entries as $entry)
                @php
                    $complaint = $entry->complaint;
                    $complaintUrl = $complaint ? route('staff.complaints.show', $complaint) : null;
                    $statusKey = str($complaint?->status ?? 'submitted')->slug('_')->toString();
                    $reviewKey = str($complaint?->review_status ?? 'pending')->slug('_')->toString();
                    $categoryLabel = $complaint?->category
                        ? str($complaint->category)->replace('_', ' ')->title()->toString()
                        : __('staff.dashboard.category_fallback');
                    $activityText = $entry->display_detail;
                    if (! empty($entry->display_note)) {
                        $activityText .= ' · ' . __('staff.dashboard.note_label') . ' ' . $entry->display_note;
                    }
                    if (! empty($entry->display_count)) {
                        $activityText .= ' · ' . trans_choice('staff.dashboard.activity_updates_count', $entry->display_count, ['count' => $entry->display_count]);
                    }
                    $activityTime = $entry->created_at?->diffForHumans() ?? '-';
                @endphp
                <li class="staff-queue-item">
                    @if ($complaintUrl)
                        <a href="{{ $complaintUrl }}" class="staff-row" aria-label="{{ __('staff.dashboard.view_complaint', ['ticket' => $complaint?->ticket_id ?? '']) }}">
                    @else
                        <div class="staff-row">
                    @endif
                        <div class="staff-row__top">
                            <div class="staff-row__icon-wrapper">
                                @include('pages.transparency.partials.category-icon', ['category' => $complaint?->category])
                            </div>
                            <div class="staff-row__text">
                                <h3 class="staff-row__title">{{ $entry->display_title }}</h3>
                                <div class="staff-row__meta">
                                    <span class="ticket-id" style="font-family: var(--font-mono); font-weight: 700; color: var(--navy-800);">{{ $complaint?->ticket_id ?? __('staff.dashboard.activity_ticket_fallback') }}</span>
                                    <span aria-hidden="true"> · </span>
                                    <span>{{ $categoryLabel }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="staff-row__badges">
                            @if ($complaint)
                                <span class="status-pill {{ $statusKey }}">
                                    <span class="status-pill__dot" aria-hidden="true"></span>
                                    {{ $complaint->statusEnum->label() }}
                                </span>
                                <span class="status-pill {{ $reviewKey }}">
                                    <span class="status-pill__dot" aria-hidden="true"></span>
                                    {{ $complaint->reviewStatusEnum->label() }}
                                </span>
                            @endif

                            <div class="staff-activity-detail" style="width: 100%; margin-top: 6px; padding-top: 6px; border-top: 1px dashed var(--line-soft); display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <span class="staff-row__subtext" style="color: var(--text);">{{ $activityText }}</span>
                                <span class="staff-row__subtext" style="font-weight: 600; color: var(--muted);">{{ $activityTime }}</span>
                            </div>
                        </div>

                        <div class="staff-row__age">
                            @if ($complaintUrl)
                                <span class="staff-button staff-button--compact staff-button--secondary" style="display: inline-flex; align-items: center; justify-content: center; min-height: 32px; padding: 4px 12px; border-radius: var(--radius-sm); background: var(--surface); color: var(--navy-800); border: 1px solid var(--line); font-size: 12px; font-weight: 600; text-decoration: none;">{{ __('staff.dashboard.view_complaint_short', ['default' => 'View complaint']) }}</span>
                            @endif
                        </div>
                    @if ($complaintUrl)
                        </a>
                    @else
                        </div>
                    @endif
                </li>
            @empty
                <li>
                    <div class="empty-state">
                        <span class="empty-state__icon" aria-hidden="true">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h4l3-8 4 16 3-8h4"></path></svg>
                        </span>
                        <strong>{{ __('staff.dashboard.no_activity') }}</strong>
                        <p>{{ __('staff.activity.department_feed_lede') }}</p>
                    </div>
                </li>
            @endforelse
        </ul>

        @if ($logs->hasPages())
            <div class="staff-pagination-container" style="margin-top: 20px; margin-bottom: 60px;">
                <div class="staff-pagination-desktop">
                    {{ $logs->links() }}
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
