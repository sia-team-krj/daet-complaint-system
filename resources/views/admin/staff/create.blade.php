@extends('admin.layouts.app')

@section('title', 'Create Staff Account — Admin')
@section('admin-content')
<div class="admin-page admin-page--narrow">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.staff.index') }}">Staff accounts</a>
        <span aria-hidden="true">/</span>
        <span>Create account</span>
    </nav>

    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Create staff account</h1>
            <p class="admin-page-description">For department onboarding, use an expiring invitation so account creation stays traceable and controlled.</p>
        </div>
        <a href="{{ route('admin.invitations.index') }}" class="admin-button admin-button--primary">Use invitation instead</a>
    </header>

    @include('admin.partials.flash')

    <section class="admin-panel">
        <div class="admin-panel-heading">
            <h2 class="admin-panel-title">Direct account creation</h2>
        </div>
        <form method="POST" action="{{ route('admin.staff.store') }}" class="admin-panel-body admin-form-stack">
            @csrf
            <div class="admin-form-grid">
                <label class="admin-field">
                    <span>First name</span>
                    <input type="text" name="first_name" class="admin-input" value="{{ old('first_name') }}" required maxlength="100">
                    @error('first_name')<span class="admin-field-error">{{ $message }}</span>@enderror
                </label>
                <label class="admin-field">
                    <span>Last name</span>
                    <input type="text" name="last_name" class="admin-input" value="{{ old('last_name') }}" required maxlength="100">
                    @error('last_name')<span class="admin-field-error">{{ $message }}</span>@enderror
                </label>
            </div>
            <label class="admin-field">
                <span>Email address</span>
                <input type="email" name="email" class="admin-input" value="{{ old('email') }}" required maxlength="255">
                @error('email')<span class="admin-field-error">{{ $message }}</span>@enderror
            </label>
            <label class="admin-field">
                <span>Contact number</span>
                <input type="text" name="contact_number" class="admin-input" value="{{ old('contact_number') }}" inputmode="numeric" placeholder="09171234567">
                @error('contact_number')<span class="admin-field-error">{{ $message }}</span>@enderror
            </label>
            <label class="admin-field">
                <span>Department</span>
                <select name="department_id" class="admin-input" required>
                    <option value="">Select a department</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<span class="admin-field-error">{{ $message }}</span>@enderror
            </label>
            <div class="admin-form-actions">
                <button type="submit" class="admin-button admin-button--primary">Create and email temporary password</button>
                <a href="{{ route('admin.staff.index') }}" class="admin-button admin-button--secondary">Cancel</a>
            </div>
        </form>
    </section>
</div>
@endsection
