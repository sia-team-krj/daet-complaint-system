@extends('admin.layouts.app')

@section('title', 'Admin Dashboard — Daet Listens')
@section('admin-content')
<div class="admin-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">System overview</h1>
            <p class="admin-page-description">Monitor service requests, department routing, and unresolved community issues across Daet.</p>
        </div>
        <div class="admin-header-actions">
            <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary">Manage users</a>
            <a href="{{ route('admin.complaints.index') }}" class="admin-button admin-button--primary">
                Review all complaints
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
            </a>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-metric-grid" aria-label="System metrics">
        <article class="admin-metric">
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
            <strong class="admin-metric-value">{{ $stats['avgDays'] ?? '—' }}<small>{{ $stats['avgDays'] !== null ? ' days' : '' }}</small></strong>
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

        <div class="admin-department-grid">
            @forelse($departments ?? [] as $department)
                <a class="admin-department-card" href="{{ route('admin.complaints.index', ['department' => $department->id]) }}">
                    <span class="admin-department-code">{{ $department->code }}</span>
                    <span class="admin-department-name">{{ $department->name }}</span>
                    <span class="admin-department-meta">{{ $department->pending_count ?? 0 }} pending · {{ $department->staff_count ?? 0 }} staff</span>
                </a>
            @empty
                <p class="admin-empty-state">No departments have been created yet.</p>
            @endforelse
        </div>
    </section>

    @if(($unassignedComplaints ?? collect())->isNotEmpty())
        <section class="admin-section" aria-labelledby="unassigned-title">
            <div class="admin-section-heading">
                <div>
                    <h2 id="unassigned-title" class="admin-section-title admin-section-title--warning">Unassigned complaints</h2>
                    <p class="admin-section-description">These complaints need a department before they can move forward.</p>
                </div>
            </div>

            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr><th>Ticket</th><th>Citizen</th><th>Category</th><th>Status</th><th>Filed</th><th><span class="sr-only">Action</span></th></tr>
                    </thead>
                    <tbody>
                        @foreach($unassignedComplaints as $complaint)
                            <tr>
                                <td><span class="admin-ticket-id">{{ $complaint->ticket_id }}</span></td>
                                <td>{{ $complaint->user?->full_name ?? 'Unknown resident' }}</td>
                                <td>{{ str($complaint->category)->replace('_', ' ')->title() }}</td>
                                <td><span class="admin-status admin-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span></td>
                                <td class="admin-muted-cell">{{ $complaint->created_at?->format('M j, Y') }}</td>
                                <td><a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-button admin-button--compact admin-button--secondary">Manage</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="admin-section" aria-labelledby="recent-complaints-title">
        <div class="admin-section-heading">
            <div>
                <h2 id="recent-complaints-title" class="admin-section-title">Recent complaints</h2>
                <p class="admin-section-description">The latest requests received across all municipal offices.</p>
            </div>
            <a href="{{ route('admin.complaints.index') }}" class="admin-text-link">View all <span aria-hidden="true">→</span></a>
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr><th>Ticket</th><th>Citizen</th><th>Department</th><th>Status</th><th>Filed</th><th><span class="sr-only">Action</span></th></tr>
                </thead>
                <tbody>
                    @forelse($recentComplaints ?? [] as $complaint)
                        <tr>
                            <td><span class="admin-ticket-id">{{ $complaint->ticket_id }}</span></td>
                            <td>{{ $complaint->user?->full_name ?? 'Unknown resident' }}</td>
                            <td>{{ $complaint->department?->name ?? 'Unassigned' }}</td>
                            <td><span class="admin-status admin-status--{{ str($complaint->status)->slug() }}">{{ $complaint->statusEnum->label() }}</span></td>
                            <td class="admin-muted-cell">{{ $complaint->created_at?->format('M j, Y') }}</td>
                            <td><a href="{{ route('admin.complaints.show', $complaint) }}" class="admin-button admin-button--compact admin-button--secondary">Manage</a></td>
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
