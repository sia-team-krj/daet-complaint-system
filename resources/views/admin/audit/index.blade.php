@extends('admin.layouts.app')

@section('title', 'Audit Trail — Admin')
@section('admin-content')
<div class="admin-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Audit trail</h1>
            <p class="admin-page-description">One immutable record of staff and administrator actions across every department, complaint, invitation, and account change.</p>
        </div>
    </header>

    <section class="admin-filter-panel" aria-label="Audit trail filters">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="admin-filter-grid admin-filter-grid--audit">
            <label class="admin-field">
                <span>Department</span>
                <select name="department_id" class="admin-input">
                    <option value="">All departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="admin-field">
                <span>Actor</span>
                <select name="actor_id" class="admin-input">
                    <option value="">All staff and admins</option>
                    @foreach($actors as $actor)
                        <option value="{{ $actor->id }}" @selected((string) request('actor_id') === (string) $actor->id)>{{ $actor->full_name }} ({{ ucfirst($actor->role) }})</option>
                    @endforeach
                </select>
            </label>

            <label class="admin-field">
                <span>Action</span>
                <select name="action" class="admin-input">
                    <option value="">All actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ str($action)->replace('.', ' ')->title() }}</option>
                    @endforeach
                </select>
            </label>

            <label class="admin-field admin-field--wide">
                <span>Search</span>
                <input type="search" name="search" class="admin-input" value="{{ request('search') }}" placeholder="Ticket, subject, or description">
            </label>

            <div class="admin-filter-actions">
                <button type="submit" class="admin-button admin-button--primary">Filter trail</button>
                @if(request()->hasAny(['department_id', 'actor_id', 'action', 'search']))
                    <a href="{{ route('admin.audit.index') }}" class="admin-button admin-button--secondary">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="admin-table-wrap" aria-label="Activity log entries">
        <table class="admin-table admin-table--audit">
            <thead>
                <tr><th>Subject</th><th>Action</th><th>Actor</th><th>Department</th><th>Details</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        $subject = $log->subject;
                        $actionLabel = str($log->action)->replace('.', ' ')->title();
                    @endphp
                    <tr>
                        <td>
                            @if($subject instanceof \App\Models\Complaint)
                                <a href="{{ route('admin.complaints.show', $subject) }}" class="admin-ticket-id">{{ $subject->ticket_id }}</a>
                            @elseif($subject)
                                <span class="admin-ticket-id">{{ class_basename($subject) }} #{{ $subject->getKey() }}</span>
                            @else
                                <span class="admin-muted-cell">System record</span>
                            @endif
                        </td>
                        <td><span class="admin-status admin-status--{{ str($log->action)->before('.')->slug() }}">{{ $actionLabel }}</span></td>
                        <td>
                            <span class="admin-audit-actor">{{ $log->actor?->full_name ?? $log->actor_name ?? 'System' }}</span>
                            <span class="admin-audit-role">{{ ucfirst($log->actor?->role ?? $log->actor_role ?? 'System') }}</span>
                        </td>
                        <td>{{ $log->department?->name ?? 'System-wide' }}</td>
                        <td class="admin-audit-comment">
                            {{ $log->description }}
                            @if($log->metadata)
                                <details class="admin-audit-metadata">
                                    <summary>View details</summary>
                                    <pre>{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                        </td>
                        <td class="admin-muted-cell">{{ $log->created_at?->format('M j, Y g:i A') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-empty-cell">No activity records match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($logs->hasPages())
        <div class="admin-pagination">{{ $logs->links() }}</div>
    @endif
</div>
@endsection
