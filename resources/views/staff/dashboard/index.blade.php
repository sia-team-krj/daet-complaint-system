@extends('staff.layouts.app')

@section('title', __('staff.dashboard.title'))
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@php
    $dept = $department;
    $deptName = $dept?->name ?? __('nav.department_pending');
    $avgDaysRaw = $stats['avgDays'] ?? null;
    $avgNum = null;
    $avgUnit = null;
    if ($avgDaysRaw === null || $avgDaysRaw === '') {
        $avgNum = '—';
    } elseif ((float) $avgDaysRaw < 1) {
        $avgNum = '<1';
        $avgUnit = __('staff.dashboard.day_unit');
    } else {
        $avgNum = number_format((float) $avgDaysRaw, 1);
        $avgUnit = __('staff.dashboard.days_unit');
    }
    $isVerifier = $isVerifier ?? true;
    $roleLabel = $user->isDepartmentAdmin()
        ? __('nav.department_administrator')
        : ($isVerifier ? __('roles.verifier') : __('roles.staff'));
    $statCards = $isVerifier
        ? [
            ['label' => __('staff.stats.pending_review'), 'value' => $stats['new'] ?? 0, 'href' => route('staff.complaints.index', ['review' => 'pending']), 'icon' => 'clock'],
            ['label' => __('staff.stats.in_progress'), 'value' => $stats['inProgress'] ?? 0, 'href' => route('staff.complaints.index', ['status' => 'in_progress']), 'icon' => 'progress'],
            ['label' => __('staff.stats.resolved_this_month'), 'value' => $stats['resolvedThisMonth'] ?? 0, 'href' => route('staff.complaints.index', ['status' => 'resolved']), 'icon' => 'check'],
            ['label' => __('staff.stats.avg_days_open'), 'value' => $avgNum, 'unit' => $avgUnit, 'href' => null, 'icon' => 'calendar'],
        ]
        : [
            ['label' => __('staff.stats.assigned_to_me'), 'value' => $stats['assignedToMe'] ?? 0, 'href' => route('staff.complaints.index', ['assigned_staff_id' => $user->id, 'scope' => 'all']), 'icon' => 'user'],
            ['label' => __('staff.stats.in_progress'), 'value' => $stats['inProgress'] ?? 0, 'href' => route('staff.complaints.index', ['assigned_staff_id' => $user->id, 'status' => 'in_progress']), 'icon' => 'progress'],
            ['label' => __('staff.stats.resolved_this_month'), 'value' => $stats['resolvedThisMonth'] ?? 0, 'href' => route('staff.complaints.index', ['assigned_staff_id' => $user->id, 'status' => 'resolved', 'scope' => 'finished']), 'icon' => 'check'],
            ['label' => __('staff.stats.avg_days_open'), 'value' => $avgNum, 'unit' => $avgUnit, 'href' => null, 'icon' => 'calendar'],
        ];
@endphp

