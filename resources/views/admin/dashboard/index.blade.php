@extends('admin.layouts.app')

@section('title', 'Admin Dashboard - Daet Listens')
@section('body-class', 'page-admin-dashboard')

@include('partials.public-ui')
@include('pages.transparency.partials.component-styles')

@php
    $statCards = [
        ['key' => 'total', 'label' => 'Total complaints', 'value' => $stats['total'] ?? 0, 'icon' => 'calendar', 'tone' => 'sky'],
        ['key' => 'unassigned', 'label' => 'Unassigned', 'value' => $stats['unassigned'] ?? 0, 'icon' => 'clock', 'tone' => 'amber'],
        ['key' => 'progress', 'label' => 'In progress', 'value' => $stats['inProgress'] ?? 0, 'icon' => 'progress', 'tone' => 'navy'],
        ['key' => 'resolved', 'label' => 'Resolved today', 'value' => $stats['resolvedToday'] ?? 0, 'icon' => 'check', 'tone' => 'green'],
    ];
@endphp

@section('admin-content')
<div class="transparency-page admin-dashboard">
    <header class="tr-banner">
        <div class="tr-container" style="max-width: none; padding: 0 24px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap;">
                <div>
                    <h1 class="tr-h1">System overview</h1>
                    <p class="tr-privacy">Monitor service requests, department routing, and unresolved community issues across Daet.</p>
                </div>
                <div class="admin-header-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ route('admin.users.index') }}" class="tr-button" style="background: var(--surface); color: var(--navy-700); border: 1px solid var(--line); min-height: 44px; display: inline-flex; align-items: center; padding: 0 16px; border-radius: var(--radius-sm); font-weight: 600; text-decoration: none;">Manage users</a>
                    <a href="{{ route('admin.complaints.index') }}" class="tr-button" style="background: var(--gold-500); color: var(--navy-950); min-height: 44px; display: inline-flex; align-items: center; padding: 0 16px; border-radius: var(--radius-sm); font-weight: 700; text-decoration: none;">
                        Review all complaints
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin-left: 6px;"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="tr-container" style="max-width: none; padding: 0 24px;">
        @include('admin.partials.flash')

        <section class="tr-stats" aria-label="System metrics" style="margin-top: -16px; margin-bottom: 24px;">
            @foreach($statCards as $stat)
                <div class="tr-stat">
                    <span class="tr-stat__icon tr-stat__icon--{{ $stat['tone'] }}" aria-hidden="true">
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
                        <span class="tr-stat__value">{{ $stat['value'] }}</span>
                        <span class="tr-stat__label">{{ $stat['label'] }}</span>
                    </span>
                </div>
            @endforeach
        </section>

        {{-- Departments Section --}}
        <section class="tr-card" style="padding: 24px; margin-bottom: 24px;" aria-labelledby="department-overview-title">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                <div>
                    <h2 id="department-overview-title" class="tr-h2" style="font-size: 20px; font-weight: 700; color: var(--text);">Departments</h2>
                    <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted);">Select an office to review its current complaint queue.</p>
                </div>
                <a href="{{ route('admin.departments.index') }}" style="color: var(--navy-700); font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">Manage departments <span aria-hidden="true">→</span></a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;" role="list">
                @forelse($departments ?? [] as $department)
                    @php $pending = (int) ($department->pending_count ?? 0); @endphp
                    <a href="{{ route('admin.complaints.index', ['department' => $department->id]) }}" role="listitem" style="display: flex; align-items: center; justify-content: space-between; padding: 16px; background: var(--surface); border: 1px solid var(--line-soft); border-radius: var(--radius-lg); text-decoration: none; color: inherit; box-shadow: var(--shadow-card);">
                        <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                            <span class="dept-badge dept-badge--{{ strtolower($department->code) }}" data-dept="{{ $department->code }}" style="font-size: 11px; font-weight: 800; padding: 4px 8px; border-radius: 6px; background: rgba(16,37,69,0.08); color: var(--navy-800);">{{ $department->code }}</span>
                            <div style="min-width: 0;">
                                <div style="font-size: 15px; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $department->name }}</div>
                                <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">{{ $pending }} pending · {{ $department->staff_count ?? 0 }} staff</div>
                            </div>
                        </div>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="color: var(--muted); flex-shrink: 0;"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                @empty
                    <p style="color: var(--muted); font-size: 14px; margin: 0;">No departments have been created yet.</p>
                @endforelse
            </div>
        </section>

        {{-- Unassigned Complaints Section --}}
        @if(($unassignedComplaints ?? collect())->isNotEmpty())
            <section class="tr-card" style="padding: 24px; margin-bottom: 24px;" aria-labelledby="unassigned-title">
                <div style="margin-bottom: 16px;">
                    <h2 id="unassigned-title" class="tr-h2" style="font-size: 20px; font-weight: 700; color: var(--status-rejected-text);">Unassigned complaints</h2>
                    <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted);">These complaints need a department before they can move forward.</p>
                </div>

                <div class="admin-table-wrap">
                    <table class="admin-table admin-table--compact-mobile">
                        <thead>
                            <tr><th>Ticket</th><th>Citizen</th><th>Category</th><th>Status</th><th>Filed</th><th><span class="sr-only">Action</span></th></tr>
                        </thead>
                        <tbody>
                            @foreach($unassignedComplaints as $complaint)
                                <tr class="admin-complaint-row">
                                    <td class="admin-complaint-cell admin-complaint-cell--ticket" data-label="Ticket">
                                        <a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-ticket-id admin-ticket-link">{{ $complaint->ticket_id }}</a>
                                    </td>
                                    <td class="admin-complaint-cell admin-complaint-cell--citizen" data-label="Citizen">{{ $complaint->user?->full_name ?? 'Unknown resident' }}</td>
                                    <td class="admin-complaint-cell admin-complaint-cell--secondary" data-label="Category">
                                        <span class="admin-complaint-secondary">{{ str($complaint->category)->replace('_', ' ')->title() }}</span><span class="admin-complaint-meta-sep" aria-hidden="true"> · </span><span class="admin-complaint-filed-inline admin-muted-cell">{{ $complaint->created_at?->format('M j, Y') }}</span>
                                    </td>
                                    <td class="admin-complaint-cell admin-complaint-cell--status" data-label="Status"><span class="admin-status admin-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span></td>
                                    <td class="admin-complaint-cell admin-complaint-cell--filed admin-muted-cell" data-label="Filed"><span class="admin-complaint-filed-desktop">{{ $complaint->created_at?->format('M j, Y') }}</span></td>
                                    <td class="admin-complaint-cell admin-complaint-cell--action" data-label="Action"><a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-button admin-button--compact admin-button--secondary admin-complaint-action">Manage</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        {{-- Recent Complaints Section --}}
        <section class="tr-card" style="padding: 24px; margin-bottom: 60px;" aria-labelledby="recent-complaints-title">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                <div>
                    <h2 id="recent-complaints-title" class="tr-h2" style="font-size: 20px; font-weight: 700; color: var(--text);">Recent complaints</h2>
                    <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted);">The latest requests received across all municipal offices.</p>
                </div>
                <a href="{{ route('admin.complaints.index') }}" style="color: var(--navy-700); font-size: 14px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">View all @if(($stats['total'] ?? 0) > 5)({{ number_format((int) $stats['total']) }}) @endif<span aria-hidden="true">→</span></a>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table admin-table--compact-mobile">
                    <thead>
                        <tr><th>Ticket</th><th>Citizen</th><th>Department</th><th>Status</th><th>Filed</th><th><span class="sr-only">Action</span></th></tr>
                    </thead>
                    <tbody>
                        @forelse($recentComplaints ?? [] as $complaint)
                            <tr class="admin-complaint-row">
                                <td class="admin-complaint-cell admin-complaint-cell--ticket" data-label="Ticket">
                                    <a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-ticket-id admin-ticket-link">{{ $complaint->ticket_id }}</a>
                                </td>
                                <td class="admin-complaint-cell admin-complaint-cell--citizen" data-label="Citizen">{{ $complaint->user?->full_name ?? 'Unknown resident' }}</td>
                                <td class="admin-complaint-cell admin-complaint-cell--secondary" data-label="Department">
                                    <span class="admin-complaint-secondary">{{ $complaint->department?->name ?? 'Unassigned' }}</span><span class="admin-complaint-meta-sep" aria-hidden="true"> · </span><span class="admin-complaint-filed-inline admin-muted-cell">{{ $complaint->created_at?->format('M j, Y') }}</span>
                                </td>
                                <td class="admin-complaint-cell admin-complaint-cell--status" data-label="Status"><span class="admin-status admin-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span></td>
                                <td class="admin-complaint-cell admin-complaint-cell--filed admin-muted-cell" data-label="Filed"><span class="admin-complaint-filed-desktop">{{ $complaint->created_at?->format('M j, Y') }}</span></td>
                                <td class="admin-complaint-cell admin-complaint-cell--action" data-label="Action"><a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-button admin-button--compact admin-button--secondary admin-complaint-action">Manage</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="admin-empty-cell">No complaints have been filed yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<style>
    .admin-content .transparency-page { padding-top: 0; }
    .admin-content .tr-banner { padding-top: 32px; padding-bottom: 24px; }
</style>
@endsection
