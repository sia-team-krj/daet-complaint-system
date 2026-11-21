@extends('admin.layouts.app')

@section('title', 'Create Department - Admin')
@section('admin-content')
<div class="admin-page admin-page--narrow">
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.departments.index') }}">Departments</a>
        <span aria-hidden="true">/</span>
        <span>Create department</span>
    </nav>

    <header class="admin-page-header">
        <div>
            <h1 class="admin-page-title">Create department</h1>
            <p class="admin-page-description">
                A new office can receive complaints, hold staff accounts, and issue invitations.
                It starts active and can be archived later without losing anything.
            </p>
        </div>
    </header>

    @include('admin.partials.flash')

    <section class="admin-panel">
        <div class="admin-panel-heading">
            <h2 class="admin-panel-title">Office details</h2>
        </div>

        <form data-loading-submit method="POST" action="{{ route('admin.departments.store') }}" class="admin-panel-body admin-form-stack">
            @csrf

            <label class="admin-field">
                <span>Office name</span>
                <input type="text" name="name" class="admin-input" value="{{ old('name') }}" required maxlength="100"
                       placeholder="Human Resource Management Office" autofocus>
                @error('name')<span class="admin-field-error">{{ $message }}</span>@enderror
            </label>

            <label class="admin-field">
                <span>Office code</span>
                <input type="text" name="code" class="admin-input" value="{{ old('code') }}" required maxlength="10"
                       pattern="[A-Z0-9]+" autocapitalize="characters" spellcheck="false" placeholder="OM">
                @error('code')<span class="admin-field-error">{{ $message }}</span>@enderror
                <span class="admin-field-hint">
                    Up to 10 uppercase letters or digits. Must not already be in use - in use:
                    {{ $existingCodes->implode(', ') }}.
                </span>
            </label>

            <label class="admin-field">
                <span>Description</span>
                <textarea name="description" class="admin-input admin-textarea" rows="4" maxlength="255"
                          placeholder="What this office handles, so staff know when a complaint belongs here.">{{ old('description') }}</textarea>
                @error('description')<span class="admin-field-error">{{ $message }}</span>@enderror
            </label>

            <div class="admin-form-actions">
                <button type="submit" class="admin-button admin-button--primary">Create department</button>
                <a href="{{ route('admin.departments.index') }}" class="admin-button admin-button--secondary">Cancel</a>
            </div>
        </form>
    </section>

    {{-- Stated here because this is where the code is chosen. Categories now
         point at the office, so the code is a human identifier rather than a
         routing key; the paragraph tells an admin what the code is for and how
         to give it meaning. --}}
    <section class="admin-panel admin-department-routing-note">
        <div class="admin-panel-heading">
            <h2 class="admin-panel-title">What happens to the code you choose</h2>
        </div>
        <div class="admin-panel-body">
            <p>
                The code is a short identifier used in filters and exports. Complaint categories
                route to the office through the
                <a href="{{ route('admin.categories.index') }}">complaint categories</a>
                page, so an office receives work as soon as a category names it.
            </p>
            <p>
                A new office starts active and can hold staff, issue invitations, and receive
                categories immediately. The code can be changed at any time without stranding the
                categories already mapped to the office.
            </p>
        </div>
    </section>
</div>
@endsection