@section('staff-content')
<div class="dept-dashboard">
    <div class="dept-dashboard__main">
        <header class="dept-page-header">
            <div>
                <p class="dept-page-header__eyebrow">{{ $deptName }}</p>
                <h1 class="dept-page-header__title">{{ __('staff.dashboard.welcome', ['name' => $user->first_name]) }}</h1>
                <p class="dept-page-header__meta">
                    <span class="dept-role-chip">{{ $roleLabel }}</span>
                    <span class="dept-page-header__sep" aria-hidden="true">·</span>
                    <span>{{ $isVerifier ? __('staff.dashboard.overview') : __('staff.dashboard.my_work_overview') }}</span>
                </p>
            </div>
        </header>

        <section class="dept-stats" aria-label="{{ $isVerifier ? __('staff.dashboard.overview') : __('staff.dashboard.my_work_overview') }}">
            @foreach($statCards as $stat)
                @php $tag = $stat['href'] ? 'a' : 'div'; @endphp
                <{{ $tag }} @if($stat['href']) href="{{ $stat['href'] }}" @endif class="dept-stat {{ $stat['href'] ? 'dept-stat--link' : '' }}" @if($stat['href']) aria-label="{{ $stat['label'] }}: {{ $stat['value'] }}{{ !empty($stat['unit']) ? ' '.$stat['unit'] : '' }}" @endif>
                    <span class="dept-stat__icon dept-stat__icon--{{ $stat['icon'] }}" aria-hidden="true">
                        @if($stat['icon'] === 'clock')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
                        @elseif($stat['icon'] === 'progress')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="M12 18v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="m16.24 16.24 2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="m16.24 7.76 2.83-2.83"/></svg>
                        @elseif($stat['icon'] === 'check')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        @elseif($stat['icon'] === 'user')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        @endif
                    </span>
                    <span class="dept-stat__body">
                        <span class="dept-stat__value">@if(!empty($stat['unit']))<span class="dept-stat__num">{{ $stat['value'] }}</span><span class="dept-stat__unit">{{ $stat['unit'] }}</span>@else{{ $stat['value'] }}@endif</span>
                        <span class="dept-stat__label">{{ $stat['label'] }}</span>
                    </span>
                </{{ $tag }}>
            @endforeach
        </section>

        @include('staff.partials.flash')

                <section class="dept-priority" aria-labelledby="priority-complaints-title">
                    <div class="dept-section-head">
                        <div>
                            <h2 id="priority-complaints-title" class="dept-section-title">{{ __('staff.dashboard.priority_title') }}</h2>
                            <p class="dept-section-sub">{{ __('staff.dashboard.priority_sub') }}</p>
                        </div>
        <div class="dept-dashboard__content-grid">
            <section class="dept-panel" aria-labelledby="priority-complaints-title">
                <div class="dept-panel__head">
                    <div>
                        <h2 id="priority-complaints-title" class="dept-panel__title">{{ __('staff.dashboard.priority_title') }}</h2>
                        <p class="dept-panel__sub">{{ __('staff.dashboard.priority_sub') }}</p>
                    </div>
                    <a href="{{ route('staff.complaints.index') }}" class="dept-text-link">{{ __('staff.dashboard.open_full_queue') }} <span aria-hidden="true">→</span></a>
                </div>

                <form data-loading-submit method="GET" action="{{ route('staff.dashboard') }}#priority-complaints-title" class="dept-filters" role="search">
                    <label class="dept-search" for="priority-q">
                        <span class="sr-only">{{ __('staff.dashboard.search_placeholder') }}</span>
                        <svg class="dept-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input id="priority-q" type="search" name="q" value="{{ $prioritySearch }}" placeholder="{{ __('staff.dashboard.search_placeholder') }}" autocomplete="off">
                    </label>
                    <button type="submit" class="dept-btn">{{ __('staff.dashboard.search') }}</button>
                </form>

                <div class="dept-priority-list">
                    @forelse($priorityComplaints as $complaint)
                        @php
                            $created = $complaint->created_at;
                            $relative = $created ? $created->locale(app()->getLocale())->diffForHumans() : '';
                            $isJustNow = $created ? $created->diffInMinutes(now()) < 60 : false;
                            $relativeLabel = $isJustNow ? __('staff.dashboard.just_now') : ($relative ?: '');
                            $filedLabel = __('staff.dashboard.filed_relative', ['time' => $relativeLabel]);
                            $exactDate = $created?->translatedFormat('M j, Y g:i A') ?? '';
                            $statusKey = str($complaint->status ?? 'submitted')->slug('_')->toString();
                            $priorityEnum = $complaint->confirmedPriorityEnum ?? $complaint->suggestedPriorityEnum;
                            $priorityKey = $priorityEnum?->value ?? 'routine';
                            $categoryLabel = \App\Services\DepartmentRouter::categoryLabel($complaint->category) ?? __('staff.dashboard.category_fallback');
                            $suggested = $complaint->suggestedPriorityEnum?->label() ?? __('staff.dashboard.confirmed_pending');
                            $confirmed = $complaint->confirmedPriorityEnum?->label() ?? __('staff.dashboard.confirmed_pending');
                            $confirmedPending = $complaint->confirmedPriorityEnum?->value === null;
                            $suggestedConfirmedSame = $complaint->confirmedPriorityEnum?->value !== null && $complaint->suggestedPriorityEnum?->value === $complaint->confirmedPriorityEnum?->value;
                            $showSuggestedConfirmed = ! $suggestedConfirmedSame;
                        @endphp
                        <article class="dept-complaint">
                            <a href="{{ route('staff.complaints.show', $complaint) }}" class="dept-complaint__link" aria-label="{{ __('staff.review') }} {{ $complaint->ticket_id }}">
                                <span class="dept-complaint__main">
                                    @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                    <span class="dept-complaint__text">
                                        <span class="dept-complaint__title">{{ $complaint->title }}</span>
                                        <span class="dept-complaint__meta"><span class="ticket-id">{{ $complaint->ticket_id }}</span><span aria-hidden="true"> · </span>{{ $categoryLabel }}</span>
                                    </span>
                                </span>
                                <span class="dept-complaint__status">
                                    <span class="status-pill {{ $statusKey }}"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->statusEnum->label() }}</span>
                                </span>
                                <span class="dept-complaint__priority">
                                    <span class="dept-priority-badge dept-priority-badge--{{ $priorityKey }}">{{ $priorityEnum?->label() ?? __('statuses.priority.routine') }}</span>
                                    @if($showSuggestedConfirmed)
                                        <span class="dept-complaint__subtext">{{ $confirmedPending ? __('staff.dashboard.suggested_awaiting', ['suggested' => $suggested]) : __('staff.dashboard.suggested_confirmed', ['suggested' => $suggested, 'confirmed' => $confirmed]) }}</span>
                                    @endif
                                </span>
                                <span class="dept-complaint__age" title="{{ $exactDate }}">{{ $filedLabel }}</span>
                            </a>
                        </article>
                    @empty
                        <div class="dept-empty">
                            <span class="dept-empty__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
                            <strong>{{ __('staff.dashboard.no_priority_complaints') }}</strong>
                            <p>{{ __('staff.dashboard.no_priority_complaints_body') }}</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <aside class="dept-panel dept-panel--activity" aria-label="{{ __('staff.dashboard.activity_title') }}">
                @include('staff.dashboard.partials.activity', ['activityId' => 'department-activity-title'])
            </aside>
        </div>
    </div>
</div>
@endsection
