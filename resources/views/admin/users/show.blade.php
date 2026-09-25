@extends('admin.layouts.app')

@section('title', $user->full_name . ' — User Management')
@section('admin-content')
@php
    $isCurrentUser = auth()->id() === $user->id;
    $roleLabel = match ($user->role) {
        'admin' => 'Administrator',
        'staff' => 'Staff',
        default => 'Resident',
    };
    $roleClass = match ($user->role) {
        'admin' => 'admin-role-badge--admin',
        'staff' => 'admin-role-badge--staff',
        default => 'admin-role-badge--citizen',
    };
@endphp

<div class="admin-page admin-user-detail-page">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.users.index') }}">User management</a>
        <span aria-hidden="true">/</span>
        <span>{{ $user->full_name }}</span>
    </nav>

    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Account details</h1>
            <p class="admin-page-description">Review identity, access, assignment, and the audit history for this account.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="admin-button admin-button--secondary">Back to directory</a>
    </header>

    @include('admin.partials.flash')

    <div class="admin-detail-layout">
        <div class="admin-detail-main">
            <section class="admin-panel" aria-labelledby="account-identity-title">
                <div class="admin-panel-heading">
                    <div>
                        <h2 id="account-identity-title" class="admin-panel-title">Account identity</h2>
                        <p class="admin-section-description">Real identity and contact information are restricted to administrators.</p>
                    </div>
                    <span class="admin-status {{ $user->is_active ? 'admin-status--active' : 'admin-status--revoked' }}">
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-user-profile-head">
                        <span class="admin-user-avatar admin-user-avatar--large" aria-hidden="true">{{ $user->initials }}</span>
                        <div class="admin-user-profile-copy">
                            <h2>{{ $user->full_name }}</h2>
                            <p>{{ $user->email }}</p>
                            <div class="admin-user-profile-tags">
                                <span class="admin-role-badge {{ $roleClass }}">{{ $roleLabel }}</span>
                                @if($user->department)
                                    <span class="admin-profile-department">{{ $user->department->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <dl class="admin-detail-list admin-user-detail-list">
                        <div>
                            <dt>Contact number</dt>
                            <dd>{{ $user->contact_number ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt>Barangay</dt>
                            <dd>{{ $user->barangay ?: 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt>Account ID</dt>
                            <dd>#{{ str_pad((string) $user->id, 5, '0', STR_PAD_LEFT) }}</dd>
                        </div>
                        <div>
                            <dt>Joined</dt>
                            <dd>{{ $user->created_at?->format('F j, Y') ?: 'Not available' }}</dd>
                        </div>
                        <div>
                            <dt>Last updated</dt>
                            <dd>{{ $user->updated_at?->format('F j, Y \a\t g:i A') ?: 'Not available' }}</dd>
                        </div>
                        <div>
                            <dt>{{ $user->role === 'staff' ? 'Assigned complaints' : 'Complaints filed' }}</dt>
                            <dd>{{ number_format($user->role === 'staff' ? $user->assigned_complaints_count : $user->complaints_count) }}</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="admin-panel" aria-labelledby="edit-account-title">
                <div class="admin-panel-heading">
                    <div>
                        <h2 id="edit-account-title" class="admin-panel-title">Edit account</h2>
                        <p class="admin-section-description">Update permitted profile fields or change this account’s access assignment.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-panel-body admin-form-stack">
                    @csrf
                    @method('PATCH')

                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>First name</span>
                            <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="admin-input" autocomplete="given-name" required maxlength="100">
                            @error('first_name')<span class="admin-field-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="admin-field">
                            <span>Last name</span>
                            <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="admin-input" autocomplete="family-name" required maxlength="100">
                            @error('last_name')<span class="admin-field-error">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <label class="admin-field">
                        <span>Email address</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="admin-input" autocomplete="email" required maxlength="255">
                        @error('email')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>

                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Contact number</span>
                            <input type="text" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" class="admin-input" autocomplete="tel" inputmode="numeric" placeholder="09171234567">
                            @error('contact_number')<span class="admin-field-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="admin-field">
                            <span>Barangay</span>
                            <select name="barangay" class="admin-input">
                                <option value="">Not provided</option>
                                @foreach(\App\Models\User::BARANGAYS as $barangay)
                                    <option value="{{ $barangay }}" @selected((string) old('barangay', $user->barangay) === $barangay)>{{ $barangay }}</option>
                                @endforeach
                            </select>
                            @error('barangay')<span class="admin-field-error">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <div class="admin-form-grid">
                        <label class="admin-field">
                            <span>Role</span>
                            @if($isCurrentUser)
                                <input type="hidden" name="role" value="{{ old('role', $user->role) }}">
                                <select class="admin-input" disabled aria-describedby="self-role-note">
                                    <option value="{{ $user->role }}" selected>{{ $roleLabel }}</option>
                                </select>
                            @else
                                <select name="role" class="admin-input" required>
                                    <option value="citizen" @selected(old('role', $user->role) === 'citizen')>Resident</option>
                                    <option value="staff" @selected(old('role', $user->role) === 'staff')>Staff</option>
                                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrator</option>
                                </select>
                            @endif
                            @error('role')<span class="admin-field-error">{{ $message }}</span>@enderror
                            @if($isCurrentUser)
                                <small id="self-role-note" class="admin-field-help">Your own administrator role is locked to prevent lockout.</small>
                            @endif
                        </label>
                        <label class="admin-field">
                            <span>Department assignment</span>
                            <select name="department_id" class="admin-input">
                                <option value="">Unassigned</option>
                                @foreach($departments as $directoryDepartment)
                                    <option value="{{ $directoryDepartment->id }}" @selected((string) old('department_id', $user->department_id) === (string) $directoryDepartment->id)>
                                        {{ $directoryDepartment->name }}{{ $directoryDepartment->is_active ? '' : ' (inactive)' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')<span class="admin-field-error">{{ $message }}</span>@enderror
                            <small class="admin-field-help">Required when the role is Staff. Reassign assigned complaints before changing a staff role or department.</small>
                        </label>
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" class="admin-button admin-button--primary">Save account changes</button>
                        <a href="{{ route('admin.users.show', $user) }}" class="admin-button admin-button--secondary">Cancel</a>
                    </div>
                </form>
            </section>
        </div>

        <aside class="admin-detail-aside">
            <section class="admin-panel" aria-labelledby="access-control-title">
                <div class="admin-panel-heading">
                    <div>
                        <h2 id="access-control-title" class="admin-panel-title">Access control</h2>
                        <p class="admin-section-description">Pause access without removing account history.</p>
                    </div>
                </div>
                <div class="admin-panel-body admin-access-control">
                    <div class="admin-access-state">
                        <span class="admin-access-state-icon {{ $user->is_active ? 'is-active' : 'is-inactive' }}" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>
                        </span>
                        <div>
                            <strong>{{ $user->is_active ? 'Account is active' : 'Account is paused' }}</strong>
                            <span>{{ $user->is_active ? 'The user can sign in and use their assigned role.' : 'Sign-in is blocked until an administrator reactivates it.' }}</span>
                        </div>
                    </div>

                    @if($isCurrentUser)
                        <div class="admin-control-note">You cannot pause your own account from this screen.</div>
                    @elseif($user->is_active && $user->role === 'admin' && $activeAdminCount <= 1)
                        <div class="admin-control-note">This is the last active administrator. Promote another administrator before pausing it.</div>
                    @else
                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}" onsubmit="return confirm('{{ $user->is_active ? 'Pause' : 'Activate' }} this account?')">
                            @csrf
                            <button type="submit" class="admin-button {{ $user->is_active ? 'admin-button--danger-ghost' : 'admin-button--primary' }} admin-user-toggle-button">
                                {{ $user->is_active ? 'Pause account access' : 'Activate account access' }}
                            </button>
                        </form>
                    @endif
                </div>
            </section>

            <section class="admin-panel" aria-labelledby="account-activity-title">
                <div class="admin-panel-heading">
                    <div>
                        <h2 id="account-activity-title" class="admin-panel-title">Account activity</h2>
                        <p class="admin-section-description">Recent changes recorded against this account.</p>
                    </div>
                </div>
                <div class="admin-panel-body">
                    @if($activity->isNotEmpty())
                        <ol class="admin-timeline">
                            @foreach($activity as $log)
                                <li class="admin-timeline-item">
                                    <span class="admin-timeline-marker" aria-hidden="true"></span>
                                    <div>
                                        <p class="admin-timeline-status">{{ ucwords(str_replace('.', ' ', $log->action)) }}</p>
                                        <p class="admin-timeline-comment">{{ $log->description }}</p>
                                        <p class="admin-timeline-meta">{{ $log->actor?->full_name ?? 'System' }} · {{ $log->created_at?->format('M j, Y g:i A') }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="admin-activity-empty">
                            <p>No account changes have been recorded yet.</p>
                        </div>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
