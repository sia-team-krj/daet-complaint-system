@extends('admin.layouts.app')

@section('title', 'User Management — Admin')
@section('admin-content')
<div class="admin-page admin-user-management">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">User management</h1>
            <p class="admin-page-description">Review resident accounts, manage staff access, and keep every account aligned with its municipal role.</p>
        </div>
        <div class="admin-header-actions">
            <a href="{{ route('admin.invitations.index') }}" class="admin-button admin-button--secondary">Invite staff</a>
            <a href="{{ route('admin.staff.create') }}" class="admin-button admin-button--primary">Create staff</a>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-metric-grid admin-user-metric-grid" aria-label="Account summary">
        <div class="admin-metric">
            <span class="admin-metric-label">Total accounts</span>
            <strong class="admin-metric-value">{{ number_format($stats['total']) }}</strong>
            <small>All registered identities</small>
        </div>
        <div class="admin-metric">
            <span class="admin-metric-label">Active now</span>
            <strong class="admin-metric-value">{{ number_format($stats['active']) }}</strong>
            <small>Can sign in to the platform</small>
        </div>
        <div class="admin-metric">
            <span class="admin-metric-label">Inactive</span>
            <strong class="admin-metric-value">{{ number_format($stats['inactive']) }}</strong>
            <small>Access paused, history kept</small>
        </div>
        <div class="admin-metric">
            <span class="admin-metric-label">Staff accounts</span>
            <strong class="admin-metric-value">{{ number_format($stats['staff']) }}</strong>
            <small>Department personnel</small>
        </div>
        <div class="admin-metric">
            <span class="admin-metric-label">Residents</span>
            <strong class="admin-metric-value">{{ number_format($stats['citizens']) }}</strong>
            <small>Community accounts</small>
        </div>
    </section>

    <section class="admin-user-directory" aria-labelledby="user-directory-title">
        <div class="admin-section-heading">
            <div>
                <h2 id="user-directory-title" class="admin-section-title">Account directory</h2>
                <p class="admin-section-description">Search by identity or narrow the directory by access state and department.</p>
            </div>
            @if($users->total() > 0)
                <span class="admin-directory-count">{{ number_format($users->total()) }} {{ \Illuminate\Support\Str::plural('account', $users->total()) }}</span>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.users.index') }}" class="admin-filter-panel">
            <div class="admin-filter-grid admin-user-filter-grid">
                <label class="admin-field admin-field--search">
                    <span>Search accounts</span>
                    <input type="search" name="search" value="{{ $search }}" class="admin-input" placeholder="Name, email, phone, or barangay">
                </label>
                <label class="admin-field">
                    <span>Role</span>
                    <select name="role" class="admin-input">
                        <option value="">All roles</option>
                        <option value="citizen" @selected($role === 'citizen')>Resident</option>
                        <option value="staff" @selected($role === 'staff')>Staff</option>
                        <option value="admin" @selected($role === 'admin')>Administrator</option>
                    </select>
                </label>
                <label class="admin-field">
                    <span>Access</span>
                    <select name="status" class="admin-input">
                        <option value="">Any status</option>
                        <option value="active" @selected($status === 'active')>Active</option>
                        <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                    </select>
                </label>
                <label class="admin-field">
                    <span>Department</span>
                    <select name="department" class="admin-input">
                        <option value="">All departments</option>
                        <option value="unassigned" @selected($department === 'unassigned')>Unassigned</option>
                        @foreach($departments as $directoryDepartment)
                            <option value="{{ $directoryDepartment->id }}" @selected((string) $department === (string) $directoryDepartment->id)>
                                {{ $directoryDepartment->name }}{{ $directoryDepartment->is_active ? '' : ' (inactive)' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <div class="admin-filter-actions">
                    <button type="submit" class="admin-button admin-button--primary">Apply filters</button>
                    @if($search !== '' || $role !== '' || $status !== '' || $department !== '')
                        <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary">Clear</a>
                    @endif
                </div>
            </div>
        </form>

        @if($users->total() > 0)
            <div class="admin-table-wrap" aria-label="User directory">
                <table class="admin-table admin-user-table">
                    <caption class="sr-only">User account directory</caption>
                    <thead>
                        <tr>
                            <th scope="col">User</th>
                            <th scope="col">Role</th>
                            <th scope="col">Department</th>
                            <th scope="col">Access</th>
                            <th scope="col">Activity</th>
                            <th scope="col">Joined</th>
                            <th scope="col">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $directoryUser)
                            @php
                                $directoryRole = match ($directoryUser->role) {
                                    'admin' => 'Administrator',
                                    'staff' => 'Staff',
                                    default => 'Resident',
                                };
                                $directoryRoleClass = match ($directoryUser->role) {
                                    'admin' => 'admin-role-badge--admin',
                                    'staff' => 'admin-role-badge--staff',
                                    default => 'admin-role-badge--citizen',
                                };
                            @endphp
                            <tr>
                                <td data-label="User">
                                    <div class="admin-user-cell">
                                        <span class="admin-user-avatar" aria-hidden="true">{{ $directoryUser->initials }}</span>
                                        <span class="admin-user-copy">
                                            <strong>{{ $directoryUser->full_name }}</strong>
                                            <small>{{ $directoryUser->email }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Role">
                                    <span class="admin-role-badge {{ $directoryRoleClass }}">{{ $directoryRole }}</span>
                                </td>
                                <td data-label="Department">
                                    @if($directoryUser->department)
                                        <span class="admin-department-cell">{{ $directoryUser->department->name }}</span>
                                        <small class="admin-table-subtext">{{ $directoryUser->department->code }}</small>
                                    @else
                                        <span class="admin-muted-cell">Unassigned</span>
                                    @endif
                                </td>
                                <td data-label="Access">
                                    <span class="admin-status {{ $directoryUser->is_active ? 'admin-status--active' : 'admin-status--revoked' }}">
                                        {{ $directoryUser->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td data-label="Activity">
                                    <span class="admin-activity-count">
                                        {{ $directoryUser->role === 'staff' ? number_format($directoryUser->assigned_complaints_count) : number_format($directoryUser->complaints_count) }}
                                    </span>
                                    <small class="admin-table-subtext">{{ $directoryUser->role === 'staff' ? 'assigned' : 'filed' }}</small>
                                </td>
                                <td data-label="Joined">
                                    <span class="admin-date-cell">{{ $directoryUser->created_at?->format('M j, Y') }}</span>
                                </td>
                                <td data-label="Manage">
                                    <div class="admin-user-actions">
                                        <a href="{{ route('admin.users.show', $directoryUser) }}" class="admin-button admin-button--compact admin-button--secondary">Manage</a>
                                        <form method="POST" action="{{ route('admin.users.toggle', $directoryUser) }}" onsubmit="return confirm('{{ $directoryUser->is_active ? 'Deactivate' : 'Activate' }} this account?')">
                                            @csrf
                                            <button type="submit" class="admin-button admin-button--compact {{ $directoryUser->is_active ? 'admin-button--danger-ghost' : 'admin-button--secondary' }}">
                                                {{ $directoryUser->is_active ? 'Pause' : 'Enable' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="admin-pagination">{{ $users->links() }}</div>
            @endif
        @else
            <div class="admin-empty-state admin-user-empty-state">
                <div class="admin-user-empty-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="4"></circle><path d="M2 21a7 7 0 0 1 14 0"></path><path d="M19 8v6"></path><path d="M22 11h-6"></path></svg>
                </div>
                <h3>No accounts match these filters</h3>
                <p>Try a different search or clear the filters to return to the full directory.</p>
                @if($search !== '' || $role !== '' || $status !== '' || $department !== '')
                    <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary">Clear filters</a>
                @endif
            </div>
        @endif
    </section>

    <p class="admin-privacy-note">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
        Resident identity and contact details are visible only in this administrator workspace. Account changes are recorded in the audit trail.
    </p>
</div>
@endsection
