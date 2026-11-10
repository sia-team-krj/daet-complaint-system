@extends('admin.layouts.app')

@section('title', 'Admin Dashboard - Daet Listens')
@section('body-class', 'admin-layout page-admin-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@php
    $statCards = [
        ['key' => 'total', 'label' => 'Total complaints', 'value' => $stats['total'] ?? 0, 'icon' => 'calendar', 'href' => route('admin.complaints.index'), 'tone' => 'sky'],
        ['key' => 'unassigned', 'label' => 'Unassigned', 'value' => $stats['unassigned'] ?? 0, 'icon' => 'clock', 'href' => route('admin.complaints.index', ['department' => 'unassigned']), 'tone' => 'amber'],
        ['key' => 'progress', 'label' => 'In progress', 'value' => $stats['inProgress'] ?? 0, 'icon' => 'progress', 'href' => route('admin.complaints.index', ['status' => \App\Enums\ComplaintStatus::InProgress->value]), 'tone' => 'navy'],
        ['key' => 'resolved', 'label' => 'Resolved today', 'value' => $stats['resolvedToday'] ?? 0, 'icon' => 'check', 'href' => route('admin.complaints.index', ['status' => \App\Enums\ComplaintStatus::Resolved->value]), 'tone' => 'green'],
    ];
@endphp

@section('admin-content')
<div class="transparency-page dept-dashboard">
    <div class="tr-container dept-dashboard__shell">
        <div class="dept-dashboard__grid">
            <div class="dept-dashboard__main">
                <section class="dept-hero-card" aria-labelledby="admin-welcome-title">
                    <div class="dept-hero-card__bg" aria-hidden="true"></div>
                    <div class="dept-hero-card__inner">
                        <div class="dept-hero-card__top">
                            <div class="dept-hero-card__copy">
                                <p class="dept-hero-card__subtitle" id="admin-welcome-title">System overview</p>
                                <div class="dept-identity-card">
                                    <div class="dept-identity-card__row">
                                        <span class="dept-identity-card__icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                        </span>
                                        <span class="dept-identity-card__name">Daet Municipal Administration</span>
                                    </div>
                                </div>
                            </div>
                            <div class="dept-hero-card__actions">
                                <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary">Manage users</a>
                                <a href="{{ route('admin.complaints.index') }}" class="admin-button admin-button--primary">Review all</a>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dept-stats" aria-label="System metrics">
                    @foreach($statCards as $stat)
                        @php $tag = $stat['href'] ? 'a' : 'div'; @endphp
                        <{{ $tag }} @if($stat['href']) href="{{ $stat['href'] }}" @endif class="dept-stat {{ $stat['href'] ? 'dept-stat--link' : '' }}">
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
                                <span class="dept-stat__value">{{ $stat['value'] }}</span>
                                <span class="dept-stat__label">{{ $stat['label'] }}</span>
                            </span>
                        </{{ $tag }}>
                    @endforeach
                </section>

                @include('admin.partials.flash')

                <section class="dept-section" aria-labelledby="departments-title">
                    <div class="dept-section-head">
                        <div>
                            <h2 id="departments-title" class="dept-section-title">Departments</h2>
                            <p class="dept-section-sub">Select an office to review its current complaint queue.</p>
                        </div>
                        <a href="{{ route('admin.departments.index') }}" class="dept-text-link">Manage departments <span aria-hidden="true">→</span></a>
                    </div>

                    <div class="dept-department-grid">
                        @forelse($departments ?? [] as $department)
                            @php $pending = (int) ($department->pending_count ?? 0); @endphp
                            <a href="{{ route('admin.complaints.index', ['department' => $department->id]) }}" class="dept-department-card tr-card">
                                <span class="dept-department-card__content">
                                    <span class="dept-department-card__head">
                                        <span class="dept-badge dept-badge--{{ strtolower($department->code) }}" data-dept="{{ $department->code }}">{{ $department->code }}</span>
                                        <span class="dept-department-card__name">{{ $department->name }}</span>
                                    </span>
                                    <span class="dept-department-card__meta">
                                        <span>{{ $pending }} pending</span>
                                        <span aria-hidden="true">·</span>
                                        <span>{{ $department->staff_count ?? 0 }} staff</span>
                                    </span>
                                </span>
                                <svg class="dept-department-card__chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </a>
                        @empty
                            <p class="dept-empty">No departments have been created yet.</p>
                        @endforelse
                    </div>
                </section>

                @if(($unassignedComplaints ?? collect())->isNotEmpty())
                    <section class="dept-priority" aria-labelledby="unassigned-title">
                        <div class="dept-section-head">
                            <div>
                                <h2 id="unassigned-title" class="dept-section-title">Unassigned complaints</h2>
                                <p class="dept-section-sub">These complaints need a department before they can move forward.</p>
                            </div>
                        </div>

                        <div class="dept-priority-list">
                            @foreach($unassignedComplaints as $complaint)
                                <article class="dept-complaint tr-card">
                                    <a href="{{ route('admin.complaints.show', $complaint) }}" class="dept-complaint__link">
                                        <span class="dept-complaint__complaint">
                                            @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                            <span class="dept-complaint__text">
                                                <span class="dept-complaint__title">{{ str($complaint->category)->replace('_', ' ')->title() }}</span>
                                                <span class="dept-complaint__meta"><span class="ticket-id dept-ticket">{{ $complaint->ticket_id }}</span><span aria-hidden="true"> · </span>{{ $complaint->user?->full_name ?? 'Unknown resident' }}</span>
                                            </span>
                                        </span>
                                        <span class="dept-complaint__cell">
                                            <span class="status-pill {{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span>
                                        </span>
                                        <span class="dept-complaint__age">
                                            <span class="dept-age" title="{{ $complaint->created_at?->format('M j, Y g:i A') }}">{{ $complaint->created_at?->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="dept-priority" aria-labelledby="recent-complaints-title">
                    <div class="dept-section-head">
                        <div>
                            <h2 id="recent-complaints-title" class="dept-section-title">Recent complaints</h2>
                            <p class="dept-section-sub">The latest requests received across all municipal offices.</p>
                        </div>
                        <a href="{{ route('admin.complaints.index') }}" class="dept-text-link">View all @if(($stats['total'] ?? 0) > 5)({{ number_format((int) $stats['total']) }}) @endif<span aria-hidden="true">→</span></a>
                    </div>

                    <div class="dept-priority-list">
                        @forelse($recentComplaints ?? [] as $complaint)
                            <article class="dept-complaint tr-card">
                                <a href="{{ route('admin.complaints.show', $complaint) }}" class="dept-complaint__link">
                                    <span class="dept-complaint__complaint">
                                        @include('pages.transparency.partials.category-icon', ['category' => $complaint->category])
                                        <span class="dept-complaint__text">
                                            <span class="dept-complaint__title">{{ $complaint->title }}</span>
                                            <span class="dept-complaint__meta"><span class="ticket-id dept-ticket">{{ $complaint->ticket_id }}</span><span aria-hidden="true"> · </span>{{ $complaint->department?->name ?? 'Unassigned' }}</span>
                                        </span>
                                    </span>
                                    <span class="dept-complaint__cell">
                                        <span class="status-pill {{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span>
                                    </span>
                                    <span class="dept-complaint__age">
                                        <span class="dept-age" title="{{ $complaint->created_at?->format('M j, Y g:i A') }}">{{ $complaint->created_at?->diffForHumans() }}</span>
                                    </span>
                                </a>
                            </article>
                        @empty
                            <div class="tr-card empty-state dept-empty">
                                <strong>No complaints have been filed yet.</strong>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
