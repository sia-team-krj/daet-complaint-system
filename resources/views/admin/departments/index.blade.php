@extends('admin.layouts.app')

@section('title', 'Departments — Admin')
@section('admin-content')
<div class="admin-page">
    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Departments</h1>
            <p class="admin-page-description">Maintain the municipal offices that receive, route, and resolve public complaints.</p>
        </div>
    </header>

    @include('admin.partials.flash')

    <div class="admin-two-column-layout">
        <section class="admin-section admin-section--flush" aria-labelledby="department-list-title">
            <div class="admin-section-heading">
                <div>
                    <h2 id="department-list-title" class="admin-section-title">Department directory</h2>
                    <p class="admin-section-description">Active offices available for complaint routing and staff invitations.</p>
                </div>
            </div>

            <div class="admin-department-list">
                @forelse($departments as $department)
                    <article class="admin-department-list-item">
                        <div class="admin-department-list-heading">
                            <div>
                                <span class="admin-department-code">{{ $department->code }}</span>
                                <h3>{{ $department->name }}</h3>
                            </div>
                            <span class="admin-status {{ $department->is_active ? 'admin-status--active' : 'admin-status--revoked' }}">{{ $department->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <p>{{ $department->description ?: 'No office description has been added.' }}</p>
                        <dl class="admin-inline-stats">
                            <div><dt>Open complaints</dt><dd>{{ $department->open_complaints_count }}</dd></div>
                            <div><dt>Staff accounts</dt><dd>{{ $department->staff_count }}</dd></div>
                            <div><dt>Active invitations</dt><dd>{{ $department->active_invitations_count }}</dd></div>
                        </dl>
                        <div class="admin-department-list-actions">
                            <a href="{{ route('admin.complaints.index', ['department' => $department->id]) }}" class="admin-button admin-button--compact admin-button--secondary">View queue</a>
                            <a href="{{ route('admin.invitations.index') }}" class="admin-button admin-button--compact admin-button--secondary">Invite staff</a>
                        </div>
                    </article>
                @empty
                    <p class="admin-empty-state">No departments have been created.</p>
                @endforelse
            </div>

            @if($departments->hasPages())
                <div class="admin-pagination">{{ $departments->links() }}</div>
            @endif
        </section>

        <aside>
            <section class="admin-panel" aria-labelledby="new-department-title">
                <div class="admin-panel-heading">
                    <h2 id="new-department-title" class="admin-panel-title">Add department</h2>
                </div>
                <form method="POST" action="{{ route('admin.departments.store') }}" class="admin-panel-body admin-form-stack">
                    @csrf
                    <label class="admin-field">
                        <span>Office name</span>
                        <input type="text" name="name" class="admin-input" value="{{ old('name') }}" required maxlength="100" placeholder="Office of the Mayor">
                        @error('name')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="admin-field">
                        <span>Office code</span>
                        <input type="text" name="code" class="admin-input" value="{{ old('code') }}" required maxlength="10" pattern="[A-Z0-9]+" placeholder="OM">
                        @error('code')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <label class="admin-field">
                        <span>Description</span>
                        <textarea name="description" class="admin-input admin-textarea" rows="4" maxlength="255" placeholder="Services handled by this office.">{{ old('description') }}</textarea>
                        @error('description')<span class="admin-field-error">{{ $message }}</span>@enderror
                    </label>
                    <button type="submit" class="admin-button admin-button--primary">Create department</button>
                </form>
            </section>
        </aside>
    </div>
</div>
@endsection
