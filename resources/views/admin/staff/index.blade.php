@extends('admin.layouts.app')

@section('title', 'Staff Accounts — Admin')
@section('admin-content')
<div class="admin-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Staff accounts</h1>
            <p class="admin-page-description">Manage department assignments and access for municipal staff accounts.</p>
        </div>
        <div class="admin-header-actions">
            <a href="{{ route('admin.staff.create') }}" class="admin-button admin-button--secondary">Create directly</a>
            <a href="{{ route('admin.invitations.index') }}" class="admin-button admin-button--primary">Invite staff</a>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-table-wrap" aria-label="Staff accounts">
        <table class="admin-table">
            <thead>
                <tr><th>Name</th><th>Email</th><th>Department</th><th>Contact</th><th>Status</th><th>Manage access</th></tr>
            </thead>
            <tbody>
                @forelse($staff as $staffMember)
                    <tr>
                        <td><span class="admin-ticket-id">{{ $staffMember->full_name }}</span></td>
                        <td>{{ $staffMember->email }}</td>
                        <td>{{ $staffMember->department?->name ?? 'Unassigned' }}</td>
                        <td class="admin-muted-cell">{{ $staffMember->contact_number ?? 'Not provided' }}</td>
                        <td><span class="admin-status {{ $staffMember->is_active ? 'admin-status--active' : 'admin-status--revoked' }}">{{ $staffMember->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>
                            <div class="admin-staff-actions">
                                <form method="POST" action="{{ route('admin.staff.update-department', $staffMember) }}">
                                    @csrf
                                    @method('PATCH')
                                    <label class="sr-only" for="department-{{ $staffMember->id }}">Department for {{ $staffMember->full_name }}</label>
                                    <select id="department-{{ $staffMember->id }}" name="department_id" class="admin-input admin-input--compact" onchange="this.form.submit()">
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" @selected((string) $staffMember->department_id === (string) $department->id)>{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <form method="POST" action="{{ route('admin.staff.toggle', $staffMember) }}" onsubmit="return confirm('{{ $staffMember->is_active ? 'Deactivate' : 'Activate' }} {{ $staffMember->full_name }}?')">
                                    @csrf
                                    <button type="submit" class="admin-button admin-button--compact {{ $staffMember->is_active ? 'admin-button--danger-ghost' : 'admin-button--secondary' }}">{{ $staffMember->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-empty-cell">No staff accounts have been created.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
