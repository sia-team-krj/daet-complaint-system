@extends('admin.layouts.app')

@section('title', 'Admin Dashboard - Daet Listens')
@section('admin-content')
<div class="admin-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">System overview</h1>
            <p class="admin-page-description">Monitor service requests, department routing, and unresolved community issues across Daet.</p>
        </div>
        <div class="admin-header-actions">
            <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary admin-header-actions__secondary">Manage users</a>
            <a href="{{ route('admin.complaints.index') }}" class="admin-button admin-button--primary admin-header-actions__primary">
                Review all complaints
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
            </a>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-metric-grid admin-metric-grid--dashboard" aria-label="System metrics">
        <article class="admin-metric admin-metric--hero-mobile">
            <span class="admin-metric-label">Total complaints</span>
            <strong class="admin-metric-value">{{ $stats['total'] ?? 0 }}</strong>
        </article>
        <article class="admin-metric">
            <span class="admin-metric-label">Unassigned</span>
            <strong class="admin-metric-value">{{ $stats['unassigned'] ?? 0 }}</strong>
        </article>
        <article class="admin-metric">
            <span class="admin-metric-label">In progress</span>
            <strong class="admin-metric-value">{{ $stats['inProgress'] ?? 0 }}</strong>
        </article>
        <article class="admin-metric">
            <span class="admin-metric-label">Resolved today</span>
            <strong class="admin-metric-value">{{ $stats['resolvedToday'] ?? 0 }}</strong>
        </article>
        <article class="admin-metric">
            <span class="admin-metric-label">Average resolution</span>
            <strong class="admin-metric-value admin-metric-value--with-unit">{{ $stats['avgDays'] ?? '-' }}@if($stats['avgDays'] !== null)<small class="admin-metric-unit">days</small>@endif</strong>
        </article>
    </section>

    <section class="admin-section" aria-labelledby="department-overview-title">
        <div class="admin-section-heading">
            <div>
                <h2 id="department-overview-title" class="admin-section-title">Departments</h2>
                <p class="admin-section-description">Select an office to review its current complaint queue.</p>
            </div>
            <a href="{{ route('admin.departments.index') }}" class="admin-text-link">Manage departments <span aria-hidden="true">→</span></a>
        </div>

        <div class="admin-department-grid admin-department-grid--dashboard" role="list">
            @forelse($departments ?? [] as $department)
                @php $pending = (int) ($department->pending_count ?? 0); @endphp
                <a class="admin-department-card {{ $pending > 0 ? 'admin-department-card--has-pending' : 'admin-department-card--clear' }}" data-dept="{{ $department->code }}" href="{{ route('admin.complaints.index', ['department' => $department->id]) }}" role="listitem">
                    <span class="admin-department-main">
                        <span class="admin-department-code" data-dept="{{ $department->code }}">{{ $department->code }}</span>
                        <span class="admin-department-name">{{ $department->name }}</span>
                    </span>
                    <span class="admin-department-meta"><span class="admin-department-meta__pending">{{ $pending }} pending</span><span class="admin-department-meta__sep" aria-hidden="true"> · </span><span class="admin-department-meta__staff">{{ $department->staff_count ?? 0 }} staff</span></span>
                    <svg class="admin-department-card__chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @empty
                <p class="admin-empty-state">No departments have been created yet.</p>
            @endforelse
        </div>
    </section>

    @if(($unassignedComplaints ?? collect())->isNotEmpty())
        <section class="admin-section" aria-labelledby="unassigned-title">
            <div class="admin-section-heading">
                <div class="admin-section-heading__copy">
                    <h2 id="unassigned-title" class="admin-section-title admin-section-title--warning">Unassigned complaints</h2>
                    <p class="admin-section-description">These complaints need a department before they can move forward.</p>
                </div>
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

    <section class="admin-section" aria-labelledby="recent-complaints-title">
        <div class="admin-section-heading">
            <div class="admin-section-heading__copy">
                <h2 id="recent-complaints-title" class="admin-section-title">Recent complaints</h2>
                <p class="admin-section-description">The latest requests received across all municipal offices.</p>
            </div>
            <a href="{{ route('admin.complaints.index') }}" class="admin-text-link">View all @if(($stats['total'] ?? 0) > 5)({{ number_format((int) $stats['total']) }}) @endif<span aria-hidden="true">→</span></a>
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
@endsection
