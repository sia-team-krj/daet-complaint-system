@extends('staff.layouts.app')

@section('title', __('staff.dashboard.title'))
@section('body-class', 'page-staff-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@php
    $dept = $department;
    $deptName = $dept?->name ?? __('nav.department_pending');
    $deptCode = $dept?->code;
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
    $verifyCount = (int) ($stats['new'] ?? 0);
    $statCards = [
        ['key' => 'pending', 'label' => __('staff.stats.pending_review'), 'value' => $stats['new'] ?? 0, 'icon' => 'clock', 'href' => route('staff.complaints.index', ['review' => 'pending']), 'tone' => 'amber'],
        ['key' => 'progress', 'label' => __('staff.stats.in_progress'), 'value' => $stats['inProgress'] ?? 0, 'icon' => 'progress', 'href' => route('staff.complaints.index', ['status' => 'in_progress']), 'tone' => 'navy'],
        ['key' => 'resolved', 'label' => __('staff.stats.resolved_this_month'), 'value' => $stats['resolvedThisMonth'] ?? 0, 'icon' => 'check', 'href' => route('staff.complaints.index', ['status' => 'resolved']), 'tone' => 'green'],
        ['key' => 'avg', 'label' => __('staff.stats.avg_days_open'), 'value' => $avgNum, 'unit' => $avgUnit, 'icon' => 'calendar', 'href' => null, 'tone' => 'sky'],
    ];
    $pCounts = $priorityCounts ?? [];
    $filters = [
        ['key' => '', 'label' => __('staff.dashboard.all'), 'count' => $pCounts['all'] ?? null],
        ['key' => 'pending', 'label' => __('staff.dashboard.pending_verification'), 'count' => $pCounts['pending'] ?? null],
        ['key' => 'needs_review', 'label' => __('staff.dashboard.needs_review'), 'count' => $pCounts['needs_review'] ?? null],
        ['key' => 'possible_duplicate', 'label' => __('staff.dashboard.possible_duplicate'), 'count' => $pCounts['possible_duplicate'] ?? null],
    ];
    $activeFilter = $priorityFilter ?? request('priority_filter', '');
@endphp

@section('staff-content')
<div class="transparency-page dept-dashboard">
    <div class="tr-container dept-dashboard__shell">
        <div class="dept-dashboard__grid">
            <div class="dept-dashboard__main">
                <section class="dept-hero-card" aria-labelledby="dept-welcome-title">
                    <div class="dept-hero-card__bg" aria-hidden="true"></div>
                    <div class="dept-hero-card__inner">
                        <div class="dept-hero-card__top">
                            <div class="dept-hero-card__copy">
                                <p class="dept-hero-card__subtitle" id="dept-welcome-title">{{ __('staff.dashboard.welcome', ['name' => $user->first_name]) }}</p>
                                <div class="dept-identity-card" aria-label="{{ __('staff.dashboard.identity_label') }}">
                                    <div class="dept-identity-card__row">
                                        <span class="dept-identity-card__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M6 21V8l6-4 6 4v13"/><path d="M10 10h4"/></svg>
                                        </span>
                                        <span class="dept-identity-card__name">{{ $deptName }}</span>
                                    </div>
                                    <p class="dept-identity-card__staff"><span class="dept-identity-card__role">{{ __('staff.dashboard.identity_role') }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dept-stats" aria-label="{{ __('staff.dashboard.overview') }}">
                    @foreach($statCards as $stat)
                        @php $tag = $stat['href'] ? 'a' : 'div'; @endphp
                        <{{ $tag }} @if($stat['href']) href="{{ $stat['href'] }}" @endif class="dept-stat {{ $stat['href'] ? 'dept-stat--link' : '' }}" @if($stat['href']) aria-label="{{ $stat['label'] }}: {{ $stat['value'] }}{{ !empty($stat['unit']) ? ' '.$stat['unit'] : '' }}" @endif>
                            <span class="tr-stat__icon tr-stat__icon--{{ $stat['tone'] }} dept-si dept-si--{{ $stat['tone'] === 'navy' ? 'navy' : $stat['tone'] }}" aria-hidden="true">
                                @if($stat['icon'] === 'clock')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>
                                @elseif($stat['icon'] === 'progress')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="M12 18v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="m16.24 16.24 2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="m16.24 7.76 2.83-2.83"/></svg>
                                @elseif($stat['icon'] === 'check')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                @endif
                            </span>
                            <span>
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
                        <a href="{{ route('staff.complaints.index') }}" class="dept-text-link">{{ __('staff.dashboard.open_full_queue') }} <span aria-hidden="true">→</span></a>
                    </div>

                    <form data-loading-submit method="GET" action="{{ route('staff.dashboard') }}#priority-complaints-title" class="dept-filters tr-card" role="search">
                        <div class="dept-filters__row">
                            <label class="tr-search dept-filters__search" for="priority-q">
                                <span class="sr-only">{{ __('staff.dashboard.search_placeholder') }}</span>
                                <svg class="tr-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input id="priority-q" type="search" name="q" value="{{ $prioritySearch }}" placeholder="{{ __('staff.dashboard.search_placeholder') }}" autocomplete="off">
                            </label>
                            <button type="submit" class="dept-filters__submit">{{ __('staff.dashboard.search') }}</button>
                        </div>
                        <div class="dept-chips" role="group" aria-label="{{ __('staff.dashboard.filters') }}">
                            @foreach($filters as $filter)
                                @php
                                    $isActive = ($activeFilter ?? '') === $filter['key'] || ($filter['key'] === '' && ($activeFilter ?? '') === '');
                                @endphp
                                <button type="submit" name="priority_filter" value="{{ $filter['key'] }}" class="dept-chip-filter {{ $isActive ? 'is-selected' : '' }}" @if($isActive) aria-pressed="true" @else aria-pressed="false" @endif>{{ $filter['label'] }} @if(is_numeric($filter['count']))<span class="dept-chip__count">{{ $filter['count'] }}</span>@endif</button>
                            @endforeach
                        </div>
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
                                $priorityStrip = match ($priorityKey) { 'critical', 'urgent' => 'critical', 'elevated' => 'elevated', default => 'routine' };
                                $categoryLabel = $complaint->category ? str($complaint->category)->replace('_', ' ')->title()->toString() : __('staff.dashboard.category_fallback');
                                $suggested = $complaint->suggestedPriorityEnum?->label() ?? __('staff.dashboard.confirmed_pending');
                                $confirmed = $complaint->confirmedPriorityEnum?->label() ?? __('staff.dashboard.confirmed_pending');
                                $suggestedKey = $complaint->suggestedPriorityEnum?->value;
                                $confirmedKey = $complaint->confirmedPriorityEnum?->value;
                                $confirmedPending = $confirmedKey === null;
                                $suggestedConfirmedSame = $confirmedKey !== null && $suggestedKey === $confirmedKey;
                                $showSuggestedConfirmed = ! $suggestedConfirmedSame;
                                $priorityClass = match ($priorityKey) {
                                    'critical', 'urgent' => 'dept-pill dept-pill--critical',
                                    'elevated' => 'dept-pill dept-pill--elevated',
                                    default => 'dept-pill dept-pill--routine',
                                };
                            @endphp
                            <article class="dept-complaint tr-card">
                                <a href="{{ route('staff.complaints.show', $complaint) }}" class="dept-complaint__link" aria-label="{{ __('staff.review') }} {{ $complaint->ticket_id }}">
                                    <span class="dept-complaint__complaint">
                                        @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                        <span class="dept-complaint__text">
                                            <span class="dept-complaint__title">{{ $complaint->title }}</span>
                                            <span class="dept-complaint__meta"><span class="ticket-id dept-ticket">{{ $complaint->ticket_id }}</span><span aria-hidden="true"> · </span>{{ $categoryLabel }}</span>
                                        </span>
                                    </span>

                                    <span class="dept-complaint__cell">
                                        <span class="status-pill {{ $statusKey }} dept-complaint__status--quiet"><span class="status-pill__dot" aria-hidden="true"></span>{{ $complaint->statusEnum->label() }}</span>
                                    </span>

                                    <span class="dept-complaint__cell">
                                        <span class="dept-priority dept-priority--{{ $priorityKey }}">{{ $priorityEnum?->label() ?? __('statuses.priority.routine') }}</span>
                                        @if($showSuggestedConfirmed)
                                            @if($confirmedPending)
                                                <span class="dept-complaint__subtext">{{ __('staff.dashboard.suggested_awaiting', ['suggested' => $suggested]) }}</span>
                                            @else
                                                <span class="dept-complaint__subtext">{{ __('staff.dashboard.suggested_confirmed', ['suggested' => $suggested, 'confirmed' => $confirmed]) }}</span>
                                            @endif
                                        @endif
                                    </span>

                                    <span class="dept-complaint__age">
                                        <span class="dept-age" title="{{ $exactDate }}">{{ $filedLabel }}</span>
                                    </span>
                                </a>
                            </article>
                        @empty
                            <div class="tr-card empty-state dept-empty">
                                <span class="empty-state__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                                </span>
                                <strong>{{ __('staff.dashboard.no_priority_complaints') }}</strong>
                                <p>{{ __('staff.dashboard.no_priority_complaints_body') }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="dept-dashboard__side dept-dashboard__side--in-main">
                @include('staff.dashboard.partials.activity', ['activityId' => 'department-activity-title'])
            </aside>
        </div>
    </div>
</div>

@endsection
